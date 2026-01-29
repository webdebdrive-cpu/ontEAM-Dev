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
$megnevezes_origi       = substr($megnevezes, 0, strlen($megnevezes)-5);
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
        global $megnevezes_origi;
        return $megnevezes_origi.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!
}

// CSV generálása
if( isset($_GET['csv']) ) {

    // CSV export előkészítése
    list($mibol, $mibe, $filename, $open) = csv_export_elokeszit($megnevezes);
    if( $open ) {

        // Nem a $_SESSION['akt_sql'] szerint szeretnénk lekérni a sorokat, mert a bal navpanelben lehet kevesebb sor jelenik meg mint amennyit lekérdeztünk
        // Viszont tudni kellene, mi volt a rendezés és a limit:

        // Rendezés kiszedése $_SESSION['akt_sql']-ből
        $order = '';
        preg_match('/ORDER BY .*?(?=LIMIT|$)/is', $_SESSION['akt_sql'], $matches);
        if (!empty($matches)) {
            $order = trim($matches[0]);
        }        

        // Ha van LIMIT a $_SESSION['akt_sql']-ben, akkor azt itt is figyelembe kell venni
        if( strpos( $_SESSION['akt_sql'], 'LIMIT' )!==false ) {
            $limit = trim( substr( $_SESSION['akt_sql'], strpos($_SESSION['akt_sql'], 'LIMIT') ), 'LIMIT ' );
            if( strpos($limit, ' ') ) {
                $limit = substr($limit, 0, strpos($limit, ' '));
            }
            $limit = " LIMIT {$limit}";
        }

        $sql = 'SELECT
                `'.get_db_tabla().$megnevezes.'`.`recid` as \'Id\',
                `'.get_db_tabla().$megnevezes.'`.`telephely_txt` as \'Szálloda\',
                `obj_txt` as \'Helyiség\',
                1 as \'Helyiség leírása\',
                `ikt_szam` as \'Munkalap szám\',
                `'.get_db_tabla().$megnevezes.'`.`update_dt` as \'Bejelentett hiba\',
                `'.get_db_tabla().$megnevezes.'`.`insert_dt` as \'Bejelentés dátuma\',
                2 as \'Hiba leírása\',
                `surgos` as \'Sürgős\',
                `stat_txt` as \'Státusz\',
                `visszaadva` as \'Visszaadva\',
                `text` as \'Leírás\',
                `gep_txt` as \'Gépnév\',
                `tipus_txt` as \'Típus\',
                `szallito_txt` as \'Külső kivitelező\',
                `jh_ert_dev` as \'Jóváhagyott érték\',
                `kcs_21` as \'Deviza\',
                `szemelyzet_txt` as \'Hozzárendelt személyzet\',
                `ig_el_id` as \'Igényelt elvégzés\',
                `varh_erk` as \'Várható érkezés\',
                `'.get_db_tabla().$megnevezes.'`.`insert_dt` as \'Munkalap létrehozás dátuma\'
                '. csv_export_get_from_tol($megnevezes).$order.$limit;
                $query = mysqli_query($db_kapcs, $sql);

                // Nagyobb CSV-kre kérdezzünk rá előzetesen
                $osszes_sor = mysqli_num_rows($query);
                if( !$_GET['mehet'] && $osszes_sor>CSV_EXPORT_LIMIT ) {
                    $gomb = '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#navGombok .excel\').attr(\'onclick\', \'csv_general( this, \\\'\\\', \\\'&mehet=1\\\');\'); $(\'.navGombok a.excel\').click();"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">';
                    echo json_encode(array('hiba' => 'A CSV export több mint '.number_format(CSV_EXPORT_LIMIT, 0, '', ' ').' sort tartalmaz. Ennek előállítása hosszabb időbe telik majd.<br>Biztosan folytatja?', 'gomb' => $gomb));
                    $weblap -> leallit();
                    exit;
                }

        $query      = mysqli_query($db_kapcs, $sql);
        $csvhead    = array();
        while ( $fejlec_nevek = mysqli_fetch_field($query) ) {
            if( $fejlec_nevek -> name == 'Id' ) {
                continue;
            }
            $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
        }
        fputcsv($open, $csvhead, ';');

        $i = 0;
        while( $sorok = mysqli_fetch_assoc($query) ) {
        
            $csvcont = array();

            $bejelentes = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs_arch', "`munkalap_id`='{$sorok['Id']}'");
            $bejelentes = $adatbazis_kapcsolat->s2('hiba_txt hiba_text helyiseg_text insert_dt', 'bejelentes_arch', "`recid`='{$bejelentes['bejelentes_id']}'");
            foreach( $sorok as $kulcs => $sor ) {

                if( $kulcs == 'Id' ) {
                    continue;
                }
                if( $kulcs == 'Bejelentett hiba' ) {
                    $sor = $bejelentes['hiba_txt'];
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
                if( $kulcs == 'Igényelt elvégzés' || $kulcs == 'Várható érkezés' ) {
                    if( $sor == '0000-00-00 00:00:00' ) {
                        $sor = '';
                    }
                }
                if( $kulcs == 'Jóváhagyott érték' ) {
                    $sorok[$szam] = str_replace('.', ',', $sorok[$szam]);
                }
                if( $kulcs == 'Deviza' ) {
                    $sor = erteklista(ERTEKLISTA_PENZNEM, $sor);
                }
                if( $kulcs == 'Sürgős' ) {
                    if( $sor ) {
                        $sor = 'igen';
                    } else {
                        $sor = 'nem';
                    }
                }
                if( $kulcs == 'Visszaadva' ) {
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

    csv_export_lezar($fh, $filename, 'Archív-Munkalap');
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
if( $sor['dok_db'] ) {
    $dok_db  = ' ('.$sor['dok_db'].')';
}
if( $sor['megj_db'] ) {
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

// Gombok nincsenek
$gombok = nl2('<div class="muveletek"></div>');

$js_submit_elott = 'return form_ell_admin();';

if( isset($_GET['modosit']) ) {

    // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
    if( isset($_GET['id_hidden']) ) {
        $_GET['id'] = $_GET['id_hidden'];
    }
    
    // Jóváhagyott érték legyen formázva, úgy is csak ki van írva
    $sor['jh_ert_dev'] = number_format($sor['jh_ert_dev'], 0, '', ' ');

    // Mivel artchiv felületen vagyunk, ezért a mezők típusát és nevét átírjuk ha kell
    // Erre azért van szükség, hogy pl. egy atocompletes mezőből txt legyen, hogy az értékét ne más táblákból olvassa vissza
    // hanem az adatbázisban letároltat
    $i = 0;
    foreach ( $akt_tomb as $tomb ) {
        if( $tomb['type_arch'] ) {
            $akt_tomb[$i]['type'] = $tomb['type_arch'];
            $akt_tomb[$i]['name'] = $tomb['name_arch'];
        }
        $i++;
    }
    
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

    $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

    $munkalap_kapcs = $adatbazis_kapcsolat->s('recid', 'munkalap_kapcs', "`munkalap_id` = '".$sor['recid']."'");

    // Form actionja
    $action = self().get_get(array('errnum','kephiba','siker'));

    if( isset($_GET['ujkent']) ) {
        foreach ( $akt_tomb as $ertek ) {
            $sor[$ertek['name']] = $_SESSION[$ertek['name']];
        }
    }

    // Ez elkészíti a $mezok változót
    require('switch.php');
    $mezok .= '<input type="hidden" id="ujkentSzerkID" name="ujkentSzerkID" value="'.$_GET['ujkent'].'">';

    $szamla_html .= str_replace(
        array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}'    ),
        array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok       ),
        fajlbol_olvas('../sablon/admin/form_modosit_arch.html', true)
    );

    // Bejelentés táblázata
    $munkalap_kapcsok = $adatbazis_kapcsolat->s('recid bejelentes_id', 'munkalap_kapcs_arch', "`munkalap_id` = '{$_GET['id']}'");
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

        foreach ( $munkalap_kapcsok as $munkalap_kapcs ) {

            $sor = $adatbazis_kapcsolat->s2('recid ikt_szam telephely_id obj_id hiba_id insert_dt stat_id hiba_text usr_nev usr_telefon usr_email usr_beosztas_id usr_reszleg_id helyiseg_text', 'bejelentes_arch', "`recid` = '".$munkalap_kapcs['bejelentes_id']."'", '', '1');
            if( $sor ) {

                // Telephely
                $telephely = getSzallodaNev($sor['telephely_id']);

                // Helység
                $helyseg = $adatbazis_kapcsolat->s('nev', 'obj', 'recid='.$sor['obj_id'], '', '1');
                $helyseg = $helyseg[0]['nev'];

                // Hiba
                $hiba = erteklista('', '', $sor['hiba_id']);

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
                        <div class="dtc vat">'.$hiba.'<br>'.nl2br2($sor['hiba_text']).'</div>
                        <div class="dtc vat">
                            '.$telephely.'
                            <br>'.$helyseg.'
                            <br>'.nl2br2($sor['helyiseg_text']).'
                        </div>
                        <div class="dtc vat">
                            <a href="bejelentes_arch.php?id=x&amp;mezo=ikt_szam&amp;op=equal&amp;felt='.$sor['ikt_szam'].'" target="_blank">
                                '.$sor['ikt_szam'].'
                            </a>
                            <br>'.datum_kiir($sor['insert_dt']).'
                            <br>'.getStatText('bejelentes', $sor['stat_id']).'
                        </div>
                        <div class="dtc vat">
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
} elseif( isset($_GET['tetel']) ) {

    // Tétel sorok
    $i = 0;
    if( $_GET['id'] ) {
        $sorok = $adatbazis_kapcsolat->s('recid atadta_dt visszaadva erteklista_id loginnev insert_dt insert_usr', 'munkalap_tetel_arch', "`munkalap_id`='{$_GET['id']}'", 'recid desc');
        foreach($sorok as $sor) {

            $munkalap = $adatbazis_kapcsolat->s2('stat_id szemelyzet_id', $megnevezes, "`recid`='{$_GET['id']}'");
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
            $feladatok = $adatbazis_kapcsolat->s('recid erteklista_id tetel_id memo', 'munkalap_feladat_arch', "`tetel_id` = '{$sor['recid']}'", 'insert_dt desc');
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

                    $szamla_html .= nl2('
                        <tr class="feladat_sor_'.$feladat['recid'].'">
                            <td id="elv_feladat_nev_'.$feladat['recid'].'">'.erteklista(ERTEKLISTA_ELV_FELAD, $feladat['erteklista_id']).'</td>
                            <td id="elv_feladat_memo_'.$feladat['recid'].'">'.nl2br($feladat['memo']).'</td>
                            <td class="r"></td>
                        </tr>
                    ');
                }

                $szamla_html .= '</table></div>';
            }

            // Anyagigénylések lekérdezése
            $anyagok = $adatbazis_kapcsolat->s('recid cikk_id cikk_nev cikk_men szuk_men cikk_mee stat_id', 'munkalap_anyag_arch', "`tetel_id` = '{$sor['recid']}'", 'insert_dt desc', '');
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

                    $cikk_men = '';
                    if( $anyag['cikk_men'] > 0 ) {
                        $cikk_men = $anyag['cikk_men'].' '.$anyag['cikk_mee'];
                    }

                    $szuk_men = '';
                    if( $anyag['szuk_men'] > 0 ) {
                        $szuk_men    = $anyag['szuk_men'].' '.$anyag['cikk_mee'];
                    }

                    $szamla_html .= nl2('
                        <tr class="anyag_sor_'.$anyag['recid'].'">
                            <td id="anyag_cikkid_'.$anyag['recid'].'">'.$anyag['cikk_id'].'</td>
                            <td id="anyag_nev_'.$anyag['recid'].'">'.$anyag['cikk_nev'].'</td>
                            <td class="r">'.$cikk_men.'</td>
                            <td class="r">'.$szuk_men.'</td>
                            <td>'.$munkAnyag_statusz[$anyag['stat_id']].'</td>
                            <td class="r"></td>
                        </tr>
                    ');
                }

                $szamla_html .= '</table>';
            }

            // Munkaidő ráfordítás lekérdezése
            $munkaidok = $adatbazis_kapcsolat->s('recid login_id ora perc', 'munkalap_munkaido_arch', "`tetel_id` = '{$sor['recid']}'", 'insert_dt desc', '');
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

                    $szamla_html .= nl2('
                        <tr class="munkaido_sor_'.$munkaido['recid'].'">
                            <td id="munkaido_nev_'.$munkaido['recid'].'">'.getErtek('teljes_nev', 'login', 'recid', $munkaido['login_id']).'</td>
                            <td id="munkaido_ido_'.$munkaido['recid'].'" class="r">'.$munkaido['ora'].':'.$munkaido['perc'].'</td>
                            <td class="r"></td>
                        </tr>
                    ');
                }

                $szamla_html .= '</table>';
            }

            $szamla_html .= '</div>';

            $i++;
        }
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

    // Műveletek box
    $gombok = nl2('
        <div class="muveletek"></div>
    ');

    if( $_GET['ajax'] ) {
        echo json_encode(array(
            'tartalom' => $szamla_html.$gombok
        ));
    } else {
        echo $szamla_html.$gombok;
    }

// Anyagigénylés fül
}elseif(isset($_GET['anyagigenyles'])){

    if( $_GET['id'] ) {
        $sorok = $adatbazis_kapcsolat->s('recid cikk_id szuk_men', 'munkalap_anyag_arch', "`munkalap_id`='{$_GET['id']}' AND `stat_id` IN (".DISZPNAG_STAT_ANYAGRA_VAR.", ".DISZPNAG_STAT_ANYAG_IGENYELVE.")", 'insert_dt desc');
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
}elseif( isset($_GET['dokumentumok']) ) {

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
} elseif (isset($_GET['megjegyzesek'])) {

    // <div class="dt naplo" class nevekből a kijCont kivéve, hogy a megjegyzéseket ne lehessen törölni
    $szamla_html .= nl2('
        <div class="dt naplo" data-tabla="megjegyzes" data-mellek="megjegyzesek">
            <div class="dtr nFejlec">
                <div class="dtc">Megjegyzés</div>
                <div class="dtc">Hozzáadta</div>
                <div class="dtc">Hozzáadva</div>
            </div>
    ');

    if( $_GET['id'] ) {
        $sorok = $adatbazis_kapcsolat->s('recid szerepkor megj insert_usr insert_dt', 'megjegyzes_arch', "`id`='{$_GET['id']}' AND `tabla`='munkalap'", 'recid desc', '');
        if( $sorok ) {
            $i = 1;
            foreach($sorok as $sor) {
                $szamla_html .= nl2('
                    <div id="naploSor-'.$sor['recid'].'" class="dtr nSor kijSor" data-id="'.$sor['recid'].'" data-i="'.$i.'">
                        <div class="dtc">'.htmlspecialchars_decode( nl2br($sor['megj']) ).'</div>
                        <div class="dtc">'.$sor['insert_usr'].' '.getSzerepkorNev($sor['szerepkor']).'</div>
                        <div class="dtc">'.str_replace('-', '.', substr($sor['insert_dt'], 0, 16)).'</div>
                    </div>
                ');

                $i++;
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

}

// szamlaBetolt div vége
if( !$_GET['ajax'] ) {
    $szamla_html .= '</div>';
}

if( !$_GET['ajax'] ) {
    require_once('footer.php');
}
