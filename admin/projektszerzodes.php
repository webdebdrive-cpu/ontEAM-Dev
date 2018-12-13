<?php
require_once('../weblap.php');

if( !_jog(JOG_PRKEZEL_SZERZODES_OLVAS) ) {
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

// Több kép menedzseléséhez
$tobb_kep = true; // Ha true, akkor js/jquery-ui.js, js/admin_sorrendez.js aktivál és minify!, admin/tobb_kep.php aktivál
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

// Projekt táblázat betöltése
if(isset($_POST['get_projekt_tabla'])){

    // Ha nincs új feltöltés jogunk, akkor kilépünk
    if( !_jog(JOG_PRKEZEL_SZERZODES_KEZEL) ){
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
    switch($_POST['order']){
        case 'bkolt': $order = '`'.get_db_tabla().'projektkezeles`.`bkolt`, `'.get_db_tabla().'telephely`.`nev`';         $bkolt_selected = ' selected';  break;
        case 'tipus': $order = '`'.get_db_tabla().'projektkezeles`.`tipus`, `'.get_db_tabla().'projektkezeles`.`bkolt`';  $tipus_selected = ' selected';  break;
        default     : $order = '`'.get_db_tabla().'telephely`.`nev`,        `'.get_db_tabla().'projektkezeles`.`bkolt`';                                  break;
    }

    $szallodak = get_akt_login_szallodai();
    $prek = $adatbazis_kapcsolat->s('
                                        `'.get_db_tabla().'projektkezeles`.`recid`,
                                        `'.get_db_tabla().'projektkezeles`.`ikt_szam`,
                                        `'.get_db_tabla().'projektkezeles`.`nev`,
                                        `'.get_db_tabla().'projektkezeles`.`tipus`,
                                        `'.get_db_tabla().'projektkezeles`.`stat_id`,
                                        `'.get_db_tabla().'projektkezeles`.`bkolt`,
                                        `'.get_db_tabla().'projektkezeles`.`telephely_id`,
                                        `'.get_db_tabla().'projektkezeles`.`insert_dt`
                                            ',
                                        'projektkezeles',
                                        "`telephely_id` IN (".implode(',', $szallodak).")", $order, '', '', '', $join);
//echo $adatbazis_kapcsolat->gp() . '<br>';
    $i = 1;
    foreach($prek as $pr){

        // Szálloda
        $szalloda = $adatbazis_kapcsolat->s2('nev', 'telephely', "`id`='{$pr['telephely_id']}'");
        
        // Tipus
        require_once 'selectek.php';
        foreach($select_pri_tipus as $tip){
            if($tip['value']==$pr['tipus']){
                $tipus = $tip['name'];
            }
        }
        if(!$pr['tipus']){
            $tipus = '-';
        }

        $html .= '
            <tr class="kijSor'.$kiemelt.'" data-i="'.$i.'" data-recid="'.$pr['recid'].'" data-ikt_szam="'.$pr['ikt_szam'].'">
                <td>'.$szalloda['nev'].'</td>
                <td style="width: 30rem">'.$pr['nev'].'</td>
                <td>'.$tipus.'</td>
                <td class="r nowrap">'.number_format($pr['bkolt'], 0, '', ' ').'</td>
                <td>'.datum_kiir($pr['insert_dt']).'</td>
            </tr>
            ';
        $i++;
    }

    if($html){

        // Táblázat rendezhető: szálloda + becsült költség, becsült költség + szálloda, típus + becsült költség. Ezek legyenek egy lenyílóból kiválaszthatóak.
        $html = '
            Rendezés:
            <select onchange="get_projekt_tabla(this.value)">
                <option>szálloda + becsült költség</option>
                <option value="bkolt"'.$bkolt_selected.'>becsült költség + szálloda</option>
                <option value="tipus"'.$tipus_selected.'>típus + becsült költség</option>
            </select>
            <div style="max-height: 40rem; overflow-y: auto; overflow-x: none;">
                <table class="csikos v2 kijCont csak_egy_kijelolheto" id="prkezelesek" style="cursor: default;">
                <thead>
                <tr>
                    <th>
                        Szálloda
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prkezelesek tbody\'), this.value, 1)">
                    </th>
                    <th>
                        Név
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prkezelesek tbody\'), this.value, 2)">
                    </th>
                    <th>
                        Típus
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prkezelesek tbody\'), this.value, 3)">
                    </th>
                    <th>
                        Becsült költség ('.get_penznem().')
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prkezelesek tbody\'), this.value, 4)">
                    </th>
                    <th>
                        Létrehozás dátuma
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prkezelesek tbody\'), this.value, 5)">
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
        $html = '<h1>Nem található projekt terv!</h1><script>$(\'#eloterj_igneyles_csatolForm div.kuldes\').html(\'<input type="button" class="gomb zold" value="Ok" onclick="popupZar();">\');  $(\'#eloterj_igneyles_csatolCim\').html(\'Hiba\');</script>';
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
    
    $recid              = $_POST['recid'];
    $szerzodes_osszege  = str_replace(' ', '', $_POST['szerzodes_osszege']);
    $_POST['adatok'] = json_decode( $_POST['adatok'] );
    
    $tetelek_osszege = '';
    foreach ( $_POST['adatok'][2][1] as $ertek ){
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
                
    // Ha a szerződés összege nem egyenlő a tételek összegével, akkor hibaüzenet
    }elseif( $tetelek_osszege != $szerzodes_osszege ){
        echo json_encode( array( 
                        'hiba'  => 'A szerződés tételeinek összege ('. number_format($tetelek_osszege, 0, '', ' ').' '.get_penznem().') nem egyezik meg a szerződés összegével ('. number_format($szerzodes_osszege, 0, '', ' ').' '.get_penznem().')!<br>Így nem menthető!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
                
    // Tételek kimentése Session-be
    }else{
        $_SESSION['pr_szer_tetel_targy']    = $_POST['adatok'][0][1];
        $_SESSION['pr_szer_tetel_varh_erk'] = $_POST['adatok'][1][1];
        $_SESSION['pr_szer_tetel_varh_ert'] = $_POST['adatok'][2][1];
    }
    
    // Ha ismert a szerződés ID-ja, akkor a sorokat is updatel-jük
    if( $recid ){
        $sql = $adatbazis_kapcsolat->d('projektszerzodes_tetel', "`projektszerzodes_recid`='{$recid}'");
        if( $sql === false ) { $db_error = true; }
        
        $i = 0;
        foreach($_SESSION['pr_szer_tetel_targy'] as $targy){
            $varh_erk = str_replace('.', '-', $_SESSION['pr_szer_tetel_varh_erk'][$i]);
            $varh_ert = str_replace(' ', '',  $_SESSION['pr_szer_tetel_varh_ert'][$i]);
            $sql = $adatbazis_kapcsolat->i('projektszerzodes_tetel',
                                                array('projektszerzodes_recid', 'targy', 'varh_erk', 'varh_ert', 'loginnev',                          'insert_dt',    'insert_usr',                             'update_usr'                              ),
                                                array($recid,                   $targy,  $varh_erk,  $varh_ert,  $_SESSION[get_db_tabla().'user'],    'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']    )
                                        );
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

// Tétel sorok mentése
if (isset($_POST['pop_tetelek_feltolt'])) {
    
    $html = '';
    
    
    $i = 0;
    foreach($_SESSION['pr_szer_tetel_targy'] as $targy){
        
        $szla_varh_erk = $_SESSION['pr_szer_tetel_varh_erk'][$i];
        $szla_varh_ert = $_SESSION['pr_szer_tetel_varh_ert'][$i];
        
        $id_tmp = time();
        $id_tmp .= $i;
        $html .= '<tr class="pop_tetel_'.$id_tmp.'">' .
                            '<td class="pop_targy">' . $targy . '</td>' .
                            '<td class="pop_varh_erk">' . $szla_varh_erk . '</td>' .
                            '<td class="pop_varh_ert r">' . $szla_varh_ert . '</td>' .
                            '<td></td>' .
                            '<td></td>' .
                            '<td><a onclick="popup_tetel_sor_torol(\'pop_tetel_'.$id_tmp.'\');"><span class="icon"><i class="material-icons">delete</i></span></a></td>' .
                        '</tr>';
        $i++;
    }
   
    echo json_encode( array(
        'html' => $html
        ) );
    
    $weblap->leallit();
    exit();
}

// Van-e a session-ban tétel
if (isset($_POST['van_tetel'])) {
    
    $szerzodes_osszege = str_replace(' ', '', $_POST['szerzodes_osszege']);
    
    // Ha nincs feltöltött tétel:
    if( !$_SESSION['pr_szer_tetel_targy'] ){
        
        echo json_encode( array(
                            'van_tetel' => 'nincs',
                            'hiba'      => 'Nem töltött fel tételeket, így nem menthető a Projekt szerződés!'
                        ) );
        $weblap->leallit();
        exit();
        
    // Ha van tétel, akkor még az összegek egyezésté is ellenőrizni kell
    }else{
        
        // Ha a szerződés összege nem egyezik a feltöltött tételek összegével
        $tetelek_osszege = 0;
        foreach ( $_SESSION['pr_szer_tetel_varh_ert'] as $ertek ){
            $ertek = str_replace(' ', '', $ertek);
            $tetelek_osszege += $ertek;
        }
        if( $tetelek_osszege != $szerzodes_osszege ){
            echo json_encode( array(
                                'hiba' => 'A szerződés tételeinek összege nem egyezik meg a szerződés összegével!<br>Így nem menthető!'
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
                            `'.get_db_tabla().$megnevezes.'`.`telephely_id` as \'Szálloda\',
                            `'.get_db_tabla().$megnevezes.'`.`sz_szam` as \'Szerződés szám\',
                            `'.get_db_tabla().$megnevezes.'`.`kelte` as \'Kelte\',
                            `'.get_db_tabla().$megnevezes.'`.`szallito_id` as \'Szerződő partner\',
                            `'.get_db_tabla().$megnevezes.'`.`pr_szam` as \'Projekt szám\',
                            `'.get_db_tabla().$megnevezes.'`.`targy` as \'Tárgy\',
                            `'.get_db_tabla().$megnevezes.'`.`kolt_vis` as \'Költségviselő\',
                            `'.get_db_tabla().$megnevezes.'`.`kiv_kezd` as \'Kivitelezés kezdete\',
                            `'.get_db_tabla().$megnevezes.'`.`kiv_vege` as \'Kivitelezés vége\',
                            `'.get_db_tabla().$megnevezes.'`.`szerz_ossz` as \'Szerződött összeg\',
                            `'.get_db_tabla().$megnevezes.'`.`let_fel` as \'Létesítmény felelős\',
                            `'.get_db_tabla().$megnevezes.'`.`muszak_atv` as \'Műszaki átvétel\',
                            `'.get_db_tabla().$megnevezes.'`.`bank_gar` as \'Bank garancia\',
                            `'.get_db_tabla().$megnevezes.'`.`bank_gar_vege` as \'Bank garancia vége\',
                            `'.get_db_tabla().$megnevezes.'`.`szamlazva` as \'Számlázva\',
                                
                            `'.get_db_tabla().$megnevezes.'_tetel`.`targy` as `Tétel tárgy`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`varh_erk` as `Számla várh. érk.`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`varh_ert` as `Számla várh. értéke (Ft)`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`teljesites` as `Teljesítés`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`ertek` as `Érték`
                            '.$lekerdezes_fromtol;

                $query      = mysqli_query($db_kapcs, $sql);
// echo $sql; exit;
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
                        if($kulcs=='Műszaki átvétel' || $kulcs=='Kelte' || $kulcs=='Kivitelezés kezdete' || $kulcs=='Kivitelezés vége' || $kulcs=='Bank garancia vége' || $kulcs=='Bank garancia vége' || $kulcs=='Teljesítés'  ){
                            $sor = datum_kiir($sor);
                        }
                        if($kulcs=='Számlázva'){
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
                            `telephely_id` as \'Szálloda\',
                            `sz_szam` as \'Szerződés szám\',
                            `kelte` as \'Kelte\',
                            `szallito_id` as \'Szerződő partner\',
                            `pr_szam` as \'Projekt szám\',
                            `targy` as \'Tárgy\',
                            `kolt_vis` as \'Költségviselő\',
                            `kiv_kezd` as \'Kivitelezés kezdete\',
                            `kiv_vege` as \'Kivitelezés vége\',
                            `szerz_ossz` as \'Szerződött összeg\',
                            `let_fel` as \'Létesítmény felelős\',
                            `muszak_atv` as \'Műszaki átvétel\',
                            `bank_gar` as \'Bank garancia\',
                            `bank_gar_vege` as \'Bank garancia vége\',
                            `szamlazva` as \'Számlázva\'
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
                        if($kulcs=='Szerződő partner'){
                            $sor = getErtek('nev', 'szallito', 'id', $sor);
                        }
                        if($kulcs=='Költségviselő'){
                            $sor = erteklista(ERTEKLISTA_KOLTSEGVISELO, $sor);
                        }
                        if($kulcs=='Műszaki átvétel' || $kulcs=='Kelte' || $kulcs=='Kivitelezés kezdete' || $kulcs=='Kivitelezés vége' || $kulcs=='Bank garancia vége' || $kulcs=='Bank garancia vége'){
                            $sor = datum_kiir($sor);
                        }
                        if($kulcs=='Számlázva'){
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
        
        // Új feltöltéskor a tétel sorokat töröljük, ne az legyen, hogy szerkesztésből maradt adaotkkal számoljunk
        $_SESSION['pr_szer_tetel_targy']    = '';
        $_SESSION['pr_szer_tetel_varh_erk'] = '';
        $_SESSION['pr_szer_tetel_varh_ert'] = '';

        if( _jog(JOG_PRKEZEL_SZERZODES_KEZEL) ){
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="szurkit(\'mehet_megsem_rejt\'); $(\'#szerk_form\').submit()">
                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="szurkit(\'mehet_megsem_rejt\'); formModositva=false; szamlaBetolt($(\'#szamlaList li:first-of-type\').attr(\'data-recid\'), \''.$megnevezes.'.php\');">
                </div>
            ');
            
            $gombok .= ' <input type="button" class="gomb" onclick="if( !$(\'#szerz_ossz\').val() || $(\'#szerz_ossz\').val()==\'0\' ){ alert2(\'Tételek kezelése előtt adja meg a szerződött összeget!\', \'\', \'Hiba\'); }else{ csoportMuvelet(\'projektszerzodes_tetelek_kezelese\'); }" value="Tételek kezelése">';

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
        $_SESSION['pr_szer_tetel_targy'] = $_SESSION['pr_szer_tetel_varh_erk'] = $_SESSION['pr_szer_tetel_varh_ert'] = array();
        $tetelek = $adatbazis_kapcsolat->s('*', 'projektszerzodes_tetel', "`projektszerzodes_recid`='{$_GET['id']}'", 'recid');
        foreach($tetelek as $tetel){
            $_SESSION['pr_szer_tetel_targy'][$i]    = $tetel['targy'];
            $_SESSION['pr_szer_tetel_varh_erk'][$i] = str_replace('-', '.', $tetel['varh_erk']);
            $_SESSION['pr_szer_tetel_varh_ert'][$i] = number_format($tetel['varh_ert'], 0, '', ' ');
            $i++;
        }
        
        // Törlés gomb
        if( !_jog(JOG_PRKEZEL_SZERZODES_KEZEL) ){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Önnek nincs joga a projekt szerződés törléséhez!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        }else{
            $gombok .= '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
        }
        
        // Tételek kezelése
        if( _jog(JOG_PRKEZEL_SZERZODES_KEZEL) ){
            $gombok .= ' <input type="button" class="gomb" onclick="if( !$(\'#szerz_ossz\').val() || $(\'#szerz_ossz\').val()==\'0\' ){ alert2(\'Tételek kezelése előtt adja meg a szerződött összeget!\', \'\', \'Hiba\'); }else{ csoportMuvelet(\'projektszerzodes_tetelek_kezelese\'); }" value="Tételek kezelése">';
            // Ha nem az Adatlapon volnánk, akkor szükségünk lesz egy rejtett inputra, ami az adatbázisban levő szerződéses összeget tartalmazza, hogy bármelyik fül alatt szerkeszteni lehessen a tételeket, összehasonlítva az aktuális szerződéses összeggel
            $gombok .= ' <input type="hidden" id="szerz_ossz_db" value="'.$sor['szerz_ossz'].'">';
        }
        
        if( isset($_GET['modosit']) && _jog(JOG_PRKEZEL_SZERZODES_KEZEL) ) {
            
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="szurkit(\'mehet_megsem_rejt\'); $(\'#szerk_form\').submit()">
                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="szurkit(\'mehet_megsem_rejt\'); formModositva=false; szamlaBetolt($(\'#szamlaList li:first-of-type\').attr(\'data-recid\'), \''.$megnevezes.'.php\');">
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
	if( isset($_GET['uj']) ){
        
        $js_submit_elott = nl2('
            
                var dfrd_pr_mehet = false;
                var hibauzenet    = \'\';
                dfrd_pr_szerzodes = $.Deferred();
                $.ajax({
                    url : \'projektszerzodes.php\',
                    type: \'POST\',
                    data: {
                        szerzodes_osszege: $(\'#szerz_ossz\').val(),
                        van_tetel        : Date.now()
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
                            }
                            dfrd_pr_szerzodes.resolve();
                        }
                        return false;
                    }
                });
                
                $.when(dfrd_pr_szerzodes).then(function() {
                    urlap_mehet = false;
                    if( !dfrd_pr_mehet ){
                        alert2(hibauzenet, \'<input type="button" class="gomb" value="Ok" onclick="szurkit_zar(\\\'mehet_megsem_mutat\\\'); alert2();">\', \'Hiba\');
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

	// Projektkezelés módosítása
	} elseif(isset($_GET['modosit'])) {

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
        }

	    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");

        foreach($akt_tomb as $tomb) {
            if( $tomb['type'] == 'autocomplete' ) {
                
                if($tomb['csoport']){
                    $kulso_ertek = $adatbazis_kapcsolat->s2('info', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}' AND `csoport`='{$tomb['csoport']}'", '', '1');
                    $kulso_ertek['nev'] = $kulso_ertek['info'];
                // Projektfelelősnél a teljes nevet írjuk ki, nem a nev oszlopot
                }elseif( $tomb['name'] == 'pfelelos' ){
                    $kulso_ertek = $adatbazis_kapcsolat->s2('recid teljes_nev', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}'", '', '1');
                    $kulso_ertek['nev'] = $kulso_ertek['teljes_nev'];
                }else{
                    $kulso_ertek = $adatbazis_kapcsolat->s2('recid nev', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}'", '', '1');
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
                    <div class="dtc">Számla várh. érk.</div>
                    <div class="dtc">Számla várh. értéke ('.get_penznem().')</div>
                    <div class="dtc">Teljesítés</div>
                    <div class="dtc">Érték ('.get_penznem().')</div>
                </div>
        ');

        // Tételek felsorolása
        $tetelek = $adatbazis_kapcsolat->s('*', 'projektszerzodes_tetel', "`projektszerzodes_recid`='{$_GET['id']}'", 'recid');
        foreach($tetelek as $tetel){
            $szamla_html .= nl2('
                <div class="dtr nSor">
                    <div class="dtc">'.$tetel['targy'].'</div>
                    <div class="dtc">'.datum_kiir($tetel['varh_erk']).'</div>
                    <div class="dtc">'.number_format($tetel['varh_ert'], 0, '', ' ').'</div>
                    <div class="dtc">'.datum_kiir($tetel['teljesites']).'</div>
                    <div class="dtc">'.number_format($tetel['ertek'], 0, '', ' ').'</div>
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