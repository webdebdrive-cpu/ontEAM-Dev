<?php
require_once('../weblap.php');

if (isset($_GET['adatok_karbantart'])) {
    $statuszok = $adatbazis_kapcsolat->s('recid stat_id', 'munkalap_anyag');
    foreach($statuszok as $statusz){
        switch($statusz['stat_id']){
            case '0': $statid = '00'; break;
            case '1': $statid = '01'; break;
            case '2': $statid = '02'; break;
        }
        $adatbazis_kapcsolat->u('munkalap_anyag', array('stat_id'), array($statid), "`recid`='{$statusz['recid']}'");
    }
    exit;
}
if (isset($_GET['adat_javit_tmp'])) {
    $anyagigenyles = $adatbazis_kapcsolat->s('recid cikk_id', 'anyagigenyles');
    foreach($anyagigenyles as $anyag){
        $ora = $adatbazis_kapcsolat->s('oracikkid', 'cikktorzs', "`id`='{$anyag['cikk_id']}'");
        $ora = $ora[0];

        $adatbazis_kapcsolat->u('anyagigenyles', array('oracikkid'), array($ora['oracikkid']), "`recid`='{$anyag['recid']}'");
        echo $adatbazis_kapcsolat->gp() . '<br>';
    }
    exit;
}

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

require_once 'anyagigenyles_nyomtat.php';


if (isset($_POST['f_anyag_rendelkezesre_all'])) {

    // Tranzakció indul
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    // Az érintett munkalap anyagok státuszait 0-ra állítjuk
    $sql = $adatbazis_kapcsolat->u('munkalap_anyag', array('stat_id'), array(DISZPNAG_STAT_VAN_ANYAG), "`telephely_id`='{$_POST['telephely_id']}' AND `cikk_id`='{$_POST['cikk_id']}'");
    if( $sql === false ) {
        $db_error = true;
    }

    // Lefuttatjuk a munkalap_folyamatbanra_halehet függvényt, ezzel ha lehet, folyamatbanra állítva a munkalap státuszát
    $munkalapok = $adatbazis_kapcsolat->s('munkalap_id', 'munkalap_anyag', "`telephely_id`='{$_POST['telephely_id']}' AND `cikk_id`='{$_POST['cikk_id']}'");
    $munkalapok = adatbazis2tomb($munkalapok, 'munkalap_id');
    if(munkalap_folyamatbanra_halehet($munkalapok)===false){
        $db_error = true;
    }

    //Tranzakció kommitolása, vagy rollback-ezése
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
    } else {
        mysqli_rollback($db_kapcs);
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

// Várólistához adás
if( isset($_POST['varolistahozAdas']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( is_array($idk) ) {
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
        }

        // Ellenőrizzük a státuszokat is
        foreach($idk as $id) {
            $statusz = $adatbazis_kapcsolat->s('recid stat_id insert_dt', $page.'_statusz', "`{$page}_recid`='$id'", 'recid desc', '1');
            if( $statusz === false ) { $db_error = true; }

            if( $statusz ) {
                $statusz = $statusz[0];
                if(  !in_array($statusz['stat_id'], $nyitott_statid) ) {
                    echo json_encode(array(
                        'hiba' => 'Csak nyitott státuszú anyagigénylést lehet várólistához adni!'
                    ));

                    $weblap->leallit();
                    exit();
                }
            } else {
                echo json_encode(array(
                    'hiba' => 'Státusz nem található!'
                ));

                $weblap->leallit();
                exit();
            }
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
                'hiba' => tranzakcioHiba()
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

        /* Ellenőrizzük a csoportokat */
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

        /* Ellenőrizzük a státuszokat és hogy az anyag, szállító, pénznem és egységár megegyezik-e a kiválasztott soroknál (vagy üres) */
        $idk_inputba = '';
        $ossz_ig_menny   = 0;
        $ossz_rend_menny = 0;
        foreach($idk as $id) {

            $idk_inputba .= $id.',';
            $statusz = $adatbazis_kapcsolat->s('recid stat_id insert_dt', 'anyagigenyles_statusz', "`anyagigenyles_recid`='$id'", 'recid desc', '1');
            if( $statusz === false ) { $db_error = true; }

            if( $statusz ) {
                $statusz = $statusz[0];

                if( $statusz['stat_id'] != CIKKIGN_STAT_NYITOTT ) {
                    $bejelentes = $adatbazis_kapcsolat->s('ikt_szam', 'anyagigenyles', "`recid` = '{$id}'", '', '');
                    if( $bejelentes === false ) { $db_error = true; }

                    $hiba_kieg = '';
                    if( $bejelentes ) {
                        $bejelentes = $bejelentes[0];

                        $hiba_kieg = '<br>A "'.$bejelentes['ikt_szam'].'" anyagigénylés státusza: '.getStatText('anyagigenyles', $statusz['stat_id']);
                    }

                    echo json_encode(array(
                        'hiba' => 'Árazni csak nyitott státuszú anyagigénylést lehet!'.$hiba_kieg
                    ));
                    $weblap->leallit();
                    exit();

                }else{

                    //Ha van eltérés az előző bejegyzéshez képest (üres mező megengedett) akkor hibaüzenet:
                    $igenyles = $adatbazis_kapcsolat->s('cikk_id szallito_id kcs_21 rndear igmen rndmen', 'anyagigenyles', "`recid`='{$id}'");
                    $igenyles = $igenyles[0];
                    if(
                            ($elozo_cikk_id      && $igenyles['cikk_id']      && $elozo_cikk_id      != $igenyles['cikk_id']     ) ||
                            ($elozo_szallito     && $igenyles['szallito_id']  && $elozo_szallito     != $igenyles['szallito_id'] ) ||
                            ($elozo_kcs_21       && $igenyles['kcs_21']       && $elozo_kcs_21       != $igenyles['kcs_21']      ) ||
                            ($elozo_rndear       && $igenyles['rndear']       && $elozo_rndear       != $igenyles['rndear']      )
                        ){
                        echo json_encode(array(
                            'hiba' => 'Árazni csak azonos cikkeket, azonos szállítóval, pénznemmel és egységárral lehet!'.$hiba_kieg
                        ));
                        $weblap->leallit();
                        exit();
                    }
                    $elozo_cikk_id      = $igenyles['cikk_id'];
                    $elozo_szallito     = $igenyles['szallito_id'];
                    $elozo_kcs_21       = $igenyles['kcs_21'];
                    $elozo_rndear       = $igenyles['rndear'];

                    //Ha ki van töltve a szállító (mivel kötelező mezők, ekkor ki lesz töltve a pénznem és rend. egységár is, akkor azt az ID-t töltjük be a popupba
                    if($igenyles['szallito_id']){
                        $betoltid = $id;
                    }

                    //Adjuk össze az igényelt mennyiségeket
                    $ossz_ig_menny   += $igenyles['igmen'];
                    $ossz_rend_menny += $igenyles['rndmen'];

                }

            } else {
                echo json_encode(array(
                    'hiba' => 'Státusz nem található!'
                ));

                $weblap->leallit();
                exit();
            }
        }

        //Ha egyik sem volt kitöltve, akkor vesszük az utoplsó ID-t. Ekkor más default értékeket is beállítunk
        $default_ertekek_mutat = false;
        if(!$betoltid){
            $betoltid = $id;
            $default_ertekek_mutat = true;
        }

        //Ha több sor van kijelölve, akkor a rendelt mennyiséget disable-zzük
        $rndmen_disabled = '';
        if(count($idk)>1){
            $rndmen_disabled = ' disabled';
        }

        //Legutóbb ellenőrzott rekod ID-ja az $id változóban, induljunk ki abból
        $igenyles = $adatbazis_kapcsolat->s('*', 'anyagigenyles', "`recid`='$betoltid'");
        $igenyles = $igenyles[0];

        if($default_ertekek_mutat){
            $ossz_rend_menny    = $igenyles['igmen'];
            $igenyles['kcs_21'] = 5;
        }

        $igenyles['atvaltva_d'] = str_replace('-', '.', $igenyles['atvaltva_d']);
        $szall_sel_disabled = '';
        $dbf_checked = '';
        $szall_sel   = '';
        $mszk_sel    = '';
        switch($igenyles['erkcimid']){
            case 1: $szall_sel   = ' selected'; break;
            case 2: $mszk_sel    = ' selected'; break;
            case 3: $dbf_checked = ' checked'; $szall_sel_disabled = ' disabled'; break;
        }

        $penznem_txt = $adatbazis_kapcsolat->s('info', 'erteklista', "`csoport`='21' AND `id`='{$igenyles['kcs_21']}'");
        $penznem_txt = $penznem_txt[0];

        $szallito_txt = $adatbazis_kapcsolat->s('nev', 'szallito', "`id`='{$igenyles['szallito_id']}'");
        $szallito_txt = $szallito_txt[0];
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
                        <td class="p"><input'.$rndmen_disabled.' type="text" name="pop_rndmen" id="pop_rndmen" class="f_num" value="'.number_format($ossz_rend_menny, 2, ',', ' ').'"> '.$igenyles['cikkmee'].'</td>
                        <td class="p r">
                            Rendelési összeg:
                        </td>
                        <td class="p">
                            <span id="rend_osszeg"></span> '.get_penznem().'
                            <br><span id="rend_osszeg2"></span>
                        </td>
                    </tr>
                    <tr id="indoklas_tr" class="dn">
                        <td class="p r">Eltérő rendelési<br>mennyiség indoklása:</td>
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
                                    \'csoport\'         : 21,
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
                        <td class="p"><input type="text" name="pop_atvaltva_d" id="pop_atvaltva_d" class="datepicker" value="'.$igenyles['atvaltva_d'].'">
                        <td class="p r">Deviza rend. egységár:</td>
                        <td class="p"><input type="text" name="pop_vrndear" id="pop_vrndear" class="f_num" data-tort="4" value="'.number_format($igenyles['vrndear'], 4, ',', ' ').'"></td>
                    </tr>
                    <tr>
                        <td class="p r"></td>
                        <td class="p" colspan="3">
                            <div class="cb">
                                <input class="check" name="pop_dbf" id="pop_dbf" type="checkbox"'.$dbf_checked.' onclick="if( $(this).prop(\'checked\') ){ $(\'#pop_erkcimid\').val(\'\'); $(\'#pop_erkcimid\').attr(\'disabled\', 1); }else{ $(\'#pop_erkcimid\').removeAttr(\'disabled\'); }">
                                <label for="pop_dbf"></label>
                            </div>
                            Danubius fuvar
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

                        if(rndmen<ig_menny){
                            $(\'#indoklas_tr\').show();
                        }else{
                            $(\'#indoklas_tr\').hide();
                        }

                        /* HUF esetén */
                        if($(\'#pop_kcs_21\').val()==5){
                            $(\'#pop_rndear\').removeAttr(\'disabled\').addClass(\'csillag\');
                            $(\'#pop_arfolyam\').attr(\'disabled\', 1).val(\'0,00000\');
                            $(\'#pop_vrndear\').attr(\'disabled\', 1).val(\'0,0000\');
                            $(\'#pop_atvaltva_d\').attr(\'disabled\', 1).val(\'    .  .  \');

                            rend_osszeg = rndmen*rndear;
                            $(\'#rend_osszeg\').html(ezresek(rend_osszeg, 4, \',\', \' \'));

                        /* Deviza esetén */
                        }else{

                            $(\'#pop_arfolyam\').removeAttr(\'disabled\').addClass(\'csillag\');
                            $(\'#pop_vrndear\').removeAttr(\'disabled\').addClass(\'csillag\');
                            $(\'#pop_atvaltva_d\').removeAttr(\'disabled\').addClass(\'csillag\');
                            $(\'#pop_rndear\').attr(\'disabled\', 1);

                            rend_osszeg = rndmen*arfolyam*arfolyam;
                            $(\'#pop_rndear\').val( ezresek(arfolyam*vrndear, 4, \',\', \' \') );
                            $(\'#rend_osszeg\').html(ezresek(rend_osszeg, 4, \',\', \' \'));
                            $(\'#rend_osszeg2\').html(ezresek(rndmen*vrndear, 4, \',\', \' \')+\' \'+$(\'#pop_kcs_21_autocomp\').val());

                        }
                    }
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
                'hiba' => tranzakcioHiba()
            ));
        }
    }

    $weblap->leallit();
    exit();
}

//Árazás mentése
if ($_POST['arazas_ment']) {
    $_POST['ig_menny']  = str_replace(array(' ', ','), array('', '.'), $_POST['ig_menny']);
    $_POST['rndear']    = str_replace(array(' ', ','), array('', '.'), $_POST['rndear']);
    $_POST['rndmen']    = str_replace(array(' ', ','), array('', '.'), $_POST['rndmen']);
    $_POST['vrndear']   = str_replace(array(' ', ','), array('', '.'), $_POST['vrndear']);
    $_POST['arfolyam']  = str_replace(array(' ', ','), array('', '.'), $_POST['arfolyam']);
    $idk = explode(',', $_POST['idk']);

    //Hibakezelés
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
    if($_POST['ig_menny'] < $_POST['rndmen']){
        echo json_encode(
                    array(
                        'hiba'  => 'A rendelt mennyiség nem lehet több az igényelt mennyiségtől!',
                        'focus' => 'pop_rndmen'
                    )
            );
        $weblap -> leallit();
        exit();
    }
    if($_POST['ig_menny'] > $_POST['rndmen'] && !$_POST['indok']){
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

    //Ha nem HUF
    if($_POST['kcs_21']!=5){
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

    //Tranzakció indul
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    //Több sor esetén a rendelt mennyiséget nem írjuk felül
    if(count($idk)>1){
        $sql = $adatbazis_kapcsolat->u('anyagigenyles',     array('szallito_id',         'kcs_21',         'rndear',           'vrndear',          'arfolyam',          'atvaltva_d',           'erkcimid'        ),
                                                            array($_POST['szallito_id'], $_POST['kcs_21'], $_POST['rndear'],   $_POST['vrndear'],   $_POST['arfolyam'], $_POST['atvaltva_d'],   $_POST['erkcimid']),
                                        "`recid` IN (".$_POST['idk'].")");

    //Egy sor esetén a rendelt mennyiséget is felülírjuk
    }else{
        $sql = $adatbazis_kapcsolat->u('anyagigenyles',     array('szallito_id',         'kcs_21',         'rndear',           'rndmen',           'vrndear',          'arfolyam',          'atvaltva_d',         'erkcimid'        ),
                                                            array($_POST['szallito_id'], $_POST['kcs_21'], $_POST['rndear'],   $_POST['rndmen'],   $_POST['vrndear'],   $_POST['arfolyam'], $_POST['atvaltva_d'], $_POST['erkcimid']),
                                        "`recid` IN (".$_POST['idk'].")");
    }
    if( $sql === false ) {
        $db_error = true;
    }

    //Ha az igényléstől kisebb a rendelés, akkor megjegyzést is fűztek hozzá
    if($_POST['ig_menny']>$_POST['rndmen']){
        foreach($idk as $id){
            $megj = addMegj('anyagigenyles', $id, 'A rendelési mennyiség megváltozott.'."\r\n".'Indoklás: '.$_POST['indok']);
            if($megj===false){
                $db_error = true;
            }
        }
    }

    //Tranzakció kommitolása, vagy rollback-ezése
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array('hiba' => 'Az adatbázisba való íráskor hiba történt!'));
    }


    $weblap -> leallit();
    exit();
}

//Küldés jóváhagyásra hatása, hogy Árazva státuszt kap
if( isset($_POST['kuldes_jovahagyasra']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( is_array($idk) ) {
        /* Ellenőrizzük a csoportokat */
        $sql = $csoportok = $adatbazis_kapcsolat->s('csoport_id', $page, "`recid` IN (".implode(',', $idk).")", '', '');
        if( $sql === false ) {
            $db_error = true;
        }

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
                        'hiba' => 'Csoportosított anyagigénylés önmagában nem küldhető jóváhagyásra. Előbb vegye ki a csoportból!'
                    ));

                    $weblap->leallit();
                    exit();
                }
            }
        }

        // Ellenőrizzük a státuszokat és hogy mind árazva van-e?
        foreach($idk as $id) {

            $statusz = $adatbazis_kapcsolat->s('recid stat_id insert_dt', $page.'_statusz', "`{$page}_recid`='$id'", 'recid desc', '1');
            if( $statusz === false ) { $db_error = true; }

            if( $statusz ) {

                $statusz = $statusz[0];
                if(  $statusz['stat_id']!=CIKKIGN_STAT_NYITOTT ) {
                    echo json_encode(array(
                        'hiba' => 'Csak nyitott státuszú anyagigényléseket lehet jóváhagyásra küldeni!'
                    ));

                    $weblap->leallit();
                    exit();
                }

                //Valóban árazva van-e? Szállító és egységár megadva
                $igenyles = $adatbazis_kapcsolat->s('szallito_id rndear kcs_21', 'anyagigenyles', "`recid`='$id'");
                $igenyles = $igenyles[0];
                if(!$igenyles['szallito_id'] || !$igenyles['rndear'] || !$igenyles['kcs_21']){
                    echo json_encode(array(
                        'hiba' => 'Csak árazott anyagigényléseket lehet jóváhagyásra küldeni!'
                    ));

                    $weblap->leallit();
                    exit();
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
        if (isset($_POST['csak_kerdezunk'])) {

            echo json_encode(array(
                'hiba' => 'Biztos jóváhagyásra küldi kijelölt anyagigényléseket?',
                'gomb' => '<input class="gomb zold" value="Igen" onclick="anyagigenyles_kuldes_jovahagyasra();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        foreach($idk as $id) {
            $statusz = addStatusz( $page.'_statusz', $id, CIKKIGN_STAT_ARAZVA );
            if( $statusz === false ) {
                $db_error = true;
            }
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            echo json_encode(array(
                'statusz' => 'siker'
            ));
        } else {
            mysqli_rollback($db_kapcs);

            echo json_encode(array(
                'hiba' => tranzakcioHiba()
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

            $statusz = $adatbazis_kapcsolat->s('recid stat_id insert_dt', $page.'_statusz', "`{$page}_recid`='$id'", 'recid desc', '1');
            if( $statusz === false ) {
                $db_error = true;
            }

            if( $statusz ) {
                $statusz = $statusz[0];
                if(  $statusz['stat_id']!=CIKKIGN_STAT_ARAZVA ) {
                    echo json_encode(array(
                        'hiba' => 'Csak árazva státuszú anyagigényléseket lehet jóváhagyni!'
                    ));

                    $weblap->leallit();
                    exit();
                }

                //Jóváhagyott mennyiség ellenőrzése
                $igenyles = $adatbazis_kapcsolat->s('rndmen cikkmee', 'anyagigenyles', "`recid`='$id'");
                $igenyles = $igenyles[0];
                if($_POST['jhmen']==0 || $_POST['jhmen']==0.00){
                    echo json_encode(array(
                        'hiba'  => 'A jóváhagyott mennyiség nem lehet 0 '.$igenyles['cikkmee'].'!',
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

            } else {
                echo json_encode(array(
                    'hiba' => 'Státusz nem található!'
                ));

                $weblap->leallit();
                exit();
            }
        }

        // Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if (isset($_POST['csak_kerdezunk'])) {

            echo json_encode(array(
                'hiba' => 'Biztosan jóváhagyja az anyagigénylést? ('.str_replace('.', ',', $igenyles['rndmen']).' '.$igenyles['cikkmee'].')',
                'gomb' => '<input class="gomb zold" value="Igen" onclick="anyagigenyles_jovahagy();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        foreach($idk as $id) {

            //Státusz léptetés jóváhagyvára
            $statusz = addStatusz( $page.'_statusz', $id, CIKKIGN_STAT_JOVAHAGYVA );
            if( $statusz === false ) {
                $db_error = true;
            }

            $sql = $adatbazis_kapcsolat->u('anyagigenyles', array('jhmen', 'rndmen'), array($_POST['jhmen'], $_POST['jhmen']), "`recid`='$id'");
            if( $sql === false ) {
                $db_error = true;
            }

        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            echo json_encode(array(
                'statusz' => 'siker'
            ));
        } else {
            mysqli_rollback($db_kapcs);

            echo json_encode(array(
                'hiba' => tranzakcioHiba()
            ));
        }
    }

    $weblap->leallit();
    exit();
}

//Anyagigénylés elutasítása: hatása, hogy Jóváhagyva státuszt kap illetve pár mezőt kitöltünk még az adatbázisban
if( isset($_POST['anyagigenyles_elutasitas']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( is_array($idk) ) {

        // Ellenőrizzük a státuszokat, és feltételeket
        foreach($idk as $id) {

            $statusz = $adatbazis_kapcsolat->s('recid stat_id insert_dt', $page.'_statusz', "`{$page}_recid`='$id'", 'recid desc', '1');
            if( $statusz === false ) {
                $db_error = true;
            }

            if( $statusz ) {
                $statusz = $statusz[0];
                if(  $statusz['stat_id']!=CIKKIGN_STAT_ARAZVA ) {
                    echo json_encode(array(
                        'hiba' => 'Csak árazva státuszú anyagigényléseket lehet elutasítani!'
                    ));

                    $weblap->leallit();
                    exit();
                }

            } else {
                echo json_encode(array(
                    'hiba' => 'Státusz nem található!'
                ));

                $weblap->leallit();
                exit();
            }
        }

        //Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if (isset($_POST['csak_kerdezunk'])) {

            echo json_encode(array(
                'hiba' => nl2('
                                <div id="hibauzi" class="piros"></div>
                                Anyagigénylés elutasításának indoklása:
                                <br><textarea id="pop_indoklas" style="width: 37rem; margin-top: 1rem" class="csillag" onkeyup="if( $(this).val().trim()==\'\' ){ $(this).val(\'\'); }"></textarea>
                            '),
                'gomb' => '<input class="gomb zold" value="Elutasítás" onclick="anyagigenyles_elutasitas();" type="button"><input class="gomb piros" value="Mégsem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        foreach($idk as $id) {

            //Státusz léptetés elutasítvára
            $statusz = addStatusz( $page.'_statusz', $id, CIKKIGN_STAT_ELUTASITVA, '', $_POST['oka'] );
            if( $statusz === false ) {
                $db_error = true;
            }
            if($_POST['oka']){
                if( addMegj('anyagigenyles', $id, $_POST['oka']) === false ) {
                    $db_error = true;
                }
            }

            $sql = $adatbazis_kapcsolat->u('anyagigenyles', array('jhmen'), array(0), "`recid`='$id'");
            if( $sql === false ) {
                $db_error = true;
            }


        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            echo json_encode(array(
                'statusz' => 'siker'
            ));
        } else {
            mysqli_rollback($db_kapcs);

            echo json_encode(array(
                'hiba' => tranzakcioHiba()
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

    // Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
    if (isset($_POST['csak_kerdezunk'])) {

        $ertek = 0;
        $igenylesek = $adatbazis_kapcsolat->s('igmen rndear', 'anyagigenyles', "`stat_id`='".CIKKIGN_STAT_ARAZVA."'".get_akt_login_szallodai('sql'));
        // Ha nincs találat, akkor hibaüzi és kilépés
        if(!$igenylesek){
            echo json_encode(array(
                'hiba' => nl2('
                                Nincs jóváhagyásra váró anyagigénylés!
                            '),
            ));
            $weblap->leallit();
            exit();
        }
        foreach ($igenylesek as $igeny){
            $ertek += $igeny['igmen']*$igeny['rndear'];
        }

        echo json_encode(array(
            'hiba' => nl2('
                            Jóváhagyás alatti érték: '.number_format($ertek, 2, ',', ' ').' '.get_penznem().'
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
            'hiba' => tranzakcioHiba()
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

            $statusz = $adatbazis_kapcsolat->s2('recid stat_id insert_dt', $page.'_statusz', "`{$page}_recid`='$id'", 'recid desc', '1');
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


        // Ha még nem kaptunk utasítást arra, hogy mehet adatbáziba, akkor a nyomtatási képeket ajánjuk fel
        if ($_POST['mehet_db_be']!='mehet') {

            mysqli_query($db_kapcs, 'SET AUTOCOMMIT=0');
            $sorszam = mysqli_query($db_kapcs, 'SELECT `sorszam` FROM `'.get_db_tabla().'iktszam_seq` WHERE `tabla`=\'cikkrendfej\' AND `ev`=\''.date('y').'\' FOR UPDATE;');
            $sorszam = mysql_fetch_array($sorszam);
            $sorszam = $sorszam[0]+1;
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
        // Megnézzük, hogy az adott évszámmal létezik e bejegyzés
        $ikt_seq = $adatbazis_kapcsolat->s2('ev sorszam', 'iktszam_seq', '`tabla`=\'cikkrendfej\' AND `ev`=\''.date('y').'\'');
        if( $ikt_seq === false ) { $db_error = true; }
        if( $ikt_seq ) {

            $sorszam = $ikt_seq['sorszam'];
            $sorszam++;

            // Letároljuk az új sorszámot
            $sql = $adatbazis_kapcsolat -> u('iktszam_seq', array('sorszam'), array($sorszam), '`tabla`=\'cikkrendfej\' AND `ev`=\''.date('y').'\'');
            if( $sql === false ) { $db_error = true; }
            
        } else {
            
            $sorszam = 1;
            $sql = $adatbazis_kapcsolat -> i('iktszam_seq', array('sorszam', 'ev', 'tabla'), array($sorszam, date('y'), 'cikkrendfej'));
            if( $sql === false ) {  $db_error = true; }
        }

        // Most kommitoljuk el a popupban lekért sorszámot
        mysqli_query($db_kapcs, 'COMMIT');

        // Tesztelés idejére kommentezve
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
        $rnd_dt = date('Y-m-d H:i:s');
        $pdf_utvonal = 'doc/rendeles/'.date('y').'/'.$elozo_szallito.'/'.str_replace('/', '_', $rndid).'.pdf';

        // Létrehozunk egy új rendelésfej bejegyzést
        $cikkrendfej_id = $adatbazis_kapcsolat->i('cikkrendfej',    array('stat_id',                'rndid', 'rnd_dt',  'kepfajl',     'szallito_id',    'insert_dt',    'insert_usr',                           'update_usr'                            ),
                                                                    array(CIKKIGN_STAT_MEGRENDELVE, $rndid,  $rnd_dt,   $pdf_utvonal,  $elozo_szallito,  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'], $_SESSION[get_db_tabla().'teljes_nev']  )
                                                    );
        if( $cikkrendfej_id === false ) { $db_error = true; }

        // Felvesszük az első státuszt
        $sql = $adatbazis_kapcsolat -> i('cikkrendfej_statusz',
                                            array('cikkrendfej_recid',  'stat_id',                  'insert_dt',    'insert_usr',                              'update_usr'                          ),
                                            array($cikkrendfej_id,      CIKKIGN_STAT_MEGRENDELVE,   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],    $_SESSION[get_db_tabla().'teljes_nev'] )
                                        );
        if( $sql === false ) { $db_error = true; }

        // Anyagigénylés sorok updatelése (státuszt is, illetve egyéb mezőket is)
        foreach($idk as $id) {
            if( addStatusz( $page.'_statusz', $id, CIKKIGN_STAT_MEGRENDELVE ) === false ) {
                $db_error = true;
            }

            // Az anyagigénylés érintett sorait updateljük
            $adatbazis_kapcsolat->u('anyagigenyles',    array('rndid', 'rnd_dt', 'kepfajl',    'update_usr',                           'aparecid'       ),
                                                        array($rndid,  $rnd_dt,  $pdf_utvonal, $_SESSION[get_db_tabla().'teljes_nev'], $cikkrendfej_id  ),
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
                'hiba' => tranzakcioHiba()
            ));
        }
    }

    $weblap->leallit();
    exit();
}

// Anyagigénylés érkeztetése
if (isset($_POST['anyagigenyles_erkeztet'])) {

    //Rendelésszám lekérdezése
    if($_POST['rsz']){

        $recid = $adatbazis_kapcsolat->s('recid', 'cikkrendfej', "`rndid`='{$_POST['rsz']}'");
        $recid = $recid[0];
        if(!$recid){
            echo json_encode(array(
                'hiba' => 'Nincs ilyen rendelésszám az anyagigénylés naplóban!'
            ));
            $weblap->leallit();
            exit();
        }
        if($adatbazis_kapcsolat->s('recid', 'cikkrendfej', "`rndid`='{$_POST['rsz']}' AND `stat_id`>'".CIKKIGN_STAT_MEGRENDELVE."'")){
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
                    <th>Név</th>
                    <th>Igénylet mennyiség</th>
                    <th>Rendelt mennyiség</th>
                    <th>Előzőleg beérkezett mennyiség</th>
                    <th>Beérkezett mennyiség</th>
                    <th>Mee</th>
                    <th>Nem lesz több beérkezés</th>
                    <th>Anyagra váró feladatok diszpécsernaplóban</th>
                </tr>
            ');

        // Beloginolt user szállodáira is szűrünk
        $szallodak = get_akt_login_szallodai();
        if($szallodak){

            $igenylesek = $adatbazis_kapcsolat->s('recid telephely_id cikk_id nev igmen rndmen erkmen cikkmee szallito_id stat_id', 'anyagigenyles', "`aparecid`='{$recid['recid']}' AND `telephely_id` IN (".implode(',', $szallodak).")");
            foreach($igenylesek as $igeny){

                $telephely = $adatbazis_kapcsolat->s2('nev', 'telephely', "`id`='{$igeny['telephely_id']}'");

                $gomb = '(nincs anyagra váró feladatok a diszpécser naplóban)';
                //munkalap_anyag táblában keresünk olyan sorokat, ahol a státusz nem DISZPNAG_STAT_VAN_ANYAG, és a cikkid-ja adott anyagigénylés cikkid-ja. Ha van ilyen akkor Mutat gomb
                $hianyzo_anyagok = $adatbazis_kapcsolat->s('distinct munkalap_id', 'munkalap_anyag', "`stat_id`!='".DISZPNAG_STAT_VAN_ANYAG."' AND `cikk_id`='{$igeny['cikk_id']}'");
                $hianyzo_anyagok = adatbazis2tomb($hianyzo_anyagok, 'munkalap_id');
                $hianyzo_anyagok = implode(',', $hianyzo_anyagok);
                if($hianyzo_anyagok){
                    $gomb = '<input type="button" value="Mutat" class="gomb" onclick="window.open(\'munkalap.php?recidk='.$hianyzo_anyagok.'\')">';
                }

                //Ha a státusz nagyobb mit a beérkezett, akkor azokat már csak szürkén jelenítjük meg disabledezve
                $szurkit  = '';
                $disabled = '';
                if($igeny['stat_id']>CIKKIGN_STAT_RESZBENBEERK){
                    $szurkit  = ' class="erkeztet_szurkit"';
                    $disabled = ' disabled';
                }

                $html .= nl2('
                            <tr'.$szurkit.'>
                                <td id="erkeztet_szall_'.$igeny['recid'].'">'.$telephely['nev'].'</td>
                                <td id="erkeztet_cikkid_'.$igeny['recid'].'">'.$igeny['cikk_id'].'</td>
                                <td>'.$igeny['nev'].'</td>
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


        $szallito = $adatbazis_kapcsolat->s('nev', 'szallito', "`id`='{$igeny['szallito_id']}'");
        $szallito = $szallito[0];

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


                // HA marerkmen+erkmen=0 ÉS nemfogerk=1 AKKOR statid=CIKKIGN_STAT_NEM_ERKEZETT
                if( ($marerkmen+$erkmen==0 || $marerkmen+$erkmen==0.00) && $nemfogerk){
                    $statusz = addStatusz( 'anyagigenyles_statusz', $id, CIKKIGN_STAT_NEM_ERKEZETT );
                    if( $statusz === false ) {
                        $db_error = true;
                    }


                // HA marerkmen+erkmen>=rndmen VAGY (marerkmen+erkmen>0 ÉS nemfogerk=1), AKKOR statid=CIKKIGN_STAT_BEERKEZETT
                }else if( $marerkmen+$erkmen>=$rndmen || ($marerkmen+$erkmen>0  && $nemfogerk) ){
                    $statusz = addStatusz( 'anyagigenyles_statusz', $id, CIKKIGN_STAT_BEERKEZETT );
                    if( $statusz === false ) { $db_error = true; }

                // HA marerkmen+erkmen>0 ÉS marerkmen+erkmen<rndmen ÉS nemfogerk=0 AKKOR statid=CIKKIGN_STAT_RESZBENBEERK
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
                                                                array($anyagigenyles['telephely_id'], $anyagigenyles['szallito_id'], $anyagigenyles['cikk_id'],  $erkmen,    MOZGID_RAKTAR_BE,  1,        $id,         $_SESSION[get_db_tabla().'teljes_nev'], $_SESSION[get_db_tabla().'teljes_nev'],  'NOW()'     )
                                                    );
                    if( $sql === false ) { $db_error = true; }
                }


            }
        }

        //Ciklus után minden esetben átírjuk cikkrndfej tábla statidet mysql procedúrával: call setcikkrndfejstatid(aparecid,loginuser.nev);
        $sql = mysqli_query($db_kapcs, 'setcikkrndfejstatid('.$anyagigenyles['aparecid'].', \''.$_SESSION[get_db_tabla().'teljes_nev'].'\')');
        if( $sql === false ) { $db_error = true; }


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
//            //Oszlopok társítása
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
                'hiba' => tranzakcioHiba()
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
    // Létrehozunk egy új rendelésfej bejegyzést
    $cikkrendfej_id = $adatbazis_kapcsolat->i('cikkrendfej',    array('keszpenz', 'stat_id',               'rndid', 'rnd_dt',  'kepfajl',   'szallito_id',         'insert_dt',    'insert_usr',                           'update_usr'                            ),
                                                                array(1,          CIKKIGN_STAT_BEERKEZETT, $rndid,  'NOW()',   '',          $_POST['szallito_id'], 'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'], $_SESSION[get_db_tabla().'teljes_nev']  )
                                                );
    if( $cikkrendfej_id === false ) { $db_error = true; }

    // Felvesszük az első státuszt
    $sql = $adatbazis_kapcsolat -> i('cikkrendfej_statusz',
                                        array('cikkrendfej_recid',  'stat_id',                 'insert_dt',    'insert_usr',                              'update_usr'                             ),
                                        array($cikkrendfej_id,      CIKKIGN_STAT_BEERKEZETT,   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],    $_SESSION[get_db_tabla().'teljes_nev']   )
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
            $anyagigenyles = $adatbazis_kapcsolat->i('anyagigenyles', array('rndid',  'szallito_id',         'telephely_id',         'cikk_id',            'nev',            'cikkmee',            'stat_id',             'rndmen',         'igmen',         'jhmen',         'erkmen',        'erkear',      'rndear',      'kulsosnek', 'keszpenz', 'erkcimid', 'rnd_dt', 'kcs_21', 'oracikkid', 'aparecid',      'insert_dt',  'insert_usr',                              'update_usr'                             ),
                                                                      array($rndid,   $_POST['szallito_id'], $_POST['telephely_id'], $anyag['cikkszam'],   $ciktorzs['nev'], $ciktorzs['cikkmee'],  CIKKIGN_STAT_NYITOTT, $anyag['menny'],  $anyag['menny'], $anyag['menny'], $anyag['menny'], $anyag['ear'], $anyag['ear'], $kulsosnek,  1,          1,          'NOW()',   5,       $oracikkid,  $cikkrendfej_id, 'NOW()',      $_SESSION[get_db_tabla().'teljes_nev'],    $_SESSION[get_db_tabla().'teljes_nev']   )
                                                    );
            if( $anyagigenyles === false ) { $db_error = true; }

            $sql = $adatbazis_kapcsolat -> i('anyagigenyles_statusz',
                                                array('anyagigenyles_recid',    'stat_id',              'insert_dt',    'insert_usr',                              'update_usr'                             ),
                                                array($anyagigenyles,           CIKKIGN_STAT_NYITOTT,   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],    $_SESSION[get_db_tabla().'teljes_nev']   )
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
            'gomb'    => '<input type="button" class="gomb" value="Ok" onclick="location.href=\'anyagigenyles.php?anyagrend&amp;id='.$anyagigenyles.'&amp;modosit\'">',
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
                    $sor = getStatText('anyagigenyles', $sor);
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
        echo '<div id="szamlaBetolt" data-recid="'.$_GET['id'].'" data-page="'.str_replace('.php', '', basename(self())).'">';
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

    if( isset($_GET['modosit']) || isset($_GET['tortenet']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) || isset($_GET['tetel']) ) {
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

    // Gombok kirajzolása minden oldalra
    $gombok = '';
    if( isset($_GET['uj']) ) {

        // Újat csak "Anyagigénylés kezelése" joggal lehet
        if(_jog(JOG_ANYAGIGENYLES_KEZEL)){
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

            // Műveletek box
            $gombok = nl2('
                <div class="muveletek"'.$muv_style.'>
                    '.$gombok.'
                </div>
            ');
        }

    // Módosítás gombok
    } else {

        $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, 'recid='.$_GET['id']);

        // Js, a törlés gombhoz
	    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';

        // Anyagigénylés jóváhagyása
        if( _jog(JOG_ANYAGIGENYLES_JOVAHAGY) ) {
            
            if($sor['stat_id']==CIKKIGN_STAT_ARAZVA){
                $gombok .= '<input type="button" class="gomb foGomb zold" value="Jóváhagyás" onclick="anyagigenyles_jovahagy(\'csak kérdezünk még\')"> ';
                $gombok .= '<input type="button" class="gomb foGomb piros" value="Elutasítás" onclick="anyagigenyles_elutasitas(\'csak kérdezünk még\')"> ';
            }
            $gombok .= '<input type="button" class="gomb foGomb" value="Mind jóváhagyása" onclick="anyagigenyles_mind_jovahagy(\'csak kérdezünk még\')"> ';
            
            // Ha jóváhagyás joga van, és a státusza árazva, akkor a jóváhagyott mennyiség enabled
            if($sor['stat_id']==CIKKIGN_STAT_ARAZVA){
                $gombok .= nl2('
                    <script>
                        $(document).ready(function() {
                            $(\'#jhmen\').removeAttr(\'disabled\');
                            $(\'#jhmen\').val($(\'#rndmen\').val());
                            jovahagy_kiszamol();
                            $(document).on(\'keyup\', \'#jhmen\', function() {
                                jovahagy_kiszamol();
                            });
                        });
                        function jovahagy_kiszamol(){
                            jhmen  = $(\'#jhmen\') .val().replace(\',\', \'.\').replace(/ /g,\'\');
                            rndear = $(\'#rndear\').val().replace(\',\', \'.\').replace(/ /g,\'\');
                            $(\'#jovahagy_ertek\').html(\'Jóváhagyás alatti érték: \'+ezresek(jhmen*rndear, 2, \',\', \' \')+\' '.get_penznem().'\' );
                        }
                    </script>
                    ');
            }
        }
        // Anyagigénylés árazása
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
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt az anyagigénylést?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
            }
        }

        if( _jog(JOG_ANYAGIGENYLES_KEZEL) ){

            // Csak saját bejelentéseit szerkesztheti
            $nem_szerkesztheti = false;
            if($sor['loginnev']!=$_SESSION[get_db_tabla().'user']){
                $nem_szerkesztheti = true;
            }

            if(!$nem_szerkesztheti && isset($_GET['modosit'])){
                $gombok = nl2('
                    <div class="form_kuldes">
                        <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Anyagigénylés" onclick="$(\'#szerk_form\').submit()">
                    </div>
                ').$gombok;
            }
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

        //Igénylésre váró anyagok a munkalapokon:
        //A beloginolt user szállodáit érintő munkalapokat szedjük össze
        $szallodak = get_akt_login_szallodai();
        if($szallodak){

            $munkalapok = $adatbazis_kapcsolat->s('recid', 'munkalap', "`telephely_id` IN (".implode(',', $szallodak).")");
            $munkalapok = adatbazis2tomb($munkalapok, 'recid');
            if($munkalapok){


                $anyagok_html = '';
                //Szállodánként járjuk be, és azon belül az egyes anyagokat összegezzük
                $munkalapok = implode(',', $munkalapok);
                $telephelyek = $adatbazis_kapcsolat->s('distinct telephely_id', 'munkalap_anyag', "`munkalap_id` IN (".$munkalapok.") AND `stat_id`=1");
                foreach($telephelyek as $telephely){

                    // Azon belül anyagonként járjuk be
                    $anyagok = $adatbazis_kapcsolat->s('distinct cikk_id', 'munkalap_anyag', "`munkalap_id` IN (".$munkalapok.") AND `stat_id`='".DISZPNAG_STAT_ANYAGRA_VAR."' AND `telephely_id`='{$telephely['telephely_id']}'");
                    foreach($anyagok as $anyag){
//echo $adatbazis_kapcsolat->gp() . '<br>';

                        // A mutat linkbe csak azokat a munkalapokID-kat kell fevenni, ami adott anyag/státusz/telephelyhez tartozik
                        $akt_munkalapok = $adatbazis_kapcsolat->s('distinct munkalap_id', 'munkalap_anyag', "`cikk_id`='{$anyag['cikk_id']}' AND `stat_id`='".DISZPNAG_STAT_ANYAGRA_VAR."' AND `telephely_id`='{$telephely['telephely_id']}'");
                        $akt_munkalapok = adatbazis2tomb($akt_munkalapok, 'munkalap_id');
                        $akt_munkalapok = implode(',', $akt_munkalapok);

                        // Igénylés alatti darabszám kiszámítása
                        $ig_var = 0;
                        $akt_anyagok = $adatbazis_kapcsolat->s('recid cikk_nev cikk_id szuk_men cikk_mee', 'munkalap_anyag', "`munkalap_id` IN (".$munkalapok.") AND `cikk_id`='{$anyag['cikk_id']}' AND `stat_id`='".DISZPNAG_STAT_ANYAGRA_VAR."' AND `telephely_id`='{$telephely['telephely_id']}'");
                        foreach($akt_anyagok as $akt_anyag){
                            $recid_egyedi_azon  = $akt_anyag['recid'];
                            $cikk_nev           = $akt_anyag['cikk_nev'];
                            $cikk_id            = $akt_anyag['cikk_id'];
                            $cikk_mee           = $akt_anyag['cikk_mee'];
                            $ig_var            += $akt_anyag['szuk_men'];
                        }

                        //A cikktörzs rec_id-jáéra van szükségünk a későbbiekben. ID alapján megkeressük
                        $cikkid_origi = $cikk_id;
                        $cikk_id = $adatbazis_kapcsolat->s('recid', 'cikktorzs', "`id`='{$cikkid_origi}'");
                        $cikk_id = $cikk_id[0]['recid'];

                        // Anyagra váró darabszám kiszámítása (ugyanaz, csak másik státuszt figyelünk)
                        $anyag_var = 0;
                        $akt_anyagok = $adatbazis_kapcsolat->s('szuk_men', 'munkalap_anyag', "`munkalap_id` IN (".$munkalapok.") AND `stat_id`='".DISZPNAG_STAT_ANYAG_IGENYELVE."' AND `telephely_id`='{$telephely['telephely_id']}'");
                        foreach($akt_anyagok as $akt_anyag){
                            $anyag_var += $akt_anyag['szuk_men'];
                        }

                        // Rendelés folyamatban kiszámítása: az összes olyan anyagigénylés, ahol adott anyag cikkszámmal dolgozunk és a státusza nem "törölt" és nem "lezárt"
                        $folyamatban = 0;
                        $folyamatbanok = $adatbazis_kapcsolat->s('igmen', $megnevezes, "`cikk_id`='$cikk_id' AND `telephely_id`='{$telephely['telephely_id']}' AND `stat_id` NOT IN (".CIKKIGN_STAT_LEZART.", ".CIKKIGN_STAT_TOROLT.")");
                        foreach($folyamatbanok as $foly){
                            $folyamatban += $foly['igmen'];
                        }

                        $szalloda = $adatbazis_kapcsolat->s2('nev', 'telephely', "`id`='{$telephely['telephely_id']}'");

                        $mar_igenyelve = '(nincs igénylés)';
                        if($folyamatban){
                            $mar_igenyelve = '<input type="button" value="Már igényelve" class="gomb">';
                        }

                        $anyagok_html .= nl2('
                                        <tr id="sor_'.$cikk_id.'">
                                            <td>'.$szalloda['nev'].'</td>
                                            <td id="cikk_id_'.$recid_egyedi_azon.'">'.$cikkid_origi.'</td>
                                            <td id="cikk_nev_'.$recid_egyedi_azon.'">'.$cikk_nev.'</td>
                                            <td class="r piros" id="th'.$telephely['telephely_id'].'a'.$cikk_id.'">'.number_format($ig_var, 2, ',', ' ').'</td>
                                            <td class="r">'.number_format($anyag_var, 2, ',', ' ').'</td>
                                            <td class="r zold">'.number_format($folyamatban, 2, ',', ' ').'</td>
                                            <td id="th'.$telephely['telephely_id'].'a'.$cikk_id.'_mee">'.$cikk_mee.'</td>
                                            <td><input type="button" value="Mutat" class="gomb" onclick="window.open(\'munkalap.php?recidk='.$akt_munkalapok.'\')"></td>
                                            <td><input type="button" value="Igénylés" class="gomb f_anyagigenyles_mezok_kitolt" data-recid_egyedi_azon="'.$recid_egyedi_azon.'" data-telephely_id="'.$telephely['telephely_id'].'" data-cikk_id="'.$cikk_id.'"></td>
                                            <td>'.$mar_igenyelve.'</td>
                                            <td><input type="button" value="Anyag rendelkezésre áll" class="gomb f_anyag_rendelkezesre_all" data-telephely_id="'.$telephely['telephely_id'].'" data-cikkid_origi="'.$cikkid_origi.'" data-cikk_id="'.$cikk_id.'"></td>
                                        </tr>
                                        ');
                    }
                }

                if($anyagok_html){
                    $szamla_html .= nl2('
                                        <p style="margin-top: 2rem" class="b">Igénylésre váró anyagok a munkalapokon:</p>
                                        <table class="csikos">
                                        <tr>
                                            <th>Szálloda</th>
                                            <th>Cikk</th>
                                            <th>Név</th>
                                            <th>Munkalapon igénylés alatt</th>
                                            <th>Munkalapon anyagra vár</th>
                                            <th>Igénylés rendelés folyamatában</th>
                                            <th>Mee</th>
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
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

	// Anyagigénylés módosítása
    } elseif(isset($_GET['modosit'])) {

        // Anyagrendelés adatlapja
        if (isset($_GET['anyagrend'])) {

            $rndid = $adatbazis_kapcsolat->s2('recid rndid', 'anyagigenyles', "`recid` = '".$_GET['id']."'", '', '1');

            if( $rndid ) {

                // Tétel sorok 
                $sorok = $adatbazis_kapcsolat->s('cikk_id nev rndmen rndear cikkmee erkmen', 'anyagigenyles', "`rndid` = '".$rndid['rndid']."'", 'insert_dt desc', '');

                if( $sorok ) {
                    $szamla_html .= nl2('
                        <table class="csikos tetel_csikos full">
                            <tr>
                                <th class="l">Cikkszám</th>
                                <th class="l">Cikk neve</th>
                                <th class="r">Rendelési egységár ('.get_penznem().')</th>
                                <th class="r">Rendelt mennyiség</th>
                                <th>Mennyiségi egység</th>
                                <th class="r">Beérkezett mennyiség</th>
                            </tr>
                    ');

                    foreach($sorok as $sor) {
                        $szamla_html .= nl2('
                            <tr >
                                <td class="l">'.$sor['cikk_id'].'</td>
                                <td class="l">'.$sor['nev'].'</td>
                                <td class="r">'.number_format($sor['rndear'], 4, ',', ' ').'</td>
                                <td class="r">'.$sor['rndmen'].'</td>
                                <td>'.$sor['cikkmee'].'</td>
                                <td class="r">'.number_format($sor['erkmen'], 2, ',', ' ').'</td>
                            </tr>
                        ');
                    }

                    $szamla_html .= '</table>';
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

        // Anyagigénylés adatlapja
        }else{

            //Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
            if(isset($_GET['id_hidden'])){
                $_GET['id'] = $_GET['id_hidden'];
            }

            $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, 'recid='.$_GET['id']);

            if( $sor['datetime'] ) {
                $_SESSION['formMod'] = array(
                    'datetime'  => $sor['update_dt'],
                    'oldal'     => basename(self())
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

            if( !$csak_szerkesztheto ) {
                // Törölhet, ha van hozzá joga és a bejegzés folyamabtan van (tehát nem törölt, sem lezárt)
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

            if( _jog(JOG_ANYAGIGENYLES_JOVAHAGY) ) {
                $szamla_html .= nl2('
                                    <div id="jovahagy_ertek" class="b"></div>
                                    ');
            }

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
                    <div id="naploSor-'.$sor['recid'].'" class="dtr nSor kijSor" data-id="'.$sor['recid'].'" data-i="'.$i.'">
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
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
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
