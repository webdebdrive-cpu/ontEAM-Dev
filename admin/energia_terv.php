<?php
require_once('../weblap.php');

if( !_jog(JOG_ENERGIATERV_MEGTEKINT) ) {
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
$akt_tomb               = $energia_terv_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'tervet';             // Hibaüzenetkor: Biztosan törlöd a $szoveg?

// Változók beállítása
$csak_szerkesztheto     = false;                // Ha tiltani szeretnéd, hogy újat tudjon feltölteni, vagy törölni adott bejegyzést
$elonezet               = '';                   // Ha üres nincs előnézet. Egyébként meg az előnézet URL-jét add meg ide, pl.: '../index.php?id='.$_GET['id']
$fajloszlop             = 'kep';                // Kép (vagy kivételes esetekben más fájl) oszlopneve.
$id_latszik             = false;                // A termék ID-ja látsszon-e a felsorolásnál, kibontásnál (van, hogy ügyfél is használja)
$kep_thumb_listaba      = false;                // Lista nézetben legyen-e a képoszlop alapján képthumbnail
$magas                  = get_indexkep_m();     // Kiskép magassága
$sorrendezes            = false;                // Ha sorrendet is be szeretnénk állítani. Adatbázisban sor oszlopot létre kell hozni, jquery-ui.js-t és admin_sorrendez.js-t aktiválni az adminhoz
$szel                   = get_indexkep_sz();    // Kiskép szélessége
$ujkent_szerkesztheto   = true;                 //  Szerkesztésnél elérhető egy gomb, melyre kattintva a termékadatai átkerülnek az új feltöltésbe

$ckeditor_js            = '../'.get_utvonal().'wysiwyg/ckeditor.js';            // Ckeditor JS útvonala
$formazatlan_szam       = array('nev', 'csz', 'tel', 'fax', 'mobil');           // Azok az oszlopneveket sorold fel, melyek numerikus értéket is tartalmazhatnak, de nem szeretnéd, ha lista nézetben ezres csoportra bontaná

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

// Új terv létrehozása
if (isset($_POST['terv_keszit'])) {
    
    $recid    = $_POST['recid'];
    $kijelolt = $_POST['kijelolt'];
    $ev       = $_POST['ev'];
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Jog ellenőrzése
    if( !_jog(JOG_ENERGIATERV_KEZEL) ) {
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga tervet készíteni!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }
    
    // Év ellenőrzése
    if( !is_numeric($ev) || $ev < date('Y') ) {
        echo json_encode(array(
            'hiba'  => 'Csak az idei év, vagy attól későbbi évszám adható meg!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    // Ha a kijelölt terv alapján kell létrehozni az újat, akkor lekérjük a kijelölt adatait
    if($kijelolt){
        
        $energia_terv = $adatbazis_kapcsolat->s2('ev text', 'energia_terv', "`recid` = '{$recid}'");
        if( $energia_terv === false ) { $db_error = true; }
        
        // Új terv létrehozása
        $uj_id = $adatbazis_kapcsolat->i('energia_terv', array('ev', 'text'), array($ev, $energia_terv['text']));
        if( $uj_id === false ) { $db_error = true; }
        
        // Egységárak átmásolása
        $egysegarak = $adatbazis_kapcsolat->s('*', 'energia_terv_ear', "`energia_terv_id`='{$recid}'");
        foreach( $egysegarak as $ear ){
            
            $mezok      = array();
            $ertekek    = array();
            
            array_push($mezok,   'energia_terv_id', 'insert_dt', 'insert_usr',                           'update_usr'                           );
            array_push($ertekek, $uj_id,            'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'], $_SESSION[get_db_tabla().'teljes_nev'] );
            
            $oszlopok   = array('telephely_idk', 'dijelem', 'ktgnem_id', 'mee', 'ear01', 'ear02', 'ear03', 'ear04', 'ear05', 'ear06', 'ear07', 'ear08', 'ear09', 'ear10', 'ear11', 'ear12', 'dnem01', 'dnem02', 'dnem03', 'dnem04', 'dnem05', 'dnem06', 'dnem07', 'dnem08', 'dnem09', 'dnem10', 'dnem11', 'dnem12', 'arf01', 'arf02', 'arf03', 'arf04', 'arf05', 'arf06', 'arf07', 'arf08', 'arf09', 'arf10', 'arf11', 'arf12');
            foreach($oszlopok as $oszlop){
                array_push($mezok,   $oszlop        );
                array_push($ertekek, $ear[$oszlop]  );
            }
            
            $sql = $adatbazis_kapcsolat->i('energia_terv_ear',  $mezok, $ertekek );
            if( $sql === false ) { $db_error = true; }
            
        }
        
    }else{
        
        // Új terv létrehozása
        $uj_id = $adatbazis_kapcsolat->i('energia_terv', array('ev'), array($ev));
        if( $uj_id === false ) { $db_error = true; }
        
    }
    
    
    // Kezdőstátusz beállítása
    $sql = $adatbazis_kapcsolat -> i('energia_terv_statusz',
                                        array('energia_terv_recid', 'stat_id',                   'login',                           'insert_dt',    'insert_usr',                             'update_usr'                           ),
                                        array($uj_id,               ENERGIATERV_STAT_MUNKAKOZI,  $_SESSION[get_db_tabla().'user'],  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'] )
                                    );
    if( $sql === false ) { $db_error = true; }
    $sql = $adatbazis_kapcsolat->u('energia_terv', array('stat_id'), array(ENERGIATERV_STAT_MUNKAKOZI), "`recid`='{$uj_id}'");
    if( $sql === false ) { $db_error = true; }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz'   => 'siker',
            'html'      => $html,
            'id'        => $uj_id,
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

// Terv élesites
if (isset($_POST['terv_elesites'])) {
    
    $recid = $_POST['recid'];
    $mehet = $_POST['mehet'];
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $energia_terv = $adatbazis_kapcsolat->s2('stat_id ev ikt_szam', 'energia_terv', "`recid` = '{$recid}'");
    if( $energia_terv === false ) { $db_error = true; }
    
    // Státusz ellenőrzése
    if( $energia_terv['stat_id'] != ENERGIATERV_STAT_KESZ ) {
        echo json_encode(array(
            'hiba' => 'Csak kész terv élesíthető!',
        ));
        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if( !_jog(JOG_ENERGIATERV_KEZEL) ) {
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga tervet élesíteni!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }
    
    // Ha van olyan terv, ami azonos évszámú, és éles, akkor azt készre kell vissza léptetni
    $van_eles_terv = $adatbazis_kapcsolat->s2('recid ev ikt_szam', 'energia_terv', "`ev`='{$energia_terv['ev']}' AND `stat_id`='".ENERGIATERV_STAT_ELES."'");
    if( $van_eles_terv === false ) { $db_error = true; }
    
    // Előbb kérdezünk
    if(!$mehet){
        if($van_eles_terv){
            echo json_encode(array(
                'hiba'  => $energia_terv['ev'].' évre már van éles egységár terv!<br><br>A jelenlegi éles terv:<br><br>Tervező év: '.$van_eles_terv['ev'].'<br>Tervszám: '.$van_eles_terv['ikt_szam'].'<br><br>A következő egységár terv élesítésére készül:<br><br>Tervező év: '.$energia_terv['ev'].'<br>Tervszám: '.$energia_terv['ikt_szam'].'<br><br>Folytatja?',
                'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="terv_elesites(\'mehet\')"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">',
            ));
            $weblap->leallit();
            exit();
        }else{
            echo json_encode(array(
                'hiba'  => 'A következő egységár terv élesítésére készül:<br><br>Tervező év: '.$energia_terv['ev'].'<br>Tervszám: '.$energia_terv['ikt_szam'].'<br><br>Folytatja?',
                'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="terv_elesites(\'mehet\')"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">',
            ));
            $weblap->leallit();
            exit();
        }
    }
    
    if($van_eles_terv){
        if( !addStatusz( 'energia_terv_statusz', $van_eles_terv['recid'], ENERGIATERV_STAT_KESZ ) ) {
            $db_error = true;
        }
    }
    
    // Státusz léptetés
    if( !addStatusz( 'energia_terv_statusz', $recid, ENERGIATERV_STAT_ELES ) ) {
        $db_error = true;
    }


    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'siker',
            'html' => $html
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

// Szálloda választó popup
if( isset($_POST['getSzallodak']) ) {
    
    // Szálloda lekérdezése
    $telephelyek = $adatbazis_kapcsolat->s('id nev recid modell', 'telephely', "`inaktiv`='0'", 'nev');

    // Felhasználóhoz kapcsolt szállodák
    $kijelolek = $adatbazis_kapcsolat->s2('telephely_idk', 'energia_terv_ear', "`recid` = '".$_POST['recid']."'");
    $kijelolek = $kijelolek['telephely_idk'];
    $kijelolek = explode(',', $kijelolek);

    if( !$felh_szallodak ) {
        $felh_szallodak = array();
    }

    $html = '';
    foreach($telephelyek as $telephely) {
        $checked = '';
        if( in_array($telephely['id'], $kijelolek) ) {
            $checked = ' checked="checked"';
        }

        $html .= nl2('
            <div class="dtr">
                <div class="dtc">
                    <div class="cb">
                        <input type="checkbox" class="check" name="telephely[]" id="telephely-'.$telephely['recid'].'" value="'.$telephely['id'].'" data-pesti="'.$telephely['modell'].'"'.$checked.'>
                        <label for="telephely-'.$telephely['recid'].'"></label>
                    </div>
                </div>
                <div class="dtc">
                    '.$telephely['nev'].'
                </div>
            </div>
        ');
    }

    echo $html;

    $weblap->leallit();
    exit();
}

// Szálloda rendelése egységárhoz
if (isset($_GET['addSzallodaEnergia'])) {
    
    $egysegar_id    = $_GET['recid'];
    $szallIDs       = json_decode($_GET['szallIDs'], true);
    sort($szallIDs);

    if( $egysegar_id && count($szallIDs) ) {

        $adatbazis_kapcsolat->u('energia_terv_ear', array('telephely_idk'), array(implode(',', $szallIDs)), "`recid`='{$egysegar_id}'");

        echo json_encode(array(
            'statusz' => 'siker',
            'kapcsID' => $kapcsID,
            'uzi'     => ''
        ));

    }

    $weblap->leallit();
    exit();
    
}

// Egységár sor adatai a popup feltöltéséhez
if (isset($_POST['get_egysegar_adatok'])) {
    
    $egysegar = $adatbazis_kapcsolat->s2('dijelem ktgnem_id mee', 'energia_terv_ear', "`recid`='{$_POST['recid']}'");
    $ktgnem   = $adatbazis_kapcsolat->s2('nev', 'ktgnem', "`recid`='{$egysegar['ktgnem_id']}'");
    echo json_encode(array(
        'ktgnem_id_autocomp'    => $ktgnem['nev'],
        'ktgnem_id'             => $egysegar['ktgnem_id'],
        'dijelem_autocomp'      => $egysegar['dijelem'],
        'popup_mee'             => $egysegar['mee'],
    ));
    
    $weblap->leallit();
    exit();
}

// Költségnem, díjelem és mennyiségi egység beállítása
if ($_POST['energia_terv_koltsegnem_dijelem']) {
    
    $terv_id    = $_POST['terv_id'];
    $terv_ea_id = $_POST['terv_ea_id'];
    $ktgnem_id  = $_POST['ktgnem_id'];
    $dijelem    = $_POST['dijelem'];
    $mee        = $_POST['mee'];
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $egysegar = $adatbazis_kapcsolat->s2('telephely_idk', 'energia_terv_ear', "`recid`='{$terv_ea_id}'");
    
    // Szálloda+költségnem+díjelem terven belüli egyediség ellenőrzése
    $hibauzi = szalloda_koltsnem_dijelem_azonos($terv_id, $egysegar['telephely_idk'], $ktgnem_id, $dijelem);
    if( $hibauzi ){
        echo json_encode(array(
            'hiba'  => $hibauzi,
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }
    
    // Mezők beírása az egységár sorba
    $sql = $adatbazis_kapcsolat->u('energia_terv_ear',  array('ktgnem_id', 'dijelem', 'mee'),
                                                        array($ktgnem_id,  $dijelem,  $mee ),
                                    "`recid`='{$terv_ea_id}'");
    if( $sql === false ) { $db_error = true; }

    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'siker',
            'html' => $html
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

// Új egységár sor hozzáadása
if (isset($_POST['uj_egysegar'])) {
    
    $terv_recid     = $_POST['terv_recid'];
    $egysegar_recid = $_POST['egysegar_recid'];
    $szalloda_idk   = json_decode($_POST['szalloda_idk'], true);
    sort($szalloda_idk);
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Ha kaptunk szállodákat, akkor azzal...
    if( $szalloda_idk ){
        $uj_egysegar_recid = $adatbazis_kapcsolat->i('energia_terv_ear', array('energia_terv_id', 'telephely_idk'            ),
                                                                         array($terv_recid,       implode(',', $szalloda_idk))
                                                    );
        if( $uj_egysegar_recid === false ) { $db_error = true; }
        
    // Ha nem kaptunk szállodát csak egységárrecid-t, akkor meg azok szállodáival hozzuk létre az új sort
    }else{
        $telephelyek = $adatbazis_kapcsolat->s2('telephely_idk', 'energia_terv_ear', "`recid`='{$egysegar_recid}'");
        if( $telephelyek === false ) { $db_error = true; }
        
        $uj_egysegar_recid = $adatbazis_kapcsolat->i('energia_terv_ear', array('energia_terv_id', 'telephely_idk'               ),
                                                                         array($terv_recid,       $telephelyek['telephely_idk'] )
                                                    );
        if( $uj_egysegar_recid === false ) { $db_error = true; }
    }
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'siker',
            'html' => $html
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

// Tartalom
if( !$_GET['ajax'] ) {
    require_once('header.php');
}
require_once('selectek.php');

    if( !$_GET['ajax'] ) {
        echo '<div id="szamlaBetolt">';
    }

    // Megjegyzések darabszáma
    $megj_db = '';
    $megj_sorok = $adatbazis_kapcsolat->s('recid', 'megjegyzes', "`tabla` = '".$megnevezes."' AND `id` = '".$_GET['id']."'");
    if( $megj_sorok ) {
        $megj_db = ' ('.count($megj_sorok).')';
    }

    // Dokumentumok darabszáma
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

    if( isset($_GET['modosit']) || isset($_GET['egysegarak']) || isset($_GET['tortenet']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Tervek',
            'egysegarak'    => 'Egységárak',
            'tortenet'      => 'Történet',
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
    
    $js_submit_elott = 'return form_ell_admin();';

    // Gombok kirajzolása minden oldalra
    $gombok = '';
    if( isset($_GET['uj']) ) {

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

        // Műveletek box
        $gombok = nl2('
            <div class="muveletek"'.$muv_style.'>
                '.$gombok.'
            </div>
        ');

    // Módosítás gombok
    } else {

        $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");

        // Js, a törlés gombhoz
	    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';

        // Jogosutságkezelés: csak az törölheti, aki létrehozta
        if(!_jog(JOG_ENERGIATERV_TORLES)){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Nincs jogosultsága '.$szoveg.' törölni!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        }elseif( $sor['stat_id']==ENERGIATERV_STAT_ELES ){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'A '.$sor['ikt_szam'].' tervszámú energia terv státusza ÉLES, így nem törölhető!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        }else{
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'A következő egységár terv törlésére készül:<br><br>Tervező év: '.$sor['ev'].'<br>Tervszám: '.$sor['ikt_szam'].'<br><br>Biztos, hogy törli?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
        }
        
        
        // Egységárak fül
        if( isset($_GET['egysegarak']) ){
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Új sor" onclick="uj_egysegar()"> ';
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Szálloda" onclick="kapcsolodoSzerkeszt(\'szallodak_energia\');"> ';
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Költségnem és díjelem" onclick="kapcsolodoSzerkeszt(\'energia_terv_koltsegnem_dijelem\');"> ';
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Egységárak" onclick=""> ';
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Sor törlése" onclick=""> ';
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Véglegesítés" onclick=""> ';
        // Tervek fül
        }else{
            // Élesítés gomb
            if( $sor['stat_id']==ENERGIATERV_STAT_KESZ && _jog(JOG_ENERGIATERV_KEZEL) ){
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Élesítés" onclick="terv_elesites()"> ';
            }
        }

        if( _jog(JOG_ENERGIATERV_KEZEL) ){
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="$(\'#szerk_form\').submit()">
                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="formModositva=false; location.reload();">
                </div>
            ').$gombok;
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
    
	// Új rekord felvitele
	if(isset($_GET['uj'])){

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
        }

        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($sor).'\'); </script>';

		// Ez elkészíti a $mezok változót
		require('switch.php');

	    $form_mit     = array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{CK_EDITOR}', '{GOMBOK}', '{MUV_STYLE}'   );
	    $form_mivel   = array($js,    $js_submit_elott,    self(),     $mezok,    $megnevezes,    $ckeditor,     $gombok,    $muv_style      );
	    $szamla_html .= str_replace($form_mit, $form_mivel, $form_sablon);

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom'  => $szamla_html,
                'link'      => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

	// Rekord módosítása
	}elseif(isset($_GET['modosit'])) {

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
            $json_sor[$kulcs] = nl2(str_replace(array("\r\n", "'"), array("#UJSOR#", "#APOSZTROF#"), $elem));
        }
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

        // NAVIGÁCIÓ
        $navi_sablon = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit    = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel  = array( $navi,             '',         $gyk2 );
        // echo str_replace($navi_mit, $navi_mivel, $navi_sablon);

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

		// Ez elkészíti a $mezok változót
		require('switch.php');

	    $szamla_html .= str_replace(
            array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}', '{UPDATE_DT}'     ),
            array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok,    $sor['update_dt'] ),
            fajlbol_olvas('../sablon/admin/form_modosit.html', true)
        );

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom'  => $szamla_html,
                'link'      => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

        if($tobb_kep){
			require_once('tobb_kep.php');
		}

	// Egységár
	}elseif(isset($_GET['egysegarak'])){

        $szamla_html .= '
            <table class="szamla_cont_tabla nolink fejlec_padd szep_csikos kisbetu">
            <thead>
            <tr>
                <th><span>Szállodák</span></th>
                <th><span>Díjelem</span></th>
                <th><span>Költségnem kód</span></th>
                <th><span>Költségnem név</span></th>
                <th><span>Mee</span></th>
                <th><span>Egységár Január</span></th>
                <th><span>Devizanem Január</span></th>
                <th><span>Árfolyam Január</span></th>
                <th><span>Egységár Február</span></th>
                <th><span>Devizanem Február</span></th>
                <th><span>Árfolyam Február</span></th>
                <th><span>Egységár Március</span></th>
                <th><span>Devizanem Március</span></th>
                <th><span>Árfolyam Március</span></th>
                <th><span>Egységár Április</span></th>
                <th><span>Devizanem Április</span></th>
                <th><span>Árfolyam Április</span></th>
                <th><span>Egységár Május</span></th>
                <th><span>Devizanem Május</span></th>
                <th><span>Árfolyam Május</span></th>
                <th><span>Egységár Június</span></th>
                <th><span>Devizanem Június</span></th>
                <th><span>Árfolyam Június</span></th>
                <th><span>Egységár Július</span></th>
                <th><span>Devizanem Július</span></th>
                <th><span>Árfolyam Július</span></th>
                <th><span>Egységár Augusztus</span></th>
                <th><span>Devizanem Augusztus</span></th>
                <th><span>Árfolyam Augusztus</span></th>
                <th><span>Egységár Szeptember</span></th>
                <th><span>Devizanem Szeptember</span></th>
                <th><span>Árfolyam Szeptember</span></th>
                <th><span>Egységár Október</span></th>
                <th><span>Devizanem Október</span></th>
                <th><span>Árfolyam Október</span></th>
                <th><span>Egységár November</span></th>
                <th><span>Devizanem November</span></th>
                <th><span>Árfolyam November</span></th>
                <th><span>Egységár December</span></th>
                <th><span>Devizanem December</span></th>
                <th><span>Árfolyam December</span></th>
            </tr>
            </thead>
            <tbody>
            ';
        
        $egysegarak = $adatbazis_kapcsolat->s('*', 'energia_terv_ear', "`energia_terv_id`='{$_GET['id']}'");
        foreach($egysegarak as $egysegar){
            
            // Szálloda nevek
            $szalloda_html = '';
            $szallodak = explode(',', $egysegar['telephely_idk']);
            $szallodak = $adatbazis_kapcsolat->s('nev', 'telephely', "`id` IN ('".implode('\', \'', $szallodak)."')");
            foreach($szallodak as $szalloda){
                $szalloda_html .= $szalloda['nev'].', ';
            }
            $szalloda_html = csonkit($szalloda_html, 2);
            
            // Ktgnem
            $ktgnem = $adatbazis_kapcsolat->s2('id nev', 'ktgnem', "`recid`='{$egysegar['ktgnem_id']}'");
            
            $szamla_html .= '
                <tr id="tr_ear_'.$egysegar['recid'].'" data-recid="'.$egysegar['recid'].'">
                    <td>'.$szalloda_html.'</td>
                    <td>'.$egysegar['dijelem'].'</td>
                    <td>'.$ktgnem['id'].'</td>
                    <td>'.$ktgnem['nev'].'</td>
                    <td>'.$egysegar['mee'].'</td>
                    <td>'.$egysegar['ear01'].'</td>
                    <td>'.$egysegar['dnem01'].'</td>
                    <td>'.$egysegar['arf01'].'</td>
                    <td>'.$egysegar['ear02'].'</td>
                    <td>'.$egysegar['dnem02'].'</td>
                    <td>'.$egysegar['arf02'].'</td>
                    <td>'.$egysegar['ear03'].'</td>
                    <td>'.$egysegar['dnem03'].'</td>
                    <td>'.$egysegar['arf03'].'</td>
                    <td>'.$egysegar['ear04'].'</td>
                    <td>'.$egysegar['dnem04'].'</td>
                    <td>'.$egysegar['arf04'].'</td>
                    <td>'.$egysegar['ear05'].'</td>
                    <td>'.$egysegar['dnem05'].'</td>
                    <td>'.$egysegar['arf05'].'</td>
                    <td>'.$egysegar['ear06'].'</td>
                    <td>'.$egysegar['dnem06'].'</td>
                    <td>'.$egysegar['arf06'].'</td>
                    <td>'.$egysegar['ear07'].'</td>
                    <td>'.$egysegar['dnem07'].'</td>
                    <td>'.$egysegar['arf07'].'</td>
                    <td>'.$egysegar['ear08'].'</td>
                    <td>'.$egysegar['dnem08'].'</td>
                    <td>'.$egysegar['arf08'].'</td>
                    <td>'.$egysegar['ear09'].'</td>
                    <td>'.$egysegar['dnem09'].'</td>
                    <td>'.$egysegar['arf09'].'</td>
                    <td>'.$egysegar['ear10'].'</td>
                    <td>'.$egysegar['dnem10'].'</td>
                    <td>'.$egysegar['arf10'].'</td>
                    <td>'.$egysegar['ear11'].'</td>
                    <td>'.$egysegar['dnem11'].'</td>
                    <td>'.$egysegar['arf11'].'</td>
                    <td>'.$egysegar['ear12'].'</td>
                    <td>'.$egysegar['dnem12'].'</td>
                    <td>'.$egysegar['arf12'].'</td>
                </tr>
                ';
        }
        
        $szamla_html .= nl2('
                            </tbody>
                        </table>
                        ');
        
        // Ha van a get-ben ear id, akkor azt a sort jelöljük meg
        if( isset($_GET['ear'])){
            $szamla_html .= nl2('
                            <script>
                                $(\'#tr_ear_'.$_GET['ear'].'\').addClass(\'kijelolve\'); 
                            </script>
                            ');
        }
        
        // Ha nincs megjelölt sor, akkor az első legyen az
        $szamla_html .= nl2('
                        <script>
                            if( !$(\'.szamla_cont_tabla .kijelolve\').length ){
                                $(\'.szamla_cont_tabla tbody tr:first-of-type\').addClass(\'kijelolve\');
                            }
                        </script>
                        ');

        $szamla_html = nl2($szamla_html);
        
        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom'  => $szamla_html.$gombok,
                'link'      => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
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

        $sorok = $adatbazis_kapcsolat->s('recid megj szerepkor insert_usr insert_dt', 'megjegyzes', "`id` = '".$_GET['id']."' AND `tabla` = '".str_replace('.php', '', basename(self()))."'", 'recid desc');
        if( $sorok ) {
            $i = 1;
            foreach($sorok as $sor) {
                $szerepkor = getErtek('nev', 'szerepkor', 'recid', $sor['szerepkor']);
                $szamla_html .= nl2('
                    <div id="naploSor-'.$sor['recid'].'" class="dtr nSor kijSor" data-id="'.$sor['recid'].'" data-i="'.$i.'">
                        <div class="dtc">'.htmlspecialchars_decode( nl2br2($sor['megj']) ).'</div>
                        <div class="dtc">'.$sor['insert_usr'].' '.$szerepkor.'</div>
                        <div class="dtc">'.datum_kiir($sor['insert_dt']).'</div>
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


// Szálloda+költségnem+díjelem terven belüli egyediség ellenőrzése
function szalloda_koltsnem_dijelem_azonos($terv_id, $telephely_idk, $ktgnem_id, $dijelem){
    
    global $adatbazis_kapcsolat;
    
    $telephely_idk  = trim($telephely_idk);
    $ktgnem_id      = trim($ktgnem_id);
    $dijelem        = trim($dijelem);
    
    if( $telephely_idk && $ktgnem_id && $dijelem && $adatbazis_kapcsolat->s('recid', 'energia_terv_ear', "`energia_terv_id`='{$terv_id}' AND `telephely_idk`='{$telephely_idk}' AND `ktgnem_id`='{$ktgnem_id}' AND `dijelem`='{$dijelem}'") ){
        return 'Azonos díjelem megnevezések szálloda - költségnem összerendelésen belül!';
    }else{
        return '';
    }
    
}