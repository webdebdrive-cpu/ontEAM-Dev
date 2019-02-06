<?php
require_once('../weblap.php');

//Config
$megnevezes             = pathinfo(self());
$megnevezes             = $megnevezes['filename'];
$akt_tomb               = $log_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'bejegyzést';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

//Több kép menedzseléséhez
$tobb_kep = false;
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

/** Tartalom */
require_once('header.php');
require_once('selectek.php');

    $js_submit_elott = 'return form_ell_admin();';

	//Új termék felvitele
	if(isset($_GET['uj']) && !$csak_szerkesztheto){

        //NAVIGÁCIÓ
        $gombok = '';
	    //$gombok = '<input type="button" class="gombb" onclick="szurkit(); location.href=\''.self().'?lista\'" value="Lista" title="Lista megtekintése">';
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
                          <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
                          <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
                      </div>').$gombok;

        $form_mit     = array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{CK_EDITOR}', '{GOMBOK}', '{MUV_STYLE}'   );
        $form_mivel   = array($js,    $js_submit_elott,    self(),     $mezok,    $megnevezes,    $ckeditor,     $gombok,    $muv_style      );
	    echo str_replace($form_mit, $form_mivel, $form_sablon);

	//Termék módosítása
	}elseif(isset($_GET['modosit'])){

        //Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
        }

	    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, 'recid='.$_GET['id']);

        echo '<script> formAdatok = JSON.parse(\''.json_encode($sor).'\'); </script>';

	    //Js, a törlés gombhoz
	    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';
        $gombok .= ' <input type="button" class="gombb" onclick="szurkit(); location.href=\''.self().get_get(array('modosit','errnum','kephiba','siker','id', 'szerkeszt')).'&amp;lista\'" value="Lista" title="Lista megtekintése">';
        //$gombok .= ' <input type="button" id="visszavonas_gomb" disabled="disabled" class="gombb" onclick="szurkit(); location.href=\''.self().get_get().'\'" value="Visszavonás" title="Visszavonás">';

        if(!$csak_szerkesztheto){
            $gombok .= ' <input type="button" class="gombb_alert" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\',gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
        }

	    //FORM
	    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

		//Form actionja
		$action = self().get_get(array('errnum','kephiba','siker'));

		// Ez elkészíti a $mezok változót
		require('switch.php');

        $jobb_oldal = '';

        $gombok = /*nl2('<div class="form_kuldes">
                          <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
                          <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
                      </div>').*/$gombok;

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
