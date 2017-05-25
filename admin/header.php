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
$aloldalak       = array($megn.'.php');
if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
if(basename(self())==$megn.'.php'){ $oldalNev = $menu_nev; }

$aktmenuk  = '';
$style = '';
if(substr( basename(self()), 0, strlen(basename($_SERVER['PHP_SELF']))-4 ) == $megn){
    $style = ' class="hover"';
}

//$menuk   .= str_replace( $akt_menu_mit, array( $style, $aloldalak['0'], $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );

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
    $megn.'.php?rkat=1',
    $megn.'.php?rkat=2',
    'szallito.php',
    $megn.'.php?rkat=4',
    $megn.'.php?rkat=5',
    $megn.'.php?rkat=1,2',
    'erteklista.php',
    'login.php',
    'szerepkor.php'
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

        $oldalNev = $rendszerKat['nev'];
    }
}

if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
if(basename(self())==$megn.'.php' && !$_GET['rkat']){ $oldalNev = $menu_nev; }
if(basename(self())=='szallito.php'     && isset($_GET['modosit'])){ $navi = 'Szállító szerkesztése'; }
if(basename(self())=='szallito.php'      ) { $oldalNev = 'Szállítók'; }
if(basename(self())=='bejovo_szamlak.php') { $oldalNev = 'Bejövő számlák'; }
if(basename(self())=='rendszer.php' && !$_GET['rkat']) { $oldalNev = 'Rendszer'; }
if(basename(self())=='erteklista.php'        ) { $oldalNev = 'Értéklisták'; }
if(basename(self())=='login.php'         ) { $oldalNev = 'Felhasználók'; }
if(basename(self())=='szerepkor.php'     ) { $oldalNev = 'Szerepkörök'; }

$aktmenuk  = '';
//$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Áfa' ), $almenu_sablon );
//$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], 'Beosztás' ), $almenu_sablon );
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['2'], 'Szállítók' ), $almenu_sablon );
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['3'], 'Költséghelyek' ), $almenu_sablon );
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['4'], 'Költségnemek' ), $almenu_sablon );
//$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['5'], 'Értéklisták' ), $almenu_sablon );
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['6'], 'Értéklisták' ), $almenu_sablon );
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['7'], 'Felhasználók' ), $almenu_sablon );
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['8'], 'Szerepkör' ), $almenu_sablon );

$style = '';
if(substr( basename(self()), 0, strlen(basename($_SERVER['PHP_SELF']))-4 ) == $megn){
    $style = ' class="hover"';
}

$menuk   .= str_replace( $akt_menu_mit, array( $style, '', $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );


/* Bejelentések */
$menu_nev        = 'Bejelentések';
$menu_nev_kis    = 'bejelentések';
$menu_nev_tobbes = 'Bejelentések';
$megn            = 'bejelentes';
$aloldalak       = array($megn.'.php');
if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
if(basename(self())==$megn.'.php'){ $oldalNev = $menu_nev; }

$aktmenuk  = '';
$style = '';
if(substr( basename(self()), 0, strlen(basename($_SERVER['PHP_SELF']))-4 ) == $megn){
    $style = ' class="hover"';
}

$menuk   .= str_replace( $akt_menu_mit, array( $style, $aloldalak['0'], $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );


/* Munkák */
$menu_nev        = 'Munkák';
$menu_nev_kis    = 'munkák';
$menu_nev_tobbes = 'Munkák';
$megn            = 'munkak';
$aloldalak = array(
            $megn.'.php',
            'utemezett_feladatok.php',
);
if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
if(basename(self())==$megn.'.php'){ $oldalNev = $menu_nev; }

$aktmenuk  = '';
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Munkalapok'                 ), $almenu_sablon );
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], 'Ütemezett feladatok'        ), $almenu_sablon );
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['2'], 'Rendszeres karbantartás'    ), $almenu_sablon );
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['3'], 'Eseti fenntartás'           ), $almenu_sablon );

$style = '';
if(substr( basename(self()), 0, strlen(basename($_SERVER['PHP_SELF']))-4 ) == $megn){
    $style = ' class="hover"';
}

$menuk   .= str_replace( $akt_menu_mit, array( $style, ''/*$aloldalak['0']*/, $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );


/* Anyagok */
$menu_nev        = 'Anyagok';
$menu_nev_kis    = 'anyagok';
$menu_nev_tobbes = 'Anyagok';
$megn            = 'anyagok';
$aloldalak       = array('cikktorzs.php');
if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
if(basename(self())==$megn.'.php'){ $oldalNev = $menu_nev; }

$aktmenuk  = '';
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Cikktörzs'  ), $almenu_sablon );
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], 'Raktár'     ), $almenu_sablon );

$style = '';
if(substr( basename(self()), 0, strlen(basename($_SERVER['PHP_SELF']))-4 ) == $megn){
    $style = ' class="hover"';
}

$menuk   .= str_replace( $akt_menu_mit, array( $style, ''/*$aloldalak['0']*/, $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );


/* Eszközök */
$menu_nev        = 'Eszközök';
$menu_nev_kis    = 'eszközök';
$menu_nev_tobbes = 'Eszközök';
$megn            = 'eszkozok';
$aloldalak       = array($megn.'.php');
if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
if(basename(self())==$megn.'.php'){ $oldalNev = $menu_nev; }

$aktmenuk  = '';
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Gépek'  ), $almenu_sablon );

$style = '';
if(substr( basename(self()), 0, strlen(basename($_SERVER['PHP_SELF']))-4 ) == $megn){
    $style = ' class="hover"';
}

$menuk   .= str_replace( $akt_menu_mit, array( $style, ''/*$aloldalak['0']*/, $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );


/* Log */
/*$menu_nev      = 'Log';
$menu_nev_kis    = 'log';
$menu_nev_tobbes = 'Log';
$megn            = 'log';
$aloldalak=array($megn.'.php?lista&amp;unset');
if(basename(self())==$megn.'.php' && isset($_GET['uj'])){      $navi = 'Új '.$menu_nev_kis.' feltöltése'; }
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
if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
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
if( !isset($_GET['lista']) && basename(self())!='index.php' ) {
    $css = ' keret';
}
/* Fotógaléria elrendezése miatt */
if( basename(self()) == 'fotoalbumok.php' ) {
    $css = ' keret_nopadd';
}

if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
    $css = ' kethasabos';
}

$tbalw  = '';
$tjobbw = '';
if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
    if( $_COOKIE['csuszkaSzelesseg'] ) {
        $tbalw  = ' style="width: '.$_COOKIE['csuszkaSzelesseg'].'px;"';
        $tjobbw = ' style="width: calc(100% - '.$_COOKIE['csuszkaSzelesseg'].'px);"';
    }
}

$tartBal  = '';
$keresodn = ' dn';
$rendezes = '';
$uj_link  = '';
if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
    $tabla = str_replace('.php', '', basename(self()));

    /* Rendezés */
    $rend_mezok = $adatbazis_kapcsolat->s('`column_name`', '`information_schema`.columns', '`table_schema` LIKE  \''.get_db().'\' AND  `table_name` LIKE  \''.get_db_tabla().$tabla.'\'', '', '');
    $rend_mezok = adatbazis2tomb($rend_mezok, 'column_name');

    $rendezes = '';
    $i = 1;
    foreach($rend_mezok as $rend_mezo) {
        if( in_array($rend_mezo, $_OPTS['rendezes']) ) {
            $selected = '';
            if( $_GET['rend'] == $rend_mezo ) {
                $selected = ' selected="selected"';
            }

            $rendezes .= '<option value="'.$rend_mezo.'"'.$selected.'>'.getOszlopNev($rend_mezo).'</option>';

            $i++;
        }
    }

    /* Rendezés onchange linkje */
    $irany = '';
    if( $_GET['irany'] ) {
        $irany = ', \''.$_GET['irany'].'\'';
    }

    $rend_change = 'bejegyzesekLista( this'.$irany.' );';

    if( $_GET['rend'] && in_array($_GET['rend'], $rend_mezok) ) {
        $_OPTS['order'] = $_GET['rend'];
    }

    /* Rendezési irány */
    $rend_irany = '<a id="rendezesIrany" href="javascript: bejegyzesekLista( \'#rendezesSelect\', \'desc\', \''.$_GET['rkat'].'\' ); void(0);">▲ <span>A-Z</span></a>';
    if( $_GET['irany'] && $_GET['irany'] == 'desc' ) {
        $rend_irany = '<a id="rendezesIrany" href="javascript: bejegyzesekLista( \'#rendezesSelect\', \'asc\', \''.$_GET['rkat'].'\' ); void(0);">▼ <span>Z-A</span></a>';

        if( $_GET['rend'] && in_array($_GET['rend'], $rend_mezok) ) {
            $_OPTS['order'] .= ' desc';
        }
    }

    $where = '';
    if( $tabla == 'rendszer' ) {
        if( $_GET['rkat'] ) {
            if( strstr($_GET['rkat'], ',') ) {
                $where = "`rkat` IN (".$_GET['rkat'].")";
            } else {
                $where = "`rkat` = '".$_GET['rkat']."'";
            }
        }
    }

    if( $where ) {
        $where .= " AND `torolve` = '0'";
    } else {
        $where = "`torolve` = '0'";
    }

    //Lapozás előkészítése
    require_once('../class/class.Lapozo.php');

    if(!$_GET['oldal']){
        $_GET['oldal'] = 1;
    }

    $_GET['epo'] = 50;

    $bejegyzesek = $adatbazis_kapcsolat->s($_OPTS['select'], $tabla, $where, $_OPTS['order'], '');

    $lapozo = new Lapozo;
    $lapozo -> elem_osszes = count($bejegyzesek);
    $lapozo -> mind_var         = ''; //'Összes elem kilistázása' változó neve. Ha üres akkor nincs össsez oldal megjelenítés. Alapértelmezetten 'osszes'
    //$lapozo -> epo_var         = 'epo';    //Elem per oldal változó neve
    //$lapozo -> oldal_var       = 'oldal';  //Oldal változó neve
    $lapozo -> kozep_meret     = 5;        //Nagy oldalszám esetén a középső intervallum mérete
    //$lapozo -> max_fix_oldal   = 10;        //Ha a megadott számtól több oldal van, akkor jelenik meg az előző/követekző felirat
    //$lapozo -> epo_array       = array(50, 100, 150);  //Elem per oldal mennyiségek ha választhatóak, akkor egy tömbbyi interrel adhatjuk át az opciókat
    //$lapozo -> mind_text       = 'Összes oldal';  //pldalszámok mellett a "minden elem kilistázásának" felirata (ha $mind_var üres string akkor nem jelenik ez meg)
    //$lapozo -> elozo_title     = '« Előző oldal';        //'Előző' felirat, link title-jéhez
    //$lapozo -> kovet_title     = 'Következő oldal »';    //'Következő' felirat, link title-jéhez
    //$lapozo -> kozep_meret     = 'Aktuális oldal';       //'Aktuális oldal' felirat, link title-jéhez

    $utvonal = '../sablon/admin/';
    $lapozo -> sablon_elozo       = $utvonal.'lapozas_elozo.html';
    $lapozo -> sablon_kovetkezo   = $utvonal.'lapozas_kovet.html';
    $lapozo -> sablon_oldalak     = $utvonal.'lapozas_oldalak.html';
    $lapozo -> sablon_option      = $utvonal.'lapozas_option.html';
    $lapozo -> sablon_pontok      = $utvonal.'lapozas_pontok.html';
    $lapozo -> sablon_select      = $utvonal.'lapozas_select.html';

    /* Számolás */
    $lapozo -> lapozas();

    $lapozas = $lapozo -> kiir();

    $tartBal  = szamla_lista( $tabla, $_OPTS['select'], $where, $_OPTS['order'], $lapozo -> getLimit() );
    $keresodn = '';

    if( isset($_GET['szakterulet']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'szakterulet\'); void(0);';
    } elseif( isset($_GET['szallodak']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'szallodak\'); void(0);';
    } else {
        $uj_link = self().'?uj';
    }
}

$felh_nev = '';
if( $_SESSION[get_db_tabla().'user'] ) {
    $felhasznalo = $adatbazis_kapcsolat->s('recid nev', 'login', 'nev='.$_SESSION[get_db_tabla().'user'], '', '1');

    if( $felhasznalo ) {
        $felhasznalo = $felhasznalo[0];

        /* Felhasználó név */
        $felh_nev = $felhasznalo['nev'];
    }
}

$self = basename(self());

$torol_dis = '';
if( !isset($_GET['modosit']) ) {
    $torol_dis = ' disabled';
}

$szerk_dis = '';
if( (isset($_GET['modosit']) && isset($_GET['szerkeszt'])) || !isset($_GET['modosit']) ) {
    $szerk_dis = ' disabled';
}

$uj_dis = '';
if( isset($_GET['uj']) ) {
    $uj_dis = ' disabled';
}

$belepve = nl2('
    <div class="dtc nav"><a href="javascript: felh_valt( true ); void(0);">'.$felh_nev.'</a></div>
    <div class="dtc nav"><a href="beleptetes.php?kilep">Kilépés</a></div>
');

if( $_SESSION['nincs_jelsz'] ) {
    $menuk    = '';
    $oldalNev = 'Jelszó csere';
    $belepve  = '';
} elseif( !$_SESSION[get_db_tabla().'user_id'] ) {
    $menuk    = '';
    $oldalNev = 'Bejelentkezés';
    $belepve  = '';
}

$header_sablon = fajlbol_olvas('../sablon/admin/header.html', true);
$header_html .= str_replace(
    array('{CSS}', '{CEGNEV}', '{MENUK}', '{TBALW}', '{TJOBBW}', '{TARTBAL}', '{TABLA}', '{KERESODN}', '{OLDALNEV}', '{RENDEZES}', '{REND_IRANY}', '{REND_CHANGE}', '{SELF}', '{TOROL_DIS}', '{SZERK_DIS}', '{UJ_DIS}', '{BELEPVE}', '{UJ_LINK}', '{LAPOZAS}'),
    array( $css,    get_nev(),  $menuk,    $tbalw,    $tjobbw,    $tartBal,    $tabla,    $keresodn,    $oldalNev,    $rendezes,    $rend_irany,    $rend_change,    $self,    $torol_dis,    $szerk_dis,    $uj_dis,    $belepve,    $uj_link,    $lapozas  ),
    $header_sablon
);

echo $header_html.fajlbol_olvas('../sablon/admin/js_beagyazasok.html', true);

if( $_SESSION[get_db_tabla().'user_id'] ) {
    $felirat = '<h1>Bejelentkezés</h1>';

    echo str_replace(
        array('{FELIRAT}', '{SELF}'),
        array( $felirat,    self() ),
        fajlbol_olvas('../sablon/admin/beleptet_popup.html')
    );

    echo fajlbol_olvas('../sablon/admin/popup.html');
} elseif( $_SESSION['nincs_jelsz'] && $_SESSION['nincs_jelsz_felhid'] ) {
    $_SESSION['kert_url'] = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

    $felirat = '';
    if( isset($_GET['jelszoures']) ) {
        $felirat = '<h1 style="color: #fff">Kérem töltse ki a mezőket!</h1>';
    } elseif( isset($_GET['jelszonemazonos']) ) {
        $felirat = '<h1 style="color: #fff">A két jelszó nem egyezik!</h1>';
    } else {
        $felirat = '<h1>Válasszon jelszót</h1>';
    }

    $mit   = array('{FELIRAT}', '{SELF}'        );
    $mivel = array( $felirat,   'beleptetes.php');
    echo str_replace($mit, $mivel, fajlbol_olvas('../sablon/admin/nincs_jelszo.html'));
} else {
    $_SESSION['kert_url'] = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

    $felirat = '';
    if(isset($_GET['hiba'])){
        $felirat = '<h1 style="color: #fff">Helytelen felhasználói név vagy jelszó!</h1>';
    }else{
        $felirat = '<h1>Bejelentkezés</h1>';
    }

    $mit   = array('{FELIRAT}', '{SELF}'        );
    $mivel = array( $felirat,   'beleptetes.php');
    echo str_replace($mit, $mivel, fajlbol_olvas('../sablon/admin/beleptetes.html'));
}

if( !$_SESSION[get_db_tabla().'user_id'] ) {
    require_once('footer.php');
    exit();
}
