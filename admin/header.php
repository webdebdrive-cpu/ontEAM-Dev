<?php
/*      MENÜK LÉTREHOZÁSA                                                                                               *\

    A header.php-t elsősorban a lenyíló menük létrehozásához használjuk. A kód a sablonok beolvasásával kezdődik, majd ez
    után törénik meg a menüpontok meghatározása. Majd csak ez után következik header-t előállító valódi kód.

    Menü létrehozása:

    1. A sablonok beolvasása után található kikommentezve minta menüket. Ezzel valójában nem kell foglalkozni. Jobb az
       utána következő Teszt menüpontot átírni, másolni. (a kikommentezett menük speciális esetekre példa, úgy mint
       elszínezett vagy almenüpont nélküli menük)

    2. Új menü hozzáadása után a // {KOVETKEZO_MENU} megjegyzés maradjon a menüdefiníciók mögött (automatizált menüpont
       hozzáadása miatt)

\*                                                                                                                      */
require_once('../weblap.php');
require_once('beleptetes.php'); //Ezzel tudod a beléptetést aktiválni. Részletek a beleptetes.php-ban
require_once('esemenyek.php');

if( isset($_GET['szerkeszt']) && $_GET['id'] ) {
    $tabla = str_replace('.php', '', basename(self()));

    /* Megnézzük, hogy van e érvényes sor */
    $record = $adatbazis_kapcsolat->s('recid', $tabla, 'recid='.$_GET['id'], '', '1');

    if( $record ) {
        /* Kezdeményezett művelet */
        $muvelet = 'A(z) '.$tabla.' '.$_GET['id'].'. recordjának szerkesztését kezdeményezte.';

        /* Végrehajtott változtatás */
        $valtozas = '-';

        $adatbazis_kapcsolat -> i(
            'log',
            array( 'felh_id',                          'nev',       'valtozas'),
            array( $_SESSION[get_db_tabla().'user_id'], $muvelet,   $valtozas )
        );
    }
}

$menuk           = '';
$menu_sablon     = fajlbol_olvas('../sablon/admin/menu.html',   true);
$almenu_sablon   = fajlbol_olvas('../sablon/admin/almenu.html', true);

$akt_almenu_mit  = array('{LINK}', '{NEV}');
$akt_menu_mit    = array('{CLASS}', '{FOLINK}', '{MEGNEVEZES}', '{FONEV}', '{ALMENUK}');

/*//Minta menü
$menu_nev        = 'Hír';
$menu_nev_kis    = 'hír';
$menu_nev_tobbes = 'Hírek';
$meg             = 'hir';
$aloldalak=array($meg.'.php?uj&amp;unset', $meg.'.php?lista&amp;unset', $meg.'.php?modosit');
if(basename(self())==$meg.'.php' && isset($_GET['uj'])){      $navi = 'Új '.$menu_nev_kis.' feltöltése'; }
if(basename(self())==$meg.'.php' && isset($_GET['lista'])){   $navi = $menu_nev_tobbes; }
if(basename(self())==$meg.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
if(basename(self())==$meg.'.php' && isset($_GET['sorrend'])){ $navi = 'Sorrendezés'; }

$aktmenuk  = '';
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Új '.$menu_nev_kis.' feltöltése' ), $almenu_sablon );
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], $menu_nev.' szerkesztése' ), $almenu_sablon );

$style = '';
if(substr( basename(self()), 0, strlen(basename($_SERVER['PHP_SELF']))-4 ) == $meg){
    $style = ' class="hover"';
}
$menuk   .= str_replace( $akt_menu_mit, array( $style, $aloldalak['1'], $meg, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );

//Ha nincs lenyíló menü, akkor az alábbi JS-el le kell venni a div borderjét, hogy ne legyen ott az 1x rolloverkor
//$menuk   .= '<li><script>$(document).ready(function(){$("#'.$meg.'_menu").css(\'border\', \'0px\')});</script></li>'.str_replace( $akt_menu_mit, array( $style, $aloldalak['1'], $meg, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );



//Színezett menüre példa:
//Kilépés
$menu_nev        = '';
$menu_nev_kis    = '';
$menu_nev_tobbes = 'Kilépés';
$megn            = '';
$aloldalak=array('index.php?kilep', $megn.'.php?lista&amp;dj', $megn.'.php?modosit');

$style = '';
$aktmenuk  = '';
$menuk   .= str_replace( $akt_menu_mit, array( $style, $aloldalak['0'], $meg, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );

//Pirosozzuk be:
$menuk = str_replace('<a href="index.php?kilep" onmouseover="mopen(\'_menu\')" onmouseout="mclosetime()">', '<a href="index.php?kilep" onmouseover="mopen(\'_menu\')" onmouseout="mclosetime()" style="color: #FF8383">', $menuk);




$menu_nev        = 'Teszt';
$menu_nev_kis    = 'teszt';
$menu_nev_tobbes = 'Tesztek - töröld!';
$megn            = 'teszt';
$aloldalak=array($megn.'.php?uj&amp;unset', $megn.'.php?lista&amp;unset', $megn.'.php?modosit');
if(basename(self())==$megn.'.php' && isset($_GET['uj'])){      $navi = 'Új '.$menu_nev_kis.' feltöltése'; }
if(basename(self())==$megn.'.php' && isset($_GET['lista'])){   $navi = $menu_nev_tobbes; }
if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
if(basename(self())==$megn.'.php' && isset($_GET['sorrend'])){ $navi = 'Sorrendezés'; }

$aktmenuk  = '';
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Új '.$menu_nev_kis.' feltöltése' ), $almenu_sablon );
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], $menu_nev.' szerkesztése' ), $almenu_sablon );

$style = '';
if(substr( basename(self()), 0, strlen(basename($_SERVER['PHP_SELF']))-4 ) == $megn){
    $style = ' class="hover"';
}
$menuk   .= str_replace( $akt_menu_mit, array( $style, $aloldalak['1'], $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );
*/

//{KOVETKEZO_MENU}

/* Bejövő számlák */
$menu_nev        = 'Bejövő számlák';
$menu_nev_kis    = 'bejövő számlák';
$menu_nev_tobbes = 'Bejövő számlák';
$megn            = 'bejovo_szamlak';
$aloldalak=array($megn.'.php');
if(basename(self())==$megn.'.php' && isset($_GET['uj'])){      $navi = 'Új '.$menu_nev_kis.' feltöltése'; }
if(basename(self())==$megn.'.php' && isset($_GET['lista'])){   $navi = $menu_nev_tobbes; }
if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
if(basename(self())==$megn.'.php' && isset($_GET['sorrend'])){ $navi = 'Sorrendezés'; }
if(basename(self())==$megn.'.php'){ $oldalNev = $menu_nev; }

$aktmenuk  = '';
$style = '';
if(substr( basename(self()), 0, strlen(basename($_SERVER['PHP_SELF']))-4 ) == $megn){
    $style = ' class="hover"';
}

$menuk   .= str_replace( $akt_menu_mit, array( $style, $aloldalak['0'], $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );

/* Kimenő számlák */
/*$menu_nev        = 'Kimenő számlák';
$menu_nev_kis    = 'kimenő számlák';
$menu_nev_tobbes = 'Kimenő számlák';
$megn            = 'kimeno_szamlak';
$aloldalak=array($megn.'.php');
if(basename(self())==$megn.'.php' && isset($_GET['uj'])){      $navi = 'Új '.$menu_nev_kis.' feltöltése'; }
if(basename(self())==$megn.'.php' && isset($_GET['lista'])){   $navi = $menu_nev_tobbes; }
if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
if(basename(self())==$megn.'.php' && isset($_GET['sorrend'])){ $navi = 'Sorrendezés'; }
if(basename(self())==$megn.'.php'){ $oldalNev = $menu_nev; }

$aktmenuk  = '';
$style = '';
if(substr( basename(self()), 0, strlen(basename($_SERVER['PHP_SELF']))-4 ) == $megn){
    $style = ' class="hover"';
}

$menuk   .= str_replace( $akt_menu_mit, array( $style, $aloldalak['0'], $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );*/

/* Rendszer beállítások */
$menu_nev        = 'Rendszer';
$menu_nev_kis    = 'rendszer';
$menu_nev_tobbes = 'Rendszer';
$megn            = 'rendszer';
$aloldalak = array(
    $megn.'.php?lista&amp;unset&amp;rkat=1',
    $megn.'.php?lista&amp;unset&amp;rkat=2',
    'szallito.php?lista&amp;unset&amp',
    $megn.'.php?lista&amp;unset&amp;rkat=4',
    $megn.'.php?lista&amp;unset&amp;rkat=5',
    $megn.'.php?lista&amp;unset&amp;rkat=6'
);

/* Rendszer katgória lekérdezése */
$rkat = '';
if( $_GET['rkat'] || $_SESSION['rendszerkat'] ) {
    if( $_GET['rkat'] ) {
        $_SESSION['rendszerkat'] = $_GET['rkat'];
    }

    $rendszerKat = $adatbazis_kapcsolat->s('recid nev', 'rendszer_kat', 'recid='.$_SESSION['rendszerkat'], '', '1');

    if( $rendszerKat ) {
        $rendszerKat = $rendszerKat[0];

        $rkat = $rendszerKat['nev'];
    }
}

if(basename(self())==$megn.'.php' && isset($_GET['uj'])){      $navi = 'Új '.mb_strtolower($rkat, 'UTF-8').' feltöltése'; }
if(basename(self())==$megn.'.php' && isset($_GET['lista'])){   $navi = $menu_nev_tobbes; }
if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
if(basename(self())==$megn.'.php' && isset($_GET['sorrend'])){ $navi = 'Sorrendezés'; }
if(basename(self())==$megn.'.php'){ $oldalNev = $menu_nev; }

if(basename(self())=='szallito.php' && isset($_GET['uj'])){      $navi = 'Új Szállító feltöltése'; }
if(basename(self())=='szallito.php' && isset($_GET['lista'])){   $navi = 'Szállítók'; }
if(basename(self())=='szallito.php' && isset($_GET['modosit'])){ $navi = 'Szállító szerkesztése'; }
if(basename(self())=='szallito.php' && isset($_GET['sorrend'])){ $navi = 'Sorrendezés'; }
if(basename(self())=='szallito.php'){ $oldalNev = $menu_nev; }

$aktmenuk  = '';
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Áfa' ), $almenu_sablon );
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], 'Beosztás' ), $almenu_sablon );
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['2'], 'Szállítók' ), $almenu_sablon );
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['3'], 'Költséghelyek' ), $almenu_sablon );
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['4'], 'Költségnemek' ), $almenu_sablon );
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['5'], 'Értéklisták' ), $almenu_sablon );

$style = '';
if(substr( basename(self()), 0, strlen(basename($_SERVER['PHP_SELF']))-4 ) == $megn){
    $style = ' class="hover"';
}

$menuk   .= str_replace( $akt_menu_mit, array( $style, $aloldalak['0'], $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );

/* Log */
/*$menu_nev        = 'Log';
$menu_nev_kis    = 'log';
$menu_nev_tobbes = 'Log';
$megn            = 'log';
$aloldalak=array($megn.'.php?lista&amp;unset');
if(basename(self())==$megn.'.php' && isset($_GET['uj'])){      $navi = 'Új '.$menu_nev_kis.' feltöltése'; }
if(basename(self())==$megn.'.php' && isset($_GET['lista'])){   $navi = $menu_nev_tobbes; }
if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
if(basename(self())==$megn.'.php' && isset($_GET['sorrend'])){ $navi = 'Sorrendezés'; }
if(basename(self())==$megn.'.php'){ $oldalNev = $menu_nev; }

$aktmenuk  = '';
$style = '';
if(substr( basename(self()), 0, strlen(basename($_SERVER['PHP_SELF']))-4 ) == $megn){
    $style = ' class="hover"';
}

$menuk   .= str_replace( $akt_menu_mit, array( $style, $aloldalak['0'], $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );*/

/** Telepítéssel kapcsolatos funkciók */
/*Telepítés már lefutott
if(isset($_GET['telepit'])){

	//"+A "-val kezdődő fájlok átnevezése
    $admin_prefix  = '+A ';
	$utvonalak     = array('../', '../css/', '../js/', '../'.get_utvonal());

	foreach($utvonalak as $utvonal){

		$konyvtar = opendir($utvonal);

		while( false !== ($elem = readdir($konyvtar)) ){

			//Ha nem saját vagy szülő könyvtár, nem index.jpg|gif|png és nem is kicsinyített verzió
			if( $elem != '.' && $elem != '..' ){

				if(substr($elem, 0, 3) == $admin_prefix){
					$ujfajl = str_replace($admin_prefix, '', $elem);
					rename($utvonal.$elem, $utvonal.$ujfajl);
				}

			}
		}

	}
	closedir($konyvtar);

	//Header.php kódjának átírása:
	$header_mit   = array(
        fajlbol_olvas('../sablon/admin/telepites/header_mit1.php'),
        fajlbol_olvas('../sablon/admin/telepites/header_mit2.php'),
    );
	$header_mivel = array(
        fajlbol_olvas('../sablon/admin/telepites/header_mivel1.php'),
        fajlbol_olvas('../sablon/admin/telepites/header_mivel2.php'),
    );

	fajlba_mentes('header.php', str_replace($header_mit, $header_mivel, fajlbol_olvas('header.php')) );

	header('Location:index.php');
    $weblap -> leallit();
    exit();
}
/*Csak hogy legyen majd egy záró komment, amikor a fenti blokkol kikomnetezzük telepítésnél*/

/** További telepítések figyelése, menedzselése egy külön fájlban*/
if(is_file('!telepitesek.php')){
    require_once('!telepitesek.php');
}

/** Tényleges tartalom */
$header_html = '';
$head = $weblap -> head_keszit();

if(get_alapnyelv()=='hu'){
    $title_prefix = 'Admin oldal - ';
}else{
    $title_prefix = 'Backend - ';
}

$head -> setDocType ( get_doctype() );
$head -> setTitle   ( $tit.$title_prefix.' '.get_domain() );
$head -> setDesc    ( '' );
$head -> setKeywords( '' );
$head -> charset       = get_web_charset();
$head -> noskype       = true;
$head -> favicon       = '../'.get_favicon();
$head -> country       = 'hu';
$head -> language      = 'hu';
$head -> js_css        = get_jsfajlok_admin();
$head -> extrak        = '<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">';

$header_html .= $head -> getHtml();

$body_class = '';
if( basename(self()) == 'bejovo_szamlak.php' || basename(self()) == 'kimeno_szamlak.php' ) {
    $body_class = ' class="teljesMagas"';
}

$header_html .= '<body'.$body_class;
    //Új feltöltésnél a név mezőn legyen a fókusz
    if( isset($_GET['uj']) ) {
        $header_html .=' onload="if(document.getElementById(\'nev\')){document.getElementById(\'nev\').focus();}"';
    //Listánál a gyoskeresőn legyen a fókusz
    } elseif( isset($_GET['lista']) ) {
        $header_html .=' onload="if(document.getElementById(\'id\')){document.getElementById(\'id\').focus();}"';
    //Többképes képfeltöltésnél ugrás a képekhez
    }


$header_html .='>';

/* A form körüli keretete a lista nézetben nem lenítjük meg */
$css = '';
if (!isset($_GET['lista']) && basename(self())!='index.php') {
    $css = ' keret';
}
/* Fotógaléria elrendezése miatt */
if(basename(self()) == 'fotoalbumok.php') {
    $css = ' keret_nopadd';
}

if( basename(self()) == 'bejovo_szamlak.php' || basename(self()) == 'kimeno_szamlak.php' ) {
    $css = ' kethasabos';
}

$tbalw  = '';
$tjobbw = '';
if( basename(self()) == 'bejovo_szamlak.php' || basename(self()) == 'kimeno_szamlak.php' ) {
    if( $_COOKIE['csuszkaSzelesseg'] ) {
        $tbalw  = ' style="width: '.$_COOKIE['csuszkaSzelesseg'].'px;"';
        $tjobbw = ' style="width: calc(100% - '.$_COOKIE['csuszkaSzelesseg'].'px);"';
    }
}

$tartBal  = '';
$keresodn = ' dn';
$rendezes = '';
if( basename(self()) == 'bejovo_szamlak.php' || basename(self()) == 'kimeno_szamlak.php' ) {
    $tabla = str_replace('.php', '', basename(self()));

    /* Rendezés */
    $rend_mezok = $adatbazis_kapcsolat->s('`column_name`', '`information_schema`.columns', '`table_schema` LIKE  \''.get_db().'\' AND  `table_name` LIKE  \''.get_db_tabla().$tabla.'\'', '', '');
    $rend_mezok = adatbazis2tomb($rend_mezok, 'column_name');

    $rendezes = '';
    foreach($rend_mezok as $rend_mezo) {
        $selected = '';
        if( $_GET['rend'] == $rend_mezo ) {
            $selected = ' selected="selected"';
        }

        $rendezes .= '<option value="'.$rend_mezo.'"'.$selected.'>'.getOszlopNev($rend_mezo).'</option>';
    }

    /* Rendezés onchange linkje */
    $irany = '';
    if( $_GET['irany'] ) {
        $irany = ', '.$_GET['irany'];
    }

    $rend_change = 'bejegyzesekLista( this'.$irany.' );';

    $order = 'erkezett desc';
    if( $_GET['rend'] && in_array($_GET['rend'], $rend_mezok) ) {
        $order = $_GET['rend'];
    }

    /* Rendezési irány */
    $rend_irany = '<a id="rendezesIrany" href="javascript: bejegyzesekLista( \'#rendezesSelect\', \'desc\' ); void(0);">▲</a>';
    if( $_GET['irany'] && $_GET['irany'] == 'desc' ) {
        $rend_irany = '<a id="rendezesIrany" href="javascript: bejegyzesekLista( \'#rendezesSelect\', \'asc\' ); void(0);">▼</a>';

        if( $_GET['rend'] && in_array($_GET['rend'], $rend_mezok) ) {
            $order .= ' desc';
        }
    }

    $tartBal  = szamla_lista( $tabla, '', $order );
    $keresodn = '';
}

$felh_nev = '';
if( $_SESSION[get_db_tabla().'user'] ) {
    $felhasznalo = $adatbazis_kapcsolat->s('id nev', 'login', 'nev='.$_SESSION[get_db_tabla().'user'], '', '1');

    if( $felhasznalo ) {
        $felhasznalo = $felhasznalo[0];

        /* Felhasználó név */
        $felh_nev = $felhasznalo['nev'];
    }
}

if( $oldalNev ) {
    $oldalNev = ' - '.$oldalNev;
}

$header_sablon = fajlbol_olvas('../sablon/admin/header.html', true);
$header_html .= str_replace(
    array('{CSS}', '{CEGNEV}', '{MENUK}', '{TBALW}', '{TJOBBW}', '{TARTBAL}', '{TABLA}', '{KERESODN}', '{FELH_NEV}', '{OLDALNEV}', '{RENDEZES}', '{REND_IRANY}', '{REND_CHANGE}'),
    array( $css,    get_nev(),  $menuk,    $tbalw,    $tjobbw,    $tartBal,    $tabla,    $keresodn,    $felh_nev,    $oldalNev,    $rendezes,    $rend_irany,    $rend_change  ),
    $header_sablon
);

echo $header_html.fajlbol_olvas('../sablon/admin/js_beagyazasok.html', true);

if( $_SESSION[get_db_tabla().'user'] ) {
    $felirat = '<h1>Kérjük azonosítsd magad</h1>';

    echo str_replace(
        array('{FELIRAT}', '{SELF}'),
        array( $felirat,    self() ),
        fajlbol_olvas('../sablon/admin/beleptet_popup.html')
    );
}
