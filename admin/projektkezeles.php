<?php
require_once('../weblap.php');

// Ha egyik megtekintés joggal sem rendelkezünk
if( !_jog(JOG_PRKEZEL_MEGTEKINT) && !_jog(JOG_PRKEZEL_SAJAT_MEGTEKINT) && !_jog(JOG_PRKEZEL_TEAM_TAG) && !_jog(JOG_PR_EGYSZALL_MEGTEKINT) && !_jog(JOG_PR_TOBBSZALL_MEGTEKINT) && !_jog(JOG_PR_B_MEGTEKINT) && !_jog(JOG_PR_F_MEGTEKINT) && !_jog(JOG_PR_T_MEGTEKINT) ) {
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
$akt_tomb               = $projektkezeles_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'projektet';   // Hibaüzenetkor: Biztosan törlöd a $szoveg?

$szamla_html = '';

// Dokumentumok fülhöz kell majd
$tobb_kep = true;
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

// Léptetés Előkészítés alatt státuszba
if (isset($_POST['projekt_elokeszites_ala'])) {
    
    $recid = $_POST['recid'];
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('stat_id', 'projektkezeles', "`recid`='{$recid}'");
    if( $sql === false ) { $db_error = true; }
    
    // Státusz ellenőrzése
    if( $sor['stat_id'] != PRKEZEL_STAT_NYITOTT ){
        echo json_encode(array(
            'hiba'  => 'Csak Nyitott státuszú projekt helyezhető előkészítés alá!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if( !get_projekt_kezeles_jog($recid) ){
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga státuszt léptetni!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }
    
    // Van-e már csatolt dokumentum
    $van_dok = $adatbazis_kapcsolat->s2('recid', 'projektkezeles_dok', "`projektkezeles_id`='{$recid}'", '', '1');
    if( $van_dok === false ) { $db_error = true; }
    if(!$van_dok){
        echo json_encode(array(
            'hiba'  => 'Előbb csatoljon egy dokumentumot a projekthez!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }
    
    // Státusz léptetés
    if( addStatusz( 'projektkezeles_statusz', $recid, PRKEZEL_STAT_ELOKESZITES_ALATT ) === false ) {
        $db_error = true;
    }
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'siker',
            'recid'   => $recid
        ));
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba',
        ));
    }

    $weblap->leallit();
    exit();
}

// Léptetés Folyamatban státuszba
if (isset($_POST['projekt_folyamatbanra'])) {
    
    $recid = $_POST['recid'];
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('stat_id', 'projektkezeles', "`recid`='{$recid}'");
    if( $sql === false ) { $db_error = true; }
    
    // Státusz ellenőrzése
    if( $sor['stat_id'] != PRKEZEL_STAT_ELOKESZITES_ALATT ){
        echo json_encode(array(
            'hiba'  => 'Csak Előkészítés alatt státuszú projekt tehető Folyamatban státuszba!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if( !get_projekt_kezeles_jog($recid) ){
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga státuszt léptetni!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }
    
    // Van-e már csatolt szerződés dokumentum
    $van_dok = $adatbazis_kapcsolat->s2('recid', 'projektkezeles_dok', "`projektkezeles_id`='{$recid}' AND `tipus`='".EL_DOK_TIPUS_SZERZODES."'", '', '1');
    if( $van_dok === false ) { $db_error = true; }
    if(!$van_dok){
        echo json_encode(array(
            'hiba'  => 'Előbb csatoljon egy szerződést a projekthez!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }
    
    // Státusz léptetés
    if( addStatusz( 'projektkezeles_statusz', $recid, PRKEZEL_STAT_FOLYAMATBAN ) === false ) {
        $db_error = true;
    }
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'siker',
            'recid'   => $recid
        ));
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba',
        ));
    }

    $weblap->leallit();
    exit();
}

// Léptetés Kész státuszba
if (isset($_POST['projekt_keszre'])) {
    
    $recid = $_POST['recid'];
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('stat_id', 'projektkezeles', "`recid`='{$recid}'");
    if( $sql === false ) { $db_error = true; }
    
    // Státusz ellenőrzése
    if( $sor['stat_id'] != PRKEZEL_STAT_FOLYAMATBAN ){
        echo json_encode(array(
            'hiba'  => 'Csak Folyamatban státuszú projekt tehető Kész státuszba!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if( !get_projekt_kezeles_jog($recid) ){
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga státuszt léptetni!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }
    
    // Van-e már csatolt Átadási Jk. dokumentum
    $van_dok = $adatbazis_kapcsolat->s2('recid', 'projektkezeles_dok', "`projektkezeles_id`='{$recid}' AND `tipus`='".EL_DOK_TIPUS_ATADASI."'", '', '1');
    if( $van_dok === false ) { $db_error = true; }
    if(!$van_dok){
        echo json_encode(array(
            'hiba'  => 'Előbb csatoljon egy Átadási. jk. Dokumentumot a projekthez!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }
    
    // Státusz léptetés
    if( addStatusz( 'projektkezeles_statusz', $recid, PRKEZEL_STAT_KESZ ) === false ) {
        $db_error = true;
    }
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'siker',
            'recid'   => $recid
        ));
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba',
        ));
    }

    $weblap->leallit();
    exit();
}

// Léptetés Lezárt státuszba
if (isset($_POST['projekt_lezartra'])) {
    
    $recid = $_POST['recid'];
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('stat_id', 'projektkezeles', "`recid`='{$recid}'");
    if( $sql === false ) { $db_error = true; }
    
    // Státusz ellenőrzése
    if( $sor['stat_id'] != PRKEZEL_STAT_KESZ ){
        echo json_encode(array(
            'hiba'  => 'Csak Kész státuszú projekt tehető Lezárt státuszba!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if( !get_projekt_kezeles_jog($recid) ){
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga státuszt léptetni!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }
    
    // Van-e már csatolt Gar. bejárási jk. dokumentum
    $van_dok = $adatbazis_kapcsolat->s2('recid', 'projektkezeles_dok', "`projektkezeles_id`='{$recid}' AND `tipus`='".EL_DOK_TIPUS_GAR_BEJAR."'", '', '1');
    if( $van_dok === false ) { $db_error = true; }
    if(!$van_dok){
        echo json_encode(array(
            'hiba'  => 'Előbb csatoljon egy Gar. bejárási jk. Dokumentumot a projekthez!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }
    
    // Státusz léptetés
    if( addStatusz( 'projektkezeles_statusz', $recid, PRKEZEL_STAT_LEZART ) === false ) {
        $db_error = true;
    }
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'siker',
            'recid'   => $recid
        ));
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba',
        ));
    }

    $weblap->leallit();
    exit();
}

// Sikeres fájlfetöltés után rákérdezünk, hogy léptessük-e tovább
if (isset($_POST['projekt_fajfeltoltes_utan_tovabb'])) {
    
    $recid = $_POST['recid'];
    
    $sor = $adatbazis_kapcsolat->s2('ikt_szam stat_id', 'projektkezeles', "`recid`='{$recid}'");
    
    // Jog ellenőrzése - ha nincs, akkor meg se kérdezzük
    if( !get_projekt_kezeles_jog($recid) ){

        $weblap->leallit();
        exit();
    }
    
    // Nyitott státusz esetén megkérdezzük, ekkor előkészítés alatt státuszba léptetünk
    if( $sor['stat_id'] == PRKEZEL_STAT_NYITOTT ){
        echo json_encode(array(
            'hiba'  => $sor['ikt_szam'].'<br>Dokumentum csatolása sikerült.<br>Előkészítés alatt státuszba helyezi most a projektet?',
            'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="projekt_elokeszites_ala()"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">',
            'recid' => $recid
        ));
    }
    
    // Előkészítés alatt státusz esetén Folyamatbanra léptetjük tovább HA van már szerződéses dokumentuma
    if( $sor['stat_id'] == PRKEZEL_STAT_ELOKESZITES_ALATT ){
        $van_dok = $adatbazis_kapcsolat->s2('tipus', 'projektkezeles_dok', "`projektkezeles_id`='{$recid}'", 'recid desc', '1');
        if( $van_dok['tipus'] == EL_DOK_TIPUS_SZERZODES ){
            echo json_encode(array(
                'hiba'  => $sor['ikt_szam'].'<br>Dokumentum csatolása sikerült.<br>Folyamatban státuszba helyezi most a projektet?',
                'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="projekt_folyamatbanra()"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">',
                'recid' => $recid
            ));
        }
    }
    
    // Folyamatban státusz esetén Készre léptetjük tovább HA van már Átadási Jk. dokumentuma
    if( $sor['stat_id'] == PRKEZEL_STAT_FOLYAMATBAN ){
        $van_dok = $adatbazis_kapcsolat->s2('tipus', 'projektkezeles_dok', "`projektkezeles_id`='{$recid}'", 'recid desc', '1');
        if( $van_dok['tipus'] == EL_DOK_TIPUS_ATADASI ){
            echo json_encode(array(
                'hiba'  => $sor['ikt_szam'].'<br>Dokumentum csatolása sikerült.<br>Kész státuszba helyezi most a projektet?',
                'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="projekt_keszre()"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">',
                'recid' => $recid
            ));
        }
    }
    
    // Kész státusz esetén Lezártra léptetjük tovább HA van már Gar. bejárási jk. dokumentuma
    if( $sor['stat_id'] == PRKEZEL_STAT_KESZ ){
        $van_dok= $adatbazis_kapcsolat->s2('tipus', 'projektkezeles_dok', "`projektkezeles_id`='{$recid}'", 'recid desc', '1');
        if( $van_dok['tipus'] == EL_DOK_TIPUS_GAR_BEJAR ){
            echo json_encode(array(
                'hiba'  => $sor['ikt_szam'].'<br>Dokumentum csatolása sikerült.<br>Lezárt státuszba helyezi most a projektet?',
                'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="projekt_lezartra()"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">',
                'recid' => $recid
            ));
        }
    }

    $weblap->leallit();
    exit();
}

// Projekt adatának lekérdezése popup felirathoz
if (isset($_POST['get_projektkezeles'])) {
    
    $sor = $adatbazis_kapcsolat->s2('ikt_szam telephely_id nev', 'projektkezeles', "`recid`='{$_POST['recid']}'");
    echo 'Azonosító: '.$sor['ikt_szam'].'<br>Szálloda: '.getSzallodaNev($sor['telephely_id']).'<br>Név: '.$sor['nev'].'<br><br>';
    
    $weblap->leallit();
    exit();
}

// Projekt felfüggesztése
if (isset($_POST['projekt_felfuggeszt'])) {
    
    $recid = $_POST['recid'];
    $indok = trim($_POST['indok']);
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('stat_id', 'projektkezeles', "`recid`='{$recid}'");
    if( $sql === false ) { $db_error = true; }
    
    // Státusz ellenőrzése
    $statuszok = array(PRKEZEL_STAT_NYITOTT, PRKEZEL_STAT_ELOKESZITES_ALATT);
    if( !in_array($sor['stat_id'], $statuszok) ){
        echo json_encode(array(
            'hiba'  => 'Csak Nyitott és Előkészítés alatt státuszú projekt függeszthető fel!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if( !_jog( JOG_PRKEZEL_FELFUGGESZTES ) ){
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga státuszt felfüggeszteni!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }
    
    // Indoklás ellenőrzése
    if(!$indok){
        echo json_encode(array(
            'hiba'  => 'Kérem adja meg a felfüggesztés indoklását!',
            'title' => 'Hiba',
            'focus' => 'felfuggeszt_indok'
        ));

        $weblap->leallit();
        exit();
    }else{
        $indok = 'Felfüggesztés indoklás: '.$indok;
    }
    
    // Státusz léptetés
    if( addStatusz( 'projektkezeles_statusz', $recid, PRKEZEL_STAT_FELFUGGESZTETT, 0, $indok ) === false ) {
        $db_error = true;
    }
    // Az indoklást a megjegyzésbe is beírjuk, nem csak a státuszsorba
    if(addMegj('projektkezeles', $recid, $indok)===false){
        $db_error = true;
    }
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'siker',
            'recid'   => $recid
        ));
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba',
        ));
    }

    $weblap->leallit();
    exit();
}

// Projekt felfüggesztése vége
if (isset($_POST['projekt_felfuggesztes_vege'])) {
    
    $recid = $_POST['recid'];
    $indok = trim($_POST['indok']);
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('stat_id', 'projektkezeles', "`recid`='{$recid}'");
    if( $sql === false ) { $db_error = true; }
    
    // Státusz ellenőrzése
    $statuszok = array(PRKEZEL_STAT_FELFUGGESZTETT);
    if( !in_array($sor['stat_id'], $statuszok) ){
        echo json_encode(array(
            'hiba'  => 'Csak Felfüggesztett státuszú projekt hozható vissza Nyitott státuszba!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if( !_jog( JOG_PRKEZEL_FELFUGGESZTES ) ){
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga projektet újra megnyitni!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }
    
    // Státusz léptetés
    if( addStatusz( 'projektkezeles_statusz', $recid, PRKEZEL_STAT_NYITOTT ) === false ) {
        $db_error = true;
    }
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'siker',
            'recid'   => $recid
        ));
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba',
        ));
    }

    $weblap->leallit();
    exit();
}

// Projekt leállítása
if (isset($_POST['projekt_leallit'])) {
    
    $recid = $_POST['recid'];
    $indok = trim($_POST['indok']);
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('stat_id', 'projektkezeles', "`recid`='{$recid}'");
    if( $sql === false ) { $db_error = true; }
    
    // Státusz ellenőrzése
    $statuszok = array(PRKEZEL_STAT_NYITOTT, PRKEZEL_STAT_ELOKESZITES_ALATT, PRKEZEL_STAT_FOLYAMATBAN);
    if( !in_array($sor['stat_id'], $statuszok) ) {
        echo json_encode(array(
            'hiba'  => 'Csak Nyitott, Előkészítés alatt és Folyamatban státuszú projekt állítható le!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if( !_jog( JOG_PRKEZEL_LEALLITAS ) ){
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga státuszt felfüggeszteni!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }
    
    // Indoklás ellenőrzése
    if(!$indok){
        echo json_encode(array(
            'hiba'  => 'Kérem adja meg a leállítás indoklását!',
            'title' => 'Hiba',
            'focus' => 'leallit_indok'
        ));

        $weblap->leallit();
        exit();
    }else{
        $indok = 'Leállítás indoklása: '.$indok;
    }
    
    // Státusz léptetés
    if( addStatusz( 'projektkezeles_statusz', $recid, PRKEZEL_STAT_LEZART, 0, $indok ) === false ) {
        $db_error = true;
    }
    // Az indoklást a megjegyzésbe is beírjuk, nem csak a státuszsorba
    if(addMegj('projektkezeles', $recid, $indok)===false){
        $db_error = true;
    }
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'siker',
            'recid'   => $recid
        ));
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba',
        ));
    }

    $weblap->leallit();
    exit();
}

// Projekt team hozzáadása
if (isset($_POST['addProjektTeam'])) {
    
    if($_POST['prkezel_recid']){
        
        $adatbazis_kapcsolat -> d('projektkezeles_login', "`projektkezeles_recid` = '".$_POST['prkezel_recid']."'");

        // Hozzáadjuk a Loginokat
        $loginok = json_decode($_POST['loginok'], true);
        foreach($loginok as $key => $login) {
            $kapcsID = $adatbazis_kapcsolat -> i('projektkezeles_login',
                                                    array('projektkezeles_recid',  'login_recid',   'insert_dt',    'insert_usr' ,                          'update_usr'                            ),
                                                    array($_POST['prkezel_recid'], $login,          'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'], $_SESSION[get_db_tabla().'teljes_nev']  )
                                                );
        }
    }
    
    echo json_encode(array(
        'statusz' => 'siker'
    ));
    
    $weblap -> leallit();
    exit;
}

// Igénylések táblázat betöltése
if(isset($_POST['igenylesek_betolt_uj_kezeleshez'])){

    // Ha nincs új feltöltés jogunk, akkor kilépünk
    if( !_jog(JOG_PRKEZEL_UJ_LETREHOZ) ){
        echo json_encode( array(
                            'hiba'  => 'Önnek nincs joga új projektet létrehozni!',
                            'title' => 'Hiba',
                        ) );
        $weblap -> leallit();
        exit;
    }
    
    $html = '';

    $postfix         = '_fb';
    $fb_bod_statusz  = PRIGENY_STAT_FB_TERJESZT_VAR;
    $nyitott_statusz = ELOT_FB_STAT_NYITOTT;
    $nyitott_is_sql  = ', \''.PRIGENY_STAT_NYITOTT.'\''; // FB csatolásnál a nyitottakat is be lehet csatolni, de BoD-nálm már nem
    if($_POST['mod']=='bod'){
        $postfix         = '_bod';
        $fb_bod_statusz  = PRIGENY_STAT_BOD_TERJESZT_VAR;
        $nyitott_statusz = ELOT_BOD_STAT_NYITOTT;
        $nyitott_is_sql  = '';
    }

    // Rendezés beállítása
    $join  = ' JOIN `'.get_db_tabla().'telephely` ON `'.get_db_tabla().'projektigenyles`.`telephely_id`=`'.get_db_tabla().'telephely`.`id`';
    switch($_POST['order']){
        case 'bkolt': $order = '`'.get_db_tabla().'projektigenyles`.`bkolt`, `'.get_db_tabla().'telephely`.`nev`';          $bkolt_selected = ' selected';  break;
        case 'tipus': $order = '`'.get_db_tabla().'projektigenyles`.`tipus`, `'.get_db_tabla().'projektigenyles`.`bkolt`';  $tipus_selected = ' selected';  break;
        default     : $order = '`'.get_db_tabla().'telephely`.`nev`,         `'.get_db_tabla().'projektigenyles`.`bkolt`';                                  break;
    }

    // Nem szerepelnek másik nyitott vagy véglegesítve státuszú FB/BoD előterjesztésben
    $where_kieg = '';
    $fb_kizart_recidk  = $adatbazis_kapcsolat->s('projektigenyles_recid', 'projektigenyles_eloterj', "`eloterj_fb_recid` IN (SELECT `recid` FROM `".get_db_tabla()."eloterj_fb` WHERE `stat_id` IN ('".ELOT_FB_STAT_NYITOTT."', '".ELOT_FB_STAT_VEGLEGESITVE."'))");
    $fb_kizart_recidk  = adatbazis2tomb($fb_kizart_recidk, 'projektigenyles_recid');
    $bod_kizart_recidk = $adatbazis_kapcsolat->s('projektigenyles_recid', 'projektigenyles_eloterj', "`eloterj_bod_recid` IN (SELECT `recid` FROM `".get_db_tabla()."eloterj_bod` WHERE `stat_id` IN ('".ELOT_BOD_STAT_NYITOTT."', '".ELOT_BOD_STAT_VEGLEGESITVE."'))");
    $bod_kizart_recidk = adatbazis2tomb($bod_kizart_recidk, 'projektigenyles_recid');
    $kizart_recidk = array_merge($fb_kizart_recidk, $bod_kizart_recidk);

    if($kizart_recidk){
        $where_kieg .= ' AND `'.get_db_tabla().'projektigenyles`.`recid` NOT IN ('.implode(',', $kizart_recidk).')';
    }

    $szallodak = get_akt_login_szallodai();
    $priek = $adatbazis_kapcsolat->s('
                                        `'.get_db_tabla().'projektigenyles`.`recid`,
                                        `'.get_db_tabla().'projektigenyles`.`nev`,
                                        `'.get_db_tabla().'projektigenyles`.`tipus`,
                                        `'.get_db_tabla().'projektigenyles`.`stat_id`,
                                        `'.get_db_tabla().'projektigenyles`.`telephely_id`,
                                        `'.get_db_tabla().'projektigenyles`.`jkolt`,
                                        `'.get_db_tabla().'telephely`.`nev` as `t_nev`',
                                        'projektigenyles',
                                        "`stat_id`='".PRIGENY_STAT_JOVAHAGYVA."' AND `telephely_id` IN (".implode(',', $szallodak).")$where_kieg", $order, '', '', '', $join);
// echo $adatbazis_kapcsolat->gp() . '<br>';
    $i = 1;
    foreach($priek as $pri){

        // Szálloda
        $szalloda = $adatbazis_kapcsolat->s2('nev', 'telephely', "`id`='{$pri['telephely_id']}'");

        // Létrehozva dátum = első nyitott státusz
        $letrehozva = $adatbazis_kapcsolat->s2('insert_dt', 'projektigenyles_statusz', "`projektigenyles_recid`='{$pri['recid']}' AND `stat_id`='".PRIGENY_STAT_NYITOTT."'", 'insert_dt', '1');

        // Van-e olyan projekt, ami erre az igénylésre mutat
        $projektben = '';
        if($adatbazis_kapcsolat->s('recid', 'projektkezeles', "`projektigenyles_recid`='{$pri['recid']}'", '', '1')){
            $projektben .= '<i class="material-icons">done</i>';
        }
        
        // Tipus
        require_once 'selectek.php';
        foreach($select_pri_tipus as $tip){
            if($tip['value']==$pri['tipus']){
                $tipus = $tip['name'];
            }
        }
        if(!$pri['tipus']){
            $tipus = '-';
        }

        // BoD elé terjesztettek
        $kiemelt = '';
        if($pri['stat_id']==$fb_bod_statusz){
            $kiemelt = ' kiemelt';
        }

        $html .= '
            <tr class="kijSor'.$kiemelt.'" data-i="'.$i.'" data-recid="'.$pri['recid'].'">
                <td>'.$szalloda['nev'].'</td>
                <td style="width: 30rem">'.$pri['nev'].'</td>
                <td>'.$tipus.'</td>
                <td class="p r nowrap">'.number_format($pri['jkolt'], 0, '', ' ').'</td>
                <td class="p">'.datum_kiir($letrehozva['insert_dt']).'</td>
                <td>'.$projektben.'</td>
            </tr>
            ';
        $i++;
    }

    if($html){

        // Táblázat rendezhető: szálloda + becsült költség, becsült költség + szálloda, típus + becsült költség. Ezek legyenek egy lenyílóból kiválaszthatóak.
        $html = '
            Rendezés:
            <select onchange="igenylesek_betolt_uj_kezeleshez(this.value)">
                <option>szálloda + becsült költség</option>
                <option value="bkolt"'.$bkolt_selected.'>jóváhagyott költség + szálloda</option>
                <option value="tipus"'.$tipus_selected.'>típus + becsült költség</option>
            </select>
            <div style="max-height: 40rem; overflow-y: auto; overflow-x: none;">
                <table class="csikos v2 kijCont csak_egy_kijelolheto" id="prigenylesek" style="cursor: default;">
                <thead>
                <tr>
                    <th>
                        Szálloda
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prigenylesek tbody\'), this.value, 1)">
                    </th>
                    <th>
                        Név
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prigenylesek tbody\'), this.value, 2)">
                    </th>
                    <th>
                        Típus
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prigenylesek tbody\'), this.value, 3)">
                    </th>
                    <th>
                        Jóváhagyott költség ('.get_penznem().')
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prigenylesek tbody\'), this.value, 4)">
                    </th>
                    <th>
                        Létrehozás dátuma
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prigenylesek tbody\'), this.value, 5)">
                    </th>
                    <th>
                        Projektben
                        <br>
                        <select id="projektben" onchange="projektben_szures()" style="width: 6.5rem">
                            <option>-Mind-</option>
                            <option value="1">Projektben</option>
                            <option value="0">Nincs projektben</option>
                        </select>
                    </th>
                </tr>
                </thead>
                <tbody>
                    '.$html.'
                </tbody>
                </table>
            </div>
            <script>
            ';
    }else{
        $html = '<h1>Nem található csatolható projektigénylés!</h1><script>$(\'#eloterj_igneyles_csatolForm div.kuldes\').html(\'<input type="button" class="gomb zold" value="Ok" onclick="popupZar();">\');  $(\'#eloterj_igneyles_csatolCim\').html(\'Hiba\');</script>';
    }

    echo json_encode(
            array( 'html' => nl2($html) )
            );

    $weblap->leallit();
    exit();
}

// Login lista betöltése új Projekt team feltöltésekor
if (isset($_POST['get_login_lista'])) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Lezárt státuszbannem adható hozzá tag
    $sor = $adatbazis_kapcsolat->s2('stat_id', 'projektkezeles', "`recid`='{$_POST['recid']}'");
    if( $sql === false ) { $db_error = true; }
    if( $sor['stat_id'] == PRKEZEL_STAT_LEZART ){
        echo json_encode(array(
            'hiba'  => 'Lezárt státuszban nem adható hozzá team tag!',
            'title' => 'Hiba'
        ));
        $weblap->leallit();
        exit();
    }
    
    $sql = $loginok = $adatbazis_kapcsolat->s('recid nev teljes_nev reszleg_id beosztas_id szemelyzet_id usertel usermail', 'login', "`aktiv` > '0' AND `szemelyzet_id`!=0", 'teljes_nev');
    if( $sql === false ) { $db_error = true; }
 
    $html = nl2('
        <div id="popup_loginok" class="mobilkeret" style="border: 0">
            <table class="csikos egyErtek sorLink">
                <thead>
                    <tr>
                        <th>
                            Név 
                            <input type="text" class="flat" onkeyup="tabla_szur( $(\'#popup_loginok table tbody\'), this.value );" id="popup_login_kereses">
                        </th>
                        <th>Részleg</th>
                        <th>Beosztás</th>
                        <th>Szerepkör</th>
                    </tr>
                </thead>
                <tbody>
    ');

    foreach($loginok as $login) {
        $html .= nl2('
            <tr class="kijSor" data-nev="'.$login['nev'].'" onclick="pop_mezok_kitolt($(this))">
                <td class="nev">'.$login['teljes_nev'].'</td>
                <td class="reszleg">'.erteklista(ERTEKLISTA_RESZLEG, $login['reszleg_id']).'</td>
                <td class="beosztas">'.erteklista(ERTEKLISTA_BEOSZTAS, $login['beosztas_id']).'</td>
                <td class="szemelyzet">
                    '.getSzerepkorNev($login['szemelyzet_id']).'
                    <input type="hidden" class="telefon" value="'.$login['usertel'].'">
                    <input type="hidden" class="email" value="'.$login['usermail'].'">
                    <input type="hidden" class="recid" value="'.$login['recid'].'">
                </td>
            </tr>
        ');
    }

    $html .= nl2('
            </tbody>
            </table>
        </div>
        ');

    
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);

        echo json_encode(array(
            'html' => $html
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

// Új projekt team rögzítése
if (isset($_POST['projekt_team_felvesz'])) {
    
    $recid          = $_POST['recid'];
    $nev            = trim($_POST['nev']);
    $ceg            = trim($_POST['ceg']);
    $beosztas       = trim($_POST['beosztas']);
    $usertel        = trim($_POST['usertel']);
    $usermail       = trim($_POST['usermail']);
    $szerepkor      = trim($_POST['szerepkor']);
    $login_recid    = trim($_POST['login_recid']);
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Projekt recid-ja ismert-e?
    if( !$recid ){
        echo json_encode( array(
                'hiba'  => 'Ismeretlen hiba történt!',
                'title' => 'Hiba',
            ) );
        $weblap->leallit();
        exit();
    }
    
    // Adatok ki vannak-e töltve?
    $mezok  = array('nev', 'ceg', 'beosztas', 'usertel', 'usermail', 'szerepkor');
    $ertekek = array();
    foreach($mezok as $mezo){
        $_POST[$mezo] = trim($_POST[$mezo]);
        if(!$_POST[$mezo]){
            echo json_encode( array(
                    'hiba'  => 'data-hiba',
                    'title' => 'Hiba',
                    'focus' => 'pop_'.$mezo,
                ) );
            $weblap->leallit();
            exit();
        }
        array_push($ertekek, $_POST[$mezo]);
    }
    
    // E-mail cím nem foglalt-e már
    $van_mail = $adatbazis_kapcsolat->s('recid', 'projektkezeles_team', "`projektkezeles_recid`='{$recid}' AND `usermail`='{$_POST['usermail']}'");
    if( $van_mail === false ) { $db_error = true; }
    
    if($van_mail){
        echo json_encode( array(
                'hiba'  => $_POST['nev'].' - '.$_POST['usermail'].'<br>A felhasználóhoz tartozó e-mail cím már szerepel a team címei között!',
                'title' => 'Hiba',
                'focus' => 'pop_usermail',
            ) );
        $weblap->leallit();
        exit();
    }
    
    // Inserthez még pár mezőt felveszünk
    array_push($mezok,   'projektkezeles_recid',    'login_recid',   'insert_dt', 'insert_usr',                              'update_usr'                            );
    array_push($ertekek, $recid,                    $login_recid,    'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'],    $_SESSION[get_db_tabla().'teljes_nev']  );
    
    $sql = $adatbazis_kapcsolat->i('projektkezeles_team', $mezok, $ertekek);
    if( $sql === false ) { $db_error = true; }
    
    // Ha ismert a felhasználó, akkor nála beállítjuk a Projekt team tag pipát
    if($_POST['login_recid']){
        $login = $adatbazis_kapcsolat->s2('flags', 'login', "`recid`='{$_POST['login_recid']}'");
        if( $login === false ) { $db_error = true; }
        
        $flags = str_split($login['flags']);
        
        // Ha nem volna elég hosszú a flags lista, akkor feltöltjük 0-kal
        for($i=0; $i<JOG_PRKEZEL_TEAM_TAG; $i++){
            if(!$flags[$i]){
                $flags[$i] = '0';
            }
        }
        $flags[JOG_PRKEZEL_TEAM_TAG] = 1;

        // A tömbből újra sztinget csinálunk
        $flags_uj = '';
        foreach ($flags as $f){
            $flags_uj .= $f;
        }

        $sql = $adatbazis_kapcsolat->u('login', array('flags'), array($flags_uj), "`recid`='{$_POST['login_recid']}'");
        if( $sql === false ) { $db_error = true; }
    }
    
    // Levélben értesítés
    $mail_hibaizenet = level_felkeszit_es_kuld('projektkezeles', $recid, '', 'projektkezelesUjTag', 'projektkezeles_tag_felveve', '', $login_recid);
    if( $mail_hibaizenet ){
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
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba'
        ));
    }
    
    $weblap->leallit();
    exit();
}

// CSV generálása
if (isset($_GET['csv'])) {
    
    require_once 'selectek.php';

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
    fwrite($open, "sep=;\n");
    if($open){

        if( $_SESSION['nyomtatas_ids'] ) {
            
            $lekerdezes_fromtol = substr($_SESSION['akt_sql'], strpos($_SESSION['akt_sql'], 'FROM'));
            
            if( strstr($_SESSION['akt_sql'], 'WHERE') ) {
                $lekerdezes_fromtol = str_replace('WHERE', "WHERE `".get_db_tabla().$megnevezes."`.`recid` IN (".$_SESSION['nyomtatas_ids'].") AND ", $lekerdezes_fromtol);
            } else {
                $lekerdezes_fromtol = str_replace('ORDER', "WHERE `".get_db_tabla().$megnevezes."`.`recid` IN (".$_SESSION['nyomtatas_ids'].") ORDER", $lekerdezes_fromtol);
            }

            $sql = 'SELECT
                        `'.get_db_tabla().$megnevezes.'`.`stat_id` as \'Státusz\',
                        `'.get_db_tabla().$megnevezes.'`.`ikt_szam` as \'Azonosító\',
                        `'.get_db_tabla().$megnevezes.'`.`pfelelos` as \'Projektfelelős\',
                        `'.get_db_tabla().$megnevezes.'`.`nev` as \'Név\',
                        `'.get_db_tabla().$megnevezes.'`.`telephely_id` as \'Szálloda\',
                        `'.get_db_tabla().$megnevezes.'`.`tipus` as \'Tipus\',
                        `'.get_db_tabla().$megnevezes.'`.`besorol` as \'Besorolás\',
                        `'.get_db_tabla().$megnevezes.'`.`prior` as \'Prioritás\',
                        `'.get_db_tabla().$megnevezes.'`.`megter` as \'Megtérülő\',
                        `'.get_db_tabla().$megnevezes.'`.`rendk` as \'Rendkívüli\',
                        `'.get_db_tabla().$megnevezes.'`.`text` as \'Leírása\',
                        `'.get_db_tabla().$megnevezes.'`.`jkolt` as \'Jóváhagyott keret\',
                        `'.get_db_tabla().$megnevezes.'`.`akt_adat` as \'Aktiválási adatok\'
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
                        $sor = getStatText('projektkezeles', $sor);
                    }
                    if($kulcs=='Projektfelelős'){
                        $sor = getErtek('teljes_nev', 'login', 'recid', $sor);
                    }
                    if($kulcs=='Szálloda'){
                        $sor = getSzallodaNev($sor);
                    }
                    if($kulcs=='Tipus'){
                        foreach($select_pri_tipus as $tipus){
                            if( $tipus['value'] == $sor ){
                                $sor = $tipus['name'];
                            }
                        }
                        $sor = str_replace('- Válasszon -', '', $sor);
                    }
                    if($kulcs=='Besorolás'){
                        foreach($select_pri_besorol as $besorol){
                            if( $besorol['value'] == $sor ){
                                $sor = $besorol['name'];
                            }
                        }
                        $sor = str_replace('- Válasszon -', '', $sor);
                    }
                    if($kulcs=='Prioritás'){
                        $sor = ertekLista( ERTEKLISTA_PROJEKT_PRIOR, $sor );
                    }

                    if($kulcs=='Megtérülő'){
                        if($sor){
                            $sor = 'igen';
                        }else{
                            $sor = 'nem';
                        }
                    }
                    if($kulcs=='Rendkívüli'){
                        if($sor){
                            $sor = 'igen';
                        }else{
                            $sor = 'nem';
                        }
                    }
                    $csvcont[] = iconv($mibol, $mibe.'//TRANSLIT', strip_tags( htmlspecialchars_decode( $sor ) ) );
                }

                fputcsv($open, $csvcont, ';');

                // 100 soronként kiírtatunk
                if ($i % 100 == 0) {
                    flush();
                }

                $i++;
            }
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

    if( isset($_GET['modosit']) || isset($_GET['garancialis']) || isset($_GET['tortenet']) || isset($_GET['rb_anyag']) || isset($_GET['projektigeny']) || isset($_GET['cash_flow']) || isset($_GET['projekt_team']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
        
        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'tortenet'      => 'Projekt történet',
            'projekt_team'  => 'Projekt-team',
            'dokumentumok'  => 'Dokumentumok'.$dok_db,
            'cash_flow'     => 'Cash-flow',
            'projektigeny'  => 'Projektigénylések',
            'rb_anyag'      => 'RB-anyagok',
            'garancialis'   => 'Garanciális munkák',
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
    
    // Az egy/több szálloás pipa beállítása
    $szamla_html .= nl2('
                            <script>
                                $(document).ready(function () {

                                    if( $(\'#egy_tobb\').val()>0 ){
                                        $(\'#egy_tobb_tobb\').prop(\'checked\', true)
                                    }else{
                                        $(\'#egy_tobb_egy\').prop(\'checked\', true)
                                    }

                                });
                            </script>
                        ');

    // Gombok kirajzolása minden oldalra
    $gombok = '';
    if( isset($_GET['uj']) ) {

        if( _jog(JOG_PRKEZEL_UJ_LETREHOZ) ){
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
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
    }else{
        
        // Több kérdés is azzal kezdődik, hogy kiírjuk a Projekt adatait, ezért egy változóba kiszervezzük
        $projekt_adatok = 'Azonosító: '.$sor['ikt_szam'].'<br>Szálloda: '.getSzallodaNev($sor['telephely_id']).'<br>Név: '.addslashes($sor['nev']).'<br><br>';
        
        // Törlés gomb
        if( !get_projekt_kezeles_jog($_GET['id']) ){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Önnek nincs joga a projekt törléséhez!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        // Csak nyitott projekt törölhető
        }elseif($sor['stat_id']!=PRKEZEL_STAT_NYITOTT){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Csak nyitott státuszú projekt törlhető!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        // Ha már tartozik hozzá projektigénylés, akkor sem törölhető
        }elseif($sor['projektigenyles_recid']){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'A projekthez már tartozik projektigénylés, így nem törölhető!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        }else{
            
            // Nézzük meg, hogy van-e olyan szerződés, amihez hozzá van kapcsolva. Ha van, akkor sem törölhető
            $van_szerzodese = $adatbazis_kapcsolat->s('recid', 'projektszerzodes', "`pr_szam`='{$sor['ikt_szam']}'");
            if( $van_szerzodese ){
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Már van olyan szerződés, amihez ez a projekthez hozzá van adva, így nem törölhető!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
            }else{
                $gombok .= '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
            }
            
        }
        
        // Előkészítés alatt státuszba léptetés
        $statuszok = array(PRKEZEL_STAT_NYITOTT);
        if( in_array($sor['stat_id'], $statuszok) && get_projekt_kezeles_jog($_GET['id']) ) {
            $van_dok = $adatbazis_kapcsolat->s2('recid', 'projektkezeles_dok', "`projektkezeles_id`='{$_GET['id']}'", '', '1');
            if(!$van_dok){
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Előkészítés alatt" onclick="alert2(\'Előbb csatoljon egy dokumentumot a projekthez!\', \'\', \'Hiba\')"> ';
            }else{
                $gombok .= '<script> gomb_elokeszites = \'<input type="button" class="gomb zold" onclick="projekt_elokeszites_ala()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\';</script>';
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Előkészítés alatt" onclick="alert2(\''.$projekt_adatok.'Biztos, hogy Előkészítés alatt státuszba helyezi a projektet?\', gomb_elokeszites)"> ';
            }
        }
        
        // Felfüggesztés
        $statuszok = array(PRKEZEL_STAT_NYITOTT, PRKEZEL_STAT_ELOKESZITES_ALATT);
        if( in_array($sor['stat_id'], $statuszok) && _jog( JOG_PRKEZEL_FELFUGGESZTES ) ) {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Felfüggesztés" onclick="csoportMuvelet(\'projektkezeles_felfuggeszt\')"> ';
        }
        // ... vége
        $statuszok = array(PRKEZEL_STAT_FELFUGGESZTETT);
        if( in_array($sor['stat_id'], $statuszok) && get_projekt_kezeles_jog($_GET['id']) ) {
            $gombok .= '<script> gomb_felfuggeszt = \'<input type="button" class="gomb zold" onclick="projekt_felfuggesztes_vege()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\';</script>';
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Felfüggesztés vége" onclick="alert2(\''.$projekt_adatok.'Biztos, hogy újra megnyitja a projektet?\', gomb_felfuggeszt)"> ';
        }
        
        // Leállítás
        $statuszok = array(PRKEZEL_STAT_NYITOTT, PRKEZEL_STAT_ELOKESZITES_ALATT, PRKEZEL_STAT_FOLYAMATBAN);
        if( in_array($sor['stat_id'], $statuszok) && _jog( JOG_PRKEZEL_LEALLITAS ) ) {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Leállítás" onclick="csoportMuvelet(\'projektkezeles_leallit\')"> ';
        }
        
        // Folyamatban
        $statuszok = array(PRKEZEL_STAT_ELOKESZITES_ALATT);
        if( in_array($sor['stat_id'], $statuszok) && get_projekt_kezeles_jog($_GET['id']) ) {
            $van_dok = $adatbazis_kapcsolat->s2('recid', 'projektkezeles_dok', "`projektkezeles_id`='{$_GET['id']}' AND `tipus`='".EL_DOK_TIPUS_SZERZODES."'", '', '1');
            if(!$van_dok){
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Folyamatban" onclick="alert2(\'Előbb csatoljon egy szerződést a projekthez!\', \'\', \'Hiba\')"> ';
            }else{
                $gombok .= '<script> gomb_folyamatbanra = \'<input type="button" class="gomb zold" onclick="projekt_folyamatbanra()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\';</script>';
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Folyamatban" onclick="alert2(\''.$projekt_adatok.'Biztos, hogy Folyamatban státuszba helyezi a projektet?\', gomb_folyamatbanra)"> ';
            }
        }
        
        // Folyamatban
        $statuszok = array(PRKEZEL_STAT_FOLYAMATBAN);
        if( in_array($sor['stat_id'], $statuszok) && get_projekt_kezeles_jog($_GET['id']) ) {
            $van_dok = $adatbazis_kapcsolat->s2('recid', 'projektkezeles_dok', "`projektkezeles_id`='{$_GET['id']}' AND `tipus`='".EL_DOK_TIPUS_ATADASI."'", '', '1');
            if(!$van_dok){
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Kész" onclick="alert2(\'Előbb csatoljon egy Átadási. jk. Dokumentumot a projekthez!\', \'\', \'Hiba\')"> ';
            }else{
                $gombok .= '<script> gomb_keszre = \'<input type="button" class="gomb zold" onclick="projekt_keszre()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\';</script>';
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Kész" onclick="alert2(\''.$projekt_adatok.'Biztos, hogy Kész státuszba helyezi a projektet?\', gomb_keszre)"> ';
            }
        }
        
        // Kész
        $statuszok = array(PRKEZEL_STAT_KESZ);
        if( in_array($sor['stat_id'], $statuszok) && get_projekt_kezeles_jog($_GET['id']) ) {
            $van_dok = $adatbazis_kapcsolat->s2('recid', 'projektkezeles_dok', "`projektkezeles_id`='{$_GET['id']}' AND `tipus`='".EL_DOK_TIPUS_GAR_BEJAR."'", '', '1');
            if(!$van_dok){
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Lezárt" onclick="alert2(\'Előbb csatoljon egy Gar. bejárási jk. Dokumentumot a projekthez!\', \'\', \'Hiba\')"> ';
            }else{
                $gombok .= '<script> gomb_lezartra = \'<input type="button" class="gomb zold" onclick="projekt_lezartra()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\';</script>';
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Lezárt" onclick="alert2(\''.$projekt_adatok.'Biztos, hogy Lezárt státuszba helyezi a projektet?\', gomb_lezartra)"> ';
            }
        }

        // Ha van egyáltalán kezelésre joga, és nincs még lezárva
        if( get_projekt_kezeles_jog($_GET['id']) && $sor['stat_id']!=PRKEZEL_STAT_LEZART ){

            if(!$nem_szerkesztheti && isset($_GET['modosit']) /*&& $sor['stat_id']==PRIGENY_STAT_LETRE_ALATT Átmenetileg végig szerkeszthető */){
                $gombok = nl2('
                    <div class="form_kuldes">
                        <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
                        <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
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
    
	// Új projektkezelés felvitele
	if(isset($_GET['uj']) && _jog(JOG_PRKEZEL_UJ_LETREHOZ) ){
        
	    // FORM
		if(isset($_GET['ujkent'])){
			foreach($akt_tomb as $ertek){
				$sor[$ertek['name']]=$_SESSION[$ertek['name']];
			}
		}
	    $js = js_kiir($akt_tomb, 'form_ell_admin()');

	    $form_sablon = fajlbol_olvas('../sablon/admin/form_uj.html', true);

        // Javascript-es ellenőrzéshez
        $tabla = str_replace('.php', '', basename(self()));

        $rend_mezok = $adatbazis_kapcsolat->s('`column_name`', '`information_schema`.columns', '`table_schema` LIKE  \''.get_db().'\' AND  `table_name` LIKE  \''.get_db_tabla().$tabla.'\'', '', '');
        $rend_mezok = adatbazis2tomb($rend_mezok, 'column_name');

        $sor = array();
        foreach($rend_mezok as $rend_mezo) {
            $sor[$rend_mezo] = '';
        }
        
        // Ha van igénylés recid-nk, akkor annak az adatait felhasználjuk
        if($_GET['igeny_id']){
            $igenyles = $adatbazis_kapcsolat->s2('*', 'projektigenyles', "`recid`='{$_GET['igeny_id']}' AND `telephely_id` IN (".implode(', ', get_akt_login_szallodai()).")");
            $sor['nev']                     = $igenyles['nev'];
            $sor['telephely_id']            = $igenyles['telephely_id'];
            $sor['tipus']                   = $igenyles['tipus'];
            $sor['besorol']                 = $igenyles['besorol'];
            $sor['prior']                   = $igenyles['prior'];
            $sor['jkolt']                   = $igenyles['jkolt'];
            $sor['rendk']                   = $igenyles['rendk'];
            $sor['megter']                  = $igenyles['megter'];
            $sor['text']                    = $igenyles['text'];
            $sor['projektigenyles_recid']   = $_GET['igeny_id'];
        }

        $json_sor = array();
        foreach($sor as $kulcs => $elem){
           $json_sor[$kulcs] = formAdatok_replace($elem);
        }
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

		// Ez elkészíti a $mezok változót
		require('switch.php');

        $form_mit     = array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{CK_EDITOR}', '{GOMBOK}', '{MUV_STYLE}'   );
        $form_mivel   = array($js,    $js_submit_elott,    self(),     $mezok,    $megnevezes,    $ckeditor,     $gombok,    $muv_style      );
	    $szamla_html .= str_replace($form_mit, $form_mivel, $form_sablon);

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

	// Projektkezelés módosítása
	} elseif(isset($_GET['modosit'])) {

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
                // Projektfelelősnél a teljes nevet írjuk ki, nem a nev oszlopot
                }elseif( $tomb['name'] == 'pfelelos' ){
                    $kulso_ertek = $adatbazis_kapcsolat->s2('recid teljes_nev', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}'", '', '1');
                    $kulso_ertek['nev'] = $kulso_ertek['teljes_nev'];
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
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

	    // FORM
	    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

		// Form actionja
		$action = self().get_get(array('errnum','kephiba','siker'));

		// Ez elkészíti a $mezok változót
		require('switch.php');
        
        // Egyik sor sincs kijelölve (mert nincs találat, pl. egy szűrés miatt), akkor a gombokat mindenképp rejtsük el
        $szamla_html .= nl2('
                <script>
                $(document).ready(function () {
                    if( !$(\'.szamla_sor.kijelolve\').length ){
                        $(\'.letrehozta_infok\').html(\'\');
                    }
                });
                </script>
                ');

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

        if($tobb_kep){
			require_once('tobb_kep.php');
		}

	// Cash flow
	}elseif(isset($_GET['cash_flow'])){

        $szamla_html .= 'Cash flow felülete';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Projekt team
	}elseif(isset($_GET['projekt_team'])){

        $team_ek = $adatbazis_kapcsolat->s('*', 'projektkezeles_team', "`projektkezeles_recid`='{$_GET['id']}'", 'recid');
        $szamla_html .= '<div id="tablazatBeszur">';
        if( $team_ek ) {

            // Fejlécek
            $szamla_html .= nl2('
                <div class="dt naplo szakter" data-tabla="projektkezeles_login" data-mellek="projekt_team">
                    <div class="dtr nFejlec">
                        <div class="dtc">Név</div>
                        <div class="dtc">Cég</div>
                        <div class="dtc">Beosztás</div>
                        <div class="dtc">Telefon</div>
                        <div class="dtc">E-mail</div>
                        <div class="dtc">Projekt szerepkör</div>
                        <div class="dtc"></div>
                    </div>
            ');

            $i = 1;

            $van_kezeles = false;
            if( get_projekt_kezeles_jog($_GET['id']) && $sor['stat_id'] != PRKEZEL_STAT_LEZART ){
                $van_kezeles = true;
                // $torles = '<script> dok_torol_gomb_141 = \'<input type="button" class="gomb zold" value="Igen" onclick="mellekTorol(\'projektkezeles\', \'projekt_team\', \'\', \'141\');"><input type="button" class="gomb piros" value="Nem" onclick="alert2(); $(\'.torles_elott\').removeClass(\'torles_elott\').removeClass(\'kijelolve\');">'; </script> <span class="icon link" onclick="$('#kep141').addClass('torles_elott'); alert2('Biztos, hogy törli a(z) wd3.jpg dokumentumot?', dok_torol_gomb_141);"><i class="material-icons" style="font-size: 2rem">delete</i></span> </div>';
            }
            
            foreach($team_ek as $team) {
                $torles = '';
                if($van_kezeles){
                    $torles = nl2('
                        <script> 
                            dok_torol_gomb_'.$team['recid'].' = \'
                                                    <input type="button" class="gomb zold" value="Igen" onclick="mellekTorol(\\\'projektkezeles_team\\\', \\\'projekt_team\\\', \\\'projekt_team\\\', \\\''.$team['recid'].'\\\');">
                                                    <input type="button" class="gomb piros" value="Nem" onclick="alert2(); $(\\\'.torles_elott\\\').removeClass(\\\'torles_elott\\\').removeClass(\\\'kijelolve\\\');">
                                                \';
                        </script>
                        <span class="icon link" onclick="$(\'#naploSor-'.$team['recid'].'\').addClass(\'torles_elott\'); alert2(\'Biztos, hogy törli a kijelölt projekt team-et?\', dok_torol_gomb_'.$team['recid'].');">
                        <i class="material-icons" style="font-size: 2rem">delete</i></span>
                        ');
                }
                $szamla_html .= nl2('
                    <div id="naploSor-'.$team['recid'].'" class="dtr nSor kijSor" data-id="'.$team['recid'].'" data-i="'.$i.'">
                        <div class="dtc">'.$team['nev'].'</div>
                        <div class="dtc">'.$team['ceg'].'</div>
                        <div class="dtc">'.$team['beosztas'].'</div>
                        <div class="dtc">'.$team['usertel'].'</div>
                        <div class="dtc">'.$team['usermail'].'</div>
                        <div class="dtc">'.erteklista(ERTEKLISTA_PROJEKT_SZEREP, $team['szerepkor']).'</div>
                        <div class="dtc">'.$torles.'</div>
                    </div>
                ');

                $i++;
            }

            $szamla_html .= '</div>';
        }

        $szamla_html .= '</div>';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Projektigény
	}elseif(isset($_GET['projektigeny'])){

        $kezeles  = $adatbazis_kapcsolat->s2('projektigenyles_recid', 'projektkezeles', "`recid`='{$_GET['id']}'");
        if($kezeles['projektigenyles_recid']){
            $igenyles = $adatbazis_kapcsolat->s2('*', 'projektigenyles', "`recid`='{$kezeles['projektigenyles_recid']}'");
            $szamla_html .= nl2('
                                <div class="dt naplo kijCont" data-tabla="munkalap_kapcs" data-mellek="munkalapok">
                                    <div class="dtr nFejlec">
                                    <div class="dtc">Azonosító</div>
                                    <div class="dtc">Szálloda</div>
                                    <div class="dtc">Név</div>
                                    <div class="dtc">Leírás</div>
                                    <div class="dtc">Létrehozva</div>
                                    <div class="dtc">Státusz</div>
                                </div>
                                <div id="naploSor-'.$igenyles['recid'].'" class="dtr nSor" data-id="'.$igenyles['recid'].'" data-i="1">
                                    <div class="dtc"><a href="projektigenyles.php?id='.$igenyles['recid'].'&amp;modosit" target="_blank">'.$igenyles['ikt_szam'].'</a></div>
                                    <div class="dtc">'.getErtek('nev', 'telephely', 'id', $igenyles['telephely_id']).'</div>
                                    <div class="dtc">'.$igenyles['nev'].'</div>
                                    <div class="dtc">'.nl2br2($igenyles['text']).'</div>
                                    <div class="dtc">'.str_replace('-', '.', $igenyles['insert_dt']).'</div>
                                    <div class="dtc">'.getStatText('projektigenyles', $igenyles['stat_id']).'</div>
                                </div>
                            ');
        }else{
            $szamla_html .= 'Ehhez a projektkezelésehez nem tartozik igénylés.';
        }
        

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Történet
	}elseif(isset($_GET['tortenet'])){

        $szamla_html .= get_tortenet($megnevezes);

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// RB-anyagok
	}elseif(isset($_GET['rb_anyag'])){

        $szamla_html .= 'RB-anyagok felülete';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Garanciális munkák
	}elseif(isset($_GET['garancialis'])){

        $szamla_html .= 'Garanciális munkák felülete';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }


	// Dokumentumok
	}elseif( isset($_GET['dokumentumok']) ) {
        
        if( $tobb_kep ) {
            require_once('tobb_kep.php');
        }

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

        // <div class="dt naplo" class nevekből a kijCont kivéve, hogy a megjegyzéseket ne lehessen törölni
        $szamla_html .= nl2('
            <div class="dt naplo" data-tabla="megjegyzes" data-mellek="megjegyzesek">
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
                    <div id="naploSor-'.$sor['recid'].'" data-id="'.$sor['recid'].'" data-i="'.$i.'" class="dtr nSor kijSor">
                        <div class="dtc">'.nl2br($sor['megj']).'</div>
                        <div class="dtc">'.$sor['insert_usr'].'</div>
                        <div class="dtc">'.datum_kiir($sor['insert_dt']).'</div>
                    </div>
                ');

                $i++;
            }
        }

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
