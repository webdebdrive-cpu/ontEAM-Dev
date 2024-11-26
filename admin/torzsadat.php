<?php
require_once('../weblap.php');

if( !_jog(JOG_TORZSADAT_MEGTEKINT) && !_jog(JOG_TORZSADAT_SAJAT_MEGTEKINT) ) {
    if(!$_SESSION[get_db_tabla().'user']){
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
$akt_tomb               = $torzsadat_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'törzsadatot';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

$szamla_html = '';

// Dokumentumok fülhöz kell majd
$tobb_kep = false;
if( $tobb_kep ) {
    function get_tobbkep_darab()    { return 10;        } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '.jpg,.png';} // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;        } // MB-ban
    function get_tobbkep_oszlopnev(){                     // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

}

// Új napi munkaidó hozzáadása
if( isset($_POST['uj_munkaido']) ) {
    
    // Jogosultság ellenőrzése
    if( !_jog(JOG_TORZSADAT_KEZEL) ) {
        echo json_encode( array(
            'hiba'  => 'Önnek nincs joga Napi munkaidőt hozzáadni!',
            'title' => 'Hiba',
        ) );
        
        $weblap -> leallit();
        exit;
    }
    
    // Adathelyességek ellenőrzése
    if( $_POST['ev']<date('Y') ) {
        echo json_encode( array(
            'hiba'  => 'Az év mező tartalma minimum az aktuális év ('.date('Y').') lehet!',
            'title' => 'Hiba',
            'focus' => 'ev',
        ) );
        
        $weblap -> leallit();
        exit;
    }
    if( $_POST['ho']<1 || $_POST['ho']>12 ) {
        echo json_encode( array(
            'hiba'  => 'Kérem válasszon hónapot!',
            'title' => 'Hiba',
            'focus' => 'ho',
        ) );
        $weblap -> leallit();
        exit;
    }
    if( $_POST['ora']<4 || $_POST['ora']>12 ) {
        echo json_encode( array(
            'hiba'  => 'Az óra mező tartalma minimum 4, maximum 12 lehet.',
            'title' => 'Hiba',
            'focus' => 'ora',
        ) );
        $weblap -> leallit();
        exit;
    }
    
    $datum = $_POST['ev'].'-'.$_POST['ho'].'-01';
    // 
    $van_mar = $adatbazis_kapcsolat -> s2('recid', 'torzsadat_napi_munkaido', "`torzsadat_recid`='{$_POST['recid']}' AND `datum`='{$datum}'");
    if( $van_mar ) {
        echo json_encode( array(
            'hiba'  => date_formaz($datum, '', 'nap nélkül').'<br>Erre a dátumra már van munkaidő rögzítve!',
            'title' => 'Hiba',
            'focus' => 'ho',
        ) );
        $weblap -> leallit();
        exit;
    }
    
    // Időpont beszúrása
    $adatbazis_kapcsolat->i('torzsadat_napi_munkaido',  array('torzsadat_recid', 'datum', 'ora',          'insert_dt', 'insert_usr',                           'update_usr'                           ),
                                                        array($_POST['recid'],   $datum,  $_POST['ora'],  'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'], $_SESSION[get_db_tabla().'teljes_nev'] )
                            );
    echo json_encode( array(
        'siker'  => '1',
    ) );
    
    $weblap -> leallit();
    exit;
}

// Utolsó napi munkaidő törlése
if( isset($_POST['munkaido_torol']) ) {
    
    // Jogosultság ellenőrzése
    if( !_jog(JOG_TORZSADAT_KEZEL) ) {
        echo json_encode( array(
            'hiba'  => 'Önnek nincs joga Napi munkaidőt törölni!',
            'title' => 'Hiba',
        ) );
        
        $weblap -> leallit();
        exit;
    }
    
    $adatbazis_kapcsolat->d('torzsadat_napi_munkaido', "`torzsadat_recid`='{$_POST['recid']}'", 'datum desc', '1');
    
    echo json_encode( array(
        'siker'  => '1',
    ) );
    
    $weblap -> leallit();
    exit;
}

// Saját beosztás táblázat kirajzolása
if( isset($_POST['sajat_beosztas_mutat']) ) {
    
    $tomb = array('ev' => $_POST['ev'], 'ho' => $_POST['ho']);
    echo json_encode(
                        array('html' => get_beosztas_tablazat_torzsadathoz($tomb, $_POST['torzsadat_id']))
                    );
    
    $weblap -> leallit();
    exit;
}

// CSV generálása
if( isset($_GET['csv']) ) {

    // CSV export előkészítése
    list($mibol, $mibe, $filename, $open) = csv_export_elokeszit($megnevezes);
    if( $open ) {

        $sql = 'SELECT
                `nev` as \'Munkavállaló neve\'
                '. csv_export_get_from_tol($megnevezes);
        $query = mysqli_query($db_kapcs, $sql);

        // Nagyobb CSV-kre kérdezzünk rá előzetesen
        $osszes_sor = mysqli_num_rows($query);
        if( !$_GET['mehet'] && $osszes_sor>CSV_EXPORT_LIMIT ) {
            $gomb = '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#navGombok .excel\').attr(\'onclick\', \'csv_general( this, \\\'\\\', \\\'&mehet=1\\\');\'); $(\'.navGombok a.excel\').click();"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">';
            echo json_encode(array('hiba' => 'A CSV export több mint '.number_format(CSV_EXPORT_LIMIT, 0, '', ' ').' sort tartalmaz. Ennek előállítása hosszabb időbe telik majd.<br>Biztosan folytatja?', 'gomb' => $gomb));
            $weblap -> leallit();
            exit;
        } else {
            $sql .= $_SESSION['akt_sql_order'];
        }

        $query      = mysqli_query($db_kapcs, $sql);
        $csvhead    = array();
        while ( $fejlec_nevek = mysqli_fetch_field($query) ) {
            $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
        }
        fputcsv($open, $csvhead, ';');

        $i = 0;
        while( $sorok = mysqli_fetch_assoc($query) ) {

            $csvcont = array();
            foreach( $sorok as $kulcs => $sor ) {
                // Lehet nem volna elég 1-szer sőt kétszer sem ráereszteni a htmlspecialchars_decode-ot
                $sor = htmlspecialchars_decode( $sor );
                while ( strpos($sor, '&amp;') !== false || strpos($sor, '&quot;') !== false ) {
                    $sor = htmlspecialchars_decode( $sor );
                }
                $csvcont[] = iconv($mibol, $mibe.'//TRANSLIT', strip_tags( $sor ) );
            }

            fputcsv($open, $csvcont, ';');

            // 1000 soronként kiíratunk
            if( $i % 1000 == 0 && $i > 0 ) {
                // Progress
                echo json_encode(array('pr' => number_format(($i / $osszes_sor) * 100, 0, '', ''))).PHP_EOL;
                
                // Felszabadítás
                ob_flush();
                flush();
            }

            $i++;
        }

        // Progress
        echo json_encode(array('pr' => '100')).PHP_EOL;

        // Felszabadítás
        ob_flush();
        flush();
    }

    csv_export_lezar($fh, $filename, 'Törzsadatok');
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
    if($sor['dok_db']){
        $dok_db  = ' ('.$sor['dok_db'].')';
    }
    if($sor['megj_db']){
        $megj_db = ' ('.$sor['megj_db'].')';
    }

    if( isset($_GET['modosit']) || isset($_GET['napi_munkaido']) || isset($_GET['beosztasok']) || isset($_GET['szabadsag']) || /*isset($_GET['tortenet']) ||*/ isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'napi_munkaido' => 'Napi munkaidő',
            // 'tortenet'      => 'Történet',
            'beosztasok'    => 'Beosztások',
            'szabadsag'     => 'Szabadság',
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

    // Gombok kirajzolása minden oldalra
    $gombok = '';
    if( isset($_GET['uj']) ) {

        if(_jog(JOG_TORZSADAT_KEZEL)){

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
        }
        
    // Napi munkaidő gombjai
    } elseif( isset($_GET['napi_munkaido']) ) {

        if( _jog(JOG_TORZSADAT_KEZEL) ) {
        
            // Gombok
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gomb zold" value="Új hozzáadása" onclick="csoportMuvelet(\'torzsadat_napi_munkaido_hozzaad\');">
                    <input type="button" class="gomb piros" value="Utolsó sor törlése" onclick="munkaido_torol();">
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
        }
        
    // Módosítás gombok
    } else {

        // Js, a törlés gombhoz
	    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';

        $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';

        if( isset($_GET['modosit']) && _jog(JOG_TORZSADAT_KEZEL) ){
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
                </div>
            ').$gombok;
        }

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

    $js_submit_elott = 'return form_ell_admin();';

	// Új rekord felvitele
	if( isset($_GET['uj']) && _jog(JOG_TORZSADAT_KEZEL) && !$csak_szerkesztheto ) {

        //FORM
		if( isset($_GET['ujkent']) ) {
            foreach ( $akt_tomb as $ertek ) {
                $sor[$ertek['name']]=$_SESSION[$ertek['name']];
			}
		}
	    $js = js_kiir($akt_tomb, 'form_ell_admin()');

	    $form_sablon = fajlbol_olvas('../sablon/admin/form_uj.html', true);

        // Javascript-es ellenőrzéshez
        $tabla = str_replace('.php', '', basename(self()));

        $rend_mezok = $adatbazis_kapcsolat->s('`column_name`', '`information_schema`.columns', '`table_schema` LIKE  \''.get_db().'\' AND  `table_name` LIKE  \''.get_db_tabla().$tabla.'\'', '', '');
        $rend_mezok = adatbazis2tomb($rend_mezok, 'column_name');

        $sor = array();
        foreach($rend_mezok as $rend_mezo) {
            $sor[$rend_mezo] = '';
        }

        foreach($akt_tomb as $tomb) {
            if( $tomb['type'] == 'autocomplete' ) {
                $sor[$tomb['name'].'_autocomp'] = '';
            }
        }

        $json_sor = array();
        foreach($sor as $kulcs => $elem){
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

	// Törzsadat módosítása
    } elseif( isset($_GET['modosit']) ) {

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if( isset($_GET['id_hidden']) ) {
            $_GET['id'] = $_GET['id_hidden'];
        }

        foreach ( $akt_tomb as $tomb ) {
            if( $tomb['type'] == 'autocomplete' ) {

                if($tomb['csoport']){
                    $kulso_ertek = $adatbazis_kapcsolat->s2('info', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}' AND `csoport`='{$tomb['csoport']}'", '', '1');
                    $kulso_ertek['nev'] = $kulso_ertek['info'];
                }else{
                    $kulso_ertek = $adatbazis_kapcsolat->s2('recid nev', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}'", '', '1');
                }

                $sor[$tomb['name'].'_autocomp'] = '';
                if( $kulso_ertek ) {
                    $sor[$tomb['name'].'_autocomp'] = $kulso_ertek['nev'];
                }
            }
        }

        $json_sor = array();
        foreach($sor as $kulcs => $elem){
            $json_sor[$kulcs] = formAdatok_replace($elem);
        }
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

	    //FORM
	    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

		//Form actionja
		$action = self().get_get(array('errnum','kephiba','siker'));

		// Ez elkészíti a $mezok változót
		require('switch.php');

        $jobb_oldal = '';

	    $szamla_html .= str_replace(
            array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}', '{UPDATE_DT}'     ),
            array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok,    $sor['update_dt'] ),
            fajlbol_olvas('../sablon/admin/form_modosit.html', true)
        );

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

        if($tobb_kep){
			require_once('tobb_kep.php');
		}
        

	// Napi munkaidő
    } elseif( isset($_GET['napi_munkaido']) ) {

        if( $_GET['id'] ) {
            $sor = $adatbazis_kapcsolat -> s2('nev', $megnevezes, "`recid`='{$_GET['id']}'");
            // Táblázat kirajzolása
            $szamla_html .= nl2('
                <input type="hidden" id="nev" value="'.$sor['nev'].'">
                <table class="csikos" id="napi_munkaido">
                <tr>
                    <th>Dátum</th>
                    <th>Óra</th>
                </tr>
                ');
            $munkaidok = $adatbazis_kapcsolat->s('*', 'torzsadat_napi_munkaido', "`torzsadat_recid`='{$_GET['id']}'", 'datum');
            foreach( $munkaidok as $munkaido ) {
                $szamla_html .= nl2('
                    <tr>
                        <td>
                            '.datum_kiir($munkaido['datum']).'
                            <input type="hidden" value="'. date_formaz( substr($munkaido['datum'], 0, 7), '', 'csak hónap').'">
                        </td>
                        <td>'.$munkaido['ora'].'</td>
                    </tr>
                    ');
            }
            $szamla_html .= nl2('
                </table>
                ');
        }
        
        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Beosztások
    } elseif( isset($_GET['beosztasok']) ) {

        
        // A táblázat formázása miatt meghívjuk ezt a CSS-t
        $szamla_html .= '<link href="../css/beosztas.css" rel="stylesheet" type="text/css">';
        
        $evek_tol = 2020;
        $evek_ig  = 2030;
        $evek_select = '<select id="ev" class="csillag disableNav" style="margin-right: 2rem">';
        $evek_select .= '<option value="">- Válasszon -</option>';
        for( $i = $evek_tol; $i<$evek_ig; $i++ ) {
            $selected = '';
            if( $i == date('Y') ) {
                $selected = ' selected';
            }
            $evek_select .= '<option'.$selected.' value="'.$i.'">'.$i.'</option>';
        }
        $evek_select .= '</select>';
        
        $honap_select = '<select id="ho" class="csillag disableNav" style="margin-right: 2rem">';
        foreach( $select_honapok as $honap ){
            $selected = '';
            if( $honap['value'] == date('n') ) {
                $selected = ' selected';
            }
            $honap_select .= '<option'.$selected.' value="'.$honap['value'].'">'.$honap['name'].'</option>';
        }
        $honap_select .= '</select>';
        
        
        $szamla_html .= nl2('
                            Év: '.$evek_select.'
                            Hó: '.$honap_select.'
                            <input type="button" value="Mutat" class="gomb alacsony" onclick="sajat_beosztas_mutat()">
                            <div id="tablazat"></div>
                        ');

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Szabadságok
    } elseif( isset($_GET['szabadsag']) ) {

        $szamla_html .= 'Szabadság felülete';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

/*	// Történet
	}elseif(isset($_GET['tortenet'])){

        $szamla_html .= get_tortenet($megnevezes);

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }
*/
	// Dokumentumok
	}elseif( isset($_GET['dokumentumok']) ) {

        $szamla_html .= 'Dokumentumok felülete';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
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
            foreach($sorok as $sor) {
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
                'tartalom' => $szamla_html.$gombok,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	}

    if( !$_GET['ajax'] ) {
        echo '</div>';
    }

if( !$_GET['ajax'] ) {
    require_once('footer.php');
}


// Beosztás táblázat kirajzolása központosítva, nyomtatási nézet miatt
function get_beosztas_tablazat_torzsadathoz( $sor, $torzsadat_id ) {
    
    global $adatbazis_kapcsolat;
    
    $napok_th = '';
    $honap = $sor['ho'];
    if( $honap<10 ) {
        $honap = '0'.$honap;
    }
    // Ünnepnapok és helyettesítő napokat felvesszük egy tömbbe
    $unnepnapok         = $adatbazis_kapcsolat->s('datum', 'beosztas_unnepnapok', "`datum` LIKE '{$sor['ev']}-{$honap}%' AND `tipus` IN (".BEOSZTAS_UNNEPNAP_MUNKASZUNET.", ".BEOSZTAS_UNNEPNAP_PIHENO.")");
    $unnepnapok         = adatbazis2tomb($unnepnapok, 'datum');
    $helyettesito_napok = $adatbazis_kapcsolat->s('datum', 'beosztas_unnepnapok', "`datum` LIKE '{$sor['ev']}-{$honap}%' AND `tipus` IN (".BEOSZTAS_UNNEPNAP_HELYETTES.")");
    $helyettesito_napok = adatbazis2tomb($helyettesito_napok, 'datum');

    $datumok_tomb = array();
    $napok_szama = date('t', strtotime($sor['ev'].'-'.$honap.'-01'));
    for( $i = 0; $i<$napok_szama; $i++ ) {

        $nap = $nap_nulakkal = $i+1;
        if( $nap_nulakkal<10 ) {
            $nap_nulakkal = '0'.$nap_nulakkal;
        }

        $sarga = '';
        $datum = $sor['ev'].'-'.$honap.'-'.$nap_nulakkal;
        // Szombat vasárnap kiemelve
        $het_napja = date('w', strtotime($sor['ev'].'-'.$honap.'-'.$nap_nulakkal));
        if( ($het_napja == 0 || $het_napja == 6) && !in_array($datum, $helyettesito_napok) ) {
            $sarga = ' class="sarga"';

        // Ünnepnapok szintúgy
        } else if( in_array($datum, $unnepnapok) ) {
            $sarga = ' class="sarga"';
        }
        array_push($datumok_tomb, array('datum' => $sor['ev'].'-'.$honap.'-'.$nap_nulakkal, 'sarga' => $sarga));

        $nap_neve = nap_neve($sor['ev'], $honap, $nap_nulakkal);
        $napok_th .= '<th'.$sarga.'>'.$nap.'<br>'.$nap_neve.'</th>';
    }

    $jobb_oldal .=
        nl2('
        <div id="fixTable">
            <table class="beosztas">
            <tr>
                '.$napok_th.'
                <th title="Munkanapok * napi munkaidő">Terv<br>óra</th>
                <th title="Beosztott napok * műszakok óraszáma">Tény<br>óra</th>
                <th title="Tény órák - terv órák">Egyenleg</th>
            </tr>
        ');
    
    $muszakok         = array();
    $szabadnapok      = array();
    $napok_td         = '';
    $orak             = 0;
    $munkanapok_szama = 0;
    foreach( $datumok_tomb as $datum ) {
        
        $naptar_adatok = $adatbazis_kapcsolat->s2('*', 'beosztas_naptar', "`beosztas_nevek_recid` IN (SELECT `recid` FROM `".get_db_tabla()."beosztas_nevek` WHERE `torzsadat_recid`='{$torzsadat_id}') AND `datum`='{$datum['datum']}'");
        array_push($muszakok, $naptar_adatok['beosztas_muszak_recid']);
        if( !$naptar_adatok['beosztas_muszak_recid'] && $naptar_adatok['jel'] ) {
            array_push($szabadnapok, $naptar_adatok['jel']);
        }
        
        $class   = '';
        if( $datum['sarga'] ) {
            $class = ' sarga';
        } else {
            $munkanapok_szama++;
        }
        
        $szin = '';
        if( $naptar_adatok['szin'] ) {
            $szin = ' style="background-color: #'.$naptar_adatok['szin'].'"';
        }
        $orak += $naptar_adatok['ora'];

        $napok_td .= '<td class="'.$class.'"'.$szin.'>'.$naptar_adatok['jel'].'</td>';
    }

    // A tervórák első körben a munkanapok (nem sárga hátterűek) * 8 órával. Aztán majd lehet az illető aktuális napi munkaidejével szorozzuk fel.
    $napi_munkaido = $adatbazis_kapcsolat->s2('ora', 'torzsadat_napi_munkaido', "`torzsadat_recid`='{$torzsadat_id}' AND `datum`<='{$sor['ev']}-{$honap}-01'", 'datum desc', '1');

    $terv_orak = $munkanapok_szama * $napi_munkaido['ora'];

    $jobb_oldal .= nl2('
        <tr>
            '.$napok_td.'
            <td class="nowrap">'.number_format($terv_orak, 0, '', ' ').'</td>
            <td class="nowrap">'.number_format($orak, 0, '', ' ').'</td>
            <td class="nowrap">'.number_format($orak-$terv_orak, 0, '', ' ').'</td>
        </tr>
        ');

    $jobb_oldal .= nl2('
            </table>
        </div>
        ');
    
    // Műszakok összestése
    if( $muszakok || $szabadnapok ) {
        
        $volt_muszak    = false;
        $muszakok_html  = '';
        $muszakok_html .= nl2('
            <h2 style="margin-top: 2rem;">Műszakok</h2>
            <div class="muszakok">
            ');
        $muszakok = array_unique($muszakok);
        foreach( $muszakok as $muszak ) {
            if( $muszak['beosztas_muszak_recid'] ) {
                $muszak = $adatbazis_kapcsolat->s2('*', 'beosztas_muszak', "`recid`='{$muszak['beosztas_muszak_recid']}'");
                $muszakok_html .= '<div>'.getSzallodaNev($muszak['telephely_id']).' <span style="background: #'.$muszak['szin'].'; padding: 0 1rem;">'.$muszak['nev'].' ('.$muszak['jel'].' '.$muszak['tol'].'-'.$muszak['ig'].')<span></div>';
                $volt_muszak = true;
            }
        }
        
        $szabadnapok = array_unique($szabadnapok);
        foreach( $szabadnapok as $szabadnap ) {
            if( $szabadnap == BEOSZTAS_FIX_BETEGSEG_JEL ) {
                $muszakok_html .= '<div>Betegség (B)</div>';
                $volt_muszak = true;
            }
            if( $szabadnap == BEOSZTAS_FIX_PIHENONAP_JEL ) {
                $muszakok_html .= '<div>Pihenőnap (P)</div>';
                $volt_muszak = true;
            }
            if( $szabadnap == BEOSZTAS_FIX_SZABADNAP_JEL ) {
                $muszakok_html .= '<div>Szabadnap (SZ)</div>';
                $volt_muszak = true;
            }
        }
        $muszakok_html .= nl2('</div>');
        
    }
    if( $volt_muszak ) {
        $jobb_oldal .= $muszakok_html;
    }
    
    return $jobb_oldal;
}