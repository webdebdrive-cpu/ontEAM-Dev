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
                array_push($ertekek, htmlspecialchars_decode($energia[$oszl]));
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
                array_push($ertekek, htmlspecialchars_decode($muszaki[$oszl]));
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
                array_push($ertekek, htmlspecialchars_decode($rendszeres[$oszl]));
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
                array_push($ertekek, htmlspecialchars_decode($eseti[$oszl]));
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
                array_push($ertekek, htmlspecialchars_decode($hibael[$oszl]));
            }
            array_push($oszlopok, 'fenntartasi_terv_id', 'update_dt', 'update_usr',                             'insert_dt',    'insert_usr'                            );
            array_push($ertekek,  $uj_id,                'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  );

            $sql = $adatbazis_kapcsolat->i('fenntartasi_terv_hibaelharitas', $oszlopok, $ertekek);
            if( $sql === false ) { $db_error = true; }
        }


        $ft_mini = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_miniprojekt', "`fenntartasi_terv_id`='{$recid}'");
        if( $ft_mini === false ) { $db_error = true; }
        foreach($ft_mini as $mini){
            $oszlopok   = array('ktgnem_id', 'targy', 'leir', 'ft01', 'ft02', 'ft03', 'ft04', 'ft05', 'ft06', 'ft07', 'ft08', 'ft09', 'ft10', 'ft11', 'ft12', 'sorid');
            $ertekek    = array();
            foreach($oszlopok as $oszl){
                array_push($ertekek, htmlspecialchars_decode($mini[$oszl]));
            }
            array_push($oszlopok, 'fenntartasi_terv_id', 'update_dt', 'update_usr',                             'insert_dt',    'insert_usr'                            );
            array_push($ertekek,  $uj_id,                'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  );

            $sql = $adatbazis_kapcsolat->i('fenntartasi_terv_miniprojekt', $oszlopok, $ertekek);
            if( $sql === false ) { $db_error = true; }
        }
        
        $ft_bwh = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_bwh', "`fenntartasi_terv_id`='{$recid}'");
        if( $ft_bwh === false ) { $db_error = true; }
        foreach($ft_bwh as $bwh){
            $oszlopok   = array('ktgnem_id', 'targy', 'leir', 'ft01', 'ft02', 'ft03', 'ft04', 'ft05', 'ft06', 'ft07', 'ft08', 'ft09', 'ft10', 'ft11', 'ft12', 'sorid');
            $ertekek    = array();
            foreach($oszlopok as $oszl){
                array_push($ertekek, htmlspecialchars_decode($bwh[$oszl]));
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
                    array_push($ertekek, htmlspecialchars_decode($ear[$oszl]));
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

        // Miniprojekt fül feltöltése
        $ktgnemek = $adatbazis_kapcsolat->s('id', 'ktgnem', "`props` LIKE '%7034%'");
        foreach($ktgnemek as $ktgnem){
            $sql = $adatbazis_kapcsolat->i('fenntartasi_terv_miniprojekt',
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
        $felirat = '';
        if($ft_energia['ktgnem_id']){
            $felirat = nl2('
                            <b>'.getErtek('nev', 'ktgnem', 'id', $ft_energia['ktgnem_id']).' kitöltése
                            <br>Tárgy: '.$ft_energia['dijelem'].'</b>
                            <br>
                            ');
        }
        if( $ft_energia ) {
            $html = nl2('
                '.$felirat.'
                <div class="dt egysegarak_tablazat" style="width: auto">
                    <div class="dtr">
                        <div class="dtc"></div>
                        <div class="dtc">Mennyiség</div>
                    </div>
            ');

            $osszesen = 0;
            foreach($honapok as $key => $honap) {
                $sorszam = sprintf('%02d', $key + 1);
                $osszesen += $ft_energia['em'.$sorszam];
                $html .= nl2('
                    <div class="dtr">
                        <div class="dtc" style="padding-right: 0.5rem;">'.$honap.'</div>
                        <div class="dtc"><input type="text" name="em'.$sorszam.'" id="em'.$sorszam.'" class="f_num popup_mennyisegek" data-tort="2" value="'.number_format($ft_energia['em'.$sorszam], 2, ',', ' ').'"> '.$ft_energia['mee'].'</div>
                    </div>
                ');
            }

            $html .= nl2('
                    <div class="dtr">
                        <div class="dtc b" style="padding-right: 0.5rem;">Összesen</div>
                        <div class="dtc r b"><span id="popup_tabla_osszesen_sor">'. number_format($osszesen, 0, ',', ' ').'</span> '.$ft_energia['mee'].'</div>
                    </div>
                </div>
                    ');
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
    if( !_jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL) ){
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
                    ');
            }

            $felirat = '';
            if($muszaki_anyag['ktgnem_id']){
                $felirat = '<b>'.getErtek('nev', 'ktgnem', 'id', $muszaki_anyag['ktgnem_id']).' kitöltése:</b><br>';
            }
            
            $html = nl2('
                '.$felirat.'
                <div class="dt egysegarak_tablazat" style="width: auto; margin-top: 1rem;">
                    '.$ktgnem_select.'
                    <div class="dtr">
                        <div class="dtc"></div>
                        <div class="dtc">Összeg</div>
                    </div>
            ');

            $osszesen = 0;
            foreach($honapok as $key => $honap) {
                $sorszam = sprintf('%02d', $key + 1);

                $osszesen += $muszaki_anyag['ft'.$sorszam];
                $html .= nl2('
                    <div class="dtr">
                        <div class="dtc" style="padding-right: 0.5rem;">'.$honap.'</div>
                        <div class="dtc"><input type="text" name="ft'.$sorszam.'" id="ft'.$sorszam.'" class="f_num popup_mennyisegek" data-tort="0" value="'.number_format($muszaki_anyag['ft'.$sorszam], 0, '', ' ').'"></div>
                    </div>
                ');
            }

            $html .= nl2('
                    <div class="dtr">
                        <div class="dtc b" style="padding-right: 0.5rem;">Összesen</div>
                        <div class="dtc r b"><span id="popup_tabla_osszesen_sor">'. number_format($osszesen, 0, ',', ' ').'</span> '.$ft_energia['mee'].'</div>
                    </div>
                </div>
                <script>
                $(document).on(\'input\', \'input.popup_mennyisegek\', function(e) {
                    popup_tabla_osszesit();
                });
                function popup_tabla_osszesit(){
                    var popup_osszesen = 0;
                    $( \'input.popup_mennyisegek\').each(function () {
                        ertek = $(this).val().replace(/ /g, \'\').replace(\',\', \'.\');
                        popup_osszesen += Number(ertek);
                    });
                    $(\'#popup_tabla_osszesen_sor\').html(ezresek(popup_osszesen, 0, \',\', \' \'));
                }
                </script>
                ');
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
            
            $felirat = '';
            if($rendszeres['ktgnem_id']){
                $felirat = '<b>'.getErtek('nev', 'ktgnem', 'id', $rendszeres['ktgnem_id']).' kitöltése:</b><br><br>';
            }
            
            // Új sor beszúrásakor költségnemet kell tudni választani
            $ktgnem_select = '';
            if($uj){
                $ktgnem_option  = $adatbazis_kapcsolat->s('id nev', 'ktgnem', "`props` LIKE '%7031%'");
                $ktgnem_option  = tomb2option($ktgnem_option, 'id', 'nev');
                $ktgnem_select  = nl2('
                    <div class="dtr">
                        <div class="dtc">Költségnem</div>
                        <div class="dtc">
                            <select name="ktgnem_id" id="ktgnem_id" class="csillag" style="max-width: 90%"> 
                                <option value="">-Válasszon-</option>
                                '.$ktgnem_option.'
                            </select>
                        </div>
                    </div>
                    ');
            }
            $html = nl2('
                '.$felirat.'
                <div class="dt egysegarak_tablazat" style="width: 100%">
                    '.$ktgnem_select.'
                    <div class="dtr">
                        <div class="dtc" style="width: 8.3rem">Tárgy</div> 
                        <div class="dtc"><input type="text" name="targy" id="targy" value="'.$rendszeres['targy'].'" maxlength="200" style="min-width: 90%; max-width: 90%"></div>
                    </div>
                </div>
                <div class="dt egysegarak_tablazat" style="width: auto; margin-top: 1rem">
                    <div class="dtr">
                        <div class="dtc"></div>
                        <div class="dtc">Összeg</div>
                    </div>
            ');

            $osszesen = 0;
            foreach($honapok as $key => $honap) {
                $sorszam = sprintf('%02d', $key + 1);
                $osszesen += $rendszeres['ft'.$sorszam];
                $html .= nl2('
                    <div class="dtr">
                        <div class="dtc" style="padding-right: 0.5rem;">'.$honap.'</div>
                        <div class="dtc"><input type="text" name="ft'.$sorszam.'" id="ft'.$sorszam.'" class="f_num popup_mennyisegek" data-tort="0" value="'.number_format($rendszeres['ft'.$sorszam], 0, '', ' ').'"></div>
                    </div>
                ');
            }

            $html .= nl2('
                    <div class="dtr">
                        <div class="dtc b" style="padding-right: 0.5rem;">Összesen</div>
                        <div class="dtc r b"><span id="popup_tabla_osszesen_sor">'. number_format($osszesen, 0, ',', ' ').'</span> '.$ft_energia['mee'].'</div>
                    </div>
                </div>
                        ');
        }

        echo json_encode(array(
            'statusz' => 'siker',
            'html'    => $html
        ));
    } else {
        echo json_encode(array(
            'hiba' => 'Rendszeres karbantartáshoz megadásához válasszon ki egy sort!'
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

            $felirat = '';
            if($eseti['ktgnem_id']){
                $felirat = '<b>'.getErtek('nev', 'ktgnem', 'id', $eseti['ktgnem_id']).' kitöltése:</b><br>';
            }
            
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
                    ');
            }
            $html = nl2('
                '.$felirat.'
                <div class="dt egysegarak_tablazat" style="width: auto">
                    '.$ktgnem_select.'
                    <div class="dtr">
                        <div class="dtc" style="min-width: 8.7rem">Tárgy</div>
                        <div class="dtc"><input type="text" name="targy" id="targy" value="'.$eseti['targy'].'" maxlength="200"></div>
                    </div>
                    <div class="dtr">
                        <div class="dtc">Leírás</div>
                        <div class="dtc"><textarea name="leir" id="leir" style="height: 4rem; min-height: 4rem;">').$eseti['leir'].nl2('</textarea></div>
                    </div>
                </div>
                <div class="dt egysegarak_tablazat" style="width: auto; margin-top: 1rem">
                    <div class="dtr">
                        <div class="dtc"></div>
                        <div class="dtc">Összeg</div>
                    </div>
            ');

            $osszesen = 0;
            foreach($honapok as $key => $honap) {
                $sorszam = sprintf('%02d', $key + 1);
                $osszesen += $eseti['ft'.$sorszam];
                $html .= nl2('
                    <div class="dtr">
                        <div class="dtc" style="padding-right: 0.5rem;">'.$honap.'</div>
                        <div class="dtc"><input type="text" name="ft'.$sorszam.'" id="ft'.$sorszam.'" class="f_num popup_mennyisegek" data-tort="0" value="'.number_format($eseti['ft'.$sorszam], 0, '', ' ').'"></div>
                    </div>
                ');
            }

            $html .= nl2('
                    <div class="dtr">
                        <div class="dtc b" style="padding-right: 0.5rem;">Összesen</div>
                        <div class="dtc r b"><span id="popup_tabla_osszesen_sor">'. number_format($osszesen, 0, ',', ' ').'</span> '.$ft_energia['mee'].'</div>
                    </div>
                </div>
                    ');
        }

        echo json_encode(array(
            'statusz' => 'siker',
            'html'    => $html
        ));
    } else {
        echo json_encode(array(
            'hiba' => 'Eseti fenntartás megadásához válasszon ki egy sort!'
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
        $muvelet  = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].') '.$_POST['uj'].' recid-jú fenntartási terv Eseti fenntartás fül alatt új sort töltött fel '.$_POST['ktgnem_id'].' költségnemmel.';
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
        $muvelet  = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].') '.$_POST['uj'].' recid-jú fenntartási terv Eseti fenntartás fül alatt a '.$_POST['ktgnem_id'].' költségnemű sort módosította.';
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

            $felirat = '';
            if($hiba['ktgnem_id']){
                $felirat = '<b>'.getErtek('nev', 'ktgnem', 'id', $hiba['ktgnem_id']).' kitöltése:</b><br><br>';
            }
            
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
                    ');
            }
            $html = nl2('
                '.$felirat.'
                <div class="dt egysegarak_tablazat"style="width: auto;">
                    '.$ktgnem_select.'
                    <div class="dtr">
                        <div class="dtc" style="min-width: 8.7rem">Leírás</div>
                        <div class="dtc"><textarea name="leir" id="leir" style="height: 4rem; min-height: 4rem; min-width: 30rem;">').$hiba['leir'].nl2('</textarea></div>
                    </div>
                </div>
                <div class="dt egysegarak_tablazat" style="width: auto; margin-top: 1rem;">
                    <div class="dtr">
                        <div class="dtc"></div>
                        <div class="dtc">Összeg</div>
                    </div>
            ');

            $osszesen = 0;
            foreach($honapok as $key => $honap) {
                $sorszam = sprintf('%02d', $key + 1);
                $osszesen += $hiba['ft'.$sorszam];
                $html .= nl2('
                    <div class="dtr">
                        <div class="dtc" style="padding-right: 0.5rem;">'.$honap.'</div>
                        <div class="dtc"><input type="text" name="ft'.$sorszam.'" id="ft'.$sorszam.'" class="f_num popup_mennyisegek" data-tort="0" value="'.number_format($hiba['ft'.$sorszam], 0, '', ' ').'"></div>
                    </div>
                ');
            }

            $html .= nl2('
                    <div class="dtr">
                        <div class="dtc b" style="padding-right: 0.5rem;">Összesen</div>
                        <div class="dtc r b"><span id="popup_tabla_osszesen_sor">'. number_format($osszesen, 0, ',', ' ').'</span></div>
                    </div>
                </div>
                    ');
        }

        echo json_encode(array(
            'statusz' => 'siker',
            'html'    => $html
        ));
    } else {
        echo json_encode(array(
            'hiba' => 'Hibaelhárítás megadásához válasszon ki egy sort!'
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

// Miniprojekt táblázat lekérdezése
if (isset($_POST['get_mini_tabla'])) {
    $id = $_POST['id'];
    $uj = $_POST['uj'];
    if($uj){
        $id = 0;
    }

    if( $id || $uj ) {
        $html = '';
        $honapok = array('Január', 'Február', 'Március', 'Április', 'Május', 'Június', 'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December');

        // Sor lekérdezése
        $mini = $adatbazis_kapcsolat->s2('*', 'fenntartasi_terv_miniprojekt', "`recid`='{$id}'");
        if( $mini || $uj ) {

            $felirat = '';
            if($mini['ktgnem_id']){
                $felirat = '<b>'.getErtek('nev', 'ktgnem', 'id', $mini['ktgnem_id']).' kitöltése:</b><br>';
            }
            
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
                    ');
            }
            $html = nl2('
                '.$felirat.'
                <div class="dt egysegarak_tablazat" style="width: auto">
                    '.$ktgnem_select.'
                    <div class="dtr">
                        <div class="dtc" style="min-width: 8.7rem">Tárgy</div>
                        <div class="dtc"><input type="text" name="targy" id="targy" value="'.$mini['targy'].'" maxlength="200"></div>
                    </div>
                    <div class="dtr">
                        <div class="dtc">Leírás</div>
                        <div class="dtc"><textarea name="leir" id="leir" style="height: 4rem; min-height: 4rem;">').$mini['leir'].nl2('</textarea></div>
                    </div>
                </div>
                <div class="dt egysegarak_tablazat" style="width: auto; margin-top: 1rem">
                    <div class="dtr">
                        <div class="dtc"></div>
                        <div class="dtc">Összeg</div>
                    </div>
            ');

            $osszesen = 0;
            foreach($honapok as $key => $honap) {
                $sorszam = sprintf('%02d', $key + 1);
                $osszesen += $mini['ft'.$sorszam];
                $html .= nl2('
                    <div class="dtr">
                        <div class="dtc" style="padding-right: 0.5rem;">'.$honap.'</div>
                        <div class="dtc"><input type="text" name="ft'.$sorszam.'" id="ft'.$sorszam.'" class="f_num popup_mennyisegek" data-tort="0" value="'.number_format($mini['ft'.$sorszam], 0, '', ' ').'"></div>
                    </div>
                ');
            }

            $html .= nl2('
                    <div class="dtr">
                        <div class="dtc b" style="padding-right: 0.5rem;">Összesen</div>
                        <div class="dtc r b"><span id="popup_tabla_osszesen_sor">'. number_format($osszesen, 0, ',', ' ').'</span> '.$ft_energia['mee'].'</div>
                    </div>
                </div>
                    ');
        }

        echo json_encode(array(
            'statusz' => 'siker',
            'html'    => $html
        ));
    } else {
        echo json_encode(array(
            'hiba' => 'Miniprojekt megadásához válasszon ki egy sort!'
        ));
    }

    $weblap->leallit();
    exit();
}

// Miniprojekt tábla mentése
if (isset($_POST['mini_tabla_ment'])) {
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
        $sor_id = $adatbazis_kapcsolat -> i('fenntartasi_terv_miniprojekt', $mit, $mire);
        if( $sor_id === false ) { $db_error = true; }

        // Logolás
        $muvelet  = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].') '.$_POST['uj'].' recid-jú fenntartási terv Miniprojekt fül alatt új sort töltött fel '.$_POST['ktgnem_id'].' költségnemmel.';
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
        $sql = $adatbazis_kapcsolat -> u('fenntartasi_terv_miniprojekt', $mit, $mire, "`recid`='{$_POST['egysegarsor_id']}'");
        if( $sql === false ) { $db_error = true; }
        $sor_id = $_POST['egysegarsor_id'];

        // Logolás
        $muvelet  = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].') '.$_POST['uj'].' recid-jú fenntartási terv Miniprojekt fül alatt a '.$_POST['ktgnem_id'].' költségnemű sort módosította.';
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
            
            $felirat = '';
            if($bwh['ktgnem_id']){
                $felirat = '<b>'.getErtek('nev', 'ktgnem', 'id', $bwh['ktgnem_id']).' kitöltése:</b><br>';
            }

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
                    ');
            }
            $html = nl2('
                '.$felirat.'
                <div class="dt egysegarak_tablazat" style="margin-top: 1rem">
                    '.$ktgnem_select.'
                    <div class="dtr">
                        <div class="dtc">Tárgy</div>
                        <div class="dtc"><input type="text" name="targy" id="targy" value="'.$bwh['targy'].'" maxlength="200"></div>
                    </div>
                    <div class="dtr">
                        <div class="dtc">Leírás</div>
                        <div class="dtc"><textarea name="leir" id="leir">').$bwh['leir'].nl2('</textarea></div>
                    </div>
                </div>
                <div class="dt egysegarak_tablazat" style="width: auto; margin-top: 1rem;">
                    <div class="dtr">
                        <div class="dtc"></div>
                        <div class="dtc">Összeg</div>
                    </div>
            ');

            $osszesen = 0;
            foreach($honapok as $key => $honap) {
                $sorszam = sprintf('%02d', $key + 1);
                $osszesen += $bwh['ft'.$sorszam];
                $html .= nl2('
                    <div class="dtr">
                        <div class="dtc" style="padding-right: 0.5rem;">'.$honap.'</div>
                        <div class="dtc"><input type="text" name="ft'.$sorszam.'" id="ft'.$sorszam.'" class="f_num popup_mennyisegek" data-tort="0" value="'.number_format($bwh['ft'.$sorszam], 0, '', ' ').'"></div>
                    </div>
                ');
            }

            $html .= nl2('
                    <div class="dtr">
                        <div class="dtc b" style="padding-right: 0.5rem;">Összesen</div>
                        <div class="dtc r b"><span id="popup_tabla_osszesen_sor">'. number_format($osszesen, 0, ',', ' ').'</span></div>
                    </div>
                </div>
                    ');
        }

        echo json_encode(array(
            'statusz' => 'siker',
            'html'    => $html
        ));
    } else {
        echo json_encode(array(
            'hiba' => 'BWH megadásához válasszon ki egy sort!'
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

// Fenntartási terv véglegesítése. Először azt vizsgáljuk, hogy jóvá lett-e minden hagyva.
if (isset($_POST['fenntartasterv_veglegesit_jovahagyva_ell'])) {

    $recid = $_POST['recid'];

    $sor = $adatbazis_kapcsolat->s2('ikt_szam stat_id telephely_id ev', 'fenntartasi_terv', "`recid`='{$recid}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs még jóváhagyva változtatás, akkor hiba
    $energia_terv   = $adatbazis_kapcsolat->s2('recid', 'energia_terv', "`ev`='{$sor['ev']}' AND `stat_id`='".ENERGIATERV_STAT_ELES."'");
    $akt_eear_sorok = $adatbazis_kapcsolat->s('sorid', 'energia_terv_ear', "`energia_terv_id`='{$energia_terv['recid']}' AND `telephely_idk` LIKE '%{$sor['telephely_id']}%'");
    $eear_soridk    = adatbazis2tomb($akt_eear_sorok, 'sorid');

    $volt_valtozas  = false;
    $energia_soridk = array();
    $energiak = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_energia', "`fenntartasi_terv_id`='{$recid}'");
    if( $energiak === false ) { $db_error = true; }
    foreach($energiak as $energia){

        // Innen tujduk majd, hogy mik az új sorok
        array_push($energia_soridk, $energia['sorid']);

        // Változások figyeléséhez lekérjük az energia terv sorait
        $energia_ear = $adatbazis_kapcsolat->s2('*', 'energia_terv_ear', "`energia_terv_id`='{$energia_terv['recid']}' AND `sorid`='{$energia['sorid']}'");
        if( $energia_ear === false ) { $db_error = true; }

        $valtozott_oszlop = array();
        $valtozott_ertek  = array();
        for($i=1; $i<13; $i++){
            if($i<10){
                $i = '0'.$i;
            }
            if($energia_ear['ear'.$i]!=$energia['ear'.$i]){
                $volt_valtozas = true;
            }
        }

        // Megszűnt sor van-e
        if( !in_array($energia['sorid'], $eear_soridk) ){
            $volt_valtozas = true;
        }
    }

    // Új sorok van-e
    foreach ($eear_soridk as $e_sorid){
        if(!in_array($e_sorid, $energia_soridk)){
            $volt_valtozas = true;
        }
    }
    if($volt_valtozas){
        echo json_encode(array(
            'hiba'  => 'Az energia egységárak változásának jóváhagyása nem történt meg!',
            'title' => 'Hiba'
        ));
    }else{
        echo json_encode(array(
            'rendben_van'  => '1'
        ));
    }

    $weblap->leallit();
    exit();
}
if (isset($_POST['fenntartasterv_veglegesit'])) {

    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat->s2('ikt_szam stat_id telephely_id ev', 'fenntartasi_terv', "`recid`='{$recid}'");
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

    // Ha nincs még jóváhagyva változtatás, akkor hiba
    $energia_terv   = $adatbazis_kapcsolat->s2('recid', 'energia_terv', "`ev`='{$sor['ev']}' AND `stat_id`='".ENERGIATERV_STAT_ELES."'");
    $akt_eear_sorok = $adatbazis_kapcsolat->s('sorid', 'energia_terv_ear', "`energia_terv_id`='{$energia_terv['recid']}' AND `telephely_idk` LIKE '%{$sor['telephely_id']}%'");
    $eear_soridk    = adatbazis2tomb($akt_eear_sorok, 'sorid');

    $volt_valtozas  = false;
    $energia_soridk = array();
    $energiak = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_energia', "`fenntartasi_terv_id`='{$recid}'");
    if( $energiak === false ) { $db_error = true; }
    foreach($energiak as $energia){

        // Innen tujduk majd, hogy mik az új sorok
        array_push($energia_soridk, $energia['sorid']);

        // Változások figyeléséhez lekérjük az energia terv sorait
        $energia_ear = $adatbazis_kapcsolat->s2('*', 'energia_terv_ear', "`energia_terv_id`='{$energia_terv['recid']}' AND `sorid`='{$energia['sorid']}'");
        if( $energia_ear === false ) { $db_error = true; }

        $valtozott_oszlop = array();
        $valtozott_ertek  = array();
        for($i=1; $i<13; $i++){
            if($i<10){
                $i = '0'.$i;
            }
            if($energia_ear['ear'.$i]!=$energia['ear'.$i]){
                $volt_valtozas = true;
            }
        }

        // Megszűnt sor van-e
        if( !in_array($energia['sorid'], $eear_soridk) ){
            $volt_valtozas = true;
        }
    }

    // Új sorok van-e
    foreach ($eear_soridk as $e_sorid){
        if(!in_array($e_sorid, $energia_soridk)){
            $volt_valtozas = true;
        }
    }
    if($volt_valtozas){
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => 'Az energia egységárak változásának jóváhagyása nem történt meg!',
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
    if(!_jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL)){
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

    $csv_cim = '';
    if (isset($_GET['energia'])) {
        $megnevezes .= '_energia';
        $csv_cim     = 'Energia';
    }
    if (isset($_GET['muszaki_anyag'])) {
        $megnevezes .= '_muszaki_anyag';
        $csv_cim     = 'Műszaki anyag';
    }
    if (isset($_GET['rendsz_karban'])) {
        $megnevezes .= '_rendszeres_karbantartas';
        $csv_cim     = 'Rendszeres karbantartás';
    }
    if (isset($_GET['eseti_karban'])) {
        $megnevezes .= '_eseti_fenntartas';
        $csv_cim     = 'Eseti fenntartás';
    }
    if (isset($_GET['hibaelharitas'])) {
        $megnevezes .= '_hibaelharitas';
        $csv_cim     = 'Hibaelhárítás';
    }
    if (isset($_GET['miniprojekt'])) {
        $megnevezes .= '_miniprojekt';
        $csv_cim     = 'Miniprojekt';
    }
    if (isset($_GET['bwh'])) {
        $megnevezes .= '_bwh';
        $csv_cim     = 'Cukrászat BWH';
    }
    if (isset($_GET['osszesen'])) {
        $megnevezes .= '_osszesen';
        $csv_cim     = 'Összesen';
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
    fwrite($open, "sep=;\n");
    ob_start();

    if($open){

        $lekerdezes_fromtol = substr($_SESSION['akt_sql'], strpos($_SESSION['akt_sql'], 'FROM'));
        
        // A fenntartási terv sorára is szükség lesz
        $fterv_sor = $adatbazis_kapcsolat->s2('stat_id ev telephely_id ikt_szam', 'fenntartasi_terv', "`recid`='{$_GET['id']}'");

        if( $_SESSION['nyomtatas_ids'] ) {

            // Energia egységár exportja
            if (isset($_GET['energia'])) {

                $sql = 'SELECT
                            `ktgnem_id` as \'Költségnem kód\',
                            `ktgnem_id` as \'Költségnem név\',
                            `dijelem` as \'Díjelem\',
                            `mee` as \'Mee\',
                            `ear01` as \'Egységár Január\',
                            `em01` as \'Mennyiség Január\',
                            `ft01` as \'Érték Január\',
                            `ear02` as \'Egységár Február\',
                            `em02` as \'Mennyiség Február\',
                            `ft02` as \'Érték Február\',
                            `ear03` as \'Egységár Március\',
                            `em03` as \'Mennyiség Március\',
                            `ft03` as \'Érték Március\',
                            `ear04` as \'Egységár Április\',
                            `em04` as \'Mennyiség Április\',
                            `ft04` as \'Érték Április\',
                            `ear05` as \'Egységár Május\',
                            `em05` as \'Mennyiség Május\',
                            `ft05` as \'Érték Május\',
                            `ear06` as \'Egységár Június\',
                            `em06` as \'Mennyiség Június\',
                            `ft06` as \'Érték Június\',
                            `ear07` as \'Egységár Július\',
                            `em07` as \'Mennyiség Július\',
                            `ft07` as \'Érték Július\',
                            `ear08` as \'Egységár Augusztus\',
                            `em08` as \'Mennyiség Augusztus\',
                            `ft08` as \'Érték Augusztus\',
                            `ear09` as \'Egységár Szeptember\',
                            `em09` as \'Mennyiség Szeptember\',
                            `ft09` as \'Érték Szeptember\',
                            `ear10` as \'Egységár Október\',
                            `em10` as \'Mennyiség Október\',
                            `ft10` as \'Érték Október\',
                            `ear11` as \'Egységár November\',
                            `em11` as \'Mennyiség November\',
                            `ft11` as \'Érték November\',
                            `ear12` as \'Egységár December\',
                            `em12` as \'Mennyiség December\',
                            `ft12` as \'Érték December\'

                            FROM `'.get_db_tabla().'fenntartasi_terv_energia` WHERE `fenntartasi_terv_id`=\''.$_GET['id'].'\'
                            ';
                $query      = mysqli_query($db_kapcs, $sql);

                // CSV első két sora a fül neve + szálloda, évszám, státusz
                $csvhead    = array();
                $csvhead[] = iconv2($mibol, $mibe, $csv_cim);
                fputcsv($open, $csvhead, ';');
                $csvhead    = array();
                $csvhead[] = iconv2($mibol, $mibe, getSzallodaNev($fterv_sor['telephely_id']));
                $csvhead[] = iconv2($mibol, $mibe, $fterv_sor['ev']);
                $csvhead[] = iconv2($mibol, $mibe, getStatText('fenntartasi_terv', $fterv_sor['stat_id']) );
                $csvhead[] = iconv2($mibol, $mibe, $fterv_sor['ikt_szam'] );
                fputcsv($open, $csvhead, ';');
                
                $csvhead    = array();
                while($fejlec_nevek = mysqli_fetch_field($query)){
                    $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
                    // Mee oszlop után a kalkulált oszlopot még beszúrjuk
                    if($fejlec_nevek -> name=='Mee'){
                        $csvhead[] = iconv2($mibol, $mibe, 'Mennyiség összesen' );
                        $csvhead[] = iconv2($mibol, $mibe, 'Érték összesen' );
                    }
                }
                fputcsv($open, $csvhead, ';');

                $i = 0;

                $honapok = array('Január', 'Február', 'Március', 'Április', 'Május', 'Június', 'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December');
                $egysegarak  = array();
                $mennyisegek = array();
                $ertekek     = array();
                $ertekek_mindosszesen = 0;
                while( $sorok = mysqli_fetch_assoc($query) ){

                    $mennyiseg_osszesen = 0;
                    $ertek_osszesen     = 0;
                    for($i_tmp=0; $i_tmp<12; $i_tmp++){

                        $mennyiseg_osszesen += $sorok['Mennyiség '.$honapok[$i_tmp]];
                        $ertek_osszesen     += $sorok['Érték '.$honapok[$i_tmp]];

                        $egysegarak[$honapok[$i_tmp]]   += $sorok['Egységár '   .$honapok[$i_tmp]];
                        $mennyisegek[$honapok[$i_tmp]]  += $sorok['Mennyiség '  .$honapok[$i_tmp]];
                        $ertekek[$honapok[$i_tmp]]      += $sorok['Érték '      .$honapok[$i_tmp]];
                    }
                    $ertekek_mindosszesen += $ertek_osszesen;

                    $csvcont = array();
                    foreach( $sorok as $kulcs => $sor ) {
                        if($kulcs=='Költségnem név'){
                            $sor = getErtek('nev', 'ktgnem', 'id', $sor);
                        }
                        $csvcont[] = iconv2($mibol, $mibe, strip_tags( htmlspecialchars_decode( $sor ) ) );

                        // Mee oszlop után a kalkulált oszlopot még beszúrjuk
                        if($kulcs=='Mee'){
                            $csvcont[] = $mennyiseg_osszesen;
                            $csvcont[] = $ertek_osszesen;
                        }
                    }

                    fputcsv($open, $csvcont, ';');

                    // 100 soronként kiírtatunk
                    if ($i % 100 == 0) {
                        flush();
                    }

                    $i++;
                }

                $csvcont = array('', '', '', '', '', $ertekek_mindosszesen);
                foreach($honapok as $honap){
                    $csvcont[] = '';
                    $csvcont[] = '';
                    $csvcont[] = $ertekek[$honap];
                }
                fputcsv($open, $csvcont, ';');

            // Műszaki anyag exportja
            }elseif (isset($_GET['muszaki_anyag'])) {

                $sql = 'SELECT
                            `ktgnem_id` as \'Költségnem kód\',
                            `ktgnem_id` as \'Költségnem név\',
                            `ft01` as \'Január\',
                            `ft02` as \'Február\',
                            `ft03` as \'Március\',
                            `ft04` as \'Április\',
                            `ft05` as \'Május\',
                            `ft06` as \'Június\',
                            `ft07` as \'Július\',
                            `ft08` as \'Augusztus\',
                            `ft09` as \'Szeptember\',
                            `ft10` as \'Október\',
                            `ft11` as \'November\',
                            `ft12` as \'December\'

                            FROM `'.get_db_tabla().'fenntartasi_terv_muszaki_anyag` WHERE `fenntartasi_terv_id`=\''.$_GET['id'].'\'
                            ';
                $query      = mysqli_query($db_kapcs, $sql);

                // CSV első két sora a fül neve + szálloda, évszám, státusz
                $csvhead    = array();
                $csvhead[] = iconv2($mibol, $mibe, $csv_cim);
                fputcsv($open, $csvhead, ';');
                $csvhead    = array();
                $csvhead[] = iconv2($mibol, $mibe, getSzallodaNev($fterv_sor['telephely_id']));
                $csvhead[] = iconv2($mibol, $mibe, $fterv_sor['ev']);
                $csvhead[] = iconv2($mibol, $mibe, getStatText('fenntartasi_terv', $fterv_sor['stat_id']) );
                $csvhead[] = iconv2($mibol, $mibe, $fterv_sor['ikt_szam']);
                fputcsv($open, $csvhead, ';');
                
                $csvhead    = array();
                while($fejlec_nevek = mysqli_fetch_field($query)){
                    $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
                    // Költségnem név oszlop után a kalkulált oszlopot még beszúrjuk
                    if($fejlec_nevek -> name=='Költségnem név'){
                        $csvhead[] = iconv2($mibol, $mibe, 'Összesen' );
                    }
                }
                fputcsv($open, $csvhead, ';');

                $i = 0;

                $honapok = array('Január', 'Február', 'Március', 'Április', 'Május', 'Június', 'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December');
                $ertekek     = array();
                $ertekek_mindosszesen = 0;
                while( $sorok = mysqli_fetch_assoc($query) ){

                    $osszesen = 0;
                    for($i_tmp=0; $i_tmp<12; $i_tmp++){

                        $osszesen += $sorok[$honapok[$i_tmp]];

                        $ertekek[$honapok[$i_tmp]]      += $sorok[$honapok[$i_tmp]];
                    }
                    $ertekek_mindosszesen += $osszesen;

                    $csvcont = array();
                    foreach( $sorok as $kulcs => $sor ) {
                        if($kulcs=='Költségnem név'){
                            $sor = getErtek('nev', 'ktgnem', 'id', $sor);
                        }
                        $csvcont[] = iconv2($mibol, $mibe, strip_tags( htmlspecialchars_decode( $sor ) ) );

                        // Költségnem név oszlop után a kalkulált oszlopot még beszúrjuk
                        if($kulcs=='Költségnem név'){
                            $csvcont[] = $osszesen;
                        }
                    }

                    fputcsv($open, $csvcont, ';');

                    // 100 soronként kiírtatunk
                    if ($i % 100 == 0) {
                        flush();
                    }

                    $i++;
                }

                $csvcont = array('', '', $ertekek_mindosszesen);
                foreach($honapok as $honap){
                    $csvcont[] = $ertekek[$honap];
                }
                fputcsv($open, $csvcont, ';');

            // Rendszeres karbantartás exportja
            }elseif (isset($_GET['rendsz_karban'])) {

                $sql = 'SELECT
                            `ktgnem_id` as \'Költségnem kód\',
                            `ktgnem_id` as \'Költségnem név\',
                            `targy` as \'Tárgy\',
                            `ft01` as \'Január\',
                            `ft02` as \'Február\',
                            `ft03` as \'Március\',
                            `ft04` as \'Április\',
                            `ft05` as \'Május\',
                            `ft06` as \'Június\',
                            `ft07` as \'Július\',
                            `ft08` as \'Augusztus\',
                            `ft09` as \'Szeptember\',
                            `ft10` as \'Október\',
                            `ft11` as \'November\',
                            `ft12` as \'December\'

                            FROM `'.get_db_tabla().'fenntartasi_terv_rendsz_karban` WHERE `fenntartasi_terv_id`=\''.$_GET['id'].'\'
                            ';
                $query      = mysqli_query($db_kapcs, $sql);

                // CSV első két sora a fül neve + szálloda, évszám, státusz
                $csvhead    = array();
                $csvhead[] = iconv2($mibol, $mibe, $csv_cim);
                fputcsv($open, $csvhead, ';');
                $csvhead    = array();
                $csvhead[] = iconv2($mibol, $mibe, getSzallodaNev($fterv_sor['telephely_id']));
                $csvhead[] = iconv2($mibol, $mibe, $fterv_sor['ev']);
                $csvhead[] = iconv2($mibol, $mibe, getStatText('fenntartasi_terv', $fterv_sor['stat_id']) );
                $csvhead[] = iconv2($mibol, $mibe, $fterv_sor['ikt_szam']);
                fputcsv($open, $csvhead, ';');
                
                $csvhead    = array();
                while($fejlec_nevek = mysqli_fetch_field($query)){
                    $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
                    // Tárgy oszlop után a kalkulált oszlopot még beszúrjuk
                    if($fejlec_nevek -> name=='Tárgy'){
                        $csvhead[] = iconv2($mibol, $mibe, 'Összesen' );
                    }
                }
                fputcsv($open, $csvhead, ';');

                $i = 0;

                $honapok = array('Január', 'Február', 'Március', 'Április', 'Május', 'Június', 'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December');
                $ertekek     = array();
                $ertekek_mindosszesen = 0;
                while( $sorok = mysqli_fetch_assoc($query) ){

                    $osszesen = 0;
                    for($i_tmp=0; $i_tmp<12; $i_tmp++){

                        $osszesen += $sorok[$honapok[$i_tmp]];

                        $ertekek[$honapok[$i_tmp]]      += $sorok[$honapok[$i_tmp]];
                    }
                    $ertekek_mindosszesen += $osszesen;

                    $csvcont = array();
                    foreach( $sorok as $kulcs => $sor ) {
                        if($kulcs=='Költségnem név'){
                            $sor = getErtek('nev', 'ktgnem', 'id', $sor);
                        }
                        $csvcont[] = iconv2($mibol, $mibe, strip_tags( htmlspecialchars_decode( $sor ) ) );

                        // Tárgy oszlop után a kalkulált oszlopot még beszúrjuk
                        if($kulcs=='Tárgy'){
                            $csvcont[] = $osszesen;
                        }
                    }

                    fputcsv($open, $csvcont, ';');

                    // 100 soronként kiírtatunk
                    if ($i % 100 == 0) {
                        flush();
                    }

                    $i++;
                }

                $csvcont = array('', '', '', $ertekek_mindosszesen);
                foreach($honapok as $honap){
                    $csvcont[] = $ertekek[$honap];
                }
                fputcsv($open, $csvcont, ';');

            // Eseti fenntartás exportja
            }elseif (isset($_GET['eseti_karban'])) {

                $sql = 'SELECT
                            `ktgnem_id` as \'Költségnem kód\',
                            `ktgnem_id` as \'Költségnem név\',
                            `targy` as \'Tárgy\',
                            `leir` as \'Részletes leírás\',
                            `ft01` as \'Január\',
                            `ft02` as \'Február\',
                            `ft03` as \'Március\',
                            `ft04` as \'Április\',
                            `ft05` as \'Május\',
                            `ft06` as \'Június\',
                            `ft07` as \'Július\',
                            `ft08` as \'Augusztus\',
                            `ft09` as \'Szeptember\',
                            `ft10` as \'Október\',
                            `ft11` as \'November\',
                            `ft12` as \'December\'

                            FROM `'.get_db_tabla().'fenntartasi_terv_eseti_karban` WHERE `fenntartasi_terv_id`=\''.$_GET['id'].'\'
                            ';
                $query      = mysqli_query($db_kapcs, $sql);

                // CSV első két sora a fül neve + szálloda, évszám, státusz
                $csvhead    = array();
                $csvhead[] = iconv2($mibol, $mibe, $csv_cim);
                fputcsv($open, $csvhead, ';');
                $csvhead    = array();
                $csvhead[] = iconv2($mibol, $mibe, getSzallodaNev($fterv_sor['telephely_id']));
                $csvhead[] = iconv2($mibol, $mibe, $fterv_sor['ev']);
                $csvhead[] = iconv2($mibol, $mibe, getStatText('fenntartasi_terv', $fterv_sor['stat_id']) );
                $csvhead[] = iconv2($mibol, $mibe, $fterv_sor['ikt_szam']);
                fputcsv($open, $csvhead, ';');
                
                $csvhead    = array();
                while($fejlec_nevek = mysqli_fetch_field($query)){
                    $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
                    // Részletes leírás oszlop után a kalkulált oszlopot még beszúrjuk
                    if($fejlec_nevek -> name=='Részletes leírás'){
                        $csvhead[] = iconv2($mibol, $mibe, 'Összesen' );
                    }
                }
                fputcsv($open, $csvhead, ';');

                $i = 0;

                $honapok = array('Január', 'Február', 'Március', 'Április', 'Május', 'Június', 'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December');
                $ertekek     = array();
                $ertekek_mindosszesen = 0;
                while( $sorok = mysqli_fetch_assoc($query) ){

                    $osszesen = 0;
                    for($i_tmp=0; $i_tmp<12; $i_tmp++){

                        $osszesen += $sorok[$honapok[$i_tmp]];

                        $ertekek[$honapok[$i_tmp]]      += $sorok[$honapok[$i_tmp]];
                    }
                    $ertekek_mindosszesen += $osszesen;

                    $csvcont = array();
                    foreach( $sorok as $kulcs => $sor ) {
                        if($kulcs=='Költségnem név'){
                            $sor = getErtek('nev', 'ktgnem', 'id', $sor);
                        }
                        $csvcont[] = iconv2($mibol, $mibe, strip_tags( htmlspecialchars_decode( $sor ) ) );

                        // Részletes leírás oszlop után a kalkulált oszlopot még beszúrjuk
                        if($kulcs=='Részletes leírás'){
                            $csvcont[] = $osszesen;
                        }
                    }

                    fputcsv($open, $csvcont, ';');

                    // 100 soronként kiírtatunk
                    if ($i % 100 == 0) {
                        flush();
                    }

                    $i++;
                }

                $csvcont = array('', '', '', '', $ertekek_mindosszesen);
                foreach($honapok as $honap){
                    $csvcont[] = $ertekek[$honap];
                }
                fputcsv($open, $csvcont, ';');

            // Hibalehárítás exportja
            }elseif (isset($_GET['hibaelharitas'])) {

                $sql = 'SELECT
                            `ktgnem_id` as \'Költségnem kód\',
                            `ktgnem_id` as \'Költségnem név\',
                            `leir` as \'Részletes leírás\',
                            `ft01` as \'Január\',
                            `ft02` as \'Február\',
                            `ft03` as \'Március\',
                            `ft04` as \'Április\',
                            `ft05` as \'Május\',
                            `ft06` as \'Június\',
                            `ft07` as \'Július\',
                            `ft08` as \'Augusztus\',
                            `ft09` as \'Szeptember\',
                            `ft10` as \'Október\',
                            `ft11` as \'November\',
                            `ft12` as \'December\'

                            FROM `'.get_db_tabla().'fenntartasi_terv_hibaelharitas` WHERE `fenntartasi_terv_id`=\''.$_GET['id'].'\'
                            ';
                $query      = mysqli_query($db_kapcs, $sql);

                // CSV első két sora a fül neve + szálloda, évszám, státusz
                $csvhead    = array();
                $csvhead[] = iconv2($mibol, $mibe, $csv_cim);
                fputcsv($open, $csvhead, ';');
                $csvhead    = array();
                $csvhead[] = iconv2($mibol, $mibe, getSzallodaNev($fterv_sor['telephely_id']));
                $csvhead[] = iconv2($mibol, $mibe, $fterv_sor['ev']);
                $csvhead[] = iconv2($mibol, $mibe, getStatText('fenntartasi_terv', $fterv_sor['stat_id']) );
                $csvhead[] = iconv2($mibol, $mibe, $fterv_sor['ikt_szam']);
                fputcsv($open, $csvhead, ';');
                
                $csvhead    = array();
                while($fejlec_nevek = mysqli_fetch_field($query)){
                    $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
                    // Részletes leírás oszlop után a kalkulált oszlopot még beszúrjuk
                    if($fejlec_nevek -> name=='Részletes leírás'){
                        $csvhead[] = iconv2($mibol, $mibe, 'Összesen' );
                    }
                }
                fputcsv($open, $csvhead, ';');

                $i = 0;

                $honapok = array('Január', 'Február', 'Március', 'Április', 'Május', 'Június', 'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December');
                $ertekek     = array();
                $ertekek_mindosszesen = 0;
                while( $sorok = mysqli_fetch_assoc($query) ){

                    $osszesen = 0;
                    for($i_tmp=0; $i_tmp<12; $i_tmp++){

                        $osszesen += $sorok[$honapok[$i_tmp]];

                        $ertekek[$honapok[$i_tmp]]      += $sorok[$honapok[$i_tmp]];
                    }
                    $ertekek_mindosszesen += $osszesen;

                    $csvcont = array();
                    foreach( $sorok as $kulcs => $sor ) {
                        if($kulcs=='Költségnem név'){
                            $sor = getErtek('nev', 'ktgnem', 'id', $sor);
                        }
                        $csvcont[] = iconv2($mibol, $mibe, strip_tags( htmlspecialchars_decode( $sor ) ) );

                        // Részletes leírás oszlop után a kalkulált oszlopot még beszúrjuk
                        if($kulcs=='Részletes leírás'){
                            $csvcont[] = $osszesen;
                        }
                    }

                    fputcsv($open, $csvcont, ';');

                    // 100 soronként kiírtatunk
                    if ($i % 100 == 0) {
                        flush();
                    }

                    $i++;
                }

                $csvcont = array('', '', '', $ertekek_mindosszesen);
                foreach($honapok as $honap){
                    $csvcont[] = $ertekek[$honap];
                }
                fputcsv($open, $csvcont, ';');


            // Miniprojekt exportja
            }elseif (isset($_GET['miniprojekt'])) {

                $sql = 'SELECT
                            `ktgnem_id` as \'Költségnem kód\',
                            `ktgnem_id` as \'Költségnem név\',
                            `targy` as \'Tárgy\',
                            `leir` as \'Részletes leírás\',
                            `ft01` as \'Január\',
                            `ft02` as \'Február\',
                            `ft03` as \'Március\',
                            `ft04` as \'Április\',
                            `ft05` as \'Május\',
                            `ft06` as \'Június\',
                            `ft07` as \'Július\',
                            `ft08` as \'Augusztus\',
                            `ft09` as \'Szeptember\',
                            `ft10` as \'Október\',
                            `ft11` as \'November\',
                            `ft12` as \'December\'

                            FROM `'.get_db_tabla().'fenntartasi_terv_miniprojekt` WHERE `fenntartasi_terv_id`=\''.$_GET['id'].'\'
                            ';
                $query      = mysqli_query($db_kapcs, $sql);

                // CSV első két sora a fül neve + szálloda, évszám, státusz
                $csvhead    = array();
                $csvhead[] = iconv2($mibol, $mibe, $csv_cim);
                fputcsv($open, $csvhead, ';');
                $csvhead    = array();
                $csvhead[] = iconv2($mibol, $mibe, getSzallodaNev($fterv_sor['telephely_id']));
                $csvhead[] = iconv2($mibol, $mibe, $fterv_sor['ev']);
                $csvhead[] = iconv2($mibol, $mibe, getStatText('fenntartasi_terv', $fterv_sor['stat_id']) );
                $csvhead[] = iconv2($mibol, $mibe, $fterv_sor['ikt_szam']);
                fputcsv($open, $csvhead, ';');
                
                $csvhead    = array();
                while($fejlec_nevek = mysqli_fetch_field($query)){
                    $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
                    // Részletes leírás oszlop után a kalkulált oszlopot még beszúrjuk
                    if($fejlec_nevek -> name=='Részletes leírás'){
                        $csvhead[] = iconv2($mibol, $mibe, 'Összesen' );
                    }
                }
                fputcsv($open, $csvhead, ';');

                $i = 0;

                $honapok = array('Január', 'Február', 'Március', 'Április', 'Május', 'Június', 'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December');
                $ertekek     = array();
                $ertekek_mindosszesen = 0;
                while( $sorok = mysqli_fetch_assoc($query) ){

                    $osszesen = 0;
                    for($i_tmp=0; $i_tmp<12; $i_tmp++){

                        $osszesen += $sorok[$honapok[$i_tmp]];

                        $ertekek[$honapok[$i_tmp]]      += $sorok[$honapok[$i_tmp]];
                    }
                    $ertekek_mindosszesen += $osszesen;

                    $csvcont = array();
                    foreach( $sorok as $kulcs => $sor ) {
                        if($kulcs=='Költségnem név'){
                            $sor = getErtek('nev', 'ktgnem', 'id', $sor);
                        }
                        $csvcont[] = iconv2($mibol, $mibe, strip_tags( htmlspecialchars_decode( $sor ) ) );

                        // Részletes leírás oszlop után a kalkulált oszlopot még beszúrjuk
                        if($kulcs=='Részletes leírás'){
                            $csvcont[] = $osszesen;
                        }
                    }

                    fputcsv($open, $csvcont, ';');

                    // 100 soronként kiírtatunk
                    if ($i % 100 == 0) {
                        flush();
                    }

                    $i++;
                }

                $csvcont = array('', '', '', '', $ertekek_mindosszesen);
                foreach($honapok as $honap){
                    $csvcont[] = $ertekek[$honap];
                }
                fputcsv($open, $csvcont, ';');
                
            // BWH exportja
            }elseif (isset($_GET['bwh'])) {

                $sql = 'SELECT
                            `ktgnem_id` as \'Költségnem kód\',
                            `ktgnem_id` as \'Költségnem név\',
                            `targy` as \'Tárgy\',
                            `leir` as \'Részletes leírás\',
                            `ft01` as \'Január\',
                            `ft02` as \'Február\',
                            `ft03` as \'Március\',
                            `ft04` as \'Április\',
                            `ft05` as \'Május\',
                            `ft06` as \'Június\',
                            `ft07` as \'Július\',
                            `ft08` as \'Augusztus\',
                            `ft09` as \'Szeptember\',
                            `ft10` as \'Október\',
                            `ft11` as \'November\',
                            `ft12` as \'December\'

                            FROM `'.get_db_tabla().'fenntartasi_terv_bwh` WHERE `fenntartasi_terv_id`=\''.$_GET['id'].'\'
                            ';
                $query      = mysqli_query($db_kapcs, $sql);

                // CSV első két sora a fül neve + szálloda, évszám, státusz
                $csvhead    = array();
                $csvhead[] = iconv2($mibol, $mibe, $csv_cim);
                fputcsv($open, $csvhead, ';');
                $csvhead    = array();
                $csvhead[] = iconv2($mibol, $mibe, getSzallodaNev($fterv_sor['telephely_id']));
                $csvhead[] = iconv2($mibol, $mibe, $fterv_sor['ev']);
                $csvhead[] = iconv2($mibol, $mibe, getStatText('fenntartasi_terv', $fterv_sor['stat_id']) );
                $csvhead[] = iconv2($mibol, $mibe, $fterv_sor['ikt_szam']);
                fputcsv($open, $csvhead, ';');
                
                $csvhead    = array();
                while($fejlec_nevek = mysqli_fetch_field($query)){
                    $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
                }
                fputcsv($open, $csvhead, ';');

                $i = 0;

                $honapok = array('Január', 'Február', 'Március', 'Április', 'Május', 'Június', 'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December');
                $ertekek     = array();
                $ertekek_mindosszesen = 0;
                while( $sorok = mysqli_fetch_assoc($query) ){

                    $osszesen = 0;
                    for($i_tmp=0; $i_tmp<12; $i_tmp++){

                        $osszesen += $sorok[$honapok[$i_tmp]];

                        $ertekek[$honapok[$i_tmp]]      += $sorok[$honapok[$i_tmp]];
                    }
                    $ertekek_mindosszesen += $osszesen;

                    $csvcont = array();
                    foreach( $sorok as $kulcs => $sor ) {
                        if($kulcs=='Költségnem név'){
                            $sor = getErtek('nev', 'ktgnem', 'id', $sor);
                        }
                        $csvcont[] = iconv2($mibol, $mibe, strip_tags( htmlspecialchars_decode( $sor ) ) );
                    }

                    fputcsv($open, $csvcont, ';');

                    // 100 soronként kiírtatunk
                    if ($i % 100 == 0) {
                        flush();
                    }

                    $i++;
                }

                $csvcont = array('', '', '', '');
                foreach($honapok as $honap){
                    $csvcont[] = $ertekek[$honap];
                }
                fputcsv($open, $csvcont, ';');

            // Összesen exportja
            }elseif (isset($_GET['osszesen'])) {

                // CSV első két sora a fül neve + szálloda, évszám, státusz
                $csvhead    = array();
                $csvhead[] = iconv2($mibol, $mibe, $csv_cim);
                fputcsv($open, $csvhead, ';');
                $csvhead    = array();
                $csvhead[] = iconv2($mibol, $mibe, getSzallodaNev($fterv_sor['telephely_id']));
                $csvhead[] = iconv2($mibol, $mibe, $fterv_sor['ev']);
                $csvhead[] = iconv2($mibol, $mibe, getStatText('fenntartasi_terv', $fterv_sor['stat_id']) );
                $csvhead[] = iconv2($mibol, $mibe, $fterv_sor['ikt_szam']);
                fputcsv($open, $csvhead, ';');
                
                $fofo_mindosszesen = 0;
                $fofo_ertekek      = array();
                $tabla_postok = array(
                                        array('tabla' => 'energia',         'nev' => 'Energia'),
                                        array('tabla' => 'muszaki_anyag',   'nev' => 'Műszaki anyag'),
                                        array('tabla' => 'rendsz_karban',   'nev' => 'Rendszeres karbantartás'),
                                        array('tabla' => 'eseti_karban',    'nev' => 'Eseti fenntartás'),
                                        array('tabla' => 'hibaelharitas',   'nev' => 'Hibaelhárítás'),
                                        array('tabla' => 'miniprojekt',     'nev' => 'Miniprojekt'),
                                        
                                    );
                // BWH feltétele
                $sajat_szallodak = get_akt_login_szallodai();
                if( in_array(2120, $sajat_szallodak) && $fterv_sor['telephely_id']==2120 ){
                    array_push($tabla_postok, array('tabla' => 'bwh', 'nev' => 'Cukrászda BWH'));
                }
                foreach($tabla_postok as $tabla_post){
                    $sql = 'SELECT
                                `ktgnem_id` as \'Költségnem kód\',
                                `ktgnem_id` as \'Költségnem név\',
                                `ft01` as \'Január\',
                                `ft02` as \'Február\',
                                `ft03` as \'Március\',
                                `ft04` as \'Április\',
                                `ft05` as \'Május\',
                                `ft06` as \'Június\',
                                `ft07` as \'Július\',
                                `ft08` as \'Augusztus\',
                                `ft09` as \'Szeptember\',
                                `ft10` as \'Október\',
                                `ft11` as \'November\',
                                `ft12` as \'December\'

                                FROM `'.get_db_tabla().'fenntartasi_terv_'.$tabla_post['tabla'].'` WHERE `fenntartasi_terv_id`=\''.$_GET['id'].'\'
                                GROUP BY `ktgnem_id`
                                ';
                    $query      = mysqli_query($db_kapcs, $sql);

                    if($tabla_post['tabla']=='energia'){
                        $csvhead    = array();
                        while($fejlec_nevek = mysqli_fetch_field($query)){
                            $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
                            // Költségnem név oszlop után a kalkulált oszlopot még beszúrjuk
                            if($fejlec_nevek -> name=='Költségnem név'){
                                $csvhead[] = iconv2($mibol, $mibe, 'Összesen' );
                            }
                        }
                        fputcsv($open, $csvhead, ';');
                    }
                    $i = 0;

                    $honapok = array('Január', 'Február', 'Március', 'Április', 'Május', 'Június', 'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December');
                    $ertekek     = array();
                    $ertekek_mindosszesen = 0;
                    while( $sorok = mysqli_fetch_assoc($query) ){
                        
                        // Kinullázzuk az értéket, mert a GroupBy-ozott sorokat összeadogatjuk
                        for($i_tmp=0; $i_tmp<12; $i_tmp++){
                            $sorok[$honapok[$i_tmp]] = 0;
                        }
                        $group_sorok = $adatbazis_kapcsolat->s('*', 'fenntartasi_terv_'.$tabla_post['tabla'], "`fenntartasi_terv_id`='{$_GET['id']}' AND `ktgnem_id`='{$sorok['Költségnem kód']}'");
                        foreach($group_sorok as $g_sorok){
                            
                            for($i_tmp=1; $i_tmp<13; $i_tmp++){
                                if($i_tmp<10){
                                    $i_tmp = '0'.$i_tmp;
                                }
                                $sorok[$honapok[(int)$i_tmp-1]] += $g_sorok['ft'.$i_tmp];
                            }
                        }
                        
                        $osszesen = 0;
                        for($i_tmp=0; $i_tmp<12; $i_tmp++){

                            $osszesen += $sorok[$honapok[$i_tmp]];

                            $ertekek[$honapok[$i_tmp]]      += $sorok[$honapok[$i_tmp]];
                            $fofo_ertekek[$honapok[$i_tmp]] += $sorok[$honapok[$i_tmp]];
                        }
                        $ertekek_mindosszesen += $osszesen;
                        $fofo_mindosszesen    += $osszesen;

                        $csvcont = array();
                        foreach( $sorok as $kulcs => $sor ) {
                            if($kulcs=='Költségnem név'){
                                $sor = getErtek('nev', 'ktgnem', 'id', $sor);
                            }
                            $csvcont[] = iconv2($mibol, $mibe, strip_tags( htmlspecialchars_decode( $sor ) ) );

                            // Költségnem név oszlop után a kalkulált oszlopot még beszúrjuk
                            if($kulcs=='Költségnem név'){
                                $csvcont[] = $osszesen;
                            }
                        }

                        fputcsv($open, $csvcont, ';');

                        // 100 soronként kiírtatunk
                        if ($i % 100 == 0) {
                            flush();
                        }

                        $i++;
                    }
                    $csvcont = array('', iconv2($mibol, $mibe, $tabla_post['nev'].' összesen'), $ertekek_mindosszesen);
                    foreach($honapok as $honap){
                        $csvcont[] = $ertekek[$honap];
                    }
                    fputcsv($open, $csvcont, ';');
                }
                $csvcont = array('', '', $fofo_mindosszesen);
                foreach($honapok as $honap){
                    $csvcont[] = $fofo_ertekek[$honap];
                }
                fputcsv($open, $csvcont, ';');

            // Fenntartási terv exportálása
            }else{

                if( strstr($_SESSION['akt_sql'], 'WHERE') ) {
                    $lekerdezes_fromtol = str_replace('WHERE', "WHERE `". get_db_tabla()."$megnevezes`.`recid` IN (".$_SESSION['nyomtatas_ids'].") AND ", $lekerdezes_fromtol);
                } else {
                    $lekerdezes_fromtol = str_replace('ORDER', "WHERE `". get_db_tabla()."$megnevezes`.`recid` IN (".$_SESSION['nyomtatas_ids'].") ORDER", $lekerdezes_fromtol);
                }

                $sql = 'SELECT
                            `ikt_szam` as \'Energia terv szám\',
                            `ev` as \'Tervező év\',
                            `stat_id` as \'Státusz\',
                            `telephely_id` as \'Szálloda\',
                            `text` as \'Leírás\',
                            `dok_db` as \'Dokumentumok\',
                            `megj_db` as \'Megjegyzések\'
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
                        if($kulcs=='Szálloda'){
                            $sor = getSzallodaNev($sor);
                        }
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

    if( isset($_GET['modosit']) || isset($_GET['energia']) || isset($_GET['muszaki_anyag']) || isset($_GET['rendsz_karban']) || isset($_GET['eseti_karban']) || isset($_GET['hibaelharitas']) || isset($_GET['miniprojekt']) || isset($_GET['bwh']) || isset($_GET['tortenet']) || isset($_GET['osszesen']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Tervek',
            'energia'       => 'Energia',
            'muszaki_anyag' => 'Műszaki anyag',
            'rendsz_karban' => 'Rendszeres karbantartás',
            'eseti_karban'  => 'Eseti fenntartás',
            'hibaelharitas' => 'Hibaelhárítás',
            'miniprojekt'   => 'Miniprojekt',
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
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'A fenntartási terv nem munkaközi, így nem törölhető!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        }else{
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'A következő fenntartási terv törlésére készül:<br><br>Tervező év: '.$sor['ev'].'<br><br>Biztos, hogy törli?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
        }

        // Terv adatlapján
        if(isset($_GET['modosit'])){
            // Élesítés gomb
            if( $sor['stat_id']==ENERGIATERV_STAT_KESZ && _jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL) ){
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Élesítés" onclick="fenntartasi_terv_elesites()"> ';
            }
            if( _jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL) && $sor['stat_id']==ENERGIATERV_STAT_MUNKAKOZI ){
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Véglegesítés" onclick="fenntartasterv_veglegesit()"> ';
            }

        // Ha nem az adatlapon vagyunk
        }else{

            if( _jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL) && $sor['stat_id']==ENERGIATERV_STAT_MUNKAKOZI ){
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Véglegesítés" onclick="fenntartasterv_veglegesit()"> ';
            }

            // Fülek alatti gombok csak akkor érhetőek el, ha van is hozzá jogunk, és munkaközi a státusza, kivéve az energiát (ott csak akkor, ha nem éles)
            if( _jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL) && $sor['stat_id']==ENERGIATERV_STAT_MUNKAKOZI ){
                if( isset($_GET['energia']) ){
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
                if( isset($_GET['miniprojekt']) ){
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Kitöltés" onclick="kapcsolodoSzerkeszt(\'ft_miniprojekt\');"> ';
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Új sor" onclick="kapcsolodoSzerkeszt(\'ft_miniprojekt_uj\');"> ';
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Sor törlés" onclick="sor_torol(\'miniprojekt\');"> ';
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

    // Ha nem éles a terv, akkor törlésnél jelezzük ezt
    }else{
        $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'A fenntartási terv már éles, így nem törölhető!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
    }

    if( $gombok ) {
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

	    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");

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

        $tomb = fenntartasi_terv_energia($sor);
        $szamla_html  .= $tomb[0];
        $volt_valtozas = $tomb[1];

        // Ha van eltérés az energia egységárahoz képest, akkor egy plusz gombot kiíruratunk
        if($volt_valtozas && $sor['stat_id']==ENERGIATERV_STAT_MUNKAKOZI && _jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL)){
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

        $szamla_html .= fenntartasi_terv_muszaki_anyag($sor);

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

        $szamla_html .= fenntartasi_terv_rendsz_karban($sor);

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

        $szamla_html .= fenntartasi_terv_eseti_karban($sor);

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

        $szamla_html .= fenntartasi_terv_hibaelharitas($sor);

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
        

	// Miniprojekt
	}elseif(isset($_GET['miniprojekt'])){

        $szamla_html .= fenntartasi_terv_miniprojekt($sor);

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

        $szamla_html .= fenntartasi_terv_bwh($sor);

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

        $szamla_html .= fenntartasi_terv_osszesen($sor, $ful_tomb, $sajat_szallodak);

        // Ha van a get-ben ft id, akkor azt a sort jelöljük meg
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
