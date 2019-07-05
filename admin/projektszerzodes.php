<?php
require_once('../weblap.php');

if( !_jog(JOG_PRKEZEL_SZERZODES_OLVAS) && !_jog(JOG_PR_SAJAT_SZERZ_MEGTEKINT) ) {
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
$akt_tomb               = $projektszerzodes_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'projekt szerződést';   // Hibaüzenetkor: Biztosan törlöd a $szoveg?

// Dokumentumok fülhöz kell majd
$tobb_kep = true;
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

// Lekérdezzük, hogy van-e a projekt szerződés tételei között olyan, amihez van kapcsolva számla tétel
if( isset($_POST['van_kapcsolt_szamla']) ) {

    // Új feltöltéskor ezt nem kell ellenőrizni
    if( !$_POST['uj'] || $_POST['uj'] == 'false' ) {
        $szerzodes_tetelek = $adatbazis_kapcsolat->s('recid', 'projektszerzodes_tetel', "`projektszerzodes_recid`='{$_POST['recid']}'");
        foreach ( $szerzodes_tetelek as $tetel ) {
            if( $adatbazis_kapcsolat->s('recid', 'projektszamla_tetel', "`projektszerzodes_tetel_recid`='{$tetel['recid']}'") ){
                echo json_encode( array(
                                    'hiba'  => 'A szerződésnek van olyan tétele, amihez van számla tétel kapcsolva, így a projekt szám nem törölhető!',
                                    'title' => 'Hiba',
                                ) );
                $weblap -> leallit();
                exit;
            }
        }
    }
    
    echo json_encode( array(
                        'nincs'  => 'nincs csatolt számla',
                    ) );
    $weblap -> leallit();
    exit;
}

// Projekt táblázat betöltése
if( isset($_POST['get_projekt_tabla']) ) {

    // Ha nincs új feltöltés jogunk, akkor kilépünk
    if( !get_projekt_szerzodes_kezel_jog($_POST['recid']) ) {
        echo json_encode( array(
                            'hiba'  => 'Önnek nincs joga projekt szerződést kezelni!',
                            'title' => 'Hiba',
                        ) );
        $weblap -> leallit();
        exit;
    }
    
    $html = '';

    // Rendezés beállítása
    $join  = ' JOIN `'.get_db_tabla().'telephely` ON `'.get_db_tabla().'projektkezeles`.`telephely_id`=`'.get_db_tabla().'telephely`.`id`';
    switch ( $_POST['order'] ) {
        case 'pr_szam'  : $order = '`'.get_db_tabla().'telephely`.`nev`,        `'.get_db_tabla().'projektkezeles`.`jkolt`';    $szalloda_selected  = ' selected';  break;
        case 'jkolt'    : $order = '`'.get_db_tabla().'projektkezeles`.`jkolt`, `'.get_db_tabla().'telephely`.`nev`';           $bkolt_selected     = ' selected';  break;
        case 'tipus'    : $order = '`'.get_db_tabla().'projektkezeles`.`tipus`, `'.get_db_tabla().'projektkezeles`.`jkolt`';    $tipus_selected     = ' selected';  break;
        default         : $order = '`'.get_db_tabla().'projektkezeles`.`ikt_szam`';                                                                                  break;
    }

    $prek = $adatbazis_kapcsolat->s('
                                        `'.get_db_tabla().'projektkezeles`.`recid`,
                                        `'.get_db_tabla().'projektkezeles`.`ikt_szam`,
                                        `'.get_db_tabla().'projektkezeles`.`nev`,
                                        `'.get_db_tabla().'projektkezeles`.`tipus`,
                                        `'.get_db_tabla().'projektkezeles`.`stat_id`,
                                        `'.get_db_tabla().'projektkezeles`.`jkolt`,
                                        `'.get_db_tabla().'projektkezeles`.`pfelelos`,
                                        `'.get_db_tabla().'projektkezeles`.`telephely_id`,
                                        `'.get_db_tabla().'projektkezeles`.`insert_dt`
                                            ',
                                        'projektkezeles',
                                        "`telephely_id`='{$_POST['telephely_id']}'", $order, '', '', '', $join);
// echo $adatbazis_kapcsolat->gp() . '<br>';
    $i = 1;
    foreach ( $prek as $pr ) {

        // Szálloda
        $szalloda = $adatbazis_kapcsolat->s2('nev', 'telephely', "`id`='{$pr['telephely_id']}'");
        
        // Tipus
        require_once 'selectek.php';
        foreach ( $select_pri_tipus as $tip ) {
            if( $tip['value'] == $pr['tipus'] ) {
                $tipus = $tip['name'];
            }
        }
        if( !$pr['tipus'] ) {
            $tipus = '-';
        }

        $pfelelos_neve = getErtek('teljes_nev', 'login', 'recid', $pr['pfelelos']);
        
        $html .= '
            <tr class="kijSor" data-i="'.$i.'" data-recid="'.$pr['recid'].'" data-ikt_szam="'.$pr['ikt_szam'].'" data-felelos="'.$pr['pfelelos'].'">
                <td class="l" style="padding: 0 0.5rem">
                    '.$szalloda['nev'].'
                    <div class="dn pfelelos_txt">'.$pfelelos_neve.'</div>
                    <div class="dn pfelelos_txt nev"><a href="projektkezeles.php?id=x&amp;modosit&amp;mezo=ikt_szam&amp;op=equal&amp;felt='.$pr['ikt_szam'].'" target="_blank">'.$pr['nev'].'</a></div>
                </td>
                <td class="l" style="padding: 0 0.5rem">'.$pr['ikt_szam'].'</td>
                <td class="l" style="padding: 0 0.5rem">'.$pr['nev'].'</td>
                <td class="l" style="padding: 0 0.5rem">'.$tipus.'</td>
                <td class="r nowrap" style="padding: 0 1rem">'.number_format($pr['jkolt'], 0, '', ' ').'</td>
                <td style="padding: 0 1rem">'.datum_kiir($pr['insert_dt']).'</td>
            </tr>
            ';
        $i++;
    }

    if( $html ) {

        // Táblázat rendezhető: szálloda + becsült költség, becsült költség + szálloda, típus + becsült költség. Ezek legyenek egy lenyílóból kiválaszthatóak.
        $html = '
            Rendezés:
            <select onchange="get_projekt_tabla(this.value)" style="margin-bottom: 2rem;">
                <option>projekt szám</option>
                <option value="jkolt"'.$szalloda_selected.'>szálloda + jóváhagyott keret</option>
                <option value="jkolt"'.$bkolt_selected.'>jóváhagyott keret + szálloda</option>
                <option value="tipus"'.$tipus_selected.'>típus + jóváhagyott keret</option>
            </select>
            <style type="text/css">
                #popup div.popup_tart table {
                    margin-top: 7rem;
                }
                table.fix_fejlec thead {
                    position: fixed;
                    margin-top: -7rem;
                    width: 90%;
                }
                table.fix_fejlec thead tr th:nth-of-type(1), table.fix_fejlec tbody tr td:nth-of-type(1) {
                    width: 8rem;
                }
                table.fix_fejlec thead tr th:nth-of-type(2), table.fix_fejlec tbody tr td:nth-of-type(2) {
                    width: 8rem;
                }
                table.fix_fejlec thead tr th:nth-of-type(3), table.fix_fejlec tbody tr td:nth-of-type(3) {
                    width: 30rem;
                }
                table.fix_fejlec thead tr th:nth-of-type(4), table.fix_fejlec tbody tr td:nth-of-type(4) {
                    width: 10rem;
                }
                table.fix_fejlec thead tr th:nth-of-type(5), table.fix_fejlec tbody tr td:nth-of-type(5) {
                    width: 13rem;
                }
                table.fix_fejlec thead tr th:nth-of-type(6), table.fix_fejlec tbody tr td:nth-of-type(6) {
                    width: 14rem;
                }
                table.fix_fejlec tbody {
                    margin-top: 10rem;
                }
            </style>
            <div style="max-height: 40rem; overflow-y: auto; overflow-x: none;">
                <table class="csikos v2 kijCont csak_egy_kijelolheto fix_fejlec" id="prkezelesek" style="cursor: default;">
                <thead>
                <tr>
                    <th>
                        Szálloda
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prkezelesek tbody\'), this.value, 1)">
                    </th>
                    <th>
                        Projekt szám
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prkezelesek tbody\'), this.value, 2)">
                    </th>
                    <th>
                        Név
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prkezelesek tbody\'), this.value, 3)">
                    </th>
                    <th>
                        Típus
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prkezelesek tbody\'), this.value, 4)">
                    </th>
                    <th>
                        Jóváhagyott keret ('.get_penznem().')
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prkezelesek tbody\'), this.value, 5)">
                    </th>
                    <th>
                        Létrehozás dátuma
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prkezelesek tbody\'), this.value, 6)">
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
        $html = '<script>popupRejt(); setTimeout(function(){ popupZar(); alert2(\'Nem található projekt!\', \'\', \'Hiba\'); }, 10);</script>';
    }

    echo json_encode(
            array( 'html' => nl2($html) )
            );

    $weblap->leallit();
    exit();
}

// Tétel sorok mentése
if( isset($_POST['tetel_sorok_ment']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Kapott tömb indexeit cseréljük beszédesebb változókra
    $recid_adat    = 0;
    $targy_adat    = 1;
    $varh_erk_adat = 2;
    $varh_ert_adat = 3;
    
    $recid              = $_POST['recid'];
    $szerzodes_osszege  = str_replace(' ', '', $_POST['szerzodes_osszege']);
    $_POST['adatok']    = json_decode( $_POST['adatok'] );
    
    // Deviza esetén tört számokkal is számolunk
    $devizas   = false;
    $tizedesek = 0;
    $db_mezo   = 'varh_ert';
    $session_mezo = 'pr_szer_tetel_varh_ert';
    if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
        $devizas   = true;
        $tizedesek = 2;
        $db_mezo   = 'dev_net';
        $session_mezo = 'pr_szer_tetel_dev_net';
    }
    
    $tetelek_osszege = '';
    foreach ( $_POST['adatok'][$varh_ert_adat][1] as $ertek ) {
        $ertek = str_replace(array(' ', ','), array('', '.'), $ertek);
        $tetelek_osszege += $ertek;
    }

    // Ha nincs egy sornyi tételünk sem, akkor hibaüzenet
    if( !$_POST['adatok'][$targy_adat][1] ) {
        echo json_encode( array( 
                        'hiba'  => 'Legalább egy tételnek szerepelnie kell a táblázatban, üresen nem menthető!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
                
    // Ha a szerződés összege nem egyenlő a tételek összegével, akkor hibaüzenet
    }elseif( number_format($tetelek_osszege, $tizedesek, ',', ' ') != number_format($szerzodes_osszege, $tizedesek, ',', ' ') ) {
        $penznem = erteklista(ERTEKLISTA_PENZNEM, $_POST['kcs_21']);
        echo json_encode( array( 
                        'hiba'  => 'A szerződés tételeinek összege ('. number_format($tetelek_osszege, $tizedesek, ',', ' ').' '.$penznem.') nem egyezik meg a szerződés összegével ('. number_format($szerzodes_osszege, $tizedesek, ',', ' ').' '.$penznem.')!<br>Így nem menthető!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
                
    // Tételek kimentése Session-be
    } else {
        $_SESSION['pr_szer_tetel_recid']    = $_POST['adatok'][$recid_adat][1];
        $_SESSION['pr_szer_tetel_targy']    = str_replace('{IDEZOJEL}', '"', $_POST['adatok'][$targy_adat][1]);
        $_SESSION['pr_szer_tetel_varh_erk'] = $_POST['adatok'][$varh_erk_adat][1];
        if( $devizas ) {
            $_SESSION['pr_szer_tetel_dev_net'] = str_replace(',', '.', $_POST['adatok'][$varh_ert_adat][1]);
        } else {
            $_SESSION['pr_szer_tetel_varh_ert'] = str_replace(',', '.', $_POST['adatok'][$varh_ert_adat][1]);
        }
    }
    
    // Logolás
    $muvelet    = $recid.' recid-jú szerződés tételeit módosította. ';
    $valtozas   = 'Előző állapot (a már kapcsolt sorokat nem listázva, hisz az biztosan nem változott): '."\n";
    
    // Ha ismert a szerződés ID-ja, akkor a sorokat is updatel-jük (töröljük, majd újra beszúrjuk)
    // Azért kell az is_numeric, mert JS-ből volt, hogy itt egy 'false' értéket kaptunk, és ilyenkor sem kéne beírni még adatbázisb (új feltöltés esete).
    if( $recid && is_numeric($recid) ) {

        // Nem törölhetünk minden tételt, hisz lehet van számla párja, és ekkor a recid-ja nem változhat, nem törölhető majd hozható létre újból.
        $szerzodes_tetelek = $adatbazis_kapcsolat->s('recid', 'projektszerzodes_tetel', "`projektszerzodes_recid`='{$recid}'");
        if( $szerzodes_tetelek === false ) { $db_error = true; }
         foreach ( $szerzodes_tetelek as $szerzodes_tetel ) {
            $szamla_tetel = $adatbazis_kapcsolat->s2('recid', 'projektszamla_tetel', "`projektszerzodes_tetel_recid`='{$szerzodes_tetel['recid']}'");
            if( !$szamla_tetel ) {

                // Logolás
                $szerzodes_tetel_torolve = $adatbazis_kapcsolat->s('recid', 'projektszerzodes_tetel', "`projektszerzodes_recid`='{$szerzodes_tetel['recid']}'");
                if( $szerzodes_tetel_torolve === false ) { $db_error = true; }
                $valtozas .= logolas_kapcsolodo_tablak_sorai($szerzodes_tetel_torolve);
                
                // Tényleges törlés
                $sql = $adatbazis_kapcsolat->d('projektszerzodes_tetel', "`recid`='{$szerzodes_tetel['recid']}'");
                if( $sql === false ) { $db_error = true; }
            }
        }

        // Logolás
        $valtozas .= 'Új állapot (a már kapcsolt sorokat nem listázva, hisz az biztosan nem változott): '."\n";
        
        $i = 0;
        foreach ( $_SESSION['pr_szer_tetel_targy'] as $targy ) {

            if( $_SESSION['pr_szer_tetel_recid'][$i] ) {
                $szamla_tetel = $adatbazis_kapcsolat->s2('recid', 'projektszamla_tetel', "`projektszerzodes_tetel_recid`='{$_SESSION['pr_szer_tetel_recid'][$i]}'");
            }
            // Ha egy fiktív sor (azaz nincs még recid-ja a sessionben, vagy ha létezik, de nincs hozzá tartozó számla, akkor létrehozzuk
            if( !$_SESSION['pr_szer_tetel_recid'][$i] || !$szamla_tetel ) {

                $varh_erk = str_replace('.', '-', $_SESSION['pr_szer_tetel_varh_erk'][$i]);
                $varh_ert = str_replace(array(' ', ','), array('', '.'),  $_SESSION[$session_mezo][$i]);
                $uj_szerzodes_tetel_recid = $adatbazis_kapcsolat->i('projektszerzodes_tetel',
                                                    array('projektszerzodes_recid', 'targy', 'varh_erk', $db_mezo,   'loginnev',                          'insert_dt',    'insert_usr',                             'update_usr'                              ),
                                                    array($recid,                   $targy,  $varh_erk,  $varh_ert,  $_SESSION[get_db_tabla().'user'],    'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']    )
                                            );

                if( $uj_szerzodes_tetel_recid === false ) { $db_error = true; }

                // Az újonnan beszúrt sor recid-ját is eltároljuk SESSION-ben.
                $_SESSION['pr_szer_tetel_recid'][$i] = $uj_szerzodes_tetel_recid;
                
                // Logolás
                $valtozas .= '[mezo]recid[/mezo] [ertek_uj]'                        .$uj_szerzodes_tetel_recid                  .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]projektszamla_recid[/mezo] [ertek_uj]'          .$recid                                     .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]targy[/mezo] [ertek_uj]'                        .$targy                                     .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]varh_erk[/mezo] [ertek_uj]'                     .$varh_erk                                  .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]'.$db_mezo.'[/mezo] [ertek_uj]'                 .$varh_ert                                  .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]loginnev[/mezo] [ertek_uj]'                     .$_SESSION[get_db_tabla().'user']           .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]insert_dt[/mezo] [ertek_uj]'                    .date('Y-m-d H:i:s')                        .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]insert_usr[/mezo] [ertek_uj]'                   .$_SESSION[get_db_tabla().'teljes_nev']     .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]update_usr[/mezo] [ertek_uj]'                   .$_SESSION[get_db_tabla().'teljes_nev']     .'[/ertek_uj]'."\n";

            }
            
            $i++;
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
            'title' => 'Hiba'
        ));
    }
    
    $weblap->leallit();
    exit();
}

// Tétel sorok kiíratása
if( isset($_POST['pop_tetelek_feltolt']) ) {

    $html = '';

    $i = 0;
    foreach ( $_SESSION['pr_szer_tetel_targy'] as $targy ) {

        $szla_varh_erk = $_SESSION['pr_szer_tetel_varh_erk'][$i];
        $ertek = $_SESSION['pr_szer_tetel_varh_ert'][$i];
        $oszlop = 'netto_ert';
        $tizedes = 0;

        if( $_POST['kcs_21'] && $_POST['kcs_21'] != KCS_21_HUF ) {

            $ertek = $_SESSION['pr_szer_tetel_dev_net'][$i];
            $oszlop = 'dev_net';
            $tizedes = 2;
        }

        // Minden sornak adunk egy azonosítót. Mivel lehet csak egy ideiglenes sorról van szó, nem mindig van recid-nk, ezért időbélyeges azonosítót hozunk létre, megtoldva egy $i-vel.
        $id_tmp = time();
        $id_tmp .= $i;
        
        // Van-e már számlával összeköttetés
        $class = $teljesites = $netto_ertek = '';
        $torol = '<a onclick="popup_tetel_sor_torol(\'pop_tetel_'.$id_tmp.'\');"><span class="icon"><i class="material-icons">delete</i></span></a>';
        if( $_SESSION['pr_szer_tetel_recid'][$i] ) {
            $szamla_tetel = $adatbazis_kapcsolat->s2('recid projektszamla_recid netto_ert', 'projektszamla_tetel', "`projektszerzodes_tetel_recid`='{$_SESSION['pr_szer_tetel_recid'][$i]}'");
            if( $szamla_tetel ) {
                $szamla         = $adatbazis_kapcsolat->s2('telj_datum', 'projektszamla', "`recid`='{$szamla_tetel['projektszamla_recid']}'");
                $class          = ' szerzodes_szamla_tetel_szurke';
                $netto_ertek    = number_format($szamla_tetel[$oszlop], $tizedes, ',', ' ');
                $teljesites     = datum_kiir($szamla['telj_datum']);
                $torol          = '';
            }
        }
        
        $html .= nl2('
                        <tr class="pop_tetel_'.$id_tmp.$class.'">
                            <td class="pop_targy l">' . $targy . '</td>
                            <td class="pop_varh_erk">' . $szla_varh_erk . '</td>
                            <td class="pop_varh_ert r">' . $ertek . '</td>
                            <td>'.$teljesites.'</td>
                            <td class="r">'.$netto_ertek.'</td>
                            <td>
                                '.$torol.'
                                <span class="dn pop_recid">'.$_SESSION['pr_szer_tetel_recid'][$i].'</span>
                            </td>
                        </tr>
                        ');
        $i++;
    }
   
    echo json_encode( array(
        'html' => $html
        ) );
    
    $weblap -> leallit();
    exit();
}

// Eredetileg azt vizsgáltuk, hogy van-e a session-ban tétel, de sok más egyéb, új feltöltéskor ellenőrizednő dolgot is itt vizsgálunk PHP-val
if( isset($_POST['van_tetel']) ) {

    $szerzodes_osszege = str_replace(array(' ', ','), array('', '.'), $_POST['szerz_ossz']);
    $arfolyam          = str_replace(array(' ', ','), array('', '.'), $_POST['arfolyam']);
    $dev_net           = str_replace(array(' ', ','), array('', '.'), $_POST['dev_net']);
    
    // Kötelező mezők ellenőrzése
    foreach ( $akt_tomb as $elem ) {
        // Ha üres             VAGY ha dátum, és az értéke nagyobb mint 4000.00.00 (tehát üres)
        if( $elem['alert'] && ( !$_POST[$elem['name']] || ( strpos($elem['class'], 'datepicker')!==false ) && $_POST[$elem['name']]>'4000.00.00' ) ){
            echo json_encode(array(
                'hiba'  => $elem['alert'],
                'title' => 'Hiba',
                'focus' => $elem['name'],
            ));
            $weblap -> leallit();
            exit();
        }
        
    }
    
    // Ellenőrizzük, hogy a dátum mezők rendben vannak-e? (Js alatt nem akar lefutni... elég összetett már a JS kód, mert űrlap elpostolása előtt még ajax-os ellenőrzés is lefut, ez kavar be). De itt - ahol az ajaxos ellenőrzések vannak - könnyen elelnőrizni tudjuk
    if( $_POST['kelte'] < '9000.00.00' && !valos_datum_e($_POST['kelte']) ) {
        echo json_encode(array(
            'hiba'  => 'Kérem egy valós dátumot adjon meg Keltének!',
            'title' => 'Hiba',
            'focus' => 'kelte',
        ));
        $weblap -> leallit();
        exit();
    }
    if( $_POST['kiv_kezd'] < '9000.00.00' && !valos_datum_e($_POST['kiv_kezd']) ) {
        echo json_encode(array(
            'hiba'  => 'Kérem egy valós dátumot adjon meg Kivitelezés kezdésnek!',
            'title' => 'Hiba',
            'focus' => 'kiv_kezd',
        ));
        $weblap -> leallit();
        exit();
    }
    if( $_POST['kiv_vege'] < '9000.00.00' && !valos_datum_e($_POST['kiv_vege']) ) {
        echo json_encode(array(
            'hiba'  => 'Kérem egy valós dátumot adjon meg Kivitelezés végének!',
            'title' => 'Hiba',
            'focus' => 'kiv_vege',
        ));
        $weblap -> leallit();
        exit();
    }
    if( $_POST['muszak_atv'] < '9000.00.00' && !valos_datum_e($_POST['muszak_atv']) ) {
        echo json_encode(array(
            'hiba'  => 'Kérem egy valós dátumot adjon meg Műszaki átvétel végének!',
            'title' => 'Hiba',
            'focus' => 'muszak_atv',
        ));
        $weblap -> leallit();
        exit();
    }
    if( $_POST['bank_gar_vege'] < '9000.00.00' && !valos_datum_e($_POST['bank_gar_vege']) ) {
        echo json_encode(array(
            'hiba'  => 'Kérem egy valós dátumot adjon meg Bank garancia végének!',
            'title' => 'Hiba',
            'focus' => 'bank_gar_vege',
        ));
        $weblap -> leallit();
        exit();
    }
    
    // Kivitelezés kezdete, nem lehet korábbi, mint a kelte
    if( $_POST['kiv_kezd'] < $_POST['kelte'] ) {
        echo json_encode(array(
            'hiba'  => 'Kivitelezés kezdete, nem lehet korábbi, mint a kelte!',
            'title' => 'Hiba',
            'focus' => 'kiv_kezd',
        ));
        $weblap -> leallit();
        exit();
    }
    
    // Kivitelezés kezdete, nem lehet korábbi, mint a kelte
    if( $_POST['kiv_kezd'] > $_POST['kiv_vege'] ) {
        echo json_encode(array(
            'hiba'  => 'Kivitelezés vége nem lehet korábbi, mint a kivitelezés kezdete!',
            'title' => 'Hiba',
            'focus' => 'kiv_vege',
        ));
        $weblap -> leallit();
        exit();
    }
    
    // Ellenőrizzük, hogy a kiválasztott szálloda és a projekt szám stimmel-e
    if( $_POST['pr_szam'] ) {
        $projekt = $adatbazis_kapcsolat->s2('telephely_id', 'projektkezeles', "`ikt_szam`='{$_POST['pr_szam']}'");
        if( $projekt['telephely_id']!=$_POST['telephely_id'] ){
            echo json_encode(array(
                'hiba'  => 'A projekt szám nem megfelelő szállodához tartozik!',
                'title' => 'Hiba',
                'focus' => 'telephely_id',
            ));
            $weblap -> leallit();
            exit();
        }
    }
    
    // A szerződött össze nem lehet negatív
    if( $szerzodes_osszege < 0 ) {
        echo json_encode(array(
            'hiba'  => 'A szerződött összeg nem lehet 0 vagy ettől kisebb!',
            'title' => 'Hiba',
            'focus' => 'szerz_ossz',
        ));
        $weblap -> leallit();
        exit();
    }
    
    // A hozzáadott Projekthez milyen szerződések tartoznak. Ha a projekt összege mínusz a már hozzáadott összegek különbsége kisebb mint a mostani szerződés összege, akkor hibaüzenet
    if( $_POST['pr_szam'] ) {
        $projekt = $adatbazis_kapcsolat->s2('jkolt', 'projektkezeles', "`ikt_szam`='{$_POST['pr_szam']}'");
        $szerzodesek = $adatbazis_kapcsolat->s2('SUM(`szerz_ossz`)', 'projektszerzodes', "`pr_szam`='{$_POST['pr_szam']}'");
        $felhasznalhato = $projekt['jkolt']-$szerzodesek['SUM(`szerz_ossz`)'];
        if( $felhasznalhato < $szerzodes_osszege && !$_POST['keret_tullepve_mehet'] ) {
            echo json_encode(array(
                'hiba'  => nl2('
                                Projekt jóváhagyott keret: '.number_format($projekt['jkolt'], 0, '', ' ').' '.get_penznem().'
                                <br>Összes szerződött összeg: '.number_format($szerzodesek['SUM(`szerz_ossz`)'], 0, '', ' ').' '.get_penznem().'.
                                <br>Ebből még felhasználható: '.number_format($felhasznalhato, 0, '', ' ').' '.get_penznem().'.
                                <br>
                                <br>Az összes szerződés összege nagyobb, mint a jóváhagyott keret! Biztosan folytatja?
                                <script>var deviza_szorzat_nem_stimmel_mehet = true;</script>
                            '),
                'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#keret_tullepve_mehet\').val(1); mentesGomb();"> <input type="button" class="gomb piros" value="Nem" onclick="szurkit_zar(\'mégsem mehet mutat\'); alert2();">',
                'title' => 'Kérdés',
            ));
            $weblap -> leallit();
            exit();
        }
    }
    
    // Ha van feltöltött tétel, akkor az összegüknek meg kell egyezzen a szerződés összegével
    if( $_SESSION['pr_szer_tetel_targy'] ) {

        $session_valtozo_nev = 'pr_szer_tetel_varh_ert';
        $osszeg  = $szerzodes_osszege;
        $tizedes = 0;
        if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
            $osszeg  = $dev_net;
            $tizedes = 2;
            $session_valtozo_nev = 'pr_szer_tetel_dev_net';
        }
        
        $tetelek_osszege = 0;
        foreach ( $_SESSION[$session_valtozo_nev] as $ertek ) {
            $ertek = str_replace(array(' ', ','), array('', '.'), $ertek);
            $tetelek_osszege += $ertek;
        }
        if( $tetelek_osszege != $osszeg ) {
            echo json_encode( array(
                                'hiba'  => 'A szerződés tételeinek összege ('.number_format($tetelek_osszege, $tizedes, ',', ' ').') nem egyezik meg a szerződés összegével ('.number_format($osszeg, $tizedes, ',', ' ').')!<br>Így nem menthető!',
                                'title' => 'Hiba',
                                'focus' => 'szerz_ossz',
                            ) );
            $weblap->leallit();
            exit();
        }
        
    }
    
    // Van-e már hasonló szerződés? Ha igen, akkor rákérdezünk
    if( !$_POST['hasonlo_szerzodes_mehet'] ) {
        $szerzodes = $adatbazis_kapcsolat->s2('sz_szam', 'projektszerzodes', "`telephely_id`='{$_POST['telephely_id']}' AND `szallito_id`='{$_POST['szallito_id']}' AND `szerz_ossz`='{$szerzodes_osszege}'");
        if($szerzodes){
            echo json_encode(array(
                'hiba'  => 'Szálloda: '. getSzallodaNev($_POST['telephely_id']).'
                            <br>Szerződő partner: '.getErtek('nev', 'szallito', 'id', $_POST['szallito_id']).'
                            <br>Szerződött összeg: '. number_format($szerzodes_osszege, 0, '', '.').' '.get_penznem().'
                            <br>Az Ön által megadott szálloda, szerződő partner és szerződött összeg szerint már létezik szerződés.
                            <br>Szerződés szám: '.$szerzodes['sz_szam'].'
                            <br>Folytatja?',
                'title' => 'Kérdés',
                'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#hasonlo_szerzodes_mehet\').val(\'1\'); mentesGomb(); alert2();"><input type="button" class="gomb piros" value="Nem" onclick="alert2();">'
            ));
            $weblap -> leallit();
            exit();
        }
    }
    
    // Ha a deviza nettó és árfolyam szorzata nem egyenlő a szerződött összeggel, akkor erre rákérdezünk
    if( !$_POST['deviza_arf_szorzat_mehet']                  && // Ha még nem kaptunk mehet jelzést
        ( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) && // Devizás a szerződés, azaz nem üres vagy HUF a deviza
        ( round($dev_net*$arfolyam) != $szerzodes_osszege  )
    ) {

        echo json_encode(array(
            'hiba'  => 'A Devizat nettó '.$dev_net.' és Árfolyam '.$arfolyam.' mezők szorzata nem egyenlő a Szerződött összeggel'.$szerzodes_osszege.' .
                        <br>Folytatja?',
            'title' => 'Kérdés',
            'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#deviza_arf_szorzat_mehet\').val(\'1\'); mentesGomb(); alert2();"><input type="button" class="gomb piros" value="Nem" onclick="alert2();">'
        ));
        $weblap -> leallit();
        exit();
    }
    
    // Ha idáig eljutottunk, akkor kiírjuk hogy van (még akkor is, ha nincsenek tételek, de ez nem baj, mert a van, annyit jelent, hogy menthető az adatlap).
    echo json_encode( array(
                        'van_tetel' => 'van'
                    ) );
    $weblap->leallit();
    exit();

}

// Jótállás dokumentum törlése
if( isset($_POST['jotallas_dok_torol']) ) {
    
    // Ha nincs Pr szerződés kezelés jog, akkor hibaüzenet
    if( !_jog( JOG_PRKEZEL_SZERZODES_KEZEL ) ) {
        echo json_encode( array(
                            'hiba'  => 'Nincs projektszerződés kezelés joga, így a fájl nem törölhető!',
                            'title' => 'Hiba'
                        ) );
        $weblap->leallit();
        exit();
    }
    
    $pr_szerzodes_jotallas  = $adatbazis_kapcsolat->s2('projektszerzodes_recid utvonal fajlnev bejaras', 'projektszerzodes_jotallas', "`recid`='{$_POST['recid']}'");
    $pr_szerzodes           = $adatbazis_kapcsolat->s2('sz_szam', 'projektszerzodes', "`recid`='{$pr_szerzodes_jotallas['projektszerzodes_recid']}'");
    kep_torol( '../'. get_utvonal().'projektszerzodes_jotallas/'.$pr_szerzodes_jotallas['utvonal'] );
    $adatbazis_kapcsolat->u('projektszerzodes_jotallas', array('utvonal', 'fajlnev'), array('', ''), "`recid`='{$_POST['recid']}'");
    
    $nev      = 'Törölte a '.$pr_szerzodes['sz_szam'].' szerződés, '.datum_kiir($pr_szerzodes_jotallas['bejaras']).' dátumú bejárás jótállás fájlját.';
    $valtozas = 'Fájl neve: '.$pr_szerzodes_jotallas['fajlnev'];
    $adatbazis_kapcsolat -> i('log',
        array('felh_id',                                'nev', 'valtozas', 'ip'                     ),
        array($_SESSION[get_db_tabla().'login_recid'],  $nev,  $valtozas,  $_SERVER['REMOTE_ADDR']  )
    );
    
    echo json_encode( array(
                        'siker' => '1'
                    ) );
    $weblap->leallit();
    exit();
}

// CSV generálása
if( isset($_GET['csv']) ) {

    $megnevezes_fajhoz = $megnevezes;
    if( isset($_GET['tetelek']) ) {
        $megnevezes_fajhoz .= '_tetelek';
    }

    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=$megnevezes_fajhoz.csv");
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

            // Tételek exportja
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
                    if( $_SESSION['akt_sql_order'] ){
                        $hossz = strpos($lekerdezes_fromtol, 'ORDER');
                        if( $hossz ) {
                            $lekerdezes_fromtol = substr($lekerdezes_fromtol, 0, $hossz);
                        }else{
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
                            `'.get_db_tabla().$megnevezes.'`.`sz_szam` as \'Szerződés szám\',
                            `'.get_db_tabla().$megnevezes.'`.`kelte` as \'Kelte\',
                            `'.get_db_tabla().$megnevezes.'`.`szallito_id` as \'Szerződő partner\',
                            `'.get_db_tabla().$megnevezes.'`.`pr_szam` as \'Projekt szám\',
                            `'.get_db_tabla().$megnevezes.'`.`targy` as \'Tárgy\',
                            `'.get_db_tabla().$megnevezes.'`.`kolt_vis` as \'Költségviselő\',
                            `'.get_db_tabla().$megnevezes.'`.`sz_koto` as \'Szerződést kötő\',
                            `'.get_db_tabla().$megnevezes.'`.`kcs_21` as \'Pénznem\',
                            `'.get_db_tabla().$megnevezes.'`.`arfolyam` as \'Árfolyam\',
                            `'.get_db_tabla().$megnevezes.'`.`valtva` as \'Átváltva\',
                            `'.get_db_tabla().$megnevezes.'`.`dev_net` as \'Deviza nettó\',
                            `'.get_db_tabla().$megnevezes.'`.`szerz_ossz` as \'Szerződött összeg\',
                            `'.get_db_tabla().$megnevezes.'`.`epitesi` as \'Ebből építési\',
                            `'.get_db_tabla().$megnevezes.'`.`epitesi_eng` as \'Építési eng.\',
                            `'.get_db_tabla().$megnevezes.'`.`kiv_kezd` as \'Kivitelezés kezdete\',
                            `'.get_db_tabla().$megnevezes.'`.`kiv_vege` as \'Kivitelezés vége\',
                            `'.get_db_tabla().$megnevezes.'`.`let_fel` as \'Létesítmény felelős\',
                            `'.get_db_tabla().$megnevezes.'`.`muszak_atv` as \'Műszaki átvétel\',
                            `'.get_db_tabla().$megnevezes.'`.`muszaki_gar_kezd` as \'Műszaki garancia kezdete\',
                            `'.get_db_tabla().$megnevezes.'`.`muszaki_gar_vege` as \'Műszaki garancia vége\',
                            `'.get_db_tabla().$megnevezes.'`.`bank_gar` as \'Bank garancia\',
                            `'.get_db_tabla().$megnevezes.'`.`bank_gar_vege` as \'Bank garancia vége\',
                            `'.get_db_tabla().$megnevezes.'`.`szamlazva` as \'Számlázva\',
                                
                            `'.get_db_tabla().$megnevezes.'_tetel`.`targy` as `Tétel tárgy`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`varh_erk` as `Számla várh. érk.`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`varh_ert` as `Számla várh. értéke (Ft)`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`teljesites` as `Teljesítés`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`ertek` as `Érték`
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
                        if($kulcs=='Szerződő partner'){
                            $sor = getErtek('nev', 'szallito', 'id', $sor);
                        }
                        if($kulcs=='Költségviselő'){
                            $sor = erteklista(ERTEKLISTA_KOLTSEGVISELO, $sor);
                        }
                        if($kulcs=='Szerződést kötő'){
                            $sor = erteklista(ERTEKLISTA_SZERZODESTKOTO, $sor);
                        }
                        if($kulcs=='Pénznem'){
                            $sor = erteklista(ERTEKLISTA_PENZNEM, $sor);
                        }
                        if($kulcs=='Árfolyam'){
                            $sor = number_format($sor, 4, ',', '');
                        }
                        if($kulcs=='Deviza nettó'){
                            $sor = number_format($sor, 2, ',', '');
                        }
                        if($kulcs=='Létesítmény felelős'){
                            $sor = getErtek('teljes_nev', 'login', 'recid', $sor);
                        }
                        if($kulcs=='Műszaki átvétel' || $kulcs=='Kelte' || $kulcs=='Kivitelezés kezdete' || $kulcs=='Kivitelezés vége' || $kulcs=='Bank garancia vége' || $kulcs=='Bank garancia vége' || $kulcs=='Teljesítés' || $kulcs=='Átváltva' ){
                            $sor = datum_kiir($sor);
                        }
                        if($kulcs=='Számlázva' || $kulcs=='Építési eng.'){
                            if($sor){
                                $sor = 'igen';
                            }else{
                                $sor = 'nem';
                            }
                        }
                        $csvcont[] = iconv($mibol, $mibe.'//TRANSLIT', strip_tags( htmlspecialchars_decode( $sor ) ) );
                    }

                    fputcsv($open, $csvcont, ';');

                    // 100 soronként kiírtatunk
                    if ($i % 100 == 0) {
                        flush();
                    }

                    $i++;
                }

            // Projektszerződések exportja
            } else {

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
                    
                }else{
                    
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
                        }else{
                            $lekerdezes_fromtol = substr($lekerdezes_fromtol, 0);
                        }
                    }
                    // Ha nincs FROM rész, akkor azt még beletesszük
                    if( !$lekerdezes_fromtol ) {
                        $lekerdezes_fromtol = ' FROM `'.get_db_tabla().$megnevezes.'` ';
                    }
                    
                }
                
                $sql = 'SELECT
                            `telephely_id` as \'Szálloda\',
                            `sz_szam` as \'Szerződés szám\',
                            `kelte` as \'Kelte\',
                            `szallito_id` as \'Szerződő partner\',
                            `pr_szam` as \'Projekt szám\',
                            `targy` as \'Tárgy\',
                            `kolt_vis` as \'Költségviselő\',
                            `sz_koto` as \'Szerződést kötő\',
                            `kcs_21` as \'Pénznem\',
                            `arfolyam` as \'Árfolyam\',
                            `valtva` as \'Átváltva\',
                            `dev_net` as \'Deviza nettó\',
                            `szerz_ossz` as \'Szerződött összeg\',
                            `epitesi` as \'Ebből építési\',
                            `epitesi_eng` as \'Építési eng.\',
                            `kiv_kezd` as \'Kivitelezés kezdete\',
                            `kiv_vege` as \'Kivitelezés vége\',
                            `let_fel` as \'Létesítmény felelős\',
                            `muszak_atv` as \'Műszaki átvétel\',
                            `muszaki_gar_kezd` as \'Műszaki garancia kezdete\',
                            `muszaki_gar_vege` as \'Műszaki garancia vége\',
                            `bank_gar` as \'Bank garancia\',
                            `bank_gar_vege` as \'Bank garancia vége\',
                            `szamlazva` as \'Számlázva\'
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
                        if($kulcs=='Szerződő partner'){
                            $sor = getErtek('nev', 'szallito', 'id', $sor);
                        }
                        if($kulcs=='Költségviselő'){
                            $sor = erteklista(ERTEKLISTA_KOLTSEGVISELO, $sor);
                        }
                        if($kulcs=='Szerződést kötő'){
                            $sor = erteklista(ERTEKLISTA_SZERZODESTKOTO, $sor);
                        }
                        if($kulcs=='Pénznem'){
                            $sor = erteklista(ERTEKLISTA_PENZNEM, $sor);
                        }
                        if($kulcs=='Árfolyam'){
                            $sor = number_format($sor, 4, ',', '');
                        }
                        if($kulcs=='Deviza nettó'){
                            $sor = number_format($sor, 2, ',', '');
                        }
                        if($kulcs=='Létesítmény felelős'){
                            $sor = getErtek('teljes_nev', 'login', 'recid', $sor);
                        }
                        if($kulcs=='Műszaki átvétel' || $kulcs=='Kelte' || $kulcs=='Kivitelezés kezdete' || $kulcs=='Kivitelezés vége' || $kulcs=='Műszaki garancia kezdete' || $kulcs=='Műszaki garancia vége' || $kulcs=='Bank garancia' || $kulcs=='Bank garancia vége' || $kulcs=='Átváltva'){
                            $sor = datum_kiir($sor);
                        }
                        if($kulcs=='Számlázva' || $kulcs=='Építési eng.'){
                            if($sor){
                                $sor = 'igen';
                            }else{
                                $sor = 'nem';
                            }
                        }
                        $csvcont[] = iconv($mibol, $mibe.'//TRANSLIT', strip_tags( htmlspecialchars_decode( $sor ) ) );
                    }

                    fputcsv($open, $csvcont, ';');

                    // 100 soronként kiírtatunk
                    if ($i % 100 == 0) {
                        flush();
                    }

                    $i++;
                }

            }

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
    if($sor['dok_db']){
        $dok_db  = ' ('.$sor['dok_db'].')';
    }
    if($sor['megj_db']){
        $megj_db = ' ('.$sor['megj_db'].')';
    }

    if( isset($_GET['modosit']) || isset($_GET['tetel']) || isset($_GET['jotallas']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
        
        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'tetel'         => 'Tételek',
            'jotallas'      => 'Jótállás',
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

    // Gombok kirajzolása minden oldalra
    $gombok = '';
    if( isset($_GET['uj']) ) {
        
        // Új feltöltéskor a tétel sorokat töröljük, ne az legyen, hogy szerkesztésből maradt adatokkal számoljunk
        $_SESSION['pr_szer_tetel_targy']    = '';
        $_SESSION['pr_szer_tetel_varh_erk'] = '';
        $_SESSION['pr_szer_tetel_varh_ert'] = '';
        $_SESSION['pr_szer_tetel_dev_net']  = '';

        if( _jog(JOG_PRKEZEL_SZERZODES_KEZEL) || _jog(JOG_PR_SAJAT_SZERZ_KEZEL) ){
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
                </div>
            ');
            
            $gombok .= ' <input type="button" class="gomb" onclick="tetelek_kezelese_popup_nyit()" value="Tételek kezelése">';

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
    } else {
        
        // Módosításkor beállítjuk a Session-el az adatbázisból ismert tételek adatait, így lesz jó a popupban
        $i = 0;
        $_SESSION['pr_szer_tetel_recid'] = $_SESSION['pr_szer_tetel_targy'] = $_SESSION['pr_szer_tetel_varh_erk'] = $_SESSION['pr_szer_tetel_varh_ert'] = array();
        $tetelek = $adatbazis_kapcsolat->s('*', 'projektszerzodes_tetel', "`projektszerzodes_recid`='{$_GET['id']}'", 'recid');
        foreach ( $tetelek as $tetel ) {
            $_SESSION['pr_szer_tetel_recid'][$i]    = $tetel['recid'];
            $_SESSION['pr_szer_tetel_targy'][$i]    = $tetel['targy'];
            $_SESSION['pr_szer_tetel_varh_erk'][$i] = str_replace('-', '.', $tetel['varh_erk']);
            $_SESSION['pr_szer_tetel_varh_ert'][$i] = number_format($tetel['varh_ert'], 0, '', ' ');
            $_SESSION['pr_szer_tetel_dev_net'][$i]  = number_format($tetel['dev_net'], 2, ',', ' ');
            $i++;
        }
        
        // Törlés gomb
        if( !get_projekt_szerzodes_kezel_jog($_GET['id']) ){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Önnek nincs joga a projekt szerződés törléséhez!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        } else {

            // Ha van már olyan tétele, ami össze van kötve számláva, akkor nem engedünk törölni
            $van_szamla_kapcsolva = false;
            $tetelek = $adatbazis_kapcsolat->s('recid', 'projektszerzodes_tetel', "`projektszerzodes_recid`='{$_GET['id']}'");
            foreach($tetelek as $tetel){
                $van_szamlaja = $adatbazis_kapcsolat->s('recid', 'projektszamla_tetel', "`projektszerzodes_tetel_recid`='{$tetel['recid']}'");
                if( $van_szamlaja ){
                    $van_szamla_kapcsolva = true;
                    break;
                }
            }

            if( $van_szamla_kapcsolva ){
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'A szerződésnek van olyan tétele, amihez már van számla tétel kapcsolva, így nem törölhető a szerződés!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
            }else{
                $gombok .= '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
            }
            
        }
        
        // Tételek kezelése
        if( get_projekt_szerzodes_kezel_jog($_GET['id']) ) {

            // Ha nincs szerződéses összeg, akkor hibaüzenet (bár ilyen elő sem fordulhat elvileg, mert már új feltöltéskor sem mentehtte volna így el)
            $gombok .= ' <input type="button" class="gomb" onclick="tetelek_kezelese_popup_nyit()" value="Tételek kezelése">';
            // Ha nem az Adatlapon volnánk, akkor szükségünk lesz egy rejtett inputra, ami az adatbázisban levő szerződéses összeget tartalmazza, hogy bármelyik fül alatt szerkeszteni lehessen a tételeket, összehasonlítva az aktuális szerződéses összeggel
            $gombok .= ' <input type="hidden" id="szerz_ossz_db" value="'.$sor['szerz_ossz'].'">';
            $gombok .= ' <input type="hidden" id="kcs_21_autocomp_db" value="'.getErtekLista(ERTEKLISTA_PENZNEM, $sor['kcs_21']).'">';
            $gombok .= ' <input type="hidden" id="kcs_21_db" value="'.$sor['kcs_21'].'">';
        }
        
        // Tételek kezelésekor a popupban ezt az összeget meg lehessen jeleníteni akkor is, ha nem az adatlapon vagyunk
        if( $sor['kcs_21'] == KCS_21_HUF ) {
            $gombok .= '<input type="hidden" id="szerz_ossz_mindig_latszik" value="'.number_format($sor['szerz_ossz'], 0, '', ' ').'">';
        } else {
            $gombok .= '<input type="hidden" id="szerz_ossz_mindig_latszik" value="'.number_format($sor['dev_net'], 2, ',', ' ').'">';
        }
        
        if( isset($_GET['modosit']) && get_projekt_szerzodes_kezel_jog($_GET['id']) ) {
            
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
                </div>
            '.$gombok);
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
    
	// Új projektkezelés felvitele
	if( isset($_GET['uj']) && (_jog(JOG_PRKEZEL_SZERZODES_KEZEL) || _jog(JOG_PR_SAJAT_SZERZ_KEZEL)) ) {
        
        $js_submit_elott = nl2('

                /* Deviza nettó mező kötelező-e, ha igen, van-e értéke? */
                if( $(\'#dev_net\').hasClass(\'csillag\') && ( !$(\'#dev_net\').val() || $(\'#dev_net\').val()==\'0,00\' ) ){

                    alert2(\'Kérem adja meg a Deviza nettó értéket!\', \'\', \'Hiba\');
                    alert_utan_mehet_megsem_mutat_beallit();
                    $(\'#dev_net\').addClass(\'hibas_ide_focus\');
                    return false;

                /* "Ebből építési" mező kötelező-e, ha igen, akkor van-e értéke? */
                }else if( $(\'#epitesi_eng\').prop(\'checked\') && (!$(\'#epitesi\').val() || $(\'#epitesi\').val()==0 ) ){

                    alert2(\'Kérem adja meg, hogy a szerződött összegből mennyi az építési!\', \'\', \'Hiba\');
                    alert_utan_mehet_megsem_mutat_beallit();
                    $(\'#epitesi\').addClass(\'hibas_ide_focus\');
                    return false;

                /* "Ebből építési" mező értéke nem lehet több mint a szerződött összeg.*/
                }else if( Number( $(\'#epitesi\').val().replace(/ /g, \'\') ) > Number( $(\'#szerz_ossz\').val().replace(/ /g, \'\') ) ){

                    alert2(\'A szerződött összegtől nem lehet több az építési összeg!\', \'\', \'Hiba\');
                    alert_utan_mehet_megsem_mutat_beallit();
                    $(\'#epitesi\').addClass(\'hibas_ide_focus\');
                    return false;
                    
                }
            
                var dfrd_pr_mehet = false;
                var hibauzenet    = \'\';
                dfrd_pr_szerzodes = $.Deferred();
                $.ajax({
                    url : \'projektszerzodes.php\',
                    type: \'POST\',
                    data: {
                        kelte                   : $(\'#kelte\').val(),
                        kiv_kezd                : $(\'#kiv_kezd\').val(),
                        kiv_vege                : $(\'#kiv_vege\').val(),
                        muszak_atv              : $(\'#muszak_atv\').val(),
                        bank_gar_vege           : $(\'#bank_gar_vege\').val(),
                        telephely_id            : $(\'#telephely_id\').val(),
                        szallito_id             : $(\'#szallito_id\').val(),
                        targy                   : $(\'#targy\').val(),
                        kolt_vis                : $(\'#kolt_vis\').val(),
                        pr_szam                 : $(\'#pr_szam\').val(),
                        kcs_21                  : $(\'#kcs_21\').val(),
                        arfolyam                : $(\'#arfolyam\').val(),
                        dev_net                 : $(\'#dev_net\').val(),
                        szerz_ossz              : $(\'#szerz_ossz\').val(),
                        sz_koto                 : $(\'#sz_koto\').val(),
                        hasonlo_szerzodes_mehet : $(\'#hasonlo_szerzodes_mehet\').val(),
                        deviza_arf_szorzat_mehet: $(\'#deviza_arf_szorzat_mehet\').val(),
                        keret_tullepve_mehet    : $(\'#keret_tullepve_mehet\').val(),
                        van_tetel               : Date.now()
                    },
                    success: function(response){
                        if(response.substring(0, 6)==\'SESKIL\'){ location.href = \'index.php\'; }
                        if(response){
                            response = JSON.parse(response);
                            if(response.van_tetel==\'van\'){
                                formModositva = false;
                                $(\'#szerk_form\').attr(\'onsubmit\', \'\');
                                $(\'#szerk_form\').submit();
                                dfrd_pr_mehet = true;
                                return true;
                            }else{
                                dfrd_pr_mehet = false;
                                hibauzenet    = response.hiba;
                                gomb          = response.gomb;
                                title         = response.title;
                                focus         = response.focus;
                            }
                            dfrd_pr_szerzodes.resolve();
                        }
                        return false;
                    }
                });
                
                urlap_mehet = false;
                $.when(dfrd_pr_szerzodes).then(function() {
                    if( !dfrd_pr_mehet ){
                        alert2(hibauzenet, gomb, title);
                        $(\'#\'+focus).addClass(\'hibas_ide_focus\');
                        szurkit_zar(\'mehet_megsem_mutat\');
                        return false;
                    }else{
                        urlap_mehet = true;
                    }
                    
                    /* Ez a feltétel nem tévedésből van kétszer, kell ide is, meg egyel lentebb is */
                    if(!urlap_mehet){
                        return false;
                    }else{
                        return form_ell_admin();
                    }
                });
                
                
                /* Ez a feltétel nem tévedésből van kétszer, kell ide is, meg egyel fentebb is */
                if(!urlap_mehet){
                    return false;
                }else{
                    return form_ell_admin();
                }
                ');
        
        $navi_sablon  = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit     = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel   = array( $navi,             '',         $gyk2 );
        $szamla_html .= str_replace($navi_mit, $navi_mivel, $navi_sablon);

	    // FORM
		if( isset($_GET['ujkent']) ) {
            foreach ( $akt_tomb as $ertek ) {
                $sor[$ertek['name']]=$_SESSION[$ertek['name']];
			}
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
        
        // Új feltöltéskor a pénzenm HUF
        $sor['kcs_21'] = 5;
        $sor['kcs_21_autocomp'] = 'HUF';
        
        // Ha van igénylés recid-nk, akkor annak az adatait felhasználjuk
        if( $_GET['igeny_id'] ) {
            $igenyles = $adatbazis_kapcsolat->s2('*', 'projektigenyles', "`recid`='{$_GET['igeny_id']}' AND `telephely_id` IN (".implode(', ', get_akt_login_szallodai()).")");
            $sor['nev']             = $igenyles['nev'];
            $sor['telephely_id']    = $igenyles['telephely_id'];
            $sor['tipus']           = $igenyles['tipus'];
            $sor['besorol']         = $igenyles['besorol'];
            $sor['prior']           = $igenyles['prior'];
            $sor['bkolt']           = $igenyles['bkolt'];
            $sor['rendk']           = $igenyles['rendk'];
            $sor['megter']          = $igenyles['megter'];
            $sor['text']            = $igenyles['text'];
            $sor['projektigenyles_recid'] = $_GET['igeny_id'];
        }
        
        // Numerikus mezők alapértelmezett értékét beállítjuk
        $sor['arfolyam'] = '0,0000';
        $sor['dev_net']  = '0,00';

        $json_sor = array();
        foreach($sor as $kulcs => $elem){
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
                'tartalom' => $szamla_html,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

	// Projektszerződés módosítása
    } elseif( isset($_GET['modosit']) ) {

        $js_submit_elott = nl2('
            /* Deviza nettó mező kötelező-e, ha igen, van-e értéke? */
            if( $(\'#dev_net\').hasClass(\'csillag\') && ( !$(\'#dev_net\').val() || $(\'#dev_net\').val()==\'0,00\' ) ){
            
                alert2(\'Kérem adja meg a Deviza nettó értéket!\', \'\', \'Hiba\');
                alert_utan_mehet_megsem_mutat_beallit();
                $(\'#dev_net\').addClass(\'hibas_ide_focus\');
                return false;
              
            /* "Ebből építési" mező kötelező-e, ha igen, akkor van-e értéke? */
            } else if( $(\'#epitesi_eng\').prop(\'checked\') && (!$(\'#epitesi\').val() || $(\'#epitesi\').val()==0 ) ){
                
                alert2(\'Kérem adja meg, hogy a szerződött összegből mennyi az építési!\', \'\', \'Hiba\');
                alert_utan_mehet_megsem_mutat_beallit();
                $(\'#epitesi\').addClass(\'hibas_ide_focus\');
                return false;
                
            /* "Ebből építési" mező értéke nem lehet több mint a szerződött összeg. */
            } else if( Number( $(\'#epitesi\').val().replace(/ /g, \'\') ) > Number( $(\'#szerz_ossz\').val().replace(/ /g, \'\') ) ){

                alert2(\'A szerződött összegtől nem lehet több az építési összeg!\', \'\', \'Hiba\');
                alert_utan_mehet_megsem_mutat_beallit();
                $(\'#epitesi\').addClass(\'hibas_ide_focus\');
                return false;

            } else {
            
                return form_ell_admin();
                
            }
            ');
        
        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if( isset($_GET['id_hidden']) ) {
            $_GET['id'] = $_GET['id_hidden'];
        }

	    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");

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
        foreach($sor as $kulcs => $elem){
            $json_sor[$kulcs] = formAdatok_replace($elem);
        }
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';
        // Futtassuk le a deviza input elhagyást, mert ez alapján dől el, hogy a többi mező disabled-e vagy sem
        $szamla_html .= nl2('
            <script>
                $(document).ready(function () {
                    $(\'#kcs_21_autocomp\').focus().blur();
                });
            </script>
            ');
        
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
                            $(\'#pr_szam_gomb\').hide(\'\');
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

        $szamla_html .= nl2('
            <div class="dt naplo">
                <div class="dtr nFejlec">
                    <div class="dtc">Tárgy</div>
                    <div class="dtc">Számla várh. érk.</div>
                    <div class="dtc r">Számla várh. értéke ('.getErtekLista(ERTEKLISTA_PENZNEM, $sor['kcs_21']).')</div>
                    <div class="dtc">Teljesítés</div>
                    <div class="dtc r">Érték ('.getErtekLista(ERTEKLISTA_PENZNEM, $sor['kcs_21']).')</div>
                </div>
        ');

        $osszeg_oszlop = 'varh_ert';
        $ertek_oszlop  = 'ertek';
        $tizedesek     = 0;
        if( $sor['kcs_21'] && $sor['kcs_21']!=KCS_21_HUF ) {
            $osszeg_oszlop = 'dev_net';
            $ertek_oszlop  = 'dev_ertek';
            $tizedesek     = 2;
        }
        
        // Tételek felsorolása
        $tetelek = $adatbazis_kapcsolat->s('*', 'projektszerzodes_tetel', "`projektszerzodes_recid`='{$_GET['id']}'", 'recid');
        foreach ( $tetelek as $tetel ) {
            
            $szamla_html .= nl2('
                <div class="dtr nSor">
                    <div class="dtc">'.$tetel['targy'].'</div>
                    <div class="dtc">'.datum_kiir($tetel['varh_erk']).'</div>
                    <div class="dtc r">'.number_format($tetel[$osszeg_oszlop], $tizedesek, ',', ' ').'</div>
                    <div class="dtc">'.datum_kiir($tetel['teljesites']).'</div>
                    <div class="dtc r">'.number_format($tetel[$ertek_oszlop], $tizedesek, ',', ' ').'</div>
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

	// Jótállás fül
    } elseif( isset($_GET['jotallas']) ) {

        $szamla_html .= nl2('
            <div class="dt naplo">
                <div class="dtr nFejlec">
                    <div class="dtc">Gar. bej. dátuma</div>
                    <div class="dtc">Gar. bej. jegyzőkönyv</div>
                    <div class="dtc">E-mail elküldve</div>
                </div>
        ');

        // Tételek felsorolása
        $tallozas_gomb = fajlbol_olvas('../sablon/admin/jsfile_pr_szerzodes_jotallas.html', 1);
        $jotallasok = $adatbazis_kapcsolat->s('*', 'projektszerzodes_jotallas', "`projektszerzodes_recid`='{$_GET['id']}'", 'bejaras');
        foreach ( $jotallasok as $jotallas ) {
            
            // Ha van projektszerződés kezelés jog
            $fajl = '';
            
            // Ha van már fájl, akkor azt megmutatjuk
            if( $jotallas['utvonal'] ) {

                $utvonal     = '../'. get_utvonal().'projektszerzodes_jotallas/'.$jotallas['utvonal'];
                $kep_reszek  = pathinfo($utvonal);
                $ikon_utvonal = '../elemek/ikonok/'.strtolower($kep_reszek['extension']).'.png';

                $torles = '';
                if( _jog( JOG_PRKEZEL_SZERZODES_KEZEL ) ) {
                    $torles = nl2('
                                <div class="dn" id="dok_nevelos_nev_'.$jotallas['recid'].'">'.a_az($jotallas['fajlnev']).' '.$jotallas['fajlnev'].'</div>
                                <span class="icon link jotallas" title="Dokumentum törlése" onclick="jotallas_dok_torol(\''.$jotallas['recid'].'\')">
                                    <i class="material-icons" style="font-size: 2rem">
                                        delete
                                    </i>
                                </span>
                        ');
                }
                
                $fajl = $torles.'<a href="'.$utvonal.'" target="_blank">'.kep_kiir( $ikon_utvonal, utf8($jotallas['fajlnev']), 30, 30, '', '', '', 'no thumb').' '.$jotallas['fajlnev'].'</a>';

            // Egyébként a feltöltés
            } else {

                if( _jog( JOG_PRKEZEL_SZERZODES_KEZEL ) ) {
                
                    $megn = $jotallas['recid'];
                    $parameterek = nl2("
                                        'megnevezes'    : '".$megn."',
                                        'konyvtar'      : 'projektszerzodes',
                                        'tabla'         : 'projektszerzodes_jotallas',
                                        'oszlop'        : 'projektszerzodes_recid',
                                        'recid'         : '".$jotallas['recid']."'
                                        ");
                    $minden_kesz = 'szamlaBetolt(_get(\'id\'), baseName(location.href) + \'.php\', \'jotallas\');';
                    $fajl = str_replace(    array('{PHP_FAJL}',     '{PARAMETEREK}', '{MEGN}', '{MINDEN_KESZ}'),
                                            array('../feltolt.php', $parameterek,    $megn,    $minden_kesz   ),
                                            $tallozas_gomb
                                        );
                }
            }
                
            $mail_kuldve = '';
            if( $jotallas['mail_ment']!='0000-00-00 00:00:00' ) {
                $mail_kuldve = '<i class="material-icons">done</i>';
            }
            $szamla_html .= nl2('
                <div class="dtr nSor">
                    <div class="dtc">'.datum_kiir($jotallas['bejaras']).'</div>
                    <div class="dtc">'.$fajl.'</div>
                    <div class="dtc">'.$mail_kuldve.'</div>
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

        $szamla_html .= get_tortenet($megnevezes);

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Dokumentumok
	}elseif( isset($_GET['dokumentumok']) ) {

        if( $tobb_kep ) {
            require_once('tobb_kep.php');
        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Megjegyzések
	}elseif(isset($_GET['megjegyzesek'])){

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
            foreach($sorok as $sor) {
                $szamla_html .= nl2('
                    <div id="naploSor-'.$sor['recid'].'" data-id="'.$sor['recid'].'" data-i="'.$i.'" class="dtr nSor kijSor">
                        <div class="dtc">'.htmlspecialchars_decode( nl2br($sor['megj']) ).'</div>
                        <div class="dtc">'.$sor['insert_usr'].'</div>
                        <div class="dtc">'.datum_kiir($sor['insert_dt']).'</div>
                    </div>
                ');

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

	}

    if( !$_GET['ajax'] ) {
        echo '</div>';
    }

if( !$_GET['ajax'] ) {
    require_once('footer.php');
}

