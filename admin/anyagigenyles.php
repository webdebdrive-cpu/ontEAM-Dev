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

if( !_jog(12) ) {
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
$akt_tomb               = $anyagigenyles_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'anyagigénylést';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

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
$formazatlan_szam       = array('nev', 'csz', 'tel', 'fax', 'mobil');   //Azok az oszlopneveket sorold fel, melyek numerikus értéket is tartalmazhatnak, de nem szeretnéd, ha lista nézetben ezres csoportra bontaná

$szamla_html = '';

require_once 'anyagigenyles_nyomtat.php';


if (isset($_POST['f_anyag_rendelkezesre_all'])) {
    
    //Tranzakció indul
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    //Az érintett munkalap anyagok státuszait 0-ra állítjuk
    $sql = $adatbazis_kapcsolat->u('munkalap_anyag', array('stat_id'), array(DISZPNAG_STAT_VAN_ANYAG), "`telephely_id`='{$_POST['telephely_id']}' AND `cikk_id`='{$_POST['cikk_id']}'");
    if( $sql === false ) {
        $db_error = true;
    }
    
    $munkalapok = $adatbazis_kapcsolat->s('munkalap_id', 'munkalap_anyag', "`telephely_id`='{$_POST['telephely_id']}' AND `cikk_id`='{$_POST['cikk_id']}'");
    $munkalapok = adatbazis2tomb($munkalapok, 'munkalap_id');

    //Lefuttatjuk a munkalap_folyamatbanra_halehet függvényt, ezzel ha lehet, folyamatbanra állítva a munkalap státuszát
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


//Több kép menedzseléséhez
$tobb_kep = false; // Ha true, akkor js/jquery-ui.js, js/admin_sorrendez.js aktivál és minify!, admin/tobb_kep.php aktivál
if($tobb_kep){
    function get_tobbkep_darab()    { return 10;        } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return 'true';    } // Lényeges, hogy egy string legyen
    function get_tobbkep_kepmeret() { return 10;        } // MB-ban
    function get_tobbkep_oszlopnev(){                     // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return 'kepek';       } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

    //Kép törlésének lekezelése
    if (isset($_GET[$torles.'tobbkep_torol'])){

        $tobbkep = $adatbazis_kapcsolat -> s('kepnev', get_tobbkep_tabla(), get_tobbkep_oszlopnev().'='.$_GET[$torles.'tobbkep_torol']);
        foreach($tobbkep as $sor_tobbkep){

            //eredeti fájl törlése
            if(is_file('../'.get_utvonal().$sor_tobbkep['kepnev'])){
                unlink('../'.get_utvonal().$sor_tobbkep['kepnev']);
            }

            //ha van kiterjesztése
            if(strrpos($sor_tobbkep['kepnev'], '.')>0){
                remover('../'.get_utvonal().substr($sor_tobbkep['kepnev'],0,strrpos($sor_tobbkep['kepnev'],'.')).'_*');
            }else{
                remover('../'.get_utvonal().$sor_tobbkep['kepnev'].'_*');
            }
        }

        $adatbazis_kapcsolat -> delete(get_tobbkep_tabla(), get_tobbkep_oszlopnev().'='.$_GET[$torles.'tobbkep_torol']);

        if(isset($_GET['noload'])){
            $weblap -> leallit();
            exit();
        }
    }

}

/** Események */
if(isset($_GET['siker']) && isset($_GET['modosit'])){

}

/* Árazás */
if( isset($_POST['arazas']) ) {
    $idk = json_decode($_POST['idk'], true);
    $munkalapID = $_POST['munkalapID'];

    if( !_jog(15) ) {
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
        $sql = $csoportok = $adatbazis_kapcsolat->s('csoport_id', 'anyagigenyles', "`recid` IN (".implode(',', $idk).")", '', '');
        if( $sql === false ) {
            $db_error = true;
        }

        $csoportok = adatbazis2tomb($csoportok, 'csoport_id');
        $csoportok_unique = array_unique($csoportok);

        $kijelolt_db = array_count_values($csoportok);

        foreach( $csoportok_unique as $csu ) {
            if( $csu > 0 ) {
                $sql = $csoport_db = $adatbazis_kapcsolat->s('csoport_id', 'anyagigenyles', "`csoport_id` = '{$csu}'", '', '');

                if( $sql === false ) {
                    $db_error = true;
                }

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
            $sql = $statusz = $adatbazis_kapcsolat->s('recid stat_id insert_dt', 'anyagigenyles_statusz', 'id='.$id, 'recid desc', '1');
            if( $sql === false ) {
                $db_error = true;
            }

            if( $statusz ) {
                $statusz = $statusz[0];

                if( $statusz['stat_id'] != 5 ) {

                    $sql = $statusz_text = $adatbazis_kapcsolat->s('nev', 'anyagigenyles_statusz_text', "`recid` = '{$statusz['stat_id']}'", '', '1');
                    $statusz_text = $statusz_text[0];
                    if( $sql === false ) {
                        $db_error = true;
                    }

                    $sql = $bejelentes = $adatbazis_kapcsolat->s('ikt_szam', 'anyagigenyles', "`recid` = '{$id}'", '', '');
                    if( $sql === false ) {
                        $db_error = true;
                    }

                    $hiba_kieg = '';
                    if( $bejelentes ) {
                        $bejelentes = $bejelentes[0];

                        $hiba_kieg = '<br>A "'.$bejelentes['ikt_szam'].'" anyagigénylés státusza: '.$statusz_text['nev'];
                    }

                    echo json_encode(array(
                        'hiba' => 'Árazni csak nyitott státuszú anyagigénylést lehet!'.$hiba_kieg
                    ));
                    $weblap->leallit();
                    exit();

                }else{

                    //Ha van eltérés az előző bejegyzéshez képest (üres mező megengedett) akkor hibaüzenet:
                    $igenyles = $adatbazis_kapcsolat->s('cikktorzs_id szallito kcs_21 rndear igmen rndmen', 'anyagigenyles', "`recid`='{$id}'");
                    $igenyles = $igenyles[0];
                    if(
                            ($elozo_cikktorzs_id && $igenyles['cikktorzs_id'] && $elozo_cikktorzs_id != $igenyles['cikktorzs_id']) ||
                            ($elozo_szallito     && $igenyles['szallito']     && $elozo_szallito     != $igenyles['szallito']    ) ||
                            ($elozo_kcs_21       && $igenyles['kcs_21']       && $elozo_kcs_21       != $igenyles['kcs_21']      ) ||
                            ($elozo_rndear       && $igenyles['rndear']       && $elozo_rndear       != $igenyles['rndear']      )
                        ){
                        echo json_encode(array(
                            'hiba' => 'Árazni csak azonos cikkeket, azonos szállítóval, pénznemmel és egységárral lehet!'.$hiba_kieg
                        ));
                        $weblap->leallit();
                        exit();
                    }
                    $elozo_cikktorzs_id = $igenyles['cikktorzs_id'];
                    $elozo_szallito     = $igenyles['szallito'];
                    $elozo_kcs_21       = $igenyles['kcs_21'];
                    $elozo_rndear       = $igenyles['rndear'];

                    //Ha ki van töltve a szállító (mivel kötelező mezők, ekkor ki lesz töltve a pénznem és rend. egységár is, akkor azt az ID-t töltjük be a popupba
                    if($igenyles['szallito']){
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

        $szallito_txt = $adatbazis_kapcsolat->s('nev', 'szallito', "`recid`='{$igenyles['szallito']}'");
        $szallito_txt = $szallito_txt[0];
        $popup = nl2('
                <p class="piros b" id="popup_hiba"></p>

                <div class="l">
                    <table class="popup" style="font-weight: normal; font-size: 1.2rem;">
                    <tr>
                        <td class="p r">Cikk:</td>
                        <td class="p" colspan="3">'.$igenyles['cikktorzs_id'].'</td>
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
                            <input type="hidden" name="pop_szallito" id="pop_szallito" value="'.$igenyles['szallito'].'">
                            <script>
                            $(document).ready(function() {
                                $(\'#pop_szallito_autocomp\').autocomplete({
                                    \'php_fajl\'        : \'../autocomplete.php\',
                                    \'tabla\'           : \'szallito\',
                                    \'oszlop_keres\'    : \'nev\',
                                    \'oszlop_listaz\'   : \'nev\',
                                    \'id_kiir\'         : \'recid\',
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
                        <td class="p"><input type="text" name="pop_arfolyam" id="pop_arfolyam" class="f_num" data-tort="4" value="'.number_format($igenyles['arfolyam'], 4, ',', ' ').'"></td>
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
                            <select'.$szall_sel_disabled.' id="pop_erkcimid" name="pop_erkcimid" onchange="if( $(this).val() ){ $(\'#pop_dbf\').prop(\'checked\', false); }">
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
                            $(\'#pop_arfolyam\').attr(\'disabled\', 1).val(\'0,0000\');
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

//Áazás mentése
if ($_POST['arazas_ment']) {
    $_POST['ig_menny']  = str_replace(array(' ', ','), array('', '.'), $_POST['ig_menny']);
    $_POST['rndear']    = str_replace(array(' ', ','), array('', '.'), $_POST['rndear']);
    $_POST['rndmen']    = str_replace(array(' ', ','), array('', '.'), $_POST['rndmen']);
    $_POST['vrndear']   = str_replace(array(' ', ','), array('', '.'), $_POST['vrndear']);
    $_POST['arfolyam']  = str_replace(array(' ', ','), array('', '.'), $_POST['arfolyam']);
    $idk = explode(',', $_POST['idk']);
    
    //Hibakezelés
    if(!$_POST['szallito']){
        echo json_encode(
                    array(
                        'hiba' => 'Kérjük adja meg a szállítót!',
                        'focus' => 'pop_szallito'
                    )
            );
        $weblap -> leallit();
        exit();
    }
    if($_POST['ig_menny'] < $_POST['rndmen']){
        echo json_encode(
                    array(
                        'hiba' => 'A rendelt mennyiség nem lehet több az igényelt mennyiségtől!',
                        'focus' => 'pop_rndmen'
                    )
            );
        $weblap -> leallit();
        exit();
    }
    if($_POST['ig_menny'] > $_POST['rndmen'] && !$_POST['indok']){
        echo json_encode(
                    array(
                        'hiba' => 'Kérjük adja meg a rendelési mennyiség<br>megváltoztatásának indoklását!',
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
        $sql = $adatbazis_kapcsolat->u('anyagigenyles',     array('szallito',         'kcs_21',         'rndear',           'vrndear',          'arfolyam',          'atvaltva_d',           'erkcimid'        ),
                                                            array($_POST['szallito'], $_POST['kcs_21'], $_POST['rndear'],   $_POST['vrndear'],   $_POST['arfolyam'], $_POST['atvaltva_d'],   $_POST['erkcimid']),
                                        "`recid` IN (".$_POST['idk'].")");
        
    //Egy sor esetén a rendelt mennyiséget is felülírjuk
    }else{
        $sql = $adatbazis_kapcsolat->u('anyagigenyles',     array('szallito',         'kcs_21',         'rndear',           'rndmen',           'vrndear',          'arfolyam',          'atvaltva_d',         'erkcimid'        ),
                                                            array($_POST['szallito'], $_POST['kcs_21'], $_POST['rndear'],   $_POST['rndmen'],   $_POST['vrndear'],   $_POST['arfolyam'], $_POST['atvaltva_d'], $_POST['erkcimid']),
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

//Küldés jóváhagyásra
if( isset($_POST['kuldes_jovahagyasra']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    //hatása, hogy Árazva státuszt kap
    $nyitott_statid = 5;
    $arazva_statid  = 20;
    
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

            $sql = $statusz = $adatbazis_kapcsolat->s('recid stat_id insert_dt', $page.'_statusz', 'id='.$id, 'recid desc', '1');
            if( $sql === false ) {
                $db_error = true;
            }

            if( $statusz ) {

                $statusz = $statusz[0];
                if(  $statusz['stat_id']!=$nyitott_statid ) {
                    echo json_encode(array(
                        'hiba' => 'Csak nyitott státuszú anyagigényléseket lehet jóváhagyásra küldeni!'
                    ));

                    $weblap->leallit();
                    exit();
                }
                
                //Valóban árazva van-e? Szállító és egységár megadva
                $igenyles = $adatbazis_kapcsolat->s('szallito rndear kcs_21', 'anyagigenyles', "`recid`='$id'");
                $igenyles = $igenyles[0];
                if(!$igenyles['szallito'] || !$igenyles['rndear'] || !$igenyles['kcs_21']){
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

        //Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if (isset($_POST['csak_kerdezunk'])) {

            echo json_encode(array(
                'hiba' => 'Biztos jóváhagyásra küldi kijelölt anyagigényléseket?',
                'gomb' => '<input class="gomb piros" value="Igen" onclick="anyagigenyles_kuldes_jovahagyasra();" type="button"><input class="gomb zold" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        foreach($idk as $id) {
            $statusz = addStatusz( $page.'_statusz', $id, $arazva_statid );

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

//Küldés jóváhagyásra
if( isset($_POST['anyagigenyles_jovahagy']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    //hatása, hogy Jóváhagyva státuszt kap illetve pár mezőt kitöltünk még az adatbázisban
    $arazva_statid      = 20;
    $jovahagyva_statid  = 25;
    
    $_POST['jhmen'] = str_replace(array(' ', ','), array('', '.'), $_POST['jhmen']);
    
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
                        'hiba' => 'Csoportosított anyagigénylés önmagában nem hagyható jóvá. Előbb vegye ki a csoportból!'
                    ));

                    $weblap->leallit();
                    exit();
                }
            }
        }
        
        // Ellenőrizzük a státuszokat, és feltételeket
        foreach($idk as $id) {

            $statusz = $adatbazis_kapcsolat->s('recid stat_id insert_dt', $page.'_statusz', 'id='.$id, 'recid desc', '1');
            if( $statusz === false ) {
                $db_error = true;
            }

            if( $statusz ) {
                $statusz = $statusz[0];
                if(  $statusz['stat_id']!=$arazva_statid ) {
                    echo json_encode(array(
                        'hiba' => 'Csak árazva státuszú anyagigényléseket lehet jóváhagyni küldeni!'
                    ));

                    $weblap->leallit();
                    exit();
                }
                
                //Jóváhagyott mennyiség ellenőrzése
                $igenyles = $adatbazis_kapcsolat->s('rndmen cikkmee', 'anyagigenyles', "`recid`='$id'");
                $igenyles = $igenyles[0];
                if($_POST['jhmen']==0 || $_POST['jhmen']==0.00){
                    echo json_encode(array(
                        'hiba'  => 'A jóváhagyott mennyiség nem lehet 0  '.$igenyles['cikkmee'].'!',
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
                        'hiba'  => 'A jóváhagyott mennyiség nem lehet több, mint a jóváhagyásra váró mennyiség ('.$igenyles['rndmen'].' '.$igenyles['cikkmee'].')!',
                        'focus' => 'jhmen'
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
                'hiba' => 'Biztosan jóváhagyja az anyagigénylést?',
                'gomb' => '<input class="gomb piros" value="Igen" onclick="anyagigenyles_jovahagy();" type="button"><input class="gomb zold" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        foreach($idk as $id) {
            
            //Státusz léptetés jóváhagyvára
            $statusz = addStatusz( $page.'_statusz', $id, $jovahagyva_statid );
            if( $statusz === false ) {
                $db_error = true;
            }

            $sql = $adatbazis_kapcsolat->u('anyagigenyles', array('jhmen'), array($_POST['jhmen']), "`recid`='$id'");
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

//Anyagigénylés elutasítása
if( isset($_POST['anyagigenyles_elutasitas']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    //hatása, hogy Jóváhagyva státuszt kap illetve pár mezőt kitöltünk még az adatbázisban
    $arazva_statid      = 20;
    $elutasitva_statid  = 91;
    
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
                        'hiba' => 'Csoportosított anyagigénylés önmagában nem utasítható el. Előbb vegye ki a csoportból!'
                    ));

                    $weblap->leallit();
                    exit();
                }
            }
        }
        
        // Ellenőrizzük a státuszokat, és feltételeket
        foreach($idk as $id) {

            $statusz = $adatbazis_kapcsolat->s('recid stat_id insert_dt', $page.'_statusz', 'id='.$id, 'recid desc', '1');
            if( $statusz === false ) {
                $db_error = true;
            }

            if( $statusz ) {
                $statusz = $statusz[0];
                if(  $statusz['stat_id']!=$arazva_statid ) {
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
                                <br><textarea id="pop_indoklas" style="width: 37rem; margin-top: 1rem"></textarea>
                            '),
                'gomb' => '<input class="gomb piros" value="Igen" onclick="anyagigenyles_elutasitas();" type="button"><input class="gomb zold" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        foreach($idk as $id) {
            
            //Státusz léptetés jóváhagyvára
            $statusz = addStatusz( $page.'_statusz', $id, $elutasitva_statid );
            if( $statusz === false ) {
                $db_error = true;
            }

            $sql = $adatbazis_kapcsolat->u('anyagigenyles', array('jhmen'), array(0), "`recid`='$id'"); 
            if( $sql === false ) {
                $db_error = true;
            }
            
            if( addMegj('anyagigenyles', $id, $_POST['oka']) === false ) {
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

//Anyagigénylés elutasítása
if( isset($_POST['anyagigenyles_mind_jovahagy']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    //hatása, hogy Jóváhagyva státuszt kap illetve pár mezőt kitöltünk még az adatbázisban
    $arazva_statid      = 20;
    $jovahagyva_statid  = 25;
    
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

        //Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if (isset($_POST['csak_kerdezunk'])) {

            $ertek = 0;
            $igenylesek = $adatbazis_kapcsolat->s('igmen rndear', 'anyagigenyles', "`statusz_id`=20");
            foreach ($igenylesek as $igeny){
                $ertek += $igeny['igmen']*$igeny['rndear'];
            }
            
            echo json_encode(array(
                'hiba' => nl2('
                                Jóváhagyásra váró érték: '.number_format($ertek, 2, ',', ' ').' '.get_penznem().'
                                <br>Folytatja?
                            '),
                'gomb' => '<input class="gomb piros" value="Igen" onclick="anyagigenyles_mind_jovahagy();" type="button"><input class="gomb zold" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        foreach($idk as $id) {
            
            //Státusz léptetés jóváhagyvára
            $igenylesek = $adatbazis_kapcsolat->s('recid rndmen', 'anyagigenyles', "`statusz_id`=20");
            foreach ($igenylesek as $igeny){
                
                $sql = $adatbazis_kapcsolat->u('anyagigenyles', array('jhmen'), array($igeny['rndmen']), "`recid`='{$igeny['recid']}'");
                if( $sql === false ) {
                    $db_error = true;
                }
                
                $statusz = addStatusz( $page.'_statusz', $igeny['recid'], $jovahagyva_statid );
                if( $statusz === false ) {
                    $db_error = true;
                }
            }
            
            if( addMegj('anyagigenyles', $id, $_POST['oka']) === false ) {
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


//Rendelés készítése
if( isset($_POST['rendeles_keszit']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    //hatása, hogy Megrendelve státuszt kap
    $jovahagyva_statid  = 25;
    $megrendelve_statid = 30;
    
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
                        'hiba' => 'Csoportosított anyagigénylésből önmagában nem készíthető rendelés. Előbb vegye ki a csoportból!'
                    ));

                    $weblap->leallit();
                    exit();
                }
            }
        }

        // Ellenőrizzük a státuszokat és hogy mind jóváhagyva van-e?
        foreach($idk as $id) {

            $sql = $statusz = $adatbazis_kapcsolat->s('recid stat_id insert_dt', $page.'_statusz', 'id='.$id, 'recid desc', '1');
            if( $sql === false ) {
                $db_error = true;
            }

            if( $statusz ) {

                $statusz = $statusz[0];
                if(  $statusz['stat_id']!=$jovahagyva_statid ) {
                    echo json_encode(array(
                        'hiba' => 'Csak jóváhagyva státuszú anyagigénylésekből lehet rendelést készíteni!'
                    ));

                    $weblap->leallit();
                    exit();
                    
                }else{

                    //Ha van eltérés az előző bejegyzéshez képest (üres mező megengedett) akkor hibaüzenet:
                    $igenyles = $adatbazis_kapcsolat->s('cikktorzs_id szallito kcs_21 rndear igmen rndmen', 'anyagigenyles', "`recid`='{$id}'");
                    $igenyles = $igenyles[0];
                    if(
                            ($elozo_szallito     && $igenyles['szallito']     && $elozo_szallito     != $igenyles['szallito']    ) ||
                            ($elozo_kcs_21       && $igenyles['kcs_21']       && $elozo_kcs_21       != $igenyles['kcs_21']      )
                        ){
                        echo json_encode(array(
                            'hiba' => 'Csak azonos szállítóval és pénznemmel rendelkező anyagigénylésekből lehet rendelést készíteni!'
                        ));
                        $weblap->leallit();
                        exit();
                    }
                    $elozo_szallito     = $igenyles['szallito'];
                    $elozo_kcs_21       = $igenyles['kcs_21'];

                    //Ha ki van töltve a szállító (mivel kötelező mezők, ekkor ki lesz töltve a pénznem és rend. egységár is, akkor azt az ID-t töltjük be a popupba
                    if($igenyles['szallito']){
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

        //Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if ($_POST['csak_kerdezunk']) {

            echo json_encode(array(
                'hiba' => count($idk).' db anyagigénylés van kijelölve.<br>Biztos, hogy elkészíti a megrendelést?',
                'gomb' => '<input class="gomb piros" value="Igen" onclick="anyagigenyles_rendeles_keszit();" type="button"><input class="gomb zold" value="Nem" onclick="alert2()" type="button">'
            ));
            
            $weblap->leallit();
            exit();
        }
        

        //Ha még nem kaptunk utasítást arra, hogy mehet adatbáziba, akkor a nyomtatási képeket ajánjuk fel
        if ($_POST['mehet_db_be']!='mehet') {
            
            mysqli_query($db_kapcs, 'SET AUTOCOMMIT=0');
            $sorszam = mysqli_query($db_kapcs, 'SELECT `sorszam`+1 FROM `'.get_db_tabla().'iktszam_seq` WHERE `tabla`=\'cikkrendfej\' AND `ev`=\''.date('y').'\' FOR UPDATE;');
            $sorszam = mysqli_fetch_array($sorszam);
            $sorszam = 'R'.date('y').'/'.sprintf('%04d', $sorszam[0]).'|'.$_POST['idk'];
            $sorszam_kodolva = trim(base64_encode($sorszam), '=');

            echo json_encode(array(
                'hiba'      => nl2('
                                    <div class="l">
                                        Válasszon nyomtatási képet:
                                        <br><a target="_blank" href="'.self().'?nyomtat='.$sorszam_kodolva.'&amp;szall">Szállító</a>
                                        <br><a target="_blank" href="'.self().'?nyomtat='.$sorszam_kodolva.'&amp;cim">Cím</a>
                                        <br><a target="_blank" href="'.self().'?nyomtat='.$sorszam_kodolva.'&amp;oracle">Oracle cikkszám</a>
                                        <br>
                                        <br>Sikerült a nyomtatás?
                                    </div>
                                    '),
                'gomb'      => '<input class="gomb piros" value="Igen" onclick="anyagigenyles_rendeles_keszit(\'mehet\');" type="button"><input class="gomb zold" value="Nem" onclick="alert2()" type="button">',
                'felirat'   => 'Nyomtatás'
            ));
            
            $weblap->leallit();
            exit();
        }
        
        //Szekvencia táblából kiszámoljuk a rendelés következő sorszámát
        /* Megnézzük, hogy az adott évszámmal létezik e bejegyzés */
        $ikt_seq = $adatbazis_kapcsolat->s('ev sorszam', 'iktszam_seq', '`tabla`=\'cikkrendfej\' AND `ev`=\''.date('y').'\'');
        if( $ikt_seq === false ) {
            $db_error = true;
        }
        if( $ikt_seq ) {
            $ikt_seq = $ikt_seq[0];

            /* Sorszám */
            $sorszam = $ikt_seq['sorszam'];

            /* Sorszám növelés */
            $sorszam++;

            /* Letároljuk az új sorszámot */
            $sql = $adatbazis_kapcsolat -> u('iktszam_seq', array('sorszam'), array($sorszam), '`tabla`=\'cikkrendfej\' AND `ev`=\''.date('y').'\'');
            if( $sql === false ) {
                $db_error = true;
            }
        } else {
            /* Sorszám */
            $sorszam = 1;

            $sql = $adatbazis_kapcsolat -> i('iktszam_seq', array('sorszam', 'ev', 'tabla'), array($sorszam, date('y'), 'cikkrendfej'));
            if( $sql === false ) {
                $db_error = true;
            }
        }
        
        //Most kommitoljuk el a popupban lekért sorszámáot
        mysqli_query($db_kapcs, 'COMMIT');
     
        //Tesztelés idejére kommentezve
        /* Rendelés ID formázása */
        $rndid = 'R'.date('y').'/'.sprintf('%04d', $sorszam);
        $rnd_dt = date('Y-m-d H:i:s');
        $pdf_utvonal = 'doc/rendeles/'.date('y').'/'.$elozo_szallito.'/'.str_replace('/', '_', $rndid).'.pdf';
        
        //Létrehozunk egy új rendelésfej bejegyzést
        $cikkrendfej_id = $adatbazis_kapcsolat->i('cikkrendfej',    array('statusz_id', 'rndid', 'rnd_dt',  'kepfajl',     'szallitoid',    'insert_dt',          'insert_usr',                           'update_usr'                            ),
                                                                    array(1,            $rndid,  $rnd_dt,   $pdf_utvonal,  $elozo_szallito, date('Y-m-d H:i:s'),  $_SESSION[get_db_tabla().'teljes_nev'], $_SESSION[get_db_tabla().'teljes_nev']  )
                                                    );
        if( $cikkrendfej_id === false ) {
            $db_error = true;
        }
        
        /* Felvesszük az első státuszt */
        $sql = $adatbazis_kapcsolat -> i('cikkrendfej_statusz',
            array('id',             'stat_id', 'insert_dt',          'insert_usr',                              'update_usr'                          ),
            array($cikkrendfej_id,  1,         date('Y-m-d H:i:s'),  $_SESSION[get_db_tabla().'teljes_nev'],    $_SESSION[get_db_tabla().'teljes_nev'] )
        );
        if( $sql === false ) {
            $db_error = true;
        }

        //Anyagigénylés sorok updatelése (státuszt is, illetve egyéb mezőket is)
        foreach($idk as $id) {
            $statusz = addStatusz( $page.'_statusz', $id, $megrendelve_statid );
            if( $statusz === false ) {
                $db_error = true;
            }
            
            //Az anyagigénylés érintett sorait updateljük
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

//Anyagigénylés érkeztetése
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
        if(!$adatbazis_kapcsolat->s('recid', 'cikkrendfej', "`rndid`='{$_POST['rsz']}' AND `statusz_id`=1")){
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
        $igenylesek = $adatbazis_kapcsolat->s('recid telephely_id cikktorzs_id nev igmen rndmen erkmen cikkmee', 'anyagigenyles', "`aparecid`='{$recid['recid']}'");
        foreach($igenylesek as $igeny){

            $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', "`id`='{$igeny['telephely_id']}'");
            $telephely = $telephely[0];

            $gomb = '(nincs anyagra váró feladatok a diszpécser naplóban)';
            $gomb = '<input type="button" value="Mutat" class="gomb">';

            $html .= nl2('
                        <tr>
                            <td id="erkeztet_szall_'.$igeny['recid'].'">'.$telephely['nev'].'</td>
                            <td id="erkeztet_cikkid_'.$igeny['recid'].'">'.$igeny['cikktorzs_id'].'</td>
                            <td>'.$igeny['nev'].'</td>
                            <td class="r">'.number_format($igeny['igmen'], 2, ',', ' ').'</td>
                            <td class="r">'.number_format($igeny['rndmen'], 2, ',', ' ').'</td>
                            <td class="r" id="marerkmen_'.$igeny['recid'].'">'.number_format($XXX, 2, ',', ' ').'</td>
                            <td class="r"><input type="text" class="f_num erkmen" data-id="'.$igeny['recid'].'" id="erkmen_'.$igeny['recid'].'" value="'.number_format($igeny['erkmen'], 2, ',', ' ').'"></td>
                            <td class="c">'.$igeny['cikkmee'].'</td>
                            <td class="c">
                                <div class="cb">
                                    <input type="checkbox" class="nem_tobb_beerk" data-id="'.$igeny['recid'].'" id="nem_tobb_'.$igeny['recid'].'">
                                    <label for="nem_tobb_'.$igeny['recid'].'"></label>
                                </div>
                            </td>
                            <td class="c">'.$gomb.'</td>
                        </tr>
                        ');
        }
        $html .= '</table>';
    
        echo json_encode(array(
            'html' =>  $html
        ));
        
    }
    
    //Anyagigénylések feldolgozása
    if($_POST['idk']){
        
        $adatok = explode('|', $_POST['idk']);
        foreach($adatok as $adat){
            list($id, $mennyiseg, $pipa) = explode('#', $adat);
            //Ha nincs ID, nem is csinálunk semmit
            if($id){
                
                if(!$pipa && (!$mennyiseg || $mennyiseg=='0.00')){
                    echo $id . '<br>';
                }
                
            }
        }
        
    }

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

    if( isset($_GET['modosit']) || isset($_GET['tortenet']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
        /* Számla fülek */ 
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'tortenet'      => 'Történet',
            'dokumentumok'  => 'Dokumentumok',
            'megjegyzesek'  => 'Megjegyzések',
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
        
        //Újat csak "Anyagigénylés kezelése" joggal lehet
        if(_jog(13)){
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="submit" class="gombb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés">
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

        /* Anyagigénylés jóváhagyása */
        if( _jog(14) ) {
            $gombok .= nl2('
                <div id="kmCont">
                    <input type="button" class="gombb foGomb" value="Jóváhagyás" onclick="anyagigenyles_jovahagy(\'csak kérdezünk még\')">
                    <input type="button" class="gombb foGomb" value="Elutasítás" onclick="anyagigenyles_elutasitas(\'csak kérdezünk még\')">
                    <input type="button" class="gombb foGomb" value="Mind jóváhagyása" onclick="anyagigenyles_mind_jovahagy(\'csak kérdezünk még\')">
                </div>
            ');
            //Ha jóváhagyás joga van, és a státusza árazva, akkor a jóváhagyott mennyiség enabled
            if($sor['statusz_id']==20){
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
                            $(\'#jovahagy_ertek\').html(\'Jóváhagyásra váró érték: \'+ezresek(jhmen*rndear, 2, \',\', \' \')+\' '.get_penznem().'\' );
                        }
                    </script>
                    ');
            }
        }
        /* Anyagigénylés árazása */
        if( _jog(15) ) {
            $gombok .= nl2('
                <div id="kmCont">
                    <div class="kijeloltek_muv">
                        <input type="button" class="gomb foGomb" value="Várólista..." title="Várólista...">
                        <div class="muv">
                            <ul>
                                <li><a onclick="varolistahozAdas(\'csak kérdezünk még\');">Várólistához adás</a></li>
                                <li><a onclick="varolistabolKivetel(\'csak kérdezünk még\');">Várólistból kivétel</a></li>
                            </ul>
                        </div>
                    </div>
                    <input type="button" class="gombb foGomb" value="Árazás" onclick="arazas()">
                    <input type="button" class="gombb foGomb" value="Küldés jóváhagyásra" onclick="anyagigenyles_kuldes_jovahagyasra(\'csak kérdezünk még\')">
                    <input type="button" class="gombb foGomb" value="Rendelés készítése" onclick="anyagigenyles_rendeles_keszit(\'csak kérdezünk még\')">
                </div>
            ');
        }
        /* Anyagigénylés érkeztetése */
        if( _jog(13) ) {
            $gombok .= nl2('
                <div id="kmCont">
                    <input type="button" class="gombb foGomb" value="Anyagok érkeztetése" onclick="csoportMuvelet(\'anyagigenyles_erkeztet\')">
                </div>
            ');
        }

        if( !$csak_szerkesztheto ) {
            //Jogosutságkezelés: csak az törölheti, aki létrehozta
            if($sor['loginnev'] != $_SESSION[get_db_tabla().'user']) {
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Csak saját bejegyzés törölhető!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
            } else {
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
            }
        }

        if( _jog(13) ){

            //Csak saját bejelentéseit szerkesztheti
            $nem_szerkesztheti = false;
            if($sor['loginnev']!=$_SESSION[get_db_tabla().'user']){
                $nem_szerkesztheti = true;
            }

            if(!$nem_szerkesztheti && isset($_GET['modosit'])){
                $gombok = nl2('
                    <div class="form_kuldes">
                        <input type="submit" class="gombb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés">
                    </div>
                ').$gombok;
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

	//Új termék felvitele
	if(isset($_GET['uj']) && _jog(13) && !$csak_szerkesztheto){

        $akt_tomb = $anyagigenyles_tomb_feltolt;
        
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

        $muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 16).'px);"';
        if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
            if( $_COOKIE['csuszkaSzelesseg'] ) {
                $muv_style = ' style="width: calc(100% - '.($_COOKIE['csuszkaSzelesseg'] + 16).'px);"';
            }
        }

	    $form_mit     = array('{JS}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{CK_EDITOR}', '{GOMBOK}', '{MUV_STYLE}'   );
	    $form_mivel   = array($js,    self(),     $mezok,    $megnevezes,    $ckeditor,     $gombok,    $muv_style      );
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
                    
                    //Azon belül anyagonként járjuk be
                    $anyagok = $adatbazis_kapcsolat->s('distinct cikk_id', 'munkalap_anyag', "`munkalap_id` IN (".$munkalapok.") AND `stat_id`='".DISZPNAG_STAT_ANYAGRA_VAR."' AND `telephely_id`='{$telephely['telephely_id']}'");
                    foreach($anyagok as $anyag){
//echo $adatbazis_kapcsolat->gp() . '<br>';
                        
                        //A mutat linkbe csak azokat a munkalapokID-kat kell fevenni, ami adott anyag/státusz/telephelyhez tartozik
                        $akt_munkalapok = $adatbazis_kapcsolat->s('distinct munkalap_id', 'munkalap_anyag', "`cikk_id`='{$anyag['cikk_id']}' AND `stat_id`='".DISZPNAG_STAT_ANYAGRA_VAR."' AND `telephely_id`='{$telephely['telephely_id']}'");
                        $akt_munkalapok = adatbazis2tomb($akt_munkalapok, 'munkalap_id');
                        $akt_munkalapok = implode(',', $akt_munkalapok);
                        
                        //Igénylésre váró darabszám kiszámítása
                        $ig_var = 0;
                        $akt_anyagok = $adatbazis_kapcsolat->s('recid cikk_nev cikk_id szuk_men cikk_mee', 'munkalap_anyag', "`munkalap_id` IN (".$munkalapok.") AND `cikk_id`='{$anyag['cikk_id']}' AND `stat_id`='".DISZPNAG_STAT_ANYAGRA_VAR."' AND `telephely_id`='{$telephely['telephely_id']}'");
                        foreach($akt_anyagok as $akt_anyag){
                            $recid_egyedi_azon  = $akt_anyag['recid'];
                            $cikk_nev           = $akt_anyag['cikk_nev'];
                            $cikk_id            = $akt_anyag['cikk_id'];
                            $cikk_mee           = $akt_anyag['cikk_mee'];
                            $ig_var            += $akt_anyag['szuk_men'];
                        }

                        //Anyagra váró darabszám kiszámítása (ugyanaz, csak másik státuszt figyelünk)
                        $anyag_var = 0;
                        $akt_anyagok = $adatbazis_kapcsolat->s('szuk_men', 'munkalap_anyag', "`munkalap_id` IN (".$munkalapok.") AND `stat_id`='".DISZPNAG_STAT_ANYAG_IGENYELVE."' AND `telephely_id`='{$telephely['telephely_id']}'");
                        foreach($akt_anyagok as $akt_anyag){
                            $anyag_var += $akt_anyag['szuk_men'];
                        }

                        //Rendelés folyamatban kiszámítása: az összes olyan anyagigénylés, ahol adott anyag cikkszámmal dolgozunk és a státusza nem "törölt" és nem "lezárt"
                        $folyamatban = 0;
                        $folyamatbanok = $adatbazis_kapcsolat->s('igmen', $megnevezes, "`cikktorzs_id`='$cikk_id' AND `telephely_id`='{$telephely['telephely_id']}' AND `statusz_id` NOT IN (50, 51)");
                        foreach($folyamatbanok as $foly){
                            $folyamatban += $foly['igmen'];
                        }

                        $szalloda = $adatbazis_kapcsolat->s('nev', 'telephely', "`id`='{$telephely['telephely_id']}'");
                        $szalloda = $szalloda[0];

                        $mar_igenyelve = '(nincs igénylés)';
                        if($folyamatban){
                            $mar_igenyelve = '<input type="button" value="Már igényelve" class="gomb">';
                        }

                        $anyagok_html .= nl2('
                                        <tr>
                                            <td>'.$szalloda['nev'].'</td>
                                            <td id="cikk_id_'.$recid_egyedi_azon.'">'.$cikk_id.'</td>
                                            <td id="cikk_nev_'.$recid_egyedi_azon.'">'.$cikk_nev.'</td>
                                            <td class="r piros" id="th'.$telephely['telephely_id'].'a'.$cikk_id.'">'.number_format($ig_var, 2, ',', ' ').'</td>
                                            <td class="r">'.number_format($anyag_var, 2, ',', ' ').'</td>
                                            <td class="r zold">'.number_format($folyamatban, 2, ',', ' ').'</td>
                                            <td id="th'.$telephely['telephely_id'].'a'.$cikk_id.'_mee">'.$cikk_mee.'</td>
                                            <td><input type="button" value="Mutat" class="gomb" onclick="window.open(\'munkalap.php?recidk='.$akt_munkalapok.'\')"></td>
                                            <td><input type="button" value="Igénylés" class="gomb f_anyagigenyles_mezok_kitolt" data-recid_egyedi_azon="'.$recid_egyedi_azon.'" data-telephely_id="'.$telephely['telephely_id'].'"></td>
                                            <td>'.$mar_igenyelve.'</td>
                                            <td><input type="button" value="Anyag rendelkezésre áll" class="gomb f_anyag_rendelkezesre_all" data-telephely_id="'.$telephely['telephely_id'].'" data-cikk_id="'.$cikk_id.'"></td>
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
                                            <th>Munkalapon igénylésre vár</th>
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

	//Termék módosítása
	}elseif(isset($_GET['modosit'])) {

        //Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
        }

	    $sor = $adatbazis_kapcsolat -> select('*', $megnevezes, 'recid='.$_GET['id']);
		$sor = $sor[0];

        if( $sor['datetime'] ) {
            $_SESSION['formMod'] = array(
                'datetime'  => $sor['update_dt'],
                'oldal'     => basename(self())
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

        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($sor).'\'); </script>';

        if( !$csak_szerkesztheto ) {
            //Törölhet, ha van hozzá joga és a bejegzés folyamabtan van (tehát nem törölt, sem lezárt)
            if(_jog(13)){
                $nincs_folyamatban_statuszok = array(5, 20);
                if(!in_array($sor['statusz_id'], $nincs_folyamatban_statuszok)){
                    $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Az anyagigénylés nem törölhető, mert a státusza nem NYITOTT, ÁRAZVA vagy VÁRAKOZÓ!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
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

        if(isset($_GET['ujkent'])){
			foreach($akt_tomb as $ertek){
				$sor[$ertek['name']] = $_SESSION[$ertek['name']];
			}
		}

        //Ckeditort csak akkor húzzuk be, ha kell
        if(is_file($ckeditor_js)){
            $ckeditor = '<script src="'.$ckeditor_js.'"></script>';
        }

		// Ez elkészíti a $mezok változót
		require('switch.php');

        $jobb_oldal = '';

        $muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 16).'px);"';
        if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
            if( $_COOKIE['csuszkaSzelesseg'] ) {
                $muv_style = ' style="width: calc(100% - '.($_COOKIE['csuszkaSzelesseg'] + 16).'px);"';
            }
        }

	    $szamla_html .= str_replace(
            array('{JS}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}', '{MUV_STYLE}'),
            array( $js,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok,    $muv_style  ),
            fajlbol_olvas('../sablon/admin/form_modosit.html', true)
        );
        
        if( _jog(14) ) {
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

	/* Történet */
    }elseif(isset($_GET['tortenet'])) {

        $szamla_html .= nl2('
            <div class="dt naplo">
                <div class="dtr nFejlec">
                    <div class="dtc">Státusz</div>
                    <div class="dtc">Kelte</div>
                    <div class="dtc">Felhasználó</div>
                    <div class="dtc">Státuszidő</div>
                    <div class="dtc">Göngyölt státuszidő</div>
                    <div class="dtc">Megjegyzés</div>
                    <!--<div class="dtc">Összesített státuszidő</div>-->
                </div>
        ');

        $sorok = $adatbazis_kapcsolat->s('stat_id insert_dt insert_usr statido gongyolt_statido ossz_statido text', $megnevezes.'_statusz', "`id` = '".$_GET['id']."'", 'insert_dt', '');
        if( $sorok ) {
            foreach($sorok as $sor) {
                $statusz = $adatbazis_kapcsolat->s('nev szinkod', $megnevezes.'_statusz_text', 'recid='.$sor['stat_id'], '', '1');

                $szinkod = '';
                if( $statusz ) {
                    $statusz = $statusz[0];

                    $sor['statusz'] = $statusz['nev'];

                    if( $statusz['szinkod'] ) {
                        $szinkod = ' style="background: '.$statusz['szinkod'].';"';
                    }
                }

                //Adatbázisba eleinte szóköz nélkül került be a nap, ezért biztonság kedvéért egy szóközt még beszúrunk, illetve a 0nap-ot is kiszűrjük (bár ez egy adatbázis frissítés után már felesleges)
                $sor['statido']          = str_replace(array('0nap', 'nap'), array('', ' nap'), $sor['statido']);
                $sor['gongyolt_statido'] = str_replace(array('0nap', 'nap'), array('', ' nap'), $sor['gongyolt_statido']);
                $sor['ossz_statido']     = str_replace(array('0nap', 'nap'), array('', ' nap'), $sor['ossz_statido']);
                $szamla_html .= nl2('
                    <div class="dtr nSor"'.$szinkod.'>
                        <div class="dtc">'.$sor['statusz'].'</div>
                        <div class="dtc">'.date('Y.m.d H:i', strtotime($sor['insert_dt'])).'</div>
                        <div class="dtc">'.$sor['insert_usr'].'</div>
                        <div class="dtc">'.$sor['statido'].'</div>
                        <div class="dtc">'.$sor['gongyolt_statido'].'</div>
                        <div class="dtc">'.nl2br2($sor['text']).'</div>
                        <!--<div class="dtc">'.$sor['ossz_statido'].'</div>-->
                    </div>
                ');
            }
        }

        $szamla_html .= '</div>';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html
            ));
        } else {
            echo $szamla_html;
        }

	//Dokumentumok
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
                    <div id="naploSor-'.$sor['recid'].'" class="dtr nSor kijSor" data-id="'.$sor['recid'].'" data-i="'.$i.'">
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

	//Lista
	}elseif(isset($_GET['lista'])){

        //LEKÉRDEZÉS
        $where      = '';
        $oszlopok   = '';
        foreach($akt_tomb as $ertek){
            if($_GET[$ertek['name']]){
                $where.=' `'.$ertek['name'].'`=\''.$_GET[$ertek['name']].'\' AND';
            }
            if($ertek['show']){
                $oszlopok .= $ertek['name'].', ';
            }
        }
        //A használni kívánt oszlopokat itt tudod bővíteni
        $oszlopok .= 'recid, ';
        if($kep_thumb_listaba){
            $oszlopok .= $fajloszlop.', ';
        }

        //Ebben az esetben a gyorskeresőt általános keresőként használtuk
        if(isset($_GET[$gyorskereso_id])){
            $where_keres = '';
            foreach($akt_tomb as $ertek){
                if($ertek['type']=='text' || $ertek['type']=='textarea'){
                    $where_keres .= ' `'.$ertek['name'].'` LIKE \'%'.$_GET[$gyorskereso_id].'%\' OR';
                }
            }
        }
        if($where){
            $where = csonkit($where,4);
        }
        //Ha keresünk, akkor még kiegészítjük a WHERE feltételt
        if($where_keres){
            $where .= ' ('.csonkit($where_keres, 3).')';
        }

        //NAVIGÁCIÓ

        //Darabszám kiszámítása
        $bejegyzesek = $adatbazis_kapcsolat -> select('recid', $megnevezes, $where, '', '', '', true);
        $navi .= ' <span class="navi_db">(<span id="bejegyzesek_szama">'.$bejegyzesek.'</span> db. bejegyzés)</span>';

        if(!$csak_szerkesztheto){
            $gombok = '<input type="button" class="gombb jf jf3" onclick="location.href=\''.self().'?uj\'" value="Új" title="Új feltöltés">';
        }
        if($sorrendezes){
            $gombok .= ' <input type="button" class="gombb jf jf4" onclick="location.href=\''.$_SERVER['PHP_SELF'].'?sorrend\'" value="Sorrend" title="Sorrendezés">';
        }

        $navi_sablon = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit    = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel  = array($navi,             $gombok,    $gyk2);
        echo str_replace($navi_mit, $navi_mivel, $navi_sablon);

        //HIBAüZENETEK
        require_once('info.php');

        //Lapozás előkészítése
        require_once('../class/class.Lapozo.php');

        if(!$_GET['oldal']){
            $_GET['oldal'] = 1;
        }

        $lapozo = new Lapozo;
        $lapozo -> elem_osszes = $bejegyzesek;
        $lapozo -> mind_var         = ''; //'Összes elem kilistázása' változó neve. Ha üres akkor nincs össsez oldal megjelenítés. Alapértelmezetten 'osszes'
        //$lapozo -> epo_var         = 'epo';    //Elem per oldal változó neve
        //$lapozo -> oldal_var       = 'oldal';  //Oldal változó neve
        //$lapozo -> kozep_meret     = 9;        //Nagy oldalszám esetén a középső intervallum mérete
        //$lapozo -> max_fix_oldal   = 10;        //Ha a megadott számtól több oldal van, akkor jelenik meg az előző/követekző felirat
        //$lapozo -> epo_array       = array(50, 100, 150);  //Elem per oldal mennyiségek ha választhatóak, akkor egy tömbbyi interrel adhatjuk át az opciókat
        //$lapozo -> mind_text       = 'Összes oldal';  //pldalszámok mellett a "minden elem kilistázásának" felirata (ha $mind_var üres string akkor nem jelenik ez meg)
        //$lapozo -> elozo_title     = '« Előző oldal';        //'Előző' felirat, link title-jéhez
        //$lapozo -> kovet_title     = 'Következő oldal »';    //'Következő' felirat, link title-jéhez
        //$lapozo -> kozep_meret     = 'Aktuális oldal';       //'Aktuális oldal' felirat, link title-jéhez

        $utvonal = '../sablon/admin/';
        $lapozo -> sablon_elozo       = $utvonal.'lapozas_elozo.html';
        $lapozo -> sablon_kovetkezo   = $utvonal.'lapozas_kovet.html';
        $lapozo -> sablon_oldalak     = $utvonal.'lapozas_oldalak.html';
        $lapozo -> sablon_option      = $utvonal.'lapozas_option.html';
        $lapozo -> sablon_pontok      = $utvonal.'lapozas_pontok.html';
        $lapozo -> sablon_select      = $utvonal.'lapozas_select.html';

        //Számolás
        $lapozo -> lapozas();

        $lekerdezes = $adatbazis_kapcsolat -> select($oszlopok, $megnevezes, $where, $_GET['rend'].' '.$_GET['desc'], $lapozo -> getLimit());

        //GYORSKERESŐ
        echo $gyk;

        //SZŰKÍTÉS HA KELL
        echo szukites($akt_tomb, $megnevezes);

        //lapozas($sql,$_GET['lh']);

        //SQL
        if($bejegyzesek){

            //Form sablon
            $form_sablon = fajlbol_olvas('../sablon/admin/form_lista_torles_nelkul.html', true);
            $form_mit    = array('{ACTION}', '{FEJLECEK}', '{SOROK}', '{MEGNEVEZES}', '{TOBBKEP}', '{TOBBKEP_OSZLOP}');

            $fejlecek = '';
            if($kep_thumb_listaba){
                $fejlecek .= '<td class="fejlec">Kép</td>';
            }
            if($id_latszik){
                $fejlecek .= '<td class="fejlec">#</td>';
            }
            foreach($akt_tomb as $ertek){
                if($ertek['show']){

                    $fejlecek .= '<td class="fejlec">';

                    if($_GET['order'] == $ertek['name']){
                        if(isset($_GET['desc'])){
                            $fejlecek .= '<div class="nyil_le"></div>';
                        }else{
                            $fejlecek .= '<div class="nyil_fel"></div>';
                        }
                    }

                    $fejlecek .= '<a href="'.self().get_get_order($ertek['name']).'">'.$ertek['megn'].'</a></td>';

                }
            }

            if($lapozo -> getOldalakSzama() > 1){
                ?><fieldset class="c"><legend>Lapozás</legend><table class="auto"><tr><td><?php
                //Kiíratás
                echo $lapozo -> kiir();
                echo $lapozo -> epoSelectKiir();
                //echo $lapozo -> oldalakSelectKiir();
                ?></td></tr></table></fieldset><?php
            }

            //törléshez a JS script
            ?><script><?php
                ?>uzi_e = 'Biztosan törli a ';<?php
                ?>uzi_v = ' <?php echo $szoveg?>?';<?php
                ?>gomb_e = get_torles_gomb_e('<?php echo self().get_get()?>','<?php echo $torles?>');<?php
                ?>gomb_v = get_torles_gomb_v();<?php

                foreach($lekerdezes as $sor){
                    ?>uzi<?php echo $sor['recid']?>='<?php echo $sor['cikksz']?>'; gomb<?php echo $sor['recid']?>='<?php echo $sor['recid']?>&amp;noload\',\'sor<?php echo $sor['recid']?>\'';<?php
                }
            ?></script><?php

            //Sorok sablon
            $dn = '';
            if($csak_szerkesztheto){
                $dn = ' dn';
            }
            $sorok = '';
            $sorok_sablon = fajlbol_olvas('../sablon/admin/form_sorok_torles_nelkul.html', true);
            //$sorok_sablon = fajlbol_olvas('../sablon/admin/form_sorok_szinezett.html', true);

            $i = 0;
            foreach($lekerdezes as $sor) {
                $oszlopok = '';
                $link = self().get_get(array('id', 'id_hidden', 'lista', 'siker')).'&amp;id='.$sor['recid'].'&amp;modosit';

                if($kep_thumb_listaba){
                    $kep = kep_kiir('../'.get_utvonal().$sor[$fajloszlop], $sor['nev'], get_indexkep_sz(), get_indexkep_m(), 'b');
                    $oszlopok .= '<td'.$style.'>'.$kep.'</td>';
                }
                if($id_latszik){
                    $oszlopok .= '<td>#'.$sor['recid'].'</td>';
                }

                foreach($akt_tomb as $ertek){
                    if($ertek['show']){

                        $style = '';
                        if($ertek['lista_style']){
                            $style = ' style="'.$ertek['lista_style'].'"';
                        }

                        //Kategóriánál hasznos: az adott érték egy külső tábla azonosítája... így tudjuk összekapcsolni
                        if($ertek['kulso_tabla']){
                            list($tabla, $oszlop) = explode('|', $ertek['kulso_tabla']);
                            $kulso_ertek = $adatbazis_kapcsolat->s($oszlop, $tabla, "id={$sor[$ertek['name']]}");
                            $sor[$ertek['name']] = $kulso_ertek[0][$oszlop];
                        }

                        if($ertek['type'] == 'checkbox'){
                            $oszlopok .= '<td'.$style.'>';
                        }else{
                            $oszlopok .= '<td'.$style.' onclick="if(!onclick_disabled){location.href=\''.$link.'\';}">';
                        }

                        if(is_numeric($sor[$ertek['name']]) && !in_array($ertek['name'], $formazatlan_szam) && $ertek['type'] != 'checkbox'){
                            $oszlopok .= '<span id="inline_szerk_'.$ertek['name'].'_'.$sor['recid'].'_span" onclick="inline_szerk(\''.$ertek['name'].'\', \''.$sor['recid'].'\')">'.$ertek['pre'].'<span id="inline_szerk_'.$ertek['name'].'_'.$sor['recid'].'_text">'.number_format($sor[$ertek['name']],0,'','.').'</span>'.$ertek['post'].'</span>';
                            $oszlopok .= '<br><input class="dn" type="text" name="inline_szerk_'.$ertek['name'].'_'.$sor['recid'].'" id="inline_szerk_'.$ertek['name'].'_'.$sor['recid'].'" value="'.$sor[$ertek['name']].'" onblur="inline_szerk_ment(\''.$megnevezes.'\', \''.$ertek['name'].'\', \''.$sor['recid'].'\')">';
                        } else {
                            if($ertek['type'] == 'checkbox'){
                                $class = '';
                                if($sor[$ertek['name']]){
                                    $class = ' aktiv';
                                }
                                $oszlopok .= '<span class="pipa'.$class.'" id="pipa_'.$ertek['name'].'_'.$i.'" onclick="pipa_kapcsolo(\'pipa_'.$ertek['name'].'_'.$i.'\', \'pipa_'.$ertek['name'].'\', \''.$megnevezes.'lista\', \''.$sor['recid'].'\')">✔</span>';
                            }else{
                                $oszlopok .= '<span id="inline_szerk_'.$ertek['name'].'_'.$sor['recid'].'_span" onclick="inline_szerk(\''.$ertek['name'].'\', \''.$sor['recid'].'\')">'.$ertek['pre'].'<span id="inline_szerk_'.$ertek['name'].'_'.$sor['recid'].'_text" onclick="inline_szerk(\''.$ertek['name'].'\')">'.$sor[$ertek['name']].'</span>'.$ertek['post'].'</span>';
                                $oszlopok .= '<br><input class="dn" type="text" name="inline_szerk_'.$ertek['name'].'_'.$sor['recid'].'" id="inline_szerk_'.$ertek['name'].'_'.$sor['recid'].'" value="'.$sor[$ertek['name']].'" onblur="inline_szerk_ment(\''.$megnevezes.'\', \''.$ertek['name'].'\', \''.$sor['recid'].'\')">';
                            }
                        }

                        $oszlopok .= '</td>';
                    }
                }

                $szin = '';
                /*Pl:
                switch( $sor['statusz'] ){
                    case 'jelezni'     : $szin = 'sarga';  break;
                    case 'jelezve'     : $szin = 'lila';   break;
                    case 'elfogadva'   : $szin = 'zold';   break;
                    case 'kiszamlazva' : $szin = 'piros';  break;
                    case 'kifizetve'   : $szin = 'szurke'; break;
                }
                */

                $sorok_mit   = array('{I}', '{ID}',     '{DN}', '{SZIN}', '{LINK}', '{OSZLOPOK}');
                $sorok_mivel = array($i%2,  $sor['recid'], $dn,    $szin,    $link,    $oszlopok   );
                $sorok .= str_replace($sorok_mit, $sorok_mivel, $sorok_sablon);
                $i++;
            }

            //Ha többképes, akkor azt a törlésnél jelezni kell, úgy hogy postoljuk az oszlopot (több termék törlése egyszerre)
            $tobbkep = '';
            if($tobb_kep){
                $tobbkep = '<input type="hidden" name="oszlopnev" value="'.$tobbkep_oszlopnev.'">';
            }

            $form_mivel  = array(self().get_get(), $fejlecek, $sorok, $megnevezes, $tobbkep, $tobbkep_oszlopnev);

            echo str_replace($form_mit, $form_mivel, $form_sablon);

            if($lapozo -> getOldalakSzama() > 1){
                ?><fieldset class="c"><legend>Lapozás</legend><table class="auto"><tr><td><?php
                //Kiíratás
                echo $lapozo -> kiir();
                echo $lapozo -> epoSelectKiir();
                //echo $lapozo -> oldalakSelectKiir();
                ?></td></tr></table></fieldset><?php
            }

        }else{
            ?><h1>Jelenleg nem taláható felvitt adat!</h1><?php
        }

    //Sorrendezése
    }elseif(isset($_GET['sorrend'])){

        //NAVIGÁCIÓ

        //Darabszám kiszámítása
        $bejegyzesek = $adatbazis_kapcsolat -> select('recid', $megnevezes, '', '', '', '', true);
        $navi .= ' <span class="navi_db">(<span id="bejegyzesek_szama">'.$bejegyzesek.'</span> db. bejegyzés)</span>';

        $gombok = '';
        if(!$csak_szerkesztheto){
            $gombok .= '<input type="button" class="gombb jf jf4" onclick="location.href=\''.self().'?uj\'" value="Új" feltöltés" title="Új feltöltés">';
        }
        $gombok .= ' <input type="button" class="gombb jf jf5" onclick="location.href=\''.self().get_get(array('sorrend')).'&amp;lista\'" value="Lista" title="Lista megtekintése">';

        $navi_sablon = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit    = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel  = array($navi,             $gombok,    $gyk2);
        echo str_replace($navi_mit, $navi_mivel, $navi_sablon);


        $sorrend_html  = '';
        $sorrend_html .= '<ul id="sorrendezes_bejegyzes" title="'.$megnevezes.'">';

        $i = 0;
        $bejegyzesek = $adatbazis_kapcsolat -> select('recid nev kep', $megnevezes, '', 'sor');
        foreach($bejegyzesek as $bejegyzes){

            /*Ha egy termékhez több kép is tartozik

            $kiskep = $adatbazis_kapcsolat -> select('kepnev', 'kepek', 'termekid='.$sor_kepek['id'], 'sor');
            $sor_kiskep = $kiskep[0];*/

            /*Egy kép esetén

            if( !$bejegyzes['kep'] || !is_file('../'.get_utvonal().$bejegyzes['kep'])){
                $kep = '../elemek/admin/sorrend.gif';
            }else{
                $kep = '../'.get_utvonal().$bejegyzes['kep'];
            }
            require_once('../class/html/class.Img.php');
            $kep = new Img($kep, $bejegyzes['nev'], get_kep_sz(), get_kep_m());
            $kep = $kep -> getHtml();
            */

            $sorrend_html .= '<li title="'.$bejegyzes['nev'].'" value="'.$bejegyzes['recid'].'"><div class="kep">'.$kep.'<br><b>'.$bejegyzes['nev'].'</b></div></li>';

            $i++;
        }

        $sorrend_html .= '</ul>';
        echo $sorrend_html;

    }

    if( !$_GET['ajax'] ) {
        echo '</div>';
    }

if( !$_GET['ajax'] ) {
    require_once('footer.php');
}
