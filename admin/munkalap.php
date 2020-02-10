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
$szoveg                 = 'munkalapot';        // Hibaüzenetkor: Biztosan törlöd a $szoveg?

$szamla_html = '';

// Dokumentumok fülhöz kell majd
$tobb_kep = true;
if( $tobb_kep ) {

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
if( isset($_GET['felvett_mobilcsop']) ) {
    $_SESSION['o_szuro_feltetel']   = 'me|stat_id#op|equal#fe|08#es|#me|mobil#op|equal#fe|'.$_GET['csop'].'#es|#';
    $_SESSION['o_szuro_html']       = '<div class="dtr"><div class="dtc"><b class="zaro nyit">(</b></div><div class="dtc"><input class="mezo" value="stat_id" type="hidden"><input class="op" value="equal" type="hidden"><input class="felt" value="08" type="hidden"><span class="m_mezo">Státusz</span> <span class="m_op">=</span> <span class="m_felt">Felvéve</span> </div><div class="dtc"><b class="zaro zar">)</b></div><div class="dtc"><select style="width: 7rem" class="es_vagy" onchange="osszetett_szuro_mondat()"><option value="es|">ÉS</option><option value="va|">VAGY</option></select></div><div class="dtc"><b class="torol">×</b></div></div><div class="dtr"><div class="dtc"><b class="zaro nyit">(</b></div><div class="dtc"><input class="mezo" value="mobil" type="hidden"><input class="op" value="equal" type="hidden"><input class="felt" value="'.$_GET['felvett_mobilcsop'].'" type="hidden"><span class="m_mezo">Mobilcsoport</span> <span class="m_op">=</span> <span class="m_felt">'. strtoupper($_GET['csop']).'</span> </div><div class="dtc"><b class="zaro zar">)</b></div><div class="dtc"><select style="width: 7rem" class="es_vagy" onchange="osszetett_szuro_mondat()"><option value="es|">ÉS</option><option value="va|">VAGY</option></select></div><div class="dtc"><b class="torol">×</b></div></div>';
}
if( isset($_GET['szures_vege']) ) {
    $_SESSION['o_szuro_feltetel']   = '';
    $_SESSION['o_szuro_html']       = '';
}

// Munkalap készítése
if( isset($_POST['munkalapKeszit']) ) {
    $idk = json_decode($_POST['idk'], true);
    $id  = $idk[1];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( is_array($idk) ) {
        /*// Ellenőrizzük a csoportokat
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
        }*/

        // Ellenőrizzük a szállodákat is
        $bejelentes = $adatbazis_kapcsolat->s2('telephely_id ikt_szam obj_id gep_id hiba_id surgos', 'bejelentes', "`recid`='{$id}'");
        if( $bejelentes === false ) { $db_error = true; }

        $telephely = getSzallodaNev($bejelentes['telephely_id']);

        // Ellenőrizzük a státuszokat is
        foreach ( $idk as $id ) {
            $statusz = $adatbazis_kapcsolat->s2('recid stat_id insert_dt', 'bejelentes_statusz', "`bejelentes_recid`='{$id}'", 'recid desc', '1');
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

                // Ha a bejelntés státusza nyitott, és egyszállodás, és van már munkalapja, akkor nem lehet belőle munkalapot készíteni
                if( $statusz['stat_id'] == BEJELENTES_STAT_NYITOTT ) {

                    // Szálloda modell lekérdezése
                    $akt_bejelentes = $adatbazis_kapcsolat->s2('telephely_id', 'bejelentes', "`recid`='{$id}'");
                    if( $akt_bejelentes === false ) { $db_error = true; }
                    $modell = $adatbazis_kapcsolat->s2('modell', 'telephely', "`id`='{$akt_bejelentes['telephely_id']}'");
                    if( $modell === false ) { $db_error = true; }

                    if(!$modell['modell'] ) {

                        $keszithet_ml_ot = true;
                        $van_kapcs = $adatbazis_kapcsolat->s2('munkalap_id', 'munkalap_kapcs', "`bejelentes_id`='{$id}'", '', '1');
                        if( $van_kapcs === false ) { $db_error = true; }
                        $letezik_munkalap = $adatbazis_kapcsolat->s2('recid', 'munkalap', "`recid`='{$van_kapcs['munkalap_id']}'");
                        if( $letezik_munkalap === false ) { $db_error = true; }

                        if( $letezik_munkalap ){
                            $keszithet_ml_ot = false;
                        }

                        if( !$keszithet_ml_ot ) {
                            echo json_encode(array(
                                'hiba'  => 'Nyitott státuszú bejelentésből nem készíthető újabb munkalap! (Egyszállodás modellben)',
                                'title' => 'Hiba',
                            ));

                            $weblap->leallit();
                            exit();
                        }
                    }

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
        foreach ( $idk as $id ) {
            $isMlap = $adatbazis_kapcsolat->s2('recid munkalap_id', 'munkalap_kapcs', "`bejelentes_id` = '{$id}'", '', '1');
            if( $isMlap === false ) { $db_error = true; }

            if( $isMlap && !$_POST['kerdesek_nelkul'] ) {

                echo json_encode(array(
                    'hiba' => nl2('
                                Bejelentés szám: '.$bejelentes['ikt_szam'].'
                                <br>Hiba helye: '.$telephely.'
                                <br>Bejelentett hiba: '. getErtek('info', 'erteklista', 'recid', $bejelentes['hiba_id']).'
                                <br>
                                <br><span class="piros f25">Ehhez a bejelentéshez<br>már készült munkalap!</span><br>Készít még egy munkalapot?
                                <script>popup_villogtat();</script>
                                    '),
                    'gomb' => '<input class="gomb zold" value="Igen" onclick="szurkit(); location.href = \'munkalap.php?uj&ujkent_bejelentes='.$id.'\'+get_szuro_link_kieg();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
                ));

                $weblap->leallit();
                exit();
            }
        }

        // Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if( isset($_POST['csak_kerdezunk']) && !$_POST['kerdesek_nelkul'] ) {

            echo json_encode(array(
                'hiba' => nl2( 'Bejelentés szám: '.$bejelentes['ikt_szam'].'
                                <br>Hiba helye: '.$telephely.'
                                <br>Bejelentett hiba: '. getErtek('info', 'erteklista', 'recid', $bejelentes['hiba_id']).'
                                <br>
                                <br>Biztos munkalapot készít a kijelölt bejelentésből?
                            '),
                'gomb' => '<input class="gomb zold" value="Igen" onclick="munkalapKeszit();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        // Státuszt folyamatban-ra állítjuk
        foreach ( $idk as $id ) {
            if( addStatusz( 'bejelentes_statusz', $id, BEJELENTES_STAT_FOLYAMATBAN ) === false ) {
                $db_error = true;
            }
        }

        if( !$db_error ) {

            // Szálloda kiszámítása
            $telephely_id = $adatbazis_kapcsolat->s2('telephely_id', 'bejelentes', "`recid` IN (".implode(',', $idk).")", '', '1');
            if( $telephely_id === false ) { $db_error = true; }

            if( $telephely_id ) {
                $telephely_id = $telephely_id['telephely_id'];
            }

            // Munkalap létrehozása
            $recid = munkalap_letrehoz(MUNKALAP_STAT_NYITOTT, $telephely_id, MLTIPUS_HIBAELHARITAS, $bejelentes['obj_id'], $bejelentes['gep_id'], $bejelentes['surgos'], '', '', '', '', '', $bejelentes['hiba_id']);
            if( $recid === false ) { $db_error = true; }

            // Hozzákapcsoljuk a bejelentéseket (valójában nincs is olyan eset, hogy több bejelentés egy munkalap, csak egy bejelentéshez tartozhat több munkalap is), de azért meghagytuk a régi ciklust
            foreach ( $idk as $id ) {
                $sql = $adatbazis_kapcsolat -> i('munkalap_kapcs',
                                                    array('munkalap_id', 'bejelentes_id',  'update_usr',                            'insert_dt',    'insert_usr',                           ),
                                                    array($recid,        $id,              $_SESSION[get_db_tabla().'teljes_nev'],  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  )
                                                );
                if( $sql === false ) { $db_error = true; }

                // Ekkor a csatolt fájlokat is átmásoljuk
                if( bejelentes_dok_munkalapba_masol($id, $recid)===false ){
                    $db_error = true;
                }

            }

            // Logolás
            $muvelet = $_SESSION[get_db_tabla().'teljes_nev'].' új munkalapot hozott létre a '.$bejelentes['ikt_szam'].' bejelentésből.';
            $sql = $adatbazis_kapcsolat -> i(
                                                'log',
                                                array( 'felh_id',                               'nev',       'valtozas', 'ip'                   ),
                                                array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,    $muvelet,   $_SERVER['REMOTE_ADDR'])
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

// Projekt táblázat betöltése
if( isset($_POST['get_projekt_tabla']) ) {
    
    $html = '';

    // Rendezés beállítása
    $join  = ' JOIN `'.get_db_tabla().'telephely` ON `'.get_db_tabla().'projektkezeles`.`telephely_id`=`'.get_db_tabla().'telephely`.`id`';
    switch ( $_POST['order'] ) {
        case 'jkolt'    : $order = '`'.get_db_tabla().'projektkezeles`.`jkolt`, `'.get_db_tabla().'telephely`.`nev`';         $bkolt_selected = ' selected';  break;
        case 'tipus'    : $order = '`'.get_db_tabla().'projektkezeles`.`tipus`, `'.get_db_tabla().'projektkezeles`.`jkolt`';  $tipus_selected = ' selected';  break;
        default         : $order = '`'.get_db_tabla().'telephely`.`nev`,        `'.get_db_tabla().'projektkezeles`.`jkolt`';                                  break;
    }

    $prek = $adatbazis_kapcsolat->s('
                                        `'.get_db_tabla().'projektkezeles`.`recid`,
                                        `'.get_db_tabla().'projektkezeles`.`ikt_szam`,
                                        `'.get_db_tabla().'projektkezeles`.`nev`,
                                        `'.get_db_tabla().'projektkezeles`.`tipus`,
                                        `'.get_db_tabla().'projektkezeles`.`stat_id`,
                                        `'.get_db_tabla().'projektkezeles`.`jkolt`,
                                        `'.get_db_tabla().'projektkezeles`.`pfelelos`,
                                        `'.get_db_tabla().'projektkezeles`.`telephely_id`,
                                        `'.get_db_tabla().'projektkezeles`.`insert_dt`
                                            ',
                                        'projektkezeles',
                                        "`telephely_id`='{$_POST['telephely_id']}'", $order, '', '', '', $join);

    $i = 1;
    foreach ( $prek as $pr ) {

        // Szálloda
        $szalloda = $adatbazis_kapcsolat->s2('nev', 'telephely', "`id`='{$pr['telephely_id']}'");
        
        // Tipus
        require_once 'selectek.php';
        foreach ( $select_pri_tipus as $tip ) {
            if( $tip['value'] == $pr['tipus'] ) {
                $tipus = $tip['name'];
            }
        }
        if( !$pr['tipus'] ) {
            $tipus = '-';
        }

        $pfelelos_neve = getErtek('teljes_nev', 'login', 'recid', $pr['pfelelos']);
        
        $html .= '
            <tr class="kijSor" data-i="'.$i.'" data-recid="'.$pr['recid'].'" data-ikt_szam="'.$pr['ikt_szam'].'" data-felelos="'.$pr['pfelelos'].'">
                <td class="l" style="padding: 0 0.5rem">
                    '.$szalloda['nev'].'
                    <div class="dn pfelelos_txt">'.$pfelelos_neve.'</div>
                    <div class="dn pfelelos_txt nev"><a href="projektkezeles.php?id=x&amp;modosit&amp;mezo=ikt_szam&amp;op=equal&amp;felt='.$pr['ikt_szam'].'" target="_blank">'.$pr['nev'].'</a></div>
                </td>
                <td class="l" style="padding: 0 0.5rem">'.$pr['ikt_szam'].'</td>
                <td class="l" style="width: 30rem; padding: 0 0.5rem">'.$pr['nev'].'</td>
                <td class="l" style="padding: 0 0.5rem">'.$tipus.'</td>
                <td class="r nowrap" style="padding: 0 1rem">'.number_format($pr['jkolt'], 0, '', ' ').'</td>
                <td style="padding: 0 1rem">'.datum_kiir($pr['insert_dt']).'</td>
            </tr>
            ';
        $i++;
    }

    if( $html ) {

        // Táblázat rendezhető: szálloda + becsült költség, becsült költség + szálloda, típus + becsült költség. Ezek legyenek egy lenyílóból kiválaszthatóak.
        $html = '
            Rendezés:
            <select onchange="get_projekt_tabla(this.value)">
                <option>szálloda + jóváhagyott keret</option>
                <option value="jkolt"'.$bkolt_selected.'>jóváhagyott keret + szálloda</option>
                <option value="tipus"'.$tipus_selected.'>típus + jóváhagyott keret</option>
            </select>
            <div style="max-height: 40rem; overflow-y: auto; overflow-x: none;">
                <table class="csikos v2 kijCont csak_egy_kijelolheto" id="prkezelesek" style="cursor: default;">
                <thead>
                <tr>
                    <th>
                        Szálloda
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prkezelesek tbody\'), this.value, 1)">
                    </th>
                    <th>
                        Projekt szám
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prkezelesek tbody\'), this.value, 2)">
                    </th>
                    <th>
                        Név
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prkezelesek tbody\'), this.value, 3)">
                    </th>
                    <th>
                        Típus
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prkezelesek tbody\'), this.value, 4)">
                    </th>
                    <th>
                        Jóváhagyott keret ('.get_penznem().')
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prkezelesek tbody\'), this.value, 5)">
                    </th>
                    <th>
                        Létrehozás dátuma
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prkezelesek tbody\'), this.value, 6)">
                    </th>
                </tr>
                </thead>
                <tbody>
                    '.$html.'
                </tbody>
                </table>
            </div>
            <script>
            ';
    } else {
        $html = '<script>popupRejt(); setTimeout(function(){ popupZar(); alert2(\'Nem található projekt!\', \'\', \'Hiba\'); }, 10);</script>';
    }

    echo json_encode(
            array( 'html' => nl2($html) )
            );

    $weblap->leallit();
    exit();
}

// Projekt táblázat betöltése
if( isset($_POST['get_projekt_tabla_hozzaad']) ) {

    require_once 'selectek_szemelyzet.php';
    
    $html       = '';
    $szemelyzet = '';
    
    $munkalap = $adatbazis_kapcsolat->s2('ikt_szam text telephely_id szemelyzet_id szallito_id', 'munkalap', "`recid`='{$_POST['recid']}'");
    if( $munkalap['szemelyzet_id'] ) {
        foreach( $select_szemelyzet as $szem ) {
            if( $munkalap['szemelyzet_id'] == $szem['value'] ) {
                $szemelyzet = $szem['name'];
                break;
            }
        }
        if( $munkalap['szemelyzet_id'] == KULSO_KIVITELEZO ) {
            $szemelyzet .= ': '. getErtek('nev', 'szallito', 'id', $munkalap['szallito_id']);
        }
    }
    
    $html .= nl2('
        Munkalap száma: <b>'.$munkalap['ikt_szam'].'</b>
        <br>Leírás: <b>'.htmlspecialchars_decode( resz_string($munkalap['text'], 30) ).'</b>
        <br>Szálloda: <b>'. getSzallodaNev($munkalap['telephely_id']).'</b>
        <br>Hozzárendelt személyzet: <b>'.$szemelyzet.'</b>
        ');

    $prek = $adatbazis_kapcsolat->s('`recid`, `ikt_szam`, `nev`, `tipus`, `stat_id`, `jkolt`, `pfelelos`, `telephely_id`, `insert_dt`',
                                        'projektkezeles',
                                        "`telephely_id`='{$_POST['telephely_id']}' AND `pfelelos`='{$_SESSION[get_db_tabla().'login_recid']}'");
    $i = 1;
    foreach ( $prek as $pr ) {
        
        $html .= '
            <tr class="kijSor" data-i="'.$i.'" data-recid="'.$pr['recid'].'" data-ikt_szam="'.$pr['ikt_szam'].'" data-felelos="'.$pr['pfelelos'].'">
                <td class="l">'.$pr['ikt_szam'].'</td>
                <td class="l">'.$pr['nev'].'</td>
            </tr>
            ';
        $i++;
    }

    if( $i > 1 ) {

        $html = '
            <div style="max-height: 40rem; overflow-y: auto; overflow-x: none;">
                <table class="csikos v2 kijCont csak_egy_kijelolheto" id="prkezelesek" style="cursor: default;">
                <thead>
                <tr>
                    <th>
                        Projekt szám
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prkezelesek tbody\'), this.value, 2)">
                    </th>
                    <th>
                        Név
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prkezelesek tbody\'), this.value, 3)">
                    </th>
                </tr>
                </thead>
                <tbody>
                    '.$html.'
                </tbody>
                </table>
            </div>
            <script>
            ';
    } else {
        $html = '<script>popupRejt(); setTimeout(function(){ popupZar(); alert2(\'Nem található projekt!\', \'\', \'Hiba\'); }, 10);</script>';
    }

    echo json_encode(
            array( 'html' => nl2($html) )
            );

    $weblap->leallit();
    exit();
}

// Munkalap hozzáadása projekthez
if( isset($_POST['projekt_hozzaad']) ) {
    
    $pr = $adatbazis_kapcsolat->s2('recid', 'projektkezeles', "`ikt_szam`='{$_POST['pr_szam']}'");
    projekt_munkalap_osszekot($pr['recid'], $_POST['recid']);    
    
    echo json_encode(array('siker' => 1));
    
    $weblap->leallit();
    exit();
}

// Munkalapon projekt törlése
if( isset($_POST['projekt_torelese']) ) {
    
    $adatbazis_kapcsolat->u('munkalap', array('pr_szam'), array(''), "`recid`='{$_POST['recid']}'");
    
    echo json_encode(array('siker' => 1));
    
    $weblap->leallit();
    exit();
}

// Új munkalap létrehozása bejelentésből
if( $_GET['ujkent_bejelentes'] ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $bejelentes = $adatbazis_kapcsolat -> s2('*', 'bejelentes', "`recid`='{$_GET['ujkent_bejelentes']}'");
    if( $bejelentes === false ) { $db_error = true; }

    // Munkalap létrehozása
    $uj_id = munkalap_letrehoz(MUNKALAP_STAT_NYITOTT, $bejelentes['telephely_id'], MLTIPUS_HIBAELHARITAS, $bejelentes['obj_id'], $bejelentes['gep_id'], $bejelentes['surgos'], $bejelentes['hiba_text'], '', '', '', '', $bejelentes['hiba_id']);
    if( $uj_id === false ) { $db_error = true; }

    // Bejelentés hozzákapcsolása
    $sql = $adatbazis_kapcsolat->i('munkalap_kapcs',   array('munkalap_id',  'bejelentes_id'           ),
                                                       array($uj_id,         $_GET['ujkent_bejelentes'])
                                    );
    if( $sql === false ) { $db_error = true; }

    // Bejelentés státuszát is léptetjük, mert van hogy már volt munkalapja, csak sztornózták és nyitottá vált a bejeletnés, és ekkor is folyamatbanra kell tenni
    if( addStatusz('bejelentes_statusz', $_GET['ujkent_bejelentes'], BEJELENTES_STAT_FOLYAMATBAN) === false){
        $db_error = true;
    }

    // Dokumentum átmásolása
    bejelentes_dok_munkalapba_masol($_GET['ujkent_bejelentes'], $uj_id);

    // Logolás
    $muvelet = $_SESSION[get_db_tabla().'teljes_nev'].' új munkalapot hozott létre a '.$bejelentes['ikt_szam'].' bejelentésből.';
    $sql = $adatbazis_kapcsolat -> i(
                                        'log',
                                        array( 'felh_id',                               'nev',       'valtozas', 'ip'                   ),
                                        array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,    $muvelet,   $_SERVER['REMOTE_ADDR'])
                                    );
    if( $sql === false ) { $db_error = true; }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        header('Location:munkalap.php?modosit&id='.$uj_id);
    } else {
        mysqli_rollback($db_kapcs);
    }


    $weblap->leallit();
    exit();
}

// Új feltöltéskor ellenőrizzük, hogy a szálloda és projektszám megegyezik-e
if( isset($_POST['telephely_ell']) ) {

    // Ha nem garanciális a típus, akkor egyből mehetünk is tovább
    if( $_POST['tipus_id'] != MLTIPUS_GARANCIALIS ) {
        echo json_encode( array(
                                'mehet'  => '1'
                            ) );
        $weblap->leallit();
        exit();
    }
    
    $projekt = $adatbazis_kapcsolat->s2('telephely_id', 'projektkezeles', "`ikt_szam`='{$_POST['pr_szam']}'");
    if( $projekt['telephely_id'] != $_POST['telephely_id'] ) {
        
        echo json_encode( array(
                                'hiba'  => 'A projektszám nem megfelelő szállodához tartozik!',
                                'title' => 'Hiba',
                                'focus' => 'telephely_id',
                            ) );
        
    } else {
        
        echo json_encode( array(
                                'mehet'  => '1'
                            ) );
        
    }
    
    $weblap->leallit();
    exit();
}

// Munkalap törlés előtti kérdés
if( isset($_POST['get_ml_torles']) ) {

    $munkalap = $adatbazis_kapcsolat->s2('ikt_szam', 'munkalap', "`recid`='{$_POST['id']}'");
    
    echo json_encode(array(
        'kerdes'  => 'Biztosan törli a '.$munkalap['ikt_szam'].' munkalap számú munkalapot?'
    ));
    
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

        foreach ( $idk as $id ) {
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
                'title' => 'Hiba',
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

    if( $recid ) {

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
                'title' => 'Hiba',
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
    $varh_erk       = str_replace(array('.'), array('-'), $_POST['varh_erk']);
    $emailkuldes_megj = '';
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid && $szallito_id ) {

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
        
        // Ha van levélküldés, akkor ellenőrizzük a többi mezőt is
        if($_POST['email']){
            if( !trim($_POST['cimzett']) ) {
                echo json_encode(array(
                    'hiba'  => 'Kérem adja meg a címzett email címét!',
                    'title' => 'Hiba',
                    'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="$(\'#email_kuldessel\').val(0); alert2();">',
                    'focus' => 'pop_cimzett',
                ));

                $weblap->leallit();
                exit();
            }
            
            // Címzett mezőben levő adat ellenőrzése
            $cimzett_hibak = is_valid_mail_tobb($_POST['cimzett'], 'Hibaüzenettel');
            if( $cimzett_hibak ) {
                echo json_encode(array(
                    'hiba'  => $cimzett_hibak,
                    'title' => 'Hiba',
                    'focus' => 'pop_cimzett',
                ));

                $weblap->leallit();
                exit();
            }
            // Másolat mezőben levő adat ellenőrzése
            $cimzett_hibak = is_valid_mail_tobb($_POST['masolat'], 'Hibaüzenettel');
            if( $cimzett_hibak ) {
                echo json_encode(array(
                    'hiba'  => $cimzett_hibak,
                    'title' => 'Hiba',
                    'focus' => 'pop_masolat',
                ));

                $weblap->leallit();
                exit();
            }
            
            // Címzettek tömb előállítása
            $mailcimek = explode(',', $_POST['cimzett']);
            $mailcimek = array_values( array_filter($mailcimek) );
            
            // Másolatot kapnak tömb előállítása
            $masolatok = explode(',', $_POST['masolat']);
            array_push($masolatok, get_mszk_mail(), get_mszk_mail2()); // Így az MSZK-k is kapnak értesítő levelet
            $masolatok = array_values( array_filter($masolatok) );

            // Tárgy ellenőrzése
            if( !trim($_POST['targy']) ) {
                echo json_encode(array(
                    'hiba'  => 'Kérem töltse ki az email tárgyát!',
                    'title' => 'Hiba',
                    'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="$(\'#email_kuldessel\').val(0); alert2();">',
                    'focus' => 'pop_targy',
                ));

                $weblap->leallit();
                exit();
            }
            
            // Üzenet ellenőrzése
            if( !trim($_POST['uzenet']) ) {
                echo json_encode(array(
                    'hiba'  => 'Kérem töltse ki a email üzenetét!',
                    'title' => 'Hiba',
                    'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="$(\'#email_kuldessel\').val(0); alert2();">',
                    'focus' => 'pop_uzenet',
                ));

                $weblap->leallit();
                exit();
            }
            
            $szallito = $adatbazis_kapcsolat->s2('nev', 'szallito', "`id`='{$szallito_id}'");
            if( $szallito === false ) { $db_error = true; }
 
            // Attól függően, hogy melyik rendszerben vagyunk, kap a tárgy/üzenet egy [Teszt] előtagot.
            $kieg = get_mail_teszt_felirat();
            $targy_kieg = $kieg[0];
            $uzi_kieg   = $kieg[1];
            
            // Csatolmányok összeszdése
            $mappa = $adatbazis_kapcsolat->s2('ikt_szam', 'munkalap', "`recid`='{$recid}'");
            $utvonal = '../'.get_utvonal().'munkalap/'.str_replace('/', '_', $mappa['ikt_szam']).'/email/*';
            $fajlok_mailnek = array();
            $fajlok = glob($utvonal);

            $sikeres_kuldes = level_kuldes_osszetettebb(get_nev(), get_mail(), $mailcimek, $mailcimek, $masolatok, $masolatok, $r_masolatok, $r_masolatok, $targy_kieg.$_POST['targy'], $uzi_kieg.nl2br2($_POST['uzenet']), $fajlok, 'levélpapíros');
            if( $sikeres_kuldes ) {
                mysqli_rollback($db_kapcs);
                echo json_encode(array(
                    'hiba'  => 'Levélküldés közben hiba történt! '.$sikeres_kuldes,
                    'title' => 'Hiba'
                ));

                $weblap->leallit();
                exit();
                
            } else {

                $cimzettek          = implode(', ', $mailcimek);
                $masolatot_kapott   = '';
                $emailkuldes_megj .= 'Megrendelés elküldve.'."\n".'Címzettek: '.$cimzettek.' ';
                if($masolatok){
                    $masolatot_kapott   = implode(', ', $masolatok);
                    $emailkuldes_megj .= "\n".'Másolatot kaptak: '.$masolatot_kapott.' ';
                }
                $emailkuldes_megj .= "\n";
                // E-mail küldés logolása
                $email_recid = $adatbazis_kapcsolat->i('munkalap_email',    array('munkalap_recid', 'szallito_id', 'cimzett',  'masolat',           'targy',                     'text',                                'insert_dt', 'insert_usr'                           ),
                                                                            array($recid,           $szallito_id,  $cimzettek, $masolatot_kapott,   $targy_kieg.$_POST['targy'], $uzi_kieg.nl2br2($_POST['uzenet']),    'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'] )
                                            );
                if( $email_recid === false ) { $db_error = true; }

                // Fájlok mozgatása
                if( csatolmanyok_eltesz($fajlok, $utvonal, $email_recid, $recid, 'munkalap') === false ) {
                    $db_error = true;
                }

                // Mail elküldve pipa bekapcsolása
                $sql = $adatbazis_kapcsolat->u('munkalap', array('mail_ment'), array('1'), "`recid`='{$recid}'");
                if( $sql === false ) { $db_error = true; }
                
            }
            
            
        // Ha nincs levélküldés, akkor rákérdezünk, hogy biztosan nem szerettek volna levelet küldeni?
        }elseif( !$_POST['mehet'] ) {
            echo json_encode(array(
                'hiba'  => 'Nem küldött email megrendelést.<br>Folytatja?',
                'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#mehet_mail_nelkul\').val(\'1\'); $(\'#munkalapKulso\').submit();"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">',
                'title' => 'Kérdés',
            ));
            $weblap->leallit();
            exit();
        }

        // Külső kivitelező hozzárendelése
        $sql = $adatbazis_kapcsolat -> u('munkalap',
                                            array('szemelyzet_id',   'szallito_id', 'varh_erk', 'visszaadva', 'direktbe',   'update_usr'                           ),
                                            array( KULSO_KIVITELEZO, $szallito_id,  $varh_erk,  0,            1,            $_SESSION[get_db_tabla().'teljes_nev'] ),
                                            "`recid` = '{$recid}'"
                                        );
        if( $sql === false ) { $db_error = true; }

        $memo = 'Kivitelező: '.getErtek('nev', 'szallito', 'id', $szallito_id)."\n".$emailkuldes_megj;
        if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_HOZZARENDELT, '', $memo ) ) {
            $db_error = true;
        }
        // A memót a megjegyzésbe is beírjuk, nem csak a státuszsorba
        if( addMegj('munkalap', $recid, $memo) === false ) {
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
    } else {
        echo json_encode(array(
            'hiba'  => 'Kérem válasszon egy külső kivitelezőt!',
            'title' => 'Hiba',
        ));
    }

    $weblap->leallit();
    exit();
}

// Munkalap személyzet előkészítése
if( isset($_POST['munkalapElokeszit']) ) {

    $recid          = $_POST['recid'];
    $szemelyzet_id  = $_POST['szemelyzet_id'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid && $szemelyzet_id ) {

        $munkalap = $adatbazis_kapcsolat->s2('stat_id telephely_id', 'munkalap', "`recid` = '{$recid}'");
        if( $munkalap === false ) { $db_error = true; }

        // Státusz ellenőrzése
        if( $munkalap['stat_id'] != MUNKALAP_STAT_NYITOTT ) {
            echo json_encode(array(
                'hiba'  => 'Csak nyitott státuszú munkalapokat lehet előkészíteni!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzése
        if( !_jog(JOG_MUNKALAP_NYITOTT_KEZEL) ) {
            echo json_encode(array(
                'hiba'  => 'Nincs joga az előkészítéshez!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // van-e felhasználó, aki adott szerepkörben és szállodára rendelkezik az előkészítés joggal
        $van_felh = false;
        $loginnevek = $adatbazis_kapcsolat->s('nev', 'telephely_kapcs', "`telephely_id`='{$munkalap['telephely_id']}'");
        $loginnevek = adatbazis2tomb($loginnevek, 'nev');
        if($loginnevek){
            if( $adatbazis_kapcsolat->s('recid', 'login', "`szemelyzet_id`='{$szemelyzet_id}' AND `nev` IN ('". implode("', '", $loginnevek)."') AND SUBSTR( `flags`, ".(JOG_MUNKALAP_KULSOS_ELOKESZIT+1).", 1 )=1") ){
                $van_felh = true;
            }
        }
        if( !$van_felh ) {
            $telephely = $adatbazis_kapcsolat->s2('nev', 'telephely', "`id`='{$munkalap['telephely_id']}'");
            echo json_encode(array(
                'hiba'  => a_az($telephely['nev'], 'nagybetű').' '.$telephely['nev'].' szálloda nem rendelkezdik a választott személyzettel!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Személyzet hozzárendelése
        $sql = $adatbazis_kapcsolat -> u('munkalap',
                                            array('szemelyzet_id',   'update_usr'                           ),
                                            array($szemelyzet_id,    $_SESSION[get_db_tabla().'teljes_nev'] ),
                                            "`recid` = '{$recid}'"
                                        );
        if( $sql === false ) { $db_error = true; }

        if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_ELOKESZITES_ALATT ) ) {
            $db_error = true;
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            // Levélküldés
            $hiba = '';
            $valasz = level_felkeszit_es_kuld('munkalap', $recid, ERT_MUNK_KK_ELOKESZIT_KOR, 'munkalapElokeszit', '_jog'.JOG_MUNKALAP_KULSOS_ELOKESZIT);
            if( substr($valasz, 0, 4)=='Hiba' ){
                $hiba .= $valasz;
            }
            if($hiba){
                echo json_encode(array(
                    'hiba'  => $hiba,
                    'title' => 'Hiba',
                ));
                $weblap->leallit();
                exit();
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
    } else {
        echo json_encode(array(
            'hiba'  => 'Kérem válasszon egy hozzárendelt személyt!',
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
    if( $munkalap['stat_id'] != MUNKALAP_STAT_HOZZARENDELT ) {
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
                                            array('munkalap_id', 'loginnev',                        'insert_dt', 'insert_usr',                              'update_usr'                           ),
                                            array($recid,        $_SESSION[get_db_tabla().'user'],  'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'],    $_SESSION[get_db_tabla().'teljes_nev'] )
                                        );
        if( $sql === false ) { $db_error = true; }

        // Van-e munkalaphoz tartozó bejelentés, és az nyitott-e? Ha igen, akkor folyamatbanra állítjuk.
        $bejelentes = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$recid}'");
        if( $bejelentes === false ) { $db_error = true; }
        $bejelentes = $adatbazis_kapcsolat->s2('recid stat_id', 'bejelentes', "`recid`='{$bejelentes['bejelentes_id']}'");
        if( $bejelentes === false ) { $db_error = true; }
        if($bejelentes['stat_id']==BEJELENTES_STAT_NYITOTT){
            if( addStatusz( 'bejelentes_statusz', $bejelentes['recid'], BEJELENTES_STAT_FOLYAMATBAN ) === false ) {
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


// Munkalap készre jelentése
if( isset($_POST['munkalapKeszre']) ) {

    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {

        $munkalap = $sor = $adatbazis_kapcsolat->s2('stat_id uf_szam szemelyzet_id telephely_id tipus_id_back', 'munkalap', "`recid` = '{$recid}'", '', '1');
        if( $munkalap === false ) { $db_error = true; }

        $telephely = $adatbazis_kapcsolat->s2('modell', 'telephely', "`id`='{$munkalap['telephely_id']}'");
        if( $telephely === false ) { $db_error = true; }

        // Státusz ellenőrzés
        if( $munkalap['stat_id'] != MUNKALAP_STAT_HOZZARENDELT && $munkalap['stat_id'] != MUNKALAP_STAT_FELVEVE ) {
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
                ( $sor['stat_id']==MUNKALAP_STAT_HOZZARENDELT && $sor['szemelyzet_id']==KULSO_KIVITELEZO && ( _jog(JOG_MUNKALAP_KEZEL_KULSO) || ( _jog(JOG_MUNKALAP_SAJAT_KEZEL) && $_SESSION[get_db_tabla().'szemelyzet_id']==KULSO_KIVITELEZO ) ) ) ||
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
        if( $sor['stat_id']==MUNKALAP_STAT_HOZZARENDELT ){
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

        // Ha a tételen nincs feladat, akkor hibaüzenet:
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
            $tabla = 'utemezett_feladatok';
            if($munkalap['tipus_id_back']==MLTIPUS_RENDSZERES_K){
                $tabla = 'rendszeres_karbantartas';
            }
            if($munkalap['tipus_id_back']==MLTIPUS_ESETI_FENNT){
                $tabla = 'eseti_fenntartas';
            }
            $sql = $adatbazis_kapcsolat->u($tabla, array('esem_d'), array('NOW()'), "`id`='{$munkalap['uf_szam']}'");
            if( $sql === false ) { $db_error = true; }
        }

        // Az utolsó tételnél frissítjük az átadta dátumot
        $sql = $adatbazis_kapcsolat->u('munkalap_tetel', array('atadta_dt'), array('NOW()'), "`recid`='{$utolso_tetel['recid']}'");
        if( $sql === false ) { $db_error = true; }
        // Kész vagy igazolásra vár státuszra léptetjük attól függően, hogy milyen személyzettípusról van szó
        $ig_var_statba_leptet = array(MOBILCSOPORT, KULSO_KIVITELEZO);
        if( in_array($munkalap['szemelyzet_id'], $ig_var_statba_leptet) ){
            // Készre állítjuk a munkalapot
            if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_IGAZOLASRA_VAR ) ) {
                $db_error = true;
            }

        } else {
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
                        $minimumkesz_statuszok = array(MUNKALAP_STAT_LEZARASRA_VAR, MUNKALAP_STAT_IGAZOLASRA_VAR, MUNKALAP_STAT_VIZSGALAT_ALATT, MUNKALAP_STAT_SZTORNO, MUNKALAP_STAT_LEZART);
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

        // Ha a munkalapnak nincs bejelentése, akkor munkalap ID-ját adjunk át, de ezt egy "m" betűvel jelezzük is
        } else {
            array_push($mail_megy, 'm'.$recid);
        }


        if( !$db_error ) {

            // Levélküldés
            foreach($mail_megy as $bejelentes_id){
                $valasz = level_felkeszit_es_kuld('bejelentes', $bejelentes_id, ERT_KESZRE_JELENTESKOR, 'munkalapKeszre', 'bejelento');
                if( substr($valasz, 0, 4)=='Hiba' ){
                    echo json_encode(array(
                        'hiba'  => $valasz,
                        'title' => 'Hiba',
                    ));
                    $weblap->leallit();
                    exit();
                }
            }

            // Ha külsős, akkor más levelet küldünk
            if( $munkalap['szemelyzet_id']==KULSO_KIVITELEZO || $munkalap['szemelyzet_id']==MOBILCSOPORT ){

                $valasz = level_felkeszit_es_kuld('munkalap', $recid, ERT_MUNK_KESZRE_KULSO_MOBIL, 'munkalapKeszreKulso', '_jog'.JOG_MUNKALAP_IGAZOLAS);
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

        $munkalap = $adatbazis_kapcsolat->s2('stat_id ikt_szam szemelyzet_id telephely_id', 'munkalap', "`recid` = '{$recid}'");
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
                'hiba' => nl2('
                                Munkalap szám: '.$munkalap['ikt_szam'].'
                                <br>Szálloda: '. getErtek('nev', 'telephely', 'id', $munkalap['telephely_id']).'
                                <br>
                                <br>Biztosan lezárja a munkalapot?
                                '),
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
        $munkalap = $adatbazis_kapcsolat->s2('stat_id, telephely_id, szallito_id, jh1_login, jh1_szerep, jh2_login, ikt_szam, jh_ert, becs_ertek', 'munkalap', "`recid` = '{$recid}'", '', '1');
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
        
        // Csak bizonyos összegig lehet jóváhagyni
        $login = $adatbazis_kapcsolat->s2('jh_ft_ig', 'login', "`nev`='{$_SESSION[get_db_tabla().'user']}'");
        if( $login === false ) { $db_error = true; }
        if( $login['jh_ft_ig']<$munkalap['jh_ert'] ) {
            echo json_encode(array(
                'hiba'  => 'Önnek '.number_format($login['jh_ft_ig'], 0, '', ' ').' '.get_penznem().' értékig van jóváhagyási joga!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }
        
        $becs_ertek_text = '';
        if( $munkalap['becs_ertek'] ) {
            $becs_ertek_text = nl2('
                                    <br>
                                    <span class="piros">
                                        Becsült érték.
                                        <br>A végleges költség utólagos kalkulációval lesz számítva!
                                    </span>
                                ');
        }

        // Ha a 2. jóváhagyásnál járunk, akkor azt is ellenőrizni kell, hogy két azonos szerepkörű ne tudja jóváhagyni
        if( $munkalap['jh1_login'] ){
            // MSZK vezető és Szálloda igazgatónak is jóvá kell hagynia
            if( $munkalap['jh1_szerep'] == $_SESSION[get_db_tabla().'szemelyzet_id'] ){
                echo json_encode(array(
                    'hiba' => nl2('
                                    Munkalap szám: '.$munkalap['ikt_szam'].'
                                    <br>Szálloda: '. getErtek('nev', 'telephely', 'id', $munkalap['telephely_id']).'
                                    <br>Külső kivitelező: '. getErtek('nev', 'szallito', 'id', $munkalap['szallito_id']).'
                                    <br>Jóváhagyott érték: '. number_format($munkalap['jh_ert'], 0, '', ' ').' '. get_penznem().' + Áfa
                                    <br>Szerepkör: '.getSzerepkorNev($_SESSION[get_db_tabla().'szemelyzet_id']).'
                                    '.$becs_ertek_text.'
                                    <br>
                                    <br>Ezzel a szerepkörrel már jóvá lett hagyva a munkalap!
                                    '),
                    'title' => 'Hiba',
               ));

                $weblap->leallit();
                exit();
            }
        }

        // Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if (isset($_POST['csak_kerdezunk'])) {

            echo json_encode(array(
                'hiba' => nl2(' Munkalap szám: '.$munkalap['ikt_szam'].'
                                <br>Szálloda: '. getErtek('nev', 'telephely', 'id', $munkalap['telephely_id']).'
                                <br>Külső kivitelező: '. getErtek('nev', 'szallito', 'id', $munkalap['szallito_id']).'
                                <br>Jóváhagyott érték: '. number_format($munkalap['jh_ert'], 0, '', ' ').' '. get_penznem().' + Áfa
                                '.$becs_ertek_text.'
                                <br>
                                <br>Biztosan jóváhagyja?
                               '),
                'gomb' => '<input class="gomb zold" value="Igen" onclick="szurkit(); munkalapKulsosJovahagy();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        // Ha még nincs meg az első jóváhagyó, akkor csak annyit teszünk, hogy rögzítjük az elsőt.
        $masodik_jovahagyo = '';
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
        } else {

            $masodik_jovahagyo = '1';

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
            
            // Ha már a 2. jóváhagyóról van szó, akkor levelet is küldünk
            if( $munkalap['jh1_login'] ){

                $hiba = '';
                $valasz = level_felkeszit_es_kuld('munkalap', $recid, ERT_MUNK_KK_JH_UTAN, 'munkalapKkJhUtan', '_jog'.JOG_MUNKALAP_KULSOS_MEGREND);
                if( substr($valasz, 0, 4)=='Hiba' ){
                    $hiba .= $valasz;
                }
                if($hiba){
                    echo json_encode(array(
                        'hiba'              => $hiba,
                        'title'             => 'Hiba',
                    ));
                    $weblap->leallit();
                    exit();
                }
            }

            echo json_encode(array(
                'statusz'           => 'siker',
                'masodik_jovahagyo' => $masodik_jovahagyo,
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

// Popup tartalmába kiírjuk a ML adatait
if( isset($_POST['get_ml_adatok']) ) {

    $munkalap = $adatbazis_kapcsolat->s2('ikt_szam telephely_id jh_ert szallito_id becs_ertek jh1_szerep jh2_szerep', 'munkalap', "`recid`='{$_POST['recid']}'");
    
    // Ha adott szerepkörrel már jóváhagyták volna
    if($munkalap['jh1_szerep']==$_SESSION[get_db_tabla().'szemelyzet_id'] || $munkalap['jh2_szerep']==$_SESSION[get_db_tabla().'szemelyzet_id']){
        echo json_encode(array(
            'hiba'  => 'Az Ön szerepkörével ezt a munkalapot már elfogadták, így nem utasítható el a külső kivitelező!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }

    $becs_ertek_text = '';
    if( $munkalap['becs_ertek'] ) {
        $becs_ertek_text = nl2('
                                <br>
                                <span class="piros">
                                    Becsült érték.
                                    <br>A végleges költség utólagos kalkulációval lesz számítva!
                                </span>
                            ');
    }
    
    echo json_encode(array(
        'html'  => nl2('
                    <b style="line-height: 2.3rem">
                        Munkalap szám: '.$munkalap['ikt_szam'].'
                        <br>Szálloda: '. getErtek('nev', 'telephely', 'id', $munkalap['telephely_id']).'
                        <br>Külső kivitelező: '.getErtek('nev', 'szallito', 'id', $munkalap['szallito_id']).'
                        <br>Jóváhagyott érték: '.number_format($munkalap['jh_ert'], 0, '', ' ').' '. get_penznem().' + Áfa
                        '.$becs_ertek_text.'
                    </b>'
                )
    ));


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
        $munkalap = $adatbazis_kapcsolat->s2('stat_id szallito_id jh1_szerep jh2_szerep', 'munkalap', "`recid` = '{$recid}'", '', '1');
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
                'hiba'  => 'Kérem adja meg az indoklást!',
                'gomb'  => '<input type="button" class="link gomb" value="Ok" onclick="alert2(); $(\'#elutasitas_memo\').focus();">',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }
        
        // Szerepkör ellenőrzése. Ha azonos a szerepköröm bármelyik jóváhagyóval, akkor hiba
        if($munkalap['jh1_szerep']==$_SESSION[get_db_tabla().'szemelyzet_id'] || $munkalap['jh1_szerep']==$_SESSION[get_db_tabla().'szemelyzet_id']){
            echo json_encode(array(
                'hiba'  => 'Az Ön szerepkörével már jóváhagyták a munkalapot, így elutasítani már nem lehet!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Visszaadva pipa bepipálása, személyzetek és jóváhagyók kiszedése
        $sql = $adatbazis_kapcsolat->u('munkalap',  array('visszaadva', 'szallito_id', 'jh_ert', 'varh_erk',            'becs_ertek',   'mail_ment', 'direktbe',    'szemelyzet_id',  'jh1_login', 'jh1_datum',           'jh1_szerep', 'update_usr'                            ),
                                                    array(1,            '',            0,        '0000-00-00 00:00:00',  0,             0,           0,             '',               '',          '0000-00-00 00:00:00',  0,            $_SESSION[get_db_tabla().'teljes_nev']  ),
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
                'hiba'  => 'Kérem adja meg az indoklást!',
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

            // Levélküldés
            $hiba = '';
            $valasz = level_felkeszit_es_kuld('munkalap', $recid, ERT_MUNK_ELUTASIT_UTAN, 'munkalapElutasit', '_jog'.JOG_MUNKALAP_VIZSGALAT);
            if( substr($valasz, 0, 4)=='Hiba' ){
                $hiba .= $valasz;
            }
            if($hiba){
                echo json_encode(array(
                    'hiba'  => $hiba,
                    'title' => 'Hiba',
                ));
                $weblap->leallit();
                exit();
            }

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
    $memo  = 'Reklamáció elutasítva:'."\r\n".$_POST['memo'];

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
                'hiba'  => 'Kérem adja meg az indoklást!',
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

            // Ha a státusza vizsgálat alatt, akkor levelet is küldünk
            if( $munkalap['stat_id']==MUNKALAP_STAT_VIZSGALAT_ALATT ){
                mysqli_commit($db_kapcs);

                // Levélküldés
                $hiba = '';
                $valasz = level_felkeszit_es_kuld('munkalap', $recid, ERT_MUNK_VIZSGALAT_UTAN, 'munkalapVizsgalatUtan', '_jog'.JOG_MUNKALAP_IGAZOLAS, '<br>'.nl2br2($memo).'<br>');
                if( substr($valasz, 0, 4)=='Hiba' ){
                    $hiba .= $valasz;
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
                'hiba'  => tranzakcioHiba(),
                'title' => 'Hiba',
            ));
        }
    }

    $weblap->leallit();
    exit();
}


// Árajánlat nyomtatva
if( isset($_POST['munkalapArajanlatNyomtatva']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {

        $munkalap = $adatbazis_kapcsolat->s2('stat_id szallito_id', 'munkalap', "`recid` = '{$recid}'", '', '1');
        if( $munkalap === false ) { $db_error = true; }

        // Státusz ellenőrzés
        if( $munkalap['stat_id'] != MUNKALAP_STAT_HOZREND_MEGREND_VAR ) {
            echo json_encode(array(
                'hiba'  => 'Csak "megrendelésre vár" státuszú munkalapból lehet árajánlatot nyomtatni!',
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
                'hiba' => 'Biztos, hogy sikerült az árajánlat nyomtatása?',
                'gomb' => '<input class="gomb zold" value="Igen" onclick="munkalapArajanlatNyomtatva();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        // Státusz léptetés
        if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_AJANLAT_NYOMTATVA ) ) {
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

        $munkalap = $adatbazis_kapcsolat->s2('stat_id szallito_id', 'munkalap', "`recid` = '{$recid}'", '', '1');
        if( $munkalap === false ) { $db_error = true; }

        // Státusz ellenőrzés
        if( $munkalap['stat_id'] != MUNKALAP_STAT_AJANLAT_NYOMTATVA ) {
            echo json_encode(array(
                'hiba'  => 'Csak "árajánlat nyomtatva" státuszú munkalaphoz rendelhető külsős munka!',
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
        $memo = 'Kivitelező: '.getErtek('nev', 'szallito', 'id', $munkalap['szallito_id']);
        if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_HOZZARENDELT, '', $memo ) ) {
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
        $munkalap = $adatbazis_kapcsolat->s2('stat_id szallito_id', 'munkalap', "`recid` = '{$recid}'", '', '1');
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
            if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_HOZZARENDELT ) ) {
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
        $sql = $munkalap = $adatbazis_kapcsolat->s2('stat_id szallito_id', 'munkalap', "`recid` = '{$recid}'");
        if( $sql === false ) { $db_error = true; }

        if( $munkalap ) {

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

// Munkalap volt-e már döntés alatt státuszban?
if (isset($_POST['volt_mar_dontesre_var'])) {

    $statusz = $adatbazis_kapcsolat->s2('insert_usr insert_dt', 'munkalap_statusz', "`munkalap_recid`='{$_POST['recid']}' AND `stat_id`='".MUNKALAP_STAT_DONTESRE_VAR."'", 'recid desc');
    if($statusz){
        if(!$statusz['text']){
            $statusz['text'] = '-';
        }
        echo json_encode(array(
            'kerdes'=> nl2('
                            Erre a munkalapra már volt javaslat téve!
                            <br>
                            <br>Javaslattevő: '.$statusz['insert_usr'].'
                            <br>Kelte: '.datum_kiir($statusz['insert_dt']).'
                            <br>Megjegyzés: '.nl2br2($statusz['text']).'
                            <br>
                            <br>Folytatja?
                            <div class="c">
                                <input type="button" name="elkuld" class="gomb zold" value="Igen" onclick="$(\'#mar_volt_kerdes\').hide(); $(\'#munkalapKulso\').show(); ">
                                <input type="button" name="megsem" class="gomb piros" value="Nem" onclick="popupZar();">
                            </div>
                            ')
        ));
    } else {
        echo json_encode(array(
            'mehet' => 1
        ));
    }

    $weblap->leallit();
    exit();
}

// Munkalap Javaslattétel
if( isset($_POST['kulsokivHozzarend']) ) {
    
    $recid              = $_POST['recid'];
    $szallitoid         = $_POST['szallito_id'];
    $kulso_valtas_mehet = $_POST['kulso_valtas_mehet'];
    $jh_ert             = (int) str_replace(array(' '), array(''), $_POST['jh_ert']);
    $varh_erk           = str_replace(array('.'), array('-'), $_POST['varh_erk']);
    $becs_ertek         = ($_POST['becsult_ertek'] == 'true' ? 1 : 0 );
    $emailkuldes_megj   = '';
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {

        // A szállító ne nulla legyen, hanem üres string
        if( !$szallitoid ) {
            $szallitoid = '';
        }
        
        $munkalap = $sor = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id telephely_id szallito_id', 'munkalap', "`recid` = '{$recid}'", '', '1');
        if( $munkalap === false ) { $db_error = true; }

        $telephely = $adatbazis_kapcsolat->s2('modell', 'telephely', "`id`='{$munkalap['telephely_id']}'");
        if( $telephely === false ) { $db_error = true; }

        // Státusz ellenőrzése
        if( $munkalap['stat_id'] != MUNKALAP_STAT_ELOKESZITES_ALATT && $munkalap['stat_id'] != MUNKALAP_STAT_HOZZARENDELT ) {
            echo json_encode(array(
                'hiba'  => 'Csak Előkészítés alatt vagy Hozzárendelt státuszban lehet szállítót választani!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzése (javaslattétel esetén külsős előkészítés jog
        if(
            !(
                ( $sor['stat_id']==MUNKALAP_STAT_ELOKESZITES_ALATT && _jog(JOG_MUNKALAP_KULSOS_ELOKESZIT) && $_SESSION[get_db_tabla().'szemelyzet_id']==$sor['szemelyzet_id'] ) ||
                ( $sor['stat_id']==MUNKALAP_STAT_HOZZARENDELT && $sor['szemelyzet_id']==KULSO_KIVITELEZO && ( _jog(JOG_MUNKALAP_KEZEL_KULSO) || ( _jog(JOG_MUNKALAP_SAJAT_KEZEL) && $_SESSION[get_db_tabla().'szemelyzet_id']==KULSO_KIVITELEZO ) ) )
            )
        ) {
            echo json_encode(array(
                'hiba'  => 'Önnek nincs joga külső kivitelezőt hozzárendelni!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }
        
        // Ha van levélküldés, akkor ellenőrizzük a többi mezőt is
        if( $_POST['email'] && !isset($_POST['javaslattetel']) ) {
            if(!trim($_POST['cimzett'])){
                echo json_encode(array(
                    'hiba'  => 'Kérem adja meg a címzett email címét!',
                    'title' => 'Hiba',
                    'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="$(\'#email_kuldessel\').val(0); alert2();">',
                    'focus' => 'pop_cimzett',
                ));

                $weblap->leallit();
                exit();
            }
            
            // Címzett mezőben levő adat ellenőrzése
            $cimzett_hibak = is_valid_mail_tobb($_POST['cimzett'], 'Hibaüzenettel');
            if( $cimzett_hibak ){
                echo json_encode(array(
                    'hiba'  => $cimzett_hibak,
                    'title' => 'Hiba',
                    'focus' => 'pop_cimzett',
                ));

                $weblap->leallit();
                exit();
            }
            // Másolat mezőben levő adat ellenőrzése
            $cimzett_hibak = is_valid_mail_tobb($_POST['masolat'], 'Hibaüzenettel');
            if( $cimzett_hibak ){
                echo json_encode(array(
                    'hiba'  => $cimzett_hibak,
                    'title' => 'Hiba',
                    'focus' => 'pop_masolat',
                ));

                $weblap->leallit();
                exit();
            }
            
            // Címzettek tömb előállítása
            $mailcimek = explode(',', $_POST['cimzett']);
            $mailcimek = array_values( array_filter($mailcimek) );
            
            // Másolatot kapnak tömb előállítása
            $masolatok = explode(',', $_POST['masolat']);
            array_push($masolatok, get_mszk_mail(), get_mszk_mail2()); // Így az MSZK-k is kapnak értesítő levelet
            $masolatok = array_values( array_filter($masolatok) );

            // Tárgy ellenőrzése
            if(!trim($_POST['targy'])){
                echo json_encode(array(
                    'hiba'  => 'Kérem töltse ki az email tárgyát!',
                    'title' => 'Hiba',
                    'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="$(\'#email_kuldessel\').val(0); alert2();">',
                    'focus' => 'pop_targy',
                ));

                $weblap->leallit();
                exit();
            }
            
            // Üzenet ellenőrzése
            if(!trim($_POST['uzenet'])){
                echo json_encode(array(
                    'hiba'  => 'Kérem töltse ki a email üzenetét!',
                    'title' => 'Hiba',
                    'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="$(\'#email_kuldessel\').val(0); alert2();">',
                    'focus' => 'pop_uzenet',
                ));

                $weblap->leallit();
                exit();
            }

            $szallito = $adatbazis_kapcsolat->s2('nev', 'szallito', "`id`='{$szallitoid}'");
            if( $szallito === false ) { $db_error = true; }

            // Attól függően, hogy melyik rendszerben vagyunk, kap a tárgy/üzenet egy [Teszt] előtagot.
            $kieg = get_mail_teszt_felirat();
            $targy_kieg = $kieg[0];
            $uzi_kieg   = $kieg[1];
            
            // Csatolmányok összeszdése
            $mappa = $adatbazis_kapcsolat->s2('ikt_szam', 'munkalap', "`recid`='{$recid}'");
            $utvonal = '../'.get_utvonal().'munkalap/'.str_replace('/', '_', $mappa['ikt_szam']).'/email/*';
            $fajlok_mailnek = array();
            $fajlok = glob($utvonal);
            
            $sikeres_kuldes = level_kuldes_osszetettebb(get_nev(), get_mail(), $mailcimek, $mailcimek, $masolatok, $masolatok, $r_masolatok, $r_masolatok, $targy_kieg.$_POST['targy'], $uzi_kieg.nl2br2($_POST['uzenet']), $fajlok, 'levélpapíros');
            // Ha nem volt sikeres a levélküldés, akkor is megállítjuk a működést
            if( $sikeres_kuldes ) {
                mysqli_rollback($db_kapcs);
                echo json_encode(array(
                    'hiba'  => 'Levélküldés közben hiba történt! '.$sikeres_kuldes,
                    'title' => 'Hiba'
                ));

                $weblap->leallit();
                exit();
                
            } else {
                
                $cimzettek          = implode(', ', $mailcimek);
                $masolatot_kapott   = '';
                $emailkuldes_megj .= 'Megrendelés elküldve.'."\n".'Címzettek: '.$cimzettek.' ';
                if($masolatok){
                    $masolatot_kapott   = implode(', ', $masolatok);
                    $emailkuldes_megj .= "\n".'Másolatot kaptak: '.$masolatot_kapott.' ';
                }
                $emailkuldes_megj .= "\n";

                // E-mail küldés logolása
                $email_recid = $adatbazis_kapcsolat->i('munkalap_email',    array('munkalap_recid', 'szallito_id', 'cimzett',   'masolat',         'targy',                     'text',                                'insert_dt', 'insert_usr'                           ),
                                                                            array($recid,           $szallitoid,   $cimzettek,  $masolatot_kapott, $targy_kieg.$_POST['targy'], $uzi_kieg.nl2br2($_POST['uzenet']),    'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'] )
                                            );
                if( $email_recid === false ) { $db_error = true; }
                
                // Fájlok mozgatása
                if( csatolmanyok_eltesz($fajlok, $utvonal, $email_recid, $recid, 'munkalap') === false ) {
                    $db_error = true;
                }

                // Mail eküldve pipa bekapcsolása
                $sql = $adatbazis_kapcsolat->u('munkalap', array('mail_ment'), array('1'), "`recid`='{$recid}'");
                if( $sql === false ) { $db_error = true; }
            }
                
            
            // E-mail értesítésekről megjegyzést is írunk
            if( addMegj('munkalap', $recid, $megj) === false ) {
                $db_error = true;
            }
            
        // Ha nincs levélküldés, akkor rákérdezünk, hogy biztosan nem szerettek volna levelet küldeni?
        }elseif( !$_POST['mehet'] && !isset($_POST['javaslattetel']) ) {
            echo json_encode(array(
                'hiba'  => 'Nem küldött email megrendelést.<br>Folytatja?',
                'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#mehet_mail_nelkul\').val(\'1\'); $(\'#munkalapKulso\').submit();"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">',
                'title' => 'Kérdés',
            ));
            $weblap->leallit();
            exit();
        }

        // Van-e külső kivitelező meghatározva
        if( !$szallitoid ){
            echo json_encode(array(
                'hiba'  => 'Kérem válasszon külső kivitelezőt!',
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

        // Javaslattétel esetén ellenőrizzük, hogy van-e már fájl és jóváhagyott érték
        if( isset($_POST['javaslattetel']) ){

            // Ha nincs jóváhagyott érték
            if( !$jh_ert ){
                echo json_encode(array(
                    'hiba'  => 'Kérem adja meg a jóváhagyott értéket!',
                    'title' => 'Hiba',
                ));
                $weblap->leallit();
                exit();
            }

            $van_doksi = $adatbazis_kapcsolat->s('recid', 'munkalap_dok', "`munkalap_id`='{$recid}'", '', '1');
            if( $van_doksi === false ) { $db_error = true; }

            // Ha nincs egyetlen dokumentum sem, akkor figyelmeztetés
            if(!$van_doksi){
                echo json_encode(array(
                    'hiba'  => 'Javaslattételhez töltsön fel egy dokumentumot!<br>(árajánlat, összesítő stb.)',
                    'title' => 'Hiba',
                ));
                $weblap->leallit();
                exit();
            }
            
            // Változott-e a külső kivitelező?
            $megj_eleje = 'Javasolt kivitelező: '. getErtek('nev', 'szallito', 'id', $szallitoid);
            if( $munkalap['szallito_id'] && $munkalap['szallito_id'] != $szallitoid && !$kulso_valtas_mehet ) {
                echo json_encode(array(
                    'hiba'  => 'Új partner megadására készül.<br>Régi partner: '. getErtek('nev', 'szallito', 'id', $munkalap['szallito_id']).'<br>Új partner: '. getErtek('nev', 'szallito', 'id', $szallitoid).'<br>Folytatja?',
                    'gomb'  => '<input type="button" value="Igen" class="gomb zold" onclick="kulsokivHozzarend(\'mehet\');"><input type="button" value="Nem" class="gomb piros" onclick="alert2()">',
                ));
                $weblap->leallit();
                exit();
            }
            // Ha változik a szállító, akkor a megjegyzést másképp kezdjük
            if( $munkalap['szallito_id'] && $munkalap['szallito_id'] != $szallitoid ) {
                $megj_eleje = 'Régi partner: '. getErtek('nev', 'szallito', 'id', $munkalap['szallito_id']).'.<br>Új partner: '. getErtek('nev', 'szallito', 'id', $szallitoid).'.';
            }
            
            // Javaslattétel update mezői. Javaslattétel esetén a direktes mezőt is töröljük mindig
            $mezok      = array('szallito_id', 'jh_ert', 'varh_erk', 'becs_ertek', 'jh1_login', 'jh1_datum',           'jh1_szerep',    'jh2_login', 'jh2_datum',           'jh2_szerep',   'szemelyzet_id', 'direktbe' );
            $ertekek    = array($szallitoid,   $jh_ert,  $varh_erk,  $becs_ertek,  '',          '0000-00-00 00:00:00', 0,               '',          '0000-00-00 00:00:00', 0,              0,               0          );

            // Munkalap update, ha javaslattétel volt
            $sql = $adatbazis_kapcsolat -> u('munkalap', $mezok, $ertekek, "`recid` = '{$recid}'");
            if( $sql === false ) { $db_error = true; }

            // Ha becsült érték, az a megjegyzésbe is kerüljön be
            $megj_kieg = '';
            if($becs_ertek){
                $megj_kieg .= ' <span class="piros">(becsült érték)</span>';
            }
            
            // Megjegyzés hozzáadása
            $megj = $megj_eleje.'<br>Érték: '. number_format($jh_ert, 0, '', ' ').' '.get_penznem().$megj_kieg.'<br>Érkezés: '.str_replace('-', '.', $varh_erk).'<br>'.$emailkuldes_megj;
            if(addMegj('munkalap', $recid, $megj)===false){
                $db_error = true;
            }
            
            // Státusz léptetés
            if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_DONTESRE_VAR ) ) {
                $db_error = true;
            }
            
        // Ha nem javaslattétel volt
        } else {

            $sql = $adatbazis_kapcsolat -> u('munkalap',
                                                        array('szallito_id', 'szemelyzet_id',   'varh_erk', 'direktbe'),
                                                        array($szallitoid,   KULSO_KIVITELEZO,  $varh_erk,  1         ),
                                                "`recid` = '{$recid}'");
            if( $sql === false ) { $db_error = true; }

            $memo = 'Kivitelező: '.getErtek('nev', 'szallito', 'id', $szallitoid).'<br>'.$emailkuldes_megj;
            if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_HOZZARENDELT, '', $memo ) ) {
                $db_error = true;
            }

            // A memót a megjegyzésbe is beírjuk, nem csak a státuszsorba
            if( addMegj('munkalap', $recid, $memo) === false ) {
                $db_error = true;
            }

        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);
            
            // Javaslattételkor levélküldés
            if(isset($_POST['javaslattetel'])) {
                $hiba = '';
                $valasz = level_felkeszit_es_kuld('munkalap', $recid, ERT_MUNK_KK_JT_UTAN, 'munkalapKkJtUtan', '_jog'.JOG_MUNKALAP_KULSOS_DONTES);
                if( substr($valasz, 0, 4)=='Hiba' ){
                    $hiba .= $valasz;
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

// Jóváhagyott érték módosítás előtt, kiolvassuk az értékét
if (isset($_POST['get_jh_ertek'])) {

    $munkalap = $adatbazis_kapcsolat->s2('jh_ert', 'munkalap', "`recid`='{$_POST['recid']}'");

    echo json_encode(array(
        'ertek' => number_format($munkalap['jh_ert'], 0, '', ' ')
    ));

    $weblap->leallit();
    exit();
}

// Jóváhagyott érték módosítás előtt, kiolvassuk az értékét
if (isset($_POST['munkalapJovahagyottErtekModositasa'])) {

    $jh_ert = str_replace(' ', '', $_POST['pop_jh_ert']);

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $munkalap = $adatbazis_kapcsolat->s2('stat_id, jh1_login, jh2_login', 'munkalap', "`recid`='{$_POST['recid']}'");
    if( $munkalap === false ) { $db_error = true; }

    // Státusz ellenőrzése
    if( $munkalap['stat_id']!=MUNKALAP_STAT_DONTESRE_VAR ){
        echo json_encode(array(
            'hiba'  => 'Ebben a státuszban nem módosítható a jóváhagyott érték!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    // Jog ellenőrzése
    if( !_jog(JOG_MUNKALAP_KULSOS_ELOKESZIT) ){
        echo json_encode(array(
            'hiba'  => 'Nincs jogosultsága jóváhagyott értéket módosítani!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    // Volt-e már jóváhagyás?
    if( $munkalap['jh1_login'] || $munkalap['jh2_login'] ){
        echo json_encode(array(
            'hiba'  => 'A munkalapot már jóváhagyta legalább egy szereplő, így a jóvhagyott érték nem módosítható!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    // Jóváhagyott érték felülírása
    $sql = $adatbazis_kapcsolat->u('munkalap', array('jh_ert'), array($jh_ert), "`recid`='{$_POST['recid']}'");
    if( $sql === false ) { $db_error = true; }

    // Megjegyzés sor is bővül
    if(addMegj('munkalap', $_POST['recid'], 'Jóváhagyásra váró érték módosítva: '.number_format($jh_ert, 0, '', ' ').' Ft')===false){
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

    $weblap->leallit();
    exit();
}

// Vissza előkészített státuszba
if (isset($_POST['munkalapVisszaElokeszit'])) {

    $recid = $_POST['recid'];
    
    $sor = $adatbazis_kapcsolat->s2('stat_id', 'munkalap', "`recid`='{$recid}'");
    if( $sql === false ) { $db_error = true; }
 
    // Ha hozzárendelt státuszban vagyunk, akkor úgy kerülhettünk ide, hogy a ML nem volt direktes. 
    // Ekkor még csak a Stornóra vár státuszba léptetjük, és rögzítjük, hogy a visszaadás melyik módját használtuk
    if( $sor['stat_id']==MUNKALAP_STAT_HOZZARENDELT ){

        $db_error = false;
        mysqli_autocommit($db_kapcs, false);
        
        // Státusz léptetés
        if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_SZTORNO_VAR ) ) {
            $db_error = true;
        }
        
        // Rögzítjük, hogy vissza előkészítés ágon volt a visszaadás
        $adatbazis_kapcsolat->u('munkalap', array('vissza_mod'), array(VISSZA_MOD_VISSZAELOK), "`recid`='{$recid}'");
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
        
    // Egyébként vissza tesszük a ML-ot előkészített státuszba
    } else {
        munkalap_vissza_elokeszitesbe($recid);
    }
    
    $weblap->leallit();
    exit();
}

// Vissza előkészített státuszba
if (isset($_POST['munkalapVisszaElokeszitDirektbe'])) {

    $recid      = $_POST['recid'];
    $munkalap   = $adatbazis_kapcsolat->s2('szallito_id', 'munkalap', "`recid`='{$recid}'");
    
    // Ha van levélküldés, akkor ellenőrizzük a többi mezőt is
    if($_POST['email']){
        if(!trim($_POST['cimzett'])){
            echo json_encode(array(
                'hiba'  => 'Kérem adja meg a címzett email címét!',
                'title' => 'Hiba',
                'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="$(\'#email_kuldessel\').val(0); alert2();">',
                'focus' => 'pop_cimzett',
            ));

            $weblap->leallit();
            exit();
        }

        // Címzett mezőben levő adat ellenőrzése
        $cimzett_hibak = is_valid_mail_tobb($_POST['cimzett'], 'Hibaüzenettel');
        if( $cimzett_hibak ){
            echo json_encode(array(
                'hiba'  => $cimzett_hibak,
                'title' => 'Hiba',
                'focus' => 'pop_cimzett',
            ));

            $weblap->leallit();
            exit();
        }
        // Másolat mezőben levő adat ellenőrzése
        $cimzett_hibak = is_valid_mail_tobb($_POST['masolat'], 'Hibaüzenettel');
        if( $cimzett_hibak ){
            echo json_encode(array(
                'hiba'  => $cimzett_hibak,
                'title' => 'Hiba',
                'focus' => 'pop_masolat',
            ));

            $weblap->leallit();
            exit();
        }

        // Címzettek tömb előállítása
        $mailcimek = explode(',', $_POST['cimzett']);
        $mailcimek = array_values( array_filter($mailcimek) );

        // Másolatot kapnak tömb előállítása
        $masolatok = explode(',', $_POST['masolat']);
        array_push($masolatok, get_mszk_mail(), get_mszk_mail2()); // Így az MSZK-k is kapnak értesítő levelet
        $masolatok = array_values( array_filter($masolatok) );

        // Tárgy ellenőrzése
        if(!trim($_POST['targy'])){
            echo json_encode(array(
                'hiba'  => 'Kérem töltse ki az email tárgyát!',
                'title' => 'Hiba',
                'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="$(\'#email_kuldessel\').val(0); alert2();">',
                'focus' => 'pop_targy',
            ));

            $weblap->leallit();
            exit();
        }

        // Üzenet ellenőrzése
        if(!trim($_POST['uzenet'])){
            echo json_encode(array(
                'hiba'  => 'Kérem töltse ki a email üzenetét!',
                'title' => 'Hiba',
                'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="$(\'#email_kuldessel\').val(0); alert2();">',
                'focus' => 'pop_uzenet',
            ));

            $weblap->leallit();
            exit();
        }

        $szallito = $adatbazis_kapcsolat->s2('nev', 'szallito', "`id`='{$munkalap['szallito_id']}'");
        if( $szallito === false ) { $db_error = true; }

        // Attól függően, hogy melyik rendszerben vagyunk, kap a tárgy/üzenet egy [Teszt] előtagot.
        $kieg = get_mail_teszt_felirat();
        $targy_kieg = $kieg[0];
        $uzi_kieg   = $kieg[1];

        // Csatolmányok összeszdése
        $mappa = $adatbazis_kapcsolat->s2('ikt_szam', 'munkalap', "`recid`='{$recid}'");
        $utvonal = '../'.get_utvonal().'munkalap/'.str_replace('/', '_', $mappa['ikt_szam']).'/email/*';
        $fajlok_mailnek = array();
        $fajlok = glob($utvonal);

        $sikeres_kuldes = level_kuldes_osszetettebb(get_nev(), get_mail(), $mailcimek, $mailcimek, $masolatok, $masolatok, $r_masolatok, $r_masolatok, $targy_kieg.$_POST['targy'], $uzi_kieg.nl2br2($_POST['uzenet']), $fajlok, 'levélpapíros');
        if( $sikeres_kuldes ) {
            mysqli_rollback($db_kapcs);
            echo json_encode(array(
                'hiba'  => 'Levélküldés közben hiba történt! '.$sikeres_kuldes,
                'title' => 'Hiba'
            ));

            $weblap->leallit();
            exit();
            
        } else {

            $cimzettek          = implode(', ', $mailcimek);
            $masolatot_kapott   = '';
            $emailkuldes_megj .= 'Megrendelés SZTORNÓ elküldve.'."\n".'Címzettek: '.$cimzettek.' ';
            if($masolatok){
                $masolatot_kapott   = implode(', ', $masolatok);
                $emailkuldes_megj .= "\n".'Másolatot kaptak: '.$masolatot_kapott.' ';
            }
            $emailkuldes_megj .= "\n";
            // E-mail küldés logolása
            $email_recid = $adatbazis_kapcsolat->i('munkalap_email',    array('munkalap_recid', 'szallito_id', 'cimzett',   'masolat',           'targy',                     'text',                                'insert_dt', 'insert_usr'                           ),
                                                                        array($recid,           $szallito_id,  $cimzettek,  $masolatot_kapott,   $targy_kieg.$_POST['targy'], $uzi_kieg.nl2br2($_POST['uzenet']),    'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'] )
                                        );
            if( $email_recid === false ) { $db_error = true; }
            
            // Fájlok mozgatása
            if( csatolmanyok_eltesz($fajlok, $utvonal, $email_recid, $recid, 'munkalap') === false ) {
                $db_error = true;
            }

            // Mail elküldve pipa bekapcsolása
            $sql = $adatbazis_kapcsolat->u('munkalap', array('mail_ment'), array('1'), "`recid`='{$recid}'");
            if( $sql === false ) { $db_error = true; }
        }


    // Ha nincs levélküldés, akkor rákérdezünk, hogy biztosan nem szerettek volna levelet küldeni?
    }elseif(!$_POST['mehet']){
        echo json_encode(array(
            'hiba'  => 'Nem küldött visszavételező emailt.<br>Folytatja?',
            'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#mehet_mail_nelkul\').val(\'1\'); $(\'#munkalapKulso\').submit();"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">',
            'title' => 'Kérdés',
        ));
        $weblap->leallit();
        exit();
    }
    
    // Munkalap visszaküldése előkészítés státuszba, kiszervezve függvénybe, mert lehet többször is szükség lesz rá
    munkalap_vissza_elokeszitesbe($_POST['recid'], $emailkuldes_megj);

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
                $munk_kapcsok = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id` = '{$recid}'");
                if( $munk_kapcsok === false ) { $db_error = true; }

                if( $munk_kapcsok ) {

                    $bejelentes = $adatbazis_kapcsolat->s2('stat_id', 'bejelentes', "`recid` = '".$munk_kapcsok['bejelentes_id']."'");
                    if( $bejelentes === false ) { $db_error = true; }

                    if( $bejelentes ) {

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

                        // Több munkalaphoz kapcsolódik a bejelentés
                        if( $tobb_munk ) {
                            $szoveg_kiir = 'A munkalap bejelentéséhez további munkalapok vannak kapcsolva. Ha folytatja, a többi munkalapot sem lehet lezárni, mert a bejelentés visszakerül Folyamatban státuszba. Előbb zárja le a többi munkalapot és utoljára adja vissza ezt a munkalapot a diszpécsernek! Folytatja?';
                        // Csak egy munkalaphoz kapcsolódik a bejelentés
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


        // Ha nem kész státuszban van a munkalap
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

            // Megnézzük hogy van e több munkalapja a bejelentésnek
            $sql = $munk_kapcsok = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id` = '{$recid}'");
            if( $sql === false ) { $db_error = true; }

            if( $munk_kapcsok ) {

                $bejelentes = $adatbazis_kapcsolat->s2('stat_id', 'bejelentes', "`recid` = '".$munk_kapcsok['bejelentes_id']."'");
                if( $bejelentes === false ) { $db_error = true; }

                if( $bejelentes ) {

                    $sql = $tobb_munk = $adatbazis_kapcsolat->s('recid munkalap_id', 'munkalap_kapcs', "`bejelentes_id` = '".$munk_kapcsok['bejelentes_id']."' AND `munkalap_id` != '{$recid}'", '', '');
                    if( $sql === false ) {  $db_error = true; }

                    // Több munkalaphoz kapcsolódik a bejelentés
                    if( $tobb_munk ) {
                        $szoveg_kiir = 'A munkalap bejelentéséhez további munkalapok vannak kapcsolva. A többi munkalap nem lesz lezárva. Folytatja?';

                        foreach($tobb_munk as $tmunk) {
                            $tobbimunkalap[] = $tmunk['munkalap_id'];
                        }

                        $is_tobbmunk = true;
                    // Csak egy munkalaphoz kapcsolódik a bejelentés
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

                // Ha egy munkalapja van a bejelentéseknek
                if( !$is_tobbmunk ) {
                    // Lezárjuk a bejelentéseket is
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
                    // Megnézzük a további munkalapokat, hogy lezártak e
                    $tm_db = count($tobbimunkalap);
                    $tm_lezart_db = 0;

                    if( $tm_db ) {
                        foreach($tobbimunkalap as $tmunkrecid) {
                            $mlap_statusz = $adatbazis_kapcsolat->s2('stat_id', 'munkalap', "`recid` = '{$tmunkrecid}'");
                            if( $mlap_statusz === false ) { $db_error = true; }

                            if( $mlap_statusz ) {
                                if( $mlap_statusz['stat_id'] == MUNKALAP_STAT_LEZART ) {
                                    $tm_lezart_db++;
                                }
                            }
                        }

                        // Összes munkalap le van zárva, tehát a bejelentések is zárhatóak
                        if( $tm_db == $tm_lezart_db ) {
                            // Lezárjuk a bejelentéseket is
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

            // Ha a munkalap kész státuszban van
            if( $munkalap['stat_id'] == MUNKALAP_STAT_LEZARASRA_VAR ) {
                $is_tobbmunk = false;
                $tobbimunkalap = array();

                $munk_kapcsok = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id` = '{$recid}'");
                if( $munk_kapcsok === false ) { $db_error = true; }

                if( $munk_kapcsok ) {

                    $bejelentes = $adatbazis_kapcsolat->s2('stat_id', 'bejelentes', "`recid` = '".$munk_kapcsok['bejelentes_id']."'");
                    if( $bejelentes === false ) { $db_error = true; }

                    if( $bejelentes ) {

                        if( $bejelentes['stat_id'] != BEJELENTES_STAT_VIZSG_ALATT ) {
                            echo json_encode(array(
                                'hiba'  => 'A munkalap bejelentése '.getStatText('bejelentes', $bejelentes['stat_id']).' státuszban van. A munkalapot nem lehet lezárni!',
                                'title' => 'Hiba',
                            ));

                            $weblap->leallit();
                            exit();
                        }

                        $tobb_munk = $adatbazis_kapcsolat->s('recid munkalap_id', 'munkalap_kapcs', "`bejelentes_id`='".$munk_kapcsok['bejelentes_id']."' AND `munkalap_id`!='{$recid}'");

                        // Több munkalaphoz kapcsolódik a bejelentés
                        if( $tobb_munk ) {
                            $szoveg_kiir = 'A munkalap bejelentéséhez további munkalapok vannak kapcsolva. A többi munkalap nem lesz lezárva. Folytatja?';

                            foreach($tobb_munk as $tmunk) {
                                $tobbimunkalap[] = $tmunk['munkalap_id'];
                            }

                            $is_tobbmunk = true;
                        // Csak egy munkalaphoz kapcsolódik a bejelentés
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

                    // Ha egy munkalapja van a bejelentéseknek
                    if( !$is_tobbmunk ) {
                        // Lezárjuk a bejelentéseket is
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
                        // Megnézzük a további munkalapokat, hogy lezártak e
                        $tm_db = count($tobbimunkalap);
                        $tm_lezart_db = 0;

                        if( $tm_db ) {
                            foreach($tobbimunkalap as $tmunkrecid) {
                                $mlap_statusz = $adatbazis_kapcsolat->s2('stat_id', 'munkalap', "`recid` = '{$tmunkrecid}'");
                                if( $mlap_statusz === false ) { $db_error = true; }

                                if( $mlap_statusz ) {

                                    if( $mlap_statusz['stat_id'] == MUNKALAP_STAT_LEZART ) {
                                        $tm_lezart_db++;
                                    }
                                }
                            }

                            // Összes munkalap le van zárva, tehát a bejelentések is zárhatóak
                            if( $tm_db == $tm_lezart_db ) {
                                // Lezárjuk a bejelentéseket is
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

        // Elutasítás
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
        if( $munkalap['stat_id'] != MUNKALAP_STAT_HOZZARENDELT && $munkalap['stat_id'] != MUNKALAP_STAT_FELVEVE && $munkalap['stat_id'] != MUNKALAP_STAT_VIZSGALAT_ALATT && $munkalap['stat_id'] != MUNKALAP_STAT_LEZARASRA_VAR ) {
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
                    ( $sor['stat_id']==MUNKALAP_STAT_HOZZARENDELT    && $sor['szemelyzet_id']==KULSO_KIVITELEZO && ( _jog(JOG_MUNKALAP_KEZEL_KULSO) || ( _jog(JOG_MUNKALAP_SAJAT_KEZEL) && $_SESSION[get_db_tabla().'szemelyzet_id']==KULSO_KIVITELEZO ) ) ) ||
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

        $akt_tetel = $adatbazis_kapcsolat->s2('recid atadta_dt', 'munkalap_tetel', "`munkalap_id` = '{$recid}'", 'recid desc', '1');
        if( $akt_tetel === false ) { $db_error = true; }

        // Ha az utolsó tétel még nyitott, tehát az átadta dátum még üres: ez is feltétel
        $utolso_tetel_nyitott = false;
        if( $akt_tetel['atadta_dt']=='0000-00-00 00:00:00' ){
            $utolso_tetel_nyitott = true;
        }

        // Ha a munkalap vizsgálat vagy lezárásra vár státusz alatt van, vagy ha nyitott az utolsó tétele, akkor mutatjuk meg popupban az utolsó tétel feladatait.
        if( $sor['stat_id']==MUNKALAP_STAT_VIZSGALAT_ALATT || $sor['stat_id']==MUNKALAP_STAT_LEZARASRA_VAR || $utolso_tetel_nyitott ){

            $munkalap_feladatok = $adatbazis_kapcsolat->s('recid erteklista_id memo', 'munkalap_feladat', "`munkalap_id` = '{$recid}' AND `tetel_id` = '".$akt_tetel['recid']."'");
            if( $munkalap_feladatok === false ) { $db_error = true; }

            if( $munkalap_feladatok ) {
                $sorok = '';
                foreach($munkalap_feladatok as $munkalap_f) {
                    $sorok .= nl2('
                        <tr class="feladat_sor_'.$munkalap_f['recid'].'">
                            <td>'.erteklista(ERTEKLISTA_ELV_FELAD, $munkalap_f['erteklista_id']).'<input type="hidden" name="addCikkID[]" value="'.$munkalap_f['cikk_id'].'"><input type="hidden" name="addCikkUj[]" value="0"></td>
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

                /*if( !in_array($statusz['stat_id'], array(MUNKALAP_STAT_FELVEVE, MUNKALAP_STAT_HOZZARENDELT)) ) {
                    echo json_encode(array(
                        'hiba'  => 'Kérem csak felvéve vagy folyamatban státuszú munkalapokat jelöljön meg!',
                        'gomb'  => '<input class="link gomb" value="Ok" onclick="csoportMuvelet(\'munkalap_mobil\', \'státus hibafigyelés nélkül\'); alert2();" type="button">',
                        'title' => 'Hiba',
                    ));

                    $weblap->leallit();
                    exit();
                }*/

                if( $statusz['stat_id'] == MUNKALAP_STAT_HOZZARENDELT && $statusz['szemelyzet_id']!=MOBILCSOPORT ) {
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
        $munkalapok = $adatbazis_kapcsolat->s('recid ikt_szam telephely_id stat_id surgos', 'munkalap', "(`szemelyzet_id` = '".MOBILCSOPORT."' AND `stat_id` = '".MUNKALAP_STAT_HOZZARENDELT."' AND `mobil_loginnev` = '') OR (`recid` IN (".implode(',', $idk).") AND `szemelyzet_id` IN (0, ".MOBILCSOPORT.") AND `stat_id` IN (".MUNKALAP_STAT_NYITOTT.") AND `mobil_loginnev` = '')");
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
        $munkalapok = $adatbazis_kapcsolat->s('recid ikt_szam telephely_id stat_id mobil_loginnev surgos', 'munkalap', "`mobil_loginnev` != '' AND `szemelyzet_id` = '".MOBILCSOPORT."' AND `stat_id` IN (".MUNKALAP_STAT_HOZZARENDELT.", ".MUNKALAP_STAT_FELVEVE.")");
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

    $telephely = $adatbazis_kapcsolat->s2('nev modell', 'telephely', "`id`='{$munkalap['telephely_id']}'");

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
            <th>Anyag rendelkezésre áll</th>
        </tr>
        ');

    $anyagok = $adatbazis_kapcsolat->s('recid cikk_id cikk_nev cikk_men szuk_men cikk_mee stat_id', 'munkalap_anyag', "`munkalap_id`='{$_POST['recid']}'");
    foreach ( $anyagok as $anyag ) {

        foreach ( $munkAnyag_statusz as $kulcs => $stat ) {
            if( $kulcs == $anyag['stat_id'] ) {
                $statusz = $stat;
            }
        }

        // Ha anyagstátusz = igénylés alatt vagy igényelve, anyag beérkezett oszlopban pipáló
        // Ha anyagstátusz=rendelkezésre áll vagy üres (ez elvileg nem lehet), szürkén mutatjuk sort és nem tud pipálni
        $disabled = '';
        $szurkit  = '';
        if( $anyag['stat_id'] == DISZPNAG_STAT_VAN_ANYAG || $anyag['stat_id'] == '' ) {
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

    // Egyszálloda esetén más gombokat rajzolunk ki
    if( !$telephely['modell'] ) {
        $gombok = nl2('
                        <input type="button" name="elkuld" value="Átvétel előkészítésre" class="gomb zold" onclick="munkalapErkeztetMent(\'elokeszitersre\')">
                        <input type="button" name="elkuld" value="Átadás telepítettnek" class="gomb" onclick="munkalapErkeztetMent(\'telepitettnek\')">
                        <input type="button" name="elkuld" value="Mégsem" class="gomb piros" onclick="popupZar()">
                    ');
    } else {
        $gombok = nl2('
                        <input type="button" name="elkuld" value="Mentés" class="gomb" onclick="munkalapErkeztetMent(\'\')">
                        <input type="button" name="elkuld" value="Mégsem" class="gomb piros" onclick="popupZar()">
                    ');
    }

    echo json_encode(array(
        'statusz'   => 'siker',
        'ikt_szam'  => $munkalap['ikt_szam'],
        'telephely' => $telephely['nev'],
        'tablazat'  => $tablazat,
        'gombok'    => $gombok,
    ));


    $weblap->leallit();
    exit();
}

// Munkalap érkeztetésének mentése
if( isset($_POST['munkalapErkeztetMent']) ) {

    $idk = json_decode($_POST['idk'], true);
    $mod = $_POST['mod'];

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
        if( count($mind_kesz) == 0 ) {
            
            // Egyszállodás modell esetén a popupon nem csak egy Mentés gomb van, hanem egy "Átvétel előkészítésre" és "Átadás telepítettnek". 
            // Ezek működését kezeljük le itt
            // Előkészítésre gomb: előkészítés státusz + hozzárendelt személy: műszaki vezető
            if ( $mod == 'elokeszitersre' ) {
                
                if( !addStatusz( 'munkalap_statusz', $munkalap_id['munkalap_id'], MUNKALAP_STAT_ELOKESZITES_ALATT ) ) {
                    $db_error = true;
                }
                $sql = $adatbazis_kapcsolat->u('munkalap',      array('szemelyzet_id'),
                                                                array(MUSZAKI_VEZETO ),
                                                "`recid` = '{$munkalap_id['munkalap_id']}'");
                if( $sql === false ) { $db_error = true; }

            // Telepítettnek gomb: hozzárendelt státusz + hozzárendelt személy: telepített
            } else if( $mod == 'telepitettnek' ) {
                
                if( !addStatusz( 'munkalap_statusz', $munkalap_id['munkalap_id'], MUNKALAP_STAT_HOZZARENDELT ) ) {
                    $db_error = true;
                }
                $sql = $adatbazis_kapcsolat->u('munkalap',      array('szemelyzet_id'),
                                                                array(TELEPITETT_SZEMELYZET ),
                                                "`recid` = '{$munkalap_id['munkalap_id']}'");
                if( $sql === false ) { $db_error = true; }
                
            // Általános eset: többszállodás modell esete
            } else {
                if( !addStatusz( 'munkalap_statusz', $munkalap_id['munkalap_id'], MUNKALAP_STAT_NYITOTT ) ) {
                    $db_error = true;
                }
            }
            
            $sql = $adatbazis_kapcsolat->u('munkalap', array('visszaadva'), array(1), "`recid`='{$munkalap_id['munkalap_id']}'");
            if( $sql === false ) { $db_error = true; }
        }
        
    // Ha egyetlen pipálót sem pipáltak be
    } else {
        echo json_encode(array(
            'hiba'  => 'Kérem, hogy a pipáló segítségével adja meg, mely anyagok állnak rendelkezésre!',
            'title' => 'Hiba',
        ));
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
    if( $sor['stat_id']!=MUNKALAP_STAT_HOZZARENDELT ){

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

            // Ha a diszpécsernek átadásakor a telephely egyszállodás, akkor a szó Átadáshoz lesz
            $telephely = $adatbazis_kapcsolat->s2('modell', 'telephely', "`id`='{$munkalap['telephely_id']}'");
            if(!$telephely['modell']){
                $szo = 'Átadáshoz';
            }
        }

        // Hozzárendelt státusznál az is feltétel, hogy a tétel nyitott-e, azaz nincs átadta dátum kitöltve. Egyébként csak a feltétel, hogy létezzen utolsó tétel.
        $sql_kieg = '';
        if( $sor['stat_id']==MUNKALAP_STAT_HOZZARENDELT ){
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

        // Tömbként kapott POST-okból változókat készítünk
        parse_str($_POST['addCikkUj']);
        parse_str($_POST['addCikkID']);
        parse_str($_POST['addCikkFelhMenny']);
        parse_str($_POST['addCikkSzukMenny']);
        parse_str($_POST['addCikkIgleir']);

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
            $akt_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id` = '{$recid}'", 'recid desc', '1');
            if( $akt_tetel === false ) { $db_error = true; }

            if( $akt_tetel ) {

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
                                                                array('munkalap_id', 'telephely_id',        'tetel_id',          'cikk_id',       'oracikkid',            'cikk_nev',                                'cikk_mee',             'cikk_men',              'szuk_men',                'igleir',              'stat_id', 'insert_dt',    'insert_usr',                             'update_usr'                          ),
                                                                array($recid,        $mlap['telephely_id'], $akt_tetel['recid'], $cikk_sor['id'], $cikk_sor['oracikkid'], htmlspecialchars_decode($cikk_sor['nev']), $cikk_sor['cikkmee'],   $addCikkFelhMenny[$key], $addCikkSzukMenny[$key],   $addCikkIgleir[$key],  $stat_id,  'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'])
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
                                                        array( 0,              '',               '',            $_SESSION[get_db_tabla().'teljes_nev'] ),
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
        if( $munkalap['stat_id'] != MUNKALAP_STAT_FELVEVE && $munkalap['stat_id'] != MUNKALAP_STAT_HOZZARENDELT && $munkalap['stat_id'] != MUNKALAP_STAT_LEZARASRA_VAR && $munkalap['stat_id'] != MUNKALAP_STAT_VIZSGALAT_ALATT ) {
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
                ( $sor['stat_id']==MUNKALAP_STAT_FELVEVE         && in_array($sor['szemelyzet_id'], $felvehetik) && $kezeles_jog ) ||
                ( $sor['stat_id']==MUNKALAP_STAT_LEZARASRA_VAR   && in_array($sor['szemelyzet_id'], $felvehetik) && $kezeles_jog ) ||
                ( $sor['stat_id']==MUNKALAP_STAT_HOZZARENDELT    && $sor['szemelyzet_id']==KULSO_KIVITELEZO      && _jog(JOG_MUNKALAP_KEZEL_KULSO) ) ||
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

        // Tömbként kapott POST-okból változókat készítünk
        parse_str($_POST['addCikkUj']);
        parse_str($_POST['addCikkID']);
        parse_str($_POST['addCikkFelhMenny']);
        parse_str($_POST['addCikkIgleir']);

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
                            array('munkalap_id', 'telephely_id',        'tetel_id',            'stat_id',                  'cikk_id',       'oracikkid',               'cikk_nev',                                 'cikk_mee',             'cikk_men',              'igleir',             'insert_dt',    'insert_usr',                             'update_usr'                              ),
                            array($recid,        $mlap['telephely_id'], $akt_tetel['recid'],    DISZPNAG_STAT_VAN_ANYAG,   $cikk_sor['id'], $cikk_sor['oracikkid'],    htmlspecialchars_decode($cikk_sor['nev']),  $cikk_sor['cikkmee'],   $addCikkFelhMenny[$key], $addCikkIgleir[$key], 'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']    )
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
    $memo           = erteklista(ERTEKLISTA_ATADAS_OKA, $erteklista_id)."\r\n".$_POST['memo'];

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
                ( /* Modell nem számít */ $sor['stat_id']==MUNKALAP_STAT_HOZZARENDELT       && $sor['szemelyzet_id']==KULSO_KIVITELEZO && ( _jog(JOG_MUNKALAP_KEZEL_KULSO) || ( _jog(JOG_MUNKALAP_SAJAT_KEZEL) && $_SESSION[get_db_tabla().'szemelyzet_id']==KULSO_KIVITELEZO ) ) ) ||
                ( /* Modell nem számít */ $sor['stat_id']==MUNKALAP_STAT_ANYAGRAVAR         && _jog(JOG_MUNKALAP_ANYAG_ERKEZTET)    ) ||
                ( /* Modell nem számít */ $sor['stat_id']==MUNKALAP_STAT_ELOKESZITES_ALATT  && $_SESSION[get_db_tabla().'szemelyzet_id']==$sor['szemelyzet_id'] && _jog( JOG_MUNKALAP_KULSOS_ELOKESZIT )  ) ||
                ( /* Modell nem számít */ $sor['stat_id']==MUNKALAP_STAT_VIZSGALAT_ALATT    && _jog(JOG_MUNKALAP_VIZSGALAT)         ) ||

                // Egyszállodás modell esetén az Átadás Diszpécsernek gomb joga
                ( !$telephely['modell'] && $sor['stat_id']==MUNKALAP_STAT_FELVEVE && $kezeles_jog )
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
        if( $sor['stat_id'] != MUNKALAP_STAT_ANYAGRAVAR && $sor['stat_id'] != MUNKALAP_STAT_ELOKESZITES_ALATT ) {

            // Nyitott státusznál az is feltétel, hogy a tétel is nyitott-e, azaz nincs átadta dátum kitöltve. Egyébként csak a feltétel, hogy létezzen utolsó tétel.
            $sql_kieg = '';
            if( $sor['stat_id'] == MUNKALAP_STAT_HOZZARENDELT ) {
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
        if( $munkalap['stat_id'] == MUNKALAP_STAT_ANYAGRAVAR ) {
            $sql = $adatbazis_kapcsolat->d('munkalap_anyag', "`tetel_id`='{$utolso_tetel['recid']}' AND `cikk_men`=0.00");
            if( $sql === false ) { $db_error = true; }
            $sql = $adatbazis_kapcsolat->u('munkalap_anyag', array('szuk_men'), array(0.00), "`tetel_id`='{$utolso_tetel['recid']}' AND `cikk_men`!=0.00");
            if( $sql === false ) { $db_error = true; }
        }

        // Frissítjük a munkalap tételt, ha épp dolgozunk rajta, azaz felvéve vagy anyagra vár státuszban van (hozzárendelt státuszban is kellene, de előbb státuszt léptetünk sztornóra várra, és majd abból továbbléptetve tesszük ezt meg).
        if( $munkalap['stat_id'] == MUNKALAP_STAT_FELVEVE || $munkalap['stat_id'] == MUNKALAP_STAT_ANYAGRAVAR ) {
            $sql = $adatbazis_kapcsolat -> u('munkalap_tetel',
                                                array('atadta_dt',  'visszaadva', 'erteklista_id', 'update_usr'                           ),
                                                array('NOW()',      1,            $erteklista_id,  $_SESSION[get_db_tabla().'teljes_nev'] ),
                                                "`recid` = '".$utolso_tetel['recid']."'"
                                            );
            if( $sql === false ) { $db_error = true; }
        }

        // Hozzárendelt státuszban le kell tárolni, hogy a visszaadás módja az Visszaadás volt, az értéklista id-t, mert a következő státuszból ez dönti majd el, hogyan léptetjük még egyel tovább.
        if( $sor['stat_id'] == MUNKALAP_STAT_HOZZARENDELT ) {
            $sql = $adatbazis_kapcsolat -> u('munkalap',
                                                array('vissza_mod',             'vissza_erteklista_id', 'update_usr'                           ),
                                                array( VISSZA_MOD_VISSZAADAS,   $erteklista_id,         $_SESSION[get_db_tabla().'teljes_nev'] ),
                                                "`recid` = '{$recid}'"
                                            );
            if( $sql === false ) { $db_error = true; }
        // Ha nem hozzárendelt státuszban vagyunk, akkor frissítjük a ML-ot, de hozzárendelt státusz alatt még csak egy státusz léptetés történik majd
        } else {
            // Frissítjuk a munkalap visszaadva sorát, ürítjük a személyzeteket és egyéb adatokat
            $sql = $adatbazis_kapcsolat -> u('munkalap',
                                                array('visszaadva', 'szemelyzet_id', 'szallito_id', 'jh_ert',    'becs_ertek',  'mail_ment',    'direktbe', 'mobil_loginnev', 'mobil', 'varh_erk',              'update_usr'                           ),
                                                array( 1,           0,               '',             0,           0,             0,              0,           '',              '',      '0000-00-00 00:00:00',   $_SESSION[get_db_tabla().'teljes_nev'] ),
                                                "`recid` = '{$recid}'"
                                            );
            if( $sql === false ) { $db_error = true; }
        }

        // Vizsgálat alatt a bejegyzéseket folyamatbanra állítjuk
        if( $munkalap['stat_id'] == MUNKALAP_STAT_VIZSGALAT_ALATT ) {

            // Ekkor a memo-t is megtoldjuk
            $memo = 'Reklamáció elfogadva'."\r\n".$memo;

            $bejelentes = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$recid}'");
            if( $sql === false ) { $db_error = true; }
            if($bejelentes['bejelentes_id']){
                if( !addStatusz( 'bejelentes_statusz', $bejelentes['bejelentes_id'], BEJELENTES_STAT_FOLYAMATBAN ) ) {
                    $db_error = true;
                }
            }
        }

        // Hozzárendelt státusz alatt "Sztornóra vár"-ra állítjuk a ML-ot
        if( $sor['stat_id'] == MUNKALAP_STAT_HOZZARENDELT ) {
            if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_SZTORNO_VAR, $erteklista_id, $memo ) ) {
                $db_error = true;
            }
        // Egyébként Nyitott státuszra állítjuk a ML-ot
        } else {
            if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_NYITOTT, $erteklista_id, $memo ) ) {
                $db_error = true;
            }
        }

        // Hozzáadjuk a megjegyzést is ha van
        if( $memo ) {
            $megj_felvesz = addMegj('munkalap', $recid, 'Visszaadás oka:'."\r\n".$memo);
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

// Átadás - visszaadás diszpécsernek direktbe
if( isset($_POST['atadasDiszpecsernekDirektbe']) ) {
    $recid          = $_POST['recid'];
    $erteklista_id  = $_POST['erteklista_id'];
    $memo           = erteklista(ERTEKLISTA_ATADAS_OKA, $erteklista_id)."\r\n".$_POST['memo']."\r\n";

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $erteklista_id ) {

        // Státusz és jog ellenőrzése
        $munkalap = $sor = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id telephely_id szallito_id', 'munkalap', "`recid`='{$recid}'");
        if( $munkalap === false ) { $db_error = true; }

        $telephely    = $adatbazis_kapcsolat->s2('modell', 'telephely', "`id`='{$munkalap['telephely_id']}'");
        if( $telephely === false ) { $db_error = true; }

        $utolso_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id`='{$recid}'", 'recid desc', '1');
        if( $utolso_tetel === false ) { $db_error = true; }

        $felvehetik = array(TELEPITETT_SZEMELYZET, MOBILCSOPORT);
        $kezeles_jog = get_kezeles_jog($sor['szemelyzet_id']);
        // Itt több feltétel volt, de csak a hozzárendelt státuszút hagytuk meg, mert direktes ág csak hozzárendelt státusz alatt van
        if(
            !(
                ( /* Modell nem számít */ $sor['stat_id']==MUNKALAP_STAT_HOZZARENDELT       && $sor['szemelyzet_id']==KULSO_KIVITELEZO && ( _jog(JOG_MUNKALAP_KEZEL_KULSO) || ( _jog(JOG_MUNKALAP_SAJAT_KEZEL) && $_SESSION[get_db_tabla().'szemelyzet_id']==KULSO_KIVITELEZO ) ) )
            )
           ){
            echo json_encode(array(
                'hiba'  => 'Nincs joga a visszaadáshoz!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }
        
        // Ha van levélküldés, akkor ellenőrizzük a többi mezőt is
        if($_POST['email']){
            if(!trim($_POST['cimzett'])){
                echo json_encode(array(
                    'hiba'  => 'Kérem adja meg a címzett email címét!',
                    'title' => 'Hiba',
                    'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="$(\'#email_kuldessel\').val(0); alert2();">',
                    'focus' => 'pop_cimzett',
                ));

                $weblap->leallit();
                exit();
            }

            // Címzett mezőben levő adat ellenőrzése
            $cimzett_hibak = is_valid_mail_tobb($_POST['cimzett'], 'Hibaüzenettel');
            if( $cimzett_hibak ){
                echo json_encode(array(
                    'hiba'  => $cimzett_hibak,
                    'title' => 'Hiba',
                    'focus' => 'pop_cimzett',
                ));

                $weblap->leallit();
                exit();
            }
            // Másolat mezőben levő adat ellenőrzése
            $cimzett_hibak = is_valid_mail_tobb($_POST['masolat'], 'Hibaüzenettel');
            if( $cimzett_hibak ){
                echo json_encode(array(
                    'hiba'  => $cimzett_hibak,
                    'title' => 'Hiba',
                    'focus' => 'pop_masolat',
                ));

                $weblap->leallit();
                exit();
            }
            
            // Címzettek tömb előállítása
            $mailcimek = explode(',', $_POST['cimzett']);
            $mailcimek = array_values( array_filter($mailcimek) );
            
            // Másolatot kapnak tömb előállítása
            $masolatok = explode(',', $_POST['masolat']);
            array_push($masolatok, get_mszk_mail(), get_mszk_mail2()); // Így az MSZK-k is kapnak értesítő levelet
            $masolatok = array_values( array_filter($masolatok) );

            // Tárgy ellenőrzése
            if(!trim($_POST['targy'])){
                echo json_encode(array(
                    'hiba'  => 'Kérem töltse ki az email tárgyát!',
                    'title' => 'Hiba',
                    'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="$(\'#email_kuldessel\').val(0); alert2();">',
                    'focus' => 'pop_targy',
                ));

                $weblap->leallit();
                exit();
            }
            
            // Üzenet ellenőrzése
            if(!trim($_POST['uzenet'])){
                echo json_encode(array(
                    'hiba'  => 'Kérem töltse ki a email üzenetét!',
                    'title' => 'Hiba',
                    'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="$(\'#email_kuldessel\').val(0); alert2();">',
                    'focus' => 'pop_uzenet',
                ));

                $weblap->leallit();
                exit();
            }

            $szallito = $adatbazis_kapcsolat->s2('nev', 'szallito', "`id`='{$munkalap['szallito_id']}'");
            if( $szallito === false ) { $db_error = true; }

            // Attól függően, hogy melyik rendszerben vagyunk, kap a tárgy/üzenet egy [Teszt] előtagot.
            $kieg = get_mail_teszt_felirat();
            $targy_kieg = $kieg[0];
            $uzi_kieg   = $kieg[1];
            
            // Csatolmányok összeszdése
            $mappa = $adatbazis_kapcsolat->s2('ikt_szam', 'munkalap', "`recid`='{$recid}'");
            $utvonal = '../'.get_utvonal().'munkalap/'.str_replace('/', '_', $mappa['ikt_szam']).'/email/*';
            $fajlok_mailnek = array();
            $fajlok = glob($utvonal);

            $sikeres_kuldes = level_kuldes_osszetettebb(get_nev(), get_mail(), $mailcimek, $mailcimek, $masolatok, $masolatok, $r_masolatok, $r_masolatok, $targy_kieg.$_POST['targy'], $uzi_kieg.nl2br2($_POST['uzenet']), $fajlok, 'levélpapíros');
            if( $sikeres_kuldes ) {
                mysqli_rollback($db_kapcs);
                echo json_encode(array(
                    'hiba'  => 'Levélküldés közben hiba történt! '.$sikeres_kuldes,
                    'title' => 'Hiba'
                ));

                $weblap->leallit();
                exit();
                
            } else {
                
                $cimzettek          = implode(', ', $mailcimek);
                $masolatot_kapott   = '';
                $emailkuldes_megj .= 'Megrendelés SZTORNÓ elküldve.'."\n".'Címzettek: '.$cimzettek.' ';

                if($masolatok){
                    $masolatot_kapott   = implode(', ', $masolatok);
                    $emailkuldes_megj .= "\n".'Másolatot kaptak: '.$masolatot_kapott.' ';
                }
                $emailkuldes_megj .= "\n";

                // E-mail küldés logolása
                $email_recid = $adatbazis_kapcsolat->i('munkalap_email',    array('munkalap_recid', 'szallito_id', 'cimzett',   'masolat',         'targy',                     'text',                                'insert_dt', 'insert_usr'                           ),
                                                                            array($recid,           $szallito_id,  $cimzettek,  $masolatot_kapott, $targy_kieg.$_POST['targy'], $uzi_kieg.nl2br2($_POST['uzenet']),    'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'] )
                                            );
                if( $email_recid === false ) { $db_error = true; }
                
                // Fájlok mozgatása
                if( csatolmanyok_eltesz($fajlok, $utvonal, $email_recid, $recid, 'munkalap') === false ) {
                    $db_error = true;
                }
                
            }

        // Ha nincs levélküldés, akkor rákérdezünk, hogy biztosan nem szerettek volna levelet küldeni?
        }elseif(!$_POST['mehet']){
            echo json_encode(array(
                'hiba'  => 'Nem küldött visszavételező emailt.<br>Folytatja?',
                'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#mehet_mail_nelkul\').val(\'1\'); $(\'#atadasDiszpecsernekForm\').submit();"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">',
                'title' => 'Kérdés',
            ));
            $weblap->leallit();
            exit();
        }

        // Frissítjük a munkalap tételt, ha épp dolgozunk rajta
        $sql = $adatbazis_kapcsolat -> u('munkalap_tetel',
                                            array('atadta_dt',  'visszaadva', 'erteklista_id', 'update_usr'                           ),
                                            array('NOW()',      1,            $erteklista_id,  $_SESSION[get_db_tabla().'teljes_nev'] ),
                                            "`recid` = '".$utolso_tetel['recid']."'"
                                        );
        if( $sql === false ) { $db_error = true; }

        // Frissítjuk a munkalap visszaadva sorát, ürítjük a személyzeteket és egyéb adatokat
        $sql = $adatbazis_kapcsolat -> u('munkalap',
                                            array('visszaadva', 'szemelyzet_id', 'mobil_loginnev', 'szallito_id', 'mobil', 'jh_ert', 'varh_erk',            'becs_ertek',   'mail_ment',    'direktbe', 'update_usr'                           ),
                                            array( 1,            0,              '',               '',            '',      '',       '0000-00-00 00:00:00',  0,             0,              0,          $_SESSION[get_db_tabla().'teljes_nev'] ),
                                            "`recid` = '{$recid}'"
                                        );
        if( $sql === false ) { $db_error = true; }

        // Nyitott státuszra állítjuk a munkalapot
        if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_NYITOTT, $erteklista_id, $memo.$emailkuldes_megj ) ) {
            $db_error = true;
        }

        // Hozzáadjuk a megjegyzést is ha van
        if( $memo.$emailkuldes_megj ) {
            $megj_felvesz = addMegj('munkalap', $recid, 'Visszaadás oka:'."\r\n".$memo.$emailkuldes_megj);
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
            ( $sor['stat_id']==MUNKALAP_STAT_HOZZARENDELT       && $sor['szemelyzet_id']==KULSO_KIVITELEZO && ( _jog(JOG_MUNKALAP_KEZEL_KULSO) || ( _jog(JOG_MUNKALAP_SAJAT_KEZEL) && $_SESSION[get_db_tabla().'szemelyzet_id']==KULSO_KIVITELEZO ) ) ) ||
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
        if( $sor['stat_id']==MUNKALAP_STAT_HOZZARENDELT ){

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
        } else {

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
                'hiba'  => 'A(z) "'.erteklista(ERTEKLISTA_ELV_FELAD, $erteklista_id).'" feladat már hozzáadásra került!',
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
        $sor = $adatbazis_kapcsolat->s2('stat_id', 'munkalap', "`recid` = '{$recid}'");
        if( $sor === false ) { $db_error = true; }

        // Státusz ellenőrzése
        if( $sor['stat_id'] != MUNKALAP_STAT_NYITOTT && $sor['stat_id'] != MUNKALAP_STAT_ELOKESZITES_ALATT ) {
            echo json_encode(array(
                'hiba'  => 'Ebben a státuszban nem adható át a Munkalap telepítettnek!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzése
        $engedelyezett_szemelyzettipusok = array(MUSZAKI_VEZETO, TERULETI_FELELOS, MUSZAKI_ADMINISZTRATOR, MSZK_VEZETO);
        if(
                ($sor['stat_id'] == MUNKALAP_STAT_NYITOTT && !_jog(JOG_MUNKALAP_NYITOTT_KEZEL)) ||
                ($sor['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT && _jog(JOG_MUNKALAP_KULSOS_ELOKESZIT) && $sor['szemelyzet_id']==$_SESSION[get_db_tabla().'szemelyzet_id'] && in_array($_SESSION[get_db_tabla().'szemelyzet_id'], $engedelyezett_szemelyzettipusok))
          ) {
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
                                                    array('szemelyzet_id',       'visszaadva', 'szallito_id', 'becs_ertek', 'mail_ment',  'direktbe',   'jh_ert', 'varh_erk',            'jh1_login', 'jh1_datum',           'jh1_szerep', 'update_usr'                            ),
                                                    array(TELEPITETT_SZEMELYZET, 0,            '',            0,            0,            0,            0,        '0000-00-00 00:00:00', '',          '0000-00-00 00:00:00', 0,            $_SESSION[get_db_tabla().'teljes_nev']  ),
                                                    "`recid` = '{$recid}'");
        if( $sql === false ) { $db_error = true; }

        // Státusz léptetés
        if( !addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_HOZZARENDELT ) ) {
            $db_error = true;
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            // Levélküldés
            if($sor['stat_id'] != MUNKALAP_STAT_ELOKESZITES_ALATT){
                $hiba = '';
                $valasz = level_felkeszit_es_kuld('munkalap', $recid, ERT_MUNK_KK_ELOKESZIT_KOR, 'munkalapElokeszit', '_jog'.JOG_MUNKALAP_KULSOS_ELOKESZIT);
                if( substr($valasz, 0, 4)=='Hiba' ){
                    $hiba .= $valasz;
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
        } else {
            if( addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_HOZZARENDELT ) === false ) {
                $db_error = true;
            }
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            // Levélküldés
            if( $munkalap['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT ){
                $hiba = '';
                $valasz = level_felkeszit_es_kuld('munkalap', $recid, ERT_MUNK_KK_ELOKESZIT_KOR, 'munkalapElokeszit', '_jog'.JOG_MUNKALAP_KULSOS_ELOKESZIT);
                if( substr($valasz, 0, 4)=='Hiba' ){
                    $hiba .= $valasz;
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
        $munkalap = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id telephely_id', 'munkalap', "`recid`='{$recid}'");
        if( $munkalap === false ) { $db_error = true; }

        $telephely  = $adatbazis_kapcsolat -> s2('nev modell', 'telephely', "`id`='{$munkalap['telephely_id']}'");

        $engedelyezett_statuszok = array( MUNKALAP_STAT_NYITOTT, MUNKALAP_STAT_HOZZARENDELT, MUNKALAP_STAT_FELVEVE, MUNKALAP_STAT_ELOKESZITES_ALATT );
        if( !in_array($munkalap['stat_id'], $engedelyezett_statuszok) ) {
            echo json_encode(array(
                'hiba'  => 'Ebben a státuszban nem adható át a munkalap!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzése
        $kezeles_jog = get_kezeles_jog($munkalap['szemelyzet_id']);
        if(
            !( // Ha nem igaz a következő feltétel rendszer
                ( $munkalap['stat_id'] == MUNKALAP_STAT_NYITOTT             && _jog(JOG_MUNKALAP_NYITOTT_KEZEL)      ) ||    // Nyitott státusz alatt nyitottak kezelése jog
                ( $munkalap['stat_id'] == MUNKALAP_STAT_HOZZARENDELT        && $kezeles_jog && !$telephely['modell'] ) ||    // Folyamatban státusz alatt fontos, hogy kezelés joga legyen és egyszállodás modell
                ( $munkalap['stat_id'] == MUNKALAP_STAT_FELVEVE             && $kezeles_jog && !$telephely['modell'] ) ||    // Felvéve státusz alatt fontos, hogy kezelés joga legyen és egyszállodás modell
                ( $munkalap['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT   && _jog(JOG_MUNKALAP_KULSOS_ELOKESZIT)   )       // Előkészítés státusz alatt előkészítés jog
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

        }elseif($munkalap['stat_id'] == MUNKALAP_STAT_HOZZARENDELT || $munkalap['stat_id'] == MUNKALAP_STAT_FELVEVE){

            if( addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_ELOKESZITES_ALATT ) === false ) {
                $db_error = true;
            }

            // Ha felvéve státuszban voltunk, akkor az utolsó tételnél az átadva dátumot kitöltjük
            if( $munkalap['stat_id'] == MUNKALAP_STAT_FELVEVE ){
                // Fevéve sáttusz esetén fentebb már lekértük az utolsó tétel recid-ját
                $sql = $adatbazis_kapcsolat->u('munkalap_tetel', array('atadta_dt'), array('NOW()'), "`recid`='{$utolso_tetel['recid']}'");
                if( $sql === false ) { $db_error = true; }
            }

            // FONTOS: Ha idáig eljutottunk, akkor már támaszkodhatunk arra, hogy egyszállodás modellben vagyunk, és van kezelés jog. Elvégre fentebb a jog ellenőrzésénél már hibát dobtunk volna.
            // Ha kifejezetten hozzárendeltben vagyunk, akkor ellenőrizzük a ML-hoz kapcsolódó bejelentést. Ha ez nyitott, akkor folyamatban ra kell léptetni.
            if( $munkalap['stat_id'] == MUNKALAP_STAT_HOZZARENDELT ){

                $bejelentes = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$recid}'");
                if( $bejelentes === false ) { $db_error = true; }
                $bejelentes = $adatbazis_kapcsolat->s2('recid stat_id', 'bejelentes', "`recid`='{$bejelentes['bejelentes_id']}'");
                if( $bejelentes === false ) { $db_error = true; }
                if($bejelentes['stat_id']==BEJELENTES_STAT_NYITOTT){
                    if( addStatusz( 'bejelentes_statusz', $bejelentes['recid'], BEJELENTES_STAT_FOLYAMATBAN ) === false ) {
                        $db_error = true;
                    }
                }
            }

        } else {

            if( addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_HOZZARENDELT ) === false ) {
                $db_error = true;
            }

        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            // Levélküldés
            if($munkalap['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT){
                $hiba = '';
                $valasz = level_felkeszit_es_kuld('munkalap', $recid, ERT_MUNK_KK_ELOKESZIT_KOR, 'munkalapElokeszit', '_jog'.JOG_MUNKALAP_KULSOS_ELOKESZIT);
                if( substr($valasz, 0, 4)=='Hiba' ){
                    $hiba .= $valasz;
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
        } else {
            if( addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_HOZZARENDELT ) === false ) {
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
        } else {
            if( addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_HOZZARENDELT ) === false ) {
                $db_error = true;
            }
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            // Levélküldés
            if( $munkalap['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT ){
                $hiba = '';
                $valasz = level_felkeszit_es_kuld('munkalap', $recid, ERT_MUNK_KK_ELOKESZIT_KOR, 'munkalapElokeszit', '_jog'.JOG_MUNKALAP_KULSOS_ELOKESZIT);
                if( substr($valasz, 0, 4)=='Hiba' ){
                    $hiba .= $valasz;
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
    $indoklas = trim($_POST['indoklas']);

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

            // Előkészítés alatt az indoklás kötelező
            if(!$indoklas){
                echo json_encode(array(
                    'hiba'  => 'Kérem indokolja meg az átadást!',
                    'title' => 'Hiba',
                    'focus' => 'atadas_indok',
                ));
                $weblap->leallit();
                exit();
            }

            if( addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_ELOKESZITES_ALATT, '', $indoklas ) === false ) {
                $db_error = true;
            }

            if(addMegj('munkalap', $recid, 'MSzK vezetőnek átadás indoklása:'."\r\n".$indoklas)===false){
                $db_error = true;
            }

        } else {
            if( addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_HOZZARENDELT ) === false ) {
                $db_error = true;
            }
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            // Levélküldés
            if( $munkalap['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT ){
                $hiba = '';
                $valasz = level_felkeszit_es_kuld('munkalap', $recid, ERT_MUNK_KK_ELOKESZIT_KOR, 'munkalapElokeszit', '_jog'.JOG_MUNKALAP_KULSOS_ELOKESZIT);
                if( substr($valasz, 0, 4)=='Hiba' ){
                    $hiba .= $valasz;
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
        } else {
            if( addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_HOZZARENDELT ) === false ) {
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
if( isset($_POST['munkalapHIUtemez']) ) {

    $recid = $_POST['recid'];
    $datum = str_replace('.', '-', $_POST['datum']);

    if( $recid ) {

        $db_error = false;
        mysqli_autocommit($db_kapcs, false);

        // Dátum nem lehet korábbi mint a jelen
        if( $_POST['datum'] <= date('Y.m.d') ) {
            echo json_encode(array(
                'hiba'  => 'Csak jövőbeli dátumra ütemezhető egy munkalap!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }

        // Dátum helyes-e?
        if( !valos_datum_e($_POST['datum']) ) {
            echo json_encode(array(
                'hiba'  => 'Helytelen dátum! Kérem válasszon egy érvényes dátumot!',
                'title' => 'Hiba',
            ));
            $weblap->leallit();
            exit();
        }

        $megj = 'Dátumra ütemezve: '.$_POST['datum'];
        if( addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_HATIDO_UTEMEZETT, '', $megj ) === false ) {
            $db_error = true;
        }

        // És egy megjegyzést is
        if( addMegj('munkalap', $recid, $megj) === false ) {
            $db_error = true;
        }

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
         foreach ( $bejelentesek as $bej ) {
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
if( isset($_POST['munkalapVisszaNyitott']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $recid = $_POST['recid'];
    $statusz = addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_NYITOTT);
    if( $statusz === false ) { $db_error = true; }

    // Munkalaphoz tartozó Bejelentések státuszát pedig folyamatbanra léptetjük
    $bejelentesek = $adatbazis_kapcsolat->s('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$recid}'");
    foreach ( $bejelentesek as $bej ) {

        // Adott bejelentésenk van-e több munkalapja?
        $munkalapok = $adatbazis_kapcsolat->s('munkalap_id', 'munkalap_kapcs', "`bejelentes_id`='{$bej['bejelentes_id']}' AND `munkalap_id`!='{$recid}'");
        if( $munkalapok === false ) { $db_error = true; }
        $munkalapok = adatbazis2tomb($munkalapok, 'munkalap_id');

        $hataridore_utemezett = '';
        if( $munkalapok ) {
            $hataridore_utemezett = $adatbazis_kapcsolat->s('recid', 'munkalap', "`stat_id`='".MUNKALAP_STAT_HATIDO_UTEMEZETT."' AND `recid` IN (".implode(',', $munkalapok).")");
            if( $hataridore_utemezett === false ) { $db_error = true; }
        }

        // Ha nincs több határidőre ütemezett munkalapja, akkor Folyamatban státuszt kap
        if( !$hataridore_utemezett ) {
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

// Határidőre ütemezésből vissza nyitottba
if( isset($_POST['munkalapVisszavetel']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $recid = $_POST['recid'];

    $munkalap = $adatbazis_kapcsolat->s2('stat_id', 'munkalap', "`recid` = '{$recid}'");
    if( $munkalap === false ) { $db_error = true; }

    // Jog ellenőrzése
    if( !_jog(JOG_MUNKALAP_NYITOTT_KEZEL) ) {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga a munkalapot visszavenni!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    // Státusz ellenőrzése
    if( $munkalap['stat_id'] != MUNKALAP_STAT_HOZZARENDELT ) {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => 'A munkalap nincs hozzárendelt státuszban, így már nem lehet visszavenni!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    // Státusz léptetés
    $statusz = addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_NYITOTT);
    if( $statusz === false ) { $db_error = true; }

    // Munkalap update
    $adatbazis_kapcsolat->u('munkalap', array('szallito_id', 'szemelyzet_id', 'mobil', 'becs_ertek', 'mail_ment', 'direktbe', 'update_usr'                          ),
                                        array('',            '',              '',       0,           0,           0,          $_SESSION[get_db_tabla().'teljes_nev']),
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

// ML visszavételezése ha direktbe volt hozzárendelve a kivitelező
if( isset($_POST['munkalapVisszavetelDirektbe']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $recid = $_POST['recid'];

    $munkalap = $adatbazis_kapcsolat->s2('stat_id szallito_id', 'munkalap', "`recid` = '{$recid}'");
    if( $munkalap === false ) { $db_error = true; }

    // Jog ellenőrzése
    if( !_jog(JOG_MUNKALAP_NYITOTT_KEZEL) ) {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga a munkalapot visszavenni!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    // Státusz ellenőrzése
    if( $munkalap['stat_id'] != MUNKALAP_STAT_HOZZARENDELT ) {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => 'A munkalap nincs hozzárendelt státuszban, így már nem lehet visszavenni!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }
    
    // Ha van levélküldés, akkor ellenőrizzük a többi mezőt is
    if( $_POST['email'] ) {
        if(!trim($_POST['cimzett'])){
            echo json_encode(array(
                'hiba'  => 'Kérem adja meg a címzett email címét!',
                'title' => 'Hiba',
                'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="$(\'#email_kuldessel\').val(0); alert2();">',
                'focus' => 'pop_cimzett',
            ));

            $weblap->leallit();
            exit();
        }

        // Címzett mezőben levő adat ellenőrzése
        $cimzett_hibak = is_valid_mail_tobb($_POST['cimzett'], 'Hibaüzenettel');
        if( $cimzett_hibak ){
            echo json_encode(array(
                'hiba'  => $cimzett_hibak,
                'title' => 'Hiba',
                'focus' => 'pop_cimzett',
            ));

            $weblap->leallit();
            exit();
        }
        // Másolat mezőben levő adat ellenőrzése
        $cimzett_hibak = is_valid_mail_tobb($_POST['masolat'], 'Hibaüzenettel');
        if( $cimzett_hibak ){
            echo json_encode(array(
                'hiba'  => $cimzett_hibak,
                'title' => 'Hiba',
                'focus' => 'pop_masolat',
            ));

            $weblap->leallit();
            exit();
        }

        // Címzettek tömb előállítása
        $mailcimek = explode(',', $_POST['cimzett']);
        $mailcimek = array_values( array_filter($mailcimek) );

        // Másolatot kapnak tömb előállítása
        $masolatok = explode(',', $_POST['masolat']);
        array_push($masolatok, get_mszk_mail(), get_mszk_mail2()); // Így az MSZK-k is kapnak értesítő levelet
        $masolatok = array_values( array_filter($masolatok) );

        // Tárgy ellenőrzése
        if(!trim($_POST['targy'])){
            echo json_encode(array(
                'hiba'  => 'Kérem töltse ki az email tárgyát!',
                'title' => 'Hiba',
                'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="$(\'#email_kuldessel\').val(0); alert2();">',
                'focus' => 'pop_targy',
            ));

            $weblap->leallit();
            exit();
        }

        // Üzenet ellenőrzése
        if(!trim($_POST['uzenet'])){
            echo json_encode(array(
                'hiba'  => 'Kérem töltse ki a email üzenetét!',
                'title' => 'Hiba',
                'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="$(\'#email_kuldessel\').val(0); alert2();">',
                'focus' => 'pop_uzenet',
            ));

            $weblap->leallit();
            exit();
        }

        $szallito = $adatbazis_kapcsolat->s2('nev', 'szallito', "`id`='{$munkalap['szallito_id']}'");
        if( $szallito === false ) { $db_error = true; }

        // Attól függően, hogy melyik rendszerben vagyunk, kap a tárgy/üzenet egy [Teszt] előtagot.
        $kieg = get_mail_teszt_felirat();
        $targy_kieg = $kieg[0];
        $uzi_kieg   = $kieg[1];
        
        // Csatolmányok összeszdése
        $mappa = $adatbazis_kapcsolat->s2('ikt_szam', 'munkalap', "`recid`='{$recid}'");
        $utvonal = '../'.get_utvonal().'munkalap/'.str_replace('/', '_', $mappa['ikt_szam']).'/email/*';
        $fajlok_mailnek = array();
        $fajlok = glob($utvonal);

        $sikeres_kuldes = level_kuldes_osszetettebb(get_nev(), get_mail(), $mailcimek, $mailcimek, $masolatok, $masolatok, $r_masolatok, $r_masolatok, $targy_kieg.$_POST['targy'], $uzi_kieg.nl2br2($_POST['uzenet']), $fajlok, 'levélpapíros');
        if( $sikeres_kuldes ) {
            mysqli_rollback($db_kapcs);
            echo json_encode(array(
                'hiba'  => 'Levélküldés közben hiba történt! '.$sikeres_kuldes,
                'title' => 'Hiba'
            ));

            $weblap->leallit();
            exit();
            
        } else {
            
            $cimzettek          = implode(', ', $mailcimek);
            $masolatot_kapott   = '';
            $emailkuldes_megj .= 'Megrendelés SZTORNÓ elküldve.'."\n".'Címzettek: '.$cimzettek.' ';
            if($masolatok){
                $masolatot_kapott   = implode(', ', $masolatok);
                $emailkuldes_megj .= "\n".'Másolatot kaptak: '.$masolatot_kapott.' ';
            }
            $emailkuldes_megj .= "\n";
            // E-mail küldés logolása
            $email_recid = $adatbazis_kapcsolat->i('munkalap_email',    array('munkalap_recid', 'szallito_id', 'cimzett',  'masolat',          'targy',                     'text',                                'insert_dt', 'insert_usr'                           ),
                                                                        array($recid,           $szallito_id,  $cimzettek, $masolatot_kapott,  $targy_kieg.$_POST['targy'], $uzi_kieg.nl2br2($_POST['uzenet']),    'NOW()',     $_SESSION[get_db_tabla().'teljes_nev'] )
                                        );
            if( $email_recid === false ) { $db_error = true; }
            
            // Fájlok mozgatása
            if( csatolmanyok_eltesz($fajlok, $utvonal, $email_recid, $recid, 'munkalap') === false ) {
                $db_error = true;
            }
            
        }


    // Ha nincs levélküldés, akkor rákérdezünk, hogy biztosan nem szerettek volna levelet küldeni?
    } elseif( !$_POST['mehet'] ) {
        echo json_encode(array(
            'hiba'  => 'Nem küldött visszavételező emailt.<br>Folytatja?',
            'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#mehet_mail_nelkul\').val(\'1\'); $(\'#munkalapKulso\').submit();"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">',
            'title' => 'Kérdés',
        ));
        $weblap->leallit();
        exit();
    }

    // Státusz léptetés
    $statusz = addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_NYITOTT, '', $emailkuldes_megj);
    if( $statusz === false ) { $db_error = true; }
    
    // A levélküldés memót a megjegyzésbe is beírjuk, nem csak a státuszsorba
    if( $emailkuldes_megj ) {
        if(addMegj('munkalap', $recid, $emailkuldes_megj)===false){
            $db_error = true;
        }
    }


    // Munkalap update
    $adatbazis_kapcsolat->u('munkalap', array('szallito_id', 'szemelyzet_id', 'mobil', 'becs_ertek', 'mail_ment', 'direktbe', 'update_usr'                              ),
                                        array('',            '',              '',       0,           0,           0,          $_SESSION[get_db_tabla().'teljes_nev']    ),
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

// Munkalap várakoztatása
if (isset($_POST['munkalapVarakoztat'])) {

    $recid = $_POST['recid'];
    $memo  = trim($_POST['memo']);

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if(!$memo){
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => 'Kérem indokolja a várakoztatást!',
            'title' => 'Hiba',
            'focus' => 'sztorno_memo',
        ));
        $weblap->leallit();
        exit();
    }

    $munkalap = $sor = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id', 'munkalap', "`recid`='{$recid}'");
    if( $munkalap === false ) { $db_error = true; }

    // Státusz ellenőrzése
    if( $sor['stat_id'] != MUNKALAP_STAT_ELOKESZITES_ALATT ) {
        echo json_encode(array(
            'hiba'  => 'Ebben a státuszban nem várakoztatható a munkalap!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }

    // Jog ellenőrzése
    if(
        !(
            _jog(JOG_MUNKALAP_VARAKOZTAT) && $_SESSION[get_db_tabla().'szemelyzet_id']==$sor['szemelyzet_id']
        )
    ) {
        echo json_encode(array(
            'hiba'  => 'Nincs joga várakoztatáshoz!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }

    // Státusz léptetés
    $statusz = addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_VARAKOZO, '', $memo);
    if( $statusz === false ) { $db_error = true; }

    // Megjegyzés
    $megj = addMegj('munkalap', $recid, "Várakoztatás oka\r\n".$memo);
    if( $megj === false ) { $db_error = true; }

    // Ha van bejelentés a munkalaphoz, akkor annak a státuszát is léptetjük, plusz a bejelentőt értesítjük
    $bejelentes = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$recid}'");
    if( $bejelentes === false ) { $db_error = true; }
    if($bejelentes){

        // Státusz léptetés
        $statusz = addStatusz( 'bejelentes_statusz', $bejelentes['bejelentes_id'], BEJELENTES_STAT_VARAKOZO, '', $memo);
        if( $statusz === false ) { $db_error = true; }

        $bejelentes = $adatbazis_kapcsolat->s2('recid', 'bejelentes', "`recid`='{$bejelentes['bejelentes_id']}'");
        if( $bejelentes === false ) { $db_error = true; }

        level_felkeszit_es_kuld('bejelentes', $bejelentes['recid'], ERT_BEJ_VARAKOZTATKOR, 'munkalapVarakoztat', 'bejelento', nl2br2($memo));

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

// Munkalap várakoztatásának vége
if (isset($_POST['munkalapVarakoztatVege'])) {

    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $munkalap = $sor = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id', 'munkalap', "`recid`='{$recid}'");
    if( $munkalap === false ) { $db_error = true; }

    // Státusz ellenőrzése
    if( $sor['stat_id'] != MUNKALAP_STAT_VARAKOZO ) {
        echo json_encode(array(
            'hiba'  => 'A munkalap státusza nem várakozó!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }

    // Jog ellenőrzése
    if(
        !(
            _jog(JOG_MUNKALAP_VARAKOZTAT) && $_SESSION[get_db_tabla().'szemelyzet_id']==$sor['szemelyzet_id']
        )
    ) {
        echo json_encode(array(
            'hiba'  => 'Nincs joga várakoztatás befejezéséhez!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }

    // Státusz léptetés
    $statusz = addStatusz( 'munkalap_statusz', $recid, MUNKALAP_STAT_ELOKESZITES_ALATT);
    if( $statusz === false ) { $db_error = true; }

    // Ha van bejelentés a munkalaphoz, akkor azt folyamatban státuszra léptetjük, DE csak akkor, ha a bejelentés más munkapajai között nincs várakozó
    $bejelentes = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$recid}'");
    if( $bejelentes === false ) { $db_error = true; }
    if($bejelentes){

        $van_varakozo_munkalap = false;
        $munkalapok = $adatbazis_kapcsolat->s('munkalap_id', 'munkalap_kapcs', "`bejelentes_id`='{$bejelentes['bejelentes_id']}'");
        foreach($munkalapok as $munkalap){

            $munkalap = $adatbazis_kapcsolat->s2('stat_id', 'munkalap', "`recid`='{$munkalap['munkalap_id']}'");

            if($munkalap['stat_id']==MUNKALAP_STAT_VARAKOZO){
                $van_varakozo_munkalap = true;
                break;
            }
        }

        // Ha a bejelentéshze nem tartozik olyan munkalap, aminek a státusza várakozó, akkor a bejelentést visszatesszük folyamatban státuszba
        if(!$van_varakozo_munkalap){
            $statusz = addStatusz( 'bejelentes_statusz', $bejelentes['bejelentes_id'], BEJELENTES_STAT_FOLYAMATBAN);
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

// Munkalap sztornózása
if (isset($_POST['munkalapSztornoz'])) {

    $recid = $_POST['recid'];
    $memo  = trim($_POST['memo']);

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if(!$memo){
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => 'Kérem indokolja a sztornózást!',
            'title' => 'Hiba',
            'focus' => 'sztorno_memo',
        ));
        $weblap->leallit();
        exit();
    }

    // Anyagfelhasználás ellenőrzése
    $van_felh_anyag = $adatbazis_kapcsolat->s('recid', 'munkalap_anyag', "`munkalap_id`='{$recid}'", '', '1');
    if($van_felh_anyag){
        echo json_encode(array(
            'hiba'  => 'Ehhez a munkalaphoz rögzítettek anyagfelhasználást.<br>Nem lehet sztornózni!',
        ));
        $weblap->leallit();
        exit();
    }

    $munkalap = $adatbazis_kapcsolat->s2('stat_id', 'munkalap', "`recid`='{$recid}'");
    if( $munkalap === false ) { $db_error = true; }

    // Státusz ellenőrzése
    if( $munkalap['stat_id'] != MUNKALAP_STAT_NYITOTT ) {
        echo json_encode(array(
            'hiba'  => 'Csak nyitott státuszú munkalapokat lehet sztornózni!',
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }

    // Jog ellenőrzése
    if(!_jog(JOG_MUNKALAP_NYITOTT_KEZEL)){
        echo json_encode(array(
            'hiba'  => 'Nincs joga munkalapot sztornózni!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    // Nézzük meg, hogy a munkalaphoz tartozó bejelentésnek hány munkalapja van
    $bejelentes = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$recid}'");
    if( $bejelentes === false ) { $db_error = true; }
    $bejelentes_recid = $bejelentes['bejelentes_id'];
    $munkalapok = $adatbazis_kapcsolat->s('munkalap_id', 'munkalap_kapcs', "`bejelentes_id`='{$bejelentes_recid}'");
    if( $munkalapok === false ) { $db_error = true; }


    // Ha a munkalapnak nincs bejelentése, akkor sztornó státuszba kerül
    if(!$bejelentes){
        if( addStatusz('munkalap_statusz', $recid, MUNKALAP_STAT_SZTORNO, '', $memo) === false ) {
            $db_error = true;
        }
        if( addMegj('munkalap', $recid, $memo) === false ){
            $db_error = true;
        }
    }

    // Ha bejelentésének ez az egyetlen munkalapja, kérdezünk
    if( count($munkalapok)==1 ){

        // Ha már tudjuk, hogy melyik státuszba mehet, akkor abba a státuszba léptetjük
        if($_POST['mehet']){

            // Munkalapot sztornózzuk
            if( addStatusz('munkalap_statusz', $recid, MUNKALAP_STAT_SZTORNO, '', $memo) === false ) {
                $db_error = true;
            }
            if( addMegj('munkalap', $recid, $memo) === false ){
                $db_error = true;
            }

            // A hozzá tartozó egyetlen bejegyzés státuszát a kért státuszba léptetjük
            if($_POST['mehet']=='nyitott'){
                if( addStatusz('bejelentes_statusz', $bejelentes_recid, BEJELENTES_STAT_NYITOTT, '', $munkalap['ikt_szam'].' munkalap sztornózva') === false ) {
                    $db_error = true;
                }
            }
            if($_POST['mehet']=='elutasit'){
                if( addStatusz('bejelentes_statusz', $bejelentes_recid, BEJELENTES_STAT_ELUTASITVA, '', $memo) === false ) {
                    $db_error = true;
                }

                // E-mail értesítés a bejelentőnek
                level_felkeszit_es_kuld('bejelentes', $bejelentes_recid, ERT_BEJ_ELUTASITKOR, 'bejelentesElutasit', 'bejelento', '<br>'.nl2br2($memo).'<br>');
            }

            // Indoklás bekerül a bejelentéshez is
            $munkalap = $adatbazis_kapcsolat->s2('ikt_szam', 'munkalap', "`recid`='{$recid}'");
            if( addMegj('bejelentes', $bejelentes_recid, $munkalap['ikt_szam'].' munkalap sztornózva') === false ){
                $db_error = true;
            }

        // Egyébként kérdezünk
        } else {
            echo json_encode(array(
                'hiba'  => 'A munkalaphoz tartozó bejelentést nyitott státuszba helyezi vagy elutasítja?',
                'gomb'  => '<input type="button" class="gomb zold" value="Nyitott státuszba" onclick="munkalapSztornoz(\'nyitott\')"><input type="button" class="gomb piros" value="Elutasítás" onclick="munkalapSztornoz(\'elutasit\')">',
            ));
            $weblap->leallit();
            exit();
        }

    }

    // Ha a bejelentésnek több munkalapja is van
    if( count($munkalapok)>1 ){

        // Munkalapot sztornózzuk + megjegyzés
        if( addStatusz('munkalap_statusz', $recid, MUNKALAP_STAT_SZTORNO, '', $memo) === false ) {
            $db_error = true;
        }
        if( addMegj('munkalap', $recid, $memo) === false ){
            $db_error = true;
        }

        $munkalapok_idk = adatbazis2tomb($munkalapok, 'munkalap_id');

        // Ha összes munkalap sztornózva státuszban van, bejelentés elutasítottra vált
        $van_nem_stornos = $adatbazis_kapcsolat->s2('recid', 'munkalap', "`recid`!='{$recid}' AND `recid` IN (".implode(', ', $munkalapok_idk).") AND `stat_id`!='".MUNKALAP_STAT_SZTORNO."'");
        if( $van_nem_stornos === false ) { $db_error = true; }
        $van_nem_stornos2 = $adatbazis_kapcsolat->s2('recid', 'munkalap', "`recid`!='{$recid}' AND `recid` IN (".implode(', ', $munkalapok_idk).") AND `stat_id` NOT IN ('".MUNKALAP_STAT_SZTORNO."', '".MUNKALAP_STAT_IGAZOLASRA_VAR."', '".MUNKALAP_STAT_LEZARASRA_VAR."', '".MUNKALAP_STAT_LEZART."')");
        if( $van_nem_stornos2 === false ) { $db_error = true; }

        if( !$van_nem_stornos ){
            if( addStatusz('bejelentes_statusz', $bejelentes_recid, BEJELENTES_STAT_ELUTASITVA, '', $memo) === false ) {
                $db_error = true;
            }

        // Ha összes munkalap sztornózva vagy igazolásra vár vagy lezárásra vár vagy lezárva státuszban van, bejelentés igazolásra vár státuszba kerül
        }elseif( !$van_nem_stornos2 ){
            if( addStatusz('bejelentes_statusz', $bejelentes_recid, BEJELENTES_STAT_KESZ_IG, '', $memo) === false ) {
                $db_error = true;
            }

        // Minden más esetben bejelentés folyamatban státuszba kerül
        } else {
            if( addStatusz('bejelentes_statusz', $bejelentes_recid, BEJELENTES_STAT_FOLYAMATBAN, '', $memo) === false ) {
                $db_error = true;
            }
        }

        // Korábbi működés, valszeg törölhető 2018.03.22
//        // Mi a legkisebb státuszú munkalap?
//        $munkalapok_idk = adatbazis2tomb($munkalapok, 'munkalap_id');
//        $lk_munkalap = $adatbazis_kapcsolat->s2('stat_id', 'munkalap', "`recid`!='{$recid}' AND `recid` IN (".implode(', ', $munkalapok_idk).")", 'stat_id', '1');
//
//        // Ha a legkisebb státuszú munkalap ezen státuszok valamelyikében van, akkor a bejelentés folyamatban státuszt kap
//        $folyamatban_statuszok  = array(MUNKALAP_STAT_FELVEVE, MUNKALAP_STAT_ELOKESZITES_ALATT, MUNKALAP_STAT_DONTESRE_VAR, MUNKALAP_STAT_HOZREND_MEGREND_VAR, MUNKALAP_STAT_VARAKOZO);
//        $ig_var_statuszok       = array(MUNKALAP_STAT_IGAZOLASRA_VAR, MUNKALAP_STAT_LEZARASRA_VAR, MUNKALAP_STAT_LEZART);
//        if( in_array($lk_munkalap['stat_id'], $folyamatban_statuszok) ){
//            if( addStatusz('bejelentes_statusz', $bejelentes_recid, BEJELENTES_STAT_FOLYAMATBAN, '', $memo) === false ) {
//                $db_error = true;
//            }
//        // Ha legkisebb státuszú munkalapja igazolásra vár vagy lezárásra vár vagy lezárva státuszban van: bejelentés igazolásra vár státuszba kerül.
//        }elseif( in_array($lk_munkalap['stat_id'], $ig_var_statuszok) ){
//            if( addStatusz('bejelentes_statusz', $bejelentes_recid, BEJELENTES_STAT_KESZ_IG, '', $memo) === false ) {
//                $db_error = true;
//            }
//        // Ha legkisebb státuszú munkalapja sztornózva státuszban van: bejelentés elutasított státuszba kerül.
//        }elseif( $lk_munkalap['stat_id']==MUNKALAP_STAT_SZTORNO ){
//            if( addStatusz('bejelentes_statusz', $bejelentes_recid, BEJELENTES_STAT_ELUTASITVA, '', $memo) === false ) {
//                $db_error = true;
//            }
//        }

        // Indoklás bekerül a bejelentéshez is
        $munkalap = $adatbazis_kapcsolat->s2('ikt_szam', 'munkalap', "`recid`='{$recid}'");
        if( addMegj('bejelentes', $bejelentes_recid, $munkalap['ikt_szam'].' munkalap sztornózva') === false ){
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

    $weblap->leallit();
    exit();
}

// Szállító maicílmének lekérdezése átadás poupban
if (isset($_POST['get_szallito_mailcim'])) {
    
    $szallito = $adatbazis_kapcsolat->s2('email', 'szallito', "`id`='{$_POST['szallito_id']}'");
    echo $szallito['email'];
    
    $weblap->leallit();
    exit();
}

// ML adatainak lekérése levélküldéshez 
if( isset($_POST['get_ml_adatok_levelhez']) ) {

    $munkalap       = $adatbazis_kapcsolat->s2('*', 'munkalap', "`recid`='{$_POST['recid']}'");
    $ml_bej_kapcs   = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$munkalap['recid']}'");
    $bejelentes     = $adatbazis_kapcsolat->s2('*', 'bejelentes', "`recid`='{$ml_bej_kapcs['bejelentes_id']}'");
    $login          = $adatbazis_kapcsolat->s2('teljes_nev usertel usermail', 'login', "`nev`='{$munkalap['loginnev']}'");
    $felhasznalo    = $adatbazis_kapcsolat->s2('nev teljes_nev szemelyzet_id reszleg_id usertel', 'login', "`nev`='{$_SESSION[get_db_tabla().'user']}'");

    // Adatbázisból származó adatok helyreállítása (pl. &amp; => &)
    foreach ( $munkalap as $kulcs => $x ) {
        $munkalap[$kulcs]   = htmlspecialchars_decode($munkalap[$kulcs]);
    }
    foreach ( $bejelentes as $kulcs => $x ) {
        $bejelentes[$kulcs] = htmlspecialchars_decode($bejelentes[$kulcs]);
    }
    foreach ( $login as $kulcs => $x ) {
        $login[$kulcs] = htmlspecialchars_decode($login[$kulcs]);
    }
    foreach ( $felhasznalo as $kulcs => $x ) {
        $felhasznalo[$kulcs] = htmlspecialchars_decode($felhasznalo[$kulcs]);
    }
    
    // További adatok hozzáadása a JSON adathoz
    $munkalap['akt_felh_nev']           = $felhasznalo['teljes_nev'];
    $munkalap['akt_felh_beosztas']      = htmlspecialchars_decode( erteklista(ERTEKLISTA_RESZLEG, $felhasznalo['reszleg_id']) );
    $munkalap['akt_felh_tel']           = $felhasznalo['usertel'];
    // Ha diszpécser a felhasználó szerepköre, akkor fix telefonszámot írunk ki
    if( $felhasznalo['szemelyzet_id'] == DISZPECSER ) {
        $munkalap['akt_felh_tel'] = DISZPECSER_TELEFONSZAM;
    }
    $munkalap['telephely_nev']          = trim(htmlspecialchars_decode( getErtek('nev', 'telephely', 'id', $munkalap['telephely_id']) ));
    $munkalap['hely']                   = trim(htmlspecialchars_decode( getSzallodaNev($munkalap['telephely_id']) )    ."\n".htmlspecialchars_decode( getErtek('nev', 'obj', 'recid', $munkalap['obj_id']) ));
    $munkalap['bejelentes_hiba_text']   = trim(htmlspecialchars_decode( getErtekLista('', '', $bejelentes['hiba_id']) )."\n".$bejelentes['hiba_text']);
    $munkalap['bejelentes_hely']        = trim(htmlspecialchars_decode( getSzallodaNev($bejelentes['telephely_id']) )  ."\n".htmlspecialchars_decode( getErtek('nev', 'obj', 'recid', $bejelentes['obj_id']) )."\n".$bejelentes['helyiseg_text']);
    $munkalap['gep']                    = trim(htmlspecialchars_decode( getErtek('nev', 'eszkozkezeles', 'recid', $munkalap['gep_id']) ) );
    $munkalap['bejelento']              = trim($bejelentes['usr_nev']."\n".htmlspecialchars_decode( erteklista(ERTEKLISTA_BEOSZTAS, $bejelentes['usr_beosztas_id']) )."\n".htmlspecialchars_decode( erteklista(ERTEKLISTA_RESZLEG, $bejelentes['usr_reszleg_id']) )."\n".$bejelentes['usr_telefon']."\n".$bejelentes['usr_email']);
    $munkalap['letrehozta']             = trim($login['teljes_nev']);
    if( $login['usertel'] ) {
        $munkalap['letrehozta'] .= "\n".$login['usertel'];
    }
    if( $login['usermail'] ) {
        $munkalap['letrehozta'] .= "\n".$login['usermail'];
    }
    $munkalap['letrehozta'] = trim(htmlspecialchars_decode($munkalap['letrehozta']));
    
    if( $munkalap['szallito_id'] ) {
        $szallito = $adatbazis_kapcsolat->s2('email', 'szallito', "`id`='{$munkalap['szallito_id']}'");
        $munkalap['mailcimek'] = $szallito['email'];
    }
    
    echo json_encode($munkalap);
    
    $weblap->leallit();
    exit();
}

// Adott ML szállítójának neve
if( isset($_POST['get_szall_nev']) ) {

    $sor = $adatbazis_kapcsolat->s2('szallito_id varh_erk', 'munkalap', "`recid`='{$_POST['recid']}'");
    
    echo json_encode(array(
        'szallito_neve'  => getErtek('nev', 'szallito', 'id', $sor['szallito_id']),
        'varh_erk'       => substr(str_replace('-', '.', $sor['varh_erk']), 0, 16)
    ));
    
    $weblap->leallit();
    exit();
}

// Várható érték megadása
if( isset($_POST['munkalapVarhatoErkMegad']) ) {

    $varh_erk = str_replace('.', '-', $_POST['varh_erk'] ).':00';
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('ikt_szam szallito_id stat_id', 'munkalap', "`recid`='{$_POST['recid']}'");
    if( $sor === false ) { $db_error = true; }
    
    // Külső kivitelező hozzárendeltségének ellenőrzése
    if( !$sor['szallito_id'] ) {
        echo json_encode(array(
            'hiba'  => 'A munkalaphoz nincs hozzárendelve külső kivitelező, így nem adható meg várható érkezés!',
            'title' => 'Hiba',
            'focus' => 'pop_varh_erk'
        ));
        $weblap->leallit();
        exit();
    }
    
    // Státusz ellenőrzése
    $tiltott_stat = array(MUNKALAP_STAT_IGAZOLASRA_VAR, MUNKALAP_STAT_LEZARASRA_VAR, MUNKALAP_STAT_LEZART, MUNKALAP_STAT_SZTORNO_VAR);
    if( in_array($sor['stat_id'], $tiltott_stat) ) {
        echo json_encode(array(
            'hiba'  => 'Ebben a státuszban nem adható meg várható érkezés!',
            'title' => 'Hiba',
            'focus' => 'pop_varh_erk'
        ));
        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if( !_jog(JOG_MUNKALAP_NYITOTT_KEZEL) && !_jog(JOG_MUNKALAP_KEZEL_KULSO) ) {
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga várható érkezést megadni!',
            'title' => 'Hiba',
            'focus' => 'pop_varh_erk'
        ));
        $weblap->leallit();
        exit();
    }
    
    // Múltbeli dátum ellenőrzése
    if( $varh_erk < date('Y-m-d H:i:s') ) {
        echo json_encode(array(
            'hiba'  => 'A várható érkezés nem lehet a jelenleginél korábbi időpont!',
            'title' => 'Hiba',
            'focus' => 'pop_varh_erk'
        ));
        $weblap->leallit();
        exit();
    }
    
    // Várható érkezés beállítása
    $sql = $adatbazis_kapcsolat->u('munkalap', array('varh_erk'), array($varh_erk), "`recid`='{$_POST['recid']}'");
    if( $sql === false ) { $db_error = true; }
    
    // Logolás
    $muvelet = $_SESSION[get_db_tabla().'teljes_nev'].' megadta '.$sor['ikt_szam'].' munkalap várható érkezését ('.$varh_erk.').';
    $sql = $adatbazis_kapcsolat -> i(
                                        'log',
                                        array( 'felh_id',                               'nev',       'valtozas', 'ip'                   ),
                                        array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,    $muvelet,   $_SERVER['REMOTE_ADDR'])
                                    );
    if( $sql === false ) { $db_error = true; }
    
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

// Várható érték megadása
if (isset($_POST['megr_sztorno_mail_elkuldve'])) {
    
    $recid = $_POST['recid'];
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('stat_id vissza_mod vissza_erteklista_id', 'munkalap', "`recid`='{$recid}'");
    if( $sor === false ) { $db_error = true; }
    
    // Státusz ellenőrzése
    if( $sor['stat_id'] != MUNKALAP_STAT_SZTORNO_VAR ){
        echo json_encode(array(
            'hiba'  => 'A munkalaphoz ebben a státuszban nem léptethető tovább!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }
    
    // Jog ellenőrzése
    if( !_jog(JOG_MUNKALAP_KULSOS_MEGREND) ){
        echo json_encode(array(
            'hiba'  => 'Nincs joga ebben a státuszban munkalapot tovább léptetni!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }

    // Szokásos mezők nullázása, de erre két ág van:
    if($sor['vissza_mod']==VISSZA_MOD_VISSZAADAS){
        
        // Mi a ML utolsó nyitott tétele?
        $utolso_tetel = $adatbazis_kapcsolat->s2('recid', 'munkalap_tetel', "`munkalap_id`='{$recid}' AND `atadta_dt`='0000-00-00 00:00:00'", 'recid desc', '1');
        if( $utolso_tetel === false ) { $db_error = true; }
        
        // Frissítjük a munkalap tételt is
        $sql = $adatbazis_kapcsolat -> u('munkalap_tetel',
                                            array('atadta_dt',  'visszaadva', 'erteklista_id',              'update_usr'                           ),
                                            array('NOW()',      1,            $sor['vissza_erteklista_id'], $_SESSION[get_db_tabla().'teljes_nev'] ),
                                            "`recid` = '".$utolso_tetel['recid']."'"
                                        );
        if( $sql === false ) { $db_error = true; }
        
        // ML adatok ürítése, de visszaadva pipa bepipálásával
        $sql = $adatbazis_kapcsolat->u('munkalap',  array('visszaadva', 'szemelyzet_id', 'szallito_id', 'jh_ert',  'becs_ertek',  'mail_ment', 'direktbe', 'vissza_mod', 'vissza_erteklista_id',    'mobil_loginnev', 'mobil',  'varh_erk',            'update_usr'                          ),
                                                    array(1,            0,               '',             0,         0,             0,           0,          0,            0,                         '',               '',       '0000-00-00 00:00:00', $_SESSION[get_db_tabla().'teljes_nev']),
                                                    "`recid`='{$recid}'");
        if( $sql === false ) { $db_error = true; }
        
        // Státusz léptetés
        if( addStatusz('munkalap_statusz', $recid, MUNKALAP_STAT_NYITOTT) === false ) {
            $db_error = true;
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
        
    // Ha Vissza előkészítés gobmról jutottunk ide:
    }elseif($sor['vissza_mod']==VISSZA_MOD_VISSZAELOK){
        
        munkalap_vissza_elokeszitesbe($recid);
        
    // Ilyen nem fordulhat elő, de nem árt bele egy védelem
    } else {
        echo json_encode(array(
            'hiba'  => 'A munkalaphoz nem sikerült nyitott státuszba léptetni, mivel nem ismert a visszaadás módja!',
            'title' => 'Hiba',
        ));
        $weblap->leallit();
        exit();
    }
    
    $weblap->leallit();
    exit();
}

// CSV generálása
if( isset($_GET['csv']) ) {

    // CSV export előkészítése
    list($mibol, $mibe, $filename, $open) = csv_export_elokeszit($megnevezes);
    if( $open ) {

        $lekerdezes_fromtol = csv_export_get_from_tol($megnevezes);
        
        $sql = 'SELECT
                `'.get_db_tabla().$megnevezes.'`.`recid` as \'Id\',
                `'.get_db_tabla().$megnevezes.'`.`telephely_id` as \'Szálloda\',
                `obj_txt` as \'Helyiség\',
                1 as \'Helyiség leírása\',
                `ikt_szam` as \'Munkalap szám\',
                `'.get_db_tabla().$megnevezes.'`.`update_dt` as \'Bejelentett hiba\',
                `'.get_db_tabla().$megnevezes.'`.`insert_dt` as \'Bejelentés dátuma\',
                2 as \'Hiba leírása\',
                `surgos` as \'Sürgős\',
                `stat_id` as \'Státusz\',
                `visszaadva` as \'Visszaadva\',
                `text` as \'Leírás\',
                `gep_id` as \'Gépnév\',
                `tipus_id` as \'Típus\',
                `szallito_id` as \'Külső kivitelező\',
                `jh_ert` as \'Jóváhagyott érték\',
                `szemelyzet_id` as \'Hozzárendelt személyzet\',
                `ig_el_id` as \'Igényelt elvégzés\',
                `varh_erk` as \'Várható érkezés\',
                `'.get_db_tabla().$megnevezes.'`.`insert_dt` as \'Munkalap létrehozás dátuma\'
                '. $lekerdezes_fromtol;
                $query = mysqli_query($db_kapcs, $sql);
        
                // Nagyobb CSV-kre kérdezzünk rá előzetesen
                $osszes_sor = mysqli_num_rows($query);
                if( !$_GET['mehet'] && $osszes_sor>CSV_EXPORT_LIMIT ) {
                    $gomb = '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#navGombok .excel\').attr(\'onclick\', \'csv_general( this, \\\'\\\', \\\'&mehet=1\\\');\'); $(\'.navGombok a.excel\').click();"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">';
                    echo json_encode(array('hiba' => 'A CSV export több mint '.number_format(CSV_EXPORT_LIMIT, 0, '', ' ').' sort tartalmaz. Ennek előállítása hosszabb időbe telik majd.<br>Biztosan folytatja?', 'gomb' => $gomb));
                    $weblap -> leallit();
                    exit;
                } else {
                    // Ha még nincs order beállítva, akkor adjuk csak hozzá a $_SESSION['akt_sql_order']-t
                    if( strpos($lekerdezes_fromtol, 'ORDER')===false ) {
                        $sql .= $_SESSION['akt_sql_order'];
                    }
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

            $bejelentes = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$sorok['Id']}'");
            $bejelentes = $adatbazis_kapcsolat->s2('hiba_id hiba_text helyiseg_text insert_dt', 'bejelentes', "`recid`='{$bejelentes['bejelentes_id']}'");
            foreach( $sorok as $kulcs => $sor ) {

                if( $kulcs == 'Id' ) {
                    continue;
                }
                if( $kulcs == 'Szálloda' ) {
                    $sor = getSzallodaNev($sor);
                }
                if( $kulcs == 'Bejelentett hiba' ) {
                    $sor = getErtek('info', 'erteklista', 'recid', $bejelentes['hiba_id']);
                }
                if( $kulcs == 'Hiba leírása' ) {
                    $sor = $bejelentes['hiba_text'];
                }
                if( $kulcs == 'Helyiség leírása' ) {
                    $sor = $bejelentes['helyiseg_text'];
                }
                if( $kulcs == 'Bejelentés dátuma' ) {
                    $sor = $bejelentes['insert_dt'];
                }
                if( $kulcs == 'Gépnév' ) {
                    $sor = getErtek('nev', 'eszkozkezeles', 'recid', $sor);
                }
                if( $kulcs == 'Hozzárendelt személyzet' ) {
                    $sor = getSzerepkorNev($sor);
                }
                if( $kulcs == 'Igényelt elvégzés' || $kulcs == 'Várható érkezés' ) {
                    if($sor=='0000-00-00 00:00:00'){
                        $sor = '';
                    }
                }
                if( $kulcs == 'Külső kivitelező' ) {
                    $sor = getErtek('nev', 'szallito', 'id', $sor);
                }
                if( $kulcs == 'Jóváhagyott érték' ) {
                    $sorok[$szam] = str_replace('.', ',', $sorok[$szam]);
                }
                if( $kulcs == 'Típus' ) {
                    $sor = erteklista(ERTEKLISTA_MUNKALAP_TIP, $sor);
                }
                if( $kulcs == 'Státusz' ) {
                    $sor = getStatText('munkalap', $sor);
                }
                if( $kulcs == 'Sürgős' ) {
                    if($sor){
                        $sor = 'igen';
                    } else {
                        $sor = 'nem';
                    }
                }
                if( $kulcs == 'Visszaadva' ) {
                    if($sor){
                        $sor = 'igen';
                    } else {
                        $sor = 'nem';
                    }
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

    csv_export_lezar($fh, $filename, 'Munkalap');
}













// Tartalom
if( !$_GET['ajax'] ) {
    require_once('header.php');
}

require('selectek.php');


    if( !$_GET['ajax'] ) {
        echo '<div id="szamlaHidden" class="dn"></div><div id="szamlaBetolt" data-recid="'.$_GET['id'].'" data-page="'.str_replace('.php', '', basename(self())).'">';
    }

    // Az adatlap adatait már a legelején lekérjük
    if( $_GET['id'] ){
        $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");
    }

    if($sor['dok_db']){
        $dok_db  = ' ('.$sor['dok_db'].')';
    }
    if($sor['megj_db']){
        $megj_db = ' ('.$sor['megj_db'].')';
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

    // Hibaüzeneteknek készítünk két hidden inputot, ami midnig elérhető lesz
    $szamla_html .= '<div class="dn" id="ikt_szam_hibauzibe">'.$sor['ikt_szam'].'</div>';
    $szamla_html .= '<div class="dn" id="telephely_hibauzibe">'. getErtek('nev', 'telephely', 'id', $sor['telephely_id']).'</div>';

    // Gyors státusz szűrő szélességének beállítása, ha még nem volt bal navpanel szélesség megmozdítva
    $szamla_html .= '<script>$(document).ready(function () { $(\'#stat_gyors_szuro_div\').css(\'width\', $(\'#tartalomBal\').width()-17 ); $(\'.muveletek\').css(\'width\', $(\'#tartalomJobb\').width()-17); });</script>';

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
                <input type="button" class="gombb'.$class.' mentes_gomb" name="mehet_gomb" id="mehet_gomb"'.$disabled.' value="Mentés" onclick="mentesGomb();">
                <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
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

        // Js, a törlés gombhoz
	    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';

        // Csak az törölhet, akinek van ilyen joga
        if(!_jog(JOG_MUNKALAP_TOROL)){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Nincs jogosultsága munkalap törléséhez!\', \'\', \'Hiba\')" value="Töröl">';
        } else {
            // Egyébként nézzük meg, hogy van-e tétel vagy bejelentés rajta. Ha van, akkor sem törölhető
            if($adatbazis_kapcsolat->s('recid', 'munkalap_tetel', "`munkalap_id`='{$_GET['id']}'")){
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Munkalapszám: '.$sor['ikt_szam'].'<br>Szálloda: '.getErtek('nev', 'telephely', 'id', $sor['telephely_id']).'<br>Ennek a munkalapnak már vannak tételei, ezért nem törölhető!\', \'\', \'Hiba\')" value="Töröl">';
            }elseif($adatbazis_kapcsolat->s('recid ', 'munkalap_kapcs', "`munkalap_id` = '{$_GET['id']}'")){
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Munkalap törlése előtt vegye le róla a bejelentéseket!\', \'\', \'Hiba\')" value="Töröl">';
            } else {
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="csoportMuvelet(\'munkalapTorles\')" value="Töröl">';
            }

            // Munkalap adatlapja csak akkor szerkeszthető, ha a státusz nyitott vagy folyamatban
            if( isset($_GET['modosit']) && in_array($sor['stat_id'], array(MUNKALAP_STAT_NYITOTT, MUNKALAP_STAT_HOZZARENDELT)) ) {
                $gombok = nl2('
                    <div class="form_kuldes">
                        <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
                        <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
                    </div>
                ').$gombok;
            }
        }

        // Munkalap adatlapja csak akkor szerkeszthető, ha a státusz nyitott és van nyitott kezelés jog
        if( isset($_GET['modosit']) && $sor['stat_id']==MUNKALAP_STAT_NYITOTT && _jog(JOG_MUNKALAP_NYITOTT_KEZEL) ) {
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
                </div>
            ').$gombok;
        }

        // Kijelöltekkel való művelet gombok
        if( ($sor['stat_id']==MUNKALAP_STAT_NYITOTT) && _jog(JOG_MUNKALAP_HATIDO_UTEMEZ) ) {
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
        if( ($sor['stat_id']==MUNKALAP_STAT_HATIDO_UTEMEZETT) && _jog(JOG_MUNKALAP_HATIDO_UTEMEZ) ) {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Vissza a nyitottak közé" onclick="munkalapVisszaNyitott(\''.$_GET['id'].'\')"> ';
        }

        // Visszavétel 
        if( $sor['stat_id'] == MUNKALAP_STAT_HOZZARENDELT && _jog(JOG_MUNKALAP_NYITOTT_KEZEL) ) {
            // Ha direktbe volt a munka hozzárendelve, akkor más popup jön fel
            $onclick = 'munkalapVisszavetel(\''.$_GET['id'].'\')';
            if($sor['direktbe']){
                $onclick = 'csoportMuvelet(\'munkalapVisszavetelDirektbe\');';
            }
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Visszavétel" onclick="'.$onclick.'"> ';
        }

        // Munka felvétele folyamatban státuszhoz és kezelés joghoz van kötve
        $felvehetik = array(TELEPITETT_SZEMELYZET, MOBILCSOPORT);
        if( $sor['stat_id'] == MUNKALAP_STAT_HOZZARENDELT && in_array($sor['szemelyzet_id'], $felvehetik) && $kezeles_jog ) {
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
                <input type="button" class="gomb modositaskor_rejt" value="Munka felvétele" onclick="alert2(munkalapFelveszKerdes, munkalapFelvetelGomb);">
            ');
        }

        // Felvéve, kész, vizsgálat státusz + kezelés jog (vizsgálat státusznál vízsgálat jog) bármelyike alatti gombok
        $felvehetik = array(TELEPITETT_SZEMELYZET, MOBILCSOPORT);
        if(
            ( $sor['stat_id']==MUNKALAP_STAT_HOZZARENDELT    && $sor['szemelyzet_id']==KULSO_KIVITELEZO && ( _jog(JOG_MUNKALAP_KEZEL_KULSO) || ( _jog(JOG_MUNKALAP_SAJAT_KEZEL) && $_SESSION[get_db_tabla().'szemelyzet_id']==KULSO_KIVITELEZO ) ) ) ||
            ( $sor['stat_id']==MUNKALAP_STAT_FELVEVE         && in_array($sor['szemelyzet_id'], $felvehetik) && $kezeles_jog )  ||
            ( $sor['stat_id']==MUNKALAP_STAT_VIZSGALAT_ALATT && _jog(JOG_MUNKALAP_VIZSGALAT) ) ||
            ( $sor['stat_id']==MUNKALAP_STAT_LEZARASRA_VAR   && $kezeles_jog )
            ) {
            $gombok .= nl2('<input type="button" class="gomb modositaskor_rejt" value="Elvégzett feladat hozzáadása" onclick="csoportMuvelet(\'feladatHozzaad\')"> ');
        }

        // Felvéve státusz + kezelés jog
        $felvehetik = array(TELEPITETT_SZEMELYZET, MOBILCSOPORT);
        if(
                ( $sor['stat_id']==MUNKALAP_STAT_HOZZARENDELT && $sor['szemelyzet_id']==KULSO_KIVITELEZO && ( _jog(JOG_MUNKALAP_KEZEL_KULSO) || ( _jog(JOG_MUNKALAP_SAJAT_KEZEL) && $_SESSION[get_db_tabla().'szemelyzet_id']==KULSO_KIVITELEZO ) ) ) ||
                ( $sor['stat_id']==MUNKALAP_STAT_FELVEVE && in_array($sor['szemelyzet_id'], $felvehetik) && $kezeles_jog )
           ){
            $gombok .= '<input type="button" class="gomb zold modositaskor_rejt" value="Készre jelentés" onclick="munkalapKeszre(\'csak kérdezünk még\');"> ';
        }

        // Felvéve, kész, vizsgálat státusz + kezelés jog (vizsgálat státusznál vízsgálat jog) bármelyike alatti gombok
        $felvehetik = array(TELEPITETT_SZEMELYZET, MOBILCSOPORT);
        if(
            ( $sor['stat_id']==MUNKALAP_STAT_FELVEVE         && in_array($sor['szemelyzet_id'], $felvehetik) && $kezeles_jog ) ||
            ( $sor['stat_id']==MUNKALAP_STAT_LEZARASRA_VAR   && in_array($sor['szemelyzet_id'], $felvehetik) && $kezeles_jog ) ||
            ( $sor['stat_id']==MUNKALAP_STAT_HOZZARENDELT    && $sor['szemelyzet_id']==KULSO_KIVITELEZO      && _jog(JOG_MUNKALAP_KEZEL_KULSO) ) ||
            ( $sor['stat_id']==MUNKALAP_STAT_VIZSGALAT_ALATT && _jog(JOG_MUNKALAP_VIZSGALAT) )
            ) {
            $gombok .= nl2('<input type="button" class="gomb modositaskor_rejt" value="Anyag felhasználás" onclick="csoportMuvelet(\'munkalapAnyagvetelezes\');"> ');
        }

        // Felvéve státusz + kezelés jog
        if( $sor['stat_id']==MUNKALAP_STAT_FELVEVE && $kezeles_jog ){
            $gombok .= nl2('<input type="button" class="gomb modositaskor_rejt" value="Anyagigénylés szükséges" onclick="csoportMuvelet(\'munkalapAnyagigenyles\');"> ');
        }

        // Felvéve státusz + munkaidő hozzáad jog
        if(
            (($sor['stat_id']==MUNKALAP_STAT_FELVEVE || $sor['stat_id']==MUNKALAP_STAT_LEZARASRA_VAR) && $kezeles_jog ) ||
            ($sor['stat_id']==MUNKALAP_STAT_VIZSGALAT_ALATT && _jog(JOG_MUNKALAP_VIZSGALAT) )
            ){
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="További munkaidő" onclick="csoportMuvelet(\'munkaidoHozzaad\');"> ';
        }

        // Előkészítés státusz és külsős előkészítés jog kell hozzá
        if(
             ( $sor['stat_id']==MUNKALAP_STAT_ELOKESZITES_ALATT && _jog(JOG_MUNKALAP_KULSOS_ELOKESZIT) && $_SESSION[get_db_tabla().'szemelyzet_id']==$sor['szemelyzet_id'] ) ||
             ( $sor['stat_id']==MUNKALAP_STAT_HOZZARENDELT && $sor['szemelyzet_id']==KULSO_KIVITELEZO && ( _jog(JOG_MUNKALAP_KEZEL_KULSO) || ( _jog(JOG_MUNKALAP_SAJAT_KEZEL) && $_SESSION[get_db_tabla().'szemelyzet_id']==KULSO_KIVITELEZO ) )  )
            ){
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Javaslattétel" onclick="csoportMuvelet(\'kulsokivHozzarend\');"> ';
        }

        // Külső kivitelező hozzáadása
        if( $sor['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT && $_SESSION[get_db_tabla() . 'szemelyzet_id'] == MUSZAKI_VEZETO && $sor['szemelyzet_id'] == MUSZAKI_VEZETO && !$telephely['modell'] /* Egyszállodás */ ) {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Külső kivitelező hozzárendelése" onclick="csoportMuvelet(\'kulsokivHozzarend2\');"> ';
        }

        // Nyitott státusz + Nyitottak kezel jog
        if($sor['stat_id']==MUNKALAP_STAT_NYITOTT && _jog(JOG_MUNKALAP_NYITOTT_KEZEL)){

            $szemelyzet_tipusok = '';
            $select_szemelyzet_sorrend = tobbdim_tomb_kulcs_rendez($select_szemelyzet, 'hozzarendel_sor');
            foreach($select_szemelyzet_sorrend as $value) {
                if($value['kulsos']){
                    $szemelyzet_tipusok .= '<li onclick="munkalapElokeszit('.$value['value'].')">'.$value['name'].'</li>';
                }
            }

            $gombok .= nl2('
                <div class="kijeloltek_muv">
                    <input type="button" class="gomb foGomb modositaskor_rejt" value="Hozzárendelés...">
                    <div class="muv">
                        <ul>
                            <li'.$szemelyzet_id_rejt[KULSO_KIVITELEZO].'>
                                <a class="tovabbi_menu">Előkészítés</a>
                                <ul>
                                    '.$szemelyzet_tipusok.'
                                </ul>
                            </li>
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
        if( $sor['stat_id'] == MUNKALAP_STAT_FELVEVE && !$telephely['modell'] /* Egyszállodás modell */ && $kezeles_jog ) {

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
                            <li'.$szemelyzet_id_rejt[DISZPECSER].'>
                                <a onclick="csoportMuvelet(\'atadasDiszpecsernek\');">Diszpécsernek</a>
                            </li>
                        </ul>
                    </div>
                </div>
            ');

        }

        // Folyamatban státusz
        if( $sor['stat_id']==MUNKALAP_STAT_HOZZARENDELT && !$telephely['modell']/*Egyszálodás modell*/ && $kezeles_jog && $_SESSION[get_db_tabla().'szemelyzet_id']!=MUSZAKI_VEZETO /* Ha műszaki vezető vagyok, akkor az egyetlen opció úgy is el volna rejtve */ ) {

            $gombok .= nl2('
                <div class="kijeloltek_muv">
                    <input type="button" class="gomb foGomb modositaskor_rejt" value="Átadás...">
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
        if( $sor['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT && _jog(JOG_MUNKALAP_KULSOS_ELOKESZIT) && $sor['szemelyzet_id'] == $_SESSION[get_db_tabla() . 'szemelyzet_id'] && in_array($_SESSION[get_db_tabla() . 'szemelyzet_id'], $engedelyezett_szemelyzettipusok) ) {

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
                    <input type="button" class="gomb foGomb modositaskor_rejt" value="Átadás...">
                    <div class="muv">
                        <ul>
                            <li'.$szemelyzet_id_rejt[TELEPITETT_SZEMELYZET].'><a onclick="munkalapTelepitett(\'csak kérdezünk még\');">Telepítettnek</a></li>
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
                                <a onclick="csoportMuvelet(\'munkalapMSZKVezeto\')">MSZK vezetőnek</a>
                            </li>
                        </ul>
                    </div>
                </div>
            ');

        }

        // Visszaadás Diszpécsernek
        $felvehetik = array(TELEPITETT_SZEMELYZET, MOBILCSOPORT);
        if (
            ( $telephely['modell'] && $sor['stat_id']==MUNKALAP_STAT_FELVEVE            && in_array($sor['szemelyzet_id'], $felvehetik) && $kezeles_jog ) ||
            ( /* Modell nem számít */ $sor['stat_id']==MUNKALAP_STAT_HOZZARENDELT       && $sor['szemelyzet_id']==KULSO_KIVITELEZO && ( _jog(JOG_MUNKALAP_KEZEL_KULSO) || ( _jog(JOG_MUNKALAP_SAJAT_KEZEL) && $_SESSION[get_db_tabla().'szemelyzet_id']==KULSO_KIVITELEZO ) ) ) ||
            ( /* Modell nem számít */ $sor['stat_id']==MUNKALAP_STAT_ANYAGRAVAR         && _jog(JOG_MUNKALAP_ANYAG_ERKEZTET)    ) ||
            ( /* Modell nem számít */ $sor['stat_id']==MUNKALAP_STAT_ELOKESZITES_ALATT  && $_SESSION[get_db_tabla().'szemelyzet_id']==$sor['szemelyzet_id'] && _jog( JOG_MUNKALAP_KULSOS_ELOKESZIT )  ) ||
            ( /* Modell nem számít */ $sor['stat_id']==MUNKALAP_STAT_VIZSGALAT_ALATT    && _jog(JOG_MUNKALAP_VIZSGALAT)         )
           ) {
                // Hozzárendelt státusz esetén, ha a ML direktbe volt hozzárendelve, más popup jön fel
                $onclick = 'csoportMuvelet(\'atadasDiszpecsernek\');';
                if( $sor['stat_id']==MUNKALAP_STAT_HOZZARENDELT && $sor['direktbe'] ){
                    $onclick = 'csoportMuvelet(\'atadasDiszpecsernekDirektbe\');';
                }
                $gombok .= '<input type="button" class="gomb piros modositaskor_rejt" value="Visszaadás" onclick="'.$onclick.'"> ';
            }

        // "Döntés alatt" státuszkor, ha még egy jóváhagyás sem történt és van jogunk, akkor a jóváhagyott értéket szerkeszteni lehet
        if( $sor['stat_id'] == MUNKALAP_STAT_DONTESRE_VAR && _jog(JOG_MUNKALAP_KULSOS_ELOKESZIT) && $sor['jh1_login'] == '' && $sor['jh2_login'] == '' ) {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Jóváhagyott érték módosítása" onclick="csoportMuvelet(\'munkalapJovahagyottErtekModositasa\')"> ';
        }
        
        // Vissza előkészítésre
        if (
                ($sor['stat_id']==MUNKALAP_STAT_DONTESRE_VAR && _jog(JOG_MUNKALAP_KULSOS_ELOKESZIT) && $sor['jh1_login']=='' && $sor['jh2_login']=='') ||
                ($sor['stat_id']==MUNKALAP_STAT_HOZZARENDELT && _jog(JOG_MUNKALAP_KEZEL_KULSO)      && $sor['szemelyzet_id']==KULSO_KIVITELEZO )
           ) {
            
            // Ha hozzárendelt státusz alatban van egy direktes ML, akkor levelezős popup jön fel.
            $onclick = 'alert2(\'Biztosan visszaadja előkészítésre?\', gomb_vissza_elok);';
            if( $sor['stat_id'] == MUNKALAP_STAT_HOZZARENDELT && $sor['direktbe'] ) {
                $onclick = 'csoportMuvelet(\'munkalapVisszaElokeszitDirektbe\');';
            }
            $gombok .= nl2('
                            <script> gomb_vissza_elok = \'<input type="button" class="gomb zold" onclick="munkalapVisszaElokeszit()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\'; </script>
                            <input type="button" class="gomb modositaskor_rejt" value="Vissza előkészítésre" onclick="'.$onclick.'">
                        ');
        }

        // "Döntés alatt" státusz + döntés jog + egyik jóváhagyó szerepkörű se legyek
        if( $sor['stat_id'] == MUNKALAP_STAT_DONTESRE_VAR && _jog(JOG_MUNKALAP_KULSOS_DONTES) && $sor['jh1_szerep'] != $_SESSION[get_db_tabla() . 'szemelyzet_id'] && $sor['jh2_szerep'] != $_SESSION[get_db_tabla() . 'szemelyzet_id'] ) {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Külsős jóváhagyása" onclick="munkalapKulsosJovahagy(\'csak kérdezünk még\');"> ';
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Külsős elutasítása" onclick="csoportMuvelet(\'munkalapKulsosElutasit\')"> ';
        }

        // Anyagra vár és anyag érkeztetése jog
        if( $sor['stat_id'] == MUNKALAP_STAT_ANYAGRAVAR && _jog(JOG_MUNKALAP_ANYAG_ERKEZTET) ) {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Anyag rendelkezésre áll" onclick="csoportMuvelet(\'munkalapErkeztet\')"> ';
        }

        // Igazolás alatt státuszban látható. Munkalap vizsgálat alatt státuszba kerül.
        if( $sor['stat_id'] == MUNKALAP_STAT_IGAZOLASRA_VAR && _jog(JOG_MUNKALAP_IGAZOLAS) ) {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Igazolás" onclick="munkalapIgazol(\'csak kérdezünk még\');"> ';
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Igazolás elutasítása" onclick="csoportMuvelet(\'munkalapIgenylesElutasit\');"> ';
        }


        // Várakoztatás: Előkészítés alatt státusz + várakoztatás jog
        if( $sor['stat_id'] == MUNKALAP_STAT_ELOKESZITES_ALATT && _jog(JOG_MUNKALAP_VARAKOZTAT) && $_SESSION[get_db_tabla() . 'szemelyzet_id'] == $sor['szemelyzet_id'] ) {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Várakoztatás" onclick="csoportMuvelet(\'munkalapVarakoztat\');"> ';
        }

        // Várakoztatás vége: Várakozó státusz + várakoztatás jog
        if( $sor['stat_id'] == MUNKALAP_STAT_VARAKOZO && _jog(JOG_MUNKALAP_VARAKOZTAT) && $_SESSION[get_db_tabla() . 'szemelyzet_id'] == $sor['szemelyzet_id'] ) {
            $gombok .= nl2('
                        <script> gomb_varakoztat_vege = \'<input type="button" class="gomb zold" onclick="munkalapVarakoztatVege()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\'; </script>
                        <input type="button" class="gomb modositaskor_rejt" value="Várakoztatás vége" onclick="alert2(\'Biztosan visszateszi a munkalapot előkészítés alatt státuszba?\', gomb_varakoztat_vege);">
                        ');
        }

        // Lezárás: Vizsgálat alatt + Vizsgálat jog
        if( $sor['stat_id'] == MUNKALAP_STAT_VIZSGALAT_ALATT && _jog(JOG_MUNKALAP_VIZSGALAT) ) {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Lezárás" onclick="csoportMuvelet(\'munkalapReklamacioElutasit\');"> ';
        }

        // Lezárás2: Kész státusz + kezelés jog
        if( $sor['stat_id'] == MUNKALAP_STAT_LEZARASRA_VAR && $kezeles_jog ) {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Lezárás" onclick="munkalapRogzit(\'csak kérdezünk még\');"> ';
        }

        // Sztornó: Nyitott státusz + nyitottak kezelése jog
        if( $sor['stat_id'] == MUNKALAP_STAT_NYITOTT && _jog(JOG_MUNKALAP_NYITOTT_KEZEL) ) {
            $onclick = 'csoportMuvelet(\'munkalapSztornoz\');';
            $van_felh_anyag = $adatbazis_kapcsolat->s('recid', 'munkalap_anyag', "`munkalap_id`='{$_GET['id']}'", '', '1');
            if($van_felh_anyag){
                $onclick = 'alert2(\''.$sor['ikt_szam'].'<br>Ehhez a munkalaphoz rögzítettek anyagfelhasználást.<br>Nem lehet sztornózni!\', \'\', \'Hiba\');';
            }
            $gombok .= '<input type="button" class="gomb piros" value="Sztornó" onclick="'.$onclick.'"> ';
        }

        // Megrendelés alatt
        if( $sor['stat_id'] == MUNKALAP_STAT_HOZREND_MEGREND_VAR && _jog(JOG_MUNKALAP_KULSOS_MEGREND) ) {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Árajánlat nyomtatva" onclick="munkalapArajanlatNyomtatva(\'csak kérdezünk még\');"> ';
        }

        // Ajánlat nyomtatva alatt
        if( $sor['stat_id'] == MUNKALAP_STAT_AJANLAT_NYOMTATVA && _jog(JOG_MUNKALAP_KULSOS_MEGREND) ) {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Külsős munka megrendelve" onclick="munkalapKulsosMegrendelve(\'csak kérdezünk még\');"> ';
        }

        // Várható érk. megadása
        $tiltott_stat = array(MUNKALAP_STAT_IGAZOLASRA_VAR, MUNKALAP_STAT_LEZARASRA_VAR, MUNKALAP_STAT_LEZART, MUNKALAP_STAT_SZTORNO_VAR);
        if( $sor['szallito_id'] && !in_array($sor['stat_id'], $tiltott_stat) && (_jog(JOG_MUNKALAP_NYITOTT_KEZEL) || _jog(JOG_MUNKALAP_KEZEL_KULSO) ) ) {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Várható érk. megadása" onclick="csoportMuvelet(\'munkalapVarhatoErkMegad\');"> ';
        }

        // Megrendelés sztornó email elküldve
        if( $sor['stat_id'] == MUNKALAP_STAT_SZTORNO_VAR && _jog(JOG_MUNKALAP_KULSOS_MEGREND) ) {
            $gombok .= nl2('
                            <script> gomb_megr_sztorno_elkuldve = \'<input type="button" class="gomb zold" onclick="megr_sztorno_mail_elkuldve()" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\'; </script>
                            <input type="button" class="gomb modositaskor_rejt" value="Megrendelés sztornó email elküldve" onclick="alert2(\'Biztosan elküldte a sztornó emailt?\', gomb_megr_sztorno_elkuldve);"> 
                        ');
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
        
        $js_submit_elott = nl2('
                var dfrd_ml_mehet = false;
                var hibauzenet    = \'\';
                dfrd_pr_munkalap = $.Deferred();
                $.ajax({
                    url     : \'munkalap.php\',
                    type    : \'POST\',
                    async   : false,
                    data    : {
                        tipus_id        : $(\'#tipus_id\').val(),
                        telephely_id    : $(\'#telephely_id\').val(),
                        pr_szam         : $(\'#pr_szam\').val(),
                        telephely_ell   : Date.now()
                    },
                    success: function(response) {
                        if(response.substring(0, 6)==\'SESKIL\'){ location.href = \'index.php\'; }
                        if(response){
                            response = JSON.parse(response);
                            if( response.hiba ) {
                                hibauzenet  = response.hiba;
                                gomb        = response.gomb;
                                title       = response.title;
                                focus       = response.focus;
                            } else {
                                dfrd_ml_mehet = true;
                            }
                            dfrd_pr_munkalap.resolve();
                        }
                        return false;
                    },
                    complete: function(xhr) {
                        if( xhr.status != 200 ) {
                            alert2(xhr.responseText, \'\', \'Hiba\');
                        }
                    }
                });
                
                urlap_mehet = false;
                $.when(dfrd_pr_munkalap).then(function() {
                    if( !dfrd_ml_mehet ){
                        alert2(hibauzenet, gomb, title);
                        $(\'#\'+focus).addClass(\'hibas_ide_focus\');
                        szurkit_zar(\'mehet_megsem_mutat\');
                        return false;
                    } else {
                        urlap_mehet = true;
                    }
                    
                    /* Ez a feltétel nem tévedésből van kétszer, kell ide is, meg egyel lentebb is */
                    if(!urlap_mehet){
                        return false;
                    } else {
                        return form_ell_admin();
                    }
                });
                
                
                /* Ez a feltétel nem tévedésből van kétszer, kell ide is, meg egyel fentebb is */
                if(!urlap_mehet){
                    return false;
                } else {
                    return form_ell_admin();
                }
            ');

        // NAVIGÁCIÓ
        $navi_sablon  = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit     = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel   = array( $navi,             '',         $gyk2 );
        $szamla_html .= str_replace($navi_mit, $navi_mivel, $navi_sablon);

	    // FORM
		if($_GET['ujkent']) {
            $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['ujkent']}'");
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

	    $js = js_kiir($akt_tomb, 'form_ell_admin()');

	    $form_sablon = fajlbol_olvas('../sablon/admin/form_uj.html', true);

        // Javascript-es ellenőrzéshez
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

        $json_sor = array();
        foreach($sor as $kulcs => $elem){
            $json_sor[$kulcs] = formAdatok_replace($elem);
        }
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

		// Ez elkészíti a $mezok változót
		require('switch.php');

        $mezok .= '<input type="hidden" id="ujkentSzerkID" name="ujkentSzerkID" value="'.$_GET['ujkent'].'">';
        if (isset($_GET['ujkent_bejelentes'])) {
            $mezok .= '<input type="hidden" id="ujkentSzerkBejelentesbol" name="ujkentSzerkBejelentesbol" value="'.$_GET['ujkent_bejelentes'].'">';
        }

        // Ha van előkészítés joga
        if( _jog(JOG_MUNKALAP_KULSOS_ELOKESZIT) ) {

            // És előkészíésre szeretné létrehozni, akkor beállítjuk a hozzárenelt személyzetet saját magára, meg egy hidden inputként is hozzáfűzzük
            if( isset($_GET['elokeszitesre']) ) {
                $mezok .= nl2('
                            <script>$(\'#szemelyzet_id\').val(\''.$_SESSION[get_db_tabla().'szemelyzet_id'].'\')</script>
                            <input type="hidden" name="szemelyzet_id" value="'.$_SESSION[get_db_tabla().'szemelyzet_id'].'">
                            <input type="hidden" name="statuszba_tovabb_leptet" value="'.MUNKALAP_STAT_ELOKESZITES_ALATT.'">
                            ');
            }

            // Ha telepítettnek szeretnék létrehozni a munkalapot, akkor ugyanezt eljátszuk, mint az előbb, csak más személyzettípussal
            if( isset($_GET['telepitettnek']) ) {
                $mezok .= nl2('
                            <script>$(\'#szemelyzet_id\').val(\''.TELEPITETT_SZEMELYZET.'\')</script>
                            <input type="hidden" name="szemelyzet_id" value="'.TELEPITETT_SZEMELYZET.'">
                            <input type="hidden" name="statuszba_tovabb_leptet" value="'.MUNKALAP_STAT_HOZZARENDELT.'">
                            ');
            }

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
    } elseif( isset($_GET['modosit']) ) {

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if( isset($_GET['id_hidden']) ) {
            $_GET['id'] = $_GET['id_hidden'];
        }

        // Speciális karakterek szűrése
        $mit   = specialis_karakterek_mit();
        $mivel = specialis_karakterek_mivel();
        $sor['obj_txt'] = str_replace($mit, $mivel, $sor['obj_txt']);
        $sor['text']    = str_replace($mit, $mivel, $sor['text']);

        // Jóváhagyott érték legyen formázva, úgy is csak ki van írva
        $sor['jh_ert'] = number_format($sor['jh_ert'], 0, '', ' ');

        foreach ( $akt_tomb as $tomb ) {
            if( $tomb['type'] == 'autocomplete' ) {

                if($tomb['csoport']){
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
        foreach($sor as $kulcs => $elem){
            $json_sor[$kulcs] = formAdatok_replace($elem);
        }

        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

        getSzallodaNev($sor['telephely_id']);

        $munkalap_kapcs = $adatbazis_kapcsolat->s('recid', 'munkalap_kapcs', "`munkalap_id` = '".$sor['recid']."'", '', '');

        // Duplikáláskor feltett kérdés
        if(!_jog(JOG_MUNKALAP_KESZITHET)){
            $szamla_html .= nl2('
                <script>
                    duplikalGomb  = "<p class=\"c\"><input class=\"gomb zold\" type=\"button\" value=\"Ok\" onclick=\"alert2()\"></p>";
                    duplikaKerdes = \'Önnek nincs joga új munkalapot létrehozni!\';
                </script>
            ');
        } else {
            $szamla_html .= nl2('
                <script>
                    duplikalGomb  = "<p class=\"c\"><input class=\"gomb zold\" type=\"button\" value=\"Igen\" onclick=\"szurkit(); location.href = \''.basename(self()).'?uj&ujkent='.$_GET['id'].'\'; \"><input class=\"gomb piros\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
                    duplikaKerdes = \'Iktatószám: '.$sor['ikt_szam'].'<br>Szálloda: '.$telephely['nev'].'<br><br>Ez a munkalap '.count($munkalap_kapcs).' db bejelentésből készült. <br>Ezek a bejelentések az új munkalaphoz is hozzá lesznek rendelve. <br>Biztos, hogy duplikálja?\';
                </script>
            ');
        }

	    // FORM
	    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

		// Form actionja
		$action = self().get_get(array('errnum','kephiba','siker'));

        if(isset($_GET['ujkent'])){
			foreach($akt_tomb as $ertek){
				$sor[$ertek['name']] = $_SESSION[$ertek['name']];
			}
		}

		// Ez elkészíti a $mezok változót
		require('switch.php');
        $mezok .= '<input type="hidden" id="ujkentSzerkID" name="ujkentSzerkID" value="'.$_GET['ujkent'].'">';

	    $szamla_html .= str_replace(
            array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}', '{UPDATE_DT}'     ),
            array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok,    $sor['update_dt'] ),
            fajlbol_olvas('../sablon/admin/form_modosit.html', true)
        );

        // Bejelentés táblázata
        $munkalap_kapcsok = $adatbazis_kapcsolat->s('recid bejelentes_id', 'munkalap_kapcs', "`munkalap_id` = '{$_GET['id']}'");
        if( $munkalap_kapcsok ) {
            $szamla_html .= nl2('
                <div class="dt naplo kijCont" data-tabla="munkalap_kapcs" data-mellek="bejelentesek" style="margin-top: 3rem">
                    <div class="dtr nFejlec">
                        <div class="dtc">Hiba</div>
                        <div class="dtc">Hely</div>
                        <div class="dtc">Bejelentés</div>
                        <div class="dtc">Bejelentő adatai</div>
                        <div class="dtc"></div>
                    </div>
            ');
            $i = 1;

            foreach($munkalap_kapcsok as $munkalap_kapcs) {

                $sor = $adatbazis_kapcsolat->s2('recid ikt_szam telephely_id obj_id hiba_id insert_dt stat_id hiba_text usr_nev usr_telefon usr_email usr_beosztas_id usr_reszleg_id helyiseg_text', 'bejelentes', "`recid` = '".$munkalap_kapcs['bejelentes_id']."'", '', '1');
                if( $sor ) {

                    // Telephely
                    $telephely = getSzallodaNev($sor['telephely_id']);

                    // Helység
                    $helyseg = $adatbazis_kapcsolat->s('nev', 'obj', 'recid='.$sor['obj_id'], '', '1');
                    $helyseg = $helyseg[0]['nev'];

                    // Hiba
                    $hiba = getErtekLista('', '', $sor['hiba_id']);

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
                            <div class="dtc vat" id="ml_bejelentese_hiba">'.$hiba.'<br>'.nl2br2($sor['hiba_text']).'</div>
                            <div class="dtc vat" id="ml_bejelentese_hely">
                                '.$telephely.'
                                <br>'.$helyseg.'
                                <br>'.nl2br2($sor['helyiseg_text']).'
                            </div>
                            <div class="dtc vat">
                                <a href="bejelentes.php?id=x&amp;mezo=ikt_szam&amp;op=equal&amp;felt='.$sor['ikt_szam'].'" target="_blank">'.$sor['ikt_szam'].'</a>
                                <br>'.datum_kiir($sor['insert_dt']).'
                                <br>'.getStatText('bejelentes', $sor['stat_id']).'
                            </div>
                            <div class="dtc vat" id="ml_bejelentese_bejelento">
                                '.$sor['usr_nev'].'
                                <br>'.erteklista(ERTEKLISTA_BEOSZTAS, $sor['usr_beosztas_id']).'
                                <br>'.erteklista(ERTEKLISTA_RESZLEG, $sor['usr_reszleg_id']).'
                                <br>'.$sor['usr_telefon'].'
                                <br>'.$sor['usr_email'].'
                            </div>
                            <div class="dtc link vat">
                                '.$torles.'
                            </div>
                        </div>
                    ');
                }

                $i++;
            }
        }

        // Becsült érték pipát megjelenítjük, ha be van pipálva
        if($sor['becs_ertek']){
            $szamla_html .= nl2('
                <script>
                $(document).ready(function () {
                    $(\'.f_becsult_ertek\').removeClass(\'dn\');
                });
                </script>
                ');
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
            $tetel_nyitott = true;
            if( $sor['atadta_dt'] != '0000-00-00 00:00:00' ) {
                $atadta = date('Y.m.d H:i', strtotime($sor['atadta_dt']));
                $tetel_nyitott = false;
            }

            $atadas_oka = erteklista(ERTEKLISTA_ATADAS_OKA, $sor['erteklista_id']);
            if( $atadas_oka ) {
                $atadas_oka = '<br>'.$atadas_oka;
            }

            // Szerepkör
            $tetel_felh = $adatbazis_kapcsolat->s2('szemelyzet_id', 'login', "`nev` = '".$sor['loginnev']."'");
            $szerepkor = '';
            if( $tetel_felh ) {
                
                $szerepkor_sor = getSzerepkorNev($tetel_felh['szemelyzet_id']);
                if( $szerepkor_sor ) {
                    $szerepkor = '<br>('.$szerepkor_sor.')';
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
                                ( $munkalap['stat_id']==MUNKALAP_STAT_HOZZARENDELT    && $munkalap['szemelyzet_id']==KULSO_KIVITELEZO && ( _jog(JOG_MUNKALAP_KEZEL_KULSO) || ( _jog(JOG_MUNKALAP_SAJAT_KEZEL) && $_SESSION[get_db_tabla().'szemelyzet_id']==KULSO_KIVITELEZO ) ) && $tetel_nyitott ) ||
                                ( $munkalap['stat_id']==MUNKALAP_STAT_FELVEVE         && in_array($munkalap['szemelyzet_id'], $felvehetik) && $kezeles_jog && $tetel_nyitott )  ||
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

                foreach ( $anyagok as $anyag ) {

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

                foreach ( $munkaidok as $munkaido ) {
                    if( $munkaido['perc'] < 10 ) {
                        $munkaido['perc'] = '0'.$munkaido['perc'];
                    }

                    // Törlés csak az első tételnél lehetséges
                    $torles = '';
                    if (
                            $i==0 &&
                            (
                                (($munkalap['stat_id']==MUNKALAP_STAT_FELVEVE || $munkalap['stat_id']==MUNKALAP_STAT_LEZARASRA_VAR) && $kezeles_jog ) ||
                                ($munkalap['stat_id']==MUNKALAP_STAT_VIZSGALAT_ALATT && _jog(JOG_MUNKALAP_VIZSGALAT) )
                            )
                        ) {
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
    } elseif( isset($_GET['tortenet']) ) {

        $szamla_html .= get_tortenet($megnevezes);

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Anyagigénylés fül
    } elseif( isset($_GET['anyagigenyles']) ) {

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
            foreach ( $sorok as $sor ) {
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
    } elseif( isset($_GET['elszamolas']) ) {

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
    } elseif( isset($_GET['dokumentumok']) ) {

        if( $tobb_kep ) {
            require_once('tobb_kep.php');
        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom'  => $szamla_html.$gombok,
                'link'      => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Megjegyzések fül
    } elseif( isset($_GET['megjegyzesek']) ) {

        // <div class="dt naplo" class nevekből a kijCont kivéve, hogy a megjegyzéseket ne lehessen törölni
        $szamla_html .= nl2('
            <div class="dt naplo" data-tabla="megjegyzes" data-mellek="megjegyzesek">
                <div class="dtr nFejlec">
                    <div class="dtc">Megjegyzés</div>
                    <div class="dtc">Hozzáadta</div>
                    <div class="dtc">Hozzáadva</div>
                </div>
        ');

        $sorok = $adatbazis_kapcsolat->s('recid szerepkor megj insert_usr insert_dt', 'megjegyzes', "`id` = '".$_GET['id']."' AND `tabla` = '".str_replace('.php', '', basename(self()))."'", 'recid desc', '');
        if( $sorok ) {
            $i = 1;
            foreach ( $sorok as $sor ) {
                $szamla_html .= nl2('
                    <div id="naploSor-'.$sor['recid'].'" class="dtr nSor kijSor" data-id="'.$sor['recid'].'" data-i="'.$i.'">
                        <div class="dtc">'.htmlspecialchars_decode( nl2br2($sor['megj']) ).'</div>
                        <div class="dtc">'.$sor['insert_usr'].' '.getSzerepkorNev($sor['szerepkor']).'</div>
                        <div class="dtc">'.datum_kiir($sor['insert_dt']).'</div>
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
