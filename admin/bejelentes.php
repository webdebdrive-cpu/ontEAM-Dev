<?php
/*
Játékszabályok:

- Két eset van: Egy bejelentés egy munkalap illetve egy bejelentés több munkalap
- Ha egy munkalapot csoportosítunk, akkor a benne levő munkalapok automatikusan várakozóra is kerülnek
  (és áttételesen a hozzá tartozó bejelentés is).
- Ha kiveszünk belőle egy elemet, akkor annak státusza nyitottra vált (és áttételesen a hozzá tartozó bejelentés is).
- Csoportot lehessen egyben visszatenni nyitottnak, de ekkor nem bomlik fel a csoport.

*/

require_once('../weblap.php');

if( !_jog(JOG_BEJELENTES_MEGTEKINT) && !_jog(JOG_BEJELENTES_SAJAT_RESZL) ) {
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
$akt_tomb               = $bejelentes_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'bejelentést';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

$szamla_html = '';

// Dokumentumok fülhöz kell majd
$tobb_kep = true;
if($tobb_kep){
    function get_tobbkep_darab()    { return 10;        } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '';        } // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;        } // MB-ban
    function get_tobbkep_oszlopnev(){                      // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!
}

// Új bejelentéskor előbb szállodát választunk, popup-ban feljön egy új szálloda lista
if( isset($_POST['bejSzallodaValaszt']) ) {
    $szall_idk = get_akt_login_szallodai();

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $szall_idk ) {
        $sql = $szallodak = $adatbazis_kapcsolat->s('id nev', 'telephely', "`id` IN ('".implode("', '", $szall_idk)."') AND `inaktiv`=0", 'nev');
        if( $sql === false ) { $db_error = true; }

        $html = '';
        if( $szallodak ) {
            $html .= '<select onchange="bejBejelentok( this.value );" style="width: 40rem;"><option value="">- Válasszon -</option>';

            foreach($szallodak as $szalloda) {
                $html .= '<option value="'.$szalloda['id'].'">'.$szalloda['nev'].'</option>';
            }

            $html .= '</select>';
        }
        
        // Ha csak egy szálloda van, akkor azt automatikusan válasszuk is ki.
        if( count($szallodak)==1 ){
            $html .= nl2('
                        <script>
                            $(document).ready(function () {
                                bejBejelentok( '.$szallodak[0]['id'].' );
                            });
                        </script>
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
                'title' => 'Hiba'
            ));
        }
    }

    $weblap->leallit();
    exit();
}


// Folyamatban levő munkák
if( isset($_GET['folyamatban_munkak']) ) {

    $telephely_id = $_GET['telephely_id'];
    $obj_id       = $_GET['obj_id'];
    $mod          = $_GET['mod'];

    // Ha szerkesztésnél ellenőrzünk
    $where = '';
    if( $mod == 'true' ) {
        $where = " AND `recid`!='{$_GET['recid']}'";
    }

    $bejelentesek = $adatbazis_kapcsolat->s('ikt_szam surgos telephely_id obj_id hiba_id hiba_text', 'bejelentes', "`telephely_id` = '{$telephely_id}' AND `obj_id` = '{$obj_id}'".$where);
    // Ha azonos Szálloda és Helységben történik bejelentés, és nem módosításról van szó, akkor jön fel a popup (2018.05.10-én kérték, hogy csak új feltöltéskor jöjjön fel, módosításkor már ne)
    if( $bejelentesek && $mod=='false' ) {
        $html = nl2('
            <div class="mobilkeret">
                <table class="csikos">
                    <tr>
                        <th>Munkalap szám</th>
                        <th>Szálloda</th>
                        <th>Hely leírása</th>
                        <th>Bejelentett hiba</th>
                        <th>Hiba leírása</th>
                        <th>Sürgős</th>
                    </tr>
        ');

        foreach($bejelentesek as $bejelentes) {
            // Telephely
            $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$bejelentes['telephely_id'], '', '1');
            $telephely = $telephely[0]['nev'];

            // Helység
            $helyseg = $adatbazis_kapcsolat->s('nev', 'obj', 'recid='.$bejelentes['obj_id'], '', '1');
            $helyseg = $helyseg[0]['nev'];

            // Hiba
            $hiba = $adatbazis_kapcsolat->s2('info', 'erteklista', "`csoport`=4 AND `recid`='{$bejelentes['hiba_id']}'");

            $surgos = '';
            if( $bejelentes['surgos'] ) {
                $surgos = nl2('
                    <span class="icon piros">
                        <i class="material-icons">error</i>
                    </span>
                ');
            }

            $html .= nl2('
                <tr>
                    <td>'.$bejelentes['ikt_szam'].'</td>
                    <td>'.$telephely.'</td>
                    <td>'.$helyseg.'</td>
                    <td>'.nl2br($hiba['info']).'</td>
                    <td>'.nl2br($bejelentes['hiba_text']).'</td>
                    <td>'.$surgos.'</td>
                </tr>
            ');
        }

        $html .= '</table></div>';

        echo json_encode(array(
            'hiba' => 'Folyamatban lévő munkák listája'.$html.'Folytatja a hibabejelentést?',
            'gomb' => '<p class="c"><input class="gomb zold" type="button" value="Igen" onclick="szurkit(); bejelentes_mehet = true; $(\'#szerk_form\').submit();"><input class="gomb piros" type="button" value="Nem" onclick="alert2(); $(\'#mehet_gomb, #megsem_gomb\').removeAttr (\'disabled\');"></p>',
            'title'=> 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }
    echo json_encode(array('siker' => 1));

    $weblap->leallit();
    exit();
}

// Bejelentés igazolás elutasítása
if( isset($_POST['bejelentesElutasit']) ) {

    $recid          = $_POST['recid'];
    $validate       = $_POST['validate'];
    $elutasitas_id  = $_POST['elutasitas_id'];
    $erteklista     = $adatbazis_kapcsolat->s2('info', 'erteklista', "`recid`='{$elutasitas_id}'");
    $memo           = $erteklista['info']."\r\n".$_POST['memo'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        $bejelentes = $adatbazis_kapcsolat->s2('stat_id usr_reszleg_id', 'bejelentes', "`recid` = '{$recid}'", '', '1');
        if( $bejelentes === false ) { $db_error = true; }

        // Jog ellenőrzése
        if(!_jog(JOG_BEJELENTES_KEZEL)){
            echo json_encode(array(
                'hiba'  => 'Önnek nincs joga bejelentést kezelni!',
                'title' => 'Hiba'
            ));
            $weblap->leallit();
            exit();
        }
        
        // Státusz ellenőrzése
        if( $bejelentes['stat_id'] != BEJELENTES_STAT_KESZ_IG ) {
            echo json_encode(array(
                'hiba'  => 'Csak Kész, igazolás alatti bejelentések utasíthatók el!',
                'title' => 'Hiba'
            ));
            $weblap->leallit();
            exit();
        }
        
        // Részleg ellenőrzése
        $login = $adatbazis_kapcsolat->s2('reszleg_id', 'login', "`recid`='{$_SESSION[get_db_tabla().'login_recid']}'");
        $reszlegek = array($login['reszleg_id']);
        // Ha van hozzá joga, megnézzük a társrészlegeket
        if( _jog(JOG_BEJELENTES_TARSRESZLEG_BEJ_IGAZOL) ){
            $tarsreszlegek = $adatbazis_kapcsolat->s('reszleg_id', 'login_tarsreszleg', "`login_id`='{$_SESSION[get_db_tabla().'login_recid']}'");
            foreach($tarsreszlegek as $reszleg){
                array_push($reszlegek, $reszleg['reszleg_id']);
            }
        }
        // Ha adott ML részlege nincs benne a felhasználóhoz tartozó részlegek között, akkor hibaüzenet
        if(!in_array($bejelentes['usr_reszleg_id'], $reszlegek)){
            echo json_encode(array(
                'hiba'  => 'A bejelentés nem tartozik az Ön részlegéhez!',
                'title' => 'Hiba'
            ));
            $weblap->leallit();
            exit();
        }

        // Validálás.... idáig eljutunk-e, meakadunk-e a fenti feltételekben?
        if( $validate == 'true' ) {
            echo json_encode(array(
                'statusz' => 'siker'
            ));
            $weblap->leallit();
            exit();
        }
        
        // Tényleges elutasítás
        if( $bejelentes ) {
            if( !addStatusz( 'bejelentes_statusz', $recid, BEJELENTES_STAT_VIZSG_ALATT, $elutasitas_id, $memo ) ) {
                $db_error = true;
            }

            if($memo){
                if( addMegj('bejelentes', $recid, $memo) === false ) {
                    $db_error = true;
                }
            }

            if( !$db_error ) {

                mysqli_commit($db_kapcs);

                $hiba = '';
                $valasz = level_felkeszit_es_kuld('bejelentes', $recid, ERT_BEJ_IGAZOL_ELUTASITKOR, 'bejelentesIgazolElutasit', '_jog'.JOG_BEJELENTES_VIZSGAL);
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
                    'title' => 'Hiba'
                ));
            }
        }
    }

    $weblap->leallit();
    exit();
}

// Bejelentés igazolása
if( isset($_POST['bejelentesIgazolas']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        $bejelentes = $adatbazis_kapcsolat->s2('stat_id usr_reszleg_id', 'bejelentes', "`recid` = '{$recid}'", '', '1');
        if( $bejelentes === false ) { $db_error = true; }

        // Jog ellenőrzése
        if(!_jog(JOG_BEJELENTES_KEZEL)){
            echo json_encode(array(
                'hiba'  => 'Önnek nincs joga bejelentést kezelni!',
                'title' => 'Hiba'
            ));
            $weblap->leallit();
            exit();
        }
        
        // Státusz ellenőrzése
        if( $bejelentes['stat_id'] != BEJELENTES_STAT_KESZ_IG ) {
            echo json_encode(array(
                'hiba'  => 'Csak Kész, igazolás alatti bejelentések igazolhatóak!',
                'title' => 'Hiba'
            ));

            $weblap->leallit();
            exit();
        }
        
        // Részleg ellenőrzése
        $login = $adatbazis_kapcsolat->s2('reszleg_id', 'login', "`recid`='{$_SESSION[get_db_tabla().'login_recid']}'");
        $reszlegek = array($login['reszleg_id']);
        // Ha van hozzá joga, megnézzük a társrészlegeket
        if( _jog(JOG_BEJELENTES_TARSRESZLEG_BEJ_IGAZOL) ){
            $tarsreszlegek = $adatbazis_kapcsolat->s('reszleg_id', 'login_tarsreszleg', "`login_id`='{$_SESSION[get_db_tabla().'login_recid']}'");
            foreach($tarsreszlegek as $reszleg){
                array_push($reszlegek, $reszleg['reszleg_id']);
            }
        }
        // Ha adott ML részlege nincs benne a felhasználóhoz tartozó részlegek között, akkor hibaüzenet
        if(!in_array($bejelentes['usr_reszleg_id'], $reszlegek)){
            echo json_encode(array(
                'hiba'  => 'A bejelentés nem tartozik az Ön részlegéhez!',
                'title' => 'Hiba'
            ));
            $weblap->leallit();
            exit();
        }

        // Előbb csak kérdezünk, viszont az ellenőrzéseket addig is lefuttatjuk, derüljön ki ha baj van még a kérdés előtt!
        if( isset($_POST['csak_kerdezunk']) && $_POST['csak_kerdezunk'] != 'false' ) {
            echo json_encode(array(
                'hiba' => 'Ha folytatja, a bejelentés lezárt státuszra vált. Folytatja?',
                'gomb' => '<input class="gomb zold" value="Igen" onclick="bejelentesIgazolas( \''.$recid.'\' );" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        if( $bejelentes ) {
            if( !addStatusz( 'bejelentes_statusz', $recid, BEJELENTES_STAT_LEZART ) ) {
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
                    'title' => 'Hiba'
                ));
            }
        }
    }

    $weblap->leallit();
    exit();
}

// Bejelentés reklamáció elfogadása előtt a munkalapok kilistázása
if (isset($_POST['rekl_elfogad_ml_ok'])) {

    $munkalapok = $adatbazis_kapcsolat->s('munkalap_id', 'munkalap_kapcs', "`bejelentes_id`='{$_POST['recid']}'");
    $munkalapok = adatbazis2tomb($munkalapok, 'munkalap_id');
    if($munkalapok){

        $html = nl2('
                    <table class="csikos">
                    <tr>
                        <th>Munkalap szám</th>
                        <th>Szálloda</th>
                        <th>Létrehozva</th>
                        <th>Státusz</th>
                    </tr>
                    ');
        $munkalapok = $adatbazis_kapcsolat->s('recid ikt_szam telephely_id stat_id', 'munkalap', "`recid` IN (".implode(', ', $munkalapok).")");
        foreach($munkalapok as $munkalap){

            // Csak nyitott, folyamatban és az aktuális sátuszt kínáljuk fel
            $statusz = '<select class="rekl_elfogad_ml_stat" data-id="'.$munkalap['recid'].'">';
            foreach( $_stat_text['munkalap'] as $stat_id => $stat ){

                if( in_array($stat_id, array($munkalap['stat_id'], MUNKALAP_STAT_NYITOTT, MUNKALAP_STAT_HOZZARENDELT)) ){
                    $selected = '';
                    if( $stat_id==$munkalap['stat_id'] ){
                        $selected = ' selected';
                    }
                    $statusz .= '<option value="'.$stat_id.'"'.$selected.'>'.$stat['nev'].'</option>';
                }
            }
            $statusz .= '</select>';

            $html .= nl2('
                        <tr>
                            <td>'.$munkalap['ikt_szam'].'</td>
                            <td>'.getErtek('nev', 'telephely', 'id', $munkalap['telephely_id']).'</td>
                            <td>'.getStatText('munkalap', $munkalap['stat_id']).'</td>
                            <td>'.$statusz.'</td>
                        </tr>
                        ');
        }
        $html .= '</table>';

        echo $html;

    }


    $weblap->leallit();
    exit();
}

// Bejelentés reklamáció elfogadása
if( isset($_POST['bejelentesReklamacioElfogad']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {

        // Státusz ellenőrzés
        $bejelentes = $adatbazis_kapcsolat->s2('stat_id', 'bejelentes', "`recid` = '{$recid}'", '', '1');
        if( $bejelentes === false ) { $db_error = true; }
        if( $bejelentes['stat_id'] != BEJELENTES_STAT_VIZSG_ALATT ) {
            echo json_encode(array(
                'hiba'  => 'Csak igazolás alatti bejelentést lehet elfogadni!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzés
        if( !_jog(JOG_BEJELENTES_VIZSGAL) ) {
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
                'gomb' => '<input class="gomb zold" value="Igen" onclick="bejelentesReklamacioElfogad();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">'
            ));

            $weblap->leallit();
            exit();
        }

        // Státusz léptetés
        if( !addStatusz( 'bejelentes_statusz', $recid, BEJELENTES_STAT_FOLYAMATBAN, '', 'Reklamáció elfogadva' ) ) {
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

// Bejelentés reklamáció elfogadásánál munkalapok folytatása
if (isset($_POST['munkalapokFolytat'])) {

    $recid      = $_POST['recid'];
    $ml_statok  = json_decode($_POST['ml_statok']);

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {

        // Státusz ellenőrzés
        $bejelentes = $adatbazis_kapcsolat->s2('stat_id', 'bejelentes', "`recid` = '{$recid}'", '', '1');
        if( $bejelentes === false ) { $db_error = true; }
        if( $bejelentes['stat_id'] != BEJELENTES_STAT_VIZSG_ALATT ) {
            echo json_encode(array(
                'hiba'  => 'Csak igazolás alatti bejelentést lehet elfogadni!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzés
        if( !_jog(JOG_BEJELENTES_VIZSGAL) ) {
            echo json_encode(array(
                'hiba'  => 'Önnek nincs joga reklamációt elfogadni!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Munkalapok státusz léptetései
        $volt_stat_valtas = false;
        foreach($ml_statok as $ml_recid => $stat){
            $akt_ml = $adatbazis_kapcsolat->s2('stat_id', 'munkalap', "`recid`='{$ml_recid}'");
            if( $akt_ml['stat_id']!=$stat ){
                $volt_stat_valtas = true;
                if( !addStatusz( 'munkalap_statusz', $ml_recid, $stat, '', 'Bejelentés reklamáció után új státusz: '. getStatText('munkalap', $stat) ) ) {
                    $db_error = true;
                }

                // Ha nyitottra vagy folyamtbanra állítja a státuszt, akkor a mezők ürüljenek, ugyanúgy mint munkalap viszaadáskor
                if( $stat == MUNKALAP_STAT_NYITOTT || $stat == MUNKALAP_STAT_HOZZARENDELT ){

                    // Folyamatbanra állítva a személyzet telepített személyzet legyen
                    $szemelyzet_id = 0;
                    $visszaadva    = 1;
                    if( $stat == MUNKALAP_STAT_HOZZARENDELT ){
                        $szemelyzet_id = TELEPITETT_SZEMELYZET;
                        $visszaadva    = 0;
                    }

                    $sql = $adatbazis_kapcsolat -> u('munkalap',
                                                        array('visszaadva', 'szemelyzet_id', 'mobil_loginnev', 'szallito_id', 'mobil', 'jh_ert', 'varh_erk',            'update_usr'                           ),
                                                        array( $visszaadva, $szemelyzet_id,  '',                0,            '',      '',       '0000-00-00 00:00:00', $_SESSION[get_db_tabla().'teljes_nev'] ),
                                                        "`recid` = '{$ml_recid}'"
                                                    );
                    if( $sql === false ) { $db_error = true; }
                }
            }
        }

        // Ha egyik munkalap státusza sem változott akkor hibaüzenet
        if(!$volt_stat_valtas){
            echo json_encode(array(
                'hiba'  => 'Folytatáshoz legalább egy munkalap státuszát állítsa vissza!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Bejelentés státusz léptetése
        if( !addStatusz( 'bejelentes_statusz', $recid, BEJELENTES_STAT_FOLYAMATBAN, '', 'Reklamáció elfogadva' ) ) {
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

    $weblap->leallit();
    exit();
}

// Bejelentés reklamáció elutasítása
if( isset($_POST['bejelentesReklamacioElutasit']) ) {
    $recid = $_POST['recid'];
    $memo  = 'Reklamáció elutasítva:'."\r\n".$_POST['memo'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {

        // Státusz ellenőrzés
        $bejelentes = $adatbazis_kapcsolat->s2('stat_id', 'bejelentes', "`recid` = '{$recid}'", '', '1');
        if( $bejelentes === false ) { $db_error = true; }
        if( $bejelentes['stat_id'] != BEJELENTES_STAT_VIZSG_ALATT ) {
            echo json_encode(array(
                'hiba'  => 'Csak igazolás alatti bejelentést lehet elutasítani!',
                'title' => 'Hiba',
            ));

            $weblap->leallit();
            exit();
        }

        // Jog ellenőrzés
        if( !_jog(JOG_BEJELENTES_VIZSGAL) ) {
            echo json_encode(array(
                'hiba'  => 'Önnek nincs joga reklamációt elutasítani!',
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
        if( !addStatusz( 'bejelentes_statusz', $recid, BEJELENTES_STAT_LEZART, '', $memo ) ) {
            $db_error = true;
        }

        // Megjegyzés hozzáadása
        if(addMegj( 'bejelentes', $recid, $memo )===false){
            $db_error = true;
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            $hiba = '';
            $valasz = level_felkeszit_es_kuld('bejelentes', $recid, ERT_BEJ_REKL_ELUTASITKOR, 'bejelentesReklElutasit', 'bejelento', '<br>'.nl2br2($memo).'<br>');
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

// CSV generálása
if (isset($_GET['csv'])) {

    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=$megnevezes.csv");
    header("Pragma: no-cache");
    header("Expires: 0");

    $mibol   = get_web_charset();
    $mibe    = 'windows-1250';

    if( !is_dir('export') ) {
        mkdir('export');
    }

    if( !is_dir('export/csv') ) {
        mkdir('export/csv');
    }

    // Open the output stream
    $open = fopen('php://output', 'w');
    fwrite($open, "sep=;\n");
    ob_start();

    if($open){

        $lekerdezes_fromtol = substr($_SESSION['akt_sql'], strpos($_SESSION['akt_sql'], 'FROM'));

        if( $_SESSION['nyomtatas_ids'] ) {
            if( strstr($_SESSION['akt_sql'], 'WHERE') ) {
                $lekerdezes_fromtol = str_replace('WHERE', "WHERE `".get_db_tabla()."bejelentes`.`recid` IN (".$_SESSION['nyomtatas_ids'].") AND ", $lekerdezes_fromtol);
            } else {
                $lekerdezes_fromtol = str_replace('ORDER', "WHERE `".get_db_tabla()."bejelentes`.`recid` IN (".$_SESSION['nyomtatas_ids'].") ORDER", $lekerdezes_fromtol);
            }
        }
  
        // Ha van $_SESSION['akt_sql_order'], akkor eszerint rendezünk, tehát a $lekerdezes_fromtol-t elvágjuk az Order-nél
        if( $_SESSION['akt_sql_order'] ){
            $hossz = strpos($lekerdezes_fromtol, 'ORDER');
            if( $hossz ) {
                $lekerdezes_fromtol = substr($lekerdezes_fromtol, 0, $hossz);
            } else {
                $lekerdezes_fromtol = substr($lekerdezes_fromtol, 0);
            }
        }
        // Ha nincs FROM rész, akkor azt még beletesszük
        if( !$lekerdezes_fromtol ) {
            $lekerdezes_fromtol = ' FROM `'.get_db_tabla().$megnevezes.'` ';
        }

        $sql = 'SELECT
                    `ikt_szam` as \'Bejelentés szám\',
                    `'.get_db_tabla().$megnevezes.'`.`telephely_id` as \'Szálloda\',
                    `hiba_id` as \'Bejelentett hiba\',
                    `hiba_text` as \'Hiba leírása\',
                    `obj_txt` as \'Helyiség\',
                    `helyiseg_text` as \'Hely leírása\',
                    `gep_id` as \'Gépnév\',
                    `stat_id` as \'Státusz\',
                    `'.get_db_tabla().$megnevezes.'`.`insert_dt` as \'Bejelentés dátuma\'
                    '.$lekerdezes_fromtol.$_SESSION['akt_sql_order'];

        $query      = mysqli_query($db_kapcs, $sql);

        $csvhead    = array();
        while($fejlec_nevek = mysqli_fetch_field($query)){
            $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
        }
        fputcsv($open, $csvhead, ';');

        $i = 0;
        while( $sorok = mysqli_fetch_assoc($query) ){

            $csvcont = array();
            foreach( $sorok as $kulcs => $sor ) {
                if($kulcs=='Szálloda'){
                    $sor = getSzallodaNev($sor);
                }
                if($kulcs=='Bejelentett hiba'){
                    $sor = getErtekLista('', '', $sor);
                }
                if( $kulcs == 'Gépnév' ) {
                    $sor = $adatbazis_kapcsolat->s2('nev', 'eszkozkezeles', "`recid`='{$sor}'");
                    $sor = $sor['nev'];
                }
                if( $kulcs == 'Státusz' ) {
                    $sor = getStatText('bejelentes', $sor);
                }
                if( $kulcs == 'Bejelentés dátuma' ) {
                    $sor = datum_kiir($sor);
                }
                
                // Lehet nem volna elég 1-szer sőt kétszer sem ráereszteni a htmlspecialchars_decode-ot
                $sor = htmlspecialchars_decode( $sor );
                while(strpos($sor, '&amp;')!==false || strpos($sor, '&quot;')!==false ){
                    $sor = htmlspecialchars_decode( $sor );
                }
                $csvcont[] = iconv($mibol, $mibe.'//TRANSLIT', strip_tags( $sor ) );
            }

            fputcsv($open, $csvcont, ';');

            // 100 soronként kiírtatunk
            if ($i % 100 == 0) {
                flush();
            }

            $i++;
        }
    }

    // Get the contents of the output buffer
    $string = ob_get_clean();
    fclose($fh);

    // Stream the CSV data
    exit($string);

    $weblap->leallit();
    exit();
}

// Tartalom
if( !$_GET['ajax'] ) {
    require_once('header.php');
}
require_once('selectek.php');

    if( !$_GET['ajax'] ) {
        echo '<div id="szamlaBetolt" data-recid="'.$_GET['id'].'" data-page="'.str_replace('.php', '', basename(self())).'">';
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

    if( isset($_GET['modosit']) || isset($_GET['tortenet']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) || isset($_GET['munkalapok']) ) {
        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'tortenet'      => 'Történet',
            'munkalapok'    => 'Munkalapok',
            'dokumentumok'  => 'Dokumentumok'.$dok_db,
            'megjegyzesek'  => 'Megjegyzések'.$megj_db
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

    // Gombok kirajzolása minden oldalra
    $gombok = '';
    if( isset($_GET['uj']) ) {
        // Gombok
        $gombok .= nl2('
            <div class="form_kuldes">
                <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
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

    // Nem új feltöltés (de minden más fül...)
    } else {

        // Js, a törlés gombhoz
	    $gombok .= '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';

        // Nyitott státuszú bejelentés elutasítása
        if( $sor['stat_id']==BEJELENTES_STAT_NYITOTT && _jog(JOG_BEJELENTES_ELUTASIT) ) {
//Korábban rákérdeztünk, de ez kivéve 2018.02.14 velentin napkor :)
//<script> gomb_b_elut = \'<input type="button" class="gomb zold" onclick="csoportMuvelet(\\\'elutasitas\\\');" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\';</script>
//<input type="button" class="gomb modositaskor_rejt" value="Bejelentés elutasítása" title="Bejelentés elutasítása" onclick="alert2(\'Biztosan elutasítja a bejelentést?\', gomb_b_elut);">

            $gombok .= nl2('
                            <input type="button" class="gomb modositaskor_rejt" value="Bejelentés elutasítása" title="Bejelentés elutasítása" onclick="csoportMuvelet(\'elutasitas\');">
                        ');
        }

        // Reklamáció elfogad/utasít
        if( $sor['stat_id']==BEJELENTES_STAT_VIZSG_ALATT && _jog(JOG_BEJELENTES_VIZSGAL) ) {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Reklamáció elfogadása" onclick="csoportMuvelet(\'bejelentesReklamacioElfogad\');"> ';
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Reklamáció elutasítása" onclick="csoportMuvelet(\'bejelentesReklamacioElutasit\');"> ';
        }

        // Törlés gomb hibaüzenetei
        // Csak nyitott és folyamatban státusz törölhető
        $torolheto_statuszok = array(BEJELENTES_STAT_NYITOTT/*, BEJELENTES_STAT_FOLYAMATBAN*/);
        if( !in_array($sor['stat_id'], $torolheto_statuszok) ) {
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Ebben a státuszban nem törölhető a bejelentés!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        // Csak törlés joggal törölhető
        } else if( !_jog(JOG_BEJELENTES_TORLESE) ) {
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Önnek nincs joga bejelentést törölni!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        } else {

            $obj = $adatbazis_kapcsolat -> s2('nev telephely_id', 'obj', "`recid`='{$sor['obj_id']}'");

            $szalloda = $adatbazis_kapcsolat -> s2('nev', 'telephely', "`id`='{$obj['telephely_id']}'");

            $gep_nev = '';
            if( $sor['gep_id'] ) {
                $gep = $adatbazis_kapcsolat -> s2('nev', 'eszkozkezeles', "`recid`='{$sor['gep_id']}'");
                if( $gep['nev'] ) {
                    $gep_nev = '<br>Gép: '.addslashes($gep['nev']);
                }
            }

            // Hiba felirat, csak ha van hiba
            if( $sor['hiba_text'] ) {
                $sor['hiba_text'] = '<br>Hiba: '.addslashes($sor['hiba_text']);
            }
            $gombok .= nl2('
                <script>var torol_kerdes = \'
                    <div class="l">
                        Bejelentés szám: '.$sor['ikt_szam'].'
                        <br>Szálloda: '.addslashes($szalloda['nev']).'
                        <br>Helyiség: '.addslashes($obj['nev']).'
                        '.$sor['hiba_text'].'
                        '.$gep_nev.'
                    </div>
                    <br>Biztosan törli ezt a '.$szoveg.'?
                \';</script><input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(torol_kerdes, gomb'.$_GET['id'].')" value="Töröl" title="Töröl">
            ');
        }

        // Szerkeszthető egy bejelentés, ha nyitott és van kezelés jogunk
        $felh         = $adatbazis_kapcsolat->s2('reszleg_id', 'login', "`nev`='{$_SESSION[get_db_tabla().'user']}'");
        $telephely    = $adatbazis_kapcsolat->s2('nev modell', 'telephely', "`id`='{$sor['telephely_id']}'");
        $munkalap_idk = $adatbazis_kapcsolat->s('munkalap_id', 'munkalap_kapcs', "`bejelentes_id`='{$_GET['id']}'");
        $munkalap_idk = adatbazis2tomb($munkalap_idk, 'munkalap_id');
        $van_nem_hozzarendelt = $adatbazis_kapcsolat->s2('recid', 'munkalap', "`recid` IN (".implode(', ', $munkalap_idk).") AND `stat_id`!='".MUNKALAP_STAT_HOZZARENDELT."' AND `stat_id`!='".MUNKALAP_STAT_NYITOTT."'");
        if( isset($_GET['modosit'])                                         &&      // Más fülek alatt ne legyen mentés gomb
            (                                                                       // Nyitott státusz esetén
                $sor['stat_id']==BEJELENTES_STAT_NYITOTT                    &&
                (
                    (   _jog(JOG_BEJELENTES_KEZEL) &&                               // Ha saját néven tudok bejelenteni
                        $sor['usr_reszleg_id'] == $felh['reszleg_id']               // És a saját részlegemhez tartozik
                    )                                                       ||
                    _jog(JOG_BEJELENTES_MAS_NEVEBEN)                                // Vagy ha más nevében is tudok bejelenteni
                )
            )
            /*||                                                                      // VAGY Folyamatban státusz esetén is szerkeszthető, ha egyszállodás, és munkalapja nincs felvéve
            (
                $sor['stat_id']==BEJELENTES_STAT_FOLYAMATBAN    &&
                !$telephely['modell']                           &&
                !$van_nem_hozzarendelt
            )*/
        ) {
                $gombok = nl2('
                    <div class="form_kuldes">
                        <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
                        <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
                    </div>
                ').$gombok;
        }

        // Igazolás és Elutasítás gombok
        if( $sor['stat_id']==BEJELENTES_STAT_KESZ_IG && _jog(JOG_BEJELENTES_KEZEL) ) {

            // Részleg ellenőrzése
            $login = $adatbazis_kapcsolat->s2('reszleg_id', 'login', "`recid`='{$_SESSION[get_db_tabla().'login_recid']}'");
            $reszlegek = array($login['reszleg_id']);
            // Ha van hozzá joga, megnézzük a társrészlegeket
            if( _jog(JOG_BEJELENTES_TARSRESZLEG_BEJ_IGAZOL) ) {
                $tarsreszlegek = $adatbazis_kapcsolat->s('reszleg_id', 'login_tarsreszleg', "`login_id`='{$_SESSION[get_db_tabla().'login_recid']}'");
                foreach ( $tarsreszlegek as $reszleg ) {
                    array_push($reszlegek, $reszleg['reszleg_id']);
                }
            }

            // Ha adott Bejelentés részlege benne van a felhasználóhoz tartozó részlegek között, akkor mutatjuk csak meg ezeket a gombokat
            if( in_array($sor['usr_reszleg_id'], $reszlegek) ) {

                // Ideiglenesen egyszállodás modell esetén nem mutatjuk az elutasítás gombot 2018.06.06
                $szalloda = $adatbazis_kapcsolat -> s2('modell', 'telephely', "`id`='{$sor['telephely_id']}'");
                $elutasit_gomb = '';
                if( $szalloda['modell'] ) {
                    $elutasit_gomb = '<input type="button" class="gomb modositaskor_rejt" value="Igazolás elutasítása" onclick="csoportMuvelet(\'bejelentesElutasit\');">';
                }
                $gombok .= nl2('
                    <div id="kmCont">
                        <input type="button" class="gomb modositaskor_rejt" value="Igazolás" onclick="bejelentesIgazolas( \''.$sor['recid'].'\', \'Csak kérdezünk még\' );">
                        '.$elutasit_gomb.'
                    </div>
                ');
                
            }
        }

        // Munkalap készítése
        if( ($sor['stat_id']==BEJELENTES_STAT_NYITOTT || $sor['stat_id']==BEJELENTES_STAT_FOLYAMATBAN) && _jog(JOG_MUNKALAP_KESZITHET) ) {
            
            // Ha egyszállodás, nyitott, és van munkalapja, akkor nem készíthet újabb munkalapot
            $keszithet_ml_ot = true;
            if( !$telephely['modell'] && $sor['stat_id'] == BEJELENTES_STAT_NYITOTT ) {
                $van_kapcs = $adatbazis_kapcsolat->s2('munkalap_id', 'munkalap_kapcs', "`bejelentes_id`='{$_GET['id']}'", '', '1');
                $letezik_munkalap = $adatbazis_kapcsolat->s2('recid', 'munkalap', "`recid`='{$van_kapcs['munkalap_id']}'");
                if( $letezik_munkalap ) {
                    $keszithet_ml_ot = false;
                }
            }
            
            if( $keszithet_ml_ot ) {
                $gombok .= nl2('
                    <input type="button" id="munkalapEgyeni" class="gomb modositaskor_rejt" value="Munkalap készítése" onclick="munkalapKeszit(\'csak kérdezünk még\')" title="Munkalap készítése">
                ');
            } else {
                $gombok .= nl2('
                    <input type="button" id="munkalapEgyeni" class="gomb modositaskor_rejt" value="Munkalap készítése" onclick="alert2(\'Bejelentés szám: '.$sor['ikt_szam'].'<br>Hiba helye: '.$telephely['nev'].'<br>Bejelentett hiba: '. getErtek('info', 'erteklista', 'recid', $sor['hiba_id']).'<br><br>Ehhez a bejelentéshez már van munkalap.<br>Nem lehet újat készíteni!\', \'\', \'Hiba\')" title="Munkalap készítése">
                ');
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
    }


    $js_submit_elott = 'return form_ell_admin();';

	// Új bejelentés felvitele
	if(isset($_GET['uj'])){

	    // FORM
        // Újként szerkesztéskor feltöltjük a mezők tartalmát
		if($_GET['ujkent']){
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

			foreach($akt_tomb as $ertek){
				$sor[$ertek['name']] = $_SESSION[$ertek['name']];
			}

            // A Bejelentést rögzítő adatai mindenképpen a beloginolt felhasználó lesz
            $login = $adatbazis_kapcsolat->s2('teljes_nev beosztas_id reszleg_id usertel usermail', 'login', "`recid`='{$_SESSION[get_db_tabla().'login_recid']}'");
            $_SESSION['usr_man_nev']         = $login['teljes_nev'];
            $_SESSION['usr_man_beosztas_id'] = $login['beosztas_id'];
            $_SESSION['usr_man_reszleg_id']  = $login['reszleg_id'];
            $_SESSION['usr_man_telefon']     = $login['usertel'];
            $_SESSION['usr_man_email']       = $login['usermail'];
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

        // Esemenyek.php bejelentések tömb előtti részből mintázva
        if( $_GET['bejelento'] ) {
            $bejelento = $adatbazis_kapcsolat->s2('teljes_nev beosztas_id reszleg_id usertel usermail', 'login', "`nev`='{$_GET['bejelento']}'");
        // Módosítánál a GET['id']-ból visszaszámolva
        } else {
            $bejelento = $adatbazis_kapcsolat->s2('teljes_nev beosztas_id reszleg_id usertel usermail', 'login', "`recid`='{$_SESSION[get_db_tabla().'login_recid']}'");
        }
        $bejelentest_rogziti = $adatbazis_kapcsolat->s2('teljes_nev beosztas_id reszleg_id usertel usermail', 'login', 'recid='.$_SESSION[get_db_tabla().'login_recid']);
        // Ha manuális kitöltés szeretnénk, akkor semmiképp ne legyen benne a bejelentő adata
        if( isset($_GET['manualisan']) ) {
            $bejelento = '';
        }
        $sor['usr_nev']                     = $bejelento['teljes_nev'];
        $sor['usr_beosztas_id']             = $bejelento['beosztas_id'];
        $sor['usr_beosztas_id_autocomp']    = erteklista(ERTEKLISTA_BEOSZTAS, $bejelento['beosztas_id']);
        $sor['usr_reszleg_id']              = $bejelento['reszleg_id'];
        $sor['usr_reszleg_id_autocomp']     = erteklista(ERTEKLISTA_RESZLEG, $bejelento['reszleg_id']);
        $sor['usr_telefon']                 = $bejelento['usertel'];
        $sor['usr_email']                   = $bejelento['usermail'];
        $sor['usr_man_nev']                 = $bejelentest_rogziti['teljes_nev'];
        $sor['usr_man_beosztas_id']         = $bejelentest_rogziti['beosztas_id'];
        $sor['usr_man_beosztas_id_autocomp']= erteklista(ERTEKLISTA_BEOSZTAS, $bejelentest_rogziti['beosztas_id']);
        $sor['usr_man_reszleg_id']          = $bejelentest_rogziti['reszleg_id'];
        $sor['usr_man_reszleg_id_autocomp'] = erteklista(ERTEKLISTA_RESZLEG, $bejelentest_rogziti['reszleg_id']);
        $sor['usr_man_telefon']             = $bejelentest_rogziti['usertel'];
        $sor['usr_man_email']               = $bejelentest_rogziti['usermail'];
        if ($_GET['telephely']) {
            $sor['telephely_id'] = $_GET['telephely'];
        }
        // Ha csak egy szálloda tartozik adott felhasználóhoz, akkor a szálloda választónak adott az értéke, ezt adjuk is át a formAdatoknak
        $szallodak = get_akt_login_szallodai();
        if( count($szallodak)==1 ){
            $sor['telephely_id'] = $szallodak[0];
        }

        $json_sor = array();
        foreach($sor as $kulcs => $elem){
            $json_sor[$kulcs] = formAdatok_replace($elem);
        }
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

		// Ez elkészíti a $mezok változót
		require('switch.php');

        $bejbejnev = '';
        if( $_GET['bejelento'] ) {
            $bejbejnev = $_GET['bejelento'];
        }

        $mezok .= '<input type="hidden" id="masnevebenFeltolt" name="masnevebenFeltolt" value="'.$bejbejnev.'">';

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

	// Bejelentés módosítása
    } elseif( isset($_GET['modosit']) ) {

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
        }

	    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, 'recid='.$_GET['id']);
        $sor['hiba_text'] = str_replace(array('	'), array(' '), $sor['hiba_text']);
        $sor['hiba_text'] = preg_replace("/[[:blank:]]+/", ' ', $sor['hiba_text']);

        // Speciális karakterek szűrése
        $mit   = specialis_karakterek_mit();
        $mivel = specialis_karakterek_mivel();
        $sor['hiba_text']       = str_replace($mit, $mivel, $sor['hiba_text']);
        $sor['helyiseg_text']   = str_replace($mit, $mivel, $sor['helyiseg_text']);
        
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
        foreach($sor as $kulcs => $elem){
            $json_sor[$kulcs] = formAdatok_replace($elem);
        }
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

        // Duplákáláskor feltett kérdés
        $telephely = $adatbazis_kapcsolat->s2('nev', 'telephely', 'id='.$sor['telephely_id'], '', '1');
        $telephely = $telephely['nev'];
        $szamla_html .= nl2('
            <script>
                duplikalGomb  = "<p class=\"c\"><input class=\"gomb zold\" type=\"button\" value=\"Igen\" onclick=\"szurkit(); location.href = \''.basename(self()).'?uj&amp;ujkent='.$_GET['id'].'\'; \"><input class=\"gomb piros\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
                duplikaKerdes = \'Azonosító: '.$sor['ikt_szam'].'<br>Szálloda: '.$telephely.'<br><br>Biztos, hogy duplikálja?\';
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
            array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok,    $sor['update_dt']  ),
            fajlbol_olvas('../sablon/admin/form_modosit.html', true)
        );

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom'  => $szamla_html,
                'link'      => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
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

	// Munkalapok fül
    } elseif( isset($_GET['munkalapok']) ) {

        $szamla_html .= nl2('
            <div class="dt naplo kijCont" data-tabla="munkalap_kapcs" data-mellek="munkalapok">
                <div class="dtr nFejlec">
                    <div class="dtc">Munkalap szám</div>
                    <div class="dtc">Szálloda</div>
                    <div class="dtc">Létrehozva</div>
                    <div class="dtc">Státusz</div>
                </div>
        ');

        $munkalap_kapcsok = $adatbazis_kapcsolat->s('recid munkalap_id', 'munkalap_kapcs', "`bejelentes_id` = '{$_GET['id']}'");
        if( $munkalap_kapcsok ) {
            $i = 1;
            foreach($munkalap_kapcsok as $munkalap_kapcs) {

                // Csak akkor látszik a munkalap, ha az nyitott
                $sor = $adatbazis_kapcsolat->s2('recid ikt_szam telephely_id obj_id insert_dt stat_id', 'munkalap', "`recid` = '".$munkalap_kapcs['munkalap_id']."'", '', '1');
                if( $sor ) {

                    // Telephely
                    $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$sor['telephely_id'], '', '1');
                    $telephely = $telephely[0]['nev'];

                    // Helység
                    $helyseg = $adatbazis_kapcsolat->s('nev', 'obj', 'recid='.$sor['obj_id'], '', '1');
                    $helyseg = $helyseg[0]['nev'];

                    $ikt_szam = '<a href="munkalap.php?id=x&amp;mezo=ikt_szam&amp;op=equal&amp;felt='.$sor['ikt_szam'].'" target="_blank">'.$sor['ikt_szam'].'</a>';
                    if( !_jog(JOG_MUNKALAP_MEGTEKINT) && !_jog(JOG_MUNKALAP_MEGTEKINT_SZALL) && !_jog(JOG_MUNKALAP_MEGTEKINT_FELADAT) ){
                        $ikt_szam = $sor['ikt_szam'];
                    }

                    $szamla_html .= nl2('
                        <div id="naploSor-'.$sor['recid'].'" class="dtr nSor kijSor" data-id="'.$sor['recid'].'" data-i="'.$i.'">
                            <div class="dtc">'.$ikt_szam.'</div>
                            <div class="dtc">'.$telephely.' - '.$helyseg.'</div>
                            <div class="dtc">'.datum_kiir($sor['insert_dt']).'</div>
                            <div class="dtc">'.getStatText('munkalap', $sor['stat_id']).'</div>
                        </div>
                    ');
                }

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


    // Dokumentumok fül
    } elseif( isset($_GET['dokumentumok']) ) {

        if( $tobb_kep ) {
            require_once('tobb_kep.php');
        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }

    // Megjegyzés
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

        $sorok = $adatbazis_kapcsolat->s('recid megj szerepkor insert_usr insert_dt', 'megjegyzes', "`id` = '".$_GET['id']."' AND `tabla` = '".str_replace('.php', '', basename(self()))."'", 'recid desc');
        if( $sorok ) {
            $i = 1;
            foreach($sorok as $sor) {
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
                'tartalom' => $szamla_html.$gombok
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
