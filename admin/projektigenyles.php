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

// Cash-flow fül helyett módosítra lépünk
if(isset($_GET['cash_flow'])){
    $_GET['modosit'] = 1;
    unset($_GET['cash_flow']);
}

// Config
$megnevezes             = pathinfo(self());
$megnevezes             = $megnevezes['filename'];
$akt_tomb               = $projektigenyles_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'projekt igénylést';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

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
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

}

// Véleményező hozzáadás státusz ellenőrzés
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
if( isset($_POST['addVelemenyezo']) ) {

    $login_recid    = $_POST['login_recid'];
    $pr_igeny_recid = $_POST['pr_igeny_recid'];

    // Jogosultságkezelés
    if(!JOG_VELEMENYEZO_HOZZAAD){

        echo json_encode(array(
            'hiba' => 'Önnek nincs joga véleményezőt hozzáadni!'
        ));

        $weblap->leallit();
        exit();
    }

    $stat = $adatbazis_kapcsolat->s2('stat_id', $megnevezes, "`recid`='{$_POST['pr_igeny_recid']}'");
    if($stat['stat_id']!=PRIGENY_STAT_NYITOTT && $stat['stat_id']!=PRIGENY_STAT_VEL_ALATT){
        echo json_encode(array(
            'hiba' => 'Véleményezőt hozzáadni csak nyitott vagy véleményezés alatt státuszokban lehet!'
        ));

        $weblap->leallit();
        exit();
    }

    if (!$login_recid) {
        echo json_encode(array(
            'hiba' => 'Kérem válassza ki a véleményezőt!',
            'gomb' => '<input class="link gomb" value="Ok" onclick="alert2(); $(\'#velemenyezoKeres\').focus();" type="button">'
        ));

        $weblap->leallit();
        exit();
    }

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $pr_igeny_recid ) {

        // Töröljük a korábbi véleményét ha van
        if($_POST['korabbi_velemeny_torlesevel']){
            $akt_velemeny = $adatbazis_kapcsolat->s2('recid utvonal', 'projektigenyles_velemeny', "`projektigenyles_recid`='{$pr_igeny_recid}' AND `login_recid`='{$login_recid}'");
            if($akt_velemeny===false){ $db_error = true; }

            $fajlnev = '../'.get_utvonal().'projektigenyles_velemeny/'.$akt_velemeny['utvonal'];
            if(is_file($fajlnev)){
                unlink($fajlnev);
            }
            $sql = $adatbazis_kapcsolat->d('projektigenyles_velemeny', "`recid`='{$akt_velemeny['recid']}'");
            if($sql===false){ $db_error = true; }
        }

        $felh = $adatbazis_kapcsolat->s2('nev', 'login', "`recid`='{$login_recid}'", '', '1');
        if($felh===false){ $db_error = true; }

        $isKapcs = $adatbazis_kapcsolat->s2('recid', 'projektigenyles_kapcs', "`projektigenyles_recid`='{$pr_igeny_recid}' AND `login_recid`='{$login_recid}'", '', '1');
        if( !$isKapcs || $_POST['korabbi_velemeny_torlesevel'] ) {
            $kapcsID = $adatbazis_kapcsolat -> i('projektigenyles_kapcs',
                                                                            array('projektigenyles_recid',  'login_recid', 'loginnev',                          'insert_dt', 'insert_usr'                            ),
                                                                            array($pr_igeny_recid,          $login_recid,  $_SESSION[get_db_tabla().'user'],    'NOW()',     $_SESSION[get_db_tabla().'teljes_nev']  )
                                                );
//echo $adatbazis_kapcsolat->gp() . '<br>';
            if($kapcsID===false){ $db_error = true; }

            // Ha hozzáadunk egy véleményezőt, akkor a státuszt biztosan léptethetjük véleményezés alattra
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
                'hiba' => 'Az illető már hozzáadásra került, mint véleményező!<br>Vélemény újboli bekérésével törlődik a korábbi véleménye. Folytatja?',
                'gomb' => '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#korabbi_velemeny_torlesevel\').val(1); $(\'#addVelemenyezokForm\').submit();"><input type="button" class="gomb piros" value="Nem" onclick="alert2();">',
            ));
        }
    }

    $weblap->leallit();
    exit();
}

// Megtérülés hozzáadása
if (isset($_POST['addMegterules'])) {

    $terulet = $_POST['terulet'];
    $osszeg  = str_replace(' ', '', $_POST['osszeg']);

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if(!$terulet){
        echo json_encode(array(
            'hiba' => 'Kérjük válasszon területet!',
            'gomb' => '<input class="link gomb" value="Ok" onclick="alert2(); $(\'#erteklista_id_autocomp\').focus();" type="button">'
        ));
        $weblap->leallit();
        exit();
    }

    if(!$osszeg){
        echo json_encode(array(
            'hiba' => 'Kérjük adja meg az összeget!',
            'gomb' => '<input class="link gomb" value="Ok" onclick="alert2(); $(\'#osszeg\').focus();" type="button">'
        ));
        $weblap->leallit();
        exit();
    }

    $kapcsID = $adatbazis_kapcsolat->i('projektigenyles_megter', array('projektigenyles_recid', 'terulet', 'osszeg', 'insert_dt',    'insert_usr',                             'update_usr'                          ),
                                                                 array($_POST['pageID'],        $terulet,  $osszeg,  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'])
                            );
    if($kapcsID===false){ $db_error = true; }

    $sql = $adatbazis_kapcsolat->u('projektigenyles', array('megter'), array(1), "`recid`='{$_POST['pageID']}'");
    if($sql===false){ $db_error = true; }


    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'siker',
            'kapcsID' => $kapcsID
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

// Egy vagy többszállodás select
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

// Új vélemény feltöltésekor ellenőrizzük, hogy van-e már feltöltött vélemény?
if (isset($_POST['van_e_velemeny'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $van_velemeny = $adatbazis_kapcsolat->s2('recid', 'projektigenyles_velemeny', "`projektigenyles_recid`='{$_POST['recid']}' AND `login_recid`='{$_SESSION[get_db_tabla().'login_recid']}'");
    if($van_velemeny===false){ $db_error = true; }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);

        // Ha van már vélemény, akkor kérdezünk
        if($van_velemeny){
            echo json_encode(array(
                'kerdes' => 'Önnek már van feltölött véleménye. Biztosan felülírja?'
            ));
        // Ha nincs vélemény, akkor mehet egyből a feltöltés
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


    // Jóváhagyáskor átmásoljuk a Projektkezelés menüpont alá
    $igenyles = $adatbazis_kapcsolat->s2('*', 'projektigenyles', "`recid`='{$_POST['recid']}'");
    if( $igenyles === false ) { $db_error = true; }

    // Jóváhagyáskor több mindenre figyelni kell
    if($_POST['statusz']==PRIGENY_STAT_JOVAHAGYVA){

        // Van-e jogunk hozzá? Ha nincs, akkor rollback
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

        // A projetk ikt.száma PI prefix helyett csak egy P-t kap
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

        // Státusz táblába is beszúrunk
        $sql = $adatbazis_kapcsolat -> i('projektkezeles_statusz',
                                            array('projektkezeles_recid', 'stat_id',              'insert_dt',    'insert_usr',                             'update_usr'                           ),
                                            array($prk_id,                 PRKEZEL_STAT_NYITOTT,  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'] )
                                        );
        if( $sql === false ) { $db_error = true; }
    }

    // FB elé terjeszteni az alábbi státuszokkal nem lehet
    if( $_POST['statusz']==PRIGENY_STAT_FB_TERJESZT && !in_array($igenyles['stat_id'], prigeny_fb_ele_statuszok()) ){
            echo json_encode(array(
                'hiba' => '!Ebből a státuszból nem lehet FB elé terjeszteni a projektigénylést!'
            ));

            $weblap -> leallit();
            exit;
    }

    // BoD elé csak Elutasítás vagy Jóváhagyás joggal lehet
    if($_POST['statusz']==PRIGENY_STAT_BOD_TERJESZT){
        if(!_jog(JOG_PRIGENY_ELUTASIT) && !_jog(JOG_PRIGENY_JOVAHAGY)){
            echo json_encode(array(
                'hiba' => 'Önnek nincs joga projektigénylést BoD elé terjeszteni!'
            ));

            $weblap -> leallit();
            exit;
        }
    }

    // BoD elé terjeszteni az alábbi státuszokkal nem lehet
    if( $_POST['statusz']==PRIGENY_STAT_BOD_TERJESZT && !in_array($igenyles['stat_id'], prigeny_bod_ele_statuszok()) ){
            echo json_encode(array(
                'hiba' => 'Ebből a státuszból nem lehet BoD elé terjeszteni a projektigénylést!'
            ));

            $weblap -> leallit();
            exit;
    }

    // Státusz tényleges léptetése
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

// Cash-flow hozzáadása
if( isset($_POST['addCashFlow']) ) {

    $pageID     = $_POST['pageID'];
    $datumok    = json_decode($_POST['datumok']);
    $szallodak  = json_decode($_POST['szallodak']);
    $osszegek   = json_decode($_POST['osszegek']);

    if( $pageID ) {

        $osszegek[$i] = trim(str_replace(' ', '',  $osszegek[$i]));
        $datumok[$i]  = trim(str_replace('.', '-', $datumok[$i] ));
        $i = 0;
        foreach($datumok as $datum){
            if($osszegek[$i]>0){
                $sql = $adatbazis_kapcsolat->i('cash_flow', array('apa_recid', 'telephely_id',  'datum',       'osszeg',       'loginnev',                       'update_usr',                           'insert_dt', 'insert_usr'                           ),
                                                            array($pageID,     $szallodak[$i],  $datumok[$i],  $osszegek[$i],  $_SESSION[get_db_tabla().'user'], $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',      $_SESSION[get_db_tabla().'teljes_nev'])
                                                );
                $i++;
            }
        }

        echo json_encode(array(
            'statusz' => 'siker'
        ));
    }

    $weblap->leallit();
    exit();
}

// Hiánypótlás indoklása
if( isset($_POST['addHianypotlas']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $pri = $adatbazis_kapcsolat->s2('stat_id', 'projektigenyles', "`recid`='{$_POST['recid']}'");
    if(in_array($pri['stat_id'], prigeny_fb_bod_var())){
        echo json_encode(array(
            'hiba' => 'Ebből a státuszból nem küldhető hiánypótlásra a projektigénylés!',
        ));
        $weblap->leallit();
        exit();
    }

    if(!trim($_POST['megj'])){
        echo json_encode(array(
            'hiba' => 'Kérjük adja meg a pótlásra váró adatokat!',
            'gomb' => '<input class="link gomb" value="Ok" onclick="alert2(); $(\'#hianypotlas_megj\').focus();" type="button">'
        ));
        $weblap->leallit();
        exit();
    }


    if(addStatusz('projektigenyles_statusz', $_POST['recid'], PRIGENY_STAT_HIANYPOTLAS, '', $_POST['megj'])===false){
        $db_error = true;
    }
    if(addMegj('projektigenyles', $_POST['recid'], $_POST['megj'])===false){
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

// Várakoztatás indoklása
if( isset($_POST['addVarakoztat']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $pri = $adatbazis_kapcsolat->s2('stat_id', 'projektigenyles', "`recid`='{$_POST['recid']}'");
    if(in_array($pri['stat_id'], prigeny_fb_bod_var())){
        echo json_encode(array(
            'hiba' => 'Ebből a státuszból nem küldhető hiánypótlásra a projektigénylés!',
        ));
        $weblap->leallit();
        exit();
    }

    if(!trim($_POST['megj'])){
        echo json_encode(array(
            'hiba' => 'Kérjük indokolja a várakoztatást!',
            'gomb' => '<input class="link gomb" value="Ok" onclick="alert2(); $(\'#varakoztat_megj\').focus();" type="button">'
        ));
        $weblap->leallit();
        exit();
    }

    if(addStatusz('projektigenyles_statusz', $_POST['recid'], PRIGENY_STAT_VARAKOZO, '', $_POST['megj'])===false){
        $db_error = true;
    }
    if(addMegj('projektigenyles', $_POST['recid'], $_POST['megj'])===false){
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


// Igénylések táblázat betöltése
if(isset($_POST['igenylesek_betolt'])){

    $html = '';

    $postfix         = '_fb';
    $fb_bod_statusz  = PRIGENY_STAT_FB_TERJESZT_VAR;
    $nyitott_statusz = ELOT_FB_STAT_NYITOTT;
    $nyitott_is_sql  = ', \''.PRIGENY_STAT_NYITOTT.'\''; //FB csatolásnál a nyitottakat is be lehet csatolni, de BoD-nálm már nem
    if($_POST['mod']=='bod'){
        $postfix         = '_bod';
        $fb_bod_statusz  = PRIGENY_STAT_BOD_TERJESZT_VAR;
        $nyitott_statusz = ELOT_BOD_STAT_NYITOTT;
        $nyitott_is_sql  = '';
    }

    // Ha nem nyitott a státusza, akkor nincs is mit számolni
    $elot = $adatbazis_kapcsolat->s2('stat_id', 'eloterj'.$postfix, "`recid`='{$_POST['eloterj_recid']}'");
    if($elot['stat_id']!=$nyitott_statusz){

        echo '<h1>Csak nyitott státuszú előterjesztéshez csatolható projektigénylés!</h1><script>$(\'#eloterj_igneyles_csatolForm div.kuldes\').html(\'<input type="button" class="gomb zold" value="Ok" onclick="popupZar();">\'); $(\'#eloterj_igneyles_csatolCim\').html(\'Hiba\');</script>';

        $weblap->leallit();
        exit();
    }

    // Rendezés beállítása
    $join  = ' JOIN `'.get_db_tabla().'telephely` ON `'.get_db_tabla().'projektigenyles`.`telephely_id`=`'.get_db_tabla().'telephely`.`id`';
    switch($_POST['order']){
        case 'bkolt': $order = '`'.get_db_tabla().'projektigenyles`.`bkolt`, `'.get_db_tabla().'telephely`.`nev`';          $bkolt_selected = ' selected';  break;
        case 'tipus': $order = '`'.get_db_tabla().'projektigenyles`.`tipus`, `'.get_db_tabla().'projektigenyles`.`bkolt`';  $tipus_selected = ' selected';  break;
        default     : $order = '`'.get_db_tabla().'telephely`.`nev`,         `'.get_db_tabla().'projektigenyles`.`bkolt`';                                  break;
    }

    // nem szerepelnek másik nyitott vagy véglegesítve státuszú előterjesztésben
    $where_kieg = '';
    $fb_kizart_recidk = $adatbazis_kapcsolat->s('projektigenyles_recid', 'eloterj_fb_kapcs', "`eloterj_recid` IN (SELECT `recid` FROM `".get_db_tabla()."eloterj_fb` WHERE `stat_id` IN ('".ELOT_FB_STAT_NYITOTT."', '".ELOT_FB_STAT_VEGLEGESITVE."'))");
    $fb_kizart_recidk = adatbazis2tomb($fb_kizart_recidk, 'projektigenyles_recid');
    $bod_kizart_recidk = $adatbazis_kapcsolat->s('projektigenyles_recid', 'eloterj_bod_kapcs', "`eloterj_recid` IN (SELECT `recid` FROM `".get_db_tabla()."eloterj_bod` WHERE `stat_id` IN ('".ELOT_BOD_STAT_NYITOTT."', '".ELOT_BOD_STAT_VEGLEGESITVE."'))");
    $bod_kizart_recidk = adatbazis2tomb($bod_kizart_recidk, 'projektigenyles_recid');
    $kizart_recidk = array_merge($fb_kizart_recidk, $bod_kizart_recidk);
    if($kizart_recidk){
        $where_kieg .= ' AND `'.get_db_tabla().'projektigenyles`.`recid` NOT IN ('.implode(',', $kizart_recidk).')';
    }

    $szallodak = get_akt_login_szallodai();
    $priek = $adatbazis_kapcsolat->s('
                                        `'.get_db_tabla().'projektigenyles`.`recid`,
                                        `'.get_db_tabla().'projektigenyles`.`nev`,
                                        `'.get_db_tabla().'projektigenyles`.`tipus`,
                                        `'.get_db_tabla().'projektigenyles`.`stat_id`,
                                        `'.get_db_tabla().'projektigenyles`.`telephely_id`,
                                        `'.get_db_tabla().'projektigenyles`.`bkolt`,
                                        `'.get_db_tabla().'telephely`.`nev` as `t_nev`',
                                        'projektigenyles',
                                        "`stat_id` IN ('".$fb_bod_statusz."'".$nyitott_is_sql.") AND `telephely_id` IN (".implode(',', $szallodak).")$where_kieg", $order, '', '', '', $join);

    $i = 1;
    foreach($priek as $pri){

        // Szálloda
        $szalloda = $adatbazis_kapcsolat->s2('nev', 'telephely', "`id`='{$pri['telephely_id']}'");

        // Létrehozva dátum = első nyitott státusz
        $letrehozva = $adatbazis_kapcsolat->s2('insert_dt', 'projektigenyles_statusz', "`projektigenyles_recid`='{$pri['recid']}' AND `stat_id`='".PRIGENY_STAT_NYITOTT."'", 'insert_dt', '1');

        // Tipus
        require_once 'selectek.php';
        foreach($select_pri_tipus as $tip){
            if($tip['value']==$pri['tipus']){
                $tipus = $tip['name'];
            }
        }
        if(!$pri['tipus']){
            $tipus = '-';
        }

        //BoD elé terjesztettek
        $kiemelt = '';
        if($pri['stat_id']==$fb_bod_statusz){
            $kiemelt = ' kiemelt';
        }

        $html .= '
            <tr class="kijSor'.$kiemelt.'" data-i="'.$i.'" data-recid="'.$pri['recid'].'">
                <td>'.getStatText('projektigenyles', $pri['stat_id']).'</td>
                <td>'.$szalloda['nev'].'</td>
                <td>'.$pri['nev'].'</td>
                <td>'.$tipus.'</td>
                <td class="r">'.number_format($pri['bkolt'], 0, '', ' ').' '.get_penznem().'</td>
                <td>'.substr(str_replace('-', '.', $letrehozva['insert_dt']), 0, 10).'</td>
            </tr>
            ';
        $i++;
    }
    if($html){

        // Táblázat rendezhető: szálloda + becsült költség, becsült költség + szálloda, típus + becsült költség. Ezek legyenek egy lenyílóból kiválaszthatóak.
        $html = '
            Rendezés:
            <select onchange="igenylesek_betolt(this.value)">
                <option>szálloda + becsült költség</option>
                <option value="bkolt"'.$bkolt_selected.'>becsült költség + szálloda</option>
                <option value="tipus"'.$tipus_selected.'>típus + becsült költség</option>
            </select>
            <table class="csikos kijCont" id="prigenylesek" style="cursor: default">
            <thead>
            <tr>
                <th>
                    Státusz
                    <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prigenylesek tbody\'), this.value, 1)">
                </th>
                <th>
                    Szálloda
                    <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prigenylesek tbody\'), this.value, 2)">
                </th>
                <th>
                    Név
                    <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prigenylesek tbody\'), this.value, 3)">
                </th>
                <th>
                    Típus
                    <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prigenylesek tbody\'), this.value, 4)">
                </th>
                <th class="r">
                    Becsült költség
                    <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prigenylesek tbody\'), this.value, 5)">
                </th>
                <th>
                    Létrehozás dátuma
                    <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prigenylesek tbody\'), this.value, 6)">
                </th>
            </tr>
            </thead>
            <tbody>
                '.$html.'
            </tbody>
            </table>';
    }else{
        $html = '<h1>Nem található csatolható projektigénylés!</h1><script>$(\'#eloterj_igneyles_csatolForm div.kuldes\').html(\'<input type="button" class="gomb zold" value="Ok" onclick="popupZar();">\');  $(\'#eloterj_igneyles_csatolCim\').html(\'Hiba\');</script>';
    }

    echo nl2($html);

    $weblap->leallit();
    exit();
}


// Igénylések csatolása
if (isset($_POST['eloterj_igneyles_csatol'])) {

    $prigeny_recidk = json_decode($_POST['prigeny_recidk'], 'tömbnek');

    //Előbb csak
    if(!$_POST['mehet']){
        echo json_encode(array(
            'hiba' => count($prigeny_recidk).' db projekt igénylést választott.<br>Biztos, hogy az előterjesztéshez csatolja ',
            'gomb' => '<input class="gomb zold" value="Igen" onclick="eloterj_igneyles_csatol(\'mehet\');" type="button"> <input class="gomb piros" value="Nem" onclick="alert2();" type="button">'
        ));
        $weblap->leallit();
        exit();
    }


    // Státusz ellenőrzés
    $nyitott_statusz = ELOT_FB_STAT_NYITOTT;
    $postfix         = '_fb';
    if($_POST['mod']=='bod'){
        $nyitott_statusz = ELOT_BOD_STAT_NYITOTT;
        $postfix         = '_bod';
    }
    $eloterj = $adatbazis_kapcsolat->s2('stat_id', 'eloterj'.$postfix, "`recid`='{$_POST['eloterj_recid']}'");
    if($eloterj['stat_id']!=$nyitott_statusz){
        echo json_encode(array(
            'hiba' => 'Projektigénylést csatolni csak nyitott státuszban lehet!',
        ));
        $weblap->leallit();
        exit();
    }

    // Csatolás
    $oszlopok = array('recid', 'csoport_id', 'ikt_szam', 'egy_tobb', 'telephely_id', 'stat_id', 'nev', 'tipus', 'prior', 'besorol', 'rendk', 'megter', 'text', 'bkolt', 'loginnev', 'update_dt', 'update_usr', 'insert_dt', 'insert_usr', 'torolve');
    foreach($prigeny_recidk as $recid){
        $prigeny = $adatbazis_kapcsolat->s2('*', $megnevezes, "`recid`='$recid'");
        $ertekek  = array();
        foreach($oszlopok as $kulcs => $oszlop){
            array_push($ertekek, $prigeny[$oszlop]);
        }
        $adatbazis_kapcsolat->i($megnevezes.'_eloterj', $oszlopok, $ertekek);

        $adatbazis_kapcsolat->i('eloterj'.$postfix.'_kapcs', array('eloterj_recid',          'projektigenyles_recid', 'insert_dt',    'insert_usr'                             ),
                                                             array($_POST['eloterj_recid'],  $recid,                  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']   )
                                );
    }

    $weblap->leallit();
    exit();
}

//Projektigénylések előterjesztés másolat tábla ideiglenes státusz mentése
if (isset($_POST['pri_eloterj_statusz_mod'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $stat_id_eloterjek  = json_decode($_POST['stat_id_eloterjek'],  'tömbnek');
    $pri_eloterj_recidk = json_decode($_POST['pri_eloterj_recidk'], 'tömbnek');

    $i = 0;
    foreach($pri_eloterj_recidk as $recid){
        $sql = $adatbazis_kapcsolat->u('projektigenyles_eloterj', array('stat_id_eloterj'), array($stat_id_eloterjek[$i]), "`recid`='{$recid}'");
        if( $sql === false ) { $db_error = true; }


        //Rögzítéskor az eredeti projektigénylés táblában is státuszt léptetünk, illetve a projektigenyles_eloterj tábla stat_id-jét is módosítjuk
        if($_POST['rogzit']){
            $sql = $adatbazis_kapcsolat->u('projektigenyles_eloterj', array('stat_id'), array($stat_id_eloterjek[$i]), "`recid`='{$recid}'");
            if( $sql === false ) { $db_error = true; }

            //Igénylések státuszsorának leírás oszlopában megjelenik előterjesztés azonosító és FB ülés dátuma.
            $eloterj = $adatbazis_kapcsolat->s2('ikt_szam ules_datum', $_POST['tabla'], "`recid`='{$_POST['eloterj_recid']}'");
            $memo = $eloterj['ikt_szam'].' '.  str_replace('-', '.', $eloterj['ules_datum']);

            //Projektigénylés státusz léptetés
            if(addStatusz('projektigenyles_statusz', $recid, $stat_id_eloterjek[$i], '', $memo)===false){
                $db_error = true;
            }

            //Előterjesztés státusz léptetés
            $dontes_rogzitve_stat = ELOT_FB_STAT_DONT_ROG;
            if($_POST['tabla']=='eloterj_bod'){
                $dontes_rogzitve_stat = ELOT_BOD_STAT_DONT_ROG;
            }
            if(addStatusz($_POST['tabla'].'_statusz', $_POST['eloterj_recid'], $dontes_rogzitve_stat)===false){
                $db_error = true;
            }
        }

        $i++;
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


// Cashflow hozzáadása előtt ellenőrizzük, hogy jog és státusz engedi-e
if (isset($_POST['cash_flow_ellenoriz'])) {

    if(!_jog(JOG_PRIGENY_S_KEZEL) && !_jog(JOG_PRIGENY_SZ_KEZEL) && !_jog(JOG_PRIGENY_B_KEZEL) && !_jog(JOG_PRIGENY_F_KEZEL) && !_jog(JOG_PRIGENY_T_KEZEL) && !_jog(JOG_PRIGENY_EGY_KEZEL) && !_jog(JOG_PRIGENY_TOBB_KEZEL)){
        echo json_encode(array(
            'hiba' => 'Önnek nincs joga Cash-flowt feltölteni!'
        ));
        $weblap->leallit();
        exit();
    }

    $statusz = $adatbazis_kapcsolat->s2('stat_id', $megnevezes, "`recid`='{$_POST['recid']}'");
    if($statusz['stat_id']!=PRIGENY_STAT_LETRE_ALATT){
        echo json_encode(array(
            'hiba' => 'Csak létrehozás alatt vihető fel új cash-flow bejegyzés!'
        ));
        $weblap->leallit();
        exit();
    }

    echo json_encode(array(
        'ok' => '1' //Valami kimenet kell legyen.
    ));

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
                `'.get_db_tabla().'projektigenyles`.`rendk` as \'Rendkívüli\',
                `'.get_db_tabla().'projektigenyles`.`megter` as \'Megtérülő\',
                `'.get_db_tabla().'projektigenyles`.`bkolt` as \'Becsült költsége\',
                `'.get_db_tabla().'projektigenyles`.`stat_id` as \'Státusz\',
                `'.get_db_tabla().'projektigenyles`.`tipus` as \'Típus\',
                `'.get_db_tabla().'projektigenyles`.`prior` as \'Prioritás\',
                `'.get_db_tabla().'projektigenyles`.`telephely_id` as \'Szálloda\'
                '.$lekerdezes_fromtol;

        $query      = mysqli_query($db_kapcs, $sql);

        $csvhead    = array();
        while($fejlec_nevek = mysqli_fetch_field($query)){
            $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
        }
        fputcsv($open, $csvhead, ';');

        require_once 'selectek.php';
        while( $sorok = mysqli_fetch_assoc($query) ){
            $csvcont = array();
            foreach( $sorok as $kulcs => $sor ) {
                if($kulcs=='Szálloda'){
                    $sor = getErtek('nev', 'telephely', 'id', $sor);
                }
                if($kulcs=='Bejelentett hiba'){
                    $sor = erteklista(4, $sor);
                }
                if($kulcs=='Helyiség'){
                    $sor = getErtek('nev', 'obj', 'recid', $sor);
                }
                if($kulcs=='Gépnév'){
                    $sor = getErtek('nev', 'eszkozkezeles', 'recid', $sor);
                }
                if($kulcs=='Prioritás'){
                    $sor = erteklista(36, $sor);
                }
                if($kulcs=='Típus'){
                    if($sor){
                        foreach($select_pri_tipus as $tipus){
                            if($tipus['value']==$sor){
                                $sor = $tipus['name'];
                                break;
                            }
                        }
                    }
                }
                if($kulcs=='Státusz'){
                    $sor = getStatText($megnevezes, $sor);
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
require('selectek.php');

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

    if( isset($_GET['modosit']) || isset($_GET['tortenet']) || isset($_GET['velemenyezok']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) || isset($_GET['megterules']) || isset($_GET['cash_flow']) ) {
        /* Számla fülek */
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'tortenet'      => 'Történet',
            'velemenyezok'  => 'Véleményezők',
            'cash_flow'     => 'Cash-flow',
            'dokumentumok'  => 'Dokumentumok'.$dok_db,
            'megjegyzesek'  => 'Megjegyzések'.$megj_db,
            'megterules'    => 'Megtérülés',
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

    // A prigeny_tipus_beallit() függvényt meghívjuk betöltéskor
    $szamla_html .= nl2('
                            <script>
                                $(document).ready(function () {

                                    prigeny_tipus_beallit();
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

        //Újat csak kezelés joggal lehet
        if(_jog(JOG_PRIGENY_B_KEZEL) || _jog(JOG_PRIGENY_EGY_KEZEL) || _jog(JOG_PRIGENY_F_KEZEL) || _jog(JOG_PRIGENY_SZ_KEZEL) || _jog(JOG_PRIGENY_S_KEZEL) || _jog(JOG_PRIGENY_TOBB_KEZEL) || _jog(JOG_PRIGENY_T_KEZEL) ){
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="$(\'#szerk_form\').submit()">
                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="formModositva=false; history.back();">
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
        }

    //Módosítás gombok
    } else {

        $sor = $adatbazis_kapcsolat -> s2('stat_id tipus', $megnevezes, "`recid`='{$_GET['id']}'");

        //Ha csak sajátot kezelhet, és adott rekord nem általa van feltöltve, akkor nem szrkesztheti törlheti.
        //Ez egy hosszú feltétel, amikre többször is szükség van, ezért változóba kimentve
        $csak_sajat_kezelheti = true;
        if( _jog(JOG_PRIGENY_S_KEZEL) && !_jog(JOG_PRIGENY_B_KEZEL) && !_jog(JOG_PRIGENY_EGY_KEZEL) && !_jog(JOG_PRIGENY_F_KEZEL) && !_jog(JOG_PRIGENY_SZ_KEZEL) && !_jog(JOG_PRIGENY_TOBB_KEZEL) && !_jog(JOG_PRIGENY_T_KEZEL) && $sor['loginnev']!=$_SESSION[get_db_tabla().'user'] ) {
            $csak_sajat_kezelheti = false;
        }

        //Ha a státusz elutasítva vagy jóváhagyva, akkor semmilyen gomb nem érhető el
        if($sor['stat_id'] == PRIGENY_STAT_ELUTASITVA || $sor['stat_id'] == PRIGENY_STAT_JOVAHAGYVA ){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Projektigénylés ebben a státuszban már nem törölhető!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        }
        if($sor['stat_id'] != PRIGENY_STAT_ELUTASITVA && $sor['stat_id'] != PRIGENY_STAT_JOVAHAGYVA){
            //Js, a törlés gombhoz
            $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';

            //Csak akkor törölhető, amikor szerkeszthető
            if( in_array($sor['stat_id'], prigeny_mentheto_statuszok()) ){
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Projektigénylés ebben a státuszban nem törölhető!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
            //Jogosutságkezelés: csak az törölheti, akinek van hozzá joga
            } elseif( !_jog(JOG_PRIGENY_B_KEZEL) && !_jog(JOG_PRIGENY_EGY_KEZEL) && !_jog(JOG_PRIGENY_F_KEZEL) && !_jog(JOG_PRIGENY_SZ_KEZEL) && !_jog(JOG_PRIGENY_S_KEZEL) && !_jog(JOG_PRIGENY_TOBB_KEZEL) && !_jog(JOG_PRIGENY_T_KEZEL) ){
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Önnek nincs joga projektigénylést törölni!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
            // Ha csak saját igénylések kezelése joga van és más kezelés nincs, és az igénylést nem saját maga hozta létre
            }elseif( !$csak_sajat_kezelheti ) {
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Ön csak saját projektigénylést törölhet!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
            } else {
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
            }

            //Ha a prigény létrehozás alatt van, akkor érhető el csak az Igénylés rögzítése gomb
            if($sor['stat_id']==PRIGENY_STAT_LETRE_ALATT){
                if (isset($_GET['modosit'])) {
                    $gombok .= ' <input type="button" class="gomb foGomb" value="Igénylés rögzítése" title="Igénylés rögzítése" id="igenyles_rogzit_gomb" onclick="$(\'#igeny_rogzit\').val(1); $(\'#szerk_form\').submit();">';
                }else{
                    $gombok .= ' <input type="button" class="gomb foGomb" value="Igénylés rögzítése" title="Igénylés rögzítése" id="igenyles_rogzit_gomb" onclick="location.href=\''.self().'?id='.$_GET['id'].'&amp;modosit&amp;rogzit\'">';
                }
            }
            //Rejtett, technikai input, viszont ez legyen mindig elérhető (pl. nyitott státuszkor mentésné is kell)
            $gombok .= ' <input type="hidden" id="igeny_rogzit">';

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

                //Létrehozás alatt semmilyen más gomb nem látszik, csak a Mentés vagy Igénylés rögzítése
                if($sor['stat_id']!=PRIGENY_STAT_LETRE_ALATT){
                    if(!in_array($sor['stat_id'], prigeny_fb_bod_var()) && $sor['stat_id'] != PRIGENY_STAT_VARAKOZO && $sor['stat_id'] != PRIGENY_STAT_HIANYPOTLAS) {
                        $gombok .= ' <input type="button" class="gomb foGomb" value="Várakoztatás" title="Várakoztatás" onclick="csoportMuvelet(\'pri_varakoztat\');">';
                    }
                    if($sor['stat_id'] == PRIGENY_STAT_VARAKOZO) {
                        $gombok .= ' <input type="button" class="gomb foGomb" value="Várakoztatás vége" title="Várakoztatás vége" onclick="pri_statusz_leptet(\''.$_GET['id'].'\', \''.PRIGENY_STAT_NYITOTT.'\');">';
                    }
                    if(!in_array($sor['stat_id'], prigeny_fb_bod_var()) && in_array($sor['stat_id'], prigeny_hianypotlasba_statuszok()) ) {
                        $gombok .= ' <input type="button" class="gomb foGomb" value="Visszaadás hiánypótlásra" title="Visszaadás hiánypótlásra" onclick="csoportMuvelet(\'pri_hianypotlasra\');">';
                    }

                    //$gombok .= ' <input type="button" class="gomb foGomb" value="FB előterjesztés nyomtatása" title="FB előterjesztés nyomtatása" onclick="window.open(\'projektigenyles.php?print=lista&amp;fb_elo\');">';

                    //Vannak gombok amik itt a helyük, de a fő feltételen kívül dől el, hogy látszik-e (nem csak az a feltétle, hogy van-e minden kezelés jog)
                    $gombok .= $gombok_meg;

                    if(!in_array($sor['stat_id'], prigeny_fb_bod_var()) && _jog(JOG_PRIGENY_ELUTASIT) && $sor['stat_id'] != PRIGENY_STAT_ELUTASITVA /*Ez a felétel felesleges, csak az egységesség miatt van itt*/) {
                        $gombok .= ' <input type="button" class="gomb piros foGomb" value="Elutasítás" title="Elutasítás" onclick="csoportMuvelet(\'elutasitas_csak_memo\');">';
                    }

                    if(!in_array($sor['stat_id'], prigeny_fb_bod_var()) && _jog(JOG_PRIGENY_JOVAHAGY) && in_array($sor['stat_id'], prigeny_jovahagyhato_statuszok()) ) {
                        $gombok .= ' <input type="button" class="gomb zold foGomb" value="Jóváhagyás" title="Jóváhagyás" onclick="pri_statusz_leptet(\''.$_GET['id'].'\', \''.PRIGENY_STAT_JOVAHAGYVA.'\');">';
                    }
                }

                //Ha kezelheti, akkor van mentés gomb is
                if( in_array($sor['stat_id'], prigeny_mentheto_statuszok()) && $csak_sajat_kezelheti){

                    //Nyitott státusz után már midig úgy mentünk, mintha lezárnánk (cél, hogy a cash-flow ellenőrzés meglegyen)
                    $gomb_kieg = '';
                    if($sor['stat_id']==PRIGENY_STAT_NYITOTT){
                        $gomb_kieg = '$(\'#igeny_rogzit\').val(1); ';
                    }
                    $gombok = nl2('
                        <div class="form_kuldes">
                            <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="'.$gomb_kieg.'$(\'#szerk_form\').submit()">
                            <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="formModositva=false; location.reload();">
                        </div>
                    ').$gombok;
                }
            }

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

    $js_submit_elott  = '';

    //Ha vannak cash-flow-jaim és többszállodás a modell, akkor mentés előtt azt is vizsgáljuk, hogy nem váltottunk-e egyszállodásra, vagy szállodát
    $rogzites_figyeles_kieg     = '';
    $egyszallodasok_leszunk_ell = '';
    if( isset($_GET['modosit']) ) {
        if($sor['egy_tobb']==1){
            $egyszallodasok_leszunk_ell .= '
                                if(!volt_figyelmeztetes){
                                    if($(\'#cash_flow_div div.kijSor\').length && $(\'#telephely_id\').val()!=\''.$sor['telephely_id'].'\'){
                                        alert2(\'Ha módosítja a vállalatot vagy egyszállodás modellre vált, a cash-flowban rögzített adatok törlődni fognak!<br>Biztosan folytatja a műveletet?\', \'<input type="button" class="gomb zold" onclick="volt_figyelmeztetes = true; $(\\\'#szerk_form\\\').submit();" value="Igen"> <input type="button" class="gomb piros" onclick="alert2()" value="Nem">\');
                                        return false;
                                    }
                                }else{
                                    volt_figyelmeztets = false;
                                }
                                ';
            //Ha többszállodás, akkor a rögzítésnél még azt is figyeljük, hogy valóban több szálloda is tartozik-e hozzá.
            $rogzites_figyeles_kieg .= '
                                kulonbozo_telephelyek = false;
                                if( telephely_nev = $(\'#cash_flow_div div.kijSor div.szalloda\').length ){
                                    elozo_telephely = $(\'#cash_flow_div div.kijSor input[name="telephely_id"]\').first().val();
                                    telephely_nev = $(\'#cash_flow_div div.kijSor div.szalloda\').first().html();
                                    $(\'#cash_flow_div div.kijSor\').each(function() {
                                        telephely_tmp = $(this).find(\'input[name="telephely_id"]\').val();
                                        if(elozo_telephely!=telephely_tmp){
                                            kulonbozo_telephelyek = true;
                                        }
                                    });
                                /* Ha egy telephely sincs rögzítve, akkor is tovább mehetünk. */
                                }else{
                                    kulonbozo_telephelyek = true;
                                }

                                if(!kulonbozo_telephelyek && !kulonbozo_telephely_mehet){
                                    alert2(\'Cashflow csak \'+telephely_nev+\' szállodára készült.<br>Ha folytatja, a projekt igénylés egy szállodásra változik!<br>Folytatja?\', \'<input type="button" class="gomb zold" value="Igen" onclick="kulonbozo_telephely_mehet = true; $(\\\'#igeny_rogzit\\\').val(1); $(\\\'#szerk_form\\\').submit();"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">\');
                                    return false;
                                }
                                ';
        }

    }

    $js_submit_elott .= '
                        /* Cash flow menüpont alatti mentés gombot levesszük */
                        $(\'#cash_flow_mentese\').remove();

                        /* Első lépésként az ideiglenes Cash-flow sorok eltároljuk az adatbázisba */
                        szallodak = [];
                        datumok   = [];
                        osszegek  = [];
                        $(\'#cash_flow_div div.kijSor\').each(function() {

                            if( $(this).attr(\'data-id\')==\'UJ\' ){
                                akt_osszeg = Number($(this).find(\'div.osszeg\').html().replace(\' '. get_penznem().'\', \'\').replace(/ /g, \'\'));
                                osszegek.push(akt_osszeg);

                                akt_telephely_id = $(this).find(\'input[name="telephely_id"]\').val();
                                akt_datum        = $(this).find(\'div.datum\').html();
                                szallodak.push(akt_telephely_id);
                                datumok  .push(akt_datum       );
                            }

                        });

                        var ajax = $.ajax({
                            url : \''.self().'\',
                            type: \'POST\',
                            data: {
                                pageID       : \''.$_GET['id'].'\',
                                szallodak    : JSON.stringify(szallodak),
                                datumok      : JSON.stringify(datumok),
                                osszegek     : JSON.stringify(osszegek),
                                addCashFlow  : new Date().getTime()
                            },
                            success: function(response) {
                                $(\'#cash_flow_div div.kijSor\').each(function() {
                                    $(this).attr(\'data-id\', \'UJ-FELDOLGOZVA\');
                                });
                            }
                        });

                        /* Igénylés rögzítése ugyanaz mint a Mentés gomb, csak itt vizsgáljuk azt is, hogy a becsült költség azonos-e a szétosztottal, illeve még jópár egyéb feltétlet. */
                        igeny_rogzites_uzemmod = false; /* form_ell_admin-hoz kell, átadjuk parméterként ezt az infót is: rögzítéskor státusz léptetés stb... */
                        if($(\'#igeny_rogzit\').val()>0){

                            igeny_rogzites_uzemmod = true;

                            /* Újból egy mentés gombra kattintva már nem akarunk igényt rögzíteni, ezt az ágat csak az igény rögzítése gombnyomásakor figyeljük */
                            $(\'#igeny_rogzit\').val(0);
                            /* Kötelező mezők ki vannak-e töltve */
                            if( !$(\'#nev\').val().trim() ){
                                alert2(\'Rögzítéshez töltse ki az igénylés nevét!\', \'<input type="button" class="gomb" value="Ok" onclick="alert2(); $(\\\'#nev\\\').focus();">\', \'Hiba\');
                                return false;
                            }

                            '.$rogzites_figyeles_kieg.'
                            '.$egyszallodasok_leszunk_ell.'

                            osszeg = 0;
                            $(\'#cash_flow_div div.kijSor\').each(function() {
                                akt_osszeg = Number($(this).find(\'div.osszeg\').html().replace(\' '. get_penznem().'\', \'\').replace(/ /g, \'\'));
                                osszeg    += akt_osszeg;
                            });

                            bkolt = $(\'#bkolt\').val().replace(/ /g, \'\');
                            if(bkolt!=osszeg){
                                alert2(\'A becsült költség nem egyenlő a szétosztott költségekkel!\', \'\', \'Hiba\');
                                return false;
                            }

                            if( $(\'#megter\').prop(\'checked\')==false ){
                                /* FONTOS, hogy itt továbbengedjük menteni, csak nem rögzítjhük, de a cashflow fület és adatlapot elmentjük, hogy a megtérólés fülre tudjon lapozni */
                                igeny_rogzites_uzemmod = false;
                                alert_vegen            = \'Az igénylés nem megtérülő, így nem rögzíthető!\'; /* Ajax lefutás után megjelenő hibaüzenet. */
                            }

                        }

                        return form_ell_admin();
                        ';
    $js_submit_elott = nl2($js_submit_elott);

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

	    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");

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
            $json_sor[$kulcs] = nl2(str_replace(array("\r\n", "'"), array("#UJSOR#", "#APOSZTROF#"), $elem));
        }
        $json_sor['mertek'] = number_format($json_sor['mertek'], 0, '', ' ');
        $json_sor['bkolt']  = number_format($json_sor['bkolt'],  0, '', ' ');
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

        //Ha van get rögzít, akkor a rögzítés gombra kattintunk.
        $js_kieg = '';
        if(isset($_GET['rogzit'])){
            $js_kieg = '$(\'#igenyles_rogzit_gomb\').click();';
        }

        $tovabbi_tartalom = '';
        $tovabbi_tartalom .= nl2('
            <script>
                volt_figyelmeztetes       = false;
                kulonbozo_telephely_mehet = false;
                $(document).ready(function() {
                    becsult_szetosztott_ujraszamol();
                    '.$js_kieg.'
                });

                function becsult_szetosztott_ujraszamol(){
                    szetosztott = 0;
                    $(\'#cash_flow_div div.kijSor\').each(function() {
                        akt_osszeg = Number($(this).find(\'div.osszeg\').html().replace(\' '. get_penznem().'\', \'\').replace(/ /g, \'\'));
                        szetosztott += akt_osszeg;
                    });
                    $(\'#szetoszt_kolt\').html( ezresek(szetosztott                                          , 0, \'\', \' \')+\' '.get_penznem().'\');
                    $(\'#becs_kolt\')    .html( ezresek($(\'#bkolt\').val().replace(/ /g, \'\')              , 0, \'\', \' \')+\' '.get_penznem().'\');
                    $(\'#kulonbseg\')    .html( ezresek($(\'#bkolt\').val().replace(/ /g, \'\') - szetosztott, 0, \'\', \' \')+\' '.get_penznem().'\');
                    if( $(\'#becs_kolt\').html() != $(\'#szetoszt_kolt\').html() ){
                        $(\'#kulonbseg\').addClass(\'piros\');
                    }else{
                        $(\'#kulonbseg\').removeClass(\'piros\');
                    }
                }
            </script>
            <div id="cash_flow_div" class="dn">

                <div class="dt full">
                    <div class="dtc">

                        <div class="dt naplo kijCont" data-tabla="cash_flow" data-mellek="cash_flow">
                            <div class="dtr nFejlec">
                                <div class="dtc" style="width: 30rem">Szálloda</div>
                                <div class="dtc" style="width: 15rem">Dátum</div>
                                <div class="dtc">Összeg</div>
                            </div>
        ');

        $cash_flowk = $adatbazis_kapcsolat->s('recid telephely_id datum osszeg', 'cash_flow', "`apa_recid`='".$_GET['id']."'", 'insert_dt desc');
        $i = 1;
        foreach($cash_flowk as $cf) {

            if($sor['egy_tobb']==0){
                $szalloda = $adatbazis_kapcsolat->s2('nev', 'telephely', "`id`='{$sor['telephely_id']}'");
            }else{
                $szalloda = $adatbazis_kapcsolat->s2('nev', 'telephely', "`id`='{$cf['telephely_id']}'");
            }

            $tovabbi_tartalom .= nl2('
                <div id="naploSor-'.$sor['recid'].'" data-id="'.$cf['recid'].'" data-i="'.$i.'" class="dtr nSor kijSor">
                    <div class="dtc szalloda" style="width: 15rem">
                        '.$szalloda['nev'].'
                        <input type="hidden" name="telephely_id" value="'.$cf['telephely_id'].'">
                    </div>
                    <div class="dtc datum" style="width: 15rem">'.str_replace('-', '.', $cf['datum']).'</div>
                    <div class="dtc osszeg">'.number_format($cf['osszeg'], 0, '', ' ').' '.get_penznem().'</div>
                </div>
            ');

            $i++;
        }

        $tovabbi_tartalom .= nl2('
                        </div>
                    </div>
                    <div class="dtc" style="padding-left: 1rem">

                        <div class="dt">
                            <div class="dtr">
                                <div class="dtc">
                                    Becsült költség:
                                </div>
                                <div class="dtc r">
                                    <b id="becs_kolt">'.number_format($sor['bkolt'], 0, '', ' ').' '.get_penznem().'</b>
                                </div>
                            </div>
                            <div class="dtr">
                                <div class="dtc">
                                    Szétosztott költség:
                                </div>
                                <div class="dtc r">
                                    <b id="szetoszt_kolt"></b>
                                </div>
                            </div>
                            <div class="dtr">
                                <div class="dtc">
                                    Különbség:
                                </div>
                                <div class="dtc r">
                                    <b id="kulonbseg"></b>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            ');

	    $szamla_html .= str_replace(
            array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',           '{CK_EDITOR}', '{GOMBOK}'    ),
            array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $tovabbi_tartalom, $ckeditor,     $gombok       ),
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

    //Véleményezők fül
    }elseif(isset($_GET['velemenyezok'])) {

        /* Fejlécek */
        $szamla_html .= '<div id="tablazatBeszur">';
        $szamla_html .= nl2('
            <div class="dt naplo szakter kijCont" data-tabla="projektigenyles_kapcs" data-mellek="velemenyezok">
                <div class="dtr nFejlec">
                    <div class="dtc">Név</div>
                    <div class="dtc">Vélemény</div>
                    <div class="dtc">Megjegyzés</div>
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
                $velemeny = $adatbazis_kapcsolat->s2('recid utvonal fajlnev megj insert_dt', 'projektigenyles_velemeny', "`projektigenyles_recid`='{$_GET['id']}' AND `login_recid`='{$velemenyezo['recid']}'");
//echo $adatbazis_kapcsolat->gp() . '<br>';
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
                        <div class="dtc">'.nl2br2($velemeny['megj']).'</div>
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

	//Megtérülés
	}elseif(isset($_GET['megterules'])){

        $szamla_html .= nl2('
            <div class="dt naplo kijCont" data-tabla="projektigenyles_megter" data-mellek="megterules">
                <div class="dtr nFejlec">
                    <div class="dtc">Terület</div>
                    <div class="dtc">Összeg</div>
                </div>
        ');

        $sorok = $adatbazis_kapcsolat->s('recid terulet osszeg insert_usr insert_dt', 'projektigenyles_megter', "`projektigenyles_recid` = '".$_GET['id']."'", 'insert_dt desc');
        $i = 1;

        foreach($sorok as $sor) {

            $szamla_html .= nl2('
                <div id="naploSor-'.$sor['recid'].'" data-id="'.$sor['recid'].'" data-i="'.$i.'" class="dtr nSor kijSor">
                    <div class="dtc" style="width: 30rem">'.erteklista(37, $sor['terulet']).'</div>
                    <div class="dtc">'.number_format($sor['osszeg'], 0, '', ' ').' '.get_penznem().'/év</div>
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

    }

    if( !$_GET['ajax'] ) {
        echo '</div>';
    }


if( !$_GET['ajax'] ) {
    require_once('footer.php');
}
