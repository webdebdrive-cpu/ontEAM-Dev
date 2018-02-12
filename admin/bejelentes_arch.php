<?php
/*
Játékszabályok:

- Két eset van: Egy bejelentés egy munkalap illetve egy bejelentés több munkalap
- Ha egy munkalapot csoportosítunk, akkor a benne levő munkalapok automatikusan várakozóra is kerülnek
  (és áttételesen a hozzá tartozó bejelentés is).
- Ha kiveszünk belőle egy elemet, akkor annak státusza nyitottra vált (és áttételesen a hozzá tartozó bejelentés is).
- Csoportot lehessen egyben visszatenni nyitottnak, de ekkor nem bomlik fel a csoport.

*/

require_once('../weblap.php');


if( !_jog(JOG_BEJELENTES_MEGTEKINT) && !_jog(JOG_BEJELENTES_SAJAT_RESZL) ) {
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
$megnevezes_origi       = substr($megnevezes, 0, strlen($megnevezes)-5);
$akt_tomb               = $bejelentes_arch_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'bejelentést';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

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
$tobb_kep = true; // Ha true, akkor js/jquery-ui.js, js/admin_sorrendez.js aktivál és minify!, admin/tobb_kep.php aktivál
if($tobb_kep){
    function get_tobbkep_darab()    { return 10;        } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '';        } // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;        } // MB-ban
    function get_tobbkep_oszlopnev(){                     // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes_origi;
        return $megnevezes_origi.'_id';
    }
    function get_tobbkep_tabla(){ return 'bejelentes_arch_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!
}

// CSV generálása
if (isset($_GET['csv'])) {


    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=$megnevezes.csv");
    header("Pragma: no-cache");
    header("Expires: 0");

    $mibol   = get_web_charset();
    $mibe    = 'windows-1250';

    if( !is_dir('export') ) {
        mkdir('export');
    }

    if( !is_dir('export/csv') ) {
        mkdir('export/csv');
    }

    // Open the output stream
    $open = fopen('php://output', 'w');
    ob_start();

    if($open){

        $lekerdezes_fromtol = substr($_SESSION['akt_sql'], strpos($_SESSION['akt_sql'], 'FROM'));

        $sql = 'SELECT
                                                    `ikt_szam` as \'Bejelentés szám\',
                                                    `'.get_db_tabla().$megnevezes.'`.`telephely_id` as \'Szálloda\',
                                                    `hiba_id` as \'Bejelentett hiba\',
                                                    `hiba_text` as \'Hiba leírása\',
                                                    `obj_id` as \'Helyiség\',
                                                    `helyiseg_text` as \'Hely leírása\',
                                                    `gep_id` as \'Gépnév\',
                                                    `stat_id` as \'Státusz\'
                                                    '.$lekerdezes_fromtol;

        $query      = mysqli_query($db_kapcs, $sql);

        $csvhead    = array();
        while($fejlec_nevek = mysqli_fetch_field($query)){
            $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
        }
        fputcsv($open, $csvhead, ';');

        $i = 0;
        while( $sorok = mysqli_fetch_assoc($query) ){

            $csvcont = array();
            foreach( $sorok as $kulcs => $sor ) {
                if($kulcs=='Szálloda'){
                    $sor = $adatbazis_kapcsolat->s('nev', 'telephely', "`id`='{$sor}'");
                    $sor = $sor[0]['nev'];
                }
                if($kulcs=='Bejelentett hiba'){
                    $sor = $adatbazis_kapcsolat->s('info', 'erteklista', "`recid`='{$sor}'");
                    $sor = $sor[0]['info'];
                }
                if($kulcs=='Helyiség'){
                    $sor = $adatbazis_kapcsolat->s('nev', 'obj', "`recid`='{$sor}'");
                    $sor = $sor[0]['nev'];
                }
                if($kulcs=='Gépnév'){
                    $sor = $adatbazis_kapcsolat->s('nev', 'eszkozkezeles', "`recid`='{$sor}'");
                    $sor = $sor[0]['nev'];
                }
                $csvcont[] = iconv2($mibol, $mibe, strip_tags( htmlspecialchars_decode( $sor ) ) );
            }

            fputcsv($open, $csvcont, ';');

            //100 soronként kiírtatunk
            if ($i % 100 == 0) {
                flush();
            }

            $i++;
        }
    }

    // Get the contents of the output buffer
    $string = ob_get_clean();
    fclose($fh);

    // Stream the CSV data
    exit($string);

    $weblap->leallit();
    exit();
}

// Tartalom
if( !$_GET['ajax'] ) {
    require_once('header.php');
}
require_once('selectek.php');

    if( !$_GET['ajax'] ) {
        echo '<div id="szamlaBetolt" data-recid="'.$_GET['id'].'" data-page="'.str_replace('.php', '', basename(self())).'">';
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

    if( isset($_GET['modosit']) || isset($_GET['tortenet']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) || isset($_GET['munkalapok']) ) {
        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'tortenet'      => 'Történet',
            'munkalapok'    => 'Munkalapok',
            'dokumentumok'  => 'Dokumentumok'.$dok_db,
            'megjegyzesek'  => 'Megjegyzések'.$megj_db
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

    // Gombok nincsenek
    $gombok = '';

    $js_submit_elott = 'return form_ell_admin();';

    if(isset($_GET['modosit'])) {

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
            $json_sor[$kulcs] = str_replace(array("\r\n", "'"), array("#UJSOR#", "#APOSZTROF#"), $elem);
        }
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

        if($id_latszik){
            $navi = '<b>#'.$_GET['id'].'</b> '.$navi;
        }

        // Duplákáláskor feltett kérdés
        $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$sor['telephely_id'], '', '1');
        $telephely = $telephely[0]['nev'];
        $szamla_html .= nl2('
            <script>
                duplikalGomb  = "<p class=\"c\"><input class=\"gomb zold\" type=\"button\" value=\"Igen\" onclick=\"location.href = \''.basename(self()).'?uj&amp;ujkent='.$_GET['id'].'\'; \"><input class=\"gomb piros\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
                duplikaKerdes = \'Azonosító: '.$sor['ikt_szam'].'<br>Szálloda: '.$telephely.'<br><br>Biztos, hogy duplikálja?\';
            </script>
        ');

        // NAVIGÁCIÓ
        $navi_sablon = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit    = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel  = array( $navi,             '',         $gyk2 );
        // echo str_replace($navi_mit, $navi_mivel, $navi_sablon);

	    // HIBAüZENETEK
        require_once('info.php');

	    // FORM
	    //$js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

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
            fajlbol_olvas('../sablon/admin/form_modosit_arch.html', true)
        );

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom'  => $szamla_html,
                'link'      => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

	// Történet fül
    }elseif(isset($_GET['tortenet'])) {

        $szamla_html .= get_tortenet($megnevezes);

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Munkalapok fül
	}elseif(isset($_GET['munkalapok'])){

        $szamla_html .= nl2('
            <div class="dt naplo kijCont" data-tabla="munkalap_kapcs" data-mellek="munkalapok">
                <div class="dtr nFejlec">
                    <div class="dtc">Bejelentés szám</div>
                    <div class="dtc">Szálloda</div>
                    <div class="dtc">Létrehozva</div>
                    <div class="dtc">Státusz</div>
                </div>
        ');

        $munkalap_kapcsok = $adatbazis_kapcsolat->s('recid munkalap_id', 'munkalap_kapcs_arch', "`bejelentes_id` = '{$_GET['id']}'");
        if( $munkalap_kapcsok ) {
            $i = 1;
            foreach($munkalap_kapcsok as $munkalap_kapcs) {
                
                // Csak akkor látszik a munkalap, ha az nyitott
                $sor = $adatbazis_kapcsolat->s2('recid ikt_szam telephely_id obj_id insert_dt stat_id', 'munkalap_arch', "`recid` = '".$munkalap_kapcs['munkalap_id']."'", '', '1');
                if( $sor ) {

                    // Telephely
                    $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$sor['telephely_id'], '', '1');
                    $telephely = $telephely[0]['nev'];

                    // Helység
                    $helyseg = $adatbazis_kapcsolat->s('nev', 'obj', 'recid='.$sor['obj_id'], '', '1');
                    $helyseg = $helyseg[0]['nev'];
                    
                    $ikt_szam = '<a href="munkalap.php?id='.$sor['recid'].'&modosit" target="_blank">'.$sor['ikt_szam'].'</a>';
                    if( !_jog(JOG_MUNKALAP_MEGTEKINT) && !_jog(JOG_MUNKALAP_MEGTEKINT_SZALL) && !_jog(JOG_MUNKALAP_MEGTEKINT_FELADAT) ){
                        $ikt_szam = $sor['ikt_szam'];
                    }
                    
                    $szamla_html .= nl2('
                        <div id="naploSor-'.$sor['recid'].'" class="dtr nSor kijSor" data-id="'.$sor['recid'].'" data-i="'.$i.'">
                            <div class="dtc">'.$ikt_szam.'</div>
                            <div class="dtc">'.$telephely.' - '.$helyseg.'</div>
                            <div class="dtc">'.$sor['insert_dt'].'</div>
                            <div class="dtc">'.getStatText('munkalap', $sor['stat_id']).'</div>
                        </div>
                    ');
                }

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


    // Dokumentumok fül
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

    // Megjegyzés
    }elseif(isset($_GET['megjegyzesek'])) {

        $szamla_html .= nl2('
            <div class="dt naplo kijCont" data-tabla="megjegyzes" data-mellek="megjegyzesek">
                <div class="dtr nFejlec">
                    <div class="dtc">Megjegyzés</div>
                    <div class="dtc">Hozzáadta</div>
                    <div class="dtc">Hozzáadva</div>
                </div>
        ');

        $sorok = $adatbazis_kapcsolat->s('recid megj szerepkor insert_usr insert_dt', 'megjegyzes_arch', "`id` = '".$_GET['id']."' AND `tabla` = '".$megnevezes_origi."'", 'recid desc');
        if( $sorok ) {
            $i = 1;
            foreach($sorok as $sor) {
                $szerepkor = getErtek('nev', 'szerepkor', 'recid', $sor['szerepkor']);
                $szamla_html .= nl2('
                    <div id="naploSor-'.$sor['recid'].'" class="dtr nSor kijSor" data-id="'.$sor['recid'].'" data-i="'.$i.'">
                        <div class="dtc">'.htmlspecialchars_decode( nl2br($sor['megj']) ).'</div>
                        <div class="dtc">'.$sor['insert_usr'].' '.$szerepkor.'</div>
                        <div class="dtc">'.$sor['insert_dt'].'</div>
                    </div>
                ');

                $i++;
            }
        }

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
