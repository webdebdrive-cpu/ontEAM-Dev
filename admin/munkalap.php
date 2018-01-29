<?php
require_once('../weblap.php');

// Config
if( !_jog(JOG_MUNKALAP_MEGTEKINT) && !_jog(JOG_MUNKALAP_MEGTEKINT_SZALL) && !_jog(JOG_MUNKALAP_MEGTEKINT_FELADAT) ) {
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

// Változók beállítása
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
$formazatlan_szam       = array();          //Azok az oszlopneveket sorold fel, melyek numerikus értéket is tartalmazhatnak, de nem szeretnéd, ha lista nézetben ezres csoportra bontaná

$szamla_html = '';

// Több kép menedzse$léséhez
$tobb_kep = true; // Ha true, akkor js/jquery-ui.js, js/admin_sorrendez.js aktivál és minify!, admin/tobb_kep.php aktivál
if($tobb_kep){
    function get_tobbkep_darab()    { return 10;         } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '';         } // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;         } // MB-ban
    function get_tobbkep_oszlopnev(){                      // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

}


// Ha mobilcsoportra és státuszra egyszerre akarunk szűrni:
if (isset($_GET['felvett_mobilcsop'])) {
    $_SESSION['o_szuro_feltetel']   = 'me|stat_id#op|equal#fe|08#es|#me|mobil#op|equal#fe|'.$_GET['csop'].'#es|#';
    $_SESSION['o_szuro_html']       = '<div class="dtr"><div class="dtc"><b class="zaro nyit">(</b></div><div class="dtc"><input class="mezo" value="stat_id" type="hidden"><input class="op" value="equal" type="hidden"><input class="felt" value="08" type="hidden"><span class="m_mezo">Státusz</span> <span class="m_op">=</span> <span class="m_felt">Felvéve</span> </div><div class="dtc"><b class="zaro zar">)</b></div><div class="dtc"><select style="width: 7rem" class="es_vagy" onchange="osszetett_szuro_mondat()"><option value="es|">ÉS</option><option value="va|">VAGY</option></select></div><div class="dtc"><b class="torol">×</b></div></div><div class="dtr"><div class="dtc"><b class="zaro nyit">(</b></div><div class="dtc"><input class="mezo" value="mobil" type="hidden"><input class="op" value="equal" type="hidden"><input class="felt" value="'.$_GET['felvett_mobilcsop'].'" type="hidden"><span class="m_mezo">Mobilcsoport</span> <span class="m_op">=</span> <span class="m_felt">'. strtoupper($_GET['csop']).'</span> </div><div class="dtc"><b class="zaro zar">)</b></div><div class="dtc"><select style="width: 7rem" class="es_vagy" onchange="osszetett_szuro_mondat()"><option value="es|">ÉS</option><option value="va|">VAGY</option></select></div><div class="dtc"><b class="torol">×</b></div></div>';
}

// Munkalap készítése
if( isset($_POST['munkalapKeszit']) ) {
    $idk = json_decode($_POST['idk'], true);

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( is_array($idk) ) {
        // Ellenőrizzük a csoportokat
        $csoportok = $adatbazis_kapcsolat->s('csoport_id', 'bejelentes', "`recid` IN (".implode(',', $idk).")");
        if( $csoportok === false ) { $db_error = true; }

        $csoportok = adatbazis2tomb($csoportok, 'csoport_id');
        $csoportok_unique = array_unique($csoportok);

        if( count($csoportok_unique) > 1 ) {
            echo json_encode(array(
                'hiba'  => 'Csoportos és szabad bejelentésekből nem lehet egy munkalapot készíteni. <br>A szabad bejelentéseket előbb adja a csoporthoz!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        $kijelolt_db = array_count_values($csoportok);

        foreach( $csoportok_unique as $csu ) {
            if( $csu > 0 ) {
                $csoport_db = $adatbazis_kapcsolat->s('csoport_id', 'bejelentes', "`csoport_id` = '{$csu}'");
                if( $csoport_db === false ) { $db_error = true; }

                if( count($csoport_db) != $kijelolt_db[$csu] ) {
                    echo json_encode(array(
                        'hiba'  => 'Csoportosított munkalapból nem adható munkalaphoz. Előbb vegye ki a csoportból!',
                        'title' => 'Hiba',
                    ));

                    $weblap->leallit();
                    exit();
                }
            }
        }

        // Ellenőrizzük a szállodákat is
        $szallodak = $adatbazis_kapcsolat->s('telephely_id', 'bejelentes', "`recid` IN (".implode(',', $idk).")", '', '');
        if( $szallodak === false ) { $db_error = true; }

        $szallodak = adatbazis2tomb($szallodak, 'telephely_id');
        $szallodak_unique = array_unique($szallodak);

        if( count($szallodak_unique) > 1 ) {
            echo json_encode(array(
                'hiba'  => 'Csak azonos szállodával rendelkező bejegyzéseket lehet munkalappá alakítani!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Ellenőrizzük a státuszokat is
        foreach($idk as $id) {
            $statusz = $adatbazis_kapcsolat->s2('recid stat_id insert_dt', 'bejelentes_statusz', "`bejelentes_recid`='$id'", 'recid desc', '1');
            if( $statusz === false ) { $db_error = true; }

            if( $statusz ) {

                if( $statusz['stat_id'] != BEJELENTES_STAT_NYITOTT && $statusz['stat_id'] != BEJELENTES_STAT_FOLYAMATBAN ) {
                    echo json_encode(array(
                        'hiba'  => 'Csak Nyitott vagy Folyamatban státuszú bejelentésből készíthető munkalap!',
                        'title' => 'Hiba',
                    ));

                    $weblap->leallit();
                    exit();
                }
            } else {
                echo json_encode(array(
                    'hiba'  => 'Státusz nem található!',
                    'title' => 'Hiba',
                ));

                $weblap->leallit();
                exit();
            }
        }

        // Ellenőrizzük, hogy van e már belőlük munkalap
        foreach($idk as $id) {
            $isMlap = $adatbazis_kapcsolat->s2('recid munkalap_id', 'munkalap_kapcs', "`bejelentes_id` = '{$id}'", '', '1');
            if( $isMlap === false ) { $db_error = true; }

            if( $isMlap ) {

                echo json_encode(array(
                    'hiba' => 'Ehhez a bejelentéshez már készült munkalap. <br>Készít még egy munkalapot?',
                    'gomb' => '<input class="gomb zold" value="Igen" onclick="location.href = \'munkalap.php?uj&ujkent_bejelentes='.$id.'\';" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
                ));

                $weblap->leallit();
                exit();
            }
        }

        // Vegyünk egy bejelentést (úgy is scak egy lesz, újabban csak egy bejelentés több munkalap kapcsolat van)
        $bejelentes = $adatbazis_kapcsolat->s2('obj_id gep_id', 'bejelentes', "`recid`='$id'");

        // Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if(isset($_POST['csak_kerdezunk'])) {

            echo json_encode(array(
                'hiba' => 'Biztos munkalapot készít a kijelölt bejelentésből?',
                'gomb' => '<input class="gomb zold" value="Igen" onclick="munkalapKeszit();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        // Státuszt folyamatban-ra állítjuk
        foreach($idk as $id) {
            if( addStatusz( 'bejelentes_statusz', $id, BEJELENTES_STAT_FOLYAMATBAN ) === false ) {
                $db_error = true;
            }
        }

        if( !$db_error ) {
            // Megnézzük, hogy az adott évszámmal létezik e bejegyzés
            $ikt_seq = $adatbazis_kapcsolat->s2('ev sorszam', 'iktszam_seq', "`tabla` = 'munkalap' AND `ev` = '".date('y')."'");
            if( $ikt_seq === false ) { $db_error = true; }

            if( $ikt_seq ) {

                $sorszam = $ikt_seq['sorszam'];
                $sorszam++;
                $sql = $adatbazis_kapcsolat -> u('iktszam_seq', array('sorszam'), array($sorszam), "`tabla` = 'munkalap' AND `ev` = '".date('y')."'");
                if( $sql === false ) { $db_error = true; }

            } else {

                $sorszam = 1;
                $sql = $adatbazis_kapcsolat -> i('iktszam_seq', array('sorszam', 'ev', 'tabla'), array($sorszam, date('y'), 'munkalap'));
                if( $sql === false ) { $db_error = true; }
            }

            // Iktatószám formázása
            $ikt_szam = 'M'.date('y').'/'.sprintf('%07d', $sorszam);

            // Ellenőrizzük a szállodákat is
            $telephely_id = $adatbazis_kapcsolat->s('telephely_id', 'bejelentes', "`recid` IN (".implode(',', $idk).")", '', '1');
            if( $telephely_id === false ) { $db_error = true; }

            if( $telephely_id ) {
                $telephely_id = $telephely_id[0]['telephely_id'];
            }

            // Munkalap beszúrása
            $recid = $adatbazis_kapcsolat -> i('munkalap',
                                                array('ikt_szam',   'stat_id',              'tipus_id',  'telephely_id', 'obj_id',              'gep_id',               'loginnev',                         'update_usr',                          'insert_dt',    'insert_usr'                             ),
                                                array($ikt_szam,     MUNKALAP_STAT_NYITOTT, 1,           $telephely_id,  $bejelentes['obj_id'],  $bejelentes['gep_id'], $_SESSION[get_db_tabla().'user'],   $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',       $_SESSION[get_db_tabla().'teljes_nev']   )
                                            );
            if( $recid === false ) { $db_error = true; }

            // Hozzákapcsoljuk a bejelentéseket
            foreach($idk as $id) {
                $sql = $adatbazis_kapcsolat -> i('munkalap_kapcs',
                                                    array('munkalap_id', 'bejelentes_id',  'update_usr',                            'insert_dt',    'insert_usr',                           ),
                                                    array($recid,        $id,              $_SESSION[get_db_tabla().'teljes_nev'],  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  )
                                                );
                if( $sql === false ) { $db_error = true; }

            }

            // Felvesszük az első státuszt
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
                'recid'   => $recid
            ));
        } else {
            mysqli_rollback($db_kapcs);
            echo json_encode(array(
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
    }

    $weblap->leallit();
    exit();
}


// Munkalap átadás mobil csoportnak
if( isset($_POST['munkalapMobil']) ) {

    $idk = json_decode($_POST['idk'], true);

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    // Hozzárendelt csoport törlése
    parse_str($_POST['munkMobilKivetel']);

    // Hozzárendelt személyzet törlése
    parse_str($_POST['munkHozzMegszuntet']);

    // Törzsadat csatolása a munkalaphoz
    parse_str($_POST['addTorzsID']);
    parse_str($_POST['addTorzsMunkID']);

    if( is_array($idk) ) {
        $maradStatRecid = array();

        // Hozzárendelt csoport törlése
        if( count($munkMobilKivetel) ) {
            foreach($munkMobilKivetel as $value) {
                $maradStatRecid[] = $value;

                $munkalap = $adatbazis_kapcsolat->s2('recid stat_id', 'munkalap', "`recid` = '{$value}'", '', '1');
                if( $munkalap === false ) { $db_error = '1'; }

                if( $munkalap ) {

                    $sql = $adatbazis_kapcsolat -> u('munkalap',
                                                        array('szemelyzet_id', 'mobil_loginnev', 'visszaadva',  'update_usr'                          ),
                                                        array( 0,              '',               0,             $_SESSION[get_db_tabla().'teljes_nev']),
                                                        "`recid` = '{$value}'"
                                                    );
                    if( $sql === false ) { $db_error = '2'; }

                    if( $munkalap['stat_id'] != MUNKALAP_STAT_NYITOTT ) {
                        if( !addStatusz( 'munkalap_statusz', $value, MUNKALAP_STAT_NYITOTT ) ) {
                            $db_error = true;
                        }
                    }
                } else {
                    echo json_encode(array(
                        'hiba'  => tranzakcioHiba(),
                        'title' => 'Hiba',
                    ));

                    $weblap->leallit();
                    exit();
                }
            }
        }

        // Kivesszük a hozzárendelt mobil_loginnev-et
        if( count($munkHozzMegszuntet) ) {
            foreach($munkHozzMegszuntet as $value) {
                $munkalap = $adatbazis_kapcsolat->s2('recid stat_id', 'munkalap', "`recid` = '{$value}'", '', '1');
                if( $munkalap === false ) { $db_error = '3'; }

                if( $munkalap ) {
                    $sql = $adatbazis_kapcsolat -> u('munkalap',
                                                    array('mobil_loginnev', 'visszaadva',   'update_dt',          'update_usr'                          ),
                                                    array('',               0,              date('Y-m-d H:i:s'), $_SESSION[get_db_tabla().'teljes_nev'] ),
                                                    "`recid` = '{$value}'"
                    );
                    if( $sql === false ) { $db_error = '4'; }
                } else {
                    echo json_encode(array(
                        'hiba'  => tranzakcioHiba(),
                        'title' => 'Hiba',
                    ));

                    $weblap->leallit();
                    exit();
                }
            }
        }

        // Törzsadat hozzárendelése munkalapokhoz
        if( count($addTorzsMunkID) ) {
            foreach($addTorzsMunkID as $key => $value) {
                $munkalap = $adatbazis_kapcsolat->s2('recid stat_id', 'munkalap', "`recid` = '{$value}'", '', '1');
                if( $munkalap === false ) { $db_error = '5'; }

                if( $munkalap ) {

                    $torzslap = $adatbazis_kapcsolat->s2('recid felh_loginnev', 'torzsadat', "`recid` = '".$addTorzsID[$key]."'", '', '1');
                    if( $torzslap === false ) { $db_error = '6'; }

                    if( $torzslap ) {

                        $sql = $adatbazis_kapcsolat -> u('munkalap',
                                                            array('szemelyzet_id', 'visszaadva', 'mobil_loginnev',           'update_usr'                          ),
                                                            array(MOBILCSOPORT,    0,            $torzslap['felh_loginnev'], $_SESSION[get_db_tabla().'teljes_nev']),
                                                            "`recid` = '{$value}'"
                                                        );
                        if( $sql === false ) { $db_error = '7'; }

                    } else {
                        echo json_encode(array(
                            'hiba'  => tranzakcioHiba(),
                            'title' => 'Hiba',
                        ));

                        $weblap->leallit();
                        exit();
                    }
                } else {
                    echo json_encode(array(
                        'hiba'  => tranzakcioHiba(),
                        'title' => 'Hiba',
                    ));

                    $weblap->leallit();
                    exit();
                }
            }
        }

        foreach($idk as $id) {
            if( !in_array($id, $maradStatRecid) ) {
                $munkalap = $adatbazis_kapcsolat->s2('recid stat_id', 'munkalap', "`recid` = '{$id}'", '', '1');
                if( $munkalap === false ) { $db_error = true; }

                if( $munkalap ) {

                    // Hozzárendeljük a mobil személyzetet
                    $sql = $adatbazis_kapcsolat -> u('munkalap', array('szemelyzet_id', 'visszaadva'), array(MOBILCSOPORT, 0), "`recid` = '{$id}'");
                    if( $sql === false ) { $db_error = '8'; }

                    // Beosztás nélkül státuszra állítjuk
                    if( !addStatusz( 'munkalap_statusz', $id, MUNKALAP_STAT_BEOSZTAS_NELKUL ) ) {
                        $db_error = true;
                    }
                }
            }
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
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba'.$db_error,
            ));
        }
    }

    $weblap->leallit();
    exit();
}


// Munkalap átadás mobilnak sima: azaz csak státuszt léptetünk
if( isset($_POST['munkalapMobil_sima']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $recid = $_POST['recid'];
    
    if($recid){
        
        $munkalap = $adatbazis_kapcsolat->s2('stat_id telephely_id', 'munkalap', "`recid` = '{$recid}'");
        if( $munkalap === false ) { $db_error = true; }

        // Státusz ellenőrzése
        if( $munkalap['stat_id'] != MUNKALAP_STAT_NYITOTT ) {
            echo json_encode(array(
                'hiba'  => 'Csak nyitott státuszú munkalapokat lehet mobilhoz rendelni!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }
        
        // Szálloda modell ellenőrzése
        $telephely = $adatbazis_kapcsolat->s2('modell', 'telephely', "`id`='{$munkalap['telephely_id']}'");
        if( !$telephely['modell'] ) {
            echo json_encode(array(
                'hiba'  => 'Csak fővárosi munkalapot lehet mobilhoz rendelni!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }
        
        // Jog ellenőrzése
        if( !_jog(JOG_MUNKALAP_NYITOTT_KEZEL) ) {
            echo json_encode(array(
                'hiba'  => 'Nincs joga az átadáshoz!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Státusz léptetés
        if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_BEOSZTAS_NELKUL ) ) {
            $db_error = true;
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
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba'.$db_error,
            ));
        }
    }
    
    $weblap->leallit();
    exit();
}


// Munkalap átadása külső csoportnak
if( isset($_POST['munkalapKulso']) ) {

    $recid          = $_POST['recid'];
    $szallito_id    = $_POST['szallito_id'];
    $szemelyzet_id  = $_POST['szemelyzet_id'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid && ($szallito_id || $szemelyzet_id) ) {

        $munkalap = $adatbazis_kapcsolat->s2('stat_id', 'munkalap', "`recid` = '{$recid}'");
        if( $munkalap === false ) { $db_error = true; }

        // Státusz ellenőrzése
        if( $munkalap['stat_id'] != MUNKALAP_STAT_NYITOTT ) {
            echo json_encode(array(
                'hiba'  => 'Csak nyitott státuszú munkalapokat lehet kiadni külsősnek!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzése
        if( !_jog(JOG_MUNKALAP_NYITOTT_KEZEL) ) {
            echo json_encode(array(
                'hiba'  => 'Nincs joga az átadáshoz!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Ha külső kivitelezőnek adjuk át, akkor Folyamatban státuszt kap
        if( $szallito_id ) {

            $sql = $adatbazis_kapcsolat -> u('munkalap',
                                                array('szemelyzet_id',   'szallito_id', 'visszaadva',   'update_usr'                           ),
                                                array( KULSO_KIVITELEZO, $szallito_id,  0,              $_SESSION[get_db_tabla().'teljes_nev'] ),
                                                "`recid` = '{$recid}'"
                                            );
            if( $sql === false ) { $db_error = true; }

            if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_FOLYAMATBAN ) ) {
                $db_error = true;
            }

        // Ha személyezttípust választottunk, akkor előkészítés alatt státuszba léptetjük
        } else if( $szemelyzet_id ) {

            $sql = $adatbazis_kapcsolat -> u('munkalap',
                                                array('szemelyzet_id',   'update_usr'                           ),
                                                array($szemelyzet_id,    $_SESSION[get_db_tabla().'teljes_nev'] ),
                                                "`recid` = '{$recid}'"
                                            );
            if( $sql === false ) { $db_error = true; }

            if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_ELOKESZITES_ALATT ) ) {
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
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
    }else{
        echo json_encode(array(
            'hiba'  => 'Kérem válasszon egy külső kivitelezőt vagy hozzárendelt személyt!',
            'title' => 'Hiba',
        ));
    }

    $weblap->leallit();
    exit();
}


// Munkalap Felvétele
if( isset($_POST['munkalapFelvesz']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $munkalap = $sor = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id', 'munkalap', "`recid`='{$recid}'");
    if( $munkalap === false ) { $db_error = true; }

    // Státusz ellenőrzése
    if($munkalap['stat_id']!=MUNKALAP_STAT_FOLYAMATBAN){
        echo json_encode(array(
            'hiba'  => 'Csak folyamatban levő munkalap vehető fel!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    // Kezelés jog ellenőrzése
    $felvehetik = array(TELEPITETT_SZEMELYZET, MOBILCSOPORT);
    if( !( get_kezeles_jog($munkalap['szemelyzet_id']) && in_array($sor['szemelyzet_id'], $felvehetik) ) ) {
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga munkalapot felvenni!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
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
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
    }

    $weblap->leallit();
    exit();
}


// Munkalap készre jelentése
if( isset($_POST['munkalapKeszre']) ) {

    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {

        $munkalap = $sor = $adatbazis_kapcsolat->s2('stat_id uf_szam szemelyzet_id telephely_id', 'munkalap', "`recid` = '{$recid}'", '', '1');
        if( $munkalap === false ) { $db_error = true; }
        
        $telephely = $adatbazis_kapcsolat->s2('modell', 'telephely', "`id`='{$munkalap['telephely_id']}'");
        if( $telephely === false ) { $db_error = true; }

        $akt_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id` = '{$recid}'", 'recid desc', '1');
        if( $akt_tetel === false ) { $db_error = true; }

        // Státusz ellenőrzés
        if( $munkalap['stat_id'] != MUNKALAP_STAT_FOLYAMATBAN && $munkalap['stat_id'] != MUNKALAP_STAT_FELVEVE ) {
            echo json_encode(array(
                'hiba'  => 'Csak felvéve vagy folyamatban státuszú munkalapot lehet készre jelenteni!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzése
        $felvehetik  = array(TELEPITETT_SZEMELYZET, MOBILCSOPORT);
        $kezeles_jog = get_kezeles_jog($sor['szemelyzet_id']);
        if(
            !(
                ( $sor['stat_id']==MUNKALAP_STAT_FOLYAMATBAN && $sor['szemelyzet_id']==KULSO_KIVITELEZO && ( _jog(JOG_MUNKALAP_KEZEL_KULSO) || ( _jog(JOG_MUNKALAP_SAJAT_KEZEL) && $_SESSION[get_db_tabla().'szemelyzet_id']==KULSO_KIVITELEZO ) ) ) ||
                ( $sor['stat_id']==MUNKALAP_STAT_FELVEVE && in_array($sor['szemelyzet_id'], $felvehetik) && $kezeles_jog )
            )
          ) {
            echo json_encode(array(
                'hiba'  => 'Önnek nincs joga munkalapot készre jelenteni!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }

        // Nyitott státusznál az is feltétel, hogy a tétel is nyitott-e, azaz nincs átadta dátum kitöltve. Egyébként csak a feltétel, hogy létezzen utolsó tétel.
        $sql_kieg = '';
        if( $sor['stat_id']==MUNKALAP_STAT_FOLYAMATBAN ){
            $sql_kieg = " AND `atadta_dt`='0000-00-00 00:00:00'";
        }
        // Van-e utolsó nyitott-e tétel?
        $utolso_tetel = $adatbazis_kapcsolat->s2('recid atadta_dt', 'munkalap_tetel', "`munkalap_id`='{$recid}'".$sql_kieg, 'recid desc', '1');
        if( $utolso_tetel === false ) { $db_error = true; }
        
        // Ha nincs utolsó (nyitott) tétel, akkor hibaüzenet:
        if(!$utolso_tetel['atadta_dt']){
            echo json_encode(array(
                'hiba'  => 'Készre jelentéshez'.HIBA_ADJON_FELADAT,
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }
        $feladat = $adatbazis_kapcsolat->s2('recid', 'munkalap_feladat', "`tetel_id`='{$utolso_tetel['recid']}'", '', '1');
        if( !$feladat ) {
            echo json_encode(array(
                'hiba'  => 'Készre jelentéshez'.HIBA_ADJON_FELADAT,
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }

        // Előbb csak kérdezünk
        if (isset($_POST['csak_kerdezunk'])) {

            echo json_encode(array(
                'hiba' => 'Biztosan készre jelenti a munkalapot?',
                'gomb' => '<input class="gomb zold" value="Igen" onclick="munkalapKeszre();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }
        
        // Ha ütemezett feladatból jött létre (azaz van érték az uf_szam-ban), akkor az "Utoljára végrehajtva:" dátumát frissítjük
        if($munkalap['uf_szam']){
            $sql = $adatbazis_kapcsolat->u('utemezett_feladatok', array('esem_d'), array('NOW()'), "`id`='{$munkalap['uf_szam']}'");
            if( $sql === false ) { $db_error = true; }
        }

        // Kész vagy igazolásra vár státuszra léptetjük attól függően, hogy milyen személyzettípusról van szó
        $ig_var_statba_leptet = array(MOBILCSOPORT, KULSO_KIVITELEZO);
        if( in_array($munkalap['szemelyzet_id'], $ig_var_statba_leptet) ){
            // Készre állítjuk a munkalapot
            if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_IGAZOLASRA_VAR ) ) {
                $db_error = true;
            }
            
        }else{
            // Készre állítjuk a munkalapot
            if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_LEZARASRA_VAR ) ) {
                $db_error = true;
            }
        }

        // Munkalaphoz kapcsolódó bejelentés
        $mail_megy = array();
        $bejelentesek = $adatbazis_kapcsolat->s('bejelentes_id', 'munkalap_kapcs', "`munkalap_id` = '{$recid}'");
        if( $bejelentesek === false ) { $db_error = true; }

        if( $bejelentesek ) {

            // Megnézzük, hogy van-e olyan munkalap, ahol ez a bejelentés szerepel
            $mashol_bej = $adatbazis_kapcsolat->s('munkalap_id bejelentes_id', 'munkalap_kapcs', "`bejelentes_id` = '".$bejelentesek[0]['bejelentes_id']."' AND `munkalap_id` != '{$recid}'");
            if( $mashol_bej === false ) { $db_error = true; }

            // Ha szerepel
            if( $mashol_bej ) {
                $osszes_munkalap = count($mashol_bej);
                $elkeszult_munkalapok = 0;

                // Megnézzük az összes munkalap státuszát
                foreach($mashol_bej as $mbej) {

                    $munkalap = $adatbazis_kapcsolat->s2('stat_id', 'munkalap', "`recid` = '".$mbej['munkalap_id']."'");
                    if( $munkalap ) {
                        $minimumkesz_statuszok = array(MUNKALAP_STAT_LEZARASRA_VAR, MUNKALAP_STAT_IGAZOLASRA_VAR, MUNKALAP_STAT_VIZSGALAT_ALATT, MUNKALAP_STAT_LEZART);
                        if( in_array($munkalap['stat_id'], $minimumkesz_statuszok) ) {
                            $elkeszult_munkalapok++;
                        }
                    }
                }

                // Ha mind elkészült, tehát ez az utolsó igazolás alatt, akkor a bejelentések státuszát átváltjuk
                if( $osszes_munkalap == $elkeszult_munkalapok ) {
                    // Lekérdezzük a bejelentéseit
                    foreach($bejelentesek as $bejelentes) {
                        // Átállítjuk a bejelentések státuszát Igazolás alatt
                        array_push($mail_megy, $bejelentes['bejelentes_id']);
                        if( !addStatusz( 'bejelentes_statusz', $bejelentes['bejelentes_id'], BEJELENTES_STAT_KESZ_IG ) ) {
                            $db_error = true;
                        }
                    }
                }

            // Ha nem szerepel akkor
            } else {

                // Lekérdezzük a bejelentéseit
                foreach($bejelentesek as $bejelentes) {
                    // Átállítjuk a bejelentések státuszát Igazolás alatt
                    array_push($mail_megy, $bejelentes['bejelentes_id']);
                    if( !addStatusz( 'bejelentes_statusz', $bejelentes['bejelentes_id'], BEJELENTES_STAT_KESZ_IG ) ) {
                        $db_error = true;
                    }
                }
            }
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);
            
            // Levélküldés
            foreach($mail_megy as $bejelentes_id){
                $valasz = level_felkeszit_es_kuld('bejelentes', $bejelentes_id, _jog(JOG_BEJELENTES_SAJAT_RESZL));
                if( substr($valasz, 0, 4)=='Hiba' ){
                    echo json_encode(array(
                        'hiba'  => $valasz,
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
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
    }

    $weblap->leallit();
    exit();
}


// Munkalap rögzítése, lezárása
if( isset($_POST['munkalapRogzit']) ) {

    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {

        $munkalap = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id telephely_id', 'munkalap', "`recid` = '{$recid}'");
        if( $munkalap === false ) { $db_error = true; }
        
        $telephely = $adatbazis_kapcsolat->s2('modell', 'telephely', "`id`='{$munkalap['telephely_id']}'");
        if( $telephely === false ) { $db_error = true; }

        // Státusz ellenőrzés
        if( $munkalap['stat_id'] != MUNKALAP_STAT_LEZARASRA_VAR ) {
            echo json_encode(array(
                'hiba'  => 'Csak '.getStatText('munkalap', MUNKALAP_STAT_LEZARASRA_VAR).' státuszú munkalapot lehet lezárni!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzése
        if( !get_kezeles_jog($munkalap['szemelyzet_id']) ) {
            echo json_encode(array(
                'hiba'  => 'Önnek nincs joga ezt a munkalapot lezárni!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Van-e feladat
        $utolso_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id`='{$recid}'", 'recid desc', '1');
        $feladat      = $adatbazis_kapcsolat->s2('recid', 'munkalap_feladat', "`tetel_id`='{$utolso_tetel['recid']}'", '', '1');
        if( !$feladat ) {
            echo json_encode(array(
                'hiba'  => 'Munkalap lezárásához'.HIBA_ADJON_FELADAT,
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }

        // Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if (isset($_POST['csak_kerdezunk'])) {

            echo json_encode(array(
                'hiba' => 'Biztosan lezárja a munkalapot?',
                'gomb' => '<input class="gomb zold" value="Igen" onclick="munkalapRogzit();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        // Lezártra állítjuk a munkalapot
        if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_LEZART) ) {
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
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
    }

    $weblap->leallit();
    exit();
}


// Munkalap igazolása
if( isset($_POST['munkalapIgazol']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {

        // Státusz ellenőrzése
        $munkalap = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id telephely_id', 'munkalap', "`recid` = '{$recid}'", '', '1');
        if( $munkalap === false ) { $db_error = true; }
        
        $telephely = $adatbazis_kapcsolat->s2('modell', 'telephely', "`id`='{$munkalap['telephely_id']}'");
        if( $telephely === false ) { $db_error = true; }
        
        if( $munkalap['stat_id'] != MUNKALAP_STAT_IGAZOLASRA_VAR ) {
            echo json_encode(array(
                'hiba'  => 'Csak igazolásra alatti munkalapot lehet készre jelenteni!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzése
        if( !_jog(JOG_MUNKALAP_IGAZOLAS) ) {
            echo json_encode(array(
                'hiba'  => 'Önnek nincs joga munkalapot igazolni!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if (isset($_POST['csak_kerdezunk'])) {

            echo json_encode(array(
                'hiba' => 'Biztosan igazolja a munkalapot?',
                'gomb' => '<input class="gomb zold" value="Igen" onclick="munkalapIgazol();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        // Lezárásra vár státuszra léptetjük
        if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_LEZARASRA_VAR) ) {
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
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
    }

    $weblap->leallit();
    exit();
}


// Munkalap külsős jóváhagy
if( isset($_POST['munkalapKulsosJovahagy']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {

        // Adatok lekérdezése
        $munkalap = $adatbazis_kapcsolat->s2('stat_id, szallito_id, jh1_login, jh2_login', 'munkalap', "`recid` = '{$recid}'", '', '1');
        if( $munkalap === false ) { $db_error = true; }

        // Státusz ellenőrzése
        if( $munkalap['stat_id'] != MUNKALAP_STAT_DONTESRE_VAR ) {
            echo json_encode(array(
                'hiba'  => 'Csak döntés alatti munkalapot lehet jóváhagyni!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzése
        if( !( _jog(JOG_MUNKALAP_KULSOS_DONTES) && $munkalap['jh1_login']!=$_SESSION[get_db_tabla().'user'] && $munkalap['jh2_login']!=$_SESSION[get_db_tabla().'user'] ) ) {
            echo json_encode(array(
                'hiba'  => 'Önnek nincs joga a jóváhagyáshoz!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }

        // Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if (isset($_POST['csak_kerdezunk'])) {

            echo json_encode(array(
                'hiba' => 'Biztosan jóváhagyja?',
                'gomb' => '<input class="gomb zold" value="Igen" onclick="munkalapKulsosJovahagy();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        // Ha még nincs meg az első jóváhagyó, akkor csak annyit teszünk, hogy rögzítjük az elsőt.
        if( !$munkalap['jh1_login'] ){
            
            // JH1 kitöltése
            $sql = $adatbazis_kapcsolat->u('munkalap', 
                                                    array('jh1_login',                      'jh1_datum', 'jh1_szerep'                               ),
                                                    array($_SESSION[get_db_tabla().'user'], 'NOW()',     $_SESSION[get_db_tabla().'szemelyzet_id']  ),
                                                    "`recid`='{$recid}'");
            if( $sql === false ) { $db_error = true; }
                                                    
            // Megjegyzésbe írás
            if( addMegj('munkalap', $recid, getErtek('nev', 'szallito', 'id', $munkalap['szallito_id']).' jóváhagyva') === false ) {
                $db_error = true;
            }
            
        // Ha már a 2. jóváhagyásról van szó, akkor státuszt is léptetünk
        }else{
            
            // JH2 kitöltése
            $sql = $adatbazis_kapcsolat->u('munkalap', 
                                                    array('jh2_login',                      'jh2_datum', 'jh2_szerep'                               ),
                                                    array($_SESSION[get_db_tabla().'user'], 'NOW()',     $_SESSION[get_db_tabla().'szemelyzet_id']  ),
                                                    "`recid`='{$recid}'");
            if( $sql === false ) { $db_error = true; }
                                                    
            // Megjegyzésbe írás
            if( addMegj('munkalap', $recid, getErtek('nev', 'szallito', 'id', $munkalap['szallito_id']).' jóváhagyva') === false ) {
                $db_error = true;
            }
            
            // Státusz léptetés
            if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_HOZREND_MEGREND_VAR ) ) {
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
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
    }

    $weblap->leallit();
    exit();
}


// Munkalap külsős elutasítás
if( isset($_POST['munkalapKulsosElutasit']) ) {
    $recid = $_POST['recid'];
    $memo  = $_POST['memo'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {

        // Státusz ellenőrzése
        $munkalap = $adatbazis_kapcsolat->s2('stat_id szallito_id', 'munkalap', "`recid` = '{$recid}'", '', '1');
        if( $munkalap === false ) { $db_error = true; }
        if( $munkalap['stat_id'] != MUNKALAP_STAT_DONTESRE_VAR ) {
            echo json_encode(array(
                'hiba'  => 'Csak döntés alatti munkalapot lehet elutasítani!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzése
        if( !_jog(JOG_MUNKALAP_KULSOS_DONTES) ) {
            echo json_encode(array(
                'hiba'  => 'Önnek nincs joga az elutasításhoz!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Indoklás ellenőrzése
        if( !$memo ) {
            echo json_encode(array(
                'hiba'  => 'Kérjük adja meg az indoklást!',
                'gomb'  => '<input type="button" class="link gomb" value="Ok" onclick="alert2(); $(\'#elutasitas_memo\').focus();">',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Visszaadva pipa bepipálása, személyzetek ésjóváhagyók kiszedése
        $sql = $adatbazis_kapcsolat->u('munkalap',  array('visszaadva', 'szallito_id',  'szemelyzet_id',  'jh1_login', 'jh1_datum',           'jh1_szerep' ),
                                                    array(1,            '',             '',               '',          '0000-00-00 00:00:00', 0            ),
                                                    "`recid`='{$recid}'");
        if( $sql === false ) { $db_error = true; }

        // Státusz léptetés
        if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_NYITOTT, '', $memo ) ) {
            $db_error = true;
        }

        // Megjegyzés hozzáadása
        if(addMegj( 'munkalap', $recid, getErtek('nev', 'szallito', 'id', $munkalap['szallito_id']).' elutasítva'."\r\n".$memo )===false){
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
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
    }

    $weblap->leallit();
    exit();
}


// Munkalap igazolás elutasítása
if( isset($_POST['munkalapIgazolElutasit']) ) {
    $recid = $_POST['recid'];
    $memo  = $_POST['memo'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {

        // Státusz ellenőrzése
        $munkalap = $adatbazis_kapcsolat->s2('stat_id', 'munkalap', "`recid` = '{$recid}'", '', '1');
        if( $munkalap === false ) { $db_error = true; }
        if( $munkalap['stat_id'] != MUNKALAP_STAT_IGAZOLASRA_VAR ) {
            echo json_encode(array(
                'hiba'  => 'Csak igazolás alatti munkalapot lehet elutasítani!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzése
        if( !_jog(JOG_MUNKALAP_IGAZOLAS) ) {
            echo json_encode(array(
                'hiba'  => 'Önnek nincs joga munkalap igazolást elutasítani!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }

        // Indoklás ellenőrzése
        if( !$memo ) {
            echo json_encode(array(
                'hiba'  => 'Kérjük adja meg az indoklást!',
                'gomb'  => '<input type="button" class="link gomb" value="Ok" onclick="alert2(); $(\'#elutasitas_memo\').focus();">',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }

        // Státusz léptetés
        if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_VIZSGALAT_ALATT, '', $memo ) ) {
            $db_error = true;
        }

        // Megjegyzés hozzáadása
        if(addMegj( 'munkalap', $recid, $memo )===false){
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
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
    }

    $weblap->leallit();
    exit();
}


/*// Munkalap reklamáció elfogadása
if( isset($_POST['munkalapReklamacioElfogad']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {

        // Státusz ellenőrzés
        $munkalap = $adatbazis_kapcsolat->s2('stat_id', 'munkalap', "`recid` = '{$recid}'", '', '1');
        if( $munkalap === false ) { $db_error = true; }
        if( $munkalap['stat_id'] != MUNKALAP_STAT_VIZSGALAT_ALATT ) {
            echo json_encode(array(
                'hiba'  => 'Csak igazolás alatti munkalapot lehet elfogadni!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzés
        if( !_jog(JOG_MUNKALAP_VIZSGALAT) ) {
            echo json_encode(array(
                'hiba'  => 'Önnek nincs joga reklamációt elfogadni!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if (isset($_POST['csak_kerdezunk'])) {

            echo json_encode(array(
                'hiba' => 'Biztosan elfogadja a reklamációt?',
                'gomb' => '<input class="gomb zold" value="Igen" onclick="munkalapReklamacioElfogad();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        // Státusz léptetés
        if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_NYITOTT ) ) {
            $db_error = true;
        }

        // Visszaadva pipa bepipálása, hozzárendelt személyzet és külső kivitelező nullázása
        $sql = $adatbazis_kapcsolat->u('munkalap',  array('visszaadva', 'szemelyzet_id', 'szallito_id'  ),
                                                    array(1,            '',              ''             ),
                                                    "`recid`='$recid'");
        if( $sql === false ) { $db_error = true; }

        // Bejelentés folyamatban státuszt kap
        $bejelentes = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='$recid'");
        if( $sql === false ) { $db_error = true; }
        if($bejelentes['bejelentes_id']){
            if( !addStatusz( 'bejelentes_statusz', $bejelentes['bejelentes_id'], BEJELENTES_STAT_FOLYAMATBAN ) ) {
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
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
    }

    $weblap->leallit();
    exit();
}*/


// Munkalap reklamáció elutasítása
if( isset($_POST['munkalapReklamacioElutasit']) ) {
    $recid = $_POST['recid'];
    $memo  = $_POST['memo'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {

        // Státusz ellenőrzés
        $munkalap = $adatbazis_kapcsolat->s2('stat_id', 'munkalap', "`recid` = '{$recid}'");
        if( $munkalap === false ) { $db_error = true; }
        
        // Van-e feladat
        $utolso_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id`='{$recid}'", 'recid desc', '1');
        $feladat      = $adatbazis_kapcsolat->s2('recid', 'munkalap_feladat', "`tetel_id`='{$utolso_tetel['recid']}'", '', '1');
        if( !$feladat ) {
            echo json_encode(array(
                'hiba'  => 'A munkalap utolsó tételéhez nincs feladat rendelve, így nem zárható!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzés
        if( !_jog(JOG_MUNKALAP_VIZSGALAT) ) {
            echo json_encode(array(
                'hiba'  => 'Önnek nincs joga munkalapot lezárni!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Indoklás ellenőrzése
        if( !$memo ) {
            echo json_encode(array(
                'hiba'  => 'Kérjük adja meg az indoklást!',
                'gomb'  => '<input type="button" class="link gomb" value="Ok" onclick="alert2();">',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Státusz léptetés
        if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_LEZART, '', $memo ) ) {
            $db_error = true;
        }

        // Megjegyzés hozzáadása
        if(addMegj( 'munkalap', $recid, $memo )===false){
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
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
    }

    $weblap->leallit();
    exit();
}


// Külsős munka megrendelve
if( isset($_POST['munkalapKulsosMegrendelve']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {

        $munkalap = $adatbazis_kapcsolat->s2('stat_id', 'munkalap', "`recid` = '{$recid}'", '', '1');
        if( $munkalap === false ) { $db_error = true; }

        // Státusz ellenőrzés
        if( $munkalap['stat_id'] != MUNKALAP_STAT_HOZREND_MEGREND_VAR ) {
            echo json_encode(array(
                'hiba'  => 'Csak "hozzárendelés megrendelés alatt" státuszú munkalaphoz rendelhető külsős munka!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzés
        if( !_jog(JOG_MUNKALAP_KULSOS_MEGREND) ) {
            echo json_encode(array(
                'hiba'  => 'Önnek nincs joga ehhez a beállításhoz!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }

        // Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if (isset($_POST['csak_kerdezunk'])) {

            echo json_encode(array(
                'hiba' => 'Biztosan meg van rendelve a külsős munka?',
                'gomb' => '<input class="gomb zold" value="Igen" onclick="munkalapKulsosMegrendelve();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        // Hozzárendelt személyzet: külső kivitelező
        $sql = $adatbazis_kapcsolat->u('munkalap',
                                                    array('szemelyzet_id'),
                                                    array(KULSO_KIVITELEZO),
                                                "`recid`='$recid'");
        if( $sql === false ) { $db_error = true; }

        // Státusz léptetés
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
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
    }

    $weblap->leallit();
    exit();
}

// Munkalap megrendelése
if( isset($_POST['munkalapMegrendelve']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        // Ellenőrizzük, hogy hozzárendelés előkészítés alatt van e
        $munkalap = $adatbazis_kapcsolat->ss('stat_id szallito_id', 'munkalap', "`recid` = '{$recid}'", '', '1');
        if( $munkalap === false ) { $db_error = true; }

        if( $munkalap ) {

            if( $munkalap['stat_id'] != MUNKALAP_STAT_HOZREND_MEGREND_VAR ) {
                echo json_encode(array(
                    'hiba'  => 'Csak Megrendelés alatt státuszban lehet a megrendelést véglegesíteni!',
                    'title' => 'Hiba',
                ));

                $weblap->leallit();
                exit();
            }

            if( !$munkalap['szallito_id'] ) {
                echo json_encode(array(
                    'hiba'  => 'Előbb rendeljen hozzá külső kivitelezőt!',
                    'title' => 'Hiba',
                ));

                $weblap->leallit();
                exit();
            }

            // Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
            if( $_POST['csak_kerdezunk'] ) {
                echo json_encode(array(
                    'hiba' => 'Biztos, hogy megrendelte '.getErtek('nev', 'szallito', 'id', $munkalap['szallito_id']).' partnertől a munka elvégzését?',
                    'gomb' => '<input class="gomb zold" value="Igen" onclick="munkalapMegrendelve( '.$recid.' );" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
                ));

                $weblap->leallit();
                exit();
            }

            // Átállítjuk a státuszt
            if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_FOLYAMATBAN ) ) {
                $db_error = true;
            }

            // Átállítjuk a személyzet típust
            $sql = $adatbazis_kapcsolat -> u('munkalap', array('szemelyzet_id'), array(KULSO_KIVITELEZO), "`recid` = '{$recid}'");
            if( $sql === false ) { $db_error = true; }

            if( !$db_error ) {
                mysqli_commit($db_kapcs);
                echo json_encode(array(
                    'statusz' => 'siker'
                ));
            } else {
                mysqli_rollback($db_kapcs);
                echo json_encode(array(
                    'hiba'  => tranzakcioHiba(),
                    'title' => 'Hiba',
                ));
            }
        }
    }

    $weblap->leallit();
    exit();
}

// Munkalap küldés jóváhagyásra
if( isset($_POST['kuldesJovahagyasra']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        // Ellenőrizzük, hogy hozzárendelés előkészítés alatt van e
        $sql = $munkalap = $adatbazis_kapcsolat->s('stat_id szallito_id', 'munkalap', "`recid` = '{$recid}'", '', '1');
        if( $sql === false ) { $db_error = true; }

        if( $munkalap ) {
            $munkalap = $munkalap[0];

            if( $munkalap['stat_id'] != MUNKALAP_STAT_ELOKESZITES_ALATT ) {
                echo json_encode(array(
                    'hiba'  => 'Csak Hozzárendelés előkészítés alatt státuszban lehet külső kivitelezőt megadni!',
                    'title' => 'Hiba',
                ));

                $weblap->leallit();
                exit();
            }

            if( !$munkalap['szallito_id'] ) {
                echo json_encode(array(
                    'hiba'  => 'Előbb rendeljen hozzá külső kivitelezőt!',
                    'title' => 'Hiba',
                ));

                $weblap->leallit();
                exit();
            }

            // Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
            if( $_POST['csak_kerdezunk'] ) {
                echo json_encode(array(
                    'hiba' => 'Biztos elküldi jóváhagyásra a munkalapot?',
                    'gomb' => '<input class="gomb zold" value="Igen" onclick="kuldesJovahagyasra( '.$recid.' );" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
                ));

                $weblap->leallit();
                exit();
            }

            // Átállítjuk a státuszt
            if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_DONTESRE_VAR ) ) {
                $db_error = true;
            }

            // Átállítjuk a személyzet típust
            $sql = $adatbazis_kapcsolat -> u('munkalap', array('szemelyzet_id'), array(MSZK_VEZETO), "`recid` = '{$recid}'");
            if( $sql === false ) { $db_error = true; }

            if( !$db_error ) {
                mysqli_commit($db_kapcs);
                echo json_encode(array(
                    'statusz' => 'siker'
                ));
            } else {
                mysqli_rollback($db_kapcs);
                echo json_encode(array(
                    'hiba'  => tranzakcioHiba(),
                    'title' => 'Hiba',
                ));
            }
        }
    }

    $weblap->leallit();
    exit();
}

// Munkalap Javaslattétel
if( isset($_POST['kulsokivHozzarend']) ) {
    $recid      = $_POST['recid'];
    $szallitoid = $_POST['szallitoid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {

        $munkalap = $sor = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id telephely_id', 'munkalap', "`recid` = '{$recid}'", '', '1');
        if( $munkalap === false ) { $db_error = true; }
        
        $telephely = $adatbazis_kapcsolat->s2('modell', 'telephely', "`id`='{$munkalap['telephely_id']}'");
        if( $telephely === false ) { $db_error = true; }

        // Státusz ellenőrzése
        if( $munkalap['stat_id'] != MUNKALAP_STAT_ELOKESZITES_ALATT ) {
            echo json_encode(array(
                'hiba'  => 'Csak Előkészítés alatt státuszban lehet szállítót választani!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzése (javaslattétel esetén külsős előkészítés jog
        if( 
            !(
                $sor['stat_id']==MUNKALAP_STAT_ELOKESZITES_ALATT && _jog(JOG_MUNKALAP_KULSOS_ELOKESZIT) && $_SESSION[get_db_tabla().'szemelyzet_id']==$sor['szemelyzet_id']
            )
        ) {
            echo json_encode(array(
                'hiba'  => 'Önnek nincs joga külső kivitelezőt hozzárendelni!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzése (javaslattétel esetén külsős előkészítés jog
        if( !isset($_POST['javaslattetel']) && !($_SESSION[get_db_tabla().'szemelyzet_id']==MUSZAKI_VEZETO && $munkalap['szemelyzet_id']==MUSZAKI_VEZETO && !$telephely['modell']) ) {
            echo json_encode(array(
                'hiba'  => 'Önnek nincs joga külső kivitelezőt hozzárendelni!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }

        // Munkalap update
        if(isset($_POST['javaslattetel'])) {
            $sql = $adatbazis_kapcsolat -> u('munkalap',
                                                        array('szallito_id',    'szemelyzet_id' ),
                                                        array($szallitoid,      ''              ),
                                                "`recid` = '{$recid}'");
            if( $sql === false ) { $db_error = true; }
            if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_DONTESRE_VAR ) ) {
                $db_error = true;
            }
        }else{
            $sql = $adatbazis_kapcsolat -> u('munkalap', 
                                                        array('szallito_id', 'szemelyzet_id' ),
                                                        array($szallitoid,   KULSO_KIVITELEZO),
                                                "`recid` = '{$recid}'");
            if( $sql === false ) { $db_error = true; }
            if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_FOLYAMATBAN ) ) {
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
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
    }

    $weblap->leallit();
    exit();
}

// Munkalap külső kivitelező select előállít
if( isset($_POST['munkalap_kulsoSelect']) ) {
    require('selectek.php');

    $recid = $_POST['recid'];

    if( $select_szemelyzet ) {

        $munkalap = $adatbazis_kapcsolat->s2('stat_id', 'munkalap', "`recid` = '{$recid}'", '', '1');
        if( $munkalap === false ) { $db_error = true; }

        // Státusz ellenőrzése
        if( $munkalap['stat_id'] != MUNKALAP_STAT_NYITOTT ) {
            echo json_encode(array(
                'hiba'  => 'Csak nyitott státuszban levő munkalapokat lehet kiadni külsősnek!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzése
        if( !_jog(JOG_MUNKALAP_NYITOTT_KEZEL) ) {
            echo json_encode(array(
                'hiba'  => 'Nincs joga az átadáshoz!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        $select = nl2('
                        <select id="p_szemelyzet_id" name="p_szemelyzet_id">
                            <option value="">- Válasszon -</option>
                        ');
        
        foreach($select_szemelyzet as $value) {
            if($value['kulsos']){
                $select .= '<option value="'.$value['value'].'">'.$value['name'].'</option>';
            }
        }

        $select .= '</select>';

        echo json_encode(array(
                            'statusz' => 'siker',
                            'select'  => $select
                        ));
    }

    $weblap->leallit();
    exit();
}

// Munkalap lezárás
if( isset($_POST['munkalapLezaras']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        $munkalap = $adatbazis_kapcsolat->s2('stat_id', 'munkalap', "`recid` = '{$recid}'");
        if( $munkalap === false ) { $db_error = true; }

        if( $munkalap ) {

            if( $munkalap['stat_id'] != MUNKALAP_STAT_LEZARASRA_VAR ) {
                echo json_encode(array(
                    'hiba'  => 'Csak '.getStatText('munkalap', MUNKALAP_STAT_LEZARASRA_VAR).' státuszú munkalapot lehet lezárni!',
                    'title' => 'Hiba',
                ));

                $weblap->leallit();
                exit();
            }
        }

        // Előbb csak kérdezünk
        if (isset($_POST['csak_kerdezunk'])) {
            echo json_encode(array(
                'hiba' => 'Biztos lezárja a munkalapot?',
                'gomb' => '<input class="gomb zold" value="Igen" onclick="munkalapLezaras();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        $munk_kapcsok = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id` = '{$recid}'", '', '1');
        if( $munk_kapcsok === false ) { $db_error = true; }

        if( $munk_kapcsok ) {

            $tobb_munk = $adatbazis_kapcsolat->s('recid munkalap_id', 'munkalap_kapcs', "`bejelentes_id` = '".$munk_kapcsok['bejelentes_id']."' AND `munkalap_id` != '{$recid}'");
            if( $tobb_munk === false ) { $db_error = true; }

            // Megnézzük, hogy szerepl e máshol a bejelentés
            $bejelentesek_kapcs = $adatbazis_kapcsolat->s('bejelentes_id', 'munkalap_kapcs', "`munkalap_id` = '{$recid}'");
            if( $bejelentesek_kapcs === false ) { $db_error = true; }

            if( $bejelentesek_kapcs ) {
                $bej_db = count($bejelentesek_kapcs);
                $bej_lezart_db = 0;

                foreach($bejelentesek_kapcs as $bej_kapcs) {
                    
                    // Bejelentés lekérdezése
                    $bejelentes = $adatbazis_kapcsolat->s2('stat_id', 'bejelentes', "`recid` = '".$bej_kapcs['bejelentes_id']."'");
                    if( $bejelentes === false ) {  $db_error = true; }

                    if( $bejelentes ) {
                        if( $bejelentes['stat_id'] == BEJELENTES_STAT_LEZART ) {
                            $bej_lezart_db++;
                        }
                    }
                }

                // Minden bejelentése lezárt státusz alatt van, ezért zárható a munkalap
                if( $bej_db == $bej_lezart_db ) {
                    if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_LEZART ) ) {
                        $db_error = true;
                    }
                } else {
                    echo json_encode(array(
                        'hiba'  => 'A munkalap igazolás alatt vagy vizsgálat alatt van. Nem lehet lezárni!',
                        'title' => 'Hiba',
                    ));

                    $weblap->leallit();
                    exit();
                }

                // Lezárjuk az összes többi munkáját
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
                        'hiba'  => tranzakcioHiba(),
                        'title' => 'Hiba',
                    ));
                }
            }
        }
    }

    $weblap->leallit();
    exit();
}

// Diszpécser központ vezető átadás
if( isset($_POST['diszKozpVezAtadas']) ) {
    $recid    = $_POST['recid'];
    $memo     = $_POST['memo'];
    $validate = $_POST['validate'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        $munkalap = $adatbazis_kapcsolat->s2('stat_id', 'munkalap', "`recid` = '{$recid}'", '', '1');
        if( $sql === false ) { $db_error = true; }

        if( $munkalap ) {

            if( $munkalap['stat_id'] == MUNKALAP_STAT_ANYAGRAVAR || $munkalap['stat_id'] == MUNKALAP_STAT_FELVEVE ) {
                echo json_encode(array(
                    'hiba'  => 'Anyagra vár és felvéve státuszú munkalapokat nem lehet átadni!',
                    'title' => 'Hiba',
                ));

                $weblap->leallit();
                exit();
            }
            
            // Ha felvéve státuszban van, akkor azt is ellenőrizzük, hogy van-e feladat az utolsó tételéhez csatolva
            if($munkalap['stat_id'] == MUNKALAP_STAT_FELVEVE){
                $utolso_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id`='{$recid}'", 'recid desc', '1');
                $feladat      = $adatbazis_kapcsolat->s2('recid', 'munkalap_feladat', "`tetel_id`='{$utolso_tetel['recid']}'", '', '1');
                if(!$feladat){
                    echo json_encode(array(
                        'hiba'  => 'Nem adható át a feladat, ha az utolsó tételhez még nincs feladat rendelve!',
                        'title' => 'Hiba',
                    ));

                    $weblap->leallit();
                    exit();
                }
            }

            if( $munkalap['stat_id'] == MUNKALAP_STAT_LEZARASRA_VAR ) {
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
                                'hiba'  => 'Csak vizsgálat alatt lévő bejelentéseket lehet átadni!',
                                'title' => 'Hiba',
                            ));

                            $weblap->leallit();
                            exit();
                        }

                        $sql = $tobb_munk = $adatbazis_kapcsolat->s('recid', 'munkalap_kapcs', "`bejelentes_id` = '".$munk_kapcsok['bejelentes_id']."' AND `munkalap_id` != '{$recid}'", '', '');
                        if( $sql === false ) { $db_error = true; }

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
                    'hiba'  => 'Csak kész, igazolás alatt státuszú munkalap adható át!',
                    'title' => 'Hiba',
                ));

                $weblap->leallit();
                exit();
            }
        }

        if( $validate == 'true' ) {
            echo json_encode(array(
                'statusz'     => 'siker',
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
                    'hiba'  => tranzakcioHiba(),
                    'title' => 'Hiba',
                ));
            }
        }
    }

    $weblap->leallit();
    exit();
}

// Diszpécser központ vezető lezárása
if( isset($_POST['diszKozpVezLezaras']) ) {
    $recid    = $_POST['recid'];
    $memo     = $_POST['memo'];
    $validate = $_POST['validate'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        $munkalap = $adatbazis_kapcsolat->s2('stat_id', 'munkalap', "`recid` = '{$recid}'");
        if( $munkalap === false ) { $db_error = true; }


        /* Ha nem kész státuszban van a munkalap */
        if( $munkalap['stat_id'] != MUNKALAP_STAT_LEZARASRA_VAR ) {
            if( $munkalap['stat_id'] == MUNKALAP_STAT_LEZART ) {
                echo json_encode(array(
                    'hiba'  => 'Lezárt munkalapot nem lehet újra lezárni!',
                    'title' => 'Hiba',
                ));

                $weblap->leallit();
                exit();
            }

            $is_tobbmunk = false;
            $tobbimunkalap = array();

            /* Megnézzük hogy van e több munkalapja a bejelentésnek */
            $sql = $munk_kapcsok = $adatbazis_kapcsolat->s('bejelentes_id', 'munkalap_kapcs', "`munkalap_id` = '{$recid}'", '', '1');
            if( $sql === false ) { $db_error = true; }

            if( $munk_kapcsok ) {
                $munk_kapcsok = $munk_kapcsok[0];

                $bejelentes = $adatbazis_kapcsolat->s('stat_id', 'bejelentes', "`recid` = '".$munk_kapcsok['bejelentes_id']."'", '', '1');
                if( $bejelentes === false ) { $db_error = true; }

                if( $bejelentes ) {
                    $bejelentes = $bejelentes[0];

                    $sql = $tobb_munk = $adatbazis_kapcsolat->s('recid munkalap_id', 'munkalap_kapcs', "`bejelentes_id` = '".$munk_kapcsok['bejelentes_id']."' AND `munkalap_id` != '{$recid}'", '', '');
                    if( $sql === false ) {  $db_error = true; }

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
                        'hiba'  => tranzakcioHiba(),
                        'title' => 'Hiba',
                    ));
                }

                $weblap->leallit();
                exit();
            }

            /* Ha a munkalap kész státuszban van */
            if( $munkalap['stat_id'] == MUNKALAP_STAT_LEZARASRA_VAR ) {
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
                                'hiba'  => 'A munkalap bejelentése '.getStatText('bejelentes', $bejelentes['stat_id']).' státuszban van. A munkalapot nem lehet lezárni!',
                                'title' => 'Hiba',
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
                    
                // Feldolgozás
                } else {
                    // Munkalap lezárul
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
                            'hiba'  => tranzakcioHiba(),
                            'title' => 'Hiba',
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
                    'hiba'  => tranzakcioHiba(),
                    'title' => 'Hiba',
                ));
            }
        }
    }

    $weblap->leallit();
    exit();
}

// Munkalap anyagok
if( isset($_POST['getMunkalapFeladatok']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {

        $munkalap = $sor = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id', 'munkalap', "`recid`='$recid'");
        if( $munkalap === false ) { $db_error = true; }

        // Státusz ellenőrzése
        if( $munkalap['stat_id'] != MUNKALAP_STAT_FOLYAMATBAN && $munkalap['stat_id'] != MUNKALAP_STAT_FELVEVE && $munkalap['stat_id'] != MUNKALAP_STAT_VIZSGALAT_ALATT && $munkalap['stat_id'] != MUNKALAP_STAT_LEZARASRA_VAR ) {
            echo json_encode(array(
                'hiba'  => 'Ebben a státuszban nem adható hozzá feladat!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Kezelés jog ellenőrzése
        $kezeles_jog = get_kezeles_jog($sor['szemelyzet_id']);
        $felvehetik = array(TELEPITETT_SZEMELYZET, MOBILCSOPORT);
        if( 
                !(
                    ( $sor['stat_id']==MUNKALAP_STAT_FOLYAMATBAN     && $sor['szemelyzet_id']==KULSO_KIVITELEZO && ( _jog(JOG_MUNKALAP_KEZEL_KULSO) || ( _jog(JOG_MUNKALAP_SAJAT_KEZEL) && $_SESSION[get_db_tabla().'szemelyzet_id']==KULSO_KIVITELEZO ) ) ) ||
                    ( $sor['stat_id']==MUNKALAP_STAT_FELVEVE         && in_array($sor['szemelyzet_id'], $felvehetik) && $kezeles_jog )  ||
                    ( $sor['stat_id']==MUNKALAP_STAT_VIZSGALAT_ALATT && _jog(JOG_MUNKALAP_VIZSGALAT) ) ||
                    ( $sor['stat_id']==MUNKALAP_STAT_LEZARASRA_VAR   && $kezeles_jog )
                )
            ) {
            echo json_encode(array(
                'hiba'  => 'Önnek nincs joga a művelethez!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }

        $akt_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id` = '{$recid}'", 'recid desc', '1');
        if( $akt_tetel === false ) { $db_error = true; }

        $munkalap_feladatok = $adatbazis_kapcsolat->s('recid erteklista_id memo', 'munkalap_feladat', "`munkalap_id` = '{$recid}' AND `tetel_id` = '".$akt_tetel['recid']."'");
        if( $munkalap_feladatok === false ) { $db_error = true; }

        if( $munkalap_feladatok ) {
            $sorok = '';
            foreach($munkalap_feladatok as $munkalap_f) {
                $sorok .= nl2('
                    <tr class="feladat_sor_'.$munkalap_f['recid'].'">
                        <td>'.erteklista(5, $munkalap_f['erteklista_id']).'<input type="hidden" name="addCikkID[]" value="'.$munkalap_f['cikk_id'].'"><input type="hidden" name="addCikkUj[]" value="0"></td>
                        <td>'.nl2br($munkalap_f['memo']).'</td>
                        <td><a id="feladat_id_'.$munkalap_f['recid'].'" onclick="munkFeladatTorol( \''.$munkalap_f['recid'].'\', \'feladat_id_'.$munkalap_f['recid'].'\' );"><span class="icon"><i class="material-icons">delete</i></span></a></td>
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
                    'html'    => $html
                ));
            } else {
                mysqli_rollback($db_kapcs);

                echo json_encode(array(
                    'hiba'  => tranzakcioHiba(),
                    'title' => 'Hiba',
                ));
            }
        }
    }

    $weblap->leallit();
    exit();
}

// Munkalap munkaidő
if( isset($_POST['getMunkalapMunkaido']) ) {

    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {

        $akt_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id` = '{$recid}'", 'recid desc', '1');
        if( $akt_tetel === false ) { $db_error = true; }

        $munkaidok = $adatbazis_kapcsolat->s('recid login_id ora perc', 'munkalap_munkaido', "`munkalap_id` = '{$recid}' AND `tetel_id` = '".$akt_tetel['recid']."'");
        if( $munkaidok === false ) { $db_error = true; }

        $sorok = '';
        foreach($munkaidok as $munkaido) {
            $login = $adatbazis_kapcsolat->s2('teljes_nev', 'login', "`recid`='{$munkaido['login_id']}'");
            if($munkaido['perc']<10){
                $munkaido['perc'] = '0'.$munkaido['perc'];
            }
            $sorok .= nl2('
                <tr class="munkaido_sor_'.$munkaido['recid'].'">
                    <td id="munkaido_nev_'.$munkaido['recid'].'">'.$login['teljes_nev'].'</td>
                    <td id="munkaido_ido_'.$munkaido['recid'].'">'.$munkaido['ora'].':'.$munkaido['perc'].'</td>
                    <td><a id="munk_ido_'.$munkaido['recid'].'" onclick="munkMunkaidoTorol( \''.$munkaido['recid'].'\', \'munk_ido_'.$munkaido['recid'].'\' );"><span class="icon"><i class="material-icons">delete</i></span></a></td>
                </tr>
            ');
        }

        $html = nl2('
            <table class="csikos">
                <tr>
                    <th>Név</th>
                    <th>Óra:perc</th>
                    <th class="torles"></th>
                </tr>
                '.$sorok.'
            </table>
        ');

        if( !$db_error ) {
            mysqli_commit($db_kapcs);
            echo json_encode(array(
                'statusz' => 'siker',
                'html'    => $html
            ));
        } else {
            mysqli_rollback($db_kapcs);
            echo json_encode(array(
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
    }

    $weblap->leallit();
    exit();
}

// Munkalap sorok lekérdezése
if( isset($_POST['hozzBeosztasNelkul']) ) {
    $idk = json_decode($_POST['idk'], true);

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( is_array($idk) ) {
        foreach($idk as $id) {

            $statusz = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id', 'munkalap', "`recid` = '{$id}'");
            if( $statusz === false ) { $db_error = '3'; }

            //Nem kell mindig figyelni a státuszt
            if( $statusz && !isset($_POST['no_statusz_hiba']) ) {

                /*if( !in_array($statusz['stat_id'], array(MUNKALAP_STAT_FELVEVE, MUNKALAP_STAT_FOLYAMATBAN)) ) {
                    echo json_encode(array(
                        'hiba'  => 'Kérem csak felvéve vagy folyamatban státuszú munkalapokat jelöljön meg!',
                        'gomb'  => '<input class="link gomb" value="Ok" onclick="csoportMuvelet(\'munkalap_mobil\', \'státus hibafigyelés nélkül\'); alert2();" type="button">',
                        'title' => 'Hiba',
                    ));

                    $weblap->leallit();
                    exit();
                }*/

                if( $statusz['stat_id'] == MUNKALAP_STAT_FOLYAMATBAN && $statusz['szemelyzet_id']!=MOBILCSOPORT ) {
                    echo json_encode(array(
                        'hiba'  => 'Ha a munkalap státusza folyamatban státuszú, akkor csak mobilcsoporthoz rendelt munkalap jelölhető meg!',
                        'gomb'  => '<input class="link gomb" value="Ok" onclick="csoportMuvelet(\'munkalap_mobil\', \'státus hibafigyelés nélkül\'); alert2();" type="button">',
                        'title' => 'Hiba',
                    ));

                    $weblap->leallit();
                    exit();
                }

            }
        }

        $osszes_sor = 0;

        // Hozzárendelt munkalapok beosztás nélkül
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

                // Telephely
                $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$munkalap['telephely_id'], '', '1');
                $telephely = $telephely[0]['nev'];

                // Sürgős e
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

        // Személyzethez rendelt munkalapok
        $munkalapok = $adatbazis_kapcsolat->s('recid ikt_szam telephely_id stat_id mobil_loginnev surgos', 'munkalap', "`mobil_loginnev` != '' AND `szemelyzet_id` = '".MOBILCSOPORT."' AND `stat_id` IN (".MUNKALAP_STAT_FOLYAMATBAN.", ".MUNKALAP_STAT_FELVEVE.")");
        $osszes_sor += count($munkalapok);
        if( $munkalapok === false ) { $db_error = '1'; }

        $szemRendelt = '';
        $sorok       = '';
        if( $munkalapok ) {
            $i = 1;
            foreach($munkalapok as $munkalap) {
                // Telephely
                $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$munkalap['telephely_id'], '', '1');
                $telephely = $telephely[0]['nev'];

                // Sürgős e
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
                'hiba'  => 'Nincsenek megjeleníthető sorok!',
                'title' => 'Hiba',
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
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
    }

    $weblap->leallit();
    exit();
}

// Munkalap érkeztetése
if( isset($_POST['munkalapErkeztet']) ) {

    $munkalap = $adatbazis_kapcsolat->s2('stat_id ikt_szam telephely_id', 'munkalap', "`recid` = '{$_POST['recid']}'");

    // Státusz ellenőrzése
    if( !in_array($munkalap['stat_id'], array(MUNKALAP_STAT_ANYAGRAVAR)) ) {
        echo json_encode(array(
            'hiba'  => $munkalap['ikt_szam'].'<br>Ez a munkalap nem anyagra vár státuszban van. Nem lehet hozzá anyagot érkeztetni!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }

    // Jog ellenőrzése
    if( !_jog(JOG_MUNKALAP_ANYAG_ERKEZTET) ) {
        echo json_encode(array(
            'hiba'  => 'Nincs joga anyagot érkeztetni!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }

    $telephely = $adatbazis_kapcsolat->s2('nev', 'telephely', "`id`='{$munkalap['telephely_id']}'");

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

        // ha anyagstátusz= igénylés alatt vagy igényelve, anyag beérkezett oszlopban pipáló
        // ha anyagstátusz=rendelkezésre áll vagy üres (ez elvileg nem lehet), szürkén mutatjuk sort és nem tud pipálni
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

// Munkalap érkeztetésének mentése
if( isset($_POST['munkalapErkeztetMent']) ) {

    $idk = json_decode($_POST['idk'], true);

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    // Kezelés jog ellenőrzése
    if( !_jog(JOG_MUNKALAP_ANYAG_ERKEZTET) ) {
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga anyagot érkeztetni!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    if( is_array($idk) && $idk ) {

        $sql = $adatbazis_kapcsolat->u('munkalap_anyag',    array('szuk_men', 'stat_id',               'update_usr'                             ),
                                                            array('0',        DISZPNAG_STAT_VAN_ANYAG, $_SESSION[get_db_tabla().'teljes_nev']   ),
                                        "`recid` IN (".implode(',', $idk).")");
        if( $sql === false ) { $db_error = true; }

        // Ellenőrizzük, hogy van-e még olyan sor, ahol statid=DISZPNAG_STAT_VAN_ANYAG, ha nincs, a munkalap státuszt átállítjuk: Nyitott és viszaadva=1
        $munkalap_id = $adatbazis_kapcsolat->s2('munkalap_id', 'munkalap_anyag', "`recid`='{$idk[0]}'");
        if( $munkalap_id === false ) { $db_error = true; }

        $mind_kesz = $adatbazis_kapcsolat->s('recid', 'munkalap_anyag', "`munkalap_id`='{$munkalap_id['munkalap_id']}' AND `stat_id`!='".DISZPNAG_STAT_VAN_ANYAG."'");
        if( $mind_kesz === false ) { $db_error = true; }
        if(count($mind_kesz)==0){
            if( !addStatusz( 'munkalap_statusz', $munkalap_id['munkalap_id'], MUNKALAP_STAT_NYITOTT ) ) {
                $db_error = true;
            }
            $sql = $adatbazis_kapcsolat->u('munkalap', array('visszaadva'), array(1), "`recid`='{$munkalap_id['munkalap_id']}'");
            if( $sql === false ) { $db_error = true; }
        }
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba',
        ));
    }

    $weblap->leallit();
    exit();
}

// Munkalap feladat törlése
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

// Munkalap idő törlése
if( isset($_POST['munkMunkaidoTorol']) ) {
    $recid = $_POST['recid'];

    if( $recid ) {
        $adatbazis_kapcsolat -> d('munkalap_munkaido', "`recid` = '{$recid}'");
        echo json_encode(array(
            'statusz' => 'siker'
        ));
    }

    $weblap->leallit();
    exit();
}

// Munkalap anyagok
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

// Munkalapon az utolsó tételnél van-e feladat
if( isset($_POST['van_e_feladat_feladat_hozzaad']) ) {
    $recid      = $_POST['recid'];
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $munkalap = $sor = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id telephely_id', 'munkalap', "`recid`='{$recid}'");
    if( $munkalap === false ) { $db_error = true; }
    
    // Folyamatban státusz KIVÉTELÉVEL minden státuszan ellenőrizni kell, hogy utolsó tétel van-e. Ha nincs, hibaüzenet
    if( $sor['stat_id']!=MUNKALAP_STAT_FOLYAMATBAN ){
    
        // Vegyük az utolsó tételt
        $akt_tetel = $adatbazis_kapcsolat->s2('recid atadta_dt', 'munkalap_tetel', "`munkalap_id` = '{$recid}'", 'recid desc', '1');
        if( $akt_tetel === false ) { $db_error = true; }

        // Nézzük meg, hogy van-e tétel a munkalapon. Ha nincs, akkor hibaüzenet
        $akt_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id` = '{$recid}'", 'recid desc', '1');
        if( $akt_tetel === false ) { $db_error = true; }
        if( !$akt_tetel ) {
            echo json_encode(array(
                'hiba'  => 'A munkalapon nem található tétel, így nem adható hozzá feladat!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }
        
    }
    
    if( $db_error ) {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba',
        ));
    }
    exit;
    
}

// Munkalapon az utolsó tételnél van-e feladat
if( isset($_POST['van_e_feladat']) ) {
    $recid      = $_POST['recid'];
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $munkalap = $sor = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id telephely_id', 'munkalap', "`recid`='{$recid}'");
    if( $munkalap === false ) { $db_error = true; }
    
    // Anyagra vár és előkészítés alatt státusztban nem figyeljük, hogy van-e tétel
    if( $sor['stat_id']!=MUNKALAP_STAT_ANYAGRAVAR && $sor['stat_id']!=MUNKALAP_STAT_ELOKESZITES_ALATT ){

        // Hibaüzenetekhez kell
        $szo = 'Anyag felhasználáshoz';
        if($_POST['igenyles']){
            $szo = 'Anyag igényléshez ';
        }
        if($_POST['munkaido']){
            $szo = 'Munkaidő hozzáadásához';
        }
        if($_POST['atadas_diszp']){
            $szo = 'Visszaadáshoz';
        }
    
        // Nyitott státusznál az is feltétel, hogy a tétel is nyitott-e, azaz nincs átadta dátum kitöltve. Egyébként csak a feltétel, hogy létezzen utolsó tétel.
        $sql_kieg = '';
        if( $sor['stat_id']==MUNKALAP_STAT_FOLYAMATBAN ){
            $sql_kieg = " AND `atadta_dt`='0000-00-00 00:00:00'";
        }

        // Van-e utolsó (nyitott) tétel?
        $utolso_tetel = $adatbazis_kapcsolat->s2('recid atadta_dt', 'munkalap_tetel', "`munkalap_id`='{$recid}'".$sql_kieg, 'recid desc', '1');
        if( $utolso_tetel === false ) { $db_error = true; }
        // Ha nincs utolsó (nyitott) tétel, akkor hibaüzenet:
        if(!$utolso_tetel['atadta_dt']){
            echo json_encode(array(
                'hiba'  => $szo.HIBA_ADJON_FELADAT,
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }
        $feladat = $adatbazis_kapcsolat->s2('recid', 'munkalap_feladat', "`tetel_id`='{$utolso_tetel['recid']}'", '', '1');
        if( !$feladat ) {
            echo json_encode(array(
                'hiba'  => $szo.HIBA_ADJON_FELADAT,
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }
    }
    
    if( $db_error ) {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba',
        ));
    }
    exit;
    
}

// Munkalapon található anyagok kilistázása táblázatba
if( isset($_POST['getMunkalapAnyagok']) ) {
    $recid      = $_POST['recid'];
    $szuk_menny = $_POST['szuk_menny'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {

        $anyagok = $adatbazis_kapcsolat->s('recid cikk_id cikk_nev cikk_men cikk_mee szuk_men', 'munkalap_anyag', "`munkalap_id` = '{$recid}' AND `tetel_id` = '".$akt_tetel['recid']."'");
        if( $anyagok === false ) { $db_error = true; }

        if( $anyagok ) {
            $sorok = '';
            foreach($anyagok as $anyag) {
                $sz_td = '';
                if( $szuk_menny ) {
                    $sz_td = '<td>'.$anyag['szuk_men'].'<input type="hidden" name="addCikkSzukMenny[]" value="'.$anyag['szuk_men'].'"></td>';
                }

                $sorok .= nl2('
                    <tr class="anyag_sor_'.$anyag['recid'].'">
                        <td>'.$anyag['cikk_id'].'<input type="hidden" name="addCikkID[]" value="'.$anyag['cikk_id'].'"><input type="hidden" name="addCikkUj[]" value="0"></td>
                        <td>'.$anyag['cikk_nev'].'</td>
                        <td>'.$anyag['cikk_men'].'<input type="hidden" name="addCikkFelhMenny[]" value="'.$anyag['cikk_men'].'"></td>
                        '.$sz_td.'
                        <td>'.$anyag['cikk_mee'].'</td>
                        <td></td>
                        <td><a id="anyag_id_'.$anyag['recid'].'" onclick="munkAnyagTorol( \''.$anyag['recid'].'\', \'anyag_id_'.$anyag['recid'].'\' );"><span class="icon"><i class="material-icons">delete</i></span></a></td>
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
                    'html'    => $html
                ));
            } else {
                mysqli_rollback($db_kapcs);

                echo json_encode(array(
                    'hiba'  => tranzakcioHiba(),
                    'title' => 'Hiba',
                ));
            }
        }

    }

    $weblap->leallit();
    exit();
}

// Anyagigénylés szükséges
if( isset($_POST['munkAnyagIgenyles']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {

        $munkalap = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id', 'munkalap', "`recid`='{$recid}'");
        if( $munkalap === false ) { $db_error = true; }

        // Státusz ellenőrzése
        if( $munkalap['stat_id']!=MUNKALAP_STAT_FELVEVE ){
            echo json_encode(array(
                'hiba'  => 'Csak felvéve státuszban igényelhető anyag!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }

        // Kezelés jog ellenőrzése
        if( !($munkalap['stat_id']==MUNKALAP_STAT_FELVEVE && get_kezeles_jog($munkalap['szemelyzet_id'])) ) {
            echo json_encode(array(
                'hiba'  => 'Önnek nincs joga anyagot igényelni!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }
        
        // Van-e feladat az utolsó tételnél
        $utolso_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id`='{$recid}'", 'recid desc', '1');
        $feladat      = $adatbazis_kapcsolat->s2('recid', 'munkalap_feladat', "`tetel_id`='{$utolso_tetel['recid']}'", '', '1');
        if( !$feladat ){
            echo json_encode(array(
                'hiba'  => 'A munkalap utolsó tételénél nincs feladat, így nem lehet anyagot igényelni!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }

        // Új
        parse_str($_POST['addCikkUj']);

        // Cikkek
        parse_str($_POST['addCikkID']);

        // Felhasznált mennyiség
        parse_str($_POST['addCikkFelhMenny']);

        // Szükséges mennyiség
        parse_str($_POST['addCikkSzukMenny']);

        $szuk_menny = false;
        if( $addCikkSzukMenny ) {
            foreach($addCikkSzukMenny as $key => $value) {
                if( !empty($value) && $addCikkUj[$key] && $value > 0 ) {
                    $szuk_menny = true;
                }
            }
        }

        // Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if(isset($_POST['csak_kerdezunk']) && $szuk_menny) {
            echo json_encode(array(
                'hiba' => 'Ha folytatja, a munkalap Anyagra vár státuszba kerül. Folytatja?',
                'gomb' => '<input class="gomb zold" value="Igen" onclick="munkAnyagIgenyles();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        if( count($addCikkID) && count($addCikkID) == count($addCikkFelhMenny) ) {
            $akt_tetel = $adatbazis_kapcsolat->s('recid', 'munkalap_tetel', "`munkalap_id` = '{$recid}'", 'recid desc', '1');
            if( $akt_tetel === false ) { $db_error = true; }

            if( $akt_tetel ) {
                $akt_tetel = $akt_tetel[0];

                foreach($addCikkID as $key => $value) {

                    $sql = $is_anyag = $adatbazis_kapcsolat->s('recid', 'munkalap_anyag', "`munkalap_id` = '{$recid}' AND `tetel_id` = '".$akt_tetel['recid']."' AND `cikk_id` = '{$value}'", '', '1');
                    if( $sql === false ) { $db_error = true; }

                    $cikk_sor = $adatbazis_kapcsolat->s2('id nev cikkmee oracikkid', 'cikktorzs', "`id` = '{$value}'");
                    if( $cikk_sor === false ) { $db_error = true; }
                    
                    // Ezresítő szóközöket kiszűrjük a emnyiségből
                    $addCikkFelhMenny[$key] = str_replace(' ', '', $addCikkFelhMenny[$key]);

                    if( $is_anyag && $addCikkUj[$key] ) {
                        echo json_encode(array(
                            'hiba'  => 'A(z) "'.$cikk_sor['nev'].'" anyag már hozzáadásra került!',
                            'title' => 'Hiba',
                        ));

                        $weblap->leallit();
                        exit();
                    }

                    if( $addCikkUj[$key] ) {
                        if( $cikk_sor ) {
                            $stat_id = DISZPNAG_STAT_VAN_ANYAG;
                            if( !empty($addCikkSzukMenny[$key]) && $addCikkSzukMenny[$key] > 0 ) {
                                $stat_id = DISZPNAG_STAT_ANYAGRA_VAR;
                            }

                            $mlap = $adatbazis_kapcsolat->s2('telephely_id', 'munkalap', "`recid` = '{$recid}'");
                            if( $mlap === false ) { $db_error = true; }

                            $sql = $adatbazis_kapcsolat -> i('munkalap_anyag',
                                                                array('munkalap_id', 'telephely_id',        'tetel_id',          'cikk_id',       'oracikkid',            'cikk_nev',                                'cikk_mee',             'cikk_men',              'szuk_men',                'stat_id', 'insert_dt',    'insert_usr',                             'update_usr'                          ),
                                                                array($recid,        $mlap['telephely_id'], $akt_tetel['recid'], $cikk_sor['id'], $cikk_sor['oracikkid'], htmlspecialchars_decode($cikk_sor['nev']), $cikk_sor['cikkmee'],   $addCikkFelhMenny[$key], $addCikkSzukMenny[$key],   $stat_id,  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'])
                                                            );

                            if( $sql === false ) { $db_error = true; }
                        }
                    }
                }

                // Anyagra vár szátuszba kerül
                if( $szuk_menny ) {
                    if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_ANYAGRAVAR ) ) {
                        $db_error = true;
                    }

                    // Munkalapon töröljük a személyzetet és a mobil loginnevet
                    $sql = $adatbazis_kapcsolat -> u('munkalap',
                                                        array('szemelyzet_id', 'mobil_loginnev', 'szallito_id', 'update_usr'                           ),
                                                        array( 0,              '',                0,            $_SESSION[get_db_tabla().'teljes_nev'] ),
                                                        "`recid` = '{$recid}'"
                                                    );
                    if( $sql === false ) { $db_error = true; }

                    // Utolsó tételnél átállítjuk az átadta mező értékét
                    $sql = $adatbazis_kapcsolat -> u('munkalap_tetel',
                                                        array('atadta_dt',  'update_usr'                           ),
                                                        array('NOW()',      $_SESSION[get_db_tabla().'teljes_nev'] ),
                                                        "`recid` = '".$akt_tetel['recid']."'"
                                                    );
                    if( $sql === false ) { $db_error = true; }
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
                    'hiba'  => tranzakcioHiba(),
                    'title' => 'Hiba',
                ));
            }
        } else {
            echo json_encode(array(
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
    }

    $weblap->leallit();
    exit();
}

// Munkalap anyag felhasználás
if( isset($_POST['munkAnyagVetelezes']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {

        $munkalap = $sor = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id', 'munkalap', "`recid`='{$recid}'");
        if( $munkalap === false ) { $db_error = true; }

        // Státusz ellenőrzése
        if( $munkalap['stat_id'] != MUNKALAP_STAT_FELVEVE && $munkalap['stat_id'] != MUNKALAP_STAT_LEZARASRA_VAR && $munkalap['stat_id'] != MUNKALAP_STAT_VIZSGALAT_ALATT ) {
            echo json_encode(array(
                'hiba'  => 'Ebben a státuszban nem lehet anyagot felhasználni!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }
        
        $kezeles_jog = get_kezeles_jog($sor['szemelyzet_id']);
        $felvehetik = array(TELEPITETT_SZEMELYZET, MOBILCSOPORT);
        // Jog ellenőrzése
        if(
            !(
                ( $sor['stat_id']==MUNKALAP_STAT_FELVEVE         && in_array($munkalap['szemelyzet_id'], $felvehetik) && $kezeles_jog ) ||
                ( $sor['stat_id']==MUNKALAP_STAT_LEZARASRA_VAR   && in_array($munkalap['szemelyzet_id'], $felvehetik) && $kezeles_jog ) ||
                ( $sor['stat_id']==MUNKALAP_STAT_VIZSGALAT_ALATT && _jog(JOG_MUNKALAP_VIZSGALAT) )
            )
        ) {
            echo json_encode(array(
                'hiba'  => 'Önnek nincs joga anyagot felhasználni!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }
        
        // Van-e feladat az utolsó tételnél
        $utolso_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id`='{$recid}'", 'recid desc', '1');
        $feladat      = $adatbazis_kapcsolat->s2('recid', 'munkalap_feladat', "`tetel_id`='{$utolso_tetel['recid']}'", '', '1');
        if( !$feladat ){
            echo json_encode(array(
                'hiba'  => 'A munkalap utolsó tételénél nincs feladat, így nem lehet anyagot felhasználni!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }

        // Új
        parse_str($_POST['addCikkUj']);

        // Cikkek
        parse_str($_POST['addCikkID']);

        // Felhasznált mennyiség
        parse_str($_POST['addCikkFelhMenny']);

        if( count($addCikkID) && count($addCikkID) == count($addCikkFelhMenny) ) {

            $akt_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id` = '{$recid}'", 'recid desc', '1');
            if( $akt_tetel === false ) { $db_error = true; }

            foreach($addCikkID as $key => $value) {
                $is_anyag = $adatbazis_kapcsolat->s('recid', 'munkalap_anyag', "`munkalap_id` = '{$recid}' AND `tetel_id` = '".$akt_tetel['recid']."' AND `cikk_id` = '{$value}'", '', '1');
                if( $is_anyag === false ) { $db_error = true; }

                $cikk_sor = $adatbazis_kapcsolat->s2('id recid nev cikkmee oracikkid', 'cikktorzs', "`id` = '{$value}'");
                if( $cikk_sor === false ) { $db_error = true; }
                
                // Ezresítő szóközöket kiszűrjük a emnyiségből
                $addCikkFelhMenny[$key] = str_replace(' ', '', $addCikkFelhMenny[$key]);

                if( $is_anyag && $addCikkUj[$key] ) {
                    echo json_encode(array(
                        'hiba'  => 'A(z) "'.$cikk_sor['nev'].'" anyag már hozzáadásra került!',
                        'title' => 'Hiba',
                    ));

                    $weblap->leallit();
                    exit();
                }

                if( $addCikkUj[$key] ) {
                    if( $cikk_sor ) {

                        $mlap = $adatbazis_kapcsolat->s2('telephely_id', 'munkalap', "`recid` = '{$recid}'", '', '1');
                        if( $mlap === false ) { $db_error = true; }

                        $sql = $adatbazis_kapcsolat -> i('munkalap_anyag',
                            array('munkalap_id', 'telephely_id',        'tetel_id',            'stat_id',                  'cikk_id',       'oracikkid',               'cikk_nev',                                 'cikk_mee',             'cikk_men',              'insert_dt',    'insert_usr',                              'update_usr'                             ),
                            array($recid,        $mlap['telephely_id'], $akt_tetel['recid'],    DISZPNAG_STAT_VAN_ANYAG,   $cikk_sor['id'], $cikk_sor['oracikkid'],    htmlspecialchars_decode($cikk_sor['nev']),  $cikk_sor['cikkmee'],   $addCikkFelhMenny[$key], 'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']    )
                        );
                        if( $sql === false ) { $db_error = true; }
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
                    'hiba'  => tranzakcioHiba(),
                    'title' => 'Hiba',
                ));
            }
        } else {
            echo json_encode(array(
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
    }

    $weblap->leallit();
    exit();
}

// Szükséges anyagokra kérdezés
if( isset($_POST['szukseges_anyagok_listaja']) ) {
    $recid          = $_POST['recid'];
    
    $munkalap     = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id telephely_id', 'munkalap', "`recid`='$recid'");
    $utolso_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id`='$recid'", 'recid desc', '1');
    
    // Anyagra vár státusz alatt kilistázzuk a szükséges anyagokat, amik nullázódni fognak
    if ( $munkalap['stat_id']==MUNKALAP_STAT_ANYAGRAVAR ) {

        $szuk_anyagok_html  = '';
        $szuk_anyagok       = $adatbazis_kapcsolat->s('cikk_id cikk_nev cikk_mee', 'munkalap_anyag', "`tetel_id`='{$utolso_tetel['recid']}' AND `szuk_men`>0", 'recid');
        foreach ($szuk_anyagok as $anyag){
            $szuk_anyagok_html .= $anyag['cikk_id'].' '.$anyag['cikk_nev'].' '.$anyag['cikk_mee'].'<br>';
        }
        
        echo json_encode(array(
            'kerdes'  => nl2('
                            Még szükséges anyagok:
                            <br>'.$szuk_anyagok_html.'
                            <br>Ha folyatatja, a munkalap nyitott státuszba kerül és a még szükséges anyagok törlésre kerülnek.
                            <br>Folytatja?
                        ')
        ));

    }
    
    $weblap->leallit();
    exit();
}

// Átadás - visszaadás diszpécsernek
if( isset($_POST['atadasDiszpecsernek']) ) {
    $recid          = $_POST['recid'];
    $erteklista_id  = $_POST['erteklista_id'];
    $memo           = erteklista(7, $erteklista_id)."\r\n".$_POST['memo'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $erteklista_id ) {

        // Státusz és jog ellenőrzése
        $munkalap = $sor = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id telephely_id', 'munkalap', "`recid`='{$recid}'");
        if( $munkalap === false ) { $db_error = true; }

        $telephely    = $adatbazis_kapcsolat->s2('modell', 'telephely', "`id`='{$munkalap['telephely_id']}'");
        if( $telephely === false ) { $db_error = true; }

        $utolso_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id`='{$recid}'", 'recid desc', '1');
        if( $utolso_tetel === false ) { $db_error = true; }

        $felvehetik = array(TELEPITETT_SZEMELYZET, MOBILCSOPORT);
        $kezeles_jog = get_kezeles_jog($sor['szemelyzet_id']);
        if(
            !(
                ( $telephely['modell'] && $sor['stat_id']==MUNKALAP_STAT_FELVEVE            && in_array($sor['szemelyzet_id'], $felvehetik) && $kezeles_jog ) ||
                ( /* Modell nem számít */ $sor['stat_id']==MUNKALAP_STAT_FOLYAMATBAN        && $sor['szemelyzet_id']==KULSO_KIVITELEZO && ( _jog(JOG_MUNKALAP_KEZEL_KULSO) || ( _jog(JOG_MUNKALAP_SAJAT_KEZEL) && $_SESSION[get_db_tabla().'szemelyzet_id']==KULSO_KIVITELEZO ) ) ) ||
                ( $telephely['modell'] && $sor['stat_id']==MUNKALAP_STAT_ANYAGRAVAR         && _jog(JOG_MUNKALAP_ANYAG_ERKEZTET)    ) || 
                ( /* Modell nem számít */ $sor['stat_id']==MUNKALAP_STAT_ELOKESZITES_ALATT  && $_SESSION[get_db_tabla().'szemelyzet_id']==MSZK_VEZETO && $_SESSION[get_db_tabla().'szemelyzet_id']==$sor['szemelyzet_id'] && _jog( JOG_MUNKALAP_KULSOS_ELOKESZIT )  ) ||
                ( /* Modell nem számít */ $sor['stat_id']==MUNKALAP_STAT_VIZSGALAT_ALATT    && _jog(JOG_MUNKALAP_VIZSGALAT)         )
            )
           ){
            echo json_encode(array(
                'hiba'  => 'Nincs joga a visszaadáshoz!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Anyagra vár és előkészítés alatt státuszban az utolsó tételt nem vizsgáljuk
        if( $sor['stat_id']!=MUNKALAP_STAT_ANYAGRAVAR && $sor['stat_id']!=MUNKALAP_STAT_ELOKESZITES_ALATT ){

            // Nyitott státusznál az is feltétel, hogy a tétel is nyitott-e, azaz nincs átadta dátum kitöltve. Egyébként csak a feltétel, hogy létezzen utolsó tétel.
            $sql_kieg = '';
            if( $sor['stat_id']==MUNKALAP_STAT_FOLYAMATBAN ){
                $sql_kieg = " AND `atadta_dt`='0000-00-00 00:00:00'";
            }

            // Van-e utolsó nyitott-e tétel?
            $utolso_tetel = $adatbazis_kapcsolat->s2('recid atadta_dt', 'munkalap_tetel', "`munkalap_id`='{$recid}'".$sql_kieg, 'recid desc', '1');
            if( $utolso_tetel === false ) { $db_error = true; }
            // Ha nincs utolsó (nyitott) tétel, akkor hibaüzenet:
            if(!$utolso_tetel['atadta_dt']){
                echo json_encode(array(
                    'hiba'  => 'Visszaadáshoz'.HIBA_ADJON_FELADAT,
                    'title' => 'Hiba',
                ));
                $weblap->leallit();
                exit();
            }
            $feladat = $adatbazis_kapcsolat->s2('recid', 'munkalap_feladat', "`tetel_id`='{$utolso_tetel['recid']}'", '', '1');
            if( !$feladat ) {
                echo json_encode(array(
                    'hiba'  => 'Visszaadáshoz'.HIBA_ADJON_FELADAT,
                    'title' => 'Hiba',
                ));
                $weblap->leallit();
                exit();
            }

        }

        // Anyagra vár státusz esetén az anyagokat is törölni kell (ha van benne felhasznált anyag, akkor csak nullázni a szükséges mennyiséget)
        if( $munkalap['stat_id'] == MUNKALAP_STAT_ANYAGRAVAR ){
            $sql = $adatbazis_kapcsolat->d('munkalap_anyag', "`tetel_id`='{$utolso_tetel['recid']}' AND `cikk_men`=0.00");
            if( $sql === false ) { $db_error = true; }
            $sql = $adatbazis_kapcsolat->u('munkalap_anyag', array('szuk_men'), array(0.00), "`tetel_id`='{$utolso_tetel['recid']}' AND `cikk_men`!=0.00");
            if( $sql === false ) { $db_error = true; }
        }

        // Frissítjük a munkalap tételt, ha épp dolgozunk rajta, azaz folyamatban vagy felvéve státuszban van.
        if( $munkalap['stat_id'] == MUNKALAP_STAT_FOLYAMATBAN || $munkalap['stat_id'] == MUNKALAP_STAT_FELVEVE ){
            $sql = $adatbazis_kapcsolat -> u('munkalap_tetel',
                                                array('atadta_dt',  'visszaadva', 'erteklista_id', 'update_usr'                           ),
                                                array('NOW()',      1,            $erteklista_id,  $_SESSION[get_db_tabla().'teljes_nev'] ),
                                                "`recid` = '".$utolso_tetel['recid']."'"
                                            );
            if( $sql === false ) { $db_error = true; }
        }

        // Frissítjuk a munkalap visszaadva sorát, ürítjük a személyzeteket
        $sql = $adatbazis_kapcsolat -> u('munkalap',
                                            array('visszaadva', 'szemelyzet_id', 'mobil_loginnev', 'szallito_id', 'mobil',  'update_usr'                           ),
                                            array( 1,            0,              '',                0,            '',       $_SESSION[get_db_tabla().'teljes_nev'] ),
                                            "`recid` = '{$recid}'"
                                        );
        if( $sql === false ) { $db_error = true; }


        // Vizsgálat alatt a bejegyzéseket folyamatbanra állítjuk
        if($munkalap['stat_id'] == MUNKALAP_STAT_VIZSGALAT_ALATT){
            $bejelentes = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$recid}'");
            if( $sql === false ) { $db_error = true; }
            if($bejelentes['bejelentes_id']){
                if( !addStatusz( 'bejelentes_statusz', $bejelentes['bejelentes_id'], BEJELENTES_STAT_FOLYAMATBAN ) ) {
                    $db_error = true;
                }
            }
        }


        // Nyitott státuszra állítjuk a munkalapot
        if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_NYITOTT, $erteklista_id, $memo ) ) {
            $db_error = true;
        }

        // Hozzáadjuk a megjegyzést is ha van
        if( $memo ) {
            $megj_felvesz = addMegj('munkalap', $recid, $memo);
            if( $megj_felvesz === false ) { $db_error = true; }
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);
            echo json_encode(array(
                'statusz' => 'siker'
            ));
        } else {
            mysqli_rollback($db_kapcs);
            echo json_encode(array(
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }

    } else {
        echo json_encode(array(
            'hiba'  => 'Adja meg az átadás okát!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }

    $weblap->leallit();
    exit();
}

// Feladat hozzáadás
if( isset($_POST['feladatHozzaadas']) ) {

    $recid          = $_POST['recid'];
    $erteklista_id  = $_POST['erteklista_id'];
    $memo           = $_POST['memo'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $munkalap = $sor = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id', 'munkalap', "`recid`='$recid'");
    if( $munkalap === false ) { $db_error = true; }
    
    $kezeles_jog = get_kezeles_jog($sor['szemelyzet_id']);
    $felvehetik = array(TELEPITETT_SZEMELYZET, MOBILCSOPORT);
    if(
        !(
            ( $sor['stat_id']==MUNKALAP_STAT_FOLYAMATBAN        && $sor['szemelyzet_id']==KULSO_KIVITELEZO && ( _jog(JOG_MUNKALAP_KEZEL_KULSO) || ( _jog(JOG_MUNKALAP_SAJAT_KEZEL) && $_SESSION[get_db_tabla().'szemelyzet_id']==KULSO_KIVITELEZO ) ) ) ||
            ( $sor['stat_id']==MUNKALAP_STAT_FELVEVE            && in_array($sor['szemelyzet_id'], $felvehetik) && $kezeles_jog )  ||
            ( $sor['stat_id']==MUNKALAP_STAT_VIZSGALAT_ALATT    && _jog(JOG_MUNKALAP_VIZSGALAT) ) ||
            ( $sor['stat_id']==MUNKALAP_STAT_LEZARASRA_VAR      && $kezeles_jog )
        )
       ){
        echo json_encode(array(
            'hiba'  => 'Nincs joga feladatot hozzáadni!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    if( $erteklista_id ) {

        // Folyamatban státusz alatt
        if( $sor['stat_id']==MUNKALAP_STAT_FOLYAMATBAN ){
            
            // Kiszámoljuk a legutóbbi nyitott (atadta_dt=0) munkalap tételt, ehhez kerül hozzáadásra az új feladat
            $akt_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id` = '{$recid}' AND `atadta_dt`='0000-00-00 00:00:00'", 'recid desc', '1');
            if( $akt_tetel === false ) { $db_error = true; }

            // Ha nincs tétel vagy az nem nyitott, akkor létre hozunk egyet
            if( !$akt_tetel ) {
                $sql = $adatbazis_kapcsolat->i('munkalap_tetel',    array('munkalap_id', 'loginnev',                        'insert_dt', 'insert_usr',                           'update_usr'                          ),
                                                                    array($recid,        $_SESSION[get_db_tabla().'user'],  'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'], $_SESSION[get_db_tabla().'teljes_nev'])
                                                );
                if( $sql === false ) { $db_error = true; }

                // Az újonnan létrehozott sor ID-ja lesz az $akt_tetel['recid']
                $akt_tetel['recid'] = $sql;
            }
            
        // Minden más státuszban
        }else{
            
            // Nézzük meg, hogy van-e tétel a munkalapon. Ha nincs, akkor hibaüzenet
            $akt_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id` = '{$recid}'", 'recid desc', '1');
            if( $akt_tetel === false ) { $db_error = true; }
            if( !$akt_tetel ) {
                echo json_encode(array(
                    'hiba'  => 'A munkalapon nem található tétel, így nem adható hozzá feladat!',
                    'title' => 'Hiba',
                ));
                $weblap->leallit();
                exit();
            }
            
        }

        // Ellenőrizzük, hogy a feladat hozzá van-e már adva
        $is_feladat = $adatbazis_kapcsolat->s('recid', 'munkalap_feladat', "`tetel_id` = '".$akt_tetel['recid']."' AND `erteklista_id` = '{$erteklista_id}'", '', '1');
        if( $sql === false ) { $db_error = true; }
        if( $is_feladat ) {
            echo json_encode(array(
                'hiba'  => 'A(z) "'.erteklista(5, $erteklista_id).'" feladat már hozzáadásra került!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }

        // Feladat hozzáadása
        $sql = $adatbazis_kapcsolat -> i('munkalap_feladat',
                                            array('munkalap_id', 'tetel_id',          'erteklista_id',    'memo',   'insert_dt', 'insert_usr',                           'update_usr'                           ),
                                            array($recid,        $akt_tetel['recid'], $erteklista_id,     $memo,    'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'], $_SESSION[get_db_tabla().'teljes_nev'] )
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
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
        
    } else {
        echo json_encode(array(
            'hiba'  => 'Adja meg az elvégzett munkát!',
            'title' => 'Hiba',
        ));
    }

    $weblap->leallit();
    exit();
}

// Munkaidő hozzáadás
if( isset($_POST['munkaidoHozzaadas']) ) {

    $recid      = $_POST['recid'];
    $login_id   = $_POST['login_id'];
    $ora        = $_POST['ora'];
    $perc       = $_POST['perc'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    // Státusz ellenőrzése
    $munkalap = $sor = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id', 'munkalap', "`recid` = '{$recid}'", '', '1');
    if( $munkalap === false ) { $db_error = true; }

    // Kiszámoljuk a legutóbbi munkalap tételt, ehhez kerül hozzáadásra az új munkaidőráfordítás
    $akt_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id` = '{$recid}'", 'recid desc', '1');
    if( $akt_tetel === false ) { $db_error = true; }

    // Státusz ellenőrzése
    if( $munkalap['stat_id'] != MUNKALAP_STAT_FELVEVE && $munkalap['stat_id'] != MUNKALAP_STAT_LEZARASRA_VAR && $munkalap['stat_id'] != MUNKALAP_STAT_VIZSGALAT_ALATT ) {
        echo json_encode(array(
            'hiba'  => 'Csak felvett, kész vagy vizsgálat alatt státuszú munkalaphoz adható munkaidőráfordítás!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    // Jog ellenőrzése
    if(
        !(
            (($sor['stat_id']==MUNKALAP_STAT_FELVEVE || $sor['stat_id']==MUNKALAP_STAT_LEZARASRA_VAR) && get_kezeles_jog($sor['szemelyzet_id']) ) ||
            ($sor['stat_id']==MUNKALAP_STAT_VIZSGALAT_ALATT && _jog(JOG_MUNKALAP_VIZSGALAT) )
        )
    ) {
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga munkaidőt ráfodítani!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }
    
    // Van-e feladat az utolsó tételnél
    $utolso_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id`='{$recid}'", 'recid desc', '1');
    $feladat      = $adatbazis_kapcsolat->s2('recid', 'munkalap_feladat', "`tetel_id`='{$utolso_tetel['recid']}'", '', '1');
    if( !$feladat ){
        echo json_encode(array(
            'hiba'  => 'A munkalap utolsó tételénél nincs feladat, így nem lehet munkaidőt hozzáadni!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    // Név ellenőrzése
    if( !$login_id ) {
        echo json_encode(array(
            'hiba'  => 'Kérem válasszon nevet!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    // Ha a név már szerepel, hibaüzenet
    if( $adatbazis_kapcsolat->s('recid', 'munkalap_munkaido', "`login_id`='$login_id' AND `tetel_id`='{$akt_tetel['recid']}'") ) {
        $nev = $adatbazis_kapcsolat->s2('teljes_nev', 'login', "`recid`='$login_id'");
        echo json_encode(array(
            'hiba'  => $nev['teljes_nev'].' már szerepel a listában!',
            'title' => 'Hiba',
            'focus' => 'login_id_autocomp',
        ));
        $weblap->leallit();
        exit();
    }

    // Idő ellenőrzése
    if( !$ora && !$perc ) {
        echo json_encode(array(
            'hiba'  => 'Kérem adja meg a ráfordított munkaidőt!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    // Perc ellenőrzése
    if( $perc>59 ) {
        echo json_encode(array(
            'hiba'  => 'A perc nem lehet nagyobb mint 59!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    if( $akt_tetel ) {
        $sql = $adatbazis_kapcsolat -> i('munkalap_munkaido',
                                            array('munkalap_id', 'tetel_id',            'login_id',    'ora',    'perc',   'insert_dt', 'insert_usr',                           'update_usr'                           ),
                                            array($recid,        $akt_tetel['recid'],   $login_id,     $ora,     $perc,    'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'], $_SESSION[get_db_tabla().'teljes_nev'] )
                                        );
        if( $sql === false ) { $db_error = true; }
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'siker'
        ));
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba',
        ));
    }

    $weblap->leallit();
    exit();
}

// Munkalap átadása telepített csoportnak
if( isset($_POST['munkalapTelepitett']) ) {

    $recid    = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        $akt_statusz = $adatbazis_kapcsolat->s2('stat_id', 'munkalap', "`recid` = '{$recid}'", '', '1');
        if( $akt_statusz === false ) { $db_error = true; }

        // Státusz ellenőrzése
        if( $akt_statusz['stat_id'] != MUNKALAP_STAT_NYITOTT ) {
            echo json_encode(array(
                'hiba'  => 'Munkalap átadása telepítettnek, csak nyitott státuszban lehetséges!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzése
        if( !_jog(JOG_MUNKALAP_NYITOTT_KEZEL) ) {
            echo json_encode(array(
                'hiba'  => 'Nincs joga az átadáshoz!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if (isset($_POST['csak_kerdezunk'])) {

            echo json_encode(array(
                'hiba' => 'Biztosan telepített karbantartónak adja át a munkát?',
                'gomb' => '<input class="gomb zold" value="Igen" onclick="munkalapTelepitett();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        // Munkalap update
        $sql = $adatbazis_kapcsolat -> u('munkalap',
                                                    array('szemelyzet_id',          'visszaadva' ),
                                                    array(TELEPITETT_SZEMELYZET,    0            ),
                                                    "`recid` = '{$recid}'");
        if( $sql === false ) { $db_error = true; }

        // Státusz léptetés
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
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
    }

    $weblap->leallit();
    exit();
}


// Munkalap átadása területi felelősnek
if( isset($_POST['munkalapTeruletiVezeto']) ) {

    $recid    = $_POST['recid'];
    
    if( $recid ) {
        
        $db_error = false;
        mysqli_autocommit($db_kapcs, false);

        // Státusz ellenőrzése
        $munkalap = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id', 'munkalap', "`recid`='$recid'");
        if( $munkalap === false ) { $db_error = true; }

        if( $munkalap['stat_id'] != MUNKALAP_STAT_NYITOTT && $munkalap['stat_id'] != MUNKALAP_STAT_FELVEVE && $munkalap['stat_id'] != MUNKALAP_STAT_ELOKESZITES_ALATT ) {
            echo json_encode(array(
                'hiba'  => 'Ebben a státuszban nem adható át a munkalap!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzése
        if( !_jog(JOG_MUNKALAP_KULSOS_ELOKESZIT) || ($munkalap['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT && $munkalap['szemelyzet_id']!=$_SESSION[get_db_tabla().'szemelyzet_id']) ) {
            echo json_encode(array(
                'hiba'  => 'Nincs joga az átadáshoz!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }
        
        // Ha felvéve státuszban van, akkor azt is ellenőrizzük, hogy van-e feladat az utolsó tételéhez csatolva
        if($munkalap['stat_id'] == MUNKALAP_STAT_FELVEVE){
            $utolso_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id`='{$recid}'", 'recid desc', '1');
            $feladat      = $adatbazis_kapcsolat->s2('recid', 'munkalap_feladat', "`tetel_id`='{$utolso_tetel['recid']}'", '', '1');
            if(!$feladat){
                echo json_encode(array(
                    'hiba'  => 'Nem adható át a feladat, ha az utolsó tételhez még nincs feladat rendelve!',
                    'title' => 'Hiba',
                ));

                $weblap->leallit();
                exit();
            }
        }
        
        // Ha előkészítés státuszban van, akkor azt is ellenőrizzük, hogy Műszaki igazgazó-e
        if($munkalap['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT && $_SESSION[get_db_tabla().'szemelyzet_id']!=MSZK_VEZETO){
            echo json_encode(array(
                'hiba'  => 'Csak MSZK vezető adhat át munkalapot ebben a státuszban!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Munkalap update
        $sql = $adatbazis_kapcsolat -> u('munkalap', array('szemelyzet_id', 'visszaadva'), array(TERULETI_FELELOS, 0), "`recid` = '{$recid}'");
        if( $sql === false ) { $db_error = true; }

        // Státusz léptetés
        if($munkalap['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT){
            if( addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_ELOKESZITES_ALATT ) === false ) {
                $db_error = true;
            }
        }else{
            if( addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_FOLYAMATBAN ) === false ) {
                $db_error = true;
            }
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);
            echo json_encode(array(
                'statusz' => 'siker',
            ));
        } else {
            mysqli_rollback($db_kapcs);
            echo json_encode(array(
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }

    }

    $weblap->leallit();
    exit();
}


// Munkalap átadása műszaki vezetőnek
if( isset($_POST['munkalapMuszakiVezeto']) ) {

    $recid = $_POST['recid'];

    if( $recid ) {
        
        $db_error = false;
        mysqli_autocommit($db_kapcs, false);

        // Státusz ellenőrzése
        $munkalap = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id telephely_id', 'munkalap', "`recid`='$recid'");
        if( $munkalap === false ) { $db_error = true; }
        
        $telephely  = $adatbazis_kapcsolat -> s2('nev modell', 'telephely', "`id`='{$munkalap['telephely_id']}'");
        
        $engedelyezett_statuszok = array( MUNKALAP_STAT_NYITOTT, MUNKALAP_STAT_FOLYAMATBAN, MUNKALAP_STAT_FELVEVE, MUNKALAP_STAT_ELOKESZITES_ALATT );
        if( !in_array($munkalap['stat_id'], $engedelyezett_statuszok) ) {
            echo json_encode(array(
                'hiba'  => 'Ebben a státuszban nem adható át a munkalap!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzése
        if( 
            !( // Ha nem igaz a következő feltétel rendszer
                ( $munkalap['stat_id'] == MUNKALAP_STAT_NYITOTT             && _jog(JOG_MUNKALAP_NYITOTT_KEZEL)                                                          ) ||    // Nyitott státusz alatt nyitottak kezelése jog
                ( $munkalap['stat_id'] == MUNKALAP_STAT_FOLYAMATBAN         && $_SESSION[get_db_tabla().'szemelyzet_id']==TELEPITETT_SZEMELYZET && !$telephely['modell'] ) ||    // Folyamatban státusz alatt fontos, hogy telepített legyen a személy, egszállodás modell
                ( $munkalap['stat_id'] == MUNKALAP_STAT_FELVEVE             && get_kezeles_jog($munkalap['szemelyzet_id']) && !$telephely['modell']                      ) ||    // Felvéve státusz alatt fontos, hogy kezelés joga legyen és egyszállodás modell
                ( $munkalap['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT   && _jog(JOG_MUNKALAP_KULSOS_ELOKESZIT)                                                       )       // Előkészítés státusz alatt előkészítés jog
            )
            ){
            echo json_encode(array(
                'hiba'  => 'Nincs joga az átadáshoz!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }
        
        // Ha felvéve státuszban van, akkor azt is ellenőrizzük, hogy van-e feladat az utolsó tételéhez csatolva
        if($munkalap['stat_id'] == MUNKALAP_STAT_FELVEVE){
            $utolso_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id`='{$recid}'", 'recid desc', '1');
            $feladat      = $adatbazis_kapcsolat->s2('recid', 'munkalap_feladat', "`tetel_id`='{$utolso_tetel['recid']}'", '', '1');
            if(!$feladat){
                echo json_encode(array(
                    'hiba'  => 'Nem adható át a feladat, ha az utolsó tételhez még nincs feladat rendelve!',
                    'title' => 'Hiba',
                ));

                $weblap->leallit();
                exit();
            }
        }
        
        // Ha előkészítés státuszban van, akkor azt is ellenőrizzük, hogy Műszaki igazgazó-e
        if($munkalap['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT && $_SESSION[get_db_tabla().'szemelyzet_id']!=MSZK_VEZETO){
            echo json_encode(array(
                'hiba'  => 'Csak MSZK vezető adhat át munkalapot ebben a státuszban!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Munkalap update
        $sql = $adatbazis_kapcsolat -> u('munkalap', array('szemelyzet_id', 'visszaadva'), array(MUSZAKI_VEZETO, 0), "`recid` = '{$recid}'");
        if( $sql === false ) { $db_error = true; }

        // Státusz léptetés
        if($munkalap['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT){
            if( addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_ELOKESZITES_ALATT ) === false ) {
                $db_error = true;
            }
        }elseif($munkalap['stat_id'] == MUNKALAP_STAT_FOLYAMATBAN || $munkalap['stat_id'] == MUNKALAP_STAT_FELVEVE){
            if( addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_ELOKESZITES_ALATT ) === false ) {
                $db_error = true;
            }
        }else{
            if( addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_FOLYAMATBAN ) === false ) {
                $db_error = true;
            }
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);
            echo json_encode(array(
                'statusz' => 'siker',
            ));
        } else {
            mysqli_rollback($db_kapcs);
            echo json_encode(array(
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
    }

    $weblap->leallit();
    exit();
}


// Munkalap átadása műszaki vezető helyettesnek
if( isset($_POST['munkalapMuszakiVezetoHelyettes']) ) {

    $recid    = $_POST['recid'];

    if( $recid ) {
        
        $db_error = false;
        mysqli_autocommit($db_kapcs, false);

        // Státusz ellenőrzése
        $munkalap = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id', 'munkalap', "`recid`='$recid'");
        if( $munkalap === false ) { $db_error = true; }
        
        if( $munkalap['stat_id'] != MUNKALAP_STAT_NYITOTT && $munkalap['stat_id'] != MUNKALAP_STAT_FELVEVE && $munkalap['stat_id'] != MUNKALAP_STAT_ELOKESZITES_ALATT ) {
            echo json_encode(array(
                'hiba'  => 'Ebben a státuszban nem adható át a munkalap!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzése
        if( !_jog(JOG_MUNKALAP_KULSOS_ELOKESZIT) || ($munkalap['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT && $munkalap['szemelyzet_id']!=$_SESSION[get_db_tabla().'szemelyzet_id']) ) {
            echo json_encode(array(
                'hiba'  => 'Nincs joga az átadáshoz!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }
        
        // Ha felvéve státuszban van, akkor azt is ellenőrizzük, hogy van-e feladat az utolsó tételéhez csatolva
        if($munkalap['stat_id'] == MUNKALAP_STAT_FELVEVE){
            $utolso_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id`='{$recid}'", 'recid desc', '1');
            $feladat      = $adatbazis_kapcsolat->s2('recid', 'munkalap_feladat', "`tetel_id`='{$utolso_tetel['recid']}'", '', '1');
            if(!$feladat){
                echo json_encode(array(
                    'hiba'  => 'Nem adható át a feladat, ha az utolsó tételhez még nincs feladat rendelve!',
                    'title' => 'Hiba',
                ));

                $weblap->leallit();
                exit();
            }
        }

        // Munkalap update
        $sql = $adatbazis_kapcsolat -> u('munkalap', array('szemelyzet_id', 'visszaadva'), array(MUSZAKI_VEZETO_HELYETTES, 0), "`recid` = '{$recid}'");
        if( $sql === false ) { $db_error = true; }

        // Státusz léptetés
        if($munkalap['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT){
            if( addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_ELOKESZITES_ALATT ) === false ) {
                $db_error = true;
            }
        }else{
            if( addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_FOLYAMATBAN ) === false ) {
                $db_error = true;
            }
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);
            echo json_encode(array(
                'statusz' => 'siker',
            ));
        } else {
            mysqli_rollback($db_kapcs);
            echo json_encode(array(
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
    }

    $weblap->leallit();
    exit();
}


// Munkalap átadása műszaki ügyintézőnek
if( isset($_POST['munkalapAdminisztrator']) ) {

    $recid    = $_POST['recid'];

    if( $recid ) {
        
        $db_error = false;
        mysqli_autocommit($db_kapcs, false);

        // Státusz ellenőrzése
        $munkalap = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id', 'munkalap', "`recid`='$recid'");
        if( $munkalap === false ) { $db_error = true; }
        
        if( $munkalap['stat_id'] != MUNKALAP_STAT_NYITOTT && $munkalap['stat_id'] != MUNKALAP_STAT_FELVEVE && $munkalap['stat_id'] != MUNKALAP_STAT_ELOKESZITES_ALATT ) {
            echo json_encode(array(
                'hiba'  => 'Ebben a státuszban nem adható át a munkalap!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzése
        if( !_jog(JOG_MUNKALAP_KULSOS_ELOKESZIT) || ($munkalap['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT && $munkalap['szemelyzet_id']!=$_SESSION[get_db_tabla().'szemelyzet_id']) ) {
            echo json_encode(array(
                'hiba'  => 'Nincs joga az átadáshoz!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }
        
        // Ha előkészítés státuszban van, akkor azt is ellenőrizzük, hogy Műszaki igazgazó-e
        if($munkalap['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT && $_SESSION[get_db_tabla().'szemelyzet_id']!=MSZK_VEZETO){
            echo json_encode(array(
                'hiba'  => 'Csak MSZK vezető adhat át munkalapot ebben a státuszban!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Munkalap update
        $sql = $adatbazis_kapcsolat -> u('munkalap', array('szemelyzet_id', 'visszaadva'), array(MUSZAKI_ADMINISZTRATOR, 0), "`recid` = '{$recid}'");
        if( $sql === false ) { $db_error = true; }

        // Státusz léptetés
        if($munkalap['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT){
            if( addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_ELOKESZITES_ALATT ) === false ) {
                $db_error = true;
            }
        }else{
            if( addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_FOLYAMATBAN ) === false ) {
                $db_error = true;
            }
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);
            echo json_encode(array(
                'statusz' => 'siker',
            ));
        } else {
            mysqli_rollback($db_kapcs);
            echo json_encode(array(
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
    }

    $weblap->leallit();
    exit();
}


// Munkalap átadása diszpécser központnak
if( isset($_POST['munkalapDiszpKozp']) ) {

    $recid    = $_POST['recid'];

    if( $recid ) {
        
        $db_error = false;
        mysqli_autocommit($db_kapcs, false);

        // Státusz ellenőrzése
        $munkalap = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id', 'munkalap', "`recid`='$recid'");
        if( $munkalap === false ) { $db_error = true; }
        
        if( $munkalap['stat_id'] != MUNKALAP_STAT_NYITOTT && $munkalap['stat_id'] != MUNKALAP_STAT_FELVEVE && $munkalap['stat_id'] != MUNKALAP_STAT_ELOKESZITES_ALATT ) {
            echo json_encode(array(
                'hiba'  => 'Ebben a státuszban nem adható át a munkalap!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzése
        if( !_jog(JOG_MUNKALAP_KULSOS_ELOKESZIT) || ($munkalap['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT && $munkalap['szemelyzet_id']!=$_SESSION[get_db_tabla().'szemelyzet_id']) ) {
            echo json_encode(array(
                'hiba'  => 'Nincs joga az átadáshoz!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Munkalap update
        $sql = $adatbazis_kapcsolat -> u('munkalap',
                                                    array('szemelyzet_id', 'visszaadva'),
                                                    array(MSZK_VEZETO,     0           ),
                                                "`recid` = '{$recid}'");
        if( $sql === false ) { $db_error = true; }

        // Státusz léptetés
        if($munkalap['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT){
            if( addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_ELOKESZITES_ALATT ) === false ) {
                $db_error = true;
            }
        }else{
            if( addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_FOLYAMATBAN ) === false ) {
                $db_error = true;
            }
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);
            echo json_encode(array(
                'statusz' => 'siker',
            ));
        } else {
            mysqli_rollback($db_kapcs);
            echo json_encode(array(
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
    }

    $weblap->leallit();
    exit();
}


// Munkalap átadása Műszaki igazgatónak
if( isset($_POST['munkalapMuszakiIgazgato']) ) {

    $recid    = $_POST['recid'];

    if( $recid ) {
        
        $db_error = false;
        mysqli_autocommit($db_kapcs, false);

        // Státusz ellenőrzése
        $munkalap = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id', 'munkalap', "`recid`='$recid'");
        if( $munkalap === false ) { $db_error = true; }
        
        if( $munkalap['stat_id'] != MUNKALAP_STAT_NYITOTT && $munkalap['stat_id'] != MUNKALAP_STAT_FELVEVE && $munkalap['stat_id'] != MUNKALAP_STAT_ELOKESZITES_ALATT ) {
            echo json_encode(array(
                'hiba'  => 'Ebben a státuszban nem adható át a munkalap!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzése
        if( !_jog(JOG_MUNKALAP_KULSOS_ELOKESZIT) || ($munkalap['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT && $munkalap['szemelyzet_id']!=$_SESSION[get_db_tabla().'szemelyzet_id']) ) {
            echo json_encode(array(
                'hiba'  => 'Nincs joga az átadáshoz!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }
        
        // Ha felvéve státuszban van, akkor azt is ellenőrizzük, hogy van-e feladat az utolsó tételéhez csatolva
        if($munkalap['stat_id'] == MUNKALAP_STAT_FELVEVE){
            $utolso_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id`='{$recid}'", 'recid desc', '1');
            $feladat      = $adatbazis_kapcsolat->s2('recid', 'munkalap_feladat', "`tetel_id`='{$utolso_tetel['recid']}'", '', '1');
            if(!$feladat){
                echo json_encode(array(
                    'hiba'  => 'Nem adható át a feladat, ha az utolsó tételhez még nincs feladat rendelve!',
                    'title' => 'Hiba',
                ));

                $weblap->leallit();
                exit();
            }
        }

        // Munkalap update
        $sql = $adatbazis_kapcsolat -> u('munkalap', array('szemelyzet_id', 'visszaadva'), array(MUSZAKI_IGAZGATO, 0), "`recid` = '{$recid}'");
        if( $sql === false ) { $db_error = true; }

        // Státusz léptetés
        if($munkalap['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT){
            if( addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_ELOKESZITES_ALATT ) === false ) {
                $db_error = true;
            }
        }else{
            if( addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_FOLYAMATBAN ) === false ) {
                $db_error = true;
            }
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);
            echo json_encode(array(
                'statusz' => 'siker',
            ));
        } else {
            mysqli_rollback($db_kapcs);
            echo json_encode(array(
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
    }

    $weblap->leallit();
    exit();
}

// Határidőre ütemezés
if (isset($_POST['munkalapHIUtemez'])) {
    
    $recid = $_POST['recid'];
    $datum = str_replace('.', '-', $_POST['datum']);
    
    if($recid){
    
        $db_error = false;
        mysqli_autocommit($db_kapcs, false);
        
        // Dátum nem lehet korábbi mint a jelen
        if($_POST['datum']<=date('Y.m.d')){
            echo json_encode(array(
                'hiba'  => 'Csak jövőbeli dátumra ütemezhető egy munkalap!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }
        
        $megj = 'Dátumra ütemezve: '.$_POST['datum'];
        if( addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_VARAKOZO, '', $megj ) === false ) {
            $db_error = true;
        }
        
        // És egy megjegyzést is
        addMegj('munkalap', $recid, $megj);

        // Sőt a munkalap megjegyzésébe is beleírjuk, illetve a dátumot frissítjük
        $munkalap = $adatbazis_kapcsolat->s2('text', 'munkalap', "`recid`='{$recid}'");
        if( $munkalap === false ) { $db_error = true; }
        $sql = $adatbazis_kapcsolat->u('munkalap',  array('esnap_d', 'text'                              ),
                                                    array($datum,    trim($megj."\r\n".$munkalap['text'])),
                                                    "`recid`='{$recid}'");
        if( $sql === false ) { $db_error = true; }
        
        // Ha van bejelentése, akkor azt várakozóra kell tenni
        $bejelentesek = $adatbazis_kapcsolat->s('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$recid}'");
        if( $bejelentesek === false ) { $db_error = true; }
        foreach($bejelentesek as $bej){
            if( addStatusz( 'bejelentes_statusz', $bej['bejelentes_id'], BEJELENTES_STAT_VARAKOZO ) === false ) {
                $db_error = true;
            }
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);
            echo json_encode(array(
                'statusz' => 'siker',
            ));
        } else {
            mysqli_rollback($db_kapcs);
            echo json_encode(array(
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
        
    }
    
    $weblap->leallit();
    exit();
}

// Határidőre ütemezésből vissza nyitottba
if (isset($_POST['munkalapVisszaNyitott'])) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $recid = $_POST['recid'];
    $statusz = addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_NYITOTT);
    if( $statusz === false ) { $db_error = true; }

    // Munkalaphoz tartozó Bejelentések státuszát pedig folyamatbanra léptetjük
    $bejelentesek = $adatbazis_kapcsolat->s('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$recid}'");
    foreach($bejelentesek as $bej){

        // Adott bejelentésenk van-e több munkalapja?
        $munkalapok = $adatbazis_kapcsolat->s('munkalap_id', 'munkalap_kapcs', "`bejelentes_id`='{$bej['bejelentes_id']}' AND `munkalap_id`!='{$recid}'");
        if( $munkalapok === false ) { $db_error = true; }
        $munkalapok = adatbazis2tomb($munkalapok, 'munkalap_id');

        $hataridore_utemezett = '';
        if($munkalapok){
            $hataridore_utemezett = $adatbazis_kapcsolat->s('recid', 'munkalap', "`stat_id`='".MUNKALAP_STAT_VARAKOZO."' AND `recid` IN (".implode(',', $munkalapok).")");
            if( $hataridore_utemezett === false ) { $db_error = true; }
        }

        // Ha nincs több határidőre ütemezett munkalapja, akkor Folyamatban státuszt kap
        if(!$hataridore_utemezett){
            $statusz = addStatusz( 'bejelentes_statusz', $bej['bejelentes_id'], BEJELENTES_STAT_FOLYAMATBAN );
            if( $statusz === false ) { $db_error = true; }
        }

    }
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'siker',
        ));
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba',
        ));
    }
    
    $weblap->leallit();
    exit();
}

// Hataáridőre ütemezésből vissza nyitottba
if (isset($_POST['munkalapVisszavetel'])) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $recid = $_POST['recid'];
    
    $munkalap = $adatbazis_kapcsolat->s2('stat_id', 'munkalap', "`recid` = '{$recid}'");
    if( $munkalap === false ) { $db_error = true; }
    
    // Jog ellenőrzése
    if( !_jog(JOG_MUNKALAP_NYITOTT_KEZEL) ){
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga a munkalapot visszavenni!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }
    
    // Státusz ellenőrzése
    if($munkalap['stat_id']!=MUNKALAP_STAT_FOLYAMATBAN){
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => 'A munkalap nincs folyamatban státuszban, így már nem lehet visszavenni!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }
    
    // Státusz léptetés
    $statusz = addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_NYITOTT);
    if( $statusz === false ) { $db_error = true; }
    
    // Munkalap update
    $adatbazis_kapcsolat->u('munkalap', array('szallito_id', 'szemelyzet_id', 'mobil'),
                                        array('',            '',              ''     ),
                                    "`recid` = '{$recid}'");
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'siker',
        ));
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba',
        ));
    }
    
    $weblap->leallit();
    exit();
}

// CSV generálása
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
require('selectek.php');

    if( !$_GET['ajax'] ) {
        echo '<div id="szamlaHidden" class="dn"></div><div id="szamlaBetolt" data-recid="'.$_GET['id'].'" data-page="'.str_replace('.php', '', basename(self())).'">';
    }

    // Megjegyzések darabszáma
    $megj_db = '';
    $megj_sorok = $adatbazis_kapcsolat->s('recid', 'megjegyzes', "`tabla` = '".$megnevezes."' AND `id` = '".$_GET['id']."'", '', '');
    if( $megj_sorok ) {
        $megj_db = ' ('.count($megj_sorok).')';
    }

    // Dokumentumok darabszáma
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
        // Fülek
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'tortenet'      => 'Történet',
            'tetel'         => 'Tételek',
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

            $fulek .= '<a href="javascript: szamlaBetolt(\''.$_GET['id'].'\', \''.basename(self()).'\', \''.$key.'\'); void(0);"'.$active.' data-oldal="'.$key.'">'.$value.'</a>';
        }

        $szamla_html .= str_replace(
            array('{FULEK}'),
            array( $fulek  ),
            fajlbol_olvas('../sablon/admin/szamla_fulek.html', true)
        );
    }
    
    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");
    // Hibaüzeneteknek készítünk két hidden inputot, ami midnig elérhető lesz
    $szamla_html .= '<div class="dn" id="ikt_szam_hibauzibe">'.$sor['ikt_szam'].'</div>';
    $szamla_html .= '<div class="dn" id="telephely_hibauzibe">'. getErtek('nev', 'telephely', 'id', $sor['telephely_id']).'</div>';
                
    // Gombok kirajzolása minden oldalra
    $gombok = '';
    if( isset($_GET['uj']) ) {

        $disabled = '';
        $class = '';
        if( !$_GET['ujkent'] ) {
            $disabled = ' disabled';
        } else {
            $class = ' gombb_alert';
        }

        // Gombok
        $gombok = nl2('
            <div class="form_kuldes">
                <input type="button" class="gombb'.$class.' mentes_gomb" name="mehet_gomb" id="mehet_gomb"'.$disabled.' value="Mentés" onclick="$(\'#szerk_form\').submit()">
                <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="formModositva=false; history.back();">
            </div>
        ');

        $muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 16).'px);"';
        if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
            if( $_COOKIE['csuszkaSzelesseg'] ) {
                $muv_style = ' style="width: calc(100% - '.(($_COOKIE['csuszkaSzelesseg'] + 16)/10).'rem);"';
            }
        }

        // Műveletek box
        $gombok = nl2('
            <div class="muveletek"'.$muv_style.'>
                '.$gombok.'
            </div>
        ');

    // Módosítás
    } else {

        require_once 'selectek.php';

        $telephely  = $adatbazis_kapcsolat -> s2('nev modell', 'telephely', "`id`='{$sor['telephely_id']}'");

        // Kezelés jog
        $kezeles_jog = get_kezeles_jog($sor['szemelyzet_id']);
        
        // Utolsó feladat van-e?
        $utolso_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id`='{$_GET['id']}'", 'recid desc', '1');
        $feladat      = $adatbazis_kapcsolat->s2('recid', 'munkalap_feladat', "`tetel_id`='{$utolso_tetel['recid']}'", '', '1');

        // Átadás gombok rejtése, ha azonos a saját szerepkörrel
        $szemelyzet_id_rejt = array();
        foreach($select_szemelyzet as $szemelyzet){
            if($_SESSION[get_db_tabla().'szemelyzet_id'] == $szemelyzet['value']){
                $szemelyzet_id_rejt[$szemelyzet['value']] = ' class="dn"';
            }
        }
        
        // Mobilcsoportnak átadás rejtése, ha egyszállodások vagyunk
        if(!$telephely['modell']) {
            $szemelyzet_id_rejt[MOBILCSOPORT] = ' class="dn"';
        }

        // Felvéve státusz alatt mindenki csak a felettesének adhatja át, tehát itt tovább szűkítünk
        /* Felettesre szűrés mégsem kell
        if($sor['stat_id']==MUNKALAP_STAT_FELVEVE){
            switch ($_SESSION[get_db_tabla().'szemelyzet_id']){
                case MOBILCSOPORT               : ; break; // Ő átadhatja, neki mindenki felettes
                case TELEPITETT_SZEMELYZET      : ; break; // Ő átadhatja, neki mindenki felettes
                case KULSO_KIVITELEZO           : ; break; // Ő átadhatja, neki mindenki felettes
                case MUSZAKI_VEZETO_HELYETTES   : ; break; // Nála csak saját magát kellene rejteni, de önmaga eleve rejteve van
                case MUSZAKI_VEZETO             : $szemelyzet_id_rejt[MUSZAKI_VEZETO_HELYETTES] = ' class="dn"'; break; // Hierarchiában latta levő személyzettípusok rejtése
                case TERULETI_FELELOS           : $szemelyzet_id_rejt[MUSZAKI_VEZETO_HELYETTES] = ' class="dn"'; $szemelyzet_id_rejt[MUSZAKI_VEZETO] = ' class="dn"'; break; // Hierarchiában latta levő személyzettípusok rejtése
                case MUSZAKI_ADMINISZTRATOR     : $szemelyzet_id_rejt[MUSZAKI_VEZETO_HELYETTES] = ' class="dn"'; $szemelyzet_id_rejt[MUSZAKI_VEZETO] = ' class="dn"'; $szemelyzet_id_rejt[TERULETI_FELELOS] = ' class="dn"'; break; // Hierarchiában latta levő személyzettípusok rejtése
                case MSZK_VEZETO                : $szemelyzet_id_rejt[MUSZAKI_VEZETO_HELYETTES] = ' class="dn"'; $szemelyzet_id_rejt[MUSZAKI_VEZETO] = ' class="dn"'; $szemelyzet_id_rejt[TERULETI_FELELOS] = ' class="dn"'; $szemelyzet_id_rejt[MUSZAKI_ADMINISZTRATOR] = ' class="dn"'; break; // Hierarchiában latta levő személyzettípusok rejtése
                case MUSZAKI_IGAZGATO           : $szemelyzet_id_rejt[MUSZAKI_VEZETO_HELYETTES] = ' class="dn"'; $szemelyzet_id_rejt[MUSZAKI_VEZETO] = ' class="dn"'; $szemelyzet_id_rejt[TERULETI_FELELOS] = ' class="dn"'; $szemelyzet_id_rejt[MUSZAKI_ADMINISZTRATOR] = ' class="dn"'; $szemelyzet_id_rejt[MSZK_VEZETO] = ' class="dn"'; break; // Hierarchiában latta levő személyzettípusok rejtése
            }
        }*/

        //Js, a törlés gombhoz
	    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';
	    if($elonezet){
           $gombok .= ' <input type="button" class="gombb" onclick="window.open(\''.$elonezet.'\')" value="Előnézet">';
        }

        // Csak az törölhet, akinek van ilyen joga
        if(!_jog(JOG_MUNKALAP_TOROL)){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Nincs jogosultsága munkalap törléséhez!\', \'\', \'Hiba\')" value="Töröl">';
        }else{
            // Egyébként nézzük meg, hogy van-e tétel vagy bejelentés rajta. Ha van, akkor sem törölhető
            if($adatbazis_kapcsolat->s('recid', 'munkalap_tetel', "`munkalap_id`='{$_GET['id']}'")){
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Munkalapszám: '.$sor['ikt_szam'].'<br>Szálloda: '.getErtek('nev', 'telephely', 'id', $sor['telephely_id']).'<br>Ennek a munkalapnak már vannak tételei, ezért nem törölhető!\', \'\', \'Hiba\')" value="Töröl">';
            }elseif($adatbazis_kapcsolat->s('recid ', 'munkalap_kapcs', "`munkalap_id` = '{$_GET['id']}'")){
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Munkalap törlése előtt vegye le róla a bejelentéseket!\', \'\', \'Hiba\')" value="Töröl">';
            }else{
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl">';
            }

            // Munkalap adatlapja csak akkor szerkeszthető, ha a státusz nyitott vagy folyamatban
            if( isset($_GET['modosit']) && in_array($sor['stat_id'], array(MUNKALAP_STAT_NYITOTT, MUNKALAP_STAT_FOLYAMATBAN)) ) {
                $gombok = nl2('
                    <div class="form_kuldes">
                        <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="$(\'#szerk_form\').submit()">
                        <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="formModositva=false; location.reload();">
                    </div>
                ').$gombok;
            }
        }


        // Munkalap adatlapja csak akkor szerkeszthető, ha a státusz nyitott és van nyitott kezelés jog
        if( isset($_GET['modosit']) && $sor['stat_id']==MUNKALAP_STAT_NYITOTT && _jog(JOG_MUNKALAP_NYITOTT_KEZEL) ) {
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="$(\'#szerk_form\').submit()">
                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="formModositva=false; location.reload();">
                </div>
            ').$gombok;
        }


        // Kijelöltekkel való művelet gombok
        if( ($sor['stat_id']==MUNKALAP_STAT_NYITOTT) && _jog(JOG_MUNKALAP_VARAKOZTAT) ) {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Határidőre ütemezés" onclick="csoportMuvelet(\'munkalapHIUtemez\');"> ';
//            $gombok .= nl2('
//                <div id="kmCont">
//                    <script>
//                        csoportKiveszGomb = "<p class=\"c\"><input class=\"gomb zold\" type=\"button\" value=\"Igen\" onclick=\"csoportbolTorol(\'csoport_id\');\"><input class=\"gomb piros\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
//                    </script>
//                    <div class="kijeloltek_muv">
//                        <input type="button" class="gomb foGomb" value="Várakoztatás...">
//                        <div class="muv">
//                            <ul>
//                                <li><a onclick="csoportMuvelet(\'csoporthoz_adas\');">Csoporthoz adás</a></li>
//                                <li><a onclick="if(balnav_kijelol_ell(\'egy csoportot szabad megbontani\')){ alert2(\'Biztos kiveszi a kijelölt munkalapokat a megadott csoportokból?\', csoportKiveszGomb); }">Csoportból kivétel</a></li>
//                                <li><a onclick="csa_alert();">Csoport átnevezése</a></li>
//                            </ul>
//                        </div>
//                    </div>
//                </div>
//            ');
        }
        
        // Ha határidőre ütemezett a munkalap
        if( ($sor['stat_id']==MUNKALAP_STAT_VARAKOZO) && _jog(JOG_MUNKALAP_VARAKOZTAT) ) {
            $gombok .= '<input type="button" class="gomb" value="Vissza a nyitottak közé" onclick="munkalapVisszaNyitott(\''.$_GET['id'].'\')"> ';
        }

        // Visszavétel (Folyamatban státusz, user személyzettípusa diszpécser.
        if( $sor['stat_id'] == MUNKALAP_STAT_FOLYAMATBAN && _jog(JOG_MUNKALAP_NYITOTT_KEZEL) ) {
            $gombok .= '<input type="button" class="gomb" value="Visszavétel" onclick="munkalapVisszavetel(\''.$_GET['id'].'\')"> ';
        }
        
        // Munka felvétele folyamatban státuszhoz és kezelés joghoz van kötve
        $felvehetik = array(TELEPITETT_SZEMELYZET, MOBILCSOPORT);
        if( $sor['stat_id'] == MUNKALAP_STAT_FOLYAMATBAN && in_array($sor['szemelyzet_id'], $felvehetik) && $kezeles_jog ) {
            $gombok .= nl2('
                <script>
                    var munkalapFelveszKerdes = \'
                        <div class="l">
                            Iktatószám: '.$sor['ikt_szam'].'
                            <br>Szálloda: '.addslashes($telephely['nev']).'
                        </div>
                        <br>Felveszi ezt a munkalapot?\';
                    munkalapFelvetelGomb = "<p class=\"c\"><input class=\"gomb zold\" type=\"button\" value=\"Igen\" onclick=\"munkalapFelvesz(\''.$_GET['id'].'\');\"><input class=\"gomb piros\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
                </script>
                <input type="button" class="gomb" value="Munka felvétele" onclick="alert2(munkalapFelveszKerdes, munkalapFelvetelGomb);">
            ');
        }

        // Felvéve státusz + kezelés jog
        $felvehetik = array(TELEPITETT_SZEMELYZET, MOBILCSOPORT);
        if(
                ( $sor['stat_id']==MUNKALAP_STAT_FOLYAMATBAN && $sor['szemelyzet_id']==KULSO_KIVITELEZO && ( _jog(JOG_MUNKALAP_KEZEL_KULSO) || ( _jog(JOG_MUNKALAP_SAJAT_KEZEL) && $_SESSION[get_db_tabla().'szemelyzet_id']==KULSO_KIVITELEZO ) ) ) ||
                ( $sor['stat_id']==MUNKALAP_STAT_FELVEVE && in_array($sor['szemelyzet_id'], $felvehetik) && $kezeles_jog )
           ){
            $gombok .= '<input type="button" class="gomb" value="Készre jelentés" onclick="munkalapKeszre(\'csak kérdezünk még\');"> ';
        }

        // Felvéve, kész, vizsgálat státusz + kezelés jog (vizsgálat státusznál vízsgálat jog) bármelyike alatti gombok
        $felvehetik = array(TELEPITETT_SZEMELYZET, MOBILCSOPORT);
        if(
            ( $sor['stat_id']==MUNKALAP_STAT_FOLYAMATBAN     && $sor['szemelyzet_id']==KULSO_KIVITELEZO && ( _jog(JOG_MUNKALAP_KEZEL_KULSO) || ( _jog(JOG_MUNKALAP_SAJAT_KEZEL) && $_SESSION[get_db_tabla().'szemelyzet_id']==KULSO_KIVITELEZO ) ) ) ||
            ( $sor['stat_id']==MUNKALAP_STAT_FELVEVE         && in_array($sor['szemelyzet_id'], $felvehetik) && $kezeles_jog )  ||
            ( $sor['stat_id']==MUNKALAP_STAT_VIZSGALAT_ALATT && _jog(JOG_MUNKALAP_VIZSGALAT) ) ||
            ( $sor['stat_id']==MUNKALAP_STAT_LEZARASRA_VAR   && $kezeles_jog )
            ) {
            $gombok .= nl2('<input type="button" class="gomb" value="Elvégzett feladat hozzáadása" onclick="csoportMuvelet(\'feladatHozzaad\')"> ');
        }

        // Felvéve, kész, vizsgálat státusz + kezelés jog (vizsgálat státusznál vízsgálat jog) bármelyike alatti gombok
        $felvehetik = array(TELEPITETT_SZEMELYZET, MOBILCSOPORT);
        if(
            ( $sor['stat_id']==MUNKALAP_STAT_FELVEVE         && in_array($sor['szemelyzet_id'], $felvehetik) && $kezeles_jog ) ||
            ( $sor['stat_id']==MUNKALAP_STAT_LEZARASRA_VAR   && in_array($sor['szemelyzet_id'], $felvehetik) && $kezeles_jog ) ||
            ( $sor['stat_id']==MUNKALAP_STAT_VIZSGALAT_ALATT && _jog(JOG_MUNKALAP_VIZSGALAT) )
            ) {
            $gombok .= nl2('<input type="button" class="gomb" value="Anyag felhasználás" onclick="csoportMuvelet(\'munkalapAnyagvetelezes\');"> ');
        }

        // Felvéve státusz + kezelés jog
        if( $sor['stat_id']==MUNKALAP_STAT_FELVEVE && $kezeles_jog ){
            $gombok .= nl2('<input type="button" class="gomb" value="Anyagigénylés szükséges" onclick="csoportMuvelet(\'munkalapAnyagigenyles\');"> ');
        }

        // Felvéve státusz + munkaidő hozzáad jog
        if(
            (($sor['stat_id']==MUNKALAP_STAT_FELVEVE || $sor['stat_id']==MUNKALAP_STAT_LEZARASRA_VAR) && $kezeles_jog ) ||
            ($sor['stat_id']==MUNKALAP_STAT_VIZSGALAT_ALATT && _jog(JOG_MUNKALAP_VIZSGALAT) )
            ){
            $gombok .= '<input type="button" class="gomb" value="Munkaidő hozzáad" onclick="csoportMuvelet(\'munkaidoHozzaad\');"> ';
        }

        // Előkészítés státusz és külsős előkészítés jog kell hozzá
        if( $sor['stat_id']==MUNKALAP_STAT_ELOKESZITES_ALATT && _jog(JOG_MUNKALAP_KULSOS_ELOKESZIT) && $_SESSION[get_db_tabla().'szemelyzet_id']==$sor['szemelyzet_id'] ){
            $gombok .= '<input type="button" class="gomb foGomb" value="Javaslattétel" onclick="csoportMuvelet(\'kulsokivHozzarend\');"> ';
        }

        // Külső kivitelező hozzáadása
        if($sor['stat_id']==MUNKALAP_STAT_ELOKESZITES_ALATT && $_SESSION[get_db_tabla().'szemelyzet_id']==MUSZAKI_VEZETO && $sor['szemelyzet_id']==MUSZAKI_VEZETO && !$telephely['modell'] /*Egyszállodás*/){
            $gombok .= '<input type="button" class="gomb foGomb" value="Külső kivitelező hozzárendelése" onclick="csoportMuvelet(\'kulsokivHozzarend2\');"> ';
        }

        // Nyitott státusz + Külsős előkészít jog
        if($sor['stat_id']==MUNKALAP_STAT_NYITOTT && _jog(JOG_MUNKALAP_NYITOTT_KEZEL)){

            $gombok .= nl2('
                <div class="kijeloltek_muv">
                    <input type="button" class="gomb foGomb modositaskor_rejt" value="Hozzárendelés...">
                    <div class="muv">
                        <ul>
                            <li'.$szemelyzet_id_rejt[TELEPITETT_SZEMELYZET].'><a onclick="munkalapTelepitett(\'csak kérdezünk még\');">Telepítettnek</a></li>
                            <li'.$szemelyzet_id_rejt[MOBILCSOPORT]/* Egyszállodánál is rejtve */./* Korábbi onclick ez volt:  onclick="csoportMuvelet(\'munkalap_mobil\'); */'>
                                <script> gomb_mobil = \'<input type="button" class="gomb zold" onclick="munkalapMobil_sima()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\';</script>
                                <a onclick="alert2(\'Biztosan átadja mobilnak?\', gomb_mobil)">Mobilnak</a>
                            </li>
                            <li'.$szemelyzet_id_rejt[KULSO_KIVITELEZO].'><a onclick="csoportMuvelet(\'munkalap_kulso\');">Külsőnek</a></li>
                        </ul>
                    </div>
                </div>
            ');
                
                // Korábban ebből volt még jópár:
//                <li'.$szemelyzet_id_rejt[TELEPITETT_SZEMELYZET].'><a onclick="munkalapTelepitett(\'csak kérdezünk még\');">Telepítettnek</a></li>
//                <li'.$szemelyzet_id_rejt[MOBILCSOPORT]./* Korábbi onclick ez volt:  onclick="csoportMuvelet(\'munkalap_mobil\'); */'>
//                    <script> gomb_mobil = \'<input type="button" class="gomb zold" onclick="munkalapMobil_sima()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\';</script>
//                    <a onclick="alert2(\'Biztosan átadja mobilnak?\', gomb_mobil)">Mobilnak</a>
//                </li>
//                <li'.$szemelyzet_id_rejt[KULSO_KIVITELEZO].'><a onclick="csoportMuvelet(\'munkalap_kulso\');">Külsőnek</a></li>
//                <li'.$szemelyzet_id_rejt[MUSZAKI_VEZETO_HELYETTES].'>
//                    <script> gomb_muszh = \'<input type="button" class="gomb zold" onclick="munkalapMuszakiVezetoHelyettes()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\';</script>
//                    <a onclick="alert2(\'Biztosan átadja a műszaki vezető helyettesnek?\', gomb_muszh)">Műszaki vezető helyettesnek</a>
//                </li>
//                <li'.$szemelyzet_id_rejt[MUSZAKI_VEZETO].'>
//                    <script> gomb_musz = \'<input type="button" class="gomb zold" onclick="munkalapMuszakiVezeto()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\'; </script>
//                    <a onclick="alert2(\'Biztosan átadja a műszaki vezetőnek?\', gomb_musz)">Műszaki vezetőnek</a>
//                </li>
//                <li'.$szemelyzet_id_rejt[TERULETI_FELELOS].'>
//                    <script> gomb_ter = \'<input type="button" class="gomb zold" onclick="munkalapTeruletiVezeto()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\'; </script>
//                    <a onclick="alert2(\'Biztosan átadja a területi felelősnek?\', gomb_ter)">Területi felelősnek</a>
//                </li>
//                <li'.$szemelyzet_id_rejt[MUSZAKI_ADMINISZTRATOR].'>
//                    <script> gomb_muszu = \'<input type="button" class="gomb zold" onclick="munkalapAdminisztrator()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\';</script>
//                    <a onclick="alert2(\'Biztosan átadja a műszaki ügyintézőnek?\', gomb_muszu)">Műszaki ügyintézőnek</a>
//                </li>
//                <li'.$szemelyzet_id_rejt[MSZK_VEZETO].'>
//                    <script> gomb_mszk = \'<input type="button" class="gomb zold" onclick="munkalapDiszpKozp()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\';</script>
//                    <a onclick="alert2(\'Biztosan átadja a MSZK vezetőnek?\', gomb_mszk)">MSZK vezetőnek</a>
//                </li>
//                <li'.$szemelyzet_id_rejt[MUSZAKI_IGAZGATO].'>
//                    <script> gomb_muszaki_ig = \'<input type="button" class="gomb zold" onclick="munkalapMuszakiIgazgato()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\';</script>
//                    <a onclick="alert2(\'Biztosan átadja a Műszaki igazgatónak?\', gomb_muszaki_ig)">Műszaki igazgatónak</a>
//                </li>

        }

        // Felvéve státusz + külsős előkészít jog + Egyszállodás modell
        if($sor['stat_id']==MUNKALAP_STAT_FELVEVE && !$telephely['modell'] /*Egyszállodás modell*/ && $kezeles_jog ){

            $alert_musz_vez         = 'alert2(\'Biztosan átadja a Műszaki vezetőnek?\', gomb_musz)';
            $alert_musz_vez_helyett = 'alert2(\'Biztosan átadja a Műszaki vezető helyettesnek?\', gomb_muszh)';
            $alert_ter              = 'alert2(\'Biztosan átadja a Területi felelősnek?\', gomb_ter)';
            $alert_mszk             = 'alert2(\'Biztosan átadja a MSZK vezetőnek?\', gomb_mszk)';
            $alert_muszaki_ig       = 'alert2(\'Biztosan átadja a Műszaki igazgatónak?\', gomb_muszaki_ig)';
            // Ha nincs egyetlen elvégzett feladat sem, akkor nem lehet majd átadni, más hibaüzenet jön majd fel
            $utolso_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id`='{$_GET['id']}'", 'recid desc', '1');
            $feladat      = $adatbazis_kapcsolat->s2('recid', 'munkalap_feladat', "`tetel_id`='{$utolso_tetel['recid']}'", '', '1');
            if(!$feladat){
                $alert_musz_vez_helyett =
                $alert_musz_vez         =
                $alert_ter              =
                $alert_mszk             =
                $alert_muszaki_ig       =
                'alert2(\'Átadáshoz adjon hozzá legalább egy elvégzett feladatot!\')';
            }

            $gombok .= nl2('
                <div class="kijeloltek_muv">
                    <input type="button" class="gomb foGomb modositaskor_rejt" value="Átadás...">
                    <div class="muv">
                        <ul>
                            <li'.$szemelyzet_id_rejt[MUSZAKI_VEZETO].'>
                                <script> gomb_musz = \'<input type="button" class="gomb zold" onclick="munkalapMuszakiVezeto()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\'; </script>
                                <a onclick="'.$alert_musz_vez.'">Műszaki vezetőnek</a>
                            </li>
                        </ul>
                    </div>
                </div>
            ');

        }

        // Folyamatban státusz
        if($sor['stat_id']==MUNKALAP_STAT_FOLYAMATBAN && $_SESSION[get_db_tabla().'szemelyzet_id']==TELEPITETT_SZEMELYZET && !$telephely['modell']/*Egyszálodás modell*/ ){

            $gombok .= nl2('
                <div class="kijeloltek_muv">
                    <input type="button" class="gomb foGomb" value="Átadás...">
                    <div class="muv">
                        <ul>
                            <li'.$szemelyzet_id_rejt[MUSZAKI_VEZETO].'>
                                <script> gomb_musz = \'<input type="button" class="gomb zold" onclick="munkalapMuszakiVezeto()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\'; </script>
                                <a onclick="alert2(\'Biztosan átadja a Műszaki vezetőnek?\', gomb_musz)">Műszaki vezetőnek</a>
                            </li>
                        </ul>
                    </div>
                </div>
            ');

        }

        // Előkészítés státusz  + Külsős előkészít jog
        $engedelyezett_szemelyzettipusok = array(MUSZAKI_VEZETO, TERULETI_FELELOS, MUSZAKI_ADMINISZTRATOR, MSZK_VEZETO);
        if($sor['stat_id']==MUNKALAP_STAT_ELOKESZITES_ALATT && _jog(JOG_MUNKALAP_KULSOS_ELOKESZIT) && $sor['szemelyzet_id']==$_SESSION[get_db_tabla().'szemelyzet_id'] && in_array($_SESSION[get_db_tabla().'szemelyzet_id'], $engedelyezett_szemelyzettipusok) ){

            // Listaelemek elrejtése annak megfelelően, hogy milyen személyzettípussal vagyunk belépve
            switch ($_SESSION[get_db_tabla().'szemelyzet_id']){
                case MUSZAKI_VEZETO: 
                    $szemelyzet_id_rejt[TERULETI_FELELOS]       = ' class="dn"';
                    $szemelyzet_id_rejt[MUSZAKI_ADMINISZTRATOR] = ' class="dn"';
                break;
                case TERULETI_FELELOS: 
                    $szemelyzet_id_rejt[MUSZAKI_VEZETO]         = ' class="dn"';
                    $szemelyzet_id_rejt[MUSZAKI_ADMINISZTRATOR] = ' class="dn"';
                break;
                case MUSZAKI_ADMINISZTRATOR: 
                    $szemelyzet_id_rejt[MUSZAKI_VEZETO]         = ' class="dn"';
                    $szemelyzet_id_rejt[TERULETI_FELELOS]       = ' class="dn"';
                break;
            }
            
            $gombok .= nl2('
                <div class="kijeloltek_muv">
                    <input type="button" class="gomb foGomb" value="Átadás...">
                    <div class="muv">
                        <ul>
                            <li'.$szemelyzet_id_rejt[MUSZAKI_VEZETO].'>
                                <script> gomb_musz = \'<input type="button" class="gomb zold" onclick="munkalapMuszakiVezeto()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\'; </script>
                                <a onclick="alert2(\'Biztosan átadja a Műszaki vezetőnek?\', gomb_musz)">Műszaki vezetőnek</a>
                            </li>
                            <li'.$szemelyzet_id_rejt[TERULETI_FELELOS].'>
                                <script> gomb_ter = \'<input type="button" class="gomb zold" onclick="munkalapTeruletiVezeto()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\'; </script>
                                <a onclick="alert2(\'Biztosan átadja a Területi felelősnek?\', gomb_ter)">Területi felelősnek</a>
                            </li>
                            <li'.$szemelyzet_id_rejt[MUSZAKI_ADMINISZTRATOR].'>
                                <script> gomb_musz_ugy = \'<input type="button" class="gomb zold" onclick="munkalapAdminisztrator()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\'; </script>
                                <a onclick="alert2(\'Biztosan átadja a Műszaki ügyintézőnek?\', gomb_musz_ugy)">Műszaki ügyintézőnek</a>
                            </li>
                            <li'.$szemelyzet_id_rejt[MSZK_VEZETO].'>
                                <script> gomb_mszk = \'<input type="button" class="gomb zold" onclick="munkalapDiszpKozp()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\';</script>
                                <a onclick="alert2(\'Biztosan átadja a MSZK vezetőnek?\', gomb_mszk)">MSZK vezetőnek</a>
                            </li>
                        </ul>
                    </div>
                </div>
            ');

        }

        // Vizsgálat státusz + Vizsgálat jog
        /*if( $sor['stat_id']==MUNKALAP_STAT_VIZSGALAT_ALATT && _jog(JOG_MUNKALAP_VIZSGALAT) && $_SESSION[get_db_tabla().'szemelyzet_id']!=MUSZAKI_VEZETO ){
            
            $gombok .= nl2('
                <div class="kijeloltek_muv">
                    <input type="button" class="gomb foGomb" value="Átadás...">
                    <div class="muv">
                        <ul>
                            <li'.$szemelyzet_id_rejt[MUSZAKI_VEZETO].'>
                                <script> gomb_musz = \'<input type="button" class="gomb zold" onclick="munkalapMuszakiVezeto()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\'; </script>
                                <a onclick="alert2(\'Biztosan átadja a Műszaki vezetőnek?\', gomb_musz)">Műszaki vezetőnek</a>
                            </li>
                        </ul>
                    </div>
                </div>
            ');

        }*/

        // Visszaadás Diszpécsernek
        $felvehetik = array(TELEPITETT_SZEMELYZET, MOBILCSOPORT);
        if( 
            ( $telephely['modell'] && $sor['stat_id']==MUNKALAP_STAT_FELVEVE            && in_array($sor['szemelyzet_id'], $felvehetik) && $kezeles_jog ) ||
            ( /* Modell nem számít */ $sor['stat_id']==MUNKALAP_STAT_FOLYAMATBAN        && $sor['szemelyzet_id']==KULSO_KIVITELEZO && ( _jog(JOG_MUNKALAP_KEZEL_KULSO) || ( _jog(JOG_MUNKALAP_SAJAT_KEZEL) && $_SESSION[get_db_tabla().'szemelyzet_id']==KULSO_KIVITELEZO ) ) ) ||
            ( $telephely['modell'] && $sor['stat_id']==MUNKALAP_STAT_ANYAGRAVAR         && _jog(JOG_MUNKALAP_ANYAG_ERKEZTET)    ) || 
            ( /* Modell nem számít */ $sor['stat_id']==MUNKALAP_STAT_ELOKESZITES_ALATT  && $_SESSION[get_db_tabla().'szemelyzet_id']==MSZK_VEZETO && $_SESSION[get_db_tabla().'szemelyzet_id']==$sor['szemelyzet_id'] && _jog( JOG_MUNKALAP_KULSOS_ELOKESZIT )  ) ||
            ( /* Modell nem számít */ $sor['stat_id']==MUNKALAP_STAT_VIZSGALAT_ALATT    && _jog(JOG_MUNKALAP_VIZSGALAT)         )
           ){
            $gombok .= '<input type="button" class="gomb" value="Visszaadás" onclick="csoportMuvelet(\'atadasDiszpecsernek\');"> ';
        }

        // Döntés alatt státusz alatt
        if( $sor['stat_id']==MUNKALAP_STAT_DONTESRE_VAR && _jog(JOG_MUNKALAP_KULSOS_DONTES) && $sor['jh1_login']!=$_SESSION[get_db_tabla().'user'] && $sor['jh2_login']!=$_SESSION[get_db_tabla().'user'] ){
            $gombok .= '<input type="button" class="gomb" value="Külsős jóváhagyása" onclick="munkalapKulsosJovahagy(\'csak kérdezünk még\');"> ';
            $gombok .= '<input type="button" class="gomb" value="Külsős elutasítása" onclick="csoportMuvelet(\'munkalapKulsosElutasit\')"> ';
        }

        // Anyagra vár és anyag érkeztetése jog
        if( $sor['stat_id']==MUNKALAP_STAT_ANYAGRAVAR && _jog( JOG_MUNKALAP_ANYAG_ERKEZTET ) ){
            $gombok .= '<input type="button" class="gomb foGomb" value="Anyag rendelkezésre áll" onclick="csoportMuvelet(\'munkalapErkeztet\')"> ';
        }

        // Igazolás alatt státuszban látható. Munkalap vizsgálat alatt státuszba kerül.
        if( $sor['stat_id']==MUNKALAP_STAT_IGAZOLASRA_VAR && _jog(JOG_MUNKALAP_IGAZOLAS) ){
            $gombok .= '<input type="button" class="gomb" value="Igazolás" onclick="munkalapIgazol(\'csak kérdezünk még\');"> ';
            $gombok .= '<input type="button" class="gomb" value="Igazolás elutasítása" onclick="csoportMuvelet(\'munkalapIgenylesElutasit\');"> ';
        }

        // Lezárás: Vizsgálat alatt + Vizsgálat jog
        if( $sor['stat_id']==MUNKALAP_STAT_VIZSGALAT_ALATT && _jog(JOG_MUNKALAP_VIZSGALAT) ){
            $gombok .= '<input type="button" class="gomb" value="Lezárás" onclick="csoportMuvelet(\'munkalapReklamacioElutasit\');"> ';
        }
        
        // Lezárás2: Kész státusz + kezelés jog
        if( $sor['stat_id']==MUNKALAP_STAT_LEZARASRA_VAR && $kezeles_jog ){
            $gombok .= '<input type="button" class="gomb" value="Lezárás" onclick="munkalapRogzit(\'csak kérdezünk még\');"> ';
        }

        // Megrendelés alatt
        if( $sor['stat_id']==MUNKALAP_STAT_HOZREND_MEGREND_VAR && _jog(JOG_MUNKALAP_KULSOS_MEGREND) ){
            $gombok .= '<input type="button" class="gomb" value="Külsős munka megrendelve" onclick="munkalapKulsosMegrendelve(\'csak kérdezünk még\');"> ';
        }

        $muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 16).'px);"';
        if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
            if( $_COOKIE['csuszkaSzelesseg'] ) {
                $muv_style = ' style="width: calc(100% - '.(($_COOKIE['csuszkaSzelesseg'] + 16)/10).'rem);"';
            }
        }

        // Műveletek box
        $gombok = nl2('
            <div class="muveletek"'.$muv_style.'>
                '.$gombok.'
            </div>
        ');
    }


    $js_submit_elott = 'return form_ell_admin();';

	// Új munkalap felvitele
	if( isset($_GET['uj']) && ( _jog(JOG_MUNKALAP_UJ_LETREHOZ) ) ) {

        // NAVIGÁCIÓ
        $navi_sablon  = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit     = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel   = array( $navi,             '',         $gyk2 );
        $szamla_html .= str_replace($navi_mit, $navi_mivel, $navi_sablon);

	    // HIBAüZENETEK
	    require_once('info.php');

	    // FORM
		if($_GET['ujkent']) {
            $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, 'recid='.$_GET['ujkent']);
            if( $sor ) {

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
        // Munkalap újként szerkesztése, de az adatokat a bejelentésből töltjük fel
		if($_GET['ujkent_bejelentes']) {
            $bejelentes = $adatbazis_kapcsolat -> s2('*', 'bejelentes', "`recid`='{$_GET['ujkent_bejelentes']}'");
            $_SESSION['surgos']      = $bejelentes['surgos'];
            $_SESSION['text']        = $bejelentes['hiba_text'];
            $_SESSION['telephely_id']= $bejelentes['telephely_id'];
            $_SESSION['obj_id']      = $bejelentes['obj_id'];
            $_SESSION['gep_id']      = $bejelentes['gep_id'];
            $_SESSION['tipus_id']    = '1';
		}

	    $js = js_kiir($akt_tomb, 'form_ell_admin()');

        // Ckeditort csak akkor húzzuk be, ha kell
        if(is_file($ckeditor_js)){
            $ckeditor = '<script src="'.$ckeditor_js.'"></script>';
        }

	    $form_sablon = fajlbol_olvas('../sablon/admin/form_uj.html', true);

        // Javascipt-es ellenőrzéshez
        $tabla = str_replace('.php', '', basename(self()));

        // Erre miért volt szükség? Az újként szerkesztés így nem működik
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
        
        // Adatok feltöltése rózsaszínezés ellen
        if($_GET['ujkent_bejelentes']) {
            $sor['telephely_id']        = $bejelentes['telephely_id'];
            $sor['obj_id_autocomp']     = getErtek('nev', 'obj', 'recid', $bejelentes['obj_id']);
            $sor['gep_id_autocomp']     = getErtek('nev', 'eszkozkezeles', 'recid', $bejelentes['gep_id']);
            $sor['tipus_id_autocomp']   = erteklista(6, 1);
        }
        $sor['ig_el_id'] = $sor['varh_erk'] = '0000-00-00 00:00:00';

        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($sor).'\'); </script>';

		// Ez elkészíti a $mezok változót
		require('switch.php');

        $mezok .= '<input type="hidden" id="ujkentSzerkID" name="ujkentSzerkID" value="'.$_GET['ujkent'].'">';
        if (isset($_GET['ujkent_bejelentes'])) {
            $mezok .= '<input type="hidden" id="ujkentSzerkBejelentesbol" name="ujkentSzerkBejelentesbol" value="'.$_GET['ujkent_bejelentes'].'">';
        }

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

	// Munkalap módosítása
	}elseif(isset($_GET['modosit'])) {

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
        }

        if( $sor['datetime'] ) {
            $_SESSION['formMod'] = array(
                'datetime' => $sor['update_dt'],
                'oldal'    => basename(self())
            );
        }

        foreach($akt_tomb as $tomb) {
            if( $tomb['type'] == 'autocomplete' ) {

                if($tomb['csoport']){
                    $kulso_ertek = $adatbazis_kapcsolat->s2('info', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}' AND `csoport`='{$tomb['csoport']}'", '', '1');
                    $kulso_ertek['nev'] = $kulso_ertek['info'];
                }else{
                    $kulso_ertek = $adatbazis_kapcsolat->s2('recid nev', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}'", '', '1');
                }

                $sor[$tomb['name'].'_autocomp'] = '';
                if( $kulso_ertek ) {
                    $sor[$tomb['name'].'_autocomp'] = $kulso_ertek['nev'];
                }
            }
        }


        $json_sor = array();
        foreach($sor as $kulcs => $elem){
            $json_sor[$kulcs] = nl2(str_replace(array("\r\n", "\n", "'"), array("#UJSOR#", "#UJSOR#", "#APOSZTROF#"), $elem));
        }

        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

        $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$sor['telephely_id'], '', '1');
        $telephely = $telephely[0]['nev'];

        $munkalap_kapcs = $adatbazis_kapcsolat->s('recid', 'munkalap_kapcs', "`munkalap_id` = '".$sor['recid']."'", '', '');

        // Duplákáláskor feltett kérdés
        if(!_jog(JOG_MUNKALAP_KESZITHET)){
            $szamla_html .= nl2('
                <script>
                    duplikalGomb  = "<p class=\"c\"><input class=\"gomb zold\" type=\"button\" value=\"Ok\" onclick=\"alert2()\"></p>";
                    duplikaKerdes = \'Önnek nincs joga új munkalapot létrehozni!\';
                </script>
            ');
        }else{
            $szamla_html .= nl2('
                <script>
                    duplikalGomb  = "<p class=\"c\"><input class=\"gomb zold\" type=\"button\" value=\"Igen\" onclick=\"location.href = \''.basename(self()).'?uj&ujkent='.$_GET['id'].'\'; \"><input class=\"gomb piros\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
                    duplikaKerdes = \'Iktatószám: '.$sor['ikt_szam'].'<br>Szálloda: '.$telephely.'<br><br>Ez a munkalap '.count($munkalap_kapcs).' db bejelentésből készült. <br>Ezek a bejelentések az új munkalaphoz is hozzá lesznek rendelve. <br>Biztos, hogy duplikálja?\';
                </script>
            ');
        }

        if($id_latszik){
            $navi = '<b>#'.$_GET['id'].'</b> '.$navi;
        }

        // NAVIGÁCIÓ
        $navi_sablon = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit    = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel  = array( $navi,             '',         $gyk2 );
        //echo str_replace($navi_mit, $navi_mivel, $navi_sablon);

	    // HIBAüZENETEK
        require_once('info.php');

	    // FORM
	    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

		// Form actionja
		$action = self().get_get(array('errnum','kephiba','siker'));

        if(isset($_GET['ujkent'])){
			foreach($akt_tomb as $ertek){
				$sor[$ertek['name']] = $_SESSION[$ertek['name']];
			}
		}

        // Ckeditort csak akkor húzzuk be, ha kell
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

        // Bejelentés táblázata
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
                        <div class="dtc"></div>
                    </div>
            ');
            $i = 1;

            foreach($munkalap_kapcsok as $munkalap_kapcs) {

                $sor = $adatbazis_kapcsolat->s2('recid ikt_szam telephely_id obj_id hiba_id insert_dt stat_id hiba_text usr_nev usr_telefon usr_email usr_beosztas_id usr_reszleg_id', 'bejelentes', "`recid` = '".$munkalap_kapcs['bejelentes_id']."'", '', '1');
                if( $sor ) {

                    // Telephely
                    $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$sor['telephely_id'], '', '1');
                    $telephely = $telephely[0]['nev'];

                    // Helység
                    $helyseg = $adatbazis_kapcsolat->s('nev', 'obj', 'recid='.$sor['obj_id'], '', '1');
                    $helyseg = $helyseg[0]['nev'];

                    // Hiba
                    $hiba = $adatbazis_kapcsolat->s2('info', 'erteklista', "`csoport`=4 AND `recid`='{$sor['hiba_id']}'");
                    
                    
                    // Bejelentést, csak munkalap törlés joggal lehet törölni.
                    $torles = '';
                    if( _jog( JOG_MUNKALAP_TOROL ) ){
                        $torles = '
                                <script>
                                    bej_torol_gomb = \'<input type="button" class="gomb zold" value="Igen" onclick="mellekTorol(\\\'munkalap_kapcs\\\', \\\'bejelentesek\\\', \\\'\\\', \\\''.$sor['recid'].'\\\');"><input type="button" class="gomb piros" value="Nem" onclick="alert2(); $(\\\'.torles_elott\\\').removeClass(\\\'torles_elott\\\').removeClass(\\\'kijelolve\\\');">\';
                                </script>
                                <span class="icon" onclick="$(\'#naploSor-'.$sor['recid'].'\').addClass(\'torles_elott\'); alert2(\'Biztos, hogy leveszi a bejelentést a munkalapról?\', bej_torol_gomb);"><i class="material-icons" style="font-size: 2rem">delete</i></span>
                            ';
                    }

// HA MÉGIS KELLENE A TÖRLÉS, AKKOR A naploSor-nak adj egy "kijSor" class-t.
                    $szamla_html .= nl2('
                        <div id="naploSor-'.$sor['recid'].'" class="dtr nSor" data-id="'.$sor['recid'].'" data-i="'.$i.'">
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
                            <div class="dtc link">
                                '.$torles.'
                            </div>
                        </div>
                    ');
                }

                $i++;
            }
        }

        // Diszpécser a sürgős pipát mégis eléri
        if($sor['stat_id']==MUNKALAP_STAT_FELVEVE ){
            $szamla_html .= nl2('
                <script>
                $(document).ready(function () {
                    $(\'#surgos\').removeAttr(\'readonly\');
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

    // Tételek fül
    } elseif(isset($_GET['tetel'])) {

        // Tétel sorok
        $i = 0;
        $sorok = $adatbazis_kapcsolat->s('recid atadta_dt visszaadva erteklista_id loginnev insert_dt insert_usr', 'munkalap_tetel', "`munkalap_id` = '".$_GET['id']."'", 'recid desc');
        foreach($sorok as $sor) {

            $munkalap = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id', 'munkalap', "`recid`='{$_GET['id']}'");
            $kezeles_jog = false;
            if( _jog(JOG_MUNKALAP_SAJAT_SZALL_KEZEL) || (_jog(JOG_MUNKALAP_SAJAT_KEZEL) && $munkalap['szemelyzet_id']==$_SESSION[get_db_tabla().'szemelyzet_id']) ) {
                $kezeles_jog = true;
            }

            $szamla_html .= nl2('
                <div class="tetel_div">
                    <table class="csikos tetel_csikos full">
                        <tr>
                            <th class="tetel_th1">Személyzet</th>
                            <th class="tetel_th2">Felvette</th>
                            <th class="tetel_th3">Átadta</th>
                            <th class="l">Visszaadva</th>
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

            // Szerepkör
            $tetel_felh = $adatbazis_kapcsolat->s2('szerepkor_id', 'login', "`nev` = '".$sor['loginnev']."'", '', '');
            $szerepkor = '';
            if( $tetel_felh ) {

                $szerepkor_sor = $adatbazis_kapcsolat->s2('nev', 'szerepkor', "`recid` = '".$tetel_felh['szerepkor_id']."'", '', '1');
                if( $szerepkor_sor ) {
                    $szerepkor = '<br>('.$szerepkor_sor['nev'].')';
                }
            }

            $szamla_html .= nl2('
                <tr>
                    <td>'.$sor['insert_usr'].$szerepkor.'</td>
                    <td>'.date('Y.m.d H:i', strtotime($sor['insert_dt'])).'</td>
                    <td>'.$atadta.'</td>
                    <td class="l" style="padding-left: 3.7rem">'.$visszaadva.$atadas_oka.'</td>
                </tr>
            ');

            $szamla_html .= '</table>';

            // Elvégzett feladatok lekérdezése
            $feladatok = $adatbazis_kapcsolat->s('recid erteklista_id tetel_id memo', 'munkalap_feladat', "`tetel_id` = '{$sor['recid']}'", 'insert_dt desc', '');
            if( $feladatok ) {
                $szamla_html .= nl2('
                    <div style="padding: 0 0.5rem">
                    <table class="csikos full al">
                        <tr>
                            <th class="feladat_th1">Elvégzett feladat</th>
                            <th class="feladat_th2">Leírás</th>
                            <th></th>
                        </tr>
                ');

                $kezeles_jog = get_kezeles_jog($munkalap['szemelyzet_id']);
                foreach($feladatok as $feladat) {

                    // Csak a legelső iterációban (tehát a legutóbb felvett tételnél) van törlés lehetőség, persze ha a státusz és jogok is rendben vannak.
                    $torles = '';
                    if(
                            $i==0 &&
                            (
                                ( $munkalap['stat_id']==MUNKALAP_STAT_FOLYAMATBAN     && $munkalap['szemelyzet_id']==KULSO_KIVITELEZO && ( _jog(JOG_MUNKALAP_KEZEL_KULSO) || ( _jog(JOG_MUNKALAP_SAJAT_KEZEL) && $_SESSION[get_db_tabla().'szemelyzet_id']==KULSO_KIVITELEZO ) ) ) ||
                                ( $munkalap['stat_id']==MUNKALAP_STAT_FELVEVE         && in_array($munkalap['szemelyzet_id'], $felvehetik) && $kezeles_jog )  ||
                                ( $munkalap['stat_id']==MUNKALAP_STAT_VIZSGALAT_ALATT && _jog(JOG_MUNKALAP_VIZSGALAT) ) ||
                                ( $munkalap['stat_id']==MUNKALAP_STAT_LEZARASRA_VAR   && $kezeles_jog )
                            )

                        ){
                        $torles = '<a class="link" id="feladat2_id_'.$feladat['recid'].'" onclick="munkFeladatTorol( \''.$feladat['recid'].'\', \'feladat2_id_'.$feladat['recid'].'\' );"><span class="icon"><i class="material-icons">delete</i></span></a>';
                    }
                    $szamla_html .= nl2('
                        <tr class="feladat_sor_'.$feladat['recid'].'">
                            <td id="elv_feladat_nev_'.$feladat['recid'].'">'.erteklista(5, $feladat['erteklista_id']).'</td>
                            <td id="elv_feladat_memo_'.$feladat['recid'].'">'.nl2br($feladat['memo']).'</td>
                            <td class="r">'.$torles.'</td>
                        </tr>
                    ');
                }

                $szamla_html .= '</table></div>';
            }

            // Anyagigénylések lekérdezése
            $anyagok = $adatbazis_kapcsolat->s('recid cikk_id cikk_nev cikk_men szuk_men cikk_mee stat_id', 'munkalap_anyag', "`tetel_id` = '{$sor['recid']}'", 'insert_dt desc', '');
            if( $anyagok ) {
                $szamla_html .= nl2('
                    <table class="csikos full al behuz">
                        <tr>
                            <th class="anyag_th1">Cikkszám</th>
                            <th class="anyag_th2">Anyag felhasználás</th>
                            <th class="anyag_th3">Felhasznált mennyiség</th>
                            <th class="anyag_th4">Szükséges mennyiség</th>
                            <th class="anyag_th5">Anyag státusz</th>
                            <th class="anyag_th6"></th>
                        </tr>
                ');

                foreach($anyagok as $anyag) {

                    // Anyag törlés csak az első tételnél lehetséges, ha a státusz és jogok is rendben vannak
                    $anyag_torol = '';
                    if(     $i==0 &&
                            (
                                (($munkalap['stat_id']==MUNKALAP_STAT_FELVEVE || $munkalap['stat_id']==MUNKALAP_STAT_LEZARASRA_VAR) && $kezeles_jog ) ||
                                ($munkalap['stat_id']==MUNKALAP_STAT_VIZSGALAT_ALATT && _jog(JOG_MUNKALAP_VIZSGALAT) )
                            )
                        ){
                        $anyag_torol = '<a class="link" id="anyag2_id_'.$anyag['recid'].'" onclick="munkAnyagTorol( \''.$anyag['recid'].'\', \'anyag2_id_'.$anyag['recid'].'\' );"><span class="icon"><i class="material-icons">delete</i></span></a>';
                    }
                    $cikk_men = '';
                    if( $anyag['cikk_men'] > 0 ) {
                        $cikk_men = $anyag['cikk_men'].' '.$anyag['cikk_mee'];
                    }

                    $szuk_men = '';
                    if( $anyag['szuk_men'] > 0 ) {
                        $szuk_men    = $anyag['szuk_men'].' '.$anyag['cikk_mee'];
                        $anyag_torol = ''; // ha van szükséges mennyiség is, akkor mégsem törölhető az anyag
                    }

                    $szamla_html .= nl2('
                        <tr class="anyag_sor_'.$anyag['recid'].'">
                            <td id="anyag_cikkid_'.$anyag['recid'].'">'.$anyag['cikk_id'].'</td>
                            <td id="anyag_nev_'.$anyag['recid'].'">'.$anyag['cikk_nev'].'</td>
                            <td class="r">'.$cikk_men.'</td>
                            <td class="r">'.$szuk_men.'</td>
                            <td>'.$munkAnyag_statusz[$anyag['stat_id']].'</td>
                            <td class="r">'.$anyag_torol.'</td>
                        </tr>
                    ');
                }

                $szamla_html .= '</table>';
            }

            // Munkaidő ráfordítás lekérdezése
            $munkaidok = $adatbazis_kapcsolat->s('recid login_id ora perc', 'munkalap_munkaido', "`tetel_id` = '{$sor['recid']}'", 'insert_dt desc', '');
            if( $munkaidok ) {
                $szamla_html .= nl2('
                    <table class="csikos full al behuz">
                        <tr>
                            <th class="mido_th1">Név</th>
                            <th class="mido_th2 r">Munkaidő ráfordítás</th>
                            <th class="mido_th3"></th>
                        </tr>
                ');

                foreach($munkaidok as $munkaido) {
                    if($munkaido['perc']<10){
                        $munkaido['perc'] = '0'.$munkaido['perc'];
                    }

                    // Törlés csak az első tételnél lehetséges
                    $torles = '';
                    if(
                            $i==0 &&
                            (
                                (($munkalap['stat_id']==MUNKALAP_STAT_FELVEVE || $munkalap['stat_id']==MUNKALAP_STAT_LEZARASRA_VAR) && $kezeles_jog ) ||
                                ($munkalap['stat_id']==MUNKALAP_STAT_VIZSGALAT_ALATT && _jog(JOG_MUNKALAP_VIZSGALAT) )
                            )
                        ){
                        $torles = '<a id="munk_ido2_'.$munkaido['recid'].'" class="link" onclick="munkMunkaidoTorol( \''.$munkaido['recid'].'\', \'munk_ido2_'.$munkaido['recid'].'\' );"><span class="icon"><i class="material-icons">delete</i></span></a>';
                    }

                    $szamla_html .= nl2('
                        <tr class="munkaido_sor_'.$munkaido['recid'].'">
                            <td id="munkaido_nev_'.$munkaido['recid'].'">'.getErtek('teljes_nev', 'login', 'recid', $munkaido['login_id']).'</td>
                            <td id="munkaido_ido_'.$munkaido['recid'].'" class="r">'.$munkaido['ora'].':'.$munkaido['perc'].'</td>
                            <td class="r">'.$torles.'</td>
                        </tr>
                    ');
                }

                $szamla_html .= '</table>';
            }

            $szamla_html .= '</div>';

            $i++;
        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Történet fül
	}elseif(isset($_GET['tortenet'])){

        $szamla_html .= get_tortenet($megnevezes);

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Anyagigénylés fül
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
                $anyag_sor = $adatbazis_kapcsolat->s2('ikt_szam cikk_id nev igmen rndmen cikkmee stat_id', 'anyagigenyles', "`cikk_id` = '".$sor['cikk_id']."'", '', '1');

                if( $anyag_sor ) {

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

	// Elszámolás fül
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


	// Dokumentumok fül
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

	// Megjegyzések fül
	}elseif(isset($_GET['megjegyzesek'])){

        $szamla_html .= nl2('
            <div class="dt naplo kijCont" data-tabla="megjegyzes" data-mellek="megjegyzesek">
                <div class="dtr nFejlec">
                    <div class="dtc">Megjegyzés</div>
                    <div class="dtc">Hozzáadta</div>
                    <div class="dtc">Hozzáadva</div>
                </div>
        ');

        $sorok = $adatbazis_kapcsolat->s('recid szerepkor megj insert_usr insert_dt', 'megjegyzes', "`id` = '".$_GET['id']."' AND `tabla` = '".str_replace('.php', '', basename(self()))."'", 'recid desc', '');
        if( $sorok ) {
            $i = 1;
            foreach($sorok as $sor) {
                $szerepkor = getErtek('nev', 'szerepkor', 'recid', $sor['szerepkor']);
                $szamla_html .= nl2('
                    <div id="naploSor-'.$sor['recid'].'" class="dtr nSor kijSor" data-id="'.$sor['recid'].'" data-i="'.$i.'">
                        <div class="dtc">'.htmlspecialchars_decode( nl2br($sor['megj']) ).'</div>
                        <div class="dtc">'.$sor['insert_usr'].' '.$szerepkor.'</div>
                        <div class="dtc">'.$sor['insert_dt'].'</div>
                    </div>
                ');

                $i++;
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

	}

    // szamlaBetolt div vége
    if( !$_GET['ajax'] ) {
        $szamla_html .= '</div>';
    }

if( !$_GET['ajax'] ) {
    require_once('footer.php');
}


// Kezelés jogot visszaadó függvény (felvéve státusz alatt)
function get_kezeles_jog($szemelyzet_id){
    $kezeles_jog = false;
    if( 
            ( _jog(JOG_MUNKALAP_SAJAT_KEZEL)        && $szemelyzet_id == $_SESSION[get_db_tabla().'szemelyzet_id'] ) ||
            ( _jog(JOG_MUNKALAP_KEZEL_TELEPITETT)   && $szemelyzet_id == TELEPITETT_SZEMELYZET )                     ||
            ( _jog(JOG_MUNKALAP_KEZEL_MOBIL)        && $szemelyzet_id == MOBILCSOPORT          )                     ||
            // Ez utóbbi feltétek vizsgálat sokszor feles leges, mert a feltétel (ahol ez a függvény meghívódik) eleve kizárja a külső kivitelezőket. De nem baj, így általánosa meghívható ez a függvény.
            ( _jog(JOG_MUNKALAP_KEZEL_KULSO)        && $szemelyzet_id == KULSO_KIVITELEZO      )
        ) {
        $kezeles_jog = true;
    }
    return $kezeles_jog;
}