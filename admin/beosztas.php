<?php
require_once('../weblap.php');

if( !_jog(JOG_BEOSZTASOK_MEGTEKINT) && !_jog(JOG_MSZK_BEOSZTAS_MEGTEKINT) ) {
    if(!$_SESSION[get_db_tabla().'user']){
        $weblap -> leallit();
        header('Location: index.php');
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');
    $weblap -> leallit();
    exit('Nincs megfelelő jogosultság az oldal eléréséhez!');
}

// Config
$megnevezes             = pathinfo(self());
$megnevezes             = $megnevezes['filename'];
$akt_tomb               = $beosztas_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'beosztást';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

$szamla_html = '';


// Dokumentumok fülhöz kell majd
$tobb_kep = false;
if( $tobb_kep ) {
    function get_tobbkep_darab()    { return 10;    } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '';    } // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;    } // MB-ban
    function get_tobbkep_oszlopnev(){                         // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni! 'kepek';       } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!
}

// Adott szálloda adott év+hó-ra létezik-e már. Ha igen, hiba!
if( isset($_POST['beosztas_letezik_e']) ) {
    
    // Ha az év nem 4 karakter, akkor hiba
    if( strlen($_POST['ev'])!=4 ) {
        echo json_encode(
                            array(
                                'hiba'  => 'Kérem az év mezőbe 4 karakter hosszú számot írjon!',
                                'title' => 'Hiba',
                                'focus' => 'ev',
                            )
            );
        $weblap -> leallit();
        exit;
    }
    
    if( $adatbazis_kapcsolat->s2('recid', 'beosztas', "`telephely_id`='{$_POST['telephely_id']}' AND `ev`='{$_POST['ev']}' AND `ho`='{$_POST['ho']}'") ) {
        $szalloda_neve = getSzallodaNev($_POST['telephely_id']);
        echo json_encode(
                            array(
                                'hiba'  => ucfirst(a_az( $szalloda_neve )).' '.$szalloda_neve.' szállodához már létezik beosztás '. date_formaz($_POST['ev'].'-'.$_POST['ho'], '', 'csak hónap').' hónaphoz!',
                                'title' => 'Hiba',
                                'focus' => 'telephely_id',
                            )
            );
        $weblap -> leallit();
        exit;
    }
    
    echo json_encode(array('mehet' => '1'));
    $weblap -> leallit();
    exit;

}

// Mobilcsoportos felhasználók neveit kilistázzuk a popupba
if( isset($_POST['get_mobil_nevek']) ) {
    
    $html = nl2('
        Az alábbi listát rendezze át a kívánt sorrendbe:
        <ul class="dropItem droptrue">
        ');
    $nevek = $adatbazis_kapcsolat->s('recid nev', 'beosztas_nevek', "`beosztas_recid`='{$_POST['recid']}' AND `mobilos`=1", 'sor');
    foreach( $nevek as $nev ) {
        $html .= '<li class="ui-state-default" data-recid="'.$nev['recid'].'">'.$nev['nev'].'</li>';
    }
    $html .= '</ul>';
    
    echo json_encode(array('nevek' => $html));
    
    
    $weblap -> leallit();
    exit;
}

// Mobil sorrend mentése
if( isset($_POST['mobil_sorrend_ment']) ) {
    
    // Tranzakció indul
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $_POST['sorrend'] = json_decode($_POST['sorrend'], 'array-be');
    $i = 0;
    foreach ( $_POST['sorrend'] as $sor ) {
        $sql = $adatbazis_kapcsolat->u('beosztas_nevek', array('sor'), array($i), "`recid`='{$sor}'");
        if( $sql === false ) { $db_error = true; }
        // Törzsadat táblába is eltároljuk
        $torzsadat = $adatbazis_kapcsolat->s2('torzsadat_recid', 'beosztas_nevek', "`recid`='{$sor}'");
        if( $sql === false ) { $db_error = true; }
        $sql = $adatbazis_kapcsolat->u('torzsadat', array('sor'), array($i), "`recid`='{$torzsadat['torzsadat_recid']}'");
        if( $sql === false ) { $db_error = true; }
        $i++;
    }
    
    // Tranzakció kommitolása, vagy rollback-ezése
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array('siker' => 1) );
    } else {
        echo json_encode( array(
                                'hiba'  => tranzakcioHiba(),
                                'title' => 'Hiba',
            ) );
        mysqli_rollback($db_kapcs);
    }
    
    $weblap -> leallit();
    exit;
}

// Beosztás tálblázatban elmentett műszakok tárolása adatbázisba
if( isset($_POST['beosztas_tabla_ment']) ) {
    
    // Tranzakció indul
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $_POST['adatok_tomb'] = json_decode($_POST['adatok_tomb'], 'array-be');
    foreach( $_POST['adatok_tomb'] as $adatok ) {

        // Szabadnap és Betegség fix műszakoknál az órát vissza kell számolni a Törzsadat Napi munkaidő táblából
        if( $adatok['jel'] == BEOSZTAS_FIX_SZABADNAP_JEL || $adatok['jel'] == BEOSZTAS_FIX_BETEGSEG_JEL ) {
            $torzsadat_recid = $adatbazis_kapcsolat->s2('torzsadat_recid', 'beosztas_nevek', "`recid`='{$adatok['nev_id']}'");
            $napi_munkaido   = $adatbazis_kapcsolat->s2('ora', 'torzsadat_napi_munkaido', "`torzsadat_recid`='{$torzsadat_recid['torzsadat_recid']}' AND `datum`<='{$adatok['datum']}'", 'datum desc', '1');
            $adatok['ora']   = $napi_munkaido['ora'];
        }
        
        $mezok   = array('beosztas_nevek_recid', 'beosztas_recid',  'beosztas_muszak_recid',   'datum',            'jel',          'szin',          'ora',         'tol',           'ig',          'insert_usr',                           'insert_dt', 'update_usr'                            );
        $ertekek = array($adatok['nev_id'],       $adatok['recid'], $adatok['muszak_id'],      $adatok['datum'],   $adatok['jel'], $adatok['szin'], $adatok['ora'], $adatok['tol'], $adatok['ig'], $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'] );

        // Insert vagy update, azon belül del?
        $naptar_recid = $adatbazis_kapcsolat->s2('recid', 'beosztas_naptar', "`beosztas_nevek_recid`='{$adatok['nev_id']}' AND `beosztas_recid`='{$adatok['recid']}' AND `datum`='{$adatok['datum']}'");

        if( $naptar_recid === false ) { $db_error = true; }
        if( $naptar_recid ) {
            if( $adatok['jel'] == '|del|' ) {
                $sql = $adatbazis_kapcsolat->d('beosztas_naptar', "`recid`='{$naptar_recid['recid']}'");
                if( $sql === false ) { $db_error = true; }
            } else {
                $sql = $adatbazis_kapcsolat->u('beosztas_naptar', $mezok, $ertekek, "`recid`='{$naptar_recid['recid']}'");
                if( $sql === false ) { $db_error = true; }
            }
        } else if( $adatok['jel'] != '|del|' ) {
            $sql = $adatbazis_kapcsolat->i('beosztas_naptar', $mezok, $ertekek);
            if( $sql === false ) { $db_error = true; }
        }

    }
    
    // Tranzakció kommitolása, vagy rollback-ezése
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array('siker' => 1) );
    } else {
        echo json_encode( array(
                                'hiba'  => tranzakcioHiba(),
                                'title' => 'Hiba',
            ) );
        mysqli_rollback($db_kapcs);
    }
    
    $weblap -> leallit();
    exit;
}

// Adott illető műszakja hol van?
if( isset($_POST['get_illeto_muszakja']) ) {
    
    $naptar   = $adatbazis_kapcsolat->s2('beosztas_recid beosztas_nevek_recid', 'beosztas_naptar', "`recid`='{$_POST['naptar_recid']}'");
    $nev      = $adatbazis_kapcsolat->s2('nev', 'beosztas_nevek', "`recid`='{$naptar['beosztas_nevek_recid']}'");
    $beosztas = $adatbazis_kapcsolat->s2('telephely_id', 'beosztas', "`recid`='{$naptar['beosztas_recid']}'");
    
    $szalloda_neve  = getSzallodaNev($beosztas['telephely_id']);
    $szalloda_neve2 = getSzallodaNev($_POST['uj_telephely_id']);
    
    echo json_encode(
            array(  'hiba' => $nev['nev'].' kolléga már be van osztva erre a napra '.a_az($szalloda_neve).' '.$szalloda_neve.' szállodában.<br>
                            '. ucfirst(a_az($szalloda_neve2)).' '.$szalloda_neve2.' beosztáshoz előbb szüntesse meg a jelenlegi beosztását.',
                    'title' => 'Hiba'
                )
        );
    
    $weblap -> leallit();
    exit;   
}

// Személyzet lista azokról, akik hozzáadhatóak
// Akik szerepköre telepített vagy mobil vagy diszpécser és van többszállodás modellű szállodája és benne vannak a személyzet táblában DE nincsenek benne az aktuális beosztás táblában.
if( isset($_POST['get_szemelyzet_hozzaad']) ) {
    
    // Vegyük azokat a telephelyeket, amik adott felhasználóhoz tartoznak ÉS többszállodások
    $telephelyek = $adatbazis_kapcsolat->s('id', 'telephely', "`id` IN (".implode(', ', get_akt_login_szallodai()).") AND `modell`=1");
    $telephelyek = adatbazis2tomb($telephelyek, 'id');

    // A hozzám tartozó szállodákhoz tartozó telepített személyek (akik Aktívak, nem virtuálisak)
    $login_nevek  = $adatbazis_kapcsolat->s('DISTINCT `nev`', 'telephely_kapcs', "`telephely_id` IN (".implode(', ', $telephelyek).")");
    $login_nevek  = adatbazis2tomb($login_nevek, 'nev');

    // Telepített személyek, akik Aktívak, nem virtuálisak
    $felhasznalok = $adatbazis_kapcsolat->s('nev', 'login', "`nev` IN ('". implode('\', \'', $login_nevek)."') AND `szemelyzet_id` IN ('".TELEPITETT_SZEMELYZET."', '".MOBILCSOPORT."', '".DISZPECSER."') AND `aktiv`=1 AND `virtual`=0");
    $felhasznalok = adatbazis2tomb($felhasznalok, 'nev');

    // Kik a már hozzáadott felhasználók
    $hozzaadott_nevek = $adatbazis_kapcsolat->s('torzsadat_recid', 'beosztas_nevek', "`beosztas_recid`='{$_POST['beosztas_recid']}'");
    $hozzaadott_nevek = adatbazis2tomb($hozzaadott_nevek, 'torzsadat_recid');
    $sql_kieg = '';
    if( $hozzaadott_nevek ) {
        $sql_kieg = " AND `recid` NOT IN (".implode(',', $hozzaadott_nevek).")";
    }

    $html = nl2('
        <table class="csikos">
        <tr>
            <th></th>
            <th>Név</th>
            <th>Szerepkör</th>
            <th>Utolsó beosztás helye</th>
        </tr>
        ');
    $torzsadatok = $adatbazis_kapcsolat->s('recid nev felh_loginnev', 'torzsadat', "`felh_loginnev` IN ('". implode('\', \'', $felhasznalok)."'){$sql_kieg}", 'nev');
    if( !$torzsadatok ) {
        echo json_encode(
                            array(
                                'hiba'  => 'Jelenleg nem található további személyzet!',
                                'title' => 'Hiba',
                                )
                        );
        $weblap -> leallit();
        exit;
    }
    foreach( $torzsadatok as $torzsadat ) {
        
        $utolso_beosztas_szalloda = '';
        $utolso_beosztas = $adatbazis_kapcsolat->s2('beosztas_recid', 'beosztas_nevek', "`torzsadat_recid`='{$torzsadat['recid']}'", 'insert_dt desc', '1');
        if( $utolso_beosztas ) {
            $beosztas = $adatbazis_kapcsolat->s2('telephely_id', 'beosztas', "`recid`='{$utolso_beosztas['beosztas_recid']}'");
            $utolso_beosztas_szalloda = getSzallodaNev($beosztas['telephely_id']);
        }
        
        $login  = $adatbazis_kapcsolat->s2('szemelyzet_id', 'login', "`nev`='{$torzsadat['felh_loginnev']}'");
        $html .= nl2('
            <tr>
                <td>
                    <div class="cb">
                        <input type="checkbox" id="torzs_'.$torzsadat['recid'].'" class="szemelyzet_hozzaad" data-recid="'.$torzsadat['recid'].'">
                        <label for="torzs_'.$torzsadat['recid'].'"></label>
                    </div>
                </td>
                <td class="l">'.$torzsadat['nev'].'</td>
                <td class="l">'.getSzerepkorNev($login['szemelyzet_id']).'</td>
                <td class="l">'.$utolso_beosztas_szalloda.'</td>
            </tr>
            ');
    }
    $html .= nl2('</table>');
    
    echo json_encode(array('html' => $html));
    
    $weblap -> leallit();
    exit;   
}

// Személyzet felvétele a listára
if( isset($_POST['szemelyzet_hozzaad']) ) {
    
    $_POST['recidk'] = trim($_POST['recidk'], ',');
    $szemelyek = explode(',', $_POST['recidk']);
    $_SESSION['friss_szemelyek'] = $szemelyek;
    foreach( $szemelyek as $szemely ) {
        
        $torzsadat  = $adatbazis_kapcsolat->s2('nev sor felh_loginnev', 'torzsadat', "`recid`='{$szemely}'");
        if( $torzsadat ) {
            $login      = $adatbazis_kapcsolat->s2('szemelyzet_id', 'felh', "`nev`='{$torzsadat['nev']}'");
            $mobilos = '';
            if( $login['szemelyzet_id'] == MOBILCSOPORT ) {
                $mobilos = 1;
            }

            $adatbazis_kapcsolat->i('beosztas_nevek',   array('beosztas_recid',         'torzsadat_recid',  'nev',              'mobilos', 'sor',               'insert_dt', 'insert_usr',                           'update_usr'                           ),
                                                        array($_POST['beosztas_recid'], $szemely,           $torzsadat['nev'],  $mobilos,  $torzsadat['sor'],   'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'], $_SESSION[get_db_tabla().'teljes_nev'] )
                                    );
        }
    }
    
    $weblap -> leallit();
    exit;  
}


// Személyzet lista törléshez
if( isset($_POST['get_szemelyzet_torol']) ) {
    
    // Kik a már hozzáadott felhasználók
    $html = nl2('
        <table class="csikos">
        <tr>
            <th></th>
            <th class="l">Név</th>
        </tr>
        ');
    $beosztas_nevek = $adatbazis_kapcsolat->s('recid nev', 'beosztas_nevek', "`beosztas_recid`='{$_POST['beosztas_recid']}'", 'mobilos, sor, nev');
    foreach( $beosztas_nevek as $beosztas_nev ) {
        
        $html .= nl2('
            <tr>
                <td>
                    <div class="cb">
                        <input type="checkbox" id="torzs_'.$beosztas_nev['recid'].'" class="szemelyzet_torol" data-recid="'.$beosztas_nev['recid'].'">
                        <label for="torzs_'.$beosztas_nev['recid'].'"></label>
                    </div>
                </td>
                <td class="l torzs_nev_'.$beosztas_nev['recid'].'">'.$beosztas_nev['nev'].'</td>
            </tr>
            ');
    }
    $html .= nl2('</table>');
    
    echo json_encode(array('html' => $html));
    
    $weblap -> leallit();
    exit;   
}


// Személyzet felvétele a listára
if( isset($_POST['szemelyzet_torles']) ) {
    
    $szemelyek = trim($_POST['recidk'], ',');
    if( $szemelyek ) {
        $adatbazis_kapcsolat->d('beosztas_nevek', "`recid` IN ({$szemelyek})");
        $adatbazis_kapcsolat->d('beosztas_naptar', "`beosztas_nevek_recid` IN ({$szemelyek}) AND `beosztas_recid`='{$_POST['beosztas_recid']}'");
    }
    
    $weblap -> leallit();
    exit;  
}

// CSV generálása
if( isset($_GET['csv']) ) {
    
    // Beosztás táblázat
    if( isset($_GET['tablazat']) ) {
        
        beosztas_csv_export_elokeszit('beosztas_tablazat');
        echo fajlbol_olvas('csv/beosztas_tablazat'.session_id().'.csv');
        
        $weblap->leallit();
        exit();
        
    // Sima lista
    } else {
        
        $honap = array('', 'január', 'február', 'március', 'április', 'május', 'június', 'július', 'augusztus', 'szeptember', 'október', 'november', 'december');

        // CSV export előkészítése
        list($mibol, $mibe, $filename, $open) = csv_export_elokeszit($megnevezes);
        if( $open ) {

            $sql = 'SELECT
                    `'.get_db_tabla().'beosztas`.`telephely_id` as \'Szálloda\',
                    `'.get_db_tabla().'beosztas`.`ev` as \'Év\',
                    `'.get_db_tabla().'beosztas`.`ho` as \'Hónap\'
                    '. csv_export_get_from_tol($megnevezes);
            $query = mysqli_query($db_kapcs, $sql);

            // Nagyobb CSV-kre kérdezzünk rá előzetesen
            $osszes_sor = mysqli_num_rows($query);
            if( !$_GET['mehet'] && $osszes_sor>CSV_EXPORT_LIMIT ) {
                $gomb = '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#navGombok .excel\').attr(\'onclick\', \'csv_general( this, \\\'\\\', \\\'&mehet=1\\\');\'); $(\'.navGombok a.excel\').click();"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">';
                echo json_encode(array('hiba' => 'A CSV export több mint '.number_format(CSV_EXPORT_LIMIT, 0, '', ' ').' sort tartalmaz. Ennek előállítása hosszabb időbe telik majd.<br>Biztosan folytatja?', 'gomb' => $gomb));
                $weblap -> leallit();
                exit;
            } else {
                $sql .= $_SESSION['akt_sql_order'];    
            }

            $query      = mysqli_query($db_kapcs, $sql);
            $csvhead    = array();
            while ( $fejlec_nevek = mysqli_fetch_field($query) ) {
                $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
            }
            fputcsv($open, $csvhead, ';');

            $i = 0;
            while( $sorok = mysqli_fetch_assoc($query) ) {

                $csvcont = array();
                foreach( $sorok as $kulcs => $sor ) {
                    if( $kulcs == 'Szálloda' ) {
                        $sor = getSzallodaNev($sor);
                    }
                    if( $kulcs == 'Hónap' ) {
                        $sor = $honap[$sor];
                    }

                    // Lehet nem volna elég 1-szer sőt kétszer sem ráereszteni a htmlspecialchars_decode-ot
                    $sor = htmlspecialchars_decode( $sor );
                    while ( strpos($sor, '&amp;') !== false || strpos($sor, '&quot;') !== false ) {
                        $sor = htmlspecialchars_decode( $sor );
                    }
                    $csvcont[] = iconv($mibol, $mibe.'//TRANSLIT', strip_tags( $sor ) );
                }

                fputcsv($open, $csvcont, ';');

                // 1000 soronként kiíratunk
                if( $i % 1000 == 0 && $i > 0 ) {
                    // Progress
                    echo json_encode(array('pr' => number_format(($i / $osszes_sor) * 100, 0, '', ''))).PHP_EOL;

                    // Felszabadítás
                    ob_flush();
                    flush();
                }

                $i++;
            }

            // Progress
            echo json_encode(array('pr' => '100')).PHP_EOL;

            // Felszabadítás
            ob_flush();
            flush();
        }

        csv_export_lezar($fh, $filename, 'Beosztások');
    }

}

// Tartalom
if( !$_GET['ajax'] ) {
    require_once('header.php');
}
require_once('selectek.php');

    if( !$_GET['ajax'] ) {
        echo '<div id="szamlaBetolt">';
    }

    // Az adatlap adatait már a legelején lekérjük
    if( $_GET['id'] ) {
        $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");
    }
    if( $sor['dok_db'] ) {
        $dok_db  = ' ('.$sor['dok_db'].')';
    }
    if( $sor['megj_db'] ) {
        $megj_db = ' ('.$sor['megj_db'].')';
    }

    if( isset($_GET['modosit']) /*|| isset($_GET['tortenet']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek'])*/ ) {

        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
//            'tortenet'      => 'Történet',
//            'dokumentumok'  => 'Dokumentumok'.$dok_db,
//            'megjegyzesek'  => 'Megjegyzések'.$megj_db,
        );

        $fulek = '';
        foreach ( $ful_tomb as $key => $value ) {

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

    // Gombok kirajzolása
    $gombok = '';
    if( isset($_GET['uj']) ) {
        // Gombok
        $gombok = nl2('
            <div class="form_kuldes">
                <input type="button" class="gomb zold" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="beosztas_letezik_e();">
                <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
            </div>
        ');

        $muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 16).'px);"';
        if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
            if( $_COOKIE['csuszkaSzelesseg'] ) {
                $muv_style = ' style="width: calc(100% - '.(($_COOKIE['csuszkaSzelesseg'] + 16)/10).'rem);"';
            }
        }
        
    $gombok .= nl2('
                <script>
                function beosztas_letezik_e() {

                    szurkit(\'mehet_megsem_rejt\');
                    
                    if( $(\'#ev\').val()<\''.(date('Y')-1).'\' ) {
                        alert2(\''.(date('Y')-1).' lehet a legkorábbi év, amit megadhat!\', \'\', \'Hiba\');
                        $(\'#ev\').addClass(\'hibas_ide_focus\');
                        return false;
                    }
                    
                    $.ajax({
                        url: PHP_SELF,
                        type: \'POST\',
                        data: {
                            telephely_id        : $(\'#telephely_id\').val(),
                            ev                  : $(\'#ev\').val(),
                            ho                  : $(\'#ho\').val(),
                            beosztas_letezik_e  : Date.now()
                        },
                        success: function (response) {
                            if(response.substring(0, 6)==\'SESKIL\'){ location.href = \'index.php\'; }
                            if(response) {
                                response = JSON.parse(response);
                                if( response.hiba ) {
                                    alert2(response.hiba, response.gomb, response.title);
                                    alert_utan_mehet_megsem_mutat_beallit();
                                    if( response.focus ) {
                                        $(\'#\'+response.focus).addClass(\'hibas_ide_focus\');
                                    }
                                } else {
                                    $(\'input, select\').removeClass(\'mod\');
                                        setTimeout(function () {
                                            $(\'#szerk_form\').submit();
                                        }, 301);
                                }
                            }
                            return false;
                        },
                        complete: function(xhr) {
                            if( xhr.status != 200 ) {
                                alert2(xhr.responseText, \'\', \'Hiba\');
                            }
                        }
                    });

                }
                </script>
        ');

        // Műveletek box
        $gombok = nl2('
            <div class="muveletek"'.$muv_style.'>
                '.$gombok.'
            </div>
        ');

    } else {

	    // Js, a törlés gombhoz
	    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';

        if( !_jog(JOG_BEOSZTASOK_KEZEL) && !_jog(JOG_MSZK_BEOSZTAS_KEZEL) ) {
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Önnek nincs joga beosztást törölni!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        } else {
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
        }

        $mobil_sorrend_gomb = '';
        if( $sor['telephely_id'] == TELEPHELY_SZK ) {
            $mobil_sorrend_gomb = '<input type="button" class="gomb" value="Nevek rendezése" onclick="csoportMuvelet(\'beosztas_nevek_rendez\');">';
        }
        
        // Ha adott felhasználónak van többszállodás szállodája, akkor: Személyzet hozzáadása gomb megjelenítése
        $szallodak = get_akt_login_szallodai();
        if( $szallodak ) {
            $tobbszallodas = $adatbazis_kapcsolat->s('recid', 'telephely', "`id` IN (". implode(', ', $szallodak).") AND `modell`='1'");
            if( $tobbszallodas ) {
                $gombok .= '<input type="button" class="gomb" value="Személyzet hozzáadása" onclick="csoportMuvelet(\'beosztas_szemelyzet_hozzaad\');"> ';
                $gombok .= '<input type="button" class="gomb" value="Személyzet törlése" onclick="csoportMuvelet(\'beosztas_szemelyzet_torol\');">';
            }
        }
        
        if( _jog(JOG_BEOSZTASOK_KEZEL) || _jog(JOG_MSZK_BEOSZTAS_KEZEL) ) {
            $gombok = nl2('
                <div class="form_kuldes">
                    '.$mobil_sorrend_gomb.'
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
                </div>
            ').$gombok;
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

	// Új termék felvitele
	if( isset($_GET['uj']) && (_jog(JOG_BEOSZTASOK_KEZEL) || _jog(JOG_MSZK_BEOSZTAS_KEZEL) ) ) {

        $js_submit_elott = 'return form_ell_admin();';
        
        // Újként szerkesztéskor feltöltjük a mezők tartalmát
		if( $_GET['ujkent'] ) {
            $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['ujkent']}'");
            if( $sor ) {
                foreach ( $akt_tomb as $ertek ) {
                    $_SESSION[$ertek['name']] = stripslashes( $sor[$ertek['name']] );
    			}
            }

			foreach ( $akt_tomb as $ertek ) {
                $sor[$ertek['name']] = $_SESSION[$ertek['name']];
			}
		}
	    $js = js_kiir($akt_tomb, 'form_ell_admin()');

        // Ckeditort csak akkor húzzuk be, ha kell
        if(is_file($ckeditor_js)){
            $ckeditor = '<script src="'.$ckeditor_js.'"></script>';
        }

	    $form_sablon = fajlbol_olvas('../sablon/admin/form_uj.html', true);

        // Javascript-es ellenőrzéshez
        $tabla = str_replace('.php', '', basename(self()));

        $rend_mezok = $adatbazis_kapcsolat->s('`column_name`', '`information_schema`.columns', '`table_schema` LIKE  \''.get_db().'\' AND  `table_name` LIKE  \''.get_db_tabla().$tabla.'\'', '', '');
        $rend_mezok = adatbazis2tomb($rend_mezok, 'column_name');

        $sor = array();
        foreach ( $rend_mezok as $rend_mezo ) {
            $sor[$rend_mezo] = '';
        }
        
        // MSZK beosztás kezelés jog esetén a szálloda fixen a TELEPHELY_SZK (A disabled értéket az események tömbben állítjuk be).
        if( _jog(JOG_MSZK_BEOSZTAS_KEZEL) ) {
            $sor['telephely_id'] = TELEPHELY_SZK;
        }

        foreach ( $akt_tomb as $tomb ) {
            if( $tomb['type'] == 'autocomplete' ) {
                $sor[$tomb['name'].'_autocomp'] = '';
            }
        }

        $json_sor = array();
        foreach ( $sor as $kulcs => $elem ) {
            $json_sor[$kulcs] = formAdatok_replace($elem);
        }
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

		// Ez elkészíti a $mezok változót
		require('switch.php');

	    $form_mit     = array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{CK_EDITOR}', '{GOMBOK}', '{MUV_STYLE}'   );
	    $form_mivel   = array($js,    $js_submit_elott,    self(),     $mezok,    $megnevezes,    $ckeditor,     $gombok,    $muv_style      );
	    $szamla_html .= str_replace($form_mit, $form_mivel, $form_sablon);

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

	// Beosztás módosítása
    } elseif( isset($_GET['modosit']) ) {

        $js_submit_elott = 'beosztas_tabla_ment();';

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if( isset($_GET['id_hidden']) ) {
            $_GET['id'] = $_GET['id_hidden'];
        }

	    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");
        foreach ( $akt_tomb as $tomb ) {
            if( $tomb['type'] == 'autocomplete' ) {

                if( $tomb['csoport'] ) {
                    $kulso_ertek = $adatbazis_kapcsolat->s2('info', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}' AND `csoport`='{$tomb['csoport']}'", '', '1');
                    $kulso_ertek['nev'] = $kulso_ertek['info'];
                } else {
                    $kulso_ertek = $adatbazis_kapcsolat->s2('recid nev', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}'", '', '1');
                }

                $sor[$tomb['name'].'_autocomp'] = '';
                if( $kulso_ertek ) {
                    $sor[$tomb['name'].'_autocomp'] = $kulso_ertek['nev'];
                }
            }
        }

        $json_sor = array();
        foreach ( $sor as $kulcs => $elem ) {
            $json_sor[$kulcs] = formAdatok_replace($elem);
        }
        $szalloda_nev = getSzallodaNev($sor['telephely_id']);
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';
        $szamla_html .= nl2('
            <script>
                duplikalGomb  = "<p class=\"c\"><input class=\"gomb zold\" type=\"button\" value=\"Igen\" onclick=\"szurkit(); location.href = \''.basename(self()).'?uj&amp;ujkent='.$_GET['id'].'\'; \"><input class=\"gomb piros\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
                duplikaKerdes = \'Szálloda: '.$szalloda_nev.'<br>Dátum: '.date_formaz($sor['ev'].'-'.$sor['ho'], '', 'csak hó').'<br><br>Biztos, hogy duplikálja?\';
            </script>
        ');
        
	    // FORM
	    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

		// Form actionja
		$action = self().get_get(array('errnum','kephiba','siker'));

        // Ckeditort csak akkor húzzuk be, ha kell
        if( is_file($ckeditor_js) ) {
            $ckeditor = '<script src="'.$ckeditor_js.'"></script>';
        }

		// Ez elkészíti a $mezok változót
		require('switch.php');

        $jobb_oldal = get_beosztas_tablazat( $sor );

        $action = 'javascript:void(0);';
        
	    $szamla_html .= str_replace(
            array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}', '{UPDATE_DT}'     ),
            array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok,    $sor['update_dt'] ),
            fajlbol_olvas('../sablon/admin/form_modosit.html', true)
        );

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

        if( $tobb_kep ) {
            require_once('tobb_kep.php');
		}

	}

    if( !$_GET['ajax'] ) {
        echo '</div>';
    }

if( !$_GET['ajax'] ) {
    require_once('footer.php');
}

// Beosztás táblázat kirajzolása központosítva, nyomtatási nézet miatt
function get_beosztas_tablazat( $sor ) {
    
    global $adatbazis_kapcsolat;
    
    $csv_mibol   = get_web_charset();
    $csv_mibe    = 'windows-1250';
    
    $napok_th = '';
    $honap = $sor['ho'];
    if( $honap<10 ) {
        $honap = '0'.$honap;
    }
    // Ünnepnapok és helyettesítő napokat felvesszük egy tömbbe
    $unnepnapok         = $adatbazis_kapcsolat->s('datum', 'beosztas_unnepnapok', "`datum` LIKE '{$sor['ev']}-{$honap}%' AND `tipus` IN (".BEOSZTAS_UNNEPNAP_MUNKASZUNET.", ".BEOSZTAS_UNNEPNAP_PIHENO.")");
    $unnepnapok         = adatbazis2tomb($unnepnapok, 'datum');
    $helyettesito_napok = $adatbazis_kapcsolat->s('datum', 'beosztas_unnepnapok', "`datum` LIKE '{$sor['ev']}-{$honap}%' AND `tipus` IN (".BEOSZTAS_UNNEPNAP_HELYETTES.")");
    $helyettesito_napok = adatbazis2tomb($helyettesito_napok, 'datum');

    $csv  = '';
    $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Szálloda:')).';';
    $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, getSzallodaNev($sor['telephely_id']))).';';
    $csv .= "\n";
    $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Év. hónap:')).';';
    $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, date_formaz($sor['ev'].'-'.$sor['ho'], '', 'csak hó'))).';';
    $csv .= "\n";
    $csv .= "\n";
    $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Név')).';';

    $datumok_tomb = array();
    $napok_szama = date('t', strtotime($sor['ev'].'-'.$honap.'-01'));
    for( $i = 0; $i<$napok_szama; $i++ ) {

        $nap = $nap_nulakkal = $i+1;
        if( $nap_nulakkal<10 ) {
            $nap_nulakkal = '0'.$nap_nulakkal;
        }

        $sarga = '';
        $datum = $sor['ev'].'-'.$honap.'-'.$nap_nulakkal;
        // Szombat vasárnap kiemelve
        $het_napja = date('w', strtotime($sor['ev'].'-'.$honap.'-'.$nap_nulakkal));
        if( ($het_napja == 0 || $het_napja == 6) && !in_array($datum, $helyettesito_napok) ) {
            $sarga = ' class="sarga"';

        // Ünnepnapok szintúgy
        } else if( in_array($datum, $unnepnapok) ) {
            $sarga = ' class="sarga"';
        }
        array_push($datumok_tomb, array('datum' => $sor['ev'].'-'.$honap.'-'.$nap_nulakkal, 'sarga' => $sarga));

        $nap_neve = nap_neve($sor['ev'], $honap, $nap_nulakkal);
        $napok_th .= '<th'.$sarga.'>'.$nap.'<br>'.$nap_neve.'</th>';
        $csv .= '='.csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $nap.' '.$nap_neve)).';';
    }
    $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Terv óra')).';';
    $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Tény óra')).';';
    $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Egyenleg')).';';
    $csv .= "\n";

    // Műszakok
    $muszak_select  = '<select id="muszak" class="disableNav nyomtatasi_elonezetben_sem_taszik">';
    $muszak_select .= '<option value="">-Válasszon műszakot-</option>';
    $muszakok = $adatbazis_kapcsolat->s('*', 'beosztas_muszak', "`telephely_id`='{$sor['telephely_id']}' AND `aktiv`=1", 'nev');

    foreach( $muszakok as $muszak ) {
        $muszak_select .= '<option value="'.$muszak['recid'].'|sep|'.$muszak['jel'].'|sep|'.$muszak['szin'].'|sep|'.$muszak['ora'].'|sep|'.$muszak['tol'].'|sep|'.$muszak['ig'].'" style="background: #'.$muszak['szin'].'">'.$muszak['nev'].' ('.$muszak['jel'].' '.$muszak['tol'].'-'.$muszak['ig'].')</option>';
    }

    $muszak_select .= '<option value="0|sep|'.BEOSZTAS_FIX_SZABADNAP_JEL.'">Szabadság ('.BEOSZTAS_FIX_SZABADNAP_JEL.')</option>';
    // A beosztás felületen a műszak lenyílóból ki kell venni a pihenőnapot mert azt nem személyenként kell megadni, hanem a törvényből jön.
    // $muszak_select .= '<option value="0|sep|'.BEOSZTAS_FIX_PIHENONAP_JEL.'">Pihenőnap ('.BEOSZTAS_FIX_PIHENONAP_JEL.')</option>';
    $muszak_select .= '<option value="0|sep|'.BEOSZTAS_FIX_BETEGSEG_JEL.'">Betegség ('.BEOSZTAS_FIX_BETEGSEG_JEL.')</option>';
    $muszak_select .= '<option value="|del|">Nincs beosztás</option>';
    $muszak_select .= '</select>';

    $jobb_oldal .= $muszak_select.
        nl2('
        <div id="fixTable">
            <table class="beosztas">
            <tr>
                <th>Név</th>
                '.$napok_th.'
                <th title="Munkanapok * napi munkaidő">Terv<br>óra</th>
                <th title="Beosztott napok * műszakok óraszáma">Tény<br>óra</th>
                <th title="Tény órák - terv órák">Egyenleg</th>
            </tr>
        ');

    // Felhasználók kilistázása
    $nevek = $adatbazis_kapcsolat -> s('recid nev torzsadat_recid', 'beosztas_nevek', "`beosztas_recid`='{$sor['recid']}'", 'mobilos, sor, nev');
    // Ha még nincsenek nevek, akkor létrehozzuk őket => ez egy pillanatfelvétel az aktuális törzsadat adatbázisról
    if( !$nevek ) {

        // SZK Szálloda esetén
        if( $sor['telephely_id'] == TELEPHELY_SZK ) {
            // Diszpécser vagy mobilcsoport usereket kell kilistázni, akik Aktívak, nem virtuálisak
            $felhasznalok = $adatbazis_kapcsolat->s('nev', 'login', "(`szemelyzet_id`='".DISZPECSER."' OR `szemelyzet_id`='".MOBILCSOPORT."') AND `aktiv`=1 AND `virtual`=0");
            $felhasznalok = adatbazis2tomb($felhasznalok, 'nev');

        // Általános szálloda esetén
        } else {

            // A hozzám tartozó szállodákhoz tartozó telepített személyek (akik Aktívak, nem virtuálisak)
            $login_nevek  = $adatbazis_kapcsolat->s('DISTINCT `nev`', 'telephely_kapcs', "`telephely_id` IN (".implode(', ', get_akt_login_szallodai()).")");
            $login_nevek  = adatbazis2tomb($login_nevek, 'nev');
            // Telepített személyek, akik Aktívak, nem virtuálisak
            $felhasznalok = $adatbazis_kapcsolat->s('nev', 'login', "`nev` IN ('". implode('\', \'', $login_nevek)."') AND `szemelyzet_id`='".TELEPITETT_SZEMELYZET."' AND `aktiv`=1 AND `virtual`=0");
            $felhasznalok = adatbazis2tomb($felhasznalok, 'nev');
        }

        $torzsadatok  = $nevek = $adatbazis_kapcsolat->s('recid nev felh_loginnev sor', 'torzsadat', "`felh_loginnev` IN ('". implode('\', \'', $felhasznalok)."')", 'nev');
        foreach( $torzsadatok as $torzsadat ) {

            // Lekérdezzük, hogy adott felhasználó mobilcsoport-e
            $login = $adatbazis_kapcsolat->s2('szemelyzet_id', 'login', "`nev`='{$torzsadat['felh_loginnev']}'");
            $mobilos = 0;
            if( $login['szemelyzet_id']==MOBILCSOPORT ) {
                $mobilos = 1;
            }
            $adatbazis_kapcsolat->i('beosztas_nevek', array('beosztas_recid', 'torzsadat_recid',    'nev',              'mobilos',  'sor',               'update_usr',                           'insert_dt', 'insert_usr'                            ),
                                                      array($sor['recid'],     $torzsadat['recid'],  $torzsadat['nev'],  $mobilos,   $torzsadat['sor'],   $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',      $_SESSION[get_db_tabla().'teljes_nev'] )
                                    );
        }
        $nevek = $adatbazis_kapcsolat -> s('recid nev torzsadat_recid', 'beosztas_nevek', "`beosztas_recid`='{$sor['recid']}'", 'mobilos, sor, nev');
    }

    foreach( $nevek as $nev ) {

        $naptar_adatok = $adatbazis_kapcsolat->s('*', 'beosztas_naptar', "`beosztas_nevek_recid`='{$nev['recid']}' AND `beosztas_recid`='{$sor['recid']}'");
        $napok_td = '';
        $orak     = 0;
        $munkanapok_szama = 0;
        $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $nev['nev'])).';';
        foreach( $datumok_tomb as $datum ) {
            $class   = '';
            $onclick = '';
            if( $datum['sarga'] ) {
                $class = ' sarga';
            } else {
                $munkanapok_szama++;
            }

            // Van-e esetleg az illetőnek másik szállodában már beosztása?
            $data_jel = $data_szin = $data_ora = $data_tol = $data_ig = $jel = $szin = '';
            $foglalt = $adatbazis_kapcsolat->s2('recid ora beosztas_recid beosztas_muszak_recid', 'beosztas_naptar', "`beosztas_nevek_recid` IN (SELECT `recid` FROM `".get_db_tabla()."beosztas_nevek` WHERE `torzsadat_recid`='{$nev['torzsadat_recid']}' AND `recid`!='{$nev['recid']}') AND `datum`='{$datum['datum']}'");
            if( $foglalt ) {
                $class .= ' foglalt';
                $jel    = '✖';
                $orak  += $foglalt['ora'];
                $muszak   = $adatbazis_kapcsolat->s2('nev telephely_id ora', 'beosztas_muszak', "`recid`='{$foglalt['beosztas_muszak_recid']}'");
                $onclick  = ' onclick="get_illeto_muszakja(\''.$foglalt['recid'].'\')" title="'.getSzallodaNev($muszak['telephely_id']).' '.$muszak['nev'].' '.$muszak['ora'].'"';
            } else {
                $class .= ' szerk';
                foreach ( $naptar_adatok as $adat ) {
                    if( $adat['datum'] == $datum['datum'] ) {
                        $data_jel   = ' data-jel="'.$adat['jel'].'"';
                        $data_szin  = ' data-szin="'.$adat['szin'].'"';
                        $data_ora   = ' data-ora="'.$adat['ora'].'"';
                        $data_tol   = ' data-tol="'.$adat['tol'].'"';
                        $data_ig    = ' data-ig="'.$adat['ig'].'"';
                        $jel        = $adat['jel'];
                        $szin       = ' style="background-color: #'.$adat['szin'].'"';
                        $orak += $adat['ora'];
                        continue;
                    }
                }
            }

            $napok_td .= '<td class="'.$class.'" data-nev_id="'.$nev['recid'].'" data-datum="'.$datum['datum'].'"'.$data_jel.$data_szin.$data_ora.$data_tol.$data_ig.$szin.$onclick.'>'.$jel.'</td>';
            $csv .= '='.csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $jel)).';';
        }

        // A tervórák első körben a munkanapok (nem sárga hátterűek) * az illető aktuális napi munkaidejével
        $napi_munkaido = $adatbazis_kapcsolat->s2('ora', 'torzsadat_napi_munkaido', "`torzsadat_recid`='{$nev['torzsadat_recid']}' AND `datum`<='{$sor['ev']}-{$honap}-01'", 'datum desc', '1');

        $terv_orak = $munkanapok_szama * $napi_munkaido['ora'];

        $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $terv_orak)).';';
        $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $orak)).';';
        $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $orak-$terv_orak)).';';
        $csv .= "\n";
        
        $uj = '';
        if( in_array($nev['torzsadat_recid'], $_SESSION['friss_szemelyek']) ) {
            $uj = ' class="uj"';
        }
        
        $jobb_oldal .= nl2('
            <tr'.$uj.'>
                <td>'.$nev['nev'].'</td>
                '.$napok_td.'
                <td class="nowrap">'.number_format($terv_orak, 0, '', ' ').'</td>
                <td class="nowrap">'.number_format($orak, 0, '', ' ').'</td>
                <td class="nowrap">'.number_format($orak-$terv_orak, 0, '', ' ').'</td>
            </tr>
            ');
    }

    $jobb_oldal .= nl2('
            </table>
        </div>
        ');
    
    // A csak 0-ás cellák elől vegyük le az egyenlődég jelet
    $csv = str_replace('="0"', '0', $csv);
    fajlba_mentes('csv/beosztas_tablazat'.session_id().'.csv',  $csv);
    
    // Kirajzolás után többé nem számít újnak az új
    unset( $_SESSION['friss_szemelyek'] );
    
    return $jobb_oldal;
}

// CSV export fejléc központosítva
function beosztas_csv_export_elokeszit($fajlnev) {
    // CSV export előkészítése
    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Cache-Control: private', false);
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.$fajlnev.'.csv";');
    header('Content-Transfer-Encoding: binary');
    
    $target  = CSV_EXPORT_UTVONAL_1;
    if( !is_dir($target) ) {
        mkdir($target);
    }
    $target .= CSV_EXPORT_UTVONAL_2;
    if( !is_dir($target) ) {
        mkdir($target);
    }
    
    // Régi fájlok törlése
    $fajlok = konyvtar_bejar($target);
    foreach ( $fajlok as $fajl ) {
        if( filemtime($fajl) < (time() - CSV_EXPORT_ELAVULAS) ) {
            unlink($fajl);
        }
    }
    
}