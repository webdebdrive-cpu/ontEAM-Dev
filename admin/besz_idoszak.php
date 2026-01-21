<?php
require_once('../weblap.php');

if( !_jog(JOG_BESZERZ_IDOSZAK_OLVAS) ) {
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
$akt_tomb               = $besz_idoszak_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'időszakot';                     // Hibaüzenetkor: Biztosan törlöd a $szoveg?

$szamla_html = '';

// Dokumentumok fülhöz kell majd
$tobb_kep = 1;
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

// Új időszak évszáma
if (isset($_POST['get_idoszak_ev'])) {

    $ev = date('Y');
    if( date('m')>9 ) {
        $ev++;
    }
    
    echo json_encode(array(
                             'ev'  => $ev,
                     ));

    $weblap -> leallit();
    exit;
}

// Új időszak mentése
if (isset($_POST['uj_idoszak_ment'])) {

    $alap_ev = date('Y');
    if( date('m')>9 ) {
        $alap_ev++;
    }

    if( $_POST['ev']<$alap_ev ) {
        ajax_hiba('Az új időszak nem lehet korábbi, mint '.$alap_ev.'!');
    }
    if( $_POST['ev']>$alap_ev+5 ) {
        ajax_hiba('Az új időszak nem lehet későbbi, mint '.($alap_ev+5).'!');
    }

    $_SESSION['uj_idoszak_ev'] = $_POST['ev'];

    echo json_encode(array(
        'html'  => 'ok',
    ));

    $weblap -> leallit();
    exit;
}

// Időszak megnyitása
if (isset($_POST['idoszak_megnyitasa'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('stat_id', 'besz_idoszak', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IDOSZAK_KEZEL) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga időszakot megnyitni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != BESZERZES_IDOSZAK_INAKTIV) {
        echo json_encode(array(
                                'hiba'  => 'Ebben a státuszban nem lehet időszakot megnyitni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    $van_nem_lezart = $adatbazis_kapcsolat -> s2('azon stat_id', 'besz_idoszak', "`stat_id`='".BESZERZES_IDOSZAK_AKTIV."'");
    if( $van_nem_lezart ) {
        echo json_encode(array(
                                'hiba'  => $van_nem_lezart['azon'].' - '.getStatText('besz_idoszak', $van_nem_lezart['stat_id']).'<br>Az időszakot nem lehet megnyitni, mert az előző időszak még nincs lezárva!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }
    
    // Státuszléptetés
    if( addStatusz('besz_idoszak_statusz', $_POST['id'], BESZERZES_IDOSZAK_AKTIV) === false ) {
        $db_error = true;
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker' => 1,
                                ) );
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba'
        ));
    }

    $weblap -> leallit();
    exit;
}

// Időszak zárása
if (isset($_POST['idoszak_zarasa'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('azon stat_id', 'besz_idoszak', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IDOSZAK_KEZEL) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga időszakot lezárni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != BESZERZES_IDOSZAK_AKTIV) {
        echo json_encode(array(
                                'hiba'  => 'Csak aktív státuszban lehet időszakot lezárni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    $van_folyamatban_igeny = $adatbazis_kapcsolat -> s2('azon stat_id', 'eszkozigenyles', "`idoszak_azon`='{$sor['azon']}' AND `stat_id` NOT IN ('".ESZKOZIGENYL_STAT_ELUTASITOTT."', '".ESZKOZIGENYL_STAT_MEGRENDELVE."')");
    if( $van_folyamatban_igeny ) {
        echo json_encode(array(
                                'hiba'  => 'Az igénylések még folyamatban vannak. Az időszak nem zárható le!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }
    
    // Státuszléptetés
    if( addStatusz('besz_idoszak_statusz', $_POST['id'], BESZERZES_IDOSZAK_LEZART) === false ) {
        $db_error = true;
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker' => 1,
                                ) );
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba'
        ));
    }

    $weblap -> leallit();
    exit;
}

// Igényfeladás megnyitása
if (isset($_POST['igenyfeladas_nyit_zar'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('nyitva stat_id', 'besz_idoszak', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    $nyit_zar = 1;
    $text     = 'megnyitni';
    $text2    = ' megnyitva';
    if( $_POST['zar'] ) {
        $nyit_zar = 0;
        $text     = 'lezárni';
        $text2    = ' lezárva';
    }
    $fured  = '';
    $fured2 = '';
    if( $_POST['fured'] ) {
        $fured  = '_fured';
        $fured2 = ' Füred';
    }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IDOSZAK_KEZEL) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga igényfeladást '.$text.'!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != BESZERZES_IDOSZAK_AKTIV) {
        echo json_encode(array(
                                'hiba'  => 'Ebben a státuszban nem lehet igényfeladást '.$text.'!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }
    
    // Megnyitás/zárás
    $sql = $adatbazis_kapcsolat -> u('besz_idoszak', array('nyitva'.$fured), array($nyit_zar), "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Megjegyzésbe írás
    if( addMegj('besz_idoszak', $_POST['id'], 'Igénylés határidő'.$fured2.$text2.'.') === false ) {
        $db_error = true;
    }

    // Értesítés küldés MEGNYITÁSKOR ha nem Füred
    if( $nyit_zar ) {
        // Ha Füred-es sor
        if ( $_POST['fured'] ) {
            $mail_hibauzenet = level_felkeszit_es_kuld('besz_idoszak', $_POST['id'], ERT_BESZERZ_IGENY_IDOSZ_NYIT_F, 'besz_idoszak_igenyl_nyit_f', 'besz_idoszak_igenyl_nyit_f');

        // Ha normál sor
        } else {
            $mail_hibauzenet = level_felkeszit_es_kuld('besz_idoszak', $_POST['id'], ERT_BESZERZ_IGENY_IDOSZ_NYIT, 'besz_idoszak_igenyl_nyit', 'besz_idoszak_igenyl_nyit');
        }
    }
    // Értesítés küldés ZÁRÁSKOR
    if( !$nyit_zar ) {
        // Ha Füred-es sor
        if( $_POST['fured'] ) {
            $mail_hibauzenet = level_felkeszit_es_kuld('besz_idoszak', $_POST['id'], ERT_BESZERZ_IGENY_IDOSZ_ZAR_F, 'besz_idoszak_igenyl_zar_f', 'besz_idoszak_igenyl_zar_f');

        // Ha normál sor
        } else {
            $mail_hibauzenet = level_felkeszit_es_kuld('besz_idoszak', $_POST['id'], ERT_BESZERZ_IGENY_IDOSZ_ZAR, 'besz_idoszak_igenyl_zar', 'besz_idoszak_igenyl_zar');
        }
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker' => 1,
                                ) );
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba'
        ));
    }

    $weblap -> leallit();
    exit;
}

// CSV generálása
if( isset($_GET['csv']) ) {

    // CSV export előkészítése
    list($mibol, $mibe, $filename, $open) = csv_export_elokeszit($megnevezes);
    if( $open ) {
        
        $sql = 'SELECT
                `azon` as \'Időszak ID\',
                `stat_id` as \'Státusz\',
                `arfolyam` as \'Alkalmazott árfolyam\',
                `kezd` as \'Időszak kezdete\',
                `vege` as \'Időszak vége\',
                `hatar` as \'Igénylés határidő\',
                `hatar_fured` as \'Igénylés határidő Füred\'
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

                if( $kulcs == 'Időszak ID' ) {
                    $sor = '{EGYENLOSEG_JELET_ELE}'.$sor.'{EGYENLOSEG_JELET_ELE_VEGE}';
                }

                if( $kulcs == 'Státusz' ) {
                    $sor = getStatText('besz_idoszak', $sor);
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

    csv_export_lezar($fh, $filename, 'Beszerzési időszak');
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
if( $sor['dok_db'] ) {
    $dok_db  = ' ('.$sor['dok_db'].')';
}
if( $sor['megj_db'] ) {
    $megj_db = ' ('.$sor['megj_db'].')';
}

if( isset($_GET['modosit']) || isset($_GET['tortenet']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {

    // Számla fülek
    $ful_tomb = array(
        'modosit'       => 'Adatlap',
        'tortenet'      => 'Történet',
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

// Űrlap mentésekor ellenőrzünk pár dolgot (nem csak új felvitelben, módosításkor is)
// Mivel a Mentés gombra kattintva fut le, ezért támaszkodhatunk arra, hogy az adatlap fülön vagyunk, tehát JS-el elérhetőek a mezők.
$sql_kieg = '';
if(isset($_GET['modosit']) && $_GET['id'] ) {
    $sql_kieg .= "`recid`<'{$_GET['id']}'";
}
// Ha van előző időszak. Első időszak létrehozásakor még nincs.
$js_submit_elott = '';
$elozo_idoszak  = $adatbazis_kapcsolat -> s2('azon vege hatar hatar_fured', 'besz_idoszak', $sql_kieg,  'recid desc', '1');
if( $elozo_idoszak ) {
    $elozo_vege         = str_replace('-', '.', $elozo_idoszak['vege']       );
    $elozo_hatar        = str_replace('-', '.', $elozo_idoszak['hatar']      );
    $elozo_hatar_fured  = str_replace('-', '.', $elozo_idoszak['hatar_fured']);

    // Igazából a 2. és 3. feltételt feleslegesen figyeljük, mert az is egy szabály, hogy a határidő az időszak dátumai közé kell hogy essen, ami már eleve lehatárolja.
    // De az első feltétel fontos!
    $js_submit_elott = nl2('
        if( $(\'#kezd\').val() <= \''.$elozo_vege.'\' ) {
            alert2(\'Az időszak kezdete nem lehet korábbi mint az előző időszak ('.$elozo_idoszak['azon'].') vége ('.$elozo_vege.')!\', \'\', \'Hiba\');
            $(\'#kezd\').addClass(\'hibas_ide_focus\');
            return false;
        }

        if( $(\'#hatar\').val() <= \''.$elozo_hatar.'\' ) {
            alert2(\'Igénylés határideje az előző időszak ('.$elozo_idoszak['azon'].') határidejétől ('.$elozo_hatar.') későbbi kell legyen!\', \'\', \'Hiba\');
            $(\'#hatar\').addClass(\'hibas_ide_focus\');
            return false;
        }

        if( $(\'#hatar_fured\').val() <= \''.$elozo_hatar_fured.'\' ) {
            alert2(\'A füredi igénylés határideje az előző időszak ('.$elozo_idoszak['azon'].') füredi határidejétől ('.$elozo_hatar_fured.') későbbi kell legyen!\', \'\', \'Hiba\');
            $(\'#hatar\').addClass(\'hibas_ide_focus\');
            return false;
        }
    ');
}

// Ha nem új felvitel, hanem meglevő mentése, csak akkor kell a követekző időszakot figyelni
if(isset($_GET['modosit']) && $_GET['id'] ) {
    $kov_idoszak = $adatbazis_kapcsolat -> s2('azon kezd hatar hatar_fured', 'besz_idoszak', "`recid`>'{$_GET['id']}'", 'recid asc', '1');
    if( $kov_idoszak ) {
        $kov_kezd        = str_replace('-', '.', $kov_idoszak['kezd'] );
        $kov_hatar       = str_replace('-', '.', $kov_idoszak['hatar']      );
        $kov_hatar_fured = str_replace('-', '.', $kov_idoszak['hatar_fured']);

        // Igazából a 2. és 3. feltételt feleslegesen figyeljük, mert az is egy szabály, hogy a határidő az időszak dátumai közé kell hogy essen, ami már eleve lehatárolja.
        // De az első feltétel fontos!
        $js_submit_elott = nl2('
            if( $(\'#vege\').val() >= \''.$kov_kezd.'\' ) {
                alert2(\'Az időszak vége nem lehet későbbi mint a követekző időszak ('.$kov_idoszak['azon'].') kezdete ('.$kov_kezd.')!\', \'\', \'Hiba\');
                $(\'#vege\').addClass(\'hibas_ide_focus\');
                return false;
            }

            if( $(\'#hatar\').val() >= \''.$kov_hatar.'\' ) {
                alert2(\'Igénylés határideje a következő időszak ('.$kov_idoszak['azon'].') határidejétől ('.$kov_hatar.') korábbi kell legyen!\', \'\', \'Hiba\');
                $(\'#hatar\').addClass(\'hibas_ide_focus\');
                return false;
            }

            if( $(\'#hatar_fured\').val() >= \''.$kov_hatar_fured.'\' ) {
                alert2(\'Igénylés füredi határideje az előző időszak ('.$kov_idoszak['azon'].') füredi határidejétől ('.$kov_hatar_fured.') korábbi kell legyen!\', \'\', \'Hiba\');
                $(\'#hatar\').addClass(\'hibas_ide_focus\');
                return false;
            }

        ');
    }
}

// Ha nincs előző időszak, akkor is ellenőrzünk pár dolgot
$holnaputan = date('Y.m.d', strtotime('+2 Days'));
$js_submit_elott .= nl2('
                    if( $(\'#vege\').val() > \''.date('Y.m.d', strtotime('+1 Year')).'\' ) {
                        alert2(\'Az időszak vége nem lehet későbbi egy évnél!\', \'\', \'Hiba\');
                        $(\'#vege\').addClass(\'hibas_ide_focus\');
                        return false;
                    }

                    if( $(\'#hatar\').val() < $(\'#kezd\').val() || $(\'#hatar\').val() > $(\'#vege\').val() ) {
                        alert2(\'Igénylés határideje az időszak kezdete és vége közé kell hogy essen!\', \'\', \'Hiba\');
                        $(\'#hatar\').addClass(\'hibas_ide_focus\');
                        return false;
                    }
                    /* IDEIGLENESEN KISZEDVE
                    if( $(\'#hatar\').val() < \''.$holnaputan.'\' ) {
                        alert2(\'Igénylés határideje legkorábban '.$holnaputan.' dátumra adható meg!\', \'\', \'Hiba\');
                        $(\'#hatar\').addClass(\'hibas_ide_focus\');
                        return false;
                    }
                    */

                    if( $(\'#hatar_fured\').val() < $(\'#kezd\').val() || $(\'#hatar_fured\').val() > $(\'#vege\').val() ) {
                        alert2(\'A füredi igénylés határideje az időszak kezdete és vége közé kell hogy essen!\', \'\', \'Hiba\');
                        $(\'#hatar_fured\').addClass(\'hibas_ide_focus\');
                        return false;
                    }

                    /* IDEIGLENESEN KISZEDVE
                    if( $(\'#hatar_fured\').val() < \''.$holnaputan.'\' ) {
                        alert2(\'A füredi igénylés határideje legkorábban '.$holnaputan.' dátumra adható meg!\', \'\', \'Hiba\');
                        $(\'#hatar_fured\').addClass(\'hibas_ide_focus\');
                        return false;
                    }
                    */

                    if( _get(\'uj\') ) {
                        if( !form_ell_admin() ) {
                            return false;
                        }
                    } else {
                        if( !form_ell_admin(\'-mentes_nelkul-\') ) {
                            return false;
                        }
                    }
');

// Gombok kirajzolása minden oldalra
$gombok = '';
if( isset($_GET['uj']) && _jog(JOG_BESZERZ_IDOSZAK_OLVAS) ) {

    $gombok = nl2('
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

// Módosítás gombok
} else {

    // Js, a törlés gombhoz
    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';

    // Jogosutságkezelés
    if( !_jog(JOG_BESZERZ_IDOSZAK_KEZEL) ) {
        $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Nincs jogosultsága törölni ezt a '.$szoveg.'!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
    } else {
        $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
    }

    // Időszak megnyitása
    if( $sor['stat_id'] == BESZERZES_IDOSZAK_INAKTIV && _jog(JOG_BESZERZ_IDOSZAK_KEZEL ) ) {
        $van_nem_lezart = $adatbazis_kapcsolat -> s2('azon stat_id', 'besz_idoszak', "`stat_id`!='".BESZERZES_IDOSZAK_LEZART."' AND `recid`!='{$_GET['id']}'");
        if( $van_nem_lezart ) {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" onclick="alert2(\''.$van_nem_lezart['azon'].' - '.getStatText('besz_idoszak', $van_nem_lezart['stat_id']).'<br>Az időszakot nem lehet megnyitni, mert az előző időszak még nincs lezárva!\', \'\', \'Hiba\');" value="Időszak megnyitása"> ';
        } else {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" onclick="idoszak_megnyitasa();" value="Időszak megnyitása"> ';
        }
    }
    // Időszak zárása
    if( $sor['stat_id'] == BESZERZES_IDOSZAK_AKTIV && _jog(JOG_BESZERZ_IDOSZAK_KEZEL ) ) {
        $van_folyamatban_igeny = $adatbazis_kapcsolat -> s2('azon stat_id', 'eszkozigenyles', "`idoszak_azon`='{$sor['azon']}' AND `stat_id` NOT IN ('".ESZKOZIGENYL_STAT_ELUTASITOTT."', '".ESZKOZIGENYL_STAT_MEGRENDELVE."')");
        if( $van_folyamatban_igeny ) {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" onclick="alert2(\'Az igénylések még folyamatban vannak. Az időszak nem zárható le!\', \'\', \'Hiba\');" value="Időszak zárása"> ';
        } else {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" onclick="idoszak_zarasa();" value="Időszak zárása"> ';
        }
    }
    $gombok .= '<input type="hidden" id="azon_db" value="'.$sor['azon'].'">';

    if( _jog(JOG_BESZERZ_IDOSZAK_KEZEL) ) {
        $gombok = nl2('
            <div class="form_kuldes">
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

// Új rekord felvitele
if( isset($_GET['uj']) ) {

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

    $form_sablon = fajlbol_olvas('../sablon/admin/form_uj.html', true);

    // Javascript-es ellenőrzéshez
    $tabla = str_replace('.php', '', basename(self()));

    $rend_mezok = $adatbazis_kapcsolat->s('`column_name`', '`information_schema`.columns', '`table_schema` LIKE  \''.get_db().'\' AND  `table_name` LIKE  \''.get_db_tabla().$tabla.'\'', '', '');
    $rend_mezok = adatbazis2tomb($rend_mezok, 'column_name');

    $sor = array();
    foreach ( $rend_mezok as $rend_mezo ) {
        $sor[$rend_mezo] = '';
    }

    $beallitas = $adatbazis_kapcsolat -> s2('arfolyam', 'beallitas', "`recid`='1'");
    $sor['arfolyam'] = $beallitas['arfolyam'];

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
            'tartalom'  => $szamla_html,
            'link'      => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
        ));
    } else {
        echo $szamla_html;
    }

// Rekord módosítása
} elseif( isset($_GET['modosit']) ) {

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
    $szamla_html .= '<script>formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';
    $szamla_html .= nl2('
        <script>
            duplikalGomb  = "<p class=\"c\"><input class=\"gomb zold\" type=\"button\" value=\"Igen\" onclick=\"szurkit(); location.href = \''.basename(self()).'?uj&amp;ujkent='.$_GET['id'].'\'; \"><input class=\"gomb piros\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
            duplikaKerdes = \'Azonosító: '.str_replace('\'', '', $sor['ikt_szam']).'<br>Szálloda: '.str_replace('\'', '', $telephely).'<br><br>Biztos, hogy duplikálja?\';
        </script>
    ');
    
    // Form
    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

    // Form actionja
    $action = self().get_get(array('errnum','kephiba','siker'));

    // Ez elkészíti a $mezok változót
    require('switch.php');

    $szamla_html .= str_replace(
        array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}', '{UPDATE_DT}'     ),
        array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok,    $sor['update_dt'] ),
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

    if( $tobb_kep ) {
        require_once('tobb_kep.php');
    }

// Történet
}elseif( isset($_GET['tortenet']) ) {

    $szamla_html .= get_tortenet($megnevezes);

    if( $_GET['ajax'] ) {
        echo json_encode(array(
            'tartalom' => $szamla_html.$gombok
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

// Megjegyzések
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

    if( $_GET['id'] ) {

        $sorok = $adatbazis_kapcsolat->s('recid megj insert_usr insert_dt', 'megjegyzes', "`id`='{$_GET['id']}' AND `tabla`='".str_replace('.php', '', basename(self()))."'", 'recid desc');
        if( $sorok ) {
            $i = 1;
            foreach ( $sorok as $sor ) {
                $szamla_html .= nl2('
                    <div id="naploSor-'.$sor['recid'].'" data-id="'.$sor['recid'].'" data-i="'.$i.'" class="dtr nSor kijSor">
                        <div class="dtc">'.htmlspecialchars_decode( nl2br2($sor['megj']) ).'</div>
                        <div class="dtc">'.$sor['insert_usr'].'</div>
                        <div class="dtc">'.datum_kiir($sor['insert_dt']).'</div>
                    </div>
                ');

                $i++;
            }
        }

    }

    if( $_GET['ajax'] ) {
        echo json_encode(array(
            'tartalom'  => $szamla_html.$gombok,
            'link'      => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
        ));
    } else {
        echo $szamla_html;
    }

}

if( !$_GET['ajax'] ) {
    echo '</div>';
}

if( !$_GET['ajax'] ) {
    require_once('footer.php');
}