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
            array( 'felh_id',                               'nev',       'valtozas'),
            array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,   $valtozas )
        );
    }
}

$menuk           = '';
$menu_sablon     = fajlbol_olvas('../sablon/admin/menu.html',   true);
$almenu_sablon   = fajlbol_olvas('../sablon/admin/almenu.html', true);

// Home ikon, csak ha már beléptünk
if($_SESSION[get_db_tabla().'login_recid']){
    $menuk .=
            nl2('
                <li class="link home">
                    <a href="index.php"><i class="material-icons">home</i></a>
                </li>
            ');
}

$akt_almenu_mit  = array('{LINK}', '{NEV}');
$akt_menu_mit    = array('{CLASS}', '{FOLINK}', '{MEGNEVEZES}', '{FONEV}', '{ALMENUK}');

// Bejövő számlák
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


// Bejelentések
if( _jog(JOG_BEJELENTES_MEGTEKINT) || _jog(JOG_BEJELENTES_SAJAT_RESZL) ) {
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
if( _jog(JOG_MUNKALAP_MEGTEKINT) || _jog(JOG_MUNKALAP_MEGTEKINT_SZALL) || _jog(JOG_UTFEL_MEGTEKINT) || _jog(JOG_RENDSZ_KARB_MEGTEKINT) || _jog(JOG_ESETI_FENN_MEGTEKINT) ) {
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
    if(basename(self())=='munkalap.php')                { $oldalNev = 'Munkák / <span id="rekord_neve">Munkalapok</span><span class="dn" id="rekord_neve_egyes">munkalap</span><span class="dn" id="rekord_neve_targy">munkalapot</span>';                                              }
    if(basename(self())=='utemezett_feladatok.php')     { $oldalNev = 'Munkák / <span id="rekord_neve">Ütemzett feladatok</span><span class="dn" id="rekord_neve_egyes">ütmezett feladat</span><span class="dn" id="rekord_neve_targy">ütemezett feladatot</span>';                     }
    if(basename(self())=='rendszeres_karbantartas.php') { $oldalNev = 'Munkák / <span id="rekord_neve">Rendszeres karbantartás</span><span class="dn" id="rekord_neve_egyes">rendszeres karbantartás</span><span class="dn" id="rekord_neve_targy">rendszeres karbantartást</span>';    }
    if(basename(self())=='eseti_fenntartas.php')        { $oldalNev = 'Munkák / <span id="rekord_neve">Eseti fenntartás</span><span class="dn" id="rekord_neve_egyes">eseti fenntartás</span><span class="dn" id="rekord_neve_targy">eseti fenntartást</span>';                         }
    if(basename(self())=='uzemeltetesi_naplo.php')      { $oldalNev = 'Munkák / <span id="rekord_neve">Üzemeltetési napló</span><span class="dn" id="rekord_neve_egyes">üzemeltetési napló</span><span class="dn" id="rekord_neve_targy">üzemeltetési naplót</span>';                   }

    $aktmenuk  = '';
    if(_jog(JOG_MUNKALAP_MEGTEKINT) || _jog(JOG_MUNKALAP_MEGTEKINT_SZALL)){
        $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Munkalapok'                 ), $almenu_sablon );
    }
    if(_jog(JOG_UTFEL_MEGTEKINT)){
        $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], 'Ütemezett feladatok'        ), $almenu_sablon );
    }
    if(_jog(JOG_RENDSZ_KARB_MEGTEKINT)){
        $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['2'], 'Rendszeres karbantartás'    ), $almenu_sablon );
    }
    if(_jog(JOG_ESETI_FENN_MEGTEKINT)){
        $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['3'], 'Eseti fenntartás'           ), $almenu_sablon );
    }
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
    if(basename(self())=='cikktorzs.php')           { $oldalNev = 'Anyagok / <span id="rekord_neve">Cikktörzs</span><span class="dn" id="rekord_neve_egyes">cikktörzs</span><span class="dn" id="rekord_neve_targy">cikktörzset</span>';        }
    if(basename(self())=='anyagigenyles.php')       {
                                                        if(isset($_GET['anyagrend'])){
                                                            $oldalNev = 'Anyagok / <span id="rekord_neve">Anyagrendelések</span><span class="dn" id="rekord_neve_egyes">anyagrendelés</span><span class="dn" id="rekord_neve_targy">anyagrendelést</span>';
                                                        }else{
                                                            $oldalNev = 'Anyagok / <span id="rekord_neve">Anyagigénylések</span><span class="dn" id="rekord_neve_egyes">anyagigénylés</span><span class="dn" id="rekord_neve_targy">anyagigénylést</span>';
                                                        }
                                                    }
    if(basename(self())=='raktar.php')              { $oldalNev = 'Anyagok / <span id="rekord_neve">Raktár</span><span class="dn" id="rekord_neve_egyes">raktár</span><span class="dn" id="rekord_neve_targy">raktárat</span>';           }

    $aktmenuk  = '';
if(_jog(JOG_ANYAIGENLES_MEGTEKINT)){
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], 'Anyagigénylések'), $almenu_sablon );
}
if(_jog(JOG_ANYAGREND_MEGTEKINT)){
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['2'], 'Anyagrendelések'), $almenu_sablon );
}
if(_jog(JOG_RAKTAR_MEGTEKINT)){
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['3'], 'Raktár'         ), $almenu_sablon );
}
if(_jog(JOG_CIKKTORZS_MEGTEKINT)){
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
if(basename(self())=='eszkozkezeles.php')   { $oldalNev = 'Eszközök / <span id="rekord_neve">Eszközkezelés</span><span class="dn" id="rekord_neve_egyes">eszközkezelés</span><span class="dn" id="rekord_neve_targy">eszközkezelést</span>';       }
if(basename(self())=='felmeresi_terv.php')  { $oldalNev = 'Eszközök / <span id="rekord_neve">Felmérési tervek</span><span class="dn" id="rekord_neve_egyes">felmérési terv</span><span class="dn" id="rekord_neve_targy">felmérési tervet</span>'; }

$aktmenuk  = '';
if(_jog(JOG_ESZKOZ_MEGTEKINT)){
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Eszközkezelés'      ), $almenu_sablon );
}
if(_jog(JOG_FT_MEGTEKINT)){
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], 'Felmérési tervek'   ), $almenu_sablon );
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
if(_jog(JOG_PRIGENY_SZ_MEGTEKINT) || _jog(JOG_PRIGENY_B_MEGTEKINT) || _jog(JOG_PRIGENY_EGY_MEGTEKINT) || _jog(JOG_PRIGENY_F_MEGTEKINT) || _jog(JOG_PRIGENY_TOBB_MEGTEKINT) || _jog(JOG_PRIGENY_T_MEGTEKINT) || _jog(JOG_PRKEZEL_MEGTEKINT) || _jog(JOG_ELOTERJ_MEGTEKINT)){
    $menu_nev        = 'Projektek';
    $menu_nev_kis    = 'rojektek';
    $menu_nev_tobbes = 'Projektek';
    $megn            = 'projektigenyles';
    $aloldalak       = array($megn.'.php', 'projektkezeles.php', 'cash_flow.php', 'eloterj_fb.php', 'eloterj_bod.php');
    if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
    if(basename(self())=='projektigenyles.php') { $oldalNev = 'Projektek / <span id="rekord_neve">Projektigénylések</span><span class="dn" id="rekord_neve_egyes">projektigénylés</span><span class="dn" id="rekord_neve_targy">projektigénylést</span>';                       }
    if(basename(self())=='projektkezeles.php')  { $oldalNev = 'Projektek / <span id="rekord_neve">Projektkezelés</span><span class="dn" id="rekord_neve_egyes">projektkezelés</span><span class="dn" id="rekord_neve_targy">projektkezelést</span>';                            }
    if(basename(self())=='cash_flow.php')       { $oldalNev = 'Projektek / <span id="rekord_neve">Cash Flow</span><span class="dn" id="rekord_neve_egyes">Cash Flow</span><span class="dn" id="rekord_neve_targy">Cash Flow-t</span>';                                          }
    if(basename(self())=='eloterj_fb.php')      { $oldalNev = 'Projektek / Előterjesztés / <span id="rekord_neve">FB előterjesztések</span><span class="dn" id="rekord_neve_egyes">FB előterjesztés</span><span class="dn" id="rekord_neve_targy">FB előterjesztést</span>';    }
    if(basename(self())=='eloterj_bod.php')     { $oldalNev = 'Projektek / Előterjesztés / <span id="rekord_neve">BoD előterjesztések</span><span class="dn" id="rekord_neve_egyes">BoD előterjesztés</span><span class="dn" id="rekord_neve_targy">BoD előterjesztést</span>'; }

    $aktmenuk  = '';
    if(_jog(JOG_PRIGENY_SZ_MEGTEKINT) || _jog(JOG_PRIGENY_B_MEGTEKINT) || _jog(JOG_PRIGENY_EGY_MEGTEKINT) || _jog(JOG_PRIGENY_F_MEGTEKINT) || _jog(JOG_PRIGENY_TOBB_MEGTEKINT) || _jog(JOG_PRIGENY_T_MEGTEKINT)){
        $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Projektigénylések'  ), $almenu_sablon );
    }
    if(_jog(JOG_PRKEZEL_MEGTEKINT)){
        $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], 'Projektkezelés'     ), $almenu_sablon );
    }
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['2'], 'Cash-flow'          ), $almenu_sablon );

    if(_jog(JOG_ELOTERJ_MEGTEKINT)){
        $aktmenuk .= nl2('
            <ul class="alalmenu_cont">
                <a class="nolink">Előterjesztések</a>
                <ul class="alalmenu">
                    <li><a href="'.$aloldalak['3'].'">FB előterjesztés</a></li>
                    <li><a href="'.$aloldalak['4'].'">BoD előterjesztés</a></li>
                </ul>
            </ul>
        ');
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



/* Továbbiak */
$menu_nev        = 'Dokumentumok';
$menu_nev_kis    = 'dokumentumok';
$menu_nev_tobbes = 'Dokumentumok';
$megn            = 'fenntartasi_szerzodes';
$aloldalak       = array($megn.'.php', 'szamla.php', 'szallito.php', 'dokumentumtar.php');
if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
if(basename(self())=='fenntartasi_szerzodes.php')   { $oldalNev = 'Dokumentumok / <span id="rekord_neve">Fenntartási szerződések</span><span class="dn" id="rekord_neve_egyes">fenntartási szerződés</span><span class="dn" id="rekord_neve_targy">fenntartási szerződést</span>';  }
if(basename(self())=='szamla.php')                  { $oldalNev = 'Dokumentumok / <span id="rekord_neve">Számlák</span><span class="dn" id="rekord_neve_egyes">számla</span><span class="dn" id="rekord_neve_targy">számlákat</span>';                                              }
if(basename(self())=='szallito.php')                { $oldalNev = 'Dokumentumok / <span id="rekord_neve">Partnertörzs</span><span class="dn" id="rekord_neve_egyes">partnertörzs</span><span class="dn" id="rekord_neve_targy">partnertörzset</span>';                              }
if(basename(self())=='dokumentumtar.php')           { $oldalNev = 'Dokumentumok / <span id="rekord_neve">Dokumentumtár</span><span class="dn" id="rekord_neve_egyes">dokumentumtár</span><span class="dn" id="rekord_neve_targy">dokumentumtárat</span>';                           }

$aktmenuk  = '';
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Fenntartási szerződések'), $almenu_sablon );
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], 'Számlák'                ), $almenu_sablon );
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['2'], 'Partnertörzs'           ), $almenu_sablon );
$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['3'], 'Dokumentumtár'          ), $almenu_sablon );

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
    if(basename(self())=='torzsadat.php')       { $oldalNev = 'Személyzet / <span id="rekord_neve">Törzsadatok</span><span class="dn" id="rekord_neve_egyes">törzsadat</span><span class="dn" id="rekord_neve_targy">törzsadatot</span>';               }
    if(basename(self())=='szabadsagigeny.php')  { $oldalNev = 'Személyzet / <span id="rekord_neve">Szabadságigények</span><span class="dn" id="rekord_neve_egyes">szabadságigény</span><span class="dn" id="rekord_neve_targy">szabadságigényt</span>'; }
    if(basename(self())=='beosztas.php')        { $oldalNev = 'Személyzet / <span id="rekord_neve">Beosztások</span><span class="dn" id="rekord_neve_egyes">beosztás</span><span class="dn" id="rekord_neve_targy">beosztást</span>';                   }

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
    'szerepkor.php',
    'szerepkor_jog.php?jogosultsag'
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

if(basename(self())==$megn.'.php'   && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése';         }
if(basename(self())=='szallito.php' && isset($_GET['modosit'])){ $navi = 'Szállító szerkesztése';           }
if(basename(self())=='rendszer.php'   && !$_GET['rkat'])    { $oldalNev = 'Rendszer';                       }
if(basename(self())=='bejovo_szamlak.php')                  { $oldalNev = 'Rendszer / <span id="rekord_neve">Bejövő számlák</span><span class="dn" id="rekord_neve_egyes">bejövő számla</span><span class="dn" id="rekord_neve_targy">bejövő számlát</span>';       }
if(basename(self())=='erteklista.php')                      { $oldalNev = 'Rendszer / <span id="rekord_neve">Értéklisták</span><span class="dn" id="rekord_neve_egyes">értéklista</span><span class="dn" id="rekord_neve_targy">értéklistát</span>';                }
if(basename(self())=='login.php')                           { $oldalNev = 'Rendszer / <span id="rekord_neve">Felhasználók</span><span class="dn" id="rekord_neve_egyes">felhasználó</span><span class="dn" id="rekord_neve_targy">felhasználót</span>';             }
if(basename(self())=='szerepkor.php')                       { $oldalNev = 'Rendszer / <span id="rekord_neve">Szerepkörök</span><span class="dn" id="rekord_neve_egyes">szerepkör</span><span class="dn" id="rekord_neve_targy">szerepkört</span>';                  }
if(basename(self())=='szerepkor_jog.php')                   { $oldalNev = 'Rendszer / <span id="rekord_neve">Szerepkörök jogai</span><span class="dn" id="rekord_neve_egyes">szerepkör jog</span><span class="dn" id="rekord_neve_targy">szerepkör jogot</span>';   }
if(basename(self())=='ktghely.php')                         { $oldalNev = 'Rendszer / <span id="rekord_neve">Költséghelyek</span><span class="dn" id="rekord_neve_egyes">költséghely</span><span class="dn" id="rekord_neve_targy">költséghelyek</span>';           }
if(basename(self())=='ktgnem.php')                          { $oldalNev = 'Rendszer / <span id="rekord_neve">Költségnemek</span><span class="dn" id="rekord_neve_egyes">költségnem</span><span class="dn" id="rekord_neve_targy">költségnemek</span>';              }

$aktmenuk  = '';
//$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Áfa' ), $almenu_sablon );
//$aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], 'Beosztás' ), $almenu_sablon );
if(_jog(JOG_ERTEKLISTAK_MEGTEKINT)){
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['3'], 'Költséghelyek' ), $almenu_sablon );
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['4'], 'Költségnemek' ), $almenu_sablon );
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['6'], 'Értéklisták' ), $almenu_sablon );
}
if(_jog(JOG_FELHASZNALOK_MEGTEKINT)){
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['7'], 'Felhasználók' ), $almenu_sablon );
}
if(_jog(JOG_SZEREPKOR_MEGTEKINT)){
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['8'], 'Szerepkörök'        ), $almenu_sablon );
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['9'], 'Szerepkörök jogai'  ), $almenu_sablon );
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

// Munkalapokkal kapcsolatos JS-eket külön fájlba szedjük össze
if(basename(self())=='munkalap.php'){
    $head -> extrak       .= '<script src="../js/munkalap.js"></script>';
}

$header_html .= $head -> getHtml();

$body_class = '';
if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
    $body_class = ' class="teljesMagas"';
}

$header_html .= '<body'.$body_class;
    // Új feltöltésnél a név mezőn legyen a fókusz
    if( isset($_GET['uj']) ) {
        if( basename(self()) == 'ktghely.php' || basename(self()) == 'ktgnem.php' ) {
            $header_html .=' onload="if(document.getElementById(\'id\')){document.getElementById(\'id\').focus();}"';
        } else if( basename(self()) == 'bejelentes.php' && isset($_GET['manualisan']) ) {
            $header_html .=' onload="if(document.getElementById(\'usr_nev\')){document.getElementById(\'usr_nev\').focus();}"';
        } else {
            $header_html .=' onload="if(document.getElementById(\'nev\')){document.getElementById(\'nev\').focus();}"';
        }
    // Listánál a gyoskeresőn legyen a fókusz
    } elseif( isset($_GET['lista']) ) {
        $header_html .=' onload="if(document.getElementById(\'id\')){document.getElementById(\'id\').focus();}"';
    // Többképes képfeltöltésnél ugrás a képekhez
    }


$header_html .='>';

// A form körüli keretete a lista nézetben nem jelenítjük meg
$css = '';
if( !isset($_GET['lista']) && basename(self())!='index.php' ) {
    $css = ' keret';
}
// Fotógaléria elrendezése miatt
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

// Bal oldali nav panel select-je
$tartBal  = '';
$keresodn = ' dn';
$rendezes = '';
$uj_link  = '';
if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
    $tabla = str_replace('.php', '', basename(self()));


    // Rendezés
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

            $kieg_text = '';
            if( basename(self()) == 'bejelentes.php' ) {
                $kieg_text = 'Bejelentés';
            } else if( basename(self()) == 'munkalap.php' ) {
                $kieg_text = 'Munkalap';
            } else if( basename(self()) == 'anyagigenyles.php' ) {
                if (isset($_GET['anyagrend'])) {
                    $kieg_text = 'Anyagrendelés';
                }else{
                    $kieg_text = 'Anyagigénylés';
                }
            }

            // Cikktorzsnél az ID legyen cikkszám
            $rend_mezo_txt = getOszlopNev($rend_mezo, $kieg_text);
            if(basename(self())=='cikktorzs.php' && $rend_mezo=='id'){
                $rend_mezo_txt = 'Cikkszám';
            }

            $rendezes .= '<option value="'.$rend_mezo.'"'.$selected.'>'.$rend_mezo_txt.'</option>';

            $i++;
        }
    }

    // Rendezés onchange linkje
    $irany = '';
    if( $_GET['irany'] ) {
        $irany = ', \''.$_GET['irany'].'\'';
    }

    $rend_change = 'bejegyzesekLista( this'.$irany.' );';

    if( $_GET['rend'] && in_array($_GET['rend'], $rend_mezok) ) {
        $_OPTS['order'] = $_GET['rend'];
    }

    // Rendezési irány
    // Aktívnál fordítva van
    if($_GET['rend']=='aktiv' || (basename(self())=='utemezett_feladatok.php' && !$_GET['rend']) ){
        $_OPTS['order'] .= ' desc';
        $rend_irany = '<a id="rendezesIrany" href="javascript: bejegyzesekLista( \'#rendezesSelect\', \'desc\', \''.$_GET['rkat'].'\' ); void(0);">▼ <span>Z-A</span></a>';
        if( $_GET['irany'] && $_GET['irany'] != 'desc' ) {
            $rend_irany = '<a id="rendezesIrany" href="javascript: bejegyzesekLista( \'#rendezesSelect\', \'desc\', \''.$_GET['rkat'].'\' ); void(0);">▲ <span>A-Z</span></a>';
        }
    }else{
        $rend_irany = '<a id="rendezesIrany" href="javascript: bejegyzesekLista( \'#rendezesSelect\', \'asc\', \''.$_GET['rkat'].'\' ); void(0);">▲ <span>A-Z</span></a>';
        if( $_GET['irany'] && $_GET['irany'] == 'desc' ) {
            $rend_irany = '<a id="rendezesIrany" href="javascript: bejegyzesekLista( \'#rendezesSelect\', \'desc\', \''.$_GET['rkat'].'\' ); void(0);">▼ <span>Z-A</span></a>';

            if( $_GET['rend'] && in_array($_GET['rend'], $rend_mezok) ) {
                $_OPTS['order'] .= ' desc';
            }
        }
    }

    $tartBal  = szamla_lista();

    if (isset($_GET['gyorskereso_ajax'])) {
        echo $tartBal;
        $weblap -> leallit();
        exit;
    }

    // Új ikonra kattintva mi történik 
    // FONTOS! Az admin.js-ben is be kell állítani, hogy az ajaxos frissítések után is működjenek ezek a beállítások, ne csak hard betöltéskor.
    $keresodn = '';
    if( isset($_GET['szakterulet']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'szakterulet\'); void(0);';

    } elseif( basename(self()) == 'anyagigenyles.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( basename(self()) == 'anyagigenyles.php' && !isset($_GET['megjegyzesek']) && !_jog(JOG_ANYAGIGENYLES_KEZEL) ) {
        $uj_link = 'javascript: alert2(\'Önnek nincs jogosultsága új anyagigénylés feltöltéséhez!\', \'\', \'Hiba\'); void(0);';

    } elseif( basename(self()) == 'anyagigenyles.php' && isset($_GET['anyagrend']) ) {
        $uj_link = 'javascript: alert2(\'Új anyagigénylés csak az anyagigénylés menüpont alatt tölthető fel!\', \'\', \'Hiba\'); void(0);';

    } elseif( basename(self()) == 'bejelentes.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    // Bejelentés más és saját névben (ekkor kérdezünk előbb)
    } elseif( basename(self()) == 'bejelentes.php' && _jog(JOG_BEJELENTES_MAS_NEVEBEN) && _jog(JOG_BEJELENTES_KEZEL) ) {
        $uj_link = 'javascript: bejSajatMasneven(); void(0);';

    // Bejelentés más nevében
    } elseif( basename(self()) == 'bejelentes.php' && _jog(JOG_BEJELENTES_MAS_NEVEBEN) ) {
        $uj_link = 'javascript: bejSzallodaValaszt(); void(0);';

    } elseif( basename(self()) == 'beosztas.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( basename(self()) == 'beosztas.php' && isset($_GET['naptar']) ) {
        $uj_link = 'javascript: $(\'#szerk_form div.dt\').show(); void(0)';

    } elseif( basename(self()) == 'cikktorzs.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( isset($_GET['szallodak']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'szallodak\'); void(0);';

    // Munkalapon, ha nem a megjegyzés alatt vagyunk, és nincs jogunk új munkalapot létrehozni akkor hibaüzenet:
    } elseif( basename(self()) == 'munkalap.php' && !isset($_GET['megjegyzesek']) && !_jog(JOG_MUNKALAP_UJ_LETREHOZ) ) {
        $uj_link = 'javascript: alert2(\'Önnek nincs joga új munkalapot létrehozni!\', \'\', \'Hiba\'); void(0);';

    } elseif( basename(self()) == 'munkalap.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( basename(self()) == 'eloterj_fb.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( basename(self()) == 'eloterj_fb.php' && isset($_GET['projektigeny']) ) {
        $uj_link = 'javascript: csoportMuvelet(\'eloterj_igneyles_csatol\'); void(0);';

    } elseif( basename(self()) == 'eloterj_bod.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( basename(self()) == 'eloterj_bod.php' && isset($_GET['projektigeny']) ) {
        $uj_link = 'javascript: csoportMuvelet(\'eloterj_igneyles_csatol\'); void(0);';

    } elseif( basename(self()) == 'energia.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( basename(self()) == 'eseti_fenntartas.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( basename(self()) == 'eszkozkezeles.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( basename(self()) == 'fenntartasi_szerzodes.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( basename(self()) == 'felmeresi_terv.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( basename(self()) == 'login.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( basename(self()) == 'tervezes.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( basename(self()) == 'utemezett_feladatok.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( basename(self()) == 'projektigenyles.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( basename(self()) == 'projektigenyles.php' && isset($_GET['megterules']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megterules\'); void(0);';

    } elseif( basename(self()) == 'projektkezeles.php' && isset($_GET['projekt_team']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'projekt_team\'); void(0);';

    } elseif( basename(self()) == 'projektkezeles.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( basename(self()) == 'raktar.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( basename(self()) == 'rendszeres_karbantartas.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( basename(self()) == 'szamla.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( basename(self()) == 'szallito.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( basename(self()) == 'torzsadat.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( isset($_GET['velemenyezok']) && !_jog(JOG_VELEMENYEZO_HOZZAAD) ) {
        $uj_link = 'javascript: alert2(\'Önnek nincs joga véleményezőt hozzáadni!\', \'\', \'Hiba\'); void(0);';

    } elseif( isset($_GET['velemenyezok']) && _jog(JOG_VELEMENYEZO_HOZZAAD) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'velemenyezok\'); void(0);';

    } else {
        $uj_link = self().'?uj';
    }

}

$felh_nev = '';
if( $_SESSION[get_db_tabla().'user'] ) {
    $felhasznalo = $adatbazis_kapcsolat->s2('recid nev teljes_nev reszleg_id', 'login', 'nev='.$_SESSION[get_db_tabla().'user'], '', '1');

    if( $felhasznalo ) {
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

/* NYOMTATÁS itt lesz lekezelve, itt hívjuk meg */
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
} elseif( !$_SESSION[get_db_tabla().'login_recid'] ) {
    $menuk    = '';
    $oldalNev = 'Bejelentkezés';
    $belepve  = '';
}

// Törlés és új gombok ne is látsszon, ha nincs hozzá joga
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

if( basename(self())=='anyagigenyles.php' && !_jog(JOG_ANYAGIGENYLES_KEZEL)){
    $torles_icon    = '';
    // $uj             = '';
}
// Anyagrendelés alatt nincs új felvitel
if( basename(self())=='anyagigenyles.php' && isset($_GET['anyagrend'])){
    $uj             = '';
}

if(basename(self())=='bejelentes.php' && (!_jog(JOG_BEJELENTES_KEZEL)) && !_jog(JOG_BEJELENTES_MAS_NEVEBEN)){
    $uj             = '';
}
if(basename(self())=='bejelentes.php' && !_jog(JOG_BEJELENTES_TORLESE)){
    $torles_icon    = '';
}

if( basename(self())=='beosztas.php' && !_jog(JOG_BEOSZTASOK_KEZEL)){
    $torles_icon    = '';
    $uj             = '';
}

if( basename(self())=='cikktorzs.php' && !_jog(JOG_CIKKTORZS_KEZEL)){
    $torles_icon    = '';
    $uj             = '';
}

if(basename(self())=='eloterj_bod.php' && !_jog(JOG_ELOTERJ_KEZEL)){
    $torles_icon    = '';
    $uj             = '';
}

if(basename(self())=='eloterj_fb.php' && !_jog(JOG_ELOTERJ_KEZEL)){
    $torles_icon    = '';
    $uj             = '';
}

if(basename(self())=='erteklista.php' && !_jog(JOG_ERTEKLISTAK_KEZEL)){
    $torles_icon    = '';
    $uj             = '';
}

if(basename(self())=='erteklista.php' && !_jog(JOG_SZEREPKOR_KEZEL)){
    $torles_icon    = '';
    $uj             = '';
}

if(basename(self())=='eszkozkezeles.php' && !_jog(JOG_ESZKOZ_KEZEL)){
    $torles_icon    = '';
    $uj             = '';
}

if(basename(self())=='felmeresi_terv.php' && !_jog(JOG_FT_KEZEL)){
    $torles_icon    = '';
    $uj             = '';
}

if(basename(self())=='login.php' && !_jog(JOG_FELHASZNALOK_KEZEL)){
    $torles_icon    = '';
    $uj             = '';
}

if(basename(self())=='munkalap.php'){
    if(!_jog(JOG_MUNKALAP_TOROL)){
        $torles_icon    = '';
    }
}

// Ütemezett feladatok 
if(basename(self())=='utemezett_feladatok.php' && !_jog(JOG_UTFEL_KEZEL) ){
    $torles_icon    = '';
    $uj             = '';
}

if( basename(self())=='projektigenyles.php' && !_jog(JOG_PRIGENY_S_KEZEL) && !_jog(JOG_PRIGENY_T_KEZEL) && !_jog(JOG_PRIGENY_B_KEZEL) && !_jog(JOG_PRIGENY_F_KEZEL) && !_jog(JOG_PRIGENY_SZ_KEZEL)){
    $torles_icon    = '';
    $uj             = '';
}

if( basename(self())=='projektkezeles.php' && !_jog(JOG_PRKEZEL_KEZEL) ){
    $torles_icon    = '';
    $uj             = '';
}

if( basename(self())=='raktar.php' && !_jog(JOG_RAKTAR_KEZEL)){
    $torles_icon    = '';
    $uj             = '';
}

//Szerepkör jogoknál semmi értelme az új és törlés ikonoknak
if( basename(self())=='szerepkor_jog.php'){
    $torles_icon    = '';
    $uj             = '';
}

if( basename(self())=='szabadsagigeny.php' && !_jog(JOG_SZABADSAGIG_KEZEL)){
    $torles_icon    = '';
    $uj             = '';
}

if( basename(self())=='torzsadat.php' && !_jog(JOG_TORZSADAT_KEZEL)){
    $torles_icon    = '';
    $uj             = '';
}

$csv_get = get_get(array('id'));

$rendez_rejt_oldalak = array('dokumentumtar.php', 'eszkozkezeles.php', 'felmeresi_terv.php');
if(in_array(basename(self()), $rendez_rejt_oldalak)){
    $rendezes_rejt = ' dn';
}

$get_get = get_get(array('pdf'));

$header_sablon = fajlbol_olvas('../sablon/admin/header.html', true);
$header_html .= str_replace(
    array('{CSS}', '{CEGNEV}', '{MENUK}', '{SZURES}', '{TBALW}', '{TJOBBW}', '{TARTBAL}', '{TABLA}', '{KERESODN}', '{OLDALNEV}', '{RENDEZES}', '{REND_IRANY}', '{REND_CHANGE}', '{MEGN}', '{SELF}', '{GET_GET}', '{CSV_GET}', '{SZERK_DIS}', '{BELEPVE}', '{LAPOZAS}', '{KSZ}',      '{TORLES}',   '{UJ}', '{CSUSZKADN}', '{RENDEZES_REJT}' ),
    array( $css,    get_nev(),  $menuk,    $szures,    $tbalw,    $tjobbw,    $tartBal,    $tabla,    $keresodn,    $oldalNev,    $rendezes,    $rend_irany,    $rend_change,    $megn,    $self,    $get_get,    $csv_get,   $szerk_dis,    $belepve,    $lapozas,    $_GET['ksz'], $torles_icon, $uj,    $csuszkadn,    $rendezes_rejt    ),
    $header_sablon
);


//Nyomtatási popup
$header_html .= nl2('
    <div class="dn">
        <div id="nyomtatasi_nezet">
            Nyomtatási nézet választás...
            <br><a href="'.self().get_get('pdf').'&amp;print=lista{KSZ}" target="_blank">Listanézet</a>
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

if( $_SESSION[get_db_tabla().'login_recid'] ) {
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

if( !$_SESSION[get_db_tabla().'login_recid'] ) {
    require_once('footer.php');
    exit();
}
