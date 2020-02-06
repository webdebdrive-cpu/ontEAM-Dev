<?php
require_once('../weblap.php');

if( !_jog(JOG_UTFEL_MEGTEKINT) ) {
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
$akt_tomb               = $utemezett_feladatok_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'ütemezett feladatot';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

$szamla_html = '';

// Dokumentumok fülhöz kell majd
$tobb_kep = true;
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

// Mentés előtt popupban megmutatjuk, mi lesz az ütemezés
if( isset($_GET['datumra_utemezes']) ) {

    echo datumra_utemezes_popup($megnevezes);

    /*
    include('datumra_utemezes.php');

    // Üres értékeket 0-val adjunk át
    $sor = $adatbazis_kapcsolat->s2('*', $megnevezes, "`recid`='{$_GET['id']}'");
    foreach($sor as $kulcs => $ertek){
        if(!$_GET[$kulcs]){
            $_GET[$kulcs] = '0';
        }
    }

    $utemezes = 'Folyamatos';
    if($_GET['idoszakos']){
        if($_GET['aktkezd_d']>'0000.00.00' && $_GET['aktkezd_d']>'0000.00.00'){
            $utemezes = $_GET['aktkezd_d'].'-'.$_GET['aktvege_d'];
            if($_GET['aktvege_d']<date('Y-m-d')){
                $utemezes = $_GET['aktvege_d'].' után nincsenek további ütemezések.';
                $kell_lista = false;
            }
        } else {
            require_once 'selectek.php';
            $kezdho = '';
            foreach($select_honapok as $honap){
                if($honap['value']==$_GET['kezdho']){
                    $kezdho = $honap['name'];
                    break;
                }
            }
            $vegeho = '';
            foreach($select_honapok as $honap){
                if($honap['value']==$_GET['vegeho']){
                    $vegeho = $honap['name'];
                    break;
                }
            }
            $utemezes = $kezdho.' '.$_GET['kezdnap'].'. - '.$vegeho.' '.$_GET['vegenap'].'.';
        }
    }

    if(!$sor['esem_d'] || $sor['esem_d']=='0000-00-00' || $sor['esem_d']=='    .  .  '){
        $sor['esem_d'] = date('Y.m.d');
    }

    $aktkezd_d  = str_replace('-', '.', $_GET['aktkezd_d']);
    $aktvege_d  = str_replace('-', '.', $_GET['aktvege_d']);
// $szamla_html .= "esemd: $esem_d<br>cikltip: $_GET[cikltip]<br>ciklaltip: $_GET[ciklaltip]<br>ciklszorz: $_GET[ciklszorz_]<br>ciklho: $_GET[ciklho]<br>ciklhet: $_GET[ciklhet]<br>ciklnap: $_GET[ciklnap]<br>idoszakos: $_GET[idoszakos]<br>kezdho: $_GET[kezdho]<br>kezdnap: $_GET[kezdnap]<br>vegeho: $_GET[vegeho]<br>vegenap: $_GET[vegenap]<br>aktkezd_d: $aktkezd_d<br>aktvege_d: $aktvege_d";

    // $getnextvaliddate = getnextvaliddate(false, $_GET['esem_d'], $_GET['cikltip'], $_GET['ciklaltip'], $_GET['ciklszorz_'], $_GET['ciklho'], $_GET['ciklhet'], $_GET['ciklnap'], $_GET['idoszakos'], $_GET['kezdho'], $_GET['kezdnap'], $_GET['vegeho'], $_GET['vegenap'], $aktkezd_d, $aktvege_d);

    $esedekes = $_GET['esnap_d'];
    if(!$esedekes || $esedekes=='0000.00.00' || $esedekes=='    .  .  ' || $esedekes<=date('Y-m-d')){
        $getnextvaliddate = getnextvaliddate(false, $sor['esem_d'], $sor['cikltip'], $sor['ciklaltip'], $sor['ciklszorz'], $sor['ciklho'], $sor['ciklhet'], $sor['ciklnap'], $sor['idoszakos'], $sor['kezdho'], $sor['kezdnap'], $sor['vegeho'], $sor['vegenap'], $aktkezd_d, $aktvege_d);
        $esedekes = $getnextvaliddate;
    }

// Fontos, hogy az első ütemezés az esedékesség napja legyen, Ezért lett kivéve az alábbi kód, mert a getnextvaliddate-et futtatva már más dátum jönne ki első időpontra, pedig oda az esedékességet kell írni. 
//    if( !is_numeric( substr($esedekes, 0, 1) ) ||  $esedekes == '    .  .  ' || $getnextvaliddate>$esedekes){
//        $esedekes = $getnextvaliddate;
//    } else {
        $getnextvaliddate = $esedekes;
    //}

    $szamla_html .= '<br>Szabály: ' . getcimke( $_GET['cikltip'], $_GET['ciklaltip'], $_GET['ciklszorz_'], $_GET['ciklho'], $_GET['ciklhet'], $_GET['ciklnap'], $getnextvaliddate);
    $szamla_html .= '<br>Ütemezés: ' . $utemezes;
    $szamla_html .= '<br>Esedékes: ' . $esedekes.' '.het_napja_txt($esedekes);
    $szamla_html .= '<br>';
    $szamla_html .= '<br>Várható ütemezés:<br>';

    $szamla_html .= $getnextvaliddate . '<br>';
    for($i=0; $i<9; $i++){
        $getnextvaliddate = getnextvaliddate(false, $getnextvaliddate, $_GET['cikltip'], $_GET['ciklaltip'], $_GET['ciklszorz_'], $_GET['ciklho'], $_GET['ciklhet'], $_GET['ciklnap'], $_GET['idoszakos'], $_GET['kezdho'], $_GET['kezdnap'], $_GET['vegeho'], $_GET['vegenap'], $aktkezd_d, $aktvege_d);
        $szamla_html .= $getnextvaliddate . '<br>';
    }

    // A hibaüzenetet ne tegyük a mezőbe
    if( mb_substr($getnextvaliddate, 0, 10)=='Hibaüzenet' ){
        $getnextvaliddate = '    .  .  ';
    }

    echo json_encode(
            array(
                'uzenet'    => 'Ön a következő ütemezést választotta:<br><div class="l" style="max-height: 20rem; overflow: auto">'.$szamla_html.'</div><br>Folytatja?',
                'esedekes'  => $esedekes
            )
        );
*/
    $weblap -> leallit();
    exit();

}

// CSV generálása
if( isset($_GET['csv']) ) {

    require_once 'selectek_szemelyzet.php';
    
    // CSV export előkészítése
    list($mibol, $mibe, $filename, $open) = csv_export_elokeszit($megnevezes);
    if( $open ) {

        $sql = 'SELECT
                `'.get_db_tabla().$megnevezes.'`.`id` as \'Feladatszám\',
                `'.get_db_tabla().$megnevezes.'`.`telephely_id` as \'Szálloda\',
                `'.get_db_tabla().$megnevezes.'`.`obj_id` as \'Helyiség\',
                `'.get_db_tabla().$megnevezes.'`.`gep_id` as \'Gép\',
                `'.get_db_tabla().$megnevezes.'`.`nev` as \'Feladat\',
                `'.get_db_tabla().$megnevezes.'`.`szemelyzet_id` as \'Hozzárendelt személyzet\',
                `'.get_db_tabla().$megnevezes.'`.`szallito_id` as \'Külső kivitelező\'
                '. csv_export_get_from_tol($megnevezes);
                $query = mysqli_query($db_kapcs, $sql);
                
                // Nagyobb CSV-kre kérdezzünk rá előzetesen
                $osszes_sor = mysqli_num_rows($query);
                if( !$_GET['mehet'] && $osszes_sor>CSV_EXPORT_LIMIT ) {
                    $gomb = '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#navGombok .excel\').attr(\'onclick\', \'csv_general( this, \\\'\\\', \\\'&mehet=1\\\');\'); $(\'.navGombok a.excel\').click();"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">';
                    echo json_encode(array('hiba' => 'A CSV export több mint '.CSV_EXPORT_LIMIT.' sort tartalmaz. Ennek előállítása hosszabb időbe telik majd.<br>Biztosan folytatja?', 'gomb' => $gomb));
                    $weblap -> leallit();
                    exit;
                } else {
                    $sql .= $_SESSION['akt_sql_order'];    
                }

        $query      = mysqli_query($db_kapcs, $sql);
        $csvhead    = array();
        while ( $fejlec_nevek = mysqli_fetch_field($query) ) {
            $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
        }
        fputcsv($open, $csvhead, ';');

        $i = 0;
        while( $sorok = mysqli_fetch_assoc($query) ) {

            $csvcont = array();
            foreach( $sorok as $kulcs => $sor ) {
                if($kulcs=='Szálloda'){
                    $sor = getErtek('nev', 'telephely', 'id', $sor);
                }
                if($kulcs=='Helyiség'){
                    $sor = getErtek('nev', 'obj', 'recid', $sor);
                }
                if($kulcs=='Gép'){
                    $sor = getErtek('nev', 'eszkozkezeles', 'recid', $sor);
                }
                if($kulcs=='Hozzárendelt személyzet'){
                    foreach( $select_szemelyzet as $szemely ){
                        if( $szemely['value'] == $sor ){
                           $sor =  $szemely['name'];
                        }
                    }
                }
                if($kulcs=='Külső kivitelező'){
                    $sor = getErtek('nev', 'szallito', 'id', $sor);
                }
                
                // Lehet nem volna elég 1-szer sőt kétszer sem ráereszteni a htmlspecialchars_decode-ot
                $sor = htmlspecialchars_decode( $sor );
                while ( strpos($sor, '&amp;') !== false || strpos($sor, '&quot;') !== false ) {
                    $sor = htmlspecialchars_decode( $sor );
                }
                $csvcont[] = iconv($mibol, $mibe.'//TRANSLIT', strip_tags( $sor ) );
            }

            fputcsv($open, $csvcont, ';');

            // 100 soronként kiíratunk
            if( $i % 1000 == 0 && $i > 0 ) {
                // Progress
                echo json_encode(array('pr' => number_format(($i / $osszes_sor) * 100, 0, '', ''))).PHP_EOL;
                
                // Felszabadítás
                ob_flush();
                flush();
            }

            $i++;
        }

        // Progress
        echo json_encode(array('pr' => '100')).PHP_EOL;

        // Felszabadítás
        ob_flush();
        flush();
    }

    csv_export_lezar($fh, $filename, 'Ütemezett feladatok');
}

// Tartalom
if( !$_GET['ajax'] && !isset($_GET['ut_teszt']) ) {
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

    if( isset($_GET['modosit']) || isset($_GET['naptar']) || isset($_GET['utemezes']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
        // Számla fülek
        $ful_tomb = array(
            'naptar'        => 'Naptár',
            'modosit'       => 'Adatlap',
            'utemezes'      => 'Ütemezések',
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

        // Újat csak "Ütemezett feladatok kezelése" joggal lehet
        if(_jog(JOG_UTFEL_KEZEL) ){
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
                </div>
            ');

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

        // Js, a törlés gombhoz
	    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';

        // Csak saját bejelentés törölhető!
        if( !_jog(JOG_UTFEL_TOROL) && $sor['loginnev'] != $_SESSION[get_db_tabla().'user'] ) {
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Önnek nincs joga '.$szoveg.' törölni!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        } else {
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt az '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
        }

        if( _jog(JOG_UTFEL_KEZEL) ){

            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
                </div>
            ').$gombok;
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

	// Új termék felvitele
	if( isset($_GET['uj']) && _jog(JOG_UTFEL_KEZEL) ){

	    // FORM
		if($_GET['ujkent']){
            $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, 'recid='.$_GET['ujkent']);
            if( $sor ) {
                foreach($akt_tomb as $ertek) {
                    if( $ertek['name'] != 'id' ) {
                        $_SESSION[$ertek['name']] = stripslashes( $sor[$ertek['name']] );
                    } else {
                        $_SESSION[$ertek['name']] = '';
                    }
    			}
            }

			foreach($akt_tomb as $ertek){
				$sor[$ertek['name']] = $_SESSION[$ertek['name']];
			}

            // Újként szerkesztésnél az ütemezés tipust beállítani, meglegyen az onchange + adatok beállítása
            $checkboxok = '';
            if($sor['cikltip']=='d' && $sor['ciklaltip']==2){
                $checkboxok .= '$(\'#hetkoz_kent\').prop(\'checked\', true);';
            }
            if($sor['cikltip']=='w' && $sor['ciklszorz']){
                $sor['ism_het'] = $sor['ciklszorz'];
            }
            if($sor['cikltip']=='m' && $sor['ciklszorz']){
                $sor['ciklho'] = $sor['ciklszorz'];
            }
            if($sor['cikltip']=='m' && $sor['ciklho2']){
                $sor['ciklho'] = '';
            }

            if($sor['cikltip']=='w'){
                if(strpos($sor['ciklnap'], '1')!==false){
                    $checkboxok .= '$(\'#h\').prop(\'checked\', true);';
                }
                if(strpos($sor['ciklnap'], '2')!==false){
                    $checkboxok .= '$(\'#k\').prop(\'checked\', true);';
                }
                if(strpos($sor['ciklnap'], '3')!==false){
                    $checkboxok .= '$(\'#s\').prop(\'checked\', true);';
                }
                if(strpos($sor['ciklnap'], '4')!==false){
                    $checkboxok .= '$(\'#cs\').prop(\'checked\', true);';
                }
                if(strpos($sor['ciklnap'], '5')!==false){
                    $checkboxok .= '$(\'#p\').prop(\'checked\', true);';
                }
                if(strpos($sor['ciklnap'], '6')!==false){
                    $checkboxok .= '$(\'#sz\').prop(\'checked\', true);';
                }
                if(strpos($sor['ciklnap'], '7')!==false){
                    $checkboxok .= '$(\'#v\').prop(\'checked\', true);';
                }
            }
            $szamla_html .= nl2('
                    <script>
                        $(document).ready(function () {
                            $(\'#cikltip\').val(\'0\').change();
                            $(\'#cikltip\').val(\''.$sor['cikltip'].'\').change();

                            $(\'#esnap_d\').val(\''.str_replace('-', '.', $sor['esnap_d']).'\');
                            $(\'#ciklho\').val(\''.$sor['ciklho'].'\');
                            $(\'#honapjan\').val(\''.$sor['honapjan'].'\');
                            $(\'#ciklho2\').val(\''.$sor['ciklho2'].'\');
                            $(\'#ciklhet\').val(\''.$sor['ciklhet'].'\');
                            $(\'#ciklszorz\').val(\''.$sor['ciklszorz'].'\');
                            $(\'#ho_rendsz_het\').val(\''.$sor['ho_rendsz_het'].'\');
                            $(\'#ciklnap\').val(\''.$sor['ciklnap'].'\');
                            $(\'#ism_het\').val(\''.$sor['ism_het'].'\');

                            '.$checkboxok.'

                        });
                    </script>
                    ');

		}
	    $js = js_kiir($akt_tomb, 'form_ell_admin()');

        // Mobilcsoport választható-e vagy sem?
        $szamla_html .= nl2('
                <script>
                    $(document).ready(function () {
                        szalloda_modell( $(\'#telephely_id\') );
                    });
                </script>
                ');

	    $form_sablon = fajlbol_olvas('../sablon/admin/form_uj.html', true);

        // Javascript-es ellenőrzéshez
        $tabla = str_replace('.php', '', basename(self()));

        $rend_mezok = $adatbazis_kapcsolat->s('`column_name`', '`information_schema`.columns', '`table_schema` LIKE  \''.get_db().'\' AND  `table_name` LIKE  \''.get_db_tabla().$tabla.'\'', '', '');
        $rend_mezok = adatbazis2tomb($rend_mezok, 'column_name');

        $sor = array();
        foreach($rend_mezok as $rend_mezo) {
            $sor[$rend_mezo] = '';
        }

        foreach($akt_tomb as $tomb) {
            if( $tomb['type'] == 'autocomplete' ) {
                $sor[$tomb['name'].'_autocomp'] = '';
            }
        }

        $json_sor = array();
        foreach($sor as $kulcs => $elem){
           $json_sor[$kulcs] = formAdatok_replace($elem);
        }
        
        $szamla_html .= nl2('
                            <script>
                                $(document).ready(function () {
                                    ut_kulso_kivitlezo_beallit();
                                });
                                formAdatok = JSON.parse(\''.json_encode($json_sor).'\');
                            </script>
                        ');

        $sor['elv_ido_tol'] = '08:00';
        $sor['elv_ido_ig']  = '17:00';

		// Ez elkészíti a $mezok változót
		require('switch.php');

	    $form_mit     = array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{CK_EDITOR}', '{GOMBOK}', '{MUV_STYLE}'   );
	    $form_mivel   = array($js,    $js_submit_elott,    self(),     $mezok,    $megnevezes,    $ckeditor,     $gombok,    $muv_style      );
	    $szamla_html .= str_replace($form_mit, $form_mivel, $form_sablon);

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

	// Ütemezet feladat módosítása
	}elseif(isset($_GET['modosit'])) {

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
        }

	    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, 'recid='.$_GET['id']);
        
        $sor['leiras'] = str_replace(array('	'), array(' '), $sor['leiras']);
        $sor['leiras'] = preg_replace("/[[:blank:]]+/", ' ', $sor['leiras']);

        // 0-ék helyett használjunk üres stringet
        foreach($sor as $kulcs => $ertek){
            if(!$sor[$kulcs]){
                $sor[$kulcs] = '';
            }
        }

        // Régi adatbázisól jövő adatok miatt szükséges:
        if($sor['cikltip']=='d'){
            $sor['ciklnap'] = $sor['ciklszorz'];
        }
        if($sor['cikltip']=='m' && $sor['ciklaltip']=='1'){
            $sor['ciklho'] = $sor['ciklszorz'];
        }
        if($sor['cikltip']=='m' && $sor['ciklaltip']=='2'){
            $sor['ciklho2'] = $sor['ciklszorz'];
        }

        foreach($akt_tomb as $tomb) {
            if( $tomb['type'] == 'autocomplete' ) {

                if($tomb['csoport']){
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
        foreach($sor as $kulcs => $elem){
            $json_sor[$kulcs] = formAdatok_replace($elem);
        }
        // A hét napjainak pipáját is le kell kezelni a formAdatok tömbbe.
        $napok = array('1' => 'h', '2' => 'k', '3' => 's', '4' => 'cs', '5' => 'p', '6' => 'sz', '7' => 'v');
        foreach($napok as $kulcs => $nap ){
            if(strpos((string)$sor['ciklnap'], (string)$kulcs)!==false){
                $json_sor[$nap] = 'true';
            } else {
                $json_sor[$nap] = 'false';
            }
        }
        // A többi mezőt is le kell kezelni
        $json_sor['ism_het'] = $sor['ciklszorz'];

        $szamla_html .= nl2('
                        <script>
                            $(document).ready(function () {
                                ut_kulso_kivitlezo_beallit();
                            });
                            formAdatok = JSON.parse(\''.json_encode($json_sor).'\');
                        </script>
                        ');

        // Duplikáláskor feltett kérdés
        $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$sor['telephely_id'], '', '1');
        $telephely = $telephely[0]['nev'];
        $szamla_html .= nl2('
            <script>
                duplikalGomb  = "<p class=\"c\"><input class=\"gomb zold\" type=\"button\" value=\"Igen\" onclick=\"szurkit(); location.href = \''.basename(self()).'?uj&amp;ujkent='.$_GET['id'].'\'; \"><input class=\"gomb piros\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
                duplikaKerdes = \'Azonosító: '.$sor['id'].'<br>Szálloda: '.$telephely.'<br><br>Biztos, hogy duplikálja?\';
            </script>
        ');
        $szamla_html .= '<script>$(document).ready(function () { szalloda_modell( $(\'#telephely_id\') ); }); </script>';

// szinvatrend.hu-n kiírjuk a részeredményeket
if(isset($_GET['ut_teszt'])) {
    $szamla_html = '';
    include('datumra_utemezes.php');
    if(!$sor['esem_d'] || $sor['esem_d']=='0000-00-00'){
        $sor['esem_d'] = date('Y-m-d');
    }

    $esem_d     = substr(str_replace('-', '.', $sor['esem_d']), 0, 10);
    $esnap_d    = str_replace('-', '.', $sor['esnap_d']);
    $aktkezd_d  = str_replace('-', '.', $sor['aktkezd_d']);
    $aktvege_d  = str_replace('-', '.', $sor['aktvege_d']);

    $szamla_html .=  "esemd: $esem_d<br>cikltip: $sor[cikltip]<br>ciklaltip: $sor[ciklaltip]<br>ciklszorz: $sor[ciklszorz]<br>ciklho: $sor[ciklho]<br>ciklhet: $sor[ciklhet]<br>ciklnap: $sor[ciklnap]<br>idoszakos: $sor[idoszakos]<br>kezdho: $sor[kezdho]<br>kezdnap: $sor[kezdnap]<br>vegeho: $sor[vegeho]<br>vegenap: $sor[vegenap]<br>aktkezd_d: $aktkezd_d<br>aktvege_d: $aktvege_d";
    $getnextvaliddate = getnextvaliddate(false, $esem_d, $sor['cikltip'], $sor['ciklaltip'], $sor['ciklszorz'], $sor['ciklho'], $sor['ciklhet'], $sor['ciklnap'], $sor['idoszakos'], $sor['kezdho'], $sor['kezdnap'], $sor['vegeho'], $sor['vegenap'], $aktkezd_d, $aktvege_d);
    $szamla_html .= '<br>Szabály: ' . getcimke( $sor['cikltip'], $sor['ciklaltip'], $sor['ciklszorz'], $sor['ciklho'], $sor['ciklhet'], $sor['ciklnap'], $getnextvaliddate).'<br><br>';
    $szamla_html .= '<hr>Ütemezés: ' . $utemezes;
    $szamla_html .= '<hr>Esedékes: ' . $sor['esnap_d'].' '.het_napja_txt($sor['esnap_d']);
    $szamla_html .= '<hr>Utolsó végrehajtás: ' . $esnap_d .' '.het_napja_txt($esnap_d);
    $szamla_html .= '<br>Következő: ' . $getnextvaliddate.' '.het_napja_txt($getnextvaliddate);
    $szamla_html .= '<br>';
    $szamla_html .= '<br>Következő 10 iteráció:<br>';
    echo $szamla_html;
    exit;
}

	    //FORM
	    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

		// Form actionja
		$action = self().get_get(array('errnum','kephiba','siker'));

		// Ez elkészíti a $mezok változót
        require('switch.php');

        $jobb_oldal = '';

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

	// Naptár
    } elseif( isset($_GET['naptar']) ) {

        // Hét beoszás fejléce
        $fejlec = '';
        if( isset($_GET['akt_nap']) ) {
            $indulo = date2int($_GET['akt_nap']);
        } else {
            $indulo = time();
        }
        for ( $i = 0; $i < 6; $i++ ) {
            $idobelyeg = $indulo+($i*60*60*24);
            $fejlec .= '<th>'.date('Y-m-d', $idobelyeg).'<br>'.het_napja( date('w', $idobelyeg) ).'</th>';
        }

        // Aktuális felhasználó szállodái
        $szallodak = get_akt_login_szallodai('sql-t adj vissza');
        
        // Hét beosztás sorai
        $sorok = '';
        for ( $i = 0; $i < 24; $i++ ) {

            $ora            = sprintf('%02d', $i);
            $ora_kovetkezo  = sprintf('%02d', $i+1);

            $napok = '';
            for ( $j = 0; $j < 6; $j++ ) {

                $datum = date('Y-m-d', strtotime('+'.$j.' days'));
                $db = count($adatbazis_kapcsolat->s('id', $megnevezes, "`esnap_d`='".$datum."' AND `elv_ido_tol`>='{$ora}:00' AND `elv_ido_tol`<'{$ora_kovetkezo}:00'{$szallodak} AND `aktiv`=1"));
                if( !$db ) {
                    $db = '';
                } else {
                    $db = '<a href="'.self().'?modosit&amp;tabla='.$megnevezes.'&amp;feltetel=me|esnap_d!HASH!op|equal!HASH!fe|'.$datum.'!HASH!es|!HASH!me|elv_ido_tol!HASH!op|gteq!HASH!fe|'.$ora.':00!HASH!es|!HASH!me|elv_ido_tol!HASH!op|lt!HASH!fe|'.$ora_kovetkezo.':00!HASH!es|!HASH!me|aktiv!HASH!op|equal!HASH!fe|1!HASH!es|!HASH!&amp;f_o_szures_submit=1" target="_blank">'.$db.'</a>';
                }
                $napok .= '<td>'.$db.'</td>';
            }
            $sorok .= nl2('
                <tr>
                    <td>'.$i.':00</td>
                    '.$napok.'
                </tr>
                ');
        }

        $szamla_html .= nl2('
            <table class="csikos" style="margin-top: 4rem">
            <tr>
                <th>Órák</th>
                '.$fejlec.'
            <tr>
            '.$sorok.'
            </table>
            ');

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Ütemezés
    }elseif(isset($_GET['utemezes'])) {

        include('datumra_utemezes.php');

        $sor = $adatbazis_kapcsolat->s2('*', $megnevezes, "`recid`='{$_GET['id']}'");

        $kell_lista = true;
        $utemezes = 'Folyamatos';
        if($sor['idoszakos']){
            if($sor['aktkezd_d']>'0000-00-00' && $sor['aktkezd_d']>'0000-00-00'){
                $utemezes = str_replace('-', '.', $sor['aktkezd_d']).' - '.str_replace('-', '.', $sor['aktvege_d']);
                if($sor['aktvege_d']<date('Y-m-d')){
                    $utemezes = $sor['aktvege_d'].' után nincsenek további ütemezések.';
                    $kell_lista = false;
                }
            } else {
                require_once 'selectek.php';
                $kezdho = '';
                foreach($select_honapok as $honap){
                    if($honap['value']==$sor['kezdho']){
                        $kezdho = $honap['name'];
                        break;
                    }
                }
                $vegeho = '';
                foreach($select_honapok as $honap){
                    if($honap['value']==$sor['vegeho']){
                        $vegeho = $honap['name'];
                        break;
                    }
                }
                $utemezes = $kezdho.' '.$sor['kezdnap'].'. - '.$vegeho.' '.$sor['vegenap'].'.';
            }
        }

        if(!$sor['esem_d'] || $sor['esem_d']=='0000-00-00'){
            $sor['esem_d'] = date('Y-m-d');
        }

        $esem_d     = str_replace('-', '.', $sor['esem_d']);
        $esnap_d    = str_replace('-', '.', $sor['esnap_d']);
        $aktkezd_d  = str_replace('-', '.', $sor['aktkezd_d']);
        $aktvege_d  = str_replace('-', '.', $sor['aktvege_d']);

        $esedekes = $esnap_d;
        if(!$esedekes || $esedekes=='0000.00.00' || $esedekes=='    .  .  '){
            $getnextvaliddate = getnextvaliddate(false, $esem_d, $sor['cikltip'], $sor['ciklaltip'], $sor['ciklszorz'], $sor['ciklho'], $sor['ciklhet'], $sor['ciklnap'], $sor['idoszakos'], $sor['kezdho'], $sor['kezdnap'], $sor['vegeho'], $sor['vegenap'], $aktkezd_d, $aktvege_d);
            $esedekes = $getnextvaliddate;
        }
        // Fontos, hogy az első ütemezés az esedékesség napja legyen, Ezért lett kivéve az alábbi kód, mert a getnextvaliddate-et futtatva már más dátum jönne ki első időpontra, pedig oda az esedékességet kell írni. 
//        $getnextvaliddate = getnextvaliddate(false, $esem_d, $sor['cikltip'], $sor['ciklaltip'], $sor['ciklszorz'], $sor['ciklho'], $sor['ciklhet'], $sor['ciklnap'], $sor['idoszakos'], $sor['kezdho'], $sor['kezdnap'], $sor['vegeho'], $sor['vegenap'], $aktkezd_d, $aktvege_d);
//        if( $esedekes == '    .  .  ' || $getnextvaliddate>$esedekes){
//            $esedekes = $getnextvaliddate;
//        } else {
            $getnextvaliddate = $esedekes;
//        }

        //$szamla_html .=  "esemd: $esem_d<br>cikltip: $sor[cikltip]<br>ciklaltip: $sor[ciklaltip]<br>ciklszorz: $sor[ciklszorz]<br>ciklho: $sor[ciklho]<br>ciklhet: $sor[ciklhet]<br>ciklnap: $sor[ciklnap]<br>idoszakos: $sor[idoszakos]<br>kezdho: $sor[kezdho]<br>kezdnap: $sor[kezdnap]<br>vegeho: $sor[vegeho]<br>vegenap: $sor[vegenap]<br>aktkezd_d: $aktkezd_d<br>aktvege_d: $aktvege_d";
        $szamla_html .= '<br>Szabály: ' . getcimke( $sor['cikltip'], $sor['ciklaltip'], $sor['ciklszorz'], $sor['ciklho'], $sor['ciklhet'], $sor['ciklnap'], $getnextvaliddate);
        $szamla_html .= '<br>Ütemezés: ' . $utemezes;
        $szamla_html .= '<br>Esedékes: ' . $esedekes.' '.het_napja_txt($esedekes);
        $szamla_html .= '<br>';
        $szamla_html .= '<br>Várható ütemezés:<br>';

        $szamla_html .= $getnextvaliddate . '<br>';
        for($i=0; $i<9; $i++){
            $getnextvaliddate = getnextvaliddate(false, $getnextvaliddate, $sor['cikltip'], $sor['ciklaltip'], $sor['ciklszorz'], $sor['ciklho'], $sor['ciklhet'], $sor['ciklnap'], $sor['idoszakos'], $sor['kezdho'], $sor['kezdnap'], $sor['vegeho'], $sor['vegenap'], $aktkezd_d, $aktvege_d);
            $szamla_html .= $getnextvaliddate . '<br>';
        }

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
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }

    // Megjegyzés
    }elseif(isset($_GET['megjegyzesek'])) {

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
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	}

    if( !$_GET['ajax'] ) {
        echo '</div>';
    }

if( !$_GET['ajax'] && !isset($_GET['ut_teszt']) ) {
    require_once('footer.php');
}
