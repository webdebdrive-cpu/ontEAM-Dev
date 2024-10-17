<?php
require_once('../weblap.php');

//if( isset($_GET['teszt']) ) {
//    $projektek = $adatbazis_kapcsolat->s('recid telephely_id', 'projektkezeles', "`telephely_unid`=''");
//    foreach ( $projektek as $projekt ) {
//        $telephely = $adatbazis_kapcsolat->s2('unid', 'telephely', "`id`='{$projekt['telephely_id']}'");
//        $adatbazis_kapcsolat->u('projektkezeles', array('telephely_unid'), array($telephely['unid']), "`recid`='{$projekt['recid']}'");
//        echo $adatbazis_kapcsolat->gp() . '<br>';
//    }
//    echo 'f';
//    exit;
//}

// Ha egyik megtekintés joggal sem rendelkezünk
if( !_jog(JOG_PRKEZEL_MEGTEKINT) && !_jog(JOG_PRKEZEL_SAJAT_MEGTEKINT) && !_jog(JOG_PRKEZEL_TEAM_TAG) && !_jog(JOG_PR_EGYSZALL_MEGTEKINT) && !_jog(JOG_PR_TOBBSZALL_MEGTEKINT) && !_jog(JOG_PR_B_MEGTEKINT) && !_jog(JOG_PR_F_MEGTEKINT) && !_jog(JOG_PR_T_MEGTEKINT) ) {
    if( !$_SESSION[get_db_tabla() . 'user'] ) {
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
$tobb_kep = 1;
if( $tobb_kep ) {

    function get_tobbkep_darab()    { return 10;        } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '';        } // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;        } // MB-ban
    function get_tobbkep_oszlopnev(){                     // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

}


// Projektfelelős mailcím ellenőrzése
if( isset($_GET['pfelelos_mail_ell']) ) {
    
    $login = $adatbazis_kapcsolat->s2('usermail', 'login', "`recid`='{$_GET['pfelelos']}'");
    if( $login['usermail'] ) {
        echo json_encode(array(
                                 'siker'  => '1',
                         ));
    } else {
        echo json_encode(array(
                                 'hiba'  => 'A projektfelelősnek nincs e-mail címe, így az új projektről nem tudunk értesítést küldeni!',
                                 'gomb'  => '',
                                 'title' => 'Hiba',
                         ));
        
    }

    $weblap -> leallit();
    exit;
}

// Projekt dokumentum típusának beállítása 
// Projektek dokumentum fül alatt, minősítés gombra kattintva.
// Ha nem a projektkez direktbe feltöltött dokumentumról van szó, hanem egy szerződés dokumentumról
// akkor az a projektszerzodes.php alatt ugyanezen post névvel van lekezelve.
if( isset($_POST['dok_minosites_beallit']) ) {
    
    $adatbazis_kapcsolat->u('projektkezeles_dok', array('tipus'), array($_POST['tipus']), "`recid`='{$_POST['recid']}'");
    
    // Meg kell nézni, hogy mi a projekt státusza. Ha előkészítés alatt vagy folyamatban, akkor rákérdezünk, hogy tovább léptet-e státuszt
    if( $_POST['tipus'] == EL_DOK_TIPUS_SZERZODES || $_POST['tipus'] == EL_DOK_TIPUS_ATADASI ) {

        $pr_dok  = $adatbazis_kapcsolat->s2('projektkezeles_id', 'projektkezeles_dok', "`recid`='{$_POST['recid']}'");
        $projekt = $adatbazis_kapcsolat->s2('recid stat_id', 'projektkezeles', "`recid`='{$pr_dok['projektkezeles_id']}'");
        
        if( $_POST['tipus'] == EL_DOK_TIPUS_SZERZODES && $projekt['stat_id'] == PRKEZEL_STAT_ELOKESZITES_ALATT ) {
            echo json_encode( array(
                                    'alert' => 'A csatolt fájlt szerződés típusú dokumentummá minősítette.<br>Folyamatba helyezi a projektet?',
                                    'title' => 'Kérdés',
                                    'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="folyamatbanra_allit()"><input type="button" class="gomb piros" value="Nem" onclick="popupZar(); szamlaBetolt( \''.$projekt['recid'].'\', \'projektkezeles.php\', \'dokumentumok\' );">',
                                ) );
        }
        if( $_POST['tipus'] == EL_DOK_TIPUS_ATADASI && $projekt['stat_id'] == PRKEZEL_STAT_FOLYAMATBAN ) {
            echo json_encode( array(
                                    'alert' => 'A csatolt fájlt Átadási jk. típusú dokumentummá minősítette.<br>Készre helyezi a projektet?',
                                    'title' => 'Kérdés',
                                    'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="keszre_allit()"><input type="button" class="gomb piros" value="Nem" onclick="popupZar(); szamlaBetolt( \''.$projekt['recid'].'\', \'projektkezeles.php\', \'dokumentumok\' );">',
                                ) );
        }
    }
    
    // Logolás
    $pr_id = $adatbazis_kapcsolat->s2('projektkezeles_id fajlnev', 'projektkezeles_dok', "`recid`='{$_POST['recid']}'");
    addMegj('projektkezeles', $pr_id['projektkezeles_id'], $pr_id['fajlnev'].' dokumentum '. erteklista(ERTEKLISTA_DOK_TIPUS, $_POST['tipus']).' típusnak minősítve');

    // Updateljük a minősítésre váró dokumentumok számát
    projekt_minositesre_varo_dok_szamol($pr_id['projektkezeles_id']);
    
    $weblap -> leallit();
    exit;
}

// Minősítés törlése
if( isset($_POST['minosites_torol']) ) {
    
    // Jog ellenőrzése
    if( !_jog(JOG_PRKEZEL_KEZEL) ) {
        echo json_encode( array(
                                'hiba'  => 'Önnek nincs joga minősítés törléséhez!',
                                'title' => 'Hiba',
                                ) );
        $weblap -> leallit();
        exit;
    }
    
    // Minősítés törlése...
    $adatbazis_kapcsolat->u($_POST['tabla'], array('tipus'), array(0), "`recid`='{$_POST['recid']}'");
    echo json_encode( array(
                            'siker'  => true,
                            ) );

    $apa_oszlop = str_replace('_dok', '_id', $_POST['tabla']);
    $apa_tabla  = str_replace('_dok', '',    $_POST['tabla']);
    $dok = $adatbazis_kapcsolat->s2($apa_oszlop.' fajlnev', $_POST['tabla'], "`recid`='{$_POST['recid']}'");
    
    // Logolás
    addMegj($apa_tabla, $dok[$apa_oszlop], addslashes($dok['fajlnev']).' dokumentumról törölve a minősítés');

    $weblap -> leallit();
    exit;
}

// Szálloda select a Cash-flow popupba
if( isset($_POST['get_szalloda_select']) ) {
    
    $sor = $ertek = $adatbazis_kapcsolat->s2('telephely_id egy_tobb', 'projektkezeles', "`recid`='{$_POST['recid']}'");
    
    // Telephelyek lekérdezése
    $select_felh_telephely = array();
    array_push($select_felh_telephely, array('name' => '- Válasszon -', 'value' => '' ));

    $loginNev = $_SESSION[get_db_tabla().'user'];

    // Kapcsolódó tábla adatai
    $telephely_kapcs = $adatbazis_kapcsolat->s('telephely_id', 'telephely_kapcs', '`nev`=\''.$loginNev.'\'');
    if( $telephely_kapcs ) {
        $telephely_kapcs = adatbazis2tomb($telephely_kapcs, 'telephely_id');
        $where = "`id` IN (".implode(',', $telephely_kapcs).")";
    }
    $where .= " AND `inaktiv`=0";

    $telephelyek = $adatbazis_kapcsolat -> select('id recid nev modell', 'telephely', $where, 'nev');
    foreach ( $telephelyek as $telephely ) {
        array_push($select_felh_telephely, array('name' => $telephely['nev'], 'value' => $telephely['id'], 'data-modell' => $telephely['modell'] ));
    }

    $ertek['onchange'] = ($ertek['onchange'] ? $ertek['onchange'].' ' : '').'szalloda_modell( this );';

    require_once('../class/html/class.Input.php');
    require_once('../class/html/class.Select.php');
    
    // Ha egyszállodás, akkor egy kiválasztott szállodával deisabled a szálloda select
    $selected = $ertek['telephely_id'];
    if( !$ertek['egy_tobb'] ) {
        $ertek['disabled'] = true;
    }
    $ertek['name'] = 'pop_telephely_id';
    
    $input = new Select($select_felh_telephely, $selected);
    $input -> cssClass = 'csillag';
    $input -> disabled = $ertek['disabled'];
    $input -> id       = $ertek['name'];
    $input -> name     = $ertek['name'];
    $input -> onblur   = $ertek['onblur'];
    $input -> onclick  = $ertek['onclick'];
    $input -> onfocus  = $ertek['onfocus'];
    $input -> post     = $ertek['post'];
    $input -> style    = $ertek['style'];

    echo json_encode( array( 'szalloda' => $input -> getHtml() ) );
    
    $weblap->leallit();
    exit();
}

// Projekt újranyitása
if( isset($_POST['projektkezeles_ujranyit']) ) {
    $recid = $_POST['recid'];
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('stat_id', 'projektkezeles', "`recid`='{$recid}'");
    if( $sql === false ) { $db_error = true; }
    
    // Státusz ellenőrzése
    if( $sor['stat_id'] != PRKEZEL_STAT_LEZART ){
        echo json_encode(array(
            'hiba'  => 'Csak Lezárt státuszú projekt nyitható újra!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if( !_jog(JOG_PRKEZEL_UJRANYITAS) ) {
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga projektet újra nyitni!',
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

// Léptetés Előkészítés alatt státuszba
if( isset($_POST['projekt_elokeszites_ala']) ) {

    $recid = $_POST['recid'];
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('stat_id ikt_szam', 'projektkezeles', "`recid`='{$recid}'");
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
    if( !get_projekt_kezeles_jog($recid) ) {
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga státuszt léptetni!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }
    
    // Van-e már csatolt dokumentum
    $szerzodesek = $adatbazis_kapcsolat->s('recid', 'projektszerzodes', "`pr_szam`='{$sor['ikt_szam']}'");
    $szerzodesek = adatbazis2tomb($szerzodesek, 'recid');

    $van_dok = '';
    if( $szerzodesek ) {
        $van_dok = $adatbazis_kapcsolat->s2('recid', 'projektszerzodes_dok', "`projektszerzodes_id` IN (".implode(', ', $szerzodesek).")", '', '1');
        if( $van_dok === false ) { $db_error = true; }
    }
    $van_dok2 = $adatbazis_kapcsolat->s2('recid', 'projektkezeles_dok', "`projektkezeles_id`='{$recid}'");
    if( $van_dok2 === false ) { $db_error = true; }
    if( !$van_dok && !$van_dok2 ) {
        echo json_encode(array(
            'hiba'  => 'Előbb csatoljon dokumentumot a projekthez vagy a hozzá tartozó szerződéshez!',
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
if( isset($_POST['projekt_folyamatbanra']) ) {

    $recid = $_POST['recid'];
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('stat_id ikt_szam', 'projektkezeles', "`recid`='{$recid}'");
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
    if( !get_projekt_kezeles_jog($recid) ) {
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga státuszt léptetni!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }
    
    // Van-e már csatolt szerződés dokumentum
    $szerzodesek = $adatbazis_kapcsolat->s('recid', 'projektszerzodes', "`pr_szam`='{$sor['ikt_szam']}'");
    $szerzodesek = adatbazis2tomb($szerzodesek, 'recid');

    $van_dok = '';
    if( $szerzodesek ) {
        $van_dok = $adatbazis_kapcsolat->s2('recid', 'projektszerzodes_dok', "`projektszerzodes_id` IN (".implode(', ', $szerzodesek).")  AND `tipus`='".EL_DOK_TIPUS_SZERZODES."'", '', '1');
    }
    if( !$van_dok ) {
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
if( isset($_POST['projekt_keszre']) ) {

    $recid = $_POST['recid'];
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('stat_id ikt_szam', 'projektkezeles', "`recid`='{$recid}'");
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
    
    // Van-e már csatolt Átadási. jk. dokumentum vagy Teljesítésigazolás dokumentum
    $szerzodesek = $adatbazis_kapcsolat->s('recid', 'projektszerzodes', "`pr_szam`='{$sor['ikt_szam']}'");
    $szerzodesek = adatbazis2tomb($szerzodesek, 'recid');

    $van_dok = '';
    if( $szerzodesek ) {
        $van_dok = $adatbazis_kapcsolat->s2('recid', 'projektszerzodes_dok', "`projektszerzodes_id` IN (".implode(', ', $szerzodesek).")  AND (`tipus`='".EL_DOK_TIPUS_ATADASI."' OR `tipus`='".EL_DOK_TIPUS_TELJESITES."')", '', '1');
    }
    $van_dok2 = $adatbazis_kapcsolat->s2('recid', 'projektkezeles_dok', "`projektkezeles_id`='{$recid}' AND (`tipus`='".EL_DOK_TIPUS_ATADASI."' OR `tipus`='".EL_DOK_TIPUS_TELJESITES."')");
    if( $van_dok2 === false ) { $db_error = true; }
    if( !$van_dok && !$van_dok2 ) {
        echo json_encode(array(
            'hiba'  => 'Előbb minősítsen egy dokumentumot Átadási. jk. vagy Teljesítésigazolásnak!',
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
if( isset($_POST['projekt_lezartra']) ) {

    $recid = $_POST['recid'];
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('stat_id ikt_szam', 'projektkezeles', "`recid`='{$recid}'");
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
    
    // Adott Projekthez kapcsolódó szerződések jótállásainak mindegyikének van-e már feltölött dokumentuma?
    // Ha csak egy olyan jótállás is létezik, aminek még nincs dokumentuma (`utvonal`=''), akkor nem engedjük lezárni.
    $szerzodesek = $adatbazis_kapcsolat->s('recid', 'projektszerzodes', "`pr_szam`='{$sor['ikt_szam']}'");
    if( $szerzodesek === false ) { $db_error = true; }
    $szerzodesek = adatbazis2tomb($szerzodesek, 'recid');
    if( $szerzodesek ) {
        $van_hianyos_jotallas = $adatbazis_kapcsolat->s2('recid', 'projektszerzodes_jotallas', "`projektszerzodes_recid` IN (".implode(', ', $szerzodesek).") AND `utvonal`=''");
        if( $van_hianyos_jotallas === false ) { $db_error = true; }
    }
    if( $van_hianyos_jotallas ) {
        echo json_encode(array(
            'hiba'  => 'Előbb minősítsen egy dokumentumot Gar. bejárási jknek!',
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

// Léptetés Folyamatban státuszba: Projektszerződésés dokumentum minődítése után kérdezünk rá, hogy tovább szeretné-e léptetni. Ha igen, akkor itt tesszük ezt meg.
if( isset($_POST['folyamatbanra_allit']) ) {
    
    $recid = $_POST['recid'];
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Jog ellenőrzése
    if( !_jog( JOG_PRKEZEL_KEZEL ) ) {
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga státuszt léptetni!',
            'title' => 'Hiba',
            'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="popupZar(); szamlaBetolt( _get(\'id\'), \'projektkezeles.php\', \'dokumentumok\' );">',
        ));
        
        $weblap -> leallit();
        exit;
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

// Léptetés Kész státuszba: Projektszerződésés dokumentum minődítése után kérdezünk rá, hogy tovább szeretné-e léptetni. Ha igen, akkor itt tesszük ezt meg.
if( isset($_POST['keszre_allit']) ) {
    
    $recid = $_POST['recid'];
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Jog ellenőrzése
    if( !_jog( JOG_PRKEZEL_KEZEL ) ) {
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga státuszt léptetni!',
            'title' => 'Hiba',
            'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="popupZar(); szamlaBetolt( _get(\'id\'), \'projektkezeles.php\', \'dokumentumok\' );">',
        ));
        
        $weblap -> leallit();
        exit;
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

// Sikeres fájlfetöltés után rákérdezünk, hogy léptessük-e tovább
if( isset($_POST['projekt_fajfeltoltes_utan_tovabb']) ) {

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
if( isset($_POST['get_projektkezeles']) ) {

    $sor = $adatbazis_kapcsolat->s2('ikt_szam telephely_id nev jkolt', 'projektkezeles', "`recid`='{$_POST['recid']}'");
    $jkolt = '';
    if( $_POST['jkotl_is'] ) {
        $jkolt .= '<br>Jóváhagyott keret: '.number_format($sor['jkolt'], 0, '', ' ').' '.get_penznem();
    }
    echo nl2('
            Azonosító: '.$sor['ikt_szam'].'
            <br>Szálloda: '.getSzallodaNev($sor['telephely_id']).'
            <br>Név: '.$sor['nev'].'
            '.$jkolt.'
            <br>
            <br>
            ');
    
    $weblap->leallit();
    exit();
}

// Keret módosítása
if (isset($_POST['projektkezeles_keret_modosit'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    // Szóközöket vegyük ki
    $_POST['popup_jkolt'] = str_replace(' ', '', $_POST['popup_jkolt']);

    if( !_jog( JOG_PRKEZEL_KERETOSSZEG_EMEL ) ) {
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga keretet módosítani!',
            'title' => 'Hiba',
            // 'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="popupZar(); szamlaBetolt( _get(\'id\'), \'projektkezeles.php\', \'modosit\' );">',
        ));
        $weblap->leallit();
        exit();
    }

    if( !$_POST['popup_jkolt'] ) {
        echo json_encode(array(
            'hiba'  => 'Kérem adja meg a jóváhagyott keretet!',
            'title' => 'Hiba',
            'focus' => 'popup_jkolt',
            // 'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="popupZar(); szamlaBetolt( _get(\'id\'), \'projektkezeles.php\', \'modosit\' );">',
        ));
        $weblap->leallit();
        exit();
    }

    $sor = $adatbazis_kapcsolat -> s2('ikt_szam stat_id', 'projektkezeles', "`recid`='{$_POST['recid']}'");
    if( $sor === false ) { $db_error = true; }

    $statuszok = array(PRKEZEL_STAT_NYITOTT, PRKEZEL_STAT_ELOKESZITES_ALATT, PRKEZEL_STAT_FOLYAMATBAN);
    if( !in_array($sor['stat_id'], $statuszok) ) {
        echo json_encode(array(
            'hiba'  => 'Ebben a státuszban már nem lehet keretet módostani!',
            'title' => 'Hiba',
            // 'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="popupZar(); szamlaBetolt( _get(\'id\'), \'projektkezeles.php\', \'modosit\' );">',
        ));
        $weblap->leallit();
        exit();
    }

    // Számoljuk át újra a szerződött összeget és a felhasználható keretet az új keret fényében
    $szerzodesek = $adatbazis_kapcsolat->s2('SUM(`szerz_ossz`)', 'projektszerzodes', "`pr_szam`='{$sor['ikt_szam']}'");
    if( $szerzodesek === false ) { $db_error = true; }

    // Új adatok + új keret letárolása
    $sz_ossz = $szerzodesek['SUM(`szerz_ossz`)'];
    $fel_hato = $_POST['popup_jkolt']-$sz_ossz;
    $sql = $adatbazis_kapcsolat->u('projektkezeles', array('jkolt',               'sz_ossz', 'fel_hato'),
                                                     array($_POST['popup_jkolt'], $sz_ossz,  $fel_hato ), "`recid`='{$_POST['recid']}'");
    if( $sql === false ) { $db_error = true; }
    

    // Az indoklást a megjegyzésbe is beírjuk, nem csak a státuszsorba
    $megj = 'Jóváhagyott keret módosítva: '.number_format($_POST['popup_jkolt'], 0, '', '.').' '.get_penznem();
    if(addMegj('projektkezeles', $_POST['recid'], $megj)===false){
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

    $weblap -> leallit();
    exit;
}

// Projekt felfüggesztése
if( isset($_POST['projekt_felfuggeszt']) ) {

    $recid = $_POST['recid'];
    $indok = trim($_POST['indok']);
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('stat_id', 'projektkezeles', "`recid`='{$recid}'");
    if( $sql === false ) { $db_error = true; }
    
    // Státusz ellenőrzése
    $statuszok = array(PRKEZEL_STAT_NYITOTT, PRKEZEL_STAT_ELOKESZITES_ALATT, PRKEZEL_STAT_FOLYAMATBAN);
    if( !in_array($sor['stat_id'], $statuszok) ){
        echo json_encode(array(
            'hiba'  => 'Csak Nyitott, Előkészítés alatt és Folyamatban státuszú projekt függeszthető fel!',
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
    } else {
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
if( isset($_POST['projekt_felfuggesztes_vege']) ) {

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
if( isset($_POST['projekt_leallit']) ) {

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
    } else {
        $indok = 'Leállítás indoklása: '.$indok;
    }
    
    // Státusz léptetés
    if( addStatusz( 'projektkezeles_statusz', $recid, PRKEZEL_STAT_LEZART, 0, $indok ) === false ) {
        $db_error = true;
    }
    // Az indoklást a megjegyzésbe is beírjuk, nem csak a státuszsorba
    if( addMegj('projektkezeles', $recid, $indok) === false ) {
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
if( isset($_POST['addProjektTeam']) ) {

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
if( isset($_POST['igenylesek_betolt_uj_kezeleshez']) ) {

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
    foreach ( $priek as $pri ) {

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
                <td class="l">'.$szalloda['nev'].'</td>
                <td class="l">'.$pri['nev'].'</td>
                <td class="l">'.$tipus.'</td>
                <td class="p r nowrap">'.number_format($pri['jkolt'], 0, '', ' ').'</td>
                <td class="p">'.datum_kiir($letrehozva['insert_dt']).'</td>
                <td>'.$projektben.'</td>
            </tr>
            ';
        $i++;
    }

    if( $html ) {

        // Táblázat rendezhető: szálloda + becsült költség, becsült költség + szálloda, típus + becsült költség. Ezek legyenek egy lenyílóból kiválaszthatóak.
        $html = '
            Rendezés:
            <select onchange="igenylesek_betolt_uj_kezeleshez(this.value)" style="margin-bottom: 2rem;">
                <option>szálloda + becsült költség</option>
                <option value="bkolt"'.$bkolt_selected.'>jóváhagyott keret + szálloda</option>
                <option value="tipus"'.$tipus_selected.'>típus + becsült költség</option>
            </select>
            <style type="text/css">
                #popup div.popup_tart table {
                    margin-top: 7rem;
                }
                table.fix_fejlec thead tr th:nth-of-type(1), table.fix_fejlec tbody tr td:nth-of-type(1) {
                    width: 8rem;
                }
                table.fix_fejlec thead tr th:nth-of-type(2), table.fix_fejlec tbody tr td:nth-of-type(2) {
                    width: 30rem;
                }
                table.fix_fejlec thead tr th:nth-of-type(3), table.fix_fejlec tbody tr td:nth-of-type(3) {
                    width: 13rem;
                }
                table.fix_fejlec thead tr th:nth-of-type(4), table.fix_fejlec tbody tr td:nth-of-type(4) {
                    width: 10rem;
                }
                table.fix_fejlec thead tr th:nth-of-type(5), table.fix_fejlec tbody tr td:nth-of-type(5) {
                    width: 13rem;
                }
                table.fix_fejlec thead tr th:nth-of-type(6), table.fix_fejlec tbody tr td:nth-of-type(6) {
                    width: 6rem;
                }
                table.fix_fejlec tbody {
                    margin-top: 10rem;
                }
            </style>
            <div style="max-height: 40rem; overflow-y: auto; overflow-x: none;">
                <table class="csikos v2 kijCont csak_egy_kijelolheto fix_fejlec hoveres" id="prigenylesek" style="cursor: default;">
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
                        Jóváhagyott keret ('.get_penznem().')
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
    } else {
        $html = '<h1>Nem található csatolható projektigénylés!</h1><script>$(\'#eloterj_igneyles_csatolForm div.kuldes\').html(\'<input type="button" class="gomb zold" value="Ok" onclick="popupZar();">\');  $(\'#eloterj_igneyles_csatolCim\').html(\'Hiba\');</script>';
    }

    echo json_encode(
            array( 'html' => nl2($html) )
            );

    $weblap->leallit();
    exit();
}

// Login lista betöltése új Projekt team feltöltésekor
if( isset($_POST['get_login_lista']) ) {

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
if( isset($_POST['projekt_team_felvesz']) ) {

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
    if( !$recid ) {
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
    foreach ( $mezok as $mezo ) {
        $_POST[$mezo] = trim($_POST[$mezo]);
        if( !$_POST[$mezo] ) {
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
    
    if( $van_mail ) {
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
    if( $_POST['login_recid'] ) {
        $login = $adatbazis_kapcsolat->s2('flags', 'login', "`recid`='{$_POST['login_recid']}'");
        if( $login === false ) { $db_error = true; }
        
        $flags = str_split($login['flags']);
        
        // Ha nem volna elég hosszú a flags lista, akkor feltöltjük 0-kal
        for ( $i = 0; $i < JOG_PRKEZEL_TEAM_TAG; $i++ ) {
            if(!$flags[$i]){
                $flags[$i] = '0';
            }
        }
        $flags[JOG_PRKEZEL_TEAM_TAG] = 1;

        // A tömbből újra sztinget csinálunk
        $flags_uj = '';
        foreach ( $flags as $f ) {
            $flags_uj .= $f;
        }

        $sql = $adatbazis_kapcsolat->u('login', array('flags'), array($flags_uj), "`recid`='{$_POST['login_recid']}'");
        if( $sql === false ) { $db_error = true; }
    }
    
    // Levélben értesítés
    $mail_hibaizenet = level_felkeszit_es_kuld('projektkezeles', $recid, '', 'projektkezelesUjTag', 'projektkezeles_tag_felveve', '', $login_recid, $usermail);
    // Ha a levélküldéssel volt gond, akkor az adatbázis művelet attól még lefut
//    if( $mail_hibaizenet ) {
//        $db_error = true;
//    }
    
    if( !$db_error ) {
        
        mysqli_commit($db_kapcs);

        echo json_encode(array(
            'statusz'   => 'siker',
            'mail_hiba' => $mail_hibaizenet
        ));
        
    } else {

        $hiba = tranzakcioHiba();
        if( $mail_hibaizenet ) {
            $hiba = $mail_hibaizenet;
        }
        
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => $hiba,
            'title' => 'Hiba'
        ));
    }
    
    $weblap->leallit();
    exit();
}

// Cash-flow sor hozzáadása
if( isset($_POST['projektkezeles_cf_uj']) ) {

    $datumok = json_decode($_POST['datumok']);
    $tervek  = json_decode($_POST['tervek']);

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Kötelező mezők ellenőrzése
    if( !$_POST['telephely_id'] ) {
        echo json_encode(array(
            'hiba'  => 'Kérem válasszon szállodát!',
            'title' => 'Hiba',
            'focus' => 'pop_telephely_id',
        ));
        $weblap->leallit();
        exit();
    }
    if( !trim($_POST['nev']) ) {
        echo json_encode(array(
            'hiba'  => 'Kérem, adja meg a tervsor nevét!',
            'title' => 'Hiba',
            'focus' => 'popup_nev',
        ));
        $weblap->leallit();
        exit();
    }
    
    if( !$datumok ){
        echo json_encode(array(
            'hiba'  => 'Kérem, egy dátum/terv adatot adjon meg!',
            'title' => 'Hiba',
            'focus' => 'popup_datum',
        ));
        $weblap->leallit();
        exit();
    }
    
    // Új feltöltés, vagy kitöltés attól függ, hogy kaptunk-e $_POST['kitolt_recid']-t
    if( $_POST['kitolt_recid'] ) {

        // Átnevezés
        $sql = $adatbazis_kapcsolat->u('projektkezeles_cashflow_nev',   array('nev',         'telephely_id'         ),
                                                                        array($_POST['nev'], $_POST['telephely_id'] ),
                                        "`recid`='{$_POST['kitolt_recid']}'");
        if( $sql === false ) { $db_error = true; }
        
        // Korábbi sorok törlése
        $sql = $adatbazis_kapcsolat->d('projektkezeles_cashflow', "`cashflow_nev_recid`='{$_POST['kitolt_recid']}'");
        if( $sql === false ) { $db_error = true; }

        // Újra feltöltés pedig már közös része a működésnek, később fut le. Ehhez a $cf_nev változót be kell azért állítani
        $cf_nev = $_POST['kitolt_recid'];
        
    // Új cashflow sor
    } else {
        
        // Db-be mentés. Először a nevet.. 
        $cf_nev = $adatbazis_kapcsolat->i('projektkezeles_cashflow_nev',    array('projektkezeles_recid',   'nev',          'telephely_id',          'login',                            'update_dt',  'update_usr',                             'insert_dt',  'insert_usr'                              ),
                                                                            array($_POST['recid'],          $_POST['nev'],  $_POST['telephely_id'],  $_SESSION[get_db_tabla().'user'],   'NOW()',      $_SESSION[get_db_tabla().'teljes_nev'],   'NOW()',      $_SESSION[get_db_tabla().'teljes_nev']    )
                                        );
        if( $cf_nev === false ) { $db_error = true; }
        
    }
    
    // Majd a dátumokat
    $i = 0;
    foreach ( $datumok as $datum ){
        
        $datumok[$i] = str_replace('.', '-', $datumok[$i]);
        $tervek[$i]  = str_replace(' ', '',  $tervek[$i] );
        
        // PHP alatt is ellenőrizzük az adatokat
        if( !valos_datum_e($datumok[$i]) ) {
            echo json_encode(array(
                'hiba'          => 'Helytelen dátum ('.datum_kiir($datumok[$i]).') miatt nem rögzíthető az új sor!',
                'title'         => 'Hiba',
                'focus_datum_i' => $i+1,
            ));
            $weblap->leallit();
            exit();
        }
        if( $datumok[$i] < date('Y-m-d') ) {
            echo json_encode(array(
                'hiba'          => 'Múltbeli dátum ('.datum_kiir($datumok[$i]).') miatt nem rögzíthető az új sor!',
                'title'         => 'Hiba',
                'focus_datum_i' => $i+1,
            ));
            $weblap->leallit();
            exit();
        }
        
        $sql = $adatbazis_kapcsolat->i('projektkezeles_cashflow',   array('projektkezeles_recid',   'cashflow_nev_recid',   'datum',       'terv',       'login',                            'update_dt',  'update_usr',                             'insert_dt',  'insert_usr'                              ),
                                                                    array($_POST['recid'],          $cf_nev,                $datumok[$i],  $tervek[$i],  $_SESSION[get_db_tabla().'user'],   'NOW()',      $_SESSION[get_db_tabla().'teljes_nev'],   'NOW()',      $_SESSION[get_db_tabla().'teljes_nev']    )
                                        );
        if( $sql === false ) { $db_error = true; }
        $i++;
    }
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);

        echo json_encode(array(
            'statusz'       => 'siker',
            'cf_nev_recid'  => $cf_nev,
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

// Cash flow kitöltéshez adatok betöltése
if( isset($_POST['cf_adatok_betolt']) ) {

    // Cf név
    $cf_nev = $adatbazis_kapcsolat->s2('*', 'projektkezeles_cashflow_nev', "`recid`='{$_POST['recid']}'");
    
    // Adatok összeszedése
    $adatok = array();
    $cash_flowk = $adatbazis_kapcsolat->s('datum, terv', 'projektkezeles_cashflow', "`cashflow_nev_recid`='{$cf_nev['recid']}' AND `projektkezeles_recid`='{$cf_nev['projektkezeles_recid']}'", 'datum');
    foreach($cash_flowk as $cf){
        array_push($adatok, array('datum' => $cf['datum'], 'terv' => $cf['terv']));
    }
    
    echo json_encode(array(
        'nev'       => htmlspecialchars_decode($cf_nev['nev']),
        'adatok'    => $adatok
    ));
    
    $weblap->leallit();
    exit();
}


// Cash-flow sor törlése 
if( isset($_POST['cf_sor_torol']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Cash-flow név törlése
    $sql = $adatbazis_kapcsolat->d('projektkezeles_cashflow_nev', "`recid`='{$_POST['recid']}'");
    if( $sql === false ) { $db_error = true; }
    
    // Cash-flow sorok törlése
    $sql = $adatbazis_kapcsolat->d('projektkezeles_cashflow', "`cashflow_nev_recid`='{$_POST['recid']}'");
    if( $sql === false ) { $db_error = true; }
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);

        echo json_encode(array(
            'statusz'       => 'siker',
            'cf_nev_recid'  => $cf_nev,
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

// Dokumentumok fül alatt egy-egy csoportot amikor kinyitunk, akkor azt is jegyezzük meg, 
// hogy nyitva vagy zárva maradt, hogy újratöltéskor úgy maradjon, ne kelljen újra ki-be nyitni
if( isset($_POST['dokumentumok_kibont']) ) {
    
    if( $_SESSION['projekt_dok'][$_POST['azon']] ) {
        $_SESSION['projekt_dok'][$_POST['azon']] = false;
    } else {
        $_SESSION['projekt_dok'][$_POST['azon']] = true;
    }
    
    $weblap->leallit();
    exit();
}

// Kiválasztott dokumentumok bepipálásának megjegyzése
if( isset($_POST['projekt_dok_check']) ) {
    
    if( $_POST['recid'] ) {
        if( $_POST['checked']=='true' ) {
            array_push($_SESSION['projekt_dok_kuld_check'], $_POST['recid']);
        } else {
            if (($key = array_search($_POST['recid'], $_SESSION['projekt_dok_kuld_check'])) !== false) {
                unset($_SESSION['projekt_dok_kuld_check'][$key]);
            }
        }
    }
    
    $weblap->leallit();
    exit();
}


// Dokumentumok küldése
if( isset($_POST['dokumentumKuldeseTeamTagoknak']) ) {
    
    $recid              = $_POST['recid'];
    $emailkuldes_megj   = '';
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        
        // Címzettek ellenőrzése
        if( !trim($_POST['cimzett']) ) {
            
            echo json_encode(array(
                'hiba'  => 'Kérem adja meg a címzett email címét!',
                'title' => 'Hiba',
                'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2();">',
                'focus' => 'pop_cimzett',
            ));

            $weblap->leallit();
            exit();
            
        }

        // Címzett mezőben levő adat ellenőrzése
        $cimzett_hibak = is_valid_mail_tobb($_POST['cimzett'], 'Hibaüzenettel');
        if( $cimzett_hibak ){
            echo json_encode(array(
                'hiba'  => $cimzett_hibak,
                'title' => 'Hiba',
                'focus' => 'pop_cimzett',
            ));

            $weblap->leallit();
            exit();
        }
        // Másolat mezőben levő adat ellenőrzése
        $cimzett_hibak = is_valid_mail_tobb($_POST['masolat'], 'Hibaüzenettel');
        if( $cimzett_hibak ) {
            echo json_encode(array(
                'hiba'  => $cimzett_hibak,
                'title' => 'Hiba',
                'focus' => 'pop_masolat',
            ));

            $weblap->leallit();
            exit();
        }

        // Címzettek tömb előállítása
        $mailcimek = explode(',', $_POST['cimzett']);
        $mailcimek = array_values( array_filter($mailcimek) );

        // Másolatot kapnak tömb előállítása
        $masolatok = explode(',', $_POST['masolat']);
        $masolatok = array_values( array_filter($masolatok) );

        // Tárgy ellenőrzése
        if( !trim($_POST['targy']) ) {
            echo json_encode(array(
                'hiba'  => 'Kérem töltse ki az email tárgyát!',
                'title' => 'Hiba',
                'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2();">',
                'focus' => 'pop_targy',
            ));

            $weblap->leallit();
            exit();
        }

        // Üzenet ellenőrzése
        if( !trim($_POST['uzenet']) ) {
            echo json_encode(array(
                'hiba'  => 'Kérem töltse ki a email üzenetét!',
                'title' => 'Hiba',
                'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2();">',
                'focus' => 'pop_uzenet',
            ));

            $weblap->leallit();
            exit();
        }

        // Attól függően, hogy melyik rendszerben vagyunk, kap a tárgy/üzenet egy [Teszt] előtagot.
        $kieg = get_mail_teszt_felirat();
        $targy_kieg = $kieg[0];
        $uzi_kieg   = $kieg[1];

        // Csatolmányok összeszdése
        $mappa = $adatbazis_kapcsolat->s2('ikt_szam', 'projektkezeles', "`recid`='{$recid}'");
        $utvonal = '../'.get_utvonal().'projektkezeles/'.str_replace('/', '_', $mappa['ikt_szam']).'/email/*';
        
        $konyvtar_tartalma = glob($utvonal); // Fontos, hogy a $fajlok-ban csak a levélküldéshez feltöltött fájlokat tartalmazza, mert ezeket később archiváljuk, elpakoljuk

        // Könyvtárak kiszűrése
        $fajlok_vegleg = array();
        foreach ( $konyvtar_tartalma as $elem ) {
            if( is_file($elem) ) {
                array_push($fajlok_vegleg, $elem);
            }
        }
        $fajlok_mailnek = $fajlok = $fajlok_vegleg;
        // Kijelölt dokumentumok hozzáadása
        $utvonalak = explode(',', $_POST['utvonalak']);
        foreach( $utvonalak as $utvonal_tmp ) {
            array_push( $fajlok_mailnek, $utvonal_tmp );
        }

        $sikeres_kuldes = level_kuldes_osszetettebb(get_nev(), get_mail(), $mailcimek, $mailcimek, $masolatok, $masolatok, $r_masolatok, $r_masolatok, $targy_kieg.$_POST['targy'], $uzi_kieg.nl2br2($_POST['uzenet']), $fajlok_mailnek, 'levélpapíros');
        // Ha nem volt sikeres a levélküldés, akkor is megállítjuk a működést
        if( $sikeres_kuldes ) {
            mysqli_rollback($db_kapcs);
            echo json_encode(array(
                'hiba'  => 'Levélküldés közben hiba történt! '.$sikeres_kuldes,
                'title' => 'Hiba'
            ));

            $weblap->leallit();
            exit();

        } else {

            $cimzettek          = implode(', ', $mailcimek);
            $masolatot_kapott   = '';
            $dokumentumok       = '';
            foreach( $fajlok_mailnek as $fajl ) {
                $dokumentumok .= basename($fajl).', ';
            }

            $dokumentumok = trim( $dokumentumok, ', ' );
            $emailkuldes_megj .= 'Dokumentumok elküldve: '.$dokumentumok."\n".'Címzettek: '.$cimzettek.' ';
            if( $masolatok ) {
                $masolatot_kapott   = implode(', ', $masolatok);
                $emailkuldes_megj .= "\n".'Másolatot kaptak: '.$masolatot_kapott.' ';
            }
            $emailkuldes_megj .= "\n";

            // E-mail küldés logolása
            $email_recid = $adatbazis_kapcsolat->i('projektkezeles_email',  array('projektkezeles_recid',   'cimzett',   'masolat',         'targy',                     'text',                                'insert_dt', 'insert_usr'                           ),
                                                                            array($recid,                   $cimzettek,  $masolatot_kapott, $targy_kieg.$_POST['targy'], $uzi_kieg.nl2br2($_POST['uzenet']),    'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'] )
                                        );
            if( $email_recid === false ) { $db_error = true; }

            // Fájlok mozgatása
            if( csatolmanyok_eltesz($fajlok, $utvonal, $email_recid, $recid, 'projektkezeles') === false ){
                $db_error = true;
            }

        }

        // A kipipált dokumentumok ürítése
        $_SESSION['projekt_dok_kuld_check'] = array();
        
        // E-mail értesítésekről megjegyzést is írunk
        if( addMegj('projektkezeles', $recid, $emailkuldes_megj) === false ) {
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
                'title' => 'Hiba',
            ));
        }
    }

    $weblap->leallit();
    exit();
}

// Szerepkör lekérdezése
if( isset($_POST['get_szerepkor']) ) {
    
    $login = $adatbazis_kapcsolat->s2('szemelyzet_id', 'login', "`recid`='{$_POST['login_id']}'");

    $where = '';
    if( $login['szemelyzet_id'] == TERULETI_FELELOS ) {
        $where = "`modell`='1'";
    }
    $szalloda = $adatbazis_kapcsolat->s('id nev', 'telephely', $where);
    $telephely_select  = '<select class="disableNav csillag" name="telephely_id" id="telephely_id" onchange="szallodaVane( this ); szalloda_modell( this );" style="width: 100%;" tabindex="12">';
    $telephely_select .= '<option value="">- Válasszon -</option>';
    $telephely_select .= tomb2option($szalloda, 'id', 'nev');
    $telephely_select .= '</select>';
    
    echo json_encode(array(
        // Del? 'szemelyzet_id'     => $login['szemelyzet_id'],
        'telephely_select'  => $telephely_select,
    ));

    $weblap->leallit();
    exit();
}

// Munkalap hozzáadásakor a ML-ok táblázatának előállítása
if( isset($_POST['get_ml_ok']) ) {
    
    // Jog ellenőrzése
    if( !_jog(JOG_PRKEZEL_KEZEL) && !_jog(JOG_PRKEZEL_SAJAT_KEZEL) ) {
        echo json_encode(
                array(
                    'hiba'  => 'Nincs joga Munkalapot hozzáadni!',
                    'title' => 'Hiba',
                )
            );
        $weblap -> leallit();
        exit;
    }
    
    require_once 'selectek_szemelyzet.php';
    
    $projektkezeles = $adatbazis_kapcsolat->s2('ikt_szam nev telephely_id', 'projektkezeles', "`recid`='{$_POST['recid']}'");
    $tablazat = nl2(
                    'Projekt szám: <b>'.$projektkezeles['ikt_szam'].'</b>
                    <br>Projekt neve: <b>'.htmlspecialchars_decode($projektkezeles['nev']).'</b>
                    <br>Szálloda: <b>'.getSzallodaNev($projektkezeles['telephely_id']).'</b>
                    <br>'
                    );
    
    $i = 1;
    $munkalapok = $adatbazis_kapcsolat->s('recid ikt_szam stat_id tipus_id szemelyzet_id szallito_id text', 'munkalap', "`telephely_id`='{$projektkezeles['telephely_id']}' AND `pr_szam`='' AND `tipus_id` NOT IN (".MLTIPUS_RENDSZERES_K.", ".MLTIPUS_UTEMEZETT_F.")");
    foreach( $munkalapok as $ml ) {

        $szemelyzet = '';
        foreach( $select_szemelyzet as $szem ) {
            if( $ml['szemelyzet_id'] && $ml['szemelyzet_id'] == $szem['value'] ) {
                $szemelyzet = $szem['name'];
                break;
            }
        }
        if( $ml['szemelyzet_id'] == KULSO_KIVITELEZO ) {
            $szemelyzet .= ': '.getErtek('nev', 'szallito', 'id', $ml['szallito_id']);
        }
        
        $tablazat .= '
            <tr class="kijSor" data-i="'.$i.'" data-recid="'.$ml['recid'].'" data-ikt_szam="'.$ml['ikt_szam'].'">
                <td class="l">'.$ml['ikt_szam'].'</td>
                <td class="l">'.erteklista(ERTEKLISTA_MUNKALAP_TIP, $ml['tipus_id']).'</td>
                <td class="l">'.$szemelyzet.'</td>
                <td class="l">'. htmlspecialchars_decode( resz_string($ml['text'], 30) ).'</td>
                <td class="r">'. getStatText('munkalap', $ml['stat_id']).'</td>
            </tr>
            ';
        $i++;
    }
    if( $i>1 ) {

        // Táblázat rendezhető: szálloda + becsült költség, becsült költség + szálloda, típus + becsült költség. Ezek legyenek egy lenyílóból kiválaszthatóak.
        $tablazat = '
            <div style="max-height: 40rem; overflow-y: auto; overflow-x: none;">
                <table class="csikos v2 kijCont fix_fejlec csak_egy_kijelolheto" id="prkezelesek" style="cursor: default;">
                <thead>
                <tr>
                    <th>
                        Munkalap szám
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prkezelesek tbody\'), this.value, 1)">
                    </th>
                    <th>
                        Munkalap típus
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prkezelesek tbody\'), this.value, 2)">
                    </th>
                    <th>
                        Hozzárendelt személyzet/partner
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prkezelesek tbody\'), this.value, 3)">
                    </th>
                    <th>
                        Munkalap leírás
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prkezelesek tbody\'), this.value, 4)">
                    </th>
                    <th>
                        Munkalap státusza
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prkezelesek tbody\'), this.value, 5)">
                    </th>
                </tr>
                </thead>
                <tbody>
                    '.$tablazat.'
                </tbody>
                </table>
            </div>
            <script>
            ';
    } else {
        $tablazat = '<script>popupRejt(); setTimeout(function(){ popupZar(); alert2(\'Nem található munkalap!\', \'\', \'Hiba\'); }, 10);</script>';
    }
    
    echo json_encode( array('tablazat' => $tablazat) );
    
    $weblap->leallit();
    exit();
}

// Munkalap hozzáadása
if( isset($_POST['munkalap_hozzaad']) ) {
    
    // Jog ellenőrzése
    if( !_jog(JOG_PRKEZEL_KEZEL) && !_jog(JOG_PRKEZEL_SAJAT_KEZEL) ) {
        echo json_encode(
                array(
                    'hiba'  => 'Nincs joga Munkalapot hozzáadni!',
                    'title' => 'Hiba',
                )
            );
        $weblap -> leallit();
        exit;
    }
    
    projekt_munkalap_osszekot($_POST['recid'], $_POST['ml_id']);
    
    echo json_encode(array('siker' => 1));
    
    $weblap->leallit();
    exit();
}

// Munkalap levétele
if( isset($_POST['projekt_munkalap_levesz']) ) {
    
    projekt_munkalap_levesz($_POST['ml_id'], $_POST['ml_tipus'], $_POST['ml_stornoz']);
    
    echo json_encode(array('siker' => 1));
    
    $weblap->leallit();
    exit();
}

// Projekt státusz léptetés folyamatbanra
if( isset($_POST['projektkezeles_folyamatbanra']) ) {
    
    addStatusz( 'projektkezeles_statusz', $_POST['recid'], PRKEZEL_STAT_FOLYAMATBAN );
    
    $weblap->leallit();
    exit();
}

// CSV generálása
if( isset($_GET['csv']) ) {

    require_once 'selectek.php';

    // CSV export előkészítése
    list($mibol, $mibe, $filename, $open) = csv_export_elokeszit($megnevezes);
    if( $open ) {
        if( $_SESSION['nyomtatas_ids'] ) {
            
            $lekerdezes_fromtol = substr($_SESSION['akt_sql'], strpos($_SESSION['akt_sql'], 'FROM'));
            
            // Ha van benne limit, akkor a teljes adatbázist kilistázzuk, de csak akkor, ha a limit az alapértelmezett (Ez olyan modulok alatt szükséges, ahol van egy alapértelmezett limit-es szűrés)
            $mindet_listazd = false;
            if( strpos( $_SESSION['akt_sql'], 'LIMIT' )!==false ) {
                $limit = trim( substr( $_SESSION['akt_sql'], strpos($_SESSION['akt_sql'], 'LIMIT') ), 'LIMIT ' );
                if( strpos($limit, ' ') ) {
                    $limit = substr($limit, 0, strpos($limit, ' '));
                }
                if( $limit == ARCHIV_NAVPANEL_LIMIT && !$_SESSION['nyomtatas_ids_ksz'] ) {
                    $mindet_listazd = true;
                }
            }
            if( $mindet_listazd ) {

                // A LIMIT-et levágjuk, illetve a balnavpanel szerinti rendezést is nullázzuk
                $lekerdezes_fromtol = substr($lekerdezes_fromtol, 0, strpos($lekerdezes_fromtol, 'LIMIT') );
                $_SESSION['akt_sql_order'] = '';

            } else {
            
                if( strstr($_SESSION['akt_sql'], 'WHERE') ) {
                    $lekerdezes_fromtol = str_replace('WHERE', "WHERE `".get_db_tabla().$megnevezes."`.`recid` IN (".$_SESSION['nyomtatas_ids'].") AND ", $lekerdezes_fromtol);
                } else {
                    $lekerdezes_fromtol = str_replace('ORDER', "WHERE `".get_db_tabla().$megnevezes."`.`recid` IN (".$_SESSION['nyomtatas_ids'].") ORDER", $lekerdezes_fromtol);
                }

                // Ha van $_SESSION['akt_sql_order'], akkor eszerint rendezünk, tehát a $lekerdezes_fromtol-t elvágjuk az Order-nél
                if( $_SESSION['akt_sql_order'] ){
                    $hossz = strpos($lekerdezes_fromtol, 'ORDER');
                    if( $hossz ) {
                        $lekerdezes_fromtol = substr($lekerdezes_fromtol, 0, $hossz);
                    } else {
                        $lekerdezes_fromtol = substr($lekerdezes_fromtol, 0);
                    }
                }
                
            }
            
            // Ha nincs FROM rész, akkor azt még beletesszük
            if( !$lekerdezes_fromtol ) {
                $lekerdezes_fromtol = ' FROM `'.get_db_tabla().$megnevezes.'` ';
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
                        `'.get_db_tabla().$megnevezes.'`.`akt_adat` as \'Aktiválási adatok\',
                        `'.get_db_tabla().$megnevezes.'`.`jovahagyva` as \'Jóváhagyva\',
                        `'.get_db_tabla().$megnevezes.'`.`jovahagyo` as \'Jóváhagyó\'
                        '.$lekerdezes_fromtol;
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
                    
                    if($kulcs=='Státusz'){
                        $sor = getStatText('projektkezeles', $sor);
                    }
                    if($kulcs=='Projektfelelős'){
                        $sor = getErtek('teljes_nev', 'login', 'recid', $sor);
                    }
                    if($kulcs=='Szálloda'){
                        $sor = getSzallodaNev($sor);
                    }
                    if( $kulcs == 'Tipus' ) {
                        foreach ( $select_pri_tipus as $tipus ) {
                            if( $tipus['value'] == $sor ) {
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
                    if( $kulcs == 'Prioritás' ) {
                        $sor = ertekLista( ERTEKLISTA_PROJEKT_PRIOR, $sor );
                    }

                    if( $kulcs == 'Megtérülő' ) {
                        if( $sor ) {
                            $sor = 'igen';
                        } else {
                            $sor = 'nem';
                        }
                    }
                    if( $kulcs == 'Rendkívüli' ) {
                        if( $sor ) {
                            $sor = 'igen';
                        } else {
                            $sor = 'nem';
                        }
                    }
                    if( $kulcs == 'Jóváhagyva' ) {
                        $sor = datum_kiir($sor);
                    }
                    if( $kulcs == 'Jóváhagyó' ) {
                        $sor = getErtek('info', 'erteklista', 'recid', $sor);
                    }
                    $csvcont[] = iconv($mibol, $mibe.'//TRANSLIT', strip_tags( htmlspecialchars_decode( $sor ) ) );
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
    }

    csv_export_lezar($fh, $filename, 'Projektek');
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
    if( $_GET['id'] ) {
        $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");
    }
    if( $sor['dok_db'] ) {
        $dok_db  = ' ('.$sor['dok_db'].')';
    }
    if( $sor['megj_db'] ) {
        $megj_db = ' ('.$sor['megj_db'].')';
    }

    if( isset($_GET['modosit']) || isset($_GET['tortenet']) || isset($_GET['projektigeny']) || isset($_GET['cash_flow']) || isset($_GET['projekt_team']) || isset($_GET['jotallas']) || isset($_GET['munkalapok']) || isset($_GET['garancialis']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
        
        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'tortenet'      => 'Projekt történet',
            'projekt_team'  => 'Projekt-team',
            'jotallas'      => 'Jótállás',
            'dokumentumok'  => 'Dokumentumok'.$dok_db,
            'cash_flow'     => 'Cash-flow',
            'projektigeny'  => 'Projektigénylések',
            'munkalapok'    => 'Munkalapok',
            'garancialis'   => 'Garanciális munkák',
            'megjegyzesek'  => 'Megjegyzések'.$megj_db,
        );

        $fulek = '';
        foreach ( $ful_tomb as $key => $value ) {

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
    
    // Az egy/több szállodás pipa beállítása
    $szamla_html .= nl2('
                            <script>
                                $(document).ready(function () {

                                    if( $(\'#egy_tobb\').val()>0 ){
                                        $(\'#egy_tobb_tobb\').prop(\'checked\', true)
                                    } else {
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
    } else {

        // Több kérdés is azzal kezdődik, hogy kiírjuk a Projekt adatait, ezért egy változóba kiszervezzük
        $projekt_adatok = 'Azonosító: '.$sor['ikt_szam'].'<br>Szálloda: '.getSzallodaNev($sor['telephely_id']).'<br>Név: '.addslashes($sor['nev']).'<br><br>';
        
        // Törlés gomb
        if( !get_projekt_kezeles_jog($_GET['id']) ) {
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Önnek nincs joga a projekt törléséhez!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        // Csak nyitott projekt törölhető
        } elseif( $sor['stat_id'] != PRKEZEL_STAT_NYITOTT ) {
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Csak nyitott státuszú projekt törölhető!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        // Ha már tartozik hozzá projektigénylés, akkor sem törölhető
        } elseif( $sor['projektigenyles_recid'] ) {
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'A projekthez már tartozik projektigénylés, így nem törölhető!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        } else {

            // Nézzük meg, hogy van-e olyan szerződés, amihez hozzá van kapcsolva. Ha van, akkor sem törölhető
            $van_szerzodese = $adatbazis_kapcsolat->s('recid', 'projektszerzodes', "`pr_szam`='{$sor['ikt_szam']}'");
            if( $van_szerzodese ){
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Már van olyan szerződés, amihez ez a projekthez hozzá van adva, így nem törölhető!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
            } else {
                $gombok .= '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
            }

        }
        
        // Előkészítés alatt státuszba léptetés
        $statuszok = array(PRKEZEL_STAT_NYITOTT);
        if( in_array($sor['stat_id'], $statuszok) && get_projekt_kezeles_jog($_GET['id']) ) {
            
            // Van-e már olyan szerződés, aminek van dokumentuma
            $szerzodesek = $adatbazis_kapcsolat->s('recid', 'projektszerzodes', "`pr_szam`='{$sor['ikt_szam']}'");
            $szerzodesek = adatbazis2tomb($szerzodesek, 'recid');

            $van_dok = '';
            if( $szerzodesek ) {
                $van_dok = $adatbazis_kapcsolat->s2('recid', 'projektszerzodes_dok', "`projektszerzodes_id` IN (".implode(', ', $szerzodesek).")", '', '1');
            }
            
            $van_dok2 = $adatbazis_kapcsolat->s2('recid', 'projektkezeles_dok', "`projektkezeles_id`='{$_GET['id']}'");
            if( !$van_dok && !$van_dok2 ) {
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Előkészítés alatt" onclick="alert2(\'Előbb csatoljon dokumentumot a projekthez vagy a hozzá tartozó szerződéshez!\', \'\', \'Hiba\')"> ';
            } else {
                $gombok .= '<script> gomb_elokeszites = \'<input type="button" class="gomb zold" onclick="projekt_elokeszites_ala()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\';</script>';
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Előkészítés alatt" onclick="alert2(\''.$projekt_adatok.'Biztos, hogy Előkészítés alatt státuszba helyezi a projektet?\', gomb_elokeszites)"> ';
            }
        }
        
        // Keretösszeg elemlés
        $statuszok = array(PRKEZEL_STAT_NYITOTT, PRKEZEL_STAT_ELOKESZITES_ALATT, PRKEZEL_STAT_FOLYAMATBAN);
        if( in_array($sor['stat_id'], $statuszok) && _jog( JOG_PRKEZEL_KERETOSSZEG_EMEL ) ) {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Keret módosítása" onclick="csoportMuvelet(\'projektkezeles_keret_modosit\')"> ';
        }
        // Felfüggesztés
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
            
            $szerzodesek = $adatbazis_kapcsolat->s('recid', 'projektszerzodes', "`pr_szam`='{$sor['ikt_szam']}'");
            $szerzodesek = adatbazis2tomb($szerzodesek, 'recid');
            
            $van_dok = '';
            if( $szerzodesek ) {
                $van_dok = $adatbazis_kapcsolat->s2('recid', 'projektszerzodes_dok', "`projektszerzodes_id` IN (".implode(', ', $szerzodesek).")  AND `tipus`='".EL_DOK_TIPUS_SZERZODES."'", '', '1');
            }
            if( !$van_dok ) {
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Folyamatban" onclick="alert2(\'Előbb minősítsen szerződésnek egy dokumentumot!\', \'\', \'Hiba\')"> ';
            } else {
                $gombok .= '<script> gomb_folyamatbanra = \'<input type="button" class="gomb zold" onclick="projekt_folyamatbanra()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\';</script>';
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Folyamatban" onclick="alert2(\''.$projekt_adatok.'Biztos, hogy Folyamatban státuszba helyezi a projektet?\', gomb_folyamatbanra)"> ';
            }
        }
        
        // Folyamatban
        $statuszok = array(PRKEZEL_STAT_FOLYAMATBAN);
        if( in_array($sor['stat_id'], $statuszok) && get_projekt_kezeles_jog($_GET['id']) ) {
            
            // Van-e már csatolt Átadási. jk. dokumentum
            $szerzodesek = $adatbazis_kapcsolat->s('recid', 'projektszerzodes', "`pr_szam`='{$sor['ikt_szam']}'");
            $szerzodesek = adatbazis2tomb($szerzodesek, 'recid');

            $van_dok = '';
            if( $szerzodesek ) {
                $van_dok = $adatbazis_kapcsolat->s2('recid', 'projektszerzodes_dok', "`projektszerzodes_id` IN (".implode(', ', $szerzodesek).") AND (`tipus`='".EL_DOK_TIPUS_ATADASI."' OR `tipus`='".EL_DOK_TIPUS_TELJESITES."')", '', '1');
            }
            $van_dok2 = $adatbazis_kapcsolat->s2('recid', 'projektkezeles_dok', "`projektkezeles_id`='{$_GET['id']}' AND (`tipus`='".EL_DOK_TIPUS_ATADASI."' OR `tipus`='".EL_DOK_TIPUS_TELJESITES."')");
            if( !$van_dok && !$van_dok2 ) {
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Kész" onclick="alert2(\'Előbb minősítsen egy dokumentumot Átadási. jk-nek vagy Teljesítésigazolásnak!\', \'\', \'Hiba\')"> ';
            } else {
                $gombok .= '<script> gomb_keszre = \'<input type="button" class="gomb zold" onclick="projekt_keszre()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\';</script>';
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Kész" onclick="alert2(\''.$projekt_adatok.'Biztos, hogy Kész státuszba helyezi a projektet?\', gomb_keszre)"> ';
            }
        }
        
        // Kész státusz
        $statuszok = array(PRKEZEL_STAT_KESZ);
        if( in_array($sor['stat_id'], $statuszok) && get_projekt_kezeles_jog($_GET['id']) ) {
            
            // Adott Projekthez kapcsolódó szerződések jótállásainak mindegyikének van-e már feltölött dokumentuma?
            // Ha csak egy olyan jótállás is létezik, aminek még nincs dokumentuma (`utvonal`=''), akkor nem engedjük lezárni.
            $szerzodesek = $adatbazis_kapcsolat->s('recid', 'projektszerzodes', "`pr_szam`='{$sor['ikt_szam']}'");
            $szerzodesek = adatbazis2tomb($szerzodesek, 'recid');
            if( $szerzodesek ) {
                $van_hianyos_jotallas = $adatbazis_kapcsolat->s2('recid', 'projektszerzodes_jotallas', "`projektszerzodes_recid` IN (".implode(', ', $szerzodesek).") AND `utvonal`=''");
            }

            if( $van_hianyos_jotallas ) {
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Lezárt" onclick="alert2(\'Utolsó garanciális jegyzőkönyv még nincs feltöltve, ezért a projekt nem zárható le!\', \'\', \'Hiba\')"> ';
            } else {
                $gombok .= '<script> gomb_lezartra = \'<input type="button" class="gomb zold" onclick="projekt_lezartra()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\';</script>';
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Lezárt" onclick="alert2(\''.$projekt_adatok.'Biztos, hogy Lezárt státuszba helyezi a projektet?\', gomb_lezartra)"> ';
            }
        }
        
        // Cash-flow fül alatt van új sor feltöltés, kitöltés és törlés
        if (isset($_GET['cash_flow'])) {
            $gombok .= ' <input type="button" class="gomb modositaskor_rejt" onclick="csoportMuvelet(\'projektkezeles_cf_uj\')" value="Új sor">';
            $gombok .= ' <input type="button" class="gomb modositaskor_rejt" onclick="kapcsolodoSzerkeszt(\'projektkezeles_cf_kitolt\');" value="Sor kitöltés">';
            $gombok .= ' <input type="button" class="gomb modositaskor_rejt" onclick="cf_sor_torol();" value="Sor törlés"> ';
        }
        
        // Ha zárt a prjekt és van újranyitás jogunk
        if( $sor['stat_id'] == PRKEZEL_STAT_LEZART && _jog( JOG_PRKEZEL_UJRANYITAS ) ) {
            $kerdes      = htmlspecialchars('Projekt: '.$sor['ikt_szam'].'<br>Név: '. addslashes($sor['nev']).'<br><br>Visszahelyezi folyamatba a projektet?');
            $alert_gomb  = htmlspecialchars('<input type="button" class="gomb zold" value="Igen" onclick="projektkezeles_ujranyit()"><input type="button" class="gomb piros" value="Nem" onclick="alert2();">');
            $gombok .= '<input type="button" class="gomb" onclick="alert2(\''.$kerdes.'\', \''.$alert_gomb.'\')" value="Újranyitás"> ';
        }

        // Ha van egyáltalán kezelésre joga, és nincs még lezárva
        if( get_projekt_kezeles_jog($_GET['id']) && $sor['stat_id']!=PRKEZEL_STAT_LEZART ) {

            if(!$nem_szerkesztheti && isset($_GET['modosit']) /*&& $sor['stat_id']==PRIGENY_STAT_LETRE_ALATT Átmenetileg végig szerkeszthető */){
                $gombok = nl2('
                    <div class="form_kuldes">
                        <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
                        <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
                    </div>
                ').$gombok;
            }
        }
        
        // Csak dokumentumok fül alatt látszik
        if( isset($_GET['dokumentumok']) ) {
            
            // Ha nincs Team tag, akkor a popup fel sem jön
            $onclick = nl2('
                    if( !$(\'.dok_jeloles:checked\').length ) {
                        alert2(\'E-mail küldéséhez kérem válasszon legalább 1 dokumentumot!\', \'\', \'Hiba\');
                    } else {
                        csoportMuvelet(\'dokumentumKuldeseTeamTagoknak\');
                    }
                ');
            $team = $adatbazis_kapcsolat->s('recid', 'projektkezeles_team', "`projektkezeles_recid`='{$_GET['id']}'");
            if( !$team ) {
                $onclick = 'alert2(\'A projekthez nincs team tag hozzárendelve!\', \'\', \'Hiba\');';
            }
            
            if( _jog(JOG_PRKEZEL_KEZEL) ) {
                $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Dokumentumok küldése team tagoknak" onclick="'.$onclick.'"> ';
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
    if( isset($_GET['uj']) && _jog(JOG_PRKEZEL_UJ_LETREHOZ) ) {
        
        // Ha új, üres projektet töltünk fel, akkor jóváhagyás előtt rákérdezünk, hogy biztos-e?
        if( !$_GET['igeny_id'] ) {
            $js_submit_elott = nl2('
                                        if( !feltoltes_mehet ) {
                                            alert2(
                                                \'Név: \'+$(\'#nev\').val()+
                                                \'<br>Szálloda: \'+$(\'#telephely_unid option:selected\').text()+
                                                \'<br>Jóváhagyott keret: \'+$(\'#jkolt\').val()+
                                                \' '. get_penznem().'
                                                <br>
                                                <br>A jóváhagyott keret mentést követően nem módosítható!
                                                <br>
                                                <br>Folytatja?\',

                                                \'<input type="button" class="gomb zold" value="Igen" onclick="feltoltes_mehet=true; mentesGomb();"><input type="button" class="gomb piros" value="Nem" onclick="alert2();">\'
                                            );
                                            alert_utan_mehet_megsem_mutat_beallit();
                                            return false;
                                        } else {
                                            return form_ell_admin();
                                        }
                                    ');
        }

        // FORM
		if( isset($_GET['ujkent']) ) {
            foreach ( $akt_tomb as $ertek ) {
                $sor[$ertek['name']] = $_SESSION[$ertek['name']];
			}
		}
	    $js  = js_kiir($akt_tomb, 'form_ell_admin()', '');
        $js .= '<script>var projektkezeles_mehet = false;</script>';

	    $form_sablon = fajlbol_olvas('../sablon/admin/form_uj.html', true);

        // Javascript-es ellenőrzéshez
        $tabla = str_replace('.php', '', basename(self()));

        $rend_mezok = $adatbazis_kapcsolat->s('`column_name`', '`information_schema`.columns', '`table_schema` LIKE  \''.get_db().'\' AND  `table_name` LIKE  \''.get_db_tabla().$tabla.'\'', '', '');
        $rend_mezok = adatbazis2tomb($rend_mezok, 'column_name');

        $sor = array();
        foreach ( $rend_mezok as $rend_mezo ) {
            $sor[$rend_mezo] = '';
        }
        
        // Ha van igénylés recid-nk, akkor annak az adatait felhasználjuk
        if( $_GET['igeny_id'] ) {
            $igenyles = $adatbazis_kapcsolat->s2('*', 'projektigenyles', "`recid`='{$_GET['igeny_id']}' AND `telephely_id` IN (".implode(', ', get_akt_login_szallodai()).")");
            $sor['nev']                     = $igenyles['nev'];
            $sor['egy_tobb']                = $igenyles['egy_tobb'];
            $sor['telephely_id']            = $igenyles['telephely_id'];
            $sor['tipus']                   = $igenyles['tipus'];
            $sor['besorol']                 = $igenyles['besorol'];
            $sor['prior']                   = $igenyles['prior'];
            $sor['jkolt']                   = $igenyles['jkolt'];
            $sor['rendk']                   = $igenyles['rendk'];
            $sor['megter']                  = $igenyles['megter'];
            $sor['text']                    = $igenyles['text'];
            $sor['projektigenyles_recid']   = $_GET['igeny_id'];
            
            $telephely = $adatbazis_kapcsolat->s2('unid', 'telephely', "`id`='{$igenyles['telephely_id']}'");
            $sor['telephely_unid']          = $telephely['unid'];
        }

        $json_sor = array();
        foreach ( $sor as $kulcs => $elem ) {
            $json_sor[$kulcs] = formAdatok_replace($elem);
        }
        $szamla_html .= '
                        <script>
                            formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); 
                            var feltoltes_mehet = false;
                         </script>';

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
    } elseif( isset($_GET['modosit']) ) {

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if( isset($_GET['id_hidden']) ) {
            $_GET['id'] = $_GET['id_hidden'];
        }

	    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");

        foreach ( $akt_tomb as $tomb ) {
            if( $tomb['type'] == 'autocomplete' ) {
                
                if( $tomb['csoport'] ) {
                    $kulso_ertek = $adatbazis_kapcsolat->s2('info', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}' AND `csoport`='{$tomb['csoport']}'", '', '1');
                    $kulso_ertek['nev'] = $kulso_ertek['info'];
                // Projektfelelősnél a teljes nevet írjuk ki, nem a nev oszlopot
                }elseif( $tomb['name'] == 'pfelelos' ) {
                    $kulso_ertek = $adatbazis_kapcsolat->s2('recid teljes_nev', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}'", '', '1');
                    $kulso_ertek['nev'] = $kulso_ertek['teljes_nev'];
                } else {
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
        $js .= '<script>var projektkezeles_mehet = false;</script>';

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
    } elseif( isset($_GET['cash_flow']) ) {

        $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");
        
        // Előző hónap kiszámítása
        $elozo_ev    = date('Y');
        $elozo_honap = date('n');
        $elozo_honap--;
        if( $elozo_honap == 0 ) {
            $elozo_honap = 12;
            $elozo_ev--;
        }
        $elozo_ev_honap = $elozo_ev.'.'.sprintf("%02d", $elozo_honap);
        
        // Előző előtti hónap kiszámíása
        $elozo_honap--;
        if( $elozo_honap == 0 ) {
            $elozo_honap = 12;
            $elozo_ev--;
        }
        $elozo_elotti_ev_honap = $elozo_ev.'.'.sprintf("%02d", $elozo_honap);
        
        // X tengely előállítása
        $idtotengely = array(str_replace('.', '-', $elozo_elotti_ev_honap), str_replace('.', '-', $elozo_ev_honap));
        $akt_ev    = date('Y');
        $akt_honap = date('n');
        for ( $i = 0; $i < 11; $i++ ) {
            array_push( $idtotengely,  $akt_ev.'-'.sprintf("%02d", $akt_honap));
            $akt_honap++;
            if( $akt_honap == 13 ) {
                $akt_honap = 1;
                $akt_ev++;
            }
        }
        
        // Táblázat kirajzolása
        $idotengely_html = '';
        $i = 0;
        foreach ( $idtotengely as $honapok ) {
            $ig = '';
            if( !$i ) {
                $ig = '-ig';
            }
            $idotengely_html .= '<th class="nowrap">'.datum_kiir($honapok).$ig.'</th>';
            $i++;
        }
        $cash_flow_html = nl2('
            <table class="szamla_cont_tabla szep_csikos kisbetu">
            <thead>
                <tr>
                    <th>Név</th>
                    <th>Szálloda</th>
                    '.$idotengely_html.'
                </tr>
            </thead>
            <tbody>
        ');
        $cash_flow_nevek = $adatbazis_kapcsolat->s('recid nev telephely_id', 'projektkezeles_cashflow_nev', "`projektkezeles_recid`='{$_GET['id']}'", 'nev');
        foreach ( $cash_flow_nevek as $nev ) {
            
            $cash_flow_html .= nl2('
                                    <tr data-recid="'.$nev['recid'].'">
                                        <td>'.htmlspecialchars_decode($nev['nev']).'</td>
                                        <td>'.getSzallodaNev($nev['telephely_id']).'</td>
                                            ');
            
            $i = 0;
            foreach ( $idtotengely as $honap ) {

                // Az első hónap egy összesítés, az összes múltbeli adatból
                if( !$i ) {
                    $cash_flow = $adatbazis_kapcsolat->s2('SUM(`terv`)', 'projektkezeles_cashflow', "`projektkezeles_recid`='{$_GET['id']}' AND `cashflow_nev_recid`='{$nev['recid']}' AND `datum`<='{$honap}'");
                } else {
                    $cash_flow = $adatbazis_kapcsolat->s2('SUM(`terv`)', 'projektkezeles_cashflow', "`projektkezeles_recid`='{$_GET['id']}' AND `cashflow_nev_recid`='{$nev['recid']}' AND `datum` LIKE '{$honap}%'");
                }
                
                $cash_flow_html .= '<td class="nowrap r">'.number_format($cash_flow['SUM(`terv`)'], 0, '', ' ').'</td>';
                $i++;
            }
            
            $cash_flow_html .= '</tr>';
        }
        
        // Szerződések
        $szerzodes_recidk = $adatbazis_kapcsolat->s('recid', 'projektszerzodes', "`pr_szam`='{$sor['ikt_szam']}'");
        $szerzodes_recidk = adatbazis2tomb($szerzodes_recidk, 'recid');
        $cash_flow_html .= nl2('
                                <tr data-recid="szerzodesek">
                                    <td>Szerződések</td>
                                    <td></td>
                                    ');
        $i = 0;
        foreach ( $idtotengely as $honap ) {

            // Az első hónap egy összesítés, az összes múltbeli adatból
            if( $szerzodes_recidk ) {
                if( !$i ) {
                    $cash_flow = $adatbazis_kapcsolat->s2('SUM(`varh_ert`)', 'projektszerzodes_tetel', "`projektszerzodes_recid` IN (".implode(', ', $szerzodes_recidk).") AND `varh_erk`<='{$honap}-31' AND `teljesites`='0000-00-00'");   
                } else {
                    $cash_flow = $adatbazis_kapcsolat->s2('SUM(`varh_ert`)', 'projektszerzodes_tetel', "`projektszerzodes_recid` IN (".implode(', ', $szerzodes_recidk).") AND `varh_erk` LIKE '{$honap}%' AND `teljesites`='0000-00-00'");
                }
            }

            $cash_flow_html .= '<td class="nowrap r">'.number_format($cash_flow['SUM(`varh_ert`)'], 0, '', ' ').'</td>';
            $i++;
        }
        $cash_flow_html .= '</tr>';
        
        // Számlák
        $cash_flow_html .= nl2('
                                <tr data-recid="szamlak">
                                    <td>Számlák</td>
                                    <td></td>
                                    ');
        $i = 0;
        foreach ( $idtotengely as $honap ) {

            // Az első hónap egy összesítés, az összes múltbeli adatból
            if( $szerzodes_recidk ) {
                if( !$i ) {
                    $cash_flow = $adatbazis_kapcsolat->s2('SUM(`ertek`)', 'projektszerzodes_tetel', "`projektszerzodes_recid` IN (".implode(', ', $szerzodes_recidk).") AND `teljesites`<='{$honap}-31' AND `teljesites`!='0000-00-00'");
                } else {
                    $cash_flow = $adatbazis_kapcsolat->s2('SUM(`ertek`)', 'projektszerzodes_tetel', "`projektszerzodes_recid` IN (".implode(', ', $szerzodes_recidk).") AND `teljesites` LIKE '{$honap}%'");
                }
            }

            $cash_flow_html .= '<td class="nowrap r">'.number_format($cash_flow['SUM(`ertek`)'], 0, '', ' ').'</td>';
            $i++;
        }
        $cash_flow_html .= '</tr>';
        
        $cash_flow_html .= nl2('
            </tbody>
            </table>
            <script>$(\'.szamla_cont_tabla tbody tr:first-of-type\').addClass(\'kijelolve\');</script>
        ');
        
        $szamla_html .= $cash_flow_html;

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Projekt team
    } elseif( isset($_GET['projekt_team']) ) {

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
            if( get_projekt_kezeles_jog($_GET['id']) && $sor['stat_id'] != PRKEZEL_STAT_LEZART ) {
                $van_kezeles = true;
            }
            
            foreach ( $team_ek as $team ) {
                $torles = '';
                if( $van_kezeles ) {
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
        
	// Jótállás
    } elseif( isset($_GET['jotallas']) ) {

        $szamla_html .= nl2('
            <div class="dt naplo">
                <div class="dtr nFejlec">
                    <div class="dtc">Szerződés száma</div>
                    <div class="dtc">Szerződő partner</div>
                    <div class="dtc">Szerződés tárgya</div>
                    <div class="dtc">Gar. bej. dátuma</div>
                    <div class="dtc">Gar. bej. jegyzőkönyv</div>
                </div>
        ');
        
        $projekt            = $adatbazis_kapcsolat->s2('ikt_szam', 'projektkezeles', "`recid`='{$_GET['id']}'");
        $pr_szerzodes_idk   = $adatbazis_kapcsolat-> s('recid', 'projektszerzodes', "`pr_szam`='{$projekt['ikt_szam']}'");
        $pr_szerzodes_idk   = adatbazis2tomb($pr_szerzodes_idk, 'recid');
        if( $pr_szerzodes_idk ) {
            $jotallasok = $adatbazis_kapcsolat->s('*', 'projektszerzodes_jotallas', "`projektszerzodes_recid` IN (".implode(', ', $pr_szerzodes_idk).")", 'bejaras');
        }
        foreach ( $jotallasok as $jotallas ) {
            
            $pr_szerzodes = $adatbazis_kapcsolat->s2('sz_szam szallito_id targy', 'projektszerzodes', "`recid`='{$jotallas['projektszerzodes_recid']}'");
            
            // Ha van már fájl, akkor azt megmutatjuk
            $fajl = '';
            if( $jotallas['utvonal'] ) {
                
                $utvonal = '../'. get_utvonal().'projektszerzodes_jotallas/'.$jotallas['utvonal'];
                $fajl = '<a href="'.$utvonal.'" target="_blank">'.kep_kiir( $utvonal, ' ', 30, 30, '', '', '' ).' '.$jotallas['fajlnev'].'</a>';
                
            }
            
            $szamla_html .= nl2('
                <div class="dtr nSor">
                    <div class="dtc"><a href="projektszerzodes.php?id=x&amp;jotallas&amp;mezo=sz_szam&amp;op=equal&amp;felt='.$pr_szerzodes['sz_szam'].'" target="_blank">'.$pr_szerzodes['sz_szam'].'</a></div>
                    <div class="dtc">'.getErtek('nev', 'szallito', 'id', $pr_szerzodes['szallito_id']).'</div>
                    <div class="dtc">'. nl2br2($pr_szerzodes['targy']).'</div>
                    <div class="dtc">'.datum_kiir($jotallas['bejaras']).'</div>
                    <div class="dtc">'.$fajl.'</div>
                </div>
            ');
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
    } elseif( isset($_GET['projektigeny']) ) {

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
        } else {
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
    } elseif( isset($_GET['tortenet']) ) {

        $szamla_html .= get_tortenet($megnevezes);

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Munkalapok
    } elseif( isset($_GET['munkalapok']) ) {
        
        // ML-ot hozzáadni csak Projektek kezelése joggal lehet
        if( _jog(JOG_PRKEZEL_KEZEL) || _jog(JOG_PRKEZEL_SAJAT_KEZEL) ) {
            $szamla_html .= '<div class="c" style="margin-bottom: 1rem"><input type="button" class="gomb" value="Hozzáadás" onclick="csoportMuvelet(\'projektkezeles_ml_hozzaad\')"></div>';
        }
        
        $szamla_html .= nl2('
            <div class="dt naplo">
                <div class="dtr nFejlec">
                    <div class="dtc">Munkalap szám</div>
                    <div class="dtc">Munkalap típus</div>
                    <div class="dtc">Hozzárendelt személyzet/Partner</div>
                    <div class="dtc">Munkalap státusza</div>
                    <div class="dtc">Munkalap levétele</div>
                </div>
        ');
        
        $projekt    = $adatbazis_kapcsolat->s2('ikt_szam', 'projektkezeles', "`recid`='{$_GET['id']}'");
        $munkalapok = $adatbazis_kapcsolat->s('recid ikt_szam stat_id tipus_id szemelyzet_id szallito_id', 'munkalap', "`pr_szam`='{$projekt['ikt_szam']}' AND `pr_ml_ful`=1 AND `stat_id`!='".MUNKALAP_STAT_TOROLT."' AND `stat_id`!='".MUNKALAP_STAT_SZTORNO."'");
        foreach ( $munkalapok as $munkalap ) {
            
            // Személyzet kiíratása
            $szemelyzet = '';
            foreach ( $select_szemelyzet as $szem ) {
                if( $munkalap['szemelyzet_id'] && $szem['value'] == $munkalap['szemelyzet_id'] ) {
                    $szemelyzet = $szem['name'];
                    break;
                }
            }
            // Ha külső kivitelező, akkor a partner nevét is odaírjuk
            if( $munkalap['szemelyzet_id'] == KULSO_KIVITELEZO ) {
                $szemelyzet .= ': '.getErtek('nev', 'szallito', 'id', $munkalap['szallito_id']);
            }
            
            $szamla_html .= nl2('
                <div class="dtr nSor">
                    <div class="dtc"><a href="munkalap.php?id=x&amp;modosit&amp;mezo=ikt_szam&amp;op=equal&amp;felt='.$munkalap['ikt_szam'].'" target="_blank">'.$munkalap['ikt_szam'].'</a></div>
                    <div class="dtc">
                        '.erteklista(ERTEKLISTA_MUNKALAP_TIP, $munkalap['tipus_id']).'
                    </div>
                    <div class="dtc">'.$szemelyzet.'</div>
                    <div class="dtc">'.getStatText('munkalap', $munkalap['stat_id']).'</div>
                    <div class="dtc"><span class="icon link" onclick="pr_ml_levesz_id = \''.$munkalap['recid'].'\'; csoportMuvelet(\'projektkezeles_ml_levetel\');"> <i class="material-icons" style="font-size: 2rem">delete</i></span></div>
                </div>
            ');
        }
        // Archiv munkalapokat is járjuk be
        $munkalapok_arch = $adatbazis_kapcsolat->s('ikt_szam stat_id tipus_id szemelyzet_id szallito_id', 'munkalap_arch', "`pr_szam`='{$projekt['ikt_szam']}' AND `stat_id`!='".MUNKALAP_STAT_TOROLT."' AND `stat_id`!='".MUNKALAP_STAT_SZTORNO."'");
        foreach ( $munkalapok_arch as $munkalap ) {
            
            // Személyzet kiíratása
            $szemelyzet = '';
            foreach ( $select_szemelyzet as $szemelyzet ) {
                if( $munkalap['szemelyzet_id'] && $szemelyzet['value'] == $munkalap['szemelyzet_id'] ) {
                    $szemelyzet = $szemelyzet['name'];
                    break;
                }
            }
            // Ha külső kivitelező, akkor a partner nevét is odaírjuk
            if( $munkalap['szemelyzet_id'] == KULSO_KIVITELEZO ) {
                $szemelyzet .= ': '.getErtek('nev', 'szallito', 'id', $munkalap['szallito_id']);
            }
            
            $szamla_html .= nl2('
                <div class="dtr nSor">
                    <div class="dtc"><a href="munkalap.php?id=x&amp;modosit&amp;mezo=ikt_szam&amp;op=equal&amp;felt='.$munkalap['ikt_szam'].'" target="_blank">'.$munkalap['ikt_szam'].'</a></div>
                    <div class="dtc">
                        '.erteklista(ERTEKLISTA_MUNKALAP_TIP, $munkalap['tipus_id']).'
                    </div>
                    <div class="dtc">'.$szemelyzet.'</div>
                    <div class="dtc">'.getStatText('munkalap', $munkalap['stat_id']).'</div>
                    <div class="dtc"><span class="icon link" onclick=""> <i class="material-icons" style="font-size: 2rem">delete</i></span></div>
                </div>
            ');
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

	// Garanciális munkák
    } elseif( isset($_GET['garancialis']) ) {

        $szamla_html .= nl2('
            <div class="dt naplo">
                <div class="dtr nFejlec">
                    <div class="dtc">Munkalap szám</div>
                    <div class="dtc">Helyiség</div>
                    <div class="dtc">Külső kivitelező</div>
                    <div class="dtc">Leírás</div>
                </div>
        ');
        
        $projekt            = $adatbazis_kapcsolat->s2('ikt_szam', 'projektkezeles', "`recid`='{$_GET['id']}'");
        $munkalapok         = $adatbazis_kapcsolat->s('ikt_szam obj_id gep_id szallito_id text', 'munkalap', "`pr_szam`='{$projekt['ikt_szam']}' AND `stat_id`!='".MUNKALAP_STAT_TOROLT."' AND `stat_id`!='".MUNKALAP_STAT_SZTORNO."'");
        foreach ( $munkalapok as $munkalap ) {
            
            $szamla_html .= nl2('
                <div class="dtr nSor">
                    <div class="dtc"><a href="munkalap.php?id=x&amp;modosit&amp;mezo=ikt_szam&amp;op=equal&amp;felt='.$munkalap['ikt_szam'].'" target="_blank">'.$munkalap['ikt_szam'].'</a></div>
                    <div class="dtc">
                        '.getErtek('nev', 'obj', 'recid', $munkalap['obj_id']).'
                        <br>'.getErtek('nev', 'eszkozkezeles', 'recid', $munkalap['gep_id']).'
                    </div>
                    <div class="dtc">'.getErtek('nev', 'szallito', 'id', $munkalap['szallito_id']).'</div>
                    <div class="dtc">'.nl2br($munkalap['text']).'</div>
                </div>
            ');
        }
        
        // Archiv munkalapokat is járjuk be
        $munkalapok_arch = $adatbazis_kapcsolat->s('ikt_szam obj_id gep_id szallito_id text', 'munkalap_arch', "`pr_szam`='{$projekt['ikt_szam']}' AND `stat_id`!='".MUNKALAP_STAT_TOROLT."' AND `stat_id`!='".MUNKALAP_STAT_SZTORNO."'");
        foreach ( $munkalapok_arch as $munkalap ) {
            
            $szamla_html .= nl2('
                <div class="dtr nSor">
                    <div class="dtc"><a href="munkalap_arch.php?id=x&amp;modosit&amp;mezo=ikt_szam&amp;op=equal&amp;felt='.$munkalap['ikt_szam'].'" target="_blank">'.$munkalap['ikt_szam'].'</a></div>
                    <div class="dtc">
                        '.getErtek('nev', 'obj', 'recid', $munkalap['obj_id']).'
                        <br>'.getErtek('nev', 'eszkozkezeles', 'recid', $munkalap['gep_id']).'
                    </div>
                    <div class="dtc">'.getErtek('nev', 'szallito', 'id', $munkalap['szallito_id']).'</div>
                    <div class="dtc">'.nl2br($munkalap['text']).'</div>
                </div>
            ');
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


	// Dokumentumok
    } elseif( isset($_GET['dokumentumok']) ) {
        
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
    } elseif (isset($_GET['megjegyzesek'])) {

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
                        <div class="dtc">'.htmlspecialchars_decode( nl2br2($sor['megj']) ).'</div>
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
