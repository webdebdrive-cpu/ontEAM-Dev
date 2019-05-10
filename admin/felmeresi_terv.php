<?php
require_once('../weblap.php');

if( !_jog(JOG_FT_MEGTEKINT) ) {
    if(!$_SESSION[get_db_tabla().'user']){
        $weblap -> leallit();
        header('Location: index.php');
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');
    $weblap -> leallit();
    exit('Nincs megfelelő jogosultság az oldal eléréséhez!');
}

$megnevezes             = pathinfo(self());
$megnevezes             = $megnevezes['filename'];
$akt_tomb               = $felmeresi_terv_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'felmérési tervet';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

$szamla_html = '';

// Dokumentumok fülhöz kell majd
$tobb_kep = true;
if($tobb_kep){
    function get_tobbkep_darab()    { return 10;        } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '';   } // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;        } // MB-ban
    function get_tobbkep_oszlopnev(){                     // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

}

// Bal oldali navpanel frissítése, könyvtárszerkezet újra rajzolása
if (isset($_POST['bal_panel_frissit'])) {

    $bejegyzesek = array();
    $sorok = mysqli_query($db_kapcs, $_SESSION['akt_sql']);
    while($sor = mysqli_fetch_assoc($sorok)){
        array_push($bejegyzesek, $sor);
    }

    echo json_encode(array(
        'html' => '<div class="szamlaLapozas fent clearFix"></div>'.felmeresi_tervLista( $bejegyzesek, $megnevezes ).'<div class="szamlaLapozas clearFix"></div>'
    ));

    $weblap->leallit();
    exit();
}

// Foglalt azonosító ellenőrzése
if ($_POST['azon_ell']) {

    $_POST['azon'] = trim($_POST['azon']);
    if($_POST['azon']){
        if($adatbazis_kapcsolat->s('recid', $megnevezes, "`azon`='{$_POST['azon']}' AND `recid`!='{$_POST['recid']}'")){
            echo json_encode(array(
                'hiba' => 'Ez az azonosító már foglalt, válasszon másikat!',
                'gomb' => '<input class="link gomb" value="Ok" onclick="alert2(); $(\'#azon\').focus();" type="button">'
            ));
        }else{
            echo json_encode(array(
                'statusz' => 'siker'
            ));
        }
    }else{
        echo json_encode(array(
            'hiba' => 'Kérem töltse ki az azonosítót!'
        ));
    }

    $weblap->leallit();
    exit();
}

// CSV generálása
if (isset($_GET['csv'])) {

    $mibol   = get_web_charset();
    $mibe    = 'windows-1250';

    if( !is_dir('export') ) {
        mkdir('export');
    }

    if( !is_dir('export/csv') ) {
        mkdir('export/csv');
    }

    // Régi fájlok törlése
    $utvonal = 'export/csv/';
    $fajlok = konyvtar_bejar($utvonal);
    foreach($fajlok as $fajl){
        if(filemtime($fajl)<(time()-60*60)){
            unlink($fajl);
        }
    }

    $fajlnev = $utvonal.'csv'.time().'.csv';
    $open = fopen($fajlnev,'a');
    fwrite($open, "sep=;\n");
    if($open){

        $lekerdezes_fromtol = substr($_SESSION['akt_sql'], strpos($_SESSION['akt_sql'], 'FROM'));

        if( $_SESSION['nyomtatas_ids'] ) {
            if( strstr($_SESSION['akt_sql'], 'WHERE') ) {
                $lekerdezes_fromtol = str_replace('WHERE', "WHERE `".get_db_tabla().$megnevezes."`.`recid` IN (".$_SESSION['nyomtatas_ids'].") AND ", $lekerdezes_fromtol);
            } else {
                $lekerdezes_fromtol = str_replace('ORDER', "WHERE `".get_db_tabla().$megnevezes."`.`recid` IN (".$_SESSION['nyomtatas_ids'].") ORDER", $lekerdezes_fromtol);
            }
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

        $sql = 'SELECT
                `'.get_db_tabla().$megnevezes.'`.`nev` as \'Név\'
                '.$lekerdezes_fromtol.$_SESSION['akt_sql_order'];

        $query      = mysqli_query($db_kapcs, $sql);

        $csvhead    = array();
        while($fejlec_nevek = mysqli_fetch_field($query)){
            $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
        }
        fputcsv($open, $csvhead, ';');

        while( $sorok = mysqli_fetch_assoc($query) ){
            $csvcont = array();
            foreach( $sorok as $kulcs => $sor ) {
                if($kulcs=='Szálloda'){
                    $sor = getErtek('nev', 'telephely', 'id', $sor);
                }
                if($kulcs=='Bejelentett hiba'){
                    $sor = getErtek('info', 'erteklista', 'recid', $sor);
                }
                if($kulcs=='Helyiség'){
                    $sor = getErtek('nev', 'obj', 'recid', $sor);
                }
                if($kulcs=='Gépnév'){
                    $sor = getErtek('nev', 'eszkozkezeles', 'recid', $sor);
                }
                if($kulcs=='Típus'){
                    $sor = erteklista(ERTEKLISTA_MUNKALAP_TIP, $sor);
                }
                if($kulcs=='Státusz'){
                    $sor = getStatText('munkalap', $sor);
                }

                // Lehet nem volna elég 1-szer sőt kétszer sem ráereszteni a htmlspecialchars_decode-ot
                $sor = htmlspecialchars_decode( $sor );
                while(strpos($sor, '&amp;')!==false || strpos($sor, '&quot;')!==false ){
                    $sor = htmlspecialchars_decode( $sor );
                }
                $csvcont[] = iconv($mibol, $mibe.'//TRANSLIT', strip_tags( $sor ) );
            }
            fputcsv($open, $csvcont, ';');
        }
    }
    fclose($open);

    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=$megnevezes.csv");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo fajlbol_olvas($fajlnev);

    $weblap->leallit();
    exit();
}

// Felmérési terv fájl létezik-e?
if (isset($_POST['felmeresi_terv_csatolva'])) {

    // Új feltöltésnél még nincs recid, ekkor a Cache mappát kell megvizsgálni, hogy van-e session kezdetű fájl
    if (!$_POST['recid']) {
        // Nézzük meg, hogy van-e SESSION id kezdetű fájl a könyvtárban
        $utvonal = '../'.get_utvonal().'cache/jsfajlok/'.session_id().'*';
        $session_kezdetu_fajlok = glob($utvonal); //Ha több ilyen is van az azért lehet, mert több fájlfeltöltés input is volt a tömbben

        if( count($session_kezdetu_fajlok)>0 ){
            echo json_encode(array(
                'hiba'  => 'Ehhez a tervhez már van felmérési terv csatolva. Ha folytatja, le fogja cserélni.<br>Folytatja?',
                'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#jsff_felmeresi_terv_felm_terv input\').click(); alert2();"><input type="button" class="gomb piros" value="Nem" onclick="alert2();">',
                'title' => 'Kérdés',
            ));
        }else{
            echo json_encode(array(
                'tallozas_mehet'  => true,
            ));
        }

    }else{

        $ft = $adatbazis_kapcsolat->s2('felm_terv', 'felmeresi_terv', "`recid`='{$_POST['recid']}'");
        if(is_file('../'.get_utvonal().$megnevezes.'/'.$ft['felm_terv'])){
            echo json_encode(array(
                'hiba'  => 'Ehhez a tervhez már van felmérési terv csatolva. Ha folytatja, le fogja cserélni.<br>Folytatja?',
                'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#jsff_felmeresi_terv_felm_terv input\').click(); alert2();"><input type="button" class="gomb piros" value="Nem" onclick="alert2();">',
                'title' => 'Kérdés',
            ));
        }else{
            echo json_encode(array(
                'tallozas_mehet'  => true,
            ));
        }

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

    if( isset($_GET['modosit']) || isset($_GET['megjegyzesek']) ) {
        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Tervlap',
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

        if(_jog(JOG_FT_KEZEL)){
            $gombok = nl2('
                <input type="button" class="gomb" value="Felmérési terv csatolása" onclick="felmeresi_terv_csatolva()">
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
        $gombok  = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';
        $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';

        if(!$nem_szerkesztheti && isset($_GET['modosit']) && _jog(JOG_FT_KEZEL)){

            $gombok = nl2('
                <input type="button" class="gomb" value="Felmérési terv csatolása" onclick="felmeresi_terv_csatolva(\''.$_GET['id'].'\')">
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

    // Felmérési terveknél, az űrlap elpostolása előtt még ellenőrizzük, hogy az azonosító nem foglalt-e.
    // Egy ajax hívástbevárva tudjuk meg, hogy fogalalt-e vagy sem, és ha nem az, akkor újra elpostoljuk a formot de már egy az eredeti JS fügvny ellenőrzéssel.
    $js_submit_elott = '
                        mehet_e = false;
                        uj_form_ajax = $.ajax({
                            url : \''.basename(self()).'\',
                            type: \'POST\',
                            data: {
                                azon    : $(\'#azon\').val(),
                                recid   : \''.$_GET['id'].'\',
                                azon_ell: new Date().getTime()
                            },
                            success: function (response) {
                                if(response.substring(0, 6)==\'SESKIL\'){ location.href = \'index.php\'; }
                                response = JSON.parse(response);
                                if(response.hiba){
                                    alert2(response.hiba, response.gomb, \'Hiba\');
                                    mehet_e = false;
                                }else{
                                    mehet_e = true;
                                }
                            }
                        });

                        $.when(
                            uj_form_ajax
                        ).then(function() {
                            if(mehet_e){
                                szurkit();
                                $(\'#szerk_form\').attr(\'onsubmit\', \'return form_ell_admin()\');
                                $(\'#szerk_form\').submit();
                            }else{
                                return false;
                            }
                        } );

                        return false;
                        ';

	// Új FT felvitele
	if(isset($_GET['uj']) && _jog(JOG_FT_KEZEL)){

	    // FORM
		if(isset($_GET['ujkent'])){
            $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, 'recid='.$_GET['ujkent']);
            if( $sor ) {
                foreach($akt_tomb as $ertek){
                    if( $ertek['name'] != 'azon' ) {
                        $_SESSION[$ertek['name']] = stripslashes( $sor[$ertek['name']] );
                    }else{
                        $_SESSION[$ertek['name']] = '';
                    }
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

        foreach($akt_tomb as $tomb) {
            if( $tomb['type'] == 'autocomplete' ) {
                $sor[$tomb['name'].'_autocomp'] = '';
            }
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
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

	// Termék módosítása
	}elseif(isset($_GET['modosit'])) {

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
        }

        foreach($akt_tomb as $tomb) {
            if( $tomb['type'] == 'autocomplete' ) {

                if($tomb['csoport']){
                    $kulso_ertek = $adatbazis_kapcsolat->s2('info', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}' AND `csoport`='{$tomb['csoport']}'", '', '1');
                    $kulso_ertek['nev'] = $kulso_ertek['info'];
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
            $json_sor[$kulcs] = formAdatok_replace($elem);
        }
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

        // Duplákáláskor feltett kérdés
        $szamla_html .= nl2('
            <script>
                duplikalGomb  = "<p class=\"c\"><input class=\"gomb zold\" type=\"button\" value=\"Igen\" onclick=\"szurkit(); location.href = \''.basename(self()).'?uj&amp;ujkent='.$_GET['id'].'\'; \"><input class=\"gomb piros\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
                duplikaKerdes = \'Név: '.$sor['nev'].'<br><br>Biztos, hogy duplikálja?\';
            </script>
        ');

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
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Dokumentumok
	}elseif( isset($_GET['dokumentumok']) ) {
        /* Egyelőre nem kell ilyen
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
        */
    }

    if( !$_GET['ajax'] ) {
        echo '</div>';
    }

if( !$_GET['ajax'] ) {
    require_once('footer.php');
}
