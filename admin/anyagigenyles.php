<?php
require_once('../weblap.php');

if( !_jog(JOG_ANYAIGENLES_MEGTEKINT) ) {
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
$akt_tomb               = $anyagigenyles_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'anyagigénylést';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

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
$formazatlan_szam       = array('nev', 'csz', 'tel', 'fax', 'mobil');   //Azok az oszlopneveket sorold fel, melyek numerikus értéket is tartalmazhatnak, de nem szeretnéd, ha lista nézetben ezres csoportra bontaná

$szamla_html = '';

// Az $id-k tömböt és a $page változót innen kaptjuk
require_once 'anyagigenyles_nyomtat.php';

if (isset($_POST['get_cikkszam'])) {
    
    $cikktozs = $adatbazis_kapcsolat->s2('id', 'cikktorzs', "`recid`='{$_POST['recid']}'");
    echo $cikktozs['id'];
    
    $weblap -> leallit();
    exit;
}

// Anyagigénylés adatok beolvasása
if (isset($_POST['get_adatok'])) {
    
    $anyagigenyles = $adatbazis_kapcsolat->s2('ikt_szam cikk_id nev igmen cikkmee', 'anyagigenyles', "`recid`='{$_POST['id']}'");
    
    echo nl2('
        Anyagigénylés szám: '.$anyagigenyles['ikt_szam'].'
        <br>Cikkszám: '.$anyagigenyles['cikk_id'].'
        <br>Cikk név: '.$anyagigenyles['nev'].'
        <br>Mennyiség: '.number_format($anyagigenyles['igmen'], 2, ',', ' ').' '.$anyagigenyles['cikkmee'].'
        ');
    
    $weblap -> leallit();
    exit;
    
}

// Már igényelve gomb feldolgozás
if (isset($_POST['anyagigenyles_mar_igenyelve'])) {

    // Tranzakció indul
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    // Vegyük az értinett munkalap anyagigénylés  sorokat
    $szallodak  = get_akt_login_szallodai();
    $munkalapok = $adatbazis_kapcsolat->s('recid', 'munkalap', "`telephely_id` IN (".implode(',', $szallodak).") AND `stat_id`='".MUNKALAP_STAT_ANYAGRAVAR."'");
    $munkalapok = adatbazis2tomb($munkalapok, 'recid');
    $munkalapok = implode(',', $munkalapok);

    $munkalap_anyag = $adatbazis_kapcsolat->s2('cikk_nev cikk_mee', 'munkalap_anyag', "`munkalap_id` IN (".$munkalapok.") AND `cikk_id`='{$_POST['cikk_id']}' AND `telephely_id`='{$_POST['telephely_id']}'");
    if( $munkalap_anyag === false ) { $db_error = true; }
    // Előbb csak kérdezünk
    if($_POST['csak_kerdezunk']){

        require 'selectek.php';

        $kerdes = nl2( getErtek('nev', 'telephely', 'id', $_POST['telephely_id']).'
                        <br>'.$_POST['cikk_id'].' '.$munkalap_anyag['cikk_nev'].'
                        <br>
                        <br>Diszpécsernaplóban igénylésre váró mennyiség: '.$_POST['ig_alatt'].' '.$munkalap_anyag['cikk_mee'].'
                        <br>Már igényelt mennyiség: '.$_POST['ig_men'].' '.$munkalap_anyag['cikk_mee'].'
                        <br>Ha folytatja, a diszpécsernaplóban igénylésre váró mennyiség státusza át lesz állítva "'.$munkAnyag_statusz[DISZPNAG_STAT_ANYAG_IGENYELVE].'" státuszra.
                        <br>
                        <br>Folytatja?');
        echo json_encode(array(
            'hiba'  => $kerdes,
            'gomb'  => '<input type="button" value="Igen" onclick="anyagigenyles_mar_igenyelve(\''.$_POST['cikk_id'].'\', \''.$_POST['telephely_id'].'\')" class="gomb zold"><input type="button" value="Nem" onclick="alert2()" class="gomb piros">',
        ));

        $weblap -> leallit();
        exit;
    }

    // Átírjuk a sátuszt
    $sql = $adatbazis_kapcsolat->u('munkalap_anyag', array('stat_id'), array(DISZPNAG_STAT_ANYAG_IGENYELVE), "`munkalap_id` IN (".$munkalapok.") AND `cikk_id`='{$_POST['cikk_id']}' AND `telephely_id`='{$_POST['telephely_id']}'");
//echo $adatbazis_kapcsolat->gp() . '<br>';
    if( $sql === false ) { $db_error = true; }


    // Tranzakció kommitolása, vagy rollback-ezése
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
    } else {
        mysqli_rollback($db_kapcs);
    }

    $weblap -> leallit();
    exit;
}


// Anyag rendelékezésre állás
if (isset($_POST['f_anyag_rendelkezesre_all'])) {

    // Tranzakció indul
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    // Az érintett munkalap anyagok státuszait 0-ra állítjuk
    $sql = $adatbazis_kapcsolat->u('munkalap_anyag', array('stat_id'), array(DISZPNAG_STAT_VAN_ANYAG), "`telephely_id`='{$_POST['telephely_id']}' AND `cikk_id`='{$_POST['cikk_id']}'");
    if( $sql === false ) { $db_error = true; }

    // Lefuttatjuk a munkalap_folyamatbanra_halehet függvényt, ezzel ha lehet, folyamatbanra állítva a munkalap státuszát
    $munkalapok = $adatbazis_kapcsolat->s('munkalap_id', 'munkalap_anyag', "`telephely_id`='{$_POST['telephely_id']}' AND `cikk_id`='{$_POST['cikk_id']}'");
    $munkalapok = adatbazis2tomb($munkalapok, 'munkalap_id');
    if(munkalap_folyamatbanra_halehet($munkalapok)===false){ $db_error = true; }

    // Tranzakció kommitolása, vagy rollback-ezése
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
    } else {
        mysqli_rollback($db_kapcs);
    }

    $weblap -> leallit();
    exit;
}

// Van-e várólistát anyagigénylés
if (isset($_POST['van_e_varoslista'])) {

    if( is_array($idk) ) {
        if($adatbazis_kapcsolat->s2('recid', 'anyagigenyles', "`recid` IN (".implode(',', $idk).") AND `stat_id`='".CIKKIGN_STAT_VARAKOZO."'")){
            echo json_encode(array(
                'hiba' => 'Várakozó státuszú anyagigénylést nem lehet ismét várakoztatni!'
            ));
        }
    }

    $weblap -> leallit();
    exit;
}

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
    function get_tobbkep_tabla(){                           // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!
        global $megnevezes;
        return $megnevezes.'_dok';

    }
}

// Get leírás
if (isset($_POST['get_leiras'])) {

    $anyagig = $adatbazis_kapcsolat->s2('igleir', 'anyagigenyles', "`recid`='{$_POST['id']}'");
    echo json_encode(array(
        'txt' => str_replace('<br>', "\n", htmlspecialchars_decode( $anyagig['igleir'] ) )
    ));
    $weblap->leallit();
    exit();
}

// Van-e olyan munkalap, ami ezt a cikket hiányolná
if (isset($_POST['varnak_e_anyagra'])) {

    $gomb = '';
    $hianyzo_anyagok = hianyzo_anyagok($_POST['cikk_id'], $_POST['telephely_id']);
    $db = count($hianyzo_anyagok);
    $hianyzo_anyagok = implode(',', $hianyzo_anyagok);
    if($hianyzo_anyagok){
        $gomb = $db.' db munkalap vár erre az anyagra: <br><input type="button" value="Mutat" class="gomb" onclick="window.open(\'munkalap.php?recidk='.$hianyzo_anyagok.'\')"><br>';
    }

    echo json_encode(array(
        'gomb' => $gomb
    ));

    $weblap->leallit();
    exit();
}

// Leírás update
if (isset($_POST['anyagigenylesLeirasHozzaadas'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat->s2('stat_id', 'anyagigenyles', "`recid`='{$idk[1]}'");
    if( $sor === false ) { $db_error = true; }


    // Státusz ellenőrzése
    if(  $sor['stat_id']==CIKKIGN_STAT_ELUTASITVA ) {
        echo json_encode(array(
            'title' => 'Hiba',
            'hiba'  => 'Elutasított státuszú anyagigényléshez nem lehet leírást hozzáadni!'
        ));

        $weblap->leallit();
        exit();
    }

    // Jog ellenőrzése
    if( !_jog(JOG_ANYAGREND_KESZIT) ) {
        echo json_encode(array(
            'hiba'  => 'Nincs joga leírást hozzáadni!',
            'title' => 'Hiba'
        ));
        $weblap->leallit();
        exit();
    }

    if( is_array($idk) ) {
        $sql = $adatbazis_kapcsolat->u('anyagigenyles', array('igleir'), array($_POST['indok']), "`recid`='{$idk[1]}'");
        if( $sql === false ) { $db_error = true; }
    }else{
        echo json_encode(array(
            'hiba'  => 'Kérjük válassza ki a szerkeszteni kívánt sort!',
            'title' => 'Hiba'
        ));
        $weblap->leallit();
        exit();
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz'    => 'siker',
            'leir_html'  => nl2br2($_POST['indok']),
        ));
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba'
        ));
    }

    $weblap->leallit();
    exit();
}

// Várólistához adás
if( isset($_POST['varolistahozAdas']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( is_array($idk) ) {

        /* Anyagigénylések táblázatos megjelenést kap, így nincs csoportosítás
        // Ellenőrizzük a csoportokat
        $csoportok = $adatbazis_kapcsolat->s('csoport_id', $page, "`recid` IN (".implode(',', $idk).")");
        if( $csoportok === false ) { $db_error = true; }

        $csoportok = adatbazis2tomb($csoportok, 'csoport_id');
        $csoportok_unique = array_unique($csoportok);

        $kijelolt_db = array_count_values($csoportok);
        foreach( $csoportok_unique as $csu ) {
            if( $csu > 0 ) {
                $csoport_db = $adatbazis_kapcsolat->s('csoport_id', $page, "`csoport_id` = '{$csu}'");
                if( $csoport_db === false ) { $db_error = true; }

                if( count($csoport_db) != $kijelolt_db[$csu] ) {
                    echo json_encode(array(
                        'hiba' => 'Csoportosított anyagigénylés önmagában nem várakoztatható. Előbb vegye ki a csoportból!'
                    ));

                    $weblap->leallit();
                    exit();
                }
            }
        }*/

        /*// Ellenőrizzük a státuszokat is
        foreach($idk as $id) {
            $statusz = $adatbazis_kapcsolat->s2('stat_id', $page, "`recid`='{$id}'");
            if( $statusz === false ) { $db_error = true; }
            if( $statusz['stat_id']!=CIKKIGN_STAT_NYITOTT ) {
                echo json_encode(array(
                    'hiba' => 'Csak nyitott státuszú anyagigénylést lehet várólistához adni!'
                ));
                $weblap->leallit();
                exit();
            }
        }*/

        // Jog ellenőrzése
        if( !_jog(JOG_ANYAGREND_KESZIT) ) {
            echo json_encode(array(
                'hiba' => 'Nincs joga anyagigénylést várakoztatni!'
            ));
            $weblap->leallit();
            exit();
        }

        // Státusz léptetés
        foreach($idk as $id) {
            $statusz = addStatusz( $page.'_statusz', $id, CIKKIGN_STAT_VARAKOZO, '', $_POST['indok'] );
            if( $statusz === false ) { $db_error = true; }

            // Megjegyzés
            addMegj('anyagigenyles', $id, $_POST['indok']);
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);
            echo json_encode(array(
                'statusz' => 'siker'
            ));
        } else {
            mysqli_rollback($db_kapcs);
            echo json_encode(array(
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba'
            ));
        }
    }

    $weblap->leallit();
    exit();
}

// Árazás
if( isset($_POST['arazas']) ) {
    $idk = json_decode($_POST['idk'], true);
    $munkalapID = $_POST['munkalapID'];

    // Jog ellenőrzése
    if( !_jog(JOG_ANYAGREND_KESZIT) ) {
        echo json_encode(array(
            'hiba' => 'Nincs megfelelő jogosultság a művelet végrehajtásához!'
        ));
        $weblap->leallit();
        exit();
    }

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( is_array($idk) ) {

        // Ellenőrizzük a csoportokat
        $csoportok = $adatbazis_kapcsolat->s('csoport_id', 'anyagigenyles', "`recid` IN (".implode(',', $idk).")");
        if( $csoportok === false ) { $db_error = true; }

        $csoportok = adatbazis2tomb($csoportok, 'csoport_id');
        $csoportok_unique = array_unique($csoportok);

        $kijelolt_db = array_count_values($csoportok);

        foreach( $csoportok_unique as $csu ) {
            if( $csu > 0 ) {
                $csoport_db = $adatbazis_kapcsolat->s('csoport_id', 'anyagigenyles', "`csoport_id` = '{$csu}'");
                if( $csoport_db === false ) { $db_error = true; }

                if( count($csoport_db) != $kijelolt_db[$csu] ) {
                    echo json_encode(array(
                        'hiba' => 'Csoportosított anyagigénylés önmagában nem árazható. Előbb vegye ki a csoportból!'
                    ));

                    $weblap->leallit();
                    exit();
                }
            }
        }

        // Ellenőrizzük a státuszokat és hogy az anyag, szállító, pénznem és egységár megegyezik-e a kiválasztott soroknál (vagy üres)
        $idk_inputba = '';
        $ossz_ig_menny   = 0;
        $ossz_rend_menny = 0;
        foreach($idk as $id) {

            $idk_inputba .= $id.',';
            $statusz = $adatbazis_kapcsolat->s2('stat_id', 'anyagigenyles', "`recid`='{$id}'");
            if( $statusz === false ) { $db_error = true; }

            if( $statusz['stat_id'] != CIKKIGN_STAT_NYITOTT ) {
                $bejelentes = $adatbazis_kapcsolat->s2('ikt_szam', 'anyagigenyles', "`recid` = '{$id}'");
                if( $bejelentes === false ) { $db_error = true; }

                $hiba_kieg = '';
                if( $bejelentes ) {
                    $hiba_kieg = '<br>A "'.$bejelentes['ikt_szam'].'" anyagigénylés státusza: '.getStatText('anyagigenyles', $statusz['stat_id']);
                }

                echo json_encode(array(
                    'hiba' => 'Árazni csak nyitott státuszú anyagigénylést lehet!'.$hiba_kieg
                ));
                $weblap->leallit();
                exit();

            }else{

                // Ha van eltérés az előző bejegyzéshez képest (üres mező megengedett) akkor hibaüzenet:
                $igenyles = $adatbazis_kapcsolat->s2('cikk_id szallito_id kcs_21 rndear igmen rndmen', 'anyagigenyles', "`recid`='{$id}'");
                if(
                        ($elozo_cikk_id      && $igenyles['cikk_id']      && $elozo_cikk_id      != $igenyles['cikk_id']     ) ||
                        ($elozo_szallito     && $igenyles['szallito_id']  && $elozo_szallito     != $igenyles['szallito_id'] ) ||
                        ($elozo_kcs_21       && $igenyles['kcs_21']       && $elozo_kcs_21       != $igenyles['kcs_21']      ) ||
                        ($elozo_rndear       && $igenyles['rndear']       && $elozo_rndear       != $igenyles['rndear']      )
                    ){
                    echo json_encode(array(
                        'hiba' => nl2(' A kijelölés több cikket tartalmaz.
                                    <br>Szállítót és egységárat egyszerre csak egy cikkhez lehet megadni!')
                    ));
                    $weblap->leallit();
                    exit();
                }
                $elozo_cikk_id      = $igenyles['cikk_id'];
                $elozo_szallito     = $igenyles['szallito_id'];
                $elozo_kcs_21       = $igenyles['kcs_21'];
                $elozo_rndear       = $igenyles['rndear'];

                // Ha ki van töltve a szállító (mivel kötelező mezők, ekkor ki lesz töltve a pénznem és rend. egységár is, akkor azt az ID-t töltjük be a popupba
                if($igenyles['szallito_id']){
                    $betoltid = $id;
                }

                // Adjuk össze az igényelt mennyiségeket
                $ossz_ig_menny   += $igenyles['igmen'];
                $ossz_rend_menny += $igenyles['rndmen'];

            }

        }

        // Ha egyik sem volt kitöltve, akkor vesszük az utoplsó ID-t. Ekkor más default értékeket is beállítunk
        $default_ertekek_mutat = false;
        if(!$betoltid){
            $betoltid = $id;
            $default_ertekek_mutat = true;
        }

        // Ha több sor van kijelölve, akkor a rendelt mennyiséget disabled-ezzük
        $rndmen_disabled = '';
        if(count($idk)>1){
            $rndmen_disabled = ' readonly';
        }

        // Legutóbb ellenőrzott rekod ID-ja az $id változóban, induljunk ki abból
        $igenyles = $adatbazis_kapcsolat->s2('*', 'anyagigenyles', "`recid`='$betoltid'");

        if($default_ertekek_mutat){
            $igenyles['kcs_21'] = 5;
        }

        // Ha nincs rendelés mennyiség, akkor az igénylés összege kerül bele
        if(!$ossz_rend_menny){
            $ossz_rend_menny = $ossz_ig_menny;
        }

        $igenyles['atvaltva_d'] = str_replace('-', '.', $igenyles['atvaltva_d']);
        $szall_sel_disabled = '';
        $dbf_checked = '';
        $szall_sel   = '';
        $mszk_sel    = '';
        switch($igenyles['erkcimid']){
            case 1: $szall_sel   = ' selected'; break;
            case 2: $mszk_sel    = ' selected'; break;
            case 3: $dbf_checked = ' checked'; $szall_sel_disabled = ' readonly'; break;
        }

        $penznem_txt = $adatbazis_kapcsolat->s2('info', 'erteklista', "`csoport`='21' AND `id`='{$igenyles['kcs_21']}'");

        $szallito_txt = $adatbazis_kapcsolat->s2('nev', 'szallito', "`id`='{$igenyles['szallito_id']}'");
        $popup = nl2('
                <p class="piros b" id="popup_hiba"></p>

                <div class="l">
                    <table class="popup" style="font-weight: normal; font-size: 1.2rem;">
                    <tr>
                        <td class="p r">Cikk:</td>
                        <td class="p" colspan="3">'.$igenyles['cikk_id'].'</td>
                    </tr>
                    <tr>
                        <td class="p r">Név:</td>
                        <td class="p" colspan="3">'.$igenyles['nev'].'</td>
                    </tr>
                    <tr>
                        <td class="p r">Igényelt mennyiség:</td>
                        <td class="p" colspan="3">'.number_format($ossz_ig_menny, 2, ',', ' ').' '.$igenyles['cikkmee'].'</td>
                    </tr>
                    <tr>
                        <td class="p r">Szállító:</td>
                        <td class="p" colspan="3">
                            <input type="text" autocomplete="off" class="oriasi csillag" name="pop_szallito_autocomp" id="pop_szallito_autocomp" onblur="if(!$(this).val()){ $(\'#pop_szallito\').val(\'\'); }" value="'.$szallito_txt['nev'].'">
                            <input type="hidden" name="pop_szallito" id="pop_szallito" value="'.$igenyles['szallito_id'].'">
                            <script>
                            $(document).ready(function() {
                                $(\'#pop_szallito_autocomp\').autocomplete({
                                    \'php_fajl\'        : \'../autocomplete.php\',
                                    \'tabla\'           : \'szallito\',
                                    \'oszlop_keres\'    : \'nev\',
                                    \'oszlop_listaz\'   : \'nev\',
                                    \'id_kiir\'         : \'id\',
                                    \'popupba\'         : true
                                });
                            });
                            </script>
                        </td>
                    </tr>
                    <tr>
                        <td class="p r">Rendelt mennyiség:</td>
                        <td class="p"><input'.$rndmen_disabled.' type="text" name="pop_rndmen" id="pop_rndmen" class="f_num csillag" value="'.number_format($ossz_rend_menny, 2, ',', ' ').'" onblur="rend_ossz_beallit()"> '.$igenyles['cikkmee'].'</td>
                        <td class="p r">
                            Rendelési összeg:
                        </td>
                        <td class="p">
                            <span id="rend_osszeg"></span> '.get_penznem().'
                            <br><span id="rend_osszeg2"></span>
                        </td>
                    </tr>
                    <tr id="indoklas_tr" class="dn">
                        <td class="p r" style="line-height: 1.2">Eltérő rendelt<br>mennyiség indoklása:</td>
                        <td class="p" colspan="3"><input type="text" id="pop_indoklas" name="pop_indoklas" class="oriasi"></td>
                    </tr>
                    <tr>
                        <td class="p r">Pénznem:</td>
                        <td class="p" colspan="3">
                            <input type="text" autocomplete="off" class="csillag" name="pop_kcs_21_autocomp" id="pop_kcs_21_autocomp" style="width: 5rem" value="'.$penznem_txt['info'].'">
                            <input type="hidden" name="pop_kcs_21" id="pop_kcs_21" value="'.$igenyles['kcs_21'].'">
                            <script>
                            $(document).ready(function() {
                                $(\'#pop_kcs_21_autocomp\').autocomplete({
                                    \'php_fajl\'        : \'../autocomplete.php\',
                                    \'tabla\'           : \'erteklista\',
                                    \'oszlop_keres\'    : \'info\',
                                    \'oszlop_listaz\'   : \'info\',
                                    \'csoport\'         : '.ERTEKLISTA_PENZNEM.',
                                    \'id_kiir\'         : \'id\',
                                    \'popupba\'         : true,
                                    \'arazas_beallit\'  : true
                                });
                            });
                            </script>
                        </td>
                    </tr>
                    <tr>
                        <td class="p r">Rendelési egységár:</td>
                        <td class="p"><input type="text" name="pop_rndear" id="pop_rndear" class="f_num" data-tort="4" value="'.number_format($igenyles['rndear'], 4, ',', ' ').'"> '.get_penznem().'</td>
                        <td class="p r">Árfolyam:</td>
                        <td class="p"><input type="text" name="pop_arfolyam" id="pop_arfolyam" class="f_num" data-tort="5" value="'.number_format($igenyles['arfolyam'], 5, ',', ' ').'"></td>
                    </tr>
                    <tr>
                        <td class="p r">Átváltva:</td>
                        <td class="p"><input type="text" name="pop_atvaltva_d" id="pop_atvaltva_d" class="datepicker" value="').$igenyles['atvaltva_d'].nl2('">
                        <td class="p r">Deviza rend. egységár:</td>
                        <td class="p"><input type="text" name="pop_vrndear" id="pop_vrndear" class="f_num" data-tort="4" value="'.number_format($igenyles['vrndear'], 4, ',', ' ').'"></td>
                    </tr>
                    <tr>
                        <td class="p r"></td>
                        <td class="p" colspan="3">
                            <div class="cb">
                                <input class="check" name="pop_dbf" id="pop_dbf" type="checkbox"'.$dbf_checked.' onclick="if( $(this).prop(\'checked\') ){ $(\'#pop_erkcimid\').val(\'\'); $(\'#pop_erkcimid\').attr(\'readonly\', 1); }else{ $(\'#pop_erkcimid\').removeAttr(\'readonly\'); }">
                                <label for="pop_dbf"></label>
                            </div>
                            MSzK fuvar
                        </td>
                    </tr>
                    <tr>
                        <td class="p r">Szállítási cím:</td>
                        <td class="p" colspan="3">
                            <select'.$szall_sel_disabled.' id="pop_erkcimid" name="pop_erkcimid" onchange="if( $(this).val() ){ $(\'#pop_dbf\').prop(\'checked\', false); }" class="csillag">
                                <option value="">-Válasszon-</option>
                                <option value="1"'.$szall_sel.'>Szálloda</option>
                                <option value="2"'.$mszk_sel.'>MSZK</option>
                            </select>
                        </td>
                    </tr>
                    </table>
                    <input type="hidden" name="pop_update_dt" id="pop_update_dt" value="'.$igenyles['update_dt'].'">
                    <input type="hidden" name="idk" id="idk" value="'.trim($idk_inputba, ',').'">
                    <input type="hidden" name="pop_ig_menny" id="pop_ig_menny" value="'.number_format($ossz_ig_menny, 2, ',', ' ').'">
                </div>
                <script>

                    /* Dátumozó */
                    $(\'.datepicker\').datetimepicker({
                        closeOnDateSelect   : true,
                        format              :\'Y.m.d\',
                        timepicker          : false,
                        validateOnBlur      : false,
                    });

                    /* Betöltéskor*/
                    $(document).ready(function () {
                        rend_ossz_beallit();
                    });

                    /* Pénznem cserekor */
                    $(document).on(\'keyup\', \'#pop_arfolyam, #pop_vrndear, #pop_rndmen, #pop_rndear, #pop_vrndear\', function(e) {
                       rend_ossz_beallit();
                    });

                    function rend_ossz_beallit(){

                        arfolyam = $(\'#pop_vrndear\').val().replace(\',\', \'.\').replace(/ /g, \'\');
                        ig_menny = $(\'#pop_ig_menny\').val().replace(\',\', \'.\').replace(/ /g,\'\');
                        rndear   = $(\'#pop_rndear\').val().replace(\',\', \'.\').replace(/ /g, \'\');
                        rndmen   = $(\'#pop_rndmen\').val().replace(\',\', \'.\').replace(/ /g, \'\');
                        arfolyam = $(\'#pop_arfolyam\').val().replace(\',\', \'.\').replace(/ /g, \'\');
                        vrndear  = $(\'#pop_vrndear\').val().replace(\',\', \'.\').replace(/ /g, \'\');

                        if(rndmen!=ig_menny){
                            $(\'#indoklas_tr\').show();
                        }else{
                            $(\'#indoklas_tr\').hide();
                        }

                        /* HUF esetén */
                        if($(\'#pop_kcs_21\').val()==5){
                            $(\'#pop_rndear\').removeAttr(\'readonly\').addClass(\'csillag\');
                            $(\'#pop_arfolyam\').attr(\'readonly\', 1).val(\'0,00000\');
                            $(\'#pop_vrndear\').attr(\'readonly\', 1).val(\'0,0000\');
                            $(\'#pop_atvaltva_d\').attr(\'readonly\', 1).val(\'').'    .  .  '.nl2('\');
                            rend_osszeg = rndmen*rndear;
                            $(\'#rend_osszeg\').html(ezresek(rend_osszeg, 4, \',\', \' \'));

                        /* Deviza esetén */
                        }else{

                            $(\'#pop_arfolyam\').removeAttr(\'readonly\').addClass(\'csillag\');
                            $(\'#pop_vrndear\').removeAttr(\'readonly\').addClass(\'csillag\');
                            $(\'#pop_atvaltva_d\').removeAttr(\'readonly\').addClass(\'csillag\');
                            $(\'#pop_rndear\').attr(\'readonly\', 1);

                            rend_osszeg = rndmen*arfolyam*vrndear;
                            $(\'#pop_rndear\').val( ezresek(arfolyam*vrndear, 4, \',\', \' \') );
                            $(\'#rend_osszeg\').html(ezresek(rend_osszeg, 4, \',\', \' \'));
                            $(\'#rend_osszeg2\').html(ezresek(rndmen*vrndear, 4, \',\', \' \')+\' \'+$(\'#pop_kcs_21_autocomp\').val());

                        }
                    }

                    $(document).on(\'keyup\', \'input\', function(e) {
                        if (e.which == KEY_ENTER ) {
                            mezo_nev = $(this).attr(\'name\');
                            stop = false;
                            $(this).parents(\'table.popup\').find(\'input, select, textarea\').filter(\':not(:disabled)\').filter(":not([readonly=\'readonly\'])").each(function() {

                                var name = $(this).attr(\'name\'),
                                    type = $(this).attr(\'type\'),
                                    tag  = $(this).prop(\'tagName\');

                                /* Autocomplete hidden inputjai miatt csak arra fókuszálunk, ami látszik is */
                                if( name !== undefined && (stop && type == \'checkbox\') || (stop && $(this).is(\':visible\')) ) {

                                    if( type == \'checkbox\' || tag == \'SELECT\' ) {
                                        document.getElementById(name).focus();
                                    } else {
                                        if($(\'#\' + name).length){
                                            document.getElementById(name).select();
                                        }
                                    }

                                    return false;
                                }

                                if( name == mezo_nev ) {
                                    stop = true;
                                }
                            });

                            e.preventDefault();
                        }
                    });
                </script>
                ');

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            echo json_encode(array(
                'popup' => $popup
            ));

        } else {
            mysqli_rollback($db_kapcs);

            echo json_encode(array(
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba'
            ));
        }
    }

    $weblap->leallit();
    exit();
}

// Árazás mentése
if ($_POST['arazas_ment']) {
    $_POST['ig_menny']  = str_replace(array(' ', ','), array('', '.'), $_POST['ig_menny']);
    $_POST['rndear']    = str_replace(array(' ', ','), array('', '.'), $_POST['rndear']);
    $_POST['rndmen']    = str_replace(array(' ', ','), array('', '.'), $_POST['rndmen']);
    $_POST['vrndear']   = str_replace(array(' ', ','), array('', '.'), $_POST['vrndear']);
    $_POST['arfolyam']  = str_replace(array(' ', ','), array('', '.'), $_POST['arfolyam']);
    $idk = explode(',', $_POST['idk']);

    // Hibakezelés
    foreach($idk as $id){
        $anyagigenyles = $adatbazis_kapcsolat->s2('update_dt', 'anyagigenyles', "`recid`='{$id}'");
        if($_POST['update_dt']<$anyagigenyles['update_dt']){
            echo json_encode(
                        array(
                            'title'         => 'Hiba',
                            'hiba_alert'    => 'Anyagigénylés nem árazható, mert idő közben más már módosította az anyagigénylést!<br>Kérjük kezdje újra az árazást!',
                        )
                );
            $weblap -> leallit();
            exit();
        }
    }
    if(!$_POST['szallito_id']){
        echo json_encode(
                    array(
                        'hiba'  => 'Kérjük adja meg a szállítót!',
                        'focus' => 'pop_szallito'
                    )
            );
        $weblap -> leallit();
        exit();
    }
    if( !$_POST['rndmen'] || $_POST['rndmen']=='0.00' ){
        echo json_encode(
                    array(
                        'hiba'  => 'Kérjük adja meg a rendelt mennyiséget!',
                        'focus' => 'pop_rndmen'
                    )
            );
        $weblap -> leallit();
        exit();
    }
//    if($_POST['ig_menny'] < $_POST['rndmen']){
//        echo json_encode(
//                    array(
//                        'hiba'  => 'A rendelt mennyiség nem lehet több az igényelt mennyiségtől!',
//                        'focus' => 'pop_rndmen'
//                    )
//            );
//        $weblap -> leallit();
//        exit();
//    }
    if($_POST['ig_menny'] != $_POST['rndmen'] && !$_POST['indok']){
        echo json_encode(
                    array(
                        'hiba'  => 'Kérjük adja meg a rendelési mennyiség megváltoztatásának indoklását!',
                        'focus' => 'pop_indoklas'
                    )
            );
        $weblap -> leallit();
        exit();
    }
    if(!$_POST['kcs_21']){
        echo json_encode(
                    array(
                        'hiba'  => 'Kérjük adja meg a pénznemet!',
                        'focus' => 'pop_kcs_21'
                    )
            );
        $weblap -> leallit();
        exit();
    }
    if($_POST['kcs_21']==5){
        if(!$_POST['rndear'] || number_format($_POST['rndear'], 4, '.', '')=='0.0000'){
            echo json_encode(
                        array(
                            'hiba'  => 'Kérjük adja meg a rendelési egységárat!',
                            'focus' => 'pop_rndear'
                        )
                );
            $weblap -> leallit();
            exit();
        }
    }

    // Ha nem HUF
    if( $_POST['kcs_21']!=5 ){
        if(!$_POST['arfolyam'] || number_format($_POST['arfolyam'], 4, '.', '')=='0.0000'){
            echo json_encode(
                        array(
                            'hiba'  => 'Kérjük adja meg az árfolyamot!',
                            'focus' => 'pop_arfolyam'
                        )
                );
            $weblap -> leallit();
            exit();
        }
        if(!$_POST['vrndear'] || number_format($_POST['vrndear'], 4, '.', '')=='0.0000'){
            echo json_encode(
                        array(
                            'hiba'  => 'Kérjük adja meg a Deviza rend. egységárat!',
                            'focus' => 'pop_vrndear'
                        )
                );
            $weblap -> leallit();
            exit();
        }

        $_POST['atvaltva_d'] = trim($_POST['atvaltva_d'], '. ');
        if(!$_POST['atvaltva_d'] || $_POST['atvaltva_d']=='0000.00.00'){
            echo json_encode(
                        array(
                            'hiba'  => 'Kérjük adja meg az átváltás dátumát!',
                            'focus' => 'pop_atvaltva_d'
                        )
                );
            $weblap -> leallit();
            exit();
        }
    }

    // Tranzakció indul
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    // Több sor esetén a rendelt mennyiséghez az igényelt mennyiséget írjuk be
    if(count($idk)>1){
        foreach($idk as $id){
            $anyagig = $adatbazis_kapcsolat->s2('igmen', 'anyagigenyles', "`recid`='{$id}'");
            if( $anyagig === false ) { $db_error = true; }
            $sql = $adatbazis_kapcsolat->u('anyagigenyles',     array('szallito_id',         'kcs_21',         'rndear',           'rndmen',            'vrndear',          'arfolyam',          'atvaltva_d',           'erkcimid',         'update_dt', 'update_usr'                          ),
                                                                array($_POST['szallito_id'], $_POST['kcs_21'], $_POST['rndear'],   $anyagig['igmen'],   $_POST['vrndear'],   $_POST['arfolyam'], $_POST['atvaltva_d'],   $_POST['erkcimid'], 'NOW()',     $_SESSION[get_db_tabla().'teljes_nev']),
                                            "`recid` = '{$id}'");
        }

    // Egy sor esetén a rendelt mennyiséget is felülírjuk
    }else{
        $sql = $adatbazis_kapcsolat->u('anyagigenyles',         array('szallito_id',         'kcs_21',         'rndear',           'rndmen',           'vrndear',          'arfolyam',          'atvaltva_d',         'erkcimid',         'update_dt', 'update_usr'                         ),
                                                                array($_POST['szallito_id'], $_POST['kcs_21'], $_POST['rndear'],   $_POST['rndmen'],   $_POST['vrndear'],   $_POST['arfolyam'], $_POST['atvaltva_d'], $_POST['erkcimid'], 'NOW()',     $_SESSION[get_db_tabla().'teljes_nev']),
                                        "`recid` IN (".$_POST['idk'].")");
    }
    if( $sql === false ) { $db_error = true; }

    // Ha az igényléstől kisebb a rendelés, akkor megjegyzést is fűztek hozzá
    if($_POST['ig_menny']!=$_POST['rndmen']){
        foreach($idk as $id){
            $megj = addMegj('anyagigenyles', $id, 'Eltérő rendelt mennyiség indoklása:'."\r\n".' '.$_POST['indok']);
            if($megj===false){ $db_error = true; }
        }
    }

    // Tranzakció kommitolása, vagy rollback-ezése
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array('hiba' => 'Az adatbázisba való íráskor hiba történt!'));
    }

    $weblap -> leallit();
    exit();
}

// Küldés jóváhagyásra hatása, hogy Árazva státuszt kap
if( isset($_POST['kuldes_jovahagyasra']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( is_array($idk) ) {
        // Ellenőrizzük a csoportokat
        $sql = $csoportok = $adatbazis_kapcsolat->s('csoport_id', $page, "`recid` IN (".implode(',', $idk).")", '', '');
        if( $sql === false ) { $db_error = true; }

        $csoportok = adatbazis2tomb($csoportok, 'csoport_id');
        $csoportok_unique = array_unique($csoportok);

        $kijelolt_db = array_count_values($csoportok);

        foreach( $csoportok_unique as $csu ) {
            if( $csu > 0 ) {
                $sql = $csoport_db = $adatbazis_kapcsolat->s('csoport_id', $page, "`csoport_id` = '{$csu}'", '', '');
                if( $sql === false ) { $db_error = true; }

                if( count($csoport_db) != $kijelolt_db[$csu] ) {
                    echo json_encode(array(
                        'title' => 'Hiba',
                        'hiba'  => 'Csoportosított anyagigénylés önmagában nem küldhető jóváhagyásra. Előbb vegye ki a csoportból!'
                    ));

                    $weblap->leallit();
                    exit();
                }
            }
        }

        // Ellenőrizzük a státuszokat és hogy mind árazva van-e?
        foreach($idk as $id) {

            $statusz = $adatbazis_kapcsolat->s2('recid stat_id insert_dt', $page.'_statusz', "`{$page}_recid`='$id'", 'recid desc', '1');
            if( $statusz === false ) { $db_error = true; }

            if( $statusz ) {

                if(  $statusz['stat_id']!=CIKKIGN_STAT_NYITOTT ) {
                    echo json_encode(array(
                        'title' => 'Hiba',
                        'hiba'  => 'Csak nyitott státuszú anyagigényléseket lehet jóváhagyásra küldeni!'
                    ));

                    $weblap->leallit();
                    exit();
                }

                // Valóban árazva van-e? Szállító és egységár megadva
                $igenyles = $adatbazis_kapcsolat->s2('szallito_id rndear rndmen kcs_21', 'anyagigenyles', "`recid`='$id'");
                if(!$igenyles['szallito_id'] || $igenyles['rndear']==0.0000 || $igenyles['rndmen']==0.00 || !$igenyles['kcs_21']){
                    echo json_encode(array(
                        'title' => 'Hiba',
                        'hiba'  => 'Csak árazott anyagigényléseket lehet jóváhagyásra küldeni!'
                    ));

                    $weblap->leallit();
                    exit();
                }

            } else {
                echo json_encode(array(
                    'title' => 'Hiba',
                    'hiba'  => 'Státusz nem található!'
                ));

                $weblap->leallit();
                exit();
            }
        }

        // Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if (isset($_POST['csak_kerdezunk'])) {

            echo json_encode(array(
                'hiba'  => 'Biztos jóváhagyásra küldi kijelölt anyagigényléseket?',
                'gomb'  => '<input class="gomb zold" value="Igen" onclick="anyagigenyles_kuldes_jovahagyasra();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        foreach($idk as $id) {
            $statusz = addStatusz( $page.'_statusz', $id, CIKKIGN_STAT_ARAZVA );
            if( $statusz === false ) { $db_error = true; }
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            $hiba = '';

            foreach($idk as $id) {
                $valasz = level_felkeszit_es_kuld($page, $id, ERT_ANYAGIG_JOVAHAGYAS, 'anyagigenylesJovahagy', '_jog'.JOG_ANYAGIGENYLES_JOVAHAGY);
                if( substr($valasz, 0, 4)=='Hiba' ){
                    $hiba .= $valasz;
                }
            }
            if($hiba){
                echo json_encode(array(
                    'hiba'  => $hiba,
                    'title' => 'Hiba',
                ));
                $weblap->leallit();
                exit();
            }

            mysqli_commit($db_kapcs);
            echo json_encode(array(
                'statusz' => 'siker'
            ));
        } else {
            mysqli_rollback($db_kapcs);
            echo json_encode(array(
                'title' => 'Hiba',
                'hiba'  => tranzakcioHiba()
            ));
        }
    }

    $weblap->leallit();
    exit();
}

// Anyagigénylés jóváhagyása. Hatása, hogy Jóváhagyva státuszt kap illetve pár mezőt kitöltünk még az adatbázisban
if( isset($_POST['anyagigenyles_jovahagy']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $_POST['jhmen'] = str_replace(array(' ', ','), array('', '.'), $_POST['jhmen']);

    if( is_array($idk) ) {

        // Ellenőrizzük a státuszokat, és feltételeket
        foreach($idk as $id) {

            $statusz = $adatbazis_kapcsolat->s2('stat_id', $page, "`recid`='{$id}'");
            if( $statusz === false ) { $db_error = true; }

            // Státusz ellenőrzése
            if(  $statusz['stat_id']!=CIKKIGN_STAT_ARAZVA ) {
                echo json_encode(array(
                    'hiba' => 'Csak árazva státuszú anyagigényléseket lehet jóváhagyni!'
                ));
                $weblap->leallit();
                exit();
            }

            // Jog ellenőzése
            if(  !_jog(JOG_ANYAGIGENYLES_JOVAHAGY) ) {
                echo json_encode(array(
                    'hiba' => 'Nincs joga jóváhagyni!'
                ));
                $weblap->leallit();
                exit();
            }

            // Jóváhagyott mennyiség ellenőrzése
            $igenyles = $adatbazis_kapcsolat->s2('nev cikk_id rndmen rndear cikkmee kcs_21 igmen', 'anyagigenyles', "`recid`='{$id}'");
            if($_POST['jhmen']==0 || $_POST['jhmen']==0.00){
                echo json_encode(array(
                    'hiba'  => 'A jóváhagyott mennyiség nem lehet 0 '.$igenyles['cikkmee'].'!<script>$(\'#jhmen_'.$id.'\').addClass(\'hibas_ide_focus\')</script>',
                    'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); $(\'#jhmen_'.$id.'\').select()">',
                    'focus' => 'jhmen'
                ));
                $weblap->leallit();
                exit();
            }
            if($_POST['jhmen']<0){
                echo json_encode(array(
                    'hiba'  => 'A jóváhagyott mennyiség nem lehet negatív!',
                    'focus' => 'jhmen'
                ));
                $weblap->leallit();
                exit();
            }
            if($_POST['jhmen']>$igenyles['rndmen']){
                echo json_encode(array(
                    'hiba'  => 'A jóváhagyott mennyiség nem lehet több, mint a jóváhagyásra váró mennyiség ('.str_replace('.', ',', $igenyles['rndmen']).' '.$igenyles['cikkmee'].')!',
                    'focus' => 'jhmen',
                ));
                $weblap->leallit();
                exit();
            }

        }

        // Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if (isset($_POST['csak_kerdezunk'])) {

            echo json_encode(array(
                'hiba' => nl2('
                            Cikkszám: '.$igenyles['cikk_id'].'
                            <br>Cikk név: '.$igenyles['nev'].'
                            <br>Mennyiség: '.number_format($_POST['jhmen'], 2, ',', ' ').' '.$igenyles['cikkmee'].'
                            <br>Jóváhagyott érték: '. number_format($_POST['jhmen']*$igenyles['rndear'], 0, '', ' ').' '. get_penznem()/*Igen ide a Ft-kell és nem az utolsó tétel pénzneme */.'
                            <br>
                            <br>Biztosan jóváhagyja az anyagigénylést?
                            '),
                'gomb' => '<input class="gomb zold" value="Igen" onclick="szurkit(); anyagigenyles_jovahagy();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        foreach($idk as $id) {

            // Státusz léptetés jóváhagyvára
            $statusz = addStatusz( $page.'_statusz', $id, CIKKIGN_STAT_JOVAHAGYVA );
            if( $statusz === false ) { $db_error = true; }

            $sql = $adatbazis_kapcsolat->u('anyagigenyles', array('jhmen', 'rndmen'), array($_POST['jhmen'], $_POST['jhmen']), "`recid`='{$id}'");
            if( $sql === false ) { $db_error = true; }

        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);
            echo json_encode(array(
                'statusz' => 'siker'
            ));
        } else {
            mysqli_rollback($db_kapcs);
            echo json_encode(array(
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba'
            ));
        }
    }

    $weblap->leallit();
    exit();
}

// Anyagigénylés elutasítása: hatása, hogy Jóváhagyva státuszt kap illetve pár mezőt kitöltünk még az adatbázisban
if( isset($_POST['anyagigenyles_elutasitas']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( is_array($idk) ) {

        // Ellenőrizzük a státuszokat, és feltételeket
        foreach($idk as $id) {

            $statusz = $adatbazis_kapcsolat->s2('stat_id', $page, "`recid`='{$id}'");
            if( $statusz === false ) { $db_error = true; }

            if(  $statusz['stat_id']!=CIKKIGN_STAT_ARAZVA ) {
                echo json_encode(array(
                    'hiba' => 'Csak árazva státuszú anyagigényléseket lehet elutasítani!'
                ));

                $weblap->leallit();
                exit();
            }

        }

        // Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if (isset($_POST['csak_kerdezunk'])) {

            $anyagigenyles = $adatbazis_kapcsolat->s2('cikk_id nev cikkmee igmen', 'anyagigenyles', "`recid`='{$id}'");
            echo json_encode(array(
                'hiba' => nl2('
                                <div id="hibauzi" class="piros"></div>
                                Cikkszám: '.$anyagigenyles['cikk_id'].'
                                <br>Cikk név: '.$anyagigenyles['nev'].'
                                <br>Mennyiség: '.number_format($anyagigenyles['igmen'], 2, ',', ' ').' '.$anyagigenyles['cikkmee'].'
                                <br>
                                <br>Anyagigénylés elutasításának indoklása:
                                <br><textarea id="pop_indoklas" style="width: 37rem; margin-top: 1rem" class="csillag" onkeyup="if( $(this).val().trim()==\'\' ){ $(this).val(\'\'); }"></textarea>
                            '),
                'gomb' => '<input class="gomb zold" value="Elutasítás" onclick="anyagigenyles_elutasitas();" type="button"><input class="gomb piros" value="Mégsem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        foreach($idk as $id) {

            // Státusz léptetés elutasítvára
            $statusz = addStatusz( $page.'_statusz', $id, CIKKIGN_STAT_ELUTASITVA, '', $_POST['oka'] );
            if( $statusz === false ) { $db_error = true; }
            if($_POST['oka']){
                if( addMegj('anyagigenyles', $id, $_POST['oka']) === false ) {
                    $db_error = true;
                }
            }

            $sql = $adatbazis_kapcsolat->u('anyagigenyles', array('jhmen'), array(0), "`recid`='$id'");
            if( $sql === false ) { $db_error = true; }

        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);
            echo json_encode(array(
                'statusz' => 'siker'
            ));
        } else {
            mysqli_rollback($db_kapcs);
            echo json_encode(array(
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba'
            ));
        }
    }

    $weblap->leallit();
    exit();
}

// Minden anyagigénylés jóváhagyása: hatása jóváhagyva státuszt kap
if( isset($_POST['anyagigenyles_mind_jovahagy']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    $jhmenek = json_decode($_POST['jhmenek'], true);

    // Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
    if (isset($_POST['csak_kerdezunk'])) {

        $ertek = 0;
        $igenylesek = $adatbazis_kapcsolat->s('recid jhmen rndmen rndear kcs_21 cikkmee', 'anyagigenyles', "`stat_id`='".CIKKIGN_STAT_ARAZVA."'".get_akt_login_szallodai('sql'));
//echo $adatbazis_kapcsolat->gp() . '<br>';
        // Ha nincs találat, akkor hibaüzi és kilépés
        if(!$igenylesek){
            echo json_encode(array(
                'hiba' => 'Nincs jóváhagyásra váró anyagigénylés!',
            ));
            $weblap->leallit();
            exit();
        }
        $i = 0;

        foreach ($igenylesek as $igeny){

            // Ha postban kaptunk volna ide értéket, akkor azzal számolunk
            if($jhmenek[$igeny['recid']]){
                $igeny['jhmen'] = str_replace( array(',', ' '), array('.', ''), $jhmenek[$igeny['recid']]);
            }

            if($igeny['jhmen']==0.00 ){
                echo json_encode(array(
                    'hiba'  => 'A jóváhagyott mennyiség nem lehet 0 '.$igeny['cikkmee'].'!<script>$(\'#jhmen_'.$igeny['recid'].'\').addClass(\'hibas_ide_focus\')</script>',
                    'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); $(\'#jhmen_'.$igeny['recid'].'\').select()">',
                    'focus' => 'jhmen'
                ));
                $weblap->leallit();
                exit();
            }
            if($igeny['jhmen']<0){
                echo json_encode(array(
                    'hiba'  => 'A jóváhagyott mennyiség nem lehet negatív!',
                    'focus' => 'jhmen'
                ));
                $weblap->leallit();
                exit();
            }
            if($igeny['jhmen']>$igeny['rndmen']){
                echo json_encode(array(
                    'hiba'  => 'A jóváhagyott mennyiség nem lehet több, mint a jóváhagyásra váró mennyiség ('.str_replace('.', ',', $igeny['rndmen']).' '.$igeny['cikkmee'].')!',
                    'focus' => 'jhmen',
                ));

                $weblap->leallit();
                exit();
            }

            $ertek += $igeny['jhmen']*$igeny['rndear'];

            $i++;
        }
        echo json_encode(array(
            'hiba' => nl2('
                            Jóváhagyásra váró érték: '.number_format($ertek, 0, ',', ' ').' '.get_penznem().'
                            <br>Folytatja?
                        '),
            'gomb' => '<input class="gomb zold" value="Igen" onclick="anyagigenyles_mind_jovahagy();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
        ));

        $weblap->leallit();
        exit();
    }


    // Státusz léptetés jóváhagyás alattra
    $igenylesek = $adatbazis_kapcsolat->s('recid rndmen', 'anyagigenyles', "`stat_id`='".CIKKIGN_STAT_ARAZVA."'".get_akt_login_szallodai('sql'));
    foreach ($igenylesek as $igeny){

        $sql = $adatbazis_kapcsolat->u('anyagigenyles', array('jhmen'), array($igeny['rndmen']), "`recid`='{$igeny['recid']}'");
        if( $sql === false ) { $db_error = true; }

        $statusz = addStatusz( $page.'_statusz', $igeny['recid'], CIKKIGN_STAT_JOVAHAGYVA );
        if( $statusz === false ) {  $db_error = true; }
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'siker'
        ));
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba'
        ));
    }

    $weblap->leallit();
    exit();
}


// Rendelés készítése: hatása, hogy Megrendelve státuszt kap
if( isset($_POST['rendeles_keszit']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( is_array($idk) ) {
        // Ellenőrizzük a csoportokat
        $sql = $csoportok = $adatbazis_kapcsolat->s('csoport_id', $page, "`recid` IN (".implode(',', $idk).")", '', '');
        if( $sql === false ) { $db_error = true; }

        $csoportok = adatbazis2tomb($csoportok, 'csoport_id');
        $csoportok_unique = array_unique($csoportok);

        $kijelolt_db = array_count_values($csoportok);

        foreach( $csoportok_unique as $csu ) {
            if( $csu > 0 ) {
                $sql = $csoport_db = $adatbazis_kapcsolat->s('csoport_id', $page, "`csoport_id` = '{$csu}'", '', '');

                if( $sql === false ) {
                    $db_error = true;
                }

                if( count($csoport_db) != $kijelolt_db[$csu] ) {
                    echo json_encode(array(
                        'hiba' => 'Csoportosított anyagigénylésből önmagában nem készíthető rendelés. Előbb vegye ki a csoportból!'
                    ));

                    $weblap->leallit();
                    exit();
                }
            }
        }

        // Ellenőrizzük a státuszokat és hogy mind jóváhagyva van-e?
        foreach($idk as $id) {

            $statusz = $adatbazis_kapcsolat->s2('stat_id', $page, "`recid`='$id'");
            if( $statusz === false ) { $db_error = true; }

            if( $statusz ) {

                if(  $statusz['stat_id']!=CIKKIGN_STAT_JOVAHAGYVA ) {
                    echo json_encode(array(
                        'hiba' => 'Csak jóváhagyva státuszú anyagigénylésekből lehet rendelést készíteni!'
                    ));

                    $weblap->leallit();
                    exit();

                }else{

                    // Ha van eltérés az előző bejegyzéshez képest (üres mező megengedett) akkor hibaüzenet:
                    $igenyles = $adatbazis_kapcsolat->s2('cikk_id szallito_id kcs_21 rndear igmen rndmen', 'anyagigenyles', "`recid`='{$id}'");
                    if(
                            ($elozo_szallito     && $igenyles['szallito_id']   && $elozo_szallito     != $igenyles['szallito_id'] ) ||
                            ($elozo_kcs_21       && $igenyles['kcs_21']        && $elozo_kcs_21       != $igenyles['kcs_21']      )
                        ){
                        echo json_encode(array(
                            'hiba' => 'Csak azonos szállítóval és pénznemmel rendelkező anyagigénylésekből lehet rendelést készíteni!'
                        ));
                        $weblap->leallit();
                        exit();
                    }
                    $elozo_szallito     = $igenyles['szallito_id'];
                    $elozo_kcs_21       = $igenyles['kcs_21'];

                    // Ha ki van töltve a szállító (mivel kötelező mezők, ekkor ki lesz töltve a pénznem és rend. egységár is, akkor azt az ID-t töltjük be a popupba
                    if($igenyles['szallito_id']){
                        $betoltid = $id;
                    }

                }

            } else {
                echo json_encode(array(
                    'hiba' => 'Státusz nem található!'
                ));
                $weblap->leallit();
                exit();
            }
        }

        // Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if ($_POST['csak_kerdezunk']) {

            echo json_encode(array(
                'hiba' => count($idk).' db anyagigénylés van kijelölve.<br>Biztos, hogy elkészíti a megrendelést?',
                'gomb' => '<input class="gomb zold" value="Igen" onclick="anyagigenyles_rendeles_keszit();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }


        // Ha még nem kaptunk utasítást arra, hogy mehet adatbázisba, akkor a nyomtatási képeket ajánjuk fel
        if ($_POST['mehet_db_be']!='mehet') {

            mysqli_query($db_kapcs, 'START TRANSACTION;');
            $sorszam = mysqli_query($db_kapcs, 'SELECT `sorszam` FROM `'.get_db_tabla().'iktszam_seq` WHERE `tabla`=\'cikkrendfej\' AND `ev`=\''.date('y').'\' LIMIT 1 FOR UPDATE;');
//echo 'SELECT `sorszam` FROM `'.get_db_tabla().'iktszam_seq` WHERE `tabla`=\'cikkrendfej\' AND `ev`=\''.date('y').'\' LIMIT 1 FOR UPDATE;';
            $sorszam = mysqli_fetch_assoc($sorszam);
//var_dump($sorszam);
            $sorszam = $sorszam['sorszam']+1;
            mysqli_query($db_kapcs, 'UPDATE `'.get_db_tabla().'iktszam_seq` SET `sorszam`=\''.$sorszam.'\' WHERE `tabla`=\'cikkrendfej\' AND `ev`=\''.date('y').'\'');
            $sorszam = 'R'.date('y').'/'.sprintf('%04d', $sorszam).'|'.$_POST['idk'];
            $sorszam_kodolva = trim(base64_encode($sorszam), '=');

            $html = URL_letolt(self().'?nyomtat='.$sorszam_kodolva.'&amp;szall');

            echo json_encode(array(
                'hiba'      => nl2('
                                    <div class="l">
                                        Válasszon nyomtatási képet:
                                        <br><a target="_blank" href="'.self().'?nyomtat='.$sorszam_kodolva.'&amp;szall">Megrendelés a szállítónak</a>
                                        <br><a target="_blank" href="'.self().'?nyomtat='.$sorszam_kodolva.'&amp;cim">Szállítási címenkénti összesítés</a>
                                        <br><a target="_blank" href="'.self().'?nyomtat='.$sorszam_kodolva.'&amp;oracle">Oracle cikkszámonkénti összesítés</a>
                                        <br>
                                        <br>Sikerült a nyomtatás?
                                    </div>
                                    '),
                'gomb'      => '<input class="gomb zold" value="Igen" onclick="anyagigenyles_rendeles_keszit(\'mehet\');" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">',
                'felirat'   => 'Nyomtatás'
            ));

            $weblap->leallit();
            exit();
        }

        // Szekvencia táblából kiszámoljuk a rendelés következő sorszámát
        $sorszam = get_iktatoszam_sorszam('cikkrendfej');
        if( $sorszam === false ) { $db_error = true; }

        // Most kommitoljuk el a popupban lekért sorszámot
        mysqli_query($db_kapcs, 'COMMIT');

        // Könyvtárak kialakítása
        if( !is_dir('../'.get_utvonal().'rendeles') ) {
            mkdir('../'.get_utvonal().'rendeles');
        }

        if( !is_dir('../'.get_utvonal().'rendeles/'.date('y')) ) {
            mkdir('../'.get_utvonal().'rendeles/'.date('y'));
        }

        if( !is_dir('../'.get_utvonal().'rendeles/'.date('y').'/'.$elozo_szallito) ) {
            mkdir('../'.get_utvonal().'rendeles/'.date('y').'/'.$elozo_szallito);
        }

        // Rendelés ID formázása
        $rndid = 'R'.date('y').'/'.sprintf('%04d', $sorszam);
        $pdf_utvonal = 'rendeles/'.date('y').'/'.$elozo_szallito.'/'.str_replace('/', '_', $rndid).'.pdf';

        // Számoljuk ki a rendelt értéket
        $rndertek = 0;
        $anyagigenylesek = $adatbazis_kapcsolat->s('rndmen rndear', 'anyagigenyles', "`recid` IN (".implode(',', $idk).")");
        foreach($anyagigenylesek as $anyagi){
            $rndertek += ($anyagi['rndmen']*$anyagi['rndear']);
        }
        // Létrehozunk egy új rendelésfej bejegyzést
        $cikkrendfej_id = $adatbazis_kapcsolat->i('cikkrendfej',    array('stat_id',                'rndid', 'rnd_dt', 'rndertek',  'kepfajl',     'szallito_id',    'insert_dt',    'insert_usr',                           'update_usr'                            ),
                                                                    array(CIKKIGN_STAT_MEGRENDELVE, $rndid,  'NOW()',  $rndertek,   $pdf_utvonal,  $elozo_szallito,  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'], $_SESSION[get_db_tabla().'teljes_nev']  )
                                                    );
        if( $cikkrendfej_id === false ) { $db_error = true; }

        // Felvesszük az első státuszt
        $sql = $adatbazis_kapcsolat -> i('cikkrendfej_statusz',
                                            array('cikkrendfej_recid',  'stat_id',                'login',                          'insert_dt',    'insert_usr',                              'update_usr'                          ),
                                            array($cikkrendfej_id,      CIKKIGN_STAT_MEGRENDELVE, $_SESSION[get_db_tabla().'user'], 'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],    $_SESSION[get_db_tabla().'teljes_nev'] )
                                        );
        if( $sql === false ) { $db_error = true; }

        // Anyagigénylés sorok updatelése (státuszt is, illetve egyéb mezőket is)
        foreach($idk as $id) {
            if( addStatusz( $page.'_statusz', $id, CIKKIGN_STAT_MEGRENDELVE ) === false ) {
                $db_error = true;
            }

            // Az anyagigénylés érintett sorait updateljük
            $adatbazis_kapcsolat->u('anyagigenyles',    array('rndid', 'rnd_dt', 'kepfajl',    'update_usr',                           'aparecid'       ),
                                                        array($rndid,  'NOW()',  $pdf_utvonal, $_SESSION[get_db_tabla().'teljes_nev'], $cikkrendfej_id  ),
                                    "`recid`='$id'");
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);
            echo json_encode(array(
                'statusz' => 'siker'
            ));
        } else {
            mysqli_rollback($db_kapcs);
            echo json_encode(array(
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba'
            ));
        }
    }

    $weblap->leallit();
    exit();
}

// Anyagigénylés érkeztetése
if (isset($_POST['anyagigenyles_erkeztet'])) {

    // Rendelésszám lekérdezése
    if($_POST['rsz']){

        $recid = $adatbazis_kapcsolat->s2('recid', 'cikkrendfej', "`rndid`='{$_POST['rsz']}'");
        if(!$recid){
            echo json_encode(array(
                'hiba' => 'Nincs ilyen rendelésszám az anyagigénylés naplóban!'
            ));
            $weblap->leallit();
            exit();
        }
        if($adatbazis_kapcsolat->s('recid', 'cikkrendfej', "`rndid`='{$_POST['rsz']}' AND `stat_id`>'".CIKKIGN_STAT_RESZBENBEERK."'")){
            echo json_encode(array(
                'hiba' => 'Ehhez a megrendeléshez már rögzítve vannak a beérkezett mennyiségek!'
            ));
            $weblap->leallit();
            exit();
        }

        $html = nl2('
                <table class="csikos kicsi anyagig_erkeztet">
                <tr>
                    <th>Szálloda</th>
                    <th>Cikk</th>
                    <th style="min-width: 20rem" class="l">Név</th>
                    <th>Igénylet mennyiség</th>
                    <th>Rendelt mennyiség</th>
                    <th>Előzőleg beérkezett mennyiség</th>
                    <th>Beérkezett mennyiség</th>
                    <th>Mee</th>
                    <th>Nem lesz több beérkezés</th>
                    <th>Anyagra váró munkalapok</th>
                </tr>
            ');

        // Beloginolt user szállodáira is szűrünk
        $szallodak = get_akt_login_szallodai();
        if($szallodak){

            $igenylesek = $adatbazis_kapcsolat->s('recid telephely_id cikk_id nev igmen rndmen erkmen cikkmee szallito_id stat_id', 'anyagigenyles', "`aparecid`='{$recid['recid']}' AND `telephely_id` IN (".implode(',', $szallodak).")");
            foreach($igenylesek as $igeny){

                $telephely = $adatbazis_kapcsolat->s2('nev', 'telephely', "`id`='{$igeny['telephely_id']}'");

                $gomb = '(nincs)';
                $hianyzo_anyagok = hianyzo_anyagok($igeny['cikk_id'], $igeny['telephely_id']);
                $hianyzo_anyagok = implode(',', $hianyzo_anyagok);
                if($hianyzo_anyagok){
                    $gomb = '<input type="button" value="Mutat" class="gomb" onclick="window.open(\'munkalap.php?recidk='.$hianyzo_anyagok.'\')">';
                }

                // Ha a státusz nagyobb mit a beérkezett, akkor azokat már csak szürkén jelenítjük meg disabledezve
                $szurkit  = '';
                $disabled = '';
                if($igeny['stat_id']>CIKKIGN_STAT_RESZBENBEERK){
                    $szurkit  = ' class="erkeztet_szurkit"';
                    $disabled = ' readonly';
                }

                $html .= nl2('
                            <tr'.$szurkit.'>
                                <td id="erkeztet_szall_'.$igeny['recid'].'" class="nowrap">'.$telephely['nev'].'</td>
                                <td id="erkeztet_cikkid_'.$igeny['recid'].'">'.$igeny['cikk_id'].'</td>
                                <td class="l" id="erkeztet_cikknev_'.$igeny['recid'].'">'.$igeny['nev'].'</td>
                                <td class="r">'.number_format($igeny['igmen'], 2, ',', ' ').'</td>
                                <td class="r" id="rndmen_'.$igeny['recid'].'">'.number_format($igeny['rndmen'], 2, ',', ' ').'</td>
                                <td class="r" id="marerkmen_'.$igeny['recid'].'">'.number_format($igeny['erkmen'], 2, ',', ' ').'</td>
                                <td class="r"><input type="text" class="f_num erkmen" data-id="'.$igeny['recid'].'" id="erkmen_'.$igeny['recid'].'" value="0,00"'.$disabled.'></td>
                                <td class="c" id="mee_'.$igeny['recid'].'">'.$igeny['cikkmee'].'</td>
                                <td class="c">
                                    <div class="cb">
                                        <input type="checkbox" class="nem_tobb_beerk" data-id="'.$igeny['recid'].'" id="nem_tobb_'.$igeny['recid'].'"'.$disabled.'>
                                        <label for="nem_tobb_'.$igeny['recid'].'"></label>
                                    </div>
                                </td>
                                <td class="c">'.$gomb.'</td>
                            </tr>
                            ');
            }
            $html .= '</table>';
        }


        $szallito = $adatbazis_kapcsolat->s2('nev', 'szallito', "`id`='{$igeny['szallito_id']}'");

        echo json_encode(array(
            'html'      =>  $html,
            'szallito'  => htmlspecialchars_decode($szallito['nev']),
        ));

    }

    // Anyagigénylés érkeztetésének feldolgozása
    if($_POST['idk']){
        // Tranzakció indul
        $db_error = false;
        mysqli_autocommit($db_kapcs, false);

        $adatok = explode('|', $_POST['idk']);
        foreach($adatok as $adat){
            list($id, $erkmen, $nemfogerk) = explode('#', $adat);
            //Ha nincs ID, nem is csinálunk semmit
            if($id){

                $anyagigenyles = $adatbazis_kapcsolat->s2('*', 'anyagigenyles', "`recid`='$id'");
                if($anyagigenyles===false){ $db_error = false; }

                $marerkmen = $anyagigenyles['erkmen'];
                $rndmen    = $anyagigenyles['rndmen'];
                // Ha marerkmen+erkmen=0 ÉS nemfogerk=1 AKKOR statid=CIKKIGN_STAT_NEM_ERKEZETT
                if( ($marerkmen+$erkmen==0 || $marerkmen+$erkmen==0.00) && $nemfogerk){
                    $statusz = addStatusz( 'anyagigenyles_statusz', $id, CIKKIGN_STAT_NEM_ERKEZETT );
                    if( $statusz === false ) { $db_error = true; }

                // Ha marerkmen+erkmen>=rndmen VAGY (marerkmen+erkmen>0 ÉS nemfogerk=1), AKKOR statid=CIKKIGN_STAT_BEERKEZETT
                }else if( $marerkmen+$erkmen>=$rndmen || ($marerkmen+$erkmen>0 && $nemfogerk) ){
                    $statusz = addStatusz( 'anyagigenyles_statusz', $id, CIKKIGN_STAT_BEERKEZETT );
                    if( $statusz === false ) { $db_error = true; }

                // Ha marerkmen+erkmen>0 ÉS marerkmen+erkmen<rndmen ÉS nemfogerk=0 AKKOR statid=CIKKIGN_STAT_RESZBENBEERK
                }elseif( $marerkmen+$erkmen>0 && $marerkmen+$erkmen<$rndmen && !$nemfogerk){
                    $statusz = addStatusz( 'anyagigenyles_statusz', $id, CIKKIGN_STAT_RESZBENBEERK  );
                    if( $statusz === false ) { $db_error = true; }
                }

                // Végül UPDATE cikkign SET erkmen=marerkmen+erkmen, update_usr= WHERE recid=
                $sql = $adatbazis_kapcsolat -> u('anyagigenyles',   array('erkmen',             'update_usr'                            ),
                                                                    array($marerkmen+$erkmen,   $_SESSION[get_db_tabla().'teljes_nev']  ),
                                                    "`recid`='$id'"
                                                );
                if( $sql === false ) { $db_error = true; }

                // Ha erkmen>0 INSERT mozgas                    SET telephely_id=,                           szallito_id=,                cikk_id=,                        cikkmen=erkmen, mozgid=MOZGID_RAKTAR_BE, mozgir=1, rendtetid=recid, update_usr=, insert_usr=, insert_dt=NOW()
                if($erkmen>0){
                    $sql = $adatbazis_kapcsolat->i('mozgas',    array('telephely_id',                 'szallito_id',                 'cikk_id',                  'cikkmen', 'mozgid',          'mozgir', 'rendtetid', 'update_usr',                           'insert_usr',                            'insert_dt' ),
                                                                array($anyagigenyles['telephely_id'], $anyagigenyles['szallito_id'], $anyagigenyles['cikk_id'],  $erkmen,    MOZGID_RAKTAR_BE,  1,        $id,         $_SESSION[get_db_tabla().'teljes_nev'], $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()'     )
                                                    );
                    if( $sql === false ) { $db_error = true; }
                }

            }
        }

        // Ciklus után minden esetben átírjuk cikkrndfej tábla statidet mysql procedúrával: call setcikkrndfejstatid(aparecid,loginuser.nev);
        // WebDeb adatbázisban nem futott le rendesen, itt kiagyjuk
        if($_SERVER['HTTP_HOST']!='demo.webdeb.hu'){
            $sql = mysqli_query($db_kapcs, 'call setcikkrndfejstatid('.$anyagigenyles['aparecid'].', \''.$_SESSION[get_db_tabla().'teljes_nev'].'\')');
            if( $sql === false ) { $db_error = true; }
        }


        //Archiválás egy lépésben törétnik, ha minden anyagigénylés sor státusza > 35
//        if($marerkmen+$erkmen>=$rndmen || $nemfogerk){
//
//            //Ha marerkmen+erkmen=0, statid=CIKKIGN_STAT_NEM_ERKEZETT
//            if($marerkmen+$erkmen==0){
//                $statid = CIKKIGN_STAT_NEM_ERKEZETT;
//            //Ha elutleir nem üres, statid=CIKKIGN_STAT_ELUTASITVA
//            }elseif($anyagigenyles['elutleir']){
//                $statid = CIKKIGN_STAT_ELUTASITVA;
//            //Amúgy: statid=CIKKIGN_STAT_LEZARVA
//            }else{
//                $statid = CIKKIGN_STAT_LEZARVA;
//            }
//
//            // Oszlopok társítása
//            $oszlopok        = array('recid', 'ikt_szam', 'telephely_id',  'cikk_id', 'nev', 'cikkmee', 'igmen', 'igleir', 'jhmen', 'rndmen', 'rndear', 'rndid', 'erkmen', 'erkear', 'erkcimid', 'szlacimid', 'elutleir', 'keszpenz', 'uileir', 'szallito_id', 'oracikkid', 'aparecid', 'kepfajl', 'rnd_dt', 'kulsosnek', 'szlaid', 'rndmenleir', 'vbrutert', 'vnetert', 'kcs_21', 'arfolyam', 'atvaltva_d', 'vrndear', 'verkear');
//            $anyagig_oszlop  = array('recid', 'ikt_szam', 'telephely_id',  'cikk_id', 'nev', 'cikkmee', 'igmen', 'igleir', 'jhmen', 'rndmen', 'rndear', 'rndid', 'erkmen', 'erkear', 'erkcimid', 'szlacimid', 'elutleir', 'keszpenz', 'uileir', 'szallito_id', 'oracikkid', 'aparecid', 'kepfajl', 'rnd_dt', 'kulsosnek', 'szlaid', 'rndmenleir', 'vbrutert', 'vnetert', 'kcs_21', 'arfolyam', 'atvaltva_d', 'vrndear', 'verkear');
//
//            $ertekek  = array();
//            foreach($anyagig_oszlop as $oszlop){
//                array_push($ertekek, $anyagigenyles[$oszlop]);
//            }
//
//            array_push($oszlopok, 'stat_id','update_usr',                             'insert_usr',                           'insert_dt' );
//            array_push($ertekek,  $statid,  $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()'     );
//            $cikkrnd = $adatbazis_kapcsolat->i('cikkrnd', $oszlopok, $ertekek);
//            if($cikkrnd===false){
//                $db_error = true;
//            }
//
//            $torles  = $adatbazis_kapcsolat->d('anyagigenyles',         "`recid`='$id'");
//            $torles2 = $adatbazis_kapcsolat->d('anyagigenyles_statusz', "`anyagigenyles_recid`='$id'");
//            if($torles===false || $torles2===false){
//                $db_error = true;
//            }
//        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);
            echo json_encode(array(
                'statusz' => 'siker'
            ));
        } else {
            mysqli_rollback($db_kapcs);
            echo json_encode(array(
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba'
            ));
        }

    }

    $weblap->leallit();
    exit();
}


// KP-s anyagbeszerzés feldolgozása
if (isset($_POST['anyagigenylesKPBeszerez'])) {

    // Tranzakció indul
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    // Szekvencia táblából kiszámoljuk a rendelés következő sorszámát
    $sorszam = get_iktatoszam_sorszam('cikkrendfej');
    if($sorszam===false){ $db_error = true; }

    // Rendelés ID formázása
    $rndid = 'R'.date('y').'/'.sprintf('%04d', $sorszam);

    // Számoljuk ki a rendelés értékét
    $rndertek = 0;
    $anyagok = json_decode( $_POST['anyagok'], 1 );
    foreach($anyagok as $anyag){
        if($anyag['cikkszam']){
            $anyag['menny'] = str_replace(array(',', ' '), array('.', ''), $anyag['menny']);
            $anyag['ear']   = str_replace(array(',', ' '), array('.', ''), $anyag['ear']);
            $rndertek += ($anyag['menny']*$anyag['ear']);
        }
    }

    // Létrehozunk egy új rendelésfej bejegyzést
    $cikkrendfej_id = $adatbazis_kapcsolat->i('cikkrendfej',    array('keszpenz', 'stat_id',               'rndid', 'rndertek', 'rnd_dt',  'kepfajl',   'szallito_id',         'insert_dt',    'insert_usr',                           'update_usr'                            ),
                                                                array(1,          CIKKIGN_STAT_BEERKEZETT, $rndid,  $rndertek,  'NOW()',   '',          $_POST['szallito_id'], 'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'], $_SESSION[get_db_tabla().'teljes_nev']  )
                                                );
    if( $cikkrendfej_id === false ) { $db_error = true; }

    // Felvesszük az első státuszt
    $sql = $adatbazis_kapcsolat -> i('cikkrendfej_statusz',
                                        array('cikkrendfej_recid',  'stat_id',                'login',                          'insert_dt',    'insert_usr',                              'update_usr'                             ),
                                        array($cikkrendfej_id,      CIKKIGN_STAT_BEERKEZETT,  $_SESSION[get_db_tabla().'user'], 'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],    $_SESSION[get_db_tabla().'teljes_nev']   )
                                    );
    if( $sql === false ) { $db_error = true; }

    // Anyagigénylés sorok
    $anyagok = json_decode( $_POST['anyagok'], 1 );
    foreach($anyagok as $anyag){

        if($anyag['cikkszam']){

            $anyag['menny'] = str_replace(array(',', ' '), array('.', ''), $anyag['menny']);
            $anyag['ear']   = str_replace(array(',', ' '), array('.', ''), $anyag['ear']);

            $ciktorzs = $adatbazis_kapcsolat->s2('id oracikkid nev cikkmee', 'cikktorzs', "`id`='{$anyag['cikkszam']}'");
            $oracikkid = $ciktorzs['oracikkid'];
            $kulsosnek = 0;
            if($anyag['rb']){
                $kulsosnek = 1;
                $oracikkid = 'MÜRBA0002';
            }


            // Anyagigénylés beszúrása (itt nem kell iktatószámot generálni, mert egyből archiválni fogjuk)
            $anyagigenyles = $adatbazis_kapcsolat->i('anyagigenyles', array('rndid',  'szallito_id',         'telephely_id',         'cikk_id',            'nev',            'cikkmee',            'stat_id',             'igleir',         'rndmen',         'igmen',         'jhmen',         'erkmen',        'erkear',      'rndear',      'kulsosnek', 'keszpenz', 'erkcimid', 'rnd_dt', 'kcs_21', 'oracikkid', 'aparecid',      'insert_dt',  'insert_usr',                              'update_usr'                             ),
                                                                      array($rndid,   $_POST['szallito_id'], $_POST['telephely_id'], $anyag['cikkszam'],   $ciktorzs['nev'], $ciktorzs['cikkmee'],  CIKKIGN_STAT_NYITOTT, $anyag['igleir'], $anyag['menny'],  $anyag['menny'], $anyag['menny'], $anyag['menny'], $anyag['ear'], $anyag['ear'], $kulsosnek,  1,          1,          'NOW()',   5,       $oracikkid,  $cikkrendfej_id, 'NOW()',      $_SESSION[get_db_tabla().'teljes_nev'],    $_SESSION[get_db_tabla().'teljes_nev']   )
                                                    );
            if( $anyagigenyles === false ) { $db_error = true; }

            $sql = $adatbazis_kapcsolat -> i('anyagigenyles_statusz',
                                                array('anyagigenyles_recid',    'stat_id',            'login',                           'insert_dt',    'insert_usr',                              'update_usr'                             ),
                                                array($anyagigenyles,           CIKKIGN_STAT_NYITOTT, $_SESSION[get_db_tabla().'user'],  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],    $_SESSION[get_db_tabla().'teljes_nev']   )
                                            );
            if( $sql === false ) { $db_error = true; }

            if(addStatusz('anyagigenyles_statusz', $anyagigenyles, CIKKIGN_STAT_ARAZVA      )===false){ $db_error = true; }
            if(addStatusz('anyagigenyles_statusz', $anyagigenyles, CIKKIGN_STAT_JOVAHAGYVA  )===false){ $db_error = true; }
            if(addStatusz('anyagigenyles_statusz', $anyagigenyles, CIKKIGN_STAT_MEGRENDELVE )===false){ $db_error = true; }
            if(addStatusz('anyagigenyles_statusz', $anyagigenyles, CIKKIGN_STAT_BEERKEZETT  )===false){ $db_error = true; }

            $sql = $adatbazis_kapcsolat -> i('mozgas',
                                                array('telephely_id',           'szallito_id',        'cikk_id',            'cikkmen',       'mozgid',         'mozgir', 'rendtetid',     'insert_dt',  'insert_usr',                              'update_usr'                             ),
                                                array($_POST['telephely_id'],   $_POST['szallito_id'], $anyag['cikkszam'],  $anyag['menny'], MOZGID_RAKTAR_BE, 1,        $anyagigenyles,  'NOW()',      $_SESSION[get_db_tabla().'teljes_nev'],    $_SESSION[get_db_tabla().'teljes_nev']   )
                                            );
            if( $sql === false ) { $db_error = true; }
        }

    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'siker',
            'alert'   => 'Az Ön által feldolgozott számla rendelésszáma:<br>'.$rndid.'<br>Kérem, írja rá a számlára!',
            'gomb'    => '<input type="button" class="gomb" value="Ok" onclick="szurkit(); location.href=\'anyagigenyles.php?anyagrend&amp;id='.$anyagigenyles.'&amp;modosit\'">',
            'title'   => 'Üzenet',
        ));
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba'
        ));
    }

    $weblap->leallit();
    exit();
}

// Anyagigénylés táblázat rendezésének letárolása SESSION-be
if (isset($_POST['anyagigenyles_tablazat_rendez'])) {

    $mentett_adat = $adatbazis_kapcsolat->s2('recid tabla_rendezes', 'login', "`recid` = '".$_SESSION[get_db_tabla().'login_recid']."'", '', '1');

    if( $mentett_adat ) {
        $rendezes = array(
            'anyagigenyles_tablazat_rendez' => $_POST['oszlop'],
            'anyagigenyles_tablazat_irany'  => $_POST['irany']
        );

        if( !$mentett_adat['tabla_rendezes'] ) {
            $adatbazis_kapcsolat -> u('login', array('tabla_rendezes'), array(json_encode($rendezes)), "`recid` = '".$_SESSION[get_db_tabla().'login_recid']."'");
        } else {
            $mentett_rendezes = json_decode(htmlspecialchars_decode($mentett_adat['tabla_rendezes']), true);

            $mentett_rendezes['anyagigenyles_tablazat_rendez'] = $_POST['oszlop'];
            $mentett_rendezes['anyagigenyles_tablazat_irany']  = $_POST['irany'];

            $adatbazis_kapcsolat -> u('login', array('tabla_rendezes'), array(json_encode($mentett_rendezes)), "`recid` = '".$_SESSION[get_db_tabla().'login_recid']."'");
        }
    }

    // Nem biztos, hogy küldönk oszlop adatot, lehet csak az irány változik
    if($_POST['oszlop']){
        $_SESSION['anyagigenyles_tablazat_rendez'] = $_POST['oszlop'];
    }
    if($_POST['irany']){
        $_SESSION['anyagigenyles_tablazat_irany']  = $_POST['irany'];
    }

    $weblap->leallit();
    exit();
}

// Anyagigénylés sztornózása
if (isset($_POST['anyagigenylesSztornoz'])) {
    
    $memo  = $_POST['memo'];
    $recid = $_POST['recid'];
    
    // Tranzakció indul
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('stat_id rndmen rndear szallito_id', 'anyagigenyles', "`recid`='{$recid}'");
    if( $sor === false ) { $db_error = true; }

    // Státusz ellenőrzése
    $sztornos_statuszok = array(CIKKIGN_STAT_NYITOTT, CIKKIGN_STAT_ARAZVA, CIKKIGN_STAT_VARAKOZO);
    if( !in_array($sor['stat_id'], $sztornos_statuszok) ){
        echo json_encode(array(
            'title' => 'Hiba',
            'hiba'  => 'Ebben a státuszban nem lehet anyagigénylést sztornózni!'
        ));

        $weblap->leallit();
        exit();
    }

    // Jog ellenőrzése
    if( !_jog(JOG_ANYAGREND_KESZIT) ) {
        echo json_encode(array(
            'hiba'  => 'Nincs joga anyagigénylést sztornózni!',
            'title' => 'Hiba'
        ));
        $weblap->leallit();
        exit();
    }
    
    // Memó ellenőrzése
    if(!$memo){
        echo json_encode(array(
            'hiba'  => 'Kérjük indokolja a sztornózást!',
            'title' => 'Hiba'
        ));
        $weblap->leallit();
        exit();
    }else{
        $memo = 'Sztornó indoklása:'."\r\n".$memo;
    }
    
    // Létrehozunk egy új rendelésfej bejegyzést
    $sorszam = get_iktatoszam_sorszam('cikkrendfej');
    if( $sorszam === false ) { $db_error = true; }
    $rndid = 'R'.date('y').'/'.sprintf('%04d', $sorszam);
    $rndertek = ($sor['rndmen']*$sor['rndear']);
        
    $cikkrendfej_id = $adatbazis_kapcsolat->i('cikkrendfej',    array('stat_id',           'rndid', 'rnd_dt', 'rndertek',  'kepfajl',   'szallito_id',        'insert_dt',    'insert_usr',                           'update_usr'                            ),
                                                                array(CIKKIGN_STAT_TOROLT, $rndid,  'NOW()',  $rndertek,   '',          $sor['szallito_id'],  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'], $_SESSION[get_db_tabla().'teljes_nev']  )
                                                );
    if( $cikkrendfej_id === false ) { $db_error = true; }

    // Felvesszük az első státuszt
    $sql = $adatbazis_kapcsolat -> i('cikkrendfej_statusz',
                                        array('cikkrendfej_recid',  'stat_id',              'login',                          'insert_dt',    'insert_usr',                              'update_usr'                          ),
                                        array($cikkrendfej_id,      CIKKIGN_STAT_TOROLT,    $_SESSION[get_db_tabla().'user'], 'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],    $_SESSION[get_db_tabla().'teljes_nev'] )
                                    );
    if( $sql === false ) { $db_error = true; }
    
    // Anyagigénylés státusz léptetés
    if( addStatusz('anyagigenyles_statusz', $recid, CIKKIGN_STAT_TOROLT, '', $memo) === false ){
        $db_error = true;
    }
    // Illetve update
    $adatbazis_kapcsolat->u('anyagigenyles',    array('rndid', 'rnd_dt', 'kepfajl', 'update_usr',                           'aparecid'       ),
                                                array($rndid,  'NOW()',  '',        $_SESSION[get_db_tabla().'teljes_nev'], $cikkrendfej_id  ),
                            "`recid`='$recid'");
    
    // Értesítés küldése az anyagigénylőnek
    $hiba = level_felkeszit_es_kuld('anyagigenyles', $recid, '', 'anyagigenylesSztorno', 'anyagigenylo');
    if( $sor === false ) { $db_error = true; }
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'siker',
        ));
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba'
        ));
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

        if( $_SESSION['nyomtatas_ids'] ) {
            if( strstr($_SESSION['akt_sql'], 'WHERE') ) {
                $lekerdezes_fromtol = str_replace('WHERE', "WHERE `".get_db_tabla()."anyagigenyles`.`recid` IN (".$_SESSION['nyomtatas_ids'].") AND ", $lekerdezes_fromtol);
            } else {
                $lekerdezes_fromtol = str_replace('ORDER', "WHERE `".get_db_tabla()."anyagigenyles`.`recid` IN (".$_SESSION['nyomtatas_ids'].") ORDER", $lekerdezes_fromtol);
            }
        }

        // De azért indulunk ki az eredeti SQL-ből (és nem kezdjük a cikkrndfej táblát bejárni) mert így lesznek a kívánt rekordok az exportban is.
        if(isset($_GET['anyagrend'])){
            $sql = 'SELECT
                    `'.get_db_tabla().'anyagigenyles`.`rndid` as \'Rendelés szám\',
                    `'.get_db_tabla().'anyagigenyles`.`ikt_szam` as \'Anyagigénylés szám\',
                    `'.get_db_tabla().'anyagigenyles`.`cikk_id` as \'Cikk szám\',
                    `'.get_db_tabla().'anyagigenyles`.`nev` as \'Cikk név\',
                    `'.get_db_tabla().'anyagigenyles`.`stat_id` as \'Státusz\'
                    '.$lekerdezes_fromtol;

            $query      = mysqli_query($db_kapcs, $sql);

            $csvhead    = array();
            while($fejlec_nevek = mysqli_fetch_field($query)){
                $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
            }
            fputcsv($open, $csvhead, ';');
            while( $sorok = mysqli_fetch_assoc($query) ){

                // rndid alapján annyi sora lesz egy rendelés fejnek, ahány igénylés ahhoz tartozik
                $rendeles = $adatbazis_kapcsolat->s2('recid', 'cikkrendfej', "`rndid`='{$sorok['Rendelés szám']}'");
                $anyagigenylesek = $adatbazis_kapcsolat->s('rndid ikt_szam cikk_id nev stat_id', $megnevezes, "`rndid`='{$sorok['Rendelés szám']}'", 'insert_dt desc');
                foreach($anyagigenylesek as $anyagigenyles){

                    $csvcont = array();

                    // Itt legyünk összhangban a fejléccel
                    $csvcont[] = iconv2($mibol, $mibe, strip_tags( htmlspecialchars_decode( $anyagigenyles['rndid'] ) ) );
                    $csvcont[] = iconv2($mibol, $mibe, strip_tags( htmlspecialchars_decode( $anyagigenyles['ikt_szam'] ) ) );
                    $csvcont[] = (string)iconv2($mibol, $mibe, strip_tags( htmlspecialchars_decode( $anyagigenyles['cikk_id'] ) ) );
                    $csvcont[] = iconv2($mibol, $mibe, strip_tags( htmlspecialchars_decode( $anyagigenyles['nev'] ) ) );
                    $csvcont[] = iconv2($mibol, $mibe, strip_tags( htmlspecialchars_decode( getStatText('anyagigenyles', $anyagigenyles['stat_id']) ) ) );
                    fputcsv($open, $csvcont, ';');

                }
            }

        // Anyagigénylés
        }else{

            $sql = 'SELECT
                    `'.get_db_tabla().'anyagigenyles`.`stat_id` as \'Státusz\',
                    `'.get_db_tabla().'anyagigenyles`.`ikt_szam` as \'Anyagigénylés szám\',
                    `'.get_db_tabla().'anyagigenyles`.`telephely_id` as \'Szálloda\',
                    `'.get_db_tabla().'anyagigenyles`.`cikk_id` as \'Cikk szám\',
                    `'.get_db_tabla().'anyagigenyles`.`nev` as \'Cikk név\',
                    `'.get_db_tabla().'anyagigenyles`.`igleir` as \'Leírás\',
                    `'.get_db_tabla().'anyagigenyles`.`szallito_id` as \'Szállító\',
                    `'.get_db_tabla().'anyagigenyles`.`igmen` as \'Igényelt mennyiség\',
                    `'.get_db_tabla().'anyagigenyles`.`cikkmee` as \'Mee\',
                    `'.get_db_tabla().'anyagigenyles`.`insert_dt` as \'Igénylés dátuma\',
                    `'.get_db_tabla().'anyagigenyles`.`jhmen` as \'Jóváhagyott mennyiség\',
                    `'.get_db_tabla().'anyagigenyles`.`rndmen` as \'Rendelt mennyiség\',
                    `'.get_db_tabla().'anyagigenyles`.`rndear` as \'Rendelési egységár ('.get_penznem().')\',
                    `'.get_db_tabla().'anyagigenyles`.`vrndear` as \'Deviza rend. egységár\',
                    `'.get_db_tabla().'anyagigenyles`.`kcs_21` as \'Pénznem\',
                    `'.get_db_tabla().'anyagigenyles`.`kulsosnek` as \'RB-s anyag\'
                    '.$lekerdezes_fromtol;

            $query      = mysqli_query($db_kapcs, $sql);

            $csvhead    = array();
            while($fejlec_nevek = mysqli_fetch_field($query)){
                $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
            }
            fputcsv($open, $csvhead, ';');

            while( $sorok = mysqli_fetch_assoc($query) ){
                $csvcont = array();

                // Dátum formázása
                $sorok['insert_dt'] = str_replace('-', '.', $sorok['insert_dt']);
                // Számok formázása
                $szamok = array('Igényelt mennyiség', 'Jóváhagyott mennyiség', 'Rendelt mennyiség', 'Rendelési egységár ('.get_penznem().')', 'Deviza rend. egységár');
                foreach($szamok as $szam){
                    $sorok[$szam] = str_replace('.', ',', $sorok[$szam]);
                }

                foreach( $sorok as $kulcs => $sor ) {

                    if($kulcs=='Pénznem'){
                        $sor = erteklista(ERTEKLISTA_PENZNEM, $sor);
                    }
                    if($kulcs=='Szállító'){
                        $sor = getErtek('nev', 'szallito', 'id', $sor);
                    }
                    if($kulcs=='Szálloda'){
                        $sor = getErtek('nev', 'telephely', 'id', $sor);
                    }
                    if($kulcs=='Státusz'){
                        $sor = getStatText('anyagigenyles', $sor);
                    }
                    if($kulcs=='RB-s anyag'){
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
    }
    fclose($open);

    $megnevezes_csv = $megnevezes;
    if(isset($_GET['anyagrend'])){
        $megnevezes_csv = 'anyagrendeles';
    }

    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=$megnevezes_csv.csv");
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
require_once('selectek.php');

    if( !$_GET['ajax'] ) {
        $class = '';
        if( isset($_GET['tablazat']) ) {
            $class = ' class="nopadd"';
        }

        echo '<div id="szamlaBetolt"'.$class.' data-recid="'.$_GET['id'].'" data-page="'.str_replace('.php', '', basename(self())).'">';
    }

    // Az adatlap adatait már a legelején lekérjük
    if( $_GET['id'] ){
        $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");
    }
    if($sor['dok_db']){
        $dok_db  = ' ('.$sor['dok_db'].')';
    }
    if($sor['megj_db']){
        $megj_db = ' ('.$sor['megj_db'].')';
    }

    if( isset($_GET['uj']) || isset($_GET['tablazat']) || isset($_GET['modosit']) || isset($_GET['tortenet']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) || isset($_GET['tetel']) ) {
        // Számla fülek
        $ful_tomb = array(
            'tablazat'      => 'Anyagigénylés',
            'modosit'       => 'Adatlap',
            'tortenet'      => 'Történet',
            'dokumentumok'  => 'Dokumentumok'.$dok_db,
            'megjegyzesek'  => 'Megjegyzések'.$megj_db,
        );

        $kivetelek = array('ajax', 'uj');
        foreach($ful_tomb as $key => $value){
            array_push($kivetelek, $key);
        }

        $fulek = '';
        foreach($ful_tomb as $key => $value) {

            // Anyagrendelés alatt Anyagignylés fül nem kell Anyagrend mod: 2018.04
            // if( !(isset($_GET['anyagrend']) && $key=='tablazat') ){

                if( $key=='tablazat' && isset($_GET['anyagrend']) ) {
                    $value = 'Anyagrendelés';
                }

                $active = 'active';
                if( isset($_GET[$key]) ) {
                    $active = ' class="active"';
                }

                $id_betolteshez = $_GET['id'];
                if(!$_GET['id']){
                    $id_betolteshez = $_SESSION['legutobbi_id'];
                }

                // Hogy JS alatt is biztosan lássuk ezt a get-et
                if(isset($_GET['anyagrend'])){
                    $_GET['anyagrend'] = 1;
                }
                if($key=='tablazat'){
                    $fulek .= '<a href="'.self().get_get($kivetelek).'&amp;tablazat"'.$active.' data-oldal="'.$key.'">'.$value.'</a>';
                }else{
                    $fulek .= '<a href="javascript: szamlaBetolt(\''.$id_betolteshez.'\', \''.basename(self()).'\', \''.$key.'\', \'\', \'\', \'Mindenképp tölsön be\'); void(0);"'.$active.' data-oldal="'.$key.'">'.$value.'</a>';
                }

            // }
        }

        $szamla_html .= str_replace(
            array('{FULEK}'),
            array( $fulek  ),
            fajlbol_olvas('../sablon/admin/szamla_fulek.html', true)
        );
    }

    // Gombok kirajzolása minden oldalra
    $gombok = '';
    if( isset($_GET['uj']) ) {

        // Újat csak "Anyagigénylés kezelése" joggal lehet
        if(_jog(JOG_ANYAGIGENYLES_KEZEL)) { // $(\'#szerk_form\').submit()
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Anyagigénylés" onclick="szurkit(\'mehet_megsem_rejt\'); anyagigenylesekMentes();">
                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="szurkit(\'mehet_megsem_rejt\'); formModositva=false; location.reload();">
                </div>
            ');

            if(isset($_GET['anyagrend'])){
                $muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 16).'px);"';
                if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
                    if( $_COOKIE['csuszkaSzelesseg'] ) {
                        $muv_style = ' style="width: calc(100% - '.(($_COOKIE['csuszkaSzelesseg'] + 16)/10).'rem);"';
                    }
                }
            }

            // Műveletek box
            $gombok = nl2('
                <div class="muveletek"'.$muv_style.'>
                    '.$gombok.'
                </div>
            ');
        }

    // Módosítás gombok
    } else {

        // Js, a törlés gombhoz
	    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';

        // Anyagrendelés esetén rendelés kép gomb
        if( isset($_GET['anyagrend']) && _jog(JOG_ANYAGREND_MEGTEKINT) ){
            $utvonal = '../'.get_utvonal().$sor['kepfajl'];
            $onclick = 'window.open(\''.$utvonal.'\')';
            if ( !is_file($utvonal) ) {
                $onclick = 'alert2(\'Nem található a következő fájl:<br>'.$sor['kepfajl'].'\')';
            }
            $gombok .= '<input type="button" class="gomb" value="Rendelés kép" onclick="'.$onclick.'"> ';
        }

        // Anyagigénylés jóváhagyása
        if( _jog(JOG_ANYAGIGENYLES_JOVAHAGY) ) {

            if($sor['stat_id']==CIKKIGN_STAT_ARAZVA){
                $gombok .= '<input type="button" class="gomb zold" value="Jóváhagyás" onclick="anyagigenyles_jovahagy(\'csak kérdezünk még\')"> ';
                $gombok .= '<input type="button" class="gomb piros" value="Elutasítás" onclick="anyagigenyles_elutasitas(\'csak kérdezünk még\')"> ';
                $gombok .= '<input type="button" class="gomb" value="Mind jóváhagyása" onclick="anyagigenyles_mind_jovahagy(\'csak kérdezünk még\')"> ';
            }

            // Ha jóváhagyás joga van, és a státusza árazva, akkor a jóváhagyott mennyiség enabled és értéke a rendelt mennyiség
            if($sor['stat_id']==CIKKIGN_STAT_ARAZVA){
                $gombok .= nl2('
                    <script>
                        $(document).ready(function() {
                            $(\'#jhmen\').val($(\'#rndmen\').val());
                            jovahagy_kiszamol();
                            $(document).on(\'keyup\', \'#jhmen\', function() {
                                jovahagy_kiszamol();
                            });
                        });
                        function jovahagy_kiszamol(){
                            if( $(\'#jhmen\').length && $(\'#rndear\').length ){
                                jhmen  = $(\'#jhmen\') .val().replace(\',\', \'.\').replace(/ /g,\'\');
                                rndear = $(\'#rndear\').val().replace(\',\', \'.\').replace(/ /g,\'\');
                                $(\'#jovahagy_ertek\').html(\'<div style="font-size: 1.7rem; margin-top: 0.5rem">Jóváhagyásra váró érték: \'+ezresek(jhmen*rndear, 0, \',\', \' \')+\' '.get_penznem().'</div>\' );
                            }
                        }
                    </script>
                    ');
            }
        }

        // Anyagigénylés készít jög
        if( _jog(JOG_ANYAGREND_KESZIT) ) {

            if(!isset($_GET['anyagrend'])){

                $arazas        = '';
                $kuld_jovahagy = '';
                if($sor['stat_id']==CIKKIGN_STAT_NYITOTT){
                    $arazas        = '<input type="button" class="gomb foGomb" value="Árazás" onclick="arazas()"> ';
                    $kuld_jovahagy = '<input type="button" class="gomb foGomb" value="Küldés jóváhagyásra" onclick="anyagigenyles_kuldes_jovahagyasra(\'csak kérdezünk még\')"> ';
                }
                $rend_keszit = '';
                if($sor['stat_id']==CIKKIGN_STAT_JOVAHAGYVA){
                    $rend_keszit = '<input type="button" class="gomb foGomb" value="Rendelés készítése" onclick="anyagigenyles_rendeles_keszit(\'csak kérdezünk még\')"> ';
                }

                $gombok .= nl2('
                            <div class="kijeloltek_muv">
                            ');

                // Elutasított státusz alatt nincs Leírás hozzáadása gomb
                if( $sor['stat_id']!=CIKKIGN_STAT_ELUTASITVA ){
                    $gombok .= '<input type="button" class="gomb" value="Leírás hozzáadása" title="Leírás hozzáadása" onclick="csoportMuvelet(\'anyagigenylesLeirasHozzaadas\')"> ';
                }

                $gombok .= nl2('
                        <input type="button" class="gomb foGomb" value="Várólista..." title="Várólista...">
                        <div class="muv">
                            <ul>
                                <li><a onclick="csoportMuvelet(\'anyagigenylesVarolistahozAdas\')">Várólistához adás</a></li>
                                <li><a onclick="varolistabolKivetel(\'csak kérdezünk még\');">Várólistából kivétel</a></li>
                            </ul>
                        </div>
                    </div>
                    '.$arazas.'
                    '.$kuld_jovahagy.'
                    '.$rend_keszit.'
                ');
                
                // Sztornó gomb
                $sztornos_statuszok = array(CIKKIGN_STAT_NYITOTT, CIKKIGN_STAT_ARAZVA, CIKKIGN_STAT_VARAKOZO);
                if( in_array($sor['stat_id'], $sztornos_statuszok) ){
                    $gombok .= '<input type="button" class="gomb piros" value="Sztornó" onclick="csoportMuvelet(\'anyagigenylesSztornoz\')"> ';
                }

            // Anyagrendelés alatt csak a Leírás hozzáadása gomb látszik
            }else{

                if( $sor['stat_id']!=CIKKIGN_STAT_ELUTASITVA ){
                    $gombok .= nl2('
                                    <div class="kijeloltek_muv">
                                ');
                    // Elutasított státusz alatt nincs Leírás hozzáadása gomb
                    if( $sor['stat_id']!=CIKKIGN_STAT_ELUTASITVA ){
                        $gombok .= '<input type="button" class="gomb" value="Leírás hozzáadása" title="Leírás hozzáadása" onclick="csoportMuvelet(\'anyagigenylesLeirasHozzaadas\')"> ';
                    }
                    $gombok .= nl2('
                                    </div>
                                ');
                }
            }
        }

        // Anyagigénylés érkeztetése
        if( _jog(JOG_ANYAGIGENYLES_KEZEL) ) {
            if(isset($_GET['anyagrend'])){
                $gombok .= nl2('
                    <input type="button" class="gomb foGomb" value="Anyagok érkeztetése" onclick="csoportMuvelet(\'anyagigenyles_erkeztet\')">
                    <input type="button" class="gomb foGomb" value="Készpénzes anyagbeszerzés" onclick="csoportMuvelet(\'anyagigenylesKpBeszerez\');">
                ');
            }
        }

        // Anyagrendeléskor anyagrendelés törlés jog kell hozzá
        if (isset($_GET['anyagrend']) ) {
            if(!_jog(JOG_ANYAGREND_TOROL)){
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Önnek nincs joga törléshez!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
            }else{
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt az anyagrendelést?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
            }

        // Anyagigénylénél pedig kezelés jog
        }else{
            if(!_jog(JOG_ANYAGIGENYLES_KEZEL)){
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Önnek nincs joga törléshez!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
            }else{
                $gombok .= nl2('
                                <script>
                                    function torles_fgv(){
                                        $.ajax({
                                            url : \'anyagigenyles.php\',
                                            type: \'POST\',
                                            data: {
                                                cikk_id         : \''.$sor['cikk_id'].'\',
                                                telephely_id    : \''.$sor['telephely_id'].'\',
                                                varnak_e_anyagra: Date.now()
                                            },
                                            success: function (response) {
                                                if(response.substring(0, 6)==\'SESKIL\'){ location.href = \'index.php\'; }
                                                if (response) {
                                                    response = JSON.parse(response);
                                                    alert2(response.gomb+\'Biztosan törli ezt az anyagigénylést?\', gomb'.$_GET['id'].');
                                                }
                                            }
                                        });

                                    }
                                </script>
                                <input type="button" id="recordTorol" class="gombb_alert dn" onclick="torles_fgv()" value="Töröl" title="Töröl">
                                ');
            }
        }

        if( _jog(JOG_ANYAGIGENYLES_KEZEL) ){
            $hianyzo_anyagok = hianyzo_anyagok($sor['cikk_id'], $sor['telephely_id']);
            $hianyzo_anyagok = implode(',', $hianyzo_anyagok);
            if($hianyzo_anyagok){
                $onclick = 'window.open(\'munkalap.php?recidk='.$hianyzo_anyagok.'\')';
            }else{
                $onclick = 'alert2(\''.getErtek('nev', 'telephely', 'id', $sor['telephely_id']).'<br>'.$sor['cikk_id'].' - '.$sor['nev'].'<br>Erre az anyagra nem várnak munkalapok!\', \'\', \'Hiba\');';
            }
            $gombok .= '<input type="button" class="gomb" value="Anyagra váró munkalapok" onclick="'.$onclick.'">';
        }

        // Rendelés kép gomb
        $utvonal = '../'.get_utvonal().$megnevezes.'/'.$sor['kepfajl'];
        if(is_file($utvonal)){
            $gombok .= ' <input type="button" class="gomb foGomb" value="Rendelés kép" onclick="window.open(\''.$utvonal.'\')">';
        }

        /* Anyagigénylés nem szerkeszthető egyáltalán
        if( _jog(JOG_ANYAGIGENYLES_KEZEL) ){

            // Csak saját bejelentéseit szerkesztheti
            $nem_szerkesztheti = false;
            if($sor['loginnev']!=$_SESSION[get_db_tabla().'user']){
                $nem_szerkesztheti = true;
            }

            if(!$nem_szerkesztheti && isset($_GET['modosit'])){
                $gombok = nl2('
                    <div class="form_kuldes">
                        <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Anyagigénylés" onclick="szurkit(\'mehet_megsem_rejt\'); $(\'#szerk_form\').submit()">
                        <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="szurkit(\'mehet_megsem_rejt\'); formModositva=false; location.reload();">
                    </div>
                ').$gombok;
            }
        }*/

        if(isset($_GET['anyagrend'])){
            // $muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 16).'px);"';
            // if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
            //     if( $_COOKIE['csuszkaSzelesseg'] ) {
            //         $muv_style = ' style="width: calc(100% - '.(($_COOKIE['csuszkaSzelesseg'] + 16)/10).'rem);"';
            //     }
            // }
        }

        // Műveletek box
        $gombok = nl2('
            <div class="muveletek"'.$muv_style.'>
                '.$gombok.'
            </div>
        ');
    }

    $js_submit_elott = 'return form_ell_admin();';

	// Új termék felvitele
	if(isset($_GET['uj']) && _jog(JOG_ANYAGIGENYLES_KEZEL)){

        $akt_tomb = $anyagigenyles_tomb_feltolt;

        $navi_sablon  = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit     = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel   = array( $navi,             '',         $gyk2 );
        $szamla_html .= str_replace($navi_mit, $navi_mivel, $navi_sablon);

	    // HIBAüZENETEK
	    require_once('info.php');

	    // FORM
		if(isset($_GET['ujkent'])){
			foreach($akt_tomb as $ertek){
				$sor[$ertek['name']]=$_SESSION[$ertek['name']];
			}
		}
	    $js = js_kiir($akt_tomb, 'form_ell_admin()');

        // Ckeditort csak akkor húzzuk be, ha kell
        if(is_file($ckeditor_js)){
            $ckeditor = '<script src="'.$ckeditor_js.'"></script>';
        }

	    $form_sablon = fajlbol_olvas('../sablon/admin/form_uj.html', true);

        // Javascipt-es ellenőrzéshez
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
            if( strpos($tomb['class'], 'f_num') !== false ) {
                $sor[$tomb['name']] = str_replace('.', '.', $sor[$tomb['name']]);
            }
        }

        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($sor).'\'); </script>';

		// Ez elkészíti a $mezok változót
		require('switch.php');

	    $form_mit     = array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{CK_EDITOR}', '{GOMBOK}', '{MUV_STYLE}'   );
	    $form_mivel   = array($js,    $js_submit_elott,    self(),     $mezok,    $megnevezes,    $ckeditor,     $gombok,    $muv_style      );
	    $szamla_html .= str_replace($form_mit, $form_mivel, $form_sablon);

        // Igénylésre váró anyagok a munkalapokon:
        // A beloginolt user szállodáit érintő munkalapokat szedjük össze
        $szallodak = get_akt_login_szallodai();
        if($szallodak){

            // Vegyük a loginolt user azon munkalapjait, amik anyagra vár státuszban vannak
            $munkalapok = $adatbazis_kapcsolat->s('recid', 'munkalap', "`telephely_id` IN (".implode(',', $szallodak).") AND `stat_id`='".MUNKALAP_STAT_ANYAGRAVAR."'");
            $munkalapok = adatbazis2tomb($munkalapok, 'recid');
            if($munkalapok){

                $anyagok_html = '';
                $anyag_sql = " AND `szuk_men`>0 AND `stat_id`='".DISZPNAG_STAT_ANYAGRA_VAR."'";
                // Szállodánként járjuk be, és azon belül az egyes anyagokat összegezzük
                $munkalapok = implode(',', $munkalapok);
                $telephelyek = $adatbazis_kapcsolat->s('distinct telephely_id', 'munkalap_anyag', "`munkalap_id` IN (".$munkalapok.") AND `stat_id`!='".DISZPNAG_STAT_VAN_ANYAG."'".$anyag_sql);

                foreach($telephelyek as $telephely){

                    // Azon belül anyagonként járjuk be
                    $anyagok = $adatbazis_kapcsolat->s('distinct cikk_id', 'munkalap_anyag', "`munkalap_id` IN (".$munkalapok.") AND `stat_id`!='".DISZPNAG_STAT_VAN_ANYAG."' AND `telephely_id`='{$telephely['telephely_id']}'".$anyag_sql);
                    foreach($anyagok as $anyag){

                        $anyag = $adatbazis_kapcsolat->s2('recid id', 'cikktorzs', "`id`='{$anyag['cikk_id']}'");
                        $anyag['cikk_id'] = $anyag['id'];

                        $recid_egyedi_azon = $telephely['telephely_id'].'_'.$anyag['cikk_id'];

                        // A mutat linkbe csak azokat a munkalapokID-kat kell fevenni, ami adott anyag/státusz/telephelyhez tartozik
                        $akt_munkalapok = $adatbazis_kapcsolat->s('distinct munkalap_id', 'munkalap_anyag', "`cikk_id`='{$anyag['cikk_id']}' AND `telephely_id`='{$telephely['telephely_id']}' AND `stat_id`='".DISZPNAG_STAT_ANYAGRA_VAR."'".$anyag_sql);
                        $akt_munkalapok = adatbazis2tomb($akt_munkalapok, 'munkalap_id');
                        $akt_munkalapok = implode(',', $akt_munkalapok);

                        // Igénylés alatti darabszám kiszámítása
                        $ig_var = 0;
                        $akt_anyagok = $adatbazis_kapcsolat->s('recid szuk_men', 'munkalap_anyag', "`munkalap_id` IN (".$munkalapok.") AND `cikk_id`='{$anyag['cikk_id']}' AND `telephely_id`='{$telephely['telephely_id']}' AND `stat_id`='".DISZPNAG_STAT_ANYAGRA_VAR."'".$anyag_sql);
                        foreach($akt_anyagok as $akt_anyag){
                            $ig_var += $akt_anyag['szuk_men'];
                        }

                        // Anyagra váró darabszám kiszámítása (ugyanaz, csak másik státuszt figyelünk)
                        $anyag_var = 0;
                        $akt_anyagok = $adatbazis_kapcsolat->s('szuk_men', 'munkalap_anyag', "`munkalap_id` IN (".$munkalapok.") AND `cikk_id`='{$anyag['cikk_id']}' AND `telephely_id`='{$telephely['telephely_id']}' AND `stat_id`='".DISZPNAG_STAT_ANYAG_IGENYELVE."'".$anyag_sql);
                        foreach($akt_anyagok as $akt_anyag){
                            $anyag_var += $akt_anyag['szuk_men'];
                        }

                        //A cikktörzs rec_id-jáéra van szükségünk a későbbiekben. ID alapján megkeressük
                        $cikkid_origi = $cikk_id;
                        $cikk_id = $adatbazis_kapcsolat->s('recid', 'cikktorzs', "`id`='{$cikkid_origi}'");
                        $cikk_id = $cikk_id[0]['recid'];

                        // Rendelés folyamatban kiszámítása: az összes olyan anyagigénylés, ahol adott anyag cikkszámmal dolgozunk és a státusza nem "törölt" és nem "lezárt"
                        $folyamatban = 0;
                        $folyamatbanok = $adatbazis_kapcsolat->s('stat_id igmen rndmen erkmen', 'anyagigenyles', "`cikk_id`='{$anyag['cikk_id']}' AND `telephely_id`='{$telephely['telephely_id']}' AND `stat_id` IN ('".CIKKIGN_STAT_NYITOTT."', '".CIKKIGN_STAT_ARAZVA."', '".CIKKIGN_STAT_JOVAHAGYVA."', '".CIKKIGN_STAT_MEGRENDELVE."', '".CIKKIGN_STAT_RESZBENBEERK."') ");
                        foreach($folyamatbanok as $foly){
                            if( $foly['stat_id']==CIKKIGN_STAT_NYITOTT ){
                                $folyamatban += $foly['igmen'];
                            }elseif(in_array($foly['stat_id'], array(CIKKIGN_STAT_ARAZVA, CIKKIGN_STAT_JOVAHAGYVA, CIKKIGN_STAT_MEGRENDELVE) ) ){
                                $folyamatban += $foly['rndmen'];
                            }elseif( $foly['stat_id']==CIKKIGN_STAT_RESZBENBEERK ){
                                $folyamatban += ($foly['rndmen']-$foly['erkmen']);
                            }
                        }

                        $szalloda = $adatbazis_kapcsolat->s2('nev', 'telephely', "`id`='{$telephely['telephely_id']}'");

                        $mar_igenyelve = '(nincs igénylés)';
                        if($folyamatban){
                            $mar_igenyelve = '<input type="button" value="Már igényelve" class="gomb" onclick="anyagigenyles_mar_igenyelve(\''.$anyag['cikk_id'].'\', \''.$telephely['telephely_id'].'\', \''.number_format($ig_var, 2, ',', ' ').'\', \''.number_format($anyag_var, 2, ',', ' ').'\', \'csak kérezünk\')">';
                        }

                        // Kiíratáshoz
                        $akt_anyag = $adatbazis_kapcsolat->s2('cikk_nev cikk_id szuk_men cikk_mee igleir', 'munkalap_anyag', "`munkalap_id` IN (".$munkalapok.") AND `telephely_id`='{$telephely['telephely_id']}' AND `cikk_id`='{$anyag['cikk_id']}'");

                        $anyagok_html .= nl2('
                            <tr id="sor_'.$anyag['cikk_id'].'">
                                <td>'.$szalloda['nev'].'</td>
                                <td id="cikk_id_'.$recid_egyedi_azon.'">'.$akt_anyag['cikk_id'].'</td>
                                <td id="cikk_nev_'.$recid_egyedi_azon.'" class="l">'.$akt_anyag['cikk_nev'].'</td>
                                <td class="r piros" id="th'.$telephely['telephely_id'].'a'.$anyag['cikk_id'].'">'.number_format($ig_var, 2, ',', ' ').'</td>
                                <td class="r">'.number_format($anyag_var, 2, ',', ' ').'</td>
                                <td class="r zold">'.number_format($folyamatban, 2, ',', ' ').'</td>
                                <td id="th'.$telephely['telephely_id'].'a'.$anyag['cikk_id'].'_mee">'.$akt_anyag['cikk_mee'].'</td>
                                <td id="th'.$telephely['telephely_id'].'a'.$anyag['cikk_id'].'_igleir">'.nl2br2($akt_anyag['igleir']).'</td>
                                <td><input type="button" value="Mutat" class="gomb" onclick="window.open(\'munkalap.php?recidk='.$akt_munkalapok.'\')"></td>
                                <td><input type="button" value="Igénylés" class="gomb f_anyagigenyles_mezok_kitolt" data-recid_egyedi_azon="'.$recid_egyedi_azon.'" data-telephely_id="'.$telephely['telephely_id'].'" data-cikk_id="'.$anyag['recid'].'" data-cikk_id2="'.$anyag['cikk_id'].'"></td>
                                <td>'.$mar_igenyelve.'</td>
                                <td><input type="button" value="Anyag rendelkezésre áll" class="gomb f_anyag_rendelkezesre_all" data-telephely_id="'.$telephely['telephely_id'].'" data-cikkid_origi="'.$anyag['cikk_id'].'" data-cikk_id="'.$anyag['cikk_id'].'"></td>
                            </tr>
                        ');
                    }
                }

                $szamla_html .= nl2('
                    <div class="dt">
                        <div class="dtc form_bal" style="padding-right: 4rem;">
                            <div class="hozzaadasGomb">
                                <input type="button" class="gombb mentes_gomb" name="mehet_gomb" value="Hozzáadás" onclick="if( form_ell_admin_elott() ) { anyagigenylesHozzaad(); }">
                            </div>
                        </div>
                        <div class="dtc form_jobb">
                            <form id="anyagSorokForm">
                                <table id="insertAnyagigenylesSor" class="csikos tomorit full">
                                    <tr>
                                        <th>Szálloda</th>
                                        <th style="width: 6rem">Cikk</th>
                                        <th>Név</th>
                                        <th>RB-s anyag</th>
                                        <th>Igényelt mennyiség</th>
                                        <th>Mee</th>
                                        <th>Leírás</th>
                                        <th>Törlés</th>
                                    </tr>
                                </table>
                            </form>
                        </div>
                    </div>
                    ');
                    if($anyagok_html){
                        $szamla_html .= nl2('<p style="margin: 2rem 0 0.2rem 0" class="b">Igénylésre váró anyagok a munkalapokon:</p>
                        <table class="csikos tomorit">
                            <tr>
                                <th>Szálloda</th>
                                <th>Cikk</th>
                                <th>Név</th>
                                <th>Munkalapon igénylés alatt</th>
                                <th>Munkalapon anyagra vár (igényelve)</th>
                                <th>Igénylés rendelés folyamatában</th>
                                <th>Mee</th>
                                <th>Leírás</th>
                                <th>Anyagra váró feladatok</th>
                                <th>Anyag igénylés</th>
                                <th>Már igényelve</th>
                                <th>Anyag rendelkezésre áll</th>
                            </tr>
                            '.$anyagok_html.'
                    ');
                }

                $szamla_html .= '</table>';
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

	// Táblázat
    } elseif(isset($_GET['tablazat'])) {

        // A füle CSS-ét itt felülírjuk, illetve a táblázat rendezőt és egyés ide vonatkozós JS-t is meghívunk
        $szamla_html .= nl2('
                <style type="text/css">
                    div.szamlaFulek{
                        margin-left: 0;
                    }
                </style>
                <script src="../js/sorttable.js"></script>
                <script src="../js/anyagigenyles_tablazat.js"></script>
        ');

        $szamla_html .= $balNavPanelHTMLje;
        // $szamla_html .= szamla_lista();

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'fulek'    => $fulek,  // A fülek is változnak
                'tartalom' => $gombok, // Ajax esetén csak a gombokat írjuk ki
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Anyagigénylés módosítása
    } elseif(isset($_GET['modosit'])) {

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
        }
        $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");

        // Anyagrendeléskor a cikkrendfej táblából dolgozunk.
        if (isset($_GET['anyagrend'])) {
            $sor_rnd = $adatbazis_kapcsolat -> s2('*', 'cikkrendfej', "`rndid`='{$sor['rndid']}'");

            $sor_rnd['rndertek'] = number_format($sor_rnd['rndertek'], 0, '', ' ');
            $kepfajl_original   = $sor_rnd['kepfajl'];
            $sor_rnd['kepfajl'] = str_replace('rendeles/', '', $sor_rnd['kepfajl']);

            foreach($sor_rnd as $kulcs => $ertek){
                $sor[$kulcs] = $sor_rnd[$kulcs];
            }

        }

        $sor['rnd_dt']  = str_replace('-', '.', $sor['rnd_dt']);
        $sor['vrndear'] = number_format( $sor['vrndear'], 2, ',', ' ');
        $sor['rndear']  = number_format( $sor['rndear'],  2, ',', ' ');

        foreach($akt_tomb as $tomb) {

            // Autocomplete lekezelése
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
                }else{
                    $sor[$tomb['name'].'_autocomp'] = $sor[$tomb['name']];
                }

            }

        }

        // A két számot visszaállítjuk
        $sor['vrndear'] = str_replace( array(' ', ','), array('', '.'), $sor['vrndear'] );
        $sor['rndear']  = str_replace( array(' ', ','), array('', '.'), $sor['rndear'] );

        $json_sor = array();
        foreach($sor as $kulcs => $elem){
            $json_sor[$kulcs] = str_replace(array("\r\n", "\r", "\n", "'"), array("#UJSOR#", "#UJSOR#", "#UJSOR#", "#APOSZTROF#"), $elem);
        }
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

        if( !$csak_szerkesztheto ) {
            // Törölhet, ha van hozzá joga és a bejegyzés folyamabtan van (tehát nem törölt, sem lezárt)
            if(_jog(JOG_ANYAGIGENYLES_KEZEL)){
                $nincs_folyamatban_statuszok = array(CIKKIGN_STAT_NYITOTT, CIKKIGN_STAT_ARAZVA, CIKKIGN_STAT_VARAKOZO);
                if(!in_array($sor['stat_id'], $nincs_folyamatban_statuszok)){
                    $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Az anyagigénylés nem törölhető, mert a státusza nem Nyitott, Árazva vagy Várakozó!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
                }else{
                    $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].', \'Kérdés\')" value="Töröl" title="Töröl">';
                }

            }else{
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Nincs jogosultsága törléshez!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
            }
        }

        if($id_latszik){
            $navi = '<b>#'.$_GET['id'].'</b> '.$navi;
        }

        // Duplikáláskor feltett kérdés
        $szamla_html .= nl2('
            <script>
                duplikalGomb  = "<p class=\"c\"><input class=\"gomb zold\" type=\"button\" value=\"Igen\" onclick=\"szurkit(); location.href = \''.basename(self()).'?uj&amp;ujkent='.$_GET['id'].'\'; \"><input class=\"gomb piros\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
                duplikaKerdes = \'Név: '.$sor['nev'].'<br><br>Biztos, hogy duplikálja?\';
            </script>
        ');

        // NAVIGÁCIÓ
        $navi_sablon = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit    = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel  = array( $navi,             '',         $gyk2 );
        //echo str_replace($navi_mit, $navi_mivel, $navi_sablon);

        // HIBAüZENETEK
        require_once('info.php');

        // FORM
        $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

        // Form actionja
        $action = self().get_get(array('errnum','kephiba','siker'));

        // Ez elkészíti a $mezok változót
        require('switch.php');

        $jobb_oldal = '';

        $szamla_html .= str_replace(
            array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}', '{UPDATE_DT}'     ),
            array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok,    $sor['update_dt'] ),
            fajlbol_olvas('../sablon/admin/form_modosit.html', true)
        );

        if( _jog(JOG_ANYAGIGENYLES_JOVAHAGY) ) {
            $szamla_html .= nl2('
                                <div id="jovahagy_ertek" class="b"></div>
                                ');
        }

        /* 2018.04.09 mégsem
        // AnyagRendelés esetén az anygigényléseket még fel kell sorolni egy táblázatba
        if (isset($_GET['anyagrend'])) {

            $rndid = $adatbazis_kapcsolat->s2('recid rndid', 'anyagigenyles', "`recid` = '".$_GET['id']."'", '', '1');
            if( $rndid ) {

                // Tétel sorok
                $sorok = $adatbazis_kapcsolat->s('recid cikk_id stat_id nev rndmen rndear cikkmee erkmen vrndear kcs_21 telephely_id', 'anyagigenyles', "`rndid` = '".$rndid['rndid']."'", 'insert_dt desc', '');

                if( $sorok ) {
                    $szamla_html .= nl2('
                        <div style="margin: 1rem 0 0.5rem 0">Anyagigénylések:</div>
                        <table class="csikos tetel_csikos full">
                            <tr>
                                <th class="l">Szálloda</th>
                                <th class="l">Cikkszám</th>
                                <th class="l">Cikk név</th>
                                <th class="r">Rendelt mennyiség</th>
                                <th>Mee</th>
                                <th class="r">Rendelési egységár ('.get_penznem().')</th>
                                <th class="r">Deviza rend. egységár</th>
                                <th class="r">Beérkezett mennyiség</th>
                                <th class="r">Pénzn</th>
                                <th class="r">Státusz</th>
                            </tr>
                    ');

                    foreach($sorok as $sor) {
                        $szamla_html .= nl2('
                            <tr >
                                <td class="l nowrap">'.getErtek('nev', 'telephely', 'id', $sor['telephely_id']).'</td>
                                <td class="l">'.$sor['cikk_id'].'</td>
                                <td class="l">'.htmlspecialchars_decode($sor['nev']).'</td>
                                <td class="r nowrap">'.$sor['rndmen'].'</td>
                                <td>'.$sor['cikkmee'].'</td>
                                <td class="r nowrap">'.number_format($sor['rndear'], 2, ',', ' ').'</td>
                                <td class="r nowrap">'.number_format($sor['vrndear'], 4, ',', ' ').'</td>
                                <td class="r nowrap">'.number_format($sor['erkmen'], 2, ',', ' ').'</td>
                                <td class="r">'.erteklista(21, $sor['kcs_21']).'</td>
                                <td class="r">'.getStatText('anyagigenyles', $sor['stat_id']).'</td>
                            </tr>
                        ');
                    }

                    $szamla_html .= '</table>';
                }
            }

        }*/

        // A léptetés csak akkor fog működni, ha a bal navpanel kódja benne van a kódban
        $szamla_html .= '<div class="dn">'.$balNavPanelHTMLje.'</div>';
        //$szamla_html .= '<div class="dn">'.szamla_lista().'</div>';

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


	// Történet
    }elseif(isset($_GET['tortenet'])) {

        $szamla_html .= get_tortenet($megnevezes);

        // A léptetés csak akkor fog működni, ha a bal navpanel kódja benne van a kódban
        $szamla_html .= '<div class="dn">'.$balNavPanelHTMLje.'</div>';
        //$szamla_html .= '<div class="dn">'.szamla_lista().'</div>';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Dokumentumok
	}elseif( isset($_GET['dokumentumok']) ) {

        if( $tobb_kep ) {
            require_once('tobb_kep.php');
        }

        // A léptetés csak akkor fog működni, ha a bal navpanel kódja benne van a kódban
        $szamla_html .= '<div class="dn">'.$balNavPanelHTMLje.'</div>';
        //$szamla_html .= '<div class="dn">'.szamla_lista().'</div>';

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
                    <div id="naploSor-'.$sor['recid'].'" class="dtr nSor kijSor" data-id="'.$sor['recid'].'" data-i="'.$i.'">
                        <div class="dtc">'.htmlspecialchars_decode( nl2br($sor['megj']) ).'</div>
                        <div class="dtc">'.$sor['insert_usr'].'</div>
                        <div class="dtc">'.$sor['insert_dt'].'</div>
                    </div>
                ');

                $i++;
            }
        }

        // A léptetés csak akkor fog működni, ha a bal navpanel kódja benne van a kódban
        $szamla_html .= '<div class="dn">'.$balNavPanelHTMLje.'</div>';
        //$szamla_html .= '<div class="dn">'.szamla_lista().'</div>';

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

// Adott anyagigénylés sor cikk_id-ja milyen munkalapokon szerepel
function hianyzo_anyagok($anyagig_cikk_id, $telephely_id){

    global $adatbazis_kapcsolat;

    $szallodak = get_akt_login_szallodai();
    if($szallodak){
        // munkalap_anyag táblában keresünk olyan sorokat, ahol a státusz nem DISZPNAG_STAT_VAN_ANYAG, és a cikkid-ja adott anyagigénylés cikkid-ja. Ha van ilyen akkor Mutat gomb
        $hianyzo_anyagok = $adatbazis_kapcsolat->s('distinct munkalap_id', 'munkalap_anyag', "`stat_id`!='".DISZPNAG_STAT_VAN_ANYAG."' AND `cikk_id`='{$anyagig_cikk_id}' AND `telephely_id`='{$telephely_id}'");
//echo $adatbazis_kapcsolat->gp() . '<br>';
        $hianyzo_anyagok = adatbazis2tomb($hianyzo_anyagok, 'munkalap_id');
    }
    return $hianyzo_anyagok;
}
