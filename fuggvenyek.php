<?php
/*                                                                                                                          *\
    Ide írd meg a saját, globális függvényeidet. Mivel ez a header() előtt meghívódik, kimenetele a fájlnak ne legyen.
\*                                                                                                                          */







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
    if($type == 'update') {
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
    if($tabla=='bejelentes' || $tabla=='munkalap' ){
        $obj_txt = getErtek('nev', 'obj', 'recid', $_POST['obj_id']);
        array_push($oszlopok, 'obj_txt');
        array_push($ertekek,  $obj_txt);
    }
    // Munkalapnál meg a gép nevét
    if($tabla=='munkalap' ){
        $gep_txt = getErtek('nev', 'eszkozkezeles', 'recid', $_POST['gep_id']);
        array_push($oszlopok, 'gep_txt');
        array_push($ertekek,  $gep_txt);
    }

    // Értékek párosítása
	$i = 0;
	foreach($tomb as $ertek) {
        if( $ertek['type'] != 'nosql' ) {

    		// Ha alert-es egy mező, és a post-ja üres, akkor hiba! (nem adott meg egy kötelező mezőt)
            if($ertek['alert'] != '' && $_POST[$ertek['name']] == '' && !$ertek['disabled']) {
    			return array($i, '');
    			break;
    		}

            // Ha az adott esemenyek.php tomb sora tartalmaz nevid jelzést, akkor ezt a sort eltároljuk, mert ennek a nevéből gyártuk névid-t
            if($ertek['nevid'] == true) {
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
            if(strpos($ertek['class'], 'datepicker')!==false){
                $_POST[$ertek['name']] = str_replace('.', '-', $_POST[$ertek['name']]);
            }

            // Szám tiupusból ki kell szűrni a szóközt (ezresítés), és a vesszőt pontra cserélni
            if(strpos($ertek['class'], 'f_num')!==false){
                $_POST[$ertek['name']] = str_replace(array(' ', ','), array('', '.'), $_POST[$ertek['name']]);
            }

    		// Ha nem gomb, nem fájl és nem disabled és az oszlopnév sem üres és maga a POST egyáltalán létezik is (ne nullázzunk felül el nem küldött POST miatt)
    		if($ertek['type'] != 'submit' && $ertek['type'] != 'button' && $ertek['type'] != 'file' && $ertek['type'] != 'flashfile' && $ertek['type'] != 'jsfile' && !($ertek['disabled'] && $ertek['name'] != 'szallito_id' && $type == 'update'/*Tiltott mezőket beszúrni lehet csak moddolni nem*/) && $ertek['name'] && isset($_POST[$ertek['name']]) ) {
                array_push($oszlopok, $ertek['name']);
    			array_push($ertekek, trim($_POST[$ertek['name']]));
    		}

    		$i++;
        }
	}

    // Dátumra ütmezés, ha az ütemezett feladatokat módosítjuk/töltjük fel
    if( $tabla == 'utemezett_feladatok' || $tabla == 'rendszeres_karbantartas' ) {

        if($_POST['cikltip']=='l'){
            array_push($oszlopok, 'ciklaltip'       );
            array_push($ertekek,  ''                );
            array_push($oszlopok, 'ciklszorz'       );
            array_push($ertekek,  ''                );
        }
        // Napi cilus, X naponként ismétlődik
        if($_POST['ciklnap'] && $_POST['cikltip']!='m' && $_POST['cikltip']!='w'){
            array_push($oszlopok, 'ciklaltip'       );
            array_push($ertekek,  '1'               );

            array_push($oszlopok, 'ciklszorz'       );
            array_push($ertekek,  $_POST['ciklnap'] );
        }

        // Napi ciklus, hétköznaponként ismétlődik
        $hetkoz_kent = '';
        if($type == 'insert') {
            if($_POST['hetkoz_kent']){
                $hetkoz_kent = '1';
            }
        }else{
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
        if($_POST['ciklho']){
            array_push($oszlopok, 'ciklaltip'   );
            array_push($ertekek,  '1'           );

            array_push($oszlopok, 'ciklszorz'      );
            array_push($ertekek,  $_POST['ciklho'] );

            $index = array_search('ciklnap', $oszlopok);
            $ertekek[$index] = $_POST['honapjan'];

        }

        if($_POST['ciklho2']){
            array_push($oszlopok, 'ciklaltip'       );
            array_push($ertekek,  '2'               );

            array_push($oszlopok, 'ciklszorz'       );
            array_push($ertekek,  $_POST['ciklho2'] );

            $index = array_search('ciklnap', $oszlopok);
            $ertekek[$index] = $_POST['ho_rendsz_het'];
        }

        if($_POST['ism_het']){
            array_push($oszlopok, 'ciklszorz'       );
            array_push($ertekek,  $_POST['ism_het'] );
            $cikl_nap = '';
            if($type == 'insert') {
                if($_POST['h']) { $cikl_nap .= '1'; }
                if($_POST['k']) { $cikl_nap .= '2'; }
                if($_POST['s']) { $cikl_nap .= '3'; }
                if($_POST['cs']){ $cikl_nap .= '4'; }
                if($_POST['p']) { $cikl_nap .= '5'; }
                if($_POST['sz']){ $cikl_nap .= '6'; }
                if($_POST['v']) { $cikl_nap .= '7'; }
            }else{
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

//echo '<pre style="font-size: 1.4rem">';
//print_r($oszlopok);
//echo '</pre>';
//echo '<pre style="font-size: 1.4rem">';
//print_r($ertekek);
//echo '</pre>';
//exit;
    }

    // Új bejegyzés esetén több mindent is le kell kezelni (iktatószám kizsámolás, plusz oszlopok beállítása
	if($type == 'insert') {
        // Insert DT és USR lekezelése általánosan
        if( in_array($tabla.'.php', $_SESSION['ketHasabos']) ) {
            array_push($oszlopok, 'insert_dt',  'insert_usr'                          );
            array_push($ertekek,   'NOW()',     $_SESSION[get_db_tabla().'teljes_nev']);
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
            foreach($oszlopok as $oszl){
                if($oszl=='nev'){
                    $ertekek[$i] = htmlspecialchars_decode($cikktorzs['nev']);
                }
                $i++;
            }

            // Ha be volt pipálva, hogy külsősnek, akkor oracikkid-t felülírjuk, és kulsősnek pipát beállítjuk
            if($_POST['kulsosnek']){
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
            foreach($oszlopok as $oszl){
                if($oszl=='ikt_szam'){
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
            $ikt_szam = 'ELFB'.date('y').'/'.sprintf('%07d', $sorszam);

            // Felvesszük az értékek közé
            $i = 0;
            foreach($oszlopok as $oszl){
                if($oszl=='ikt_szam'){
                    $ertekek[$i] = $ikt_szam;
                    break;
                }
                $i++;
            }

        // BOD Előterjesztés új bejegyzés esetén
        } else if( $tabla == 'eloterj_fb' ) {

            // Iktatószám kiszámítása
            $sorszam  = get_iktatoszam_sorszam($tabla);
            if($sorszam===false){ $db_error = true; }
            $ikt_szam = 'ELBO'.date('y').'/'.sprintf('%07d', $sorszam);

            // Felvesszük az értékek közé
            $i = 0;
            foreach($oszlopok as $oszl){
                if($oszl=='ikt_szam'){
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
            foreach($oszlopok as $oszl){
                if($oszl=='id'){
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
            foreach($oszlopok as $oszl){
                if($oszl=='id'){
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
            foreach($oszlopok as $oszl){
                if($oszl=='id'){
                    $ertekek[$i] = $id;
                    break;
                }
                $i++;
            }

            array_push($oszlopok, 'loginnev'                        );
            array_push($ertekek,   $_SESSION[get_db_tabla().'user'] );

        }

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

        // Anyagigénylés státusz beszúrása
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

        // Szállodákat is beállítjuk
        if( $tabla == 'login' ) {
            $adatok = explode(',', str_replace(array('"', '{', '}'), '', $_POST['szallodak_hidden']));
            foreach($adatok as $adat){
                list($x, $telephely_id) = explode(':', $adat);
                $sql = $adatbazis_kapcsolat->i('telephely_kapcs',
                                                            array('nev',        'telephely_id', 'insert_dt',    'insert_usr',                             'update_usr'                           ),
                                                            array($_POST['nev'], $telephely_id, 'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'] )
                                                );
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

            // Ha van előkészítős jogunk, és post-ként kaptunk is "elokeszitett_statuszba" információt, akkor rögtön léptetjük is tovább a sáttuszát
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

        // Naplózás
        // Kezdeményezett művelet
        $muvelet = 'A(z) '.$tabla.'-ba új recordot szúrt be a következő azonosítóval: '.$id.'.';

        // Végrehajtott változtatás
        $valtozas = '1.: A(z) <strong>recid</strong> mező a következő értéket kapta: <strong>'.$id.'</strong><br>';

        $i = 2;
        foreach($_POST as $key => $value) {
            if( $key != 'mehet_gomb' && $key != 'uj'.$tabla ) {
                $valtozas .= $i.'.: A(z) <strong>'.$key.'</strong> mező a következő értéket kapta: <strong>'.$value.'</strong><br>';

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

            // Kezdeményezett művelet
            $muvelet = 'A(z) '.$tabla.' '.$_GET['id'].'. recordját módosította.';

            // Végrehajtott változtatás
            $valtozas = '';

            $i = 1;
            foreach($record[0] as $key => $value) {
                if( $key != 'recid' && $_POST[$key] ) {
                    if( preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value) ) {
                        $value = date('Y.m.d', strtotime($value));
                    }

                    if( $_POST[$key] != $value ) {
                        $valtozas .= $i.'.: A(z) <strong>'.$key.'</strong> mező értékét <strong>"'.$value.'"</strong>-ról <strong>"'.$_POST[$key].'"</strong>-ra változtatta.<br>';

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
        }

        // Datetime-ot is frissítjük
        if( in_array($tabla.'.php', $_SESSION['ketHasabos']) ) {
            array_push($oszlopok, 'update_usr'                          );
            array_push($ertekek,  $_SESSION[get_db_tabla().'teljes_nev']);
        }

        // Loginnál szerepkörök lekezelése + Jelszóval rendelkezik pipa kiszedése esetén e-mail értesítés küldése
        if( $tabla == 'login' ) {

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
                $cash_flowk = $adatbazis_kapcsolat->s2('DISTINCT telephely_id', 'cash_flow', "`apa_recid`='{$_GET['id']}'");
                if( count($cash_flowk)==1 ){

                    $i_tmp = 0;
                    foreach($oszlopok as $ertek){
                        if($ertek=='egy_tobb'){
                            $ertekek[$i_tmp] = 0;
                        }
                        if($ertek=='telephely_id'){
                            $ertekek[$i_tmp] = $cash_flowk['telephely_id'];
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
                $ikt_szam = 'PI'.date('y').'/'.sprintf('%07d', $sorszam);

                array_push($oszlopok,  'ikt_szam');
                array_push($ertekek,   $ikt_szam );
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

                // Üzemezett feladatoknál az esedékesség dátumát még ki kell számolni
                if( $tabla == 'utemezett_feladatok' ) {

                    $sor_tmp = $sor[0];

                    include('datumra_utemezes.php');
                    if(!$sor_tmp['esem_d'] || $sor_tmp['esem_d']=='0000-00-00'){
                        $sor_tmp['esem_d'] = date('Y-m-d');
                    }

                    $esem_d     = substr(str_replace('-', '.', $sor_tmp['esem_d']), 0, 10);
                    $esnap_d    = str_replace('-', '.', $sor_tmp['esnap_d']);
                    $aktkezd_d  = str_replace('-', '.', $sor_tmp['aktkezd_d']);
                    $aktvege_d  = str_replace('-', '.', $sor_tmp['aktvege_d']);

                    // 0-ék helyett használjunk üres stringet
                    foreach($sor_tmp as $kulcs => $ertek){
                        if(!$sor_tmp[$kulcs]){
                            $sor_tmp[$kulcs] = '';
                        }
                    }

                    $getnextvaliddate = getnextvaliddate(false, $esem_d, $sor_tmp['cikltip'], $sor_tmp['ciklaltip'], $sor_tmp['ciklszorz'], $sor_tmp['ciklho'], $sor_tmp['ciklhet'], $sor_tmp['ciklnap'], $sor_tmp['idoszakos'], $sor_tmp['kezdho'], $sor_tmp['kezdnap'], $sor_tmp['vegeho'], $sor_tmp['vegenap'], $aktkezd_d, $aktvege_d);
                    $uj_esnap_d = str_replace('.', '-', $getnextvaliddate);
                    $adatbazis_kapcsolat->u($tabla, array('esnap_d'), array($uj_esnap_d), "`recid`='{$_GET['id']}' AND `esnap_d`<'$uj_esnap_d'");

                }

                echo json_encode(array(
                    'statusz' => 'siker',
                    'adatok'  => $sor[0]
                ));

            }
        }
	}

	if(isset($_GET['id']) && $type == 'update'){
		$id = $_GET['id'];
	}

    // Ha kell, akkor beállítjuk még a nevid-s oszlopot
	if($nevid != '' && $type == 'insert'){

		$nevid = fajlnev_keszit($_POST[$nevid[name]]);
		if(!$nevid){
            $nevid = 'noname';
        }
        $nevid_tomb = array();
        $nevidk = $adatbazis_kapcsolat -> s('nevid', $tabla, '`nevid` LIKE \''.$nevid.'%\'');
        if( $nevidk === false ) { $db_error = true; }
        foreach($nevidk as $nev_id){
            array_push($nevid_tomb, $nev_id['nevid']);
        }

        while(in_array($nevid, $nevid_tomb)){
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
    foreach($tomb as $ertek){
        if( $ertek['type'] != 'statusz' && $ertek['type'] != 'info' ) {
            if($ertek['name']) {

                // Csak akkor figyelünk lenti paramétereket, ha egyáltalán az elem létezik. Ezzel egy csomó JS hibát elekerólhetünk és debuggolós szívásokat
                $ret .= 'if($(\'#'.$ertek['name'].'\').length){ ';

                if($ertek['alert']){
                    $focus_autocomp = $ertek['name'];
                    if( $ertek['type'] == 'autocomplete' ) {
                        $focus_autocomp = $ertek['name'].'_autocomp';
                    }

                    $ret .= 'if($(\'#'.$ertek['name'].'\').val()==\'\'){ document.getElementById(\''.$focus_autocomp.'\').className += \' alert\'; $(\'#'.$focus_autocomp.'\').addClass(\'hibas_ide_focus\'); alert2(\''.$ertek['alert'].'\', \'<p class="c"><input type="button" class="link gomb" value="Ok" onclick="alert2();"></p>\', \''.$hiba_txt.'\'); return false; }';
                }

                // Ha numerikus a mező, akkor a 0,00 is üres értéknek számít
                if(strpos($ertek['class'], 'f_num')!==false){
                    $ret .= 'if(document.getElementById(\''.$ertek['name'].'\').value==\'0,00\'){ document.getElementById(\''.$ertek['name'].'\').className += \' alert\'; $(\'#'.$ertek['name'].'\').addClass(\'hibas_ide_focus\'); alert2(\''.$ertek['alert'].'\', \'<p class="c"><input type="button" class="link gomb" value="Ok" onclick="alert2();"></p>\', \''.$hiba_txt.'\'); return false; }';
                }

                // Ha dátum tipusú a mezők ellenőrzése
                if( strpos($ertek['class'], 'datepicker')!==false || strpos($ertek['class'], 'datetimepicker')!==false ){
                    $ret .= 'if( !valid_date( $(\'#'.$ertek['name'].'\') )===false ){ return false; }';
                }
                if( strpos($ertek['class'], 'ora_perc')!==false ){
                    $ret .= 'if( valid_ido( $(\'#'.$ertek['name'].'\') )===false ){ return false; }';
                }

                // Ha dátum mező, akkor az ürességet másképp figyeljük
                if($ertek['alert'] && (strpos($ertek['class'], 'datepicker')!==false || strpos($ertek['class'], 'datetimepicker')!==false)){
                    $ret .= 'if(document.getElementById(\''.$ertek['name'].'\').value.substring(0,1)==\'\xa0\'){ document.getElementById(\''.$ertek['name'].'\').className += \' alert\'; $(\'#'.$ertek['name'].'\').addClass(\'hibas_ide_focus\'); alert2(\''.$ertek['alert'].'\', \'<p class="c"><input type="button" class="link gomb" value="Ok" onclick="alert2();"></p>\', \''.$hiba_txt.'\'); return false; } ';
                }

                // Személyzet_id-nál a 0 sem elfogadható érték
                if($ertek['alert'] && $ertek['name']=='szemelyzet_id'){
                    $ret .= 'if(document.getElementById(\''.$ertek['name'].'\').value==\'0\'){ document.getElementById(\''.$ertek['name'].'\').className += \' alert\'; $(\'#'.$ertek['name'].'\').addClass(\'hibas_ide_focus\'); alert2(\''.$ertek['alert'].'\', \'<p class="c"><input type="button" class="link gomb" value="Ok" onclick="alert2();"></p>\', \''.$hiba_txt.'\'); return false; } ';
                }

                if($ertek['mail']){
                    $ret .= "str=document.getElementById('$ertek[name]').value.trim(); if(str){ filter=/^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i; if (!filter.test(str)){ document.getElementById('{$ertek['name']}').className='alert'; $('#'{$ertek['name']}').addClass(\'hibas_ide_focus\');  alert2('Helytelen e-mail cím!', \'\', \''.$hiba_txt.'\'); return false; } }";
                }

                if($ertek['name'] == 'jelsz2'){
                    $ret .= 'if(document.getElementById(\'reg_jelsz\').value.length < 4 ){document.getElementById(\''.$ertek['name'].'\').className += \' alert\';alert2(\'A jelszó hossza minimum 4 karakter legyen!\', \'\', \''.$hiba_txt.'\');return false;}';
                    $ret .= 'if(document.getElementById(\'jelsz2\').value != document.getElementById(\'reg_jelsz\').value){document.getElementById(\''.$ertek['name'].'\').className += \' alert\';alert2(\'A jelszavak nem egyeznek meg!\', \'\', \''.$hiba_txt.'\');return false;}';
                }

                if($ertek['name'] == 'isz' && $ertek['alert']){
                    $ret .= 'if(document.getElementById(\'isz\').value.length != 4){document.getElementById(\'isz\').className += \' alert\';alert2(\'Helytelen irányítószám!\', \'\', \''.$hiba_txt.'\'); return false;}';
                }

                if($ertek['name'] == 'sz_isz' && $ertek['alert']){
                    $ret .= 'if(document.getElementById(\''.$ertek['name'].'\').value.length != 4){document.getElementById(\''.$ertek['name'].'\').className += \' alert\';alert2(\'Helytelen irányítószám!\', \'\', \''.$hiba_txt.'\'); return false;}';
                }

                if($ertek['name'] == 'szall_isz' && $ertek['alert']){
                    $ret .= 'if(document.getElementById(\''.$ertek['name'].'\').value.length != 4){document.getElementById(\''.$ertek['name'].'\').className += \' alert\';alert2(\'Helytelen irányítószám!\', \'\', \''.$hiba_txt.'\'); return false;}';
                }

                // Adószám formátumának ellenőrzése (xxxxxxxx-x-xx alakú-e). Az inputra egyébként a main.js-ben található adoszam_formaz() függvényt hívd meg, de az alábbi ellenőrzéssel együtt lesz tökéletes
                if($ertek['name'] == 'ado'){
                    $ret .= nl2('
                        reszek = $(\'#'.$ertek['name'].'\').val().split(\'-\');
                        if( reszek[0].length!=8 || reszek[1].length!=1 || reszek[2].length!=2){
                            document.getElementById(\''.$ertek['name'].'\').className += \' alert\';
                            alert2(\'Az adószám formátuma helytelen!\', \'\', \''.$hiba_txt.'\');
                            return false;
                        }
                        ');
                }

                // Adószám formátumának ellenőrzése (xx-xx-xxxxxx alakú-e). Az inputra egyébként a main.js-ben található cegjegyzekszam_formaz() függvényt hívd meg, de az alábbi ellenőrzéssel együtt lesz tökéletes
                if($ertek['name'] == 'cegjegyzek'){
                    $ret .= nl2('
                        reszek = $(\'#'.$ertek['name'].'\').val().split(\'-\');
                        if( reszek[0].length!=2 || reszek[1].length!=2 || reszek[2].length!=2){
                            document.getElementById(\''.$ertek['name'].'\').className += \' alert\';
                            alert2(\'Az adószám formátuma helytelen!\', \'\', \''.$hiba_txt.'\');
                            return false;
                        }
                        ');
                }

                if($ertek['name'] == 'elf'){
                    $ret .= 'if(document.getElementById(\''.$ertek['name'].'\').checked==false){document.getElementById(\''.$ertek['name'].'\').className += \' alert\';alert2(\'A feltételek elfogadása kötelező!\',\'\', \''.$hiba_txt.'\'); return false;}';
                }

                if($ertek['name'] == 'akcar'){	//Ha van akciós ár, akkor figyelük azt is, hogy kissebb legyen
                                            //Először ki kell szűrni a pontokat belőle
                    $ret .= "var ar=document.getElementById('ar').value; var akcar=document.getElementById('akcar').value; mit=new RegExp('[.]','ig'); ar=ar.replace(mit,''); akcar=akcar.replace(mit,''); if(Number(ar)<=Number(akcar)){ document.getElementById('".$ertek['name']."').className += ' alert'; alert2('Az akciós ár nem kedvezőbb a listaártól!', '', ".$hiba_txt."'); return false; }";
                }

                $ret .= '}';
            }

            if($ajax && !strstr($ertek['type'], 'file')/* tehát nem jsfile vagy sima file... */ && $ertek['type']!='nosql' ){
                if($ertek['name']){// Ha van neve, nem HTML pl.
                    if($ertek['type']=='checkbox'){
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
                alert2(\'Kérjük válasszon ki egy cikknevet az értéklistából!\', \'\', \'Hiba\');
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
            $mod = 'true';
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
                            alert2(response.hiba, response.gomb);
                            return false;
                        }
                        if( response.siker ) {
                            szurkit();
                            bejelentes_mehet = true;
                            $(\'#szerk_form\').submit();
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
                alert2(\'Kérjük adja meg a szerepkört!\', \'\', \'Hiba\');
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
                if($(\'#szemelyzet_id\').val()==3){
                    if( !$(\'#szallito_id\').val() ){
                        alert2(\'Kérjük adja meg a külső kivitelezőt!\', \'\', \'Hiba\');
                        $(\'#szallito_id_autocomp\').addClass(\'hibas_ide_focus\');
                        return false;
                    }
                }
                /* Kötelező mezők hibaüzenetei */
                if($(\'#cikltip\').val()==\'d\'){
                    if( !$(\'#ciklnap\').val() && $(\'#hetkoz_kent\').prop(\'checked\')==false ){
                        alert2(\'Kérjük állítsa be a kívánt ütemezést!\', \'\', \'Hiba\');
                        $(\'#ciklnap\').addClass(\'hibas_ide_focus\');
                        return false;
                    }
                }
                if($(\'#cikltip\').val()==\'w\'){
                    if( !$(\'#ism_het\').val() || (!$(\'#h\').prop(\'checked\') && !$(\'#k\').prop(\'checked\') && !$(\'#s\').prop(\'checked\') && !$(\'#cs\').prop(\'checked\') && !$(\'#p\').prop(\'checked\') && !$(\'#sz\').prop(\'checked\') && !$(\'#v\').prop(\'checked\')) ){
                        alert2(\'Kérjük állítsa be a kívánt ütemezést!\', \'\', \'Hiba\');
                        $(\'#ism_het\').addClass(\'hibas_ide_focus\');
                        return false;
                    }
                }
                if($(\'#cikltip\').val()==\'m\'){
                    if( !$(\'#ciklho\').val() && !$(\'#ciklho2\').val() ){
                        alert2(\'Kérjük állítsa be a kívánt ütemezést!\', \'\', \'Hiba\');
                        $(\'#ciklho\').addClass(\'hibas_ide_focus\');
                        return false;
                    }
                }

                /* Havi ismétlődések esetei */
                if( $(\'#ciklho\').val()>0 && !$(\'#honapjan\').val() ){
                    alert2(\'Kérjük adja meg a hónap napját!\', \'\', \'Hiba\');
                    $(\'#honapjan\').addClass(\'alert\');
                    $(\'#honapjan\').addClass(\'hibas_ide_focus\');
                    return false;
                }
                if( $(\'#honapjan\').val() && !$(\'#ciklho\').val() ){
                    alert2(\'Kérjük adja meg a havi ismétlődés gyakoriságát!\', \'\', \'Hiba\');
                    $(\'#ciklho\').addClass(\'alert\');
                    $(\'#ciklho\').addClass(\'hibas_ide_focus\');
                    return false;
                }

                if( $(\'#ciklho2\').val()>0 && !$(\'#ciklhet\').val() ){
                    alert2(\'Kérjük adja meg a rendszerességet!\', \'\', \'Hiba\');
                    $(\'#ciklhet\').addClass(\'alert\');
                    $(\'#ciklhet\').addClass(\'hibas_ide_focus\');
                    return false;
                }
                if( $(\'#ciklho2\').val()>0 && !$(\'#ho_rendsz_het\').val() ){
                    alert2(\'Kérjük adja meg a rendszerességet!\', \'\', \'Hiba\');
                    $(\'#ho_rendsz_het\').addClass(\'alert\');
                    $(\'#ho_rendsz_het\').addClass(\'hibas_ide_focus\');
                    return false;
                }
                if( ( $(\'#ciklhet\').val()>0 || $(\'#ho_rendsz_het\').val() ) && !$(\'#ciklho2\').val() ){
                    alert2(\'Kérjük adja meg az ismétlődés gyakoriságát!\'+$(\'#ho_rendsz_het\').val(), \'\', \'Hiba\');
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

                        }
                    });
                    return false;
                }else{
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
    $konyvtarszerkezetesek = array(/*'eszkozkezeles.php', */'felmeresi_terv.php');
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
            url : \''.self().'?id='.$_GET['id'].'\',
            type: \'POST\',
            data: esamp2es(\''.$param.str_replace('.php', '', basename(self())).'lista=1&amp;szerkeszt=1\'+param_kieg+\'&amp;ajax_form_modosit=\' + new Date().getTime()),
            success: function(response) {
                if( response ) {
                    response = JSON.parse(response);
                    if( response.hiba ) {
                        if( response.gomb ) {
                            alert2(response.hiba, response.gomb);
                        } else {
                            alert2(response.hiba, \'\', \''.$hiba_txt.'\');
                        }

                        return false;
                    } else {
                        alert2();
                    }

                    '.$js_kieg.'

                    szamlaSorFrissit(\''.str_replace('.php', '', basename(self())).'\', \''.$_GET['id'].'\');
                    betoltott = szamlaBetolt( \''.$_GET['id'].'\', \''.basename(self()).'\', \''.$tab.'\', \'\', \'Elhagy kérdés nélkül\' );

                    '.$cash_flow_fulre_lepes.'

                    /* Ha lefutott az ajax, akkor még lehet dubni szeretnénk egy hibaüzenetet, vagy tájékoztatást */
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
        $ret .= nl2('; '.$ajax_kod.'
            ; return false; }
            ');
    } else {
        $ret .= '; formModositva = false; return true;	}';
    }

    $ret .= nl2('
            $(document).ready(function() {
                $(\'form input[type="text"], #szerk_form textarea\').keyup(function() {
                    /*$(\'#szerk_form input[type="submit"]\').addClass(\'gombb_alert\');*/
                });

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
    }else{
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
    }else{
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
    }else{
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
    }else{
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
                    }else{
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
	}else{

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
            }else{
                imagepng($src_img, $utvonal.$kepnev);
            }
        }

		return array($kepnev, '-1');
	}else{
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
function szamla_lista( $where, $tabla, $kijeloltek = false, $csak_sql_miatt = false, $getek = false ) {

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
    if(!$tabla){
        $tabla  = str_replace('.php', '', basename(self()));
    }

    // Dokumentumtárnál egyszerű a helyzet, nem kell számolni SQL-t
    if($tabla=='dokumentumtar'){
        return array(dokumentumtarLista(), '0');
    }

    $select = $_OPTS['select'];
    if($_GET['rend']){
        $order = $_GET['rend'];
    }else{
        $order = $_OPTS['rend'];
    }

    // Ha csak az SQL kell, hogy lássuk, mik a kapott ID-k, akkor a rendezéssel ne bajlódjunk
    if(!$csak_sql_miatt){
        // Előterjesztéseknél ha státusz szerint rendezünk, akkor azon belül iktatószám
        if(basename(self())=='eloterj_fb.php' || basename(self())=='eloterj_bod.php'){
            if($order=='stat_id'){
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
        if($_SESSION['elozo_rend']!=$_GET['rend']){
            $i = 0;
            foreach ($_OPTS['rendezes'] as $rendezes){
                if($rendezes == $_GET['rend']){
                    $_GET['irany'] = $_OPTS['iranyok'][$i];
                    $volt_irany_valtas = true;
                }
                $i++;
            }
        }

        // Jegyezzük meg, mi volt az előző rendezés szempont, hogy kétszer ne váltsunk irányt
        $_SESSION['elozo_rend'] = $order;

        $irany = $_GET['irany'];
        // Ha egy rendezésben vegyesen vannak az irányok, akkor erre az esetre letároljuk az ellenkezőjét egy másik GET-be
        if($_GET['irany']=='desc'){
            $_GET['ellen_irany'] = '';
        }else{
            $_GET['ellen_irany'] = 'desc';
        }
        // Aktív esetén a rendezés irányát meg kell fordítani (tehát az aktívak, egyesek legyenek elől)
        if($order=='aktiv'){
            if($irany=='desc'){
                $irany = 'asc';
            }else{
                $irany = 'desc';
            }
        }
        $order .= ' '.$irany;
    }

    // Ha nem egy konkrét recid-t keresünk, akkor állítsuk elő a where feltételt
    if(!$where){

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
        if($tabla == 'anyagigenyles'){
            $where = "`".get_db_tabla()."anyagigenyles`.`telephely_id` IN (".implode(',', $szallodak).") AND ";
        }

        // Bejelentéseknél szűrünk a törölve oszlop értékére is (logikailag törölt sorok nem látszanak)
        // Illetve csak a beloginolt user szállodáihoz tartozó bejegyzések látszanak
        if($tabla == 'bejelentes' || $tabla == 'bejelentes_arch'){
            $where = "`".get_db_tabla().$tabla."`.`telephely_id` IN (".implode(',', $szallodak).") AND ";
            // Ha csak a saját részleget láthat, akkor tovább szűrünk részlegre is
            $felh = $adatbazis_kapcsolat->s2('reszleg_id', 'login', "`nev`='{$_SESSION[get_db_tabla().'user']}'");
            if(_jog(JOG_BEJELENTES_SAJAT_RESZL) && !_jog(JOG_BEJELENTES_MEGTEKINT)){
                $where .= "`usr_reszleg_id`='{$felh['reszleg_id']}'";
            }
        }
        if($tabla == 'eseti_fenntartas'){
            $where = "`".get_db_tabla()."eseti_fenntartas`.`telephely_id` IN (".implode(',', $szallodak).") AND ";
        }
        if($tabla == 'eszkozkezeles'){
            $where = "`".get_db_tabla()."eszkozkezeles`.`telephely_id` IN (".implode(',', $szallodak).") AND ";
        }
        if($tabla == 'fenntartasi_terv'){
            $where = "`".get_db_tabla()."fenntartasi_terv`.`telephely_id` IN (".implode(',', $szallodak).") AND ";
        }
        if($tabla == 'utemezett_feladatok'){
            $where = "`".get_db_tabla()."utemezett_feladatok`.`telephely_id` IN (".implode(',', $szallodak).") AND ";
        }
        // A projektigénylés láthatóságának beálltásai
        if($tabla=='projektigenyles'){

            $szallodak_kieg = '';
            // Többszállodás esetén a két tobbsszállodás telephely_id-t felvesszük még
            if(_jog(JOG_PRIGENY_TOBB_MEGTEKINT)){
                $szallodak_kieg = ', 1011, 4301';
            }

            // Számít a saját szállodai látókör
            $where = "`".get_db_tabla()."projektigenyles`.`telephely_id` IN (".implode(',', $szallodak).$szallodak_kieg.") AND ";

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
            if(_jog(JOG_PRIGENY_TOBB_MEGTEKINT)){
                $where_jogok .= "`egy_tobb`='1' OR ";
            }
            if($where_jogok){
                $where .= '('.csonkit($where_jogok, 4).') AND ';
            }

            // Létrehozás alatti szállodát nem láthat senki, kivéve aki dolgozik rajta
            $where .= " (`stat_id`!='".PRIGENY_STAT_LETRE_ALATT."' OR `loginnev`='{$_SESSION[get_db_tabla().'user']}')";

            // Viszont ahol véleményező vagyok, azokat az igényléseket mindenképp lássam
            $lathato_prigenyek = $adatbazis_kapcsolat->s('projektigenyles_recid', 'projektigenyles_kapcs', "`login_recid`='{$_SESSION[get_db_tabla().'login_recid']}'");
            $lathato_prigenyek = adatbazis2tomb($lathato_prigenyek, 'projektigenyles_recid');
            if($lathato_prigenyek){
                $where .= " OR `".  get_db_tabla()."projektigenyles`.`recid` IN (".implode(',', $lathato_prigenyek).") AND ";
            }

        }

        if($tabla == 'rendszeres_karbantartas'){
            $where = "`".get_db_tabla()."rendszeres_karbantartas`.`telephely_id` IN (".implode(',', $szallodak).") AND ";
        }

        if($tabla == 'munkalap' || $tabla == 'munkalap_arch'){

            // Ki-ki csak a saját szállodáit láthatja, ez alap
            $where = "`".get_db_tabla().$tabla."`.`telephely_id` IN (".implode(',', $szallodak).") AND ";

            // Ha mobilcsopotos felhasználó vagyok csak azokat a munkalapokat látom, amik mobilhoz vannak rendelve és a hozzárendelt személy üres vagy én vagyok.
            if( $_SESSION[get_db_tabla().'szemelyzet_id']==MOBILCSOPORT ){
                $login = $adatbazis_kapcsolat->s2('mobil', 'login', "`recid`='{$_SESSION[get_db_tabla().'login_recid']}'");
                if($login['mobil']){
                    $where .= "`".get_db_tabla().$tabla."`.`szemelyzet_id`='{$_SESSION[get_db_tabla().'szemelyzet_id']}' AND (`mobil_loginnev`='' OR `mobil_loginnev`='{$_SESSION[get_db_tabla().'user']}') AND `mobil`='{$login['mobil']}' AND ";
                // Ha nincs mobilcsoportom, akkor nem látok munkalapokat
                }else{
                    $where .= ' FALSE AND ';
                }

            // Ha nem mobilcsoport vagyok, akkor még a saját szállodák jogra kell figyelni:
            }else{

                // Saját szem. tip. megtekintése: saját szállodák + hozzárendelt személyzet: tehát tovább szűrünk hozzárendelt személyzetre, amit a felhasználókon, majd személyezeten át kapunk meg
                if(_jog(JOG_MUNKALAP_MEGTEKINT)){
                    // Hozzárendelt személyzet megállapítása. Ez egyenlő a Személyzet beosztásával.
                    $where .= "`".get_db_tabla().$tabla."`.`szemelyzet_id`='{$_SESSION[get_db_tabla().'szemelyzet_id']}' AND ";
                    if(!$_SESSION[get_db_tabla().'szemelyzet_id']){
                        $where .= ' FALSE AND ';
                    }
                }
            }

            // Azaz ha saját szállodáim/saját feladataim jogom van, akkor az egy lazább feltétel, nem szűrünk hozzárendelt személyzetre
            if( _jog(JOG_MUNKALAP_MEGTEKINT_SZALL) ){
                $where .= '';
            }

            // Ha saját feladatok a láthatósági kör, akkor az SQL feltétel azon múlik, hogy milyen jogai vannak a felhasználónak
            if( _jog(JOG_MUNKALAP_MEGTEKINT_FELADAT) ){
                $statuszra_szures = array();
                if( _jog(JOG_MUNKALAP_IGAZOLAS) ){
                    array_push($statuszra_szures, MUNKALAP_STAT_IGAZOLASRA_VAR);
                }
                if( _jog(JOG_MUNKALAP_VIZSGALAT) ){
                    array_push($statuszra_szures, MUNKALAP_STAT_VIZSGALAT_ALATT);
                }
                if( _jog(JOG_MUNKALAP_KULSOS_ELOKESZIT) ){
                    array_push($statuszra_szures, MUNKALAP_STAT_ELOKESZITES_ALATT);
                }
                if( _jog(JOG_MUNKALAP_KULSOS_DONTES) ){
                    array_push($statuszra_szures, MUNKALAP_STAT_DONTESRE_VAR);
                }
                if( _jog(JOG_MUNKALAP_KULSOS_MEGREND) ){
                    array_push($statuszra_szures, MUNKALAP_STAT_HOZREND_MEGREND_VAR);
                    array_push($statuszra_szures, MUNKALAP_STAT_AJANLAT_NYOMTATVA);
                }

                // Szűrés azokra a státuszokra, amiket láthat
                if($statuszra_szures){
                    $where .= "`".get_db_tabla().$tabla."`.`stat_id` IN (".implode(',', $statuszra_szures).") AND ";
                // Ha egyiket sem láthatja, akkor legyen üres a lista
                }else{
                    $where .= ' FALSE AND ';
                }
            }

        }

        // Keresés
        if( $_GET['ksz'] ) {

            // Ki kell szűrni az " ... as " részeket.
            $select_feldogloz = nl2($_OPTS['select']);
            if(strpos($select_feldogloz, '` as `')!==false){
                $as_resz = strpos($select_feldogloz, '` as `');

                $select_tmp = substr($select_feldogloz, 1, $as_resz);
                // Maradékból vágjuk ki az as `ami ide van írva` részt. 4. ` karaterig vágunk
                $maradek    = substr($select_feldogloz, $as_resz);
                for($i=0; $i<4; $i++){
                    $maradek = substr($maradek, 0, strpos('`'));
                }
                $select_feldogloz = $select_tmp.$maradek;
            }

            $selectek = explode(' ', $select_feldogloz);
            $ksz_where = '';
            foreach($selectek as $select_tmp) {
                if( $select_tmp && $select_tmp != 'recid' ) {
                    if(strpos($select_tmp, '`')!==false){
                        $ksz_where .= trim($select_tmp, ',')." LIKE '%{$_GET['ksz']}%' OR ";

                        // Ékezet sensitive verzió: $ksz_where .= trim($select_tmp, ',')." LIKE '%{$_GET['ksz']}%' COLLATE utf8_bin OR ";
                    }else{
                        $ksz_where .= "`{$select_tmp}` LIKE '%{$_GET['ksz']}%' OR ";
                        // Ékezet sensitive verzió: $ksz_where .= "`{$select_tmp}` LIKE '%{$_GET['ksz']}%' COLLATE utf8_bin OR ";
                    }
                }
            }

            if($ksz_where){
                $where .= '('.trim($ksz_where, ' OR ').') AND ';
            }

        }

        // Szűrés lekezelése
        if ($_GET['mezo'] && $_GET['op']) {
            // Az eddigi Where-t egy nagy zárójelbe tesszük és ehhez vesszük hozzá az új feltételeket
            if($where){
                $where = '('.csonkit($where, 5).') AND ';
            }

            // Dátum mezők (sajnos itt class-ra már nem tudok keresni, szóval konkrét mezőnevek alapján dől el)
            // nél ha egyenlős akor az valóban like
            $datum_mezok = array('insert_dt');
            if( in_array($_GET['mezo'], $datum_mezok) && $_GET['op']=='equal' ){
                $_GET['op'] = 'like';
            }

            $where .= get_szures_where_kieg($_GET['mezo'], $_GET['op'], $_GET['felt'], $tabla).' AND ';

        }

        // Ha van öszetettt szűrés feltétel
        if($_SESSION['o_szuro_feltetel']){
            // Az eddigi Where-t egy nagy zárójelbe tesszük és ehhez vesszük hozzá az új feltételeket
            if($where){
                $where = '('.csonkit($where, 5).') AND ';
            }
            $where .= get_o_szures_where_kieg($_SESSION['o_szuro_feltetel'], $tabla).' AND ';
        }

        // Ha van $_GET['idk'] változónk, akkor azokra az ID-kra még pluszban szűrünk
        if ($_GET['recidk']) {
            $where .= '`'.get_db_tabla().$tabla.'`.`recid` IN ('.$_GET['recidk'].') AND ';
        }

        // Anyagigénylés/anyagrendelés lekezelése
        if($tabla=='anyagigenyles'){
            // Jóváhagyva státuszig anyagigénylés, afelett rendelés
            if (isset($_GET['anyagrend'])) {
                $where .= "`".get_db_tabla()."anyagigenyles`.`stat_id`>".CIKKIGN_STAT_JOVAHAGYVA;
            }else{
                $where .= "`".get_db_tabla()."anyagigenyles`.`stat_id`<=".CIKKIGN_STAT_JOVAHAGYVA;
            }
        }

        // Where feltétel lezárása
        $where = trim($where, ' AND ');

    // Ha van megadva where feltétel, akkor az a recid=x, tehát frissítünk
    }else{
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

    // Telephely táblával JOIN-olva, ha telephely_id alapján rendezünk vagy a selectben szerepel ez az oszlop
    $bejegyzesek = array();
    if(strpos($order, 'telephely_id')!==false || strpos($select, 'telephely_id')!==false){

        // Először is az ordert felülírjuk, ha eszerint akartunk rendezni
        if(strpos($order, 'telephely_id')!==false){
            $order = '`'.get_db_tabla().'telephely`.`nev` '.$_GET['irany'];
        }

        // Rendezés anyagigényléskor Anyagrend mod: 2018.04
        if($tabla=='anyagigenyles' /*&& !isset($_GET['anyagrend'])*/){

            // Táblázatos rendezés miatt itt másképp kell a rendezést megoldani
            // Alapértelmezett irány beállítása
            if(!$_SESSION['anyagigenyles_tablazat_rendez']){
                $_SESSION['anyagigenyles_tablazat_rendez'] = 'anyag_tabla_ikt_szam';
                $_SESSION['anyagigenyles_tablazat_irany']  = 'desc';
            }
            switch($_SESSION['anyagigenyles_tablazat_rendez']){
                case 'anyag_tabla_ikt_szam'     : $order = '`'.get_db_tabla().'anyagigenyles`.`ikt_szam`';  break;
                case 'anyag_tabla_szalloda'     : $order = '`'.get_db_tabla().'telephely`.`nev`';           break;
                case 'anyag_tabla_cikkszam'     : $order = '`'.get_db_tabla().'anyagigenyles`.`cikk_id`';   break;
                case 'anyag_tabla_cikknev'      : $order = '`'.get_db_tabla().'anyagigenyles`.`nev`';       break;
                case 'anyag_tabla_szallito'     : $order = '`'.get_db_tabla().'szallito`.`nev`';            break;
                case 'anyag_tabla_ig_menny'     : $order = '`'.get_db_tabla().'anyagigenyles`.`igmen`';     break;
                case 'anyag_tabla_mee'          : $order = '`'.get_db_tabla().'anyagigenyles`.`cikkmee`';   break;
                case 'anyag_tabla_ig_datum'     : $order = '`'.get_db_tabla().'anyagigenyles`.`insert_dt`'; break;
                case 'anyag_tabla_jovah_menny'  : $order = '`'.get_db_tabla().'anyagigenyles`.`jhmen`';     break;
                case 'anyag_tabla_rend_menny'   : $order = '`'.get_db_tabla().'anyagigenyles`.`rndmen`';    break;
                case 'anyag_tabla_rend_egysegar': $order = '`'.get_db_tabla().'anyagigenyles`.`rndear`';    break;
                case 'anyag_tabla_penznem'      : $order = '`'.get_db_tabla().'erteklista`.`info`';         break;
                case 'anyag_tabla_dev_rend'     : $order = '`'.get_db_tabla().'anyagigenyles`.`vrndear`';   break;
                case 'anyag_tabla_dev_beerk'    : $order = '`'.get_db_tabla().'anyagigenyles`.`erkear`';    break;
                case 'anyag_tabla_statusz'      : $order = '`'.get_db_tabla().'anyagigenyles`.`stat_id`';   break;
                case 'anyag_tabla_rndid'        : $order = '`'.get_db_tabla().'anyagigenyles`.`rndid`';     break;
                case 'anyag_tabla_rnd_dt'       : $order = '`'.get_db_tabla().'anyagigenyles`.`rnd_dt`';    break;
                case 'anyag_tabla_rndertek'     : $order = '`'.get_db_tabla().'cikkrendfej`.`rndertek`';    break;
            }
            $order .= ' '.$_SESSION['anyagigenyles_tablazat_irany'];
        }

        // Rendezés anyagrendeléskor
        if($tabla=='anyagigenyles' && isset($_GET['anyagrend'])){
            if($_GET['rend']=='stat_id'){
                $order = '`'.get_db_tabla().'anyagigenyles`.`stat_id` '.$_GET['irany'].', `'.get_db_tabla().'anyagigenyles`.`rndid` '.$_GET['ellen_irany'].'';
            }
            if($_GET['rend']=='rnd_dt'){
                $order = '`'.get_db_tabla().'anyagigenyles`.`rnd_dt` '.$_GET['irany'].', `'.get_db_tabla().'anyagigenyles`.`rndid` '.$_GET['irany'].'';
            }
        }

        // Rendezés beosztásnál
        if($tabla=='beosztas'){
            // Ha iktatószám szerint rendezünk, akkor ez lesz a következő rendezési szempont, és az irány is erre vonatkozik
            if($_GET['rend']=='telephely_id'){
                $order = '`'.get_db_tabla().'telephely`.`nev` '.$_GET['irany'].', `'.get_db_tabla().'beosztas`.`tol`';
            }
        }

        // Bejelentéseknél a rendezés külön problémakör:
        if($tabla=='bejelentes' || $tabla=='bejelentes_arch'){

            $order = '';

            $irany_ikt_szam  = '';
            $irany_telephely = '';

            if($_GET['rend']=='stat_id'){

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
                $statuszok = array_reverse( array(BEJELENTES_STAT_FOLYAMATBAN, BEJELENTES_STAT_KESZ_IG, BEJELENTES_STAT_VIZSG_ALATT, BEJELENTES_STAT_VARAKOZO, BEJELENTES_STAT_ELUTASITVA, BEJELENTES_STAT_LEZART) );
                foreach($statuszok as $stat){
                    $bejelentesek = $adatbazis_kapcsolat->s('recid', $tabla, "`stat_id`='$stat'", '`surgos`, `ikt_szam`');
                    foreach($bejelentesek as $bejelentes){
                        $adatbazis_kapcsolat->u($tabla, array('sor'), array($i), "`recid`='{$bejelentes['recid']}'");
                        $i++;
                        array_push($sorrend_idk, $bejelentes['recid']);
                    }
                }

                // Nyitott státuszú, azon belül sürgősek majd iktatószám
                $bejelentesek = $adatbazis_kapcsolat->s('recid', $tabla, "`stat_id`='".BEJELENTES_STAT_NYITOTT."'", '`surgos`, `ikt_szam`');
                foreach($bejelentesek as $bejelentes){
                    $adatbazis_kapcsolat->u($tabla, array('sor'), array($i), "`recid`='{$bejelentes['recid']}'");
                    $i++;
                    array_push($sorrend_idk, $bejelentes['recid']);
                }

                // Fordított sorrendben a recid-kat is megfordítjuk
                if($_GET['irany']=='desc'){
                    $sorrend_idk = array_reverse($sorrend_idk);
                }

                // $order .= 'FIELD(`'.get_db_tabla().'bejelentes`.`recid`, \''.implode('\', \'', $sorrend_idk).'\') '.$_GET['irany'];

            // Ha telephely szerint rendezünk, akkor pedig ezt a szempontot kell előre venni, és az irány is erre vonatkozik
            }elseif($_GET['rend']=='telephely_id'){
                $order .= '`'.get_db_tabla().'telephely`.`nev` '.$_GET['irany'].', `'.get_db_tabla().$tabla.'`.`ikt_szam`';

            // Helyiség név szerinti rendezés
            }elseif($_GET['rend']=='obj_txt'){
                $order .= '`'.get_db_tabla().$tabla.'`.`obj_txt` '.$_GET['irany'].', `'.get_db_tabla().'telephely`.`nev` '.$_GET['irany'];

            // Alapértelmezett rendezettség a sürgős, azon belül telephely
            }elseif($_GET['rend']=='ikt_szam'){
                $order .= '`'.get_db_tabla().$tabla.'`.`ikt_szam` '.$_GET['irany'].', `'.get_db_tabla().'telephely`.`nev`';
            }

        }

        // Rendezés munkalap esetén külön problémakör:
        if( $tabla=='munkalap' || $tabla=='munkalap_arch' ){

            $order = '';
            // Elsődleges rendezés a csoport, ettől lesznek a csoportok is sorban (elől az önálló bejelentések). De ezt csak Diszpécsereknél renezzük így, mert csak náluk jelenik meg a csoportosítás

            // Már nincs csoportosítás
//            if(_jog(JOG_MUNKALAP_KESZITHET)){
//                $order .= '`'.get_db_tabla().'munkalap_csoport`.`nev`, '; // <= itt fixen munkalap kell, nem a $tabla
//            }

            $irany_ikt_szam  = '';
            $irany_telephely = '';

            // Ha státusz szerint rendezünk
            if($_GET['rend']=='stat_id'){

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
                $statuszok = array_reverse( array( MUNKALAP_STAT_BEOSZTAS_NELKUL, MUNKALAP_STAT_HOZZARENDELT, MUNKALAP_STAT_FELVEVE, MUNKALAP_STAT_ANYAGRAVAR, MUNKALAP_STAT_IGAZOLASRA_VAR, MUNKALAP_STAT_IGAZOLT, MUNKALAP_STAT_VIZSGALAT_ALATT, MUNKALAP_STAT_ELOKESZITES_ALATT, MUNKALAP_STAT_DONTESRE_VAR, MUNKALAP_STAT_AJANLAT_NYOMTATVA, MUNKALAP_STAT_HOZREND_MEGREND_VAR, MUNKALAP_STAT_HATIDO_UTEMEZETT, MUNKALAP_STAT_VARAKOZO, MUNKALAP_STAT_LEZARASRA_VAR, MUNKALAP_STAT_LEZART, MUNKALAP_STAT_ELUTASITVA, MUNKALAP_STAT_TOROLT, MUNKALAP_STAT_SZTORNO ) );
                foreach($statuszok as $stat){
                    $munkalapok = $adatbazis_kapcsolat->s('recid', $tabla, "`stat_id`='$stat'", '`surgos`, `ikt_szam`');
                    foreach($munkalapok as $munkalap){
//                        $adatbazis_kapcsolat->u($tabla, array('sor'), array($i), "`recid`='{$munkalap['recid']}'");
//                        $i++;
                        array_push($sorrend_idk, $munkalap['recid']);
                    }
                }

                // Nyitott státuszú, azon belül sürgősek majd iktatószám
                $munkalapok = $adatbazis_kapcsolat->s('recid', $tabla, "`stat_id`='".MUNKALAP_STAT_NYITOTT."'", '`surgos`, `ikt_szam`');
                foreach($munkalapok as $munkalap){
//                    $adatbazis_kapcsolat->u($tabla, array('sor'), array($i), "`recid`='{$munkalap['recid']}'");
//                    $i++;
                    array_push($sorrend_idk, $munkalap['recid']);
                }

                // Fordított sorrendben a recid-kat is megfordítjuk
                if($_GET['irany']=='desc'){
                    $sorrend_idk = array_reverse($sorrend_idk);
                }

                //$order .= '`'.get_db_tabla().$tabla.'`.`sor` '.$_GET['irany'].'';
                // $order .= 'FIELD(`'.get_db_tabla().'munkalap`.`recid`, \''.implode('\', \'', $sorrend_idk).'\') '.$_GET['irany'];

            // Ha iktatószám szerint rendezünk, akkor ez lesz a következő rendezési szempont, és az irány is erre vonatkozik
            }elseif($_GET['rend']=='ikt_szam'){

                $order .= '`'.get_db_tabla().$tabla.'`.`ikt_szam` '.$_GET['irany'].', `'.get_db_tabla().'telephely`.`nev`';

            // Helyiség név szerinti rendezés
            }elseif($_GET['rend']=='obj_id'){
                $order .= '`'.get_db_tabla().$tabla.'`.`obj_txt` '.$_GET['irany'].', `'.get_db_tabla().'telephely`.`nev` '.$_GET['irany'].'';

            // Ha telephely szerint rendezünk, akkor pedig ezt a szempontot kell előre venni, és az irány is erre vonatkozik
            }elseif($_GET['rend']=='telephely_id'){
                $order .= '`'.get_db_tabla().'telephely`.`nev` '.$_GET['irany'].', `'.get_db_tabla().$tabla.'`.`ikt_szam`';
            }

            $order = trim($order, ', ');

        }

        // Archiv menüpont alatt egy 100-as limit mindig van (ha nincs más szűrés, akkor a feltétel a lista hossza)
        if( $_GET['mezo'] == 'legfrissebb' ) {
            $limit = $_GET['felt'];
            if($order){
                $order .= ', ';
            }
            $order .= '`'.get_db_tabla().$tabla.'`.`insert_dt` DESC';
        }
        $osszetett_legfrissebb = strpos($_SESSION['o_szuro_feltetel'], 'me|legfrissebb#op|equal#fe|');
        if($osszetett_legfrissebb!==false){
            // Keressük meg a "legfrissebb" szűrő feltétel vége pozíciót
            $vege  = strpos($_SESSION['o_szuro_feltetel'], '#es', $osszetett_legfrissebb+27);

            // A feltétel vége pozíciójából kivonva a kezdőpozíciója + a 27 lesz a limit amit keresünk => "me|legfrissebb#op|equal#fe|NA ENNEK A SZÁMNAK A HOSSZA#es"
            // Ezt a limit hosszt vágjuk ki a $_SESSION['o_szuro_feltetel'] stringből.
            $limit = substr($_SESSION['o_szuro_feltetel'], $osszetett_legfrissebb+27, $vege-($osszetett_legfrissebb+27) );
            if($order){
                $order .= ', ';
            }
            $order .= '`'.get_db_tabla().$tabla.'`.`insert_dt` DESC';
        }

        // Rendezés Ütemezett feladatoknál
        if( $tabla=='utemezett_feladatok' ){
            // Ha aktív szerint rendezünk, akkor azon belül esedékesség dátuma csökkenő + szálloda csökkenő
            if($_GET['rend']=='aktiv'){
                $order = '`aktiv` '.$_GET['irany'].', `esnap_d` '.$_GET['irany'].', `'.get_db_tabla().'telephely`.`nev` '.$_GET['irany'];
            }
            // Ha szálloda szerint rendezünk, akkor azon belül esedékesség dátuma csökkenő + szálloda csökkenő
            if($_GET['rend']=='telephely_id'){
                $order = '`'.get_db_tabla().'telephely`.`nev` '.$_GET['irany'].', `aktiv` '.$_GET['ellen_irany'].', `esnap_d` '.$_GET['ellen_irany'];
            }
            // Ha szálloda szerint rendezünk, akkor azon belül esedékesség dátuma csökkenő + szálloda csökkenő
            if($_GET['rend']=='esnap_d'){
                $order = '`esnap_d` '.$_GET['irany'].', `aktiv` '.$_GET['irany'].', `'.get_db_tabla().'telephely`.`nev` '.$_GET['ellen_irany'];
            }
        }

        // Ez azért kell, hogy az SQL stringünk meglegyen. A WHERE szót le fogjuk még cserélni JOIN-okkal
        $adatbazis_kapcsolat->s($select, $tabla, $where, $order, $limit, false, false, false, 'csak az SQL string-je kell');
        $sql = $adatbazis_kapcsolat->gp();
        $mit = 'WHERE';

        // Anyagigényléskor a szállítót vesszük még fel
        $sql_kieg = '';
        if($tabla=='anyagigenyles'){
            $sql_kieg .= nl2('
                                LEFT JOIN `'.get_db_tabla().'szallito` ON (`'.get_db_tabla().'anyagigenyles`.`szallito_id`=`'.get_db_tabla().'szallito`.`id`)
                                LEFT JOIN `'.get_db_tabla().'erteklista` ON (`'.get_db_tabla().'anyagigenyles`.`kcs_21`=`'.get_db_tabla().'erteklista`.`id` AND `'.get_db_tabla().'erteklista`.`csoport`=\''.ERTEKLISTA_PENZNEM.'\')
                                LEFT JOIN `'.get_db_tabla().'cikkrendfej` ON (`'.get_db_tabla().'anyagigenyles`.`rndid`=`'.get_db_tabla().'cikkrendfej`.`rndid`)
                            ');
        }

        $arch = '';
        if($tabla=='bejelentes_arch' || $tabla=='munkalap_arch'){
            $arch = '_arch';
        }
        // Bejelentésnél és csoportoknál még az értéklistát és csoportot is felvesszük
        if($tabla=='bejelentes' || $tabla=='bejelentes_arch'){
            // Státusz szerinti rendezésnél nem JOIN-oljuk a telephelyeket, egyébként mindig
            if($_GET['rend']!='stat_id'){
                $sql_kieg .= ' JOIN `'.get_db_tabla().'telephely` ON `'.get_db_tabla().$tabla.'`.`telephely_id`=`'.get_db_tabla().'telephely`.`id`';
            }
        }
        // Már nem használunk csoportot, de az SQL-el pedig csínján kell bánni, ezért kivéve 2018.05.28
        if($tabla=='munkalap' || $tabla=='munkalap_arch'){
            // Státusz szerinti rendezésnél nem JOIN-oljuk a telephelyeket, egyébként mindig
            if($_GET['rend']!='stat_id'){
                $sql_kieg .= ' JOIN `'.get_db_tabla().'telephely` ON `'.get_db_tabla().$tabla.'`.`telephely_id`=`'.get_db_tabla().'telephely`.`id`';
            }
        }

        // Szűréskor megjegyzés JOIN
        if ($_GET['mezo']=='megjegyzes') {
            $sql_kieg .= ' JOIN `'.get_db_tabla().'megjegyzes` ON `'.get_db_tabla().'megjegyzes`.`tabla`=\''.$tabla.'\' AND `'.get_db_tabla().''.$tabla.'`.`recid`=`'.get_db_tabla().'megjegyzes`.`id`';
        }

        // A bejelentések és munkalapoknál már megvan oldva, hogy ne használjunk obj JOIN-t, de a többi tálbára még nincs megoldva
        $kivetelek = array('bejelentes', 'bejelentes_arch', 'munkalap', 'munkalap_arch');
        if( (strpos($select, 'obj_id')!==false && !in_array($tabla, $kivetelek)) ){
            $sql_kieg .= ' LEFT JOIN `'.get_db_tabla().'obj` ON `'.get_db_tabla().$tabla.'`.`obj_id`=`'.get_db_tabla().'obj`.`recid` ';
        }
        // Sőt a Telephely JOIN sincs még megoldva
        if(!in_array($tabla, $kivetelek)){
            $sql_kieg .= ' JOIN `'.get_db_tabla().'telephely` ON `'.get_db_tabla().$tabla.'`.`telephely_id`=`'.get_db_tabla().'telephely`.`id`';
        }

        // Ha az SQL-ben nem volna WHERE feltétel, akkor az ORDER BY elé kell beszúrni a JOIN-t
        $mivel = $sql_kieg.' WHERE';
        if(strpos($sql, 'WHERE')===false){
            $mit   = 'ORDER BY';
            $mivel = str_replace('WHERE', 'ORDER BY', $mivel);
        }
        $sql = str_replace($mit, $mivel, $sql);

        $sorok = mysqli_query($db_kapcs, $sql);
        while($sor = mysqli_fetch_assoc($sorok)){
            array_push($bejegyzesek, $sor);
        }

    // Értéklistát csoport szerinti sorrendben, azon belül pedig infó szerinti sorrendben kell kilistázni
    }elseif($tabla=='erteklista'){

        // Gyorskeresőnél képződhet where feltétel
        if($where){
            $where = ' AND '.$where;
        }

        $sql = 'SELECT
                        `'.get_db_tabla().'erteklista`.`recid`,
                        `'.get_db_tabla().'erteklista`.`csoport`,
                        `'.get_db_tabla().'erteklista`.`info`,
                        `'.get_db_tabla().'erteklista_csoport`.`nev`
                FROM    `'.get_db_tabla().'erteklista`
                JOIN    `'.get_db_tabla().'erteklista_csoport` ON `'.get_db_tabla().'erteklista`.`csoport`=`'.get_db_tabla().'erteklista_csoport`.`csoport`
                WHERE   `'.get_db_tabla().'erteklista`.`torolve`=0'.$where.'
                ORDER BY `'.get_db_tabla().'erteklista_csoport`.`nev`, `'.get_db_tabla().'erteklista`.`info`
                ';

        $sorok = mysqli_query($db_kapcs, $sql);
        while($sor = mysqli_fetch_assoc($sorok)){
            array_push($bejegyzesek, $sor);
        }

    }else{

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

        // Ha csak egy konkrét rekordot frissítünk a bal navpanelben, akkor a limit 1 legyen. Join esetén többször ugyanaz is kijöhet, ezért lényeges limitálni.
        if( isset($_GET['szamlaSorFrissit']) ){
            $limit = 1;
        }

        $bejegyzesek = $adatbazis_kapcsolat->s($select, $tabla, $where, $order, $limit, '', '', $join);
        $sql = $adatbazis_kapcsolat->gp();

    }

    // Ha csak a darabszámra vagyunk kíváncsiak, akkor ezt írjuk ki, amit az admin.js f_szures_submit-nál kezelünk le
    if (isset($_GET['sql_db'])) {
        echo count($bejegyzesek);
        $weblap -> leallit();
        exit;
    }

    // Weblap.php elején vizsgáljuk, hogy a kért ID-t egyáltlán láthatjuk-e vagy sem. Ekkor elég ha kapunk egy SQL-t
    if($csak_sql_miatt){
        return $sql;
    }
    // Excel exporthoz (lehet Nyomtatáshoz, PDF-hez is kell majd?)
    if(!isset($_GET['szamlaSorFrissit']) && $tabla!='esemenyek'){
        $_SESSION['akt_sql'] = $sql;
    }

if(isset($_GET['debug'])) {
    echo $_SESSION['akt_sql'] . '<br>';
}

    if( $bejegyzesek ) {
        switch($tabla) {
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
            case 'projektkezeles':
                $html = projektkezelesLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
            break;
            case 'projektigenyles':
                $html = projektigenylesLista( $bejegyzesek, $tabla, $frissit, $kijeloltek );
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
        }else{

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
            if( $bsz['stat_id'] == BEJELENTES_STAT_NYITOTT ){
                $bold    = ' class="b"';
                $active .= ' nyitott_statuszu'; // Innen fogjuk tudni, hogy a csoporosítás gombokat mutassuk-e meg vagy sem
            }

            // Egyszálloda esetén, ha nyitott a státusz, van munkalap készíthet jog, és ponstosan egy ML-ja van, és a ML-nak két státusza van (ez csak Nyitott és hozzárendelt lehet), akkor legyen szürke a háttér
            $rejtsuk = false;
            if( _jog(JOG_MUNKALAP_KESZITHET) && $bsz['stat_id']==BEJELENTES_STAT_NYITOTT){
                $szalloda  = $adatbazis_kapcsolat->s2('modell', 'telephely', "`id`='{$bsz['telephely_id']}'");
                if(!$szalloda['modell']){
                    $van_kapcs = $adatbazis_kapcsolat->s('munkalap_id', 'munkalap_kapcs', "`bejelentes_id`='{$bsz['recid']}'");
                    if( count($van_kapcs)==1 ){
                        $letezik_munkalap = $adatbazis_kapcsolat->s2('recid', 'munkalap', "`recid`='{$van_kapcs[0]['munkalap_id']}'");
                        if($letezik_munkalap){
                            $statuszok = $adatbazis_kapcsolat->s('recid', 'munkalap_statusz', "`munkalap_recid`='{$letezik_munkalap['recid']}'");
                            if(count($statuszok)==2){
                                $active .= ' szurke_hat';
                                $rejtsuk = true;
                            }
                        }
                    }
                }
            }

            // Korábban szürke színnel voltak kiírva azok a nyitott bejelentések, amikhez tartozott munkalap, de ezeket végül teljesen elrejtjük.
            if($rejtsuk){
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
                                <td>'.$szalloda_neve.$bsz['obj_txt'].'</td>
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
        if(!$tabla){
            $nohover = ' nohover';
        }

        if( !$frissit ) {

            $stat_select = '';
            $statuszok_abc = array();
            $statuszok = $adatbazis_kapcsolat->s('distinct stat_id', $tabla, "`stat_id`!=''");
            $statuszok = adatbazis2tomb($statuszok, 'stat_id');
            foreach($statuszok as $stat){
                $text = getStatText($tabla, $stat);
                if($text){
                    array_push($statuszok_abc, array('id' => $stat, 'nev' => $text));
                }
            }
            $statuszok_abc = tobbdim_tomb_kulcs_rendez($statuszok_abc, 'nev');
            foreach($statuszok_abc as $stat){
                $selected = '';
                if($_GET['mezo']=='stat_id' && $_GET['felt']==$stat['id']){
                    $selected = ' selected';
                }
                $stat_select .= '<option'.$selected.' value="'.$stat['id'].'">'.$stat['nev'].'</option>';
            }
            if($stat_select){
                $stat_select = '<div id="stat_gyors_szuro_div"><select id="stat_gyors_szuro" style="margin: 0.2rem 0 0 0.2rem;" onchange="if(this.value){ param = \'&amp;mezo=stat_id&amp;op=equal&amp;felt=\'+this.value; }else{ param = \'\'; } szurkit(); location.href=\''.self().get_get(array('mezo', 'op', 'felt')).'\'+param"><option value="">-Összes státusz-</option>'.$stat_select.'</select></div>';
            }
            $html = $stat_select.'<ul id="szamlaList" class="szamla_cont nobehuzas rendszer'.$jClass.'" style="margin-top: 3.2rem; backgorund: #F4F6F5">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
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



            $surgos = '';
            if( $bsz['surgos'] ) {
                $surgos = nl2('
                    <span class="icon piros">
                        <i class="material-icons">error</i>
                    </span>
                ');
            }

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
            if($bsz['szallito_id']){
                $szemelyzet = getErtek('nev', 'szallito', 'id', $bsz['szallito_id']);
            }elseif($bsz['szemelyzet_id']){

                // Ha a személyzettípus külső kivitelező, akkor a külső cég neve jelenjen meg
//                if($bsz['szemelyzet_id']==KULSO_KIVITELEZO){
//                    $szemelyzet = getErtek('nev', 'szallito', 'id', $bsz['szallito_id']);
//                }else{
                    foreach($select_szemelyzet as $szemely){
                        if($szemely['value']==$bsz['szemelyzet_id']){
                            $szemelyzet = $szemely['name'];
                        }
                    }
//                }

            }

            // Mobilcsoport esetén látszódjon a betűjel is
            if($bsz['szemelyzet_id']==MOBILCSOPORT){
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
                }else{
                    $hiba_rovid = $bsz['hiba_txt'];
                }
                $ml_tipus = '<span title="'.$bsz['hiba_txt'].'">'.$hiba_rovid.'</span>';
            }else{
                // Munkalap tipus szövegén rövidítsünk
                $ml_tipus = getErtekLista(ERTEKLISTA_MUNKALAP_TIP, $bsz['tipus_id']);
                $ml_tipus = str_replace(array('Ütemezett feladat',  'Eseti fenntartás', 'Rendszeres kbt.', 'Üzemeltetési feladat'),
                                        array('Ütemezett',          'Eseti',            'Rendsz.Karb.',    'Üzemeltetés'         ),
                                        $ml_tipus);
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

            $li_tart = '<a class="szamla'.$nohover.'" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <table'.$bold.'>
                            <tr>
                                <td class="ikonok" rowspan="2" style="width: 4rem; white-space: nowrap;">
                                    <br>'.$kesz.$dok_icon.$surgos.'&nbsp;
                                </td>
                                <td style="width: 11rem;" class="ikt_szam">'.$bsz['ikt_szam'].'</td>
                                <td style="width: 9rem;">'.getStatText($tabla, $bsz['stat_id'], 'rovid').'</td>
                                '.$szalloda_neve.'
                                <td>'.$bsz['obj_txt'].$gep_txt.'</td>
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
                            <span>'.$aktiv.' '.$bsz['id'].' '.str_replace('-', '.', $bsz['esnap_d']).'</span>
                            <span>'.$telephely.' - '.$helyseg.'</span>
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

function fenntartasi_szerzodesLista( $bejegyzesek, $tabla, $frissit, $kijeloltek ) {
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

            // Ha a usernek nem csak egy szállodája van, akkor kiírjuk a szálloda nevét
            $szalloda_neve = '';
            if(!$_SESSION['egyszallodas_user']){
                $szalloda_neve = getSzallodaNev($bsz['telephely_id']);
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <table>
                            <tr>
                                <td style="width: 7rem">'.$bsz['ikt_szam'].'</td>
                                <td style="width: 3.6rem">'.$bsz['ev'].'</td>
                                <td style="width: 7rem">'.getStatText('energia_terv', $bsz['stat_id']).'</td>
                                <td>'.$szalloda_neve.'</td>
                            </tr>
                            <tr>
                                <td colspan="4">'.$bsz['text'].'</td>
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
        // }else{

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

            if(isset($_GET['anyagrend'])) {
                array_push($oszlopok,
                    array('id' => 'anyag_tabla_rndid',    'nev' => 'Rendelés szám'),
                    array('id' => 'anyag_tabla_rnd_dt',   'nev' => 'Rendelés dátum'),
                    array('id' => 'anyag_tabla_rndertek', 'nev' => 'Rendelés érték')
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
                    foreach($oszlopok as $oszl){
                        $van_talalat = false;
                        foreach($oszlopok_sorrend as $oszl_s){
                            if($oszl_s['id']==$oszl['id']){
                                $van_talalat = true;
                                continue;
                            }
                        }
                        if(!$van_talalat){
                            $val['id']    = $oszl['id'];
                            $val['width'] = 40;
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
                foreach($oszlopok as $oszl) {
                    $order = '';
                    if($oszl['id']==$_SESSION['anyagigenyles_tablazat_rendez']){
                        if($_SESSION['anyagigenyles_tablazat_irany']=='desc'){
                            $order = ' desc';
                        }else{
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
                if( $bsz['stat_id']==CIKKIGN_STAT_ARAZVA ){
                    $rendelesi_ertek += $bsz['rndmen']*$bsz['rndear'];
                }

                $active = '';
                if( $bsz['recid'] == $_GET['id'] && !isset($_GET['print']) ) {
                    $active = ' kijelolve';
                }

                // Telephely
                $telephely = $adatbazis_kapcsolat->s2('nev', 'telephely', 'id='.$bsz['telephely_id'], '', '1');

                $bold = '';
                if( $bsz['stat_id'] == CIKKIGN_STAT_NYITOTT ){
                    $bold = ' b';
                }

                // Anyagigénylés jog és Árazva státusz esetén a jóváhagyott mennyiség helyben szerkeszthető
                $jhmen = number_format($bsz['jhmen'], 2, ',', ' ');
                if ( $bsz['stat_id']==CIKKIGN_STAT_ARAZVA && _jog(JOG_ANYAGIGENYLES_JOVAHAGY)) {
                    $jhmen = '<input class="r f_num mod" data-tort="2" data-recid="'.$bsz['recid'].'" type="text" id="jhmen_'.$bsz['recid'].'" value="'.number_format($bsz['rndmen'], 2, ',', ' ').'" style="width: 7rem; font-size: 1.4rem;" onclick="select()">';
                }
                $eredeti_ertekek['jhmen_'.$bsz['recid']] = number_format($bsz['rndmen'], 2, ',', ' ');

                // Bal oldali navpanel frissítésekor kijelöljük az aktuális sort
                if($frissit){
                    // $active .= ' kijelolve';
                }

                // Jóváhagyásra váró érték
                $jh_ert = $bsz['rndear']*$bsz['rndmen'];

                $rndertek = getErtek('rndertek', 'cikkrendfej', 'rndid', $bsz['rndid']);

                if($bsz['kulsosnek']){
                    $bsz['kulsosnek'] = '✓';
                }else{
                    $bsz['kulsosnek'] = '';
                }

                $html .= '<tr class="'.$active.$bold.'" onclick="$(\'#szamla-'.$bsz['recid'].'\').click()" data-recid="'.$bsz['recid'].'" data-i="'.$i.'" id="anyagig-sor-'.$bsz['recid'].'">';

                $sorok = array(
                    'anyag_tabla_statusz'       => '<td class="c" sorttable_customkey="'.$bsz['stat_id'].'">'.getStatText('anyagigenyles', $bsz['stat_id']).'</td>',
                    'anyag_tabla_ikt_szam'      => '<td class="c">'.$bsz['ikt_szam'].'</td>',
                    'anyag_tabla_szalloda'      => '<td class="c">'.$telephely['nev'].'</td>',
                    'anyag_tabla_cikkszam'      => '<td class="c">'.$bsz['cikk_id'].'</td>',
                    'anyag_tabla_cikknev'       => '<td>'.htmlspecialchars_decode($bsz['anyagigenyles_nev']).'</td>',
                    'anyag_tabla_igleir'        => '<td id="td_igleir_'.$bsz['recid'].'">'.htmlspecialchars_decode(nl2br2($bsz['igleir'])).'</td>',
                    'anyag_tabla_szallito'      => '<td>'.getErtek('nev', 'szallito', 'id', $bsz['szallito_id']).'</td>',
                    'anyag_tabla_ig_menny'      => '<td class="r nowrap" data-sort="'.$bsz['igmen'].'">'.number_format($bsz['igmen'], 2, ',', ' ').'</td>',
                    'anyag_tabla_mee'           => '<td class="c">'.$bsz['cikkmee'].'</td>',
                    'anyag_tabla_ig_datum'      => '<td class="c">'.substr(str_replace('-', '.', $bsz['insert_dt']), 0, 10).'</td>',
                    'anyag_tabla_jovah_menny'   => '<td class="r nowrap" id="td_jhmen_'.$bsz['recid'].'" data-sort="'.$bsz['jhmen'].'">'.$jhmen.'</td>',
                    'anyag_tabla_jovah_ertek'   => '<td class="r nowrap'.$jhertek_latszik.'" data-sort="'.$jh_ert.'" id="jovah_ertek_sor_'.$bsz['recid'].'">'.number_format($jh_ert, 2, ',', ' ').'</td>',
                    'anyag_tabla_rend_menny'    => '<td class="r nowrap" data-sort="'.$bsz['rndmen'].'">'.number_format($bsz['rndmen'], 2, ',', ' ').'</td>',
                    'anyag_tabla_rend_egysegar' => '<td class="r nowrap" id="td_rndear_'.$bsz['recid'].'" data-sort="'.$bsz['rndear'].'">'.number_format($bsz['rndear'], 2, ',', ' ').'</td>',
                    'anyag_tabla_dev_rend'      => '<td class="r nowrap">'.number_format($bsz['vrndear'], 2, ',', ' ').'</td>',
                    'anyag_tabla_penznem'       => '<td class="c">'.erteklista(ERTEKLISTA_PENZNEM, $bsz['kcs_21']).'</td>',
                    'anyag_tabla_rbs'           => '<td class="c">'.$bsz['kulsosnek'].'</td>'
                );

                if(isset($_GET['anyagrend'])) {
                    $sorok['anyag_tabla_rndid']     = '<td class="c">'.$bsz['rndid'].'</td>';
                    $sorok['anyag_tabla_rnd_dt']    = '<td class="c">'.substr(str_replace('-', '.', $bsz['rnd_dt']), 0, 10).'</td>';
                    // A rendelés érték a Rendelt mennyiség * Rendelési egységár (Ft) oszlopok szorzata
                    $rendeles_ertek = $bsz['rndmen']*$bsz['rndear'];
                    $sorok['anyag_tabla_rndertek']  = '<td class="c" data-sort="'.$rendeles_ertek.'">'.number_format($rendeles_ertek, 0, ',', ' ').' '.get_penznem().'</td>';
                }

                foreach($oszlopok as $oszl) {
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
            $telephely = $adatbazis_kapcsolat->s2('nev', 'telephely', "`id`='{$bsz['telephely_id']}'");

            require 'selectek.php';
            foreach($select_pri_tipus as $tip){
                if($bsz['tipus'] == $tip['value']){
                    $tipus = substr($tip['name'], 0, 1);
                }
            }

            if($bsz['rendk']){
                $tipus = '<span class="piros">'.$tipus.'</span>';
            }
//echo '<pre style="font-size: 1.4rem">';
//print_r($bsz);
//echo '</pre>';
            $li_tart = '<a class="szamla'.$nohover.'" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <table'.$bold.'>
                            <tr>
                                <td class="ikonok" rowspan="2" style="width: 2rem; text-align: center;">'.$tipus.'</td>
                                <td style="width: 15rem;">'.$bsz['ikt_szam'].'</td>
                                <td style="width: 17rem; text-align: left">'.$telephely['nev'].'</td>
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

            //Nyitott státuszú bejegyzések navpanelen félkövérrel jelenjenek meg
            $bold = '';
            if( $bsz['stat_id'] == PRIGENY_STAT_NYITOTT ){
                $bold    = ' class="b"';
                //$active .= ' nyitott_statuszu'; //Erre itt nincs szükség, de meghagytam, ha később mégis kellene valamire
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span'.$bold.'>'.$bsz['ikt_szam'].'</span>
                            <span'.$bold.'>'.$bsz['nev'].'</span>
                            <span'.$bold.'>'.$statusz.'</span>
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
                        array('tabla' => 'anyagigenyles',           'nev' => 'Anyagigénylések'          ),
                        array('tabla' => 'bejelentes',              'nev' => 'Bejelentés'               ),
                        array('tabla' => 'beosztas',                'nev' => 'Beosztások'               ),
                        array('tabla' => 'cikktorzs',               'nev' => 'Cikktörzs'                ),
                        array('tabla' => 'munkalap',                'nev' => 'Munkalap'                 ),
                        array('tabla' => 'energia',                 'nev' => 'Energia'                  ),
                        array('tabla' => 'eseti_fenntartas',        'nev' => 'Eseti fenntartás'         ),
                        array('tabla' => 'eszkozkezeles',           'nev' => 'Eszközkezelés'            ),
                        array('tabla' => 'fenntartasi_szerzodes',   'nev' => 'Fenntartási szerződések'  ),
                        array('tabla' => 'fenntartasi_terv',        'nev' => 'Fenntartási terv'         ),
                        array('tabla' => 'raktar',                  'nev' => 'Raktár'                   ),
                        array('tabla' => 'tervezes',                'nev' => 'Tervezés'                 ),
                        array('tabla' => 'utemezett_feladatok',     'nev' => 'Ütemezett feladatok'      ),
                        array('tabla' => 'uzemeltetesi_naplo',      'nev' => 'Üzemeltetési napló'       ),
                        array('tabla' => 'projektigenyles',         'nev' => 'Projektigénylések'        ),
                        array('tabla' => 'projektigenyles_velemeny','nev' => 'Projektigénylés vélemények'),
                        array('tabla' => 'projektkezeles',          'nev' => 'Projektkezelés'           ),
                        array('tabla' => 'rendszeres_karbantartas', 'nev' => 'Rendszeres karbantartás'  ),
                        array('tabla' => 'szamla',                  'nev' => 'Számlák'                  ),
                        array('tabla' => 'szallito',                'nev' => 'Partnertörzs'             ),
                        array('tabla' => 'torzsadat',               'nev' => 'Törzsadatok'              ),
                        // array('tabla' => 'login',                   'nev' => 'Felhasználók'             )
                    );

    foreach($menupontok as $menu){

        $checked = '';
        if($_GET['konyvtar']){
            $kapott_konyvtar = substr($_GET['konyvtar'], 0, strpos($_GET['konyvtar'], '/'));
            if($kapott_konyvtar == $menu['tabla']){
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
            if($menu['tabla']=='projektigenyles_velemeny'){
                $post = '';
            }

            $alkak = $adatbazis_kapcsolat->s('utvonal', $menu['tabla'].$post);
            foreach($alkak as $alk){
                $konyvtar = substr($alk['utvonal'], 0, strpos($alk['utvonal'], '/'));
                array_push($alkonyvtarak, $konyvtar);
            }

            if($alkonyvtarak){
                $html .= '<ol>';

                $alkonyvtarak = array_unique($alkonyvtarak);
                sort($alkonyvtarak);
                foreach($alkonyvtarak as $alk){

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
    if($login_szallodai){

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
        if($_GET['id']){
            $akt_eszkoz = $adatbazis_kapcsolat->s2('recid telephely_id obj_id', $tabla, "`recid`='{$_GET['id']}'");
            $akt_obj    = $akt_eszkoz['obj_id'];
            $akt_szint  = $adatbazis_kapcsolat->s2('szulo', 'obj', "`recid`='$akt_obj'");
            $akt_szint  = $akt_szint['szulo'];
        }

        // Ha van szűrés (az csak szállodára lehet)
        $sql_kieg = '';
        if ($_GET['mezo']) {
            $sql_kieg .= ' AND `id`=\''.$_GET['felt'].'\'';
        }
        $sql_kieg .= " AND `inaktiv`=0";

        // Visszadjuk az objektum ID-kat is
        $obj_idk = array();

        $szallodak = $adatbazis_kapcsolat->s('id nev', 'telephely', "`id` IN (".implode(',', $login_szallodai).")$sql_kieg", 'nev');
        foreach($szallodak as $szalloda){

            if( isset($_GET['obj']) && $_GET['id'] ) {
                $obj_sor = $adatbazis_kapcsolat->s2('telephely_id szulo', 'obj', "`recid` = '".$_GET['id']."'", '', '1');
            }

            $checked = '';
            if($akt_eszkoz['telephely_id']==$szalloda['id'] || (is_array($obj_sor) && count($obj_sor) && $szalloda['id'] == $obj_sor['telephely_id'])){
                $checked = ' checked';
            }

            $link = '';
            $szintek = $adatbazis_kapcsolat->s('recid nev', 'obj', "`szulo`='0' AND `telephely_id`='{$szalloda['id']}'", 'sor');
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
                                $obj_idk[] = $szint['recid'];

                                $checked = '';
                                if($akt_szint==$szint['recid'] || (is_array($obj_sor) && count($obj_sor) && $szint['recid'] == $obj_sor['szulo'])){
                                    $checked = ' checked';
                                }

                                $link = '';
                                $objektumok = $adatbazis_kapcsolat->s('recid nev', 'obj', "`szulo`='{$szint['recid']}'", 'sor');
                                if($objektumok){
                                    $link = ' class="link"';
                                }
                                $html .= '
                                        <li>
                                            <a id="obj_'.$szint['recid'].'" href="javascript: szamlaBetolt(\''.$szint['recid'].'\', \'eszkozkezeles.php\', \'obj\'); void(0);"><label'.$link.' id="obj_'.$szint['recid'].'" for="szint_'.$szint['recid'].'">'.$szint['nev'].'</label></a>
                                            <input type="checkbox"'.$checked.' class="asdas">
                                        ';
                                // id="szint_'.$szint['recid'].'"

                                if($objektumok){
                                    $html .= '<ol>';
                                    foreach($objektumok as $obj) {
                                        $obj_idk[] = $obj['recid'];

                                        $checked = '';
                                        if($akt_obj==$obj['recid'] || (is_array($obj_sor) && count($obj_sor) && $_GET['id'] == $obj['recid'])){
                                            $checked = ' checked';
                                        }

                                        $link = '';
                                        $eszkozok = $adatbazis_kapcsolat->s('recid nev', 'eszkozkezeles', "`telephely_id`='{$szalloda['id']}' AND `obj_id`='{$obj['recid']}'", 'nev');
                                        if($eszkozok){
                                            $link = ' class="link"';
                                        }
                                        $html .= '<li>
                                                    <a id="obj_'.$obj['recid'].'" href="javascript: szamlaBetolt(\''.$obj['recid'].'\', \'eszkozkezeles.php\', \'obj\'); void(0);"><label'.$link.' id="obj_'.$obj['recid'].'" for="obj_'.$obj['recid'].'">'.$obj['nev'].'</label></a>
                                                    <input type="checkbox"'.$checked.'>';
                                        // id="obj_'.$obj['recid'].'"
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
                    $csoportSor = $adatbazis_kapcsolat->s('nev', 'erteklista_csoport', 'csoport='.$aktCsoport, '', '1');

                    if( $csoportSor ) {
                        $csoportSor = $csoportSor[0];

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
        'rkat'          => 'Érték',
        'id'            => 'Kód',
        'ikt_szam'      => $kieg_nev.' szám',
        'nev'           => 'Név',
        'obj_id'        => 'Helyiség',
        'obj_txt'       => 'Helyiség',
        'rndid'         => 'Rendelés száma',
        'rnd_dt'        => 'Rendelés dátum',
        'recid'         => 'Rendszer ID',
        'surgos'        => 'Sürgős',
        'statusz'       => 'Státusz',
        'stat_id'       => 'Státusz',
        'szakter_id'    => 'Szakterület',
        'szallito_id'   => 'Szállító',
        'szerepkor_id'  => 'Szerepkör',
        'telephely_id'  => 'Szálloda',
        'teljes_nev'    => 'Teljes név',
        'torolve'       => 'Törölve',
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
        $sor   = $adatbazis_kapcsolat->s2('info', 'erteklista', "`csoport` = '{$csoport}' AND `id` = '{$id}'", '', '1');
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
                    }else{
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

    if($adott_ido){
        $adott_ido = trim($adott_ido);
        $adott_ido  = str_replace('.', '-', $adott_ido);
        if(strlen($adott_ido)==16){
            $adott_ido .= ':00';
        }
        $now  = $adott_ido;
        $time = strtotime($adott_ido);
    }else{
        $now  = 'NOW()';
        $time = time();
    }

    // Ütemezők esetén is legyen teljes_nev, amit be tudunk írni
    $teljes_nev = $_SESSION[get_db_tabla().'teljes_nev'];
    $login      = $_SESSION[get_db_tabla().'user'];
    if($adott_nev){
        $login      = $adatbazis_kapcsolat->s2('nev teljes_nev', 'login', "`nev`='{$adott_nev}'");
        $teljes_nev = $login['teljes_nev'];
        $login      = $login['nev'];
    }
    if($rendszer_a_user){
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
                $tablak = array(
                                array('tabla' => 'projektigenyles_dok',     'kulcs' => 'projektigenyles_id'     ),
                                array('tabla' => 'projektigenyles_eloterj', 'kulcs' => 'recid'                  ),
                                array('tabla' => 'projektigenyles_kapcs',   'kulcs' => 'projektigenyles_recid'  ),
                                array('tabla' => 'projektigenyles_megter',  'kulcs' => 'projektigenyles_recid'  ),
                                array('tabla' => 'projektigenyles_statusz', 'kulcs' => 'projektigenyles_recid'  ),
                                array('tabla' => 'projektigenyles_velemeny','kulcs' => 'projektigenyles_recid'  ),
                                );

                foreach($tablak as $tabla){
                    $sql = 'INSERT `'.get_db_tabla().$tabla['tabla'].'_arch` SELECT * FROM `'.get_db_tabla().$tabla['tabla'].'` WHERE `'.$tabla['kulcs'].'`=\''.$pri['recid'].'\'';
                    if(!mysqli_query($db_kapcs, $sql)){
                        $db_error = true;
                    }
                    $sql = $adatbazis_kapcsolat->d($tabla['tabla'], "`{$tabla['kulcs']}`='{$pri['recid']}'");
                    if( $sql === false ) { $db_error = true; }
                }

            }

            if( !$db_error ) {
                return true;
            } else {
                return false;
            }
        }

        // Ha azonos a státusz mint ahová léptetünk akkor is true-val térünk vissza
        if($akt_statusz['stat_id']==$stat_id){
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
    global $felmeresi_terv_tomb;
    global $fenntartasi_terv_tomb;
    global $login_tomb;
    global $munkalap_tomb;
    global $utemezett_feladatok_tomb;
    global $projektigenyles_tomb;
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
        case 'felmeresi_terv'           : case 'felmeresi_terv_arch'            : $akt_tomb = $felmeresi_terv_tomb;         break;
        case 'fenntartasi_terv'         : case 'fenntartasi_terv_arch'          : $akt_tomb = $fenntartasi_terv_tomb;       break;
        case 'login'                    : case 'login_arch'                     : $akt_tomb = $login_tomb;                  break;
        case 'munkalap'                 : case 'munkalap_arch'                  : $akt_tomb = $munkalap_tomb;               break;
        case 'utemezett_feladatok'      : case 'utemezett_feladatok_arch'       : $akt_tomb = $utemezett_feladatok_tomb;    break;
        case 'projektigenyles'          : case 'projektigenyles_arch'           : $akt_tomb = $projektigenyles_tomb;        break;
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
function get_akt_login_szallodai($sqlt_adj_vissza, $telephely_id_oszlop_helyett_masik_oszlop, $masik_user){
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
        }else{
            return ' AND `'.$oszlop.'`=0';
        }
    // Tömböt adunk vissza a szálloda ID-kal
    }else{
        return $szallodak;
    }
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
        if(!$sql){
            if(!addStatusz( 'munkalap_statusz', $mukalap_id, MUNKALAP_STAT_NYITOTT )){
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
function get_szures_where_kieg($mezo, $op, $felt, $tabla){

    $mezo = sql_ertek($mezo);
    $op   = sql_ertek($op);
    $felt = sql_ertek($felt);

    // Legfrisebb mezőre nem szűrünk, ez a limit-re vonatkozik
    if( $mezo == 'legfrissebb' ) {
        return '';
    }

    $op_origi = $op;
    switch ($op){
        case 'equal'    : $op = '=';    break;
        case 'not_eq'   : $op = '!=';   break;
        case 'gteq'     : $op = '>=';   break;
        case 'lteq'     : $op = '<=';   break;
        case 'gt'       : $op = '>';    break;
        case 'lt'       : $op = '<';    break;
        case 'like'     : $op = ' LIKE '; $felt = '%'.$felt.'%'; break;
        case 'empty'    :
            if($felt==1){
                $op = '=';
            }else{
                $op = '!=';
            }
            $felt = '';
        break;
    }

// echo $mezo.' - '.$op.' - '.$felt;exit;
    // Külső táblák kapcsolása
    // A szamla_lista() függvényben is állítsd be a JOIN-okat
    if($mezo=='megjegyzes'){
        return '`'.get_db_tabla().'megjegyzes`.`tabla`=\''.$tabla.'\' AND `'.get_db_tabla().'megjegyzes`.`megj` LIKE \''.$felt.'\'';
    }elseif( $op_origi != 'empty' && ($tabla=='anyagigenyles' && $mezo=='nev') ){
        return '`'.get_db_tabla().'anyagigenyles`.`nev` LIKE \'%'.$felt.'%\'';
    }elseif($tabla=='bejelentes' && $mezo=='telephely_id'){
        return '`'.get_db_tabla().'bejelentes`.`telephely_id`=\''.$felt.'\'';
    }elseif($tabla=='bejelentes' && $mezo=='insert_dt'){
        $felt = str_replace('.', '-', $felt);
        return '`'.get_db_tabla().'bejelentes`.`insert_dt`'.$op.'\''.$felt.'\'';
    }elseif($tabla=='login' && $mezo=='jogosultsag'){
        return 'SUBSTRING(`flags`, '.($felt+1).', 1)=1';
    }elseif($tabla=='login' && $mezo=='szalloda'){
        return '`'.get_db_tabla().'telephely_kapcs`.`telephely_id`=\''.$felt.'\'';

    // mszk_jovahagy feltétele: a munkalap döntésre vár státuszban van, és a hozzárendelt személy vagy Műszaki vezető vagy diszpécser
    }elseif($tabla=='munkalap' && $mezo=='mszk_jovahagy'){
        if($felt==1){
            return '`stat_id`=\''.MUNKALAP_STAT_DONTESRE_VAR.'\' AND (`jh1_szerep` IN (\''.MSZK_VEZETO.'\', \''.MUSZAKI_IGAZGATO.'\') OR `jh2_szerep` IN (\''.MSZK_VEZETO.'\', \''.MUSZAKI_IGAZGATO.'\'))';
        }else{
            return '`stat_id`=\''.MUNKALAP_STAT_DONTESRE_VAR.'\' AND (`jh1_szerep` NOT IN (\''.MSZK_VEZETO.'\', \''.MUSZAKI_IGAZGATO.'\') AND `jh2_szerep` NOT IN (\''.MSZK_VEZETO.'\', \''.MUSZAKI_IGAZGATO.'\'))';
        }
    }elseif($tabla=='utemezett_feladatok' && $mezo=='telephely_id'){
        return '`'.get_db_tabla().'utemezett_feladatok`.`telephely_id`=\''.$felt.'\'';
    }elseif($tabla=='utemezett_feladatok' && $mezo=='id'){
        return '`'.get_db_tabla().'utemezett_feladatok`.`id`=\''.$felt.'\'';
    }elseif($tabla=='szallito' && $mezo=='szakterulet'){
        return '`'.get_db_tabla().'szakter_kapcs`.`szakter_id`=\''.$felt.'\'';
    }else{

        // Mezőnevek pontosítása
        if( $tabla=='anyagigenyles' && $mezo=='nev' ){
            $mezo = get_db_tabla().'anyagigenyles`.`'.$mezo;
        }
        return '`'.get_db_tabla().$tabla.'`.`'.$mezo.'`'.$op.'\''.$felt.'\'';
    }

}
// Összetett szűrés SQL-jét állítja elő egy stringből.
function get_o_szures_where_kieg($feltetel, $tabla){

    $sql = '';
    $elemek = explode('#', $feltetel);
    foreach($elemek as $elem){

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
    // return $sql;
    return csonkit($sql, 5);
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
                    <div class="dtc">'.nl2br2($sor['text']).'</div>
                </div>
            ');
        }
    }

    $szamla_html .= '</div>';

    return $szamla_html;
}

// Adott tábla iktatószámának következő sorszámának lekérése
function get_iktatoszam_sorszam($tabla){

    global $adatbazis_kapcsolat;

    $db_error = false;

    $ikt_seq = $adatbazis_kapcsolat->s2('ev sorszam', 'iktszam_seq', "`tabla`='$tabla' AND `ev`='".date('y')."'");
    if( $ikt_seq === false ) { $db_error = true; }
    if( $ikt_seq ) {

        // Sorszám
        $sorszam = $ikt_seq['sorszam'];

        // Sorszám növelés
        $sorszam++;

        // Letároljuk az új sorszámot
        $sql = $adatbazis_kapcsolat -> u('iktszam_seq', array('sorszam'), array($sorszam), "`tabla`='$tabla' AND `ev`='".date('y')."'");
        if( $sql === false ) { $db_error = true; }
    } else {
        // Sorszám
        $sorszam = 1;

        $sql = $adatbazis_kapcsolat -> i('iktszam_seq', array('sorszam', 'ev', 'tabla'), array($sorszam, date('y'), $tabla));
        if( $sql === false ) { $db_error = true; }
    }

    if($db_error){
        return false;
    }else{
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

// Szerepkör címkéjét ne DB-ből kérjük már le, erre itt egy függvény
function getSzerepkorNev($szerepkor_id){
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
}

// Szállodák nevét ne DB-ből kérjük már le, vagyis csak egyszer, loginnál
function getSzallodaNev($szalloda_id){
    foreach($_SESSION['telephelyek'] as $telephely){
        if($telephely['id']==$szalloda_id){
            return $telephely['nev'];
        }
    }
}

// Szállodák nevét ne DB-ből kérjük már le, vagyis csak egyszer, loginnál
function getErtekLista($erteklista_csoport, $erteklista_id, $erteklista_recid){
    if($erteklista_csoport){
        foreach($_SESSION['erteklista'] as $erteklista){
            if($erteklista['csoport']==$erteklista_csoport && $erteklista['id']==$erteklista_id){
                return $erteklista['info'];
            }
        }
    }
    if($erteklista_recid){
        foreach($_SESSION['erteklista'] as $erteklista){
            if($erteklista['recid']==$erteklista_recid){
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
    }else{
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

    $new_array = array();
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
    }else{
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
function level_felkeszit_es_kuld($tabla, $recid, $ert_pipa, $sablon, $cimzett, $extra_szoveg){

    global $adatbazis_kapcsolat;
    global $db_kapcs;
    $cimzett_mail = '';
    $cimzett_nev  = '';
    $felhasznalonak_szo = 'felhasználónak';


    // Kinek menjen a mail? Egy $cimzettek tömböt kell előállítani.
    // Illetve az üzenet törzse is itt kerül beállításra

    switch($cimzett){
        // Anyagigénylőnek
        case 'anyagigenylo':

            $felhasznalonak_szo = 'anyagigénylőnek';

            $anyagigenyles = $adatbazis_kapcsolat->s2('ikt_szam cikk_id nev igmen cikkmee loginnev', 'anyagigenyles', "`recid`='{$recid}'");

            $mit            = array('{IKT_SZAM}',               '{CIKKSZAM}',               '{CIKKNEV}',            '{MENNY}'                                                                           );
            $mivel          = array($anyagigenyles['ikt_szam'], $anyagigenyles['cikk_id'],  $anyagigenyles['nev'],  number_format($anyagigenyles['igmen'], 2, ',', ' ').' '.$anyagigenyles['cikkmee']   );
            $uzi            = str_replace( $mit, $mivel, fajlbol_olvas('../sablon/admin/mail/anyagigenyles.html') );

            // Aktív-e és nem virtuális-e a felhasználó, van-e mailcíme? Ha nem, kilépünk
            $login = $adatbazis_kapcsolat -> s2('teljes_nev usermail aktiv virtual szemelyzet_id flags_ert', 'login', "`nev` = '{$anyagigenyles['loginnev']}'");
            if(!$login['aktiv'] || $login['virtual'] || !$login['usermail'] || substr($login['flags_ert'], $ert_pipa, 1)!=1 ){
                return false;
            }

            // Címzett adatait kiszámoljuk
            $cimzett_nev   = $login['teljes_nev'];
            $cimzett_mail  = $login['usermail'];
            $telefon       = $login['usertel'];
            $szemelyzet_id = $login['szemelyzet_id'];

            $cimzettek = array( array('nev' => $anyagigenyles['loginnev'], 'teljes_nev' => $cimzett_nev, 'usermail' => $cimzett_mail, 'szemelyzet_id' => $szemelyzet_id) );
        break;

        // Bejelentőnek
        case 'bejelento':

            $felhasznalonak_szo = 'bejelentőnek';

            if(substr($recid, 0, 1)=='m'){
                $munkalap   = $adatbazis_kapcsolat->s2('text', 'munkalap', "`recid`='".substr($recid, 1)."'");
                $hiba       = htmlspecialchars_decode( nl2br2( $munkalap['text'] ) );
            }else{
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
            $login = $adatbazis_kapcsolat -> s2('teljes_nev usermail aktiv virtual szemelyzet_id flags_ert', 'login', "`nev` = '{$bejelentes['loginnev']}'");
            if(!$login['aktiv'] || $login['virtual'] || !$login['usermail'] || substr($login['flags_ert'], $ert_pipa, 1)!=1 ){
                return false;
            }

            // Ha még nincs meg a címzett, akkor a login táblából számítjuk
            if(!$cimzett_nev) { $cimzett_nev   = $login['teljes_nev'];  }
            if(!$cimzett_mail){ $cimzett_mail  = $login['usermail'];    }
            if(!$telefon)     { $telefon       = $login['usertel'];     }

            $cimzettek = array( array('nev' => $bejelentes['loginnev'], 'teljes_nev' => $cimzett_nev, 'usermail' => $cimzett_mail, 'szemelyzet_id' => $bejelentes['szemelyzet_id']) );
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

            $cimzettek = array( array('nev' => 'htdiszp', 'teljes_nev' => 'Handari Tamás', 'usermail' => 'handari@szinvatrend.hu', 'szemelyzet_id' => '10') );
        break;

        // Elfelejtett jelszó kérése
        case 'elfelejtettJelszo':

            $login      = $adatbazis_kapcsolat->s2('nev teljes_nev beosztas_id reszleg_id usertel usermail aktiv', 'login', "`recid`='{$recid}'");
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

            $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail aktiv virtual szemelyzet_id', 'login', "substr(`flags_ert`, ".($ert_pipa+1).", 1)=1 AND `aktiv`=1 AND `virtual`=0");
            if(!$cimzettek){
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

            $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail aktiv virtual szemelyzet_id', 'login', "`recid`='{$recid}'");
            if(!$cimzettek){
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
            $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail aktiv virtual szemelyzet_id', 'login', "substr(`flags`, ".(JOG_BEJELENTES_VIZSGAL+1).", 1)=1 AND substr(`flags_ert`, ".($ert_pipa+1).", 1)=1 AND `aktiv`=1 AND `virtual`=0");
            if(!$cimzettek){
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
            $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail aktiv virtual szemelyzet_id', 'login', "substr(`flags`, ".(JOG_MUNKALAP_IGAZOLAS+1).", 1)=1 AND substr(`flags_ert`, ".($ert_pipa+1).", 1)=1 AND `aktiv`=1 AND `virtual`=0");
            if(!$cimzettek){
                return false;
            }

        break;

        // Munkalap igazolás joggal rendelkezőknek
        case '_jog'.JOG_MUNKALAP_KULSOS_DONTES:

            $munkalap       = $adatbazis_kapcsolat->s2('ikt_szam telephely_id obj_id insert_dt', $tabla, "`recid`='{$recid}'");
            $munkalap_kapcs = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$recid}'");
            $bejelentes     = $adatbazis_kapcsolat->s2('hiba_id', 'bejelentes', "`recid`='{$munkalap_kapcs['bejelentes_id']}'");

            $mit            = array('{IKT_SZAM}',          '{SZALLODA}',                                                   '{INS_DT}',             '{HIBA}',                                                        '{OBJ}',                                              '{KESZRE_JELENTETTE}' );
            $mivel          = array($munkalap['ikt_szam'], getErtek('nev', 'telephely', 'id', $munkalap['telephely_id']),  $munkalap['insert_dt'], getErtek('info', 'erteklista', 'recid', $bejelentes['hiba_id']), getErtek('nev', 'obj', 'recid', $munkalap['obj_id']), ''                    );
            $uzi            = str_replace( $mit, $mivel, fajlbol_olvas('../sablon/admin/mail/munkalaposoknak.html') );

            // Több címzett is van
            $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail aktiv virtual szemelyzet_id', 'login', "substr(`flags`, ".(JOG_MUNKALAP_KULSOS_DONTES+1).", 1)=1 AND substr(`flags_ert`, ".($ert_pipa+1).", 1)=1 AND `aktiv`=1 AND `virtual`=0");
            if(!$cimzettek){
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
            $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail aktiv virtual szemelyzet_id', 'login', "{$sql_kieg} substr(`flags`, ".(JOG_MUNKALAP_KULSOS_ELOKESZIT+1).", 1)=1 AND substr(`flags_ert`, ".($ert_pipa+1).", 1)=1 AND `aktiv`=1 AND `virtual`=0");
            if(!$cimzettek){
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
            $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail aktiv virtual szemelyzet_id', 'login', "substr(`flags`, ".(JOG_MUNKALAP_KULSOS_MEGREND+1).", 1)=1 AND substr(`flags_ert`, ".($ert_pipa+1).", 1)=1 AND `aktiv`=1 AND `virtual`=0");
            if(!$cimzettek){
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
            $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail aktiv virtual szemelyzet_id', 'login', "substr(`flags`, ".(JOG_MUNKALAP_VIZSGALAT+1).", 1)=1 AND substr(`flags_ert`, ".($ert_pipa+1).", 1)=1 AND `aktiv`=1 AND `virtual`=0");
            if(!$cimzettek){
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
            $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail aktiv virtual szemelyzet_id', 'login', "substr(`flags`, ".(JOG_MUNKALAP_MEGTEKINT_FELADAT+1).", 1)=1 AND substr(`flags_ert`, ".($ert_pipa+1).", 1)=1 AND `aktiv`=1 AND `virtual`=0");
            if(!$cimzettek){
                return false;
            }

        break;

        // Anyagigénylés küldés jóváhagyásra
        case '_jog'.JOG_ANYAGIGENYLES_JOVAHAGY:

            $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail aktiv virtual szemelyzet_id', 'login', "substr(`flags`, ".(JOG_ANYAGIGENYLES_JOVAHAGY+1).", 1)=1 AND substr(`flags_ert`, ".($ert_pipa+1).", 1)=1 AND `aktiv`=1 AND `virtual`=0");
            if(!$cimzettek){
                return false;
            }

        break;
    }

    // Esemény neve
    switch( $sablon ){

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

    }

    // Aláírásban a {GET_NEV} még át kell íni
    $uzi = str_replace('{GET_NEV}', get_nev(), $uzi);

    // Levélküldés
    $megj = '';
    $hiba = '';

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

//echo '<pre style="font-size: 1.4rem">';
//print_r($cimzettek);
//echo '</pre>';
//echo $targy_kieg.$targy . '<br>';
//echo $uzi_kieg.$uzi . '<br>';
//exit;

    foreach($cimzettek as $akt_cimzett){

        // Mely tábláknál ellenőrizzünk szállodát is (saját szállodák). Kivéve, ha nem a Diszpécser köztponti mailcímre megy az értesítés
        $szallodat_ellenorizz = array('bejelentes', 'munkalap', 'anyagigenyles');
        if( in_array($tabla, $szallodat_ellenorizz) && $cimzett!='diszpecser_kozponti' ){
            $szalloda = $adatbazis_kapcsolat->s2('telephely_id', $tabla, "`recid`='{$recid}'");
            $sajat_szallodak = get_akt_login_szallodai('', '', $akt_cimzett['nev']);

            // Ha adott rekord szállodája a usernek nem saját szállodája, akkor nem küldünk levelet
            if( !in_array($szalloda['telephely_id'], $sajat_szallodak) ){
                continue;
            }
        }

        $szerepkor = getSzerepkorNev($akt_cimzett['szemelyzet_id']);

        $mit            = array('{NEV}');
        $mivel          = array($akt_cimzett['teljes_nev']);
        $megszolitas    = str_replace( $mit, $mivel, fajlbol_olvas('../sablon/admin/mail/megszolitas.html'));

        // Tényleges küldés
        if($akt_cimzett['usermail']){
            $sikeres_kuldes = level_kuldes(get_nev(), get_mail(), $akt_cimzett['teljes_nev'], $akt_cimzett['usermail'], $targy_kieg.$targy, $uzi_kieg.$megszolitas.$uzi);
        }

        // Ha van értéke, az egy hibaüzenet, akkor sikerült küldeni, ha nincs visszatérési érték
        if( $sikeres_kuldes ){

            $megj .= 'E-mail küldése nem sikerült '.$akt_cimzett['teljes_nev'].' '.$szerepkor.' '.$felhasznalonak_szo.' '.$esemeny.'('.$sikeres_kuldes.')<br><br>';

            // Popupos hibaüzenet csak MSZK vezetőnek és Diszpécsernek megy
            if( $_SESSION[get_db_tabla().'szemelyzet_id']==MSZK_VEZETO || $_SESSION[get_db_tabla().'szemelyzet_id']==DISZPECSER ){
                $hiba .=  nl2('Hiba: E-mail küldése nem sikerült
                        <br>'.$cimzett_nev.' '.$szerepkor.'
                        <br>Tel: '.$telefon.'
                        <br>Email: '.$cimzett_mail.'
                        ');
            }

        // Sikeres levélküldés
        }else{

            // Ha a címzett nem a diszpécser köztponti mailcím, akkor van csak megjegyzés
            if( $cimzett!='diszpecser_kozponti' ){
                $adatbazis_kapcsolat->u($tabla, array('mail_ment'), array(1), "`recid`='{$recid}'");
                $megj .= 'E-mail elküldve '.$akt_cimzett['teljes_nev'].' '.$szerepkor.' '.$felhasznalonak_szo.' '.$esemeny.'<br><br>';
            }

        }
    }

    // Ha nincs e-mail cím, akkor ezt a megjegyzésbe is írjuk bele
    if(!$akt_cimzett['usermail']){
        $megj = 'A felhasználónak nincs e-mail címe, ezért a következő esemény nem következett be:<br>'.$megj;
    }

    // Ha van megejgyzés, akkor azt hozzáadjuk
    if($megj){
        // Megjegyzés hozzáadása
        addMegj($tabla, $recid, $megj);
        // Ha volt kapcsolódó munkalap, akkor oda is beírjuk a megjegyést
        if($munkalap_kapcs){
            addMegj('munkalap', $munkalap_kapcs['munkalap_id'], $megj);
        }
    }
    mysqli_commit($db_kapcs);
    // Hibaüzenettel is visszatérhetünk
    if($hiba){
        return $hiba;
    }

}

// Egységes dátum kiíráshoz (másodperc nem kell, kötőjel helyett pont)
function datum_kiir($datum){
    return str_replace('-', '.', substr($datum, 0, 16));
}


// Új munkalap készítése kiszervezve
function munkalap_letrehoz($stat_id, $telephely_id, $tipus_id, $obj_id, $gep_id, $surgos=0, $text='', $szemelyzet_id=0, $szallito_id=0, $uf_szam='', $esnap_d='0000-00-00', $hiba_id = 0, $loginnev = ''){

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

    // Ha meg van adva, hogy ki legyen a létrehozó, akkor adott loginnév legyen a név/teljes név insert-nél, egyébként a Session (tehátbelépett) felhasználó
    if($loginnev){
        $login = $adatbazis_kapcsolat->s2('nev teljes_nev', 'login', "`nev`='{$loginnev}'");
    }else{
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
    }else{
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
            // Ez utóbbi feltétek vizsgálat sokszor felesleges, mert a feltétel (ahol ez a függvény meghívódik) eleve kizárja a külső kivitelezőket. De nem baj, így általánosa meghívható ez a függvény.
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
function fenntartasi_terv_energia($sor/* Fenntartás terv sora */){

    global $adatbazis_kapcsolat;

    $szamla_html = '
    <div id="fixTable">
        <table class="szamla_cont_tabla nolink fejlec_padd szep_csikos kisbetu energia_tabla">
        <thead>
        <tr>
            <th class="ktgnem_kod"><span>Ktgnem kód</span></th>
            <th style="min-width: 10rem; width: 10rem"><span>Ktgnem név</span></th>
            <th style="min-width: 20rem; width: 20rem"><span>Tárgy</span></th>
            <th><span>Mee</span></th>
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
    if($sor['stat_id']==ENERGIATERV_STAT_MUNKAKOZI){
        $energia_terv   = $adatbazis_kapcsolat->s2('recid', 'energia_terv', "`ev`='{$sor['ev']}' AND `stat_id`='".ENERGIATERV_STAT_ELES."'");
        $akt_eear_sorok = $adatbazis_kapcsolat->s('*', 'energia_terv_ear', "`energia_terv_id`='{$energia_terv['recid']}' AND `telephely_idk` LIKE '%{$sor['telephely_id']}%'");
        $eear_soridk    = adatbazis2tomb($akt_eear_sorok, 'sorid');
    }

    $volt_valtozas  = false;
    $energia_soridk = array();
    $energiak = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_energia', "`fenntartasi_terv_id`='{$sor['recid']}'");
    foreach($energiak as $energia){

        // Innen tujduk majd, hogy mik az új sorok
        array_push($energia_soridk, $energia['sorid']);

        // Ha változtak volna az egységárak, azt felül kell írni
        if($sor['stat_id']==ENERGIATERV_STAT_MUNKAKOZI){
            $energia_ear = $adatbazis_kapcsolat->s2('*', 'energia_terv_ear', "`energia_terv_id`='{$energia_terv['recid']}' AND `sorid`='{$energia['sorid']}'");
        }

        // Számok formázása
        $mennyiseg_osszesen = 0;
        $ertek_osszesen     = 0;
        for($i=1; $i<13; $i++){
            if($i<10){
                $i = '0'.$i;
            }

            if($sor['stat_id']==ENERGIATERV_STAT_MUNKAKOZI){
                if($energia_ear['ear'.$i]!=$energia['ear'.$i]){
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
        $ertek_osszesen     = number_format($ertek_osszesen,  0, ',', ' ');

        $class = '';
        if( $sor['stat_id']==ENERGIATERV_STAT_MUNKAKOZI && !in_array($energia['sorid'], $eear_soridk) ){
            $class          = ' piros_hat';
            $volt_valtozas  = true;
        }

        $szamla_html .= '
            <tr id="ft_em_'.$energia['recid'].'" data-recid="'.$energia['recid'].'" class="ftt_menny'.$class.'">
                <td>'.$energia['ktgnem_id'].'</td>
                <td class="wrap">'.getErtek('nev', 'ktgnem', 'id', $energia['ktgnem_id']).'</td>
                <td class="wrap">'.$energia['dijelem'].'</td>
                <td>'.$energia['mee'].'</td>
                <td class="r mennyossz">'.$mennyiseg_osszesen.'</td>
                <td class="r ertekossz">'.$ertek_osszesen.'</td>
                <td class="r">'.$energia['ear01'].'</td>
                <td class="r">'.$energia['em01'].'</td>
                <td class="r ft01">'.$energia['ft01'].'</td>
                <td class="r">'.$energia['ear02'].'</td>
                <td class="r">'.$energia['em02'].'</td>
                <td class="r ft02">'.$energia['ft02'].'</td>
                <td class="r">'.$energia['ear03'].'</td>
                <td class="r">'.$energia['em03'].'</td>
                <td class="r ft03">'.$energia['ft03'].'</td>
                <td class="r">'.$energia['ear04'].'</td>
                <td class="r">'.$energia['em04'].'</td>
                <td class="r ft04">'.$energia['ft04'].'</td>
                <td class="r">'.$energia['ear05'].'</td>
                <td class="r">'.$energia['em05'].'</td>
                <td class="r ft05">'.$energia['ft05'].'</td>
                <td class="r">'.$energia['ear06'].'</td>
                <td class="r">'.$energia['em06'].'</td>
                <td class="r ft06">'.$energia['ft06'].'</td>
                <td class="r">'.$energia['ear07'].'</td>
                <td class="r">'.$energia['em07'].'</td>
                <td class="r ft07">'.$energia['ft07'].'</td>
                <td class="r">'.$energia['ear08'].'</td>
                <td class="r">'.$energia['em08'].'</td>
                <td class="r ft08">'.$energia['ft08'].'</td>
                <td class="r">'.$energia['ear09'].'</td>
                <td class="r">'.$energia['em09'].'</td>
                <td class="r ft09">'.$energia['ft09'].'</td>
                <td class="r">'.$energia['ear10'].'</td>
                <td class="r">'.$energia['em10'].'</td>
                <td class="r ft10">'.$energia['ft10'].'</td>
                <td class="r">'.$energia['ear11'].'</td>
                <td class="r">'.$energia['em11'].'</td>
                <td class="r ft11">'.$energia['ft11'].'</td>
                <td class="r">'.$energia['ear12'].'</td>
                <td class="r">'.$energia['em12'].'</td>
                <td class="r ft12">'.$energia['ft12'].'</td>
            </tr>
            ';
    }

    // Új sorok kiszámítása, kirajzolása
    if($sor['stat_id']==ENERGIATERV_STAT_MUNKAKOZI){
        foreach ($eear_soridk as $sorid){
            if(!in_array($sorid, $energia_soridk)){
                $volt_valtozas  = true;

                $energia = $adatbazis_kapcsolat->s2('*', 'energia_terv_ear', "`energia_terv_id`='{$energia_terv['recid']}' AND `sorid`='{$sorid}'");
// echo $adatbazis_kapcsolat->gp() . '<br>';
                $mennyiseg_osszesen = 0;
                $ertek_osszesen     = 0;
                for($i=1; $i<13; $i++){
                    if($i<10){
                        $i = '0'.$i;
                    }

                    $mennyiseg_osszesen += $energia['em'.$i];
                    $ertek_osszesen     += $energia['ft'.$i];
                }
                $mennyiseg_osszesen = number_format($mennyiseg_osszesen,  0, ',', ' ');
                $ertek_osszesen     = number_format($ertek_osszesen,  0, ',', ' ');
                $ktgnem = $adatbazis_kapcsolat->s2('id nev', 'ktgnem', "`id`='{$energia['ktgnem_id']}'");

                $szamla_html .= '
                    <tr id="ft_em_'.$sorid.'" data-recid="'.$sorid.'" class="ftt_menny zold_hat">
                        <td>'.$ktgnem['id'].'</td>
                        <td>'.$ktgnem['nev'].'</td>
                        <td>'.$energia['dijelem'].'</td>
                        <td>'.$energia['mee'].'</td>
                        <td class="r">'.$mennyiseg_osszesen.'</td>
                        <td class="r ertekossz">'.$ertek_osszesen.'</td>
                        <td class="r">'.$energia['ear01'].'</td>
                        <td class="r">'.$energia['em01'].'</td>
                        <td class="r ft01">'.$energia['ft01'].'</td>
                        <td class="r">'.$energia['ear02'].'</td>
                        <td class="r">'.$energia['em02'].'</td>
                        <td class="r ft02">'.$energia['ft02'].'</td>
                        <td class="r">'.$energia['ear03'].'</td>
                        <td class="r">'.$energia['em03'].'</td>
                        <td class="r ft03">'.$energia['ft03'].'</td>
                        <td class="r">'.$energia['ear04'].'</td>
                        <td class="r">'.$energia['em04'].'</td>
                        <td class="r ft04">'.$energia['ft04'].'</td>
                        <td class="r">'.$energia['ear05'].'</td>
                        <td class="r">'.$energia['em05'].'</td>
                        <td class="r ft05">'.$energia['ft05'].'</td>
                        <td class="r">'.$energia['ear06'].'</td>
                        <td class="r">'.$energia['em06'].'</td>
                        <td class="r ft06">'.$energia['ft06'].'</td>
                        <td class="r">'.$energia['ear07'].'</td>
                        <td class="r">'.$energia['em07'].'</td>
                        <td class="r ft07">'.$energia['ft07'].'</td>
                        <td class="r">'.$energia['ear08'].'</td>
                        <td class="r">'.$energia['em08'].'</td>
                        <td class="r ft08">'.$energia['ft08'].'</td>
                        <td class="r">'.$energia['ear09'].'</td>
                        <td class="r">'.$energia['em09'].'</td>
                        <td class="r ft09">'.$energia['ft09'].'</td>
                        <td class="r">'.$energia['ear10'].'</td>
                        <td class="r">'.$energia['em10'].'</td>
                        <td class="r ft10">'.$energia['ft10'].'</td>
                        <td class="r">'.$energia['ear11'].'</td>
                        <td class="r">'.$energia['em11'].'</td>
                        <td class="r ft11">'.$energia['ft11'].'</td>
                        <td class="r">'.$energia['ear12'].'</td>
                        <td class="r">'.$energia['em12'].'</td>
                        <td class="r ft12">'.$energia['ft12'].'</td>
                    </tr>
                    ';
            }
        }
    }

    $szamla_html .= nl2('
                        </tbody>
                        <tfoot class="nowrap">
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
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
function fenntartasi_terv_muszaki_anyag($sor){

    global $adatbazis_kapcsolat;

    $szamla_html = '
            <div id="fixTable">
                <table class="szamla_cont_tabla nolink fejlec_padd szep_csikos kisbetu muszaki_anyag_tabla">
                <thead>
                <tr>
                    <th class="ktgnem_kod"><span>Ktgnem kód</span></th>
                    <th style="min-width: 10rem; width: 10rem"><span>Ktgnem név</span></th>
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

            $muszaki_anyagok = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_muszaki_anyag', "`fenntartasi_terv_id`='{$sor['recid']}'");
            foreach($muszaki_anyagok as $anyagok){

                $osszesen = 0;
                for($i=1; $i<13; $i++){
                    if($i<10){
                        $i = '0'.$i;
                    }
                    $osszesen += $anyagok['ft'.$i];

                    $anyagok['ft'.$i] = number_format($anyagok['ft'.$i], 0, '', ' ');
                }
                $osszesen = number_format($osszesen, 0, '', ' ');

                $szamla_html .= '
                    <tr id="ft_ft_'.$anyagok['recid'].'" data-recid="'.$anyagok['recid'].'" class="ftt_menny">
                        <td>'.$anyagok['ktgnem_id'].'</td>
                        <td class="wrap">'.getErtek('nev', 'ktgnem', 'id', $anyagok['ktgnem_id']).'</td>
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
                                    <td></td>
                                    <td></td>
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
function fenntartasi_terv_rendsz_karban($sor){

    global $adatbazis_kapcsolat;

    $szamla_html = '
    <div id="fixTable">
        <table class="szamla_cont_tabla nolink fejlec_padd szep_csikos kisbetu rendsz_karban_tabla">
        <thead>
        <tr>
            <th class="ktgnem_kod"><span>Ktgnem kód</span></th>
            <th style="min-width: 10rem; width: 10rem"><span>Ktgnem név</span></th>
            <th style="min-width: 20rem; width: 20rem"><span>Tárgy</span></th>
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

    $rendszeresek = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_rendsz_karban', "`fenntartasi_terv_id`='{$sor['recid']}'");
    foreach($rendszeresek as $rendszeres){

        $osszesen = 0;
        for($i=1; $i<13; $i++){
            if($i<10){
                $i = '0'.$i;
            }
            $osszesen += $rendszeres['ft'.$i];

            $rendszeres['ft'.$i] = number_format($rendszeres['ft'.$i], 0, '', ' ');
        }
        $osszesen = number_format($osszesen, 0, '', ' ');

        $szamla_html .= '
            <tr id="ft_ft_'.$rendszeres['recid'].'" data-recid="'.$rendszeres['recid'].'" class="ftt_menny">
                <td>'.$rendszeres['ktgnem_id'].'</td>
                <td class="wrap">'.getErtek('nev', 'ktgnem', 'id', $rendszeres['ktgnem_id']).'</td>
                <td class="wrap">'.$rendszeres['targy'].'</td>
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
                            <td></td>
                            <td></td>
                            <td></td>
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
            <th class="ktgnem_kod"><span>Ktgnem kód</span></th>
            <th style="min-width: 10rem; width: 10rem"><span>Ktgnem név</span></th>
            <th style="min-width: 20rem; width: 20rem"><span>Tárgy</span></th>
            <th style="min-width: 20rem; width: 20rem"><span>Részletes leírás</span></th>
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

    $esetik = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_eseti_karban', "`fenntartasi_terv_id`='{$sor['recid']}'");
    foreach($esetik as $eseti){

        $osszesen = '';
        for($i=1; $i<13; $i++){
            if($i<10){
                $i = '0'.$i;
            }
            $osszesen += $eseti['ft'.$i];
            $eseti['ft'.$i] = number_format($eseti['ft'.$i], 0, '', ' ');
        }
        $osszesen = number_format($osszesen, 0, '', ' ');

        $szamla_html .= '
            <tr id="ft_ft_'.$eseti['recid'].'" data-recid="'.$eseti['recid'].'" class="ftt_menny">
                <td>'.$eseti['ktgnem_id'].'</td>
                <td style="min-width: 10rem; width: 10rem" class="wrap">'.getErtek('nev', 'ktgnem', 'id', $eseti['ktgnem_id']).'</td>
                <td style="min-width: 20rem; width: 20rem" class="wrap">'.$eseti['targy'].'</td>
                <td style="min-width: 20rem; width: 20rem" class="wrap">'.nl2br2($eseti['leir']).'</td>
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
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
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
function fenntartasi_terv_hibaelharitas($sor){

    global $adatbazis_kapcsolat;

    $szamla_html .= '
    <div id="fixTable">
        <table class="szamla_cont_tabla nolink fejlec_padd szep_csikos kisbetu hibaelharitas_tabla">
        <thead>
        <tr>
            <th class="ktgnem_kod"><span>Ktgnem kód</span></th>
            <th style="min-width: 10rem; width: 10rem"><span>Ktgnem név</span></th>
            <th style="min-width: 20rem; width: 20rem"><span>Részletes leírás</span></th>
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

    $hibak = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_hibaelharitas', "`fenntartasi_terv_id`='{$sor['recid']}'");
    foreach($hibak as $hiba){

        $osszesen = '';
        for($i=1; $i<13; $i++){
            if($i<10){
                $i = '0'.$i;
            }
            $osszesen += $hiba['ft'.$i];
            $hiba['ft'.$i] = number_format($hiba['ft'.$i], 0, '', ' ');
        }
        $osszesen = number_format($osszesen, 0, '', ' ');

        $szamla_html .= '
            <tr id="ft_ft_'.$hiba['recid'].'" data-recid="'.$hiba['recid'].'" class="ftt_menny">
                <td>'.$hiba['ktgnem_id'].'</td>
                <td class="wrap">'.getErtek('nev', 'ktgnem', 'id', $hiba['ktgnem_id']).'</td>
                <td class="wrap">'.nl2br2($hiba['leir']).'</td>
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
                            <td></td>
                            <td></td>
                            <td></td>
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
function fenntartasi_terv_miniprojekt($sor){

    global $adatbazis_kapcsolat;

    $szamla_html = '
    <div id="fixTable">
        <table class="szamla_cont_tabla nolink fejlec_padd szep_csikos kisbetu miniprojekt_tabla width_auto">
        <thead>
        <tr>
            <th class="ktgnem_kod"><span>Ktgnem kód</span></th>
            <th style="min-width: 10rem; width: 10rem"><span>Ktgnem név</span></th>
            <th style="min-width: 20rem; width: 20rem"><span>Tárgy</span></th>
            <th style="min-width: 20rem; width: 20rem"><span>Részletes leírás</span></th>
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

    $minik = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_miniprojekt', "`fenntartasi_terv_id`='{$sor['recid']}'");
    foreach($minik as $mini){

        $osszesen = '';
        for($i=1; $i<13; $i++){
            if($i<10){
                $i = '0'.$i;
            }
            $osszesen += $mini['ft'.$i];
            $mini['ft'.$i] = number_format($mini['ft'.$i], 0, '', ' ');
        }
        $osszesen = number_format($osszesen, 0, '', ' ');

        $szamla_html .= '
            <tr id="ft_ft_'.$mini['recid'].'" data-recid="'.$mini['recid'].'" class="ftt_menny">
                <td>'.$mini['ktgnem_id'].'</td>
                <td style="min-width: 10rem; width: 10rem" class="wrap">'.getErtek('nev', 'ktgnem', 'id', $mini['ktgnem_id']).'</td>
                <td style="min-width: 20rem; width: 20rem" class="wrap">'.$mini['targy'].'</td>
                <td style="min-width: 20rem; width: 20rem" class="wrap">'.nl2br2($mini['leir']).'</td>
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
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
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
function fenntartasi_terv_bwh($sor){

    global $adatbazis_kapcsolat;

    $szamla_html .= '
        <table class="szamla_cont_tabla nolink fejlec_padd szep_csikos kisbetu bwh_tabla">
        <thead>
        <tr>
            <th class="ktgnem_kod"><span>Ktgnem kód</span></th>
            <th style="min-width: 10rem; width: 10rem"><span>Ktgnem név</span></th>
            <th style="min-width: 20rem; width: 20rem"><span>Tárgy</span></th>
            <th style="min-width: 20rem; width: 20rem"><span>Leírás</span></th>
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

    $bwhk = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_bwh', "`fenntartasi_terv_id`='{$sor['recid']}'");
    foreach($bwhk as $bwh){

        for($i=1; $i<13; $i++){
            if($i<10){
                $i = '0'.$i;
            }
            $bwh['ft'.$i] = number_format($bwh['ft'.$i], 0, '', ' ');
        }

        $szamla_html .= '
            <tr id="ft_ft_'.$bwh['recid'].'" data-recid="'.$bwh['recid'].'" class="ftt_menny">
                <td>'.$bwh['ktgnem_id'].'</td>
                <td class="wrap">'.getErtek('nev', 'ktgnem', 'id', $bwh['ktgnem_id']).'</td>
                <td class="wrap">'.$bwh['targy'].'</td>
                <td class="wrap">'.nl2br2($bwh['leir']).'</td>
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
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
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
function fenntartasi_terv_osszesen($sor, $ful_tomb, $sajat_szallodak){

    global $adatbazis_kapcsolat;

    $szamla_html = '
    <div id="fixTable">
        <table class="szamla_cont_tabla nolink fejlec_padd szep_csikos kisbetu osszesen_tabla">
        <thead>
        <tr>
            <th class="ktgnem_kod"><span>Ktgnem kód</span></th>
            <th style="min-width: 10rem; width: 10rem;"><span>Ktgnem név</span></th>
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
    if( !in_array(2120, $sajat_szallodak) || $sor['telephely_id']!=2120 ){
        array_push($kivetelek, 'bwh');
    }
    foreach($ful_tomb as $tabla_post => $ful_neve){

        if( !in_array($tabla_post, $kivetelek) ){

            $ful_sorok = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_'.$tabla_post, "`fenntartasi_terv_id`='{$sor['recid']}'", '', '', 'ktgnem_id');
            if($ful_sorok){
                foreach($ful_sorok as $f_sor){

                    // Mivel költségnem id szerint group by-oztunk, ezért itt le kell kérni újra a sorokat, mert lehet több azonos költségnem rekord is van adott terv_id-hoz
                    // Első lépésként ezket ezért nullázzuk is
                    for($i=1; $i<13; $i++){
                        if($i<10){
                            $i = '0'.$i;
                        }
                        $f_sor['ft'.$i] = 0;
                    }
                    $akt_ful_sorok = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_'.$tabla_post, "`fenntartasi_terv_id`='{$sor['recid']}' AND `ktgnem_id`='{$f_sor['ktgnem_id']}'");
                    foreach($akt_ful_sorok as $akt_sor){
                        for($i=1; $i<13; $i++){
                            if($i<10){
                                $i = '0'.$i;
                            }
                            $f_sor['ft'.$i] += $akt_sor['ft'.$i];
                        }
                    }

                    $honapok_osszesen = 0;
                    for($i=1; $i<13; $i++){
                        if($i<10){
                            $i = '0'.$i;
                        }
                        $honapok_osszesen += $f_sor['ft'.$i];
                        $f_sor['ft'.$i] = number_format($f_sor['ft'.$i], 0, '', ' ');
                    }
                    $honapok_osszesen = number_format($honapok_osszesen, 0, '', ' ');

                    $szamla_html .= '
                        <tr id="ft_ft_'.$tabla_post.$f_sor['recid'].'" data-recid="'.$f_sor['recid'].'" class="ftt_menny">
                            <td>'.$f_sor['ktgnem_id'].'</td>
                            <td class="wrap">'.getErtek('nev', 'ktgnem', 'id', $f_sor['ktgnem_id']).'</td>
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
                        <td>
                            <script>
                                $(document).ready(function () {
                                    energia_szummaz(\'osszesen_tabla\', \''.$tabla_post.'\');
                                });
                            </script>
                        </td>
                        <td>'.$ful_neve.' összesen</td>
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
                            <td></td>
                            <td></td>
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
