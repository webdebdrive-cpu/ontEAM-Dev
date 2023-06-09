<?php
require_once('../weblap.php');

//Config
$megnevezes             = pathinfo(self());
$megnevezes             = $megnevezes['filename'];
$akt_tomb               = $rendszer_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'rendszer bejegyzést';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

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

    //Kép törlésének lekezelése
    if (isset($_GET[$torles.'tobbkep_torol'])){

        $tobbkep = $adatbazis_kapcsolat -> s('kepnev', get_tobbkep_tabla(), get_tobbkep_oszlopnev().'='.$_GET[$torles.'tobbkep_torol']);
        foreach($tobbkep as $sor_tobbkep){

            //eredeti fájl törlése
            if(is_file('../'.get_utvonal().$sor_tobbkep['kepnev'])){
                unlink('../'.get_utvonal().$sor_tobbkep['kepnev']);
            }

            //ha van kiterjesztése
            if(strrpos($sor_tobbkep['kepnev'], '.')>0){
                remover('../'.get_utvonal().substr($sor_tobbkep['kepnev'],0,strrpos($sor_tobbkep['kepnev'],'.')).'_*');
            }else{
                remover('../'.get_utvonal().$sor_tobbkep['kepnev'].'_*');
            }
        }

        $adatbazis_kapcsolat -> delete(get_tobbkep_tabla(), get_tobbkep_oszlopnev().'='.$_GET[$torles.'tobbkep_torol']);

        if(isset($_GET['noload'])){
            $weblap -> leallit();
            exit();
        }
    }

}
/*
Ha true a config.php-ban:
js/+jquery-ui.js aktiválni a config.php-ban az ADMIN JS és CSS RÉSZBEN!!!
js/+admin_sorrendez.js aktiválni

Hozzá kell adni ezt a fájlt:
admin/+tobb_kep.php aktiválni

Minta SQL
CREATE TABLE IF NOT EXISTS `TABLA_kepek`(
  `id` int(10) unsigned NOT NULL auto_increment,
  `"TERMEK"ID` int(10) unsigned default NULL,
  `kepnev` varchar(100) NOT NULL default '',
  `megj` varchar(200) NOT NULL default '',
  `sor` int(11) NOT NULL default '0',
  `datetime` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
)

Vízjelezéshez vissza kell kommentezni a +tobb_kep.php-ban a Képfeltöltésnél egy részt!
*/

/** Események */
if(isset($_GET['siker']) && isset($_GET['modosit'])){

}


/** Tartalom */
require_once('header.php');
require_once('selectek.php');

    $js_submit_elott = 'return form_ell_admin();';

	//Új termék felvitele
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
                          <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
                      </div>').$gombok;

	    $form_mit    = array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{CK_EDITOR}', '{GOMBOK}');
	    $form_mivel  = array($js,    $js_submit_elott,    self(),     $mezok,    $megnevezes,    $ckeditor,     $gombok   );
	    echo str_replace($form_mit, $form_mivel, $form_sablon);

	//Termék módosítása
	}elseif(isset($_GET['modosit'])){

        //Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
        }

	    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, 'recid='.$_GET['id']);

	    //FORM
	    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

		//Form actionja
		$action = self().get_get(array('errnum','kephiba','siker'));

		// Ez elkészíti a $mezok változót
		require('switch.php');

        $jobb_oldal = '';

        if( isset($_GET['szerkeszt']) ) {
            $gombok = nl2('<div class="form_kuldes">
                              <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
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