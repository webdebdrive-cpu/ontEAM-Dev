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
$formazatlan_szam       = array('nev', 'csz', 'tel', 'fax', 'mobil');          //Azok az oszlopneveket sorold fel, melyek numerikus értéket is tartalmazhatnak, de nem szeretnéd, ha lista nézetben ezres csoportra bontaná

$szamla_html = '';

// Több kép menedzseléséhez
$tobb_kep = true; // Ha true, akkor js/jquery-ui.js, js/admin_sorrendez.js aktivál és minify!, admin/tobb_kep.php aktivál
if($tobb_kep){
    function get_tobbkep_darab()    { return 10;        } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '';        } // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;        } // MB-ban
    function get_tobbkep_oszlopnev(){                     // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return 'bejelentes_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!
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
    if( $bejelentesek ) {
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
            'gomb' => '<p class="c"><input class="gomb zold" type="button" value="Igen" onclick="bejelentes_mehet = true; $(\'#szerk_form\').submit();"><input class="gomb piros" type="button" value="Nem" onclick="alert2()"></p>'
        ));

        $weblap->leallit();
        exit();
    }
    echo json_encode(array('siker' => 1));

    $weblap->leallit();
    exit();
}

// Bejelentés elutasítása
if( isset($_POST['bejelentesElutasit']) ) {
    
    $recid          = $_POST['recid'];
    $validate       = $_POST['validate'];
    $elutasitas_id  = $_POST['elutasitas_id'];
    $erteklista     = $adatbazis_kapcsolat->s2('info', 'erteklista', "`recid`='{$elutasitas_id}'");
    $memo           = $erteklista['info']."\r\n".$_POST['memo'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        $bejelentes = $adatbazis_kapcsolat->s2('stat_id', 'bejelentes', "`recid` = '{$recid}'", '', '1');
        if( $bejelentes === false ) { $db_error = true; }

        if( $bejelentes['stat_id'] != BEJELENTES_STAT_KESZ_IG ) {
            echo json_encode(array(
                'hiba' => 'Csak Kész, igazolás alatti bejelentések utasíthatók el!',
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

        // Elutasítás
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

// Bejelentés igazolása
if( isset($_POST['bejelentesIgazolas']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        $bejelentes = $adatbazis_kapcsolat->s2('stat_id', 'bejelentes', "`recid` = '{$recid}'", '', '1');
        if( $bejelentes === false ) { $db_error = true; }

        if( $bejelentes ) {

            if( $bejelentes['stat_id'] != BEJELENTES_STAT_KESZ_IG ) {
                echo json_encode(array(
                    'hiba' => 'Csak Kész, igazolás alatti bejelentések igazolhatóak!',
                ));

                $weblap->leallit();
                exit();
            }
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
                    'hiba' => tranzakcioHiba()
                ));
            }
        }
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
        if( !addStatusz( 'bejelentes_statusz', $recid, BEJELENTES_STAT_FOLYAMATBAN ) ) {
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


// Bejelentés reklamáció elutasítása
if( isset($_POST['bejelentesReklamacioElutasit']) ) {
    $recid = $_POST['recid'];
    $memo  = $_POST['memo'];

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
    ob_start();

    if($open){

        $lekerdezes_fromtol = substr($_SESSION['akt_sql'], strpos($_SESSION['akt_sql'], 'FROM'));

        $sql = 'SELECT
                                                    `ikt_szam` as \'Bejelentés szám\',
                                                    `'.get_db_tabla().'bejelentes`.`telephely_id` as \'Szálloda\',
                                                    `hiba_id` as \'Bejelentett hiba\',
                                                    `hiba_text` as \'Hiba leírása\',
                                                    `obj_id` as \'Helyiség\',
                                                    `helyiseg_text` as \'Hely leírása\',
                                                    `gep_id` as \'Gépnév\',
                                                    `stat_id` as \'Státusz\'
                                                    '.$lekerdezes_fromtol;

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
                    $sor = $adatbazis_kapcsolat->s('nev', 'telephely', "`id`='{$sor}'");
                    $sor = $sor[0]['nev'];
                }
                if($kulcs=='Bejelentett hiba'){
                    $sor = $adatbazis_kapcsolat->s('info', 'erteklista', "`recid`='{$sor}'");
                    $sor = $sor[0]['info'];
                }
                if($kulcs=='Helyiség'){
                    $sor = $adatbazis_kapcsolat->s('nev', 'obj', "`recid`='{$sor}'");
                    $sor = $sor[0]['nev'];
                }
                if($kulcs=='Gépnév'){
                    $sor = $adatbazis_kapcsolat->s('nev', 'eszkozkezeles', "`recid`='{$sor}'");
                    $sor = $sor[0]['nev'];
                }
                $csvcont[] = iconv2($mibol, $mibe, strip_tags( htmlspecialchars_decode( $sor ) ) );
            }

            fputcsv($open, $csvcont, ';');

            //100 soronként kiírtatunk
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
                <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="$(\'#szerk_form\').submit()">
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

    // Nem új feltöltés (de minden más fül...)
    } else {

        $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, 'recid='.$_GET['id']);

        // Js, a törlés gombhoz
	    $gombok .= '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';

        // Nyitott státuszú bejelentés elutasítása
        if( $sor['stat_id']==BEJELENTES_STAT_NYITOTT && _jog(JOG_BEJELENTES_ELUTASIT) ) {
            $gombok .= nl2('
                            <script> gomb_b_elut = \'<input type="button" class="gomb zold" onclick="csoportMuvelet(\\\'elutasitas\\\');" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">\';</script>
                            <input type="button" class="gomb modositaskor_rejt" value="Bejelentés elutasítása" title="Bejelentés elutasítása" onclick="alert2(\'Biztosan elutasítja a bejelentést?\', gomb_b_elut);">
                        ');
        }

        // Reklamációk
        if( $sor['stat_id']==BEJELENTES_STAT_VIZSG_ALATT && _jog(JOG_BEJELENTES_VIZSGAL) ) {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Reklamáció elfogadása" onclick="bejelentesReklamacioElfogad(\'csak kérdezünk még\');"> ';
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Reklamáció elutasítása" onclick="csoportMuvelet(\'bejelentesReklamacioElutasit\');"> ';
        }

        // Törlés gomb hibaüzenetei
        // Csak nyitott státuszú törölhető
        if($sor['stat_id']!=BEJELENTES_STAT_NYITOTT) {
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Csak nyitott státuszú bejelentés törölhető!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        // Csak törlés joggal törölhető
        } else if( !_jog(JOG_BEJELENTES_TORLESE) ) {
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Önnek nincs joga bejelentést törölni!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        } else {

            $obj = $adatbazis_kapcsolat -> s2('nev telephely_id', 'obj', "`recid`='{$sor['obj_id']}'");

            $szalloda = $adatbazis_kapcsolat -> s2('nev', 'telephely', "`id`='{$obj['telephely_id']}'");

            $gep_nev = '';
            if($sor['gep_id']){
                $gep = $adatbazis_kapcsolat -> s2('nev', 'eszkozkezeles', "`recid`='{$sor['gep_id']}'");
                if($gep['nev']){
                    $gep_nev = '<br>Gép: '.addslashes($gep['nev']);
                }
            }

            // Hiba felirat, csak ha van hiba
            if($sor['hiba_text']){
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
        if( isset($_GET['modosit'])                                         &&  // Más fülek alatt ne legyen mentés gomb
            $sor['stat_id']==BEJELENTES_STAT_NYITOTT                        &&  // Nyitott
            (_jog(JOG_BEJELENTES_KEZEL) || _jog(JOG_BEJELENTES_MAS_NEVEBEN))    // Ki-ki úgy is csak a saját listáját látja majd
        ) {
                $gombok = nl2('
                    <div class="form_kuldes">
                        <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="$(\'#szerk_form\').submit()">
                        <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="formModositva=false; location.reload();">
                    </div>
                ').$gombok;
        }

        // Igazolás és Elutasítás gombok
        if( $sor['stat_id']==BEJELENTES_STAT_KESZ_IG && _jog(JOG_BEJELENTES_KEZEL) ) {
            $gombok .= nl2('
                <div id="kmCont">
                    <input type="button" class="gomb modositaskor_rejt" value="Igazolás" onclick="bejelentesIgazolas( \''.$sor['recid'].'\', \'Csak kérdezünk még\' );">
                    <input type="button" class="gomb modositaskor_rejt" value="Igazolás elutasítása" onclick="csoportMuvelet(\'bejelentesElutasit\');">
                </div>
            ');
        }

        // Munkalap készítése
        if( ($sor['stat_id']==BEJELENTES_STAT_NYITOTT || $sor['stat_id']==BEJELENTES_STAT_FOLYAMATBAN) && _jog(JOG_MUNKALAP_KESZITHET) ) {
            $gombok .= nl2('
                <input type="button" id="munkalapEgyeni" class="gomb modositaskor_rejt" value="Munkalap készítése" onclick="munkalapKeszit(\'csak kérdezünk még\')" title="Munkalap készítése">
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

	// Új bejelentés felvitele
	if(isset($_GET['uj'])){

        // NAVIGÁCIÓ
        $navi_sablon  = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit     = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel   = array( $navi,             '',         $gyk2 );
        $szamla_html .= str_replace($navi_mit, $navi_mivel, $navi_sablon);

	    // HIBAüZENETEK
	    require_once('info.php');

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

        // Ckeditort csak akkor húzzuk be, ha kell
        if(is_file($ckeditor_js)){
            $ckeditor = '<script src="'.$ckeditor_js.'"></script>';
        }

	    $form_sablon = fajlbol_olvas('../sablon/admin/form_uj.html', true);

        // Javascipt-es ellenőrzéshez
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
        $sor['usr_beosztas_id_autocomp']    = erteklista(18, $bejelento['beosztas_id']);
        $sor['usr_reszleg_id']              = $bejelento['reszleg_id'];
        $sor['usr_reszleg_id_autocomp']     = erteklista(17, $bejelento['reszleg_id']);
        $sor['usr_telefon']                 = $bejelento['usertel'];
        $sor['usr_email']                   = $bejelento['usermail'];
        $sor['usr_man_nev']                 = $bejelentest_rogziti['teljes_nev'];
        $sor['usr_man_beosztas_id']         = $bejelentest_rogziti['beosztas_id'];
        $sor['usr_man_beosztas_id_autocomp']= erteklista(18, $bejelentest_rogziti['beosztas_id']);
        $sor['usr_man_reszleg_id']          = $bejelentest_rogziti['reszleg_id'];
        $sor['usr_man_reszleg_id_autocomp'] = erteklista(17, $bejelentest_rogziti['reszleg_id']);
        $sor['usr_man_telefon']             = $bejelentest_rogziti['usertel'];
        $sor['usr_man_email']               = $bejelentest_rogziti['usermail'];
        if ($_GET['telephely']) {
            $sor['telephely_id'] = $_GET['telephely'];
        }
        
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($sor).'\'); </script>';

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
	}elseif(isset($_GET['modosit'])) {

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
        }

	    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, 'recid='.$_GET['id']);

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
            $json_sor[$kulcs] = nl2(str_replace(array("\r\n", "'"), array("#UJSOR#", "#APOSZTROF#"), $elem));
        }
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

        if($id_latszik){
            $navi = '<b>#'.$_GET['id'].'</b> '.$navi;
        }

        // Duplákáláskor feltett kérdés
        $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$sor['telephely_id'], '', '1');
        $telephely = $telephely[0]['nev'];
        $szamla_html .= nl2('
            <script>
                duplikalGomb  = "<p class=\"c\"><input class=\"gomb zold\" type=\"button\" value=\"Igen\" onclick=\"location.href = \''.basename(self()).'?uj&amp;ujkent='.$_GET['id'].'\'; \"><input class=\"gomb piros\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
                duplikaKerdes = \'Azonosító: '.$sor['ikt_szam'].'<br>Szálloda: '.$telephely.'<br><br>Biztos, hogy duplikálja?\';
            </script>
        ');

        // NAVIGÁCIÓ
        $navi_sablon = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit    = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel  = array( $navi,             '',         $gyk2 );
        // echo str_replace($navi_mit, $navi_mivel, $navi_sablon);

	    // HIBAüZENETEK
        require_once('info.php');

	    // FORM
	    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

		// Form actionja
		$action = self().get_get(array('errnum','kephiba','siker'));

        // Ckeditort csak akkor húzzuk be, ha kell
        if(is_file($ckeditor_js)){
            $ckeditor = '<script src="'.$ckeditor_js.'"></script>';
        }

		// Ez elkészíti a $mezok változót
		require('switch.php');

        $jobb_oldal = '';

	    $szamla_html .= str_replace(
            array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}'    ),
            array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok       ),
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
    }elseif(isset($_GET['tortenet'])) {

        $szamla_html .= get_tortenet($megnevezes);

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Munkalapok fül
	}elseif(isset($_GET['munkalapok'])){

        $szamla_html .= nl2('
            <div class="dt naplo kijCont" data-tabla="munkalap_kapcs" data-mellek="munkalapok">
                <div class="dtr nFejlec">
                    <div class="dtc">Bejelentés szám</div>
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
                    
                    $ikt_szam = '<a href="munkalap.php?id='.$sor['recid'].'&modosit" target="_blank">'.$sor['ikt_szam'].'</a>';
                    if( !_jog(JOG_MUNKALAP_MEGTEKINT) && !_jog(JOG_MUNKALAP_MEGTEKINT_SZALL) && !_jog(JOG_MUNKALAP_MEGTEKINT_FELADAT) ){
                        $ikt_szam = $sor['ikt_szam'];
                    }
                    
                    $szamla_html .= nl2('
                        <div id="naploSor-'.$sor['recid'].'" class="dtr nSor kijSor" data-id="'.$sor['recid'].'" data-i="'.$i.'">
                            <div class="dtc">'.$ikt_szam.'</div>
                            <div class="dtc">'.$telephely.' - '.$helyseg.'</div>
                            <div class="dtc">'.$sor['insert_dt'].'</div>
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
    }elseif(isset($_GET['dokumentumok'])) {

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
    }elseif(isset($_GET['megjegyzesek'])) {

        $szamla_html .= nl2('
            <div class="dt naplo kijCont" data-tabla="megjegyzes" data-mellek="megjegyzesek">
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
