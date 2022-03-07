<?php
require_once('../weblap.php');

if( !_jog(JOG_FELHASZNALOK_MEGTEKINT) ) {
    if( !$_SESSION[get_db_tabla() . 'user'] ) {
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
$akt_tomb               = $login_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'felhasználót';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

$szamla_html = '';

// Dokumentumok fülhöz kell majd
$tobb_kep = false;
if($tobb_kep){
    function get_tobbkep_darab()    { return 10;            } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '.jpg,.png';   } // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;            } // MB-ban
    function get_tobbkep_oszlopnev(){                         // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

}

// Jogosultságok beállítsa
if( isset($_POST['jogokhozzaad']) ) {
    
    // Ha a usernek nincs joga felhasználót kezelni, akkor ne tudjon jogokat változtatni
    if( !_jog(JOG_FELHASZNALOK_KEZEL) ) {
        exit;
    }
    
    $id = $_POST['id'];
    $_POST['jh_ft_ig'] = str_replace(' ', '', $_POST['jh_ft_ig']);

    // Kitöröljük a nem kívánatos POST elemeket
    unset($_POST['id']);
    unset($_POST['jogokhozzaad']);

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    // Tömb sorrendezése
    $jogok      = array();
    foreach($_POST as $key => $value) {

        if( strpos($key, 'jog-')!==false ){
            $kulcs = str_replace('jog-', '', $key);
            if($value=='true'){
                $jogok[$kulcs] = 1;
            } else {
                $jogok[$kulcs] = 0;
            }
        }
    }

    ksort($jogok);

    // Log tartalmának beállítása
    $flags = $adatbazis_kapcsolat->s2('flags', 'login', "`recid`='{$id}'");
    if( $flags === false ) { $db_error = true; }
    $log = 'Előző jogok: '.$flags['flags'].'. Új jogok: '.implode('', $jogok).' Külsős jóváhagyás: '.number_format($_POST['jh_ft_ig'], 0, '', ' ').' '. get_penznem().'-ig';

    // Új jogok letárolása
    $sql = $adatbazis_kapcsolat -> u('login',   array('flags',              'jh_ft_ig',         'update_usr'                            ),
                                                array(implode('', $jogok),  $_POST['jh_ft_ig'], $_SESSION[get_db_tabla().'teljes_nev']  ),
                                                "`recid`='{$id}'");
    if( $sql === false ) { $db_error = true; }

    // Logba is eltároljuk
    $sql = $adatbazis_kapcsolat -> i('log',
                                array( 'felh_id',                               'nev',                                                                                    'valtozas',    'ip'                    ),
                                array( $_SESSION[get_db_tabla().'login_recid'], $_SESSION[get_db_tabla().'teljes_nev'].' módosította '.$id.' felhasználó jogosultságait', $log,          $_SERVER['REMOTE_ADDR'] )
                            );
    if( $sql === false ) { $db_error = true; }

    // A formAdatok JS változoiót frissíteni kell
    require_once('jogosultsag.php');
    $i      = 0;
    $jogEll = array();
    foreach ( $jogosultsagok as $jog ) {
        if( $jogok[$i] ) {
            $jogEll['jog-'.$i] = 1;
        } else {
            $jogEll['jog-'.$i] = 0;
        }

        $i++;
    }
    $jogEll['jh_ft_ig'] = $_POST['jh_ft_ig'];


    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode($jogEll);
    } else {
        mysqli_rollback($db_kapcs);
    }


    $weblap->leallit();
    exit();
}

// Értesítések beállítsa
if( isset($_POST['ertekhozzaad']) ) {
    
    // Ha a usernek nincs joga felhasználót kezelni, akkor ne tudjon értesítéseket változtatni
    if( !_jog(JOG_FELHASZNALOK_KEZEL) ){
        exit;
    }
    
    $id = $_POST['id'];

    // Kitöröljük a nem kívánatos POST elemeket
    unset($_POST['id']);
    unset($_POST['ertekhozzaad']);

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    // Tömb sorrendezése
    $ertek      = array();
    foreach ( $_POST as $key => $value ) {

        if( strpos($key, 'ert-')!==false ){
            $kulcs = str_replace('ert-', '', $key);
            if( $value == 'true' ) {
                $ertek[$kulcs] = 1;
            } else {
                $ertek[$kulcs] = 0;
            }
        }
    }

    ksort($ertek);


    // Log tartalmának beállítása
    $flags = $adatbazis_kapcsolat->s2('flags_ert', 'login', "`recid`='{$id}'");
    if( $flags === false ) { $db_error = true; }
    $log = 'Előző értesítések: '.$flags['flags_ert'].'. Új értesítések: '.implode('', $ertek);

    // Új értesítések letárolása
    $sql = $adatbazis_kapcsolat -> u('login',  array('flags_ert'), array(implode('', $ertek)), "`recid`='{$id}'");
    if( $sql === false ) { $db_error = true; }

    // Logba is eltároljuk
    $sql = $adatbazis_kapcsolat -> i('log',
                                array( 'felh_id',                               'nev',                                                                                  'valtozas',    'ip'                    ),
                                array( $_SESSION[get_db_tabla().'login_recid'], $_SESSION[get_db_tabla().'teljes_nev'].' módosította '.$id.' felhasználó értesítéseit', $log,          $_SERVER['REMOTE_ADDR'] )
                            );
    if( $sql === false ) { $db_error = true; }

    // A formAdatok JS változoiót frissíteni kell
    require_once('ertesitesek.php');
    $i      = 0;
    $ertEll = array();
    foreach ( $ertesitesek as $ert ) {
        if( $ertek[$i] ) {
            $ertEll['ert-'.$i] = 1;
        } else {
            $ertEll['ert-'.$i] = 0;
        }

        $i++;
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode($ertEll);
    } else {
        mysqli_rollback($db_kapcs);
    }

    $weblap->leallit();
    exit();
}

// Adott felhasználó létezik-e már
if( isset($_POST['login_letezik']) ) {

    // Loginnév foglalt-e
    if( $adatbazis_kapcsolat->s2('recid', 'login', "`nev`='{$_POST['nev']}'") ){
        echo json_encode(array(
            'hiba'  => 'Ez a loginnév már foglalt!',
            'title' => 'Hiba',
            'focus' => 'nev',
        ));
        
        $weblap->leallit();
        exit();
    }
    
    // E-mail címek validálása
    $hibauzenet = is_valid_mail_tobb($_POST['usermail'], 'hibaüzenettel');
    if( $hibauzenet ){
        echo json_encode(array(
            'hiba'  => $hibauzenet,
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }
    
    // Ha a személyzettípusa benne van az alábbiakban, akkor rákérdezés
    if( in_array($_POST['szemelyzet_id'], loginbol_torzsadatot_szemelyzetek() ) && $_POST['torzsadatba_mehet'] == 'false' ) {
        echo json_encode(array(
            'hiba'  => nl2('
                            '.$_POST['teljes_nev'].' – '.getSzerepkorNev($_POST['szemelyzet_id']).'
                            <br>
                            <br>Az új felhasználó szerepköre szerint a műszaki személyzethez tartozik.
                            <br>Ha folytatja, adatait felvesszük a személyzet adatbázisába is.
                            <br>Folytatja?
                            '),
            'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="torzsadatba_mehet = true; uj_login_feltolt_ellenozesek();"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">',
            'title' => 'Kérdés',
        ));

        $weblap->leallit();
        exit();
    }
    
    // Valami kimenet kell legyen
    echo json_encode(array(
        'mehet' => 'mehet',
    ));

    $weblap->leallit();
    exit();
}

// Szálloda választó popup
if( isset($_POST['getSzallodak']) ) {

    // Szálloda lekérdezése
    $telephelyek = $adatbazis_kapcsolat->s('id nev recid modell', 'telephely', "`inaktiv`='0'", 'nev');

    // Felhasználóhoz kapcsolt szállodák
    $felh = $adatbazis_kapcsolat->s2('nev', 'login', "`recid` = '".$_POST['recid']."'");

    // Kapcsolt szállodák
    $felh_szallodak = $adatbazis_kapcsolat->s('telephely_id', 'telephely_kapcs', "`nev` = '".$felh['nev']."'");
    $felh_szallodak = adatbazis2tomb($felh_szallodak, 'telephely_id');

    if( !$felh_szallodak ) {
        $felh_szallodak = array();
    }

    $html = '';
    foreach ( $telephelyek as $telephely ) {
        $checked = '';
        if( in_array($telephely['id'], $felh_szallodak) ) {
            $checked = ' checked="checked"';
        }

        $html .= nl2('
            <div class="dtr">
                <div class="dtc">
                    <div class="cb">
                        <input type="checkbox" class="check" name="telephely[]" id="telephely-'.$telephely['recid'].'" value="'.$telephely['id'].'" data-pesti="'.$telephely['modell'].'"'.$checked.'>
                        <label for="telephely-'.$telephely['recid'].'"></label>
                    </div>
                </div>
                <div class="dtc">
                    <label class="link" for="telephely-'.$telephely['recid'].'">
                        '.$telephely['nev'].'
                    </label>
                </div>
            </div>
        ');
    }

    echo $html;

    $weblap->leallit();
    exit();
}

// Részleg választó popup
if( isset($_POST['getReszlegek']) ) {

    // Részleg lekérdezése
    $reszlegek = $adatbazis_kapcsolat->s('id info', 'erteklista', "`csoport`='".ERTEKLISTA_RESZLEG."'", 'info');
    
    $akt_reszleg = $adatbazis_kapcsolat->s2('reszleg_id', 'login', "`recid`='{$_POST['recid']}'");

    // Kapcsolt részlegek
    $felh_reszlegek = $adatbazis_kapcsolat->s('reszleg_id', 'login_tarsreszleg', "`login_id` = '".$_POST['recid']."'");
    $felh_reszlegek = adatbazis2tomb($felh_reszlegek, 'reszleg_id');

    if( !$felh_reszlegek ) {
        $felh_reszlegek = array();
    }

    $html = '';
    foreach($reszlegek as $reszleg) {
        
        $checked = '';
        if( in_array($reszleg['id'], $felh_reszlegek) ) {
            $checked = ' checked="checked"';
        }

        $html .= nl2('
            <div class="dtr">
                <div class="dtc">
                    <div class="cb">
                        <input type="checkbox" class="check" name="reszleg[]" id="reszleg-'.$reszleg['id'].'" value="'.$reszleg['id'].'"'.$checked.'>
                        <label for="reszleg-'.$reszleg['id'].'"></label>
                    </div>
                </div>
                <div class="dtc">
                    <label class="link" for="reszleg-'.$reszleg['id'].'">
                        '.$reszleg['info'].'
                    </label>
                </div>
            </div>
        ');
    }

    echo $html;

    $weblap->leallit();
    exit();
}

// Részleg hozzáadása felhasználóhoz ajaxosan
if( isset($_GET['addReszleg']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if(!_jog(JOG_FELHASZNALOK_KEZEL)){
        echo json_encode(array(
            'hiba'      => 'Önnek nincs joga felhasználót kezelni!',
            'title'     => 'Hiba'
        ));
        $weblap->leallit();
        exit();
    }

    $loginID  = $_GET['loginID'];
    $reszlegIDs = json_decode($_GET['reszlegIDs'], true);

    if( $loginID ) {

        $felh = $adatbazis_kapcsolat->s2('nev reszleg_id', 'login', "`recid`='{$loginID}'");
        if( $felh ) {

            $log = '';
            $akt_reszlegek = $adatbazis_kapcsolat->s('reszleg_id', 'login_tarsreszleg', "`login_id`='{$loginID}'");
            if( $akt_reszlegek === false ) { $db_error = true; }
            $akt_reszlegek = adatbazis2tomb($akt_reszlegek, 'reszleg_id');

            $log .= 'Aktuális részlegek: '.implode(', ', $akt_reszlegek).'. Új részlegek: '.implode(', ', $reszlegIDs);

            // Összes részleg ürítése, majd egyesével felvesszük
            $sql = $adatbazis_kapcsolat -> d('login_tarsreszleg', "`login_id`='{$loginID}'");
            if( $sql === false ) { $db_error = true; }

            // Hozzáadjuk a részlegeket
            foreach($reszlegIDs as $key => $reszlegID) {
                if($felh['reszleg_id']!=$reszlegID){
                    $kapcsID = $adatbazis_kapcsolat -> i('login_tarsreszleg',
                        array('login_id',   'reszleg_id', 'insert_dt',    'insert_usr'                            ),
                        array($loginID,     $reszlegID,   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  )
                    );
                    if( $kapcsID === false ) { $db_error = true; }
                }
            }

            // Logba is eltároljuk
            $sql = $adatbazis_kapcsolat -> i('log',
                                        array( 'felh_id',                               'nev',                                                                                  'valtozas',    'ip'                    ),
                                        array( $_SESSION[get_db_tabla().'login_recid'], $_SESSION[get_db_tabla().'teljes_nev'].' módosította '.$felh['nev'].' társrészlegeit',  $log,          $_SERVER['REMOTE_ADDR'] )
                                    );
            if( $sql === false ) { $db_error = true; }
            
            // Számoljuk ki az aktuális társrészlegek listáját, hogy az adatlapon ki tudjuk cserélni
            $reszlegek_html = get_tarsreszleg_lista($loginID);

            if( !$db_error ) {
                mysqli_commit($db_kapcs);
                echo json_encode(array(
                    'statusz'       => 'siker',
                    'kapcsID'       => $kapcsID,
                    'tarsreszlegek' => $reszlegek_html,
                ));
            } else {
                mysqli_rollback($db_kapcs);
                echo json_encode(array(
                    'hiba'  => tranzakcioHiba(),
                    'title' => 'Hiba'
                ));
            }

        } else {
            echo json_encode(array(
                'hiba'      => 'Felhasználó azonosítása nem sikerült!',
                'title'     => 'Hiba'
            ));
        }
    }

    $weblap->leallit();
    exit();
}

// Felhasználó választó popup
if( isset($_POST['getLoginok']) ) {

    // Kapcsolt (már team) loginok
    $login_team = $adatbazis_kapcsolat->s('login_recid', 'projektkezeles_login', "`projektkezeles_recid`='".$_POST['recid']."'");
    $login_team = adatbazis2tomb($login_team, 'login_recid');
    if( !$login_team ) {
        $login_team = array();
    }

    // Felhasználók lekérdezése
    $loginok = $adatbazis_kapcsolat->s('recid, nev, teljes_nev, beosztas_id', 'login', "", 'nev');

    $html = '';
    foreach($loginok as $login) {
        $checked = '';
        if( in_array($login['recid'], $login_team) ) {
            $checked = ' checked="checked"';
        }

        $html .= nl2('
            <div class="dtr">
                <div class="dtc">
                    <div class="cb">
                        <input type="checkbox" class="check" name="loginok[]" id="login-'.$login['recid'].'" value="'.$login['recid'].'"'.$checked.'>
                        <label for="login-'.$login['recid'].'"></label>
                    </div>
                </div>
                <div class="dtc">
                    '.$login['teljes_nev'].' ('.erteklista(ERTEKLISTA_BEOSZTAS, $login['beosztas_id']).')
                </div>
            </div>
        ');
    }

    echo $html;

    $weblap->leallit();
    exit();
}


// Szálloda hozzáadása felhasználóhoz ajaxosan
if( isset($_GET['addSzalloda']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    if(!_jog(JOG_FELHASZNALOK_KEZEL)){
        echo json_encode(array(
            'statusz' => 'hiba',
            'uzi'     => 'Önnek nincs joga felhasználót kezelni!'
        ));
        $weblap->leallit();
        exit();
    }

    $loginID  = $_GET['loginID'];
    $szallIDs = json_decode($_GET['szallIDs'], true);

    if( $loginID && count($szallIDs) ) {

        $felh = $adatbazis_kapcsolat->s2('nev', 'login', "`recid`='{$loginID}'");
        if( $felh ) {

            $log = '';
            $akt_szallodak = $adatbazis_kapcsolat->s('telephely_id', 'telephely_kapcs', "`nev`='{$felh['nev']}'");
            if( $akt_szallodak === false ) { $db_error = true; }
            $akt_szallodak = adatbazis2tomb($akt_szallodak, 'telephely_id');

            $log .= 'Aktuális szállodák: '.implode(', ', $akt_szallodak).'. Új szállodák: '.implode(', ', $szallIDs);

            $sql = $adatbazis_kapcsolat -> d('telephely_kapcs', "`nev`='{$felh['nev']}'");
            if( $sql === false ) { $db_error = true; }

            // Hozzáadjuk a szállodákat
            foreach($szallIDs as $key => $szallID) {
                $kapcsID = $adatbazis_kapcsolat -> i('telephely_kapcs',
                    array('nev',        'telephely_id', 'insert_dt',    'insert_usr',                           'update_usr'                            ),
                    array($felh['nev'], $szallID,       'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'], $_SESSION[get_db_tabla().'teljes_nev']  )
                );
                if( $kapcsID === false ) { $db_error = true; }
            }

            // Logba is eltároljuk
            $sql = $adatbazis_kapcsolat -> i('log',
                                        array( 'felh_id',                               'nev',                                                                              'valtozas',    'ip'                    ),
                                        array( $_SESSION[get_db_tabla().'login_recid'], $_SESSION[get_db_tabla().'teljes_nev'].' módosította '.$felh['nev'].' szállodáit',  $log,          $_SERVER['REMOTE_ADDR'] )
                                    );
            if( $sql === false ) { $db_error = true; }

            if( !$db_error ) {
                mysqli_commit($db_kapcs);
                echo json_encode(array(
                    'statusz' => 'siker',
                    'kapcsID' => $kapcsID,
                    'uzi'     => ''
                ));
            } else {
                mysqli_rollback($db_kapcs);
                echo json_encode(array(
                    'hiba'  => tranzakcioHiba(),
                    'title' => 'Hiba'
                ));
            }

        } else {
            echo json_encode(array(
                'statusz' => 'hiba',
                'uzi'     => 'Felhasználó azonosítása nem sikerült!'
            ));
        }
    }

    $weblap->leallit();
    exit();
}


// CSV generálása
if( isset($_GET['csv']) ) {

    // CSV export előkészítése
    list($mibol, $mibe, $filename, $open) = csv_export_elokeszit($megnevezes);
    if( $open ) {

        $sql = 'SELECT
                                                    `'.get_db_tabla().$megnevezes.'`.`nev` as \'Loginnév\',
                                                    `teljes_nev` as \'Teljes név\',
                                                    `usertel` as \'Telefon\',
                                                    `usermail` as \'E-mail\',
                                                    `szemelyzet_id` as \'Szerepkör\',
                                                    `beosztas_id` as \'Beosztás\',
                                                    `reszleg_id` as \'Részleg\',
                                                    `virtual` as \'Virtuális\',
                                                    `aktiv` as \'Aktiv\'
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
                if($kulcs=='Szerepkör'){
                    $sor = getSzerepkorNev($sor);
                }
                if($kulcs=='Telefon'){
                    $sor = '{EGYENLOSEG_JELET_ELE}'.$sor.'{EGYENLOSEG_JELET_ELE_VEGE}';
                }
                if($kulcs=='Beosztás'){
                    $sor = erteklista(ERTEKLISTA_BEOSZTAS, $sor);
                }
                if($kulcs=='Részleg'){
                    $sor = erteklista(ERTEKLISTA_RESZLEG, $sor);
                }
                if($kulcs=='Virtuális' || $kulcs=='Törölve' || $kulcs=='Aktiv'){
                    if( $sor ) {
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
                $sor = str_replace(
                                array('"{EGYENLOSEG_JELET_ELE}',    '{EGYENLOSEG_JELET_ELE_VEGE}"',     '{EGYENLOSEG_JELET_ELE}',   '{EGYENLOSEG_JELET_ELE_VEGE}', ';="";'  ),
                                array('="',                         '"',                                '="',                       '"',                            ';;'    ),
                                $sor
                        );
                $sor = iconv($mibol, $mibe.'//TRANSLIT', strip_tags( $sor ) );
                $csvcont[] = $sor;
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

    csv_export_lezar($fh, $filename, 'Felhasználók');
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
    if( $_GET['id'] ){
        $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");
    }
    if($sor['dok_db']){
        $dok_db  = ' ('.$sor['dok_db'].')';
    }
    if($sor['megj_db']){
        $megj_db = ' ('.$sor['megj_db'].')';
    }

    if( isset($_GET['modosit']) || isset($_GET['jogosultsag']) || isset($_GET['ertesites']) || isset($_GET['szallodak']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'jogosultsag'   => 'Jogosultságok',
            'ertesites'     => 'Értesítések',
            'szallodak'     => 'Szállodák',
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
    
    $gombok = '';
    // Kezelés joggal rendelkezők társrészleget is tudjanak megadni.
    if( _jog(JOG_FELHASZNALOK_KEZEL) ){
        $tarsreszleg_gomb .= '<input type="button" class="gomb" value="Társrészlegek kezelése" onclick="kapcsolodoSzerkeszt(\'tarsreszlegek\');">';
    }
    // Külön változóként hoztuk létre, mert a jogosultság/értesítés felület alatt másképp kell hozzáadni
    $gombok .= $tarsreszleg_gomb;

    // Új feltöltés alatti gombok
    if( isset($_GET['uj']) ) {
        $gombok .= nl2('
                        <input type="button" class="gomb" value="Szállodák hozzáadása" onclick="kapcsolodoSzerkeszt(\'szallodak\');">
                        <input type="hidden" name="szallodak_hidden" id="szallodak_hidden">
                        <input type="hidden" name="tarsreszlegek_hidden" id="tarsreszlegek_hidden">
                        ');
        if( _jog(JOG_FELHASZNALOK_KEZEL) ) {
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="uj_login_feltolt_ellenozesek();">
                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
                </div>
            ').$gombok;
        }
        
        $gombok .= nl2('
                    <script>
                    var torzsadatba_mehet = false;
                    function uj_login_feltolt_ellenozesek() {

                        szurkit(\'mehet_megsem_rejt\');

                        formModositva = false;
                        if( $(\'#nev\').val().length<8 ){
                            alert2(\'A Login név hossza nem lehet kevesebb mint 8 karakter!\', \'\', \'Hiba\');
                            $(\'#nev\').addClass(\'hibas_ide_focus\');
                            return false;
                        }

                        /* Felhasználó név foglalt-e már */
                        $.ajax({
                            url : \'login.php\',
                            type: \'POST\',
                            data: esamp2es(\'nev=\' + $(\'#nev\').val() + \'&amp;teljes_nev=\' + $(\'#teljes_nev\').val()+ \'&amp;torzsadatba_mehet=\' + torzsadatba_mehet + \'&amp;usermail=\' + $(\'#usermail\').val() + \'&amp;szemelyzet_id=\' + $(\'#szemelyzet_id\').val() + \'&amp;login_letezik=\' + new Date().getTime()),
                            success: function(response) {
                                if(response.substring(0, 6)==\'SESKIL\'){ location.href = \'index.php\'; }
                                if(response) {
                                    response = JSON.parse(response);

                                    if( response.hiba ) {
                                        alert2(response.hiba, response.gomb, response.title);
                                        alert_utan_mehet_megsem_mutat_beallit();
                                        if(response.focus){
                                            $(\'#\'+response.focus).addClass(\'hibas_ide_focus\');
                                        }
                                    } else {
                                        $(\'#szerk_form\').submit();
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
        
    // Módosítás alatti gombok
    } elseif( isset($_GET['modosit']) ) {

        if( _jog(JOG_FELHASZNALOK_KEZEL) || _jog(JOG_FELHASZNALOK_JELSZO) ) {
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
                </div>
            ').$gombok;
        }
        
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

	// Új felhasználó felvitele
    $js_submit_elott = 'return form_ell_admin();';
	if( isset($_GET['uj']) && _jog(JOG_FELHASZNALOK_KEZEL) ) {

        // Hibaüzenetek lekezelése
        if( isset($_GET['rovid_nev']) ) {
            $navi = '<span class="piros">A Login név hossza nem lehet kevesebb mint 8 karakter!</span>';
        }
        if( isset($_GET['hibasnev']) ) {
            $navi = '<span class="piros">Csak betűket, számokat, alulvonsát (_) és pontot (.) használhat!</span>';
        }
        if( isset($_GET['foglalt_nev']) ) {
            $navi = '<span class="piros">Ez a loginnév már foglalt!</span>';
        }

	    // FORM
		if( $_GET['ujkent'] ) {
            $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, 'recid='.$_GET['ujkent']);
            if( $sor ) {

                foreach ( $akt_tomb as $ertek ) {
                    if( $ertek['name'] != 'nev' && $ertek['name'] != 'van_jelszo' ) {
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

        $json_sor = array();
        foreach($sor as $kulcs => $elem){
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

	// Felhasználók módosítása
	}elseif(isset($_GET['modosit'])) {

        // Módosításnál figyeljük, hogy a szerepkör megváltozott-e
        $js_submit_elott = nl2('
                                if($(\'#szemelyzet_id\').hasClass(\'mod\')){
                                    gomb_folytatja = \'<input type="button" class="gomb zold" value="Igen" onclick="form_ell_admin()"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">\';
                                    alert2(\'A szerepkör megváltozott: \'+$(\'#szemelyzet_id option:selected\').text()+\'<br>Ha folytatja, az új szerepkör jogsultságai is beállításra kerülnek!<br>Folytatja?\', gomb_folytatja);
                                    return false;
                                } else {
                                    return form_ell_admin();
                                }
                                ');

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if( isset($_GET['id_hidden']) ) {
            $_GET['id'] = $_GET['id_hidden'];
        }

        foreach($akt_tomb as $tomb) {
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
        $szamla_html .= '<script>formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

        // Még egy technikai JS az e-mail cím ellenőrzéséhez
        $szamla_html .= '<script>var nincs_mailcim_mehet = false;</script>';

        // Duplikáláskor feltett kérdés
        $szamla_html .= nl2('
            <script>
                duplikalGomb  = "<p class=\"c\"><input class=\"gomb zold\" type=\"button\" value=\"Igen\" onclick=\"szurkit(); location.href = \''.basename(self()).'?uj&ujkent='.$_GET['id'].'\'; \"><input class=\"gomb piros\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
                duplikaKerdes = \'Név: '.$sor['teljes_nev'].'<br>Szerepkör: '.getErtek('nev', 'szerepkor', 'recid', $sor['szemelyzet_id']).'<br><br>Biztos, hogy duplikálja?\';
            </script>
        ');

	    // FORM
	    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

		// Form actionja
		$action = self().get_get(array('errnum','kephiba','siker'));

		// Ez elkészíti a $mezok változót
		require('switch.php');

        $jobb_oldal = '';

	    $szamla_html .= str_replace(
            array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}', '{UPDATE_DT}'     ),
            array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok,    $sor['update_dt'] ),
            fajlbol_olvas('../sablon/admin/form_modosit.html', true)
        );

        $szamla_html = nl2($szamla_html);

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

	// Jogosultság
    }elseif( isset($_GET['jogosultsag']) ) {

        require_once('jogosultsag.php');

        $login = $adatbazis_kapcsolat->s2('flags jh_ft_ig', 'login', "`recid`='{$_GET['id']}'", '', '1');
        if( $login ) {

            // Jogosultságok feldarabolás
            $flags  = str_split($login['flags']     );

            // Ellenőrzéshez
            $jogEll = array();
            $i = 0;
            foreach($jogosultsagok as $jog) {
                if( $flags[$i] ) {
                    $jogEll['jog-'.$i] = 1;
                } else {
                    $jogEll['jog-'.$i] = 0;
                }

                $i++;
            }
            $jogEll['jh_ft_ig'] = $login['jh_ft_ig'];

            $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($jogEll).'\'); </script>';

            // Hozzáadjuk az értékeket a jogosultságokhoz
            $i = 0;
            foreach($jogosultsagok as $jog) {
                $jogosultsagok[$i]['value']      = $flags[$i];
                $jogosultsagok[$i]['id']         = $i;

                $i++;
            }

            // Kigyűjtjük a kategóriákat
            $jogKatok = jogosultsagKatok( $jogosultsagok );

            // Rendezzük a kategóriákat
            function sortByOrder($a, $b) {
                return $a['sor'] - $b['sor'];
            }
            usort($jogKatok, 'sortByOrder');

            $szamla_html .= nl2('
                                <script>
                                    function form_elkuld(){

                                        param = \'\';
                                        $(\'input[type="checkbox"]\').each(function () {
                                            param += $(this).attr(\'name\')+\'=\'+$(this).is(\':checked\')+\'&amp;\';
                                        });
                                        param += \'jh_ft_ig=\'+$(\'#jh_ft_ig\').val();
                                        
                                        $.ajax({
                                            url    : \''.self().'\',
                                            type   : \'POST\',
                                            data   : esamp2es(param+\'&amp;id='.$_GET['id'].'&amp;jogokhozzaad=\' + new Date().getTime()),
                                            success: function (response) {

                                                if(response.substring(0, 6)==\'SESKIL\'){ location.href = \'index.php\'; }
                                                $(\'input[type="checkbox"]\').removeClass(\'mod\');
                                                $(\'#jh_ft_ig\').removeClass(\'mod\');
                                                
                                                formModositva = false;
                                                $(\'.mentes_gomb\').attr(\'disabled\', true);
                                                $(\'#megsem_gomb\').attr(\'disabled\', true);
                                                
                                                szurkit_zar();
                                                formAdatok = JSON.parse(response);
                                            },
                                            complete: function(xhr) {
                                                if( xhr.status != 200 ) {
                                                    alert2(xhr.responseText, \'\', \'Hiba\');
                                                }
                                            }
                                        });
                                        return false;
                                    }
                                </script>
                                <form id="szerk_form" action="" method="post" onsubmit="return form_elkuld();">
                                    <div class="dt">
                ');

            $jogdb      = count($jogKatok);
            $jogdb_fel  = ceil($jogdb / 2);

            // Kiíratjuk az egészet
            $i = 1;
            foreach($jogKatok as $jogKat) {
                if( $i == 1 ) {
                    $szamla_html .= '
                        <div class="dt jog_dt">
                            <div class="dtr">
                                <div class="dtc jog_dtc">
                    ';
                } else if( $i == $jogdb_fel ) {
                    $szamla_html .= '
                                </div>
                                <div class="dtc jog_dtc">
                    ';
                }

                $szamla_html .= '
                    <div class="dtr">
                        <div class="dtc form_bal jogKat"><span>'.$jogKat['nev'].':</span></div>
                        <div class="dtc form_jobb"></div>
                    </div>
                ';

                $jogSorok = getJogSorRendez( $jogKat['id'],  $jogosultsagok);
                usort($jogSorok, 'sortByOrder');
                foreach($jogSorok as $jogSor) {

                    $checked = '';
                    if( $jogSor['value'] ) {
                        $checked = ' checked="checked"';
                    }

                    $mail_checked = '';
                    if( $jogSor['mail_value'] ) {
                        $mail_checked = ' checked="checked"';
                    }

                    $jogSor['nev'] .= ':';
                    if($jogSor['i']){
                        $jogSor['nev'] .= '<br><i class="f12">('.$jogSor['i'].')</i><div style="margin-bottom: 0.8rem"></div>';
                    }

                    $onclick = '';
                    $kikapcs = '';
                    if($jogSor['kikapcs']!=''/*Lényeges, hogy az üres stringgel legyen hasonlítva, mert 0 lehet értéke*/){
                        foreach(explode(',', $jogSor['kikapcs']) as $pipa_ki){
                            $kikapcs .= '$(\'#jog-'.$pipa_ki.'\').prop(\'checked\', false).removeClass(\'mod\');';
                        }
                    }
                    $kikapcskor_kikapcs = '';
                    if($jogSor['kikapcskor_kikapcs']==''/*Lényeges, hogy az üres stringgel legyen hasonlítva, mert 0 lehet értéke*/){
                        foreach(explode(',', $jogSor['kikapcskor_kikapcs']) as $pipa_ki){
                            $kikapcskor_kikapcs .= '$(\'#jog-'.$pipa_ki.'\').prop(\'checked\', false).addClass(\'mod\');';
                        }
                    }
                    $bekapcs = '';
                    if($jogSor['bekapcs']!=''){
                        foreach(explode(',', $jogSor['bekapcs']) as $pipa_be){
                            $bekapcs .= '$(\'#jog-'.$pipa_be.'\').prop(\'checked\', true).addClass(\'mod\');';
                        }
                    }

                    // Ha épp most kap bekapcsolást, csak akkor futtatjuk le
                    if($bekapcs){
                        $bekapcs = 'if( $(this).prop(\'checked\') ){'.$bekapcs.'}';
                    }
                    // Ha épp most kapcsoltuk ki
                    if($kikapcskor_kikapcs){
                        $kikapcskor_kikapcs = 'if( $(this).prop(\'checked\') == false ){'.$kikapcskor_kikapcs.'}';
                    }

                    if($kikapcs || $kikapcskor_kikapcs || $bekapcs){
                        $onclick = ' onclick="'.$kikapcs.$kikapcskor_kikapcs.$bekapcs.'"';
                    }

                    // A már törölteket rejtsük el
                    $dn = '';
                    if($jogSor['torolve']){
                        $dn = ' dn';
                    }

                    // Van ahol a pipát kell elrejteni
                    $dn_pipa  = '';
                    $dn_pipac = '';
                    if($jogSor['nincs_pipa']){
                        $dn_pipa  = ' dn';
                        $dn_pipac = ' class="dn"';
                    }
                    $dn_mail  = ' dn';
                    if($jogSor['mail']){
                        $dn_mail = '';
                    }

                    $jh_ft_ig = '';
                    // Külsős jóváhagyás jognál plusz input
                    if($jogSor['id']==JOG_MUNKALAP_KULSOS_DONTES){
                        $jh_ft_ig = '<input type="text" id="jh_ft_ig" name="jh_ft_ig" value="'.number_format($login['jh_ft_ig'], 0, '', ' ').'" class="f_num f_select" data-tort="0" data- style="margin-left: 5rem; width: 10rem;"> '. get_penznem().'-ig';
                    }
                    
                    // WebDeb szerverén mutatjuk a jog sorszámát is
                    $sorszam = '';
                    if( $_SERVER['HTTP_HOST'] == 'demo.webdeb.hu' ){
                        $sorszam = '['.$jogSor['id'].'] ';
                    }
                    
                    $szamla_html .= nl2('
                        <div class="dtr'.$dn.'">
                            <div class="dtc form_bal"><span>'.$sorszam.$jogSor['nev'].'</span>'.$jh_ft_ig.'</div>
                            <div class="dtc form_jobb">
                                <div class="cb">
                                    <input type="hidden" name="jog-'.$jogSor['id'].'" value="0">
                                    <input type="checkbox" class="check'.$dn_pipa.'" name="jog-'.$jogSor['id'].'" id="jog-'.$jogSor['id'].'" value="1"'.$checked.$onclick.'>
                                    <label'.$dn_pipac.' for="jog-'.$jogSor['id'].'"></label>
                                </div>
                            </div>
                            <div class="dtc form_jobb'.$dn_mail.'">
                                <div class="cb">
                                    <input type="hidden" name="mail-'.$jogSor['id'].'" value="0">
                                    <input type="checkbox" class="check'.$dn_pipa.'" name="mail-'.$jogSor['id'].'" id="mail-'.$jogSor['id'].'" value="1"'.$mail_checked.'>
                                    <label'.$dn_pipac.' for="mail-'.$jogSor['id'].'"></label>
                                </div>
                            </div>
                        </div>
                    ');
                }

                if( $jogdb == $i ) {
                    $szamla_html .= '
                            </div>
                        </div>
                    ';
                }

                $i++;
            }

            $muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 16).'px);"';
            if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
                if( $_COOKIE['csuszkaSzelesseg'] ) {
                    $muv_style = ' style="width: calc(100% - '.(($_COOKIE['csuszkaSzelesseg'] + 16)/10).'rem);"';
                }
            }
            
            if( _jog(JOG_FELHASZNALOK_KEZEL) ){
                $szamla_html .= nl2('
                        <div class="muveletek"'.$muv_style.'>
                            <div class="form_kuldes">
                                <input type="hidden" name="id" value="'.$_GET['id'].'">
                                <input type="submit" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="szurkit(\'mehet_megsem_rejt\'); form_elkuld();">
                                <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
                                '.$tarsreszleg_gomb.'
                            </div>
                        </div>');
            } else {
                $szamla_html .= nl2('
                        <script>
                            $(document).ready(function () {
                                $(\'div.cb input\').attr(\'disabled\', true);
                            });
                        </script>
                    ');
            }
            
            $szamla_html .= nl2('</div>
                </form>
            ');

        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html
            ));
        } else {
            echo $szamla_html;
        }

	// Értesítések
    }elseif(isset($_GET['ertesites'])) {

        require_once('ertesitesek.php');

        $login = $adatbazis_kapcsolat->s2('flags_ert', 'login', "`recid`='{$_GET['id']}'", '', '1');
        if( $login ) {

            // Értesítések feldarabolás
            $flags = str_split($login['flags_ert']);

            // Ellenőrzéshez
            $ertEll = array();
            $i = 0;
            foreach($ertesitesek as $ert) {
                if( $flags[$i] ) {
                    $ertEll['ert-'.$i] = 1;
                } else {
                    $ertEll['ert-'.$i] = 0;
                }

                $i++;
            }

            $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($ertEll).'\'); </script>';

            // Hozzáadjuk az értékeket az értesítésekhez
            $i = 0;
            foreach($ertesitesek as $ert) {
                $ertesitesek[$i]['value']      = $flags[$i];
                $ertesitesek[$i]['id']         = $i;

                $i++;
            }

            // Kigyűjtjük a kategóriákat
            $ertKatok = ertesitesKatok( $ertesitesek );

            // Rendezzük a kategóriákat
            function sortByOrder($a, $b) {
                return $a['sor'] - $b['sor'];
            }
            usort($ertKatok, 'sortByOrder');

            $szamla_html .= nl2('
                                <script>
                                    function form_elkuld(){
                                        param = \'\';
                                        $(\'input[type="checkbox"]\').each(function () {
                                            param += $(this).attr(\'name\')+\'=\'+$(this).is(\':checked\')+\'&amp;\';
                                        });

                                        $.ajax({
                                            url    : \''.self().'\',
                                            type   : \'POST\',
                                            data   : esamp2es(param+\'&amp;id='.$_GET['id'].'&amp;ertekhozzaad=\' + new Date().getTime()),
                                            success: function (response) {

                                                if(response.substring(0, 6)==\'SESKIL\'){ location.href = \'index.php\'; }
                                                $(\'input[type="checkbox"]\').removeClass(\'mod\');
                                                formModositva = false;
                                                $(\'.mentes_gomb\').attr(\'disabled\', true);
                                                $(\'#megsem_gomb\').attr(\'disabled\', true);

                                                formAdatok = JSON.parse(response);
                                            },
                                            complete: function(xhr) {
                                                if( xhr.status != 200 ) {
                                                    alert2(xhr.responseText, \'\', \'Hiba\');
                                                }
                                            }
                                        });
                                        return false;
                                    }
                                </script>
                                <form id="szerk_form" action="" method="post" onsubmit="return form_elkuld();">
                                    <div class="dt">
                ');

            $ertdb      = count($ertKatok);
            $ertdb_fel  = ceil($ertdb / 2);

            // Kiíratjuk az egészet
            $i = 1;
            foreach($ertKatok as $ertKat) {
                if( $i == 1 ) {
                    $szamla_html .= '
                        <div class="dt jog_dt">
                            <div class="dtr">
                                <div class="dtc jog_dtc">
                    ';
                } else if( $i == $ertdb_fel ) {
                    $szamla_html .= '
                                </div>
                                <div class="dtc jog_dtc">
                    ';
                }

                $szamla_html .= '
                    <div class="dtr">
                        <div class="dtc form_bal jogKat"><span>'.$ertKat['nev'].':</span></div>
                        <div class="dtc form_jobb"></div>
                    </div>
                ';

                $ertSorok = getJogSorRendez( $ertKat['id'],  $ertesitesek);
                usort($ertSorok, 'sortByOrder');

                foreach($ertSorok as $ertSor) {

                    $checked = '';
                    if( $ertSor['value'] ) {
                        $checked = ' checked="checked"';
                    }

                    $ertSor['nev'] .= ':';
                    if($ertSor['i']){
                        $ertSor['nev'] .= '<br><i class="f12">('.$ertSor['i'].')</i><div style="margin-bottom: 0.8rem"></div>';
                    }

                    $onclick = '';
                    $kikapcs = '';
                    if($ertSor['kikapcs']!=''/*Lényeges, hogy az üres stringgel legyen hasonlítva, mert 0 lehet értéke*/){
                        foreach(explode(',', $ertSor['kikapcs']) as $pipa_ki){
                            $kikapcs .= '$(\'#ert-'.$pipa_ki.'\').prop(\'checked\', false).removeClass(\'mod\');';
                        }
                    }
                    $kikapcskor_kikapcs = '';
                    if($ertSor['kikapcskor_kikapcs']==''/*Lényeges, hogy az üres stringgel legyen hasonlítva, mert 0 lehet értéke*/){
                        foreach(explode(',', $ertSor['kikapcskor_kikapcs']) as $pipa_ki){
                            $kikapcskor_kikapcs .= '$(\'#ert-'.$pipa_ki.'\').prop(\'checked\', false).addClass(\'mod\');';
                        }
                    }
                    $bekapcs = '';
                    if($ertSor['bekapcs']!=''){
                        foreach(explode(',', $ertSor['bekapcs']) as $pipa_be){
                            $bekapcs .= '$(\'#ert-'.$pipa_be.'\').prop(\'checked\', true).addClass(\'mod\');';
                        }
                    }

                    // Ha épp most kap bekapcsolást, csak akkor futtatjuk le
                    if($bekapcs){
                        $bekapcs = 'if( $(this).prop(\'checked\') ){'.$bekapcs.'}';
                    }
                    // Ha épp most kapcsoltuk ki
                    if($kikapcskor_kikapcs){
                        $kikapcskor_kikapcs = 'if( $(this).prop(\'checked\') == false ){'.$kikapcskor_kikapcs.'}';
                    }

                    if($kikapcs || $kikapcskor_kikapcs || $bekapcs){
                        $onclick = ' onclick="'.$kikapcs.$kikapcskor_kikapcs.$bekapcs.'"';
                    }

                    // A már törölteket rejtsük el
                    $dn = '';
                    if($ertSor['torolve']){
                        $dn = ' dn';
                    }

                    // Van ahol a pipát kell elrejteni
                    $dn_pipa  = '';
                    $dn_pipac = '';
                    if($ertSor['nincs_pipa']){
                        $dn_pipa  = ' dn';
                        $dn_pipac = ' class="dn"';
                    }

                    $szamla_html .= nl2('
                        <div class="dtr'.$dn.'">
                            <div class="dtc form_bal"><span>'.$ertSor['nev'].'</span></div>
                            <div class="dtc form_jobb">
                                <div class="cb">
                                    <input type="hidden" name="ert-'.$ertSor['id'].'" value="0">
                                    <input type="checkbox" class="check'.$dn_pipa.'" name="ert-'.$ertSor['id'].'" id="ert-'.$ertSor['id'].'" value="1"'.$checked.$onclick.'>
                                    <label'.$dn_pipac.' for="ert-'.$ertSor['id'].'"></label>
                                </div>
                            </div>
                        </div>
                    ');
                }

                if( $ertdb == $i ) {
                    $szamla_html .= '
                            </div>
                        </div>
                    ';
                }

                $i++;
            }

            $muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 16).'px);"';
            if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
                if( $_COOKIE['csuszkaSzelesseg'] ) {
                    $muv_style = ' style="width: calc(100% - '.(($_COOKIE['csuszkaSzelesseg'] + 16)/10).'rem);"';
                }
            }

            if( _jog(JOG_FELHASZNALOK_KEZEL) ){
                $szamla_html .= nl2('
                        <div class="muveletek"'.$muv_style.'>
                            <div class="form_kuldes">
                                <input type="hidden" name="id" value="'.$_GET['id'].'">
                                <input type="submit" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="szurkit(\'mehet_megsem_rejt\'); form_elkuld();">
                                <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
                                '.$tarsreszleg_gomb.'
                            </div>');
            } else {
                $szamla_html .= nl2('
                        <script>
                            $(document).ready(function () {
                                $(\'div.cb input\').attr(\'disabled\', true);
                            });
                        </script>
                    ');
            }
            
            $szamla_html .= nl2('</div>
                    </div>
                </form>
            ');

        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html
            ));
        } else {
            echo $szamla_html;
        }

    // Szállodák fül
    }elseif(isset($_GET['szallodak'])) {

        $felh = $adatbazis_kapcsolat->s2('recid nev', 'login', 'recid='.$_GET['id'], '', '1');
        if( $felh ) {

            $sorok = $adatbazis_kapcsolat->s('recid telephely_id', 'telephely_kapcs', '`nev`=\''.$felh['nev'].'\'');
            $szamla_html .= '<div id="tablazatBeszur">';
            if( $sorok ) {

                // Fejlécek
                $szamla_html .= nl2('
                    <div class="dt naplo szakter kijCont" data-tabla="telephely_kapcs" data-mellek="szallodak">
                        <div class="dtr nFejlec">
                            <div class="dtc">Név</div>
                        </div>
                ');

                $i = 1;
                $telephelyek = adatbazis2tomb($sorok, 'telephely_id');
                $telephelyek = $adatbazis_kapcsolat->s('id nev', 'telephely', "`id` IN (".implode(',', $telephelyek).") AND `inaktiv`=0", 'nev');
                foreach($telephelyek as $telephely) {

                    $kapcs_id = $adatbazis_kapcsolat->s2('recid', 'telephely_kapcs', "`telephely_id`='{$telephely['id']}' AND `nev`='{$felh['nev']}'");

                    $szamla_html .= nl2('
                        <div id="naploSor-'.$kapcs_id['recid'].'" class="dtr nSor kijSor" data-id="'.$kapcs_id['recid'].'" data-i="'.$i.'">
                            <div class="dtc full">'.$telephely['nev'].'</div>
                        </div>
                    ');

                    $i++;
                }

                $szamla_html .= '</div>';
            }

            $szamla_html .= '</div>';
        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }


	// Dokumentumok
	}elseif( isset($_GET['dokumentumok']) ) {

        $szamla_html .= 'Dokumentumok felülete';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Megjegyzések
	}elseif(isset($_GET['megjegyzesek'])){

        // <div class="dt naplo" class nevekből a kijCont kivéve, hogy a megjegyzéseket ne lehessen törölni
        $szamla_html .= nl2('
            <div class="dt naplo" data-tabla="megjegyzes" data-mellek="megjegyzesek">
                <div class="dtr nFejlec">
                    <div class="dtc">Megjegyzés</div>
                    <div class="dtc">Hozzáadta</div>
                    <div class="dtc">Hozzáadva</div>
                </div>
        ');

        $sorok = $adatbazis_kapcsolat->s('recid megj insert_usr insert_dt', 'megjegyzes', "`id` = '".$_GET['id']."' AND `tabla` = '".str_replace('.php', '', basename(self()))."'", 'recid desc');

        if( $sorok ) {
            $i = 1;
            foreach($sorok as $sor) {
                $szamla_html .= nl2('
                    <div id="naploSor-'.$sor['recid'].'" data-id="'.$sor['recid'].'" data-i="'.$i.'" class="dtr nSor kijSor">
                        <div class="dtc">'.htmlspecialchars_decode( nl2br($sor['megj']) ).'</div>
                        <div class="dtc">'.$sor['insert_usr'].'</div>
                        <div class="dtc">'.datum_kiir($sor['insert_dt']).'</div>
                    </div>
                ');

                $i++;
            }
        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom'  => $szamla_html.$gombok,
                'link'      => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
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
