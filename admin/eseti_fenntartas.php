<?php
require_once('../weblap.php');

if( !_jog(JOG_ESETI_FENN_MEGTEKINT) ) {
    if(!$_SESSION[get_db_tabla().'user']){
        $weblap -> leallit();
        header('Location: index.php');
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');
    $weblap -> leallit();
    exit('Nincs megfelelő jogosultság az oldal eléréséhez!');
}

//Config
$megnevezes             = pathinfo(self());
$megnevezes             = $megnevezes['filename'];
$akt_tomb               = $eseti_fenntartas_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'eseti fenntartást';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

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
$tobb_kep = true; // Ha true, akkor js/jquery-ui.js, js/admin_sorrendez.js aktivál és minify!, admin/tobb_kep.php aktivál
if($tobb_kep){
    function get_tobbkep_darab()    { return 10;        } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return 'false';    } // Lényeges, hogy egy string legyen
    function get_tobbkep_kepmeret() { return 10;        } // MB-ban
    function get_tobbkep_oszlopnev(){                     // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

}


if (isset($_GET['datumra_utemezes'])) {
    include('datumra_utemezes.php');

    $sor = $adatbazis_kapcsolat->s('*', $megnevezes, "`recid`='{$_GET['id']}'");
    $sor = $sor[0];

    if(!$sor['esem_d']){
        $sor['esem_d'] = date('Y-m-d');
    }
    $esem_d     = substr(str_replace('-', '.', $sor['esem_d']), 0, 10);
    $esnap_d    = str_replace('-', '.', $sor['esnap_d']);

//    $szamla_html .=  "esemd: $esem_d<br>cikltip: $_GET[cikltip]<br>ciklaltip: $_GET[ciklaltip]<br>ciklszorz: $_GET[ciklszorz_]<br>ciklho: $_GET[ciklho]<br>ciklhet: $_GET[ciklhet]<br>ciklnap: $_GET[ciklnap]<br>idoszakos: $_GET[idoszakos]<br>kezdho: $_GET[kezdho]<br>kezdnap: $_GET[kezdnap]<br>vegeho: $_GET[vegeho]<br>vegenap: $_GET[vegenap]<br>aktkezd_d: $aktkezd_d<br>aktvege_d: $aktvege_d";
//    $getnextvaliddate = getnextvaliddate(false, $esem_d, $_GET['cikltip'], $_GET['ciklaltip'], $_GET['ciklszorz'], $_GET['ciklho'], $_GET['ciklhet'], $_GET['ciklnap'], $_GET['idoszakos'], $_GET['kezdho'], $_GET['kezdnap'], $_GET['vegeho'], $_GET['vegenap'], $aktkezd_d, $aktvege_d);
//    $szamla_html .= '<hr>Következő esedékesség (esnap_d): ' . $esnap_d;
//    $szamla_html .= '<br>Következő: ' . $getnextvaliddate;
//    $szamla_html .= '<br>Szabály: ' . getcimke( $_GET['cikltip'], $_GET['ciklaltip'], $_GET['ciklszorz'], $_GET['ciklho'], $_GET['ciklhet'], $_GET['ciklnap'], $getnextvaliddate).'<br><br>';

    //A hibaüzenetet ne tegyük a mezőbe
    if( mb_substr($getnextvaliddate, 0, 10)=='Hibaüzenet' ){
        $getnextvaliddate = '    .  .  ';
    }

    echo json_encode( array('uzenet' => 'Ön a következő ütemezést választotta:<br><div class="l">'.$szamla_html.'</div><br>Folytatja?', 'esedekes' => $getnextvaliddate) );

    $weblap -> leallit();
    exit();

}

/* CSV generálása */
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
                `'.get_db_tabla().$megnevezes.'`.`id` as \'Feladatszám\',
                `'.get_db_tabla().$megnevezes.'`.`telephely_id` as \'Szálloda\',
                `'.get_db_tabla().$megnevezes.'`.`obj_id` as \'Helyiség\',
                `'.get_db_tabla().$megnevezes.'`.`nev` as \'Feladat\'
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
                if($kulcs=='Helyiség'){
                    $sor = getErtek('nev', 'obj', 'recid', $sor);
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

    /* Megjegyzések darabszáma */
    $megj_db = '';
    $megj_sorok = $adatbazis_kapcsolat->s('recid', 'megjegyzes', "`tabla` = '".$megnevezes."' AND `id` = '".$_GET['id']."'", '', '');
    if( $megj_sorok ) {
        $megj_db = ' ('.count($megj_sorok).')';
    }

    /* Dokumentumok darabszáma */
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

    if( isset($_GET['modosit']) || isset($_GET['tortenet']) || isset($_GET['naptar']) || isset($_GET['utemezes']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
        /* Számla fülek */
        $ful_tomb = array(
            'naptar'        => 'Naptár',
            'modosit'       => 'Adatlap',
            'utemezes'      => 'Ütemezések',
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

    /* Gombok kirajzolása minden oldalra */
    $gombok = '';
    if( isset($_GET['uj']) ) {

        //Újat csak "Eseti fenntartások kezelése" joggal lehet
        if(_jog(JOG_ESETI_FENN_KEZEL) ){
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="$(\'#szerk_form\').submit()">
                </div>
            ');

            $muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 16).'px);"';
            if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
                if( $_COOKIE['csuszkaSzelesseg'] ) {
                    $muv_style = ' style="width: calc(100% - '.($_COOKIE['csuszkaSzelesseg'] + 16).'px);"';
                }
            }

            /* Műveletek box */
            $gombok = nl2('
                <div class="muveletek"'.$muv_style.'>
                    '.$gombok.'
                </div>
            ');
        }

    //Módosítás gombok
    } else {

        $sor = $adatbazis_kapcsolat -> select('*', $megnevezes, 'recid='.$_GET['id']);
		$sor = $sor[0];

        //Js, a törlés gombhoz
	    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';

        if( _jog(JOG_ESETI_FENN_KEZEL) ){
            //Csak saját bejelentés törölhető!
            if($sor['loginnev'] != $_SESSION[get_db_tabla().'user']) {
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Csak saját bejelentés törölhető!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
            }else{
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
            }
        }else{
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Önnek nincs joga ütemezett feladatot törölni!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        }

        if( _jog(JOG_ESETI_FENN_KEZEL) ){

            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="$(\'#szerk_form\').submit()">
                </div>
            ').$gombok;
        }

        $muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 16).'px);"';
        if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
            if( $_COOKIE['csuszkaSzelesseg'] ) {
                $muv_style = ' style="width: calc(100% - '.($_COOKIE['csuszkaSzelesseg'] + 16).'px);"';
            }
        }

        /* Műveletek box */
        $gombok = nl2('
            <div class="muveletek"'.$muv_style.'>
                '.$gombok.'
            </div>
        ');
    }

	//Új termék felvitele
	if( isset($_GET['uj']) && _jog(JOG_ESETI_FENN_KEZEL) ){

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

        $js_submit_elott = 'return form_ell_admin();';

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

	//Termék módosítása
	}elseif(isset($_GET['modosit'])) {

        //Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
        }

	    $sor = $adatbazis_kapcsolat -> select('*', $megnevezes, 'recid='.$_GET['id']);
		$sor = $sor[0];

        //Régi adatbázisól jövő adatok miatt szükséges:
        if($sor['cikltip']=='d'){
            $sor['ciklnap'] = $sor['ciklszorz'];
        }
        if($sor['cikltip']=='m' && $sor['ciklaltip']=='1'){
            $sor['ciklho'] = $sor['ciklszorz'];
        }
        if($sor['cikltip']=='m' && $sor['ciklaltip']=='2'){
            $sor['ciklho2'] = $sor['ciklszorz'];
        }
//echo $adatbazis_kapcsolat->gp() . '<br>';
        if( $sor['datetime'] ) {
            $_SESSION['formMod'] = array(
                'datetime' => $sor['update_dt'],
                'oldal'    => basename(self())
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

        $json_sor = array();
        foreach($sor as $kulcs => $elem){
            $json_sor[$kulcs] = nl2(str_replace(array("\n", "'"), array("#UJSOR#", "#APOSZTROF#"), $elem));
        }
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

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

        //Ckeditort csak akkor húzzuk be, ha kell
        if(is_file($ckeditor_js)){
            $ckeditor = '<script src="'.$ckeditor_js.'"></script>';
        }

		// Ez elkészíti a $mezok változót
        require('switch.php');

        $jobb_oldal = '';

	    $szamla_html .= str_replace(
            array('{JS}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}', '{MUV_STYLE}'),
            array( $js,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok,    $muv_style  ),
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

	//Státusz
    }elseif(isset($_GET['naptar'])) {

        //Hét beoszás fejléce
        $fejlec = '';
        if(isset($_GET['akt_nap'])){
            $indulo = date2int($_GET['akt_nap']);
        }else{
            $indulo = time();
        }
        for($i=0; $i<6; $i++){
            $idobelyeg = $indulo+($i*60*60*24);
            $fejlec .= '<th>'.date('Y-m-d', $idobelyeg).'<br>'.het_napja( date('w', $idobelyeg) ).'</th>';
        }

        //Hét beoszás sorai
        $sorok = '';
        for($i=0; $i<24; $i++){

            $ora = $i;
            if($ora<10){
                $ora = '0'.$ora;
            }

            $napok = '';
            for($j=0; $j<6; $j++){

                $db = count($adatbazis_kapcsolat->s('id', $megnevezes, "`esnap_d`='".date('Y-m-d', strtotime('+'.$j.' days'))."' AND `elv_ido_tol`='$ora:00'"));
//echo $adatbazis_kapcsolat->gp() . '<br>';
                if(!$db){
                    $db = '';
                }
                $napok .= '<td>'.$db.'</td>';
            }
            $sorok .= nl2('
                <tr>
                    <td>'.$i.':00</td>
                    '.$napok.'
                </tr>
                ');
        }

        $szamla_html .= nl2('
            <table class="csikos" style="margin-top: 4rem">
            <tr>
                <th>Órák</th>
                '.$fejlec.'
            <tr>
            '.$sorok.'
            </table>
            ');

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	//Ütemezés
    }elseif(isset($_GET['utemezes'])) {

        include('datumra_utemezes.php');
        $szamla_html .= 'Ön a következő ütemezést választotta:<br>';

        $sor = $adatbazis_kapcsolat->s('*', $megnevezes, "`recid`='{$_GET['id']}'");
        $sor = $sor[0];

        $kell_lista = true;
        $utemezes = 'Folyamatos';
        if($sor['idoszakos']){
            if($sor['aktkezd_d']>'0000-00-00' && $sor['aktkezd_d']>'0000-00-00'){
                $utemezes = $sor['aktkezd_d'].'-'.$sor['aktvege_d'];
                if($sor['aktvege_d']<date('Y-m-d')){
                    $utemezes = $sor['aktvege_d'].' után nincsenek további ütemezések.';
                    $kell_lista = false;
                }
            }else{
                require_once 'selectek.php';
                $kezdho = '';
                foreach($select_honapok as $honap){
                    if($honap['value']==$sor['kezdho']){
                        $kezdho = $honap['name'];
                        break;
                    }
                }
                $vegeho = '';
                foreach($select_honapok as $honap){
                    if($honap['value']==$sor['vegeho']){
                        $vegeho = $honap['name'];
                        break;
                    }
                }
                $utemezes = $kezdho.' '.$sor['kezdnap'].'. - '.$vegeho.' '.$sor['vegenap'].'.';
            }
        }

        if(!$sor['esem_d']){
            $sor['esem_d'] = date('Y-m-d');
        }
        $esem_d     = substr(str_replace('-', '.', $sor['esem_d']), 0, 10);
        $esnap_d    = str_replace('-', '.', $sor['esnap_d']);
        $aktkezd_d  = str_replace('-', '.', $sor['aktkezd_d']);
        $aktvege_d  = str_replace('-', '.', $sor['aktvege_d']);

        $szamla_html .=  "esemd: $esem_d<br>cikltip: $sor[cikltip]<br>ciklaltip: $sor[ciklaltip]<br>ciklszorz: $sor[ciklszorz]<br>ciklho: $sor[ciklho]<br>ciklhet: $sor[ciklhet]<br>ciklnap: $sor[ciklnap]<br>idoszakos: $sor[idoszakos]<br>kezdho: $sor[kezdho]<br>kezdnap: $sor[kezdnap]<br>vegeho: $sor[vegeho]<br>vegenap: $sor[vegenap]<br>aktkezd_d: $aktkezd_d<br>aktvege_d: $aktvege_d";
        $getnextvaliddate = getnextvaliddate(false, $esem_d, $sor['cikltip'], $sor['ciklaltip'], $sor['ciklszorz'], $sor['ciklho'], $sor['ciklhet'], $sor['ciklnap'], $sor['idoszakos'], $sor['kezdho'], $sor['kezdnap'], $sor['vegeho'], $sor['vegenap'], $aktkezd_d, $aktvege_d);
        $szamla_html .= '<br>Szabály: ' . getcimke( $sor['cikltip'], $sor['ciklaltip'], $sor['ciklszorz'], $sor['ciklho'], $sor['ciklhet'], $sor['ciklnap'], $getnextvaliddate).'<br><br>';
        $szamla_html .= '<hr>Ütemezés: ' . $utemezes;
        $szamla_html .= '<hr>Esedékes: ' . $sor['esnap_d'].' '.het_napja_txt($sor['esnap_d']);
        $szamla_html .= '<hr>Utolsó végrehajtás: ' . $esnap_d .' '.het_napja_txt($esnap_d);
        $szamla_html .= '<br>Következő: ' . $getnextvaliddate.' '.het_napja_txt($getnextvaliddate);
        $szamla_html .= '<br>';
        $szamla_html .= '<br>Következő 10 iteráció:<br>';

        for($i=0; $i<10; $i++){
            $getnextvaliddate = getnextvaliddate(false, $getnextvaliddate, $sor['cikltip'], $sor['ciklaltip'], $sor['ciklszorz'], $sor['ciklho'], $sor['ciklhet'], $sor['ciklnap'], $sor['idoszakos'], $sor['kezdho'], $sor['kezdnap'], $sor['vegeho'], $sor['vegenap'], $aktkezd_d, $aktvege_d);
            $szamla_html .= $getnextvaliddate . '<br>';
        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }

    /* Dokumentumok */
    }elseif(isset($_GET['dokumentumok'])) {

        if( $tobb_kep ) {
            require_once('tobb_kep.php');
        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }

    /* Megjegyzés */
    }elseif(isset($_GET['megjegyzesek'])) {

        $szamla_html .= nl2('
            <div class="dt naplo kijCont" data-tabla="megjegyzes" data-mellek="megjegyzesek">
                <div class="dtr nFejlec">
                    <div class="dtc">Megjegyzés</div>
                    <div class="dtc">Hozzáadta</div>
                    <div class="dtc">Hozzáadva</div>
                </div>
        ');

        $sorok = $adatbazis_kapcsolat->s('recid megj insert_usr insert_dt', 'megjegyzes', "`id` = '".$_GET['id']."' AND `tabla` = '".str_replace('.php', '', basename(self()))."'", 'insert_dt desc', '');

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

        $szamla_html .= '</div>';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
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
