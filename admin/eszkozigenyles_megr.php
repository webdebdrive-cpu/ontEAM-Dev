<?php
require_once('../weblap.php');

if( !_jog(JOG_BESZERZ_IGENYLESEK_MEGR_OLVAS) ) {
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
$akt_tomb               = $eszkozigenyles_megr_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'megrendelést';                   // Hibaüzenetkor: Biztosan törlöd a $szoveg?

$szamla_html = '';

// Dokumentumok fülhöz kell majd
$tobb_kep = 1;
if( $tobb_kep ) {

    function get_tobbkep_darab()    { return 10;         } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '';         } // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;         } // MB-ban
    function get_tobbkep_oszlopnev(){                      // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!
}

// Megrendelő kiküldése
if (isset($_POST['megrendelo_kikuldese'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('stat_id hash rend_szam szall_mail szallito_id', 'eszkozigenyles_megr', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_MEGR_KIKULD) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga megrendelést kiküldeni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_MEGR_STAT_KIKULD_VAR) {
        echo json_encode(array(
                                'hiba'  => 'Ebben a státuszban nem lehet megrendelést kiküldeni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Levélküldés
    // Kontakt személy lekérdezése: akinek van igénylés létrehozás joga és adott igénylés szállodája a látókörében van
    // Viszon ez egy megrendelés, amihez több igénylés is tartozhat, ezért vesszük az elsőt
    $kontakt = $adatbazis_kapcsolat -> s2('teljes_nev usertel usermail beosztas_id', 'login', "substr(`flags`, ".(JOG_BESZERZ_IGENYLESEK_LETREH+1).", 1)=1 AND `aktiv`=1 AND `virtual`=0 AND `nev` IN (SELECT `nev` FROM `".get_db_tabla()."telephely_kapcs` WHERE `telephely_id` IN (SELECT `telephely_id` FROM `".get_db_tabla()."eszkozigenyles` WHERE `rend_szam`='{$sor['rend_szam']}'))");
    $kontakt_alairas  = '<br>'.$kontakt['teljes_nev'];
    if( $kontakt['beosztas_id'] ) {
        $kontakt_alairas .= '<br>'.getErtekLista(ERTEKLISTA_BEOSZTAS, $kontakt['beosztas_id']);
    }
    if( $kontakt['usertel'] ) {
        $kontakt_alairas .= '<br>Tel.: '.$kontakt['usertel'];
    }
    if( $kontakt['usermail'] ) {
        $kontakt_alairas .= '<br>E-mail: '.$kontakt['usermail'];
    }

    // Attól függően, hogy melyik rendszerben vagyunk, kap a tárgy/üzenet egy [Teszt] előtagot.
    $kieg = get_mail_teszt_felirat();
    $targy_kieg = $kieg[0];
    $uzi_kieg   = $kieg[1];

    // Címzett nevének lekérése
    $szallito = $adatbazis_kapcsolat -> s2('knev', 'szallito_beszerzes', "`recid`='{$sor['szallito_id']}'");
    if( !$szallito['knev']) {
        $szallito['knev'] = 'Címzett';
    }

    $targy = $targy_kieg.$sor['rend_szam'].' megrendelés';
    $uzi   = $uzi_kieg.'Tisztelt '.$szallito['knev'].'!
    <br>
    <br>Ezúton küldjük a megrendelést csatolva.
    <br>Kérjük, hogy alábbi gombra kattintással igazolják vissza a megrendelő email beérkezését:
    <br><a href="https://'.get_domain().'/megr_ig.php?h='.$sor['hash'].'" style="display: inline-block; padding: 5px 10px; border-radius: 5px; background: #438fe5ff; color: #fff; text-decoration: none; margin-top: 5px;" target="_blank">
        Megrendelő emailt megkaptuk
    </a>
    <br>
    <br>Üdvözlettel:
    '.$kontakt_alairas.'
    '.str_replace('Üdvözlettel:', '', fajlbol_olvas('../sablon/admin/mail/alairas.html'));

    $dok = $adatbazis_kapcsolat -> s2('utvonal', 'eszkozigenyles_megr_dok', "`eszkozigenyles_megr_id`='{$_POST['id']}'", 'recid asc', '1');
    $dok = '../'.get_utvonal().'eszkozigenyles_megr/'.$dok['utvonal'];

    // Dev és Teszt rendszerben annak megy ki a levél, aki a gombot megnyomta. FONTOS, hogy Teszt-en is, mert nem mehet ki élesbe szállítóknak
    if( $_SERVER['HTTP_HOST'] == 'dev.onteam.hu' || $_SERVER['HTTP_HOST'] == 'teszt.onteam.hu' ) {
        $login = $adatbazis_kapcsolat -> s2('usermail', 'login', "`nev`='{$_SESSION[get_db_tabla().'user']}'");
        $sor['szall_mail'] = $login['usermail'];
    }

    // Címzettek a szállító email címei
    $cimzettek = explode(',', $sor['szall_mail']);
    // Teszt felületen azt kérték, hogy Tamás és Tibi kapjanak másolatot
    if( $_SERVER['HTTP_HOST'] == 'teszt.onteam.hu' ) {
        array_push($cimzettek, SZINVATREND_TESZT_MAIL );
        array_push($cimzettek, SZINVATREND_TESZT_MAIL2);
    }

// Debug módban csak WebDeb
if( $_SERVER['REMOTE_ADDR'] == get_debug_ip() ) {
    $cimzettek = [WEBDEB_TESZT_MAIL2];
}

    foreach ($cimzettek as $cimzett) {
        $cimzett = trim($cimzett);
        if( $cimzett ) {
            level_kuldes(get_nev(), get_mail(), $szallito['knev'], $cimzett, $targy, $uzi, $dok);
        }
    }
    // Email küldésről megjegyzés
    $megj = 'E-mail elküldve '.$szallito['knev'].' (e-mail: '.$sor['szall_mail'].') szállítónak megrendeléskor';
    if( addMegj('eszkozigenyles_megr', $_POST['id'], $megj) === false ) {
        $db_error = true;
    }

    // Megrendelés dátum kitöltése
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles_megr', array('megr_dt'), array('NOW()'), "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Státusz módosítása megrendelésnél
    if( addStatusz('eszkozigenyles_megr_statusz', $_POST['id'], ESZKOZIGENYL_MEGR_STAT_MEGRENDELVE) === false ) {
        $db_error = true;
    }
    // Státusz módosítása igényléseknél
    $eszkozigenylesek = $adatbazis_kapcsolat -> s('recid', 'eszkozigenyles', "`rend_szam`='{$sor['rend_szam']}'");
    foreach ($eszkozigenylesek as $eszkozigenyles) {
        if( addStatusz('eszkozigenyles_statusz', $eszkozigenyles['recid'], ESZKOZIGENYL_STAT_MEGRENDELVE) === false ) {
            $db_error = true;
        }
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                ) );
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba'
        ));
    }

    $weblap -> leallit();
    exit;
}

// Megrendelés javításához adatok lekérdezése
if (isset($_POST['get_megr_javit'])) {

    $megrendeles    = $adatbazis_kapcsolat -> s2('rend_szam telephely_id szallito_id megr_dt', 'eszkozigenyles_megr', "`recid`='{$_POST['id']}'");
    $eszkozigenyles = $adatbazis_kapcsolat -> s2('tipus', 'eszkozigenyles', "`rend_szam`='{$megrendeles['rend_szam']}'");

    $html = nl2('
        <b>Rendelés száma:</b> '.$megrendeles['rend_szam'].'
        <br><b>Szálloda:</b> '.getSzallodaNev($megrendeles['telephely_id']).'
        <br><b>Szállító:</b> '.getErtek('nev', 'szallito_beszerzes', 'recid', $megrendeles['szallito_id']).'
        <br><b>Megrendelés dátuma:</b> '.datum_kiir($megrendeles['megr_dt']).'
        <br>
        <br>Biztosan javítja ezt a megrendelést?
    ');

    $gomb_vegl = '';
    // Ha nem GM terhes, csak akkor jelenik meg a "BI véglegesítésre vár" státusz gomb
    if( $eszkozigenyles['tipus'] != BESZ_TIP_GM_KERET ) {
        $gomb_vegl = '<input type="button" class="gomb zold" value="BI véglegesítésre vár" onclick="megrendeles_javitasa(\'bi_vegl\');">';
    }
    $gombok = nl2('
        <input type="button" class="gomb" value="BI validálásra vár" onclick="megrendeles_javitasa(\'bi_valid\');">
        '.$gomb_vegl.'
        <input type="button" name="megsem" class="gombb_alert" value="Mégsem" onclick="popupZar();">
    ');

    echo json_encode(array(
                             'html'     => $html,
                             'gombok'   => $gombok,
                     ));

    $weblap -> leallit();
    exit;
}

// Megrendelés javítása
if (isset($_POST['megrendeles_javitasa'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $megrendeles = $adatbazis_kapcsolat -> s2('recid stat_id rend_szam', 'eszkozigenyles_megr', "`recid`='{$_POST['id']}'");
    if( $megrendeles === false ) { $db_error = true; }

    $eszkozigenyles = $adatbazis_kapcsolat -> s2('recid tipus stat_id rend_szam', 'eszkozigenyles', "`rend_szam`='{$megrendeles['rend_szam']}'");
    if( $eszkozigenyles === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_KEZEL) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga megrendelést javítani!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $megrendeles['stat_id'] != ESZKOZIGENYL_MEGR_STAT_MEGRENDELVE && $megrendeles['stat_id'] != ESZKOZIGENYL_MEGR_STAT_KIKULD_VAR) {
        echo json_encode(array(
                                'hiba'  => 'Ebben a státuszban nem lehet megrendelést javítani!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Megrendelés és annak dokumentumának törlése
    $dokumentumok = $adatbazis_kapcsolat -> s('utvonal', 'eszkozigenyles_megr_dok', "`eszkozigenyles_megr_id`='{$_POST['id']}'");
    if( $dokumentumok === false ) { $db_error = true; }
    foreach ($dokumentumok as $dokumentum) {
        // Fájl törlése
        unlink( '../'.get_utvonal().'eszkozigenyles_megr/'.$dokumentum['utvonal'] );

        // A fájl be volt csatolva eszközigénylésekhez is, onnan is törölni kell, dokumentum újraszámolással
        $eszkozigenyles_dokok = $adatbazis_kapcsolat -> s('recid eszkozigenyles_id', 'eszkozigenyles_dok', "`utvonal` LIKE '%{$dokumentum['utvonal']}'");
        if( $eszkozigenyles_dokok === false ) { $db_error = true; }
        foreach ($eszkozigenyles_dokok as $eszkozigenyles_dok) {
            $sql = $adatbazis_kapcsolat -> d('eszkozigenyles_dok', "`recid`='{$eszkozigenyles_dok['recid']}'");
            if( $sql === false ) { $db_error = true; }

            $eszkozigenyles_dok_db = $adatbazis_kapcsolat -> s('recid', 'eszkozigenyles_dok', "`eszkozigenyles_id`='{$eszkozigenyles_dok['eszkozigenyles_id']}'", '', '', '', '-num-rows-');
            if( $eszkozigenyles_dok_db === false ) { $db_error = true; }

            $sql = $adatbazis_kapcsolat -> u('eszkozigenyles', array('dok_db'), array($eszkozigenyles_dok_db), "`recid`='{$eszkozigenyles_dok['eszkozigenyles_id']}'");
            if( $sql === false ) { $db_error = true; }
        }
    }

    // Megrendelés törlése
    $sql = $adatbazis_kapcsolat -> d('eszkozigenyles_megr', "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }
    
    // Megrendelés dokumentumok törlése
    $sql = $adatbazis_kapcsolat -> d('eszkozigenyles_megr_dok', "`eszkozigenyles_megr_id`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }
    
    // Megrendelés státuszok törlése
    $sql = $adatbazis_kapcsolat -> d('eszkozigenyles_megr_statusz', "`eszkozigenyles_megr_recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Rendelés szám ürítése igényéseknél
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles', array('rend_szam'), array(''), "`rend_szam`='{$megrendeles['rend_szam']}'");
    if( $sql === false ) { $db_error = true; }

    // Státuszléptetés - JS-től kapott státusz szöveg azonosítása
    $statusz_lep = '';
    switch ($_POST['statusz']) {
        case 'bi_valid':
            $statusz_lep = ESZKOZIGENYL_STAT_BI_VALID_VAR;
            // E-mail értesítés küldése, ha soron kívüli
            if( $eszkozigenyles['tipus'] != BESZ_TIP_TERVEZETT ) {
                $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $eszkozigenyles['recid'], ERT_BESZERZ_BI_VALIDALAS_ELO, 'eszkozigenyles', 'eszkozigenyles_bi_validalas_elo');
            }
        break;
        case 'bi_vegl':
            $statusz_lep = ESZKOZIGENYL_STAT_BI_VEGL_VAR;
            // E-mail értesítés küldése, ha soron kívüli
            if( $eszkozigenyles['tipus'] != BESZ_TIP_TERVEZETT ) {
                $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $eszkozigenyles['recid'], ERT_BESZERZ_BI_VEGLEGESIT_ELO, 'eszkozigenyles', 'eszkozigenyles_bi_veglegesit_elo');
            }
        break;
    }
    if( $statusz_lep ) {
        if( addStatusz('eszkozigenyles_statusz', $eszkozigenyles['recid'], $statusz_lep) === false ) {
            $db_error = true;
        }
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker' => 1,
                                ) );
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba'
        ));
    }

    $weblap -> leallit();
    exit;
}

// Megjegyzések kiíratása
if (isset($_POST['megj_kiir'])) {

    echo json_encode( array('html' => megj_kiir($megnevezes, $_POST['id'], $_POST['rendszer'])) );

    $weblap -> leallit();
    exit;
}

// Határidő lekérése
if (isset($_POST['get_hatarido'])) {

    $megr = $adatbazis_kapcsolat -> s2('szall_hat', 'eszkozigenyles_megr', "`recid`='{$_POST['id']}'");

    echo json_encode(array(
                             'hatarido'  => datum_kiir($megr['szall_hat']),
                     ));

    $weblap -> leallit();
    exit;
}

// Új határidő mentése
if (isset($_POST['hatraido_ment'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( !valos_datum_e($_POST['hatarido']) ) {
        ajax_hiba('Érvénytelen dátumot adott meg!');
    }

    $megrendeles = $adatbazis_kapcsolat -> s2('recid stat_id', 'eszkozigenyles_megr', "`recid`='{$_POST['id']}'");
    if( $megrendeles === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_MEGR_KIKULD) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga határidőt megadni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Határidőt bármikor lehet megadni
    // Nem megfelelő státuszkor hibaüzenet
    // if( $megrendeles['stat_id'] != ESZKOZIGENYL_MEGR_STAT_KIKULD_VAR ) {
    //     echo json_encode(array(
    //                             'hiba'  => 'Ebben a státuszban nem lehet határidőt megadni!',
    //                             'title' => 'Hiba',
    //                     ));
    //     $weblap -> leallit();
    //     exit;
    // }

    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles_megr', array('szall_hat'), array($_POST['hatarido']), "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    if( addMegj('eszkozigenyles_megr', $_POST['id'], 'Új szállítási határidő: '.datum_kiir($_POST['hatarido'])) === false ) {
        $db_error = true;
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker' => 1,
                                ) );
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba'
        ));
    }

    $weblap -> leallit();
    exit;
}

// CSV generálása
if( isset($_GET['csv']) ) {

    // CSV export előkészítése
    list($mibol, $mibe, $filename, $open) = csv_export_elokeszit($megnevezes);
    if( $open ) {
        
        $sql = 'SELECT
                `rend_szam` as \'Rendelés szám\',
                `telephely_id` as \'Szálloda\',
                `szallito_id` as \'Szállító\',
                `ossz_dev` as \'Deviza összesen\',
                `ossz_netto` as \'Nettó összesen\',
                `szall_hat` as \'Szállítási határidő\',
                `'.get_db_tabla().$megnevezes.'`.`insert_dt` as \'Megrendelés dátuma\',
                `szall_mail` as \'Szállító email címe\'
                '. csv_export_get_from_tol($megnevezes);
        $query = mysqli_query($db_kapcs, $sql);

        // Nagyobb CSV-kre kérdezzünk rá előzetesen
        $osszes_sor = mysqli_num_rows($query);
        if( !$_GET['mehet'] && $osszes_sor>CSV_EXPORT_LIMIT ) {
            $gomb = '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#navGombok .excel\').attr(\'onclick\', \'csv_general( this, \\\'\\\', \\\'&mehet=1\\\');\'); $(\'.navGombok a.excel\').click();"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">';
            echo json_encode(array('hiba' => 'A CSV export több mint '.number_format(CSV_EXPORT_LIMIT, 0, '', ' ').' sort tartalmaz. Ennek előállítása hosszabb időbe telik majd.<br>Biztosan folytatja?', 'gomb' => $gomb));
            $weblap -> leallit();
            exit;
        } else {
            $sql .= $_SESSION['akt_sql_order'];
        }

        $query      = mysqli_query($db_kapcs, $sql);
        $csvhead    = array();
        while ( $fejlec_nevek = mysqli_fetch_field($query) ) {
            $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
        }
        fputcsv($open, $csvhead, ';');

        $i = 0;
        while( $sorok = mysqli_fetch_assoc($query) ) {

            $csvcont = array();
            foreach( $sorok as $kulcs => $sor ) {

                if( $kulcs == 'Szálloda' ) {
                    $sor = getSzallodaNev($sor);
                }
                if( $kulcs == 'Szállító' ) {
                    $sor = getErtek('nev', 'szallito_beszerzes', 'recid', $sor);
                }

                // Lehet nem volna elég 1-szer sőt kétszer sem ráereszteni a htmlspecialchars_decode-ot
                $sor = htmlspecialchars_decode( $sor );
                while ( strpos($sor, '&amp;') !== false || strpos($sor, '&quot;') !== false ) {
                    $sor = htmlspecialchars_decode( $sor );
                }
                $sor = str_replace(
                                array('"{EGYENLOSEG_JELET_ELE}',    '{EGYENLOSEG_JELET_ELE_VEGE}"',     '{EGYENLOSEG_JELET_ELE}',   '{EGYENLOSEG_JELET_ELE_VEGE}', ';="";'  ),
                                array('="',                         '"',                                '="',                       '"',                            ';;'    ),
                                $sor
                        );
                $sor = iconv($mibol, $mibe.'//TRANSLIT', strip_tags( $sor ) );
                $csvcont[] = $sor;
            }

            fputcsv($open, $csvcont, ';');

            // 1000 soronként kiíratunk
            if( $i % 1000 == 0 && $i > 0 ) {
                // Progress
                echo json_encode(array('pr' => number_format(($i / $osszes_sor) * 100, 0, '', ''))).PHP_EOL;
                
                // Felszabadítás
                ob_flush();
                flush();
            }

            $i++;
        }

        // Progress
        echo json_encode(array('pr' => '100')).PHP_EOL;

        // Felszabadítás
        ob_flush();
        flush();
    }

    csv_export_lezar($fh, $filename, 'Beszerzés szállító');
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
if ($sor['dok_db']) {
    $dok_db  = ' ('.$sor['dok_db'].')';
}
if ($sor['megj_db']) {
    $megj_db = ' ('.$sor['megj_db'].')';
}

if( isset($_GET['modosit']) || isset($_GET['tortenet']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
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
if( isset($_GET['uj']) && _jog(JOG_BESZERZ_IGENYLESEK_MEGR_OLVAS) ) {
    
    // Új felvitel itt nincs is, automatikusan jön létre

// Módosítás gombok
} else {

    $gombok = '';

    // Jogosutságkezelés
    if ( $sor['stat_id'] == ESZKOZIGENYL_MEGR_STAT_KIKULD_VAR && _jog(JOG_BESZERZ_IGENYLESEK_MEGR_KIKULD)) {
        $gombok .= '<input type="button" class="gomb modositaskor_rejt" onclick="megrendelo_kikuldese()" value="Megrendelő kiküldése"> ';
    }
    if( _jog(JOG_BESZERZ_IGENYLESEK_MEGR_KIKULD) ) {
        $gombok .= '<input type="button" class="gomb modositaskor_rejt" onclick="csoportMuvelet(\'eszkozigenyles_megrendeles_hatarido\');" value="Szállítási határidő hozzáadása"> ';
    }

    // Megrendelés javítása
    if( ($sor['stat_id'] == ESZKOZIGENYL_MEGR_STAT_MEGRENDELVE || $sor['stat_id'] == ESZKOZIGENYL_MEGR_STAT_KIKULD_VAR ) && _jog(JOG_BESZERZ_IGENYLESEK_KEZEL) ) {
        $gombok .= nl2('
            <input type="button" class="gomb modositaskor_rejt" onclick="csoportMuvelet(\'eszkozigenyles_megrendeles_javitasa\');" value="Megrendelés javítása">
            '
        );
    }
    $gombok .= '<input type="hidden" value="'.$sor['rend_szam'].'" id="rend_szam_db">';

    // Szerkeszteni nem lehet, csak kiküldeni
    // if( _jog(JOG_BESZERZ_IGENYLESEK_MEGR_KIKULD) ){
    //     $gombok = nl2('
    //         <div class="form_kuldes">
    //             <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
    //             <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
    //         </div>
    //     ').$gombok;
    // }

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

// Új termék felvitele
if(isset($_GET['uj']) && !$csak_szerkesztheto){
    
    // Új felvitel nincs, automatikusan jön létre

// Szállító módosítása
} elseif (isset($_GET['modosit'])) {
    
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
        $json_sor[$kulcs] = formAdatok_replace($elem);
    }
    $szamla_html .= '<script>formAdatok = JSON.parse(\''.json_encode($json_sor).'\');</script>';
    
    // Duplákáláskor feltett kérdés
    $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', "`id`='{$sor['telephely_id']}'", '', '1');
    $telephely = $telephely[0]['nev'];
    $szamla_html .= nl2('
        <script>
            duplikalGomb  = "<p class=\"c\"><input class=\"gomb zold\" type=\"button\" value=\"Igen\" onclick=\"szurkit(); location.href = \''.basename(self()).'?uj&amp;ujkent='.$_GET['id'].'\'; \"><input class=\"gomb piros\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
            duplikaKerdes = \'Név: '.addslashes($sor['nev']).'<br><br>Biztos, hogy duplikálja?\';
        </script>
    ');

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

    if( $_GET['ajax'] ) {
        echo json_encode(array(
            'tartalom' => $szamla_html,
            'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
        ));
    } else {
        echo $szamla_html;
    }

    if( $tobb_kep ) {
        require_once('tobb_kep.php');
    }

// Történet
}elseif( isset($_GET['tortenet']) ) {

    $szamla_html .= get_tortenet($megnevezes);

    // A léptetés csak akkor fog működni, ha a bal navpanel kódja benne van a kódban
    // FONTOS, hogy nem lehet "dn", mert látszania kell, hogy a JavaScript tudjon vele dolgozni. A létetéshez kell.
    $szamla_html .= '<div class="vh" style="height: 0; overflow: hidden;">'.$balNavPanelHTMLje.'</div>';

    if( $_GET['ajax'] ) {
        echo json_encode(array(
            'tartalom' => $szamla_html.$gombok
        ));
    } else {
        echo $szamla_html.$gombok;
    }

// Dokumentumok fül
} elseif( isset($_GET['dokumentumok']) ) {

    if( $tobb_kep ) {
        require_once('tobb_kep.php');
    }

    if( $_GET['ajax'] ) {
        echo json_encode(array(
            'tartalom'  => $szamla_html.$gombok,
            'link'      => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
        ));
    } else {
        echo $szamla_html.$gombok;
    }

// Megjegyzések
} elseif (isset($_GET['megjegyzesek'])) {

    $szamla_html .= megj_kiir($megnevezes, $_GET['id']);

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

// Megjegyzések kiíratása
function megj_kiir($tabla, $id, $rendszer_is = 0) {
    global $adatbazis_kapcsolat;

        $sql_kieg = " AND `user`='1'";
        $rendszer_checked = '';
        $rendszer_param   = 'rendszer';
        if( $rendszer_is ) {
            $rendszer_checked = ' checked';
            $rendszer_param   = '';
            $sql_kieg = '';
        }

    // <div class="dt naplo" class nevekből a kijCont kivéve, hogy a megjegyzéseket ne lehessen törölni
    $html = nl2('
        <div class="dt naplo" data-tabla="megjegyzes" data-mellek="megjegyzesek" id="megj_tartalma">
            <div class="dtr nFejlec">
                <div class="dtc">
                    Megjegyzés
                    <label style="margin-left: 2rem">
                        <input type="checkbox"'.$rendszer_checked.' onclick="megj_kiir(\''.$rendszer_param.'\')">
                        Rendszerüzenetek megjelenítése
                    <label>
                </div>
                <div class="dtc">Hozzáadta</div>
                <div class="dtc">Hozzáadva</div>
            </div>
    ');

    if( $id ) {

        $sorok = $adatbazis_kapcsolat->s('recid megj user insert_usr insert_dt', 'megjegyzes', "`id`='{$id}' AND `tabla`='{$tabla}'{$sql_kieg}", 'recid desc');
        if( $sorok ) {
            $i = 1;
            foreach ( $sorok as $sor ) {
                $user_kieg = '';
                if( !$sor['user'] ) {
                    $user_kieg .= 'ontEAM<br>';
                }
                $html .= nl2('
                    <div id="naploSor-'.$sor['recid'].'" data-id="'.$sor['recid'].'" data-i="'.$i.'" class="dtr nSor kijSor">
                        <div class="dtc">'.htmlspecialchars_decode( nl2br2($sor['megj']) ).'</div>
                        <div class="dtc">'.$user_kieg.$sor['insert_usr'].'</div>
                        <div class="dtc">'.datum_kiir($sor['insert_dt']).'</div>
                    </div>
                ');

                $i++;
            }
        }

    }
    
    return $html;
}