<?php
require_once('../weblap.php');

// Ha egyik megtekintés joggal sem rendelkezünk
if( false /* Jogot kellene ide írni */ ) {
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
    if( $_POST['ev_tol'].'-'.$_POST['ho_tol']>=$_POST['ev_ig'].'-'.$_POST['ho_ig'] ) {
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
    $honapok_szama = get_honapok_szama($_POST['ev_ig'] .'-'.$_POST['ho_ig'], $_POST['ev_tol'].'-'.$_POST['ho_tol']);
    
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
        if( !$_SESSION['cash_flow_ev_tol'] ) {
            
            $szamla_html .= '<h1 class="f20">Kérem előbb válasszon időszakot és szállodákat,<br>hogy a cash-flow táblázatot elő lehessen állítani!</h1>';
            
        // Egyébként a táblázat kirajzolása
        } else {
            
            // X tengely előállítása
            $honapok = get_honapok_szama($_SESSION['cash_flow_ev_tol'] .'-'.$_SESSION['cash_flow_ho_tol'], $_SESSION['cash_flow_ev_ig'].'-'.$_SESSION['cash_flow_ho_ig'], 'tömbként');
            $honapok_fejlecbe = '';
            $lablec           = '';
            foreach( $honapok as $honap ) {
                $honapok_fejlecbe .= '<th>'.datum_kiir($honap).'</th>';
                $lablec           .= '<td></td>';
            }

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
                        <th class="ktgnem_kod fix mini1">Szálloda / Projekt / CF típus</th>
                        <th>Összesen</th>
                        '.$honapok_fejlecbe.'
                    </tr>
                    </thead>
                    <tbody>
            ');

            $dn = 'dn';
            if( isset($_GET['mind']) ) {
                $dn = '';
            }

            $szallodak = explode(',', $_SESSION['cash_flow_szallodak']);
            foreach ( $szallodak as $szalloda_id ) {

                $projekt_sorok = '';
                $szalloda_sum  = array();
                // Projektigénylések kilistázása, ha ez a pipa be volt pipálva
                if( $_SESSION['cash_flow_pri_egyutt'] ) {
                    $projekt_igenyek = $adatbazis_kapcsolat->s("`".get_db_tabla()."projektigenyles`.`recid`, `".get_db_tabla()."projektigenyles`.`ikt_szam` , `".get_db_tabla()."projektigenyles`.`nev`", 'projektigenyles', "`".get_db_tabla()."projektigenyles`.`telephely_id`='{$szalloda_id}' AND ISNULL(`".get_db_tabla()."projektkezeles`.`projektigenyles_recid`)", '', '', '', '',
                                                               "LEFT JOIN `".get_db_tabla()."projektkezeles`  ON (`".get_db_tabla()."projektkezeles`.`projektigenyles_recid`=`".get_db_tabla()."projektigenyles`.`recid`)");
                    foreach( $projekt_igenyek as $projekt_igeny ) {
                        
                        $projekt_igeny_sum    = array();
                        $tervezett_igeny_sum  = 0;
                        $pr_igeny_ossz_sum    = 0;
                        
                        $tervezett_igeny        = $adatbazis_kapcsolat->s('SUBSTRING(`datum`, 1, 7) as `datum_honapig`, SUM(`osszeg`)', 'cash_flow', "`apa_recid`='{$projekt_igeny['recid']}' AND `datum`>='{$_SESSION['cash_flow_ev_tol']}-{$_SESSION['cash_flow_ho_tol']}-01' AND `datum`<='{$_SESSION['cash_flow_ev_ig']}-{$_SESSION['cash_flow_ho_ig']}-31'", '', '', 'datum_honapig');
                        $tervezett_igeny_html   = '';
                        foreach( $honapok as $honap ) {
                            $tervezett_osszeg = 0;
                            foreach( $tervezett_igeny as $tervez ) {
                                if( $tervez['datum_honapig'] == $honap ) {
                                    $tervezett_osszeg            = $tervez['SUM(`osszeg`)'];
                                    $projekt_igeny_sum[$honap]  += $tervez['SUM(`osszeg`)'];
                                    $tervezett_igeny_sum        += $tervez['SUM(`osszeg`)'];
                                    break;
                                }
                            }
                            $tervezett_igeny_html .= '<td class="r">'.number_format($tervezett_osszeg, 0, '', ' ').'</td>';
                        }
                        
                        // Projektigénylés szumma sor
                        $projekt_igeny_sum_html = '';
                        foreach( $honapok as $honap ) {
                            $szalloda_sum[$honap]   += $projekt_igeny_sum[$honap];
                            $pr_igeny_ossz_sum      += $projekt_igeny_sum[$honap];
                            $projekt_igeny_sum_html .= '<td class="r">'.number_format($projekt_igeny_sum[$honap], 0, '', ' ').'</td>';
                        }
                        $projekt_sorok .= nl2('
                        <tr id="projekt_igeny_'.$projekt_igeny['recid'].'" class="'.$dn.' szalloda_'.$szalloda_id.'">
                            <td class="fix cf1 kibont f_projekt_kibont wrap"><b>'.$projekt_igeny['ikt_szam'].'</b><br>'.$projekt_igeny['nev'].'</td>
                            <td class="r">'.number_format($pr_igeny_ossz_sum, 0, '', ' ').'</td>
                            '.$projekt_igeny_sum_html.'
                        </tr>
                        <tr id="tervezett_igeny_'.$projekt_igeny['recid'].'" class="'.$dn.' projekt_igeny_'.$projekt_igeny['recid'].'">
                            <td class="fix cf1"><a href="projektigenyles.php?id='.$projekt_igeny['recid'].'&amp;cash_flow&amp;mezo=ikt_szam&amp;op=equal&amp;felt='.$projekt_igeny['ikt_szam'].'" target="_blank">Tervezett</a></td>
                            <td class="r">'.number_format($tervezett_sum, 0, '', ' ').'</td>
                            '.$tervezett_igeny_html.'
                        </tr>
                        ');
                    }
                    
                }
                
                // Projektek kilistázása
                $projektek = $adatbazis_kapcsolat->s('recid ikt_szam nev pfelelos', 'projektkezeles', "`telephely_id`='{$szalloda_id}'");
                foreach( $projektek as $projekt ) {

                    $projekt_sum    = array();
                    $tervezett_sum  = 0;
                    $szerzodes_sum  = 0;
                    $szamlazott_sum = 0;
                    $pr_ossz_sum    = 0;

                    // Tervezett összegek összeadása
                    $tervezett = $adatbazis_kapcsolat->s('SUBSTRING(`datum`, 1, 7) as `datum_honapig`, SUM(`terv`)', 'projektkezeles_cashflow', "`projektkezeles_recid`='{$projekt['recid']}' AND `datum`>='{$_SESSION['cash_flow_ev_tol']}-{$_SESSION['cash_flow_ho_tol']}-01' AND `datum`<='{$_SESSION['cash_flow_ev_ig']}-{$_SESSION['cash_flow_ho_ig']}-31'", '', '', 'datum_honapig');
                    $tervezett_html = '';
                    foreach( $honapok as $honap ) {
                        $tervezett_osszeg = 0;
                        foreach( $tervezett as $tervez ) {
                            if( $tervez['datum_honapig'] == $honap ) {
                                $tervezett_osszeg      = $tervez['SUM(`terv`)'];
                                $projekt_sum[$honap]  += $tervez['SUM(`terv`)'];
                                $tervezett_sum        += $tervez['SUM(`terv`)'];
                                break;
                            }
                        }
                        $tervezett_html .= '<td class="r">'.number_format($tervezett_osszeg, 0, '', ' ').'</td>';
                    }

                    // Szerződött összegek összeadása
                    $szerzodott      = $adatbazis_kapcsolat->s('SUBSTRING(`varh_erk`, 1, 7) as `datum_honapig`, SUM(`varh_ert`)', 'projektszerzodes_tetel', "`teljesites`='0000-00-00' AND `projektszerzodes_recid` IN ( SELECT `recid` FROM `".get_db_tabla()."projektszerzodes` WHERE `pr_szam`='{$projekt['ikt_szam']}') AND `varh_erk`>='{$_SESSION['cash_flow_ev_tol']}-{$_SESSION['cash_flow_ho_tol']}-31' AND `varh_erk`<='{$_SESSION['cash_flow_ev_ig']}-{$_SESSION['cash_flow_ho_ig']}-01'", '', '', 'datum_honapig');
                    $szerzodott_html = '';
                    foreach( $honapok as $honap ) {
                        $szerzodott_osszeg = 0;
                        foreach( $szerzodott as $szerz ) {
                            if( $szerz['datum_honapig'] == $honap ) {
                                $szerzodott_osszeg     = $szerz['SUM(`varh_ert`)'];
                                $projekt_sum[$honap]  += $szerz['SUM(`varh_ert`)'];
                                $szerzodes_sum        += $szerz['SUM(`varh_ert`)'];
                                break;
                            }
                        }
                        $szerzodott_html .= '<td class="r">'.number_format($szerzodott_osszeg, 0, '', ' ').'</td>';
                    }


                    $szamlazott      = $adatbazis_kapcsolat->s('SUBSTRING(`teljesites`, 1, 7) as `datum_honapig`, SUM(`varh_ert`)', 'projektszerzodes_tetel', "`projektszerzodes_recid` IN ( SELECT `recid` FROM `".get_db_tabla()."projektszerzodes` WHERE `pr_szam`='{$projekt['ikt_szam']}') AND `teljesites`>='{$_SESSION['cash_flow_ev_tol']}-{$_SESSION['cash_flow_ho_tol']}-01' AND `teljesites`<='{$_SESSION['cash_flow_ev_ig']}-{$_SESSION['cash_flow_ho_ig']}-31'", '', '', 'datum_honapig');
                    $szamlazott_html = '';
                    foreach( $honapok as $honap ) {
                        $szamlazott_osszeg = 0;
                        foreach( $szamlazott as $szamlaz ) {
                            if( $szamlaz['datum_honapig'] == $honap ) {
                                $szamlazott_osszeg     = $szamlaz['SUM(`varh_ert`)'];
                                $projekt_sum[$honap]  += $szamlaz['SUM(`varh_ert`)'];
                                $szamlazott_sum       += $szamlaz['SUM(`varh_ert`)'];
                                break;
                            }
                        }
                        $szamlazott_html .= '<td class="r">'.number_format($szamlazott_osszeg, 0, '', ' ').'</td>';
                    }

                    // Projektek szumma sor
                    $projekt_sum_html = '';
                    foreach( $honapok as $honap ) {
                        $szalloda_sum[$honap] += $projekt_sum[$honap];
                        $pr_ossz_sum          += $projekt_sum[$honap];
                        $projekt_sum_html .= '<td class="r">'.number_format($projekt_sum[$honap], 0, '', ' ').'</td>';
                    }

                    $projekt_sorok .= nl2('
                    <tr id="projekt_'.$projekt['recid'].'" class="'.$dn.' szalloda_'.$szalloda_id.'">
                        <td class="fix cf1 kibont f_projekt_kibont wrap"><b>'.$projekt['ikt_szam'].'</b> '.getErtek('teljes_nev', 'login', 'recid', $projekt['pfelelos']).'<br>'.$projekt['nev'].'</td>
                        <td class="r">'.number_format($pr_ossz_sum, 0, '', ' ').'</td>
                        '.$projekt_sum_html.'
                    </tr>
                    <tr id="tervezett_'.$projekt['recid'].'" class="'.$dn.' projekt_'.$projekt['recid'].'">
                        <td class="fix cf1"><a href="projektkezeles.php?id='.$projekt['recid'].'&amp;cash_flow&amp;mezo=ikt_szam&amp;op=equal&amp;felt='.$projekt['ikt_szam'].'" target="_blank">Tervezett</a></td>
                        <td class="r">'.number_format($tervezett_sum, 0, '', ' ').'</td>
                        '.$tervezett_html.'
                    </tr>
                    <tr id="szerzodott_'.$projekt['recid'].'" class="'.$dn.' projekt_'.$projekt['recid'].'">
                        <td class="fix cf1"><a href="javascript:alert2(\'Szerződött\')">Szerződött</a></td>
                        <td class="r">'.number_format($szerzodes_sum, 0, '', ' ').'</td>
                        '.$szerzodott_html.'
                    </tr>
                    <tr id="szamlazott_'.$projekt['recid'].'" class="'.$dn.' projekt_'.$projekt['recid'].'">
                        <td class="fix cf1"><a href="javascript:alert2(\'számlázott\')">Számlázott</a></td>
                        <td class="r">'.number_format($szamlazott_sum, 0, '', ' ').'</td>
                        '.$szamlazott_html.'
                    </tr>
                    ');
                }

                $szalloda_ossz_sum = 0;
                $szalloda_sum_html = '';
                foreach( $honapok as $honap ) {
                    $szalloda_ossz_sum += $szalloda_sum[$honap];
                    $szalloda_sum_html .= '<td class="r">'.number_format($szalloda_sum[$honap], 0, '', ' ').'</td>';
                }

                $szamla_html .= nl2('
                <tr id="szalloda_'.$szalloda_id.'">
                    <td class="fix cf1 kibont f_szalloda_kibont">'.getSzallodaNev($szalloda_id).'</td>
                    <td class="r">'.number_format($szalloda_ossz_sum, 0, '', ' ').'</td>
                    '.$szalloda_sum_html.'
                </tr>
                ').$projekt_sorok;

            }

            $szamla_html .= nl2('</tbody>
                <tfoot class="nowrap">
                    <td></td>
                    <td></td>
                    '.$lablec.'
                </tfoot>
            </table>
        </div>
                ');
        }
        
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
    
    if( $html ) {
        $html = '<b>Hozzáadott szállodák:</b>'.$html;
    }
    
    return $html;
}


// Két dátumot megadva (napok nélkül), visszaaja a kettő közti dátumok számát
function get_honapok_szama($ev_ho_tol, $ev_ho_ig, $tombkent = false) {
    
    if( $ev_ho_tol != '-' && $ev_ho_ig != '-' ) {
    
        $d1 = new DateTime($ev_ho_tol.'-01');
        $d2 = $d1 -> diff( new DateTime($ev_ho_ig.'-01') );

        $akt_ev    = $_SESSION['cash_flow_ev_tol'];
        $akt_honap = $_SESSION['cash_flow_ho_tol'];

        $honapok_szama  = ($d2 -> y*12) + $d2 -> m + 1;
        $honapok        = array();
        if( $tombkent ) {
            for ( $i = 0; $i <= $honapok_szama; $i++ ) {
                array_push( $honapok,  $akt_ev.'-'.sprintf("%02d", $akt_honap));
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