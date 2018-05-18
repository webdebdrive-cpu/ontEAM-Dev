<?php
require_once('../weblap.php');

// Config
if( !_jog(JOG_MUNKALAP_MEGTEKINT) && !_jog(JOG_MUNKALAP_MEGTEKINT_SZALL) && !_jog(JOG_MUNKALAP_MEGTEKINT_FELADAT) ) {
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
$megnevezes_origi       = substr($megnevezes, 0, strlen($megnevezes)-5);
$akt_tomb               = $munkalap_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'munkalapot';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

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
$formazatlan_szam       = array();          //Azok az oszlopneveket sorold fel, melyek numerikus értéket is tartalmazhatnak, de nem szeretnéd, ha lista nézetben ezres csoportra bontaná

$szamla_html = '';

// Több kép menedzseléséhez
$tobb_kep = true; // Ha true, akkor js/jquery-ui.js, js/admin_sorrendez.js aktivál és minify!, admin/tobb_kep.php aktivál
if($tobb_kep){
    function get_tobbkep_darab()    { return 10;         } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '';         } // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;         } // MB-ban
    function get_tobbkep_oszlopnev(){                      // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes_origi;
        return $megnevezes_origi.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

}


// Ha mobilcsoportra és státuszra egyszerre akarunk szűrni:
if (isset($_GET['felvett_mobilcsop'])) {
    $_SESSION['o_szuro_feltetel']   = 'me|stat_id#op|equal#fe|08#es|#me|mobil#op|equal#fe|'.$_GET['csop'].'#es|#';
    $_SESSION['o_szuro_html']       = '<div class="dtr"><div class="dtc"><b class="zaro nyit">(</b></div><div class="dtc"><input class="mezo" value="stat_id" type="hidden"><input class="op" value="equal" type="hidden"><input class="felt" value="08" type="hidden"><span class="m_mezo">Státusz</span> <span class="m_op">=</span> <span class="m_felt">Felvéve</span> </div><div class="dtc"><b class="zaro zar">)</b></div><div class="dtc"><select style="width: 7rem" class="es_vagy" onchange="osszetett_szuro_mondat()"><option value="es|">ÉS</option><option value="va|">VAGY</option></select></div><div class="dtc"><b class="torol">×</b></div></div><div class="dtr"><div class="dtc"><b class="zaro nyit">(</b></div><div class="dtc"><input class="mezo" value="mobil" type="hidden"><input class="op" value="equal" type="hidden"><input class="felt" value="'.$_GET['felvett_mobilcsop'].'" type="hidden"><span class="m_mezo">Mobilcsoport</span> <span class="m_op">=</span> <span class="m_felt">'. strtoupper($_GET['csop']).'</span> </div><div class="dtc"><b class="zaro zar">)</b></div><div class="dtc"><select style="width: 7rem" class="es_vagy" onchange="osszetett_szuro_mondat()"><option value="es|">ÉS</option><option value="va|">VAGY</option></select></div><div class="dtc"><b class="torol">×</b></div></div>';
}
if (isset($_GET['szures_vege'])) {
    $_SESSION['o_szuro_feltetel']   = '';
    $_SESSION['o_szuro_html']       = '';
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

        $sql = 'SELECT
                `'.get_db_tabla().$megnevezes.'`.`recid` as \'Id\',
                `'.get_db_tabla().$megnevezes.'`.`telephely_id` as \'Szálloda\',
                `obj_id` as \'Helyiség\',
                `ikt_szam` as \'Munkalap szám\',
                `'.get_db_tabla().$megnevezes.'`.`update_dt` as \'Bejelentett hiba\',
                `'.get_db_tabla().$megnevezes.'`.`insert_dt` as \'Bejelentés dátuma\',
                `surgos` as \'Sürgős\',
                `stat_id` as \'Státusz\',
                `visszaadva` as \'Visszaadva\',
                `text` as \'Leírás\',
                `gep_id` as \'Gépnév\',
                `tipus_id` as \'Típus\',
                `szallito_id` as \'Külső kivitelező\',
                `szemelyzet_id` as \'Hozzárendelt személyzet\',
                `ig_el_id` as \'Igényelt elvégzés\',
                `varh_erk` as \'Várható érkezés\'
                '.$lekerdezes_fromtol;

        $query      = mysqli_query($db_kapcs, $sql);

        $csvhead    = array();
        while($fejlec_nevek = mysqli_fetch_field($query)){
            $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
        }
        unset($csvhead[0]);
        fputcsv($open, $csvhead, ';');

        while( $sorok = mysqli_fetch_assoc($query) ){
            $csvcont = array();

            $bejelentes = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs_arch', "`munkalap_id`='{$sorok['Id']}'");
            $bejelentes = $adatbazis_kapcsolat->s2('hiba_id insert_dt', 'bejelentes_arch', "`recid`='{$bejelentes['bejelentes_id']}'");
            foreach( $sorok as $kulcs => $sor ) {

                if($kulcs=='Id'){
                    continue;
                }
                if($kulcs=='Szálloda'){
                    $sor = getErtek('nev', 'telephely', 'id', $sor);
                }
                if($kulcs=='Bejelentett hiba'){
                    $sor = getErtek('info', 'erteklista', 'recid', $bejelentes['hiba_id']);
                }
                if($kulcs=='Bejelentés dátuma'){
                    $sor = $bejelentes['insert_dt'];
                }
                if($kulcs=='Gépnév'){
                    $sor = getErtek('nev', 'eszkozkezeles', 'recid', $sor);
                }
                if($kulcs=='Helyiség'){
                    $sor = getErtek('nev', 'obj', 'recid', $sor);
                }
                if($kulcs=='Hozzárendelt személyzet'){
                    $sor = getErtek('nev', 'szerepkor', 'recid', $sor);
                }
                if($kulcs=='Igényelt elvégzés' || $kulcs=='Várható érkezés'){
                    if($sor=='0000-00-00 00:00:00'){
                        $sor = '';
                    }
                }
                if($kulcs=='Külső kivitelező'){
                    $sor = getErtek('nev', 'szallito', 'id', $sor);
                }
                if($kulcs=='Típus'){
                    $sor = erteklista(6, $sor);
                }
                if($kulcs=='Státusz'){
                    $sor = getStatText('munkalap', $sor);
                }
                if($kulcs=='Sürgős'){
                    if($sor){
                        $sor = 'igen';
                    }else{
                        $sor = 'nem';
                    }
                }
                if($kulcs=='Visszaadva'){
                    if($sor){
                        $sor = 'igen';
                    }else{
                        $sor = 'nem';
                    }
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
require('selectek.php');

    if( !$_GET['ajax'] ) {
        echo '<div id="szamlaHidden" class="dn"></div><div id="szamlaBetolt" data-recid="'.$_GET['id'].'" data-page="'.str_replace('.php', '', basename(self())).'">';
    }

    // Megjegyzések darabszáma
    $megj_db = '';
    $megj_sorok = $adatbazis_kapcsolat->s('recid', 'megjegyzes_arch', "`tabla` = '".$megnevezes_origi."' AND `id` = '".$_GET['id']."'");
    if( $megj_sorok ) {
        $megj_db = ' ('.count($megj_sorok).')';
    }

    // Dokumentumok darabszáma
    $dok_db = '';
    $dok_sorok = $adatbazis_kapcsolat->s('recid utvonal', $megnevezes_origi.'_dok_arch', "`{$megnevezes_origi}_id` = '".$_GET['id']."'");
    if( $dok_sorok ) {
        $dok_db = 0;

        foreach($dok_sorok as $dok_sor) {
            if( is_file('../'.get_utvonal().$megnevezes_origi.'/'.$dok_sor['utvonal']) ) {
                $dok_db += 1;
            }
        }

        $dok_db = ' ('.$dok_db.')';
    }

    if( isset($_GET['modosit']) || isset($_GET['tortenet']) || isset($_GET['naptar']) || /*isset($_GET['bejelentesek']) ||*/ isset($_GET['tortenet']) || isset($_GET['anyagigenyles']) || isset($_GET['elszamolas']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) || isset($_GET['tetel']) ) {
        // Fülek
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'tortenet'      => 'Történet',
            'tetel'         => 'Tételek',
            //'bejelentesek'  => 'Bejelentések',
            'elszamolas'    => 'Elszámolás',
            'anyagigenyles' => 'Anyagigénylések',
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

    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");
    // Hibaüzeneteknek készítünk két hidden inputot, ami midnig elérhető lesz
    $szamla_html .= '<div class="dn" id="ikt_szam_hibauzibe">'.$sor['ikt_szam'].'</div>';
    $szamla_html .= '<div class="dn" id="telephely_hibauzibe">'. getErtek('nev', 'telephely', 'id', $sor['telephely_id']).'</div>';

    // Gombok nincsenek
    $gombok = '';

    $js_submit_elott = 'return form_ell_admin();';

	if(isset($_GET['modosit'])) {

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
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

        $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$sor['telephely_id'], '', '1');
        $telephely = $telephely[0]['nev'];

        $munkalap_kapcs = $adatbazis_kapcsolat->s('recid', 'munkalap_kapcs', "`munkalap_id` = '".$sor['recid']."'");

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
	    //$js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

		// Form actionja
		$action = self().get_get(array('errnum','kephiba','siker'));

        if(isset($_GET['ujkent'])){
			foreach($akt_tomb as $ertek){
				$sor[$ertek['name']] = $_SESSION[$ertek['name']];
			}
		}

        // Ckeditort csak akkor húzzuk be, ha kell
        if(is_file($ckeditor_js)){
            $ckeditor = '<script src="'.$ckeditor_js.'"></script>';
        }

		// Ez elkészíti a $mezok változót
		require('switch.php');
        $mezok .= '<input type="hidden" id="ujkentSzerkID" name="ujkentSzerkID" value="'.$_GET['ujkent'].'">';

	    $szamla_html .= str_replace(
            array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}'    ),
            array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok       ),
            fajlbol_olvas('../sablon/admin/form_modosit_arch.html', true)
        );

        // Bejelentés táblázata
        $munkalap_kapcsok = $adatbazis_kapcsolat->s('recid bejelentes_id', 'munkalap_kapcs_arch', "`munkalap_id` = '{$_GET['id']}'");
        if( $munkalap_kapcsok ) {
            $szamla_html .= nl2('
                <div class="dt naplo kijCont" data-tabla="munkalap_kapcs" data-mellek="bejelentesek" style="margin-top: 3rem">
                    <div class="dtr nFejlec">
                        <div class="dtc">Bejelentés szám</div>
                        <div class="dtc">Szálloda</div>
                        <div class="dtc">Hiba</div>
                        <div class="dtc">Hiba leírása</div>
                        <div class="dtc">Bejelentő</div>
                        <div class="dtc">Bejelentve</div>
                        <div class="dtc">Státusz</div>
                        <div class="dtc"></div>
                    </div>
            ');
            $i = 1;

            foreach($munkalap_kapcsok as $munkalap_kapcs) {

                $sor = $adatbazis_kapcsolat->s2('recid ikt_szam telephely_id obj_id hiba_id insert_dt stat_id hiba_text usr_nev usr_telefon usr_email usr_beosztas_id usr_reszleg_id', 'bejelentes_arch', "`recid` = '".$munkalap_kapcs['bejelentes_id']."'", '', '1');
                if( $sor ) {

                    // Telephely
                    $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$sor['telephely_id'], '', '1');
                    $telephely = $telephely[0]['nev'];

                    // Helység
                    $helyseg = $adatbazis_kapcsolat->s('nev', 'obj', 'recid='.$sor['obj_id'], '', '1');
                    $helyseg = $helyseg[0]['nev'];

                    // Hiba
                    $hiba = $adatbazis_kapcsolat->s2('info', 'erteklista', "`csoport`=4 AND `recid`='{$sor['hiba_id']}'");

// HA MÉGIS KELLENE A TÖRLÉS, AKKOR A naploSor-nak adj egy "kijSor" class-t.
                    $szamla_html .= nl2('
                        <div id="naploSor-'.$sor['recid'].'" class="dtr nSor" data-id="'.$sor['recid'].'" data-i="'.$i.'">
                            <div class="dtc"><a href="bejelentes_arch.php?id='.$sor['recid'].'&modosit" target="_blank">'.$sor['ikt_szam'].'</a></div>
                            <div class="dtc">'.$telephely.' - '.$helyseg.'</div>
                            <div class="dtc">'.$hiba['info'].'</div>
                            <div class="dtc">'.nl2br2($sor['hiba_text']).'</div>
                            <div class="dtc">
                                '.$sor['usr_nev'].'
                                <br>'.erteklista(18, $sor['usr_beosztas_id']).'
                                <br>'.$sor['usr_telefon'].'
                                <br>'.erteklista(17, $sor['usr_reszleg_id']).'
                                <br>'.$sor['usr_email'].'
                            </div>
                            <div class="dtc">'.$sor['insert_dt'].'</div>
                            <div class="dtc">'.getStatText('bejelentes', $sor['stat_id']).'</div>
                            <div class="dtc link">
                            </div>
                        </div>
                    ');
                }

                $i++;
            }
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

    // Tételek fül
    } elseif(isset($_GET['tetel'])) {

        // Tétel sorok
        $i = 0;
        $sorok = $adatbazis_kapcsolat->s('recid atadta_dt visszaadva erteklista_id loginnev insert_dt insert_usr', 'munkalap_tetel_arch', "`munkalap_id` = '".$_GET['id']."'", 'recid desc');
        foreach($sorok as $sor) {

            $munkalap = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id', $megnevezes, "`recid`='{$_GET['id']}'");
            $kezeles_jog = false;
            if( _jog(JOG_MUNKALAP_SAJAT_SZALL_KEZEL) || (_jog(JOG_MUNKALAP_SAJAT_KEZEL) && $munkalap['szemelyzet_id']==$_SESSION[get_db_tabla().'szemelyzet_id']) ) {
                $kezeles_jog = true;
            }

            $szamla_html .= nl2('
                <div class="tetel_div">
                    <table class="csikos tetel_csikos full">
                        <tr>
                            <th class="tetel_th1">Személyzet</th>
                            <th class="tetel_th2">Felvette</th>
                            <th class="tetel_th3">Átadta</th>
                            <th class="l">Visszaadva</th>
                        </tr>
            ');

            $visszaadva = '';
            if( $sor['visszaadva'] ) {
                $visszaadva = '<span class="icon piros"><i class="material-icons">done</i></span>';
            }

            $atadta = '';
            if( $sor['atadta_dt'] != '0000-00-00 00:00:00' ) {
                $atadta = date('Y.m.d H:i', strtotime($sor['atadta_dt']));
            }

            $atadas_oka = erteklista(ERTEKLISTA_ATADAS_OKA, $sor['erteklista_id']);
            if( $atadas_oka ) {
                $atadas_oka = '<br>'.$atadas_oka;
            }

            // Szerepkör
            $tetel_felh = $adatbazis_kapcsolat->s2('szerepkor_id', 'login', "`nev` = '".$sor['loginnev']."'");
            $szerepkor = '';
            if( $tetel_felh ) {

                $szerepkor_sor = $adatbazis_kapcsolat->s2('nev', 'szerepkor', "`recid` = '".$tetel_felh['szerepkor_id']."'", '', '1');
                if( $szerepkor_sor ) {
                    $szerepkor = '<br>('.$szerepkor_sor['nev'].')';
                }
            }

            $szamla_html .= nl2('
                <tr>
                    <td>'.$sor['insert_usr'].$szerepkor.'</td>
                    <td>'.date('Y.m.d H:i', strtotime($sor['insert_dt'])).'</td>
                    <td>'.$atadta.'</td>
                    <td class="l" style="padding-left: 3.7rem">'.$visszaadva.$atadas_oka.'</td>
                </tr>
            ');

            $szamla_html .= '</table>';

            // Elvégzett feladatok lekérdezése
            $feladatok = $adatbazis_kapcsolat->s('recid erteklista_id tetel_id memo', 'munkalap_feladat_arch', "`tetel_id` = '{$sor['recid']}'", 'insert_dt desc');
            if( $feladatok ) {
                $szamla_html .= nl2('
                    <div style="padding: 0 0.5rem">
                    <table class="csikos full al">
                        <tr>
                            <th class="feladat_th1">Elvégzett feladat</th>
                            <th class="feladat_th2">Leírás</th>
                            <th></th>
                        </tr>
                ');

                $kezeles_jog = get_kezeles_jog($munkalap['szemelyzet_id']);
                foreach($feladatok as $feladat) {

                    $szamla_html .= nl2('
                        <tr class="feladat_sor_'.$feladat['recid'].'">
                            <td id="elv_feladat_nev_'.$feladat['recid'].'">'.erteklista(5, $feladat['erteklista_id']).'</td>
                            <td id="elv_feladat_memo_'.$feladat['recid'].'">'.nl2br($feladat['memo']).'</td>
                            <td class="r"></td>
                        </tr>
                    ');
                }

                $szamla_html .= '</table></div>';
            }

            // Anyagigénylések lekérdezése
            $anyagok = $adatbazis_kapcsolat->s('recid cikk_id cikk_nev cikk_men szuk_men cikk_mee stat_id', 'munkalap_anyag_arch', "`tetel_id` = '{$sor['recid']}'", 'insert_dt desc', '');
            if( $anyagok ) {
                $szamla_html .= nl2('
                    <table class="csikos full al behuz">
                        <tr>
                            <th class="anyag_th1">Cikkszám</th>
                            <th class="anyag_th2">Anyag felhasználás</th>
                            <th class="anyag_th3">Felhasznált mennyiség</th>
                            <th class="anyag_th4">Szükséges mennyiség</th>
                            <th class="anyag_th5">Anyag státusz</th>
                            <th class="anyag_th6"></th>
                        </tr>
                ');

                foreach($anyagok as $anyag) {

                    $cikk_men = '';
                    if( $anyag['cikk_men'] > 0 ) {
                        $cikk_men = $anyag['cikk_men'].' '.$anyag['cikk_mee'];
                    }

                    $szuk_men = '';
                    if( $anyag['szuk_men'] > 0 ) {
                        $szuk_men    = $anyag['szuk_men'].' '.$anyag['cikk_mee'];
                    }

                    $szamla_html .= nl2('
                        <tr class="anyag_sor_'.$anyag['recid'].'">
                            <td id="anyag_cikkid_'.$anyag['recid'].'">'.$anyag['cikk_id'].'</td>
                            <td id="anyag_nev_'.$anyag['recid'].'">'.$anyag['cikk_nev'].'</td>
                            <td class="r">'.$cikk_men.'</td>
                            <td class="r">'.$szuk_men.'</td>
                            <td>'.$munkAnyag_statusz[$anyag['stat_id']].'</td>
                            <td class="r"></td>
                        </tr>
                    ');
                }

                $szamla_html .= '</table>';
            }

            // Munkaidő ráfordítás lekérdezése
            $munkaidok = $adatbazis_kapcsolat->s('recid login_id ora perc', 'munkalap_munkaido_arch', "`tetel_id` = '{$sor['recid']}'", 'insert_dt desc', '');
            if( $munkaidok ) {
                $szamla_html .= nl2('
                    <table class="csikos full al behuz">
                        <tr>
                            <th class="mido_th1">Név</th>
                            <th class="mido_th2 r">Munkaidő ráfordítás</th>
                            <th class="mido_th3"></th>
                        </tr>
                ');

                foreach($munkaidok as $munkaido) {
                    if($munkaido['perc']<10){
                        $munkaido['perc'] = '0'.$munkaido['perc'];
                    }

                    $szamla_html .= nl2('
                        <tr class="munkaido_sor_'.$munkaido['recid'].'">
                            <td id="munkaido_nev_'.$munkaido['recid'].'">'.getErtek('teljes_nev', 'login', 'recid', $munkaido['login_id']).'</td>
                            <td id="munkaido_ido_'.$munkaido['recid'].'" class="r">'.$munkaido['ora'].':'.$munkaido['perc'].'</td>
                            <td class="r"></td>
                        </tr>
                    ');
                }

                $szamla_html .= '</table>';
            }

            $szamla_html .= '</div>';

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

	// Történet fül
	}elseif(isset($_GET['tortenet'])){

        $szamla_html .= get_tortenet($megnevezes);

        // Műveletek box
        $gombok = nl2('
            <div class="muveletek"></div>
        ');

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Anyagigénylés fül
	}elseif(isset($_GET['anyagigenyles'])){

        $sorok = $adatbazis_kapcsolat->s('recid cikk_id szuk_men', 'munkalap_anyag_arch', "`munkalap_id` = '".$_GET['id']."' AND `stat_id` IN (".DISZPNAG_STAT_ANYAGRA_VAR.", ".DISZPNAG_STAT_ANYAG_IGENYELVE.")", 'insert_dt desc');

        if( $sorok ) {
            $szamla_html .= nl2('
                <table class="csikos full">
                    <tr>
                        <th>Ikt szám</th>
                        <th>Cikkszám</th>
                        <th>Név</th>
                        <th>Igényelt men.</th>
                        <th>Rendelt men.</th>
                        <th>Státusz</th>
                    </tr>
            ');

            $i = 1;
            foreach($sorok as $sor) {
                $anyag_sor = $adatbazis_kapcsolat->s2('ikt_szam cikk_id nev igmen rndmen cikkmee stat_id', 'anyagigenyles', "`cikk_id` = '".$sor['cikk_id']."'", '', '1');

                if( $anyag_sor ) {

                    $szamla_html .= nl2('
                        <tr>
                            <td>'.$anyag_sor['ikt_szam'].'</td>
                            <td>'.$anyag_sor['cikk_id'].'</td>
                            <td>'.$anyag_sor['nev'].'</td>
                            <td>'.$anyag_sor['igmen'].' '.$anyag_sor['cikkmee'].'</td>
                            <td>'.$anyag_sor['rndmen'].' '.$anyag_sor['cikkmee'].'</td>
                            <td>'.getStatText('anyagigenyles', $anyag_sor['stat_id']).'</td>
                        </tr>
                    ');
                }
                $i++;
            }

            $szamla_html .= '</table>';
        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Elszámolás fül
	}elseif(isset($_GET['elszamolas'])){

        $szamla_html .= 'Elszámolás felülete';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }


	// Dokumentumok fül
	}elseif( isset($_GET['dokumentumok']) ) {

        if( $tobb_kep ) {
            require_once('tobb_kep.php');
        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Megjegyzések fül
	}elseif(isset($_GET['megjegyzesek'])){

        $szamla_html .= nl2('
            <div class="dt naplo kijCont" data-tabla="megjegyzes" data-mellek="megjegyzesek">
                <div class="dtr nFejlec">
                    <div class="dtc">Megjegyzés</div>
                    <div class="dtc">Hozzáadta</div>
                    <div class="dtc">Hozzáadva</div>
                </div>
        ');

        $sorok = $adatbazis_kapcsolat->s('recid szerepkor megj insert_usr insert_dt', 'megjegyzes_arch', "`id` = '".$_GET['id']."' AND `tabla` = '".str_replace('.php', '', basename(self()))."'", 'recid desc', '');
        if( $sorok ) {
            $i = 1;
            foreach($sorok as $sor) {
                $szerepkor = getErtek('nev', 'szerepkor', 'recid', $sor['szerepkor']);
                $szamla_html .= nl2('
                    <div id="naploSor-'.$sor['recid'].'" class="dtr nSor kijSor" data-id="'.$sor['recid'].'" data-i="'.$i.'">
                        <div class="dtc">'.htmlspecialchars_decode( nl2br($sor['megj']) ).'</div>
                        <div class="dtc">'.$sor['insert_usr'].' '.$szerepkor.'</div>
                        <div class="dtc">'.str_replace('-', '.', substr($sor['insert_dt'], 0, 16)).'</div>
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

    // szamlaBetolt div vége
    if( !$_GET['ajax'] ) {
        $szamla_html .= '</div>';
    }

if( !$_GET['ajax'] ) {
    require_once('footer.php');
}


// Kezelés jogot visszaadó függvény (felvéve státusz alatt)
function get_kezeles_jog($szemelyzet_id){
    $kezeles_jog = false;
    if(
            ( _jog(JOG_MUNKALAP_SAJAT_KEZEL)        && $szemelyzet_id == $_SESSION[get_db_tabla().'szemelyzet_id'] ) ||
            ( _jog(JOG_MUNKALAP_KEZEL_TELEPITETT)   && $szemelyzet_id == TELEPITETT_SZEMELYZET )                     ||
            ( _jog(JOG_MUNKALAP_KEZEL_MOBIL)        && $szemelyzet_id == MOBILCSOPORT          )                     ||
            // Ez utóbbi feltétek vizsgálat sokszor feles leges, mert a feltétel (ahol ez a függvény meghívódik) eleve kizárja a külső kivitelezőket. De nem baj, így általánosa meghívható ez a függvény.
            ( _jog(JOG_MUNKALAP_KEZEL_KULSO)        && $szemelyzet_id == KULSO_KIVITELEZO      )
        ) {
        $kezeles_jog = true;
    }
    return $kezeles_jog;
}
