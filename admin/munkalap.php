<?php
require_once('../weblap.php');
//Config

if( !_jog(JOG_MUNKALAP_MEGTEKINT) ) {
    if(!$_SESSION[get_db_tabla().'user']){
        $weblap -> leallit();
        header('Location: index.php');
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');
    $weblap -> leallit();
    exit('Nincs megfelelő jogosultság az oldal eléréséhez!');
}

$megnevezes             = pathinfo(self());
$megnevezes             = $megnevezes['filename'];
$akt_tomb               = $munkalap_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'munkalapot';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

$gyorskereso_text       = '';       //Gyorskereső felirat. Ha üres akkor nincs gyorskeresés
$oszlop_keres           = 'nev';                //Oszlopnevek, melyben keresünk (több oszlop is felsorolható vesszőkkel)
$oszlop_listaz          = 'nev';                //Oszlopnevek, melyeket kilizátunk (több oszlop is felsorolható vesszőkkel)
$gyorskereso_id         = 'ksz';                //Gyorskereső input neve és ID-ja

//Változók beállítása
$csak_szerkesztheto     = false;                //Ha tiltani szeretnéd, hogy újat tudjon feltölteni, vagy törölni adott bejegyzést
$elonezet               = '';                   //Ha üres nincs előnézet. Egyébként meg az előnézet URL-jét add meg ide, pl.: '../index.php?id='.$_GET['id']
$fajloszlop             = 'kep';                //Kép (vagy kivételes esetekben más fájl) oszlopneve.
$id_latszik             = false;                //A termék ID-ja látsszon-e a felsorolásnál, kibontásnál (van, hogy ügyfél is használja)
$kep_thumb_listaba      = false;                //Lista nézetben legyen-e a képoszlop alapján képthumbnail
$magas                  = get_indexkep_m();     //Kiskép magassága
$sorrendezes            = false;                //Ha sorrendet is be szeretnénk állítani. Adatbázisban sor oszlopot létre kell hozni, jquery-ui.js-t és admin_sorrendez.js-t aktiválni az adminhoz
$szel                   = get_indexkep_sz();    //Kiskép szélessége
$ujkent_szerkesztheto   = true;                 //Szerkesztésnél elérhető egy gomb, melyre kattintva a termékadatai átkerülnek az új feltöltésbe

$ckeditor_js            = '../'.get_utvonal().'wysiwyg/ckeditor.js';    //Ckeditor JS útvonala
$formazatlan_szam       = array('nev', 'csz', 'tel', 'fax', 'mobil');          //Azok az oszlopneveket sorold fel, melyek numerikus értéket is tartalmazhatnak, de nem szeretnéd, ha lista nézetben ezres csoportra bontaná

$szamla_html = '';

//Több kép menedzseléséhez
$tobb_kep = true; // Ha true, akkor js/jquery-ui.js, js/admin_sorrendez.js aktivál és minify!, admin/tobb_kep.php aktivál
if($tobb_kep){
    function get_tobbkep_darab()    { return 10;         } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return 'false';    } // Lényeges, hogy egy string legyen
    function get_tobbkep_kepmeret() { return 10;         } // MB-ban
    function get_tobbkep_oszlopnev(){                     // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

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

        if( count($csoportok_unique) > 1 ) {
            echo json_encode(array(
                'hiba' => 'Csoportos és szabad bejelentésekből nem lehet egy munkalapot készíteni. <br>A szabad bejelentéseket előbb adja a csoporthoz!'
            ));

            $weblap->leallit();
            exit();
        }

        $kijelolt_db = array_count_values($csoportok);

        foreach( $csoportok_unique as $csu ) {
            if( $csu > 0 ) {
                $sql = $csoport_db = $adatbazis_kapcsolat->s('csoport_id', 'bejelentes', "`csoport_id` = '{$csu}'", '', '');

                if( $sql === false ) {
                    $db_error = true;
                }

                if( count($csoport_db) != $kijelolt_db[$csu] ) {
                    echo json_encode(array(
                        'hiba' => 'Csoportosított munkalapból nem adható munkalaphoz. Előbb vegye ki a csoportból!'
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
            $statusz = $adatbazis_kapcsolat->s('recid stat_id insert_dt', 'bejelentes_statusz', "`bejelentes_recid`='$id'", 'recid desc', '1');
            if( $statusz === false ) { $db_error = true; }

            if( $statusz ) {
                $statusz = $statusz[0];

                if( $statusz['stat_id'] != BEJELENTES_STAT_NYITOTT && $statusz['stat_id'] != BEJELENTES_STAT_FOLYAMATBAN ) {
                    echo json_encode(array(
                        'hiba' => 'Csak Nyitott vagy Folyamatban státuszú bejelentésből készíthető munkalap!'
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

        /* Ellenőrizzük, hogy van e már belőlük munkalap */
        foreach($idk as $id) {
            $sql = $isMlap = $adatbazis_kapcsolat->s('recid munkalap_id', 'munkalap_kapcs', "`bejelentes_id` = '{$id}'", '', '1');

            if( $sql === false ) {
                $db_error = true;
            }

            if( $isMlap ) {
                $isMlap = $isMlap[0];

                echo json_encode(array(
                    'hiba' => 'Ehhez a bejelentéshez már készült munkalap. <br>Ha mégegyet szeretne készíteni, duplikálja a munkalapot! <br>Duplikálja most a munkalapot?',
                    'gomb' => '<input class="gomb zold" value="Igen" onclick="location.href = \'munkalap.php?uj&ujkent='.$isMlap['munkalap_id'].'\';" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
                ));

                $weblap->leallit();
                exit();
            }
        }

        //Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if(isset($_POST['csak_kerdezunk'])) {

            echo json_encode(array(
                'hiba' => 'Biztos munkalapot készít a kijelölt bejelentés(ek)ből?',
                'gomb' => '<input class="gomb zold" value="Igen" onclick="munkalapKeszit();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        /* Státuszt folyamatban-ra állítjuk */
        foreach($idk as $id) {
            if( addStatusz( 'bejelentes_statusz', $id, BEJELENTES_STAT_FOLYAMATBAN ) === false ) {
                $db_error = true;
            }
        }

        if( !$db_error ) {
            /* Megnézzük, hogy az adott évszámmal létezik e bejegyzés */
            $ikt_seq = $adatbazis_kapcsolat->s('ev sorszam', 'iktszam_seq', "`tabla` = 'munkalap' AND `ev` = '".date('y')."'", '', '');
            if( $ikt_seq === false ) { $db_error = true; }

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
                if( $sql === false ) { $db_error = true; }
            }

            /* Iktatószám formázása */
            $ikt_szam = 'M'.date('y').'/'.sprintf('%07d', $sorszam);

            /* Ellenőrizzük a szállodákat is */
            $telephely_id = $adatbazis_kapcsolat->s('telephely_id', 'bejelentes', "`recid` IN (".implode(',', $idk).")", '', '1');
            if( $telephely_id === false ) { $db_error = true; }

            if( $telephely_id ) {
                $telephely_id = $telephely_id[0]['telephely_id'];
            }

            /* Munkalap beszúrása */
            $recid = $adatbazis_kapcsolat -> i('munkalap',
                                                array('ikt_szam',   'stat_id',              'tipus_id',  'telephely_id', 'loginnev',                         'update_usr',                          'insert_dt',    'insert_usr'                              ),
                                                array($ikt_szam,     MUNKALAP_STAT_NYITOTT, 1,           $telephely_id,  $_SESSION[get_db_tabla().'user'],   $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',       $_SESSION[get_db_tabla().'teljes_nev']   )
                                            );
            if( $recid === false ) { $db_error = true; }

            /* Hozzákapcsoljuk a bejelentéseket */
            foreach($idk as $id) {
                $sql = $adatbazis_kapcsolat -> i('munkalap_kapcs',
                                                    array('munkalap_id', 'bejelentes_id',  'update_usr',                            'insert_dt',    'insert_usr',                           ),
                                                    array($recid,        $id,              $_SESSION[get_db_tabla().'teljes_nev'],  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  )
                                                );
                if( $sql === false ) { $db_error = true; }

            }

            /* Felvesszük az első státuszt */
            $sql = $adatbazis_kapcsolat -> i('munkalap_statusz',
                                                array('munkalap_recid', 'stat_id',              'insert_dt',    'insert_usr',                              'update_usr'                           ),
                                                array($recid,           MUNKALAP_STAT_NYITOTT,  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],    $_SESSION[get_db_tabla().'teljes_nev'] )
                                            );
            if( $sql === false ) { $db_error = true; }
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


/* Munkalap átadása telepített csoportnak */
if( isset($_POST['munkalapTelepitett']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        $akt_statusz = $adatbazis_kapcsolat->s('stat_id', 'munkalap', "`recid` = '{$recid}'", '', '1');
        if( $akt_statusz === false ) { $db_error = true; }

        if( $akt_statusz ) {
            $akt_statusz = $akt_statusz[0];

            if( $akt_statusz['stat_id'] != MUNKALAP_STAT_NYITOTT ) {
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
                'gomb' => '<input class="gomb zold" value="Igen" onclick="munkalapTelepitett();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        $sql = $adatbazis_kapcsolat -> u('munkalap', array('szemelyzet_id', 'visszaadva'), array(1, 0), "`recid` = '{$recid}'");

        if( $sql === false ) {
            $db_error = true;
        }

        if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_FOLYAMATBAN ) ) {
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

/* Munkalap átadása külső csoportnak */
if( isset($_POST['munkalapKulso']) ) {
    $recid = $_POST['recid'];
    $szallito_id = $_POST['szallito_id'];
    $szemelyzet_id = $_POST['szemelyzet_id'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid && ($szallito_id || $szemelyzet_id) ) {
        /* Ellenőrizzük, hogy felvéve státuszban van e */
        $sql = $munkalap = $adatbazis_kapcsolat->s('stat_id', 'munkalap', "`recid` = '{$recid}'", '', '1');

        if( $sql === false ) {
            $db_error = true;
        }

        if( $munkalap ) {
            $munkalap = $munkalap[0];

            if( $munkalap['stat_id'] != MUNKALAP_STAT_NYITOTT && $munkalap['stat_id'] != MUNKALAP_STAT_FOLYAMATBAN ) {
                echo json_encode(array(
                    'hiba' => 'Csak nyitott, vagy folyamatban lévő munkalapokat lehet kiadni külsősnek!'
                ));

                $weblap->leallit();
                exit();
            }
        }

        if( $szallito_id ) {
            $sql = $adatbazis_kapcsolat -> u('munkalap',
                                                array('szemelyzet_id',   'szallito_id',   'update_usr'                           ),
                                                array( KULSO_KIVITELEZO, $szallito_id,    $_SESSION[get_db_tabla().'teljes_nev'] ),
                                                "`recid` = '{$recid}'"
                                            );

            if( $sql === false ) { $db_error = true; }

            if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_FOLYAMATBAN ) ) {
                $db_error = true;
            }
        } else if( $szemelyzet_id ) {
            $sql = $adatbazis_kapcsolat -> u('munkalap',
                                                array('szemelyzet_id',   'update_usr'                           ),
                                                array( $szemelyzet_id,   $_SESSION[get_db_tabla().'teljes_nev'] ),
                                                "`recid` = '{$recid}'"
                                            );
            if( $sql === false ) { $db_error = true; }

            if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_HOZREND_ELOKESZ ) ) {
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
}


/* Munkalap Felvétele */
if( isset($_POST['munkalapFelvesz']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    //Műszaki vezető csak olyan munkalapot vehet, fel, ahol a hozzárendelt személy a műszaki vezető
    if(_jog(JOG_MUNKALAP_MUSZAKI_VEZ)){
        $munkalap = $adatbazis_kapcsolat->s2('szemelyzet_id', 'munkalap', "`recid`='{$recid}'");
        if( $munkalap === false ) { $db_error = true; }
        if($munkalap['szemelyzet_id']!=MUSZAKI_VEZETO){
            echo json_encode(array(
                'hiba' => 'Műszaki vezetőként csak olyan munkalapot vehet fel, amely Műszaki vezetőhöz lett rendelve!'
            ));
            $weblap->leallit();
            exit();
        }
    }


    if( $recid ) {
        if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_FELVEVE ) ) {
            $db_error = true;
        }

        $sql = $adatbazis_kapcsolat -> i('munkalap_tetel',
                                            array('munkalap_id', 'insert_dt', 'insert_usr',                              'update_usr'                           ),
                                            array($recid,         'NOW()',    $_SESSION[get_db_tabla().'teljes_nev'],    $_SESSION[get_db_tabla().'teljes_nev'] )
                                        );
        if( $sql === false ) { $db_error = true; }

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


/* Munkalap készre jelentése */
if( isset($_POST['munkalapKeszre']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        $akt_tetel = $adatbazis_kapcsolat->s('recid', 'munkalap_tetel', "`munkalap_id` = '{$recid}'", 'recid desc', '1');
        if( $akt_tetel === false ) { $db_error = true; }

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

        /* Ellenőrizzük, hogy felvéve státuszban van e */
        $munkalap = $adatbazis_kapcsolat->s('stat_id', 'munkalap', "`recid` = '{$recid}'", '', '1');
        if( $munkalap === false ) { $db_error = true; }

        if( $munkalap ) {
            $munkalap = $munkalap[0];

            if( $munkalap['stat_id'] != MUNKALAP_STAT_FELVEVE ) {
                echo json_encode(array(
                    'hiba' => 'Csak felvéve státuszú bejelentést lehet készre jelenteni!'
                ));

                $weblap->leallit();
                exit();
            }
        }

        //Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if (isset($_POST['csak_kerdezunk'])) {

            echo json_encode(array(
                'hiba' => 'Biztosan készre jelenti a munkát?',
                'gomb' => '<input class="gomb zold" value="Igen" onclick="munkalapKeszre();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        /* Készre állítjuk a munkát */
        if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_KESZ_IG ) ) {
            $db_error = true;
        }

        $akt_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id` = '{$recid}'", 'recid desc', '1');
        if( $akt_tetel === false ) { $db_error = true; }

        if( $akt_tetel ) {

            /* Munkalapon töröljük a személyzetet és a mobil loginnevet */
            $sql = $adatbazis_kapcsolat -> u('munkalap',
                                                array('szemelyzet_id', 'mobil_loginnev', 'szallito_id', 'update_usr'                           ),
                                                array( 0,              '',                0,            $_SESSION[get_db_tabla().'teljes_nev'] ),
                                                "`recid` = '{$recid}'"
                                            );
            if( $sql === false ) { $db_error = true; }

            /* Utolsó tételnél átállítjuk az átadta mező értékét */
            $sql = $adatbazis_kapcsolat -> u('munkalap_tetel',
                                                array('atadta_dt',  'visszaadva', 'update_usr'                            ),
                                                array('NOW()',      1,             $_SESSION[get_db_tabla().'teljes_nev'] ),
                                                "`recid` = '".$akt_tetel['recid']."'"
                                            );
            if( $sql === false ) { $db_error = true; }
        }

        /* Munkalaphoz kapcsolódó bejelentés */
        $bejelentesek = $adatbazis_kapcsolat->s('bejelentes_id', 'munkalap_kapcs', "`munkalap_id` = '{$recid}'");
        if( $bejelentesek === false ) { $db_error = true; }

        if( $bejelentesek ) {

            /* Megnézzük, hogy van-e olyan munkalap, ahol ez a bejelentés szerepel */
            $mashol_bej = $adatbazis_kapcsolat->s('munkalap_id bejelentes_id', 'munkalap_kapcs', "`bejelentes_id` = '".$bejelentesek[0]['bejelentes_id']."' AND `munkalap_id` != '{$recid}'");
            if( $mashol_bej === false ) { $db_error = true; }

            /* Ha szerepel */
            if( $mashol_bej ) {
                $osszes_munkalap = count($mashol_bej);
                $elkeszult_munkalapok = 0;

                /* Megnézzük az összes munkalap státuszát */
                foreach($mashol_bej as $mbej) {

                    $munkalap = $adatbazis_kapcsolat->s2('stat_id', 'munkalap', "`recid` = '".$mbej['munkalap_id']."'");
                    if( $munkalap ) {

                        if( $munkalap['stat_id'] == MUNKALAP_STAT_KESZ_IG ) {
                            $elkeszult_munkalapok++;
                        }
                    }
                }

                /* Ha mind elkészült, tehát ez az utolsó igazolásra váró, akkor a bejelentések státuszát átváltjuk */
                if( $osszes_munkalap == $elkeszult_munkalapok ) {
                    /* Lekérdezzük a bejelentéseit */
                    foreach($bejelentesek as $bejelentes) {
                        /* Átállítjuk a bejelentések státuszát Igazolásra várra */
                        if( !addStatusz( 'bejelentes_statusz', $bejelentes['bejelentes_id'], BEJELENTES_STAT_KESZ_IG ) ) {
                            $db_error = true;
                        }
                    }
                }

            /* Ha nem szerepel akkor */
            } else {
                /* Lekérdezzük a bejelentéseit */
                foreach($bejelentesek as $bejelentes) {
                    /* Átállítjuk a bejelentések státuszát Igazolásra várra */
                    if( !addStatusz( 'bejelentes_statusz', $bejelentes['bejelentes_id'], BEJELENTES_STAT_KESZ_IG ) ) {
                        $db_error = true;
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

/* Munkalap megrendelése */
if( isset($_POST['munkalapMegrendelve']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        /* Ellenőrizzük, hogy hozzárendelés előkészítés alatt van e */
        $sql = $munkalap = $adatbazis_kapcsolat->s('stat_id szallito_id', 'munkalap', "`recid` = '{$recid}'", '', '1');

        if( $sql === false ) {
            $db_error = true;
        }

        if( $munkalap ) {
            $munkalap = $munkalap[0];

            if( $munkalap['stat_id'] != MUNKALAP_STAT_HOZREND_MEGREND_VAR ) {
                echo json_encode(array(
                    'hiba' => 'Csak Megrendelésre vár státuszban lehet a megrendelést véglegesíteni!'
                ));

                $weblap->leallit();
                exit();
            }

            if( !$munkalap['szallito_id'] ) {
                echo json_encode(array(
                    'hiba' => 'Előbb rendeljen hozzá külső kivitelezőt!'
                ));

                $weblap->leallit();
                exit();
            }

            //Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
            if( $_POST['csak_kerdezunk'] ) {
                echo json_encode(array(
                    'hiba' => 'Biztos, hogy megrendelte '.getErtek('nev', 'szallito', 'id', $munkalap['szallito_id']).' partnertől a munka elvégzését?',
                    'gomb' => '<input class="gomb piros" value="Igen" onclick="munkalapMegrendelve( '.$recid.' );" type="button"><input class="gomb zold" value="Nem" onclick="alert2()" type="button">'
                ));

                $weblap->leallit();
                exit();
            }

            /* Átállítjuk a státuszt */
            if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_FOLYAMATBAN ) ) {
                $db_error = true;
            }

            /* Átállítjuk a személyzet típust */
            $sql = $adatbazis_kapcsolat -> u('munkalap', array('szemelyzet_id'), array(KULSO_KIVITELEZO), "`recid` = '{$recid}'");

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
    }

    $weblap->leallit();
    exit();
}

/* Munkalap küldés jóváhagyásra */
if( isset($_POST['kuldesJovahagyasra']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        /* Ellenőrizzük, hogy hozzárendelés előkészítés alatt van e */
        $sql = $munkalap = $adatbazis_kapcsolat->s('stat_id szallito_id', 'munkalap', "`recid` = '{$recid}'", '', '1');

        if( $sql === false ) {
            $db_error = true;
        }

        if( $munkalap ) {
            $munkalap = $munkalap[0];

            if( $munkalap['stat_id'] != MUNKALAP_STAT_HOZREND_ELOKESZ ) {
                echo json_encode(array(
                    'hiba' => 'Csak Hozzárendelés előkészítés alatt státuszban lehet külső kivitelezőt megadni!'
                ));

                $weblap->leallit();
                exit();
            }

            if( !$munkalap['szallito_id'] ) {
                echo json_encode(array(
                    'hiba' => 'Előbb rendeljen hozzá külső kivitelezőt!'
                ));

                $weblap->leallit();
                exit();
            }

            //Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
            if( $_POST['csak_kerdezunk'] ) {
                echo json_encode(array(
                    'hiba' => 'Biztos elküldi jóváhagyásra a munkalapot?',
                    'gomb' => '<input class="gomb piros" value="Igen" onclick="kuldesJovahagyasra( '.$recid.' );" type="button"><input class="gomb zold" value="Nem" onclick="alert2()" type="button">'
                ));

                $weblap->leallit();
                exit();
            }

            /* Átállítjuk a státuszt */
            if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_HOZREND_JOVAHAGY ) ) {
                $db_error = true;
            }

            /* Átállítjuk a személyzet típust */
            $sql = $adatbazis_kapcsolat -> u('munkalap', array('szemelyzet_id'), array(DISZPECSER_KOZP_VEZETO), "`recid` = '{$recid}'");

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
    }

    $weblap->leallit();
    exit();
}

/* Munkalap külső kivitelező hozzárendelése */
if( isset($_POST['kulsokivHozzarend']) ) {
    $recid = $_POST['recid'];
    $szallitoid = $_POST['szallitoid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        /* Ellenőrizzük, hogy hozzárendelés előkészítés alatt van e */
        $sql = $munkalap = $adatbazis_kapcsolat->s('stat_id', 'munkalap', "`recid` = '{$recid}'", '', '1');

        if( $sql === false ) {
            $db_error = true;
        }

        if( $munkalap ) {
            $munkalap = $munkalap[0];

            if( $munkalap['stat_id'] != MUNKALAP_STAT_HOZREND_ELOKESZ ) {
                echo json_encode(array(
                    'hiba' => 'Csak Hozzárendelés előkészítés alatt státuszban lehet külső kivitelezőt megadni!'
                ));

                $weblap->leallit();
                exit();
            }

            if( $validate == 'true' ) {
                echo json_encode(array(
                    'statusz' => 'siker'
                ));

                $weblap->leallit();
                exit();
            }

            $sql = $adatbazis_kapcsolat -> u('munkalap', array('szallito_id'), array($szallitoid), "`recid` = '{$recid}'");

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
    }

    $weblap->leallit();
    exit();
}

/* Munkalap külső kivitelező select előállít */
if( isset($_POST['munkalap_kulsoSelect']) ) {
    require_once('selectek.php');

    $recid = $_POST['recid'];

    if( $select_szemelyzet ) {
        /* Ellenőrizzük, hogy felvéve státuszban van e */
        $sql = $munkalap = $adatbazis_kapcsolat->s('stat_id', 'munkalap', "`recid` = '{$recid}'", '', '1');

        if( $sql === false ) {
            $db_error = true;
        }

        if( $munkalap ) {
            $munkalap = $munkalap[0];

            if( $munkalap['stat_id'] != MUNKALAP_STAT_NYITOTT && $munkalap['stat_id'] != MUNKALAP_STAT_FOLYAMATBAN ) {
                echo json_encode(array(
                    'hiba' => 'Csak nyitott, vagy folyamatban lévő munkalapokat lehet kiadni külsősnek!'
                ));

                $weblap->leallit();
                exit();
            }
        }

        $select = '<select id="p_szemelyzet_id" name="p_szemelyzet_id">';

        foreach($select_szemelyzet as $value) {
            $select .= '<option value="'.$value['value'].'">'.$value['name'].'</option>';
        }

        $select .= '</select>';

        echo json_encode(array(
            'statusz' => 'siker',
            'select' => $select
        ));
    }

    $weblap->leallit();
    exit();
}

/* Munkalap lezárás */
if( isset($_POST['munkalapLezaras']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        $sql = $munkalap = $adatbazis_kapcsolat->s('stat_id', 'munkalap', "`recid` = '{$recid}'", '', '1');

        if( $sql === false ) {
            $db_error = true;
        }

        if( $munkalap ) {
            $munkalap = $munkalap[0];

            if( $munkalap['stat_id'] != MUNKALAP_STAT_KESZ_IG ) {
                echo json_encode(array(
                    'hiba' => 'Csak Kész státuszú munkalapot lehet lezárni!',
                ));

                $weblap->leallit();
                exit();
            }
        }

        //Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if (isset($_POST['csak_kerdezunk'])) {
            echo json_encode(array(
                'hiba' => 'Biztos lezárja a munkalapot?',
                'gomb' => '<input class="gomb piros" value="Igen" onclick="munkalapLezaras();" type="button"><input class="gomb zold" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        if( $recid ) {
            $sql = $munk_kapcsok = $adatbazis_kapcsolat->s('bejelentes_id', 'munkalap_kapcs', "`munkalap_id` = '{$recid}'", '', '1');

            if( $sql === false ) {
                $db_error = true;
            }

            if( $munk_kapcsok ) {
                $munk_kapcsok = $munk_kapcsok[0];

                $sql = $tobb_munk = $adatbazis_kapcsolat->s('recid munkalap_id', 'munkalap_kapcs', "`bejelentes_id` = '".$munk_kapcsok['bejelentes_id']."' AND `munkalap_id` != '{$recid}'", '', '');

                if( $sql === false ) {
                    $db_error = true;
                }

                /* Megnézzük, hogy szerepl e máshol a bejelentés */
                $sql = $bejelentesek_kapcs = $adatbazis_kapcsolat->s('bejelentes_id', 'munkalap_kapcs', "`munkalap_id` = '{$recid}'", '', '');

                if( $sql === false ) {
                    $db_error = true;
                }

                if( $bejelentesek_kapcs ) {
                    $bej_db = count($bejelentesek_kapcs);
                    $bej_lezart_db = 0;

                    foreach($bejelentesek_kapcs as $bej_kapcs) {
                        /* Bejelentés lekérdezése */
                        $sql = $bejelentes = $adatbazis_kapcsolat->s('stat_id', 'bejelentes', "`recid` = '".$bej_kapcs['bejelentes_id']."'", '', '');

                        if( $sql === false ) {
                            $db_error = true;
                        }

                        if( $bejelentes ) {
                            $bejelentes = $bejelentes[0];

                            if( $bejelentes['stat_id'] == BEJELENTES_STAT_LEZART ) {
                                $bej_lezart_db++;
                            }
                        }
                    }

                    /* Minden bejelentése lezárt státusz alatt van, ezért zárható a munkalap */
                    if( $bej_db == $bej_lezart_db ) {
                        if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_LEZART ) ) {
                            $db_error = true;
                        }
                    } else {
                        echo json_encode(array(
                            'hiba' => 'A munkalap igazolásra vár vagy vizsgálat alatt van. Nem lehet lezárni!',
                        ));

                        $weblap->leallit();
                        exit();
                    }

                    /* Lezárjuk az összes többi munkáját */
                    if( $tobb_munk && $bej_db == $bej_lezart_db ) {
                        foreach($tobb_munk as $tmunk) {
                            if( !addStatusz( 'munkalap_statusz', $tmunk['munkalap_id'], MUNKALAP_STAT_LEZART ) ) {
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
            }
        }
    }

    $weblap->leallit();
    exit();
}

/* Diszpécser központ vezető átadás */
if( isset($_POST['diszKozpVezAtadas']) ) {
    $recid  = $_POST['recid'];
    $memo   = $_POST['memo'];
    $validate = $_POST['validate'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        $sql = $munkalap = $adatbazis_kapcsolat->s('stat_id', 'munkalap', "`recid` = '{$recid}'", '', '1');

        if( $sql === false ) {
            $db_error = true;
        }

        if( $munkalap ) {
            $munkalap = $munkalap[0];

            if( $munkalap['stat_id'] == MUNKALAP_STAT_ANYAGRAVAR || $munkalap['stat_id'] == MUNKALAP_STAT_FELVEVE ) {
                echo json_encode(array(
                    'hiba' => 'Anyagra vár és felvéve státuszú munkalapokat nem lehet átadni!',
                ));

                $weblap->leallit();
                exit();
            }

            if( $munkalap['stat_id'] == MUNKALAP_STAT_KESZ_IG ) {
                $munk_kapcsok = $adatbazis_kapcsolat->s('bejelentes_id', 'munkalap_kapcs', "`munkalap_id` = '{$recid}'", '', '1');
                if( $munk_kapcsok === false ) { $db_error = true; }

                if( $munk_kapcsok ) {
                    $munk_kapcsok = $munk_kapcsok[0];

                    $bejelentes = $adatbazis_kapcsolat->s('stat_id', 'bejelentes', "`recid` = '".$munk_kapcsok['bejelentes_id']."'", '', '1');
                    if( $bejelentes === false ) { $db_error = true; }

                    if( $bejelentes ) {
                        $bejelentes = $bejelentes[0];

                        if( $bejelentes['stat_id'] != BEJELENTES_STAT_VIZSG_ALATT ) {
                            echo json_encode(array(
                                'hiba' => 'Csak vizsgálat alatt lévő bejelentéseket lehet átadni!',
                            ));

                            $weblap->leallit();
                            exit();
                        }

                        $sql = $tobb_munk = $adatbazis_kapcsolat->s('recid', 'munkalap_kapcs', "`bejelentes_id` = '".$munk_kapcsok['bejelentes_id']."' AND `munkalap_id` != '{$recid}'", '', '');

                        if( $sql === false ) {
                            $db_error = true;
                        }

                        /* Több munkalaphoz kapcsolódik a bejelentés */
                        if( $tobb_munk ) {
                            $szoveg_kiir = 'A munkalap bejelentéséhez további munkalapok vannak kapcsolva. Ha folytatja, a többi munkalapot sem lehet lezárni, mert a bejelentés visszakerül Folyamatban státuszba. Előbb zárja le a többi munkalapot és utoljára adja vissza ezt a munkalapot a diszpécsernek! Folytatja?';
                        /* Csak egy munkalaphoz kapcsolódik a bejelentés */
                        } else {
                            $szoveg_kiir = 'Ha folytatja, a munkalap és a bejelentései Folyamatban státuszba kerülnek. Folytatja?';
                        }
                    }
                }
            } else {
                echo json_encode(array(
                    'hiba' => 'Csak kész, igazolásra váró státuszú munkalap adható át!'
                ));

                $weblap->leallit();
                exit();
            }
        }

        if( $validate == 'true' ) {
            echo json_encode(array(
                'statusz' => 'siker',
                'szoveg_kiir' => $szoveg_kiir
            ));

            $weblap->leallit();
            exit();
        }


        if( $munkalap ) {
            if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_NYITOTT, '', $memo ) ) {
                $db_error = true;
            }
            if( $memo ) {
                if( addMegj('munkalap', $recid, $memo) === false ) {
                    $db_error = true;
                }
            }

            $sql = $munk_bejek = $adatbazis_kapcsolat->s('bejelentes_id', 'munkalap_kapcs', "`munkalap_id` = '{$recid}'");
            if( $sql === false ) { $db_error = true; }

            if( $munk_bejek ) {
                foreach($munk_bejek as $munk_bej) {
                    if( !addStatusz( 'bejelentes_statusz', $munk_bej['bejelentes_id'], BEJELENTES_STAT_FOLYAMATBAN, '', $memo ) ) {
                        $db_error = true;
                    }
                    if( $memo ) {
                        if( addMegj('bejelentes', $munk_bej['bejelentes_id'], $memo) === false ) {
                            $db_error = true;
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
    }

    $weblap->leallit();
    exit();
}

/* Diszpécser központ vezető lezárása */
if( isset($_POST['diszKozpVezLezaras']) ) {
    $recid    = $_POST['recid'];
    $memo     = $_POST['memo'];
    $validate = $_POST['validate'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        $munkalap = $adatbazis_kapcsolat->s('stat_id', 'munkalap', "`recid` = '{$recid}'", '', '1');
        if( $munkalap === false ) { $db_error = true; }

        if( $munkalap ) {
            $munkalap = $munkalap[0];

            /* Ha nem kész státuszban van a munkalap */
            if( $munkalap['stat_id'] != MUNKALAP_STAT_KESZ_IG ) {
                if( $munkalap['stat_id'] == MUNKALAP_STAT_LEZART ) {
                    echo json_encode(array(
                        'hiba' => 'Lezárt munkalapot nem lehet újra lezárni!',
                    ));

                    $weblap->leallit();
                    exit();
                }

                $is_tobbmunk = false;
                $tobbimunkalap = array();

                /* Megnézzük hogy van e több munkalapja a bejelentésnek */
                $sql = $munk_kapcsok = $adatbazis_kapcsolat->s('bejelentes_id', 'munkalap_kapcs', "`munkalap_id` = '{$recid}'", '', '1');

                if( $sql === false ) {
                    $db_error = true;
                }

                if( $munk_kapcsok ) {
                    $munk_kapcsok = $munk_kapcsok[0];

                    $bejelentes = $adatbazis_kapcsolat->s('stat_id', 'bejelentes', "`recid` = '".$munk_kapcsok['bejelentes_id']."'", '', '1');
                    if( $bejelentes === false ) { $db_error = true; }

                    if( $bejelentes ) {
                        $bejelentes = $bejelentes[0];

                        $sql = $tobb_munk = $adatbazis_kapcsolat->s('recid munkalap_id', 'munkalap_kapcs', "`bejelentes_id` = '".$munk_kapcsok['bejelentes_id']."' AND `munkalap_id` != '{$recid}'", '', '');

                        if( $sql === false ) {
                            $db_error = true;
                        }

                        /* Több munkalaphoz kapcsolódik a bejelentés */
                        if( $tobb_munk ) {
                            $szoveg_kiir = 'A munkalap bejelentéséhez további munkalapok vannak kapcsolva. A többi munkalap nem lesz lezárva. Folytatja?';

                            foreach($tobb_munk as $tmunk) {
                                $tobbimunkalap[] = $tmunk['munkalap_id'];
                            }

                            $is_tobbmunk = true;
                        /* Csak egy munkalaphoz kapcsolódik a bejelentés */
                        } else {
                            $szoveg_kiir = 'Ha folytatja, a munkalap és bejelentései is lezárásra kerülnek. Folytatja?';
                        }
                    }
                }

                if( $validate == 'true' ) {
                    echo json_encode(array(
                        'statusz'     => 'siker',
                        'popup_cim'   => 'Lezárás indoka:',
                        'szoveg_kiir' => $szoveg_kiir
                    ));

                    $weblap->leallit();
                    exit();
                /* Feldolgozás */
                } else {
                    /* Munkalap lezárul */
                    if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_LEZART, '', $memo ) ) {
                        $db_error = true;
                    }

                    if( $memo ) {
                        if( addMegj('munkalap', $recid, $memo) === false ) {
                            $db_error = true;
                        }
                    }

                    /* Ha egy munkalapja van a bejelentéseknek */
                    if( !$is_tobbmunk ) {
                        /* Lezárjuk a bejelentéseket is */
                        $sql = $munk_kapcsok = $adatbazis_kapcsolat->s('bejelentes_id', 'munkalap_kapcs', "`munkalap_id` = '{$recid}'");
                        if( $sql === false ) { $db_error = true; }

                        if( $munk_kapcsok ) {
                            foreach($munk_kapcsok as $munk_kapcs) {
                                if( !addStatusz( 'bejelentes_statusz', $munk_kapcs['bejelentes_id'], BEJELENTES_STAT_LEZART, '', $memo ) ) {
                                    $db_error = true;
                                }

                                if( $memo ) {
                                    if( addMegj('bejelentes', $munk_kapcs['bejelentes_id'], $memo) === false ) {
                                        $db_error = true;
                                    }
                                }
                            }
                        }
                    } else {
                        /* Megnézzük a további munkalapokat, hogy lezártak e */
                        $tm_db = count($tobbimunkalap);
                        $tm_lezart_db = 0;

                        if( $tm_db ) {
                            foreach($tobbimunkalap as $tmunkrecid) {
                                $mlap_statusz = $adatbazis_kapcsolat->s('stat_id', 'munkalap', "`recid` = '{$tmunkrecid}'", '', '1');
                                if( $mlap_statusz === false ) { $db_error = true; }

                                if( $mlap_statusz ) {
                                    $mlap_statusz = $mlap_statusz[0];

                                    if( $mlap_statusz['stat_id'] == MUNKALAP_STAT_LEZART ) {
                                        $tm_lezart_db++;
                                    }
                                }
                            }

                            /* Összes munkalap le van zárva, tehát a bejelentések is zárhatóak */
                            if( $tm_db == $tm_lezart_db ) {
                                /* Lezárjuk a bejelentéseket is */
                                $sql = $munk_kapcsok = $adatbazis_kapcsolat->s('bejelentes_id', 'munkalap_kapcs', "`munkalap_id` = '{$recid}'");
                                if( $sql === false ) { $db_error = true; }

                                if( $munk_kapcsok ) {
                                    foreach($munk_kapcsok as $munk_kapcs) {
                                        if( !addStatusz( 'bejelentes_statusz', $munk_kapcs['bejelentes_id'], BEJELENTES_STAT_LEZART, '', $memo ) ) {
                                            $db_error = true;
                                        }

                                        if( $memo ) {
                                            if( addMegj('bejelentes', $munk_kapcs['bejelentes_id'], $memo) === false ) {
                                                $db_error = true;
                                            }
                                        }
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

                    $weblap->leallit();
                    exit();
                }
            }

            /* Ha a munkalap kész státuszban van */
            if( $munkalap['stat_id'] == MUNKALAP_STAT_KESZ_IG ) {
                $is_tobbmunk = false;
                $tobbimunkalap = array();

                $munk_kapcsok = $adatbazis_kapcsolat->s('bejelentes_id', 'munkalap_kapcs', "`munkalap_id` = '{$recid}'", '', '1');
                if( $munk_kapcsok === false ) { $db_error = true; }

                if( $munk_kapcsok ) {
                    $munk_kapcsok = $munk_kapcsok[0];

                    $bejelentes = $adatbazis_kapcsolat->s('stat_id', 'bejelentes', "`recid` = '".$munk_kapcsok['bejelentes_id']."'", '', '1');
                    if( $bejelentes === false ) { $db_error = true; }

                    if( $bejelentes ) {
                        $bejelentes = $bejelentes[0];

                        if( $bejelentes['stat_id'] != BEJELENTES_STAT_VIZSG_ALATT ) {
                            echo json_encode(array(
                                'hiba' => 'A munkalap bejelentése '.getStatText('bejelentes', $bejelentes['stat_id']).' státuszban van. A munkalapot nem lehet lezárni!',
                            ));

                            $weblap->leallit();
                            exit();
                        }

                        $tobb_munk = $adatbazis_kapcsolat->s('recid munkalap_id', 'munkalap_kapcs', "`bejelentes_id`='".$munk_kapcsok['bejelentes_id']."' AND `munkalap_id`!='{$recid}'");

                        /* Több munkalaphoz kapcsolódik a bejelentés */
                        if( $tobb_munk ) {
                            $szoveg_kiir = 'A munkalap bejelentéséhez további munkalapok vannak kapcsolva. A többi munkalap nem lesz lezárva. Folytatja?';

                            foreach($tobb_munk as $tmunk) {
                                $tobbimunkalap[] = $tmunk['munkalap_id'];
                            }

                            $is_tobbmunk = true;
                        /* Csak egy munkalaphoz kapcsolódik a bejelentés */
                        } else {
                            $szoveg_kiir = 'Ha folytatja, a munkalap és a bejelentései lezárulnak. Folytatja?';
                        }
                    }
                }

                if( $validate == 'true' ) {
                    echo json_encode(array(
                        'statusz'     => 'siker',
                        'popup_cim'   => 'Reklamáció elutasításának oka:',
                        'szoveg_kiir' => $szoveg_kiir
                    ));

                    $weblap->leallit();
                    exit();
                /* Feldolgozás */
                } else {
                    /* Munkalap lezárul */
                    if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_LEZART, '', $memo ) ) {
                        $db_error = true;
                    }

                    if( $memo ) {
                        if( addMegj('munkalap', $recid, $memo) === false ) {
                            $db_error = true;
                        }
                    }

                    /* Ha egy munkalapja van a bejelentéseknek */
                    if( !$is_tobbmunk ) {
                        /* Lezárjuk a bejelentéseket is */
                        $sql = $munk_kapcsok = $adatbazis_kapcsolat->s('bejelentes_id', 'munkalap_kapcs', "`munkalap_id` = '{$recid}'");
                        if( $sql === false ) { $db_error = true; }

                        if( $munk_kapcsok ) {
                            foreach($munk_kapcsok as $munk_kapcs) {
                                if( !addStatusz( 'bejelentes_statusz', $munk_kapcs['bejelentes_id'], BEJELENTES_STAT_LEZART, '', $memo ) ) {
                                    $db_error = true;
                                }

                                if( $memo ) {
                                    if( addMegj('bejelentes', $munk_kapcs['bejelentes_id'], $memo) === false ) {
                                        $db_error = true;
                                    }
                                }
                            }
                        }
                    } else {
                        /* Megnézzük a további munkalapokat, hogy lezártak e */
                        $tm_db = count($tobbimunkalap);
                        $tm_lezart_db = 0;

                        if( $tm_db ) {
                            foreach($tobbimunkalap as $tmunkrecid) {
                                $mlap_statusz = $adatbazis_kapcsolat->s('stat_id', 'munkalap', "`recid` = '{$tmunkrecid}'", '', '1');
                                if( $mlap_statusz === false ) { $db_error = true; }

                                if( $mlap_statusz ) {
                                    $mlap_statusz = $mlap_statusz[0];

                                    if( $mlap_statusz['stat_id'] == MUNKALAP_STAT_LEZART ) {
                                        $tm_lezart_db++;
                                    }
                                }
                            }

                            /* Összes munkalap le van zárva, tehát a bejelentések is zárhatóak */
                            if( $tm_db == $tm_lezart_db ) {
                                /* Lezárjuk a bejelentéseket is */
                                $sql = $munk_kapcsok = $adatbazis_kapcsolat->s('bejelentes_id', 'munkalap_kapcs', "`munkalap_id` = '{$recid}'");
                                if( $sql === false ) { $db_error = true; }

                                if( $munk_kapcsok ) {
                                    foreach($munk_kapcsok as $munk_kapcs) {
                                        if( !addStatusz( 'bejelentes_statusz', $munk_kapcs['bejelentes_id'], BEJELENTES_STAT_LEZART, '', $memo ) ) {
                                            $db_error = true;
                                        }

                                        if( $memo ) {
                                            if( addMegj('bejelentes', $munk_kapcs['bejelentes_id'], $memo) === false ) {
                                                $db_error = true;
                                            }
                                        }
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

                    $weblap->leallit();
                    exit();
                }
            }
        }

        $weblap->leallit();
        exit();

        /* Elutasítás */
        if( $munkalap ) {
            if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_NYITOTT, '', $memo ) ) {
                $db_error = true;
            }
            if( $memo ) {
                if( addMegj('munkalap', $recid, $memo) === false ) {
                    $db_error = true;
                }
            }

            $sql = $munk_bejek = $adatbazis_kapcsolat->s('bejelentes_id', 'munkalap_kapcs', "`munkalap_id` = '{$recid}'");
            if( $sql === false ) { $db_error = true; }

            if( $munk_bejek ) {
                foreach($munk_bejek as $munk_bej) {
                    if( !addStatusz( 'bejelentes_statusz', $munk_bej['bejelentes_id'], BEJELENTES_STAT_FOLYAMATBAN, '', $memo ) ) {
                        $db_error = true;
                    }
                    if( $memo ) {
                        if( addMegj('bejelentes', $munk_bej['bejelentes_id'], $memo) === false ) {
                            $db_error = true;
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
    }

    $weblap->leallit();
    exit();
}

/* Átadás műszaki vezetőnek */
if( isset($_POST['atadasMuszVeznek']) ) {
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

            /* Frissítjük a munkalap tételt */
            $sql = $adatbazis_kapcsolat -> u('munkalap_tetel',
                array('atadta_dt',           'visszaadva', 'update_usr'                           ),
                array( date('Y-m-d H:i:s'),   1,           $_SESSION[get_db_tabla().'teljes_nev'] ),
                "`recid` = '".$akt_tetel['recid']."'"
            );

            if( $sql === false ) {
                $db_error = true;
            }

            /* Frissítjuk a munkalap visszaadva sorát */
            $sql = $adatbazis_kapcsolat -> u('munkalap',
                array('visszaadva', 'szemelyzet_id', 'mobil_loginnev', 'update_dt',          'update_usr'                           ),
                array( 1,            MUSZAKI_VEZETO, '',                date('Y-m-d H:i:s'), $_SESSION[get_db_tabla().'teljes_nev'] ),
                "`recid` = '{$recid}'"
            );

            if( $sql === false ) {
                $db_error = true;
            }

            /* Folyamatban státuszra állítjuk a munkalapot */
            if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_FOLYAMATBAN ) ) {
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
    } else {
        echo json_encode(array(
            'hiba' => 'Adja meg az átadás okát!'
        ));
    }

    $weblap->leallit();
    exit();
}

/* Munkalap anyagok */
if( isset($_POST['getMunkalapFeladatok']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        $sql = $akt_tetel = $adatbazis_kapcsolat->s('recid', 'munkalap_tetel', "`munkalap_id` = '{$recid}'", 'recid desc', '1');
        if( $sql === false ) { $db_error = true; }

        if( $akt_tetel ) {
            $akt_tetel = $akt_tetel[0];

            $sql = $munkalapok = $adatbazis_kapcsolat->s('recid erteklista_id memo', 'munkalap_feladat', "`munkalap_id` = '{$recid}' AND `tetel_id` = '".$akt_tetel['recid']."'", '', '');
            if( $sql === false ) { $db_error = true; }

            if( $munkalapok ) {
                $sorok = '';
                foreach($munkalapok as $munkalap) {
                    $sorok .= nl2('
                        <tr>
                            <td>'.erteklista(5, $munkalap['erteklista_id']).'<input type="hidden" name="addCikkID[]" value="'.$munkalap['cikk_id'].'"><input type="hidden" name="addCikkUj[]" value="0"></td>
                            <td>'.nl2br($munkalap['memo']).'</td>
                            <td><a onclick="munkFeladatTorol( \''.$munkalap['recid'].'\', this );"><span class="icon"><i class="material-icons">delete</i></span></a></td>
                        </tr>
                    ');
                }

                $html = nl2('
                    <table class="csikos">
                        <tr>
                            <th>Elvégzett feladat</th>
                            <th>Leírás</th>
                            <th class="torles"></th>
                        </tr>
                        '.$sorok.'
                    </table>
                ');

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

/* Munkalap átadása mobil csoportnak */
if( isset($_POST['munkalapMobil']) ) {
    $idk = json_decode($_POST['idk'], true);

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    /* Hozzárendelt csoport törlése */
    parse_str($_POST['munkMobilKivetel']);

    /* Hozzárendelt személyzet törlése */
    parse_str($_POST['munkHozzMegszuntet']);

    /* Törzsadat csatolása a munkalaphoz */
    parse_str($_POST['addTorzsID']);
    parse_str($_POST['addTorzsMunkID']);

    /*foreach($idk as $id) {
        $munkalap = $adatbazis_kapcsolat->s('recid ikt_szam stat_id', 'munkalap', "`recid` = '{$id}'", '', '1');
        if( $munkalap === false ) { $db_error = true; }

        if( $munkalap  ) {
            $munkalap = $munkalap[0];

            if( !in_array($munkalap['stat_id'], array(1)) ) {
                echo json_encode(array(
                    'hiba' => 'A(z) "'.$munkalap['ikt_szam'].'" iktatószámú munkalap státusz idő közben megváltozott!'
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
    }*/

    if( is_array($idk) ) {
        $maradStatRecid = array();

        /* Hozzárendelt csoport törlése */
        if( count($munkMobilKivetel) ) {
            foreach($munkMobilKivetel as $value) {
                $maradStatRecid[] = $value;

                $sql = $munkalap = $adatbazis_kapcsolat->s('recid stat_id', 'munkalap', "`recid` = '{$value}'", '', '1');
                if( $sql === false ) {
                    $db_error = '1';
                }

                if( $munkalap ) {
                    $munkalap = $munkalap[0];

                    $sql = $adatbazis_kapcsolat -> u('munkalap',
                        array('szemelyzet_id', 'mobil_loginnev', 'update_usr'                          ),
                        array( 0,              '',               $_SESSION[get_db_tabla().'teljes_nev']),
                        "`recid` = '{$value}'"
                    );
                    if( $sql === false ) {
                        $db_error = '2';
                    }

                    if( $munkalap['stat_id'] != MUNKALAP_STAT_NYITOTT ) {
                        if( !addStatusz( 'munkalap_statusz', $value, MUNKALAP_STAT_NYITOTT ) ) {
                            $db_error = true;
                        }
                    }
                } else {
                    echo json_encode(array(
                        'hiba' => tranzakcioHiba().$db_error
                    ));

                    $weblap->leallit();
                    exit();
                }
            }
        }

        /* Kivesszük a hozzárendelt mobil_loginnev-et */
        if( count($munkHozzMegszuntet) ) {
            foreach($munkHozzMegszuntet as $value) {
                $munkalap = $adatbazis_kapcsolat->s('recid stat_id', 'munkalap', "`recid` = '{$value}'", '', '1');
                if( $munkalap === false ) { $db_error = '3'; }

                if( $munkalap ) {
                    $munkalap = $munkalap[0];

                    $sql = $adatbazis_kapcsolat -> u('munkalap',
                        array('mobil_loginnev', 'update_dt',          'update_usr'                          ),
                        array('',                date('Y-m-d H:i:s'), $_SESSION[get_db_tabla().'teljes_nev']),
                        "`recid` = '{$value}'"
                    );
                    if( $sql === false ) {
                        $db_error = '4';
                    }
                } else {
                    echo json_encode(array(
                        'hiba' => tranzakcioHiba().$db_error
                    ));

                    $weblap->leallit();
                    exit();
                }
            }
        }

        /* Törzsadat hozzárendelése munkalapokhoz */
        if( count($addTorzsMunkID) ) {
            foreach($addTorzsMunkID as $key => $value) {
                $munkalap = $adatbazis_kapcsolat->s('recid stat_id', 'munkalap', "`recid` = '{$value}'", '', '1');
                if( $munkalap === false ) { $db_error = '5'; }

                if( $munkalap ) {
                    $munkalap = $munkalap[0];

                    $sql = $torzslap = $adatbazis_kapcsolat->s('recid felh_loginnev', 'torzsadat', "`recid` = '".$addTorzsID[$key]."'", '', '1');

                    if( $sql === false ) {
                        $db_error = '6';
                    }

                    if( $torzslap ) {
                        $torzslap = $torzslap[0];

                        $sql = $adatbazis_kapcsolat -> u('munkalap',
                            array('szemelyzet_id', 'mobil_loginnev',           'update_usr'                          ),
                            array(2,               $torzslap['felh_loginnev'], $_SESSION[get_db_tabla().'teljes_nev']),
                            "`recid` = '{$value}'"
                        );

                        if( $sql === false ) {
                            $db_error = '7';
                        }
                    } else {
                        echo json_encode(array(
                            'hiba' => tranzakcioHiba().$db_error
                        ));

                        $weblap->leallit();
                        exit();
                    }
                } else {
                    echo json_encode(array(
                        'hiba' => tranzakcioHiba().$db_error
                    ));

                    $weblap->leallit();
                    exit();
                }
            }
        }

        foreach($idk as $id) {
            if( !in_array($id, $maradStatRecid) ) {
                $munkalap = $adatbazis_kapcsolat->s('recid stat_id', 'munkalap', "`recid` = '{$id}'", '', '1');
                if( $munkalap === false ) { $db_error = true; }

                if( $munkalap ) {
                    $munkalap = $munkalap[0];

                    /* Hozzárendeljük a mobil személyzetet */
                    $sql = $adatbazis_kapcsolat -> u('munkalap', array('szemelyzet_id'), array(MOBILCSOPORT), "`recid` = '{$id}'");
                    if( $sql === false ) {
                        $db_error = '8';
                    }

                    /* Folyamatbanra állítjuk */
                    if( $munkalap['stat_id'] != MUNKALAP_STAT_FOLYAMATBAN ) {
                        if( !addStatusz( 'munkalap_statusz', $id, MUNKALAP_STAT_FOLYAMATBAN ) ) {
                            $db_error = '9';
                        }
                    }
                }
            }
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            echo json_encode(array(
                'statusz' => 'siker',
                'beosztasNelkul' => $beosztasNelkul,
                'szemRendelt' => $szemRendelt
            ));
        } else {
            mysqli_rollback($db_kapcs);

            echo json_encode(array(
                'hiba' => tranzakcioHiba().$db_error
            ));
        }
    }

    $weblap->leallit();
    exit();
}

/* Munkalap sorok lekérdezése */
if( isset($_POST['hozzBeosztasNelkul']) ) {
    $idk = json_decode($_POST['idk'], true);

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( is_array($idk) ) {
        foreach($idk as $id) {

            $statusz = $adatbazis_kapcsolat->s('stat_id szemelyzet_id', 'munkalap', "`recid` = '{$id}'");
            if( $statusz === false ) { $db_error = '3'; }

            //2. körben már nem kell figyelni a státuszokból eredő hibákat
            if( $statusz && !isset($_POST['no_statusz_hiba']) ) {
                $statusz = $statusz[0];

                if( !in_array($statusz['stat_id'], array(MUNKALAP_STAT_NYITOTT, MUNKALAP_STAT_FOLYAMATBAN)) ) {
                    echo json_encode(array(
                        'hiba' => 'Kérem csak nyitott vagy folyamatban státuszú munkalapokat jelöljön meg!',
                        'gomb' => '<input class="link gomb" value="Ok" onclick="csoportMuvelet(\'munkalap_mobil\', \'státus hibafigyelés nélkül\'); alert2();" type="button">'
                    ));

                    $weblap->leallit();
                    exit();
                }

                if( $statusz['stat_id'] == MUNKALAP_STAT_FOLYAMATBAN && $statusz['szemelyzet_id']!=MOBILCSOPORT ) {
                    echo json_encode(array(
                        'hiba' => 'Ha a munkalap státusza folyamatban státuszú, akkor csak mobilcsoporthoz rendelt munkalap jelölhető meg!',
                        'gomb' => '<input class="link gomb" value="Ok" onclick="csoportMuvelet(\'munkalap_mobil\', \'státus hibafigyelés nélkül\'); alert2();" type="button">'
                    ));

                    $weblap->leallit();
                    exit();
                }

            }
        }

        $osszes_sor = 0;

        /* Hozzárendelt munkalapok beosztás nélkül */
        $munkalapok = $adatbazis_kapcsolat->s('recid ikt_szam telephely_id stat_id surgos', 'munkalap', "(`szemelyzet_id` = '".MOBILCSOPORT."' AND `stat_id` = '".MUNKALAP_STAT_FOLYAMATBAN."' AND `mobil_loginnev` = '') OR (`recid` IN (".implode(',', $idk).") AND `szemelyzet_id` IN (0, ".MOBILCSOPORT.") AND `stat_id` IN (".MUNKALAP_STAT_NYITOTT.") AND `mobil_loginnev` = '')");
        $osszes_sor += count($munkalapok);
        if( $munkalapok === false ) {
            $db_error = '2';
        }

        $beosztasNelkul = '';
        $sorok = '';
        if( $munkalapok ) {
            $i = 1;
            foreach($munkalapok as $munkalap) {
                /* Telephely */
                $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$munkalap['telephely_id'], '', '1');
                $telephely = $telephely[0]['nev'];

                /* Sürgős e */
                $surgos = 'nem';
                if( $munkalap['surgos'] ) {
                    $surgos = 'igen';
                }

                $sorok .= nl2('
                    <tr class="kijSor" data-recid="'.$munkalap['recid'].'" data-i="'.$i.'">
                        <td class="nev"></td>
                        <td>'.getStatText('munkalap', $munkalap['stat_id']).'</td>
                        <td>'.$munkalap['ikt_szam'].'</td>
                        <td>'.$telephely.'</td>
                        <td>'.$surgos.'</td>
                    </tr>
                ');

                $i++;
            }
        }

        $beosztasNelkul = nl2('
            <table class="csikos kicsi kijCont">
                <tr>
                    <th>Név</th>
                    <th>Mlap státusz</th>
                    <th>Ikt. szám</th>
                    <th>Szálloda</th>
                    <th>Sürgős</th>
                </tr>
                '.$sorok.'
            </table>
        ');

        /* Személyzethez rendelt munkalapok */
        $munkalapok = $adatbazis_kapcsolat->s('recid ikt_szam telephely_id stat_id mobil_loginnev surgos', 'munkalap', "`mobil_loginnev` != '' AND `szemelyzet_id` = '".MOBILCSOPORT."' AND `stat_id` IN (".MUNKALAP_STAT_FOLYAMATBAN.", ".MUNKALAP_STAT_FELVEVE.")");
        $osszes_sor += count($munkalapok);
        if( $munkalapok === false ) { $db_error = '1'; }

        $szemRendelt = '';
        $sorok = '';
        if( $munkalapok ) {
            $i = 1;
            foreach($munkalapok as $munkalap) {
                /* Telephely */
                $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$munkalap['telephely_id'], '', '1');
                $telephely = $telephely[0]['nev'];

                /* Sürgős e */
                $surgos = 'nem';
                if( $munkalap['surgos'] ) {
                    $surgos = 'igen';
                }

                $sorok .= nl2('
                    <tr class="kijSor" data-recid="'.$munkalap['recid'].'" data-statid="'.$munkalap['stat_id'].'" data-i="'.$i.'">
                        <td class="nev">'.$munkalap['mobil_loginnev'].'</td>
                        <td>'.getStatText('munkalap', $munkalap['stat_id']).'</td>
                        <td>'.$munkalap['ikt_szam'].'</td>
                        <td>'.$telephely.'</td>
                        <td>'.$surgos.'</td>
                    </tr>
                ');

                $i++;
            }
        }

        $szemRendelt = nl2('
            <table class="csikos kicsi kijCont">
                <tr>
                    <th>Név</th>
                    <th>Mlap státusz</th>
                    <th>Ikt. szám</th>
                    <th>Szálloda</th>
                    <th>Sürgős</th>
                </tr>
                '.$sorok.'
            </table>
        ');

        if( !$osszes_sor ) {
            echo json_encode(array(
                'hiba' => 'Nincsenek megjeleníthető sorok!'
            ));

            $weblap->leallit();
            exit();
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            echo json_encode(array(
                'statusz'        => 'siker',
                'beosztasNelkul' => $beosztasNelkul,
                'szemRendelt'    => $szemRendelt
            ));
        } else {
            mysqli_rollback($db_kapcs);
            echo json_encode(array(
                'hiba' => tranzakcioHiba().$db_error
            ));
        }
    }

    $weblap->leallit();
    exit();
}

/* Munkalap érkeztetése */
if( isset($_POST['munkalapErkeztet']) ) {


    $munkalap = $adatbazis_kapcsolat->s('stat_id ikt_szam telephely_id', 'munkalap', "`recid` = '{$_POST['recid']}'");
    $munkalap = $munkalap[0];

    if( !in_array($munkalap['stat_id'], array(MUNKALAP_STAT_ANYAGRAVAR)) ) {
        echo json_encode(array(
            'hiba' => $munkalap['ikt_szam'].'<br>Ez a munkalap nem anyagra vár státuszban van. Nem lehet hozzá anyagot érkeztetni!',
        ));

        $weblap->leallit();
        exit();
    }

    $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', "`id`='{$munkalap['telephely_id']}'");
    $telephely = $telephely[0];

    $tablazat = nl2('
        <div style="margin: 1rem 0 0.3rem 0;">Felhasznált és hiányzó anyagok:</div>
        <table class="csikos" style="margin-top: 0">
        <tr>
            <th>Cikk</th>
            <th>Név</th>
            <th>Felhasznált mennyiség</th>
            <th>Szükséges mennyiség</th>
            <th>Mee</th>
            <th>Anyag státusz</th>
            <th>Anyag beérkezett</th>
        </tr>
        ');

    require 'selectek.php';
    $anyagok = $adatbazis_kapcsolat->s('recid cikk_id cikk_nev cikk_men szuk_men cikk_mee stat_id', 'munkalap_anyag', "`munkalap_id`='{$_POST['recid']}'");
    foreach($anyagok as $anyag){

        foreach($munkAnyag_statusz as $kulcs => $stat){
            if($kulcs == $anyag['stat_id']){
                $statusz = $stat;
            }
        }

        //ha anyagstátusz= igénylésre vár vagy igényelve, anyag beérkezett oszlopban pipáló
        //ha anyagstátusz=rendelkezésre áll vagy üres (ez elvileg nem lehet), szürkén mutatjuk sort és nem tud pipálni
        $disabled = '';
        $szurkit  = '';
        if($anyag['stat_id']==DISZPNAG_STAT_VAN_ANYAG || $anyag['stat_id']==''){
            $disabled = ' disabled';
            $szurkit  = ' class="erkeztet_szurkit"';
        }
        $tablazat .= nl2('
                    <tr'.$szurkit.'>
                        <td>'.$anyag['cikk_id'].'</td>
                        <td>'.$anyag['cikk_nev'].'</td>
                        <td class="r">'.$anyag['cikk_men'].'</td>
                        <td class="r">'.$anyag['szuk_men'].'</td>
                        <td>'.$anyag['cikk_mee'].'</td>
                        <td>'.$statusz.'</td>
                        <td>
                            <div class="cb">
                                <input type="checkbox" id="pop_cb_'.$anyag['recid'].'"'.$disabled.' class="pop_cbok" data-recid="'.$anyag['recid'].'">
                                <label for="pop_cb_'.$anyag['recid'].'"></label>
                            </div>
                        </td>
                    </tr>
                    ');
    }
    $tablazat .= nl2('</table>');


    echo json_encode(array(
        'statusz'   => 'siker',
        'ikt_szam'  => $munkalap['ikt_szam'],
        'telephely' => $telephely['nev'],
        'tablazat'  => $tablazat,
    ));


    $weblap->leallit();
    exit();
}
/* Munkalap érkeztetésének mentése */
if( isset($_POST['munkalapErkeztetMent']) ) {

    $idk = json_decode($_POST['idk'], true);

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( is_array($idk) && $idk ) {
        $sql = $adatbazis_kapcsolat->u('munkalap_anyag',    array('szuk_men', 'stat_id',               'update_usr'  ),
                                                            array('0',        DISZPNAG_STAT_VAN_ANYAG, $_SESSION[get_db_tabla().'teljes_nev']  ),
                                        "`recid` IN (".implode(',', $idk).")");
        if( $sql === false ) {
            $db_error = true;
        }

        //ellenőrizzük, hogy van-e még olyan sor, ahol statid=DISZPNAG_STAT_VAN_ANYAG, ha nincs, a munkalap státuszt átállítjuk: Nyitott és viszaadva=1
        $munkalap_id = $adatbazis_kapcsolat->s('munkalap_id', 'munkalap_anyag', "`recid`='{$idk[0]}'");
        if( $munkalap_id === false ) {
            $db_error = true;
        }
        $munkalap_id = $munkalap_id[0];
        $mind_kesz = $adatbazis_kapcsolat->s('recid', 'munkalap_anyag', "`munkalap_id`='{$munkalap_id['munkalap_id']}' AND `stat_id`!='".DISZPNAG_STAT_VAN_ANYAG."'");
        if( $mind_kesz === false ) {
            $db_error = true;
        }
        if(count($mind_kesz)==0){
            if( !addStatusz( 'munkalap_statusz', $munkalap_id['munkalap_id'], MUNKALAP_STAT_NYITOTT ) ) {
                $db_error = true;
            }
            $sql = $adatbazis_kapcsolat->u('munkalap', array('visszaadva'), array(1), "`recid`='{$munkalap_id['munkalap_id']}'");
            if( $sql === false ) {
                $db_error = true;
            }
        }
    }


    if( !$db_error ) {
        mysqli_commit($db_kapcs);
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba' => tranzakcioHiba().$db_error
        ));
    }

    $weblap->leallit();
    exit();
}

/* CSV generálása */
if (isset($_GET['csv'])) {

    $mibol   = get_web_charset();
    $mibe    = 'windows-1250';

    if( !is_dir('export') ) {
        mkdir('export');
    }

    if( !is_dir('export/csv') ) {
        mkdir('export/csv');
    }

    //Régi fájlok törlése
    $utvonal = 'export/csv/';
    $fajlok = konyvtar_bejar($utvonal);
    foreach($fajlok as $fajl){
        if(filemtime($fajl)<(time()-60*60)){
            unlink($fajl);
        }
    }

    $fajlnev = $utvonal.'csv'.time().'.csv';
    $open = fopen($fajlnev,'a');
    if($open){

        $lekerdezes_fromtol = substr($_SESSION['akt_sql'], strpos($_SESSION['akt_sql'], 'FROM'));

        $sql = 'SELECT
                `stat_id` as \'Státusz\',
                `ikt_szam` as \'Munkalap szám\',
                `surgos` as \'Sürgős\',
                `visszaadva` as \'Visszaadva\',
                `text` as \'Leírás\',
                `'.get_db_tabla().'munkalap`.`telephely_id` as \'Szálloda\',
                `obj_id` as \'Helyiség\',
                `gep_id` as \'Gépnév\',
                `tipus_id` as \'Típus\',
                `szallito_id` as \'Külső kivitelező\',
                `szemelyzet_id` as \'Hozzárendelt személyzet\'
                '.$lekerdezes_fromtol;

        $query      = mysqli_query($db_kapcs, $sql);

        $csvhead    = array();
        while($fejlec_nevek = mysqli_fetch_field($query)){
            $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
        }
        fputcsv($open, $csvhead, ';');

        while( $sorok = mysqli_fetch_assoc($query) ){
            $csvcont = array();
            foreach( $sorok as $kulcs => $sor ) {
                if($kulcs=='Szálloda'){
                    $sor = getErtek('nev', 'telephely', 'id', $sor);
                }
                if($kulcs=='Bejelentett hiba'){
                    $sor = getErtek('info', 'erteklista', 'recid', $sor);
                }
                if($kulcs=='Helyiség'){
                    $sor = getErtek('nev', 'obj', 'recid', $sor);
                }
                if($kulcs=='Gépnév'){
                    $sor = getErtek('nev', 'eszkozkezeles', 'recid', $sor);
                }
                if($kulcs=='Típus'){
                    $sor = erteklista(6, $sor);
                }
                if($kulcs=='Státusz'){
                    $sor = getStatText('munkalap', $sor);
                }

                $csvcont[] = iconv2($mibol, $mibe, strip_tags( htmlspecialchars_decode( $sor ) ) );
            }
            fputcsv($open, $csvcont, ';');
        }
    }
    fclose($open);

    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=$megnevezes.csv");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo fajlbol_olvas($fajlnev);

    $weblap->leallit();
    exit();
}

/** Tartalom */
if( !$_GET['ajax'] ) {
    require_once('header.php');
}
require_once('selectek.php');

if($gyorskereso_text){
    $dl = nl2('
                <input type="text" value="'.htmlspecialchars($_GET['ksz']).'" name="'.$gyorskereso_id.'" id="'.$gyorskereso_id.'" placeholder="'.$gyorskereso_text.'" autocomplete="off">
                <script>
                    $(document).ready(function() {
                        $(\'#'.$gyorskereso_id.'\').autocomplete({
                            \'php_fajl\'        : \'../autocomplete.php\',
                            \'tabla\'           : \''.$megnevezes.'\',
                            \'oszlop_keres\'    : \''.$oszlop_keres.'\',
                            \'oszlop_listaz\'   : \''.$oszlop_listaz.'\'
                        });
                    });
                </script>
            ');
    $gyk_sablon  = fajlbol_olvas('../sablon/admin/gyorskereso.html',  true);
    $gyk_sablon2 = fajlbol_olvas('../sablon/admin/gyorskereso2.html', true);
    $gyk_mit     = array('{ACTION}', '{FELIRAT}',       '{DL}', '{EGYEB_DE_FORM_NEM_LEHET}');
    $gyk_mivel   = array(self(),     $gyorskereso_text, $dl);
    $gyk  = str_replace($gyk_mit, $gyk_mivel, $gyk_sablon);
    $gyk2 = str_replace($gyk_mit, $gyk_mivel, $gyk_sablon2);
}

    if( !$_GET['ajax'] ) {
        echo '<div id="szamlaBetolt" data-recid="'.$_GET['id'].'" data-page="'.str_replace('.php', '', basename(self())).'">';
    }

    /* Megjegyzések darabszáma */
    $megj_db = '';
    $megj_sorok = $adatbazis_kapcsolat->s('recid', 'megjegyzes', "`tabla` = '".$megnevezes."' AND `id` = '".$_GET['id']."'", '', '');
    if( $megj_sorok ) {
        $megj_db = ' ('.count($megj_sorok).')';
    }

    /* Dokumentumok darabszáma */
    $dok_db = '';
    $dok_sorok = $adatbazis_kapcsolat->s('recid utvonal', $megnevezes.'_dok', "`{$megnevezes}_id` = '".$_GET['id']."'", '', '');
    if( $dok_sorok ) {
        $dok_db = 0;

        foreach($dok_sorok as $dok_sor) {
            if( is_file('../'.get_utvonal().$megnevezes.'/'.$dok_sor['utvonal']) ) {
                $dok_db += 1;
            }
        }

        $dok_db = ' ('.$dok_db.')';
    }

    if( isset($_GET['modosit']) || isset($_GET['tortenet']) || isset($_GET['naptar']) || /*isset($_GET['bejelentesek']) ||*/ isset($_GET['tortenet']) || isset($_GET['anyagigenyles']) || isset($_GET['elszamolas']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) || isset($_GET['tetel']) ) {
        /* Számla fülek */
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'tetel'         => 'Tételek',
            'tortenet'      => 'Történet',
            //'bejelentesek'  => 'Bejelentések',
            'elszamolas'    => 'Elszámolás',
            'anyagigenyles' => 'Anyagigénylések',
            'dokumentumok'  => 'Dokumentumok'.$dok_db,
            'megjegyzesek'  => 'Megjegyzések'.$megj_db,
        );

        $fulek = '';
        foreach($ful_tomb as $key => $value) {
            $active = 'active';
            if( isset($_GET[$key]) ) {
                $active = ' class="active"';
            }

            $fulek .= '<a href="javascript: szamlaBetolt('.$_GET['id'].', \''.basename(self()).'\', \''.$key.'\'); void(0);"'.$active.' data-oldal="'.$key.'">'.$value.'</a>';
        }

        $szamla_html .= str_replace(
            array('{FULEK}'),
            array( $fulek  ),
            fajlbol_olvas('../sablon/admin/szamla_fulek.html', true)
        );
    }

    /* Gombok kirajzolása minden oldalra */
    $gombok = '';
    if( isset($_GET['uj']) ) {

        $disabled = '';
        $class = '';
        if( !$_GET['ujkent'] ) {
            $disabled = ' disabled';
        } else {
            $class = ' gombb_alert';
        }

        /* Gombok */
        $gombok = nl2('
            <div class="form_kuldes">
                <input type="button" class="gombb'.$class.' mentes_gomb" name="mehet_gomb" id="mehet_gomb"'.$disabled.' value="Mentés" onclick="$(\'#szerk_form\').submit()">
            </div>
        ');

        $muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 16).'px);"';
        if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
            if( $_COOKIE['csuszkaSzelesseg'] ) {
                $muv_style = ' style="width: calc(100% - '.($_COOKIE['csuszkaSzelesseg'] + 16).'px);"';
            }
        }

        /* Műveletek box */
        $gombok = nl2('
            <div class="muveletek"'.$muv_style.'>
                '.$gombok.'
            </div>
        ');

    /* Módosítás */
    } else {

        $sor = $adatbazis_kapcsolat -> select('*', $megnevezes, 'recid='.$_GET['id']);
        $sor = $sor[0];

        //Js, a törlés gombhoz
	    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';
	    if($elonezet){
           $gombok .= ' <input type="button" class="gombb" onclick="window.open(\''.$elonezet.'\')" value="Előnézet" title="Előnézet">';
        }
        if($sorrendezes){
            $gombok .= ' <input type="button" class="gombb" onclick="location.href=\''.self().'?sorrend\'" value="Sorrend" title="Sorrendezés">';
        }

        //Csak diszpécser vagy diszpkp vezető törölhet
        if(!_jog(JOG_DISZPECSER) && !_jog(JOG_MUNKALAP_DISZP_KP_VEZ)){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Nincs jogosultsága munkalap törléséhez!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        }else{

            //Egyébként nézzük meg, hogy van-e tétel vagy bejelentés rajta. Ha van, akkor sem törölhető
            if($adatbazis_kapcsolat->s('id', 'munkalap_tetel', "`munkalap_id`='{$_GET['id']}'")){
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'A munkalap nem törölhető, mert munkavégzéseket tartalmaz!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
            }elseif($adatbazis_kapcsolat->s('recid ', 'munkalap_kapcs', "`munkalap_id` = '{$_GET['id']}'")){
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Munkalap törlése előtt vegye le róla a bejelentéseket!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
            }else{
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
            }

            //Munkalap adatlapja csak akkor szerkeszthető, ha a státusz nyitott vagy folyamatban, és a belépett felhasználó diszpécser vagy diszpécser közp vezető
            if( isset($_GET['modosit']) && in_array($sor['stat_id'], array(MUNKALAP_STAT_NYITOTT, MUNKALAP_STAT_FOLYAMATBAN)) ) {
                $gombok = nl2('
                    <div class="form_kuldes">
                        <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="$(\'#szerk_form\').submit()">
                    </div>
                ').$gombok;
            }
        }


        /* Kijelöltekkel való művelet gombok */
        if( _jog(JOG_DISZPECSER) ) {
            $gombok .= nl2('
                <div id="kmCont">
                    <script>
                        csoportKiveszGomb = "<p class=\"c\"><input class=\"gomb piros\" type=\"button\" value=\"Igen\" onclick=\"csoportbolTorol(\'csoport_id\');\"><input class=\"gomb zold\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
                    </script>
                    <div class="kijeloltek_muv">
                        <input type="button" class="gomb foGomb" value="Csoportosítás..." title="Csoportosítás...">
                        <div class="muv">
                            <ul>
                                <li><a onclick="csoportMuvelet(\'csoporthoz_adas\');">Csoporthoz adás</a></li>
                                <li><a onclick="if(balnav_kijelol_ell(\'egy csoportot szabad megbontani\')){ alert2(\'Biztos kiveszi a kijelölt munkalapokat a megadott csoportokból?\', csoportKiveszGomb); }">Csoportból kivétel</a></li>
                                <li><a onclick="csa_alert();">Csoport átnevezése</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="kijeloltek_muv">
                        <input type="button" class="gomb foGomb" value="Várólista..." title="Várólista...">
                        <div class="muv">
                            <ul>
                                <li><a onclick="varolistahozAdas(\'csak kérdezünk még\');">Várólistához adás</a></li>
                                <li><a onclick="varolistabolKivetel(\'csak kérdezünk még\');">Várólistból kivétel</a></li>
                            </ul>
                        </div>
                    </div>
            ');

            if( _jog(JOG_MUNKALAP_MEGTEKINT) && _jog(JOG_MUNKALAP_DISZP_KP_VEZ) ) {
                $gombok .= nl2('
                    <div class="kijeloltek_muv">
                        <input type="button" class="gombb foGomb" value="Munka átadása..." title="Munka átadása...">
                        <div class="muv">
                            <ul>
                                <li><a onclick="csoportMuvelet( \'diszKozpVezAtadas\' );">Diszpécser</a></li>
                            </ul>
                        </div>
                    </div>
                    <input type="button" class="gomb_alert piros foGomb" value="Lezárás" title="Lezárás" onclick="csoportMuvelet( \'diszKozpVezLezaras\' );">
                ');
            } else {
                $gombok .= nl2('
                    <div class="kijeloltek_muv">
                        <input type="button" class="gomb foGomb" value="Munka átadása..." title="Munka átadása...">
                        <div class="muv">
                            <ul>
                                <li><a onclick="munkalapTelepitett(\'csak kérdezünk még\');">Telepített</a></li>
                                <li><a onclick="csoportMuvelet(\'munkalap_mobil\');">Mobil</a></li>
                                <li><a onclick="csoportMuvelet(\'munkalap_kulso\');">Külső</a></li>
                                <li><a onclick="csoportMuvelet(\'munkalap_adminisztrator\');">Adminisztrátor</a></li>
                                <li><a onclick="csoportMuvelet(\'munkalap_muszaki_vezeto\');">Műszaki vezető</a></li>
                                <li><a onclick="csoportMuvelet(\'munkalap_teruleti_vezeto\');">Területi vezető</a></li>
                                <li><a onclick="csoportMuvelet(\'munkalap_diszp_kozp\');">Diszp. közp. vezető</a></li>
                            </ul>
                        </div>
                    </div>
                ');
            }

            $gombok .= '</div>';

        //Ha a munkalapot megtekintheti, és Telepített, mobil vagy műszaki vezető, és a munkalap státusza felvéve vagy kész, akkor jelennek meg további gombok a munka felvétel gomb helyett (ez a következő ág)
        } else if( _jog(JOG_MUNKALAP_MEGTEKINT) && ( (_jog(JOG_MUNKALAP_TELEPITETT) || _jog(JOG_MUNKALAP_MOBIL)) || _jog(JOG_MUNKALAP_MUSZAKI_VEZ)) && in_array($sor['stat_id'], array(MUNKALAP_STAT_FELVEVE, MUNKALAP_STAT_KESZ_IG)) ) {

            $telephely = $adatbazis_kapcsolat->s('modell', 'telephely', 'id='.$sor['telephely_id'], '', '1');

            $atadas_gomb = '';
            if( $telephely ) {
                $telephely = $telephely[0]['modell'];

                /* Több szállodás modell */
                if( !$telephely ) {
                    $atadas_gomb = nl2('
                        <script>
                            muszVezAtadGomb = "<p class=\"c\"><input class=\"gomb piros\" type=\"button\" value=\"Igen\" onclick=\"atadasMuszVeznek();\"><input class=\"gomb zold\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
                        </script>
                        <input type="button" class="gomb foGomb" value="Átadás Műsz.Vez.-ne" title="Átadás Műsz.Vez.-nek" onclick="alert2(\'Biztos, hogy átadja a munkalapot Műszaki Vezetőnek?\', muszVezAtadGomb);">
                    ');
                /* Egy szállodás modell */
                } else {
                    $atadas_gomb = '<input type="button" class="gomb foGomb" value="Átadás diszpécsernek" title="Átadás diszpécsernek" onclick="csoportMuvelet(\'atadasDiszpecsernek\')">';
                }
            }

            $gombok .= nl2('
                <input type="button" class="gomb foGomb" value="Elvégzett feladat hozzáadása" title="Elvégzett feladat hozzáadása" onclick="csoportMuvelet(\'feladatHozzaad\')">
                <input type="button" class="gomb foGomb" value="Készre jelentés" title="Készre jelentés" onclick="munkalapKeszre(\'csak kérdezünk még\');">
                '.$atadas_gomb.'
                <input type="button" class="gomb foGomb" value="Anyagigénylés szükséges" title="Anyagigénylés szükséges" onclick="csoportMuvelet(\'munkalapAnyagigenyles\');">
                <input type="button" class="gomb foGomb" value="Anyag vételezés" title="Anyag vételezés" onclick="csoportMuvelet(\'munkalapAnyagvetelezes\');">
                <input type="button" class="gomb_alert piros foGomb" value="Lezárás" title="Lezárás" onclick="munkalapLezaras(\'csak kérdezünk még\');">
            ');

        //Ha a munkalapot megtekintheti, és Telepített, mobil vagy műszaki vezető, akkor felveheti a munkát
        } else if( _jog(JOG_MUNKALAP_MEGTEKINT) && ( (_jog(JOG_MUNKALAP_TELEPITETT) || _jog(JOG_MUNKALAP_MOBIL)) || _jog(JOG_MUNKALAP_MUSZAKI_VEZ)) ) {

            $telephely = $adatbazis_kapcsolat -> select('nev', 'telephely', "`id`='{$sor['telephely_id']}'");
            $telephely = $telephely[0];

            if( $sor['stat_id'] == MUNKALAP_STAT_FOLYAMATBAN ) {
                $onclick = 'alert2(munkalapFelveszKerdes, munkalapFelvetelGomb);';
            }else{
                $onclick = 'alert2(\'Csak folyamatban levő munkalap vehető fel!\', \'\', \'Hiba\');';
            }

            $gombok .= nl2('
                <script>
                    var munkalapFelveszKerdes = \'
                        <div class="l">
                            Iktatószám: '.$sor['ikt_szam'].'
                            <br>Szálloda: '.addslashes($telephely['nev']).'
                        </div>
                        <br>Felveszi ezt a munkalapot?\';

                    munkalapFelvetelGomb = "<p class=\"c\"><input class=\"gomb piros\" type=\"button\" value=\"Igen\" onclick=\"munkalapFelvesz(\''.$_GET['id'].'\');\"><input class=\"gomb zold\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
                </script>
                <input type="button" class="gomb foGomb" value="Munka felvétele" title="Munka felvétele" onclick="'.$onclick.'">
            ');
        } else if( _jog(JOG_MUNKALAP_MEGTEKINT) && _jog(JOG_MUNKALAP_MUSZAK_ADMIN) ) {
            $gombok .= nl2('
                <input type="button" class="gomb foGomb" value="Külső kivitelező hozzárendelése" title="Külső kivitelező hozzárendelése" onclick="csoportMuvelet(\'kulsokivHozzarend\');">
                <input type="button" class="gomb foGomb" value="Küldés jóváhagyásra" title="Küldés jóváhagyásra" onclick="kuldesJovahagyasra(\''.$_GET['id'].'\', \'csak kérdezünk még\');">
                <input type="button" class="gomb foGomb" value="Megrendelve" title="Megrendelve" onclick="munkalapMegrendelve(\''.$_GET['id'].'\', \'csak kérdezünk még\');">
            ');
        }

        if( _jog(JOG_MUNKALAP_MUSZAKI_VEZ) ){
            $gombok .= '<input type="button" class="gomb foGomb" value="Anyag érkeztetés" title="Anyag érkeztetés" onclick="csoportMuvelet(\'munkalapErkeztet\')">';
        }

        $muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 16).'px);"';
        if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
            if( $_COOKIE['csuszkaSzelesseg'] ) {
                $muv_style = ' style="width: calc(100% - '.($_COOKIE['csuszkaSzelesseg'] + 16).'px);"';
            }
        }

        /* Műveletek box */
        $gombok = nl2('
            <div class="muveletek"'.$muv_style.'>
                '.$gombok.'
            </div>
        ');
    }

    
    $js_submit_elott = 'return form_ell_admin();';
    
	//Új munkalap felvitele
	if(     isset($_GET['uj']) &&
            (
                _jog(JOG_MUNKALAP_TELEPITETT)   ||
                _jog(JOG_MUNKALAP_MOBIL)        ||
                _jog(JOG_MUNKALAP_MUSZAKI_VEZ)  ||
                _jog(JOG_MUNKALAP_TER_FELELOS)  ||
                _jog(JOG_MUNKALAP_DISZP_KP_VEZ) ||
                _jog(JOG_MUNKALAP_MUSZAK_ADMIN) ||
                _jog(JOG_MUNKALAP_ADMIN)        ||
                _jog(JOG_MUNKALAP_KULSO)
            )
        ) {

        //NAVIGÁCIÓ
        $navi_sablon  = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit     = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel   = array( $navi,             '',         $gyk2 );
        $szamla_html .= str_replace($navi_mit, $navi_mivel, $navi_sablon);

	    //HIBAüZENETEK
	    require_once('info.php');

	    //FORM
		if($_GET['ujkent']) {
            $sor = $adatbazis_kapcsolat -> select('*', $megnevezes, 'recid='.$_GET['ujkent']);

            if( $sor ) {
                $sor = $sor[0];

                foreach($akt_tomb as $ertek) {
                    if( $ertek['name'] != 'ikt_szam' ) {
                        $_SESSION[$ertek['name']] = stripslashes( $sor[$ertek['name']] );
                    } else {
                        $_SESSION[$ertek['name']] = '';
                    }
    			}
            }

            foreach($akt_tomb as $ertek) {
				$sor[$ertek['name']] = $_SESSION[$ertek['name']];
			}
		}

	    $js = js_kiir($akt_tomb, 'form_ell_admin()');

        //Ckeditort csak akkor húzzuk be, ha kell
        if(is_file($ckeditor_js)){
            $ckeditor = '<script src="'.$ckeditor_js.'"></script>';
        }

	    $form_sablon = fajlbol_olvas('../sablon/admin/form_uj.html', true);

        /* Javascipt-es ellenőrzéshez */
        $tabla = str_replace('.php', '', basename(self()));

        $rend_mezok = $adatbazis_kapcsolat->s('`column_name`', '`information_schema`.columns', '`table_schema` LIKE  \''.get_db().'\' AND  `table_name` LIKE  \''.get_db_tabla().$tabla.'\'', '', '');
        $rend_mezok = adatbazis2tomb($rend_mezok, 'column_name');

        $sor = array();
        foreach($rend_mezok as $rend_mezo) {
            $sor[$rend_mezo] = '';
        }

        foreach($akt_tomb as $tomb) {
            if( $tomb['type'] == 'autocomplete' ) {
                $sor[$tomb['name'].'_autocomp'] = '';
            }
        }

        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($sor).'\'); </script>';

		// Ez elkészíti a $mezok változót
		require('switch.php');

        $mezok .= '<input type="hidden" id="ujkentSzerkID" name="ujkentSzerkID" value="'.$_GET['ujkent'].'">';

	    $form_mit     = array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{CK_EDITOR}', '{GOMBOK}', '{MUV_STYLE}'   );
	    $form_mivel   = array($js,    $js_submit_elott,    self(),     $mezok,    $megnevezes,    $ckeditor,     $gombok,    $muv_style      );
	    $szamla_html .= str_replace($form_mit, $form_mivel, $form_sablon);

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom'  => $szamla_html,
                'link'      => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

	//Termék módosítása
	}elseif(isset($_GET['modosit'])) {

        //Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
        }

	    $sor = $adatbazis_kapcsolat -> select('*', $megnevezes, 'recid='.$_GET['id']);
		$sor = $sor[0];
//echo $adatbazis_kapcsolat->gp() . '<br>';
        if( $sor['datetime'] ) {
            $_SESSION['formMod'] = array(
                'datetime' => $sor['update_dt'],
                'oldal'    => basename(self())
            );
        }

        foreach($akt_tomb as $tomb) {
            if( $tomb['type'] == 'autocomplete' ) {
                $kulso_ertek = $adatbazis_kapcsolat->s('recid nev', $tomb['tabla'], 'recid='.$sor[$tomb['name']], '', '1');

                $sor[$tomb['name'].'_autocomp'] = '';
                if( $kulso_ertek ) {
                    $sor[$tomb['name'].'_autocomp'] = $kulso_ertek[0]['nev'];
                }
            }
        }

        $json_sor = array();
        foreach($sor as $kulcs => $elem){
            $json_sor[$kulcs] = nl2(str_replace(array("\n", "'"), array("#UJSOR#", "#APOSZTROF#"), $elem));
        }
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

        $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$sor['telephely_id'], '', '1');
        $telephely = $telephely[0]['nev'];

        $munkalap_kapcs = $adatbazis_kapcsolat->s('recid', 'munkalap_kapcs', "`munkalap_id` = '".$sor['recid']."'", '', '');

        //Duplákáláskor feltett kérdés
        $szamla_html .= nl2('
            <script>
                duplikalGomb  = "<p class=\"c\"><input class=\"gomb zold\" type=\"button\" value=\"Igen\" onclick=\"location.href = \''.basename(self()).'?uj&ujkent='.$_GET['id'].'\'; \"><input class=\"gomb piros\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
                duplikaKerdes = \'Iktatószám: '.$sor['ikt_szam'].'<br>Szálloda: '.$telephely.'<br><br>Ez a munkalap '.count($munkalap_kapcs).' db bejelentésből készült. <br>Ezek a bejelentések az új munkalaphoz is hozzá lesznek rendelve. <br>Biztos, hogy duplikálja?\';
            </script>
        ');

        if($id_latszik){
            $navi = '<b>#'.$_GET['id'].'</b> '.$navi;
        }

        //NAVIGÁCIÓ
        $navi_sablon = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit    = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel  = array( $navi,             '',         $gyk2 );
        //echo str_replace($navi_mit, $navi_mivel, $navi_sablon);

	    //HIBAüZENETEK
        require_once('info.php');

	    //FORM
	    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

		//Form actionja
		$action = self().get_get(array('errnum','kephiba','siker'));

        if(isset($_GET['ujkent'])){
			foreach($akt_tomb as $ertek){
				$sor[$ertek['name']] = $_SESSION[$ertek['name']];
			}
		}

        //Ckeditort csak akkor húzzuk be, ha kell
        if(is_file($ckeditor_js)){
            $ckeditor = '<script src="'.$ckeditor_js.'"></script>';
        }

		// Ez elkészíti a $mezok változót
		require('switch.php');
        $mezok .= '<input type="hidden" id="ujkentSzerkID" name="ujkentSzerkID" value="'.$_GET['ujkent'].'">';

	    $szamla_html .= str_replace(
            array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}'    ),
            array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok       ),
            fajlbol_olvas('../sablon/admin/form_modosit.html', true)
        );

        //Bejelentés táblázata
        $munkalap_kapcsok = $adatbazis_kapcsolat->s('recid bejelentes_id', 'munkalap_kapcs', "`munkalap_id` = '{$_GET['id']}'");
        if( $munkalap_kapcsok ) {
            $szamla_html .= nl2('
                <div class="dt naplo kijCont" data-tabla="munkalap_kapcs" data-mellek="bejelentesek" style="margin-top: 3rem">
                    <div class="dtr nFejlec">
                        <div class="dtc">Bejelentés szám</div>
                        <div class="dtc">Szálloda</div>
                        <div class="dtc">Hiba</div>
                        <div class="dtc">Hiba leírása</div>
                        <div class="dtc">Bejelentő</div>
                        <div class="dtc">Bejelentve</div>
                        <div class="dtc">Státusz</div>
                    </div>
            ');
            $i = 1;
            foreach($munkalap_kapcsok as $munkalap_kapcs) {

                $sor = $adatbazis_kapcsolat->s2('recid ikt_szam telephely_id obj_id hiba_id insert_dt stat_id hiba_text usr_nev usr_telefon usr_email usr_beosztas_id usr_reszleg_id', 'bejelentes', "`recid` = '".$munkalap_kapcs['bejelentes_id']."'", '', '1');
                if( $sor ) {

                    /* Telephely */
                    $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$sor['telephely_id'], '', '1');
                    $telephely = $telephely[0]['nev'];

                    /* Helység */
                    $helyseg = $adatbazis_kapcsolat->s('nev', 'obj', 'recid='.$sor['obj_id'], '', '1');
                    $helyseg = $helyseg[0]['nev'];

                    /* Hiba */
                    $hiba = $adatbazis_kapcsolat->s('info', 'erteklista', "`csoport`=4 AND `recid`='{$sor['hiba_id']}'");
                    $hiba = $hiba[0];

                    $szamla_html .= nl2('
                        <div id="naploSor-'.$sor['recid'].'" class="dtr nSor kijSor" data-id="'.$sor['recid'].'" data-i="'.$i.'">
                            <div class="dtc"><a href="bejelentes.php?id='.$sor['recid'].'&modosit" target="_blank">'.$sor['ikt_szam'].'</a></div>
                            <div class="dtc">'.$telephely.' - '.$helyseg.'</div>
                            <div class="dtc">'.$hiba['info'].'</div>
                            <div class="dtc">'.nl2br2($sor['hiba_text']).'</div>
                            <div class="dtc">
                                '.$sor['usr_nev'].'
                                <br>'.erteklista(18, $sor['usr_beosztas_id']).'
                                <br>'.$sor['usr_telefon'].'
                                <br>'.erteklista(17, $sor['usr_reszleg_id']).'
                                <br>'.$sor['usr_email'].'
                            </div>
                            <div class="dtc">'.$sor['insert_dt'].'</div>
                            <div class="dtc">'.getStatText('bejelentes', $sor['stat_id']).'</div>
                        </div>
                    ');
                }

                $i++;
            }
        }

        $szamla_html .= '</div>';

        // Diszpécser a sürgős pipát mégis eléri
        if(_jog(JOG_MUNKALAP_MUSZAKI_VEZ) && $sor['stat_id']==MUNKALAP_STAT_FELVEVE ){
            $szamla_html .= nl2('
                <script>
                $(document).ready(function () {
                    $(\'#surgos\').removeAttr(\'disabled\');
                });
                </script>
                ');
        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));

        } else {
            echo $szamla_html;
        }

        if($tobb_kep){
			require_once('tobb_kep.php');
		}

    } elseif(isset($_GET['tetel'])) {

        /* Tétel sorok */
        $sorok = $adatbazis_kapcsolat->s('recid atadta_dt visszaadva erteklista_id loginnev insert_dt insert_usr', 'munkalap_tetel', "`munkalap_id` = '".$_GET['id']."'", 'insert_dt desc', '');

        if( $sorok ) {
            foreach($sorok as $sor) {
                $szamla_html .= nl2('
                    <table class="csikos tetel_csikos full">
                        <tr>
                            <th class="nev">Személyzet</th>
                            <th>Felvette</th>
                            <th>Átadta</th>
                            <th>Visszaadva</th>
                        </tr>
                ');

                $visszaadva = '';
                if( $sor['visszaadva'] ) {
                    $visszaadva = '<span class="icon piros"><i class="material-icons">done</i></span>';
                }

                $atadta = '';
                if( $sor['atadta_dt'] != '0000-00-00 00:00:00' ) {
                    $atadta = date('Y.m.d H:i', strtotime($sor['atadta_dt']));
                }

                $atadas_oka = erteklista(7, $sor['erteklista_id']);
                if( $atadas_oka ) {
                    $atadas_oka = '<br>'.$atadas_oka;
                }

                /* Szerepkör */
                $tetel_felh = $adatbazis_kapcsolat->s('szerepkor_id', 'login', "`nev` = '".$sor['loginnev']."'", '', '');

                $szerepkor = '';
                if( $tetel_felh ) {
                    $tetel_felh = $tetel_felh[0];

                    $szerepkor_sor = $adatbazis_kapcsolat->s('nev', 'szerepkor', "`recid` = '".$tetel_felh['szerepkor_id']."'", '', '1');

                    if( $szerepkor_sor ) {
                        $szerepkor_sor = $szerepkor_sor[0];

                        $szerepkor = '<br>('.$szerepkor_sor['nev'].')';
                    }
                }

                $szamla_html .= nl2('
                    <tr >
                        <td>'.$sor['insert_usr'].$szerepkor.'</td>
                        <td>'.date('Y.m.d H:i', strtotime($sor['insert_dt'])).'</td>
                        <td>'.$atadta.'</td>
                        <td>'.$visszaadva.$atadas_oka.'</td>
                    </tr>
                ');

                $szamla_html .= '</table>';

                /* Elvégzett feladatok lekérdezése */
                $feladatok = $adatbazis_kapcsolat->s('erteklista_id tetel_id memo', 'munkalap_feladat', "`tetel_id` = '{$sor['recid']}'", 'insert_dt desc', '');

                if( $feladatok ) {
                    $szamla_html .= nl2('
                        <table class="csikos full al behuz">
                            <tr>
                                <th>Elvégzett feladat</th>
                                <th>Leírás</th>
                            </tr>
                    ');

                    foreach($feladatok as $feladat) {
                        $szamla_html .= nl2('
                            <tr>
                                <td>'.erteklista(5, $feladat['erteklista_id']).'</td>
                                <td>'.nl2br($feladat['memo']).'</td>
                            </tr>
                        ');
                    }

                    $szamla_html .= '</table>';
                }

                /* Elvégzett feladatok lekérdezése */
                $anyagok = $adatbazis_kapcsolat->s('cikk_id cikk_nev cikk_men szuk_men cikk_mee stat_id', 'munkalap_anyag', "`tetel_id` = '{$sor['recid']}'", 'insert_dt desc', '');

                if( $anyagok ) {
                    $szamla_html .= nl2('
                        <table class="csikos full al behuz">
                            <tr>
                                <th class="anyag_th1">Cikkszám</th>
                                <th class="anyag_th2">Anyag felhasználás</th>
                                <th class="anyag_th3">Felhasznált mennyiség</th>
                                <th class="anyag_th4">Szükséges mennyiség</th>
                                <th class="anyag_th5">Anyag státusz</th>
                            </tr>
                    ');

                    foreach($anyagok as $anyag) {
                        $cikk_men = '';
                        if( $anyag['cikk_men'] > 0 ) {
                            $cikk_men = $anyag['cikk_men'].' '.$anyag['cikk_mee'];
                        }

                        $szuk_men = '';
                        if( $anyag['szuk_men'] > 0 ) {
                            $szuk_men = $anyag['szuk_men'].' '.$anyag['cikk_mee'];
                        }

                        $szamla_html .= nl2('
                            <tr>
                                <td>'.$anyag['cikk_id'].'</td>
                                <td>'.$anyag['cikk_nev'].'</td>
                                <td class="r">'.$cikk_men.'</td>
                                <td class="r">'.$szuk_men.'</td>
                                <td>'.$munkAnyag_statusz[$anyag['stat_id']].'</td>
                            </tr>
                        ');
                    }

                    $szamla_html .= '</table>';
                }
            }
        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

/*	//Bejelentések
	}elseif(isset($_GET['bejelentesek'])){

        $szamla_html .= nl2('
            <div class="dt naplo kijCont" data-tabla="munkalap_kapcs" data-mellek="bejelentesek">
                <div class="dtr nFejlec">
                    <div class="dtc">Bejelentés szám</div>
                    <div class="dtc">Szálloda</div>
                    <div class="dtc">Hiba</div>
                    <div class="dtc">Bejelentve</div>
                    <div class="dtc">Státusz</div>
                </div>
        ');

        $munkalap_kapcsok = $adatbazis_kapcsolat->s('recid bejelentes_id', 'munkalap_kapcs', "`munkalap_id` = '{$_GET['id']}'");
        if( $munkalap_kapcsok ) {
            $i = 1;
            foreach($munkalap_kapcsok as $munkalap_kapcs) {

                $sor = $adatbazis_kapcsolat->s('recid ikt_szam telephely_id obj_id hiba_id insert_dt stat_id', 'bejelentes', "`recid` = '".$munkalap_kapcs['bejelentes_id']."'", '', '1');
                if( $sor ) {
                    $sor = $sor[0];

                    // Telephely
                    $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$sor['telephely_id'], '', '1');
                    $telephely = $telephely[0]['nev'];

                    // Helység
                    $helyseg = $adatbazis_kapcsolat->s('nev', 'obj', 'recid='.$sor['obj_id'], '', '1');
                    $helyseg = $helyseg[0]['nev'];

                    // Hiba
                    $hiba = $adatbazis_kapcsolat->s('info', 'erteklista', "`csoport`=4 AND `recid`='{$sor['hiba_id']}'");
                    $hiba = $hiba[0];

                    $szamla_html .= nl2('
                        <div id="naploSor-'.$sor['recid'].'" class="dtr nSor kijSor" data-id="'.$sor['recid'].'" data-i="'.$i.'">
                            <div class="dtc"><a href="bejelentes.php?id='.$sor['recid'].'&modosit" target="_blank">'.$sor['ikt_szam'].'</a></div>
                            <div class="dtc">'.$telephely.' - '.$helyseg.'</div>
                            <div class="dtc">'.$hiba['info'].'</div>
                            <div class="dtc">'.$sor['insert_dt'].'</div>
                            <div class="dtc">'.getStatText('bejelentes', $sor['stat_id']).'</div>
                        </div>
                    ');
                }

                $i++;
            }
        }

        $szamla_html .= '</div>';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }
*/
	//Történet
	}elseif(isset($_GET['tortenet'])){

        $szamla_html .= get_tortenet($megnevezes);

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	//Anyagigénylés
	}elseif(isset($_GET['anyagigenyles'])){

        $sorok = $adatbazis_kapcsolat->s('recid cikk_id szuk_men', 'munkalap_anyag', "`munkalap_id` = '".$_GET['id']."' AND `stat_id` IN (".DISZPNAG_STAT_ANYAGRA_VAR.", ".DISZPNAG_STAT_ANYAG_IGENYELVE.")", 'insert_dt desc', '');

        if( $sorok ) {
            $szamla_html .= nl2('
                <table class="csikos full">
                    <tr>
                        <th>Ikt szám</th>
                        <th>Cikkszám</th>
                        <th>Név</th>
                        <th>Igényelt men.</th>
                        <th>Rendelt men.</th>
                        <th>Státusz</th>
                    </tr>
            ');

            $i = 1;
            foreach($sorok as $sor) {
                $anyag_sor = $adatbazis_kapcsolat->s('ikt_szam cikk_id nev igmen rndmen cikkmee stat_id', 'anyagigenyles', "`cikk_id` = '".$sor['cikk_id']."'", '', '1');

                if( $anyag_sor ) {
                    $anyag_sor = $anyag_sor[0];

                    $szamla_html .= nl2('
                        <tr>
                            <td>'.$anyag_sor['ikt_szam'].'</td>
                            <td>'.$anyag_sor['cikk_id'].'</td>
                            <td>'.$anyag_sor['nev'].'</td>
                            <td>'.$anyag_sor['igmen'].' '.$anyag_sor['cikkmee'].'</td>
                            <td>'.$anyag_sor['rndmen'].' '.$anyag_sor['cikkmee'].'</td>
                            <td>'.getStatText('anyagigenyles', $anyag_sor['stat_id']).'</td>
                        </tr>
                    ');
                }
                $i++;
            }

            $szamla_html .= '</table>';
        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	//Elszámolás
	}elseif(isset($_GET['elszamolas'])){

        $szamla_html .= 'Elszámolás felülete';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }


	//Dokumentumok
	}elseif(isset($_GET['dokumentumok'])){

        if( $tobb_kep ) {
            require_once('tobb_kep.php');
        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	//Megjegyzések
	}elseif(isset($_GET['megjegyzesek'])){

        $szamla_html .= nl2('
            <div class="dt naplo kijCont" data-tabla="megjegyzes" data-mellek="megjegyzesek">
                <div class="dtr nFejlec">
                    <div class="dtc">Megjegyzés</div>
                    <div class="dtc">Hozzáadta</div>
                    <div class="dtc">Hozzáadva</div>
                </div>
        ');

        $sorok = $adatbazis_kapcsolat->s('recid megj insert_usr insert_dt', 'megjegyzes', "`id` = '".$_GET['id']."' AND `tabla` = '".str_replace('.php', '', basename(self()))."'", 'insert_dt desc', '');

        if( $sorok ) {
            $i = 1;
            foreach($sorok as $sor) {
                $szamla_html .= nl2('
                    <div id="naploSor-'.$sor['recid'].'" class="dtr nSor kijSor" data-id="'.$sor['recid'].'" data-i="'.$i.'">
                        <div class="dtc">'.nl2br($sor['megj']).'</div>
                        <div class="dtc">'.$sor['insert_usr'].'</div>
                        <div class="dtc">'.$sor['insert_dt'].'</div>
                    </div>
                ');

                $i++;
            }
        }

        $szamla_html .= '</div>';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	}

    if( !$_GET['ajax'] ) {
        echo '</div>';
    }

if( !$_GET['ajax'] ) {
    require_once('footer.php');
}
