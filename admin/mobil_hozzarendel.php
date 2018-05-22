<?php
require_once('../weblap.php');

if( !_jog(JOG_MUNKALAP_NYITOTT_KEZEL)) {
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


$sajat_szallodak = get_akt_login_szallodai();
$sajat_szallodak = implode(',', $sajat_szallodak);

// Csoportosítás mentése
if (isset($_POST['beosztas_kerdez'])) {
    
    $uzenet = 'Biztos, hogy véglegesíti a mobil csoporthoz rendelést?';
    $hianyos_csop = array();
    $csoportok = array('a', 'b', 'cn', 'd');
    foreach($csoportok as $csoport){
        if($_POST[$csoport.'_csop']){
            $idk = explode($csoport, trim($_POST[$csoport.'_csop'], ','));
            // Ha van ebbe a csoportba húzott feladat, nézzük meg, hogy adott csoporthoz van-e személyzet rendelve
            if($idk){
                $van_login = $adatbazis_kapcsolat->s('recid', 'login', "`aktiv`>0 AND `szemelyzet_id`='".MOBILCSOPORT."' AND `mobil`='{$csoport}'", '', '1');
                if(!$van_login){
                    array_push($hianyos_csop, strtoupper($csoport));
                }
            }
        }
    }
    
    // Ha van hiányos csoport, akkor meg erre kérdezünk rá
    if($hianyos_csop){
        $uzenet = '"'.implode('", "', $hianyos_csop).'" mobilcsoportban jelenleg nincs hozzárendelt személyzet.<br>Folytatja?';
    }

    echo json_encode(array(
        'hiba' => $uzenet
    ));
    $weblap -> leallit();
    exit;
}

// Csoportosítás mentése
if (isset($_POST['beosztas_veglegesit'])) {
    
    // Tranzakció indul
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $csoportok = array('a', 'b', 'cn', 'd');
    foreach($csoportok as $csoport){
        if($_POST[$csoport.'_csop']){
            $idk = explode(',', trim($_POST[$csoport.'_csop'], ','));
            foreach($idk as $id){
                if(addStatusz('munkalap_statusz', $id, MUNKALAP_STAT_HOZZARENDELT)===false){
                    $db_error = true;
                }
                $sql = $adatbazis_kapcsolat->u('munkalap', array('mobil', 'szemelyzet_id'), array($csoport, MOBILCSOPORT), "`recid`='{$id}'");
                if($sql === false){ $db_error = true; }

            }
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
    
    $weblap -> leallit();
    exit;
}

// Nyitottak közé
if (isset($_POST['munkalapok_nyitottak_koze'])) {

    var_dump(addStatusz('munkalap_statusz', $_POST['recid'], MUNKALAP_STAT_NYITOTT));
    
    $weblap -> leallit();
    exit;
}

// Nyitottak közé
if (isset($_POST['beosztasok_lekerdez'])) {

    $loginok_tomb = array();
    $loginok = $adatbazis_kapcsolat->s('recid nev teljes_nev mobil', 'login', "`aktiv`>0 AND `szemelyzet_id`='".MOBILCSOPORT."'", 'nev');
    foreach($loginok as $login){
        if(!$login['mobil']){
            $login['mobil'] = 'x';
        }
        $loginok_tomb[$login['mobil']] .= '<li data-recid="'.$login['recid'].'">'.$login['teljes_nev'].'</li>';
    }
    echo json_encode($loginok_tomb);
    
    $weblap -> leallit();
    exit;
}

// Mobilcsoport login hozzárendelés
if (isset($_POST['mobil_beosztas_login_ment'])) {

    // Tranzakció indul
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $csoportok = array('x', 'a', 'b', 'cn', 'd');
    foreach($csoportok as $csoport){
        if($_POST[$csoport.'_csop']){
            
            $mobil = $csoport;
            if($mobil=='x'){
                $mobil = '';
            }
            $sql = $adatbazis_kapcsolat->u('login', array('mobil'), array($mobil), "`recid` IN (".trim($_POST[$csoport.'_csop'], ',').")");
            if($sql === false){ $db_error = true; }

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
    
    $weblap -> leallit();
    exit;
}

// Tartalom
if( !$_GET['ajax'] ) {
    require_once('header.php');
}
require_once('selectek.php');

    if( !$_GET['ajax'] ) {
        echo '<div id="szamlaBetolt">';
    }
    
    $js_submit_elott = 'return true;';


    // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
    if(isset($_GET['id_hidden'])){
        $_GET['id'] = $_GET['id_hidden'];
    }

    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, 'recid='.$_GET['id']);

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
        $json_sor[$kulcs] = str_replace(array("\r\n", "\r", "\n", "'"), array("#UJSOR#", "#UJSOR#", "#UJSOR#", "#APOSZTROF#"), $elem);
    }
    $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

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
    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

    // Form actionja
    $action = self().get_get(array('errnum','kephiba','siker'));

    // Ckeditort csak akkor húzzuk be, ha kell
    if(is_file($ckeditor_js)){
        $ckeditor = '<script src="'.$ckeditor_js.'"></script>';
    }
    
    $html .= nl2('
            <style type="text/css">
                .sor{
                    max-width: none;
                }
                div.keret{
                    padding-top: 0;
                }
                #szamlaBetolt {
                    margin: auto;
                    width: auto;
                }
            </style>
            <script>
                $(function() {
                    $(\'ul.droptrue\').sortable({
                        connectWith: \'ul.dropItem\',
                        start: function( event, ui ) {

                        },
                        stop: function( event, ui ) {
                            if($(\'.mobilcsop_a li\').length || $(\'.mobilcsop_b li\').length || $(\'.mobilcsop_cn li\').length || $(\'.mobilcsop_d li\').length){
                                $(\'#beosztas_veglegesit_gomb\').removeClass(\'dn\');
                            }else{
                                $(\'#beosztas_veglegesit_gomb\').addClass(\'dn\');
                            }
                        },

                        helper: function(e, tr) {
                            var helper = tr.clone();
                            helper.css({\'width\': \'21rem\'});
                            helper.find(\'.dtc\').css({\'display\': \'none\'});
                            helper.find(\'.dtc.latszik\').css({\'display\': \'table-cell\'});
                            return helper;
                        },

                        placeholder: \'highlight\'
                    });

                    $(\'ul.dropfalse\').sortable({
                        connectWith: \'ul.dropItem\',
                        dropOnEmpty: false
                    });


                    $(\'.dropItem\').disableSelection();
                });
            </script>

            <div class="kozep mobil_hozzarendel">

                <div class="mobilkeret">

                    <div class="dtr">
                        <div class="dtc r" style="width: 11.5rem">
                            Munkalap szám
                        </div>
                        <div class="dtc latszik" style="width: 10.2rem">
                            Szálloda
                        </div>
                        <div class="dtc" style="width: 12rem">
                            Helyiség
                        </div>
                        <div class="dtc latszik" style="width: 23rem">
                            Bejelentett hiba
                        </div>
                        <div class="dtc latszik" style="width: 24.1rem">
                            Hiba leírása
                        </div>
                        <div class="dtc c" style="width: 7rem">
                            Sürgős
                        </div>
                        <div class="dtc c" style="width: 8.4rem">
                            Visszaadva
                        </div>
                        <div class="dtc" style="width: 16rem">
                            Bejelentés dátuma
                        </div>
                    </div>

                    <div class="csop_nelkul">
                        <ul class="dropItem droptrue">
                    ');
    
    $munkalapok = $adatbazis_kapcsolat->s('*', 'munkalap', "`stat_id`='".MUNKALAP_STAT_BEOSZTAS_NELKUL."' AND `telephely_id` IN (".$sajat_szallodak.")", 'ikt_szam');
    foreach($munkalapok as $munkalap){
        
        $kapcsolat  = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$munkalap['recid']}'", '', '0,1');
        $bejelentes = $adatbazis_kapcsolat->s2('hiba_id hiba_text', 'bejelentes', "`recid`='{$kapcsolat['bejelentes_id']}'");
        $hiba       = getErtek('info', 'erteklista', 'recid', $bejelentes['hiba_id']);
        
        // ha ütemezett feladat a munkalap típusa, táblázat bejelentett hiba oszlopába kerüljön munkalap leírás tartalma
        if($munkalap['uf_szam']){
            $hiba .= $munkalap['text'];
        }
        
        $surgos = '';
        if($munkalap['surgos']){
            $surgos = '<span class="icon piros"> <i class="material-icons">error</i> </span>';
        }
        $visszaadva = '';
        if($munkalap['visszaadva']){
            $visszaadva = '<span class="icon zold"> <i class="material-icons">done</i> </span>';
        }
        
        $html .= nl2('
                        <li class="ui-state-default" data-recid="'.$munkalap['recid'].'" ondblclick="window.open(\'munkalap.php?id='.$munkalap['recid'].'&amp;modosit&amp;szures_vege\', \'uj_tab\')">
                            <div class="dtc c munkalapszam" style="width: 11rem">
                                '.$munkalap['ikt_szam'].'
                            </div>
                            <div class="dtc c latszik c szalloda" style="width: 10rem">
                                '.getErtek('nev', 'telephely', 'id', $munkalap['telephely_id']).'
                            </div>
                            <div class="dtc c" style="width: 12rem">
                                '.getErtek('nev', 'obj', 'recid', $munkalap['obj_id']).'
                            </div>
                            <div class="dtc c latszik hiba" style="width: 23rem">
                                '.$hiba.'
                            </div>
                            <div class="dtc c hiba" style="width: 24.1rem">
                                '.htmlspecialchars_decode( nl2br2($bejelentes['hiba_text'])).'
                            </div>
                            <div class="dtc c" style="width: 6rem">
                                '.$surgos.'
                            </div>
                            <div class="dtc c" style="width: 8.4rem">
                                '.$visszaadva.'
                            </div>
                            <div class="dtc c" style="width: 15rem">
                                '.substr(str_replace('-', '.', $munkalap['insert_dt']), 0, 10).'
                            </div>
                        </li>
                    ');
    }
    
    // Számoljuk ki a folyamatban levő munkalapokat is
    $mobilok        = array('a', 'b', 'cn', 'd');
    $folyamatban    = array();
    $folyamatban_db = array();
    foreach($mobilok as $mobil){

        $munkalapok = $adatbazis_kapcsolat->s('recid telephely_id', 'munkalap', "`szemelyzet_id`='".MOBILCSOPORT."' AND `telephely_id` IN (".$sajat_szallodak.") AND `mobil`='{$mobil}' AND `stat_id`='".MUNKALAP_STAT_HOZZARENDELT."'", 'ikt_szam');
        foreach($munkalapok as $munkalap){
            $kapcsolat  = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$munkalap['recid']}'", '', '0,1');
            $bejelentes = $adatbazis_kapcsolat->s2('hiba_id hiba_text', 'bejelentes', "`recid`='{$kapcsolat['bejelentes_id']}'");
            $hiba       = getErtek('info', 'erteklista', 'recid', $bejelentes['hiba_id']);
            $szalloda   = getErtek('nev',  'telephely',  'id', $munkalap['telephely_id']);
            $folyamatban[$mobil] .= '
                <tr class="link" ondblclick="window.open(\'munkalap.php?id='.$munkalap['recid'].'&amp;modosit&amp;szures_vege\', \'uj_tab\')">
                    <td class="vat">
                        '.$szalloda.'
                    </td>
                    <td class="vat">
                        '.$hiba.'
                        <div class="f9">'.nl2br2($bejelentes['hiba_text']).'</div>
                    </td>
                </tr>';
        }

        $munkalapok = $adatbazis_kapcsolat->s('recid', 'munkalap', "`szemelyzet_id`='".MOBILCSOPORT."' AND `telephely_id` IN (".$sajat_szallodak.") AND `mobil`='{$mobil}' AND `stat_id`='".MUNKALAP_STAT_FELVEVE."'");
        $folyamatban_db[$mobil] = count($munkalapok);

        $folyamatban[$mobil] = nl2('
                                <table class="folyamatban">
                                    <tr>
                                        <td>Szálloda</td>
                                        <td>Bejelentett hiba</td>
                                    </tr>
                                    '.$folyamatban[$mobil].'
                                </table>
                                ');
    }

    $html .= nl2('
                        </ul>
                    </div>
                </div>

                <table class="csoportok">
                    <tr>
                        <td>
                            <input type="button" value="\'A\' csoport ('.$folyamatban_db['a'].')" onclick="window.open(\'munkalap.php?felvett_mobilcsop&amp;csop=a\', \'munka_lista\')">
                        </td>
                        <td>
                            <input type="button" value="\'B\' csoport ('.$folyamatban_db['b'].')" onclick="window.open(\'munkalap.php?felvett_mobilcsop&amp;csop=b\', \'munka_lista\')">
                        </td>
                        <td>
                            <input type="button" value="\'CN\' csoport ('.$folyamatban_db['cn'].')" onclick="window.open(\'munkalap.php?felvett_mobilcsop&amp;csop=cn\', \'munka_lista\')">
                        </td>
                        <td>
                            <input type="button" value="\'D\' csoport ('.$folyamatban_db['d'].')" onclick="window.open(\'munkalap.php?felvett_mobilcsop&amp;csop=d\', \'munka_lista\')">
                        </td>
                    </tr>
                    <tr class="teruletek">
                        <td>
                            <ul class="dropItem droptrue mobilcsop_a"></ul>
                        </td>
                        <td>
                            <ul class="dropItem droptrue mobilcsop_b"></ul>
                        </td>
                        <td>
                            <ul class="dropItem droptrue mobilcsop_cn"></ul>
                        </td>
                        <td>
                            <ul class="dropItem droptrue mobilcsop_d"></ul>
                        </td>
                    </tr>
                    <tr class="folyamatban">
                        <td>
                            '.$folyamatban['a'].'
                        </td>
                        <td>
                            '.$folyamatban['b'].'
                        </td>
                        <td>
                            '.$folyamatban['cn'].'
                        </td>
                        <td>
                            '.$folyamatban['d'].'
                        </td>
                    </tr>
                </table>
            </div>

            <div class="muveletek"> 
                <input class="gomb dn" value="Mobil hozzárendelés véglegesítése" onclick="beosztas_veglegesit()" type="button" id="beosztas_veglegesit_gomb">
                <input class="gomb" value="Vissza a nyitottak közé" onclick="munkalapok_nyitottak_koze()" type="button" id="vissza_nyitottak_koze">
                <input class="gomb" value="Mobil személyzet beosztása" onclick="csoportMuvelet(\'mobil_szemelyzet_beosztas\')" type="button">

                <input type="submit" class="gombb mentes_gomb dn" id="mehet_gomb" disabled value="Mentés" onclick="szurkit(\'mehet_megsem_rejt\'); formModositva = false; $(\'#szerk_form\').submit()">

            </div>
            ');

$mezok = $html;

$jobb_oldal = '';
$szamla_html .= str_replace(
    array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}', '{UPDATE_DT}'     ),
    array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok,    $sor['update_dt'] ),
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
    require_once('footer.php');
}
