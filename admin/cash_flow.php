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
    
    $html = '';
    $szamlazott_tetelek = $adatbazis_kapcsolat->s('`projektszerzodes_recid`, `varh_erk`, `varh_ert`', 'projektszerzodes_tetel', "`teljesites`='0000-00-00' AND `projektszerzodes_recid` IN ( SELECT `recid` FROM `".get_db_tabla()."projektszerzodes` WHERE `pr_szam`='{$_POST['pr_szam']}') AND `varh_erk`>='{$_SESSION['cash_flow_ev_tol']}-{$_SESSION['cash_flow_ho_tol']}-01' AND `varh_erk`<='{$_SESSION['cash_flow_ev_ig']}-{$_SESSION['cash_flow_ho_ig']}-31'", 'varh_erk');
    if( $szamlazott_tetelek ) {
        $html = nl2('
                    <table class="csikos">
                    <tr>
                        <th>Szerződés szám</th>
                        <th class="r">Várható érték</th>
                        <th class="r">Várható érkezés</th>
                    </tr>
                ');
        foreach( $szamlazott_tetelek as $szamlazott ) {
            $szerzodes = $adatbazis_kapcsolat->s2('recid sz_szam', 'projektszerzodes', "`recid`='{$szamlazott['projektszerzodes_recid']}'");
            $html .= nl2('
                        <tr>
                            <td class="l"><a href="projektszerzodes.php?id='.$szerzodes['recid'].'&amp;tetel&amp;mezo=sz_szam&amp;op=equal&amp;felt='.$szerzodes['sz_szam'].'" target="_blank">'.$szerzodes['sz_szam'].'</a></td>
                            <td class="r">'.number_format($szamlazott['varh_ert'], 0, ',', ' ').'</td>
                            <td class="r">'.datum_kiir($szamlazott['varh_erk']).'</td>
                        </tr>
                        ');
        }
        $html .= '</table>';
    } else {
        $html = '<div class="b">Nem található olyan szerződés tétel a '.$_POST['pr_szam'].' számú projekthez, ami nincs teljesítve,<br>és a várható érkezés dátuma '.$_SESSION['cash_flow_ev_tol'].'.'.$_SESSION['cash_flow_ho_tol'].'.01 és '.$_SESSION['cash_flow_ev_ig'].'.'.$_SESSION['cash_flow_ho_ig'].'.31 közötti volna.</div>';
    }
    
    echo json_encode(array(
                        'html' => $html
                    ));

    $weblap->leallit();
    exit();
}

// Számla tételek kiíratása popup-ba
if( isset($_POST['cash_flow_szamlak_mutat']) ) {
    
    $html = '';
    $szamlazott_tetelek = $adatbazis_kapcsolat->s('`projektszamla_recid`, `telj_dt`, `netto_ert`', 'projektszamla_tetel', "`projektszerzodes_tetel_recid`!=0 AND `projektszamla_recid` IN ( SELECT `recid` FROM `".get_db_tabla()."projektszamla` WHERE `pr_szam`='{$_POST['pr_szam']}') AND `".get_db_tabla()."projektszamla`.`telj_dt`>='{$_SESSION['cash_flow_ev_tol']}-{$_SESSION['cash_flow_ho_tol']}-01' AND `".get_db_tabla()."projektszamla`.`telj_dt`<='{$_SESSION['cash_flow_ev_ig']}-{$_SESSION['cash_flow_ho_ig']}-31'", "`".get_db_tabla()."projektszamla`.`telj_dt`", '', '', '',
                          "LEFT JOIN `".get_db_tabla()."projektszamla` ON `".get_db_tabla()."projektszamla`.`recid`=`".get_db_tabla()."projektszamla_tetel`.`projektszamla_recid`");
    if( $szamlazott_tetelek ) {
        $html = nl2('
                    <table class="csikos">
                    <tr>
                        <th>Számla szám</th>
                        <th class="r">Várható érték</th>
                        <th class="r">Teljesítés</th>
                    </tr>
                ');
        foreach( $szamlazott_tetelek as $szamlazott ) {
            $szamla = $adatbazis_kapcsolat->s2('recid szla_szam', 'projektszamla', "`recid`='{$szamlazott['projektszamla_recid']}'");
            $html .= nl2('
                        <tr>
                            <td class="l"><a href="projektszamla.php?id='.$szamla['recid'].'&amp;tetel&amp;mezo=szla_szam&amp;op=equal&amp;felt='.$szamla['szla_szam'].'" target="_blank">'.$szamla['szla_szam'].'</a></td>
                            <td class="r">'.number_format($szamlazott['netto_ert'], 0, ',', ' ').'</td>
                            <td class="r">'.datum_kiir($szamlazott['telj_dt']).'</td>
                        </tr>
                        ');
        }
        $html .= '</table>';
    } else {
        $html = '<div class="b">Nem található olyan szerződés tétel a '.$_POST['pr_szam'].' számú projekthez,<br>ahol a teljesítés dátuma '.$_SESSION['cash_flow_ev_tol'].'.'.$_SESSION['cash_flow_ho_tol'].'.01 és '.$_SESSION['cash_flow_ev_ig'].'.'.$_SESSION['cash_flow_ho_ig'].'.31 közötti volna.</div>';
    }
    
    echo json_encode(array(
                        'html' => $html
                    ));

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
            
            $datum_hossz = 7; // Hónappontos
            if( $_SESSION['cash_flow_bontas'] == 'heti' ) {
                $datum_hossz = 10; // Nappontos - heti bontáshoz már szükségünk van a napokra is, mert egy heti bontásbna nappontos dátumok vannak.
            }
            
            // Hónapokok kiszámítása, és a fejléc előállítása
            $bontas = get_honapok_szama($_SESSION['cash_flow_ev_tol'], $_SESSION['cash_flow_ho_tol'], $_SESSION['cash_flow_ev_ig'], $_SESSION['cash_flow_ho_ig'], 'tömbként');
            $cimek_fejlecbe = '';
            foreach( $bontas as $cim => $honapok ) {
                $cimek_fejlecbe .= '<th>'.datum_kiir($cim).'</th>';
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

                $projekt_sorok = '';
                $szalloda_sum  = array();
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
                            $tervezett_igeny_html .= '<td class="r">'.number_format($tervezett_osszeg, 0, '', ' ').'</td>';
                        }
                        
                        // Projektigénylés szumma sor
                        $projekt_igeny_sum_html = '';
                        foreach( $bontas as $cim => $honapok ) {
                            $projekt_igeny_ossz = 0;
                            foreach ( $honapok as $honap ) {
                                $projekt_igeny_ossz     += $projekt_igeny_sum[$honap];
                                $szalloda_sum[$honap]   += $projekt_igeny_sum[$honap];
                                $pr_igeny_ossz_sum      += $projekt_igeny_sum[$honap];
                            }
                            $projekt_igeny_sum_html .= '<td class="r">'.number_format($projekt_igeny_ossz, 0, '', ' ').'</td>';
                        }
                        
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
                    }
                    
                }
                
                // Projektek kilistázása
                // Régi: $projektek = $adatbazis_kapcsolat->s('recid ikt_szam nev pfelelos', 'projektkezeles', "`telephely_id`='{$szalloda_id}'");
                $projektek = $adatbazis_kapcsolat->s('`'.get_db_tabla().'projektkezeles`.`recid`, `'.get_db_tabla().'projektkezeles`.`ikt_szam`, `'.get_db_tabla().'projektkezeles`.`nev`, `'.get_db_tabla().'projektkezeles`.`pfelelos`', 'projektkezeles', "`".get_db_tabla()."projektkezeles`.`telephely_id`='{$szalloda_id}' AND 
                                                    (
                                                    (`".get_db_tabla()."projektkezeles_cashflow`.`datum`>='{$_SESSION['cash_flow_ev_tol']}-{$_SESSION['cash_flow_ho_tol']}-01' AND `".get_db_tabla()."projektkezeles_cashflow`.`datum`<='{$_SESSION['cash_flow_ev_ig']}-{$_SESSION['cash_flow_ho_ig']}-31') OR
                                                    (`".get_db_tabla()."projektszerzodes_tetel`.`teljesites`>='{$_SESSION['cash_flow_ev_tol']}-{$_SESSION['cash_flow_ho_tol']}-01' AND `".get_db_tabla()."projektszerzodes_tetel`.`teljesites`<='{$_SESSION['cash_flow_ev_ig']}-{$_SESSION['cash_flow_ho_ig']}-31') OR
                                                     `".get_db_tabla()."projektszerzodes_tetel`.`teljesites`='0000-00-00'
                                                    )
                                                    ", '', '', "`".get_db_tabla()."projektkezeles`.`ikt_szam`", '',
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

                    // Tervezett összegek összeadása
                    $tervezett = $adatbazis_kapcsolat->s('SUBSTRING(`datum`, 1, '.$datum_hossz.') as `datum_honapig`, SUM(`terv`)', 'projektkezeles_cashflow', "`projektkezeles_recid`='{$projekt['recid']}' AND `datum`>='{$_SESSION['cash_flow_ev_tol']}-{$_SESSION['cash_flow_ho_tol']}-01' AND `datum`<='{$_SESSION['cash_flow_ev_ig']}-{$_SESSION['cash_flow_ho_ig']}-31'", '', '', 'datum_honapig');
                    $tervezett_html = '';
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
                    }

                    // Szerződött összegek összeadása
                    $szerzodott      = $adatbazis_kapcsolat->s('SUBSTRING(`varh_erk`, 1, '.$datum_hossz.') as `datum_honapig`, SUM(`varh_ert`)', 'projektszerzodes_tetel', "`teljesites`='0000-00-00' AND `projektszerzodes_recid` IN ( SELECT `recid` FROM `".get_db_tabla()."projektszerzodes` WHERE `pr_szam`='{$projekt['ikt_szam']}') AND `varh_erk`>='{$_SESSION['cash_flow_ev_tol']}-{$_SESSION['cash_flow_ho_tol']}-01' AND `varh_erk`<='{$_SESSION['cash_flow_ev_ig']}-{$_SESSION['cash_flow_ho_ig']}-31'", '', '', 'datum_honapig');
                    $szerzodott_html = '';
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
                    }


                    $szamlazott      = $adatbazis_kapcsolat->s('SUBSTRING(`teljesites`, 1, '.$datum_hossz.') as `datum_honapig`, SUM(`varh_ert`)', 'projektszerzodes_tetel', "`projektszerzodes_recid` IN ( SELECT `recid` FROM `".get_db_tabla()."projektszerzodes` WHERE `pr_szam`='{$projekt['ikt_szam']}') AND `teljesites`>='{$_SESSION['cash_flow_ev_tol']}-{$_SESSION['cash_flow_ho_tol']}-01' AND `teljesites`<='{$_SESSION['cash_flow_ev_ig']}-{$_SESSION['cash_flow_ho_ig']}-31'", '', '', 'datum_honapig');
                    $szamlazott_html = '';
                    foreach( $bontas as $cim => $honapok ) {
                        $szamlazott_osszeg = 0;
                        foreach ( $honapok as $honap ) {
                            foreach( $szamlazott as $szamlaz ) {
                                if( $szamlaz['datum_honapig'] == $honap ) {
                                    $szamlazott_osszeg    += $szamlaz['SUM(`varh_ert`)'];
                                    $projekt_sum[$honap]  += $szamlaz['SUM(`varh_ert`)'];
                                    $szamlazott_sum       += $szamlaz['SUM(`varh_ert`)'];
                                    break;
                                }
                            }
                        }
                        $szamlazott_html .= '<td class="r">'.number_format($szamlazott_osszeg, 0, '', ' ').'</td>';
                    }
                    
                    // Ez kell-e?
                    // Egy sort csak akkor írunk ki, ha legalább egy helyen van is benne érték
                    //if( $pr_ossz_sum || $tervezett_sum || $szerzodes_sum || $szamlazott_sum ) {

                        // Projektek szumma sor
                        $projekt_sum_html = '';
                        foreach( $bontas as $cim => $honapok ) {
                            $pr_ossz = 0;
                            foreach ( $honapok as $honap ) {
                                $pr_ossz              += $projekt_sum[$honap];
                                $szalloda_sum[$honap] += $projekt_sum[$honap];
                                $pr_ossz_sum          += $projekt_sum[$honap];
                            }
                            $projekt_sum_html .= '<td class="r">'.number_format($pr_ossz, 0, '', ' ').'</td>';
                        }

                        $projekt_sorok .= nl2('
                        <tr id="projekt_'.$projekt['recid'].'" class="projekt_sor '.$dn.' szalloda_'.$szalloda_id.'">
                            <td class="fix cf1 kibont f_projekt_kibont wrap"><b>'.$projekt['ikt_szam'].'</b> '.getErtek('teljes_nev', 'login', 'recid', $projekt['pfelelos']).'<br>'.$projekt['nev'].'</td>
                            <td class="r">'.number_format($pr_ossz_sum, 0, '', ' ').'</td>
                            '.$projekt_sum_html.'
                        </tr>
                        <tr id="tervezett_'.$projekt['recid'].'" class="tervezett_sor '.$dn.' projekt_'.$projekt['recid'].'">
                            <td class="fix cf1"><a href="projektkezeles.php?id='.$projekt['recid'].'&amp;cash_flow&amp;mezo=ikt_szam&amp;op=equal&amp;felt='.$projekt['ikt_szam'].'" target="_blank">Tervezett</a></td>
                            <td class="r">'.number_format($tervezett_sum, 0, '', ' ').'</td>
                            '.$tervezett_html.'
                        </tr>
                        <tr id="szerzodott_'.$projekt['recid'].'" class="szerzodott_sor '.$dn.' projekt_'.$projekt['recid'].'">
                            <td class="fix cf1"><a href="javascript:cash_flow_pr_szam=\''.$projekt['ikt_szam'].'\'; csoportMuvelet(\'cash_flow_szerzodesek_mutat\'); void(0);">Szerződött</a></td>
                            <td class="r">'.number_format($szerzodes_sum, 0, '', ' ').'</td>
                            '.$szerzodott_html.'
                        </tr>
                        <tr id="szamlazott_'.$projekt['recid'].'" class="szamlazott_sor '.$dn.' projekt_'.$projekt['recid'].'">
                            <td class="fix cf1"><a href="javascript:cash_flow_pr_szam=\''.$projekt['ikt_szam'].'\'; csoportMuvelet(\'cash_flow_szamlak_mutat\'); void(0);">Számlázott</a></td>
                            <td class="r">'.number_format($szamlazott_sum, 0, '', ' ').'</td>
                            '.$szamlazott_html.'
                        </tr>
                        ');
//                    }
                }

                $szalloda_ossz_sum = 0;
                $szalloda_sum_html = '';
                foreach( $bontas as $cim => $honapok ) {
                    $nagy_osszesito = 0;
                    foreach ( $honapok as $honap ) {
                        $nagy_osszesito             += $szalloda_sum[$honap];
                        $szalloda_ossz_sum          += $szalloda_sum[$honap];
                        $nagy_osszesito_sum[$honap] += $szalloda_sum[$honap];
                    }
                    $szalloda_sum_html  .= '<td class="r">'.number_format($nagy_osszesito, 0, '', ' ').'</td>';
                }

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
                
                $szetosztasra_varo = $adatbazis_kapcsolat->s('SUBSTRING(`telj_dt`, 1, '.$datum_hossz.') as `datum_honapig`, SUM(`netto_ert`)', 'projektszamla_tetel', "`projektszerzodes_tetel_recid`='0' AND `telj_dt`>='{$_SESSION['cash_flow_ev_tol']}-{$_SESSION['cash_flow_ho_tol']}-01' AND `telj_dt`<='{$_SESSION['cash_flow_ev_ig']}-{$_SESSION['cash_flow_ho_ig']}-31'", '', '', 'datum_honapig', '',
                                                             'LEFT JOIN `'.get_db_tabla().'projektszamla` ON `'.get_db_tabla().'projektszamla`.`recid`=`'.get_db_tabla().'projektszamla_tetel`.`projektszamla_recid`');
                $szetosztasra_varo_html = '';
                $szetosztasra_varo_sum  = 0;
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
                }
                
                $szamla_html .= nl2('
                <tr id="szetosztasra_varo_szla_tetelek">
                    <td class="fix cf1">Szétosztásra váró számla tételek</td>
                    <td class="r">'.number_format($szetosztasra_varo_sum, 0, '', ' ').'</td>
                    '.$szetosztasra_varo_html.'
                </tr>
                ');
            }
            
            // Teljes összesítés
            $osszesito_html = '';
            $vegso_nagy_sum = 0;
            foreach( $bontas as $cim => $honapok ) {
                $vegso_nagy_ossz = 0;
                foreach ( $honapok as $honap ) {
                    $vegso_nagy_ossz    += $nagy_osszesito_sum[$honap];
                    $vegso_nagy_sum     += $nagy_osszesito_sum[$honap];
                }
                $osszesito_html .= '<td class="r">'.number_format($vegso_nagy_ossz, 0, '', ' ').'</td>';
            }
            
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