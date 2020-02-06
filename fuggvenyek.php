<?php

// Főmenüpntok láthatóságát központosítjuk, hogy a header.php-ban és a dasboard-on is fel lehessen használni, ne két helyen kelljen módosítani
function bejelentes_lathat() {
    return _jog(JOG_BEJELENTES_MEGTEKINT) || _jog(JOG_BEJELENTES_SAJAT_RESZL);
}
function munkalap_lathat() {
    return _jog(JOG_MUNKALAP_MEGTEKINT) || _jog(JOG_MUNKALAP_MEGTEKINT_SZALL) || _jog(JOG_MUNKALAP_MEGTEKINT_FELADAT) || _jog(JOG_UTFEL_MEGTEKINT) || _jog(JOG_RENDSZ_KARB_MEGTEKINT) || _jog(JOG_ESETI_FENN_MEGTEKINT);
}
function anyagigenyles_lathat() {
    return _jog(JOG_ANYAIGENLES_MEGTEKINT) || _jog(JOG_ANYAGREND_MEGTEKINT) || _jog(JOG_RAKTAR_MEGTEKINT) || _jog(JOG_CIKKTORZS_MEGTEKINT);
}
function projektek_lathat() {
    return 
        _jog(JOG_PRIGENY_S_KEZEL) || _jog(JOG_PRIGENY_SZ_MEGTEKINT) || _jog(JOG_PRIGENY_B_MEGTEKINT) || _jog(JOG_PRIGENY_EGY_MEGTEKINT) || _jog(JOG_PRIGENY_F_MEGTEKINT) || _jog(JOG_PRIGENY_TOBB_MEGTEKINT) || _jog(JOG_PRIGENY_T_MEGTEKINT) || _jog(JOG_PR_B_MEGTEKINT) || _jog(JOG_PR_F_MEGTEKINT) || _jog(JOG_PR_T_MEGTEKINT) ||
        _jog(JOG_PRKEZEL_MEGTEKINT) || _jog(JOG_PRKEZEL_SAJAT_MEGTEKINT) || _jog(JOG_PRKEZEL_TEAM_TAG) || _jog(JOG_PR_EGYSZALL_MEGTEKINT) || _jog(JOG_PR_TOBBSZALL_MEGTEKINT) ||
        _jog(JOG_PRKEZEL_SZERZODES_OLVAS) || _jog(JOG_PR_SAJAT_SZERZ_MEGTEKINT) ||
        _jog(JOG_PRSZAMLAK_OLVAS) || _jog(JOG_PR_SAJAT_SZAMLA_MEGTEKINT);
    
}

/**
*	Insert & Update
*
*	@param string $type: 'insert' vagy 'update'
*	@param string $tabla: a beszúrandó tábla neve 'elotag_' nélkül
*	@param array $tomb: beszúrandó étékek. Vigyázat, egyeznie kell a megfelelő szerkezettel:
*	name: $POST[name] (tehát érték) és az adatbázis tábla neve
*	alert:  ha nem üres akkor visszaadja a sorsámot, hogy hol akadt el
*
*	@return array: első része a $errnum második pedig a beszúrt vagy módosított sor id-je
*/
function i_u($type, $tabla, $tomb) {

	global $adatbazis_kapcsolat;
	global $db_kapcs;
	global $weblap;

    // Archiv táblába biztosan nem írunk
    if( substr($tabla, strlen($tabla)-5) == '_arch' ){
        return false;
    }

    // Ha már valaki rámentett az adatlapomra, akkor hibaüzenet
    if( $type == 'update' ) {
        $update_dt = $adatbazis_kapcsolat->s2('update_dt', $tabla, "`recid`='{$_GET['id']}'");
        if( $update_dt['update_dt'] > $_POST['update_dt'] ){
            echo json_encode(array(
                'hiba' => 'A rekordot egy másik felhasználó időközben módosította.<br>A változásokat nem lehet menteni, próbálja újra!',
                'gomb' => '<input type="button" value="Ok" class="gomb zold" onclick="szurkit(); formModositva=false; location.reload();">'
            ));

            $weblap -> leallit();
            exit();
        }

        // Fenntartási terv és energia terv is csak akkor szerkeszthető, ha munkaközi
        if( $tabla == 'fenntartasi_terv' && $tabla == 'energia_terv' ){
            $terv = $adatbazis_kapcsolat->s2('stat_id', 'fenntartasi_terv', "`recid`='{$_GET['id']}'");
            if( $terv === false ) { $db_error = true; }

            if($terv['stat_id']!=ENERGIATERV_STAT_MUNKAKOZI){
                exit;
            }
        }
    }

    // Tranzakció indul
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $nevid   = '';

    $oszlopok = array();
    $ertekek  = array();

    // Bejelentés és munkalap esetén az obj_id mellé a címkét is letároljuk
    if( $tabla == 'bejelentes' || $tabla == 'munkalap' ) {
        $obj_txt = getErtek('nev', 'obj', 'recid', $_POST['obj_id']);
        array_push($oszlopok, 'obj_txt');
        array_push($ertekek,  $obj_txt);
    }
    // Munkalapnál meg a gép nevét
    if( $tabla == 'munkalap' ) {
        $gep_txt = getErtek('nev', 'eszkozkezeles', 'recid', $_POST['gep_id']);
        array_push($oszlopok, 'gep_txt');
        array_push($ertekek,  $gep_txt);
    }
    
    // Projektszámláknál a "Pénzügynek átadva" dátum fixen disabled, de mégis mentsük el, azaz itt vegyük fel
    // Sőt ekkor generálunk XML-t a számlából
    $projektszamla_xml_gen = false;
    if( $tabla == 'projektszamla' ) {
        // De csak akkor mentjük el, ha még nincs értéke
        $prsz = $adatbazis_kapcsolat->s2('p_atad_dt', 'projektszamla', "`recid`='{$_GET['id']}'");
        if( !$prsz['p_atad_dt'] || $prsz['p_atad_dt']=='0000-00-00' ) {
            $projektszamla_xml_gen = true;
        } else {
            $_POST['p_atad_dt'] = $prsz['p_atad_dt'];
        }
        
        // Szerkesztéskor a számla kifizetve pipájának lekezelése (Új feltöltéskor még nincsenek kifizetés sorok)
        if( $type == 'update' ) {

            if( $_POST['kcs_21'] == KCS_21_HUF ) {
                $kif_mezo   = 'br_ertek';
                $br_mezo    = 'brutto';
            } else {
                $kif_mezo = 'dev_br';
                $br_mezo  = 'dev_br';
            }
            $kifizetesek = $adatbazis_kapcsolat->s2('SUM(`'.$kif_mezo.'`)', 'projektszamla_kifizetesek', "`projektszamla_recid`='{$_GET['id']}'");
            if( $kifizetesek['SUM(`'.$kif_mezo.'`)']>=$_POST[$br_mezo] ) {
                $_POST['kifizetve'] = 1;
            } else {
                $_POST['kifizetve'] = 0;
            }

        }
        
    }
    // Projektszerződésnél ha nincs pénznem információnk, akkor a devizás mezőket nullázzuk a biztosnág kedvéért
    if( $tabla == 'projektszerzodes' ) {

        if( !$_POST['kcs_21'] ) {
            $_POST['arfolyam']      = '';
            $_POST['atvaltva_dt']   = '';
            $_POST['dev_net']       = '';
        }
        
        // Illetve ha az építési összege meghaladná a szerződött összeget, azt ne engedjük elmenteni
        if( str_replace(' ', '', $_POST['epitesi']) > str_replace(' ', '', $_POST['szerz_ossz']) ){
            exit;
        }
    }
    
    // Ütemezett feladatok, rendszeres karbantartás és eseti fenntartásoknál ha nem volna ismert az Elvégzési idő, akkor alapértelmezett értéket kap
    $elv_ido = array('utemezett_feladatok', 'rendszeres_karbantartas', 'eseti_fenntartas');
    if( in_array($tabla, $elv_ido) ) {

        if( !$_POST['elv_ido_tol'] || strlen($_POST['elv_ido_tol'])>5 /* Ekkor üres. Az üres szóközöket nem tudjuk másképp felismerni */ ) {
            $_POST['elv_ido_tol'] = '08:00';
        }

        if( !$_POST['elv_ido_ig'] || strlen($_POST['elv_ido_ig'])>5 /* Ekkor üres. Az üres szóközöket nem tudjuk másképp felismerni */ ) {
            $_POST['elv_ido_ig'] = '17:00';
        }

    }

    // Értékek párosítása
	$i = 0;
	foreach ( $tomb as $ertek ) {
        if( $ertek['type'] != 'nosql' && !$ertek['nosql'] ) {

    		// Ha alert-es egy mező, és a post-ja üres, akkor hiba! (nem adott meg egy kötelező mezőt)
            if( $ertek['alert'] != '' && $_POST[$ertek['name']] == '' && !$ertek['disabled'] ) {
                return array($i, '');
    			break;
    		}

            // Ha az adott esemenyek.php tomb sora tartalmaz nevid jelzést, akkor ezt a sort eltároljuk, mert ennek a nevéből gyártuk névid-t
            if( $ertek['nevid'] == true ) {
                $nevid = $ertek;
    		}

    		// Wysiwyg útvonalszépítés a feltöltött képekre, HA van egyáltalán fájlfeltöltés
    		if($ertek['wysiwyg'] && $ertek['feltoltes']) {
                $_POST[$ertek['name']] = str_replace('wysiwyg/ckfinder/core/connector/php/../../../../../', '', $_POST[$ertek['name']]);
            }

            // Ha felhasználó adatlapját módosítjuk, és van megadva jelszó, akkor lecseréljük a mostanit
            if($tabla=='felh' && $ertek['name']=='reg_jelsz' && $_POST[$ertek['name']]) {
                $_POST[$ertek['name']] = sha1($_POST[$ertek['name']]); //sha1 helyett más elkódolást is használhatnánk, de ez javasolt. A lényeg, hogy legyen összhangban a class.Felhasznalo konfigjával
            }

            // Ha van datepicker class-ja, akkor dátum, és a pontokat kötőjelre cseréljük
            if( strpos($ertek['class'], 'datepicker') !== false ) {
                $_POST[$ertek['name']] = str_replace('.', '-', $_POST[$ertek['name']]);
            }

            // Szám típusból ki kell szűrni a szóközt (ezresítés), és a vesszőt pontra cserélni
            if( strpos($ertek['class'], 'f_num') !== false ) {
                $_POST[$ertek['name']] = str_replace(array(' ', ','), array('', '.'), $_POST[$ertek['name']]);
            }

    		// Az oszlop/érték párokat felvesszük egy tömbbe, ha:
    		if (
                $ertek['type'] != 'submit' && $ertek['type'] != 'button' && $ertek['type'] != 'file' && $ertek['type'] != 'flashfile' && $ertek['type'] != 'jsfile' && // ... ha megfelelő fajtájú input
                !(
                    $ertek['disabled'] &&
                    $ertek['name']!='szallito_id'   && 
                    !$ertek['sql_disabledkor_is']   &&  // Tiltott mezőt mégis eltároljuk, ha van "sql_disabledkor_is" tulajdonsága. Ez arra az esetre kell, ha egy mező betöltéskor Disabled, de az adatlap szerkesztések közben JS engedélyez egy tiltott mezőt.  Ilyenkor mentsük is el az értékét.
                    $type == 'update'                   // Tiltott mezőket beszúrni lehet csak moddolni nem
                )                                   && 
                $ertek['name']                      && // oszlopnév sem üres és maga a POST egyáltalán létezik is (ne nullázzunk felül el nem küldött POST miatt)
                isset($_POST[$ertek['name']])
            ) {
                array_push($oszlopok, $ertek['name']);
    			array_push($ertekek, trim($_POST[$ertek['name']]));
    		}

    		$i++;
        }
	}

    // Dátumra ütemezés, ha az ütemezett feladatokat módosítjuk/töltjük fel
    if( $tabla == 'utemezett_feladatok' || $tabla == 'rendszeres_karbantartas' ) {

        if( $_POST['cikltip'] == 'l' ) {
            array_push($oszlopok, 'ciklaltip'       );
            array_push($ertekek,  ''                );
            array_push($oszlopok, 'ciklszorz'       );
            array_push($ertekek,  ''                );
        }
        // Napi cilus, X naponként ismétlődik
        if( $_POST['ciklnap'] && $_POST['cikltip'] != 'm' && $_POST['cikltip'] != 'w' ) {
            array_push($oszlopok, 'ciklaltip'       );
            array_push($ertekek,  '1'               );

            array_push($oszlopok, 'ciklszorz'       );
            array_push($ertekek,  $_POST['ciklnap'] );
        }

        // Napi ciklus, hétköznaponként ismétlődik
        $hetkoz_kent = '';
        if( $type == 'insert' ) {
            if($_POST['hetkoz_kent']){
                $hetkoz_kent = '1';
            }
        } else {
            if($_POST['hetkoz_kent']=='true'){
                $hetkoz_kent = '1';
            }
        }
        if($hetkoz_kent){
            array_push($oszlopok, 'ciklaltip'   );
            array_push($ertekek,  '2'           );

            array_push($oszlopok, 'ciklszorz'   );
            array_push($ertekek,  '0'           );
        }

        // Havi ciklus x havonta ismétlődik
        if( $_POST['ciklho'] ) {
            array_push($oszlopok, 'ciklaltip'   );
            array_push($ertekek,  '1'           );

            array_push($oszlopok, 'ciklszorz'      );
            array_push($ertekek,  $_POST['ciklho'] );

            $index = array_search('ciklnap', $oszlopok);
            $ertekek[$index] = $_POST['honapjan'];

        }

        if( $_POST['ciklho2'] ) {
            array_push($oszlopok, 'ciklaltip'       );
            array_push($ertekek,  '2'               );

            array_push($oszlopok, 'ciklszorz'       );
            array_push($ertekek,  $_POST['ciklho2'] );

            $index = array_search('ciklnap', $oszlopok);
            $ertekek[$index] = $_POST['ho_rendsz_het'];
        }

        if( $_POST['ism_het'] ) {
            array_push($oszlopok, 'ciklszorz'       );
            array_push($ertekek,  $_POST['ism_het'] );
            $cikl_nap = '';
            if( $type == 'insert' ) {
                if($_POST['h']) { $cikl_nap .= '1'; }
                if($_POST['k']) { $cikl_nap .= '2'; }
                if($_POST['s']) { $cikl_nap .= '3'; }
                if($_POST['cs']){ $cikl_nap .= '4'; }
                if($_POST['p']) { $cikl_nap .= '5'; }
                if($_POST['sz']){ $cikl_nap .= '6'; }
                if($_POST['v']) { $cikl_nap .= '7'; }
            } else {
                if($_POST['h']=='true') { $cikl_nap .= '1'; }
                if($_POST['k']=='true') { $cikl_nap .= '2'; }
                if($_POST['s']=='true') { $cikl_nap .= '3'; }
                if($_POST['cs']=='true'){ $cikl_nap .= '4'; }
                if($_POST['p']=='true') { $cikl_nap .= '5'; }
                if($_POST['sz']=='true'){ $cikl_nap .= '6'; }
                if($_POST['v']=='true') { $cikl_nap .= '7'; }
            }

            $index = array_search('ciklnap', $oszlopok);
            $ertekek[$index] = $cikl_nap;

            array_push($oszlopok, 'ciklaltip'   );
            array_push($ertekek,  ''            );
        }

        // Úgy általánosságban még hozzáadjuk a kezdho, kezdnap, vegeho, vegenap értékeket
        array_push($oszlopok, 'kezdho',        'kezdnap',           'vegeho',           'vegenap',          'aktkezd_d',            'aktvege_d'           );
        array_push($ertekek, $_POST['kezdho'], $_POST['kezdnap'],   $_POST['vegeho'],   $_POST['vegenap'],  $_POST['aktkezd_d'],    $_POST['aktvege_d']   );

    }
    
//echo '<pre style="font-size: 1.4rem">';
//print_r($oszlopok);
//echo '</pre>';
//echo '<pre style="font-size: 1.4rem">';
//print_r($ertekek);
//echo '</pre>';
//exit;

    // Új bejegyzés esetén több mindent is le kell kezelni (iktatószám kiszámolás, plusz oszlopok beállítása
	if( $type == 'insert' ) {
        
        // Insert DT és USR lekezelése általánosan
        if( in_array($tabla.'.php', $_SESSION['ketHasabos']) ) {
            array_push($oszlopok, 'insert_dt',  'insert_usr'                          );
            array_push($ertekek,  'NOW()',      $_SESSION[get_db_tabla().'teljes_nev']);
        }

        // Anyagigénylés új bejegyzés esetén
        if( $tabla == 'anyagigenyles' ) {

            // Iktatószám kiszámítása
            $sorszam  = get_iktatoszam_sorszam($tabla);
            if($sorszam===false){ $db_error = true; }
            $ikt_szam = date('y').'/'.sprintf('%04d', $sorszam);

            // Miután a $_POST['nev']-et felvettük cikkid-nak, csak utána írjuk felül
            $cikktorzs = $adatbazis_kapcsolat->s2('id nev cikkmee oracikkid', 'cikktorzs', "`recid`='{$_POST['nev']}'");
            if( $cikktorzs === false ) { $db_error = true; }

            // Új anyagigényléskor a munkalaphoz tartozó anyagok státuszán állítani kell
            $sql = $adatbazis_kapcsolat->u('munkalap_anyag', array('stat_id'), array(MUNKALAP_STAT_HOZZARENDELT), "`telephely_id`='{$_POST['telephely_id']}' AND `cikk_id`='{$cikktorzs['id']}'");
            if( $sql === false ) { $db_error = true; }

            $i = 0;
            foreach ( $oszlopok as $oszl ) {
                if( $oszl == 'nev' ) {
                    $ertekek[$i] = htmlspecialchars_decode($cikktorzs['nev']);
                }
                $i++;
            }

            // Ha be volt pipálva, hogy külsősnek, akkor oracikkid-t felülírjuk, és kulsősnek pipát beállítjuk
            if( $_POST['kulsosnek'] ) {
                $cikktorzs['oracikkid'] = 'MÜRBA0002';
            }

            // CikkID-t
            array_push($oszlopok,  'cikk_id'        );
            array_push($ertekek,   $cikktorzs['id'] );

            // Beállítjuk az iktatószámot
            array_push($oszlopok,  'ikt_szam');
            array_push($ertekek,   $ikt_szam );

            // Mennyiségi egységet pedig az anyag mennyiségi egységéből
            array_push($oszlopok,  'oracikkid');
            array_push($ertekek,   $cikktorzs['oracikkid'] );

            array_push($oszlopok,  'cikkmee');
            array_push($ertekek,   $cikktorzs['cikkmee'] );

            array_push($oszlopok, 'loginnev'                        );
            array_push($ertekek,   $_SESSION[get_db_tabla().'user'] );

        // Bejelentések  új bejegyzés esetén
        } else if( $tabla == 'bejelentes' ) {

            // Iktatószám kiszámítása
            $sorszam  = get_iktatoszam_sorszam($tabla);
            if($sorszam===false){ $db_error = true; }
            $ikt_szam = date('y').'/'.sprintf('%07d', $sorszam);

            // Felvesszük az értékek közé
            $i = 0;
            foreach ( $oszlopok as $oszl ) {
                if( $oszl == 'ikt_szam' ) {
                    $ertekek[$i] = $ikt_szam;
                    break;
                }
                $i++;
            }

            array_push($oszlopok,  'stat_id'                );
            array_push($ertekek,    BEJELENTES_STAT_NYITOTT );

            if( $_POST['masnevebenFeltolt'] ) {
                array_push($oszlopok, 'loginnev'                   );
                array_push($ertekek,   $_POST['masnevebenFeltolt'] );
            } else {
                array_push($oszlopok, 'loginnev'                        );
                array_push($ertekek,   $_SESSION[get_db_tabla().'user'] );
            }

        // FB Előterjesztés új bejegyzés esetén
        } else if( $tabla == 'eloterj_fb' ) {

            // Iktatószám kiszámítása
            $sorszam  = get_iktatoszam_sorszam($tabla);
            if($sorszam===false){ $db_error = true; }
            $ikt_szam = 'ELFB'.date('y').'/'.sprintf('%02d', $sorszam);

            // Felvesszük az értékek közé
            $i = 0;
            foreach ( $oszlopok as $oszl ) {
                if( $oszl == 'ikt_szam' ) {
                    $ertekek[$i] = $ikt_szam;
                    break;
                }
                $i++;
            }

        // BOD Előterjesztés új bejegyzés esetén
        } else if( $tabla == 'eloterj_bod' ) {

            // Iktatószám kiszámítása
            $sorszam  = get_iktatoszam_sorszam($tabla);
            if($sorszam===false){ $db_error = true; }
            $ikt_szam = 'ELBO'.date('y').'/'.sprintf('%02d', $sorszam);

            // Felvesszük az értékek közé
            $i = 0;
            foreach ( $oszlopok as $oszl ) {
                if( $oszl == 'ikt_szam' ) {
                    $ertekek[$i] = $ikt_szam;
                    break;
                }
                $i++;
            }

        // Értéklista új bejegyzés esetén
        } else if( $tabla == 'erteklista' ) {
            $ertek = $adatbazis_kapcsolat->s2('id', 'erteklista', "`csoport` = '".$_POST['csoport']."'", 'id desc');

            if( $ertek ) {
                $ertek_id = $ertek['id'] + 1;
            } else {
                $ertek_id = 1;
            }

            array_push($oszlopok,  'id'       );
            array_push($ertekek,   $ertek_id  );


        // Ütemezett feladatok új bejegyzés esetén
        } else if( $tabla == 'eseti_fenntartas' ) {

            // Szálloda azonosító
            $szalloda   = $adatbazis_kapcsolat->s('azon', 'telephely', "`id`='{$_POST['telephely_id']}'");
            $szalloda   = $szalloda[0]['azon'];

            // Iktatószám kiszámítása
            $sorszam  = get_iktatoszam_sorszam($tabla);
            if($sorszam===false){ $db_error = true; }
            $id = $szalloda.sprintf('%04d', $sorszam);

            // Felvesszük az értékek közé
            $i = 0;
            foreach ( $oszlopok as $oszl ) {
                if( $oszl == 'id' ) {
                    $ertekek[$i] = $id;
                    break;
                }
                $i++;
            }

            array_push($oszlopok, 'loginnev'                        );
            array_push($ertekek,   $_SESSION[get_db_tabla().'user'] );

        // Fenntartási terv
        } else if( $tabla == 'fenntartasi_terv' ) {
            array_push($oszlopok,  'stat_id'                    );
            array_push($ertekek,   ENERGIATERV_STAT_MUNKAKOZI   );

        // Gégpek új bejegyzés esetén
        } else if( $tabla == 'gep' ) {
            array_push($oszlopok, 'loginnev'                        );
            array_push($ertekek,   $_SESSION[get_db_tabla().'user'] );

        // Költséghely új bejegyzés esetén
        } else if( $tabla == 'ktghely' ) {
            array_push($oszlopok, 'loginnev'                        );
            array_push($ertekek,   $_SESSION[get_db_tabla().'user'] );

        // Költségnem új bejegyzés esetén
        } else if( $tabla == 'ktgnem' ) {
            array_push($oszlopok, 'loginnev'                        );
            array_push($ertekek,   $_SESSION[get_db_tabla().'user'] );

        // Login új bejegyzés esetén
        } elseif( $tabla == 'login' ) {

            // Loginnév hossza nem lehet rövidebb mint 8 karakter
            if( mb_strlen( $_POST['nev'], 'UTF-8' )<8 ){
                header('Location:login.php?uj&rovid_nev');
                $weblap -> leallit();
                exit;
            }

            // Ha illegális karaktert használt
            if( preg_match('/[^a-zA-Z0-9._@éáűőúöüóíÉÁŰŐÚÖÜÓÍ]+/', $_POST['nev']) ){
                header('Location:login.php?uj&hibasnev');
                $weblap -> leallit();
                exit;
            }

            // Nem lehet ugyanazt a loginnevet kétszer felhasználni
            if($adatbazis_kapcsolat->s('recid', 'login', "`nev`='{$_POST['nev']}'")){
                header('Location:login.php?uj&foglalt_nev');
                $weblap -> leallit();
                exit;
            }

            // Adott szerepkor jogosultságai lesz adott felhasználó jogosultsága is
            if( $_POST['szemelyzet_id'] ) {
                $szerepkor = $adatbazis_kapcsolat->s2('flags', 'szerepkor', "`recid`='{$_POST['szemelyzet_id']}'", '', '1');
                if( $szerepkor === false ) { $db_error = true; }
                if( $szerepkor['flags'] ) {
                    array_push($oszlopok, 'flags'             );
                    array_push($ertekek,   $szerepkor['flags']);
                }
            }

        // Munkalap új bejegyzés esetén
        } else if( $tabla == 'munkalap' ) {

            // Iktatószám kiszámítása
            $sorszam  = get_iktatoszam_sorszam($tabla);
            if($sorszam===false){ $db_error = true; }
            $ikt_szam = 'M'.date('y').'/'.sprintf('%07d', $sorszam);

            // Felvesszük az értékek közé
            $i = 0;
            foreach($oszlopok as $oszl){
                if($oszl=='ikt_szam'){
                    $ertekek[$i] = $ikt_szam;
                    break;
                }
                $i++;
            }

            // Egy nyitott státusz, a loginnevet és a tipusát backupként, hogy tudjuk, mi volt eredetileg (a sima tipus_id sem változhat, de az a tuti, ha duplán benne van, mert készre jelentéskor nem mindegy, milyen tipusú volt eredetileg, melyik táblát kell majd updatelni)
            array_push($oszlopok,  'stat_id',              'tipus_id_back',     'loginnev'                      );
            array_push($ertekek,    MUNKALAP_STAT_NYITOTT, $_POST['tipus_id'],  $_SESSION[get_db_tabla().'user']);

        // Ütemezett feladatok új bejegyzés esetén
        } else if( $tabla == 'utemezett_feladatok' ) {

            // Szálloda azonosító
            $szalloda   = $adatbazis_kapcsolat->s('azon', 'telephely', "`id`='{$_POST['telephely_id']}'");
            $szalloda   = $szalloda[0]['azon'];

            // Iktatószám kiszámítása
            $sorszam  = get_iktatoszam_sorszam($tabla);
            if($sorszam===false){ $db_error = true; }
            $id = $szalloda.sprintf('%04d', $sorszam);

            // Felvesszük az értékek közé
            $i = 0;
            foreach ( $oszlopok as $oszl ) {
                if( $oszl == 'id' ) {
                    $ertekek[$i] = $id;
                    break;
                }
                $i++;
            }

            array_push($oszlopok, 'loginnev'                        );
            array_push($ertekek,   $_SESSION[get_db_tabla().'user'] );


        // Projektigénylés új bejegyzés esetén
        } else if( $tabla == 'projektigenyles' ) {

            array_push($oszlopok, 'loginnev'                        );
            array_push($ertekek,   $_SESSION[get_db_tabla().'user'] );
            
            // Ha nincs új feltöltés jogunk, akkor kilépünk
            if( !_jog(JOG_PRIGENY_LETREHOZ) ){
                exit;
            }

        // Projektkezelés
        } else if( $tabla == 'projektkezeles' ) {
               
            // Iktatószám kiszámítása
            $sorszam  = get_iktatoszam_sorszam($tabla);
            if($sorszam===false){ $db_error = true; }
            $ikt_szam = 'P'.date('y').'/'.sprintf('%03d', $sorszam);
            $_POST['ikt_szam'] = $ikt_szam; // Erre a levélküldésnél lesz szükség
            
            // Felvesszük az értékek közé
            $i = 0;
            foreach ( $oszlopok as $oszl ) {
                if( $oszl == 'ikt_szam' ) {
                    $ertekek[$i] = $ikt_szam;
                    break;
                }
                $i++;
            }

            array_push($oszlopok, 'loginnev'                        );
            array_push($ertekek,  $_SESSION[get_db_tabla().'user']  );
  
            // Ha nincs új feltöltés jogunk, akkor kilépünk
            if( !_jog(JOG_PRKEZEL_UJ_LETREHOZ) ){
                exit;
            }

        // Projektszámlák
        } else if( $tabla == 'projektszamla' ) {
            
            // Ha nics egyetlen tétel sem, akkor automatikusan létrehozunk egyet, aminek a tárgya ugyanaz mint szerződés adatlapján a tárgya nettó összeg pedig ugyanaz, mint a számla nettó értéke
            if( !$_SESSION['pr_szla_tetel_targy'] ) {
                $_SESSION['pr_szla_tetel_targy']        = array($_POST['targy']     );
                $_SESSION['pr_szla_tetel_netto_ert']    = array($_POST['netto']     );
                $_SESSION['pr_szla_tetel_dev_net']      = array($_POST['dev_net']   );
                $_SESSION['pr_szla_tetel_pr_tetel']     = array(0                   ); // A szerződés tétel ID-ja fixen 0, hisz nincs összekötve szerződéssel a számla.
            }
            
            // Nullás számla esetén a nullás tételt hozzuk létre automatikusan
            if( $_POST['nullas'] ) {
                $_SESSION['pr_szla_tetel_targy']        = array(NULLAS_TETEL);
                $_SESSION['pr_szla_tetel_netto_ert']    = array(0           );
                $_SESSION['pr_szla_tetel_dev_net']      = array(0.00        );
                $_SESSION['pr_szla_tetel_pr_tetel']     = array(0           ); // A szerződés tétel ID-ja fixen 0, hisz nincs összekötve szerződéssel a számla.
            }
            
            // Új számla rögzítésekor a mellszam mező értéke ugyanaz lesz mint az ikt_szam mezőé.
            array_push($oszlopok, 'loginnev',                       'mell_szam'         );
            array_push($ertekek,  $_SESSION[get_db_tabla().'user'], $_POST['ikt_szam']  );
            
        // Projektszerződés
        } else if( $tabla == 'projektszerzodes' ) {
            
            // Ha nincs egyetlen tétel sem, akkor automatikusan létrehozunk egyet, aminek a tárgya ugyanaz mint szerződés adatlapján a tárgy,
            // a várható érkezés ugyanaz, mint a szerződésen a kivitelezés vége, a nettó összeg pedig ugyanaz, mint a szerződés nettó értéke.
            // Ha dedvizás a szerződés, akkor viszont a tételnél is a dev_net oszlopot töltjük ki
            if( !$_SESSION['pr_szer_tetel_targy'] ) {
                $_SESSION['pr_szer_tetel_targy']    = array($_POST['targy']     );
                $_SESSION['pr_szer_tetel_varh_erk'] = array($_POST['kiv_vege']  );
                $_SESSION['pr_szer_tetel_varh_ert'] = array($_POST['szerz_ossz']);
                $_SESSION['pr_szer_tetel_dev_net']  = array($_POST['dev_net']   );
            }
            
            // Iktatószám kiszámítása
            // Szálloda azonosító
            $szalloda   = $adatbazis_kapcsolat->s2('props', 'telephely', "`id`='{$_POST['telephely_id']}'");
            if( $szalloda === false ) { $db_error = true; }
            $sorszam  = get_iktatoszam_sorszam($tabla, $_POST['telephely_id']);
            if($sorszam===false){ $db_error = true; }
            $ikt_szam = 'PSZ-'.date('y').'/'.get_props($szalloda['props'], 'RN').'-'.sprintf('%03d', $sorszam);

            // Felvesszük az értékek közé
            $i = 0;
            foreach ( $oszlopok as $oszl ) {
                if( $oszl == 'sz_szam' ) {
                    $ertekek[$i] = $ikt_szam;
                    break;
                }
                $i++;
            }
            
            // Illetve a projekt mezőit is frissítjük, ha van már hozzáadva Projekt szám
            if( $_POST['pr_szam'] ) {
                $szerzodesek = $adatbazis_kapcsolat->s2('SUM(`szerz_ossz`)', 'projektszerzodes', "`pr_szam`='{$_POST['pr_szam']}'");
                if( $szerzodesek === false ) { $db_error = true; }
                $projekt = $adatbazis_kapcsolat->s2('jkolt', 'projektkezeles', "`ikt_szam`='{$_POST['pr_szam']}'");
                if( $projekt === false ) { $db_error = true; }
                
                $sz_ossz = $szerzodesek['SUM(`szerz_ossz`)'] + $_POST['szerz_ossz'];
                $fel_hato = $projekt['jkolt']-$sz_ossz;
                $sql = $adatbazis_kapcsolat->u('projektkezeles', array('sz_ossz', 'fel_hato'), array($sz_ossz, $fel_hato), "`ikt_szam`='{$_POST['pr_szam']}'");
                if( $sql === false ) { $db_error = true; }
            }
            
            array_push($oszlopok, 'loginnev'                        );
            array_push($ertekek,  $_SESSION[get_db_tabla().'user']  );

        // Rendszeres karbantarás új bejegyzés esetén
        } else if( $tabla == 'rendszeres_karbantartas' ) {

            // Szálloda azonosító
            $szalloda   = $adatbazis_kapcsolat->s('azon', 'telephely', "`id`='{$_POST['telephely_id']}'");
            $szalloda   = $szalloda[0]['azon'];
            // Iktatószám kiszámítása
            $sorszam  = get_iktatoszam_sorszam($tabla);
            if($sorszam===false){ $db_error = true; }
            $id = $szalloda.sprintf('%04d', $sorszam);

            // Felvesszük az értékek közé
            $i = 0;
            foreach ( $oszlopok as $oszl ) {
                if( $oszl == 'id' ) {
                    $ertekek[$i] = $id;
                    break;
                }
                $i++;
            }

            array_push($oszlopok, 'loginnev'                        );
            array_push($ertekek,   $_SESSION[get_db_tabla().'user'] );

        // Szállítók esetén ellenőrizni, hogy az ID nem foglalt-e
        } else if( $tabla == 'rendszeres_karbantartas' ) {
            $id_foglalt = $adatbazis_kapcsolat->s('id', $tabla, "`id`='{$_POST['id']}'");
            if( $id_foglalt === false ) { $db_error = true; }
            
            if($id_foglalt){
                exit;
            }
            
        }

// Insert lefutása
		$id = $adatbazis_kapcsolat -> insert($tabla, $oszlopok, $ertekek);
        if( $id === false ) { $db_error = true; }
        
//echo '<pre style="font-size: 1.4rem">';
//echo $adatbazis_kapcsolat->gp() . '<br>';
//echo '</pre>';
//exit;
//mysqli_commit($db_kapcs);
//exit;

        // Új bejegyzés létrehozása utáni teendők
        // Itt újabb if felételek jönnek a táblákra. Azért kerültek ide, mert itt már felhasználjuk a létrejött bejegyzés ID-ját (ezért nem fentebb kezeltük le)

        // Anyagigényleés státusz beszúrása
        if( $tabla == 'anyagigenyles' ) {
            $sql = $adatbazis_kapcsolat -> i($tabla.'_statusz',
                                                array($tabla.'_recid', 'stat_id',               'login',                            'insert_dt',    'insert_usr',                             'update_usr'                           ),
                                                array($id,             CIKKIGN_STAT_NYITOTT,    $_SESSION[get_db_tabla().'user'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'] )
                                            );
            if( $sql === false ) { $db_error = true; }
            $sql = $adatbazis_kapcsolat->u($tabla, array('stat_id'), array(CIKKIGN_STAT_NYITOTT), "`recid`='$id'");
            if( $sql === false ) { $db_error = true; }
        }

        // Bejelentések
        if( $tabla == 'bejelentes' ) {

            // Kezdőstátusz beállítása
            $sql = $adatbazis_kapcsolat -> i($tabla.'_statusz',
                                            array($tabla.'_recid',   'stat_id',               'login',                          'insert_dt', 'insert_usr',                              'update_usr'                          ),
                                            array($id,               BEJELENTES_STAT_NYITOTT, $_SESSION[get_db_tabla().'user'], 'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'] )
                                        );
            if( $sql === false ) { $db_error = true; }
            $sql = $adatbazis_kapcsolat->u($tabla, array('stat_id'), array(BEJELENTES_STAT_NYITOTT), "`recid`='{$id}'");
            if( $sql === false ) { $db_error = true; }

            // Megvizsgáljuk, hogy egy/több szállodás modell
            $telephely = $adatbazis_kapcsolat->s2('modell', 'telephely', "`id` = '".$_POST['telephely_id']."'", '', '1');
            if( $telephely ) {

                // Egyszállodás modell esetén, vagy ha "telefonon bejelentve" pipa be van pipálva rögtön munkalapot generálunk belőle
                if( !$telephely['modell'] || $_POST['telefon'] ) {


                    $recid = munkalap_letrehoz(MUNKALAP_STAT_NYITOTT, $_POST['telephely_id'], MLTIPUS_HIBAELHARITAS, $_POST['obj_id'], $_POST['gep_id'], $_POST['surgos'], '', TELEPITETT_SZEMELYZET, '', '', '', $_POST['hiba_id']);
                    if( $recid === false ) { $db_error = true; }

                    if( addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_HOZZARENDELT ) === false ) { $db_error = true; }

                    $sql = $adatbazis_kapcsolat -> i('munkalap_kapcs',
                                                        array('munkalap_id', 'bejelentes_id',  'update_usr',                           'insert_dt',  'insert_usr',                            ),
                                                        array($recid,        $id,              $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',       $_SESSION[get_db_tabla().'teljes_nev']  )
                                                    );
                    if( $sql === false ) { $db_error = true; }

                    // Dokumentumot is átmásoljuk, de nem itt, mert itt még nincs hozzárendelve, hanem az esemenyek.php-ban (keress rá erre: bejelentes_dok_munkalapba_masol )

                }

                // Ha "telefonon bejelentve" pipa be van pipálva, akkor a munkalap generáláson felül a bejelentést folyamatban státuszra léptetjük
                if( $_POST['telefon'] ) {

                    // Bejelentést is folyamatbanra állítjuk
                    if( addStatusz( 'bejelentes_statusz', $id, BEJELENTES_STAT_FOLYAMATBAN ) === false ) {
                        $db_error = true;
                    }

                }
            }

        }

        // Energia terv státusz beszúrása
        if( $tabla == 'energia_terv' ) {
            $sql = $adatbazis_kapcsolat -> i($tabla.'_statusz',
                                                array($tabla.'_recid', 'stat_id',                  'login',                          'insert_dt',    'insert_usr',                             'update_usr'                           ),
                                                array($id,             ENERGIATERV_STAT_MUNKAKOZI, $_SESSION[get_db_tabla().'user'], 'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'] )
                                            );
            if( $sql === false ) { $db_error = true; }
            $sql = $adatbazis_kapcsolat->u($tabla, array('stat_id'), array(ENERGIATERV_STAT_MUNKAKOZI), "`recid`='$id'");
            if( $sql === false ) { $db_error = true; }
        }

        // Előterjesztés (FB) státusz beszúrása
        if( $tabla == 'eloterj_fb' ) {
            $sql = $adatbazis_kapcsolat -> i($tabla.'_statusz',
                                                array($tabla.'_recid', 'stat_id',            'login',                           'insert_dt',    'insert_usr',                             'update_usr'                           ),
                                                array($id,             ELOT_FB_STAT_NYITOTT, $_SESSION[get_db_tabla().'user'],  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'] )
                                            );
            if( $sql === false ) { $db_error = true; }
            $sql = $adatbazis_kapcsolat->u($tabla, array('stat_id'), array(ELOT_FB_STAT_NYITOTT), "`recid`='$id'");
            if( $sql === false ) { $db_error = true; }
        }

        // Előterjesztés (BoD) státusz beszúrása
        if( $tabla == 'eloterj_bod' ) {
            $sql = $adatbazis_kapcsolat -> i($tabla.'_statusz',
                                                array($tabla.'_recid', 'stat_id',             'login',                          'insert_dt',    'insert_usr',                             'update_usr'                           ),
                                                array($id,             ELOT_BOD_STAT_NYITOTT, $_SESSION[get_db_tabla().'user'], 'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'] )
                                            );
            if( $sql === false ) { $db_error = true; }
            $sql = $adatbazis_kapcsolat->u($tabla, array('stat_id'), array(ELOT_BOD_STAT_NYITOTT), "`recid`='$id'");
            if( $sql === false ) { $db_error = true; }
        }

        // Szállodákat és társrészlegeket is beállítjuk
        if( $tabla == 'login' ) {
            // Szállodák feldolgozása
            $adatok = explode(',', str_replace(array('"', '{', '}'), '', $_POST['szallodak_hidden']));
            foreach($adatok as $adat){
                list($x, $telephely_id) = explode(':', $adat);
                $sql = $adatbazis_kapcsolat->i('telephely_kapcs',
                                                            array('nev',        'telephely_id', 'insert_dt',    'insert_usr',                             'update_usr'                           ),
                                                            array($_POST['nev'], $telephely_id, 'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'] )
                                                );
                if( $sql === false ) { $db_error = true; }
            }
            
            // Társrészlegek feldolgozása
            $adatok = explode(',', str_replace(array('"', '{', '}'), '', $_POST['tarsreszlegek_hidden']));
            foreach($adatok as $adat){
                list($x, $reszleg_id) = explode(':', $adat);
                // Részlegid-k felvétele, ha nem az adatlap részlege
                if($reszleg_id != $_POST['reszleg_id']){
                    $sql = $adatbazis_kapcsolat->i('login_tarsreszleg',
                                                                array('login_id',   'reszleg_id', 'insert_dt',    'insert_usr',                             ),
                                                                array($id,          $reszleg_id,  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']    )
                                                    );
                }
                if( $sql === false ) { $db_error = true; }
            }
        }

        // Munkalap
        if( $tabla == 'munkalap' ) {
            if( $_POST['ujkentSzerkID'] ) {
                $munk_kapcs = $adatbazis_kapcsolat->s('bejelentes_id', 'munkalap_kapcs', "`munkalap_id` = '".$_POST['ujkentSzerkID']."'");

                if( $munk_kapcs === false ) { $db_error = true; }

                if( $munk_kapcs ) {
                    foreach($munk_kapcs as $mkapcs) {
                        $sql = $adatbazis_kapcsolat -> i('munkalap_kapcs',
                                                            array('munkalap_id', 'bejelentes_id',          'insert_dt',  'insert_usr',                               'update_usr'                             ),
                                                            array($id,           $mkapcs['bejelentes_id'], 'NOW()',       $_SESSION[get_db_tabla().'teljes_nev'],    $_SESSION[get_db_tabla().'teljes_nev']   )
                                                        );
                        if( $sql === false ) { $db_error = true; }

                    }
                }
            }

            // Bejelentésből újként szerkesztve
            if( $_POST['ujkentSzerkBejelentesbol'] ) {
                $sql = $adatbazis_kapcsolat -> i('munkalap_kapcs',
                                                    array('munkalap_id', 'bejelentes_id',                    'insert_dt',  'insert_usr',                               'update_usr'                             ),
                                                    array($id,           $_POST['ujkentSzerkBejelentesbol'], 'NOW()',       $_SESSION[get_db_tabla().'teljes_nev'],    $_SESSION[get_db_tabla().'teljes_nev']   )
                                                );
                if( $sql === false ) { $db_error = true; }

                // Ekkor a csatolt fájlokat is átmásoljuk
                if( bejelentes_dok_munkalapba_masol($_POST['ujkentSzerkBejelentesbol'], $id) === false ){
                    $db_error = true;
                }

            }

            // Felvesszük az első státuszt
            $sql = $adatbazis_kapcsolat -> i($tabla.'_statusz',
                                                array($tabla.'_recid', 'stat_id',               'login',                           'insert_dt',    'insert_usr',                              'update_usr'                          ),
                                                array($id,              MUNKALAP_STAT_NYITOTT,  $_SESSION[get_db_tabla().'user'],  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],    $_SESSION[get_db_tabla().'teljes_nev'] )
                                            );
            if( $sql === false ) { $db_error = true; }
            $sql = $adatbazis_kapcsolat->u($tabla, array('stat_id'), array(MUNKALAP_STAT_NYITOTT), "`recid`='$id'");
            if( $sql === false ) { $db_error = true; }

            // Ha van előkészítés jogunk, és post-ként kaptunk is "elokeszitett_statuszba" információt, akkor rögtön léptetjük is tovább a sáttuszát
            if (_jog(JOG_MUNKALAP_KULSOS_ELOKESZIT) && $_POST['statuszba_tovabb_leptet']) {
                if ( addStatusz($tabla.'_statusz', $id, $_POST['statuszba_tovabb_leptet']) === false ){
                    $db_error = true;
                }
            }

        }

        // Projektigénylés státusz beszúrása
        if( $tabla == 'projektigenyles' ) {

            $sql = $adatbazis_kapcsolat -> i($tabla.'_statusz',
                                                array($tabla.'_recid', 'stat_id',                'login',                           'insert_dt',    'insert_usr',                             'update_usr'                           ),
                                                array($id,             PRIGENY_STAT_LETRE_ALATT, $_SESSION[get_db_tabla().'user'],  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'] )
                                            );
            if( $sql === false ) { $db_error = true; }
            $sql = $adatbazis_kapcsolat->u($tabla, array('stat_id'), array(PRIGENY_STAT_LETRE_ALATT), "`recid`='$id'");
            if( $sql === false ) { $db_error = true; }

        }
        
        // Projektezelés státusz beszúrása + levélküldés + igénylés cash-flow áthozatala
        if( $tabla == 'projektkezeles' ) {
            
            // Cash-flow sorok áthozatala
            if( $_POST['projektigenyles_recid'] ) {
                
                // Telephely szerint GROUP BY-ozunk, hogy azono szállodákat egy sorban hozzunk létre
                $cash_flow = $adatbazis_kapcsolat->s('*', 'cash_flow', "`apa_recid`='{$_POST['projektigenyles_recid']}'", '', '', 'telephely_id');
                if( $cash_flow === false ) { $db_error = true; }
                
                foreach( $cash_flow as $cf ) {
                    $cf_nev_id = $adatbazis_kapcsolat->i('projektkezeles_cashflow_nev', 
                                                            array('projektkezeles_recid',  'telephely_id',      'login',                            'insert_dt', 'insert_usr',                              'update_usr'                            ),
                                                            array($id,                     $cf['telephely_id'], $_SESSION[get_db_tabla().'user'],   'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'],    $_SESSION[get_db_tabla().'teljes_nev']  )
                                                        );
                    if( $cf_nev_id === false ) { $db_error = true; }
                    
                    $cash_flow_datumok = $adatbazis_kapcsolat->s('*', 'cash_flow', "`apa_recid`='{$_POST['projektigenyles_recid']}' AND `telephely_id`='{$cf['telephely_id']}'");
                    if( $cash_flow_datumok === false ) { $db_error = true; }
                    foreach( $cash_flow_datumok as $cf_datumok ) {
                        $sql = $adatbazis_kapcsolat->i('projektkezeles_cashflow', 
                                                                array('projektkezeles_recid', 'cashflow_nev_recid', 'datum',                'terv',                 'login',                            'insert_dt', 'insert_usr',                              'update_usr'                            ),
                                                                array($id,                    $cf_nev_id,           $cf_datumok['datum'],   $cf_datumok['osszeg'],  $_SESSION[get_db_tabla().'user'],   'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'],    $_SESSION[get_db_tabla().'teljes_nev']  )
                                                    );
                        if( $sql === false ) { $db_error = true; }
                    }
                }
                
            }
            
            $sql = $adatbazis_kapcsolat -> i($tabla.'_statusz',
                                                array($tabla.'_recid', 'stat_id',             'login',                          'insert_dt',    'insert_usr',                             'update_usr'                           ),
                                                array($id,             PRKEZEL_STAT_NYITOTT,  $_SESSION[get_db_tabla().'user'], 'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'] )
                                            );
            if( $sql === false ) { $db_error = true; }
            $sql = $adatbazis_kapcsolat->u($tabla, array('stat_id'), array(PRKEZEL_STAT_NYITOTT), "`recid`='{$id}'");
            if( $sql === false ) { $db_error = true; }
            
            $mail_hibaizenet = level_felkeszit_es_kuld('projektkezeles', $id, '', 'projektkezelesUj', 'projektkezeles_felelose', '');
            if( $mail_hibaizenet ) {
                echo json_encode(array(
                    'hiba'  => $mail_hibaizenet,
                    'title' => 'Hiba',
                ));
                $weblap -> leallit();
                exit();
            }
        }
        
        // Projektszámla tételek felvétele
        if( $tabla == 'projektszamla' ) {
            $i = 0;
            foreach ( $_SESSION['pr_szla_tetel_targy'] as $targy ) {
                $netto_ert  = str_replace(' ', '',  $_SESSION['pr_szla_tetel_netto_ert'][$i]);
                $dev_net    = str_replace(array(' ', ','), array('', '.'),  $_SESSION['pr_szla_tetel_dev_net'][$i]);
                $arfolyam   = str_replace(array(' ', ','), array('', '.'),  $_POST['arfolyam']);
                // Devizás számla esetén a Ft-os összeget az árfolyamból számítjuk ki
                if( $dev_net>0 ) {
                    $netto_ert = round($dev_net*$arfolyam);
                }
                $sql = $adatbazis_kapcsolat->i('projektszamla_tetel',
                                                    array('projektszamla_recid', 'targy', 'netto_ert', 'dev_net', 'projektszerzodes_tetel_recid',          'loginnev',                          'insert_dt',    'insert_usr',                             'update_usr'                              ),
                                                    array($id,                   $targy,  $netto_ert,  $dev_net,  $_SESSION['pr_szla_tetel_pr_tetel'][$i], $_SESSION[get_db_tabla().'user'],    'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']    )
                                            );
                if( $sql === false ) { $db_error = true; }

                // Illetve a projektszerződés tételénél updateljük a teljesítést és nettó értéket
                $sql = $adatbazis_kapcsolat->u('projektszerzodes_tetel', array('teljesites', 'ertek'), array($_POST['telj_dt'], $netto_ert), "`recid`='{$_SESSION['pr_szla_tetel_pr_tetel'][$i]}'");
                if( $sql === false ) { $db_error = true; }
                
                $i++;
            }
            
            // Feldolgozással a SESSION értékeket ki is ürítjük.
            unset($_SESSION['pr_szla_tetel_targy']);
            unset($_SESSION['pr_szla_tetel_netto_ert']);
            unset($_SESSION['pr_szla_tetel_dev_net']);
        }

        // Projektszerződés tételek felvétele
        if( $tabla == 'projektszerzodes' ) {
            
            $i = 0;
            foreach ( $_SESSION['pr_szer_tetel_targy'] as $targy ) {
                $varh_erk = str_replace('.', '-', $_SESSION['pr_szer_tetel_varh_erk'][$i]);
                $varh_ert = str_replace(' ', '',  $_SESSION['pr_szer_tetel_varh_ert'][$i]);
                $dev_net  = str_replace(array(' ', ','), array('', '.'),  $_SESSION['pr_szer_tetel_dev_net'] [$i]);
                $arfolyam = str_replace(array(' ', ','), array('', '.'),  $_POST['arfolyam']);
                // Devizás számla esetén a Ft-os összeget az árfolyamból számítjuk ki
                if( $dev_net>0 ) {
                    $varh_ert = round($dev_net*$arfolyam);
                }
                $sql = $adatbazis_kapcsolat->i('projektszerzodes_tetel',
                                                    array('projektszerzodes_recid', 'targy', 'varh_erk', 'varh_ert', 'dev_varh_ert', 'loginnev',                          'insert_dt',    'insert_usr',                             'update_usr'                              ),
                                                    array($id,                      $targy,  $varh_erk,  $varh_ert,  $dev_net,       $_SESSION[get_db_tabla().'user'],    'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']    )
                                            );
                if( $sql === false ) { $db_error = true; }
                
                $i++;
            }

            // Feldolgozással a SESSION értékeket ki is ürítjük.
            unset($_SESSION['pr_szer_tetel_varh_erk']);
            unset($_SESSION['pr_szer_tetel_varh_ert']);
            unset($_SESSION['pr_szer_tetel_dev_net']);

            // Jótállás gyermektábla kialakítása
            $jotallas = pr_szerzodes_jotallas_beallit( $id, $_POST['muszaki_gar_kezd'], $_POST['muszaki_gar_vege'], 'új feltöltés' );
            if( $jotallas === false ) { $db_error = true; }
            
        }

        // Naplózás
        // Kezdeményezett művelet
        $muvelet = 'A(z) '.$tabla.'-ba új recordot szúrt be a következő azonosítóval: '.$id.'.';

        // Végrehajtott változtatás
        $valtozas = '[mezo]recid[/mezo] [ertek]'.$id.'[/ertek]'."\n";

        $i = 2;
        foreach($_POST as $key => $value) {
            if( $key != 'mehet_gomb' && $key != 'uj'.$tabla ) {
                $valtozas .= '[mezo]'.$key.'[/mezo] [ertek]'.$value.'[/ertek]'."\n";

                $i++;
            }
        }

        // Logolás
        $sql = $adatbazis_kapcsolat -> i(
                                            'log',
                                            array( 'felh_id',                               'nev',       'valtozas', 'ip'                   ),
                                            array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,    $valtozas,  $_SERVER['REMOTE_ADDR'])
                                        );
        if( $sql === false ) { $db_error = true; }


    // Update
	} else {

        // Bejelentések szerkesztésekor
        if( $tabla == 'bejelentes' ) {

            // Csak nyitott státuszú bejelentést lehet szerkeszteni
            $akt_statusz = $adatbazis_kapcsolat->s2('stat_id', $tabla, "`recid`='{$_GET['id']}'");
            if( $akt_statusz === false ) { $db_error = true; }

            if( $akt_statusz ) {
                if( $akt_statusz['stat_id'] != BEJELENTES_STAT_NYITOTT ) {
                    echo json_encode(array(
                        'hiba' => 'Csak nyitott státuszú bejegyzést lehet módosítani!'
                    ));
                    exit();
                }
            } else {
                echo json_encode(array(
                    'hiba' => 'Státusz nem található!'
                ));
                exit();
            }

            // Ha a bejelentéshez tartozik munkalap, akkor annak adatait is frissítjük
            $munkalapok = $adatbazis_kapcsolat->s('munkalap_id', 'munkalap_kapcs', "`bejelentes_id`='{$_GET['id']}'");
            $munkalapok = adatbazis2tomb($munkalapok, 'munkalap_id');
            if($munkalapok){

                // Van pár mező, ami a munkalapban létezik, de a bejelentésnél nem, ezért ki kell számolni:
                $_POST['gep_txt']  = getErtek('nev', 'eszkozkezeles', 'recid', $_POST['gep_id']);
                $_POST['hiba_txt'] = getErtekLista('', '', $_POST['hiba_id']);
                $_POST['obj_txt']  = getErtek('nev', 'obj', 'recid', $_POST['obj_id']);

                // Adatok összeszedése
                $munkalapba_mezok   = array('telephely_id', 'obj_id', 'obj_txt', 'gep_id', 'gep_txt', 'hiba_id', 'hiba_txt', 'surgos');
                $munkalapba_ertekek = array();
                foreach( $munkalapba_mezok as $mezo ){
                    array_push($munkalapba_ertekek, $_POST[$mezo]);
                }

                $sql = $adatbazis_kapcsolat->u('munkalap', $munkalapba_mezok, $munkalapba_ertekek, "`recid` IN (".implode(', ', $munkalapok).")");
                if( $sql === false ) { $db_error = true; }

            }

        }

        // Ha volt változás, akkor logoljuk
        $record = $adatbazis_kapcsolat->s('*', $tabla, 'recid='.$_GET['id'], '', '1');
        if( $record === false ) { $db_error = true; }
        if( $record ) {

            require_once 'selectek.php';
            
            // Kezdeményezett művelet
            $muvelet = 'A(z) '.$tabla.' '.$_GET['id'].'. recordját módosította.';

            // Végrehajtott változtatás
            $valtozas       = '';
            $valtozas_megj  = '';
            
            // Mely felületeken kerüljön megjegyzésbe a módosítás
            $mod_megjegyzesbe_mehet = false;
            $mod_megjegyzesbe = array( 'projektkezeles.php', 'projektszamla.php', 'projektszerzodes.php' );
            $kiveteles_mezok  = array( 'stat_id', 'update_dt' );
            if( in_array(basename(self()), $mod_megjegyzesbe) ) {
                $mod_megjegyzesbe_mehet = true;
            }

            $i = 1;
            foreach ( $record[0] as $key => $value ) {
                if( $key && $key != 'recid' ) {
                    
                    if( preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value) ) {
                        $value = date('Y.m.d', strtotime($value));
                    }
                    
                    $post_key = $_POST[$key];
                    
                    // Numerikus illetve dátum mezők esetén le kell kezelni, ha üres adatot kapnánk, újra legyenek formázva
                    foreach ( $tomb as $elem ) {
                        if( $elem['name'] == $key ) {
                            if( strpos($elem['class'], 'f_num') !== false ) {
                                if( !$post_key ) {
                                    $post_key = '0';
                                }
                                $tort  = trim( str_replace('data-tort=', '', $elem['data']), '"' );
                                $value      = number_format($value,     $tort, ',', ' ');
                                $post_key   = number_format($post_key,  $tort, ',', ' ');
                            }
                            if( strpos($elem['class'], 'datepicker') !== false /* van még pl. "datepicker_tegnapig", de azt is felismeri az előző feltétel */ ) {
                                if( !$post_key ) {
                                    $post_key = '0000-00-00';
                                }
                            }
                            if( strpos($elem['class'], 'datetimepicker') !== false /* van még pl. "datepicker_tegnapig", de azt is felismeri az előző feltétel */ ) {
                                if( !$post_key ) {
                                    $post_key = '0000-00-00 00:00:00';
                                }
                            }
                        }
                    }
                    
                    // Adatbázisból jövő adatot visszaalakítunk, ne emiatt ne legyen egyenlő a két érték
                    $value = htmlspecialchars_decode($value);

                    // Ha az újonnan kapott adat, és DB-ben tárolt adat nem egyezik
                    // És vagy ez előző vagy az új adatnak van értéke (ne azért íródjon már be, mert üres az új érték, de 0 volt az adatbázisban)
                    if( $post_key != $value && ($post_key || $value) ) {

                        $valtozas .= '[mezo]'.$key.'[/mezo] [ertek_regi]'.$value.'[/ertek_regi] [ertek_uj]'.$post_key.'[/ertek_uj]'."\n";

                        // Ha a megjegyzésbe is bele szeretnénk írni a változást, akkor pár formázást még el kell végezni
                        if( $mod_megjegyzesbe_mehet && !in_array($key, $kiveteles_mezok) ) {
                            // Üres dátum mező esetének kivédése
                            if( !($post_key > '4000-00-00' && $value == '0000-00-00') ) {

                                $mezo_neve = '';
                                $uj_ertek = $post_key;
                                
                                foreach ( $tomb as $elem ) {
                                    // Ha az aktuális elemnél járunk
                                    if( $elem['name'] == $key ) {
                                        
                                        $mezo_neve = trim($elem['megn'], ':');
                                        
                                        // Dátumot formázzuk
                                        if( strpos($elem['class'], 'datepicker') !== false || strpos($elem['class'], 'datetimepicker') !== false || strpos($elem['class'], 'datepicker_tegnapig') !== false || strpos($elem['class'], 'datepicker_holnaptol') !== false ) {
                                            $value    = datum_kiir($value);
                                            $uj_ertek = datum_kiir($uj_ertek);
                                        }
                                        
                                        // Számot is formázzuk
                                        if( strpos($elem['class'], 'f_num') !== false ) {
                                            $value    = str_replace(array(' ', ','), array('', '.'), $value);
                                            $uj_ertek = str_replace(array(' ', ','), array('', '.'), $uj_ertek);
                                            
                                            $tort     = trim( str_replace('data-tort=', '', $elem['data']), '"' );
                                            $value    = number_format($value,    $tort, ',', ' ');
                                            $uj_ertek = number_format($uj_ertek, $tort, ',', ' ');
                                        }

                                        // Autocompletes mezők
                                        if( $elem['type'] == 'autocomplete' ) {
                                            if( $elem['tabla'] == 'erteklista' ) {
                                                if( $elem['oszlop_keres'] == 'id' ) {
                                                    $uj_ertek = getErtekLista($elem['csoport'], $uj_ertek   );
                                                    $value    = getErtekLista($elem['csoport'], $value      );
                                                } elseif( $elem['oszlop_keres'] == 'recid' ) {
                                                    $uj_ertek = getErtekLista($elem['csoport'], '', $uj_ertek   );
                                                    $value    = getErtekLista($elem['csoport'], '', $value      );
                                                }
                                            } else {
                                                $uj_ertek = getErtek($elem['listaz'], $elem['tabla'], $elem['oszlop_keres'], $uj_ertek);
                                                $value    = getErtek($elem['listaz'], $elem['tabla'], $elem['oszlop_keres'], $value   );
                                            }
                                        }
                                        
                                        // Checkboxok lekezelése
                                        if( $elem['type'] == 'checkbox' ) {
                                            if( $uj_ertek ) {
                                                $uj_ertek = 'igen';
                                            } else {
                                                $uj_ertek = 'nem';
                                            }
                                            if( $value ) {
                                                $value = 'igen';
                                            } else {
                                                $value = 'nem';
                                            }
                                        }
                                        
                                        // Tipus mező lekezelése
                                        if( $elem['name'] == 'besorol' ) {
                                            foreach( $select_pri_besorol as $select ) {
                                                
                                                if( $select['name'] == '- Válasszon -' ) {
                                                    $select['name'] = '';
                                                }
                                                if( $select['value']==$uj_ertek ) {
                                                    $uj_ertek = $select['name'];
                                                }
                                                if( $select['value']==$value ) {
                                                    $value = $select['name'];
                                                }

                                            }
                                        }
                                        
                                        // Jóváhagyó mező lekezelése
                                        if( $elem['name'] == 'besorol' ) {
                                            foreach( $select_pri_besorol as $select ) {
                                                
                                                if( $select['name'] == '- Válasszon -' ) {
                                                    $select['name'] = '';
                                                }
                                                if( $select['value']==$uj_ertek ) {
                                                    $uj_ertek = $select['name'];
                                                }
                                                if( $select['value']==$value ) {
                                                    $value = $select['name'];
                                                }

                                            }
                                        }
                                        
                                        // Szálloda select lekezelése
                                        if( $elem['name'] == 'telephely_id' ) {
                                            $uj_ertek = getSzallodaNev($uj_ertek);
                                            $value    = getSzallodaNev($value   );
                                        }
                                        
                                        // Tipus mező lekezelése
                                        if( $elem['name'] == 'tipus' ) {
                                            foreach( $select_pri_tipus as $select ) {
                                               
                                                if( $select['name'] == '- Válasszon -' ) {
                                                    $select['name'] = '';
                                                }
                                                if( $select['value']==$uj_ertek ) {
                                                    $uj_ertek = $select['name'];
                                                }
                                                if( $select['value']==$value ) {
                                                    $value = $select['name'];
                                                }
                                               

                                            }
                                        }
                                    }
                                }

                                if( $mezo_neve ) {
                                    if( !$uj_ertek ) {
                                        $uj_ertek = '[üres]';
                                    }
                                    if( !$value ) {
                                        $value = '[üres]';
                                    }
                                    $valtozas_megj  .= $mezo_neve.' mező előző értéke: <b>'.$value.'</b>. Új értéke: <b>'.$uj_ertek.'</b><br>';
                                }
                            }
                        }
                        
                        // Ha a projektkezelésnél változott volna meg a Projektfelelős, akkor ezt át kell vinni a hozzá tartozó szerződésekbe, számlákba is
                        if( basename(self()) == 'projektkezeles.php' && $key == 'pfelelos' ) {
                            
                            // Szerződések bejárása
                            $szerzodesek = $adatbazis_kapcsolat->s('recid let_fel', 'projektszerzodes', "`pr_szam`='{$record[0]['ikt_szam']}'");
                            if( $szerzodesek === false ) { $db_error = true; }
                            foreach ( $szerzodesek as $szerzodes ) {
                                
                                $megj_kapcsolodoba = $mezo_neve.' mező előző értéke: <b>'.getErtek('teljes_nev', 'login', 'recid', $szerzodes['let_fel']).'</b>. Új értéke: <b>'.$uj_ertek.'</b><br>';
                                
                                $sql = $adatbazis_kapcsolat->u('projektszerzodes', array('let_fel'), array($_POST['pfelelos']), "`recid`='{$szerzodes['recid']}'");
                                if( $sql === false ) { $db_error = true; }
                                
                                if( addMegj('projektszerzodes', $szerzodes['recid'], $megj_kapcsolodoba) === false ){
                                    $db_error = true;
                                }
                                
                            }
                            
                            // Számlák bejárása
                            $szamlak = $adatbazis_kapcsolat->s('recid let_fel', 'projektszamla', "`pr_szam`='{$record[0]['ikt_szam']}'");
                            if( $szamlak === false ) { $db_error = true; }
                            foreach ( $szamlak as $szamla ) {
                                
                                $megj_kapcsolodoba = $mezo_neve.' mező előző értéke: <b>'.getErtek('teljes_nev', 'login', 'recid', $szamla['let_fel']).'</b>. Új értéke: <b>'.$uj_ertek.'</b><br>';
                                
                                $sql = $adatbazis_kapcsolat->u('projektszamla', array('let_fel'), array($_POST['pfelelos']), "`recid`='{$szamla['recid']}'");
                                if( $sql === false ) { $db_error = true; }
                                
                                if( addMegj('projektszamla', $szamla['recid'], $megj_kapcsolodoba) === false ){
                                    $db_error = true;
                                }
                                
                            }
                            
                        }
                        
                        $i++;
                        
                    }
                }
            }

            $sql = $adatbazis_kapcsolat -> i(
                'log',
                array( 'felh_id',                               'nev',       'valtozas', 'ip'                   ),
                array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,    $valtozas,  $_SERVER['REMOTE_ADDR'])
            );
            if( $sql === false ) { $db_error = true; }
            
            // Mely felületeken kerüljön megjegyzésbe a módosítás
            if( in_array( basename(self()), $mod_megjegyzesbe) ){
                if( addMegj($tabla, $_GET['id'], $valtozas_megj) === false ){
                    $db_error = true;
                }
            }
            
        }

        // Datetime-ot is frissítjük
        if( in_array($tabla.'.php', $_SESSION['ketHasabos']) ) {
            array_push($oszlopok, 'update_usr'                          );
            array_push($ertekek,  $_SESSION[get_db_tabla().'teljes_nev']);
        }

        // Loginnál szerepkörök lekezelése + Jelszóval rendelkezik pipa kiszedése esetén e-mail értesítés küldése
        if( $tabla == 'login' ) {
            
            // Mailcímek helyességének ellenőrzése
            $hibauzenet = is_valid_mail_tobb($_POST['usermail'], 'hibaüzenettel');
            if( $hibauzenet ){
                echo json_encode(array(
                    'hiba'  => $hibauzenet,
                    'title' => 'Hiba',
                    'focus' => 'usermail',
                ));

                $weblap->leallit();
                exit();
            }

            // Ha változott a szerepkör, akkor a jogokat frissítjük
            $login = $adatbazis_kapcsolat->s2('szemelyzet_id van_jelszo', 'login', "`recid`='{$_GET['id']}'");
            $van_jelszo = $login['van_jelszo']; // Ez később a commit után kell
            if( $login['szemelyzet_id'] != $_POST['szemelyzet_id'] ) {
                $szerepkor = $adatbazis_kapcsolat->s2('flags', 'szerepkor', "`recid`='{$_POST['szemelyzet_id']}'", '', '1');
                if( $szerepkor === false ) { $db_error = true; }

                if( $szerepkor['flags'] ) {
                    array_push($oszlopok, 'flags'             );
                    array_push($ertekek,   $szerepkor['flags']);
                }
            }
            
            // Adott felhasználó részlege nem lehet társrészleg
            $sql = $adatbazis_kapcsolat->d('login_tarsreszleg', "`login_id`='{$_GET['id']}' AND `reszleg_id`='{$_POST['reszleg_id']}'");
            if($sql===false){ $db_error = true; }

        }

        // Projektigényléseknél egy-több szállodás váltás lekezelése
        if( $tabla == 'projektigenyles' ) {
            $prigeny = $adatbazis_kapcsolat->s2('egy_tobb telephely_id', $tabla, "`recid`='{$_GET['id']}'");

            // Ha többszállodás
            if($prigeny['egy_tobb']==1){

                // Változott a telephely, akkor töröljük a Cash-flow adatokat
                if($prigeny['telephely_id']!=$_POST['telephely_id']){
                    $sql = $adatbazis_kapcsolat->d('cash_flow', "`apa_recid`='{$_GET['id']}'");
                    if( $sql === false ) { $db_error = true; }
                }

                // Ha minden telephely azonos, akkor átállunk egy szállodásra
                $cash_flowk = $adatbazis_kapcsolat->s/*NEM JÓ AZ S2!*/('DISTINCT telephely_id', 'cash_flow', "`apa_recid`='{$_GET['id']}'");
                if( count($cash_flowk)==1 ){

                    $i_tmp = 0;
                    foreach($oszlopok as $ertek){
                        if($ertek=='egy_tobb'){
                            $ertekek[$i_tmp] = 0;
                        }
                        if($ertek=='telephely_id'){
                            $ertekek[$i_tmp] = $cash_flowk[0]['telephely_id']; // A nulla kell ide!!!, így helyes! NEM JÓ az s2() változat!!!
                        }
                        $i_tmp++;
                    }

                }

            }

            // Ha igény rögzítéséről van szó, akkor státuszt léptetünk, illetve iktatószámot is ekkor kap
            if ($_POST['igeny_rogzites']) {
                if( addStatusz( 'projektigenyles_statusz', $_GET['id'], PRIGENY_STAT_NYITOTT ) === false ) { $db_error = true; }
                // Iktatószám kiszámítása
                $sorszam  = get_iktatoszam_sorszam($tabla);
                if($sorszam===false){ $db_error = true; }
                $ikt_szam = 'PI'.date('y').'/'.sprintf('%04d', $sorszam);

                array_push($oszlopok,  'ikt_szam');
                array_push($ertekek,   $ikt_szam );
                
                // Ha van már csatolt dokumentuma, akkor azokat másoljuk át az iktatószám nevű mappába
                $alap_utvonal = '../'.get_utvonal().'projektigenyles/';
                $konyvtar     = str_replace('/', '_', $ikt_szam);
                mkdir( $alap_utvonal.$konyvtar );
                $dokumentumok = $adatbazis_kapcsolat->s('*', 'projektigenyles_dok', "`projektigenyles_id`='{$_GET['id']}'");
                if( $dokumentumok === false ) { $db_error = true; }
                foreach( $dokumentumok as $dok ){
                    copy($alap_utvonal.$dok['utvonal'], $alap_utvonal.$konyvtar.$dok['utvonal']);
                    unlink($alap_utvonal.$dok['utvonal']);
                    $adatbazis_kapcsolat->u('projektigenyles_dok', array('utvonal'), array($konyvtar.$dok['utvonal']), "`recid`='{$dok['recid']}'");
                }
            }

        }

        // Projekt módosításakor levélküldés
        if( $tabla == 'projektkezeles' ) {
            
            // Levélküldés a módosítsáról. Két eset van: projektfelelős változott vagy sem. Ha igen, akkor a régi és új felelős is kap mailt, egyébként csak a meglévő felelős
            $prk = $adatbazis_kapcsolat->s2('pfelelos', 'projektkezeles', "`recid`='{$_GET['id']}'");
            if( $prk === false ) { $db_error = true; }
            
            
            // Ha nem változott a Pfelelős
            if( $_POST['pfelelos'] == $prk['pfelelos'] ){

                $mail_hibaizenet = level_felkeszit_es_kuld('projektkezeles', $_GET['id'], '', 'projektkezelesModosit', 'projektkezeles_felelose', '');
                if( $mail_hibaizenet ){
                    echo json_encode(array(
                        'hiba'  => $mail_hibaizenet,
                        'title' => 'Hiba',
                    ));
                    $weblap -> leallit();
                    exit();
                }
            
            // Ha változott a Projekt felelőse
            } else {

                $mail_hibaizenet = level_felkeszit_es_kuld('projektkezeles', $_GET['id'], '', 'projektkezelesModosit', 'projektkezeles_felelose', '', $_POST['pfelelos']);
                if( $mail_hibaizenet ){
                    echo json_encode(array(
                        'hiba'  => $mail_hibaizenet,
                        'title' => 'Hiba',
                    ));
                    $weblap -> leallit();
                    exit();
                }
                $mail_hibaizenet = level_felkeszit_es_kuld('projektkezeles', $_GET['id'], '', 'projektkezelesModositElozo', 'projektkezeles_felelose', '', $prk['pfelelos']);
                if( $mail_hibaizenet ){
                    echo json_encode(array(
                        'hiba'  => $mail_hibaizenet,
                        'title' => 'Hiba',
                    ));
                    $weblap -> leallit();
                    exit();
                }
            }
            
        }

        // Projekt számla
        if( $tabla == 'projektszamla' ) {
            
            // Ellenőrizzük, hogy a visszaadva pipa be van-e pipálva, ha igen, akkor a dátum kötelező
            if( $_POST['visszaadva'] && ( !$_POST['visszaadva_dt'] || $_POST['visszaadva_dt']>'9000-00-00') ){
                echo json_encode(array(
                    'hiba'  => 'Kérem adja meg a visszaadás dátumát!',
                    'focus' => 'visszaadva_dt',
                    'title' => 'Hiba',
                ));
                $weblap -> leallit();
                exit();
            }
            
            // Ha a visszaadva pipát most kapcsoltam be, akkor ellenőrizzük, hogy szerződéshez volt-e kapcsolva.
            // Ha igen, akkor a szerződés tétel kapcsolatot meg kell szüntetni, de előtte a szerződés tételeinél a teljesítést/értéket is ki kell nullázni.
            $szamla = $adatbazis_kapcsolat->s2('visszaadva pr_szam', 'projektszamla', "`recid`='{$_GET['id']}'");
            $pr_szam_projektkezeles_update_hez = $szamla['pr_szam']; // Ez az i_u() függvény legvégén kell, hogy az új és a régi pr_szam-ot is újra lehessen számolni
            if( $_POST['visszaadva'] && !$szamla['visszaadva'] ) {

                $szamla_tetelek = $adatbazis_kapcsolat->s('projektszerzodes_tetel_recid', 'projektszamla_tetel', "`projektszamla_recid`='{$_GET['id']}'");
                if( $szamla_tetelek === false ) { $db_error = true; }
                foreach( $szamla_tetelek as $tetelek ) {
                    $sql = $adatbazis_kapcsolat->u('projektszerzodes_tetel', array('teljesites', 'ertek',   'dev_ertek'),
                                                                             array('0000-00-00', '0',       '0.00'     ),
                                                    "`recid`='{$tetelek['projektszerzodes_tetel_recid']}'");
                    if( $sql === false ) { $db_error = true; }

                }
                
                $sql = $adatbazis_kapcsolat->u('projektszamla_tetel', array('projektszerzodes_tetel_recid'), array('0'), "`projektszamla_recid`='{$_GET['id']}'");
                if( $sql === false ) { $db_error = true; }
                
                // Továbbá a projektnél a felhasználható összeget csökkenteni kell a számla értékével
                if( $_POST['pr_szam'] ) {
                    $projekt = $adatbazis_kapcsolat->s2('fel_hato', 'projektkezeles', "`ikt_szam`='{$_POST['pr_szam']}'");
                    if( $projekt === false ) { $db_error = true; }

                    $sql = $adatbazis_kapcsolat->u('projektkezeles', array('fel_hato'), array($projekt['fel_hato']-$_POST['netto']), "`ikt_szam`='{$_POST['pr_szam']}'");
                    if( $sql === false ) { $db_error = true; }

                }
            }
            
            // Projekt számla mentésekor ellenőrizzük, hogy a dátumok nem múltbeliek-e. (Van pár, amelyiknél ezt ellenőrizni kell.)
            $csak_mult_datum_lehet = array(
                                            array('name' => 'erk_dt',        'alert' => 'Céghez érkezett dátumhoz csak mai, vagy múltbeli dátum adható meg!'),
                                            array('name' => 'kelte_dt',      'alert' => 'A számla kelte dátumhoz csak mai, vagy múltbeli dátum adható meg!' ),
                                            array('name' => 'visszaadva_dt', 'alert' => 'A visszaadva dátumhoz csak mai, vagy múltbeli dátum adható meg!'   ),
                                        );

            foreach ( $csak_mult_datum_lehet as $elem ) {

                // Visszaadva dátumot csak akkor figyeljük, ha a visszaadva pipa be is van pipálva
                if( $elem['name'] != 'visszaadva_dt' || $_POST['visszaadva'] ) {
                    if( $_POST[$elem['name']] > date('Y-m-d') ) {
                        echo json_encode(array(
                            'hiba'  => $elem['alert'],
                            'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); szurkit_zar(\'mehet_megsem_mutat\'); $(\'#'.$elem['name'].'\').focus();">',
                            'title' => 'Hiba',
                        ));
                        $weblap -> leallit();
                        exit();
                    }
                }
            }
            
            // Ellenőrizzük továbbá, hogy a szálloda és a Projekt szám azonos szállodához tartozik-e
            if( $_POST['pr_szam'] ) {
                $projekt = $adatbazis_kapcsolat->s2('telephely_id', 'projektkezeles', "`ikt_szam`='{$_POST['pr_szam']}'");
                if( $projekt === false ) { $db_error = true; }
                if( $projekt['telephely_id'] != $_POST['telephely_id'] ) {
                    echo json_encode(array(
                        'hiba'  => 'A projekt szám nem megfelelő szállodához tartozik!',
                        'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); szurkit_zar(\'mehet_megsem_mutat\');">',
                        'title' => 'Hiba',
                    ));
                    $weblap -> leallit();
                    exit();
                }
            }

            // Projekt számla mentésekor HA nem nullás a számla, akkor ellenőrizzük, hogy a számla nettó értéke azonos-e a tételek összegével
            // Deviza esetén más session-t járunk be
            if( !$_POST['nullas'] ) {
                $tizedesek  = 0;
                $mezo1      = 'pr_szla_tetel_netto_ert';
                $mezo2      = 'netto';
                $szoveg     = 'szerződés összegével';
                if( $_POST['kcs_21']!=KCS_21_HUF ) {
                    $tizedesek  = 2;
                    $mezo1      = 'pr_szla_tetel_dev_net';
                    $mezo2      = 'dev_net';
                    $szoveg     = 'deviza nettóval';
                }
                $tetelek_osszege = '';
                foreach ( $_SESSION[$mezo1] as $ertek ) {
                    $ertek = str_replace(array(' ', ','), array('', '.'), $ertek);
                    $tetelek_osszege += $ertek;
                }

                if( $tetelek_osszege != $_POST[$mezo2] ) {
                    echo json_encode(array(
                        'hiba'   => 'A számla tételeinek összege ('. number_format($tetelek_osszege, $tizedesek, ',', ' ').' '. erteklista(ERTEKLISTA_PENZNEM, $_POST['kcs_21']).') nem egyezik meg a számla nettó értékével('. number_format($_POST[$mezo2], $tizedesek, ',', ' ').' '. erteklista(ERTEKLISTA_PENZNEM, $_POST['kcs_21']).')!<br>Így nem menthető!',
                        'gomb'   => '<input type="button" class="gomb" value="Ok" onclick="szurkit_zar(\'mégsem mehet mutat\'); alert2();">',
                        'title'  => 'Hiba',
                        'select' => 'netto',
                    ));
                    $weblap -> leallit();
                    exit();
                }
                
            // Ha nullás egy számla, akkor a tételeit törölni kell, létre kell hozni egy nullás sort
            } else {
                
                // Nullás tételt csak akkor hozzuk létre, ha még nem volna
                $van_nullas = $adatbazis_kapcsolat->s('recid', 'projektszamla_tetel', "`projektszamla_recid`='{$_GET['id']}' AND `netto_ert`='0' AND `dev_net`='0.00'");
                if( $van_nullas === false ) { $db_error = true; }
                
                $tetelek_szama = $adatbazis_kapcsolat->s('recid', 'projektszamla_tetel', "`projektszamla_recid`='{$_GET['id']}'", '', '', '', 'num_rows');
                if( $tetelek_szama === false ) { $db_error = true; }

                // Ha nem csak a nullás tétele van, akkor töröljük őket, és létrehozzuk a nullást
                if( !$van_nullas || $tetelek_szama>1 ) {

                    $sql = $adatbazis_kapcsolat->d('projektszamla_tetel', "`projektszamla_recid`='{$_GET['id']}'");
                    if( $sql === false ) { $db_error = true; }
                    
                    $sql = $adatbazis_kapcsolat->i('projektszamla_tetel',   array('projektszamla_recid',    'targy',        'insert_dt', 'insert_usr'   ),
                                                                            array($_GET['id'],              'Nullás tétel', 'NOW()',     get_nev()      )
                                                );
                    if( $sql === false ) { $db_error = true; }
                }


            }

            // Vagy ha megadta a dátumot, de nincs bepipálva a visszaadva, akkor meg azért hiba
            if( !$_POST['visszaadva'] && $_POST['visszaadva_dt'] && $_POST['visszaadva_dt']<'9000-00-00' ){
                echo json_encode(array(
                    'hiba'  => 'Csak akkor adja meg a visszaadás dátumát, ha bekapcsolta a visszaadás pipát!',
                    'focus' => 'visszaadva_dt',
                    'title' => 'Hiba',
                ));
                $weblap -> leallit();
                exit();
            }
            
            // Projektszámla mentésekor, ha az devizás, akkor a Ft-os értékeket az árfolyam alapján kiszámoljuk
            if( $_POST['kcs_21']!=KCS_21_HUF ) {

                $arfolyam = str_replace(array(' ', ','), array('', '.'),  $_POST['arfolyam']);
                $tetelek = $adatbazis_kapcsolat->s('recid dev_net', 'projektszamla_tetel', "`projektszamla_recid`='{$_GET['id']}'");
                if( $tetelek === false ) { $db_error = true; }
                foreach( $tetelek as $tetel ) {
                    $sql = $adatbazis_kapcsolat->u('projektszamla_tetel', array('netto_ert'), array(round($tetel['dev_net']*$arfolyam)), "`recid`='{$tetel['recid']}'");
                    if( $sql === false ) { $db_error = true; }
                }
            // Ha nem devizás a számla, akkor viszont a tételeknél a deviza oszlopot nullázzuk
            // Ez arra az esetre jó, ha devizás volt a számla, és váltottuk sima Ft-ra, akkor a devzizás értékeket ürítjük
            } else {
                $sql = $adatbazis_kapcsolat->u('projektszamla_tetel', array('dev_net'), array(0), "`projektszamla_recid`='{$_GET['id']}'");
                if( $sql === false ) { $db_error = true; }
            }
            
            // Illetve a szerződés számot, projekt számot, létesítmény felelőst, költség viselőt még külön fel kell még venni, mert a tiltott mező miatt ez magától nem íródna be
            array_push($oszlopok, 'sz_szam',         'pr_szam',         'let_fel',        'kolt_vis'            );
            array_push($ertekek,  $_POST['sz_szam'], $_POST['pr_szam'], $_POST['let_fel'], $_POST['kolt_vis']   );
            
        }
        
        // Projekt szerződés
        $szerzodes = $adatbazis_kapcsolat->s2('pr_szam', 'projektszerzodes', "`recid`='{$_GET['id']}'");
        $pr_szam_projektkezeles_update_hez = $szerzodes['pr_szam'];
        if( $tabla == 'projektszerzodes' ) {

            // Kivitelezés kezdete, nem lehet korábbi, mint a kelte
            if( $_POST['kiv_kezd'] < $_POST['kelte_dt'] ) {
                echo json_encode(array(
                    'hiba'  => 'Kivitelezés kezdete, nem lehet korábbi, mint a kelte!',
                    'title' => 'Hiba',
                    'focus' => 'kiv_kezd',
                ));
                $weblap -> leallit();
                exit();
            }
            
            // Kivitelezés kezdete, nem lehet korábbi, mint a kivitelezés vége
            if( $_POST['kiv_kezd'] > $_POST['kiv_vege'] ) {
                echo json_encode(array(
                    'hiba'  => 'Kivitelezés vége nem lehet korábbi, mint a kivitelezés kezdete!',
                    'title' => 'Hiba',
                    'focus' => 'kiv_vege',
                ));
                $weblap -> leallit();
                exit();
            }
            
            // Ha a deviza nettó és árfolyam szorzata nem egyenlő a szerződött összeggel, akkor erre rákérdezünk
            if( !$_POST['deviza_arf_szorzat_mehet']                 &&  // Ha még nem kaptunk mehet jelzést
                (!$_POST['kcs_21'] || $_POST['kcs_21']!=KCS_21_HUF) &&  // Devizás a szerződés, azaz nem üres vagy HUF a deviza
                ( round($_POST['dev_net']*$_POST['arfolyam']) != $_POST['szerz_ossz'] )      // A szorzat kerekítése nem stimmel
            ) {

                echo json_encode(array(
                    'hiba'  => 'A Devizat nettó és Árfolyam mezők szorzata nem egyenlő a Szerződött összeggel.
                                <br>Folytatja?',
                    'title' => 'Kérdés',
                    'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#deviza_arf_szorzat_mehet\').val(\'1\'); mentesGomb();"><input type="button" class="gomb piros" value="Nem" onclick="alert2();">'
                ));
                $weblap -> leallit();
                exit();
            }
            
            $sor = $adatbazis_kapcsolat->s2('pr_szam', 'projektszerzodes', "`recid`='{$_GET['id']}'");
            if( $sor === false ) { $db_error = true; }

            // A hozzáadott Projekthez milyen szerződések tartoznak. Ha a projekt összege - a már hozzáadott összegek különbsgée kisebb mint a mostani szerződés összege, akkor hibaüzenet
            if( $_POST['pr_szam'] ) {
                $projekt = $adatbazis_kapcsolat->s2('jkolt', 'projektkezeles', "`ikt_szam`='{$_POST['pr_szam']}'");
                if( $projekt === false ) { $db_error = true; }
                $szerzodesek = $adatbazis_kapcsolat->s2('SUM(`szerz_ossz`)', 'projektszerzodes', "`pr_szam`='{$_POST['pr_szam']}' AND `recid`!='{$_GET['id']}'");
                if( $szerzodesek === false ) { $db_error = true; }
                $felhasznalhato = $projekt['jkolt']-$szerzodesek['SUM(`szerz_ossz`)'];
                if( $felhasznalhato < $_POST['szerz_ossz'] && !$_POST['keret_tullepve_mehet'] ){
                    echo json_encode(array(
                        'hiba'  => nl2('
                                        Projekt jóváhagyott keret: '.number_format($projekt['jkolt'], 0, '', ' ').' '.get_penznem().'
                                        <br>Összes szerződött összeg: '.number_format($szerzodesek['SUM(`szerz_ossz`)']+$_POST['szerz_ossz'], 0, '', ' ').' '.get_penznem().'.
                                        <br>Ebből még felhasználható: '.number_format($felhasznalhato-$_POST['szerz_ossz'], 0, '', ' ').' '.get_penznem().'.
                                        <br>
                                        <br>Az összes szerződés összege nagyobb, mint a jóváhagyott keret! Biztosan folytatja?
                                    '),
                        'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#keret_tullepve_mehet\').val(1); mentesGomb();"> <input type="button" class="gomb piros" value="Nem" onclick="szurkit_zar(\'mégsem mehet mutat\'); alert2();">',
                        'title' => 'Kérdés',
                    ));
                    $weblap -> leallit();
                    exit();
                }
                
            }
            
            // Illetve a projekt mezőit is frissítjük, ha van már hozzáadva Projekt szám
            // Ez két esetet jelent: lehet most adtuk hozzá:
            if( $_POST['pr_szam'] ) {
                $szerzodesek = $adatbazis_kapcsolat->s2('SUM(`szerz_ossz`)', 'projektszerzodes', "`pr_szam`='{$_POST['pr_szam']}' AND `recid`!='{$_GET['id']}'");
                if( $szerzodesek === false ) { $db_error = true; }
                $projekt = $adatbazis_kapcsolat->s2('jkolt', 'projektkezeles', "`ikt_szam`='{$_POST['pr_szam']}'");
                if( $projekt === false ) { $db_error = true; }
                
                $sz_ossz = $szerzodesek['SUM(`szerz_ossz`)']+ $_POST['szerz_ossz'];
                $fel_hato = $projekt['jkolt']-$sz_ossz;
                $sql = $adatbazis_kapcsolat->u('projektkezeles', array('sz_ossz', 'fel_hato'), array($sz_ossz, $fel_hato), "`ikt_szam`='{$_POST['pr_szam']}'");
                if( $sql === false ) { $db_error = true; }
                
            // De az is lehet, hogy most vettük le, csak DB-ben létezik, ekkor is újra kell számíttatni, kicsit másképp
            } elseif( $sor['pr_szam'] ) {
                $szerzodesek = $adatbazis_kapcsolat->s2('SUM(`szerz_ossz`)', 'projektszerzodes', "`pr_szam`='{$sor['pr_szam']}' AND `recid`!='{$_GET['id']}'");
                if( $szerzodesek === false ) { $db_error = true; }
                $projekt = $adatbazis_kapcsolat->s2('jkolt', 'projektkezeles', "`ikt_szam`='{$sor['pr_szam']}'");
                if( $projekt === false ) { $db_error = true; }
                
                $sz_ossz = $szerzodesek['SUM(`szerz_ossz`)'];
                $fel_hato = $projekt['jkolt']-$sz_ossz;
                $sql = $adatbazis_kapcsolat->u('projektkezeles', array('sz_ossz', 'fel_hato'), array($sz_ossz, $fel_hato), "`ikt_szam`='{$sor['pr_szam']}'");
                if( $sql === false ) { $db_error = true; }
            }
            
            // Ellenőrizzük, hogy a szerződés összege azonos-e a tételek összegével
            // Deviza esetén más session-t járunk be
            $tizedesek  = 0;
            $mezo1      = 'pr_szer_tetel_varh_ert';
            $mezo2      = 'szerz_ossz';
            $mezo3      = 'varh_ert';
            $szoveg     = 'szerződés összegével';
            if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
                $tizedesek  = 2;
                $mezo1      = 'pr_szer_tetel_dev_net';
                $mezo2      = 'dev_net';
                $mezo3      = 'dev_varh_ert';
                $szoveg     = 'deviza nettóval';
            }
            $tetelek_osszege = '';
            // Korábban $_SESSION-ből dolgoztunk, de ez problémát okozott, ha két projektszerződés adatlap meg volt nyitva egy-egy tabon. Keveredtek a SESSION-ök. 
            // Továbbá úgy láttam, hogy a tételek még az adatlap mentése előtt eltárolódnak az adatbázisban, tehát ezen a ponton tényleg nem $_SESSION-ből kellene már dolgozni.
            // foreach ( $_SESSION[$mezo1] as $ertek ) {
            $tetelek = $adatbazis_kapcsolat->s($mezo3, 'projektszerzodes_tetel', "`projektszerzodes_recid`='{$_GET['id']}'");
            foreach( $tetelek as $tetel ) {
                $ertek = $tetel[$mezo3];
                $ertek = str_replace(array(' ', ','), array('', '.'), $ertek);
                $tetelek_osszege += $ertek;
            }
            if( $tetelek_osszege != $_POST[$mezo2] ) {
                echo json_encode(array(
                    'hiba'  => 'A szerződés tételeinek összege ('.number_format($tetelek_osszege, $tizedesek, ',', ' ').' '. getErtekLista(ERTEKLISTA_PENZNEM, $_POST['kcs_21']).') nem egyezik meg a '.$szoveg.' ('.number_format($_POST[$mezo2], $tizedesek, ',', ' ').' '. getErtekLista(ERTEKLISTA_PENZNEM, $_POST['kcs_21']).')!<br>Így nem menthető!',
                    'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="szurkit_zar(\'mégsem mehet mutat\'); alert2();">',
                    'title' => 'Hiba',
                ));
                $weblap -> leallit();
                exit();
            }
            
            // Ellenőrizzük továbbá, hogy a szálloda és a Projekt szám azonos szállodához tartozik-e
            if( $_POST['pr_szam'] ) {
                $projekt = $adatbazis_kapcsolat->s2('telephely_id', 'projektkezeles', "`ikt_szam`='{$_POST['pr_szam']}'");
                if( $projekt === false ) { $db_error = true; }
                if( $projekt['telephely_id']!=$_POST['telephely_id'] ){
                    echo json_encode(array(
                        'hiba'  => 'A projekt szám nem megfelelő szállodához tartozik!',
                        'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); szurkit_zar(\'mehet_megsem_mutat\');">',
                        'title' => 'Hiba',
                    ));
                    $weblap -> leallit();
                    exit();
                }
            }
            
            // Projektszerződés mentésekor, ha az devizás, akkor a Ft-os értékeket az árfolyam alapján kiszámoljuk
            if( $_POST['kcs_21']!=KCS_21_HUF ) {

                $arfolyam = str_replace(array(' ', ','), array('', '.'),  $_POST['arfolyam']);
                $tetelek = $adatbazis_kapcsolat->s('recid dev_varh_ert', 'projektszerzodes_tetel', "`projektszerzodes_recid`='{$_GET['id']}'");
                if( $tetelek === false ) { $db_error = true; }
                foreach( $tetelek as $tetel ) {
                    $sql = $adatbazis_kapcsolat->u('projektszerzodes_tetel', array('varh_ert'), array(round($tetel['dev_varh_ert']*$arfolyam)), "`recid`='{$tetel['recid']}'");
                    if( $sql === false ) { $db_error = true; }
                }
            // Ha nem devizás a szerződés, akkor viszont a tételeknél a deviza oszlopot nullázzuk
            // Ez arra az esetre jó, ha devizás volt a szerződés, és váltottuk sima Ft-ra, akkor a devzizás értékeket ürítjük
            } else {
                $sql = $adatbazis_kapcsolat->u('projektszerzodes_tetel', array('dev_varh_ert'), array(0), "`projektszerzodes_recid`='{$_GET['id']}'");
                if( $sql === false ) { $db_error = true; }
            }
            
            // Jótállás gyermektábla kialakítása
            $jotallas = pr_szerzodes_jotallas_beallit( $_GET['id'], $_POST['muszaki_gar_kezd'], $_POST['muszaki_gar_vege'] );
            if( $jotallas === false ) { $db_error = true; }
            
            // Illetve a létesítmény felelőst külön fel kell még venni, mert a tiltott mező miatt ez magától nem íródna be
            array_push($oszlopok, 'let_fel',        'pr_szam'           );
            array_push($ertekek, $_POST['let_fel'], $_POST['pr_szam']   );
        }
        
        // Szállítónál ellenőrizzük a mailcímek helyességét
        if( $tabla == 'szallito' ) {
            // Mailcímek helyességének ellenőrzése
            $hibauzenet = is_valid_mail_tobb($_POST['email'], 'hibaüzenettel');
            if( $hibauzenet ){
                echo json_encode(array(
                    'hiba'  => $hibauzenet,
                    'title' => 'Hiba',
                    'focus' => 'email',
                ));

                $weblap->leallit();
                exit();
            }
        }

        // Szeméyzet aktív/inaktív váltása a hozzá tartozó login-t is aktív/inaktiválja
        if( $tabla == 'torzsadat' ) {
            $login_nev = $adatbazis_kapcsolat->s2('felh_loginnev', $tabla, "`recid`='{$_GET['id']}'");
            if( $login_nev === false ) { $db_error = true; }
            if($login_nev){
                $sql = $adatbazis_kapcsolat->u('login', array('aktiv'), array($_POST['aktiv']), "`nev`='{$login_nev['felh_loginnev']}'");
                if( $sql === false ) { $db_error = true; }
            }
        }

		$id = $adatbazis_kapcsolat -> update($tabla, $oszlopok, $ertekek, "`recid`='{$_GET['id']}'");
        if( $id === false ) { $db_error = true; }
// echo $adatbazis_kapcsolat->gp() . '<br>';
// mysqli_commit($db_kapcs);
// exit;

        if( $id ) {
            $sor = $adatbazis_kapcsolat->s('*', $tabla, "`recid`='{$_GET['id']}'", '', '1');
            if( $sor === false ) { $db_error = true; }

            if( $sor ) {
                $_SESSION['formMod'] = array(
                    'datetime' => $sor[0]['update_dt'],
                    'oldal'    => basename(self())
                );

                foreach($tomb as $value) {
                    if( $value['type'] == 'autocomplete' ) {
                        $value['tabla'] = str_replace('_csakszint', '', $value['tabla']);
                        $kulso_ertek = $adatbazis_kapcsolat->s('recid nev', $value['tabla'], 'recid='.$sor[0][$value['name']], '', '1');
                        if( $kulso_ertek === false ) { $db_error = true; }
                        
                        $sor[0][$value['name'].'_autocomp'] = '';
                        if( $kulso_ertek ) {
                            $sor[0][$value['name'].'_autocomp'] = $kulso_ertek[0]['nev'];
                        }
                    }
                }

                echo json_encode(array(
                    'statusz' => 'siker',
                    'adatok'  => $sor[0]
                ));

            }
        }
        
        // Ha a projektszámla most került átadásra PSzK-ba, akkor generálunk egy XML-t
        if( $projektszamla_xml_gen ) {
            
            $prsz = $adatbazis_kapcsolat->s2('*', 'projektszamla', "`recid`='{$_GET['id']}'");
            $kolt_vis = '';
            switch( $prsz['kolt_vis'] ) {
                case '1': $kolt_vis = 'D'; break;
                case '3': $kolt_vis = 'S'; break;
                case '4': $kolt_vis = 'V'; break;
            }
            
            $xml = '<?xml version = "1.0" encoding="UTF-8" standalone="yes"?>	
<projszla>
    <onteamid>'.$prsz['recid'].'</onteamid>
    <erk_dat>'.str_replace('-', '', $prsz['erk_dt']).'</erk_dat>
    <szallkod>'.$prsz['telephely_id'].'</szallkod>
    <szlaszam>'.$prsz['szla_szam'].'</szlaszam>
    <pszam>'.$prsz['pr_szam'].'</pszam>
    <telj_dat>'.str_replace('-', '', $prsz['telj_dt']).'</telj_dat>
    <fizhat_dat>'.str_replace('-', '', $prsz['fizhat_dt']).'</fizhat_dat>
    <szallitoid>'.$prsz['szallito_id'].'</szallitoid>
    <targy><![CDATA['.htmlspecialchars_decode($prsz['targy']).']]></targy>
    <netert>'.$prsz['netto'].'</netert>
    <brutert>'.$prsz['brutto'].'</brutert>
    <atv_dat>'.str_replace('-', '', $prsz['p_atad_dt']).'</atv_dat>
    <iktszam>'.$prsz['ikt_szam'].'</iktszam>
    <mellszam>'.$prsz['mell_szam'].'</mellszam>
    <musz>'.$prsz['muszak_szam'].'</musz>
    <musz_dat>'.str_replace('-', '', $prsz['musz_dt']).'</musz_dat>
    <valnetert>'.$prsz['dev_net'].'</valnetert>
    <valarf>'.$prsz['arfolyam'].'</valarf>
    <valarf_dat>'.str_replace('-', '', $prsz['atvaltva_dt']).'</valarf_dat>
    <kcs_46>'.$kolt_vis.'</kcs_46>
    <kcs_51>'.$prsz['kcs_21'].'</kcs_51>
    <valbrutert>'.$prsz['dev_br'].'</valbrutert>
</projszla>
';
            // Biztonság kedvéért ellenőrizzük a könyvtár meglétét is
            if( !is_dir(get_pszk_utvonal()) ) {
                mkdir(get_pszk_utvonal());
            }
            fajlba_mentes(get_pszk_utvonal().time().'_'.rand(0, 100).'.xml', $xml);
        }
	}
    
    // Ha update volt, ha insert, projekt számla módosításakor a projekt számlázott összegét frissíteni kell
    // Update esetén ez két eset, hisz mi van, ha épp a pr_szam változott. Ezért a régit és az újat is újra kell számolni
    if( $tabla == 'projektszamla' || $tabla == 'projektszerzodes' ) {
        // A számla korábbi pr_szam-ával is lefuttatuk a karbantartást...
        if( projektkezeles_szamlazott_osszeg($pr_szam_projektkezeles_update_hez) === false ) {
            $db_error = true;
        }
        // ... meg az új pr_szam-al is.
        if( projektkezeles_szamlazott_osszeg($_POST['pr_szam']) === false ) {
            $db_error = true;
        }
    }
    
	if( isset($_GET['id']) && $type == 'update' ) {
        $id = $_GET['id'];
	}

    // Ha kell, akkor beállítjuk még a nevid-s oszlopot
	if( $nevid != '' && $type == 'insert' ) {

        $nevid = fajlnev_keszit($_POST[$nevid[name]]);
		if( !$nevid ) {
            $nevid = 'noname';
        }
        $nevid_tomb = array();
        $nevidk = $adatbazis_kapcsolat -> s('nevid', $tabla, '`nevid` LIKE \''.$nevid.'%\'');
        if( $nevidk === false ) { $db_error = true; }
        foreach ( $nevidk as $nev_id ) {
            array_push($nevid_tomb, $nev_id['nevid']);
        }

        while ( in_array($nevid, $nevid_tomb) ) {
            $nevid = fajlnev_sorszamoz($nevid);
        }

		$sql = $adatbazis_kapcsolat -> u($tabla, 'nevid', $nevid, 'id='.$id);
        if( $sql === false ) { $db_error = true; }
	}

    // Tranzakció kommitolása, vagy rollback-ezése
    if( !$db_error ) {
        mysqli_commit($db_kapcs);

        // Login jelszó küldést commit után küldünk
        if( $tabla == 'login' ) {
            // Ha most vették ki a Jelszóval rendelkezik pipát, akkor e-mail értesítés küldése
            if( $van_jelszo && !$_POST['van_jelszo'] ){
                level_felkeszit_es_kuld('login', $_GET['id'], '', 'elfelejtettJelszoKuld', 'elfelejtettJelszoKuld');
            }
        }

    } else {
        mysqli_rollback($db_kapcs);
    }

	return array('', $id);
}


/**
*	JS alapú hibaüzenetket generál egy tömb alapján (alert2)
*
*	@param array:  mely tomb alapján generáljon (az alert miatt lényeges)
*	@param string: mi legyen a form ellenőrző függvény neve?
*	@param bool:   lehet-e ajaxos vagy hagyományos?
*/
function js_kiir($tomb, $fg = 'form_ell_regi()', $ajax = false) {

    $hiba_txt = 'Hiba'; // Csak hogy egy helyen kelljen átírni.

    $ret = nl2('
                <'.str_replace('#', '', 's#cript').'>

                    var helyiseg_gep_mehet      = false;
                    var riasztas_mehet          = false;
                    var utemezes_mehet          = false;
                    var bejelentes_mehet        = false;
                    var bejelentes_mehet2       = false;
                    var alert_vegen             = false; /* Van, hogy mentés után még láttani akarunk egy alertet */
                    var igeny_rogzites_uzemmod  = false;
                    function '.$fg.'{

                        var volt_helytelen = false;
                        $(\'.datepicker, .datetimepicker\').each(function () {
                            if(valid_date($(this))===false){
                                volt_helytelen = true;
                            }
                        });
                        if(volt_helytelen){
                            return false;
                        }
            ');


    $param = '';
    foreach ( $tomb as $ertek ) {
        if( $ertek['type'] != 'statusz' && $ertek['type'] != 'info' ) {
            if( $ertek['name'] ) {

                // Csak akkor figyelünk lenti paramétereket, ha egyáltalán az elem létezik. Ezzel egy csomó JS hibát elekerülhetünk és debuggolós szívásokat
                $ret .= 'if($(\'#'.$ertek['name'].'\').length){ ';

                if( $ertek['alert'] ) {
                    $focus_autocomp = $ertek['name'];
                    if( $ertek['type'] == 'autocomplete' ) {
                        $focus_autocomp = $ertek['name'].'_autocomp';
                    }

                    $ret .= 'if($(\'#'.$ertek['name'].'\').val()==\'\'){ document.getElementById(\''.$focus_autocomp.'\').className += \' alert\'; $(\'#'.$focus_autocomp.'\').addClass(\'hibas_ide_focus\'); alert2(\''.$ertek['alert'].'\', \'\', \''.$hiba_txt.'\'); alert_utan_mehet_megsem_mutat_beallit(); return false; }';
                }

                // Ha numerikus a mező, akkor a 0,00 is üres értéknek számít, kivéve, ha megengedjük a 0-t mint menthető értéket (Pl. projekt számláknál van ilyen)
                if( strpos($ertek['class'], 'f_num') !== false && !$ertek['nulla_megengedett'] ) {
                    $ret .= 'if( $(\'#'.$ertek['name'].'\').val()==\'0,00\' && !$(\'#'.$ertek['name'].'\')[0].hasAttribute(\'readonly\') && !$(\'#'.$ertek['name'].'\')[0].hasAttribute(\'disabled\') ){ document.getElementById(\''.$ertek['name'].'\').className += \' alert\'; $(\'#'.$ertek['name'].'\').addClass(\'hibas_ide_focus\'); alert2(\''.$ertek['alert'].'\', \'\', \''.$hiba_txt.'\'); alert_utan_mehet_megsem_mutat_beallit(); return false; }';
                }

                // Ha dátum tipusú a mezők ellenőrzése
                if( strpos($ertek['class'], 'datepicker')!==false || strpos($ertek['class'], 'datetimepicker')!==false ){
                    $ret .= 'if( !valid_date( $(\'#'.$ertek['name'].'\') )===false ){ return false; }';
                }
                if( strpos($ertek['class'], 'ora_perc') !== false ) {
                    $ret .= 'if( valid_ido( $(\'#'.$ertek['name'].'\') )===false ){ return false; }';
                }

                // Ha dátum mező, akkor az ürességet másképp figyeljük
                if( $ertek['alert'] && (strpos($ertek['class'], 'datepicker') !== false || strpos($ertek['class'], 'datetimepicker') !== false) ) {
                    $ret .= 'if(document.getElementById(\''.$ertek['name'].'\').value.substring(0,1)==\'\xa0\'){ document.getElementById(\''.$ertek['name'].'\').className += \' alert\'; $(\'#'.$ertek['name'].'\').addClass(\'hibas_ide_focus\'); alert2(\''.$ertek['alert'].'\', \'\', \''.$hiba_txt.'\'); alert_utan_mehet_megsem_mutat_beallit(); return false; } ';
                }

                // Pár mezőnél a 0 sem elfogadható érték
                if( $ertek['alert'] && ($ertek['name'] == 'szemelyzet_id' || $ertek['nulla_is_ures_adat']) ) {
                    $ret .= 'if(document.getElementById(\''.$ertek['name'].'\').value==\'0\'){ document.getElementById(\''.$ertek['name'].'\').className += \' alert\'; $(\'#'.$ertek['name'].'\').addClass(\'hibas_ide_focus\'); alert2(\''.$ertek['alert'].'\', \'\', \''.$hiba_txt.'\'); alert_utan_mehet_megsem_mutat_beallit(); return false; } ';
                }

                if( $ertek['mail'] ) {
                    $ret .= "str=document.getElementById('$ertek[name]').value.trim(); if(str){ filter=/^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i; if (!filter.test(str)){ document.getElementById('{$ertek['name']}').className='alert'; $('#'{$ertek['name']}').addClass(\'hibas_ide_focus\'); alert2('Helytelen e-mail cím!', \'\', \''.$hiba_txt.'\'); alert_utan_mehet_megsem_mutat_beallit(); return false; } }";
                }

                if( $ertek['name'] == 'jelsz2' ) {
                    $ret .= 'if(document.getElementById(\'reg_jelsz\').value.length < 4 ){document.getElementById(\''.$ertek['name'].'\').className += \' alert\';alert2(\'A jelszó hossza minimum 4 karakter legyen!\', \'\', \''.$hiba_txt.'\'); alert_utan_mehet_megsem_mutat_beallit(); return false;}';
                    $ret .= 'if(document.getElementById(\'jelsz2\').value != document.getElementById(\'reg_jelsz\').value){document.getElementById(\''.$ertek['name'].'\').className += \' alert\'; alert2(\'A jelszavak nem egyeznek meg!\', \'\', \''.$hiba_txt.'\'); alert_utan_mehet_megsem_mutat_beallit(); return false;}';
                }

                if( $ertek['name'] == 'isz' && $ertek['alert'] ) {
                    $ret .= 'if(document.getElementById(\'isz\').value.length != 4){document.getElementById(\'isz\').className += \' alert\';alert2(\'Helytelen irányítószám!\', \'\', \''.$hiba_txt.'\'); return false;}';
                }

                if( $ertek['name'] == 'sz_isz' && $ertek['alert'] ) {
                    $ret .= 'if(document.getElementById(\''.$ertek['name'].'\').value.length != 4){document.getElementById(\''.$ertek['name'].'\').className += \' alert\';alert2(\'Helytelen irányítószám!\', \'\', \''.$hiba_txt.'\'); alert_utan_mehet_megsem_mutat_beallit(); return false;}';
                }

                if( $ertek['name'] == 'szall_isz' && $ertek['alert'] ) {
                    $ret .= 'if(document.getElementById(\''.$ertek['name'].'\').value.length != 4){document.getElementById(\''.$ertek['name'].'\').className += \' alert\';alert2(\'Helytelen irányítószám!\', \'\', \''.$hiba_txt.'\'); alert_utan_mehet_megsem_mutat_beallit(); return false;}';
                }

                if( $ertek['name'] == 'elf' ) {
                    $ret .= 'if(document.getElementById(\''.$ertek['name'].'\').checked==false){document.getElementById(\''.$ertek['name'].'\').className += \' alert\'; alert2(\'A feltételek elfogadása kötelező!\', \'\', \''.$hiba_txt.'\'); alert_utan_mehet_megsem_mutat_beallit(); return false;}';
                }

                if( $ertek['name'] == 'akcar' ) { //Ha van akciós ár, akkor figyelük azt is, hogy kissebb legyen
                                            //Először ki kell szűrni a pontokat belőle
                    $ret .= "var ar=document.getElementById('ar').value; var akcar=document.getElementById('akcar').value; mit=new RegExp('[.]','ig'); ar=ar.replace(mit,''); akcar=akcar.replace(mit,''); if(Number(ar)<=Number(akcar)){ document.getElementById('".$ertek['name']."').className += ' alert'; alert2('Az akciós ár nem kedvezőbb a listaártól!', '', ".$hiba_txt."'); $(\'.bezar\').attr(\'onclick\', \'alert2(); szurkit_zar(\\\'Mehet-mégsem mutat\\\');\'); return false; }";
                }

                $ret .= '}';
            }

            if( $ajax && !strstr($ertek['type'], 'file')/* tehát nem jsfile vagy sima file... */ && $ertek['type'] != 'nosql' ) {
                // Ha van neve, nem HTML pl.
                if( $ertek['name'] ) {
                    if( $ertek['type'] == 'checkbox' ) {
                        $param .= $ertek['name'].'=\'+$(\'#'.$ertek['name'].'\').is(\':checked\')+\'&amp;';
                    }  else {
                        $param .= $ertek['name'].'=\'+$(\'#'.$ertek['name'].'\').val().replace(/&/gi, \'|ES|\')+\'&amp;';
                    }
                }
            }
        }
    }

    
    // Update_dt is kerüljön bele
    $param .= 'update_dt=\'+$(\'#update_dt\').val()+\'&amp;';


    // Egyedi hibaüzenetek
    if( basename(self())=='anyagigenyles.php' ) {
        $ret .= nl2('

            if( !$(\'#nev\').val() ){
                alert2(\'Kérem válasszon ki egy cikknevet az értéklistából!\', \'\', \'Hiba\');
                alert_utan_mehet_megsem_mutat_beallit();
                return false;
            }
            
            if( $(\'#igmen\').val().replace(\',\', \'.\')<0  ){
                alert2(\'Az igényelt mennyiség nem lehet kisebb vagy egyenlő, mint 0!\', \'\', \'Hiba\');
                alert_utan_mehet_megsem_mutat_beallit();
                $(\'#igmen\').addClass(\'hibas_ide_focus\');
                return false;
            }

            anyagig_id = \'#th\'+$(\'#telephely_id\').val()+\'a\'+$(\'#nev\').val();
            min_mennyiseg = $(anyagig_id).html();
            mee           = $(anyagig_id+\'_mee\').html();
            gomb          = \'<input type="button" class="gomb zold" value="Igen" onclick="$(\\\'#igmen\\\').val(min_mennyiseg); $(\\\'#szerk_form\\\').submit();"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">\';
            if(min_mennyiseg && min_mennyiseg>$(\'#igmen\').val()){
                alert2(\'Igényelt mennyiség: \' + $(\'#igmen\').val() + \' \'+mee+\'<br>Minimális igényelhető mennyiség: \' +min_mennyiseg+\' \'+mee+\'<br>Ha folytatja, \' +min_mennyiseg+\' \'+mee+\' lesz igényelve.<br>Folytatja?\', gomb, \'Hiba\');
                return false;
            }

        ');
    }

    // Új bejelentéskor egy popup feljön, hogy milyen folyamatban levő bejelentések vannak
    if( basename(self())=='bejelentes.php' ) {

        $mod = 'false';
        $get_id = '';
        if( isset($_GET['modosit']) ) {
            $mod    = 'true';
            $get_id = '&amp;recid=\' + '.$_GET['id'].' + \'';
        }

        $ret .= nl2('
            if( !bejelentes_mehet ) {

                var ellenorzes = $.ajax({
                    url  : \'bejelentes.php\',
                    type : \'GET\',
                    async: false,
                    data : esamp2es(\'telephely_id=\' + $(\'#telephely_id\').val() + \'&amp;obj_id=\' + $(\'#obj_id\').val() + \'&amp;mod=\' + '.$mod.' + \''.$get_id.'&amp;folyamatban_munkak=\' + new Date().getTime()),
                    success: function(response) {

                        response = JSON.parse(response);
                        if( response.hiba ) {
                            alert2(response.hiba, response.gomb, response.title);
                            return false;
                        }
                        if( response.siker ) {
                            szurkit();
                            bejelentes_mehet = true;
                            $(\'#szerk_form\').submit();
                        }

                    },
                    complete: function(xhr) {
                        if( xhr.status != 200 ) {
                            alert2(xhr.responseText, \'\', \'Hiba\');
                        }
                    }
                });

                return false;
            }
        ');
    }

    // Személyzet -> Beosztás -> Naptár fül alatt nem a hagyományos form feldogozás törétnik majd, hanem egy beosztas_naptarba_tesz() FGV-t hívunk meg
    if( basename(self())=='beosztas.php' && isset($_GET['naptar']) ) {
        $ret .= nl2('
            if( $(\'#beosztas\').val()==1 && !$(\'#szerepkor\').val() ) {
                alert2(\'Kérem adja meg a szerepkört!\', \'\', \'Hiba\');
                return false;
            }

            tol = $(\'#tol\').val().substring(0, 13).replace(/\./g, \'-\');
            ig  = $(\'#ig\').val().substring(0, 13).replace(/\./g, \'-\');
            idoszak_tol = $(\'#idoszak_tol\').val();
            idoszak_ig  = $(\'#idoszak_ig\').val();
            if(idoszak_tol > tol){
                alert2(\'A kezdődátum nem lehet korábbi mint az adatlap időszaka!\', \'\', \''.$hiba_txt.'\');
                return false;
            }
            if(idoszak_ig < tol){
                alert2(\'A kezdődátum nem lehet későbbi mint \'+idoszak_ig+\'!\', \'\', \''.$hiba_txt.'\');
                return false;
            }
            if(idoszak_ig < ig){
                alert2(\'A végdátum nem lehet későbbi mint \'+idoszak_ig+\'!\', \'\', \''.$hiba_txt.'\');
                return false;
            }
            if(idoszak_tol > ig){
                alert2(\'A végdátum nem lehet korábbi mint \'+idoszak_tol+\'!\', \'\', \''.$hiba_txt.'\');
                return false;
            }
            if(tol >= ig){
                alert2(\'A végdátum nem lehet korábbi mint a kezdődátum!\'+tol+\'-\'+ig, \'\', \''.$hiba_txt.'\');
                return false;
            }

            beosztas_naptarba_tesz(\''.$_GET['id'].'\');
            return false;
        ');
    }

    // Költséghely és költségnem esetén a foglalt id ellenőrzése
    if( basename(self())=='ktghely.php' || basename(self())=='ktgnem.php' ){

        $ret .= nl2('
            if($(\'#id\').hasClass(\'foglalt_id\')){
                alert2($(\'#id\').val()+\' - \'+$(\'#nev\').val()+\'<br>Ez a kód már használatban van!\', \'\', \''.$hiba_txt.'\');
                return false;
            }
        ');
    }

    // Új felhasználó feltöltésekor a szállodát is ki kell választani
    if( basename(self())=='login.php' && isset($_GET['uj']) ){
        $ret .= nl2('
            if( !$(\'#szallodak_hidden\').val() || $(\'#szallodak_hidden\').val()==\'{}\' ){
                alert2(\'Mentés előtt válasszon szállodát!\', \'<input type="button" class="gomb" value="Ok" onclick="kapcsolodoSzerkeszt(\\\'szallodak\\\'); alert2();">\', \'Hiba\');
                $(\'#mehet_gomb\').removeAttr(\'disabled\').addClass(\'gombb_alert\');
                $(\'#megsem_gomb\').removeAttr(\'disabled\');
                return false;
            }
        ');
    }

    // Ha épp most vettük ki a pipát a "Jelszóval rendelkezik" mezőből, akkor ellenőrizzük, hogy van-e egyáltalán mailcím.
    if( basename(self())=='login.php' && isset($_GET['modosit']) ){
        $ret .= nl2('
            if( !nincs_mailcim_mehet && $(\'#van_jelszo\').prop(\'checked\')==false && $(\'#van_jelszo\').hasClass(\'mod\') && !$(\'#usermail\').val() ){
                alert2( $(\'#teljes_nev\').val()+\' felhasználónak nincs e-mail címe,<br>'.get_nev().' nem tud értesítő emailt küldeni új jelszóról.<br><br>Folytatja?\', \'<input type="button" class="gomb zold" value="Igen" onclick="nincs_mailcim_mehet = true; $(\\\'#szerk_form\\\').submit();"> <input type="button" class="gomb piros" value="Nem" onclick="alert2();">\');
                return false;
            }
        ');
    }

    // Ütemezett feladatok ellenőrzése
    if( basename(self())=='utemezett_feladatok.php' || basename(self())=='rendszeres_karbantartas.php' ){

        $param .= 'hetkoz_kent=\'+$(\'#hetkoz_kent\').is(\':checked\')+\'&amp;';
        $param .= 'aktkezd_d=\'+$(\'#aktkezd_d\').val().replace(/&/gi, \'|ES|\')+\'&amp;';
        $param .= 'aktvege_d=\'+$(\'#aktvege_d\').val().replace(/&/gi, \'|ES|\')+\'&amp;';
        $param .= 'ism_het=\'+$(\'#ism_het\').val().replace(/&/gi, \'|ES|\')+\'&amp;';
        $param .= 'kezdho=\'+$(\'#kezdho\').val().replace(/&/gi, \'|ES|\')+\'&amp;';
        $param .= 'kezdnap=\'+$(\'#kezdnap\').val().replace(/&/gi, \'|ES|\')+\'&amp;';
        $param .= 'vegeho=\'+$(\'#vegeho\').val().replace(/&/gi, \'|ES|\')+\'&amp;';
        $param .= 'vegenap=\'+$(\'#vegenap\').val().replace(/&/gi, \'|ES|\')+\'&amp;';
        $param .= 'h=\'+$(\'#h\') .is(\':checked\')+\'&amp;';
        $param .= 'k=\'+$(\'#k\') .is(\':checked\')+\'&amp;';
        $param .= 's=\'+$(\'#s\') .is(\':checked\')+\'&amp;';
        $param .= 'cs=\'+$(\'#cs\').is(\':checked\')+\'&amp;';
        $param .= 'p=\'+$(\'#p\') .is(\':checked\')+\'&amp;';
        $param .= 'sz=\'+$(\'#sz\').is(\':checked\')+\'&amp;';
        $param .= 'v=\'+$(\'#v\') .is(\':checked\')+\'&amp;';
        $ret .= nl2('

                /* Hozzárendelt személy ha külsős, akkor a Külső kivitelező kitöltése kötelező */
                if( $(\'#szemelyzet_id\').val()=='.KULSO_KIVITELEZO.' ) {
                    if( !$(\'#szallito_id\').val() ) {
                        alert2(\'Kérem adja meg a külső kivitelezőt!\', \'\', \'Hiba\');
                        $(\'#szallito_id_autocomp\').addClass(\'hibas_ide_focus\');
                        return false;
                    }
                }
                /* Kötelező mezők hibaüzenetei */
                if($(\'#cikltip\').val()==\'d\'){
                    if( !$(\'#ciklnap\').val() && $(\'#hetkoz_kent\').prop(\'checked\')==false ){
                        alert2(\'Kérem állítsa be a kívánt ütemezést!\', \'\', \'Hiba\');
                        $(\'#ciklnap\').addClass(\'hibas_ide_focus\');
                        return false;
                    }
                }
                if($(\'#cikltip\').val()==\'w\'){
                    if( !$(\'#ism_het\').val() || (!$(\'#h\').prop(\'checked\') && !$(\'#k\').prop(\'checked\') && !$(\'#s\').prop(\'checked\') && !$(\'#cs\').prop(\'checked\') && !$(\'#p\').prop(\'checked\') && !$(\'#sz\').prop(\'checked\') && !$(\'#v\').prop(\'checked\')) ){
                        alert2(\'Kérem állítsa be a kívánt ütemezést!\', \'\', \'Hiba\');
                        $(\'#ism_het\').addClass(\'hibas_ide_focus\');
                        return false;
                    }
                }
                if($(\'#cikltip\').val()==\'m\'){
                    if( !$(\'#ciklho\').val() && !$(\'#ciklho2\').val() ){
                        alert2(\'Kérem állítsa be a kívánt ütemezést!\', \'\', \'Hiba\');
                        $(\'#ciklho\').addClass(\'hibas_ide_focus\');
                        return false;
                    }
                }

                /* Havi ismétlődések esetei */
                if( $(\'#ciklho\').val()>0 && !$(\'#honapjan\').val() ){
                    alert2(\'Kérem adja meg a hónap napját!\', \'\', \'Hiba\');
                    $(\'#honapjan\').addClass(\'alert\');
                    $(\'#honapjan\').addClass(\'hibas_ide_focus\');
                    return false;
                }
                if( $(\'#honapjan\').val() && !$(\'#ciklho\').val() ){
                    alert2(\'Kérem adja meg a havi ismétlődés gyakoriságát!\', \'\', \'Hiba\');
                    $(\'#ciklho\').addClass(\'alert\');
                    $(\'#ciklho\').addClass(\'hibas_ide_focus\');
                    return false;
                }

                if( $(\'#ciklho2\').val()>0 && !$(\'#ciklhet\').val() ){
                    alert2(\'Kérem adja meg a rendszerességet!\', \'\', \'Hiba\');
                    $(\'#ciklhet\').addClass(\'alert\');
                    $(\'#ciklhet\').addClass(\'hibas_ide_focus\');
                    return false;
                }
                if( $(\'#ciklho2\').val()>0 && !$(\'#ho_rendsz_het\').val() ){
                    alert2(\'Kérem adja meg a rendszerességet!\', \'\', \'Hiba\');
                    $(\'#ho_rendsz_het\').addClass(\'alert\');
                    $(\'#ho_rendsz_het\').addClass(\'hibas_ide_focus\');
                    return false;
                }
                if( ( $(\'#ciklhet\').val()>0 || $(\'#ho_rendsz_het\').val() ) && !$(\'#ciklho2\').val() ){
                    alert2(\'Kérem adja meg az ismétlődés gyakoriságát!\'+$(\'#ho_rendsz_het\').val(), \'\', \'Hiba\');
                    $(\'#ciklho2\').addClass(\'alert\');
                    $(\'#ciklho2\').addClass(\'hibas_ide_focus\');
                    return false;
                }


                /* Gép és helyiség is üresen maradt figyelmeztetés */
                if(!$(\'#gep_id\').val() && !$(\'#obj_id\').val() && !helyiseg_gep_mehet){
                    uf_gomb = \'<div class="c"><input type="button" class="gomb zold" value="Igen" onclick="helyiseg_gep_mehet = true; $(\\\'#szerk_form\\\').submit();"> <input type="button" class="gomb piros" value="Nem" onclick="alert2(); szurkit_zar(\\\'Mehet_mégsem_mutat\\\');"></div>\';
                    alert2(\'Nem adott meg sem helyiséget sem gépet a feladathoz.<br>Folytatja?\', uf_gomb);
                    return false;
                }

                /*  Szokásos ütemezés ellenőrzése */
                ev = new Date();
                if( $(\'#kezdho\').val() && !Date.parse( ev.getFullYear()+\'-\'+$(\'#kezdho\').val()+\'-\'+$(\'#kezdnap\').val()) ){
                    alert2(\'Helytelen szokásos ütemezés kezdődátum!\', \'\', \'Hiba\');
                    $(\'#kezdnap\').addClass(\'hibas_ide_focus\');
                    $(\'#kezdnap\').val(\'1\');
                    return false;
                }
                if( $(\'#vegeho\').val() && !Date.parse( ev.getFullYear()+\'-\'+$(\'#vegeho\').val()+\'-\'+$(\'#vegenap\').val()) ){
                    alert2(\'Helytelen szokásos ütemezés végdátum!\', \'\', \'Hiba\');
                    $(\'#vegenap\').addClass(\'hibas_ide_focus\');
                    $(\'#vegenap\').val(\'1\');
                    return false;
                }

                /* Időszakos ütemezés ha ki van pipálva, akkor a szokásos mezők kitöltése kötelező */
                if( $(\'#idoszakos\').prop(\'checked\') ){
                    if( !$(\'#kezdho\').val() || !$(\'#kezdnap\').val() || $(\'#kezdnap\').val()==\'0\' || !$(\'#vegeho\').val() || !$(\'#vegenap\').val() || $(\'#vegenap\').val()==\'0\' ){
                        alert2(\'Ha az időszakos ütemezést bekapcsolta, akkor a szokásos időpontok kitöltése kötelező!\', \'\', \'Hiba\');
                        return false;
                    }
                }

                /* Riasztás 0 értékkel van beállítva, így mehet? */
                if( (!$(\'#riaszt\').val() || $(\'#riaszt\').val()==0) && !riasztas_mehet ){
                    uf_gomb2 = \'<div class="c"><input type="button" class="gomb zold" value="Igen" onclick="riasztas_mehet = true; $(\\\'#szerk_form\\\').submit();"> <input type="button" class="gomb piros" value="Nem" onclick="alert2(); szurkit_zar(\\\'mentés mégsem gomb megjelenít\\\');"></div>\';
                    alert2(\'
                        Ön a következőt választotta:
                        <br>
                        <br><b class="piros">Riasztás 0 nappal az esedékesség előtt.</b>
                        <br>
                        <br>Ha folytatja, az esedékesség napján készül belőle munkalap!
                        <br>
                        <br>Folytatja?
                    \', uf_gomb2);
                    return false;
                }

                /* Dátumra ütemezés közlése mentés előtt. */
                if( !utemezes_mehet ){

                    /* Ciklus szorzó kiszámítása */
                    ciklszorz_val = 0;
                    if( $(\'#ciklnap\').val()>0 ){ ciklszorz_val = $(\'#ciklnap\').val(); }
                    if( $(\'#ciklho\').val()>0 ) { ciklszorz_val = $(\'#ciklho\').val();  }
                    if( $(\'#ciklho2\').val()>0 ){ ciklszorz_val = $(\'#ciklho2\').val(); }
                    if( $(\'#ism_het\').val()>0 ){ ciklszorz_val = $(\'#ism_het\').val(); }

                    /* Időszakos pipa lekezelése */
                    idoszakos_val = 0;
                    if($(\'#idoszakos\').prop(\'checked\')){
                        idoszakos_val = 1;
                    }

                    /* Ciklustítpus beállítása */
                    ciklaltip_val = \'\';
                    if($(\'#ciklnap\').val()>0){
                        ciklaltip_val = 1;
                    }
                    if($(\'#hetkoz_kent\').prop(\'checked\')){
                        ciklaltip_val = 2;
                    }
                    if($(\'#ciklho\').val()>0){
                        ciklaltip_val = 1;
                    }
                    if( $(\'#ciklho2\').val()>0 ){
                        ciklaltip_val = 2;
                    }

                    /* Alapból a mező értéke, de a későbbi feltételeknél ez felülíródhat még */
                    ciklnap_val = $(\'#ciklnap\').val();
                    ciklho_val  = $(\'#ciklho\').val();
                    if($(\'#ciklho2\').val()>0){
                        ciklnap_val = $(\'#ho_rendsz_het\').val();
                        ciklho_val  = $(\'#ciklho2\').val();
                    }

                    /* Havi ciklus esetén */
                    if( $(\'#ciklho\').val()>0 ){
                        ciklnap_val = $(\'#honapjan\').val();
                    }

                    /* Ha van heti rendszerességű ismétlés, akkor a cikl_nap-ot be kell állítani */
                    if( $(\'#ism_het\').val()>0 ){
                        ciklnap_val = \'\';
                        if($(\'#h\').prop(\'checked\')) { ciklnap_val += \'1\'; }
                        if($(\'#k\').prop(\'checked\')) { ciklnap_val += \'2\'; }
                        if($(\'#s\').prop(\'checked\')) { ciklnap_val += \'3\'; }
                        if($(\'#cs\').prop(\'checked\')){ ciklnap_val += \'4\'; }
                        if($(\'#p\').prop(\'checked\')) { ciklnap_val += \'5\'; }
                        if($(\'#sz\').prop(\'checked\')){ ciklnap_val += \'6\'; }
                        if($(\'#v\').prop(\'checked\')) { ciklnap_val += \'7\'; }
                    }


                    $.ajax({
                        url : \''.self().'\',
                        type: \'GET\',
                        data: {
                            id              : \''.$_GET['id'].'\',
                            ciklszorz_      : ciklszorz_val,
                            esnap_d         : $(\'#esnap_d\').val(),
                            aktkezd_d       : $(\'#aktkezd_d\').val(),
                            aktvege_d       : $(\'#aktvege_d\').val(),
                            cikltip         : $(\'#cikltip\').val(),
                            ciklaltip       : ciklaltip_val,
                            ciklszorz       : $(\'#ciklszorz\').val(),
                            ciklho          : ciklho_val,
                            ciklhet         : $(\'#ciklhet\').val(),
                            ciklnap         : ciklnap_val,
                            idoszakos       : idoszakos_val,
                            kezdho          : $(\'#kezdho\').val(),
                            kezdnap         : $(\'#kezdnap\').val(),
                            vegeho          : $(\'#vegeho\').val(),
                            vegenap         : $(\'#vegenap\').val(),
                            datumra_utemezes: new Date().getTime()
                        },
                        success: function(response) {

                            response = JSON.parse(response);
                            eredeti_esnap_d = $(\'#esnap_d\').val();
                            uf_gomb3 = \'<div class="c"><input type="button" class="gomb zold" value="Igen" onclick="utemezes_mehet = true; $(\\\'#szerk_form\\\').submit();"> <input type="button" class="gomb piros" value="Nem" onclick="alert2(); szurkit_zar(\\\'mentés mégsem gomb megjelenít\\\'); $(\\\'#esnap_d\\\').val(eredeti_esnap_d);"></div>\';
                            alert2(response[\'uzenet\'], uf_gomb3);
                            $(\'#esnap_d\').val(response[\'esedekes\']);
                            return false;

                        },
                        complete: function(xhr) {
                            if( xhr.status != 200 ) {
                                alert2(xhr.responseText, \'\', \'Hiba\');
                            }
                        }
                    });
                    return false;
                } else {
                    alert2();
                }

        ');
    }

    // Projektigénylésnél váltás cash-flow fülre, ha azon voltunk
    $cash_flow_fulre_lepes = '';
    if( basename(self())=='projektigenyles.php' ){
        $cash_flow_fulre_lepes = nl2('
                    if($(\'#ful_cash_flow\').hasClass(\'active\')){
                        $.when( betoltott ).done(function() {
                            cash_flow_fulre_lep();
                        });
                    }
                ');
    }

    // Ajaxos form módosítás
    $js_kieg = '';

    // Felmérési terv esetén meg kell hívni még egy JS függvényt, ami a bal oldali navpanelt frissíti
    $konyvtarszerkezetesek = array('felmeresi_terv.php');
    $akt_php = basename(self());
    if( in_array($akt_php, $konyvtarszerkezetesek) ){
        $is_obj = 'false';
        if( isset($_GET['obj']) ) {
            $is_obj = 'true';
        }

        $js_kieg = 'bal_panel_frissit(\''.$akt_php.'\', \''.$_GET['id'].'\', '.$is_obj.');';
    }

    $obj_szerk = '';
    $tab = 'modosit';
    if( basename(self()) == 'eszkozkezeles.php' && isset($_GET['obj']) ) {
        $obj_szerk = 'param_kieg += \'&amp;obj=1\';';
        $tab = 'obj';
    }

    $ajax_kod = nl2('

        utemezes_mehet = false;
        param_kieg = \'\';
        if(igeny_rogzites_uzemmod){
            param_kieg = \'&amp;igeny_rogzites=1\';
        }
        '.$obj_szerk.'

        $.ajax({
            url     : \''.self().'?id='.$_GET['id'].'\',
            type    : \'POST\',
            data    : esamp2es(\''.$param.str_replace('.php', '', basename(self())).'lista=1&amp;szerkeszt=1\'+param_kieg+\'&amp;ajax_form_modosit=\' + new Date().getTime()),
            success : function(response) {

                if( response ) {
                    response = JSON.parse(response);
                    if( response.hiba ) {
                        if( response.gomb ) {
                            alert2(response.hiba, response.gomb, response.title);
                        } else {
                            alert2(response.hiba, \'\', \''.$hiba_txt.'\');
                        }
                        
                        /* Űrlap módosításakor ha hibát kapunk, az mindig hibaüzenet. Ekkor a mehet-mégsem gombokat újra meg kell mutatni. */
                        alert_utan_mehet_megsem_mutat_beallit();
                        
                        if( response.focus ) {
                            $(\'#\'+response.focus).addClass(\'hibas_ide_focus\');
                        }
                        if( response.select ) {
                            $(\'#\'+response.select).select();
                        }

                        return false;
                    } else {
                        alert2();
                    }

                    '.$js_kieg.'

                    betoltott = szamlaBetolt( \''.$_GET['id'].'\', \''.basename(self()).'\', \''.$tab.'\', \'\', \'Elhagy kérdés nélkül\' );

                    '.$cash_flow_fulre_lepes.'

                    /* Ha lefutott az ajax, akkor még lehet dobni szeretnénk egy hibaüzenetet, vagy tájékoztatást */
                    alert2(alert_vegen);

                    formAdatok = response.adatok;

                    gombokNullaz();
                    formModositva = false;

                    $(\'#mehet_gomb\').removeClass(\'gombb_alert\').attr(\'disabled\', true);
                }
            }
        });
    ');

    if( $ajax ) {
        $ret .= nl2('
                ;

                '.$ajax_kod.';
                return false;
            }
            ');
    } else {
        $ret .= nl2('
                ;
                formModositva = false;
                return true;
            }');
    }

    $ret .= nl2('
            $(document).ready(function() {
                $(\'form input[type="checkbox"]\').click(function() {
                    $(\'#szerk_form input[type="submit"]\').addClass(\'gombb_alert\');
                });

                $(\'form select\').change(function() {
                    $(\'#szerk_form input[type="submit"]\').addClass(\'gombb_alert\');
                });
            });</script>
        ');

	return $ret;
}


function get_termek_thumb($id, $sablon, $hivatkozott_oldal, $id_oszlop, $id_get_neve, $tabla, $netto_aras){

    global $adatbazis_kapcsolat;

    $termek = $adatbazis_kapcsolat->s2('id nev kep text ar akcar', $tabla, "`$id_oszlop`='$id'");

    $kat = $adatbazis_kapcsolat->s2('nev', 'kat', "`id`='{$termek['kat']}'");

    //Link
    $link = $hivatkozott_oldal.'?'.fajlnev_keszit($kat['nev'].' '.$termek['nev']).'&amp;'.$id_get_neve.'='.$termek[$id_oszlop];

    /*//Többképes verzió
    $kepek = $adatbazis_kapcsolat -> select('id, kepnev', 'kepek', 'termek_id='.$termek['id'], 'sor');
    $kep   = $kepek[0];
    $termek['kep'] = $kep['kepnev'];*/

    //Kép
    if($termek['kep'] && is_file(get_utvonal().$termek['kep'])){
        $kep_obj =  new Img(get_utvonal().$termek['kep'], $termek['nev'], get_indexkep_sz(), get_indexkep_m());
    } else {
        $kep_obj = new Img(get_utvonal().'nincskep.jpg', $termek['nev']);
    }
    $fokep     = $kep_obj -> getHtml();

    $intro = nl2br2( resz_string($termek['text'], 200) );
    //$intro = nl2(resz_string( strip_tags( htmlspecialchars_decode2( $termek['text'] ) ), 200) ); <= Webes szövegszerkesztő esetére
    //$intro = nl2(resz_string( strip_tags( string2html( $termek['text'] ) ), 200) );              <= Wikipedia szerű formázáshoz

    //Ár, akciós ár
    if($netto_aras){
        $netto_ar  = $termek['ar'];
        $brutto_ar = $termek['ar']*((100+get_afa())/100);
        if($termek['akcar']){
            $netto_akcar  = $termek['akcar'];
            $brutto_akcar = $termek['akcar']*((100+get_afa())/100);
        }
    } else {
        $netto_ar  = $termek['ar']/((100+get_afa())/100);
        $brutto_ar = $termek['ar'];
        if($termek['akcar']){
            $netto_akcar  = $termek['akcar']/((100+get_afa())/100);
            $brutto_akcar = $termek['akcar'];
        }
    }

    if($termek['akcar']>0){
        $ar       = '<p class="listaar">Listaár: '.number_format($brutto_ar, 0, '', '.').' '.get_penznem().' <span class="afa">('.number_format($netto_ar, 0, '', '.').' '.get_penznem().' + '.get_afa().'% ÁFA)</span></p>';
        $akcar    = '<p class="akcar">Akciós ár: <span class="ar">'.number_format($brutto_akcar, 0, '', '.').' '.get_penznem().'</span> <span class="afa">('.number_format($netto_akcar, 0, '', '.').' '.get_penznem().' + '.get_afa().'% ÁFA)</span></p>';
    } else {
        $ar    = '';
        $akcar = '<p class="akcar">Listaár: <span class="ar">'.number_format($brutto_ar, 0, '', '.').' '.get_penznem().'</span> <span class="afa">('.number_format($netto_ar, 0, '', '.').' '.get_penznem().' + '.get_afa().'% ÁFA)</span></p>';
    }

    $bovebben = '<a href="'.$link.'">Bővebben</a>';

    $kosarba  = '';

    //Kiíratás
    $mit    = array('{ID}', /* A sablonváltozók és adatok legyenek egymás alatt */  '{LINK}', '{KEP}', '{NEV}',        '{INTRO}',  '{AR}', '{AKCAR}', '{KOSARBA}');
    $mivel = array($termek['id']/*itt a rendes id kell pl. a kosár miatt*/,         $link,    $fokep,  $termek['nev'], $intro,     $ar,    $akcar,    $kosarba);
    echo str_replace($mit, $mivel, $sablon);

}

/**
*	Web FTP
*	FONTOS: ha képet töltünk fel, magától odateszi a kiterjesztést (.jpg .gif .png).
*	Átlagos fájlok esetén adjuk meg a kiterjesztést is a $kepnev-ben
*
*	@param string $valtozo kötelező megadni, ez lesz a változó neve (űrlap neve)
*	@param string $fajlneve kötelező megadni, ez lesz a feltöltött fájl neve
*	@param integer $size méret limit, ami alapból 400 Kb
*	@param boolean $overwrite az eredeti fájlt felülírjuk-e ha már létezett ilyen nevű, vagy készítünk egy újat (pl: kep2.jpg)
*	@param boolean $csakkep ez alapból igaz, tehát csak jpg, png és gifet enged feltölteni
*	@param string $tabla adatbázis tábla neve. Kötelező megadni ha a $sor_id nem null
*	@param string $oszlop adatbázis oszlop neve. Kötelező megadni ha a $sor_id nem null
*	@param string $sor_id adatbázis tábla egy soridje. Ha ez adott, akkor SQL update-ja a tábla adott oszlopát, mivel ilyen esetben a fájl útvonala letárolt
*
*	@return array 0. argumentuma a kép neve, 1. pedig a hibaüzenet (-1 ha sikeres)
*	k1: túllépte a limitet
*	k2: nem képtipusú fájl
*	k3: ilyen nevű fájl már létezett a szerveren és törölni nem lehetett
*	k4: nem sikerül felmásolni
*/
function web_ftp($valtozo, $kepnev, $size=409600, $overwrite=false, $csakkep=true, $tabla=null, $oszlop=null, $sor_id=null, $flashfeltoltes){

    global $adatbazis_kapcsolat;
    global $db_kapcs;

    $kepnev = str_replace('//', '/', $kepnev);

    // Hogy ne csak admin alól tudjuk használni
	if(basename( dirname( self() ) ).'/' == get_admin() ){
        $utvonal = '../'.get_utvonal();
    } else {
        $utvonal = get_utvonal();
    }

    // Ha túllépi a méretlimitet: hiba1
	if($_FILES[$valtozo]['size'] > $size){
		return array($kepnev, 'k1');
	}

	$tiltott_fajlok = array('text/x-js', 'text/css', 'text/html', 'application/xhtml+xml', 'text/xml');
	// Ha csakképet engedünk, és nem képformátumú: hiba2
	if($csakkep && !$flashfeltoltes && !($_FILES[$valtozo]['type']=='image/pjpeg' || $_FILES[$valtozo]['type']=='image/jpeg' || $_FILES[$valtozo]['type']=='image/gif' || $_FILES[$valtozo]['type']=='image/png' || $_FILES[$valtozo]['type']=='image/x-png')) {
		return array($kepnev,'k2');
	}elseif(in_array($_FILES[$valtozo]['type'],$tiltott_fajlok)){
		return array($kepnev,'k2b');
	}

    // Ha kell, levesszük a kiterjesztést
	$kit = '';
    if(strrpos($kepnev,'.')){
        $kepnev_tmp = substr($kepnev,0,strrpos($kepnev,'.'));
        $kit        = strtolower(substr($kepnev,strrpos($kepnev,'.'),strlen($kepnev)));//elmentjük a kiterjesztést, kisbetűsre alakítva
        $kepnev     = $kepnev_tmp;
    }
    // Ha van, felülírjuk a már létező fájlt
	if($overwrite){
		if($sor_id != null){//ha meg van adva a $sor_id akkor valószínűleg az elérési útvonal le van tárolva adatbázisba. Megkíséreljük ezt az előző fájlt törölni

			$sor = $adatbazis_kapcsolat -> s2($oszlop, $tabla, 'recid='.$sor_id);

			// Ebben az esetben valóban az adatbázisban van elmentve a kép elérési útvonala
			if($sor[$oszlop]){

                // Elfőrodukhat, hogy több bejegyzésnél is a kép útvonala azonos
                // Emiatt ha az egyiknél cserélnénk a képet, akkor azt még ne törölje le, ha máshol még arra a képre hivatkozunk
                $van_mashol_kep = false;
                $van_mashol_kep = $adatbazis_kapcsolat->s('recid', $tabla, $oszlop.'='.$sor[$oszlop].' AND recid!='.$sor_id);

                if(!$van_mashol_kep){
                    // Ha van kiterjesztése (általában van)
                    if( strrpos($sor[$oszlop], '.') > 0 ){
                        remover($utvonal.$tabla.'/'.substr($sor[$oszlop], 0, strrpos($sor[$oszlop], '.')).'_*');
                    } else {
                        remover($utvonal.$tabla.'/'.$sor[$oszlop].'_*');
                    }
                    // Végül magát a fájlt is töröljük
                    if(is_file($utvonal.$tabla.'/'.$sor[$oszlop])){
                        unlink($utvonal.$tabla.'/'.$sor[$oszlop]);
                    }
                }
			}

			$lekerdez = mysqli_query($db_kapcs, 'SELECT `recid` FROM `'.get_db_tabla().$tabla.'` WHERE `recid`!=\''.$sor_id.'\' AND `'.$oszlop.'` LIKE \''.$kepnev.'%\'');
			$foglalt_nev = mysqli_num_rows($lekerdez);
			while( $foglalt_nev ){
				$kepnev = fajlnev_sorszamoz($kepnev);

				$lekerdez=mysqli_query($db_kapcs, 'SELECT `recid` FROM `'.get_db_tabla().$tabla.'` WHERE `recid`!=\''.$sor_id.'\' AND `'.$oszlop.'` LIKE \''.$kepnev.'%\'');
				$foglalt_nev = mysqli_num_rows($lekerdez);
			}

		}

		// Egyébként ha nincs letárolva (sorid-s technika) és minden más esetben is törlünk minden hasonló fájlt
        $kepnev_ = $utvonal.$kepnev;
		if(is_file($kepnev_))       { if(!unlink($kepnev_)){        return 'k3'; } }
		if(is_file($kepnev_.'.jpg')){ if(!unlink($kepnev_.'.jpg')){ return 'k3'; } }
		if(is_file($kepnev_.'.gif')){ if(!unlink($kepnev_.'.gif')){ return 'k3'; } }
		if(is_file($kepnev_.'.png')){ if(!unlink($kepnev_.'.png')){ return 'k3'; } }

        // Resized képek esetére
		remover($kepnev_.'_*');

    // Egyébként másolat (Arra az esetre, ha pl. két termék neve megeggyezik és a nevéből nyerjük ki a képnevet => termeknev_2.jpg)
	} else {

		$i = 1;
		$ujnev = $kepnev;
		while(glob($utvonal.$ujnev.'*')){
			$ujnev = $kepnev.'_'.$i;
			$i++;
		}
		if( $i==1 && $sor_id ){
            $ujnev = $kepnev.'_1';
        }
		$kepnev = $ujnev;

	}

	// Ha szükséges, akkor adunk neki kiterjesztést is (képek esetén), vagy visszaadjuk az eredetit ha nem kép volt
	if($_FILES[$valtozo]['type']=='image/pjpeg' || $_FILES[$valtozo]['type']=='image/jpeg') { $kit='.jpg'; }
	if($_FILES[$valtozo]['type']=='image/gif')                                              { $kit='.gif'; }
	if($_FILES[$valtozo]['type']=='image/png' || $_FILES[$valtozo]['type']=='image/x-png')  { $kit='.png'; }
	$kepnev .= $kit;


    $konyvtar = substr($kepnev,0,strrpos($kepnev,'/'));
    if(!is_dir($utvonal.$konyvtar)){
        mkdir($utvonal.$konyvtar, 0777);
    }

	if(move_uploaded_file($_FILES[$valtozo]['tmp_name'], $utvonal.$kepnev)){
		if($sor_id) {

            // Még a tálba levét se tároljuk le, azt is felesleges.
            $kepnev = str_replace($tabla.'/', '', $kepnev);

			if(!$adatbazis_kapcsolat -> u($tabla, array($oszlop), array($kepnev), "`recid`='{$sor_id}'")){
                // Sikertelen táblamod => hiba5
				return(array($kepnev,'k5'));
			}

		}
		if($flashfeltoltes){
            // Flash alapú fájlfeltöltéskor kell egy kimenet, ha minden rendben volt.
            // Akkor ez már legyen az útvonal, mert JS-el feldolgozva ezt ki is tudjuk íratni.
            echo $utvonal.$kepnev;
        }

        // A képeket progresszívvá alakítjuk
        if( in_array($kit, array('.jpg'/*, '.png' PNG tömörítéssel gondok voltak, erre más megoldás kell */))  ){
            $data       = fread(fopen($utvonal.$kepnev, 'rb'), filesize($utvonal.$kepnev));
            $src_img    = @imagecreatefromstring($data);
            imageinterlace($src_img, 1);
            if($kit=='.jpg'){
                imagejpeg($src_img, $utvonal.$kepnev, 100);
            } else {
                imagepng($src_img, $utvonal.$kepnev);
            }
        }

		return array($kepnev, '-1');
	} else {
		return array($kepnev, 'k4');
	}
}

// Központi SQL előállítása a bal oldali navpanel elemeinek kiszámításához (where feltétel, rendezés)
/**
 * @param $where:  Pl. egy konkrét recid kiszámolásához át tudunk adni egy Where feltételt.
 * @param $tabla: Épp melyik adatbázison dolgozunk? Ha nincs megadva, akkor a self()-ből számolódik ki.
 * @param $kijeloltek: kijelölt sorok recid-jai  a navpanelban
 * @param $csak_sql_miatt: Ha nem akarjuk a bal navpenalt kiszámolni, csak a lekérdezés SQL-e kell
 * @param $getek: néha szükség van arra, hogy a GET-eket átadjuk, mert ez kell az adatok kiszámításhoz.
 *  */
function szamla_lista( $where = '', $tabla = '', $kijeloltek = false, $csak_sql_miatt = false, $getek = false ) {

    global $adatbazis_kapcsolat;
    global $db_kapcs;
    global $_OPTS;
    global $weblap;

    // Van, hogy pár GET-et is át szeretnénk adni
    if($getek){
        foreach($getek as $kulcs => $get){
            $_GET[$kulcs] = $get;
        }
    }

    if( $kijeloltek !== false ) {
        $kijeloltek = json_decode($kijeloltek, true);
    }

    // Config
    if( !$tabla ) {
        $tabla  = str_replace('.php', '', basename(self()));
    }

    // Dokumentumtárnál egyszerű a helyzet, nem kell számolni SQL-t
    if( $tabla == 'dokumentumtar' ) {
        return array(dokumentumtarLista(), '0');
    }

    $select = $_OPTS['select'];
    if($_GET['rend']){
        $order = $_GET['rend'];
    } else {
        $order = $_OPTS['rend'];
    }

    // Ha csak az SQL kell, hogy lássuk, mik a kapott ID-k, akkor a rendezéssel ne bajlódjunk
    if( !$csak_sql_miatt ) {
        // Előterjesztéseknél ha státusz szerint rendezünk, akkor azon belül iktatószám
        if( basename(self()) == 'eloterj_fb.php' || basename(self()) == 'eloterj_bod.php' ) {
            if( $order == 'stat_id' ) {
                $order = '`stat_id`, `ikt_szam`';
            }
        }

        // Bizonyos rendezéseket pont fordítva szeretnénk rendezni, mint adott oldal alapértelmezett rendezése iránya
        // Ezért az itt felsoroltakra váltva a rendezés irányán is fordítunk
        $iranyt_is_valt = array(
                                array('tabla' => 'utemezett_feladatok', 'rend' => 'aktiv',          'default' => 'desc' ),
                                array('tabla' => 'utemezett_feladatok', 'rend' => 'esnap_d',        'default' => 'asc'  ),
                            );

        $volt_irany_valtas = false;
        if( $_SESSION[$tabla]['elozo_rend'] && $_SESSION[$tabla]['elozo_rend'] != $_GET['rend'] ) {
            $i = 0;
            foreach ( $_OPTS['rendezes'] as $rendezes ) {
                if( $rendezes == $_GET['rend'] ) {
                    $_GET['irany'] = $_OPTS['iranyok'][$i];
                    $volt_irany_valtas = true;
                }
                $i++;
            }
        }

        // Jegyezzük meg, mi volt az előző rendezés szempont, hogy kétszer ne váltsunk irányt
        $_SESSION[$tabla]['elozo_rend'] = $order;

        $irany = $_GET['irany'];
        // Ha egy rendezésben vegyesen vannak az irányok, akkor erre az esetre letároljuk az ellenkezőjét egy másik GET-be
        if( $_GET['irany'] == 'desc' ) {
            $_GET['ellen_irany'] = '';
        } else {
            $_GET['ellen_irany'] = 'desc';
        }
        // Aktív esetén a rendezés irányát meg kell fordítani (tehát az aktívak, egyesek legyenek elől)
        if( $order == 'aktiv' ) {
            if( $irany == 'desc' ) {
                $irany = 'asc';
            } else {
                $irany = 'desc';
            }
        }
        $order .= ' '.$irany;
    }

    // Ha nem egy konkrét recid-t keresünk, akkor állítsuk elő a where feltételt
    if( !$where ) {

        if( $tabla == 'rendszer' ) {
            if( $_GET['rkat'] ) {
                if( strstr($_GET['rkat'], ',') ) {
                    $where = "`rkat` IN (".$_GET['rkat'].") AND ";
                } else {
                    $where = "`rkat` = '".$_GET['rkat']."' AND ";
                }
            }
        }

        $szallodak = get_akt_login_szallodai();
        if( $tabla == 'anyagigenyles' ) {
            $where = "`".get_db_tabla()."anyagigenyles`.`telephely_id` IN (".implode(',', $szallodak).") AND ";
        }

        // Bejelentéseknél szűrünk a törölve oszlop értékére is (logikailag törölt sorok nem látszanak)
        // Illetve csak a beloginolt user szállodáihoz tartozó bejegyzések látszanak
        if( $tabla == 'bejelentes' || $tabla == 'bejelentes_arch' ) {
            $where = "`".get_db_tabla().$tabla."`.`telephely_id` IN (".implode(',', $szallodak).") AND ";

            $reszlegek = get_akt_login_reszlegei();
            if( _jog(JOG_BEJELENTES_SAJAT_RESZL) && !_jog(JOG_BEJELENTES_MEGTEKINT) ) {
                $where .= "`usr_reszleg_id` IN (".implode(',', $reszlegek).") AND ";
            }
        }
        if( $tabla == 'eseti_fenntartas' ) {
            $where = "`".get_db_tabla()."eseti_fenntartas`.`telephely_id` IN (".implode(',', $szallodak).") AND ";
        }
        if( $tabla == 'eszkozkezeles' ) {
            $where = "`".get_db_tabla()."eszkozkezeles`.`telephely_id` IN (".implode(',', $szallodak).") AND ";
        }
        if( $tabla == 'fenntartasi_terv' ) {
            $where = "`".get_db_tabla()."fenntartasi_terv`.`telephely_id` IN (".implode(',', $szallodak).") AND ";
        }
        if( $tabla == 'utemezett_feladatok' ) {
            $where = "`".get_db_tabla()."utemezett_feladatok`.`telephely_id` IN (".implode(',', $szallodak).") AND ";
        }
        // A projektigénylés láthatóságának beállításai
        if( $tabla == 'projektigenyles' ) {

            $szallodak_kieg = '';
            // Többszállodás esetén a két többsszállodás telephely_id-t felvesszük még
            if( _jog(JOG_PRIGENY_TOBB_MEGTEKINT) ) {
                $szallodak_kieg = ', 4101, 4301';
            }

            // Számít a saját szállodai látókör
            $where = "`".get_db_tabla()."projektigenyles`.`telephely_id` IN (".implode(',', $szallodak).$szallodak_kieg.") AND ";
            
            // Ha csak egyszállodás, vagy csak többszállodás láthatóságom van, akkor ennek megfelelően még ki kell egészítei az SQL lekérdezést
            if( _jog(JOG_PRIGENY_EGY_MEGTEKINT) && !_jog(JOG_PRIGENY_TOBB_MEGTEKINT) ) {
                $where .= "`egy_tobb`='0' AND ";
            }
            if( !_jog(JOG_PRIGENY_EGY_MEGTEKINT) && _jog(JOG_PRIGENY_TOBB_MEGTEKINT) ) {
                $where .= "`egy_tobb`='1' AND ";
            }

            // Szűrés jogokra (igénylés tipusra külön látási jogok vannak)
            $where_jogok = '';
            if(_jog(JOG_PRIGENY_B_MEGTEKINT)){
                $where_jogok .= "`tipus`='".PRIGENY_TIPUS_BERUH."' OR ";
            }
            if(_jog(JOG_PRIGENY_F_MEGTEKINT)){
                $where_jogok .= "`tipus`='".PRIGENY_TIPUS_FENNT."' OR ";
            }
            if(_jog(JOG_PRIGENY_T_MEGTEKINT)){
                $where_jogok .= "`tipus`='".PRIGENY_TIPUS_TARGY."' OR ";
            }

            // Az egy-több szállodára is külön jogok vannak
            if(_jog(JOG_PRIGENY_EGY_MEGTEKINT)){
                $where_jogok .= "`egy_tobb`='0' OR ";
            }
            if( _jog(JOG_PRIGENY_TOBB_MEGTEKINT) ) {
                $where_jogok .= "`egy_tobb`='1' OR ";
            }
            if( $where_jogok ) {
                $where .= '('.csonkit($where_jogok, 4).') AND ';
            }

            // Létrehozás alatti szállodát nem láthat senki, kivéve aki dolgozik rajta
            $where .= " (`stat_id`!='".PRIGENY_STAT_LETRE_ALATT."' OR `loginnev`='{$_SESSION[get_db_tabla().'user']}')";

            // Viszont ahol véleményező vagyok, azokat az igényléseket mindenképp lássam
            $lathato_prigenyek = $adatbazis_kapcsolat->s('projektigenyles_recid', 'projektigenyles_kapcs', "`login_recid`='{$_SESSION[get_db_tabla().'login_recid']}'");
            $lathato_prigenyek = adatbazis2tomb($lathato_prigenyek, 'projektigenyles_recid');
            if( $lathato_prigenyek ) {
                $where .= " OR `".get_db_tabla()."projektigenyles`.`recid` IN (".implode(',', $lathato_prigenyek).") AND ";
            } else {
                $where .= ' AND ';
            }

        }        

        // Projektkezelés
        if( $tabla == 'projektkezeles' ) {

            // Központosítva van ennek a feltételnek a kiszámítása
            $where = get_projektkezeles_where_jog();
            
        }

        // Projektszamla
        if( $tabla == 'projektszamla' ) {
            // Saját szállodáimat láthatom
            $where = "(`".get_db_tabla()."projektszamla`.`telephely_id` IN (".implode(',', $szallodak).")) AND ";
            // Saját számla megtekintése esetén azokat a számlákat listázzuk csak ki, amelyekhez tartozó Projekteknél én vagyok a projekt felelős
            // Csak akkor szűkítünk tovább, ha nincs bekapcsolva a "Projekt szerződések megtekintése" globálisabb jog
            if( _jog(JOG_PR_SAJAT_SZAMLA_MEGTEKINT) && !_jog(JOG_PRSZAMLAK_OLVAS) ) {
                // Ezt egy SQL-ként is megcsinálhattuk volna, de azért kellett külön lekérni így az ikt. számokat, mert az SQL feltételben kétszer szerpeelt volna a WHERE szó, ami elé a legvégén 
                // egy JOIN-t még beszúrunk. Hiába írtam volnta úgy, hogy {W H E R E}, amit lecserélek, akkor sem működött.
                $pfelelos_prek = $adatbazis_kapcsolat->s('ikt_szam', 'projektkezeles', "`pfelelos`='{$_SESSION[get_db_tabla().'login_recid']}'");
                if( $pfelelos_prek ) {
                    $pfelelos_prek = adatbazis2tomb($pfelelos_prek, 'ikt_szam');
                    $where .= "`".get_db_tabla()."projektszamla`.`pr_szam` IN ('". implode('\', \'', $pfelelos_prek)."') AND ";
                }
            }
        }
        
        // Projektszerződés
        if( $tabla == 'projektszerzodes' ) {
            // Saját szállodáimat láthatom
            $where = "(`".get_db_tabla()."projektszerzodes`.`telephely_id` IN (".implode(',', $szallodak).")) AND ";
            // Saját szerződés megtekintése esetén azokat a szerződéseket listázzuk csak ki, amelyekhez tartozó Projekteknél én vagyok a projekt felelős
            // Csak akkor szűkítünk tovább, ha nincs bekapcsolva a "Projekt szerződések megtekintése" globálisabb jog
            if( _jog(JOG_PR_SAJAT_SZERZ_MEGTEKINT) && !_jog(JOG_PRKEZEL_SZERZODES_OLVAS) ) {
                // Ezt egy SQL-ként is megcsinálhattuk volna, de azért kellett külön lekérni így az ikt. számokat, mert az SQL feltételben kétszer szerpeelt volna a WHERE szó, ami elé a legvégén 
                // egy JOIN-t még beszúrunk. Hiába írtam volnta úgy, hogy {W H E R E}, amit lecserélek, akkor sem működött.
                $pfelelos_prek = $adatbazis_kapcsolat->s('ikt_szam', 'projektkezeles', "`pfelelos`='{$_SESSION[get_db_tabla().'login_recid']}'");
                if( $pfelelos_prek ) {
                    $pfelelos_prek = adatbazis2tomb($pfelelos_prek, 'ikt_szam');
                    $where .= "`".get_db_tabla()."projektszerzodes`.`pr_szam` IN ('". implode('\', \'', $pfelelos_prek)."') AND ";
                }
            }
        }

        // Rendszeres karbantartás
        if( $tabla == 'rendszeres_karbantartas' ) {
            // Saját szállodáimat láthatom
            $where = "`".get_db_tabla()."rendszeres_karbantartas`.`telephely_id` IN (".implode(',', $szallodak).") AND ";
        }

        if( $tabla == 'munkalap' || $tabla == 'munkalap_arch' ) {
            $where = get_munkalap_lathatosag_feltetel($tabla, $szallodak);
        }

        // Keresés
        if( $_GET['ksz'] ) {

            // Ki kell szűrni az " ... as " részeket.
            $select_feldogloz = nl2($_OPTS['select']);
            if( strpos($select_feldogloz, '` as `') !== false ) {
                $as_resz = strpos($select_feldogloz, '` as `');

                $select_tmp = substr($select_feldogloz, 1, $as_resz);
                // Maradékból vágjuk ki az as `ami ide van írva` részt. 4. ` karaterig vágunk
                $maradek    = substr($select_feldogloz, $as_resz);
                for ( $i = 0; $i < 4; $i++ ) {
                    $maradek = substr($maradek, 0, strpos('`'));
                }
                $select_feldogloz = $select_tmp.$maradek;
            }

            $selectek = explode(' ', $select_feldogloz);
            $ksz_where = '';
            foreach ( $selectek as $select_tmp ) {
                if( $select_tmp && $select_tmp != 'recid' ) {
                    if( strpos($select_tmp, '`') !== false ) {
                        $ksz_where .= trim($select_tmp, ',')." LIKE '%{$_GET['ksz']}%' OR ";

                        // Ékezet sensitive verzió: $ksz_where .= trim($select_tmp, ',')." LIKE '%{$_GET['ksz']}%' COLLATE utf8_bin OR ";
                    } else {
                        $ksz_where .= "`{$select_tmp}` LIKE '%{$_GET['ksz']}%' OR ";
                        // Ékezet sensitive verzió: $ksz_where .= "`{$select_tmp}` LIKE '%{$_GET['ksz']}%' COLLATE utf8_bin OR ";
                    }
                }
            }

            if( $ksz_where ) {
                $where .= '('.trim($ksz_where, ' OR ').') AND ';
            }

        }

        // Szűrés lekezelése
        if( $_GET['mezo'] && $_GET['op'] ) {
            // Az eddigi Where-t egy nagy zárójelbe tesszük és ehhez vesszük hozzá az új feltételeket
            if( $where ) {
                $where = '('.csonkit($where, 5).') AND ';
            }

            // Dátum mezők (sajnos itt class-ra már nem tudok keresni, szóval konkrét mezőnevek alapján dől el)
            $datum_mezok = array('insert_dt');
            if( in_array($_GET['mezo'], $datum_mezok) && $_GET['op'] == 'equal' ) {
                $_GET['op'] = 'like';
            }

            // Kiegészítő SQL hozzáfűzése, ha kell
            $where_kieg = get_szures_where_kieg($_GET['mezo'], $_GET['op'], $_GET['felt'], $tabla);
            if( $where_kieg ) {
                $where .= $where_kieg.' AND ';
            }

        }
        // Ha van öszetett szűrés feltétel
        if( $_SESSION['o_szuro_feltetel'] ) {
            // Az eddigi Where-t egy nagy zárójelbe tesszük és ehhez vesszük hozzá az új feltételeket
            if( $where ) {
                $where = '('.csonkit($where, 5).') AND ';
            }

            // Ha van get_o_szures_where_kieg() feltételünk is, akkor még azzal is megtoldjuk  a $where-t
            // Nem feltétlenük van SQL-ünk, ha van $_SESSION['o_szuro_feltetel']. Pl. ha a szűrés csak annyi tartalmaz, hogy Legfrisebb 100.
            $osszetett_szures_where = get_o_szures_where_kieg($_SESSION['o_szuro_feltetel'], $tabla);
            if( $osszetett_szures_where ) {
                $where .= $osszetett_szures_where.' AND ';
            }
        }

        // Ha van $_GET['idk'] változónk, akkor azokra az ID-kra még pluszban szűrünk
        if ($_GET['recidk']) {
            $where .= '`'.get_db_tabla().$tabla.'`.`recid` IN ('.$_GET['recidk'].') AND ';
        }

        // Anyagigénylés/anyagrendelés lekezelése
        if( $tabla == 'anyagigenyles' ) {
            // Jóváhagyva státuszig anyagigénylés, afelett rendelés
            if (isset($_GET['anyagrend'])) {
                $where .= "`".get_db_tabla()."anyagigenyles`.`stat_id`>".CIKKIGN_STAT_JOVAHAGYVA;
            } else {
                $where .= "`".get_db_tabla()."anyagigenyles`.`stat_id`<=".CIKKIGN_STAT_JOVAHAGYVA;
            }
        }

        // Where feltétel lezárása
        $where = trim($where, ' AND ');

    // Ha van megadva where feltétel, akkor az a recid=x, tehát frissítünk
    } else {
        $frissit = true;
    }

    // Lapozás előkészítése
    require_once('../class/class.Lapozo.php');

    if(!$_GET['oldal']){
        $_GET['oldal'] = 1;
    }

    $_GET['epo'] = 9999999999999;

    $bejegyzesek = $adatbazis_kapcsolat->s('recid', $tabla, $where);
    $lapozo = new Lapozo;
    $lapozo -> elem_osszes      = count($bejegyzesek);
    $lapozo -> fajl             = $_SERVER['PHP_SELF'];
    $lapozo -> mind_var         = ''; //'Összes elem kilistázása' változó neve. Ha üres akkor nincs össsez oldal megjelenítés. Alapértelmezetten 'osszes'
    //$lapozo -> epo_var         = 'epo';    //Elem per oldal változó neve
    //$lapozo -> oldal_var       = 'oldal';  //Oldal változó neve
    $lapozo -> kozep_meret      = 5; //Nagy oldalszám esetén a középső intervallum mérete
    //$lapozo -> max_fix_oldal   = 10;        //Ha a megadott számtól több oldal van, akkor jelenik meg az előző/követekző felirat
    //$lapozo -> epo_array       = array(50, 100, 150);  //Elem per oldal mennyiségek ha választhatóak, akkor egy tömbbyi interrel adhatjuk át az opciókat
    //$lapozo -> mind_text       = 'Összes oldal';  //pldalszámok mellett a "minden elem kilistázásának" felirata (ha $mind_var üres string akkor nem jelenik ez meg)
    //$lapozo -> elozo_title     = '« Előző oldal';        //'Előző' felirat, link title-jéhez
    //$lapozo -> kovet_title     = 'Következő oldal »';    //'Következő' felirat, link title-jéhez
    //$lapozo -> kozep_meret     = 'Aktuális oldal';       //'Aktuális oldal' felirat, link title-jéhez

    $utvonal = '../sablon/admin/';
    $lapozo -> sablon_elozo       = $utvonal.'lapozas_elozo.html';
    $lapozo -> sablon_kovetkezo   = $utvonal.'lapozas_kovet.html';
    $lapozo -> sablon_oldalak     = $utvonal.'lapozas_oldalak.html';
    $lapozo -> sablon_option      = $utvonal.'lapozas_option.html';
    $lapozo -> sablon_pontok      = $utvonal.'lapozas_pontok.html';
    $lapozo -> sablon_select      = $utvonal.'lapozas_select.html';


    // Számolás
    $lapozo -> lapozas();

    $lapozas = $lapozo -> kiir();

    $limit = $lapozo ->getLimit();
//echo 'sel:'.$select . ' ir:'.$irany.' or:'.$order.' lim:'.$limit.' where:'.$where.'<br>';

    // Ha recid alapján rendeznénk, akkor tegyük bele a `'.get_db_tabla().$tabla.'`-t, mert CSV exportnál úgy is lesz olyan JOIN, minek van recid-ja is, és ütni fogják egymást
    if( $_GET['rend']=='recid' ){
        $order = '`'.get_db_tabla().$tabla.'`.`recid` '.$_GET['irany'];
    }

    // Telephely táblával JOIN-olva, ha telephely_id alapján rendezünk vagy a selectben szerepel ez az oszlop
    $bejegyzesek = array();
    $kivetelek = array('projektkezeles');
    if( (strpos($order, 'telephely_id')!==false || strpos($select, 'telephely_id')!==false) && !in_array($tabla, $kivetelek) ) {

        // Először is az ordert felülírjuk, ha eszerint akartunk rendezni
        if(strpos($order, 'telephely_id')!==false){
            $order = '`'.get_db_tabla().'telephely`.`nev` '.$_GET['irany'];
        }

        // Rendezés anyagigényléskor Anyagrend mod: 2018.04
        if( $tabla == 'anyagigenyles' /* && !isset($_GET['anyagrend']) */ ) {

            // Táblázatos rendezés miatt itt másképp kell a rendezést megoldani
            // Alapértelmezett irány beállítása
            if( !$_SESSION['anyagigenyles_tablazat_rendez'] ) {
                $_SESSION['anyagigenyles_tablazat_rendez'] = 'anyag_tabla_ikt_szam';
                $_SESSION['anyagigenyles_tablazat_irany']  = 'desc';
            }
            switch ( $_SESSION['anyagigenyles_tablazat_rendez'] ) {
                case 'anyag_tabla_ikt_szam'     : $order = '`'.get_db_tabla().'anyagigenyles`.`ikt_szam`';      break;
                case 'anyag_tabla_szalloda'     : $order = '`'.get_db_tabla().'telephely`.`nev`';               break;
                case 'anyag_tabla_cikkszam'     : $order = '`'.get_db_tabla().'anyagigenyles`.`cikk_id`';       break;
                case 'anyag_tabla_cikknev'      : $order = '`'.get_db_tabla().'anyagigenyles`.`nev`';           break;
                case 'anyag_tabla_szallito'     : $order = '`'.get_db_tabla().'szallito`.`nev`';                break;
                case 'anyag_tabla_ig_menny'     : $order = '`'.get_db_tabla().'anyagigenyles`.`igmen`';         break;
                case 'anyag_tabla_mee'          : $order = '`'.get_db_tabla().'anyagigenyles`.`cikkmee`';       break;
                case 'anyag_tabla_ig_datum'     : $order = '`'.get_db_tabla().'anyagigenyles`.`insert_dt`';     break;
                case 'anyag_tabla_jovah_menny'  : $order = '`'.get_db_tabla().'anyagigenyles`.`jhmen`';         break;
                case 'anyag_tabla_rend_menny'   : $order = '`'.get_db_tabla().'anyagigenyles`.`rndmen`';        break;
                case 'anyag_tabla_rend_egysegar': $order = '`'.get_db_tabla().'anyagigenyles`.`rndear`';        break;
                case 'anyag_tabla_penznem'      : $order = '`'.get_db_tabla().'erteklista`.`info`';             break;
                case 'anyag_tabla_dev_rend'     : $order = '`'.get_db_tabla().'anyagigenyles`.`vrndear`';       break;
                case 'anyag_tabla_dev_beerk'    : $order = '`'.get_db_tabla().'anyagigenyles`.`erkear`';        break;
                case 'anyag_tabla_statusz'      : $order = '`'.get_db_tabla().'anyagigenyles`.`stat_id`';       break;
                case 'anyag_tabla_rndid'        : $order = '`'.get_db_tabla().'anyagigenyles`.`rndid`';         break;
                case 'anyag_tabla_rnd_dt'       : $order = '`'.get_db_tabla().'anyagigenyles`.`rnd_dt`';        break;
                case 'anyag_tabla_beerkezett'   : $order = '`'.get_db_tabla().'anyagigenyles`.`beerkezett`';    break;
                case 'anyag_tabla_rndertek'     : $order = '`'.get_db_tabla().'anyagigenyles`.`rndmen`*`'.get_db_tabla().'anyagigenyles`.`rndear`';  break;
                case 'anyag_tabla_beerkertek'   : $order = '`'.get_db_tabla().'anyagigenyles`.`erkmen`*`'.get_db_tabla().'anyagigenyles`.`rndear`';  break;
            }
            $order .= ' '.$_SESSION['anyagigenyles_tablazat_irany'];
            // Ha már kétszer volna benne a desc, akkor azt egyre cseréljük
            $order = str_replace('desc desc', 'desc', $order);
            $order = str_replace('desc asc',  'asc',  $order);
        }

        // Rendezés anyagrendeléskor
        if( $tabla == 'anyagigenyles' && isset($_GET['anyagrend']) ) {
            if( $_GET['rend'] == 'stat_id' ) {
                $order = '`'.get_db_tabla().'anyagigenyles`.`stat_id` '.$_GET['irany'].', `'.get_db_tabla().'anyagigenyles`.`rndid` '.$_GET['ellen_irany'].'';
            }
            if( $_GET['rend'] == 'rnd_dt' ) {
                $order = '`'.get_db_tabla().'anyagigenyles`.`rnd_dt` '.$_GET['irany'].', `'.get_db_tabla().'anyagigenyles`.`rndid` '.$_GET['irany'].'';
            }
        }

        // Rendezés beosztásnál
        if( $tabla == 'beosztas' ) {
            // Ha telephely szerint rendezünk, akkor ez lesz a következő rendezési szempont, és az irány is erre vonatkozik
            if( $_GET['rend'] == 'telephely_id' ) {
                $order = '`'.get_db_tabla().'telephely`.`nev` '.$_GET['irany'].', `'.get_db_tabla().'beosztas`.`tol`';
            }
        }

        // Bejelentéseknél a rendezés külön problémakör:
        if( $tabla == 'bejelentes' || $tabla == 'bejelentes_arch' ) {

            $order = '';

            $irany_ikt_szam  = '';
            $irany_telephely = '';

            if( $_GET['rend'] == 'stat_id' ) {

                if( !$_GET['irany'] ) {
                    $_GET['irany'] = 'desc';
                }

                $sorrend_idk = array();

                // Ha státusz szerint rendezünk, akkor beállítunk egy aktuális sorrendet, és a sorrend oszlop szerint rendezünk
                // Fontos! A rendezési sorrendet fordítva számítjuk ki, mert az irány alapból DESC az iktatószám csökkenősorrendje miatt.
                // Ez azért fontos, mert ha másik szűrésre vált, akkor ne forduljon meg rendezés nyíl.

                // Nem nyitott státuszú, azon belül sürgősek majd iktatószám
                // A státuszoknak is van egy sorrendje
                $statuszok = array_reverse( array(BEJELENTES_STAT_FOLYAMATBAN, BEJELENTES_STAT_KESZ_IG, BEJELENTES_STAT_VIZSG_ALATT, BEJELENTES_STAT_VARAKOZO, BEJELENTES_STAT_ELUTASITVA, BEJELENTES_STAT_LEZART) );
                foreach ( $statuszok as $stat ) {
                    $bejelentesek = $adatbazis_kapcsolat->s('recid', $tabla, "`stat_id`='{$stat}'", '`surgos`, `ikt_szam`');
                    foreach ( $bejelentesek as $bejelentes ) {
                        array_push($sorrend_idk, $bejelentes['recid']);
                    }
                }

                // Nyitott státuszú, azon belül sürgősek majd iktatószám
                $bejelentesek = $adatbazis_kapcsolat->s('recid', $tabla, "`stat_id`='".BEJELENTES_STAT_NYITOTT."'", '`surgos`, `ikt_szam`');
                foreach ( $bejelentesek as $bejelentes ) {                    
                    array_push($sorrend_idk, $bejelentes['recid']);
                }

                // Fordított sorrendben a recid-kat is megfordítjuk
                if( $_GET['irany'] == 'desc' ) {
                    $sorrend_idk = array_reverse($sorrend_idk);
                }

            // Ha telephely szerint rendezünk, akkor pedig ezt a szempontot kell előre venni, és az irány is erre vonatkozik
            }elseif($_GET['rend']=='telephely_id'){
                $order .= '`'.get_db_tabla().'telephely`.`nev` '.$_GET['irany'].', `'.get_db_tabla().$tabla.'`.`ikt_szam`';

            // Helyiség név szerinti rendezés
            }elseif($_GET['rend']=='obj_txt'){
                $order .= '`'.get_db_tabla().$tabla.'`.`obj_txt` '.$_GET['irany'].', `'.get_db_tabla().'telephely`.`nev` '.$_GET['irany'];

            // Alapértelmezett rendezettség a sürgős, azon belül telephely
            }elseif($_GET['rend']=='ikt_szam'){
                $order .= '`'.get_db_tabla().$tabla.'`.`ikt_szam` '.$_GET['irany'].', `'.get_db_tabla().'telephely`.`nev`';
            // Végül a felvitel szerinti sorrend
            }elseif($_GET['rend']=='recid'){
                $order .= '`'.get_db_tabla().$tabla.'`.`recid` '.$_GET['irany'];
            }

        }

        // Rendezés fenntartási tervnél
        if( $tabla == 'fenntartasi_terv' ) {
            // Ha telephely szerint rendezünk, akkor ez lesz a következő rendezési szempont, és az irány is erre vonatkozik
            if($_GET['rend']=='telephely_id'){
                $order = '`'.get_db_tabla().'telephely`.`nev` '.$_GET['irany'].', `'.get_db_tabla().'fenntartasi_terv`.`ev` '.$_GET['ellen_irany'].', `'.get_db_tabla().'fenntartasi_terv`.`update_dt` '.$_GET['ellen_irany'].'';
            }
        }
        
        // Rendezés munkalap esetén külön problémakör:
        if( $tabla == 'munkalap' || $tabla == 'munkalap_arch' ) {

            $order = '';

            $irany_ikt_szam  = '';
            $irany_telephely = '';

            // Ha státusz szerint rendezünk
            if( $_GET['rend'] == 'stat_id' ) {

                if( !$_GET['irany'] ) {
                    $_GET['irany'] = 'desc';
                }

                $sorrend_idk = array();

                // Ha státusz szerint rendezünk, akkor beállítunk egy aktuális sorrendet, és a sorrend oszlop szerint rendezünk
                // Fontos! A rendezési sorrendet fordítva számítjuk ki, mert az irány alapból DESC az iktatószám csökkenősorrendje miatt.
                // Ez azért fontos, mert ha másik szűrésre vált, akkor ne forduljon meg rendezés nyíl.

                $i = 0;
                // Nem nyitott státuszú, azon belül sürgősek majd iktatószám
                // A státuszoknak is van egy sorrendje
                $statuszok = array_reverse( array( MUNKALAP_STAT_BEOSZTAS_NELKUL, MUNKALAP_STAT_HOZZARENDELT, MUNKALAP_STAT_FELVEVE, MUNKALAP_STAT_ANYAGRAVAR, MUNKALAP_STAT_IGAZOLASRA_VAR, MUNKALAP_STAT_IGAZOLT, MUNKALAP_STAT_VIZSGALAT_ALATT, MUNKALAP_STAT_ELOKESZITES_ALATT, MUNKALAP_STAT_DONTESRE_VAR, MUNKALAP_STAT_AJANLAT_NYOMTATVA, MUNKALAP_STAT_HOZREND_MEGREND_VAR, MUNKALAP_STAT_HATIDO_UTEMEZETT, MUNKALAP_STAT_VARAKOZO, MUNKALAP_STAT_LEZARASRA_VAR, MUNKALAP_STAT_LEZART, MUNKALAP_STAT_ELUTASITVA, MUNKALAP_STAT_TOROLT, MUNKALAP_STAT_SZTORNO_VAR, MUNKALAP_STAT_SZTORNO ) );
                foreach ( $statuszok as $stat ) {
                    $munkalapok = $adatbazis_kapcsolat->s('recid', $tabla, "`stat_id`='$stat'", '`surgos`, `ikt_szam`');
                    foreach ( $munkalapok as $munkalap ) {
                        array_push($sorrend_idk, $munkalap['recid']);
                    }
                }

                // Nyitott státuszú, azon belül sürgősek majd iktatószám
                $munkalapok = $adatbazis_kapcsolat->s('recid', $tabla, "`stat_id`='".MUNKALAP_STAT_NYITOTT."'", '`surgos`, `ikt_szam`');
                foreach ( $munkalapok as $munkalap ) {
                    array_push($sorrend_idk, $munkalap['recid']);
                }

                // Fordított sorrendben a recid-kat is megfordítjuk
                if( $_GET['irany'] == 'desc' ) {
                    $sorrend_idk = array_reverse($sorrend_idk);
                }

            // Ha iktatószám szerint rendezünk, akkor ez lesz a következő rendezési szempont, és az irány is erre vonatkozik
            } elseif( $_GET['rend'] == 'ikt_szam' ) {

                $order .= '`'.get_db_tabla().$tabla.'`.`ikt_szam` '.$_GET['irany'].', `'.get_db_tabla().'telephely`.`nev`';

            // Helyiség név szerinti rendezés
            } elseif( $_GET['rend'] == 'obj_id' ) {
                $order .= '`'.get_db_tabla().$tabla.'`.`obj_txt` '.$_GET['irany'].', `'.get_db_tabla().'telephely`.`nev` '.$_GET['irany'].'';

            // Ha telephely szerint rendezünk, akkor pedig ezt a szempontot kell előre venni, és az irány is erre vonatkozik
            } elseif( $_GET['rend'] == 'telephely_id' ) {
                $order .= '`'.get_db_tabla().'telephely`.`nev` '.$_GET['irany'].', `'.get_db_tabla().$tabla.'`.`ikt_szam`';
            
            // Végül a felvitel szerinti sorrend
            } elseif( $_GET['rend'] == 'recid' ) {
                $order .= '`'.get_db_tabla().$tabla.'`.`recid` '.$_GET['irany'];
            }

            $order = trim($order, ', ');

        }

        // Archiv menüpont alatt egy 1000-es limit mindig van (ha nincs más szűrés, akkor a feltétel a lista hossza)
        if( ($tabla=='bejelentes_arch' || $tabla=='munkalap_arch') && !$_GET['mezo'] ) {
            $_GET['mezo'] = 'legfrissebb';
            $_GET['felt'] = ARCHIV_NAVPANEL_LIMIT;
        }
        // Anyagrendelések alatt, ha a szerepkör mszk ügyintéző vagy mszk vezető,
        // és nincs szűrve semmire (összetetten sem, ez itt fontos a 0-ás alapértelmezett érték miatt), akkor nem listázunk ki semmit.
        $nullas_szerepkorok = array(MSZK_VEZETO, MUSZAKI_ADMINISZTRATOR);
        if( in_array($_SESSION[get_db_tabla().'szemelyzet_id'], $nullas_szerepkorok) && $tabla=='anyagigenyles' && isset($_GET['anyagrend']) && !$_GET['mezo'] && !$_SESSION['o_szuro_feltetel'] ) {
            $_GET['mezo'] = 'legfrissebb';
            $_GET['felt'] = '-'; // Ha csak simán 0-át írnánk ide, akkor a feltételt üresnek tekintené, olyan mintha nem létezne
        }

        if( $_GET['mezo'] == 'legfrissebb' ) {
            $limit = $_GET['felt'];
            if( $order ) {
                $order .= ', ';
            }
            $order .= '`'.get_db_tabla().$tabla.'`.`insert_dt` DESC';
        }

        // Limit szám kiszámítása az összetett szűrőből
        $osszetett_legfrissebb = strpos($_SESSION['o_szuro_feltetel'], 'me|legfrissebb#op|equal#fe|');
        if( $osszetett_legfrissebb!==false ) {
            // Keressük meg a "legfrissebb" szűrő feltétel vége pozíciót
            $vege  = strpos( $_SESSION['o_szuro_feltetel'], '#es', $osszetett_legfrissebb+27 );

            // A feltétel vége pozíciójából kivonva a kezdőpozíciója + a 27 lesz a limit amit keresünk => "me|legfrissebb#op|equal#fe|NA ENNEK A SZÁMNAK A HOSSZA#es"
            // Ezt a limit hosszt vágjuk ki a $_SESSION['o_szuro_feltetel'] stringből.
            $limit = substr($_SESSION['o_szuro_feltetel'], $osszetett_legfrissebb+27, $vege-($osszetett_legfrissebb+27) );
            if( $order ) {
                $order .= ', ';
            }
            $order .= '`'.get_db_tabla().$tabla.'`.`insert_dt` DESC';
        }

        // Rendezés projektigényeknél
        if( $tabla == 'projektigenyles' ) {
            // Ha státusz szerint rendezünk, akkor azon belül iktató szám szerint is
            if( $_GET['rend'] == 'stat_id' ) {
                $order .= ', `'.get_db_tabla().$tabla.'`.`ikt_szam` '.$_GET['irany'];
            }
        }

        // Rendezés projektszámláknál
        if( $tabla == 'projektszamla' ) {
            // Ha telephely szerint rendezünk, akkor ez lesz a következő rendezési szempont, és az irány is erre vonatkozik
            if( $_GET['rend'] == 'telephely_id' ) {
                $order = '`'.get_db_tabla().'telephely`.`nev` '.$_GET['irany'].', `'.get_db_tabla().'projektszamla`.`erk_dt` '.$_GET['irany'];
            }
        }

        // Rendezés projektszerződéseknél
        if( $tabla == 'projektszerzodes' ) {
            // Ha telephely szerint rendezünk, akkor ez lesz a következő rendezési szempont, és az irány is erre vonatkozik
            if( $_GET['rend'] == 'telephely_id' ) {
                $order = '`'.get_db_tabla().'telephely`.`nev` '.$_GET['irany'].', `'.get_db_tabla().'projektszerzodes`.`sz_szam` '.$_GET['irany'];
            }
        }
        
        // Rendezés Ütemezett feladatoknál
        if( $tabla == 'utemezett_feladatok' ) {
            // Ha aktív szerint rendezünk, akkor azon belül esedékesség dátuma csökkenő + szálloda csökkenő
            if( $_GET['rend'] == 'aktiv' ) {
                $order = '`aktiv` '.$_GET['irany'].', `esnap_d` '.$_GET['irany'].', `'.get_db_tabla().'telephely`.`nev` '.$_GET['irany'];
            }
            // Ha szálloda szerint rendezünk, akkor azon belül esedékesség dátuma csökkenő + szálloda csökkenő
            if( $_GET['rend'] == 'telephely_id' ) {
                $order = '`'.get_db_tabla().'telephely`.`nev` '.$_GET['irany'].', `aktiv` '.$_GET['ellen_irany'].', `esnap_d` '.$_GET['ellen_irany'];
            }
            // Ha szálloda szerint rendezünk, akkor azon belül esedékesség dátuma csökkenő + szálloda csökkenő
            if( $_GET['rend'] == 'esnap_d' ) {
                $order = '`esnap_d` '.$_GET['irany'].', `aktiv` '.$_GET['irany'].', `'.get_db_tabla().'telephely`.`nev` '.$_GET['ellen_irany'];
            }
        }
        
        // Nullás limit visszaállítása
        if( $limit == '-' ) {
            $limit = '0';
        }

        // Ez azért kell, hogy az SQL stringünk meglegyen. A WHERE szót le fogjuk még cserélni JOIN-okkal
        $adatbazis_kapcsolat->s($select, $tabla, $where, $order, $limit, false, false, false, 'csak az SQL string-je kell');
        $sql = $adatbazis_kapcsolat->gp();
        $mit = 'WHERE';
        
        // Anyagigényléskor a szállítót, cikkrendfejtet és értéklistát vesszük még fel
        $sql_kieg = '';
        if( $tabla == 'anyagigenyles' ) {
            $sql_kieg .= nl2('
                                LEFT JOIN `'.get_db_tabla().'szallito` ON (`'.get_db_tabla().'anyagigenyles`.`szallito_id`=`'.get_db_tabla().'szallito`.`id`)
                                LEFT JOIN `'.get_db_tabla().'cikkrendfej` ON (`'.get_db_tabla().'anyagigenyles`.`aparecid`=`'.get_db_tabla().'cikkrendfej`.`recid`)
                                LEFT JOIN `'.get_db_tabla().'erteklista` ON (`'.get_db_tabla().'anyagigenyles`.`kcs_21`=`'.get_db_tabla().'erteklista`.`id` AND `'.get_db_tabla().'erteklista`.`csoport`=\''.ERTEKLISTA_PENZNEM.'\')
                            ');
        }

        $arch = '';
        if( $tabla == 'bejelentes_arch' || $tabla == 'munkalap_arch' ) {
            $arch = '_arch';
        }
        // Bejelentésnél és csoportoknál még az értéklistát és csoportot is felvesszük
        if( $tabla == 'bejelentes' || $tabla == 'bejelentes_arch' ) {
            // Státusz szerinti rendezésnél nem JOIN-oljuk a telephelyeket, egyébként mindig
            if( $_GET['rend'] != 'stat_id' ) {
                $sql_kieg .= ' JOIN `'.get_db_tabla().'telephely` ON `'.get_db_tabla().$tabla.'`.`telephely_id`=`'.get_db_tabla().'telephely`.`id`';
            }
        }
        // Már nem használunk csoportot, de az SQL-el pedig csínján kell bánni, ezért kivéve 2018.05.28
        if( $tabla == 'munkalap' || $tabla == 'munkalap_arch' ) {
            // Státusz szerinti rendezésnél nem JOIN-oljuk a telephelyeket, egyébként mindig
            if( $_GET['rend'] != 'stat_id' ) {
                $sql_kieg .= ' JOIN `'.get_db_tabla().'telephely` ON `'.get_db_tabla().$tabla.'`.`telephely_id`=`'.get_db_tabla().'telephely`.`id`';
            }
        }

        // Összetett szűrés kapcsolódó táblái miatti JOIN-ok
        if ( $_GET['mezo']=='megjegyzes' || strpos($_SESSION['o_szuro_feltetel'], 'megjegyzes')!==false ) {
            $sql_kieg .= ' JOIN `'.get_db_tabla().'megjegyzes` ON `'.get_db_tabla().'megjegyzes`.`tabla`=\''.$tabla.'\' AND `'.get_db_tabla().''.$tabla.'`.`recid`=`'.get_db_tabla().'megjegyzes`.`id`';
        }
        // A bejelentések és munkalapoknál már megvan oldva, hogy ne használjunk obj JOIN-t, de a többi tálbára még nincs megoldva
        $kivetelek = array('bejelentes', 'bejelentes_arch', 'munkalap', 'munkalap_arch');
        if( (strpos($select, 'obj_id')!==false && !in_array($tabla, $kivetelek)) ){
            $sql_kieg .= ' LEFT JOIN `'.get_db_tabla().'obj` ON `'.get_db_tabla().$tabla.'`.`obj_id`=`'.get_db_tabla().'obj`.`recid` ';
        }
        // Sőt a Telephely JOIN sincs még megoldva
        if( !in_array($tabla, $kivetelek) ) {
            $sql_kieg .= ' JOIN `'.get_db_tabla().'telephely` ON `'.get_db_tabla().$tabla.'`.`telephely_id`=`'.get_db_tabla().'telephely`.`id`';
        }

        // Ha az SQL-ben nem volna WHERE feltétel, akkor az ORDER BY elé kell beszúrni a JOIN-t
        $mivel = $sql_kieg.' WHERE';
        if( strpos($sql, 'WHERE') === false ) {
            $mit   = 'ORDER BY';
            $mivel = str_replace('WHERE', 'ORDER BY', $mivel);
        }
        //$sql = str_replace($mit, $mivel, $sql);
        // Így cseréljük le, azaz csak az első előfordulást, ne az összeset! Ez fontos.
        $sql = preg_replace('/'.$mit.'/', $mivel, $sql, 1);

        $sorok = mysqli_query($db_kapcs, $sql);
        while ( $sor = mysqli_fetch_assoc($sorok) ) {
            array_push($bejegyzesek, $sor);
        }

    // Értéklistát csoport szerinti sorrendben, azon belül pedig infó szerinti sorrendben kell kilistázni
    } elseif( $tabla == 'erteklista' ) {

        // Gyorskeresőnél képződhet where feltétel
        if( $where ) {
            $where = ' WHERE '.$where;
        }

        $sql = 'SELECT
                        `'.get_db_tabla().'erteklista`.`recid`,
                        `'.get_db_tabla().'erteklista`.`csoport`,
                        `'.get_db_tabla().'erteklista`.`info`,
                        `'.get_db_tabla().'erteklista_csoport`.`nev`
                FROM    `'.get_db_tabla().'erteklista`
                JOIN    `'.get_db_tabla().'erteklista_csoport` ON `'.get_db_tabla().'erteklista`.`csoport`=`'.get_db_tabla().'erteklista_csoport`.`csoport`
                '.$where.'
                ORDER BY `'.get_db_tabla().'erteklista_csoport`.`nev`, `'.get_db_tabla().'erteklista`.`info`
                ';

        $sorok = mysqli_query($db_kapcs, $sql);
        while ( $sor = mysqli_fetch_assoc($sorok) ) {
            array_push($bejegyzesek, $sor);
        }

    // Általános eset
    } else {

        // Szűréskor kell pár JOIN-olás a külső táblákhoz
        $join = '';
        // Rendezés Cikktörzsnél: Ha szerepkör szerint rendezünk, akkor a JOIN-olt értéklista tábla info-ja szerint rendezünk, mert a szerepkör szöveges tartalma ez valójában
        if($tabla=='cikktorzs' && $_GET['rend']=='szakter_id'){
            $join = ' JOIN `'.get_db_tabla().'erteklista` ON `'.get_db_tabla().$tabla.'`.`szakter_id`=`'.get_db_tabla().'erteklista`.`id` AND `'.get_db_tabla().'erteklista`.`csoport`=\''.ERTEKLISTA_SZAKTER.'\'';
            $order = '`'.get_db_tabla().'erteklista`.`info` '.$_GET['irany'];
        }
        if ($_GET['mezo']=='megjegyzes' || strpos($_SESSION['o_szuro_feltetel'], 'megjegyzes')!==false ) {
            $join = ' JOIN `'.get_db_tabla().'megjegyzes` ON `'.get_db_tabla().'megjegyzes`.`tabla`=\''.$tabla.'\' AND `'.get_db_tabla().$tabla.'`.`recid`=`'.get_db_tabla().'megjegyzes`.`id`';
        }
        if ( $tabla=='szallito' && ($_GET['mezo']=='szakterulet' || strpos($_SESSION['o_szuro_feltetel'], 'szakterulet')!==false ) ) {
            $join = ' JOIN `'.get_db_tabla().'szakter_kapcs` ON `'.get_db_tabla().'szakter_kapcs`.`szallito_id`=`'.get_db_tabla().$tabla.'`.`recid`';
        }
        if ( $tabla=='login' && ($_GET['mezo']=='szalloda' || strpos($_SESSION['o_szuro_feltetel'], 'szalloda')!==false ) ) {
            $join = ' JOIN `'.get_db_tabla().'telephely_kapcs` ON `'.get_db_tabla().'login`.`nev`=`'.get_db_tabla().'telephely_kapcs`.`nev`';
        }
        // Rendezés projektkezelés esetén
        if( $tabla=='projektkezeles' ){
            $join = ' JOIN `'.get_db_tabla().'telephely` ON `'.get_db_tabla().'projektkezeles`.`telephely_id`=`'.get_db_tabla().'telephely`.`id`';
            if($_GET['rend']=='stat_id'){
                $order = '`'.get_db_tabla().'projektkezeles`.`stat_id` '.$_GET['irany'].', `'.get_db_tabla().'telephely`.`nev` '.$_GET['irany'].', `'.get_db_tabla().$tabla.'`.`ikt_szam` '.$_GET['irany'];
            }
            if($_GET['rend']=='telephely_id'){
                $order = '`'.get_db_tabla().'telephely`.`nev` '.$_GET['irany'].', `'.get_db_tabla().'projektkezeles`.`stat_id` '.$_GET['irany'].', `'.get_db_tabla().$tabla.'`.`ikt_szam` '.$_GET['irany'];
            }
        }
        
        // Összetett szűrő kapcsolódó táblája miatt kell még pár JOIN
        if ( $_GET['mezo']=='team_tag' || strpos($_SESSION['o_szuro_feltetel'], 'team_tag')!==false ) {
            $join .= ' JOIN `'.get_db_tabla().'projektkezeles_team` ON `'.get_db_tabla().'projektkezeles_team`.`projektkezeles_recid`=`'.get_db_tabla().'projektkezeles`.`recid`';
        }
        
        // Gyorsszűrő limitje
        if( $_GET['mezo'] == 'legfrissebb' ) {
            $limit = $_GET['felt'];
            if($order){
                $order .= ', ';
            }
            $order .= '`'.get_db_tabla().$tabla.'`.`insert_dt` DESC';
        }
        
        // Összetett szűrő limitjét kiszámoljuk
        $osszetett_legfrissebb = strpos($_SESSION['o_szuro_feltetel'], 'me|legfrissebb#op|equal#fe|');
        if( $osszetett_legfrissebb!==false ) {
            // Keressük meg a "legfrissebb" szűrő feltétel vége pozíciót
            $vege  = strpos( $_SESSION['o_szuro_feltetel'], '#es', $osszetett_legfrissebb+27 );

            // A feltétel vége pozíciójából kivonva a kezdőpozíciója + a 27 lesz a limit amit keresünk => "me|legfrissebb#op|equal#fe|NA ENNEK A SZÁMNAK A HOSSZA#es"
            // Ezt a limit hosszt vágjuk ki a $_SESSION['o_szuro_feltetel'] stringből.
            $limit = substr($_SESSION['o_szuro_feltetel'], $osszetett_legfrissebb+27, $vege-($osszetett_legfrissebb+27) );
        }

        // Ha csak egy konkrét rekordot frissítünk a bal navpanelben, akkor a limit 1 legyen. Join esetén többször ugyanaz is kijöhet, ezért lényeges limitálni.
        if( isset($_GET['szamlaSorFrissit']) ) {
            $limit = 1;
        }
        
        $bejegyzesek = $adatbazis_kapcsolat->s($select, $tabla, $where, $order, $limit, '', '', $join);
        $sql = $adatbazis_kapcsolat->gp();

    }

    // $sorrend_idk tömböt mindig létrehozzuk, ha még nem létezne, mert a CSV sorrendet mindig a navpaneles sorrend alapján állítjuk elő
    if( !isset($_GET['szamlaSorFrissit']) ) {

        if( !$sorrend_idk ) {
            $sorrend_idk = array();
            $lekerdezes = mysqli_query($db_kapcs, $sql);
            while( $sor = mysqli_fetch_assoc($lekerdezes) ) {
                // Ha túl sok ID-t veszünk fel a $sorrend_idk többe, akkor az SQL ORDER BY FIELD része is túl hosszú lesz, nem fog tudni lefutni, üres excel jön majd le.
                // Sajnos ez azt is jelenti, hogy az itt megadott limiten felüli számú sort nem lehetn sorrendbe kiexportálni. Maximum az első X lesz sorrendben
                array_push( $sorrend_idk, $sor['recid'] );
            }
        }
        // Ha van $sorrend_idk információnk (azaz a bejelentés és munkalapokat nem SQL-el, hanem JS-el rendezzük), akkor a CSV export miatt az SQL-t csak előállítjuk, hogy meglegyen CSV sorrend beállításakor
        if( !isset($_GET['csv']) ) {
            if( $sorrend_idk ) {
                $sorrend_idk = array_slice($sorrend_idk, 0, get_sorrend_tomb_max_meret());
                $_SESSION['akt_sql_order'] = ' ORDER BY FIELD(`'.get_db_tabla().$tabla.'`.`recid`, \''.implode('\', \'', $sorrend_idk).'\') ';
            } else {
                $_SESSION['akt_sql_order'] = '';
            }
        }
    }

    // Ha csak a darabszámra vagyunk kíváncsiak, akkor ezt írjuk ki, amit az admin.js f_szures_submit-nál kezelünk le
    if( isset($_GET['sql_db']) ) {
        $db = count($bejegyzesek);
        
        // Nyitott bejelentéseknél a le kell vonni a lerejtett sorokat
        // Itt azt a feltételt kell lekezelni, mint ami a bejelentesLista függvényben is van
        if( $tabla == 'bejelentes' ) {
            if( _jog(JOG_MUNKALAP_KESZITHET) ) {
                foreach( $bejegyzesek as $bejelentes ) {
                    
                    $rejtsuk = false;
                    if( $bejelentes['stat_id'] == BEJELENTES_STAT_NYITOTT ) {
                    
                        $szalloda  = $adatbazis_kapcsolat->s2('modell', 'telephely', "`id`='{$bejelentes['telephely_id']}'");

                        if( !$szalloda['modell'] ) {
                            $van_kapcs = $adatbazis_kapcsolat->s('munkalap_id', 'munkalap_kapcs', "`bejelentes_id`='{$bejelentes['recid']}'");
                            if( count($van_kapcs) == 1 ) {
                                $letezik_munkalap = $adatbazis_kapcsolat->s2('recid', 'munkalap', "`recid`='{$van_kapcs[0]['munkalap_id']}'");
                                if( $letezik_munkalap ) {
                                    $statuszok = $adatbazis_kapcsolat->s('recid', 'munkalap_statusz', "`munkalap_recid`='{$letezik_munkalap['recid']}'");
                                    if( count($statuszok) == 2 ) {
                                        $rejtsuk = true;
                                    }
                                }
                            }
                        }
                        
                    }
                    if( $rejtsuk ) {
                        $db--;
                    }
                }
            }
        }
        
        // Munkalapoknál is ki kell vonni azokat a sorokat, ahol a jóváhagyható keretem miatt el van rejtve
        if( $tabla == 'munkalap' ) {
            
            $login = $adatbazis_kapcsolat->s2('jh_ft_ig', 'login', "`recid`='{$_SESSION[get_db_tabla().'login_recid']}'");
            foreach( $bejegyzesek as $munkalap ) {
                if( $munkalap['stat_id'] == MUNKALAP_STAT_DONTESRE_VAR && $munkalap['jh_ert'] > $login['jh_ft_ig'] ) {
                    $db--;
                }
            }
            
        }
        
        echo $db;
        $weblap -> leallit();
        exit;
    }

    // Weblap.php elején vizsgáljuk, hogy a kért ID-t egyáltalán láthatjuk-e vagy sem. Ekkor elég ha kapunk egy SQL-t
    if( $csak_sql_miatt ) {
        return $sql;
    }
    // Excel exporthoz (lehet Nyomtatáshoz, PDF-hez is kell majd?)
    if( !isset($_GET['szamlaSorFrissit']) && $tabla != 'esemenyek' && !isset($_GET['csv']) ) {
        $_SESSION['akt_sql'] = $sql;
    }

if( isset($_GET['debug']) && $_SERVER['HTTP_HOST']!='teszt.onteam.hu' /*&& $_SERVER['HTTP_HOST']!='dev.onteam.hu'*/ && $_SERVER['HTTP_HOST']!='www.onteam.hu' && $_SERVER['HTTP_HOST']!='onteam.hu' ) {
    echo $_SESSION['akt_sql']. '<br>';
}

    if( $bejegyzesek ) {
        switch($tabla) {
            case 'anyag_szamla':
                $html = anyag_szamlaLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'anyagigenyles':
                $html = anyagigenylesLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'bejelentes':
            case 'bejelentes_arch':
                $html = bejelentesLista( $bejegyzesek, $tabla, $frissit, $kijeloltek, $sorrend_idk );
            break;
            case 'bejovo_szamlak':
                $html = bejovoSzamlaLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'beosztas':
                $html = beosztasLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'cash_flow':
                $html = cash_flowLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'cikktorzs':
                $html = cikktorzsLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'eloterj_bod':
                $html = eloterj_bodLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'eloterj_fb':
                $html = eloterj_fbLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'energia':
                $html = energiaLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'energia_szamla':
                $html = energia_szamlaLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'energia_terv':
                $html = energia_tervLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'erteklista':
                $html = erteklistaLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'eseti_fenntartas':
                $html = eseti_fenntartasLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'obj':
            case 'eszkozkezeles':
                // Itt ketté bontjuk, mert szükségünk van az objektumok id-jára is
                $eszkozokLista = eszkozkezelesLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
                $html = $eszkozokLista['html'];
            break;
            case 'felmeresi_terv':
                $html = felmeresi_tervLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'fenntartasi_szamla':
                $html = fenntartasi_szamlaLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'fenntartasi_szerzodes':
                $html = fenntartasi_szerzodesLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'fenntartasi_terv':
                $html = fenntartasi_tervLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'ktghely':
                $html = ktghelyLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'ktgnem':
                $html = ktghelyLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'login':
                $html = loginLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'munkalap':
            case 'munkalap_arch':
                $html = munkalapLista( $bejegyzesek, $tabla, $frissit, $kijeloltek, $sorrend_idk);
            break;
            case 'rendszeres_karbantartas':
                $html = rendszeres_karbantartasLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'projektigenyles':
                $html = projektigenylesLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'projektkezeles':
                $html = projektkezelesLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'projektszamla':
                $html = projektszamlaLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'projektszerzodes':
                $html = projektszerzodesLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'raktar':
                $html = raktarLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'rendszer':
                $html = rendszerLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'szamla':
                $html = szamlaLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'szallito':
                $html = szallitoLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'szabadsagigeny':
                $html = szabadsagigenyLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'szerepkor':
                $html = szerepkorLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'tervezes':
                $html = tervezesLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'torzsadat':
                $html = torzsadatLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'utemezett_feladatok':
                $html = utemezett_feladatokLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'uzemeltetesi_naplo':
                $html = uzemeltetesi_naploLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
        }
    }

    if(!$html && !$frissit){
        $html = '<div class="p c b">Nincs találat a megadott feltételekre!</div>';
    }

    // Ha volt irány váltás, akkor még egy JS kódot kiíratunk, amivel a nyilat is megfordítjuk
    if($volt_irany_valtas && !isset($_GET['szamlaSorFrissit'])){
        if($_GET['irany']=='desc'){

            $js = '
                    $(\'#rendezesIrany\').html(\'▼ <span>Z-A</span>\');
                    $(\'#rendezesIrany\').attr(\'href\', $(\'#rendezesIrany\').attr(\'href\').replace(\'\\\'desc\\\'\', \'\\\'asc\\\'\'));
                    ';
        } else {

            $js = '
                    $(\'#rendezesIrany\').html(\'▲ <span>A-Z</span>\');
                    $(\'#rendezesIrany\').attr(\'href\', $(\'#rendezesIrany\').attr(\'href\').replace(\'\\\'asc\\\'\', \'\\\'desc\\\'\'));
                    ';
        }
        $html .= '<script>
                    $.when( lista ).then(function() {
                        '.$js.'
                    });
                </script>';
    }

    $idk_vissza = adatbazis2tomb($bejegyzesek, 'recid');

    if( is_array($eszkozokLista['idk']) && count($eszkozokLista['idk']) ) {
        $idk_vissza = array_merge($idk_vissza, $eszkozokLista['idk']);
    }

    return array(nl2($html), $idk_vissza);
    // return nl2($html);

}

function anyag_szamlaLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach ( $bejegyzesek as $bsz ) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <table>
                            <tr> 
                                <td style="width: 12rem">'.getSzallodaNev($bsz['telephely_id']).'</td>
                                <td style="width: 10rem" class="r">'.number_format($bsz['netto'], 0, '', ' ').' '.get_penznem().'</td>
                                <td></td>
                            </tr>
                            <tr> 
                                <td colspan="3">'. getErtek('nev', 'szallito', 'id', $bsz['szallito_id']).'</td>
                            </tr>
                            </table>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function bejelentesLista( $bejegyzesek, $tabla, $frissit, $kijeloltek, $sorrend_idk ) {
    global $adatbazis_kapcsolat;

    // Legyen-e JS rendezés, vagy sem?
    // Csak akkor van, ha nincs megadva rendezés, vagy ha az a stat_id, ÉS FONTOS, hogy nem egy sort frissítünk
    $js_rendezes = false;
    if( $sorrend_idk && (!$_GET['rend'] || $_GET['rend']=='stat_id') && !$_GET['szamlaSorFrissit'] && !$_GET['ajax'] ){
        $js_rendezes = true;
    }

    if( $bejegyzesek ) {
        $csoport = $bejegyzesek[0]['csoport_id'];

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer nobehuzas">';
        }

        // Ha nem adunk meg $tabla-t, akkor nyomtatási nézetben vagyunk
        $nohover = '';
        if(!$tabla){
            $nohover = ' nohover';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
// Csoportosítás megszűnt, ezt máris vegyük ki úgy ahogy van
//            if( !$frissit && _jog(JOG_MUNKALAP_KESZITHET)/* Csoportra bontás csak a diszpécsernek jelenik meg */ ) {
//                $aktCsoport = $bsz['csoport_id'];
//
//                if( $i == 1 || $csoport != $aktCsoport ) {
//                    $csoportSor = $adatbazis_kapcsolat->s2('nev', 'bejelentes_csoport', 'recid='.$aktCsoport, '', '1');
//
//                    if( $csoportSor ) {
//
//                        // Van e sürgős
//                        $surgos_e = $adatbazis_kapcsolat->s('recid', $tabla, "`csoport_id` = '{$aktCsoport}' AND `surgos` > 0", '', '1');
//                        $surgos_icon = '';
//                        if( $surgos_e ) {
//                            $surgos_icon = nl2('
//                                <span class="icon bej piros">
//                                    <i class="material-icons">error</i>
//                                </span>
//                            ');
//                        }
//
//                        // Ha egy csoport elemén vagyunk, akkor a ki-be nyitó ikon "-"-on legyen
//                        $csoport_bej = $adatbazis_kapcsolat->s('recid', $tabla, "`csoport_id` = '{$aktCsoport}'");
//                        $csoport_active = '';
//                        if( $csoport_bej ) {
//                            $csoport_bej = adatbazis2tomb($csoport_bej, 'recid');
//                            if( in_array($_GET['id'], $csoport_bej) ) {
//                                $csoport_active = ' nyitva';
//                            }
//                        }
//
//
//                        $bold = '';
//                        $bejelentes = $adatbazis_kapcsolat->s2('recid telephely_id', $tabla, "`csoport_id` = '{$aktCsoport}' AND `stat_id`='".BEJELENTES_STAT_NYITOTT."'", '', '1');
//                        // Ha van legalább egy nyitott eleme, akkor biztosan nyitott a csoport is (elvégre a csoport státusza csak eygszerre mozoghat)
//                        if($bejelentes){
//                            $bold = ' b';
//                        }
//                        // Egy csoporton belül biztosan csak olyan bejelentések lesznek, amiknek azonos a telephelye
//                        $telephely = $adatbazis_kapcsolat->s2('nev', 'telephely', "`id`='{$bejelentes['telephely_id']}'");
//
//                        $html .= nl2('
//                                    <li class="szamla_datum csoport'.$csoport_active.$nohover.'" data-id="'.$aktCsoport.'">
//                                        <span class="icon"></span>
//                                        <span class="csoportNev" data-id="'.$aktCsoport.'">
//                                            '.$surgos_icon.'<span class="icsopnev '.$bold.'">'.$csoportSor['nev'].'
//                                        </span>
//                                        <br>'.$telephely['nev'].'
//                                    </li>
//                                    ');
//                    }
//
//                    $csoport = $aktCsoport;
//                }
//            }

            $surgos = '';
            if( $bsz['surgos'] ) {
                $surgos = nl2('
                    <span class="icon piros">
                        <i class="material-icons">error</i>
                    </span>
                ');
            }

            $kesz = '';
            if( $bsz['stat_id'] == BEJELENTES_STAT_KESZ_IG ) {
                $kesz = nl2('
                    <span class="icon zold">
                        <i class="material-icons">done</i>
                    </span>
                ');
            }

            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            // Nyitott státuszú bejegyzések navpanelen félkövérrel jelenjenek meg
            $bold = '';
            if( $bsz['stat_id'] == BEJELENTES_STAT_NYITOTT ) {
                $bold    = ' class="b"';
                $active .= ' nyitott_statuszu'; // Innen fogjuk tudni, hogy a csoporosítás gombokat mutassuk-e meg vagy sem
            }

            // Egyszálloda esetén, ha nyitott a státusz, van munkalap készíthet jog, és ponstosan egy ML-ja van, és a ML-nak két státusza van (ez csak Nyitott és hozzárendelt lehet), akkor legyen szürke a háttér
            $rejtsuk = false;
            if( _jog(JOG_MUNKALAP_KESZITHET) && $bsz['stat_id'] == BEJELENTES_STAT_NYITOTT ) {
                $szalloda  = $adatbazis_kapcsolat->s2('modell', 'telephely', "`id`='{$bsz['telephely_id']}'");
                if( !$szalloda['modell'] ) {
                    $van_kapcs = $adatbazis_kapcsolat->s('munkalap_id', 'munkalap_kapcs', "`bejelentes_id`='{$bsz['recid']}'");
                    if( count($van_kapcs) == 1 ) {
                        $letezik_munkalap = $adatbazis_kapcsolat->s2('recid', 'munkalap', "`recid`='{$van_kapcs[0]['munkalap_id']}'");
                        if( $letezik_munkalap ) {
                            $statuszok = $adatbazis_kapcsolat->s('recid', 'munkalap_statusz', "`munkalap_recid`='{$letezik_munkalap['recid']}'");
                            if( count($statuszok) == 2 ) {
                                $active .= ' szurke_hat';
                                $rejtsuk = true;
                            }
                        }
                    }
                }
            }

            // Korábban szürke színnel voltak kiírva azok a nyitott bejelentések, amikhez tartozott munkalap, de ezeket végül teljesen elrejtjük.
            if( $rejtsuk ) {
                continue;
            }

            // Van-e csatolt dokumentuma
            $dok_icon = '';
            if( $bsz['dok_db'] ) {
                $dok_icon = nl2('
                    <span class="icon">
                        <i class="material-icons">attach_file</i>
                    </span>
                ');
            }

            // Ha a usernek nem csak egy szállodája van, akkor kiírjuk a szálloda nevét
            $szalloda_neve = '';
            if(!$_SESSION['egyszallodas_user']){
                $szalloda_neve = getSzallodaNev($bsz['telephely_id']).' - ';
            }

            $li_tart = '<a class="szamla'.$nohover.'" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit" title="'.$bsz['hiba_text'].'">
                            <table'.$bold.'>
                            <tr>
                                <td class="ikonok" rowspan="2" style="width: 4rem">'.$kesz.$dok_icon.$surgos.'</td>
                                <td style="width: 9rem">'.$bsz['ikt_szam'].'</td>
                                <td style="width: 9rem">'.getStatText($tabla, $bsz['stat_id']).'</td>
                                <td style="width: 7rem">'.str_replace('-', '.', substr($bsz['insert_dt'], 0, 16) ).'</td>
                                <td>'.$szalloda_neve.htmlspecialchars_decode(htmlspecialchars_decode($bsz['obj_txt'])).'</td>
                            </tr>
                            <tr>
                                <td colspan="4">'.getErtekLista('', '', $bsz['hiba_id']).'</td>
                            </tr>
                            </table>
                         </a>';
// Csoportosítás megszűnt
//            $csoport_class = '';
//            $csoport_data  = '';
//            if( $bsz['csoport_id'] && _jog(JOG_MUNKALAP_KESZITHET) ) {
//                $csoport_class = ' behuz dn';
//
//                $akt_csoport = $adatbazis_kapcsolat->s('csoport_id', $tabla, "`recid` = '{$_GET['id']}'", '', '1');
//
//                if( $akt_csoport ) {
//                    $akt_csoport = $akt_csoport[0];
//
//                    if( $akt_csoport['csoport_id'] == $bsz['csoport_id'] ) {
//                        $csoport_class = ' behuz';
//                    }
//                }
//
//                $csoport_data = ' data-csoportID="'.$bsz['csoport_id'].'"';
//            }

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if($frissit){
                // $active .= ' kijelolve';
            }

            $style = '';
            if( $bsz['stat_id'] == BEJELENTES_STAT_VARAKOZO ) {
                if( $szinkod = getStatText('bejelentes', BEJELENTES_STAT_VARAKOZO, true) ) {
                    $style = ' style="background: '.$szinkod.';"';
                }
            }

            // Ha sürgős, akkor legyen piros a sora
            $surgos_class = '';
            if($bsz['surgos']){
                $surgos_class = ' surgos';
            }

            // Ha van kívánt sorrend információnk is, akkor ezt tegyük be egy data-sorrend tag-be
            $sorrend = '';
            if($js_rendezes){
                $sorrend = ' data-sort="'.array_search($bsz['recid'], $sorrend_idk).'"';
            }

            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.$surgos_class.'" data-recid="'.$bsz['recid'].'" data-telephely_id="'.$bsz['telephely_id'].'"'.$sorrend.' data-i="'.$i.'"'.$style.'>'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        // Ha kaptunk sorrendiséget, akkor JS-el állítjuk be a sorrendet, hogy ne az SQL-t terheljük
        if($js_rendezes){
            $html .= nl2('
                <script>
                    $(document).ready(function () {
                        var inverse = false;

                        $(\'#szamlaList li\').sortElements(function(a, b) {
                            var at = $.text([a]),
                                bt = $.text([b]);

                            if( $(a).attr(\'data-sort\') !== undefined ) {
                                var at = $(a).attr(\'data-sort\'),
                                    bt = $(b).attr(\'data-sort\');
                            }

                            if( isNumeric(at) && isNumeric(bt) ) {
                                at = parseInt(at);
                                bt = parseInt(bt);

                                return at > bt ? inverse ? -1 : 1 : inverse ? 1 : -1;
                            } else {
                                return inverse ? bt.localeCompare(at) : at.localeCompare(bt);
                            }
                        });

                        function isNumeric(n) {
                            return !isNaN(parseFloat(n)) && isFinite(n);
                        }

                        /* data-i újraszámozása */
                        var data_i = 1;
                        $(\'#szamlaList li\').each(function () {
                            $(this).attr(\'data-i\', data_i);
                            data_i++;
                        });

                    });
                </script>
                ');
        }

        return nl2($html);
    }

    return false;
}

function munkalapLista( $bejegyzesek, $tabla, $frissit, $kijeloltek, $sorrend_idk) {
    global $adatbazis_kapcsolat;

    // Legyen-e JS rendezés, vagy sem?
    $js_rendezes = false;
    if( $sorrend_idk && (!$_GET['rend'] || $_GET['rend']=='stat_id') && !$_GET['szamlaSorFrissit'] && !$_GET['ajax'] ){
        $js_rendezes = true;
    }

    if( $bejegyzesek ) {
        $csoport = $bejegyzesek[0]['csoport_id'];

        $jClass         = '';
        if( _jog(JOG_MUNKALAP_HATIDO_UTEMEZ) && !isset($_GET['print']) ) {
            // $jClass = ' kijelolheto'; //2017.11.16 - nem kell kijelölhetőek legyenek a munkalapok
            $jClass = ' csoport_nevre_kattintas';
        }

        // Ha nem adunk meg $tabla-t, akkor nyomtatási nézetben vagyunk
        $nohover = '';
        if( !$tabla ) {
            $nohover = ' nohover';
        }
        
        // Szükség lesz a login max jóváhagyó értékére, hogy azokat a ML-okat, aminek magasabb a jóváhagyott értéke, ki se listázzuk
        $login = $adatbazis_kapcsolat->s2('jh_ft_ig', 'login', "`recid`='{$_SESSION[get_db_tabla().'login_recid']}'");

        if( !$frissit ) {

            $stat_select = '';
            $statuszok_abc = array();
            $statuszok = $adatbazis_kapcsolat->s('distinct stat_id', $tabla, "`stat_id`!=''");
            $statuszok = adatbazis2tomb($statuszok, 'stat_id');
            foreach($statuszok as $stat){
                $text = getStatText($tabla, $stat);
                if( $text ) {
                    array_push($statuszok_abc, array('id' => $stat, 'nev' => $text));
                }
            }
            $statuszok_abc = tobbdim_tomb_kulcs_rendez($statuszok_abc, 'nev');
            foreach ( $statuszok_abc as $stat ) {
                $selected = '';
                if( $_GET['mezo'] == 'stat_id' && $_GET['felt'] == $stat['id'] ) {
                    $selected = ' selected';
                }
                $stat_select .= '<option'.$selected.' value="'.$stat['id'].'">'.$stat['nev'].'</option>';
            }
            if( $stat_select ) {
                $stat_select = '<div id="stat_gyors_szuro_div"><select id="stat_gyors_szuro" style="margin: 0.2rem 0 0 0.2rem;" onchange="if(this.value){ param = \'&amp;mezo=stat_id&amp;op=equal&amp;felt=\'+this.value; } else { param = \'\'; } szurkit(); location.href=\''.self().get_get(array('mezo', 'op', 'felt')).'\'+param"><option value="">-Összes státusz-</option>'.$stat_select.'</select></div>';
            }
            $html = $stat_select.'<ul id="szamlaList" class="szamla_cont nobehuzas rendszer'.$jClass.'" style="margin-top: 3.2rem; backgorund: #F4F6F5">';
        }

        $i = 1;
        foreach ( $bejegyzesek as $bsz ) {
//            if( false/* Csoportra bontás kikapcsolva 2017.12.11 */ && !$frissit && $tabla && _jog(JOG_MUNKALAP_HATIDO_UTEMEZ) && !isset($_GET['print']) ) {
//                $aktCsoport = $bsz['csoport_id'];
//
//                if( $i == 1 || $csoport != $aktCsoport ) {
//                    $csoportSor = $adatbazis_kapcsolat->s2('nev', $tabla.'_csoport', 'recid='.$aktCsoport, '', '1');
//
//                    if( $csoportSor ) {
//
//                        // Van e sürgős
//                        $surgos_e = $adatbazis_kapcsolat->s('recid', $tabla, "`csoport_id` = '{$aktCsoport}' AND `surgos` > 0", '', '1');
//                        $surgos_icon = '';
//                        if( $surgos_e ) {
//                            $surgos_icon = nl2('
//                                <span class="icon bej piros">
//                                    <i class="material-icons">error</i>
//                                </span>
//                            ');
//                        }
//
//                        // Ha egy csoport elemén vagyunk, akkor a ki-be nyitó ikon "-"-on legyen
//                        $csoport_bej = $adatbazis_kapcsolat->s('recid', $tabla, "`csoport_id` = '{$aktCsoport}'");
//                        $csoport_active = '';
//                        if( $csoport_bej ) {
//                            $csoport_bej = adatbazis2tomb($csoport_bej, 'recid');
//                            if( in_array($_GET['id'], $csoport_bej) ) {
//                                $csoport_active = ' nyitva';
//                            }
//                        }
//
//                        $html .= nl2('
//                                    <li class="szamla_datum csoport'.$csoport_active.'" data-id="'.$aktCsoport.'">
//                                        <span class="icon"></span>
//                                        <span class="csoportNev" data-id="'.$aktCsoport.'">
//                                        '.$surgos_icon.'
//                                        <strong class="icsopnev">'.$csoportSor['nev'].'</strong>
//                                    </li>
//                                    ');
//                    }
//
//                    $csoport = $aktCsoport;
//                }
//            }

            // Ha a státusz döntésre vár, és a felhasználónak túl alacsony a jóváhagyható limite, akkor meg se jelenítjük, nem is számolunk tovább
            if( $bsz['stat_id'] == MUNKALAP_STAT_DONTESRE_VAR && $bsz['jh_ert'] > $login['jh_ft_ig'] ) {
                continue;
            }

            // Sűrgős pipa
            $surgos = '';
            if( $bsz['surgos'] ) {
                $surgos = nl2('
                    <span class="icon piros">
                        <i class="material-icons">error</i>
                    </span>
                ');
            }

            // Kész pipa
            $kesz = '';
            if( $bsz['stat_id'] == MUNKALAP_STAT_LEZARASRA_VAR ) {
                $kesz = nl2('
                    <span class="icon zold">
                        <i class="material-icons">done</i>
                    </span>
                ');
            }

            $statusz = '';
            if( $bsz['stat_id'] ) {
                $statusz = getStatText($tabla, $bsz['stat_id']);
            }

            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            } else if( is_array($kijeloltek) && in_array($bsz['recid'], $kijeloltek) ) {
                $active = ' kijelolve';
            }

            // Nyitott és előkészítés alatt státuszú bejegyzések plus class-t kapnak
            if( $bsz['stat_id'] == MUNKALAP_STAT_NYITOTT || $bsz['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT ){
                $active .= ' nyitott_statuszu'; // Innen fogjuk tudni, hogy a csoporosítás gombokat mutassuk-e meg vagy sem
            }

            // Félkövérítések:
            // Nyitott + nyitottak kezelése jog
            $bold = '';
            if( $bsz['stat_id'] == MUNKALAP_STAT_NYITOTT && _jog(JOG_MUNKALAP_NYITOTT_KEZEL) ){
                $bold = ' class="b"';
            }

            // Hozzárendelt + kezelő jog
            if( $bsz['stat_id'] == MUNKALAP_STAT_HOZZARENDELT && get_kezeles_jog($bsz['szemelyzet_id']) ){
                $bold = ' class="b"';
            }

            // Előkészítés alatt + előkészítés kezelő jog + ML szerepkör=Login személyzet
            if( $bsz['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT && _jog(JOG_MUNKALAP_KULSOS_ELOKESZIT) && $_SESSION[get_db_tabla().'szemelyzet_id']==$bsz['szemelyzet_id'] ){
                $bold = ' class="b"';
            }

            // Személyzettípus kiíratása
            require 'selectek_szemelyzet.php';

            $szemelyzet = '';
            if( $bsz['szallito_id'] ) {
                $szemelyzet = getErtek('nev', 'szallito', 'id', $bsz['szallito_id']);
            } elseif( $bsz['szemelyzet_id'] ) {

                // Ha a személyzettípus külső kivitelező, akkor a külső cég neve jelenjen meg
//                if($bsz['szemelyzet_id']==KULSO_KIVITELEZO){
//                    $szemelyzet = getErtek('nev', 'szallito', 'id', $bsz['szallito_id']);
//                } else {
                    foreach ( $select_szemelyzet as $szemely ) {
                        if( $szemely['value'] == $bsz['szemelyzet_id'] ) {
                            $szemelyzet = $szemely['name'];
                        }
                    }
//                }

            }

            // Mobilcsoport esetén látszódjon a betűjel is
            if( $bsz['szemelyzet_id'] == MOBILCSOPORT ) {
                $szemelyzet .= ' '.strtoupper($bsz['mobil']).'';
            }

            // Van-e csatolt dokumentuma
            $dok_icon = '';
            if( $bsz['dok_db'] ) {
                $dok_icon = nl2('
                    <span class="icon">
                        <i class="material-icons">attach_file</i>
                    </span>
                ');
            }

            // Ha a munkalap típusa Hibaelhárítás, akkor a bejelentés hibáját jelenítsük meg röviden
            if( $bsz['tipus_id']==MLTIPUS_HIBAELHARITAS && $bsz['hiba_txt'] ){
                $hossz = 14;
                if(mb_strlen($bsz['hiba_txt'])>$hossz){
                    $hiba_rovid = mb_substr($bsz['hiba_txt'], 0, 14, get_web_charset()).'...';
                } else {
                    $hiba_rovid = $bsz['hiba_txt'];
                }
                $ml_tipus = '<span title="'.$bsz['hiba_txt'].'">'.$hiba_rovid.'</span>';
            } else {
                // Munkalap tipus szövegén rövidítsünk
                $ml_tipus = getErtekLista(ERTEKLISTA_MUNKALAP_TIP, $bsz['tipus_id']);
                $ml_tipus = str_replace(array('Ütemezett feladat',  'Eseti fenntartás', 'Rendszeres kbt.', 'Üzemeltetési feladat'),
                                        array('Ütemezett',          'Eseti',            'Rendsz.Karb.',    'Üzemeltetés'         ),
                                        $ml_tipus);
            }
            
            // Title-ba kerüljön a ML hiba leírása. Ha van hozzá bejelentés, akkor meg a bejelentésé
            $title = $bsz['text'];
            $bejelentes = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$bsz['recid']}'");
            if( $bejelentes ){
                $bejelentes = $adatbazis_kapcsolat->s2('hiba_text', 'bejelentes', "`recid`='{$bejelentes['bejelentes_id']}'");
                if( $bejelentes ){
                    $title = $bejelentes['hiba_text'];
                }
            }

            // Ha van gép, csak akkor legyen kötőjel
            $gep_txt = '';
            if($bsz['gep_txt']){
                $gep_txt = ' - '.$bsz['gep_txt'];
            }

            // Ha a usernek nem csak egy szállodája van, akkor kiírjuk a szálloda nevét
            $szalloda_neve = '';
            $colspan       = '';
            if(!$_SESSION['egyszallodas_user']){
                $szalloda_neve = '<td style="width: 7rem;">'.getSzallodaNev($bsz['telephely_id']).'</td>';
                $colspan       = ' colspan="2"';
            }

            $li_tart = '<a class="szamla'.$nohover.'" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit" title="'.$title.'">
                            <table'.$bold.'>
                            <tr>
                                <td class="ikonok" rowspan="2" style="width: 4rem; white-space: nowrap;">
                                    <br>'.$kesz.$dok_icon.$surgos.'&nbsp;
                                </td>
                                <td style="width: 11rem;" class="ikt_szam">'.$bsz['ikt_szam'].'</td>
                                <td style="width: 9rem;">'.getStatText($tabla, $bsz['stat_id'], 'rovid').'</td>
                                '.$szalloda_neve.'
                                <td>'.htmlspecialchars_decode(htmlspecialchars_decode($bsz['obj_txt'])).$gep_txt.'</td>
                            </tr>
                            <tr>
                                <td>'.$ml_tipus.'</td>
                                <td'.$colspan.'>'.datum_kiir($bsz['insert_dt']).'</td>
                                <td>'.$szemelyzet.'</td>
                            </tr>
                            </table>
                         </a>';

//            $csoport_class = '';
//            $csoport_data  = '';
//            if(false && $bsz['csoport_id'] && _jog(JOG_MUNKALAP_HATIDO_UTEMEZ) && !isset($_GET['print']) ) {
//                $csoport_class = ' behuz';
//                if(!$frissit){
//                    $csoport_class .= ' dn';
//                }
//
//                $akt_csoport = $adatbazis_kapcsolat->s2('csoport_id', $tabla, "`recid` = '{$_GET['id']}'", '', '1');
//                if( $akt_csoport['csoport_id'] == $bsz['csoport_id'] ) {
//                    $csoport_class = ' behuz';
//                }
//
//                $csoport_data = ' data-csoportID="'.$bsz['csoport_id'].'"';
//            }

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
//            if($frissit){
//                // $active .= ' kijelolve';
//            }

            // Ha sürgős, akkor legyen piros a sora
            $surgos_class = '';
            if($bsz['surgos']){
                $surgos_class = ' surgos';
            }

            // Ha van kívánt sorrend információnk is, akkor ezt tegyük be egy data-sorrend tag-be
            $sorrend = '';
            if($js_rendezes){
                $sorrend = ' data-sort="'.array_search($bsz['recid'], $sorrend_idk).'"';
            }

            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.$surgos_class.'" data-recid="'.$bsz['recid'].'" data-telephely_id="'.$bsz['telephely_id'].'"'.$sorrend.' data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        // Ha kaptunk sorrendiséget, akkor JS-el állítjuk be a sorrendet, hogy ne az SQL-t terheljük
        if($js_rendezes){
            $html .= nl2('
                <script>
                    $(document).ready(function () {
                        var inverse = false;

                        $(\'#szamlaList li\').sortElements(function(a, b) {
                            var at = $.text([a]),
                                bt = $.text([b]);

                            if( $(a).attr(\'data-sort\') !== undefined ) {
                                var at = $(a).attr(\'data-sort\'),
                                    bt = $(b).attr(\'data-sort\');
                            }

                            if( isNumeric(at) && isNumeric(bt) ) {
                                at = parseInt(at);
                                bt = parseInt(bt);

                                return at > bt ? inverse ? -1 : 1 : inverse ? 1 : -1;
                            } else {
                                return inverse ? bt.localeCompare(at) : at.localeCompare(bt);
                            }
                        });

                        function isNumeric(n) {
                            return !isNaN(parseFloat(n)) && isFinite(n);
                        }

                        /* data-i újraszámozása */
                        var data_i = 1;
                        $(\'#szamlaList li\').each(function () {
                            $(this).attr(\'data-i\', data_i);
                            data_i++;
                        });
                    });
                </script>
                ');
        }

        return nl2($html);
    }

    return false;
}

function utemezett_feladatokLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach ( $bejegyzesek as $bsz ) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            // Telephely
            $telephely = getSzallodaNev($bsz['telephely_id']);

            // Helység
            $helyseg = $adatbazis_kapcsolat->s2('nev', 'obj', 'recid='.$bsz['obj_id'], '', '1');
            $helyseg = $helyseg['nev'];
            
            // Gép
            $gep = $adatbazis_kapcsolat->s2('nev', 'eszkozkezeles', 'recid='.$bsz['gep_id'], '', '1');
            $gep = $gep['nev'];

            $aktiv = nl2('
                <span class="icon piros">
                    <i class="material-icons">clear</i>
                </span>
            ');
            if( $bsz['aktiv'] ) {
                $aktiv = nl2('
                    <span class="icon zold">
                        <i class="material-icons">done</i>
                    </span>
                ');
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$aktiv.' '.$bsz['id'].' '.str_replace('-', '.', $bsz['esnap_d']).'</span>
                            <span>'.trim($telephely.' - '.$helyseg.' - '.$gep, ' - ').'</span>
                            <span>'.$bsz['uf_nev'].'</span>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if( $frissit ) {
                // $active .= ' kijelolve';
            }
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= nl2('
                </ul>
            ');
        }

        return nl2($html);
    }

    return false;
}

function rendszeres_karbantartasLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        $naptar_dn = 'dn';
        $lista_dn  = '';
        if (isset($_GET['naptar'])) {
            $naptar_dn = '';
            $lista_dn  = ' dn';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$lista_dn.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            // Telephely
            $telephely = getSzallodaNev($bsz['telephely_id']);

            // Helység
            $helyseg = $adatbazis_kapcsolat->s2('nev', 'obj', 'recid='.$bsz['obj_id'], '', '1');
            $helyseg = $helyseg['nev'];
            
            // Gép
            $gep = $adatbazis_kapcsolat->s2('nev', 'eszkozkezeles', 'recid='.$bsz['gep_id'], '', '1');
            $gep = $gep['nev'];

            $aktiv = nl2('
                <span class="icon piros">
                    <i class="material-icons">clear</i>
                </span>
            ');
            if( $bsz['aktiv'] ) {
                $aktiv = nl2('
                    <span class="icon zold">
                        <i class="material-icons">done</i>
                    </span>
                ');
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$aktiv.' '.$bsz['id'].'</span>
                            <span>'.trim($telephely.' - '.$helyseg.' - '.$gep, ' - ').'</span>
                            <span>'.$bsz['uf_nev'].'</span>
                        </a>';


            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if($frissit){
                // $active .= ' kijelolve';
            }
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= nl2('
                </ul>
                <div id="naptar" class="'.$naptar_dn.'">
                    <div class="naptar"></div>
                </div>
            ');
        }

        return nl2($html);
    }

    return false;
}

function eseti_fenntartasLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        $naptar_dn = 'dn';
        $lista_dn  = '';
        if (isset($_GET['naptar'])) {
            $naptar_dn = '';
            $lista_dn  = ' dn';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$lista_dn.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            // Telephely
            $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$bsz['telephely_id'], '', '1');
            $telephely = $telephely[0]['nev'];

            // Helység
            $helyseg = $adatbazis_kapcsolat->s('nev', 'obj', 'recid='.$bsz['obj_id'], '', '1');
            $helyseg = $helyseg[0]['nev'];

            $aktiv = nl2('
                <span class="icon piros">
                    <i class="material-icons">clear</i>
                </span>
            ');
            if( $bsz['aktiv'] ) {
                $aktiv = nl2('
                    <span class="icon zold">
                        <i class="material-icons">done</i>
                    </span>
                ');
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$aktiv.' '.$bsz['id'].'</span>
                            <span>'.$telephely.' - '.$helyseg.'</span>
                            <span>'.$bsz['uf_nev'].'</span>
                        </a>';


            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if( $frissit ) {
                // $active .= ' kijelolve';
            }
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= nl2('
                </ul>
                <div id="naptar" class="'.$naptar_dn.'">
                    <div class="naptar"></div>
                </div>
            ');
        }

        return nl2($html);
    }

    return false;
}

function fenntartasi_szamlaLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach ( $bejegyzesek as $bsz ) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <table>
                            <tr> 
                                <td style="width: 8rem">'.$bsz['szla_szam'].'</td>
                                <td>'. number_format($bsz['netto'], 0, '', '').' '. get_penznem().'</td>
                            </tr>
                            <tr> 
                                <td colspan="2">'.getErtek('nev', 'szallito', 'id', $bsz['szallito_id']).'</td>
                            </tr>
                            <tr> 
                                <td colspan="2">'.resz_string($bsz['targy'], 60).'</td>
                            </tr>
                            </table>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function fenntartasi_szerzodesLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach ( $bejegyzesek as $bsz ) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <table>
                            <tr> 
                                <td style="width: 12rem">'.$bsz['szszam'].'</td>
                                <td>'.datum_kiir($bsz['bef_dt']).'</td>
                            </tr>
                            </table>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function fenntartasi_tervLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <table>
                            <tr> 
                                <td style="width: 8rem">'.getSzallodaNev($bsz['telephely_id']).'</td>
                                <td style="width: 4rem">'.$bsz['ev'].'</td>
                                <td>'.getStatText('energia_terv', $bsz['stat_id']).'</td>
                            </tr>
                            <tr>
                                <td colspan="3">'.$bsz['text'].'</td>
                            </tr>
                            </table>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if( $frissit ) {
                // $active .= ' kijelolve';
            }
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function energia_tervLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <table>
                            <tr>
                                <td style="width: 7rem">'.$bsz['ikt_szam'].'</td>
                                <td style="width: 3.6rem">'.$bsz['ev'].'</td>
                                <td>'.getStatText('energia_terv', $bsz['stat_id']).'</td>
                            </tr>
                            <tr>
                                <td colspan="3">'.$bsz['text'].'</td>
                            </tr>
                            </table>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if($frissit){
                // $active .= ' kijelolve';
            }
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function anyagigenylesLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        // Anyagrendelés esetén más listát generálunk ki (rndid alapján group by-oztuk) Anyagrend mod: 2018.04
        // if(isset($_GET['anyagrend'])){
        //
        //     $jClass = '';
        //     // Anyagrendelés készíthet jöghöz van kötve a többszörös kijelölés (Anyagrend alatt is?)
        //     if( _jog(JOG_ANYAGREND_KESZIT) ) {
        //         $jClass = ' kijelolheto csoport_nevre_kattintas';
        //     }
        //
        //     if( !$frissit ) {
        //         $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$jClass.'">';
        //     }
        //
        //     $i = 1;
        //     foreach($bejegyzesek as $bsz) {
        //
        //         $active = '';
        //         if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
        //             $active = ' kijelolve';
        //         }
        //
        //         $azon = $bsz['ikt_szam'];
        //         if (isset($_GET['anyagrend'])) {
        //             $azon = $bsz['rndid'];
        //         }
        //
        //         $bold = '';
        //         if( $bsz['stat_id'] == CIKKIGN_STAT_NYITOTT ){
        //             $bold = ' class="b"';
        //         }
        //
        //         $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
        //                         <span'.$bold.'>'.$azon.' - '.str_replace('-', '.', $bsz['rnd_dt']).'</span>
        //                         <span'.$bold.'>'.getStatText('anyagigenyles', $bsz['stat_id']).'</span>
        //                     </a>';
        //
        //         // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
        //         if($frissit){
        //             // $active .= ' kijelolve';
        //         }
        //         $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
        //
        //
        //         $i++;
        //     }
        //
        //     if( !$frissit ) {
        //         $html .= '</ul>';
        //     }

        // Anyagigényléseknél egy táblázatot rajzolunk ki
        // } else {

            // Anyagigénylés jóváhagyás jog (Igazgatók) esetén látszik csak a jóváhagyási érték
            $jhertek_latszik = ' dn';
            if (_jog(JOG_ANYAGIGENYLES_JOVAHAGY)) {
                $jhertek_latszik = '';
            }

            $oszlopok = array(
                array('id' => 'anyag_tabla_statusz',        'nev' => 'Státusz'),
                array('id' => 'anyag_tabla_ikt_szam',       'nev' => 'Anyagig. szám'),
                array('id' => 'anyag_tabla_szalloda',       'nev' => 'Szálloda'),
                array('id' => 'anyag_tabla_cikkszam',       'nev' => 'Cikkszám'),
                array('id' => 'anyag_tabla_cikknev' ,       'nev' => 'Cikk név'),
                array('id' => 'anyag_tabla_igleir' ,        'nev' => 'Leírás'),
                array('id' => 'anyag_tabla_szallito',       'nev' => 'Szállító'),
                array('id' => 'anyag_tabla_ig_menny',       'nev' => 'Igényelt mennyiség'),
                array('id' => 'anyag_tabla_mee',            'nev' => 'Mee'),
                array('id' => 'anyag_tabla_ig_datum',       'nev' => 'Igénylés dátuma'),
                array('id' => 'anyag_tabla_jovah_menny',    'nev' => 'Jóváhagyott mennyiség'),
                array('id' => 'anyag_tabla_jovah_ertek',    'nev' => 'Jóváhagyásra váró érték ('.get_penznem().')', 'class' => $jhertek_latszik),
                array('id' => 'anyag_tabla_rend_menny',     'nev' => 'Rendelt mennyiség'),
                array('id' => 'anyag_tabla_rend_egysegar',  'nev' => 'Rendelési egységár ('.get_penznem().')'),
                array('id' => 'anyag_tabla_dev_rend',       'nev' => 'Deviza rend. egységár'),
                array('id' => 'anyag_tabla_penznem',        'nev' => 'Pénzn'),
                array('id' => 'anyag_tabla_rbs',            'nev' => 'RB-s anyag'),
            );

            if( isset($_GET['anyagrend']) ) {
                array_push($oszlopok,
                    array('id' => 'anyag_tabla_rndid',      'nev' => 'Rendelés szám'        ),
                    array('id' => 'anyag_tabla_rnd_dt',     'nev' => 'Rendelés dátum'       ),
                    array('id' => 'anyag_tabla_beerkezett', 'nev' => 'Beérkezés dátuma'     ),
                    array('id' => 'anyag_tabla_rndertek',   'nev' => 'Rendelés érték'       ),
                    array('id' => 'anyag_tabla_beerkertek', 'nev' => 'Beérkezett érték'     ),
                    array('id' => 'anyag_tabla_erkmen',     'nev' => 'Beérkezett mennyiség' ),
                    array('id' => 'anyag_tabla_szlamen',    'nev' => 'Számla mennyiség'     )
                );
            }

            // Anyagrendelés készíthet joghoz van kötve a többszörös kijelölés
            if( _jog(JOG_ANYAGREND_KESZIT) && !_jog(JOG_ANYAGIGENYLES_JOVAHAGY) ) {
                $jClass = ' kijelolheto';
            }

            $data_tabla = $tabla;
            if(isset($_GET['anyagrend'])) {
                $data_tabla = 'anyagrendeles';
            }

            $sorrend = getErtek('tabla_sorrend', 'login', 'recid', $_SESSION[get_db_tabla().'login_recid']);

            $oszlopok_sorrend = array();
            if( $sorrend ) {
                $sorrend = json_decode(htmlspecialchars_decode($sorrend), true);

                if( $sorrend[$data_tabla] ) {
                    foreach($sorrend[$data_tabla] as $key => $value) {
                        foreach($oszlopok as $key => $val) {
                           if( $val['id'] === $value['id']) {
                               $val['width'] = $value['width'];

                               // Még véletlen se szerepelhessen egy oszlop benne duplán
                               $mar_benne_van = false;
                               foreach($oszlopok_sorrend as $oszl_s){
                                   if($oszl_s['id']==$val['id']){
                                       $mar_benne_van = true;
                                   }
                               }
                               if(!$mar_benne_van){
                                    $oszlopok_sorrend[] = $val;
                               }
                           }
                       }
                    }

                    // Ha olyan oszlop is van, ami adott felhasználónál még nincs letárolva, akkor alapértelmezett szélességgel kerüljön be
                    foreach ( $oszlopok as $oszl ) {
                        $van_talalat = false;
                        foreach ( $oszlopok_sorrend as $oszl_s ) {
                            if( $oszl_s['id'] == $oszl['id'] ) {
                                $van_talalat = true;
                                continue;
                            }
                        }
                        if( !$van_talalat ) {
                            $val['id']      = $oszl['id'];
                            $val['nev']     = $oszl['nev'];
                            $val['width']   = 40;
                            $oszlopok_sorrend[] = $val;
                        }
                    }

                    $oszlopok = $oszlopok_sorrend;
                }
            }

            if( !$frissit ) {
                $html .= nl2('
                        <table id="dragTable" class="sar-table szamla_cont_tabla'.$jClass.'" data-tabla="'.$data_tabla.'">
                        <thead>
                            <tr>');

                foreach ( $oszlopok as $oszl ) {

                    $order = '';
                    if($oszl['id']==$_SESSION['anyagigenyles_tablazat_rendez']){
                        if($_SESSION['anyagigenyles_tablazat_irany']=='desc'){
                            $order = ' desc';
                        } else {
                            $order = ' asc';
                        }
                    }

                    $width = '';
                    if( $oszl['width'] ) {
                        $width = ' style="min-width: '.$oszl['width'].'px; max-width: '.$oszl['width'].'px; width: '.$oszl['width'].'px;"';
                    }

                    // Class hozzáadása
                    $class = '';
                    if($oszl['class']){
                        $class = ' class="'.$oszl['class'].'"';
                    }

                    $html .= nl2('
                        <th id="'.$oszl['id'].'"'.$class.$width.'>
                            <div class="thKeret">
                                <div class="some-handle"></div>
                                <div class="order'.$order.'"></div>
                                <span>'.$oszl['nev'].'</span>
                                <!-- <input type="text" name="tableSearch" value="">
                                <label>
                                    <input type="checkbox" name="nowrap" value="">
                                    <span>Ne tördelje</span>
                                </label> -->
                            </div>
                        </th>
                    ');
                }
                $html .= nl2('</tr>
                        </thead>
                    <tbody>
                ');
                $html .= nl2('<tr class="fake">');
                foreach($oszlopok as $oszl) {
                    // Class hozzáadása
                    $class = '';
                    if($oszl['class']){
                        $class = ' class="'.$oszl['class'].'"';
                    }
                    $html .= nl2('<td'.$class.'></td>');
                }
                $html .= nl2('</tr>');
            }

            $i = 1;
            $rendelesi_ertek = 0;
            $eredeti_ertekek = array();
            foreach($bejegyzesek as $bsz) {

                // Rendelési érték számítás
                if( $bsz['stat_id'] == CIKKIGN_STAT_ARAZVA ) {
                    $rendelesi_ertek += $bsz['rndmen']*$bsz['rndear'];
                }

                $active = '';
                if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                    $active = ' kijelolve';
                }

                // Telephely
                $telephely = $adatbazis_kapcsolat->s2('nev', 'telephely', 'id='.$bsz['telephely_id'], '', '1');

                $bold = '';
                if( $bsz['stat_id'] == CIKKIGN_STAT_NYITOTT ) {
                    $bold = ' b';
                }

                // Anyagigénylés jog és Árazva státusz esetén a jóváhagyott mennyiség helyben szerkeszthető
                $jhmen = number_format($bsz['jhmen'], 2, ',', ' ');
                if ( $bsz['stat_id']==CIKKIGN_STAT_ARAZVA && _jog(JOG_ANYAGIGENYLES_JOVAHAGY)) {
                    $jhmen = '<input class="r f_num mod" data-tort="2" data-recid="'.$bsz['recid'].'" type="text" id="jhmen_'.$bsz['recid'].'" value="'.number_format($bsz['rndmen'], 2, ',', ' ').'" style="width: 7rem; font-size: 1.4rem;" onclick="select()">';
                }
                $eredeti_ertekek['jhmen_'.$bsz['recid']] = number_format($bsz['rndmen'], 2, ',', ' ');

                // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
                if( $frissit ) {
                    // $active .= ' kijelolve';
                }

                // Jóváhagyásra váró érték
                $jh_ert = $bsz['rndear']*$bsz['rndmen'];

                if( $bsz['kulsosnek'] ) {
                    $bsz['kulsosnek'] = '✓';
                } else {
                    $bsz['kulsosnek'] = '';
                }

                $html .= '<tr class="'.$active.$bold.'" onclick="$(\'#szamla-'.$bsz['recid'].'\').click(); $(\'.muveletek\').addClass(\'dn\');" data-recid="'.$bsz['recid'].'" data-i="'.$i.'" id="anyagig-sor-'.$bsz['recid'].'">';

                $sorok = array(
                    'anyag_tabla_statusz'       => '<td class="c" sorttable_customkey="'.$bsz['stat_id'].'">'.getStatText('anyagigenyles', $bsz['stat_id']).'</td>',
                    'anyag_tabla_ikt_szam'      => '<td class="c">'.$bsz['ikt_szam'].'</td>',
                    'anyag_tabla_szalloda'      => '<td class="c">'.$telephely['nev'].'</td>',
                    'anyag_tabla_cikkszam'      => '<td class="c">'.$bsz['cikk_id'].'</td>',
                    'anyag_tabla_cikknev'       => '<td>'.htmlspecialchars_decode($bsz['anyagigenyles_nev']).'</td>',
                    'anyag_tabla_igleir'        => '<td id="td_igleir_'.$bsz['recid'].'">'.htmlspecialchars_decode(nl2br2($bsz['igleir'])).'</td>',
                    'anyag_tabla_szallito'      => '<td>'.getErtek('nev', 'szallito', 'id', $bsz['szallito_id']).'</td>',
                    'anyag_tabla_ig_menny'      => '<td class="r nowrap" data-sort="'.$bsz['igmen'].'">'.number_format($bsz['igmen'], 2, ',', ' ').'</td>',
                    'anyag_tabla_mee'           => '<td class="c">'.str_replace('m3', 'm<sup>3</sup>', $bsz['cikkmee']).'</td>',
                    'anyag_tabla_ig_datum'      => '<td class="c">'.substr(str_replace('-', '.', $bsz['insert_dt']), 0, 10).'</td>',
                    'anyag_tabla_jovah_menny'   => '<td class="r nowrap" id="td_jhmen_'.$bsz['recid'].'" data-sort="'.$bsz['jhmen'].'">'.$jhmen.'</td>',
                    'anyag_tabla_jovah_ertek'   => '<td class="r nowrap'.$jhertek_latszik.'" data-sort="'.$jh_ert.'" id="jovah_ertek_sor_'.$bsz['recid'].'">'.number_format($jh_ert, 2, ',', ' ').'</td>',
                    'anyag_tabla_rend_menny'    => '<td class="r nowrap" data-sort="'.$bsz['rndmen'].'">'.number_format($bsz['rndmen'], 2, ',', ' ').'</td>',
                    'anyag_tabla_rend_egysegar' => '<td class="r nowrap" id="td_rndear_'.$bsz['recid'].'" data-sort="'.$bsz['rndear'].'">'.number_format($bsz['rndear'], 2, ',', ' ').'</td>',
                    'anyag_tabla_dev_rend'      => '<td class="r nowrap">'.number_format($bsz['vrndear'], 2, ',', ' ').'</td>',
                    'anyag_tabla_penznem'       => '<td class="c">'.erteklista(ERTEKLISTA_PENZNEM, $bsz['kcs_21']).'</td>',
                    'anyag_tabla_rbs'           => '<td class="c">'.$bsz['kulsosnek'].'</td>'
                );

                if( isset($_GET['anyagrend']) ) {
                    $sorok['anyag_tabla_rndid']         = '<td class="c">'.$bsz['rndid'].'</td>';
                    $sorok['anyag_tabla_rnd_dt']        = '<td class="c">'.substr(str_replace('-', '.', $bsz['rnd_dt']    ), 0, 10).'</td>';
                    $sorok['anyag_tabla_beerkezett']    = '<td class="c">'.substr(str_replace('-', '.', $bsz['beerkezett']), 0, 10).'</td>';
                    // A Rendelés érték a Rendelt mennyiség * Rendelési egységár (Ft) oszlopok szorzata
                    $rendeles_ertek = $bsz['rndmen']*$bsz['rndear'];
                    $sorok['anyag_tabla_rndertek']      = '<td class="c" data-sort="'.$rendeles_ertek.'">'.number_format($rendeles_ertek, 0, ',', ' ').' '.get_penznem().'</td>';
                    // A Beérkezett érték a Beérkezett mennyiség * Rendelési egységár (Ft) oszlopok szorzata
                    $beerkezett_ertek = $bsz['erkmen']*$bsz['rndear'];
                    $sorok['anyag_tabla_beerkertek']    = '<td class="c" data-sort="'.$beerkezett_ertek.'">'.number_format($beerkezett_ertek, 0, ',', ' ').' '.get_penznem().'</td>';
                    $sorok['anyag_tabla_erkmen']        = '<td class="c" data-sort="'.$bsz['erkmen'].'">'.number_format($bsz['erkmen'], 2, ',', ' ').'</td>';
                    $sorok['anyag_tabla_szlamen']       = '<td class="c" data-sort="'.$bsz['szlamen'].'">'.number_format($bsz['szlamen'], 2, ',', ' ').'</td>';
                }

                foreach ( $oszlopok as $oszl ) {
                    $html .= $sorok[$oszl['id']];
                }

                $html .= '</tr>';

                $i++;
            }

            if( !$frissit ) {

                // Anyagigénylés jóváhagyás jog (Igazgatók) esetén, ha anyagigénylés alatt vagyunk, a rendelési egységár alatt
                if ( _jog(JOG_ANYAGIGENYLES_JOVAHAGY) && !isset($_GET['anyagrend']) ) {

                    $html .= nl2('
                            <tfoot>
                            <tr>
                                <td class="b r" colspan="'.count($sorok).'" id="rend_ertek_td">
                                    Jóváhagyásra váró érték:
                                    <span id="rend_ertek" class="nowrap" style="margin-left: 2rem">'.number_format($rendelesi_ertek, 0, ',', ' ').' '. get_penznem().'</span>
                                </td>
                            </tr>
                            </tfoot>
                                ');
                }

                $html .= nl2('
                                </tbody>
                                </table>
                            ');
                $html .= nl2('
                            <script>
                                $(document).ready(function () {
                                    anyagig_eredeti_jhmen = JSON.parse(\''.json_encode($eredeti_ertekek).'\');
                                    jovahagyasra_varo_igazit();
                                });
                            </script>
                            ');
            }

            // A bal navpaneles listát is kirajzoljuk, mert léptetéshez, árazás popuphoz kell. Szóval ott lesz a forráskódban, de rejtve
            if( !$frissit ) {
                //
                if (!isset($_GET['teszt'])) {
                    $style = ' style="display: none"';
                }
                $html .= '<ul id="szamlaList" class="szamla_cont rendszer kijelolheto csoport_nevre_kattintas"'.$style.'>';
            }

            $i = 1;
            foreach($bejegyzesek as $bsz) {

                $active = '';
                if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                    $active = ' kijelolve';
                }

                $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                                '.$bsz['recid'].' - '.$bsz['ikt_szam'].'
                            </a>';

                // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
                if($frissit){
                    // $active .= ' kijelolve';
                }
                // Azt is jelöljük a class-ban, hogy árazva van-e már, mert ha igen, akkor mind jóváhagásánál így fogjuk bejárni a sorokat.
                if ( $bsz['stat_id']==CIKKIGN_STAT_ARAZVA && _jog(JOG_ANYAGIGENYLES_JOVAHAGY)) {
                    $active .= ' arazva';
                }
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

                $i++;
            }

            if( !$frissit ) {
                $html .= '</ul>';
            }

        // }

        return nl2($html);
    }

    return false;
}

function torzsadatLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['nev'].'</span>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if($frissit){
                // $active .= ' kijelolve';
            }
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function szabadsagigenyLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['recid'].'</span>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if($frissit){
                // $active .= ' kijelolve';
            }
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function beosztasLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            // Telephely
            $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$bsz['telephely_id'], '', '1');
            $telephely = $telephely[0]['nev'];

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['cim'].'</span>
                            <span>'.$telephely.' '.str_replace('-', '.', $bsz['tol']).' - '.str_replace('-', '.', $bsz['ig']).'</span>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if($frissit){
                // $active .= ' kijelolve';
            }
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function projektigenylesLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        $csoport = $bejegyzesek[0]['csoport_id'];

        // Ha nem adunk meg $tabla-t, akkor nyomtatási nézetben vagyunk
        $nohover = '';
        if(!$tabla){
            $nohover = ' nohover';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer nobehuzas">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {

            if( !$frissit && $tabla ) {
                $aktCsoport = $bsz['csoport_id'];

                if( $i == 1 || $csoport != $aktCsoport ) {
                    $csoportSor = $adatbazis_kapcsolat->s2('nev', $tabla.'_csoport', 'recid='.$aktCsoport, '', '1');
                    if( $csoportSor ) {
                        $csoport_bej = $adatbazis_kapcsolat->s('recid', $tabla, "`csoport_id` = '{$aktCsoport}'");

                        // Ha egy csoport elemén vagyunk, akkor a ki-be nyitó ikon "-"-on legyen
                        $csoport_bej = $adatbazis_kapcsolat->s('recid', $tabla, "`csoport_id` = '{$aktCsoport}'");
                        $csoport_active = '';
                        if( $csoport_bej ) {
                            $csoport_bej = adatbazis2tomb($csoport_bej, 'recid');
                            if( in_array($_GET['id'], $csoport_bej) ) {
                                $csoport_active = ' nyitva';
                            }
                        }

                        $html .= nl2('
                                    <li class="szamla_datum csoport'.$csoport_active.'" data-id="'.$aktCsoport.'">
                                        <span class="icon"></span>
                                        <span class="csoportNev" data-id="'.$aktCsoport.'">
                                        <strong class="icsopnev">'.$csoportSor['nev'].'</strong>
                                    </li>
                                    ');
                    }

                    $csoport = $aktCsoport;
                }
            }

            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            $statusz = '';
            if( $bsz['stat_id'] ) {
                $statusz = getStatText($tabla, $bsz['stat_id']);
            }

            // Nyitott státuszú bejegyzések navpanelen félkövérrel jelenjenek meg
            $bold = '';
            if( $bsz['stat_id'] == PRIGENY_STAT_NYITOTT ){
                $bold    = ' class="b"';
                $active .= ' nyitott_statuszu';
            }

            // Egy csoporton belül biztosan csak olyan bejelentések lesznek, amiknek azonos a telephelye
            $telephely = getSzallodaNev($bsz['telephely_id']);

            require 'selectek.php';
            foreach($select_pri_tipus as $tip){
                if($bsz['tipus'] == $tip['value']){
                    $tipus = substr($tip['name'], 0, 1);
                }
            }

            if($bsz['rendk']){
                $tipus = '<span class="piros">'.$tipus.'</span>';
            }

            $li_tart = '<a class="szamla'.$nohover.'" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <table'.$bold.'>
                            <tr>
                                <td class="ikonok" rowspan="2" style="width: 2rem; text-align: center;">'.$tipus.'</td>
                                <td style="width: 15rem;">'.$bsz['ikt_szam'].'</td>
                                <td style="width: 17rem; text-align: left">'.$telephely.'</td>
                                <td style="text-align: left">'.$statusz.'</td>
                            </tr>
                            <tr>
                                <td style="white-space: pre-wrap;" colspan="2">'.$bsz['nev'].'</td>
                                <td>'.number_format($bsz['bkolt'], 0, '', ' ').' '.get_penznem().'</td>
                            </tr>
                            </table>
                        </a>
                        ';

            $csoport_class = '';
            $csoport_data = '';
            if( $bsz['csoport_id'] ) {
                $csoport_class = ' behuz';
                if(!$frissit){
                    $csoport_class .= ' dn';
                }

                $akt_csoport = $adatbazis_kapcsolat->s2('csoport_id', $tabla, "`recid` = '{$_GET['id']}'", '', '1');
                if( $akt_csoport['csoport_id'] == $bsz['csoport_id'] ) {
                    $csoport_class = ' behuz';
                }

                $csoport_data = ' data-csoportID="'.$bsz['csoport_id'].'"';
            }

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if($frissit){
                // $active .= ' kijelolve';
            }
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.$csoport_class.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'"'.$csoport_data.'>'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function projektkezelesLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            $statusz = '';
            if( $bsz['stat_id'] ) {
                $statusz = getStatText($tabla, $bsz['stat_id']);
            }

            // Nyitott státuszú bejegyzések navpanelen félkövérrel jelenjenek meg
            $bold = '';
            if( $bsz['stat_id'] == PRIGENY_STAT_NYITOTT ) {
                $bold  = ' class="b"';
                //$active .= ' nyitott_statuszu'; //Erre itt nincs szükség, de meghagytam, ha később mégis kellene valamire
            }
            
            // Van-e csatolt dokumentuma
            $dok_icon = '';
            // Ha van saját dokumentuma, akkor van... ez a legegyszerűbb eset, nem kell tovább számolni
            if( $adatbazis_kapcsolat->s('recid', 'projektkezeles_dok', "`projektkezeles_id`='{$bsz['recid']}'") ) {
                $dok_icon = nl2('
                    <span class="icon">
                        <i class="material-icons">attach_file</i>
                    </span>
                ');
            }


            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <table style="width: 30rem">
                            <tr>
                                <td class="ikonok" rowspan="2" style="width: 2rem">'.$dok_icon.'</td>
                                <td style="width: 10rem">'.$bsz['ikt_szam'].'</td>
                                <td style="width: 10rem">'.getSzallodaNev($bsz['telephely_id']).'</td>
                                <td >'.$statusz.'</td>
                            </tr>
                            <tr>
                                <td colspan="3" style="width: 40rem">'.resz_string($bsz['nev'], 50).'</td>
                            </tr>
                            </table>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if( $frissit ) {
                // $active .= ' kijelolve';
            }
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function projektszamlaLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <table style="width: 30rem">
                            <tr>
                                <td style="width: 5rem">'.$bsz['szla_szam'].'</td>
                                <td style="width: 10rem">'.getSzallodaNev($bsz['telephely_id']).'</td>
                                <td class="r nowrap">'. number_format($bsz['netto'], 0, '', ' ').' '.get_penznem().'</td>
                            </tr>
                            <tr>
                                <td colspan="3">'. getErtek('nev', 'szallito', 'id', $bsz['szallito_id']).'</td>
                            </tr>
                            <tr>
                                <td colspan="3">'.resz_string($bsz['targy'], 60).'</td>
                            </tr>
                            </table>
                        </a>';

            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function projektszerzodesLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <table style="width: 30rem">
                            <tr>
                                <td style="width: 8rem">'.$bsz['sz_szam'].'</td>
                                <td style="width: 10rem">'.getSzallodaNev($bsz['telephely_id']).'</td>
                                <td style="width: 7rem" class="r nowrap">'. number_format($bsz['szerz_ossz'], 0, '', ' ').' '. get_penznem().'</td>
                            </tr>
                            <tr>
                                <td colspan="3">'.getErtek('nev', 'szallito', 'id', $bsz['szallito_id']).'</td>
                            </tr>
                            <tr>
                                <td colspan="3">'.resz_string($bsz['targy'], 60).'</td>
                            </tr>
                            </table>
                        </a>';

            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function uzemeltetesi_naploLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['recid'].'</span>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if($frissit){
                // $active .= ' kijelolve';
            }
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function eloterj_bodLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['ikt_szam'].' '.getStatText($tabla, $bsz['stat_id']).'</span>
                            <span>'.$bsz['nev'].'</span>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if($frissit){
                // $active .= ' kijelolve';
            }
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function eloterj_fbLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['ikt_szam'].' '.getStatText($tabla, $bsz['stat_id']).'</span>
                            <span>'.$bsz['nev'].'</span>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if($frissit){
                // $active .= ' kijelolve';
            }
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function energiaLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['recid'].'</span>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if($frissit){
                // $active .= ' kijelolve';
            }
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}


function energia_szamlaLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach ( $bejegyzesek as $bsz ) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <table>
                            <tr> 
                                <td style="width: 12rem">'.getSzallodaNev($bsz['telephely_id']).'</td>
                                <td style="width: 10rem" class="r">'.number_format($bsz['netto'], 0, '', ' ').' '.get_penznem().'</td>
                                <td></td>
                            </tr>
                            <tr> 
                                <td colspan="3">'. getErtek('nev', 'szallito', 'id', $bsz['szallito_id']).'</td>
                            </tr>
                            </table>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function tervezesLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['recid'].'</span>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if($frissit){
                // $active .= ' kijelolve';
            }
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function cash_flowLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['recid'].'</span>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if($frissit){
                // $active .= ' kijelolve';
            }
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function raktarLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['recid'].'</span>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if($frissit){
                // $active .= ' kijelolve';
            }
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function szamlaLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['recid'].'</span>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if($frissit){
                // $active .= ' kijelolve';
            }
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function cikktorzsLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            $szakter = erteklista(ERTEKLISTA_SZAKTER, $bsz['szakter_id']);

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['id'].' '.$bsz['nev'].' - '.$szakter.'</span>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if($frissit){
                // $active .= ' kijelolve';
            }
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';


            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

// Dokumentumtár nav panel része
function dokumentumtarLista() {
    global $adatbazis_kapcsolat;
    $html = '
            <div class="padd b">Dokumentumok:</div>
            <link rel="stylesheet" type="text/css" href="../css/konyvtarszerkezet.css">
            <ol class="konyvtar_szerk">
            ';

    // Ezen menüpontok dokumentumait járjuk be
    $menupontok = array(
                        array('tabla' => 'anyag_szamla',            'nev' => 'Anyag számlák'            ),
                        array('tabla' => 'anyagigenyles',           'nev' => 'Anyagigénylések'          ),
                        array('tabla' => 'bejelentes',              'nev' => 'Bejelentések'             ),
                        array('tabla' => 'beosztas',                'nev' => 'Beosztások'               ),
                        array('tabla' => 'cikktorzs',               'nev' => 'Cikktörzs'                ),
                        array('tabla' => 'munkalap',                'nev' => 'Munkalapok'               ),
                        array('tabla' => 'energia',                 'nev' => 'Energia'                  ),
                        array('tabla' => 'energia_szamla',          'nev' => 'Energia számlák'          ),
                        array('tabla' => 'eseti_fenntartas',        'nev' => 'Eseti fenntartás'         ),
                        array('tabla' => 'eszkozkezeles',           'nev' => 'Eszközkezelés'            ),
                        array('tabla' => 'fenntartasi_szamla',      'nev' => 'Fenntartási számlák'      ),
                        array('tabla' => 'fenntartasi_szerzodes',   'nev' => 'Fenntartási szerződések'  ),
                        array('tabla' => 'fenntartasi_terv',        'nev' => 'Fenntartási terv'         ),
                        array('tabla' => 'raktar',                  'nev' => 'Raktár'                   ),
                        array('tabla' => 'tervezes',                'nev' => 'Tervezés'                 ),
                        array('tabla' => 'utemezett_feladatok',     'nev' => 'Ütemezett feladatokok'    ),
                        array('tabla' => 'uzemeltetesi_naplo',      'nev' => 'Üzemeltetési napló'       ),
                        array('tabla' => 'szallito',                'nev' => 'Partnertörzs'             ),
                        array('tabla' => 'projektigenyles',         'nev' => 'Projektigénylések'        ),
                        array('tabla' => 'projektigenyles_velemeny','nev' => 'Projektigénylés vélemények'),
                        array('tabla' => 'projektkezeles',          'nev' => 'Projektkezelés'           ),
                        array('tabla' => 'projektszamla',           'nev' => 'Projekt számlák'          ),
                        array('tabla' => 'projektszerzodes',        'nev' => 'Projekt szerződsek'       ),
                        array('tabla' => 'rendszeres_karbantartas', 'nev' => 'Rendszeres karbantartás'  ),
                        array('tabla' => 'szamla',                  'nev' => 'Számlák'                  ),
                        array('tabla' => 'torzsadat',               'nev' => 'Törzsadatok'              ),
                    );

    foreach ( $menupontok as $menu ) {

        $checked = '';
        if( $_GET['konyvtar'] ) {
            $kapott_konyvtar = substr($_GET['konyvtar'], 0, strpos($_GET['konyvtar'], '/'));
            if( $kapott_konyvtar == $menu['tabla'] ) {
                $checked = ' checked';
            }
        }
        $html .= '
                <li>
                    <label class="link" for="konyvtar_'.$menu['tabla'].'">'.$menu['nev'].'</label>
                    <input type="checkbox"'.$checked.' id="konyvtar_'.$menu['tabla'].'">';

            // Alkönyvtárak kiszámítása
            $alkonyvtarak = array();
            $post = '_dok';
            // Projektigénylés véleményél nincs _dok postfix
            if( $menu['tabla'] == 'projektigenyles_velemeny' ) {
                $post = '';
            }

            $alkak = $adatbazis_kapcsolat->s('utvonal', $menu['tabla'].$post);
            foreach ( $alkak as $alk ) {
                $konyvtar = substr($alk['utvonal'], 0, strpos($alk['utvonal'], '/'));
                array_push($alkonyvtarak, $konyvtar);
            }

            if( $alkonyvtarak ) {
                $html .= '<ol>';

                $alkonyvtarak = array_unique($alkonyvtarak);
                sort($alkonyvtarak);
                foreach ( $alkonyvtarak as $alk ) {

                    $html .= '
                            <li>
                                <label class="as"><a href="javascript: getKonyvtar(\''.self().'?konyvtar='.$menu['tabla'].'/'.$alk.'\'); void(0);" class="link">'.$alk.'</a></label>
                            </li>';
                }
                $html .= '</ol>';
            }

        $html .= '</li>';

    }
    $html .= '</ol>';

    return nl2($html);
}

// Eszközkezelés bal oldali könyvtárszerkezete
function eszkozkezelesLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {

    global $adatbazis_kapcsolat;

    $login_szallodai = get_akt_login_szallodai();
    if( $login_szallodai ) {

        // Hogy ne az egész objektum fát generáljuk ki újra, ezért ha frissítünk eszközkezelés alatt és obj alatt, akkor csak visszaadjuk az első sort
        if( $frissit ) {
            return array(
                'html' => json_encode($bejegyzesek[0]),
                'idk'  => array()
            );
        }

        $html = '
                <div class="padd b">Eszközök:</div>
                <link rel="stylesheet" type="text/css" href="../css/konyvtarszerkezet.css">
                <ol class="konyvtar_szerk">
                ';

        // Ha már nyitva van egy bejegyzés, akkor a könyvtárszerkezetet is kinyitogatjuk
        if( $_GET['id'] ) {
            $akt_eszkoz = $adatbazis_kapcsolat->s2('recid telephely_id obj_id', $tabla, "`recid`='{$_GET['id']}'");
            $akt_obj    = $akt_eszkoz['obj_id'];
            $akt_szint  = $adatbazis_kapcsolat->s2('szulo', 'obj', "`recid`='$akt_obj'");
            $akt_szint  = $akt_szint['szulo'];
        }

        // Ha van szűrés (az csak szállodára lehet)
        $sql_kieg = '';
        if( $_GET['mezo'] ) {
            $sql_kieg .= ' AND `id`=\''.$_GET['felt'].'\'';
        }
        $sql_kieg .= " AND `inaktiv`=0";
        
        // Összetett szűrő esetén is szűrjünk ha kell
        $o_szuro_kieg = get_o_szures_where_kieg($_SESSION['o_szuro_feltetel'], 'telephely');
        if( $o_szuro_kieg ) {
            $sql_kieg .= ' AND '.str_replace('telephely_id', 'id', $o_szuro_kieg);
        }

        // Visszaadjuk az objektum ID-kat is
        $obj_idk = array();

        $szallodak = $adatbazis_kapcsolat->s('id nev', 'telephely', "`id` IN (".implode(',', $login_szallodai).")$sql_kieg", 'nev');
        foreach ( $szallodak as $szalloda ) {

            if( isset($_GET['obj']) && $_GET['id'] ) {
                $obj_sor = $adatbazis_kapcsolat->s2('telephely_id szulo', 'obj', "`recid` = '".$_GET['id']."'", '', '1');
            }

            $checked = '';
            if( $akt_eszkoz['telephely_id'] == $szalloda['id'] || (is_array($obj_sor) && count($obj_sor) && $szalloda['id'] == $obj_sor['telephely_id']) ) {
                $checked = ' checked';
            }

            $link = '';
            $szintek = $adatbazis_kapcsolat->s('recid nev', 'obj', "`szulo`='0' AND `telephely_id`='{$szalloda['id']}'", 'sor');
            if( $szintek ) {
                $link = ' class="link"';
            }
            $html .= '
                    <li>
                        <label'.$link.' for="telephely_'.$szalloda['id'].'">'.$szalloda['nev'].'</label>
                        <input type="checkbox"'.$checked.' id="telephely_'.$szalloda['id'].'">';

                        if($szintek) {
                            $html .= '<ol>';
                            foreach ( $szintek as $szint ) {
                                $obj_idk[] = $szint['recid'];

                                $checked = '';
                                if($akt_szint==$szint['recid'] || (is_array($obj_sor) && count($obj_sor) && $szint['recid'] == $obj_sor['szulo'])){
                                    $checked = ' checked';
                                }
                                
                                $class   = '';
                                if( isset($_GET['obj']) && $_GET['id'] == $szint['recid'] ){
                                    $class   = ' class="aktiv"';
                                }

                                $link = '';
                                $objektumok = $adatbazis_kapcsolat->s('recid nev', 'obj', "`szulo`='{$szint['recid']}'", 'sor');
                                if($objektumok){
                                    $link = ' class="link"';
                                }
                                $html .= '
                                        <li>
                                            <a id="obj_'.$szint['recid'].'"'.$class.' href="javascript: szamlaBetolt(\''.$szint['recid'].'\', \'eszkozkezeles.php\', \'obj\'); void(0);"><label'.$link.' id="obj_label_'.$szint['recid'].'" for="szint_'.$szint['recid'].'">'.$szint['nev'].'</label></a>
                                            <input type="checkbox"'.$checked.' class="asdas">
                                        ';

                                if($objektumok){
                                    $html .= '<ol>';
                                    foreach($objektumok as $obj) {
                                        $obj_idk[] = $obj['recid'];

                                        $checked = '';
                                        $class   = '';

                                        if( $akt_obj==$obj['recid'] || (is_array($obj_sor) && count($obj_sor) && $_GET['id'] == $obj['recid'])){
                                            $checked = ' checked';
                                            if( $_GET['id'] == $obj['recid'] ){
                                                $class = ' class="aktiv"';
                                            }
                                        }

                                        $link = '';
                                        $eszkozok = $adatbazis_kapcsolat->s('recid nev', 'eszkozkezeles', "`telephely_id`='{$szalloda['id']}' AND `obj_id`='{$obj['recid']}'", 'nev');
                                        if($eszkozok){
                                            $link = ' class="link"';
                                        }
                                        $html .= '<li>
                                                    <a id="obj_'.$obj['recid'].'"'.$class.' href="javascript: szamlaBetolt(\''.$obj['recid'].'\', \'eszkozkezeles.php\', \'obj\'); void(0);"><label'.$link.' id="obj_label_'.$obj['recid'].'" for="obj_'.$obj['recid'].'">'.$obj['nev'].'</label></a>
                                                    <input type="checkbox"'.$checked.'>';
                                            if($eszkozok){
                                                $html .= '<ol>';
                                                foreach($eszkozok as $eszkoz){
                                                    $aktiv = '';
                                                    if($akt_eszkoz['recid']==$eszkoz['recid']){
                                                        $aktiv = ' aktiv';
                                                    }
                                                    $html .= '<li id="eszkoz_'.$eszkoz['recid'].'" id="eszkozkezeles-'.$eszkoz['recid'].'" class="sor'.$aktiv.'" onclick="szamlaBetolt(\''.$eszkoz['recid'].'\', \'eszkozkezeles.php\', \'modosit\')">'.$eszkoz['nev'].'<li>';
                                                }
                                                $html .= '</ol>';
                                            }

                                        $html .= '</li>';
                                    }
                                    $html .= '</ol>';
                                }

                                $html .= '</li>';

                            }
                            $html .= '</ol>';
                        }

            $html .= '</li>';

        }
        $html .= '</ol>';
    }

    return array(
        'html' => nl2($html),
        'idk'  => $obj_idk
    );
}

function felmeresi_tervLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    $login_szallodai = get_akt_login_szallodai();
    if($login_szallodai){

        $html = '
                <div class="padd b">Felmérési tervek:</div>
                <link rel="stylesheet" type="text/css" href="../css/konyvtarszerkezet.css">
                <ol class="konyvtar_szerk">
                ';

        // Ha már nyitva van egy bejegyzés, akkor a könyvtárszerkezetet is kinyitogatjuk
        if($_GET['id']){
            $akt_ft     = $adatbazis_kapcsolat->s2('recid telephely_id obj_id', $tabla, "`recid`='{$_GET['id']}'");
            $akt_szint  = $akt_ft['obj_id'];
        }

        // Ha van szűrés (az csak szállodára lehet)
        $sql_kieg = '';
        if ($_GET['mezo']) {
            $sql_kieg .= ' AND `id`=\''.$_GET['felt'].'\'';
        }
        $sql_kieg .= " AND `inaktiv`=0";

        $szallodak = $adatbazis_kapcsolat->s('id nev', 'telephely', "`id` IN (".implode(',', $login_szallodai).")$sql_kieg", 'nev');
        foreach($szallodak as $szalloda){

            $checked = '';
            if($akt_ft['telephely_id']==$szalloda['id']){
                $checked = ' checked';
            }

            $link = '';
            $szintek = $adatbazis_kapcsolat->s('recid nev', 'obj', "`szulo`='0' AND `telephely_id`='{$szalloda['id']}'");
            if($szintek){
                $link = ' class="link"';
            }
            $html .= '
                    <li>
                        <label'.$link.' for="telephely_'.$szalloda['id'].'">'.$szalloda['nev'].'</label>
                        <input type="checkbox"'.$checked.' id="telephely_'.$szalloda['id'].'">';

                        if($szintek){
                            $html .= '<ol>';
                            foreach($szintek as $szint) {

                                $checked = '';
                                if($akt_szint==$szint['recid']){
                                    $checked = ' checked';
                                }

                                $ftek = $adatbazis_kapcsolat->s('recid nev', 'felmeresi_terv', "`telephely_id`='{$szalloda['id']}' AND `obj_id`='{$szint['recid']}'", 'nev');
                                $link = '';
                                if($ftek){
                                    $link = ' class="link"';
                                }
                                $html .= '
                                        <li>
                                            <label'.$link.' for="szint_'.$szint['recid'].'">'.$szint['nev'].'</label>
                                            <input type="checkbox"'.$checked.' id="szint_'.$szint['recid'].'">
                                        ';


                                if($ftek){
                                    $html .= '<ol>';
                                    foreach($ftek as $ft){
                                        $aktiv = '';
                                        if($akt_ft['recid']==$ft['recid']){
                                            $aktiv = ' aktiv';
                                        }
                                        $html .= '<li class="sor'.$aktiv.'" onclick="szamlaBetolt(\''.$ft['recid'].'\', \'felmeresi_terv.php\', \'modosit\')">'.$ft['nev'].'<li>';
                                    }
                                    $html .= '</ol>';
                                }

                                $html .= '</li>';

                            }
                            $html .= '</ol>';
                        }

            $html .= '</li>';

        }
        $html .= '</ol>';
    }

    return nl2($html);
}

function szerepkorLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if($frissit){
                // $active .= ' kijelolve';
            }

            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">
                            <a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                                <span>'.$bsz['nev'].'</span>
                            </a>
                        </li>';


            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function loginLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            $aktiv = nl2('
                <span class="icon piros">
                    <i class="material-icons">clear</i>
                </span>
            ');
            if( $bsz['aktiv'] ) {
                $aktiv = nl2('
                    <span class="icon zold">
                        <i class="material-icons">done</i>
                    </span>
                ');
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$aktiv.' '.$bsz['teljes_nev'].'</span>
                            <span style="display: none">'.$bsz['nev'].'</span>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if($frissit){
                // $active .= ' kijelolve';
            }
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function erteklistaLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {

    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {
        $csoport = $bejegyzesek[0]['csoport'];
        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            if( !$frissit ) {
                $aktCsoport = $bsz['csoport'];

                if( $i == 1 || $csoport != $aktCsoport ) {
                    $csoportSor = $adatbazis_kapcsolat->s2('nev', 'erteklista_csoport', "`csoport`='{$aktCsoport}'", '', '1');

                    if( $csoportSor ) {
                        $html .= '<li class="szamla_datum"><strong>'.$csoportSor['nev'].'</strong></li>';
                    }

                    $csoport = $aktCsoport;
                }
            }

            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['info'].'</span>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if($frissit){
                // $active .= ' kijelolve';
            }
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function szallitoLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            $aktiv = nl2('
                <span class="icon piros">
                    <i class="material-icons">clear</i>
                </span>
            ');
            if( $bsz['aktiv'] ) {
                $aktiv = nl2('
                    <span class="icon zold">
                        <i class="material-icons">done</i>
                    </span>
                ');
            }

            // Szakterületek
            $szakterulet_html = '';
            $szakteruletek = $adatbazis_kapcsolat->s('szakter_id', 'szakter_kapcs', "`szallito_id`='{$bsz['recid']}'");
            foreach($szakteruletek as $szakter){
                $szakterulet_html .= getErtek('info', 'erteklista', 'recid', $szakter['szakter_id']).' - ';
            }
            $szakterulet_html = csonkit($szakterulet_html, 3);
            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$aktiv.' '.$bsz['nev'].' - '.$szakterulet_html.'</span>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if($frissit){
                // $active .= ' kijelolve';
            }
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function ktghelyLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['id'].' - '.$bsz['nev'].'</span>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if($frissit){
                // $active .= ' kijelolve';
            }
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function ktgnemLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['id'].' - '.$bsz['nev'].'</span>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if($frissit){
                // $active .= ' kijelolve';
            }
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

function bejovoSzamlaLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {

            $active = '';
            if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                $active = ' kijelolve';
            }

            $statusz = '';
            $szinkod = '';
            if( $bsz['statusz'] ) {
                $statusz_sor = $adatbazis_kapcsolat->s('recid nev szinkod', 'bsz_statusz', 'recid='.$bsz['statusz']);

                if( $statusz_sor ) {
                    $statusz_sor = $statusz_sor[0];

                    $statusz = $statusz_sor['nev'];

                    if( $statusz_sor['szinkod'] ) {
                        $szinkod = ' style="background: '.$statusz_sor['szinkod'].';"';
                    }
                }
            }

            $szallito_sor = $adatbazis_kapcsolat->s('recid nev', 'szallito', 'recid='.$bsz['szallito_id'], '', '1');

            $szallito = '';
            if( $szallito_sor ) {
                $szallito_sor = $szallito_sor[0];

                $szallito = $szallito_sor['nev'];
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span class="ikt_szam"><span class="szso"><span class="statusz"'.$szinkod.'></span>Ikt. szám:</span> '.$bsz['ikt_szam'].'</span>
                            <span class="targy"><span class="szso">Tárgy:</span> '.resz_string($bsz['targy'], 40).'</span>
                            <span class="szallito"><span class="szso">Szállító:</span> '.$szallito.'</span>
                            <span class="netto"><span class="szso">Nettó:</span> '.number_format($bsz['netto'], 0, '', '.').' '.get_penznem().'</span>
                            <span class="statusz"><span class="szso">Státusz:</span> '.$statusz.'</span>
                        </a>';

            // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
            if($frissit){
                // $active .= ' kijelolve';
            }
            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return nl2($html);
    }

    return false;
}

// Rendezés select címkéi
function getOszlopNev( $oszlop, $kieg_nev = '' ) {
    $oszlopNevek = array(
        'sor'           => 'Státusz',
        'aktiv'         => 'Aktív',
        'csoport'       => 'Név',
        'cim'           => 'Cím',
        'cikk_id'       => 'Cikkszám',
        'e-szamla'      => 'E-számla',
        'esnap_d'       => 'Esedékesség',
        'erkezett'      => 'Érkezett',
        'ev'            => 'Tervező év',
        'fizhat_dt'     => 'Fizetési határidő',
        'id'            => 'Kód',
        'ikt_szam'      => $kieg_nev.' szám',
        'nev'           => 'Név',
        'obj_id'        => 'Helyiség',
        'obj_txt'       => 'Helyiség',
        'rndid'         => 'Rendelés száma',
        'rnd_dt'        => 'Rendelés dátum',
        'recid'         => 'Felvitel',
        'rkat'          => 'Érték',
        'surgos'        => 'Sürgős',
        'statusz'       => 'Státusz',
        'stat_id'       => 'Státusz',
        'sz_szam'       => 'Szerződés szám',
        'szakter_id'    => 'Szakterület',
        'szallito_id'   => 'Szállító',
        'szemelyzet_id' => 'Szerepkör',
        'telephely_id'  => 'Szálloda',
        'teljes_nev'    => 'Teljes név',
        'torolve'       => 'Törölve',
    );

    // Néhol át kell írni az általános mezőneveket (pl. loginnál a nev nem Név, hanem Login név)
    if( basename(self()) == 'bejelentes.php' || basename(self()) == 'bejelentes_arch.php' ) {
        $oszlopNevek['surgos'] = 'Státusz';
    }
    if( basename(self()) == 'login.php' ) {
        $oszlopNevek['nev'] = 'Login név';
    }
    if( basename(self()) == 'projektigenyles.php' || basename(self()) == 'eloterj_fb.php' || basename(self()) == 'eloterj_bod.php' ) {
        $oszlopNevek['ikt_szam'] = 'Azonosító';
    }
    if( basename(self()) == 'energia_terv.php' || basename(self()) == 'fenntartasi_terv.php' ) {
        $oszlopNevek['ikt_szam'] = 'Tervszám';
    }
    if( basename(self()) == 'szallito.php' ) {
        $oszlopNevek['id'] = 'Szállító kód';
    }
    if( basename(self()) == 'utemezett_feladatok.php' ) {
        $oszlopNevek['id'] = 'Feladatszám';
    }

    if( $oszlopNevek[$oszlop] ) {
        return $oszlopNevek[$oszlop];
    } else {
        return $oszlop;
    }
}

// Értéklistából infó kinyerése
function erteklista($csoport, $id) {

    global $adatbazis_kapcsolat;

    // Megpróbáljuk Session-ból visszaadni
    $ertek = getErtekLista($csoport, $id);

    // De ha nem sikerült, akkor lekérjük adatbázisból
    if(!$ertek){
        $sor   = $adatbazis_kapcsolat->s2('info', 'erteklista', "`csoport` = '{$csoport}' AND `id` = '{$id}'");
        $ertek = $sor['info'];
    }
    return $ertek;

}

// Adatbázisból egy érték gyors kinyerése
function getErtek($mit, $tabla, $keres, $val) {
    global $adatbazis_kapcsolat;

    $sor = $adatbazis_kapcsolat->s2($mit, $tabla, "`{$keres}` = '{$val}'", '', '1');
    if( $sor ) {
        return $sor[$mit];
    } else {
        return false;
    }
}

function jogosultsagKatok( $tomb ) {
    require('jogosultsag.php');

    if( $tomb ) {
        $katok = array();

        foreach($tomb as $value) {
            if( !in_array($value['kat'], adatbazis2tomb($katok, 'id')) ) {
                array_push(
                    $katok,
                    array(
                        'id'  => $value['kat'],
                        'nev' => $jogosultsagKatok[$value['kat']]['nev'],
                        'sor' => $jogosultsagKatok[$value['kat']]['sor'],
                    )
                );
            }
        }

        return $katok;
    }

    return false;
}

function ertesitesKatok( $tomb ) {
    require('ertesitesek.php');

    if( $tomb ) {
        $katok = array();

        foreach($tomb as $value) {
            if( !in_array($value['kat'], adatbazis2tomb($katok, 'id')) ) {
                array_push(
                    $katok,
                    array(
                        'id'  => $value['kat'],
                        'nev' => $ertesitesekKatok[$value['kat']]['nev'],
                        'sor' => $ertesitesekKatok[$value['kat']]['sor'],
                    )
                );
            }
        }

        return $katok;
    }

    return false;
}

function getJogSorRendez( $katID, $tomb ) {
    $jogKatok = array();

    if( $tomb ) {
        foreach($tomb as $value) {
            if( $value['kat'] == $katID ) {
                array_push($jogKatok, $value);
            }
        }

        return $jogKatok;
    }

    return false;
}

// Objektumhierarchia kirajzolása
function obj_gen( $telephelyID = false, $csak_szintig ) {
    global $adatbazis_kapcsolat;

    $where = '';
    if( $telephelyID ) {
        $where = "`id` = '{$telephelyID}'";
    }

    $telephelyek = $adatbazis_kapcsolat->s('id nev', 'telephely', $where, 'nev');

    $fokat_i = 0;
    $html    = '';
    if( $telephelyek ) {
        $html .= '<h2>Objektumok hierarchiája</h2><div class="kozep" style="max-height: 30rem"><ul class="telephelyek">';

        $egy_szalloda_nyitva = '';
        if( count($telephelyek)==1 ){
            $egy_szalloda_nyitva = ' active';
        }
        foreach($telephelyek as $telephely) {
            $fokatok = $adatbazis_kapcsolat->s('recid telephely_id nev szulo', 'obj', "`szulo`=0 AND `telephely_id`='{$telephely['id']}'", 'sor');

            $fokat_html = '';
            if( $fokatok ) {
                $active = '';
                if( $telephelyID == $telephely['id'] && !isset($_GET['print']) ) {
                    $active = ' kijelolve';
                }

                $fokat_html .= '<ul class="alkatok'.$active.$egy_szalloda_nyitva.'">';

                foreach($fokatok as $fokat) {

                    // Ha csak szint mélységig szeretnénk lemenni
                    if($csak_szintig){
                        $fokat_html .= '<li class="alkat"><a onclick="popupZar(); addObj( this );" class="link" data-recid="'.$fokat['recid'].'">'.$fokat['nev'].'</a></li>';
                    } else {
                        $katok = $adatbazis_kapcsolat->s('recid telephely_id nev szulo', 'obj', "`szulo`='{$fokat['recid']}'", 'sor');

                        $kat_html = '';
                        if( $katok ) {
                            $kat_html .= '<ul class="alkatok">';

                            foreach($katok as $kat) {
                                $kat_html .= '<li class="alkat"><a onclick="addObj( this );" class="link" data-recid="'.$kat['recid'].'">'.$kat['nev'].'</a></li>';
                            }

                            $kat_html .= '</ul>';
                        }

                        $alkatok = '';
                        $icon    = '';
                        $nyit    = ' class="link"';
                        $onclick = 'addObj( this );';
                        if( $kat_html ) {
                            $alkatok = ' alkatos';
                            $icon    = '<span class="icon"></span>';
                            $nyit    = ' class="nyit link"';
                            $onclick = '';
                            $fokat['nev'] = '<span onclick="addObj( this );" data-recid="'.$fokat['recid'].'">'.$fokat['nev'].'</span>';
                        }

                        $fokat_html .= '<li class="kat'.$alkatok.'"><a onclick="'.$onclick.'"'.$nyit.' data-recid="'.$fokat['recid'].'">'.$icon.$fokat['nev'].'</a>'.$kat_html.'</li>';
                    }
                    $fokat_i++;
                }

                $fokat_html .= '</ul>';
            }

            $alkatok = '';
            $icon = '';
            $nyit = '';
            if( $fokat_html ) {
                $alkatok = ' alkatos';
                $icon = '<span class="icon"></span>';
                $nyit = ' class="nyit"';
            }

            $html .= '<li class="fokat'.$alkatok.'"><a href=""'.$nyit.'>'.$icon.$telephely['nev'].'</a>'.$fokat_html.'</li>';
        }

        $html .= '</ul></div>';
    }

    // Ha egy szálloda objektumhierarchiáját kérnénk le, de nincs, akkor hibaüzenet
    if($telephelyID && !$fokat_i){
        $html = '<h2>Objektumok hierarchiája</h2><div class="kozep" style="max-height: 30rem">A(z) '.$telephely['nev'].' szállodához nem tartoznak objektumok!</ul></div>';
    }

    return $html;
}

// Hét napja szövegesen
function het_napja($nap){
    $napok = array('vasárnap', 'hétfő', 'kedd', 'szerda', 'csütörtök', 'péntek', 'szombat');
    return $napok[$nap];
}

// Jogosultásg gyors lekérdezése
function _jog( $value ) {

    if( $_SESSION[get_db_tabla().'rights'][$value] ) {
        return $_SESSION[get_db_tabla().'rights'][$value];
    }

    return false;
}

// Értesítés gyors lekérdezése
function _ert( $value ) {
    if( $_SESSION[get_db_tabla().'ertesites'][$value] ) {
        return $_SESSION[get_db_tabla().'ertesites'][$value];
    }

    return false;
}

// Státusz idő kiszámítása
function getStatuszIdo( $time ) {

    // Eltelt napok, órák, percek, másodpercek
    $napok = floor($time / 86400);
    $ora   = floor(($time % 86400) / 3600);
    $perc  = floor(($time % 86400 % 3600) / 60);
    $mp    = floor(($time % 86400 % 3600 % 60));

    // Napokat csak akkor fűzzük hozzá, ha nagyobb mint 0
    $napok_string = '';
    if($napok){
        $napok_string = $napok.' nap ';
    }

    return $napok_string.sprintf('%02d', $ora).':'.sprintf('%02d', $perc).':'.sprintf('%02d', $mp);
}

// Státusz hozzáadása
function addStatusz( $tabla = false, $recid = false, $stat_id = false, $erteklista_recid = 0, $memo = '', $rendszer_a_user = false, $adott_ido = '', $adott_nev = '' ) {

    global $adatbazis_kapcsolat;
    global $db_kapcs;

    $db_error = false;
    $kulso_kulcs = str_replace('_statusz', '', $tabla);

    if( $adott_ido ) {
        $adott_ido = trim($adott_ido);
        $adott_ido = str_replace('.', '-', $adott_ido);
        if( strlen($adott_ido) == 16 ) {
            $adott_ido .= ':00';
        }
        $now  = $adott_ido;
        $time = strtotime($adott_ido);
    } else {
        $now  = 'NOW()';
        $time = time();
    }

    // Ütemezők esetén is legyen teljes_nev, amit be tudunk írni
    $teljes_nev = $_SESSION[get_db_tabla().'teljes_nev'];
    $login      = $_SESSION[get_db_tabla().'user'];
    if( $adott_nev ) {
        $login      = $adatbazis_kapcsolat->s2('nev teljes_nev', 'login', "`nev`='{$adott_nev}'");
        $teljes_nev = $login['teljes_nev'];
        $login      = $login['nev'];
    }
    if( $rendszer_a_user ) {
        $teljes_nev = get_nev();
    }

    if( $tabla && $recid && $stat_id ) {

        // Lekérjük az az éppen aktuális státuszt
        $akt_statusz = $adatbazis_kapcsolat->s2('recid stat_id insert_dt', $tabla, "`{$kulso_kulcs}_recid`='{$recid}'", 'recid desc', '1');
        if( $sql === false ) { $db_error = true; }
 
        // Ha új státusz, vagy munkalap előkészítés alatt státusz (tehát ez jöhet többször egymás után is)
        if( $akt_statusz && ( $akt_statusz['stat_id']!=$stat_id || $akt_statusz['stat_id']==MUNKALAP_STAT_ELOKESZITES_ALATT ) ) {

            // Megállapítjuk a státusz idejét
            $statusz_ido_s = round( $time - strtotime($akt_statusz['insert_dt']) );
            $statusz_ido   = getStatuszIdo( $statusz_ido_s );

            // Göngyölt státuszidő
            $elso_statusz = $adatbazis_kapcsolat->s2('insert_dt', $tabla, "`{$kulso_kulcs}_recid`='{$recid}'", 'recid asc', '1');
            if( $elso_statusz === false ) { $db_error = true; }
            if( $elso_statusz ) {
                $gongyolt_statido_s = round( $time - strtotime($elso_statusz['insert_dt']) );
                $gongyolt_statido   = getStatuszIdo( $gongyolt_statido_s );
            }

            // Összesített státuszidő
            $elso_akt_stat = $adatbazis_kapcsolat->s2('recid insert_dt', $tabla, "`{$kulso_kulcs}_recid`='{$recid}' AND `stat_id`='{$stat_id}'", 'recid asc', '1');
            if( $elso_akt_stat === false ) { $db_error = true; }

            $ossz_statido = '';
            if( $elso_akt_stat ) {

                $ossz_statido_s = round( $time - strtotime($elso_akt_stat['insert_dt']) );
                $ossz_statido   = getStatuszIdo( $ossz_statido_s );

                // Frissítjük a sort
                $sql = $adatbazis_kapcsolat -> u($tabla,    array('ossz_statido', 'ossz_statido_s'),
                                                            array($ossz_statido,  $ossz_statido_s ),
                                                       "`{$kulso_kulcs}_recid`='{$recid}' AND `stat_id`='{$stat_id}'"
                                            );
                if( $sql === false ) { $db_error = true; }
            }

            // Frissítjük a sort
            $sql = $adatbazis_kapcsolat -> u($tabla,
                                                array('end_dt',             'statido',    'statido_s',    'gongyolt_statido', 'gongyolt_statido_s'),
                                                array( date('Y-m-d H:i:s'), $statusz_ido, $statusz_ido_s, $gongyolt_statido,  $gongyolt_statido_s ),
                                                "`recid`='{$akt_statusz['recid']}'"
                                            );
            if( $sql === false ) { $db_error = true; }

            // Beszúrjuk az újat
            $sql = $adatbazis_kapcsolat -> i($tabla,
                                                array($kulso_kulcs.'_recid', 'stat_id', 'erteklista_recid', 'text',  'login',   'insert_dt',   'update_dt', 'insert_usr',  'update_usr' ),
                                                array($recid,                $stat_id,  $erteklista_recid,  $memo,   $login,    $now,          $now,        $teljes_nev,   $teljes_nev  )
                                            );
            if( $sql === false ) { $db_error = true; }
// echo $adatbazis_kapcsolat->gp() . '<br>';
            // Ha van memó, akkor azt az előző státuszhoz írjuk be: MÉGSEM, a friss sorba kerül be
//            $sql = $adatbazis_kapcsolat -> u($tabla, array('text' ), array($memo), "`recid`='{$akt_statusz['recid']}'");
//            if( $sql === false ) { $db_error = true; }

            // Az alap tábla státusz oszlopát is karban tartjuk
            $sql = $adatbazis_kapcsolat->u($kulso_kulcs, array('stat_id'), array($stat_id), "`recid`='$recid'");
            if( $sql === false ) { $db_error = true; }

            // Projektigénylés elutasításakor archiválunk
            if($alap_tabla=='projektigenyles' && $stat_id==PRIGENY_STAT_ELUTASITVA){
                $pri = $adatbazis_kapcsolat->s2('*', 'projektigenyles', "`recid`='$recid'");
                if( $pri === false ) { $db_error = true; }

                // Mivel tranzakcióban vagyunk, ezért az új értéket itt felülírom
                $pri['stat_id'] = $stat_id;

                $oszlopok = array();
                foreach($pri as $kulcs => $mezok){
                    array_push($oszlopok, $kulcs);
                }

                // További oszlopok (ID-k címkéjét is letároljuk)
                $telephely = $adatbazis_kapcsolat->s2('nev', 'telephely', "`id`='{$pri['telephely_id']}'");
                array_push($oszlopok, 'telephely_cimk');
                array_push($pri,      $telephely['nev']);

                require_once 'selectek.php';
                $tipus_cimk = '';
                foreach ($select_pri_tipus as $tipus){
                    if($pri['tipus']==$tipus['value']){
                        $tipus_cimk = $tipus['name'];
                    }
                }
                array_push($oszlopok, 'tipus_cimk');
                array_push($pri,      $tipus_cimk);

                $besorol_cimk = '';
                foreach ($select_pri_besorol as $besorol){
                    if($pri['besorol']==$besorol['value']){
                        $besorol_cimk = $besorol['name'];
                    }
                }
                array_push($oszlopok, 'besorol_cimk');
                array_push($pri,      $besorol_cimk);

                $statusz_cimk = getStatText('projektigenyles', $stat_id);
                array_push($oszlopok, 'stat_cimk'   );
                array_push($pri,      $statusz_cimk );

                $sql = $adatbazis_kapcsolat->i('projektigenyles_arch', $oszlopok, $pri);
                if( $sql === false ) { $db_error = true; }

                $sql = $adatbazis_kapcsolat->d('projektigenyles', "`recid`='$recid'");
                if( $sql === false ) { $db_error = true; }

                // Megjegyzés archiválása
                $sql = 'INSERT `'.get_db_tabla().'megjegyzes_arch` SELECT * FROM `'.get_db_tabla().'megjegyzes` WHERE `tabla`=\'projektigenyles\' AND `id`=\''.$pri['recid'].'\'';
                if(!mysqli_query($db_kapcs, $sql)){
                    $db_error = true;
                }

                $sql = $adatbazis_kapcsolat->d('megjegyzes', "`tabla`='projektigenyles' AND `id`='{$pri['recid']}'");
                if( $sql === false ) { $db_error = true; }

                // További kapcsolódó táblák archiválása
                $kapcs_tablak = array(
                                array('tabla' => 'projektigenyles_dok',     'kulcs' => 'projektigenyles_id'     ),
                                array('tabla' => 'projektigenyles_eloterj', 'kulcs' => 'recid'                  ),
                                array('tabla' => 'projektigenyles_kapcs',   'kulcs' => 'projektigenyles_recid'  ),
                                array('tabla' => 'projektigenyles_megter',  'kulcs' => 'projektigenyles_recid'  ),
                                array('tabla' => 'projektigenyles_statusz', 'kulcs' => 'projektigenyles_recid'  ),
                                array('tabla' => 'projektigenyles_velemeny','kulcs' => 'projektigenyles_recid'  ),
                                );

                foreach($kapcs_tablak as $ktabla){
                    $sql = 'INSERT `'.get_db_tabla().$ktabla['tabla'].'_arch` SELECT * FROM `'.get_db_tabla().$ktabla['tabla'].'` WHERE `'.$ktabla['kulcs'].'`=\''.$pri['recid'].'\'';
                    if(!mysqli_query($db_kapcs, $sql)){
                        $db_error = true;
                    }
                    $sql = $adatbazis_kapcsolat->d($ktabla['tabla'], "`{$ktabla['kulcs']}`='{$pri['recid']}'");
                    if( $sql === false ) { $db_error = true; }
                }

            }
            
            // Az anyagigénylés ha beérkezett státuszt kap, akkor ezt az időpontot eltároljuk az anyagigénylése táblába is, mert eszerint szeretnénk tudni azt rendezni is
            if($tabla=='anyagigenyles_statusz' && $stat_id==CIKKIGN_STAT_BEERKEZETT){
                $sql = $adatbazis_kapcsolat->u('anyagigenyles', array('beerkezett'), array('NOW()'), "`recid`='{$recid}'");
                if( $sql === false ) { $db_error = true; }
            }

            if( !$db_error ) {
                return true;
            } else {
                return false;
            }
        }

        // Ha azonos a státusz mint ahová léptetünk akkor is true-val térünk vissza
        if( $akt_statusz['stat_id'] == $stat_id ) {
            return true;
        }
    }

    return false;
}

// Megjegyzés beszúrása
function addMegj( $tabla, $id, $megj ) {
    // Ha van egyáltalán megjegyzés
    if(trim($megj)){
        global $adatbazis_kapcsolat;
        $szerepkor = getSzerepkorNev($_SESSION[get_db_tabla().'szemelyzet_id']);
        $ins   = $adatbazis_kapcsolat->i('megjegyzes',  array('tabla', 'id', 'megj', 'loginnev',                       'szerepkor',                                 'szerepkor_txt',    'update_usr',                           'insert_dt', 'insert_usr'                           ),
                                                        array($tabla,  $id, $megj,   $_SESSION[get_db_tabla().'user'], $_SESSION[get_db_tabla().'szemelyzet_id'],   $szerepkor,         $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',      $_SESSION[get_db_tabla().'teljes_nev'])
                                        );
        if($ins===false){ return false; }

        // Adott tábla megj_db oszlop karban tartása
        $megj_db = $adatbazis_kapcsolat->s('recid', 'megjegyzes', "`tabla`='{$tabla}' AND `id`='{$id}'");
        if($megj_db===flase){ return false; }


        $sql = $adatbazis_kapcsolat->u($tabla, array('megj_db'), array(count($megj_db)), "`recid`='{$id}'");
        if($sql===flase){ return false; }

    }
}

// Megnevezésből a megfelelő tömb betöltése központosítva
function get_akt_tomb($megn){

    global $anyagigenyles_tomb;
    global $bejelentes_tomb;
    global $beosztas_tomb;
    global $cikktorzs_tomb;
    global $eloterj_fb_tomb;
    global $eloterj_bod_tomb;
    global $energia_terv_tomb;
    global $eseti_fenntartas_tomb;
    global $eszkozkezeles_tomb;
    global $erteklista_tomb;
    global $felmeresi_terv_tomb;
    global $fenntartasi_terv_tomb;
    global $login_tomb;
    global $munkalap_tomb;
    global $utemezett_feladatok_tomb;
    global $projektigenyles_tomb;
    global $projektkezeles_tomb;
    global $projektszamla_tomb;
    global $projektszerzodes_tomb;
    global $rendszeres_karbantartas_tomb;
    global $szallito_tomb;
    global $torzsadat_tomb;

    switch($megn){
        case 'anyagigenyles'            : case 'anyagigenyles_arch'             : $akt_tomb = $anyagigenyles_tomb;          break;
        case 'bejelentes'               : case 'bejelentes_arch'                : $akt_tomb = $bejelentes_tomb;             break;
        case 'beosztas'                 : case 'beosztas_arch'                  : $akt_tomb = $beosztas_tomb;               break;
        case 'cikktorzs'                : case 'cikktorzs_arch'                 : $akt_tomb = $cikktorzs_tomb;              break;
        case 'eloterj_fb'               : case 'eloterj_fb_arch'                : $akt_tomb = $eloterj_fb_tomb;             break;
        case 'eloterj_bod'              : case 'eloterj_bod_arch'               : $akt_tomb = $eloterj_bod_tomb;            break;
        case 'energia_terv'             : case 'energia_terv_arch'              : $akt_tomb = $energia_terv_tomb;           break;
        case 'eseti_fenntartas'         : case 'eseti_fenntartas_arch'          : $akt_tomb = $eseti_fenntartas_tomb;       break;
        case 'eszkozkezeles'            : case 'eszkozkezeles_arch'             : $akt_tomb = $eszkozkezeles_tomb;          break;
        case 'erteklista'               : case 'erteklista_arch'                : $akt_tomb = $erteklista_tomb;             break;
        case 'felmeresi_terv'           : case 'felmeresi_terv_arch'            : $akt_tomb = $felmeresi_terv_tomb;         break;
        case 'fenntartasi_terv'         : case 'fenntartasi_terv_arch'          : $akt_tomb = $fenntartasi_terv_tomb;       break;
        case 'login'                    : case 'login_arch'                     : $akt_tomb = $login_tomb;                  break;
        case 'munkalap'                 : case 'munkalap_arch'                  : $akt_tomb = $munkalap_tomb;               break;
        case 'utemezett_feladatok'      : case 'utemezett_feladatok_arch'       : $akt_tomb = $utemezett_feladatok_tomb;    break;
        case 'projektigenyles'          : case 'projektigenyles_arch'           : $akt_tomb = $projektigenyles_tomb;        break;
        case 'projektkezeles'           : case 'projektkezeles_arch'            : $akt_tomb = $projektkezeles_tomb;         break;
        case 'projektszamla'            : case 'projektszamla_arch'             : $akt_tomb = $projektszamla_tomb;          break;
        case 'projektszerzodes'         : case 'projektszerzodes_arch'          : $akt_tomb = $projektszerzodes_tomb;       break;
        case 'rendszeres_karbantartas'  : case 'rendszeres_karbantartas_arch'   : $akt_tomb = $rendszeres_karbantartas_tomb;break;
        case 'szallito'                 : case 'szallito_arch'                  : $akt_tomb = $szallito_tomb;               break;
        case 'torzsadat'                : case 'torzsadat_arch'                 : $akt_tomb = $torzsadat_tomb;              break;
    }

    // Beállítjuk mindenhol a megn_szurobe értéket, mert ez alapján fogjuk rendezni
    foreach($akt_tomb as $kulcs => $elem){
        if( !$akt_tomb[$kulcs]['megn_szurobe'] ){
            $akt_tomb[$kulcs]['megn_szurobe'] = $akt_tomb[$kulcs]['megn'];
        }
    }

    return $akt_tomb;
}

// Az aktuálisan bejelentkezett felhasználóhoz rendelt szállodák ID-ját adja vissza. Ez többször előfroduló igény, azért van kiszervezve függvénybe.
function get_akt_login_szallodai($sqlt_adj_vissza = '', $telephely_id_oszlop_helyett_masik_oszlop = '', $masik_user = '') {
    global $adatbazis_kapcsolat;

    // Aktuális felhasználó, vagy valaki más?
    $user = $_SESSION[get_db_tabla().'user'];
    if($masik_user){
        $user = $masik_user;
    }

    // Szállodák lekérdezése
    $szallodak = $adatbazis_kapcsolat->s('telephely_id', 'telephely_kapcs', "`nev`='{$user}'");
    $szallodak = adatbazis2tomb($szallodak, 'telephely_id');

    // SQL-t adjunk vissza
    if($sqlt_adj_vissza){
        $oszlop = 'telephely_id';
        if($telephely_id_oszlop_helyett_masik_oszlop){
            $oszlop = $telephely_id_oszlop_helyett_masik_oszlop;
        }
        // Ha tartozik hozzá szálloda
        if($szallodak){
            return ' AND `'.$oszlop.'` IN ('.implode(', ', $szallodak).')';
        // Ha nem tartozik hozzá szálloda, akkor így válik majd hamissá az SQL feltétele
        } else {
            return ' AND `'.$oszlop.'`=0';
        }
    // Tömböt adunk vissza a szálloda ID-kal
    } else {
        return $szallodak;
    }
}

// Adott felhasználó részlegeit adja vissza egy tömbben (részlet + társrészlegeket)
function get_akt_login_reszlegei( $felh_id = false ) {
    
    global $adatbazis_kapcsolat;
    
    // Alapértelmezetten az aktuálisan belépett felhasználó részlegei érdekelnek bennünket, de paramétereheztő másra is
    if( !$felh_id ) {
        $felh_id = $_SESSION[get_db_tabla().'login_recid'];
    }
    
    $reszlegek = $adatbazis_kapcsolat->s('reszleg_id', 'login_tarsreszleg', "`login_id`='{$felh_id}'");
    $reszlegek = adatbazis2tomb($reszlegek, 'reszleg_id');
    $felh = $adatbazis_kapcsolat->s2('reszleg_id', 'login', "`nev`='{$_SESSION[get_db_tabla().'user']}'");
    array_push($reszlegek, $felh['reszleg_id']);
    
    return $reszlegek;
    
}

// A kapott munkalapID-k alapján bejárjuk a munkalapokat. Ha adott munkalapon minden anyag státusza 0 (beérkezett), akkor maga a munkalap státusza folyamatbanra állítható
function munkalap_folyamatbanra_halehet($munkalapidk){

    global $adatbazis_kapcsolat;

    $db_error = false;

    foreach($munkalapidk as $mukalap_id){
        // Ha nincs olyan anyag, aminek a státusza ne 0 volna, akkor a munkalap státuszát folyamatbanra váltjuk
        $sql = $adatbazis_kapcsolat->s('recid', 'munkalap_anyag', "`munkalap_id`='$mukalap_id' AND `stat_id`!='".DISZPNAG_STAT_VAN_ANYAG."'");
        if( $sql === false ) {
            $db_error = true;
            return false;
        }
        if( !$sql ) {
            if( !addStatusz('munkalap_statusz', $mukalap_id, MUNKALAP_STAT_NYITOTT) ) {
                $db_error = true;
            }
        }
    }

    if( !$db_error ) {
        return true;
    } else {
        return false;
    }

}

// Beosztásonkál használt naptár kiegenerálása
function beosztas_naptar($beosztas_id){

    global $adatbazis_kapcsolat;

    // Naptár események
    $naptar_sorok = $adatbazis_kapcsolat->s('recid tol ig felh_loginnev beosztas szerepkor', 'beosztas_naptar', "`beosztas_id` = '".$beosztas_id."'", 'tol');

    $events = '';
    if( $naptar_sorok ) {
        foreach($naptar_sorok as $ns) {

            // Személyzet név
            $szemelyzet = $adatbazis_kapcsolat->s2('nev', 'torzsadat', "`felh_loginnev`='".$ns['felh_loginnev']."'");

            // Szín
            $bgcolor = '';
            $color   = '#242424';
            $kieg    = ' - Szabadság';
            foreach($select_szemelyzet as $szerepkor) {
                if( $szerepkor['value'] && $ns['szerepkor'] == $szerepkor['value'] ) {
                    $bgcolor = $szerepkor['szin'];
                    $kieg    = ' - '.$szerepkor['name'];
                }
            }

            if( !$bgcolor ) {
                $color = '#fff';
            }

            $tol = date('c', strtotime($ns['tol']));
            $ig  = date('c', strtotime($ns['ig']));

            $events .= "{ id: ".$ns['recid'].", title: '".$szemelyzet['nev'].$kieg."', start: '".$tol."', end: '".$ig."', backgroundColor: '".$bgcolor."', borderColor: '".$bgcolor."', textColor: '".$color."'},";
        }

        if( $events ) {
            $events = csonkit($events, 1);
        }
    }

    // Start Date
    $beosztas = $adatbazis_kapcsolat->s2('tol', 'beosztas', "`recid`='$beosztas_id'");
    $start_dt = date('c', date2int($beosztas['tol']) );

    $html .= str_replace(
                array('{EVENTS}', '{START_DT}'),
                array( $events,    $start_dt  ),
                fajlbol_olvas('../sablon/admin/naptar.html', true)
            );

    return $html;
}

// A kapott mező, operátor és feltétel alapján előállítja a where feltétel kiegészítését
function get_szures_where_kieg($mezo, $op, $felt, $tabla) {

    $mezo = sql_ertek($mezo);
    $op   = sql_ertek($op);
    $felt = sql_ertek($felt);
    
    // Numerikus mezőt visszaalakítjuk
    if(
            ($tabla=='anyagigenyles'    && $mezo=='arfolyam')  ||
            ($tabla=='projektszamla'    && $mezo=='dev_net')   ||
            ($tabla=='projektszamla'    && $mezo=='dev_br')    ||
            ($tabla=='projektszamla'    && $mezo=='arfolyam')  ||
            ($tabla=='projektszerzodes' && $mezo=='arfolyam')  ||
            ($tabla=='projektszerzodes' && $mezo=='dev_net')
        ){
        $felt = str_replace(',', '.', $felt);
    }

    // Legfrisebb mezőre nem szűrünk, ez a limit-re vonatkozik
    if( $mezo == 'legfrissebb' ) {
        return '';
    }

    $op_origi = $op;
    switch ( $op ) {
        case 'equal'    : $op = '=';    break;
        case 'not_eq'   :
            // Létrehozás dátumánál kicsit más az operátor működése
            if( $mezo == 'insert_dt' ) {
                $op = ' NOT LIKE ';
                $felt = '%'.$felt.'%';
            } else {
                $op = '!=';
            }
        break;
        case 'gteq'     : $op = '>=';   break;
        case 'lteq'     : $op = '<=';   break;
        case 'gt'       : $op = '>';    break;
        case 'lt'       : $op = '<';    break;
        case 'like'     : $op = ' LIKE '; $felt = '%'.$felt.'%'; break;
        case 'empty'    :
            if( $felt == 1 ) {
                $op = '=';
            } else {
                $op = '!=';
            }
            $felt = '';
        break;
    }

// echo $mezo.' - '.$op.' - '.$felt;exit;
    // Külső táblák kapcsolása
    // A szamla_lista() függvényben is állítsd be a JOIN-okat
    if( $mezo == 'megjegyzes' ) {
        return '`'.get_db_tabla().'megjegyzes`.`tabla`=\''.$tabla.'\' AND `'.get_db_tabla().'megjegyzes`.`megj` LIKE \''.$felt.'\'';
    } elseif( $op_origi != 'empty' && ($tabla == 'anyagigenyles' && $mezo == 'nev') ) {
        return '`'.get_db_tabla().'anyagigenyles`.`nev` LIKE \'%'.$felt.'%\'';
    } elseif( $tabla == 'anyagigenyles' && $mezo == 'beerkertek' ) {
        return '`'.get_db_tabla().'anyagigenyles`.`erkmen`*`'.get_db_tabla().'anyagigenyles`.`rndear`=\''.$felt.'\'';
    } elseif( $tabla == 'anyagigenyles' && $mezo == 'rndertek' ) {
        return '`'.get_db_tabla().'anyagigenyles`.`rndmen`*`'.get_db_tabla().'anyagigenyles`.`rndear`=\''.$felt.'\'';
    } elseif( $tabla == 'anyagigenyles' && $mezo == 'beerkezett' && $op == '=' ) {
        return '`'.get_db_tabla().'anyagigenyles`.`beerkezett` LIKE \''.str_replace('.', '-', $felt).'%\'';
    } elseif( $tabla == 'bejelentes' && $mezo == 'telephely_id' ) {
        return '`'.get_db_tabla().'bejelentes`.`telephely_id`=\''.$felt.'\'';
    
    // Létrehozás dáumára keresve általánosan így járunk el
    }elseif( $mezo == 'insert_dt' ) {
        $felt = str_replace('.', '-', $felt);
        if( $op_origi == 'empty' ) {
            $felt = '0000-00-00';
        }
        return '`'.get_db_tabla().$tabla.'`.`insert_dt`'.$op.'\''.$felt.'\'';
    // Van-e dokumentuma szűrés
    }elseif( $mezo == 'dok_van_e' ) {
        if( $felt > 0 ) {
            return '`dok_db`>0';
        } else {
            return '`dok_db`=0';
        }
    // Minősítésre váró dokumentuma van-e
    }elseif( $mezo == 'dok_db_min_var' ) {
        if( $felt > 0 ) {
            return '`dok_db_min_var`>0';
        } else {
            return '`dok_db_min_var`=0';
        }
    } elseif( $tabla == 'login' && $mezo == 'jogosultsag' ) {
        return 'SUBSTRING(`flags`, '.($felt+1).', 1)=1';
    } elseif( $tabla == 'login' && $mezo == 'szalloda' ) {
        return '`'.get_db_tabla().'telephely_kapcs`.`telephely_id`=\''.$felt.'\'';

    // mszk_jovahagy feltétele: a munkalap döntésre vár státuszban van, és a hozzárendelt személy vagy Műszaki vezető vagy diszpécser
    } elseif( $tabla == 'munkalap' && $mezo == 'mszk_jovahagy' ) {
        if( $felt == 1 ) {
            return '`stat_id`=\''.MUNKALAP_STAT_DONTESRE_VAR.'\' AND (`jh1_szerep` IN (\''.MSZK_VEZETO.'\', \''.MUSZAKI_IGAZGATO.'\') OR `jh2_szerep` IN (\''.MSZK_VEZETO.'\', \''.MUSZAKI_IGAZGATO.'\'))';
        } else {
            return '`stat_id`=\''.MUNKALAP_STAT_DONTESRE_VAR.'\' AND (`jh1_szerep` NOT IN (\''.MSZK_VEZETO.'\', \''.MUSZAKI_IGAZGATO.'\') AND `jh2_szerep` NOT IN (\''.MSZK_VEZETO.'\', \''.MUSZAKI_IGAZGATO.'\'))';
        }
    } elseif( $tabla == 'munkalap' && $mezo == 'insert_dt' ) {
        $felt = str_replace('.', '-', $felt);
        if($op_origi=='empty'){
            $felt = '0000-00-00';
        }
        return '`'.get_db_tabla().'munkalap`.`insert_dt`'.$op.'\''.$felt.'\'';
    } elseif( $tabla == 'utemezett_feladatok' && $mezo == 'telephely_id' ) {
        return '`'.get_db_tabla().'utemezett_feladatok`.`telephely_id`=\''.$felt.'\'';
    } elseif( $tabla == 'utemezett_feladatok' && $mezo == 'id' ) {
        return '`'.get_db_tabla().'utemezett_feladatok`.`id`=\''.$felt.'\'';
        
    // Projket számlánál szűrni kell arra, hogy van-e kifizetése
    } elseif( $tabla == 'projektszamla' && $mezo == 'kifizetes_van_e' ) {
        $return = ' (SELECT COUNT(`'.get_db_tabla().'projektszamla_kifizetesek`.`recid`) FROM `'.get_db_tabla().'projektszamla_kifizetesek` WHERE `'.get_db_tabla().'projektszamla_kifizetesek`.`projektszamla_recid`=`'.get_db_tabla().'projektszamla`.`recid`)';
        if( $felt ) {
            $return .= ' > 0';
        } else {
            $return .= ' = 0';
        }
        return $return;
        
    // Projket számlánál szűrni kell arra, hogy kapcsolt szerződés van-e
    } elseif( $tabla == 'projektszamla' && $mezo == 'kapcsolt_szerzodes_van_e' ) {
        $return = ' (SELECT COUNT(`'.get_db_tabla().'projektszamla_tetel`.`recid`) FROM `'.get_db_tabla().'projektszamla_tetel` WHERE `'.get_db_tabla().'projektszamla_tetel`.`projektszamla_recid`=`'.get_db_tabla().'projektszamla`.`recid` AND `projektszerzodes_tetel_recid`!=0)';
        if( $felt ) {
            $return .= ' > 0';
        } else {
            $return .= ' = 0';
        }
        return $return;
        
    // Projket számlánál szűrni kell arra, hogy kapcsolt szerződés van-e
    } elseif( $tabla == 'projektszerzodes' && $mezo == 'kapcsolt_szamla_van_e' ) {
        $return = ' (SELECT COUNT(`'.get_db_tabla().'projektszamla_tetel`.`recid`) FROM `'.get_db_tabla().'projektszamla_tetel` WHERE `projektszerzodes_tetel_recid` IN (SELECT `recid` FROM `'.get_db_tabla().'projektszerzodes_tetel` WHERE `projektszerzodes_recid`=`'.get_db_tabla().'projektszerzodes`.`recid`))';
        if( $felt ) {
            $return .= ' > 0';
        } else {
            $return .= ' = 0';
        }
        return $return;
        
    } elseif( $tabla == 'projektkezeles' && $mezo == 'team_tag' ) {
        return '`'.get_db_tabla().'projektkezeles_team`.`login_recid`=\''.$felt.'\'';
    } elseif( $tabla == 'szallito' && $mezo == 'szakterulet' ) {
        return '`'.get_db_tabla().'szakter_kapcs`.`szakter_id`=\''.$felt.'\'';
    } else {

        // Mezőnevek pontosítása
        if( $tabla == 'anyagigenyles' && $mezo == 'nev' ) {
            $mezo = get_db_tabla().'anyagigenyles`.`'.$mezo;
        }
        
        return '`'.get_db_tabla().$tabla.'`.`'.$mezo.'`'.$op.'\''.$felt.'\'';
    }

}

// Összetett szűrés SQL-jét állítja elő egy stringből.
function get_o_szures_where_kieg ($feltetel, $tabla ) {

    $sql = '';
    $elemek = explode('#', $feltetel);
    
    // Legfrisebb feltételt kiszűrjük, ez majd egy Limit lesz az SQL végén.
    $legfrissebb_flag = 0;
    $limit            = '';
    $elemek_vegleges = array();
    foreach($elemek as $elem){
        
        // Ha a mező a legfrisebb, akkor bekapcsolunk egy flag-et, hogy a következő mezőig ne vegyünk fel elemeket a tömbbe
        if( $elem == 'me|legfrissebb' ){
            
            $legfrissebb_flag = 1;
            
        // Viszont a limitet kimentjük
        } elseif( $legfrissebb_flag && substr($elem, 0, 3) == 'fe|' ) {
            
            $limit = substr($elem, 3);
            
        // Ha nincs legfrissebb flag vagy már a következő mezőnél járunk, akkor mehetnek a tömbbe az elemek, amit a következő cilusban össze is rakunk SQL feltétellé
        } elseif( !$legfrissebb_flag || substr($elem, 0, 3) == 'me|' ) {
            
            $legfrissebb_flag = 0;
            array_push($elemek_vegleges, $elem);
            
        }
    }

    foreach($elemek_vegleges as $elem){

        switch(substr($elem, 0, 3)){
            case 'ny|':
                $sql .= '(';
            break;

            case 'me|':
                $mezo = substr($elem, 3);
            break;

            case 'op|':
                $op = substr($elem, 3);
            break;

            case 'fe|':

                $feltetel = substr($elem, 3);

                $sql .= get_szures_where_kieg($mezo, $op, $feltetel, $tabla);
                $mezo     = '';
                $op       = '';
                $feltetel = '';
                
            break;

            case 'za|':
                $sql .= ')';
            break;

            case 'es|':
                $sql .= ' AND ';
            break;

            case 'va|':
                $sql .= ' OR  ';
            break;

        }
    }
    
    $sql = str_replace(array(' AND  AND ', ' OR   AND ', ' AND  OR  ', ' AND ) AND '), array(' AND ', ' OR  ', ' OR  ', ') AND '), $sql);
    $sql = csonkit($sql, 5);
    
// echo $sql;
    return $sql;
    
}

// Az adatbázisba letárolt props stringből visszaadja azt a mezőértéket, amire szükségünk van
function get_props($props, $mezo){
    $props = htmlspecialchars_decode($props);
    $props = explode("\n", $props);
    foreach($props as $p){
        $akt_mezo = trim($p, '<');
        $akt_mezo = explode('>', $akt_mezo);
        $akt_mezo = $akt_mezo[0];
        if(strtolower($akt_mezo) == strtolower($mezo)){
            $p = explode('>', $p);
            $p = $p[1];
            $p = explode('<', $p);
            $p = $p[0];
            return $p;
        }
    }
}

// Történet fül tartalma
function get_tortenet($megnevezes){

    global $adatbazis_kapcsolat;

    $szamla_html = nl2('
        <div class="dt naplo">
            <div class="dtr nFejlec">
                <div class="dtc">Státusz</div>
                <div class="dtc">Kelte</div>
                <div class="dtc">Felhasználó</div>
                <div class="dtc">Státuszidő</div>
                <div class="dtc">Göngyölt státuszidő</div>
                <div class="dtc">Megjegyzés</div>
            </div>
    ');

    // Archivum táblák esetére
    $tabla = $megnevezes.'_statusz';
    if( substr($megnevezes, strlen($megnevezes)-5) == '_arch' ){
        $megnevezes = substr($megnevezes, 0, strlen($megnevezes)-5);
        $tabla      = $megnevezes.'_statusz_arch';
    }
    $sorok = $adatbazis_kapcsolat->s('stat_id insert_dt insert_usr statido gongyolt_statido ossz_statido text', $tabla, "`{$megnevezes}_recid` = '".$_GET['id']."'", 'recid desc');
// echo $adatbazis_kapcsolat->gp() . '<br>';
    if( $sorok ) {
        foreach($sorok as $sor) {

            $statusz['nev']     = getStatText($megnevezes, $sor['stat_id']);
            $statusz['szinkod'] = getStatText($megnevezes, $sor['stat_id'], 'szinkod');

            $szinkod = '';
            $sor['statusz'] = $statusz['nev'];
            if( $statusz['szinkod'] ) {
                $szinkod = ' style="background: '.$statusz['szinkod'].';"';
            }

            // Adatbázisba eleinte szóköz nélkül került be a nap, ezért biztonság kedvéért egy szóközt még beszúrunk, illetve a 0nap-ot is kiszűrjük (bár ez egy adatbázis frissítés után már felesleges)
            $sor['statido']          = str_replace(array('0nap', 'nap'), array('', ' nap'), $sor['statido']);
            $sor['gongyolt_statido'] = str_replace(array('0nap', 'nap'), array('', ' nap'), $sor['gongyolt_statido']);
            $sor['ossz_statido']     = str_replace(array('0nap', 'nap'), array('', ' nap'), $sor['ossz_statido']);
            $szamla_html .= nl2('
                <div class="dtr nSor"'.$szinkod.'>
                    <div class="dtc">'.$sor['statusz'].'</div>
                    <div class="dtc">'.date('Y.m.d H:i', strtotime($sor['insert_dt'])).'</div>
                    <div class="dtc">'.$sor['insert_usr'].'</div>
                    <div class="dtc">'.$sor['statido'].'</div>
                    <div class="dtc">'.$sor['gongyolt_statido'].'</div>
                    <div class="dtc">'.nl2br2( htmlspecialchars_decode($sor['text']) ).'</div>
                </div>
            ');
        }
    }

    $szamla_html .= '</div>';

    return $szamla_html;
}

// Adott tábla iktatószámának következő sorszámának lekérése
function get_iktatoszam_sorszam($tabla, $telephely_id = ''){

    global $adatbazis_kapcsolat;

    $db_error = false;

    // Ha kaptunk telephely_id-t, akkor azzal is számolnunk kell még, ez is kulcs része
    $where = '';
    if( $telephely_id ){
        $where = " AND `telephely_id`='{$telephely_id}'";
    }
    
    // A rendszeres karbantartás és ütemezett feladatoknál nem nullázunk sorszámot évente, hanem megyünk végig
    // FONTOS: ilyenkor az adatbázisban adott táblának csak egy rekordja lehet az iktszam_seq-ben
    $evszam_mentes = array('eseti_fenntartas', 'utemezett_feladatok', 'rendszeres_karbantartas');
    if( in_array($tabla, $evszam_mentes) ){
        $ikt_seq = $adatbazis_kapcsolat->s2('ev sorszam', 'iktszam_seq', "`tabla`='{$tabla}'{$where}");
    } else {
        $ikt_seq = $adatbazis_kapcsolat->s2('ev sorszam', 'iktszam_seq', "`tabla`='{$tabla}' AND `ev`='".date('y')."'{$where}");
    }
    if( $ikt_seq === false ) { $db_error = true; }
    
    if( $ikt_seq ) {

        // Sorszám
        $sorszam = $ikt_seq['sorszam'];

        // Sorszám növelés
        $sorszam++;

        // Letároljuk az új sorszámot
        if( in_array($tabla, $evszam_mentes) ){
            $sql = $adatbazis_kapcsolat -> u('iktszam_seq', array('sorszam'), array($sorszam), "`tabla`='{$tabla}'{$where}");
        } else {
            $sql = $adatbazis_kapcsolat -> u('iktszam_seq', array('sorszam'), array($sorszam), "`tabla`='{$tabla}' AND `ev`='".date('y')."'{$where}");
        }
        if( $sql === false ) { $db_error = true; }
        
    } else {
        
        // Sorszám
        $sorszam = 1;

        $mezok      = array('sorszam', 'ev', 'tabla');
        $ertekek    = array($sorszam, date('y'), $tabla);
        if( $telephely_id ){
            array_push($mezok,   'telephely_id');
            array_push($ertekek, $telephely_id );
        }
        
        $sql = $adatbazis_kapcsolat -> i('iktszam_seq', $mezok, $ertekek);
        if( $sql === false ) { $db_error = true; }
    }

    if($db_error){
        return false;
    } else {
        return $sorszam;
    }

}

// Státusz címkék és színeket visszaadó függvény ($_stat_text változó pedig a statusz_text.php -ben kerül előállításra)
function getStatText($tabla, $statid, $mezo = '') {

    global $_stat_text;

    if(!$mezo){
        $mezo = 'nev';
    }

    if( $_stat_text[$tabla][$statid] ) {
        if($mezo=='rovid' && !$_stat_text[$tabla][$statid][$mezo]){
            $mezo='nev';
        }
        return $_stat_text[$tabla][$statid][$mezo];
    }

    return '';
}

// Szerepkör címkéjét ne DB-ből kérjük le, erre itt egy függvény
function getSzerepkorNev($szerepkor_id) {

    // Az első 12 szerpkör név fix
    if( $szerepkor_id<13 ) {

        $szerepkorok = array(
                            '',
                            'Telepített személyzet',        // 1
                            'Mobilcsoport',                 // 2
                            'Külső kivitelező',             // 3
                            'MSZK vezető',                  // 4
                            'Műszaki vezető',               // 5
                            'Műszaki vezető helyettes',     // 6
                            'Területi felelős',             // 7
                            'Műszaki ügyintéző',            // 8
                            'Műszaki igazgató',             // 9
                            'Diszpécser',                   // 10
                            'Szálloda igazgató',            // 11
                            'Hibabejelentő',                // 12
                        );
        return $szerepkorok[$szerepkor_id];
        
    // A 13. szerepkörtől már dinamikus
    } else {

        global $adatbazis_kapcsolat;
        $szerepkor = $adatbazis_kapcsolat->s2('nev', 'szerepkor', "`recid`='{$szerepkor_id}'");
        return $szerepkor['nev'];
        
    }
    
}

// Szállodák nevét ne DB-ből kérjük már le, vagyis csak egyszer, loginnál
function getSzallodaNev($szalloda_id) {
    
    global $adatbazis_kapcsolat;
    
    // Ha nem lenne meg a szállodák session-ben (pl. ütemezők, amit cron futtat)
    if( !$_SESSION['telephelyek'] ) {
        $szalloda = $adatbazis_kapcsolat->s2('nev', 'telephely', "`id`='{$szalloda_id}'");
        return $szalloda['nev'];
    }
    
    // Egyébként sessionból a leggyorsabb visszaadni
    foreach ( $_SESSION['telephelyek'] as $telephely ) {
        if( $telephely['id'] == $szalloda_id ) {
            return $telephely['nev'];
        }
    }
}

// Szállodák nevét ne DB-ből kérjük már le, vagyis csak egyszer, loginnál
function getErtekLista($erteklista_csoport = '', $erteklista_id = '', $erteklista_recid = '') {
    if( $erteklista_csoport ) {
        foreach ( $_SESSION['erteklista'] as $erteklista ) {
            if( $erteklista['csoport'] == $erteklista_csoport && $erteklista['id'] == $erteklista_id ) {
                return $erteklista['info'];
            }
        }
    }
    if( $erteklista_recid ) {
        foreach ( $_SESSION['erteklista'] as $erteklista ) {
            if( $erteklista['recid'] == $erteklista_recid ) {
                return $erteklista['info'];
            }
        }
    }

}

// Minden véleményező ha töltött már véleményt, akkor nyitottra léptetjük a státuszt (hacsak nem abban van már eleve)
function prigeny_mindenki_velemenyezett_e($prigen_recid){

    global $adatbazis_kapcsolat;

    $db_error = false;

    $velemenyezok = $adatbazis_kapcsolat->s('login_recid', 'projektigenyles_kapcs', "`projektigenyles_recid`='$prigen_recid'");
    if( $velemenyezok === false ) { $db_error = true; }
    $minden_velemeny_van = true;
    foreach($velemenyezok as $velemenyezo){
        $van_velemeny = $adatbazis_kapcsolat->s('recid', 'projektigenyles_velemeny', "`projektigenyles_recid`='$prigen_recid' AND `login_recid`='{$velemenyezo['login_recid']}'");
        if( $van_velemeny === false ) { $db_error = true; }
        if(!$van_velemeny){
            $minden_velemeny_van = false;
        }
    }

    //Ha mindenkinek van véleménye, és nem nyitott a státusz eleve, akkor nyitottra állítjuk
    if($minden_velemeny_van){
        $statusz = $adatbazis_kapcsolat->s2('stat_id', 'projektigenyles', "`recid`='$prigen_recid'");
        if( $statusz === false ) { $db_error = true; }

        if($statusz['stat_id']!=PRIGENY_STAT_NYITOTT){
            if( !addStatusz( 'projektigenyles_statusz', $prigen_recid, PRIGENY_STAT_NYITOTT ) ) {
                $db_error = true;
            }
        }
    }

    if($db_error){
        return false;
    } else {
        return true;
    }
}

// Egy dátumról megmondja, hogy a hét melyik napjára esik és szövegesen visszaadja
function het_napja_txt($datum){
    $datum = str_replace('.', '-', $datum);
    $het_napjai = array(
                    1 => 'Hétfő',
                    2 => 'Kedd',
                    3 => 'Szerda',
                    4 => 'Csütörtök',
                    5 => 'Péntek',
                    6 => 'Szombat',
                    0 => 'Vasárnap',
                );

    return $het_napjai[date('w', strtotime($datum))];
}

// Valós dátum-e
function valos_datum_e($datum){
    $datum = substr(str_replace('.', '-', $datum), 0, 10);
    list($ev, $ho, $nap) = explode('-', $datum);
    return checkdate($ho, $nap, $ev);
}


// Többdimenziós tömb rendezése kulcs alapján
function tobbdim_tomb_kulcs_rendez($array, $on, $order=SORT_ASC){
    
    $new_array      = array();
    $sortable_array = array();

    if (count($array) > 0) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if ($k2 == $on) {
                        $sortable_array[$k] = $v2;
                    }
                }
            } else {
                $sortable_array[$k] = $v;
            }
        }

        $i = 0;
        
        foreach($sortable_array as $elemek){
            if( mb_substr($elemek, 0, 1, 'UTF-8')=='Á' ){
                $sortable_array[$i] = 'A'.mb_substr($elemek, 1, 0, 'UTF-8');
            }
            if( mb_substr($elemek, 0, 1, 'UTF-8')=='É' ){
                $sortable_array[$i] = 'E'.mb_substr($elemek, 1, 0, 'UTF-8');
            }
            if( mb_substr($elemek, 0, 1, 'UTF-8')=='Í' ){
                $sortable_array[$i] = 'I'.mb_substr($elemek, 1, 0, 'UTF-8');
            }
            if( mb_substr($elemek, 0, 1, 'UTF-8')=='Ó' || mb_substr($elemek, 0, 1, 'UTF-8')=='Ő' ){
                $sortable_array[$i] = 'O'.mb_substr($elemek, 1, 0, 'UTF-8');
            }
            if( mb_substr($elemek, 0, 1, 'UTF-8')=='Ú' || mb_substr($elemek, 0, 1, 'UTF-8')=='Ű' ){
                $sortable_array[$i] = 'U'.mb_substr($elemek, 1, 0, 'UTF-8');
            }
            $i++;
        }
        
        switch ($order) {
            case SORT_ASC:
                asort($sortable_array);
                break;
            case SORT_DESC:
                arsort($sortable_array);
                break;
        }

        foreach ($sortable_array as $k => $v) {
            $new_array[$k] = $array[$k];
        }
    }

    return $new_array;
}

// Bejelentések dokumentumainak másolása munkalapra
function bejelentes_dok_munkalapba_masol($bejelentes_id, $munkalap_id){

    global $adatbazis_kapcsolat;
    $db_error = false;

    $doksik = $adatbazis_kapcsolat->s('utvonal fajlnev loginnev', 'bejelentes_dok', "`bejelentes_id`='{$bejelentes_id}'");
    if( $doksik === false ) { $db_error = true; }
    $munkalap = $adatbazis_kapcsolat->s2('ikt_szam', 'munkalap', "`recid`='{$munkalap_id}'");
    if( $munkalap === false ) { $db_error = true; }
    foreach($doksik as $doc){
        $utvonal      = '../'.get_utvonal().'munkalap/'.str_replace('/', '_', $munkalap['ikt_szam']).'/';
        $utvonal_regi = '../'.get_utvonal().'bejelentes/';
        $fajlnev      = basename($doc['utvonal']);

        $konytarak = explode('/', $utvonal);
        $akt_utvonal = '';
        foreach($konytarak as $konyvtar){
            $akt_utvonal .= $konyvtar.'/';
            if(!is_dir($akt_utvonal)){
                mkdir($akt_utvonal);
            }
        }

        // Fájl másolása
        copy($utvonal_regi.$doc['utvonal'], $utvonal.$fajlnev);

        $sql = $adatbazis_kapcsolat->i('munkalap_dok',  array('munkalap_id', 'utvonal',                                                 'fajlnev',       'loginnev',      'insert_dt',    'insert_usr',                              'update_usr'                             ),
                                                        array($munkalap_id,  str_replace('/', '_', $munkalap['ikt_szam']).'/'.$fajlnev, $doc['fajlnev'], $doc['loginnev'],'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],    $_SESSION[get_db_tabla().'teljes_nev']   )
                                        );
        if( $sql === false ) { $db_error = true; }
    }

    // Darabszám kiszámítása
    $dok_db = $adatbazis_kapcsolat->s('recid', 'munkalap_dok', "`munkalap_id`='{$munkalap_id}'");
    if( $dok_db === false ) { $db_error = true; }
    $sql = $adatbazis_kapcsolat->u('munkalap', array('dok_db'), array(count($dok_db)), "`recid`='{$munkalap_id}'");
    if( $sql === false ) { $db_error = true; }

    if($db_error){
        return false;
    } else {
        return true;
    }
}

// Státusz időt, ami szövegesen van eltárolva, másodpercekké alakítja
function statido_to_sec($string){
    list($napok, $opmp) = explode('nap', $string);
    $napok = trim($napok);

    list($ora, $perc, $mp) = explode(':', trim($opmp));

    return (60*60*24*$napok)+($ora*60*60)+($perc*60)+$mp;
}

// Levélküldés előtt jogot, értesítés pipát vizsgálunk, levél tartalmát és címzetteket kiszámoljuk
function level_felkeszit_es_kuld( $tabla = '', $recid = '', $ert_pipa = '', $sablon = '', $cimzett = '', $extra_szoveg = '', $cimzett_login_recid = '', $cimzett_mailcimek = '' ) {

    global $adatbazis_kapcsolat;
    global $db_kapcs;
    $cimzett_mail = '';
    $cimzett_nev  = '';
    $felhasznalonak_szo = 'felhasználónak';


    // Kinek menjen a mail? Egy $cimzettek tömböt kell előállítani.
    // Illetve az üzenet törzse is itt kerül beállításra
    
    // Ha a értesítés pipa értéke '-1', akkor nem vizsgáljuk a `flags_ert` oszlop értékét, tehát az értesítési pipákat nem figyeljük.
    $sql_ertesites_kieg = " AND substr(`flags_ert`, ".($ert_pipa+1).", 1)=1";
    if( $ert_pipa == '-1' ) {
        $sql_ertesites_kieg = '';
    }

    switch ( $cimzett ) {
        // Anyagigénylőnek
        case 'anyagigenylo':

            $felhasznalonak_szo = 'anyagigénylőnek';

            $anyagigenyles = $adatbazis_kapcsolat->s2('ikt_szam cikk_id nev igmen cikkmee loginnev', 'anyagigenyles', "`recid`='{$recid}'");

            $mit            = array('{IKT_SZAM}',               '{CIKKSZAM}',               '{CIKKNEV}',            '{MENNY}'                                                                           );
            $mivel          = array($anyagigenyles['ikt_szam'], $anyagigenyles['cikk_id'],  $anyagigenyles['nev'],  number_format($anyagigenyles['igmen'], 2, ',', ' ').' '.$anyagigenyles['cikkmee']   );
            $uzi            = str_replace( $mit, $mivel, fajlbol_olvas('../sablon/admin/mail/anyagigenyles.html') );
            // Aktív-e és nem virtuális-e a felhasználó, van-e mailcíme? Ha nem, kilépünk
            $login = $adatbazis_kapcsolat -> s2('teljes_nev usermail usertel aktiv virtual szemelyzet_id flags_ert', 'login', "`nev` = '{$anyagigenyles['loginnev']}'");
            if(!$login['aktiv'] || $login['virtual'] || !$login['usermail'] || ($ert_pipa!='-1' && substr($login['flags_ert'], $ert_pipa, 1)!=1) ){
                return false;
            }

            // Címzett adatait kiszámoljuk
            $cimzett_nev   = $login['teljes_nev'];
            $cimzett_mail  = $login['usermail'];
            $telefon       = $login['usertel'];
            $szemelyzet_id = $login['szemelyzet_id'];

            $cimzettek = array( array('nev' => $anyagigenyles['loginnev'], 'teljes_nev' => $cimzett_nev, 'usermail' => $cimzett_mail, 'usertel' => $telefon, 'szemelyzet_id' => $szemelyzet_id) );
        break;

        // Bejelentőnek
        case 'bejelento':

            $felhasznalonak_szo = 'bejelentőnek';

            if( substr($recid, 0, 1) == 'm' ) {
                $munkalap   = $adatbazis_kapcsolat->s2('text', 'munkalap', "`recid`='".substr($recid, 1)."'");
                $hiba       = htmlspecialchars_decode( nl2br2( $munkalap['text'] ) );
            } else {
                $munkalap_kapcs = $adatbazis_kapcsolat->s2('munkalap_id', 'munkalap_kapcs', "`bejelentes_id`='{$recid}'");
                $bejelentes     = $adatbazis_kapcsolat->s2('ikt_szam usr_nev usr_email usr_telefon stat_id telephely_id obj_id loginnev insert_dt hiba_id', $tabla, "`recid`='{$recid}'");
                $cimzett_nev    = $bejelentes['usr_nev'];
                $cimzett_mail   = $bejelentes['usr_email'];
                $telefon        = $bejelentes['usr_telefon'];
                $bejelentes['insert_dt'] = str_replace('-', '.', $bejelentes['insert_dt']);
                $hiba = getErtek('info', 'erteklista', 'recid', $bejelentes['hiba_id']);
            }

            $mit            = array('{IKT_SZAM}',            '{SZALLODA}',                                                    '{INS_DT}',                '{HIBA}',      '{OBJ}'                                                 );
            $mivel          = array($bejelentes['ikt_szam'], getErtek('nev', 'telephely', 'id', $bejelentes['telephely_id']),  $bejelentes['insert_dt'], $hiba,         getErtek('nev', 'obj', 'recid', $bejelentes['obj_id'])  );
            $uzi            = str_replace( $mit, $mivel, fajlbol_olvas('../sablon/admin/mail/'.$cimzett.'.html') );

            // Aktív-e és nem virtuális-e a felhasználó, van-e mailcíme? Ha nem, kilépünk
            $login = $adatbazis_kapcsolat -> s2('teljes_nev usermail usertel aktiv virtual szemelyzet_id flags_ert', 'login', "`nev` = '{$bejelentes['loginnev']}'");
            if(!$login['aktiv'] || $login['virtual'] || !$login['usermail'] || ($ert_pipa!='-1' && substr($login['flags_ert'], $ert_pipa, 1)!=1) ){
                return false;
            }

            // Ha még nincs meg a címzett, akkor a login táblából számítjuk
            if(!$cimzett_nev) { $cimzett_nev   = $login['teljes_nev'];  }
            if(!$cimzett_mail){ $cimzett_mail  = $login['usermail'];    }
            if(!$telefon)     { $telefon       = $login['usertel'];     }

            $cimzettek = array( array('nev' => $bejelentes['loginnev'], 'teljes_nev' => $cimzett_nev, 'usermail' => $cimzett_mail, 'usertel' => $telefon, 'szemelyzet_id' => $bejelentes['szemelyzet_id']) );
        break;

        // Diszpécserek központi címe
        case 'diszpecser_kozponti':

            $felhasznalonak_szo = 'központi diszpécsernek';
            $bejelentes     = $adatbazis_kapcsolat->s2('ikt_szam usr_nev usr_email usr_telefon stat_id telephely_id obj_id loginnev insert_dt hiba_id', $tabla, "`recid`='{$recid}'");

            $munkalapszamok = '';
            $munkalapok = $adatbazis_kapcsolat->s('munkalap_id', 'munkalap_kapcs', "`bejelentes_id`='{$recid}'");
            foreach($munkalapok as $munkalap){
                $munkalap = $adatbazis_kapcsolat->s2('ikt_szam', 'munkalap', "`recid`='{$munkalap['munkalap_id']}'");
                $munkalapszamok .= '<br>Munkalapszám: '.$munkalap['ikt_szam']."\r\n";
            }

            $bejelentes['insert_dt'] = str_replace('-', '.', $bejelentes['insert_dt']);

            $mit            = array('{MUNKALAPSZAMOK}', '{IKT_SZAM}',            '{SZALLODA}',                                                    '{INS_DT}',                                       '{HIBA}',                                                        '{OBJ}'                                                );
            $mivel          = array($munkalapszamok,    $bejelentes['ikt_szam'], getErtek('nev', 'telephely', 'id', $bejelentes['telephely_id']),  str_replace('-', '.', $bejelentes['insert_dt']), getErtek('info', 'erteklista', 'recid', $bejelentes['hiba_id']), getErtek('nev', 'obj', 'recid', $bejelentes['obj_id']) );
            $uzi            = str_replace( $mit, $mivel, fajlbol_olvas('../sablon/admin/mail/'.$sablon.'.html') );

            $cimzettek = array( array('nev' => 'htdiszp', 'teljes_nev' => 'Handari Tamás', 'usermail' => 'handari@szinvatrend.hu', 'usertel' => '70-210-1312', 'szemelyzet_id' => '10') );
        break;

        // Elfelejtett jelszó kérése
        case 'elfelejtettJelszo':

            $login      = $adatbazis_kapcsolat->s2('nev teljes_nev beosztas_id reszleg_id usertel usermail usertel aktiv', 'login', "`recid`='{$recid}'");
            if(!$login){
                return 'Nem ismert felhasználói név!<br>Kérését így nem tudjuk továbbítani!';
            }
            if(!$login['aktiv']){
                return 'Inaktív felhasználói név!<br>Kérését így nem tudjuk továbbítani!';
            }
            $beosztas   = erteklista(ERTEKLISTA_BEOSZTAS, $login['beosztas_id']);
            $reszleg    = erteklista(ERTEKLISTA_RESZLEG, $login['reszleg_id']);
            $mit        = array('{LOGINNEV}',  '{TELJES_NEV}',        '{BEOSZTAS}', '{RESZLEG}',    '{TELEFON}',       '{EMAIL}'          );
            $mivel      = array($login['nev'], $login['teljes_nev'],  $beosztas,    $reszleg,       $login['usertel'], $login['usermail'] );
            $uzi        = str_replace( $mit, $mivel, fajlbol_olvas('../sablon/admin/mail/'.$cimzett.'.html') );

            $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail aktiv virtual szemelyzet_id', 'login', "`aktiv`=1 AND `virtual`=0 {$sql_ertesites_kieg}");
            if( !$cimzettek ) {
                return false;
            }

        break;

        // Elfelejtett jelszó küldése
        case 'elfelejtettJelszoKuld':

            $domain     = get_http().$_SERVER['HTTP_HOST'];
            $domain     = '<a href="'.$domain.'" target="_blank">'.$domain.'</a>';
            $mit        = array('{DOMAIN}'  );
            $mivel      = array($domain     );
            $uzi        = str_replace( $mit, $mivel, fajlbol_olvas('../sablon/admin/mail/'.$cimzett.'.html') );

            $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail usertel aktiv virtual szemelyzet_id', 'login', "`recid`='{$recid}'");
            if( !$cimzettek ) {
                return false;
            }

        break;
        
        // Projekt módosításkor a felelősét értesítjük
        case 'projektkezeles_felelose':

            // Projektfelelős a címzett
            if( $recid ){
                $projektkezeles = $adatbazis_kapcsolat->s2('pfelelos', 'projektkezeles', "`recid`='{$recid}'");
            } else {
                $projektkezeles['pfelelos'] = $_SESSION[get_db_tabla().'login_recid'];
            }

            $mit        = array('{IKT_SZAM}',       '{NEV}',       '{SZALLODA}'                             );
            $mivel      = array($_POST['ikt_szam'], $_POST['nev'], getSzallodaNev($_POST['telephely_id'])   );
            $uzi = str_replace( $mit, $mivel, fajlbol_olvas('../sablon/admin/mail/'.$cimzett.'.html') );
            
            
            // Ha van konkrét Login címezve (pl. Projekt felelős változott és mindkettőnek ki kell küldeni a levelet.
            if( $cimzett_login_recid ){
                $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail aktiv virtual szemelyzet_id', 'login', "`aktiv`=1 AND `virtual`=0 AND `recid`='{$cimzett_login_recid}'");
            } else {
                $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail aktiv virtual szemelyzet_id', 'login', "`aktiv`=1 AND `virtual`=0 AND `recid`='{$projektkezeles['pfelelos']}'");
            }

            if( !$cimzettek ) {
                return false;
            }

        break;
        
        // Projekt Team tag hozzáadása
        case 'projektkezeles_tag_felveve':
            
            $projektkezeles = $adatbazis_kapcsolat->s2('ikt_szam telephely_id nev pfelelos', 'projektkezeles', "`recid`='{$recid}'");
            $mit        = array('{IKT_SZAM}',                '{NEV}',                '{SZALLODA}'                                       );
            $mivel      = array($projektkezeles['ikt_szam'], $projektkezeles['nev'], getSzallodaNev($projektkezeles['telephely_id'])    );
            $uzi = str_replace( $mit, $mivel, fajlbol_olvas('../sablon/admin/mail/'.$cimzett.'.html') );
            
            if( $cimzett_mailcimek ) {
                $cimzettek = array();
                $cimzettek[0]['usermail'] = $cimzett_mailcimek;
            } else {
                $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail aktiv virtual szemelyzet_id', 'login', "`aktiv`=1 AND `virtual`=0 AND `recid`='{$cimzett_login_recid}'");
            }
            if( !$cimzettek ) {
                return false;
            }

        break;
        
        // Projekt Team tag törlése
        case 'projektkezeles_tag_torolve':
            
            $projektkezeles = $adatbazis_kapcsolat->s2('ikt_szam telephely_id nev pfelelos', 'projektkezeles', "`recid`='{$recid}'");

            $mit        = array('{IKT_SZAM}',                '{NEV}',                '{SZALLODA}'                                       );
            $mivel      = array($projektkezeles['ikt_szam'], $projektkezeles['nev'], getSzallodaNev($projektkezeles['telephely_id'])    );
            $uzi = str_replace( $mit, $mivel, fajlbol_olvas('../sablon/admin/mail/'.$cimzett.'.html') );
            
            if( $cimzett_mailcimek ) {
                $cimzettek = array();
                $cimzettek[0]['usermail'] = $cimzett_mailcimek;
            } else {
                $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail aktiv virtual szemelyzet_id', 'login', "`aktiv`=1 AND `virtual`=0 AND `recid`='{$cimzett_login_recid}'");
            }
            if( !$cimzettek ) {
                return false;
            }

        break;
        
        // Projekt módosításkor a felelősét értesítjük
        case 'projektszerzodes_ujdok':

            // Projektfelelős a címzett, számoljuk ki, ki a felelős
            $projektszerzodes = $adatbazis_kapcsolat->s2('pr_szam sz_szam targy telephely_id', 'projektszerzodes', "`recid`='{$recid}'");
            $projektkezeles   = $adatbazis_kapcsolat->s2('pfelelos', 'projektkezeles', "`ikt_szam`='{$projektszerzodes['pr_szam']}'");
            
            // Nem kell sablon beolvasni, mert a levél tartalmát itt elő tudjuk állítani:
            $uzi = 'Szerződés szám: '.$projektszerzodes['sz_szam'].'
                <br>Tárgy: '. resz_string($projektszerzodes['targy'], 100).'
                <br>Projekt szám: <a href="'. get_http().get_domain().'/admin/projektkezeles.php?&amp;dokumentumok&amp;mezo=ikt_szam&amp;op=equal&amp;felt='.$projektszerzodes['pr_szam'].'" target="_blank">'.$projektszerzodes['pr_szam'].'</a>
                <br>Szálloda: '. getSzallodaNev($projektszerzodes['telephely_id']).'
                <br>
                '; 
            
            // Ha van konkrét Login címezve (pl. Projekt felelős változott és mindkettőnek ki kell küldeni a levelet.
            if( $cimzett_login_recid ) {
                $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail aktiv virtual szemelyzet_id', 'login', "`aktiv`=1 AND `virtual`=0 AND `recid`='{$cimzett_login_recid}'");
            } else {
                $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail aktiv virtual szemelyzet_id', 'login', "`aktiv`=1 AND `virtual`=0 AND `recid`='{$projektkezeles['pfelelos']}'");
            }

            if( !$cimzettek ) {
                return false;
            }

        break;

        // Bejelentés vizsgál joggal rendelkezőknek
        case '_jog'.JOG_BEJELENTES_VIZSGAL:

            $bejelentes     = $adatbazis_kapcsolat->s2('ikt_szam usr_nev usr_email usr_telefon stat_id telephely_id obj_id loginnev insert_dt hiba_id', $tabla, "`recid`='{$recid}'");
            $mit            = array('{IKT_SZAM}',            '{SZALLODA}',                                                    '{INS_DT}',                '{HIBA}',                                                        '{OBJ}'                                                 );
            $mivel          = array($bejelentes['ikt_szam'], getErtek('nev', 'telephely', 'id', $bejelentes['telephely_id']),  $bejelentes['insert_dt'], getErtek('info', 'erteklista', 'recid', $bejelentes['hiba_id']), getErtek('nev', 'obj', 'recid', $bejelentes['obj_id'])  );
            $uzi            = str_replace( $mit, $mivel, fajlbol_olvas('../sablon/admin/mail/bejelento.html') );

            // Több címzett is van
            $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail usertel aktiv virtual szemelyzet_id', 'login', "substr(`flags`, ".(JOG_BEJELENTES_VIZSGAL+1).", 1)=1 AND `aktiv`=1 AND `virtual`=0 {$sql_ertesites_kieg}");
            if( !$cimzettek ) {
                return false;
            }

        break;

        // Munkalap igazolás joggal rendelkezőknek
        case '_jog'.JOG_MUNKALAP_IGAZOLAS:

            $munkalap       = $adatbazis_kapcsolat->s2('ikt_szam telephely_id obj_id text szemelyzet_id szallito_id insert_dt', $tabla, "`recid`='{$recid}'");
            $munkalap_kapcs = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$recid}'");
            $bejelentes     = $adatbazis_kapcsolat->s2('hiba_id', 'bejelentes', "`recid`='{$munkalap_kapcs['bejelentes_id']}'");

            // Ha nincs bejeletése, akkor más a hiba
            $hiba = getErtek('info', 'erteklista', 'recid', $bejelentes['hiba_id']);
            if(!$bejelentes){
                $hiba = htmlspecialchars_decode( nl2br2( $munkalap['text'] ) );
            }

            // Ha mobil jelentette készre, akkor felveszünk egy Készre jelentette: adatot is
            $keszre_jelentette = '';
            if( $sablon == 'munkalapKeszreKulso' && $_SESSION[get_db_tabla().'szemelyzet_id'] == MOBILCSOPORT ){
                $login = $adatbazis_kapcsolat -> s2('mobil', 'login', "`nev` = '{$_SESSION[get_db_tabla().'user']}'");
                $keszre_jelentette .= $_SESSION[get_db_tabla().'teljes_nev'].' '.getSzerepkorNev($_SESSION[get_db_tabla().'szemelyzet_id']).' '.$login['mobil'];
            }

            // Ha külsős munkát jelentettek készre, alul jelenjen meg a Külső kivitelező
            if( $munkalap['szemelyzet_id']==KULSO_KIVITELEZO ){
                $keszre_jelentette .= "\r\n".'<br>Külső kivitelező: '. getErtek('nev', 'szallito', 'id', $munkalap['szallito_id']);
            }

            $felhasznalonak_szo = 'munkalap igazolónak';

            $mit            = array('{IKT_SZAM}',          '{SZALLODA}',                                                   '{INS_DT}',             '{HIBA}',    '{OBJ}',                                              '{KESZRE_JELENTETTE}' );
            $mivel          = array($munkalap['ikt_szam'], getErtek('nev', 'telephely', 'id', $munkalap['telephely_id']),  $munkalap['insert_dt'], $hiba,       getErtek('nev', 'obj', 'recid', $munkalap['obj_id']), $keszre_jelentette    );
            $uzi            = str_replace( $mit, $mivel, fajlbol_olvas('../sablon/admin/mail/munkalaposoknak.html') );

            // Több címzett is van
            $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail usertel aktiv virtual szemelyzet_id', 'login', "substr(`flags`, ".(JOG_MUNKALAP_IGAZOLAS+1).", 1)=1 AND `aktiv`=1 AND `virtual`=0 $sql_ertesites_kieg");
            if( !$cimzettek ) {
                return false;
            }

        break;

        // Munkalap igazolás joggal rendelkezőknek
        case '_jog'.JOG_MUNKALAP_KULSOS_DONTES:

            $munkalap       = $adatbazis_kapcsolat->s2('ikt_szam telephely_id obj_id jh_ert insert_dt', $tabla, "`recid`='{$recid}'");
            $munkalap_kapcs = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$recid}'");
            $bejelentes     = $adatbazis_kapcsolat->s2('hiba_id', 'bejelentes', "`recid`='{$munkalap_kapcs['bejelentes_id']}'");

            $mit            = array('{IKT_SZAM}',          '{SZALLODA}',                                                   '{INS_DT}',             '{HIBA}',                                                        '{OBJ}',                                              '{KESZRE_JELENTETTE}' );
            $mivel          = array($munkalap['ikt_szam'], getErtek('nev', 'telephely', 'id', $munkalap['telephely_id']),  $munkalap['insert_dt'], getErtek('info', 'erteklista', 'recid', $bejelentes['hiba_id']), getErtek('nev', 'obj', 'recid', $munkalap['obj_id']), ''                    );
            $uzi            = str_replace( $mit, $mivel, fajlbol_olvas('../sablon/admin/mail/munkalaposoknak.html') );

            // Több címzett is van
            $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail usertel aktiv virtual szemelyzet_id', 'login', "substr(`flags`, ".(JOG_MUNKALAP_KULSOS_DONTES+1).", 1)=1 AND `jh_ft_ig`>='{$munkalap['jh_ert']}' AND `aktiv`=1 AND `virtual`=0 $sql_ertesites_kieg");
            if( !$cimzettek ) {
                return false;
            }

        break;

        // Munkalap külső kivitelező előkészítésekor
        case '_jog'.JOG_MUNKALAP_KULSOS_ELOKESZIT:

            $munkalap       = $adatbazis_kapcsolat->s2('ikt_szam telephely_id szemelyzet_id obj_id insert_dt', $tabla, "`recid`='{$recid}'");
            $munkalap_kapcs = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$recid}'");
            $bejelentes     = $adatbazis_kapcsolat->s2('hiba_id', 'bejelentes', "`recid`='{$munkalap_kapcs['bejelentes_id']}'");

            $mit            = array('{IKT_SZAM}',          '{SZALLODA}',                                                   '{INS_DT}',             '{HIBA}',                                                        '{OBJ}',                                              '{KESZRE_JELENTETTE}' );
            $mivel          = array($munkalap['ikt_szam'], getErtek('nev', 'telephely', 'id', $munkalap['telephely_id']),  $munkalap['insert_dt'], getErtek('info', 'erteklista', 'recid', $bejelentes['hiba_id']), getErtek('nev', 'obj', 'recid', $munkalap['obj_id']), ''                    );
            $uzi            = str_replace( $mit, $mivel, fajlbol_olvas('../sablon/admin/mail/munkalaposoknak.html') );

            // Előkészítéskor a ML hozzárendelt személyzet szerepkörű és "külsős: előkészítés jogú" felhasználók
            $sql_kieg = '';
            if($sablon=='munkalapElokeszit'){
                $sql_kieg = "`szemelyzet_id`='{$munkalap['szemelyzet_id']}' AND ";
            }

            // Több címzett is van
            $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail usertel aktiv virtual szemelyzet_id', 'login', "{$sql_kieg} substr(`flags`, ".(JOG_MUNKALAP_KULSOS_ELOKESZIT+1).", 1)=1 AND `aktiv`=1 AND `virtual`=0 $sql_ertesites_kieg");
            if( !$cimzettek ) {
                return false;
            }

        break;

        // Munkalap Külső kivitelező jóváhagyás után
        case '_jog'.JOG_MUNKALAP_KULSOS_MEGREND:

            $munkalap       = $adatbazis_kapcsolat->s2('ikt_szam telephely_id obj_id insert_dt', $tabla, "`recid`='{$recid}'");
            $munkalap_kapcs = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$recid}'");
            $bejelentes     = $adatbazis_kapcsolat->s2('hiba_id', 'bejelentes', "`recid`='{$munkalap_kapcs['bejelentes_id']}'");

            $mit            = array('{IKT_SZAM}',          '{SZALLODA}',                                                   '{INS_DT}',             '{HIBA}',                                                        '{OBJ}',                                              '{KESZRE_JELENTETTE}' );
            $mivel          = array($munkalap['ikt_szam'], getErtek('nev', 'telephely', 'id', $munkalap['telephely_id']),  $munkalap['insert_dt'], getErtek('info', 'erteklista', 'recid', $bejelentes['hiba_id']), getErtek('nev', 'obj', 'recid', $munkalap['obj_id']), ''                    );
            $uzi            = str_replace( $mit, $mivel, fajlbol_olvas('../sablon/admin/mail/munkalaposoknak.html') );

            // Több címzett is van
            $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail usertel aktiv virtual szemelyzet_id', 'login', "substr(`flags`, ".(JOG_MUNKALAP_KULSOS_MEGREND+1).", 1)=1 AND `aktiv`=1 AND `virtual`=0 $sql_ertesites_kieg");
            if( !$cimzettek ) {
                return false;
            }

        break;

        // Munkalap vizsgálat joggal rendelkezőknek
        case '_jog'.JOG_MUNKALAP_VIZSGALAT:

            $munkalap       = $adatbazis_kapcsolat->s2('ikt_szam telephely_id obj_id insert_dt', $tabla, "`recid`='{$recid}'");
            $munkalap_kapcs = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$recid}'");
            $bejelentes     = $adatbazis_kapcsolat->s2('hiba_id', 'bejelentes', "`recid`='{$munkalap_kapcs['bejelentes_id']}'");

            $mit            = array('{IKT_SZAM}',          '{SZALLODA}',                                                   '{INS_DT}',             '{HIBA}',                                                        '{OBJ}',                                              '{KESZRE_JELENTETTE}' );
            $mivel          = array($munkalap['ikt_szam'], getErtek('nev', 'telephely', 'id', $munkalap['telephely_id']),  $munkalap['insert_dt'], getErtek('info', 'erteklista', 'recid', $bejelentes['hiba_id']), getErtek('nev', 'obj', 'recid', $munkalap['obj_id']), ''                    );
            $uzi            = str_replace( $mit, $mivel, fajlbol_olvas('../sablon/admin/mail/munkalaposoknak.html') );

            // Több címzett is van
            $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail usertel aktiv virtual szemelyzet_id', 'login', "substr(`flags`, ".(JOG_MUNKALAP_VIZSGALAT+1).", 1)=1 AND `aktiv`=1 AND `virtual`=0 $sql_ertesites_kieg");
            if( !$cimzettek ) {
                return false;
            }

        break;

        // Munkalap Saját feladatok megtekintése joggal rendelkezőknek
        case '_jog'.JOG_MUNKALAP_MEGTEKINT_FELADAT:

            $munkalap       = $adatbazis_kapcsolat->s2('ikt_szam telephely_id obj_id insert_dt', $tabla, "`recid`='{$recid}'");
            $munkalap_kapcs = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$recid}'");
            $bejelentes     = $adatbazis_kapcsolat->s2('hiba_id', 'bejelentes', "`recid`='{$munkalap_kapcs['bejelentes_id']}'");

            $mit            = array('{IKT_SZAM}',          '{SZALLODA}',                                                   '{INS_DT}',             '{HIBA}',                                                        '{OBJ}',                                              '{KESZRE_JELENTETTE}' );
            $mivel          = array($munkalap['ikt_szam'], getErtek('nev', 'telephely', 'id', $munkalap['telephely_id']),  $munkalap['insert_dt'], getErtek('info', 'erteklista', 'recid', $bejelentes['hiba_id']), getErtek('nev', 'obj', 'recid', $munkalap['obj_id']), ''                    );
            $uzi            = str_replace( $mit, $mivel, fajlbol_olvas('../sablon/admin/mail/munkalaposoknak.html') );

            // Több címzett is van
            $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail usertel aktiv virtual szemelyzet_id', 'login', "substr(`flags`, ".(JOG_MUNKALAP_MEGTEKINT_FELADAT+1).", 1)=1 AND `aktiv`=1 AND `virtual`=0 $sql_ertesites_kieg");
            if( !$cimzettek ) {
                return false;
            }

        break;

        // Anyagigénylés küldés jóváhagyásra
        case '_jog'.JOG_ANYAGIGENYLES_JOVAHAGY:
            
            $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail usertel aktiv virtual szemelyzet_id', 'login', "substr(`flags`, ".(JOG_ANYAGIGENYLES_JOVAHAGY+1).", 1)=1 AND `aktiv`=1 AND `virtual`=0 $sql_ertesites_kieg");
            if( !$cimzettek ) {
                return false;
            }

        break;
    }

    // Esemény neve
    switch ( $sablon ) {

        case 'anyagigenylesJovahagy':
            $esemeny =  'anyagigénylés elküldése jóváhagyásrakor';
            $targy   =  get_nev().' anyagigénylések jóváhagyásra várnak';
            $uzi     =  'Anyagigénylések várnak az Ön jóváhagyására.
                        <br>'.fajlbol_olvas('../sablon/admin/mail/alairas.html');
        break;

        case 'anyagigenylesSztorno':
            $esemeny =  'anyagigénylés sztornózásakor';
            $targy   =  get_nev().' anyagigénylés sztornózva';
            $uzi     =  'Az Ön által igényelt anyag sztornózásra került.
                        <br>'.
                        $extra_szoveg.
                        $uzi.
                        fajlbol_olvas('../sablon/admin/mail/alairas.html');
        break;

        case 'bejelentesElutasit':
            $esemeny =  'bejelentés elutasításakor';
            $targy   =  get_nev().' bejelentett hiba elutasítva';
            $uzi     =  'Az Ön által bejelentett hiba elutasításra került.
                        <br>'.
                        $extra_szoveg.
                        $uzi.
                        fajlbol_olvas('../sablon/admin/mail/alairas.html');
        break;

        case 'bejelentesReklElutasit':
            if( $tabla == 'bejelentes' ){
                $esemeny =  'bejelentés reklamáció elutasításakor';
                $targy   =  get_nev().' hibabejelentés lezárásra került';
            }elseif( $tabla == 'munkalap' ){
                $esemeny =  'munkalap lezárásakor';
                $targy   =  get_nev().' munkalap lezárásra került';
            }
            $uzi     =  'Az Ön által bejelentett reklamáció elutasításra került.
                        <br>'.
                        $extra_szoveg.
                        $uzi.
                        fajlbol_olvas('../sablon/admin/mail/alairas.html');
        break;

        case 'bejelentesIgazolElutasit':
            $esemeny =  'bejelentés igazolásának elutasításakor';
            $targy   =  get_nev().' hibabejelentés vizsgálatra vár';
            $uzi     =  'A bejelentés vizsgálatra vár.
                        <br>'.
                        $uzi.fajlbol_olvas('../sablon/admin/mail/alairas.html');
        break;

        case 'bejelentes_torolve':

            $bejelentes = $adatbazis_kapcsolat->s2('ikt_szam', $tabla, "`recid`='{$recid}'");

            $esemeny =  'bejelentés törlésekor';
            $targy   =  get_nev().' '.$bejelentes['ikt_szam'].' hibabejelentést törölték';
            $uzi     =  $uzi.fajlbol_olvas('../sablon/admin/mail/alairas.html');
        break;

        case 'elfelejtettJelszo':
            $login   = $adatbazis_kapcsolat->s2('teljes_nev', 'login', "`recid`='{$recid}'");
            $esemeny =  'elfelejtett jelszó igénylésekor';
            $targy   =  $login['teljes_nev'].' jelszava törlését kéri '.get_nev().' rendszerhez';
            $uzi     =  $uzi.fajlbol_olvas('../sablon/admin/mail/alairas.html');
        break;

        case 'elfelejtettJelszoKuld':
            $esemeny =  'elfelejtett jelszó kiküldésekor';
            $targy   =  get_nev().' elfelejtett jelszó';
            $uzi     =  $uzi.fajlbol_olvas('../sablon/admin/mail/alairas.html');
        break;

        case 'munkalapKeszre':
            $esemeny =  'munkalap készre jelentéskor';
            $targy   =  get_nev().' bejelentett hiba javítása elkészült';
            $uzi     =  'Az Ön által bejelentett hiba javítása sikeresen befejeződött.
                        <br>'.
                        $uzi.fajlbol_olvas('../sablon/admin/mail/alairas.html');
        break;

        case 'munkalapKeszreKulso':
            $esemeny =  'munkalap készre jelentéskor';
            $targy   =  get_nev().' bejelentett hiba javítása igazolásra vár';
            $uzi     =  'A bejelentett hiba javítása befejeződött.
                        <br>'.
                        $uzi.fajlbol_olvas('../sablon/admin/mail/alairas.html');
        break;

        case 'munkalapVizsgalatUtan':
            $esemeny =  'munkalap vizsgálat utáni lezárásakor';
            $targy   =  get_nev().' hibabejelentés lezárásra került';
            $uzi     =  'Az Ön által bejelentett reklamáció elutasításra került.
                        <br>'.
                        $extra_szoveg.
                        $uzi.fajlbol_olvas('../sablon/admin/mail/alairas.html');
        break;

        case 'munkalapElokeszit':
            $esemeny =  'munkalapon külső kivitelező előkészítésekor';
            $targy   =  get_nev().' munkalap külső kivitelező előkészítésére vár';
            $uzi     =  'A munkalap külső kivitelező előkészítésére vár.
                        <br>'.
                        $uzi.fajlbol_olvas('../sablon/admin/mail/alairas.html');
        break;

        case 'munkalapKkJtUtan':
            $esemeny =  'munkalapon külső kivitelező javaslattétel után';
            $targy   =  get_nev().' munkalap döntésre vár';
            $uzi     =  'A munkalap döntésre vár.
                        <br>'.
                        $uzi.fajlbol_olvas('../sablon/admin/mail/alairas.html');
        break;

        case 'munkalapKkJhUtan':
            $esemeny =  'munkalapon 2. jóváhagyásakor';
            $targy   =  get_nev().' munkalap külső kivitelező megrendelésére vár';
            $uzi     =  'A munkalap külső kivitelező megrendelésére vár.
                        <br>'.
                        $uzi.fajlbol_olvas('../sablon/admin/mail/alairas.html');
        break;

        case 'munkalapElutasit':
            $esemeny =  'munkalap igazolás elutasítása után';
            $targy   =  get_nev().' munkalap vizsgálatra vár';
            $uzi     =  'A munkalap vizsgálatra vár.
                        <br>'.
                        $uzi.fajlbol_olvas('../sablon/admin/mail/alairas.html');
        break;

        case 'munkalapVarakoztat':
            $esemeny =  'munkalap várakoztatásakor';
            $targy   =  get_nev().' hibabejelentése várakozik';
            $uzi     =  'Az Ön által bejelentett hiba javítása várakozik.
                        <br>'.
                        $uzi.'
                        <br>
                        <br>Várakoztatja: '.$_SESSION[get_db_tabla().'teljes_nev'].' '.getSzerepkorNev($_SESSION[get_db_tabla().'szemelyzet_id']).'
                        <br>Várakoztatás indoka:
                        <br>'.$extra_szoveg.'
                        <br>'.fajlbol_olvas('../sablon/admin/mail/alairas.html');
        break;

        case 'projektkezelesUj':
            $esemeny =  'projekt létrehozásakor';
            $targy   =  get_nev().' új projekt létrehozva';
            $uzi     =  'Ön az alábbi projekt felelőse:
                        <br>'.
                        $uzi.'
                        <br>'.$extra_szoveg.'
                        <br>'.fajlbol_olvas('../sablon/admin/mail/alairas.html');
        break;

        case 'projektkezelesModosit':
            $esemeny =  'projekt módosításkor';
            $targy   =  get_nev().' projekt módosult';
            $uzi     =  'Ön az alábbi projekt felelőse:
                        <br>'.
                        $uzi.'
                        <br>'.$extra_szoveg.'
                        <br>'.fajlbol_olvas('../sablon/admin/mail/alairas.html');
        break;

        case 'projektkezelesModositElozo':
            $esemeny =  'projekt módosításkor';
            $targy   =  get_nev().' projekt módosult';
            $uzi     =  'Ön már nem felelőse az alábbi projektnek:
                        <br>'.
                        $uzi.'
                        <br>'.$extra_szoveg.'
                        <br>'.fajlbol_olvas('../sablon/admin/mail/alairas.html');
        break;

        case 'projektkezelesUjTag':
            $esemeny =  'projekt Team tag hozzáadásakor';
            $targy   =  get_nev().' Team taggá vált';
            $uzi     =  'Ön az alábbi projekt tagjává vált:
                        <br>'.
                        $uzi.'
                        <br>'.$extra_szoveg.'
                        <br>'.fajlbol_olvas('../sablon/admin/mail/alairas.html');
        break;

        case 'projektkezelesTagDel':
            $esemeny =  'projekt Team tag törlésekor';
            $targy   =  get_nev().' Team tag negszűnése';
            $uzi     =  'Ön továbbá nem tagja az alábbi projektnek:
                        <br>'.
                        $uzi.'
                        <br>'.$extra_szoveg.'
                        <br>'.fajlbol_olvas('../sablon/admin/mail/alairas.html');
        break;

        case 'projektszerzodesUjDok':

            $esemeny =  'projektszerződés új dokumentum feltöltésekor';
            $targy   =  get_nev().' minősítésre váró dokumentum';
            $uzi     =  'Az alábbi szerződéshez minősítésre váró dokumentumot töltöttek fel:
                        <br>'.
                        $uzi.'
                        <br>'.$extra_szoveg.'
                        <br>'.fajlbol_olvas('../sablon/admin/mail/alairas.html');

        break;

    }

    // Aláírásban a {GET_NEV} még át kell íni
    $uzi = str_replace('{GET_NEV}', get_nev(), $uzi);

    // Levélküldés
    $megj = '';
    $hiba = '';

    // Attól függően, hogy melyik rendszerben vagyunk, kap a tárgy/üzenet egy [Teszt] előtagot.
    $kieg = get_mail_teszt_felirat();
    $targy_kieg = $kieg[0];
    $uzi_kieg   = $kieg[1];

    foreach ( $cimzettek as $akt_cimzett ) {

        // Mely tábláknál ellenőrizzünk szállodát is (saját szállodák). Kivéve, ha nem a Diszpécser köztponti mailcímre megy az értesítés
        $szallodat_ellenorizz = array('bejelentes', 'munkalap', 'anyagigenyles');
        if( in_array($tabla, $szallodat_ellenorizz) && $cimzett!='diszpecser_kozponti' ){
            $szalloda = $adatbazis_kapcsolat->s2('telephely_id', $tabla, "`recid`='{$recid}'");
            $sajat_szallodak = get_akt_login_szallodai('', '', $akt_cimzett['nev']);

            // Ha adott rekord szállodája a usernek nem saját szállodája, akkor nem küldünk levelet
            if( !in_array($szalloda['telephely_id'], $sajat_szallodak) ) {
                continue;
            }
        }

        $szerepkor = getSzerepkorNev($akt_cimzett['szemelyzet_id']);

        $mit            = array('{NEV}');
        $mivel          = array($akt_cimzett['teljes_nev']);
        $megszolitas    = str_replace( $mit, $mivel, fajlbol_olvas('../sablon/admin/mail/megszolitas.html'));

        // Ha nincs e-mail cím, akkor ezt a megjegyzésbe is írjuk bele
        if( !$akt_cimzett['usermail'] ) {
            $megj .= 'A felhasználónak nincs e-mail címe, ezért a következő esemény NEM következett be:'."\n";
        }
        
        // Ha több mailcím is szerepel vesszővel elválasztva, akkor azt is le kell kezelni:
        $mailcimek = explode(',', $akt_cimzett['usermail']);
        foreach( $mailcimek as $mailcim ) {

            $mailcim = trim($mailcim);

            // Tényleges küldés
            $sikeres_kuldes = level_kuldes(get_nev(), get_mail(), $akt_cimzett['teljes_nev'], $mailcim, $targy_kieg.$targy, $uzi_kieg.$megszolitas.$uzi);
            // Ha van értéke, az egy hibaüzenet, akkor sikerült küldeni, ha nincs visszatérési érték
            if( $sikeres_kuldes ) {

                $sikeres_kuldes = str_replace('Could not instantiate mail function.', 'Could not instantiate mail function. - Lehet túl nagy fájlokat mellékelt?', $sikeres_kuldes);

                $megj .= 'E-mail küldése nem sikerült '.$akt_cimzett['teljes_nev'].' '.$szerepkor.' '.$felhasznalonak_szo.' '.$esemeny.'('.$sikeres_kuldes.')'."\n\n";

                // Popupos hibaüzenet csak MSZK vezetőnek és Diszpécsernek megy
                if( $_SESSION[get_db_tabla().'szemelyzet_id']==MSZK_VEZETO || $_SESSION[get_db_tabla().'szemelyzet_id']==DISZPECSER ) {

                    $tovabbi_info = '';
                    if( $akt_cimzett['usertel'] || $mailcim ) {
                        $tovabbi_info = nl2('<br>'.$cimzett_nev.' '.$szerepkor.'
                                            <br>Tel: '.$akt_cimzett['usertel'].'
                                            <br>E-mail: '.$mailcim.'
                                            <br>');
                    }
                    $hiba .=  nl2('Hiba: E-mail küldése nem sikerült!'.$tovabbi_info);
                }

            // Sikeres levélküldés
            } else {

                // Ha a címzett nem a diszpécser központi mailcím, akkor van csak megjegyzés
                if( $cimzett != 'diszpecser_kozponti' ) {
                    $megj .= 'E-mail elküldve '.$akt_cimzett['teljes_nev'].' (e-mail: '.$mailcim.') '.$szerepkor.' '.$felhasznalonak_szo.' '.$esemeny."\n\n";
                }

            }
        }

    }

    // Ha van megjegyzés, akkor azt hozzáadjuk
    if( $megj ) {
        // Megjegyzés hozzáadása
        addMegj($tabla, $recid, $megj);
        // Ha volt kapcsolódó munkalap, akkor oda is beírjuk a megjegyést
        if( $munkalap_kapcs['munkalap_id'] ) {
            addMegj('munkalap', $munkalap_kapcs['munkalap_id'], $megj);
        }
    }
    mysqli_commit($db_kapcs);
    // Hibaüzenettel is visszatérhetünk
    if( $hiba ) {
        return nl2br2($megj).$hiba;
    }

}


// Egységes dátum kiíráshoz (másodperc nem kell, kötőjel helyett pont)
function datum_kiir($datum, $csak_datum = false) {
    if( $csak_datum ) {
        $hossz = 10;
    } else {
        $hossz = 16;
    }
    return str_replace('-', '.', substr($datum, 0, $hossz));
}


// Új munkalap készítése kiszervezve
function munkalap_letrehoz($stat_id, $telephely_id, $tipus_id, $obj_id, $gep_id, $surgos=0, $text='', $szemelyzet_id=0, $szallito_id='', $uf_szam='', $esnap_d='0000-00-00', $hiba_id = 0, $loginnev = ''){

    global $adatbazis_kapcsolat;

    $db_error = false;

    // Iktatószám beállítása
    $sorszam = get_iktatoszam_sorszam('munkalap');
    if($sorszam===false){ $db_error = true; }
    $ikt_szam = 'M'.date('y').'/'.sprintf('%07d', $sorszam);

    // Címkék lekezelése
    $obj_txt = getErtek('nev', 'obj', 'recid', $obj_id);
    $gep_txt = getErtek('nev', 'eszkozkezeles', 'recid', $gep_id);
    $hiba_txt = '';
    if($hiba_id){
        $hiba_txt = getErtek('info', 'erteklista', 'recid', $hiba_id);
    }

    // Ha meg van adva, hogy ki legyen a létrehozó, akkor adott loginnév legyen a név/teljes név insert-nél, egyébként a Session (tehát belépett) felhasználó
    if($loginnev){
        $login = $adatbazis_kapcsolat->s2('nev teljes_nev', 'login', "`nev`='{$loginnev}'");
    } else {
        $login['teljes_nev']    = $_SESSION[get_db_tabla().'teljes_nev'];
        $login['nev']           = $_SESSION[get_db_tabla().'user'];
    }

    // Munkalap beszúrása
    // A tipusát backupként is eltároljuk, hogy tudjuk, mi volt eredetileg (a sima tipus_id sem változhat, de az a tuti, ha duplán benne van, mert készre jelentéskor nem mindegy, milyen tipusú volt eredetileg, melyik táblát kell majd updatelni)
    $recid = $adatbazis_kapcsolat -> i('munkalap',
                                        array('ikt_szam',   'stat_id',  'tipus_id', 'tipus_id_back',    'surgos',  'telephely_id', 'obj_id', 'obj_txt', 'gep_id', 'gep_txt', 'hiba_id', 'hiba_txt', 'text', 'szemelyzet_id', 'szallito_id', 'uf_szam', 'esnap_d', 'loginnev',     'update_usr',         'insert_dt',    'insert_usr'           ),
                                        array($ikt_szam,     $stat_id,  $tipus_id,  $tipus_id,          $surgos,   $telephely_id,  $obj_id,  $obj_txt,  $gep_id,  $gep_txt,  $hiba_id,  $hiba_txt,  $text,  $szemelyzet_id,  $szallito_id,  $uf_szam,  $esnap_d,  $login['nev'],  $login['teljes_nev'], 'NOW()',        $login['teljes_nev']   )
                                    );
    if( $recid === false ) { $db_error = true; }

    // Felvesszük az első státuszt
    $sql = $adatbazis_kapcsolat -> i('munkalap_statusz',
                                        array('munkalap_recid', 'stat_id',  'login',        'insert_dt',    'insert_usr',            'update_usr'            ),
                                        array($recid,           $stat_id,   $login['nev'],  'NOW()',        $login['teljes_nev'],    $login['teljes_nev']    )
                                    );
    if( $sql === false ) { $db_error = true; }

    if($db_error){
        return false;
    } else {
        return $recid;
    }

}

// Kezelés jogot visszaadó függvény (felvéve státusz alatt)
function get_kezeles_jog($szemelyzet_id){
    $kezeles_jog = false;
    if(
            ( _jog(JOG_MUNKALAP_SAJAT_KEZEL)        && $szemelyzet_id == $_SESSION[get_db_tabla().'szemelyzet_id'] ) ||
            ( _jog(JOG_MUNKALAP_KEZEL_TELEPITETT)   && $szemelyzet_id == TELEPITETT_SZEMELYZET )                     ||
            ( _jog(JOG_MUNKALAP_KEZEL_MOBIL)        && $szemelyzet_id == MOBILCSOPORT          )                     ||
            // Ez utóbbi feltétek vizsgálat sokszor felesleges, mert a feltétel (ahol ez a függvény meghívódik) eleve kizárja a külső kivitelezőket. De nem baj, így általánosan meghívható ez a függvény.
            ( _jog(JOG_MUNKALAP_KEZEL_KULSO)        && $szemelyzet_id == KULSO_KIVITELEZO      )
        ) {
        $kezeles_jog = true;
    }
    return $kezeles_jog;
}

// iPhone alatt vagyunk-e
function isIphone($user_agent=NULL) {
    if(!isset($user_agent)) {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    }
    return (strpos($user_agent, 'iPhone') !== FALSE);
}

// Fenntartási terv energia fül alatti táblázat kirajzolása
function fenntartasi_terv_energia($sor/* Fenntartás terv sora */) {

    global $adatbazis_kapcsolat;

    $szamla_html = '
    <div id="fixTable">
        <table class="szamla_cont_tabla nolink fejlec_padd szep_csikos kisbetu energia_tabla">
        <thead>
        <tr>
            <th class="ktgnem_kod fix energia1"><span>Ktgnem kód</span></th>
            <th class="fix energia2" style="min-width: 10rem; width: 10rem"><span>Ktgnem név</span></th>
            <th class="fix energia3" style="min-width: 20rem; width: 20rem"><span>Tárgy</span></th>
            <th class="fix energia4"><span>Mee</span></th>
            <th><span>Mennyiség összesen</span></th>
            <th><span>Érték összesen</span></th>
            <th><span>Egységár Január</span></th>
            <th class="egys_dev"><span>Mennyiség Január</span></th>
            <th><span>Érték Január</span></th>
            <th><span>Egységár Február</span></th>
            <th class="egys_dev"><span>Mennyiség Február</span></th>
            <th><span>Érték Február</span></th>
            <th><span>Egységár Március</span></th>
            <th class="egys_dev"><span>Mennyiség Március</span></th>
            <th><span>Érték Március</span></th>
            <th><span>Egységár Április</span></th>
            <th class="egys_dev"><span>Mennyiség Április</span></th>
            <th><span>Érték Április</span></th>
            <th><span>Egységár Május</span></th>
            <th class="egys_dev"><span>Mennyiség Május</span></th>
            <th><span>Érték Május</span></th>
            <th><span>Egységár Június</span></th>
            <th class="egys_dev"><span>Mennyiség Június</span></th>
            <th><span>Érték Június</span></th>
            <th><span>Egységár Július</span></th>
            <th class="egys_dev"><span>Mennyiség Július</span></th>
            <th><span>Érték Július</span></th>
            <th><span>Egységár Augusztus</span></th>
            <th class="egys_dev"><span>Mennyiség Augusztus</span></th>
            <th><span>Érték Augusztus</span></th>
            <th><span>Egységár Szeptember</span></th>
            <th class="egys_dev"><span>Mennyiség Szeptember</span></th>
            <th><span>Érték Szeptember</span></th>
            <th><span>Egységár Október</span></th>
            <th class="egys_dev"><span>Mennyiség Október</span></th>
            <th><span>Érték Október</span></th>
            <th><span>Egységár November</span></th>
            <th class="egys_dev"><span>Mennyiség November</span></th>
            <th><span>Érték November</span></th>
            <th><span>Egységár December</span></th>
            <th class="egys_dev"><span>Mennyiség December</span></th>
            <th><span>Érték December</span></th>
        </tr>
        </thead>
        <tbody>
        ';

    // Nézzük meg, hogy az energia egységár soraiban van-e változás (feltéve ha még munkaközi)
    if( $sor['stat_id'] == ENERGIATERV_STAT_MUNKAKOZI ) {
        $energia_terv   = $adatbazis_kapcsolat->s2('recid', 'energia_terv', "`ev`='{$sor['ev']}' AND `stat_id`='".ENERGIATERV_STAT_ELES."'");
        $akt_eear_sorok = $adatbazis_kapcsolat->s('*', 'energia_terv_ear', "`energia_terv_id`='{$energia_terv['recid']}' AND `telephely_idk` LIKE '%{$sor['telephely_id']}%'");
        $eear_soridk    = adatbazis2tomb($akt_eear_sorok, 'sorid');
    }

    $volt_valtozas  = false;

    $energia_soridk = array();
    $energiak = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_energia', "`fenntartasi_terv_id`='{$sor['recid']}'");
    foreach ( $energiak as $energia ) {

        // Ha a gázfogyasztás sort írjuk ki, akkor azzal másképp számolunk
        $gazfogyasztas_sor = false;
        if( $energia['ktgnem_id']==FUTOERTEK_KOD && $energia['mee']==FUTOERTEK_MEE3 && $energia['dijelem']==GAZFOGYASZTAS_DIJELEM ){
            $gazfogyasztas_sor = true;
        }
        
        // Innen tujduk majd, hogy mik az új sorok
        array_push($energia_soridk, $energia['sorid']);

        // Ha változtak volna az egységárak, azt felül kell írni
        if($sor['stat_id']==ENERGIATERV_STAT_MUNKAKOZI){
            $energia_ear = $adatbazis_kapcsolat->s2('*', 'energia_terv_ear', "`energia_terv_id`='{$energia_terv['recid']}' AND `sorid`='{$energia['sorid']}'");
        }

        // Számok formázása
        $mennyiseg_osszesen = 0;
        $ertek_osszesen     = 0;
        for ( $i = 1; $i < 13; $i++ ) {
            if( $i < 10 ) {
                $i = '0'.$i;
            }

            if( $sor['stat_id'] == ENERGIATERV_STAT_MUNKAKOZI && !$gazfogyasztas_sor ) {
                if( $energia_ear['ear' . $i] != $energia['ear' . $i] ) {
                    $energia['ear'.$i] = $energia_ear['ear'.$i];
                    $energia['ft'.$i]  = $energia['em'.$i]*$energia['ear'.$i];
                    $volt_valtozas     = true;
                }
            }

            $mennyiseg_osszesen += $energia['em'.$i];
            $ertek_osszesen     += $energia['ft'.$i];

            $energia['em'.$i]  = number_format($energia['em'.$i],  2, ',', ' ');
            $energia['ear'.$i] = number_format($energia['ear'.$i], 3, ',', ' ');
            $energia['ft'.$i]  = number_format($energia['ft'.$i],  0, '',  ' ');
        }
        $mennyiseg_osszesen = number_format($mennyiseg_osszesen,  0, ',', ' ');
        $ertek_osszesen     = number_format($ertek_osszesen,      0, ',', ' ');

        $class = $class2 = '';
        if( $sor['stat_id']==ENERGIATERV_STAT_MUNKAKOZI && !in_array($energia['sorid'], $eear_soridk) && !$gazfogyasztas_sor ) {
            $class         .= ' piros_hat';
            $volt_valtozas  = true;
        }
        if( futoertek_sor($energia) || $gazfogyasztas_sor ) {
            $class  .= ' futoertek';
            $class2 .= ' szurke';
        }

        $szamla_html .= '
            <tr id="ft_em_'.$energia['recid'].'" data-recid="'.$energia['recid'].'" class="ftt_menny'.$class.'">
                <td class="fix energia1">'.$energia['ktgnem_id'].'</td>
                <td class="fix energia2 wrap">'.getErtek('nev', 'ktgnem', 'id', $energia['ktgnem_id']).'</td>
                <td class="fix energia3 wrap">'.str_replace('m3', 'm<sup>3</sup>', $energia['dijelem']).'</td>
                <td class="fix energia4">'.str_replace('m3', 'm<sup>3</sup>', $energia['mee']).'</td>
                <td class="r mennyossz'.$class2.'">'.$mennyiseg_osszesen.'</td>
                <td class="r ertekossz'.$class2.'">'.$ertek_osszesen.'</td>
                <td class="r'.$class2.'">'.$energia['ear01'].'</td>
                <td class="r'.$class2.'">'.$energia['em01'].'</td>
                <td class="r'.$class2.' ft01">'.$energia['ft01'].'</td>
                <td class="r'.$class2.'">'.$energia['ear02'].'</td>
                <td class="r'.$class2.'">'.$energia['em02'].'</td>
                <td class="r'.$class2.' ft02">'.$energia['ft02'].'</td>
                <td class="r'.$class2.'">'.$energia['ear03'].'</td>
                <td class="r'.$class2.'">'.$energia['em03'].'</td>
                <td class="r'.$class2.' ft03">'.$energia['ft03'].'</td>
                <td class="r'.$class2.'">'.$energia['ear04'].'</td>
                <td class="r'.$class2.'">'.$energia['em04'].'</td>
                <td class="r'.$class2.' ft04">'.$energia['ft04'].'</td>
                <td class="r'.$class2.'">'.$energia['ear05'].'</td>
                <td class="r'.$class2.'">'.$energia['em05'].'</td>
                <td class="r'.$class2.' ft05">'.$energia['ft05'].'</td>
                <td class="r'.$class2.'">'.$energia['ear06'].'</td>
                <td class="r'.$class2.'">'.$energia['em06'].'</td>
                <td class="r'.$class2.' ft06">'.$energia['ft06'].'</td>
                <td class="r'.$class2.'">'.$energia['ear07'].'</td>
                <td class="r'.$class2.'">'.$energia['em07'].'</td>
                <td class="r'.$class2.' ft07">'.$energia['ft07'].'</td>
                <td class="r'.$class2.'">'.$energia['ear08'].'</td>
                <td class="r'.$class2.'">'.$energia['em08'].'</td>
                <td class="r'.$class2.' ft08">'.$energia['ft08'].'</td>
                <td class="r'.$class2.'">'.$energia['ear09'].'</td>
                <td class="r'.$class2.'">'.$energia['em09'].'</td>
                <td class="r'.$class2.' ft09">'.$energia['ft09'].'</td>
                <td class="r'.$class2.'">'.$energia['ear10'].'</td>
                <td class="r'.$class2.'">'.$energia['em10'].'</td>
                <td class="r'.$class2.' ft10">'.$energia['ft10'].'</td>
                <td class="r'.$class2.'">'.$energia['ear11'].'</td>
                <td class="r'.$class2.'">'.$energia['em11'].'</td>
                <td class="r'.$class2.' ft11">'.$energia['ft11'].'</td>
                <td class="r'.$class2.'">'.$energia['ear12'].'</td>
                <td class="r'.$class2.'">'.$energia['em12'].'</td>
                <td class="r'.$class2.' ft12">'.$energia['ft12'].'</td>
            </tr>
            ';
        
    }

    // Új sorok kiszámítása, kirajzolása
    if( $sor['stat_id'] == ENERGIATERV_STAT_MUNKAKOZI ) {
        foreach ( $eear_soridk as $sorid ) {
            if( !in_array($sorid, $energia_soridk) ) {
                $volt_valtozas  = true;

                $energia = $adatbazis_kapcsolat->s2('*', 'energia_terv_ear', "`energia_terv_id`='{$energia_terv['recid']}' AND `sorid`='{$sorid}'");
                $mennyiseg_osszesen = 0;
                $ertek_osszesen     = 0;
                for ( $i = 1; $i < 13; $i++ ) {
                    if( $i < 10 ) {
                        $i = '0'.$i;
                    }

                    $mennyiseg_osszesen += $energia['em'.$i];
                    $ertek_osszesen     += $energia['ft'.$i];
                }
                $mennyiseg_osszesen = number_format($mennyiseg_osszesen,  0, ',', ' ');
                $ertek_osszesen     = number_format($ertek_osszesen,  0, ',', ' ');
                $ktgnem = $adatbazis_kapcsolat->s2('id nev', 'ktgnem', "`id`='{$energia['ktgnem_id']}'");

                $class  = '';
                $class2 = '';
                if( futoertek_sor($energia) ) {
                    $class  .= ' futoertek';
                    $class2 .= ' szurke';
                }
                
                $szamla_html .= '
                    <tr id="ft_em_'.$sorid.'" data-recid="'.$sorid.'" class="ftt_menny zold_hat'.$class.'">
                        <td class="fix energia1">'.$ktgnem['id'].'</td>
                        <td class="fix energia2">'.$ktgnem['nev'].'</td>
                        <td class="fix energia3">'.$energia['dijelem'].'</td>
                        <td class="fix energia4">'.str_replace('m3', 'm<sup>3</sup>', $energia['mee']).'</td>
                        <td class="r'.$class2.'">'.$mennyiseg_osszesen.'</td>
                        <td class="r ertekossz'.$class2.'">'.$ertek_osszesen.'</td>
                        <td class="r'.$class2.'">'.$energia['ear01'].'</td>
                        <td class="r'.$class2.'">'.$energia['em01'].'</td>
                        <td class="r ft01'.$class2.'">'.$energia['ft01'].'</td>
                        <td class="r'.$class2.'">'.$energia['ear02'].'</td>
                        <td class="r'.$class2.'">'.$energia['em02'].'</td>
                        <td class="r ft02'.$class2.'">'.$energia['ft02'].'</td>
                        <td class="r'.$class2.'">'.$energia['ear03'].'</td>
                        <td class="r'.$class2.'">'.$energia['em03'].'</td>
                        <td class="r ft03'.$class2.'">'.$energia['ft03'].'</td>
                        <td class="r'.$class2.'">'.$energia['ear04'].'</td>
                        <td class="r'.$class2.'">'.$energia['em04'].'</td>
                        <td class="r ft04'.$class2.'">'.$energia['ft04'].'</td>
                        <td class="r'.$class2.'">'.$energia['ear05'].'</td>
                        <td class="r'.$class2.'">'.$energia['em05'].'</td>
                        <td class="r ft05'.$class2.'">'.$energia['ft05'].'</td>
                        <td class="r'.$class2.'">'.$energia['ear06'].'</td>
                        <td class="r'.$class2.'">'.$energia['em06'].'</td>
                        <td class="r ft06'.$class2.'">'.$energia['ft06'].'</td>
                        <td class="r'.$class2.'">'.$energia['ear07'].'</td>
                        <td class="r'.$class2.'">'.$energia['em07'].'</td>
                        <td class="r ft07'.$class2.'">'.$energia['ft07'].'</td>
                        <td class="r'.$class2.'">'.$energia['ear08'].'</td>
                        <td class="r'.$class2.'">'.$energia['em08'].'</td>
                        <td class="r ft08'.$class2.'">'.$energia['ft08'].'</td>
                        <td class="r'.$class2.'">'.$energia['ear09'].'</td>
                        <td class="r'.$class2.'">'.$energia['em09'].'</td>
                        <td class="r ft09'.$class2.'">'.$energia['ft09'].'</td>
                        <td class="r'.$class2.'">'.$energia['ear10'].'</td>
                        <td class="r'.$class2.'">'.$energia['em10'].'</td>
                        <td class="r ft10'.$class2.'">'.$energia['ft10'].'</td>
                        <td class="r'.$class2.'">'.$energia['ear11'].'</td>
                        <td class="r'.$class2.'">'.$energia['em11'].'</td>
                        <td class="r ft11'.$class2.'">'.$energia['ft11'].'</td>
                        <td class="r'.$class2.'">'.$energia['ear12'].'</td>
                        <td class="r'.$class2.'">'.$energia['em12'].'</td>
                        <td class="r ft12'.$class2.'">'.$energia['ft12'].'</td>
                    </tr>
                    ';
            }
        }
    }

    $szamla_html .= nl2('
                        </tbody>
                        <tfoot class="nowrap">
                            <td class="fix energia1"></td>
                            <td class="fix energia2"></td>
                            <td class="fix energia3"></td>
                            <td class="fix energia4"></td>
                            <td class="r"></td>
                            <td class="r" id="szum_ertek_o"></td>
                            <td></td>
                            <td></td>
                            <td class="r" id="szum_ft01"></td>
                            <td></td>
                            <td></td>
                            <td class="r" id="szum_ft02"></td>
                            <td></td>
                            <td></td>
                            <td class="r" id="szum_ft03"></td>
                            <td></td>
                            <td></td>
                            <td class="r" id="szum_ft04"></td>
                            <td></td>
                            <td></td>
                            <td class="r" id="szum_ft05"></td>
                            <td></td>
                            <td></td>
                            <td class="r" id="szum_ft06"></td>
                            <td></td>
                            <td></td>
                            <td class="r" id="szum_ft07"></td>
                            <td></td>
                            <td></td>
                            <td class="r" id="szum_ft08"></td>
                            <td></td>
                            <td></td>
                            <td class="r" id="szum_ft09"></td>
                            <td></td>
                            <td></td>
                            <td class="r" id="szum_ft10"></td>
                            <td></td>
                            <td></td>
                            <td class="r" id="szum_ft11"></td>
                            <td></td>
                            <td></td>
                            <td class="r" id="szum_ft12"></td>
                        </tfoot>
                    </table>
                </div>
            <script>
                $(document).ready(function () {
                    energia_szummaz(\'energia_tabla\');
                });
            </script>
        ');

    return array($szamla_html, $volt_valtozas);
}

// Fenntartási terv műszaki anyag fül alatti táblázat kirajzolása
function fenntartasi_terv_muszaki_anyag($sor) {

    global $adatbazis_kapcsolat;

    $szamla_html = '
            <div id="fixTable">
                <table class="szamla_cont_tabla nolink fejlec_padd szep_csikos kisbetu muszaki_anyag_tabla">
                <thead>
                <tr>
                    <th class="ktgnem_kod fix muszaki1"><span>Ktgnem kód</span></th>
                    <th class="fix muszaki2" style="min-width: 10rem; width: 10rem"><span>Ktgnem név</span></th>
                    <th><span>Összesen</span></th>
                    <th><span>Január</span></th>
                    <th><span>Február</span></th>
                    <th><span>Március</span></th>
                    <th><span>Április</span></th>
                    <th><span>Május</span></th>
                    <th><span>Június</span></th>
                    <th><span>Július</span></th>
                    <th><span>Augusztus</span></th>
                    <th><span>Szeptember</span></th>
                    <th><span>Október</span></th>
                    <th><span>November</span></th>
                    <th><span>December</span></th>
                </tr>
                </thead>
                <tbody>
                ';

            $muszaki_anyagok = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_muszaki_anyag', "`fenntartasi_terv_id`='{$sor['recid']}'", 'ktgnem_id');
            foreach ( $muszaki_anyagok as $anyagok ) {

                $osszesen = 0;
                for ( $i = 1; $i < 13; $i++ ) {
                    if( $i < 10 ) {
                        $i = '0'.$i;
                    }
                    $osszesen += $anyagok['ft'.$i];

                    $anyagok['ft'.$i] = number_format($anyagok['ft'.$i], 0, '', ' ');
                }
                $osszesen = number_format($osszesen, 0, '', ' ');

                $szamla_html .= '
                    <tr id="ft_ft_'.$anyagok['recid'].'" data-recid="'.$anyagok['recid'].'" class="ftt_menny">
                        <td class="fix muszaki1">'.$anyagok['ktgnem_id'].'</td>
                        <td class="fix muszaki2 wrap">'.getErtek('nev', 'ktgnem', 'id', $anyagok['ktgnem_id']).'</td>
                        <td class="r ftossz">'.$osszesen.'</td>
                        <td class="r ft01">'.$anyagok['ft01'].'</td>
                        <td class="r ft02">'.$anyagok['ft02'].'</td>
                        <td class="r ft03">'.$anyagok['ft03'].'</td>
                        <td class="r ft04">'.$anyagok['ft04'].'</td>
                        <td class="r ft05">'.$anyagok['ft05'].'</td>
                        <td class="r ft06">'.$anyagok['ft06'].'</td>
                        <td class="r ft07">'.$anyagok['ft07'].'</td>
                        <td class="r ft08">'.$anyagok['ft08'].'</td>
                        <td class="r ft09">'.$anyagok['ft09'].'</td>
                        <td class="r ft10">'.$anyagok['ft10'].'</td>
                        <td class="r ft11">'.$anyagok['ft11'].'</td>
                        <td class="r ft12">'.$anyagok['ft12'].'</td>
                    </tr>
                    ';
            }

            $szamla_html .= nl2('
                                </tbody>
                                <tfoot class="nowrap">
                                    <td class="fix muszaki1"></td>
                                    <td class="fix muszaki2"></td>
                                    <td class="r" id="szum_ftossz"></td>
                                    <td class="r" id="szum_ft01"></td>
                                    <td class="r" id="szum_ft02"></td>
                                    <td class="r" id="szum_ft03"></td>
                                    <td class="r" id="szum_ft04"></td>
                                    <td class="r" id="szum_ft05"></td>
                                    <td class="r" id="szum_ft06"></td>
                                    <td class="r" id="szum_ft07"></td>
                                    <td class="r" id="szum_ft08"></td>
                                    <td class="r" id="szum_ft09"></td>
                                    <td class="r" id="szum_ft10"></td>
                                    <td class="r" id="szum_ft11"></td>
                                    <td class="r" id="szum_ft12"></td>
                                </tfoot>
                            </table>
                        </div>
                    <script>
                        $(document).ready(function () {
                            energia_szummaz(\'muszaki_anyag_tabla\');
                        });
                    </script>
                ');

    return $szamla_html;
}

// Fenntartási terv rendszeres karbantartás fül alatti táblázat kirajzolása
function fenntartasi_terv_rendsz_karban($sor) {

    global $adatbazis_kapcsolat;

    $szamla_html = '
    <div id="fixTable">
        <table class="szamla_cont_tabla nolink fejlec_padd szep_csikos kisbetu rendsz_karban_tabla">
        <thead>
        <tr>
            <th class="ktgnem_kod fix rendszeres1"><span>Ktgnem kód</span></th>
            <th class="fix rendszeres2" style="min-width: 10rem; width: 10rem"><span>Ktgnem név</span></th>
            <th class="fix rendszeres3" style="min-width: 20rem; width: 20rem"><span>Tárgy</span></th>
            <th><span>Összesen</span></th>
            <th><span>Január</span></th>
            <th><span>Február</span></th>
            <th><span>Március</span></th>
            <th><span>Április</span></th>
            <th><span>Május</span></th>
            <th><span>Június</span></th>
            <th><span>Július</span></th>
            <th><span>Augusztus</span></th>
            <th><span>Szeptember</span></th>
            <th><span>Október</span></th>
            <th><span>November</span></th>
            <th><span>December</span></th>
        </tr>
        </thead>
        <tbody>
        ';

    $rendszeresek = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_rendsz_karban', "`fenntartasi_terv_id`='{$sor['recid']}'", 'ktgnem_id');
    foreach ( $rendszeresek as $rendszeres ) {

        $osszesen = 0;
        for ( $i = 1; $i < 13; $i++ ) {
            if( $i < 10 ) {
                $i = '0'.$i;
            }
            $osszesen += $rendszeres['ft'.$i];

            $rendszeres['ft'.$i] = number_format($rendszeres['ft'.$i], 0, '', ' ');
        }
        $osszesen = number_format($osszesen, 0, '', ' ');

        $szamla_html .= '
            <tr id="ft_ft_'.$rendszeres['recid'].'" data-recid="'.$rendszeres['recid'].'" class="ftt_menny">
                <td class="fix rendszeres1">'.$rendszeres['ktgnem_id'].'</td>
                <td class="wrap fix rendszeres2">'.getErtek('nev', 'ktgnem', 'id', $rendszeres['ktgnem_id']).'</td>
                <td class="wrap fix rendszeres3">'.$rendszeres['targy'].'</td>
                <td class="r ftossz">'.$osszesen.'</td>
                <td class="r ft01">'.$rendszeres['ft01'].'</td>
                <td class="r ft02">'.$rendszeres['ft02'].'</td>
                <td class="r ft03">'.$rendszeres['ft03'].'</td>
                <td class="r ft04">'.$rendszeres['ft04'].'</td>
                <td class="r ft05">'.$rendszeres['ft05'].'</td>
                <td class="r ft06">'.$rendszeres['ft06'].'</td>
                <td class="r ft07">'.$rendszeres['ft07'].'</td>
                <td class="r ft08">'.$rendszeres['ft08'].'</td>
                <td class="r ft09">'.$rendszeres['ft09'].'</td>
                <td class="r ft10">'.$rendszeres['ft10'].'</td>
                <td class="r ft11">'.$rendszeres['ft11'].'</td>
                <td class="r ft12">'.$rendszeres['ft12'].'</td>
            </tr>
            ';
    }

    $szamla_html .= nl2('
                        </tbody>
                        <tfoot class="nowrap">
                            <td class="fix rendszeres1"></td>
                            <td class="fix rendszeres2"></td>
                            <td class="fix rendszeres3"></td>
                            <td class="r" id="szum_ftossz"></td>
                            <td class="r" id="szum_ft01"></td>
                            <td class="r" id="szum_ft02"></td>
                            <td class="r" id="szum_ft03"></td>
                            <td class="r" id="szum_ft04"></td>
                            <td class="r" id="szum_ft05"></td>
                            <td class="r" id="szum_ft06"></td>
                            <td class="r" id="szum_ft07"></td>
                            <td class="r" id="szum_ft08"></td>
                            <td class="r" id="szum_ft09"></td>
                            <td class="r" id="szum_ft10"></td>
                            <td class="r" id="szum_ft11"></td>
                            <td class="r" id="szum_ft12"></td>
                        </tfoot>
                    </table>
                </div>
            <script>
                $(document).ready(function () {
                    energia_szummaz(\'rendsz_karban_tabla\');
                });
            </script>
        ');

    return $szamla_html;
}

// Fenntartási terv eseti karbantartás fül alatti táblázat kirajzolása
function fenntartasi_terv_eseti_karban($sor){

    global $adatbazis_kapcsolat;

    $szamla_html = '
    <div id="fixTable">
        <table class="szamla_cont_tabla nolink fejlec_padd szep_csikos kisbetu eseti_karban_tabla width_auto">
        <thead>
        <tr>
            <th class="ktgnem_kod fix eseti1"><span>Ktgnem kód</span></th>
            <th class="fix eseti2" style="min-width: 10rem; width: 10rem"><span>Ktgnem név</span></th>
            <th class="fix eseti3" style="min-width: 20rem; width: 20rem"><span>Tárgy</span></th>
            <th class="fix eseti4" style="min-width: 20rem; width: 20rem"><span>Részletes leírás</span></th>
            <th><span>Összesen</span></th>
            <th><span>Január</span></th>
            <th><span>Február</span></th>
            <th><span>Március</span></th>
            <th><span>Április</span></th>
            <th><span>Május</span></th>
            <th><span>Június</span></th>
            <th><span>Július</span></th>
            <th><span>Augusztus</span></th>
            <th><span>Szeptember</span></th>
            <th><span>Október</span></th>
            <th><span>November</span></th>
            <th><span>December</span></th>
        </tr>
        </thead>
        <tbody>
        ';

    $esetik = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_eseti_karban', "`fenntartasi_terv_id`='{$sor['recid']}'", 'ktgnem_id');
    foreach ( $esetik as $eseti ) {

        $osszesen = '';
        for ( $i = 1; $i < 13; $i++ ) {
            if( $i < 10 ) {
                $i = '0'.$i;
            }
            $osszesen += $eseti['ft'.$i];
            $eseti['ft'.$i] = number_format($eseti['ft'.$i], 0, '', ' ');
        }
        $osszesen = number_format($osszesen, 0, '', ' ');

        $szamla_html .= '
            <tr id="ft_ft_'.$eseti['recid'].'" data-recid="'.$eseti['recid'].'" class="ftt_menny">
                <td class="fix eseti1">'.$eseti['ktgnem_id'].'</td>
                <td class="wrap fix eseti2" style="min-width: 10rem; width: 10rem">'.getErtek('nev', 'ktgnem', 'id', $eseti['ktgnem_id']).'</td>
                <td class="wrap fix eseti3" style="min-width: 20rem; width: 20rem">'.$eseti['targy'].'</td>
                <td class="wrap fix eseti4" style="min-width: 20rem; width: 20rem">'.nl2br2($eseti['leir']).'</td>
                <td class="r ftossz">'.$osszesen.'</td>
                <td class="r ft01">'.$eseti['ft01'].'</td>
                <td class="r ft02">'.$eseti['ft02'].'</td>
                <td class="r ft03">'.$eseti['ft03'].'</td>
                <td class="r ft04">'.$eseti['ft04'].'</td>
                <td class="r ft05">'.$eseti['ft05'].'</td>
                <td class="r ft06">'.$eseti['ft06'].'</td>
                <td class="r ft07">'.$eseti['ft07'].'</td>
                <td class="r ft08">'.$eseti['ft08'].'</td>
                <td class="r ft09">'.$eseti['ft09'].'</td>
                <td class="r ft10">'.$eseti['ft10'].'</td>
                <td class="r ft11">'.$eseti['ft11'].'</td>
                <td class="r ft12">'.$eseti['ft12'].'</td>
            </tr>
            ';
    }

    $szamla_html .= nl2('
                        </tbody>
                        <tfoot class="nowrap">
                            <td class="fix eseti1"></td>
                            <td class="fix eseti2"></td>
                            <td class="fix eseti3"></td>
                            <td class="fix eseti4"></td>
                            <td class="r" id="szum_ftossz"></td>
                            <td class="r" id="szum_ft01"></td>
                            <td class="r" id="szum_ft02"></td>
                            <td class="r" id="szum_ft03"></td>
                            <td class="r" id="szum_ft04"></td>
                            <td class="r" id="szum_ft05"></td>
                            <td class="r" id="szum_ft06"></td>
                            <td class="r" id="szum_ft07"></td>
                            <td class="r" id="szum_ft08"></td>
                            <td class="r" id="szum_ft09"></td>
                            <td class="r" id="szum_ft10"></td>
                            <td class="r" id="szum_ft11"></td>
                            <td class="r" id="szum_ft12"></td>
                        </tfoot>
                    </table>
                </div>
                    <script>
                    $(document).ready(function () {
                        energia_szummaz(\'eseti_karban_tabla\');
                    });
                    </script>
                ');

    return $szamla_html;
}

// Fenntartási terv hibaelhárítás fül alatti táblázat kirajzolása
function fenntartasi_terv_hibaelharitas($sor) {

    global $adatbazis_kapcsolat;

    $szamla_html .= '
    <div id="fixTable">
        <table class="szamla_cont_tabla nolink fejlec_padd szep_csikos kisbetu hibaelharitas_tabla">
        <thead>
        <tr>
            <th class="ktgnem_kod fix hiba1"><span>Ktgnem kód</span></th>
            <th class="fix hiba2" style="min-width: 10rem; width: 10rem"><span>Ktgnem név</span></th>
            <th class="fix hiba3" style="min-width: 20rem; width: 20rem"><span>Részletes leírás</span></th>
            <th><span>Összesen</span></th>
            <th><span>Január</span></th>
            <th><span>Február</span></th>
            <th><span>Március</span></th>
            <th><span>Április</span></th>
            <th><span>Május</span></th>
            <th><span>Június</span></th>
            <th><span>Július</span></th>
            <th><span>Augusztus</span></th>
            <th><span>Szeptember</span></th>
            <th><span>Október</span></th>
            <th><span>November</span></th>
            <th><span>December</span></th>
        </tr>
        </thead>
        <tbody>
        ';

    $hibak = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_hibaelharitas', "`fenntartasi_terv_id`='{$sor['recid']}'", 'ktgnem_id');
    foreach ( $hibak as $hiba ) {

        $osszesen = '';
        for ( $i = 1; $i < 13; $i++ ) {
            if( $i < 10 ) {
                $i = '0'.$i;
            }
            $osszesen += $hiba['ft'.$i];
            $hiba['ft'.$i] = number_format($hiba['ft'.$i], 0, '', ' ');
        }
        $osszesen = number_format($osszesen, 0, '', ' ');

        $szamla_html .= '
            <tr id="ft_ft_'.$hiba['recid'].'" data-recid="'.$hiba['recid'].'" class="ftt_menny">
                <td class="fix hiba1">'.$hiba['ktgnem_id'].'</td>
                <td class="wrap fix hiba2">'.getErtek('nev', 'ktgnem', 'id', $hiba['ktgnem_id']).'</td>
                <td class="wrap fix hiba3">'.nl2br2($hiba['leir']).'</td>
                <td class="r ftossz">'.$osszesen.'</td>
                <td class="r ft01">'.$hiba['ft01'].'</td>
                <td class="r ft02">'.$hiba['ft02'].'</td>
                <td class="r ft03">'.$hiba['ft03'].'</td>
                <td class="r ft04">'.$hiba['ft04'].'</td>
                <td class="r ft05">'.$hiba['ft05'].'</td>
                <td class="r ft06">'.$hiba['ft06'].'</td>
                <td class="r ft07">'.$hiba['ft07'].'</td>
                <td class="r ft08">'.$hiba['ft08'].'</td>
                <td class="r ft09">'.$hiba['ft09'].'</td>
                <td class="r ft10">'.$hiba['ft10'].'</td>
                <td class="r ft11">'.$hiba['ft11'].'</td>
                <td class="r ft12">'.$hiba['ft12'].'</td>
            </tr>
            ';
    }

    $szamla_html .= nl2('
                        </tbody>
                        <tfoot class="nowrap">
                            <td class="fix hiba1"></td>
                            <td class="fix hiba2"></td>
                            <td class="fix hiba3"></td>
                            <td class="r" id="szum_ftossz"></td>
                            <td class="r" id="szum_ft01"></td>
                            <td class="r" id="szum_ft02"></td>
                            <td class="r" id="szum_ft03"></td>
                            <td class="r" id="szum_ft04"></td>
                            <td class="r" id="szum_ft05"></td>
                            <td class="r" id="szum_ft06"></td>
                            <td class="r" id="szum_ft07"></td>
                            <td class="r" id="szum_ft08"></td>
                            <td class="r" id="szum_ft09"></td>
                            <td class="r" id="szum_ft10"></td>
                            <td class="r" id="szum_ft11"></td>
                            <td class="r" id="szum_ft12"></td>
                        </tfoot>
                    </table>
                </div>
            <script>
            $(document).ready(function () {
                energia_szummaz(\'hibaelharitas_tabla\');
            });
            </script>
        ');

    return $szamla_html;
}


// Fenntartási terv miniprojekt fül alatti táblázat kirajzolása
function fenntartasi_terv_miniprojekt($sor) {

    global $adatbazis_kapcsolat;

    $szamla_html = '
    <div id="fixTable">
        <table class="szamla_cont_tabla nolink fejlec_padd szep_csikos kisbetu miniprojekt_tabla width_auto">
        <thead>
        <tr>
            <th class="ktgnem_kod fix mini1"><span>Ktgnem kód</span></th>
            <th class="fix mini2" style="min-width: 10rem; width: 10rem"><span>Ktgnem név</span></th>
            <th class="fix mini3" style="min-width: 20rem; width: 20rem"><span>Tárgy</span></th>
            <th class="fix mini4" style="min-width: 20rem; width: 20rem"><span>Részletes leírás</span></th>
            <th><span>Összesen</span></th>
            <th><span>Január</span></th>
            <th><span>Február</span></th>
            <th><span>Március</span></th>
            <th><span>Április</span></th>
            <th><span>Május</span></th>
            <th><span>Június</span></th>
            <th><span>Július</span></th>
            <th><span>Augusztus</span></th>
            <th><span>Szeptember</span></th>
            <th><span>Október</span></th>
            <th><span>November</span></th>
            <th><span>December</span></th>
        </tr>
        </thead>
        <tbody>
        ';

    $minik = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_miniprojekt', "`fenntartasi_terv_id`='{$sor['recid']}'", 'ktgnem_id');
    foreach ( $minik as $mini ) {

        $osszesen = '';
        for ( $i = 1; $i < 13; $i++ ) {
            if( $i < 10 ) {
                $i = '0'.$i;
            }
            $osszesen += $mini['ft'.$i];
            $mini['ft'.$i] = number_format($mini['ft'.$i], 0, '', ' ');
        }
        $osszesen = number_format($osszesen, 0, '', ' ');

        $szamla_html .= '
            <tr id="ft_ft_'.$mini['recid'].'" data-recid="'.$mini['recid'].'" class="ftt_menny">
                <td class="fix mini1">'.$mini['ktgnem_id'].'</td>
                <td class="fix mini2 wrap" style="min-width: 10rem; width: 10rem">'.getErtek('nev', 'ktgnem', 'id', $mini['ktgnem_id']).'</td>
                <td class="fix mini3 wrap" style="min-width: 20rem; width: 20rem">'.$mini['targy'].'</td>
                <td class="fix mini4 wrap" style="min-width: 20rem; width: 20rem">'.nl2br2($mini['leir']).'</td>
                <td class="r ftossz">'.$osszesen.'</td>
                <td class="r ft01">'.$mini['ft01'].'</td>
                <td class="r ft02">'.$mini['ft02'].'</td>
                <td class="r ft03">'.$mini['ft03'].'</td>
                <td class="r ft04">'.$mini['ft04'].'</td>
                <td class="r ft05">'.$mini['ft05'].'</td>
                <td class="r ft06">'.$mini['ft06'].'</td>
                <td class="r ft07">'.$mini['ft07'].'</td>
                <td class="r ft08">'.$mini['ft08'].'</td>
                <td class="r ft09">'.$mini['ft09'].'</td>
                <td class="r ft10">'.$mini['ft10'].'</td>
                <td class="r ft11">'.$mini['ft11'].'</td>
                <td class="r ft12">'.$mini['ft12'].'</td>
            </tr>
            ';
    }

    $szamla_html .= nl2('
                        </tbody>
                        <tfoot class="nowrap">
                            <td class="fix mini1"></td>
                            <td class="fix mini2"></td>
                            <td class="fix mini3"></td>
                            <td class="fix mini4"></td>
                            <td class="r" id="szum_ftossz"></td>
                            <td class="r" id="szum_ft01"></td>
                            <td class="r" id="szum_ft02"></td>
                            <td class="r" id="szum_ft03"></td>
                            <td class="r" id="szum_ft04"></td>
                            <td class="r" id="szum_ft05"></td>
                            <td class="r" id="szum_ft06"></td>
                            <td class="r" id="szum_ft07"></td>
                            <td class="r" id="szum_ft08"></td>
                            <td class="r" id="szum_ft09"></td>
                            <td class="r" id="szum_ft10"></td>
                            <td class="r" id="szum_ft11"></td>
                            <td class="r" id="szum_ft12"></td>
                        </tfoot>
                    </table>
                </div>
                    <script>
                    $(document).ready(function () {
                        energia_szummaz(\'miniprojekt_tabla\');
                    });
                    </script>
                ');

    return $szamla_html;
}

// Fenntartási terv bwh fül alatti táblázat kirajzolása
function fenntartasi_terv_bwh($sor) {

    global $adatbazis_kapcsolat;

    $szamla_html .= '
        <table class="szamla_cont_tabla nolink fejlec_padd szep_csikos kisbetu bwh_tabla">
        <thead>
        <tr>
            <th class="ktgnem_kod fix bwh1"><span>Ktgnem kód</span></th>
            <th class="fix bwh2" style="min-width: 10rem; width: 10rem"><span>Ktgnem név</span></th>
            <th class="fix bwh3" style="min-width: 20rem; width: 20rem"><span>Tárgy</span></th>
            <th class="fix bwh4" style="min-width: 20rem; width: 20rem"><span>Leírás</span></th>
            <th><span>Január</span></th>
            <th><span>Február</span></th>
            <th><span>Március</span></th>
            <th><span>Április</span></th>
            <th><span>Május</span></th>
            <th><span>Június</span></th>
            <th><span>Július</span></th>
            <th><span>Augusztus</span></th>
            <th><span>Szeptember</span></th>
            <th><span>Október</span></th>
            <th><span>November</span></th>
            <th><span>December</span></th>
        </tr>
        </thead>
        <tbody>
        ';

    $bwhk = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_bwh', "`fenntartasi_terv_id`='{$sor['recid']}'", 'ktgnem_id');
    foreach ( $bwhk as $bwh ) {

        for ( $i = 1; $i < 13; $i++ ) {
            if( $i < 10 ) {
                $i = '0'.$i;
            }
            $bwh['ft'.$i] = number_format($bwh['ft'.$i], 0, '', ' ');
        }

        $szamla_html .= '
            <tr id="ft_ft_'.$bwh['recid'].'" data-recid="'.$bwh['recid'].'" class="ftt_menny">
                <td class="fix bwh1">'.$bwh['ktgnem_id'].'</td>
                <td class="wrap fix bwh2">'.getErtek('nev', 'ktgnem', 'id', $bwh['ktgnem_id']).'</td>
                <td class="wrap fix bwh3">'.$bwh['targy'].'</td>
                <td class="wrap fix bwh4">'.nl2br2($bwh['leir']).'</td>
                <td class="r ft01">'.$bwh['ft01'].'</td>
                <td class="r ft02">'.$bwh['ft02'].'</td>
                <td class="r ft03">'.$bwh['ft03'].'</td>
                <td class="r ft04">'.$bwh['ft04'].'</td>
                <td class="r ft05">'.$bwh['ft05'].'</td>
                <td class="r ft06">'.$bwh['ft06'].'</td>
                <td class="r ft07">'.$bwh['ft07'].'</td>
                <td class="r ft08">'.$bwh['ft08'].'</td>
                <td class="r ft09">'.$bwh['ft09'].'</td>
                <td class="r ft10">'.$bwh['ft10'].'</td>
                <td class="r ft11">'.$bwh['ft11'].'</td>
                <td class="r ft12">'.$bwh['ft12'].'</td>
            </tr>
            ';
    }

    $szamla_html .= nl2('
                        </tbody>
                        <tfoot class="nowrap">
                            <td class="fix bwh1"></td>
                            <td class="fix bwh2"></td>
                            <td class="fix bwh3"></td>
                            <td class="fix bwh4"></td>
                            <td class="r" id="szum_ft01"></td>
                            <td class="r" id="szum_ft02"></td>
                            <td class="r" id="szum_ft03"></td>
                            <td class="r" id="szum_ft04"></td>
                            <td class="r" id="szum_ft05"></td>
                            <td class="r" id="szum_ft06"></td>
                            <td class="r" id="szum_ft07"></td>
                            <td class="r" id="szum_ft08"></td>
                            <td class="r" id="szum_ft09"></td>
                            <td class="r" id="szum_ft10"></td>
                            <td class="r" id="szum_ft11"></td>
                            <td class="r" id="szum_ft12"></td>
                        </tfoot>
                    </table>
                    <script>
                    $(document).ready(function () {
                        energia_szummaz(\'bwh_tabla\');
                    });
                    </script>
                    ');

    return $szamla_html;
}

// Fenntartási terv összesen fül alatti táblázat kirajzolása
function fenntartasi_terv_osszesen($sor, $ful_tomb, $sajat_szallodak) {

    global $adatbazis_kapcsolat;

    $szamla_html = '
    <div id="fixTable">
        <table class="szamla_cont_tabla nolink fejlec_padd szep_csikos kisbetu osszesen_tabla">
        <thead>
        <tr>
            <th class="ktgnem_kod fix ossz1"><span>Ktgnem kód</span></th>
            <th class="fix ossz2" style="min-width: 10rem; width: 10rem;"><span>Ktgnem név</span></th>
            <th><span>Összesen</span></th>
            <th><span>Január</span></th>
            <th><span>Február</span></th>
            <th><span>Március</span></th>
            <th><span>Április</span></th>
            <th><span>Május</span></th>
            <th><span>Június</span></th>
            <th><span>Július</span></th>
            <th><span>Augusztus</span></th>
            <th><span>Szeptember</span></th>
            <th><span>Október</span></th>
            <th><span>November</span></th>
            <th><span>December</span></th>
        </tr>
        </thead>
        <tbody>
        ';

    $kivetelek = array('modosit', 'osszesen', 'tortenet', 'dokumentumok', 'megjegyzesek');
    // A BWH-t is felvesszük a kivételek közé, hacsak nem Hungáriás a user
    if( !in_array(2120, $sajat_szallodak) || $sor['telephely_id'] != 2120 ) {
        array_push($kivetelek, 'bwh');
    }
    foreach ( $ful_tomb as $tabla_post => $ful_neve ) {

        if( !in_array($tabla_post, $kivetelek) ) {

            $ful_sorok = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_'.$tabla_post, "`fenntartasi_terv_id`='{$sor['recid']}'", '', '', 'ktgnem_id');
            if( $ful_sorok ) {
                foreach ( $ful_sorok as $f_sor ) {

                    // Mivel költségnem id szerint group by-oztunk, ezért itt le kell kérni újra a sorokat, mert lehet több azonos költségnem rekord is van adott terv_id-hoz
                    // Első lépésként ezket ezért nullázzuk is
                    for ( $i = 1; $i < 13; $i++ ) {
                        if( $i < 10 ) {
                            $i = '0'.$i;
                        }
                        $f_sor['ft'.$i] = 0;
                    }
                    $akt_ful_sorok = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_'.$tabla_post, "`fenntartasi_terv_id`='{$sor['recid']}' AND `ktgnem_id`='{$f_sor['ktgnem_id']}'");
                    foreach ( $akt_ful_sorok as $akt_sor ) {
                        for ( $i = 1; $i < 13; $i++ ) {
                            if( $i < 10 ) {
                                $i = '0'.$i;
                            }
                            $f_sor['ft'.$i] += $akt_sor['ft'.$i];
                        }
                    }

                    $honapok_osszesen = 0;
                    for ( $i = 1; $i < 13; $i++ ) {
                        if( $i < 10 ) {
                            $i = '0'.$i;
                        }
                        $honapok_osszesen += $f_sor['ft'.$i];
                        $f_sor['ft'.$i] = number_format($f_sor['ft'.$i], 0, '', ' ');
                    }
                    $honapok_osszesen = number_format($honapok_osszesen, 0, '', ' ');

                    $szamla_html .= '
                        <tr id="ft_ft_'.$tabla_post.$f_sor['recid'].'" data-recid="'.$f_sor['recid'].'" class="ftt_menny">
                            <td class="fix ossz1">'.$f_sor['ktgnem_id'].'</td>
                            <td class="wrap fix ossz2">'.getErtek('nev', 'ktgnem', 'id', $f_sor['ktgnem_id']).'</td>
                            <td class="r fto '.$tabla_post.'">'.$honapok_osszesen.'</td>
                            <td class="r ft01 '.$tabla_post.'">'.$f_sor['ft01'].'</td>
                            <td class="r ft02 '.$tabla_post.'">'.$f_sor['ft02'].'</td>
                            <td class="r ft03 '.$tabla_post.'">'.$f_sor['ft03'].'</td>
                            <td class="r ft04 '.$tabla_post.'">'.$f_sor['ft04'].'</td>
                            <td class="r ft05 '.$tabla_post.'">'.$f_sor['ft05'].'</td>
                            <td class="r ft06 '.$tabla_post.'">'.$f_sor['ft06'].'</td>
                            <td class="r ft07 '.$tabla_post.'">'.$f_sor['ft07'].'</td>
                            <td class="r ft08 '.$tabla_post.'">'.$f_sor['ft08'].'</td>
                            <td class="r ft09 '.$tabla_post.'">'.$f_sor['ft09'].'</td>
                            <td class="r ft10 '.$tabla_post.'">'.$f_sor['ft10'].'</td>
                            <td class="r ft11 '.$tabla_post.'">'.$f_sor['ft11'].'</td>
                            <td class="r ft12 '.$tabla_post.'">'.$f_sor['ft12'].'</td>
                        </tr>
                        ';
                }

                $szamla_html .= '
                    <tr id="ft_ft_'.$tabla_post.$f_sor['recid'].'" data-recid="'.$f_sor['recid'].'" class="ftt_menny piros">
                        <td class="fix ossz1">
                            <script>
                                $(document).ready(function () {
                                    energia_szummaz(\'osszesen_tabla\', \''.$tabla_post.'\');
                                });
                            </script>
                        </td>
                        <td class="fix ossz2">'.$ful_neve.' összesen</td>
                        <td class="r" id="szum_fto_'.$tabla_post.'">Ö2</td>
                        <td class="r" id="szum_ft01_'.$tabla_post.'"></td>
                        <td class="r" id="szum_ft02_'.$tabla_post.'"></td>
                        <td class="r" id="szum_ft03_'.$tabla_post.'"></td>
                        <td class="r" id="szum_ft04_'.$tabla_post.'"></td>
                        <td class="r" id="szum_ft05_'.$tabla_post.'"></td>
                        <td class="r" id="szum_ft06_'.$tabla_post.'"></td>
                        <td class="r" id="szum_ft07_'.$tabla_post.'"></td>
                        <td class="r" id="szum_ft08_'.$tabla_post.'"></td>
                        <td class="r" id="szum_ft09_'.$tabla_post.'"></td>
                        <td class="r" id="szum_ft10_'.$tabla_post.'"></td>
                        <td class="r" id="szum_ft11_'.$tabla_post.'"></td>
                        <td class="r" id="szum_ft12_'.$tabla_post.'"></td>
                    </tr>
                    ';
            }
        }
    }

    $szamla_html .= nl2('
                        </tbody>
                        <tfoot class="nowrap">
                            <td class="fix ossz1"></td>
                            <td class="fix ossz2"></td>
                            <td class="r" id="szum_fto"></td>
                            <td class="r" id="szum_ft01"></td>
                            <td class="r" id="szum_ft02"></td>
                            <td class="r" id="szum_ft03"></td>
                            <td class="r" id="szum_ft04"></td>
                            <td class="r" id="szum_ft05"></td>
                            <td class="r" id="szum_ft06"></td>
                            <td class="r" id="szum_ft07"></td>
                            <td class="r" id="szum_ft08"></td>
                            <td class="r" id="szum_ft09"></td>
                            <td class="r" id="szum_ft10"></td>
                            <td class="r" id="szum_ft11"></td>
                            <td class="r" id="szum_ft12"></td>
                        </tfoot>
                    </table>
                </div>
            <script>
                $(document).ready(function () {
                    energia_szummaz(\'osszesen_tabla\');
                });
            </script>
        ');

    return $szamla_html;
}

// Mennyiségi egyég felirat cseréjéhez használt függvény
function mennyisegi_egyseg_konvertal($mee){
    
    $mee_switchhez = mb_strtolower($mee, get_web_charset());
    switch($mee_switchhez){
        case 'ft/hó'        : $mee = 'db';  break;
        case 'ft/bekötés/hó': $mee = 'db';  break;
        case 'ft/kw/hó'     : $mee = 'kW';  break;
        case 'ft/kw'        : $mee = 'kW';  break;
        case 'ft/kwh/hó'    : $mee = 'kWh'; break;
        case 'ft/kwh'       : $mee = 'kWh'; break;
        case 'ft/mj/hó'     : $mee = 'MJ';  break;
        case 'ft/mj'        : $mee = 'MJ';  break;
        case 'ft/gj'        : $mee = 'GJ';  break;
        case 'ft/mw'        : $mee = 'MW';  break;
        case 'ft/m3'        : $mee = 'm<sup>3</sup>';  break;
        case 'ft/db/hó'     : $mee = 'db';  break;
    }
    
    return $mee;
}

// Kapott sor fűtőérték sor-e? Feltételt kiszervezve függvénybe
function futoertek_sor($sor){
    if($sor['ktgnem_id']==FUTOERTEK_KOD && $sor['mee']==FUTOERTEK_MEE && fajlnev_keszit($sor['dijelem'])==FUTOERTEK_DIJELEM){
        return true;
    } else {
        return false;
    }
}

// Rendszeres karbantartásnál és ütemezett feladatoknál egy popupban mutatjuk meg, milyen ütemezést választottak. E két kódrészlet teljesen megegyezik, tehát függvénybe szerveztük
function datumra_utemezes_popup($megnevezes){
    
    global $adatbazis_kapcsolat;

    include('datumra_utemezes.php');
    
    // Üres értékeket 0-val adjunk át
    $sor = $adatbazis_kapcsolat->s2('*', $megnevezes, "`recid`='{$_GET['id']}'");

    foreach($sor as $kulcs => $ertek){
        if(!$_GET[$kulcs]){
            $_GET[$kulcs] = '0';
        }
    }
    
    if($_GET['aktkezd_d']=='    .  .  '){
        $_GET['aktkezd_d'] = '0000.00.00';
    }
    if($_GET['aktvege_d']=='    .  .  '){
        $_GET['aktvege_d'] = '0000.00.00';
    }

    $utemezes = 'Folyamatos';
    if($_GET['idoszakos']){
        if($_GET['aktkezd_d']>'0000.00.00' && $_GET['aktkezd_d']>'0000.00.00'){
            $utemezes = $_GET['aktkezd_d'].'-'.$_GET['aktvege_d'];
            if($_GET['aktvege_d']<date('Y-m-d')){
                $utemezes = $_GET['aktvege_d'].' után nincsenek további ütemezések.';
                $kell_lista = false;
            }
        } else {
            require_once 'selectek.php';
            $kezdho = '';
            foreach($select_honapok as $honap){
                if($honap['value']==$_GET['kezdho']){
                    $kezdho = $honap['name'];
                    break;
                }
            }
            $vegeho = '';
            foreach($select_honapok as $honap){
                if($honap['value']==$_GET['vegeho']){
                    $vegeho = $honap['name'];
                    break;
                }
            }
            $utemezes = $kezdho.' '.$_GET['kezdnap'].'. - '.$vegeho.' '.$_GET['vegenap'].'.';
        }
    }

    if(!$_GET['esem_d'] || $_GET['esem_d']=='0000.00.00'){
        $_GET['esem_d'] = date('Y.m.d');
    }
    
    $esem_d     = str_replace('-', '.', $_GET['esem_d']);
    $esnap_d    = str_replace('-', '.', $_GET['esnap_d']);
    $aktkezd_d  = str_replace('-', '.', $_GET['aktkezd_d']);
    $aktvege_d  = str_replace('-', '.', $_GET['aktvege_d']);
//var_dump($esem_d);
//exit;
    // $getnextvaliddate = getnextvaliddate(false, $_GET['esem_d'], $_GET['cikltip'], $_GET['ciklaltip'], $_GET['ciklszorz_'], $_GET['ciklho'], $_GET['ciklhet'], $_GET['ciklnap'], $_GET['idoszakos'], $_GET['kezdho'], $_GET['kezdnap'], $_GET['vegeho'], $_GET['vegenap'], $aktkezd_d, $aktvege_d);
    $esedekes = $_GET['esnap_d'];

// echo "$esem_d, $_GET[cikltip], $_GET[ciklaltip], $_GET[ciklszorz_], $_GET[ciklho], $_GET[ciklhet], $_GET[ciklnap], $_GET[idoszakos], $_GET[kezdho], $_GET[kezdnap], $_GET[vegeho], $_GET[vegenap], $aktkezd_d, $aktvege_d";
    if(!$esedekes || $esedekes=='0000.00.00' || $esedekes>'4000.00.00'/* Az $esedekes == '    .  .  ' feltétel sehogy sem működött*/){
        $getnextvaliddate = getnextvaliddate(false, $esem_d, $_GET['cikltip'], $_GET['ciklaltip'], $_GET['ciklszorz_'], $_GET['ciklho'], $_GET['ciklhet'], $_GET['ciklnap'], $_GET['idoszakos'], $_GET['kezdho'], $_GET['kezdnap'], $_GET['vegeho'], $_GET['vegenap'], $aktkezd_d, $aktvege_d);
        $esedekes = $getnextvaliddate;
    }

// Fontos, hogy az első ütemezés az esedékesség napja legyen, Ezért lett kivéve az alábbi kód, mert a getnextvaliddate-et futtatva már más dátum jönne ki első időpontra, pedig oda az esedékességet kell írni. 
//    if( !is_numeric( substr($esedekes, 0, 1) ) ||  $esedekes == '    .  .  ' || $getnextvaliddate>$esedekes){
//        $esedekes = $getnextvaliddate;
//    } else {
        $getnextvaliddate = $esedekes;
    //}


    $szamla_html .= '<br>Szabály: ' . getcimke( $_GET['cikltip'], $_GET['ciklaltip'], $_GET['ciklszorz_'], $_GET['ciklho'], $_GET['ciklhet'], $_GET['ciklnap'], $getnextvaliddate);
    $szamla_html .= '<br>Ütemezés: ' . $utemezes;
    $szamla_html .= '<br>Esedékes: ' . $esedekes.' '.het_napja_txt($esedekes);
    $szamla_html .= '<br>';
    $szamla_html .= '<br>Várható ütemezés:<br>';

    $szamla_html .= $getnextvaliddate . '<br>';
    for($i=0; $i<9; $i++){
//echo "$getnextvaliddate, $_GET[cikltip], $_GET[ciklaltip], $_GET[ciklszorz_], $_GET[ciklho], $_GET[ciklhet], $_GET[ciklnap], $_GET[idoszakos], $_GET[kezdho], $_GET[kezdnap], $_GET[vegeho], $_GET[vegenap], $aktkezd_d, $aktvege_d";
//exit;
        $getnextvaliddate = getnextvaliddate(false, $getnextvaliddate, $_GET['cikltip'], $_GET['ciklaltip'], $_GET['ciklszorz_'], $_GET['ciklho'], $_GET['ciklhet'], $_GET['ciklnap'], $_GET['idoszakos'], $_GET['kezdho'], $_GET['kezdnap'], $_GET['vegeho'], $_GET['vegenap'], $aktkezd_d, $aktvege_d);
        $szamla_html .= $getnextvaliddate . '<br>';
    }

    // A hibaüzenetet ne tegyük a mezőbe
    if( mb_substr($getnextvaliddate, 0, 10)=='Hibaüzenet' ){
        $getnextvaliddate = '    .  .  ';
    }
    
    return json_encode(
            array(
                'uzenet'    => 'Ön a következő ütemezést választotta:<br><div class="l" style="max-height: 20rem; overflow: auto">'.$szamla_html.'</div><br>Folytatja?',
                'esedekes'  => $esedekes
            ) 
        );
}

// Attól függően, hogy melyik rendszerben vagyunk, kap a tárgy/üzenet egy [Teszt] előtagot.
function get_mail_teszt_felirat(){
    $targy_kieg = '';
    $uzi_kieg   = '';
    $dev_kieg   = '';
    if( $_SERVER['HTTP_HOST']=='dev.onteam.hu' ){
        $dev_kieg = 'Dev ';
    }
    if( get_teszt_rendszer() ){
        $targy_kieg = '['.$dev_kieg.'Teszt email] ';
        $uzi_kieg   = '['.$dev_kieg.'Teszt email]<br><br>';
    }
    return array($targy_kieg, $uzi_kieg);
}

// Munkalap visszaküldése előkészítés státuszba, kiszervezve függvénybe, mert lehet többször is szükség lesz rá
function munkalap_vissza_elokeszitesbe($recid, $memo){
    
    global $adatbazis_kapcsolat;
    global $db_kapcs;
    global $weblap;
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat->s2('ikt_szam, stat_id, jh1_login, jh2_login, szemelyzet_id', 'munkalap', "`recid`='{$recid}'");
    if( $sor === false ) { $db_error = true; }

    // Státusz ellenőrzése
    if( $sor['stat_id']!=MUNKALAP_STAT_DONTESRE_VAR && $sor['stat_id']!=MUNKALAP_STAT_HOZZARENDELT && $sor['stat_id']!=MUNKALAP_STAT_SZTORNO_VAR ){
        echo json_encode(array(
            'hiba'  => 'Ebben a státuszban a munkalap nem küldhető vissza előkészítésre!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    // Jog ellenőrzése
    if( !_jog(JOG_MUNKALAP_KULSOS_ELOKESZIT) && !_jog(JOG_MUNKALAP_KULSOS_MEGREND) ){
        echo json_encode(array(
            'hiba'  => 'Nincs jogosultsága a munkalapot visszaküldeni előkészítésre!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }
    
    // Státusz + jog kombinációk ellenőrzése
    if(
        !(
            ($sor['stat_id']==MUNKALAP_STAT_DONTESRE_VAR && _jog(JOG_MUNKALAP_KULSOS_ELOKESZIT) && $sor['jh1_login']=='' && $sor['jh2_login']=='')  ||
            ($sor['stat_id']==MUNKALAP_STAT_HOZZARENDELT && _jog(JOG_MUNKALAP_KEZEL_KULSO)      && $sor['szemelyzet_id']==KULSO_KIVITELEZO )        ||
            ($sor['stat_id']==MUNKALAP_STAT_SZTORNO_VAR  && _jog(JOG_MUNKALAP_KULSOS_MEGREND)   && $sor['szemelyzet_id']==KULSO_KIVITELEZO )
        )
    ){
        echo json_encode(array(
            'hiba'  => 'Nincs jogosultsága a munkalapot visszaküldeni előkészítésre!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    // Döntés alatt státusz esetén
    if( $sor['stat_id']==MUNKALAP_STAT_DONTESRE_VAR ){
        
        // Ellenőrizzük, hogy volt-e már jóváhagyás?
        if( $sor['jh1_login'] || $sor['jh2_login'] ){
            echo json_encode(array(
                'hiba'  => 'A munkalapot már jóváhagyta legalább egy szereplő, így nem küldhető vissza előkészítésre!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }

        // Nézzük meg, ki tette legutóbb döntés státuszba. Annak a usernek a személyzettípusát állítsuk be hozzárendelt személynek, illetve ürítjük a külső kivitelezőt és jóváhagyott értéket
        $dontes_statusz = $adatbazis_kapcsolat->s2('login', 'munkalap_statusz', "`munkalap_recid`='{$recid}' AND `stat_id`='".MUNKALAP_STAT_DONTESRE_VAR."'", 'recid desc', '1');
        if( $dontes_statusz === false ) { $db_error = true; }
        $login = $adatbazis_kapcsolat->s2('szemelyzet_id', 'login', "`nev`='{$dontes_statusz['login']}'");
        if( $login === false ) { $db_error = true; }
        $sql = $adatbazis_kapcsolat->u('munkalap', 
                                                array('szemelyzet_id',          'szallito_id', 'becs_ertek', 'mail_ment', 'jh_ert', 'direktbe', 'varh_erk'              ),
                                                array($login['szemelyzet_id'],  '',            0,            0,           0,        0,          '0000-00-00 00:00:00'   ),
                                        "`recid`='{$recid}'");
        if( $sql === false ) { $db_error = true; }
    }
    
    // Hozzárendelt státusz esetén a hozzárendelt személy automatikusan a műszaki vezető lesz
    if( $sor['stat_id']==MUNKALAP_STAT_HOZZARENDELT || $sor['stat_id']==MUNKALAP_STAT_SZTORNO_VAR ){
        $sql = $adatbazis_kapcsolat->u('munkalap', 
                                                array('szemelyzet_id', 'vissza_mod', 'szallito_id', 'becs_ertek', 'mail_ment', 'direktbe', 'jh_ert',   'varh_erk'              ),
                                                array(MUSZAKI_VEZETO,  0,            '',            0,            0,           0,          0,          '0000-00-00 00:00:00'   ),
                                        "`recid`='{$recid}'");
        if( $sql === false ) { $db_error = true; }
    }
    
    // Státusz léptetés előkészítés alá
    if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_ELOKESZITES_ALATT, '', $memo ) ) {
        $db_error = true;
    }
    
    // Ha van memo, akkor megjegyzést is írunk
    if($memo){
        if(addMegj('munkalap', $recid, $memo)===false){
            $db_error = true;
        }
    }
    
    // Logolás
    $muvelet = $_SESSION[get_db_tabla().'teljes_nev'].' a '.$sor['ikt_szam'].' iktatószámú munkalapot visszatette előkészítésre.';
    $sql = $adatbazis_kapcsolat -> i(
                                        'log',
                                        array( 'felh_id',                               'nev',       'valtozas', 'ip'                   ),
                                        array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,    $muvelet,   $_SERVER['REMOTE_ADDR'])
                                    );
    if( $sql === false ) { $db_error = true; }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'siker'
        ));
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba',
        ));
    }
}

// Társrészleg lista előállítása egy $loginID-ból
function get_tarsreszleg_lista($loginID){
    
    global $adatbazis_kapcsolat;
    
    $reszlegek_html = '';
    
    $reszlegidk = $adatbazis_kapcsolat->s('reszleg_id', 'login_tarsreszleg', "`login_id`='{$loginID}'");
    $reszlegidk = adatbazis2tomb($reszlegidk, 'reszleg_id');

    $reszlegek = $adatbazis_kapcsolat->s('info', 'erteklista', "`id` IN (".implode(',', $reszlegidk).") AND `csoport`='".ERTEKLISTA_RESZLEG."'", 'info');
    foreach($reszlegek as $reszleg){
        $reszlegek_html .= $reszleg['info'] . '<br>';
    }
    if(!$reszlegek_html){
        $reszlegek_html = '-';
    }
    
    return $reszlegek_html;
}

// Ezeknél a menüpontoknál dokumentumok kezelésekor tipust is használunk. Így csak egy helyen kell bővíteni a menüpont listát
function tipusos_dokumentumok($tabla) {
    if( in_array($tabla, array(
                                'projektigenyles',
                                'projektkezeles', // Tibi kérésére a Projektek dokumentum tálbából kivéve a Típus oszlop 2019.08.02
                            )
                )
    ) {
        return true;
    } else {
        return false;
    }
}

// Kapott recid alapján eldönti, hogy adott Projektre van-e kezelés jogom vagy sem. Azért van itt, mert törlés blokkolása miatt az esemenyek.php-hoz is kell.
function get_projekt_kezeles_jog($recid) {

    global $adatbazis_kapcsolat;
    $prk = $adatbazis_kapcsolat->s2('pfelelos telephely_id egy_tobb', 'projektkezeles', "`recid`='{$recid}'");
    // Sima kezelés jog esetén csak saját szállodákat kezelhetünk
    if( _jog(JOG_PRKEZEL_KEZEL) && in_array($prk['telephely_id'], get_akt_login_szallodai()) ) {
        return true;
    }
    // Saját projekt kezelés jog esetén azokat kezelhetjük, ahol projekt felelős vagyok
    if( _jog(JOG_PRKEZEL_SAJAT_KEZEL) && $prk['pfelelos'] == $_SESSION[get_db_tabla() . 'login_recid'] ) {
        return true;
    }
    // Egyszállodás projekt kezelésével az egyszállodás Projekteket kezelhetjük
    if( _jog(JOG_PR_EGYSZALL_KEZEL) && $prk['egy_tobb'] == 0 && in_array($prk['telephely_id'], get_akt_login_szallodai()) ) {
        return true;
    }
    // Egyszállodás projekt kezelésével az egyszállodás Projekteket kezelhetjük
    if( _jog(JOG_PR_TOBBSZALL_KEZEL) && $prk['egy_tobb'] == 1 && in_array($prk['telephely_id'], get_akt_login_szallodai()) ) {
        return true;
    }
    
    return false;
    
}

// Kapott recid alapján eldönti, hogy adott Projekt számlára van-e kezelés jogom vagy sem. Azért van itt, mert törlés blokkolása miatt az esemenyek.php-hoz is kell.
function get_projekt_szamla_kezel_jog($recid) {

    // Ha nincs ID, akkor hamis
    if( !$recid ) {
        return false;
    }
    
    global $adatbazis_kapcsolat;
    
    // Ha van Projekt számlák kezelése globális jog, akkor biztosan kezelhetem
    if( _jog(JOG_PRSZAMLAK_KEZEL) ) {
        return true;
    }
    
    // Saját számla kezelés jog: ha a hozzá tartozó projektnek én vagyok a projektfelelőse
    if( _jog(JOG_PR_SAJAT_SZAMLA_KEZEL) ) {
        $pr_szam  = $adatbazis_kapcsolat->s2('pr_szam',  'projektszamla',  "`recid`='{$recid}'");
        $pfelelos = $adatbazis_kapcsolat->s2('pfelelos', 'projektkezeles', "`ikt_szam`='{$pr_szam['pr_szam']}'");
        if( $pfelelos['pfelelos']==$_SESSION[get_db_tabla().'login_recid'] ){
            return true;
        }
    }
    
    return false;
    
}

// Kapott recid alapján eldönti, hogy adott Projekt szerződésre van-e kezelés jogom vagy sem. Azért van itt, mert törlés blokkolása miatt az esemenyek.php-hoz is kell.
function get_projekt_szerzodes_kezel_jog($recid) {

    global $adatbazis_kapcsolat;
    
    // Ha nincs ID, akkor hamis
    if( !$recid ){
        return false;
    }
    
    // Ha van Projekt szerződés kezelése globális jog, akkor biztosan kezelhetem
    if( _jog(JOG_PRKEZEL_SZERZODES_KEZEL) ){
        return true;
    }
    
    // Saját szerződés kezelés jog: ha a hozzá tartozó projektnek én vagyok a projektfelelőse
    if( _jog(JOG_PR_SAJAT_SZERZ_KEZEL) ){
        $pr_szam  = $adatbazis_kapcsolat->s2('pr_szam', 'projektszerzodes', "`recid`='{$recid}'");
        $pfelelos = $adatbazis_kapcsolat->s2('pfelelos', 'projektkezeles', "`ikt_szam`='{$pr_szam['pr_szam']}'");
        if( $pfelelos['pfelelos']==$_SESSION[get_db_tabla().'login_recid'] ){
            return true;
        }
    }
    
    return false;
    
}

// Levélküldés után a csatolmányokat egy almappába tesszük
function csatolmanyok_eltesz($fajlok, $utvonal, $email_recid, $ml_recid, $tabla) {

    global $adatbazis_kapcsolat;
    
    $utvonal = trim($utvonal, '*');
    
    $db_error = false;
    if( !is_dir($utvonal.'/'.$email_recid) ) {
        mkdir($utvonal.'/'.$email_recid, 0777);
    }

    foreach ( $fajlok as $fajl ) {
        if( is_file($fajl) ) {
            $fajlnev = basename($fajl);
            copy($fajl, $utvonal.'/'.$email_recid.'/'. $fajlnev);
            unlink($fajl);
            $sql = $adatbazis_kapcsolat->i($tabla.'_email_dok',     array($tabla.'_recid',  'email_recid', 'fajlnev', 'loginnev',                       'update_dt',  'update_usr',                           'insert_dt', 'insert_usr'                              ),
                                                                    array($ml_recid,        $email_recid,  $fajlnev,  $_SESSION[get_db_tabla().'user'], 'NOW()',      $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',      $_SESSION[get_db_tabla().'teljes_nev']   )
                                            );
            if( $sql === false ) { $db_error = true; }
        }
    }

    if( $db_error ) {
        return false;
    } else {
        return true;
    }
        
}

// Egy rekord törlésekor a hozzá kapcsolódó gyermektáblákból törölt adatokat is szeretnénk logolni. Ezért átadunk neki egy adatbázis lekérdezésből kapott tömböt, és ő visszaadja a log szövegét.
function logolas_kapcsolodo_tablak_sorai($sorok, $tabla = ''){
    
    $valtozas = '';
    foreach($sorok as $sor){
        $i = 1;
        foreach($sor as $key => $value) {
            
            $valtozas .= '[mezo]'.$key.'[/mezo] [ertek]'.$value.'[/ertek]';
            
            switch($key){
                case 'hiba_id':
                    $valtozas .= '[cimke]'.getErtekLista(ERTEKLISTA_BEJ_HIBA, '', $value).'[/cimke]';
                break;
                case 'gep_id':
                    $valtozas .= '[cimke]'. getErtek('nev', 'eszkozkezeles', 'recid', $value).'[/cimke]';
                break;
                case 'obj_id':
                    $valtozas .= '[cimke]'. getErtek('nev', 'obj', 'recid', $value).'[/cimke]';
                break;
                case 'stat_id':
                    $valtozas .= '[cimke]'. getStatText($tabla, $value).'[/cimke]';
                break;
                case 'telephely_id':
                    $valtozas .= '[cimke]'.getSzallodaNev($value).'[/cimke]';
                break;
            }
            
            $valtozas .= "\n";
            
            $i++;
        }
    }
    $valtozas .= "\n";
    
    return $valtozas;
    
}

// Form adatok JS tömb előállításakor kiszűrjük az újsorokat és egyéb speciális karaktereket. Ez ennek az általános függvénye
function formAdatok_replace($string){
    
    return str_replace(     array("\r\n",    "\r",      "\n",       "'",            "\t",    '	 ',  '"',           '&quot;'     ),
                            array("#UJSOR#", "#UJSOR#", "#UJSOR#",  "#APOSZTROF#",  "#TAB#", "#TAB#", "#IDEZOJEL#", "#IDEZOJEL#" ),
                    $string);
}

// Előterjesztésenk (FB-t és BoD-t is tud számolni) össze költségének kiszámítása. Kell az adatlapra, excelre, nyomtatásira is, azért van külön függvénybe szervezve
function eloterjesztes_osszes_koltseg( $recid, $fb_bod ){
    
    global $adatbazis_kapcsolat;
    
    $prik = $adatbazis_kapcsolat->s('projektigenyles_recid', 'eloterj_'.$fb_bod.'_kapcs', "`eloterj_recid`='{$recid}'");
    if(!$prik){
        $ossz_kolts = 'Még nincsenek csatolt projektigénylések.';
    } else {
        foreach($prik as $pri){
            $pri = $adatbazis_kapcsolat->s2('bkolt', 'projektigenyles_eloterj', "`recid`='{$pri['projektigenyles_recid']}'");
            $ossz_kolts += $pri['bkolt'];
        }
        $ossz_kolts = number_format($ossz_kolts, 0, '', ' ').' '.get_penznem();
    }
    
    return $ossz_kolts;
    
}

// Pl. "Biztosan törli?" kérdésenél ha meg szeretnénk jeleníteni még helység nevet, vagy egyéb adaot, akkor ott vigyázni kell a speciális karakterekkel
function alert_kerdesbe_sztring( $szoveg ){
    return str_replace('\'', '', htmlspecialchars( strip_tags( htmlspecialchars_decode($szoveg) ) ) );
}

// Projektszerződések mentésekor a jótállás gyermektáblák beállítása
function pr_szerzodes_jotallas_beallit( $pr_szerzodes_id, $gar_kezd, $gar_vege, $uj_feltoltes ) {
    
    global $adatbazis_kapcsolat;

    $db_error = false;
    
    $pr_szerzodes = $adatbazis_kapcsolat->s2('muszaki_gar_kezd muszaki_gar_vege', 'projektszerzodes', "`recid`='{$pr_szerzodes_id}'");
    
    if( $gar_vege > '4000-00-00' ) {
        $gar_vege = '0000-00-00';
    }
    if( $gar_kezd > '4000-00-00' ) {
        $gar_kezd = '0000-00-00';
    }

    // Ha van egyáltalán garancia vége időpontunk, és ha változott a garancia idő vagy új feltöltés
    if( $gar_vege && $gar_vege!='0000-00-00' && ( $uj_feltoltes || $pr_szerzodes['muszaki_gar_kezd']!=$gar_kezd || $pr_szerzodes['muszaki_gar_vege']!=$gar_vege ) ) {

        // Meglevő sorokat töröljük
        // 2DO: jegyzőkönyveket is törölni!
        $jotallasok = $adatbazis_kapcsolat->s('utvonal', 'projektszerzodes_jotallas', "`projektszerzodes_recid`='{$pr_szerzodes_id}'");
        foreach( $jotallasok as $jotallas ) {
            kep_torol( '../'.get_utvonal().'projektszerzodes_jotallas/'.$jotallas['utvonal'] );
        }
        $sql = $adatbazis_kapcsolat->d('projektszerzodes_jotallas', "`projektszerzodes_recid`='{$pr_szerzodes_id}'");
        if( $sql === false ) { $db_error = true; }
        
        // A Garancia időből számítjuk, 3 eset van
        // Ha csak a garancia vége időpont van megadva (azaz nincs kezdő dátum)
        $ger_kezd_plusz_egy_ev = date('Y-m-d', strtotime("+12 months {$gar_kezd}"));
        if( !$gar_kezd || $gar_kezd=='0000-00-00' || ( $gar_kezd && $ger_kezd_plusz_egy_ev >= $gar_vege ) ) {
            $sql = $adatbazis_kapcsolat->i('projektszerzodes_jotallas', array('projektszerzodes_recid',     'bejaras',  'loginnev',                         'insert_dt',    'insert_usr',                             'update_usr'                              ),
                                                                        array($pr_szerzodes_id,             $gar_vege,  $_SESSION[get_db_tabla().'user'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']    )
                                            );
            if( $sql === false ) { $db_error = true; }

        // Ha van garancia kezdő dátum
        } else {
        
            // Ha a garancia vége dátum több mint egy év múlva van, akkor évente + a garancia idő vége dátummal létre kell hozni egy rekordot
            $akt_kezd = $gar_kezd;
            if( $ger_kezd_plusz_egy_ev <= $gar_vege ) {

                while( $akt_kezd < $gar_vege ) {
                    $sql = $adatbazis_kapcsolat->i('projektszerzodes_jotallas', array('projektszerzodes_recid',     'bejaras',  'loginnev',                         'insert_dt',    'insert_usr',                             'update_usr'                              ),
                                                                                array($pr_szerzodes_id,             $akt_kezd,  $_SESSION[get_db_tabla().'user'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']    )
                                                    );
                    if( $sql === false ) { $db_error = true; }
                    $akt_kezd = date('Y-m-d', strtotime("+12 months {$akt_kezd}"));
                }
                $sql = $adatbazis_kapcsolat->i('projektszerzodes_jotallas', array('projektszerzodes_recid',     'bejaras',  'loginnev',                         'insert_dt',    'insert_usr',                             'update_usr'                              ),
                                                                            array($pr_szerzodes_id,             $gar_vege,  $_SESSION[get_db_tabla().'user'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']    )
                                                );
                if( $sql === false ) { $db_error = true; }
            }
            
        }
        
    }
    
    // Ha hiba volt, azt jelezni kell, hogy a tranzakció megszakítható legyen
    if( $db_error ) {
        return false;
    }
    
}

// Projekt kezelés felülete alatt a where feltételt aszerint állítjuk be hogy milyen jogaink vannak. 
// Ezt bal navpanel esetén, de főoldali dashboardnál is ki kell számolni, ezért központosítva van
function get_projektkezeles_where_jog() {
    
    global $adatbazis_kapcsolat;
    
    // Ha Projekt team tag vagyok, akkor láthatom azokat a Projekteket, ahol a Projekt teamként jelen vagyok
    if( _jog(JOG_PRKEZEL_TEAM_TAG) ) {
        $team_vagyok = ' OR `'.get_db_tabla().'projektkezeles`.`recid` IN (SELECT `projektkezeles_recid` FROM `'.get_db_tabla().'projektkezeles_team` WHERE `login_recid`=\''.$_SESSION[get_db_tabla().'login_recid'].'\')';
    }

    // Projektek megtekintése: saját szállodák láthatósági körébe tartozó projektek lisstázása
    $sajtat_szaloda_latokor = "`".get_db_tabla()."projektkezeles`.`telephely_id` IN (".implode(',', get_akt_login_szallodai()).")";

    // Projekt típus jog alapján tovább szűkítünk
    $tipusra_szures = '';
    if( _jog(JOG_PR_B_MEGTEKINT) ) {
        $tipusra_szures .= PRIGENY_TIPUS_BERUH.', ';
    }
    if( _jog(JOG_PR_F_MEGTEKINT) ) {
        $tipusra_szures .= PRIGENY_TIPUS_FENNT.', ';
    }
    if( _jog(JOG_PR_T_MEGTEKINT) ) {
        $tipusra_szures .= PRIGENY_TIPUS_TARGY.', ';
    }
    if( $tipusra_szures ) {
        $tipusra_szures = "`".get_db_tabla()."projektkezeles`.`tipus` IN (".csonkit($tipusra_szures, 2).")";
    }

    // Globális jogosultság, ha ez be van pipálva, akkor nem kell már tovább szűrni
    if( _jog(JOG_PRKEZEL_MEGTEKINT) ) {

        $where = "({$sajtat_szaloda_latokor}{$team_vagyok}) AND ";

    // Ha "Projektek megtekintése" (JOG_PRKEZEL_MEGTEKINT) jog nincs bepipálva, csak akkor kell a többivel foglalkozni, mert a legtágabb jog kibővít minden más, szűkebb láthatóságot
    } else {

        // Saját projektek megtekintése: ekkor csak azokat listázzuk, ahol én Projektfelelős vagyok
        if( _jog(JOG_PRKEZEL_SAJAT_MEGTEKINT) ) {
            $or = '';
            if( $tipusra_szures ) {
                $or = ' OR ';
            }
            $where = "(`".get_db_tabla()."projektkezeles`.`pfelelos`='{$_SESSION[get_db_tabla().'login_recid']}' {$team_vagyok} {$or} {$tipusra_szures}) AND ";

        // Egy/több szálloda láthatósági feltétele
        } elseif( _jog(JOG_PR_EGYSZALL_MEGTEKINT) || _jog(JOG_PR_TOBBSZALL_MEGTEKINT) ) {

            // Egyszálodás projekt láthatósága
            $lathatosag_feltetel = '';
            if( _jog(JOG_PR_EGYSZALL_MEGTEKINT) ) {
                $lathatosag_feltetel .= "`".get_db_tabla()."projektkezeles`.`egy_tobb`=0 OR ";
            }

            // Többszállodás projekt láthatósága
            if( _jog(JOG_PR_TOBBSZALL_MEGTEKINT) ){
                $lathatosag_feltetel .= "`".get_db_tabla()."projektkezeles`.`egy_tobb`=1 OR ";
            }
            $where = "( ({$sajtat_szaloda_latokor} AND (".csonkit($lathatosag_feltetel, 4).")) {$team_vagyok} OR  {$tipusra_szures}) AND";

        // Ha csak Team tag jogunk volna
        } else {
            $or = '';
            if( $team_vagyok && $tipusra_szures ) {
                $or = ' OR ';
            }
            $where = "(".substr($team_vagyok, 4)." $or {$tipusra_szures})";
        }

    }
    
    return $where;
}

// Projekteknél a számlázott összeget számoljuk ki a kapcsolódó számlák összegéből.
// Azért van itt központilag függgvényesítve, mert nem csak a projektkezeles.php-ban van rá szükség, hanem mondjuk az i_u() függvényben is
function projektkezeles_szamlazott_osszeg ( $pr_szam ) {
    
    global $db_kapcs;

    if( $pr_szam ) {
        
        $update1 = mysqli_query($db_kapcs, "UPDATE `".get_db_tabla()."projektkezeles` 
                                        SET `szla_ossz`=( 
                                                        SELECT SUM(`netto`) FROM `".get_db_tabla()."projektszamla` WHERE `visszaadva`=0 AND `pr_szam`='{$pr_szam}'
                                                        )
                                         WHERE `ikt_szam`='{$pr_szam}'");

        $update2 = mysqli_query($db_kapcs, "UPDATE `".get_db_tabla()."projektkezeles` SET `sz_elteres`=`sz_ossz`-`szla_ossz` WHERE `ikt_szam`='{$pr_szam}'");
        
        if( !$update1 || !$update2 ) {
            return false;
        } else {
            return true;
        }
        
    // Ha nem volt projektszámunk, akkor sikeresen updateltük azt
    } else {
        return true;
    }
    
}

// Mivel szűrni kell tudni arra, hogy adott projektnek van-e minősítésre váró dokumentuma, ezért dokaumenutmok
// mozgásakor ezt mindig nyilván kell tartani
function projekt_minositesre_varo_dok_szamol( $pr_id ) {
    
    global $adatbazis_kapcsolat;
    
    $db_error = false;
    
    // Akkor számoljuk össze
    $kepek_direkt = $adatbazis_kapcsolat->s('recid', 'projektkezeles_dok', "`projektkezeles_id`='{$pr_id}' AND `tipus`='0'", '', '', '', 'num_rows');
    if( $kepek_direkt === false ) { $db_error = true; }
    
    $projekt = $adatbazis_kapcsolat->s2('ikt_szam', 'projektkezeles', "`recid`='{$pr_id}'");
    if( $projekt === false ) { $db_error = true; }
    
    $szerzodesek = $adatbazis_kapcsolat->s('recid', 'projektszerzodes', "`pr_szam`='{$projekt['ikt_szam']}'");
    if( $szerzodesek === false ) { $db_error = true; }
    $szerzodesek = adatbazis2tomb($szerzodesek, 'recid');
    
    // Ha van szerződés, akkor a szerződés dokumentumokat is kirajzoljuk
    if( $szerzodesek ) {
        $kepek = $adatbazis_kapcsolat -> s('recid', 'projektszerzodes_dok', "`projektszerzodes_id` IN (".implode(',', $szerzodesek).") AND `tipus`='0'", '', '', '', 'num_rows');
        if( $kepek === false ) { $db_error = true; }
    }
    
    // Végül update
    $sql = $adatbazis_kapcsolat->u('projektkezeles', array('dok_db_min_var'), array($kepek_direkt+$kepek), "`recid`='{$pr_id}'");
    if( $sql === false ) { $db_error = true; }
    
    // Visszatéréskor jeleheztjük a DB hibát
    if( $db_error ) {
        return false;
    } else {
        return true;
    }
    
}

// Adott felhasználónak milyen SQL feltételt kell összeállítani, ha a Munkalapok felületére lép?
// Ezt központosítjuk itt, hisz nem csak a bal navpanelben lesz rá szükség, hanem a nyitólapi dashboard-nál is
function get_munkalap_lathatosag_feltetel($tabla, $szallodak) {
    
    global $adatbazis_kapcsolat;
    
    // Ki-ki csak a saját szállodáit láthatja, ez alap
    $where = "`".get_db_tabla().$tabla."`.`telephely_id` IN (".implode(',', $szallodak).") AND ";

    // Ha mobilcsopotos felhasználó vagyok csak azokat a munkalapokat látom, amik mobilhoz vannak rendelve és a hozzárendelt személy üres vagy én vagyok.
    if( $_SESSION[get_db_tabla() . 'szemelyzet_id'] == MOBILCSOPORT ) {
        $login = $adatbazis_kapcsolat->s2('mobil', 'login', "`recid`='{$_SESSION[get_db_tabla().'login_recid']}'");
        if( $login['mobil'] ) {
            $where .= "`".get_db_tabla().$tabla."`.`szemelyzet_id`='{$_SESSION[get_db_tabla().'szemelyzet_id']}' AND (`mobil_loginnev`='' OR `mobil_loginnev`='{$_SESSION[get_db_tabla().'user']}') AND `mobil`='{$login['mobil']}' AND ";
        // Ha nincs mobilcsoportom, akkor nem látok munkalapokat
        } else {
            $where .= ' FALSE AND ';
        }

    // Ha nem mobilcsoport vagyok, akkor még a saját szállodák jogra kell figyelni:
    } else {

        // Saját szem. tip. megtekintése: saját szállodák + hozzárendelt személyzet: tehát tovább szűrünk hozzárendelt személyzetre, amit a felhasználókon, majd személyezeten át kapunk meg
        if( _jog(JOG_MUNKALAP_MEGTEKINT) ) {
            // Hozzárendelt személyzet megállapítása. Ez egyenlő a Személyzet beosztásával.
            $where .= "`".get_db_tabla().$tabla."`.`szemelyzet_id`='{$_SESSION[get_db_tabla().'szemelyzet_id']}' AND ";
            if( !$_SESSION[get_db_tabla() . 'szemelyzet_id'] ) {
                $where .= ' FALSE AND ';
            }
        }
    }

    // Azaz ha saját szállodáim/saját feladataim jogom van, akkor az egy lazább feltétel, nem szűrünk hozzárendelt személyzetre
    if( _jog(JOG_MUNKALAP_MEGTEKINT_SZALL) ) {
        $where .= '';
    }
    // Ha saját feladatok a láthatósági kör, akkor az SQL feltétel azon múlik, hogy milyen jogai vannak a felhasználónak
    // Archív munkalaponknál nincs külön státuszra szűrés, ott ez értelmetlen
    if( _jog(JOG_MUNKALAP_MEGTEKINT_FELADAT) && $tabla != 'munkalap_arch' ) {
        $statuszra_szures = array();
        if( _jog(JOG_MUNKALAP_IGAZOLAS) ) {
            array_push($statuszra_szures, MUNKALAP_STAT_IGAZOLASRA_VAR);
        }
        if( _jog(JOG_MUNKALAP_VIZSGALAT) ) {
            array_push($statuszra_szures, MUNKALAP_STAT_VIZSGALAT_ALATT);
        }
        if( _jog(JOG_MUNKALAP_KULSOS_ELOKESZIT) ) {
            array_push($statuszra_szures, MUNKALAP_STAT_ELOKESZITES_ALATT);
        }
        if( _jog(JOG_MUNKALAP_KULSOS_DONTES) ) {
            array_push($statuszra_szures, MUNKALAP_STAT_DONTESRE_VAR);
        }
        if( _jog(JOG_MUNKALAP_KULSOS_MEGREND) ) {
            array_push($statuszra_szures, MUNKALAP_STAT_HOZREND_MEGREND_VAR);
            array_push($statuszra_szures, MUNKALAP_STAT_AJANLAT_NYOMTATVA);
            array_push($statuszra_szures, MUNKALAP_STAT_SZTORNO_VAR);
        }

        // Szűrés azokra a státuszokra, amiket láthat
        if( $statuszra_szures ) {
            $where .= "`".get_db_tabla().$tabla."`.`stat_id` IN (".implode(',', $statuszra_szures).") AND ";
        // Ha egyiket sem láthatja, akkor legyen üres a lista
        } else {
            $where .= ' FALSE AND ';
        }
    }
    
    // Dasboardról kaphatunk még szűrést döntésre váróra ($_GET['dv']);
    if( $_GET['dv'] ) {
        $login = $adatbazis_kapcsolat->s2('jh_ft_ig', 'login', "`recid`='{$_SESSION[get_db_tabla().'login_recid']}'");
    }
    if( $_GET['dv'] == 'sz_ig' ) {
        $where .= " `jh1_szerep`!='".SZALLODA_IGAZGATO."' AND `jh2_szerep`!='".SZALLODA_IGAZGATO."' AND `jh_ert`<='{$login['jh_ft_ig']}' AND ";
    }
    if( $_GET['dv'] == 'mszk_vez' ) {
        $where .= " `jh1_szerep`!='".MSZK_VEZETO."' AND `jh2_szerep`!='".MSZK_VEZETO."' AND `jh_ert`<='{$login['jh_ft_ig']}' AND ";
    }
    
    return $where;
}

// Minden CSV export előtt ezeket be kell állítani
function csv_export_elokeszit($megnevezes) {
    // Progresshez szükséges fejlécek
    header('X-Accel-Buffering: no');
    header('Content-Type: text/octet-stream');
    header('Cache-Control: no-cache');
    
    $mibol   = get_web_charset();
    $mibe    = 'windows-1250';

    $target  = CSV_EXPORT_UTVONAL_1;
    if( !is_dir($target) ) {
        mkdir($target);
    }
    $target .= CSV_EXPORT_UTVONAL_2;
    if( !is_dir($target) ) {
        mkdir($target);
    }
    
    // Régi fájlok törlése
    $fajlok = konyvtar_bejar($target);
    foreach ( $fajlok as $fajl ) {
        if( filemtime($fajl) < (time() - CSV_EXPORT_ELAVULAS) ) {
            unlink($fajl);
        }
    }

    $filename = $target.'/'.$megnevezes.'_'.time().'.csv';

    // Open the output stream
    $open = fopen($filename, 'w');
    fwrite($open, "sep=;\n");
    
    return array($mibol, $mibe, $filename, $open);
}

// $lekerdezes_fromtol változó kiszámítása
function csv_export_get_from_tol( $megnevezes ) {
    
    $lekerdezes_fromtol = substr($_SESSION['akt_sql'], strpos($_SESSION['akt_sql'], 'FROM'));

    // Eredetileg csak a bal navpanelben is megjelenő sorokat listázzuk ki (cél, hogy illetéktelen sorokhoz ne lehessen hozzáférni).
    // Viszont ha érvényben volna az alapértelmezett LIMIT az aktuális SQL-ben, akkor ezt az ID ellenőrzést mégsem tesszük meg,
    // hisz minden sort ki szeretnénk írtani. De ha a bámrilyen más LIMIT szerepel benne, akkor meg a CSV-ben is a limitált mennyiséget szeretnénk látni.
    if( $_SESSION['nyomtatas_ids'] && strpos( $_SESSION['akt_sql'], 'LIMIT '.SZURES_DB_LIMIT )===false ) {
        if( strstr($_SESSION['akt_sql'], 'WHERE') ) {
            $lekerdezes_fromtol = str_replace('WHERE', "WHERE `recid` IN (".$_SESSION['nyomtatas_ids'].") AND ", $lekerdezes_fromtol);
        } else {
            $lekerdezes_fromtol = str_replace('ORDER', "WHERE `recid` IN (".$_SESSION['nyomtatas_ids'].") ORDER", $lekerdezes_fromtol);
        }
    }

    // Ha van $_SESSION['akt_sql_order'], akkor eszerint rendezünk, tehát a $lekerdezes_fromtol-t elvágjuk az Order-nél
    if( $_SESSION['akt_sql_order'] ){
        $hossz = strpos($lekerdezes_fromtol, 'ORDER');
        if( $hossz ) {
            $lekerdezes_fromtol = substr($lekerdezes_fromtol, 0, $hossz);
        } else {
            $lekerdezes_fromtol = substr($lekerdezes_fromtol, 0);
        }
    }
    // Ha nincs FROM rész, akkor azt még beletesszük
    if( !$lekerdezes_fromtol ) {
        $lekerdezes_fromtol = ' FROM `'.get_db_tabla().$megnevezes.'` ';
    }

    return $lekerdezes_fromtol;
}

// Minden CSV export után ezeket be kell állítani
function csv_export_lezar($fh, $filename, $nev = '') {
    
    global $weblap;
    
    // Get the contents of the output buffer
    fclose($fh);

    echo json_encode(array(
        'filename'  => $filename,
        'nev'       => $nev,
    ));

    // Felszabadítás
    ob_flush();
    flush();
    
    $weblap->leallit();
    exit();
}