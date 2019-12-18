<?php
require_once('../weblap.php');

if( !_jog(JOG_ANYAG_SZAMLAK_OLVAS) ) {
    if( !$_SESSION[get_db_tabla() . 'user'] ) {
        $weblap -> leallit();
        header('Location: index.php');
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');
    $weblap -> leallit();
    exit('Nincs megfelelő jogosultság az oldal eléréséhez!');
}

// Config
$megnevezes             = pathinfo(self());
$megnevezes             = $megnevezes['filename'];
$akt_tomb               = $anyag_szamla_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'számlát';         // Hibaüzenetkor: Biztosan törlöd a $szoveg?

$szamla_html = '';

// Dokumentumok fülhöz kell majd
$tobb_kep = true;
if( $tobb_kep ) {
    function get_tobbkep_darab()    { return 10;         } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '.jpg,.png';} // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;         } // MB-ban
    function get_tobbkep_oszlopnev(){                      // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

}

// Feldolgozásra váró számlák kilistázása
if( isset($_POST['get_feld_var_szamlak']) ) {
    
    if( _jog(JOG_ANYAG_SZAMLAK_KEZEL) ) {
        
        $szamlak = $adatbazis_kapcsolat->s('recid telephely_id szallito_id szla_szam telj_dt netto dev_net rnd_id', 'anyag_szamla', "`stat_id`='".A_SZAMLA_STAT_FELDOLGOZASRA_VAR."'");

        $tablazat = nl2('
            <div style="max-height: 40rem; overflow-y: auto; overflow-x: none;">
                <table id="feld_var_szamlak" class="csikos v2 kijCont csak_egy_kijelolheto fix_fejlec hoveres">
                <tr>
                    <th>Rendelés szám</th>
                    <th>Szálloda</th>
                    <th>Szállító</th>
                    <th>Számlaszám</th>
                    <th>Teljesítés dátuma</th>
                    <th>Nettó érték/<br>Deviza nettó</th>
                </tr>
            ');
        foreach ( $szamlak as $szamla ) {

            $netto = number_format($szamla['netto'], 0, '', ' ');
            if( $szamla['dev_net'] ) {
                $netto = number_format($szamla['dev_net'], 2, ',', ' ');
            }

            $tablazat .= nl2('
                <tr class="kijSor" data-recid="'.$szamla['recid'].'">
                    <td>'.$szamla['rnd_id'].'</td>
                    <td>'.getSzallodaNev($szamla['telephely_id']).'</td>
                    <td>'.getErtek('nev', 'szallito', 'id', $szamla['szallito_id']).'</td>
                    <td>'.$szamla['szla_szam'].'</td>
                    <td>'.datum_kiir($szamla['telj_dt']).'</td>
                    <td class="r">'.$netto.'</td>
                </tr>
                ');
        }

        $tablazat .= nl2('
                </table>
            </div>
            ');
        
        echo json_encode( array('tablazat' => $tablazat) );
        
    } else {
        echo json_encode( array(
                                'hiba'  => 'Önnek nincs joga számlát kiválasztani!',
                                'title' => 'Hiba'
                                )
                        );
    }
    
    
    
    $weblap -> leallit();
    exit;
}

// Kiválasztott számla tételeinek felsorolása
if( isset($_POST['get_szamla_tetelek']) ) {
    
    if( _jog(JOG_ANYAG_SZAMLAK_KEZEL) ) {
        
        // Javasriptből kaptunk egy tömbböt, ilyen formában: tomb[szamla_tetel_recid] = anyagrendeles_recid
        $kapcsolasok = json_decode($_POST['kapcsolasok'], 'tömbbe');

        // Tételek bejárása
        $tetelek = $adatbazis_kapcsolat->s('recid ktgnem_id netto_ert cikk_id mee szlamen szlaear', 'anyag_szamla_tetel', "`anyag_szamla_recid`='{$_POST['szamla_id']}'");

        $tablazat = nl2('
            <div style="max-height: 40rem; overflow-y: auto; overflow-x: none;">
                <table id="feld_var_szamlak" class="csikos v2 fix_fejlec hoveres">
                <tr>
                    <th>Név</th>
                    <th>Mee</th>
                    <th>Rendelt mennyiség</th>
                    <th>Beérkezett mennyiség</th>
                    <th>Már számlázott mennyiség</th>
                    <th>Számlázott mennyiség</th>
                    <th>Rendelési egységár</th>
                    <th>Számlázott egységár</th>
                    <th>Nettó érték</th>
                    <th>Cikkszám</th>
                    <th>Cikknév</th>
                    <th>Rendelés kiválasztása</th>
                    <th>Módosító tétel</th>
                </tr>
            ');
        foreach ( $tetelek as $tetel ) {

            // Ha már van kacsolat a számla tétel és az anyagrendelés tétel között, akkor a rendelés adatait is kitöltjük
            $anyagrend = '';
            if( $kapcsolasok[$tetel['recid']] ) {
                $anyagrend = $adatbazis_kapcsolat->s2('nev rndmen erkmen szlamen rndear', 'anyagigenyles', "`recid`='{$kapcsolasok[$tetel['recid']]}'");
            }
            
            $hibas_sor = '';
            if( $anyagrend['erkmen'] && $anyagrend['erkmen'] < $tetel['szlamen'] ) {
                $hibas_sor = ' class="piros"';
            }
            if( $anyagrend['rndear'] && $anyagrend['rndear'] < $tetel['szlaear'] ) {
                $hibas_sor = ' class="piros"';
            }

            $tablazat .= nl2('
                <tr'.$hibas_sor.' data-recid="'.$tetel['recid'].'">
                    <td>?</td>
                    <td>'.$tetel['mee'].'</td>
                    <td class="nowrap">'.number_format($anyagrend['rndmen'], 2, ',', ' ').'</td>
                    <td class="nowrap">'.number_format($anyagrend['erkmen'], 2, ',', ' ').'</td>
                    <td>'.number_format($anyagrend['szlamen'], 4, ',', ' ').'</td>
                    <td class="nowrap">'.number_format($tetel['szlamen'], 2, ',', ' ').'</td>
                    <td class="nowrap">'.number_format($anyagrend['rndear'], 4, ',', ' ').'</td>
                    <td class="nowrap">'.number_format($tetel['szlaear'], 4, ',', ' ').'</td>
                    <td class="nowrap">'.number_format($tetel['netto_ert'], 0, ',', ' ').'</td>
                    <td>'.$tetel['cikk_id'].'</td>
                    <td>'.$anyagrend['nev'].'</td>
                    <td><input type="button" class="gomb alacsony" value="Kiválaszt" onclick="anyag_szamla_tetel_rendeles_kivalaszt(\''.$tetel['recid'].'\')"></td>
                    <td>
                        <div class="cb">
                            <input type="checkbox" class="check" id="modosit_'.$tetel['recid'].'">
                            <label for="modosit_'.$tetel['recid'].'"></label>
                        </div>
                    </td>
                </tr>
                ');
        }

        $tablazat .= nl2('
                </table>
            </div>
            ');

        echo json_encode( array('tablazat' => $tablazat) );
    
    } else {
        echo json_encode( array(
                                'hiba'  => 'Önnek nincs joga számlát kiválasztani!',
                                'title' => 'Hiba'
                                )
                        );
    }
    
    
    $weblap -> leallit();
    exit;
}

// Anyagrendelések kilistázása egy számla tétel alapján
if( isset($_POST['get_anyagrendeles_tetelek']) ) {
    
    if( _jog(JOG_ANYAG_SZAMLAK_KEZEL) ) {
        
        $foglalt_anyagrend_sorok = array();
        $kapcsolasok = json_decode($_POST['kapcsolasok'], 'tömbbe');
        foreach ( $kapcsolasok as $kapcs ) {
            array_push($foglalt_anyagrend_sorok, $kapcs);
        }
    
        $szamla = $adatbazis_kapcsolat->s2('rnd_id', 'anyag_szamla', "`recid`='{$_POST['szamla_id']}'");

        // A rendelés kiválasztás popupon, azokat a rendelés tételeket kell megjeleníteni, melyeknél a rendelés szám megegyezik a kiválasztott számla rendelés számával 
        // és a számlázott mennyiség < beérkezett mennyiség, mert ezeknél a tételeknél várunk még számlát.
        // Továbbá melyeknek részben beérkezett vagy beérkezett a státusza és szállító az, ami a kiválasztott számla szállítója, telephely pedig szintén az előbb kiválasztott számla telephelye.
        $anyagrendelesek = $adatbazis_kapcsolat->s('recid rndid cikk_id cikkmee ikt_szam rndmen erkmen szlamen rndear', 'anyagigenyles', "`rndid`='{$szamla['rnd_id']}' AND `szlamen`<`erkmen`");

        $tablazat = nl2('
            <div style="max-height: 40rem; overflow-y: auto; overflow-x: none;">
                <table id="anyagrend_tetelek" class="csikos v2 kijCont csak_egy_kijelolheto fix_fejlec hoveres">
                <tr>
                    <th>Rendelés szám</th>
                    <th>Cikkszám</th>
                    <th>Cikknév</th>
                    <th>Mee</th>
                    <th>Anyagig. Szám</th>
                    <th>Rendelt mennyiség</th>
                    <th>Beérkezett mennyiség</th>
                    <th>Már számlázott mennyiség</th>
                    <th>Rendelési egységár</th>
                </tr>
            ');
        foreach ( $anyagrendelesek as $rendeles ) {

            $szurke = '';
            if( in_array($rendeles['recid'], $foglalt_anyagrend_sorok) ) {
                $szurke = ' szurke';
            }
            
            $tablazat .= nl2('
                <tr class="kijSor'.$szurke.'" data-recid="'.$rendeles['recid'].'">
                    <td>'.$rendeles['rndid'].'</td>
                    <td>'.$rendeles['cikk_id'].'</td>
                    <td>'.getErtek('nev', 'cikktorzs', 'id', $rendeles['cikk_id']).'</td>
                    <td>'.$rendeles['cikkmee'].'</td>
                    <td>'.$rendeles['ikt_szam'].'</td>
                    <td class="r">'.number_format($rendeles['rndmen'],  2, ',', ' ').'</td>
                    <td class="r">'.number_format($rendeles['erkmen'],  2, ',', ' ').'</td>
                    <td class="r">'.number_format($rendeles['szlamen'], 2, ',', ' ').'</td>
                    <td class="r">'.number_format($rendeles['rndear'],  4, ',', ' ').'</td>
                </tr>
                ');
        }

        $tablazat .= nl2('
                </table>
            </div>
            ');

        echo json_encode( array('tablazat' => $tablazat) );
    
    } else {
        echo json_encode( array(
                                'hiba'  => 'Önnek nincs joga számlát kiválasztani!',
                                'title' => 'Hiba'
                                )
                        );
    }
    
    $weblap -> leallit();
    exit;
}

// Anyagrendelések és számla tételek összekapcsolása
if( isset($_POST['anyag_szamla_tetelek_ment']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Javasriptből kaptunk egy tömbböt, ilyen formában: tomb[szamla_tetel_recid] = anyagrendeles_recid
    $kapcsolasok = json_decode($_POST['kapcsolasok'], 'tömbbe');
    
    // Státusz léptetés
    if( !addStatusz('anyag_szamla_statusz', $_POST['id'], A_SZAMLA_STAT_IGAZOLASRA_VAR) ) {
        $db_error = true;
    }
    
    // Az anyagrendelés sorába hozzáadjuk a már számlázott összeget a meglevőhöz
    // Illetve a számla tétel sorába is letároljuk a rendelés azonosítóját és rnd_id-ját.
    foreach( $kapcsolasok as $kulcs => $kapcs ) {
        
        $szamla_tetel  = $adatbazis_kapcsolat->s2('netto_ert szlamen cikk_id szlaear', 'anyag_szamla_tetel', "`recid`='{$kulcs}'");
        $anyagrendeles = $adatbazis_kapcsolat->s2('ikt_szam rndid erkmen rndear', 'anyagigenyles', "`recid`='{$kapcs}'");
        
        // A számlázott mennyiség nem lehet nagyobb, mint a beérkezett mennyiség!
        if( $anyagrendeles['erkmen'] < $szamla_tetel['szlamen'] ) {
            echo json_encode(array(
                'hiba'  => 'Cikkszám: '.$szamla_tetel['cikk_id'].'<br>A számlázott mennyiség nem lehet nagyobb, mint a beérkezett mennyiség!<br>A számla nem menthető!',
                'title' => 'Hiba',
            ));
            $weblap -> leallit();
            exit;
        }
        
        // Számlázási egységár nem lehet nagyobb a rendelési egységárnál
        if( $anyagrendeles['rndear'] < $szamla_tetel['szlaear'] ) {
            echo json_encode(array(
                'hiba'  => 'Cikkszám: '.$szamla_tetel['cikk_id'].'<br>Számlázási egységár nem lehet nagyobb a rendelési egységárnál!<br>A számla nem menthető!',
                'title' => 'Hiba',
            ));
            $weblap -> leallit();
            exit;
        }
        
        // Számlázott mennyiség növelése
        if( !$szamla_tetel ) { $db_error = true; }
        if( !mysqli_query($db_kapcs, 'UPDATE `'.get_db_tabla().'anyagigenyles` SET `szlamen`=`szlamen`+'.$szamla_tetel['netto_ert'].' WHERE `recid`=\''.$kapcs.'\'') ) {
            $db_error = true;
        }
        
        // Anyagrendelés ID-k eltárolása tételekhez
        $sql = $adatbazis_kapcsolat->u('anyag_szamla_tetel',
                                                            array('anyagigenyles_szam',         'rnd_id'                 ),
                                                            array($anyagrendeles['ikt_szam'],   $anyagrendeles['rndid']  ),
                                        "`recid`='{$kulcs}'");
        if( !$sql ) { $db_error = true; }
    }
    
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
    
    
    $weblap -> leallit();
    exit;
}

// Tartalom
if( !$_GET['ajax'] ) {
    require_once('header.php');
}
require_once('selectek.php');

    if( !$_GET['ajax'] ) {
        echo '<div id="szamlaBetolt">';
    }

    // Az adatlap adatait már a legelején lekérjük
    if( $_GET['id'] ){
        $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");
    }
    
    if( $sor['dok_db'] ) {
        $dok_db  = ' ('.$sor['dok_db'].')';
    }
    if( $sor['megj_db'] ) {
        $megj_db = ' ('.$sor['megj_db'].')';
    }

    if( isset($_GET['modosit']) || isset($_GET['tetel']) || isset($_GET['kifizetesek']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'tetel'         => 'Tételek',
            'kifizetesek'   => 'Kifizetések',
            'dokumentumok'  => 'Dokumentumok'.$dok_db,
            'megjegyzesek'  => 'Megjegyzések'.$megj_db,
        );
        $fulek = '';
        foreach($ful_tomb as $key => $value) {
            
            $active = 'active';
            if( isset($_GET[$key]) ) {
                $active = ' class="active"';
            }

            $fulek .= '<a href="javascript: szamlaBetolt(\''.$_GET['id'].'\', \''.basename(self()).'\', \''.$key.'\'); void(0);"'.$active.' data-oldal="'.$key.'">'.$value.'</a>';
        }

        $szamla_html .= str_replace(
            array('{FULEK}'),
            array( $fulek  ),
            fajlbol_olvas('../sablon/admin/szamla_fulek.html', true)
        );
    }
    
    $js_submit_elott = 'return form_ell_admin();';

    // Gombok kirajzolása minden oldalra
    $gombok = '';
    if( isset($_GET['uj']) && _jog(JOG_ANYAG_SZAMLAK_OLVAS) ) {

        $gombok = nl2('
            <div class="form_kuldes">
                <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
                <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
            </div>
        ');

        $muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 16).'px);"';
        if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
            if( $_COOKIE['csuszkaSzelesseg'] ) {
                $muv_style = ' style="width: calc(100% - '.(($_COOKIE['csuszkaSzelesseg'] + 16)/10).'rem);"';
            }
        }

        // Műveletek box
        $gombok = nl2('
            <div class="muveletek"'.$muv_style.'>
                '.$gombok.'
            </div>
        ');

    // Módosítás gombok
    } else {

        // Js, a törlés gombhoz
	    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';

        // Jogosutságkezelés
        if( true /* !_jog(... anyag számla törlés jog ...) */ ){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Nincs jogosultsága törölni ezt a '.$szoveg.'!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        } else {
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
        }

//        if( _jog(... anyag számla írás jog ...) ){
//            $gombok = nl2('
//                <div class="form_kuldes">
//                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
//                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
//                </div>
//            ').$gombok;
//        }

        $muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 16).'px);"';
        if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
            if( $_COOKIE['csuszkaSzelesseg'] ) {
                $muv_style = ' style="width: calc(100% - '.(($_COOKIE['csuszkaSzelesseg'] + 16)/10).'rem);"';
            }
        }

        // Műveletek box
        $gombok = nl2('
            <div class="muveletek"'.$muv_style.'>
                '.$gombok.'
            </div>
        ');
    }
    
	// Új rekord felvitele
	if( isset($_GET['uj']) ) {

        // FORM
		if( isset($_GET['ujkent']) ) {
            foreach ( $akt_tomb as $ertek ) {
                $sor[$ertek['name']] = $_SESSION[$ertek['name']];
            }
		}
	    $js = js_kiir($akt_tomb, 'form_ell_admin()');

	    $form_sablon = fajlbol_olvas('../sablon/admin/form_uj.html', true);

        // Javascript-es ellenőrzéshez
        $tabla = str_replace('.php', '', basename(self()));

        $rend_mezok = $adatbazis_kapcsolat->s('`column_name`', '`information_schema`.columns', '`table_schema` LIKE  \''.get_db().'\' AND  `table_name` LIKE  \''.get_db_tabla().$tabla.'\'', '', '');
        $rend_mezok = adatbazis2tomb($rend_mezok, 'column_name');

        $sor = array();
        foreach ( $rend_mezok as $rend_mezo ) {
            $sor[$rend_mezo] = '';
        }

        foreach ( $akt_tomb as $tomb ) {
            if( $tomb['type'] == 'autocomplete' ) {
                $sor[$tomb['name'].'_autocomp'] = '';
            }
        }

        $json_sor = array();
        foreach ( $sor as $kulcs => $elem ) {
            $json_sor[$kulcs] = formAdatok_replace($elem);
        }
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

		// Ez elkészíti a $mezok változót
		require('switch.php');

	    $form_mit     = array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{CK_EDITOR}', '{GOMBOK}', '{MUV_STYLE}'   );
	    $form_mivel   = array($js,    $js_submit_elott,    self(),     $mezok,    $megnevezes,    $ckeditor,     $gombok,    $muv_style      );
	    $szamla_html .= str_replace($form_mit, $form_mivel, $form_sablon);

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom'  => $szamla_html,
                'link'      => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

	// Rekord módosítása
    } elseif( isset($_GET['modosit']) ) {

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if( isset($_GET['id_hidden']) ) {
            $_GET['id'] = $_GET['id_hidden'];
        }

	    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");

        // Fizetési módokat szövegesen írjuk ki (amúgy szem szerkeszthető az adatlap)
        switch ($sor['fizmod']) {
            case 'u': $sor['fizmod'] = 'Utalás';    break;
            case 'k': $sor['fizmod'] = 'Készpénz';  break;
        }
        
        foreach ( $akt_tomb as $tomb ) {
            if( $tomb['type'] == 'autocomplete' ) {
                
                if( $tomb['csoport'] ) {
                    $kulso_ertek = $adatbazis_kapcsolat->s2('info', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}' AND `csoport`='{$tomb['csoport']}'", '', '1');
                    $kulso_ertek['nev'] = $kulso_ertek['info'];
                } else {
                    $kulso_ertek = $adatbazis_kapcsolat->s2('recid nev', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}'", '', '1');
                }

                $sor[$tomb['name'].'_autocomp'] = '';
                if( $kulso_ertek ) {
                    $sor[$tomb['name'].'_autocomp'] = $kulso_ertek['nev'];
                }
            }
        }

        $json_sor = array();
        foreach ( $sor as $kulcs => $elem ) {
            $json_sor[$kulcs] = formAdatok_replace($elem);
        }
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

	    // FORM
	    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

		// Form actionja
		$action = self().get_get(array('errnum','kephiba','siker'));

		// Ez elkészíti a $mezok változót
		require('switch.php');

	    $szamla_html .= str_replace(
            array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}', '{UPDATE_DT}'     ),
            array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok,    $sor['update_dt'] ),
            fajlbol_olvas('../sablon/admin/form_modosit.html', true)
        );

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom'  => $szamla_html,
                'link'      => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

        if( $tobb_kep ) {
            require_once('tobb_kep.php');
		}

	// Számla tételek
    } elseif( isset($_GET['tetel']) ) {

        $szamla_html .= nl2('
            <div class="dt naplo">
                <div class="dtr nFejlec">
                    <div class="dtc">Költségnem</div>
                    <div class="dtc r">Nettó érték</div>
                    <div class="dtc r">Deviza nettó érték</div>
                    <div class="dtc">Költséghely</div>
                    <div class="dtc">Rendelés tétel érték</div>
                    <div class="dtc">Módosító tétel érték</div>
                    <div class="dtc">Deviza rendelés tétel érték</div>
                    <div class="dtc">Deviza módosító tétel érték</div>
                    <div class="dtc">Oracle cikkszám</div>
                </div>
        ');
        
        // Tételek felsorolása
        $tetelek = $adatbazis_kapcsolat->s('*', 'anyag_szamla_tetel', "`anyag_szamla_recid`='{$_GET['id']}'");
        foreach ( $tetelek as $tetel ) {
            
            $szamla_html .= nl2('
                <div class="dtr nSor">
                    <div class="dtc">'.getErtek('nev', 'ktgnem', 'id', $tetel['ktgnem_id']).'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['netto_ert'],  0, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['dev_net'], 2, ',', ' ').'</div>
                    <div class="dtc">'.getErtek('nev', 'ktghely', 'id', $tetel['ktghely_id']).'</div>
                    <div class="dtc">'.number_format($tetel['rndert'],  0, ',', ' ').'</div>
                    <div class="dtc">'.number_format($tetel['modert'],  0, ',', ' ').'</div>
                    <div class="dtc">'.number_format($tetel['vnrdert'], 2, ',', ' ').'</div>
                    <div class="dtc">'.number_format($tetel['vmodert'], 2, ',', ' ').'</div>
                    <div class="dtc">'.$tetel['oracikkid'].'</div>
                </div>
            ');
        }
        $szamla_html .= '</div>';
        
        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Kifizetések fül
    } elseif( isset($_GET['kifizetesek']) ) {

        $szamla_html .= nl2('
            <div class="dt naplo">
                <div class="dtr nFejlec">
                    <div class="dtc">Bank</div>
                    <div class="dtc">Kifizetve</div>
                    <div class="dtc r">Bruttó</div>
                    <div class="dtc r">Deviza bruttó</div>
                    <div class="dtc">Pénznem</div>
                    <div class="dtc">Rögzítve</div>
                    <div class="dtc">Rögzítette</div>
                </div>
        ');

        // Tételek felsorolása
        $kifizetesek = $adatbazis_kapcsolat->s('*', 'anyag_szamla_kifizetesek', "`anyag_szamla_recid`='{$_GET['id']}'", 'insert_dt');
        foreach( $kifizetesek as $kifizetes ) {
            
            $szamla_html .= nl2('
                <div class="dtr nSor">
                    <div class="dtc">'.erteklista(ERTEKLISTA_BANK, $kifizetes['bank']).'</div>
                    <div class="dtc">'.datum_kiir($kifizetes['kifizetes_dt']).'</div>
                    <div class="dtc r">'.number_format($kifizetes['br_ertek'], 0, '', ' ').'</div>
                    <div class="dtc r">'.number_format($kifizetes['dev_br'], 2, ',', ' ').'</div>
                    <div class="dtc">'.erteklista(ERTEKLISTA_PENZNEM, $kifizetes['kcs_21']).'</div>
                    <div class="dtc">'.datum_kiir($kifizetes['rogzitve_dt'], 'csak dátum').'</div>
                    <div class="dtc">'.$kifizetes['rogzitette'].'</div>
                </div>
                ');
        }
        $szamla_html .= '</div>';
        
        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }
        
	// Dokumentumok
    } elseif( isset($_GET['dokumentumok']) ) {

        if( $tobb_kep ) {
            require_once('tobb_kep.php');
        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Megjegyzések
    } elseif( isset($_GET['megjegyzesek']) ) {

        // <div class="dt naplo" class nevekből a kijCont kivéve, hogy a megjegyzéseket ne lehessen törölni
        $szamla_html .= nl2('
            <div class="dt naplo" data-tabla="megjegyzes" data-mellek="megjegyzesek">
                <div class="dtr nFejlec">
                    <div class="dtc">Megjegyzés</div>
                    <div class="dtc">Hozzáadta</div>
                    <div class="dtc">Hozzáadva</div>
                </div>
        ');

        $sorok = $adatbazis_kapcsolat->s('recid megj insert_usr insert_dt', 'megjegyzes', "`id` = '".$_GET['id']."' AND `tabla` = '".str_replace('.php', '', basename(self()))."'", 'recid desc');

        if( $sorok ) {
            $i = 1;
            foreach ( $sorok as $sor ) {
                $szamla_html .= nl2('
                    <div id="naploSor-'.$sor['recid'].'" data-id="'.$sor['recid'].'" data-i="'.$i.'" class="dtr nSor kijSor">
                        <div class="dtc">'.htmlspecialchars_decode( nl2br2($sor['megj']) ).'</div>
                        <div class="dtc">'.$sor['insert_usr'].'</div>
                        <div class="dtc">'.datum_kiir($sor['insert_dt']).'</div>
                    </div>
                ');

                $i++;
            }
        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom'  => $szamla_html.$gombok,
                'link'      => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

	}

    if( !$_GET['ajax'] ) {
        echo '</div>';
    }

if( !$_GET['ajax'] ) {
    require_once('footer.php');
}