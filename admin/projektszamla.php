<?php
require_once('../weblap.php');

if( !_jog(JOG_PRSZAMLAK_OLVAS) && !_jog(JOG_PR_SAJAT_SZAMLA_MEGTEKINT) ) {
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
$akt_tomb               = $projektszamla_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'projekt számlát';   // Hibaüzenetkor: Biztosan törlöd a $szoveg?

// Dokumentumok fülhöz kell majd
$tobb_kep = true;
if($tobb_kep){
    function get_tobbkep_darab()    { return 10;         } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '';         } // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;         } // MB-ban
    function get_tobbkep_oszlopnev(){                      // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!
}

// Lekérdezzük, hogy van-e a projekt számla tételei között olyan, amihez van kapcsolva szerződés tétel
if (isset($_POST['van_kapcsolt_szerzodes'])) {
    
    $szerzodes_tetelek = $adatbazis_kapcsolat->s('recid', 'projektszamla_tetel', "`projektszamla_recid`='{$_POST['recid']}' AND `projektszerzodes_tetel_recid`!=0");
    if ( $szerzodes_tetelek ){

        echo json_encode( array(
                            'hiba'  => 'A számlának van olyan tétele, amihez van szerződés tétel kapcsolva, így a szerződés nem törölhető!',
                            'title' => 'Hiba',
                        ) );
        $weblap -> leallit();
        exit;
    }
    
    echo json_encode( array(
                        'nincs'  => 'nincs csatolt szerződés',
                    ) );
    $weblap -> leallit();
    exit;
}

// Projektszerződés táblázat betöltése
if(isset($_POST['get_projekt_szerzodes_tabla'])){

    // Ha nincs új feltöltés jogunk, akkor kilépünk
    if( !get_projekt_szamla_kezel_jog($_POST['recid']) ){
        echo json_encode( array(
                            'hiba'  => 'Önnek nincs joga projekt számlákat kezelni!',
                            'title' => 'Hiba',
                        ) );
        $weblap -> leallit();
        exit;
    }
    
    $html = '';

//    // Rendezés beállítása
//     $join  = ' JOIN `'.get_db_tabla().'telephely` ON `'.get_db_tabla().'projektszerzodes`.`telephely_id`=`'.get_db_tabla().'telephely`.`id`';
//    // Rendezést egyelőre kivettük, ezért mindig a default fog érvényesülni
//    switch($_POST['order']){
//        case 'bkolt': $order = '`'.get_db_tabla().'projektszerzodes`.`szerz_ossz`,  `'.get_db_tabla().'telephely`.`nev`';               $sz_osszeg_selected = ' selected';  break;
//        default     : $order = '`'.get_db_tabla().'telephely`.`nev`,                `'.get_db_tabla().'projektszerzodes`.`szerz_ossz`';                                     break;
//    }
    // Nincs rendezés, fixen a projekt szerződés szerint rendezünk
    $order = '`'.get_db_tabla().'projektszerzodes`.`sz_szam`';

    $pr_sz_ek = $adatbazis_kapcsolat->s('
                                        `'.get_db_tabla().'projektszerzodes`.`recid`,
                                        `'.get_db_tabla().'projektszerzodes`.`sz_szam`,
                                        `'.get_db_tabla().'projektszerzodes`.`pr_szam`,
                                        `'.get_db_tabla().'projektszerzodes`.`targy`,
                                        `'.get_db_tabla().'projektszerzodes`.`let_fel`,
                                        `'.get_db_tabla().'projektszerzodes`.`kolt_vis`,
                                        `'.get_db_tabla().'projektszerzodes`.`szerz_ossz`
                                            ',
                                        'projektszerzodes',
                                        "`".get_db_tabla()."projektszerzodes`.`telephely_id`='{$_POST['telephely_id']}' AND `".get_db_tabla()."projektszerzodes`.`szallito_id`='{$_POST['szallito_id']}' AND `pr_szam`!=''", $order, '', '', '', $join);
 //echo $adatbazis_kapcsolat->gp() . '<br>';
    $i = 1;
    foreach($pr_sz_ek as $pr){
        
        $let_fel_neve  = getErtek('teljes_nev', 'login', 'recid', $pr['let_fel']);
        $kolt_vis_neve = getErtekLista(ERTEKLISTA_KOLTSEGVISELO, $pr['kolt_vis']);
        
        $html .= '
            <tr class="kijSor" data-i="'.$i.'" data-recid="'.$pr['recid'].'" data-sz_szam="'.$pr['sz_szam'].'" data-pr_szam="'.$pr['pr_szam'].'" data-let_fel="'.$pr['let_fel'].'" data-kolt_vis="'.$pr['kolt_vis'].'">
                <td style="width: 11rem">
                    '.$pr['sz_szam'].'
                    <div class="dn let_fel_txt">'.$let_fel_neve.'</div>
                    <div class="dn kolt_vis_txt">'.$kolt_vis_neve.'</div>
                    <div class="dn targy_txt">'.resz_string($pr['targy'], 60).'</div>
                </td>
                <td class="l" style="width: 40rem">'.nl2br2($pr['targy']).'</td>
                <td class="r nowrap">'.number_format($pr['szerz_ossz'], 0, '', ' ').'</td>
            </tr>
            ';
        $i++;
    }

    if($html){

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
                        Nettó érték ('.get_penznem().')
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prszerzodesek tbody\'), this.value, 4)">
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
    }else{
        $html = '<script>popupRejt(); setTimeout(function(){ popupZar(); alert2(\'Nem található projekt szerződés!\', \'\', \'Hiba\'); }, 10);</script>';
    }

    echo json_encode(
            array( 'html' => nl2($html) )
            );

    $weblap->leallit();
    exit();
}

// Számla tétel sorok mentése
if (isset($_POST['tetel_sorok_ment'])) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $recid                  = $_POST['recid'];
    $szamla_netto_erteke    = str_replace(' ', '', $_POST['szamla_netto_erteke']);
    $_POST['adatok'] = json_decode( $_POST['adatok'] );
    
    // Kapott tömb indexeit cseréljük beszédesebb változókra
    $recid_adat     = 0;
    $targy_adat     = 1;
    $netto_ert_adat = 2;
    $pr_tetel_adat  = 3;
    
    $tetelek_osszege = '';
    foreach ( $_POST['adatok'][$netto_ert_adat][1] as $ertek ){
        $ertek = str_replace(' ', '', $ertek);
        $tetelek_osszege += $ertek;
    }
    
    // Ha nincs egy sornyi tételünk sem, akkor hibaüzenet
    if( !$_POST['adatok'][$targy_adat][1] && !$_POST['db_nelkul'] ){
        echo json_encode( array( 
                        'hiba'  => 'Legalább egy számla tételnek szerepelnie kell a táblázatban, üresen nem menthető!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
                
    // Ha a számla nettó értéke nem egyenlő a tételek összegével, akkor hibaüzenet
    }elseif( $tetelek_osszege != $szamla_netto_erteke && !$_POST['db_nelkul'] ){
        echo json_encode( array( 
                        'hiba'  => 'A számla tételeinek összege ('. number_format($tetelek_osszege, 0, '', ' ').' '.get_penznem().') nem egyezik meg a számla nettó értékével ('. number_format($szamla_netto_erteke, 0, '', ' ').' '.get_penznem().')!<br>Így nem menthető!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
                
    // Tételek kimentése Session-be
    }else{
        $_SESSION['pr_szla_tetel_recid']     = $_POST['adatok'][$recid_adat][1];
        $_SESSION['pr_szla_tetel_targy']     = $_POST['adatok'][$targy_adat][1];
        $_SESSION['pr_szla_tetel_netto_ert'] = $_POST['adatok'][$netto_ert_adat][1];
        $_SESSION['pr_szla_tetel_pr_tetel']  = $_POST['adatok'][$pr_tetel_adat][1];
    }
    
    // Ha csak SESSION-be szeretnénk elmenteni, akkor itt meg is állunk, nem megyünk tovább az adatbázis műveletekig.
    if( $_POST['db_nelkul'] ){
        mysqli_commit($db_kapcs);

        echo json_encode(array(
            'statusz' => 'siker'
        ));
        
        $weblap -> leallit();
        exit;
    }
    
    // Adott projektszámla rekord lekérdezése
    $prszamla = $adatbazis_kapcsolat->s2('telj_datum', 'projektszamla', "`recid`='{$recid}'");
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
    if( $recid && is_numeric($recid) ){

        // Nem törölhetünk minden tételt, hisz lehet van számla párja, és ekkor a recid-ja nem változhat, nem törölhető majd hozható létre újból.
        $adatbazis_kapcsolat->d('projektszamla_tetel', "`projektszamla_recid`='{$recid}' AND `projektszerzodes_tetel_recid`='0'");
        
        $i = 0;
        foreach($_SESSION['pr_szla_tetel_targy'] as $targy){
            
            // Ha egy létező sor, akkor az előbb nem töröltük ki (azaz hozzá van csatolva szerződéshez), nem kell létrehozni
            $letezik = $adatbazis_kapcsolat->s('recid', 'projektszamla_tetel', "`recid`='{$_SESSION['pr_szla_tetel_recid'][$i]}'");
            if( $letezik === false ) { $db_error = true; }
            if( !$letezik ){
                $netto_ert = str_replace(' ', '',  $_SESSION['pr_szla_tetel_netto_ert'][$i]);
                $uj_szamla_tetel_recid = $adatbazis_kapcsolat->i('projektszamla_tetel',
                                                array('projektszamla_recid', 'targy', 'netto_ert', 'projektszerzodes_tetel_recid',          'loginnev',                          'insert_dt',    'insert_usr',                             'update_usr'                              ),
                                                array($recid,                $targy,  $netto_ert,  $_SESSION['pr_szla_tetel_pr_tetel'][$i], $_SESSION[get_db_tabla().'user'],    'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']    )
                                            );
                if( $uj_szamla_tetel_recid === false ) { $db_error = true; }
                $_SESSION['pr_szla_tetel_recid'][$i] = $uj_szamla_tetel_recid;
                
                // Logolás
                $valtozas .= '[mezo]recid[/mezo] [ertek_uj]'                        .$uj_szamla_tetel_recid                     .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]projektszamla_recid[/mezo] [ertek_uj]'          .$recid                                     .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]targy[/mezo] [ertek_uj]'                        .$targy                                     .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]netto_ert[/mezo] [ertek_uj]'                    .$netto_ert                                 .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]projektszerzodes_tetel_recid[/mezo] [ertek_uj]' .$_SESSION['pr_szla_tetel_pr_tetel'][$i]    .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]loginnev[/mezo] [ertek_uj]'                     .$_SESSION[get_db_tabla().'user']           .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]insert_dt[/mezo] [ertek_uj]'                    .date('Y-m-d H:i:s')                        .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]insert_usr[/mezo] [ertek_uj]'                   .$_SESSION[get_db_tabla().'teljes_nev']     .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]update_usr[/mezo] [ertek_uj]'                   .$_SESSION[get_db_tabla().'teljes_nev']     .'[/ertek_uj]'."\n";
                
            }
            
            $i++;
        }
        
    }
    
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
if (isset($_POST['projektszamla_kapcsolas'])) {
    
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
    
    // Ha nincs egy sornyi tételünk sem, akkor hibaüzenet
    if( !$_POST['adatok'][$targy_adat][1]  ){
        echo json_encode( array( 
                        'hiba'  => 'Legalább egy szerződés tételnek szerepelnie kell a táblázatban, üresen nem menthető!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
                
    }
    
    $szamla = $adatbazis_kapcsolat->s2('sz_szam telj_datum', 'projektszamla', "`recid`='{$recid}'");
    if( $szamla === false ) { $db_error = true; }
    $szerzodes = $adatbazis_kapcsolat->s2('recid szerz_ossz', 'projektszerzodes', "`sz_szam`='{$szamla['sz_szam']}'");
    if( $szerzodes === false ) { $db_error = true; }
    // Az a számlatétel, amit épp kapcsolunk
    $szamla_tetel = $adatbazis_kapcsolat->s2('netto_ert', 'projektszamla_tetel', "`recid`='{$_POST['szamla_tetel_recid']}'");
    if( $szamla_tetel === false ) { $db_error = true; }
    
    
    $kifizet_var_ossz = '';
    $i = 0;
    foreach ( $_POST['adatok'][$varhato_ert_adat][1] as $ertek ){
        // A kifizetésre vár értéket csak akkor adjuk hozzá, ha a nettó érték mező üres, azaz nincs benne tényadat
        if( !$_POST['adatok'][$netto_ert_adat][1][$i] ){
            $ertek = str_replace(' ', '', $ertek);
            $kifizet_var_ossz += $ertek;
        }
        $i++;
    }

    $kifizetett_ossz = '';
    foreach ( $_POST['adatok'][$netto_ert_adat][1] as $ertek ){
        $ertek = str_replace(' ', '', $ertek);
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
    foreach ( $_POST['adatok'][$netto_ert_adat][1] as $ertek ){
        
        // Számokból a szóközt vegyük ki
        $ertek = str_replace(' ', '', $ertek);
        $_POST['adatok'][$varhato_ert_adat][1][$i]  = str_replace(' ', '', $_POST['adatok'][$varhato_ert_adat][1][$i]);
        $_POST['adatok'][$netto_ert_adat][1][$i]    = str_replace(' ', '', $_POST['adatok'][$netto_ert_adat][1][$i]);
        
        // Recid alapján nézzük meg, hogy van-e már kapcsolt számlája. 
        $van_szamlaja = $adatbazis_kapcsolat->s2('recid', 'projektszamla_tetel', "`projektszerzodes_tetel_recid`='{$_POST['adatok'][0][1][$i]}'");
        if( $van_szamlaja === false ) { $db_error = true; }

        // Ha nincs számlája (ennek lehet az is az oka, hogy egy fiktív sor, azaz nem simert a recid),
        // akkor ellenőrizzük, hogy van-e beírva érték. Ha van, számoljuk, mert csak egy ilyen mező lehet
        // Ha van, akkor azt nem ellenőrizzük, mert akkor az egy szürke sor (már van számlája)
        if( (!$van_szamlaja || !$_POST['adatok'][$recid_adat][1][$i]) && $ertek ){

            $volt_ertek++;
            // Csak egy értéket fogadunk el, ezt jegyezzük is meg
            $beirt_netto_ertek = $ertek;
            $ertek_i           = $i;
        }

        // A dátumot is ellenőrizni kell, hogy csak egy helyen lett-e beírva
        if( (!$van_szamlaja || !$_POST['adatok'][$recid_adat][1][$i]) && $_POST['adatok'][$teljesites_adat][1][$i] && $_POST['adatok'][$teljesites_adat][1][$i]!='0000.00.00' && $_POST['adatok'][$teljesites_adat][1][$i]<'4000.00.00' ){
            $volt_teljesites++;
            // Csak egy teljesítés dátumot fogadunk el, ezt jegyezzük is meg
            $beirt_teljesites = str_replace('.', '-', $_POST['adatok'][$teljesites_adat][1][$i]);
            $teljesites_i     = $i;
        }
        
        // Várható érték oszlopban sehol nem lehet 0
        if( !$_POST['adatok'][$varhato_ert_adat][1][$i] ){
            echo json_encode( array( 
                            'hiba'  => 'A várható érték oszlopban nem szerepelhet üres adat!',
                            'title' => 'Hiba'
                            ) );
                    $weblap->leallit();
                    exit();
        }
        
        $i++;
    }

    // Ha több értéket/teljesítést is kitöltöttek, akkor hiba
    if( $volt_ertek > 1 ){
        echo json_encode( array( 
                        'hiba'  => 'Csak egy szerződés tételhez írhat nettó értéket!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
    }
    if( $volt_teljesites > 1 ){
        echo json_encode( array( 
                        'hiba'  => 'Csak egy szerződés tételhez írhat teljesítést!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
    }
    
    // Ha itt vagyunk, akkor csak egy nettó értéket/teljesítést írtak be. Kérdés, hogy megfelelő értéket írtak be?
    if( $szamla_tetel['netto_ert'] != $beirt_netto_ertek ){
        echo json_encode( array(
                        'hiba'  => 'A nettó érték oszlopba kérem '.number_format($szamla_tetel['netto_ert'], 0, '', ' ') .' '. get_penznem().'-ot írjon, hisz ez az összeg szerepel a csatolni kívánt számla tételen!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
    }
    
    // Teljesítés dátuma a számla adatlapján található teljesítés dátumával egyezen meg
    if( $szamla['telj_datum'] != $beirt_teljesites ){
        echo json_encode( array(
                        'hiba'  => 'A teljesítés oszlopba kérem '. datum_kiir($szamla['telj_datum']).' dátumot írjon, hisz ez szerepel a csatolni kívánt számlán!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
    }
    
    // Ha eltérő sorokban vannak új adatok, az is hiba
    if( $teljesites_i != $ertek_i ){
        echo json_encode( array(
                        'hiba'  => 'Kérem azonos tételhez írja be a teljesítést és nettó értéket!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
    }
    
    // Ha a számla nettó értéke nem egyenlő a tételek összegével, akkor hibaüzenet
    if( $kif_ossz > $szerzodes['szerz_ossz']  ){
        echo json_encode( array( 
                        'hiba'  => 'A már kifizetett ('.number_format($kifizetett_ossz, 0, '', ' ').' '.get_penznem().') és a még kifizetésre váró ('.number_format($kifizet_var_ossz, 0, '', ' ').' '.get_penznem().') számlák összege ('.number_format($kif_ossz, 0, '', ' ').' '.get_penznem().') több,<br>mint a szerződés összege ('.number_format($szerzodes['szerz_ossz'], 0, '', ' ').' '.get_penznem().')! Így nem menthető!',
                        'title' => 'Hiba',
                        ) );
                $weblap->leallit();
                exit();
                
    }elseif( $kif_ossz < $szerzodes['szerz_ossz'] && !$_POST['mehet'] ){
        echo json_encode( array( 
                        'hiba'  => 'A várható összes teljesítés ('.number_format($kif_ossz, 0, '', ' ').' '.get_penznem().') kevesebb, mint a szerződött összeg ('.number_format($szerzodes['szerz_ossz'], 0, '', ' ').' '.get_penznem().')!<br>Folytatja?',
                        'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="projektszamla_kapcsolas(\'mehet\')"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">',
                        'title' => 'Kérdés',
                        ) );
                $weblap->leallit();
                exit();
    }
    
    // Ellenőrzések után tároljuk el a változtatásokat. 
    // Szerződés tétetel rekordok frissítése
    if( $_POST['adatok'][$recid_adat][1][$ertek_i] ){
        $szerzodes_tetel_recid = $_POST['adatok'][$recid_adat][1][$ertek_i];
    }else{
        $szerzodes_tetel_recid = 'most hozza majd létre';
    }
    $muvelet  = 'Projektszámla tételhez projekt tételt csatolt. Számla recid: '.$recid.', Számla száma: '.$szamla['sz_szam'].', Számla tétel recid: '.$_POST['szamla_tetel_recid'].', Szerződés tétel recid: '.$szerzodes_tetel_recid;
    $valtozas = '';
    $i = 0;
    $kapott_recidk = array();
    foreach ( $_POST['adatok'][$recid_adat][1] as $szerzodes_tetel_recid ){
        
        // Ha valós rekord, nem újonnan létrehozott fiktiv tétel
        if( $szerzodes_tetel_recid ){
            // Ha már van számlája, akkor az egy szürke sor volt, annak nem mentjük el az adatait
            $van_szamlaja = $adatbazis_kapcsolat->s2('recid', 'projektszamla_tetel', "`projektszerzodes_tetel_recid`='{$szerzodes_tetel_recid}'");
            if( $van_szamlaja === false ) { $db_error = true; }
            if( !$van_szamlaja ){
                
                $ertek_regi = $adatbazis_kapcsolat->s2('*', 'projektszerzodes_tetel', "`recid`='{$_POST['adatok'][$recid_adat][1][$i]}'");
                if( $ertek_regi === false ) { $db_error = true; }
                
                $sql = $adatbazis_kapcsolat->u('projektszerzodes_tetel',
                                                                        array('varh_ert',                                  'teljesites',                                'ertek'                                   ),
                                                                        array($_POST['adatok'][$varhato_ert_adat][1][$i],  $_POST['adatok'][$teljesites_adat][1][$i],   $_POST['adatok'][$netto_ert_adat][1][$i]  ),
                                            "`recid`='{$_POST['adatok'][$recid_adat][1][$i]}'");
                if( $sql === false ) { $db_error = true; }
                
                // Logolás
                if( $ertek_i == $i ){
                    $valtozas .= $ertek_regi['recid'].' recid-jú szerződés tételt módosította, és EHHEZ KAPCSOLTA a számla tételt:' . "\n";
                }else{
                    $valtozas .= $ertek_regi['recid'].' recid-jú szerződés tételt módosította:' . "\n";
                }
                $valtozas .= '[mezo]varh_ert[/mezo] [ertek_regi]'   .$ertek_regi['varh_ert']    .'[/ertek_regi] [ertek_uj]'.$_POST['adatok'][$varhato_ert_adat][1][$i]                      .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]teljesites[/mezo] [ertek_regi]' .$ertek_regi['teljesites']  .'[/ertek_regi] [ertek_uj]'.str_replace('.', '-', $_POST['adatok'][$teljesites_adat][1][$i]).'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]ertek[/mezo] [ertek_regi]'      .$ertek_regi['ertek']       .'[/ertek_regi] [ertek_uj]'.$_POST['adatok'][$netto_ert_adat][1][$i]                        .'[/ertek_uj]'."\n";
            }
            
        // Az újonnan létrehozott fiktív tételt be kell szúrni
        }else{

            $uj_szerzodes_tetel_recid = $adatbazis_kapcsolat->i('projektszerzodes_tetel',
                                                                    array('projektszerzodes_recid', 'targy',                                'varh_erk',                               'varh_ert',                                  'teljesites',                                'ertek',                                    'loginnev',                         'insert_dt',    'insert_usr',                             'update_usr'                            ),
                                                                    array($szerzodes['recid'],      $_POST['adatok'][$targy_adat][1][$i],   $_POST['adatok'][$varh_erk_adat][1][$i],  $_POST['adatok'][$varhato_ert_adat][1][$i],  $_POST['adatok'][$teljesites_adat][1][$i],   $_POST['adatok'][$netto_ert_adat][1][$i],   $_SESSION[get_db_tabla().'user'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']  )
                                        );
            if( $uj_szerzodes_tetel_recid === false ) { $db_error = true; }
            
            // Az újonnan létrehozott sorokat vegyük fel a tömbbe, mert ami nem a tömb része, azt törölni fogjuk (hisz onnan fogjuk tudni, mely sorokat törölték a popup táblázatából)
            array_push( $_POST['adatok'][$recid_adat][1], $uj_szerzodes_tetel_recid);

            // Ha épp az $ertek_i-edik ciklusnál járnánk, akkor a frissen létrehozott tétel recid-ját felvesszük a tömbbe, hisz a következő lépésnél még össze is kell kapcsolni
            if( $ertek_i == $i ){
                $_POST['adatok'][$recid_adat][1][$ertek_i] = $uj_szerzodes_tetel_recid;
                $valtozas .= 'Szerződés tételt hozott létre, és EHHEZ KAPCSOLTA a számla tételt:' . "\n";
            }else{
                $valtozas .= 'Szerződés tételt hozott létre:' . "\n";
            }
            
            // Logolás
            $valtozas .= '[mezo]recid[/mezo] [ertek_uj]'                    .$uj_szerzodes_tetel_recid                  .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]projektszerzodes_recid[/mezo] [ertek_uj]'   .$szerzodes['recid']                        .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]targy[/mezo] [ertek_uj]'                    .$_POST['adatok'][$targy_adat][1][$i]       .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]varh_erk[/mezo] [ertek_uj]'                 .$_POST['adatok'][$varh_erk_adat][1][$i]    .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]varh_ert[/mezo] [ertek_uj]'                 .$_POST['adatok'][$varhato_ert_adat][1][$i] .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]teljesites[/mezo] [ertek_uj]'               .$_POST['adatok'][$teljesites_adat][1][$i]  .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]ertek[/mezo] [ertek_uj]'                    .$_POST['adatok'][$netto_ert_adat][1][$i]   .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]loginnev[/mezo] [ertek_uj]'                 .$_SESSION[get_db_tabla().'user']           .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]insert_dt[/mezo] [ertek_uj]'                .date('Y-m-d H:i:s')                        .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]insert_usr[/mezo] [ertek_uj]'               .$_SESSION[get_db_tabla().'teljes_nev']     .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]update_usr[/mezo] [ertek_uj]'               .$_SESSION[get_db_tabla().'teljes_nev']     .'[/ertek_uj]'."\n";
            
        }
        $i++;
    }

    // Számla tétel összekapcsolása a szerződés tétellel. Az $ertek_i-edig adatot kell menteni
    $sql = $adatbazis_kapcsolat->u('projektszamla_tetel', array('projektszerzodes_tetel_recid'), array($_POST['adatok'][$recid_adat][1][$ertek_i]), "`recid`='{$_POST['szamla_tetel_recid']}'");
    if( $sql === false ) { $db_error = true; }
    
    // Ezt a SESSION-ben is updatelni kell
    $i = 0;
    foreach( $_SESSION['pr_szla_tetel_recid'] as $szla_tetel_recid ){
        if( $szla_tetel_recid==$_POST['szamla_tetel_recid'] ){
            $_SESSION['pr_szla_tetel_pr_tetel'][$i] = $_POST['adatok'][$recid_adat][1][$ertek_i];
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

// Tétel sorok kiíratása
if (isset($_POST['pop_tetelek_feltolt'])) {
    
    // Projekt számlák táblázat kiírtatása előtt ellenőrizzük, hogy a hozzá tartozó szerződéshez milyen számlák tartoznak, és azoknak mennyi az össz nettó értéke.
    // Ha a Projekt számlák össz nettója több mint a hozzájuk tartozó szerződésé, akkor hibaüzenet
    // Ezt csak akkor tesszük meg, ha van már hozzárendelt szerződés
    if( $_POST['sz_szam'] ){
        // Lekérjük, a projekt szerződéses összegét
        $pr_szerzodes = $adatbazis_kapcsolat->s2('szerz_ossz', 'projektszerzodes', "`sz_szam`='{$_POST['sz_szam']}'");
        // Illetve, hogy az aktuális számlán kívül milyen számlák tartoznak hozzá
        $ossznetto = 0;
        $szamlak   = $adatbazis_kapcsolat->s('netto', 'projektszamla', "`sz_szam`='{$_POST['sz_szam']}' AND `recid`!='{$_POST['id']}'");
        foreach($szamlak as $szamla){
            $ossznetto += $szamla['netto'];
        }
        // Beleférünk-e a keretbe
        if( ($ossznetto+$_POST['netto']) > $pr_szerzodes['szerz_ossz'] ){
            echo json_encode( array(
                                'hiba'  => nl2('
                                                Szerződött összeg: '.number_format($pr_szerzodes['szerz_ossz'], 0, '', ' ').' '.get_penznem().'
                                                <br>Még felhasználható összeg: '.number_format($pr_szerzodes['szerz_ossz']-$ossznetto, 0, '', ' ').' '.get_penznem().'
                                                <br>A számla nettó értéke több, mint a választott szerződésből még felhasználható összeg!
                                                '),
                                'focus' => 'netto',
                                'title' => 'Hiba',
                            ) );
            $weblap -> leallit();
            exit;
        }
    }
    
    $html = '';

    // Tételek táblázat sorait kiszámoljuk:
    $i = 0;
    foreach( $_SESSION['pr_szla_tetel_targy'] as $targy ) {
        
        $szla_netto_erk = $_SESSION['pr_szla_tetel_netto_ert'][$i];
        
        // Minden sornak adunk egy azonosítót. Mivel lehet csak egy ideiglenes sorról van szó, nem mindig van recid-nk, ezért időbélyeges azonosítót hozunk létre, megtoldva egy $i-vel.
        $id_tmp = time();
        $id_tmp .= $i;
        
        $class = $targy = '';
        $torol = '<a onclick="popup_tetel_sor_torol(\'pop_tetel_'.$id_tmp.'\');"><span class="icon"><i class="material-icons">delete</i></span></a>';
        $pr_tetel = '';
        if( $_SESSION['pr_szla_tetel_pr_tetel'][$i] ){
            $pr_tetel = $adatbazis_kapcsolat->s2('recid targy', 'projektszerzodes_tetel', "`recid`='{$_SESSION['pr_szla_tetel_pr_tetel'][$i]}'");
        }
        if( $pr_tetel ){
            $targy = htmlspecialchars_decode( $pr_tetel['targy'] );
            $class = ' szerzodes_szamla_tetel_szurke';
            $torol = '';
        }else{
            if( $_SESSION['pr_szla_tetel_recid'][$i] ){
                $targy = '<input type="button" class="gomb" value="kapcsolás" onclick="kapcsolas_popup_nyit(\''.$_SESSION['pr_szla_tetel_recid'][$i].'\');">';
            }
        }
        
        $html .= nl2('
                        <tr class="pop_tetel_'.$id_tmp.$class.'">
                            <td class="pop_targy l">' . $_SESSION['pr_szla_tetel_targy'][$i] . '</td>
                            <td class="pop_netto_ert r">' . $szla_netto_erk . '</td>
                            <td class="pop_pr_tetel l" data-pr_tetel_recid="' . $pr_tetel['recid'] . '">'.$targy.'</td>
                            <td>
                                '.$torol.'
                                <span class="dn pop_recid">'.$_SESSION['pr_szla_tetel_recid'][$i].'</span>
                            </td>
                        </tr>');
        $i++;
    }
   
    echo json_encode( array(
        'html'          => $html,
        ) );
    
    $weblap -> leallit();
    exit();
}

// Kapcsoló popup feltöltése
if (isset($_POST['pop_kapcsolas_feltolt'])) {
    
    // Szerződés összegének kiszámítása
    $szamla_tetel   = $adatbazis_kapcsolat->s2('projektszamla_recid netto_ert', 'projektszamla_tetel', "`recid`='{$_POST['szamla_tetel_recid']}'");
    if($szamla_tetel['projektszamla_recid']){
        $szamla = $adatbazis_kapcsolat->s2('sz_szam telj_datum', 'projektszamla', "`recid`='{$szamla_tetel['projektszamla_recid']}'");
    }
    
    // Szerződés számot post-ként is megkapjuk. Ha mgésem akkor hibát dobunk.
    if($_POST['sz_szam']){
        
        // Hibáa kaptuk meg post-ként, minket az érdekel hogy DB szintén meg van-e. Ha nem akkor figelmeztetés, hogy előbb mentsen adatlapot
        if( !$szamla['sz_szam'] ){
            
            echo json_encode( array(
                'hiba'  => 'Kapcsolás előtt mentse szerződésszámot a számla adatlapján!',
                'title' => 'Hiba'
                ) );

            $weblap -> leallit();
            exit();
            
        }
        
        // Szerződés lekérdezése
        $szerzodes = $adatbazis_kapcsolat->s2('recid szerz_ossz telephely_id', 'projektszerzodes', "`sz_szam`='{$szamla['sz_szam']}'");

        // Ellenőrizzük, hogy a kiválasztott szálloda és a projekt szám stimmel-e
        if( $szerzodes['telephely_id']!=$_POST['telephely_id'] ){
            echo json_encode(array(
                'hiba'  => 'A projekt szám nem megfelelő szállodához tartozik!<br>Kérem egy '.getSzallodaNev($szerzodes['telephely_id']).' szállodához tartozó szerződést adjon hozzá a számla adatpaján!',
                'title' => 'Hiba',
            ));
            $weblap -> leallit();
            exit();
        }
        
        $szerzodes_ossz = number_format($szerzodes['szerz_ossz'], 0, '', ' ').' '. get_penznem();
        
    // Ilyen elevileg elő sem fordulhat, de jobb leellenőrizni
    }else{
        
        echo json_encode( array(
            'hiba'  => 'Tételek kapcsolásához kérem előbb adjon egy szerződést a projket számlához!',
            'title' => 'Hiba'
            ) );
        
        $weblap -> leallit();
        exit();
        
    }
    
    // Tételek táblázat sorait kiszámoljuk:
    $tabla  = '';
    $i      = 0;
    $szerzodes_tetelek = $adatbazis_kapcsolat->s('*', 'projektszerzodes_tetel', "`projektszerzodes_recid`='{$szerzodes['recid']}'");
    foreach( $szerzodes_tetelek as $tetel ) {
        
        // Minden sornak adunk egy azonosítót. Mivel lehet csak egy ideiglenes sorról van szó, nem mindig van recid-nk, ezért időbélyeges azonosítót hozunk létre, megtoldva egy $i-vel.
        $id_tmp = time();
        $id_tmp .= $i;
        
        $class      = '';
        $disabled   = '';
        $torol      = '<a onclick="popup_tetel_sor_torol(\'pop_kapcsol_'.$id_tmp.'\');"><span class="icon"><i class="material-icons">delete</i></span></a>';
        $van_szamlaja = $adatbazis_kapcsolat->s('recid', 'projektszamla_tetel', "`projektszerzodes_tetel_recid`='{$tetel['recid']}'");
        if( $van_szamlaja ){
            $disabled   = ' disabled';
            $class      = ' szerzodes_szamla_tetel_szurke';
            $torol      = '';
        }
        
        $tabla .= nl2('
                        <tr class="pop_kapcsol_' . $id_tmp.$class . '">
                            <td class="pop_targy l">' . $tetel['targy'] . '</td>
                            <td class="pop_varh_erk">' . datum_kiir($tetel['varh_erk']) . '</td>
                            <td><input type="text" id="varh_ert_'.$id_tmp.'" value="'.number_format($tetel['varh_ert'], 0, '', ' ').'" class="r f_num pop_varhato_ert" data-tort="0" maxlength="12"'.$disabled.'></td>
                            <td><input type="text" value="'.datum_kiir($tetel['teljesites']).'" class="datepicker_matol pop_teljesites" maxlength="10"'.$disabled.'></td>
                            <td><input type="text" id="ertek_'.$id_tmp.'" value="'.number_format($tetel['ertek'], 0, '', ' ').'" class="r f_num pop_netto_ert" data-tort="0" maxlength="12"'.$disabled.'></td>
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
        'szamla_tetel_netto'    => number_format($szamla_tetel['netto_ert'], 0, '', ' '),
        'szamla_telj_datum'     => datum_kiir($szamla['telj_datum']),
        ) );
    
    $weblap -> leallit();
    exit();
}

// Eredetileg azt vizsgáltuk, hogy van-e a session-ban tétel, de sok más egyéb, új feltöltéskor ellenőrizednő dolgot is itt vizsgálunk PHP-val
if (isset($_POST['van_tetel'])) {
    
    $szamla_netto_erteke = str_replace(' ', '', $_POST['netto']);
    
    // Vannak dátumok, amik csak múltbeliek lehetnek. Ennek ellenőrzését itt végezzük el
    $csak_mult_datum_lehet = array(
                                    array('name' => 'ceghez_erk',    'alert' => 'Céghez érkezett dátumhoz csak mai, vagy múltbeli dátum adható meg!'),
                                    array('name' => 'kelte',         'alert' => 'A számla kelte dátumhoz csak mai, vagy múltbeli dátum adható meg!'),
                                    array('name' => 'visszaadva_dt', 'alert' => 'A visszaadva dátumhoz csak mai, vagy múltbeli dátum adható meg!'),
                                );
    
    foreach($csak_mult_datum_lehet as $elem){
        
        // Visszaadva dátumot csak akkor figyeljük, ha a visszaadva pipa be is van pipálva
        if( $elem['name']!='visszaadva_dt' || $_POST['visszaadva']=='true' ){
            if( $_POST[$elem['name']]>date('Y.m.d') ){
                echo json_encode(array(
                    'hiba'  => $elem['alert'],
                    'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); szurkit_zar(\'mehet_megsem_mutat\'); $(\'#'.$elem['name'].'\').focus();">',
                    'title' => 'Hiba',
                ));
                $weblap -> leallit();
                exit();
            }
        }
    }
    
    // Kötelező mezők ellenőrzése
    foreach ($akt_tomb as $elem){
                                // Ha üres             VAGY ha dátum, és az értéke nagyobb mint 4000.00.00 (tehát üres)
        if( $elem['alert'] && ( !$_POST[$elem['name']] || ( strpos($elem['class'], 'datepicker')!==false ) && $_POST[$elem['name']]>'4000.00.00' ) ){
            echo json_encode(array(
                'hiba'  => $elem['alert'],
                'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); szurkit_zar(\'mehet_megsem_mutat\'); $(\'#'.$elem['name'].'\').focus();">',
                'title' => 'Hiba',
            ));
            $weblap -> leallit();
            exit();
        }
        
    }
    
    // Ellenőrizzük, hogy a dátum mezők rendben vannak-e? (Js alatt nem akar lefutni... elég összetett már a JS kód, mert űrlap elpostolása előtt még ajax-os ellenőrzés is lefut, ez kavar be). De itt - ahol az ajaxos ellenőrzések vannak - könnyen elelnőrizni tudjuk
    if( $_POST['ceghez_erk']<'9000.00.00' && !valos_datum_e($_POST['ceghez_erk']) ){
        echo json_encode(array(
            'hiba'  => 'Kérem egy valós dátumot adjon meg érkezés dátumának!',
            'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); szurkit_zar(\'mehet_megsem_mutat\'); $(\'#ceghez_erk\').focus();">',
            'title' => 'Hiba',
        ));
        $weblap -> leallit();
        exit();
    }
    if( $_POST['kelte']<'9000.00.00' && !valos_datum_e($_POST['kelte']) ){
        echo json_encode(array(
            'hiba'  => 'Kérem egy valós dátumot adjon meg Keltének!',
            'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); szurkit_zar(\'mehet_megsem_mutat\'); $(\'#kelte\').focus();">',
            'title' => 'Hiba',
        ));
        $weblap -> leallit();
        exit();
    }
    if( $_POST['telj_datum']<'9000.00.00' && !valos_datum_e($_POST['telj_datum']) ){
        echo json_encode(array(
            'hiba'  => 'Kérem egy valós dátumot adjon meg Teljesítése dátumnak!',
            'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); szurkit_zar(\'mehet_megsem_mutat\'); $(\'#telj_datum\').focus();">',
            'title' => 'Hiba',
        ));
        $weblap -> leallit();
        exit();
    }
    if( $_POST['fiz_hatar']<'9000.00.00' && !valos_datum_e($_POST['fiz_hatar']) ){
        echo json_encode(array(
            'hiba'  => 'Kérem egy valós dátumot adjon meg Fizetési határidőnek!',
            'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); szurkit_zar(\'mehet_megsem_mutat\'); $(\'#fiz_hatar\').focus();">',
            'title' => 'Hiba',
        ));
        $weblap -> leallit();
        exit();
    }
    if( $_POST['visszaadva']=='true' && $_POST['visszaadva_dt']>'9000.00.00' ){
        echo json_encode(array(
            'hiba'  => 'Kérem adja meg a Visszaadás dátumát!',
            'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); szurkit_zar(\'mehet_megsem_mutat\'); $(\'#visszaadva_dt\').focus();">',
            'title' => 'Hiba',
        ));
        $weblap -> leallit();
        exit();
    }
    if( $_POST['visszaadva']=='true' && $_POST['visszaadva_dt']<'9000.00.00' && !valos_datum_e($_POST['visszaadva_dt']) ){
        echo json_encode(array(
            'hiba'  => 'Kérem egy valós dátumot adjon meg Visszaadva dátumnak!',
            'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); szurkit_zar(\'mehet_megsem_mutat\'); $(\'#visszaadva_dt\').focus();">',
            'title' => 'Hiba',
        ));
        $weblap -> leallit();
        exit();
    }
    if( $_POST['visszaadva']=='false' && $_POST['visszaadva_dt'] && $_POST['visszaadva_dt']<'9000.00.00' ){
        echo json_encode(array(
            'hiba'  => 'A Visszaadás dátumát csak akkor töltse ki, ha a visszaadva pipát be is kapcsolja!',
            'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); szurkit_zar(\'mehet_megsem_mutat\'); $(\'#visszaadva_dt\').focus();">',
            'title' => 'Hiba',
        ));
        $weblap -> leallit();
        exit();
    }
    
    // Ellenőrizzük, hogy a kiválasztott szálloda és a projekt szám stimmel-e
    if( $_POST['sz_szam'] ){
        $projekt = $adatbazis_kapcsolat->s2('telephely_id', 'projektszerzodes', "`sz_szam`='{$_POST['sz_szam']}'");
        if( $projekt['telephely_id']!=$_POST['telephely_id'] ){
            echo json_encode(array(
                'hiba'  => 'A projekt szám nem megfelelő szállodához tartozik!',
                'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); szurkit_zar(\'mehet_megsem_mutat\'); $(\'#telephely_id\').focus();">',
                'title' => 'Hiba',
            ));
            $weblap -> leallit();
            exit();
        }
    }
    
    // Ha ismert a szerződés szám ellenőrizzük, hogy a kiválasztott szálloda és a partner stimmel-e
    if( $_POST['sz_szam'] ){
        $projekt = $adatbazis_kapcsolat->s2('szallito_id', 'projektszerzodes', "`sz_szam`='{$_POST['sz_szam']}'");
        if( $projekt['szallito_id']!=$_POST['szallito_id'] ){
            echo json_encode(array(
                'hiba'  => 'A partner nem megfelelő szerződéshez tartozik!',
                'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); szurkit_zar(\'mehet_megsem_mutat\'); $(\'#szallito_id_autocomp\').focus();">',
                'title' => 'Hiba',
            ));
            $weblap -> leallit();
            exit();
        }
    }
        
    // Ha van feltöltött tétel, akkor az összegüknek meg kell egyezzen a számla nettó értékével
    if( $_SESSION['pr_szla_tetel_targy'] ){
        
        $tetelek_osszege = 0;
        foreach ( $_SESSION['pr_szla_tetel_netto_ert'] as $ertek ){
            $ertek = str_replace(' ', '', $ertek);
            $tetelek_osszege += $ertek;
        }
        if( $tetelek_osszege != $szamla_netto_erteke ){
            echo json_encode( array(
                                'hiba' => 'A számla tételeinek összege nem egyezik meg a számla nettó értékével!<br>Így nem menthető!',
                                'gomb' => '<input type="button" class="gomb" value="Ok" onclick="alert2(); szurkit_zar(\'mehet_megsem_mutat\');">',
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

// CSV generálása
if (isset($_GET['csv'])) {

    $megnevezes_fajhoz = $megnevezes;
    if (isset($_GET['tetelek'])) {
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
            if (isset($_GET['tetelek'])) {

                $lekerdezes_fromtol = str_replace('FROM `'.get_db_tabla().$megnevezes.'` ', "FROM `".get_db_tabla().$megnevezes."` JOIN `".get_db_tabla().$megnevezes."_tetel` ON `".get_db_tabla().$megnevezes."`.`recid`=`".get_db_tabla().$megnevezes."_tetel`.`".$megnevezes."_recid`", $lekerdezes_fromtol);
                
                if( strstr($_SESSION['akt_sql'], 'WHERE') ) {
                    $lekerdezes_fromtol = str_replace('WHERE', "WHERE `".get_db_tabla().$megnevezes."`.`recid` IN (".$_SESSION['nyomtatas_ids'].") AND ", $lekerdezes_fromtol);
                } else {
                    $lekerdezes_fromtol = str_replace('ORDER', "WHERE `".get_db_tabla().$megnevezes."`.`recid` IN (".$_SESSION['nyomtatas_ids'].") ORDER", $lekerdezes_fromtol);
                }

                $sql = 'SELECT
                            `'.get_db_tabla().$megnevezes.'`.`ceghez_erk` as \'Céghez érkezett\',
                            `'.get_db_tabla().$megnevezes.'`.`kelte` as \'Kelte\',
                            `'.get_db_tabla().$megnevezes.'`.`szla_szam` as \'Számlaszám\',
                            `'.get_db_tabla().$megnevezes.'`.`telj_datum` as \'Teljesítés dátuma\',
                            `'.get_db_tabla().$megnevezes.'`.`fiz_hatar` as \'Fizetési határidő\',
                            `'.get_db_tabla().$megnevezes.'`.`telephely_id` as \'Szálloda\',
                            `'.get_db_tabla().$megnevezes.'`.`szallito_id` as \'Partner\',
                            `'.get_db_tabla().$megnevezes.'`.`targy` as \'Tárgy\',
                            `'.get_db_tabla().$megnevezes.'`.`netto` as \'Nettó érték\',
                            `'.get_db_tabla().$megnevezes.'`.`brutto` as \'Bruttó érték\',
                            `'.get_db_tabla().$megnevezes.'`.`visszaadva` as \'Visszaadva\',
                            `'.get_db_tabla().$megnevezes.'`.`visszaadva_dt` as \'Visszaadva dátum\',
                            `'.get_db_tabla().$megnevezes.'`.`sz_szam` as \'Szerződés szám\',
                            `'.get_db_tabla().$megnevezes.'`.`pr_szam` as \'Projekt szám\',
                            `'.get_db_tabla().$megnevezes.'`.`let_fel` as \'Felelős\',
                            `'.get_db_tabla().$megnevezes.'`.`kolt_vis` as \'Költségviselő\',
                                
                            `'.get_db_tabla().$megnevezes.'_tetel`.`targy` as `Tétel tárgy`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`netto_ert` as `Tétel nettó érték`
                            '.$lekerdezes_fromtol;

                $query      = mysqli_query($db_kapcs, $sql);
// echo $sql; 
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
                        if($kulcs=='Partner'){
                            $sor = getErtek('nev', 'szallito', 'id', $sor);
                        }
                        if($kulcs=='Költségviselő'){
                            $sor = erteklista(ERTEKLISTA_KOLTSEGVISELO, $sor);
                        }
                        if($kulcs=='Felelős'){
                            $sor = getErtek('teljes_nev', 'login', 'recid', $sor);
                        }
                        if($kulcs=='Céghez érkezett' || $kulcs=='Kelte' || $kulcs=='Teljesítés dátuma' || $kulcs=='Fizetési határidő' || $kulcs=='Visszaadva dátum'  ){
                            $sor = datum_kiir($sor);
                        }
                        if($kulcs=='Visszaadva'){
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
            }else{

                if( strstr($_SESSION['akt_sql'], 'WHERE') ) {
                    $lekerdezes_fromtol = str_replace('WHERE', "WHERE `".get_db_tabla().$megnevezes."`.`recid` IN (".$_SESSION['nyomtatas_ids'].") AND ", $lekerdezes_fromtol);
                } else {
                    $lekerdezes_fromtol = str_replace('ORDER', "WHERE `".get_db_tabla().$megnevezes."`.`recid` IN (".$_SESSION['nyomtatas_ids'].") ORDER", $lekerdezes_fromtol);
                }

                $sql = 'SELECT
                            `'.get_db_tabla().$megnevezes.'`.`ceghez_erk` as \'Céghez érkezett\',
                            `'.get_db_tabla().$megnevezes.'`.`kelte` as \'Kelte\',
                            `'.get_db_tabla().$megnevezes.'`.`szla_szam` as \'Számlaszám\',
                            `'.get_db_tabla().$megnevezes.'`.`telj_datum` as \'Teljesítés dátuma\',
                            `'.get_db_tabla().$megnevezes.'`.`fiz_hatar` as \'Fizetési határidő\',
                            `'.get_db_tabla().$megnevezes.'`.`telephely_id` as \'Szálloda\',
                            `'.get_db_tabla().$megnevezes.'`.`szallito_id` as \'Partner\',
                            `'.get_db_tabla().$megnevezes.'`.`targy` as \'Tárgy\',
                            `'.get_db_tabla().$megnevezes.'`.`netto` as \'Nettó érték\',
                            `'.get_db_tabla().$megnevezes.'`.`brutto` as \'Bruttó érték\',
                            `'.get_db_tabla().$megnevezes.'`.`visszaadva` as \'Visszaadva\',
                            `'.get_db_tabla().$megnevezes.'`.`visszaadva_dt` as \'Visszaadva dátum\',
                            `'.get_db_tabla().$megnevezes.'`.`sz_szam` as \'Szerződés szám\',
                            `'.get_db_tabla().$megnevezes.'`.`pr_szam` as \'Projekt szám\',
                            `'.get_db_tabla().$megnevezes.'`.`let_fel` as \'Felelős\',
                            `'.get_db_tabla().$megnevezes.'`.`kolt_vis` as \'Költségviselő\'
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
                            $sor = getSzallodaNev($sor);
                        }
                        if($kulcs=='Partner'){
                            $sor = getErtek('nev', 'szallito', 'id', $sor);
                        }
                        if($kulcs=='Költségviselő'){
                            $sor = erteklista(ERTEKLISTA_KOLTSEGVISELO, $sor);
                        }
                        if($kulcs=='Felelős'){
                            $sor = getErtek('teljes_nev', 'login', 'recid', $sor);
                        }
                        if($kulcs=='Céghez érkezett' || $kulcs=='Kelte' || $kulcs=='Teljesítés dátuma' || $kulcs=='Fizetési határidő' || $kulcs=='Visszaadva dátum'  ){
                            $sor = datum_kiir($sor);
                        }
                        if($kulcs=='Visszaadva'){
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

    if( isset($_GET['modosit']) || isset($_GET['tetel']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
        
        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'tetel'         => 'Tételek',
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

    // Gombok kirajzolása minden oldalra. Projekt számlák kezelés globális jog kell hozzá
    $gombok = '';
    if( isset($_GET['uj']) && _jog(JOG_PRSZAMLAK_KEZEL) ) {
        
        // Új feltöltéskor a tétel sorokat töröljük, ne az legyen, hogy szerkesztésből maradt adatokkal számoljunk
        $_SESSION['pr_szla_tetel_targy']     = '';
        $_SESSION['pr_szla_tetel_netto_ert'] = '';
        $_SESSION['pr_szla_tetel_pr_tetel']  = '';

        $gombok = nl2('
            <div class="form_kuldes">
                <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="szurkit(\'mehet_megsem_rejt\'); $(\'#szerk_form\').submit()">
                <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="szurkit(\'mehet_megsem_rejt\'); formModositva=false; szamlaBetolt($(\'#szamlaList li:first-of-type\').attr(\'data-recid\'), \''.$megnevezes.'.php\');">
            </div>
        ');

        $gombok .= ' <input type="button" class="gomb" onclick="if( !$(\'#netto\').val() || $(\'#netto\').val()==\'0\' ){ alert2(\'Tételek kezelése előtt adja meg a számla nettó értékét!\', \'\', \'Hiba\'); }else{ csoportMuvelet(\'projektszamla_tetelek_kezelese\'); }" value="Tételek kezelése">';

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
    }else{
        
        // Módosításkor updateljük a Session-ben a tételek adatait, így lesz jó a popupban
        $i = 0;
        $_SESSION['pr_szla_tetel_targy'] = $_SESSION['pr_szla_tetel_netto_ert'] = array();
        $tetelek = $adatbazis_kapcsolat->s('*', 'projektszamla_tetel', "`projektszamla_recid`='{$_GET['id']}'", 'recid');
        foreach( $tetelek as $tetel ){
            $_SESSION['pr_szla_tetel_recid'][$i]     = $tetel['recid'];
            $_SESSION['pr_szla_tetel_targy'][$i]     = $tetel['targy'];
            $_SESSION['pr_szla_tetel_netto_ert'][$i] = number_format($tetel['netto_ert'], 0, '', ' ');
            $_SESSION['pr_szla_tetel_pr_tetel'][$i]  = $tetel['projektszerzodes_tetel_recid'];
            $i++;
        }
        
        // Törlés gomb
        if( !get_projekt_szamla_kezel_jog($_GET['id']) ){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Önnek nincs joga a projekt számla törléséhez!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        }else{
            
            // Ha van már olyan tétele, ami össze van kötve szerződéssel, akkor nem engedünk törölni
            if( $sor['projektszerzodes_tetel_recid'] ){
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'A számlának van olyan tétele, amihez már van szerződés tétel kapcsolva, így nem törölhető a számla!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
            }else{
                $gombok .= '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
            }
        }
        
        // Tételek kezelése
        if( get_projekt_szamla_kezel_jog($_GET['id']) ){
            
            $gombok .= ' <input type="button" class="gomb" onclick="csoportMuvelet(\'projektszamla_tetelek_kezelese\');" value="Tételek kezelése">';
            // Ha nem az Adatlapon volnánk, akkor szükségünk lesz egy rejtett inputra, ami az adatbázisban levő nettó értéket tartalmazza, hogy bármelyik fül alatt szerkeszteni lehessen a tételeket, összehasonlítva az aktuális számla nettó értékével
            $gombok .= ' <input type="hidden" id="netto_db" value="'.$sor['netto'].'">';
            $gombok .= ' <input type="hidden" id="sz_szam_db" value="'.$sor['sz_szam'].'">';
            $gombok .= ' <input type="hidden" id="pr_szam_db" value="'.$sor['pr_szam'].'">';
            $gombok .= ' <input type="hidden" id="telephely_id_db" value="'.$sor['telephely_id'].'">';
        }
        
        if( isset($_GET['modosit']) && get_projekt_szamla_kezel_jog($_GET['id']) ) {
            
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="szurkit(\'mehet_megsem_rejt\'); $(\'#szerk_form\').submit()">
                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="szurkit(\'mehet_megsem_rejt\'); formModositva=false; szamlaBetolt(\''.$_GET['id'].'\', \''.$megnevezes.'.php\');">
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

    $js_submit_elott = 'return form_ell_admin();';
    
	// Új projektkezelés felvitele
	if( isset($_GET['uj']) && _jog(JOG_PRSZAMLAK_KEZEL) ){
        
        // Az űrlap feldolgozása előtti JS-t tiszta lappal indítjuk
        $js_submit_elott = '';
        
        $js_submit_elott .= nl2('
            
                var dfrd_pr_mehet = false;
                var hibauzenet    = \'\';
                dfrd_pr_szszamla = $.Deferred();
                $.ajax({
                    url : \'projektszamla.php\',
                    type: \'POST\',
                    data: {
                        ceghez_erk      : $(\'#ceghez_erk\').val(),
                        kelte           : $(\'#kelte\').val(),
                        szla_szam       : $(\'#szla_szam\').val(),
                        telj_datum      : $(\'#telj_datum\').val(),
                        fiz_hatar       : $(\'#fiz_hatar\').val(),
                        visszaadva      : $(\'#visszaadva\').prop(\'checked\'),
                        visszaadva_dt   : $(\'#visszaadva_dt\').val(),
                        telephely_id    : $(\'#telephely_id\').val(),
                        szallito_id     : $(\'#szallito_id\').val(),
                        targy           : $(\'#targy\').val(),
                        kolt_vis        : $(\'#kolt_vis\').val(),
                        netto           : $(\'#netto\').val(),
                        brutto          : $(\'#brutto\').val(),
                        sz_szam         : $(\'#sz_szam\').val(),
                        pr_szam         : $(\'#pr_szam\').val(),
                        let_fel         : $(\'#let_fel\').val(),
                        kolt_vis        : $(\'#kolt_vis\').val(),
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
                            }else{
                                dfrd_pr_mehet = false;
                                hibauzenet    = response.hiba;
                                gomb          = response.gomb;
                                title         = response.title;
                            }
                            dfrd_pr_szszamla.resolve();
                        }
                        return false;
                    }
                });
                
                urlap_mehet = false;
                $.when(dfrd_pr_szszamla).then(function() {
                    if( !dfrd_pr_mehet ){
                        alert2(hibauzenet, gomb, title);
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
		if($_GET['ujkent']) {
            $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['ujkent']}'");
            if( $sor ) {
                foreach($akt_tomb as $ertek) {
                    $_SESSION[$ertek['name']] = $sor[$ertek['name']];
    			}
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
        
        // Ha van igénylés recid-nk, akkor annak az adatait felhasználjuk
        if($_GET['igeny_id']){
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
	} elseif(isset($_GET['modosit'])) {

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
        }

	    $sor        = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");
        $telephely  = $adatbazis_kapcsolat -> s2('nev modell', 'telephely', "`id`='{$sor['telephely_id']}'");

        foreach($akt_tomb as $tomb) {
            if( $tomb['type'] == 'autocomplete' ) {
                
                if($tomb['csoport']){
                    $kulso_ertek = $adatbazis_kapcsolat->s2('info', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}' AND `csoport`='{$tomb['csoport']}'");
                    $kulso_ertek['nev'] = $kulso_ertek['info'];
                // Létesítményfelelősnél a teljes nevet írjuk ki, nem a nev oszlopot
                }elseif( $tomb['name'] == 'let_fel' ){
                    $kulso_ertek = $adatbazis_kapcsolat->s2('recid teljes_nev', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}'");
                    $kulso_ertek['nev'] = $kulso_ertek['teljes_nev'];
                }else{
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

        // Duplikáláskor feltett kérdés
        if( !get_projekt_szamla_kezel_jog($_GET['id']) ){
            $szamla_html .= nl2('
                <script>
                    duplikalGomb  = "<p class=\"c\"><input class=\"gomb zold\" type=\"button\" value=\"Ok\" onclick=\"alert2()\"></p>";
                    duplikaKerdes = \'Önnek nincs joga új projektszámlát létrehozni!\';
                </script>
            ');
        }else{
            $szamla_html .= nl2('
                <script>
                    duplikalGomb  = "<p class=\"c\"><input class=\"gomb zold\" type=\"button\" value=\"Igen\" onclick=\"szurkit(); location.href = \''.basename(self()).'?uj&ujkent='.$_GET['id'].'\'; \"><input class=\"gomb piros\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
                    duplikaKerdes = \'Számlaszám: '.$sor['sz_szam'].'<br>Szálloda: '.$telephely['nev'].'<br><br>Biztos, hogy duplikálja?\';
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
                            $(\'#pr_szam_gomb\').hide(\'\');
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
	}elseif(isset($_GET['tetel'])){
        
        $szamla_html .= nl2('
            <div class="dt naplo">
                <div class="dtr nFejlec">
                    <div class="dtc">Tárgy</div>
                    <div class="dtc r">Nettó érték ('.get_penznem().')</div>
                    <div class="dtc">Projekt tétel</div>
                </div>
        ');

        // Tételek felsorolása
        $tetelek = $adatbazis_kapcsolat->s('*', 'projektszamla_tetel', "`projektszamla_recid`='{$_GET['id']}'", 'recid');
        foreach($tetelek as $tetel){
            
            $pr_tetel = $adatbazis_kapcsolat->s2('targy', 'projektszerzodes_tetel', "`recid`='{$tetel['projektszerzodes_tetel_recid']}'");
            
            $szamla_html .= nl2('
                <div class="dtr nSor">
                    <div class="dtc">'.$tetel['targy'].'</div>
                    <div class="dtc r">'.number_format($tetel['netto_ert'], 0, '', ' ').'</div>
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

	// Történet
	}elseif(isset($_GET['tortenet'])){

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
                        <div class="dtc">'.nl2br($sor['megj']).'</div>
                        <div class="dtc">'.$sor['insert_usr'].'</div>
                        <div class="dtc">'.$sor['insert_dt'].'</div>
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

