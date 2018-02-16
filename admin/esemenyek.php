<?php
// Tömbök létrehozása

require_once('../weblap.php');

$_SESSION['defaultCsuszka'] = 400;

$disabled_szerk = false;
if (isset($_GET['modosit'])) {
    $disabled_szerk = true;
}

$_SESSION['ketHasabos'] = array(
    'anyagigenyles.php',
    'bejelentes.php',
    'bejelentes_arch.php',
    'bejovo_szamlak.php',
    'beosztas.php',
    'cash_flow.php',
    'cikktorzs.php',
    'dokumentumtar.php',
    'eloterj_fb.php',
    'eloterj_bod.php',
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
    'munkalap_arch.php',
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
$tabla_nophp = str_replace('.php', '', $tabla);

$_OPTS = array();
switch($tabla) {
    case 'anyagigenyles.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = nl2('
                                    `'.get_db_tabla().$tabla_nophp.'`.`recid`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`stat_id`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`ikt_szam`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`rndid`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`cikk_id`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`nev` as `anyagigenyles_nev`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`telephely_id`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`szallito_id`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`igmen`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`cikkmee`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`insert_dt`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`jhmen`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`rndmen`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`rndear`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`kcs_21`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`vrndear`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`verkear`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`rndid`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`rnd_dt`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`erkmen`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`erkear`,
                                    `'.get_db_tabla().'szallito`.`nev`
                                ');

        // Anyagrendeléskor más a rendezés
        if (isset($_GET['anyagrend'])) {
            // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
            $_OPTS['rendezes']  = array('rndid', 'stat_id', 'rnd_dt');
            $_OPTS['iranyok']   = array('desc',  'asc',     'desc'  );

            // Default rendezés
            if( !$_GET['rend'] ) {
                $_GET['rend'] = 'rndid';
            }

            // Default rendezési irány
            if( !$_GET['irany'] ) {
                $_GET['irany'] = 'desc';
            }
            
        // Anyagigénylés rendezése
        }else{
            // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
            $_OPTS['rendezes']  = array('stat_id', 'telephely_id', 'cikk_id', 'szallito_id');

            // Default rendezés
            if( !$_GET['rend'] ) {
                $_GET['rend'] = 'stat_id';
            }

            // Default rendezési irány
            if( !$_GET['irany'] ) {
                $_GET['irany'] = '';
            }
        }
    break;
    case 'anyagrendeles.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
                                    // V TÉNYLEG AZ ANYAGIGÉNLÉS TÁBLÁBÓL DOLGOZIK V
        $_OPTS['select']    = nl2('
                                    `'.get_db_tabla().'anyagigenyles`.`recid`,
                                    `'.get_db_tabla().'anyagigenyles`.`stat_id`,
                                    `'.get_db_tabla().'anyagigenyles`.`ikt_szam`,
                                    `'.get_db_tabla().'anyagigenyles`.`cikk_id`,
                                    `'.get_db_tabla().'anyagigenyles`.`nev` as `anyagigenyles_nev`,
                                    `'.get_db_tabla().'anyagigenyles`.`telephely_id`
                                ');

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('ikt_szam', 'telephely_id');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'ikt_szam';
        }

        // Default rendezési irány
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'bejelentes.php':
    case 'bejelentes_arch.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = nl2('
                                `'.get_db_tabla().$tabla_nophp.'`.`recid`,
                                `'.get_db_tabla().$tabla_nophp.'`.`ikt_szam`,
                                `'.get_db_tabla().$tabla_nophp.'`.`stat_id`,
                                `'.get_db_tabla().$tabla_nophp.'`.`surgos`,
                                `'.get_db_tabla().$tabla_nophp.'`.`telephely_id`,
                                `'.get_db_tabla().$tabla_nophp.'`.`csoport_id`,
                                `'.get_db_tabla().$tabla_nophp.'`.`obj_id`,
                                `'.get_db_tabla().$tabla_nophp.'`.`hiba_id`,
                                `'.get_db_tabla().$tabla_nophp.'`.`hiba_text`,
                                `'.get_db_tabla().$tabla_nophp.'`.`insert_dt`,
                                `'.get_db_tabla().'telephely`.`nev`,
                                `'.get_db_tabla().'obj`.`nev`,
                                `'.get_db_tabla().'erteklista`.`info`
                                ');

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('stat_id', 'ikt_szam', 'telephely_id',  'nev'   );
        $_OPTS['iranyok']   = array('desc',    'asc',      'asc',           'asc'   );

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'stat_id';
        }

        // Default rendezési irány
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'bejovo_szamlak.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = 'recid ikt_szam szallito targy netto statusz erkezett';

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('erkezett', 'e-szamla', 'torolve', 'ikt_szam', 'statusz');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'erkezett';
        }

        // Default rendezési irány
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'beosztas.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = nl2('
                                `'.get_db_tabla().$tabla_nophp.'`.`recid`,
                                `'.get_db_tabla().$tabla_nophp.'`.`cim`,
                                `'.get_db_tabla().$tabla_nophp.'`.`telephely_id`,
                                `'.get_db_tabla().$tabla_nophp.'`.`tol`,
                                `'.get_db_tabla().$tabla_nophp.'`.`ig`,
                                `'.get_db_tabla().'telephely`.`nev`
                            ');

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('telephely_id');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'telephely_id';
        }
    break;
    case 'cash_flow.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = 'recid';

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('recid');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'recid';
        }

        // Default rendezési irány
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'cikktorzs.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = 'nev recid id';

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('id', 'nev');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'id';
        }
    break;
    case 'dokumentumtar.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = '';

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('id', 'nev');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'id';
        }
    break;
    case 'eloterj_fb.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = 'recid, ikt_szam, stat_id, nev';

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('ikt_szam', 'stat_id');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'stat_id';
        }

        // Default rendezési irány
        if( !$_GET['irany'] ) {
            $_GET['irany'] = '';
        }
    break;
    case 'eloterj_bod.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = 'recid, ikt_szam, stat_id, nev';

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('ikt_szam', 'stat_id');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'stat_id';
        }

        // Default rendezési irány
        if( !$_GET['irany'] ) {
            $_GET['irany'] = '';
        }
    break;
    case 'energia.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = 'recid';

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('recid');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'recid';
        }

        // Default rendezési irány
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'erteklista.php':
        $_OPTS['select'] = 'info'; //Függvényeknél, szamla_lista()-nál is van vele varia beállítva

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('csoport');

        // Default rendezés //Függvényeknél, szamla_lista()-nál beállítva

    break;
    case 'eseti_fenntartas.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = nl2('
                                    `'.get_db_tabla().$tabla_nophp.'`.`recid`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`id`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`nev` as `uf_nev`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`telephely_id`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`obj_id`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`aktiv`,
                                    `'.get_db_tabla().'telephely`.`nev`,
                                    `'.get_db_tabla().'obj`.`nev`
                                ');


        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('id', 'aktiv', 'telephely_id', 'esnap_d');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'aktiv';
        }

        // Default rendezési irány
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'eszkozkezeles.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = 'recid nev';

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('nev');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'nev';
        }
    break;
    case 'felmeresi_terv.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = 'recid';

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('recid');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'recid';
        }

        // Default rendezési irány
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'fenntartasi_szerzodes.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = 'recid';

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('recid');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'recid';
        }

        // Default rendezési irány
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'ktghely.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = 'recid id nev';

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('id', 'nev');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'id';
        }

        // Default rendezési irány
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'asc';
        }
    break;
    case 'ktgnem.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = 'recid id nev';

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('id', 'nev');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'id';
        }

        // Default rendezési irány
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'asc';
        }
    break;
    case 'login.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = nl2('
                                    `'.get_db_tabla().$tabla_nophp.'`.`recid`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`nev`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`teljes_nev`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`aktiv`
                                ');

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('teljes_nev', 'aktiv', 'szerepkor_id');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'teljes_nev';
        }

        // Default rendezési irány
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'asc';
        }
    break;
    case 'munkalap.php':
    case 'munkalap_arch.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = nl2('
                                `'.get_db_tabla().$tabla_nophp.'`.`recid`,
                                `'.get_db_tabla().$tabla_nophp.'`.`ikt_szam`,
                                `'.get_db_tabla().$tabla_nophp.'`.`stat_id`,
                                `'.get_db_tabla().$tabla_nophp.'`.`telephely_id`,
                                `'.get_db_tabla().$tabla_nophp.'`.`csoport_id`,
                                `'.get_db_tabla().$tabla_nophp.'`.`surgos`,
                                `'.get_db_tabla().$tabla_nophp.'`.`tipus_id`,
                                `'.get_db_tabla().$tabla_nophp.'`.`obj_id`,
                                `'.get_db_tabla().$tabla_nophp.'`.`gep_id`,
                                `'.get_db_tabla().$tabla_nophp.'`.`szemelyzet_id`,
                                `'.get_db_tabla().$tabla_nophp.'`.`szallito_id`,
                                `'.get_db_tabla().'munkalap_csoport`.`nev`
                            ');

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('ikt_szam', 'telephely_id', 'stat_id');
        $_OPTS['iranyok']   = array('asc',      'asc',          'desc'   );

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'stat_id';
        }

        // Default rendezési irány
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'utemezett_feladatok.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = nl2('
                                    `'.get_db_tabla().$tabla_nophp.'`.`recid`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`id`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`nev` as `uf_nev`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`telephely_id`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`obj_id`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`aktiv`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`esnap_d`,
                                    `'.get_db_tabla().'telephely`.`nev`,
                                    `'.get_db_tabla().'obj`.`nev`
                                ');


        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('id',   'aktiv', 'telephely_id', 'esnap_d');
        $_OPTS['iranyok']   = array('desc', 'desc',  'asc',          'asc'    );

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'aktiv';
        }

        // Default rendezési irány
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'uzemeltetesi_naplo.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = 'recid ';

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('recid');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'recid';
        }

        // Default rendezési irány
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'torzsadat.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = 'recid nev';

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('nev');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'nev';
        }
    break;
    case 'raktar.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = 'recid';

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('recid');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'recid';
        }

        // Default rendezési irány
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'rendszeres_karbantartas.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = nl2('
                                    `'.get_db_tabla().$tabla_nophp.'`.`recid`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`id`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`nev` as `uf_nev`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`telephely_id`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`obj_id`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`aktiv`,
                                    `'.get_db_tabla().'telephely`.`nev`,
                                    `'.get_db_tabla().'obj`.`nev`
                                ');


        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('id', 'aktiv', 'telephely_id', 'esnap_d');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'aktiv';
        }

        // Default rendezési irány
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'tervezes.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = 'recid';

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('recid');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'recid';
        }

        // Default rendezési irány
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'projektigenyles.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = nl2('
                                    `'.get_db_tabla().$tabla_nophp.'`.`recid`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`ikt_szam`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`nev`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`bkolt`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`tipus`,
                                    `'.get_db_tabla().$tabla_nophp.'`.`rendk`,
                                    `stat_id`,
                                    `telephely_id`,
                                    `csoport_id`
                                    ');

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('ikt_szam', 'stat_id');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'stat_id';
        }

        // Default rendezési irány
        if( !$_GET['irany'] ) {
            $_GET['irany'] = '';
        }
    break;
    case 'projektkezeles.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = 'recid ikt_szam nev stat_id';

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('recid');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'recid';
        }

        // Default rendezési irány
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'rendszer.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = 'recid nev rkat aktiv';

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('nev');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'nev';
        }

        // Default rendezési irány
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'asc';
        }
    break;
    case 'szabadsagigeny.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = 'recid ';

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('recid');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'recid';
        }

        // Default rendezési irány
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'szallito.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = 'recid nev aktiv';

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('nev', 'id');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'nev';
        }

        // Default rendezési irány
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'asc';
        }
    break;
    case 'szamla.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = 'recid';

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('recid');

        // Default rendezés
        if( !$_GET['rend'] ) {
            $_GET['rend'] = 'recid';
        }

        // Default rendezési irány
        if( !$_GET['irany'] ) {
            $_GET['irany'] = 'desc';
        }
    break;
    case 'szerepkor.php':
        // Ezeket a tábla oszlopokat kérdezzük le baloldalra
        $_OPTS['select']    = 'recid nev';

        // Ezek az oszlopok generálódnak ki a rendezés selectbe, lehet szépíteni az oszlopneveket, amit a getOszlopNev() függvényben kell beállítani
        $_OPTS['rendezes']  = array('nev');

        // Default rendezés
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
//  array('type' => 'textarea',    'megn' => 'Tartalom:',  'name' => 'text',        'class' => 'oriasi'/*,  'limit' => 500/*Limit wysiwyg-el nem kombinálható!*/ /*, 'wysiwyg' => 'Alap'/*Min vagy Full*/, 'feltoltes' => 'Images', 'width' => '100%', 'height' => '200'),
//  array('type' => 'text',        'megn' => 'Listaár:',   'name' => 'ar',          'class' => 'kicsi', 'maxlength' => 10, 'ezresit' => true, 'post' => ' '.get_penznem()),
//  array('type' => 'file',        'megn' => 'Nyitókép:',  'name' => 'hir_kep',     'oszlop' => 'kep', 'algoritmus' => 'nev | fajlnev | id', 'fajl_torlessel' => true),
//  array('type' => 'jsfile',      'megn' => 'Nyitókép:',  'name' => 'termek_kep',  'tabla' => $megnevezes,  'oszlop' => 'kep',   'algoritmus' => 'nev',   'meret' => 4194304, 'csakkep'  => true, 'fajlok_szama'  => 1),
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
//    array('type' => 'jsfile',      'megn' => 'Nyitókép:',   'name' => 'termek_kep',    'tabla' => $megnevezes,  'oszlop' => 'kep',   'algoritmus' => 'nev',  'meret' => 1572864, 'csakkep'  => false, 'fajlok_szama'  => 1),
//	array('type' => 'text',        'value' => $datum,       'megn' => 'Dátum:',        'name' => 'datetime',    'class' => 'normal datepicker'),
//    array('type' => 'textarea',    'megn' => 'Tartalom:',  'name' => 'text',        'class' => 'oriasi'/*,  'limit' => 500/*Limit wysiwyg-el nem kombinálható!*/ /*, 'wysiwyg' => 'Alap'/*Min vagy Full*/, 'feltoltes' => 'Images', 'width' => '100%', 'height' => '200'),
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

// Bejövő számlák
$megnevezes = 'bejovo_szamlak';
$bejovo_szamlak_tomb = $akt_tomb = array(
    array('type' => 'text',                 'megn' => 'Iktató szám:',        'name' => 'ikt_szam',       'disabled' => $disabled,   'maxlength' => 250, 'class' => 'normal', 'osszevon' => true),
    array('type' => 'text',                 'megn' => 'Érkezett:',           'name' => 'erkezett',       'disabled' => $disabled,   'class' => 'kicsi datepicker', 'value' => $datum, 'alert' => 'Kérem töltse ki az érkezett dátumot!', 'osszevon' => true),
    array('type' => 'text',                 'megn' => 'Visszaadva:',         'name' => 'visszaadva',     'disabled' => $disabled,   'class' => 'kicsi datepicker', 'value' => $datum),
    array('type' => 'text',                 'megn' => 'Számla szám:',        'name' => 'szaml_szam',     'disabled' => $disabled,   'maxlength' => 250, 'class' => 'nagy', 'alert' => 'Kérem töltse ki a számlaszámot!', 'osszevon' => true),
    array('type' => 'checkbox',             'megn' => 'E-számla:',           'name' => 'e-szamla',       'disabled' => $disabled,   'class' => 'check', 'osszevon' => true),
    array('type' => 'checkbox',             'megn' => 'Törölve:',            'name' => 'torolve',        'disabled' => $disabled,   'class' => 'check'),
    array('type' => 'autocomplete',         'megn' => 'Szállító:',           'name' => 'szallito_id',    'disabled' => $disabled,   'class' => 'oriasi disableNav', 'tabla' => 'szallito', 'oszlop_keres' => 'nev', 'listaz' => 'nev'),
    array('type' => 'text',                 'megn' => 'Szállító szabadon:',  'name' => 'szallito_szab',  'disabled' => $disabled,   'maxlength' => 250,  'class' => 'oriasi'),
    array('type' => 'text',                 'megn' => 'Teljesítés:',         'name' => 'teljesites',     'disabled' => $disabled,   'class' => 'kicsi datepicker', 'value' => $datum, 'alert' => 'Kérem töltse ki a teljesítési dátumot!', 'osszevon' => true),
    array('type' => 'text',                 'megn' => 'Fiz. hat.:',          'name' => 'fiz_hat',        'disabled' => $disabled,   'class' => 'kicsi datepicker', 'value' => $datum, 'osszevon' => true),
    array('type' => 'select_fiz_mod',       'megn' => 'Fiz. mód:',           'name' => 'fiz_mod',        'disabled' => $disabled,   'class' => 'disableNav'),
    array('type' => 'textarea',             'megn' => 'Tárgy:',              'name' => 'targy',          'disabled' => $disabled,   'class' => 'oriasiTargy disableNav'/*,  'limit' => 500/*Limit wysiwyg-el nem kombinálható!*/ /*, 'wysiwyg' => 'Alap'/*Min vagy Full*/, 'feltoltes' => 'Images', 'width' => '100%', 'height' => '150'),
    array('type' => 'text',                 'megn' => 'Bruttó:',             'name' => 'brutto',         'disabled' => $disabled,   'class' => 'kicsi', 'maxlength' => 10, 'ezresit' => true, 'post' => ' '.get_penznem(), 'osszevon' => true, 'onkeyup' => 'onlyNumbers( $(this) );'),
    array('type' => 'text',                 'megn' => 'Nettó:',              'name' => 'netto',          'disabled' => $disabled,   'class' => 'kicsi', 'maxlength' => 10, 'ezresit' => true, 'post' => ' '.get_penznem(), 'osszevon' => true, 'onkeyup' => 'onlyNumbers( $(this) );'),
    array('type' => 'select_afa_tipus',     'megn' => 'Áfa típus:',          'name' => 'fiz_mod',        'disabled' => $disabled,   'class' => 'disableNav'),
    array('type' => 'text',                 'megn' => 'Valuta Bruttó:',      'name' => 'valuta_brutto',  'disabled' => $disabled,   'class' => 'kicsi', 'maxlength' => 20, 'osszevon' => true, 'onkeyup' => 'onlyNumbers( $(this) );'),
    array('type' => 'text',                 'megn' => 'Valuta nettó:',       'name' => 'valuta_netto',   'disabled' => $disabled,   'class' => 'kicsi', 'maxlength' => 20, 'osszevon' => true, 'onkeyup' => 'onlyNumbers( $(this) );'),
    array('type' => 'text',                 'megn' => 'Árfolyam:',           'name' => 'arfolyam',       'disabled' => $disabled,   'class' => 'kicsi', 'maxlength' => 20),
    array('type' => 'text',                 'megn' => 'Részleg:',            'name' => 'reszleg',        'disabled' => $disabled,   'class' => 'normal disableNav', 'osszevon' => true),
    array('type' => 'text',                 'megn' => 'Átváltva:',           'name' => 'atvaltva',       'disabled' => $disabled,   'class' => 'kicsi datepicker', 'value' => $datum),
    array('type' => 'textarea',             'megn' => 'Megjegyzés:',         'name' => 'megj',           'disabled' => $disabled,   'class' => 'oriasiTargy disableNav'/*,  'limit' => 500/*Limit wysiwyg-el nem kombinálható!*/ /*, 'wysiwyg' => 'Alap'/*Min vagy Full*/, 'feltoltes' => 'Images', 'width' => '100%', 'height' => '150'),
    array('type' => 'select_eszk_csop',     'megn' => 'Eszköz csoport:',     'name' => 'eszkoz_csop',    'disabled' => $disabled,   'class' => 'disableNav',   'osszevon' => true),
    array('type' => 'text',                 'megn' => 'Eszköz érték:',       'name' => 'eszkoz_ert',     'disabled' => $disabled,   'class' => 'kicsi', 'maxlength' => 10, 'ezresit' => true, 'post' => ' '.get_penznem()),
    array('type' => 'text',                 'megn' => 'Rendelés szám:',      'name' => 'rend_szam',      'disabled' => $disabled,   'maxlength' => 250, 'class' => '', 'osszevon' => true),
    array('type' => 'select_cimzett',       'megn' => 'Címzett:',            'name' => 'cimzett',        'disabled' => $disabled,   'class' => 'disableNav'),
    array('type' => 'text',                 'megn' => 'Bev. szám:',          'name' => 'bev_szam',       'disabled' => $disabled,   'maxlength' => 250, 'class' => '', 'osszevon' => true),
    array('type' => 'select_ill_reszleg',   'megn' => 'Illetékes részleg:',  'name' => 'ill_reszleg',    'disabled' => $disabled,   'class' => 'disableNav'),

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
    array('type' => 'jsfile',               'megn' => 'Fájlok:',  'name' => 'termek_kep',  'tabla' => $megnevezes,  'oszlop' => 'kep_teszt',   'algoritmus' => 'fajlnev',   'meret' => 31457280, 'csakkep'  => false, 'fajlok_szama'  => 5),
);

// Kimeno számlák
$megnevezes = 'kimeno_szamlak';
$kimeno_szamlak_tomb = $akt_tomb = array(
	array('type' => 'text',    'megn' => 'Név:',   'name' => 'nev',    'disabled' => $disabled,   'maxlength' => 250, 'class' => 'oriasi', 'alert' => 'Kérem töltse ki a megnevezést!'),

	//array('type' => 'submit', 'value' => 'Mentés',    'name' => 'mehet_gomb', 'class' => 'gombb'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));

// Rendszer
$megnevezes = 'ktghely';
$ktghely_tomb = $akt_tomb = array(
    array('type' => 'text',         'megn' => 'ID:',             'name' => 'id',      'disabled' => $csakUjkent,    'alert' => 'Kérem töltse ki az ID-t!', 'onblur' => 'id_foglalt_e(\''.$megnevezes.'.php\', $(this))', 'maxlength' => 7, 'style' => 'width: 7rem'),
    array('type' => 'text',         'megn' => 'Érték:',          'name' => 'nev',     'maxlength' => 250, 'class' => 'oriasi', 'alert' => 'Kérem töltse ki a megnevezést!',  'show' => true),

	//array('type' => 'submit', 'value' => 'Mentés',    'name' => 'mehet_gomb', 'class' => 'gombb'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));

// Rendszer
$megnevezes = 'ktgnem';
$ktgnem_tomb = $akt_tomb = array(
    array('type' => 'text',         'megn' => 'ID:',             'name' => 'id',      'disabled' => $csakUjkent,    'onblur' => 'id_foglalt_e(\''.$megnevezes.'.php\', $(this))', 'alert' => 'Kérem töltse ki az ID-t!', 'maxlength' => 7, 'style' => 'width: 7rem'),
    array('type' => 'text',         'megn' => 'Érték:',          'name' => 'nev',     'maxlength' => 250, 'class' => 'oriasi', 'alert' => 'Kérem töltse ki a megnevezést!',  'show' => true),

	//array('type' => 'submit', 'value' => 'Mentés',    'name' => 'mehet_gomb', 'class' => 'gombb'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));

// Log
$megnevezes = 'log';
$log_tomb = $akt_tomb = array(
	array('type' => 'text',            'megn' => 'Felh:',     'name' => 'felh_id',   'maxlength' => 250, 'class' => 'oriasi', 'kulso_tabla' => 'login|nev'),
	array('type' => 'text',            'megn' => 'Művelet:',  'name' => 'nev',       'maxlength' => 250, 'class' => 'oriasi', 'show' => true),
    array('type' => 'textarea',        'megn' => 'Változás:', 'name' => 'valtozas',  'class' => 'oriasi disableNav'/*,  'limit' => 500/*Limit wysiwyg-el nem kombinálható!*/, 'wysiwyg' => 'Min2'/*Min vagy Full*/, 'feltoltes' => 'Images', 'width' => '100%', 'height' => '200'),
    array('type' => 'text',            'megn' => 'Dátum:',    'name' => 'datetime',  'class' => 'normal datepicker', 'value' => '',/*$datum*/ 'show' => true),

	//array('type' => 'submit', 'value' => 'Mentés',    'name' => 'mehet_gomb', 'class' => 'gombb'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));

// Szállítók
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


// Kódtár
$megnevezes = 'erteklista';
$erteklista_tomb = $akt_tomb = array(
	//array('type' => 'text',             'megn' => 'ID:',         'name' => 'id',    'maxlength' => 250, 'class' => 'kicsi',     'alert' => 'Kérem töltse ki az ID-t!'),
    array('type' => 'select_erteklista', 'megn' => 'Csoport:',    'name' => 'csoport',    'disabled' => $csakUjkent,   'class' => 'disableNav',    'alert' =>  $szoveg_reg['varos'][$megsz],    'alert' => 'Kérem válassza ki a csoportot!'),
	array('type' => 'text',              'megn' => 'Érték:',      'name' => 'info',       'maxlength' => 40, 'class' => 'oriasi',    'alert' => 'Kérem töltse ki az értéket!'),

	//array('type' => 'submit', 'value' => 'Mentés',    'name' => 'mehet_gomb', 'class' => 'gombb'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));

// Felhasználók
$disabled_uj    = false;
if (isset($_GET['uj'])) {
    $disabled_uj = true;
}
$megnevezes = 'login';
$login_tomb = $akt_tomb = array(
    array('type' => 'checkbox',             'megn' => 'Aktív felhasználó:',         'name' => 'aktiv',          'class' => 'check',             'szuro' => 1, 'post' => '<span style="margin-right: 4rem"></span>',   'osszevon' => true),
    array('type' => 'checkbox',             'megn' => 'Virtuális felhasználó:',     'name' => 'virtual',        'class' => 'check',             'szuro' => 1),
    array('type' => 'text',                 'megn' => 'Login név:',                 'name' => 'nev',            'class' => 'f_loginnev',        'szuro' => 1, 'disabled' => $disabled_szerk,  'maxlength' => 30, 'alert' => 'Kérem töltse ki a login nevet!', 'style' => 'width: 21.5rem'),
    array('type' => 'text',                 'megn' => 'Név:',                       'name' => 'teljes_nev',     'class' => 'oriasi',            'szuro' => 1,                 'maxlength' => 250, 'alert' => 'Kérem töltse ki a nevet!'),
    array('type' => 'checkbox',             'megn' => 'Jelszóval rendelkezik:',     'name' => 'van_jelszo',     'class' => 'check',             'szuro' => 1, 'disabled' => $disabled_uj),
    array('type' => 'autocomplete',         'megn' => 'Beosztás:',                  'name' => 'beosztas_id',    'class' => 'oriasi disableNav', 'szuro' => 1, 'tabla' => 'erteklista', 'oszlop_keres' => 'id', 'oszlop_keres_autocomp' => 'info', 'listaz' => 'info', 'csoport' => 18, 'id_kiir' => 'id', 'alert' => 'Kérem adja meg a beosztást!'),
    array('type' => 'autocomplete',         'megn' => 'Részleg:',                   'name' => 'reszleg_id',     'class' => 'oriasi disableNav', 'szuro' => 1, 'tabla' => 'erteklista', 'oszlop_keres' => 'id', 'oszlop_keres_autocomp' => 'info', 'listaz' => 'info', 'csoport' => 17, 'id_kiir' => 'id', 'alert' => 'Kérem adja meg a részleget!'),
    array('type' => 'select_szemelyzet',    'megn' => 'Szerepkör:',                 'name' => 'szemelyzet_id',  'class' => 'disableNav',        'szuro' => 1, 'alert' => 'Kérjük adja meg a szerepkört!'),
    array('type' => 'text',                 'megn' => 'E-mail:',                    'name' => 'usermail',       'class' => 'oriasi',            'szuro' => 1, 'maxlength' => 250),
    // array('type' => 'checkbox',             'megn' => 'Kap értesítést:',            'name' => 'kap_mailt',      'class' => 'check',             'szuro' => 1, 'disabled' => $disabled_uj),
    array('type' => 'text',                 'megn' => 'Telefon:',                   'name' => 'usertel',        'class' => 'oriasi',            'szuro' => 1, 'maxlength' => 250, 'alert' => 'Kérjük adja meg a telefonszámot!'),
    //array('type' => 'autocomplete',         'megn' => 'Szerepkör:',                 'name' => 'szerepkor_id',   'class' => 'oriasi disableNav', 'szuro' => 1, 'tabla' => 'szerepkor', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'alert' => 'Kérem adja meg a szerepkört!'),
    array('type' => 'autocomplete',         'megn' => 'Partner cég:',               'name' => 'szallito_id',    'szuro' => 1,   'disabled' => 1,   'class' => 'oriasi disableNav', 'tabla' => 'szallito', 'oszlop_keres' => 'id', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'id_kiir' => 'id'),

	//array('type' => 'submit', 'value' => 'Mentés',    'name' => 'mehet_gomb', 'class' => 'gombb'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));


// Szerepkör
$megnevezes = 'szerepkor';
$szerepkor_tomb = $akt_tomb = array(
	array('type' => 'text',        'megn' => 'Név:',        'name' => 'nev',   'maxlength' => 250, 'alert' => 'Kérem töltse ki a nevet!', 'class' => 'oriasi', 'disabled' => 1),

	//array('type' => 'submit', 'value' => 'Mentés',    'name' => 'mehet_gomb', 'class' => 'gombb'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));


// Bejelentés
$megnevezes = 'bejelentes';
if( basename(self())=='bejelentes_arch.php' ){
    $megnevezes = 'bejelentes_arch';
}

// Felhasználó lekérdezése a bejelentés táblához
// Új feltöltésnél ha van get bejelentőm
if( isset($_GET['uj']) && $_GET['bejelento'] ) {
    $bejelento = $adatbazis_kapcsolat->s2('teljes_nev beosztas_id reszleg_id usertel usermail', 'login', "`nev`='{$_GET['bejelento']}'");
// Módosítánál a GET['id']-ból visszaszámolva
} else if( isset($_GET['modosit']) && $_GET['id'] ) {
    $loginNev = $adatbazis_kapcsolat->s2('loginnev', $megnevezes, 'recid='.$_GET['id'], '', '1');
    $loginNev = $loginNev['loginnev'];

    if( $loginNev ) {
        $bejelento = $adatbazis_kapcsolat->s2('teljes_nev beosztas_id reszleg_id usertel usermail', 'login', "`nev`='{$loginNev}'");
    }
// Új feltöltés, de nincs $_GET['bejelento']-nk, ekkor $_SESSION-ből saját magunk vagyunk a bejelentők
} elseif( isset($_GET['uj']) ) {
    $bejelento = $adatbazis_kapcsolat->s2('teljes_nev beosztas_id reszleg_id usertel usermail', 'login', "`recid`='{$_SESSION[get_db_tabla().'login_recid']}'");
}

// Ha manuális kitöltés szeretnénk, akkor semmiképp ne legyen benne a bejelentő adata
if( isset($_GET['manualisan']) ) {
    $bejelento = '';
}

if (isset($_GET['uj'])) {
    $bejelentest_rogziti = $adatbazis_kapcsolat->s2('teljes_nev beosztas_id reszleg_id usertel usermail', 'login', 'recid='.$_SESSION[get_db_tabla().'login_recid']);
}

// Sűrgősség pipa
$surgos_disabled = '';
$surgos_onclick  = '';
if (isset($_GET['modosit'])) {

    $surgos_disabled = true;

    $bejelentes = $adatbazis_kapcsolat->s2('stat_id', 'bejelentes', "`recid`='{$_GET['id']}'");
    if( $bejelentes['stat_id']==BEJELENTES_STAT_NYITOTT && ( _jog(JOG_BEJELENTES_KEZEL) || _jog(JOG_BEJELENTES_MAS_NEVEBEN) ) ) {
        $surgos_disabled = false;
        $surgos_onclick  = 'csoportMuvelet(\'surgosseg_indoklas\')';
    }
}

$usr_nev        = '';
$usr_beosztas   = '';
$usr_reszleg    = '';
$usr_telefon    = '';
$usr_email      = '';
if( isset($_GET['uj']) ) {
    $usr_nev        = $bejelento['teljes_nev'];
    $usr_beosztas   = $bejelento['beosztas_id']; //erteklista(18, $user['beosztas_id']);
    $usr_reszleg    = $bejelento['reszleg_id'];  //erteklista(17, $user['reszleg_id']);
    $usr_telefon    = $bejelento['usertel'];
    $usr_email      = $bejelento['usermail'];
}

$usr_dis = true;
if( isset($_GET['manualisan']) ) {
    $usr_dis = false;
}
$szalloda_dis  = false;
$szalloda_post = '';
if( isset($_GET['bejelento']) ) {
    $szalloda_dis  = true;
    $szalloda_post = '<input type="hidden" name="telephely_id" value="'.$_GET['telephely'].'">';
}

// Bejelentések
$bejelentes_tomb = $bejelentes_arch_tomb = $akt_tomb = array(
    array('type' => 'statusz',         'megn' => 'Státusz:',            'name' => 'stat_id',    'szuro' => 1,  'oldal' => $megnevezes),
	array('type' => 'text',            'megn' => 'Bejelentés szám:',    'name' => 'ikt_szam',   'szuro' => 1,  'disabled' => true,              'maxlength' => 250, 'osszevon' => 1, 'pre' => '<div class="dib">', 'post' => '</div><span style="margin-right: 4rem"></span>'),
    array('type' => 'checkbox',        'megn' => 'Sürgős',              'name' => 'surgos',     'szuro' => 1,  'disabled' => $surgos_disabled,  'class' => 'check', 'onclick' => $surgos_onclick),

    array('type' => 'html',            'megn' => nl2('
                                                        <div class="dtr">
                                                            <div class="dtc">

                                                            </div>
                                                            <div class="dtc" style="padding-top: 2rem; min-width: 43rem">
                                                                <div class="dib c" style="width: 26%; min-width: 21rem; padding-top: 1rem">Bejelentő</div>
                                                                <div class="dib c" style="width: 26%; min-width: 21rem; padding-top: 1rem">Bejelentést rögzítő</div>
                                                            </div>
                                                        </div>
                                                    ')),
    array('type' => 'text',            'megn' => 'Név:',        'megn_szurobe' => 'Név:',                   'name' => 'usr_nev',                'disabled' => $usr_dis,     'maxlength' => 250, 'class' => 'kozepes', 'szuro' => 1, 'osszevon' => true, 'letarol' => true,  'value' => $usr_nev, 'alert' => 'Kérem adja meg a bejelentő nevét!'),
    array('type' => 'text',            'megn' => '',            'megn_szurobe' => 'Rögzítő név:',           'name' => 'usr_man_nev',            'disabled' => 1,            'maxlength' => 250, 'class' => 'kozepes', 'szuro' => 1, 'value' => $bejelentest_rogziti['teljes_nev']),
    array('type' => 'autocomplete',    'megn' => 'Beosztás:',   'megn_szurobe' => 'Rögzítő beosztás:',      'name' => 'usr_beosztas_id',        'disabled' => $usr_dis,     'class' => 'kozepes disableNav', 'szuro' => 1, 'tabla' => 'erteklista', 'oszlop_keres' => 'id', 'oszlop_keres_autocomp' => 'info', 'listaz' => 'info', 'csoport' => 18, 'id_kiir' => 'id', 'osszevon' => true, 'value' => $usr_beosztas),
    array('type' => 'autocomplete',    'megn' => '',            'megn_szurobe' => 'Beosztás:',              'name' => 'usr_man_beosztas_id',    'disabled' => 1,            'class' => 'kozepes disableNav', 'szuro' => 1, 'tabla' => 'erteklista', 'oszlop_keres' => 'id', 'oszlop_keres_autocomp' => 'info', 'listaz' => 'info', 'csoport' => 18, 'id_kiir' => 'id', 'value' => $bejelentest_rogziti['beosztas_id']),
    array('type' => 'autocomplete',    'megn' => 'Részleg:',    'megn_szurobe' => 'Rögzítő részleg:',       'name' => 'usr_reszleg_id',         'disabled' => $usr_dis,     'class' => 'kozepes disableNav', 'szuro' => 1, 'tabla' => 'erteklista', 'oszlop_keres' => 'id', 'oszlop_keres_autocomp' => 'info', 'listaz' => 'info', 'csoport' => 17, 'id_kiir' => 'id', 'osszevon' => true, 'value' => $usr_reszleg, 'alert' => 'Kérem adja meg a bejelentő részlegét!'),
    array('type' => 'autocomplete',    'megn' => '',            'megn_szurobe' => 'Részleg:',               'name' => 'usr_man_reszleg_id',     'disabled' => 1,            'class' => 'kozepes disableNav', 'szuro' => 1, 'tabla' => 'erteklista', 'oszlop_keres' => 'id', 'oszlop_keres_autocomp' => 'info', 'listaz' => 'info', 'csoport' => 17, 'id_kiir' => 'id', 'value' => $bejelentest_rogziti['reszleg_id']),
    array('type' => 'text',            'megn' => 'Telefon:',    'megn_szurobe' => 'Rögzítő telefon:',       'name' => 'usr_telefon',            'disabled' => $usr_dis,     'maxlength' => 250, 'class' => 'kozepes', 'szuro' => 1, 'osszevon' => true, 'letarol' => true,  'value' => $usr_telefon),
    array('type' => 'text',            'megn' => '',            'megn_szurobe' => 'Telefon:',               'name' => 'usr_man_telefon',        'disabled' => 1,            'maxlength' => 250, 'class' => 'kozepes', 'szuro' => 1, 'value' => $bejelentest_rogziti['usertel']),
    array('type' => 'text',            'megn' => 'E-mail:',     'megn_szurobe' => 'Rögzítő e-mail:',        'name' => 'usr_email',              'disabled' => $usr_dis,     'maxlength' => 250, 'class' => 'kozepes', 'szuro' => 1, 'osszevon' => true, 'letarol' => true, 'value' => $usr_email),
    array('type' => 'text',            'megn' => '',            'megn_szurobe' => 'E-mail:',                'name' => 'usr_man_email',          'disabled' => 1,            'maxlength' => 250, 'class' => 'kozepes', 'szuro' => 1, 'value' => $bejelentest_rogziti['usermail']),

    array('type' => 'html',            'megn' => nl2('
                                                        <div class="dtr">
                                                            <div class="dtc">
                                                            </div>
                                                            <div class="dtc" style="padding-bottom: 2rem">
                                                            </div>
                                                        </div>
                                                    ')),

    array('type' => 'select_felh_telephely',   'megn' => 'Szálloda:',   'name' => 'telephely_id', 'szuro' => 1,  'class' => 'disableNav', 'style' => 'width: 100%;', 'alert' => 'Kérem válasszon szállodát!', 'onchange' => 'szallodaVane( this );', 'disabled' => $szalloda_dis, 'post' => $szalloda_post),

    array('type' => 'autocomplete',    'megn' => 'Bejelentett hiba:',   'name' => 'hiba_id',      'szuro' => 1,  'class' => 'oriasi disableNav', 'tabla' => 'erteklista', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'info', 'listaz' => 'info', 'csoport' => 4, 'alert' => 'Kérem adja meg a bejelentett hibát!'),
    array('type' => 'textarea',        'megn' => 'Hiba leírása:',       'name' => 'hiba_text',    'szuro' => 1,  'disabled' => $disabled,    'class' => 'oriasiTargy disableNav'/*,  'limit' => 500/*Limit wysiwyg-el nem kombinálható!*/ /*, 'wysiwyg' => 'Alap'/*Min vagy Full*/, 'feltoltes' => 'Images', 'width' => '100%', 'height' => '150'),

    array('type' => 'autocomplete',    'megn' => 'Helyiség:',           'name' => 'obj_id',       'szuro' => 1,  'class' => 'disableNav', 'style' => 'width: calc(100% - 3rem);', 'tabla' => 'obj', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'id_kiir' => 'recid', 'alert' => 'Kérem adja meg a Helyiséget!', 'onclick' => 'szallodaVane( this );', 'post' => '<a href="javascript: obj_gen(); void(0);" class="obj_gen"><i class="material-icons">reorder</i></a>'),

    array('type' => 'textarea',        'megn' => 'Hely leírása:',       'name' => 'helyiseg_text','szuro' => 1,  'class' => 'oriasiTargy disableNav'/*,  'limit' => 500/*Limit wysiwyg-el nem kombinálható!*/ /*, 'wysiwyg' => 'Alap'/*Min vagy Full*/, 'feltoltes' => 'Images', 'width' => '100%', 'height' => '150'),
    array('type' => 'autocomplete',    'megn' => 'Gépnév:',             'name' => 'gep_id',       'szuro' => 1,  'class' => 'oriasi disableNav', 'tabla' => 'eszkozkezeles', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'onclick' => 'helyisegVane( this );'),

    array('type' => 'checkbox',        'megn' => '',   'megn_szurobe' => 'Telefonon bejelentve',    'name' => 'telefon',      'szuro' => 1,  'class' => 'check', 'post' => ' közvetlenül telepített karbantartónak telefonon bejelentve'),
    array('type' => 'nosql',           'name' => '',   'megn_szurobe' => 'Bejelentés dátuma',       'name' => 'insert_dt',    'szuro' => 1,  'class' => 'datetimepicker'),
    array('type' => 'nosql',           'name' => 'masnevebenFeltolt'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));
if (isset($_GET['uj'])) {
    array_push($bejelentes_tomb, array('type' => 'jsfile', 'megn' => 'Dokumentumok<br>hozzáadása:',  'name' => 'termek_kep',  'tabla' => $megnevezes,  'oszlop' => 'bejelentesUjFeltoltes',   'algoritmus' => 'fajlnev',   'meret' => 31457280, 'csakkep'  => false, 'sablon_post'  => '_bejelentes') );
}

$bejelentes_dok_tomb = $akt_tomb = array(
    array('type' => 'jsfile',               'megn' => 'Fájlok:',  'name' => 'termek_kep',  'tabla' => $megnevezes,  'oszlop' => 'kep_teszt',   'algoritmus' => 'fajlnev',   'meret' => 31457280, 'csakkep'  => false, 'fajlok_szama'  => 5),
);


// Munkalap tömb
$megnevezes = 'munkalap';
if( basename(self())=='munkalap_arch.php' ){
    $megnevezes = 'munkalap_arch';
}
$surgos_disabled = '';
$surgos_onclick  = '';
if(isset($_GET['modosit'])){

    $munkalap = $adatbazis_kapcsolat->s2('szemelyzet_id mobil_loginnev stat_id mobil', $megnevezes, "`recid`='{$_GET['id']}'");
    $hozrend_mobilnev = '';
    if($munkalap['szemelyzet_id']==MOBILCSOPORT && $munkalap['mobil_loginnev']){
        $torzsadat = $adatbazis_kapcsolat->s2('nev', 'torzsadat', "`felh_loginnev`='{$munkalap['mobil_loginnev']}'");
        $hozrend_mobilnev .= $torzsadat['nev'];
    }
    if($munkalap['mobil']){
        if($hozrend_mobilnev){
            $hozrend_mobilnev .= ' | ';
        }
        $hozrend_mobilnev .= ' Mobilcsoport: <b>'.strtoupper($munkalap['mobil']).'</b>';
    }

    // Sürgősség pipa
    $surgos_onclick = 'csoportMuvelet(\'surgosseg_indoklas\')';
    $surgos_disabled = true;
    if(_jog(JOG_MUNKALAP_SURGOS_PIPAL)){
        //if($munkalap['stat_id']==MUNKALAP_STAT_NYITOTT && JOG_MUNKALAP_NYITOTT_KEZEL){
            $surgos_disabled = false;
        //}
    }
}
// Bejelentésből 2. munkalap készítésekor a szálloda disabled
$ub_disabled = false;
$obj_post    = '<a href="javascript: obj_gen(); void(0);" class="obj_gen"><i class="material-icons">reorder</i></a>';
if(isset($_GET['ujkent_bejelentes'])) {
    $ub_disabled = true;
    $obj_post    = '';
}

$munkalap_tomb = $munkalap_arch_tomb = $akt_tomb = array(
    array('type' => 'statusz',                  'megn' => 'Státusz:',                   'name' => 'stat_id',            'szuro' => 1,           'oldal' => $megnevezes),
    array('type' => 'text',                     'megn' => 'Munkalap szám:',             'name' => 'ikt_szam',           'szuro' => 1, 'disabled' => 1,        'maxlength' => 250,  'class' => 'kicsi', 'osszevon' => 1, 'post' => '<span style="margin-right: 4rem"></span>'),
    array('type' => 'checkbox',                 'megn' => 'Sürgős:',                    'name' => 'surgos',             'szuro' => 1, 'disabled' => $surgos_disabled,   'class' => 'check', 'osszevon' => 1, 'post' => '<span style="margin-right: 4rem"></span>', 'onclick' => $surgos_onclick),
    array('type' => 'checkbox',                 'megn' => 'Visszaadva:',                'name' => 'visszaadva',         'szuro' => 1, 'disabled' => 1,                  'class' => 'check'),
    array('type' => 'textarea',                 'megn' => 'Leírása:',                   'name' => 'text',               'szuro' => 1, 'disabled' => 0,                  'class' => 'oriasiTargy disableNav'/*,  'limit' => 500/*Limit wysiwyg-el nem kombinálható!*/ /*, 'wysiwyg' => 'Alap'/*Min vagy Full*/, 'feltoltes' => 'Images', 'width' => '100%', 'height' => '150'),
    array('type' => 'select_felh_telephely',    'megn' => 'Szálloda:',                  'name' => 'telephely_id',       'szuro' => 1, 'disabled' => $csakUjkent.$ub_disabled,        'class' => 'disableNav',        'style' => 'width: 100%;', 'alert' => 'Kérem válasszon szállodát!', 'onchange' => 'szallodaVane( this );'),
    array('type' => 'autocomplete',             'megn' => 'Helyiség:',                  'name' => 'obj_id',             'szuro' => 1, 'disabled' => $csakUjkent.$ub_disabled,        'class' => 'oriasi disableNav', 'style' => 'width: calc(100% - 3rem);', 'tabla' => 'obj', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'id_kiir' => 'recid', 'onclick' => 'szallodaVane( this );', 'post' => $obj_post),
    array('type' => 'autocomplete',             'megn' => 'Gépnév:',                    'name' => 'gep_id',             'szuro' => 1, 'disabled' => $csakUjkent,        'class' => 'oriasi disableNav', 'tabla' => 'eszkozkezeles', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'onclick' => 'helyisegVane( this );'),
    array('type' => 'autocomplete',             'megn' => 'Típus:',                     'name' => 'tipus_id',           'szuro' => 1, 'disabled' => $csakUjkent.$ub_disabled,        'class' => 'kozepes disableNav', 'style' => 'width: 30rem', 'tabla' => 'erteklista', 'oszlop_keres' => 'id', 'oszlop_keres_autocomp' => 'info', 'listaz' => 'info', 'csoport' => 6, 'id_kiir' => 'id', 'alert' => 'Kérjük adja meg a tipust!'),
    array('type' => 'autocomplete',             'megn' => 'Külső kivitelező:',          'name' => 'szallito_id',        'szuro' => 1, 'disabled' => 1,                  'class' => 'oriasi disableNav', 'tabla' => 'szallito', 'oszlop_keres' => 'id', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'id_kiir' => 'id'),
    array('type' => 'select_szemelyzet',        'megn' => 'Hozzárendelt személyzet:',   'name' => 'szemelyzet_id',      'szuro' => 1, 'disabled' => 1,                  'class' => 'disableNav',        'post'  => ' '.$hozrend_mobilnev),
    array('type' => 'text',                     'megn' => 'Igényelt elvégzés:',         'name' => 'ig_el_id',           'szuro' => 1, 'disabled' => $csakUjkent,        'class' => 'datetimepicker', 'maxlength' => 16),
    array('type' => 'text',                     'megn' => 'Várható érkezés:',           'name' => 'varh_erk',           'szuro' => 1, 'disabled' => $csakUjkent,        'class' => 'datetimepicker', 'maxlength' => 16),
	array('type' => 'nosql',                    'name' => 'ujkentSzerkID'),
	array('type' => 'nosql',                    'megn' => 'Mobilcsoport:',              'name' => 'mobil', 'szuro' => 1),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));


// Ütemezett feladatok
$nap_rejt = 'dn ';
$het_rejt = 'dn ';
$ho_rejt  = 'dn ';
$utolso_vegrehajtas = '';
if(basename(self())=='utemezett_feladatok.php'){

    $uf = $adatbazis_kapcsolat->s2('cikltip ciklaltip ciklnap ciklszorz aktkezd_d aktvege_d idoszakos kezdho kezdnap vegeho vegenap esem_d szemelyzet_id', 'utemezett_feladatok', "`recid`='{$_GET['id']}'");

    $utolso_vegrehajtas = str_replace('-', '.', $uf['esem_d']);
    $uf['aktkezd_d']    = str_replace('-', '.', $uf['aktkezd_d']);
    $uf['aktvege_d']    = str_replace('-', '.', $uf['aktvege_d']);

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
            // Kipiált hetek beállítása
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

    // Időszakos ütemezés
    require_once 'selectek.php';
    require_once('../class/html/class.Input.php');
    require_once('../class/html/class.Select.php');

    $kezd_ho = new Select($select_honapok, $uf['kezdho']);
    $kezd_ho -> id      = 'kezdho';
    $kezd_ho -> name    = 'kezdho';
    $kezd_ho -> onchange = '$(\'#mehet_gomb\').attr(\'disabled\', false); egyszeri_nullaz(); valid_date_ut_kezd();';
    $kezd_ho = $kezd_ho -> getHtml();

    $vege_ho = new Select($select_honapok, $uf['vegeho']);
    $vege_ho -> id      = 'vegeho';
    $vege_ho -> name    = 'vegeho';
    $kezd_ho -> onchange = '$(\'#mehet_gomb\').attr(\'disabled\', false); egyszeri_nullaz(); valid_date_ut_vege();';
    $vege_ho = $vege_ho -> getHtml();

}

$megnevezes = 'utemezett_feladatok';
$utemezett_feladatok_tomb = $akt_tomb = array(
    array('type' => 'text',                     'megn' => 'Feladatszám:',       'name' => 'id',             'szuro' => 1,  'disabled' => true,       'maxlength' => 10, 'style' => 'width: 6rem', 'osszevon' => 1, 'post' => '<span style="margin-left: 2rem"></span>'),
    array('type' => 'checkbox',                 'megn' => 'Aktív',              'name' => 'aktiv',          'szuro' => 1,  'class' => 'check'),
    array('type' => 'select_felh_telephely',    'megn' => 'Szálloda:',          'name' => 'telephely_id',   'szuro' => 1,  'class' => 'disableNav',  'style' => 'width: 100%;', 'alert' => 'Kérem válasszon szállodát!', 'onchange' => 'szallodaVane( this ); $(\'#obj_id_autocomp\').val(\'\'); $(\'#obj_id\').val(\'\'); $(\'#gep_id_autocomp\').val(\'\'); $(\'#gep_id\').val(\'\');'),
    array('type' => 'autocomplete',             'megn' => 'Helyiség:',          'name' => 'obj_id',         'szuro' => 1,  'class' => 'disableNav', 'style' => 'width: calc(100% - 3rem);', 'tabla' => 'obj', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'id_kiir' => 'recid'/*, 'alert' => 'Kérem adja meg a Helyiséget!'*/, 'onclick' => 'szallodaVane( this );', 'post' => '<a href="javascript: obj_gen(); void(0);" class="obj_gen"><i class="material-icons">format_list_bulleted</i></a>', 'oninput' => '$(\'#gep_id_autocomp\').val(\'\'); $(\'#gep_id\').val(\'\');'),
    array('type' => 'autocomplete',             'megn' => 'Gépnév:',            'name' => 'gep_id',         'szuro' => 1,  'class' => 'oriasi disableNav', 'tabla' => 'eszkozkezeles', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'onclick' => 'helyisegVane( this );'),
    array('type' => 'text',                     'megn' => 'Feladat:',           'name' => 'nev',            'szuro' => 1,  'maxlength' => 250, 'class' => 'oriasi'),
    array('type' => 'checkbox',                 'megn' => 'Hatósági:',          'name' => 'hatosagi',       'szuro' => 1,  'osszevon' => 1, 'post' => '<span style="margin-left: 2rem"></span>'),
    array('type' => 'text',                     'megn' => 'Utoljára ütemezve:', 'name' => 'utnap_d',        'szuro' => 1,  'class' => 'datepicker', 'osszevon' => 1, 'post' => '<span style="margin-left: 2rem"></span>'),
    array('type' => 'text',                     'megn' => 'Hatályos:',          'name' => 'hataly_d',       'szuro' => 1,  'class' => 'datepicker'),
    array('type' => 'text',                     'megn' => 'Utolsó esedékesség:','name' => 'utesnap_d',      'szuro' => 1,  'class' => 'datepicker'),
    array('type' => 'text',                     'megn' => 'Riasztás:',          'name' => 'riaszt',         'szuro' => 1,  'maxlength' => 250, 'style' => 'width: 3.5rem', 'post' => ' nappal az esedékesség előtt'),
    array('type' => 'textarea',                 'megn' => 'Leírás:',            'name' => 'leiras',         'szuro' => 1,  'disabled' => $disabled,    'class' => 'oriasiTargy disableNav'/*,  'limit' => 500/*Limit wysiwyg-el nem kombinálható!*/ /*, 'wysiwyg' => 'Alap'/*Min vagy Full*/, 'feltoltes' => 'Images', 'width' => '100%', 'height' => '150'),
    array('type' => 'select_szemelyzet',        'megn' => 'Hozzárendelt személyzet:',  'name' => 'szemelyzet_id', 'szuro' => 1, 'class' => 'disableNav', 'post' => ' Utoljára végrehajtva: <b>'.$utolso_vegrehajtas.'</b>' , 'alert' => 'Kérjük adja meg a hozzárendelt személyzetet!', 'onchange' => 'ut_kulso_kivitlezo_beallit();'),
    array('type' => 'autocomplete',             'megn' => 'Külső kivitelező:',   'name' => 'szallito_id',   'szuro' => 1,   'class' => 'oriasi disableNav', 'tabla' => 'szallito', 'oszlop_keres' => 'id', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'id_kiir' => 'id'),

    array('type' => 'html',                     'megn' => nl2('
                                                                <div class="dtr mutat_szinva_tmp">
                                                                    <div class="dtc form_bal">
                                                                        Ütemezés típus:
                                                                    </div>
                                                                ')
                                                            ),
    array('type' => 'html',                     'megn' => '<div class="dtc form_jobb" style="padding: 0rem 0 0rem 1rem">'),
        array('type' => 'select_cikltip',       'megn' => '', 'megn_szurobe' => 'Ütemezés típus:',    'name' => 'cikltip', 'class' => 'f_cikltip disableNav', 'szuro' => 1, 'style' => 'margin-left: -1.2rem'  ),
    array('type' => 'html',                     'megn' => '<fieldset style="width: auto; margin-top: 0.2rem; margin-left: -0.5rem;">'),
        array('type' => 'text',                     'megn' => '<span style="margin-left: 0.5rem"></span>Esedékes:',         'name' => 'esnap_d', 'szuro' => 1,     'class' => 'datepicker'),

        // Naponta
        array('type' => 'html',                     'megn' => nl2('<div id="tipus_d" style="padding: 0" class="'.$nap_rejt.'cikltip">')),
        array('type' => 'text',                     'megn' => '',                   'name' => 'ciklnap', 'class' => 'c',       'pre' => 'Ismétlődik ', 'post' => ' naponta', 'style' => 'width: 2.6rem', 'maxlength' => 2, 'onkeyup' => 'onlyNumbers( $(this) ); $(\'#hetkoz_kent\').prop(\'checked\', false)', 'value' => $ciklnap),
        array('type' => 'html',                     'megn' => nl2('
                                                                    <div style="padding: 0 0 0.3rem 0.7rem">
                                                                        <div class="cb">
                                                                            <input'.$hetkoznapokent.' name="hetkoz_kent" id="hetkoz_kent" onclick="$(\'#ciklnap\').val(\'\')" type="checkbox">
                                                                            <label for="hetkoz_kent" onclick="$(\'#mehet_gomb\').attr(\'disabled\', false);"></label>
                                                                        </div>
                                                                        Ismétlődik minden hétköznap
                                                                    </div>
                                                                ')),
        array('type' => 'html',                     'megn' => nl2('</div>')),

        // Hetente
        array('type' => 'html',                     'megn' => nl2('<div id="tipus_w" style="padding: 0" class="'.$het_rejt.'cikltip">')),
        array('type' => 'html',                     'megn' => nl2('
                                                            <div class="dtc form_jobb">
                                                                Ismétlődik <input maxlength="2" name="ism_het" id="ism_het" onkeyup="onlyNumbers( $(this) ); $(\'#mehet_gomb\').attr(\'disabled\', false);" style="width: 4rem" type="text" value="'.$ciklszorz_w.'">
                                                                hetente a következő napokon:
                                                            </div>
                                                            <div style="padding: 0 0 0.3rem 0.5rem">
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
                                                            </div>
                                                                
                                            ')),
        array('type' => 'html',                     'megn' => nl2('</div>')),

        //Havonta
        array('type' => 'html',                     'megn' => nl2('<div id="tipus_m" style="padding: 0" class="'.$ho_rejt.'cikltip">')),
        array('type' => 'text',                     'megn' => '',                   'name' => 'ciklho',     'pre' => 'Ismétlődik ', 'post' => ' havonta a hónap ', 'style' => 'width: 4rem', 'maxlength' => 2, 'onkeyup' => 'onlyNumbers( $(this) ); $(\'#ciklho2\').val(\'\'); $(\'#ciklhet\').val(\'\'); $(\'#ho_rendsz_het\').val(\'\');', 'osszevon' => 1, 'value' => $ciklnap_honap),
        array('type' => 'select_honapjan',          'megn' => '',                   'name' => 'honapjan',                           'post' => ' napján', 'class' => 'disableNav'),
        array('type' => 'text',                     'megn' => '',                   'name' => 'ciklho2',    'pre' => 'Ismétlődik ', 'post' => ' havonta a ', 'style' => 'width: 4rem', 'maxlength' => 2, 'onkeyup' => 'onlyNumbers( $(this) ); $(\'#ciklho\').val(\'\'); $(\'#honapjan\').val(\'\')', 'osszevon' => 1),
        array('type' => 'select_ho_ism',            'megn' => '',                   'name' => 'ciklhet',        'osszevon' => 1,    'class' => 'disableNav'),
        array('type' => 'select_ho_soran',          'megn' => '',                   'name' => 'ho_rendsz_het',  'post' => ' során', 'class' => 'disableNav'),
        array('type' => 'html',                     'megn' => nl2('</div></div>')),

    array('type' => 'html',                     'megn' => '</fieldset></div>'),

    array('type' => 'checkbox',                 'megn' => 'Időszakos ütemezés:','name' => 'idoszakos', 'szuro' => 1,   'class' => 'check', 'onclick' => '$(\'#idoszakos_utem\').toggle(); if(!$(\'#idoszakos_utem\').is(\':visible\')){ $(\'#idoszakos_utem input, #idoszakos_utem select\').val(\'\'); }', 'pre' => nl2('<div class="dtc vam">'), 'post' => nl2('
                                                                        </div>
                                                                        <div class="dtc vam" style="padding-left: 0.7rem">
                                                                            Időszak: '.$kezd_ho.'
                                                                            <input type="text" id="kezdnap" name="kezdnap" value="'.$uf['kezdnap'].'" class="c" style="width: 2.8rem" maxlength="2" onfocus="egyszeri_nullaz()"> . napjától - '.$vege_ho.'
                                                                            <input type="text" id="vegenap" name="vegenap" value="'.$uf['vegenap'].'" class="c" style="width: 2.8rem" maxlength="2" onfocus="egyszeri_nullaz()"> . napjáig
                                                                            <div style="margin-top: 0.5rem; display: none">
                                                                                Egyszeri ütemezés
                                                                                <input class="datepicker" id="aktkezd_d" name="aktkezd_d" value="'.$uf['aktkezd_d'].'" type="text" onblur="szokasos_nullaz()">
                                                                                <span class="formText"> - </span>
                                                                                <input class="datepicker" id="aktvege_d" name="aktvege_d" value="'.$uf['aktvege_d'].'" type="text" onblur="szokasos_nullaz()">
                                                                            </div>
                                                                        </div>
        ')),

    array('type' => 'text',                     'megn' => 'Elvégzési idő:',     'name' => 'elv_ido_tol', 'class' => 'ora_perc', 'maxlength' => 5, 'osszevon' => 1, 'post' => ' -', 'style' => 'margin-right: 0.5rem'),
    array('type' => 'text',                     'megn' => '',                   'name' => 'elv_ido_ig',  'class' => 'ora_perc', 'maxlength' => 5),


    //array('type' => 'submit', 'value' => 'Mentés',    'name' => 'mehet_gomb', 'class' => 'gombb'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));


// Rendszeres karbantartás
$nap_rejt = 'dn ';
$het_rejt = 'dn ';
$ho_rejt  = 'dn ';
$utolso_vegrehajtas = '';
if(basename(self())=='rendszeres_karbantartas.php'){
    $uf = $adatbazis_kapcsolat->s2('cikltip ciklaltip ciklnap ciklszorz aktkezd_d aktvege_d idoszakos kezdho kezdnap vegeho vegenap esem_d szemelyzet_id', 'rendszeres_karbantartas', "`recid`='{$_GET['id']}'");

    $utolso_vegrehajtas = $uf['esem_d'];
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

$megnevezes = 'rendszeres_karbantartas';
$rendszeres_karbantartas_tomb = $akt_tomb = array(
    array('type' => 'text',                     'megn' => 'Feladatszám:',       'name' => 'id',             'szuro' => 1,  'disabled' => true,       'maxlength' => 10, 'style' => 'width: 6rem', 'osszevon' => 1, 'post' => '<span style="margin-left: 2rem"></span>'),
    array('type' => 'checkbox',                 'megn' => 'Aktív',              'name' => 'aktiv',          'szuro' => 1,  'class' => 'check'),
    array('type' => 'select_felh_telephely',    'megn' => 'Szálloda:',          'name' => 'telephely_id',   'szuro' => 1,  'class' => 'disableNav',  'style' => 'width: 100%;', 'alert' => 'Kérem válasszon szállodát!', 'onchange' => 'szallodaVane( this ); $(\'#obj_id_autocomp\').val(\'\'); $(\'#obj_id\').val(\'\'); $(\'#gep_id_autocomp\').val(\'\'); $(\'#gep_id\').val(\'\');'),
    array('type' => 'autocomplete',             'megn' => 'Helyiség:',          'name' => 'obj_id',         'szuro' => 1,  'class' => 'disableNav', 'style' => 'width: calc(100% - 3rem);', 'tabla' => 'obj', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'id_kiir' => 'recid'/*, 'alert' => 'Kérem adja meg a Helyiséget!'*/, 'onclick' => 'szallodaVane( this );', 'post' => '<a href="javascript: obj_gen(); void(0);" class="obj_gen"><i class="material-icons">format_list_bulleted</i></a>', 'oninput' => '$(\'#gep_id_autocomp\').val(\'\'); $(\'#gep_id\').val(\'\');'),
    array('type' => 'autocomplete',             'megn' => 'Gépnév:',            'name' => 'gep_id',         'szuro' => 1,  'class' => 'oriasi disableNav', 'tabla' => 'eszkozkezeles', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'onclick' => 'helyisegVane( this );'),
    array('type' => 'text',                     'megn' => 'Feladat:',           'name' => 'nev',            'szuro' => 1,  'maxlength' => 250, 'class' => 'oriasi'),
    array('type' => 'checkbox',                 'megn' => 'Hatósági:',          'name' => 'hatosagi',       'szuro' => 1,  'osszevon' => 1, 'post' => '<span style="margin-left: 2rem"></span>'),
    array('type' => 'text',                     'megn' => 'Utoljára ütemezve:', 'name' => 'utnap_d',        'szuro' => 1,  'class' => 'datepicker', 'osszevon' => 1, 'post' => '<span style="margin-left: 2rem"></span>'),
    array('type' => 'text',                     'megn' => 'Hatályos:',          'name' => 'hataly_d',       'szuro' => 1,  'class' => 'datepicker'),
    array('type' => 'text',                     'megn' => 'Utolsó esedékesség:','name' => 'utesnap_d',      'szuro' => 1,  'class' => 'datepicker'),
    array('type' => 'text',                     'megn' => 'Riasztás:',          'name' => 'riaszt',         'szuro' => 1,  'maxlength' => 250, 'style' => 'width: 3.5rem', 'post' => ' nappal az esedékesség előtt'),
    array('type' => 'textarea',                 'megn' => 'Leírás:',            'name' => 'leiras',         'szuro' => 1,  'disabled' => $disabled,    'class' => 'oriasiTargy disableNav'/*,  'limit' => 500/*Limit wysiwyg-el nem kombinálható!*/ /*, 'wysiwyg' => 'Alap'/*Min vagy Full*/, 'feltoltes' => 'Images', 'width' => '100%', 'height' => '150'),
    array('type' => 'select_szemelyzet',        'megn' => 'Hozzárendelt személyzet:',  'name' => 'szemelyzet_id', 'szuro' => 1, 'class' => 'disableNav', 'post' => ' Utoljára végrehajtva: <b>'.$utolso_vegrehajtas.'</b>' , 'alert' => 'Kérjük adja meg a hozzárendelt személyzetet!', 'onchange' => 'ut_kulso_kivitlezo_beallit();'),
    array('type' => 'autocomplete',             'megn' => 'Külső kivitelező:',   'name' => 'szallito_id',   'szuro' => 1,   'class' => 'oriasi disableNav', 'tabla' => 'szallito', 'oszlop_keres' => 'id', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'id_kiir' => 'id'),

    array('type' => 'html',                     'megn' => nl2('
                                                                <div class="dtr mutat_szinva_tmp">
                                                                    <div class="dtc form_bal">
                                                                        Ütemezés típus:
                                                                    </div>
                                                                ')
                                                            ),
    array('type' => 'html',                     'megn' => '<div class="dtc form_jobb" style="padding: 0rem 0 0rem 1rem">'),
        array('type' => 'select_cikltip',       'megn' => '', 'megn_szurobe' => 'Ütemezés típus:',    'name' => 'cikltip', 'class' => 'f_cikltip disableNav', 'szuro' => 1, 'style' => 'margin-left: -1.2rem'  ),
    array('type' => 'html',                     'megn' => '<fieldset style="width: auto; margin-top: 0.2rem; margin-left: -0.5rem;">'),
        array('type' => 'text',                     'megn' => '<span style="margin-left: 0.5rem"></span>Esedékes:',         'name' => 'esnap_d', 'szuro' => 1,     'class' => 'datepicker'),

        // Naponta
        array('type' => 'html',                     'megn' => nl2('<div id="tipus_d" style="padding: 0" class="'.$nap_rejt.'cikltip">')),
        array('type' => 'text',                     'megn' => '',                   'name' => 'ciklnap', 'class' => 'c',       'pre' => 'Ismétlődik ', 'post' => ' naponta', 'style' => 'width: 2.6rem', 'maxlength' => 2, 'onkeyup' => 'onlyNumbers( $(this) ); $(\'#hetkoz_kent\').prop(\'checked\', false)', 'value' => $ciklnap),
        array('type' => 'html',                     'megn' => nl2('
                                                                    <div style="padding: 0 0 0.3rem 0.7rem">
                                                                        <div class="cb">
                                                                            <input'.$hetkoznapokent.' name="hetkoz_kent" id="hetkoz_kent" onclick="$(\'#ciklnap\').val(\'\')" type="checkbox">
                                                                            <label for="hetkoz_kent" onclick="$(\'#mehet_gomb\').attr(\'disabled\', false);"></label>
                                                                        </div>
                                                                        Ismétlődik minden hétköznap
                                                                    </div>
                                                                ')),
        array('type' => 'html',                     'megn' => nl2('</div>')),

        // Hetente
        array('type' => 'html',                     'megn' => nl2('<div id="tipus_w" style="padding: 0" class="'.$het_rejt.'cikltip">')),
        array('type' => 'html',                     'megn' => nl2('
                                                            <div class="dtc form_jobb">
                                                                Ismétlődik <input maxlength="2" name="ism_het" id="ism_het" onkeyup="onlyNumbers( $(this) ); $(\'#mehet_gomb\').attr(\'disabled\', false);" style="width: 4rem" type="text" value="'.$ciklszorz_w.'">
                                                                hetente a következő napokon:
                                                            </div>
                                                            <div style="padding: 0 0 0.3rem 0.5rem">
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
                                                            </div>
                                                                
                                            ')),
        array('type' => 'html',                     'megn' => nl2('</div>')),

        //Havonta
        array('type' => 'html',                     'megn' => nl2('<div id="tipus_m" style="padding: 0" class="'.$ho_rejt.'cikltip">')),
        array('type' => 'text',                     'megn' => '',                   'name' => 'ciklho',     'pre' => 'Ismétlődik ', 'post' => ' havonta a hónap ', 'style' => 'width: 4rem', 'maxlength' => 2, 'onkeyup' => 'onlyNumbers( $(this) ); $(\'#ciklho2\').val(\'\'); $(\'#ciklhet\').val(\'\'); $(\'#ho_rendsz_het\').val(\'\');', 'osszevon' => 1, 'value' => $ciklnap_honap),
        array('type' => 'select_honapjan',          'megn' => '',                   'name' => 'honapjan',                           'post' => ' napján', 'class' => 'disableNav'),
        array('type' => 'text',                     'megn' => '',                   'name' => 'ciklho2',    'pre' => 'Ismétlődik ', 'post' => ' havonta a ', 'style' => 'width: 4rem', 'maxlength' => 2, 'onkeyup' => 'onlyNumbers( $(this) ); $(\'#ciklho\').val(\'\'); $(\'#honapjan\').val(\'\')', 'osszevon' => 1),
        array('type' => 'select_ho_ism',            'megn' => '',                   'name' => 'ciklhet',        'osszevon' => 1,    'class' => 'disableNav'),
        array('type' => 'select_ho_soran',          'megn' => '',                   'name' => 'ho_rendsz_het',  'post' => ' során', 'class' => 'disableNav'),
        array('type' => 'html',                     'megn' => nl2('</div></div>')),

    array('type' => 'html',                     'megn' => '</fieldset></div>'),

    array('type' => 'checkbox',                 'megn' => 'Időszakos ütemezés:','name' => 'idoszakos', 'szuro' => 1,   'class' => 'check', 'onclick' => '$(\'#idoszakos_utem\').toggle(); if(!$(\'#idoszakos_utem\').is(\':visible\')){ $(\'#idoszakos_utem input, #idoszakos_utem select\').val(\'\'); }', 'pre' => nl2('<div class="dtc vam">'), 'post' => nl2('
                                                                        </div>
                                                                        <div class="dtc vam" style="padding-left: 0.7rem">
                                                                            Időszak: '.$kezd_ho.'
                                                                            <input type="text" id="kezdnap" name="kezdnap" value="'.$uf['kezdnap'].'" class="c" style="width: 2.8rem" maxlength="2" onfocus="egyszeri_nullaz()"> . napjától - '.$vege_ho.'
                                                                            <input type="text" id="vegenap" name="vegenap" value="'.$uf['vegenap'].'" class="c" style="width: 2.8rem" maxlength="2" onfocus="egyszeri_nullaz()"> . napjáig
                                                                            <div style="margin-top: 0.5rem; display: none">
                                                                                Egyszeri ütemezés
                                                                                <input class="datepicker" id="aktkezd_d" name="aktkezd_d" value="'.$uf['aktkezd_d'].'" type="text" onblur="szokasos_nullaz()">
                                                                                <span class="formText"> - </span>
                                                                                <input class="datepicker" id="aktvege_d" name="aktvege_d" value="'.$uf['aktvege_d'].'" type="text" onblur="szokasos_nullaz()">
                                                                            </div>
                                                                        </div>
        ')),

    array('type' => 'text',                     'megn' => 'Elvégzési idő:',     'name' => 'elv_ido_tol', 'class' => 'ora_perc', 'maxlength' => 5, 'osszevon' => 1, 'post' => ' -', 'style' => 'margin-right: 0.5rem'),
    array('type' => 'text',                     'megn' => '',                   'name' => 'elv_ido_ig',  'class' => 'ora_perc', 'maxlength' => 5),


    //array('type' => 'submit', 'value' => 'Mentés',    'name' => 'mehet_gomb', 'class' => 'gombb'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));

//Eseti fenntartás
$nap_rejt = 'dn ';
$het_rejt = 'dn ';
$ho_rejt  = 'dn ';
$utolso_vegrehajtas = '';
if(basename(self())=='eseti_fenntartas.php'){
    $uf = $adatbazis_kapcsolat->s2('cikltip ciklaltip ciklnap ciklszorz aktkezd_d aktvege_d idoszakos kezdho kezdnap vegeho vegenap esem_d', 'eseti_fenntartas', "`recid`='{$_GET['id']}'");

    $utolso_vegrehajtas = $uf['esem_d'];
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

$megnevezes = 'eseti_fenntartas';
$eseti_fenntartas_tomb = $akt_tomb = array(
    array('type' => 'text',                     'megn' => 'Feladatszám:',       'name' => 'id',             'szuro' => 1,  'disabled' => true,       'maxlength' => 10, 'style' => 'width: 6rem', 'osszevon' => 1, 'post' => '<span style="margin-left: 2rem"></span>'),
    array('type' => 'checkbox',                 'megn' => 'Aktív',              'name' => 'aktiv',          'szuro' => 1,  'class' => 'check'),
    array('type' => 'select_felh_telephely',    'megn' => 'Szálloda:',          'name' => 'telephely_id',   'szuro' => 1,  'class' => 'disableNav',  'style' => 'width: 100%;', 'alert' => 'Kérem válasszon szállodát!', 'onchange' => 'szallodaVane( this ); $(\'#obj_id_autocomp\').val(\'\'); $(\'#obj_id\').val(\'\'); $(\'#gep_id_autocomp\').val(\'\'); $(\'#gep_id\').val(\'\');'),
    array('type' => 'autocomplete',             'megn' => 'Helyiség:',          'name' => 'obj_id',         'szuro' => 1,  'class' => 'disableNav', 'style' => 'width: calc(100% - 3rem);', 'tabla' => 'obj', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'id_kiir' => 'recid'/*, 'alert' => 'Kérem adja meg a Helyiséget!'*/, 'onclick' => 'szallodaVane( this );', 'post' => '<a href="javascript: obj_gen(); void(0);" class="obj_gen"><i class="material-icons">format_list_bulleted</i></a>', 'oninput' => '$(\'#gep_id_autocomp\').val(\'\'); $(\'#gep_id\').val(\'\');'),
    array('type' => 'autocomplete',             'megn' => 'Gépnév:',            'name' => 'gep_id',         'szuro' => 1,  'class' => 'oriasi disableNav', 'tabla' => 'eszkozkezeles', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'onclick' => 'helyisegVane( this );'),
    array('type' => 'text',                     'megn' => 'Feladat:',           'name' => 'nev',            'szuro' => 1,  'maxlength' => 250, 'class' => 'oriasi'),
    array('type' => 'checkbox',                 'megn' => 'Hatósági:',          'name' => 'hatosagi',       'szuro' => 1,  'osszevon' => 1, 'post' => '<span style="margin-left: 2rem"></span>'),
    array('type' => 'text',                     'megn' => 'Utoljára ütemezve:', 'name' => 'utnap_d',        'szuro' => 1,  'class' => 'datepicker', 'osszevon' => 1, 'post' => '<span style="margin-left: 2rem"></span>'),
    array('type' => 'text',                     'megn' => 'Hatályos:',          'name' => 'hataly_d',       'szuro' => 1,  'class' => 'datepicker'),
    array('type' => 'text',                     'megn' => 'Utolsó esedékesség:','name' => 'utesnap_d',      'szuro' => 1,  'class' => 'datepicker'),
    array('type' => 'text',                     'megn' => 'Riasztás:',          'name' => 'riaszt',         'szuro' => 1,  'maxlength' => 250, 'style' => 'width: 3.5rem', 'post' => ' nappal az esedékesség előtt'),
    array('type' => 'textarea',                 'megn' => 'Leírás:',            'name' => 'leiras',         'szuro' => 1,  'disabled' => $disabled,    'class' => 'oriasiTargy disableNav'/*,  'limit' => 500/*Limit wysiwyg-el nem kombinálható!*/ /*, 'wysiwyg' => 'Alap'/*Min vagy Full*/, 'feltoltes' => 'Images', 'width' => '100%', 'height' => '150'),
    array('type' => 'select_szemelyzet',        'megn' => 'Hozzárendelt személyzet:',  'name' => 'szemelyzet_id', 'szuro' => 1, 'class' => 'disableNav', 'post' => ' Utoljára végrehajtva: <b>'.$utolso_vegrehajtas.'</b>' , 'alert' => 'Kérjük adja meg a hozzárendelt személyzetet!', 'onchange' => 'ut_kulso_kivitlezo_beallit();'),
    array('type' => 'autocomplete',             'megn' => 'Külső kivitelező:',   'name' => 'szallito_id',   'szuro' => 1,   'class' => 'oriasi disableNav', 'tabla' => 'szallito', 'oszlop_keres' => 'id', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'id_kiir' => 'id'),

    array('type' => 'html',                     'megn' => nl2('
                                                                <div class="dtr mutat_szinva_tmp">
                                                                    <div class="dtc form_bal">
                                                                        Ütemezés típus:
                                                                    </div>
                                                                ')
                                                            ),
    array('type' => 'html',                     'megn' => '<div class="dtc form_jobb" style="padding: 0rem 0 0rem 1rem">'),
        array('type' => 'select_cikltip',       'megn' => '', 'megn_szurobe' => 'Ütemezés típus:',    'name' => 'cikltip', 'class' => 'f_cikltip disableNav', 'szuro' => 1, 'style' => 'margin-left: -1.2rem'  ),
    array('type' => 'html',                     'megn' => '<fieldset style="width: auto; margin-top: 0.2rem; margin-left: -0.5rem;">'),
        array('type' => 'text',                     'megn' => '<span style="margin-left: 0.5rem"></span>Esedékes:',         'name' => 'esnap_d', 'szuro' => 1,     'class' => 'datepicker'),

        // Naponta
        array('type' => 'html',                     'megn' => nl2('<div id="tipus_d" style="padding: 0" class="'.$nap_rejt.'cikltip">')),
        array('type' => 'text',                     'megn' => '',                   'name' => 'ciklnap', 'class' => 'c',       'pre' => 'Ismétlődik ', 'post' => ' naponta', 'style' => 'width: 2.6rem', 'maxlength' => 2, 'onkeyup' => 'onlyNumbers( $(this) ); $(\'#hetkoz_kent\').prop(\'checked\', false)', 'value' => $ciklnap),
        array('type' => 'html',                     'megn' => nl2('
                                                                    <div style="padding: 0 0 0.3rem 0.7rem">
                                                                        <div class="cb">
                                                                            <input'.$hetkoznapokent.' name="hetkoz_kent" id="hetkoz_kent" onclick="$(\'#ciklnap\').val(\'\')" type="checkbox">
                                                                            <label for="hetkoz_kent" onclick="$(\'#mehet_gomb\').attr(\'disabled\', false);"></label>
                                                                        </div>
                                                                        Ismétlődik minden hétköznap
                                                                    </div>
                                                                ')),
        array('type' => 'html',                     'megn' => nl2('</div>')),

        // Hetente
        array('type' => 'html',                     'megn' => nl2('<div id="tipus_w" style="padding: 0" class="'.$het_rejt.'cikltip">')),
        array('type' => 'html',                     'megn' => nl2('
                                                            <div class="dtc form_jobb">
                                                                Ismétlődik <input maxlength="2" name="ism_het" id="ism_het" onkeyup="onlyNumbers( $(this) ); $(\'#mehet_gomb\').attr(\'disabled\', false);" style="width: 4rem" type="text" value="'.$ciklszorz_w.'">
                                                                hetente a következő napokon:
                                                            </div>
                                                            <div style="padding: 0 0 0.3rem 0.5rem">
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
                                                            </div>
                                                                
                                            ')),
        array('type' => 'html',                     'megn' => nl2('</div>')),

        //Havonta
        array('type' => 'html',                     'megn' => nl2('<div id="tipus_m" style="padding: 0" class="'.$ho_rejt.'cikltip">')),
        array('type' => 'text',                     'megn' => '',                   'name' => 'ciklho',     'pre' => 'Ismétlődik ', 'post' => ' havonta a hónap ', 'style' => 'width: 4rem', 'maxlength' => 2, 'onkeyup' => 'onlyNumbers( $(this) ); $(\'#ciklho2\').val(\'\'); $(\'#ciklhet\').val(\'\'); $(\'#ho_rendsz_het\').val(\'\');', 'osszevon' => 1, 'value' => $ciklnap_honap),
        array('type' => 'select_honapjan',          'megn' => '',                   'name' => 'honapjan',                           'post' => ' napján', 'class' => 'disableNav'),
        array('type' => 'text',                     'megn' => '',                   'name' => 'ciklho2',    'pre' => 'Ismétlődik ', 'post' => ' havonta a ', 'style' => 'width: 4rem', 'maxlength' => 2, 'onkeyup' => 'onlyNumbers( $(this) ); $(\'#ciklho\').val(\'\'); $(\'#honapjan\').val(\'\')', 'osszevon' => 1),
        array('type' => 'select_ho_ism',            'megn' => '',                   'name' => 'ciklhet',        'osszevon' => 1,    'class' => 'disableNav'),
        array('type' => 'select_ho_soran',          'megn' => '',                   'name' => 'ho_rendsz_het',  'post' => ' során', 'class' => 'disableNav'),
        array('type' => 'html',                     'megn' => nl2('</div></div>')),

    array('type' => 'html',                     'megn' => '</fieldset></div>'),

    // Eseti fenntartásnál nincs időszakos ütemezés
//    array('type' => 'checkbox',                 'megn' => 'Időszakos ütemezés:','name' => 'idoszakos', 'szuro' => 1,   'class' => 'check', 'onclick' => '$(\'#idoszakos_utem\').toggle(); if(!$(\'#idoszakos_utem\').is(\':visible\')){ $(\'#idoszakos_utem input, #idoszakos_utem select\').val(\'\'); }', 'pre' => nl2('<div class="dtc vam">'), 'post' => nl2('
//                                                                        </div>
//                                                                        <div class="dtc vam" style="padding-left: 0.7rem">
//                                                                            Időszak: '.$kezd_ho.'
//                                                                            <input type="text" id="kezdnap" name="kezdnap" value="'.$uf['kezdnap'].'" class="c" style="width: 2.8rem" maxlength="2" onfocus="egyszeri_nullaz()"> . napjától - '.$vege_ho.'
//                                                                            <input type="text" id="vegenap" name="vegenap" value="'.$uf['vegenap'].'" class="c" style="width: 2.8rem" maxlength="2" onfocus="egyszeri_nullaz()"> . napjáig
//                                                                            <div style="margin-top: 0.5rem; display: none">
//                                                                                Egyszeri ütemezés
//                                                                                <input class="datepicker" id="aktkezd_d" name="aktkezd_d" value="'.$uf['aktkezd_d'].'" type="text" onblur="szokasos_nullaz()">
//                                                                                <span class="formText"> - </span>
//                                                                                <input class="datepicker" id="aktvege_d" name="aktvege_d" value="'.$uf['aktvege_d'].'" type="text" onblur="szokasos_nullaz()">
//                                                                            </div>
//                                                                        </div>
//        ')),

    array('type' => 'text',                     'megn' => 'Elvégzési idő:',     'name' => 'elv_ido_tol', 'class' => 'ora_perc', 'maxlength' => 5, 'osszevon' => 1, 'post' => ' -', 'style' => 'margin-right: 0.5rem'),
    array('type' => 'text',                     'megn' => '',                   'name' => 'elv_ido_ig',  'class' => 'ora_perc', 'maxlength' => 5),


    //array('type' => 'submit', 'value' => 'Mentés',    'name' => 'mehet_gomb', 'class' => 'gombb'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));


// Anyagigénylés
$megnevezes = 'anyagigenyles';
// Mennyiségi egység kiszámítása
$cikkmee    = 'db';
$post       = '';
if (isset($_GET['id'])) {
    $anyagigenyles = $adatbazis_kapcsolat->s2('cikk_id', $megnevezes, "`recid`='{$_GET['id']}'");

    $cikktorzs     = $adatbazis_kapcsolat->s2('cikkmee', 'cikktorzs', "`id`='{$anyagigenyles['cikk_id']}'");
    $cikkmee       = $cikktorzs['cikkmee'];
}

$style = '';
if (isset($_GET['modosit'])) {
    $igenyles = $adatbazis_kapcsolat->s2('stat_id', $megnevezes, "`recid`='{$_GET['id']}'");
    // Ha az anyagigénylés legalább megrendelve vagy nagyobb státuszban van, akkor jelenik meg rajta a rendelés kép gomb.
    if($igenyles['stat_id']>=CIKKIGN_STAT_MEGRENDELVE){
        $post = '<span style="margin-left: 2rem"></span><input type="button" value="rendelés kép" class="gomb f_nem_rejt">';
    }
}

$tav_04 = '<span style="margin-left: 0.5rem"></span>';
$tav_12 = '<span style="margin-left: 1.2rem"></span>';
$tav_26 = '<span style="margin-left: 2.6rem"></span>';
$tav_4  = '<span style="margin-left: 4rem"></span>';
$anyagigenyles_tomb = $akt_tomb = array(
    array('type' => 'statusz',                  'megn' => 'Státusz:',                       'name' => 'stat_id',        'szuro' => 1,   'oldal' => $megnevezes),
    array('type' => 'text',                     'megn' => 'Anyagigénylés szám:',            'name' => 'ikt_szam',       'szuro' => 1,   'disabled' => 1,   'class' => 'normal', 'post' => '<span style="margin-left: 4rem"></span>', 'style' => 'width: 8rem'),
//    array('type' => 'checkbox',                 'megn' => 'Készpénzes:',                    'name' => 'keszpenz',       'szuro' => 1,   'disabled' => 1,   'post' => $post),
    array('type' => 'select_felh_telephely',    'megn' => 'Szálloda:',                      'name' => 'telephely_id',   'szuro' => 1,   'disabled' => 1,   'class' => 'disableNav',   'style' => 'width: 100%;', 'alert' => 'Kérem válasszon szállodát!', 'onchange' => 'szallodaVane( this );'),
    array('type' => 'text',                     'megn' => 'Cikkszám:',                      'name' => 'cikk_id',        'szuro' => 1,   'disabled' => 1),
    array('type' => 'text',                     'megn' => 'Cikk név:',                      'name' => 'nev',            'szuro' => 1,   'disabled' => 1,   'class' => 'oriasi disableNav', 'tabla' => 'cikktorzs', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev'),
    array('type' => 'text',                     'megn' => 'Igényelt mennyiség:',            'name' => 'igmen',          'szuro' => 1,   'disabled' => 1,   'class' => 'f_num', 'post' => ' '.$cikkmee.'<span style="margin-left: 2rem"></span>', 'osszevon' => 1),
    array('type' => 'checkbox',                 'megn' => 'RB-s anyag:',                    'name' => 'kulsosnek',      'szuro' => 1,   'disabled' => 1),
    array('type' => 'text',                     'megn' => 'Jóváhagyott mennyiség:',         'name' => 'jhmen',          'szuro' => 1,   'disabled' => 1,   'class' => 'f_num',   'post' => ' '.$cikkmee.'<span style="margin-left: 2rem"></span>', 'osszevon' => 1, 'style' => $style),
    array('type' => 'text',                     'megn' => $tav_4.'Rendelés szám:',          'name' => 'rndid',          'szuro' => 1,   'disabled' => 1,   'style' => 'width: 9rem'),
    array('type' => 'text',                     'megn' => 'Rendelt mennyiség:',             'name' => 'rndmen',         'szuro' => 1,   'disabled' => 1,   'class' => 'f_num',   'data' => 'data-tort="2"',   'post' => ' '.$cikkmee.'<span style="margin-left: 2rem"></span>', 'osszevon' => 1),
    array('type' => 'text',                     'megn' => $tav_12.'Rendelési egységár:',    'name' => 'rndear',         'szuro' => 1,   'disabled' => 1,   'class' => 'f_num',   'data' => 'data-tort="4"',   'post' => ' '.  get_penznem()),
//array('type' => 'text',                     'megn' => 'Beérkezett mennyiség:',          'name' => 'erkmen',         'szuro' => 1,   'disabled' => 1,   'class' => 'f_num',   'post' => ' '.$cikkmee.'<span style="margin-left: 2rem"></span>', 'osszevon' => 1),
//array('type' => 'text',                     'megn' => $tav_04.'Beérkezési egységár:',   'name' => 'erkear',         'szuro' => 1,   'disabled' => 1,   'class' => 'f_num',   'data' => 'data-tort="4"',   'post' => ' '.  get_penznem()),
    array('type' => 'autocomplete',             'megn' => 'Pénznem:',                       'name' => 'kcs_21',         'szuro' => 1,   'disabled' => 1,   'class' => 'disableNav', 'tabla' => 'erteklista', 'oszlop_keres' => 'id', 'oszlop_keres_autocomp' => 'info', 'listaz' => 'info', 'csoport' => 21, 'id_kiir' => 'id', 'alert' => 'Kérem adja meg a pénznemet!', 'style' => 'width: 5rem'),
//array('type' => 'text',                     'megn' => 'Deviza rend. egységár:',         'name' => 'vrndear',        'szuro' => 1,   'disabled' => 1,   'class' => 'f_num',   'data' => 'data-tort="4"',   'osszevon' => 1),
//array('type' => 'text',                     'megn' => $tav_26.'Deviza beérk. egységár:','name' => 'verkear',        'szuro' => 1,   'disabled' => 1,   'class' => 'f_num',   'data' => 'data-tort="4"'),
    array('type' => 'autocomplete',             'megn' => 'Szállító:',                      'name' => 'szallito_id',    'szuro' => 1,   'disabled' => 1,   'class' => 'oriasi disableNav', 'tabla' => 'szallito', 'oszlop_keres' => 'id', 'id_kiir' => 'id', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev'),
    array('type' => 'textarea',                 'megn' => 'Leírás:',                        'name' => 'igleir',         'szuro' => 1,   'disabled' => 1,   'class' => 'oriasi disableNav', 'style' => 'height: 4rem'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));

// Anyagrendeléskor más az űrlap
if (isset($_GET['anyagrend'])) {
    $anyagigenyles_tomb = $akt_tomb = array(
        array('type' => 'statusz',                  'megn' => 'Státusz:',                       'name' => 'stat_id',        'szuro' => 1,   'oldal' => $megnevezes),
        array('type' => 'text',                     'megn' => 'Rendelés szám:',                 'name' => 'rndid',          'szuro' => 1,   'disabled' => 1,   'class' => 'normal', 'osszevon' => 1, 'post' => '<span style="margin-left: 4rem"></span>', 'style' => 'width: 6.8rem'),
        array('type' => 'text',                     'megn' => 'Rendelés dátum:',                'name' => 'rnd_dt',         'szuro' => 1,   'disabled' => 1,   'style' => 'width: 7.8rem'),
        array('type' => 'text',                     'megn' => 'Rendelés érték:',                'name' => 'rndertek',       'szuro' => 1,   'disabled' => 1,   'style' => 'width: 7.3rem; text-align: right', 'osszevon' => 1, 'post' => ' '.get_penznem().'<span style="margin-left: 4.5rem"></span>'),
        array('type' => 'checkbox',                 'megn' => 'Készpénzes:',                    'name' => 'keszpenz',       'szuro' => 1,   'disabled' => 1),
        array('type' => 'autocomplete',             'megn' => 'Szállító:',                      'name' => 'szallito_id',    'szuro' => 1,   'disabled' => 1,   'class' => 'oriasi disableNav', 'tabla' => 'szallito', 'oszlop_keres' => 'id', 'id_kiir' => 'id', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev'),
        array('type' => 'text',                     'megn' => 'Képfájl:',                       'name' => 'kepfajl',        'szuro' => 0,   'disabled' => 1,   'style' => 'width: 18rem;'),
    );
}

// Anyagigénylésnél a feltöltésnek az űrlapja minimalizált
$anyagigenyles_tomb_feltolt = array(
    array('type' => 'select_felh_telephely',    'megn' => 'Szálloda:',                      'name' => 'telephely_id',   'class' => 'disableNav',   'style' => 'width: 100%;', 'alert' => 'Kérem válasszon szállodát!', 'onchange' => 'szallodaVane( this );'),
    array('type' => 'autocomplete',             'megn' => 'Cikk név:',                      'name' => 'nev',            'class' => 'oriasi disableNav', 'tabla' => 'cikktorzs', 'id_kiir' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'alert' => 'Kérem adja meg a kívánt cikket!'),
    array('type' => 'text',                     'megn' => 'Igényelt mennyiség:',            'name' => 'igmen',          'class' => 'f_num f_anyagig_menny_feltolt', 'post' => '<span class="insertMee" style="margin-left: 0.5rem"></span><span style="margin-left: 2rem"></span>', 'osszevon' => 1, 'alert' => 'Kérem adja meg a mennyiséget!'),
    array('type' => 'checkbox',                 'megn' => 'RB-s anyag:',                    'name' => 'kulsosnek'),
    array('type' => 'textarea',                 'megn' => 'Leírás:',                        'name' => 'igleir',         'class' => 'oriasi', 'style' => 'height: 4rem'),
);


// Anyagrendelés
$megnevezes = 'anyagrendeles';
$anyagrendeles_tomb = $akt_tomb = array(

);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));



//Raktár
$megnevezes = 'raktar';
$raktar_tomb = $akt_tomb = array(

);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));




// Törzsadatok
$megnevezes = 'torzsadat';
$torzsadat_tomb = $akt_tomb = array(
    array('type' => 'checkbox',             'megn' => 'Aktív:',                 'name' => 'aktiv',                                  'szuro' => 1,   'class' => 'check'),
	array('type' => 'text',                 'megn' => 'Munkavállaló neve:',     'name' => 'nev',            'maxlength' => 250,     'szuro' => 1,   'class' => 'oriasi',            'alert' => 'Kérjük adja meg a munkavállaló nevét!'),
	array('type' => 'text',                 'megn' => 'Születési ideje:',       'name' => 'szul_datum',                             'szuro' => 1,   'class' => 'kicsi datepicker',  'alert' => 'Kérjük adja meg a születés idejét!'),
	array('type' => 'text',                 'megn' => 'Szakmája:',              'name' => 'szakma',         'maxlength' => 250,     'szuro' => 1,   'class' => 'oriasi'),
    array('type' => 'select_szemelyzet',    'megn' => 'Beosztása:',             'name' => 'szemelyzet_id',                          'szuro' => 1,   'class' => 'disableNav',        'alert' => 'Kérjük adja meg a beosztást!'),
	array('type' => 'text',                 'megn' => 'Munkaviszony kezdete:',  'name' => 'munka_kezd',                             'szuro' => 1,   'class' => 'kicsi datepicker',  'alert' => 'Kérjük adja meg a munkaviszony kezdetét!'),
	array('type' => 'text',                 'megn' => 'Kompetenciák:',          'name' => 'komp',           'maxlength' => 250,     'szuro' => 1,   'class' => 'oriasi'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));



// Beosztások
$disabled = '';
if(!isset($_GET['uj'])){
    $disabled = '1';
}
$megnevezes = 'beosztas';
$beosztas_tomb = $akt_tomb = array(
	array('type' => 'text',                     'megn' => 'Cím:',               'name' => 'cim',                                        'szuro' => 1,   'class' => 'oriasi',       'maxlength' => 250),
    array('type' => 'select_felh_telephely',    'megn' => 'Szálloda:',          'name' => 'telephely_id',   'disabled' => $disabled,    'szuro' => 1,   'class' => 'disableNav',    'style' => 'width: 100%;', 'alert' => 'Kérem válasszon szállodát!', 'onchange' => 'szallodaVane( this ); '),
	array('type' => 'text',                     'megn' => 'Időszak:',           'name' => 'tol',            'disabled' => $disabled,    'szuro' => 1,   'class' => 'kicsi datepicker', 'megn_szurobe' => 'Időpont tól', 'osszevon' => 1),
	array('type' => 'text',                     'megn' => ' - ',                'name' => 'ig',             'disabled' => $disabled,    'szuro' => 1,   'class' => 'kicsi datepicker', 'megn_szurobe' => 'Időpont ig'),
);
if(!isset($_GET['uj'])){
    array_push( $beosztas_tomb,
                array('type' => 'text',                     'megn' => 'Készítette:',        'name' => 'insert_usr',     'disabled' => 1,            'szuro' => 1,   'class' => 'oriasi'),
                array('type' => 'text',                     'megn' => 'Készítés ideje:',    'name' => 'insert_dt',      'disabled' => 1,            'szuro' => 1,   'class' => 'datetimepicker'),
                array('type' => 'text',                     'megn' => 'Utoljára mentette:', 'name' => 'update_usr',     'disabled' => 1,            'szuro' => 1,   'class' => 'oriasi'),
                array('type' => 'text',                     'megn' => 'Mentés ideje:',      'name' => 'update_dt',      'disabled' => 1,            'szuro' => 1,   'class' => 'datetimepicker')
            );
}
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));

$megnevezes = 'beosztas_naptar';
$beosztas_naptar_tomb = $akt_tomb = array(
	array('type' => 'text',                     'megn' => 'Időszak:',           'name' => 'tol',            'class' => 'datetimepicker', 'megn_szurobe' => 'Időszak tól', 'maxlength' => 16, 'alert' => 'Kérjük adja meg az időszak kezdetét!', 'osszevon' => 1   ),
	array('type' => 'text',                     'megn' => ' - ',                'name' => 'ig',             'class' => 'datetimepicker', 'megn_szurobe' => 'Időszak ig',  'maxlength' => 16, 'alert' => 'Kérjük adja meg az időszak végét!'                       ),
    array('type' => 'select_beosztas',          'megn' => 'Beosztás tipus:',    'name' => 'beosztas',       'class' => 'disableNav',       'onchange' => 'if($(this).val()==1){ $(\'.beosztas_rejt\').removeClass(\'dn\'); }else{ $(\'.beosztas_rejt\').addClass(\'dn\'); }',          'alert' => 'Kérjük adja meg a beosztás típusát!'),
    array('type' => 'select_szemelyzet',        'megn' => 'Szerepkör:',         'name' => 'szerepkor',      'class' => 'disableNav',      'dtr_class' => ' dn beosztas_rejt'),
    array('type' => 'autocomplete',             'megn' => 'Név:',               'name' => 'felh_loginnev',  'class' => 'disableNav',   'tabla' => 'torzsadat',  'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'id_kiir' => 'recid', 'alert' => 'Kérem adja meg a személy nevét!'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));



// Szabadságigény
$megnevezes = 'szabadsagigeny';
$szabadsagigeny_tomb = $akt_tomb = array(

);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));



// Projektigénylés
$megnevezes       = 'projektigenyles';
$megter_cel_rejt  = ' class="dn"';
$letrehozta_mikor = '';
if (isset($_GET['modosit'])) {
    $pi = $adatbazis_kapcsolat->s2('megter stat_id egy_tobb insert_dt insert_usr', $megnevezes, "`recid`='{$_GET['id']}'");
    if($pi['megter']){
        $megter_cel_rejt = '';
    }
    $letrehozta_mikor .= 'Létrehozta: <b>'.$pi['insert_usr'].' '.  str_replace('-', '.', substr($pi['insert_dt'], 0, 16)).'</b>-kor<br>';

    //Projektigényhez kell a legutolsó döntés, a döntés dátuma és a jóváhagyó
    $dontes = $adatbazis_kapcsolat->s2('insert_usr insert_dt', $megnevezes.'_statusz', "`projektigenyles_recid`='{$_GET['id']}' AND `stat_id` IN ('".PRIGENY_STAT_VARAKOZO."', '".PRIGENY_STAT_JOVAHAGYVA."', '".PRIGENY_STAT_ELUTASITVA."')", 'insert_dt desc', '1');
    if($dontes){
        $letrehozta_mikor .= 'Legutolsó döntés: <b>'.$dontes['insert_usr'].' '.  str_replace('-', '.', substr($dontes['insert_dt'], 0, 16)).'</b>-kor<br>';
    }

    $eltoterj_html = '';
    $eloterj = $adatbazis_kapcsolat->s2('eloterj_recid ', 'eloterj_fb_kapcs', "`projektigenyles_recid`='{$_GET['id']}'");
    if($eloterj){
        $eloterj = $adatbazis_kapcsolat->s2('ikt_szam', 'eloterj_fb', "`recid`='{$eloterj['eloterj_recid']}'");
        $eltoterj_html .= '<a href="eloterj_fb.php?id='.$eloterj['eloterj_recid'].'&amp;modosit" target="_blank"><b>'.$eloterj['ikt_szam'].'</b></a>';
    }
    $eloterj = $adatbazis_kapcsolat->s2('eloterj_recid ', 'eloterj_bod_kapcs', "`projektigenyles_recid`='{$_GET['id']}'");
    if($eloterj){
        $eloterj = $adatbazis_kapcsolat->s2('ikt_szam', 'eloterj_bod', "`recid`='{$eloterj['eloterj_recid']}'");
        $eltoterj_html .= '<a href="eloterj_bod.php?id='.$eloterj['eloterj_recid'].'&amp;modosit" target="_blank"><b>'.$eloterj['ikt_szam'].'</b></a>';
    }

    if($eltoterj_html){
        $letrehozta_mikor .= 'Előterjesztés: '.$eltoterj_html.'<br>';
    }

    $tobb_szalloda = '';
    if($pi['egy_tobb']){
        $tobb_szalloda = '1';
    }

}

$projektigenyles_tomb = $akt_tomb = array(
    array('type' => 'statusz',              'megn' => 'Státusz:',               'name' => 'stat_id',        'szuro' => 1,  'oldal' => $megnevezes),
    array('type' => 'text',                 'megn' => 'Azonosító:',             'name' => 'ikt_szam',       'szuro' => 1,       'class' => 'normal', 'disabled' => 1,   'style' => 'width: 10rem', 'osszevon' => 1, 'post' => '<span style="margin-right: 4rem"></span>'),
    array('type' => 'checkbox',             'megn' => 'Rendkívüli:',            'name' => 'rendk',          'szuro' => 1,       'class' => 'check',  'osszevon' => 1, 'post' => '<span style="margin-right: 4rem"></span>'),
    array('type' => 'checkbox',             'megn' => 'Megtérülő:',             'name' => 'megter',         'szuro' => 1,       'class' => 'check',  'disabled' => 1),
    array('type' => 'text',                 'megn' => 'Név:',                   'name' => 'nev',            'maxlength' => 250, 'class' => 'oriasi csillag'),
array('type' => 'hidden','megn' => '',      'name' => 'egy_tobb'),
array('type' => 'html',                     'megn' => nl2('
                                                <div class="dtr">
                                                    <div class="dtc form_bal">
                                                        <span></span>
                                                    </div>
                                                    <div class="dtc form_jobb">
                                                        Egyszállodás
                                                        <div class="cb">
                                                            <input id="egy_tobb_egy" name="egy_tobb_egy" tabindex="15" type="checkbox" class="f_pri_egytobb" data-egytobb="egy">
                                                            <label for="egy_tobb_egy" onclick="$(\'#mehet_gomb\').addClass(\'gombb_alert\');"></label>
                                                        </div>
                                                        <span class="formText" style="margin-left: 4rem"></span>

                                                        Többszállodás
                                                        <div class="cb">
                                                            <input id="egy_tobb_tobb" name="egy_tobb_tobb" tabindex="16" type="checkbox" class="f_pri_egytobb" data-egytobb="tobb">
                                                            <label for="egy_tobb_tobb" onclick="$(\'#mehet_gomb\').addClass(\'gombb_alert\');"></label>
                                                        </div>

                                                    </div>
                                                </div>
    ')),
    array('type' => 'select_felh_telephely','megn' => 'Szálloda:',    'tobb_szalloda' => $tobb_szalloda,           'name' => 'telephely_id',   'szuro' => 1,       'class' => 'disableNav',        'style' => 'width: 100%;', 'alert' => 'Kérem válasszon szállodát!', 'onchange' => 'szallodaVane( this );'),
array('type' => 'html',                     'megn' => nl2('
                                                        <div class="dtr">
                                                            <div class="dtc form_bal">
                                                                Becsült költsége:
                                                            </div>
                                                            <div class="dtc">
                                                                <div class="dtc" style="padding-left: 0; padding-right: 4rem; white-space: nowrap;">
                                                                ')),
    array('type' => 'text',                 'megn_szurobe' => 'Becsült költség:', 'name' => 'bkolt',          'szuro' => 1,       'class' => 'oriasi f_num csillag', 'data' => 'data-tort="0"', 'style' => 'width: 11.5rem; margin-left: -0.2rem;', 'onblur' => 'becsult_szetosztott_ujraszamol();', 'post' => ' '.get_penznem()),
array('type' => 'html',                     'megn' => nl2('
                                                                </div>
                                                                <div id="" class="dtc f_prigeny_tipus" style="padding-right: 4rem">
                                                            ')),
    array('type' => 'select_pri_tipus',     'megn' => 'Típus:',         'name' => 'tipus',          'szuro' => 1,       'class' => 'disableNav', 'onchange' => 'prigeny_tipus_beallit()'),
array('type' => 'html',                     'megn' => nl2('
                                                                </div>
                                                                <div id="besorolas_panel" class="dtc" style="padding-right: 4rem; display: none">
                                                            ')),
    array('type' => 'select_pri_besorol',   'megn' => 'Besorolás:',     'name' => 'besorol',        'szuro' => 1,       'class' => 'disableNav'),
array('type' => 'html',                     'megn' => nl2('
                                                                </div>
                                                            </div>
                                                        </div>
                                                                ')),
    array('type' => 'autocomplete',         'megn' => 'Prioritás:',             'name' => 'prior',      'class' => 'oriasi disableNav', 'szuro' => 1, 'tabla' => 'erteklista', 'oszlop_keres' => 'id', 'oszlop_keres_autocomp' => 'info', 'listaz' => 'info', 'csoport' => 36, 'id_kiir' => 'id'),
    array('type' => 'textarea',             'megn' => 'Leírása:',               'name' => 'text',       'szuro' => 1,      'class' => 'oriasiTargy disableNav'),
array('type' => 'html',                     'megn' => nl2('
                                                        <div class="dtr">
                                                            <div class="dtc form_bal">
                                                            </div>
                                                            <div class="dtc form_jobb">
                                                                '.$letrehozta_mikor.'
                                                            </div>
                                                        </div>
                                                                ')),

);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));


$megnevezes          = 'projektkezeles';
$projektkezeles_tomb = $akt_tomb = array(
    array('type' => 'statusz',              'megn' => 'Státusz:',               'name' => 'stat_id',        'szuro' => 1,  'oldal' => $megnevezes),
    array('type' => 'text',                 'megn' => 'Azonosító:',             'name' => 'ikt_szam',       'szuro' => 1,   'disabled' => 1,   'class' => 'normal', 'style' => 'width: 10rem'),
    array('type' => 'text',                 'megn' => 'Név:',                   'name' => 'nev',            'maxlength' => 250, 'class' => 'oriasi'),
    array('type' => 'select_felh_telephely','megn' => 'Szálloda:',              'name' => 'telephely_id',   'szuro' => 1,       'class' => 'disableNav',        'style' => 'width: 100%;', 'alert' => 'Kérem válasszon szállodát!', 'onchange' => 'szallodaVane( this );'),
    array('type' => 'select_pri_tipus',     'megn' => 'Tipus:',                 'name' => 'tipus',          'szuro' => 1,       'class' => 'disableNav'),
    array('type' => 'text',                 'megn' => 'Aktiválási adatok:',     'name' => 'akt_adat',       'maxlength' => 250, 'class' => 'oriasi'),
    array('type' => 'text',                 'megn' => 'Prioritás:',             'name' => 'prior',          'szuro' => 1,       'class' => 'oriasi'),
    array('type' => 'select_pri_besorol',   'megn' => 'Besorolás:',             'name' => 'besorol',        'szuro' => 1,       'class' => 'disableNav'),
    array('type' => 'checkbox',             'megn' => 'Rendkívüli:',            'name' => 'rendk',          'szuro' => 1,       'class' => 'check'),
    array('type' => 'checkbox',             'megn' => 'Megtérülő:',             'name' => 'megter',         'szuro' => 1,       'class' => 'check', 'onclick' => 'if($(this).prop(\'checked\')){ $(\'#megter_cel_div\').show(); }else{ $(\'#megter_cel_div\').hide(); }'),
array('type' => 'html',                     'megn' => nl2('
                                                        <div class="dtr">
                                                            <div class="dtc form_bal">
                                                            </div>
                                                            <div class="dtc form_jobb">
                                                                <div'.$megter_cel_rejt.' id="megter_cel_div">
                                                                        <fieldset style="margin: 0">
                                                                            <legend>Megtérülési cél:</legend>
                                                                ')),
    array('type' => 'select_pri_cel',       'megn' => 'Cél:',               'name' => 'megter_cel',     'class' => 'disableNav',     'szuro' => 1, 'osszevon' => 1, 'post' => '<span style="margin-left: 2rem"></span>'),
    array('type' => 'text',                 'megn' => 'Mértéke:',           'name' => 'mertek',         'class' => 'oriasi f_num', 'data' => 'data-tort="0"', 'post' => ' '.get_penznem()),
    array('type' => 'text',                 'megn' => 'Megtérülési adatok:','name' => 'megter_adat',   'maxlength' => 250, 'class' => 'oriasi'),
array('type' => 'html',                     'megn' => nl2('
                                                                    </fieldset>
                                                                </div>
                                                            </div>
                                                        </div>
                                                                ')),
    array('type' => 'textarea',             'megn' => 'Leírása:',               'name' => 'text',       'szuro' => 1,      'class' => 'oriasiTargy disableNav'),
    array('type' => 'text',                 'megn' => 'Becsült költsége:',      'name' => 'bkolt',      'szuro' => 1,      'class' => 'oriasi f_num', 'data' => 'data-tort="0"', 'style' => 'width: 11.5rem', 'post' => ' '.get_penznem()),
array('type' => 'html',                     'megn' => nl2('
                                                        <div class="dtr">
                                                            <div class="dtc form_bal">
                                                            </div>
                                                            <div class="dtc form_jobb">
                                                                '.$letrehozta_mikor.'
                                                            </div>
                                                        </div>
                                                                ')),
    array('type' => 'hidden',               'megn' => '',      'name' => 'projektigenyles_recid'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));


//Cash-flow
$megnevezes = 'cash_flow';
$cash_flow_tomb = $akt_tomb = array(

);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));


//FB előterjesztés
if (isset($_GET['modosit'])) {
    $ossz_kolts = '';
    $prik = $adatbazis_kapcsolat->s('projektigenyles_recid', 'eloterj_fb_kapcs', "`eloterj_recid`='{$_GET['id']}'");
    if(!$prik){
        $ossz_kolts = 'Még nincsenek csatolt projektigénylések.';
    }else{
        foreach($prik as $pri){
            $pri = $adatbazis_kapcsolat->s2('bkolt', 'projektigenyles_eloterj', "`recid`='{$pri['projektigenyles_recid']}'");
            $ossz_kolts += $pri['bkolt'];
        }
        $ossz_kolts = number_format($ossz_kolts, 0, '', ' ').' '.get_penznem();
    }
}
$megnevezes = 'eloterj_fb';
$eloterj_fb_tomb = $akt_tomb = array(
    array('type' => 'statusz',              'megn' => 'Státusz:',               'name' => 'stat_id',        'szuro' => 1,  'oldal' => $megnevezes),
    array('type' => 'text',                 'megn' => 'Azonosító:',             'name' => 'ikt_szam',       'szuro' => 1,  'disabled' => 1,   'class' => 'normal', 'style' => 'width: 11.5rem'),
    array('type' => 'text',                 'megn' => 'Név:',                   'name' => 'nev',            'maxlength' => 250, 'class' => 'oriasi'),
    array('type' => 'text',                 'megn' => 'Ülés dátuma:',           'name' => 'ules_datum',     'class' => 'kicsi datepicker'),
    array('type' => 'html',                 'megn' => nl2('
                                                        <div class="dtr">
                                                            <div class="dtc">
                                                                Összes költség:
                                                            </div>
                                                            <div class="dtc" style="padding-top: 2rem">
                                                                '.$ossz_kolts.'
                                                            </div>
                                                        </div>
                                                    ')),

);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));


//BoD előterjesztés
if (isset($_GET['modosit'])) {
    $ossz_kolts = '';
    $prik = $adatbazis_kapcsolat->s('projektigenyles_recid', 'eloterj_bod_kapcs', "`eloterj_recid`='{$_GET['id']}'");
    if(!$prik){
        $ossz_kolts = 'Még nincsenek csatolt projektigénylések.';
    }else{
        foreach($prik as $pri){
            $pri = $adatbazis_kapcsolat->s2('bkolt', 'projektigenyles_eloterj', "`recid`='{$pri['projektigenyles_recid']}'");
            $ossz_kolts += $pri['bkolt'];
        }
        $ossz_kolts = number_format($ossz_kolts, 0, '', ' ').' '.get_penznem();
    }
}
$megnevezes = 'eloterj_bod';
$eloterj_bod_tomb = $akt_tomb = array(
    array('type' => 'statusz',              'megn' => 'Státusz:',               'name' => 'stat_id',        'szuro' => 1,  'oldal' => $megnevezes),
    array('type' => 'text',                 'megn' => 'Azonosító:',             'name' => 'ikt_szam',       'szuro' => 1,  'disabled' => 1,   'class' => 'normal', 'style' => 'width: 11.5rem'),
    array('type' => 'text',                 'megn' => 'Név:',                   'name' => 'nev',            'maxlength' => 250, 'class' => 'oriasi'),
    array('type' => 'text',                 'megn' => 'Ülés dátuma:',           'name' => 'ules_datum',     'class' => 'kicsi datepicker'),
    array('type' => 'html',                 'megn' => nl2('
                                                        <div class="dtr">
                                                            <div class="dtc">
                                                                Összes költség:
                                                            </div>
                                                            <div class="dtc" style="padding-top: 2rem">
                                                                '.$ossz_kolts.'
                                                            </div>
                                                        </div>
                                                    ')),

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




//Fenntartási szerződés
$megnevezes = 'fenntartasi_szerzodes';
$fenntartasi_szerzodes_tomb = $akt_tomb = array(

);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));



// Számlák
$megnevezes = 'szamla';
$szamla_tomb = $akt_tomb = array(

);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));





// Cikktörzs
if (isset($_GET['uj'])) {
    $cikkszam_hidden = array('type' => 'hidden',                     'megn' => '',              'name' => 'id',         'szuro' => 1,  'maxlength' => 7,   'class' => 'oriasi');
}
$megnevezes = 'cikktorzs';
$cikktorzs_tomb = $akt_tomb = array(
	array('type' => 'text',                     'megn' => 'Cikkszám:',              'name' => 'id',         'szuro' => 1,  'maxlength' => 7,   'class' => 'oriasi', 'alert' => 'Kérem töltse ki az cikkszámot!', 'disabled' => 1),
    $cikkszam_hidden,
	array('type' => 'text',                     'megn' => 'Név:',                   'name' => 'nev',        'szuro' => 1,  'maxlength' => 250, 'class' => 'oriasi', 'alert' => 'Kérem töltse ki a nevet!'),
	array('type' => 'select_oracikkid',         'megn' => 'Oracle cikkszám:',       'name' => 'oracikkid',  'szuro' => 1,  'maxlength' => 10,  'style' => 'width: 12rem',    'alert' => 'Kérem töltse ki az Oracle cikkszámot!'),
	array('type' => 'text',                     'megn' => 'Mee:',                   'name' => 'cikkmee',    'szuro' => 1,  'maxlength' => 5,   'style' => 'width: 5.1rem',   'alert' => 'Kérem töltse ki a Mee-t!'),
    array('type' => 'autocomplete',             'megn' => 'Szakterület:',           'name' => 'szakter_id', 'szuro' => 1,  'class' => 'oriasi disableNav', 'tabla' => 'erteklista', 'oszlop_keres' => 'id', 'oszlop_keres_autocomp' => 'info', 'listaz' => 'info', 'csoport' => 19, 'id_kiir' => 'id'),
    array('type' => 'textarea',                 'megn' => 'Leírás:',                'name' => 'leiras',     'szuro' => 1,  'class' => 'oriasiTargy disableNav'),
    array('type' => 'checkbox',                 'megn' => 'Szerelési segédanyag:',  'name' => 'segedanyag', 'szuro' => 1,  'class' => 'check'),
    array('type' => 'checkbox',                 'megn' => 'Helyi beszerzésű:',      'name' => 'helyi',      'szuro' => 1,  'class' => 'check'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));



// Gépek
$megnevezes = 'eszkozkezeles';
$eszkozkezeles_tomb = $akt_tomb = array(
	array('type' => 'text',                     'megn' => 'Név:',                   'name' => 'nev',            'maxlength' => 250, 'class'  => 'oriasi', 'alert' => 'Kérem töltse ki a nevet!'),
	array('type' => 'text',                     'megn' => 'Azonosító:',             'name' => 'azon',           'maxlength' => 20,  'alert'  => 'Kérem töltse ki az azonosítót!'),
    array('type' => 'select_felh_telephely',    'megn' => 'Szálloda:',              'name' => 'telephely_id',   'szuro' => 1,       'class'  => 'disableNav',  'style' => 'width: 100%;', 'alert' => 'Kérem válasszon szállodát!', 'onchange' => 'szallodaVane( this );'),
    array('type' => 'autocomplete',             'megn' => 'Helyiség:',              'name' => 'obj_id',                             'class'  => 'disableNav',  'style' => 'width: calc(100% - 3rem);', 'tabla' => 'obj', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'id_kiir' => 'recid', 'alert' => 'Kérem adja meg a Helyiséget!', 'onclick' => 'szallodaVane( this );', 'post' => '<a href="javascript: obj_gen(); void(0);" class="obj_gen"><i class="material-icons">reorder</i></a>'),
    array('type' => 'textarea',                 'megn' => 'Megjegyzés:',            'name' => 'megj',                               'class'  => 'oriasiTargy disableNav', 'feltoltes' => 'Images', 'width' => '100%', 'height' => '150'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));


// Objektumok
$megnevezes = 'obj';
$obj_tomb = $akt_tomb = array(
	array('type' => 'text',    'megn' => 'Név:',   'name' => 'nev',    'maxlength' => 250, 'class'  => 'oriasi', 'alert' => 'Kérem töltse ki a nevet!'),
);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));

// Felmérési terv
$megnevezes = 'felmeresi_terv';
$felmeresi_terv_tomb = $akt_tomb = array(
	array('type' => 'text',                     'megn' => 'Név:',                   'name' => 'nev',            'maxlength' => 250, 'class'  => 'oriasi', 'alert' => 'Kérem töltse ki a nevet!'),
	array('type' => 'text',                     'megn' => 'Azonosító:',             'name' => 'azon',           'maxlength' => 20,  'alert' => 'Kérem töltse ki az azonosítót!'),
    array('type' => 'select_felh_telephely',    'megn' => 'Szálloda:',              'name' => 'telephely_id',   'szuro' => 1,       'class'  => 'disableNav',  'style' => 'width: 100%;', 'alert' => 'Kérem válasszon szállodát!', 'onchange' => 'szallodaVane( this );'),
    array('type' => 'autocomplete',             'megn' => 'Emelet:',                'name' => 'obj_id',                             'class'  => 'disableNav',  'style' => 'width: calc(100% - 3rem);', 'tabla' => 'obj_csakszint', 'oszlop_keres' => 'recid', 'oszlop_keres_autocomp' => 'nev', 'listaz' => 'nev', 'id_kiir' => 'recid', 'alert' => 'Kérem adja meg a Helyiséget!', 'onclick' => 'szallodaVane( this );', 'post' => '<a href="javascript: obj_gen(\'csak szintig\'); void(0);" class="obj_gen"><i class="material-icons">reorder</i></a>'),
    array('type' => 'jsfile',                   'megn' => 'Felmérési terv:',        'name' => 'felm_terv',      'tabla' => $megnevezes,  'oszlop' => 'felm_terv',   'algoritmus' => 'fajlnev',   'meret' => 31457280, 'eng_kit' => '.pdf,.dwg'),

);
array_push($postok, array('tomb' => $akt_tomb, 'uj' => 'uj'.$megnevezes, 'lista' => $megnevezes.'lista', 'tabla' => $megnevezes, 'torid' => $megnevezes.'tid' ));
//{KOVETKEZO_TOMB}





// Elfelejtett jelszó igénylés
if (isset($_POST['elfelejtett_jelszo'])) {
    
    $login = $adatbazis_kapcsolat->s2('recid', 'login', "`nev`='{$_POST['nev']}'");
    $hiba  = level_felkeszit_es_kuld('login', $login['recid'], ERT_ELFELEJTETT_JELSZO, 'elfelejtettJelszo', 'elfelejtettJelszo');
    if($hiba){
        echo json_encode(array(
            'hiba' => $hiba
        ));
    }
    
    $weblap->leallit();
    exit();
}

// Összetett szűrés törlése, ha menüpontot váltunk
$kivetelek = array('header.php', 'esemenyek.php');
if( !in_array(basename(self()), $kivetelek) ){
    if($_SESSION['o_szuro_feltetel'] && $_SESSION['elozo_oldal']!=self()){
        unset($_SESSION['o_szuro_feltetel']);
        unset($_SESSION['o_szuro_html']);
    }
    $_SESSION['elozo_oldal'] = self();
}
// Összetett szűrés inicializálása
if (isset($_POST['o_szures'])) {
    require_once 'szures.php';

    echo json_encode(array(
        'inputok' => $szures,
        'html'    => $_SESSION['o_szuro_html'],
    ));

    $weblap->leallit();
    exit();
}
// Összetett szűrés SQL-é alaktíása
if (isset($_POST['f_o_szures_submit'])) {

    $sql = 'SELECT * FROM `'.get_db_tabla().$_POST['tabla'].'` WHERE '.get_o_szures_where_kieg($_POST['feltetel'], $_POST['tabla']);
    if(mysqli_query($db_kapcs, $sql)){
        $_SESSION['o_szuro_feltetel'] = $_POST['feltetel'];
        $_SESSION['o_szuro_html']     = $_POST['html'];
        echo json_encode(array(
            'mehet' => '1'
        ));
    }else{
        echo json_encode(array(
            'hiba' => 'A megfogalmazott feltétel hibás, nem futtatható le!'
        ));
    }

    $weblap->leallit();
    exit();
}
// Összetett szűrés törlése
if (isset($_POST['f_o_szures_torol'])) {
    unset($_SESSION['o_szuro_feltetel']);
    unset($_SESSION['o_szuro_html']);
    $weblap->leallit();
    exit();
}

// Bejelentés más nevében
if( isset($_GET['getBejBejelentok']) ) {
    $telephely_id = $_GET['telephely_id'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $telep_kapcsok = $adatbazis_kapcsolat->s('nev', 'telephely_kapcs', "`telephely_id` = '{$telephely_id}'");
    if( $telep_kapcsok === false ) { $db_error = true; }

    if( $telep_kapcsok ) {
        $telep_kapcsok = adatbazis2tomb($telep_kapcsok, 'nev');

        // Felhasználók lekérdezése
        $sql = $loginok = $adatbazis_kapcsolat->s('nev teljes_nev reszleg_id beosztas_id szemelyzet_id', 'login', "`nev` IN ('".implode('\' , \'', $telep_kapcsok)."') AND `aktiv` > '0' AND `szemelyzet_id`!=0");
        if( $sql === false ) { $db_error = true; }

        if( $loginok ) {
            $html = nl2('
                <div id="bejbejelentok" class="mobilkeret">
                    <table class="csikos egyErtek sorLink">
                        <thead>
                            <tr>
                                <th>
                                    Név
                                    <input type="text" class="flat" onkeyup="tabla_szur( $(\'#bejbejelentok table tbody\'), this.value );">
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
                    <tr class="kijSor" data-nev="'.$login['nev'].'">
                        <td>'.$login['teljes_nev'].'</td>
                        <td>'.erteklista(17, $login['reszleg_id']).'</td>
                        <td>'.erteklista(18, $login['beosztas_id']).'</td>
                        <td>'.getErtek('nev', 'szerepkor', 'recid', $login['szemelyzet_id']).'</td>
                    </tr>
                ');
            }

            $html .= nl2('
                    </tbody>
                    </table>
                </div>
                <script>
                function tabla_szur(tabla, kulcs, oszlop) {
                    var $rows = tabla.find(\'tr\');
                    var val = $.trim(kulcs).replace(/ +/g, \' \').toLowerCase();

                    $rows.show().filter(function() {
                        var text = $(this).find(\'td:nth-of-type(1)\').text().replace(/\s+/g, \' \').toLowerCase();
                        return !~text.indexOf(val);
                    }).hide();

                    if( $(\'table.egyErtek tr.kijSor.kijelolve\').is(\':hidden\') ){
                        $(\'#diszpFeltolt\').attr(\'disabled\', true);
                        $(\'#masnevebenFeltolt\').val(\'\');
                    }else{
                        $(\'#diszpFeltolt\').attr(\'disabled\', false);
                        $(\'#masnevebenFeltolt\').val( $(\'table.egyErtek tr.kijSor.kijelolve\').attr(\'data-nev\') );
                    }

                }
                </script>
                ');

            echo json_encode(array(
                'hiba'  => 'Válasszon hibabejelentőt a(z) '.getErtek('nev', 'telephely', 'id', $telephely_id).' szállodából!'.$html,
                'gomb'  => '<p class="c"><input id="diszpFeltolt" class="gomb zold" disabled type="button" value="Tovább" onclick="bejBejValaszt( '.$telephely_id.' );"><input class="gomb zold" type="button" value="Manuálisan" onclick="bejBejManualis( '.$telephely_id.' );"><input class="gomb piros" type="button" value="Mégsem" onclick="alert2()"></p>',
                'title' => 'Bejelentés más nevében',
            ));

            $weblap->leallit();
            exit();
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

    $weblap->leallit();
    exit();
}


// Csoport select
if( isset($_GET['csoportSelect']) ) {
    $page = $_GET['page'];

    // Lekérdezzük a csoportokat
    $html .= '<select id="csoportID" name="csoportID"><option value="">- Válasszon -</option>';

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

// Munkalap select
if( isset($_GET['munkalapSelect']) ) {
    $munkalapok = $adatbazis_kapcsolat->s('recid ikt_szam', 'munkalap', '', 'insert_dt desc', '');

    if( $munkalapok ) {
        $html = '<select id="munkalapID" name="munkalapID"><option value="">- Válasszon -</option>';

        foreach($munkalapok as $munkalap) {
            $munkalap_statusz = $adatbazis_kapcsolat->s('stat_id', 'munkalap_statusz', "`munkalap_recid`='{$munkalap['recid']}'", 'recid desc', '1');

            if( $munkalap_statusz ) {
                $munkalap_statusz = $munkalap_statusz[0];

                if( in_array($munkalap_statusz['stat_id'], array(MUNKALAP_STAT_NYITOTT, MUNKALAP_STAT_FOLYAMATBAN, MUNKALAP_STAT_VARAKOZO, MUNKALAP_STAT_ANYAGRAVAR)) ) {
                    $html .= '<option value="'.$munkalap['recid'].'">'.$munkalap['ikt_szam'].'</option>';
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

// Csoport átnevezése
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

// Várólistához adás és kivétel config
$idk = json_decode($_POST['idk'], true);
$page = $_POST['page'];

// Mik az egyes státusz ID-k?
$hibauzi_szoveg         = 'bejelentés';
$hibauzi_szoveg_targy   = 'bejelentést';
$nyitott_statid         = array(BEJELENTES_STAT_NYITOTT);
$varakozo_statid        = BEJELENTES_STAT_VARAKOZO;
if($page=='anyagigenyles'){
    $hibauzi_szoveg         = 'anyagigénylés';
    $hibauzi_szoveg_targy   = 'anyagigénylést';
    $nyitott_statid         = array(CIKKIGN_STAT_NYITOTT, CIKKIGN_STAT_ARAZVA);
    $varakozo_statid        = CIKKIGN_STAT_VARAKOZO;
}
if($page=='munkalap'){
    $hibauzi_szoveg         = 'munkalap';
    $hibauzi_szoveg_targy   = 'munkalapot';
    $varakozo_statid        = MUNKALAP_STAT_VARAKOZO;
}

// Várólistából kivétel
if( isset($_POST['varolistabolKivetel']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( is_array($idk) ) {
        // Ellenőrizzük a csoportokat
        $sql = $csoportok = $adatbazis_kapcsolat->s('csoport_id', $page, "`recid` IN (".implode(',', $idk).")", '', '');
        if( $sql === false ) { $db_error = true; }

        $csoportok = adatbazis2tomb($csoportok, 'csoport_id');
        $csoportok_unique = array_unique($csoportok);

        $kijelolt_db = array_count_values($csoportok);

        foreach( $csoportok_unique as $csu ) {
            if( $csu > 0 ) {
                $sql = $csoport_db = $adatbazis_kapcsolat->s('csoport_id', $page, "`csoport_id` = '{$csu}'", '', '');
                if( $sql === false ) { $db_error = true; }

                if( count($csoport_db) != $kijelolt_db[$csu] ) {
                    echo json_encode(array(
                        'hiba' => 'Csoportosított '.$hibauzi_szoveg.' önmagában nem vehető le várólistáról. Előbb vegye ki a csoportból!'
                    ));

                    $weblap->leallit();
                    exit();
                }
            }
        }

        // Ellenőrizzük a státuszokat is
        foreach($idk as $id) {
            $statusz = $adatbazis_kapcsolat->s('recid stat_id insert_dt', $page.'_statusz', "`{$page}_recid`='$id'", 'recid desc', '1');
            if( $statusz === false ) { $db_error = true; }

            if( $statusz ) {
                $statusz = $statusz[0];

                if( $statusz['stat_id'] != $varakozo_statid ) {
                    echo json_encode(array(
                        'hiba' => 'Csak várakozó státuszú '.$hibauzi_szoveg_targy.' lehet levenni a várakoztatásról!'
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

        // Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if (isset($_POST['csak_kerdezunk'])) {

            echo json_encode(array(
                'hiba' => 'Biztos kiveszi a várólistából a kijelölt '.$hibauzi_szoveg_targy.'?',
                'gomb' => '<input class="gomb zold" value="Igen" onclick="varolistabolKivetel();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }
        
        // Anyagigénylésnél a státuszt 
        if($page=='anyagigenyles'){
            $elozo_stat = $adatbazis_kapcsolat->s2('stat_id', 'anyagigenyles_statusz', "`anyagigenyles_recid`='{$id}'", 'recid desc', '1,1');

            $statusz = addStatusz( $page.'_statusz', $id, $elozo_stat['stat_id']);
            if( $statusz === false ) { $db_error = true; }
        
        // Általános eset
        }else{

            // Státuszt nyitott-ra állítjuk
            foreach($idk as $id) {
                $statusz = addStatusz( $page.'_statusz', $id, $nyitott_statid[0] );
                if( $statusz === false ) { $db_error = true; }

                // Munkalaphoz tartozó Bejelentések státuszát is várakozóra léptetjük
                if($page=='munkalap'){
                    $bejelentesek = $adatbazis_kapcsolat->s('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$id}'");
                    foreach($bejelentesek as $bej){
                        $statusz = addStatusz( 'bejelentes_statusz', $bej['bejelentes_id'], BEJELENTES_STAT_FOLYAMATBAN );
                        if( $statusz === false ) { $db_error = true; }
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
    }

    $weblap->leallit();
    exit();
}


// Bejegyzések elutasítása
if( isset($_POST['bejegyzesElutasitas']) ) {

    $tabla          = $_POST['tabla'];
    $elutasitas_id  = $_POST['elutasitas_id'];
    $erteklista     = $adatbazis_kapcsolat->s2('info', 'erteklista', "`recid`='{$elutasitas_id}'");
    $memo           = $erteklista['info']."\r\n".$_POST['memo'];
    $csak_memo      = $_POST['csak_memo'];
    $idk            = json_decode($_POST['idk'], true);

    switch($tabla){
        case 'bejelentes':

            // Jogosultság és hiba kezelés
            if(!$memo){
                echo json_encode(array(
                    'hiba' => 'Kérjük indokolja az elutasítást!',
                    'gomb' => '<input type="button" class="link gomb" value="Ok" onclick="alert2(); $(\'#elutasitas_memo\').focus();">',
                ));
                $weblap->leallit();
                exit();
            }

            if( !_jog(JOG_BEJELENTES_ELUTASIT) ){
                echo json_encode(array(
                    'hiba' => 'Önnek nincs joga bejelentést elutasítani!'
                ));
                $weblap->leallit();
                exit();
            }
            $van_munkalapja = $adatbazis_kapcsolat->s('recid', 'munkalap_kapcs', "`bejelentes_id` IN (".implode(',', $idk).")");
            if($van_munkalapja){
                echo json_encode(array(
                    'hiba' => 'A bejelentéshez már tartozik munkalap, így nem utasítható el!'
                ));
                $weblap->leallit();
                exit();
            }

            // Úgy is csak egy sort ad vissza, mert nem lehet több sort megjelölni
            $statusz = $adatbazis_kapcsolat->s2('stat_id', 'bejelentes', "`recid` IN (".implode(',', $idk).")");
            if($statusz['stat_id']==BEJELENTES_STAT_ELUTASITVA){
                echo json_encode(array(
                    'hiba' => 'A bejelentés már elutasított státuszban van!'
                ));
                $weblap->leallit();
                exit();
            }

            $statusz     = BEJELENTES_STAT_ELUTASITVA;
            $level_tabla = 'bejelentes';
            $level_jog   = ERT_BEJ_ELUTASITKOR;
        break;
        case 'projektigenyles':
            $statusz = PRIGENY_STAT_ELUTASITVA;
            if(!_jog(JOG_PRIGENY_ELUTASIT)){
                echo json_encode(array(
                    'hiba' => 'Nincs joga az elutasításhoz!'
                ));
                $weblap->leallit();
                exit();
            }

            // Úgy is csak egy sort ad vissza, mert nem lehet több sort megjelölni
            $stat_tmp = $adatbazis_kapcsolat->s2('stat_id', 'projektigenyles', "`recid` IN (".implode(',', $idk).")");
            if(in_array($stat_tmp['stat_id'], prigeny_fb_bod_var())){
                echo json_encode(array(
                    'hiba' => 'Ebben a státuszban nem utasítható el a projektigénylés!'
                ));
                $weblap->leallit();
                exit();
            }

            if(!$memo){
                echo json_encode(array(
                    'hiba' => 'Kérjük adja meg az elutasítás okát!',
                    'gomb' => '<input class="link gomb" value="Ok" onclick="alert2(); $(\'#elutasitas_memo\').focus()" type="button">',
                ));
                $weblap->leallit();
                exit();
            }

        break;
    }

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( ($elutasitas_id || $csak_memo) && is_array($idk) ) {
        foreach($idk as $id) {

            // Hozzáadjuk a megjegyzést is ha van
            // FONTOS, hogy előbb megjegyzés, majd csak utána a státusz, mert státusznál archiválunk és a megyjezést is archiválni kell
            if( $memo ) {
                $megj_felvesz = addMegj($tabla, $id, $memo);
                if( $megj_felvesz === false ) {
                    $db_error = true;
                }
            }

            if( addStatusz( $tabla.'_statusz', $id, $statusz, $elutasitas_id, $memo ) === false ) {
                $db_error = true;
            }

        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            // Levélküldés
            if($level_tabla){

                $hiba = '';
                foreach($idk as $id){
                    $valasz = level_felkeszit_es_kuld($level_tabla, $id, $level_jog, 'bejelentesElutasit', 'bejelento');
                    if( substr($valasz, 0, 4)=='Hiba' ){
                        $hiba .= $valasz;
                    }
                }
                if($hiba){
                    echo json_encode(array(
                        'hiba'  => $hiba,
                        'title' => 'Hiba',
                    ));
                    $weblap->leallit();
                    exit();
                }
            }

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

// Csoportból törlés
if( isset($_POST['csoportbolTorol']) ) {
    $oszlop = $_POST['oszlop'];
    $idk    = json_decode($_POST['idk'], true);
    $page   = $_POST['page'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $oszlop && is_array($idk) ) {
        $csoportok = $adatbazis_kapcsolat->s('csoport_id', $page, "`recid` IN (".implode(',', $idk).")", '', '');
        if( $csoportok === false ) { $db_error = true; }

        $csoportok = adatbazis2tomb($csoportok, 'csoport_id');

        // Kiszedjük a duplikációkat
        $csoportok = array_unique($csoportok);

        // Ha legalább egy olyan elem van, aminek nincs is csoportja, akkor hibaüzenet
        foreach($csoportok as $csoport){
            if(!$csoport){
                switch($page){
                    case 'bejelentes'       : $bejegyzes_nev = 'Bejelentés';        break;
                    case 'munkalap'         : $bejegyzes_nev = 'Munkalap';          break;
                    case 'projektigenyles'  : $bejegyzes_nev = 'Projektigénylés';   break;
                }
                echo json_encode(array(
                    'hiba' => 'A kijelöltek között szerepel olyan '.$bejegyzes_nev.' is, amely nincs egyik csoportban sem!'
                ));
                $weblap->leallit();
                exit();
            }
        }

        // Frissítjük a megfelelő sorokat
        $sql = $adatbazis_kapcsolat -> u($page, array($oszlop), array(0), "`recid` IN (".implode(',', $idk).")");
        if( $sql === false ) { $db_error = true; }

        // Munkalapok csoportból törlésekor a státusza automatikusan nyitottra vált
        if($page=='munkalap'){
            foreach($idk as $id) {
                $statusz = addStatusz( $page.'_statusz', $id, MUNKALAP_STAT_NYITOTT);
                if( $statusz === false ) { $db_error = true; }

                // Munkalaphoz tartozó Bejelentések státuszát pedig folyamatbanra léptetjük
                if($page=='munkalap'){
                    $bejelentesek = $adatbazis_kapcsolat->s('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$id}'");
                    foreach($bejelentesek as $bej){
                        $statusz = addStatusz( 'bejelentes_statusz', $bej['bejelentes_id'], BEJELENTES_STAT_FOLYAMATBAN );
                        if( $statusz === false ) { $db_error = true; }
                    }
                }
            }
        }

        // Csoportok bejárása
        if( $csoportok ) {
            foreach($csoportok as $csoport) {
                $sql = $csoportok = $adatbazis_kapcsolat->s('recid', $page, "`csoport_id` = '{$csoport}' AND `torolve` = '0'", '', '1');
                if( $sql === false ) { $db_error = true; }

                if( !$csoportok ) {
                    $sql = $adatbazis_kapcsolat -> d($page.'_csoport', "`recid` = '{$csoport}'");
                    if( $sql === false ) { $db_error = true; }
                }
            }
        }

        // Select előállítása
        $html = '<select id="csoportID" name="csoportID"><option value="">- Válasszon -</option>';
        $sql = $csoportok = $adatbazis_kapcsolat->s('recid nev', $page.'_csoport', '', 'nev', '');
        if( $sql === false ) { $db_error = true; }

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
                'html'    => $html
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

// Csoportba rendezés
if( isset($_POST['csoportbaRendez']) ) {
    $csoportID = $_POST['csoportID'];
    $idk       = json_decode($_POST['idk'], true);
    $page      = $_POST['page'];

    switch($page){
        case 'bejelentes'       : $hibauzi_param = 'bejelentést';       break;
        case 'munkalap'         : $hibauzi_param = 'munkalapot';        break;
        case 'projektigenyles'  : $hibauzi_param = 'projektigénylést';  break;
    }

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $csoportID && is_array($idk) ) {
        $is_csoport = $adatbazis_kapcsolat->s('recid', $page, "`recid` IN (".implode(',', $idk).") AND `csoport_id` > '0'");
        if( $is_csoport === false ) {  $db_error = true; }

        // Ellenőrizzük, hogy azonos szállodához tartoznak-e a bejeletések. Ha nem: hiba
        $szallodak = $adatbazis_kapcsolat->s('distinct telephely_id', $page, "`recid` IN (".implode(',', $idk).")");
        if(count($szallodak)>1){
            echo json_encode(array(
                'hiba' => 'Csak azonos szállodához tartozót '.$hibauzi_param.' csoportosíthat!'
            ));
            $weblap->leallit();
            exit();
        }
        // Csoportban milyen szállodájú bejegyzések vannak? Csak ilyen adható hozzá.
        $egy_csoport_elem = $adatbazis_kapcsolat -> s2('recid', $page, "`csoport_id`='$csoportID'", '', '1');
        if($egy_csoport_elem){
            $telephelye = $adatbazis_kapcsolat->s2('telephely_id', $page, "`recid`='{$egy_csoport_elem['recid']}'");
            
            $akt_elem_telephelye = $adatbazis_kapcsolat->s2('telephely_id', $page, "`recid` IN (".implode(',', $idk).")", '', '1');
            
            if($telephelye['telephely_id']!=$akt_elem_telephelye['telephely_id']){
                $telephely = $adatbazis_kapcsolat->s2('nev', 'telephely', "`id`='{$telephelye['telephely_id']}'");
                
                echo json_encode(array(
                    'hiba' => 'A csoportba csak a következő szállodához tartozó '.$hibauzi_param.' vehet fel:<br>'.$telephely['nev']
                ));
                $weblap->leallit();
                exit();
            }
        }

        if( !$is_csoport ) {
            $sql = $adatbazis_kapcsolat -> u($page, array('csoport_id'), array($csoportID), "`recid` IN (".implode(',', $idk).")");
            if( $sql === false ) { $db_error = true; }

            // Munkalapok csoportba rendezésekor a státusza automatikusan várakozóra vált, és a bejelentéseinek is, és a hozzárendelt személyeztet, és szállítót
            if($page=='munkalap'){

                foreach($idk as $id) {

                    // Státusz léptetés
                    $statusz = addStatusz( $page.'_statusz', $id, MUNKALAP_STAT_VARAKOZO );
                    if( $statusz === false ) { $db_error = true; }

                    // Munkalap update
                    $sql = $adatbazis_kapcsolat->u('munkalap',  array('szemelyzet_id', 'mobil_loginnev', 'szallito_id'  ),
                                                                array(0,               '',               0              ),
                                                    "`recid`='{$id}'");
                    if( $sql === false ) { $db_error = true; }

                    // Munkalaphoz tartozó Bejelentések státuszát is várakozóra léptetjük
                    $bejelentesek = $adatbazis_kapcsolat->s('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$id}'");
                    foreach($bejelentesek as $bej){
                        $statusz = addStatusz( 'bejelentes_statusz', $bej['bejelentes_id'], BEJELENTES_STAT_VARAKOZO );
                        if( $statusz === false ) { $db_error = true; }
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
                'hiba' => 'Kérem, csak olyan '.$hibauzi_param.' jelöljön ki, <br>amelyek még nem rendelkeznek csoporttal!'
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

// Új csoport/várólista hozzáadása
if( isset($_GET['addCsoport']) ) {

    $nev  = $_GET['csoportnev'];
    $page = $_GET['page'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $csoport = $adatbazis_kapcsolat->s('nev', $page.'_csoport', "`nev` = '{$nev}'", '', '1');
    if( $csoport === false ) { $db_error = true; }

    if( !$csoport ) {
        $uj_csopid = $adatbazis_kapcsolat -> i($page.'_csoport',  array('nev', 'insert_dt',   'insert_usr',                            'update_usr'                            ),
                                                                  array($nev,  'NOW()',       $_SESSION[get_db_tabla().'teljes_nev'],  $_SESSION[get_db_tabla().'teljes_nev']  )
                                        );
        if( $uj_csopid === false ) { $db_error = true; }

        /* Select előállítása */
        $html = '<select id="csoportID" name="csoportID"><option value="">- Válasszon -</option>';

        $csoportok = $adatbazis_kapcsolat->s('recid nev', $page.'_csoport', '', 'nev', '');
        if( $csoportok === false ) { $db_error = true; }

        if( $csoportok ) {
            foreach($csoportok as $csoport) {
                $selected = '';
                if($csoport['recid']==$uj_csopid){
                    $selected = ' selected';
                }
                $html .= '<option value="'.$csoport['recid'].'"'.$selected.'>'.$csoport['nev'].'</option>';
            }
        }

        $html .= '</select>';

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            echo json_encode(array(
                'statusz' => 'siker',
                'html'    => $html
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

// Csoport nevére kattintva a jobb oldali rész tartalmának kiszámítása
if (isset($_POST['csoport_tartalom'])) {

    switch($_POST['tabla']){
        case 'bejelentes'       : $megnevezes = 'bejelentések';         break;
        case 'munkalap'         : $megnevezes = 'munkalapok';           break;
        case 'projektigenyles'  : $megnevezes = 'projektigénylések';    break;
    }

    $csoport_neve = $adatbazis_kapcsolat->s2('nev', $_POST['tabla'].'_csoport', "`recid`='{$_POST['csoport_id']}'");
    $html = nl2('
        <b>'.$csoport_neve['nev'].' csoportba tartozó '.$megnevezes.':</b>
        <table class="csikos" style="margin-top: 1rem">
            <tr>
                <th>Munkalap szám</th>
                <th>Munkalap készítés dátuma</th>
                <th>Telephely</th>
                <th>Helyiség</th>
                <th>Gép</th>
                <th>Bejelentett hiba</th>
            </tr>
        ');
    $munkalapok = $adatbazis_kapcsolat->s('recid ikt_szam telephely_id stat_id obj_id gep_id insert_dt', $_POST['tabla'], "`csoport_id`='{$_POST['csoport_id']}'");
    foreach($munkalapok as $munkalap){

        $telephely = $adatbazis_kapcsolat->s2('nev', 'telephely', "`id`='{$munkalap['telephely_id']}'");
        $obj       = $adatbazis_kapcsolat->s2('nev', 'obj', "`recid`='{$munkalap['obj_id']}'");
        $gep       = $adatbazis_kapcsolat->s2('nev', 'eszkozkezeles', "`recid`='{$munkalap['gep_id']}'");

        $bejelentes= $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$munkalap['recid']}'");
        $bejelentes= $adatbazis_kapcsolat->s2('hiba_id, hiba_text', 'bejelentes', "`recid`='{$bejelentes['bejelentes_id']}'");
        $hiba      = $adatbazis_kapcsolat->s2('info', 'erteklista', "`recid`='{$bejelentes['hiba_id']}'");
        $hiba      = $hiba['info'].'<br>'.nl2br2($bejelentes['hiba_text']);

        $html .= nl2('
            <tr>
                <td style="text-align: left">'.$munkalap['ikt_szam'].'</td>
                <td>'.str_replace('-', '.', substr($munkalap['insert_dt'], 0, 10)).'</td>
                <td style="text-align: left">'.$telephely['nev'].'</td>
                <td style="text-align: left">'.$obj['nev'].'</td>
                <td style="text-align: left">'.$gep['nev'].'</td>
                <td style="text-align: left">'.$hiba.'</td>
            </tr>
            ');
    }
    $html .= '</table>';

    echo '<div style="padding: 3rem">'.$html.'</div>';

    $weblap->leallit();
    exit();
}

// Kapcsolódó táblák - fülek alatti rekordok - törlése
if( isset($_POST['mellekTorol']) ) {

    $tabla  = $_POST['tabla'];
    $recid  = $_POST['recid'];
    $idk    = json_decode($_POST['idk'], true);

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    // Ki-ki csak a saját rekordját törölheti, hacsak nem a kivételekbe tartozó tábláról van szó
    $klivetelek = array('telephely_kapcs', 'munkalap_kapcs', 'projektigenyles_kapcs', 'projektkezeles_login', 'cash_flow');
    if( !in_array($tabla, $kivetelek) && !strstr($tabla, '_dok') ) {
        if( $sql = $adatbazis_kapcsolat->s('id', $tabla, "`recid` IN (".implode(',', $idk).") AND `loginnev`!='{$_SESSION[get_db_tabla().'user']}'")){
            echo json_encode(array(
                'hiba' => 'Más által létrehozott bejegyzés nem törölhető!'
            ));

            $weblap->leallit();
            exit();
        }
    }

    // Törlés tényleges lekezelése
    if( $tabla && is_array($idk) ) {

        // Munkalapok-bejelentések kapcsolata
        if( $tabla == 'munkalap_kapcs' ) {
            // Újra nyitott státuszú lesz a bejegyzés
            foreach($idk as $id) {
                
                // Ha nincs joga, ne tudjon törölni
                if( !_jog(JOG_MUNKALAP_TOROL) ) {
                    echo json_encode(array(
                        'hiba' => 'Önnek nincs joga bejelentést törölni!'
                    ));
                    $weblap->leallit();
                    exit();
                }
                
                // Megejgyzést is adunk hozzá
                $bejelentes = $adatbazis_kapcsolat->s2('ikt_szam', 'bejelentes', "`recid`='$id'");
                if(addMegj('munkalap', $recid, $bejelentes['ikt_szam'].' bejelentést töröltem a munkalapról')===false){
                    $db_error = true;
                }

                // Töröljük a csoportjukat, ha lenne
                $sql = $adatbazis_kapcsolat -> u('bejelentes', array('csoport_id'), array(0), "`recid` = '{$id}'");
                if( $sql === false ) { $db_error = true; }

                // Sorok törlése
                $sql = $adatbazis_kapcsolat -> d($tabla, "`munkalap_id` = '{$recid}' AND `bejelentes_id` = '{$id}'");
                if( $sql === false ) { $db_error = true; }
                
                // Ha a bejelentés nem szerepel más munkalapon, akkor státusza nyitott lesz
                $szerepel_mas_munkalapon = $adatbazis_kapcsolat->s('recid', $tabla, "`bejelentes_id`='{$id}'");
                if(!$szerepel_mas_munkalapon){
                    if( addStatusz( 'bejelentes_statusz', $id, BEJELENTES_STAT_NYITOTT ) === false ) {
                        $db_error = true;
                    }
                }
            }

        // Dokumentumok törlése
        } else if( strstr($tabla, '_dok') ) {

            // Korábban számított, hogy csak nyitott státuszban lehessen törölni
//            $statuszt_nem_kell_figyelni = array('eseti_fenntartas', 'eszkozkezeles', 'eszkozkezeles_eng', 'felmeresi_terv', 'utemezett_feladatok', 'rendszeres_karbantartas'); //Ezeknél a dokumentumainál nem figyeljük, hogy az apa tábla státusza nyitott-e
//            $apa_tabla = str_replace('_dok', '', $tabla);
//            if(!in_array($apa_tabla, $statuszt_nem_kell_figyelni)){
//                $sor = $adatbazis_kapcsolat->s2('stat_id', $apa_tabla, "`recid` = '{$recid}'");
//                if( $sor === false ) { $db_error = true; }
//                $statuszt_nem_kell_figyelni = false;
//            }else{
//                $statuszt_nem_kell_figyelni = true;
//            }

            //if( $sor || $statuszt_nem_kell_figyelni ) {

                //if( $sor['stat_id'] == '01' || $statuszt_nem_kell_figyelni ) {
                    $fajlok = $adatbazis_kapcsolat->s('recid utvonal', $tabla, "`recid` IN (".implode(',', $idk).") AND `loginnev`='{$_SESSION[get_db_tabla().'user']}'");
                    if( $fajlok === false ) { $db_error = true; }

                    if( $fajlok ) {
                        $mit      = 'A(z) '.str_replace('_dok', '', $tabla).' '.$recid.'. recordját módosította.';
                        $valtozas = 'A következő fájlokat törölte: <br>';

                        foreach($fajlok as $fajl) {
                            $utvonal = '../'.get_utvonal().csonkit($tabla, 4).'/'.$fajl['utvonal'];

                            if( is_file($utvonal) ) {
                                
                                // Töröljük a fájlt
                                unlink($utvonal);
                                remover('../'.get_utvonal().csonkit($tabla, 4).'/'.substr($fajl['utvonal'], 0, strrpos($fajl['utvonal'], '.')).'_*');

                                // Töröljük a sort is
                                $sql = $adatbazis_kapcsolat -> d($tabla, "`recid` = '".$fajl['recid']."'");
                                if( $sql === false ) { $db_error = true; }

                                // Log változás
                                $valtozas .= '<strong>'.$utvonal.'</strong><br>';
                            }
                        }

                        // Logoljuk
                        $sql = $adatbazis_kapcsolat -> i('log',
                            array('felh_id',                                'nev', 'valtozas', 'ip'                     ),
                            array($_SESSION[get_db_tabla().'login_recid'],  $mit,  $valtozas,  $_SERVER['REMOTE_ADDR']  )
                        );
                        if( $sql === false ) { $db_error = true; }
                    }
//                } else {
//                    echo json_encode(array(
//                        'hiba' => 'Csak nyitott státuszú bejegyzések dokumentumai törölhetőek!'
//                    ));
//                    $weblap->leallit();
//                    exit();
//                }
            //}

        // Előterjesztésnél csak nyitott státuszban törölhető projektigénylés
        } else if($tabla=='eloterj_fb_kapcs'){

            //Vegyük az első kijelölést, ebből már vissza tudjuk számolni, hogy melyik előterjesztésről van szó
            $eloterj = $adatbazis_kapcsolat->s2('eloterj_recid', 'eloterj_fb_kapcs', "`recid`='{$idk[1]}'");
            $statusz = $adatbazis_kapcsolat->s2('stat_id', 'eloterj_fb', "`recid`='{$eloterj['eloterj_recid']}'");
            if($statusz['stat_id']!=ELOT_FB_STAT_NYITOTT){
                echo json_encode(array(
                    'hiba' => 'Csak nyitott státuszban törölhetőek a csatolt projektigénylések!'
                ));

                $weblap->leallit();
                exit();
            }else{
                $sql = $adatbazis_kapcsolat -> d($tabla, "`recid` IN (".implode(',', $idk).")");
                if( $sql === false ) { $db_error = true; }
            }

        // Előterjesztésnél csak nyitott státuszban törölhető projektigénylés
        } else if($tabla=='eloterj_bod_kapcs'){

            //Vegyük az első kijelölést, ebből már vissza tudjuk számolni, hogy melyik előterjesztésről van szó
            $eloterj = $adatbazis_kapcsolat->s2('eloterj_recid', 'eloterj_bod_kapcs', "`recid`='{$idk[1]}'");
            $statusz = $adatbazis_kapcsolat->s2('stat_id', 'eloterj_bod', "`recid`='{$eloterj['eloterj_recid']}'");
            if($statusz['stat_id']!=ELOT_BOD_STAT_NYITOTT){
                echo json_encode(array(
                    'hiba' => 'Csak nyitott státuszban törölhetőek a csatolt projektigénylések!'
                ));

                $weblap->leallit();
                exit();
            }else{
                $sql = $adatbazis_kapcsolat -> d($tabla, "`recid` IN (".implode(',', $idk).")");
                if( $sql === false ) { $db_error = true; }
            }

        // Projektigénylés véleményező törlés esetén töröljük a véleményt (fájlt) is ha volt
        } else if($tabla=='projektigenyles_kapcs'){

            $velemenyezok = $adatbazis_kapcsolat->s('projektigenyles_recid, login_recid', $tabla, "`recid` IN (".implode(',', $idk).")");
            if( $velemenyezok === false ) { $db_error = true; }

            foreach($velemenyezok as $velemenyezo){
                $velemeny = $adatbazis_kapcsolat->s2('utvonal', 'projektigenyles_velemeny', "`projektigenyles_recid`='{$velemenyezo['projektigenyles_recid']}' AND `login_recid`='{$velemenyezo['login_recid']}'");
                if( $velemenyezok === false ) { $db_error = true; }

                if($velemeny){
                    $file = '../'.get_utvonal().$velemeny['utvonal'];
                    if(is_file($file)){
                        unlink($file);
                    }
                    $adatbazis_kapcsolat->d('projektigenyles_velemeny', "`projektigenyles_recid`='{$velemenyezo['projektigenyles_recid']}' AND `login_recid`='{$velemenyezo['login_recid']}'");
                }
            }

            $sql = $adatbazis_kapcsolat -> d($tabla, "`recid` IN (".implode(',', $idk).")");
            if( $sql === false ) { $db_error = true; }

            // Ha ellenőrizzük, hogy a megmaradtaknak mindnek van-e már vélemények. Státuszt kell léptetni ha igen.
            prigeny_mindenki_velemenyezett_e($velemenyezo['projektigenyles_recid']);

        //Általános eset
        } else {
            $sql = $adatbazis_kapcsolat -> d($tabla, "`recid` IN (".implode(',', $idk).")");
            if( $sql === false ) { $db_error = true; }
        }

        // Projektigényléseknél véleményezők törlésekor vissza is kell állítani a projektigénylés státuszát
        $pri_statusz_kerdez = false;
        if($tabla=='projektigenyles_kapcs'){
            // Ha nem maradt véleményező, akkor rákérdezünk, hogy menjen-e vissza nyitottba
            $maradt_velemenyezo = $adatbazis_kapcsolat->s('recid', 'projektigenyles_kapcs', "`projektigenyles_recid`='{$recid}'", '', '0,1');
            if( $maradt_velemenyezo === false ) { $db_error = true; }
            if(!$maradt_velemenyezo){
                $pri_statusz_kerdez = true;
            }
        }
        // Projektigényléseknél megtérülés törlésekor ellenőrizzük, hogy van-e még, mert ha nincs, az adatlapon ki kell venni a pipát
        if($tabla=='projektigenyles_megter'){
            $van_megter = $adatbazis_kapcsolat->s2('recid', $tabla, "`projektigenyles_recid`='{$_POST['recid']}'");
            if( $van_megter === false ) { $db_error = true; }
            if(!$van_megter){
                $sql = $adatbazis_kapcsolat->u('projektigenyles', array('megter'), array(0), "`recid`='{$_POST['recid']}'");
                if( $sql === false ) { $db_error = true; }
            }
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            if($pri_statusz_kerdez){
                echo json_encode(array(
                                    'statusz' => 'siker',
                                    //Ezt egyelőre kivettük, egybúl nyitottra vált'alert'   => 'Ehhez a projektigényléshez nem tartozik több véleményező.<br>Állítsuk át a státuszát nyitottra?',
                                    //'gomb'    => '<input class="gomb zold" type="button" value="Igen" onclick="pri_statusz_leptet(\''.$recid.'\', \''.PRIGENY_STAT_NYITOTT.'\')"> <input class="gomb piros" type="button" value="Nem" onclick="alert2()">',
                                ));

            }else{
                echo json_encode(array(
                                    'statusz' => 'siker'
                                ));
            }
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

// Objektumok generálása
if( isset($_GET['gepVane']) ) {
    $telephely_id   = $_GET['telephely_id'];
    $obj_id         = $_GET['obj_id'];

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

// Objektumok generálása
if( isset($_GET['obj_general']) ) {

    $telephely_id = $_GET['telephely_id'];
    $csak_szintig = 0;
    if($_GET['szintig']==1){
        $csak_szintig = 1;
    }
    echo obj_gen( $telephely_id, $csak_szintig );

    $weblap->leallit();
    exit();
}

// További beállítások
if( isset($_GET['naploTorol']) ) {
    $tabla = $_GET['tabla'];
    $sorID = $_GET['sorID'];

    if( $tabla && $sorID ) {
        $adatbazis_kapcsolat -> d($tabla, 'recid='.$sorID);
    }

    $weblap->leallit();
    exit();
}

// Megjegyzés hozzáadása
if( isset($_POST['addMegjegyzes']) ) {
    $pageID = $_POST['pageID'];
    $megj   = $_POST['megj'];
    $tabla  = $_POST['tabla'];

    if(!trim($megj)){

        echo json_encode(array(
            'hiba' => 'Nem adhat hozzá üres megjegyzést!',
            'gomb' => '<input class="link gomb" value="Ok" onclick="alert2(); $(\'#megjegyzes\').focus();" type="button">'
        ));

        $weblap->leallit();
        exit();
    }

    if( $pageID ) {
        if(addMegj( $tabla, $pageID, $megj )===false){

            echo json_encode(array(
                'hiba'    => 'Nem sikerült a megjegyzést hozzáadni!'
            ));
            $weblap -> leallit();
            exit;
        }

        echo json_encode(array(
            'statusz' => 'siker',
            'kapcsID' => $kapcsID
        ));
    }

    $weblap->leallit();
    exit();
}

// Szakterület hozzáadása
if( isset($_GET['addSzakterulet']) ) {
    $szallitoID = $_GET['szallitoID'];
    $szakterID  = $_GET['szakterID'];

    if( $szallitoID && $szakterID ) {
        $isKapcs = $adatbazis_kapcsolat->s('recid', 'szakter_kapcs', "`szallito_id` = '{$szallitoID}' AND `szakter_id` = '{$szakterID}'", '', '1');

        if( !$isKapcs ) {
            $kapcsID = $adatbazis_kapcsolat -> i('szakter_kapcs', array('szallito_id', 'szakter_id'), array($szallitoID, $szakterID));

            // Szakterület lekérdezése
            $sor = $adatbazis_kapcsolat->s('recid', 'erteklista', "`recid` = '{$szakterID}'", '', '1');

            if( $sor ) {
                echo json_encode(array(
                    'statusz' => 'siker',
                    'kapcsID' => $kapcsID,
                    'uzi'     => ''
                ));
            }
        } else {
            echo json_encode(array(
                'statusz' => 'hiba',
                'uzi'     => 'Ez a szakterület már hozzáadásra került!'
            ));
        }
    }

    $weblap->leallit();
    exit();
}

// Sűrgősség pipa lekezelése
if (isset($_POST['surgosseg_allit'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    $megnevezes = '';

    // Csak nyitott munkalap sürgőssége állítható!
    $statusz = $adatbazis_kapcsolat->s2('stat_id', $_POST['page'], "`recid`='{$_POST['recid']}'");
    if($_POST['page']=='bejelentes'){

        $megnevezes = 'bejelentés';
        if($statusz['stat_id']!=BEJELENTES_STAT_NYITOTT){
            echo json_encode(array(
                'hiba'  => 'Csak nyitott bejelentés sürgőssége állítható!',
            ));

            $weblap->leallit();
            exit();
        }
    }

    // Csak nyitott munkalap sürgőssége állítható!
    if($_POST['page']=='munkalap'){

        $megnevezes = 'munkalap';

        /*// Státusz ellenőrzés
        if( $statusz['stat_id']!=MUNKALAP_STAT_NYITOTT ){
            echo json_encode(array(
                'hiba'  => 'Csak nyutott munkalap sürgőssége állítható!',
            ));
            $weblap->leallit();
            exit();
        }*/

        // Jog ellenőrzése
        if( /*!_jog(JOG_MUNKALAP_NYITOTT_KEZEL) ||*/ !_jog(JOG_MUNKALAP_SURGOS_PIPAL) ){
            echo json_encode(array(
                'hiba'  => 'Önnek nincs joga sürgősséget állítani!',
            ));

            $weblap->leallit();
            exit();
        }

    }

    // Indoklás ellenőrzése
    if(!trim($_POST['indoklas'])){
        echo json_encode(array(
            'hiba'  => 'Az indoklás kitöltése kötelező!',
        ));

        $weblap->leallit();
        exit();
    }

    // Jelenleg sürgős-e vagy sem
    $surgos = $adatbazis_kapcsolat->s2('surgos', $_POST['page'], "`recid`='{$_POST['recid']}'");
    if( $surgos === false ) { $db_error = true; }

    if($surgos['surgos']){
        $uj_stat_szoveg = 'nem sürgős';
        $surgos_allit   = 0;
    }else{
        $uj_stat_szoveg = 'sürgős';
        $surgos_allit   = 1;
    }

    // Adott rekordon cseréjük a sürgősséget
    $sql = $adatbazis_kapcsolat->u($_POST['page'], array('surgos'), array($surgos_allit), "`recid`='{$_POST['recid']}'");
    if( $sql === false ) { $db_error = true; }

    // Indoklás a megjegyzésekbe
    $indoklas = 'A '.$megnevezes.' sürgősségi státusza módosult: '.$uj_stat_szoveg."\n".$_POST['indoklas'];
    if( addMegj($_POST['page'], $_POST['recid'], $indoklas) === false ) {
        $db_error = true;
    }

    // Ha bejelentésen változtat, munkalapjait is megváltoztatjuk és beírjuk munkalapjai megjegyzésébe is.
    if($_POST['page']=='bejelentes'){
        $munkalapok = $adatbazis_kapcsolat->s('munkalap_id', 'munkalap_kapcs', "`bejelentes_id`='{$_POST['recid']}'");
        if( $munkalapok === false ) { $db_error = true; }
        foreach($munkalapok as $munkalap){
            $sql = $adatbazis_kapcsolat->u('munkalap', array('surgos'), array($surgos_allit), "`recid`='{$munkalap['munkalap_id']}'");
            if( $sql === false ) { $db_error = true; }

            if( addMegj('munkalap', $munkalap['munkalap_id'], $indoklas) === false ) {
                $db_error = true;
            }
        }
    }

    // Ha munkalapon változtat, bejelentéseit is megváltoztatjuk és beírjuk belejentések megjegyzésébe is.
    if($_POST['page']=='munkalap'){
        $bejelentesek = $adatbazis_kapcsolat->s('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$_POST['recid']}'");
        if( $bejelentesek === false ) { $db_error = true; }

        // Bár ez felesleges, mert egy munkalaphoz csak egy bejelentés tartozhat csak
        foreach($bejelentesek as $bejelentes){
            $sql = $adatbazis_kapcsolat->u('bejelentes', array('surgos'), array($surgos_allit), "`recid`='{$bejelentes['bejelentes_id']}'");
            if( $sql === false ) { $db_error = true; }

            if( addMegj('bejelentes', $bejelentes['bejelentes_id'], $indoklas) === false ) {
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

    $weblap->leallit();
    exit();
}

// Szálloda hozzáadása felhasználóhoz ajaxosan
if( isset($_GET['addSzalloda']) ) {
    
    $loginID  = $_GET['loginID'];
    $szallIDs = json_decode($_GET['szallIDs'], true);

    if( $loginID && count($szallIDs) ) {
        $felh = $adatbazis_kapcsolat->s2('nev', 'login', 'recid='.$loginID, '', '1');

        if( $felh ) {
            $adatbazis_kapcsolat -> d('telephely_kapcs', "`nev` = '".$felh['nev']."'");

            // Hozzáadjuk a szállodákat
            foreach($szallIDs as $key => $szallID) {
                $kapcsID = $adatbazis_kapcsolat -> i('telephely_kapcs',
                    array('nev',        'telephely_id', 'insert_dt',    'insert_usr',                           'update_usr'                            ),
                    array($felh['nev'], $szallID,       'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'], $_SESSION[get_db_tabla().'teljes_nev']  )
                );
            }

            echo json_encode(array(
                'statusz' => 'siker',
                'kapcsID' => $kapcsID,
                'uzi'     => ''
            ));
        } else {
            echo json_encode(array(
                'statusz' => 'hiba',
                'uzi'     => 'Ez a szálloda már hozzáadásra került!'
            ));
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

    $szamla_lista = szamla_lista('', $_GET['tabla'], $_GET['kijeloltek']);

    if( $szamla_lista ) {
        echo '<div class="szamlaLapozas fent clearFix">'.$lapozas.'</div>'.$szamla_lista.'<div class="szamlaLapozas clearFix">'.$lapozas.'</div>';
    } else {
        echo '<ul class="szamla_cont"><li class="szamla_datum"><strong>Nincs találat.</strong></li></ul>';
    }

    $weblap->leallit();
    exit();
}

// Select generálása ajaxxal adott login szállodáiról
if (isset($_POST['get_szalloda_select_options'])) {

    $szallodak = get_akt_login_szallodai();
    $szallodak = $adatbazis_kapcsolat->s('id nev', 'telephely', "`id` IN (".implode(',', $szallodak).") AND `inaktiv`=0", 'nev');

    echo json_encode(array(
        'html'            => tomb2option($szallodak, 'id', 'nev'),
        'szallodak_szama' => count($szallodak)
    ));

    $weblap->leallit();
    exit();
}


// Kell-e kétszintű kategóriát kezelni vagy sem
$ketszintu_kat = false;

// Változók mentése SESSION-ba és újrabetöltés, hogy a kiválasztott lista rendezése és szűkítése megmaradjon
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
if( !$_GET['epo'] )         { $_GET['epo'] = 50;    }
if( !isset($_GET['oldal'])) { $_GET['oldal']=0;     }


// ESEMÉNYEK
// Flashfáljok törlése
if(isset($_POST['fajl_torol'])){
    
    // Ha ismert az ID
    if( is_numeric($_POST['id']) ){
        $elem = $adatbazis_kapcsolat -> s2($_POST['oszlop'], $_POST['tabla'], 'recid='.$_POST['id']);
        kep_torol('../'.get_utvonal().$_POST['tabla'].'/'.$elem[$_POST['oszlop']]);
        $adatbazis_kapcsolat -> u($_POST['tabla'], $_POST['oszlop'], '', 'recid='.$_POST['id']);
        
    // Egyébként a cache fájlokat töröljük
    }else{
        
        // Ha a post tábla egy konkrét útvonalat tartalmaz, akkor csak azt töröljük.
        if( is_file($_POST['tabla']) ){
            unlink($_POST['tabla']);
        }else{
            $utvonal = '../'.get_utvonal().'cache/jsfajlok/'.session_id().'*';
            $session_kezdetu_fajlok = glob($utvonal);
            foreach($session_kezdetu_fajlok as $utvonal){
                unlink($utvonal);
            }
        }
        
    }

    $weblap -> leallit();
    exit;
}

// Admin fájlfeltöltés thumb kinyerése
if (isset($_GET['get_admin_file_thumb'])) {
    echo get_admin_file_thumb($_GET['tabla'], $_GET['oszlop'], $_GET['id']);
    $weblap -> leallit();
    exit;
}
// Képek fájlok, fileok kiírátása
function get_admin_file_thumb($tabla, $oszlop, $id){

    global $adatbazis_kapcsolat;
    
    // Fájl elérési útvonalának kiszámítása
    $utvonal = $adatbazis_kapcsolat->s2($oszlop, $tabla, "`recid`='$id'");
    $utvonal = '../'.get_utvonal().$tabla.'/'.$utvonal[$oszlop];
    
    // Ha nem található a fájl akkor lehet új feltötlésről van szó (ekkor nincs $id-nk sem), akkor próbáljuk meg cache mappából dolgozni.
    $cache_file = false;
    if(!is_file($utvonal) && !$id){
        // Nézzük meg, hogy van-e SESSION id kezdetű fájl a könyvtárban
        $utvonal = '../'.get_utvonal().'cache/jsfajlok/'.session_id().'*';
        $session_kezdetu_fajlok = glob($utvonal); //Ha több ilyen is van az azért lehet, mert több fájlfeltöltés input is volt a tömbben
        $utvonal = $session_kezdetu_fajlok[0];
        if(is_file($utvonal)){
            $cache_file = true;
        }
    }
    
    if(is_file($utvonal)){

        $neve = basename($utvonal);
        $kit = kit($neve);
        
        if(is_file('../elemek/ikonok/'.$kit.'.png')){
            $akt_fajl = '../elemek/ikonok/'.$kit.'.png';
        }else{
            $akt_fajl = '../elemek/ikonok/ismeretlen.png';
        }
        $fajlnev = basename($utvonal);
        if($cache_file){
            $return = '';
            $i = 0;
            foreach($session_kezdetu_fajlok as $utvonal){
                
                $neve = basename($utvonal);
                $kit = kit($neve);

                if(is_file('../elemek/ikonok/'.$kit.'.png')){
                    $akt_fajl = '../elemek/ikonok/'.$kit.'.png';
                }else{
                    $akt_fajl = '../elemek/ikonok/ismeretlen.png';
                }
                
                list($x, $x, $x, $fajlnev, $x) = explode('|||', $utvonal);
                
                $return .= nl2('
                        <div id="fajl_'.$tabla.'_'.$oszlop.'_'.$id.'">
                            <script>var gombfajl_'.$oszlop.'_'.$i.'=get_fajl_torol_gomb2(\'sorszam_'.$i.'\', \''.$utvonal.'\', \''.$oszlop.'\');</script>
                            <div id="fajl_'.$oszlop.'_sorszam_'.$i.'">

                                <div class="file_thumb">
                                    <div class="dtc vam" title="Fájl letöltése">
                                        <a href="'.$utvonal.'" target="_blank">
                                            <img alt="'.htmlspecialchars($sor['nev']).'" src="'.$akt_fajl.'" width="25" height="25">
                                        </a>
                                    </div>
                                    <div class="dtc vam f13 full">
                                        <a href="'.$utvonal.'" target="_blank">
                                            '.$fajlnev.'
                                        </a>
                                    </div>
                                    <div class="dtc vam link" onclick="alert2(\'Biztosan folytatja a csatolt fájl törlését?\', gombfajl_'.$oszlop.'_'.$i.')" title="Fájl törlése">
                                       <i class="material-icons">delete</i>
                                    </div>
                                </div>

                            </div>
                        </div>
                    ');
                $i++;
            }
            
            return $return;
        }

        return nl2('
                <div id="fajl_'.$tabla.'_'.$oszlop.'_'.$id.'">
                    <script>var gombfajl_'.$oszlop.'_'.$id.'=get_fajl_torol_gomb(\''.$id.'\', \''.$tabla.'\', \''.$oszlop.'\');</script>
                    <div id="fajl_'.$oszlop.'_'.$id.'">

                        <div class="file_thumb">
                            <div class="dtc vam" title="Fájl letöltése">
                                <a href="'.$utvonal.'" target="_blank">
                                    <img alt="'.htmlspecialchars($sor['nev']).'" src="'.$akt_fajl.'" width="25" height="25">
                                </a>
                            </div>
                            <div class="dtc vam f13 full">
                                <a href="'.$utvonal.'" target="_blank">
                                    '.$fajlnev.'
                                </a>
                            </div>
                            <div class="dtc vam link" onclick="alert2(\'Biztosan folytatja a csatolt fájl törlését?\', gombfajl_'.$oszlop.'_'.$id.')" title="Fájl törlése">
                                <i class="material-icons">delete</i>
                            </div>
                        </div>

                    </div>
                </div>
            ');
        
    }
}

// Képforgatás lekezelése
if (isset($_GET['kep_forgat'])) {
    echo kep_forgat($_GET['utvonal'], $_GET['szazalek']).'?'.time();
    $weblap -> leallit();
    exit;
}

// Sorrend mentése
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

foreach($postok as $ertek){
	// Új feltételt használok, hogy feleslegesen ne kelljen minden tömböt bejárni. Csak az aktuális php fájl nevével megegyező tömböt pászátzza végig
    // if(isset($_POST[$ertek['uj']]) || isset($_POST[$ertek['lista']])){//ÚJ TERMÉK FELTÖLTÉSE
	if( (isset($_POST[$ertek['uj']]) || isset($_POST[$ertek['lista']])) ){

		// Checkboxból 1 és 0, számok ezresítése
		foreach($ertek['tomb'] as $elem){
            if($elem['type'] == 'checkbox'){
                if($_POST[$elem['name']]=='on' || $_POST[$elem['name']]=='true'){
                    $_POST[$elem['name']] = 1;
                }else{
                    $_POST[$elem['name']] = 0;
                }

                // Pipa ki-be kapcsolás, Ajaxos hívással
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

		// Ha az újként szerkesztés post nem üres, akkor beállítjuk a $_POST-olt értékeket egy SESSION-ba, majd ezzekkel az adatokkal indul egy új feltöltés adatlapja
		if($_POST[$ertek['tabla'].'ujkent_szerk']) {
            if( $_GET['ujkent'] ) {
                $id = $_GET['ujkent'];
            } else {
                $id = $_GET['id'];
            }

            $sor = $adatbazis_kapcsolat -> s2('*', $ertek['tabla'], 'recid='.$id);
            if( $sor ) {

                foreach($ertek['tomb'] as $akt_tomb_ertek) {
                    if( $akt_tomb_ertek['name'] != 'ikt_szam' ) {
                        $_SESSION[$akt_tomb_ertek['name']] = stripslashes( $sor[$akt_tomb_ertek['name']] );
                    } else {
                        $_SESSION[$akt_tomb_ertek['name']] = '';
                    }
    			}

                $adatbazis_kapcsolat -> bezar();
    			header('location:'.self().'?uj&ujkent='.$_GET['id']);
    			exit();
            } else {
                $adatbazis_kapcsolat -> bezar();
    			header('location:'.self());
    			exit();
            }
		}

		if(isset($_POST[$ertek['uj']])){

			$u_i_ret  = i_u('insert',$ertek['tabla'],$ertek['tomb']);
			$errnum   = $u_i_ret['0'];
			$tabla_id = $u_i_ret['1'];
			$uj_lista   = 'uj';
            
		}elseif(isset($_POST[$ertek['lista']])){
            
            if( isset($_POST['obj']) ) {
                $ertek['tabla'] = 'obj';
                $ertek['tomb']  = $obj_tomb;
            }

			$u_i_ret  = i_u('update',$ertek['tabla'],$ertek['tomb']);
			$errnum   = $u_i_ret['0'];
			$tabla_id = $u_i_ret['1'];
			$uj_lista   = 'lista';
            
		}

		// KÉPEK
		// Hír-Kép feltöltése
		$kephiba = kep_menedzser('termek_kep', $ertek['tomb'], $ertek['tabla'], $tabla_id, $uj_lista, 440600, true);
		// KÉPEK VÉGE

		$url = '';
		if(isset($_GET['id'])){
			$url      = '&id='.$_GET['id'];
			$tabla_id = $_GET['id'];
		}

		// Ha hibamentes volt a feltöltés (adatbázis beszúráskor az adatok illetve képhiba is rendben van)
		if( $errnum == '' && $kephiba['1'] <= 0){

			$adatbazis_kapcsolat -> bezar();
			if(isset($_POST['ugras_kepekhez'])){
                header('location:'.self().str_replace('&amp;','&',get_get(array('id', 'hiba', 'errnum', 'kephiba', 'oldal', 'epo', 'kat', 'order'))).'&id='.$tabla_id.'#kepek');
            }else{
                // Csak akkor van továbbléptetés, ha nem ajaxosan hívtuk meg
                if(!isset($_POST['ajax_form_modosit'])){
                    // header('location:'.self().'?uj')
                    // Mégis a létrehozott adatlapra érkezzünk:
//exit;
                    // Anyagigénylés feltöltésekor a táblázatra érkezzünk
                    if($ertek['tabla']=='anyagigenyles'){
                        
                        // A táblázat igénylés dátum szerinti csökkenő sorrendben jelenjenek meg
                        $_SESSION['anyagigenyles_tablazat_rendez'] = 'anyag_tabla_ig_datum';
                        $_SESSION['anyagigenyles_tablazat_irany']  = 'desc';
   
                        header('location:'.self().'?tablazat');
                    }else{
                        header('location:'.self().str_replace('&amp;','&',get_get(array('id', 'hiba', 'errnum', 'kephiba', 'oldal', 'epo', 'kat', 'rend', 'irany', 'order'))).'&id='.$tabla_id.'&siker');
                    }
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

	// Törlés, töröl
	if(isset($_GET[$ertek['torid']])){
        
        // Archiv táblából biztosan nem törlünk
        if( substr($ertek['tabla'], strlen($ertek['tabla'])-5) == '_arch' ){
            $weblap -> leallit(); exit;
        }

        // Jogosultságkezelés
        if($ertek['tabla']=='anyagigenyles' && !_jog(JOG_ANYAGIGENYLES_KEZEL)){
            $weblap -> leallit(); exit;
        }
        if($ertek['tabla']=='bejelentes' && !_jog(JOG_BEJELENTES_TORLESE)){
            $weblap -> leallit(); exit;
        }
        if( $ertek['tabla']=='beosztas' && !_jog(JOG_BEOSZTASOK_KEZEL)){
            $weblap -> leallit(); exit;
        }
        if( $ertek['tabla']=='cikktorzs' && !_jog(JOG_CIKKTORZS_KEZEL)){
            $weblap -> leallit(); exit;
        }
        if($ertek['tabla']=='eloterj_bod' && !_jog(JOG_ELOTERJ_KEZEL)){
            $weblap -> leallit(); exit;
        }
        if($ertek['tabla']=='eloterj_fb' && !_jog(JOG_ELOTERJ_KEZEL)){
            $weblap -> leallit(); exit;
        }
        if($ertek['tabla']=='erteklista' && !_jog(JOG_ERTEKLISTAK_KEZEL)){
            $weblap -> leallit(); exit;
        }
        if($ertek['tabla']=='erteklista' && !_jog(JOG_SZEREPKOR_KEZEL)){
            $weblap -> leallit(); exit;
        }
        if($ertek['tabla']=='eszkozkezeles' && !_jog(JOG_ESZKOZ_KEZEL)){
            $weblap -> leallit(); exit;
        }
        if($ertek['tabla']=='felmeresi_terv' && !_jog(JOG_FT_KEZEL)){
            $weblap -> leallit(); exit;
        }
        // Login egyáltalán nem törölhető
        if($ertek['tabla']=='login'){
            $weblap -> leallit(); exit;
        }
        if( $ertek['tabla']=='projektigenyles' && (!_jog(JOG_PRIGENY_S_KEZEL) && !_jog(JOG_PRIGENY_T_KEZEL)) ){
            $weblap -> leallit(); exit;
        }
        if( $ertek['tabla']=='projektkezeles' && !_jog(JOG_PRKEZEL_KEZEL) ){
            $weblap -> leallit(); exit;
        }
        if( $ertek['tabla']=='raktar' && !_jog(JOG_RAKTAR_KEZEL)){
            $weblap -> leallit(); exit;
        }
        if( $ertek['tabla']=='szabadsagigeny' && !_jog(JOG_SZABADSAGIG_KEZEL)){
            $weblap -> leallit(); exit;
        }
        if( $ertek['tabla']=='torzsadat' && !_jog(JOG_TORZSADAT_KEZEL)){
            $weblap -> leallit(); exit;
        }

        // Törléssel kapcsolatos műveletek egy tranzakció
        $db_error = false;
        mysqli_autocommit($db_kapcs, false);

        // Ütmezett feladatoknál és társainál vagy törlés jog kell hozzá, vagy ha az nincs, akkor az kell, hogy magam hoztam létre
        if(
            ( $ertek['tabla']=='utemezett_feladatok'     && !_jog( JOG_UTFEL_TOROL       )  ) ||
            ( $ertek['tabla']=='rendszeres_karbantartas' && !_jog( JOG_RENDSZ_KARB_TOROL )  ) ||
            ( $ertek['tabla']=='eseti_fenntartas'        && !_jog( JOG_ESETI_FENN_TOROL  )  )
        ){
            
            $loginnev = $adatbazis_kapcsolat->s2('loginnev', $ertek['tabla'], "`recid`='{$_GET[$ertek['torid']]}'");
            if( $loginnev === false ) { $db_error = true; }

            if($loginnev['loginnev']!=$_SESSION[get_db_tabla().'user']){
                $weblap -> leallit(); exit;
            }
        }
        
        // Csak az törölheti, aki létrehozta (itt nem vizsgálunk külön törlés jogot
        $csak_aki_letrehozta_torolheti = array('projektigenyles');
        if( in_array($ertek['tabla'], $csak_aki_letrehozta_torolheti) ){
            $loginnev = $adatbazis_kapcsolat->s2('loginnev', $ertek['tabla'], "`recid`='{$_GET[$ertek['torid']]}'");
            if( $loginnev === false ) { $db_error = true; }

            if($loginnev['loginnev']!=$_SESSION[get_db_tabla().'user']){
                $weblap -> leallit(); exit;
            }
        }

        // Kapcsolódó táblák törlése
        // Megjegyzéseket általánosságban
        $sql = $adatbazis_kapcsolat->d('megjegyzes', "`tabla`='{$ertek['tabla']}' AND `id`='{$_GET[$ertek['torid']]}'");
        if( $sql === false ) { $db_error = true; }
        // Státusz tábla törlése, hacsak nem kivételes
        $kivetelek = array('cikktorzs', 'eseti_fenntartas', 'eszkozkezeles', 'felmeresi_terv', 'utemezett_feladatok', 'rendszeres_karbantartas', 'szerepkor');
        if( !in_array($ertek['tabla'], $kivetelek) ){
            $sql = $adatbazis_kapcsolat->d($ertek['tabla'].'_statusz', "`{$ertek['tabla']}_recid`='{$_GET[$ertek['torid']]}'");
            if( $sql === false ) { $db_error = true; }
        }

        // Dokumentumok törlése
        $torolt_fajlok = '';
        $dokos_oldalak = array('anyagigenyles', 'bejelentes', 'eseti_fenntartas', 'eszkozkezeles', 'eszkozkezeles_eng', 'felmeresi_terv', 'munkalap', 'utemezett_feladatok', 'projektigenyles', 'rendszeres_karbantartas');
        if( in_array($ertek['tabla'], $dokos_oldalak) ){
            $fajlok = $adatbazis_kapcsolat->s('*', $ertek['tabla'].'_dok', "`{$ertek['tabla']}_id`='{$_GET[$ertek['torid']]}'");

            foreach($fajlok as $fajl){
                $torolt_fajlok .= '../'.get_utvonal().$ertek['tabla'].'/'.$fajl['utvonal'].'<br>';
                kep_torol('../'.get_utvonal().$ertek['tabla'].'/'.$fajl['utvonal']);
            }

            $sql = $adatbazis_kapcsolat->d($ertek['tabla'].'_dok', "`{$ertek['tabla']}_id`='{$_GET[$ertek['torid']]}'");
            if( $sql === false ) { $db_error = true; }
        }

        // Felmérési tervnél a Tervlap is tartalmaz egy dokumentumot
        $fajl = $adatbazis_kapcsolat->s2('felm_terv', $ertek['tabla'], "`recid`='{$_GET[$ertek['torid']]}'");
        kep_torol('../'.get_utvonal().$fajl['felm_terv']);

        // Előterjesztéskor a kapcsolódó táblák és projektigénylés előterjesztés tábla törlése
        if($ertek['tabla']=='eloterj_fb'){
            $elot = $adatbazis_kapcsolat->s2('stat_id', 'eloterj_fb', "`recid`='{$_GET[$ertek['torid']]}'");
            if($elot['stat_id']!=ELOT_FB_STAT_NYITOTT){
                exit;//Megszakítjuk, ha nincs is nyitott státuszban
            }
            $prigenyek = $adatbazis_kapcsolat->s('projektigenyles_recid', 'eloterj_fb_kapcs', "`eloterj_recid`='{$_GET[$ertek['torid']]}'");
            foreach($prigenyek as $pri){
                $sql = $adatbazis_kapcsolat->d('projektigenyles_eloterj', "`recid`='{$pri['projektigenyles_recid']}'");
                if( $sql === false ) { $db_error = true; }
            }
            $sql = $adatbazis_kapcsolat->d('eloterj_fb_kapcs', "`eloterj_recid`='{$_GET[$ertek['torid']]}'");
            if( $sql === false ) { $db_error = true; }
        }
        if($ertek['tabla']=='eloterj_bod'){
            $elot = $adatbazis_kapcsolat->s2('stat_id', 'eloterj_bod', "`recid`='{$_GET[$ertek['torid']]}'");
            if($elot['stat_id']!=ELOT_BOD_STAT_NYITOTT){
                exit;// Megszakítjuk, ha nincs is nyitott státuszban
            }
            $prigenyek = $adatbazis_kapcsolat->s('projektigenyles_recid', 'eloterj_bod_kapcs', "`eloterj_recid`='{$_GET[$ertek['torid']]}'");
            foreach($prigenyek as $pri){
                $sql = $adatbazis_kapcsolat->d('projektigenyles_eloterj', "`recid`='{$pri['projektigenyles_recid']}'");
                if( $sql === false ) { $db_error = true; }
            }
            $sql = $adatbazis_kapcsolat->d('eloterj_fb_kapcs', "`eloterj_recid`='{$_GET[$ertek['torid']]}'");
            if( $sql === false ) { $db_error = true; }
        }

        // Projektigénylésnél a véleményezők és azok dokumentumai
        if($ertek['tabla']=='projektigenyles'){
            $sql = $adatbazis_kapcsolat->d('projektigenyles_kapcs', "`projektigenyles_recid`='{$_GET[$ertek['torid']]}'");
            if( $sql === false ) { $db_error = true; }
            $velemenyek = $adatbazis_kapcsolat->s('utvonal', 'projektigenyles_velemeny', "`projektigenyles_recid`='{$_GET[$ertek['torid']]}'");
            foreach($velemenyek as $velemeny){
                kep_torol('../'.get_utvonal().'projektigenyles_velemeny/'.$velemeny['utvonal']);
            }
            $sql = $adatbazis_kapcsolat->d('projektigenyles_velemeny', "`projektigenyles_recid`='{$_GET[$ertek['torid']]}'");
            if( $sql === false ) { $db_error = true; }
        }

        // Login táblából csak logikailag törlünk. Többi helyről fizikailag.
        if($ertek['tabla']=='login'){
            $sikeres_torles = $adatbazis_kapcsolat -> u($ertek['tabla'], array('torolve'), array(1), "`recid`='{$_GET[$ertek['torid']]}'");
        }else{
            // Kezdeményezett művelet
            $muvelet = 'A(z) '.$ertek['tabla'].'-ból recordot törölt a következő azonosítóval: '.$_GET[$ertek['torid']].'.';

            // Végrehajtott változtatás
            $valtozas = '';

            $sor = $adatbazis_kapcsolat->s2('*', $ertek['tabla'], "`recid`='{$_GET[$ertek['torid']]}'", '', '');
            if( $sor === false ) { $db_error = true; }

            $i = 1;
            foreach($sor as $key => $value) {
                $valtozas .= $i.'.: A(z) <strong>'.$key.'</strong> mező a következő értéket kapta: <strong>'.$value.'</strong><br>';

                $i++;
            }

            if( $torolt_fajlok ) {
                $valtozas .= '<br>Törölt fájlok:<br>'.$torolt_fajlok;
            }

            $sql = $adatbazis_kapcsolat -> i(
                'log',
                array( 'felh_id',                               'nev',       'valtozas', 'ip'                   ),
                array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,    $valtozas,  $_SERVER['REMOTE_ADDR'])
            );
            if( $sql === false ) { $db_error = true; }

            $sikeres_torles = $adatbazis_kapcsolat -> d($ertek['tabla'], "`recid`='{$_GET[$ertek['torid']]}'");
        }
        if( $sikeres_torles === false ) { $db_error = true; }
        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            if(isset($_GET['noload'])){

                // Visszaadjuk az elemek számát
                echo number_format( count($adatbazis_kapcsolat->s('recid', $ertek['tabla'])), 0, '', '.' );

				$weblap -> leallit();
				exit();
			}else{
				$weblap -> leallit();
				
                // Anyagigénylés törlés után a táblzathoz jutunk
                $kieg = '';
                if( $ertek['tabla']=='anyagigenyles' ){
                    $kieg = '?tablazat';
                }
				header('location:'.self().$kieg);
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

// JS FÁJLFELTÖLTÉSHEZ Új bejegyzés feltöltése után a csatolt fájl eltárolása adatbázisba és megfelelő mappába másolás
if(isset($_GET['modosit'])){

    $tabla = str_replace('.php', '', basename(self()));

    // Nézzük meg, hogy van-e SESSION id kezdetű fájl a könyvtárban
    $utvonal = '../'.get_utvonal().'cache/jsfajlok/'.session_id().'*';
    $session_kezdetu_fajlok = glob($utvonal); // Ha több ilyen is van az azért lehet, mert több fájlfeltöltés input is volt a tömbben
    foreach($session_kezdetu_fajlok as $akt_fajl){
        list($eleje, $oszlop, $fajlnev, $fajlnev2, $vege) = explode('|||', $akt_fajl);

        // Ha feltöltéskor a 'get_id'-t adtunk meg fájlnévnek, akkor az algoritmus ID, azaz a GET id szerint mentjük el
        if($fajlnev=='get_id'){
            $fajlnev = $_GET['id'];

        // Ha nincs információnk a fájl nevéről, akkor az algoritmus az NEV, azaz a bejegyzés nevéből származtatjuk
        }elseif(!$fajlnev){
            $fajlnev = $adatbazis_kapcsolat->s('nev', $tabla, 'id='.$_GET['id']);
            $fajlnev = fajlnev_keszit($fajlnev[0]['nev']);
        }

        $alkonyvtar  = '';
        $akt_utvonal = '../'.get_utvonal().$tabla.'/';
        if(!is_dir($akt_utvonal)){
            mkdir($akt_utvonal, 0777);
        }
        if($tabla=='felmeresi_terv'){
            $telephely = $adatbazis_kapcsolat->s2('telephely_id', $tabla, "`recid`='{$_GET['id']}'");
            $alkonyvtar = 'telep_'.$telephely['telephely_id'].'/';
            $akt_utvonal .= $alkonyvtar;
            if(!is_dir($akt_utvonal)){
                mkdir($akt_utvonal, 0777);
            }
        }

        // Ha az oszlop a bejelentesUjFeltoltes, akkor bejelentések új feltötlésekor hozzáadott dokumentumról van szó
        // Ezt másoljuk be a dokumentumok füle alá
        if($oszlop=='bejelentesUjFeltoltes'){
            $bejelentes = $adatbazis_kapcsolat->s2('ikt_szam', $tabla, "`recid`='{$_GET['id']}'");
            $alkonyvtar = str_replace('/', '_', $bejelentes['ikt_szam']).'/';
            $akt_utvonal .= $alkonyvtar;
            if(!is_dir($akt_utvonal)){
                mkdir($akt_utvonal, 0777);
            }

            copy  ($akt_fajl, $akt_utvonal.$fajlnev2);
            unlink($akt_fajl);
            
            $fajlnev2 = addslashes($fajlnev2);
            $adatbazis_kapcsolat->i('bejelentes_dok',   array('bejelentes_id', 'utvonal',             'fajlnev',  'loginnev',                       'update_dt', 'update_usr',                            'insert_dt', 'insert_usr'                            ),
                                                        array($_GET['id'],     $alkonyvtar.$fajlnev2, $fajlnev2,  $_SESSION[get_db_tabla().'user'], 'NOW()',      $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',      $_SESSION[get_db_tabla().'teljes_nev']  )
                                    );
            
        // Általános eset
        }else{
            
            $kepnev      = $fajlnev;
            $ujnev       = $kepnev.'_1';

            $i = 1;
            // DE! megnézzük, hogy ez a '_1' nem foglalt-e. Ha az, keresünk szabad számot
            while( glob($akt_utvonal.$ujnev.'*') ){
                $i++;
                $ujnev = $kepnev.'_'.$i;
            }

            $kepnev = fajlnev_keszit($ujnev).'.'.kit($akt_fajl);

            // Fájlok mozgatása
            copy($akt_fajl, $akt_utvonal.$kepnev);
            unlink($akt_fajl);

            // Végül letároljuk az adatbázisba is
            $adatbazis_kapcsolat->u($tabla, array($oszlop), array($alkonyvtar.$kepnev), 'recid='.$_GET['id']);
        }
    }
}

// Kijelölt termékek törlése
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

// Kép törlése
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

//Fájl törlése. Ajaxal hívódik meg. általában nem kép, hanem csv, pdf .. fájlok kezelésére
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