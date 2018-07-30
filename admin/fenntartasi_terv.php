<?php
require_once('../weblap.php');

if( !_jog(JOG_FENNTARTASI_SAJAT_SZ_OLVAS) ) {
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
    if( !_jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL) ) {
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
        
        // Új terv létrehozása
        $uj_id = $adatbazis_kapcsolat->i('fenntartasi_terv',    array('ev', 'telephely_id', 'loginnev',                        'insert_dt',    'insert_usr',                             'update_usr'                              ),
                                                                array($ev,  $telephely,     $_SESSION[get_db_tabla().'user'],  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']    )
                                        );
        if( $uj_id === false ) { $db_error = true; }

        // Gyermek táblák másolása
        $ft_energiak = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_energia', "`fenntartasi_terv_id`='{$recid}'");
        if( $ft_energiak === false ) { $db_error = true; }
        foreach($ft_energiak as $energia){
            $oszlopok   = array('ktgnem_id', 'dijelem', 'efajta', 'mee', 'ear01', 'ear02', 'ear03', 'ear04', 'ear05', 'ear06', 'ear07', 'ear08', 'ear09', 'ear10', 'ear11', 'ear12', 'em01', 'em02', 'em03', 'em04', 'em05', 'em06', 'em07', 'em08', 'em09', 'em10', 'em11', 'em12', 'ft01', 'ft02', 'ft03', 'ft04', 'ft05', 'ft06', 'ft07', 'ft08', 'ft09', 'ft10', 'ft11', 'ft12', 'sorid');
            $ertekek    = array();
            foreach($oszlopok as $oszl){
                array_push($ertekek, $energia[$oszl]);
            }
            array_push($oszlopok, 'fenntartasi_terv_id', 'update_dt', 'update_usr',                             'insert_dt',    'insert_usr'                            );
            array_push($ertekek,  $uj_id,                'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  );
            
            $sql = $adatbazis_kapcsolat->i('fenntartasi_terv_energia', $oszlopok, $ertekek);
            if( $sql === false ) { $db_error = true; }
        }
        
        $ft_muszaki = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_muszaki_anyag', "`fenntartasi_terv_id`='{$recid}'");
        if( $ft_muszaki === false ) { $db_error = true; }
        foreach($ft_muszaki as $muszaki){
            $oszlopok   = array('ktgnem_id', 'ft01', 'ft02', 'ft03', 'ft04', 'ft05', 'ft06', 'ft07', 'ft08', 'ft09', 'ft10', 'ft11', 'ft12', 'sorid');
            $ertekek    = array();
            foreach($oszlopok as $oszl){
                array_push($ertekek, $muszaki[$oszl]);
            }
            array_push($oszlopok, 'fenntartasi_terv_id', 'update_dt', 'update_usr',                             'insert_dt',    'insert_usr'                            );
            array_push($ertekek,  $uj_id,                'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  );
            
            $sql = $adatbazis_kapcsolat->i('fenntartasi_terv_muszaki_anyag', $oszlopok, $ertekek);
            if( $sql === false ) { $db_error = true; }
        }

        $ft_rendszeres = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_rendsz_karban', "`fenntartasi_terv_id`='{$recid}'");
        if( $ft_rendszeres === false ) { $db_error = true; }
        foreach($ft_rendszeres as $rendszeres){
            $oszlopok   = array('ktgnem_id', 'targy', 'ft01', 'ft02', 'ft03', 'ft04', 'ft05', 'ft06', 'ft07', 'ft08', 'ft09', 'ft10', 'ft11', 'ft12', 'sorid');
            $ertekek    = array();
            foreach($oszlopok as $oszl){
                array_push($ertekek, $rendszeres[$oszl]);
            }
            array_push($oszlopok, 'fenntartasi_terv_id', 'update_dt', 'update_usr',                             'insert_dt',    'insert_usr'                            );
            array_push($ertekek,  $uj_id,                'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  );
            
            $sql = $adatbazis_kapcsolat->i('fenntartasi_terv_rendsz_karban', $oszlopok, $ertekek);
            if( $sql === false ) { $db_error = true; }
        }

        $ft_eseti = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_eseti_karban', "`fenntartasi_terv_id`='{$recid}'");
        if( $ft_eseti === false ) { $db_error = true; }
        foreach($ft_eseti as $eseti){
            $oszlopok   = array('ktgnem_id', 'targy', 'leir', 'ft01', 'ft02', 'ft03', 'ft04', 'ft05', 'ft06', 'ft07', 'ft08', 'ft09', 'ft10', 'ft11', 'ft12', 'sorid');
            $ertekek    = array();
            foreach($oszlopok as $oszl){
                array_push($ertekek, $eseti[$oszl]);
            }
            array_push($oszlopok, 'fenntartasi_terv_id', 'update_dt', 'update_usr',                             'insert_dt',    'insert_usr'                            );
            array_push($ertekek,  $uj_id,                'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  );
            
            $sql = $adatbazis_kapcsolat->i('fenntartasi_terv_eseti_karban', $oszlopok, $ertekek);
            if( $sql === false ) { $db_error = true; }
        }

        $ft_hibaelharit = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_hibaelharitas', "`fenntartasi_terv_id`='{$recid}'");
        if( $ft_hibaelharit === false ) { $db_error = true; }
        foreach($ft_hibaelharit as $hibael){
            $oszlopok   = array('ktgnem_id', 'ft01', 'ft02', 'ft03', 'ft04', 'ft05', 'ft06', 'ft07', 'ft08', 'ft09', 'ft10', 'ft11', 'ft12', 'leir', 'sorid');
            $ertekek    = array();
            foreach($oszlopok as $oszl){
                array_push($ertekek, $hibael[$oszl]);
            }
            array_push($oszlopok, 'fenntartasi_terv_id', 'update_dt', 'update_usr',                             'insert_dt',    'insert_usr'                            );
            array_push($ertekek,  $uj_id,                'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  );
            
            $sql = $adatbazis_kapcsolat->i('fenntartasi_terv_hibaelharitas', $oszlopok, $ertekek);
            if( $sql === false ) { $db_error = true; }
        }

        $ft_bwh = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_bwh', "`fenntartasi_terv_id`='{$recid}'");
        if( $ft_bwh === false ) { $db_error = true; }
        foreach($ft_bwh as $bwh){
            $oszlopok   = array('ktgnem_id', 'targy', 'leir', 'ft01', 'ft02', 'ft03', 'ft04', 'ft05', 'ft06', 'ft07', 'ft08', 'ft09', 'ft10', 'ft11', 'ft12', 'sorid');
            $ertekek    = array();
            foreach($oszlopok as $oszl){
                array_push($ertekek, $bwh[$oszl]);
            }
            array_push($oszlopok, 'fenntartasi_terv_id', 'update_dt', 'update_usr',                             'insert_dt',    'insert_usr'                            );
            array_push($ertekek,  $uj_id,                'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  );
            
            $sql = $adatbazis_kapcsolat->i('fenntartasi_terv_bwh', $oszlopok, $ertekek);
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
        $volt_energia = false;
        if($energia){
            $egysegarak = $adatbazis_kapcsolat->s('*', 'energia_terv_ear', "`energia_terv_id`='{$energia['recid']}' AND `telephely_idk` LIKE '%{$telephely}%'");
            foreach($egysegarak as $ear){
                $volt_energia = true;
                $oszlopok = array('ktgnem_id', 'dijelem', 'mee', 'ear01', 'ear02', 'ear03', 'ear04', 'ear05', 'ear06', 'ear07', 'ear08', 'ear09', 'ear10', 'ear11', 'ear12', 'sorid');
                $ertekek  = array();
                foreach($oszlopok as $oszl){
                    array_push($ertekek, $ear[$oszl]);
                }
                array_push($oszlopok, 'update_dt', 'fenntartasi_terv_id',   'update_usr',                            'insert_dt',    'insert_usr'                            );
                array_push($ertekek,  'NOW()',     $uj_id,                  $_SESSION[get_db_tabla().'teljes_nev'],  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  );
                $adatbazis_kapcsolat->i('fenntartasi_terv_energia', $oszlopok, $ertekek);
            }
        }

        // Ha nincs, akkor is minden megy tovább, de tájékoztatjuk, hogy az Energia fül nem került feltöltésre sorokkal.
        if(!$volt_energia){
            $uzenet = 'Energia egységár tábla hiánya miatt energia tervtábla készítése nem lehetséges.';
        }
        
        // Műszaki anyag fül feltöltése
        $ktgnemek = $adatbazis_kapcsolat->s('id', 'ktgnem', "`props` LIKE '%7030%'");
        foreach($ktgnemek as $ktgnem){
            $sql = $adatbazis_kapcsolat->i('fenntartasi_terv_muszaki_anyag',
                                                                    array('ktgnem_id',   'fenntartasi_terv_id', 'insert_dt',    'insert_usr',                             'update_usr'                           ),
                                                                    array($ktgnem['id'], $uj_id,                'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'] )
                                    );
            if( $sql === false ) { $db_error = true; }
        }
        
        // Rendszeres karbantartás fül feltöltése
        $ktgnemek = $adatbazis_kapcsolat->s('id', 'ktgnem', "`props` LIKE '%7031%'");
        foreach($ktgnemek as $ktgnem){
            $sql = $adatbazis_kapcsolat->i('fenntartasi_terv_rendsz_karban',
                                                                    array('ktgnem_id',   'fenntartasi_terv_id', 'insert_dt',    'insert_usr',                             'update_usr'                           ),
                                                                    array($ktgnem['id'], $uj_id,                'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'] )
                                    );
            if( $sql === false ) { $db_error = true; }
        }
        
        // Eseti karbantartás fül feltöltése
        $ktgnemek = $adatbazis_kapcsolat->s('id', 'ktgnem', "`props` LIKE '%7032%'");
        foreach($ktgnemek as $ktgnem){
            $sql = $adatbazis_kapcsolat->i('fenntartasi_terv_eseti_karban',
                                                                    array('ktgnem_id',   'fenntartasi_terv_id', 'insert_dt',    'insert_usr',                             'update_usr'                           ),
                                                                    array($ktgnem['id'], $uj_id,                'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'] )
                                    );
            if( $sql === false ) { $db_error = true; }
        }
        
        // Hibalehárítás fül feltöltése
        $ktgnemek = $adatbazis_kapcsolat->s('id', 'ktgnem', "`props` LIKE '%7033%'");
        foreach($ktgnemek as $ktgnem){
            $sql = $adatbazis_kapcsolat->i('fenntartasi_terv_hibaelharitas',
                                                                    array('ktgnem_id',   'fenntartasi_terv_id', 'insert_dt',    'insert_usr',                             'update_usr'                           ),
                                                                    array($ktgnem['id'], $uj_id,                'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'] )
                                    );
            if( $sql === false ) { $db_error = true; }
        }
        
        // BWH fül feltöltése
        $ktgnemek = $adatbazis_kapcsolat->s('id', 'ktgnem', "`props` LIKE '%7210%'");
        foreach($ktgnemek as $ktgnem){
            $sql = $adatbazis_kapcsolat->i('fenntartasi_terv_bwh',
                                                                    array('ktgnem_id',   'fenntartasi_terv_id', 'insert_dt',    'insert_usr',                             'update_usr'                           ),
                                                                    array($ktgnem['id'], $uj_id,                'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'] )
                                    );
            if( $sql === false ) { $db_error = true; }
        }

    }
    
    // Ha van éles energia terv adott évre, akkor annak recid-ját etároljuk az earrecid-ba
    earrecid_beallit($ev, $uj_id);

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
if (isset($_POST['get_energia_tabla'])) {
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
                        <div class="dtc"><input type="text" name="em'.$sorszam.'" id="em'.$sorszam.'" class="f_num" data-tort="2" value="'.number_format($ft_energia['em'.$sorszam], 2, ',', ' ').'"></div>
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
if (isset($_POST['energia_tabla_ment'])) {
    $mit  = array();
    $mire = array();
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('stat_id ev', 'fenntartasi_terv', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }
    // Ha van éles energia terv adott évre, akkor annak recid-ját etároljuk az earrecid-ba
    earrecid_beallit($sor['ev'], $_POST['id']);
    
    // Státusz ellenőrzése
    if($sor['stat_id']!=ENERGIATERV_STAT_MUNKAKOZI){
        echo json_encode(array(
            'hiba'  => 'Csak munkaközi terv szerkeszthető!',
            'title' => 'Hiba'
        ));
        
        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if(!_jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL) || $_SESSION[get_db_tabla().'szemelyzet_id']!=MUSZAKI_VEZETO){
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga a tervet szerkeszteni!',
            'title' => 'Hiba'
        ));
        
        $weblap->leallit();
        exit();
    }

    $energia = $adatbazis_kapcsolat->s2('*', 'fenntartasi_terv_energia', "`recid`='{$_POST['egysegarsor_id']}'");
    if( $energia === false ) { $db_error = true; }
    
    for($i = 1; $i < 13; $i++) {
        $sorszam = sprintf('%02d', $i);

        // Mennyiség
        $mennyiseg = str_replace(array(',', ' '), array('.', ''), $_POST['em'.$sorszam]);
        $ertek     = round($energia['ear'.$sorszam]*$mennyiseg);
        array_push($mit,    'em'.$sorszam, 'ft'.$sorszam);
        array_push($mire,   $mennyiseg,    $ertek       );
    }

    // Frissítés
    $sql = $adatbazis_kapcsolat -> u('fenntartasi_terv_energia', $mit, $mire, "`recid`='{$_POST['egysegarsor_id']}'");
    if( $sql === false ) { $db_error = true; }
    
    // Logolás
    $muvelet  = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].') '.$_POST['uj'].' recid-jú fenntartási terv Energia fül alatt a '.$_POST['ktgnem_id'].' költségnemű sort módosította.';
    $valtozas = $adatbazis_kapcsolat->gp();
    $sql = $adatbazis_kapcsolat -> i(
                                        'log',
                                        array( 'felh_id',                               'nev',     'valtozas',              'ip'                   ),
                                        array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,  $muvelet.' '.$valtozas,  $_SERVER['REMOTE_ADDR'])
                                    );
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

// Műszaki anyag táblázat lekérdezése
if (isset($_POST['get_muszaki_tabla'])) {
    $id = $_POST['id'];
    $uj = $_POST['uj'];
    if($uj){
        $id = 0;
    }

    if( $id || $uj ) {
        $html = '';
        $honapok = array('Január', 'Február', 'Március', 'Április', 'Május', 'Június', 'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December');

        // Sor lekérdezése
        $muszaki_anyag = $adatbazis_kapcsolat->s2('*', 'fenntartasi_terv_muszaki_anyag', "`recid`='{$id}'");
        if( $muszaki_anyag || $uj ) {
            
            // Új sor beszúrásakor költségnemet kell tudni választani
            $ktgnem_select = '';
            if($uj){
                $ktgnem_option = $adatbazis_kapcsolat->s('id nev', 'ktgnem', "`props` LIKE '%7030%'");
                $ktgnem_option = tomb2option($ktgnem_option, 'id', 'nev');
                $ktgnem_select = nl2('
                    <div class="dtr">
                        <div class="dtc">Költségnem</div>
                        <div class="dtc">
                            <select name="ktgnem_id" id="ktgnem_id" class="csillag">
                                <option value="">-Válasszon-</option>
                                '.$ktgnem_option.'
                            </select>
                        </div>
                    </div>
                    <div class="dtr">
                        <div class="dtc padd">&nbsp;</div>
                        <div class="dtc padd">&nbsp;</div>
                    </div>
                    ');
            }
            
            $html = nl2('
                <div class="dt egysegarak_tablazat" style="width: auto">
                    '.$ktgnem_select.'
                    <div class="dtr">
                        <div class="dtc"></div>
                        <div class="dtc">Összeg</div>
                    </div>
            ');

            foreach($honapok as $key => $honap) {
                $sorszam = sprintf('%02d', $key + 1);

                $html .= nl2('
                    <div class="dtr">
                        <div class="dtc" style="padding-right: 0.5rem;">'.$honap.'</div>
                        <div class="dtc"><input type="text" name="ft'.$sorszam.'" id="ft'.$sorszam.'" class="f_num" data-tort="0" value="'.number_format($muszaki_anyag['ft'.$sorszam], 0, '', ' ').'"></div>
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
            'hiba' => 'Műszaki anyag megadásához válasszon ki egy sort!'
        ));
    }

    $weblap->leallit();
    exit();
}

// Műszaki anyag tábla mentése
if (isset($_POST['muszaki_tabla_ment'])) {
    $mit  = array();
    $mire = array();
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('stat_id ev', 'fenntartasi_terv', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }
    // Ha van éles energia terv adott évre, akkor annak recid-ját etároljuk az earrecid-ba
    earrecid_beallit($sor['ev'], $_POST['id']);
    
    // Státusz ellenőrzése
    if($sor['stat_id']!=ENERGIATERV_STAT_MUNKAKOZI){
        echo json_encode(array(
            'hiba'  => 'Csak munkaközi terv szerkeszthető!',
            'title' => 'Hiba'
        ));
        
        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if(!_jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL)){
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga a tervet szerkeszteni!',
            'title' => 'Hiba'
        ));
        
        $weblap->leallit();
        exit();
    }
    
    // Ha új feltöltés, akkor ellenőrizni kell, hogy a ktsgnem ki van-e töltve
//    if (isset($_POST['uj']) && !$_POST['ktgnem_id']) {
//        echo json_encode(array(
//            'hiba'  => 'Kérjük válasszon költségnemet!',
//            'focus' => 'ktgnem_id',
//            'title' => 'Hiba'
//        ));
//        $weblap->leallit();
//        exit();
//    }

    // Frissítés
    for($i = 1; $i <= 12; $i++) {
        $sorszam = sprintf('%02d', $i);
        array_push($mit,  'ft'.$sorszam                                 );
        array_push($mire, str_replace(' ', '', $_POST['ft'.$sorszam])   );
    }
    
//    if (isset($_POST['uj'])) {
//        array_push($mit,  'fenntartasi_terv_id', 'ktgnem_id',         'insert_usr',                           'insert_dt',  'update_usr',                           'update_dt' );
//        array_push($mire, $_POST['uj'],          $_POST['ktgnem_id'], $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',      $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()'     );
//        $sor_id = $adatbazis_kapcsolat -> i('fenntartasi_terv_muszaki_anyag', $mit, $mire);
//        if( $sor_id === false ) { $db_error = true; }
//        
//        // Logolás
//        $muvelet  = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].') '.$_POST['uj'].' recid-jú fenntartási terv Műszaki anyag fül alatt új sort töltött fel '.$_POST['ktgnem_id'].' költségnemmel.';
//        $valtozas = $adatbazis_kapcsolat->gp();
//        $sql = $adatbazis_kapcsolat -> i(
//                                            'log',
//                                            array( 'felh_id',                               'nev',     'valtozas',              'ip'                   ),
//                                            array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,  $muvelet.' '.$valtozas,  $_SERVER['REMOTE_ADDR'])
//                                        );
//        if( $sql === false ) { $db_error = true; }
//    }else{
        array_push($mit,  'update_usr'                              );
        array_push($mire, $_SESSION[get_db_tabla().'teljes_nev']    );
        $sql = $adatbazis_kapcsolat -> u('fenntartasi_terv_muszaki_anyag', $mit, $mire, "`recid`='{$_POST['egysegarsor_id']}'");
        if( $sql === false ) { $db_error = true; }
        $sor_id = $_POST['egysegarsor_id'];
        
        // Logolás
        $muvelet  = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].') '.$_POST['uj'].' recid-jú fenntartási terv Műszaki anyag fül alatt a '.$_POST['ktgnem_id'].' költségnemű sort módosította.';
        $valtozas = $adatbazis_kapcsolat->gp();
        $sql = $adatbazis_kapcsolat -> i(
                                            'log',
                                            array( 'felh_id',                               'nev',     'valtozas',              'ip'                   ),
                                            array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,  $muvelet.' '.$valtozas,  $_SERVER['REMOTE_ADDR'])
                                        );
        if( $sql === false ) { $db_error = true; }
//    }
    
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'OK',
            'recid'   => $sor_id
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

// Rendszeres karbantartás táblázat lekérdezése
if (isset($_POST['get_rendszeres_tabla'])) {
    $id = $_POST['id'];
    $uj = $_POST['uj'];
    if($uj){
        $id = 0;
    }

    if( $id || $uj ) {
        $html = '';
        $honapok = array('Január', 'Február', 'Március', 'Április', 'Május', 'Június', 'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December');

        // Sor lekérdezése
        $rendszeres = $adatbazis_kapcsolat->s2('*', 'fenntartasi_terv_rendsz_karban', "`recid`='{$id}'");
        if( $rendszeres || $uj ) {
            
            // Új sor beszúrásakor költségnemet kell tudni választani
            $ktgnem_select = '';
            if($uj){
                $ktgnem_option = $adatbazis_kapcsolat->s('id nev', 'ktgnem', "`props` LIKE '%7031%'");
                $ktgnem_option = tomb2option($ktgnem_option, 'id', 'nev');
                $ktgnem_select = nl2('
                    <div class="dtr">
                        <div class="dtc">Költségnem</div>
                        <div class="dtc">
                            <select name="ktgnem_id" id="ktgnem_id" class="csillag">
                                <option value="">-Válasszon-</option>
                                '.$ktgnem_option.'
                            </select>
                        </div>
                    </div>
                    <div class="dtr">
                        <div class="dtc padd">&nbsp;</div>
                        <div class="dtc padd">&nbsp;</div>
                    </div>
                    ');
            }
            $html = nl2('
                <div class="dt egysegarak_tablazat" style="width: auto">
                    '.$ktgnem_select.'
                    <div class="dtr">
                        <div class="dtc">Tárgy</div>
                        <div class="dtc"><input type="text" name="targy" id="targy" value="'.$rendszeres['targy'].'" maxlength="200"></div>
                    </div>
                    <div class="dtr">
                        <div class="dtc padd">&nbsp;</div>
                        <div class="dtc padd">&nbsp;</div>
                    </div>
                    <div class="dtr">
                        <div class="dtc"></div>
                        <div class="dtc">Összeg</div>
                    </div>
            ');

            foreach($honapok as $key => $honap) {
                $sorszam = sprintf('%02d', $key + 1);

                $html .= nl2('
                    <div class="dtr">
                        <div class="dtc" style="padding-right: 0.5rem;">'.$honap.'</div>
                        <div class="dtc"><input type="text" name="ft'.$sorszam.'" id="ft'.$sorszam.'" class="f_num" data-tort="0" value="'.number_format($rendszeres['ft'.$sorszam], 0, '', ' ').'"></div>
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
            'hiba' => 'Műszaki anyag megadásához válasszon ki egy sort!'
        ));
    }

    $weblap->leallit();
    exit();
}

// Rendszeres karbantartás tábla mentése
if (isset($_POST['rendszeres_tabla_ment'])) {
    $mit  = array();
    $mire = array();
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('stat_id ev', 'fenntartasi_terv', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }
    
    // Ha van éles energia terv adott évre, akkor annak recid-ját etároljuk az earrecid-ba
    earrecid_beallit($sor['ev'], $_POST['id']);
    
    // Státusz ellenőrzése
    if($sor['stat_id']!=ENERGIATERV_STAT_MUNKAKOZI){
        echo json_encode(array(
            'hiba'  => 'Csak munkaközi terv szerkeszthető!',
            'title' => 'Hiba'
        ));
        
        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if(!_jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL)){
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga a tervet szerkeszteni!',
            'title' => 'Hiba'
        ));
        
        $weblap->leallit();
        exit();
    }
    
    // Ha új feltöltés, akkor ellenőrizni kell, hogy a ktsgnem ki van-e töltve
    if (isset($_POST['uj']) && !$_POST['ktgnem_id']) {
        echo json_encode(array(
            'hiba'  => 'Kérjük válasszon költségnemet!',
            'focus' => 'ktgnem_id',
            'title' => 'Hiba'
        ));
        $weblap->leallit();
        exit();
    }

    // Frissítés
    for($i = 1; $i <= 12; $i++) {
        $sorszam = sprintf('%02d', $i);
        array_push($mit,  'ft'.$sorszam                                 );
        array_push($mire, str_replace(' ', '', $_POST['ft'.$sorszam])   );
    }
    
    // Tárgy
    array_push($mit,    'targy'           );
    array_push($mire,   $_POST['targy']   );
    
    if (isset($_POST['uj'])) {
        array_push($mit,  'fenntartasi_terv_id', 'ktgnem_id',         'insert_usr',                           'insert_dt',  'update_usr',                           'update_dt', 'sorid' );
        array_push($mire, $_POST['uj'],          $_POST['ktgnem_id'], $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',      $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',     time()  );
        $sor_id = $adatbazis_kapcsolat -> i('fenntartasi_terv_rendsz_karban', $mit, $mire);
        if( $sor_id === false ) { $db_error = true; }
        
        // Logolás
        $muvelet  = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].') '.$_POST['uj'].' recid-jú fenntartási terv Rendszeres karbantartás fül alatt új sort töltött fel '.$_POST['ktgnem_id'].' költségnemmel.';
        $valtozas = $adatbazis_kapcsolat->gp();
        $sql = $adatbazis_kapcsolat -> i(
                                            'log',
                                            array( 'felh_id',                               'nev',     'valtozas',              'ip'                   ),
                                            array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,  $muvelet.' '.$valtozas,  $_SERVER['REMOTE_ADDR'])
                                        );
        if( $sql === false ) { $db_error = true; }
    }else{
        array_push($mit,  'update_usr'                              );
        array_push($mire, $_SESSION[get_db_tabla().'teljes_nev']    );
        $sql = $adatbazis_kapcsolat -> u('fenntartasi_terv_rendsz_karban', $mit, $mire, "`recid`='{$_POST['egysegarsor_id']}'");
        if( $sql === false ) { $db_error = true; }
        $sor_id = $_POST['egysegarsor_id'];
        
        // Logolás
        $muvelet  = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].') '.$_POST['uj'].' recid-jú fenntartási terv Rendszeres fül alatt a '.$_POST['ktgnem_id'].' költségnemű sort módosította.';
        $valtozas = $adatbazis_kapcsolat->gp();
        $sql = $adatbazis_kapcsolat -> i(
                                            'log',
                                            array( 'felh_id',                               'nev',     'valtozas',              'ip'                   ),
                                            array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,  $muvelet.' '.$valtozas,  $_SERVER['REMOTE_ADDR'])
                                        );
        if( $sql === false ) { $db_error = true; }
    }
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'OK',
            'recid'   => $sor_id
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

// Eseti karbantartás táblázat lekérdezése
if (isset($_POST['get_eseti_tabla'])) {
    $id = $_POST['id'];
    $uj = $_POST['uj'];
    if($uj){
        $id = 0;
    }

    if( $id || $uj ) {
        $html = '';
        $honapok = array('Január', 'Február', 'Március', 'Április', 'Május', 'Június', 'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December');

        // Sor lekérdezése
        $eseti = $adatbazis_kapcsolat->s2('*', 'fenntartasi_terv_eseti_karban', "`recid`='{$id}'");
        if( $eseti || $uj ) {
            
            // Új sor beszúrásakor költségnemet kell tudni választani
            $ktgnem_select = '';
            if($uj){
                $ktgnem_option = $adatbazis_kapcsolat->s('id nev', 'ktgnem', "`props` LIKE '%7032%'");
                $ktgnem_option = tomb2option($ktgnem_option, 'id', 'nev');
                $ktgnem_select = nl2('
                    <div class="dtr">
                        <div class="dtc">Költségnem</div>
                        <div class="dtc">
                            <select name="ktgnem_id" id="ktgnem_id" class="csillag">
                                <option value="">-Válasszon-</option>
                                '.$ktgnem_option.'
                            </select>
                        </div>
                    </div>
                    <div class="dtr">
                        <div class="dtc padd">&nbsp;</div>
                        <div class="dtc padd">&nbsp;</div>
                    </div>
                    ');
            }
            $html = nl2('
                <div class="dt egysegarak_tablazat" style="width: auto">
                    '.$ktgnem_select.'
                    <div class="dtr">
                        <div class="dtc">Tárgy</div>
                        <div class="dtc"><input type="text" name="targy" id="targy" value="'.$eseti['targy'].'" maxlength="200"></div>
                    </div>
                    <div class="dtr">
                        <div class="dtc">Leírás</div>
                        <div class="dtc"><textarea name="leir" id="leir">'.$eseti['leir'].'</textarea></div>
                    </div>
                    <div class="dtr">
                        <div class="dtc padd">&nbsp;</div>
                        <div class="dtc padd">&nbsp;</div>
                    </div>
                    <div class="dtr">
                        <div class="dtc"></div>
                        <div class="dtc">Összeg</div>
                    </div>
            ');

            foreach($honapok as $key => $honap) {
                $sorszam = sprintf('%02d', $key + 1);

                $html .= nl2('
                    <div class="dtr">
                        <div class="dtc" style="padding-right: 0.5rem;">'.$honap.'</div>
                        <div class="dtc"><input type="text" name="ft'.$sorszam.'" id="ft'.$sorszam.'" class="f_num" data-tort="0" value="'.number_format($eseti['ft'.$sorszam], 0, '', ' ').'"></div>
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
            'hiba' => 'Műszaki anyag megadásához válasszon ki egy sort!'
        ));
    }

    $weblap->leallit();
    exit();
}

// Eseti karbantartás tábla mentése
if (isset($_POST['eseti_tabla_ment'])) {
    $mit  = array();
    $mire = array();
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('stat_id ev', 'fenntartasi_terv', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }
    // Ha van éles energia terv adott évre, akkor annak recid-ját etároljuk az earrecid-ba
    earrecid_beallit($sor['ev'], $_POST['id']);
    
    // Státusz ellenőrzése
    if($sor['stat_id']!=ENERGIATERV_STAT_MUNKAKOZI){
        echo json_encode(array(
            'hiba'  => 'Csak munkaközi terv szerkeszthető!',
            'title' => 'Hiba'
        ));
        
        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if(!_jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL)){
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga a tervet szerkeszteni!',
            'title' => 'Hiba'
        ));
        
        $weblap->leallit();
        exit();
    }
    
    // Ha új feltöltés, akkor ellenőrizni kell, hogy a ktsgnem ki van-e töltve
    if (isset($_POST['uj']) && !$_POST['ktgnem_id']) {
        echo json_encode(array(
            'hiba'  => 'Kérjük válasszon költségnemet!',
            'focus' => 'ktgnem_id',
            'title' => 'Hiba'
        ));
        $weblap->leallit();
        exit();
    }

    // Frissítés
    for($i = 1; $i <= 12; $i++) {
        $sorszam = sprintf('%02d', $i);
        array_push($mit,  'ft'.$sorszam                                 );
        array_push($mire, str_replace(' ', '', $_POST['ft'.$sorszam])   );
    }
    
    // Tárgy
    array_push($mit,    'targy',         'leir'           );
    array_push($mire,   $_POST['targy'], $_POST['leir']   );

    if (isset($_POST['uj'])) {
        array_push($mit,  'fenntartasi_terv_id', 'ktgnem_id',         'insert_usr',                           'insert_dt',  'update_usr',                           'update_dt',    'sorid' );
        array_push($mire, $_POST['uj'],          $_POST['ktgnem_id'], $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',      $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',        time()  );
        $sor_id = $adatbazis_kapcsolat -> i('fenntartasi_terv_eseti_karban', $mit, $mire);
        if( $sor_id === false ) { $db_error = true; }
        
        // Logolás
        $muvelet  = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].') '.$_POST['uj'].' recid-jú fenntartási terv Eseti karbantartás fül alatt új sort töltött fel '.$_POST['ktgnem_id'].' költségnemmel.';
        $valtozas = $adatbazis_kapcsolat->gp();
        $sql = $adatbazis_kapcsolat -> i(
                                            'log',
                                            array( 'felh_id',                               'nev',     'valtozas',              'ip'                   ),
                                            array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,  $muvelet.' '.$valtozas,  $_SERVER['REMOTE_ADDR'])
                                        );
        if( $sql === false ) { $db_error = true; }
    }else{
        array_push($mit,  'update_usr'                              );
        array_push($mire, $_SESSION[get_db_tabla().'teljes_nev']    );
        $sql = $adatbazis_kapcsolat -> u('fenntartasi_terv_eseti_karban', $mit, $mire, "`recid`='{$_POST['egysegarsor_id']}'");
        if( $sql === false ) { $db_error = true; }
        $sor_id = $_POST['egysegarsor_id'];
        
        // Logolás
        $muvelet  = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].') '.$_POST['uj'].' recid-jú fenntartási terv Eseti karbantartás fül alatt a '.$_POST['ktgnem_id'].' költségnemű sort módosította.';
        $valtozas = $adatbazis_kapcsolat->gp();
        $sql = $adatbazis_kapcsolat -> i(
                                            'log',
                                            array( 'felh_id',                               'nev',     'valtozas',              'ip'                   ),
                                            array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,  $muvelet.' '.$valtozas,  $_SERVER['REMOTE_ADDR'])
                                        );
        if( $sql === false ) { $db_error = true; }
    }
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'OK',
            'recid'   => $sor_id
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

// Hibaelhárítás táblázat lekérdezése
if (isset($_POST['get_hiba_tabla'])) {
    $id = $_POST['id'];
    $uj = $_POST['uj'];
    if($uj){
        $id = 0;
    }

    if( $id || $uj ) {
        $html = '';
        $honapok = array('Január', 'Február', 'Március', 'Április', 'Május', 'Június', 'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December');

        // Sor lekérdezése
        $hiba = $adatbazis_kapcsolat->s2('*', 'fenntartasi_terv_hibaelharitas', "`recid`='{$id}'");
        if( $hiba || $uj ) {
            
            // Új sor beszúrásakor költségnemet kell tudni választani
            $ktgnem_select = '';
            if($uj){
                $ktgnem_option = $adatbazis_kapcsolat->s('id nev', 'ktgnem', "`props` LIKE '%7033%'");
                $ktgnem_option = tomb2option($ktgnem_option, 'id', 'nev');
                $ktgnem_select = nl2('
                    <div class="dtr">
                        <div class="dtc">Költségnem</div>
                        <div class="dtc">
                            <select name="ktgnem_id" id="ktgnem_id" class="csillag">
                                <option value="">-Válasszon-</option>
                                '.$ktgnem_option.'
                            </select>
                        </div>
                    </div>
                    <div class="dtr">
                        <div class="dtc padd">&nbsp;</div>
                        <div class="dtc padd">&nbsp;</div>
                    </div>
                    ');
            }
            $html = nl2('
                <div class="dt egysegarak_tablazat" style="width: 100%">
                    '.$ktgnem_select.'
                    <div class="dtr">
                        <div class="dtc">Leírás</div>
                        <div class="dtc"><textarea name="leir" id="leir">'.$hiba['leir'].'</textarea></div>
                    </div>
                    <div class="dtr">
                        <div class="dtc padd">&nbsp;</div>
                        <div class="dtc padd">&nbsp;</div>
                    </div>
                    <div class="dtr">
                        <div class="dtc"></div>
                        <div class="dtc">Összeg</div>
                    </div>
            ');

            foreach($honapok as $key => $honap) {
                $sorszam = sprintf('%02d', $key + 1);

                $html .= nl2('
                    <div class="dtr">
                        <div class="dtc" style="padding-right: 0.5rem;">'.$honap.'</div>
                        <div class="dtc"><input type="text" name="ft'.$sorszam.'" id="ft'.$sorszam.'" class="f_num" data-tort="0" value="'.number_format($hiba['ft'.$sorszam], 0, '', ' ').'"></div>
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
            'hiba' => 'Műszaki anyag megadásához válasszon ki egy sort!'
        ));
    }

    $weblap->leallit();
    exit();
}

// Hibaelhárítás tábla mentése
if (isset($_POST['hiba_tabla_ment'])) {
    $mit  = array();
    $mire = array();
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('stat_id ev', 'fenntartasi_terv', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }
    // Ha van éles energia terv adott évre, akkor annak recid-ját etároljuk az earrecid-ba
    earrecid_beallit($sor['ev'], $_POST['id']);
    
    // Státusz ellenőrzése
    if($sor['stat_id']!=ENERGIATERV_STAT_MUNKAKOZI){
        echo json_encode(array(
            'hiba'  => 'Csak munkaközi terv szerkeszthető!',
            'title' => 'Hiba'
        ));
        
        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if(!_jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL)){
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga a tervet szerkeszteni!',
            'title' => 'Hiba'
        ));
        
        $weblap->leallit();
        exit();
    }
    
    // Ha új feltöltés, akkor ellenőrizni kell, hogy a ktsgnem ki van-e töltve
    if (isset($_POST['uj']) && !$_POST['ktgnem_id']) {
        echo json_encode(array(
            'hiba'  => 'Kérjük válasszon költségnemet!',
            'focus' => 'ktgnem_id',
            'title' => 'Hiba'
        ));
        $weblap->leallit();
        exit();
    }

    // Frissítés
    for($i = 1; $i <= 12; $i++) {
        $sorszam = sprintf('%02d', $i);
        array_push($mit,  'ft'.$sorszam                                 );
        array_push($mire, str_replace(' ', '', $_POST['ft'.$sorszam])   );
    }
    
    // Tárgy
    array_push($mit,    'leir'           );
    array_push($mire,   $_POST['leir']   );

    if (isset($_POST['uj'])) {
        array_push($mit,  'fenntartasi_terv_id', 'ktgnem_id',         'insert_usr',                           'insert_dt',  'update_usr',                           'update_dt', 'sorid' );
        array_push($mire, $_POST['uj'],          $_POST['ktgnem_id'], $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',      $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',     time()  );
        $sor_id = $adatbazis_kapcsolat -> i('fenntartasi_terv_hibaelharitas', $mit, $mire);
        if( $sor_id === false ) { $db_error = true; }
        
        // Logolás
        $muvelet  = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].') '.$_POST['uj'].' recid-jú fenntartási terv Hibaelhárítás fül alatt új sort töltött fel '.$_POST['ktgnem_id'].' költségnemmel.';
        $valtozas = $adatbazis_kapcsolat->gp();
        $sql = $adatbazis_kapcsolat -> i(
                                            'log',
                                            array( 'felh_id',                               'nev',     'valtozas',          'ip'                   ),
                                            array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,  $muvelet.' '.$valtozas,  $_SERVER['REMOTE_ADDR'])
                                        );
        if( $sql === false ) { $db_error = true; }
    }else{
        array_push($mit,  'update_usr'                              );
        array_push($mire, $_SESSION[get_db_tabla().'teljes_nev']    );
        $sql = $adatbazis_kapcsolat -> u('fenntartasi_terv_hibaelharitas', $mit, $mire, "`recid`='{$_POST['egysegarsor_id']}'");
        if( $sql === false ) { $db_error = true; }
        $sor_id = $_POST['egysegarsor_id'];
        
        // Logolás
        $muvelet  = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].') '.$_POST['uj'].' recid-jú fenntartási terv Hibaelhárítás fül alatt a '.$_POST['ktgnem_id'].' költségnemű sort módosította.';
        $valtozas = $adatbazis_kapcsolat->gp();
        $sql = $adatbazis_kapcsolat -> i(
                                            'log',
                                            array( 'felh_id',                               'nev',     'valtozas',          'ip'                   ),
                                            array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,  $muvelet.' '.$valtozas,  $_SERVER['REMOTE_ADDR'])
                                        );
        if( $sql === false ) { $db_error = true; }
    }
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'OK',
            'recid'   => $sor_id
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

// BWH táblázat lekérdezése
if (isset($_POST['get_bwh_tabla'])) {
    $id = $_POST['id'];
    $uj = $_POST['uj'];
    if($uj){
        $id = 0;
    }

    if( $id || $uj ) {
        $html = '';
        $honapok = array('Január', 'Február', 'Március', 'Április', 'Május', 'Június', 'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December');

        // Sor lekérdezése
        $bwh = $adatbazis_kapcsolat->s2('*', 'fenntartasi_terv_bwh', "`recid`='{$id}'");
        if( $bwh || $uj ) {
            
            // Új sor beszúrásakor költségnemet kell tudni választani
            $ktgnem_select = '';
            if($uj){
                $ktgnem_option = $adatbazis_kapcsolat->s('id nev', 'ktgnem', "`props` LIKE '%7210%'");
                $ktgnem_option = tomb2option($ktgnem_option, 'id', 'nev');
                $ktgnem_select = nl2('
                    <div class="dtr">
                        <div class="dtc">Költségnem</div>
                        <div class="dtc">
                            <select name="ktgnem_id" id="ktgnem_id" class="csillag">
                                <option value="">-Válasszon-</option>
                                '.$ktgnem_option.'
                            </select>
                        </div>
                    </div>
                    <div class="dtr">
                        <div class="dtc padd">&nbsp;</div>
                        <div class="dtc padd">&nbsp;</div>
                    </div>
                    ');
            }
            $html = nl2('
                <div class="dt egysegarak_tablazat" style="width: 100%">
                    '.$ktgnem_select.'
                    <div class="dtr">
                        <div class="dtc">Tárgy</div>
                        <div class="dtc"><input type="text" name="targy" id="targy" value="'.$bwh['targy'].'" maxlength="200"></div>
                    </div>
                    <div class="dtr">
                        <div class="dtc">Leírás</div>
                        <div class="dtc"><textarea name="leir" id="leir">'.$bwh['leir'].'</textarea></div>
                    </div>
                    <div class="dtr">
                        <div class="dtc padd">&nbsp;</div>
                        <div class="dtc padd">&nbsp;</div>
                    </div>
                    <div class="dtr">
                        <div class="dtc"></div>
                        <div class="dtc">Összeg</div>
                    </div>
            ');

            foreach($honapok as $key => $honap) {
                $sorszam = sprintf('%02d', $key + 1);

                $html .= nl2('
                    <div class="dtr">
                        <div class="dtc" style="padding-right: 0.5rem;">'.$honap.'</div>
                        <div class="dtc"><input type="text" name="ft'.$sorszam.'" id="ft'.$sorszam.'" class="f_num" data-tort="0" value="'.number_format($bwh['ft'.$sorszam], 0, '', ' ').'"></div>
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
            'hiba' => 'Műszaki anyag megadásához válasszon ki egy sort!'
        ));
    }

    $weblap->leallit();
    exit();
}

// BWH tábla mentése
if (isset($_POST['bwh_tabla_ment'])) {
    $mit  = array();
    $mire = array();
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('stat_id ev', 'fenntartasi_terv', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }
    // Ha van éles energia terv adott évre, akkor annak recid-ját etároljuk az earrecid-ba
    earrecid_beallit($sor['ev'], $_POST['id']);
    
    // Státusz ellenőrzése
    if($sor['stat_id']!=ENERGIATERV_STAT_MUNKAKOZI){
        echo json_encode(array(
            'hiba'  => 'Csak munkaközi terv szerkeszthető!',
            'title' => 'Hiba'
        ));
        
        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if(!_jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL)){
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga a tervet szerkeszteni!',
            'title' => 'Hiba'
        ));
        
        $weblap->leallit();
        exit();
    }
    
    // Ha új feltöltés, akkor ellenőrizni kell, hogy a ktsgnem ki van-e töltve
    if (isset($_POST['uj']) && !$_POST['ktgnem_id']) {
        echo json_encode(array(
            'hiba'  => 'Kérjük válasszon költségnemet!',
            'focus' => 'ktgnem_id',
            'title' => 'Hiba'
        ));
        $weblap->leallit();
        exit();
    }

    // Frissítés
    for($i = 1; $i <= 12; $i++) {
        $sorszam = sprintf('%02d', $i);
        array_push($mit,  'ft'.$sorszam                                 );
        array_push($mire, str_replace(' ', '', $_POST['ft'.$sorszam])   );
    }
    
    // Tárgy
    array_push($mit,    'targy',            'leir'           );
    array_push($mire,   $_POST['targy'],    $_POST['leir']   );

    if (isset($_POST['uj'])) {
        array_push($mit,  'fenntartasi_terv_id', 'ktgnem_id',         'insert_usr',                           'insert_dt',  'update_usr',                           'update_dt', 'sorid' );
        array_push($mire, $_POST['uj'],          $_POST['ktgnem_id'], $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',      $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',     time()  );
        $sor_id = $adatbazis_kapcsolat -> i('fenntartasi_terv_bwh', $mit, $mire);
        if( $sor_id === false ) { $db_error = true; }
        
        // Logolás
        $muvelet  = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].') '.$_POST['uj'].' recid-jú fenntartási terv BWH fül alatt új sort töltött fel '.$_POST['ktgnem_id'].' költségnemmel.';
        $valtozas = $adatbazis_kapcsolat->gp();
        $sql = $adatbazis_kapcsolat -> i(
                                            'log',
                                            array( 'felh_id',                               'nev',     'valtozas',          'ip'                   ),
                                            array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,  $muvelet.' '.$valtozas,  $_SERVER['REMOTE_ADDR'])
                                        );
        if( $sql === false ) { $db_error = true; }
    }else{
        array_push($mit,  'update_usr'                              );
        array_push($mire, $_SESSION[get_db_tabla().'teljes_nev']    );
        $sql = $adatbazis_kapcsolat -> u('fenntartasi_terv_bwh', $mit, $mire, "`recid`='{$_POST['egysegarsor_id']}'");
        if( $sql === false ) { $db_error = true; }
        $sor_id = $_POST['egysegarsor_id'];
        
        // Logolás
        $muvelet  = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].') '.$_POST['uj'].' recid-jú fenntartási terv BWH fül alatt a '.$_POST['ktgnem_id'].' költségnemű sort módosította.';
        $valtozas = $adatbazis_kapcsolat->gp();
        $sql = $adatbazis_kapcsolat -> i(
                                            'log',
                                            array( 'felh_id',                               'nev',     'valtozas',          'ip'                   ),
                                            array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,  $muvelet.' '.$valtozas,  $_SERVER['REMOTE_ADDR'])
                                        );
        if( $sql === false ) { $db_error = true; }
    }
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'OK',
            'recid'   => $sor_id
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
    if( !_jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL) ) {
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
    
    // Logolás
    $muvelet = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].') '.$recid.' recid-jú fenntartási tervet élesítette.';
    $sql = $adatbazis_kapcsolat -> i(
                                        'log',
                                        array( 'felh_id',                               'nev',     'valtozas',  'ip'                   ),
                                        array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,  $muvelet,    $_SERVER['REMOTE_ADDR'])
                                    );
    if( $sql === false ) { $db_error = true; }

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

    // Státusz léptetés
    if( !addStatusz( 'fenntartasi_terv_statusz', $recid, ENERGIATERV_STAT_KESZ ) ) {
        $db_error = true;
    }
    
    // Logolás
    $muvelet = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].') '.$recid.' recid-jú fenntartási tervet véglegesítette.';
    $sql = $adatbazis_kapcsolat -> i(
                                        'log',
                                        array( 'felh_id',                               'nev',     'valtozas',  'ip'                   ),
                                        array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,  $muvelet,    $_SERVER['REMOTE_ADDR'])
                                    );
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

// Változtatás jóváhagyása
if (isset($_POST['valtozas_jovahagyas'])) {
    
    $recid = $_POST['id'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('ev stat_id telephely_id', 'fenntartasi_terv', "`recid`='{$recid}'");
    if( $sor === false ) { $db_error = true; }
    
    // Ha van éles energia terv adott évre, akkor annak recid-ját etároljuk az earrecid-ba
    earrecid_beallit($sor['ev'], $_POST['id']);
    
    // Jog ellenőrzése
    if(!_jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL) || $_SESSION[get_db_tabla().'szemelyzet_id']!=MUSZAKI_VEZETO){
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga a tervet szerkeszteni!',
            'title' => 'Hiba'
        ));
        
        $weblap->leallit();
        exit();
    }
    
    // Státusz ellenőrzése
    if($sor['stat_id']!=ENERGIATERV_STAT_MUNKAKOZI){
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => 'Csak munkaközi fenntartási terv módosítható!',
            'title' => 'Hiba'
        ));

        $weblap->leallit();
        exit();
    }
    
    $energia_terv = $adatbazis_kapcsolat->s2('recid', 'energia_terv', "`ev`='{$sor['ev']}' AND `stat_id`='".ENERGIATERV_STAT_ELES."'");
    if( $energia_terv === false ) { $db_error = true; }

    $akt_eear_sorok = $adatbazis_kapcsolat->s('*', 'energia_terv_ear', "`energia_terv_id`='{$energia_terv['recid']}' AND `telephely_idk` LIKE '%{$sor['telephely_id']}%'");
    if( $akt_eear_sorok === false ) { $db_error = true; }
    $eear_soridk = adatbazis2tomb($akt_eear_sorok, 'sorid');
    
    // A fenntartási terv earrecid oszlopát frissítjük
    $sql = $adatbazis_kapcsolat->u('fenntartasi_terv', array('earrecid'), array($energia_terv['recid']), "`recid`='{$recid}'");
    if( $sql === false ) { $db_error = true; }
 
    $energia_soridk = array();
    $energiak = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_energia', "`fenntartasi_terv_id`='{$recid}'");
    if( $energiak === false ) { $db_error = true; }
    foreach($energiak as $energia){
//echo $energia['recid'] . '<br>';
        // Innen tujduk majd, hogy mik az új sorok
        array_push($energia_soridk, $energia['sorid']);
        
        // Ha változtak volna az egységárak, azt felül kell írni
        $energia_ear = $adatbazis_kapcsolat->s2('*', 'energia_terv_ear', "`energia_terv_id`='{$energia_terv['recid']}' AND `sorid`='{$energia['sorid']}'");
        if( $energia_ear === false ) { $db_error = true; }

        // Változott egységár frissítése
        $valtozott_oszlop = array();
        $valtozott_ertek  = array();
        for($i=1; $i<13; $i++){
            if($i<10){
                $i = '0'.$i;
            }
            if($energia_ear['ear'.$i]!=$energia['ear'.$i]){
                array_push($valtozott_oszlop, 'ear'.$i              );
                array_push($valtozott_ertek,  $energia_ear['ear'.$i]);
            }
            if($valtozott_oszlop){
                $sql = $adatbazis_kapcsolat->u('fenntartasi_terv_energia', $valtozott_oszlop, $valtozott_ertek, "`fenntartasi_terv_id`='{$recid}' AND `sorid`='{$energia['sorid']}'");
                if( $sql === false ) { $db_error = true; }
            }
        }

        // Megszűnt sor törlése
        if( !in_array($energia['sorid'], $eear_soridk) ){
            $sql = $adatbazis_kapcsolat->d('fenntartasi_terv_energia', "`fenntartasi_terv_id`='{$recid}' AND `sorid`='{$energia['sorid']}'");
            if( $sql === false ) { $db_error = true; }
        }
    }

    // Új sorok beszúrása
    foreach ($eear_soridk as $e_sorid){
        if(!in_array($e_sorid, $energia_soridk)){
            
            $ear = $adatbazis_kapcsolat->s2('*', 'energia_terv_ear', "`energia_terv_id`='{$energia_terv['recid']}' AND `sorid`='{$e_sorid}'");
            
            $volt_energia = true;
            $oszlopok = array('ktgnem_id', 'dijelem', 'mee', 'ear01', 'ear02', 'ear03', 'ear04', 'ear05', 'ear06', 'ear07', 'ear08', 'ear09', 'ear10', 'ear11', 'ear12', 'sorid');
            $ertekek  = array();
            foreach($oszlopok as $oszl){
                array_push($ertekek, $ear[$oszl]);
            }
            array_push($oszlopok, 'update_dt', 'fenntartasi_terv_id',           'update_usr',                            'insert_dt',    'insert_usr'                            );
            array_push($ertekek,  'NOW()',     $recid/*tehát a $_POST['id']*/,  $_SESSION[get_db_tabla().'teljes_nev'],  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  );
            $sql = $adatbazis_kapcsolat->i('fenntartasi_terv_energia', $oszlopok, $ertekek);
            if( $sql === false ) { $db_error = true; }

        }
    }
    
    // Logolás
    $muvelet = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].') '.$recid.' recid-jú fenntartási terv enrgia adatait frissítette.';
    $sql = $adatbazis_kapcsolat -> i(
                                        'log',
                                        array( 'felh_id',                               'nev',     'valtozas',  'ip'                   ),
                                        array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,  $muvelet,    $_SERVER['REMOTE_ADDR'])
                                    );
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

// Sor törlése
if (isset($_POST['sor_torol'])) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Műszaki anyagot még véletlen se tudjanak törölni
    if($_POST['ful']=='muszaki_anyag' ){
        exit;
    }
    
    $sor = $adatbazis_kapcsolat->s2('stat_id ev', 'fenntartasi_terv', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }
    
    $ful = $adatbazis_kapcsolat->s2('ktgnem_id', 'fenntartasi_terv_'. $_POST['ful'], "`recid`='{$_POST['sor']}'");
    if( $ful === false ) { $db_error = true; }
    
    // Ha van éles energia terv adott évre, akkor annak recid-ját etároljuk az earrecid-ba
    earrecid_beallit($sor['ev'], $_POST['id']);
    
    // Státusz ellenőrzése
    if($sor['stat_id']==ENERGIATERV_STAT_ELES || $sor['stat_id']==ENERGIATERV_STAT_KESZ){
        echo json_encode(array(
            'hiba'  => 'Kész és éles státuszú terv nem törölhető!',
            'title' => 'Hiba'
        ));
        
        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if(!_jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL)){
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga sort törölni!',
            'title' => 'Hiba'
        ));
        
        $weblap->leallit();
        exit();
    }
    
    // Előbb kérdezünk
    if (!isset($_POST['mehet'])) {
        
        echo json_encode(array(
            'hiba'  => 'Biztosan törli a kijelölt sort?',
            'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="sor_torol(\''.$_POST['ful'].'\', \'mehet\')"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">',
            'title' => 'Kérdés'
        ));
        
        $weblap->leallit();
        exit();
    }
    
    // Tényleges törlés
    $sor = $adatbazis_kapcsolat->d('fenntartasi_terv_'. $_POST['ful'], "`recid`='{$_POST['sor']}' AND `fenntartasi_terv_id`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }
    
    // Logolás
    $muvelet = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].') '.$_POST['id'].' recid-jú fenntartási terv '.$_POST['ful'].' füléből törölte a '.$ful['ktgnem_id'].' költségnem sorát.';
    $valtozas = $adatbazis_kapcsolat->gp();
    $sql = $adatbazis_kapcsolat -> i(
                                        'log',
                                        array( 'felh_id',                               'nev',     'valtozas',          'ip'                   ),
                                        array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,  $muvelet.' '.$valtozas,  $_SERVER['REMOTE_ADDR'])
                                    );
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
        if( _jog(JOG_FENNTARTASI_SAJAT_SZ_OLVAS) && in_array($telephely['telephely_id'], $sajat_szallodak) ){
            $olvasas_jog = true;
        }
    }
    $kezeles_jog = false;
    if(  _jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL) && in_array($sor['telephely_id'], $sajat_szallodak) ){
        $kezeles_jog = true;
    }

    if( isset($_GET['modosit']) || isset($_GET['energia']) || isset($_GET['muszaki_anyag']) || isset($_GET['rendsz_karban']) || isset($_GET['eseti_karban']) || isset($_GET['hibaelharitas']) || isset($_GET['bwh']) || isset($_GET['tortenet']) || isset($_GET['osszesen']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
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
            
            // A BWH fül csak Hungária (2120) szálloda esetén látszik
            if( (in_array(2120, $sajat_szallodak) && $sor['telephely_id']==2120) || $key != 'bwh' ){
                $active = 'active';
                if( isset($_GET[$key]) ) {
                    $active = ' class="active"';
                }

                $fulek .= '<a href="javascript: szamlaBetolt(\''.$_GET['id'].'\', \''.basename(self()).'\', \''.$key.'\'); void(0);"'.$active.' data-oldal="'.$key.'">'.$value.'</a>';
            }
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
        // Csak munkaközi terv törölhető
        }elseif($sor['stat_id']!=ENERGIATERV_STAT_MUNKAKOZI){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Csak munkaközi terv törölhető!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        }else{
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'A következő fenntartási terv törlésére készül:<br><br>Tervező év: '.$sor['ev'].'<br>Tervszám: '.$sor['ikt_szam'].'<br><br>Biztos, hogy törli?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
        }

        // Terv adatlapján
        if(isset($_GET['modosit'])){
            // Élesítés gomb
            if( $sor['stat_id']==ENERGIATERV_STAT_KESZ && _jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL) ){
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Élesítés" onclick="fenntartasi_terv_elesites()"> ';
            }
        // Ha nem az adatlapon vagyunk
        }else{
            
            if( _jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL) && $sor['stat_id']==ENERGIATERV_STAT_MUNKAKOZI ){
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Véglegesítés" onclick="fenntartasterv_veglegesit()"> ';
            }
            
            // Fülek alatti gombok csak akkor érhetőek el, ha van is hozzá jogunk, és munkaközi a státusza, kivéve az energiát (ott csak akkor, ha nem éles)
            if( _jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL) && $sor['stat_id']==ENERGIATERV_STAT_MUNKAKOZI ){
                if( isset($_GET['energia']) && $_SESSION[get_db_tabla().'szemelyzet_id']==MUSZAKI_VEZETO ){
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt dn" id="energia_kitolt" value="Kitöltés" onclick="kapcsolodoSzerkeszt(\'ft_energia_mennyiseg\');"> ';
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt dn" id="valtoztatas_jovahagyas" value="Változás jóváhagyás" onclick="valtozas_jovahagyas()"> ';
                }
            }
            if( _jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL) && $sor['stat_id']==ENERGIATERV_STAT_MUNKAKOZI ){
                if( isset($_GET['muszaki_anyag']) ){
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Kitöltés" onclick="kapcsolodoSzerkeszt(\'ft_muszaki_anyag\');"> ';
//                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Új sor" onclick="kapcsolodoSzerkeszt(\'ft_muszaki_anyag_uj\');"> ';
//                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Sor törlés" onclick="sor_torol(\'muszaki_anyag\');"> ';
                }
                if( isset($_GET['rendsz_karban']) ){
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Kitöltés" onclick="kapcsolodoSzerkeszt(\'ft_rendsz_karban\');"> ';
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Új sor" onclick="kapcsolodoSzerkeszt(\'ft_rendsz_karban_uj\');"> ';
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Sor törlés" onclick="sor_torol(\'rendsz_karban\');"> ';
                }
                if( isset($_GET['eseti_karban']) ){
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Kitöltés" onclick="kapcsolodoSzerkeszt(\'ft_eseti_karban\');"> ';
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Új sor" onclick="kapcsolodoSzerkeszt(\'ft_eseti_karban_uj\');"> ';
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Sor törlés" onclick="sor_torol(\'eseti_karban\');"> ';
                }
                if( isset($_GET['hibaelharitas']) ){
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Kitöltés" onclick="kapcsolodoSzerkeszt(\'ft_hibaelharitas\');"> ';
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Új sor" onclick="kapcsolodoSzerkeszt(\'ft_hibaelharitas_uj\');"> ';
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Sor törlés" onclick="sor_torol(\'hibaelharitas\');"> ';
                }
                if( isset($_GET['bwh']) ){
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Kitöltés" onclick="kapcsolodoSzerkeszt(\'ft_bwh\');"> ';
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Új sor" onclick="kapcsolodoSzerkeszt(\'ft_bwh_uj\');"> ';
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Sor törlés" onclick="sor_torol(\'bwh\');"> ';
                }
            }
            
        }
        
        // Csak munkaközi terv szerkeszthető
        if( $kezeles_jog && $sor['stat_id']==ENERGIATERV_STAT_MUNKAKOZI ){
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
                <th class="ktgnem_kod"><span>Ktgnem kód</span></th>
                <th style="min-width: 10rem; width: 10rem"><span>Ktgnem név</span></th>
                <th style="min-width: 20rem; width: 20rem"><span>Tárgy</span></th>
                <th><span>Mee</span></th>
                <th><span>Mennyiség összesen</span></th>
                <th><span>Érték összesen</span></th>
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

        // Nézzük meg, hogy az energia egységár soraiban van-e változás
        $energia_terv   = $adatbazis_kapcsolat->s2('recid', 'energia_terv', "`ev`='{$sor['ev']}' AND `stat_id`='".ENERGIATERV_STAT_ELES."'");
        $akt_eear_sorok = $adatbazis_kapcsolat->s('*', 'energia_terv_ear', "`energia_terv_id`='{$energia_terv['recid']}' AND `telephely_idk` LIKE '%{$sor['telephely_id']}%'");
        $eear_soridk    = adatbazis2tomb($akt_eear_sorok, 'sorid');
        
        $volt_valtozas  = false;
        $energia_soridk = array();
        $energiak = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_energia', "`fenntartasi_terv_id`='{$_GET['id']}'");
        foreach($energiak as $energia){
            
            // Innen tujduk majd, hogy mik az új sorok
            array_push($energia_soridk, $energia['sorid']);
            
            // Ha változtak volna az egységárak, azt felül kell írni
            $energia_ear = $adatbazis_kapcsolat->s2('*', 'energia_terv_ear', "`energia_terv_id`='{$energia_terv['recid']}' AND `sorid`='{$energia['sorid']}'");
            
            // Számok formázása
            $mennyiseg_osszesen = 0;
            $ertek_osszesen     = 0;
            for($i=1; $i<13; $i++){
                if($i<10){
                    $i = '0'.$i;
                }
                
                if($energia_ear['ear'.$i]!=$energia['ear'.$i]){
                    $energia['ear'.$i] = $energia_ear['ear'.$i];
                    $volt_valtozas     = true;
                }
                
                $mennyiseg_osszesen += $energia['em'.$i];
                $ertek_osszesen     += $energia['ft'.$i];
                
                $energia['em'.$i]  = number_format($energia['em'.$i],  2, ',', ' ');
                $energia['ear'.$i] = number_format($energia['ear'.$i], 3, ',', ' ');
                $energia['ft'.$i]  = number_format($energia['ft'.$i],  0, '',  ' ');
            }
            $mennyiseg_osszesen = number_format($mennyiseg_osszesen,  0, ',', ' ');
            $ertek_osszesen     = number_format($ertek_osszesen,  0, ',', ' ');
            
            $class = '';
            if( !in_array($energia['sorid'], $eear_soridk) ){
                $class          = ' piros_hat';
                $volt_valtozas  = true;
            }
            
            $szamla_html .= '
                <tr id="ft_em_'.$energia['recid'].'" data-recid="'.$energia['recid'].'" class="ftt_menny'.$class.'">
                    <td>'.$energia['ktgnem_id'].'</td>
                    <td class="wrap">'.getErtek('nev', 'ktgnem', 'id', $energia['ktgnem_id']).'</td>
                    <td class="wrap">'.$energia['dijelem'].'</td>
                    <td>'.$energia['mee'].'</td>
                    <td class="r mennyossz">'.$mennyiseg_osszesen.'</td>
                    <td class="r ertekossz">'.$ertek_osszesen.'</td>
                    <td class="r">'.$energia['ear01'].'</td>
                    <td class="r">'.$energia['em01'].'</td>
                    <td class="r ft01">'.$energia['ft01'].'</td>
                    <td class="r">'.$energia['ear02'].'</td>
                    <td class="r">'.$energia['em02'].'</td>
                    <td class="r ft02">'.$energia['ft02'].'</td>
                    <td class="r">'.$energia['ear03'].'</td>
                    <td class="r">'.$energia['em03'].'</td>
                    <td class="r ft03">'.$energia['ft03'].'</td>
                    <td class="r">'.$energia['ear04'].'</td>
                    <td class="r">'.$energia['em04'].'</td>
                    <td class="r ft04">'.$energia['ft04'].'</td>
                    <td class="r">'.$energia['ear05'].'</td>
                    <td class="r">'.$energia['em05'].'</td>
                    <td class="r ft05">'.$energia['ft05'].'</td>
                    <td class="r">'.$energia['ear06'].'</td>
                    <td class="r">'.$energia['em06'].'</td>
                    <td class="r ft06">'.$energia['ft06'].'</td>
                    <td class="r">'.$energia['ear07'].'</td>
                    <td class="r">'.$energia['em07'].'</td>
                    <td class="r ft07">'.$energia['ft07'].'</td>
                    <td class="r">'.$energia['ear08'].'</td>
                    <td class="r">'.$energia['em08'].'</td>
                    <td class="r ft08">'.$energia['ft08'].'</td>
                    <td class="r">'.$energia['ear09'].'</td>
                    <td class="r">'.$energia['em09'].'</td>
                    <td class="r ft09">'.$energia['ft09'].'</td>
                    <td class="r">'.$energia['ear10'].'</td>
                    <td class="r">'.$energia['em10'].'</td>
                    <td class="r ft10">'.$energia['ft10'].'</td>
                    <td class="r">'.$energia['ear11'].'</td>
                    <td class="r">'.$energia['em11'].'</td>
                    <td class="r ft11">'.$energia['ft11'].'</td>
                    <td class="r">'.$energia['ear12'].'</td>
                    <td class="r">'.$energia['em12'].'</td>
                    <td class="r ft12">'.$energia['ft12'].'</td>
                </tr>
                ';
        }
        
        // Új sorok kiszámítása, kirajzolása
        foreach ($eear_soridk as $sorid){
            if(!in_array($sorid, $energia_soridk)){
                $volt_valtozas  = true;
                
                $energia = $adatbazis_kapcsolat->s2('*', 'energia_terv_ear', "`energia_terv_id`='{$energia_terv['recid']}' AND `sorid`='{$sorid}'");
                
                $mennyiseg_osszesen = 0;
                $ertek_osszesen     = 0;
                for($i=1; $i<13; $i++){
                    if($i<10){
                        $i = '0'.$i;
                    }

                    $mennyiseg_osszesen += $energia['em'.$i];
                    $ertek_osszesen     += $energia['ft'.$i];
                }
                $mennyiseg_osszesen = number_format($mennyiseg_osszesen,  0, ',', ' ');
                $ertek_osszesen     = number_format($ertek_osszesen,  0, ',', ' ');
                
                $szamla_html .= '
                    <tr id="ft_em_'.$sorid.'" data-recid="'.$sorid.'" class="ftt_menny zold_hat">
                        <td>'.$energia['ktgnem_id'].'</td>
                        <td>'.getErtek('nev', 'ktgnem', 'id', $energia['ktgnem_id']).'</td>
                        <td>'.$energia['dijelem'].'</td>
                        <td>'.$energia['mee'].'</td>
                        <td class="r">'.$mennyiseg_osszesen.'</td>
                        <td class="r ertekossz">'.$ertek_osszesen.'</td>
                        <td class="r">'.$energia['ear01'].'</td>
                        <td class="r">'.$energia['em01'].'</td>
                        <td class="r ft01">'.$energia['ft01'].'</td>
                        <td class="r">'.$energia['ear02'].'</td>
                        <td class="r">'.$energia['em02'].'</td>
                        <td class="r ft02">'.$energia['ft02'].'</td>
                        <td class="r">'.$energia['ear03'].'</td>
                        <td class="r">'.$energia['em03'].'</td>
                        <td class="r ft03">'.$energia['ft03'].'</td>
                        <td class="r">'.$energia['ear04'].'</td>
                        <td class="r">'.$energia['em04'].'</td>
                        <td class="r ft04">'.$energia['ft04'].'</td>
                        <td class="r">'.$energia['ear05'].'</td>
                        <td class="r">'.$energia['em05'].'</td>
                        <td class="r ft05">'.$energia['ft05'].'</td>
                        <td class="r">'.$energia['ear06'].'</td>
                        <td class="r">'.$energia['em06'].'</td>
                        <td class="r ft06">'.$energia['ft06'].'</td>
                        <td class="r">'.$energia['ear07'].'</td>
                        <td class="r">'.$energia['em07'].'</td>
                        <td class="r ft07">'.$energia['ft07'].'</td>
                        <td class="r">'.$energia['ear08'].'</td>
                        <td class="r">'.$energia['em08'].'</td>
                        <td class="r ft08">'.$energia['ft08'].'</td>
                        <td class="r">'.$energia['ear09'].'</td>
                        <td class="r">'.$energia['em09'].'</td>
                        <td class="r ft09">'.$energia['ft09'].'</td>
                        <td class="r">'.$energia['ear10'].'</td>
                        <td class="r">'.$energia['em10'].'</td>
                        <td class="r ft10">'.$energia['ft10'].'</td>
                        <td class="r">'.$energia['ear11'].'</td>
                        <td class="r">'.$energia['em11'].'</td>
                        <td class="r ft11">'.$energia['ft11'].'</td>
                        <td class="r">'.$energia['ear12'].'</td>
                        <td class="r">'.$energia['em12'].'</td>
                        <td class="r ft12">'.$energia['ft12'].'</td>
                    </tr>
                    ';
            }
        }

        $szamla_html .= nl2('
                            </tbody>
                            <tfoot class="nowrap">
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="r"></td>
                                <td class="r" id="szum_ertek_o"></td>
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
        
        // Ha van eltérés az energia egységárahoz képest, akkor egy plusz gombot kiíruratunk
        if($volt_valtozas && $_SESSION[get_db_tabla().'szemelyzet_id']==MUSZAKI_VEZETO && $sor['stat_id']==ENERGIATERV_STAT_MUNKAKOZI && _jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL)){
            $szamla_html .= nl2('
                        <script>
                        $(document).ready(function () {
                            $(\'#valtoztatas_jovahagyas\').show();
                            alert2(\'Az energia egységár tábla megváltozott.<br>Kérem ellenőrizze az energia tervtábla adatait!\', \'\', \'Üzenet\');
                        });
                        </script>
                ');
        }
        // A kitöltés gomb csak akkor látsszon, ha nem volt változás
        if(!$volt_valtozas){
            $szamla_html .= nl2('
                        <script>
                        $(document).ready(function () {
                            $(\'#energia_kitolt\').show();
                         });
                        </script>
                ');
        }

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
        
	// Műszaki anyag
	}elseif(isset($_GET['muszaki_anyag'])){

        $szamla_html .= '
            <table class="szamla_cont_tabla nolink fejlec_padd szep_csikos kisbetu">
            <thead>
            <tr>
                <th class="ktgnem_kod"><span>Ktgnem kód</span></th>
                <th style="min-width: 10rem; width: 10rem"><span>Ktgnem név</span></th>
                <th><span>Összesen</span></th>
                <th><span>Január</span></th>
                <th><span>Február</span></th>
                <th><span>Március</span></th>
                <th><span>Április</span></th>
                <th><span>Május</span></th>
                <th><span>Június</span></th>
                <th><span>Július</span></th>
                <th><span>Augusztus</span></th>
                <th><span>Szeptember</span></th>
                <th><span>Október</span></th>
                <th><span>November</span></th>
                <th><span>December</span></th>
            </tr>
            </thead>
            <tbody>
            ';

        $muszaki_anyagok = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_muszaki_anyag', "`fenntartasi_terv_id`='{$_GET['id']}'");
        foreach($muszaki_anyagok as $anyagok){
            
            $osszesen = 0;
            for($i=1; $i<13; $i++){
                if($i<10){
                    $i = '0'.$i;
                }
                $osszesen += $anyagok['ft'.$i];
                
                $anyagok['ft'.$i] = number_format($anyagok['ft'.$i], 0, '', ' ');
            }
            $osszesen = number_format($osszesen, 0, '', ' ');
            
            $szamla_html .= '
                <tr id="ft_ft_'.$anyagok['recid'].'" data-recid="'.$anyagok['recid'].'" class="ftt_menny">
                    <td>'.$anyagok['ktgnem_id'].'</td>
                    <td class="wrap">'.getErtek('nev', 'ktgnem', 'id', $anyagok['ktgnem_id']).'</td>
                    <td class="r ftossz">'.$osszesen.'</td>
                    <td class="r ft01">'.$anyagok['ft01'].'</td>
                    <td class="r ft02">'.$anyagok['ft02'].'</td>
                    <td class="r ft03">'.$anyagok['ft03'].'</td>
                    <td class="r ft04">'.$anyagok['ft04'].'</td>
                    <td class="r ft05">'.$anyagok['ft05'].'</td>
                    <td class="r ft06">'.$anyagok['ft06'].'</td>
                    <td class="r ft07">'.$anyagok['ft07'].'</td>
                    <td class="r ft08">'.$anyagok['ft08'].'</td>
                    <td class="r ft09">'.$anyagok['ft09'].'</td>
                    <td class="r ft10">'.$anyagok['ft10'].'</td>
                    <td class="r ft11">'.$anyagok['ft11'].'</td>
                    <td class="r ft12">'.$anyagok['ft12'].'</td>
                </tr>
                ';
        }

        $szamla_html .= nl2('
                            </tbody>
                            <tfoot class="nowrap">
                                <td></td>
                                <td></td>
                                <td class="r" id="szum_ftossz"></td>
                                <td class="r" id="szum_ft01"></td>
                                <td class="r" id="szum_ft02"></td>
                                <td class="r" id="szum_ft03"></td>
                                <td class="r" id="szum_ft04"></td>
                                <td class="r" id="szum_ft05"></td>
                                <td class="r" id="szum_ft06"></td>
                                <td class="r" id="szum_ft07"></td>
                                <td class="r" id="szum_ft08"></td>
                                <td class="r" id="szum_ft09"></td>
                                <td class="r" id="szum_ft10"></td>
                                <td class="r" id="szum_ft11"></td>
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
        if( isset($_GET['ft'])){
            $szamla_html .= nl2('
                            <script>
                                $(\'#ft_ft_'.$_GET['ft'].'\').addClass(\'kijelolve\');
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
        
	// Rendszeres karbantartás
	}elseif(isset($_GET['rendsz_karban'])){

        $szamla_html .= '
            <table class="szamla_cont_tabla nolink fejlec_padd szep_csikos kisbetu">
            <thead>
            <tr>
                <th class="ktgnem_kod"><span>Ktgnem kód</span></th>
                <th style="min-width: 10rem; width: 10rem"><span>Ktgnem név</span></th>
                <th style="min-width: 20rem; width: 20rem"><span>Tárgy</span></th>
                <th><span>Összesen</span></th>
                <th><span>Január</span></th>
                <th><span>Február</span></th>
                <th><span>Március</span></th>
                <th><span>Április</span></th>
                <th><span>Május</span></th>
                <th><span>Június</span></th>
                <th><span>Július</span></th>
                <th><span>Augusztus</span></th>
                <th><span>Szeptember</span></th>
                <th><span>Október</span></th>
                <th><span>November</span></th>
                <th><span>December</span></th>
            </tr>
            </thead>
            <tbody>
            ';

        $rendszeresek = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_rendsz_karban', "`fenntartasi_terv_id`='{$_GET['id']}'");
        foreach($rendszeresek as $rendszeres){
            
            $osszesen = 0;
            for($i=1; $i<13; $i++){
                if($i<10){
                    $i = '0'.$i;
                }
                $osszesen += $rendszeres['ft'.$i];
                
                $rendszeres['ft'.$i] = number_format($rendszeres['ft'.$i], 0, '', ' ');
            }
            $osszesen = number_format($osszesen, 0, '', ' ');
            
            $szamla_html .= '
                <tr id="ft_ft_'.$rendszeres['recid'].'" data-recid="'.$rendszeres['recid'].'" class="ftt_menny">
                    <td>'.$rendszeres['ktgnem_id'].'</td>
                    <td class="wrap">'.getErtek('nev', 'ktgnem', 'id', $rendszeres['ktgnem_id']).'</td>
                    <td class="wrap">'.$rendszeres['targy'].'</td>
                    <td class="r ftossz">'.$osszesen.'</td>
                    <td class="r ft01">'.$rendszeres['ft01'].'</td>
                    <td class="r ft02">'.$rendszeres['ft02'].'</td>
                    <td class="r ft03">'.$rendszeres['ft03'].'</td>
                    <td class="r ft04">'.$rendszeres['ft04'].'</td>
                    <td class="r ft05">'.$rendszeres['ft05'].'</td>
                    <td class="r ft06">'.$rendszeres['ft06'].'</td>
                    <td class="r ft07">'.$rendszeres['ft07'].'</td>
                    <td class="r ft08">'.$rendszeres['ft08'].'</td>
                    <td class="r ft09">'.$rendszeres['ft09'].'</td>
                    <td class="r ft10">'.$rendszeres['ft10'].'</td>
                    <td class="r ft11">'.$rendszeres['ft11'].'</td>
                    <td class="r ft12">'.$rendszeres['ft12'].'</td>
                </tr>
                ';
        }

        $szamla_html .= nl2('
                            </tbody>
                            <tfoot class="nowrap">
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="r" id="szum_ftossz"></td>
                                <td class="r" id="szum_ft01"></td>
                                <td class="r" id="szum_ft02"></td>
                                <td class="r" id="szum_ft03"></td>
                                <td class="r" id="szum_ft04"></td>
                                <td class="r" id="szum_ft05"></td>
                                <td class="r" id="szum_ft06"></td>
                                <td class="r" id="szum_ft07"></td>
                                <td class="r" id="szum_ft08"></td>
                                <td class="r" id="szum_ft09"></td>
                                <td class="r" id="szum_ft10"></td>
                                <td class="r" id="szum_ft11"></td>
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
        if( isset($_GET['ft'])){
            $szamla_html .= nl2('
                            <script>
                                $(\'#ft_ft_'.$_GET['ft'].'\').addClass(\'kijelolve\');
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
        
	// Eseti karbantartás
	}elseif(isset($_GET['eseti_karban'])){

        $szamla_html .= '
            <table class="szamla_cont_tabla nolink fejlec_padd szep_csikos kisbetu">
            <thead>
            <tr>
                <th class="ktgnem_kod"><span>Ktgnem kód</span></th>
                <th style="min-width: 10rem; width: 10rem"><span>Ktgnem név</span></th>
                <th style="min-width: 20rem; width: 20rem"><span>Tárgy</span></th>
                <th style="min-width: 20rem; width: 20rem"><span>Részletes leírás</span></th>
                <th><span>Összesen</span></th>
                <th><span>Január</span></th>
                <th><span>Február</span></th>
                <th><span>Március</span></th>
                <th><span>Április</span></th>
                <th><span>Május</span></th>
                <th><span>Június</span></th>
                <th><span>Július</span></th>
                <th><span>Augusztus</span></th>
                <th><span>Szeptember</span></th>
                <th><span>Október</span></th>
                <th><span>November</span></th>
                <th><span>December</span></th>
            </tr>
            </thead>
            <tbody>
            ';

        $esetik = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_eseti_karban', "`fenntartasi_terv_id`='{$_GET['id']}'");
        foreach($esetik as $eseti){
            
            $osszesen = '';
            for($i=1; $i<13; $i++){
                if($i<10){
                    $i = '0'.$i;
                }
                $osszesen += $eseti['ft'.$i];
                $eseti['ft'.$i] = number_format($eseti['ft'.$i], 0, '', ' ');
            }
            $osszesen = number_format($osszesen, 0, '', ' ');
            
            $szamla_html .= '
                <tr id="ft_ft_'.$eseti['recid'].'" data-recid="'.$eseti['recid'].'" class="ftt_menny">
                    <td>'.$eseti['ktgnem_id'].'</td>
                    <td class="wrap">'.getErtek('nev', 'ktgnem', 'id', $eseti['ktgnem_id']).'</td>
                    <td class="wrap">'.$eseti['targy'].'</td>
                    <td class="wrap">'.nl2br2($eseti['leir']).'</td>
                    <td class="r ftossz">'.$osszesen.'</td>
                    <td class="r ft01">'.$eseti['ft01'].'</td>
                    <td class="r ft02">'.$eseti['ft02'].'</td>
                    <td class="r ft03">'.$eseti['ft03'].'</td>
                    <td class="r ft04">'.$eseti['ft04'].'</td>
                    <td class="r ft05">'.$eseti['ft05'].'</td>
                    <td class="r ft06">'.$eseti['ft06'].'</td>
                    <td class="r ft07">'.$eseti['ft07'].'</td>
                    <td class="r ft08">'.$eseti['ft08'].'</td>
                    <td class="r ft09">'.$eseti['ft09'].'</td>
                    <td class="r ft10">'.$eseti['ft10'].'</td>
                    <td class="r ft11">'.$eseti['ft11'].'</td>
                    <td class="r ft12">'.$eseti['ft12'].'</td>
                </tr>
                ';
        }

        $szamla_html .= nl2('
                            </tbody>
                            <tfoot class="nowrap">
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="r" id="szum_ftossz"></td>
                                <td class="r" id="szum_ft01"></td>
                                <td class="r" id="szum_ft02"></td>
                                <td class="r" id="szum_ft03"></td>
                                <td class="r" id="szum_ft04"></td>
                                <td class="r" id="szum_ft05"></td>
                                <td class="r" id="szum_ft06"></td>
                                <td class="r" id="szum_ft07"></td>
                                <td class="r" id="szum_ft08"></td>
                                <td class="r" id="szum_ft09"></td>
                                <td class="r" id="szum_ft10"></td>
                                <td class="r" id="szum_ft11"></td>
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
        if( isset($_GET['ft'])){
            $szamla_html .= nl2('
                            <script>
                                $(\'#ft_ft_'.$_GET['ft'].'\').addClass(\'kijelolve\');
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
        
	// Hibaelhárítás
	}elseif(isset($_GET['hibaelharitas'])){

        $szamla_html .= '
            <table class="szamla_cont_tabla nolink fejlec_padd szep_csikos kisbetu">
            <thead>
            <tr>
                <th class="ktgnem_kod"><span>Ktgnem kód</span></th>
                <th style="min-width: 10rem; width: 10rem"><span>Ktgnem név</span></th>
                <th style="min-width: 20rem; width: 20rem"><span>Részletes leírás</span></th>
                <th><span>Összesen</span></th>
                <th><span>Január</span></th>
                <th><span>Február</span></th>
                <th><span>Március</span></th>
                <th><span>Április</span></th>
                <th><span>Május</span></th>
                <th><span>Június</span></th>
                <th><span>Július</span></th>
                <th><span>Augusztus</span></th>
                <th><span>Szeptember</span></th>
                <th><span>Október</span></th>
                <th><span>November</span></th>
                <th><span>December</span></th>
            </tr>
            </thead>
            <tbody>
            ';

        $hibak = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_hibaelharitas', "`fenntartasi_terv_id`='{$_GET['id']}'");
        foreach($hibak as $hiba){
            
            $osszesen = '';
            for($i=1; $i<13; $i++){
                if($i<10){
                    $i = '0'.$i;
                }
                $osszesen += $hiba['ft'.$i];
                $hiba['ft'.$i] = number_format($hiba['ft'.$i], 0, '', ' ');
            }
            $osszesen = number_format($osszesen, 0, '', ' ');
            
            $szamla_html .= '
                <tr id="ft_ft_'.$hiba['recid'].'" data-recid="'.$hiba['recid'].'" class="ftt_menny">
                    <td>'.$hiba['ktgnem_id'].'</td>
                    <td class="wrap">'.getErtek('nev', 'ktgnem', 'id', $hiba['ktgnem_id']).'</td>
                    <td class="wrap">'.nl2br2($hiba['leir']).'</td>
                    <td class="r ftossz">'.$osszesen.'</td>
                    <td class="r ft01">'.$hiba['ft01'].'</td>
                    <td class="r ft02">'.$hiba['ft02'].'</td>
                    <td class="r ft03">'.$hiba['ft03'].'</td>
                    <td class="r ft04">'.$hiba['ft04'].'</td>
                    <td class="r ft05">'.$hiba['ft05'].'</td>
                    <td class="r ft06">'.$hiba['ft06'].'</td>
                    <td class="r ft07">'.$hiba['ft07'].'</td>
                    <td class="r ft08">'.$hiba['ft08'].'</td>
                    <td class="r ft09">'.$hiba['ft09'].'</td>
                    <td class="r ft10">'.$hiba['ft10'].'</td>
                    <td class="r ft11">'.$hiba['ft11'].'</td>
                    <td class="r ft12">'.$hiba['ft12'].'</td>
                </tr>
                ';
        }

        $szamla_html .= nl2('
                            </tbody>
                            <tfoot class="nowrap">
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="r" id="szum_ftossz"></td>
                                <td class="r" id="szum_ft01"></td>
                                <td class="r" id="szum_ft02"></td>
                                <td class="r" id="szum_ft03"></td>
                                <td class="r" id="szum_ft04"></td>
                                <td class="r" id="szum_ft05"></td>
                                <td class="r" id="szum_ft06"></td>
                                <td class="r" id="szum_ft07"></td>
                                <td class="r" id="szum_ft08"></td>
                                <td class="r" id="szum_ft09"></td>
                                <td class="r" id="szum_ft10"></td>
                                <td class="r" id="szum_ft11"></td>
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
        if( isset($_GET['ft'])){
            $szamla_html .= nl2('
                            <script>
                                $(\'#ft_ft_'.$_GET['ft'].'\').addClass(\'kijelolve\');
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
        
	// BWH - csak ha Hungária (2102) a login szállodái között van
	}elseif(isset($_GET['bwh']) && in_array(2120, $sajat_szallodak) ){

        $szamla_html .= '
            <table class="szamla_cont_tabla nolink fejlec_padd szep_csikos kisbetu">
            <thead>
            <tr>
                <th class="ktgnem_kod"><span>Ktgnem kód</span></th>
                <th style="min-width: 10rem; width: 10rem"><span>Ktgnem név</span></th>
                <th style="min-width: 20rem; width: 20rem"><span>Tárgy</span></th>
                <th style="min-width: 20rem; width: 20rem"><span>Leírás</span></th>
                <th><span>Január</span></th>
                <th><span>Február</span></th>
                <th><span>Március</span></th>
                <th><span>Április</span></th>
                <th><span>Május</span></th>
                <th><span>Június</span></th>
                <th><span>Július</span></th>
                <th><span>Augusztus</span></th>
                <th><span>Szeptember</span></th>
                <th><span>Október</span></th>
                <th><span>November</span></th>
                <th><span>December</span></th>
            </tr>
            </thead>
            <tbody>
            ';

        $bwhk = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_bwh', "`fenntartasi_terv_id`='{$_GET['id']}'");
        foreach($bwhk as $bwh){
            
            for($i=1; $i<13; $i++){
                if($i<10){
                    $i = '0'.$i;
                }
                $bwh['ft'.$i] = number_format($bwh['ft'.$i], 0, '', ' ');
            }
            
            $szamla_html .= '
                <tr id="ft_ft_'.$bwh['recid'].'" data-recid="'.$bwh['recid'].'" class="ftt_menny">
                    <td>'.$bwh['ktgnem_id'].'</td>
                    <td class="wrap">'.getErtek('nev', 'ktgnem', 'id', $bwh['ktgnem_id']).'</td>
                    <td class="wrap">'.$bwh['targy'].'</td>
                    <td class="wrap">'.nl2br2($bwh['leir']).'</td>
                    <td class="r ft01">'.$bwh['ft01'].'</td>
                    <td class="r ft02">'.$bwh['ft02'].'</td>
                    <td class="r ft03">'.$bwh['ft03'].'</td>
                    <td class="r ft04">'.$bwh['ft04'].'</td>
                    <td class="r ft05">'.$bwh['ft05'].'</td>
                    <td class="r ft06">'.$bwh['ft06'].'</td>
                    <td class="r ft07">'.$bwh['ft07'].'</td>
                    <td class="r ft08">'.$bwh['ft08'].'</td>
                    <td class="r ft09">'.$bwh['ft09'].'</td>
                    <td class="r ft10">'.$bwh['ft10'].'</td>
                    <td class="r ft11">'.$bwh['ft11'].'</td>
                    <td class="r ft12">'.$bwh['ft12'].'</td>
                </tr>
                ';
        }

        $szamla_html .= nl2('
                            </tbody>
                            <tfoot class="nowrap">
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="r" id="szum_ft01"></td>
                                <td class="r" id="szum_ft02"></td>
                                <td class="r" id="szum_ft03"></td>
                                <td class="r" id="szum_ft04"></td>
                                <td class="r" id="szum_ft05"></td>
                                <td class="r" id="szum_ft06"></td>
                                <td class="r" id="szum_ft07"></td>
                                <td class="r" id="szum_ft08"></td>
                                <td class="r" id="szum_ft09"></td>
                                <td class="r" id="szum_ft10"></td>
                                <td class="r" id="szum_ft11"></td>
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
        if( isset($_GET['ft'])){
            $szamla_html .= nl2('
                            <script>
                                $(\'#ft_ft_'.$_GET['ft'].'\').addClass(\'kijelolve\');
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
        
	// Összesítő fül
	}elseif(isset($_GET['osszesen']) ){

        $szamla_html .= '
            <table class="szamla_cont_tabla nolink fejlec_padd szep_csikos kisbetu">
            <thead>
            <tr>
                <th class="ktgnem_kod"><span>Ktgnem kód</span></th>
                <th style="min-width: 10rem; width: 10rem;"><span>Ktgnem név</span></th>
                <th><span>Összesen</span></th>
                <th><span>Január</span></th>
                <th><span>Február</span></th>
                <th><span>Március</span></th>
                <th><span>Április</span></th>
                <th><span>Május</span></th>
                <th><span>Június</span></th>
                <th><span>Július</span></th>
                <th><span>Augusztus</span></th>
                <th><span>Szeptember</span></th>
                <th><span>Október</span></th>
                <th><span>November</span></th>
                <th><span>December</span></th>
            </tr>
            </thead>
            <tbody>
            ';
        
        $kivetelek = array('modosit', 'osszesen', 'tortenet', 'dokumentumok', 'megjegyzesek');
        // A BWH-t is felvesszük a kivételek közé, hacsak nem Hungáriás a user
        if( !in_array(2120, $sajat_szallodak) || $sor['telephely_id']!=2120 ){
            array_push($kivetelek, 'bwh');
        }
        foreach($ful_tomb as $tabla_post => $ful_neve){

            if( !in_array($tabla_post, $kivetelek) ){
            
                $ful_sorok = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_'.$tabla_post, "`fenntartasi_terv_id`='{$_GET['id']}'", '', '', 'ktgnem_id');
// echo '---'.$adatbazis_kapcsolat->gp() . '<br>';
                if($ful_sorok){
                    foreach($ful_sorok as $sor){

                        // Mivel költségnem id szerint group by-oztunk, ezért itt le kell kérni újra a sorokat, mert lehet több azonos költségnem rekord is van adott terv_id-hoz
                        // Első lépésként ezket ezért nullázzuk is
                        for($i=1; $i<13; $i++){
                            if($i<10){
                                $i = '0'.$i;
                            }
                            $sor['ft'.$i] = 0;
                        }
                        $akt_ful_sorok = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_'.$tabla_post, "`fenntartasi_terv_id`='{$_GET['id']}' AND `ktgnem_id`='{$sor['ktgnem_id']}'");
                        foreach($akt_ful_sorok as $akt_sor){
                            for($i=1; $i<13; $i++){
                                if($i<10){
                                    $i = '0'.$i;
                                }
                                $sor['ft'.$i] += $akt_sor['ft'.$i];
                            }
                        }
                        
                        $honapok_osszesen = 0;
                        for($i=1; $i<13; $i++){
                            if($i<10){
                                $i = '0'.$i;
                            }
                            $honapok_osszesen += $sor['ft'.$i];
                            $sor['ft'.$i] = number_format($sor['ft'.$i], 0, '', ' ');
                        }
                        $honapok_osszesen = number_format($honapok_osszesen, 0, '', ' ');

                        $szamla_html .= '
                            <tr id="ft_ft_'.$tabla_post.$sor['recid'].'" data-recid="'.$sor['recid'].'" class="ftt_menny">
                                <td>'.$sor['ktgnem_id'].'</td>
                                <td class="wrap">'.getErtek('nev', 'ktgnem', 'id', $sor['ktgnem_id']).'</td>
                                <td class="r fto '.$tabla_post.'">'.$honapok_osszesen.'</td>
                                <td class="r ft01 '.$tabla_post.'">'.$sor['ft01'].'</td>
                                <td class="r ft02 '.$tabla_post.'">'.$sor['ft02'].'</td>
                                <td class="r ft03 '.$tabla_post.'">'.$sor['ft03'].'</td>
                                <td class="r ft04 '.$tabla_post.'">'.$sor['ft04'].'</td>
                                <td class="r ft05 '.$tabla_post.'">'.$sor['ft05'].'</td>
                                <td class="r ft06 '.$tabla_post.'">'.$sor['ft06'].'</td>
                                <td class="r ft07 '.$tabla_post.'">'.$sor['ft07'].'</td>
                                <td class="r ft08 '.$tabla_post.'">'.$sor['ft08'].'</td>
                                <td class="r ft09 '.$tabla_post.'">'.$sor['ft09'].'</td>
                                <td class="r ft10 '.$tabla_post.'">'.$sor['ft10'].'</td>
                                <td class="r ft11 '.$tabla_post.'">'.$sor['ft11'].'</td>
                                <td class="r ft12 '.$tabla_post.'">'.$sor['ft12'].'</td>
                            </tr>
                            ';
                    }

                    $szamla_html .= '
                        <tr id="ft_ft_'.$tabla_post.$sor['recid'].'" data-recid="'.$sor['recid'].'" class="ftt_menny piros">
                            <td>
                                <script>
                                    $(document).ready(function () {
                                        energia_szummaz(\''.$tabla_post.'\');
                                    });
                                </script>
                            </td>
                            <td>'.$ful_neve.' összesen</td>
                            <td class="r" id="szum_fto_'.$tabla_post.'">Ö2</td>
                            <td class="r" id="szum_ft01_'.$tabla_post.'"></td>
                            <td class="r" id="szum_ft02_'.$tabla_post.'"></td>
                            <td class="r" id="szum_ft03_'.$tabla_post.'"></td>
                            <td class="r" id="szum_ft04_'.$tabla_post.'"></td>
                            <td class="r" id="szum_ft05_'.$tabla_post.'"></td>
                            <td class="r" id="szum_ft06_'.$tabla_post.'"></td>
                            <td class="r" id="szum_ft07_'.$tabla_post.'"></td>
                            <td class="r" id="szum_ft08_'.$tabla_post.'"></td>
                            <td class="r" id="szum_ft09_'.$tabla_post.'"></td>
                            <td class="r" id="szum_ft10_'.$tabla_post.'"></td>
                            <td class="r" id="szum_ft11_'.$tabla_post.'"></td>
                            <td class="r" id="szum_ft12_'.$tabla_post.'"></td>
                        </tr>
                        ';
                }
            }
        }

        $szamla_html .= nl2('
                            </tbody>
                            <tfoot class="nowrap">
                                <td></td>
                                <td></td>
                                <td class="r" id="szum_fto"></td>
                                <td class="r" id="szum_ft01"></td>
                                <td class="r" id="szum_ft02"></td>
                                <td class="r" id="szum_ft03"></td>
                                <td class="r" id="szum_ft04"></td>
                                <td class="r" id="szum_ft05"></td>
                                <td class="r" id="szum_ft06"></td>
                                <td class="r" id="szum_ft07"></td>
                                <td class="r" id="szum_ft08"></td>
                                <td class="r" id="szum_ft09"></td>
                                <td class="r" id="szum_ft10"></td>
                                <td class="r" id="szum_ft11"></td>
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
        if( isset($_GET['ft'])){
            $szamla_html .= nl2('
                            <script>
                                $(\'#ft_ft_'.$_GET['ft'].'\').addClass(\'kijelolve\');
                            </script>
                            ');
        }
//
//        // Ha nincs megjelölt sor, akkor az első legyen az
//        $szamla_html .= nl2('
//                        <script>
//                            if( !$(\'.szamla_cont_tabla .kijelolve\').length ){
//                                $(\'.szamla_cont_tabla tbody tr:first-of-type\').addClass(\'kijelolve\');
//                            }
//                        </script>
//                        ');

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

// earrecid beállítása az éles energia terv alapján
function earrecid_beallit($ev, $ft_recid){
    global $adatbazis_kapcsolat;
    
    $energia_terv = $adatbazis_kapcsolat->s2('recid', 'energia_terv', "`ev`='{$ev}' AND `stat_id`='".ENERGIATERV_STAT_ELES."'");
    if( $energia_terv === false ) { $db_error = true; }
    if($energia_terv){
        $sql = $adatbazis_kapcsolat->u('fenntartasi_terv', array('earrecid'), array($energia_terv['recid']), "`recid`='{$ft_recid}'");
        if( $sql === false ) { $db_error = true; }
    }
}