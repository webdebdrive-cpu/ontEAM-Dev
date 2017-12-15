<?php
require_once('../weblap.php');

if( !_jog(JOG_ERTEKLISTAK_MEGTEKINT) ) {
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
$akt_tomb               = $ktgnem_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'költségnemet';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

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
$formazatlan_szam       = array('nev', 'csz', 'tel', 'fax', 'mobil');          //Azok az oszlopneveket sorold fel, melyek numerikus értéket is tartalmazhatnak, de nem szeretnéd, ha lista nézetben ezres csoportra bontaná

$szamla_html = '';

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
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

}

// Foglalt ID ellenőrzése
if (isset($_GET['id_foglalt_e'])) {

    if($adatbazis_kapcsolat->s('id', $megnevezes, "`id`='{$_GET['id']}'")){
        echo json_encode(array(
            'hiba' => tranzakcioHiba()
        ));
    }
    $weblap -> leallit();
    exit;
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

    //Régi fájlok törlése
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

        $sql = 'SELECT
                `'.get_db_tabla().'ktgnem`.`id` as \'id\',
                `nev` as \'Érték\'
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

    $js_submit_elott = 'return form_ell_admin();';
    
    // Gombok kirajzolása minden oldalra
    $gombok = '';
    if( isset($_GET['uj']) ) {

        // Gombok
        $gombok .= nl2('
            <div class="form_kuldes">
                <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="$(\'#szerk_form\').submit()">
                <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="formModositva=false; history.back();">
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

        $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, 'recid='.$_GET['id']);

        // Jogosutságkezelés: csak az törölheti, aki létrehozta
        if(!_jog(JOG_ERTEKLISTAK_KEZEL)){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Nincs jogosultsága törölni ezt a '.$szoveg.'!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        }else{
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
        }
        
        if(_jog(JOG_ERTEKLISTAK_KEZEL)){
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="$(\'#szerk_form\').submit()">
                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="formModositva=false; location.reload();">
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
    
	// Új termék felvitele
	if(isset($_GET['uj']) && _jog(JOG_ERTEKLISTAK_KEZEL) ){

        // NAVIGÁCIÓ
        $navi_sablon  = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit     = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel   = array( $navi,             '',         $gyk2 );
        $szamla_html .= str_replace($navi_mit, $navi_mivel, $navi_sablon);

	    // HIBAüZENETEK
	    require_once('info.php');

	    // FORM
		if(isset($_GET['ujkent'])){
			foreach($akt_tomb as $ertek){
				$sor[$ertek['name']]=$_SESSION[$ertek['name']];
			}
		}
	    $js = js_kiir($akt_tomb, 'form_ell_admin()');

        //Ckeditort csak akkor húzzuk be, ha kell
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

	// Ktgnem módosítása
	}elseif(isset($_GET['modosit'])) {

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
        }

	    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, 'recid='.$_GET['id']);

        if( $sor['datetime'] ) {
            $_SESSION['formMod'] = array(
                'datetime' => $sor['datetime'],
                'oldal' => basename(self())
            );
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
            $json_sor[$kulcs] = nl2(str_replace(array("\r\n", "'"), array("#UJSOR#", "#APOSZTROF#"), $elem));
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

	    // HIBAüZENETEK
        require_once('info.php');

	    // FORM
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

	    $szamla_html .= str_replace(
            array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}'    ),
            array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok       ),
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

	}

    if( !$_GET['ajax'] ) {
        echo '</div>';
    }

if( !$_GET['ajax'] ) {
    require_once('footer.php');
}
