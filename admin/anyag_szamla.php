<?php
require_once('../weblap.php');

if( !_jog(JOG_ANYAG_SZAMLAK_OLVAS) ) {
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
$akt_tomb               = $anyag_szamla_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'számlát';         // Hibaüzenetkor: Biztosan törlöd a $szoveg?

$szamla_html = '';

// Dokumentumok fülhöz kell majd
$tobb_kep = true;
if( $tobb_kep ) {
    function get_tobbkep_darab()    { return 10;         } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '.jpg,.png';} // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;         } // MB-ban
    function get_tobbkep_oszlopnev(){                      // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

}

// Mennyiségi egység feltöltése a cikktörzs alapján
if( isset($_POST['onteam_id_mee_kitolt']) ) {
    
    $cikk = $adatbazis_kapcsolat->s2('id cikkmee', 'cikktorzs', "`recid`={$_POST['recid']}");
    echo json_encode(array(
                        'cikkmee'   => $cikk['cikkmee'],
                        'id'        => $cikk['id'],
                        ));
    
    $weblap -> leallit();
    exit;
}

// Jogcím autocomplete alapján a költségnem és hely feltöltése
if( isset($_POST['ktgnem_ktghely_kitolt']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $jogcim  = $adatbazis_kapcsolat->s2('szla_szam ktghely ktghely_id megn', 'jogcim', "`recid`='{$_POST['jogcim_recid']}'");
    if( $jogcim === false ) { $db_error = true; }
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);

        echo json_encode(array(
            'ktgnem_id' => $jogcim['szla_szam'],
            'ktghely_id'=> $jogcim['ktghely_id'],
            'ktgnem'    => $jogcim['megn'],
            'ktghely'   => $jogcim['ktghely'],
        ));
    } else {
        mysqli_rollback($db_kapcs);

        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba'
        ));
    }
    
    $weblap->leallit();
    exit();
}

// Anyagrendelések kilistázása
if( isset($_POST['rendeles_tetel_hozzaadasa']) ) {
    
    $_POST['torolt_rndrecidk']      = json_decode($_POST['torolt_rndrecidk'],       '-tömbként-');
    $_POST['hozzaadott_rndrecidk']  = json_decode($_POST['hozzaadott_rndrecidk'],   '-tömbként-');
    
    $title  = 'Rendelés tétel kiválasztása';
    $fizmod = '0';
    if( mb_strtolower($_POST['fizmod'], get_web_charset()) == 'készpénz' ) {
        $fizmod = '1';
    }

    $html = nl2('
            <div class="piros c hibauzenet"></div>
            <div style="max-height: 40rem; overflow-y: auto">
                <table class="csikos rendeles_sorok">
                    <thead>
                        <tr>
                            <th>Rendelés szám</th>
                            <th>Cikkszám</th>
                            <th>Cikknév</th>
                            <th>Rendelt mennyiség</th>
                            <th>Rendelési egységár</th>
                            <th>Pénznem</th>
                            <th>Számlázott mennyiség</th>
                        </tr>
                    </thead>
                    <tbody>
                ');
    $where_kieg = '';
    if( $_POST['torolt_rndrecidk'] ) {
        $where_kieg .= '`recid` IN ('.implode(',', $_POST['torolt_rndrecidk']).') OR ';
    }
    // 2021.12.16 Az alábbi SQL feltételben volt még egy ilyen is: AND `keszpenz`='{$fizmod}' de ezt kérték kivenni belőle
    $rendelesek = $adatbazis_kapcsolat->s('recid rndid cikk_id nev rndmen rndear vrndear kcs_21 cikkmee szlamen', 'anyagigenyles', "{$where_kieg}(`telephely_id`='{$_POST['telephely_id']}' AND `szallito_id`='{$_POST['szallito_id']}' AND `rndmen`>`szlamen` AND `stat_id` IN ('".CIKKIGN_STAT_RESZBENBEERK."', '".CIKKIGN_STAT_BEERKEZETT."'))");
    foreach ( $rendelesek as $rendeles ) {
        
        // Ha adott anyagrendelés már hozzá van adva egy számla tételhez, akkor szürkén jelenítjük meg
        // Ha egy sort logikailag töröltünk a popup-on, akkor azokkal viszont ne foglalkozzunk.
        $class = '';
        // Update: ezt mindig mutatjuk, mert egy rendelést lehet két részletben számláznak.
        // Csak azért mert már a rendelés szerepel egy másik számla tétellel összekapcsolva,
        // attól az még kiválasztható lesz egy másiknál
//        if( !in_array($rendeles['recid'], $_POST['torolt_rndrecidk']) ) {
//            $mar_kapcsolva = $adatbazis_kapcsolat->s2('recid', 'anyag_szamla_tetel', "`rnd_recid`='{$rendeles['recid']}'");
//            if( $mar_kapcsolva ) {
//                $class = ' class="szurke"';
//            }
//        }

        // A fordítottja is igaz: ha most adtunk volna hozzá a sort (hiába még csak logikailag), akkor viszont legyen szürke
        if( in_array($rendeles['recid'], $_POST['hozzaadott_rndrecidk']) ) {
            $class = ' class="szurke"';
        }
        
        $egysegar_oszlop = 'rndear';
        if( $rendeles['kcs_21'] != KCS_21_HUF ) {
            $egysegar_oszlop = 'vrndear';
        }
        
        $html .= nl2('
                    <tr'.$class.'>
                        <td class="rend_pop_rendid">'.$rendeles['rndid'].'</td>
                        <td class="rend_pop_cikk_id">'.$rendeles['cikk_id'].'</td>
                        <td class="rend_pop_cikk_nev">'.$rendeles['nev'].'</td>
                        <td>'.number_format($rendeles['rndmen'], 2, ',', ' ').'</td>
                        <td>'.number_format($rendeles[$egysegar_oszlop], 2, ',', ' ').'</td>
                        <td>
                            '.erteklista(ERTEKLISTA_PENZNEM, $rendeles['kcs_21']).'
                            <input type="hidden" class="rend_pop_cikkmee" value="'.$rendeles['cikkmee'].'">
                            <input type="hidden" class="rend_pop_rend_recid" value="'.$rendeles['recid'].'">
                        </td>
                        <td>'.number_format($rendeles['szlamen'], 2, ',', ' ').'</td>
                    </tr>
                    ');
    }
    $html .= '
                </tbody>
            </table>
        </div>
        ';
    
    if( !$rendelesek ) {
        $hiba = nl2('
                    Nem található az alábbi adatokhoz kapcsolódó rendelés:
                    <br>Szálloda: '.getSzallodaNev($_POST['telephely_id']).'
                    <br>Partner: '. getErtek('nev', 'szallito', 'id', $_POST['szallito_id']).'
                    ');
        echo json_encode(array(
                                'hiba'  => $hiba,
                                'title' => 'Hiba',
                                ));
        $weblap->leallit();
        exit();
    }
    
    echo json_encode(array(
                            'html'  => $html,
                            'title' => $title,
                            ));
    
    
    $weblap->leallit();
    exit();
}

// Számla küldése igazolásra előtt a tételeket csoportosítjuk
if( isset($_POST['kuldes_igazolasra_csoportosit']) ) {
    
    // A tételeket költségnem + költséghely + ÁFAkulcs szerint csoportosíva küldjük vissza.
    $szamla   = $adatbazis_kapcsolat->s2('kcs_21', 'anyag_szamla', "`recid`='{$_POST['id']}'");
    $tetelek  = $adatbazis_kapcsolat->s('brutto_ert dev_br ktghely ktghely_id ktgnem_id afa_kod', 'anyag_szamla_tetel', "`anyag_szamla_recid`='{$_POST['id']}' AND `valtozott`='1'", '', '', "`ktgnem_id`, `ktghely_id`, `afa_kod`");
    $tetelek2 = $adatbazis_kapcsolat->s('recid', 'anyag_szamla_tetel', "`anyag_szamla_recid`='{$_POST['id']}' AND `valtozott`='1'");
    
    // Ha a csoportosított tételek száma azonos a rendes tételek számával,
    // akkor a tárgy mezőket előre kitöltjük
    $targy_kitoltheto = false;
    if( count($tetelek) == count($tetelek2) ) {
        $targy_kitoltheto = true;
    }   
    $tizedesek   = 0;
    $brutto_mezo = 'brutto_ert';
    if( $szamla['kcs_21'] != KCS_21_HUF ) {
        $tizedesek   = 2;
        $brutto_mezo = 'dev_br';
    }

    $i = 0;
    $html = nl2('
                <h1 id="osszevon_hiba" class="piros"></h1>
                <table class="csikos">
                <tr>
                    <th>Tárgy</th>
                    <th>Bruttó sorösszeg</th>
                    <th>Főkönyvi számlaszám</th>
                    <th>Költséghely</th>
                </tr>
                ');
    
    foreach( $tetelek as $tetel ) {
        $targy_val = '';
        if( $targy_kitoltheto ) {
            $targy_val .= ' value="'.htmlspecialchars_decode($tetel['targy']).'"';
        }
        $brutto_ertek = $adatbazis_kapcsolat->s2("SUM(`{$brutto_mezo}`)", 'anyag_szamla_tetel', "`anyag_szamla_recid`='{$_POST['id']}' AND `valtozott`='1' AND `ktgnem_id`='{$tetel['ktgnem_id']}' AND `ktghely_id`='{$tetel['ktghely_id']}' AND `afa_kod`='{$tetel['afa_kod']}'");
        $brutto_ertek = $brutto_ertek["SUM(`{$brutto_mezo}`)"];
        $html .= nl2('
                    <tr>
                        <td><input type="text" class="flat osszevon_targy" maxlength="250"'.$targy_val.'></td>
                        <td>'.number_format($brutto_ertek, $tizedesek, ',', ' ').' '.erteklista(ERTEKLISTA_PENZNEM, $szamla['kcs_21']).'</td>
                        <td>'.$tetel['ktgnem_id'].'</td>
                        <td>'.$tetel['ktghely'].'</td>
                    </tr>
                    ');
    }
    $html .= '</table>';


    echo json_encode(array(
                        'hiba'  => $html,
                        'gomb'  => '<input type="button" class="gomb zold" value="Mentés" onclick="osszevon_targy_kitoltve_ell();"><input type="button" class="gomb piros" value="Mégsem" onclick="alert2()">',
                        'title' => 'Kérem adja meg az összevont tételek tárgyait',
                        ));

    $weblap -> leallit();
    exit;
}

// Ha volt tétel összevonás és a tételeknél kitöltötték a tárgyat,
// akkor az itt megadott adatokat tároljuk le a fejben
if( isset($_POST['osszevon_targy_kitoltve_ell']) ) {
    
    $targyak = json_decode($_POST['targyak'], '-tömbként-');
    $szamla  = $adatbazis_kapcsolat->s2('kcs_21 telephely_id', 'anyag_szamla', "`recid`='{$_POST['id']}'");
    $tetelek = $adatbazis_kapcsolat->s('*', 'anyag_szamla_tetel', "`anyag_szamla_recid`='{$_POST['id']}' AND `valtozott`='1'", '', '', "`ktgnem_id`, `ktghely_id`, `afa_kod`");

    // A számla devizája dönti el, hogy a tételbe melyik nettó mező kerüljön
    if( $szamla['kcs_21'] == KCS_21_HUF ) {
        $netto_mezo  = 'netto_ert';
        $brutto_mezo = 'brutto_ert';
    } else {
        $netto_mezo  = 'dev_net';
        $brutto_mezo = 'dev_br';
    }
    
    $tetel_xml = '';
    $i = 0;
    foreach ( $tetelek as $tetel ) {
        $brutto_ertek = $adatbazis_kapcsolat->s2("SUM(`{$brutto_mezo}`)", 'anyag_szamla_tetel', "`anyag_szamla_recid`='{$_POST['id']}' AND `valtozott`='1' AND `ktgnem_id`='{$tetel['ktgnem_id']}' AND `ktghely_id`='{$tetel['ktghely_id']}' AND `afa_kod`='{$tetel['afa_kod']}'");
        $brutto_ertek = $brutto_ertek["SUM(`{$brutto_mezo}`)"];
        
        $tetel_xml .= '
            <row rownumberonpage="'.($i+1).'">
                <property name="Sor megnevezése"><![CDATA['.urlencode(htmlspecialchars_decode($targyak[$i])).']]></property>
                <property name="Bruttó sorösszeg">'.urlencode($brutto_ertek).'</property>
                <property name="Főkönyvi számlaszám">'.urlencode(htmlspecialchars_decode($tetel['ktgnem_id'])).'</property>
                <property name="Jogcím"><![CDATA['.urlencode(htmlspecialchars_decode($tetel['jogcim'])).']]></property>
                <property name="Üzemrész"><![CDATA['.urlencode(htmlspecialchars_decode($tetel['uzemresz'])).']]></property>
                <property name="Főkönyvi számlaszám megnevezése"><![CDATA['.urlencode(htmlspecialchars_decode($tetel['megn'])).']]></property>
                <property name="Költséghely">'.urlencode(htmlspecialchars_decode($tetel['ktghely_id'])).'</property>
                <property name="ÁFA kód">'.urlencode(htmlspecialchars_decode($tetel['afa_kod'])).'</property>
                <property name="ÁFA neve">'.urlencode(htmlspecialchars_decode($tetel['afa_nev'])).'</property>
                <property name="ÁFA %">'.urlencode(htmlspecialchars_decode($tetel['afa'])).'</property>
                <property name="Szálloda"><![CDATA['.urlencode(getSzallodaNev($szamla['telephely_id'], '-Andoc név-')).']]></property>
                <property name="Konszolidációs kód">00</property>
                <property name="Projektkód">00000</property>
                <property name="Egyedi azonosító">000000</property>
            </row>
            ';
        $i++;
    }
    
    $adatbazis_kapcsolat->u('anyag_szamla', 
                                            array('kuld_tetel_xml', 'tetel_db'  ),
                                            array($tetel_xml,       $i          ),
                                            "`recid`='{$_POST['id']}'");
    
    $weblap -> leallit();
    exit;
}

// Számla küldése igazolásra
if( isset($_POST['kuldes_igazolasra']) ) {
    
    $sor = $adatbazis_kapcsolat->s2('feldolgozva_dt', 'anyag_szamla', "`recid`='{$_POST['id']}'");
    if( $sor['feldolgozva_dt'] != '0000-00-00 00:00:00' ) {
        
        echo json_encode(array(
                            'hiba'  => 'A számla már feldolgozott, így nem küldhető újra igazolásra!',
                            'title' => 'Hiba',
                            ));
        
        $weblap -> leallit();
        exit;
    }
    
    $adatbazis_kapcsolat->u('anyag_szamla',  array('feldolgozva_dt', 'feldolgozva_usr'                       ),
                                             array('NOW()',          $_SESSION[get_db_tabla().'teljes_nev']  ),
                                             "`recid`='{$_POST['id']}'");
    
    echo json_encode(array(
                        'siker' => 1,
                        ));
    
    $weblap -> leallit();
    exit;
}

// Köztes tábla számláinak kilistázása
if( isset($_POST['anyag_szamla_get_koztes']) ) {
    
    // Köztes számlákból készítsünk táblázatot
    $html = '';
    $szamlak = $adatbazis_kapcsolat->s('recid andoc_id telephely_id szallito_id szla_szam telj_dt dok_link', 'anyag_szamla_atmeneti', "");
    if( $szamlak ) {
        $html .= nl2('
            <table class="csikos szamla_cont_tabla" id="pop_tetelek">
                <thead>
                <tr>
                    <th class="l">Szálloda</th>
                    <th class="l">Szállító</th>
                    <th class="l">Számlaszám</th>
                    <th class="c">Teljesítés dátuma</th>
                    <th class="c">Számla kép</th>
                </tr>
                </thead>
                <tbody>
            ');
        foreach( $szamlak as $szamla ) {
            $link = '';
            if( $szamla['dok_link'] ) {
                $link .= nl2('
                            <a href="'.$szamla['dok_link'].'" target="_blank">
                                <img src="../elemek/admin/file.svg" title="Fájl megnyitása" alt="Fájl megnyitása" width="24">
                            </a>
                            ');
            }
            $html .= nl2('
                    <tr data-andoc_id="'.$szamla['andoc_id'].'">
                        <td class="l">'.getSzallodaNev($szamla['telephely_id']).'</td>
                        <td class="l">'.getErtek('nev', 'szallito', 'id', $szamla['szallito_id']).'</td>
                        <td class="l">'.htmlspecialchars($szamla['szla_szam']).'</td>
                        <td class="c">'.datum_kiir($szamla['telj_dt']).'</td>
                        <td class="c">'.$link.'</td>
                    </tr>
                ');
        }
        $html .= nl2('
                <tbody>
                </table>
            ');
    } else {
        $html .= '<script>popupZar(); alert2(\'Jelenleg nem található feldolgozandó számla.\', \'\', \'Hiba\')</script>';
    }
    
    echo json_encode( array('html' => $html) );
    
    $weblap -> leallit();
    exit;
}

// Számla igazolása
if( isset($_POST['szamla_igazol']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Ha már igazolva volna, akkor hibaüzenet
    $sor = $adatbazis_kapcsolat->s2('igazolva_dt', 'anyag_szamla', "`recid`='{$_POST['id']}'");
    if( $sor['igazolva_dt']!='0000-00-00 00:00:00' ) {
        echo json_encode(array(
                                'hiba'  => 'A számla már igazolva van, így nem igazolható újból!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }
    
    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(SZAMLAK_IGAZOLASA) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga számlát igazolni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }
    
    
    // Illetve az XML visszát is intézzük
    $xml_vissza = xml_vissza($_POST['id'], 'Igazolva', '');
    if( $xml_vissza !== false ) {
        // Ha nem csak egy 1-es kaptunk, akkor abban van egy hibaüzenet még, amit ki kell írni
        $extra_hibauzenet = '';
        if( $xml_vissza != '1' ) {
            $extra_hibauzenet = '<br>'.$xml_vissza;
        }
        echo json_encode(array(
                                'hiba'  => 'Az Andoc felé küldött visszaigazoló XML elküldése során hiba történt!<br>A számlát nem sikerült igazolni.'.$extra_hibauzenet,
                                'title' => 'Hiba',
                        ));
        mysqli_commit($db_kapcs);
        $weblap -> leallit();
        exit;
        
    // Adatbázisba mentés csak akkor, ha nem volt gond az XML küldéssel
    } else {
        $sql = $adatbazis_kapcsolat->u('anyag_szamla', array('igazolva', 'igazolva_dt', 'igazolva_usr'                           ),
                                                       array(1,          'NOW()',       $_SESSION[get_db_tabla().'teljes_nev']   ),
                                        "`recid`='{$_POST['id']}'");
        if( $sql === false ) { $db_error = true; }        
    }
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array('siker' => 1 ) );
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

// Számla elutasít
if( isset($_POST['anyagSzamlaElutasit']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(SZAMLAK_IGAZOLASA) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga számlát elutasítani!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }
    
    // Illetve az XML visszát is intézzük
    $xml_vissza = xml_vissza($_POST['id'], 'Elutasítva', $_POST['memo']);
    if( $xml_vissza !== false ) {
        // Ha nem csak egy 1-es kaptunk, akkor abban van egy hibaüzenet még, amit ki kell írni
        $extra_hibauzenet = '';
        if( $xml_vissza != '1' ) {
            $extra_hibauzenet = '<br>'.$xml_vissza;
        }
        echo json_encode(array(
                                'hiba'  => 'Az Andoc felé küldött visszaigazoló XML elküldése során hiba történt!<br>A számlát nem sikerült elutasítani.'.$extra_hibauzenet,
                                'title' => 'Hiba',
                        ));
        mysqli_commit($db_kapcs);
        $weblap -> leallit();
        exit;
        
    // Adatbázisba mentés csak akkor, ha nem volt gond az XML küldéssel
    } else {
        // Elutasítással kapcsolatos adatok beállítása, illetve a kapcsolt számlaszám ürítése
        $sql = $adatbazis_kapcsolat->u('anyag_szamla',    array('igazolva', 'sz_szam', 'elutleir',     'igazolva_dt', 'igazolva_usr',                          'sztornozva_dt', 'sztornozva_usr'                        ),
                                                          array(0,          '',        $_POST['memo'], 'NOW()',       $_SESSION[get_db_tabla().'teljes_nev'],  'NOW()',         $_SESSION[get_db_tabla().'teljes_nev']  ),
                                        "`recid`='{$_POST['id']}'");
        if( $sql === false ) { $db_error = true; }
        
        // Anyagigényléseknél csökkenteni kell a számlázott mennyiséget
        $tetelek = $adatbazis_kapcsolat->s('rnd_recid rndmen', 'anyag_szamla_tetel', "`anyag_szamla_recid`='{$_POST['id']}'");
        if( $tetelek === false ) { $db_error = true; }
        foreach ( $tetelek as $tetel ) {
            if( $tetel['rnd_recid'] ) {
                $rnd = $adatbazis_kapcsolat->s2('szlamen', 'anyagigenyles', "`recid`='{$tetel['rnd_recid']}'");
                if( $rnd === false ) { $db_error = true; }
                $sql = $adatbazis_kapcsolat -> u('anyagigenyles', array('szlamen'), array($rnd['szlamen']-$tetel['rndmen']), "`recid`='{$tetel['rnd_recid']}'");
                if( $sql === false ) { $db_error = true; }
            }
        }
        
        // Megjegyzésbe is kerüljön be az indoklás
        if( addMegj('anyag_szamla', $_POST['id'], 'Teljesítés igazolás elutasítva.<br>Indoklás: '.nl2br2($_POST['memo'])) === false ) {
            $db_error = true;
        }
        
    }
                                                
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array('siker' => 1 ) );
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

// Számla vissza ügyintézőnek
if( isset($_POST['anyagSzamlaVisszaUi']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(SZAMLAK_IGAZOLASA) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga a számlát visszaküldeni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }
    
    // Visszaküldés SQL-je
    $sql = $adatbazis_kapcsolat->u('anyag_szamla',  array('feldolgozva_dt',      'feldolgozva_usr'  ),
                                                    array('0000-00-00 00:00:00', ''                 ),
                                                    "`recid`='{$_POST['recid']}'");
    if( $sql === false ) { $db_error = true; }
    
    // Megjegyzésben logoljuk
    if( addMegj('anyag_szamla', $_POST['recid'], 'Igazoló visszaadta számlát ügyintézőnek.<br>Indoklás: '.nl2br2($_POST['memo'])) === false ) {
        $db_error = true;
    }
                                                
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array('siker' => 1 ) );
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

// Számla sztornózása
if( isset($_POST['anyagSzamlaSztornoz']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_ANYAG_SZAMLAK_KEZEL) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga számlát sztornózni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }
        
    // Sztornózással kapcsolatos adatok beállítása, illetve a kapcsolt számlaszám ürítése
    $sql = $adatbazis_kapcsolat->u('anyag_szamla',  array('sz_szam', 'sztornozva_leir',   'sztornozva_dt', 'sztornozva_usr'                       ),
                                                    array('',        $_POST['memo'],      'NOW()',         $_SESSION[get_db_tabla().'teljes_nev'] ),
                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }
    
    // Anyagigényléseknél csökkenteni kell a számlázott mennyiséget
    $tetelek = $adatbazis_kapcsolat->s('rnd_recid rndmen', 'anyag_szamla_tetel', "`anyag_szamla_recid`='{$_POST['id']}'");
    if( $tetelek === false ) { $db_error = true; }
    foreach ( $tetelek as $tetel ) {
        if( $tetel['rnd_recid'] ) {
            $rnd = $adatbazis_kapcsolat->s2('szlamen', 'anyagigenyles', "`recid`='{$tetel['rnd_recid']}'");
            if( $rnd === false ) { $db_error = true; }
            $sql = $adatbazis_kapcsolat -> u('anyagigenyles', array('szlamen'), array($rnd['szlamen']-$tetel['rndmen']), "`recid`='{$tetel['rnd_recid']}'");
            if( $sql === false ) { $db_error = true; }
        }
    }
    
    // Megjegyzésbe is kerüljön be az indoklás
    if( addMegj('anyag_szamla', $_POST['id'], 'Számla sztornózva.<br>Indoklás: '.nl2br2($_POST['memo'])) === false ) {
        $db_error = true;
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array('siker' => 1 ) );
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

// Tétel sorok kiíratása
if( isset($_POST['pop_tetelek_feltolt']) ) {
    
    // Ha van a GET-ben andoc_id, akkor azt itt $_POST-ként kapjuk meg, ami annyit jelent, 
    // hogy egy új számla felvitelénél vagyunk, új feltöltési módban, és az átmeneti táblából kell dolgozni
    if( $_POST['andoc_id'] ) {
        $szamla = $adatbazis_kapcsolat->s2('recid kcs_21', 'anyag_szamla_atmeneti', "`andoc_id`='{$_POST['andoc_id']}'");
    } else {
        $szamla = $adatbazis_kapcsolat->s2('kcs_21', 'anyag_szamla', "`recid`='{$_POST['id']}'");
    }
    
    // Illetve ezt az alábbi változókkal két ágra bontuk: Ft-os vagy devizás-e a számla.
    if( $szamla['kcs_21'] && $szamla['kcs_21']!=KCS_21_HUF ) {
        $szamla_netto_mezo      = 'dev_net';
        $penznem                = erteklista(ERTEKLISTA_PENZNEM, $szamla['kcs_21']);
        $tizedesek              = 2;
        $session_mezo           = 'anyag_szla_tetel_dev_net';
        $session_mezo_br        = 'anyag_szla_tetel_dev_br';
    } else {
        $szamla_netto_mezo      = 'netto_ert';
        $penznem                = get_penznem();
        $tizedesek              = 0;
        $session_mezo           = 'anyag_szla_tetel_netto_ert';
        $session_mezo_br        = 'anyag_szla_tetel_brutto_ert';
    }
    
    // Tételek táblázat sorait kiszámoljuk:
    $html = '';
    $i = 0;
    foreach( $_SESSION['anyag_szla_tetel_cikktorzs_id'] as $cikktorzs_id ) {
        
        $szla_netto_ert = $_SESSION[$session_mezo][$i];
        $torles = '';
        if( !$_SESSION['anyag_szla_tetel_andocos'][$i] ) {
            $torles = '
                        <a onclick="popup_tetel_sor_torol(\'pop_tetel_'.$_SESSION['anyag_szla_tetel_recid'][$i].'\');">
                            <span class="icon">
                                <i class="material-icons">
                                    delete
                                </i>
                            </span>
                        </a>
                ';
        }
        
        // Minden sornak adunk egy azonosítót. Mivel lehet csak egy ideiglenes sorról van szó, nem mindig van recid-nk, ezért időbélyeges azonosítót hozunk létre, megtoldva egy $i-vel.
        if( !$_SESSION['anyag_szla_tetel_recid'][$i] ) {
            $_SESSION['anyag_szla_tetel_recid'][$i] = time().rand(0,1000);
        }
        $class = '';
        
        $jogcim = $adatbazis_kapcsolat->s2('jogcim ktghely megn', 'jogcim', "`recid`='{$_SESSION['anyag_szla_tetel_jogcim'][$i]}'");
        $html .= nl2('
                        <tr class="pop_tetel_'.$_SESSION['anyag_szla_tetel_recid'][$i].'">
                            <td class="pop_cikktorzs l">'.htmlspecialchars($_SESSION['anyag_szla_tetel_cikktorzs'][$i]).'</td>
                            <td class="pop_szall_csz l">'.htmlspecialchars($_SESSION['anyag_szla_tetel_szall_csz'][$i]).'</td>
                            <td class="pop_cikktorzs_id c">'.htmlspecialchars($_SESSION['anyag_szla_tetel_cikktorzs_id'][$i]).'</td>
                            <td class="pop_rndmen r nowrap">'.htmlspecialchars($_SESSION['anyag_szla_tetel_rndmen'][$i]).'</td>
                            <td class="pop_rndear r nowrap">'.htmlspecialchars($_SESSION['anyag_szla_tetel_rndear'][$i]).'</td>
                            <td class="pop_cikkmee c">'.htmlspecialchars($_SESSION['anyag_szla_tetel_cikkmee'][$i]).'</td>
                            <td class="pop_netto_ert r nowrap">'.$_SESSION[$session_mezo][$i].'</td>
                            <td class="pop_brutto_ert r nowrap">'.$_SESSION[$session_mezo_br][$i].'</td>
                            <td class="pop_jogcim_autocomp l">'.htmlspecialchars($jogcim['jogcim']).'</td>
                            <td class="pop_ktgnem_autocomp l">
                                '.$jogcim['megn'].'
                            </td>
                            <td class="pop_ktghely_autocomp l">
                                '.$jogcim['ktghely'].'
                            </td>
                            <td>
                                '.$_SESSION['anyag_szla_tetel_rndid'][$i].'
                            </td>
                            <!--<td>
                                <a class="szerk" onclick="popup_tetel_sor_szerk(\'pop_tetel_'.$_SESSION['anyag_szla_tetel_recid'][$i].'\');">
                                    🖉
                                </a>
                            </td>-->
                            <td>
                                '.$torles.'
                                <input type="hidden" class="pop_rndid" name="pop_rndid" value="'.$_SESSION['anyag_szla_tetel_rndid'][$i].'">
                                <input type="hidden" class="pop_rnd_recid" name="pop_rnd_recid" value="'.$_SESSION['anyag_szla_tetel_rnd_recid'][$i].'">
                                <input type="hidden" class="pop_afakod" name="pop_afakod_id" value="'.$_SESSION['anyag_szla_tetel_afakod'][$i].'">
                                <input type="hidden" class="pop_jogcim" name="pop_jogcim_id" value="'.$_SESSION['anyag_szla_tetel_jogcim'][$i].'">
                                <input type="hidden" class="pop_valtozott" value="'.$_SESSION['anyag_szla_tetel_valtozott'][$i].'">
                                <input type="hidden" class="pop_recid" name="recid" value="'.$_SESSION['anyag_szla_tetel_recid'][$i].'">
                            </td>
                        </tr>');
        $i++;
    }
   
    echo json_encode( array(
        'html' => $html,
        ) );
    
    $weblap -> leallit();
    exit();
}

// Tétel hozzáadásakor ellenőrizzük le, hogy a mennyiség és az egység ár értéke rendben van-e,
// feltéve ha ismert a rendelés sor
if( isset($_POST['menny_ear_ell']) ) {
    
    $_POST['rndmen']            = str_replace(array(' ', ','), array('', '.'), $_POST['rndmen']);
    $_POST['rndear']            = str_replace(array(' ', ','), array('', '.'), $_POST['rndear']);
    $_POST['torolt_rndrecidk']  = json_decode($_POST['torolt_rndrecidk']);
    $_POST['torolt_rnd_szlamen']= json_decode($_POST['torolt_rnd_szlamen']);
    
    if( $_POST['rnd_recid'] ) {
        
        $anyagig = $adatbazis_kapcsolat->s2('recid rndmen szlamen cikkmee rndear kcs_21', 'anyagigenyles', "`recid`='{$_POST['rnd_recid']}'");
        $i = 0;
        // Nézzük át a logiailag törölt számla tétel sorokat. Ha ebben van egyezés az aktuális rendelés recid-val,
        // akkor az annak megfelelő számlázott mennyiségével csökkentsük a rendelés sor számlázott mennyiségét
        // Tehát itt azt kell lekezelni, hogy a popupon már törölt, de adatbázisba még nem beírt tétel
        // számlázott mennyiségét vonjuk ki a már számlázott mennyiségből
        foreach ( $_POST['torolt_rndrecidk'] as $torolt_recid ) {
            if( $torolt_recid == $anyagig['recid'] ) {
                $anyagig['szlamen'] -= str_replace(array(',', ' '), array('.', ''), $_POST['torolt_rnd_szlamen'][$i]);
            }
            $i++;
        }
        
        // Nem számlázhat többet, mint a rendelt mennyiség-szamlazott mennyiség
        if( number_format($_POST['rndmen'], 2, '.', '') > number_format($anyagig['rndmen']-$anyagig['szlamen'], 2, '.', '')  ) {
            echo json_encode( array(
                'hiba'  => nl2('
                                Rendelt mennyiség: '.number_format($anyagig['rndmen'], 2, ',', ' ').' '.$anyagig['cikkmee'].'
                                <br>Már számlázott: '.number_format($anyagig['szlamen'], 2, ',', ' ').' '.$anyagig['cikkmee'].'
                                <br>Legfeljebb '.number_format($anyagig['rndmen']-$anyagig['szlamen'], 2, ',', ' ').' '.$anyagig['cikkmee'].' számlázható!
                            '),
                'title' => 'Hiba',
                ) );
            $weblap -> leallit();
            exit();
        }
        
        // Az egységár nem lehet több mint a rendelés egységára
        if( $_POST['rndear'] > $anyagig['rndear'] ) {
            echo json_encode( array(
                'hiba'  => nl2('A számlázott tétel egységára nem lehet nagyobb, mint a rendelési egységár ('.number_format($anyagig['rndear'], 4, ',', ' ').' '.erteklista(ERTEKLISTA_PENZNEM, $anyagig['kcs_21']).')!'),
                'title' => 'Hiba',
                ) );
            $weblap -> leallit();
            exit();
        }
        
    }
    
    echo json_encode( array(
        'mehet'  => 1,
        ) );
    
    $weblap -> leallit();
    exit();
}

// Tételek mentése
if( isset($_POST['tetel_sorok_ment']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    if( $_POST['recid'] == 'false' ) {
        $_POST['recid'] = '';
    }
    $_POST['torolt_rndrecidk']  = json_decode($_POST['torolt_rndrecidk']);
    $_POST['torolt_rnd_szlamen']= json_decode($_POST['torolt_rnd_szlamen']);
    
    $recid                  = $_POST['recid'];
    $szamla_netto_erteke    = str_replace(array(' ', ','), array('', '.'), $_POST['szamla_netto_erteke']);
    $szamla_brutto_erteke   = str_replace(array(' ', ','), array('', '.'), $_POST['szamla_brutto_erteke']);
    $_POST['adatok']        = json_decode( $_POST['adatok'] );
    $arfolyam               = str_replace(array(' ', ','), array('', '.'), $_POST['arfolyam']);
    
    // Kapott tömb indexeit cseréljük beszédesebb változókra
    $recid_adat         = 0;
    $rndid_adat         = 1;
    $rnd_recid_adat     = 2;
    $cikktorzs_adat     = 3;
    $szall_csz_adat     = 4;
    $cikktorzs_id_adat  = 5;
    $rndmen_adat        = 6;
    $rndear_adat        = 7;
    $cikkmee_adat       = 8;
    $netto_ert_adat     = 9;
    $brutto_ert_adat    = 10;
    $afakod_adat        = 11;
    $jogcim_adat        = 12;
    $valtozott_adat     = 13;

    $tetelek_osszege = 0;
    $i = 0;
    foreach ( $_POST['adatok'][$netto_ert_adat][1] as $ertek ) {
        $_POST['adatok'][$netto_ert_adat][1][$i] = str_replace(array(' ', ','), array('', '.'), $ertek);
        $tetelek_osszege += $_POST['adatok'][$netto_ert_adat] [1][$i];
        $i++;
    }
    $tetelek_br_osszege = 0;
    $i = 0;
    foreach ( $_POST['adatok'][$brutto_ert_adat][1] as $ertek ) {
        $_POST['adatok'][$brutto_ert_adat][1][$i]   = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$brutto_ert_adat][1][$i]);
        $_POST['adatok'][$rndmen_adat][1][$i]       = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$rndmen_adat][1][$i]);
        $_POST['adatok'][$rndear_adat][1][$i]       = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$rndear_adat][1][$i]);
        $tetelek_br_osszege += $_POST['adatok'][$brutto_ert_adat][1][$i];
        $i++;
    }

    // Deviza esetén tört számokkal is számolunk
    $penznem   = get_penznem();
    $devizas   = false;
    $tizedesek = 0;
    $db_mezo   = 'netto_ert';
    $db_mezo_br= 'brutto_ert';
    if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
        $devizas   = true;
        $tizedesek = 2;
        $penznem   = erteklista(ERTEKLISTA_PENZNEM, $_POST['kcs_21']);
        $db_mezo   = 'dev_net';
        $db_mezo_br= 'dev_br';
    }
    
    // Ha nincs egy sornyi tételünk sem, akkor hibaüzenet
    if( !$_POST['adatok'][$cikktorzs_id_adat][1] ) {
        echo json_encode( array( 
                        'hiba'  => 'Legalább egy számla tételnek szerepelnie kell a táblázatban, üresen nem menthető!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
                
    // Ha a számla nettó értéke nem egyenlő a tételek nettó összegével, akkor hibaüzenet
    } elseif( number_format($tetelek_osszege, $tizedesek, ',', ' ') != number_format($szamla_netto_erteke, $tizedesek, ',', ' ') ) {
        echo json_encode( array( 
                        'hiba'  => 'A számla tételeinek nettó összege ('. number_format($tetelek_osszege, $tizedesek, ',', ' ').' '.$penznem.') nem egyezik meg a számla nettó értékével ('.number_format($szamla_netto_erteke, $tizedesek, ',', ' ').' '.$penznem.')!<br>Így nem menthető!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
                
    // Ha a számla bruttó értéke nem egyenlő a tételek bruttó összegével, akkor hibaüzenet
    } elseif( number_format($tetelek_br_osszege, $tizedesek, ',', ' ') != number_format($szamla_brutto_erteke, $tizedesek, ',', ' ') ) {
        echo json_encode( array( 
                        'hiba'  => 'A számla tételeinek bruttó összege ('. number_format($tetelek_br_osszege, $tizedesek, ',', ' ').' '.$penznem.') nem egyezik meg a számla bruttó értékével ('.number_format($szamla_brutto_erteke, $tizedesek, ',', ' ').' '.$penznem.')!<br>Így nem menthető!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
                
    // Tételek kimentése Session-be
    } else {
        $_SESSION['anyag_szla_tetel_recid']         = $_POST['adatok'][$recid_adat][1];
        $_SESSION['anyag_szla_tetel_rndid']         = $_POST['adatok'][$rndid_adat][1];
        $_SESSION['anyag_szla_tetel_rnd_recid']     = $_POST['adatok'][$rnd_recid_adat][1];
        $_SESSION['anyag_szla_tetel_cikktorzs']     = str_replace('{IDEZOJEL}', '"', $_POST['adatok'][$cikktorzs_adat][1]);
        $_SESSION['anyag_szla_tetel_szall_csz']     = str_replace('{IDEZOJEL}', '"', $_POST['adatok'][$szall_csz_adat][1]);
        $_SESSION['anyag_szla_tetel_cikktorzs_id']  = $_POST['adatok'][$cikktorzs_id_adat][1];
        $_SESSION['anyag_szla_tetel_rndmen']        = $_POST['adatok'][$rndmen_adat][1];
        $_SESSION['anyag_szla_tetel_rndear']        = $_POST['adatok'][$rndear_adat][1];
        $_SESSION['anyag_szla_tetel_cikkmee']       = $_POST['adatok'][$cikkmee_adat][1];
        if( $devizas ) {
            $_SESSION['anyag_szla_tetel_dev_net'] = str_replace(',', '.', $_POST['adatok'][$netto_ert_adat][1]);
            $_SESSION['anyag_szla_tetel_dev_br']  = str_replace(',', '.', $_POST['adatok'][$brutto_ert_adat][1]);
        } else {
            $_SESSION['anyag_szla_tetel_netto_ert']  = str_replace(',', '.', $_POST['adatok'][$netto_ert_adat][1]);
            $_SESSION['anyag_szla_tetel_brutto_ert'] = str_replace(',', '.', $_POST['adatok'][$brutto_ert_adat][1]);
        }
        $_SESSION['anyag_szla_tetel_afakod']    = $_POST['adatok'][$afakod_adat][1];
        $_SESSION['anyag_szla_tetel_jogcim']    = $_POST['adatok'][$jogcim_adat][1];
        $_SESSION['anyag_szla_tetel_valtozott'] = $_POST['adatok'][$valtozott_adat][1];
    }

    // Adatbázisba mentést csak akkor csinálnuk, ha már meglevő számlát szerkesztünk
    $anyag_rend_idk = array();
    if( $recid ) {
        $i = 0;
        foreach ( $_POST['adatok'][$cikktorzs_id_adat][1] as $cikktorzs_id ) {

            $rndid          = $_POST['adatok'][$rndid_adat][1][$i];
            $rnd_recid      = $_POST['adatok'][$rnd_recid_adat][1][$i];
            $cikktorzs      = $_POST['adatok'][$cikktorzs_adat][1][$i];
            $szall_csz      = $_POST['adatok'][$szall_csz_adat][1][$i];
            $cikktorzs_id   = $_POST['adatok'][$cikktorzs_id_adat][1][$i];
            $rndmen         = $_POST['adatok'][$rndmen_adat][1][$i];
            $rndear         = $_POST['adatok'][$rndear_adat][1][$i];
            $cikkmee        = $_POST['adatok'][$cikkmee_adat][1][$i];
            $netto          = $_POST['adatok'][$netto_ert_adat][1][$i];
            $brutto         = $_POST['adatok'][$brutto_ert_adat][1][$i];
            $afakod         = $_POST['adatok'][$afakod_adat][1][$i];
            $targy          = $_POST['adatok'][$targy_adat][1][$i];
            $jogcim         = $_POST['adatok'][$jogcim_adat][1][$i];
            $ktgnem         = $_POST['adatok'][$ktgnem_adat][1][$i];
            $ktghely        = $_POST['adatok'][$ktghely_adat][1][$i];
            $valtozott      = 0;
            if( $_POST['adatok'][$valtozott_adat][1][$i] ) {
                $valtozott = 1;
            }
            
            // Jogcím/Áfakód esetén nem csak egy recid-t tárolunk le, hanem a tábla oszlopait, ezért lekérjük a megfelelő sort
            $jogcim = $adatbazis_kapcsolat->s2('recid jogcim uzemresz megn szla_szam ktghely ktghely_id', 'jogcim', "`recid`='{$jogcim}'");
            $afakod = $adatbazis_kapcsolat->s2('recid nev kod afa', 'afakod', "`recid`='{$afakod}'");
            
            // Rendelés azonosítókat is szedjük össze
            array_push($anyag_rend_idk, $rndid);
            
            // Ha ismerjük a tétel recid-ját, akkor update
            if( $_POST['adatok'][$recid_adat][1][$i] ) {
                
                $mezok   = array('rndid', 'rnd_recid', 'cikktorzs', 'szall_csz', 'cikktorzs_id', 'rndmen', 'rndear', 'cikkmee', $db_mezo, $db_mezo_br, 'ktgnem_id',           'ktghely_id',           'ktghely',          'jogcim_recid',   'jogcim',          'uzemresz',          'megn',          'afakod_recid',   'afa',          'afa_kod',      'afa_nev',      'valtozott', 'update_usr'                             );
                $ertekek = array($rndid,  $rnd_recid,  $cikktorzs,  $szall_csz,  $cikktorzs_id,  $rndmen,  $rndear,  $cikkmee,  $netto,   $brutto,     $jogcim['szla_szam'],  $jogcim['ktghely_id'],  $jogcim['ktghely'], $jogcim['recid'], $jogcim['jogcim'], $jogcim['uzemresz'], $jogcim['megn'], $afakod['recid'], $afakod['afa'], $afakod['kod'], $afakod['nev'], $valtozott,  $_SESSION[get_db_tabla().'teljes_nev']   );
                // Devizás számla esetén a Ft-os nettó/bruttó értékeket is számoljuk ki és írjuk be
                if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
                    array_push($mezok,   'netto_ert',       'brutto_ert'        );
                    array_push($ertekek, $netto*$arfolyam,  $brutto*$arfolyam   );
                }
                $sql = $adatbazis_kapcsolat->u('anyag_szamla_tetel', $mezok, $ertekek, "`recid`='{$_POST['adatok'][$recid_adat][1][$i]}'");
                if( $sql === false ) { $db_error = true; }
                
            // Egyébként insert
            } else {

                $mezok   = array('anyag_szamla_recid', 'rndid', 'rnd_recid', 'cikktorzs', 'szall_csz', 'cikktorzs_id', 'rndmen', 'rndear', 'cikkmee',  $db_mezo, $db_mezo_br, 'ktgnem_id',          'ktghely_id',           'ktghely',            'jogcim_recid',   'jogcim',          'uzemresz',          'megn',          'afakod_recid',   'afa',          'afa_kod',      'afa_nev',        'valtozott', 'loginnev',                          'insert_dt',    'insert_usr',                             'update_usr'                              );
                $ertekek = array($recid,               $rndid,  $rnd_recid,  $cikktorzs,  $szall_csz,  $cikktorzs_id,  $rndmen,  $rndear,  $cikkmee,   $netto,   $brutto,     $jogcim['szla_szam'], $jogcim['ktghely_id'],  $jogcim['ktghely'],   $jogcim['recid'], $jogcim['jogcim'], $jogcim['uzemresz'], $jogcim['megn'], $afakod['recid'], $afakod['afa'], $afakod['kod'], $afakod['nev'],   '1',          $_SESSION[get_db_tabla().'user'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']    );
                // Devizás számla esetén a Ft-os nettó/bruttó értékeket is számoljuk ki és írjuk be
                if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
                    array_push($mezok,   'netto_ert',       'brutto_ert'        );
                    array_push($ertekek, $netto*$arfolyam,  $brutto*$arfolyam   );
                }
                $uj_id = $adatbazis_kapcsolat->i('anyag_szamla_tetel', $mezok, $ertekek);
                if( $sql === false ) { $db_error = true; }
                $_SESSION['anyag_szla_tetel_recid'][$i] = $uj_id;
            }

            // Ha ismert a rendelés azonosítója, akkor írjuk be a rendelés sorába a számlázott mennyiséget
            if( $rnd_recid ) {
                $anyagig = $adatbazis_kapcsolat->s2('recid szlamen', 'anyagigenyles', "`recid`='{$rnd_recid}'");
                if( $anyagig === false ) { $db_error = true; }
 
                // Nézzük át a logiailag törölt számla tétel sorokat. Ha ebben van egyezés az aktuális rendelés recid-val,
                // akkor az annak megfelelő számlázott mennyiségével csökkentsük a rendelés sor számlázott mennyiségét
                // Tehát itt azt kell lekezelni, hogy a popupon már törölt, de adatbázisba még nem beírt tétel
                // számlázott mennyiségét vonjuk ki a már számlázott mennyiségből
                $j = 0;
                foreach ( $_POST['torolt_rndrecidk'] as $torolt_recid ) {
                    if( $torolt_recid == $anyagig['recid'] ) {
                        $anyagig['szlamen'] -= str_replace(array(',', ' '), array('.', ''), $_POST['torolt_rnd_szlamen'][$j]);
                        // Egyúttal ki is nullázzuk, hogy később ezeket már ne dolgozzuk fel
                        $_POST['torolt_rndrecidk'][$j]   = 0;
                        $_POST['torolt_rnd_szlamen'][$j] = 0;
                    }
                    $j++;
                }

                $sql = $adatbazis_kapcsolat->u('anyagigenyles', array('szlamen'), array($anyagig['szlamen']+$rndmen), "`recid`='{$rnd_recid}'");
                if( $sql === false ) { $db_error = true; }

            }
            $i++;
        }
        
        // Ha kell a törölt sorok törlése adatbázisból is
        $_POST['torolt_sorok'] = trim($_POST['torolt_sorok'], 'pop_tetel_');
        $torlendo_idk = explode('pop_tetel_', $_POST['torolt_sorok']);
        if( count($torlendo_idk) ) {
            $sql = $adatbazis_kapcsolat->d('anyag_szamla_tetel', "`recid` IN ('". implode('\', \'', $torlendo_idk)."')");
            if( $sql === false ) { $db_error = true; }
        }
        
        // Fejben frissítsük az anyagrendeléseket
        $anyag_rend_idk = array_unique($anyag_rend_idk);
        $anyag_rend_idk = implode(' ', $anyag_rend_idk);
        sort($anyag_rend_idk);
        $sql = $adatbazis_kapcsolat->u('anyag_szamla',  array('sz_szam'         ),
                                                        array($anyag_rend_idk   ),
                                                        "`recid`='{$recid}'");
        if( $sql === false ) { $db_error = true; }
    }
    
    // Ha itt járunk előfordulhat, hogy vannak még fel nem dolgozott törölt sorok:
    $i = 0;
    foreach ( $_POST['torolt_rndrecidk'] as $torolt_recid ) {
        if( $torolt_recid ) {
            $anyagig = $adatbazis_kapcsolat->s2('recid szlamen', 'anyagigenyles', "`recid`='{$torolt_recid}'");
            if( $anyagig === false ) { $db_error = true; }
            
            $torolt_men = str_replace(array(',', ' '), array('.', ''), $_POST['torolt_rnd_szlamen'][$i]);
            $sql = $adatbazis_kapcsolat->u('anyagigenyles', array('szlamen'), array($anyagig['szlamen']-$torolt_men), "`recid`='{$torolt_recid}'");
            if( $sql === false ) { $db_error = true; }
        }
        $i++;
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array('siker' => 1, 'netto' => number_format($tetelek_osszege, $tizedesek, ',', ' ')) );
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba'
        ));
    }
    
    $weblap -> leallit();
    exit();
}

// Kiválasztott számla tételeinek felsorolása
if( isset($_POST['get_szamla_tetelek']) ) {
    
    if( _jog(JOG_ANYAG_SZAMLAK_KEZEL) ) {
        
        // Javasriptből kaptunk egy tömbböt, ilyen formában: tomb[szamla_tetel_recid] = anyagrendeles_recid
        $kapcsolasok = json_decode($_POST['kapcsolasok'], 'tömbbe');

        // Tételek bejárása
        $tetelek = $adatbazis_kapcsolat->s('recid ktgnem_id netto_ert cikk_id mee szlamen szlaear', 'anyag_szamla_tetel', "`anyag_szamla_recid`='{$_POST['szamla_id']}'");

        $tablazat = nl2('
            <div style="max-height: 40rem; overflow-y: auto; overflow-x: none;">
                <table id="feld_var_szamlak" class="csikos v2 fix_fejlec hoveres">
                <tr>
                    <th>Név</th>
                    <th>Mee</th>
                    <th>Rendelt mennyiség</th>
                    <th>Beérkezett mennyiség</th>
                    <th>Már számlázott mennyiség</th>
                    <th>Számlázott mennyiség</th>
                    <th>Rendelési egységár</th>
                    <th>Számlázott egységár</th>
                    <th>Nettó érték</th>
                    <th>Cikkszám</th>
                    <th>Cikknév</th>
                    <th>Rendelés kiválasztása</th>
                    <th>Módosító tétel</th>
                </tr>
            ');
        foreach ( $tetelek as $tetel ) {

            // Ha már van kacsolat a számla tétel és az anyagrendelés tétel között, akkor a rendelés adatait is kitöltjük
            $anyagrend = '';
            if( $kapcsolasok[$tetel['recid']] ) {
                $anyagrend = $adatbazis_kapcsolat->s2('nev rndmen erkmen szlamen rndear', 'anyagigenyles', "`recid`='{$kapcsolasok[$tetel['recid']]}'");
            }
            
            $hibas_sor = '';
            if( $anyagrend['erkmen'] && $anyagrend['erkmen'] < $tetel['szlamen'] ) {
                $hibas_sor = ' class="piros"';
            }
            if( $anyagrend['rndear'] && $anyagrend['rndear'] < $tetel['szlaear'] ) {
                $hibas_sor = ' class="piros"';
            }

            $tablazat .= nl2('
                <tr'.$hibas_sor.' data-recid="'.$tetel['recid'].'">
                    <td>?</td>
                    <td>'.$tetel['mee'].'</td>
                    <td class="nowrap">'.number_format($anyagrend['rndmen'], 2, ',', ' ').'</td>
                    <td class="nowrap">'.number_format($anyagrend['erkmen'], 2, ',', ' ').'</td>
                    <td>'.number_format($anyagrend['szlamen'], 4, ',', ' ').'</td>
                    <td class="nowrap">'.number_format($tetel['szlamen'], 2, ',', ' ').'</td>
                    <td class="nowrap">'.number_format($anyagrend['rndear'], 4, ',', ' ').'</td>
                    <td class="nowrap">'.number_format($tetel['szlaear'], 4, ',', ' ').'</td>
                    <td class="nowrap">'.number_format($tetel['netto_ert'], 0, ',', ' ').'</td>
                    <td>'.$tetel['cikk_id'].'</td>
                    <td>'.$anyagrend['nev'].'</td>
                    <td><input type="button" class="gomb alacsony" value="Kiválaszt" onclick="anyag_szamla_tetel_rendeles_kivalaszt(\''.$tetel['recid'].'\')"></td>
                    <td>
                        <div class="cb">
                            <input type="checkbox" class="check" id="modosit_'.$tetel['recid'].'">
                            <label for="modosit_'.$tetel['recid'].'"></label>
                        </div>
                    </td>
                </tr>
                ');
        }

        $tablazat .= nl2('
                </table>
            </div>
            ');

        echo json_encode( array('tablazat' => $tablazat) );
    
    } else {
        echo json_encode( array(
                                'hiba'  => 'Önnek nincs joga számlát kiválasztani!',
                                'title' => 'Hiba'
                                )
                        );
    }
    
    
    $weblap -> leallit();
    exit;
}

// Anyagrendelések kilistázása egy számla tétel alapján
if( isset($_POST['get_anyagrendeles_tetelek']) ) {
    
    if( _jog(JOG_ANYAG_SZAMLAK_KEZEL) ) {
        
        $foglalt_anyagrend_sorok = array();
        $kapcsolasok = json_decode($_POST['kapcsolasok'], 'tömbbe');
        foreach ( $kapcsolasok as $kapcs ) {
            array_push($foglalt_anyagrend_sorok, $kapcs);
        }
    
        $szamla = $adatbazis_kapcsolat->s2('rnd_id', 'anyag_szamla', "`recid`='{$_POST['szamla_id']}'");

        // A rendelés kiválasztás popupon, azokat a rendelés tételeket kell megjeleníteni, melyeknél a rendelés szám megegyezik a kiválasztott számla rendelés számával 
        // és a számlázott mennyiség < beérkezett mennyiség, mert ezeknél a tételeknél várunk még számlát.
        // Továbbá melyeknek részben beérkezett vagy beérkezett a státusza és szállító az, ami a kiválasztott számla szállítója, telephely pedig szintén az előbb kiválasztott számla telephelye.
        $anyagrendelesek = $adatbazis_kapcsolat->s('recid rndid cikk_id cikkmee ikt_szam rndmen erkmen szlamen rndear', 'anyagigenyles', "`rndid`='{$szamla['rnd_id']}' AND `szlamen`<`erkmen`");

        $tablazat = nl2('
            <div style="max-height: 40rem; overflow-y: auto; overflow-x: none;">
                <table id="anyagrend_tetelek" class="csikos v2 kijCont csak_egy_kijelolheto fix_fejlec hoveres">
                <tr>
                    <th>Rendelés szám</th>
                    <th>Cikkszám</th>
                    <th>Cikknév</th>
                    <th>Mee</th>
                    <th>Anyagig. Szám</th>
                    <th>Rendelt mennyiség</th>
                    <th>Beérkezett mennyiség</th>
                    <th>Már számlázott mennyiség</th>
                    <th>Rendelési egységár</th>
                </tr>
            ');
        foreach ( $anyagrendelesek as $rendeles ) {

            $szurke = '';
            if( in_array($rendeles['recid'], $foglalt_anyagrend_sorok) ) {
                $szurke = ' szurke';
            }
            
            $tablazat .= nl2('
                <tr class="kijSor'.$szurke.'" data-recid="'.$rendeles['recid'].'">
                    <td>'.$rendeles['rndid'].'</td>
                    <td>'.$rendeles['cikk_id'].'</td>
                    <td>'.getErtek('nev', 'cikktorzs', 'id', $rendeles['cikk_id']).'</td>
                    <td>'.$rendeles['cikkmee'].'</td>
                    <td>'.$rendeles['ikt_szam'].'</td>
                    <td class="r">'.number_format($rendeles['rndmen'],  2, ',', ' ').'</td>
                    <td class="r">'.number_format($rendeles['erkmen'],  2, ',', ' ').'</td>
                    <td class="r">'.number_format($rendeles['szlamen'], 2, ',', ' ').'</td>
                    <td class="r">'.number_format($rendeles['rndear'],  4, ',', ' ').'</td>
                </tr>
                ');
        }

        $tablazat .= nl2('
                </table>
            </div>
            ');

        echo json_encode( array('tablazat' => $tablazat) );
    
    } else {
        echo json_encode( array(
                                'hiba'  => 'Önnek nincs joga számlát kiválasztani!',
                                'title' => 'Hiba'
                                )
                        );
    }
    
    $weblap -> leallit();
    exit;
}

// Anyagrendelések és számla tételek összekapcsolása
if( isset($_POST['anyag_szamla_tetelek_ment']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Javasriptből kaptunk egy tömbböt, ilyen formában: tomb[szamla_tetel_recid] = anyagrendeles_recid
    $kapcsolasok = json_decode($_POST['kapcsolasok'], 'tömbbe');
    
    // Az anyagrendelés sorába hozzáadjuk a már számlázott összeget a meglevőhöz
    // Illetve a számla tétel sorába is letároljuk a rendelés azonosítóját és rnd_id-ját.
    foreach( $kapcsolasok as $kulcs => $kapcs ) {
        
        $szamla_tetel  = $adatbazis_kapcsolat->s2('netto_ert szlamen cikk_id szlaear', 'anyag_szamla_tetel', "`recid`='{$kulcs}'");
        $anyagrendeles = $adatbazis_kapcsolat->s2('ikt_szam rndid erkmen rndear', 'anyagigenyles', "`recid`='{$kapcs}'");
        
        // A számlázott mennyiség nem lehet nagyobb, mint a beérkezett mennyiség!
        if( $anyagrendeles['erkmen'] < $szamla_tetel['szlamen'] ) {
            echo json_encode(array(
                'hiba'  => 'Cikkszám: '.$szamla_tetel['cikk_id'].'<br>A számlázott mennyiség nem lehet nagyobb, mint a beérkezett mennyiség!<br>A számla nem menthető!',
                'title' => 'Hiba',
            ));
            $weblap -> leallit();
            exit;
        }
        
        // Számlázási egységár nem lehet nagyobb a rendelési egységárnál
        if( $anyagrendeles['rndear'] < $szamla_tetel['szlaear'] ) {
            echo json_encode(array(
                'hiba'  => 'Cikkszám: '.$szamla_tetel['cikk_id'].'<br>Számlázási egységár nem lehet nagyobb a rendelési egységárnál!<br>A számla nem menthető!',
                'title' => 'Hiba',
            ));
            $weblap -> leallit();
            exit;
        }
        
        // Számlázott mennyiség növelése
        if( !$szamla_tetel ) { $db_error = true; }
        if( !mysqli_query($db_kapcs, 'UPDATE `'.get_db_tabla().'anyagigenyles` SET `szlamen`=`szlamen`+'.$szamla_tetel['netto_ert'].' WHERE `recid`=\''.$kapcs.'\'') ) {
            $db_error = true;
        }
        
        // Anyagrendelés ID-k eltárolása tételekhez
        $sql = $adatbazis_kapcsolat->u('anyag_szamla_tetel',
                                                            array('anyagigenyles_szam',         'rnd_id'                 ),
                                                            array($anyagrendeles['ikt_szam'],   $anyagrendeles['rndid']  ),
                                        "`recid`='{$kulcs}'");
        if( !$sql ) { $db_error = true; }
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
    
    
    $weblap -> leallit();
    exit;
}

// Áfakód recid alapján lekérjük az áfát, hogy abból ott a popupon tudjunk bruttót számolni
if( isset($_POST['get_afa']) ) {
    
    $afakod = $adatbazis_kapcsolat->s2('afa', 'afakod', "`recid`='{$_POST['afakod_recid']}'");
    echo json_encode( array(
                        'afa' => $afakod['afa'],
                    ) );
    
    $weblap -> leallit();
    exit();
}

// CSV generálása
if( isset($_GET['csv']) ) {

    require_once 'selectek.php';

    // CSV export előkészítése
    $csv_fajl_neve = 'Anyag-számla';
    if( isset($_GET['tetelek']) ) {
        $csv_fajl_neve .= '-tételek';
    }
    $datum_mezok    = array('Céghez érkezett', 'Kelte', 'Teljesítés dátuma', 'Fizetési határidő', 'Átváltva', 'Kifizetve', 'Utolsó kifizetés dátuma');
    $szam_mezok     = array('Árfolyam', 'Deviza nettó', 'Deviza bruttó', 'Egységár', 'Nettó érték', 'Bruttó érték', 'Kifizetett összeg', 'Mennyiség', 'Egységár', 'Tétel nettó érték', 'Tétel bruttó érték', 'Tétel nettó érték (devizában)', 'Tétel bruttó érték (devizában)', 'Áfa (%)');
    $igen_nem_mezok = array('0 értékű számla', 'E-számla', 'Fogyasztással arányos', 'Kifizetve');

    list($mibol, $mibe, $filename, $open) = csv_export_elokeszit($megnevezes);
    if( $open ) {
        
        $lekerdezes_fromtol = substr($_SESSION['akt_sql'], strpos($_SESSION['akt_sql'], 'FROM'));
        
        if( $_SESSION['nyomtatas_ids'] ) {
            
            // Tételek kiexportálása
            if( isset($_GET['tetelek']) ) {
                
                $lekerdezes_fromtol = str_replace('FROM `'.get_db_tabla().$megnevezes.'` ', "FROM `".get_db_tabla().$megnevezes."` JOIN `".get_db_tabla().$megnevezes."_tetel` ON `".get_db_tabla().$megnevezes."`.`recid`=`".get_db_tabla().$megnevezes."_tetel`.`".$megnevezes."_recid`", $lekerdezes_fromtol);

                // Ha van benne limit, akkor a teljes adatbázist kilistázzuk, de csak akkor, ha a limit az alapértelmezett (Mert ekkor a legfrisebb 1000-es he hasonló feltétel van érvényben)
                $mindet_listazd = false;
                if( strpos( $_SESSION['akt_sql'], 'LIMIT' )!==false ) {
                    $limit = trim( substr( $_SESSION['akt_sql'], strpos($_SESSION['akt_sql'], 'LIMIT') ), 'LIMIT ' );
                    if( strpos($limit, ' ') ) {
                        $limit = substr($limit, 0, strpos($limit, ' '));
                    }
                    if( $limit == SZURES_DB_LIMIT && !$_SESSION['nyomtatas_ids_ksz'] ) {
                        $mindet_listazd = true;
                    }
                }
                if( $mindet_listazd ) {
                    
                    // A LIMIT-et levágjuk, illetve a balnavpanel szerinti rendezést is nullázzuk
                    $lekerdezes_fromtol = substr($lekerdezes_fromtol, 0, strpos($lekerdezes_fromtol, 'LIMIT') );
                    $_SESSION['akt_sql_order'] = '';
                    
                } else {
                
                    if( strstr($_SESSION['akt_sql'], 'WHERE') ) {
                        $lekerdezes_fromtol = str_replace('WHERE', "WHERE `".get_db_tabla().$megnevezes."`.`recid` IN (".$_SESSION['nyomtatas_ids'].") AND ", $lekerdezes_fromtol);
                    } else {
                        $lekerdezes_fromtol = str_replace('ORDER', "WHERE `".get_db_tabla().$megnevezes."`.`recid` IN (".$_SESSION['nyomtatas_ids'].") ORDER", $lekerdezes_fromtol);
                    }

                    // Ha van $_SESSION['akt_sql_order'], akkor eszerint rendezünk, tehát a $lekerdezes_fromtol-t elvágjuk az Order-nél
                    if( $_SESSION['akt_sql_order'] ) {
                        $hossz = strpos($lekerdezes_fromtol, 'ORDER');
                        if( $hossz ) {
                            $lekerdezes_fromtol = substr($lekerdezes_fromtol, 0, $hossz);
                        } else {
                            $lekerdezes_fromtol = substr($lekerdezes_fromtol, 0);
                        }
                    }
                    
                }
                
                // Ha nincs FROM rész, akkor azt még beletesszük
                if( !$lekerdezes_fromtol ) {
                    $lekerdezes_fromtol = ' FROM `'.get_db_tabla().$megnevezes.'` ';
                }
                
                $sql = 'SELECT
                            `'.get_db_tabla().$megnevezes.'`.`telephely_id` as \'Szálloda\',
                            `'.get_db_tabla().$megnevezes.'`.`szallito_id` as \'Partner\',
                            `'.get_db_tabla().$megnevezes.'`.`targy` as \'Tárgy\',
                            `'.get_db_tabla().$megnevezes.'`.`megjegyz` as \'Megjegyzés\',
                            `'.get_db_tabla().$megnevezes.'`.`netto` as \'Nettó érték\',
                            `'.get_db_tabla().$megnevezes.'`.`brutto` as \'Bruttó érték\',
                            `'.get_db_tabla().$megnevezes.'`.`kcs_21` as \'Pénznem\',
                            `'.get_db_tabla().$megnevezes.'`.`dev_net` as \'Deviza nettó\',
                            `'.get_db_tabla().$megnevezes.'`.`dev_br` as \'Deviza bruttó\',
                            `'.get_db_tabla().$megnevezes.'`.`arfolyam` as \'Árfolyam\',
                            `'.get_db_tabla().$megnevezes.'`.`arfolyam_szall` as \'Szállító árfolyam\',
                            `'.get_db_tabla().$megnevezes.'`.`atvaltva_dt` as \'Átváltva\',
                            `'.get_db_tabla().$megnevezes.'`.`telj_dt` as \'Teljesítés dátuma\',
                            `'.get_db_tabla().$megnevezes.'`.`fizhat_dt` as \'Fizetési határidő\',
                            `'.get_db_tabla().$megnevezes.'`.`erk_dt` as \'Céghez érkezett\',
                            `'.get_db_tabla().$megnevezes.'`.`kelte_dt` as \'Kelte\',
                            `'.get_db_tabla().$megnevezes.'`.`szla_szam` as \'Számlaszám\',
                            `'.get_db_tabla().$megnevezes.'`.`ikt_szam` as \' Iktatószám\',
                            `'.get_db_tabla().$megnevezes.'`.`kif_ossz` as \'Kifizetett összeg\',
                            `'.get_db_tabla().$megnevezes.'`.`ut_kif_dt` as \'Utolsó kifizetés dátuma\',
                            `'.get_db_tabla().$megnevezes.'`.`kifizetve` as \'Kifizetve\',
                            `'.get_db_tabla().$megnevezes.'`.`nullas` as \'0 értékű számla\',
                            `'.get_db_tabla().$megnevezes.'`.`sz_szam` as \'Szerződés szám\',
                            `'.get_db_tabla().$megnevezes.'`.`kolt_vis` as \'Költségviselő\',
                            `'.get_db_tabla().$megnevezes.'`.`fizmod` as \'Fizetési mód\',
                            `'.get_db_tabla().$megnevezes.'`.`eszamla` as \'E-számla\',
                            `'.get_db_tabla().$megnevezes.'`.`tipus` as \'Számla típusa\',
                            '.
                            // Innentől a tételek
                            '
                            `'.get_db_tabla().$megnevezes.'_tetel`.`cikktorzs_id` as `Danubius cikkszám`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`cikktorzs` as `Cikknév`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`szall_csz` as `Szállító cikkszáma`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`rndmen` as `Mennyiség`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`rndear` as `Egységár`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`cikkmee` as `Mee`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`megn` as \'Költségnem\',
                            `'.get_db_tabla().$megnevezes.'_tetel`.`ktghely` as \'Költséghely\',
                            `'.get_db_tabla().$megnevezes.'_tetel`.`netto_ert` as `Tétel nettó érték`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`brutto_ert` as `Tétel bruttó érték`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`dev_net` as `Tétel nettó érték (devizában)`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`dev_br` as `Tétel bruttó érték (devizában)`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`afa` as `Áfa (%)`
                            '.$lekerdezes_fromtol.$_SESSION['akt_sql_order'];
                $query = mysqli_query($db_kapcs, $sql);

                // Nagyobb CSV-kre kérdezzünk rá előzetesen
                $osszes_sor = mysqli_num_rows($query);
//  Itt ilyet ne csináljunk, mert a csv_link_cserel_kattint 3 paraméterét nem tudom kimenteni
//                if( !$_GET['mehet'] && $osszes_sor>CSV_EXPORT_LIMIT ) {
//                    $gomb = '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#navGombok .excel\').attr(\'onclick\', \'csv_general( this, \\\'\\\', \\\'&mehet=1\\\');\'); $(\'.navGombok a.excel\').click();"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">';
//                    echo json_encode(array('hiba' => 'A CSV export több mint '.number_format(CSV_EXPORT_LIMIT, 0, '', ' ').' sort tartalmaz. Ennek előállítása hosszabb időbe telik majd.<br>Biztosan folytatja?', 'gomb' => $gomb));
//                    $weblap -> leallit();
//                    exit;
//                } else {
//                    $sql .= $_SESSION['akt_sql_order'];
//                }

                $query      = mysqli_query($db_kapcs, $sql);
                $csvhead    = array();
                while ( $fejlec_nevek = mysqli_fetch_field($query) ) {
                    $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
                    if( $fejlec_nevek -> name == 'Partner' ) {
                        $csvhead[] = iconv2($mibol, $mibe, 'Partner neve' );
                    }
                }
                fputcsv($open, $csvhead, ';');

                $i = 0;
                while( $sorok = mysqli_fetch_assoc($query) ) {

                    $csvcont = array();
                    foreach( $sorok as $kulcs => $sor ) {

                        if( $kulcs == 'Fizetési mód' ) {
                            if( $sor == 'k' ) {
                                $sor = 'Készpénz';
                            } else {
                                $sor = 'Utalás';
                            }
                        }
                        if( $kulcs == 'Számlaszám' ) {
                            $sor = '="'.$sor.'"';
                        }
                        if( $kulcs == 'Danubius cikkszám' ) {
                            $sor = '="'.$sor.'"';
                        }
                        if( $kulcs == 'Szálloda' ) {
                            $sor = getSzallodaNev($sor);
                        }
                        if( $kulcs == 'Pénznem' ) {
                            $sor = erteklista(ERTEKLISTA_PENZNEM, $sor);
                        }
                        if( $kulcs == 'Költségviselő' ) {
                            $sor = erteklista(ERTEKLISTA_KOLTSEGVISELO, $sor);
                        }
                        // Dátumok és számok formázása
                        if( in_array($kulcs, $datum_mezok) ) {
                            $sor = datum_kiir($sor);
                        }
                        // Szám mezők
                        if( in_array($kulcs, $szam_mezok) ) {
                            $sor = str_replace('.', ',', $sor);
                        }
                        // Igen/nem mezők
                        if( in_array($kulcs, $igen_nem_mezok) ) {
                            if( $sor ) {
                                $sor = 'igen';
                            } else {
                                $sor = 'nem';
                            }
                        }

                        $csvcont[] = iconv($mibol, $mibe.'//TRANSLIT', strip_tags( htmlspecialchars_decode( $sor ) ) );
                        if( $kulcs == 'Partner' ) {
                            $csvcont[] = iconv($mibol, $mibe.'//TRANSLIT', strip_tags( htmlspecialchars_decode(getErtek('nev', 'szallito', 'id', $sor) ) ) );
                        }
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
                
            // Számlák exportja
            } else {
                
                $lekerdezes_fromtol = substr($_SESSION['akt_sql'], strpos($_SESSION['akt_sql'], 'FROM'));

                // Ha van benne limit, akkor a teljes adatbázist kilistázzuk, de csak akkor, ha a limit az alapértelmezett (Mert ekkor a legfrisebb 1000-es he hasonló feltétel van érvényben) és nincs gyorskeresőbe se írva semmi
                $mindet_listazd = false;
                if( strpos( $_SESSION['akt_sql'], 'LIMIT' )!==false ) {
                    $limit = trim( substr( $_SESSION['akt_sql'], strpos($_SESSION['akt_sql'], 'LIMIT') ), 'LIMIT ' );
                    if( strpos($limit, ' ') ) {
                        $limit = substr($limit, 0, strpos($limit, ' '));
                    }
                    if( $limit == SZURES_DB_LIMIT && !$_SESSION['nyomtatas_ids_ksz'] ) {
                        $mindet_listazd = true;
                    }
                }
                if( $mindet_listazd ) {

                    // A LIMIT-et levágjuk, illetve a balnavpanel szerinti rendezést is nullázzuk
                    $lekerdezes_fromtol = substr($lekerdezes_fromtol, 0, strpos($lekerdezes_fromtol, 'LIMIT') );
                    $_SESSION['akt_sql_order'] = '';

                } else {

                    if( strstr($_SESSION['akt_sql'], 'WHERE') ) {
                        $lekerdezes_fromtol = str_replace('WHERE', "WHERE `".get_db_tabla().$megnevezes."`.`recid` IN (".$_SESSION['nyomtatas_ids'].") AND ", $lekerdezes_fromtol);
                    } else {
                        $lekerdezes_fromtol = str_replace('ORDER', "WHERE `".get_db_tabla().$megnevezes."`.`recid` IN (".$_SESSION['nyomtatas_ids'].") ORDER", $lekerdezes_fromtol);
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

                }

                // Ha nincs FROM rész, akkor azt még beletesszük
                if( !$lekerdezes_fromtol ) {
                    $lekerdezes_fromtol = ' FROM `'.get_db_tabla().$megnevezes.'` ';
                }

                $sql = 'SELECT
                            `'.get_db_tabla().$megnevezes.'`.`telephely_id` as \'Szálloda\',
                            `'.get_db_tabla().$megnevezes.'`.`szallito_id` as \'Partner\',
                            `'.get_db_tabla().$megnevezes.'`.`targy` as \'Tárgy\',
                            `'.get_db_tabla().$megnevezes.'`.`megjegyz` as \'Megjegyzés\',
                            `'.get_db_tabla().$megnevezes.'`.`netto` as \'Nettó érték\',
                            `'.get_db_tabla().$megnevezes.'`.`brutto` as \'Bruttó érték\',
                            `'.get_db_tabla().$megnevezes.'`.`kcs_21` as \'Pénznem\',
                            `'.get_db_tabla().$megnevezes.'`.`dev_net` as \'Deviza nettó\',
                            `'.get_db_tabla().$megnevezes.'`.`dev_br` as \'Deviza bruttó\',
                            `'.get_db_tabla().$megnevezes.'`.`arfolyam` as \'Árfolyam\',
                            `'.get_db_tabla().$megnevezes.'`.`arfolyam_szall` as \'Szállító árfolyam\',
                            `'.get_db_tabla().$megnevezes.'`.`atvaltva_dt` as \'Átváltva\',
                            `'.get_db_tabla().$megnevezes.'`.`telj_dt` as \'Teljesítés dátuma\',
                            `'.get_db_tabla().$megnevezes.'`.`fizhat_dt` as \'Fizetési határidő\',
                            `'.get_db_tabla().$megnevezes.'`.`erk_dt` as \'Céghez érkezett\',
                            `'.get_db_tabla().$megnevezes.'`.`kelte_dt` as \'Kelte\',
                            `'.get_db_tabla().$megnevezes.'`.`szla_szam` as \'Számlaszám\',
                            `'.get_db_tabla().$megnevezes.'`.`ikt_szam` as \' Iktatószám\',
                            `'.get_db_tabla().$megnevezes.'`.`kif_ossz` as \'Kifizetett összeg\',
                            `'.get_db_tabla().$megnevezes.'`.`ut_kif_dt` as \'Utolsó kifizetés dátuma\',
                            `'.get_db_tabla().$megnevezes.'`.`kifizetve` as \'Kifizetve\',
                            `'.get_db_tabla().$megnevezes.'`.`nullas` as \'0 értékű számla\',
                            `'.get_db_tabla().$megnevezes.'`.`sz_szam` as \'Szerződés szám\',
                            `'.get_db_tabla().$megnevezes.'`.`kolt_vis` as \'Költségviselő\',
                            `'.get_db_tabla().$megnevezes.'`.`fizmod` as \'Fizetési mód\',
                            `'.get_db_tabla().$megnevezes.'`.`eszamla` as \'E-számla\',
                            `'.get_db_tabla().$megnevezes.'`.`tipus` as \'Számla típusa\'
                            '.$lekerdezes_fromtol;
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
                    if( $fejlec_nevek -> name == 'Partner' ) {
                        $csvhead[] = iconv2($mibol, $mibe, 'Partner neve' );
                    }
                }
                fputcsv($open, $csvhead, ';');

                $i = 0;
                while( $sorok = mysqli_fetch_assoc($query) ) {

                    $csvcont = array();
                    foreach( $sorok as $kulcs => $sor ) {

                        if( $kulcs == 'Fizetési mód' ) {
                            if( $sor == 'k' ) {
                                $sor = 'Készpénz';
                            } else {
                                $sor = 'Utalás';
                            }
                        }
                        if( $kulcs == 'Szálloda' ) {
                            $sor = getSzallodaNev($sor);
                        }
                        if( $kulcs == 'Pénznem' ) {
                            $sor = erteklista(ERTEKLISTA_PENZNEM, $sor);
                        }
                        if( $kulcs == 'Költségviselő' ) {
                            $sor = erteklista(ERTEKLISTA_KOLTSEGVISELO, $sor);
                        }
                        if( $kulcs == 'Számlaszám' ) {
                            $sor = '="'.$sor.'"';
                        }
                        // Dátumok formázása
                        if( in_array($kulcs, $datum_mezok) ) {
                            $sor = datum_kiir($sor);
                        }
                        // Szám mezők
                        if( in_array($kulcs, $szam_mezok) ) {
                            $sor = str_replace('.', ',', $sor);
                        }
                        // Igen/nem mezők
                        if( in_array($kulcs, $igen_nem_mezok) ) {
                            if( $sor ) {
                                $sor = 'igen';
                            } else {
                                $sor = 'nem';
                            }
                        }

                        $csvcont[] = iconv($mibol, $mibe.'//TRANSLIT', strip_tags( htmlspecialchars_decode( $sor ) ) );
                        if( $kulcs == 'Partner' ) {
                            $csvcont[] = iconv($mibol, $mibe.'//TRANSLIT', strip_tags( htmlspecialchars_decode(getErtek('nev', 'szallito', 'id', $sor) ) ) );
                        }
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
            }
            
            // Progress
            echo json_encode(array('pr' => '100')).PHP_EOL;

            // Felszabadítás
            ob_flush();
            flush();
        }
    }

    csv_export_lezar($fh, $filename, $csv_fajl_neve);
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

    if( isset($_GET['modosit']) || isset($_GET['tetel']) || /*isset($_GET['kifizetesek']) ||*/ isset($_GET['tortenet']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'tetel'         => 'Tételek',
            // 'kifizetesek'   => 'Kifizetések',
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
    
    $js_submit_elott = 'return form_ell_admin();';
    
    
    // Tételek SESSION-jének feltöltése (új felvitelben kicsit más a lekérdezés
    if( isset($_GET['uj']) && _jog(JOG_ANYAG_SZAMLAK_OLVAS) ) {
        $szamla  = $adatbazis_kapcsolat->s2('recid kcs_21', 'anyag_szamla_atmeneti', "`andoc_id`='{$_GET['andoc_id']}'");
        $tetelek = $adatbazis_kapcsolat->s('*', 'anyag_szamla_tetel_atmeneti', "`anyag_szamla_recid`='{$szamla['recid']}'");
    } else if( _jog(JOG_ANYAG_SZAMLAK_OLVAS) ) {
        $tetelek = $adatbazis_kapcsolat->s('*', 'anyag_szamla_tetel', "`anyag_szamla_recid`='{$_GET['id']}'");
    }
    $i = 0;
    $ossz_netto   = 0;
    $ossz_dev_net = 0;
    $_SESSION['anyag_szla_tetel_recid']         =
    $_SESSION['anyag_szla_tetel_rndid']         =
    $_SESSION['anyag_szla_tetel_rnd_recid']     =
    $_SESSION['anyag_szla_tetel_cikktorzs']     =
    $_SESSION['anyag_szla_tetel_szall_csz']     =
    $_SESSION['anyag_szla_tetel_cikktorzs_id']  =
    $_SESSION['anyag_szla_tetel_rndmen']        =
    $_SESSION['anyag_szla_tetel_rndear']        =
    $_SESSION['anyag_szla_tetel_cikkmee']       =
    $_SESSION['anyag_szla_tetel_netto_ert']     =
    $_SESSION['anyag_szla_tetel_brutto_ert']    =
    $_SESSION['anyag_szla_tetel_dev_net']       =
    $_SESSION['anyag_szla_tetel_dev_br']        =
    $_SESSION['anyag_szla_tetel_afakod']        =
    $_SESSION['anyag_szla_tetel_jogcim']        =
                                                array();
    foreach ( $tetelek as $tetel ) {
        $ossz_netto   += $tetel['netto_ert'];
        $ossz_dev_net += $tetel['dev_net'];
        $_SESSION['anyag_szla_tetel_recid'][$i]         = $tetel['recid'];
        $_SESSION['anyag_szla_tetel_rndid'][$i]         = $tetel['rndid'];
        $_SESSION['anyag_szla_tetel_rnd_recid'][$i]     = $tetel['rnd_recid'];
        $_SESSION['anyag_szla_tetel_cikktorzs'][$i]     = $tetel['cikktorzs'];
        $_SESSION['anyag_szla_tetel_szall_csz'][$i]     = $tetel['szall_csz'];
        $_SESSION['anyag_szla_tetel_cikktorzs_id'][$i]  = $tetel['cikktorzs_id'];
        $_SESSION['anyag_szla_tetel_rndmen'][$i]        = number_format($tetel['rndmen'], 2, ',', ' ');
        $_SESSION['anyag_szla_tetel_rndear'][$i]        = number_format($tetel['rndear'], 2, ',', ' ');
        $_SESSION['anyag_szla_tetel_cikkmee'][$i]       = $tetel['cikkmee'];
        $_SESSION['anyag_szla_tetel_netto_ert'][$i]     = number_format($tetel['netto_ert'],  0, '', ' ');
        $_SESSION['anyag_szla_tetel_brutto_ert'][$i]    = number_format($tetel['brutto_ert'], 0, '', ' ');
        $_SESSION['anyag_szla_tetel_dev_net'][$i]       = number_format($tetel['dev_net'],    2, ',', ' ');
        $_SESSION['anyag_szla_tetel_dev_br'][$i]        = number_format($tetel['dev_br'],     2, ',', ' ');
        $_SESSION['anyag_szla_tetel_afakod'][$i]        = $tetel['afakod_recid'];
        $_SESSION['anyag_szla_tetel_jogcim'][$i]        = $tetel['jogcim_recid'];
        $_SESSION['anyag_szla_tetel_valtozott'][$i]     = $tetel['valtozott'];
        $_SESSION['anyag_szla_tetel_andocos'][$i]       = $tetel['andocos'];

        $i++;
    }
    
    // Anyag számla mentésekor HA nem nullás a számla, akkor ellenőrizzük, hogy a számla nettó értéke azonos-e a tételek összegével
    // Deviza esetén más session-t járunk be
    $tizedesek  = 0;
    $mezo1      = 'ossz_netto';
    $mezo2      = 'netto';
    if( $szamla['kcs_21']!=KCS_21_HUF ) {
        $tizedesek  = 2;
        $mezo1      = 'ossz_dev_net';
        $mezo2      = 'dev_net';
    }
    
    // Tételek SESSION-jének feltöltése (új felvitelben kicsit más a lekérdezés
    if( isset($_GET['uj']) && _jog(JOG_ANYAG_SZAMLAK_OLVAS) ) {
        $szamla = $adatbazis_kapcsolat->s2('recid kcs_21', 'anyag_szamla_atmeneti', "`andoc_id`='{$_GET['andoc_id']}'");
    }

    // Gombok kirajzolása minden oldalra
    // Fenntartási számla mentésekor HA nem nullás a számla, akkor ellenőrizzük, hogy a számla nettó értéke azonos-e a tételek összegével
    // Deviza esetén más session-t járunk be
    $tizedesek  = 0;
    $mezo1      = 'ossz_netto';
    $mezo2      = 'netto';
    if( $szamla['kcs_21']!=KCS_21_HUF ) {
        $tizedesek  = 2;
        $mezo1      = 'ossz_dev_net';
        $mezo2      = 'dev_net';
    }
    $gombok = '';
    if( isset($_GET['uj']) && _jog(JOG_ANYAG_SZAMLAK_OLVAS) ) {

        // Új számla rögzítésekor így ellenőrizzük a nettó és tételek összegének azonosságát
        $js_submit_elott = nl2('

            var tetelek_ossz = ezresek($(\'#'.$mezo1.'\').val().replace(/ /g, \'\').replace(/,/g, \'.\'), '.$tizedesek.', \',\', \' \');
            if( $(\'#nullas\').is(\':checked\') == false ) {
                if( $(\'#'.$mezo2.'\').val() != tetelek_ossz ) {
                    alert2(\'A számla tételeinek összege (\'+tetelek_ossz+\' \'+$(\'#kcs_21_autocomp\').val()+\') nem egyezik meg a számla nettó értékével (\'+$(\'#'.$mezo2.'\').val()+\' \'+$(\'#kcs_21_autocomp\').val()+\')!<br>Így nem menthető!\', \'\', \'Hiba\');
                    return false;
                }
            } else if( $(\'#nullas\').is(\':checked\') == true ) {
                if( tetelek_ossz != \'0\' && tetelek_ossz != \'0,00\' && tetelek_ossz != \'\' ) {
                    alert2(\'Nullás számla esetén a tételek értéke is 0 legyen!<br>Így nem menthető!\', \'\', \'Hiba\');
                    return false;
                }
            }

            return form_ell_admin();
            
            ');
        
        $gombok = nl2('
            <div class="form_kuldes">
                <input type="hidden" name="atmeneti_recid" value="'.$szamla['recid'].'">
                <input type="hidden" id="ossz_netto" value="">
                <input type="hidden" id="ossz_dev_net" value="">
                <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
                <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
            </div>
        ');
        $gombok .= '<input type="button" class="gomb" value="Tételek kezelése" onclick="csoportMuvelet(\'anyagszamla_tetelek_kezelese\');"> ';
        $gombok .= '<input type="button" class="gomb" value="Vissza Andoc számlákhoz" onclick="vissza_andoc_szamlakhoz();"> ';
        
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


        // Ha már fel van dolgozva, nincs még igazolva és van hozzá jogunk
        if( $sor['feldolgozva_dt'] != '0000-00-00 00:00:00' && $sor['igazolva_dt']=='0000-00-00 00:00:00' && _jog(SZAMLAK_IGAZOLASA) ) {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt zold" value="Igazolás" onclick="szamla_igazol()"> ';
            $gombok .= '<input type="button" class="gomb modositaskor_rejt piros" value="Elutasítás" onclick="csoportMuvelet(\'anyagSzamlaElutasit\');"> ';
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Vissza üi.-nek" onclick="csoportMuvelet(\'anyagSzamlaVisszaUi\');"> ';
        }
        
        // Szerkesztéshez az kell, hogy legyen jogunk, illetve a számla ne legyen még feldolgzova
        if( _jog(JOG_ANYAG_SZAMLAK_KEZEL) && $sor['feldolgozva_dt'] == '0000-00-00 00:00:00' ) {
            // Ha még nincs feldolgozva
            if( $sor['feldolgozva_dt'] == '0000-00-00 00:00:00' ) {
                $gombok .= '<input type="button" class="gomb" value="Küldés igazolásra" onclick="kuldes_igazolasra()"> ';
            }
            $gombok .= '<input type="button" class="gomb" value="Tételek kezelése" onclick="csoportMuvelet(\'anyagszamla_tetelek_kezelese\');"> ';
            // Ez a popuphoz kell
            $gombok .= '<input type="hidden" id="kcs_21_autocomp_db" value="'.erteklista(ERTEKLISTA_PENZNEM, $sor['kcs_21']).'">';
            $gombok .= '<input type="hidden" id="kcs_21_db" value="'.$sor['kcs_21'].'">';
            $gombok .= '<input type="hidden" id="netto_db" value="'.$sor['netto'].'">';
            $gombok .= '<input type="hidden" id="brutto_db" value="'.$sor['brutto'].'">';
            $gombok .= '<input type="hidden" id="dev_net_db" value="'.$sor['dev_net'].'">';
            $gombok .= '<input type="hidden" id="dev_br_db" value="'.$sor['dev_br'].'">';
            $gombok .= '<input type="hidden" id="arfolyam_db" value="'.$sor['arfolyam'].'">';
            $gombok .= '<input type="hidden" id="nullas_db" value="'.$sor['nullas'].'">';
            $gombok .= '<input type="hidden" id="telephely_id_db" value="'.$sor['telephely_id'].'">';
            $gombok .= '<input type="hidden" id="szallito_id_db" value="'.$sor['szallito_id'].'">';
            $gombok .= '<input type="hidden" id="fizmod_db" value="'.$sor['fizmod'].'">';
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
                </div>
            ').$gombok;
        // Ha nem módosítható már az űrlap, akkor a "Szerződés hozzáadása gombot is vegyük le"
        } else {
            $gombok .= '<script>$(document).ready(function () { $(\'#sz_szam_gomb\').hide(); });</script>';
        }
        
        // Ha van jogunk hozzá és a számla már utalványozva van, akkor lehet stornózni
        if( _jog(JOG_ANYAG_SZAMLAK_KEZEL) && $sor['utalvanyozva_dt'] != '0000-00-00 00:00:00' && $sor['sztornozva_dt'] == '0000-00-00 00:00:00' ) {
            $gombok .= '<input type="button" class="gomb piros" value="Sztornózás" onclick="csoportMuvelet(\'anyagSzamlaSztornoz\');"> ';
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

        // Itt úgy fogunk tenni, mintha újként szerkesztenénk, viszont nem a session-ból keresünk ide értékeket
        // hanem a kapott andocid alapján az átmeneti táblából. Magyarul egy sima lekérdezéssel meg is van a $sor tömb.
        $sor = $adatbazis_kapcsolat->s2('*', 'anyag_szamla_atmeneti', "`andoc_id`='{$_GET['andoc_id']}'");
        $sor['szallito_id_autocomp'] = getErtek('nev', 'szallito', 'id', $sor['szallito_id']);
	    $js = js_kiir($akt_tomb, 'form_ell_admin()');

	    $form_sablon = fajlbol_olvas('../sablon/admin/form_uj.html', true);

        // Javascript-es ellenőrzéshez
        $tabla = str_replace('.php', '', basename(self()));

// Itt most kifejezetten kell, hogy az autocomplete-s értékek is meglegyenek
//        foreach ( $akt_tomb as $tomb ) {
//            if( $tomb['type'] == 'autocomplete' ) {
//                $sor[$tomb['name'].'_autocomp'] = '';
//            }
//        }
//        
        // Fizetési módot oda vissza konvertálni kell, mert XML-ből a teljes címkét kapjuk, de mi csak egy karaktert tárolunk
        switch ( strtolower($sor['fizmod']) ) {
            case 'u': $sor['fizmod'] = 'Utalás';    break;
            case 'k': $sor['fizmod'] = 'Készpénz';  break;
        }

        $json_sor = array();
        foreach ( $sor as $kulcs => $elem ) {
            $json_sor[$kulcs] = formAdatok_replace($elem);
        }
        
        $szamla_html .= '<script>formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

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

        // Fizetési módokat szövegesen írjuk ki (amúgy szem szerkeszthető az adatlap)
        switch ( strtolower($sor['fizmod']) ) {
            case 'u': $sor['fizmod'] = 'Utalás';    break;
            case 'k': $sor['fizmod'] = 'Készpénz';  break;
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

	    // FORM
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

	// Számla tételek
    } elseif( isset($_GET['tetel']) ) {

        $deviza = '';
        if( $sor['kcs_21'] != KCS_21_HUF ) {
            $deviza = ' ('.erteklista(ERTEKLISTA_PENZNEM, $sor['kcs_21']).')';
        }
        
        $szamla_html .= nl2('
            <div class="dt naplo">
                <div class="dtr nFejlec">
                    <div class="dtc">Danubius<br>cikkszám</div>
                    <div class="dtc">Cikknév</div>
                    <div class="dtc">Szállító<br>cikkszáma</div>
                    <div class="dtc">Mennyiség</div>
                    <div class="dtc">Egységár</div>
                    <div class="dtc">Mee</div>
                    <div class="dtc">Költségnem</div>
                    <div class="dtc">Költséghely</div>
                    <div class="dtc r">Nettó érték (HUF)</div>
                    <div class="dtc r">Bruttó érték (HUF)</div>
                    <div class="dtc r">Deviza nettó'.$deviza.'</div>
                    <div class="dtc r">Deviza bruttó'.$deviza.'</div>
                    <div class="dtc r">Áfa (%)</div>
                </div>
        ');
        
        // Tételek felsorolása
        $tetelek = $adatbazis_kapcsolat->s('*', 'anyag_szamla_tetel', "`anyag_szamla_recid`='{$_GET['id']}'");
        foreach ( $tetelek as $tetel ) {
            
            $szamla_html .= nl2('
                <div class="dtr nSor">
                    <div class="dtc">'.$tetel['cikktorzs_id'].'</div>
                    <div class="dtc">'.htmlspecialchars_decode(str_replace('{IDEZOJEL}', '"', $tetel['cikktorzs'])).'</div>
                    <div class="dtc">'.htmlspecialchars_decode($tetel['szall_csz']).'</div>
                    <div class="dtc nowrap">'.number_format($tetel['rndmen'], 2, ',', ' ').'</div>
                    <div class="dtc nowrap">'.number_format($tetel['rndear'], 4, ',', ' ').'</div>
                    <div class="dtc">'.htmlspecialchars_decode($tetel['cikkmee']).'</div>
                    <div class="dtc">'.htmlspecialchars_decode($tetel['megn']).'</div>
                    <div class="dtc">'.htmlspecialchars_decode($tetel['ktghely']).'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['netto_ert'],  0, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['brutto_ert'], 0, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['dev_net'],    2, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['dev_br'],     2, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.$tetel['afa'].'</div>
                </div>
            ');
        }
        $szamla_html .= '</div>';
        
        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }
        
	// Történet
    } elseif( isset($_GET['tortenet']) ) {
        
        $igazolva = $igazolva_dt = $utalvanyozva = $igazol_elut = $utalvanyozva_elut = $sztornozva = '';
        if( $sor['igazolva_dt']!='0000-00-00 00:00:00' ) {
            $igazolva_dt = datum_kiir($sor['igazolva_dt']);
        }
        if( $sor['utalvanyozva_dt']!='0000-00-00 00:00:00' ) {
            $utalvanyozva_dt = datum_kiir($sor['utalvanyozva_dt']);
        }
        if( $sor['igazolva'] && $sor['igazolva_dt'] != '0000-00-00 00:00:00' ) {
            $igazolva = '<b class="pipa">✓</b>';
        }
        if( $sor['utalvanyozva'] && $sor['utalvanyozva_dt'] != '0000-00-00 00:00:00' ) {
            $utalvanyozva = '<b class="pipa">✓</b>';
        }
        if( $sor['sztornozva_dt'] != '0000-00-00 00:00:00' ) {
            $sztornozva = '<b class="x">✖</b>';
            $sztornozva_dt = datum_kiir($sor['sztornozva_dt']);
            $sztornozva_leir = '';
            if( $sor['sztornozva_leir'] ) {
                $sztornozva_leir = nl2('
                    </div>
                    <div class="indoklas">
                        Sztornózás indoklása:
                        <br>'.htmlspecialchars_decode( nl2br2($sor['sztornozva_leir']) ).'
                    </div>
                    <div class="dt naplo">
                    ');
            }
        }
        // 2021.09.14 Tamás kérte, hogy ne legyen piros X ha még nincs feldolgozva, csak pipa, ha már megvan
        // $feldolgozva = '<b class="x">✖</b>';
        $feldolgozva = '';
        if( $sor['feldolgozva_dt'] != '0000-00-00 00:00:00' ) {
            $feldolgozva     = '<b class="pipa">✓</b>';
            $feldolgozva_usr = $sor['feldolgozva_usr'];
            $feldolgozva_dt  = datum_kiir($sor['feldolgozva_dt']);
        }
        // Ha nincs igazolva, de az igazolás időpontja már ismert, akkor tujduk, ez el lett utasítva
        if( !$sor['igazolva'] && $sor['igazolva_dt']!='0000-00-00 00:00:00' ) {
            $igazolva    = '<b class="x">✖</b>';
            $igazol_elut = nl2('
                </div>
                <div class="indoklas">
                    Elutasítás indoklása:
                    <br>'.htmlspecialchars_decode( nl2br2($sor['elutleir']) ).'
                </div>
                <div class="dt naplo">
                ');
        }
        if( !$sor['utalvanyozva'] && $sor['utalvanyozva_dt']!='0000-00-00 00:00:00' ) {
            $utalvanyozva       = '<b class="x">✖</b>';
            $utalvanyozva_elut  = nl2('
                </div>
                <div class="indoklas">
                    Elutasítás indoklása:
                    <br>'.htmlspecialchars_decode( nl2br2($sor['elutleir']) ).'
                </div>
                <div class="dt naplo">
                ');
        }
        
        $szamla_html .= nl2('
            <div class="dt naplo">
                <div class="dtr nFejlec">
                    <div class="dtc">Esemény</div>
                    <div class="dtc"><b class="pipa">✓</b></div>
                    <div class="dtc">Felhasználó</div>
                    <div class="dtc">Dátum</div>
                </div>

                <div class="dtr nSor">
                    <div class="dtc">ontEAM-be érkezett</div>
                    <div class="dtc"><b class="pipa">✓</b></div>
                    <div class="dtc">'.$sor['ikt_usr'].'</div>
                    <div class="dtc">'.datum_kiir($sor['ont_erk_dt']).'</div>
                </div>
                <div class="dtr nSor">
                    <div class="dtc">Feldolgozva</div>
                    <div class="dtc"><b class="pipa">'.$feldolgozva.'</b></div>
                    <div class="dtc">'.$feldolgozva_usr.'</div>
                    <div class="dtc">'.$feldolgozva_dt.'</div>
                </div>
                <div class="dtr nSor">
                    <div class="dtc">Igazolva</div>
                    <div class="dtc">'.$igazolva.'</div>
                    <div class="dtc">'.$sor['igazolva_usr'].'</div>
                    <div class="dtc">'.$igazolva_dt.'</div>
                </div>
                '.$igazol_elut.'
                <div class="dtr nSor">
                    <div class="dtc">Utalványozva</div>
                    <div class="dtc">'.$utalvanyozva.'</div>
                    <div class="dtc">'.$sor['utalvanyozva_usr'].'</div>
                    <div class="dtc">'.$utalvanyozva_dt.'</div>
                </div>
                '.$utalvanyozva_elut.'
                <div class="dtr nSor">
                    <div class="dtc">Sztornózva</div>
                    <div class="dtc">'.$sztornozva.'</div>
                    <div class="dtc">'.$sor['sztornozva_usr'].'</div>
                    <div class="dtc">'.$sztornozva_dt.'</div>
                </div>
                '.$sztornozva_leir.'
            </div>
        ');
        
        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Kifizetések fül
//    } elseif( isset($_GET['kifizetesek']) ) {
//
//        $szamla_html .= nl2('
//            <div class="dt naplo">
//                <div class="dtr nFejlec">
//                    <div class="dtc">Bank</div>
//                    <div class="dtc">Kifizetve</div>
//                    <div class="dtc r">Bruttó</div>
//                    <div class="dtc r">Deviza bruttó</div>
//                    <div class="dtc">Pénznem</div>
//                    <div class="dtc">Rögzítve</div>
//                    <div class="dtc">Rögzítette</div>
//                </div>
//        ');
//
//        // Tételek felsorolása
//        $kifizetesek = $adatbazis_kapcsolat->s('*', 'anyag_szamla_kifizetesek', "`anyag_szamla_recid`='{$_GET['id']}'", 'insert_dt');
//        foreach( $kifizetesek as $kifizetes ) {
//            
//            $szamla_html .= nl2('
//                <div class="dtr nSor">
//                    <div class="dtc">'.erteklista(ERTEKLISTA_BANK, $kifizetes['bank']).'</div>
//                    <div class="dtc">'.datum_kiir($kifizetes['kifizetes_dt']).'</div>
//                    <div class="dtc r">'.number_format($kifizetes['br_ertek'], 0, '', ' ').'</div>
//                    <div class="dtc r">'.number_format($kifizetes['dev_br'], 2, ',', ' ').'</div>
//                    <div class="dtc">'.erteklista(ERTEKLISTA_PENZNEM, $kifizetes['kcs_21']).'</div>
//                    <div class="dtc">'.datum_kiir($kifizetes['rogzitve_dt'], 'csak dátum').'</div>
//                    <div class="dtc">'.$kifizetes['rogzitette'].'</div>
//                </div>
//                ');
//        }
//        $szamla_html .= '</div>';
//        
//        if( $_GET['ajax'] ) {
//            echo json_encode(array(
//                'tartalom' => $szamla_html.$gombok,
//                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
//            ));
//        } else {
//            echo $szamla_html.$gombok;
//        }
        
	// Dokumentumok
    } elseif( isset($_GET['dokumentumok']) ) {

        // Itt az XML-ből kapott dokumentum linkjét jelenítjük meg
        if( $sor['dok_link'] ) {
            $szamla_html .= nl2('
                Fődokumentum link:
                <br><input type="text" value="'.$sor['dok_link'].'" readonly style="width: 100%; margin: 1rem 0">
                <br><input type="button" class="gomb" value="Dokumentum megtekintése" onclick="window.open(\''.$sor['dok_link'].'\')">');
        }
        $szamla_html .= nl2('
                <br>Bejövő xml:
                <br>').'<textarea class="oriasiTargy input_kijelolve" style="height: 30rem;" disabled>'.$sor['erk_xml'].'</textarea>'.nl2('
                <br>            
                <br>Kimenő xml:
                <br>').'<textarea class="oriasiTargy input_kijelolve" style="height: 30rem;" disabled>'.$sor['kuld_xml'].'</textarea>';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
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

        $sorok = $adatbazis_kapcsolat->s('recid megj insert_usr insert_dt', 'megjegyzes', "`id` = '".$_GET['id']."' AND `tabla` = '".str_replace('.php', '', basename(self()))."'", 'recid desc');

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

// Igazoláskor, elutasításkor, utalványozáskor gyártunk egy XML vissza fájlt, és azt beírjuk az adatbázisba
function xml_vissza( $szamla_id, $igazolva = '', $oka = '' ) {
    
    global $adatbazis_kapcsolat;
    
    $db_error = false;
    
    $szamla = $adatbazis_kapcsolat->s2('targy igazolva andoc_id telephely_id kcs_21 kuld_tetel_xml kuld_xml megjegyz kolt_vis igazolva_usr', 'anyag_szamla', "`recid`='{$szamla_id}'");
    if( $szamla === false ) { $db_error = true; }
    
    // Cégkód konverzió
    $erteklista = $adatbazis_kapcsolat->s2('cegkod', 'erteklista', "`csoport`='".ERTEKLISTA_KOLTSEGVISELO."' AND `id`='{$szamla['kolt_vis']}'");
    if( $erteklista === false ) { $db_error = true; }
    
    // XML dekódolása
    $szamla['kuld_xml']         = htmlspecialchars_decode($szamla['kuld_xml']);
    $szamla['kuld_tetel_xml']   = htmlspecialchars_decode($szamla['kuld_tetel_xml']);
    $szamla['targy']            = htmlspecialchars_decode($szamla['targy']);
    $szamla['megjegyz']         = htmlspecialchars_decode($szamla['megjegyz']);
    
    // Igazolás elutasítva/jóváhagyva esetén más-más szöveg kerül az XML-be
    if( $igazolva == 'Elutasítva' ) {
        $wf_name = '[AUTO] Számla szétosztás';
    } else {
        $telephely = $adatbazis_kapcsolat->s2('teljigkuldes', 'telephely', "`id`='{$szamla['telephely_id']}'");
        if( $telephely === false ) { $db_error = true; }
        if( $telephely['teljigkuldes'] == 'k' ) {
            if( $szamla['telephely_id'] == TELEPHELY_GUNDEL ) {
                $wf_name = TELEPHELY_GUNDEL_MOD.'_Könyvelés';
            } else if( $szamla['telephely_id'] == TELEPHELY_LOVER ) {
                $wf_name = TELEPHELY_LOVER_MOD.'_Könyvelés';
            } else if( $szamla['telephely_id'] == TELEPHELY_ZAMARDI) {
                $wf_name = TELEPHELY_ZAMARDI_MOD.'_Könyvelés';
            } else if( $szamla['telephely_id'] == TELEPHELY_DZRT) {
                $wf_name = TELEPHELY_DZRT_MOD.'_Könyvelés';
            } else if( $szamla['telephely_id'] == TELEPHELY_KOZP_CUK) {
                $wf_name = TELEPHELY_KOZP_CUK_MOD.'_Könyvelés';
            } else {
                $wf_name = $szamla['telephely_id'].'_Könyvelés';
            }
        } else {
            $wf_name = '[AUTO] Utalványozásra küldés';
        }
    }
    
    $valasz_xml = 'psInputXml=<?xml version="1.0" encoding="utf-8"?>
<xml>
  <data>
    <register registernumber="'.$szamla['andoc_id'].'" operation="update">
      <workflow_node_name>'.urlencode($wf_name).'</workflow_node_name>
      <properties>
        <container name="NIK1">
          <row rownumberonpage="1">
            <property name="Számla főtípus">Műszaki anyag</property>
            <property name="Igazoló döntés">'.urlencode($igazolva).'</property>
            <property name="Teljesítés igazolás elutasítás oka"><![CDATA['.urlencode($oka).']]></property>
            <property name="Szálloda - Klaszter">'.urlencode($szamla['telephely_id']).'</property>
            <property name="Számla tárgya"><![CDATA['.urlencode($szamla['targy']).']]></property>
            <property name="Megjegyzés"><![CDATA['.urlencode($szamla['megjegyz']).']]></property>
            <property name="Számla fizetője">'.urlencode($erteklista['cegkod']).'</property>
            <property name="Teljesítés igazoló személye"><![CDATA['.
            // Azért nem a $szamla['igazolva_usr'], hisz épp most zajlik az igazolás/elutasítás, ez az adat még nincs az adatbázisban
            urlencode($_SESSION[get_db_tabla().'teljes_nev'])
            .']]></property>
          </row>
        </container>
        <container name="IK1">
          '.$szamla['kuld_tetel_xml']/*Ennek előállítását ha meg szeretnéd nézni, keres rá erre: "$_POST['osszevon_targy_kitoltve_ell']"*/.'
        </container>
      </properties>
    </register>
  </data>
</xml>
';
   
    $fejlec = array(
        "Content-type: application/x-www-form-urlencoded",
        "Content-length: ".strlen($valasz_xml),
        "Connection: close",
    );
    
    // Válasz XML postolása CSAK éles és Teszt környezetben van
    if( eles_es_teszt_rendszer_e() ) {
        if( eles_rendszer_e() ) {
            $valasz = URL_post(ANDOC_POST_KULD_URL_ELES, $valasz_xml, $fejlec);
        } else {
            $valasz = URL_post(ANDOC_POST_KULD_URL_TESZT, $valasz_xml, $fejlec);
        }
    } else {
        $valasz_xml = '
***Csak Éles és Teszt rendszerben küldjük el az XML-t valójában!***

        '.urldecode($valasz_xml);
    }
    
    // Visszaalakítjuk az XML-t, hogy logolni tudjuk (megjegyzésbe és andoc.log-ba is)
    // Az urldecode() a teljes stringre meghívható, nem gond, ha csak egy része van elkódolva
    $valasz_xml = urldecode($valasz_xml);
    
    // Logolás
    $log_utvonal = '../andoc.log';
    $log = '*****************************
    KÜLDÖTT XML (Igazolás/elutasításkor)
    '.date('Y.m.d H:i:s').'
    Ip: '.$_SERVER['REMOTE_ADDR'].'
    URL: '.get_http().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'
    POST:
    '.$valasz_xml.'



    ';
    file_put_contents($log_utvonal, $log, FILE_APPEND | LOCK_EX);
    
    // Logolás
    $log_utvonal = '../andoc.log';
    $log = '*****************************
    KAPOTT XML (Igazolás/elutasításra válaszként)
    '.date('Y.m.d H:i:s').'
    Ip: '.$_SERVER['REMOTE_ADDR'].'
    URL: '.get_http().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'
    POST:
    '.$valasz.'
        
        
        
    ';
    file_put_contents($log_utvonal, $log, FILE_APPEND | LOCK_EX);

    // A kapott választ feldolgozató állapotra kell hozni, hogy értelmezni lehessen, ehhez ki kell törölni belőle a sallangot
    if( eles_es_teszt_rendszer_e() ) {
        $mit   = array('<?xml version="1.0" encoding="utf-8"?>', '<string xmlns="AndocWebServices.WebService_AndocSimple"><?xml version="1.0" encoding="utf-8" standalone="yes"?>', '</xml></string>');
        $mivel = array('',                                       '',                                                                                                                '</xml>'         );
        $kapott_xml = new SimpleXMLElement(trim(str_replace($mit, $mivel, htmlspecialchars_decode($valasz))));
    }

    // Ha Andoc-ék nem tudják fogadni a választ, akkor hibaüzenet
    $extra_hibauzenet = '';
    if( eles_es_teszt_rendszer_e() && $kapott_xml -> result != 'I' ) {
        $extra_hibauzenet = 'Hiba: '.$kapott_xml -> errorcode.' - '.$kapott_xml -> errormessage;
        addMegj('anyag_szamla', $szamla_id, 'Az alábbi válasz XML-t nem sikerült elküldeni:<br>'.htmlspecialchars($valasz_xml).'<br><br>'.$extra_hibauzenet);
        $db_error = true;
    // Ezt csak akkor módosítjuk, ha az XML küldése is rendben volt
    } else {
        $xml = $szamla['kuld_xml'].
               date('Y-m-d H:i:s').'
               '.$valasz_xml.'



        ';

        $sql = $adatbazis_kapcsolat->u('anyag_szamla',  array('kuld_xml'),
                                                        array($xml),
                                        "`recid`='{$szamla_id}'");
        if( $tetelek === false ) { $db_error = true; }

        // Tételeknél nullázzuk a változott pipát
        $sql = $adatbazis_kapcsolat->u('anyag_szamla_tetel', array('valtozott'), array(0), "`anyag_szamla_recid`='{$szamla_id}' AND `valtozott`='1'");
        if( $sql === false ) { $db_error = true; }
    }
    
    if( $extra_hibauzenet ) {
        return $extra_hibauzenet;
    } else {
        return $db_error;
    }
    
}