<?php
require_once('../weblap.php');

if( !_jog(JOG_PRSZAMLAK_OLVAS) ) {
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

// Projektszerződés táblázat betöltése
if(isset($_POST['get_projekt_szerzodes_tabla'])){

    // Ha nincs új feltöltés jogunk, akkor kilépünk
    if( !_jog(JOG_PRSZAMLAK_KEZEL) ){
        echo json_encode( array(
                            'hiba'  => 'Önnek nincs joga projekt számlákat kezelni!',
                            'title' => 'Hiba',
                        ) );
        $weblap -> leallit();
        exit;
    }
    
    $html = '';

    // Rendezés beállítása
    $join  = ' JOIN `'.get_db_tabla().'telephely` ON `'.get_db_tabla().'projektszerzodes`.`telephely_id`=`'.get_db_tabla().'telephely`.`id`';
    // Rendezést egyelőre kivettük, ezért mindig a default fog érvényesülni
    switch($_POST['order']){
        case 'bkolt': $order = '`'.get_db_tabla().'projektszerzodes`.`szerz_ossz`,  `'.get_db_tabla().'telephely`.`nev`';               $sz_osszeg_selected = ' selected';  break;
        default     : $order = '`'.get_db_tabla().'telephely`.`nev`,                `'.get_db_tabla().'projektszerzodes`.`szerz_ossz`';                                     break;
    }

    $pr_sz_ek = $adatbazis_kapcsolat->s('
                                        `'.get_db_tabla().'projektszerzodes`.`recid`,
                                        `'.get_db_tabla().'projektszerzodes`.`sz_szam`,
                                        `'.get_db_tabla().'projektszerzodes`.`pr_szam`,
                                        `'.get_db_tabla().'projektszerzodes`.`targy`,
                                        `'.get_db_tabla().'projektszerzodes`.`szallito_id`,
                                        `'.get_db_tabla().'projektszerzodes`.`let_fel`,
                                        `'.get_db_tabla().'projektszerzodes`.`kolt_vis`,
                                        `'.get_db_tabla().'projektszerzodes`.`szerz_ossz`
                                            ',
                                        'projektszerzodes',
                                        "`".get_db_tabla()."projektszerzodes`.`telephely_id`='{$_POST['telephely_id']}' AND `pr_szam`!=''", $order, '', '', '', $join);
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
                </td>
                <td class="l" style="width: 40rem">'.nl2br2($pr['targy']).'</td>
                <td>'. getErtek('nev', 'szallito', 'id', $pr['szallito_id']).'</td>
                <td class="r nowrap">'.number_format($pr['szerz_ossz'], 0, '', ' ').'</td>
            </tr>
            ';
        $i++;
    }

    if($html){

        // Táblázat rendezhető: szálloda + becsült költség, becsült költség + szálloda, típus + becsült költség. Ezek legyenek egy lenyílóból kiválaszthatóak.
        $html = '
            <!--Rendezés:
            <select onchange="get_projekt_tabla(this.value)">
                <option>szálloda + becsült költség</option>
                <option value="bkolt"'.$sz_osszeg_selected.'>becsült költség + szálloda</option>
            </select>-->
            <div style="max-height: 40rem; overflow-y: auto; overflow-x: none;">
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
                        Partner
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prszerzodesek tbody\'), this.value, 3)">
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

// Tétel sorok mentése
if (isset($_POST['tetel_sorok_ment'])) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $recid                  = $_POST['recid'];
    $szamla_netto_erteke    = str_replace(' ', '', $_POST['szamla_netto_erteke']);
    $_POST['adatok'] = json_decode( $_POST['adatok'] );
    
    $tetelek_osszege = '';
    foreach ( $_POST['adatok'][1][1] as $ertek ){
        $ertek = str_replace(' ', '', $ertek);
        $tetelek_osszege += $ertek;
    }
    
    // Ha nincs egy sornyi tételünk sem, akkor hibaüzenet
    if(!$_POST['adatok'][0][1]){
        echo json_encode( array( 
                        'hiba'  => 'Legalább egy tételnek szerepelnie kell a táblázatban, üresen nem menthető!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
                
    // Ha a számla nettó értéke nem egyenlő a tételek összegével, akkor hibaüzenet
    }elseif( $tetelek_osszege != $szamla_netto_erteke ){
        echo json_encode( array( 
                        'hiba'  => 'A számla tételeinek összege ('. number_format($tetelek_osszege, 0, '', ' ').' '.get_penznem().') nem egyezik meg a számla nettó értékével ('. number_format($szamla_netto_erteke, 0, '', ' ').' '.get_penznem().')!<br>Így nem menthető!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
                
    // Tételek kimentése Session-be
    }else{
        $_SESSION['pr_szla_tetel_targy']     = $_POST['adatok'][0][1];
        $_SESSION['pr_szla_tetel_netto_ert'] = $_POST['adatok'][1][1];
        $_SESSION['pr_szla_tetel_pr_tetel']  = $_POST['adatok'][2][1];
    }
    
    // Adott projektszámla rekord lekérdezése
    $prszamla = $adatbazis_kapcsolat->s2('telj_datum', 'projektszamla', "`recid`='{$recid}'");
    if( $prszamla === false ) { $db_error = true; }
    
    // Ha ismert a szerződés ID-ja, akkor a sorokat is updatel-jük (törölkük, majd újra veszúrjuk)
    // Azért kell az is_numeric, mert JS-ből volt, hogy itt egy 'false' értéket kaptunk, és ilyenkor sem kéne beírni még adatbázisb (új feltöltés esete).
    if( $recid && is_numeric($recid) ){
        $sql = $adatbazis_kapcsolat->d('projektszamla_tetel', "`projektszamla_recid`='{$recid}'");
        if( $sql === false ) { $db_error = true; }
        
        $i = 0;
        foreach($_SESSION['pr_szla_tetel_targy'] as $targy){
            $netto_ert = str_replace(' ', '',  $_SESSION['pr_szla_tetel_netto_ert'][$i]);
            $sql = $adatbazis_kapcsolat->i('projektszamla_tetel',
                                                array('projektszamla_recid', 'targy', 'netto_ert', 'projektszerzodes_tetel_recid',          'loginnev',                          'insert_dt',    'insert_usr',                             'update_usr'                              ),
                                                array($recid,                $targy,  $netto_ert,  $_SESSION['pr_szla_tetel_pr_tetel'][$i], $_SESSION[get_db_tabla().'user'],    'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']    )
                                        );
            if( $sql === false ) { $db_error = true; }
            
            // Illetve a projektszerződés tételénél updateljük a teljesítést és nettó értéket
            $sql = $adatbazis_kapcsolat->u('projektszerzodes_tetel', array('teljesites', 'ertek'), array($prszamla['telj_datum'], $netto_ert), "`recid`='{$_SESSION['pr_szla_tetel_pr_tetel'][$i]}'");
            if( $sql === false ) { $db_error = true; }
            
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
if (isset($_POST['pop_tetelek_feltolt'])) {
    
    $html = '';
    
    // Tételek táblázat sorait kiszámoljuk:
//    $felvett_pr_tetelek = array();
    $i = 0;
    foreach($_SESSION['pr_szla_tetel_targy'] as $targy){
        
        $szla_netto_erk = $_SESSION['pr_szla_tetel_netto_ert'][$i];
        
        $pr_tetel = $adatbazis_kapcsolat->s2('recid targy', 'projektszerzodes_tetel', "`recid`='{$_SESSION['pr_szla_tetel_pr_tetel'][$i]}'");
        
        $id_tmp = time();
        $id_tmp .= $i;
        $html .= '<tr class="pop_tetel_'.$id_tmp.'">' .
                            '<td class="pop_targy">' . $targy . '</td>' .
                            '<td class="pop_netto_ert r">' . $szla_netto_erk . '</td>' .
                            '<td class="pop_pr_tetel" data-pr_tetel_recid="' . $pr_tetel['recid'] . '">' . htmlspecialchars_decode( $pr_tetel['targy'] ) . '</td>' .
                            '<td><a onclick="popup_tetel_sor_torol(\'pop_tetel_'.$id_tmp.'\');"><span class="icon"><i class="material-icons">delete</i></span></a></td>' .
                        '</tr>';
        $i++;
    }
    
    $pr_tetel_option = '<option value="">- Válasszon -</option>';
    $projekt    = $adatbazis_kapcsolat->s2('recid', 'projektszerzodes', "`pr_szam`='{$_POST['pr_szam']}'");
    $pr_tetelek = $adatbazis_kapcsolat->s('*', 'projektszerzodes_tetel', "`projektszerzodes_recid`='{$projekt['recid']}'");
    foreach($pr_tetelek as $tetel){
        $pr_tetel_option .= '<option value="'.$tetel['recid'].'">'.$tetel['targy']/*.' - '.datum_kiir($tetel['varh_erk']).' - '. number_format($tetel['varh_ert'], 0, '', ' ').' '.get_penznem()*/.'</option>';
    }
   
    echo json_encode( array(
        'html'          => $html,
        'pr_tetelek'    => $pr_tetel_option,
        ) );
    
    $weblap -> leallit();
    exit();
}

// Van-e a session-ban tétel
if (isset($_POST['van_tetel'])) {
    
    $szamla_netto_erteke = str_replace(' ', '', $_POST['netto']);
    
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
    if( $_POST['visszaadva']=='false' && $_POST['visszaadva_dt']<'9000.00.00' ){
        echo json_encode(array(
            'hiba'  => 'A Visszaadás dátumát csak akkor töltse ki, ha a visszaadva pipát be is kapcsolja!',
            'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); szurkit_zar(\'mehet_megsem_mutat\'); $(\'#visszaadva_dt\').focus();">',
            'title' => 'Hiba',
        ));
        $weblap -> leallit();
        exit();
    }
    
    // Ellenőrizzük, hogy a kiválasztott szálloda és a projekt szám stimmel-e
    $projekt = $adatbazis_kapcsolat->s2('telephely_id', 'projektszerzodes', "`pr_szam`='{$_POST['pr_szam']}'");
    if( $projekt['telephely_id']!=$_POST['telephely_id'] ){
        echo json_encode(array(
            'hiba'  => 'A projekt szám nem megfelelő szállodához tartozik!',
            'gomb'  => '<input type="button" class="gomb" value="Ok" onclick="alert2(); szurkit_zar(\'mehet_megsem_mutat\'); $(\'#telephely_id\').focus();">',
            'title' => 'Hiba',
        ));
        $weblap -> leallit();
        exit();
    }
    
    // Ha nincs feltöltött tétel:
    if( !$_SESSION['pr_szla_tetel_targy'] ){
        
        echo json_encode( array(
                            'van_tetel' => 'nincs',
                            'hiba'      => 'Nem töltött fel tételeket, így nem menthető a Projekt számla!',
                            'gomb'      => '<input type="button" class="gomb" value="Ok" onclick="alert2(); szurkit_zar(\'mehet_megsem_mutat\');">',
                            'title'     => 'Hiba',
                        ) );
        $weblap->leallit();
        exit();
        
    // Ha van tétel, akkor még az összegek egyezését is ellenőrizni kell
    }else{
        
        // Ha a számla összege nem egyezik a feltöltött tételek összegével
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
        
        echo json_encode( array(
                            'van_tetel' => 'van'
                        ) );
        $weblap->leallit();
        exit();
    }
    
    
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

    // Gombok kirajzolása minden oldalra
    $gombok = '';
    if( isset($_GET['uj']) ) {
        
        // Új feltöltéskor a tétel sorokat töröljük, ne az legyen, hogy szerkesztésből maradt adatokkal számoljunk
        $_SESSION['pr_szla_tetel_targy']     = '';
        $_SESSION['pr_szla_tetel_netto_ert'] = '';
        $_SESSION['pr_szla_tetel_pr_tetel']  = '';

        if( _jog(JOG_PRSZAMLAK_KEZEL) ){
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
        }
        
    // Módosítás gombok
    }else{
        
        // Módosításkor updateljük a Session-ben a tételek adatait, így lesz jó a popupban
        $i = 0;
        $_SESSION['pr_szla_tetel_targy'] = $_SESSION['pr_szla_tetel_netto_ert'] = array();
        $tetelek = $adatbazis_kapcsolat->s('*', 'projektszamla_tetel', "`projektszamla_recid`='{$_GET['id']}'", 'recid');
        foreach($tetelek as $tetel){
            $_SESSION['pr_szla_tetel_targy'][$i]     = $tetel['targy'];
            $_SESSION['pr_szla_tetel_netto_ert'][$i] = number_format($tetel['netto_ert'], 0, '', ' ');
            $_SESSION['pr_szla_tetel_pr_tetel'][$i]  = $tetel['projektszerzodes_tetel_recid'];
            $i++;
        }
        
        // Törlés gomb
        if( !_jog(JOG_PRSZAMLAK_KEZEL) ){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Önnek nincs joga a projekt számla törléséhez!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        }else{
            $gombok .= '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
        }
        
        // Tételek kezelése
        if( _jog(JOG_PRSZAMLAK_KEZEL) ){
            
            $gombok .= ' <input type="button" class="gomb" onclick="csoportMuvelet(\'projektszamla_tetelek_kezelese\');" value="Tételek kezelése">';
            // Ha nem az Adatlapon volnánk, akkor szükségünk lesz egy rejtett inputra, ami az adatbázisban levő nettó értéket tartalmazza, hogy bármelyik fül alatt szerkeszteni lehessen a tételeket, összehasonlítva az aktuális számla nettó értékével
            $gombok .= ' <input type="hidden" id="netto_db" value="'.$sor['netto'].'">';
            $gombok .= ' <input type="hidden" id="pr_szam_db" value="'.$sor['pr_szam'].'">';
        }
        
        if( isset($_GET['modosit']) && _jog(JOG_PRSZAMLAK_KEZEL) ) {
            
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
        
        $js_submit_elott = nl2('
            
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
		if(isset($_GET['ujkent'])){
			foreach($akt_tomb as $ertek){
				$sor[$ertek['name']]=$_SESSION[$ertek['name']];
			}
		}
	    $js = js_kiir($akt_tomb, 'form_ell_admin()');

	    $form_sablon = fajlbol_olvas('../sablon/admin/form_uj.html', true);

        // Javascipt-es ellenőrzéshez
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
           $json_sor[$kulcs] = str_replace(array("\r\n", "\r", "\n", "'"), array("#UJSOR#", "#UJSOR#", "#UJSOR#", "#APOSZTROF#"), $elem);
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

	    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");

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
            $json_sor[$kulcs] = str_replace(array("\r\n", "\r", "\n", "'"), array("#UJSOR#", "#UJSOR#", "#UJSOR#", "#APOSZTROF#"), $elem);
        }
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

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
                    }, 100);
                    
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