<?php
require_once('../weblap.php');

if( !_jog(JOG_FENNTARTASI_SAJAT_SZ_OLVAS) && !_jog(JOG_FENNTARTASI_MIND_SZ_OLVAS) ) {
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
$akt_tomb               = $fenntartasi_terv_tomb;
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

// Új tervhez szállodák lekérdezése
if (isset($_POST['get_szalloda'])) {
    
    $szlloda_idk = get_akt_login_szallodai();
    $valasszon = '<option value="">-Válasszon-</option>';
    $disabled  = '';
    $focus     = 'popup_telephely';
    // Ha csak egy szállodás, akkor fixen ki van válatsztva
    if( count($szlloda_idk)<2 ){
        $valasszon = '';
        $disabled  = ' disabled';
        $focus     = 'popup_ev';
    }
    $szallodak = $adatbazis_kapcsolat->s('id nev', 'telephely', "`id` IN ('".implode('\', \'', $szlloda_idk)."')");
    $szall_html = nl2('
                <div class="dtc p">
                    Szálloda: 
                </div>
                <div class="dtc p">
                    <select id="popup_telephely"'.$disabled.' class="csillag" onchange="$(\'#popup_ev\').focus();">
                        '.$valasszon.'
                        '.tomb2option($szallodak, 'id', 'nev').'
                    </select>
                </div>
                ');
    
    // Kijelölt tervnek mi a státusza
    $munkakozi = 0;
    $kijelolt = $adatbazis_kapcsolat->s2('stat_id', 'fenntartasi_terv', "`recid`='{$_POST['recid']}'");
    if($kijelolt['stat_id']==ENERGIATERV_STAT_MUNKAKOZI){
        $munkakozi = 1;
    }
    
    echo json_encode( array(
                    'telephely_select'  => $szall_html,
                    'munkakozi'         => $munkakozi,
                    'focus'             => $focus,
                    ) );
    
    $weblap->leallit();
    exit();
}

// Új terv létrehozása
if (isset($_POST['terv_keszit'])) {

    $recid      = $_POST['recid'];
    $kijelolt   = $_POST['kijelolt'];
    $ev         = $_POST['ev'];
    $telephely  = $_POST['telephely'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    // Jog ellenőrzése
    if( !_jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL) && !_jog(JOG_FENNTARTASI_MIND_SZ_KEZEL) ) {
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga tervet készíteni!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    // Szálloda ellenőrzése
    if( !$telephely ) {
        echo json_encode(array(
            'hiba'  => 'Kérjük válasszon szállodát!',
            'focus' => 'popup_telephely',
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

    // Munkaközi terv meglétének ellenőrzése
    if( $adatbazis_kapcsolat->s2('recid', 'fenntartasi_terv', "`ev` = '{$ev}' AND `telephely_id`='{$telephely}' AND `stat_id`='".ENERGIATERV_STAT_MUNKAKOZI."'", '', '1') ) {
        echo json_encode(array(
            //'hiba'  => $ev.' évre már van munkaközi terv.',
            'hiba'  => 'Munkaközi tervverzió nem hozható létre.',
            //'gomb'  => '<input type="button" class="gomb zold" value="Megnyitás" onclick="szamlaBetolt( \''.$energia_terv['recid'].'\', \''.basename(self()).'\', \'modosit\' ); alert2(); popupZar();"><input type="button" class="gomb piros" value="Mégsem" onclick="alert2()">',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    // Ha a kijelölt terv alapján kell létrehozni az újat, akkor lekérjük a kijelölt adatait
    if($kijelolt){

        $energia_terv = $adatbazis_kapcsolat->s2('text', 'fenntartasi_terv', "`recid` = '{$recid}'");
        if( $energia_terv === false ) { $db_error = true; }

        // Új terv létrehozása
        $uj_id = $adatbazis_kapcsolat->i('fenntartasi_terv',    array('ev', 'telephely_id', 'text',                'loginnev',                        'insert_dt',    'insert_usr',                             'update_usr'                              ),
                                                                array($ev,  $telephely,     $energia_terv['text'], $_SESSION[get_db_tabla().'user'],  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']    )
                                        );
        if( $uj_id === false ) { $db_error = true; }
        
        // Gyermek táblák másolása
        $ft_energiak = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_energia', "`fenntartasi_terv_id`='{$recid}'");
        if( $ft_energiak === false ) { $db_error = true; }
        foreach($ft_energiak as $energia){
            $oszlopok   = array('ktgnem_id', 'dijelem', 'efajta', 'mee', 'ear01', 'ear02', 'ear03', 'ear04', 'ear05', 'ear06', 'ear07', 'ear08', 'ear09', 'ear10', 'ear11', 'ear12', 'sorid');
            $ertekek    = array();
            foreach($oszlopok as $oszl){
                array_push($ertekek, $energia[$oszl]);
            }
            array_push($oszlopok, 'fenntartasi_terv_id', 'update_dt', 'update_usr',                             'insert_dt',    'insert_usr'                            );
            array_push($ertekek,  $uj_id,                'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  );
            
            $sql = $adatbazis_kapcsolat->i('fenntartasi_terv_energia', $oszlopok, $ertekek);
            if( $sql === false ) { $db_error = true; }
        }

    }else{

        // Új terv létrehozása
        $uj_id = $adatbazis_kapcsolat->i('fenntartasi_terv', array('ev', 'telephely_id', 'loginnev',                        'insert_dt',    'insert_usr',                             'update_usr'                            ),
                                                             array($ev,  $telephely,     $_SESSION[get_db_tabla().'user'],  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']  )
                                        );
        if( $uj_id === false ) { $db_error = true; }
        
        // Van-e éles energia egységár. Ha van, akkor ez alapján feltöltjük az Energia fület, de csak ekkor, ha újat, üresen töltünk fel. Kijelöt alapján a kijelölt energia sorai kerülnek átmásolásra.
        $uzenet = '';
        $energia = $adatbazis_kapcsolat->s2('recid', 'energia_terv', "`ev`='{$ev}' AND `stat_id`='".ENERGIATERV_STAT_ELES."'");
        if( $energia === false ) { $db_error = true; }
        // Ha van, akkor ez alapján feltöltjük az Energia fület
        if($energia){
            $egysegarak = $adatbazis_kapcsolat->s('*', 'energia_terv_ear', "`energia_terv_id`='{$energia['recid']}' AND `telephely_idk` LIKE '%{$telephely}%'");
            foreach($egysegarak as $ear){
                $oszlopok = array('ktgnem_id', 'dijelem', 'mee', 'ear01', 'ear02', 'ear03', 'ear04', 'ear05', 'ear06', 'ear07', 'ear08', 'ear09', 'ear10', 'ear11', 'ear12', 'sorid');
                $ertekek  = array();
                foreach($oszlopok as $oszl){
                    array_push($ertekek, $ear[$oszl]);
                }
                array_push($oszlopok, 'update_dt', 'fenntartasi_terv_id',   'update_usr',                            'insert_dt',    'insert_usr'                            );
                array_push($ertekek,  'NOW()',     $uj_id,                  $_SESSION[get_db_tabla().'teljes_nev'],  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  );
                $adatbazis_kapcsolat->i('fenntartasi_terv_energia', $oszlopok, $ertekek);
            }
        // Ha nincs, akkor is minden megy tovább, de tájékoztatjuk, hogy az Energia fül nem került feltöltésre sorokkal.
        }else{
            $uzenet = 'Energia egységár tábla hiánya miatt energia tervtábla készítése nem lehetséges.';
        }

    }

    // Kezdőstátusz beállítása
    $sql = $adatbazis_kapcsolat -> i('fenntartasi_terv_statusz',
                                        array('fenntartasi_terv_recid', 'stat_id',                   'login',                           'insert_dt',    'insert_usr',                             'update_usr'                           ),
                                        array($uj_id,                   ENERGIATERV_STAT_MUNKAKOZI,  $_SESSION[get_db_tabla().'user'],  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'] )
                                    );
    if( $sql === false ) { $db_error = true; }
 
    $sql = $adatbazis_kapcsolat->u('fenntartasi_terv', array('stat_id'), array(ENERGIATERV_STAT_MUNKAKOZI), "`recid`='{$uj_id}'");
    if( $sql === false ) { $db_error = true; }
    

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz'   => 'siker',
            'uzenet'    => $uzenet,
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

// Éles-e már az energia terv státusza?
if (isset($_POST['get_statusz'])) {
    
    $terv = $adatbazis_kapcsolat->s2('stat_id', 'energia_terv', "`recid`='{$_POST['recid']}'");
    if($terv['stat_id']==ENERGIATERV_STAT_ELES){
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => 'A fenntartási terv már éles, így nem szerkeszthető!',
            'title' => 'Hiba'
        ));

    }
    
    $weblap->leallit();
    exit();
}

// Energia mennyiség táblázat lekérdezése
if (isset($_POST['get_mennyisegek'])) {
    $id = $_POST['id'];

    if( $id ) {
        $html = '';
        $honapok = array('Január', 'Február', 'Március', 'Április', 'Május', 'Június', 'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December');

        // Egységár sor lekérdezése
        $ft_energia = $adatbazis_kapcsolat->s2('*', 'fenntartasi_terv_energia', "`recid`='{$id}'");
        if( $ft_energia ) {
            $html = nl2('
                <div class="dt egysegarak_tablazat" style="width: auto">
                    <div class="dtr">
                        <div class="dtc"></div>
                        <div class="dtc">Mennyiség</div>
                    </div>
            ');

            foreach($honapok as $key => $honap) {
                $sorszam = sprintf('%02d', $key + 1);

                $html .= nl2('
                    <div class="dtr">
                        <div class="dtc" style="padding-right: 0.5rem;">'.$honap.'</div>
                        <div class="dtc"><input type="text" name="em'.$sorszam.'" id="em'.$sorszam.'" class="f_num" data-tort="2" value="'.str_replace('.', ',', $ft_energia['em'.$sorszam]).'"></div>
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
            'hiba' => 'Energia mennyiségek megadásához válasszon ki egy sort!'
        ));
    }

    $weblap->leallit();
    exit();
}

// Energia mennyiségek mentése
if (isset($_POST['ft_energiaKitolt'])) {
    $mit  = array();
    $mire = array();
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $energia = $adatbazis_kapcsolat->s2('*', 'fenntartasi_terv_energia', "`recid`='{$_POST['egysegarsor_id']}'");
    if( $energia === false ) { $db_error = true; }
    
    for($i = 1; $i <= 12; $i++) {
        $sorszam = sprintf('%02d', $i);

        // Mennyiség
        $mennyiseg = str_replace(',', '.', $_POST['em'.$sorszam]);
        $ertek     = round($energia['ear'.$sorszam]*$mennyiseg);
        array_push($mit,    'em'.$sorszam, 'ft'.$sorszam);
        array_push($mire,   $mennyiseg,    $ertek       );
    }

    // Frissítés
    $sql = $adatbazis_kapcsolat -> u('fenntartasi_terv_energia', $mit, $mire, "`recid`='{$_POST['egysegarsor_id']}'");
    if( $sql === false ) { $db_error = true; }
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'OK',
            'recid'   => $_POST['egysegarsor_id']
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

// Fenntartási terv élesítése
if (isset($_POST['fenntartasi_terv_elesites'])) {

    $recid = $_POST['recid'];
    $mehet = $_POST['mehet'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $fenntartasi_terv = $adatbazis_kapcsolat->s2('stat_id ev telephely_id ikt_szam', 'fenntartasi_terv', "`recid` = '{$recid}'");
    if( $fenntartasi_terv === false ) { $db_error = true; }

    // Státusz ellenőrzése
    if( $fenntartasi_terv['stat_id'] != ENERGIATERV_STAT_KESZ ) {
        echo json_encode(array(
            'hiba' => 'Csak kész terv élesíthető!',
        ));
        $weblap->leallit();
        exit();
    }

    // Jog ellenőrzése
    if( !_jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL) && !_jog(JOG_FENNTARTASI_MIND_SZ_KEZEL) ) {
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga tervet élesíteni!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    // Ha van olyan terv, ami azonos évszámú, szállodájú, és éles, akkor azt készre kell vissza léptetni
    $van_eles_terv = $adatbazis_kapcsolat->s2('recid ev ikt_szam', 'fenntartasi_terv', "`ev`='{$fenntartasi_terv['ev']}' AND `telephely_id`='{$fenntartasi_terv['telephely_id']}' AND `stat_id`='".ENERGIATERV_STAT_ELES."'");
    if( $van_eles_terv === false ) { $db_error = true; }

    // Előbb kérdezünk
    if(!$mehet){
        if($van_eles_terv){
            echo json_encode(array(
                'hiba'  => getSzallodaNev($fenntartasi_terv['telephely_id']).' szállodának '.$fenntartasi_terv['ev'].' évre már van éles fenntartási terve!<br><br>A jelenlegi éles terv:<br><br>Tervező év: '.$van_eles_terv['ev'].'<br>Tervszám: '.$van_eles_terv['ikt_szam'].'<br><br>A következő fenntartási terv élesítésére készül:<br><br>Tervező év: '.$fenntartasi_terv['ev'].'<br>Tervszám: '.$fenntartasi_terv['ikt_szam'].'<br><br>Folytatja?',
                'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="fenntartasi_terv_elesites(\'mehet\')"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">',
            ));
            $weblap->leallit();
            exit();
        }else{
            echo json_encode(array(
                'hiba'  => 'A következő fenntartási terv élesítésére készül:<br><br>Tervező év: '.$fenntartasi_terv['ev'].'<br>Tervszám: '.$fenntartasi_terv['ikt_szam'].'<br><br>Folytatja?',
                'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="fenntartasi_terv_elesites(\'mehet\')"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">',
            ));
            $weblap->leallit();
            exit();
        }
    }

    $kesz_id = 0;
    if($van_eles_terv){
        $kesz_id = $van_eles_terv['recid'];
        if( !addStatusz( 'fenntartasi_terv_statusz', $van_eles_terv['recid'], ENERGIATERV_STAT_KESZ ) ) {
            $db_error = true;
        }
    }

    // Státusz léptetés
    if( !addStatusz( 'fenntartasi_terv_statusz', $recid, ENERGIATERV_STAT_ELES ) ) {
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

// Fenntartási terv véglegesítése
if (isset($_POST['fenntartasterv_veglegesit'])) {

    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat->s2('ikt_szam stat_id', 'fenntartasi_terv', "`recid`='{$recid}'");
    if( $sor === false ) { $db_error = true; }
    
    // Státusz ellenőrzése
    if($sor['stat_id']!=ENERGIATERV_STAT_MUNKAKOZI){
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => 'Csak munkaközi fenntartási terv véglegesíthető!',
            'title' => 'Hiba'
        ));

        $weblap->leallit();
        exit();
    }

    // Ha már van iktató szám, akkor hibaüzenet
    if($sor['ikt_szam']){
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => 'A fenntartási tervnek már van iktató száma ('.$sor['ikt_szam'].'), így nem véglegesíthető!',
            'title' => 'Hiba'
        ));

        $weblap->leallit();
        exit();
    }

    if( !addStatusz( 'fenntartasi_terv_statusz', $recid, ENERGIATERV_STAT_KESZ ) ) {
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
            if( strstr($_SESSION['akt_sql'], 'WHERE') ) {
                $lekerdezes_fromtol = str_replace('WHERE', "WHERE `recid` IN (".$_SESSION['nyomtatas_ids'].") AND ", $lekerdezes_fromtol);
            } else {
                $lekerdezes_fromtol = str_replace('ORDER', "WHERE `recid` IN (".$_SESSION['nyomtatas_ids'].") ORDER", $lekerdezes_fromtol);
            }
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
    
    $sajat_szallodak = get_akt_login_szallodai();
    $olvasas_jog = false;
    if($_GET['id']){
        $telephely = $adatbazis_kapcsolat->s2('telephely_id', 'fenntartasi_terv', "`recid`='{$_GET['id']}'");
        if( _jog(JOG_FENNTARTASI_MIND_SZ_OLVAS) || ( _jog(JOG_FENNTARTASI_SAJAT_SZ_OLVAS) && in_array($telephely['telephely_id'], $sajat_szallodak) ) ){
            $olvasas_jog = true;
        }
    }
    $kezeles_jog = false;
    if( _jog(JOG_FENNTARTASI_MIND_SZ_KEZEL) || ( _jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL) && in_array($sor['telephely_id'], $sajat_szallodak) ) ){
        $kezeles_jog = true;
    }

    if( isset($_GET['modosit']) || isset($_GET['energia']) || isset($_GET['muszaki_anyag']) || isset($_GET['rendsz_karban']) || isset($_GET['eseti_karban']) || isset($_GET['hibaelharitas']) || isset($_GET['bwh']) || isset($_GET['tortenet']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Tervek',
            'energia'       => 'Energia',
            'muszaki_anyag' => 'Műszaki anyag',
            'rendsz_karban' => 'Rendszeres karbantartás',
            'eseti_karban'  => 'Eseti karbantartás',
            'hibaelharitas' => 'Hibaelhárítás',
            'bwh'           => 'Cukrászat BWH',
            'osszesen'      => 'Összesen',
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
        
        if( $kezeles_jog ){
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
        }

    // Módosítás gombok: ha éles, akkor semmilyen gomb nem használható
    } else if( $sor['stat_id']!=ENERGIATERV_STAT_ELES ) {

        // Js, a törlés gombhoz
	    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';

        // Jogosutságkezelés
        if( !_jog(JOG_FENNTARTASI_TERV_TOROL) ){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Nincs jogosultsága fenntartási tervet törölni!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        // Ha van törlés joga, akkor még ellenőrizni kell a terv státuszát+személyzettípust, hogy stimmel-e
        }else{
            // Munkaközit csak területi felelős, Kész tervet pedig MSZK vezető tud csak törölni
            if(
                ($sor['stat_id']==ENERGIATERV_STAT_MUNKAKOZI && $_SESSION[get_db_tabla().'szemelyzet_id']==TERULETI_FELELOS ) || 
                ($sor['stat_id']==ENERGIATERV_STAT_KESZ      && $_SESSION[get_db_tabla().'szemelyzet_id']==MSZK_VEZETO      )
            ){
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'A következő fenntartási terv törlésére készül:<br><br>Tervező év: '.$sor['ev'].'<br>Tervszám: '.$sor['ikt_szam'].'<br><br>Biztos, hogy törli?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
            }else{
                if($sor['stat_id']==ENERGIATERV_STAT_MUNKAKOZI){
                    $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Munkaközi fenntartási tervet csak Területi felelős törölhet!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
                }
                if($sor['stat_id']==ENERGIATERV_STAT_KESZ){
                    $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Kész fenntartási tervet csak MSZK vezető törölhet!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
                }
            }
        }

        // Terv adatlapján
        if(isset($_GET['modosit'])){
            // Élesítés gomb
            if( $sor['stat_id']==ENERGIATERV_STAT_KESZ && (_jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL) || _jog(JOG_FENNTARTASI_MIND_SZ_KEZEL)) ){
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Élesítés" onclick="fenntartasi_terv_elesites()"> ';
            }
        // Ha nem az adatlapon vagyunk
        }else{
            
            if( (_jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL) || _jog(JOG_FENNTARTASI_MIND_SZ_KEZEL)) && $sor['stat_id']==ENERGIATERV_STAT_MUNKAKOZI ){
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Véglegesítés" onclick="fenntartasterv_veglegesit()"> ';
            }
            
            // Egységárak fül
            if( isset($_GET['energia']) ){
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Kitöltés" onclick="kapcsolodoSzerkeszt(\'ft_energia_mennyiseg\');"> ';

            }
            
        }
        

        if( $kezeles_jog ){
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="szurkit(\'mehet_megsem_rejt\'); $(\'#szerk_form\').submit()">
                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="szurkit(\'mehet_megsem_rejt\'); formModositva=false; szamlaBetolt(\''.$_GET['id'].'\', \''.$megnevezes.'.php\');">
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
        
    // Ha nem éles a terv, akkor törlésnél jelezzük ezt
    }else{
        $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'A fenntartási terv már éles, így nem törölhető!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
    }

	// Új rekord felvitele
	if(isset($_GET['uj'])){

        if($kezeles_jog){
        
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

	// Energia
	}elseif(isset($_GET['energia'])){

        $szamla_html .= '
            <table class="szamla_cont_tabla nolink fejlec_padd szep_csikos kisbetu">
            <thead>
            <tr>
                <th class="egys_szall"><span>Tárgy</span></th>
                <th><span>Mee</span></th>
                <th><span>Egységár Január</span></th>
                <th class="egys_dev"><span>Mennyiség Január</span></th>
                <th><span>Érték Január</span></th>
                <th><span>Egységár Február</span></th>
                <th class="egys_dev"><span>Mennyiség Február</span></th>
                <th><span>Érték Február</span></th>
                <th><span>Egységár Március</span></th>
                <th class="egys_dev"><span>Mennyiség Március</span></th>
                <th><span>Érték Március</span></th>
                <th><span>Egységár Április</span></th>
                <th class="egys_dev"><span>Mennyiség Április</span></th>
                <th><span>Érték Április</span></th>
                <th><span>Egységár Május</span></th>
                <th class="egys_dev"><span>Mennyiség Május</span></th>
                <th><span>Érték Május</span></th>
                <th><span>Egységár Június</span></th>
                <th class="egys_dev"><span>Mennyiség Június</span></th>
                <th><span>Érték Június</span></th>
                <th><span>Egységár Július</span></th>
                <th class="egys_dev"><span>Mennyiség Július</span></th>
                <th><span>Érték Július</span></th>
                <th><span>Egységár Augusztus</span></th>
                <th class="egys_dev"><span>Mennyiség Augusztus</span></th>
                <th><span>Érték Augusztus</span></th>
                <th><span>Egységár Szeptember</span></th>
                <th class="egys_dev"><span>Mennyiség Szeptember</span></th>
                <th><span>Érték Szeptember</span></th>
                <th><span>Egységár Október</span></th>
                <th class="egys_dev"><span>Mennyiség Október</span></th>
                <th><span>Érték Október</span></th>
                <th><span>Egységár November</span></th>
                <th class="egys_dev"><span>Mennyiség November</span></th>
                <th><span>Érték November</span></th>
                <th><span>Egységár December</span></th>
                <th class="egys_dev"><span>Mennyiség December</span></th>
                <th><span>Érték December</span></th>
            </tr>
            </thead>
            <tbody>
            ';

        $energiak = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_energia', "`fenntartasi_terv_id`='{$_GET['id']}'");
        foreach($energiak as $energia){
            
            // Számok formzása
            for($i=1; $i<13; $i++){
                if($i<10){
                    $i = '0'.$i;
                }
                $energia['em'.$i] = str_replace('.', ',', $energia['em01']);
            }
            
            $szamla_html .= '
                <tr id="ft_em_'.$energia['recid'].'" data-recid="'.$energia['recid'].'" class="ftt_menny">
                    <td>'.$energia['dijelem'].'</td>
                    <td>'.$energia['mee'].'</td>
                    <td class="r">'.str_replace('.', ',', $energia['ear01']).'</td>
                    <td>'.str_replace('.', ',', $energia['em01']).'</td>
                    <td class="r ft01">'.number_format($energia['ft01'], 0, '', ' ').'</td>
                    <td class="r">'.str_replace('.', ',', $energia['ear02']).'</td>
                    <td>'.str_replace('.', ',', $energia['em02']).'</td>
                    <td class="r ft02">'.number_format($energia['ft02'], 0, '', ' ').'</td>
                    <td class="r">'.str_replace('.', ',', $energia['ear03']).'</td>
                    <td>'.str_replace('.', ',', $energia['em03']).'</td>
                    <td class="r ft03">'.number_format($energia['ft03'], 0, '', ' ').'</td>
                    <td class="r">'.str_replace('.', ',', $energia['ear04']).'</td>
                    <td>'.str_replace('.', ',', $energia['em04']).'</td>
                    <td class="r ft04">'.number_format($energia['ft04'], 0, '', ' ').'</td>
                    <td class="r">'.str_replace('.', ',', $energia['ear05']).'</td>
                    <td>'.str_replace('.', ',', $energia['em05']).'</td>
                    <td class="r ft05">'.number_format($energia['ft05'], 0, '', ' ').'</td>
                    <td class="r">'.str_replace('.', ',', $energia['ear06']).'</td>
                    <td>'.str_replace('.', ',', $energia['em06']).'</td>
                    <td class="r ft06">'.number_format($energia['ft06'], 0, '', ' ').'</td>
                    <td class="r">'.str_replace('.', ',', $energia['ear07']).'</td>
                    <td>'.str_replace('.', ',', $energia['em07']).'</td>
                    <td class="r ft07">'.number_format($energia['ft07'], 0, '', ' ').'</td>
                    <td class="r">'.str_replace('.', ',', $energia['ear08']).'</td>
                    <td>'.str_replace('.', ',', $energia['em08']).'</td>
                    <td class="r ft08">'.number_format($energia['ft08'], 0, '', ' ').'</td>
                    <td class="r">'.str_replace('.', ',', $energia['ear09']).'</td>
                    <td>'.str_replace('.', ',', $energia['em09']).'</td>
                    <td class="r ft09">'.number_format($energia['ft09'], 0, '', ' ').'</td>
                    <td class="r">'.str_replace('.', ',', $energia['ear10']).'</td>
                    <td>'.str_replace('.', ',', $energia['em10']).'</td>
                    <td class="r ft10">'.number_format($energia['ft10'], 0, '', ' ').'</td>
                    <td class="r">'.str_replace('.', ',', $energia['ear11']).'</td>
                    <td>'.str_replace('.', ',', $energia['em11']).'</td>
                    <td class="r ft11">'.number_format($energia['ft11'], 0, '', ' ').'</td>
                    <td class="r">'.str_replace('.', ',', $energia['ear12']).'</td>
                    <td>'.str_replace('.', ',', $energia['em12']).'</td>
                    <td class="r ft12">'.number_format($energia['ft12'], 0, '', ' ').'</td>
                </tr>
                ';
        }

        $szamla_html .= nl2('
                            </tbody>
                            <tfoot>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="r" id="szum_ft01"></td>
                                <td></td>
                                <td></td>
                                <td class="r" id="szum_ft02"></td>
                                <td></td>
                                <td></td>
                                <td class="r" id="szum_ft03"></td>
                                <td></td>
                                <td></td>
                                <td class="r" id="szum_ft04"></td>
                                <td></td>
                                <td></td>
                                <td class="r" id="szum_ft05"></td>
                                <td></td>
                                <td></td>
                                <td class="r" id="szum_ft06"></td>
                                <td></td>
                                <td></td>
                                <td class="r" id="szum_ft07"></td>
                                <td></td>
                                <td></td>
                                <td class="r" id="szum_ft08"></td>
                                <td></td>
                                <td></td>
                                <td class="r" id="szum_ft09"></td>
                                <td></td>
                                <td></td>
                                <td class="r" id="szum_ft10"></td>
                                <td></td>
                                <td></td>
                                <td class="r" id="szum_ft11"></td>
                                <td></td>
                                <td></td>
                                <td class="r" id="szum_ft12"></td>
                            </tfoot>
                        </table>
                        <script>
                        $(document).ready(function () {
                            energia_szummaz();
                        });
                        </script>
                        ');

        // Ha van a get-ben em id, akkor azt a sort jelöljük meg
        if( isset($_GET['em'])){
            $szamla_html .= nl2('
                            <script>
                                $(\'#ft_em_'.$_GET['em'].'\').addClass(\'kijelolve\');
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