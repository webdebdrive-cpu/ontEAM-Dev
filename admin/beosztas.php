<?php
require_once('../weblap.php');

if( !_jog(JOG_BEOSZTASOK_MEGTEKINT) ) {
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
$akt_tomb               = $beosztas_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'beosztást';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

// Változók beállítása
$csak_szerkesztheto     = false;                //Ha tiltani szeretnéd, hogy újat tudjon feltölteni, vagy törölni adott bejegyzést
$elonezet               = '';                   //Ha üres nincs előnézet. Egyébként meg az előnézet URL-jét add meg ide, pl.: '../index.php?id='.$_GET['id']
$fajloszlop             = 'kep';                //Kép (vagy kivételes esetekben más fájl) oszlopneve.
$id_latszik             = false;                //A termék ID-ja látsszon-e a felsorolásnál, kibontásnál (van, hogy ügyfél is használja)
$kep_thumb_listaba      = false;                //Lista nézetben legyen-e a képoszlop alapján képthumbnail
$magas                  = get_indexkep_m();     //Kiskép magassága
$sorrendezes            = false;                //Ha sorrendet is be szeretnénk állítani. Adatbázisban sor oszlopot létre kell hozni, jquery-ui.js-t és admin_sorrendez.js-t aktiválni az adminhoz
$szel                   = get_indexkep_sz();    //Kiskép szélessége
$ujkent_szerkesztheto   = true;                 //Szerkesztésnél elérhető egy gomb, melyre kattintva a termékadatai átkerülnek az új feltöltésbe

$ckeditor_js            = '../'.get_utvonal().'wysiwyg/ckeditor.js';    //Ckeditor JS útvonala
$formazatlan_szam       = array('nev', 'csz', 'tel', 'fax', 'mobil');   //Azok az oszlopneveket sorold fel, melyek numerikus értéket is tartalmazhatnak, de nem szeretnéd, ha lista nézetben ezres csoportra bontaná

$szamla_html = '';


// Adott beosztás adatinak lekérése kiíratáshoz
if (isset($_POST['get_beosztas_adatok'])) {

    $naptar     = $adatbazis_kapcsolat->s2('felh_loginnev beosztas tol ig', 'beosztas_naptar', "`recid`='{$_POST['id']}'");
    $szemelyzet = $adatbazis_kapcsolat->s2('nev szemelyzet_id', 'torzsadat', "`felh_loginnev`='{$naptar['felh_loginnev']}'");

    $ret = $szemelyzet['nev'].' - ';
    if($naptar['beosztas']==1){
        require 'selectek.php';
        foreach($select_szemelyzet as $szerepkor){
            if($szerepkor['value']==$szemelyzet['szemelyzet_id']){
                $ret .= $szerepkor['name'];
            }
        }
    }else{
        $ret .= 'Szabadság';
    }

    $ret .= ' | '.substr($naptar['tol'], 0, 16).' - '.substr($naptar['ig'], 0, 16);
    echo $ret;

    $weblap -> leallit();
    exit;
}

// Naptárba tevés
if (isset($_POST['beosztas_naptarba_tesz'])) {

    // Tranzakció indul
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $felh_loginnev = $adatbazis_kapcsolat->s('felh_loginnev', 'torzsadat', "`recid`='{$_POST['felh_loginnev']}'");
    if($felh_loginnev===false){ $db_error = true; }
    $felh_loginnev = $felh_loginnev[0]['felh_loginnev'];

    $_POST['tol'] = str_replace('.', '-', $_POST['tol']);
    $_POST['ig']  = str_replace('.', '-', $_POST['ig']);

    // Előbb nézzük meg, hogy nincs-e ütközés más időponttal
    $utkozes = $adatbazis_kapcsolat->s('id', 'beosztas_naptar', nl2("
                                                                `beosztas_id`='{$_POST['beosztas_id']}' AND
                                                                `felh_loginnev`='$felh_loginnev'        AND
                                                                (
                                                                    (`tol`<'{$_POST['ig']}' AND `ig`>'{$_POST['ig']}') OR
                                                                    (`ig`>'{$_POST['tol']}' AND `tol`<'{$_POST['tol']}') OR
                                                                    (`tol`>'{$_POST['tol']}' AND `ig`<'{$_POST['ig']}')
                                                                )"));

    if($utkozes===false){ $db_error = true; }

    // Ha nincs ütközés, vagy felülírhatjuk, akkor beszúrjuk
    if(!$utkozes || $_POST['felulir']){
        $sql = $adatbazis_kapcsolat->i('beosztas_naptar',   array('beosztas_id',         'tol',        'ig',           'beosztas',         'szerepkor',        'felh_loginnev'),
                                                            array($_POST['beosztas_id'], $_POST['tol'], $_POST['ig'], $_POST['beosztas'], $_POST['szerepkor'], $felh_loginnev)
                                        );
        if($sql===false){ $db_error = true; }

        // Ha felül kell írni, akkor 3 estet le kell kezelni
        if($_POST['felulir']){

            //Ha teljes az átfedés, akkor az eredeti bejegyzést töröljük, hisz egy új jött létre helyette
            $sql1 = $adatbazis_kapcsolat->d('beosztas_naptar', "`beosztas_id`='{$_POST['beosztas_id']}' AND `felh_loginnev`='$felh_loginnev' AND `tol`>'{$_POST['tol']}' AND `ig`<'{$_POST['ig']}'");
            $sql2 = $adatbazis_kapcsolat->d('beosztas_naptar', "`beosztas_id`='{$_POST['beosztas_id']}' AND `felh_loginnev`='$felh_loginnev' AND `tol`<'{$_POST['tol']}' AND `ig`>'{$_POST['ig']}'");

            //Jobbról-balról ha belőg az új beosztás, akkor arrébb toljuk a vég/kezdő időpontot
            $sql3 = $adatbazis_kapcsolat->u('beosztas_naptar', array('tol'), array($_POST['ig']),  "`beosztas_id`='{$_POST['beosztas_id']}' AND `felh_loginnev`='$felh_loginnev' AND `tol`<'{$_POST['ig']}' AND `ig`>'{$_POST['ig']}'  ");
            $sql4 = $adatbazis_kapcsolat->u('beosztas_naptar', array('ig'),  array($_POST['tol']), "`beosztas_id`='{$_POST['beosztas_id']}' AND `felh_loginnev`='$felh_loginnev' AND `ig`>'{$_POST['tol']}' AND `tol`<'{$_POST['tol']}'");
            if($sql1===false || $sql2===false || $sql3===false || $sql4===false){
                $db_error = true;
            }

        }

    // Ha van ütközés, rákérdezünk, hogy biztosan?
    }else{

        //a már megengedtük a felülírást, akkor nem kérdezzük meg újra
        if(!$_POST['felulir']){
            echo json_encode( array('hiba' => 'Az új beosztás időpontja ütközik. Felülírja?', 'gomb' => '<input type="button" class="gomb zold" value="Igen" onclick="beosztas_naptarba_tesz(\''.$_POST['beosztas_id'].'\', \'írd felül\'); alert2();"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">'));
            mysqli_rollback($db_kapcs);
        }
        $weblap -> leallit();
        exit;
    }


    if(!$db_error){
        echo json_encode( array(
                                'siker'  => 'siker',
                                'naptar' => beosztas_naptar($_POST['beosztas_id'])
                                )
                        );
        mysqli_commit($db_kapcs);
    }else{
        echo json_encode( array('hiba' => 'Az adatbázisba írás során hiba történt!'));
        mysqli_rollback($db_kapcs);
    }

    $weblap -> leallit();
    exit;
}

// Beosztás naptárból törlés
if (isset($_POST['naptar_torol'])) {

    // Tranzakció indul
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $beosztas_id = $adatbazis_kapcsolat->s('beosztas_id', 'beosztas_naptar', "`recid`='{$_POST['id']}'");
    if($beosztas_id===false){ $db_error = true; }

    $beosztas_id = $beosztas_id[0]['beosztas_id'];
    $sql = $adatbazis_kapcsolat->d('beosztas_naptar', "`recid`='{$_POST['id']}'");
    if($sql===false){ $db_error = true; }

    if(!$db_error){
        echo json_encode( array('siker' => 'siker', 'naptar' => beosztas_naptar($beosztas_id)) );
        mysqli_commit($db_kapcs);
    }else{
        echo json_encode( array('hiba' => 'Az adatbázisba írás során hiba történt!'));
        mysqli_rollback($db_kapcs);
    }

    $weblap -> leallit();
    exit;
}

// Több kép menedzseléséhez
$tobb_kep = false; // Ha true, akkor js/jquery-ui.js, js/admin_sorrendez.js aktivál és minify!, admin/tobb_kep.php aktivál
if($tobb_kep){
    function get_tobbkep_darab()    { return 10;        } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '.jpg,.png';} // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;        } // MB-ban
    function get_tobbkep_oszlopnev(){                     // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni! 'kepek';       } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!


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
    if($open){

        $lekerdezes_fromtol = substr($_SESSION['akt_sql'], strpos($_SESSION['akt_sql'], 'FROM'));

        if( $_SESSION['nyomtatas_ids'] ) {
            if( strstr($_SESSION['akt_sql'], 'WHERE') ) {
                $lekerdezes_fromtol = str_replace('WHERE', "WHERE `".get_db_tabla()."beosztas`.`recid` IN (".$_SESSION['nyomtatas_ids'].") AND ", $lekerdezes_fromtol);
            } else {
                $lekerdezes_fromtol = str_replace('ORDER', "WHERE `".get_db_tabla()."beosztas`.`recid` IN (".$_SESSION['nyomtatas_ids'].") ORDER", $lekerdezes_fromtol);
            }
        }

        $sql = 'SELECT
                `'.get_db_tabla().'beosztas`.`cim` as \'Cím\',
                `'.get_db_tabla().'beosztas`.`telephely_id` as \'Szálloda\',
                `tol` as \'Időszak tól\',
                `ig` as \'Időszak ig\'
                '.$lekerdezes_fromtol;

        $query      = mysqli_query($db_kapcs, $sql);

        $csvhead    = array();
        while($fejlec_nevek = mysqli_fetch_field($query)){
            $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
        }
        fputcsv($open, $csvhead, ';');

        while( $sorok = mysqli_fetch_assoc($query) ){
            $csvcont = array();
            foreach( $sorok as $kulcs => $sor ) {
                if($kulcs=='Szálloda'){
                    $sor = getErtek('nev', 'telephely', 'id', $sor);
                }
                if($kulcs=='Bejelentett hiba'){
                    $sor = getErtek('info', 'erteklista', 'recid', $sor);
                }
                if($kulcs=='Helyiség'){
                    $sor = getErtek('nev', 'obj', 'recid', $sor);
                }
                if($kulcs=='Gépnév'){
                    $sor = getErtek('nev', 'eszkozkezeles', 'recid', $sor);
                }
                if($kulcs=='Típus'){
                    $sor = erteklista(6, $sor);
                }
                if($kulcs=='Státusz'){
                    $sor = getStatText('munkalap', $sor);
                }

                $csvcont[] = iconv2($mibol, $mibe, strip_tags( htmlspecialchars_decode( $sor ) ) );
            }
            fputcsv($open, $csvcont, ';');
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

    if( isset($_GET['modosit']) || isset($_GET['naptar']) || isset($_GET['tortenet']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {

        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'tortenet'      => 'Történet',
            'naptar'        => 'Naptár',
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
        // Gombok
        $gombok = nl2('
            <div class="form_kuldes">
                <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="szurkit(\'mehet_megsem_rejt\'); $(\'#szerk_form\').submit()">
                <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="szurkit(\'mehet_megsem_rejt\'); formModositva=false; szamlaBetolt($(\'#szamlaList li:first-of-type\').attr(\'data-recid\'), \''.$megnevezes.'.php\');">
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


    } elseif(isset($_GET['naptar'])) {

        $beosztas = $adatbazis_kapcsolat->s2('tol ig', 'beosztas', "`recid`='{$_GET['id']}'");

        if(_jog(JOG_BEOSZTASOK_KEZEL)){
            $gombok = nl2('
                <div class="muveletek" style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 16).'px);">
                    <div class="form_kuldes">
                        <input type="button" class="gombb mentes_gomb" id="mehet_gomb" disabled value="Mentés" onclick="szurkit(\'mehet_megsem_rejt\'); $(\'#szerk_form\').submit()">
                        <input type="button" class="gombb gombb_alert" id="mehet_gomb_kivesz" disabled value="Naptárból kivesz" onclick="naptar_torol_kerdez()">
                        <input type="hidden" name="naptar_torol_id" id="naptar_torol_id" value="">
                        <input type="hidden" name="get_id" id="get_id" value="'.$_GET['id'].'">
                        <input type="hidden" name="idoszak_tol" id="idoszak_tol" value="'.$beosztas['tol'].'">
                        <input type="hidden" name="idoszak_ig" id="idoszak_ig" value="'.$beosztas['ig'].'">
                    </div>
                </div>
            ');
        }

    } else {

	    // Js, a törlés gombhoz
	    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';
	    if($elonezet){
           $gombok .= ' <input type="button" class="gombb" onclick="window.open(\''.$elonezet.'\')" value="Előnézet" title="Előnézet">';
        }
        if($sorrendezes){
            $gombok .= ' <input type="button" class="gombb" onclick="szurkit(); location.href=\''.self().'?sorrend\'" value="Sorrend" title="Sorrendezés">';
        }

        if( !$csak_szerkesztheto ) {
            // Jogosutságkezelés: csak az törölheti, aki létrehozta
            if($sor['loginnev']!=$_SESSION[get_db_tabla().'user']){
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Csak saját bejegyzés törölhető!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
            }else{
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
            }
        }

        if(_jog(JOG_BEOSZTASOK_KEZEL)){
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="szurkit(\'mehet_megsem_rejt\'); $(\'#szerk_form\').submit()">
                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="szurkit(\'mehet_megsem_rejt\'); formModositva=false; szamlaBetolt(\''.$_GET['id'].'\', \''.$megnevezes.'.php\');">
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

	// Új termék felvitele
	if(isset($_GET['uj']) && _jog(JOG_BEOSZTASOK_KEZEL) && !$csak_szerkesztheto){

        $navi_sablon  = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit     = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel   = array( $navi,             '',         $gyk2 );
        $szamla_html .= str_replace($navi_mit, $navi_mivel, $navi_sablon);

	    //HIBAüZENETEK
	    require_once('info.php');

	    //FORM
		if(isset($_GET['ujkent'])){
			foreach($akt_tomb as $ertek){
				$sor[$ertek['name']]=$_SESSION[$ertek['name']];
			}
		}
	    $js = js_kiir($akt_tomb, 'form_ell_admin()');

        // Ckeditort csak akkor húzzuk be, ha kell
        if(is_file($ckeditor_js)){
            $ckeditor = '<script src="'.$ckeditor_js.'"></script>';
        }

	    $form_sablon = fajlbol_olvas('../sablon/admin/form_uj.html', true);

        // Javascipt-es ellenőrzéshez
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

        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($sor).'\'); </script>';

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

	// Beosztás módosítása
	}elseif(isset($_GET['modosit'])) {

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
        }

	    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, 'recid='.$_GET['id']);

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
            $json_sor[$kulcs] = str_replace(array("\r\n", "\r", "\n", "'"), array("#UJSOR#", "#UJSOR#", "#UJSOR#", "#APOSZTROF#"), $elem);
        }
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

        if($id_latszik){
            $navi = '<b>#'.$_GET['id'].'</b> '.$navi;
        }

        // NAVIGÁCIÓ
        $navi_sablon = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit    = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel  = array( $navi,             '',         $gyk2 );
        //echo str_replace($navi_mit, $navi_mivel, $navi_sablon);

	    //HIBAüZENETEK
        require_once('info.php');

	    //FORM
	    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

		// Form actionja
		$action = self().get_get(array('errnum','kephiba','siker'));

        // Ckeditort csak akkor húzzuk be, ha kell
        if(is_file($ckeditor_js)){
            $ckeditor = '<script src="'.$ckeditor_js.'"></script>';
        }

		// Ez elkészíti a $mezok változót
		require('switch.php');

        $jobb_oldal = '';

        $action = 'javascript:beosztas_naptar_hozzaad()';

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

	// Naptár
	}elseif(isset($_GET['naptar'])){

		// Ez elkészíti a $mezok változót
        $akt_tomb = $beosztas_naptar_tomb;

	    // FORM
	    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

        $sor = array();
        $szamla_html .= '<link rel="stylesheet" type="text/css" href="../css/fullcalendar.min.css">';
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($sor).'\'); </script>';

		require('switch.php');

	    $szamla_html .=
            str_replace(
                array('{JS}', '{ACTION}', '{JS_SUBMIT_ELOTT}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}', '{MUV_STYLE}', '{UPDATE_DT}'      ),
                array( $js,    $action,   $js_submit_elott,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok,    $muv_style,    $sor['update_dt']  ),
                fajlbol_olvas('../sablon/admin/form_modosit.html', true)
            ).'<script>$(document).ready(function () { $(\'#szerk_form div.dt\').hide(); });</script>';

        $szamla_html .= beosztas_naptar($_GET['id']);

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

	// Történet
	}elseif(isset($_GET['tortenet'])){

        $szamla_html .= get_tortenet($megnevezes);

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }

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
	}elseif(isset($_GET['megjegyzesek'])){

        $szamla_html .= nl2('
            <div class="dt naplo kijCont" data-tabla="megjegyzes" data-mellek="megjegyzesek">
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
                        <div class="dtc">'.nl2br($sor['megj']).'</div>
                        <div class="dtc">'.$sor['insert_usr'].'</div>
                        <div class="dtc">'.$sor['insert_dt'].'</div>
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
