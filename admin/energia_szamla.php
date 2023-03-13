<?php
require_once('../weblap.php');

if( !_jog(JOG_ENERGIA_SZAMLAK_OLVAS) ) {
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
$akt_tomb               = $energia_szamla_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'számlát';         // Hibaüzenetkor: Biztosan törlöd a $szoveg?

$szamla_html = '';

// Dokumentumok fülhöz kell majd
$tobb_kep = true;
if( $tobb_kep ) {
    function get_tobbkep_darab()    { return 10;    } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '';    } // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;    } // MB-ban
    function get_tobbkep_oszlopnev(){                 // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

}

// Számla küldése igazolásra előtt a tételeket csoportosítjuk
if( isset($_POST['kuldes_igazolasra_csoportosit']) ) {
    
    // A tételeket költségnem + költséghely + ÁFAkulcs szerint csoportosíva küldjük vissza.
    $szamla   = $adatbazis_kapcsolat->s2('kcs_21', 'energia_szamla', "`recid`='{$_POST['id']}'");
    $tetelek  = $adatbazis_kapcsolat->s('brutto_ert dev_br ktghely ktghely_id ktgnem_id afa_kod tol', 'energia_szamla_tetel', "`energia_szamla_recid`='{$_POST['id']}' AND `valtozott`='1'", '', '', "`ktgnem_id`, `ktghely_id`, `afa_kod`, SUBSTRING(`tol`, 1, 7)");
    $tetelek2 = $adatbazis_kapcsolat->s('recid', 'energia_szamla_tetel', "`energia_szamla_recid`='{$_POST['id']}' AND `valtozott`='1'");
    
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
        $brutto_ertek = $adatbazis_kapcsolat->s2("SUM(`{$brutto_mezo}`)", 'energia_szamla_tetel', "`energia_szamla_recid`='{$_POST['id']}' AND `valtozott`='1' AND `ktgnem_id`='{$tetel['ktgnem_id']}' AND `ktghely_id`='{$tetel['ktghely_id']}' AND `afa_kod`='{$tetel['afa_kod']}' AND `tol` LIKE '".substr($tetel['tol'], 0, 7)."%'");
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
// Illetve ekkor írjuk be a könyvelés tételeket is
if( isset($_POST['osszevon_targy_kitoltve_ell']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $targyak = json_decode($_POST['targyak'], '-tömbként-');
    $szamla  = $adatbazis_kapcsolat->s2('kcs_21 telephely_id', 'energia_szamla', "`recid`='{$_POST['id']}'");
    $tetelek = $adatbazis_kapcsolat->s('*', 'energia_szamla_tetel', "`energia_szamla_recid`='{$_POST['id']}' AND `valtozott`='1'", '', '', "`ktgnem_id`, `ktghely_id`, `afa_kod`, SUBSTRING(`tol`, 1, 7)");
    
    $tetel_xml = '';
    $i = 0;
    foreach ( $tetelek as $tetel ) {
        
        $netto_ertek    = $adatbazis_kapcsolat->s2("SUM(`netto_ert`)",  'energia_szamla_tetel', "`energia_szamla_recid`='{$_POST['id']}' AND `valtozott`='1' AND `ktgnem_id`='{$tetel['ktgnem_id']}' AND `ktghely_id`='{$tetel['ktghely_id']}' AND `afa_kod`='{$tetel['afa_kod']}' AND `tol` LIKE '".substr($tetel['tol'], 0, 7)."%'");
        $netto_ertek    = $netto_ertek["SUM(`netto_ert`)"];
        $brutto_ertek   = $adatbazis_kapcsolat->s2("SUM(`brutto_ert`)", 'energia_szamla_tetel', "`energia_szamla_recid`='{$_POST['id']}' AND `valtozott`='1' AND `ktgnem_id`='{$tetel['ktgnem_id']}' AND `ktghely_id`='{$tetel['ktghely_id']}' AND `afa_kod`='{$tetel['afa_kod']}' AND `tol` LIKE '".substr($tetel['tol'], 0, 7)."%'");
        $brutto_ertek   = $brutto_ertek["SUM(`brutto_ert`)"];
        $dev_net_ertek  = $adatbazis_kapcsolat->s2("SUM(`dev_net`)",    'energia_szamla_tetel', "`energia_szamla_recid`='{$_POST['id']}' AND `valtozott`='1' AND `ktgnem_id`='{$tetel['ktgnem_id']}' AND `ktghely_id`='{$tetel['ktghely_id']}' AND `afa_kod`='{$tetel['afa_kod']}' AND `tol` LIKE '".substr($tetel['tol'], 0, 7)."%'");
        $dev_net_ertek  = $dev_net_ertek["SUM(`dev_net`)"];
        $dev_br_ertek   = $adatbazis_kapcsolat->s2("SUM(`dev_br`)",     'energia_szamla_tetel', "`energia_szamla_recid`='{$_POST['id']}' AND `valtozott`='1' AND `ktgnem_id`='{$tetel['ktgnem_id']}' AND `ktghely_id`='{$tetel['ktghely_id']}' AND `afa_kod`='{$tetel['afa_kod']}' AND `tol` LIKE '".substr($tetel['tol'], 0, 7)."%'");
        $dev_br_ertek   = $dev_br_ertek["SUM(`dev_br`)"];
        
        // A számla devizája dönti el, hogy a tételbe melyik nettó mező kerüljön
        if( $szamla['kcs_21'] == KCS_21_HUF ) {
            $netto_mezo     = 'netto_ert';
            $brutto_mezo    = 'brutto_ert';
            $osszeg_xml_be  = $brutto_ertek;
        } else {
            $netto_mezo     = 'dev_net';
            $brutto_mezo    = 'dev_br';
            $osszeg_xml_be  = $dev_br_ertek;
        }
        
        foreach ( $tetel as $kulcs => $value ) {
            $tetel[$kulcs] = htmlspecialchars_decode($tetel[$kulcs]);
        }
        
        // Könyvelés tételek létrehozása
        $sql = $adatbazis_kapcsolat->i('energia_szamla_konyveles',  array('energia_szamla_recid', 'netto_ert',  'brutto_ert',  'dev_net',      'dev_br',      'ktgnem_id',         'jogcim',         'jogcim_recid',          'uzemresz',         'megn',         'ktghely',         'ktghely_id',         'afa_kod',         'afakod_recid',         'afa_nev',         'afa',         'loginnev',                         'insert_dt',    'insert_usr',                             'update_usr'                              ),
                                                                    array($_POST['id'],           $netto_ertek, $brutto_ertek, $dev_net_ertek, $dev_br_ertek, $tetel['ktgnem_id'], $tetel['jogcim'], $tetel['jogcim_recid'],  $tetel['uzemresz'], $tetel['megn'], $tetel['ktghely'], $tetel['ktghely_id'], $tetel['afa_kod'], $tetel['afakod_recid'], $tetel['afa_nev'], $tetel['afa'], $_SESSION[get_db_tabla().'user'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']    )
                                );
        if( $sql === false ) { $db_error = true; }
        
        $tetel_xml .= '
            <row rownumberonpage="'.($i+1).'">
                <property name="Sor megnevezése"><![CDATA['.urlencode(htmlspecialchars_decode($targyak[$i])).']]></property>
                <property name="Bruttó sorösszeg">'.urlencode(htmlspecialchars_decode($osszeg_xml_be)).'</property>
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
    
    $sql = $adatbazis_kapcsolat->u('energia_szamla', 
                                                    array('kuld_tetel_xml', 'tetel_db'  ),
                                                    array($tetel_xml,       $i          ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }
                                                
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
    } else {
        mysqli_rollback($db_kapcs);
    }
                                                
    $weblap -> leallit();
    exit;
}

// Számla küldése igazolásra
if( isset($_POST['kuldes_igazolasra']) ) {
    
    $sor = $adatbazis_kapcsolat->s2('feldolgozva_dt', 'energia_szamla', "`recid`='{$_POST['id']}'");
    if( $sor['feldolgozva_dt'] != '0000-00-00 00:00:00' ) {
        
        echo json_encode(array(
                            'hiba'  => 'A számla már feldolgozott, így nem küldhető újra igazolásra!',
                            'title' => 'Hiba',
                            ));
        
        $weblap -> leallit();
        exit;
    }
    
    $adatbazis_kapcsolat->u('energia_szamla',   array('feldolgozva_dt', 'feldolgozva_usr'                       ),
                                                array('NOW()',          $_SESSION[get_db_tabla().'teljes_nev']  ),
                                                "`recid`='{$_POST['id']}'");
    
    echo json_encode(array(
                        'siker' => 1,
                        ));
    
    $weblap -> leallit();
    exit;
}

// Köztes tábla számláinak kilistázása
if( isset($_POST['energia_szamla_get_koztes']) ) {
    
    // Köztes számlákból készítsünk táblázatot
    $html = '';
    $szamlak = $adatbazis_kapcsolat->s('recid andoc_id telephely_id szallito_id szla_szam telj_dt dok_link', 'energia_szamla_atmeneti', "");
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
    $sor = $adatbazis_kapcsolat->s2('igazolva_dt', 'energia_szamla', "`recid`='{$_POST['id']}'");
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
        $sql = $adatbazis_kapcsolat->u('energia_szamla',    array('igazolva', 'igazolva_dt', 'igazolva_usr'                           ),
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
if( isset($_POST['energiaSzamlaElutasit']) ) {
    
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
        $sql = $adatbazis_kapcsolat->u('energia_szamla',    array('igazolva', 'sz_szam', 'elutleir',     'igazolva_dt', 'igazolva_usr',                         'sztornozva_dt', 'sztornozva_usr'                       ),
                                                            array(0,          '',        $_POST['memo'], 'NOW()',       $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',         $_SESSION[get_db_tabla().'teljes_nev'] ),
                                        "`recid`='{$_POST['id']}'");
        if( $sql === false ) { $db_error = true; }
        
        // Megjegyzésbe is kerüljön be az indoklás
        if( addMegj('energia_szamla', $_POST['id'], 'Teljesítés igazolás elutasítva.<br>Indoklás: '.nl2br2($_POST['memo'])) === false ) {
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
if( isset($_POST['energiaSzamlaVisszaUi']) ) {
    
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
    $sql = $adatbazis_kapcsolat->u('energia_szamla',    array('feldolgozva_dt',      'feldolgozva_usr'  ),
                                                        array('0000-00-00 00:00:00', ''                 ),
                                                        "`recid`='{$_POST['recid']}'");
    if( $sql === false ) { $db_error = true; }
    
    // Könyvelés tételeket ekkor töröljük
    $sql = $adatbazis_kapcsolat -> d('energia_szamla_konyveles', "`energia_szamla_recid`='{$_POST['recid']}'");
    if( $sql === false ) { $db_error = true; }
    
    // Megjegyzésben logoljuk
    if( addMegj('energia_szamla', $_POST['recid'], 'Igazoló visszaadta számlát ügyintézőnek.<br>Indoklás: '.nl2br2($_POST['memo'])) === false ) {
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
if( isset($_POST['energiaSzamlaSztornoz']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_ENERGIA_SZAMLAK_KEZEL) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga számlát sztornózni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }
        
    // Sztornózással kapcsolatos adatok beállítása, illetve a kapcsolt számlaszám ürítése
    $sql = $adatbazis_kapcsolat->u('energia_szamla',    array('sz_szam', 'sztornozva_leir',   'sztornozva_dt', 'sztornozva_usr'                       ),
                                                        array('',        $_POST['memo'],      'NOW()',         $_SESSION[get_db_tabla().'teljes_nev'] ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }
    
    // Megjegyzésbe is kerüljön be az indoklás
    if( addMegj('energia_szamla', $_POST['id'], 'Számla sztornózva.<br>Indoklás: '.nl2br2($_POST['memo'])) === false ) {
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
        $szamla = $adatbazis_kapcsolat->s2('recid dev_net netto kcs_21 feldolgozva_dt tetel_elter', 'energia_szamla_atmeneti', "`andoc_id`='{$_POST['andoc_id']}'");
    } else {
        $szamla = $adatbazis_kapcsolat->s2('dev_net netto kcs_21 feldolgozva_dt tetel_elter', 'energia_szamla', "`recid`='{$_POST['id']}'");
    }
    
    // Ha már fel van dolgozva egy számla, és a tételek eltérése sem áll fenn, akkor a tételei már nem szerkeszthetőek
    if( $szamla['feldolgozva_dt'] != '0000-00-00 00:00:00' && !$szamla['tetel_elter'] ) {
        echo json_encode( array(
            'hiba'  => 'A számla tételei már nem szerkeszthetőek!',
            'title' => 'Hiba',
            ) );
        $weblap -> leallit();
        exit;
    }
    
    // Illetve ezt az alábbi változókkal két ágra bontuk: Ft-os vagy devizás-e a számla.
    if( $szamla['kcs_21'] && $szamla['kcs_21']!=KCS_21_HUF ) {
        $szamla_netto_mezo      = 'dev_net';
        $penznem                = erteklista(ERTEKLISTA_PENZNEM, $szamla['kcs_21']);
        $tizedesek              = 2;
        $session_mezo           = 'energia_szla_tetel_dev_net';
        $session_mezo_br        = 'energia_szla_tetel_dev_br';
    } else {
        $szamla_netto_mezo      = 'netto';
        $penznem                = get_penznem();
        $tizedesek              = 0;
        $session_mezo           = 'energia_szla_tetel_netto';
        $session_mezo_br        = 'energia_szla_tetel_brutto';
    }
    
    // Tételek táblázat sorait kiszámoljuk:
    $html = '';
    $i = 0;
    foreach( $_SESSION['energia_szla_tetel_jogcim'] as $jogcim ) {
        
        $torles = '';
        // Ha eltérő tételek volnának, akkor a törlés ikon helyett "zöld pipa/piros x"-eket jelenítünk meg
        if( $szamla['tetel_elter'] ) {
            if( $_SESSION['energia_szla_tetel_kifizetes'][$i] ) {
                $torles = '<span class="zold f20 b pop_pipalo" id="pipa_'.$_SESSION['energia_szla_tetel_recid'][$i].'" onclick="pipa_x_cserel(\''.$_SESSION['energia_szla_tetel_recid'][$i].'\')">✓</span>';
            } else {
                $torles = '<span class="piros f20 b pop_pipalo" id="pipa_'.$_SESSION['energia_szla_tetel_recid'][$i].'" onclick="pipa_x_cserel(\''.$_SESSION['energia_szla_tetel_recid'][$i].'\')">✖</span>';
            }
        // Törlés ikon, ha nem Andoc-os a tétel
        } else if( !$_SESSION['energia_szla_tetel_andocos'][$i] ) {
            $torles = '
                        <a onclick="popup_tetel_sor_torol(\'pop_tetel_'.$_SESSION['energia_szla_tetel_recid'][$i].'\');">
                            <span class="icon">
                                <i class="material-icons">
                                    delete
                                </i>
                            </span>
                        </a>
                ';
        }
        
        // Minden sornak adunk egy azonosítót. Mivel lehet csak egy ideiglenes sorról van szó, nem mindig van recid-nk, ezért időbélyeges azonosítót hozunk létre, megtoldva egy $i-vel.
        if( !$_SESSION['energia_szla_tetel_recid'][$i] ) {
            $_SESSION['energia_szla_tetel_recid'][$i] = time().rand(0, 1000);
        }

        $jogcim  = $adatbazis_kapcsolat->s2('jogcim', 'jogcim',     "`recid`='{$_SESSION['energia_szla_tetel_jogcim'][$i]}'");
        $afakod  = $adatbazis_kapcsolat->s2('afa kod','afakod',     "`recid`='{$_SESSION['energia_szla_tetel_afakod'][$i]}'");
        $dijelem = $adatbazis_kapcsolat->s2('info',   'erteklista', "`recid`='{$_SESSION['energia_szla_tetel_dijelem'][$i]}'");
        $aranyos = '';
        if( $_SESSION['energia_szla_tetel_aranyos'][$i] ) {
            $aranyos .= '✓';
        }
        $class = '';
        if( $_SESSION['energia_szla_tetel_kifizetes'][$i] ) {
            $class = ' piros';
        }
        
        // Pár oszlop csak akkor kerül bele, ha nem eltérő tételek miatt vagyunk itt
        $tovabbi_oszlopok = $tovabbi_oszlopok2 = '';
        if( $szamla['tetel_elter'] ) {
            $tovabbi_oszlopok .= nl2('
                                    <td>'.$_SESSION['energia_szla_tetel_fokonyvi'][$i].'</td>
                                    <td>'.$_SESSION['energia_szla_tetel_ktg_hely'][$i].'</td>
                                ');
            if( $szamla['kcs_21']==KCS_21_HUF ) {
                $tovabbi_oszlopok2 .= nl2('
                                        <td class="pop_netto nowrap r">'.$_SESSION['energia_szla_tetel_netto'][$i].'</td>
                                        <td class="pop_brutto nowrap r">'.$_SESSION['energia_szla_tetel_brutto'][$i].'</td>
                                    ');
            } else {
                $tovabbi_oszlopok2 .= nl2('
                                        <td class="pop_netto nowrap r">'.$_SESSION['energia_szla_tetel_netto'][$i].'</td>
                                        <td class="pop_brutto nowrap r">'.$_SESSION['energia_szla_tetel_brutto'][$i].'</td>
                                        <td class="pop_dev_net nowrap r">'.$_SESSION['energia_szla_tetel_dev_net'][$i].'</td>
                                        <td class="pop_dev_br nowrap r">'.$_SESSION['energia_szla_tetel_dev_br'][$i].'</td>
                                    ');
            }
            
        // Popup tételek normál esetben, sima tételek szerkesztésekor
        } else {
            $tovabbi_oszlopok .= nl2('
                            <td class="pop_dijelem_txt">'.$dijelem['info'].'</td>
                            <td class="pop_aranyos">'.$aranyos.'</td>
                            <td class="l">
                                <span class="pop_tol nowrap">'.datum_kiir($_SESSION['energia_szla_tetel_tol'][$i]).'</span>
                                <span class="pop_ig nowrap">'.datum_kiir($_SESSION['energia_szla_tetel_ig'][$i]).'</span>
                            </td>
                            <td class="l">
                                <span class="pop_indul nowrap">'.$_SESSION['energia_szla_tetel_indul'][$i].'</span>
                                <span class="pop_zar nowrap">'.$_SESSION['energia_szla_tetel_zar'][$i].'</span>
                            </td>
                            <td class="pop_szorzo nowrap">'.$_SESSION['energia_szla_tetel_szorzo'][$i].'</td>
                            <td class="pop_mert nowrap">'.$_SESSION['energia_szla_tetel_mert'][$i].'</td>
                            <td class="pop_kwh_szorzo nowrap">'.$_SESSION['energia_szla_tetel_kwh_szorzo'][$i].'</td>
                            <td class="pop_menny nowrap">'.$_SESSION['energia_szla_tetel_menny'][$i].'</td>
                            <td class="pop_egyseg nowrap">'.$_SESSION['energia_szla_tetel_egyseg'][$i].'</td>
                            <td class="pop_menny_kwh nowrap">'.$_SESSION['energia_szla_tetel_menny_kwh'][$i].'</td>
                            <td class="pop_egyseg_ar nowrap">'.$_SESSION['energia_szla_tetel_egyseg_ar'][$i].'</td>
                ');
            
            $tovabbi_oszlopok2 .= nl2('
                                    <td class="pop_netto nowrap">'.$_SESSION[$session_mezo][$i].'</td>
                                    <td class="pop_brutto nowrap">'.$_SESSION[$session_mezo_br][$i].'</td>
                                ');
        }
        $html .= nl2('
                        <tr class="pop_tetel_'.$_SESSION['energia_szla_tetel_recid'][$i].$class.'">
                            <td class="l pop_jogcim_txt">'.$jogcim['jogcim'].'</td>
                            '.$tovabbi_oszlopok.'
                            '.$tovabbi_oszlopok2.'
                            <td class="nowrap">'.$afakod['afa'].'</td>
                            <td>
                                '.$torles.'
                                <input type="hidden" class="pop_afa" name="pop_afakod_id" value="'.$_SESSION['energia_szla_tetel_afakod'][$i].'">
                                <input type="hidden" class="pop_jogcim" name="pop_jogcim_id" value="'.$_SESSION['energia_szla_tetel_jogcim'][$i].'">
                                <input type="hidden" class="pop_dijelem" name="pop_dijelem_id" value="'.$_SESSION['energia_szla_tetel_dijelem'][$i].'">
                                <input type="hidden" class="tetel_nullas" value="'.$_SESSION['energia_szla_tetel_nullas'][$i].'">
                                <input type="hidden" class="pop_valtozott" value="'.$_SESSION['energia_szla_tetel_valtozott'][$i].'">
                                <input type="hidden" class="pop_recid" name="recid" value="'.$_SESSION['energia_szla_tetel_recid'][$i].'">
                            </td>
                        </tr>');
        $i++;
    }
    
    require_once 'selectek.php';
    
    // Jogcím select előállítása
    $jogcim_select = '<select id="pop_jogcim" onchange="dijelem_select()" class="csillag">';
    foreach ( $select_jogcim as $jogcim ) {
        $jogcim_select .= '<option value="'.$jogcim['value'].'">'.$jogcim['name'].'</option>';
    }
    $jogcim_select .= '</select>';
    
    
    
    // Ha tételek száma eltér, akkor kicsit más popup jön fel: pl. nincsenek inputok
    $input_mutat = '1';
    $css_kod     = '';
    if( $szamla['tetel_elter'] ) {
        $input_mutat = '';
        // Ha eltérő tételek miatt vagyunk itt, akkor a jogcím select-hez hozzáadunk még egy CSS kódot, hogy a táblázat fejlécét elrejtsük
        $css_kod .= '<style type="text/css">.eltereskor_rejt { display: none; }</style>';
    } else {
        $css_kod .= '<style type="text/css">.alapbol_rejt { display: none; }</style>';
    }
    
    echo json_encode( array(
                        'html'          => $html,
                        'css'           => $css_kod,
                        'jogcim_select' => $jogcim_select,
                        'input_mutat'   => $input_mutat,
                    ) );
    
    $weblap -> leallit();
    exit();
}

// Díjelem select előállítása
if( isset($_POST['dijelem_select']) ) {

    $dijelem_select  = '<select id="pop_dijelem" class="csillag" onchange="dijelem_ertek_betolt()">';
    $dijelem_select .= '<option value="">-Válasszon-</option>';
    $jogcim     = $adatbazis_kapcsolat->s2('szla_szam', 'jogcim', "`recid`='{$_POST['jogcim_recid']}'");
    $erteklista = $adatbazis_kapcsolat->s('recid info', 'erteklista', "`csoport`='".ERTEKLISTA_DIJELEM."' AND `ktgnem`='{$jogcim['szla_szam']}'");
    foreach ( $erteklista as $ertek ) {
        $dijelem_select .= '<option value="'.$ertek['recid'].'">'.$ertek['info'].'</option>';
    }
    $dijelem_select .= '</select>';
    
    echo json_encode( array(
                        'dijelem_select' => $dijelem_select,
                    ) );
    
    $weblap -> leallit();
    exit();
}

// Díjelemet kiválasztva az értéklista mezőit töltsük be 
if( isset($_POST['dijelem_ertek_betolt']) ) {

    $erteklista = $adatbazis_kapcsolat->s2('mee afa kwh_szorzo', 'erteklista', "`recid`='{$_POST['dijelem_recid']}'");
    
    // Ha csak egy érték volna benne, akkor nem szerkeszthető
    // (vesszővel felsorolva több érték is lehet benne)
    $meek = explode(',', $erteklista['mee']);
    $disabled = '';
    $elso_option = '<option value="">-Válasszon-</option>';
    if( count($meek)==1 ) {
        $disabled = ' disabled';
        if( $meek[0] ) {
            $elso_option = '';
        }
    }

    $mee_select  = '<select id="pop_egyseg" class="csillag"'.$disabled.'>';
    $mee_select .= $elso_option;
    foreach ( $meek as $mee ) {
        $mee = trim($mee);
        $mee_select .= '<option value="'.$mee.'">'.$mee.'</option>';
    }
    $kwh_szorzo .= '</select>';
    echo json_encode( array(
                        'mee'       => $mee_select,
                        'afa'       => str_replace('.', ',', $erteklista['afa']),
                        'kwh_szorzo'=> str_replace('.', ',', $erteklista['kwh_szorzo']),
                    ) );
    
    $weblap -> leallit();
    exit();
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

// Tételek mentése
if( isset($_POST['tetel_sorok_ment']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $_POST['recid'] == 'false' ) {
        $_POST['recid'] = '';
    }
    $recid                  = $_POST['recid'];
    $szamla_netto_erteke    = str_replace(array(' ', ','), array('', '.'), $_POST['szamla_netto_erteke']);
    $szamla_brutto_erteke   = str_replace(array(' ', ','), array('', '.'), $_POST['szamla_brutto_erteke']);
    $_POST['adatok']        = json_decode( $_POST['adatok'] );
    $arfolyam               = str_replace(array(' ', ','), array('', '.'), $_POST['arfolyam']);
    
    // Ha már fel van dolgozva egy számla, és a tételek eltérése sem áll fenn, akkor a tételei már nem szerkeszthetőek
    if( $recid ) {
        $szamla = $adatbazis_kapcsolat->s2('feldolgozva_dt tetel_elter', 'energia_szamla', "`recid`='{$recid}'");
        if( $szamla === false ) { $db_error = true; }
        if( $szamla['feldolgozva_dt'] != '0000-00-00 00:00:00' && !$szamla['tetel_elter'] ) {
            echo json_encode( array(
                'hiba'  => 'A számla tételei már nem szerkeszthetőek!',
                'title' => 'Hiba',
                ) );
            $weblap -> leallit();
            exit;
        }
    }
    
    // Kapott tömb indexeit cseréljük beszédesebb változókra
    $recid_adat     = 0;
    $jogcim_adat    = 1;
    $dijelem_adat   = 2;
    $aranyos_adat   = 3;
    $tol_adat       = 4;
    $ig_adat        = 5;
    $indul_adat     = 6;
    $zar_adat       = 7;
    $szorzo_adat    = 8;
    $mert_adat      = 9;
    $kwh_szorzo_adat= 10;
    $menny_adat     = 11;
    $egyseg_adat    = 12;
    $menny_kwh_adat = 13;
    $egyseg_ar_adat = 14;
    $afa_adat       = 15;
    $netto_adat     = 16;
    $brutto_adat    = 17;
    $nullas_adat    = 18;
    $valtozott_adat = 19;
    $pipalo_adat    = 20;

    // Tétel összeg ellenőrzése, illetve a számadatok igazítása
    // Ennek van egy 2. fele később az ellenőrzéseknél
    $tetelek_osszege = 0;
    $i = 0;
    foreach ( $_POST['adatok'][$netto_adat][1] as $ertek ) {
        $_POST['adatok'][$indul_adat        ] [1][$i] = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$indul_adat     ] [1][$i]);
        $_POST['adatok'][$zar_adat          ] [1][$i] = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$zar_adat       ] [1][$i]);
        $_POST['adatok'][$szorzo_adat       ] [1][$i] = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$szorzo_adat    ] [1][$i]);
        $_POST['adatok'][$mert_adat         ] [1][$i] = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$mert_adat      ] [1][$i]);
        $_POST['adatok'][$kwh_szorzo_adat   ] [1][$i] = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$kwh_szorzo_adat] [1][$i]);
        $_POST['adatok'][$menny_adat        ] [1][$i] = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$menny_adat     ] [1][$i]);
        $_POST['adatok'][$menny_kwh_adat    ] [1][$i] = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$menny_kwh_adat ] [1][$i]);
        $_POST['adatok'][$egyseg_ar_adat    ] [1][$i] = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$egyseg_ar_adat ] [1][$i]);
        $_POST['adatok'][$netto_adat        ] [1][$i] = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$netto_adat     ] [1][$i]);
        $_POST['adatok'][$brutto_adat       ] [1][$i] = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$brutto_adat    ] [1][$i]);
        // Egy tétellel akkor kalkulálunk, ha nincs eltérés a tételek számában, vagy ha van, akkor a pipálója igaz
        if( !$szamla['tetel_elter'] || $_POST['adatok'][$pipalo_adat][1][$i] ) {
            $tetelek_osszege += $_POST['adatok'][$netto_adat] [1][$i];
        }
        $i++;
    }
    $tetelek_br_osszege = 0;
    $i = 0;
    foreach ( $_POST['adatok'][$brutto_adat][1] as $ertek ) {
        $_POST['adatok'][$brutto_adat][1][$i]   = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$brutto_adat][1][$i]);
        // Egy tétellel akkor kalkulálunk, ha nincs eltérés a tételek számában, vagy ha van, akkor a pipálója igaz
        if( !$szamla['tetel_elter'] || $_POST['adatok'][$pipalo_adat][1][$i] ) {
            $tetelek_br_osszege += $_POST['adatok'][$brutto_adat][1][$i];
        }
        $i++;
    }

    // Deviza esetén tört számokkal is számolunk
    $penznem   = get_penznem();
    $devizas   = false;
    $tizedesek = 0;
    $db_mezo   = 'netto';
    $kerekit   = KEREKITESI_HATAR_HUF;
    if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
        $devizas   = true;
        $tizedesek = 2;
        $penznem   = erteklista(ERTEKLISTA_PENZNEM, $_POST['kcs_21']);
        $db_mezo   = 'dev_net';
        $kerekit   = KEREKITESI_HATAR_DEV;
    }
    
    // Ha nincs egy sornyi tételünk sem, akkor hibaüzenet
    if( !$_POST['adatok'][$jogcim_adat][1] ) {
        echo json_encode( array( 
                        'hiba'  => 'Legalább egy számla tételnek szerepelnie kell a táblázatban, üresen nem menthető!',
                        'title' => 'Hiba'
                        ) );
        $weblap->leallit();
        exit();
                
    // Ha a számla nettó értéke nem egyenlő a tételek díj összegével, akkor hibaüzenet
    } elseif( abs($tetelek_osszege - $szamla_netto_erteke) > $kerekit ) {
        $felirat = 'számla';
        if( $szamla['tetel_elter'] ) {
            $felirat = 'könyvelés';
        }
        echo json_encode( array( 
                        'hiba'  => 'A '.$felirat.' tételeinek összege ('. number_format($tetelek_osszege, $tizedesek, ',', ' ').' '.$penznem.') nem egyezik meg a számla nettó értékével ('.number_format($szamla_netto_erteke, $tizedesek, ',', ' ').' '.$penznem.')!<br>Így nem menthető!',
                        'title' => 'Hiba'
                        ) );
        $weblap->leallit();
        exit();
        
    // Ha a számla bruttó értéke nem egyenlő a tételek bruttó összegével, akkor hibaüzenet
    // Ezt könyvelés tételeknél NEM kell ellenőrizni
    } elseif( !$szamla['tetel_elter'] && abs($tetelek_br_osszege - $szamla_brutto_erteke) > $kerekit ) {
        echo json_encode( array( 
                        'hiba'  => 'A számla tételeinek bruttó összege ('. number_format($tetelek_br_osszege, $tizedesek, ',', ' ').' '.$penznem.') nem egyezik meg a számla bruttó értékével ('.number_format($szamla_brutto_erteke, $tizedesek, ',', ' ').' '.$penznem.')!<br>Így nem menthető!',
                        'title' => 'Hiba'
                        ) );
        $weblap->leallit();
        exit();
                
    // Tételek kimentése Session-be
    } else {
        
        // Ha nem ismert a recid, akkor új feltöltésről van szó, ekkor a session neveknél kicsit más megnevezést használunk
        // hogy még véletlen se keveredjen pl. másik tabon megnyitott számla tételek szereksztésével voltak gondok, azt mentette be az újhoz
        $kieg = '';
        if( !$recid ) {
            $kieg = '_uj';
        }
        
        $_SESSION['energia_szla_tetel_recid'.$kieg]     = $_POST['adatok'][$recid_adat][1];
        $_SESSION['energia_szla_tetel_jogcim'.$kieg]    = $_POST['adatok'][$jogcim_adat][1];
        $_SESSION['energia_szla_tetel_dijelem'.$kieg]   = $_POST['adatok'][$dijelem_adat][1];
        $_SESSION['energia_szla_tetel_aranyos'.$kieg]   = $_POST['adatok'][$aranyos_adat][1];
        $_SESSION['energia_szla_tetel_tol'.$kieg]       = $_POST['adatok'][$tol_adat][1];
        $_SESSION['energia_szla_tetel_ig'.$kieg]        = $_POST['adatok'][$ig_adat][1];
        $_SESSION['energia_szla_tetel_indul'.$kieg]     = $_POST['adatok'][$indul_adat][1];
        $_SESSION['energia_szla_tetel_zar'.$kieg]       = $_POST['adatok'][$zar_adat][1];
        $_SESSION['energia_szla_tetel_szorzo'.$kieg]    = $_POST['adatok'][$szorzo_adat][1];
        $_SESSION['energia_szla_tetel_mert'.$kieg]      = $_POST['adatok'][$mert_adat][1];
        $_SESSION['energia_szla_tetel_kwh_szorzo'.$kieg]= $_POST['adatok'][$kwh_szorzo_adat][1];
        $_SESSION['energia_szla_tetel_menny'.$kieg]     = $_POST['adatok'][$menny_adat][1];
        $_SESSION['energia_szla_tetel_egyseg'.$kieg]    = $_POST['adatok'][$egyseg_adat][1];
        $_SESSION['energia_szla_tetel_menny_kwh'.$kieg] = $_POST['adatok'][$menny_kwh_adat][1];
        $_SESSION['energia_szla_tetel_egyseg_ar'.$kieg] = $_POST['adatok'][$egyseg_ar_adat][1];
        if( $devizas ) {
            $_SESSION['energia_szla_tetel_dev_net'.$kieg] = str_replace(',', '.', $_POST['adatok'][$netto_adat][1]);
            $_SESSION['energia_szla_tetel_dev_br'.$kieg]  = str_replace(',', '.', $_POST['adatok'][$brutto_adat][1]);
        } else {
            $_SESSION['energia_szla_tetel_netto'.$kieg]     = $_POST['adatok'][$netto_adat][1];
            $_SESSION['energia_szla_tetel_brutto'.$kieg]    = $_POST['adatok'][$brutto_adat][1];
        }
        $_SESSION['energia_szla_tetel_afakod'.$kieg]      = $_POST['adatok'][$afa_adat][1];
        $_SESSION['energia_szla_tetel_nullas'.$kieg]      = $_POST['adatok'][$nullas_adat][1];
        $_SESSION['energia_szla_tetel_valtozott'.$kieg]   = $_POST['adatok'][$valtozott_adat][1];
    }
    
    // Ha tétel szám eltérés miatt járunk itt, és még nincs "mehet" adatunk, akkor előbb rákérdezünk
    if( $szamla['tetel_elter'] && !$_POST['mehet'] ) {
        echo json_encode( array( 
                        'hiba'  => 'Biztosan elmenti a zöld pipálval jelölt sorokat?',
                        'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="tetel_sorok_ment(\'-mehet-\');"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">',
                        'title' => 'Kérdés'
                        ) );
                $weblap->leallit();
                exit();
    }
    
    // Adatbázisba mentést csak akkor csinálnuk, ha már meglevő számlát szerkesztünk
    if( $recid ) {
        
        // Ha azért kerültünk ide, mert a számla tételei eltérnek a könyvelés tételeitől, akkor ürítsük az összes könyvelés tételt, mert most frissen be fogjuk szúrni őket
        if( $szamla['tetel_elter'] ) {
            $sql = $adatbazis_kapcsolat->d('energia_szamla_konyveles', "`energia_szamla_recid`='{$recid}'");
            if( $sql === false ) { $db_error = true; }
        }
                
        $i = 0;
        $konyveles_recidk = array();
        foreach ( $_POST['adatok'][$jogcim_adat][1] as $jogcim ) {
            
            // Egy tétellel akkor kalkulálunk, ha nincs eltérés a tételek számában, vagy ha van, akkor a pipálója igaz
            if( !$szamla['tetel_elter'] || $_POST['adatok'][$pipalo_adat][1][$i] ) {
            
                // $rownum = $i+1;
                $jogcim    = $_POST['adatok'][$jogcim_adat][1][$i];
                $afakod    = $_POST['adatok'][$afa_adat][1][$i];
                $dijelem   = $_POST['adatok'][$dijelem_adat][1][$i];
                $aranyos   = $_POST['adatok'][$aranyos_adat][1][$i];
                $tol       = $_POST['adatok'][$tol_adat][1][$i];
                $ig        = $_POST['adatok'][$ig_adat][1][$i];
                $indul     = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$indul_adat][1][$i]);
                $zar       = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$zar_adat][1][$i]);
                $szorzo    = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$szorzo_adat][1][$i]);
                $mert      = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$mert_adat][1][$i]);
                $kwh_szorzo= str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$kwh_szorzo_adat][1][$i]);
                $menny     = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$menny_adat][1][$i]);
                $egyseg    = $_POST['adatok'][$egyseg_adat][1][$i];
                $menny_kwh = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$menny_kwh_adat][1][$i]);
                $egyseg_ar = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$egyseg_ar_adat][1][$i]);
                if( $devizas ) {
                    $netto     = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$netto_adat][1][$i])*$arfolyam;
                    $brutto    = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$brutto_adat][1][$i])*$arfolyam;
                    $dev_net   = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$netto_adat][1][$i]);
                    $dev_br    = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$brutto_adat][1][$i]);
                } else {
                    $netto     = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$netto_adat][1][$i]);
                    $brutto    = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$brutto_adat][1][$i]);
                    $dev_net   = 0;
                    $dev_br    = 0;
                }
                $nullas = $_POST['adatok'][$nullas_adat][1][$i];
                $valtozott = 0;
                if( $_POST['adatok'][$valtozott_adat][1][$i] ) {
                    $valtozott = 1;
                }

                // Jogcím esetén még a ktgnem-et is letároljuk
                $jogcim = $adatbazis_kapcsolat->s2('recid jogcim uzemresz megn szla_szam ktghely ktghely_id', 'jogcim', "`recid`='{$jogcim}'");
                $afakod = $adatbazis_kapcsolat->s2('recid nev kod afa', 'afakod', "`recid`='{$afakod}'");

                // Ha ismerjük a tétel recid-ját, akkor update
                if( $_POST['adatok'][$recid_adat][1][$i] ) {

                    // Ha azért kerültünk ide, mert a számla tételei eltérnek a könyvelés tételeitől, akkor a könyvelés táblába írunk
                    if( $szamla['tetel_elter'] ) {
                        $mezok   = array('energia_szamla_recid', 'ktgnem_id',           'ktghely_id',           'ktghely',          'jogcim_recid',   'jogcim',          'uzemresz',          'megn',          'afakod_recid',   'afa',          'afa_kod',      'afa_nev',      'netto_ert', 'brutto_ert', 'dev_net',  'dev_br', 'loginnev',                        'insert_dt',   'insert_usr',                             'update_usr'                          );
                        $ertekek = array($recid,                 $jogcim['szla_szam'],  $jogcim['ktghely_id'],  $jogcim['ktghely'], $jogcim['recid'], $jogcim['jogcim'], $jogcim['uzemresz'], $jogcim['megn'], $afakod['recid'], $afakod['afa'], $afakod['kod'], $afakod['nev'], $netto,      $brutto,      $dev_net,   $dev_br,  $_SESSION[get_db_tabla().'user'],  'NOW()',       $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']);
                        // Ha a recid-ben van egy kifizetés, akkor egy olyan sor volt jóváhagyva, amit egy kifizetésben kaptunk
                        // Ekkor ennek a flexmezőjét még kérjük le
                        if( strpos($_POST['adatok'][$recid_adat][1][$i], '_kifizetes') ) {
                            $kif_recid = str_replace('_kifizetes', '', $_POST['adatok'][$recid_adat][1][$i]);
                            $kif_sor = $adatbazis_kapcsolat->s2('flex', 'energia_szamla_tetel_kifizetes', "`recid`='{$kif_recid}'");
                            array_push($mezok,   'flex'          );
                            array_push($ertekek, $kif_sor['flex']);
                        }
                        $sql = $adatbazis_kapcsolat->i('energia_szamla_konyveles', $mezok, $ertekek);
                        if( $sql === false ) { $db_error = true; }
                    
                    // Sima tételek kezelés popup esete
                    } else {
                        $mezok   = array('ktgnem_id',           'ktghely_id',           'ktghely',          'jogcim_recid',   'jogcim',          'uzemresz',          'megn',          'afakod_recid',   'afa',          'afa_kod',      'afa_nev',      'dijelem_recid', 'aranyos', 'tol', 'ig',   'indul', 'zar', 'szorzo',   'mert', 'kwh_szorzo', 'menny', 'egyseg', 'menny_kwh', 'egyseg_ar', 'netto_ert', 'brutto_ert', 'dev_net',  'dev_br', 'nullas', 'valtozott', 'update_usr'                             );
                        $ertekek = array($jogcim['szla_szam'],  $jogcim['ktghely_id'],  $jogcim['ktghely'], $jogcim['recid'], $jogcim['jogcim'], $jogcim['uzemresz'], $jogcim['megn'], $afakod['recid'], $afakod['afa'], $afakod['kod'], $afakod['nev'], $dijelem,        $aranyos,  $tol,  $ig,    $indul,  $zar,  $szorzo,    $mert,  $kwh_szorzo,  $menny,  $egyseg,  $menny_kwh,  $egyseg_ar,  $netto,      $brutto,      $dev_net,   $dev_br,  $nullas,  $valtozott,  $_SESSION[get_db_tabla().'teljes_nev']   );
                        $sql = $adatbazis_kapcsolat->u('energia_szamla_tetel', $mezok, $ertekek, "`recid`='{$_POST['adatok'][$recid_adat][1][$i]}'");
                        if( $sql === false ) { $db_error = true; }
                    }

                // Egyébként insert
                } else {

                    $mezok   = array('energia_szamla_recid', 'ktgnem_id',           'ktghely_id',           'ktghely',          'jogcim_recid',   'jogcim',          'uzemresz',          'megn',          'afakod_recid',   'afa',          'afa_kod',      'afa_nev',      'dijelem_recid', 'aranyos', 'tol', 'ig',   'indul', 'zar', 'szorzo',   'mert', 'kwh_szorzo', 'menny', 'egyseg', 'menny_kwh', 'egyseg_ar', 'netto_ert', 'brutto_ert', 'dev_net',  'dev_br',  'nullas', 'valtozott', 'loginnev',                          'insert_dt',    'insert_usr',                             'update_usr'                              );
                    $ertekek = array($recid,                 $jogcim['szla_szam'],  $jogcim['ktghely_id'],  $jogcim['ktghely'], $jogcim['recid'], $jogcim['jogcim'], $jogcim['uzemresz'], $jogcim['megn'], $afakod['recid'], $afakod['afa'], $afakod['kod'], $afakod['nev'], $dijelem,        $aranyos,  $tol,  $ig,    $indul,  $zar,  $szorzo,    $mert,  $kwh_szorzo,  $menny,  $egyseg,  $menny_kwh,  $egyseg_ar,  $netto,      $brutto,      $dev_net,   $dev_br,   $nullas,  '1',          $_SESSION[get_db_tabla().'user'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']    );
                    
                    // Ha azért kerültünk ide, mert a számla tételei eltérnek a könyvelés tételeitől, akkor beszúrjuk a könyvelés tételei közé
                    if( $szamla['tetel_elter'] ) {
                        $uj_id = $adatbazis_kapcsolat->i('energia_szamla_konyveles', $mezok, $ertekek);
                        if( $sql === false ) { $db_error = true; }
                    } else {
                        $uj_id = $adatbazis_kapcsolat->i('energia_szamla_tetel', $mezok, $ertekek);
                        if( $sql === false ) { $db_error = true; }
                        $_SESSION['energia_szla_tetel_recid'][$i] = $uj_id;
                    }

                }
            }
            
            $i++;
        }
        
        // Ha kell a törölt sorok törlése adatbázisból is
        $_POST['torolt_sorok'] = trim($_POST['torolt_sorok'], 'pop_tetel_');
        $torlendo_idk = explode('pop_tetel_', $_POST['torolt_sorok']);
        $torlendo_idk_kiszurve = array();
        foreach ( $torlendo_idk as $id ) {
            if( strpos($id, '_kifizetes') === false ) {
                array_push($torlendo_idk_kiszurve, $id);
            }
        }
        if( count($torlendo_idk_kiszurve) ) {
            $sql = $adatbazis_kapcsolat->d('energia_szamla_tetel', "`recid` IN ('". implode('\', \'', $torlendo_idk_kiszurve)."')");
            if( $sql === false ) { $db_error = true; }
        }
    }
    
    // Tételek mentésekor a számláról vegyük le a tételek eltérése pipát, mert ha ilyen állapotban is lett volna, ezzel a lépéssel már biztosan nem az
    $sql = $adatbazis_kapcsolat->u('energia_szamla', array('tetel_elter'), array('0'), "`recid`='{$recid}'");
    if( $sql === false ) { $db_error = true; }
    // Továbbá a kifizetéskor kapott tételeket is töröljük, ha volna
    $sql = $adatbazis_kapcsolat->d('energia_szamla_tetel_kifizetes', "`energia_szamla_recid`='{$recid}'");
    if( $sql === false ) { $db_error = true; }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(   array(    
                                'siker'  => 1,
                                'netto'  => number_format($tetelek_osszege,     $tizedesek, ',', ' '),
                                'brutto' => number_format($tetelek_br_osszege,  $tizedesek, ',', ' '),
                                )
                );
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

// CSV generálása
if( isset($_GET['csv']) ) {

    require_once 'selectek.php';

    // CSV export előkészítése
    $csv_fajl_neve = 'Energia-számla';
    if( isset($_GET['tetelek']) ) {
        $csv_fajl_neve .= '-tételek';
    }
    $datum_mezok    = array('Céghez érkezett', 'Kelte', 'Teljesítés dátuma', 'Fizetési határidő', 'Átváltva', 'Kifizetve', 'Utolsó kifizetés dátuma', 'Vonatkozó időszak (tól)', 'Vonatkozó időszak (ig)');
    $szam_mezok     = array('Árfolyam', 'Deviza nettó', 'Deviza bruttó', 'Egységár', 'Nettó érték', 'Bruttó érték', 'Kifizetett összeg', 'kWh-szorzó', 'Mennyiség (kWh-ban)', 'Mennyiség (számlázási egységben)', 'Mérőállás (induló)', 'Mérőállás (záró)', 'Mért fogyasztás', 'Szorzó / Fűtőérték', 'Tétel nettó érték', 'Tétel bruttó érték', 'Tétel nettó érték (devizában)', 'Tétel bruttó érték (devizában)', 'Áfa (%)');
    $igen_nem_mezok = array('0 értékű számla', 'E-számla', 'Fogyasztással arányos', 'Kifizetve');

    list($mibol, $mibe, $filename, $open) = csv_export_elokeszit($megnevezes);
    if( $open ) {
        
        $lekerdezes_fromtol = substr($_SESSION['akt_sql'], strpos($_SESSION['akt_sql'], 'FROM'));
        
        if( $_SESSION['nyomtatas_ids'] ) {
            
            // Tételek kiexportálása
            if( isset($_GET['tetelek']) ) {
                
                $lekerdezes_fromtol = str_replace('FROM `'.get_db_tabla().$megnevezes.'` ', "FROM `".get_db_tabla().$megnevezes."` JOIN `".get_db_tabla().$megnevezes."_tetel` ON `".get_db_tabla().$megnevezes."`.`recid`=`".get_db_tabla().$megnevezes."_tetel`.`".$megnevezes."_recid`", $lekerdezes_fromtol);

                // Ha van benne limit, akkor a teljes adatbázist kilistázzuk, de csak akkor, ha a limit az alapértelmezett (Ez olyan modulok alatt szükséges, ahol van egy alapértelmezett limit-es szűrés)
                $mindet_listazd = false;
                if( strpos( $_SESSION['akt_sql'], 'LIMIT' )!==false ) {
                    $limit = trim( substr( $_SESSION['akt_sql'], strpos($_SESSION['akt_sql'], 'LIMIT') ), 'LIMIT ' );
                    if( strpos($limit, ' ') ) {
                        $limit = substr($limit, 0, strpos($limit, ' '));
                    }
                    if( $limit == ARCHIV_NAVPANEL_LIMIT && !$_SESSION['nyomtatas_ids_ksz'] ) {
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
                            `'.get_db_tabla().$megnevezes.'_tetel`.`jogcim` as `Jogcím`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`megn` as `Díjelem`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`aranyos` as `Fogyasztással arányos`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`tol` as `Vonatkozó időszak (tól)`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`ig` as `Vonatkozó időszak (ig)`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`indul` as `Mérőállás (induló)`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`zar` as `Mérőállás (záró)`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`szorzo` as `Szorzó / Fűtőérték`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`mert` as `Mért fogyasztás`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`kwh_szorzo` as `kWh-szorzó`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`menny` as `Mennyiség (számlázási egységben)`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`egyseg` as `Mennyiségegység`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`menny_kwh` as `Mennyiség (kWh-ban)`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`egyseg_ar` as `Egységár`,
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

                // Ha van benne limit, akkor a teljes adatbázist kilistázzuk, de csak akkor, ha a limit az alapértelmezett (Ez olyan modulok alatt szükséges, ahol van egy alapértelmezett limit-es szűrés)
                $mindet_listazd = false;
                if( strpos( $_SESSION['akt_sql'], 'LIMIT' )!==false ) {
                    $limit = trim( substr( $_SESSION['akt_sql'], strpos($_SESSION['akt_sql'], 'LIMIT') ), 'LIMIT ' );
                    if( strpos($limit, ' ') ) {
                        $limit = substr($limit, 0, strpos($limit, ' '));
                    }
                    if( $limit == ARCHIV_NAVPANEL_LIMIT && !$_SESSION['nyomtatas_ids_ksz'] ) {
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

    if( isset($_GET['modosit']) || isset($_GET['tetel']) || isset($_GET['tortenet']) /*|| isset($_GET['kifizetesek'])*/ || isset($_GET['konyveles']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'tetel'         => 'Tételek',
            'konyveles'     => 'Könyvelés',
            'tortenet'      => 'Történet',
            // 'kifizetesek'   => 'Kifizetések',
            'dokumentumok'  => 'Dokumentumok'.$dok_db,
            'megjegyzesek'  => 'Megjegyzések'.$megj_db,
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
    
    $js_submit_elott = 'return form_ell_admin();';
    
    // Gombok kirajzolása minden oldalra
    $gombok = '';
    if( isset($_GET['uj']) && _jog(JOG_ENERGIA_SZAMLAK_KEZEL) ) {
        
        $szamla = $adatbazis_kapcsolat->s2('recid kcs_21 ikt_szam', 'energia_szamla_atmeneti', "`andoc_id`='{$_GET['andoc_id']}'");
        
        // Energia számla mentésekor HA nem nullás a számla, akkor ellenőrizzük, hogy a számla nettó értéke azonos-e a tételek összegével
        // Deviza esetén más session-t járunk be
        $tizedesek  = 0;
        $mezo1      = 'ossz_netto';
        $mezo2      = 'netto';
        $kerekit    = KEREKITESI_HATAR_HUF;
        if( $szamla['kcs_21']!=KCS_21_HUF ) {
            $tizedesek  = 2;
            $mezo1      = 'ossz_dev_net';
            $mezo2      = 'dev_net';
            $kerekit    = KEREKITESI_HATAR_DEV;
        }

        // Új felvitelkor updateljük a Session-ben a tételek adatait, így lesz jó a popupban
        $i = 0;
        $_SESSION['energia_szla_tetel_recid']       =
        $_SESSION['energia_szla_tetel_dijelem']     =
        $_SESSION['energia_szla_tetel_jogcim']      =
        $_SESSION['energia_szla_tetel_fokonyvi']    =
        $_SESSION['energia_szla_tetel_ktg_hely']    =
        $_SESSION['energia_szla_tetel_aranyos']     =
        $_SESSION['energia_szla_tetel_tol']         =
        $_SESSION['energia_szla_tetel_ig']          =
        $_SESSION['energia_szla_tetel_indul']       =
        $_SESSION['energia_szla_tetel_zar']         =
        $_SESSION['energia_szla_tetel_szorzo']      =
        $_SESSION['energia_szla_tetel_mert']        =
        $_SESSION['energia_szla_tetel_kwh_szorzo']  =
        $_SESSION['energia_szla_tetel_menny']       =
        $_SESSION['energia_szla_tetel_egyseg']      =
        $_SESSION['energia_szla_tetel_menny_kwh']   =
        $_SESSION['energia_szla_tetel_egyseg_ar']   =
        $_SESSION['energia_szla_tetel_netto']       =
        $_SESSION['energia_szla_tetel_brutto']      =
        $_SESSION['energia_szla_tetel_dev_net']     =
        $_SESSION['energia_szla_tetel_dev_br']      =
        $_SESSION['energia_szla_tetel_afakod']      =
        $_SESSION['energia_szla_tetel_nullas']      =
        $_SESSION['energia_szla_tetel_valtozott']   =
        $_SESSION['energia_szla_tetel_andocos']     =
        $_SESSION['energia_szla_tetel_kifizetes']   =
                                                    array();
        $tetelek = $adatbazis_kapcsolat->s('*', 'energia_szamla_tetel_atmeneti', "`energia_szamla_recid`='{$szamla['recid']}'", 'recid');
        $van_kapcsolt_tetel = false;
        foreach ( $tetelek as $tetel ) {
            $_SESSION['energia_szla_tetel_recid'][$i]       = $tetel['recid'];
            $_SESSION['energia_szla_tetel_dijelem'][$i]     = $tetel['dijelem_recid'];
            $_SESSION['energia_szla_tetel_jogcim'][$i]      = $tetel['jogcim_recid'];
            $_SESSION['energia_szla_tetel_fokonyvi'][$i]    = $tetel['ktgnem_id'];
            $_SESSION['energia_szla_tetel_ktg_hely'][$i]    = $tetel['ktghely_id'];
            $_SESSION['energia_szla_tetel_aranyos'][$i]     = $tetel['aranyos'];
            $_SESSION['energia_szla_tetel_tol'][$i]         = $tetel['tol'];
            $_SESSION['energia_szla_tetel_ig'][$i]          = $tetel['ig'];
            $_SESSION['energia_szla_tetel_indul'][$i]       = number_format($tetel['indul'      ], 4, ',', ' ');
            $_SESSION['energia_szla_tetel_zar'][$i]         = number_format($tetel['zar'        ], 4, ',', ' ');
            $_SESSION['energia_szla_tetel_szorzo'][$i]      = number_format($tetel['szorzo'     ], 4, ',', ' ');
            $_SESSION['energia_szla_tetel_mert'][$i]        = number_format($tetel['mert'       ], 5, ',', ' ');
            $_SESSION['energia_szla_tetel_kwh_szorzo'][$i]  = number_format($tetel['kwh_szorzo' ], 4, ',', ' ');
            $_SESSION['energia_szla_tetel_menny'][$i]       = number_format($tetel['menny'      ], 4, ',', ' ');
            $_SESSION['energia_szla_tetel_egyseg'][$i]      = $tetel['egyseg'];
            $_SESSION['energia_szla_tetel_menny_kwh'][$i]   = number_format($tetel['menny_kwh'  ], 4, ',', ' ');
            $_SESSION['energia_szla_tetel_egyseg_ar'][$i]   = number_format($tetel['egyseg_ar'  ], 4, ',', ' ');
            $_SESSION['energia_szla_tetel_netto'][$i]       = number_format($tetel['netto_ert'  ], 0, ',', ' ');
            $_SESSION['energia_szla_tetel_brutto'][$i]      = number_format($tetel['brutto_ert' ], 0, ',', ' ');
            $_SESSION['energia_szla_tetel_dev_net'][$i]     = number_format($tetel['dev_net'    ], 2, ',', ' ');
            $_SESSION['energia_szla_tetel_dev_br'][$i]      = number_format($tetel['dev_br'     ], 2, ',', ' ');
            $_SESSION['energia_szla_tetel_afakod'][$i]      = $tetel['afakod_recid'];
            $_SESSION['energia_szla_tetel_nullas'][$i]      = $tetel['nullas'];
            $_SESSION['energia_szla_tetel_valtozott'][$i]   = $tetel['valtozott'];
            $_SESSION['energia_szla_tetel_andocos'][$i]     = $tetel['andocos'];
            if( $tetel['energia_szerzodes_tetel_recid'] ) {
                $van_kapcsolt_tetel = true;
            }
            $i++;
        }
        
        // Új számla rögzítésekor így ellenőrizzük a nettó és tételek összegének azonosságát
        $js_submit_elott = nl2('
            var tetelek_ossz = $(\'#'.$mezo1.'\').val().replace(/ /g, \'\').replace(/,/g, \'.\');
            var netto_ossz   = $(\'#'.$mezo2.'\').val().replace(/ /g, \'\').replace(/,/g, \'.\');
            if( $(\'#nullas\').is(\':checked\') == false ) {
                if( Math.abs( Number(tetelek_ossz)-Number(netto_ossz) ) > '.$kerekit.' ) {
                    alert2(\'A számla tételeinek díj összege (\'+tetelek_ossz+\' \'+$(\'#kcs_21_autocomp\').val()+\') nem egyezik meg a számla nettó értékével (\'+$(\'#'.$mezo2.'\').val()+\' \'+$(\'#kcs_21_autocomp\').val()+\')!<br>Így nem menthető!\', \'\', \'Hiba\');
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
        
        // Ha foglalt volna az Andoc azonosító vagy az iktatószám, akkor mentéskor küldjünk hibaüzenetet
        // Éles rendszerben ilyen nem fordulhat elő, de teszelésekkor szükséges, hogy ha gond volna, tudjunk róla
        $onclick = 'mentesGomb();';
        if( $adatbazis_kapcsolat -> s2('recid', 'energia_szamla', "`andoc_id`='{$_GET['andoc_id']}'") ) {
            $onclick = 'alert2(\''.a_az($_GET['andoc_id'], '-nagybetű-').' „'.$_GET['andoc_id'].'” Andoc azonosító már foglalt, így nem menthető a számla!\', \'\', \'Hiba\');';
        }
        if( $adatbazis_kapcsolat -> s2('recid', 'energia_szamla', "`ikt_szam`='{$szamla['ikt_szam']}'") ) {
            $onclick = 'alert2(\''.a_az($szamla['ikt_szam'], '-nagybetű-').' „'.$szamla['ikt_szam'].'” iktatószám már foglalt, így nem menthető a számla!\', \'\', \'Hiba\');';
        }
        
        $gombok = nl2('
            <script>var tetelek_szama_elter = 0;</script>
            <div class="form_kuldes">
                <input type="hidden" name="atmeneti_recid" value="'.$szamla['recid'].'">
                <input type="hidden" id="ossz_netto" value="">
                <input type="hidden" id="ossz_dev_net" value="">
                <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="'.$onclick.'">
                <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
            </div>
        ');     
        $gombok .= '<input type="button" class="gomb" value="Tételek kezelése" onclick="csoportMuvelet(\'energiaszamla_tetelek_kezelese\');"> ';
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

        // Módosításkor updateljük a Session-ben a tételek adatait, így lesz jó a popupban
        $i = 0;
        $_SESSION['energia_szla_tetel_recid']       =
        $_SESSION['energia_szla_tetel_dijelem']     =
        $_SESSION['energia_szla_tetel_jogcim']      =
        $_SESSION['energia_szla_tetel_aranyos']     =
        $_SESSION['energia_szla_tetel_tol']         =
        $_SESSION['energia_szla_tetel_ig']          =
        $_SESSION['energia_szla_tetel_indul']       =
        $_SESSION['energia_szla_tetel_zar']         =
        $_SESSION['energia_szla_tetel_szorzo']      =
        $_SESSION['energia_szla_tetel_mert']        =
        $_SESSION['energia_szla_tetel_kwh_szorzo']  =
        $_SESSION['energia_szla_tetel_menny']       =
        $_SESSION['energia_szla_tetel_egyseg']      =
        $_SESSION['energia_szla_tetel_menny_kwh']   =
        $_SESSION['energia_szla_tetel_egyseg_ar']   =
        $_SESSION['energia_szla_tetel_netto']       =
        $_SESSION['energia_szla_tetel_brutto']      =
        $_SESSION['energia_szla_tetel_dev_net']     =
        $_SESSION['energia_szla_tetel_dev_br']      =
        $_SESSION['energia_szla_tetel_afakod']      =
        $_SESSION['energia_szla_tetel_nullas']      =
        $_SESSION['energia_szla_tetel_valtozott']   =
        $_SESSION['energia_szla_tetel_andocos']     =
        $_SESSION['energia_szla_tetel_kifizetes']   =
                                                    array();
        for( $j = 0; $j < 2; $j++ ) {
            // Alapvetően a számla tételeit írjuk ki
            $tetelek_tabla = 'energia_szamla_tetel';
            // De ha eltérő tétel soraink vannak, akkor már a küldött tételeket mutassuk
            if( $sor['tetel_elter'] ) {
                $tetelek_tabla = 'energia_szamla_konyveles';
            }
            $recid_kieg = '';
            // Ha a 2. ciklusban vagyunk, akkor viszont a kifizetésben kapott tételeket kell lekérni
            if( $j ) {
                $tetelek_tabla = 'energia_szamla_tetel_kifizetes';
                $recid_kieg    = '_kifizetes';
                // Ha nincsenek eltérő sorok, akkor a 2. ciklust ne járjuk be, ekkor nem kell megmutatni az esetleges kifizetéses tétel sorokat
                if( !$sor['tetel_elter'] ) {
                    continue;
                }
            }
            $tetelek = $adatbazis_kapcsolat->s('*', $tetelek_tabla, "`energia_szamla_recid`='{$_GET['id']}'", 'recid');
            $van_kapcsolt_tetel = false;
            foreach ( $tetelek as $tetel ) {
                
                // Ha a kifizetés sorokat nézzük, akkor ott ne legyen ismert a recid
                // (mert mentéskor majd ezt az recid-t akarná frissíteni)
                
                $_SESSION['energia_szla_tetel_recid'][$i]       = $tetel['recid'].$recid_kieg;
                $_SESSION['energia_szla_tetel_dijelem'][$i]     = $tetel['dijelem_recid'];
                $_SESSION['energia_szla_tetel_fokonyvi'][$i]    = $tetel['ktgnem_id'];
                $_SESSION['energia_szla_tetel_ktg_hely'][$i]    = $tetel['ktghely_id'];
                $_SESSION['energia_szla_tetel_jogcim'][$i]      = $tetel['jogcim_recid'];
                $_SESSION['energia_szla_tetel_aranyos'][$i]     = $tetel['aranyos'];
                $_SESSION['energia_szla_tetel_tol'][$i]         = $tetel['tol'];
                $_SESSION['energia_szla_tetel_ig'][$i]          = $tetel['ig'];
                $_SESSION['energia_szla_tetel_indul'][$i]       = number_format($tetel['indul'      ], 4, ',', ' ');
                $_SESSION['energia_szla_tetel_zar'][$i]         = number_format($tetel['zar'        ], 4, ',', ' ');
                $_SESSION['energia_szla_tetel_szorzo'][$i]      = number_format($tetel['szorzo'     ], 4, ',', ' ');
                $_SESSION['energia_szla_tetel_mert'][$i]        = number_format($tetel['mert'       ], 5, ',', ' ');
                $_SESSION['energia_szla_tetel_kwh_szorzo'][$i]  = number_format($tetel['kwh_szorzo' ], 4, ',', ' ');
                $_SESSION['energia_szla_tetel_menny'][$i]       = number_format($tetel['menny'      ], 4, ',', ' ');
                $_SESSION['energia_szla_tetel_egyseg'][$i]      = $tetel['egyseg'];
                $_SESSION['energia_szla_tetel_menny_kwh'][$i]   = number_format($tetel['menny_kwh'  ], 4, ',', ' ');
                $_SESSION['energia_szla_tetel_egyseg_ar'][$i]   = number_format($tetel['egyseg_ar'  ], 4, ',', ' ');
                $_SESSION['energia_szla_tetel_netto'][$i]       = number_format($tetel['netto_ert'  ], 0, ',', ' ');
                $_SESSION['energia_szla_tetel_brutto'][$i]      = number_format($tetel['brutto_ert' ], 0, ',', ' ');
                $_SESSION['energia_szla_tetel_dev_net'][$i]     = number_format($tetel['dev_net'    ], 2, ',', ' ');
                $_SESSION['energia_szla_tetel_dev_br'][$i]      = number_format($tetel['dev_br'     ], 2, ',', ' ');
                $_SESSION['energia_szla_tetel_afakod'][$i]      = $tetel['afakod_recid'];
                $_SESSION['energia_szla_tetel_nullas'][$i]      = $tetel['nullas'];
                $_SESSION['energia_szla_tetel_valtozott'][$i]   = $tetel['valtozott'];
                $_SESSION['energia_szla_tetel_andocos'][$i]     = $tetel['andocos'];
                $_SESSION['energia_szla_tetel_kifizetes'][$i]   = $recid_kieg;
                if( $tetel['energia_szerzodes_tetel_recid'] ) {
                    $van_kapcsolt_tetel = true;
                }
                $i++;
            }
        }

        // Js, a törlés gombhoz
	    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';

        // Jogosutságkezelés
        if( !_jog(JOG_ENERGIA_SZAMLAK_KEZEL) ) {
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Nincs jogosultsága törölni ezt a '.$szoveg.'!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        } else {
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
        }

        // Ha már fel van dolgozva, nincs még igazolva és van hozzá jogunk
        if( $sor['feldolgozva_dt'] != '0000-00-00 00:00:00' && $sor['igazolva_dt']=='0000-00-00 00:00:00' && _jog(SZAMLAK_IGAZOLASA) ) {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt zold" value="Igazolás" onclick="szamla_igazol()"> ';
            $gombok .= '<input type="button" class="gomb modositaskor_rejt piros" value="Elutasítás" onclick="csoportMuvelet(\'energiaSzamlaElutasit\');"> ';
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Vissza üi.-nek" onclick="csoportMuvelet(\'energiaSzamlaVisszaUi\');"> ';
        }

// Saját IP-ről belépve a személyzeten módosítunk, hogy a Könyvelés tételek kezelése tesztelhető legyen
if( $_SERVER['REMOTE_ADDR'] == get_debug_ip() ) {
    $_SESSION[get_db_tabla().'szemelyzet_id'] = MUSZAKI_ADMINISZTRATOR;
}
        // Szerkesztéshez az kell, hogy legyen jogunk, illetve a számla ne legyen még igazolva/elutasítva, vagy ha igen, akkor legyen bepipálva a téttelek eltérnek pipa és a felhasználó MUSZAKI_ADMINISZTRATOR legyen
        if( _jog(JOG_ENERGIA_SZAMLAK_KEZEL) && ($sor['feldolgozva_dt']=='0000-00-00 00:00:00' || ($sor['tetel_elter'] && $_SESSION[get_db_tabla().'szemelyzet_id'] == MUSZAKI_ADMINISZTRATOR ) ) ) {
            // Ha még nincs feldolgozva
            if( $sor['feldolgozva_dt'] == '0000-00-00 00:00:00' ) {
                $gombok .= '<input type="button" class="gomb" value="Küldés igazolásra" onclick="kuldes_igazolasra()"> ';
            }
            
            $cimke = 'Tételek kezelése';
            if( $sor['tetel_elter'] ) {
                $cimke = 'Könyvelés tételek kezelése';
            }
            $gombok .= '<input type="button" class="gomb" value="'.$cimke.'" onclick="csoportMuvelet(\'energiaszamla_tetelek_kezelese\');"> ';
            // Ez a popuphoz kell
            $gombok .= '<input type="hidden" id="kcs_21_autocomp_db" value="'.erteklista(ERTEKLISTA_PENZNEM, $sor['kcs_21']).'">';
            $gombok .= '<input type="hidden" id="kcs_21_db" value="'.$sor['kcs_21'].'">';
            $gombok .= '<input type="hidden" id="netto_db" value="'.$sor['netto'].'">';
            $gombok .= '<input type="hidden" id="dev_net_db" value="'.$sor['dev_net'].'">';
            $gombok .= '<input type="hidden" id="arfolyam_db" value="'.$sor['arfolyam'].'">';
            $gombok .= '<input type="hidden" id="nullas_db" value="'.$sor['nullas'].'">';
            
            // Ha csak azért vagyunk itt, mert a tételek száma eltér, és kell tudni tételeket szerkeszteni, akkor ne legyen elérhető a Mentés/Mégsem gombok
            // azaz ne lehessen az adatlapon már semmit sem módosítani, ez számtalan új bonyodalommal járna, lásd 2022.03.11-én írt mailt
            // Ha meg azért vagyunk itt, akkor meg beállítunk egy JS változót, hogy a popup bezárása után tudjuk, hogy frissíteni kell az adatlapot.
            if( $sor['tetel_elter'] ) {
                $gombok .= '<script>var tetelek_szama_elter = 1;</script>';
            } else {
                $gombok = nl2('
                    <script>var tetelek_szama_elter = 0;</script>
                    <div class="form_kuldes">
                        <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
                        <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
                    </div>
                ').$gombok;
            }
            
        // Ha nem módosítható már az űrlap, akkor a "Szerződés hozzáadása gombot is vegyük le"
        } else {
            $gombok .= '<script>$(document).ready(function () { $(\'#sz_szam_gomb\').hide(); });</script>';
        }

        // Ha van jogunk hozzá és a számla már utalványozva van, akkor lehet stornózni
        if( _jog(JOG_ENERGIA_SZAMLAK_KEZEL) && $sor['utalvanyozva_dt'] != '0000-00-00 00:00:00' && $sor['sztornozva_dt'] == '0000-00-00 00:00:00' ) {
            $gombok .= '<input type="button" class="gomb piros" value="Sztornózás" onclick="csoportMuvelet(\'energiaSzamlaSztornoz\');"> ';
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
        $sor = $adatbazis_kapcsolat->s2('*', 'energia_szamla_atmeneti', "`andoc_id`='{$_GET['andoc_id']}'");
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

        // Számít, hogy ki a költségviselő (befolyásolja, hogy milyen szállodák lesznek a lenyílóban), ezért kell ez az alábbi sor
        $koltsegviselo_szalloda_lenyilohoz = $sor['kolt_vis'];
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

	    // Fentebb már lekértük $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");

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

        // Számít, hogy ki a költségviselő (befolyásolja, hogy milyen szállodák lesznek a lenyílóban), ezért kell ez az alábbi sor
        $koltsegviselo_szalloda_lenyilohoz = $sor['kolt_vis'];
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
            <div class="dt naplo oszl_igazit">
                <div class="dtr nFejlec">
                    <div class="dtc">Jogcím</div>
                    <div class="dtc">Díjelem</div>
                    <div class="dtc">Fogyasztással arányos</div>
                    <div class="dtc">Vonatkozó időszak</div>
                    <div class="dtc">Mérőállás</div>
                    <div class="dtc">Szorzó / Fűtőérték</div>
                    <div class="dtc">Mért fogyasztás</div>
                    <div class="dtc">kWh-szorzó</div>
                    <div class="dtc">Mennyiség (számlázási egységben)</div>
                    <div class="dtc">Mennyiség-egység</div>
                    <div class="dtc">Mennyiség (kWh-ban)</div>
                    <div class="dtc">Egységár</div>
                    <div class="dtc">Nettó érték</div>
                    <div class="dtc">Bruttó érték</div>
                    <div class="dtc">Deviza nettó'.$deviza.'</div>
                    <div class="dtc">Deviza bruttó'.$deviza.'</div>
                    <div class="dtc">ÁFA (%)</div>
                </div>
        ');
        
        // Tételek felsorolása
        $tetelek = $adatbazis_kapcsolat->s('*', 'energia_szamla_tetel', "`energia_szamla_recid`='{$_GET['id']}'", 'recid');
        foreach ( $tetelek as $tetel ) {
            
            $jogcim  = $adatbazis_kapcsolat->s2('jogcim', 'jogcim',   "`recid`='{$tetel['jogcim_recid']}'");
            $afakod  = $adatbazis_kapcsolat->s2('afa',    'afakod',   "`recid`='{$tetel['afakod_recid']}'");
            $dijelem = $adatbazis_kapcsolat->s2('info', 'erteklista', "`recid`='{$tetel['dijelem_recid']}'");
            $aranyos = '';
            if( $tetel['aranyos'] ) {
                $aranyos .= '✓';
            }
            $szamla_html .= nl2('
                <div class="dtr nSor">
                    <div class="dtc">'.htmlspecialchars_decode($jogcim['jogcim']).'</div>
                    <div class="dtc">'.htmlspecialchars_decode($dijelem['info']).'</div>
                    <div class="dtc c">'.$aranyos.'</div>
                    <div class="dtc nowrap">'.datum_kiir($tetel['tol']).'<br>'.datum_kiir($tetel['ig']).'</div>
                    <div class="dtc nowrap">'.number_format($tetel['indul'],        4, ',', ' ').'<br>'.number_format($tetel['zar'], 4, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['szorzo'],     4, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['mert'],       5, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['kwh_szorzo'], 4, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['menny'],      4, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.$tetel['egyseg'].'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['menny_kwh'],  4, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['egyseg_ar'],  4, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['netto_ert'],  0, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['brutto_ert'], 0, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['dev_net'],    2, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['dev_br'],     2, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($afakod['afa'],       0, ',', ' ').'</div>
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

	// Könyvelés
    } elseif( isset($_GET['konyveles']) ) {

        $deviza = $dev_fej = '';
        if( $sor['kcs_21'] != KCS_21_HUF ) {
            $deviza      = ' ('.erteklista(ERTEKLISTA_PENZNEM, $sor['kcs_21']).')';
            $dev_fej = nl2('
                            <div class="dtc r">Deviza nettó'.$deviza.'</div>
                            <div class="dtc r">Deviza bruttó'.$deviza.'</div>
                            ');
        }
        
        $szamla_html .= nl2('
            <div class="dt naplo">
                <div class="dtr nFejlec">
                    <div class="dtc">Jogcím</div>
                    <div class="dtc r">Főkönyvi számlaszám</div>
                    <div class="dtc r">Költséghely</div>
                    <div class="dtc r">Nettó érték</div>
                    <div class="dtc r">Bruttó érték</div>
                    '.$dev_fej.'
                    <div class="dtc">ÁFA kód</div>
                    <div class="dtc r">ÁFA (%)</div>
                </div>
        ');
        
        // Tételek felsorolása
        $tetelek = $adatbazis_kapcsolat->s('*', 'energia_szamla_konyveles', "`energia_szamla_recid`='{$_GET['id']}'", 'recid');
        $szamla  = $adatbazis_kapcsolat->s2('kcs_21', 'energia_szamla', "`recid`='{$_GET['id']}'");
        foreach ( $tetelek as $tetel ) {
            
            $jogcim  = $adatbazis_kapcsolat->s2('jogcim', 'jogcim', "`recid`='{$tetel['jogcim_recid']}'");
            $afakod  = $adatbazis_kapcsolat->s2('afa',    'afakod', "`recid`='{$tetel['afakod_recid']}'");
            $dijelem = $adatbazis_kapcsolat->s2('info', 'erteklista', "`recid`='{$tetel['dijelem_recid']}'");
            
            $aranyos = '';
            if( $tetel['aranyos'] ) {
                $aranyos .= '✓';
            }
            
            $dev_oszlopok = '';
            if( $sor['kcs_21'] != KCS_21_HUF ) {
                $dev_oszlopok = nl2('
                    <div class="dtc r nowrap">'.number_format($tetel['dev_net'], 2, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['dev_br'], 2, ',', ' ').'</div>
                    ');
            }
            
            $szamla_html .= nl2('
                <div class="dtr nSor">
                    <div class="dtc">'.htmlspecialchars_decode($jogcim['jogcim']).'</div>
                    <div class="dtc r nowrap">'.$tetel['ktgnem_id'].'</div>
                    <div class="dtc r nowrap">'.$tetel['ktghely_id'].'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['netto_ert'], 0, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['brutto_ert'], 0, ',', ' ').'</div>
                    '.$dev_oszlopok.'
                    <div class="dtc nowrap">'.$tetel['afa_kod'].'</div>
                    <div class="dtc r nowrap">'.number_format($afakod['afa'], 0, ',', ' ').'</div>
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
//        $kifizetesek = $adatbazis_kapcsolat->s('*', 'energia_szamla_kifizetesek', "`energia_szamla_recid`='{$_GET['id']}'", 'insert_dt');
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

//        if( $tobb_kep ) {
//            require_once('tobb_kep.php');
//        }
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
    
    $szamla = $adatbazis_kapcsolat->s2('ikt_szam targy igazolva andoc_id telephely_id kcs_21 kuld_tetel_xml kuld_xml megjegyz kolt_vis igazolva_usr', 'energia_szamla', "`recid`='{$szamla_id}'");
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
            } else if( $szamla['telephely_id'] == TELEPHELY_KOZP_CRO) {
                $wf_name = TELEPHELY_KOZP_CRO_MOD.'_Könyvelés';
            } else if( $szamla['telephely_id'] == TELEPHELY_DHZ) {
                $wf_name = TELEPHELY_DHZ_MOD.'_Könyvelés';
            } else if( $szamla['telephely_id'] == TELEPHELY_MSZ_GH) {
                $wf_name = TELEPHELY_MSZ_GH_MOD.'_Könyvelés';
            } else if( $szamla['telephely_id'] == TELEPHELY_ANNAB_MSZ) {
                $wf_name = TELEPHELY_ANNAB_MSZ_MOD.'_Könyvelés';
            } else if( $szamla['telephely_id'] == TELEPHELY_MARINA_MSZ) {
                $wf_name = TELEPHELY_MARINA_MSZ_MOD.'_Könyvelés';
            } else {
                $wf_name = $szamla['telephely_id'].'_Könyvelés';
            }
        // Ha "u" szerepel a teljigkuldes-ben
        } else {
            $wf_name = '[AUTO] Utalványozásra küldés';
        }
        // 2022.11.09. 8:42 e-mail alapján ideiglenesen egy másik logika szerint működött, de
        // 2023.01.12-én visszaállítottuk a normálra
//        // Projektszámlákhoz hasonlóan a $wf_name értéke fix értéket kap, kivéve két esetet vagyis itt csak egyet
//        switch ( $szamla['telephely_id'] ) {
//            // case '4100' : $wf_name = '4100_Könyvelés'; break;
//            case TELEPHELY_GUNDEL : $wf_name = '5400_Könyvelés'; break;
//            default               : $wf_name = TELEPHELY_SZK.'_Könyvelés';
//        }
    }
    
    // Központi cukrásztermelő esetén a $szalloda_klaszter számát átírjuk
    $szalloda_klaszter = $szamla['telephely_id'];
    if( $szalloda_klaszter == TELEPHELY_KOZP_CUK ) {
        $szalloda_klaszter = TELEPHELY_KOZP_CUK_MOD;
    }
    if( $szalloda_klaszter == TELEPHELY_GUNDEL ) {
        $szalloda_klaszter = TELEPHELY_GUNDEL_MOD;
    }
    if( $szalloda_klaszter == TELEPHELY_LOVER ) {
        $szalloda_klaszter = TELEPHELY_LOVER_MOD;
    }
    if( $szalloda_klaszter == TELEPHELY_MSZ_GH ) {
        $szalloda_klaszter = TELEPHELY_MSZ_GH_MOD;
    }
    if( $szalloda_klaszter == TELEPHELY_ANNAB_MSZ ) {
        $szalloda_klaszter = TELEPHELY_ANNAB_MSZ_MOD;
    }
    if( $szalloda_klaszter == TELEPHELY_MARINA_MSZ ) {
        $szalloda_klaszter = TELEPHELY_MARINA_MSZ_MOD;
    }
    if( $szalloda_klaszter == TELEPHELY_DZRT ) {
        $szalloda_klaszter = TELEPHELY_DZRT_MOD;
    }
    if( $szalloda_klaszter == TELEPHELY_ZAMARDI ) {
        $szalloda_klaszter = TELEPHELY_ZAMARDI_MOD;
    }
    
    $valasz_xml = 'psInputXml=<?xml version="1.0" encoding="utf-8"?>
<xml>
  <data>
    <register registernumber="'.$szamla['andoc_id'].'" operation="update">
      <workflow_node_name>'.urlencode($wf_name).'</workflow_node_name>
      <properties>
        <container name="NIK1">
          <row rownumberonpage="1">
            <property name="Számla főtípus">Energia számla</property>
            <property name="Igazoló döntés">'.urlencode($igazolva).'</property>
            <property name="Teljesítés igazolás elutasítás oka"><![CDATA['.urlencode($oka).']]></property>
            <property name="Szálloda - Klaszter">'.urlencode($szalloda_klaszter).'</property>
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
            
    // A kapott választ feldolgozható állapotra kell hozni, hogy értelmezni lehessen, ehhez ki kell törölni belőle a sallangot
    if( eles_es_teszt_rendszer_e() ) {
        $mit   = array('<?xml version="1.0" encoding="utf-8"?>', '<string xmlns="AndocWebServices.WebService_AndocSimple"><?xml version="1.0" encoding="utf-8" standalone="yes"?>', '</xml></string>');
        $mivel = array('',                                       '',                                                                                                                '</xml>'         );
        $kapott_xml = new SimpleXMLElement(trim(str_replace($mit, $mivel, htmlspecialchars_decode($valasz))));
    }

    // Ha Andoc-ék nem tudják fogadni a választ, akkor hibaüzenet
    $extra_hibauzenet = '';
    if( eles_es_teszt_rendszer_e() && $kapott_xml -> result != 'I' ) {
        $extra_hibauzenet = 'Hiba: '.$kapott_xml -> errorcode.' - '.$kapott_xml -> errormessage;
        addMegj('energia_szamla', $szamla_id, 'Az alábbi válasz XML-t nem sikerült elküldeni:<br>'.htmlspecialchars($valasz_xml).'<br><br>'.$extra_hibauzenet);
        $db_error = true;
    // Ezt csak akkor módosítjuk, ha az XML küldése is rendben volt
    } else {
        $xml = $szamla['kuld_xml'].
               date('Y-m-d H:i:s').'
               '.$valasz_xml.'



        ';

        $sql = $adatbazis_kapcsolat->u('energia_szamla',    array('kuld_xml'),
                                                            array($xml      ),
                                        "`recid`='{$szamla_id}'");
        if( $sql === false ) { $db_error = true; }

        // Tételeknél nullázzuk a változott pipát
        $sql = $adatbazis_kapcsolat->u('energia_szamla_tetel', array('valtozott'), array(0), "`energia_szamla_recid`='{$szamla_id}' AND `valtozott`='1'");
        if( $sql === false ) { $db_error = true; }
    }
   
    if( $extra_hibauzenet ) {
        return $extra_hibauzenet;
    } else {
        return $db_error;
    }
    
}