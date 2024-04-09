<?php
require_once('../weblap.php');

if( !_jog(JOG_PRSZAMLAK_OLVAS) && !_jog(JOG_PR_SAJAT_SZAMLA_MEGTEKINT) ) {
    if( !$_SESSION[get_db_tabla().'user'] ) {
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
$akt_tomb               = $projektszamla_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'projekt számlát';   // Hibaüzenetkor: Biztosan törlöd a $szoveg?

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


// Számla küldése igazolásra
if( isset($_POST['kuldes_igazolasra']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('feldolgozva_dt', 'projektszamla', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }
    if( $sor['feldolgozva_dt'] != '0000-00-00 00:00:00' ) {
        
        echo json_encode(array(
                            'hiba'  => 'A számla már feldolgozott, így nem küldhető újra igazolásra!',
                            'title' => 'Hiba',
                            ));
        
        $weblap -> leallit();
        exit;
    }
    
    $sql = $adatbazis_kapcsolat->u('projektszamla', array('feldolgozva_dt', 'feldolgozva_usr'                       ),
                                                    array('NOW()',          $_SESSION[get_db_tabla().'teljes_nev']  ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
    } else {
        mysqli_rollback($db_kapcs);
    }

    // Levélküldés
    level_felkeszit_es_kuld('projektszamla', $_POST['id'], ERT_IGAZOLASRA_KULDESKOR, 'projektszamlaIgazolKuld', 'projektszamla_felelose');
    
    echo json_encode(array(
                        'siker' => 1,
                        ));

    $weblap -> leallit();
    exit;
}

// Köztes tábla számláinak kilistázása
if( isset($_POST['projekt_szamla_get_koztes']) ) {
    
    if( $_COOKIE['uj_felvitel_'.$megnevezes] ) {
        
        echo json_encode( array('hiba' => 'Egy másik tabon már megkezdődött egy Projekt számla feltöltése,<br>kérem előbb azt fejezze be, vagy lépjen ki belőle!', 'title' => 'Hiba') );

        $weblap -> leallit();
        exit;
    }
    
    // Köztes számlákból készítsünk táblázatot
    $html = '';
    $szamlak = $adatbazis_kapcsolat->s('recid andoc_id telephely_id szallito_id szla_szam telj_dt dok_link', 'projektszamla_atmeneti', "");
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
                </tbody>
                </table>
            ');
    } else {
        $html .= '<script>popupZar(); alert2(\'Jelenleg nem található feldolgozandó számla.\', \'\', \'Hiba\')</script>';
    }
    
    echo json_encode( array('html' => $html) );
    
    $weblap -> leallit();
    exit;
}

// Számla küldése igazolásra előtt a tételeket csoportosítjuk
if( isset($_POST['kuldes_igazolasra_csoportosit']) ) {
    
    // Igazolásra csak akkor küldhető egy számla, ha minden tétele kapcsolva van már
    $van_nem_kapcsolt_tetel = $adatbazis_kapcsolat->s('recid', 'projektszamla_tetel', "`projektszamla_recid`='{$_POST['id']}' AND `projektszerzodes_tetel_recid`='0'");
    if( $van_nem_kapcsolt_tetel ) {
        ajax_hiba('A számlának vannak még nem kapcsolt tételei, így nem küldhető igazolásra!');
    }
    
    // A tételeket költségnem + költséghely + ÁFAkulcs szerint csoportosíva küldjük vissza.
    $szamla   = $adatbazis_kapcsolat->s2('kcs_21', 'projektszamla', "`recid`='{$_POST['id']}'");
    $tetelek  = $adatbazis_kapcsolat->s('targy brutto_ert dev_br ktghely_id ktgnem_id afa_kod', 'projektszamla_tetel', "`projektszamla_recid`='{$_POST['id']}' AND `valtozott`='1'", '', '', "`ktgnem_id`, `ktghely_id`, `afa_kod`");
    $tetelek2 = $adatbazis_kapcsolat->s('recid', 'projektszamla_tetel', "`projektszamla_recid`='{$_POST['id']}' AND `valtozott`='1'");
    
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
                </tr>
                ');
    
    foreach( $tetelek as $tetel ) {
        $targy_val = '';
        if( $targy_kitoltheto ) {
            $targy_val .= ' value="'.htmlspecialchars_decode($tetel['targy']).'"';
        }
        $brutto_ertek = $adatbazis_kapcsolat->s2("SUM(`{$brutto_mezo}`)", 'projektszamla_tetel', "`projektszamla_recid`='{$_POST['id']}' AND `valtozott`='1' AND `ktgnem_id`='{$tetel['ktgnem_id']}' AND `ktghely_id`='{$tetel['ktghely_id']}' AND `afa_kod`='{$tetel['afa_kod']}'");
        $brutto_ertek = $brutto_ertek["SUM(`{$brutto_mezo}`)"];
        $html .= nl2('
                    <tr>
                        <td><input type="text" class="flat osszevon_targy" maxlength="250"'.$targy_val.'></td>
                        <td>'.number_format($brutto_ertek, $tizedesek, ',', ' ').' '.erteklista(ERTEKLISTA_PENZNEM, $szamla['kcs_21']).'</td>
                        <td>'.$tetel['ktgnem_id'].'</td>
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
    $szamla  = $adatbazis_kapcsolat->s2('kcs_21 telephely_id pr_szam', 'projektszamla', "`recid`='{$_POST['id']}'");
    $tetelek = $adatbazis_kapcsolat->s('*', 'projektszamla_tetel', "`projektszamla_recid`='{$_POST['id']}' AND `valtozott`='1'", '', '', "`ktgnem_id`, `ktghely_id`, `afa_kod`");
    
    // Projekt kód kiszámítása, tételek XML-be bele fog kerülni
    $projekt_kod = '00000';
    if( $szamla['pr_szam'] ) {
        $projekt_kod = str_replace(array('P', '/'), array('', ''), $szamla['pr_szam']);
    }
    
    $tetel_xml = '';
    $i = 0;
    foreach ( $tetelek as $tetel ) {
        
        $netto_ertek    = $adatbazis_kapcsolat->s2("SUM(`netto_ert`)",  'projektszamla_tetel', "`projektszamla_recid`='{$_POST['id']}' AND `valtozott`='1' AND `ktgnem_id`='{$tetel['ktgnem_id']}' AND `ktghely_id`='{$tetel['ktghely_id']}' AND `afa_kod`='{$tetel['afa_kod']}'");
        $netto_ertek    = $netto_ertek["SUM(`netto_ert`)"];
        $brutto_ertek   = $adatbazis_kapcsolat->s2("SUM(`brutto_ert`)", 'projektszamla_tetel', "`projektszamla_recid`='{$_POST['id']}' AND `valtozott`='1' AND `ktgnem_id`='{$tetel['ktgnem_id']}' AND `ktghely_id`='{$tetel['ktghely_id']}' AND `afa_kod`='{$tetel['afa_kod']}'");
        $brutto_ertek   = $brutto_ertek["SUM(`brutto_ert`)"];
        $dev_net_ertek  = $adatbazis_kapcsolat->s2("SUM(`dev_net`)",    'projektszamla_tetel', "`projektszamla_recid`='{$_POST['id']}' AND `valtozott`='1' AND `ktgnem_id`='{$tetel['ktgnem_id']}' AND `ktghely_id`='{$tetel['ktghely_id']}' AND `afa_kod`='{$tetel['afa_kod']}'");
        $dev_net_ertek  = $dev_net_ertek["SUM(`dev_net`)"];
        $dev_br_ertek   = $adatbazis_kapcsolat->s2("SUM(`dev_br`)",     'projektszamla_tetel', "`projektszamla_recid`='{$_POST['id']}' AND `valtozott`='1' AND `ktgnem_id`='{$tetel['ktgnem_id']}' AND `ktghely_id`='{$tetel['ktghely_id']}' AND `afa_kod`='{$tetel['afa_kod']}'");
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
        $sql = $adatbazis_kapcsolat->i('projektszamla_konyveles',  array('projektszamla_recid', 'netto_ert',  'brutto_ert',  'dev_net',      'dev_br',      'ktgnem_id',         'jogcim',         'jogcim_recid',          'uzemresz',         'megn',         'ktghely',         'ktghely_id',         'afa_kod',         'afakod_recid',         'afa_nev',         'afa',         'loginnev',                         'insert_dt',    'insert_usr',                             'update_usr'                              ),
                                                                   array($_POST['id'],          $netto_ertek, $brutto_ertek, $dev_net_ertek, $dev_br_ertek, $tetel['ktgnem_id'], $tetel['jogcim'], $tetel['jogcim_recid'],  $tetel['uzemresz'], $tetel['megn'], $tetel['ktghely'], $tetel['ktghely_id'], $tetel['afa_kod'], $tetel['afakod_recid'], $tetel['afa_nev'], $tetel['afa'], $_SESSION[get_db_tabla().'user'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']    )
                                );
        if( $sql === false ) { $db_error = true; }
        
        $szalloda_nev = getSzallodaNev($szamla['telephely_id'], '-Andoc név-');
        if( substr($szalloda_nev, 0, 7) == '1013 - ' ) {
            $szalloda_nev = '1012 - Ensana Grand Margitsziget';
            // $telephely = $adatbazis_kapcsolat -> s2('andoc_nev', 'telephely', "`andoc_nev` LIKE '2012 - %'");
            // if( $telephely === false ) { $db_error = true; }
            // $szalloda_nev = $telephely['andoc_nev'];
        }
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
                <property name="Szálloda"><![CDATA['.urlencode($szalloda_nev).']]></property>
                <property name="Konszolidációs kód">00</property>
                <property name="Projektkód">'.$projekt_kod.'</property>
                <property name="Egyedi azonosító">000000</property>
            </row>
            ';
        $i++;
    }
    
    $sql = $adatbazis_kapcsolat->u('projektszamla',
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


// Számla igazolása
if( isset($_POST['szamla_igazol']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
        
    // Ha már igazolva volna, akkor hibaüzenet
    $sor = $adatbazis_kapcsolat->s2('elo_igazolva let_fel', 'projektszamla', "`recid`='{$_POST['id']}'");
    if( $sor['elo_igazolva'] ) {
        echo json_encode(array(
                                'hiba'  => 'A számla már igazolva van, így nem igazolható újból!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }
    
    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_PRSZAMLAK_IGAZOL) || $sor['let_fel'] != $_SESSION[get_db_tabla().'login_recid'] ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga számlát igazolni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }
    
    

    $sql = $adatbazis_kapcsolat->u('projektszamla', array('elo_igazolva', 'elo_igazolva_dt', 'elo_igazolva_usr'                     ),
                                                    array(1,              'NOW()',           $_SESSION[get_db_tabla().'teljes_nev'] ),
                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

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

    // Levélküldés
    level_felkeszit_es_kuld('projektszamla', $_POST['id'], ERT_UT_IGAZOLASRA_KULDESKOR, 'projektszamlaUtoIgazolKuld', 'projektszamla_utoigazolok');
    
    $weblap -> leallit();
    exit;
}

// Számla utóigazolása
if( isset($_POST['szamla_utoigazol']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Ha még nincs szerződés kapcsolva, akkor hibaüzenet
    $sor = $adatbazis_kapcsolat->s2('sz_szam igazolva_dt', 'projektszamla', "`recid`='{$_POST['id']}'");
    if( !$sor['sz_szam'] ) {
        echo json_encode(array(
                                'hiba'  => 'A számlához nincs még szerződés kapcsolva, így nem utóigazolható!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }
    
    // Ha már utóigazolva volna, akkor hibaüzenet
    if( $sor['igazolva_dt']!='0000-00-00 00:00:00' ) {
        echo json_encode(array(
                                'hiba'  => 'A számla már utóigazolva van, így nem utóigazolható újból!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }
    
    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_PRSZAMLAK_UTOIGAZOL) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga számlát utóigazolni!',
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
        $sql = $adatbazis_kapcsolat->u('projektszamla', array('igazolva', 'igazolva_dt', 'igazolva_usr'                           ),
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
if( isset($_POST['projektSzamlaElutasit']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('pr_szam let_fel elo_igazolva_dt', 'projektszamla', "`recid`='{$_POST['id']}'");
    
    // Ha nincs joga hozzá, akkor hibaüzenet
    if(
        !(
            ( _jog(JOG_PRSZAMLAK_IGAZOL) && $sor['let_fel'] == $_SESSION[get_db_tabla().'login_recid'] ) ||
            _jog(JOG_PRSZAMLAK_UTOIGAZOL)
        )
       ) {
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
        
        // Ha az előigazolva dátuma még üres, akkor előigazolásnál van az elutasítás, annak az oszlopait töltjük meg adatokkal
        // Ha már van értéke, akkor utóigazolásnál történt az elutasítás, akkor meg azok oszlopaiba kell írni
        $db_tabla_kieg = '';
        if( $sor['elo_igazolva_dt'] == '0000-00-00 00:00:00' ) {
            $db_tabla_kieg .= 'elo_';
        }
        
        // Elutasítással kapcsolatos adatok beállítása, illetve a kapcsolt számlaszám ürítése
        $sql = $adatbazis_kapcsolat->u('projektszamla', array($db_tabla_kieg.'igazolva', 'sz_szam', 'pr_szam', 'let_fel', 'elutleir',     $db_tabla_kieg.'igazolva_dt',    $db_tabla_kieg.'igazolva_usr',           'sztornozva_dt', 'sztornozva_usr'                       ),
                                                        array(0,                         '',        '',        '',        $_POST['memo'], 'NOW()',                         $_SESSION[get_db_tabla().'teljes_nev'],  'NOW()',         $_SESSION[get_db_tabla().'teljes_nev'] ),
                                        "`recid`='{$_POST['id']}'");
        if( $sql === false ) { $db_error = true; }
        
        // Tételeknél a kapcsolt szerződés tételeknél a teljesítést és nettó értékeket töröljük
        $tetelek = $adatbazis_kapcsolat->s('projektszerzodes_tetel_recid', 'projektszamla_tetel', "`projektszamla_recid`='{$_POST['id']}'");
        if( $tetelek === false ) { $db_error = true; }
        foreach ( $tetelek as $tetel ) {
            $sql = $adatbazis_kapcsolat->u('projektszerzodes_tetel',    array('ertek', 'dev_ertek', 'teljesites' ),
                                                                        array('0',     '0.00',      '0000-00-00' ),
                                                                "`recid`='{$tetel['projektszerzodes_tetel_recid']}'");
            if( $sql === false ) { $db_error = true; }
        }
        //... illetve a kapcsolást szintén töröljük a számla tételeknél
        $sql = $adatbazis_kapcsolat->u('projektszamla_tetel',   array('projektszerzodes_tetel_recid'),
                                                                array('0'                           ),
                                        "`projektszamla_recid`='{$_POST['id']}'");
        if( $sql === false ) { $db_error = true; }
        
        if( projektkezeles_szamlazott_osszeg ( $sor['pr_szam'] ) === false ) {
            $db_error = true;
        }
        
        // Megjegyzésbe is kerüljön be az indoklás
        if( addMegj('projektszamla', $_POST['id'], 'Teljesítés igazolás elutasítva.<br>Indoklás: '.nl2br2($_POST['memo'])) === false ) {
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
if( isset($_POST['projektSzamlaVisszaUi']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('let_fel', 'projektszamla', "`recid`='{$_POST['recid']}'");
    
    // Ha nincs joga hozzá, akkor hibaüzenet
    if(
        !(
            ( _jog(JOG_PRSZAMLAK_IGAZOL) && $sor['let_fel'] == $_SESSION[get_db_tabla().'login_recid'] ) ||
            _jog(JOG_PRSZAMLAK_UTOIGAZOL)
        )
       ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga a számlát visszaküldeni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }
    
    // Visszaküldés SQL-je
    $sql = $adatbazis_kapcsolat->u('projektszamla', array('feldolgozva_dt',      'feldolgozva_usr', 'elo_igazolva', 'elo_igazolva_dt', 'elo_igazolva_usr'   ),
                                                    array('0000-00-00 00:00:00', '',                '0',            '',                ''                   ),
                                                    "`recid`='{$_POST['recid']}'");
    if( $sql === false ) { $db_error = true; }
    
    // Könyvelés tételeket ekkor töröljük
    $sql = $adatbazis_kapcsolat -> d('projektszamla_konyveles', "`projektszamla_recid`='{$_POST['recid']}'");
    if( $sql === false ) { $db_error = true; }
    
    // Megjegyzésben logoljuk
    if( addMegj('projektszamla', $_POST['recid'], 'Igazoló visszaadta számlát ügyintézőnek.<br>Indoklás: '.nl2br2($_POST['memo'])) === false ) {
        $db_error = true;
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
    } else {
        mysqli_rollback($db_kapcs);
    }

    // Levélküldés
    level_felkeszit_es_kuld('projektszamla', $_POST['recid'], ERT_ASSZISZT_VISSZAADAS, 'projektszamlaVisszaUi', 'projektszamla_asszisztens');

    echo json_encode(array(
        'siker' => 1,
        ));
    
    $weblap -> leallit();
    exit;
}

// Számla vissza ügyintézőnek
if( isset($_POST['projektSzamlaVisszaIgazolonak']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $sor = $adatbazis_kapcsolat->s2('let_fel', 'projektszamla', "`recid`='{$_POST['recid']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_PRSZAMLAK_UTOIGAZOL) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga a számlát visszaküldeni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }
    
    // Visszaküldés SQL-je
    $sql = $adatbazis_kapcsolat->u('projektszamla', array('elo_igazolva', 'elo_igazolva_dt', 'elo_igazolva_usr' ),
                                                    array(0,              '',                ''                 ),
                                                    "`recid`='{$_POST['recid']}'");
    if( $sql === false ) { $db_error = true; }
    
    // Megjegyzésben logoljuk
    if( addMegj('projektszamla', $_POST['recid'], 'Utóigazoló visszaadta számlát az Igazolónak.<br>Indoklás: '.nl2br2($_POST['memo'])) === false ) {
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

    // Levélküldés
    level_felkeszit_es_kuld('projektszamla', $_POST['recid'], ERT_IGAZOLONAK_VISSZAADAS, 'projektszamlaVisszaIgnak', 'projektszamla_felelose');
    
    $weblap -> leallit();
    exit;
}

// Számla sztornózása
if( isset($_POST['projektSzamlaSztornoz']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_PRSZAMLAK_KEZEL) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga számlát sztornózni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }
        
    // Sztornózással kapcsolatos adatok beállítása, illetve a kapcsolt számlaszám ürítése
    $sql = $adatbazis_kapcsolat->u('projektszamla', array('sz_szam', 'pr_szam', 'let_fel',  'sztornozva_leir',   'sztornozva_dt', 'sztornozva_usr'                       ),
                                                    array('',        '',        '',         $_POST['memo'],      'NOW()',         $_SESSION[get_db_tabla().'teljes_nev'] ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }
    
    // Tételeknél a kapcsolt szerződés tételenél a teljesítést és nettó értékeket töröljük
    $tetelek = $adatbazis_kapcsolat->s('projektszerzodes_tetel_recid', 'projektszamla_tetel', "`projektszamla_recid`='{$_POST['id']}'");
    if( $tetelek === false ) { $db_error = true; }
    foreach ( $tetelek as $tetel ) {
        $sql = $adatbazis_kapcsolat->u('projektszerzodes_tetel',    array('ertek', 'dev_ertek', 'teljesites' ),
                                                                    array('0',     '0.00',      '0000-00-00' ),
                                                            "`recid`='{$tetel['projektszerzodes_tetel_recid']}'");
        if( $sql === false ) { $db_error = true; }
    }
    //... illetve a kapcsolást szintén töröljük a számla tételeknél
    $sql = $adatbazis_kapcsolat->u('projektszamla_tetel',   array('projektszerzodes_tetel_recid'   ),
                                                            array('0'                                   ),
                                    "`projektszamla_recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    if( projektkezeles_szamlazott_osszeg ( $szamla['pr_szam'] ) === false ) {
        $db_error = true;
    }
    
    // Megjegyzésbe is kerüljön be az indoklás
    if( addMegj('projektszamla', $_POST['id'], 'Számla sztornózva.<br>Indoklás: '.nl2br2($_POST['memo'])) === false ) {
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

// Lekérdezzük, hogy van-e a projekt számla tételei között olyan, amihez van kapcsolva szerződés tétel
if( isset($_POST['van_kapcsolt_szerzodes']) ) {

    // Új feltöltéskor ezt nem kell ellenőrizni
    if( !$_POST['uj'] || $_POST['uj'] == 'false' ) {

        $szerzodes_tetelek = $adatbazis_kapcsolat->s('recid', 'projektszamla_tetel', "`projektszamla_recid`='{$_POST['recid']}' AND `projektszerzodes_tetel_recid`!=0");
        if ( $szerzodes_tetelek ){

            echo json_encode( array(
                                'hiba'  => 'A számlának van olyan tétele, amihez van szerződés tétel kapcsolva, így a szerződés szám nem törölhető!',
                                'title' => 'Hiba',
                            ) );
            $weblap -> leallit();
            exit;
        }
        
    }
    
    echo json_encode( array(
                        'nincs'  => 'nincs csatolt szerződés',
                    ) );
    $weblap -> leallit();
    exit;
}

// Projektszerződés táblázat betöltése
if( isset($_POST['get_projekt_szerzodes_tabla']) ) {

    // Ha nincs új feltöltés jogunk, akkor kilépünk
    if( !get_projekt_szamla_kezel_jog($_POST['recid']) ) {
        echo json_encode( array(
                            'hiba'  => 'Önnek nincs joga projekt számlákat kezelni!',
                            'title' => 'Hiba',
                        ) );
        $weblap -> leallit();
        exit;
    }
    
    $html = '';

    // Nincs rendezés, fixen a projekt szerződés szerint rendezünk
    $order = '`'.get_db_tabla().'projektszerzodes`.`sz_szam`';

    $pr_sz_ek = $adatbazis_kapcsolat->s('
                                        `'.get_db_tabla().'projektszerzodes`.`recid`,
                                        `'.get_db_tabla().'projektszerzodes`.`sz_szam`,
                                        `'.get_db_tabla().'projektszerzodes`.`pr_szam`,
                                        `'.get_db_tabla().'projektszerzodes`.`targy`,
                                        `'.get_db_tabla().'projektszerzodes`.`let_fel`,
                                        `'.get_db_tabla().'projektszerzodes`.`kolt_vis`,
                                        `'.get_db_tabla().'projektszerzodes`.`kcs_21`,
                                        `'.get_db_tabla().'projektszerzodes`.`dev_net`,
                                        `'.get_db_tabla().'projektszerzodes`.`szerz_ossz`
                                            ',
                                        'projektszerzodes',
                                        "`".get_db_tabla()."projektszerzodes`.`telephely_id`='{$_POST['telephely_id']}' AND `".get_db_tabla()."projektszerzodes`.`szallito_id`='{$_POST['szallito_id']}' AND `pr_szam`!=''", $order, '', '', '', $join);
 //echo $adatbazis_kapcsolat->gp() . '<br>';
    $i = 1;
    foreach ( $pr_sz_ek as $pr ) {

        $let_fel_neve  = getErtek('teljes_nev', 'login', 'recid', $pr['let_fel']);
        $kolt_vis_neve = erteklista(ERTEKLISTA_KOLTSEGVISELO, $pr['kolt_vis']);
        
        $projekt = $adatbazis_kapcsolat->s2('recid nev', 'projektkezeles', "`ikt_szam`='{$pr['pr_szam']}'");
        
        $dev_nev = '';
        if( $pr['dev_net'] != '0.00' ) {
            $dev_nev = number_format($pr['dev_net'], 2, ',', ' ');
        }
        
        $html .= '
            <tr class="kijSor" data-i="'.$i.'" data-recid="'.$pr['recid'].'" data-sz_szam="'.$pr['sz_szam'].'" data-pr_szam="'.$pr['pr_szam'].'" data-let_fel="'.$pr['let_fel'].'" data-kolt_vis="'.$pr['kolt_vis'].'">
                <td style="width: 11rem">
                    '.$pr['sz_szam'].'
                    <div class="dn let_fel_txt">'.$let_fel_neve.'</div>
                    <div class="dn kolt_vis_txt">'.$kolt_vis_neve.'</div>
                    <div class="dn targy_txt"><a href="projektszerzodes.php?id=x&amp;modosit&amp;mezo=sz_szam&amp;op=equal&amp;felt='.$pr['sz_szam'].'" target="_blank">'.resz_string($pr['targy'], 60).'</a></div>
                    <div class="dn pr_nev_txt"><a href="projektkezeles.php?id=x&amp;modosit&amp;mezo=ikt_szam&amp;op=equal&amp;felt='.$pr['pr_szam'].'" target="_blank">'.resz_string($projekt['nev'], 60).'</a></div>
                </td>
                <td class="l" style="width: 40rem">'.nl2br2($pr['targy']).'</td>
                <td class="r nowrap">'.number_format($pr['szerz_ossz'], 0, '', ' ').'</td>
                <td class="r nowrap">'.$dev_nev.'</td>
            </tr>
            ';
        $i++;
    }

    if( $html ) {

        // Táblázat rendezhető: szálloda + becsült költség, becsült költség + szálloda, típus + becsült költség. Ezek legyenek egy lenyílóból kiválaszthatóak.
        // Nincs rendezés..., de amúgy így kellene a HTML-t bővíteni:
//            Rendezés:
//            <select onchange="get_projekt_tabla(this.value)">
//                <option>szálloda + becsült költség</option>
//                <option value="bkolt"'.$sz_osszeg_selected.'>becsült költség + szálloda</option>
//            </select>
        $html = '
            <div style="max-height: 40rem; overflow-y: auto; overflow-x: none;">
                '.getErtek('nev', 'szallito', 'id', $_POST['szallito_id']).' szerződései:
                <table class="csikos v2 kijCont csak_egy_kijelolheto" id="prszerzodesek" style="cursor: default;">
                <thead>
                <tr>
                    <th>
                        Szerződés szám
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prszerzodesek tbody\'), this.value, 1)">
                    </th>
                    <th>
                        Tárgy
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prszerzodesek tbody\'), this.value, 2)">
                    </th>
                    <th>
                        Nettó érték
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prszerzodesek tbody\'), this.value, 4)">
                    </th>
                    <th>
                        Deviza nettó
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prszerzodesek tbody\'), this.value, 5)">
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
        $html = '<script>popupRejt(); setTimeout(function(){ popupZar(); alert2(\'Szálloda: '.getSzallodaNev($_POST['telephely_id']).'<br>Szállító: '.getErtek('nev', 'szallito', 'id', $_POST['szallito_id']).'<br><br>Nincs ilyen szerződés vagy a szerződésnek nincs projektszáma!\', \'\', \'Hiba\'); }, 10);</script>';
    }

    echo json_encode(
            array( 'html' => nl2($html) )
            );

    $weblap->leallit();
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
        $szamla = $adatbazis_kapcsolat->s2('feldolgozva_dt tetel_elter', 'projektszamla', "`recid`='{$recid}'");
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
    $targy_adat     = 1;
    $netto_ert_adat = 2;
    $brutto_ert_adat= 3;
    $afa_adat       = 4;
    $jogcim_adat    = 5;
    $valtozott_adat = 6;
    $szerzodes_adat = 7;
    $pipalo_adat    = 8;

    $tetelek_osszege = 0;
    $i = 0;
    foreach ( $_POST['adatok'][$netto_ert_adat][1] as $ertek ) {
        $_POST['adatok'][$netto_ert_adat][1][$i] = str_replace(array(' ', ','), array('', '.'), $ertek);
        // Egy tétellel akkor kalkulálunk, ha nincs eltérés a tételek számában, vagy ha van, akkor a pipálója igaz
        if( !$szamla['tetel_elter'] || $_POST['adatok'][$pipalo_adat][1][$i] ) {
            $tetelek_osszege += $_POST['adatok'][$netto_ert_adat][1][$i];
        }
        $i++;
    }
    $tetelek_br_osszege = 0;
    $i = 0;
    foreach ( $_POST['adatok'][$brutto_ert_adat][1] as $ertek ) {
        $_POST['adatok'][$brutto_ert_adat][1][$i] = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$brutto_ert_adat][1][$i]);
        // Egy tétellel akkor kalkulálunk, ha nincs eltérés a tételek számában, vagy ha van, akkor a pipálója igaz
        if( !$szamla['tetel_elter'] || $_POST['adatok'][$pipalo_adat][1][$i] ) {
            $tetelek_br_osszege += $_POST['adatok'][$brutto_ert_adat][1][$i];
        }
        $i++;
    }

    // Deviza esetén tört számokkal is számolunk
    $penznem   = get_penznem();
    $devizas   = false;
    $tizedesek = 0;
    $db_mezo   = 'netto_ert';
    $db_mezo_br= 'brutto_ert';
    $kerekit   = KEREKITESI_HATAR_HUF;
    if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
        $devizas   = true;
        $tizedesek = 2;
        $penznem   = erteklista(ERTEKLISTA_PENZNEM, $_POST['kcs_21']);
        $db_mezo   = 'dev_net';
        $db_mezo_br= 'dev_br';
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
                
    // Ha a számla nettó értéke nem egyenlő a tételek összegével, akkor hibaüzenet
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
        
        // Ha nem ismert a recid, a $fej_id legyen "uj"
        $fej_id = $recid;
        if( !$recid ) {
            $fej_id = 'uj';
        }

        $_SESSION['pr_szla_tetel_recid'][$fej_id] = $_POST['adatok'][$recid_adat][1];
        $_SESSION['pr_szla_tetel_targy'][$fej_id] = $_POST['adatok'][$targy_adat][1];

        if( $devizas ) {
            $_SESSION['pr_szla_tetel_dev_net'][$fej_id] = str_replace(',', '.', $_POST['adatok'][$netto_ert_adat ][1]);
            $_SESSION['pr_szla_tetel_dev_br' ][$fej_id] = str_replace(',', '.', $_POST['adatok'][$brutto_ert_adat][1]);
        } else {
            $_SESSION['pr_szla_tetel_netto_ert' ][$fej_id] = str_replace(',', '.', $_POST['adatok'][$netto_ert_adat ][1]);
            $_SESSION['pr_szla_tetel_brutto_ert'][$fej_id] = str_replace(',', '.', $_POST['adatok'][$brutto_ert_adat][1]);
        }
        $_SESSION['pr_szla_tetel_afakod'    ][$fej_id] = $_POST['adatok'][$afa_adat      ][1];
        $_SESSION['pr_szla_tetel_jogcim'    ][$fej_id] = $_POST['adatok'][$jogcim_adat   ][1];
        $_SESSION['pr_szla_tetel_valtozott' ][$fej_id] = $_POST['adatok'][$valtozott_adat][1];
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
            $sql = $adatbazis_kapcsolat->d('projektszamla_konyveles', "`projektszamla_recid`='{$recid}'");
            if( $sql === false ) { $db_error = true; }
        }
        
        $i = 0;
        foreach ( $_POST['adatok'][$jogcim_adat][1] as $jogcim ) {
            
            // Egy tétellel akkor kalkulálunk, ha nincs eltérés a tételek számában, vagy ha van, akkor a pipálója igaz
            if( !$szamla['tetel_elter'] || $_POST['adatok'][$pipalo_adat][1][$i] ) {

                $netto   = $_POST['adatok'][$netto_ert_adat][1][$i];
                $brutto  = $_POST['adatok'][$brutto_ert_adat][1][$i];
                $targy   = $_POST['adatok'][$targy_adat][1][$i];
                $afakod  = $_POST['adatok'][$afa_adat][1][$i];
                $jogcim  = $_POST['adatok'][$jogcim_adat][1][$i];
                $ktgnem  = $_POST['adatok'][$ktgnem_adat][1][$i];
                $ktghely = $_POST['adatok'][$ktghely_adat][1][$i];
                $valtozott = 0;
                if( $_POST['adatok'][$valtozott_adat][1][$i] ) {
                    $valtozott = 1;
                }

                // Jogcím/Áfakód esetén nem csak egy recid-t tárolunk le, hanem a tábla oszlopait, ezért lekérjük a megfelelő sort
                $jogcim = $adatbazis_kapcsolat->s2('recid jogcim uzemresz megn szla_szam ktghely ktghely_id', 'jogcim', "`recid`='{$jogcim}'");
                $afakod = $adatbazis_kapcsolat->s2('recid nev kod afa', 'afakod', "`recid`='{$afakod}'");
                // Adatbázisból olvasunk ki adatot, ezért ezt kódoljuk vissza
                foreach ( $jogcim as $kulcs => $ertek ) {
                    $jogcim[$kulcs] = htmlspecialchars_decode($jogcim[$kulcs]);
                }
                foreach ( $afakod as $kulcs => $ertek ) {
                    $afakod[$kulcs] = htmlspecialchars_decode($afakod[$kulcs]);
                }

                // Ha ismerjük a tétel recid-ját, akkor update
                if( $_POST['adatok'][$recid_adat][1][$i] ) {

                    // Ha azért kerültünk ide, mert a számla tételei eltérnek a könyvelés tételeitől, akkor a könyvelés táblába írunk
                    if( $szamla['tetel_elter'] ) {
                        $mezok   = array('projektszamla_recid',  'ktgnem_id',           'ktghely_id',           'ktghely',          'jogcim_recid',   'jogcim',          'uzemresz',          'megn',          'afakod_recid',   'afa',          'afa_kod',      'afa_nev',      'netto_ert', 'brutto_ert', 'dev_net',  'dev_br', 'loginnev',                        'insert_dt',   'insert_usr',                             'update_usr'                          );
                        $ertekek = array($recid,                 $jogcim['szla_szam'],  $jogcim['ktghely_id'],  $jogcim['ktghely'], $jogcim['recid'], $jogcim['jogcim'], $jogcim['uzemresz'], $jogcim['megn'], $afakod['recid'], $afakod['afa'], $afakod['kod'], $afakod['nev'], $netto,      $brutto,      $dev_net,   $dev_br,  $_SESSION[get_db_tabla().'user'],  'NOW()',       $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']);
                        // Ha a recid-ben van egy kifizetés, akkor egy olyan sor volt jóváhagyva, amit egy kifizetésben kaptunk
                        // Ekkor ennek a flexmezőjét még kérjük le
                        if( strpos($_POST['adatok'][$recid_adat][1][$i], '_kifizetes') ) {
                            $kif_recid = str_replace('_kifizetes', '', $_POST['adatok'][$recid_adat][1][$i]);
                            $kif_sor = $adatbazis_kapcsolat->s2('flex', 'projektszamla_tetel_kifizetes', "`recid`='{$kif_recid}'");
                            array_push($mezok,   'flex'          );
                            array_push($ertekek, $kif_sor['flex']);
                        }
                        $sql = $adatbazis_kapcsolat->i('projektszamla_konyveles', $mezok, $ertekek);
                        if( $sql === false ) { $db_error = true; }

                    // Sima tételek kezelés popup esete
                    } else {

                        $mezok   = array('targy', $db_mezo, $db_mezo_br, 'ktgnem_id',           'jogcim_recid',   'jogcim',          'uzemresz',          'megn',          'afakod_recid',   'afa',          'afa_kod',      'afa_nev',      'valtozott', 'update_usr'                             );
                        $ertekek = array($targy,  $netto,   $brutto,     $jogcim['szla_szam'],  $jogcim['recid'], $jogcim['jogcim'], $jogcim['uzemresz'], $jogcim['megn'], $afakod['recid'], $afakod['afa'], $afakod['kod'], $afakod['nev'], $valtozott,  $_SESSION[get_db_tabla().'teljes_nev']   );
                        // Devizás számla esetén a Ft-os nettó/bruttó értékeket is számoljuk ki és írjuk be
                        if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
                            array_push($mezok,   'netto_ert',       'brutto_ert'        );
                            array_push($ertekek, $netto*$arfolyam,  $brutto*$arfolyam   );
                        }
                        $sql = $adatbazis_kapcsolat->u('projektszamla_tetel', $mezok, $ertekek, "`recid`='{$_POST['adatok'][$recid_adat][1][$i]}'");
                        if( $sql === false ) { $db_error = true; }
                    }

                // Egyébként insert
                } else {

                    $mezok   = array('projektszamla_recid', 'targy', $db_mezo, $db_mezo_br, 'ktgnem_id',          'jogcim_recid',   'jogcim',          'uzemresz',          'megn',          'afakod_recid',   'afa',          'afa_kod',      'afa_nev',        'valtozott', 'loginnev',                          'insert_dt',    'insert_usr',                             'update_usr'                              );
                    $ertekek = array($recid,                $targy,  $netto,   $brutto,     $jogcim['szla_szam'], $jogcim['recid'], $jogcim['jogcim'], $jogcim['uzemresz'], $jogcim['megn'], $afakod['recid'], $afakod['afa'], $afakod['kod'], $afakod['nev'],   '1',          $_SESSION[get_db_tabla().'user'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']    );
                    // Devizás számla esetén a Ft-os nettó/bruttó értékeket is számoljuk ki és írjuk be
                    if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
                        array_push($mezok,   'netto_ert',       'brutto_ert'        );
                        array_push($ertekek, $netto*$arfolyam,  $brutto*$arfolyam   );
                    }

                    // Ha azért kerültünk ide, mert a számla tételei eltérnek a könyvelés tételeitől, akkor beszúrjuk a könyvelés tételei közé
                    if( $szamla['tetel_elter'] ) {
                        $uj_id = $adatbazis_kapcsolat->i('projektszamla_konyveles', $mezok, $ertekek);
                        if( $sql === false ) { $db_error = true; }
                    } else {
                        $uj_id = $adatbazis_kapcsolat->i('projektszamla_tetel', $mezok, $ertekek);
                        if( $sql === false ) { $db_error = true; }
                        $_SESSION['pr_szla_tetel_recid'][$i] = $uj_id;
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
            $sql = $adatbazis_kapcsolat->d('projektszamla_tetel', "`recid` IN ('". implode('\', \'', $torlendo_idk_kiszurve)."')");
            if( $sql === false ) { $db_error = true; }
        }
 
    }
    
    // Tételek mentésekor a számláról vegyük le a tételek eltérése pipát, mert ha ilyen állapotban is lett volna, ezzel a lépéssel már biztosan nem az
    $sql = $adatbazis_kapcsolat->u('projektszamla', array('tetel_elter'), array('0'), "`recid`='{$recid}'");
    if( $sql === false ) { $db_error = true; }
    
    // Továbbá a kifizetéskor kapott tételeket is töröljük, ha volna
    $sql = $adatbazis_kapcsolat->d('projektszamla_tetel_kifizetes', "`projektszamla_recid`='{$recid}'");
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

// Számla tétel sorok mentése
if( isset($_POST['tetel_sorok_ment_modosit']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    if( $_POST['recid'] == 'false' ) {
        $_POST['recid'] = '';
    }
    $recid                  = $_POST['recid'];
    $szamla_netto_erteke    = str_replace(array(' ', ','), array('', '.'), $_POST['szamla_netto_erteke']);
    $_POST['adatok']        = json_decode( $_POST['adatok'] );
    
    // Kapott tömb indexeit cseréljük beszédesebb változókra
    $recid_adat     = 0;
    $targy_adat     = 1;
    $netto_ert_adat = 2;
    $pr_jogcim_adat = 3;
    $pr_tetel_adat  = 4;
    
    $tetelek_osszege = '';
    foreach ( $_POST['adatok'][$netto_ert_adat][1] as $ertek ) {
        $ertek = str_replace(array(' ', ','), array('', '.'), $ertek);
        $tetelek_osszege += $ertek;
    }
    
    // Deviza esetén tört számokkal is számolunk
    $penznem   = get_penznem();
    $devizas   = 0;
    $tizedesek = 0;
    $db_mezo   = 'netto_ert';
    $session_mezo = 'pr_szla_tetel_netto_ert';
    if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
        $devizas   = 1;
        $tizedesek = 2;
        $penznem   = erteklista(ERTEKLISTA_PENZNEM, $_POST['kcs_21']);
        $db_mezo   = 'dev_net';
        $session_mezo = 'pr_szla_tetel_dev_net';
    }
    
    // Ha nincs egy sornyi tételünk sem, akkor hibaüzenet
    if( !$_POST['adatok'][$targy_adat][1] ) {
        echo json_encode( array( 
                        'hiba'  => 'Legalább egy számla tételnek szerepelnie kell a táblázatban, üresen nem menthető!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
                
    // Ha a számla nettó értéke nem egyenlő a tételek összegével, akkor hibaüzenet
    } elseif( number_format($tetelek_osszege, $tizedesek, ',', ' ') != number_format($szamla_netto_erteke, $tizedesek, ',', ' ') ) {
        echo json_encode( array( 
                        'hiba'  => 'A számla tételeinek összege ('. number_format($tetelek_osszege, $tizedesek, ',', ' ').' '.$penznem.') nem egyezik meg a számla nettó értékével ('.number_format($szamla_netto_erteke, $tizedesek, ',', ' ').' '.$penznem.')!<br>Így nem menthető!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
                
    // Tételek kimentése Session-be
    } else {
        
        $fej_id = $recid;
        if( !$recid ) {
            $fej_id = 'uj';
        }
        $_SESSION['pr_szla_tetel_recid'     ][$fej_id] = $_POST['adatok'][$recid_adat   ][1];
        $_SESSION['pr_szla_tetel_targy'     ][$fej_id] = $_POST['adatok'][$targy_adat   ][1];
        $_SESSION['pr_szla_tetel_pr_tetel'  ][$fej_id] = $_POST['adatok'][$pr_tetel_adat][1];
        if( $devizas ) {
            $_SESSION['pr_szla_tetel_dev_net'][$fej_id] = str_replace(',', '.', $_POST['adatok'][$netto_ert_adat][1]);
        } else {
            $_SESSION['pr_szla_tetel_netto_ert'][$fej_id] = str_replace(',', '.', $_POST['adatok'][$netto_ert_adat][1]);
        }
        $_SESSION['pr_szla_tetel_jogcim'][$fej_id] = $_POST['adatok'][$jogcim_adat][1];
    }

    // Adott projektszámla rekord lekérdezése
    $prszamla = $adatbazis_kapcsolat->s2('telj_dt', 'projektszamla', "`recid`='{$recid}'");
    if( $prszamla === false ) { $db_error = true; }
    
    // Loglás
    $muvelet    = $recid.' recid-jú számla tételeit módosította. ';
    $valtozas   = 'Előző állapot (a már kapcsolt sorokat nem listázva, hisz az biztosan nem változott): '."\n";
    $szamla_tetelek = $adatbazis_kapcsolat->s('*', 'projektszamla_tetel', "`projektszamla_recid`='{$recid}' AND `projektszerzodes_tetel_recid`='0'");
    if( $szamla_tetelek === false ) { $db_error = true; }
    $valtozas .= logolas_kapcsolodo_tablak_sorai($szamla_tetelek);
    $valtozas .= 'Új állapot (a már kapcsolt sorokat nem listázva, hisz az biztosan nem változott): '."\n";
    
    // Ha ismert a szerződés ID-ja, akkor a sorokat is updatel-jük (töröljük, majd újra beszúrjuk)
    // Azért kell az is_numeric, mert JS-ből volt, hogy itt egy 'false' értéket kaptunk, és ilyenkor sem kéne beírni még adatbázisba (új feltöltés esete).
    if( $recid && is_numeric($recid) ) {

        // Nem törölhetünk minden tételt, hisz lehet van számla párja, és ekkor a recid-ja nem változhat, nem törölhető majd hozható létre újból.
        $adatbazis_kapcsolat->d('projektszamla_tetel', "`projektszamla_recid`='{$recid}' AND `projektszerzodes_tetel_recid`='0'");
        
        $i = 0;
        $fej_id = $recid;
        foreach ( $_SESSION['pr_szla_tetel_targy'][$fej_id] as $targy ) {

            // Ha egy létező sor, akkor az előbb nem töröltük ki (azaz hozzá van csatolva szerződéshez), nem kell létrehozni
            $letezik = $adatbazis_kapcsolat->s('recid', 'projektszamla_tetel', "`recid`='{$_SESSION['pr_szla_tetel_recid'][$fej_id][$i]}'");
            if( $letezik === false ) { $db_error = true; }
            if( !$letezik ) {
                $netto_ert = str_replace(' ', '',  $_SESSION[$session_mezo][$fej_id][$i]);
                $uj_szamla_tetel_recid = $adatbazis_kapcsolat->i('projektszamla_tetel',
                                                array('projektszamla_recid', 'targy', $db_mezo,    'projektszerzodes_tetel_recid',                   'loginnev',                          'insert_dt',    'insert_usr',                             'update_usr'                              ),
                                                array($recid,                $targy,  $netto_ert,  $_SESSION['pr_szla_tetel_pr_tetel'][$fej_id][$i], $_SESSION[get_db_tabla().'user'],    'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']    )
                                            );

                if( $uj_szamla_tetel_recid === false ) { $db_error = true; }
                $_SESSION['pr_szla_tetel_recid'][$fej_id][$i] = $uj_szamla_tetel_recid;
                
                // Logolás
                $valtozas .= '[mezo]recid[/mezo] [ertek_uj]'                        .$uj_szamla_tetel_recid                             .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]projektszamla_recid[/mezo] [ertek_uj]'          .$recid                                             .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]targy[/mezo] [ertek_uj]'                        .$targy                                             .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]'.$db_mezo.'[/mezo] [ertek_uj]'                 .$netto_ert                                         .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]projektszerzodes_tetel_recid[/mezo] [ertek_uj]' .$_SESSION['pr_szla_tetel_pr_tetel'][$fej_id][$i]   .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]loginnev[/mezo] [ertek_uj]'                     .$_SESSION[get_db_tabla().'user']                   .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]insert_dt[/mezo] [ertek_uj]'                    .date('Y-m-d H:i:s')                                .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]insert_usr[/mezo] [ertek_uj]'                   .$_SESSION[get_db_tabla().'teljes_nev']             .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]update_usr[/mezo] [ertek_uj]'                   .$_SESSION[get_db_tabla().'teljes_nev']             .'[/ertek_uj]'."\n";
                
            }
            
            $i++;
        }
        
    }

    // Tételek mentésekor a számláról vegyük le a tételek eltérése pipát, mert ha ilyen állapotban is lett volna, ezzel a lépéssel már biztosan nem az
    $sql = $adatbazis_kapcsolat->u('projektszamla', array('tetel_elter'), array('0'), "`recid`='{$recid}'");
    if( $sql === false ) { $db_error = true; }
    
    // Logolás
    $sql = $adatbazis_kapcsolat -> i(
        'log',
        array( 'felh_id',                               'nev',       'valtozas',    'ip'                   ),
        array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,    $valtozas,     $_SERVER['REMOTE_ADDR'])
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
            'title' => 'Hiba'
        ));
    }
    
    $weblap->leallit();
    exit();
}

// Projekt számla kapcsoló popup mentése
if( isset($_POST['projektszamla_kapcsolas']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Kapott tömb indexeit cseréljük beszédesebb változókra
    $recid_adat         = 0;
    $targy_adat         = 1;
    $varh_erk_adat      = 2;
    $varhato_ert_adat   = 3;
    $teljesites_adat    = 4;
    $netto_ert_adat     = 5;
    
    $recid           = $_POST['recid'];
    $_POST['adatok'] = json_decode( $_POST['adatok'] );
    
    $varh_ert_mezo  = 'varh_ert';
    $ertek_mezo     = 'ertek';
    $netto_mezo     = 'netto_ert';
    $szerz_mezo     = 'szerz_ossz';
    $tizedesek      = 0;
    $penznem        = get_penznem();
    if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
        $varh_ert_mezo  = 'dev_varh_ert';
        $ertek_mezo     = 'dev_ertek';
        $netto_mezo     = 'dev_net';
        $szerz_mezo     = 'dev_net';
        $tizedesek      = 2;
        $penznem        = erteklista(ERTEKLISTA_PENZNEM, $_POST['kcs_21']);
    }
    
    // Tárgy-ban levő idézőjelek visszakonvertálása
    $i = 0;
    foreach ( $_POST['adatok'][$targy_adat][1] as $ertek ) {
        $_POST['adatok'][$targy_adat][1][$i] = str_replace('|IDEZOJEL|', '"', $ertek);
        $i++;
    }
    
    // Ha nincs egy sornyi tételünk sem, akkor hibaüzenet
    if( !$_POST['adatok'][$targy_adat][1] ) {
        echo json_encode( array( 
                        'hiba'  => 'Legalább egy szerződés tételnek szerepelnie kell a táblázatban, üresen nem menthető!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
                
    }
    
    $szamla = $adatbazis_kapcsolat->s2('sz_szam telj_dt arfolyam', 'projektszamla', "`recid`='{$recid}'");
    if( $szamla === false ) { $db_error = true; }
    $szerzodes = $adatbazis_kapcsolat->s2('recid '.$szerz_mezo, 'projektszerzodes', "`sz_szam`='{$szamla['sz_szam']}'");
    if( $szerzodes === false ) { $db_error = true; }
    // Az a számlatétel, amit épp kapcsolunk
    $szamla_tetel = $adatbazis_kapcsolat->s2($netto_mezo, 'projektszamla_tetel', "`recid`='{$_POST['szamla_tetel_recid']}'");
    if( $szamla_tetel === false ) { $db_error = true; }
    
    $kifizet_var_ossz = '';
    $i = 0;
    foreach ( $_POST['adatok'][$varhato_ert_adat][1] as $ertek ) {
        // A kifizetésre vár értéket csak akkor adjuk hozzá, ha a nettó érték mező üres, azaz nincs benne tényadat
        if( !$_POST['adatok'][$netto_ert_adat][1][$i] ) {
            $ertek = str_replace(array(' ', ','), array('', '.'), $ertek);
            $kifizet_var_ossz += $ertek;
        }
        $i++;
    }

    $kifizetett_ossz = '';
    foreach ( $_POST['adatok'][$netto_ert_adat][1] as $ertek ) {
        $ertek = str_replace(array(' ', ','), array('', '.'), $ertek);
        $kifizetett_ossz += $ertek;
    }
    $kif_ossz = $kifizet_var_ossz + $kifizetett_ossz;
    
    // Egyetlen új nettó értékünk van?
    $i = 0;
    $ertek_i            = 0;
    $teljesites_i       = 0;
    $volt_ertek         = 0;
    $volt_teljesites    = 0;
    $beirt_netto_ertek  = 0;
    $beirt_teljesites   = 0;

    foreach ( $_POST['adatok'][$netto_ert_adat][1] as $ertek ) {
        
        // Számokból a szóközt vegyük ki, vesszőt pontra cseréljük
        $ertek = str_replace(array(' ', ','), array('', '.'), $ertek);
        $_POST['adatok'][$varhato_ert_adat][1][$i]  = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$varhato_ert_adat][1][$i]);
        $_POST['adatok'][$netto_ert_adat][1][$i]    = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$netto_ert_adat][1][$i]);
        
        // Recid alapján nézzük meg, hogy van-e már kapcsolt számlája. 
        $van_szamlaja = $adatbazis_kapcsolat->s2('recid', 'projektszamla_tetel', "`projektszerzodes_tetel_recid`='{$_POST['adatok'][0][1][$i]}'");
        if( $van_szamlaja === false ) { $db_error = true; }

        // Ha nincs számlája (ennek lehet az is az oka, hogy egy fiktív sor, azaz nem ismert a recid),
        // akkor ellenőrizzük, hogy van-e beírva várható érkezés. Ha van, számoljunk, mert csak egy ilyen mező lehet
        // Ha van számlája, akkor azt nem ellenőrizzük, mert akkor az egy szürke sor, azzal nem kell foglalkozni
        if( ( !$van_szamlaja || !$_POST['adatok'][$recid_adat][1][$i] ) && ($_POST['adatok'][$teljesites_adat][1][$i]!='' && $_POST['adatok'][$teljesites_adat][1][$i]!='0000.00.00') ) {
            $volt_ertek++;
            // Csak egy értéket fogadunk el, ezt jegyezzük is meg
            $beirt_netto_ertek = $ertek;
            $ertek_i           = $i;
        }

        // A dátumot is ellenőrizni kell, hogy csak egy helyen lett-e beírva
        if( (!$van_szamlaja || !$_POST['adatok'][$recid_adat][1][$i]) && $_POST['adatok'][$teljesites_adat][1][$i] && $_POST['adatok'][$teljesites_adat][1][$i] != '0000.00.00' && $_POST['adatok'][$teljesites_adat][1][$i] < URES_DATUM_JSBOL_PHP_ALATT ) {
            $volt_teljesites++;
            // Csak egy teljesítés dátumot fogadunk el, ezt jegyezzük is meg
            $beirt_teljesites = str_replace('.', '-', $_POST['adatok'][$teljesites_adat][1][$i]);
            $teljesites_i     = $i;
        }
        
        $i++;
    }

    // Ha több értéket/teljesítést is kitöltöttek, akkor hiba
    if( $volt_ertek > 1 ) {
        echo json_encode( array( 
                        'hiba'  => 'Csak egy szerződés tételhez írhat nettó értéket!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
    }
    if( $volt_teljesites > 1 ) {
        echo json_encode( array( 
                        'hiba'  => 'Csak egy szerződés tételhez írhat teljesítést!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
    }

    // Ha itt vagyunk, akkor csak egy nettó értéket/teljesítést írtak be. Kérdés, hogy megfelelő értéket írtak be?
    if( number_format($szamla_tetel[$netto_mezo], $tizedesek, ',', ' ') != number_format($beirt_netto_ertek, $tizedesek, ',', ' ') ) {
        echo json_encode( array(
                        'hiba'  => 'A nettó érték oszlopba kérem '.number_format($szamla_tetel[$netto_mezo], $tizedesek, ',', ' ') .' '. $penznem.'-t írjon, hisz ez az összeg szerepel a csatolni kívánt számla tételen!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
    }
    // Teljesítés dátuma a számla adatlapján található teljesítés dátumával egyezen meg
    if( $szamla['telj_dt'] != $beirt_teljesites ) {
        echo json_encode( array(
                        'hiba'  => 'A teljesítés oszlopba kérem '. datum_kiir($szamla['telj_dt']).' dátumot írjon, hisz ez szerepel a csatolni kívánt számlán!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
    }
    
    // Ha eltérő sorokban vannak új adatok, az is hiba
    // De a feltételt úgy fogalmazzuk meg, hogy ha van és van $teljesites_i és $ertek_i is. 
    // Mert ha nullás értéket kapcsolunk, akkor a 0-át nem fogja értéknek tekinteni, míg a dátumot igen, és a két sor nem lesz egyenlő. Na az ilyen esetet meg kell engedni.
    if( $teljesites_i && $ertek_i && $teljesites_i != $ertek_i ) {
        echo json_encode( array(
                        'hiba'  => 'Kérem azonos tételhez írja be a teljesítést és nettó értéket!'.$teljesites_i.' '.$ertek_i,
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
    }
    
    // Ha a kifizetett és a még kifizetésre váró összegek meghaladják a szerződés összegével, akkor hibaüzenet
    if( number_format($kifizetett_ossz, $tizedesek, '.', '') > number_format($szerzodes[$szerz_mezo], $tizedesek, '.', '') ) {
        echo json_encode( array( 
                        'hiba'  => 'A már kifizetett ('.number_format($kifizetett_ossz, $tizedesek, ',', ' ').' '.$penznem.') számlák összege több,<br>mint a szerződés összege ('.number_format($szerzodes[$szerz_mezo], $tizedesek, ',', ' ').' '.$penznem.')! Így nem menthető!',
                        'title' => 'Hiba',
                        ) );
                $weblap->leallit();
                exit();
                
    }

    // Ellenőrzések után tároljuk el a változtatásokat. 
    // Szerződés tétetel rekordok frissítése
    if( $_POST['adatok'][$recid_adat][1][$ertek_i] ) {
        $szerzodes_tetel_recid = $_POST['adatok'][$recid_adat][1][$ertek_i];
    } else {
        $szerzodes_tetel_recid = 'most hozza majd létre';
    }
    $muvelet  = 'Projektszámla tételhez projekt tételt csatolt. Számla recid: '.$recid.', Számla száma: '.$szamla['sz_szam'].', Számla tétel recid: '.$_POST['szamla_tetel_recid'].', Szerződés tétel recid: '.$szerzodes_tetel_recid;
    $valtozas = '';
    $i = 0;
    $kapott_recidk = array();
    foreach ( $_POST['adatok'][$recid_adat][1] as $szerzodes_tetel_recid ) {

        // Ha valós rekord, nem újonnan létrehozott fiktiv tétel
        if( $szerzodes_tetel_recid ) {
            // Ha már van számlája, akkor az egy szürke sor volt, annak nem mentjük el az adatait
            $van_szamlaja = $adatbazis_kapcsolat->s2('recid', 'projektszamla_tetel', "`projektszerzodes_tetel_recid`='{$szerzodes_tetel_recid}'");
            if( $van_szamlaja === false ) { $db_error = true; }
            if( !$van_szamlaja ) {

                $ertek_regi = $adatbazis_kapcsolat->s2('*', 'projektszerzodes_tetel', "`recid`='{$_POST['adatok'][$recid_adat][1][$i]}'");
                if( $ertek_regi === false ) { $db_error = true; }
                
                $sql = $adatbazis_kapcsolat->u('projektszerzodes_tetel',
                                                                        array($varh_ert_mezo,                              'teljesites',                                $ertek_mezo                               ),
                                                                        array($_POST['adatok'][$varhato_ert_adat][1][$i],  $_POST['adatok'][$teljesites_adat][1][$i],   $_POST['adatok'][$netto_ert_adat][1][$i]  ),
                                            "`recid`='{$_POST['adatok'][$recid_adat][1][$i]}'");
                if( $sql === false ) { $db_error = true; }
                
                // Ha devizás a számla, akkor nem csak a dev_ertek hanem a sima ertek mezőt is ki kell tölteni kapcsoláskor a szerőzdés tételnél.
                // Az `ertek` mező = `dev_ertek` * szerződés árfolyama
                if( $ertek_mezo == 'dev_ertek' ) {
                    $sql = $adatbazis_kapcsolat->u('projektszerzodes_tetel',
                                                                        array('varh_ert',                                                     'ertek'                                                       ),
                                                                        array($_POST['adatok'][$varhato_ert_adat][1][$i]*$szamla['arfolyam'], $_POST['adatok'][$netto_ert_adat][1][$i]*$szamla['arfolyam']  ),
                                                "`recid`='{$_POST['adatok'][$recid_adat][1][$i]}'");
                    if( $sql === false ) { $db_error = true; }
                }

                // Logolás
                if( $ertek_i == $i ) {
                    $valtozas .= $ertek_regi['recid'].' recid-jú szerződés tételt módosította, és EHHEZ KAPCSOLTA a számla tételt:' . "\n";
                } else {
                    $valtozas .= $ertek_regi['recid'].' recid-jú szerződés tételt módosította:' . "\n";
                }
                $valtozas .= '[mezo]'.$varh_ert_mezo.'[/mezo] [ertek_regi]' .$ertek_regi[$varh_ert_mezo]    .'[/ertek_regi] [ertek_uj]'.$_POST['adatok'][$varhato_ert_adat][1][$i]                      .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]teljesites[/mezo] [ertek_regi]'         .$ertek_regi['teljesites']      .'[/ertek_regi] [ertek_uj]'.str_replace('.', '-', $_POST['adatok'][$teljesites_adat][1][$i]).'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]'.$ertek_mezo.'[/mezo] [ertek_regi]'    .$ertek_regi[$ertek_mezo]       .'[/ertek_regi] [ertek_uj]'.$_POST['adatok'][$netto_ert_adat][1][$i]                        .'[/ertek_uj]'."\n";
            }
            
        // Az újonnan létrehozott fiktív tételt be kell szúrni
        } else {

            $uj_szerzodes_tetel_recid = $adatbazis_kapcsolat->i('projektszerzodes_tetel',
                                                                    array('projektszerzodes_recid', 'targy',                                'varh_erk',                               $varh_ert_mezo,                              'teljesites',                                $ertek_mezo,                                'loginnev',                         'insert_dt',    'insert_usr',                             'update_usr'                            ),
                                                                    array($szerzodes['recid'],      $_POST['adatok'][$targy_adat][1][$i],   $_POST['adatok'][$varh_erk_adat][1][$i],  $_POST['adatok'][$varhato_ert_adat][1][$i],  $_POST['adatok'][$teljesites_adat][1][$i],   $_POST['adatok'][$netto_ert_adat][1][$i],   $_SESSION[get_db_tabla().'user'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']  )
                                        );
            if( $uj_szerzodes_tetel_recid === false ) { $db_error = true; }
            
            // Ha devizás a számla, akkor nem csak a dev_ertek hanem a sima ertek mezőt is ki kell tölteni kapcsoláskor a szerőzdés tételnél.
            // Az `netto_ert` mező = `dev_net` * szerződés árfolyama
            if( $ertek_mezo == 'dev_ertek' ) {
                $sql = $adatbazis_kapcsolat->u('projektszerzodes_tetel',
                                                                        array('varh_ert',                                                     'ertek'                                                       ),
                                                                        array($_POST['adatok'][$varhato_ert_adat][1][$i]*$szamla['arfolyam'], $_POST['adatok'][$netto_ert_adat][1][$i]*$szamla['arfolyam']  ),
                                            "`recid`='{$uj_szerzodes_tetel_recid}'");
                if( $sql === false ) { $db_error = true; }
            }
            
            // Az újonnan létrehozott sorokat vegyük fel a tömbbe, mert ami nem a tömb része, azt törölni fogjuk (hisz onnan fogjuk tudni, mely sorokat törölték a popup táblázatából)
            array_push( $_POST['adatok'][$recid_adat][1], $uj_szerzodes_tetel_recid);

            // Ha épp az $ertek_i-edik ciklusnál járnánk, akkor a frissen létrehozott tétel recid-ját felvesszük a tömbbe, hisz a következő lépésnél még össze is kell kapcsolni
            if( $ertek_i == $i ) {
                $_POST['adatok'][$recid_adat][1][$ertek_i] = $uj_szerzodes_tetel_recid;
                $valtozas .= 'Szerződés tételt hozott létre, és EHHEZ KAPCSOLTA a számla tételt:' . "\n";
            } else {
                $valtozas .= 'Szerződés tételt hozott létre:' . "\n";
            }
            
            // Logolás
            $valtozas .= '[mezo]recid[/mezo] [ertek_uj]'                    .$uj_szerzodes_tetel_recid                  .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]projektszerzodes_recid[/mezo] [ertek_uj]'   .$szerzodes['recid']                        .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]targy[/mezo] [ertek_uj]'                    .$_POST['adatok'][$targy_adat][1][$i]       .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]varh_erk[/mezo] [ertek_uj]'                 .$_POST['adatok'][$varh_erk_adat][1][$i]    .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]'.$varh_ert_mezo.'[/mezo] [ertek_uj]'       .$_POST['adatok'][$varhato_ert_adat][1][$i] .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]teljesites[/mezo] [ertek_uj]'               .$_POST['adatok'][$teljesites_adat][1][$i]  .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]'.$ertek_mezo.'[/mezo] [ertek_uj]'          .$_POST['adatok'][$netto_ert_adat][1][$i]   .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]loginnev[/mezo] [ertek_uj]'                 .$_SESSION[get_db_tabla().'user']           .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]insert_dt[/mezo] [ertek_uj]'                .date('Y-m-d H:i:s')                        .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]insert_usr[/mezo] [ertek_uj]'               .$_SESSION[get_db_tabla().'teljes_nev']     .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]update_usr[/mezo] [ertek_uj]'               .$_SESSION[get_db_tabla().'teljes_nev']     .'[/ertek_uj]'."\n";
            
        }
        $i++;
    }

    // Számla tétel összekapcsolása a szerződés tétellel. Az $ertek_i-edik adatot kell menteni
    $sql = $adatbazis_kapcsolat->u('projektszamla_tetel', array('projektszerzodes_tetel_recid'), array($_POST['adatok'][$recid_adat][1][$ertek_i]), "`recid`='{$_POST['szamla_tetel_recid']}'");
    if( $sql === false ) { $db_error = true; }
    
    // Ezt a SESSION-ben is updatelni kell
    $i = 0;
    $fej_id = $recid;
    foreach ( $_SESSION['pr_szla_tetel_recid'][$fej_id] as $szla_tetel_recid ) {
        if( $szla_tetel_recid == $_POST['szamla_tetel_recid'] ) {
            $_SESSION['pr_szla_tetel_pr_tetel'][$fej_id][$i] = $_POST['adatok'][$recid_adat][1][$ertek_i];
        }
        $i++;
    }
    
    // Végül töröljük azokat a szerződés tételeket, amik nem jöttek át adatként (ki lettek törölve kliens oldalon)
    $_POST['adatok'][$recid_adat][1] = array_filter($_POST['adatok'][$recid_adat][1]);
    $torolt_tetelek = $adatbazis_kapcsolat->s('*', 'projektszerzodes_tetel', "`projektszerzodes_recid`='{$szerzodes['recid']}' AND `recid` NOT IN (".implode(', ', $_POST['adatok'][$recid_adat][1]).")");
    if( $torolt_tetelek ){
        $valtozas .= 'A következő szerződés tételeket törölte:'."\n".logolas_kapcsolodo_tablak_sorai($torolt_tetelek);
    }
    // Tényleges törlés
    $sql = $adatbazis_kapcsolat->d('projektszerzodes_tetel', "`projektszerzodes_recid`='{$szerzodes['recid']}' AND `recid` NOT IN (".implode(', ', $_POST['adatok'][$recid_adat][1]).")");
    if( $sql === false ) { $db_error = true; }
    
    // Legvégül logolunk
    $sql = $adatbazis_kapcsolat -> i(
        'log',
        array( 'felh_id',                               'nev',       'valtozas',    'ip'                   ),
        array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,    $valtozas,     $_SERVER['REMOTE_ADDR'])
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
            'title' => 'Hiba'
        ));
    }
    
    $weblap->leallit();
    exit();
}

// EZ NINCS HASZNÁLATBAN, A KÖVETKEZŐT KERESD: pop_tetelek_feltolt_modosit
// Tétel sorok kiíratása
if( isset($_POST['pop_tetelek_feltolt']) ) {
    
    // Ha van a GET-ben andoc_id, akkor azt itt $_POST-ként kapjuk meg, ami annyit jelent, 
    // hogy egy új számla felvitelénél vagyunk, új feltöltési módban, és az átmeneti táblából kell dolgozni
    if( $_POST['andoc_id'] ) {
        $szamla = $adatbazis_kapcsolat->s2('recid kcs_21 feldolgozva_dt tetel_elter', 'projektszamla_atmeneti', "`andoc_id`='{$_POST['andoc_id']}'");
        $fej_id = 'uj';
    } else {
        $szamla = $adatbazis_kapcsolat->s2('kcs_21 feldolgozva_dt tetel_elter', 'projektszamla', "`recid`='{$_POST['id']}'");
        $fej_id = $_POST['id'];
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
        $session_mezo           = 'pr_szla_tetel_dev_net';
    } else {
        $szamla_netto_mezo      = 'netto_ert';
        $penznem                = get_penznem();
        $tizedesek              = 0;
        $session_mezo           = 'pr_szla_tetel_netto_ert';
    }
    
    // Tételek táblázat sorait kiszámoljuk:
    $html = '';
    $i    = 0;
    foreach( $_SESSION['pr_szla_tetel_targy'][$fej_id] as $targy ) {
        
        $szla_netto_ert = $_SESSION[$session_mezo][$fej_id][$i];
        $torles = '';
        if( !$_SESSION['pr_szla_tetel_andocos'][$fej_id][$i] ) {
            $torles = '
                        <a onclick="popup_tetel_sor_torol(\'pop_tetel_'.$_SESSION['pr_szla_tetel_recid'][$fej_id][$i].'\');">
                            <span class="icon">
                                <i class="material-icons">
                                    delete
                                </i>
                            </span>
                        </a>
                ';
        }
        
        // Minden sornak adunk egy azonosítót. Mivel lehet csak egy ideiglenes sorról van szó, nem mindig van recid-nk, ezért időbélyeges azonosítót hozunk létre, megtoldva egy $i-vel.
        if( !$_SESSION['pr_szla_tetel_recid'][$fej_id][$i] ) {
            $_SESSION['pr_szla_tetel_recid'][$fej_id][$i] = time().rand(0, 1000);
        }
        $html .= nl2('
                        <tr class="pop_tetel_'.$_SESSION['pr_szla_tetel_recid'][$fej_id][$i].'">
                            <td class="pop_targy l">'.htmlspecialchars($_SESSION['pr_szla_tetel_targy'][$fej_id][$i]).'</td>
                            <td class="pop_netto_ert r nowrap">'.$_SESSION[$session_mezo][$fej_id][$i].'</td>
                            <td class="pop_ktgnem_autocomp l">'.getErtek('nev', 'ktgnem',  'id', $_SESSION['pr_szla_tetel_ktgnem'][$fej_id][$i]).'</td>
                            <td class="pop_ktghely_autocomp l">
                                '.getErtek('nev', 'ktghely', 'id', $_SESSION['pr_szla_tetel_ktghely'][$fej_id][$i]).'
                            </td>
                            <td>
                                <a class="szerk" onclick="popup_tetel_sor_szerk(\'pop_tetel_'.$_SESSION['pr_szla_tetel_recid'][$fej_id][$i].'\');">
                                    🖉
                                </a>
                            </td>
                            <td>
                                '.$torles.'
                                <input type="hidden" class="pop_ktgnem" name="pop_ktgnem_id" value="'.$_SESSION['pr_szla_tetel_ktgnem'][$fej_id][$i].'">
                                <input type="hidden" class="pop_ktghely" name="pop_ktghely_id" value="'.$_SESSION['pr_szla_tetel_ktghely'][$fej_id][$i].'">
                                <input type="hidden" class="pop_valtozott" value="'.$_SESSION['pr_szla_tetel_valtozott'][$fej_id][$i].'">
                                <input type="hidden" class="pop_recid" name="recid" value="'.$_SESSION['pr_szla_tetel_recid'][$fej_id][$i].'">
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

// Tétel sorok kiíratása
if( isset($_POST['pop_tetelek_feltolt_modosit']) ) {
    
    // Ha vissza van adva a számla, akkor már nem lehet tételeket feltöléteni
    if( $_POST['andoc_id'] ) {
        $recid = 0;
        $prsz  = $adatbazis_kapcsolat->s2('recid visszaadva dev_net netto kcs_21 feldolgozva_dt tetel_elter', 'projektszamla_atmeneti', "`andoc_id`='{$_POST['andoc_id']}'");
    } else {
        $recid = $_POST['id'];
        $prsz  = $adatbazis_kapcsolat->s2('visszaadva dev_net netto kcs_21 feldolgozva_dt tetel_elter', 'projektszamla', "`recid`='{$_POST['id']}'");
    }

    if( $prsz['visszaadva'] && !($prsz['tetel_elter'] && $_SESSION[get_db_tabla().'szemelyzet_id'] == MUSZAKI_ADMINISZTRATOR ) ) {
        echo json_encode( array(
                            'hiba'  => nl2('A számla már vissza van adva, így a tételei nem kezelhetőek!'),
                            'title' => 'Hiba',
                        ) );
        $weblap -> leallit();
        exit;
    }
    
    // Ha már fel van dolgozva egy számla, és a tételek eltérése sem áll fenn, akkor a tételei már nem szerkeszthetőek
    if( $prsz['feldolgozva_dt'] != '0000-00-00 00:00:00' && !$prsz['tetel_elter'] ) {
        echo json_encode( array(
            'hiba'  => 'A számla tételei már nem szerkeszthetőek!',
            'title' => 'Hiba',
            ) );
        $weblap -> leallit();
        exit;
    }
    
    // Illetve ezt az alábbi változókkal két ágra bontuk: Ft-os vagy devizás-e a számla.
    if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
        $szamla_netto_mezo      = 'dev_net';
        $szerzodes_netto_mezo   = 'dev_net';
        $penznem                = erteklista(ERTEKLISTA_PENZNEM, $_POST['kcs_21']);
        $szoveg_mod             = 'deviza ';
        $tizedesek              = 2;
        $session_mezo           = 'pr_szla_tetel_dev_net';
        $session_mezo_br        = 'pr_szla_tetel_dev_br';
    } else {
        $szamla_netto_mezo      = 'netto';
        $szerzodes_netto_mezo   = 'szerz_ossz';
        $penznem                = get_penznem();
        $szoveg_mod             = '';
        $tizedesek              = 0;
        $session_mezo           = 'pr_szla_tetel_netto_ert';
        $session_mezo_br        = 'pr_szla_tetel_brutto_ert';
    }
    
    // Projekt számlák táblázat kiírtatása előtt ellenőrizzük, hogy a hozzá tartozó szerződéshez milyen számlák tartoznak, és azoknak mennyi az össz nettó értéke.
    // Ha a Projekt számlák össz nettója több mint a hozzájuk tartozó szerződésé, akkor hibaüzenet
    // Ezt csak akkor tesszük meg, ha van már hozzárendelt szerződés
    if( $_POST['sz_szam'] ) {
        
        // Lekérjük, a projekt szerződéses összegét
        $pr_szerzodes = $adatbazis_kapcsolat->s2($szerzodes_netto_mezo.', kcs_21', 'projektszerzodes', "`sz_szam`='{$_POST['sz_szam']}'");
        
        // Illetve, hogy az aktuális számlán kívül milyen számlák tartoznak hozzá, és beleférünk-e a keretbe?
        $szamla_netto = str_replace( array(' ', ','), array('', '.'), $_POST[$szamla_netto_mezo]);
        $szamlak = $adatbazis_kapcsolat->s2('SUM(`'.$szamla_netto_mezo.'`)', 'projektszamla', "`sz_szam`='{$_POST['sz_szam']}' AND `recid`!='{$_POST['id']}' AND `visszaadva`=0");
        if( number_format($szamlak['SUM(`'.$szamla_netto_mezo.'`)'] + $szamla_netto, $tizedesek, '.', '') > number_format( $pr_szerzodes[$szerzodes_netto_mezo], $tizedesek, '.', '') ) {
            echo json_encode( array(
                                'hiba'  => nl2('
                                                Szerződött összeg: '.number_format($pr_szerzodes[$szerzodes_netto_mezo], $tizedesek, ',', ' ').' '.$penznem.'
                                                <br>Még felhasználható összeg: '.number_format($pr_szerzodes[$szerzodes_netto_mezo]-$szamlak['SUM(`'.$szamla_netto_mezo.'`)'], $tizedesek, ',', ' ').' '.$penznem.'
                                                <br>A számla '.$szoveg_mod.'nettó értéke több, mint a választott szerződésből még felhasználható '.$szoveg_mod.'összeg!
                                                '),
                                'focus' => $szamla_netto_mezo,
                                'title' => 'Hiba',
                            ) );
            $weblap -> leallit();
            exit;
        }
    }
    
    // Tételek táblázat sorait kiszámoljuk:
    $html = '';
    $fej_id = $recid;
    if( !$recid ) {
        $fej_id = 'uj';
    }
    
    $i = 0;
    foreach( $_SESSION['pr_szla_tetel_jogcim'][$fej_id] as $jogcim ) {
               
        // Minden sornak adunk egy azonosítót. Mivel lehet csak egy ideiglenes sorról van szó, nem mindig van recid-nk, ezért időbélyeges azonosítót hozunk létre, megtoldva egy $i-vel.
        $id_tmp = time();
        $id_tmp .= $i;
        
        $class = $targy = $torles = '';
        // Ha eltérő tételek volnának, akkor a törlés ikon helyett "zöld pipa/piros x"-eket jelenítünk meg
        if( $prsz['tetel_elter'] ) {
            if( $_SESSION['pr_szla_tetel_kifizetes'][$fej_id][$i] ) {
                $torles = '<span class="zold f20 b pop_pipalo" id="pipa_'.$_SESSION['pr_szla_tetel_recid'][$fej_id][$i].'" onclick="pipa_x_cserel(\''.$_SESSION['pr_szla_tetel_recid'][$fej_id][$i].'\')">✓</span>';
            } else {
                $torles = '<span class="piros f20 b pop_pipalo" id="pipa_'.$_SESSION['pr_szla_tetel_recid'][$fej_id][$i].'" onclick="pipa_x_cserel(\''.$_SESSION['pr_szla_tetel_recid'][$fej_id][$i].'\')">✖</span>';
            }
        // Törlés ikon, ha nem Andoc-os a tétel
        } else if( !$_SESSION['pr_szla_tetel_andocos'][$fej_id][$i] ) {
            $torles = '
                        <a onclick="popup_tetel_sor_torol(\'pop_tetel_'.$_SESSION['pr_szla_tetel_recid'][$fej_id][$i].'\');">
                            <span class="icon">
                                <i class="material-icons">
                                    delete
                                </i>
                            </span>
                        </a>
                ';
        }
        
        
        $pr_tetel = '';
        if( $_SESSION['pr_szla_tetel_pr_tetel'][$fej_id][$i] ) {
            $pr_tetel = $adatbazis_kapcsolat->s2('recid targy', 'projektszerzodes_tetel', "`recid`='{$_SESSION['pr_szla_tetel_pr_tetel'][$fej_id][$i]}'");
        }
        if( $pr_tetel ) {
            $targy  = htmlspecialchars_decode( $pr_tetel['targy'] );
            $class  = ''; // ' szerzodes_szamla_tetel_szurke'
            $torles = '';
        } else {
            if( $_SESSION['pr_szla_tetel_recid'][$fej_id][$i] ) {
                $targy = '<div class="c"><input type="button" class="gomb" value="kapcsolás" onclick="kapcsolas_popup_nyit(\''.$_SESSION['pr_szla_tetel_recid'][$fej_id][$i].'\');"></div>';
            }
        }
        
        $jogcim  = $adatbazis_kapcsolat->s2('jogcim ktghely megn',  'jogcim',     "`recid`='{$_SESSION['pr_szla_tetel_jogcim' ][$fej_id][$i]}'");
        $afakod  = $adatbazis_kapcsolat->s2('afa kod',              'afakod',     "`recid`='{$_SESSION['pr_szla_tetel_afakod' ][$fej_id][$i]}'");
        $dijelem = $adatbazis_kapcsolat->s2('info',                 'erteklista', "`recid`='{$_SESSION['pr_szla_tetel_dijelem'][$fej_id][$i]}'");
        
        $class = '';
        if( $_SESSION['pr_szla_tetel_kifizetes'][$fej_id][$i] ) {
            $class = ' piros';
        }
        
        // Pár oszlop csak akkor kerül bele, ha nem eltérő tételek miatt vagyunk itt
        $tovabbi_oszlopok = $tovabbi_oszlopok2 = '';
        if( $prsz['tetel_elter'] ) {
            $tovabbi_oszlopok .= nl2('
                                    <td class="l pop_jogcim_txt">'.$jogcim['jogcim'].'</td>
                                    <td>'.$_SESSION['pr_szla_tetel_fokonyvi'][$fej_id][$i].'</td>
                                    <td>'.$_SESSION['pr_szla_tetel_ktg_hely'][$fej_id][$i].'</td>
                                ');
            if( $prsz['kcs_21']==KCS_21_HUF ) {
                $tovabbi_oszlopok2 .= nl2('
                                        <td class="pop_netto nowrap r">' .$_SESSION['pr_szla_tetel_netto_ert' ][$fej_id][$i].'</td>
                                        <td class="pop_brutto nowrap r">'.$_SESSION['pr_szla_tetel_brutto_ert'][$fej_id][$i].'</td>
                                    ');
            } else {
                $tovabbi_oszlopok2 .= nl2('
                                        <td class="pop_netto nowrap r">'  .$_SESSION['pr_szla_tetel_netto_ert' ][$fej_id][$i].'</td>
                                        <td class="pop_brutto nowrap r">' .$_SESSION['pr_szla_tetel_brutto_ert'][$fej_id][$i].'</td>
                                        <td class="pop_dev_net nowrap r">'.$_SESSION['pr_szla_tetel_dev_net'   ][$fej_id][$i].'</td>
                                        <td class="pop_dev_br nowrap r">' .$_SESSION['pr_szla_tetel_dev_br'    ][$fej_id][$i].'</td>
                                    ');
            }
            
        // Popup tételek normál esetben, sima tételek szerkesztésekor
        } else {
            $tovabbi_oszlopok .= nl2('
                                <td class="pop_targy l">'.$_SESSION['pr_szla_tetel_targy'][$fej_id][$i].'</td>
                                <td class="pop_netto r nowrap">' .number_format($_SESSION[$session_mezo   ][$fej_id][$i], $tizedesek, ',', ' ').'</td>
                                <td class="pop_brutto r nowrap">'.number_format($_SESSION[$session_mezo_br][$fej_id][$i], $tizedesek, ',', ' ').'</td>
                                <td class="pop_jogcim_txt nowrap r">'.$jogcim['jogcim'].'</td>
                                <td class="pop_fokonyvi nowrap r">'.$_SESSION['pr_szla_tetel_fokonyvi'][$fej_id][$i].'</td>
                                <td class="pop_pr_tetel l" data-pr_tetel_recid="'.$pr_tetel['recid'].'">
                                    '.$targy.'
                                </td>
                                ');
        }
        $html .= nl2('
                        <tr class="pop_tetel_'.$_SESSION['pr_szla_tetel_recid'][$fej_id][$i].$class.'">
                            '.$tovabbi_oszlopok.'
                            '.$tovabbi_oszlopok2.'
                            <td>
                                '.$torles.'
                                <input type="hidden" class="pop_recid" name="recid" value="'  .$_SESSION['pr_szla_tetel_recid'    ][$fej_id][$i].'">
                                <input type="hidden" class="pop_jogcim" name="jogcim" value="'.$_SESSION['pr_szla_tetel_jogcim'   ][$fej_id][$i].'">
                                <input type="hidden" class="pop_afa" name="afa" value="'      .$_SESSION['pr_szla_tetel_afakod'   ][$fej_id][$i].'">
                                <input type="hidden" class="pop_valtozott" value="'           .$_SESSION['pr_szla_tetel_valtozott'][$fej_id][$i].'">
                            </td>
                        </tr>');
        $i++;
    }
    
    // Ha tételek száma eltér, akkor kicsit más popup jön fel: pl. nincsenek inputok
    $input_mutat = '1';
    $css_kod     = '';
    if( $prsz['tetel_elter'] ) {
        $input_mutat = '';
        // Ha eltérő tételek miatt vagyunk itt, akkor hozzáadunk még egy CSS kódot, hogy a táblázat fejlécét elrejtsük
        $css_kod .= '<style type="text/css">.eltereskor_rejt { display: none; }</style>';
    } else {
        $css_kod .= '<style type="text/css">.alapbol_rejt { display: none; }</style>';
    }
   
    echo json_encode( array(
        'html'          => $html,
        'css'           => $css_kod,
        'input_mutat'   => $input_mutat,
        'netto'         => number_format($prsz[$szamla_netto_mezo], $tizedesek, ',', ' '),
        ) );
    
    $weblap -> leallit();
    exit();
}

// Kapcsoló popup feltöltése
if( isset($_POST['pop_kapcsolas_feltolt']) ) {
    
    // Mindenek előtt ellenőrizzük, hogy a szerződés partnerkódja azonos-e a számla partner kódjával
    $szamla_tetel   = $adatbazis_kapcsolat->s2('projektszamla_recid netto_ert dev_net', 'projektszamla_tetel', "`recid`='{$_POST['szamla_tetel_recid']}'");
    $szamla         = $adatbazis_kapcsolat->s2('sz_szam szallito_id', 'projektszamla', "`recid`='{$szamla_tetel['projektszamla_recid']}'");
    $szerzodes      = $adatbazis_kapcsolat->s2('szallito_id', 'projektszerzodes', "`sz_szam`='{$szamla['sz_szam']}'");

    if( $szamla['szallito_id'] != $szerzodes['szallito_id'] ) {
        echo json_encode( array(
            'hiba'  => 'A számla partnerkódja nem egyezik a szerződés partnerkódjával, kérem ellenőrizze!',
            'title' => 'Hiba'
            ) );

        $weblap -> leallit();
        exit();
    }

    $penznem    = erteklista(ERTEKLISTA_PENZNEM, $_POST['kcs_21']);
    $tizedesek  = 0;
    $szl_netto  = 'netto';
    $mezo       = 'varh_ert';
    $netto_mezo = 'netto_ert';
    $szerz_mezo = 'szerz_ossz';
    $ertek_mezo = 'ertek';
    if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
        $szl_netto  = 'dev_net';
        $netto_mezo = 'dev_net';
        $mezo       = 'dev_varh_ert';
        $szerz_mezo = 'dev_net';
        $ertek_mezo = 'dev_ertek';
        $tizedesek  = 2;
    }
    
    // Formázott számból kivesszük a szóközöket
    $_POST[$szl_netto] = str_replace(array(' ', ','), array('', '.'), $_POST[$szl_netto]);
    
    // Szerződés összegének kiszámítása
    if( $szamla_tetel['projektszamla_recid'] ) {
        $szamla = $adatbazis_kapcsolat->s2('sz_szam telj_dt', 'projektszamla', "`recid`='{$szamla_tetel['projektszamla_recid']}'");
    }

    // Szerződés számot post-ként is megkapjuk. Ha mégsem akkor hibát dobunk.
    if( $_POST['sz_szam'] ) {

        // Hiába kaptuk meg post-ként, minket az érdekel hogy DB szintén meg van-e. Ha nem akkor figyelmeztetés, hogy előbb mentsen adatlapot
        if( !$szamla['sz_szam'] ) {

            echo json_encode( array(
                'hiba'  => 'Kapcsolás előtt mentse szerződésszámot a számla adatlapján!',
                'title' => 'Hiba'
                ) );

            $weblap -> leallit();
            exit();
            
        }
        
        // Szerződés lekérdezése
        $szerzodes = $adatbazis_kapcsolat->s2('recid kcs_21 '.$szerz_mezo.' telephely_id', 'projektszerzodes', "`sz_szam`='{$szamla['sz_szam']}'");

        // Ellenőrizzük, hogy a kiválasztott szálloda és a projekt szám stimmel-e
        if( $szerzodes['telephely_id'] != $_POST['telephely_id'] ) {
            echo json_encode(array(
                'hiba'  => 'A projekt szám nem megfelelő szállodához tartozik!<br>Kérem egy '.getSzallodaNev($szerzodes['telephely_id']).' szállodához tartozó szerződést adjon hozzá a számla adatpaján!',
                'title' => 'Hiba',
            ));
            $weblap -> leallit();
            exit();
        }
        
        $szerzodes_ossz = number_format($szerzodes[$szerz_mezo], $tizedesek, ',', ' ').' '.$penznem;
        
        // Ha a szerződés és a számla pénzneme nem azonos, akkor hibaüzenet
        if( $szerzodes['kcs_21'] != $_POST['kcs_21'] ) {
            echo json_encode( array(
                'hiba'  => nl2('
                                A számla tétele nem kapcsolható a szerződés tételhez,
                                <br>mert a számla pénzneme nem egyezik a szerződés pénznemével!
                                '),
                'title' => 'Hiba'
                ) );

            $weblap -> leallit();
            exit();
        }
        
    // Ilyen elvileg elő sem fordulhat, de jobb leellenőrizni
    } else {

        echo json_encode( array(
            'hiba'  => 'Előbb adja meg szerződés számot az adatlapon!',
            'title' => 'Hiba'
            ) );
        
        $weblap -> leallit();
        exit();
        
    }
    
    // A nettó mezőben levő összeg azonos-e a tételek összegével?
    $tetelek = $adatbazis_kapcsolat->s2('SUM(`'.$netto_mezo.'`)', 'projektszamla_tetel', "`projektszamla_recid`='{$szamla_tetel['projektszamla_recid']}'");
    if( $tetelek['SUM(`'.$netto_mezo.'`)']!= $_POST[$szl_netto] ) {
        echo json_encode( array(
            'hiba'  => 'A számla aktuális nettó értéke ('.number_format($_POST[$szl_netto], $tizedesek, ',', ' ').' '.$penznem.') nem egyenlő a tételek értékeivel ('.number_format($tetelek['SUM(`'.$netto_mezo.'`)'], $tizedesek, ',', ' ').' '.$penznem.'), így nem kapcsolható!',
            'title' => 'Hiba'
            ) );
        
        $weblap -> leallit();
        exit();
    }
   
    // Tételek táblázat sorait kiszámoljuk:
    $tabla  = '';
    $i      = 0;
    $szerzodes_tetelek = $adatbazis_kapcsolat->s('*', 'projektszerzodes_tetel', "`projektszerzodes_recid`='{$szerzodes['recid']}'", 'varh_erk');
    foreach( $szerzodes_tetelek as $tetel ) {
        
        // Minden sornak adunk egy azonosítót. Mivel lehet csak egy ideiglenes sorról van szó, nem mindig van recid-nk, ezért időbélyeges azonosítót hozunk létre, megtoldva egy $i-vel.
        $id_tmp = time();
        $id_tmp .= $i;
        
        $class      = '';
        $disabled   = '';
        $torol      = '<a onclick="popup_tetel_sor_torol(\'pop_kapcsol_'.$id_tmp.'\');"><span class="icon"><i class="material-icons">delete</i></span></a>';
        $van_szamlaja = $adatbazis_kapcsolat->s('recid', 'projektszamla_tetel', "`projektszerzodes_tetel_recid`='{$tetel['recid']}'");
// echo $adatbazis_kapcsolat->gp() . '<br>';
        if( $van_szamlaja ) {
            $disabled   = ' disabled';
            $class      = ' szerzodes_szamla_tetel_szurke';
            $torol      = '';
        }
        
        $tabla .= nl2('
                        <tr class="pop_kapcsol_' . $id_tmp.$class . '">
                            <td class="pop_targy l">' . $tetel['targy'] . '</td>
                            <td class="pop_varh_erk">' . datum_kiir($tetel['varh_erk']) . '</td>
                            <td><input type="text" id="varh_ert_'.$id_tmp.'" value="'.number_format($tetel[$mezo], $tizedesek, ',', ' ').'" class="r f_num pop_varhato_ert" data-tort="'.$tizedesek.'" maxlength="12"'.$disabled.'></td>
                            <td><input type="text" value="'.datum_kiir($tetel['teljesites']).'" class="datepicker_matol pop_teljesites" maxlength="10"'.$disabled.'></td>
                            <td><input type="text" id="ertek_'.$id_tmp.'" value="'.number_format($tetel[$ertek_mezo], $tizedesek, ',', ' ').'" class="r f_num pop_netto_ert" data-tort="'.$tizedesek.'" maxlength="12"'.$disabled.'></td>
                            <td>
                                '.$torol.'
                                <span class="dn pop_recid">'.$tetel['recid'].'</span>
                            </td>
                        </tr>');
        $i++;
    }
   
    echo json_encode( array(
        'tabla'                 => $tabla,
        'szerzodes_ossz'        => $szerzodes_ossz,
        'szamla_tetel_netto'    => number_format($szamla_tetel[$netto_mezo], $tizedesek, ',', ' '),
        'szamla_telj_dt'        => datum_kiir($szamla['telj_dt']),
        ) );
    
    $weblap -> leallit();
    exit();
}

// Eredetileg azt vizsgáltuk, hogy van-e a session-ban tétel, de sok más egyéb, új feltöltéskor ellenőrizendő dolgot is itt vizsgálunk PHP-val
// Tehát ez új feltöltéskor fut le
if( isset($_POST['van_tetel']) ) {
    
    // Kötelező mezők ellenőrzése
    foreach ( $akt_tomb as $elem ) {
        // Ha üres             VAGY ha dátum, és az értéke nagyobb mint 4000.00.00 - URES_DATUM_JSBOL_PHP_ALATT (tehát üres)
        if( $elem['alert'] && ( !trim($_POST[$elem['name']]) || ( strpos($elem['class'], 'datepicker')!==false ) && $_POST[$elem['name']]>URES_DATUM_JSBOL_PHP_ALATT ) ){
            echo json_encode(array(
                'hiba'  => $elem['alert'],
                'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2();">',
                'title' => 'Hiba',
                'focus' => $elem['name'],
            ));
            $weblap -> leallit();
            exit();
        }
        
    }
    
    // Vannak dátumok, amik csak múltbeliek lehetnek. Ennek ellenőrzését itt végezzük el
    $csak_mult_datum_lehet = array(
                                    array('name' => 'erk_dt',        'alert' => 'Céghez érkezett dátumhoz csak mai, vagy múltbeli dátum adható meg!'),
                                    // array('name' => 'kelte_dt',      'alert' => 'A számla kelte dátumhoz csak mai, vagy múltbeli dátum adható meg!'),
                                    array('name' => 'visszaadva_dt', 'alert' => 'A visszaadva dátumhoz csak mai, vagy múltbeli dátum adható meg!'),
                                );
    
    foreach ( $csak_mult_datum_lehet as $elem ) {

        // Visszaadva dátumot csak akkor figyeljük, ha a visszaadva pipa be is van pipálva
        if( $elem['name'] != 'visszaadva_dt' || $_POST['visszaadva'] == 'true' ) {

            if( $_POST[$elem['name']] > date('Y.m.d') ) {
                echo json_encode(array(
                    'hiba'  => $elem['alert'],
                    'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); szurkit_zar(\'mehet_megsem_mutat\'); $(\'#'.$elem['name'].'\').focus();">',
                    'title' => 'Hiba',
                    'focus' => 'visszaadva_dt',
                ));
                $weblap -> leallit();
                exit();
            }
        }
    }
    
    // Ellenőrizzük, hogy a dátum mezők rendben vannak-e? (Js alatt nem akar lefutni... elég összetett már a JS kód, mert űrlap elpostolása előtt még ajax-os ellenőrzés is lefut, ez kavar be). De itt - ahol az ajaxos ellenőrzések vannak - könnyen elelnőrizni tudjuk
    if( $_POST['erk_dt'] < '9000.00.00' && !valos_datum_e($_POST['erk_dt']) ) {
        echo json_encode(array(
            'hiba'  => 'Kérem egy valós dátumot adjon meg érkezés dátumának!',
            'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2();">',
            'title' => 'Hiba',
            'focus' => 'erk_dt',
        ));
        $weblap -> leallit();
        exit();
    }
    if( $_POST['kelte_dt'] < '9000.00.00' && !valos_datum_e($_POST['kelte_dt']) ) {
        echo json_encode(array(
            'hiba'  => 'Kérem egy valós dátumot adjon meg Keltének!',
            'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2();">',
            'title' => 'Hiba',
            'focus' => 'kelte_dt',
        ));
        $weblap -> leallit();
        exit();
    }
    if( $_POST['telj_dt'] < '9000.00.00' && !valos_datum_e($_POST['telj_dt']) ) {
        echo json_encode(array(
            'hiba'  => 'Kérem egy valós dátumot adjon meg Teljesítése dátumnak!',
            'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2();">',
            'title' => 'Hiba',
            'focus' => 'telj_dt',
        ));
        $weblap -> leallit();
        exit();
    }
    if( $_POST['fizhat_dt'] < '9000.00.00' && !valos_datum_e($_POST['fizhat_dt']) ) {
        echo json_encode(array(
            'hiba'  => 'Kérem egy valós dátumot adjon meg Fizetési határidőnek!',
            'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2();">',
            'title' => 'Hiba',
            'focus' => 'fizhat_dt',
        ));
        $weblap -> leallit();
        exit();
    }
    if( $_POST['visszaadva'] == 'true' && $_POST['visszaadva_dt'] > '9000.00.00' ) {
        echo json_encode(array(
            'hiba'  => 'Kérem adja meg a Visszaadás dátumát!',
            'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2();">',
            'title' => 'Hiba',
            'focus' => 'visszaadva_dt',
        ));
        $weblap -> leallit();
        exit();
    }
    if( $_POST['visszaadva'] == 'true' && $_POST['visszaadva_dt'] < '9000.00.00' && !valos_datum_e($_POST['visszaadva_dt']) ) {
        echo json_encode(array(
            'hiba'  => 'Kérem egy valós dátumot adjon meg Visszaadva dátumnak!',
            'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2();">',
            'title' => 'Hiba',
            'focus' => 'visszaadva_dt',
        ));
        $weblap -> leallit();
        exit();
    }
    if( $_POST['visszaadva'] == 'false' && $_POST['visszaadva_dt'] && $_POST['visszaadva_dt'] < '9000.00.00' ) {
        echo json_encode(array(
            'hiba'  => 'A Visszaadás dátumát csak akkor töltse ki, ha a visszaadva pipát be is kapcsolja!',
            'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2();">',
            'title' => 'Hiba',
        ));
        $weblap -> leallit();
        exit();
    }
    
    // Ha szálloda+partner+nettó (deb)érték már szerepel a rendszerben, akkor kell egy figyelmeztetés
    if( !$_POST['kcs_21'] || $_POST['kcs_21']==KCS_21_HUF ) {
        $netto_oszlop = 'netto';
        $tizedesek    = 0;
        $penznem      = get_penznem();
    } else {
        $netto_oszlop = 'dev_net';
        $tizedesek    = 2;
        $penznem      = erteklista(ERTEKLISTA_PENZNEM, $_POST['kcs_21']);
    }
    $szamla_netto = str_replace( array(' ', ','), array('', '.'), $_POST[$netto_oszlop]);

    if( !$_POST['azonos_szamla_mehet'] && $adatbazis_kapcsolat->s('recid', 'projektszamla', "`telephely_id`='{$_POST['telephely_id']}' AND `szallito_id`='{$_POST['szallito_id']}' AND `{$netto_oszlop}`='{$szamla_netto}'") ) {
        echo json_encode(array(
            'hiba'  => nl2('Szálloda: '.getSzallodaNev($_POST['telephely_id']).'
                            <br>Partner: '.getErtek('nev', 'szallito', 'id', $_POST['szallito_id']).'
                            <br>Nettó érték: '.number_format($szamla_netto, $tizedesek, ',', ' ').' '.$penznem.'
                            <br>
                            <br>Ennek a partnernek, erre a szállodára már van egy ilyen értékű számlája.
                            <br>Folytatja?'),
            'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="azonos_szamla_mehet=1; mentesGomb(); alert2();"><input type="button" class="gomb piros" value="Nem" onclick="alert2();">',
        ));
        $weblap -> leallit();
        exit();
    }
    
    // Deviza vagy sem
    if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
        $szamla_netto_mezo      = 'dev_net';
        $szerzodes_netto_mezo   = 'dev_net';
        $penznem                = erteklista(ERTEKLISTA_PENZNEM, $_POST['kcs_21']);
        $szoveg_mod             = 'deviza ';
        $tizedesek              = 2;
        $session_mezo           = 'pr_szla_tetel_dev_net';
    } else {
        $szamla_netto_mezo      = 'netto';
        $szerzodes_netto_mezo   = 'szerz_ossz';
        $penznem                = get_penznem();
        $szoveg_mod             = '';
        $tizedesek              = 0;
        $session_mezo           = 'pr_szla_tetel_netto_ert';
    }
    
    $szamla_netto_erteke = str_replace(array(' ', ','), array('', '.'), $_POST[$szamla_netto_mezo]);

    // Ha megvan adva a szerződés szám
    if( $_POST['sz_szam'] ){
        
        // Ellenőrizzük, hogy a kiválasztott szálloda és a projekt szám stimmel-e
        $pr_szerzodes = $adatbazis_kapcsolat->s2('telephely_id szerz_ossz dev_net kcs_21', 'projektszerzodes', "`sz_szam`='{$_POST['sz_szam']}'");
        if( $pr_szerzodes['telephely_id']!=$_POST['telephely_id'] ){
            echo json_encode(array(
                'hiba'  => 'A projekt szám nem megfelelő szállodához tartozik!',
                'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2();">',
                'title' => 'Hiba',
            ));
            $weblap -> leallit();
            exit();
        }
        
        // Ellenőrizzük, hogy beleférünk-e még a szerződött összegbe
        // Ezt viszont két ágra bontottuk, ugyanis másképp járunk el, ha a számla devizás, és másként, ha HUF-os. Ezért kerülnek beállításra az alábbi változók, de maga az ellenőrzés/hibaüzenet így egységesítve van
        // HUF esetén (ha nincs pénznem az HUF-nak számít)

        // Szerződött összeg ellenőrése
        $szamla_netto = str_replace( array(' ', ','), array('', '.'), $_POST[$szamla_netto_mezo]);
        $szamlak = $adatbazis_kapcsolat->s2('SUM(`'.$szamla_netto_mezo.'`)', 'projektszamla', "`sz_szam`='{$_POST['sz_szam']}' AND `visszaadva`=0");
        if( number_format($szamlak['SUM(`'.$szamla_netto_mezo.'`)']+$szamla_netto, $tizedesek, ',') > number_format($pr_szerzodes[$szerzodes_netto_mezo], $tizedesek, ',') ) {
            echo json_encode( array(
                'hiba'  => nl2('
                                Szerződött összeg: '.number_format($pr_szerzodes[$szerzodes_netto_mezo], $tizedesek, ',', ' ').' '.$penznem.'
                                <br>Még felhasználható összeg: '.number_format($pr_szerzodes[$szerzodes_netto_mezo]-$szamlak['SUM(`'.$szamla_netto_mezo.'`)'], $tizedesek, ',', ' ').' '.$penznem.'
                                <br>A számla '.$szoveg_mod.'nettó értéke több, mint a választott szerződésből még felhasználható összeg!
                                '),
                'select' => $szamla_netto_mezo,
                'title'  => 'Hiba',
            ) );
            $weblap -> leallit();
            exit;
        }

    }
        
    // Új feltöltésről van most szó: Ha van feltöltött tétel és nem nullás a számla, akkor az összegüknek meg kell egyezzen a számla nettó értékével
    // Módosításkor a fuggvenyek.php-ban ellenőrizzük ugyanezt
    $fej_id = 'uj';
    if( $_SESSION['pr_szla_tetel_targy'][$fej_id] && !$_POST['nullas'] ) {

        $tetelek_osszege = 0;
        foreach ( $_SESSION[$session_mezo][$fej_id] as $ertek ) {
            $ertek = str_replace(array(' ', ','), array('', '.'), $ertek);
            $tetelek_osszege += $ertek;
        }
        if( number_format($tetelek_osszege, $tizedesek, ',', ' ') != number_format($szamla_netto_erteke, $tizedesek, ',', ' ') ) {
            echo json_encode( array(
                                'hiba'  => 'A számla tételeinek összege ('. number_format($tetelek_osszege, $tizedesek, ',', ' ').' '.$penznem.') nem egyezik meg a számla nettó értékével ('. number_format($szamla_netto_erteke, $tizedesek, ',', ' ').' '.$penznem.')!<br>Így nem menthető!',
                                'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); szurkit_zar(\'mehet_megsem_mutat\');">',
                                'title' => 'Hiba',
                            ) );
            $weblap->leallit();
            exit();
        }
        
    }
    
    // Ha idáig eljutottunk, akkor kiírjuk hogy van (még akkor is, ha nincsenek tételek, de ez nem baj, mert a van, annyit jelent, hogy menthető az adatlap).
    echo json_encode( array(
                        'van_tetel' => 'van'
                    ) );
    $weblap->leallit();
    exit();
    
    $weblap->leallit();
    exit();
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
    
    $weblap -> leallit();
    exit();
}

// CSV generálása
if( isset($_GET['csv']) ) {

    require_once 'selectek.php';

    // CSV export előkészítése
    $csv_fajl_neve = 'Projekt-számla';
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
                            `'.get_db_tabla().$megnevezes.'`.`muszak_szam` as \'Műszak szám\',
                            `'.get_db_tabla().$megnevezes.'`.`musz_dt` as \'Műszak dátum\',
                            `'.get_db_tabla().$megnevezes.'`.`pr_szam` as \'Projekt szám\',
                            `'.get_db_tabla().$megnevezes.'`.`let_fel` as \'Felelős\',
                            '.
                            // Innentől a tételek
                            '
                            `'.get_db_tabla().$megnevezes.'_tetel`.`targy` as `Tétel tárgy`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`megn` as `Főkönyvi számlaszám`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`netto_ert` as `Tétel nettó érték`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`brutto_ert` as `Tétel bruttó érték`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`dev_net` as `Tétel nettó érték (devizában)`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`dev_br` as `Tétel bruttó érték (devizában)`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`afa` as `Áfa (%)`
                            '.$lekerdezes_fromtol.$_SESSION['akt_sql_order'];
                $query = mysqli_query($db_kapcs, $sql);

                // Nagyobb CSV-kre kérdezzünk rá előzetesen
                $osszes_sor = mysqli_num_rows($query);
// Itt ilyet ne csináljunk, mert a csv_link_cserel_kattint 3 paraméterét nem tudom kimenteni
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
                while ( $sorok = mysqli_fetch_assoc($query) ) {

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
                        if( $kulcs == 'Felelős' ) {
                            $sor = getErtek('teljes_nev', 'login', 'recid', $sor);
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
                            `'.get_db_tabla().$megnevezes.'`.`muszak_szam` as \'Műszak szám\',
                            `'.get_db_tabla().$megnevezes.'`.`musz_dt` as \'Műszak dátum\',
                            `'.get_db_tabla().$megnevezes.'`.`pr_szam` as \'Projekt szám\',
                            `'.get_db_tabla().$megnevezes.'`.`let_fel` as \'Felelős\'
                            '.$lekerdezes_fromtol.$_SESSION['akt_sql_order'];
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
                        if( $kulcs == 'Felelős' ) {
                            $sor = getErtek('teljes_nev', 'login', 'recid', $sor);
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




$szamla_html = '';

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
    if( isset($_GET['uj']) && (_jog(JOG_PRSZAMLAK_KEZEL) || _jog(JOG_PR_SAJAT_SZAMLA_KEZEL)) ) {

        // Ha már volt regisztrálva új felvitel, akkor be se töltsük az új felvitel felületet
        if( $_COOKIE['uj_felvitel_'.$megnevezes] ) {
            $szamla_html .= nl2('
                                <h1 class="piros">A Projekt számla feltöltése már megkezdődött,<br>kérem előbb azt fejezze be, vagy lépjen ki belőle!</h1>
                                <script>
                                    setTimeout(function(){
                                        $(\'#szerk_form\').hide();
                                    }, 200);
                                </script>
                                ');
            

        // Regisztráljuk, hogy elkezdődött egy új felvitel, hogy ne lehessen két új felvitelt elindítani
        } else {
        
            // Regisztráljuk, hogy elkezdődött egy új felvitel, hogy ne lehessen két új felvitelt elindítani
            $szamla_html .= nl2('
                            <script>
                                createCookie(\'uj_felvitel_\'+baseName(location), \''.$_GET['andoc_id'].'\', 1);
                            </script>
                        ');

            $szamla  = $adatbazis_kapcsolat->s2('recid kcs_21', 'projektszamla_atmeneti', "`andoc_id`='{$_GET['andoc_id']}'");

            // Gombok kirajzolása minden oldalra
            // Projektszámla mentésekor HA nem nullás a számla, akkor ellenőrizzük, hogy a számla nettó értéke azonos-e a tételek összegével
            // Deviza esetén más session-t járunk be
            $tizedesek  = 0;
            $mezo1      = 'ossz_netto';
            $mezo2      = 'netto';
            $mezo3      = 'ossz_brutto';
            $mezo4      = 'brutto';
            $kerekit    = KEREKITESI_HATAR_HUF;
            if( $szamla['kcs_21']!=KCS_21_HUF ) {
                $tizedesek  = 2;
                $mezo1      = 'ossz_dev_net';
                $mezo2      = 'dev_net';
                $mezo3      = 'ossz_dev_br';
                $mezo4      = 'dev_br';
                $kerekit    = KEREKITESI_HATAR_DEV;
            }

            // Új felvitelkor updateljük a Session-ben a tételek adatait, így lesz jó a popupban
            $i = 0;
            $ossz_netto   = 0;
            $ossz_dev_net = 0;
            $fej_id = 'uj';
            $_SESSION['pr_szla_tetel_recid'     ][$fej_id] =
            $_SESSION['pr_szla_tetel_jogcim'    ][$fej_id] = 
            $_SESSION['pr_szla_tetel_fokonyvi'  ][$fej_id] =
            $_SESSION['pr_szla_tetel_ktg_hely'  ][$fej_id] =
            $_SESSION['pr_szla_tetel_targy'     ][$fej_id] = 
            $_SESSION['pr_szla_tetel_netto_ert' ][$fej_id] = 
            $_SESSION['pr_szla_tetel_brutto_ert'][$fej_id] = 
            $_SESSION['pr_szla_tetel_dev_net'   ][$fej_id] = 
            $_SESSION['pr_szla_tetel_dev_br'    ][$fej_id] = 
            $_SESSION['pr_szla_tetel_afakod'    ][$fej_id] = 
            $_SESSION['pr_szla_tetel_ktgnem'    ][$fej_id] = 
            $_SESSION['pr_szla_tetel_valtozott' ][$fej_id] = 
            $_SESSION['pr_szla_tetel_andocos'   ][$fej_id] = 
            $_SESSION['pr_szla_tetel_pr_tetel'  ][$fej_id] = 
            $_SESSION['pr_szla_tetel_kifizetes' ][$fej_id] = 
                                                    array();
            $tetelek = $adatbazis_kapcsolat->s('*', 'projektszamla_tetel_atmeneti', "`projektszamla_recid`='{$szamla['recid']}'");
            foreach ( $tetelek as $tetel ) {
                $ossz_netto   += $tetel['netto_ert'];
                $ossz_dev_net += $tetel['dev_net'];
                $_SESSION['pr_szla_tetel_recid'     ][$fej_id][$i] = $tetel['recid'         ];
                $_SESSION['pr_szla_tetel_jogcim'    ][$fej_id][$i] = $tetel['jogcim_recid'  ];
                $_SESSION['pr_szla_tetel_fokonyvi'  ][$fej_id][$i] = $tetel['ktgnem_id'     ];
                $_SESSION['pr_szla_tetel_ktg_hely'  ][$fej_id][$i] = $tetel['ktghely_id'    ];
                $_SESSION['pr_szla_tetel_targy'     ][$fej_id][$i] = $tetel['targy'         ];
                $_SESSION['pr_szla_tetel_netto_ert' ][$fej_id][$i] = $tetel['netto_ert'     ];
                $_SESSION['pr_szla_tetel_brutto_ert'][$fej_id][$i] = $tetel['brutto_ert'    ];
                $_SESSION['pr_szla_tetel_dev_net'   ][$fej_id][$i] = $tetel['dev_net'       ];
                $_SESSION['pr_szla_tetel_dev_br'    ][$fej_id][$i] = $tetel['dev_br'        ];
                $_SESSION['pr_szla_tetel_afakod'    ][$fej_id][$i] = $tetel['afakod_recid'  ];
                $_SESSION['pr_szla_tetel_ktgnem'    ][$fej_id][$i] = $tetel['ktgnem_id'     ];
                $_SESSION['pr_szla_tetel_valtozott' ][$fej_id][$i] = $tetel['valtozott'     ];
                $_SESSION['pr_szla_tetel_andocos'   ][$fej_id][$i] = $tetel['andocos'       ];

                $i++;
            }

            // Új számla rögzítésekor így ellenőrizzük a nettó és tételek összegének azonosságát
            $js_submit_elott = nl2('
                var tetelek_ossz    = Number($(\'#'.$mezo1.'\').val().replace(/ /g, \'\').replace(/,/g, \'.\'));
                var netto_ossz      = Number($(\'#'.$mezo2.'\').val().replace(/ /g, \'\').replace(/,/g, \'.\'));
                var tetelek_ossz_br = Number($(\'#'.$mezo3.'\').val().replace(/ /g, \'\').replace(/,/g, \'.\'));
                var brutto_ossz     = Number($(\'#'.$mezo4.'\').val().replace(/ /g, \'\').replace(/,/g, \'.\'));
                if( $(\'#nullas\').is(\':checked\') == false ) {
                    if( Math.abs( Number(tetelek_ossz)-Number(netto_ossz) ) > '.$kerekit.' ) {
                        alert2(\'A számla tételeinek összege (\'+tetelek_ossz+\' \'+$(\'#kcs_21_autocomp\').val()+\') nem egyezik meg a számla nettó értékével (\'+$(\'#'.$mezo2.'\').val()+\' \'+$(\'#kcs_21_autocomp\').val()+\')!<br>Így nem menthető!\', \'\', \'Hiba\');
                        return false;
                    }
                    if( Math.abs( Number(tetelek_ossz_br)-Number(brutto_ossz) ) > '.$kerekit.' ) {
                        alert2(\'A számla tételeinek összege (\'+tetelek_ossz_br+\' \'+$(\'#kcs_21_autocomp\').val()+\') nem egyezik meg a számla nettó értékével (\'+$(\'#'.$mezo4.'\').val()+\' \'+$(\'#kcs_21_autocomp\').val()+\')!<br>Így nem menthető!\', \'\', \'Hiba\');
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
                <script>var tetelek_szama_elter = 0;</script>
                <div class="form_kuldes">
                    <input type="hidden" name="atmeneti_recid" value="'.$szamla['recid'].'">
                    <input type="hidden" id="ossz_netto" value="">
                    <input type="hidden" id="ossz_dev_net" value="">
                    <input type="hidden" id="ossz_brutto" value="">
                    <input type="hidden" id="ossz_dev_br" value="">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
                </div>
            ');

            // A projekt számlák új feltöltésének mintájára is készült egy tételek kezelése gomb, de ide az eredetileg is használt változatra van szükség új felvitelben is
            $gombok .= '<input type="button" class="gomb" value="Tételek kezelése" onclick="if( $(\'#kcs_21\').val()!=\''.KCS_21_HUF.'\' && ( $(\'#arfolyam\').val()==\'\' || $(\'#arfolyam\').val()==\'0,0000\' ) ) { $(\'#arfolyam\').addClass(\'hibas_ide_focus\'); alert2(\'Tételek kezeléséhez kérem adja meg az Árfolyamot!\', \'\', \'Hiba\'); } else { csoportMuvelet(\'projektszamla_tetelek_kezelese_modosit\'); }"> ';
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
        }

    // Módosítás gombok
    } else if( get_projekt_szamla_kezel_jog($_GET['id']) ) {

        // Módosításkor updateljük a Session-ben a tételek adatait, így lesz jó a popupban
        $i = 0;
        $fej_id = $_GET['id'];
        $_SESSION['pr_szla_tetel_recid'         ][$fej_id]  =
        $_SESSION['pr_szla_tetel_jogcim'        ][$fej_id]  = 
        $_SESSION['pr_szla_tetel_fokonyvi'      ][$fej_id]  =
        $_SESSION['pr_szla_tetel_ktg_hely'      ][$fej_id]  =
        $_SESSION['pr_szla_tetel_targy'         ][$fej_id]  = 
        $_SESSION['pr_szla_tetel_netto_ert'     ][$fej_id]  = 
        $_SESSION['pr_szla_tetel_brutto_ert'    ][$fej_id]  = 
        $_SESSION['pr_szla_tetel_dev_net'       ][$fej_id]  = 
        $_SESSION['pr_szla_tetel_dev_br'        ][$fej_id]  = 
        $_SESSION['pr_szla_tetel_afakod'        ][$fej_id]  = 
        $_SESSION['pr_szla_tetel_ktgnem'        ][$fej_id]  = 
        $_SESSION['pr_szla_tetel_valtozott'     ][$fej_id]  = 
        $_SESSION['pr_szla_tetel_andocos'       ][$fej_id]  = 
        $_SESSION['pr_szla_tetel_pr_tetel'      ][$fej_id]  = 
        $_SESSION['pr_szla_tetel_kifizetes'     ][$fej_id]  = 
                                                               array();
        for( $j = 0; $j < 2; $j++ ) {
            // Alapvetően a számla tételeit írjuk ki
            $tetelek_tabla = 'projektszamla_tetel';
            // De ha eltérő tétel soraink vannak, akkor már a küldött tételeket mutassuk
            if( $sor['tetel_elter'] ) {
                $tetelek_tabla = 'projektszamla_konyveles';
            }
            $recid_kieg = '';
            // Ha a 2. ciklusban vagyunk, akkor viszont a kifizetésben kapott tételeket kell lekérni
            if( $j ) {
                $tetelek_tabla = 'projektszamla_tetel_kifizetes';
                $recid_kieg    = '_kifizetes';
                // Ha nincsenek eltérő sorok, akkor a 2. ciklust ne járjuk be, ekkor nem kell megmutatni az esetleges kifizetéses tétel sorokat
                if( !$sor['tetel_elter'] ) {
                    continue;
                }
            }
            $tetelek = $adatbazis_kapcsolat->s('*', $tetelek_tabla, "`projektszamla_recid`='{$_GET['id']}'", 'recid');
            $van_kapcsolt_tetel = 0;
            foreach ( $tetelek as $tetel ) {
                $_SESSION['pr_szla_tetel_recid'         ][$fej_id][$i] = $tetel['recid'         ].$recid_kieg;
                $_SESSION['pr_szla_tetel_jogcim'        ][$fej_id][$i] = $tetel['jogcim_recid'  ];
                $_SESSION['pr_szla_tetel_fokonyvi'      ][$fej_id][$i] = $tetel['ktgnem_id'     ];
                $_SESSION['pr_szla_tetel_ktg_hely'      ][$fej_id][$i] = $tetel['ktghely_id'    ];
                $_SESSION['pr_szla_tetel_targy'         ][$fej_id][$i] = $tetel['targy'         ];
                $_SESSION['pr_szla_tetel_netto_ert'     ][$fej_id][$i] = $tetel['netto_ert'     ];
                $_SESSION['pr_szla_tetel_brutto_ert'    ][$fej_id][$i] = $tetel['brutto_ert'    ];
                $_SESSION['pr_szla_tetel_dev_net'       ][$fej_id][$i] = $tetel['dev_net'       ];
                $_SESSION['pr_szla_tetel_dev_br'        ][$fej_id][$i] = $tetel['dev_br'        ];
                $_SESSION['pr_szla_tetel_afakod'        ][$fej_id][$i] = $tetel['afakod_recid'  ];
                $_SESSION['pr_szla_tetel_ktgnem'        ][$fej_id][$i] = $tetel['ktgnem_id'     ];
                $_SESSION['pr_szla_tetel_valtozott'     ][$fej_id][$i] = $tetel['valtozott'     ];
                $_SESSION['pr_szla_tetel_andocos'       ][$fej_id][$i] = $tetel['andocos'       ];
                $_SESSION['pr_szla_tetel_pr_tetel'      ][$fej_id][$i] = $tetel['projektszerzodes_tetel_recid'];
                $_SESSION['pr_szla_tetel_kifizetes'     ][$fej_id][$i] = $recid_kieg;
                if( $tetel['projektszerzodes_tetel_recid'] ){
                    $van_kapcsolt_tetel = 1;
                }
        
                $i++;
            }
        }
        
        // Törlés gomb
        if( !get_projekt_szamla_kezel_jog($_GET['id']) ) {
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Önnek nincs joga a projekt számla törléséhez!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        } else {
            
            // Ha van már olyan tétele, ami össze van kötve szerződéssel, akkor nem engedünk törölni
            if( $van_kapcsolt_tetel ){
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'A számlának van olyan tétele, amihez már van szerződés tétel kapcsolva, így nem törölhető a számla!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
            } else {
                $gombok .= '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
            }
        }

        if( $sor['feldolgozva_dt'] != '0000-00-00 00:00:00' ) {
            // Ha még nincs igazolva, akkor előbb "előigazolunk", és majd csak ez után lehet utóigazolni
            if( $sor['elo_igazolva_dt']=='0000-00-00 00:00:00' ) {

                // Igazolás joggal lehet csak igazolni: jog + felhasználó = számla projektjének projektfelelőse
                if( _jog(JOG_PRSZAMLAK_IGAZOL) && $sor['let_fel'] == $_SESSION[get_db_tabla().'login_recid'] ) {
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt zold" value="Igazolás" onclick="szamla_igazol()"> ';
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt piros" value="Elutasítás" onclick="csoportMuvelet(\'projektSzamlaElutasit\');"> ';
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Vissza üi.-nek" onclick="csoportMuvelet(\'projektSzamlaVisszaUi\');"> ';
                }
            
            } 
            
            // Ha már előigazolva van, de nincs még utóigazolva
            if( $sor['elo_igazolva'] && $sor['igazolva_dt']=='0000-00-00 00:00:00' ) {
                if( _jog(JOG_PRSZAMLAK_UTOIGAZOL) ) {
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt zold" value="Utóigazolás" onclick="szamla_utoigazol()"> ';
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt piros" value="Elutasítás" onclick="csoportMuvelet(\'projektSzamlaElutasit\');"> ';
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Vissza üi.-nek" onclick="csoportMuvelet(\'projektSzamlaVisszaUi\');"> ';
                    $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Vissza igazolónak" onclick="csoportMuvelet(\'projektSzamlaVisszaIgazolonak\')"> ';
                }
            }
        }
        
// Saját IP-ről belépve a személyzeten módosítunk, hogy a Könyvelés tételek kezelése tesztelhető legyen
if( $_SERVER['REMOTE_ADDR'] == get_debug_ip() ) {
    $_SESSION[get_db_tabla().'szemelyzet_id'] = MUSZAKI_ADMINISZTRATOR;
}

        // Ez kell a popuphoz (azért nem lentebb van a kódban mint a többi számla modulban, mert ott még vannak további feltételek, és ez minden feltételtől függetleünk a kódban kell hogy legyen)
        if( $sor['tetel_elter'] ) {
            $gombok .= '<script>var tetelek_szama_elter = 1;</script>';
        } else {
            $gombok .= nl2('
                <script>var tetelek_szama_elter = 0;</script>
                ');
        }

        // Tételek kezelése, és a számla nincs még visszaadva
        if( get_projekt_szamla_kezel_jog($_GET['id']) && ( (!$sor['visszaadva'] && $sor['feldolgozva_dt']=='0000-00-00 00:00:00') || ($sor['tetel_elter'] && $_SESSION[get_db_tabla().'szemelyzet_id'] == MUSZAKI_ADMINISZTRATOR ) ) ) {

            // Ha még nincs feldolgozva
            if( $sor['feldolgozva_dt'] == '0000-00-00 00:00:00' ) {
                $gombok .= '<input type="button" class="gomb" value="Küldés igazolásra" onclick="kuldes_igazolasra()"> ';
            }
            
            $cimke = 'Tételek kezelése';
            if( $sor['tetel_elter'] ) {
                $cimke = 'Könyvelés tételek kezelése';
            }
            $gombok .= ' <input type="button" class="gomb" onclick="if( $(\'#kcs_21\').val()!=\''.KCS_21_HUF.'\' && ( $(\'#arfolyam\').val()==\'\' || $(\'#arfolyam\').val()==\'0,0000\' ) ) { $(\'#arfolyam\').addClass(\'hibas_ide_focus\'); alert2(\'Tételek kezeléséhez kérem adja meg az Árfolyamot!\', \'\', \'Hiba\'); } else { csoportMuvelet(\'projektszamla_tetelek_kezelese_modosit\'); }" value="'.$cimke.'"> ';
            
            // Ha nem az Adatlapon volnánk, akkor szükségünk lesz egy rejtett inputra, ami az adatbázisban levő nettó értéket tartalmazza, hogy bármelyik fül alatt szerkeszteni lehessen a tételeket, összehasonlítva az aktuális számla nettó értékével
            $gombok .= '<input type="hidden" id="netto_db" value="'.$sor['netto'].'">';
            $gombok .= '<input type="hidden" id="brutto_db" value="'.$sor['brutto'].'">';
            $gombok .= '<input type="hidden" id="dev_net_db" value="'.$sor['dev_net'].'">';
            $gombok .= '<input type="hidden" id="dev_br_db" value="'.$sor['dev_br'].'">';
            $gombok .= '<input type="hidden" id="kcs_21_db" value="'.$sor['kcs_21'].'">';
            $gombok .= '<input type="hidden" id="arfolyam_db" value="'.number_format($sor['arfolyam'], $tizedesek, ',', ' ').'">';
            $gombok .= '<input type="hidden" id="sz_szam_db" value="'.$sor['sz_szam'].'">';
            $gombok .= '<input type="hidden" id="pr_szam_db" value="'.$sor['pr_szam'].'">';
            $gombok .= '<input type="hidden" id="telephely_id_db" value="'.$sor['telephely_id'].'">';
            $gombok .= '<input type="hidden" id="kcs_21_autocomp_db" value="'.erteklista(ERTEKLISTA_PENZNEM, $sor['kcs_21']).'">';
            $gombok .= '<input type="hidden" id="nullas_db" value="'.$sor['nullas'].'">';
        }
        
        if( isset($_GET['modosit']) && !$sor['visszaadva'] && get_projekt_szamla_kezel_jog($_GET['id']) ) {
            
            // 2021.09.15 kérték levenni
//            // Küldés PSzK-ba
//            if( !$sor['p_atad_dt'] || $sor['p_atad_dt']=='0000-00-00' ) {
//                $gombok .= ' <input type="button" class="gomb" onclick="$(\'#p_atad_dt\').val(\''.date('Y.m.d').'\').addClass(\'mod\').focus().blur(); " value="Küldés PSzK-ba">';
//            }
            
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
        if( _jog(JOG_PRSZAMLAK_KEZEL) && $sor['utalvanyozva_dt'] != '0000-00-00 00:00:00' && $sor['sztornozva_dt'] == '0000-00-00 00:00:00' ) {
            $gombok .= '<input type="button" class="gomb piros" value="Sztornózás" onclick="csoportMuvelet(\'projektSzamlaSztornoz\');"> ';
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
	if( isset($_GET['uj']) && (_jog(JOG_PRSZAMLAK_KEZEL) || _jog(JOG_PR_SAJAT_SZAMLA_KEZEL)) ) {

        // Az űrlap feldolgozása előtti JS-t tiszta lappal indítjuk     
        /*
        $szamla_html .= nl2('
                        <script>
                            var netto_br_ell        = false;
                            var netto_br_deviza_ell = false;
                            var azonos_szamla_mehet = \'\';
                        </script>
                        ');
        */
        /*
            $js_submit_elott = nl2('

                // Nettó és Bruttó mezők kötelezőek, hacsak nem nullás a számla
                if( !$(\'#nullas\').prop(\'checked\') && ( $(\'#netto\').val()==\'\' || $(\'#netto\').val()==\'0\' ) ) {

                    alert2(\'Kérem adja meg a Nettó értéket!\', \'\', \'Hiba\');
                    alert_utan_mehet_megsem_mutat_beallit();
                    $(\'#netto\').addClass(\'hibas_ide_focus\');
                    return false;
                }
                if( !$(\'#nullas\').prop(\'checked\') && ( $(\'#brutto\').val()==\'\' || $(\'#brutto\').val()==\'0\' ) ) {

                    alert2(\'Kérem adja meg a Bruttó értéket!\', \'\', \'Hiba\');
                    alert_utan_mehet_megsem_mutat_beallit();
                    $(\'#brutto\').addClass(\'hibas_ide_focus\');
                    return false;
                }

                // Árfolyam mező kötelező-e, ha igen, van-e értéke?
                if( $(\'#arfolyam\').hasClass(\'csillag\') && ( !$(\'#arfolyam\').val() || $(\'#arfolyam\').val()==\'0,0000\' ) ) {

                    alert2(\'Kérem adja meg az Árfolyamot!\', \'\', \'Hiba\');
                    alert_utan_mehet_megsem_mutat_beallit();
                    $(\'#dev_net\').addClass(\'hibas_ide_focus\');
                    return false;
                }
                // Deviza nettó mező kötelező-e, ha igen, van-e értéke?
                if( $(\'#dev_net\').hasClass(\'csillag\') && ( !$(\'#dev_net\').val() || $(\'#dev_net\').val()==\'0,00\' ) ) {

                    alert2(\'Kérem adja meg a Deviza nettó értéket!\', \'\', \'Hiba\');
                    alert_utan_mehet_megsem_mutat_beallit();
                    $(\'#dev_net\').addClass(\'hibas_ide_focus\');
                    return false;
                }
                // Deviza bruttó mező kötelező-e, ha igen, van-e értéke?
                if( $(\'#dev_br\').hasClass(\'csillag\') && ( !$(\'#dev_br\').val() || $(\'#dev_br\').val()==\'0,00\' ) ) {

                    alert2(\'Kérem adja meg a Deviza bruttó értéket!\', \'\', \'Hiba\');
                    alert_utan_mehet_megsem_mutat_beallit();
                    $(\'#dev_br\').addClass(\'hibas_ide_focus\');
                    return false;
                }

                // A bruttó és nettó mezőt is ellenőrizzük
                var hibauzi = pr_szamla_netto_br_ell();
                if( hibauzi && !netto_br_ell ) {
                    alert2(hibauzi, \'<input type="button" class="gomb zold" value="Igen" onclick="netto_br_ell=true; mentesGomb(); alert2();"> <input type="button" class="gomb piros" value="Nem" onclick="alert2();">\', \'Hiba\');
                    alert_utan_mehet_megsem_mutat_beallit();
                    return false;
                }
                
                // A bruttó és nettó deviza mezőt is ellenőrizzük
                var hibauzi = pr_szamla_deviza_netto_br_ell();
                if( hibauzi && !netto_br_deviza_ell ) {
                    alert2(hibauzi, \'<input type="button" class="gomb zold" value="Igen" onclick="netto_br_deviza_ell=true; mentesGomb(); alert2();"> <input type="button" class="gomb piros" value="Nem" onclick="alert2();">\', \'Hiba\');
                    alert_utan_mehet_megsem_mutat_beallit();
                    return false;
                }

                var dfrd_pr_mehet       = false;
                var hibauzenet          = \'\';
                dfrd_pr_szszamla = $.Deferred();
                $.ajax({
                    url : \'projektszamla.php\',
                    type: \'POST\',
                    data: {
                        erk_dt          : $(\'#erk_dt\').val(),
                        kelte_dt        : $(\'#kelte_dt\').val(),
                        szla_szam       : $(\'#szla_szam\').val(),
                        telj_dt         : $(\'#telj_dt\').val(),
                        fizhat_dt       : $(\'#fizhat_dt\').val(),
                        visszaadva      : $(\'#visszaadva\').prop(\'checked\'),
                        visszaadva_dt   : $(\'#visszaadva_dt\').val(),
                        telephely_id    : $(\'#telephely_id\').val(),
                        szallito_id     : $(\'#szallito_id\').val(),
                        targy           : $(\'#targy\').val(),
                        kolt_vis        : $(\'#kolt_vis\').val(),
                        kcs_21          : $(\'#kcs_21\').val(),
                        netto           : $(\'#netto\').val(),
                        brutto          : $(\'#brutto\').val(),
                        nullas          : $(\'#nullas\').prop(\'checked\'),
                        dev_net         : $(\'#dev_net\').val(),
                        sz_szam         : $(\'#sz_szam\').val(),
                        pr_szam         : $(\'#pr_szam\').val(),
                        let_fel         : $(\'#let_fel\').val(),
                        kolt_vis        : $(\'#kolt_vis\').val(),
                        azonos_szamla_mehet: azonos_szamla_mehet,
                        van_tetel       : Date.now()
                    },
                    success: function(response){
                        if(response){
                            response = JSON.parse(response);
                            if(response.van_tetel==\'van\'){
                                formModositva = false;
                                $(\'#szerk_form\').attr(\'onsubmit\', \'\');
                                $(\'#szerk_form\').submit();
                                dfrd_pr_mehet = true;
                                return true;
                            } else {
                                dfrd_pr_mehet = false;
                                hibauzenet    = response.hiba;
                                gomb          = response.gomb;
                                title         = response.title;
                                focus         = response.focus;
                                select        = response.select;
                            }
                            dfrd_pr_szszamla.resolve();
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
                $.when(dfrd_pr_szszamla).then(function() {
                    if( !dfrd_pr_mehet ){
                        alert2(hibauzenet, gomb, title);
                        alert_utan_mehet_megsem_mutat_beallit();
                        if( focus ){
                            $(\'#\'+focus).focus();
                        }
                        if( select ){
                            $(\'#\'+select).select();
                        }
                        return false;
                    } else {
                        urlap_mehet = true;
                    }
                    
                    // Ez a feltétel nem tévedésből van kétszer, kell ide is, meg egyel lentebb is
                    if(!urlap_mehet){
                        return false;
                    } else {
                        return form_ell_admin();
                    }
                });
                
                
                // Ez a feltétel nem tévedésből van kétszer, kell ide is, meg egyel fentebb is
                if(!urlap_mehet){
                    return false;
                } else {
                    return form_ell_admin();
                }
                ');
        $navi_sablon  = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit     = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel   = array( $navi,             '',         $gyk2 );
        $szamla_html .= str_replace($navi_mit, $navi_mivel, $navi_sablon);
        */

        // Itt úgy fogunk tenni, mintha újként szerkesztenénk, viszont nem a session-ból keresünk ide értékeket
        // hanem a kapott andocid alapján az átmeneti táblából. Magyarul egy sima lekérdezéssel meg is van a $sor tömb.
        $sor = $adatbazis_kapcsolat->s2('*', 'projektszamla_atmeneti', "`andoc_id`='{$_GET['andoc_id']}'");
	    
        $js = js_kiir($akt_tomb, 'form_ell_admin()');

	    $form_sablon = fajlbol_olvas('../sablon/admin/form_uj.html', true);

        // Javascript-es ellenőrzéshez
        $tabla = str_replace('.php', '', basename(self()));

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

        /*
        // Ha van PPM1 import információnk, akkor azokkal az adatokkal töltsük fel az űrlapot
        if( $_SESSION['projekt_szamla_import'] ) {
            $post_adatok = '';
            $_SESSION['projekt_szamla_import'] = '';
            foreach( $akt_tomb as $ertek ) {
                $sor[$ertek['name']] = trim($_SESSION[$ertek['name']]);
                if( strpos($ertek['class'], 'datepicker')!==false ) {
                    $sor[$ertek['name']] = str_replace('.', '-', $sor[$ertek['name']]);
                }
                $post_adatok .= '[mezo]'.$ertek['name'].'[/mezo][ertek]'.htmlspecialchars($sor[$ertek['name']]).'[/ertek]'."\n";
                unset( $_SESSION[$ertek['name']] );
            }
            $sor['post_data'] = $post_adatok;
            // Futtassuk le a deviza input elhagyást, mert ez alapján dől el, hogy a többi mező disabled-e vagy sem
            // Illetve új feltöltéskor startból a 0-ás számla még biztos nincs kipipálva, ezért a kötelező mező class-t ráadjuk a nettó és bruttó mezőkre.
            $szamla_html .= nl2('
                <script>
                    $(document).ready(function () {
                        $(\'#kcs_21_autocomp\').focus().blur();
                    });
                </script>
                ');
        }
        
        // Ha nincs bepipála a 0-ás számla, akkor a nettó és bruttó mezők kötelezőek
        $szamla_html .= nl2('
            <script>
                $(document).ready(function () {
                    // Ha nincs bepipálva a nullás pipa, akkor a nettó és bruttó mezők kötelezőek
                    if( !$(\'#nullas\').prop(\'checked\') ) {
                        $(\'#netto\').addClass(\'csillag\');
                        $(\'#brutto\').addClass(\'csillag\');
                    }
                });
            </script>
            ');
        */
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

	// Projektszámla módosítása
    } elseif( isset($_GET['modosit']) ) {

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if( isset($_GET['id_hidden']) ) {
            $_GET['id'] = $_GET['id_hidden'];
        }
        
        // Ha épp egy sikeres feltöltés után vagyunk, akkor töröljük a Cookie-t
        if( isset($_GET['siker']) ) {
            $szamla_html .= nl2('
                            <script>
                                eraseCookie(\'uj_felvitel_\'+baseName(location));
                            </script>
                        ');
        }
        
        // Ha a visszaadva pipa be van pipálva, és van rajta egy mod css class, akkor ez most kerül bepipálásra. Ebben az esetben rákérdezdünk, hogy biztosan?
        $szamla_html .= nl2('
                        <script>
                            var visszaadva_pipa_mehet   = false;
                            var nullas_pipa_mehet       = false;
                            var netto_br_ell            = false;
                            var netto_br_deviza_ell     = false;
                        </script>
                        ');
        
        // A nullás megerősítő kérdéset csak akkor tesszük fel, ha nem a nullás tétele van adott számlának
        if( !$adatbazis_kapcsolat->s('recid', 'projektszamla_tetel', "`projektszamla_recid`='{$_GET['id']}' AND `netto_ert`='0' AND `dev_net`='0.00'") ) {
            $nullas_megerosites = nl2('
                /* Nullás számla megerősítő kérdés */
                if( !nullas_pipa_mehet && $(\'#nullas\').prop(\'checked\') ) {
                    alert2( \' 0 értékű számlát készül feldolgozni. Az eddigi tételek ki fognak ürülni.\'+
                            \'<br>Folytatja?\',
                            \'<input type="button" class="gomb zold" value="Igen" onclick="nullas_pipa_mehet = true; mentesGomb();"><input type="button" class="gomb piros" value="Nem" onclick="alert2();">\');
                            alert_utan_mehet_megsem_mutat_beallit();
                            return false;
                }
                ');
        }
        
        $js_submit_elott = nl2('
            
            /* Nettó és Bruttó mezők kötelezőek, hacsak nem nullás a számla */
            if( !$(\'#nullas\').prop(\'checked\') && ( $(\'#netto\').val()==\'\' || $(\'#netto\').val()==\'0\' ) ) {

                alert2(\'Kérem adja meg a Nettó értéket!\', \'\', \'Hiba\');
                alert_utan_mehet_megsem_mutat_beallit();
                $(\'#netto\').addClass(\'hibas_ide_focus\');
                return false;
            }
            if( !$(\'#nullas\').prop(\'checked\') && ( $(\'#brutto\').val()==\'\' || $(\'#brutto\').val()==\'0\' ) ) {

                alert2(\'Kérem adja meg a Bruttó értéket!\', \'\', \'Hiba\');
                alert_utan_mehet_megsem_mutat_beallit();
                $(\'#brutto\').addClass(\'hibas_ide_focus\');
                return false;
            }
            
            /* Árfolyam mező kötelező-e, ha igen, van-e értéke? */
            if( $(\'#arfolyam\').hasClass(\'csillag\') && ( !$(\'#arfolyam\').val() || $(\'#arfolyam\').val()==\'0,0000\' ) ) {

                alert2(\'Kérem adja meg az Árfolyamot!\', \'\', \'Hiba\');
                alert_utan_mehet_megsem_mutat_beallit();
                $(\'#dev_net\').addClass(\'hibas_ide_focus\');
                return false;
            }
            /* Deviza nettó mező kötelező-e, ha igen, van-e értéke? */
            if( $(\'#dev_net\').hasClass(\'csillag\') && ( !$(\'#dev_net\').val() || $(\'#dev_net\').val()==\'0,00\' ) ) {

                alert2(\'Kérem adja meg a Deviza nettó értéket!\', \'\', \'Hiba\');
                alert_utan_mehet_megsem_mutat_beallit();
                $(\'#dev_net\').addClass(\'hibas_ide_focus\');
                return false;
            }
            /* Deviza bruttó mező kötelező-e, ha igen, van-e értéke? */
            if( $(\'#dev_br\').hasClass(\'csillag\') && ( !$(\'#dev_br\').val() || $(\'#dev_br\').val()==\'0,00\' ) ) {

                alert2(\'Kérem adja meg a Deviza bruttó értéket!\', \'\', \'Hiba\');
                alert_utan_mehet_megsem_mutat_beallit();
                $(\'#dev_br\').addClass(\'hibas_ide_focus\');
                return false;
            }

            /* A bruttó és nettó mezőt is ellenőrizzük */
            var hibauzi = pr_szamla_netto_br_ell();
            if( hibauzi && !netto_br_ell ) {
                alert2(hibauzi, \'<input type="button" class="gomb zold" value="Igen" onclick="netto_br_ell=true; alert2(); mentesGomb();"> <input type="button" class="gomb piros" value="Nem" onclick="alert2();">\', \'Hiba\');
                alert_utan_mehet_megsem_mutat_beallit();
                return false;
            }
            
            /* A bruttó és nettó deviza mezőt is ellenőrizzük */
            var hibauzi = pr_szamla_deviza_netto_br_ell();
            if( hibauzi && !netto_br_deviza_ell ) {
                alert2(hibauzi, \'<input type="button" class="gomb zold" value="Igen" onclick="netto_br_deviza_ell=true; alert2(); mentesGomb();"> <input type="button" class="gomb piros" value="Nem" onclick="alert2();">\', \'Hiba\');
                alert_utan_mehet_megsem_mutat_beallit();
                return false;
            }

            /* Visszaadva pipa ellenőrzése */
            if( !visszaadva_pipa_mehet && $(\'#visszaadva\').prop(\'checked\') && $(\'#visszaadva\').hasClass(\'mod\') ) {
                
                if( !$(\'#visszaadva_dt\').val() || $(\'#visszaadva_dt\').val()==\'\xa0\xa0\xa0\xa0.\xa0\xa0.\xa0\xa0\' ) {
                    alert2(\'Kérem adja meg a visszaadás dátumát!\', \'\', \'Hiba\');
                    $(\'#visszaadva_dt\').addClass(\'hibas_ide_focus\');
                    return false;
                }
                alert2( \'Szállító: \'+$(\'#szallito_id_autocomp\').val()+
                        \'<br>Számlaszám: \'+$(\'#szla_szam\').val()+
                        \'<br>Tárgy: \'+$(\'#targy\').val()+
                        \'<br><br>A számla visszaadására készül. Amennyiben folytatja, a számla a továbbiakban nem módosíthatóvá válik.\'+
                        \'<br>Ha a számla szerződéshez van kapcsolva, akkor adatai a szerződés tételeiből kiürülnek.\'+
                        \'<br>Folytatja?\', \'<input type="button" class="gomb zold" value="Igen" onclick="visszaadva_pipa_mehet = true; mentesGomb();"><input type="button" class="gomb piros" value="Nem" onclick="alert2();">\');
                        alert_utan_mehet_megsem_mutat_beallit();
                        return false;
            }

            '.$nullas_megerosites.'

            return form_ell_admin();
            ');

	    // Fentebb már lekértük $sor        = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");
        $telephely = $adatbazis_kapcsolat -> s2('nev modell', 'telephely', "`id`='{$sor['telephely_id']}'");
        // Fizetési módokat szövegesen írjuk ki (amúgy szem szerkeszthető az adatlap)
        switch ( strtolower($sor['fizmod']) ) {
            case 'u': $sor['fizmod'] = 'Utalás';    break;
            case 'k': $sor['fizmod'] = 'Készpénz';  break;
        }
        
        foreach ( $akt_tomb as $tomb ) {
            if( $tomb['type'] == 'autocomplete' ) {
                
                if( $tomb['csoport'] ) {
                    $kulso_ertek = $adatbazis_kapcsolat->s2('info', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}' AND `csoport`='{$tomb['csoport']}'");
                    $kulso_ertek['nev'] = $kulso_ertek['info'];
                // Létesítményfelelősnél a teljes nevet írjuk ki, nem a nev oszlopot
                }elseif( $tomb['name'] == 'let_fel' ) {
                    $kulso_ertek = $adatbazis_kapcsolat->s2('recid teljes_nev', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}'");
                    $kulso_ertek['nev'] = $kulso_ertek['teljes_nev'];
                } else {
                    $kulso_ertek = $adatbazis_kapcsolat->s2('recid nev', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}'");
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
        // Futtassuk le a deviza input elhagyást, mert ez alapján dől el, hogy a többi mező disabled-e vagy sem
        // Illetve a 0-ás számla pipa lekezelését is itt intézzük el, hogy minden lapletölsékor lefusson (ezért nem a projektszámla.js-ben van)
        $szamla_html .= nl2('
            <script>
                $(document).ready(function () {
                    $(\'#kcs_21_autocomp\').focus();
                    $(\'#szallito_id_autocomp\').focus();
                    
                    /* Ha nincs bepipálva a nullás pipa, akkor a nettó és bruttó mezők kötelezőek */
                    if( !$(\'#nullas\').prop(\'checked\') ) {
                        $(\'#netto\').addClass(\'csillag\');
                        $(\'#brutto\').addClass(\'csillag\');
                    }
                });
            </script>
            ');
        
        // Duplikáláskor feltett kérdés
        if( !get_projekt_szamla_kezel_jog($_GET['id']) ){
            $szamla_html .= nl2('
                <script>
                    duplikalGomb  = "<p class=\"c\"><input class=\"gomb zold\" type=\"button\" value=\"Ok\" onclick=\"alert2()\"></p>";
                    duplikaKerdes = \'Önnek nincs joga új projektszámlát létrehozni!\';
                </script>
            ');
        } else {
            $szamla_html .= nl2('
                <script>
                    duplikalGomb  = "<p class=\"c\"><input class=\"gomb zold\" type=\"button\" value=\"Igen\" onclick=\"szurkit(); location.href = \''.basename(self()).'?uj&ujkent='.$_GET['id'].'\'; \"><input class=\"gomb piros\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
                    duplikaKerdes = \'Számlaszám: '.addcslashes($sor['sz_szam']).'<br>Szálloda: '.addcslashes($telephely['nev']).'<br><br>Biztos, hogy duplikálja?\';
                </script>
            ');
        }
        
	    // FORM
	    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

		// Form actionja
		$action = self().get_get(array('errnum','kephiba','siker'));

		// Ez elkészíti a $mezok változót
		require('switch.php');

        // Egyik sor sincs kijelölve (mert nincs találat, pl. egy szűrés miatt), akkor a létre hozta feliratokat tüntessük még el
        $szamla_html .= nl2('
                <script>
                $(document).ready(function () {
                    setTimeout(function(){
                        if( !$(\'.szamla_sor.kijelolve\').length ){
                            $(\'#sz_szam_gomb\').hide(\'\');
                            $(\'#szerzodes_neve\').hide(\'\');
                            $(\'#projekt_neve\').hide(\'\');
                        }
                    }, 500);
                    
                });
                </script>
                ');
        
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
                    <div class="dtc">Tárgy</div>
                    <div class="dtc">Főkönyvi számlaszám</div>
                    <div class="dtc r">Nettó érték (HUF)</div>
                    <div class="dtc r">Bruttó érték (HUF)</div>
                    <div class="dtc r">Áfa (%)</div>
                    <div class="dtc r">Deviza nettó'.$deviza.'</div>
                    <div class="dtc r">Deviza bruttó'.$deviza.'</div>
                    <div class="dtc">Szerződés tétel</div>
                </div>
        ');
        
        // Tételek felsorolása
        $tetelek = $adatbazis_kapcsolat->s('*', 'projektszamla_tetel', "`projektszamla_recid`='{$_GET['id']}'", 'recid');
        foreach ( $tetelek as $tetel ) {

            $pr_tetel = $adatbazis_kapcsolat->s2('targy', 'projektszerzodes_tetel', "`recid`='{$tetel['projektszerzodes_tetel_recid']}'");
            
            $szamla_html .= nl2('
                <div class="dtr nSor">
                    <div class="dtc">'.htmlspecialchars_decode($tetel['targy']).'</div>
                    <div class="dtc">'.htmlspecialchars_decode($tetel['megn']).'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['netto_ert'], 0, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['brutto_ert'], 0, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.$tetel['afa'].'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['dev_net'], 2, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['dev_br'], 2, ',', ' ').'</div>
                    <div class="dtc">'.htmlspecialchars_decode($pr_tetel['targy']).'</div>
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
        $tetelek = $adatbazis_kapcsolat->s('*', 'projektszamla_konyveles', "`projektszamla_recid`='{$_GET['id']}'", 'recid');
        $szamla  = $adatbazis_kapcsolat->s2('kcs_21', 'projektszamla', "`recid`='{$_GET['id']}'");
        foreach ( $tetelek as $tetel ) {
            
            $jogcim  = $adatbazis_kapcsolat->s2('jogcim', 'jogcim',   "`recid`='{$tetel['jogcim_recid']}'");
            $afakod  = $adatbazis_kapcsolat->s2('afa',    'afakod',   "`recid`='{$tetel['afakod_recid']}'");
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
/*
	// Kifizetések fül
    } elseif( isset($_GET['kifizetesek']) ) {

        $szamla_html .= nl2('
            <div class="dt naplo">
                <div class="dtr nFejlec">
                    <div class="dtc">Bank</div>
                    <div class="dtc">Kifizetve</div>
                    <div class="dtc r">Bruttó</div>
                    <div class="dtc r">Deviza bruttó</div>
                    <div class="dtc">Pénznem</div>
                    <div class="dtc">Rögzítve</div>
                    <div class="dtc">Rögzítette</div>
                </div>
        ');

        // Tételek felsorolása
        $kifizetesek = $adatbazis_kapcsolat->s('*', 'projektszamla_kifizetesek', "`projektszamla_recid`='{$_GET['id']}'", 'insert_dt');
        foreach( $kifizetesek as $kifizetes ) {
            
            $szamla_html .= nl2('
                <div class="dtr nSor">
                    <div class="dtc">'.erteklista(ERTEKLISTA_BANK, $kifizetes['bank']).'</div>
                    <div class="dtc">'.datum_kiir($kifizetes['kifizetes_dt']).'</div>
                    <div class="dtc r">'.number_format($kifizetes['br_ertek'], 0, '', ' ').'</div>
                    <div class="dtc r">'.number_format($kifizetes['dev_br'], 2, ',', ' ').'</div>
                    <div class="dtc">'.erteklista(ERTEKLISTA_PENZNEM, $kifizetes['kcs_21']).'</div>
                    <div class="dtc">'.datum_kiir($kifizetes['insert_dt'], 'csak dátum').'</div>
                    <div class="dtc">'.$kifizetes['insert_usr'].'</div>
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
*/
	// Történet
    } elseif( isset($_GET['tortenet']) ) {

        $eloigazolva  = $eloigazolva_dt  = $eloigazolva_usr  = $eloigazol_elut    =
        $utoigazolva  = $utoigazolva_dt  = $utoigazolva_usr  = $utoigazol_elut    = 
        $utalvanyozva = $utalvanyozva_dt = $utalvanyozva_usr = $utalvanyozva_elut = 
        $sztornozva   = $sztornozva_dt   = $sztornozva_usr   = $sztornozva_leir   = '';

        // 2021.09.14 Tamás kérte, hogy ne legyen piros X ha még nincs feldolgozva, csak pipa, ha már megvan
        // $feldolgozva = '<b class="x">✖</b>';
        $feldolgozva = '';
        if( $sor['feldolgozva_dt'] != '0000-00-00 00:00:00' ) {
            $feldolgozva_usr = $sor['feldolgozva_usr'];
            $feldolgozva_dt  = $sor['feldolgozva_dt'];
            $feldolgozva     = '<b class="pipa">✓</b>';
        }
        
        // (Elő)igazolva
        if( $sor['elo_igazolva_dt']!='0000-00-00 00:00:00' ) {
            $eloigazolva_usr = $sor['elo_igazolva_usr'];
            $eloigazolva_dt  = $sor['elo_igazolva_dt'];
            if( $sor['elo_igazolva'] ) {
                $eloigazolva = '<b class="pipa">✓</b>';
            }
            if( !$sor['elo_igazolva'] ) {
                $eloigazolva    = '<b class="x">✖</b>';
                $eloigazol_elut = nl2('
                    </div>
                    <div class="indoklas">
                        Elutasítás indoklása:
                        <br>'.htmlspecialchars_decode( nl2br2($sor['elutleir']) ).'
                    </div>
                    <div class="dt naplo">
                    ');
            }
        }
        // (Utó)igazolva
        if( $sor['igazolva_dt']!='0000-00-00 00:00:00' ) {
            $utoigazolva_usr = $sor['igazolva_usr'];
            $utoigazolva_dt  = $sor['igazolva_dt'];
            if( $sor['igazolva'] ) {
                $utoigazolva = '<b class="pipa">✓</b>';
            }
            if( !$sor['igazolva'] ) {
                $utoigazolva = '<b class="x">✖</b>';
                $utoigazol_elut = nl2('
                    </div>
                    <div class="indoklas">
                        Elutasítás indoklása:
                        <br>'.htmlspecialchars_decode( nl2br2($sor['elutleir']) ).'
                    </div>
                    <div class="dt naplo">
                    ');
            }
        }
        
        // Utalványozva
        if( $sor['utalvanyozva_dt']!='0000-00-00 00:00:00' ) {
            
            $utalvanyozva_usr = $sor['utalvanyozva_usr'];
            $utalvanyozva_dt  = $sor['utalvanyozva_dt'];
            
            if( $sor['utalvanyozva'] ) {
                $utalvanyozva = '<b class="pipa">✓</b>';
            }
            if( !$sor['utalvanyozva'] ) {
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
        }
        
        // Sztornózva
        if( $sor['sztornozva_dt'] != '0000-00-00 00:00:00' ) {

            $sztornozva_usr = $sor['sztornozva_usr'];
            $sztornozva_dt  = $sor['sztornozva_dt'];
            $sztornozva     = '<b class="x">✖</b>';
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
        
        $szamla_html .= nl2('
            <div class="dt naplo tortenet">
                <div class="dtr nFejlec">
                    <div class="dtc">Esemény</div>
                    <div class="dtc pipa"><b class="pipa">✓</b></div>
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
                    <div class="dtc">'.datum_kiir($feldolgozva_dt).'</div>
                </div>
                <div class="dtr nSor">
                    <div class="dtc">Igazolva</div>
                    <div class="dtc">'.$eloigazolva.'</div>
                    <div class="dtc">'.$eloigazolva_usr.'</div>
                    <div class="dtc">'.datum_kiir($eloigazolva_dt).'</div>
                </div>
                '.$eloigazol_elut.'
                    
                <div class="dtr nSor">
                    <div class="dtc">Utóigazolva</div>
                    <div class="dtc">'.$utoigazolva.'</div>
                    <div class="dtc">'.$utoigazolva_usr.'</div>
                    <div class="dtc">'.datum_kiir($utoigazolva_dt).'</div>
                </div>
                '.$utoigazol_elut.'
                    
                <div class="dtr nSor">
                    <div class="dtc">Utalványozva</div>
                    <div class="dtc">'.$utalvanyozva.'</div>
                    <div class="dtc">'.$utalvanyozva_usr.'</div>
                    <div class="dtc">'.datum_kiir($utalvanyozva_dt).'</div>
                </div>
                '.$utalvanyozva_elut.'
                    
                <div class="dtr nSor">
                    <div class="dtc">Sztornózva</div>
                    <div class="dtc">'.$sztornozva.'</div>
                    <div class="dtc">'.$sztornozva_usr.'</div>
                    <div class="dtc">'.datum_kiir($sztornozva_dt).'</div>
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

	// Dokumentumok
    } elseif( isset($_GET['dokumentumok']) ) {

        if( $tobb_kep ) {
            require_once('tobb_kep.php');
        }
        // Itt az XML-ből kapott dokumentum linkjét jelenítjük meg
        if( $sor['dok_link'] ) {
            $szamla_html .= nl2('
                <div style="margin-top: 2rem;"></div>
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
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
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
            echo $szamla_html.$gombok;
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
    
    $szamla = $adatbazis_kapcsolat->s2('ikt_szam targy igazolva andoc_id telephely_id kcs_21 sz_szam pr_szam kuld_tetel_xml kuld_xml kolt_vis igazolva_usr', 'projektszamla', "`recid`='{$szamla_id}'");
    if( $szamla === false ) { $db_error = true; }
    
    // Cégkód konverzió
    $erteklista = $adatbazis_kapcsolat->s2('cegkod', 'erteklista', "`csoport`='".ERTEKLISTA_KOLTSEGVISELO."' AND `id`='{$szamla['kolt_vis']}'");
    if( $erteklista === false ) { $db_error = true; }
    
    switch( $erteklista['cegkod'] ) {
        case SZAMLA_KOLT_VIS_DHZRT : $fokonyvi_szla_szam = '11-4301-4460000-0000-00-00000-000000'; break;
        case SZAMLA_KOLT_VIS_DZRT  : $fokonyvi_szla_szam = '13-4301-4460000-0000-00-00000-000000'; break;
        case SZAMLA_KOLT_VIS_GUNDEL: $fokonyvi_szla_szam = '15-5411-4460000-0000-00-00000-000000'; break;
        default: $fokonyvi_szla_szam = ''; break;
    }
    // XML dekódolása
    $szamla['kuld_xml']         = htmlspecialchars_decode($szamla['kuld_xml']);
    $szamla['kuld_tetel_xml']   = htmlspecialchars_decode($szamla['kuld_tetel_xml']);
    $szamla['targy']            = htmlspecialchars_decode($szamla['targy']);
    
    $urlap_tipus = '<property name="Rögzítendő űrlap típusa">Műszaki számla</property>';
    $sz_szam = $pr_szam = '';
    // Igazolás elutasítva/jóváhagyva esetén más-más szöveg kerül az XML-be
    if( $igazolva == 'Elutasítva' ) {
        $wf_name = '[AUTO] Számla szétosztás';
        $urlap_tipus = '<property name="Rögzítendő űrlap típusa">Elutasított</property>';
    } else {
        // Projektszámláknál a $wf_name értéke fix értéket kap, kivéve két esetet
        switch ( $szamla['telephely_id'] ) {
            // case '4100' : $wf_name = '4100_Könyvelés'; break;
            case TELEPHELY_GUNDEL   : $wf_name = '5400_Könyvelés'; break;
            default                 : $wf_name = TELEPHELY_SZK.'_Könyvelés';
        }

        // Elutasításkor ezeket nem adjuk át, minden más esetben igen
        $sz_szam = '<property name="Szerződés iktatószáma"><![CDATA['.urlencode($szamla['sz_szam']).']]></property>';
        $pr_szam = '<property name="Beruházási projektkód"><![CDATA['.urlencode($szamla['pr_szam']).']]></property>';
        
    }
    
    // A szálloda klaszert Gundel esetén 5400, minden más esetben 1106
    $szalloda_klaszter = $szamla['telephely_id'];
    if( $szalloda_klaszter == TELEPHELY_GUNDEL ) {
        $szalloda_klaszter = TELEPHELY_GUNDEL_MOD;
    } else {
        $szalloda_klaszter = TELEPHELY_SZK;
    }

    $valasz_xml = 'psInputXml=<?xml version="1.0" encoding="utf-8"?>
<xml>
  <data>
    <register registernumber="'.$szamla['andoc_id'].'" operation="update">
      <workflow_node_name>'.urlencode($wf_name).'</workflow_node_name>
      <properties>
        <container name="NIK1">
          <row rownumberonpage="1">
            <property name="Számla főtípus">Beruházási számla</property>
            <property name="Igazoló döntés">'.urlencode($igazolva).'</property>
            <property name="Teljesítés igazolás elutasítás oka"><![CDATA['.urlencode($oka).']]></property>
            '.$sz_szam.'
            '.$pr_szam.'
            <property name="Szálloda - Klaszter">'.$szalloda_klaszter.'</property>
            <property name="Számla tárgya"><![CDATA['.urlencode($szamla['targy']).']]></property>
            <property name="Számla fizetője">'.urlencode($erteklista['cegkod']).'</property>
            <property name="Kötelezettségi főkönyvi számlaszám"><![CDATA['.urlencode($fokonyvi_szla_szam).']]></property>
            <property name="Teljesítés igazoló személye"><![CDATA['.
            // Azért nem a $szamla['igazolva_usr'], hisz épp most zajlik az igazolás/elutasítás, ez az adat még nincs az adatbázisban
            urlencode($_SESSION[get_db_tabla().'teljes_nev'])
            .']]></property>
            </row>
        </container>
        <container name="NIK3">
            <row rownumberonpage="1">
                '.$urlap_tipus.'
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
            if( $_SERVER['REMOTE_ADDR'] != get_debug_ip() ) {
                $valasz = URL_post(ANDOC_POST_KULD_URL_TESZT, $valasz_xml, $fejlec);
            }
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
        addMegj('projektszamla', $szamla_id, 'Az alábbi válasz XML-t nem sikerült elküldeni:<br>'.htmlspecialchars($valasz_xml).'<br><br>'.$extra_hibauzenet);
        $db_error = true;
    // Ezt csak akkor módosítjuk, ha az XML küldése is rendben volt
    } else {
        $xml = $szamla['kuld_xml'].
               date('Y-m-d H:i:s').'
               '.$valasz_xml.'



        ';

        $sql = $adatbazis_kapcsolat->u('projektszamla', array('kuld_xml'),
                                                        array($xml),
                                        "`recid`='{$szamla_id}'");
        if( $tetelek === false ) { $db_error = true; }

        // Tételeknél nullázzuk a változott pipát
        $sql = $adatbazis_kapcsolat->u('projektszamla_tetel', array('valtozott'), array(0), "`projektszamla_recid`='{$szamla_id}' AND `valtozott`='1'");
        if( $sql === false ) { $db_error = true; }
    }
    
    if( $extra_hibauzenet ) {
        return $extra_hibauzenet;
    } else {
        return $db_error;
    }
    
}