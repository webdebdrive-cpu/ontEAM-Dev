<?php
require_once('../weblap.php');
//Config
$megnevezes             = pathinfo(self());
$megnevezes             = $megnevezes['filename'];
$akt_tomb               = $bejovo_szamlak_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'számlát';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

//Változók beállítása
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

//Több kép menedzseléséhez
$tobb_kep = false; // Ha true, akkor js/jquery-ui.js, js/admin_sorrendez.js aktivál és minify!, admin/tobb_kep.php aktivál
if($tobb_kep){
    function get_tobbkep_darab()    { return 10;        } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '.jpg,.png';} // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;        } // MB-ban
    function get_tobbkep_oszlopnev(){                     // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return 'kepek';       } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!
}


/** Tartalom */
if( !$_GET['ajax'] ) {
    require_once('header.php');
}
require_once('selectek.php');

    if( !$_GET['ajax'] ) {
        echo '<div id="szamlaBetolt">';
    }

    if( isset($_GET['modosit']) || isset($_GET['naplo']) || isset($_GET['dokumentumok']) ) {
        /* Számla fülek */
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'naplo'         => 'Napló',
            'dokumentumok'  => 'Dokumentumok'
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
    
	//Új termék felvitele
	if(isset($_GET['uj']) && !$csak_szerkesztheto){

        //NAVIGÁCIÓ
	    $gombok = '';
	    //$gombok = '<input type="button" class="gombb" onclick="szurkit(); location.href=\''.self().'?lista\'" value="Lista" title="Lista megtekintése">';
        $gombok .= ' <input type="button" id="visszavonas_gomb" disabled="disabled" class="gombb" onclick="szamlaBetolt(\'1\', \''.basename(self()).'\', \'uj\', true);" value="Visszavonás" title="Visszavonás">';
        $gombok .= ' <input type="button" id="kilepes_gomb" class="gombb_alert" onclick="szurkit(); location.href = \''.basename(self()).'\';" value="Kilépés" title="Kilépés">';

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

        //Ckeditort csak akkor húzzuk be, ha kell
        if(is_file($ckeditor_js)){
            $ckeditor = '<script src="'.$ckeditor_js.'"></script>';
        }

	    $form_sablon = fajlbol_olvas('../sablon/admin/form_uj.html', true);

        /* Javascipt-es ellenőrzéshez */
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

        $gombok = nl2('<div class="form_kuldes">
                          <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="szurkit(\'mehet_megsem_rejt\'); $(\'#szerk_form\').submit()">
                          <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="szurkit(\'mehet_megsem_rejt\'); formModositva=false; szamlaBetolt($(\'#szamlaList li:first-of-type\').attr(\'data-recid\'), \''.$megnevezes.'.php\');">
                      </div>').$gombok;

        $muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 16).'px);"';
        if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
            if( $_COOKIE['csuszkaSzelesseg'] ) {
                $muv_style = ' style="width: calc(100% - '.(($_COOKIE['csuszkaSzelesseg'] + 16)/10).'rem);"';
            }
        }

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

	// Termék módosítása
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

	    // Js, a törlés gombhoz
	    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';
	    if($elonezet){
           $gombok .= ' <input type="button" class="gombb" onclick="window.open(\''.$elonezet.'\')" value="Előnézet" title="Előnézet">';
        }
        if($sorrendezes){
            $gombok .= ' <input type="button" class="gombb" onclick="szurkit(); location.href=\''.self().'?sorrend\'" value="Sorrend" title="Sorrendezés">';
        }
        if($ujkent_szerkesztheto && !$csak_szerkesztheto){
            //$gombok .= ' <input type="button" class="gombb" onclick="document.getElementById(\''.$megnevezes.'ujkent_szerk\').value=\'1\'; document.getElementById(\'szerk_form\').submit()" title="Újként szerkeszt" value="Újként">';
        }
        if(!$csak_szerkesztheto){
            //$gombok .= ' <input type="button" id="rocordUj" class="gombb dn" onclick="szurkit(); location.href=\''.self().'?uj\'" value="Új" title="Új feltöltés">';
        }
        //$gombok .= ' <input type="button" class="gombb" onclick="szurkit(); location.href=\''.self().get_get(array('modosit','errnum','kephiba','siker','id', 'szerkeszt', 'ajax')).'&amp;lista\'" value="Lista" title="Lista megtekintése">';
        $gombok .= ' <input type="button" id="visszavonas_gomb" disabled="disabled" class="gombb" onclick="szamlaBetolt(\''.$sor['recid'].'\', \''.basename(self()).'\', \'modosit\', true);" value="Visszavonás" title="Visszavonás">';

        if( !$csak_szerkesztheto ) {
            //Jogosutságkezelés: csak az törölheti, aki létrehozta
            if($sor['loginnev']!=$_SESSION[get_db_tabla().'user']){
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Csak saját bejegyzés törölhető!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
            }else{
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
            }
        }

        $gombok .= ' <input type="button" id="kilepes_gomb" class="gombb_alert" onclick="szamlaBetolt(\''.$sor['recid'].'\', \''.basename(self()).'\', \'modosit\');" value="Kilépés" title="Kilépés">';

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

        if( isset($_GET['szerkeszt']) ) {
            $gombok = nl2('<div class="form_kuldes">
                              <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="szurkit(\'mehet_megsem_rejt\'); $(\'#szerk_form\').submit()">
                              <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="szurkit(\'mehet_megsem_rejt\'); formModositva=false; szamlaBetolt(\''.$_GET['id'].'\', \''.$megnevezes.'.php\');">
                           </div>').$gombok;
        } else {
            $gombok = '<input type="button" id="recordSzerk" class="gombb dn" onclick="szamlaBetolt(\''.$sor['recid'].'\', \''.basename(self()).'\', \'modosit\', true)" value="Szerkeszés" title="Record szerkesztése">';
        }

        $muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 16).'px);"';
        if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
            if( $_COOKIE['csuszkaSzelesseg'] ) {
                $muv_style = ' style="width: calc(100% - '.(($_COOKIE['csuszkaSzelesseg'] + 16)/10).'rem);"';
            }
        }

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

	// Dokumentumok
	}elseif( isset($_GET['dokumentumok']) ) {

        $akt_tomb = $bejovo_szamlak_dok_tomb;

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
        }

	    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, 'recid='.$_GET['id']);

        $json_sor = array();
        foreach($sor as $kulcs => $elem){
            $json_sor[$kulcs] = str_replace(array("\r\n", "\r", "\n", "'"), array("#UJSOR#", "#UJSOR#", "#UJSOR#", "#APOSZTROF#"), $elem);
        }
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

	    $gombok = '';

        if($id_latszik){
            $navi = '<b>#'.$_GET['id'].'</b> '.$navi;
        }

        // NAVIGÁCIÓ
        $navi_sablon = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit    = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel  = array( $navi,             '',         $gyk2 );
        // echo str_replace($navi_mit, $navi_mivel, $navi_sablon);

	    // HIBAüZENETEK
	    require_once('info.php');

	    // FORM
	    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

		//Form actionja
		$action = self().get_get(array('errnum','kephiba','siker'));

        //Ckeditort csak akkor húzzuk be, ha kell
        if(is_file($ckeditor_js)){
            $ckeditor = '<script src="'.$ckeditor_js.'"></script>';
        }

		// Ez elkészíti a $mezok változót
		require('switch.php');

        $jobb_oldal = '';

	    $szamla_html .= str_replace(
            array('{JS}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}', '{UPDATE_DT}'      ),
            array( $js,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,    '',         $sor['update_dt']  ),
            fajlbol_olvas('../sablon/admin/form_modosit.html', true)
        );

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html,
                'link' => get_get(array('ajax', 'naplo', 'modosit'), true)
            ));
        } else {
            echo $szamla_html;
        }

        if($tobb_kep){
			require_once('tobb_kep.php');
		}

	//Napló
    }elseif(isset($_GET['naplo'])) {

        $szamla_html .= nl2('
            <div class="dt naplo">
                <div class="dtr nFejlec">
                    <div class="dtc">Iktató szám</div>
                    <div class="dtc">Számla szám</div>
                    <div class="dtc">Státusz</div>
                    <div class="dtc">Részleg</div>
                    <div class="dtc">Szállító</div>
                    <div class="dtc">Tárgy</div>
                    <div class="dtc">Bruttó</div>
                    <div class="dtc">Nettó</div>
                    <div class="dtc">Teljesítés</div>
                    <div class="dtc">Érkezés</div>
                </div>
        ');

        $sorok = $adatbazis_kapcsolat->s('*', 'bejovo_szamlak', "`statusz` IN (1, 2, 3, 4, 5, 6, 12)", 'erkezett desc', '60,10');

        if( $sorok ) {
            foreach($sorok as $sor) {
                $szallito = $adatbazis_kapcsolat->s('nev', 'szallito', "`id`='{$sor['szallito_id']}'", '', '1');

                if( $szallito ) {
                    $szallito = $szallito[0];

                    $sor['szallito'] = $szallito['nev'];
                }

                $statusz = $adatbazis_kapcsolat->s('nev szinkod', 'bsz_statusz', 'recid='.$sor['statusz'], '', '1');

                $szinkod = '';
                if( $statusz ) {
                    $statusz = $statusz[0];

                    $sor['statusz'] = $statusz['nev'];

                    if( $statusz['szinkod'] ) {
                        $szinkod = ' style="background: '.$statusz['szinkod'].';"';
                    }
                }

                $szamla_html .= nl2('
                    <div class="dtr nSor"'.$szinkod.'>
                        <div class="dtc">'.$sor['ikt_szam'].'</div>
                        <div class="dtc">'.$sor['szaml_szam'].'</div>
                        <div class="dtc">'.$sor['statusz'].'</div>
                        <div class="dtc">'.$sor['reszleg'].'</div>
                        <div class="dtc">'.$sor['szallito'].'</div>
                        <div class="dtc">'.$sor['targy'].'</div>
                        <div class="dtc">'.number_format($sor['brutto'], 0, '', '.').' '.get_penznem().'</div>
                        <div class="dtc">'.number_format($sor['netto'], 0, '', '.').' '.get_penznem().'</div>
                        <div class="dtc">'.str_replace('-', '.', $sor['teljesites']).'</div>
                        <div class="dtc">'.str_replace('-', '.', $sor['erkezett']).'</div>
                    </div>
                ');
            }
        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html
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
