<?php
require_once('../weblap.php');
//Config

if( !_jog(JOG_ELOTERJ_MEGTEKINT) ) {
    if(!$_SESSION[get_db_tabla().'user']){
        $weblap -> leallit();
        header('Location: index.php');
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');
    $weblap -> leallit();
    exit('Nincs megfelelő jogosultság az oldal eléréséhez!');
}

$megnevezes             = pathinfo(self());
$megnevezes             = $megnevezes['filename'];
$akt_tomb               = $eloterj_fb_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'FB előterjesztést';  //Hibaüzenetkor: Biztosan törlöd a $szoveg?

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
$tobb_kep = true; // Ha true, akkor js/jquery-ui.js, js/admin_sorrendez.js aktivál és minify!, admin/tobb_kep.php aktivál
if($tobb_kep){
    function get_tobbkep_darab()    { return 10;        } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '';   } // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;        } // MB-ban
    function get_tobbkep_oszlopnev(){                     // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

}

// Előterjesztés véglegesítése
if (isset($_POST['eloterj_veglegesit'])) {

    //Státusz léptetés
    addStatusz( $megnevezes.'_statusz', $_POST['recid'], ELOT_FB_STAT_VEGLEGESITVE);
    
    //Az eredeti projektigénylés státuszát is léptetjük:
    $prik = $adatbazis_kapcsolat->s('projektigenyles_recid', $megnevezes.'_kapcs', "`eloterj_recid`='{$_POST['recid']}'");
    foreach($prik as $pri){
        addStatusz( 'projektigenyles_statusz', $pri['projektigenyles_recid'], PRIGENY_STAT_FB_TERJESZT);
    }
    
    $weblap->leallit();
    exit();
}

// Előterjesztés visszanyitása
if (isset($_POST['eloterj_visszanyit'])) {

    //Státusz léptetés
    addStatusz( $megnevezes.'_statusz', $_POST['recid'], ELOT_FB_STAT_NYITOTT);
    
    //Az eredeti projektigénylés státuszát visszaállítjuk:
    $prik = $adatbazis_kapcsolat->s('projektigenyles_recid', $megnevezes.'_kapcs', "`eloterj_recid`='{$_POST['recid']}'");
    foreach($prik as $pri){
        $elozo_stat = $adatbazis_kapcsolat->s2('stat_id', 'projektigenyles_eloterj', "`recid`='{$pri['projektigenyles_recid']}'");
        addStatusz( 'projektigenyles_statusz', $pri['projektigenyles_recid'], $elozo_stat['stat_id']);
    }
    
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
                `'.get_db_tabla().$megnevezes.'`.`stat_id` as \'Státusz\',
                `'.get_db_tabla().$megnevezes.'`.`ikt_szam` as \'Azonosító\',
                `'.get_db_tabla().$megnevezes.'`.`nev` as \'Név\',
                `'.get_db_tabla().$megnevezes.'`.`ules_datum` as \'Ülés dátuma\'
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












/** Tartalom */
if( !$_GET['ajax'] ) {
    require_once('header.php');
}
require_once('selectek.php');

//Ülés mezőre fókuszálás
$ulesre_fokuszalj = false;
if(isset($_GET['modosit_ules'])){
    $_GET['modosit'] = 1;
    $ulesre_fokuszalj = true;
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

    /* Engedélyek darabszáma */
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

    if( isset($_GET['modosit']) || isset($_GET['tortenet']) || isset($_GET['projektigeny']) || isset($_GET['megjegyzesek']) || isset($_GET['dokumentumok']) ) {
        /* Számla fülek */
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'tortenet'      => 'Történet',
            'projektigeny'  => 'Projektigények',
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

    /* Gombok kirajzolása minden oldalra */
    $gombok = '';
    if( isset($_GET['uj']) ) {
        
        $gombok = nl2('
            <div class="form_kuldes">
                <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="$(\'#szerk_form\').submit()">
            </div>
        ');

        $muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 16).'px);"';
        if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
            if( $_COOKIE['csuszkaSzelesseg'] ) {
                $muv_style = ' style="width: calc(100% - '.(($_COOKIE['csuszkaSzelesseg'] + 16)/10).'rem);"';
            }
        }

        /* Műveletek box */
        $gombok = nl2('
            <div class="muveletek"'.$muv_style.'>
                '.$gombok.'
            </div>
        ');

    //Módosítás gombok
    } else {

        $sor = $adatbazis_kapcsolat -> s2('stat_id', $megnevezes, "`recid`='{$_GET['id']}'");
        
        //Js, a törlés gombhoz
        $gombok  = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';
        
        if(!_jog(JOG_ELOTERJ_KEZEL)){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Önnek nincs joga előterjesztsét törölni!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        }elseif($sor['stat_id']!=ELOT_FB_STAT_NYITOTT){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'BoD előterjesztés csak nyitott státuszban törölhető!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        }else{
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
        }
        
        //Nyitott státuszban látható gombok
        if($sor['stat_id']==ELOT_FB_STAT_NYITOTT){
            $gombok .= '<input type="button" class="gomb foGomb" value="Igénylés csatolása" title="Igénylés csatolása" onclick="csoportMuvelet(\'eloterj_igneyles_csatol\');"> ';
            $gombok .= '
                <script>
                    veglegesit_gombok = \'<input type="button" class="gomb zold" value="Igen" onclick="eloterj_veglegesit()"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">\';
                    function eloterj_veglegesit(){
                        $.ajax({
                            url : \''.self().'\',
                            type: \'POST\',
                            data: {
                                recid                : \''.$_GET['id'].'\',
                                eloterj_veglegesit   : new Date().getTime()
                            },
                            success: function(response){
                                if(response.substring(0, 6)==\'SESKIL\'){ location.href = \'index.php\'; }
                                szamlaBetolt(\''.$_GET['id'].'\', \''.basename(self()).'\', \'modosit\');
                            }
                        });
                    }
                </script>';
            $gombok .= '<input type="button" class="gomb foGomb" value="Véglegesítés" title="Véglegesítés" onclick="alert2(\'Ha folytatja, a projektigénylések és az előterjesztés adatai már nem módosíthatók.<br>Biztos, hogy véglegesíti az előterjesztést?\', veglegesit_gombok);">';
        }
        
        //Véglegesítve státuszban látható gombok
        if($sor['stat_id']==ELOT_FB_STAT_VEGLEGESITVE){
            $gombok .= '
                <script>
                    visszanyit_gombok = \'<input type="button" class="gomb zold" value="Igen" onclick="eloterj_visszanyit()"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">\';
                    function eloterj_visszanyit(){
                        $.ajax({
                            url : \''.self().'\',
                            type: \'POST\',
                            data: {
                                recid                : \''.$_GET['id'].'\',
                                eloterj_visszanyit   : new Date().getTime()
                            },
                            success: function(response){
                                if(response.substring(0, 6)==\'SESKIL\'){ location.href = \'index.php\'; }
                                szamlaBetolt(\''.$_GET['id'].'\', \''.basename(self()).'\', \'modosit\');
                            }
                        });
                    }
                </script>';
            $gombok .= '<input type="button" class="gomb" value="Visszanyitás" title="Visszanyitás" onclick="alert2(\'Ha folytatja, az előterjesztés Nyitott státuszba kerül, a projekt igénylései előző státuszukba kerülnek.<br>Folytatja?\', visszanyit_gombok);"> ';
            
            //Projektigények fül alatt érhető csak el
            if (isset($_GET['projektigeny'])) {
                $gombok .= '<input type="button" class="gomb" value="FB döntés feldolgozása" title="FB döntés feldolgozása" onclick="eloterj_dontes_feldolgoz()"> ';
                $gombok .= '<input id="statusz_ment_gomb" type="button" class="gomb zold dn" value="Státuszok mentése" title="Státuszok mentése" onclick="pri_eloterj_statusz_mod_mentes()"> ';
                $gombok .= '<input type="button" class="gomb zold" value="Státuszok rögzítése" title="Státuszok rögzítése" onclick="pri_eloterj_statusz_mod_mentes(\'rogzít\')"> ';
            }
        }

        if(isset($_GET['modosit'])&& _jog(JOG_ELOTERJ_KEZEL)){
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="$(\'#szerk_form\').submit()">
                </div>
            ').$gombok;
        }

        $muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 16).'px);"';
        if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
            if( $_COOKIE['csuszkaSzelesseg'] ) {
                $muv_style = ' style="width: calc(100% - '.(($_COOKIE['csuszkaSzelesseg'] + 16)/10).'rem);"';
            }
        }

        /* Műveletek box */
        $gombok = nl2('
            <div class="muveletek"'.$muv_style.'>
                '.$gombok.'
            </div>
        ');
    }

    $js_submit_elott = 'return form_ell_admin();';

	//Új FB előterjesztés felvitele
	if(isset($_GET['uj']) && _jog(JOG_ELOTERJ_KEZEL)){
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

	//Termék módosítása
	}elseif(isset($_GET['modosit'])) {

        //Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
        }

	    $sor = $adatbazis_kapcsolat -> select('*', $megnevezes, 'recid='.$_GET['id']);
		$sor = $sor[0];
//echo $adatbazis_kapcsolat->gp() . '<br>';
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
            $json_sor[$kulcs] = str_replace(array("\r\n", "\n", "'"), array("#UJSOR#", "#UJSOR#", "#APOSZTROF#"), $elem);
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

	    $szamla_html .= str_replace(
            array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}'    ),
            array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok       ),
            fajlbol_olvas('../sablon/admin/form_modosit.html', true)
        );
        
        //Ülés mezőre fókuszálás
        if($ulesre_fokuszalj){
            $szamla_html .= '<script>
                                $(document).ready(function(){
                                    $(\'#ules_datum\').focus();
                                });
                            </script>';
        }

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
        
	/* Történet */
    }elseif(isset($_GET['tortenet'])) {

        $szamla_html .= get_tortenet($megnevezes);

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }
        
	//Projektigények
	}elseif(isset($_GET['projektigeny'])){

        //Csak nyitott státuszban törölhetőek ezek a rekordok
        $class = '';
        if($sor['stat_id']==ELOT_FB_STAT_NYITOTT){
            $class = ' kijCont';
        }
        
        $ules = $adatbazis_kapcsolat->s2('ules_datum', $megnevezes, "`recid`='{$_GET['id']}'");
        $szamla_html .= nl2('
            <input type="hidden" id="ules_datum" value="'.$ules['ules_datum'].'">
            <div class="dt naplo'.$class.'" data-tabla="eloterj_fb_kapcs" data-mellek="projektigeny">
                <div class="dtr nFejlec">
                    <div class="dtc">Státusz</div>
                    <div class="dtc">Szálloda</div>
                    <div class="dtc">Név</div>
                    <div class="dtc">Típus</div>
                    <div class="dtc r">Becsült költség</div>
                </div>
        ');
        
        $i = 1;
        $prigenyek = $adatbazis_kapcsolat->s('recid projektigenyles_recid', $megnevezes.'_kapcs', "`eloterj_recid`='{$_GET['id']}'", 'insert_dt desc');
        foreach($prigenyek as $prigeny){
            
            $pri = $adatbazis_kapcsolat->s2('recid nev stat_id stat_id_eloterj telephely_id tipus bkolt', 'projektigenyles_eloterj', "`recid`='{$prigeny['projektigenyles_recid']}'");
            
            // Szálloda
            $szalloda = $adatbazis_kapcsolat->s2('nev', 'telephely', "`id`='{$pri['telephely_id']}'");
            
            //Tipus
            require_once 'selectek.php';
            foreach($select_pri_tipus as $tip){
                if($tip['value']==$pri['tipus']){
                    $tipus = $tip['name'];
                }
            }
            if(!$pri['tipus']){
                $tipus = '-';
            }
            
            $statusz_tomb = array(
                                array('value' => PRIGENY_STAT_HIANYPOTLAS,      'name' => 'Visszaadás hiánypótlásra'),
                                array('value' => PRIGENY_STAT_BOD_TERJESZT_VAR, 'name' => 'BoD elé terjesztés'),
                                array('value' => PRIGENY_STAT_ELUTASITVA,       'name' => 'Elutasítás'),
                                array('value' => PRIGENY_STAT_JOVAHAGYVA,       'name' => 'Jóváhagyás'),
                                array('value' => PRIGENY_STAT_NYITOTT,          'name' => 'Műsz.Ig.-nak visszaadás döntésre'),
                            );
            
            $options = '';
            foreach($statusz_tomb as $stat){
                $selected = '';
                if($pri['stat_id_eloterj']==$stat['value']){
                    $selected = ' selected';
                }
                $options .= '<option'.$selected.' value="'.$stat['value'].'">'.$stat['name'].'</option>';
            }
            
            if($pri['stat_id_eloterj']){
                $pri['stat_id'] = $pri['stat_id_eloterj'];
            }
            
            $szamla_html .= nl2('
                <div data-id="'.$prigeny['recid'].'" data-i="'.$i.'" class="dtr nSor kijSor">
                    <div class="dtc">
                        <div class="prig_statusz_txt">'.getStatText('projektigenyles', $pri['stat_id']).'</div>
                        <div class="dn prig_statusz_select">
                            <select data-pri_eloterj_recid="'.$pri['recid'].'" onchange="$(\'#statusz_ment_gomb\').removeClass(\'dn\')">
                                <option value="">-Válasszon-</option>
                                '.$options.'
                            </select>
                        </div>
                    </div>
                    <div class="dtc">'.$szalloda['nev'].'</div>
                    <div class="dtc">'.$pri['nev'].'</div>
                    <div class="dtc">'.$tipus.'</div>
                    <div class="dtc r">'.number_format($pri['bkolt'], 0, '', ' ').' '.get_penznem().'</div>
                </div>
            ');
            $i++;
        }
        
        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
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
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	//Dokumentumok
	}elseif(isset($_GET['dokumentumok'])){
        
        if( $tobb_kep ) {
            require_once('tobb_kep.php');
        }

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
