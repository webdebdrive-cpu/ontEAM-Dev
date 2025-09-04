<?php

// Ha $db_prefix nem üres (tehát használunk adatbázist), akkor a következők végrehajtás kötelező
require_once('config.php');
require_once('engine.php');
require_once('class/class.Weblap.php');
require_once('class/class.Adatbazis.php');
require_once('class/class.Head.php');
require_once('class/html/class.Img.php');

if( is_file('statusz_text.php') ) {
    require_once('statusz_text.php');
}
// Css-ben lévő képelemeket DATA-URI-ra cseréljük
if( isset($_GET['css_img_uri']) ) {

    $js_css_tomb = get_jsfajlok();
    foreach ( $js_css_tomb['css'] as $css ) {
        fajlba_mentes($css, css_img_data( fajlbol_olvas($css) ));
    }

}

$weblap = new Weblap();
// Kötelező adatok kitöltése, nyelv, session, adatbázis csatlakozás beállítása
$weblap -> alap_nyelv      = get_alapnyelv();
$weblap -> db_prefix       = get_db_tabla();
$weblap -> domain          = get_domain();
$weblap -> kep_mini_szel   = get_mini_sz();
$weblap -> kep_mini_magas  = get_mini_m();
$weblap -> kep_index_szel  = get_indexkep_sz();
$weblap -> kep_index_magas = get_indexkep_m();
$weblap -> kep_szel        = get_kep_sz();
$weblap -> kep_magas       = get_kep_m();
$weblap -> kep_nagy_szel   = get_nagykep_sz();
$weblap -> kep_nagy_magas  = get_nagykep_m();
$weblap -> mail            = get_mail();
$weblap -> mail_charset    = get_mail_charset();
$weblap -> megszolitas     = get_megsz();
$weblap -> nev             = get_nev();
$weblap -> nyelvek         = get_nyelvek();
$weblap -> nyelv_detektal  = get_nyelv_detek();
$weblap -> utvonal         = get_utvonal();
$weblap -> mail_charset    = get_mail_charset();

$weblap -> indit();

// Ha még nincs kért URL, akkor az aktuális cím lesz az (tehát az index.php)
// Session csak ettől a ponttól van
if(     !$_SESSION['kert_url']                                                  &&
        dirname($_SERVER['REQUEST_URI']) != '/andoc2'                           &&
        substr(basename($_SERVER['REQUEST_URI']), 0, 16)!='autocomplete.php'    &&
        strpos($_SERVER['REQUEST_URI'], '&no_lista=1') === false                &&
        strpos($_SERVER['REQUEST_URI'], '&sql_db') === false
    ) {
    $_SESSION['kert_url'] = get_http().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
}

// Ha $db_prefix nem üres (tehát használunk adatbázist), akkor a következők végrehajtás kötelező
if( $weblap->db_prefix ) {
    $adatbazis_kapcsolat = $weblap -> adatbazis;
	$adatbazis_kapcsolat -> csatlakozas(get_db_host(), get_db_user(), get_db_pass(), get_db());
	$adatbazis_kapcsolat -> setCharset(get_db_charset());
	$adatbazis_kapcsolat -> debug = get_debug();             // Ez nem kötelező. Ha igaz, akkor sikertelen lekérdezés esetén kiírja a parancsot
    $db_kapcs = $adatbazis_kapcsolat -> adatbazis_kapcsolat; // mysqli_query-hez (ha megkell adni magát a kapcsolatot is)

    // SQL injection ellen
    foreach ( $_GET as $kulcs => $get ) {
        $_GET[$kulcs] = sql_ertek($_GET[$kulcs]);
    }
    // Valami miatt a POST-nál ez nem jó. "valami'idézőjeles" adatot elküldve rosszul kerül eltárolsára. Nem tudtam egyelőre megoldani.
    // foreach ( $_POST as $kulcs => $post ) {
    //     $_POST[$kulcs] = sql_ertek($_POST[$kulcs]);
    // }
}

// Majd a weblap indulhat, ekkor tölthető ki a meta (később már lesz kimenet)
require_once('esemenyek.php');	// Ezt ne töröld, még ha üres is, kell, mert különben az admin nem működik
require_once('meta.php');
require_once('tartalom.php');

/**
*   Többnyelvű oldal esetére egy plusz függvény, amint ki kell venni a függvények tömbből, mert ott a taratlom.php még nem látszik
*	Get tartalom érték
*
* 	Tartalom tömbből gyors adatkinyerés. Pl.: get_tart('valami') megegyezik a $tartalom_tomb['valami'][$_SESSION['nyelv']]; értékkel
*
*	@param string $azon
*	@param string $nyelv - arra az esetre, ha nem az aktuális nyelv tartalmát szeretnénk lekérni
*
*	@return string: A tartalom tömb megfelelő bejegyzése
*/
// Kiléptetés, ha nem él a SESSION és nem az index.php-n vagyunk és nem is egy ütemezőn, nem is elfelejtett jelszót kérünk le.
$kiveteles_oldalak = array('index.php', 'import_szamla.php', 'import_szamla_kifizetes.php', 'megr_ig.php', 'beleptetes.php');
if( !$_SESSION[get_db_tabla().'user'] && !$_POST['elfelejtett_jelszo'] && substr(basename($_SERVER['PHP_SELF']), 0, 8) != 'utemezo_' && !in_array(basename($_SERVER['PHP_SELF']), $kiveteles_oldalak) && substr(basename($_SERVER['PHP_SELF']), 0, 5)!='andoc' ) {
    echo 'SESKIL<script>document.body.innerHTML = \'\'; location.href=\'index.php\'</script>';
    exit;
}
    
// Jogosultságok karbantartása
if( $_SESSION[get_db_tabla().'user'] ) {
    $felhasznalo = $adatbazis_kapcsolat->s2('flags flags_ert', 'login', "`nev`='{$_SESSION[get_db_tabla().'user']}'", '', '1');
    if( $felhasznalo['flags'] ) {
        $_SESSION[get_db_tabla().'rights']    = str_split($felhasznalo['flags']);
    }
    if( $felhasznalo['flags_ert'] ) {
        $_SESSION[get_db_tabla().'ertesites'] = str_split($felhasznalo['flags_ert']);
    }
}

// Archiv bejelentés/munkalap esetén ha nincs szűrés/összetett szűrés, akkor a legfrisseb legyen beállítva (amúgy is van rajta egy limit, ha van más szűrés, de ha nincs beállítva semmi, akkor ez látszik is).
// FONTOS, hogy ez még itt a szamla_lista() függvény meghívása előtt legyen, mert az már számol ezekkel!
// A tényleg kifejezetten Archiv modulokkal kezdjük, mert ha már lesz értéke a $_GET['mezo']-nek, a másik feltételbe nem kerülünk bele
$archivok2 = array('bejelentes_arch.php', 'munkalap_arch.php');
if( in_array(basename(self()), $archivok2) && !isset($_GET['mezo']) && !$_SESSION['o_szuro_feltetel'] ) {
    $_GET['mezo']   = 'legfrissebb';
    $_GET['op']     = 'equal';
    $_GET['felt'] = SZURES_DB_LIMIT;
}

$archivok = array('anyag_szamla.php', 'bejelentes_arch.php', 'energia_szamla.php', 'fenntartasi_szamla.php', 'fenntartasi_szerzodes.php', 'megallapodas.php', 'megallapodas_szamla.php', 'munkalap_arch.php', 'projektigenyles.php', 'projektkezeles.php', 'projektszamla.php', 'projektszerzodes.php', 'vendegejszakak.php');
if( in_array(basename(self()), $archivok) && !isset($_GET['mezo']) && !$_SESSION['o_szuro_feltetel'] ) {
    $_GET['mezo']   = 'legfrissebb';
    $_GET['op']     = 'equal';
    $_GET['felt']   = SZURES_DB_LIMIT;
}


// Anyagrendelések alatt, ha a szerepkör mszk ügyintéző vagy mszk vezető,
// és nincs szűrve semmire (összetetten sem, ez itt fontos a 0-ás alapértelmezett érték miatt), akkor nem listázunk ki semmit.
$nullas_szerepkorok = array(MSZK_VEZETO, MUSZAKI_ADMINISZTRATOR);
if( in_array($_SESSION[get_db_tabla().'szemelyzet_id'], $nullas_szerepkorok) && basename(self())=='anyagigenyles.php' && isset($_GET['anyagrend']) && !$_GET['mezo'] && !$_SESSION['o_szuro_feltetel'] ) {
    $_GET['mezo'] = 'legfrissebb';
    // Korábban ez volt, így lehetett elérni azt, hogy üres legyen a találati lista startból
    // $_GET['felt'] = '-'; // Ha csak simán 0-át írnánk ide, akkor a feltételt üresnek tekintené, olyan mintha nem létezne
    $_GET['felt'] = ANYAGREND_LIMIT; // Ha csak simán 0-át írnánk ide, akkor a feltételt üresnek tekintené, olyan mintha nem létezne
}
// Bármilyen más szereplő esetén (ugyanúgy Anyagrendelések alatt) pedig 200-as legyen a lista, ne az 1000-es
if( !in_array($_SESSION[get_db_tabla().'szemelyzet_id'], $nullas_szerepkorok) && basename(self())=='anyagigenyles.php' && isset($_GET['anyagrend']) && !$_GET['mezo'] && !$_SESSION['o_szuro_feltetel'] ) {
    $_GET['mezo'] = 'legfrissebb';
    $_GET['felt'] = ANYAGREND_NEM_MSZK_LIMIT;
}

// Ha olyan $_GET['id']-t akarnék megnézni, ami az aktuális SQL-nek nem is eredménye, akkor kinullázzuk: ne lehessen leskelődni
// Ehhez a nav panel kiszámításakor a recid-kat is kiszámoljuk egy tömbbe, hogy többet egy ilyen bonyolult SQL-t ne kelljen lefuttatni
// Ezt viszont nem tesszük meg, ha van $_POST-olt adat, mert azok ajax hívások, ott meg feleslegesen szintén ne számolgassuk ki a bonyolult SQL-t.
// Ugyanígy az autocomplete.php esetén sem hívjuk meg, meg még van pár kivételes oldal.
$kiveteles_oldalak = array('index.php', 'autocomplete.php', 'szakter_autocomplete.php', 'velemeny_autocomplete.php');
if( empty($_POST) && !in_array( basename(self()), $kiveteles_oldalak ) && !isset($_GET['no_lista']) && substr(basename($_SERVER['PHP_SELF']), 0, 5)!='andoc' ) {
    list($balNavPanelHTMLje, $balNavPanelRECIDk) = szamla_lista();          // Ezt azért futtatjuk le, hogy az aktuális oldalnak megfelelő $_SESSION['akt_sql'] jöjjön létre, hogy tudjunk ID-t ellenőrizni.
    $_SESSION[csonkit(basename(self()), 4).'_idk'] = $balNavPanelRECIDk;    // Egy Session-ben is eltároljuk az ID-kat, hogy ajax-al egy sort meghívva, ne töltse be, ha adott ID nem tartozik a felhasználó látokörébe
}

// Munkalap megrendelés nyomtatásához fontos, hogy mindenképp megmaradjon az ID
if( isset($_GET['nyomtat']) ) {
    $_GET['id_nyomtatashoz'] = $_GET['id'];
}

if( $_GET['id'] && !isset($_GET['no_lista']) ) {
    if( !in_array($_GET['id'], $balNavPanelRECIDk) ) {
        $_GET['id'] = '';
    }
}

// Ha van id, akkor ellenőrizzük, hogy én azt láthatom-e. A legfrissebbnél ezt nem tudjuk eljátszani, mert ott nincs minden id a session-ban (túl sok is volna). Itt ha rendezést állítunk (pl. Z-A), akkor nem lehetne megnyitni adott rekordot. 
if( $_GET['id'] && $_GET['mezo']!='legfrissebb' ) {
    if( !in_array($_GET['id'], $_SESSION[csonkit(basename(self()), 4).'_idk']) ){
        exit;
    }
}

// Ha jóváhagyás joggal léptünk az anyagigénylésekhez, és nem módosítottunk még a szűrésen, akkor alapból leszűrünk az árazva státuszra.
// Viszont emiatt kell egy get szűrés töröl is, hogy tudjuk mikor kapcsolták ki szándékosan.
if( isset($_GET['szures_torol']) ) {

    $_SESSION['szures_torol'] = 1;
    $kieg = '';
    if( isset($_GET['anyagrend']) ) {
        $kieg = '&anyagrend';
    }

    hd(basename(self()).'?tablazat'.$kieg);
}
if( empty($_POST) && basename(self()) == 'anyagigenyles.php' && !$_GET['anyagrend'] && _jog(JOG_ANYAGIGENYLES_JOVAHAGY) && !$_GET['mezo'] && !$_SESSION['szures_torol'] ) {
    header('Location:'.self().get_get('', 'nem &amp;').'&mezo=stat_id&op=equal&felt='.CIKKIGN_STAT_ARAZVA);
}

// Új feltöltés után, a szűrést jegyezzük meg. Ezt SESSION-ba törétnő eltárolással tesszük meg.
// Csak akkor módosítjuk a seesion-t, ha egy adatlapon vaguynk
$kiveteles_oldal = array('dokumentumtar.php', 'eszkozkezeles.php');
if( ($_GET['id'] && isset($_GET['modosit']) && !isset($_GET['no_lista'])) || (in_array(basename(self()), $kiveteles_oldal) && empty($_POST)) ){
    $_SESSION[basename(self())]['szures_mezo'] = $_GET['mezo'];
    $_SESSION[basename(self())]['szures_op']   = $_GET['op'];
    $_SESSION[basename(self())]['szures_felt'] = $_GET['felt'];
}
