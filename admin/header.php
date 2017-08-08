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

//Home ikon, csak ha már beléptünk
if($_SESSION[get_db_tabla().'user_id']){
    $menuk .=
            nl2('
                <li class="link home">
                    <a href="index.php"><i class="material-icons">home</i></a>
                </li>
            ');
}

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
if(substr( basename(self()), 0, strlen(basename($_SERVER['PHP_SELF']))-4 ) == $megn){
    $style = ' class="hover{STYLE_KIEG}"';
}

if( $aktmenuk ) {
    if( $style ) {
        $style = str_replace('{STYLE_KIEG}', ' almenus', $style);
    } else {
        $style = ' class="almenus"';
    }
} else {
    if( $style ) {
        $style = str_replace('{STYLE_KIEG}', '', $style);
    }
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
    $style = ' class="hover{STYLE_KIEG}"';
}

if( $aktmenuk ) {
    if( $style ) {
        $style = str_replace('{STYLE_KIEG}', ' almenus', $style);
    } else {
        $style = ' class="almenus"';
    }
} else {
    if( $style ) {
        $style = str_replace('{STYLE_KIEG}', '', $style);
    }
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
    $style = ' class="hover{STYLE_KIEG}"';
}

if( $aktmenuk ) {
    if( $style ) {
        $style = str_replace('{STYLE_KIEG}', ' almenus', $style);
    } else {
        $style = ' class="almenus"';
    }
} else {
    if( $style ) {
        $style = str_replace('{STYLE_KIEG}', '', $style);
    }
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
    $style = ' class="hover{STYLE_KIEG}"';
}

if( $aktmenuk ) {
    if( $style ) {
        $style = str_replace('{STYLE_KIEG}', ' almenus', $style);
    } else {
        $style = ' class="almenus"';
    }
} else {
    if( $style ) {
        $style = str_replace('{STYLE_KIEG}', '', $style);
    }
}

$menuk   .= str_replace( $akt_menu_mit, array( $style, $aloldalak['0'], $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );*/


/* Bejelentések */
if( _jog(0) ) {
    $menu_nev        = 'Bejelentések';
    $menu_nev_kis    = 'bejelentések';
    $menu_nev_tobbes = 'Bejelentések';
    $megn            = 'bejelentes';
    $aloldalak       = array($megn.'.php');
    if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
    //if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = 'Új bejelentés feltöltése'; }
    if(basename(self())==$megn.'.php'){ $oldalNev = '<span id="rekord_neve">'.$menu_nev.'</span><span class="dn" id="rekord_neve_egyes">bejelentés</span><span class="dn" id="rekord_neve_targy">bejelentést</span>'; }

    $aktmenuk  = '';
    $style = '';
    if(substr( basename(self()), 0, strlen(basename($_SERVER['PHP_SELF']))-4 ) == $megn){
        $style = ' class="hover{STYLE_KIEG}"';
    }

    if( $aktmenuk ) {
        if( $style ) {
            $style = str_replace('{STYLE_KIEG}', ' almenus', $style);
        } else {
            $style = ' class="almenus"';
        }
    } else {
        if( $style ) {
            $style = str_replace('{STYLE_KIEG}', '', $style);
        }
    }

    $menuk .= str_replace( $akt_menu_mit, array( $style, $aloldalak['0'], $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );
}

/* Munkák */
if( _jog(3) ) {
    $menu_nev        = 'Munkák';
    $menu_nev_kis    = 'munkák';
    $menu_nev_tobbes = 'Munkák';
    $megn            = 'munkalap';
    $aloldalak = array(
                $megn.'.php',
                'utemezett_feladatok.php',
                'rendszeres_karbantartas.php',
                'eseti_fenntartas.php',
                'uzemeltetesi_naplo.php',
    );
    if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
    if(basename(self())=='munkalap.php')                { $oldalNev = 'Munkák / <span id="rekord_neve">Munkalapok</span><span class="dn" id="rekord_neve_egyes">munkalap</span><span class="dn" id="rekord_neve_targy">munkalapot</span>';  }
    if(basename(self())=='utemezett_feladatok.php')     { $oldalNev = 'Munkák / Ütemzett feladatok';                        }
    if(basename(self())=='rendszeres_karbantartas.php') { $oldalNev = 'Munkák / Rendszeres karbantartás';                   }
    if(basename(self())=='eseti_fenntartas.php')        { $oldalNev = 'Munkák / Eseti fenntartás';                          }
    if(basename(self())=='uzemeltetesi_naplo.php')      { $oldalNev = 'Munkák / Üzemeltetési napló';                        }

    $aktmenuk  = '';
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Munkalapok'                 ), $almenu_sablon );
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], 'Ütemezett feladatok'        ), $almenu_sablon );
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['2'], 'Rendszeres karbantartás'    ), $almenu_sablon );
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['3'], 'Eseti fenntartás'           ), $almenu_sablon );
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['4'], 'Üzemeltetési napló'         ), $almenu_sablon );

    $style = '';
    if(substr( basename(self()), 0, strlen(basename($_SERVER['PHP_SELF']))-4 ) == $megn){
        $style = ' class="hover{STYLE_KIEG}"';
    }

    if( $aktmenuk ) {
        if( $style ) {
            $style = str_replace('{STYLE_KIEG}', ' almenus', $style);
        } else {
            $style = ' class="almenus"';
        }
    } else {
        if( $style ) {
            $style = str_replace('{STYLE_KIEG}', '', $style);
        }
    }

    $menuk   .= str_replace( $akt_menu_mit, array( $style, '#'/*$aloldalak['0']*/, $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );
}


/* Anyagok */
if(_jog(JOG_ANYAIGENLES_MEGTEKINT) || _jog(JOG_ANYAGREND_MEGTEKINT) || _jog(JOG_RAKTAR_MEGTEKINT) || _jog(JOG_CIKKTORZS_MEGTEKINT)){
    $menu_nev        = 'Anyagok';
    $menu_nev_kis    = 'anyagok';
    $menu_nev_tobbes = 'Anyagok';
    $megn            = 'anyagok';
    $aloldalak       = array('cikktorzs.php', 'anyagigenyles.php', 'anyagigenyles.php?anyagrend', 'raktar.php');
    if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
    if(basename(self())=='cikktorzs.php')           { $oldalNev = 'Anyagok / Cikktörzs';        }
    if(basename(self())=='anyagigenyles.php')       {
                                                        if(isset($_GET['anyagrend'])){
                                                            $oldalNev = 'Anyagok / Anyagrendelések';
                                                        }else{
                                                            $oldalNev = 'Anyagok / Anyagigénylések';
                                                        }
                                                    }
    if(basename(self())=='raktar.php')              { $oldalNev = 'Anyagok / Raktár';           }

    $aktmenuk  = '';
if(_jog(12)){
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], 'Anyagigénylések'), $almenu_sablon );
}
if(_jog(16)){
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['2'], 'Anyagrendelések'), $almenu_sablon );
}
if(_jog(17)){
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['3'], 'Raktár'         ), $almenu_sablon );
}
if(_jog(19)){
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Cikktörzs'      ), $almenu_sablon );
}

    $style = '';
    if(substr( basename(self()), 0, strlen(basename($_SERVER['PHP_SELF']))-4 ) == $megn){
        $style = ' class="hover{STYLE_KIEG}"';
    }

    if( $aktmenuk ) {
        if( $style ) {
            $style = str_replace('{STYLE_KIEG}', ' almenus', $style);
        } else {
            $style = ' class="almenus"';
        }
    } else {
        if( $style ) {
            $style = str_replace('{STYLE_KIEG}', '', $style);
        }
    }

    $menuk   .= str_replace( $akt_menu_mit, array( $style, '#'/*$aloldalak['0']*/, $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );
}

/* Eszközök */
$menu_nev        = 'Eszközök';
$menu_nev_kis    = 'eszközök';
$menu_nev_tobbes = 'Eszközök';
$megn            = 'eszkozkezeles';
$aloldalak       = array($megn.'.php', 'felmeresi_terv.php');
if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
if(basename(self())=='eszkozkezeles.php')   { $oldalNev = 'Eszközök / Eszközkezelés';       }
if(basename(self())=='felmeresi_terv.php')  { $oldalNev = 'Eszközök / Felmérési tervek';    }

$aktmenuk  = '';
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Eszközkezelés'      ), $almenu_sablon );
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], 'Felmérési tervek'   ), $almenu_sablon );

$style = '';
if(substr( basename(self()), 0, strlen(basename($_SERVER['PHP_SELF']))-4 ) == $megn){
    $style = ' class="hover{STYLE_KIEG}"';
}

if( $aktmenuk ) {
    if( $style ) {
        $style = str_replace('{STYLE_KIEG}', ' almenus', $style);
    } else {
        $style = ' class="almenus"';
    }
} else {
    if( $style ) {
        $style = str_replace('{STYLE_KIEG}', '', $style);
    }
}

$menuk   .= str_replace( $akt_menu_mit, array( $style, '#'/*$aloldalak['0']*/, $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );


/* Tervezés */
$menu_nev        = 'Tervezés';
$menu_nev_kis    = 'tervezés';
$menu_nev_tobbes = 'Tervezés';
$megn            = 'tervezes';
$aloldalak       = array($megn.'.php');
if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
if(basename(self())==$megn.'.php'){ $oldalNev = $menu_nev; }

$aktmenuk  = '';
//$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Gépek'  ), $almenu_sablon );

$style = '';
if(substr( basename(self()), 0, strlen(basename($_SERVER['PHP_SELF']))-4 ) == $megn){
    $style = ' class="hover{STYLE_KIEG}"';
}

if( $aktmenuk ) {
    if( $style ) {
        $style = str_replace('{STYLE_KIEG}', ' almenus', $style);
    } else {
        $style = ' class="almenus"';
    }
} else {
    if( $style ) {
        $style = str_replace('{STYLE_KIEG}', '', $style);
    }
}

$menuk   .= str_replace( $akt_menu_mit, array( $style, $aloldalak['0'], $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );


/* Energia */
$menu_nev        = 'Energia';
$menu_nev_kis    = 'energia';
$menu_nev_tobbes = 'Energia';
$megn            = 'energia';
$aloldalak       = array($megn.'.php');
if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
if(basename(self())==$megn.'.php'){ $oldalNev = $menu_nev; }

$aktmenuk  = '';
//$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Gépek'  ), $almenu_sablon );

$style = '';
if(substr( basename(self()), 0, strlen(basename($_SERVER['PHP_SELF']))-4 ) == $megn){
    $style = ' class="hover{STYLE_KIEG}"';
}

if( $aktmenuk ) {
    if( $style ) {
        $style = str_replace('{STYLE_KIEG}', ' almenus', $style);
    } else {
        $style = ' class="almenus"';
    }
} else {
    if( $style ) {
        $style = str_replace('{STYLE_KIEG}', '', $style);
    }
}

$menuk   .= str_replace( $akt_menu_mit, array( $style, $aloldalak['0'], $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );



/* Projektek */
$menu_nev        = 'Projektek';
$menu_nev_kis    = 'rojektek';
$menu_nev_tobbes = 'Projektek';
$megn            = 'projektigenyles';
$aloldalak       = array($megn.'.php', 'projektkezeles.php', 'cash_flow.php');
if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
if(basename(self())=='projektigenyles.php') { $oldalNev = 'Projektek / Projektigénylések';  }
if(basename(self())=='projektkezeles.php')  { $oldalNev = 'Projektek / Projektkezelés';     }
if(basename(self())=='cash_flow.php')       { $oldalNev = 'Projektek / Cash Flow';          }

$aktmenuk  = '';
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Projektigénylések'  ), $almenu_sablon );
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], 'Projektkezelés'     ), $almenu_sablon );
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['2'], 'Cash-flow'          ), $almenu_sablon );

$style = '';
if(substr( basename(self()), 0, strlen(basename($_SERVER['PHP_SELF']))-4 ) == $megn){
    $style = ' class="hover{STYLE_KIEG}"';
}

if( $aktmenuk ) {
    if( $style ) {
        $style = str_replace('{STYLE_KIEG}', ' almenus', $style);
    } else {
        $style = ' class="almenus"';
    }
} else {
    if( $style ) {
        $style = str_replace('{STYLE_KIEG}', '', $style);
    }
}

$menuk   .= str_replace( $akt_menu_mit, array( $style, '#'/*$aloldalak['0']*/, $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );



/* Továbbiak */
$menu_nev        = 'Dokumentumok';
$menu_nev_kis    = 'dokumentumok';
$menu_nev_tobbes = 'Dokumentumok';
$megn            = 'fenntartasi_szerzodes';
$aloldalak       = array($megn.'.php', 'szamla.php', 'szallito.php');
if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
if(basename(self())=='fenntartasi_szerzodes.php')   { $oldalNev = 'Dokumentumok / Fenntartási szerződések'; }
if(basename(self())=='szamla.php')                  { $oldalNev = 'Dokumentumok / Számlák';                 }
if(basename(self())=='szallito.php')                { $oldalNev = 'Dokumentumok / Partnertörzs';            }

$aktmenuk  = '';
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Fenntartási szerződések'), $almenu_sablon );
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], 'Számlák'                ), $almenu_sablon );
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['2'], 'Partnertörzs' ), $almenu_sablon );

$style = '';
if(substr( basename(self()), 0, strlen(basename($_SERVER['PHP_SELF']))-4 ) == $megn){
    $style = ' class="hover{STYLE_KIEG}"';
}

if( $aktmenuk ) {
    if( $style ) {
        $style = str_replace('{STYLE_KIEG}', ' almenus', $style);
    } else {
        $style = ' class="almenus"';
    }
} else {
    if( $style ) {
        $style = str_replace('{STYLE_KIEG}', '', $style);
    }
}

$menuk   .= str_replace( $akt_menu_mit, array( $style, '#'/*$aloldalak['0']*/, $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );



/* Személyzet */
if(_jog(25) || _jog(27) || _jog(29)){
    $menu_nev        = 'Személyzet';
    $menu_nev_kis    = 'személyzet';
    $menu_nev_tobbes = 'Személyzet';
    $megn            = 'torzsadat';
    $aloldalak       = array($megn.'.php', 'szabadsagigeny.php', 'beosztas.php');
    if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
    if(basename(self())=='torzsadat.php')       { $oldalNev = 'Személyzet / Törzsadatok';       }
    if(basename(self())=='szabadsagigeny.php')  { $oldalNev = 'Személyzet / Szabadságigények';  }
    if(basename(self())=='beosztas.php')        { $oldalNev = 'Személyzet / Beosztások';        }

    $aktmenuk  = '';
    if(_jog(25)){
        $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Törzsadatok'        ), $almenu_sablon );
    }
    if(_jog(27)){
        $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], 'Szabadságigények'   ), $almenu_sablon );
    }
    if(_jog(29)){
        $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['2'], 'Beosztások'         ), $almenu_sablon );
    }

    $style = '';
    if(substr( basename(self()), 0, strlen(basename($_SERVER['PHP_SELF']))-4 ) == $megn){
        $style = ' class="hover{STYLE_KIEG}"';
    }

    if( $aktmenuk ) {
        if( $style ) {
            $style = str_replace('{STYLE_KIEG}', ' almenus', $style);
        } else {
            $style = ' class="almenus"';
        }
    } else {
        if( $style ) {
            $style = str_replace('{STYLE_KIEG}', '', $style);
        }
    }

    $menuk   .= str_replace( $akt_menu_mit, array( $style, '#'/*$aloldalak['0']*/, $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );
}



/* Rendszer beállítások */
$menu_nev        = 'Rendszer';
$menu_nev_kis    = 'rendszer';
$menu_nev_tobbes = 'Rendszer';
$megn            = 'rendszer';
$aloldalak = array(
    $megn.'.php?rkat=1',
    $megn.'.php?rkat=2',
    'szallito.php',
    'ktghely.php',
    'ktgnem.php',
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

if(basename(self())==$megn.'.php'   && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
if(basename(self())=='szallito.php' && isset($_GET['modosit'])){ $navi = 'Szállító szerkesztése'; }
if(basename(self())=='rendszer.php'   && !$_GET['rkat'])    { $oldalNev = 'Rendszer';                   }
if(basename(self())=='bejovo_szamlak.php')                  { $oldalNev = 'Rendszer / Bejövő számlák';  }
if(basename(self())=='erteklista.php')                      { $oldalNev = 'Rendszer / Értéklisták';     }
if(basename(self())=='login.php')                           { $oldalNev = 'Rendszer / Felhasználók';    }
if(basename(self())=='szerepkor.php')                       { $oldalNev = 'Rendszer / Szerepkörök';     }
if(basename(self())=='ktghely.php')                         { $oldalNev = 'Rendszer / Költséghelyek';   }
if(basename(self())=='ktgnem.php')                          { $oldalNev = 'Rendszer / Költségnemek';    }

$aktmenuk  = '';
//$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Áfa' ), $almenu_sablon );
//$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], 'Beosztás' ), $almenu_sablon );
if(_jog(8)){
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['3'], 'Költséghelyek' ), $almenu_sablon );
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['4'], 'Költségnemek' ), $almenu_sablon );
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['6'], 'Értéklisták' ), $almenu_sablon );
}
if(_jog(6)){
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['7'], 'Felhasználók' ), $almenu_sablon );
}
if(_jog(10)){
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['8'], 'Szerepkörök' ), $almenu_sablon );
}
$aktmenuk .= str_replace( $akt_almenu_mit, array( '../tervek/" target="_blank', 'Design' ), $almenu_sablon );

$style = '';
if(substr( basename(self()), 0, strlen(basename($_SERVER['PHP_SELF']))-4 ) == $megn){
    $style = ' class="hover{STYLE_KIEG}"';
}

if( $aktmenuk ) {
    if( $style ) {
        $style = str_replace('{STYLE_KIEG}', ' almenus', $style);
    } else {
        $style = ' class="almenus"';
    }
} else {
    if( $style ) {
        $style = str_replace('{STYLE_KIEG}', '', $style);
    }
}

$menuk   .= str_replace( $akt_menu_mit, array( $style, '#', $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );


/** További telepítések figyelése, menedzselése egy külön fájlban*/
if(is_file('!telepitesek.php')){
    require_once('!telepitesek.php');
}

/** Tényleges tartalom */
$header_html = '';
$head = $weblap -> head_keszit();

$head -> setDocType ( get_doctype() );
$head -> setTitle   ( get_title() );
$head -> setDesc    ( '' );
$head -> setKeywords( '' );
$head -> charset       = get_web_charset();
$head -> noskype       = true;
$head -> favicon       = '../'.get_favicon();
$head -> country       = 'hu';
$head -> language      = 'hu';
$head -> js_css        = get_jsfajlok_admin();
$head -> extrak        = '<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">';
$head -> extrak       .= '<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">';

$header_html .= $head -> getHtml();

$body_class = '';
if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
    $body_class = ' class="teljesMagas"';
}

$header_html .= '<body'.$body_class;
    //Új feltöltésnél a név mezőn legyen a fókusz
    if( isset($_GET['uj']) ) {
        if( basename(self()) == 'ktghely.php' || basename(self()) == 'ktgnem.php' ) {
            $header_html .=' onload="if(document.getElementById(\'id\')){document.getElementById(\'id\').focus();}"';
        } else {
            $header_html .=' onload="if(document.getElementById(\'nev\')){document.getElementById(\'nev\').focus();}"';
        }
    //Listánál a gyoskeresőn legyen a fókusz
    } elseif( isset($_GET['lista']) ) {
        $header_html .=' onload="if(document.getElementById(\'id\')){document.getElementById(\'id\').focus();}"';
    //Többképes képfeltöltésnél ugrás a képekhez
    }


$header_html .='>';

/* A form körüli keretete a lista nézetben nem jelenítjük meg */
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

$tbalw      = '';
$tjobbw     = '';
$csuszkadn  = ' dn';
if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
    if( $_COOKIE['csuszkaSzelesseg'] ) {
        $tbalw  = ' style="width: '.$_COOKIE['csuszkaSzelesseg'].'px;"';
        $tjobbw = ' style="width: calc(100% - '.($_COOKIE['csuszkaSzelesseg'] + 16).'px);"';
    }

    $csuszkadn  = '';
}


$self = basename(self());
$megn = str_replace('.php', '', basename(self()) );

/* Bal oldali nav panel select-je */
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

            //Cikktorzsnél az ID legyen cikkszám
            $rend_mezo_txt = getOszlopNev($rend_mezo);
            if(basename(self())=='cikktorzs.php' && $rend_mezo=='id'){
                $rend_mezo_txt = 'Cikkszám';
            }

            $rendezes .= '<option value="'.$rend_mezo.'"'.$selected.'>'.$rend_mezo_txt.'</option>';

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
    //Aktívnál fordítva van
    if($_GET['rend']=='aktiv' || (basename(self())=='utemezett_feladatok.php' && !$_GET['rend']) ){
        $_OPTS['order'] .= ' desc';
        $rend_irany = '<a id="rendezesIrany" href="javascript: bejegyzesekLista( \'#rendezesSelect\', \'desc\', \''.$_GET['rkat'].'\' ); void(0);">▼ <span>Z-A</span></a>';
        if( $_GET['irany'] && $_GET['irany'] != 'desc' ) {
            $rend_irany = '<a id="rendezesIrany" href="javascript: bejegyzesekLista( \'#rendezesSelect\', \'desc\', \''.$_GET['rkat'].'\' ); void(0);">▲ <span>A-Z</span></a>';
        }
    }else{
        $rend_irany = '<a id="rendezesIrany" href="javascript: bejegyzesekLista( \'#rendezesSelect\', \'desc\', \''.$_GET['rkat'].'\' ); void(0);">▼ <span>Z-A</span></a>';
        if( $_GET['irany'] && $_GET['irany'] == 'desc' ) {
            $rend_irany = '<a id="rendezesIrany" href="javascript: bejegyzesekLista( \'#rendezesSelect\', \'asc\', \''.$_GET['rkat'].'\' ); void(0);">▲ <span>A-Z</span></a>';

            if( $_GET['rend'] && in_array($_GET['rend'], $rend_mezok) ) {
                $_OPTS['order'] .= ' desc';
            }
        }
    }

/* ....*/

    $tartBal  = szamla_lista();

    if (isset($_GET['gyorskereso_ajax'])) {
        echo $tartBal;
        $weblap -> leallit();
        exit;
    }

    /* Új ikonra kattintva mi történik */
    $keresodn = '';
    if( isset($_GET['szakterulet']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'szakterulet\'); void(0);';
    } elseif( basename(self()) == 'anyagigenyles.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';
    } elseif( basename(self()) == 'anyagigenyles.php' && !isset($_GET['megjegyzesek']) && !_jog(13) ) {
        $uj_link = 'javascript: alert2(\'Önnen nincs jogosultsága új anyagigénylés feltöltéséhez!\', \'\', \'Hiba\'); void(0);';
    } elseif( basename(self()) == 'anyagigenyles.php' && isset($_GET['anyagrend']) ) {
        $uj_link = 'javascript: alert2(\'Új anyagigénylés csak az anyagigénylés menüpont alatt tölthető fel!\', \'\', \'Hiba\'); void(0);';
    } elseif( basename(self()) == 'bejelentes.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';
    } elseif( basename(self()) == 'beosztas.php' && isset($_GET['naptar']) ) {
        $uj_link = 'javascript: $(\'#szerk_form div.dt\').show(); void(0)';
    } elseif( basename(self()) == 'cikktorzs.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';
    } elseif( isset($_GET['szallodak']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'szallodak\'); void(0);';
    } elseif( basename(self()) == 'utemezett_feladatok.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';
    } elseif( basename(self()) == 'munkalap.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';
    } elseif( basename(self()) == 'rendszeres_karbantartas.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';
    } elseif( basename(self()) == 'eseti_fenntartas.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';
    } elseif( basename(self()) == 'raktar.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';
    } elseif( basename(self()) == 'eszkozkezeles.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';
    } elseif( basename(self()) == 'tervezes.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';
    } elseif( basename(self()) == 'energia.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';
    } elseif( basename(self()) == 'projektigenyles.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';
    } elseif( basename(self()) == 'projektkezeles.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';
    } elseif( basename(self()) == 'fenntartasi_szerzodes.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';
    } elseif( basename(self()) == 'szamla.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';
    } elseif( basename(self()) == 'szallito.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';
    } elseif( basename(self()) == 'torzsadat.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';
    } elseif( basename(self()) == 'beosztas.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';
    } elseif( basename(self()) == 'login.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';
    } else {
        $uj_link = self().'?uj';
    }

    //Diszpécser nem hozhat létre új bejegyzést
    if( !isset($_GET['megjegyzesek']) && basename(self()) == 'bejelentes.php' && _jog(JOG_DISZPECSER)  ) {
        //$uj_link = 'javascript: alert2(\'Diszpécserként nem hozhat létre új bejelentést!\'); void(0);';
    }

}

$felh_nev = '';
if( $_SESSION[get_db_tabla().'user'] ) {
    $felhasznalo = $adatbazis_kapcsolat->s('recid nev teljes_nev reszleg_id', 'login', 'nev='.$_SESSION[get_db_tabla().'user'], '', '1');

    if( $felhasznalo ) {
        $felhasznalo = $felhasznalo[0];

        /* Felhasználó név */
        $felh_nev = $felhasznalo['teljes_nev'];

        $reszleg = erteklista(17, $felhasznalo['reszleg_id']);
    }
}



/* SZŰRÉS
 *  $szures nevű változót állítja elő, benne a szűréssel kapcsolatos funkciókkal (selectek, feltételek)
 */
$nem_kell_szuro = array('ktghely', 'ktgnem', 'szerepkor');
if( !in_array($megn, $nem_kell_szuro)){
    require_once 'szures.php';
}

/* NYOMTATÁS */
require_once 'nyomtatas.php';





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
    <div class="dtc nav c felh">
        <a href="javascript: felh_valt( true ); void(0);">'.$felh_nev.'</a>
        <br><span class="f11">'.$reszleg.'</span>
    </div>
    <div class="dtc nav kilep">
        <a href="beleptetes.php?kilep"><i class="material-icons">exit_to_app</i></a>
    </div>
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

/* Törlés és új gombok ne is látsszon, ha nincs hozzá joga */
$torles_icon = nl2('
        <a onclick="naviTorles();" class="torles'.$torol_dis.'" title="Törlés">
            <i class="material-icons">delete</i>
        </a>
    ');

$uj  = nl2('
        <a href="'.$uj_link.'" class="uj'.$uj_dis.'" title="Új">
            <i class="material-icons">add</i>
        </a>
    ');

if(basename(self())=='bejelentes.php' && !_jog(1)){
    $torles_icon    = '';
    $uj             = '';

}

if(basename(self())=='utemezett_feladatok.php' && !_jog(4)){
    $torles_icon    = '';
    $uj             = '';
}

if(basename(self())=='login.php' && !_jog(7)){
    $torles_icon    = '';
    $uj             = '';
}

if(basename(self())=='erteklista.php' && !_jog(9)){
    $torles_icon    = '';
    $uj             = '';
}

if(basename(self())=='erteklista.php' && !_jog(11)){
    $torles_icon    = '';
    $uj             = '';
}

if( basename(self())=='anyagigenyles.php' && !_jog(13)){
    $torles_icon    = '';
    //$uj             = '';
}

if( basename(self())=='raktar.php' && !_jog(18)){
    $torles_icon    = '';
    $uj             = '';
}

if( basename(self())=='cikktorzs.php' && !_jog(20)){
    $torles_icon    = '';
    $uj             = '';
}

if( basename(self())=='torzsadat.php' && !_jog(26)){
    $torles_icon    = '';
    $uj             = '';
}

if( basename(self())=='szabadsagigeny.php' && !_jog(28)){
    $torles_icon    = '';
    $uj             = '';
}

if( basename(self())=='beosztas.php' && !_jog(30)){
    $torles_icon    = '';
    $uj             = '';
}

$header_sablon = fajlbol_olvas('../sablon/admin/header.html', true);
$header_html .= str_replace(
    array('{CSS}', '{CEGNEV}', '{MENUK}', '{SZURES}', '{TBALW}', '{TJOBBW}', '{TARTBAL}', '{TABLA}', '{KERESODN}', '{OLDALNEV}', '{RENDEZES}', '{REND_IRANY}', '{REND_CHANGE}', '{MEGN}', '{SELF}', '{GET_GET}', '{SZERK_DIS}', '{BELEPVE}', '{LAPOZAS}', '{KSZ}',      '{TORLES}',   '{UJ}', '{CSUSZKADN}' ),
    array( $css,    get_nev(),  $menuk,    $szures,    $tbalw,    $tjobbw,    $tartBal,    $tabla,    $keresodn,    $oldalNev,    $rendezes,    $rend_irany,    $rend_change,    $megn,    $self,    get_get(),   $szerk_dis,    $belepve,    $lapozas,    $_GET['ksz'], $torles_icon, $uj,    $csuszkadn   ),
    $header_sablon
);


//Nyomtatási popup
$header_html .= nl2('
<div class="dn">
    <div id="nyomtatasi_nezet">
        Nyomtatási nézet választás...
        <br><a href="'.self().get_get().'&amp;print=lista{KSZ}" target="_blank">Listanézet</a>
        <br><a href="#" target="_blank">B nézet</a>
        <br><a href="#" target="_blank">C nézet</a>
    </div>
</div>
    ');
//Jogosultság tomb JS-be
$jogok = '';
foreach ($_SESSION[get_db_tabla().'rights'] as $jog){
    $jogok .= $jog;
}
$header_html .= nl2('
<script>
    var _jogok = \''.$jogok.'\';
</script>
    ');


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
        $felirat = '<h1 style="color: #fff">Helytelen felhasználó név vagy jelszó!</h1>';
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
