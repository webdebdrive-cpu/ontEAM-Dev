<?php
require_once('../weblap.php');
//Config
$megnevezes             = pathinfo(self());
$megnevezes             = $megnevezes['filename'];
$akt_tomb               = $felmeresi_terv_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'felmérési tervet';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

$gyorskereso_text       = '';       //Gyorskereső felirat. Ha üres akkor nincs gyorskeresés
$oszlop_keres           = 'nev';                //Oszlopnevek, melyben keresünk (több oszlop is felsorolható vesszőkkel)
$oszlop_listaz          = 'nev';                //Oszlopnevek, melyeket kilizátunk (több oszlop is felsorolható vesszőkkel)
$gyorskereso_id         = 'ksz';                //Gyorskereső input neve és ID-ja

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

if( $_POST ) {
    echo '<pre style="font-size: 1.4rem">', print_r($_POST), '</pre>';
    exit();
}

//Több kép menedzseléséhez
$tobb_kep = false; // Ha true, akkor js/jquery-ui.js, js/admin_sorrendez.js aktivál és minify!, admin/tobb_kep.php aktivál
if($tobb_kep){
    function get_tobbkep_darab()    { return 10;        } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return 'true';    } // Lényeges, hogy egy string legyen
    function get_tobbkep_kepmeret() { return 10;        } // MB-ban
    function get_tobbkep_oszlopnev(){                     // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return 'kepek';       } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

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

if( isset($_POST['jogokhozzaad']) ) {
    $id = $_POST['id'];

    /* Kitöröljük a nem kívánatos POST elemeket */
    unset($_POST['id']);
    unset($_POST['jogokhozzaad']);

    /* Rendezzük a tömböt */
    ksort($_POST);

    $adatbazis_kapcsolat -> u('login', array('flags'), array(implode('', $_POST)), 'recid='.$id);

    $weblap->leallit();
    hd($_SERVER['REQUEST_URI']);

    exit();
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
if( !$_GET['ajax'] ) {
    require_once('header.php');
}
require_once('selectek.php');

if($gyorskereso_text){
    $dl = nl2('
                <input type="text" value="'.htmlspecialchars($_GET['ksz']).'" name="'.$gyorskereso_id.'" id="'.$gyorskereso_id.'" placeholder="'.$gyorskereso_text.'" autocomplete="off">
                <script>
                    $(document).ready(function() {
                        $(\'#'.$gyorskereso_id.'\').autocomplete({
                            \'php_fajl\'        : \'../autocomplete.php\',
                            \'tabla\'           : \''.$megnevezes.'\',
                            \'oszlop_keres\'    : \''.$oszlop_keres.'\',
                            \'oszlop_listaz\'   : \''.$oszlop_listaz.'\'
                        });
                    });
                </script>
            ');
    $gyk_sablon  = fajlbol_olvas('../sablon/admin/gyorskereso.html',  true);
    $gyk_sablon2 = fajlbol_olvas('../sablon/admin/gyorskereso2.html', true);
    $gyk_mit     = array('{ACTION}', '{FELIRAT}',       '{DL}', '{EGYEB_DE_FORM_NEM_LEHET}');
    $gyk_mivel   = array(self(),     $gyorskereso_text, $dl);
    $gyk  = str_replace($gyk_mit, $gyk_mivel, $gyk_sablon);
    $gyk2 = str_replace($gyk_mit, $gyk_mivel, $gyk_sablon2);
}

    if( !$_GET['ajax'] ) {
        echo '<div id="szamlaBetolt">';
    }

//    if( isset($_GET['modosit']) || isset($_GET['tortenet']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
//        /* Számla fülek */ 
//        $ful_tomb = array(
//            'modosit'       => 'Adatlap',
//            'tortenet'      => 'Történet',
//            'dokumentumok'  => 'Dokumentumok',
//            'megjegyzesek'  => 'Megjegyzések',
//        );
//
//        $fulek = '';
//        foreach($ful_tomb as $key => $value) {
//            $active = 'active';
//            if( isset($_GET[$key]) ) {
//                $active = ' class="active"';
//            }
//
//            $fulek .= '<a href="javascript: szamlaBetolt('.$_GET['id'].', \''.basename(self()).'\', \''.$key.'\'); void(0);"'.$active.'>'.$value.'</a>';
//        }
//
//        $szamla_html .= str_replace(
//            array('{FULEK}'),
//            array( $fulek  ),
//            fajlbol_olvas('../sablon/admin/szamla_fulek.html', true)
//        );
//    }

	//Új termék felvitele
	if(isset($_GET['uj']) && !$csak_szerkesztheto){

        //NAVIGÁCIÓ
        $gombok = '';
	    //$gombok = '<input type="button" class="gombb" onclick="location.href=\''.self().'?lista\'" value="Lista" title="Lista megtekintése">';
        //$gombok .= ' <input type="button" id="visszavonas_gomb" disabled="disabled" class="gombb" onclick="szamlaBetolt(\'1\', \''.basename(self()).'\', \'uj\', true);" value="Visszavonás" title="Visszavonás">';
        //$gombok .= ' <input type="button" id="kilepes_gomb" class="gombb_alert" onclick="location.href = \''.basename(self()).'\';" value="Kilépés" title="Kilépés">';

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
	    $form_mit    = array('{JS}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{CK_EDITOR}', '{GOMBOK}', '{MUV_STYLE}');

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
                          <input type="submit" class="gombb" name="mehet_gomb" id="mehet_gomb" value="Mentés">
                      </div>').$gombok;

        $muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 6).'px);"';
        if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
            if( $_COOKIE['csuszkaSzelesseg'] ) {
                $muv_style = ' style="width: calc(100% - '.($_COOKIE['csuszkaSzelesseg'] + 6).'px);"';
            }
        }

	    $form_mivel   = array($js, self(), $mezok, $megnevezes, $ckeditor, $gombok, $muv_style);
	    $szamla_html .= str_replace($form_mit, $form_mivel, $form_sablon);

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

	//Termék módosítása
	}elseif(isset($_GET['modosit'])) {

        //Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
        }

	    $sor = $adatbazis_kapcsolat -> select('*', $megnevezes, 'recid='.$_GET['id']);
		$sor = $sor[0];

        if( $sor['datetime'] ) {
            $_SESSION['formMod'] = array(
                'datetime' => $sor['update_dt'],
                'oldal' => basename(self())
            );
        }

        foreach($akt_tomb as $tomb) {
            if( $tomb['type'] == 'autocomplete' ) {
                $kulso_ertek = $adatbazis_kapcsolat->s('recid nev', $tomb['tabla'], 'recid='.$sor[$tomb['name']], '', '1');

                $sor[$tomb['name'].'_autocomp'] = '';
                if( $kulso_ertek ) {
                    $sor[$tomb['name'].'_autocomp'] = $kulso_ertek[0]['nev'];
                }
            }
        }

        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($sor).'\'); </script>';

	    //Js, a törlés gombhoz
	    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';
	    if($elonezet){
           $gombok .= ' <input type="button" class="gombb" onclick="window.open(\''.$elonezet.'\')" value="Előnézet" title="Előnézet">';
        }
        if($sorrendezes){
            $gombok .= ' <input type="button" class="gombb" onclick="location.href=\''.self().'?sorrend\'" value="Sorrend" title="Sorrendezés">';
        }
        if($ujkent_szerkesztheto && !$csak_szerkesztheto){
            //$gombok .= ' <input type="button" class="gombb" onclick="document.getElementById(\''.$megnevezes.'ujkent_szerk\').value=\'1\'; document.getElementById(\'szerk_form\').submit()" title="Újként szerkeszt" value="Újként">';
        }
        if(!$csak_szerkesztheto){
            //$gombok .= ' <input type="button" id="rocordUj" class="gombb dn" onclick="location.href=\''.self().'?uj\'" value="Új" title="Új feltöltés">';
        }
        //$gombok .= ' <input type="button" class="gombb" onclick="location.href=\''.self().get_get(array('modosit','errnum','kephiba','siker','id', 'szerkeszt', 'ajax')).'&amp;lista\'" value="Lista" title="Lista megtekintése">';
        //$gombok .= ' <input type="button" id="visszavonas_gomb" disabled="disabled" class="gombb" onclick="szamlaBetolt('.$sor['recid'].', \''.basename(self()).'\', \'modosit\', true);" value="Visszavonás" title="Visszavonás">';

        if( !$csak_szerkesztheto ) {
            //Jogosutságkezelés: csak az törölheti, aki létrehozta
            if($sor['loginnev']!=$_SESSION[get_db_tabla().'user']){
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Csak saját bejegyzés törölhető!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
            }else{
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].', \'Kérdés\')" value="Töröl" title="Töröl">';
            }
        }

        //$gombok .= ' <input type="button" id="kilepes_gomb" class="gombb_alert" onclick="szamlaBetolt('.$sor['recid'].', \''.basename(self()).'\', \'modosit\');" value="Kilépés" title="Kilépés">';

        if($id_latszik){
            $navi = '<b>#'.$_GET['id'].'</b> '.$navi;
        }

        //NAVIGÁCIÓ
        $navi_sablon = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit    = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel  = array( $navi,             '',         $gyk2 );
        //echo str_replace($navi_mit, $navi_mivel, $navi_sablon);

	    //HIBAüZENETEK
        require_once('info.php');

	    //FORM
	    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

		//Form actionja
		$action = self().get_get(array('errnum','kephiba','siker'));

        if(isset($_GET['ujkent'])){
			foreach($akt_tomb as $ertek){
				$sor[$ertek['name']] = $_SESSION[$ertek['name']];
			}
		}

        //Ckeditort csak akkor húzzuk be, ha kell
        if(is_file($ckeditor_js)){
            $ckeditor = '<script src="'.$ckeditor_js.'"></script>';
        }

		// Ez elkészíti a $mezok változót
		require('switch.php');

        $jobb_oldal = '';

        $gombok = nl2('
            <div class="form_kuldes">
                <input type="submit" class="gombb" name="mehet_gomb" id="mehet_gomb" disabled="disabled" value="Mentés">
            </div>
        ').$gombok;

        $muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 6).'px);"';
        if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
            if( $_COOKIE['csuszkaSzelesseg'] ) {
                $muv_style = ' style="width: calc(100% - '.($_COOKIE['csuszkaSzelesseg'] + 6).'px);"';
            }
        }

	    $szamla_html .= str_replace(
            array('{JS}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}', '{MUV_STYLE}'),
            array( $js,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok,    $muv_style  ),
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

	//Történet
	}elseif(isset($_GET['tortenet'])){

        $szamla_html .= 'Történet felülete';
        
        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

	//Dokumentumok
	}elseif(isset($_GET['dokumentumok'])){

        $szamla_html .= 'Dokumentumok felülete';
        
        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

	//Megjegyzések
	}elseif(isset($_GET['megjegyzesek'])){

        $szamla_html .= 'Megjegyzések felülete';
        
        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

	//Lista
	}elseif(isset($_GET['lista'])){

        //LEKÉRDEZÉS
        $where      = '';
        $oszlopok   = '';
        foreach($akt_tomb as $ertek){
            if($_GET[$ertek['name']]){
                $where.=' `'.$ertek['name'].'`=\''.$_GET[$ertek['name']].'\' AND';
            }
            if($ertek['show']){
                $oszlopok .= $ertek['name'].', ';
            }
        }
        //A használni kívánt oszlopokat itt tudod bővíteni
        $oszlopok .= 'recid, ';
        if($kep_thumb_listaba){
            $oszlopok .= $fajloszlop.', ';
        }

        //Ebben az esetben a gyorskeresőt általános keresőként használtuk
        if(isset($_GET[$gyorskereso_id])){
            $where_keres = '';
            foreach($akt_tomb as $ertek){
                if($ertek['type']=='text' || $ertek['type']=='textarea'){
                    $where_keres .= ' `'.$ertek['name'].'` LIKE \'%'.$_GET[$gyorskereso_id].'%\' OR';
                }
            }
        }
        if($where){
            $where = csonkit($where,4);
        }
        //Ha keresünk, akkor még kiegészítjük a WHERE feltételt
        if($where_keres){
            $where .= ' ('.csonkit($where_keres, 3).')';
        }

        //NAVIGÁCIÓ

        //Darabszám kiszámítása
        $bejegyzesek = $adatbazis_kapcsolat -> select('recid', $megnevezes, $where, '', '', '', true);
        $navi .= ' <span class="navi_db">(<span id="bejegyzesek_szama">'.$bejegyzesek.'</span> db. bejegyzés)</span>';

        if(!$csak_szerkesztheto){
            $gombok = '<input type="button" class="gombb jf jf3" onclick="location.href=\''.self().'?uj\'" value="Új" title="Új feltöltés">';
        }
        if($sorrendezes){
            $gombok .= ' <input type="button" class="gombb jf jf4" onclick="location.href=\''.$_SERVER['PHP_SELF'].'?sorrend\'" value="Sorrend" title="Sorrendezés">';
        }

        $navi_sablon = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit    = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel  = array($navi,             $gombok,    $gyk2);
        echo str_replace($navi_mit, $navi_mivel, $navi_sablon);

        //HIBAüZENETEK
        require_once('info.php');

        //Lapozás előkészítése
        require_once('../class/class.Lapozo.php');

        if(!$_GET['oldal']){
            $_GET['oldal'] = 1;
        }

        $lapozo = new Lapozo;
        $lapozo -> elem_osszes = $bejegyzesek;
        $lapozo -> mind_var         = ''; //'Összes elem kilistázása' változó neve. Ha üres akkor nincs össsez oldal megjelenítés. Alapértelmezetten 'osszes'
        //$lapozo -> epo_var         = 'epo';    //Elem per oldal változó neve
        //$lapozo -> oldal_var       = 'oldal';  //Oldal változó neve
        //$lapozo -> kozep_meret     = 9;        //Nagy oldalszám esetén a középső intervallum mérete
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

        //Számolás
        $lapozo -> lapozas();

        $lekerdezes = $adatbazis_kapcsolat -> select($oszlopok, $megnevezes, $where, $_GET['rend'].' '.$_GET['desc'], $lapozo -> getLimit());

        //GYORSKERESŐ
        echo $gyk;

        //SZŰKÍTÉS HA KELL
        echo szukites($akt_tomb, $megnevezes);

        //lapozas($sql,$_GET['lh']);

        //SQL
        if($bejegyzesek){

            //Form sablon
            $form_sablon = fajlbol_olvas('../sablon/admin/form_lista_torles_nelkul.html', true);
            $form_mit    = array('{ACTION}', '{FEJLECEK}', '{SOROK}', '{MEGNEVEZES}', '{TOBBKEP}', '{TOBBKEP_OSZLOP}');

            $fejlecek = '';
            if($kep_thumb_listaba){
                $fejlecek .= '<td class="fejlec">Kép</td>';
            }
            if($id_latszik){
                $fejlecek .= '<td class="fejlec">#</td>';
            }
            foreach($akt_tomb as $ertek){
                if($ertek['show']){

                    $fejlecek .= '<td class="fejlec">';

                    if($_GET['order'] == $ertek['name']){
                        if(isset($_GET['desc'])){
                            $fejlecek .= '<div class="nyil_le"></div>';
                        }else{
                            $fejlecek .= '<div class="nyil_fel"></div>';
                        }
                    }

                    $fejlecek .= '<a href="'.self().get_get_order($ertek['name']).'">'.$ertek['megn'].'</a></td>';

                }
            }

            if($lapozo -> getOldalakSzama() > 1){
                ?><fieldset class="c"><legend>Lapozás</legend><table class="auto"><tr><td><?php
                //Kiíratás
                echo $lapozo -> kiir();
                echo $lapozo -> epoSelectKiir();
                //echo $lapozo -> oldalakSelectKiir();
                ?></td></tr></table></fieldset><?php
            }

            //törléshez a JS script
            ?><script><?php
                ?>uzi_e = 'Biztosan törli a ';<?php
                ?>uzi_v = ' <?php echo $szoveg?>?';<?php
                ?>gomb_e = get_torles_gomb_e('<?php echo self().get_get()?>','<?php echo $torles?>');<?php
                ?>gomb_v = get_torles_gomb_v();<?php

                foreach($lekerdezes as $sor){
                    ?>uzi<?php echo $sor['recid']?>='<?php echo $sor['cikksz']?>'; gomb<?php echo $sor['recid']?>='<?php echo $sor['recid']?>&amp;noload\',\'sor<?php echo $sor['recid']?>\'';<?php
                }
            ?></script><?php

            //Sorok sablon
            $dn = '';
            if($csak_szerkesztheto){
                $dn = ' dn';
            }
            $sorok = '';
            $sorok_sablon = fajlbol_olvas('../sablon/admin/form_sorok_torles_nelkul.html', true);
            //$sorok_sablon = fajlbol_olvas('../sablon/admin/form_sorok_szinezett.html', true);

            $i = 0;
            foreach($lekerdezes as $sor) {
                $oszlopok = '';
                $link = self().get_get(array('id', 'id_hidden', 'lista', 'siker')).'&amp;id='.$sor['recid'].'&amp;modosit';

                if($kep_thumb_listaba){
                    $kep = kep_kiir('../'.get_utvonal().$sor[$fajloszlop], $sor['nev'], get_indexkep_sz(), get_indexkep_m(), 'b');
                    $oszlopok .= '<td'.$style.'>'.$kep.'</td>';
                }
                if($id_latszik){
                    $oszlopok .= '<td>#'.$sor['recid'].'</td>';
                }

                foreach($akt_tomb as $ertek){
                    if($ertek['show']){

                        $style = '';
                        if($ertek['lista_style']){
                            $style = ' style="'.$ertek['lista_style'].'"';
                        }

                        //Kategóriánál hasznos: az adott érték egy külső tábla azonosítája... így tudjuk összekapcsolni
                        if($ertek['kulso_tabla']){
                            list($tabla, $oszlop) = explode('|', $ertek['kulso_tabla']);
                            $kulso_ertek = $adatbazis_kapcsolat->s($oszlop, $tabla, "id={$sor[$ertek['name']]}");
                            $sor[$ertek['name']] = $kulso_ertek[0][$oszlop];
                        }

                        if($ertek['type'] == 'checkbox'){
                            $oszlopok .= '<td'.$style.'>';
                        }else{
                            $oszlopok .= '<td'.$style.' onclick="if(!onclick_disabled){location.href=\''.$link.'\';}">';
                        }

                        if(is_numeric($sor[$ertek['name']]) && !in_array($ertek['name'], $formazatlan_szam) && $ertek['type'] != 'checkbox'){
                            $oszlopok .= '<span id="inline_szerk_'.$ertek['name'].'_'.$sor['recid'].'_span" onclick="inline_szerk(\''.$ertek['name'].'\', \''.$sor['recid'].'\')">'.$ertek['pre'].'<span id="inline_szerk_'.$ertek['name'].'_'.$sor['recid'].'_text">'.number_format($sor[$ertek['name']],0,'','.').'</span>'.$ertek['post'].'</span>';
                            $oszlopok .= '<br><input class="dn" type="text" name="inline_szerk_'.$ertek['name'].'_'.$sor['recid'].'" id="inline_szerk_'.$ertek['name'].'_'.$sor['recid'].'" value="'.$sor[$ertek['name']].'" onblur="inline_szerk_ment(\''.$megnevezes.'\', \''.$ertek['name'].'\', \''.$sor['recid'].'\')">';
                        } else {
                            if($ertek['type'] == 'checkbox'){
                                $class = '';
                                if($sor[$ertek['name']]){
                                    $class = ' aktiv';
                                }
                                $oszlopok .= '<span class="pipa'.$class.'" id="pipa_'.$ertek['name'].'_'.$i.'" onclick="pipa_kapcsolo(\'pipa_'.$ertek['name'].'_'.$i.'\', \'pipa_'.$ertek['name'].'\', \''.$megnevezes.'lista\', \''.$sor['recid'].'\')">✔</span>';
                            }else{
                                $oszlopok .= '<span id="inline_szerk_'.$ertek['name'].'_'.$sor['recid'].'_span" onclick="inline_szerk(\''.$ertek['name'].'\', \''.$sor['recid'].'\')">'.$ertek['pre'].'<span id="inline_szerk_'.$ertek['name'].'_'.$sor['recid'].'_text" onclick="inline_szerk(\''.$ertek['name'].'\')">'.$sor[$ertek['name']].'</span>'.$ertek['post'].'</span>';
                                $oszlopok .= '<br><input class="dn" type="text" name="inline_szerk_'.$ertek['name'].'_'.$sor['recid'].'" id="inline_szerk_'.$ertek['name'].'_'.$sor['recid'].'" value="'.$sor[$ertek['name']].'" onblur="inline_szerk_ment(\''.$megnevezes.'\', \''.$ertek['name'].'\', \''.$sor['recid'].'\')">';
                            }
                        }

                        $oszlopok .= '</td>';
                    }
                }

                $szin = '';
                /*Pl:
                switch( $sor['statusz'] ){
                    case 'jelezni'     : $szin = 'sarga';  break;
                    case 'jelezve'     : $szin = 'lila';   break;
                    case 'elfogadva'   : $szin = 'zold';   break;
                    case 'kiszamlazva' : $szin = 'piros';  break;
                    case 'kifizetve'   : $szin = 'szurke'; break;
                }
                */

                $sorok_mit   = array('{I}', '{ID}',     '{DN}', '{SZIN}', '{LINK}', '{OSZLOPOK}');
                $sorok_mivel = array($i%2,  $sor['recid'], $dn,    $szin,    $link,    $oszlopok   );
                $sorok .= str_replace($sorok_mit, $sorok_mivel, $sorok_sablon);
                $i++;
            }

            //Ha többképes, akkor azt a törlésnél jelezni kell, úgy hogy postoljuk az oszlopot (több termék törlése egyszerre)
            $tobbkep = '';
            if($tobb_kep){
                $tobbkep = '<input type="hidden" name="oszlopnev" value="'.$tobbkep_oszlopnev.'">';
            }

            $form_mivel  = array(self().get_get(), $fejlecek, $sorok, $megnevezes, $tobbkep, $tobbkep_oszlopnev);

            echo str_replace($form_mit, $form_mivel, $form_sablon);

            if($lapozo -> getOldalakSzama() > 1){
                ?><fieldset class="c"><legend>Lapozás</legend><table class="auto"><tr><td><?php
                //Kiíratás
                echo $lapozo -> kiir();
                echo $lapozo -> epoSelectKiir();
                //echo $lapozo -> oldalakSelectKiir();
                ?></td></tr></table></fieldset><?php
            }

        }else{
            ?><h1>Jelenleg nem taláható felvitt adat!</h1><?php
        }

    //Sorrendezése
    }elseif(isset($_GET['sorrend'])){

        //NAVIGÁCIÓ

        //Darabszám kiszámítása
        $bejegyzesek = $adatbazis_kapcsolat -> select('recid', $megnevezes, '', '', '', '', true);
        $navi .= ' <span class="navi_db">(<span id="bejegyzesek_szama">'.$bejegyzesek.'</span> db. bejegyzés)</span>';

        $gombok = '';
        if(!$csak_szerkesztheto){
            $gombok .= '<input type="button" class="gombb jf jf4" onclick="location.href=\''.self().'?uj\'" value="Új" feltöltés" title="Új feltöltés">';
        }
        $gombok .= ' <input type="button" class="gombb jf jf5" onclick="location.href=\''.self().get_get(array('sorrend')).'&amp;lista\'" value="Lista" title="Lista megtekintése">';

        $navi_sablon = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit    = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel  = array($navi,             $gombok,    $gyk2);
        echo str_replace($navi_mit, $navi_mivel, $navi_sablon);


        $sorrend_html  = '';
        $sorrend_html .= '<ul id="sorrendezes_bejegyzes" title="'.$megnevezes.'">';

        $i = 0;
        $bejegyzesek = $adatbazis_kapcsolat -> select('recid nev kep', $megnevezes, '', 'sor');
        foreach($bejegyzesek as $bejegyzes){

            /*Ha egy termékhez több kép is tartozik

            $kiskep = $adatbazis_kapcsolat -> select('kepnev', 'kepek', 'termekid='.$sor_kepek['id'], 'sor');
            $sor_kiskep = $kiskep[0];*/

            /*Egy kép esetén

            if( !$bejegyzes['kep'] || !is_file('../'.get_utvonal().$bejegyzes['kep'])){
                $kep = '../elemek/admin/sorrend.gif';
            }else{
                $kep = '../'.get_utvonal().$bejegyzes['kep'];
            }
            require_once('../class/html/class.Img.php');
            $kep = new Img($kep, $bejegyzes['nev'], get_kep_sz(), get_kep_m());
            $kep = $kep -> getHtml();
            */

            $sorrend_html .= '<li title="'.$bejegyzes['nev'].'" value="'.$bejegyzes['recid'].'"><div class="kep">'.$kep.'<br><b>'.$bejegyzes['nev'].'</b></div></li>';

            $i++;
        }

        $sorrend_html .= '</ul>';
        echo $sorrend_html;

    }

    if( !$_GET['ajax'] ) {
        echo '</div>';
    }

if( !$_GET['ajax'] ) {
    require_once('footer.php');
}
