<?php
require_once('../weblap.php');

if( !_jog(JOG_PRIGENY_SZ_MEGTEKINT) && !_jog(JOG_PRIGENY_B_MEGTEKINT) && !_jog(JOG_PRIGENY_F_MEGTEKINT) && !_jog(JOG_PRIGENY_T_MEGTEKINT) && !_jog(JOG_PRIGENY_EGY_MEGTEKINT) && !_jog(JOG_PRIGENY_TOBB_MEGTEKINT) ) {
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
$akt_tomb               = $projektigenyles_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'projekt igénylést';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

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

//Több kép menedzseléséhez
$tobb_kep = true; // Ha true, akkor js/jquery-ui.js, js/admin_sorrendez.js aktivál és minify!, admin/tobb_kep.php aktivál
if($tobb_kep){
    function get_tobbkep_darab()    { return 10;        } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return 'false';   } // Lényeges, hogy egy string legyen
    function get_tobbkep_kepmeret() { return 10;        } // MB-ban
    function get_tobbkep_oszlopnev(){                     // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

}

//Véleményező hozzáadás státusz ellenőrzés
if (isset($_POST['velemenyezok_status_ell'])) {

    $stat = $adatbazis_kapcsolat->s2('stat_id', $megnevezes, "`recid`='{$_POST['recid']}'");
    if( !in_array($stat['stat_id'], prigeny_velemenyezo_hozzaad_statuszok()) ){
        echo json_encode(array(
            'hiba' => 'Véleményezőt hozzáadni csak nyitott, véleményezés alatt vagy hiánypótolt státuszokban lehet!'
        ));
    }

    $weblap->leallit();
    exit();
}

// Véleményező hozzáadása ajaxosan
if( isset($_GET['addVelemenyezo']) ) {

    //Jogosultságkezelés
    if(!JOG_VELEMENYEZO_HOZZAAD){

        echo json_encode(array(
            'hiba' => 'Önnek nincs joga véleményezőt hozzáadni!'
        ));

        $weblap->leallit();
        exit();
    }
    
    $stat = $adatbazis_kapcsolat->s2('stat_id', $megnevezes, "`recid`='{$_GET['pr_igeny_recid']}'");
    if($stat['stat_id']!=PRIGENY_STAT_NYITOTT && $stat['stat_id']!=PRIGENY_STAT_VEL_ALATT){
        echo json_encode(array(
            'hiba' => 'Véleményezőt hozzáadni csak nyitott vagy véleményezés alatt státuszokban lehet!'
        ));

        $weblap->leallit();
        exit();
    }

    $login_recid    = $_GET['login_recid'];
    $pr_igeny_recid = $_GET['pr_igeny_recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $login_recid && $pr_igeny_recid ) {

        $felh = $adatbazis_kapcsolat->s2('nev', 'login', "`recid`='{$login_recid}'", '', '1');
        if($felh===false){ $db_error = true; }

        $isKapcs = $adatbazis_kapcsolat->s2('recid', 'projektigenyles_kapcs', "`projektigenyles_recid`='{$pr_igeny_recid}' AND `login_recid`='{$login_recid}'", '', '1');
        if( !$isKapcs ) {
            $kapcsID = $adatbazis_kapcsolat -> i('projektigenyles_kapcs',
                                                                            array('projektigenyles_recid',  'login_recid', 'loginnev',                          'insert_dt', 'insert_usr'                            ),
                                                                            array($pr_igeny_recid,          $login_recid,  $_SESSION[get_db_tabla().'user'],    'NOW()',     $_SESSION[get_db_tabla().'teljes_nev']  )
                                                );
//echo $adatbazis_kapcsolat->gp() . '<br>';
            if($kapcsID===false){ $db_error = true; }

            //Ha hozzáadunk egy véleményezőt, akkor a státuszt biztosan léptethetjük véleményezés alattra
            $akt_stat = $adatbazis_kapcsolat->s2('stat_id', 'projektigenyles', "`recid`='{$pr_igeny_recid}'");
            if($akt_stat['stat_id']!=PRIGENY_STAT_VEL_ALATT){
                if(addStatusz('projektigenyles_statusz', $pr_igeny_recid, PRIGENY_STAT_VEL_ALATT)===false){
                    $db_error = true;
                }
            }

            if( !$db_error ) {
                mysqli_commit($db_kapcs);

                echo json_encode(array(
                    'statusz' => 'siker',
                    'kapcsID' => $kapcsID,
                    'uzi'     => ''
                ));

            } else {

                mysqli_rollback($db_kapcs);

                echo json_encode(array(
                    'hiba' => tranzakcioHiba()
                ));
            }

        } else {

            echo json_encode(array(
                'hiba' => 'Az illető már hozzáadásra került, mint véleményező!'
            ));
        }
    }

    $weblap->leallit();
    exit();
}

//Egy vagy többszállodás select
if (isset($_POST['f_pri_egytobb'])) {

    $tobb_szalloda = '';
    if($_POST['egy_tobb']=='tobb'){
        $tobb_szalloda = 1;
    }
    
    $akt_tomb = array(
                    array('type' => 'select_felh_telephely','megn' => 'Szálloda:',      'tobb_szalloda' => $tobb_szalloda,        'name' => 'telephely_id',   'szuro' => 1,       'class' => 'disableNav',        'style' => 'width: 100%;', 'alert' => 'Kérem válasszon szállodát!', 'onchange' => 'szallodaVane( this );'),
                );
    require 'switch.php';
    
    echo $mezok;

    $weblap->leallit();
    exit();
}

// Vélemény törlése
if (isset($_POST['velemeny_torol'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $velemeny = $adatbazis_kapcsolat->s2('login_recid utvonal projektigenyles_recid', 'projektigenyles_velemeny', "`recid`='{$_POST['velemeny_recid']}'");
    if($velemeny===false){ $db_error = true; }
    if($velemeny['login_recid']!=$_SESSION[get_db_tabla().'login_recid']){
        echo json_encode(array(
            'hiba' => 'Csak saját vélemény törölhető!'
        ));

        $weblap->leallit();
        exit();
    }

    // Vélemény törlése
    $fajl = '../'.get_utvonal().'projektigenyles_velemeny/'.$velemeny['utvonal'];
    if(is_file($fajl)){
        unlink($fajl);
        remover('../'.get_utvonal().'projektigenyles_velemeny/'.$velemeny['utvonal'].'_*');
    }
    $sql = $adatbazis_kapcsolat->d('projektigenyles_velemeny', "`recid`='{$_POST['velemeny_recid']}'");
    if($sql===false){ $db_error = true; }

    // Ha egy fájlt törlünk, akkor véleményezés alatt lesz ismét az igénylés (hisz nincs mindenhol feltöltött vélemény, nem maradhat nyitott ha az lett volna)
    $akt_stat = $adatbazis_kapcsolat->s2('stat_id', 'projektigenyles', "`recid`='{$velemeny['projektigenyles_recid']}'");
    if($akt_stat['stat_id']!=PRIGENY_STAT_VEL_ALATT){
        if(addStatusz('projektigenyles_statusz', $velemeny['projektigenyles_recid'], PRIGENY_STAT_VEL_ALATT)===false){
            $db_error = true;
        }
    }
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);

        echo json_encode(array(
            'statusz' => 'siker',
            'kapcsID' => $kapcsID,
            'uzi'     => ''
        ));

    } else {

        mysqli_rollback($db_kapcs);

        echo json_encode(array(
            'hiba' => tranzakcioHiba()
        ));
    }


    $weblap->leallit();
    exit();
}

//Új vélemény feltöltésekor ellenőrizzük, hogy van-e már feltöltött vélemény?
if (isset($_POST['van_e_velemeny'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $van_velemeny = $adatbazis_kapcsolat->s2('recid', 'projektigenyles_velemeny', "`projektigenyles_recid`='{$_POST['recid']}' AND `login_recid`='{$_SESSION[get_db_tabla().'login_recid']}'");
    if($van_velemeny===false){ $db_error = true; }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);

        //Ha van már vélemény, akkor kérdezünk
        if($van_velemeny){
            echo json_encode(array(
                'kerdes' => 'Önnek már van feltölött véleménye. Biztosan felülírja?'
            ));
        //Ha nincs vélemény, akkor mehet egyből a feltöltés
        }else{
            echo json_encode(array(
                'mehet' => '1'
            ));
        }

    } else {

        mysqli_rollback($db_kapcs);

        echo json_encode(array(
            'hiba' => tranzakcioHiba()
        ));
    }

    $weblap->leallit();
    exit();
}

// Session id lekérdezése vélemény feltöltéshez
if (isset($_POST['get_session_id'])) {

    echo session_id();

    $weblap->leallit();
    exit();
}

// Státusz léptetés
if (isset($_POST['pri_statusz_leptet'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);


    //Jóváhagyáskor átmásoljuk a Projektkezelés menüpont alá
    $igenyles = $adatbazis_kapcsolat->s2('*', 'projektigenyles', "`recid`='{$_POST['recid']}'");
    if( $igenyles === false ) { $db_error = true; }

    //Jóváhagyáskor több mindenre figyelni kell
    if($_POST['statusz']==PRIGENY_STAT_JOVAHAGYVA){

        //Van-e jogunk hozzá? Ha nincs, akkor rollback
        if(!_jog(JOG_PRIGENY_JOVAHAGY)){
            echo json_encode(array(
                'hiba' => 'Önnek nincs joga projektigénylést jóváhagyni!'
            ));

            $weblap -> leallit();
            exit;
        }

        //Státusz ellenőrzés
        if( !in_array($igenyles['stat_id'], prigeny_jovahagyhato_statuszok()) ){
            echo json_encode(array(
                'hiba' => 'A projektigénylés ebben a státuszban nem hagyható jóvá!'
            ));

            $weblap -> leallit();
            exit;
        }

        //A projetk ikt.száma PI prefix helyett csak egy P-t kap
        $igenyles['ikt_szam'] = str_replace('PI', 'P', $igenyles['ikt_szam']);

        $mezok    = array('ikt_szam', 'telephely_id', 'nev', 'tipus', 'akt_adat', 'prior', 'besorol', 'rendk', 'megter', 'megter_cel', 'mertek', 'megter_adat', 'text', 'bkolt', 'loginnev');
        $ertekek  = array();
        foreach($mezok as $mezo){
            array_push($ertekek, $igenyles[$mezo]);
        }
        array_push($mezok,   'stat_id',             'insert_dt', 'insert_usr'                          );
        array_push($ertekek, PRKEZEL_STAT_NYITOTT,  'NOW()',     $_SESSION[get_db_tabla().'teljes_nev']);
        $prk_id = $adatbazis_kapcsolat->i('projektkezeles', $mezok, $ertekek);
        if( $prk_id === false ) { $db_error = true; }

        //Státusz táblába is beszúrunk
        $sql = $adatbazis_kapcsolat -> i('projektkezeles_statusz',
                                            array('projektkezeles_recid', 'stat_id',              'insert_dt',    'insert_usr',                             'update_usr'                           ),
                                            array($prk_id,                 PRKEZEL_STAT_NYITOTT,  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'] )
                                        );
        if( $sql === false ) { $db_error = true; }
    }

    //FB elé terjeszteni az alábbi státuszokkal nem lehet
    if( $_POST['statusz']==PRIGENY_STAT_FB_TERJESZT && !in_array($igenyles['stat_id'], prigeny_fb_ele_statuszok()) ){
            echo json_encode(array(
                'hiba' => '!Ebből a státuszból nem lehet FB elé terjeszteni a projektigénylést!'
            ));

            $weblap -> leallit();
            exit;
    }

    //BoD elé csak Elutasítás vagy Jóváhagyás joggal lehet
    if($_POST['statusz']==PRIGENY_STAT_BOD_TERJESZT){
        if(!_jog(JOG_PRIGENY_ELUTASIT) && !_jog(JOG_PRIGENY_JOVAHAGY)){
            echo json_encode(array(
                'hiba' => 'Önnek nincs joga projektigénylést BoD elé terjeszteni!'
            ));

            $weblap -> leallit();
            exit;
        }
    }

    //BoD elé terjeszteni az alábbi státuszokkal nem lehet
    if( $_POST['statusz']==PRIGENY_STAT_BOD_TERJESZT && !in_array($igenyles['stat_id'], prigeny_bod_ele_statuszok()) ){
            echo json_encode(array(
                'hiba' => 'Ebből a státuszból nem lehet BoD elé terjeszteni a projektigénylést!'
            ));

            $weblap -> leallit();
            exit;
    }

    //Státusz tényleges léptetése
    if(addStatusz('projektigenyles_statusz', $_POST['recid'], $_POST['statusz'])===false){
        $db_error = true;
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);

        echo json_encode(array(
            'statusz' => 'siker'
        ));
    }else{
        mysqli_rollback($db_kapcs);

        echo json_encode(array(
            'hiba' => tranzakcioHiba()
        ));
    }

    $weblap->leallit();
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
                `ikt_szam` as \'Azonosító\',
                `'.get_db_tabla().'projektigenyles`.`nev` as \'Név\',
                `stat_id` as \'Státusz\'
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
        echo '<div id="szamlaBetolt" data-recid="'.$_GET['id'].'" data-page="'.str_replace('.php', '', basename(self())).'">';
    }

    /* Megjegyzések darabszáma */
    $megj_db = '';
    $megj_sorok = $adatbazis_kapcsolat->s('recid', 'megjegyzes', "`tabla` = '".$megnevezes."' AND `id` = '".$_GET['id']."'", '', '');
    if( $megj_sorok ) {
        $megj_db = ' ('.count($megj_sorok).')';
    }

    /* Dokumentumok darabszáma */
    $dok_db = '';
    $dok_sorok = $adatbazis_kapcsolat->s('recid utvonal', $megnevezes.'_dok', "`{$megnevezes}_id` = '".$_GET['id']."'");
    if( $dok_sorok ) {
        $dok_db = 0;

        foreach($dok_sorok as $dok_sor) {
            if( is_file('../'.get_utvonal().$megnevezes.'/'.$dok_sor['utvonal']) ) {
                $dok_db += 1;
            }
        }

        $dok_db = ' ('.$dok_db.')';
    }

    if( isset($_GET['modosit']) || isset($_GET['tortenet']) || isset($_GET['velemenyezok']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) || isset($_GET['cash_flow']) ) {
        /* Számla fülek */
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'tortenet'      => 'Történet',
            'velemenyezok'  => 'Véleményezők',
            'cash_flow'     => 'Cash-flow',
            'dokumentumok'  => 'Dokumentumok'.$dok_db,
            'megjegyzesek'  => 'Megjegyzések'.$megj_db,
        );

        $fulek = '';
        foreach($ful_tomb as $key => $value) {
            $active = 'active';
            if( isset($_GET[$key]) ) {
                $active = ' class="active"';
            }
            
            if($key=='cash_flow'){
                $fulek .= '<a id="ful_'.$key.'" href="javascript:prigeny_ful_valt(\'cash_flow\');"'.$active.' data-oldal="'.$key.'">'.$value.'</a>';
            }else if($key=='modosit'){
                $fulek .= '<a id="ful_'.$key.'" href="javascript:prigeny_ful_valt(\'adatlap\');"'.$active.' data-oldal="'.$key.'">'.$value.'</a>';
            }else{
                $fulek .= '<a id="ful_'.$key.'" href="javascript: szamlaBetolt('.$_GET['id'].', \''.basename(self()).'\', \''.$key.'\'); void(0);"'.$active.' data-oldal="'.$key.'">'.$value.'</a>';
            }
        }

        $szamla_html .= str_replace(
            array('{FULEK}'),
            array( $fulek  ),
            fajlbol_olvas('../sablon/admin/szamla_fulek.html', true)
        );
    }
    
    // A prigen_tipus_beallit() függvényt meghívjuk betöltéskor
    $szamla_html .= nl2('
                            <script>
                                $(document).ready(function () {
                                
                                    prigen_tipus_beallit();
                                    if( $(\'#egy_tobb\').val()>0 ){
                                        $(\'#egy_tobb_tobb\').prop(\'checked\', true)
                                    }else{
                                        $(\'#egy_tobb_egy\').prop(\'checked\', true)
                                    }
                                        
                                });
                            </script>
                        ');

    /* Gombok kirajzolása minden oldalra */
    $gombok = '';
    if( isset($_GET['uj']) ) {

        //Újat csak "Projektigénylés kezelése" joggal lehet
        if(_jog(JOG_PRIGENY_S_KEZEL) || _jog(JOG_PRIGENY_T_KEZEL) ){
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

        $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");
        
        //Ha csak sajátot kezelhet, és adott rekord nem által van feltöltve, akkor nem szrkesztheti törlheti.
        //Ez egy hosszú feltétel, amikre többször is szükség van, ezért változóba kimentve
        $csak_sajat_kezelheti = true;
        if( _jog(JOG_PRIGENY_S_KEZEL) && !_jog(JOG_PRIGENY_B_KEZEL) && !_jog(JOG_PRIGENY_EGY_KEZEL) && !_jog(JOG_PRIGENY_F_KEZEL) && !_jog(JOG_PRIGENY_SZ_KEZEL) && !_jog(JOG_PRIGENY_TOBB_KEZEL) && !_jog(JOG_PRIGENY_T_KEZEL) && $sor['loginnev']!=$_SESSION[get_db_tabla().'user'] ) {
            $csak_sajat_kezelheti = false;
        }

        //Ha a státusz elutasítva vagy jóváhagyva, akkor semmilyen gomb nem érhető el
        if($sor['stat_id'] == PRIGENY_STAT_ELUTASITVA || $sor['stat_id'] == PRIGENY_STAT_JOVAHAGYVA ){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Csak létrehozás alatt, nyitott vagy véleményezés alatt státuszú projektigénylés törölhető!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        }
        if($sor['stat_id'] != PRIGENY_STAT_ELUTASITVA && $sor['stat_id'] != PRIGENY_STAT_JOVAHAGYVA){
            //Js, a törlés gombhoz
            $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';

            //Csak nyitott vagy véleményezés alatt státuszú törölhető
            if( $sor['stat_id']!=PRIGENY_STAT_LETRE_ALATT && $sor['stat_id']!=PRIGENY_STAT_NYITOTT && $sor['stat_id']!=PRIGENY_STAT_VEL_ALATT ) {
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Csak létrehozás alatt, nyitott vagy véleményezés alatt státuszú projektigénylés törölhető!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
            //Jogosutságkezelés: csak az törölheti, akinek van hozzá joga
            } elseif( !_jog(JOG_PRIGENY_B_KEZEL) && !_jog(JOG_PRIGENY_EGY_KEZEL) && !_jog(JOG_PRIGENY_F_KEZEL) && !_jog(JOG_PRIGENY_SZ_KEZEL) && !_jog(JOG_PRIGENY_S_KEZEL) && !_jog(JOG_PRIGENY_TOBB_KEZEL) && !_jog(JOG_PRIGENY_T_KEZEL) ){
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Önnek nincs joga projektigénylést törölni!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
            // Ha csak saját igénylések kezelése joga van és más kezelés nincs, és az igénylést nem saját maga hozta létre
            }elseif( !$csak_sajat_kezelheti ) {
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Ön csak saját projektigénylést törölhet!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
            } else {
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
            }

            //Ha a prigény véleményezés alatt van, és véleményező vagyok, akkor tudok véleményt feltölteni
            if($sor['stat_id']==PRIGENY_STAT_VEL_ALATT && $adatbazis_kapcsolat->s('recid', 'projektigenyles_kapcs', "`projektigenyles_recid`='{$_GET['id']}' AND `login_recid`='{$_SESSION[get_db_tabla().'login_recid']}'", '', '1')){
                $gombok .= ' <input type="button" class="gomb foGomb" value="Vélemény csatolása" title="Vélemény csatolása" onclick="velemeny_hozzaad();">';
            }

            //Hiánypótolt csak akkor lehet egy igénylés, ha most épp hiánypótlásra várunk és van bármilyen kezelési joga
            if($sor['stat_id'] == PRIGENY_STAT_HIANYPOTLAS && (_jog(JOG_PRIGENY_S_KEZEL) || (_jog(JOG_PRIGENY_T_KEZEL) && $sor['tipus']==PRIGENY_TIPUS_TARGY)  ) ) {
                $gombok .= ' <input type="button" class="gomb foGomb" value="Hiánypótolt" title="Hiánypótolt" onclick="pri_statusz_leptet(\''.$_GET['id'].'\', \''.PRIGENY_STAT_HIANYPOTOLT.'\');">';
            }
            
            // Ha van egyálátalán kezelésre joga
            if(_jog(JOG_PRIGENY_B_KEZEL) || _jog(JOG_PRIGENY_EGY_KEZEL) || _jog(JOG_PRIGENY_F_KEZEL) || _jog(JOG_PRIGENY_SZ_KEZEL) || _jog(JOG_PRIGENY_S_KEZEL) || _jog(JOG_PRIGENY_TOBB_KEZEL) || _jog(JOG_PRIGENY_T_KEZEL)){

                if($sor['stat_id'] != PRIGENY_STAT_VARAKOZO && $sor['stat_id'] != PRIGENY_STAT_HIANYPOTLAS) {
                    $gombok .= ' <input type="button" class="gomb foGomb" value="Várakoztatás" title="Várakoztatás" onclick="pri_statusz_leptet(\''.$_GET['id'].'\', \''.PRIGENY_STAT_VARAKOZO.'\');">';
                }
                if($sor['stat_id'] == PRIGENY_STAT_VARAKOZO) {
                    $gombok .= ' <input type="button" class="gomb foGomb" value="Várakoztatás vége" title="Várakoztatás vége" onclick="pri_statusz_leptet(\''.$_GET['id'].'\', \''.PRIGENY_STAT_NYITOTT.'\');">';
                }
                if( in_array($sor['stat_id'], prigeny_hianypotlasba_statuszok()) ) {
                    $gombok .= ' <input type="button" class="gomb foGomb" value="Visszaadás hiánypótlásra" title="Visszaadás hiánypótlásra" onclick="pri_statusz_leptet(\''.$_GET['id'].'\', \''.PRIGENY_STAT_HIANYPOTLAS.'\');">';
                }
                if( in_array($sor['stat_id'], prigeny_fb_ele_statuszok()) ) {
                    $gombok .= ' <input type="button" class="gomb foGomb" value="FB elé terjesztés" title="FB elé terjesztés" onclick="pri_statusz_leptet(\''.$_GET['id'].'\', \''.PRIGENY_STAT_FB_TERJESZT.'\');">';
                }
                if( in_array($sor['stat_id'], prigeny_bod_ele_statuszok()) ) {
                    $gombok .= ' <input type="button" class="gomb foGomb" value="BoD elé terjesztés" title="BoD elé terjesztés" onclick="pri_statusz_leptet(\''.$_GET['id'].'\', \''.PRIGENY_STAT_BOD_TERJESZT.'\');">';
                }

                $gombok .= ' <input type="button" class="gomb foGomb" value="FB előterjesztés nyomtatása" title="FB előterjesztés nyomtatása" onclick="window.open(\'projektigenyles.php?print=lista&amp;fb_elo\');">';

                //Vannak gombok amik itt a helyük, de a fő feltételen kívül dől el, hogy látszik-e (nem csak az a feltétle, hogy van-e minden kezelés jog)
                $gombok .= $gombok_meg;

                if( _jog(JOG_PRIGENY_ELUTASIT) && $sor['stat_id'] != PRIGENY_STAT_ELUTASITVA /*Ez a felétel felesleges, csak az egységesség miatt van itt*/) {
                    $gombok .= ' <input type="button" class="gomb piros foGomb" value="Elutasítás" title="Elutasítás" onclick="csoportMuvelet(\'elutasitas_csak_memo\');">';
                }

                if( _jog(JOG_PRIGENY_JOVAHAGY) && in_array($sor['stat_id'], prigeny_jovahagyhato_statuszok()) ) {
                    $gombok .= ' <input type="button" class="gomb zold foGomb" value="Jóváhagyás" title="Jóváhagyás" onclick="pri_statusz_leptet(\''.$_GET['id'].'\', \''.PRIGENY_STAT_JOVAHAGYVA.'\');">';
                }

                //Ha kezelheti, akkor van mentés gomb is
                if($csak_sajat_kezelheti){
                    $gombok = nl2('
                        <div class="form_kuldes">
                            <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="$(\'#szerk_form\').submit()">
                        </div>
                    ').$gombok;
                }
            }

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

    $js_submit_elott = 'return form_ell_admin();';
    
	//Új projektigénylés felvitele
	if( isset($_GET['uj']) && (_jog(JOG_PRIGENY_S_KEZEL) || _jog(JOG_PRIGENY_T_KEZEL) || _jog(JOG_PRIGENY_B_KEZEL) || _jog(JOG_PRIGENY_F_KEZEL) || _jog(JOG_PRIGENY_SZ_KEZEL)) ){

        $navi_sablon  = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit     = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel   = array( $navi,             '',         $gyk2 );
        $szamla_html .= str_replace($navi_mit, $navi_mivel, $navi_sablon);

	    //HIBAüZENETEK
	    require_once('info.php');

	    //FORM
		if(isset($_GET['ujkent'])){

            $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, 'recid='.$_GET['ujkent']);
            if( $sor ) {
                foreach($akt_tomb as $ertek) {
                    if( $ertek['name'] != 'ikt_szam' ) {
                        $_SESSION[$ertek['name']] = stripslashes( $sor[$ertek['name']] );
                    } else {
                        $_SESSION[$ertek['name']] = '';
                    }
    			}
            }

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
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

	//Projektigénylés módosítása
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
        $json_sor['mertek'] = number_format($json_sor['mertek'], 0, '', ' ');
        $json_sor['bkolt']  = number_format($json_sor['bkolt'],  0, '', ' ');
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

        if($id_latszik){
            $navi = '<b>#'.$_GET['id'].'</b> '.$navi;
        }

        //Duplákáláskor feltett kérdés
        $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$sor['telephely_id'], '', '1');
        $telephely = $telephely[0]['nev'];
        $szamla_html .= nl2('
            <script>
                duplikalGomb  = "<p class=\"c\"><input class=\"gomb zold\" type=\"button\" value=\"Igen\" onclick=\"location.href = \''.basename(self()).'?uj&amp;ujkent='.$_GET['id'].'\'; \"><input class=\"gomb piros\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
                duplikaKerdes = \'Azonosító: '.$sor['ikt_szam'].'<br>Szálloda: '.$telephely.'<br><br>Biztos, hogy duplikálja?\';
            </script>
        ');

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

        $tovabbi_tartalom = '';
        
        $tovabbi_tartalom .= nl2('
            <div id="cash_flow_div" class="dn">
                <div class="dt naplo kijCont" data-tabla="cash_flow" data-mellek="cash_flow">
                    <div class="dtr nFejlec">
                        <div class="dtc" style="width: 15rem">Dátum</div>
                        <div class="dtc">Összeg</div>
                    </div>
        ');

        $sorok = $adatbazis_kapcsolat->s('recid datum osszeg', 'cash_flow', "`apa_recid`='".$_GET['id']."'", 'insert_dt desc');
        if( $sorok ) {
            $i = 1;
            foreach($sorok as $sor) {
                $tovabbi_tartalom .= nl2('
                    <div id="naploSor-'.$sor['recid'].'" data-id="'.$sor['recid'].'" data-i="'.$i.'" class="dtr nSor kijSor">
                        <div class="dtc" style="width: 15rem">'.str_replace('-', '.', $sor['datum']).'</div>
                        <div class="dtc">'.number_format($sor['osszeg'], 0, '', ' ').' '.get_penznem().'</div>
                    </div>
                ');

                $i++;
            }
        }

        $tovabbi_tartalom .= '</div></div>';

	    $szamla_html .= str_replace(
            array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',           '{CK_EDITOR}', '{GOMBOK}'    ),
            array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $tovabbi_tartalom, $ckeditor,     $gombok       ),
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

    //Véleményezők fül
    }elseif(isset($_GET['velemenyezok'])) {

        /* Fejlécek */
        $szamla_html .= '<div id="tablazatBeszur">';
        $szamla_html .= nl2('
            <div class="dt naplo szakter kijCont" data-tabla="projektigenyles_kapcs" data-mellek="velemenyezok">
                <div class="dtr nFejlec">
                    <div class="dtc">Név</div>
                    <div class="dtc">Vélemény</div>
                    <div class="dtc">Feltöltve</div>
                    <div class="dtc"></div>
                    <div class="dtc"></div>
                </div>
        ');

        $i = 1;
        $velemenyezok = $adatbazis_kapcsolat->s('login_recid', 'projektigenyles_kapcs', "`projektigenyles_recid`='{$_GET['id']}'");
        $velemenyezok = adatbazis2tomb($velemenyezok, 'login_recid');
        if($velemenyezok){
            $velemenyezok = $adatbazis_kapcsolat->s('recid teljes_nev', 'login', "`recid` IN (".implode(',', $velemenyezok).")", 'teljes_nev');
            foreach($velemenyezok as $velemenyezo) {

                $kapcs_id = $adatbazis_kapcsolat->s2('recid', 'projektigenyles_kapcs', "`login_recid`='{$velemenyezo['recid']}' AND `projektigenyles_recid`='{$_GET['id']}'");

                //Vélemény fájl kiíratása
                $velemeny_html = '';
                $letoltes_html = '';
                $torles_html   = '';
                $velemeny = $adatbazis_kapcsolat->s2('recid utvonal fajlnev insert_dt', 'projektigenyles_velemeny', "`projektigenyles_recid`='{$_GET['id']}' AND `login_recid`='{$velemenyezo['recid']}'");
                if($velemeny){
                    $fajl = '../'.get_utvonal().'projektigenyles_velemeny/'.$velemeny['utvonal'];
                    $felirat = $velemeny['fajlnev'];
                    $kep     = new Img($fajl, $felirat, 150, 150);
                    $kep     = $kep -> getHtml();

                    $velemeny_html .= nl2('
                                        <div class="dtc" style="border-bottom: 0; padding: 0 1rem 0 0">
                                            <a href="'.$fajl.'" target="_blank">
                                                '.$kep.'
                                            </a>
                                        </div>
                                        <div class="dtc" style="border-bottom: 0; padding: 0">
                                            <a href="'.$fajl.'" target="_blank">
                                            '.$felirat.'
                                            </a>
                                        </div>
                                            ');

                    //Törölni csak saját fájlt lehet
                    if($velemenyezo['recid']==$_SESSION[get_db_tabla().'login_recid']){
                        $torles_html = nl2('
                                        <a href="javascript:velemeny_torol(\''.$velemeny['recid'].'\')" title="Törlés">
                                            <i class="material-icons piros">delete</i>
                                        </a>
                                        ');
                    }
                }

                $szamla_html .= nl2('
                    <div id="naploSor-'.$kapcs_id['recid'].'" class="dtr nSor kijSor" data-id="'.$kapcs_id['recid'].'" data-i="'.$i.'">
                        <div class="dtc">'.$velemenyezo['teljes_nev'].'</div>
                        <div class="dtc">'.$velemeny_html.'</div>
                        <div class="dtc">'.substr( str_replace('-', '.', $velemeny['insert_dt']), 0, 16 ).'</div>
                        <div class="dtc">'.$letoltes_html.'</div>
                        <div class="dtc r">'.$torles_html.'</div>
                    </div>
                ');

                $i++;
            }
        }

        $szamla_html .= '</div></div><input type="hidden" id="priRecid" value="'.$_GET['id'].'">';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	//Történet
	}elseif(isset($_GET['tortenet'])){

        $szamla_html .= get_tortenet($megnevezes);

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
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
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	//Megjegyzések
	}elseif(isset($_GET['megjegyzesek'])){

        $szamla_html .= nl2('
            <div class="dt naplo kijCont" data-tabla="megjegyzes" data-mellek="megjegyzesek">
                <div class="dtr nFejlec">
                    <div class="dtc">Megjegyzés</div>
                    <div class="dtc">Hozzáadta</div>
                    <div class="dtc">Hozzáadva</div>
                </div>
        ');

        $sorok = $adatbazis_kapcsolat->s('recid megj insert_usr insert_dt', 'megjegyzes', "`id` = '".$_GET['id']."' AND `tabla` = '".str_replace('.php', '', basename(self()))."'", 'insert_dt desc');

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
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }


	//Cash-flow
	}elseif(isset($_GET['cash_flow'])){

        $szamla_html .= nl2('
            <div class="dt naplo kijCont" data-tabla="cash_flow" data-mellek="cash_flow">
                <div class="dtr nFejlec">
                    <div class="dtc" style="width: 15rem">Dátum</div>
                    <div class="dtc">Összeg</div>
                </div>
        ');

        $sorok = $adatbazis_kapcsolat->s('recid datum osszeg', 'cash_flow', "`apa_recid`='".$_GET['id']."'", 'insert_dt desc');
        if( $sorok ) {
            $i = 1;
            foreach($sorok as $sor) {
                $szamla_html .= nl2('
                    <div id="naploSor-'.$sor['recid'].'" data-id="'.$sor['recid'].'" data-i="'.$i.'" class="dtr nSor kijSor">
                        <div class="dtc" style="width: 15rem">'.str_replace('-', '.', $sor['datum']).'</div>
                        <div class="dtc">'.number_format($sor['osszeg'], 0, '', ' ').' '.get_penznem().'</div>
                    </div>
                ');

                $i++;
            }
        }

        $szamla_html .= '</div>';

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
