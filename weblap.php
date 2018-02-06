<?php
// Ha $db_prefix nem üres (tehát használunk adatbázist), akkor a következők végrehajtás kötelező
require_once('config.php');
require_once('engine.php');
require_once('class/class.Weblap.php');
require_once('class/class.Adatbazis.php');
require_once('class/class.Head.php');
require_once('class/html/class.Img.php');

if(is_file('statusz_text.php')){
    require_once('statusz_text.php');
}
// Css-ben lévő képelemeket DATA-URI-ra cseréljük
if (isset($_GET['css_img_uri'])) {

    $js_css_tomb = get_jsfajlok();
    foreach($js_css_tomb['css'] as $css){
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

// Ha $db_prefix nem üres (tehát használunk adatbázist), akkor a következők végrehajtás kötelező
if($weblap -> db_prefix){
    $adatbazis_kapcsolat = $weblap -> adatbazis;
	$adatbazis_kapcsolat -> csatlakozas(get_db_host(), get_db_user(), get_db_pass(), get_db());
	$adatbazis_kapcsolat -> setCharset(get_db_charset());
	$adatbazis_kapcsolat -> debug = get_debug();             //Ez nem kötelező. Ha igaz, akkor sikertelen lekérdezés esetén kiírja a parancsot
    $db_kapcs = $adatbazis_kapcsolat -> adatbazis_kapcsolat; // mysqli_query-hez (ha megkell adni magát a kapcsolatot is)

    //SQL injection ellen
    foreach($_GET as $kulcs => $get){
        $_GET[$kulcs] = sql_ertek($_GET[$kulcs]);
    }
    foreach($_POST as $kulcs => $post){
        $_POST[$kulcs] = sql_ertek($_POST[$kulcs]);
    }
}

//Majd a weblap indulhat, ekkor tölthető ki a meta (később már lesz kimenet)
    require_once('esemenyek.php');	//Ezt ne töröld, még ha üres is, kell, mert különben az admin nem működik
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
//function get_tart($azon, $nyelv = ''){
//    global $tartalom_tomb;
//    if(!$nyelv){
//        $nyelv = $_SESSION['nyelv'];
//    }
//    return $tartalom_tomb[$azon][$nyelv];
//}

// Kiléptetés, ha nem él a SESSION és nem az index.php-n vagyunk.
if( !$_SESSION[get_db_tabla().'user'] && basename( $_SERVER['PHP_SELF'] )!='index.php' && basename( $_SERVER['PHP_SELF'] )!='beleptetes.php' ){
    echo 'SESKIL<script>document.body.innerHTML = \'\'; location.href=\'index.php\'</script>';
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

// Ha olyan $_GET['id']-t akarnék megnézni, ami az aktuális SQL-nek nem is eredménye, akkor kinullázzuk: ne lehessen leskelődni
if($_GET['id']){
    szamla_lista('', '', '', 'csak SQL miatt'); // Ezt azért futtatjuk le, hogy az aktuális oldalnak megfelelő $_SESSION['akt_sql'] jöjjön létre, hogy tudjunk ID-t ellenőrizni.
    $van_getid = false;
    $akt_sql = mysqli_query($db_kapcs, $_SESSION['akt_sql']);
    while( $akt_sor = mysqli_fetch_assoc($akt_sql) ){
        if( $akt_sor['recid']== $_GET['id'] ){
            $van_getid = true;
        }
    }
    if(!$van_getid){
        $_GET['id'] = '';
    }
}

// Ha jóváhagyás joggal léptünk az anyagigénylésekhez, és nem módossítottunk még a szűrésen, akkor alapból leszűrünk az árazva státuszra. 
// Viszont emiatt kell egy get szűrés töröl is, hogy tudjuk mikor kapcsolták ki szándékosan.
if (isset($_GET['szures_torol'])) {
    $_SESSION['szures_torol'] = 1;
    hd(basename(self()).'?tablazat');
}
if( empty($_POST) && basename(self()) == 'anyagigenyles.php' && !$_GET['anyagrend'] && _jog(JOG_ANYAGIGENYLES_JOVAHAGY) && !$_GET['mezo'] && !$_SESSION['szures_torol']){
    header('Location:'.self().get_get('', 'nem &amp;').'&mezo=stat_id&op=equal&felt='.CIKKIGN_STAT_ARAZVA);
}