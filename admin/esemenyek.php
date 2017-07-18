<?php
/** Tömbök létrehozása */

require_once('../weblap.php');

$_SESSION['defaultCsuszka'] = 400;

$disabled_szerk = false;
if (isset($_GET['modosit'])) {
    $disabled_szerk = true;
}

$_SESSION['ketHasabos'] = array(
    'anyagigenyles.php',
    'anyagrendeles.php',
    'bejelentes.php',
    'bejovo_szamlak.php',
    'beosztas.php',
    'cash_flow.php',
    'cikktorzs.php',
    'energia.php',
    'erteklista.php',
    'eseti_fenntartas.php',
    'eszkozkezeles.php',
    'felmeresi_terv.php',
    'fenntartasi_szerzodes.php',
    'ktghely.php',
    'ktgnem.php',
    'login.php',
    'munkalap.php',
    'projektkezeles.php',
    'projektigenyles.php',
    'raktar.php',
    'rendszer.php',
    'rendszeres_karbantartas.php',
    'szabadsagigeny.php',
    'szallito.php',
    'szamla.php',
    'szerepkor.php',
    'tervezes.php',
    'torzsadat.php',
    'utemezett_feladatok.php',
    'uzemeltetesi_naplo.php',
);

$tabla = basename(self());
if( isset($_GET['szamlaLista']) || isset($_GET['szamlaSorFrissit']) ) {
    $tabla = $_GET['tabla'].'.php';
}

$_OPTS = array();
switch($tabla) {
    case 'bejovo_szamlak.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = 'recid ikt_szam szallito targy netto statusz erkezett';

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('erkezett', 'e-szamla', 'torolve', 'ikt_szam', 'statusz');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'erkezett';
        }

        /* Default rendezési irány */
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'rendszer.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = 'recid nev rkat aktiv';

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('nev');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'nev';
        }

        /* Default rendezési irány */
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'asc';
        }
    break;
    case 'szallito.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = 'recid nev aktiv';

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('nev', 'id');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'nev';
        }

        /* Default rendezési irány */
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'asc';
        }
    break;
    case 'erteklista.php':
        $_OPTS['select'] = ''; //Függvényeknél, szamla_lista()-nál beállítva

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('csoport');

        /* Default rendezés */ //Függvényeknél, szamla_lista()-nál beállítva

    break;
    case 'login.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = nl2('
                                    `'.get_db_tabla().'login`.`recid`,
                                    `'.get_db_tabla().'login`.`nev`,
                                    `'.get_db_tabla().'login`.`teljes_nev`,
                                    `'.get_db_tabla().'login`.`aktiv`
                                ');

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('teljes_nev', 'aktiv', 'szerepkor_id');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'teljes_nev';
        }

        /* Default rendezési irány */
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'asc';
        }
    break;
    case 'szerepkor.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = 'recid nev';

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('nev');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'nev';
        }

        /* Default rendezési irány */
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'bejelentes.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = nl2('
                                `'.get_db_tabla().'bejelentes`.`recid`,
                                `'.get_db_tabla().'bejelentes`.`ikt_szam`,
                                `'.get_db_tabla().'bejelentes`.`statusz_id`,
                                `'.get_db_tabla().'bejelentes`.`surgos`,
                                `'.get_db_tabla().'bejelentes`.`telephely_id`,
                                `'.get_db_tabla().'bejelentes`.`csoport_id`,
                                `'.get_db_tabla().'bejelentes`.`obj_id`,
                                `'.get_db_tabla().'bejelentes`.`hiba_id`,
                                `'.get_db_tabla().'bejelentes_csoport`.`nev`,
                                `'.get_db_tabla().'telephely`.`nev`,
                                `'.get_db_tabla().'obj`.`nev`,
                                `'.get_db_tabla().'erteklista`.`info`
                                ');

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('ikt_szam', 'telephely_id');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'ikt_szam';
        }

        /* Default rendezési irány */
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'munkalap.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = nl2('
            `'.get_db_tabla().'munkalap`.`recid`,
            `'.get_db_tabla().'munkalap`.`ikt_szam`,
            `'.get_db_tabla().'munkalap`.`statusz_id`,
            `'.get_db_tabla().'munkalap`.`telephely_id`,
            `'.get_db_tabla().'munkalap`.`csoport_id`
        ');

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('recid');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'recid';
        }

        /* Default rendezési irány */
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'uzemeltetesi_naplo.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = 'recid ';

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('recid');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'recid';
        }

        /* Default rendezési irány */
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'szabadsagigeny.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = 'recid ';

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('recid');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'recid';
        }

        /* Default rendezési irány */
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'beosztas.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = nl2('
                                `'.get_db_tabla().'beosztas`.`recid`,
                                `'.get_db_tabla().'beosztas`.`cim`,
                                `'.get_db_tabla().'beosztas`.`telephely_id`,
                                `'.get_db_tabla().'beosztas`.`tol`,
                                `'.get_db_tabla().'beosztas`.`ig`,
                                `'.get_db_tabla().'telephely`.`nev`
                            ');

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('telephely_id');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'telephely_id';
        }
    break;
    case 'utemezett_feladatok.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = nl2('
                                    `'.get_db_tabla().'utemezett_feladatok`.`recid`,
                                    `'.get_db_tabla().'utemezett_feladatok`.`id`,
                                    `'.get_db_tabla().'utemezett_feladatok`.`nev`,
                                    `'.get_db_tabla().'utemezett_feladatok`.`telephely_id`,
                                    `'.get_db_tabla().'utemezett_feladatok`.`obj_id`,
                                    `'.get_db_tabla().'utemezett_feladatok`.`aktiv`,
                                    `'.get_db_tabla().'telephely`.`nev`,
                                    `'.get_db_tabla().'obj`.`nev`
                                ');


        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('aktiv', 'telephely_id', 'esnap_d');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'aktiv';
        }

        /* Default rendezési irány */
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'rendszeres_karbantartas.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = 'recid';

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('recid');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'recid';
        }

        /* Default rendezési irány */
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'projektigenyles.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = 'recid';

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('recid');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'recid';
        }

        /* Default rendezési irány */
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'cash_flow.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = 'recid';

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('recid');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'recid';
        }

        /* Default rendezési irány */
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'anyagigenyles.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = nl2('
                                    `'.get_db_tabla().'anyagigenyles`.`recid`,
                                    `'.get_db_tabla().'anyagigenyles`.`ikt_szam`,
                                    `'.get_db_tabla().'anyagigenyles`.`nev` as `anyagigenyles_nev`,
                                    `'.get_db_tabla().'anyagigenyles`.`telephely_id`
                                ');

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('ikt_szam', 'telephely_id');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'ikt_szam';
        }
    break;
    case 'energia.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = 'recid';

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('recid');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'recid';
        }

        /* Default rendezési irány */
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'torzsadat.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = 'recid nev';

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('nev');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'nev';
        }
    break;
    case 'anyagrendeles.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = 'recid';

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('recid');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'recid';
        }

        /* Default rendezési irány */
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'raktar.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = 'recid';

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('recid');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'recid';
        }

        /* Default rendezési irány */
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'tervezes.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = 'recid';

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('recid');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'recid';
        }

        /* Default rendezési irány */
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'felmeresi_terv.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = 'recid';

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('recid');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'recid';
        }

        /* Default rendezési irány */
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'eseti_fenntartas.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = 'recid';

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('recid');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'recid';
        }

        /* Default rendezési irány */
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'projektkezeles.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = 'recid';

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('recid');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'recid';
        }

        /* Default rendezési irány */
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'fenntartasi_szerzodes.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = 'recid';

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('recid');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'recid';
        }

        /* Default rendezési irány */
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'szamla.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = 'recid';

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('recid');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'recid';
        }

        /* Default rendezési irány */
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'cikktorzs.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = 'nev recid id';

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('nev');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'nev';
        }
    break;
    case 'ktghely.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = 'recid id nev';

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('id', 'nev');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'id';
        }

        /* Default rendezési irány */
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'asc';
        }
    break;
    case 'ktgnem.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = 'recid id nev';

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('id', 'nev');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'id';
        }

        /* Default rendezési irány */
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'asc';
        }
    break;
    case 'eszkozkezeles.php':
        /* Ezeket a tábla oszlopokat kérdezzük le baloldalra */
        $_OPTS['select']    = 'recid nev';

        /* Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani */
        $_OPTS['rendezes']  = array('nev');

        /* Default rendezés */
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'nev';
        }
    break;
}

// Csak új feltöltés esetén kell az aktuális időpont, egyébként a már eltároltat írjuk ki
$datum = '';
if(isset($_GET['uj'])){
	$datum = date('Y.m.d');
}

//TÖMBÖK:
// A tömbök alatt ezt fogjuk bővíteni
$postok = array();
//Mintaadatok speciális esetekre. Új tömböt inkább a teszt tömb átírásával hozz létre (vagy már meglevők alapján)
//$hir_tomb = array(
//  array('type' => 'p',           'megn' => 'akármi',     'class' => 'form_felirat'),
//  array('type' => 'text',        'megn' => 'Hír címe:',  'name' => 'nev', /*'nevid' => true (ilyen esetben a `nevid` oszlop létezzen)*/    'class' => 'oriasi/*pici | kicsi | normal | nagy | oriasi*//*', 'maxlength' => 100, 'alert' => 'Kérem töltse ki a címet!', 'szukit' => true/*, 'szukit_tabla' => 'kat|nev' <= így oldható meg, hogy ne az ID-ket írja ki, hanem a kat tábla név oszlopát*/, 'lista_style' => 'text-align: right'),
//  array('type' => 'select_kat',  'megn' => 'Kategória:', 'name' => 'kat',         'kulso_tabla' =>  'kat|nev' /* <= így oldahtó meg, hogy ne az ID-ket írja ki, hanem a kat tábla név oszlopát*/ ),
//  array('type' => 'text',        'megn' => 'Link:',      'name' => 'url',         'class' => 'oriasi', 'maxlength' => 100, 'pre' => 'http://'),
//  array('type' => 'textarea',    'megn' => 'Tartalom:',  'name' => 'text',        'class' => 'oriasi'/*,  'limit' => 500/*Limit wysiwyg-el nem kombinálható!*/ /*, 'wysiwyg' => 'Alap'/*Min vagy Full*/, 'feltoltes' => 'Images'/*Files Flash*//*, 'autogrow' => false*/, 'width' => '100%', 'height' => '200'/*<=WYSIWYG esetére*/),
//  array('type' => 'text',        'megn' => 'Listaár:',   'name' => 'ar',          'class' => 'kicsi', 'maxlength' => 10, 'ezresit' => true, 'post' => ' '.get_penznem()),
//  array('type' => 'file',        'megn' => 'Nyitókép:',  'name' => 'hir_kep',     'oszlop' => 'kep', 'algoritmus' => 'nev | fajlnev | id', 'fajl_torlessel' => true),
//  array('type' => 'jsfile',      'megn' => 'Nyitókép:',  'name' => 'termek_kep',  'tabla' => $megnevezes,  'oszlop' => 'kep',   'algoritmus' => 'nev'/*nev | fajlnev | id*/,   'meret' => 4194304, 'csakkep'  => true, 'fajlok_szama'  => 1),
//  array('type' => 'text',        'megn' => 'Dátum:',     'name' => 'datetime',    'class' => 'normal datepicker', 'value' => $datum),
//  array('type' => 'text',        'megn' => 'E-mail :',   'name' => 'mail',        'class' => 'oriasi', 'mail' => true, 'alert' => 'Kérem töltse ki a címet!', 'szukit' => true),
//  array('type' => 'dl',          'megn' => 'Város:',     'name' => 'varos',       'class' => 'oriasi',  'tabla' => 'teszt_hirl', 'oszlop' => 'nev', 'php_fajl' => '../dl.php', 'dl_kellid' => true, 'alert' =>  $szoveg_reg['varos'][$megsz]),
//  array('type' => 'select_VALAMI',   'megn' => 'Teszt:', 'name' => 'x',  'alert' =>  $szoveg_reg['varos'][$megsz]),
//  array('type' => 'checkbox',    'megn' => 'Aktív:', 'name' => 'aktiv',  'show' => true, 'class' => 'check'),
//  array('type' => 'submit',  'value' => 'Mentés',     'name' => 'mehet_gomb', 'class' => 'gombb'),
//
//);
//$megnevezes = 'hir';
//array_push($postok, array('tomb' => $hir_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));


//Termékek tömb
//$megnevezes = 'teszt';
//$teszt_tomb = $akt_tomb = array(
//	array('type' => 'text',        'megn' => 'Megnevezés:', 'name' => 'nev',           'maxlength' => 100,      'class' => 'oriasi', 'alert' => 'Kérem töltse ki a megnevezést!'),
//	array('type' => 'text',        'megn' => 'Listaár:',    'name' => 'ar',            'class' => 'kicsi',      'maxlength' => 10, 'ezresit' => true, 'post' => ' '.get_penznem()),
//    array('type' => 'jsfile',      'megn' => 'Nyitókép:',   'name' => 'termek_kep',    'tabla' => $megnevezes,  'oszlop' => 'kep',   'algoritmus' => 'nev'/*nev | fajlnev | id*/,  'meret' => 1572864, 'csakkep'  => false, 'fajlok_szama'  => 1),
//	array('type' => 'text',        'value' => $datum,       'megn' => 'Dátum:',        'name' => 'datetime',    'class' => 'normal datepicker'),
//    array('type' => 'textarea',    'megn' => 'Tartalom:',  'name' => 'text',        'class' => 'oriasi'/*,  'limit' => 500/*Limit wysiwyg-el nem kombinálható!*/ /*, 'wysiwyg' => 'Alap'/*Min vagy Full*/, 'feltoltes' => 'Images'/*Files Flash*//*, 'autogrow' => false*/, 'width' => '100%', 'height' => '200'/*<=WYSIWYG esetére*/),
//	array('type' => 'checkbox',    'megn' => 'Aktív:',      'name' => 'aktiv', 'class' => 'check'),
//
//	array('type' => 'submit', 'value' => 'Mentés',    'name' => 'mehet_gomb', 'class' => 'gombb'),
//);
//array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));

if( isset($_GET['uj']) ) {
    $csakUjkent = false;
} else {
    $csakUjkent = true;
}

/* Bejövő számlák */
$megnevezes = 'bejovo_szamlak';
$bejovo_szamlak_tomb = $akt_tomb = array(
    array('type' => 'text',                 'megn' => 'Iktató szám:',        'name' => 'ikt_szam',       'disabled' => $disabled,    'maxlength' => 250, 'class' => 'normal', 'osszevon' => true),
    array('type' => 'text',                 'megn' => 'Érkezett:',           'name' => 'erkezett',       'disabled' => $disabled,    'class' => 'kicsi datepicker', 'value' => $datum, 'alert' => 'Kérem töltse ki az érkezett dátumot!', 'osszevon' => true),
    array('type' => 'text',                 'megn' => 'Visszaadva:',         'name' => 'visszaadva',     'disabled' => $disabled,    'class' => 'kicsi datepicker', 'value' => $datum),
    array('type' => 'text',                 'megn' => 'Számla szám:',        'name' => 'szaml_szam',     'disabled' => $disabled,    'maxlength' => 250, 'class' => 'nagy', 'alert' => 'Kérem töltse ki a számlaszámot!', 'osszevon' => true),
    array('type' => 'checkbox',             'megn' => 'E-számla:',           'name' => 'e-szamla',       'disabled' => $disabled,    'class' => 'check', 'osszevon' => true),
    array('type' => 'checkbox',             'megn' => 'Törölve:',            'name' => 'torolve',        'disabled' => $disabled,    'class' => 'check'),
    array('type' => 'autocomplete',         'megn' => 'Szállító:',           'name' => 'szallito',       'disabled' => $disabled,    'class' => 'oriasi disableNav', 'tabla' => 'szallito', 'oszlop_keres' => 'nev', 'listaz' => 'nev'),
    array('type' => 'text',                 'megn' => 'Szállító szabadon:',  'name' => 'szallito_szab',  'disabled' => $disabled,    'maxlength' => 250,  'class' => 'oriasi'),
    array('type' => 'text',                 'megn' => 'Teljesítés:',         'name' => 'teljesites',     'disabled' => $disabled,    'class' => 'kicsi datepicker', 'value' => $datum, 'alert' => 'Kérem töltse ki a teljesítési dátumot!', 'osszevon' => true),
    array('type' => 'text',                 'megn' => 'Fiz. hat.:',          'name' => 'fiz_hat',        'disabled' => $disabled,    'class' => 'kicsi datepicker', 'value' => $datum, 'osszevon' => true),
    array('type' => 'select_fiz_mod',       'megn' => 'Fiz. mód:',           'name' => 'fiz_mod',        'disabled' => $disabled),
    array('type' => 'textarea',             'megn' => 'Tárgy:',              'name' => 'targy',          'disabled' => $disabled,    'class' => 'oriasiTargy'/*,  'limit' => 500/*Limit wysiwyg-el nem kombinálható!*/ /*, 'wysiwyg' => 'Alap'/*Min vagy Full*/, 'feltoltes' => 'Images'/*Files Flash*//*, 'autogrow' => false*/, 'width' => '100%', 'height' => '150'/*<=WYSIWYG esetére*/),
    array('type' => 'text',                 'megn' => 'Bruttó:',             'name' => 'brutto',         'disabled' => $disabled,    'class' => 'kicsi', 'maxlength' => 10, 'ezresit' => true, 'post' => ' '.get_penznem(), 'osszevon' => true, 'onkeyup' => 'this.value = onlyNumbers(this.value);'),
    array('type' => 'text',                 'megn' => 'Nettó:',              'name' => 'netto',          'disabled' => $disabled,    'class' => 'kicsi', 'maxlength' => 10, 'ezresit' => true, 'post' => ' '.get_penznem(), 'osszevon' => true, 'onkeyup' => 'this.value = onlyNumbers(this.value);'),
    array('type' => 'select_afa_tipus',     'megn' => 'Áfa típus:',          'name' => 'fiz_mod',        'disabled' => $disabled),
    array('type' => 'text',                 'megn' => 'Valuta Bruttó:',      'name' => 'valuta_brutto',  'disabled' => $disabled,    'class' => 'kicsi', 'maxlength' => 20, 'osszevon' => true, 'onkeyup' => 'this.value = onlyNumbers(this.value);'),
    array('type' => 'text',                 'megn' => 'Valuta nettó:',       'name' => 'valuta_netto',   'disabled' => $disabled,    'class' => 'kicsi', 'maxlength' => 20, 'osszevon' => true, 'onkeyup' => 'this.value = onlyNumbers(this.value);'),
    array('type' => 'text',                 'megn' => 'Árfolyam:',           'name' => 'arfolyam',       'disabled' => $disabled,    'class' => 'kicsi', 'maxlength' => 20),
    array('type' => 'text',                 'megn' => 'Részleg:',            'name' => 'reszleg',        'disabled' => $disabled,    'class' => 'normal disableNav', 'osszevon' => true),
    array('type' => 'text',                 'megn' => 'Átváltva:',           'name' => 'atvaltva',       'disabled' => $disabled,    'class' => 'kicsi datepicker', 'value' => $datum),
    array('type' => 'textarea',             'megn' => 'Megjegyzés:',         'name' => 'megj',           'disabled' => $disabled,    'class' => 'oriasiTargy'/*,  'limit' => 500/*Limit wysiwyg-el nem kombinálható!*/ /*, 'wysiwyg' => 'Alap'/*Min vagy Full*/, 'feltoltes' => 'Images'/*Files Flash*//*, 'autogrow' => false*/, 'width' => '100%', 'height' => '150'/*<=WYSIWYG esetére*/),
    array('type' => 'select_eszk_csop',     'megn' => 'Eszköz csoport:',     'name' => 'eszkoz_csop',    'disabled' => $disabled,    'osszevon' => true),
    array('type' => 'text',                 'megn' => 'Eszköz érték:',       'name' => 'eszkoz_ert',     'disabled' => $disabled,    'class' => 'kicsi', 'maxlength' => 10, 'ezresit' => true, 'post' => ' '.get_penznem()),
    array('type' => 'text',                 'megn' => 'Rendelés szám:',      'name' => 'rend_szam',      'disabled' => $disabled,    'maxlength' => 250, 'class' => '', 'osszevon' => true),
    array('type' => 'select_cimzett',       'megn' => 'Címzett:',            'name' => 'cimzett',        'disabled' => $disabled),
    array('type' => 'text',                 'megn' => 'Bev. szám:',          'name' => 'bev_szam',       'disabled' => $disabled,    'maxlength' => 250, 'class' => '', 'osszevon' => true),
    array('type' => 'select_ill_reszleg',   'megn' => 'Illetékes részleg:',  'name' => 'ill_reszleg',    'disabled' => $disabled),

    //array('type' => 'text',                 'megn' => 'Iktatva:',            'name' => 'iktatva',        'disabled' => $disabled,    'class' => 'kicsi datepicker', 'value' => $datum, 'osszevon' => true),
    //array('type' => 'text',                 'megn' => '',                    'name' => 'iktatta',        'disabled' => $disabled,    'maxlength' => 250, 'class' => ''),
    //array('type' => 'text',                 'megn' => 'Igazolásra átadva:',  'name' => 'igazolasra_adva','disabled' => $disabled,    'class' => 'kicsi datepicker', 'value' => $datum, 'osszevon' => true),
    //array('type' => 'text',                 'megn' => '',                    'name' => 'igazolasra_adta','disabled' => $disabled,    'maxlength' => 250, 'class' => ''),
    //array('type' => 'text',                 'megn' => 'Igazolva:',           'name' => 'igazolva',       'disabled' => $disabled,    'class' => 'kicsi datepicker', 'value' => $datum, 'osszevon' => true),
    //array('type' => 'text',                 'megn' => '',                    'name' => 'igazolta',       'disabled' => $disabled,    'maxlength' => 250, 'class' => ''),

    //array('type' => 'text',                 'megn' => 'Elküldve:',           'name' => 'elkuldve',       'disabled' => $disabled,    'class' => 'kicsi datepicker', 'value' => $datum, 'osszevon' => true),
    //array('type' => 'text',                 'megn' => '',                    'name' => 'elkuldte',       'disabled' => $disabled,    'maxlength' => 250, 'class' => ''),
    //array('type' => 'text',                 'megn' => 'Szolg.központ átvette:','name' => 'szkp_atvette', 'disabled' => $disabled,    'class' => 'kicsi datepicker', 'value' => $datum, 'osszevon' => true),
    //array('type' => 'text',                 'megn' => '',                    'name' => 'szkp_feldolg',   'disabled' => $disabled,    'maxlength' => 250, 'class' => ''),
    array('type' => 'checkbox',             'megn' => 'Kontírozási sablon:', 'name' => 'sablon',         'disabled' => $disabled,    'class' => 'check'),
    array('type' => 'bsz_statusz',          'megn' => 'Státusz:',            'disabled' => $disabled),

	//array('type' => 'submit', 'value' => 'Mentés',    'name' => 'mehet_gomb', 'class' => 'gombb'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));

$bejovo_szamlak_dok_tomb = $akt_tomb = array(
    array('type' => 'jsfile',               'megn' => 'Fájlok:',  'name' => 'termek_kep',  'tabla' => $megnevezes,  'oszlop' => 'kep_teszt',   'algoritmus' => 'fajlnev'/*nev | fajlnev | id*/,   'meret' => 31457280, 'csakkep'  => false, 'fajlok_szama'  => 5),
);

/* Kimeno számlák */
$megnevezes = 'kimeno_szamlak';
$kimeno_szamlak_tomb = $akt_tomb = array(
	array('type' => 'text',    'megn' => 'Név:',   'name' => 'nev',    'disabled' => $disabled,   'maxlength' => 250, 'class' => 'oriasi', 'alert' => 'Kérem töltse ki a megnevezést!'),

	//array('type' => 'submit', 'value' => 'Mentés',    'name' => 'mehet_gomb', 'class' => 'gombb'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));

//if (isset($_GET['teszt'])) {
//    $valami = array('Szolgáltatásra adott el?leg', 'Követk.időszakot terh.költség,ráfordítás', 'Segédanyag tisztítószer', 'Nyomtatvány-irodaszer', 'Számítástechnikai anyag', 'Munkaruha felhasználás', 'Szerszám felhasználás', 'Védőétel-ital', 'Műszaki anyag vegyes', 'Műszaki anyag üzemeltetés', 'Műszaki segédanyag', 'Műszaki rb. anyagok', 'Ivóvíz díj', 'Csatornázási díj', 'Termál-, és gyógyvíz', 'Gyógyvíz', 'Villamos energia', 'Gáz', 'Olaj', 'Tüzelőanyag', 'Üzemanyag', 'Egyéb', 'Üzleti reprezentáció', 'Mosási költségtérítés', 'Személygépkocsi költségtérítés', 'Munkábajárás', 'Egyéb költségtérítés', 'Személyzeti menü', 'Személyzeti étkezés hozzájárulás', 'Kazán - karbantartás', 'Klíma - karbantartás', 'Felvonó - karbantartás', 'Egyéb épületgépészeti karbantartás', 'Elektromos hálózat karbantartás', 'Szőnyeg, padló - karbantartás', 'Festés, tapétázás - karbantartás', 'Vízvezeték-hálózat karbantartás', 'Egyéb - karbantartás', 'Konyhai gép - karbantartás', 'Kárpitos, asztalos - karbantartás', 'Takarítógép (2010.01.01-től nem használatos)', 'Mosodai gép - karbantartás', 'Számítógép (2010.01.01-től nem használatos)', 'Irodatechnika, telefon karbantartás', 'Tv, videó, stúdió - karbantartás', 'Gyengeáramú hálózat karbantartás', 'Járművek (2010.01.01-től nem használatos)', 'Tűzjelző - karbantartás', 'Gyógyászati, fitness berendezés karbantartás', 'Egyéb épület karbantartás', 'Uszoda - karbantartás', 'Egyéb gép, berendezés', 'Kapcsolóberendezés karbantartás', 'Egyéb épületvillamossági karbantartás', 'Belföldi szállítás', 'Szemétszállítás', 'Egyéb, veszélyes hulladék szállítás', 'Belföldi  egyéb (parkolás, közlekedés)', 'Szoftver felügyelet és követés', 'Posta', 'Internet', 'Egyéb ki nem emelt anyagjellegű költség', 'Előző évek anyagjellegű költségei', 'Csatornahálózat karbantartás', 'Távfűtés', 'Kéményseprés és egyéb', 'Külső szerződéssel foglalkoztatott munka', 'Bérelt eszközök bérleti díja', 'Lízing díjak', 'Egyéb  bérleti díj', 'Egyéb számlázott oktatási, tanfolyami dí', 'Szakértői díj', 'Tv, videó, kábeltelevízió előfizetés', 'Közvetített szolgáltatás (leány-anyaváll', 'Közvetített szolgáltatás(más vállalkozó)', 'Egyéb közvetített szolgáltatás', 'Előző évek közvetített szolgáltatásai', 'Káreseményekkel kapcs.ktgek', 'Késedelmi kamat', 'Fizetett kártérítés', 'Hulladék értékesítés (fordított adózás)');
//    foreach($valami as $v){
//        $adatbazis_kapcsolat->i('rendszer', array('nev', 'rkat', 'aktiv'), array($v, 5, 1));
//    }
//    exit;
//}

/* Rendszer */
$megnevezes = 'ktghely';
$ktghely_tomb = $akt_tomb = array(
    array('type' => 'text',         'megn' => 'ID:',             'name' => 'id',      'disabled' => $csakUjkent,    'alert' => 'Kérem töltse ki az ID-t!', 'onblur' => 'id_foglalt_e(\''.$megnevezes.'.php\', $(this))', 'maxlength' => 7, 'style' => 'width: 7rem'),
    array('type' => 'text',         'megn' => 'Érték:',          'name' => 'nev',     'maxlength' => 250, 'class' => 'oriasi', 'alert' => 'Kérem töltse ki a megnevezést!',  'show' => true),

	//array('type' => 'submit', 'value' => 'Mentés',    'name' => 'mehet_gomb', 'class' => 'gombb'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));

/* Rendszer */
$megnevezes = 'ktgnem';
$ktgnem_tomb = $akt_tomb = array(
    array('type' => 'text',         'megn' => 'ID:',             'name' => 'id',      'disabled' => $csakUjkent,    'onblur' => 'id_foglalt_e(\''.$megnevezes.'.php\', $(this))', 'alert' => 'Kérem töltse ki az ID-t!', 'maxlength' => 7, 'style' => 'width: 7rem'),
    array('type' => 'text',         'megn' => 'Érték:',          'name' => 'nev',     'maxlength' => 250, 'class' => 'oriasi', 'alert' => 'Kérem töltse ki a megnevezést!',  'show' => true),

	//array('type' => 'submit', 'value' => 'Mentés',    'name' => 'mehet_gomb', 'class' => 'gombb'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));

/* Log */
$megnevezes = 'log';
$log_tomb = $akt_tomb = array(
	array('type' => 'text',            'megn' => 'Felh:',     'name' => 'felh_id',   'maxlength' => 250, 'class' => 'oriasi', 'kulso_tabla' => 'login|nev'),
	array('type' => 'text',            'megn' => 'Művelet:',  'name' => 'nev',       'maxlength' => 250, 'class' => 'oriasi'),
    array('type' => 'textarea',        'megn' => 'Változás:', 'name' => 'valtozas',  'class' => 'oriasi'/*,  'limit' => 500/*Limit wysiwyg-el nem kombinálható!*/, 'wysiwyg' => 'Min2'/*Min vagy Full*/, 'feltoltes' => 'Images'/*Files Flash*//*, 'autogrow' => false*/, 'width' => '100%', 'height' => '200'/*<=WYSIWYG esetére*/),
    array('type' => 'text',            'megn' => 'Dátum:',    'name' => 'datetime',  'class' => 'normal datepicker', 'value' => ''/*$datum*/),

	//array('type' => 'submit', 'value' => 'Mentés',    'name' => 'mehet_gomb', 'class' => 'gombb'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));

/* Szállítók */
$megnevezes = 'szallito';
$szallito_tomb = $akt_tomb = array(
    array('type' => 'checkbox',    'megn' => 'Aktív:',          'name' => 'aktiv',  'show' => true, 'class' => 'check'),
    array('type' => 'text',        'megn' => 'ID:',             'name' => 'id',     'disabled' => $disabled_szerk,  'maxlength' => 10, 'class' => 'kicsi',   'alert' => 'Kérem töltse ki az ID mezőt!', 'onkeyup' => 'ertekFoglalt({ID}this.value, \'szallito\', \'id\')'),
	array('type' => 'text',        'megn' => 'Név:',            'name' => 'nev',    'disabled' => $disabled_szerk,  'maxlength' => 250, 'class' => 'oriasi', 'alert' => 'Kérem töltse ki a megnevezést!',      'show' => true),
	array('type' => 'text',        'megn' => 'Adószám:',        'name' => 'adoszam','maxlength' => 250, 'class' => 'oriasi'),
	array('type' => 'text',        'megn' => 'Bankszámlaszám:', 'name' => 'bank',   'maxlength' => 250, 'class' => 'oriasi'),
	array('type' => 'text',        'megn' => 'Cg:',             'name' => 'cg',     'maxlength' => 250, 'class' => 'kicsi'),
	array('type' => 'text',        'megn' => 'Szállító kód:',   'name' => 'kod',    'maxlength' => 250, 'class' => 'kicsi'),
	array('type' => 'text',        'megn' => 'Irsz:',           'name' => 'isz',    'maxlength' => 250, 'class' => 'kicsi'),
	array('type' => 'text',        'megn' => 'Lev.cím:',        'name' => 'lcim',   'maxlength' => 250, 'class' => 'oriasi'),
	array('type' => 'text',        'megn' => 'Cím:',            'name' => 'cim',    'maxlength' => 250, 'class' => 'oriasi'),
	array('type' => 'text',        'megn' => 'Email:',          'name' => 'email',  'maxlength' => 250, 'class' => 'oriasi'),
	array('type' => 'text',        'megn' => 'Kapcs.név:',      'name' => 'knev',   'maxlength' => 250, 'class' => 'oriasi'),
	array('type' => 'text',        'megn' => 'Telefon:',        'name' => 'tel',    'maxlength' => 250, 'class' => 'oriasi'),
	array('type' => 'text',        'megn' => 'Telefon2:',       'name' => 'tel2',   'maxlength' => 250, 'class' => 'oriasi'),
	array('type' => 'text',        'megn' => 'Utca:',           'name' => 'utca',   'maxlength' => 250, 'class' => 'oriasi'),
	array('type' => 'text',        'megn' => 'Város:',          'name' => 'varos',  'maxlength' => 250, 'class' => 'oriasi'),

	//array('type' => 'submit', 'value' => 'Mentés',    'name' => 'mehet_gomb', 'class' => 'gombb'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));


/* Kódtár */
$megnevezes = 'erteklista';
$erteklista_tomb = $akt_tomb = array(
	//array('type' => 'text',             'megn' => 'ID:',         'name' => 'id',    'maxlength' => 250, 'class' => 'kicsi',     'alert' => 'Kérem töltse ki az ID-t!'),
    array('type' => 'select_erteklista', 'megn' => 'Csoport:',    'name' => 'csoport',    'disabled' => $csakUjkent,    'alert' =>  $szoveg_reg['varos'][$megsz],    'alert' => 'Kérem válassza ki a csoportot!'),
	array('type' => 'text',              'megn' => 'Érték:',      'name' => 'info',       'maxlength' => 250, 'class' => 'oriasi',    'alert' => 'Kérem töltse ki az értéket!'),

	//array('type' => 'submit', 'value' => 'Mentés',    'name' => 'mehet_gomb', 'class' => 'gombb'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));

/* Felhasználók */
$megnevezes = 'login';
$login_tomb = $akt_tomb = array(
    array('type' => 'checkbox',         'megn' => 'Aktív felhasználó:',         'name' => 'aktiv',          'class' => 'check',             'szuro' => 1, 'post' => '<span style="margin-right: 4rem"></span>',   'osszevon' => true),
    array('type' => 'checkbox',         'megn' => 'Virtuális felhasználó:',     'name' => 'virtual',        'class' => 'check',             'szuro' => 1),
    array('type' => 'text',             'megn' => 'Login név:',                 'name' => 'nev',            'class' => 'f_loginnev',        'szuro' => 1,   'disabled' => $disabled_szerk,  'maxlength' => 10, 'alert' => 'Kérem töltse ki a login nevet!', 'style' => 'width: 10rem'),
    array('type' => 'text',             'megn' => 'Név:',                       'name' => 'teljes_nev',     'class' => 'oriasi',            'szuro' => 1,                 'maxlength' => 250, 'alert' => 'Kérem töltse ki a nevet!'),
    array('type' => 'checkbox',         'megn' => 'Jelszóval rendelkezik:',     'name' => 'van_jelszo',     'class' => 'check',             'szuro' => 1),
    array('type' => 'autocomplete',     'megn' => 'Beosztás:',                  'name' => 'beosztas_id',    'class' => 'oriasi disableNav', 'szuro' => 1, 'tabla' => 'erteklista', 'oszlop_keres' => 'id', 'oszlop_keres_autocomp' => 'info', 'listaz' => 'info', 'csoport' => 18, 'id_kiir' => 'id', 'alert' => 'Kérem adja meg a beosztást!'),
    array('type' => 'autocomplete',     'megn' => 'Részleg:',                   'name' => 'reszleg_id',     'class' => 'oriasi disableNav', 'szuro' => 1, 'tabla' => 'erteklista', 'oszlop_keres' => 'id', 'oszlop_keres_autocomp' => 'info', 'listaz' => 'info', 'csoport' => 17, 'id_kiir' => 'id', 'alert' => 'Kérem adja meg a részleget!'),
    array('type' => 'text',             'megn' => 'E-mail:',                    'name' => 'usermail',       'class' => 'oriasi',            'szuro' => 1, 'maxlength' => 250),
    array('type' => 'text',             'megn' => 'Telefon:',                   'name' => 'usertel',        'class' => 'oriasi',            'szuro' => 1, 'maxlength' => 250),
    array('type' => 'autocomplete',     'megn' => 'Szerepkör:',                 'name' => 'szerepkor_id',   'class' => 'oriasi disableNav', 'szuro' => 1, 'tabla' => 'szerepkor', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'alert' => 'Kérem adja meg a szerepkört!'),
    array('type' => 'checkbox',         'megn' => '',                           'name' => 'fer',            'class' => 'check',             'szuro' => 0, 'post' => ' ontEAM emlékeztessen a feladataimra'),

	//array('type' => 'submit', 'value' => 'Mentés',    'name' => 'mehet_gomb', 'class' => 'gombb'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));


/* Szerepkör */
$megnevezes = 'szerepkor';
$szerepkor_tomb = $akt_tomb = array(
	array('type' => 'text',        'megn' => 'Név:',        'name' => 'nev',   'maxlength' => 250, 'alert' => 'Kérem töltse ki a nevet!', 'class' => 'oriasi'),

	//array('type' => 'submit', 'value' => 'Mentés',    'name' => 'mehet_gomb', 'class' => 'gombb'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));


/* Bejelentés */
$megnevezes = 'bejelentes';

if( isset($_GET['modosit']) && $_GET['id'] ) {
    $loginNev = $adatbazis_kapcsolat->s('loginnev', $megnevezes, 'recid='.$_GET['id'], '', '1');

    if( $loginNev ) {
        $loginNev = $loginNev[0]['loginnev'];

        /* Felhasználó lekérdezése a bejelentés táblához */
        $user = $adatbazis_kapcsolat->s('teljes_nev beosztas_id reszleg_id usertel usermail', 'login', "`nev`='{$loginNev}'", '', '1');
    }
} elseif( isset($_GET['uj']) ) {
    /* Felhasználó lekérdezése a bejelentés táblához */
    $user = $adatbazis_kapcsolat->s('teljes_nev beosztas_id reszleg_id usertel usermail', 'login', 'recid='.$_SESSION[get_db_tabla().'user_id'], '', '1');
}

//Diszpécser számára nem elérhető
$helyseg_valaszto = '<a href="javascript: obj_gen(); void(0);" class="obj_gen"><i class="material-icons">reorder</i></a>';
if(_jog(2)){
    $helyseg_valaszto = '';
}

if( $user ) {
    $user = $user[0];
}

//Bejelentések
$bejelentes_tomb = $akt_tomb = array(
    array('type' => 'statusz',         'megn' => 'Státusz:',            'oldal' => $megnevezes),
	array('type' => 'text',            'megn' => 'Iktató szám:',        'name' => 'ikt_szam',      'disabled' => true,       'maxlength' => 250, 'class' => 'oriasi'),
    array('type' => 'info',            'megn' => 'Név:',                'name' => 'felh_nev',      'value' => $user['teljes_nev']),
    array('type' => 'info',            'megn' => 'Beosztás:',           'value' => erteklista(18, $user['beosztas_id'])),
    array('type' => 'info',            'megn' => 'Részleg:',            'value' => erteklista(17, $user['reszleg_id'])),
    array('type' => 'info',            'megn' => 'Telefon:',            'value' => $user['usertel']),
    array('type' => 'info',            'megn' => 'E-mail:',             'value' => $user['usermail']),

    array('type' => 'select_felh_telephely',   'megn' => 'Szálloda:',   'name' => 'telephely_id', 'style' => 'width: 100%;', 'alert' => 'Kérem válasszon szállodát!', 'onchange' => 'szallodaVane( this );'),

    array('type' => 'autocomplete',    'megn' => 'Bejelentett hiba:',   'name' => 'hiba_id',        'class' => 'oriasi disableNav', 'tabla' => 'erteklista', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'info', 'listaz' => 'info', 'csoport' => 4, 'alert' => 'Kérem adja meg a bejelentett hibát!'),
    array('type' => 'textarea',        'megn' => 'Hiba leírása:',       'name' => 'hiba_text',      'disabled' => $disabled,    'class' => 'oriasiTargy'/*,  'limit' => 500/*Limit wysiwyg-el nem kombinálható!*/ /*, 'wysiwyg' => 'Alap'/*Min vagy Full*/, 'feltoltes' => 'Images'/*Files Flash*//*, 'autogrow' => false*/, 'width' => '100%', 'height' => '150'/*<=WYSIWYG esetére*/),

    array('type' => 'autocomplete',    'megn' => 'Helyiség:',           'name' => 'obj_id',  'class' => 'disableNav', 'style' => 'width: calc(100% - 3rem);', 'tabla' => 'obj', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'id_kiir' => 'recid', 'alert' => 'Kérem adja meg a Helyiséget!', 'onclick' => 'szallodaVane( this );', 'post' => $helyseg_valaszto),

    array('type' => 'textarea',        'megn' => 'Hely leírása:',       'name' => 'helyiseg_text',  'class' => 'oriasiTargy'/*,  'limit' => 500/*Limit wysiwyg-el nem kombinálható!*/ /*, 'wysiwyg' => 'Alap'/*Min vagy Full*/, 'feltoltes' => 'Images'/*Files Flash*//*, 'autogrow' => false*/, 'width' => '100%', 'height' => '150'/*<=WYSIWYG esetére*/),
    array('type' => 'autocomplete',    'megn' => 'Gépnév:',             'name' => 'gep_id',         'class' => 'oriasi disableNav', 'tabla' => 'eszkozkezeles', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'onclick' => 'helyisegVane( this );'),

    array('type' => 'checkbox',        'megn' => 'Sürgős',              'name' => 'surgos',         'class' => 'check'),
    array('type' => 'checkbox',        'megn' => '',                    'name' => 'telefon',        'class' => 'check', 'post' => ' közvetlenül telepített karbantartónak telefonon bejelentve'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));

$bejelentes_dok_tomb = $akt_tomb = array(
    array('type' => 'jsfile',               'megn' => 'Fájlok:',  'name' => 'termek_kep',  'tabla' => $megnevezes,  'oszlop' => 'kep_teszt',   'algoritmus' => 'fajlnev'/*nev | fajlnev | id*/,   'meret' => 31457280, 'csakkep'  => false, 'fajlok_szama'  => 5),
);


/* Munkalap */
$megnevezes = 'munkalap';
$munkalap_tomb = $akt_tomb = array(
    array('type' => 'statusz',                  'megn' => 'Státusz:',                   'oldal' => $megnevezes),
    array('type' => 'text',                     'megn' => 'Iktató szám:',               'name' => 'ikt_szam',           'disabled' => 1,        'maxlength' => 250, 'class' => 'kicsi', 'osszevon' => 1, 'post' => '<span style="margin-right: 4rem"></span>'),
    array('type' => 'checkbox',                 'megn' => 'Sürgős:',                    'name' => 'surgos',             'disabled' => 1,        'class' => 'check', 'osszevon' => 1, 'post' => '<span style="margin-right: 4rem"></span>'),
    array('type' => 'checkbox',                 'megn' => 'Visszaadva:',                'name' => 'visszaadva',         'disabled' => 1,        'class' => 'check'),
    array('type' => 'textarea',                 'megn' => 'Leírása:',                   'name' => 'text',               'disabled' => 0,        'class' => 'oriasiTargy'/*,  'limit' => 500/*Limit wysiwyg-el nem kombinálható!*/ /*, 'wysiwyg' => 'Alap'/*Min vagy Full*/, 'feltoltes' => 'Images'/*Files Flash*//*, 'autogrow' => false*/, 'width' => '100%', 'height' => '150'/*<=WYSIWYG esetére*/),
    array('type' => 'select_felh_telephely',    'megn' => 'Szálloda:',                  'name' => 'telephely_id',       'disabled' => 1,        'style' => 'width: 100%;', 'alert' => 'Kérem válasszon szállodát!', 'onchange' => 'szallodaVane( this );'),
    array('type' => 'autocomplete',             'megn' => 'Helyiség:',                  'name' => 'obj_id',             'disabled' => 1,        'class' => 'oriasi disableNav', 'style' => '', 'tabla' => 'obj', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'id_kiir' => 'recid', 'onclick' => '', 'post' => ''),
    array('type' => 'autocomplete',             'megn' => 'Gépnév:',                    'name' => 'gep_id',             'disabled' => 1,        'class' => 'oriasi disableNav', 'tabla' => 'eszkozkezeles', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'onclick' => 'helyisegVane( this );'),
    array('type' => 'autocomplete',             'megn' => 'Típus:',                     'name' => 'tipus_id',           'disabled' => 1,        'class' => 'kozepes disableNav', 'style' => 'width: 30rem', 'tabla' => 'erteklista', 'oszlop_keres' => 'id', 'oszlop_keres_autocomp' => 'info', 'listaz' => 'info', 'csoport' => 6, 'id_kiir' => 'id'),
    array('type' => 'select_szemelyzet',        'megn' => 'Hozzárendelt személyzet:',   'name' => 'szemelyzet_id',      'disabled' => 1),
    array('type' => 'text',                     'megn' => 'Kezdés/befejezési idő:',     'name' => 'elv_ido_tol',        'disabled' => 1,        'class' => 'ora_perc', 'osszevon' => 1, 'post' => ' -', 'style' => 'margin-right: 0.5rem'),
	array('type' => 'text',                     'megn' => '',                           'name' => 'elv_ido_ig',         'disabled' => 1,        'class' => 'ora_perc'),


    //array('type' => 'statusz',         'megn' => 'Státusz:',            'oldal' => 'bejelentes'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));



//Ütemezett feladatok
$nap_rejt = 'dn ';
$het_rejt = 'dn ';
$ho_rejt  = 'dn ';
if(basename(self())=='utemezett_feladatok.php' && isset($_GET['id'])){
    $uf = $adatbazis_kapcsolat->s('cikltip ciklaltip ciklnap ciklszorz aktkezd_d aktvege_d idoszakos kezdho kezdnap vegeho vegenap', 'utemezett_feladatok', "`recid`='{$_GET['id']}'");
    $uf = $uf[0];

    $uf['aktkezd_d'] = str_replace('-', '.', $uf['aktkezd_d']);
    $uf['aktvege_d'] = str_replace('-', '.', $uf['aktvege_d']);

    switch($uf['cikltip']){
        case 'd':
            $nap_rejt = '';
            if($uf['ciklaltip']==1){
                $ciklnap = $uf['ciklszorz'];
            }
            if($uf['ciklaltip']==2){
                $hetkoznapokent = ' checked';
            }
        break;
        case 'm':
            $ho_rejt  = '';
            if($uf['ciklaltip']==1){
                $ciklnap_honap = $uf['ciklszorz'];
            }
        break;
        case 'w':
            $het_rejt = '';

            $ciklszorz_w = $uf['ciklszorz'];
            //Kipiált hetek beállítása
            for($i=0; $i<strlen($uf['ciklnap']); $i++){
                switch($uf['ciklnap'][$i]){
                    case 1: $h_checked  = ' checked'; break;
                    case 2: $k_checked  = ' checked'; break;
                    case 3: $s_checked  = ' checked'; break;
                    case 4: $cs_checked = ' checked'; break;
                    case 5: $p_checked  = ' checked'; break;
                    case 6: $sz_checked = ' checked'; break;
                    case 7: $v_checked  = ' checked'; break;
                }
            }
        break;
    }

    //Időszakos ütemezés
    require_once 'selectek.php';
    require_once('../class/html/class.Input.php');
    require_once('../class/html/class.Select.php');

    $kezd_ho = new Select($select_honapok, $uf['kezdho']);
    $kezd_ho -> id      = 'kezdho';
    $kezd_ho -> name    = 'kezdho';
    $kezd_ho -> onchange = '$(\'#mehet_gomb\').attr(\'disabled\', false); egyszeri_nullaz();';
    $kezd_ho = $kezd_ho -> getHtml();

    $vege_ho = new Select($select_honapok, $uf['vegeho']);
    $vege_ho -> id      = 'vegeho';
    $vege_ho -> name    = 'vegeho';
    $kezd_ho -> onchange = '$(\'#mehet_gomb\').attr(\'disabled\', false); egyszeri_nullaz();';
    $vege_ho = $vege_ho -> getHtml();

    $idoszakos_dn = ' class="dn"';
    if($uf['idoszakos']){
        $idoszakos_dn = '';
    }

}

$megnevezes = 'utemezett_feladatok';
$utemezett_feladatok_tomb = $akt_tomb = array(
    array('type' => 'checkbox',                 'megn' => 'Aktív',              'name' => 'aktiv',         'class' => 'check'),
	array('type' => 'text',                     'megn' => 'Feladatszám:',       'name' => 'id',             'disabled' => true,       'maxlength' => 250, 'class' => 'oriasi'),
    array('type' => 'select_felh_telephely',    'megn' => 'Szálloda:',          'name' => 'telephely_id',   'style' => 'width: 100%;', 'alert' => 'Kérem válasszon szállodát!', 'onchange' => 'szallodaVane( this ); $(\'#obj_id_autocomp\').val(\'\'); $(\'#obj_id\').val(\'\'); $(\'#gep_id_autocomp\').val(\'\'); $(\'#gep_id\').val(\'\');'),
    array('type' => 'autocomplete',             'megn' => 'Helyiség:',          'name' => 'obj_id',         'class' => 'disableNav', 'style' => 'width: calc(100% - 3rem);', 'tabla' => 'obj', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'id_kiir' => 'recid'/*, 'alert' => 'Kérem adja meg a Helyiséget!'*/, 'onclick' => 'szallodaVane( this );', 'post' => '<a href="javascript: obj_gen(); void(0);" class="obj_gen"><svg height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M4 10.5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zm0-6c-.83 0-1.5.67-1.5 1.5S3.17 7.5 4 7.5 5.5 6.83 5.5 6 4.83 4.5 4 4.5zm0 12c-.83 0-1.5.68-1.5 1.5s.68 1.5 1.5 1.5 1.5-.68 1.5-1.5-.67-1.5-1.5-1.5zM7 19h14v-2H7v2zm0-6h14v-2H7v2zm0-8v2h14V5H7z"/><path d="M0 0h24v24H0V0z" fill="none"/></svg></a>', 'oninput' => '$(\'#gep_id_autocomp\').val(\'\'); $(\'#gep_id\').val(\'\');'),
    array('type' => 'autocomplete',             'megn' => 'Gépnév:',            'name' => 'gep_id',         'class' => 'oriasi disableNav', 'tabla' => 'eszkozkezeles', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'onclick' => 'helyisegVane( this );'),
	array('type' => 'text',                     'megn' => 'Feladat:',           'name' => 'nev',            'maxlength' => 250, 'class' => 'oriasi'),
	array('type' => 'checkbox',                 'megn' => 'Hatósági:',          'name' => 'hatosagi', 'osszevon' => 1, 'post' => '<span style="margin-left: 2rem"></span>'),
	array('type' => 'text',                     'megn' => 'Utoljára ütemezve:', 'name' => 'utnap_d',    'class' => 'datepicker', 'osszevon' => 1, 'post' => '<span style="margin-left: 2rem"></span>'),
	array('type' => 'text',                     'megn' => 'Hatályos:',          'name' => 'hataly_d',   'class' => 'datepicker'),
	array('type' => 'text',                     'megn' => 'Utolsó esedékesség:','name' => 'utesnap_d',  'class' => 'datepicker'),
	array('type' => 'text',                     'megn' => 'Riasztás:',          'name' => 'riaszt',         'maxlength' => 250, 'style' => 'width: 3.5rem', 'post' => ' nappal az esedékesség előtt'),
    array('type' => 'textarea',                 'megn' => 'Leírás:',            'name' => 'leiras',      'disabled' => $disabled,    'class' => 'oriasiTargy'/*,  'limit' => 500/*Limit wysiwyg-el nem kombinálható!*/ /*, 'wysiwyg' => 'Alap'/*Min vagy Full*/, 'feltoltes' => 'Images'/*Files Flash*//*, 'autogrow' => false*/, 'width' => '100%', 'height' => '150'/*<=WYSIWYG esetére*/),
	array('type' => 'select_szemelyzet',        'megn' => 'Hozzárendelt személyzet:',  'name' => 'szemelyzet_id'),
//	array('type' => 'text',                     'megn' => 'Ütemezési időszak:', 'name' => 'aktkezd_d', 'class' => 'datepicker', 'osszevon' => 1),
//	array('type' => 'text',                     'megn' => ' - ',                'name' => 'aktvege_d', 'class' => 'datepicker'),

	array('type' => 'html',                     'megn' => '<div class="dtc" style="padding: 1rem 0 1rem 0">'),
        array('type' => 'select_cikltip',           'megn' => 'Ütemezés típus:',    'name' => 'cikltip', 'class' => 'f_cikltip'),
	array('type' => 'html',                     'megn' => '</div>'),
	array('type' => 'html',                     'megn' => '<div class="dtc" style="padding: 1rem 0 1rem 1rem"><fieldset style="width: auto; margin-top: 1rem">'),
        array('type' => 'text',                     'megn' => '<span style="margin-left: 0.7rem"></span>Esedékes:',         'name' => 'esnap_d',     'class' => 'datepicker'),

        //Naponta
        array('type' => 'html',                     'megn' => nl2('<div id="tipus_d" style="padding: 0" class="'.$nap_rejt.'cikltip">')),
        array('type' => 'text',                     'megn' => '',                   'name' => 'ciklnap',       'pre' => 'Ismétlődik ', 'post' => ' naponta', 'style' => 'width: 4rem', 'maxlength' => 2, 'onkeyup' => 'this.value = onlyNumbers(this.value); $(\'#hetkoz_kent\').prop(\'checked\', false)', 'value' => $ciklnap),
        array('type' => 'html',                     'megn' => nl2('
                                                                    <div class="cb">
                                                                        <input'.$hetkoznapokent.' name="hetkoz_kent" id="hetkoz_kent" onclick="$(\'#ciklnap\').val(\'\')" type="checkbox">
                                                                        <label for="hetkoz_kent" onclick="$(\'#mehet_gomb\').attr(\'disabled\', false);"></label>
                                                                    </div> Ismétlődik minden hétköznap
                                                                ')),
        array('type' => 'html',                     'megn' => nl2('</div>')),

        //Hetente
        array('type' => 'html',                     'megn' => nl2('<div id="tipus_w" style="padding: 0" class="'.$het_rejt.'cikltip">')),
        array('type' => 'html',                     'megn' => nl2('
                                                            <div class="dtc form_jobb">
                                                                Ismétlődik <input maxlength="2" name="ism_het" id="ism_het" onkeyup="this.value = onlyNumbers(this.value); $(\'#mehet_gomb\').attr(\'disabled\', false);" style="width: 4rem" type="text" value="'.$ciklszorz_w.'">
                                                                hetente a következő napokon:
                                                            </div>
                                                            <div class="cb">
                                                                <input name="h" id="h" type="checkbox"'.$h_checked.'>
                                                                <label for="h" onclick="$(\'#mehet_gomb\').attr(\'disabled\', false);"></label>
                                                            </div>
                                                            <label for="h" class="link"> hétfő</label>

                                                            <span class="formText"></span>
                                                            <div class="cb">
                                                                <input name="k" id="k" type="checkbox"'.$k_checked.'>
                                                                <label for="k" onclick="$(\'#mehet_gomb\').attr(\'disabled\', false);"></label>
                                                            </div>
                                                            <label for="k" class="link"> kedd</label>

                                                            <span class="formText"></span>
                                                            <div class="cb">
                                                                <input name="s" id="s" type="checkbox"'.$s_checked.'>
                                                                <label for="s" onclick="$(\'#mehet_gomb\').attr(\'disabled\', false);"></label>
                                                            </div>
                                                            <label for="s" class="link"> szerda</label>

                                                            <span class="formText"></span>
                                                            <div class="cb">
                                                                <input name="cs" id="cs" type="checkbox"'.$cs_checked.'>
                                                                <label for="cs" onclick="$(\'#mehet_gomb\').attr(\'disabled\', false);"></label>
                                                            </div>
                                                            <label for="cs" class="link"> csütörtök</label>

                                                            <span class="formText"></span>
                                                            <div class="cb">
                                                                <input name="p" id="p" type="checkbox"'.$p_checked.'>
                                                                <label for="p" onclick="$(\'#mehet_gomb\').attr(\'disabled\', false);"></label>
                                                            </div>
                                                            <label for="p" class="link"> péntek</label>

                                                            <span class="formText"></span>
                                                            <div class="cb">
                                                                <input name="sz" id="sz" type="checkbox"'.$sz_checked.'>
                                                                <label for="sz" onclick="$(\'#mehet_gomb\').attr(\'disabled\', false);"></label>
                                                            </div>
                                                            <label for="sz" class="link"> szombat</label>

                                                            <span class="formText"></span>
                                                            <div class="cb">
                                                                <input name="v" id="v" type="checkbox"'.$v_checked.'>
                                                                <label for="v" onclick="$(\'#mehet_gomb\').attr(\'disabled\', false);"></label>
                                                            </div>
                                                            <label for="v" class="link"> vasárnap</label>
                                            ')),
        array('type' => 'html',                     'megn' => nl2('</div>')),

        //Havonta
        array('type' => 'html',                     'megn' => nl2('<div id="tipus_m" style="padding: 0" class="'.$ho_rejt.'cikltip">')),
        array('type' => 'text',                     'megn' => '',                   'name' => 'ciklho',     'pre' => 'Ismétlődik ', 'post' => ' havonta a hónap ', 'style' => 'width: 4rem', 'maxlength' => 2, 'onkeyup' => 'this.value = onlyNumbers(this.value); $(\'#ciklho2\').val(\'\'); $(\'#ciklhet\').val(\'\'); $(\'#ho_rendsz_het\').val(\'\');', 'osszevon' => 1, 'value' => $ciklnap_honap),
        array('type' => 'select_honapjan',          'megn' => '',                   'name' => 'honapjan',                               'post' => ' napján'),
        array('type' => 'text',                     'megn' => '',                   'name' => 'ciklho2',    'pre' => 'Ismétlődik ', 'post' => ' havonta a ', 'style' => 'width: 4rem', 'maxlength' => 2, 'onkeyup' => 'this.value = onlyNumbers(this.value); $(\'#ciklho\').val(\'\'); $(\'#honapjan\').val(\'\')', 'osszevon' => 1),
        array('type' => 'select_ho_ism',            'megn' => '',                   'name' => 'ciklhet',        'osszevon' => 1),
        array('type' => 'select_ho_soran',          'megn' => '',                   'name' => 'ho_rendsz_het',  'post' => ' során'),
        array('type' => 'html',                     'megn' => nl2('</div>')),

	array('type' => 'html',                     'megn' => '</fieldset></div>'),

    array('type' => 'checkbox',                 'megn' => 'Időszakos ütemezés:','name' => 'idoszakos',   'class' => 'check', 'onclick' => '$(\'#idoszakos_utem\').toggle()', 'pre' => nl2('<div class="dtc vam">'), 'post' => nl2('
                                                                        </div>
                                                                        <div class="dtc vam">
                                                                            <fieldset'.$idoszakos_dn.' id="idoszakos_utem">
                                                                                Szokásos ütemezés '.$kezd_ho.'
                                                                                <input type="text" id="kezdnap" name="kezdnap" value="'.$uf['kezdnap'].'" style="width: 3.5rem" maxlength="2" onfocus="egyszeri_nullaz()"> . napjától - '.$vege_ho.'
                                                                                <input type="text" id="vegenap" name="vegenap" value="'.$uf['vegenap'].'" style="width: 3.5rem" maxlength="2" onfocus="egyszeri_nullaz()"> . napjáig
                                                                                <div style="margin-top: 0.5rem">
                                                                                    Egyszeri ütemezés
                                                                                    <input class="datepicker" id="aktkezd_d" name="aktkezd_d" value="'.$uf['aktkezd_d'].'" type="text" onblur="szokasos_nullaz()">
                                                                                    <span class="formText"> - </span>
                                                                                    <input class="datepicker" id="aktvege_d" name="aktvege_d" value="'.$uf['aktvege_d'].'" type="text" onblur="szokasos_nullaz()">
                                                                                </div>
                                                                            </fieldset>
                                                                        </div>
        ')),

	array('type' => 'text',                     'megn' => 'Elvégzési idő:',     'name' => 'elv_ido_tol', 'class' => 'ora_perc', 'osszevon' => 1, 'post' => ' -', 'style' => 'margin-right: 0.5rem'),
	array('type' => 'text',                     'megn' => '',                   'name' => 'elv_ido_ig',  'class' => 'ora_perc'),


	//array('type' => 'submit', 'value' => 'Mentés',    'name' => 'mehet_gomb', 'class' => 'gombb'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));



//Rendszeres karbantartás
$megnevezes = 'rendszeres_karbantartas';
$rendszeres_karbantartas_tomb = $akt_tomb = array(

);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));



//Anyagigénylés
$megnevezes = 'anyagigenyles';
$tav_04 = '<span style="margin-left: 0.5rem"></span>';
$tav_12 = '<span style="margin-left: 1.2rem"></span>';
$tav_26 = '<span style="margin-left: 2.6rem"></span>';
$tav_4  = '<span style="margin-left: 4rem"></span>';
$anyagigenyles_tomb = $akt_tomb = array(
    array('type' => 'statusz',                  'megn' => 'Státusz:',                       'oldal' => $megnevezes),
    array('type' => 'text',                     'megn' => 'Iktató szám:',                   'name' => 'ikt_szam',       'szuro' => 1,   'disabled' => 1,   'class' => 'normal', 'osszevon' => 1, 'post' => '<span style="margin-left: 4rem"></span>', 'style' => 'width: 8rem'),
    array('type' => 'checkbox',                 'megn' => 'Készpénzes:',                    'name' => 'keszpenz',       'szuro' => 1,   'disabled' => 1,   'post' => '<span style="margin-left: 2rem"></span><input type="button" value="rendelés kép" class="gomb">'),
    array('type' => 'select_felh_telephely',    'megn' => 'Szálloda:',                      'name' => 'telephely_id',   'szuro' => 1,   'disabled' => 1,   'style' => 'width: 100%;', 'alert' => 'Kérem válasszon szállodát!', 'onchange' => 'szallodaVane( this );'),
    array('type' => 'text',                     'megn' => 'Cikkszám:',                      'name' => 'cikktorzs_id',   'szuro' => 1,   'disabled' => 1),
    array('type' => 'text',                     'megn' => 'Cikk név:',                      'name' => 'nev',            'szuro' => 1,   'disabled' => 1,   'class' => 'oriasi disableNav', 'tabla' => 'cikktorzs', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev'),
    array('type' => 'text',                     'megn' => 'Igényelt mennyiség:',            'name' => 'igmen',          'szuro' => 1,   'disabled' => 1,   'class' => 'f_num', 'post' => ' db<span style="margin-left: 2rem"></span>', 'osszevon' => 1),
    array('type' => 'checkbox',                 'megn' => 'RB-s anyag:',                    'name' => 'kulsosnek',      'szuro' => 1,   'disabled' => 1),
    array('type' => 'text',                     'megn' => 'Jóváhagyott mennyiség:',         'name' => 'jhmen',          'szuro' => 1,   'disabled' => 1,   'class' => 'f_num',   'post' => ' db<span style="margin-left: 2rem"></span>', 'osszevon' => 1),
    array('type' => 'text',                     'megn' => $tav_4.'Rendelés szám:',          'name' => 'rndid',          'szuro' => 1,   'disabled' => 1,   'style' => 'width: 7rem'),
    array('type' => 'text',                     'megn' => 'Rendelt mennyiség:',             'name' => 'rndmen',         'szuro' => 1,   'disabled' => 1,   'class' => 'f_num',   'data' => 'data-tort="4"',   'post' => ' db<span style="margin-left: 2rem"></span>', 'osszevon' => 1),
    array('type' => 'text',                     'megn' => $tav_12.'Rendelési egységár:',    'name' => 'rndear',         'szuro' => 1,   'disabled' => 1,   'class' => 'f_num',   'data' => 'data-tort="4"',   'post' => ' '.  get_penznem()),
    array('type' => 'text',                     'megn' => 'Beérkezett mennyiség:',          'name' => 'erkmen',         'szuro' => 1,   'disabled' => 1,   'class' => 'f_num',   'post' => ' db<span style="margin-left: 2rem"></span>', 'osszevon' => 1),
    array('type' => 'text',                     'megn' => $tav_04.'Beérkezési egységár:',   'name' => 'erkear',         'szuro' => 1,   'disabled' => 1,   'class' => 'f_num',   'post' => ' '.  get_penznem()),
    array('type' => 'autocomplete',             'megn' => 'Pénznem:',                       'name' => 'kcs_21',         'szuro' => 1,   'disabled' => 1,   'class' => 'disableNav', 'tabla' => 'erteklista', 'oszlop_keres' => 'id', 'oszlop_keres_autocomp' => 'info', 'listaz' => 'info', 'csoport' => 21, 'id_kiir' => 'id', 'alert' => 'Kérem adja meg a pénznemet!', 'style' => 'width: 5rem'),
    array('type' => 'text',                     'megn' => 'Deviza rend. egységár:',         'name' => 'vrndear',        'szuro' => 1,   'disabled' => 1,   'class' => 'f_num',   'data' => 'data-tort="4"',   'osszevon' => 1),
    array('type' => 'text',                     'megn' => $tav_26.'Deviza beérk. egységár:','name' => 'verkear',        'szuro' => 1,   'disabled' => 1,   'class' => 'f_num',   'data' => 'data-tort="4"'),
    array('type' => 'autocomplete',             'megn' => 'Szállító:',                      'name' => 'szallito',       'szuro' => 1,   'disabled' => 1,   'class' => 'oriasi disableNav', 'tabla' => 'szallito', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev'),

);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));
//Anyagigénylésnél a feltöltésnek az űrlapja minimalizált
$anyagigenyles_tomb_feltolt = array(
    array('type' => 'select_felh_telephely',    'megn' => 'Szálloda:',                      'name' => 'telephely_id',   'style' => 'width: 100%;', 'alert' => 'Kérem válasszon szállodát!', 'onchange' => 'szallodaVane( this );'),
    array('type' => 'autocomplete',             'megn' => 'Cikk név:',                      'name' => 'nev',            'class' => 'oriasi disableNav', 'tabla' => 'cikktorzs', 'id_kiir' => 'id', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'alert' => 'Kérem adja meg a kívánt cikket!'),
    array('type' => 'text',                     'megn' => 'Igényelt mennyiség:',            'name' => 'igmen',          'class' => 'f_num f_anyagig_menny_feltolt', 'post' => '<span style="margin-left: 2rem"></span>', 'osszevon' => 1, 'alert' => 'Kérem adja meg a mennyiséget!'),
    array('type' => 'checkbox',                 'megn' => 'RB-s anyag:',                    'name' => 'kulsosnek'),

);


//Anyagrendelés
$megnevezes = 'anyagrendeles';
$anyagrendeles_tomb = $akt_tomb = array(

);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));



//Felmérési terv
$megnevezes = 'raktar';
$raktar_tomb = $akt_tomb = array(

);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));



//Felmérési terv
$megnevezes = 'felmeresi_terv';
$felmeresi_terv_tomb = $akt_tomb = array(

);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));



//Törzsadatok
$megnevezes = 'torzsadat';
$torzsadat_tomb = $akt_tomb = array(
    array('type' => 'checkbox',             'megn' => 'Aktív:',                 'name' => 'aktiv',                                  'szuro' => 1,   'class' => 'check'),
	array('type' => 'text',                 'megn' => 'Munkavállaló neve:',     'name' => 'nev',            'maxlength' => 250,     'szuro' => 1,   'class' => 'oriasi'),
	array('type' => 'text',                 'megn' => 'Születési ideje:',       'name' => 'szul_datum',                             'szuro' => 1,   'class' => 'kicsi datepicker'),
	array('type' => 'text',                 'megn' => 'Szakmája:',              'name' => 'szakma',         'maxlength' => 250,     'szuro' => 1,   'class' => 'oriasi'),
    array('type' => 'select_szemelyzet',    'megn' => 'Beosztása:',             'name' => 'szemelyzet_id',                          'szuro' => 1),
	array('type' => 'text',                 'megn' => 'Munkaviszony kezdete:',  'name' => 'munka_kezd',                             'szuro' => 1,   'class' => 'kicsi datepicker'),
	array('type' => 'text',                 'megn' => 'Kompetenciák:',          'name' => 'komp',           'maxlength' => 250,     'szuro' => 1,   'class' => 'oriasi'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));



//Beosztások
$megnevezes = 'beosztas';
$beosztas_tomb = $akt_tomb = array(
	array('type' => 'text',                     'megn' => 'Cím:',               'name' => 'cim',                                'szuro' => 1,   'class' => 'oriasi',       'maxlength' => 250),
    array('type' => 'select_felh_telephely',    'megn' => 'Szálloda:',          'name' => 'telephely_id',   'disabled' => 1,    'szuro' => 1,    'style' => 'width: 100%;', 'alert' => 'Kérem válasszon szállodát!', 'onchange' => 'szallodaVane( this ); '),
	array('type' => 'text',                     'megn' => 'Időszak:',           'name' => 'tol',            'disabled' => 1,    'szuro' => 1,   'class' => 'kicsi datepicker', 'megn_szurobe' => 'Időpont tól', 'osszevon' => 1),
	array('type' => 'text',                     'megn' => ' - ',                'name' => 'ig',             'disabled' => 1,    'szuro' => 1,   'class' => 'kicsi datepicker', 'megn_szurobe' => 'Időpont ig'),
	array('type' => 'text',                     'megn' => 'Készítette:',        'name' => 'insert_usr',     'disabled' => 1,    'szuro' => 1,   'class' => 'oriasi'),
	array('type' => 'text',                     'megn' => 'Készítés ideje:',    'name' => 'insert_dt',      'disabled' => 1,    'szuro' => 1,   'class' => 'datetimepicker'),
	array('type' => 'text',                     'megn' => 'Utoljára mentette:', 'name' => 'update_usr',     'disabled' => 1,    'szuro' => 1,   'class' => 'oriasi'),
	array('type' => 'text',                     'megn' => 'Mentés ideje:',      'name' => 'update_dt',      'disabled' => 1,    'szuro' => 1,   'class' => 'datetimepicker'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));

$megnevezes = 'beosztas_naptar';
$beosztas_naptar_tomb = $akt_tomb = array(
	array('type' => 'text',                     'megn' => 'Időszak:',           'name' => 'tol',            'class' => 'kicsi datetimepicker', 'megn_szurobe' => 'Időszak tól', 'maxlength' => 16, 'alert' => 'Kérjük adja meg az időszak kezdetét!', 'osszevon' => 1   ),
	array('type' => 'text',                     'megn' => ' - ',                'name' => 'ig',             'class' => 'kicsi datetimepicker', 'megn_szurobe' => 'Időszak ig',  'maxlength' => 16, 'alert' => 'Kérjük adja meg az időszak végét!'                       ),
    array('type' => 'select_beosztas',          'megn' => 'Beosztás tipus:',    'name' => 'beosztas',       'onchange' => 'if($(this).val()==1){ $(\'.beosztas_rejt\').removeClass(\'dn\'); }else{ $(\'.beosztas_rejt\').addClass(\'dn\'); }',          'alert' => 'Kérjük adja meg a beosztás típusát!'),
    array('type' => 'select_szemelyzet',        'megn' => 'Szerepkör:',         'name' => 'szerepkor',      'dtr_class' => ' dn beosztas_rejt'),
    array('type' => 'autocomplete',             'megn' => 'Név:',               'name' => 'felh_loginnev',  'class' => 'disableNav',   'tabla' => 'torzsadat',  'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'id_kiir' => 'recid', 'alert' => 'Kérem adja meg a személy nevét!'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));



//Szabadságigény
$megnevezes = 'szabadsagigeny';
$szabadsagigeny_tomb = $akt_tomb = array(

);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));



//Projektigénylés
$megnevezes = 'projektigenyles';
$projektigenyles_tomb = $akt_tomb = array(

);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));


//Cash-flow
$megnevezes = 'cash_flow';
$cash_flow_tomb = $akt_tomb = array(

);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));


//Energia
$megnevezes = 'energia';
$energia_tomb = $akt_tomb = array(

);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));



//Energia
$megnevezes = 'uzemeltetesi_naplo';
$uzemeltetesi_naplo_tomb = $akt_tomb = array(

);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));





//Eseti fenntartás
$megnevezes = 'eseti_fenntartas';
$eseti_fenntartas_tomb = $akt_tomb = array(

);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));





//Eseti fenntartás
$megnevezes = 'fenntartasi_szerzodes';
$fenntartasi_szerzodes_tomb = $akt_tomb = array(

);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));



//Számlák
$megnevezes = 'szamla';
$szamla_tomb = $akt_tomb = array(

);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));





//Cikktörzs
$megnevezes = 'cikktorzs';
$cikktorzs_tomb = $akt_tomb = array(
	array('type' => 'text',                     'megn' => 'Név:',                   'name' => 'nev',        'maxlength' => 250, 'class' => 'oriasi', 'alert' => 'Kérem töltse ki a nevet!'),
	array('type' => 'text',                     'megn' => 'Oracle cikkszám:',       'name' => 'oracikkid',  'maxlength' => 10,  'style' => 'width: 9rem',     'alert' => 'Kérem töltse ki az Oracle cikkszámot!'),
	array('type' => 'text',                     'megn' => 'Mee:',                   'name' => 'cikkmee',    'maxlength' => 5,   'style' => 'width: 5.1rem',   'alert' => 'Kérem töltse ki a Mee-t!'),
    array('type' => 'autocomplete',             'megn' => 'Szakterület:',           'name' => 'szakter_id', 'class' => 'oriasi disableNav', 'tabla' => 'erteklista', 'oszlop_keres' => 'id', 'oszlop_keres_autocomp' => 'info', 'listaz' => 'info', 'csoport' => 19, 'id_kiir' => 'id', 'alert' => 'Kérem adja meg a szakterületet!'),
    array('type' => 'textarea',                 'megn' => 'Megjegyzés:',            'name' => 'leiras',     'disabled' => $disabled,    'class' => 'oriasiTargy'/*,  'limit' => 500/*Limit wysiwyg-el nem kombinálható!*/ /*, 'wysiwyg' => 'Alap'/*Min vagy Full*/, 'feltoltes' => 'Images'/*Files Flash*//*, 'autogrow' => false*/, 'width' => '100%', 'height' => '150'/*<=WYSIWYG esetére*/),
    array('type' => 'checkbox',                 'megn' => 'Szerelési segédanyag:',  'name' => 'segedanyag', 'class' => 'check'),
    array('type' => 'checkbox',                 'megn' => 'Helyi beszerzésű:',      'name' => 'helyi',      'class' => 'check'),

	//array('type' => 'submit', 'value' => 'Mentés',    'name' => 'mehet_gomb', 'class' => 'gombb'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));



//Gépek
$megnevezes = 'eszkozkezeles';
$eszkozkezeles_tomb = $akt_tomb = array(
	array('type' => 'text',                     'megn' => 'Név:',                   'name' => 'nev',            'maxlength' => 250, 'class' => 'oriasi', 'alert' => 'Kérem töltse ki a nevet!'),
    array('type' => 'select_felh_telephely',    'megn' => 'Szálloda:',              'name' => 'telephely_id',   'style' => 'width: 100%;', 'alert' => 'Kérem válasszon szállodát!', 'onchange' => 'szallodaVane( this );'),
    array('type' => 'autocomplete',             'megn' => 'Helyiség:',              'name' => 'obj_id',         'class' => 'disableNav', 'style' => 'width: calc(100% - 3rem);', 'tabla' => 'obj', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'id_kiir' => 'recid', 'alert' => 'Kérem adja meg a Helyiséget!', 'onclick' => 'szallodaVane( this );', 'post' => '<a href="javascript: obj_gen(); void(0);" class="obj_gen"><svg height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M4 10.5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zm0-6c-.83 0-1.5.67-1.5 1.5S3.17 7.5 4 7.5 5.5 6.83 5.5 6 4.83 4.5 4 4.5zm0 12c-.83 0-1.5.68-1.5 1.5s.68 1.5 1.5 1.5 1.5-.68 1.5-1.5-.67-1.5-1.5-1.5zM7 19h14v-2H7v2zm0-6h14v-2H7v2zm0-8v2h14V5H7z"/><path d="M0 0h24v24H0V0z" fill="none"/></svg></a>'),
	array('type' => 'text',                     'megn' => 'Azonosító:',             'name' => 'azon',           'maxlength' => 250, 'class' => 'oriasi'),
    array('type' => 'textarea',                 'megn' => 'Megjegyzés:',            'name' => 'megj',           'disabled' => $disabled,    'class' => 'oriasiTargy'/*,  'limit' => 500/*Limit wysiwyg-el nem kombinálható!*/ /*, 'wysiwyg' => 'Alap'/*Min vagy Full*/, 'feltoltes' => 'Images'/*Files Flash*//*, 'autogrow' => false*/, 'width' => '100%', 'height' => '150'/*<=WYSIWYG esetére*/),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));
//{KOVETKEZO_TOMB}


/* Munkalap feladat */
if( isset($_POST['munkFeladatTorol']) ) {
    $recid = $_POST['recid'];

    if( $recid ) {
        $adatbazis_kapcsolat -> d('munkalap_feladat', "`recid` = '{$recid}'");

        echo json_encode(array(
            'statusz' => 'siker'
        ));
    }

    $weblap->leallit();
    exit();
}

/* Munkalap anyagok */
if( isset($_POST['munkAnyagTorol']) ) {
    $recid = $_POST['recid'];

    if( $recid ) {
        $adatbazis_kapcsolat -> d('munkalap_anyag', "`recid` = '{$recid}'");

        echo json_encode(array(
            'statusz' => 'siker'
        ));
    }

    $weblap->leallit();
    exit();
}

/* Munkalap anyagok */
if( isset($_POST['getMunkalapAnyagok']) ) {
    $recid = $_POST['recid'];
    $szuk_menny = $_POST['szuk_menny'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        $sql = $akt_tetel = $adatbazis_kapcsolat->s('recid', 'munkalap_tetel', "`munkalap_id` = '{$recid}'", 'recid desc', '1');

        if( $sql === false ) {
            $db_error = true;
        }

        if( $akt_tetel ) {
            $akt_tetel = $akt_tetel[0];

            $sql = $anyagok = $adatbazis_kapcsolat->s('recid cikk_id cikk_nev cikk_men cikk_mee szuk_men', 'munkalap_anyag', "`munkalap_id` = '{$recid}' AND `tetel_id` = '".$akt_tetel['recid']."'", '', '');

            if( $sql === false ) {
                $db_error = true;
            }

            if( $anyagok ) {
                $sorok = '';
                foreach($anyagok as $anyag) {
                    $sz_td = '';
                    if( $szuk_menny ) {
                        $sz_td = '<td>'.$anyag['szuk_men'].'<input type="hidden" name="addCikkSzukMenny[]" value="'.$anyag['szuk_men'].'"></td>';
                    }

                    $sorok .= nl2('
                        <tr>
                            <td>'.$anyag['cikk_id'].'<input type="hidden" name="addCikkID[]" value="'.$anyag['cikk_id'].'"><input type="hidden" name="addCikkUj[]" value="0"></td>
                            <td>'.$anyag['cikk_nev'].'</td>
                            <td>'.$anyag['cikk_men'].'<input type="hidden" name="addCikkFelhMenny[]" value="'.$anyag['cikk_men'].'"></td>
                            '.$sz_td.'
                            <td>'.$anyag['cikk_mee'].'</td>
                            <td></td>
                            <td><a onclick="munkAnyagTorol( \''.$anyag['recid'].'\', this );"><span class="icon"><i class="material-icons">delete</i></span></a></td>
                        </tr>
                    ');
                }

                $html = '';
                if($sorok) {
                    $sz_th = '';
                    if( $szuk_menny ) {
                        $sz_th = '<th>Szükséges mennyiség</th>';
                    }

                    $html = nl2('
                        <table class="csikos">
                            <tr>
                                <th>Cikk</th>
                                <th>Név</th>
                                <th>Felhasznált mennyiség</th>
                                '.$sz_th.'
                                <th>Mee</th>
                                <th>Anyag státusz</th>
                                <th class="torles"></th>
                            </tr>
                            '.$sorok.'
                        </table>
                    ');
                }

                if( !$db_error ) {
                    mysqli_commit($db_kapcs);

                    echo json_encode(array(
                        'statusz' => 'siker',
                        'html' => $html
                    ));
                } else {
                    mysqli_rollback($db_kapcs);

                    echo json_encode(array(
                        'hiba' => tranzakcioHiba()
                    ));
                }
            }
        } else {
            echo json_encode(array(
                'hiba' => tranzakcioHiba()
            ));
        }
    }

    $weblap->leallit();
    exit();
}

/* Munkalap anyagigénylés */
if( isset($_POST['munkAnyagIgenyles']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        /* Új */
        parse_str($_POST['addCikkUj']);

        /* Cikkek */
        parse_str($_POST['addCikkID']);

        /* Felhasznált mennyiség */
        parse_str($_POST['addCikkFelhMenny']);

        /* Szükséges mennyiség */
        parse_str($_POST['addCikkSzukMenny']);

        $szuk_menny = false;
        if( $addCikkSzukMenny ) {
            foreach($addCikkSzukMenny as $key => $value) {
                if( !empty($value) && $addCikkUj[$key] && $value > 0 ) {
                    $szuk_menny = true;
                }
            }
        }

        //Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if(isset($_POST['csak_kerdezunk']) && $szuk_menny) {
            echo json_encode(array(
                'hiba' => 'Ha folytatja, a munkalap Anyagra vár státuszba kerül. Folytatja?',
                'gomb' => '<input class="gomb piros" value="Igen" onclick="munkAnyagIgenyles( \'#munkAnyagAnyagigenyles\' );" type="button"><input class="gomb zold" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        if( count($addCikkID) && count($addCikkID) == count($addCikkFelhMenny) ) {
            $sql = $akt_tetel = $adatbazis_kapcsolat->s('recid', 'munkalap_tetel', "`munkalap_id` = '{$recid}'", 'recid desc', '1');

            if( $sql === false ) {
                $db_error = true;
            }

            if( $akt_tetel ) {
                $akt_tetel = $akt_tetel[0];

                foreach($addCikkID as $key => $value) {
                    $sql = $is_anyag = $adatbazis_kapcsolat->s('recid', 'munkalap_anyag', "`munkalap_id` = '{$recid}' AND `tetel_id` = '".$akt_tetel['recid']."' AND `cikk_id` = '{$value}'", '', '1');

                    if( $sql === false ) {
                        $db_error = true;
                    }

                    $sql = $cikk_sor = $adatbazis_kapcsolat->s('recid nev cikkmee', 'cikktorzs', "`recid` = '{$value}'", '', '1');

                    if( $sql === false ) {
                        $db_error = true;
                    }

                    if( $cikk_sor ) {
                        $cikk_sor = $cikk_sor[0];
                    }

                    if( $is_anyag && $addCikkUj[$key] ) {
                        echo json_encode(array(
                            'hiba' => 'A(z) "'.$cikk_sor['nev'].'" anyag már hozzáadásra került!'
                        ));

                        $weblap->leallit();
                        exit();
                    }

                    if( $addCikkUj[$key] ) {
                        if( $cikk_sor ) {
                            $stat_id = 1;
                            if( !empty($addCikkSzukMenny[$key]) && $addCikkSzukMenny[$key] > 0 ) {
                                $stat_id = 2;
                            }

                            $sql = $adatbazis_kapcsolat -> i('munkalap_anyag',
                                array('munkalap_id', 'tetel_id',          'cikk_id',            'cikk_nev',         'cikk_mee',             'cikk_men',              'szuk_men',                'stat_id', 'insert_dt',          'insert_usr',                              'update_dt',          'update_usr'                          ),
                                array($recid,        $akt_tetel['recid'], $cikk_sor['recid'],   $cikk_sor['nev'],   $cikk_sor['cikkmee'],   $addCikkFelhMenny[$key], $addCikkSzukMenny[$key],   $stat_id,   date('Y-m-d H:i:s'),  $_SESSION[get_db_tabla().'teljes_nev'],    date('Y-m-d H:i:s'), $_SESSION[get_db_tabla().'teljes_nev'])
                            );

                            if( $sql === false ) {
                                $db_error = true;
                            }
                        }
                    }
                }

                /* Anyagra vár szátuszba kerül */
                if( $szuk_menny ) {
                    if( !addStatusz( 'munkalap_statusz', $recid, 9 ) ) {
                        $db_error = true;
                    }

                    /* Munkalapon töröljük a személyzetet és a mobil loginnevet */
                    $sql = $adatbazis_kapcsolat -> u('munkalap', array('szemelyzet_id', 'mobil_loginnev'), array(0, ''), "`recid` = '{$recid}'");

                    if( $sql === false ) {
                        $db_error = true;
                    }

                    /* Utolsó tételnél átállítjuk az átadta mező értékét */
                    $sql = $adatbazis_kapcsolat -> u('munkalap_tetel', array('atadta_dt', 'visszaadva'), array(date('Y-m-d H:i:s'), 1), "`recid` = '".$akt_tetel['recid']."'");

                    if( $sql === false ) {
                        $db_error = true;
                    }
                }
            }

            if( !$db_error ) {
                mysqli_commit($db_kapcs);

                $refresh = false;
                if( $szuk_menny ) {
                    $refresh = true;
                }

                echo json_encode(array(
                    'statusz' => 'siker',
                    'refresh' => $refresh
                ));
            } else {
                mysqli_rollback($db_kapcs);

                echo json_encode(array(
                    'hiba' => tranzakcioHiba()
                ));
            }
        } else {
            echo json_encode(array(
                'hiba' => tranzakcioHiba()
            ));
        }
    }

    $weblap->leallit();
    exit();
}

/* Munkalap anyag vételezés */
if( isset($_POST['munkAnyagVetelezes']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        /* Új */
        parse_str($_POST['addCikkUj']);

        /* Cikkek */
        parse_str($_POST['addCikkID']);

        /* Felhasznált mennyiség */
        parse_str($_POST['addCikkFelhMenny']);

        if( count($addCikkID) && count($addCikkID) == count($addCikkFelhMenny) ) {
            $sql = $akt_tetel = $adatbazis_kapcsolat->s('recid', 'munkalap_tetel', "`munkalap_id` = '{$recid}'", 'recid desc', '1');

            if( $sql === false ) {
                $db_error = true;
            }

            if( $akt_tetel ) {
                $akt_tetel = $akt_tetel[0];

                foreach($addCikkID as $key => $value) {
                    $sql = $is_anyag = $adatbazis_kapcsolat->s('recid', 'munkalap_anyag', "`munkalap_id` = '{$recid}' AND `tetel_id` = '".$akt_tetel['recid']."' AND `cikk_id` = '{$value}'", '', '1');

                    if( $sql === false ) {
                        $db_error = true;
                    }

                    $sql = $cikk_sor = $adatbazis_kapcsolat->s('recid nev cikkmee', 'cikktorzs', "`recid` = '{$value}'", '', '1');

                    if( $sql === false ) {
                        $db_error = true;
                    }

                    if( $cikk_sor ) {
                        $cikk_sor = $cikk_sor[0];
                    }

                    if( $is_anyag && $addCikkUj[$key] ) {
                        echo json_encode(array(
                            'hiba' => 'A(z) "'.$cikk_sor['nev'].'" anyag már hozzáadásra került!'
                        ));

                        $weblap->leallit();
                        exit();
                    }

                    if( $addCikkUj[$key] ) {
                        if( $cikk_sor ) {
                            $sql = $adatbazis_kapcsolat -> i('munkalap_anyag',
                                array('munkalap_id', 'tetel_id',          'cikk_id',            'cikk_nev',         'cikk_mee',             'cikk_men',              'insert_dt',          'insert_usr',                              'update_dt',          'update_usr'                          ),
                                array($recid,        $akt_tetel['recid'], $cikk_sor['recid'],   $cikk_sor['nev'],   $cikk_sor['cikkmee'],   $addCikkFelhMenny[$key],  date('Y-m-d H:i:s'),  $_SESSION[get_db_tabla().'teljes_nev'],    date('Y-m-d H:i:s'), $_SESSION[get_db_tabla().'teljes_nev'])
                            );

                            if( $sql === false ) {
                                $db_error = true;
                            }
                        }
                    }
                }
            }

            if( !$db_error ) {
                mysqli_commit($db_kapcs);

                echo json_encode(array(
                    'statusz' => 'siker'
                ));
            } else {
                mysqli_rollback($db_kapcs);

                echo json_encode(array(
                    'hiba' => tranzakcioHiba()
                ));
            }
        } else {
            echo json_encode(array(
                'hiba' => tranzakcioHiba()
            ));
        }
    }

    $weblap->leallit();
    exit();
}

/* Átadás diszpécsernek */
if( isset($_POST['munkalapKeszre']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        $sql = $akt_tetel = $adatbazis_kapcsolat->s('recid', 'munkalap_tetel', "`munkalap_id` = '{$recid}'", 'recid desc', '1');

        if( $sql === false ) {
            $db_error = true;
        }

        if( $akt_tetel ) {
            $akt_tetel = $akt_tetel[0];

            /* Ellenőrizzük, hogy van e elvégzett munka hozzáadva */
            $is_feladat = $adatbazis_kapcsolat->s('recid', 'munkalap_feladat', "`tetel_id` = '".$akt_tetel['recid']."'", '', '1');

            if( !$is_feladat ) {
                echo json_encode(array(
                    'hiba' => 'Adjon meg elvégzett munkát!'
                ));

                $weblap->leallit();
                exit();
            }
        }

        //Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if (isset($_POST['csak_kerdezunk'])) {

            echo json_encode(array(
                'hiba' => 'Biztosan készre jelenti a munkát?',
                'gomb' => '<input class="gomb piros" value="Igen" onclick="munkalapKeszre();" type="button"><input class="gomb zold" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        /* Készre állítjuk a munkát */
        if( !addStatusz( 'munkalap_statusz', $recid, 10 ) ) {
            $db_error = true;
        }

        $sql = $akt_tetel = $adatbazis_kapcsolat->s('recid', 'munkalap_tetel', "`munkalap_id` = '{$recid}'", 'recid desc', '1');

        if( $sql === false ) {
            $db_error = true;
        }

        if( $akt_tetel ) {
            $akt_tetel = $akt_tetel[0];

            /* Munkalapon töröljük a személyzetet és a mobil loginnevet */
            $sql = $adatbazis_kapcsolat -> u('munkalap', array('szemelyzet_id', 'mobil_loginnev'), array(0, ''), "`recid` = '{$recid}'");

            if( $sql === false ) {
                $db_error = true;
            }

            /* Utolsó tételnél átállítjuk az átadta mező értékét */
            $sql = $adatbazis_kapcsolat -> u('munkalap_tetel', array('atadta_dt', 'visszaadva'), array(date('Y-m-d H:i:s'), 1), "`recid` = '".$akt_tetel['recid']."'");

            if( $sql === false ) {
                $db_error = true;
            }
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            echo json_encode(array(
                'statusz' => 'siker'
            ));
        } else {
            mysqli_rollback($db_kapcs);

            echo json_encode(array(
                'hiba' => tranzakcioHiba()
            ));
        }
    }

    $weblap->leallit();
    exit();
}

/* Átadás diszpécsernek */
if( isset($_POST['atadasDiszpecsernek']) ) {
    $recid = $_POST['recid'];
    $erteklista_id = $_POST['erteklista_id'];
    $memo = $_POST['memo'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $erteklista_id ) {
        /* Beszúrjuk a munkalap tételt */
        $sql = $adatbazis_kapcsolat -> i('munkalap_tetel',
            array('munkalap_id', 'erteklista_id', 'visszaadva', 'atadta_dt',            'insert_dt',          'insert_usr',                              'update_dt',          'update_usr'                           ),
            array($recid,        $erteklista_id,   1,            date('Y-m-d H:i:s'),    date('Y-m-d H:i:s'), $_SESSION[get_db_tabla().'teljes_nev'],     date('Y-m-d H:i:s'), $_SESSION[get_db_tabla().'teljes_nev'] )
        );

        if( $sql === false ) {
            $db_error = true;
        }

        /* Frissítjuk a munkalap visszaadva sorát */
        $sql = $adatbazis_kapcsolat -> u('munkalap', array('visszaadva', 'szemelyzet_id', 'mobil_loginnev'), array(1, 0, ''), "`recid` = '{$recid}'");

        if( $sql === false ) {
            $db_error = true;
        }

        /* Nyitott státuszra állítjuk a munkalapot */
        if( !addStatusz( 'munkalap_statusz', $recid, 1 ) ) {
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
                'hiba' => tranzakcioHiba()
            ));
        }
    } else {
        echo json_encode(array(
            'hiba' => 'Adja meg az átadás okát!'
        ));
    }

    $weblap->leallit();
    exit();
}

/* Munkalaphoz adás */
if( isset($_POST['feladatHozzaadas']) ) {
    $recid = $_POST['recid'];
    $erteklista_id = $_POST['erteklista_id'];
    $memo = $_POST['memo'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $erteklista_id ) {
        $sql = $akt_tetel = $adatbazis_kapcsolat->s('recid', 'munkalap_tetel', "`munkalap_id` = '{$recid}'", 'recid desc', '1');

        if( $sql === false ) {
            $db_error = true;
        }

        if( $akt_tetel ) {
            $akt_tetel = $akt_tetel[0];

            $is_feladat = $adatbazis_kapcsolat->s('recid', 'munkalap_feladat', "`tetel_id` = '".$akt_tetel['recid']."' AND `erteklista_id` = '{$erteklista_id}'", '', '1');

            if( $sql === false ) {
                $db_error = true;
            }

            if( $is_feladat ) {
                echo json_encode(array(
                    'hiba' => 'A(z) "'.erteklista(5, $erteklista_id).'" feladat már hozzáadásra került!'
                ));

                $weblap->leallit();
                exit();
            }

            $sql = $adatbazis_kapcsolat -> i('munkalap_feladat',
                array('munkalap_id', 'tetel_id',          'erteklista_id',    'memo',       'insert_dt',          'insert_usr',                              'update_dt',          'update_usr'                           ),
                array($recid,        $akt_tetel['recid'], $erteklista_id,     $memo, date('Y-m-d H:i:s'),  $_SESSION[get_db_tabla().'teljes_nev'],    date('Y-m-d H:i:s'), $_SESSION[get_db_tabla().'teljes_nev'] )
            );

            if( $sql === false ) {
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
                    'hiba' => tranzakcioHiba()
                ));
            }
        } else {
            echo json_encode(array(
                'hiba' => 'Hiba: Tétel nem található!'
            ));
        }
    } else {
        echo json_encode(array(
            'hiba' => 'Adja meg az elvégzett munkát!'
        ));
    }

    $weblap->leallit();
    exit();
}

/* Munkalaphoz adás */
if( isset($_GET['folyamatban_munkak']) ) {
    $telephely_id = $_GET['telephely_id'];
    $obj_id       = $_GET['obj_id'];

    $bejelentesek = $adatbazis_kapcsolat->s('ikt_szam surgos telephely_id obj_id hiba_id hiba_text', 'bejelentes', "`telephely_id` = '{$telephely_id}' AND `obj_id` = '{$obj_id}'");

    if( $bejelentesek ) {
        $html = nl2('
            <div class="dt alert_dt">
                <div class="dtr adt_fejlec">
                    <div class="dtc">Iktató szám</div>
                    <div class="dtc">Szálloda</div>
                    <div class="dtc">Hely leírása</div>
                    <div class="dtc">Bejelentett hiba</div>
                    <div class="dtc">Hiba leírása</div>
                    <div class="dtc">Sürgős</div>
                </div>
        ');

        foreach($bejelentesek as $bejelentes) {
            /* Telephely */
            $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$bejelentes['telephely_id'], '', '1');
            $telephely = $telephely[0]['nev'];

            /* Helység */
            $helyseg = $adatbazis_kapcsolat->s('nev', 'obj', 'recid='.$bejelentes['obj_id'], '', '1');
            $helyseg = $helyseg[0]['nev'];

            /* Hiba */
            $hiba = $adatbazis_kapcsolat->s('info', 'erteklista', "`csoport`=4 AND `recid`='{$bejelentes['hiba_id']}'");
            $hiba = $hiba[0];

            $surgos = '';
            if( $bejelentes['surgos'] ) {
                $surgos = nl2('
                    <span class="icon piros">
                        <i class="material-icons">error</i>
                    </span>
                ');
            }

            $html .= nl2('
                <div class="dtr adt_sor">
                    <div class="dtc">'.$bejelentes['ikt_szam'].'</div>
                    <div class="dtc">'.$telephely.'</div>
                    <div class="dtc">'.$helyseg.'</div>
                    <div class="dtc">'.$hiba['info'].'</div>
                    <div class="dtc">'.$bejelentes['hiba_text'].'</div>
                    <div class="dtc">'.$surgos.'</div>
                </div>
            ');
        }

        $html .= '</div>';

        echo json_encode(array(
            'hiba' => 'Folyamatban lévő munkák listája'.$html.'Folytatja a hibabejelentést?',
            'gomb' => '<p class="c"><input class="gomb piros" type="button" value="Igen" onclick="bejelentes_mehet = true; $(\'#szerk_form\').submit();"><input class="gomb zold" type="button" value="Nem" onclick="alert2()"></p>'
        ));

        $weblap->leallit();
        exit();
    }
    echo json_encode(array('siker' => 1));

    $weblap->leallit();
    exit();
}

/* Munkalaphoz adás */
if( isset($_POST['munkalaphozAdas']) ) {
    $idk = json_decode($_POST['idk'], true);
    $munkalapID = $_POST['munkalapID'];

    if( !_jog(2) ) {
        echo json_encode(array(
            'hiba' => 'Nincs megfelelő jogosultság a művelet végrehajtásához!'
        ));

        $weblap->leallit();
        exit();
    }

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( is_array($idk) && $munkalapID ) {
        /* Ellenőrizzük a csoportokat */
        $sql = $csoportok = $adatbazis_kapcsolat->s('csoport_id', 'bejelentes', "`recid` IN (".implode(',', $idk).")", '', '');

        if( $sql === false ) {
            $db_error = true;
        }

        $csoportok = adatbazis2tomb($csoportok, 'csoport_id');
        $csoportok_unique = array_unique($csoportok);

        $kijelolt_db = array_count_values($csoportok);

        foreach( $csoportok_unique as $csu ) {
            if( $csu > 0 ) {
                $sql = $csoport_db = $adatbazis_kapcsolat->s('csoport_id', 'bejelentes', "`csoport_id` = '{$csu}'", '', '');

                if( $sql === false ) {
                    $db_error = true;
                }

                if( count($csoport_db) != $kijelolt_db[$csu] ) {
                    echo json_encode(array(
                        'hiba' => 'Egy csoportot nem lehet megbontani, minden elemének kijelölése szükséges!'
                    ));

                    $weblap->leallit();
                    exit();
                }
            }
        }

        /* Ellenőrizzük a szállodákat is */
        $sql = $szallodak = $adatbazis_kapcsolat->s('telephely_id', 'bejelentes', "`recid` IN (".implode(',', $idk).")", '', '');

        if( $sql === false ) {
            $db_error = true;
        }

        $szallodak = adatbazis2tomb($szallodak, 'telephely_id');
        $szallodak_unique = array_unique($szallodak);

        if( count($szallodak_unique) > 1 ) {
            echo json_encode(array(
                'hiba' => 'Csak azonos szállodával rendelkező bejelentéseket lehet munkalappá alakítani!'
            ));

            $weblap->leallit();
            exit();
        }

        /* Ellenőrizzük a státuszokat is */
        foreach($idk as $id) {
            $sql = $statusz = $adatbazis_kapcsolat->s('recid stat_id insert_dt', 'bejelentes_statusz', 'id='.$id, 'recid desc', '1');

            if( $sql === false ) {
                $db_error = true;
            }

            if( $statusz ) {
                $statusz = $statusz[0];

                if( $statusz['stat_id'] != 1 && $statusz['stat_id'] != 2 ) {
                    $sql = $statusz_text = $adatbazis_kapcsolat->s('nev', 'bejelentes_statusz_text', "`recid` = '{$statusz['stat_id']}'", '', '1');
                    $statusz_text = $statusz_text[0];

                    if( $sql === false ) {
                        $db_error = true;
                    }

                    $sql = $bejelentes = $adatbazis_kapcsolat->s('ikt_szam', 'bejelentes', "`recid` = '{$id}'", '', '');

                    if( $sql === false ) {
                        $db_error = true;
                    }

                    $hiba_kieg = '';
                    if( $bejelentes ) {
                        $bejelentes = $bejelentes[0];

                        $hiba_kieg = '<br>A "'.$bejelentes['ikt_szam'].'" bejelentés státusza: '.$statusz_text['nev'];
                    }

                    echo json_encode(array(
                        'hiba' => 'Munkalaphoz hozzáadni csak nyitott státuszú bejelentést lehet!'.$hiba_kieg
                    ));

                    $weblap->leallit();
                    exit();
                }
            } else {
                echo json_encode(array(
                    'hiba' => 'Státusz nem található!'
                ));

                $weblap->leallit();
                exit();
            }
        }

        //Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if (isset($_POST['csak_kerdezunk'])) {
            echo json_encode(array(
                'mehet' => ''
            ));
            $weblap->leallit();
            exit();
        }

        /* Státuszt folyamatban-ra állítjuk */
        foreach($idk as $id) {
            $statusz = addStatusz( 'bejelentes_statusz', $id, 2 );

            if( $statusz === false ) {
                $db_error = true;
            }
        }

        if( !$db_error ) {
            $munkalap_statusz = $adatbazis_kapcsolat->s('stat_id', 'munkalap_statusz', "`id` = '{$munkalapID}'", 'recid desc', '1');

            if( $munkalap_statusz ) {
                $munkalap_statusz = $munkalap_statusz[0];

                if( in_array($munkalap_statusz['stat_id'], array(1, 7, 2, 9)) ) {
                    /* Hozzákapcsoljuk a bejelentéseket */
                    foreach($idk as $id) {
                        $sql = $adatbazis_kapcsolat -> i('munkalap_kapcs',
                            array('munkalap_id', 'bejelentes_id',  'update_dt',          'update_usr',                          'insert_dt',          'insert_usr',                            ),
                            array($munkalapID,    $id,              date('Y-m-d H:i:s'), $_SESSION[get_db_tabla().'teljes_nev'], date('Y-m-d H:i:s'),  $_SESSION[get_db_tabla().'teljes_nev']  )
                        );

                        if( $sql === false ) {
                            $db_error = true;
                        }
                    }
                } else {
                    echo json_encode(array(
                        'hiba' => 'Csak nyitott, várakozó, folyamatban vagy anyagra vár státuszú munkalaphoz lehet bejelentéseket adni!'
                    ));

                    $weblap->leallit();
                    exit();
                }
            } else {
                echo json_encode(array(
                    'hiba' => 'Munkalap státusz nem található!'
                ));

                $weblap->leallit();
                exit();
            }
        }
        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            echo json_encode(array(
                'statusz' => 'siker',
                'recid' => $munkalapID
            ));
        } else {
            mysqli_rollback($db_kapcs);

            echo json_encode(array(
                'hiba' => tranzakcioHiba()
            ));
        }
    }

    $weblap->leallit();
    exit();
}

/* Csoport select */
if( isset($_GET['csoportSelect']) ) {
    $page = $_GET['page'];

    /* Lekérdezzük a csoportokat */
    $html .= '<select id="csoportID" name="csoportID"><option value="">- Válassz -</option>';

    $csoportok = $adatbazis_kapcsolat->s('recid nev', $page.'_csoport', '', 'nev', '');

    if( $csoportok ) {
        foreach($csoportok as $csoport) {
            $html .= '<option value="'.$csoport['recid'].'">'.$csoport['nev'].'</option>';
        }
    }

    $html .= '</select>';

    echo json_encode(array(
        'statusz' => 'siker',
        'html'    => $html
    ));

    $weblap->leallit();
    exit();
}

/* Munkalap select */
if( isset($_GET['munkalapSelect']) ) {
    $munkalapok = $adatbazis_kapcsolat->s('recid', 'munkalap', '', 'insert_dt desc', '');

    if( $munkalapok ) {
        $html = '<select id="munkalapID" name="munkalapID"><option value="">- Válassz -</option>';

        foreach($munkalapok as $munkalap) {
            $munkalap_statusz = $adatbazis_kapcsolat->s('stat_id', 'munkalap_statusz', "`id` = '{$munkalap['recid']}'", 'recid desc', '1');

            if( $munkalap_statusz ) {
                $munkalap_statusz = $munkalap_statusz[0];

                if( in_array($munkalap_statusz['stat_id'], array(1, 7, 2, 9)) ) {
                    $html .= '<option value="'.$munkalap['recid'].'">'.$munkalap['recid'].'</option>';
                }
            }

        }

        $html .= '</select>';

        echo json_encode(array(
            'statusz' => 'siker',
            'html'    => $html
        ));
    } else {
        echo json_encode(array(
            'hiba' => 'Még nincs egy létrehozott munkalap sem!'
        ));
    }

    $weblap->leallit();
    exit();
}

/* Csoport átnevezése */
if( isset($_GET['csoportAtnevezes']) ) {
    $csoportID = $_GET['csoportID'];
    $val = $_GET['val'];
    $page = $_GET['page'];

    if( $csoportID && $val && $page ) {
        $adatbazis_kapcsolat -> u($page.'_csoport', array('nev'), array($val), 'recid='.$csoportID);

        echo json_encode(array(
            'statusz' => 'siker',
            'value'   => $val
        ));
    }

    $weblap->leallit();
    exit();
}

/* Várólistához adás és kivétel config */
$idk = json_decode($_POST['idk'], true);
$page = $_POST['page'];

//Mik az egyes státusz ID-k?
$nyitott_statid  = array(1);
$varakozo_statid = 4;
if($page=='anyagigenyles'){
    $nyitott_statid  = array(5,20);
    $varakozo_statid = 52;
}
if($page=='munkalap'){
    $varakozo_statid = 7;
}
if( isset($_POST['varolistahozAdas']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( is_array($idk) ) {
        /* Ellenőrizzük a csoportokat */
        $sql = $csoportok = $adatbazis_kapcsolat->s('csoport_id', $page, "`recid` IN (".implode(',', $idk).")", '', '');
        if( $sql === false ) {
            $db_error = true;
        }

        $csoportok = adatbazis2tomb($csoportok, 'csoport_id');
        $csoportok_unique = array_unique($csoportok);

        $kijelolt_db = array_count_values($csoportok);

        foreach( $csoportok_unique as $csu ) {
            if( $csu > 0 ) {
                $sql = $csoport_db = $adatbazis_kapcsolat->s('csoport_id', $page, "`csoport_id` = '{$csu}'", '', '');

                if( $sql === false ) {
                    $db_error = true;
                }

                if( count($csoport_db) != $kijelolt_db[$csu] ) {
                    echo json_encode(array(
                        'hiba' => 'Csoportosított munkalap önmagában nem várakoztatható. Előbb vegye ki a csoportból!'
                    ));

                    $weblap->leallit();
                    exit();
                }
            }
        }

        /* Ellenőrizzük a státuszokat is */
        foreach($idk as $id) {
            $sql = $statusz = $adatbazis_kapcsolat->s('recid stat_id insert_dt', $page.'_statusz', 'id='.$id, 'recid desc', '1');

            if( $sql === false ) {
                $db_error = true;
            }

            if( $statusz ) {
                $statusz = $statusz[0];

                if(  !in_array($statusz['stat_id'], $nyitott_statid) ) {
                    echo json_encode(array(
                        'hiba' => 'Csak nyitott státuszú munkalapokat lehet várólistához adni!'
                    ));

                    $weblap->leallit();
                    exit();
                }
            } else {
                echo json_encode(array(
                    'hiba' => 'Státusz nem található!'
                ));

                $weblap->leallit();
                exit();
            }
        }

        //Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if (isset($_POST['csak_kerdezunk'])) {

            echo json_encode(array(
                'hiba' => 'Biztos várólistához adja a kijelölt munkalapot?',
                'gomb' => '<input class="gomb piros" value="Igen" onclick="varolistahozAdas();" type="button"><input class="gomb zold" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        foreach($idk as $id) {
            $statusz = addStatusz( $page.'_statusz', $id, $varakozo_statid );

            if( $statusz === false ) {
                $db_error = true;
            }
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            echo json_encode(array(
                'statusz' => 'siker'
            ));
        } else {
            mysqli_rollback($db_kapcs);

            echo json_encode(array(
                'hiba' => tranzakcioHiba()
            ));
        }
    }

    $weblap->leallit();
    exit();
}

/* Várólistából kivétel */
if( isset($_POST['varolistabolKivetel']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( is_array($idk) ) {
        /* Ellenőrizzük a csoportokat */
        $sql = $csoportok = $adatbazis_kapcsolat->s('csoport_id', $page, "`recid` IN (".implode(',', $idk).")", '', '');

        if( $sql === false ) {
            $db_error = true;
        }

        $csoportok = adatbazis2tomb($csoportok, 'csoport_id');
        $csoportok_unique = array_unique($csoportok);

        $kijelolt_db = array_count_values($csoportok);

        foreach( $csoportok_unique as $csu ) {
            if( $csu > 0 ) {
                $sql = $csoport_db = $adatbazis_kapcsolat->s('csoport_id', $page, "`csoport_id` = '{$csu}'", '', '');

                if( $sql === false ) {
                    $db_error = true;
                }

                if( count($csoport_db) != $kijelolt_db[$csu] ) {
                    echo json_encode(array(
                        'hiba' => 'Csoportosított munkalap önmagában nem vehető le várólistáról. Előbb vegye ki a csoportból!'
                    ));

                    $weblap->leallit();
                    exit();
                }
            }
        }

        /* Ellenőrizzük a státuszokat is */
        foreach($idk as $id) {
            $sql = $statusz = $adatbazis_kapcsolat->s('recid stat_id insert_dt', $page.'_statusz', 'id='.$id, 'recid desc', '1');

            if( $sql === false ) {
                $db_error = true;
            }

            if( $statusz ) {
                $statusz = $statusz[0];

                if( $statusz['stat_id'] != $varakozo_statid ) {
                    echo json_encode(array(
                        'hiba' => 'Csak várakozó státuszú munkalapról lehet levenni a várakoztatást!'
                    ));

                    $weblap->leallit();
                    exit();
                }
            } else {
                echo json_encode(array(
                    'hiba' => 'Státusz nem található!'
                ));

                $weblap->leallit();
                exit();
            }
        }

        //Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if (isset($_POST['csak_kerdezunk'])) {

            echo json_encode(array(
                'hiba' => 'Biztos kiveszi a várólistából a kijelölt munkalapot?',
                'gomb' => '<input class="gomb piros" value="Igen" onclick="varolistabolKivetel();" type="button"><input class="gomb zold" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        /* Státuszt nyitott-ra állítjuk */
        foreach($idk as $id) {
            $statusz = addStatusz( $page.'_statusz', $id, $nyitott_statid[0] );

            if( $statusz === false ) {
                $db_error = true;
            }
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            echo json_encode(array(
                'statusz' => 'siker'
            ));
        } else {
            mysqli_rollback($db_kapcs);

            echo json_encode(array(
                'hiba' => tranzakcioHiba()
            ));
        }
    }

    $weblap->leallit();
    exit();
}

/* Munkalap átadása diszpécser központnak */
if( isset($_POST['munkalapDiszpKozp']) ) {
    $recid = $_POST['recid'];

    if( $recid ) {
        $adatbazis_kapcsolat -> u('munkalap', array('szemelyzet_id'), array(4), "`recid` = '{$recid}'");

        echo json_encode(array(
            'statusz' => 'siker'
        ));
    }
}

/* Munkalap átadása területi vezetőnek */
if( isset($_POST['munkalapTeruletiVezeto']) ) {
    $recid = $_POST['recid'];

    if( $recid ) {
        $adatbazis_kapcsolat -> u('munkalap', array('szemelyzet_id'), array(7), "`recid` = '{$recid}'");

        echo json_encode(array(
            'statusz' => 'siker'
        ));
    }
}

/* Munkalap átadása műszaki vezetőnek */
if( isset($_POST['munkalapMuszakiVezeto']) ) {
    $recid = $_POST['recid'];

    if( $recid ) {
        $adatbazis_kapcsolat -> u('munkalap', array('szemelyzet_id'), array(5), "`recid` = '{$recid}'");

        echo json_encode(array(
            'statusz' => 'siker'
        ));
    }
}

/* Munkalap átadása adminisztrátornak */
if( isset($_POST['munkalapAdminisztrator']) ) {
    $recid = $_POST['recid'];

    if( $recid ) {
        $adatbazis_kapcsolat -> u('munkalap', array('szemelyzet_id'), array(6), "`recid` = '{$recid}'");

        echo json_encode(array(
            'statusz' => 'siker'
        ));
    }
}

/* Munkalap átadása külső csoportnak */
if( isset($_POST['munkalapKulso']) ) {
    $recid = $_POST['recid'];

    if( $recid ) {
        $adatbazis_kapcsolat -> u('munkalap', array('szemelyzet_id'), array(3), "`recid` = '{$recid}'");

        echo json_encode(array(
            'statusz' => 'siker'
        ));
    }
}

/* Munkalap átadása telepített csoportnak */
if( isset($_POST['munkalapTelepitett']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        $sql = $akt_statusz = $adatbazis_kapcsolat->s('statusz_id', 'munkalap', "`recid` = '{$recid}'", '', '1');

        if( $sql === false ) {
            $db_error = true;
        }

        if( $akt_statusz ) {
            $akt_statusz = $akt_statusz[0];

            if( $akt_statusz['statusz_id'] != 1 ) {
                echo json_encode(array(
                    'hiba' => 'Munkalap kiadása csak nyitott státusz esetén lehetséges!'
                ));

                $weblap->leallit();
                exit();
            }
        } else {
            echo json_encode(array(
                'hiba' => tranzakcioHiba()
            ));

            $weblap->leallit();
            exit();
        }

        //Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if (isset($_POST['csak_kerdezunk'])) {

            echo json_encode(array(
                'hiba' => 'Biztosan telepített karbantartónak adja át a munkát?',
                'gomb' => '<input class="gomb piros" value="Igen" onclick="munkalapTelepitett();" type="button"><input class="gomb zold" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        $sql = $adatbazis_kapcsolat -> u('munkalap', array('szemelyzet_id', 'visszaadva'), array(1, 0), "`recid` = '{$recid}'");

        if( $sql === false ) {
            $db_error = true;
        }

        if( !addStatusz( 'munkalap_statusz', $recid, 2 ) ) {
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
                'hiba' => tranzakcioHiba()
            ));
        }
    }
}

/* Munkalap Felvétele */
if( isset($_POST['munkalapFelvesz']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        if( !addStatusz( 'munkalap_statusz', $recid, 8 ) ) {
            $db_error = true;
        }

        $sql = $adatbazis_kapcsolat -> i('munkalap_tetel',
            array('munkalap_id', 'insert_dt',          'insert_usr',                              'update_dt',          'update_usr'                           ),
            array($recid,         date('Y-m-d H:i:s'), $_SESSION[get_db_tabla().'teljes_nev'],     date('Y-m-d H:i:s'), $_SESSION[get_db_tabla().'teljes_nev'] )
        );

        if( $sql === false ) {
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
                'hiba' => tranzakcioHiba()
            ));
        }
    }

    $weblap->leallit();
    exit();
}

/* Munkalap készítése */
if( isset($_POST['munkalapKeszit']) ) {
    $idk = json_decode($_POST['idk'], true);

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( is_array($idk) ) {
        /* Ellenőrizzük a csoportokat */
        $sql = $csoportok = $adatbazis_kapcsolat->s('csoport_id', 'bejelentes', "`recid` IN (".implode(',', $idk).")", '', '');

        if( $sql === false ) {
            $db_error = true;
        }

        $csoportok = adatbazis2tomb($csoportok, 'csoport_id');
        $csoportok_unique = array_unique($csoportok);

        $kijelolt_db = array_count_values($csoportok);

        foreach( $csoportok_unique as $csu ) {
            if( $csu > 0 ) {
                $sql = $csoport_db = $adatbazis_kapcsolat->s('csoport_id', 'bejelentes', "`csoport_id` = '{$csu}'", '', '');

                if( $sql === false ) {
                    $db_error = true;
                }

                if( count($csoport_db) != $kijelolt_db[$csu] ) {
                    echo json_encode(array(
                        'hiba' => 'Egy csoportot nem lehet megbontani, minden elemének kijelölése szükséges!'
                    ));

                    $weblap->leallit();
                    exit();
                }
            }
        }

        /* Ellenőrizzük a szállodákat is */
        $sql = $szallodak = $adatbazis_kapcsolat->s('telephely_id', 'bejelentes', "`recid` IN (".implode(',', $idk).")", '', '');

        if( $sql === false ) {
            $db_error = true;
        }

        $szallodak = adatbazis2tomb($szallodak, 'telephely_id');
        $szallodak_unique = array_unique($szallodak);

        if( count($szallodak_unique) > 1 ) {
            echo json_encode(array(
                'hiba' => 'Csak azonos szállodával rendelkező bejegyzéseket lehet munkalappá alakítani!'
            ));

            $weblap->leallit();
            exit();
        }

        /* Ellenőrizzük a státuszokat is */
        foreach($idk as $id) {
            $sql = $statusz = $adatbazis_kapcsolat->s('recid stat_id insert_dt', 'bejelentes_statusz', 'id='.$id, 'recid desc', '1');

            if( $sql === false ) {
                $db_error = true;
            }

            if( $statusz ) {
                $statusz = $statusz[0];

                if( $statusz['stat_id'] != 1 && $statusz['stat_id'] != 2 ) {
                    echo json_encode(array(
                        'hiba' => 'Munkalap csak nyitott státuszú bejelentésből készíthető!'
                    ));

                    $weblap->leallit();
                    exit();
                }
            } else {
                echo json_encode(array(
                    'hiba' => 'Státusz nem található!'
                ));

                $weblap->leallit();
                exit();
            }
        }

        //Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if (isset($_POST['csak_kerdezunk'])) {

            echo json_encode(array(
                'hiba' => 'Biztos munkalapot készít a kijelölt bejelentés(ek)ből?',
                'gomb' => '<input class="gomb piros" value="Igen" onclick="munkalapKeszit();" type="button"><input class="gomb zold" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        /* Státuszt folyamatban-ra állítjuk */
        foreach($idk as $id) {
            $statusz = addStatusz( 'bejelentes_statusz', $id, 2 );

            if( $statusz === false ) {
                $db_error = true;
            }
        }

        if( !$db_error ) {
            /* Megnézzük, hogy az adott évszámmal létezik e bejegyzés */
            $ikt_seq = $adatbazis_kapcsolat->s('ev sorszam', 'iktszam_seq', "`tabla` = 'munkalap' AND `ev` = '".date('y')."'", '', '');

            if( $ikt_seq === false ) {
                $db_error = true;
            }

            if( $ikt_seq ) {
                $ikt_seq = $ikt_seq[0];

                /* Sorszám */
                $sorszam = $ikt_seq['sorszam'];

                /* Sorszám növelés */
                $sorszam++;

                /* Letároljuk az új sorszámot */
                $sql = $adatbazis_kapcsolat -> u('iktszam_seq', array('sorszam'), array($sorszam), "`tabla` = 'munkalap' AND `ev` = '".date('y')."'");

                if( $sql === false ) {
                    $db_error = true;
                }
            } else {
                /* Sorszám */
                $sorszam = 1;

                $sql = $adatbazis_kapcsolat -> i('iktszam_seq', array('sorszam', 'ev', 'tabla'), array($sorszam, date('y'), 'munkalap'));
                if( $sql === false ) {
                    $db_error = true;
                }
            }

            /* Iktatószám formázása */
            $ikt_szam = 'M'.date('y').'/'.sprintf('%07d', $sorszam);

            /* Ellenőrizzük a szállodákat is */
            $sql = $telephely_id = $adatbazis_kapcsolat->s('telephely_id', 'bejelentes', "`recid` IN (".implode(',', $idk).")", '', '1');

            if( $sql === false ) {
                $db_error = true;
            }

            if( $telephely_id ) {
                $telephely_id = $telephely_id[0]['telephely_id'];
            }

            /* Munkalap beszúrása */
            $recid = $adatbazis_kapcsolat -> i('munkalap',
                array('ikt_szam',   'statusz_id',  'tipus_id',  'telephely_id', 'loginnev',                         'update_dt',         'update_usr',                          'insert_dt',          'insert_usr'                              ),
                array($ikt_szam,     1,             1,          $telephely_id,  $_SESSION[get_db_tabla().'user'],   date('Y-m-d H:i:s'), $_SESSION[get_db_tabla().'teljes_nev'], date('Y-m-d H:i:s'),  $_SESSION[get_db_tabla().'teljes_nev']   )
            );

            if( $recid === false ) {
                $db_error = true;
            }

            /* Hozzákapcsoljuk a bejelentéseket */
            foreach($idk as $id) {
                $sql = $adatbazis_kapcsolat -> i('munkalap_kapcs',
                    array('munkalap_id', 'bejelentes_id',  'update_dt',          'update_usr',                          'insert_dt',          'insert_usr',                            ),
                    array($recid,        $id,               date('Y-m-d H:i:s'), $_SESSION[get_db_tabla().'teljes_nev'], date('Y-m-d H:i:s'),  $_SESSION[get_db_tabla().'teljes_nev']  )
                );

                if( $sql === false ) {
                    $db_error = true;
                }
            }

            /* Felvesszük az első státuszt */
            $sql = $adatbazis_kapcsolat -> i('munkalap_statusz',
                array('id',     'stat_id', 'insert_dt',          'insert_usr',                              'update_dt',          'update_usr'                           ),
                array($recid,   1,         date('Y-m-d H:i:s'),  $_SESSION[get_db_tabla().'teljes_nev'],    date('Y-m-d H:i:s'), $_SESSION[get_db_tabla().'teljes_nev'] )
            );

            if( $sql === false ) {
                $db_error = true;
            }
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            echo json_encode(array(
                'statusz' => 'siker',
                'recid' => $recid
            ));
        } else {
            mysqli_rollback($db_kapcs);

            echo json_encode(array(
                'hiba' => tranzakcioHiba()
            ));
        }
    }

    $weblap->leallit();
    exit();
}

/* Bejegyzések elutasítása */
if( isset($_POST['bejegyzesElutasitas']) ) {
    $recid          = $_POST['recid'];
    $tabla          = $_POST['tabla'];
    $memo           = $_POST['memo'];
    $elutasitas_id  = $_POST['elutasitas_id'];
    $idk            = json_decode($_POST['idk'], true);

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $elutasitas_id && is_array($idk) ) {
        foreach($idk as $id) {
            $statusz = addStatusz( 'bejelentes_statusz', $id, 3, $elutasitas_id );

            if( $statusz === false ) {
                $db_error = true;
            }

            /* Hozzáadjuk a megjegyzést is ha van */
            if( $memo ) {
                $sql = $adatbazis_kapcsolat -> i('megjegyzes',
                    array('tabla', 'id', 'megj', 'loginnev',                       'insert_dt',          'insert_usr',                              'update_dt',          'update_usr'                          ),
                    array($tabla,  $id,  $memo,  $_SESSION[get_db_tabla().'user'], date('Y-m-d H:i:s'),  $_SESSION[get_db_tabla().'teljes_nev'],    date('Y-m-d H:i:s'), $_SESSION[get_db_tabla().'teljes_nev'] )
                );

                if( $sql === false ) {
                    $db_error = true;
                }
            }
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            echo json_encode(array(
                'statusz' => 'siker'
            ));
        } else {
            mysqli_rollback($db_kapcs);

            echo json_encode(array(
                'hiba' => tranzakcioHiba()
            ));
        }
    }

    $weblap->leallit();
    exit();
}

/* Csoportba rendezés */
if( isset($_POST['csoportbolTorol']) ) {
    $oszlop = $_POST['oszlop'];
    $idk    = json_decode($_POST['idk'], true);
    $page   = $_POST['page'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $oszlop && is_array($idk) ) {
        $sql = $csoportok = $adatbazis_kapcsolat->s('csoport_id', $page, "`recid` IN (".implode(',', $idk).")", '', '');

        if( $sql === false ) {
            $db_error = true;
        }

        $csoportok = adatbazis2tomb($csoportok, 'csoport_id');

        /* Kiszedjük a duplikációkat */
        $csoportok = array_unique($csoportok);

        /* Frissítjük a megfelelő sorokat */
        $sql = $adatbazis_kapcsolat -> u($page, array($oszlop), array(0), "`recid` IN (".implode(',', $idk).")");

        if( $sql === false ) {
            $db_error = true;
        }

        /* Csoportok bejárása */
        if( $csoportok ) {
            foreach($csoportok as $csoport) {
                $sql = $csoportok = $adatbazis_kapcsolat->s('recid', $page, "`csoport_id` = '{$csoport}' AND `torolve` = '0'", '', '1');

                if( $sql === false ) {
                    $db_error = true;
                }

                if( !$csoportok ) {
                    $sql = $adatbazis_kapcsolat -> d($page.'_csoport', "`recid` = '{$csoport}'");

                    if( $sql === false ) {
                        $db_error = true;
                    }
                }
            }
        }

        /* Select előállítása */
        $html = '<select id="csoportID" name="csoportID"><option value="">- Válassz -</option>';

        $sql = $csoportok = $adatbazis_kapcsolat->s('recid nev', $page.'_csoport', '', 'nev', '');

        if( $sql === false ) {
            $db_error = true;
        }

        if( $csoportok ) {
            foreach($csoportok as $csoport) {
                $html .= '<option value="'.$csoport['recid'].'">'.$csoport['nev'].'</option>';
            }
        }

        $html .= '</select>';

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            echo json_encode(array(
                'statusz' => 'siker',
                'html' => $html
            ));
        } else {
            mysqli_rollback($db_kapcs);

            echo json_encode(array(
                'hiba' => tranzakcioHiba()
            ));
        }
    }

    $weblap->leallit();
    exit();
}

/* Csoportba rendezés */
if( isset($_POST['csoportbaRendez']) ) {
    $csoportID = $_POST['csoportID'];
    $idk = json_decode($_POST['idk'], true);
    $page = $_POST['page'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $csoportID && is_array($idk) ) {
        $sql = $is_csoport = $adatbazis_kapcsolat->s('recid', $page, "`recid` IN (".implode(',', $idk).") AND `csoport_id` > '0'", '', '');

        if( $sql === false ) {
            $db_error = true;
        }

        if( !$is_csoport ) {
            $sql = $adatbazis_kapcsolat -> u($page, array('csoport_id'), array($csoportID), "`recid` IN (".implode(',', $idk).")");

            if( $sql === false ) {
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
                    'hiba' => tranzakcioHiba()
                ));
            }
        } else {
            echo json_encode(array(
                'hiba' => 'Kérem, csak olyan sorokat jelöljön ki, <br>amelyek még nem rendelkeznek csoporttal!'
            ));
        }
    } else {
        echo json_encode(array(
            'hiba' => 'Ismeretlen hiba történt!'
        ));
    }

    $weblap->leallit();
    exit();
}

/* Új csoport/várólista hozzáadása */
if( isset($_GET['addCsoport']) ) {
    $nev  = $_GET['csoportnev'];
    $page = $_GET['page'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sql = $csoport = $adatbazis_kapcsolat->s('nev', $page.'_csoport', "`nev` = '{$nev}'", '', '1');

    if( $sql === false ) {
        $db_error = true;
    }

    if( !$csoport ) {
        $sql = $adatbazis_kapcsolat -> i($page.'_csoport', array('nev', 'insert_dt', 'insert_usr', 'update_dt', 'update_usr'), array($nev, date('Y-m-d H:i:s'),  $_SESSION[get_db_tabla().'teljes_nev'], date('Y-m-d H:i:s'), $_SESSION[get_db_tabla().'teljes_nev']));

        if( $sql === false ) {
            $db_error = true;
        }

        /* Select előállítása */
        $html = '<select id="csoportID" name="csoportID"><option value="">- Válassz -</option>';

        $sql = $csoportok = $adatbazis_kapcsolat->s('recid nev', $page.'_csoport', '', 'nev', '');

        if( $sql === false ) {
            $db_error = true;
        }

        if( $csoportok ) {
            foreach($csoportok as $csoport) {
                $html .= '<option value="'.$csoport['recid'].'">'.$csoport['nev'].'</option>';
            }
        }

        $html .= '</select>';

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            echo json_encode(array(
                'statusz' => 'siker',
                'html' => $html
            ));
        } else {
            mysqli_rollback($db_kapcs);

            echo json_encode(array(
                'hiba' => tranzakcioHiba()
            ));
        }
    } else {
        echo json_encode(array(
            'hiba' => 'Ilyen csoport már létezik!'
        ));
    }

    $weblap->leallit();
    exit();
}

/* Mellékletek törlése */
if( isset($_POST['mellekTorol']) ) {
    $tabla  = $_POST['tabla'];
    $recid  = $_POST['recid'];
    $idk    = json_decode($_POST['idk'], true);

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    //Ki-ki csak a saját megjegyzését törölheti
    if( $tabla != 'telephely_kapcs' && $tabla != 'munkalap_kapcs' ) { //telephely_kapcs-nál nincs ilyen feltétel
        if( $sql = $adatbazis_kapcsolat->s('id', $tabla, "`recid` IN (".implode(',', $idk).") AND `loginnev`!='{$_SESSION[get_db_tabla().'user']}'")){
            echo json_encode(array(
                'hiba' => 'Más által létrehozott bejegyzés nem törölhető!'
            ));

            $weblap->leallit();
            exit();
        }
    }

    if( $sql === false ) {
        $db_error = true;
    }

    if( $tabla && is_array($idk) ) {
        if( $tabla == 'munkalap_kapcs' ) {
            /* Újra nyitott státuszú lesz a bejegyzés */
            foreach($idk as $id) {
                $statusz = addStatusz( 'bejelentes_statusz', $id, 1 );

                if( $statusz === false ) {
                    $db_error = true;
                }

                /* Töröljük a csoportjukat, ha lenne */
                $sql = $adatbazis_kapcsolat -> u('bejelentes', array('csoport_id'), array(0), "`recid` = '{$id}'");

                if( $sql === false ) {
                    $db_error = true;
                }

                /* Sorok törlése */
                $sql = $adatbazis_kapcsolat -> d($tabla, "`munkalap_id` = '{$recid}' AND `bejelentes_id` = '{$id}'");

                if( $sql === false ) {
                    $db_error = true;
                }
            }
        } else {
            $sql = $adatbazis_kapcsolat -> d($tabla, "`recid` IN (".implode(',', $idk).")");

            if( $sql === false ) {
                $db_error = true;
            }
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            echo json_encode(array(
                'statusz' => 'siker'
            ));
        } else {
            mysqli_rollback($db_kapcs);

            echo json_encode(array(
                'hiba' => tranzakcioHiba()
            ));
        }
    }

    $weblap->leallit();
    exit();
}

/* Objektumok generálása */
if( isset($_GET['gepVane']) ) {
    $telephely_id = $_GET['telephely_id'];
    $obj_id = $_GET['obj_id'];

    $eszkozkezeles = $adatbazis_kapcsolat->s('recid', 'eszkozkezeles', "`telephely_id` = '{$telephely_id}' AND `obj_id` = '{$obj_id}'", '', '1');

    if( $eszkozkezeles ) {
        echo json_encode(array(
            'statusz' => true
        ));
    } else {
        echo json_encode(array(
            'statusz' => false
        ));
    }

    $weblap->leallit();
    exit();
}

/* Objektumok generálása */
if( isset($_GET['obj_general']) ) {
    $telephely_id = $_GET['telephely_id'];

    echo obj_gen( $telephely_id );

    $weblap->leallit();
    exit();
}

/** További beállítások */
if( isset($_GET['naploTorol']) ) {
    $tabla = $_GET['tabla'];
    $sorID = $_GET['sorID'];

    if( $tabla && $sorID ) {
        $adatbazis_kapcsolat -> d($tabla, 'recid='.$sorID);
    }

    $weblap->leallit();
    exit();
}

if( isset($_POST['addMegjegyzes']) ) {
    $pageID = $_POST['pageID'];
    $megj   = $_POST['megj'];
    $tabla  = $_POST['tabla'];

    if( $pageID && $megj ) {
        $kapcsID = $adatbazis_kapcsolat -> i('megjegyzes',
                                                array('tabla',  'id',    'megj',    'insert_dt',         'insert_usr',                          'loginnev'                        ),
                                                array($tabla,   $pageID, $megj,     date('Y-m-d H:i:s'), $_SESSION[get_db_tabla().'teljes_nev'], $_SESSION[get_db_tabla().'user'] )
                                            );

        echo json_encode(array(
            'statusz' => 'siker',
            'kapcsID' => $kapcsID,
            'uzi'     => ''
        ));
    }

    $weblap->leallit();
    exit();
}

if( isset($_GET['addSzakterulet']) ) {
    $szallitoID = $_GET['szallitoID'];
    $szakterID  = $_GET['szakterID'];

    if( $szallitoID && $szakterID ) {
        $isKapcs = $adatbazis_kapcsolat->s('recid', 'szakter_kapcs', "`szallito_id` = '{$szallitoID}' AND `szakter_id` = '{$szakterID}'", '', '1');

        if( !$isKapcs ) {
            $kapcsID = $adatbazis_kapcsolat -> i('szakter_kapcs', array('szallito_id', 'szakter_id'), array($szallitoID, $szakterID));

            /* Szakterület lekérdezése */
            $sor = $adatbazis_kapcsolat->s('recid', 'erteklista', "`recid` = '{$szakterID}'", '', '1');

            if( $sor ) {
                echo json_encode(array(
                    'statusz' => 'siker',
                    'kapcsID' => $kapcsID,
                    'uzi' => ''
                ));
            }
        } else {
            echo json_encode(array(
                'statusz' => 'hiba',
                'uzi' => 'Ez a szakterület már hozzáadásra került!'
            ));
        }
    }

    $weblap->leallit();
    exit();
}

if( isset($_GET['addSzalloda']) ) {
    $loginID = $_GET['loginID'];
    $szallID = $_GET['szallID'];

    if( $loginID && $szallID ) {
        $felh = $adatbazis_kapcsolat->s('nev', 'login', 'recid='.$loginID, '', '1');

        if( $felh ) {
            $felh = $felh[0];

            $isKapcs = $adatbazis_kapcsolat->s('recid', 'telephely_kapcs', "`telephely_id` = '{$szallID}' AND `nev` = '".$felh['nev']."'", '', '1');

            if( !$isKapcs ) {
                $kapcsID = $adatbazis_kapcsolat -> i('telephely_kapcs', array('nev', 'telephely_id'), array($felh['nev'], $szallID));

                /* Szálloda lekérdezése */
                $sor = $adatbazis_kapcsolat->s('nev', 'telephely', "`id` = '{$szallID}'", '', '1');

                if( $sor ) {
                    echo json_encode(array(
                        'statusz' => 'siker',
                        'kapcsID' => $kapcsID,
                        'uzi' => ''
                    ));
                }
            } else {
                echo json_encode(array(
                    'statusz' => 'hiba',
                    'uzi' => 'Ez a szálloda már hozzáadásra került!'
                ));
            }
        }
    }

    $weblap->leallit();
    exit();
}

if( isset($_GET['ertekFoglalt']) ) {
    $value = $_GET['value'];
    $tabla = $_GET['tabla'];
    $mezo  = $_GET['mezo'];

    if( $value && $tabla && $mezo ) {
        $sor = $adatbazis_kapcsolat->s($mezo, $tabla, $mezo.'='.$value, '', '1');

        if( $sor ) {
            echo json_encode(array(
                'uzi' => 'Ez az érték már foglalt!'
            ));
        } else {
            echo json_encode(array(
                'uzi' => ''
            ));
        }
    }

    $weblap->leallit();
    exit();
}

if( isset($_GET['szamlaSorFrissit']) ) {
    $tabla = $_GET['tabla'];
    $recid = $_GET['recid'];
    if( $tabla && $recid ) {
        $szamla_lista = szamla_lista("`".get_db_tabla().$tabla."`.`recid` = '{$recid}'", $tabla);

        echo $szamla_lista;
    }

    $weblap->leallit();
    exit();
}

if( isset($_GET['szamlaLista']) ) {

    $szamla_lista = szamla_lista('', $_GET['tabla']);

    if( $szamla_lista ) {
        echo '<div class="szamlaLapozas fent clearFix">'.$lapozas.'</div>'.$szamla_lista.'<div class="szamlaLapozas clearFix">'.$lapozas.'</div>';
    } else {
        echo '<ul class="szamla_cont"><li class="szamla_datum"><strong>Nincs találat.</strong></li></ul>';
    }

    $weblap->leallit();
    exit();
}

//Kell-e kétszintű kategóriát kezelni vagy sem
$ketszintu_kat = false;

//Változók mentése SESSION-ba és újrabetöltés, hogy a kiválasztott lista rendezése és szűkítése megmaradjon
if(isset($_GET['unset'])){
	unset($_SESSION['kat']);
	unset($_SESSION['epo']);
	unset($_SESSION['oldal']);
	unset($_SESSION['order']);
	unset($_SESSION['desc']);

	unset($_GET['kat']);
	unset($_GET['oldal']);
	unset($_GET['order']);
	unset($_GET['desc']);
}else{
	if(isset($_GET['kat']))		{ $_SESSION['kat']   = $_GET['kat'];  	}
	if(isset($_GET['epo']))		{ $_SESSION['epo']   = $_GET['epo'];   	}
	if(isset($_GET['oldal']))   { $_SESSION['oldal'] = $_GET['oldal'];  }
	if(isset($_GET['order']))	{ $_SESSION['order'] = $_GET['order'];  }
	if(isset($_GET['desc']))	{ $_SESSION['desc']  = true;    		}
	if(isset($_GET['nodesc']))	{ $_SESSION['desc']  = false;    		}

	$_GET['kat']   = $_SESSION['kat'];
	$_GET['epo']   = $_SESSION['epo'];
	$_GET['oldal'] = $_SESSION['oldal'];
	$_GET['order'] = $_SESSION['order'];
	if($_SESSION['desc']){ $_GET['desc']='desc'; }

}
if( !$_GET['epo'] ){   $_GET['epo'] = 50;       }
if( !isset($_GET['oldal'])){ $_GET['oldal']=0;  }


//ESEMÉNYEK
//Flashfáljok törlése
if(isset($_POST['fajl_torol'])){
    $elem = $adatbazis_kapcsolat -> s($_POST['oszlop'], $_POST['tabla'], 'recid='.$_POST['id']);
    kep_torol('../'.get_utvonal().$elem[0][$_POST['oszlop']]);
    $adatbazis_kapcsolat -> u($_POST['tabla'], $_POST['oszlop'], '', 'recid='.$_POST['id']);

    $weblap -> leallit();
    exit;
}

// Admin fájlfeltöltés thumb kinyerése
if (isset($_GET['get_admin_file_thumb'])) {
    echo get_admin_file_thumb($_GET['tabla'], $_GET['oszlop'], $_GET['id']);
    $weblap -> leallit();
    exit;
}
function get_admin_file_thumb($tabla, $oszlop, $id){

    global $adatbazis_kapcsolat;
    $utvonal = $adatbazis_kapcsolat->s($oszlop, $tabla, 'recid='.$id);
    $utvonal = '../'.get_utvonal().$utvonal[0][$oszlop];
    if(is_file($utvonal)){

        $neve = basename($utvonal);
        $kit = kit($neve);
        if($kit!='jpg' && $kit!='png' && $kit!='gif'){
            if(is_file('../elemek/ikonok/'.$kit.'.png')){
                $akt_fajl = '../elemek/ikonok/'.$kit.'.png';
            }else{
                $akt_fajl = '../elemek/ikonok/ismeretlen.png';
            }

            return nl2('
                    <div id="fajl_'.$tabla.'_'.$oszlop.'_'.$id.'">
                        <script>var gombfajl_'.$oszlop.'_'.$id.'=get_fajl_torol_gomb(\''.$id.'\', \''.$tabla.'\', \''.$oszlop.'\');</script>
                        <div class="kep_torol link">
                            <span>

                            </span>

                        </div>
                        <div id="fajl_'.$oszlop.'_'.$id.'">

                            <div class="file_thumb">
                                <div class="dtc vam" title="Fájl letöltése">
                                    <a href="'.$utvonal.'" target="_blank">
                                        <img alt="'.htmlspecialchars($sor['nev']).'" src="'.$akt_fajl.'" />
                                    </a>
                                </div>
                                <div class="dtc vam f16 link full" onclick="window.open(\''.$utvonal.'\')" title="Fájl letöltése">
                                    '.basename($utvonal).'
                                </div>
                                <div class="dtc vam link" onclick="alert2(\'Biztosan törli a fájlt?\', gombfajl_'.$oszlop.'_'.$id.')" title="Fájl törlése">
                                    x
                                </div>
                            </div>

                        </div>
                    </div>
                ');
        //Ebben az esetben képelemről van szó
        }else{

            //Ha elég széles a kép, akkor resposítjuk, amúgy nem
            $szelesseg = 0;
            $width     = 'width: 100%';
            if(is_file($utvonal)){
                $szelesseg = getimagesize($utvonal);
                $szelesseg = $szelesseg[0];
            }
            if($szelesseg<500){
                $width = '';
            }

            return nl2('
                    <div id="fajl_'.$tabla.'_'.$oszlop.'_'.$id.'">
                        <script>var gombfajl_'.$oszlop.'_'.$id.'=get_fajl_torol_gomb(\''.$id.'\', \''.$tabla.'\', \''.$oszlop.'\');</script>
                        <div class="kep_torol link">
                            <span onclick="alert2(\'Biztosan törli a fájlt?\', gombfajl_'.$oszlop.'_'.$id.')">
                                <img src="../elemek/admin/torol.png" alt="x" title="Kép törlése">
                                Kép törlése
                            </span>
                            &nbsp;&nbsp;&nbsp;&nbsp;
                            <span onclick="kep_forgat(\''.$utvonal.'\', \'-90\', \'#fajl_'.$oszlop.'_'.$id.'\')">
                                <img src="../elemek/admin/elforgat.png" alt="C" title="Kép elforgatása">
                                Kép elforgatása
                            <span>
                        </div>
                        <div id="fajl_'.$oszlop.'_'.$id.'">
                            '.kep_kiir($utvonal, htmlspecialchars($sor['nev']), 600, 600, '', $width, 'lightboxos').'
                        </div>
                    </div>
                ');
        }

    }
}

// Képforgatás lekezelése
if (isset($_GET['kep_forgat'])) {
    echo kep_forgat($_GET['utvonal'], $_GET['szazalek']).'?'.time();
    $weblap -> leallit();
    exit;
}

//Sorrend mentése
if(isset($_POST['sorrendezes'])){

    //Mindig egy undefined-el kezdődik ezt le kell venni
    $_POST['sorrendezes'] = substr($_POST['sorrendezes'], 9, strlen($_POST['sorrendezes']));

    $i = 1;
    $idk = explode(';', $_POST['sorrendezes']);
    foreach($idk as $id){
        if($id){
            $adatbazis_kapcsolat -> u($_POST['tabla'], 'sor', $i, 'id='.$id);
        }
        $i++;
    }

    $weblap -> leallit();
    exit;
}

//Inline szerkesztéshez
if(isset($_GET['inline_szerk'])){

    $adatbazis_kapcsolat->u($_GET['tabla'], array($_GET['oszlop']), array($_GET['ertek']), 'id='.$_GET['sorid']);
    $visszaolvas = $adatbazis_kapcsolat->s($_GET['oszlop'], $_GET['tabla'], 'id='.$_GET['sorid']);
    if(is_numeric($visszaolvas[0][$_GET['oszlop']])){
        echo number_format($visszaolvas[0][$_GET['oszlop']], 0, '', '.');
    }else{
        echo $visszaolvas[0][$_GET['oszlop']];
    }
    $weblap -> leallit();
    exit;
}

foreach($postok as $ertek){
	//Új feltételt használok, hogy feleslegesen ne kelljen minden tömböt bejárni. Csak az aktuális php fájl nevével megegyező tömböt pászátzza végig
    //if(isset($_POST[$ertek['uj']]) || isset($_POST[$ertek['lista']])){//ÚJ TERMÉK FELTÖLTÉSE
	if( (isset($_POST[$ertek['uj']]) || isset($_POST[$ertek['lista']])) ){

		//Checkboxból 1 és 0, számok ezresítése
		foreach($ertek['tomb'] as $elem){
            if($elem['type'] == 'checkbox'){
                if($_POST[$elem['name']]=='on' || $_POST[$elem['name']]=='true'){
                    $_POST[$elem['name']] = 1;
                }else{
                    $_POST[$elem['name']] = 0;
                }

                //Pipa ki-be kapcsolás, Ajaxos hívással
                if( isset($_POST['pipa_'.$elem['name']]) ){
                    $pipa_on = $adatbazis_kapcsolat -> s($elem['name'], $ertek['tabla'], 'id='.$_POST['id']);
                    if( $pipa_on[0][$elem['name']] ){
                        $adatbazis_kapcsolat -> u($ertek['tabla'], $elem['name'], '', 'id='.$_POST['id']);
                    }else{
                        $adatbazis_kapcsolat -> u($ertek['tabla'], $elem['name'], '1', 'id='.$_POST['id']);
                    }
                    $weblap -> leallit();
                    exit;
                }
            }

            if($elem['ezresit']){
                if($_POST[$elem['name']]){ $_POST[$elem['name']] = str_replace('.', '', $_POST[$elem['name']]); }
            }

        }

		//Ha az újként szerkesztés post nem üres, akkor beállítjuk a $_POST-olt értékeket egy SESSION-ba, majd ezzekkel az adatokkal indul egy új feltöltés adatlapja
		if($_POST[$ertek['tabla'].'ujkent_szerk']){
			foreach($ertek['tomb'] as $akt_tomb_ertek){
				$_SESSION[$akt_tomb_ertek['name']] = stripslashes( $_POST[$akt_tomb_ertek['name']] );
			}
			$adatbazis_kapcsolat -> bezar();
			header('location:'.self().'?uj&ujkent');
			exit();
		}

		if(isset($_POST[$ertek['uj']])){

			$u_i_ret  = i_u('insert',$ertek['tabla'],$ertek['tomb']);
			$errnum   = $u_i_ret['0'];
			$tabla_id = $u_i_ret['1'];
			$uj_lista   = 'uj';
		}elseif(isset($_POST[$ertek['lista']])){
			$u_i_ret  = i_u('update',$ertek['tabla'],$ertek['tomb']);
			$errnum   = $u_i_ret['0'];
			$tabla_id = $u_i_ret['1'];
			$uj_lista   = 'lista';
		}

		//KÉPEK
		//Hír-Kép feltöltése
		$kephiba = kep_menedzser('termek_kep', $ertek['tomb'], $ertek['tabla'], $tabla_id, $uj_lista, 440600, true);
		//KÉPEK VÉGE

		$url = '';
		if(isset($_GET['id'])){
			$url      = '&id='.$_GET['id'];
			$tabla_id = $_GET['id'];
		}

		//Ha hibamentes volt a feltöltés (adatbázis beszúráskor az adatok illetve képhiba is rendben van)
		if( $errnum == '' && $kephiba['1'] <= 0){

			$adatbazis_kapcsolat -> bezar();
			if(isset($_POST['ugras_kepekhez'])){
                header('location:'.self().str_replace('&amp;','&',get_get(array('id', 'hiba', 'errnum', 'kephiba', 'oldal', 'epo', 'kat', 'order'))).'&id='.$tabla_id.'#kepek');
            }else{
                //Csak akkor van továbbléptetés, ha nem ajaxosan hívtuk meg
                if(!isset($_POST['ajax_form_modosit'])){
                    //header('location:'.self().'?uj')
                    //Mégis a létrehozott adatlapra érkezzünk:
                    header('location:'.self().str_replace('&amp;','&',get_get(array('id', 'hiba', 'errnum', 'kephiba'))).'&id='.$tabla_id.'&siker');
                }
            }

		//Hiba történt
		}else{
			if($errnum != '-1')    { $url .= '&errnum='.$errnum;      }
			if($kephiba['1'] != ''){ $url .= '&kephiba='.$kephiba['1']; }

			$kivetelek = array('siker', 'hiba', 'errnum', 'kephiba', 'modosit', 'oldal', 'epo', 'kat', 'order');

			if(isset($_POST[$ertek['uj']])){
				array_push($kivetelek,'modosit');
			}

			$url=self().get_get($kivetelek, true).'&hiba&modosit'.$url;

			$adatbazis_kapcsolat -> bezar();
			header('location:'.$url);
		}
		exit();
	}

	//Törlés, töröl
	if(isset($_GET[$ertek['torid']])){

        //Jogosultságkezelés
        if($ertek['tabla']=='bejelentes' && !_jog(1)){
            $weblap -> leallit(); exit;
        }
        //Diszpécsser nem törölhet bejegyzést
        if($ertek['tabla']=='bejelentes' && _jog(2)){
            $weblap -> leallit(); exit;
        }
        if($ertek['tabla']=='utemezett_feladatok' && !_jog(4)){
            $weblap -> leallit(); exit;
        }
        if($ertek['tabla']=='login' && !_jog(7)){
            $weblap -> leallit(); exit;
        }
        if($ertek['tabla']=='erteklista' && !_jog(9)){
            $weblap -> leallit(); exit;
        }
        if($ertek['tabla']=='erteklista' && !_jog(11)){
            $weblap -> leallit(); exit;
        }
        if($ertek['tabla']=='anyagigenyles' && !_jog(13)){
            $weblap -> leallit(); exit;
        }
        if( $ertek['tabla']=='raktar' && !_jog(18)){
            $weblap -> leallit(); exit;
        }
        if( $ertek['tabla']=='cikktorzs' && !_jog(20)){
            $weblap -> leallit(); exit;
        }
        if( $ertek['tabla']=='torzsadat' && !_jog(26)){
            $weblap -> leallit(); exit;
        }
        if( $ertek['tabla']=='szabadsagigeny' && !_jog(28)){
            $weblap -> leallit(); exit;
        }
        if( $ertek['tabla']=='beosztas' && !_jog(30)){
            $weblap -> leallit(); exit;
        }
        //Login egyáltalán nem törölhető
        if($ertek['tabla']=='login'){
            $weblap -> leallit(); exit;
        }

        //Törléssel kapcsolatos műveletek egy tranzakció
        $db_error = false;
        mysqli_autocommit($db_kapcs, false);

        //Csak az törölheti, aki létrehozta
        $csak_aki_letrehozta_torolheti = array('bejelentes', 'utemezett_feladatok');
        if( in_array($ertek['tabla'], $csak_aki_letrehozta_torolheti) ){
            $loginnev = $adatbazis_kapcsolat->s('loginnev', $ertek['tabla'], "`recid`='{$_GET[$ertek['torid']]}'");
            if( $loginnev === false ) {
                $db_error = true;
            }
            $loginnev = $loginnev[0];
            if($loginnev['loginnev']!=$_SESSION[get_db_tabla().'user']){
                $weblap -> leallit(); exit;
            }
        }
        $lekerdez = $adatbazis_kapcsolat -> select('*', $ertek['tabla'], "`recid`='{$_GET[$ertek['torid']]}'");
		$sor      = $lekerdez[0];

        if( $lekerdez === false ) {
            $db_error = true;
        }


        /* Kapcsolódó táblák törlése */
        //Megjegyzéseket általánosságban
        $sql = $adatbazis_kapcsolat->d('megjegyzes', "`tabla`='{$ertek['tabla']}' AND `id`='{$sor['recid']}'");
        if( $sql === false ) { $db_error = true; }

        if($ertek['tabla']=='bejelentes'){
            $sql = $adatbazis_kapcsolat->d('bejelentes_statusz', "`id`='{$sor['recid']}'");
            if( $sql === false ) { $db_error = true; }
            $fajlok = $adatbazis_kapcsolat->s('*', 'bejelentes_dok', "`bejelentes_id`='{$sor['recid']}'");
            foreach($fajlok as $fajl){
                unlink('../'.get_utvonal().$fajl['kepnev']);
            }

            $sql = $adatbazis_kapcsolat->d('bejelentes_dok', "`bejelentes_id`='{$sor['recid']}'");
            if( $sql === false ) { $db_error = true; }
        }

        //Login táblából csak logikailag törlünk. Többi helyről fizikailag.
        if($ertek['tabla']=='login'){
            $sikeres_torles = $adatbazis_kapcsolat -> u($ertek['tabla'], array('torolve'), array(1), "`recid`='{$sor['recid']}'");
        }else{
            $sikeres_torles = $adatbazis_kapcsolat -> d($ertek['tabla'], "`recid`='{$sor['recid']}'");
        }
        if( $sikeres_torles === false ) {
            $db_error = true;
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            if(isset($_GET['noload'])){

                //Visszaadjuk az elemek számát
                echo number_format( count($adatbazis_kapcsolat->s('recid', $ertek['tabla'])), 0, '', '.' );

				$weblap -> leallit();
				exit();
			}else{
				$weblap -> leallit();
				//header('location:'.self().str_replace('&amp;','&',get_get(array($ertek['torid'], 'kat', 'epo', 'oldal', 'order'))).'&siker&'.$ertek['torid'].'tobbkep_torol='.$_GET[$ertek['torid']]);
				header('location:'.self());
				exit();
			}

        } else {

            mysqli_rollback($db_kapcs);

            if(isset($_GET['noload'])){
                echo 'A törlés nem sikerült!';
                $weblap -> leallit();
                exit();
            }else{
                $weblap -> leallit();
                //header('location:'.self().str_replace('&amp;','&',get_get(array($ertek['torid']))).'&hiba');
                header('location:'.self().str_replace('&amp;','&',get_get(array($ertek['torid']))).'&hiba');
                exit();
            }
        }

	}
}

//JS FÁJLFELTÖLTÉSHEZ Új bejegyzés feltöltése után a csatolt fájl eltárolása adatbázisba és megfelelő mappába másolás
if(isset($_GET['modosit'])){

    $tabla = str_replace('.php', '', basename(self()));

    //Nézzük meg, hogy van-e SESSION id kezdetű fájl a könyvtárban
    $utvonal = '../'.get_utvonal().'cache/jsfajlok/'.session_id().'*';
    $session_kezdetu_fajlok = glob($utvonal); //Ha több ilyen is van az azért lehet, mert több fájlfeltöltés input is volt a tömbben
    foreach($session_kezdetu_fajlok as $akt_fajl){
        list($eleje, $oszlop, $fajlnev, $vege) = explode('|||', $akt_fajl);

        //Ha feltöltéskor a 'get_id'-t adtunk meg fájlnévnek, akkor az algoritmus ID, azaz a GET id szerint mentjük el
        if($fajlnev=='get_id'){
            $fajlnev = $_GET['id'];

        //Ha nincs informácónk a fájl nevéről, akkor az algoritmus az NEV, azaz a bejegyzés nevéből származtatjuk
        }elseif(!$fajlnev){
            $fajlnev = $adatbazis_kapcsolat->s('nev', $tabla, 'id='.$_GET['id']);
            $fajlnev = fajlnev_keszit($fajlnev[0]['nev']);
        }

        $akt_utvonal = '../'.get_utvonal().$tabla.'/';
        $kepnev      = $fajlnev;
        $ujnev       = $kepnev.'_1';

        $i = 1;
        //DE! megnézzük, hogy ez a '_1' nem foglalt-e. Ha az, keresünk szabad számot
        while( glob($akt_utvonal.$ujnev.'*') ){
            $i++;
            $ujnev = $kepnev.'_'.$i;
        }

        $kepnev = fajlnev_keszit($ujnev).'.'.kit($akt_fajl);

        //Fájlok mozgatása
        copy($akt_fajl, $akt_utvonal.$kepnev);
        unlink($akt_fajl);

        //Végül letároljuk az adatbázisba is
        $adatbazis_kapcsolat->u($tabla, array($oszlop), array($tabla.'/'.$kepnev), 'id='.$_GET['id']);

    }
}

//Kijelölt termékek törlése
if(isset($_POST['del'])){
	$mit   = array('.jpg','.gif','.png');
	$mivel = array('','','');
	//Megkeressük azt a tömböt, amelyből törlünk
    foreach($postok as $ertek){
        if($ertek['tabla'] == $_POST['tabla']){
			$akt_tomb = $ertek['tomb'];
			$konyvtar = $ertek['tabla'];
		}
	}

    //minden kijelölt bejegyzésre
    foreach($_POST['del'] as $ertek){

		//ha megadtunk táblanevet és oszlopnevet, akkor több kép van
        if( $_POST['oszlopnev'] && $_POST['tabla'] ){
            //Megnézzük hogy a kepek táblában van-e ide vonatkozó rész (FG)
            $kepek = $adatbazis_kapcsolat -> select('kepnev', 'kepek', $_POST['oszlopnev'].'='.$ertek);
			foreach($kepek as $sor_kepek){
				$sor_kepek['kepnev'] = '../'.get_utvonal().$sor_kepek['kepnev'];
				if(is_file($sor_kepek['kepnev'])){
					unlink($sor_kepek['kepnev']);
				}
				remover(str_replace($mit,$mivel,$sor_kepek['kepnev']).'_*');
			}
			$adatbazis_kapcsolat -> delete('kepek', $_POST['oszlopnev'].'='.$ertek);
		}

		//Az aktuális tömbben megkeressük a fájltipusokat
		foreach($akt_tomb as $tomb_ertek){
			if($tomb_ertek['type'] == 'file'){

				if($tomb_ertek['algoritmus'] == 'sorid'){

					$kepnev = '../'.get_utvonal().$konyvtar.'/'.$ertek;
					if(is_file($kepnev))       { unlink('../'.get_utvonal().$konyvtar.'/'.$ertek); }//ha létezik a fájl egy az egybe...
					//ha kép és nem volt hozzá kiterjesztés
					if(is_file($kepnev.'.jpg')){ unlink('../'.get_utvonal().$konyvtar.'/'.$ertek.'.jpg');}
					if(is_file($kepnev.'.gif')){ unlink('../'.get_utvonal().$konyvtar.'/'.$ertek.'.gif');}
					if(is_file($kepnev.'.png')){ unlink('../'.get_utvonal().$konyvtar.'/'.$ertek.'.png');}
					//ráadásul még át is méreteztük
					remover('../'.get_utvonal().$konyvtar.'/'.$_POST['id'].'_*');

				//nev és fajlnev esetében ugyanazt tesszük
				}else{

					$termek     = $adatbazis_kapcsolat -> select($tomb_ertek['oszlop'], $_POST['tabla'], 'id='.$ertek);
					$sor_termek = $termek[0];

					//ha van kiterjesztése
					if( strrpos( $sor_termek[$tomb_ertek['oszlop']], '.') > 0 ){
						$kepnev = substr($sor_termek[$tomb_ertek['oszlop']], 0, strrpos($sor_termek[$tomb_ertek['oszlop']], '.'));
						remover('../'.get_utvonal().$kepnev.'_*');
						if(is_file('../'.get_utvonal().$sor_termek[$tomb_ertek['oszlop']])){
							unlink('../'.get_utvonal().$sor_termek[$tomb_ertek['oszlop']]);
						}
					}

				}
			}
		}

		//Végül a termék bejegyzést töröljük
		$adatbazis_kapcsolat -> delete($_POST['tabla'], 'id='.$ertek);
	}

	$adatbazis_kapcsolat -> bezar();
	header('location:'.self().str_replace('&amp;','&',get_get()));
	exit();
}

//Kép törlése
if(isset($_GET['keptorol']) && $_GET['keptorol']!=''){

	if(isset($_GET['tabla'])){//Tehát a kép útvonala adatbázisban volt mentve, azt kell törölni
		$adatbazis_kapcsolat -> update($_GET['tabla'], array($_GET['oszlop']), array(''), $_GET['oszlop'].'='.$_GET['keptorol']);

        //ha van kiterjesztése
		if( strrpos( $_GET['keptorol'],'.') > 0 ){
			$kepnev = substr($_GET['keptorol'],0,strrpos($_GET['keptorol'],'.'));
			remover('../'.get_utvonal().$kepnev.'_*');
		}

		$kepnev = '../'.get_utvonal().$kepnev;
		if(is_file($kepnev))       { if(!unlink($kepnev)){        return 'k3'; } }
		if(is_file($kepnev.'.jpg')){ if(!unlink($kepnev.'.jpg')){ return 'k3'; } }
		if(is_file($kepnev.'.gif')){ if(!unlink($kepnev.'.gif')){ return 'k3'; } }
		if(is_file($kepnev.'.png')){ if(!unlink($kepnev.'.png')){ return 'k3'; } }

	//egyébként az sorid-t
	}else{

		$kepnev = '../'.get_utvonal().$_GET['keptorol'];
		if(is_file($kepnev))       { if(!unlink($kepnev)){        return 'k3'; } }
		if(is_file($kepnev.'.jpg')){ if(!unlink($kepnev.'.jpg')){ return 'k3'; } }
		if(is_file($kepnev.'.gif')){ if(!unlink($kepnev.'.gif')){ return 'k3'; } }
		if(is_file($kepnev.'.png')){ if(!unlink($kepnev.'.png')){ return 'k3'; } }

		remover($kepnev.'_*');
	}

	$adatbazis_kapcsolat -> bezar();
	$url=str_replace('&amp;','&',get_get(array('keptorol','tabla','oszlop')));
	header('location:'.self().$url);
	exit();
}

//Fájl törlése. Ajaxal hívódik meg. általában nem kép, ahnem csv, pdf .. fájlok kezelésére
if(isset($_POST['fajl_torles'])){
    require_once('../weblap.php');
	if(is_file($_POST['fajl'])){
		unlink($_POST['fajl']);
		remover(substr($_POST['fajl'], 0, strrpos($_POST['fajl'], '.')).'_*');
		$adatbazis_kapcsolat -> update($_POST['tabla'], array($_POST['oszlop']), array(''), 'id='.$_POST['fajl_torles']);
	}
	$adatbazis_kapcsolat -> bezar();
	exit();
}

/**
*	Kép menedzser kizárólag az eseményekhez való
*	FONTOS: ha képet töltünk fel, magától odateszi a kiterjesztést (.jpg .gif .png).
*	Átlagos fájlok esetén adjuk meg a kiterjesztést
*
*	@param string $megnevezes tömben a képváltozó neve pl.: hir_kep
*	@param array $tomb Tömb ami alapján dolgozunk. Jelen esetben mindig az $ertek[tomb] lesz az aktuális tömb, ezzel kell meghívni
*	@param string $tabla Adatbázis tábla neve (ilyen nevű mappába másol)
*	@param string $tabla_id Utolsó beszúrt sor, vagy módosított sor (amennyiben a fájlneve a sorid-ből származna)
*	@param string $uj_lista Új beszúrás vagy lista (uj|lista) (Ha uj akkor a régi kép felülíródhat, lista esetén új képnevet gyárt pl. teszt_1.jpg)
*	@param integer $meret méret limit, ami alapból 400 Kb
*	@param boolean $csakkep ez alapból igaz, tehát csak jpg, png és gifet enged feltölteni
*
*	@return array 0. argumentuma a kép neve, 1. pedig a hibaüzenet (ha van) <== ezt a web_ftp-ből örökli
*	k1: túllépte a limitet
*	k2: nem képtipusú fájl
*	k3: ilyen nevű fájl már létezett a szerveren és törölni nem lehetett
*	k4: nem sikerül felmásolni
*	k5: nincs `kep` oszlop az adatbázisban
*/
function kep_menedzser($megnevezes, $tomb, $tabla, $sor_id, $uj_lista, $meret, $csakkep){

    global $adatbazis_kapcsolat;

	if( $_FILES[$megnevezes]['name'] != '' ){
		foreach($tomb as $kep_ertek){
			if( $kep_ertek['name'] == $megnevezes ){
				$algoritmus  = $kep_ertek['algoritmus'];
				$fajl_oszlop = $kep_ertek['oszlop'];
			}
		}

		switch($algoritmus){
			//case 'sorid': $kepnev=$sor_id; break;

			case 'fajlnev':
				//a fájl neve + a kiterjesztés HA nem kép
				if($_FILES[$megnevezes]['type']=='image/pjpeg' || $_FILES[$megnevezes]['type']=='image/jpeg' || $_FILES[$megnevezes]['type']=='image/gif' || $_FILES[$megnevezes]['type']=='image/png' || $_FILES[$megnevezes]['type']=='image/x-png') {
					$kepnev = substr($_FILES[$megnevezes]['name'],0,strrpos($_FILES[$megnevezes]['name'],'.'));
					$kit    = '';//Ha képek töltönk fel, nem kell megadni a kiterjesztést!
				}else{
					$kit    = substr($_FILES[$megnevezes]['name'],strrpos($_FILES[$megnevezes]['name'],'.'),strlen($_FILES[$megnevezes]['name']));
					$kepnev = substr($_FILES[$megnevezes]['name'],0,strrpos($_FILES[$megnevezes]['name'],'.'));
				}

                $sor = $adatbazis_kapcsolat -> select($fajl_oszlop, $tabla, 'id='.$sor_id);
                $sor = $sor[0];

                //ha a fájlt felülírjuk (tehát már el volt tárolva a neve a táblában), akkor levesszük a a tábla nevét és ha kell a kiterjesztését (pl.: a tabla_neve/kepnev.jpg -ből)
                if( $sor[$fajl_oszlop] != '' ){

					$mit   = array($tabla.'/','.jpg','.gif','.png');
					$mivel = array('','','','');

					$kepnev    = str_replace($mit,$mivel,$sor[$fajl_oszlop]);
					$overwrite = true;	//Megmondjuk a webftp-nek, hogy az adott fáljt írja felül, ne szemeteljünk, takarítsunk magunk után

				}else{//Ha nincs a táblában a neve (új felöltés vagy módosítás, és sose volt kép, vagy törölve lett)
					if($uj_lista=='lista'){//Ha módosításkor nincs még kép hozzárendelve, akkor kap egy '_1'-t, hogy ne törölje az esetleges kepnev_2-t. Új feltöltéskor ezt a web_ftp() teszi meg

						$i=1;
						$ujnev=$tabla.'/'.$kepnev.'_1';
						while(glob('../'.get_utvonal().$ujnev.'*')){//DE! megnézzük, hogy ez a '_1' nem foglalt-e. Ha az keresünk szabad számot
							$i++;
							$ujnev=$kepnev.'_'.$i;
						}
						if( $i == 1 && $sor_id != '' ){ $ujnev=$kepnev.'_1'; }
						$kepnev = $ujnev;

						$mit    = array($tabla.'/','.jpg','.gif','.png');
						$mivel  = array('','','','');

						$kepnev = fajlnev_keszit(str_replace($mit,$mivel,$kepnev)).$kit;
						$overwrite = true;	//Megmondjuk a webftp-nek, hogy az adott fáljt írja felül, ne szemeteljünk, takarítsunk magunk után

					}else{//Ha új beszúrás, akkor gyártunk egy filename-t

						$kepnev    = fajlnev_keszit($kepnev).$kit;
						$overwrite = false;	//Ne töröljön esetleges azonos nevű fálokat, inább gyártsun újnevűeket új beszúrásnál

					}
				}
			break;

			case 'nev': //termék nevéből nyert képnév + az erdeti kiterjesztés HA nem kép
				if($_FILES[$megnevezes]['type'] == 'image/pjpeg' || $_FILES[$megnevezes]['type'] == 'image/jpeg' || $_FILES[$megnevezes]['type'] == 'image/gif' || $_FILES[$megnevezes]['type'] == 'image/png' || $_FILES[$megnevezes]['type'] == 'image/x-png') {
					$kit = '';
				}else{
					$kit = substr($_FILES[$megnevezes]['name'], strrpos($_FILES[$megnevezes]['name'], '.'), strlen($_FILES[$megnevezes]['name']));
				}

                $sor = $adatbazis_kapcsolat -> select($fajl_oszlop, $tabla, 'id='.$sor_id);
                $sor = $sor[0];

                //ha a fájlt felülírjuk (tehát már el volt tárolva a neve a táblában), akkor levesszük a a tábla nevét és ha kell a kiterjesztését (pl.: a tabla_neve/kepnev.jpg -ből)
				if($sor[$fajl_oszlop] != ''){

					$mit       = array($tabla.'/','.jpg','.gif','.png');
					$mivel     = array('','','','');
					$kepnev    = str_replace($mit,$mivel,$sor[$fajl_oszlop]);
					$overwrite = true;	//Megmondjuk a webftp-nek, hogy az adott fáljt írja felül, ne szemeteljünk, takarítsunk magunk után

				}else{//Ha nincs a táblában a neve (új felöltés vagy módosítás, és sose volt kép, vagy törölve lett)

					$kepnev = $_POST['nev'];
					if($uj_lista=='lista'){//Ha módosításkor nincs még kép hozzárendelve, akkor kap egy '_1'-t, hogy ne törölje az esetleges kepnev_2-t. Új feltöltéskor ezt a web_ftp() teszi meg

						$i=1;
						$ujnev=$tabla.'/'.$kepnev.'_1';
						while(glob('../'.get_utvonal().$ujnev.'*')){//DE! megnézzük, hogy ez a '_1' nem foglalt-e. Ha az keresünk szavad számot
							$i++;
							$ujnev=$kepnev.'_'.$i;
						}
						if($i==1 && $sor_id!=''){ $ujnev=$kepnev.'_1'; }
						$kepnev = $ujnev;

						$mit    = array($tabla.'/','.jpg','.gif','.png');
						$mivel  = array('','','','');

						$kepnev    = fajlnev_keszit(str_replace($mit,$mivel,$kepnev)).$kit;
						$overwrite = true;	//Megmondjuk a webftp-nek, hogy az adott fáljt írja felül, ne szemeteljünk, takarítsunk magunk után

					}else{//Ha új beszúrás, akkor gyártunk egy filename-t

						$kepnev    = fajlnev_keszit($kepnev).$kit;
						$overwrite = false;	//Ne töröljön esetleges azonos nevű fálokat, inább gyártsun újnevűeket új beszúrásnál

					}
				}
			break;

			default:
				$kit=strtolower(substr($_FILES[$megnevezes]['name'],strrpos($_FILES[$megnevezes]['name'],'.'),strlen($_FILES[$megnevezes]['name'])));//elmentjük a kiterjesztést, kisbetűsre alakítva
				$kepnev=$sor_id;

				//Ha nem képfájl, visszaadjuk a kiterjesztést
				if($kit!='.jpg' && $kit!='.gif' && $kit!='.png'){
					$kepnev .= $kit;
				}

				$algoritmus = 'sorid';//bizti kedvéért
				$sor_id     = null;//Kinullázzuk a táblát, hogy a web_ftp ne próbálkozzon SQL-el
				$overwrite  = true;	//Sorid esetén mindig töröljük az előzőt (pl. 'sorid'.jpg-t)
			break;
		}

//echo $tabla.'/'.$kepnev; exit();

		return web_ftp($megnevezes,$tabla.'/'.$kepnev,$meret,$overwrite,$csakkep,$tabla,$fajl_oszlop,$sor_id);
	}

}
?>
