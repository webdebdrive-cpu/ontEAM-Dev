<?php
// Beállítások
$sha1           = 1;
$jogolsultsagos = 0;

require_once('../weblap.php');

// Elfelejtett jelszós linkkel érkeztek ide
if( isset($_GET['a']) ) {
    $login = $adatbazis_kapcsolat -> s2('recid', 'login', "`azon_kod`='{$_GET['a']}' AND `azon_dt`>'".date('Y-m-d H:i:s', time()-LOGIN_AZON_ERV)."'");
    unset($_SESSION[get_db_tabla().'user']);
    unset($_SESSION[get_db_tabla().'teljes_nev']);
    unset($_SESSION[get_db_tabla().'login_recid']);
    unset($_SESSION[get_db_tabla().'rights']);
    unset($_SESSION[get_db_tabla().'ertesites']);
    unset($_SESSION[get_db_tabla().'szemelyzet_id']);
    $_SESSION['nincs_jelsz_felhid'] = $login['recid'];
    $_SESSION['nincs_jelsz']        = 1;
}

// Nem láthat bárki bármilyen tartalmat, viszont az admin bármit
if( $_SESSION[get_db_tabla().'user'] && $_SESSION[get_db_tabla().'user'] != 'admin' ) {

    $user_joga = $adatbazis_kapcsolat -> s2('jog', 'login', "`nev`='{$_SESSION[get_db_tabla().'user']}'");

    $jogok = $adatbazis_kapcsolat -> s2('oldalak', 'jog', "`id`='{$user_joga['jog']}'");

    // Nem tudjuk, hogy most  \n\r -lesz e, ezért csak \n-re vágok és trimmelek
    $jogok['oldalak'] = nl2br($jogok['oldalak']);
    $jogosultsagok = explode('<br />', $jogok['oldalak']);
    $i = 0;
    foreach ( $jogosultsagok as $x ) {
        $jogosultsagok[$i] = trim($jogosultsagok[$i]);
        $i++;
    }

    if( $jogolsultsagos && !in_array(basename(self()), $jogosultsagok) ) {
        $adatbazis_kapcsolat -> bezar();
        header('Location:index.php?nincsjog');
        exit();
    }

}

// Jelszó csere
if( isset($_POST['jelszo_csere']) ) {

    // Jelszó nem üres-e
    if( !empty($_POST['jlsz']) && !empty($_POST['jlsz2']) ) {

        // Elég hosszúak-e
        $hossz = mb_strlen($_POST['jlsz'], 'UTF-8');
        if( $hossz >= 8 && $hossz <= 20 ) {

            // Tartalmaz-e betűt és számot
            if( preg_match('/[A-Za-z].*[0-9]|[0-9].*[A-Za-z]/', $_POST['jlsz']) ) {

                // Azonosak-e
                if( $_POST['jlsz'] == $_POST['jlsz2'] ) {

                    $felhasznalo = $adatbazis_kapcsolat->s2('recid nev teljes_nev jelszo helyesseg flags flags_ert jog van_jelszo szemelyzet_id', 'login', "`recid`='{$_SESSION['nincs_jelsz_felhid']}'");
                    if( $felhasznalo ) {

                        // Helyesség és jelszó
                        $helyesseg  = sha1($felhasznalo['nev'].$_POST['jlsz']);
                        $jlsz       = sha1($_POST['jlsz']);

                        $adatbazis_kapcsolat -> u('login',  array('jelszo', 'helyesseg',    'van_jelszo'),
                                                            array($jlsz,    $helyesseg,     1           ),
                                                    "`recid` = '".$felhasznalo['recid']."'");
                        // Belépéskor új session azonosító (session fixation ellen)
                        session_regenerate_id(true);
                        $_SESSION[get_db_tabla().'user']            = $felhasznalo['nev'];
                        $_SESSION[get_db_tabla().'teljes_nev']      = $felhasznalo['teljes_nev'];
                        $_SESSION[get_db_tabla().'login_recid']     = $felhasznalo['recid'];
                        $_SESSION[get_db_tabla().'rights']          = str_split($felhasznalo['flags']);
                        $_SESSION[get_db_tabla().'ertesites']       = str_split($felhasznalo['flags_ert']);
                        $_SESSION[get_db_tabla().'szemelyzet_id']   = $felhasznalo['szemelyzet_id'];

                        $muvelet = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].')  jelszót választott';
                        $adatbazis_kapcsolat -> i('log',
                                                    array( 'felh_id',                               'nev',      'ip'                    ),
                                                    array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,   $_SERVER['REMOTE_ADDR'] )
                                                );

                        // Nincs jelszó változók törlése
                        $_SESSION['nincs_jelsz_felhid'] = 0;
                        $_SESSION['nincs_jelsz']        = 0;

                        if( !$_SESSION['kert_url'] ) {
                            $_SESSION['kert_url'] = get_http().get_domain().'/'.get_admin();
                        }

                        // Vizsgáljuk, hogy csak bejelentés joga van-e. Ha igen, akkor bejelentések menüpontra visszük egyből
                        $i = 0;
                        $bejelento = 1;
                        foreach ( $_SESSION[get_db_tabla() . 'rights'] as $flag ) {
                            if( $flag && $i != JOG_BEJELENTES_SAJAT_RESZL && $i != JOG_BEJELENTES_KEZEL && $i != JOG_BEJELENTES_MEGTEKINT ) {
                                $bejelento = 0;
                                break;
                            }
                            $i++;
                        }

                        if( $bejelento ) {
                            $_SESSION['bejelento_menu_rejt'] = 1;
                            $_SESSION['kert_url'] = 'https://'.$_SERVER['HTTP_HOST'].'/admin/bejelentes.php';
                            if($_SERVER['HTTP_HOST'] == 'demo.webdeb.hu'){
                                $_SESSION['kert_url'] = get_kerturl_elotag().$_SESSION['kert_url'];
                            }
                        }
                        
                        // A megfelelő menüpont betöltése
                        if( substr($_SESSION['kert_url'], 0, 4) != 'http' ) {
                            $_SESSION['kert_url'] = 'https://'.$_SESSION['kert_url'];
                        }
                        // Ajax hívást azt vegyük ki belőle
                        $_SESSION['kert_url'] = str_replace('ajax=1', '', $_SESSION['kert_url']);
                        if( $_SESSION['kert_url'] == 'esemenyek.php' ) {
                            $_SESSION['kert_url'] = 'index.php';
                        }
                        header('Location: '.$_SESSION['kert_url']);
                        
                        $adatbazis_kapcsolat -> bezar();
                        exit();
                    }

                } else {

                    $muvelet = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].')  eltérő jelszavakat adott meg. Elutasítva.';
                    $adatbazis_kapcsolat -> i('log',
                                                array( 'felh_id',                               'nev',      'ip'                    ),
                                                array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,   $_SERVER['REMOTE_ADDR'] )
                                            );

                    header('Location: index.php?jelszonemazonos');
                    exit();
                }

            } else {

                $muvelet = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].')  gyenge jelszót választott. Elutasítva.';
                $adatbazis_kapcsolat -> i('log',
                                            array( 'felh_id',                               'nev',      'ip'                    ),
                                            array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,   $_SERVER['REMOTE_ADDR'] )
                                        );

                header('Location: index.php?betuszam');
                exit();
            }

        } else {

            $muvelet = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].')  túl rövid/hosszú jelszót választott. Elutasítva.';
            $adatbazis_kapcsolat -> i('log',
                                        array( 'felh_id',                               'nev',      'ip'                    ),
                                        array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,   $_SERVER['REMOTE_ADDR'] )
                                    );

            header('Location: index.php?jelszohossz');
            exit();
        }

    } else {

        $muvelet = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].')  üres jelszót választott. Elutasítva.';
        $adatbazis_kapcsolat -> i('log',
                                    array( 'felh_id',                               'nev',      'ip'                    ),
                                    array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,   $_SERVER['REMOTE_ADDR'] )
                                );

        header('Location: index.php?jelszoures');
        exit();
    }
}

// Jelszó csere mégsem
if( isset($_POST['megsem']) ) {

    $muvelet = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].') mégsem választott jelszót';
    $adatbazis_kapcsolat -> i('log',
                                array( 'felh_id',                               'nev',      'ip'                    ),
                                array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,   $_SERVER['REMOTE_ADDR'] )
                            );

    $_SESSION['nincs_jelsz_felhid'] = 0;
    $_SESSION['nincs_jelsz']        = 0;

    header('Location: index.php');
    exit();
}

// Manuális beléptetés. A nev oszlopot, tehát a loginnevet kell átadni ilyenkor GET-ben
// Pl: https://www.onteam.hu/admin/beleptetes.php?manualis_belepes=XY
// FIGYELEM! Jelszó nélkül bárki beléphetne vele bármelyik felhasználóként, ezért csak ideiglenesen, a használat idejére kapcsold vissza!
// if (isset($_GET['manualis_belepes'])) {
//
//     $felhasznalo = $adatbazis_kapcsolat->s2('recid nev szallito_id teljes_nev jelszo helyesseg flags flags_ert jog van_jelszo szemelyzet_id tabla_rendezes', 'login', "`nev`='". $_GET['manualis_belepes']."'");
//     // echo $adatbazis_kapcsolat->gp() . '<br>';
//     $_SESSION[get_db_tabla().'user']            = $felhasznalo['nev'];
//     $_SESSION[get_db_tabla().'teljes_nev']      = $felhasznalo['teljes_nev'];
//     $_SESSION[get_db_tabla().'login_recid']     = $felhasznalo['recid'];
//     $_SESSION[get_db_tabla().'szallito_id']     = $felhasznalo['szallito_id'];
//     $_SESSION[get_db_tabla().'rights']          = str_split($felhasznalo['flags']);
//     $_SESSION[get_db_tabla().'ertesites']       = str_split($felhasznalo['flags_ert']);
//     $_SESSION[get_db_tabla().'szemelyzet_id']   = $felhasznalo['szemelyzet_id'];
// }

// Beléptetés
if( isset($_POST['login']) ) {

    unset($_SESSION['bejelento_menu_rejt']);
    if( $_POST['nev'] ) {
        $helyesseg = sha1($_POST['nev'].$_POST['jelszo']);

        if( $sha1 ) {
            $_POST['jelszo'] = sha1($_POST['jelszo']);
        }

        $felhasznalo = $adatbazis_kapcsolat->s2('recid nev szallito_id teljes_nev jelszo helyesseg flags flags_ert jog van_jelszo szemelyzet_id tabla_rendezes', 'login', "`nev`='".sql_ertek($_POST['nev'])."' AND `aktiv`='1'");
        if( $felhasznalo ) {

            // Ha van jelszó, vagy ha nem stimmel a kód vagy már lejárt az ideje, akkor is erre az árgra jussunk (sikertelen belépés lesz belőle).
            if( $felhasznalo['van_jelszo'] ) {
                if( $felhasznalo['jelszo'] == $_POST['jelszo'] && $felhasznalo['helyesseg'] == $helyesseg ) {

                    // Belépéskor új session azonosító (session fixation ellen)
                    session_regenerate_id(true);
                    $_SESSION[get_db_tabla().'user']            = $felhasznalo['nev'];
                    $_SESSION[get_db_tabla().'teljes_nev']      = $felhasznalo['teljes_nev'];
                    $_SESSION[get_db_tabla().'login_recid']     = $felhasznalo['recid'];
                    $_SESSION[get_db_tabla().'szallito_id']     = $felhasznalo['szallito_id'];
                    $_SESSION[get_db_tabla().'rights']          = str_split($felhasznalo['flags']);
                    $_SESSION[get_db_tabla().'ertesites']       = str_split($felhasznalo['flags_ert']);
                    $_SESSION[get_db_tabla().'szemelyzet_id']   = $felhasznalo['szemelyzet_id'];
                    
                    // Szállodanevek és értéklista Session-be
                    $_SESSION['telephelyek'] = $adatbazis_kapcsolat->s('id nev',                'telephely');
                    $_SESSION['erteklista']  = $adatbazis_kapcsolat->s('recid csoport id info', 'erteklista');

                    // A felhasználó egyszállodás-e vagy sem (ha igen, a navpanelben nem írjuk ki külön a szálloda nevét)
                    $szallodak = $adatbazis_kapcsolat->s('telephely_id', 'telephely_kapcs', "`nev`='{$felhasznalo['nev']}'");
                    if( count($szallodak) == 1 ) {
                        $_SESSION['egyszallodas_user'] = 1;
                    } else {
                        unset($_SESSION['egyszallodas_user']);
                    }
                    
                    if( !$_SESSION['kert_url'] ) {
                        $_SESSION['kert_url'] = get_http().get_domain().'/'.get_admin();
                    }

                    // Logolások
                    $muvelet = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].') bejelentkezett';
                    $adatbazis_kapcsolat -> u('login', array('login_dt'), array(date('Y-m-d H:i:s')), '`recid` = \''.$felhasznalo['recid'].'\'');
                    $adatbazis_kapcsolat -> i('log',
                                                array( 'felh_id',                               'nev',      'ip'                    ),
                                                array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,   $_SERVER['REMOTE_ADDR'] )
                                            );

                    // Vizsgáljuk, hogy csak bejelentés joga van-e. Ha igen, akkor bejelentések menüopntra visszük egyből
                    $i = 0;
                    $bejelento = true;
                    foreach ( $_SESSION[get_db_tabla().'rights'] as $flag ) {
                        if( $flag && $i != JOG_BEJELENTES_SAJAT_RESZL && $i != JOG_BEJELENTES_KEZEL && $i != JOG_BEJELENTES_MEGTEKINT ) {
                            $bejelento = false;
                        }
                        $i++;
                    }
                    if( $bejelento ) {
                        $_SESSION['bejelento_menu_rejt'] = 1;
                        $_SESSION['kert_url'] = 'https://'.$_SERVER['HTTP_HOST'].'/admin/bejelentes.php';
                        if($_SERVER['HTTP_HOST'] == 'demo.webdeb.hu'){
                            $_SESSION['kert_url'] = get_kerturl_elotag().$_SESSION['kert_url'];
                        }
                    }

                    if( $_POST['ajax'] ) {
                        if( $bejelento ) {
                            echo json_encode(array(
                                'statusz' => 'bejelento'
                            ));
                        } else {
                            echo json_encode(array(
                                'statusz' => 'siker'
                            ));
                        }
                        
                    } else {

                        if( substr($_SESSION['kert_url'], 0, 4) != 'http' ) {
                            $_SESSION['kert_url'] = 'https://'.$_SESSION['kert_url'];
                        }
                        // Ajax hívást azt vegyük ki belőle
                        $_SESSION['kert_url'] = str_replace('ajax=1', '', $_SESSION['kert_url']);
                        header('Location: '.$_SESSION['kert_url']);
                        
                        $adatbazis_kapcsolat -> bezar();
                        exit();
                    }
                    
                } else {
                    
                    if( $_POST['ajax'] ) {
                        echo json_encode(array(
                            'statusz' => 'hiba'
                        ));
                    } else {
                        $adatbazis_kapcsolat -> bezar();
                        header('Location: index.php?hiba');
                        exit();
                    }
                }

            // Ide csak akkor lehessen eljutni, ha nincs jelszava a felhasználónak, ekkor tud magának újat választani
            } else {

                $_SESSION['nincs_jelsz_felhid'] = $felhasznalo['recid'];
                $_SESSION['nincs_jelsz']        = 1;

                if( $_POST['ajax'] ) {
                    echo json_encode(array(
                        'statusz' => 'hiba',
                        'link'    => 'beleptetes.php?kilep'
                    ));
                } else {
                    header('Location: index.php?hiba');
                }
            }

        } else {

            if( $_POST['ajax'] ) {
                echo json_encode(array(
                    'statusz' => 'hiba',
                    'link'    => 'beleptetes.php?kilep'
                ));
            } else {
                header('Location: index.php?hiba');
            }
            exit();

        }

    } else {

        if( $_POST['ajax'] ) {
            echo json_encode(array(
                'statusz' => 'hiba',
                'link'    => 'beleptetes.php?kilep'
            ));
        } else {
            header('Location: index.php?hiba');
        }
        exit();
    }
}

// Kiléptetés
if( isset($_GET['kilep']) ) {

    $adatbazis_kapcsolat -> u('login', array('logout_dt'), array(date('Y-m-d H:i:s')), '`recid` = \''.$_SESSION[get_db_tabla().'login_recid'].'\'');
    $muvelet = $_SESSION[get_db_tabla().'teljes_nev'].' ('.$_SESSION[get_db_tabla().'user'].') kilépett.';
    $adatbazis_kapcsolat -> i('log',
                                array( 'felh_id',                               'nev',      'ip'                    ),
                                array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,   $_SERVER['REMOTE_ADDR'] )
                            );

    session_unset();

    $adatbazis_kapcsolat -> bezar();
    header('Location:index.php');
    exit();
}

