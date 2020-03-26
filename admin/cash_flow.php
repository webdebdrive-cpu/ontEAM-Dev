<?php
require_once('../weblap.php');

// Ha nincs jogunk, akkor nem láthatjuk ezt a modult
if( !_jog(JOG_PR_TELJES_CASH_FLOW) ) {
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
$akt_tomb               = $cash_flow_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'cash-flow-t';   // Hibaüzenetkor: Biztosan törlöd a $szoveg?

$csv_mibol   = get_web_charset();
$csv_mibe    = 'windows-1250';

$szamla_html = '';

// Dokumentumok fülhöz kell majd
$tobb_kep = false;
if( $tobb_kep ) {

    function get_tobbkep_darab()    { return 10;        } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '';        } // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;        } // MB-ban
    function get_tobbkep_oszlopnev(){                     // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

}

// Szálloda választó popup
if( isset($_POST['getSzallodak']) ) {

    $html = '';
    $kijelolek = explode(',', $_SESSION['cash_flow_szallodak']);

    $telephelyek = $adatbazis_kapcsolat->s('id nev recid modell', 'telephely', "`inaktiv`='0'", 'nev');
    foreach ( $telephelyek as $telephely ) {
        $checked = '';
        if( in_array($telephely['id'], $kijelolek) ) {
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

// Szállodák kiír
if( isset($_POST['szallodak_ment']) ) {
    
    $_SESSION['cash_flow_szallodak'] = trim($_POST['szallodak'], ',');

    echo json_encode( array('html' => hozzaadott_szallodak_kiir()) );

    $weblap->leallit();
    exit();
}

// Cash-flow elkészítése
if( isset($_POST['cash_flow_elkeszitese']) ) {
    
    // Hibaellenőrzések
    if( !$_POST['ev_tol'] ) {
        echo json_encode(   array(
                                'hiba'  => 'Kérem adja meg a kezdő évet!',
                                'title' => 'Hiba',
                                'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); $(\'#ev_tol\').select();">',
                                'focus' => 'ev_tol',
                            )
                        );
        $weblap -> leallit();
        exit;
    }
    if( $_POST['ev_tol']<2000 || $_POST['ev_tol']>9999 ) {
        echo json_encode(   array(
                                'hiba'  => 'Hibás kezdő év!',
                                'title' => 'Hiba',
                                'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); $(\'#ev_tol\').select();">',
                                'focus' => 'ev_tol',
                            )
                        );
        $weblap -> leallit();
        exit;
    }
    if( !$_POST['ho_tol'] ) {
        echo json_encode(   array(
                                'hiba'  => 'Kérem adja meg a kezdő hónapot!',
                                'title' => 'Hiba',
                                'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); $(\'#ho_tol\').select();">',
                                'focus' => 'ho_tol',
                            )
                        );
        $weblap -> leallit();
        exit;
    }
    if( $_POST['ho_tol']<1 || $_POST['ho_tol']>12 ) {
        echo json_encode(   array(
                                'hiba'  => 'Hibás kezdő hónap!',
                                'title' => 'Hiba',
                                'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); $(\'#ho_tol\').select();">',
                                'focus' => 'ho_tol',
                            )
                        );
        $weblap -> leallit();
        exit;
    }
    if( !$_POST['ev_ig'] ) {
        echo json_encode(   array(
                                'hiba'  => 'Kérem adja meg a záró évet!',
                                'title' => 'Hiba',
                                'focus' => 'ev_ig',
                            )
                        );
        $weblap -> leallit();
        exit;
    }
    if( $_POST['ev_ig']<2000 || $_POST['ev_ig']>9999 ) {
        echo json_encode(   array(
                                'hiba'  => 'Hibás záró év!',
                                'title' => 'Hiba',
                                'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); $(\'#ev_ig\').select();">',
                                'focus' => 'ev_ig',
                            )
                        );
        $weblap -> leallit();
        exit;
    }
    if( !$_POST['ho_ig'] ) {
        echo json_encode(   array(
                                'hiba'  => 'Kérem adja meg a záró hónapot!',
                                'title' => 'Hiba',
                                'focus' => 'ho_ig',
                            )
                        );
        $weblap -> leallit();
        exit;
    }
    if( $_POST['ho_ig']<1 || $_POST['ho_ig']>12 ) {
        echo json_encode(   array(
                                'hiba'  => 'Hibás záró hónap!',
                                'title' => 'Hiba',
                                'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); $(\'#ho_ig\').select();">',
                                'focus' => 'ho_ig',
                            )
                        );
        $weblap -> leallit();
        exit;
    }
    if( !$_SESSION['cash_flow_szallodak'] ) {
        echo json_encode(   array(
                                'hiba'  => 'Kérem adjon hozzá legalább egy szállodát!',
                                'title' => 'Hiba',
                                'focus' => 'szalloda_gomb',
                            )
                        );
        $weblap -> leallit();
        exit;
    }
    
    // Dátum adathelyesség ellenőrzése
    if( $_POST['ev_tol'].'-'.sprintf("%02d", $_POST['ho_tol'])>=$_POST['ev_ig'].'-'.sprintf("%02d", $_POST['ho_ig']) ) {
        echo json_encode(   array(
                                'hiba'  => 'Záró hónap nem lehet korábbi mint a kezdő hónap!',
                                'title' => 'Hiba',
                                'focus' => 'ev_ig',
                            )
                        );
        $weblap -> leallit();
        exit;
    }
    // Eltérő hónapok különbsége
    $honapok_szama = get_honapok_szama($_POST['ev_tol'], $_POST['ho_tol'], $_POST['ev_ig'], $_POST['ho_ig']);
    if( $honapok_szama > CASH_FLOW_MAX_HONAPOK ) {
        echo json_encode(   array(
                                'hiba'  => 'Legfeljebb 5 éves intervallum (60 hónap) adható meg!',
                                'title' => 'Hiba',
                                'focus' => 'ev_ig',
                            )
                        );
        $weblap -> leallit();
        exit;
    }
    
    $_SESSION['cash_flow_ev_tol']   = $_POST['ev_tol'];
    $_SESSION['cash_flow_ho_tol']   = sprintf("%02d", $_POST['ho_tol']);
    $_SESSION['cash_flow_ev_ig']    = $_POST['ev_ig'];
    $_SESSION['cash_flow_ho_ig']    = sprintf("%02d", $_POST['ho_ig']);
    
    $_SESSION['cash_flow_bontas']   = $_POST['bontas'];
    
    if( $_POST['pri_egyutt'] == 'false' ) {
        $_SESSION['cash_flow_pri_egyutt'] = 0;
    }
    if( $_POST['pri_egyutt'] == 'true' ) {
        $_SESSION['cash_flow_pri_egyutt'] = 1;
    }
    // IGEN ez szándékosan van fordítva, mert a bepipáltság az alapértelmezés
    if( $_POST['szetosztas_egyutt'] == 'false' ) {
        $_SESSION['cash_flow_szetosztas_egyutt'] = 1;
    }
    if( $_POST['szetosztas_egyutt'] == 'true' ) {
        $_SESSION['cash_flow_szetosztas_egyutt'] = 0;
    }
    
    echo json_encode(   array(
                            'mehet' => 1,
                        )
                    );
    
    $weblap->leallit();
    exit();
}

// Szerződés tételek kiíratása popup-ba
if( isset($_POST['cash_flow_szerzodesek_mutat']) ) {
    
    $projekt    = $adatbazis_kapcsolat->s2('nev telephely_id', 'projektkezeles', "`ikt_szam`='{$_POST['pr_szam']}'");
    $szalloda   = getSzallodaNev($projekt['telephely_id']);
    
    $html = '';
    $csv = '';
    $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Projekt szám:' )).';';
    $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $_POST['pr_szam'].' '.$projekt['nev'] )).';'."\n";
    
    $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Szálloda:' )).';';
    $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szalloda )).';'."\n";

    
    $szamlazott_tetelek = $adatbazis_kapcsolat->s('`projektszerzodes_recid`, `varh_erk`, `varh_ert`', 'projektszerzodes_tetel', "`teljesites`='0000-00-00' AND `projektszerzodes_recid` IN ( SELECT `recid` FROM `".get_db_tabla()."projektszerzodes` WHERE `pr_szam`='{$_POST['pr_szam']}') AND `varh_erk`>='{$_SESSION['cash_flow_ev_tol']}-{$_SESSION['cash_flow_ho_tol']}-01' AND `varh_erk`<='{$_SESSION['cash_flow_ev_ig']}-{$_SESSION['cash_flow_ho_ig']}-31'", 'varh_erk');
    if( $szamlazott_tetelek ) {
        $html = nl2('
                    <table class="csikos">
                    <tr>
                        <th class="l">Szerződés szám</th>
                        <th class="l">Partner</th>
                        <th class="l">Számla várh. érk.</th>
                        <th class="r">Számla várh. értéke (HUF)</th>
                    </tr>
                ');
        $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Szerződés szám' )).';';
        $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Partner' )).';';
        $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Számla várh. érk.' )).';';
        $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Számla várh. értéke (HUF)' )).';'."\n";
        foreach( $szamlazott_tetelek as $szamlazott ) {
            $szerzodes = $adatbazis_kapcsolat->s2('recid sz_szam szallito_id', 'projektszerzodes', "`recid`='{$szamlazott['projektszerzodes_recid']}'");
            $szallito = $szerzodes['szallito_id'].' - '.getErtek('nev', 'szallito', 'id', $szerzodes['szallito_id']);
            $varh_erk = datum_kiir($szamlazott['varh_erk']);
            $varh_ert = number_format($szamlazott['varh_ert'], 0, ',', ' ');
            $html .= nl2('
                        <tr>
                            <td class="l"><a href="projektszerzodes.php?id='.$szerzodes['recid'].'&amp;tetel&amp;mezo=sz_szam&amp;op=equal&amp;felt='.$szerzodes['sz_szam'].'" target="_blank">'.$szerzodes['sz_szam'].'</a></td>
                            <td class="l">'.$szallito.'</td>
                            <td class="l">'.$varh_erk.'</td>
                            <td class="r nowrap">'.$varh_ert.'</td>
                        </tr>
                        ');
            $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szerzodes['sz_szam'] )).';';
            $csv .= '='.csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szallito)).';';
            $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $varh_erk )).';';
            $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szamlazott['varh_ert'] )).';'."\n";
        }
        
        $fajlnev = 'cash-flow-szerzodes-'.time();
        fajlba_mentes(CSV_EXPORT_UTVONAL_1.CSV_EXPORT_UTVONAL_2.'/'.$fajlnev.'.csv', $csv);
        
        $link = self().'?cash_flow_szerzodesek_export='.$fajlnev;
        $html .= nl2('
            </table>
            ');
        
        $excel_html = nl2('
            <div class="excel_grid">
                <div>
                    <a href="'.$link.'" target="_blank"><img src="../elemek/admin/excel.svg" alt="Excel"></a>
                </div>
                <div>
                    <a href="'.$link.'" target="_blank">Excel export</a>
                </div>
            </div>
            ');
        
    } else {
        $html = '<div class="b">Nem található olyan szerződés tétel a '.$_POST['pr_szam'].' számú projekthez, ami nincs teljesítve,<br>és a várható érkezés dátuma '.$_SESSION['cash_flow_ev_tol'].'.'.$_SESSION['cash_flow_ho_tol'].'.01 és '.$_SESSION['cash_flow_ev_ig'].'.'.$_SESSION['cash_flow_ho_ig'].'.31 közötti volna.</div>';
    }
    
    echo json_encode(array(
                        'html'       => $html,
                        'excel_html' => $excel_html
                    ));

    $weblap->leallit();
    exit();
}
if( isset($_GET['cash_flow_szerzodesek_export']) ) {
    
    cash_flow_csv_export_elokeszit($_GET['cash_flow_szerzodesek_export']);
    echo fajlbol_olvas(CSV_EXPORT_UTVONAL_1.CSV_EXPORT_UTVONAL_2.'/'.$_GET['cash_flow_szerzodesek_export'].'.csv');
    
    $weblap->leallit();
    exit();
}

// Számla tételek kiíratása popup-ba
if( isset($_POST['cash_flow_szamlak_mutat']) ) {
    
    $projekt    = $adatbazis_kapcsolat->s2('nev telephely_id', 'projektkezeles', "`ikt_szam`='{$_POST['pr_szam']}'");
    $szalloda   = getSzallodaNev($projekt['telephely_id']);
    
    $html = '';
    $csv = '';
    $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Projekt szám:' )).';';
    $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $_POST['pr_szam'].' '.$projekt['nev'] )).';'."\n";
    
    $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Szálloda:' )).';';
    $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szalloda )).';'."\n";
    
    $szamlazott_tetelek = $adatbazis_kapcsolat->s('`projektszamla_recid`, `telj_dt`, `netto_ert`', 'projektszamla_tetel', "`projektszerzodes_tetel_recid`!=0 AND `projektszamla_recid` IN ( SELECT `recid` FROM `".get_db_tabla()."projektszamla` WHERE `pr_szam`='{$_POST['pr_szam']}') AND `".get_db_tabla()."projektszamla`.`telj_dt`>='{$_SESSION['cash_flow_ev_tol']}-{$_SESSION['cash_flow_ho_tol']}-01' AND `".get_db_tabla()."projektszamla`.`telj_dt`<='{$_SESSION['cash_flow_ev_ig']}-{$_SESSION['cash_flow_ho_ig']}-31'", "`".get_db_tabla()."projektszamla`.`telj_dt`", '', '', '',
                          "LEFT JOIN `".get_db_tabla()."projektszamla` ON `".get_db_tabla()."projektszamla`.`recid`=`".get_db_tabla()."projektszamla_tetel`.`projektszamla_recid`");
    if( $szamlazott_tetelek ) {
        $html = nl2('
                    <table class="csikos">
                    <tr>
                        <th class="l">Számla szám</th>
                        <th class="l">Partner</th>
                        <th class="l">Teljesítés</th>
                        <th class="r">Érték (HUF)</th>
                    </tr>
                ');
        $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Számla szám' )).';';
        $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Partner' )).';';
        $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Teljesítés' )).';';
        $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Érték (HUF)' )).';'."\n";
        foreach( $szamlazott_tetelek as $szamlazott ) {
            $szamla     = $adatbazis_kapcsolat->s2('recid szla_szam szallito_id', 'projektszamla', "`recid`='{$szamlazott['projektszamla_recid']}'");
            $szallito   = $szamla['szallito_id'].' - '.getErtek('nev', 'szallito', 'id', $szamla['szallito_id']);
            $teljesites = datum_kiir($szamlazott['telj_dt']);
            $ertek      = number_format($szamlazott['netto_ert'], 0, ',', ' ');
            $html .= nl2('
                        <tr>
                            <td class="l"><a href="projektszamla.php?id='.$szamla['recid'].'&amp;tetel&amp;mezo=szla_szam&amp;op=equal&amp;felt='.$szamla['szla_szam'].'" target="_blank">'.$szamla['szla_szam'].'</a></td>
                            <td class="l">'.$szallito.'</td>
                            <td class="l">'.$teljesites.'</td>
                            <td class="r nowrap">'.$ertek.'</td>
                        </tr>
                        ');
            $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szamla['szla_szam'] )).';';
            $csv .= '='.csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szallito)).';';
            $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $teljesites )).';';
            $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szamlazott['netto_ert'] )).';'."\n";
        }
        
        $fajlnev = 'cash-flow-szamla-'.time();
        fajlba_mentes(CSV_EXPORT_UTVONAL_1.CSV_EXPORT_UTVONAL_2.'/'.$fajlnev.'.csv', $csv);
        
        $link = self().'?cash_flow_szamlak_export='.$fajlnev;
        $html .= nl2('
            </table>
            ');
        
        $excel_html = nl2('
            <div class="excel_grid">
                <div>
                    <a href="'.$link.'" target="_blank"><img src="../elemek/admin/excel.svg" alt="Excel"></a>
                </div>
                <div>
                    <a href="'.$link.'" target="_blank">Excel export</a>
                </div>
            </div>
            ');
        
    } else {
        $html = '<div class="b">Nem található olyan szerződés tétel a '.$_POST['pr_szam'].' számú projekthez,<br>ahol a teljesítés dátuma '.$_SESSION['cash_flow_ev_tol'].'.'.$_SESSION['cash_flow_ho_tol'].'.01 és '.$_SESSION['cash_flow_ev_ig'].'.'.$_SESSION['cash_flow_ho_ig'].'.31 közötti volna.</div>';
    }
    
    echo json_encode(array(
                        'html'       => $html,
                        'excel_html' => $excel_html
                    ));

    $weblap->leallit();
    exit();
}
if( isset($_GET['cash_flow_szamlak_export']) ) {
    
    cash_flow_csv_export_elokeszit($_GET['cash_flow_szamlak_export']);
    echo fajlbol_olvas(CSV_EXPORT_UTVONAL_1.CSV_EXPORT_UTVONAL_2.'/'.$_GET['cash_flow_szamlak_export'].'.csv');
    
    $weblap->leallit();
    exit();
}

// Szétosztásra váró számla tételek
if( isset($_POST['cash_flow_szetosztasra_mutat']) ) {
    
    $html = '';
    $csv = '';
    $szetosztasra_varo = $adatbazis_kapcsolat->s(
                                                // Lekérdezett oszlopok
                                                'projektszerzodes_tetel_recid, projektszamla_recid, netto_ert, telj_dt',
                                                // Tábla
                                                'projektszamla_tetel',
                                                // Where feltétel:
                                                "
                                                `projektszerzodes_tetel_recid`='0'
                                                AND `".get_db_tabla()."projektszamla`.`visszaadva`='0'
                                                AND `".get_db_tabla()."projektszamla`.`telephely_id` IN (".$_SESSION['cash_flow_szallodak'].") 
                                                AND `".get_db_tabla()."projektszamla`.`telj_dt`>='{$_SESSION['cash_flow_ev_tol']}-{$_SESSION['cash_flow_ho_tol']}-01' 
                                                AND `".get_db_tabla()."projektszamla`.`telj_dt`<='{$_SESSION['cash_flow_ev_ig']}-{$_SESSION['cash_flow_ho_ig']}-31'",
                                                // Limit Order Group by Numrows
                                                '', '', '', '',
                                                // Join-ok:
                                                'LEFT JOIN `'.get_db_tabla().'projektszamla` ON `'.get_db_tabla().'projektszamla`.`recid`=`'.get_db_tabla().'projektszamla_tetel`.`projektszamla_recid`');

    if( $szetosztasra_varo ) {
        $html = nl2('
                    <table class="csikos">
                    <tr>
                        <th class="l">Számla szám</th>
                        <th class="l">Partner</th>
                        <th class="l">Szálloda</th>
                        <th class="l">Teljesítés</th>
                        <th class="r">Várható érték</th>
                        <th class="l">Szerződés szám</th>
                        <th class="l">Projekt szám</th>
                    </tr>
                ');
        $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Számla szám' )).';';
        $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Partner' )).';';
        $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Szálloda' )).';';
        $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Teljesítés' )).';';
        $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Várható érték' )).';';
        $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Szerződés szám' )).';';
        $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Projekt szám' )).';'."\n";
        foreach( $szetosztasra_varo as $szetosztas ) {
            $szamla     = $adatbazis_kapcsolat->s2('recid szla_szam sz_szam pr_szam szallito_id telephely_id', 'projektszamla', "`recid`='{$szetosztas['projektszamla_recid']}'");
            $projekt    = $adatbazis_kapcsolat->s2('nev', 'projektkezeles', "`ikt_szam`='{$szamla['pr_szam']}'");
            
            $szallito   = $szamla['szallito_id'].' - '.getErtek('nev', 'szallito', 'id', $szamla['szallito_id']);
            $szalloda   = getSzallodaNev($szamla['telephely_id']);
            $teljesites = datum_kiir($szetosztas['telj_dt']);
            $netto_ert  = number_format($szetosztas['netto_ert'], 0, ',', ' ');
            $html .= nl2('
                        <tr>
                            <td class="l"><a href="projektszamla.php?id='.$szamla['recid'].'&amp;tetel&amp;mezo=szla_szam&amp;op=equal&amp;felt='.$szamla['szla_szam'].'" target="_blank">'.$szamla['szla_szam'].'</a></td>
                            <td class="l">'.$szallito.'</td>
                            <td class="l">'.$szalloda.'</td>
                            <td class="l">'.$teljesites.'</td>
                            <td class="r nowrap">'.$netto_ert.'</td>
                            <td class="l">'.$szamla['sz_szam'].'</td>
                            <td class="l">'.$szamla['pr_szam'].' '.$projekt['nev'].'</td>
                        </tr>
                        ');
            $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szamla['szla_szam'] )).';';
            $csv .= '='.csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szallito)).';';
            $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szalloda )).';';
            $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $teljesites )).';';
            $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $netto_ert )).';';
            $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szamla['sz_szam'] )).';';
            $csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szamla['pr_szam'].' '.$projekt['nev'] )).';'."\n";
        }
        
        $fajlnev = 'cash-flow-szetosztasra-varo-'.time();
        fajlba_mentes(CSV_EXPORT_UTVONAL_1.CSV_EXPORT_UTVONAL_2.'/'.$fajlnev.'.csv', $csv);
        
        $link = self().'?cash_flow_szetosztasra_export='.$fajlnev;
        $html .= nl2('
            </table>
            ');
        
        $excel_html = nl2('
            <div class="excel_grid">
                <div>
                    <a href="'.$link.'" target="_blank"><img src="../elemek/admin/excel.svg" alt="Excel"></a>
                </div>
                <div>
                    <a href="'.$link.'" target="_blank">Excel export</a>
                </div>
            </div>
            ');
        
    } else {
        $html = '<div class="b">Nem található olyan szétosztásra váró számla tétel<br>ahol a teljesítés dátuma '.$_SESSION['cash_flow_ev_tol'].'.'.$_SESSION['cash_flow_ho_tol'].'.01 és '.$_SESSION['cash_flow_ev_ig'].'.'.$_SESSION['cash_flow_ho_ig'].'.31 közötti volna.</div>';
    }
    
    echo json_encode(array(
                        'html'       => $html,
                        'excel_html' => $excel_html,
                    ));

    $weblap->leallit();
    exit();
}
if( isset($_GET['cash_flow_szetosztasra_export']) ) {
    
    cash_flow_csv_export_elokeszit($_GET['cash_flow_szetosztasra_export']);
    echo fajlbol_olvas(CSV_EXPORT_UTVONAL_1.CSV_EXPORT_UTVONAL_2.'/'.$_GET['cash_flow_szetosztasra_export'].'.csv');
    
    $weblap->leallit();
    exit();
}

// CSV generálása
if( isset($_GET['csv']) ) {
    
    if( isset($_GET['szallodankent']) ) {
        cash_flow_csv_export_elokeszit('cash_flow_export_szalloda');
        echo fajlbol_olvas('cash_flow_export_szalloda'.session_id().'.csv');
    } else if( isset($_GET['projektenkent']) ) {
        cash_flow_csv_export_elokeszit('cash_flow_export_projekt');
        echo fajlbol_olvas('cash_flow_export_projekt'.session_id().'.csv');
    } else if( isset($_GET['reszletes']) ) {
        cash_flow_csv_export_elokeszit('cash_flow_export_reszletes');
        echo fajlbol_olvas('cash_flow_export_reszletes'.session_id().'.csv');
    }
    
    $weblap->leallit();
    exit();
}



// Tartalom
if( !$_GET['ajax'] ) {
    require_once('header.php');
}
require_once('selectek.php');

    if( !$_GET['ajax'] ) {
        echo '<div id="szamlaBetolt">';
    }

    if( isset($_GET['modosit']) || isset($_GET['cash_flow']) ) {
        
        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'cash_flow'     => 'Cash-flow',
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

    if( isset($_GET['modosit']) ) {
        // Gombok kirajzolása minden oldalra
        $gombok = '<input type="button" class="gomb zold" value="Cash-flow elkészítése" onclick="cash_flow_elkeszitese()">';
    }
    
    $muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 16).'px);"';
    if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
        if( $_COOKIE['csuszkaSzelesseg'] ) {
            $muv_style = ' style="width: calc(100% - '.(($_COOKIE['csuszkaSzelesseg'] + 16)/10).'rem);"';
        }
    }

    // Műveletek box
    $gombok = nl2('
        <div class="muveletek megis_mindig_latszik"'.$muv_style.'>
            '.$gombok.'
        </div>
    ');
    
    $js_submit_elott = '';
    
    // Adatlap
	if( isset($_GET['modosit']) ) {
        
        // Adatlap két hasábja
        $h_szallodak = hozzaadott_szallodak_kiir();

        $pri_egyutt         = '';
        $szetosztas_egyutt  = ' checked="checked"';
        if( $_SESSION['cash_flow_pri_egyutt'] ) {
            $pri_egyutt         = ' checked="checked"';
        }
        if( $_SESSION['cash_flow_szetosztas_egyutt'] ) {
            $szetosztas_egyutt  = '';
        }
        
        $bontas_opciok = array(
            array('name' => 'Havi',         'val' => 'havi'  ),
            array('name' => 'Heti',         'val' => 'heti'  ),
            array('name' => 'Negyedéves',   'val' => 'negyed'),
        );
        $bontas = tomb2option($bontas_opciok, 'val', 'name', $_SESSION['cash_flow_bontas']);
        
        $ev_tol = $_SESSION['cash_flow_ev_tol'];
        $ho_tol = $_SESSION['cash_flow_ho_tol'];
        $ev_ig  = $_SESSION['cash_flow_ev_ig'];
        $ho_ig  = $_SESSION['cash_flow_ho_ig'];
        $szamla_html .= str_replace(
                array('{EV_TOL}', '{HO_TOL}', '{EV_IG}', '{HO_IG}', '{H_SZALLODAK}', '{BONTAS}', '{PRI_EGYUTT}', '{SZETOSZTAS_EGYUTT}'),
                array($ev_tol,    $ho_tol,    $ev_ig,    $ho_ig,    $h_szallodak,    $bontas,    $pri_egyutt,    $szetosztas_egyutt   ),
                fajlbol_olvas('../sablon/admin/cash_flow_adatlap.html', 'tömörítve')
        );

        // Form_mod kirajzolása
	    $szamla_html .= str_replace(
            array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}', '{UPDATE_DT}'     ),
            array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok,    $sor['update_dt'] ),
            fajlbol_olvas('../sablon/admin/form_modosit.html', 'tömörítve')
        );

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

	// Cash flow 
    } elseif( isset($_GET['cash_flow']) ) {
        
        // Ha nincsenek beállítások, akkor hibaüzenet
        if( !$_SESSION['cash_flow_ev_tol'] || !$_SESSION['cash_flow_szallodak'] ) {
            
            $szamla_html .= '<h1 class="f20">Kérem előbb válasszon időszakot és szállodákat,<br>hogy a cash-flow táblázatot elő lehessen állítani!</h1>';
            
        // Egyébként a táblázat kirajzolása
        } else {
            
            $csv_szalloda = $csv_projekt = $csv_reszletes = '';
            $csv_szalloda   .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Szálloda' ))                     .';'.csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Összesen' )).';';
            $csv_projekt    .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Szálloda / Projekt' ))           .';'.csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Összesen' )).';';
            $csv_reszletes  .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Szálloda / Projekt / CF típus' )).';'.csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Összesen' )).';';
            
            $datum_hossz = 7; // Hónappontos
            if( $_SESSION['cash_flow_bontas'] == 'heti' ) {
                $datum_hossz = 10; // Nappontos - heti bontáshoz már szükségünk van a napokra is, mert egy heti bontásban nappontos dátumok vannak.
            }
            
            // Hónapok kiszámítása, és a fejléc előállítása
            $bontas = get_honapok_szama($_SESSION['cash_flow_ev_tol'], $_SESSION['cash_flow_ho_tol'], $_SESSION['cash_flow_ev_ig'], $_SESSION['cash_flow_ho_ig'], 'tömbként');
            $cimek_fejlecbe = '';
            foreach( $bontas as $cim => $honapok ) {
                $cimek_fejlecbe .= '<th>'.datum_kiir($cim).'</th>';
                $csv_szalloda   .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, datum_kiir($cim) )).';';
                $csv_projekt    .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, datum_kiir($cim) )).';';
                $csv_reszletes  .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, datum_kiir($cim) )).';';
            }
            $csv_szalloda   .= "\n";
            $csv_projekt    .= "\n";
            $csv_reszletes  .= "\n";

            $szamla_html .= nl2('
                <script>
                /* A betöltés... alert-et bezárjuk, sikeres betöltés után. */
                $(document).ready(function () {
                    setTimeout(function(){
                        alert2();
                    }, 200);
                });
                </script>
                <div id="fixTable">
                    <table class="szamla_cont_tabla nolink fejlec_padd szep_csikos kisbetu miniprojekt_tabla width_auto">
                    <thead>
                    <tr>
                        <th class="ktgnem_kod fix mini1 kibont f_osszes_kibont">Szálloda / Projekt / CF típus</th>
                        <th>Összesen</th>
                        '.$cimek_fejlecbe.'
                    </tr>
                    </thead>
                    <tbody>
            ');

            // Ezt csak magunknak, teszteléshez, ha mindent szeretnénk kinyitva látni
            $dn = 'dn';
            if( isset($_GET['mind']) ) {
                $dn = '';
            }

            $nagy_osszesito_sum  = array();
            $szallodak = explode(',', $_SESSION['cash_flow_szallodak']);
            foreach ( $szallodak as $szalloda_id ) {

                $projekt_sorok              = '';
                $projektigeny_csv           = '';
                $projektigeny_reszletes_csv = '';
                $szalloda_sum               = array();
                // Projektigénylések kilistázása, ha ez a pipa be volt pipálva
                if( $_SESSION['cash_flow_pri_egyutt'] ) {
                    $projekt_igenyek = $adatbazis_kapcsolat->s('`'.get_db_tabla().'projektigenyles`.`recid`, `'.get_db_tabla().'projektigenyles`.`ikt_szam` , `'.get_db_tabla().'projektigenyles`.`nev`', 'projektigenyles', "`".get_db_tabla()."projektigenyles`.`telephely_id`='{$szalloda_id}' AND ISNULL(`".get_db_tabla()."projektkezeles`.`projektigenyles_recid`) AND `".get_db_tabla()."cash_flow`.`datum`>='{$_SESSION['cash_flow_ev_tol']}-{$_SESSION['cash_flow_ho_tol']}-01' AND `".get_db_tabla()."cash_flow`.`datum`<='{$_SESSION['cash_flow_ev_ig']}-{$_SESSION['cash_flow_ho_ig']}-31'", '', '', "`".get_db_tabla()."projektigenyles`.`ikt_szam`", '',
                                                               '
                                                                LEFT JOIN `'.get_db_tabla().'projektkezeles`  ON `'.get_db_tabla().'projektkezeles`.`projektigenyles_recid`=`'.get_db_tabla().'projektigenyles`.`recid`
                                                                LEFT JOIN `'.get_db_tabla().'cash_flow` ON `'.get_db_tabla().'cash_flow`.`apa_recid`=`'.get_db_tabla().'projektigenyles`.`recid`
                                                                ');
                    foreach( $projekt_igenyek as $projekt_igeny ) {
                        
                        $projekt_igeny_sum    = array();
                        $tervezett_igeny_sum  = 0;
                        $pr_igeny_ossz_sum    = 0;
                        
                        $tervezett_igeny        = $adatbazis_kapcsolat->s('SUBSTRING(`datum`, 1, '.$datum_hossz.') as `datum_honapig`, SUM(`osszeg`)', 'cash_flow', "`apa_recid`='{$projekt_igeny['recid']}' AND `datum`>='{$_SESSION['cash_flow_ev_tol']}-{$_SESSION['cash_flow_ho_tol']}-01' AND `datum`<='{$_SESSION['cash_flow_ev_ig']}-{$_SESSION['cash_flow_ho_ig']}-31'", '', '', 'datum_honapig');
                        $tervezett_igeny_html   = '';
                        $projektigeny_terv_csv  = csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Tervezett' )).';';
                        $csv_tmp                = '';
                        foreach( $bontas as $cim => $honapok ) {
                            $tervezett_osszeg = 0;
                            foreach( $honapok as $honap ) {
                                foreach( $tervezett_igeny as $tervez ) {
                                    if( $tervez['datum_honapig'] == $honap ) {
                                        $tervezett_osszeg           += $tervez['SUM(`osszeg`)'];
                                        $projekt_igeny_sum[$honap]  += $tervez['SUM(`osszeg`)'];
                                        $tervezett_igeny_sum        += $tervez['SUM(`osszeg`)'];
                                        break;
                                    }
                                }
                            }
                            $tervezett_igeny_html   .= '<td class="r">'.number_format($tervezett_osszeg, 0, '', ' ').'</td>';
                            $csv_tmp                .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $tervezett_osszeg )).';';
                        }
                        $projektigeny_terv_csv  .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $tervezett_igeny_sum )).';'.$csv_tmp."\n";
                        
                        // Projektigénylés szumma sor
                        $projekt_igeny_sum_html     = '';
                        $projekt_sorok_csv_tmp_sum  = '';
                        $projektigeny_csv          .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $projekt_igeny['ikt_szam'].' '.$projekt_igeny['nev'] )).';';
                        $projektigeny_reszletes_csv.= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $projekt_igeny['ikt_szam'].' '.$projekt_igeny['nev'] )).';';
                        $csv_tmp                    = '';
                        foreach( $bontas as $cim => $honapok ) {
                            $projekt_igeny_ossz = 0;
                            foreach ( $honapok as $honap ) {
                                $projekt_igeny_ossz     += $projekt_igeny_sum[$honap];
                                $szalloda_sum[$honap]   += $projekt_igeny_sum[$honap];
                                $pr_igeny_ossz_sum      += $projekt_igeny_sum[$honap];
                            }
                            $projekt_igeny_sum_html     .= '<td class="r">'.number_format($projekt_igeny_ossz, 0, '', ' ').'</td>';
                            $csv_tmp                    .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $projekt_igeny_ossz )).';';
                        }
                        $projektigeny_csv           .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $pr_igeny_ossz_sum )).';'.$csv_tmp."\n";
                        $projektigeny_reszletes_csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $pr_igeny_ossz_sum )).';'.$csv_tmp."\n"
                                                    .$projektigeny_terv_csv;
                        
                        
                        $projekt_sorok .= nl2('
                        <tr id="projekt_igeny_'.$projekt_igeny['recid'].'" class="projektigeny_sor '.$dn.' szalloda_'.$szalloda_id.'">
                            <td class="fix cf1 kibont f_projekt_kibont wrap"><b>'.$projekt_igeny['ikt_szam'].'</b><br>'.$projekt_igeny['nev'].'</td>
                            <td class="r">'.number_format($pr_igeny_ossz_sum, 0, '', ' ').'</td>
                            '.$projekt_igeny_sum_html.'
                        </tr>
                        <tr id="tervezett_igeny_'.$projekt_igeny['recid'].'" class="tervezett_sor '.$dn.' projekt_igeny_'.$projekt_igeny['recid'].'">
                            <td class="fix cf1"><a href="projektigenyles.php?id='.$projekt_igeny['recid'].'&amp;cash_flow&amp;mezo=ikt_szam&amp;op=equal&amp;felt='.$projekt_igeny['ikt_szam'].'" target="_blank">Tervezett</a></td>
                            <td class="r">'.number_format($tervezett_igeny_sum, 0, '', ' ').'</td>
                            '.$tervezett_igeny_html.'
                        </tr>
                        ');
                        $projekt_sorok_csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $pr_igeny_ossz_sum )).';'.$projekt_sorok_csv_tmp_sum."\n";
                        $projekt_sorok_csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $tervezett_igeny_sum )).';'.$projekt_sorok_csv_tmp."\n";
                    }
                    
                }
                
                // Projektek kilistázása
                // E bonyolult lekérdezés arra szolgál, hogy csak azokat a projekteket kérjük le, ami a megadott időpontban és szállodánál értintett
                $projekt_szumma                 = '';
                $projektek = $adatbazis_kapcsolat->s(
                                                    // Lekért oszlopok:
                                                    '`'.get_db_tabla().'projektkezeles`.`recid`, `'.get_db_tabla().'projektkezeles`.`ikt_szam`, `'.get_db_tabla().'projektkezeles`.`nev`, `'.get_db_tabla().'projektkezeles`.`pfelelos`',
                                                    // Tábla:
                                                    'projektkezeles',
                                                    // Where feltételek:
                                                    "`".get_db_tabla()."projektkezeles`.`telephely_id`='{$szalloda_id}' AND
                                                    (
                                                        (`".get_db_tabla()."projektkezeles_cashflow`.`datum`>='{$_SESSION['cash_flow_ev_tol']}-{$_SESSION['cash_flow_ho_tol']}-01' AND `".get_db_tabla()."projektkezeles_cashflow`.`datum`<='{$_SESSION['cash_flow_ev_ig']}-{$_SESSION['cash_flow_ho_ig']}-31') OR
                                                        (`".get_db_tabla()."projektszerzodes_tetel`.`teljesites`>='{$_SESSION['cash_flow_ev_tol']}-{$_SESSION['cash_flow_ho_tol']}-01' AND `".get_db_tabla()."projektszerzodes_tetel`.`teljesites`<='{$_SESSION['cash_flow_ev_ig']}-{$_SESSION['cash_flow_ho_ig']}-31') OR
                                                        (`".get_db_tabla()."projektszerzodes_tetel`.`teljesites`='0000-00-00' AND `".get_db_tabla()."projektszerzodes_tetel`.`varh_erk`>='{$_SESSION['cash_flow_ev_tol']}-{$_SESSION['cash_flow_ho_tol']}-01' AND `".get_db_tabla()."projektszerzodes_tetel`.`varh_erk`<='{$_SESSION['cash_flow_ev_ig']}-{$_SESSION['cash_flow_ho_ig']}-31')
                                                    )
                                                    ", '', '',
                                                    // Group by
                                                    "`".get_db_tabla()."projektkezeles`.`ikt_szam`", '',
                                                    // Join-ok
                                                    '
                                                    LEFT JOIN `'.get_db_tabla().'projektszerzodes` ON `'.get_db_tabla().'projektszerzodes`.`pr_szam`=`'.get_db_tabla().'projektkezeles`.`ikt_szam`
                                                    LEFT JOIN `'.get_db_tabla().'projektszerzodes_tetel` ON `'.get_db_tabla().'projektszerzodes_tetel`.`projektszerzodes_recid`=`'.get_db_tabla().'projektszerzodes`.`recid`
                                                    LEFT JOIN `'.get_db_tabla().'projektkezeles_cashflow` ON `'.get_db_tabla().'projektkezeles_cashflow`.`projektkezeles_recid`=`'.get_db_tabla().'projektkezeles`.`recid`
                                                     '
                                                            );
                                                    
                foreach( $projektek as $projekt ) {

                    $projekt_sum    = array();
                    $tervezett_sum  = 0;
                    $szerzodes_sum  = 0;
                    $szamlazott_sum = 0;
                    $pr_ossz_sum    = 0;
                    
                    $projekt_terv_csv               = '';
                    $projekt_szerz_csv              = '';
                    $projekt_szaml_csv              = '';

                    // Tervezett összegek összeadása
                    $tervezett = $adatbazis_kapcsolat->s('SUBSTRING(`datum`, 1, '.$datum_hossz.') as `datum_honapig`, SUM(`terv`)', 'projektkezeles_cashflow', "`projektkezeles_recid`='{$projekt['recid']}' AND `datum`>='{$_SESSION['cash_flow_ev_tol']}-{$_SESSION['cash_flow_ho_tol']}-01' AND `datum`<='{$_SESSION['cash_flow_ev_ig']}-{$_SESSION['cash_flow_ho_ig']}-31'", '', '', 'datum_honapig');
                    $tervezett_html     = '';
                    $csv_tmp            = '';
                    $projekt_terv_csv   .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Tervezett' )).';';
                    foreach( $bontas as $cim => $honapok ) {
                        $tervezett_osszeg = 0;
                        foreach ( $honapok as $honap ) {
                            foreach( $tervezett as $tervez ) {
                                if( $tervez['datum_honapig'] == $honap ) {
                                    $tervezett_osszeg     += $tervez['SUM(`terv`)'];
                                    $projekt_sum[$honap]  += $tervez['SUM(`terv`)'];
                                    $tervezett_sum        += $tervez['SUM(`terv`)'];
                                    break;
                                }
                            }
                        }
                        $tervezett_html .= '<td class="r">'.number_format($tervezett_osszeg, 0, '', ' ').'</td>';
                        $csv_tmp        .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $tervezett_osszeg )).';';
                    }
                    $projekt_terv_csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $tervezett_sum )).';'.$csv_tmp."\n";

                    // Szerződött összegek összeadása
                    $szerzodott         = $adatbazis_kapcsolat->s('SUBSTRING(`varh_erk`, 1, '.$datum_hossz.') as `datum_honapig`, SUM(`varh_ert`)', 'projektszerzodes_tetel', "`teljesites`='0000-00-00' AND `projektszerzodes_recid` IN ( SELECT `recid` FROM `".get_db_tabla()."projektszerzodes` WHERE `pr_szam`='{$projekt['ikt_szam']}') AND `varh_erk`>='{$_SESSION['cash_flow_ev_tol']}-{$_SESSION['cash_flow_ho_tol']}-01' AND `varh_erk`<='{$_SESSION['cash_flow_ev_ig']}-{$_SESSION['cash_flow_ho_ig']}-31'", '', '', 'datum_honapig');
                    $szerzodott_html    = '';
                    $csv_tmp            = '';
                    $projekt_szerz_csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Szerződött' )).';';
                    foreach( $bontas as $cim => $honapok ) {
                        $szerzodott_osszeg = 0;
                        foreach ( $honapok as $honap ) {
                            foreach( $szerzodott as $szerz ) {
                                if( $szerz['datum_honapig'] == $honap ) {
                                    $szerzodott_osszeg    += $szerz['SUM(`varh_ert`)'];
                                    $projekt_sum[$honap]  += $szerz['SUM(`varh_ert`)'];
                                    $szerzodes_sum        += $szerz['SUM(`varh_ert`)'];
                                    break;
                                }
                            }
                        }
                        $szerzodott_html .= '<td class="r">'.number_format($szerzodott_osszeg, 0, '', ' ').'</td>';
                        $csv_tmp         .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szerzodott_osszeg )).';';
                    }
                    $projekt_szerz_csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szerzodes_sum )).';'.$csv_tmp."\n";

                    
                    $szamlazott         = $adatbazis_kapcsolat->s('SUBSTRING(`teljesites`, 1, '.$datum_hossz.') as `datum_honapig`, SUM(`ertek`)', 'projektszerzodes_tetel', "`projektszerzodes_recid` IN ( SELECT `recid` FROM `".get_db_tabla()."projektszerzodes` WHERE `pr_szam`='{$projekt['ikt_szam']}') AND `teljesites`>='{$_SESSION['cash_flow_ev_tol']}-{$_SESSION['cash_flow_ho_tol']}-01' AND `teljesites`<='{$_SESSION['cash_flow_ev_ig']}-{$_SESSION['cash_flow_ho_ig']}-31'", '', '', 'datum_honapig');
                    $szamlazott_html    = '';
                    $csv_tmp            = '';
                    $projekt_szaml_csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Számlázott' )).';';
                    foreach( $bontas as $cim => $honapok ) {
                        $szamlazott_osszeg = 0;
                        foreach ( $honapok as $honap ) {
                            foreach( $szamlazott as $szamlaz ) {
                                if( $szamlaz['datum_honapig'] == $honap ) {
                                    $szamlazott_osszeg    += $szamlaz['SUM(`ertek`)'];
                                    $projekt_sum[$honap]  += $szamlaz['SUM(`ertek`)'];
                                    $szamlazott_sum       += $szamlaz['SUM(`ertek`)'];
                                    break;
                                }
                            }
                        }
                        $szamlazott_html .= '<td class="r">'.number_format($szamlazott_osszeg, 0, '', ' ').'</td>';
                        $csv_tmp         .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szamlazott_osszeg )).';';
                    }
                    $projekt_szaml_csv .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szamlazott_sum )).';'.$csv_tmp."\n";

                    // Projektek szumma sor
                    $projekt_sum_html          = '';
                    $csv_tmp                   = '';
                    $projekt_szumma           .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $projekt['ikt_szam'].' '.$projekt['nev'] )).';';
                    $projekt_szumma_reszletes .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $projekt['ikt_szam'].' '.$projekt['nev'] )).';';
                    foreach( $bontas as $cim => $honapok ) {
                        $pr_ossz = 0;
                        foreach ( $honapok as $honap ) {
                            $pr_ossz              += $projekt_sum[$honap];
                            $szalloda_sum[$honap] += $projekt_sum[$honap];
                            $pr_ossz_sum          += $projekt_sum[$honap];
                        }
                        $projekt_sum_html .= '<td class="r">'.number_format($pr_ossz, 0, '', ' ').'</td>';
                        $csv_tmp          .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $pr_ossz )).';';
                    }
                    $projekt_szumma           .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $pr_ossz_sum )).';'.$csv_tmp."\n";
                    $projekt_szumma_reszletes .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $pr_ossz_sum )).';'.$csv_tmp."\n"
                                              .$projekt_terv_csv
                                              .$projekt_szerz_csv
                                              .$projekt_szaml_csv;
                    
                    $projekt_sorok .= nl2('
                    <tr id="projekt_'.$projekt['recid'].'" class="projekt_sor '.$dn.' szalloda_'.$szalloda_id.'">
                        <td class="fix cf1 kibont f_projekt_kibont wrap"><b>'.$projekt['ikt_szam'].'</b> '.getErtek('teljes_nev', 'login', 'recid', $projekt['pfelelos']).'<br>'.$projekt['nev'].'</td>
                        <td class="r">'.number_format($pr_ossz_sum, 0, '', ' ').'</td>
                        '.$projekt_sum_html.'
                    </tr>
                    <tr id="tervezett_'.$projekt['recid'].'" class="tervezett_sor '.$dn.' szalloda_'.$szalloda_id.'_zar projekt_'.$projekt['recid'].'">
                        <td class="fix cf1"><a href="projektkezeles.php?id='.$projekt['recid'].'&amp;cash_flow&amp;mezo=ikt_szam&amp;op=equal&amp;felt='.$projekt['ikt_szam'].'" target="_blank">Tervezett</a></td>
                        <td class="r">'.number_format($tervezett_sum, 0, '', ' ').'</td>
                        '.$tervezett_html.'
                    </tr>
                    <tr id="szerzodott_'.$projekt['recid'].'" class="szerzodott_sor '.$dn.' szalloda_'.$szalloda_id.'_zar projekt_'.$projekt['recid'].'">
                        <td class="fix cf1"><a href="javascript:cash_flow_pr_szam=\''.$projekt['ikt_szam'].'\'; csoportMuvelet(\'cash_flow_szerzodesek_mutat\'); void(0);">Szerződött</a></td>
                        <td class="r">'.number_format($szerzodes_sum, 0, '', ' ').'</td>
                        '.$szerzodott_html.'
                    </tr>
                    <tr id="szamlazott_'.$projekt['recid'].'" class="szamlazott_sor '.$dn.' szalloda_'.$szalloda_id.'_zar projekt_'.$projekt['recid'].'">
                        <td class="fix cf1"><a href="javascript:cash_flow_pr_szam=\''.$projekt['ikt_szam'].'\'; csoportMuvelet(\'cash_flow_szamlak_mutat\'); void(0);">Számlázott</a></td>
                        <td class="r">'.number_format($szamlazott_sum, 0, '', ' ').'</td>
                        '.$szamlazott_html.'
                    </tr>
                    ');
                }

                $szalloda_ossz_sum = 0;
                $szalloda_sum_html = '';
                $szalloda_nev = getSzallodaNev($szalloda_id);
                $csv_szalloda   .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szalloda_nev )).';';
                $csv_projekt    .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szalloda_nev )).';';
                $csv_reszletes  .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szalloda_nev )).';';
                $csv_tmp = '';
                foreach( $bontas as $cim => $honapok ) {
                    $nagy_osszesito = 0;
                    foreach ( $honapok as $honap ) {
                        $nagy_osszesito             += $szalloda_sum[$honap];
                        $szalloda_ossz_sum          += $szalloda_sum[$honap];
                        $nagy_osszesito_sum[$honap] += $szalloda_sum[$honap];
                    }
                    $szalloda_sum_html  .= '<td class="r">'.number_format($nagy_osszesito, 0, '', ' ').'</td>';
                    $csv_tmp .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $nagy_osszesito )).';';
                }
                $csv_szalloda   .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szalloda_ossz_sum )).';'.$csv_tmp."\n";
                $csv_projekt    .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szalloda_ossz_sum )).';'.$csv_tmp."\n"
                                .$projektigeny_csv
                                .$projekt_szumma;
                $csv_reszletes  .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szalloda_ossz_sum )).';'.$csv_tmp."\n"
                                .$projektigeny_reszletes_csv
                                .$projekt_szumma_reszletes;

                $szamla_html .= nl2('
                <tr id="szalloda_'.$szalloda_id.'" class="szalloda_sor">
                    <td class="fix cf1 kibont f_szalloda_kibont">'.getSzallodaNev($szalloda_id).'</td>
                    <td class="r">'.number_format($szalloda_ossz_sum, 0, '', ' ').'</td>
                    '.$szalloda_sum_html.'
                </tr>
                ').$projekt_sorok;

            }
            
            // Szétosztásra váró számla tételek 
            // (igen itt fordítva van, akkor kell csinálni, ha hamis, mert azaz alapértelmezés, hogy be van pipálva)
            if( !$_SESSION['cash_flow_szetosztas_egyutt'] ) {
                
                $szetosztasra_varo = $adatbazis_kapcsolat->s(
                                                            // Lekrédezett oszlopok
                                                            'SUBSTRING(`telj_dt`, 1, '.$datum_hossz.') as `datum_honapig`, SUM(`netto_ert`)',
                                                            // Tábla
                                                            'projektszamla_tetel',
                                                            // Where feltétel:
                                                            "
                                                            `projektszerzodes_tetel_recid`='0'
                                                            AND `".get_db_tabla()."projektszamla`.`visszaadva`='0'
                                                            AND `".get_db_tabla()."projektszamla`.`telephely_id` IN (".$_SESSION['cash_flow_szallodak'].") 
                                                            AND `".get_db_tabla()."projektszamla`.`telj_dt`>='{$_SESSION['cash_flow_ev_tol']}-{$_SESSION['cash_flow_ho_tol']}-01' 
                                                            AND `".get_db_tabla()."projektszamla`.`telj_dt`<='{$_SESSION['cash_flow_ev_ig']}-{$_SESSION['cash_flow_ho_ig']}-31'",
                                                            // Group by:
                                                            '', '', 'datum_honapig', '',
                                                            // Join-ok:
                                                            'LEFT JOIN `'.get_db_tabla().'projektszamla` ON `'.get_db_tabla().'projektszamla`.`recid`=`'.get_db_tabla().'projektszamla_tetel`.`projektszamla_recid`');
                $szetosztasra_varo_html = '';
                $szetosztasra_varo_sum  = 0;
                $csv_szalloda   .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Szétosztásra váró számla tételek' )).';';
                $csv_projekt    .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Szétosztásra váró számla tételek' )).';';
                $csv_reszletes  .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Szétosztásra váró számla tételek' )).';';
                $csv_tmp = '';
                foreach( $bontas as $cim => $honapok ) {
                    $szetosztasra_varo_osszeg = 0;
                    foreach ( $honapok as $honap ) {
                        foreach( $szetosztasra_varo as $szetoszt ) {
                            if( $szetoszt['datum_honapig'] == $honap ) {
                                $szetosztasra_varo_osszeg   += $szetoszt['SUM(`netto_ert`)'];
                                $szetosztasra_varo_sum      += $szetoszt['SUM(`netto_ert`)'];
                                $nagy_osszesito_sum[$honap] += $szetoszt['SUM(`netto_ert`)'];
                                break;
                            }
                        }
                    }
                    $szetosztasra_varo_html .= '<td class="r">'.number_format($szetosztasra_varo_osszeg, 0, '', ' ').'</td>';
                    $csv_tmp .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szetosztasra_varo_osszeg )).';';
                }
                $csv_szalloda   .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szetosztasra_varo_sum )).';'.$csv_tmp."\n";
                $csv_projekt    .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szetosztasra_varo_sum )).';'.$csv_tmp."\n";
                $csv_reszletes  .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $szetosztasra_varo_sum )).';'.$csv_tmp."\n";
                
                $szamla_html .= nl2('
                <tr id="szetosztasra_varo_szla_tetelek">
                    <td class="fix cf1"><a href="javascript:csoportMuvelet(\'cash_flow_szetosztas_mutat\'); void(0);">Szétosztásra váró számla tételek</a></td>
                    <td class="r">'.number_format($szetosztasra_varo_sum, 0, '', ' ').'</td>
                    '.$szetosztasra_varo_html.'
                </tr>
                ');
            }
            
            // Teljes összesítés
            $osszesito_html = '';
            $vegso_nagy_sum = 0;
            $csv_szalloda   .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Összesen' )).';';
            $csv_projekt    .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Összesen' )).';';
            $csv_reszletes  .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, 'Összesen' )).';';
            $csv_tmp         = '';
            foreach( $bontas as $cim => $honapok ) {
                $vegso_nagy_ossz = 0;
                foreach ( $honapok as $honap ) {
                    $vegso_nagy_ossz    += $nagy_osszesito_sum[$honap];
                    $vegso_nagy_sum     += $nagy_osszesito_sum[$honap];
                }
                $osszesito_html .= '<td class="r">'.number_format($vegso_nagy_ossz, 0, '', ' ').'</td>';
                $csv_tmp        .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $vegso_nagy_ossz )).';';
            }
            $csv_szalloda   .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $vegso_nagy_sum )).';'.$csv_tmp."\n";
            $csv_projekt    .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $vegso_nagy_sum )).';'.$csv_tmp."\n";
            $csv_reszletes  .= csv_ellenorzes_letolt(iconv2($csv_mibol, $csv_mibe, $vegso_nagy_sum )).';'.$csv_tmp."\n";
            
            $szamla_html .= nl2('
            <tr id="osszesen_lablec">
                <td class="fix cf1">Összesen</td>
                <td class="r">'.number_format($vegso_nagy_sum, 0, '', ' ').'</td>
                '.$osszesito_html.'
            </tr>
            ');
            
            // Fixált footer-t így tudsz csinálni
//            $szamla_html .= nl2('</tbody>
//                <tfoot class="nowrap">
//                    <td></td>
//                    <td></td>
//                    '.$lablec.'
//                </tfoot>');
            
            // Lezárás
            $szamla_html .= nl2('
            </table>
        </div>
                ');
        }
        
        fajlba_mentes('cash_flow_export_szalloda'.session_id().'.csv',  $csv_szalloda);
        fajlba_mentes('cash_flow_export_projekt'.session_id().'.csv',   $csv_projekt);
        fajlba_mentes('cash_flow_export_reszletes'.session_id().'.csv', $csv_reszletes);

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
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

// Hozzáadott szállodák kiíratása
function hozzaadott_szallodak_kiir() {
    
    global $adatbazis_kapcsolat;
    
    $html = '';
    $telephelyek = $adatbazis_kapcsolat->s('id nev', 'telephely', "`id` IN (".$_SESSION['cash_flow_szallodak'].")", 'nev');
    foreach ( $telephelyek as $telephely ) {
        $html .= nl2('
            <div>
                '.$telephely['nev'].'
            </div>
        ');
    }
    
    return $html;
}


// Két dátumot megadva (napok nélkül), visszaaja a kettő közti dátumok számát
function get_honapok_szama($ev_tol, $ho_tol, $ev_ig, $ho_ig, $tombkent = false) {
    
    if( $ev_tol && $ho_tol && $ev_ig && $ho_ig ) {

        $honapok_szama = (($ev_ig - $ev_tol)*12) + ((int)$ho_ig - (int)$ho_tol) ;

        $akt_ev    = $_SESSION['cash_flow_ev_tol'];
        $akt_honap = $_SESSION['cash_flow_ho_tol'];

        $honapok = array();
        if( $tombkent ) {

            for ( $i = 0; $i <= $honapok_szama; $i++ ) {
                
                $akt_honap_kiir = $akt_ev.'-'.sprintf("%02d", $akt_honap);
                
                // Heti bontás: ekkor heti szinten készítünk egy bontás, és belepakolunk 7 dátumot,
                // amire keressük majd illesztést, az SQL-ből kapott dátumokkal
                if( $_SESSION['cash_flow_bontas'] == 'heti' ) {
                    for( $nap = 1; $nap <= 31; $nap++ ) {
                        $datum   = $akt_ev.'-'.sprintf("%02d", $akt_honap).'-'.sprintf("%02d", $nap);
                        $ev_hete = date('W', strtotime($datum));
                        $bontas_cim = $akt_ev.'<br>'.$ev_hete.'. hét';
                        if( !is_array($honapok[$bontas_cim]) ) {
                            $honapok[$bontas_cim] = array();
                        }
                        array_push( $honapok[$bontas_cim], $datum );
                    }
                
                // Havi bontás: ekkor minden tömben csak egy elem van, az aktuális hónap
                } else if( $_SESSION['cash_flow_bontas'] == 'havi' ) {
                    $honapok[$akt_honap_kiir] = array($akt_honap_kiir);
                    
                // Ekkor minden tömbben benne van adott negyedévhez tartozó hónapok dátumai
                } else if( $_SESSION['cash_flow_bontas'] == 'negyed' ) {
                    switch( $akt_honap ) {
                        case 1 : 
                        case 2 : 
                        case 3 :
                            $bontas_cim = $akt_ev.'<br>1.n.év';
                            if( !is_array($honapok[$bontas_cim]) ) {
                                $honapok[$bontas_cim] = array();
                            }
                            array_push( $honapok[$bontas_cim], $akt_honap_kiir );
                        break;
                        case 4 : 
                        case 5 : 
                        case 6 :
                            $bontas_cim = $akt_ev.'<br>2.n.év';
                            if( !is_array($honapok[$bontas_cim]) ) {
                                $honapok[$bontas_cim] = array();
                            }
                            array_push( $honapok[$bontas_cim], $akt_honap_kiir );
                        break;
                        case 7 : 
                        case 8 : 
                        case 9 :
                            $bontas_cim = $akt_ev.'<br>3.n.év';
                            if( !is_array($honapok[$bontas_cim]) ) {
                                $honapok[$bontas_cim] = array();
                            }
                            array_push( $honapok[$bontas_cim], $akt_honap_kiir );;
                        break;
                        case 10: 
                        case 11: 
                        case 12:
                            $bontas_cim = $akt_ev.'<br>4.n.év';
                            if( !is_array($honapok[$bontas_cim]) ) {
                                $honapok[$bontas_cim] = array();
                            }
                            array_push( $honapok[$bontas_cim], $akt_honap_kiir );
                        break;
                    }
                }
                $akt_honap++;
                if( $akt_honap == 13 ) {
                    $akt_honap = 1;
                    $akt_ev++;
                }
            }
            return $honapok;
        } else {
            return $honapok_szama;
        }
    }
    
}

// CSV export fejléc központosítva
function cash_flow_csv_export_elokeszit($fajlnev) {
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