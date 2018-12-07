<?php
require_once('../weblap.php');

// Config
$megnevezes             = pathinfo(self());
$megnevezes             = $megnevezes['filename'];
$akt_tomb               = $kimeno_szamlak_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'számla';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

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

// Tartalom
require_once('header.php');
require_once('selectek.php');

    $js_submit_elott = 'return form_ell_admin();';
    
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
    
	// Új termék felvitele
	if(isset($_GET['uj']) && !$csak_szerkesztheto){

        //NAVIGÁCIÓ

	    $gombok = '<input type="button" class="gombb" onclick="szurkit(); location.href=\''.self().'?lista\'" value="Lista" title="Lista megtekintése">';
        if($sorrendezes){
            $gombok .= ' <input type="button" class="gombb" onclick="szurkit(); location.href=\''.self().'?sorrend\'" value="Sorrend" title="Sorrendezés">';
        }

	    //FORM
		if(isset($_GET['ujkent'])){
			foreach($akt_tomb as $ertek){
				$sor[$ertek['name']]=$_SESSION[$ertek['name']];
			}
		}
	    $js = js_kiir($akt_tomb, 'form_ell_admin()');

	    $form_sablon = fajlbol_olvas('../sablon/admin/form_uj.html', true);

		// Ez elkészíti a $mezok változót
		require('switch.php');

        $gombok = nl2('<div class="form_kuldes">
                          <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="szurkit(\'mehet_megsem_rejt\'); $(\'#szerk_form\').submit()">
                          <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="szurkit(\'mehet_megsem_rejt\'); formModositva=false; szamlaBetolt($(\'#szamlaList li:first-of-type\').attr(\'data-recid\'), \''.$megnevezes.'.php\');">
                      </div>').$gombok;

    $form_mit     = array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{CK_EDITOR}', '{GOMBOK}', '{MUV_STYLE}'   );
    $form_mivel   = array($js,    $js_submit_elott,    self(),     $mezok,    $megnevezes,    $ckeditor,     $gombok,    $muv_style      );
	    echo str_replace($form_mit, $form_mivel, $form_sablon);

	// Rekord módosítása
	}elseif(isset($_GET['modosit'])){

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
        }

        echo '<script> formAdatok = JSON.parse(\''.json_encode($sor).'\'); </script>';

	    //Js, a törlés gombhoz
	    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';
        if(!$csak_szerkesztheto){
            $gombok .= ' <input type="button" class="gombb" onclick="szurkit(); location.href=\''.self().'?uj\'" value="Új" title="Új feltöltés">';
        }
        $gombok .= ' <input type="button" class="gombb" onclick="szurkit(); location.href=\''.self().get_get(array('modosit','errnum','kephiba','siker','id', 'szerkeszt')).'&amp;lista\'" value="Lista" title="Lista megtekintése">';
        $gombok .= ' <input type="button" id="visszavonas_gomb" disabled="disabled" class="gombb" onclick="szurkit(); location.href=\''.self().get_get().'\'" value="Visszavonás" title="Visszavonás">';

        if(!$csak_szerkesztheto){
            $gombok .= ' <input type="button" class="gombb_alert" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\',gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
        }

        $gombok .= ' <input type="button" id="kilepes_gomb" class="gombb_alert" onclick="szurkit(); location.href=\''.self().get_get('szerkeszt').'\'" value="Kilépés" title="Kilépés">';

	    //FORM
	    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

		//Form actionja
		$action = self().get_get(array('errnum','kephiba','siker'));

		// Ez elkészíti a $mezok változót
		require('switch.php');

        $jobb_oldal = '';

        if( isset($_GET['szerkeszt']) ) {
            $gombok = nl2('<div class="form_kuldes">
                              <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="szurkit(\'mehet_megsem_rejt\'); $(\'#szerk_form\').submit()">
                              <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="szurkit(\'mehet_megsem_rejt\'); formModositva=false; szamlaBetolt(\''.$_GET['id'].'\', \''.$megnevezes.'.php\');">
                          </div>').$gombok;
        } else {
            $gombok = '<input type="button" class="gombb" onclick="szurkit(); location.href=\''.self().get_get('szerkeszt').'&szerkeszt\'" value="Szerkeszés" title="Record szerkesztése">';
        }

	    echo str_replace(
            array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}', '{UPDATE_DT}'     ),
            array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok,    $sor['update_dt'] ),
            fajlbol_olvas('../sablon/admin/form_modosit.html', true)
        );

        if($tobb_kep){
			require_once('tobb_kep.php');
		}

	}
require_once('footer.php');
?>
