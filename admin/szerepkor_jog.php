<?php
require_once('../weblap.php');

if( !_jog(JOG_SZEREPKOR_MEGTEKINT) ) {
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
$akt_tomb               = $_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'törzset';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

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
    function get_tobbkep_darab()    { return 10;        } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '.jpg,.png';} // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;        } // MB-ban
    function get_tobbkep_oszlopnev(){                     // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!
}

if( isset($_POST['jogokhozzaad']) ) {
    $id = $_POST['id'];

    // Kitöröljük a nem kívánatos POST elemeket
    unset($_POST['id']);
    unset($_POST['jogokhozzaad']);

    // Tömb sorrendezése
    $jogok = array();
    foreach($_POST as $key => $value) {
        list($x, $x, $szerepkor_recid) = explode('_', $key);
        list($szerepkor_recid, $kulcs) = explode('-', $szerepkor_recid);
        $szerepkor_recid = str_replace('sz', '', $szerepkor_recid);
        if(!is_array($jogok[$szerepkor_recid])){
            $jogok[$szerepkor_recid] = array();
        }
        $jogok[$szerepkor_recid][$kulcs] = $value;
    }
    
    foreach($jogok as $szerepkor_recid => $szerepkor_tomb){
        ksort($szerepkor_tomb);
        $adatbazis_kapcsolat -> u('szerepkor', array('flags'), array(implode('', $szerepkor_tomb)), "`recid`='$szerepkor_recid'");
    }

    $weblap->leallit();
    hd($_SERVER['REQUEST_URI']);

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
            `'.get_db_tabla().'cikktorzs`.`id` as \'Cikkszám\',
            `'.get_db_tabla().'cikktorzs`.`nev` as \'Név\'
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
                if($kulcs=='Bejelentett hiba'){
                    $sor = getErtek('info', 'erteklista', 'recid', $sor);
                }
                if($kulcs=='Helyiség'){
                    $sor = getErtek('nev', 'obj', 'recid', $sor);
                }
                if($kulcs=='Gépnév'){
                    $sor = getErtek('nev', 'eszkozkezeles', 'recid', $sor);
                }
                if($kulcs=='Típus'){
                    $sor = erteklista(6, $sor);
                }
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

    if( isset($_GET['modosit']) || isset($_GET['tortenet']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'tortenet'      => 'Történet',
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
    //Js, a törlés gombhoz
    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';

    if( _jog(JOG_SZEREPKOR_KEZEL) ){

        $gombok = nl2('
            <div class="form_kuldes">
                <input type="submit" class="gombb mentes_gomb" id="mehet_gomb" disabled value="Mentés" onclick="formModositva = false; $(\'#szerk_form\').submit()">
            </div>
        ').$gombok;

    }

    $muv_style = ' style="width: calc(100% - '.(($_SESSION['defaultCsuszka'] + 16)/10).'rem);"';
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
    
    $js_submit_elott = 'return true;';


    //Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
    if(isset($_GET['id_hidden'])){
        $_GET['id'] = $_GET['id_hidden'];
    }

    $sor = $adatbazis_kapcsolat -> select('*', $megnevezes, 'recid='.$_GET['id']);
    $sor = $sor[0];

    if( $sor['datetime'] ) {
        $_SESSION['formMod'] = array(
            'datetime' => $sor['update_dt'],
            'oldal'    => basename(self())
        );
    }

    foreach($akt_tomb as $tomb) {
        if( $tomb['type'] == 'autocomplete' ) {
            
            if($tomb['csoport']){
                $kulso_ertek = $adatbazis_kapcsolat->s2('info', $tomb['tabla'], "`id`='{$sor[$tomb['name']]}' AND `csoport`='{$tomb['csoport']}'", '', '1');
                $kulso_ertek['nev'] = $kulso_ertek['info'];
            }else{
                $kulso_ertek = $adatbazis_kapcsolat->s2('recid nev', $tomb['tabla'], 'recid='.$sor[$tomb['name']], '', '1');
            }

            $sor[$tomb['name'].'_autocomp'] = '';
            if( $kulso_ertek ) {
                $sor[$tomb['name'].'_autocomp'] = $kulso_ertek['nev'];
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

    
    require_once 'jogosultsag.php';
    $html = nl2('
            <script>
                $(document).ready(function () {
                    $(\'.fix_elso_oszl\').width( ($(window).width()-450)/10+\'rem\');
                });
            </script>
            <style type="text/css">
                .sor{
                    max-width: none;
                }
                div.keret{
                    padding-top: 0;
                }
            </style>
            <input type="hidden" name="jogokhozzaad">
            ');
    
    $html .= nl2('
        <div class="fix_elso_oszl" style="width: 10rem">
        <table class="csikos">
    ');
    foreach($jogosultsagKatok as $kulcs_kat => $kat){
        $html .= nl2('
                    <tr>
                        <th class="szerepkor_jog_kat">'.$kat['nev'].'</th>
                        <th colspan="100%" class="szerepkor_jog" style="background: #fff; border: 0"></th>
                    </tr>
                    <tr>
                        <th class="szerepkor_jog">Jog</th>
                    ');
        $szerepkorok = $adatbazis_kapcsolat->s('recid nev flags', 'szerepkor', "", 'nev');
        foreach($szerepkorok as $szerepkor){
            $html .= '<th class="szerepkor_jog">'.str_replace(' ', '<br>', $szerepkor['nev']).'</th>';
        }
        $html .= '</tr>';

        foreach($jogosultsagok as $kulcs_jog => $jogSor){
            if($jogSor['kat']==$kulcs_kat){
                
                $html .= nl2('
                            <tr>
                                <td class="l">'.$jogSor['nev'].'</td>
                            ');
                foreach($szerepkorok as $szerepkor){
                
                    $checked = '';
                    if( $szerepkor['flags'][$kulcs_jog] ) {
                        $checked = ' checked="checked"';
                    }

                    $jogSor['nev'] .= ':';

                    $onclick = '';
                    $kikapcs = '';
                    if($jogSor['kikapcs']){
                        foreach(explode(',', $jogSor['kikapcs']) as $pipa_ki){
                            $kikapcs .= '$(\'#jog_k'.$kulcs_kat.'_sz'.$szerepkor['recid'].'-'.$pipa_ki.'\').prop(\'checked\', false).removeClass(\'mod\');';
                        }
                    }
                    $kikapcskor_kikapcs = '';
                    if($jogSor['kikapcskor_kikapcs']){
                        foreach(explode(',', $jogSor['kikapcskor_kikapcs']) as $pipa_ki){
                            $kikapcskor_kikapcs .= '$(\'#jog_k'.$kulcs_kat.'_sz'.$szerepkor['recid'].'-'.$pipa_ki.'\').prop(\'checked\', false).addClass(\'mod\');';
                        }
                    }
                    $bekapcs = '';
                    if($jogSor['bekapcs']!=''){
                        foreach(explode(',', $jogSor['bekapcs']) as $pipa_be){
                            $bekapcs .= '$(\'#jog_k'.$kulcs_kat.'_sz'.$szerepkor['recid'].'-'.$pipa_be.'\').prop(\'checked\', true).addClass(\'mod\');';
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
                
                    $html .= nl2('
                            <td>
                                <div class="cb'.$dn.'">
                                    <input type="hidden" name="jog_k'.$kulcs_kat.'_sz'.$szerepkor['recid'].'-'.$kulcs_jog.'" value="0">
                                    <input type="checkbox" class="check" name="jog_k'.$kulcs_kat.'_sz'.$szerepkor['recid'].'-'.$kulcs_jog.'" id="jog_k'.$kulcs_kat.'_sz'.$szerepkor['recid'].'-'.$kulcs_jog.'" value="1"'.$checked.$onclick.'>
                                    <label for="jog_k'.$kulcs_kat.'_sz'.$szerepkor['recid'].'-'.$kulcs_jog.'"></label>
                                </div>
                            </td>
                            ');
                }
                $html .= '</tr>';
            }
        }
    }
    $html .= nl2('
                    </table>
                </div>
                ');
    
    $mezok = $html;

    $jobb_oldal = '';
    $szamla_html .= str_replace(
        array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}'    ),
        array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok       ),
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


    if( !$_GET['ajax'] ) {
        echo '</div>';
    }

if( !$_GET['ajax'] ) {
    require_once('footer.php');
}
