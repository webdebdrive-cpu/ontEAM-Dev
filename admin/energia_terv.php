<?php
require_once('../weblap.php');

if( !_jog(JOG_ENERGIA_EGYSEGAR_OLVAS) ) {
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

// Egységárak mentése
if (isset($_POST['egysegarMentes'])) {
    $mit  = array();
    $mire = array();
    
    // Státusz ellenőzése
    $terv = $adatbazis_kapcsolat -> s2('energia_terv_id', 'energia_terv_ear', "`recid`='{$_POST['egysegarsor_id']}'");
    $terv = $adatbazis_kapcsolat->s2('stat_id', 'energia_terv', "`recid`='{$terv['energia_terv_id']}'");
    if($terv['stat_id']!=ENERGIATERV_STAT_MUNKAKOZI){
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => 'Csak munkaközi terv szerkeszthető!',
            'title' => 'Hiba'
        ));
        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if( !_jog(JOG_ENERGIA_EGYSEGAR_KEZEL) ) {
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga tervet szerkeszteni!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    for($i = 1; $i < 13; $i++) {
        $sorszam = sprintf('%02d', $i);

        // Egységára
        array_push($mit,    'ear'.$sorszam);
        array_push($mire,   str_replace(array(',', ' '), array('.', ''), $_POST['ear'.$sorszam]));

        // Deviza
        array_push($mit,    'dnem'.$sorszam);
        array_push($mire,   $_POST['dnem'.$sorszam]);

        // Árfolyam
        array_push($mit,    'arf'.$sorszam);
        array_push($mire,   str_replace(array(',', ' '), array('.', ''), $_POST['arf'.$sorszam]));
    }

    // Frissítés
    $adatbazis_kapcsolat -> u('energia_terv_ear', $mit, $mire, 'recid='.$_POST['egysegarsor_id']);

    echo json_encode(array(
        'statusz' => 'OK',
        'recid'   => $_POST['egysegarsor_id']
    ));

    $weblap->leallit();
    exit();
}

// Egységárak táblázat lekérdezése
if (isset($_POST['get_egysegarak'])) {
    $id = $_POST['id'];
        
    // Státusz ellenőzése
    $terv = $adatbazis_kapcsolat -> s2('energia_terv_id', 'energia_terv_ear', "`recid`='{$id}'");
    $terv = $adatbazis_kapcsolat->s2('stat_id', 'energia_terv', "`recid`='{$terv['energia_terv_id']}'");
    if($terv['stat_id']!=ENERGIATERV_STAT_MUNKAKOZI){
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => 'Csak munkaközi terv szerkeszthető!',
            'title' => 'Hiba'
        ));
        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if( !_jog(JOG_ENERGIA_EGYSEGAR_KEZEL) ) {
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga tervet szerkeszteni!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    if( $id ) {
        $html = '';
        $honapok = array('Január', 'Február', 'Március', 'Április', 'Május', 'Június', 'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December');

        // Egységár sor lekérdezése
        $egysegar = $adatbazis_kapcsolat->s2('*', 'energia_terv_ear', "`recid`='{$id}'");

        if( $egysegar ) {
            $html = nl2('
                <div class="dt egysegarak_tablazat">
                    <div class="dtr">
                        <div class="dtc"></div>
                        <div class="dtc">Egységár</div>
                        <div class="dtc">Deviza</div>
                        <div class="dtc">Árfolyam</div>
                    </div>
            ');

            foreach($honapok as $key => $honap) {
                $sorszam = sprintf('%02d', $key + 1);

                $kulso_val = $egysegar['dnem'.$sorszam];
                if( !$kulso_val ) {
                    $kulso_val = 5;
                }

                $kulso_ertek = $adatbazis_kapcsolat->s2('id info', 'erteklista', '`id`=\''.$kulso_val.'\' and `csoport` = \'21\'', '', '1');

                // Árfolyam
                $arfolyam = str_replace('.', ',', $egysegar['arf'.$sorszam]);
                if( $arfolyam == '0,00000' ) {
                    $arfolyam = '1,00000';
                }

                $html .= nl2('
                    <div class="dtr">
                        <div class="dtc">'.$honap.'</div>
                        <div class="dtc"><input type="text" name="ear'.$sorszam.'" id="ear'.$sorszam.'" class="f_num" data-tort="3" value="'. number_format($egysegar['ear'.$sorszam], 3, ',', ' ').'"></div>
                        <div class="dtc">
                            <input type="text" autocomplete="off" class="csillag autocomp" name="dnem'.$sorszam.'_autocomp" id="dnem'.$sorszam.'_autocomp" style="min-width: inherit; width: 5rem;" value="'.$kulso_ertek['info'].'" data-origin-val="'.$kulso_ertek['info'].'">
                            <input type="hidden" name="dnem'.$sorszam.'" id="dnem'.$sorszam.'" value="'.$kulso_ertek['id'].'">
                            <script>
                                $(document).ready(function() {
                                    $(\'#dnem'.$sorszam.'_autocomp\').autocomplete({
                                        \'php_fajl\'        : \'../autocomplete.php\',
                                        \'tabla\'           : \'erteklista\',
                                        \'oszlop_keres\'    : \'info\',
                                        \'oszlop_listaz\'   : \'info\',
                                        \'csoport\'         : 21,
                                        \'id_kiir\'         : \'id\',
                                        \'popupba\'         : true
                                    });
                                });
                            </script>
                        </div>
                        <div class="dtc"><input type="text" name="arf'.$sorszam.'" id="arf'.$sorszam.'" class="f_num" data-tort="5" value="'.$arfolyam.'"></div>
                    </div>
                ');
            }

            $html .= '</div>';
        }

        echo json_encode(array(
            'statusz' => 'siker',
            'html'    => $html
        ));
    } else {
        echo json_encode(array(
            'hiba' => 'Egységárak megadásához válasszon ki egy sort!'
        ));
    }

    $weblap->leallit();
    exit();
}

// Új terv létrehozása
if (isset($_POST['terv_keszit'])) {

    $recid    = $_POST['recid'];
    $kijelolt = $_POST['kijelolt'];
    $ev       = $_POST['ev'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    // Jog ellenőrzése
    if( !_jog(JOG_ENERGIA_EGYSEGAR_KEZEL) ) {
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga tervet készíteni!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    // Év ellenőrzése
    if( !is_numeric($ev) || $ev < date('Y') || $ev > date('Y')+5 ) {
        echo json_encode(array(
            'hiba'  => 'A tervező év '.date('Y').'-'.(date('Y')+5).' között lehet!',
            'focus' => 'popup_ev',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    if( $energia_terv = $adatbazis_kapcsolat->s2('recid', 'energia_terv', "`ev` = '{$ev}' AND `stat_id` = '".ENERGIATERV_STAT_MUNKAKOZI."'", '', '1') ) {
        echo json_encode(array(
            'hiba'  => $ev.' évre már van munkaközi terv.',
            'gomb'  => '<input type="button" class="gomb zold" value="Megnyitás" onclick="szamlaBetolt( \''.$energia_terv['recid'].'\', \''.basename(self()).'\', \'modosit\' ); alert2(); popupZar();"><input type="button" class="gomb piros" value="Mégsem" onclick="alert2()">',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    // Ha a kijelölt terv alapján kell létrehozni az újat, akkor lekérjük a kijelölt adatait
    if($kijelolt){

        // Új terv létrehozása
        $uj_id = $adatbazis_kapcsolat->i('energia_terv',
                                                        array('ev', 'loginnev',                        'insert_dt',    'insert_usr',                             'update_usr'                           ),
                                                        array($ev,  $_SESSION[get_db_tabla().'user'],  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'] )
                                        );
        if( $uj_id === false ) { $db_error = true; }

        // Egységárak átmásolása
        $egysegarak = $adatbazis_kapcsolat->s('*', 'energia_terv_ear', "`energia_terv_id`='{$recid}'");
        foreach( $egysegarak as $ear ){

            $mezok      = array();
            $ertekek    = array();

            array_push($mezok,   'energia_terv_id', 'insert_dt', 'insert_usr',                           'update_usr'                           );
            array_push($ertekek, $uj_id,            'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'], $_SESSION[get_db_tabla().'teljes_nev'] );

            $oszlopok   = array('telephely_idk', 'dijelem', 'ktgnem_id', 'mee', 'ear01', 'ear02', 'ear03', 'ear04', 'ear05', 'ear06', 'ear07', 'ear08', 'ear09', 'ear10', 'ear11', 'ear12', 'dnem01', 'dnem02', 'dnem03', 'dnem04', 'dnem05', 'dnem06', 'dnem07', 'dnem08', 'dnem09', 'dnem10', 'dnem11', 'dnem12', 'arf01', 'arf02', 'arf03', 'arf04', 'arf05', 'arf06', 'arf07', 'arf08', 'arf09', 'arf10', 'arf11', 'arf12', 'sorid');
            foreach($oszlopok as $oszlop){
                array_push($mezok,   $oszlop        );
                array_push($ertekek, $ear[$oszlop]  );
            }

            $sql = $adatbazis_kapcsolat->i('energia_terv_ear',  $mezok, $ertekek );
            if( $sql === false ) { $db_error = true; }

        }

    }else{

        // Új terv létrehozása
        $uj_id = $adatbazis_kapcsolat->i('energia_terv', 
                                                            array('ev', 'loginnev',                        'insert_dt',    'insert_usr',                             'update_usr'                           ),
                                                            array($ev,  $_SESSION[get_db_tabla().'user'],  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'] )
                                        );
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
    if( !_jog(JOG_ENERGIA_EGYSEGAR_KEZEL) ) {
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

    $kesz_id = 0;
    if($van_eles_terv){
        $kesz_id = $van_eles_terv['recid'];
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
            'kesz_id' => $kesz_id,
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
    
    // Státusz ellenőrzése
    $terv_ear = $adatbazis_kapcsolat->s2('energia_terv_id, telephely_idk, ktgnem_id, dijelem', 'energia_terv_ear', "`recid`='{$egysegar_id}'");
    if( $terv_ear === false ) { $db_error = true; }
    $terv     = $adatbazis_kapcsolat->s2('stat_id', 'energia_terv', "`recid`='{$terv_ear['energia_terv_id']}'");
    if( $terv === false ) { $db_error = true; }
    if($terv['stat_id']!=ENERGIATERV_STAT_MUNKAKOZI){
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => 'Csak munkaközi terv szerkeszthető!',
            'title' => 'Hiba'
        ));

        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if( !_jog(JOG_ENERGIA_EGYSEGAR_KEZEL) ) {
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga tervet szerkeszteni!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }
    
    // Azonos díjelem, költségem és szálloda ellenőrzése
    $hiba = '';
    foreach($szallIDs as $szall){
        $hiba = szalloda_koltsnem_dijelem_azonos( $terv_ear['energia_terv_id'], $szall, $terv_ear['ktgnem_id'], $terv_ear['dijelem'], $egysegar_id );
        if( $hiba === false ) { $db_error = true; }
        if($hiba){
            break;
        }
    }
    if( $hiba ){
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => $hiba,
            'title' => 'Hiba'
        ));

        $weblap->leallit();
        exit();
    }
    
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

// Éles-e már az energia terv státusza?
if (isset($_POST['get_statusz'])) {
    
    $terv = $adatbazis_kapcsolat->s2('stat_id', 'energia_terv', "`recid`='{$_POST['recid']}'");
    if($terv['stat_id']!=ENERGIATERV_STAT_MUNKAKOZI){
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => 'Csak munkaközi terv szerkeszthető!',
            'title' => 'Hiba'
        ));

    }
    
    // Jog ellenőrzése
    if( !_jog(JOG_ENERGIA_EGYSEGAR_KEZEL) ) {
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga tervet szerkeszteni!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }
    
    $weblap->leallit();
    exit();
}

// Egységár sor adatai a popup feltöltéséhez
if (isset($_POST['get_egysegar_adatok'])) {
    
    $egysegar = $adatbazis_kapcsolat->s2('dijelem ktgnem_id mee energia_terv_id', 'energia_terv_ear', "`recid`='{$_POST['recid']}'");
    $terv     = $adatbazis_kapcsolat->s2('stat_id', 'energia_terv', "`recid`='{$egysegar['energia_terv_id']}'");
    $ktgnem   = $adatbazis_kapcsolat->s2('nev', 'ktgnem', "`id`='{$egysegar['ktgnem_id']}'");
    
    if($terv['stat_id']!=ENERGIATERV_STAT_MUNKAKOZI){
        echo json_encode(array(
            'hiba'  => 'Csak munkaközi terv szerkeszthető!',
            'title' => 'Hiba'
        ));

        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if( !_jog(JOG_ENERGIA_EGYSEGAR_KEZEL) ) {
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga tervet szerkeszteni!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }
    
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
    if( $egysegar === false ) { $db_error = true; }
    
    // Szálloda+költségnem+díjelem terven belüli egyediség ellenőrzése
    $hibauzi = szalloda_koltsnem_dijelem_azonos($terv_id, $egysegar['telephely_idk'], $ktgnem_id, $dijelem, $terv_ea_id);
    if( $hibauzi ){
        echo json_encode(array(
            'hiba'  => $hibauzi,
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if( !_jog(JOG_ENERGIA_EGYSEGAR_KEZEL) ) {
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga tervet élesíteni!',
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

// Új egységár sor hozzáadása
if (isset($_POST['uj_egysegar'])) {

    $terv_recid     = $_POST['terv_recid'];
    $egysegar_recid = $_POST['egysegar_recid'];
    $szalloda_idk   = json_decode($_POST['szalloda_idk'], true);
    sort($szalloda_idk);

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Ha a terv éles, akkor már nem szerkeszthető
    $terv = $adatbazis_kapcsolat->s2('stat_id', 'energia_terv', "`recid`='{$terv_recid}'");
    if( $terv === false ) { $db_error = true; }
    
    if($terv['stat_id']!=ENERGIATERV_STAT_MUNKAKOZI){
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => 'Csak munkaközi terv szerkeszthető!',
            'title' => 'Hiba'
        ));

        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if( !_jog(JOG_ENERGIA_EGYSEGAR_KEZEL) ) {
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga tervet szerkeszteni!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    // Ha kaptunk szállodákat, akkor azzal...
    if( $szalloda_idk ){
        
        $uj_egysegar_recid = $adatbazis_kapcsolat->i('energia_terv_ear', array('energia_terv_id', 'telephely_idk',              'dnem01', 'dnem02', 'dnem03', 'dnem04', 'dnem05', 'dnem06', 'dnem07', 'dnem08', 'dnem09', 'dnem10', 'dnem11', 'dnem12', 'insert_usr',                           'insert_dt',  'update_usr',                           'update_dt', 'sorid' ),
                                                                         array($terv_recid,       implode(',', $szalloda_idk),  '5',      '5',      '5',      '5',      '5',      '5',      '5',      '5',      '5',      '5',      '5',      '5',      $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',      $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',      time()  )
                                                    );
        if( $uj_egysegar_recid === false ) { $db_error = true; }

    // Ha nem kaptunk szállodát csak egységárrecid-t, akkor meg azok szállodáival hozzuk létre az új sort
    }else{
        $telephelyek = $adatbazis_kapcsolat->s2('telephely_idk', 'energia_terv_ear', "`recid`='{$egysegar_recid}'");
        if( $telephelyek === false ) { $db_error = true; }

        $uj_egysegar_recid = $adatbazis_kapcsolat->i('energia_terv_ear', array('energia_terv_id', 'telephely_idk',               'dnem01', 'dnem02', 'dnem03', 'dnem04', 'dnem05', 'dnem06', 'dnem07', 'dnem08', 'dnem09', 'dnem10', 'dnem11', 'dnem12', 'insert_usr',                           'insert_dt',  'update_usr',                           'update_dt', 'sorid' ),
                                                                         array($terv_recid,       $telephelyek['telephely_idk'], '5',      '5',      '5',      '5',      '5',      '5',      '5',      '5',      '5',      '5',      '5',      '5',      $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',      $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',     time() )
                                                    );
        if( $uj_egysegar_recid === false ) { $db_error = true; }
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'siker',
            'recid'   => $uj_egysegar_recid
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

// Egységár sor törlése
if (isset($_POST['egysegar_sor_torol'])) {

    $egysegar_recid = $_POST['egysegar_recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Státusz ellenőrzése
    $terv_ear = $adatbazis_kapcsolat->s2('energia_terv_id', 'energia_terv_ear', "`recid`='{$egysegar_recid}'");
    if( $terv_ear === false ) { $db_error = true; }
    $terv     = $adatbazis_kapcsolat->s2('stat_id', 'energia_terv', "`recid`='{$terv_ear['energia_terv_id']}'");
    if( $terv === false ) { $db_error = true; }
    if($terv['stat_id']!=ENERGIATERV_STAT_MUNKAKOZI){
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => 'Csak munkaközi terv szerkeszthető!',
            'title' => 'Hiba'
        ));

        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if( !_jog(JOG_ENERGIA_EGYSEGAR_KEZEL) ) {
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga tervet szerkeszteni!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    // Sor törlése
    $sql = $adatbazis_kapcsolat->d('energia_terv_ear', "`recid`='{$egysegar_recid}'");
    if( $sql === false ) { $db_error = true; }

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

// Energiaterv véglegesítése
if (isset($_POST['energiaterv_veglegesit'])) {

    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat->s2('ikt_szam stat_id', 'energia_terv', "`recid`='{$recid}'");
    if( $sor === false ) { $db_error = true; }
    
    // Státusz ellenőrzése
    if($sor['stat_id']!=ENERGIATERV_STAT_MUNKAKOZI){
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => 'Csak munkaközi terv szerkeszthető!',
            'title' => 'Hiba'
        ));

        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if( !_jog(JOG_ENERGIA_EGYSEGAR_KEZEL) ) {
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga tervet szerkeszteni!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }
    
    // Ha már van iktató szám, akkor hibaüzenet
    if(trim($sor['ikt_szam'])){
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => 'Az energia tervnek már van tervszáma ('.$sor['ikt_szam'].'), így nem véglegesíthető!',
            'title' => 'Hiba'
        ));

        $weblap->leallit();
        exit();
    }
    
    // Ha nincsenek egységár sorok, akkor nem lehet véglegesíteni
    $sql = $adatbazis_kapcsolat->s('recid', 'energia_terv_ear', "`energia_terv_id`='{$recid}'");
    if( $sql === false ) { $db_error = true; }
    if(!$sql){
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => 'Az energia tervnek nincsenek egységár sorai, így nem véglegesíthető!',
            'title' => 'Hiba'
        ));

        $weblap->leallit();
        exit();
    }
    
    // Ha vannak olyan egységár sorok, amik nincsenek kitöltve (ha ki van töltve akkor a ktgnem_id-nek már biztosan van értéke), akkor szintén hiba
    $sql = $adatbazis_kapcsolat->s('recid', 'energia_terv_ear', "`energia_terv_id`='{$recid}' AND `ktgnem_id`=''");
    if( $sql === false ) { $db_error = true; }
    if($sql){
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => 'Az energia tervnek van még ki nem töltött egységár sora, így nem véglegesíthető!',
            'title' => 'Hiba'
        ));

        $weblap->leallit();
        exit();
    }


    if( !addStatusz( 'energia_terv_statusz', $recid, ENERGIATERV_STAT_KESZ ) ) {
        $db_error = true;
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

// CSV generálása
if (isset($_GET['csv'])) {
    
    if (isset($_GET['egysegarak'])) {
        $megnevezes .= '_egysegarak';
    }

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

        if( $_SESSION['nyomtatas_ids'] ) {
            
            // Energia egységár exportja
            if (isset($_GET['egysegarak'])) {
                
                $sql = 'SELECT
                            `telephely_idk` as \'Szállodák\',
                            `dijelem` as \'Díjelem\',
                            `ktgnem_id` as \'Költségnem kód\',
                            `ktgnem_id` as \'Költségnem név\',
                            `mee` as \'Mee\',
                            `ear01` as \'Egységár Január\',
                            `dnem01` as \'Devizanem Január\',
                            `arf01` as \'Árfolyam Január\',
                            `ear02` as \'Egységár Február\',
                            `dnem02` as \'Devizanem Február\',
                            `arf02` as \'Árfolyam Február\',
                            `ear03` as \'Egységár Március\',
                            `dnem03` as \'Devizanem Március\',
                            `arf03` as \'Árfolyam Március\',
                            `ear04` as \'Egységár Április\',
                            `dnem04` as \'Devizanem Április\',
                            `arf04` as \'Árfolyam Április\',
                            `ear05` as \'Egységár Május\',
                            `dnem05` as \'Devizanem Május\',
                            `arf05` as \'Árfolyam Május\',
                            `ear06` as \'Egységár Június\',
                            `dnem06` as \'Devizanem Június\',
                            `arf06` as \'Árfolyam Június\',
                            `ear07` as \'Egységár Július\',
                            `dnem07` as \'Devizanem Július\',
                            `arf07` as \'Árfolyam Július\',
                            `ear08` as \'Egységár Augusztus\',
                            `dnem08` as \'Devizanem Augusztus\',
                            `arf08` as \'Árfolyam Augusztus\',
                            `ear09` as \'Egységár Szeptember\',
                            `dnem09` as \'Devizanem Szeptember\',
                            `arf09` as \'Árfolyam Szeptember\',
                            `ear10` as \'Egységár Október\',
                            `dnem10` as \'Devizanem Október\',
                            `arf10` as \'Árfolyam Október\',
                            `ear11` as \'Egységár November\',
                            `dnem11` as \'Devizanem November\',
                            `arf11` as \'Árfolyam November\',
                            `ear12` as \'Egységár December\',
                            `dnem12` as \'Devizanem December\',
                            `arf12` as \'Árfolyam December\'
                            
                            FROM `'.get_db_tabla().'energia_terv_ear` WHERE `energia_terv_id`=\''.$_GET['id'].'\'
                            ';
                $query      = mysqli_query($db_kapcs, $sql);
                
                $eterv_sor = $adatbazis_kapcsolat->s2('ikt_szam ev stat_id', 'energia_terv', "`recid`='{$_GET['id']}'");

                // CSV első sora évszám, tervszám (ha van), státusz
                $csvhead    = array();
                $csvhead[] = iconv2($mibol, $mibe, $eterv_sor['ev']);
                $csvhead[] = iconv2($mibol, $mibe, $eterv_sor['ikt_szam']);
                $csvhead[] = iconv2($mibol, $mibe, getStatText('fenntartasi_terv', $eterv_sor['stat_id']) );
                fputcsv($open, $csvhead, ';');
                
                $csvhead    = array();
                while($fejlec_nevek = mysqli_fetch_field($query)){
                    $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
                }
                fputcsv($open, $csvhead, ';');

                $i = 0;
                while( $sorok = mysqli_fetch_assoc($query) ){

                    $csvcont = array();
                    foreach( $sorok as $kulcs => $sor ) {
                        if($kulcs=='Szállodák'){
                            $telepehely_nevek = array();
                            $telephely_idk = explode(',', $sor);
                            foreach($telephely_idk as $t_id){
                                array_push($telepehely_nevek, getSzallodaNev($t_id));
                            }
                            $sor = implode(', ', $telepehely_nevek);
                        }
                        if($kulcs=='Költségnem név'){
                            $sor = getErtek('nev', 'ktgnem', 'id', $sor);
                        }
                        if( strpos($kulcs, 'Devizanem')!==false ){
                            $sor = getErtekLista(ERTEKLISTA_PENZNEM, $sor);
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
                
            // Energia terv exportja
            }else{
                
                if( strstr($_SESSION['akt_sql'], 'WHERE') ) {
                    $lekerdezes_fromtol = str_replace('WHERE', "WHERE `recid` IN (".$_SESSION['nyomtatas_ids'].") AND ", $lekerdezes_fromtol);
                } else {
                    $lekerdezes_fromtol = str_replace('ORDER', "WHERE `recid` IN (".$_SESSION['nyomtatas_ids'].") ORDER", $lekerdezes_fromtol);
                }
                
                $sql = 'SELECT
                            `ikt_szam` as \'Energia terv szám\',
                            `stat_id` as \'Státusz\',
                            `ev` as \'Tervező év\',
                            `text` as \'Leírás\',
                            `dok_db` as \'Dokumentumok\',
                            `megj_db` as \'Megjegyzések\',
                            `insert_dt` as \'Energia terv dátuma\'
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
                        if($kulcs=='Státusz'){
                            $sor = getStatText('energia_terv', $sor);
                        }
                        if($kulcs=='Energia terv dátuma'){
                            $sor = datum_kiir($sor);
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
        echo '<div id="szamlaBetolt">';
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
                <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="szurkit(\'mehet_megsem_rejt\'); $(\'#szerk_form\').submit()">
                <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="szurkit(\'mehet_megsem_rejt\'); formModositva=false; szamlaBetolt($(\'#szamlaList li:first-of-type\').attr(\'data-recid\'), \''.$megnevezes.'.php\');">
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

    // Módosítás gombok: ha éles, akkor semmilyen gomb nem használható
    } else if( $sor['stat_id']!=ENERGIATERV_STAT_ELES ) {

        // Js, a törlés gombhoz
	    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';

        // Jogosutságkezelés: csak az törölheti, aki létrehozta
        if(!_jog(JOG_ENERGIA_EGYSEGAR_KEZEL)){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Nincs jogosultsága '.$szoveg.' törölni!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        }elseif( $sor['stat_id']!=ENERGIATERV_STAT_MUNKAKOZI ){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Az energia terv státusza nem munkaközi, így nem törölhető!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        }else{
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'A következő egységár terv törlésére készül:<br><br>Tervező év: '.$sor['ev'].'<br><br>Biztos, hogy törli?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
        }

        // Munkaközi státusz és kezelés jog nélkül nincsenek gombok
        if( $sor['stat_id']==ENERGIATERV_STAT_MUNKAKOZI ){
            if(_jog(JOG_ENERGIA_EGYSEGAR_KEZEL)){
                
                // Egységárak fül
                if( isset($_GET['egysegarak']) ){
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Új sor" onclick="uj_egysegar()"> ';
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Szálloda" onclick="kapcsolodoSzerkeszt(\'szallodak_energia\');"> ';
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Költségnem és díjelem" onclick="kapcsolodoSzerkeszt(\'energia_terv_koltsegnem_dijelem\');"> ';
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Egységárak" onclick="kapcsolodoSzerkeszt(\'egysegarak\');"> ';
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Sor törlése" onclick="egysegar_sor_torol()"> ';
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Véglegesítés" onclick="energiaterv_veglegesit()"> ';
                // Tervek fül
                }else{
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Véglegesítés" onclick="energiaterv_veglegesit()"> ';
                }

                $gombok = nl2('
                    <div class="form_kuldes">
                        <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="szurkit(\'mehet_megsem_rejt\'); $(\'#szerk_form\').submit()">
                        <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="szurkit(\'mehet_megsem_rejt\'); formModositva=false; szamlaBetolt(\''.$_GET['id'].'\', \''.$megnevezes.'.php\');">
                    </div>
                ').$gombok;
            }
        }
        
        // Élesítés gomb
        if( $sor['stat_id']==ENERGIATERV_STAT_KESZ && _jog(JOG_ENERGIA_EGYSEGAR_KEZEL) ){
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Élesítés" onclick="terv_elesites()"> ';
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
        
    // Ha nem éles a terv, akkor törlésnél jelezzük ezt
    }else{
        $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Az energia terv már éles, így nem törölhető!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
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
            $json_sor[$kulcs] = str_replace(array("\r\n", "\r", "\n", "'"), array("#UJSOR#", "#UJSOR#", "#UJSOR#", "#APOSZTROF#"), $elem);
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
                <th class="egys_szall"><span>Szállodák</span></th>
                <th class="egys_szall_fel"><span>Díjelem</span></th>
                <th><span>Ktgnem kód</span></th>
                <th class="egys_szall_fel"><span>Ktgnem név</span></th>
                <th><span>Mee</span></th>
                <th><span>Egységár Január</span></th>
                <th class="egys_dev"><span>Dev. Jan.</span></th>
                <th><span>Árfolyam Január</span></th>
                <th><span>Egységár Február</span></th>
                <th class="egys_dev"><span>Dev. Feb</span></th>
                <th><span>Árfolyam Február</span></th>
                <th><span>Egységár Március</span></th>
                <th class="egys_dev"><span>Dev. Márc.</span></th>
                <th><span>Árfolyam Március</span></th>
                <th><span>Egységár Április</span></th>
                <th class="egys_dev"><span>Dev. Ápr.</span></th>
                <th><span>Árfolyam Április</span></th>
                <th><span>Egységár Május</span></th>
                <th class="egys_dev"><span>Dev. Máj.</span></th>
                <th><span>Árfolyam Május</span></th>
                <th><span>Egységár Június</span></th>
                <th class="egys_dev"><span>Dev. Jún.</span></th>
                <th><span>Árfolyam Június</span></th>
                <th><span>Egységár Július</span></th>
                <th class="egys_dev"><span>Dev. Júl.</span></th>
                <th><span>Árfolyam Július</span></th>
                <th><span>Egységár Augusztus</span></th>
                <th class="egys_dev"><span>Dev. Aug.</span></th>
                <th><span>Árfolyam Augusztus</span></th>
                <th><span>Egységár Szeptember</span></th>
                <th class="egys_dev"><span>Dev. Szept.</span></th>
                <th><span>Árfolyam Szeptember</span></th>
                <th><span>Egységár Október</span></th>
                <th class="egys_dev"><span>Dev. Okt.</span></th>
                <th><span>Árfolyam Október</span></th>
                <th><span>Egységár November</span></th>
                <th class="egys_dev"><span>Dev. Nov.</span></th>
                <th><span>Árfolyam November</span></th>
                <th><span>Egységár December</span></th>
                <th class="egys_dev"><span>Dev. Dec</span></th>
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
            $szallodak = adatbazis2tomb($szallodak, 'nev');

            // ABC sorrend
            sort($szallodak);

            // Szálloda html
            $szalloda_html = implode(', ', $szallodak);

            // Ktgnem
            $ktgnem = $adatbazis_kapcsolat->s2('id nev', 'ktgnem', "`id`='{$egysegar['ktgnem_id']}'");

            for($i = 1; $i < 13; $i++) {
                $sorszam = sprintf('%02d', $i);

                $egysegar['ear'.$sorszam] = number_format($egysegar['ear'.$sorszam], 3, ',', ' ');
                $egysegar['arf'.$sorszam] = number_format($egysegar['arf'.$sorszam], 5, ',', ' ');
            }
            
            $szamla_html .= '
                <tr id="tr_ear_'.$egysegar['recid'].'" data-recid="'.$egysegar['recid'].'" class="ftt_menny">
                    <td class="wrap">'.$szalloda_html.'</td>
                    <td class="wrap">'.$egysegar['dijelem'].'</td>
                    <td>'.$ktgnem['id'].'</td>
                    <td class="wrap">'.$ktgnem['nev'].'</td>
                    <td>'.$egysegar['mee'].'</td>
                    <td class="r">'.$egysegar['ear01'].'</td>
                    <td>'.erteklista(ERTEKLISTA_PENZNEM, $egysegar['dnem01']).'</td>
                    <td class="r">'.$egysegar['arf01'].'</td>
                    <td class="r">'.$egysegar['ear02'].'</td>
                    <td>'.erteklista(ERTEKLISTA_PENZNEM, $egysegar['dnem02']).'</td>
                    <td class="r">'.$egysegar['arf02'].'</td>
                    <td class="r">'.$egysegar['ear03'].'</td>
                    <td>'.erteklista(ERTEKLISTA_PENZNEM, $egysegar['dnem03']).'</td>
                    <td class="r">'.$egysegar['arf03'].'</td>
                    <td class="r">'.$egysegar['ear04'].'</td>
                    <td>'.erteklista(ERTEKLISTA_PENZNEM, $egysegar['dnem04']).'</td>
                    <td class="r">'.$egysegar['arf04'].'</td>
                    <td class="r">'.$egysegar['ear05'].'</td>
                    <td>'.erteklista(ERTEKLISTA_PENZNEM, $egysegar['dnem05']).'</td>
                    <td class="r">'.$egysegar['arf05'].'</td>
                    <td class="r">'.$egysegar['ear06'].'</td>
                    <td>'.erteklista(ERTEKLISTA_PENZNEM, $egysegar['dnem06']).'</td>
                    <td class="r">'.$egysegar['arf06'].'</td>
                    <td class="r">'.$egysegar['ear07'].'</td>
                    <td>'.erteklista(ERTEKLISTA_PENZNEM, $egysegar['dnem07']).'</td>
                    <td class="r">'.$egysegar['arf07'].'</td>
                    <td class="r">'.$egysegar['ear08'].'</td>
                    <td>'.erteklista(ERTEKLISTA_PENZNEM, $egysegar['dnem08']).'</td>
                    <td class="r">'.$egysegar['arf08'].'</td>
                    <td class="r">'.$egysegar['ear09'].'</td>
                    <td>'.erteklista(ERTEKLISTA_PENZNEM, $egysegar['dnem09']).'</td>
                    <td class="r">'.$egysegar['arf09'].'</td>
                    <td class="r">'.$egysegar['ear10'].'</td>
                    <td>'.erteklista(ERTEKLISTA_PENZNEM, $egysegar['dnem10']).'</td>
                    <td class="r">'.$egysegar['arf10'].'</td>
                    <td class="r">'.$egysegar['ear11'].'</td>
                    <td>'.erteklista(ERTEKLISTA_PENZNEM, $egysegar['dnem11']).'</td>
                    <td class="r">'.$egysegar['arf11'].'</td>
                    <td class="r">'.$egysegar['ear12'].'</td>
                    <td>'.erteklista(ERTEKLISTA_PENZNEM, $egysegar['dnem12']).'</td>
                    <td class="r">'.$egysegar['arf12'].'</td>
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
    }elseif( isset($_GET['dokumentumok']) ) {

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

        // <div class="dt naplo" class nevekből a kijCont kivéve, hogy a megjegyzéseket ne lehessen törölni
        $szamla_html .= nl2('
            <div class="dt naplo" data-tabla="megjegyzes" data-mellek="megjegyzesek">
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
                $szamla_html .= nl2('
                    <div id="naploSor-'.$sor['recid'].'" class="dtr nSor kijSor" data-id="'.$sor['recid'].'" data-i="'.$i.'">
                        <div class="dtc">'.htmlspecialchars_decode( nl2br2($sor['megj']) ).'</div>
                        <div class="dtc">'.$sor['insert_usr'].' '.getSzerepkorNev($sor['szerepkor']).'</div>
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
function szalloda_koltsnem_dijelem_azonos($terv_id, $telephely_idk, $ktgnem_id, $dijelem, $ear_id = ''){

    global $adatbazis_kapcsolat;

    $telephely_idk  = trim($telephely_idk);
    $ktgnem_id      = trim($ktgnem_id);
    $dijelem        = trim($dijelem);

    if($ear_id){
        $van_egyezes = $adatbazis_kapcsolat->s('recid', 'energia_terv_ear', "`recid`!='{$ear_id}' AND `energia_terv_id`='{$terv_id}' AND `telephely_idk` LIKE '%{$telephely_idk}%' AND `ktgnem_id`='{$ktgnem_id}' AND `dijelem`='{$dijelem}'");
        if($van_egyezes===false){ return false; }
    }else{
        $van_egyezes = $adatbazis_kapcsolat->s('recid', 'energia_terv_ear', "`energia_terv_id`='{$terv_id}' AND `telephely_idk` LIKE '%{$telephely_idk}%' AND `ktgnem_id`='{$ktgnem_id}' AND `dijelem`='{$dijelem}'");
        if($van_egyezes===false){ return false; }    
    }
    
    if( $telephely_idk && $ktgnem_id && $dijelem && $van_egyezes ){
        return 'Azonos díjelem megnevezések szálloda - költségnem összerendelésen belül!';
    }else{
        return '';
    }

}
