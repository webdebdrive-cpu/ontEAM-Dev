<?php
require_once('../weblap.php');

if( !_jog(JOG_FELHASZNALOK_MEGTEKINT) ) {
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
$akt_tomb               = $login_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'felhasználót';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

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
    function get_tobbkep_darab()    { return 10;            } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '.jpg,.png';   } // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;            } // MB-ban
    function get_tobbkep_oszlopnev(){                         // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

}

// Jogosultságok beállítsa
if( isset($_POST['jogokhozzaad']) ) {
    $id = $_POST['id'];

    // Kitöröljük a nem kívánatos POST elemeket
    unset($_POST['id']);
    unset($_POST['jogokhozzaad']);

    // Tömb sorrendezése
    $jogok = array();
    foreach($_POST as $key => $value) {
        $kulcs = str_replace('jog-', '', $key);
        if($value=='true'){
            $jogok[$kulcs] = 1;
        }else{
            $jogok[$kulcs] = 0;
        }
    }

    ksort($jogok);

    $adatbazis_kapcsolat -> u('login', array('flags'), array(implode('', $jogok)), 'recid='.$id);
    
    // A formAdatok JS változoót frissíteni kelll
    require_once('jogosultsag.php');
    $i = 0;
    foreach($jogosultsagok as $jog) {
        if( $jogok[$i] ) {
            $jogEll['jog-'.$i] = 1;
        } else {
            $jogEll['jog-'.$i] = 0;
        }

        $i++;
    }
    echo json_encode($jogEll);

    $weblap->leallit();
    exit();
}

// Szálloda választó popup
if( isset($_POST['getSzallodak']) ) {
    
    // Szálloda lekérdezése
    $telephelyek = $adatbazis_kapcsolat->s('id nev recid modell', 'telephely', "`inaktiv`='0'", 'nev');

    // Felhasználóhoz kapcsolt szállodák
    $felh = $adatbazis_kapcsolat->s2('nev', 'login', "`recid` = '".$_POST['recid']."'");

    // Kapcsolt szállodák
    $felh_szallodak = $adatbazis_kapcsolat->s('telephely_id', 'telephely_kapcs', "`nev` = '".$felh['nev']."'");
    $felh_szallodak = adatbazis2tomb($felh_szallodak, 'telephely_id');

    if( !$felh_szallodak ) {
        $felh_szallodak = array();
    }

    $html = '';
    foreach($telephelyek as $telephely) {
        $checked = '';
        if( in_array($telephely['id'], $felh_szallodak) ) {
            $checked = ' checked="checked"';
        }

        $html .= nl2('
            <div class="dtr">
                <div class="dtc">
                    <div class="cb">
                        <input type="checkbox" class="check" name="telephely[]" id="telephely-'.$telephely['recid'].'" value="'.$telephely['id'].'" data-pesti="'.$telephely['modell'].'"'.$checked.'>
                        <label for="telephely-'.$telephely['recid'].'"></label>
                    </div>
                </div>
                <div class="dtc">
                    '.$telephely['nev'].'
                </div>
            </div>
        ');
    }

    echo $html;

    $weblap->leallit();
    exit();
}

// Felhasználó választó popup
if( isset($_POST['getLoginok']) ) {

    // Kapcsolt (már team) loginok
    $login_team = $adatbazis_kapcsolat->s('login_recid', 'projektkezeles_login', "`projektkezeles_recid`='".$_POST['recid']."'");
    $login_team = adatbazis2tomb($login_team, 'login_recid');
    if( !$login_team ) {
        $login_team = array();
    }
    
    // Felhasználók lekérdezése
    $loginok = $adatbazis_kapcsolat->s('recid, nev, teljes_nev', 'login', "", 'nev');

    $html = '';
    foreach($loginok as $login) {
        $checked = '';
        if( in_array($login['recid'], $login_team) ) {
            $checked = ' checked="checked"';
        }

        $html .= nl2('
            <div class="dtr">
                <div class="dtc">
                    <div class="cb">
                        <input type="checkbox" class="check" name="loginok[]" id="login-'.$login['recid'].'" value="'.$login['recid'].'"'.$checked.'>
                        <label for="login-'.$login['recid'].'"></label>
                    </div>
                </div>
                <div class="dtc">
                    '.$login['nev'].' ('.$login['teljes_nev'].')
                </div>
            </div>
        ');
    }

    echo $html;

    $weblap->leallit();
    exit();
}

// CSV generálása
if (isset($_GET['csv'])) {

    $mibol   = get_web_charset();
    $mibe    = 'windows-1250';

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
        $sql = 'SELECT
                                                    `nev` as \'Loginnév\',
                                                    `teljes_nev` as \'Teljes név\',
                                                    `usertel` as \'Telefon\',
                                                    `usermail` as \'E-mail\',
                                                    `szerepkor_id` as \'Szerepkör\',
                                                    `beosztas_id` as \'Beosztás\',
                                                    `reszleg_id` as \'Részleg\',
                                                    `virtual` as \'Virtuális\',
                                                    `torolve` as \'Törölve\',
                                                    `aktiv` as \'Aktiv\'
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
                if($kulcs=='Szerepkör'){
                    $sor = $adatbazis_kapcsolat->s('nev', 'szerepkor', "`recid`='{$sor}'");
                    $sor = $sor[0]['nev'];
                }
                if($kulcs=='Beosztás' || $kulcs=='Részleg'){
                    $sor = $adatbazis_kapcsolat->s('info', 'erteklista', "`id`='{$sor}'");
                    $sor = $sor[0]['info'];
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


/** Tartalom */
if( !$_GET['ajax'] ) {
    require_once('header.php');
}
require_once('selectek.php');

    if( !$_GET['ajax'] ) {
        echo '<div id="szamlaBetolt">';
    }

    // Megjegyzések darabszáma
    $megj_db = '';
    $megj_sorok = $adatbazis_kapcsolat->s('recid', 'megjegyzes', "`tabla` = '".$megnevezes."' AND `id` = '".$_GET['id']."'", '', '');
    if( $megj_sorok ) {
        $megj_db = ' ('.count($megj_sorok).')';
    }

    // Dokumentumok darabszáma
    $dok_db = '';
    $dok_sorok = $adatbazis_kapcsolat->s('recid utvonal', $megnevezes.'_dok', "`{$megnevezes}_id` = '".$_GET['id']."'", '', '');
    if( $dok_sorok ) {
        $dok_db = 0;

        foreach($dok_sorok as $dok_sor) {
            if( is_file('../'.get_utvonal().$megnevezes.'/'.$dok_sor['utvonal']) ) {
                $dok_db += 1;
            }
        }

        $dok_db = ' ('.$dok_db.')';
    }

    if( isset($_GET['modosit']) || isset($_GET['jogosultsag']) || isset($_GET['szallodak']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'jogosultsag'   => 'Jogosultságok',
            'szallodak'     => 'Szállodák',
            'dokumentumok'  => 'Dokumentumok'.$dok_db,
            'megjegyzesek'  => 'Megjegyzések'.$megj_db,
        );

        $fulek = '';
        foreach($ful_tomb as $key => $value) {
            $active = 'active';
            if( isset($_GET[$key]) ) {
                $active = ' class="active"';
            }

            $fulek .= '<a href="javascript: szamlaBetolt('.$_GET['id'].', \''.basename(self()).'\', \''.$key.'\'); void(0);"'.$active.' data-oldal="'.$key.'">'.$value.'</a>';
        }

        $szamla_html .= str_replace(
            array('{FULEK}'),
            array( $fulek  ),
            fajlbol_olvas('../sablon/admin/szamla_fulek.html', true)
        );
    }

    // Új feletöltés alatti gombok
    if (isset($_GET['uj'])) {
        $gombok .= nl2('
                        <input type="button" class="gomb" value="Szállodák hozzáadása" onclick="kapcsolodoSzerkeszt(\'szallodak\');">
                        <input type="hidden" name="szallodak_hidden" id="szallodak_hidden">
                        ');
    }
    
    if(_jog(JOG_FELHASZNALOK_KEZEL)){
        $gombok = nl2('
            <div class="form_kuldes">
                <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="$(\'#szerk_form\').submit()">
                <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="formModositva=false; history.back();">
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

    $js_submit_elott = 'return form_ell_admin();';

	// Új felhasználó felvitele
	if(isset($_GET['uj']) && _jog(JOG_FELHASZNALOK_KEZEL)){

        if (isset($_GET['foglalt_nev'])) {
            $navi = '<span class="piros">Ez a loginnév már foglalt!</span>';
        }

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

	// Felhasználók módosítása
	}elseif(isset($_GET['modosit'])) {
        
        // Módosításnál figyeljük, hogy a szerepkör megváltozott-e
        $js_submit_elott = nl2('
                                if($(\'#szerepkor_id_autocomp\').hasClass(\'mod\')){
                                    gomb_folytatja = \'<input type="button" class="gomb zold" value="Igen" onclick="form_ell_admin()"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">\';
                                    alert2(\'A szerepkör megváltozott: \'+$(\'#szerepkor_id_autocomp\').val()+\'<br>Ha folytatja, az új szerepkör jogsultságai is beállításra kerülnek!<br>Folytatja?\', gomb_folytatja);
                                    return false;
                                }else{
                                    return form_ell_admin();
                                }
                                ');

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
        }

	    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, 'recid='.$_GET['id']);

        if( $sor['datetime'] ) {
            $_SESSION['formMod'] = array(
                'datetime' => $sor['update_dt'],
                'oldal'    => basename(self())
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

	    //HIBAüZENETEK
        require_once('info.php');

	    //FORM
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

	// Jogosultság
    }elseif(isset($_GET['jogosultsag'])) {
        
        require_once('jogosultsag.php');

        $login = $adatbazis_kapcsolat->s2('flags', 'login', 'recid='.$_GET['id'], '', '1');

        if( $login ) {

            // Jogosultságok feldarabolás
            $flags = str_split($login['flags']);

            // Ellenőrzéshez 
            $jogEll = array();
            $i = 0;
            foreach($jogosultsagok as $jog) {
                if( $flags[$i] ) {
                    $jogEll['jog-'.$i] = 1;
                } else {
                    $jogEll['jog-'.$i] = 0;
                }

                $i++;
            }

            $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($jogEll).'\'); </script>';

            // Hozzáadjuk az értékeket a jogosultságokhoz
            $i = 0;
            foreach($jogosultsagok as $jog) {
                $jogosultsagok[$i]['value'] = $flags[$i];
                $jogosultsagok[$i]['id']    = $i;

                $i++;
            }

            // Kigyüjtjük a kategóriákat
            $jogKatok = jogosultsagKatok( $jogosultsagok );

            // Rendezzük a kategóriákat
            function sortByOrder($a, $b) {
                return $a['sor'] - $b['sor'];
            }
            usort($jogKatok, 'sortByOrder');

            $szamla_html .= nl2('
                                <script>
                                    function form_elkuld(){
                                        param = \'\';
                                        $(\'input[type="checkbox"]\').each(function () {
                                            param += $(this).attr(\'name\')+\'=\'+$(this).is(\':checked\')+\'&amp;\';
                                        });
                                        
                                        $.ajax({
                                            url    : \''.self().'\',
                                            type   : \'POST\',
                                            data   : esamp2es(param+\'&amp;id='.$_GET['id'].'&amp;jogokhozzaad=\' + new Date().getTime()),
                                            success: function (response) {

                                                if(response.substring(0, 6)==\'SESKIL\'){ location.href = \'index.php\'; }
                                                $(\'input[type="checkbox"]\').removeClass(\'mod\');
                                                formModositva = false;
                                                $(\'.mentes_gomb\').attr(\'disabled\', true);
                                                
                                                formAdatok = JSON.parse(response); 
                                            }
                                        });
                                        return false;
                                    }
                                </script>
                                <form id="szerk_form" action="" method="post" onsubmit="return form_elkuld();">
                                    <div class="dt">
                ');

            $jogdb      = count($jogKatok);
            $jogdb_fel  = ceil($jogdb / 2);

            // Kiíratjuk az egészet
            $i = 1;
            foreach($jogKatok as $jogKat) {
                if( $i == 1 ) {
                    $szamla_html .= '
                        <div class="dt jog_dt">
                            <div class="dtr">
                                <div class="dtc jog_dtc">
                    ';
                } else if( $i == $jogdb_fel ) {
                    $szamla_html .= '
                                </div>
                                <div class="dtc jog_dtc">
                    ';
                }

                $szamla_html .= '
                    <div class="dtr">
                        <div class="dtc form_bal jogKat"><span>'.$jogKat['nev'].':</span></div>
                    </div>
                ';

                $jogSorok = getJogSorRendez( $jogKat['id'],  $jogosultsagok);
                usort($jogSorok, 'sortByOrder');

                foreach($jogSorok as $jogSor) {

                    $checked = '';
                    if( $jogSor['value'] ) {
                        $checked = ' checked="checked"';
                    }

                    $jogSor['nev'] .= ':';
                    if($jogSor['i']){
                        $jogSor['nev'] .= '<br><i>('.$jogSor['i'].')</i><br><br>';
                    }

                    $onclick = '';
                    $kikapcs = '';
                    if($jogSor['kikapcs']!=''/*Lényeges, hogy az üres stringgel legyen hasonlítva, mert 0 lehet értéke*/){
                        foreach(explode(',', $jogSor['kikapcs']) as $pipa_ki){
                            $kikapcs .= '$(\'#jog-'.$pipa_ki.'\').prop(\'checked\', false).removeClass(\'mod\');';
                        }
                    }
                    $kikapcskor_kikapcs = '';
                    if($jogSor['kikapcskor_kikapcs']==''/*Lényeges, hogy az üres stringgel legyen hasonlítva, mert 0 lehet értéke*/){
                        foreach(explode(',', $jogSor['kikapcskor_kikapcs']) as $pipa_ki){
                            $kikapcskor_kikapcs .= '$(\'#jog-'.$pipa_ki.'\').prop(\'checked\', false).addClass(\'mod\');';
                        }
                    }
                    $bekapcs = '';
                    if($jogSor['bekapcs']!=''){
                        foreach(explode(',', $jogSor['bekapcs']) as $pipa_be){
                            $bekapcs .= '$(\'#jog-'.$pipa_be.'\').prop(\'checked\', true).addClass(\'mod\');';
                        }
                    }

                    // Ha épp most kap bekapcsolást, csak akkor futtatjuk le
                    if($bekapcs){
                        $bekapcs = 'if( $(this).prop(\'checked\') ){'.$bekapcs.'}';
                    }
                    // Ha épp most kapcsoltuk ki
                    if($kikapcskor_kikapcs){
                        $kikapcskor_kikapcs = 'if( $(this).prop(\'checked\') == false ){'.$kikapcskor_kikapcs.'}';
                    }

                    if($kikapcs || $kikapcskor_kikapcs || $bekapcs){
                        $onclick = ' onclick="'.$kikapcs.$kikapcskor_kikapcs.$bekapcs.'"';
                    }

                    $dn = '';
                    if($jogSor['torolve']){
                        $dn = ' dn';
                    }

                    $szamla_html .= nl2('
                        <div class="dtr'.$dn.'">
                            <div class="dtc form_bal"><span>'.$jogSor['nev'].'</span></div>
                            <div class="dtc form_jobb">
                                <div class="cb">
                                    <input type="hidden" name="jog-'.$jogSor['id'].'" value="0">
                                    <input type="checkbox" class="check" name="jog-'.$jogSor['id'].'" id="jog-'.$jogSor['id'].'" value="1"'.$checked.$onclick.'>
                                    <label for="jog-'.$jogSor['id'].'"></label>
                                    '.$info.'
                                </div>
                            </div>
                        </div>
                    ');
                }

                if( $jogdb == $i ) {
                    $szamla_html .= '
                            </div>
                        </div>
                    ';
                }

                $i++;
            }

            $muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 16).'px);"';
            if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
                if( $_COOKIE['csuszkaSzelesseg'] ) {
                    $muv_style = ' style="width: calc(100% - '.(($_COOKIE['csuszkaSzelesseg'] + 16)/10).'rem);"';
                }
            }

            $szamla_html .= nl2('
                        <div class="muveletek"'.$muv_style.'>
                            <div class="form_kuldes">
                                <input type="hidden" name="id" value="'.$_GET['id'].'">
                                <input type="submit" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés">
                            </div>
                        </div>
                    </div>
                </form>
            ');

        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html
            ));
        } else {
            echo $szamla_html;
        }

    // Szállodák fül
    }elseif(isset($_GET['szallodak'])) {

        $felh = $adatbazis_kapcsolat->s2('recid nev', 'login', 'recid='.$_GET['id'], '', '1');
        if( $felh ) {

            $sorok = $adatbazis_kapcsolat->s('recid telephely_id', 'telephely_kapcs', '`nev`=\''.$felh['nev'].'\'');
            $szamla_html .= '<div id="tablazatBeszur">';
            if( $sorok ) {
                
                // Fejlécek
                $szamla_html .= nl2('
                    <div class="dt naplo szakter kijCont" data-tabla="telephely_kapcs" data-mellek="szallodak">
                        <div class="dtr nFejlec">
                            <div class="dtc">Név</div>
                        </div>
                ');

                $i = 1;
                $telephelyek = adatbazis2tomb($sorok, 'telephely_id');
                $telephelyek = $adatbazis_kapcsolat->s('id nev', 'telephely', "`id` IN (".implode(',', $telephelyek).") AND `inaktiv`=0", 'nev');
                foreach($telephelyek as $telephely) {

                    $kapcs_id = $adatbazis_kapcsolat->s2('recid', 'telephely_kapcs', "`telephely_id`='{$telephely['id']}' AND `nev`='{$felh['nev']}'");

                    $szamla_html .= nl2('
                        <div id="naploSor-'.$kapcs_id['recid'].'" class="dtr nSor kijSor" data-id="'.$kapcs_id['recid'].'" data-i="'.$i.'">
                            <div class="dtc full">'.$telephely['nev'].'</div>
                        </div>
                    ');

                    $i++;
                }

                $szamla_html .= '</div>';
            }

            $szamla_html .= '</div>';
        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html
            ));
        } else {
            echo $szamla_html;
        }


	// Dokumentumok
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
                'tartalom' => $szamla_html,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
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
