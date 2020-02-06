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
require_once('beleptetes.php');
require_once('esemenyek.php');

if( isset($_GET['szerkeszt']) && $_GET['id'] ) {
    $tabla = str_replace('.php', '', basename(self()));

    // Megnézzük, hogy van e érvényes sor
    $record = $adatbazis_kapcsolat->s('recid', $tabla, 'recid='.$_GET['id'], '', '1');

    if( $record ) {
        // Kezdeményezett művelet
        $muvelet = 'A(z) '.$tabla.' '.$_GET['id'].'. recordjának szerkesztését kezdeményezte.';

        // Végrehajtott változtatás
        $valtozas = '-';

        $adatbazis_kapcsolat -> i(
            'log',
            array( 'felh_id',                               'nev',       'valtozas', 'ip'                   ),
            array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,    $valtozas,  $_SERVER['REMOTE_ADDR'])
        );
    }
}

$menuk           = '';
$menu_sablon     = fajlbol_olvas('../sablon/admin/menu.html',   true);
$almenu_sablon   = fajlbol_olvas('../sablon/admin/almenu.html', true);

// Home ikon, csak ha már beléptünk
if( $_SESSION[get_db_tabla() . 'login_recid'] ) {
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
if( substr(basename(self()), 0, strlen(basename($_SERVER['PHP_SELF'])) - 4) == $megn ) {
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

//$menuk   .= str_replace( $akt_menu_mit, array( $style, '#', $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );

// Bejelentések
if( _jog(JOG_BEJELENTES_MEGTEKINT) || _jog(JOG_BEJELENTES_SAJAT_RESZL) ) {
    $menu_nev        = 'Bejelentések';
    $menu_nev_kis    = 'bejelentések';
    $menu_nev_tobbes = 'Bejelentések';
    $megn            = 'bejelentes';

    $menu_nev2        = 'Archív bejelentések';
    $menu_nev_kis2    = 'bejelentések';
    $menu_nev_tobbes2 = 'Bejelentések';
    $megn2            = 'bejelentes_arch';
    $aloldalak       = array($megn.'.php', $megn2.'.php');
    if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
    //if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = 'Új bejelentés feltöltése'; }
    if(basename(self())==$megn.'.php') { $oldalNev = 'Bejelentések / <span id="rekord_neve">'.$menu_nev.'</span><span class="dn" id="rekord_neve_egyes">bejelentés</span><span class="dn" id="rekord_neve_targy">bejelentést</span>'; }
    if(basename(self())==$megn2.'.php'){ $oldalNev = 'Bejelentések / <span id="rekord_neve">'.$menu_nev2.'</span><span class="dn" id="rekord_neve_egyes">archív bejelentés</span><span class="dn" id="rekord_neve_targy">archív bejelentést</span>'; }

    $aktmenuk  = '';
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], $menu_nev),                $almenu_sablon );
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], 'Archív '.$menu_nev_kis),  $almenu_sablon );
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

    $menuk .= str_replace( $akt_menu_mit, array( $style, '#', $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );
}

// Munkák
if( _jog(JOG_MUNKALAP_MEGTEKINT) || _jog(JOG_MUNKALAP_MEGTEKINT_SZALL) || _jog(JOG_MUNKALAP_MEGTEKINT_FELADAT) || _jog(JOG_UTFEL_MEGTEKINT) || _jog(JOG_RENDSZ_KARB_MEGTEKINT) || _jog(JOG_ESETI_FENN_MEGTEKINT) ) {
    $menu_nev        = 'Munkák';
    $menu_nev_kis    = 'munkák';
    $menu_nev_tobbes = 'Munkák';
    $megn            = 'munkalap';

    $menu_nev2        = 'Archív munkalapok';
    $menu_nev_kis2    = 'archív munkalapok';
    $menu_nev_tobbes2 = 'Archív munkalapok';
    $megn2            = 'munkalap_arch';
    $aloldalak = array(
                $megn.'.php',
                'utemezett_feladatok.php',
                'rendszeres_karbantartas.php',
                'eseti_fenntartas.php',
                'uzemeltetesi_naplo.php',
                $megn2.'.php',
    );
    if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
    if(basename(self())=='munkalap.php')                { $oldalNev = 'Munkák / <span id="rekord_neve">Munkalapok</span><span class="dn" id="rekord_neve_egyes">munkalap</span><span class="dn" id="rekord_neve_targy">munkalapot</span>';                                              }
    if(basename(self())=='munkalap_arch.php')           { $oldalNev = 'Munkák / <span id="rekord_neve">Archív munkalapok</span><span class="dn" id="rekord_neve_egyes">archív munkalap</span><span class="dn" id="rekord_neve_targy">archív munkalapot</span>';                                              }
    if(basename(self())=='mobil_hozzarendel.php')       { $oldalNev = 'Munkák / <span id="rekord_neve">Mobil hozzárendelés</span><span class="dn" id="rekord_neve_egyes">hozzárendelés</span><span class="dn" id="rekord_neve_targy">hozzárendelést</span>';       }
    if(basename(self())=='utemezett_feladatok.php')     { $oldalNev = 'Munkák / <span id="rekord_neve">Ütemezett feladatok</span><span class="dn" id="rekord_neve_egyes">ütmezett feladat</span><span class="dn" id="rekord_neve_targy">ütemezett feladatot</span>';                     }
    if(basename(self())=='rendszeres_karbantartas.php') { $oldalNev = 'Munkák / <span id="rekord_neve">Rendszeres karbantartás</span><span class="dn" id="rekord_neve_egyes">rendszeres karbantartás</span><span class="dn" id="rekord_neve_targy">rendszeres karbantartást</span>';    }
    if(basename(self())=='eseti_fenntartas.php')        { $oldalNev = 'Munkák / <span id="rekord_neve">Eseti fenntartás</span><span class="dn" id="rekord_neve_egyes">eseti fenntartás</span><span class="dn" id="rekord_neve_targy">eseti fenntartást</span>';                         }
    if(basename(self())=='uzemeltetesi_naplo.php')      { $oldalNev = 'Munkák / <span id="rekord_neve">Üzemeltetési napló</span><span class="dn" id="rekord_neve_egyes">üzemeltetési napló</span><span class="dn" id="rekord_neve_targy">üzemeltetési naplót</span>';                   }

    $aktmenuk  = '';
    if( _jog(JOG_MUNKALAP_MEGTEKINT) || _jog(JOG_MUNKALAP_MEGTEKINT_SZALL) || _jog(JOG_MUNKALAP_MEGTEKINT_FELADAT) ){
        $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Munkalapok'                                                             ), $almenu_sablon );
        $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['5'], 'Archív munkalapok'        ), $almenu_sablon );
    }
    if( _jog(JOG_MUNKALAP_NYITOTT_KEZEL) ){
        $aktmenuk .= str_replace( $akt_almenu_mit, array( 'mobil_hozzarendel.php', 'Mobil hozzárendelés'                                            ), $almenu_sablon );
        $aktmenuk .= str_replace( $akt_almenu_mit, array( 'javascript:csoportMuvelet(\'mobil_szemelyzet_beosztas\')', 'Mobil személyzet beosztása'  ), $almenu_sablon );
    }
    if( _jog(JOG_UTFEL_MEGTEKINT) ){
        $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], 'Ütemezett feladatok'        ), $almenu_sablon );
    }
    if( _jog(JOG_RENDSZ_KARB_MEGTEKINT) ){
        $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['2'], 'Rendszeres karbantartás'    ), $almenu_sablon );
    }
    if( _jog(JOG_ESETI_FENN_MEGTEKINT) ){
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

    $menuk   .= str_replace( $akt_menu_mit, array( $style, '#', $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );
}


// Anyagok
if( _jog(JOG_ANYAIGENLES_MEGTEKINT) || _jog(JOG_ANYAGREND_MEGTEKINT) || _jog(JOG_RAKTAR_MEGTEKINT) || _jog(JOG_CIKKTORZS_MEGTEKINT) ) {
    $menu_nev        = 'Anyagok';
    $menu_nev_kis    = 'anyagok';
    $menu_nev_tobbes = 'Anyagok';
    $megn            = 'anyagok';
    $aloldalak       = array('cikktorzs.php', 'anyagigenyles.php?tablazat', 'anyagigenyles.php?anyagrend=1&tablazat', 'raktar.php');
    if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
    if(basename(self())=='cikktorzs.php')           { $oldalNev = 'Anyagok / <span id="rekord_neve">Cikktörzs</span><span class="dn" id="rekord_neve_egyes">cikktörzs</span><span class="dn" id="rekord_neve_targy">cikktörzset</span>';        }
    if(basename(self())=='anyagigenyles.php')       {
                                                        if(isset($_GET['anyagrend'])){
                                                            $oldalNev = 'Anyagok / <span id="rekord_neve">Anyagrendelések</span><span class="dn" id="rekord_neve_egyes">anyagrendelés</span><span class="dn" id="rekord_neve_targy">anyagrendelést</span>';
                                                        } else {
                                                            $oldalNev = 'Anyagok / <span id="rekord_neve">Anyagigénylések</span><span class="dn" id="rekord_neve_egyes">anyagigénylés</span><span class="dn" id="rekord_neve_targy">anyagigénylést</span>';
                                                        }
                                                    }
    if(basename(self())=='raktar.php')              { $oldalNev = 'Anyagok / <span id="rekord_neve">Raktár</span><span class="dn" id="rekord_neve_egyes">raktár</span><span class="dn" id="rekord_neve_targy">raktárat</span>';           }

$aktmenuk  = '';
if( _jog(JOG_ANYAIGENLES_MEGTEKINT) ) {
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], 'Anyagigénylések'), $almenu_sablon );
}
if( _jog(JOG_ANYAGREND_MEGTEKINT) ) {
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['2'], 'Anyagrendelések'), $almenu_sablon );
}
if( _jog(JOG_RAKTAR_MEGTEKINT) ) {
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['3'], 'Raktár'         ), $almenu_sablon );
}
if( _jog(JOG_CIKKTORZS_MEGTEKINT) ) {
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Cikktörzs'      ), $almenu_sablon );
}

    $style = '';
    if( substr(basename(self()), 0, strlen(basename($_SERVER['PHP_SELF'])) - 4) == $megn ) {
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
}

// Csak bejelentő elől rejtve
if(!$_SESSION['bejelento_menu_rejt'] && ( _jog(JOG_ESZKOZ_MEGTEKINT) || _jog(JOG_FT_MEGTEKINT) ) ){
    // Eszközök
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

    $menuk   .= str_replace( $akt_menu_mit, array( $style, '#', $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );
}

// Csak bejelentő elől rejtve
if( !$_SESSION['bejelento_menu_rejt'] && (_jog(JOG_ENERGIA_EGYSEGAR_OLVAS) || _jog(JOG_FENNTARTASI_SAJAT_SZ_OLVAS)) ) {
    // Tervezés
    $menu_nev        = 'Fenntartás';
    $menu_nev_kis    = 'fenntartás';
    $menu_nev_tobbes = 'Fenntartás';
    $megn            = 'energia_terv';
    $aloldalak       = array($megn.'.php');
    if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
    if(basename(self())==$megn.'.php'){ $oldalNev = $oldalNev = 'Tervezés / <span id="rekord_neve">Energia egységárak</span><span class="dn" id="rekord_neve_egyes">energia egységár</span><span class="dn" id="rekord_neve_targy">energia egységárat</span>'; }
    if(basename(self())=='fenntartasi_terv.php'){ $oldalNev = $oldalNev = 'Fenntartás / <span id="rekord_neve">Fenntartási tervek</span><span class="dn" id="rekord_neve_egyes">fenntartási terv</span><span class="dn" id="rekord_neve_targy">fenntartási tervet</span>'; }

    if(basename(self())=='anyag_szamla.php')            { $oldalNev = 'Tervezés / <span id="rekord_neve">Anyag számlák</span><span class="dn" id="rekord_neve_egyes">Anyag számla</span><span class="dn" id="rekord_neve_targy">Anyag számlát</span>';                                      }
    if(basename(self())=='energia_szamla.php')          { $oldalNev = 'Tervezés / <span id="rekord_neve">Energia számlák</span><span class="dn" id="rekord_neve_egyes">Energia számla</span><span class="dn" id="rekord_neve_targy">Energia számlát</span>';                              }
    if(basename(self())=='fenntartasi_szamla.php')      { $oldalNev = 'Tervezés / <span id="rekord_neve">Fenntartási számlák</span><span class="dn" id="rekord_neve_egyes">Fenntartási számla</span><span class="dn" id="rekord_neve_targy">Fenntartási számlát</span>';              }
    if(basename(self())=='fenntartasi_szerzodes.php')   { $oldalNev = 'Tervezés / <span id="rekord_neve">Fenntartási szerződések</span><span class="dn" id="rekord_neve_egyes">Fenntartási szerződés</span><span class="dn" id="rekord_neve_targy">Fenntartási szerződést</span>'; }

    
    $aktmenuk  = '';
    if(_jog(JOG_ENERGIA_EGYSEGAR_OLVAS)){
        $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Energia egységárak'  ), $almenu_sablon );
    }
    if(_jog(JOG_FENNTARTASI_SAJAT_SZ_OLVAS)){
        $aktmenuk .= str_replace( $akt_almenu_mit, array( 'fenntartasi_terv.php', 'Fenntartási tervek'    ), $almenu_sablon );
    }
    if( _jog(JOG_FENNTARTASI_SZERZ_OLVAS) ) {
        $aktmenuk .= str_replace( $akt_almenu_mit, array( 'fenntartasi_szerzodes.php','Fenntartási szerződések'), $almenu_sablon );
    }
    if( _jog(JOG_FENNTARTASI_SZAMLAK_OLVAS) ) {
        $aktmenuk .= str_replace( $akt_almenu_mit, array( 'fenntartasi_szamla.php', 'Fenntartási számlák'    ), $almenu_sablon );
    }
    if( _jog(JOG_ENERGIA_SZAMLAK_OLVAS) ) {
        $aktmenuk .= str_replace( $akt_almenu_mit, array( 'energia_szamla.php', 'Energia számlák'        ), $almenu_sablon );
    }
    if( _jog(JOG_ANYAG_SZAMLAK_OLVAS) ) {
        $aktmenuk .= str_replace( $akt_almenu_mit, array( 'anyag_szamla.php', 'Anyag számlák'          ), $almenu_sablon );
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

    $menuk   .= str_replace( $akt_menu_mit, array( $style, '#', $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );
}

// Projektek
if( projektek_lathat() ) {
    $menu_nev        = 'Projektek';
    $menu_nev_kis    = 'rojektek';
    $menu_nev_tobbes = 'Projektek';
    $megn            = 'projektigenyles';
    $aloldalak       = array(
                            'projektigenyles.php',  'projektkezeles.php',       'projektszerzodes.php',     'cash_flow.php',
                            'eloterj_fb.php',       'eloterj_bod.php',          'projektszamla.php',        
                            );
    if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
    if(basename(self())=='projektigenyles.php')         { $oldalNev = 'Projektek / <span id="rekord_neve">Projektigénylések</span><span class="dn" id="rekord_neve_egyes">projektigénylés</span><span class="dn" id="rekord_neve_targy">projektigénylést</span>';                       }
    if(basename(self())=='projektkezeles.php')          { $oldalNev = 'Projektek / <span id="rekord_neve">Projektek</span><span class="dn" id="rekord_neve_egyes">projekt</span><span class="dn" id="rekord_neve_targy">projektet</span>';                                              }
    if(basename(self())=='projektszerzodes.php')        { $oldalNev = 'Projektek / <span id="rekord_neve">Projekt szerződések</span><span class="dn" id="rekord_neve_egyes">projekt szerződés</span><span class="dn" id="rekord_neve_targy">projekt szerződést</span>';                 }
    if(basename(self())=='projektszamla.php')           { $oldalNev = 'Projektek / <span id="rekord_neve">Projekt számlák</span><span class="dn" id="rekord_neve_egyes">projekt számla</span><span class="dn" id="rekord_neve_targy">projekt számlát</span>';                           }
    if(basename(self())=='cash_flow.php')               { $oldalNev = 'Projektek / <span id="rekord_neve">Cash Flow</span><span class="dn" id="rekord_neve_egyes">Cash Flow</span><span class="dn" id="rekord_neve_targy">Cash Flow-t</span>';                                          }
    if(basename(self())=='eloterj_fb.php')              { $oldalNev = 'Projektek / Előterjesztés / <span id="rekord_neve">FB előterjesztések</span><span class="dn" id="rekord_neve_egyes">FB előterjesztés</span><span class="dn" id="rekord_neve_targy">FB előterjesztést</span>';    }
    if(basename(self())=='eloterj_bod.php')             { $oldalNev = 'Projektek / Előterjesztés / <span id="rekord_neve">BoD előterjesztések</span><span class="dn" id="rekord_neve_egyes">BoD előterjesztés</span><span class="dn" id="rekord_neve_targy">BoD előterjesztést</span>'; }
    
    $aktmenuk  = '';
    if( _jog(JOG_PRIGENY_S_KEZEL) || _jog(JOG_PRIGENY_SZ_MEGTEKINT) || _jog(JOG_PRIGENY_B_MEGTEKINT) || _jog(JOG_PRIGENY_EGY_MEGTEKINT) || _jog(JOG_PRIGENY_F_MEGTEKINT) || _jog(JOG_PRIGENY_TOBB_MEGTEKINT) || _jog(JOG_PRIGENY_T_MEGTEKINT) ){
        $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Projektigénylések'  ), $almenu_sablon );
    }
    if( _jog(JOG_PRKEZEL_MEGTEKINT) || _jog(JOG_PRKEZEL_SAJAT_MEGTEKINT) || _jog(JOG_PRKEZEL_TEAM_TAG) || _jog(JOG_PR_EGYSZALL_MEGTEKINT) || _jog(JOG_PR_TOBBSZALL_MEGTEKINT) || _jog(JOG_PR_B_MEGTEKINT) || _jog(JOG_PR_F_MEGTEKINT) || _jog(JOG_PR_T_MEGTEKINT) ){
        $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], 'Projektek' ), $almenu_sablon );
    }
    if( _jog(JOG_PRKEZEL_SZERZODES_OLVAS) || _jog(JOG_PR_SAJAT_SZERZ_MEGTEKINT) ){
        $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['2'], 'Projekt szerződések'), $almenu_sablon );
    }
    if( _jog(JOG_PRSZAMLAK_OLVAS) || _jog(JOG_PR_SAJAT_SZAMLA_MEGTEKINT) ){
        $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['6'], 'Projekt számlák'), $almenu_sablon );
    }
    $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['3'], 'Cash-flow'              ), $almenu_sablon );


    if( _jog(JOG_ELOTERJ_MEGTEKINT) ) {
        $aktmenuk .= nl2('
            <ul class="alalmenu_cont">
                <a class="nolink">Előterjesztések</a>
                <ul class="alalmenu">
                    <li><a href="'.$aloldalak['4'].'">FB előterjesztés</a></li>
                    <li><a href="'.$aloldalak['5'].'">BoD előterjesztés</a></li>
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

    $menuk   .= str_replace( $akt_menu_mit, array( $style, '#', $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );
}


if( !$_SESSION['bejelento_menu_rejt'] /* Bejelentő elől rejtve */ && (_jog(JOG_FENNTARTASI_SZERZ_OLVAS) || _jog(JOG_SZAMLAK_OLVAS) || _jog(JOG_PARTNERTORZS_OLVAS) || _jog(JOG_DOKUMENTUMTAR_OLVAS) ) ){
    // Továbbiak
    $menu_nev        = 'Dokumentumok';
    $menu_nev_kis    = 'dokumentumok';
    $menu_nev_tobbes = 'Dokumentumok';
    $megn            = 'szamla';
    $aloldalak       = array('szamla.php', 'szallito.php', 'dokumentumtar.php');
    if(basename(self())==$megn.'.php' && isset($_GET['modosit'])){ $navi = $menu_nev.' szerkesztése'; }
    if(basename(self())=='szamla.php')                  { $oldalNev = 'Dokumentumok / <span id="rekord_neve">Számlák</span><span class="dn" id="rekord_neve_egyes">számla</span><span class="dn" id="rekord_neve_targy">számlákat</span>';                                              }
    if(basename(self())=='szallito.php')                { $oldalNev = 'Dokumentumok / <span id="rekord_neve">Partnertörzs</span><span class="dn" id="rekord_neve_egyes">partnertörzs</span><span class="dn" id="rekord_neve_targy">partnertörzset</span>';                              }
    if(basename(self())=='dokumentumtar.php')           { $oldalNev = 'Dokumentumok / <span id="rekord_neve">Dokumentumtár</span><span class="dn" id="rekord_neve_egyes">dokumentumtár</span><span class="dn" id="rekord_neve_targy">dokumentumtárat</span>';                           }

    $aktmenuk  = '';
    if( _jog(JOG_SZAMLAK_OLVAS) ){
        $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Számlák'                ), $almenu_sablon );
    }
    if( _jog(JOG_PARTNERTORZS_OLVAS) ){
        $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], 'Partnertörzs'           ), $almenu_sablon );
    }
    if( _jog(JOG_DOKUMENTUMTAR_OLVAS) ){
        $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['2'], 'Dokumentumtár'          ), $almenu_sablon );
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

    $menuk   .= str_replace( $akt_menu_mit, array( $style, '#', $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );
}


// Személyzet
if(_jog(JOG_TORZSADAT_MEGTEKINT) || _jog(JOG_SZABADSAGIG_MEGTEKINT) || _jog(JOG_BEOSZTASOK_MEGTEKINT)){
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
    if(_jog(JOG_TORZSADAT_MEGTEKINT)){
        $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Törzsadatok'        ), $almenu_sablon );
    }
    if(_jog(JOG_SZABADSAGIG_MEGTEKINT)){
        $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], 'Szabadságigények'   ), $almenu_sablon );
    }
    if(_jog(JOG_BEOSZTASOK_MEGTEKINT)){
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

    $menuk   .= str_replace( $akt_menu_mit, array( $style, '#', $megn, $menu_nev_tobbes, $aktmenuk ), $menu_sablon );
}


// Csak bejelentő elől rejtve
if(!$_SESSION['bejelento_menu_rejt']){
    // Rendszer beállítások
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

    // Rendszer kategória lekérdezése
    $rkat = '';
    if( $_GET['rkat'] || $_SESSION['rendszerkat'] ) {
        if( $_GET['rkat'] ) {
            $_SESSION['rendszerkat'] = $_GET['rkat'];
        }

        $rendszerKat = $adatbazis_kapcsolat->s('recid nev', 'rendszer_kat', 'recid='.$_SESSION['rendszerkat'], '', '1');
        if( $rendszerKat ) {
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
    // $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['0'], 'Áfa' ), $almenu_sablon );
    // $aktmenuk .= str_replace( $akt_almenu_mit, array( $aloldalak['1'], 'Beosztás' ), $almenu_sablon );
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
    // $aktmenuk .= str_replace( $akt_almenu_mit, array( '../tervek/" target="_blank', 'Design' ), $almenu_sablon );

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
}


// További telepítések figyelése, menedzselése egy külön fájlban
if(is_file('!telepitesek.php')){
    require_once('!telepitesek.php');
}

// Tényleges tartalom
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

// Respos CSS meghívás
if( in_array(basename(self()), get_respos_oldalak()) ) {
    $head -> extrak   .= '<link href="../css/responsive_mobil.css" rel="stylesheet">';
}
// Ha adott aloldalnak volna egydi css-e, akkor azt is meghívjuk
$egyedi_css = '../css/'. str_replace('.php', '', basename(self())).'.css';
if( is_file($egyedi_css) ) {
    $head -> extrak   .= '<link href="'.$egyedi_css.'" rel="stylesheet">';
}

$header_html .= $head -> getHtml();

$body_class = '';
if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
    $body_class = ' class="teljesMagas"';

    if( isset($_GET['uj']) ) {
        $body_class = ' class="teljesMagas ujfeltoltes"';
    }
} else if( isset($_GET['uj']) ) {
    $body_class = ' class="ujfeltoltes"';
} else if( !$_SESSION[get_db_tabla().'user'] ) {
    $body_class = ' class="signedout"';
}

$header_html .= '<body'.$body_class;
    // Új feltöltésnél a név mezőn legyen a fókusz
    if( isset($_GET['uj']) ) {
        // Új feltöltésnél az űrlap első aktív mezőjére fókuszálunk, ez a form_uj.html végén van beállítva.
    // Listánál a gyoskeresőn legyen a fókusz
    } elseif( isset($_GET['lista']) ) {
        $header_html .=' onload="if(document.getElementById(\'id\')){document.getElementById(\'id\').focus();}"';
    // Többképes képfeltöltésnél ugrás a képekhez
    }
    
    // Ha nem demo-n vagyunk, akkor a jobbklikket tiltjuk!
    if( $_SERVER['HTTP_HOST'] != 'demo.webdeb.hu' ) {
        $header_html .=' oncontextmenu="return false;" ';
    }


$header_html .='>';

// Diszpécsereknél az ajaxfedő legyen teljesen átlátszó
if( $_SESSION[get_db_tabla().'szemelyzet_id'] == DISZPECSER ){
    $header_html .= nl2('
                    <style type="text/css">
                        #ajaxfedo {
                            background: rgba(0, 0, 0, 0);
                        }
                    </style>
                ');
}

// A form körüli keretete a lista nézetben nem jelenítjük meg
$css = '';
if( !isset($_GET['lista']) && basename(self())!='index.php' ) {
    $css = ' keret';
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

// Anyagigénylésnél a bal navpanelt elrejtük, itt helyette egy táblázatos fül van Anyagrend mod: 2018.04
if( basename(self())=='anyagigenyles.php' ){
    $tbalw  = ' style="width: 0px;"';
    $tjobbw = ' style="width: 100%"';
}

$self = basename(self());
$megn = str_replace('.php', '', basename(self()) );

// Bal oldali nav panel select-je
$tartBal  = '';
$keresodn = ' dn';
$rendezes = '';
$uj_link  = '';

// Pl. Archiv bejelentések és munkalapok esetén ha alapértelmezetten a legfrisebb 1000 rekordot kérjük le, akkor a rendezésnél is ez legyen kiírva.
if( $_GET['mezo'] == 'legfrissebb' ) {
    $_GET['rend'] = 'legfrissebb';
}
if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
    $tabla = str_replace('.php', '', basename(self()));

    // Rendezés
    $rend_mezok = $adatbazis_kapcsolat->s('`column_name`', '`information_schema`.columns', '`table_schema` LIKE  \''.get_db().'\' AND  `table_name` LIKE  \''.get_db_tabla().$tabla.'\'');
    $rend_mezok = adatbazis2tomb($rend_mezok, 'column_name');

    $rendezes = '';
    $i = 1;
    foreach ( $rend_mezok as $rend_mezo ) {
        if( in_array($rend_mezo, $_OPTS['rendezes']) ) {
            $selected = '';
            if( $_GET['rend'] == $rend_mezo ) {
                $selected = ' selected="selected"';
            }
            
            // Alapértelmezett irány kikeresése
            $i_tmp = 0;
            foreach( $_OPTS['rendezes'] as $rend ) {
                if( $rend == $rend_mezo ) {
                    $irany = $_OPTS['iranyok'][$i_tmp];
                }
                $i_tmp++;
            }

            $kieg_text = '';
            if( basename(self()) == 'bejelentes.php' || basename(self()) == 'bejelentes_arch.php' ) {
                $kieg_text = 'Bejelentés';
            } else if( basename(self()) == 'munkalap.php' || basename(self()) == 'munkalap_arch.php' ) {
                $kieg_text = 'Munkalap';
            } else if( basename(self()) == 'anyagigenyles.php' || basename(self()) == 'anyagigenyles_arch.php' ) {
                if (isset($_GET['anyagrend'])) {
                    $kieg_text = 'Anyagrendelés';
                } else {
                    $kieg_text = 'Anyagigénylés';
                }
            }

            // Cikktorzsnél az ID legyen cikkszám
            $rend_mezo_txt = getOszlopNev($rend_mezo, $kieg_text);
            if(basename(self())=='cikktorzs.php' && $rend_mezo=='id'){
                $rend_mezo_txt = 'Cikkszám';
            }

            $rendezes .= '<option value="'.$rend_mezo.'"'.$selected.' data-irany="'.$irany.'">'.$rend_mezo_txt.'</option>';

            $i++;
        }
    }

    $rend_change = 'bejegyzesekLista( this, \'default\' );';

    if( $_GET['rend'] && in_array($_GET['rend'], $rend_mezok) ) {
        $_OPTS['order'] = $_GET['rend'];
    }

    // Rendezési irány
    // Aktívnál fordítva van
    if($_GET['rend']=='aktiv' || (basename(self())=='utemezett_feladatok.php' && !$_GET['rend']) ){
        $_OPTS['order'] .= ' desc';
        $rend_irany = '<a id="rendezesIrany" href="javascript: bejegyzesekLista( \'#rendezesSelect\', \'desc\', \''.$_GET['rkat'].'\' ); void(0);">▼<span>Z-A</span></a>';
        if( $_GET['irany'] && $_GET['irany'] != 'desc' ) {
            $rend_irany = '<a id="rendezesIrany" href="javascript: bejegyzesekLista( \'#rendezesSelect\', \'desc\', \''.$_GET['rkat'].'\' ); void(0);">▲<span>A-Z</span></a>';
        }
    } else {
        $rend_irany = '<a id="rendezesIrany" href="javascript: bejegyzesekLista( \'#rendezesSelect\', \'desc\', \''.$_GET['rkat'].'\' ); void(0);">▲<span>A-Z</span></a>';
        if( $_GET['irany'] && $_GET['irany'] == 'desc' ) {
            $rend_irany = '<a id="rendezesIrany" href="javascript: bejegyzesekLista( \'#rendezesSelect\', \'asc\', \''.$_GET['rkat'].'\' ); void(0);">▼<span>Z-A</span></a>';

            if( $_GET['rend'] && in_array($_GET['rend'], $rend_mezok) ) {
                $_OPTS['order'] .= ' desc';
            }
        }
    }

    $tartBal = '';
    // Anyagigénylésnél nincs bal nev panel
    if( basename(self()) != 'anyagigenyles.php' ) {
        // $tartBal = szamla_lista();
        $tartBal = $balNavPanelHTMLje;
    }

    if( isset($_GET['gyorskereso_ajax']) ) {
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

    } elseif( basename(self()) == 'anyag_szamla.php' ) {
        $uj_link = 'javascript: csoportMuvelet(\'anyag_szamla_valaszto\'); void(0);';

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

    // Munkalapon, ha nem a megjegyzés alatt vagyunk, és a usernek van előkészítés jog, akkor feljön egy popup, hogy válasszon, mit szeretne tenni
    } elseif( basename(self()) == 'munkalap.php' && !isset($_GET['megjegyzesek']) && _jog(JOG_MUNKALAP_KULSOS_ELOKESZIT) ) {
        $uj_link = 'javascript: uj_munkalap_elokeszites_joggal(); void(0);';

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

    } elseif( basename(self()) == 'energia_terv.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( basename(self()) == 'energia_terv.php' && !_jog(JOG_ENERGIA_EGYSEGAR_KEZEL) ) {
        $uj_link = 'javascript: alert2(\'Önnek nincs joga energia egységárat feltölteni!\', \'\', \'Hiba\'); void(0);';

    } elseif( basename(self()) == 'energia_terv.php' ) {
        $uj_link = 'javascript: csoportMuvelet(\'terv_keszites_jellemzoi\'); void(0);';

    } elseif( basename(self()) == 'eseti_fenntartas.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( basename(self()) == 'eszkozkezeles.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( basename(self()) == 'fenntartasi_szerzodes.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( basename(self()) == 'felmeresi_terv.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( basename(self()) == 'fenntartasi_terv.php' && !_jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL) ) {
        $uj_link = 'javascript: alert2(\'Önnek nincs joga fenntartási tervet feltölteni!\', \'\', \'Hiba\'); void(0);';

    } elseif( basename(self()) == 'fenntartasi_terv.php' ) {
        $uj_link = 'javascript: csoportMuvelet(\'fenntartasi_terv_feltolt\'); void(0);';

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

    } elseif( basename(self()) == 'projektkezeles.php' && !isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: csoportMuvelet(\'projektkezel_uj_feltolt\'); void(0);';

    } elseif( basename(self()) == 'projektszamla.php' && isset($_GET['megjegyzesek']) ) {
        $uj_link = 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);';

    } elseif( basename(self()) == 'projektszerzodes.php' && isset($_GET['megjegyzesek']) ) {
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
        $uj_link = self().get_get(array('modosit', 'id')).'&amp;uj';
    }

}

$felh_nev = '';
if( $_SESSION[get_db_tabla().'user'] ) {
    $felhasznalo = $adatbazis_kapcsolat->s2('recid nev teljes_nev szemelyzet_id reszleg_id usertel', 'login', "`nev`='{$_SESSION[get_db_tabla().'user']}'", '', '1');

    if( $felhasznalo ) {
        $felh_nev = $felhasznalo['teljes_nev'];
        $reszleg  = nl2(
                        '<span>'.getSzerepkorNev($felhasznalo['szemelyzet_id']).'</span> - 
                         <span>'.erteklista(ERTEKLISTA_RESZLEG, $felhasznalo['reszleg_id']).'</span>
                         '
                    );
    }
}



/* SZŰRÉS
 *  $szures nevű változót állítja elő, benne a szűréssel kapcsolatos funkciókkal (selectek, feltételek)
 */
$nem_kell_szuro = array('ktghely', 'ktgnem', 'szerepkor');
if( !in_array($megn, $nem_kell_szuro)){
    require_once 'szures.php';
}

// NYOMTATÁS itt lesz lekezelve, itt hívjuk meg
require_once 'nyomtatas.php';


$torol_dis = '';
if( !isset($_GET['modosit']) && !isset($_GET['obj']) ) {
    $torol_dis = ' disabled';
}

$szerk_dis = '';
if( (isset($_GET['modosit']) && isset($_GET['szerkeszt'])) || !isset($_GET['modosit']) ) {
    $szerk_dis = ' disabled';
}

// Új feltöltéskor az Új ikont disabled...
$uj_dis = '';
if( isset($_GET['uj']) ) {
    // Kivéve projekt szerkesztéskor
    if(basename(self())!='projektkezeles.php' ){
        $uj_dis = ' disabled';
    }
}

$belepve = nl2('
    <div class="dtc link sugo f25" style="border-right: 0.1rem solid #e9e9e9; padding-right: 1rem; padding-left: 1rem" onclick="window.open(\'../'.get_utvonal().'Hibabejelentes_az_ontEAM_rendszerben.pdf\')">
        ?
    </div>
    <div class="dtc nav c felh">
        <a href="javascript: felh_valt( true ); void(0);">'.$felh_nev.'</a>
        <br><span class="f11">'.$reszleg.'</span>
    </div>
    <div class="dtc nav kilep dn" id="eta_ikon">
        <a href="beleptetes.php?kilep"><i class="material-icons">exit_to_app</i></a>
        <script>
        $(document).ready(function () {
            $(\'#eta_ikon\').removeClass(\'dn\');
        });
        </script>
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
$sajat_szallodak = get_akt_login_szallodai();
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

$duplikal  = nl2('
        <a class="duplikal" title="Duplikálás" onclick="bejegyzesDuplikal();">
            <i class="material-icons">queue</i>
        </a>
    ');

$mail = '';
if( (basename(self())=='munkalap.php' && _jog(JOG_MUNKALAP_NYITOTT_KEZEL)) || (basename(self())=='anyagigenyles.php' && _jog(JOG_ANYAGREND_KESZIT)) ) {
    $mail  = nl2('
            <a id="sendMail" onclick="sendMail( this, \''.basename(self()).'\' );" class="mail" title="E-mail">
                <i class="material-icons">mail</i>
            </a>
        ');
}

// Archiv táblákat nem lehet törölni, újat feltölteni
$tabla_nev = basename(self());
if( substr($tabla_nev, strlen($tabla_nev) - 9) == '_arch.php' ) {
    $uj = $duplikal = $torles_icon = '';
}
// Illetve van pár tábla, ahol szintén nem lehet sem újat felteni, sem törölni
$disabled_tablak = array('energia_szamla.php', 'fenntartasi_szamla.php', 'fenntartasi_szerzodes.php');
if( in_array($tabla_nev, $disabled_tablak) ) {
    $uj = $duplikal = $torles_icon = '';
}

if( basename(self()) == 'anyagigenyles.php' && !_jog(JOG_ANYAGIGENYLES_KEZEL) ) {
    $torles_icon    = '';
}
// Anyagrendelés alatt nincs új felvitel
if( basename(self()) == 'anyagigenyles.php' && isset($_GET['anyagrend']) ) {
    $uj = $duplikal = '';
}
if( basename(self()) == 'anyagigenyles.php' && !isset($_GET['megjegyzesek']) && !_jog(JOG_ANYAGIGENYLES_KEZEL) ) {
    $duplikal = nl2('
        <a class="duplikal" title="Duplikálás" onclick="javascript: alert2(\'Önnek nincs jogosultsága anyagigénylést duplikálni!\', \'\', \'Hiba\'); void(0);">
            <i class="material-icons">queue</i>
        </a>
        ');
}

// Anyag számlánál az új ikonnak látható, de törlés nincs
if( basename(self()) == 'anyag_szamla.php' && !_jog(JOG_ANYAG_SZAMLAK_KEZEL) ) {
    $uj = '';
}
if( basename(self()) == 'anyag_szamla.php' ) {
    $duplikal = $torles_icon = '';
}

if(basename(self())=='bejelentes.php' && (!_jog(JOG_BEJELENTES_KEZEL)) && !_jog(JOG_BEJELENTES_MAS_NEVEBEN)){
    $uj = $duplikal = '';
}

if(basename(self())=='bejelentes.php' && !_jog(JOG_BEJELENTES_TORLESE)){
    $torles_icon    = '';
}

if( basename(self())=='beosztas.php' && !_jog(JOG_BEOSZTASOK_KEZEL)){
    $torles_icon    = '';
    $uj = $duplikal = '';
}

if( basename(self())=='cikktorzs.php' && !_jog(JOG_CIKKTORZS_KEZEL)){
    $torles_icon    = '';
    $uj = $duplikal = '';
}

if( basename(self())=='dokumentumtar.php' && !_jog(JOG_DOKUMENTUMTAR_KEZEL)){
    $torles_icon    = '';
    $uj = $duplikal = '';
}

// Energia tervezés alatt elrejtjük a duplikálást
if( basename(self())=='energia_terv.php' ) {
    $duplikal = '';
}
if( basename(self())=='energia_terv.php' &&  !_jog(JOG_ENERGIA_EGYSEGAR_KEZEL)) {
    $torles_icon    = '';
    $uj = $duplikal = '';
}

if(basename(self())=='eloterj_bod.php' && !_jog(JOG_ELOTERJ_KEZEL)){
    $torles_icon    = '';
    $uj = $duplikal = '';
}

if(basename(self())=='eloterj_fb.php' && !_jog(JOG_ELOTERJ_KEZEL)){
    $torles_icon    = '';
    $uj = $duplikal = '';
}

if( basename(self()) == 'erteklista.php' && !_jog(JOG_ERTEKLISTAK_KEZEL) ) {
    $torles_icon    = '';
    $uj = $duplikal = '';
}

if( basename(self()) == 'szerepkor.php' && !_jog(JOG_SZEREPKOR_KEZEL) ) {
    $torles_icon    = '';
    $uj = $duplikal = '';
}

// Eseti fenntartás
if(basename(self())=='eseti_fenntartas.php' && !_jog(JOG_ESETI_FENN_KEZEL) ){
    $uj = $duplikal = '';
}
if(basename(self())=='eseti_fenntartas.php' && !_jog(JOG_ESETI_FENN_TOROL) ){
    $torles_icon    = '';
}

if( basename(self()) == 'eszkozkezeles.php' && !_jog(JOG_ESZKOZ_KEZEL) ) {
    $torles_icon    = '';
    $uj = $duplikal = '';
}

if( basename(self()) == 'felmeresi_terv.php' && !_jog(JOG_FT_KEZEL) ) {
    $torles_icon    = '';
    $uj = $duplikal = '';
}

if( basename(self()) == 'fenntartasi_terv.php' ) {
    if( !_jog(JOG_FENNTARTASI_SAJAT_SZ_KEZEL) ) {
        $uj = $duplikal = '';
    }

    // Törléshez törlés jog mindenképp kell. Ezen felül vizsgáljuk a login személyzettípusát + terv státuszát, de ezt már törléskor kezeljük le.
    if( !_jog(JOG_FENNTARTASI_TERV_TOROL) ) {
        $torles_icon = '';
    }
}

// Felhasználóknál a törlés gomb sosem látszik.
if(basename(self())=='login.php'/* Ez valszed kelleni fog, rákérdeztem && !isset($_GET['szallodak'])*/){
    $torles_icon    = '';
}
if( basename(self()) == 'login.php' && !_jog(JOG_FELHASZNALOK_KEZEL) ) {
    $uj = $duplikal = '';
}

if( basename(self()) == 'munkalap.php' ) {
    if( !_jog(JOG_MUNKALAP_TOROL) ) {
        $torles_icon    = '';
    }
}

// Ütemezett feladatok
if(basename(self())=='utemezett_feladatok.php' && !_jog(JOG_UTFEL_KEZEL) ){
    $uj = $duplikal = '';
}
if(basename(self())=='utemezett_feladatok.php' && !_jog(JOG_UTFEL_TOROL) ){
    $torles_icon    = '';
}

// Új Projektigénylés feltöltésekor külön jogot vizsgálunk
if( basename(self())=='projektigenyles.php' && !_jog(JOG_PRIGENY_LETREHOZ) ){
    $uj = $duplikal = '';
}
// Projektigénylések: ha egyik joga sincs meg, akkor nincs szerkesztés lehetőség
if( basename(self())=='projektigenyles.php' && !_jog(JOG_PRIGENY_S_KEZEL) && !_jog(JOG_PRIGENY_T_KEZEL) && !_jog(JOG_PRIGENY_B_KEZEL) && !_jog(JOG_PRIGENY_F_KEZEL) && !_jog(JOG_PRIGENY_SZ_KEZEL) && !_jog(JOG_PRIGENY_EGY_KEZEL) && !_jog(JOG_PRIGENY_TOBB_KEZEL) ){
    $torles_icon    = '';
}

// Új Projektterv feltöltésekor külön jogot vizsgálunk
if( basename(self())=='projektkezeles.php' && !_jog(JOG_PRKEZEL_UJ_LETREHOZ) ){
    $uj = $duplikal = '';
}

// Projektszámlák: ha egyik joga sincs meg, akkor nincs szerkesztés lehetőség
if( basename(self())=='projektszamla.php' && !_jog(JOG_PRSZAMLAK_KEZEL) && !_jog(JOG_PR_SAJAT_SZAMLA_KEZEL) ){
    $uj = $duplikal = '';
}

// Projektiszerződések: ha egyik joga sincs meg, akkor nincs szerkesztés lehetőség
if( basename(self())=='projektszerzodes.php' && !_jog(JOG_PRKEZEL_SZERZODES_KEZEL) && !_jog(JOG_PR_SAJAT_SZERZ_KEZEL) ){
    $uj = $duplikal = '';
}

// Raktár
if( basename(self()) == 'raktar.php' && !_jog(JOG_RAKTAR_KEZEL) ) {
    $torles_icon    = '';
    $uj = $duplikal = '';
}

// Rendszeres karbantartás
if( basename(self()) == 'rendszeres_karbantartas.php' && !_jog(JOG_RENDSZ_KARB_KEZEL) ) {
    $uj = $duplikal = '';
}
if( basename(self()) == 'rendszeres_karbantartas.php' && !_jog(JOG_RENDSZ_KARB_TOROL) ) {
    $torles_icon    = '';
}

// Szállító
if( basename(self()) == 'szallito.php' && !_jog(JOG_PARTNERTORZS_KEZEL) ) {
    $uj = $duplikal = '';
}
if( basename(self()) == 'szallito.php' && !_jog(JOG_PARTNERTORZS_TOROL) ) {
    $torles_icon    = '';
}

// Számlák
if( basename(self()) == 'szamla.php' && !_jog(JOG_SZAMLAK_KEZEL) ) {
    $uj = $duplikal = '';
}
if( basename(self()) == 'szamla.php' && !_jog(JOG_SZAMLAK_TOROL) ) {
    $torles_icon    = '';
}

// Szabadságigény
if( basename(self()) == 'szabadsagigeny.php' && !_jog(JOG_SZABADSAGIG_KEZEL) ) {
    $torles_icon    = '';
    $uj = $duplikal = '';
}

// Szerepkör - itt törölni semmiképp nem lehet
if( basename(self()) == 'szerepkor.php' ) {
    $torles_icon = '';
    if( !_jog(JOG_SZEREPKOR_KEZEL) ) {
        $uj = $duplikal = '';
    }
}

// Szerepkör jogoknál semmi értelme az új és törlés ikonoknak
if( basename(self()) == 'szerepkor_jog.php' ) {
    $torles_icon    = '';
    $uj = $duplikal = '';
}

if( basename(self())=='torzsadat.php' && !_jog(JOG_TORZSADAT_KEZEL)){
    $torles_icon    = '';
    $uj = $duplikal = '';
}

// Excel export
$valasztos = '';
$valasztos_feluletek = array('eloterj_bod.php', 'eloterj_fb.php', 'energia_terv.php', 'fenntartasi_terv.php', 'projektszamla.php', 'projektszerzodes.php');
if( in_array(basename(self()), $valasztos_feluletek)  ){
    $valasztos = 'választós';
}
$csv_get = 'csv_general( this, \''.$valasztos.'\' );';

$rendez_rejt_oldalak = array('dokumentumtar.php', 'eszkozkezeles.php', 'felmeresi_terv.php');
if(in_array(basename(self()), $rendez_rejt_oldalak)){
    $rendezes_rejt = ' dn';
}
// Anyagigénylésnél DE nem anyagrendelésnél is rejtjük
if( basename(self())=='anyagigenyles.php' ){
    $rendezes_rejt = ' dn';
}

// Gyorskereső, hacsak nem rejtett oldalon van
$szures_border = '';
$gyorskereses_rejt_oldalak = array('dokumentumtar.php', 'eszkozkezeles.php', 'felmeresi_terv.php');
if( !in_array(basename(self()), $gyorskereses_rejt_oldalak) ) {
    // Anyagigénylés tálbázatban másképp keresünk
    if( basename(self()) == 'anyagigenyles.php' ) {
        $kereso = nl2('
                <span id="respo_keresGomb"><i class="material-icons search">search</i><i class="material-icons close">close</i></span>
                <input type="text" name="ksz" id="rendezesKeres" class="disableNav" autocomplete="off" placeholder="Keresés" onkeyup="anyagigenyles_tabla_szur($(\'table.szamla_cont_tabla\'), $(this).val())">
                <script>
                    function anyagigenyles_tabla_szur(tabla, kulcs) {
                        var $rows = tabla.find(\'tbody tr\');
                        var val = $.trim(kulcs).replace(/ +/g, \' \').toLowerCase();

                        $rows.show().filter(function() {
                            var text = $(this).text().replace(/\s+/g, \' \').toLowerCase();
                            return !~text.indexOf(val);
                        }).hide();

                        /* A fiktív sor legyen mindig látható */
                        $(\'table.szamla_cont_tabla tr.fake\').show();
                    }
                </script>
                ');

    // Kereső általános esete
    } else {
        $kereso = nl2('
            <span id="respo_keresGomb"><i class="material-icons search">search</i><i class="material-icons close">close</i></span>
            <input type="text" name="ksz" id="rendezesKeres" class="disableNav" autocomplete="off" placeholder="Keresés" onkeyup="altalanos_tabla_szur($(\'#szamlaList\'), $(this).val())">
            <script>
                function altalanos_tabla_szur(tabla, kulcs) {
                    var $rows = tabla.find(\'li\');
                    var val = $.trim(kulcs).replace(/ +/g, \' \').toLowerCase();

                    i = 1;
                    $rows.show().filter(function() {
                        var text = $(this).text().replace(/\s+/g, \' \').toLowerCase();
                        if( ~text.indexOf(val) ) {
                            $(this).attr(\'data-i\', i);
                            i++;
                        } else {
                            $(this).attr(\'data-i\', \'\');
                        }
                        return !~text.indexOf(val);
                    }).hide();
                }
            </script>
        ');
        // Felhasználók esetén a keresés mezőbe kerüljön a fókusz
        if( basename(self()) == 'login.php' ) {
            $kereso .= nl2('
                <script>
                    $(document).ready(function () {
                        $(\'#rendezesKeres\').focus();
                    });
                </script>
            ');
        }
    }
    
// Ha a gyorskeresőtrejtjük, akkor a szűrésre rá kell tenni egy noborder class-t, hogy ne legyen szeparátor vonal bal oldalon
} else {
    $szures_border = ' noborder';
}

// Teszt rendszer felirat
if( get_teszt_rendszer() ){
    // Dev-nél mást írunk ki más színnel
    if($_SERVER['HTTP_HOST']=='dev.onteam.hu'){
        $belepve .= '<div class="teszt_rendszer lila">DEV rendszer</div>';
    }elseif($_SERVER['HTTP_HOST']=='demo.webdeb.hu'){
        $belepve .= '<div class="teszt_rendszer">Demo rendszer</div>';
    } else {
        $belepve .= '<div class="teszt_rendszer">Teszt rendszer</div>';
    }
}

// Léptető ikonok
$lepteto_ikonok = nl2('
        <a onclick="naviElozo();" class="elo" title="Előző">
            <i class="material-icons">keyboard_arrow_left</i>
        </a>
        <a onclick="naviKov();" class="kov" title="Következő">
            <i class="material-icons">keyboard_arrow_right</i>
        </a>
        <a onclick="naviElso();" class="elso" title="Első">
            <i class="material-icons">skip_previous</i>
        </a>
        <a onclick="naviUtolso();" class="utolso" title="Utolsó">
            <i class="material-icons">skip_next</i>
        </a>
    ');
// Eszközkezelés alatt nincs léptetés
$nincs_lepteto_ikon = array('dokumentumtar.php', 'eszkozkezeles.php', 'felmeresi_terv.php');
if( in_array(basename(self()), $nincs_lepteto_ikon) ){
    $lepteto_ikonok = '';
}

$get_get = get_get(array('pdf'));

$header_sablon = fajlbol_olvas('../sablon/admin/header.html', true);
$header_html .= str_replace(
    array('{CACHE}',        '{CSS}', '{CEGNEV}', '{MENUK}', '{KERESO}', '{SZURES}', '{TBALW}', '{TJOBBW}', '{TARTBAL}', '{TABLA}', '{KERESODN}', '{OLDALNEV}', '{RENDEZES}', '{REND_IRANY}', '{REND_CHANGE}', '{MEGN}', '{SELF}', '{GET_GET}', '{CSV_GET}', '{SZERK_DIS}', '{BELEPVE}', '{LAPOZAS}', '{KSZ}',       '{LEPTETO_IKONOK}', '{UJ}', '{DUPLIKAL}', '{TORLES}',   '{CSUSZKADN}', '{RENDEZES_REJT}', '{MAIL}', '{FELH_NEV}', '{RESZLEG}', '{SZURES_NOBORDER}' ),
    array('?cache='.time(),  $css,    get_nev(),  $menuk,    $kereso,    $szures,    $tbalw,    $tjobbw,    $tartBal,    $tabla,    $keresodn,    $oldalNev,    $rendezes,    $rend_irany,    $rend_change,    $megn,    $self,    $get_get,    $csv_get,    $szerk_dis,    $belepve,    $lapozas,    $_GET['ksz'], $lepteto_ikonok,    $uj,    $duplikal,    $torles_icon, $csuszkadn,    $rendezes_rejt,    $mail,    $felh_nev,    $reszleg,    $szures_border   ),
    $header_sablon
);


// Előterjesztés felületei
if(basename(self())=='eloterj_fb.php' || basename(self())=='eloterj_bod.php'){
    $header_html .= '<script src="../js/eloterjesztes.js?cache='.time().'"></script>';
}
// Energia terv JS behúzva
if(basename(self())=='energia_terv.php'){
    $header_html .= '<script src="../js/energia_terv.js?cache='.time().'"></script>';
}
// Fenntartási terv JS behúzva
if(basename(self())=='fenntartasi_terv.php'){
    $header_html .= '<script src="../js/fenntartasi_terv.js?cache='.time().'"></script>';
}
// Munkalapokkal kapcsolatos JS-eket külön fájlba szedjük össze
if(basename(self())=='munkalap.php'){
    $header_html .= '<script src="../js/munkalap.js?cache='.time().'"></script>';
}
// Projektigénylésekkel kapcsolatos JS-eket külön fájlba szedjük össze
if(basename(self())=='projektigenyles.php'){
    $header_html .= '<script src="../js/projektigenyles.js?cache='.time().'"></script>';
}
// Projektkezeléssel kapcsolatos JS-eket külön fájlba szedjük össze
if(basename(self())=='projektkezeles.php'){
    $header_html .= '<script src="../js/projektkezeles.js?cache='.time().'"></script>';
}
// Projektszámlákkal kapcsolatos JS-eket külön fájlba szedjük össze
if(basename(self())=='projektszamla.php'){
    $header_html .= '<script src="../js/projektszamla.js?cache='.time().'"></script>';
}
// Projektszerződéssel kapcsolatos JS-eket külön fájlba szedjük össze
if(basename(self())=='projektszerzodes.php'){
    $header_html .= '<script src="../js/projektszerzodes.js?cache='.time().'"></script>';
}
$header_html .= '<script src="../js/jquery.fixedheadertable.js?cache='.time().'"></script>';

if(isIphone()){
    $header_html .= nl2('
        <style type="text/css">
            html, body{
                position: fixed;
                overflow: hidden;
            }
        </style>
    ');
}

if( $_SERVER['HTTP_HOST'] != 'demo.webdeb.hu' ) {
    $header_html .= nl2('
        <script>
            $(function() {
                $(document).on(\'contextmenu\', function(e){
                    e.preventDefault();
                    return false;
                });
            });
        </script>
    ');
}

// Nyomtatási popup
if( basename(self()) == 'energia_terv.php' ) {
    $header_html .= nl2('
        <div class="dn">
            <div id="nyomtatasi_nezet">
                Válasszon nyomtatási nézetet:
                <br><a class="link" onclick="listanezet_nyomtat( this );" target="_blank">» Energia tervek listája</a>
                <br><a class="link" onclick="egysegarak_nyomtat( this );" target="_blank">» Egységárak listája</a>
            </div>
        </div>
    ');
} elseif(basename(self()) == 'fenntartasi_terv.php') {
    $header_html .= nl2('
        <div class="dn">
            <div id="nyomtatasi_nezet">
                Válasszon nyomtatási nézetet:
                <br><a class="link" onclick="listanezet_nyomtat( this );" target="_blank">» Fenntartási tervek listája</a>
                <br><a class="link" onclick="egysegarak_nyomtat( this );" target="_blank">» Egységárak listája</a>
            </div>
        </div>
    ');
} else {
    $header_html .= nl2('
        <div class="dn">
            <div id="nyomtatasi_nezet">
                Válasszon nyomtatási nézetet:
                <br><a class="link" onclick="listanezet_nyomtat( this );" target="_blank">» Listanézet</a>
                <br><a href="#" target="_blank">» B nézet</a>
                <br><a href="#" target="_blank">» C nézet</a>
            </div>
        </div>
    ');
}

// Jogosultság tomb JS-be
$jogok = '';
foreach ($_SESSION[get_db_tabla().'rights'] as $jog){
    $jogok .= $jog;
}
$header_html .= nl2('
    <script>var _jogok = \''.$jogok.'\';</script>
');

echo $header_html.fajlbol_olvas('../sablon/admin/js_beagyazasok.html', true);
echo '<script>var SZURES_DB_LIMIT = '.SZURES_DB_LIMIT.';</script>';

if( $_SESSION[get_db_tabla().'login_recid'] ) {

    $felirat = '<h1 style="font-size: 2rem">Bejelentkezés</h1>';

    echo str_replace(
        array('{FELIRAT}', '{SELF}'),
        array( $felirat,    self() ),
        fajlbol_olvas('../sablon/admin/beleptet_popup.html')
    );

    echo fajlbol_olvas('../sablon/admin/popup.html');

} elseif( $_SESSION['nincs_jelsz'] && $_SESSION['nincs_jelsz_felhid'] ) {

    $_SESSION['kert_url'] = get_http().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

    $felirat = '';
    if( isset($_GET['jelszoures']) ) {
        $felirat = '<h1 style="font-size: 1.8rem">Kérem töltse ki a mezőket!</h1>';
    } elseif( isset($_GET['jelszonemazonos']) ) {
        $felirat = '<h1 style="font-size: 1.8rem">A két jelszó nem egyezik!</h1>';
    } elseif( isset($_GET['jelszohossz']) ) {
        $felirat = '<h1 style="font-size: 1.6rem">A jelszó hossza 8 és 20 karakter között legyen!</h1>';
    } elseif( isset($_GET['betuszam']) ) {
        $felirat = '<h1 style="font-size: 1.6rem">A jelszó betűket és számokat is tartalmazzon!</h1>';
    } else {
        $felirat = '<h1 style="font-size: 2rem">Válasszon jelszót</h1>';
    }

    $mit   = array('{FELIRAT}', '{SELF}'        );
    $mivel = array( $felirat,   'beleptetes.php');
    echo str_replace($mit, $mivel, fajlbol_olvas('../sablon/admin/nincs_jelszo.html', 1));

} else {

    // Ha még nincs kért URL, akkor az aktuális cím lesz az (tahát az index.php)
    if( !$_SESSION['kert_url'] ) {
        $_SESSION['kert_url'] = get_http().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    }

    $felirat = '';
    if( isset($_GET['hiba']) ) {
        $felirat = '<h1 style="font-size: 1.8rem">Helytelen felhasználó név vagy jelszó!</h1>';
    } else {
        $felirat = '<h1 style="font-size: 2rem">Bejelentkezés</h1>';
    }

    $mit   = array('{FELIRAT}', '{SELF}'        );
    $mivel = array( $felirat,   'beleptetes.php');
    echo str_replace($mit, $mivel, fajlbol_olvas('../sablon/admin/beleptetes.html', 1));
}

if( !$_SESSION[get_db_tabla().'login_recid'] ) {
    require_once('footer.php');
    exit();
}