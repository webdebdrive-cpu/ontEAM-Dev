<?php
require_once('../weblap.php');

if( !_jog(JOG_ESZKOZ_MEGTEKINT) ) {
    if(!$_SESSION[get_db_tabla().'user']){
        $weblap -> leallit();
        header('Location: index.php');
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');
    $weblap -> leallit();
    exit('Nincs megfelelő jogosultság az oldal eléréséhez!');
}

if( isset($_GET['obj']) ) {
    $megnevezes             = 'obj';
    $akt_tomb               = $obj_tomb;
    $torles                 = $megnevezes.'tid';
    $szoveg                 = 'helyiséget';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?
} else {
    $megnevezes             = pathinfo(self());
    $megnevezes             = $megnevezes['filename'];
    $akt_tomb               = $eszkozkezeles_tomb;
    $torles                 = $megnevezes.'tid';
    $szoveg                 = 'gépet';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?
}

$szamla_html = '';

// Dokumentumok fülhöz kell majd
$tobb_kep = true;
if($tobb_kep){
    function get_tobbkep_darab()    { return 10;        } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '';        } // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;        } // MB-ban
    function get_tobbkep_oszlopnev(){                     // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

}

// Engedély dátum megadása
if ($_POST['engedelyDatum']) {

    //Dátumok ellenőrzése
    if($_POST['kezdet']!='0000-00-00'){
        if(!valos_datum_e($_POST['kezdet'])){
            echo json_encode(array(
                'hiba' => 'Érvénytelen hatályba lépés dátumot adott meg!',
                'gomb' => '<input class="link gomb" value="Ok" onclick="alert2(); $(\'#esz_eng_kezdet\').focus();" type="button">'
            ));

            $weblap->leallit();
            exit();
        }
    }
    if($_POST['veg']!='0000-00-00'){
        if(!valos_datum_e($_POST['veg'])){
            echo json_encode(array(
                'hiba' => 'Érvénytelen hatályosság lejártát dátumot adott meg!',
                'gomb' => '<input class="link gomb" value="Ok" onclick="alert2(); $(\'#esz_eng_veg\').focus();" type="button">'
            ));

            $weblap->leallit();
            exit();
        }
    }

    if($_POST['kezdet']!='0000-00-00' && $_POST['veg']!='0000-00-00'){
        if($_POST['kezdet']>$_POST['veg']){
            echo json_encode(array(
                'hiba' => 'A kezdődátum nem lehet későbbi a végdátumtól!',
                'gomb' => '<input class="link gomb" value="Ok" onclick="alert2(); $(\'#esz_eng_kezdet\').focus();" type="button">'
            ));

            $weblap->leallit();
            exit();
        }
    }

    $_POST['kezdet'] = str_replace('.', '-', $_POST['kezdet']);
    $_POST['veg']    = str_replace('.', '-', $_POST['veg']);
    $adatbazis_kapcsolat->u($megnevezes.'_eng_dok', array('kezdet', 'veg'), array($_POST['kezdet'], $_POST['veg']), "`recid`='{$_POST['recid']}'");

    $weblap->leallit();
    exit();
}

// Bal oldali navpanel frissítése, könyvtárszerkezet újra rajzolása
if (isset($_POST['bal_panel_frissit'])) {

    $bejegyzesek = array();
    $sorok = mysqli_query($db_kapcs, $_SESSION['akt_sql']);
    while($sor = mysqli_fetch_assoc($sorok)){
        array_push($bejegyzesek, $sor);
    }

    if( $_POST['obj'] ) {
        $_GET['obj'] = true;
    }

    echo json_encode(array(
        'html' => '<div class="szamlaLapozas fent clearFix"></div>'.eszkozkezelesLista( $bejegyzesek, $megnevezes ).'<div class="szamlaLapozas clearFix"></div>'
    ));

    $weblap->leallit();
    exit();
}

// Foglalt azonosító ellenőrzése
if( $_POST['azon_ell'] ) {

    $_POST['azon'] = trim($_POST['azon']);
    if($_POST['azon']){
        if($adatbazis_kapcsolat->s('recid', $megnevezes, "`azon`='{$_POST['azon']}' AND `recid`!='{$_POST['recid']}'")){
            echo json_encode(array(
                'hiba' => 'Ez az azonosító már foglalt, válasszon másikat!',
                'gomb' => '<input class="link gomb" value="Ok" onclick="alert2(); $(\'#azon\').focus();" type="button">'
            ));
            $weblap->leallit();
            exit();
        }
    }
    
    echo json_encode(array(
        'statusz' => 'siker'
    ));

    $weblap->leallit();
    exit();
}

// CSV generálása
if (isset($_GET['csv'])) {

    $mibol   = get_web_charset();
    $mibe    = 'windows-1250';

    if( !is_dir('export') ) {
        mkdir('export');
    }

    if( !is_dir('export/csv') ) {
        mkdir('export/csv');
    }

    // Régi fájlok törlése
    $utvonal = 'export/csv/';
    $fajlok = konyvtar_bejar($utvonal);
    foreach($fajlok as $fajl){
        if(filemtime($fajl)<(time()-60*60)){
            unlink($fajl);
        }
    }

    $fajlnev = $utvonal.'csv'.time().'.csv';
    $open = fopen($fajlnev,'a');
    fwrite($open, "sep=;\n");
    if($open){
        
        // Fejléc bellítása
        $csvhead    = array();
        array_push($csvhead, iconv2($mibol, $mibe, 'Szálloda'));
        array_push($csvhead, iconv2($mibol, $mibe, 'Szint'));
        array_push($csvhead, iconv2($mibol, $mibe, 'Helyiség'));
        array_push($csvhead, iconv2($mibol, $mibe, 'Eszköz'));
        fputcsv($open, $csvhead, ';');
        
        // Általános eset, ha a láthatóságomba tartozó szállodákat exportáljuk
        $login_szallodai = get_akt_login_szallodai();
        $where = "`id` IN (".implode(',', $login_szallodai).")";
        // Elvileg csak szálloda szűrésről lehet szó itt az eszközkezelés felület alatt
        if($_GET['mezo']=='telephely_id' && $_GET['op']=='equal' ){
            $where = "`id`='{$_GET['felt']}'";
        }
        $szallodak = $adatbazis_kapcsolat->s('id nev', 'telephely', $where, 'nev');
        foreach($szallodak as $szalloda){
            
            // Szálloda felvétele CSV-be
            $csvsor    = array();
            array_push($csvsor, iconv2($mibol, $mibe, htmlspecialchars_decode($szalloda['nev'])));
            fputcsv($open, $csvsor, ';');
            
            $szintek = $adatbazis_kapcsolat->s('recid nev', 'obj', "`szulo`='0' AND `telephely_id`='{$szalloda['id']}'", 'sor');
            foreach($szintek as $szint) {
                
                // Szint felvétele CSV-be
                $csvsor    = array();
                array_push($csvsor, '');
                array_push($csvsor, iconv2($mibol, $mibe, htmlspecialchars_decode($szint['nev'])));
                fputcsv($open, $csvsor, ';');
                
                $objektumok = $adatbazis_kapcsolat->s('recid nev', 'obj', "`szulo`='{$szint['recid']}'", 'sor');
                foreach($objektumok as $obj) {
                    
                    // Objektum felvétele CSV-be
                    $csvsor    = array();
                    array_push($csvsor, '');
                    array_push($csvsor, '');
                    array_push($csvsor, iconv2($mibol, $mibe, htmlspecialchars_decode($obj['nev'])));
                    fputcsv($open, $csvsor, ';');
                    
                    $eszkozok = $adatbazis_kapcsolat->s('nev', 'eszkozkezeles', "`telephely_id`='{$szalloda['id']}' AND `obj_id`='{$obj['recid']}'", 'nev');
                    foreach($eszkozok as $eszkoz){
                        
                        // Eszköz felvétele CSV-be
                        $csvsor    = array();
                        array_push($csvsor, '');
                        array_push($csvsor, '');
                        array_push($csvsor, '');
                        array_push($csvsor, iconv2($mibol, $mibe, htmlspecialchars_decode($eszkoz['nev'])));
                        fputcsv($open, $csvsor, ';');
                        
                    }
                    
                }
                
            }
            
            
        }

    }
    fclose($open);

    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=$megnevezes.csv");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo fajlbol_olvas($fajlnev);

    $weblap->leallit();
    exit();
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

    // Engedélyek darabszáma
    $eng_db = '';
    $dok_sorok = $adatbazis_kapcsolat->s('recid utvonal', $megnevezes.'_eng_dok', "`{$megnevezes}_id` = '".$_GET['id']."'");
    if( $dok_sorok ) {
        $eng_db = 0;

        foreach($dok_sorok as $dok_sor) {
            if( is_file('../'.get_utvonal().$megnevezes.'_eng/'.$dok_sor['utvonal']) ) {
                $eng_db += 1;
            }
        }

        $eng_db = ' ('.$eng_db.')';
    }

    if( isset($_GET['modosit']) || isset($_GET['megjegyzesek']) || isset($_GET['dokumentumok']) || isset($_GET['naptar']) || isset($_GET['esemenyek']) || isset($_GET['engedelyek']) || isset($_GET['felmeresi']) || isset($_GET['terkep']) || isset($_GET['obj']) ) {
        // Számla fülek
        if( isset($_GET['obj']) ) {
            $ful_tomb = array(
                'obj' => 'Adatlap'
            );
        } else {
            $ful_tomb = array(
                'modosit'       => 'Adatlap',
                'dokumentumok'  => 'Dokumentumok'.$dok_db,
                'esemenyek'     => 'Események',
                'engedelyek'    => 'Engedélyek'.$eng_db,
                'felmeresi'     => 'Felmérési terv',
                'terkep'        => 'Térkép',
                'megjegyzesek'  => 'Megjegyzések'.$megj_db,
            );
        }

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
        if(_jog(JOG_ESZKOZ_KEZEL)){
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

    // Módosítás gombok
    } else {

        // Js, a törlés gombhoz
        $gombok = '';
        // Ha van olyan élő munkalap, amin ez az eszköz van megjelölve, akkor nem törölhető
        if( isset($_GET['obj']) ){
            $hiba_szoveg = 'a helyiségre';
            $van_elo_ml_on  = $adatbazis_kapcsolat->s2('recid', 'munkalap',      "`obj_id`='{$_GET['id']}'", '', '1');
            $van_elo_bej_en = $adatbazis_kapcsolat->s2('recid', 'bejelentes',    "`obj_id`='{$_GET['id']}'", '', '1');
            $van_eszkoze    = $adatbazis_kapcsolat->s2('recid', 'eszkozkezeles', "`obj_id`='{$_GET['id']}'", '', '1');
            $van_helyisege  = $adatbazis_kapcsolat->s2('recid', 'obj', "`szulo`='{$_GET['id']}'", '', '1');
        }else{
            $hiba_szoveg = 'az eszközre';
            $van_elo_ml_on  = $adatbazis_kapcsolat->s2('recid', 'munkalap',   "`gep_id`='{$_GET['id']}'", '', '1');
            $van_elo_bej_en = $adatbazis_kapcsolat->s2('recid', 'bejelentes', "`gep_id`='{$_GET['id']}'", '', '1');
        }
        if( $van_eszkoze ){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Ehhez a helyiséghez eszköz van rendelve, így nem törölhető. Előbb törölje az eszközeit!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        }elseif( $van_helyisege ){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Ehhez a helyiséghez további helyiségek tartoznak, így nem törölhető. Előbb törölje a helyiségeit!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        }elseif( $van_elo_ml_on || $van_elo_bej_en ){
            
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Erre '.$hiba_szoveg.' folyamatban lévő munkalap van, nem törölhető. Előbb zárja le a munkalapot!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        
        }else{
            
            $gombok .= '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';
            if( isset($_GET['obj']) ){
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Szálloda: '. getSzallodaNev($sor['telephely_id']).'<br>Név: '.alert_kerdesbe_sztring( $sor['nev'] ).'<br><br>Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
            }else{
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Szálloda: '. getSzallodaNev($sor['telephely_id']).'<br>Helyiség: '.alert_kerdesbe_sztring( getErtek('nev', 'obj', 'recid', $sor['obj_id']) ).'<br>Gépnév: '.alert_kerdesbe_sztring( $sor['nev'] ).'<br>Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
            }
            
        }

        if(!$nem_szerkesztheti && (isset($_GET['modosit']) || isset($_GET['obj'])) && _jog(JOG_ESZKOZ_KEZEL)){
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


    // Eszközkezelésnél, az űrlap elpostolása előtt még ellenőrizzük, hogy az azonosító nem foglalt-e.
    // Egy ajax hívástbevárva tudjuk meg, hogy fogalalt-e vagy sem, és ha nem az, akkor újra elpostoljuk a formot de már egy az eredeti JS fügvny ellenőrzéssel.
    if( !isset($_GET['obj']) ) {
        $js_submit_elott = '
            mehet_e = false;
            uj_form_ajax = $.ajax({
                url : \''.basename(self()).'\',
                type: \'POST\',
                data: {
                    azon    : $(\'#azon\').val(),
                    recid   : \''.$_GET['id'].'\',
                    azon_ell: new Date().getTime()
                },
                success: function (response) {
                    if(response.substring(0, 6)==\'SESKIL\'){ location.href = \'index.php\'; }
                    response = JSON.parse(response);
                    if(response.hiba){
                        alert2(response.hiba, response.gomb, \'Hiba\');
                        $(\'#azon\').addClass(\'hibas_ide_focus\');
                        mehet_e = false;
                    }else{
                        mehet_e = true;
                    }
                }
            });

            $.when(
                uj_form_ajax
            ).then(function() {
                if(mehet_e){
                    szurkit();
                    $(\'#szerk_form\').attr(\'onsubmit\', \'return form_ell_admin()\');
                    $(\'#szerk_form\').submit();
                }else{
                    return false;
                }
            } );

            return false;
        ';
    } else {
        $js_submit_elott = '
            szurkit();
            $(\'#szerk_form\').attr(\'onsubmit\', \'return form_ell_admin()\');
            $(\'#szerk_form\').submit();

            return false;
        ';
    }

	// Új eszköz felvitele
	if(isset($_GET['uj']) && _jog(JOG_ESZKOZ_KEZEL)){

	    // FORM
		if(isset($_GET['ujkent'])){
			foreach($akt_tomb as $ertek){
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

        // Új feltöltéskor az $akt_tomb mindig az eszközkezelés, még akkor is, ha egy obj-on (helyiségen) állunk. Emiatt kell itt felülírni.
        $akt_tomb = $eszkozkezeles_tomb;
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
                'tartalom' => $szamla_html,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

	// Eszköz módosítása
    }elseif(isset($_GET['modosit']) || isset($_GET['obj'])) {

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
        }

        foreach($akt_tomb as $tomb) {
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
                'tartalom' => $szamla_html,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

        if($tobb_kep){
			require_once('tobb_kep.php');
		}

    // Megjegyzések
	}elseif(isset($_GET['megjegyzesek'])){

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
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Dokumentumok
	}elseif( isset($_GET['dokumentumok']) ) {

        if( $tobb_kep ) {
            require_once('tobb_kep.php');
        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Események
	}elseif(isset($_GET['esemenyek'])){

        // Adott géphez tartozó bejelentések
        $bejelentes_html = '';
        $bejelentesek = $adatbazis_kapcsolat->s('recid ikt_szam hiba_id', 'bejelentes', "`gep_id`='{$_GET['id']}'", 'ikt_szam');
        foreach ($bejelentesek as $bejelentes){

            $hiba = $adatbazis_kapcsolat->s2('info', 'erteklista', "`recid`='{$bejelentes['hiba_id']}'");
            $bejelentes_html .= '
                <tr>
                    <td class="l"><a href="bejelentes.php?id='.$bejelentes['recid'].'&amp;modosit" target="_blank">'.$bejelentes['ikt_szam'].'</a></td>
                    <td class="l">'.$hiba['info'].'</td>
                </tr>
                ';
        }
        if($bejelentes_html){
            $bejelentes_html = '
                <h3>Bejelentések</h3>
                <table class="csikos" style="margin: 0.3rem 0 3rem 0">
                <tr>
                    <th>Bejelentés szám</th>
                    <th>Bejelentett hiba</th>
                </tr>
                '.$bejelentes_html.'
                </table>
                ';
        }

        // Adott géphez tartozó munkalapok
        $munkalap_html = '';
        $munkalapok = $adatbazis_kapcsolat->s('recid ikt_szam text', 'munkalap', "`gep_id`='{$_GET['id']}'", 'ikt_szam');
        foreach ($munkalapok as $munkalap){

            $munkalap_html .= '
                <tr>
                    <td class="l"><a href="munkalap.php?id='.$munkalap['recid'].'&amp;modosit" target="_blank">'.$munkalap['ikt_szam'].'</a></td>
                    <td class="l">'.nl2br2($munkalap['text']).'</td>
                </tr>
                ';
        }
        if($munkalap_html){
            $munkalap_html = '
                <h3>Munkalapok</h3>
                <table class="csikos" style="margin: 0.3rem 0 3rem 0">
                <tr>
                    <th>Munkalap szám</th>
                    <th>Leírás</th>
                </tr>
                '.$munkalap_html.'
                </table>
                ';
        }

        // Adott géphez tartozó ütemezett feladatok
        $uf_html = '';
        $ufk = $adatbazis_kapcsolat->s('recid id nev', 'utemezett_feladatok', "`gep_id`='{$_GET['id']}'", 'id');
        foreach ($ufk as $uf){

            $uf_html .= '
                <tr>
                    <td class="l"><a href="utemezett_feladatok.php?id='.$uf['recid'].'&amp;modosit" target="_blank">'.$uf['id'].'</a></td>
                    <td class="l">'.$uf['nev'].'</td>
                </tr>
                ';
        }
        if($uf_html){
            $uf_html = '
                <h3>Ütemezett feladatok</h3>
                <table class="csikos" style="margin: 0.3rem 0 3rem 0">
                <tr>
                    <th>Feladatszám</th>
                    <th>Feladat</th>
                </tr>
                '.$uf_html.'
                </table>
                ';
        }

        $szamla_html .= $bejelentes_html.$munkalap_html.$uf_html;

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Engedélyek fül
	}elseif(isset($_GET['engedelyek'])){

        if( $tobb_kep ) {
            require_once('tobb_kep.php');
        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	//Felmérési terv
	}elseif(isset($_GET['felmeresi'])){

        $szamla_html .= 'Felmérési terv felülete';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }


	// Térkép
	}elseif(isset($_GET['terkep'])){

        $szamla_html .= 'Térkép felülete';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
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
