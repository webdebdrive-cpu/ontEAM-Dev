<?php
require_once('../weblap.php');

if( !_jog(JOG_SZEREPKOR_MEGTEKINT) ) {
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
$akt_tomb               = $szerepkor_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'szerepkört';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

$szamla_html = '';

// Dokumentumok fülhöz kell majd
$tobb_kep = false;
if($tobb_kep){
    function get_tobbkep_darab()    { return 10;        } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '.jpg,.png';} // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;        } // MB-ban
    function get_tobbkep_oszlopnev(){                     // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

}

// Jogosultásgok beállítsa
if( isset($_POST['jogokhozzaad']) ) {

    $id = $_POST['id'];

    // Kitöröljük a nem kívánatos POST elemeket
    $erintetteket_modositjuk = false;
    if($_POST['erintettek_mod']){
        $erintetteket_modositjuk = true;
    }
    unset($_POST['id']);
    unset($_POST['jogokhozzaad']);
    unset($_POST['erintettek_mod']);
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    // Tömb sorrendezése
    $jogok = array();
    foreach($_POST as $key => $value) {
        $kulcs = str_replace('jog-', '', $key);
        if($value=='true'){
            $jogok[$kulcs] = 1;
        }else{
            $jogok[$kulcs] = 0;
        }
    }
    ksort($jogok);
    
    // Log tartalmának beállítása
    $flags = $adatbazis_kapcsolat->s2('flags', 'szerepkor', "`recid`='{$id}'");
    if( $flags === false ) { $db_error = true; }
    $log = 'Előző jogok: '.$flags['flags'].'. Új jogok: '.implode('', $jogok);

    // Szerepkör jogok eltárolása
    $adatbazis_kapcsolat -> u('szerepkor', array('flags', 'update_usr'), array(implode('', $jogok), $_SESSION[get_db_tabla().'teljes_nev']), 'recid='.$id);

    // Ha kell, az összes érintett felhasználónál frissítjük a jogokat
    if($erintetteket_modositjuk){
        $adatbazis_kapcsolat->u('login', array('flags', 'update_usr'), array(implode('', $jogok), $_SESSION[get_db_tabla().'teljes_nev']), "`szemelyzet_id`='{$id}'");
        $log .= ' Érintett felhasználókat is módosította.';
    }
    
    // Logba is eltároljuk
    $sql = $adatbazis_kapcsolat -> i('log',
                                array( 'felh_id',                               'nev',                                                                                        'valtozas',    'ip'                    ),
                                array( $_SESSION[get_db_tabla().'login_recid'], $_SESSION[get_db_tabla().'teljes_nev'].' módosította '.$id.' ID-jú szerepkör jogosultságait', $log,          $_SERVER['REMOTE_ADDR'] )
                            );
    if( $sql === false ) { $db_error = true; }

    // A formAdatok JS változoót frissíteni kell
    require_once('jogosultsag.php');
    $i = 0;
    foreach($jogosultsagok as $jog) {
        if( $jogok[$i] ) {
            $jogEll['jog-'.$i] = 1;
        } else {
            $jogEll['jog-'.$i] = 0;
        }
        $i++;
    }


    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode($jogEll);
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba',
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
                $lekerdezes_fromtol = str_replace('WHERE', "WHERE `".get_db_tabla()."szerepkor`.`recid` IN (".$_SESSION['nyomtatas_ids'].") AND ", $lekerdezes_fromtol);
            } else {
                $lekerdezes_fromtol = str_replace('ORDER', "WHERE `".get_db_tabla()."szerepkor`.`recid` IN (".$_SESSION['nyomtatas_ids'].") ORDER", $lekerdezes_fromtol);
            }
        }

        $sql = 'SELECT
                `'.get_db_tabla().'szerepkor`.`nev` as \'Név\'
                '.$lekerdezes_fromtol;

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

// Tartalom
if( !$_GET['ajax'] ) {
    require_once('header.php');
}
require_once('selectek.php');

    if( !$_GET['ajax'] ) {
        echo '<div id="szamlaBetolt">';
    }

    if( isset($_GET['modosit']) || isset($_GET['jogosultsag']) ) {
        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'jogosultsag'   => 'Jogosultságok',
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

    $js_submit_elott = 'return form_ell_admin();';

    // Gombok kirajzolása minden oldalra
    $gombok = '';
    if( isset($_GET['uj']) ) {

        if( _jog(JOG_SZEREPKOR_KEZEL) ){
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="szurkit(\'mehet_megsem_rejt\'); $(\'#szerk_form\').submit();">
                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="szurkit(\'mehet_megsem_rejt\'); formModositva=false; szamlaBetolt($(\'#szamlaList li:first-of-type\').attr(\'data-recid\'), \''.$megnevezes.'.php\');">
                </div>
            ');
        }

    // Módosítás gombok
    } else {

        $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, 'recid='.$_GET['id']);

        // Szerepkör egyáltalán nem módosítható, sem törölhető, tehát ilyen gomb nem kell. 
        // A jogosultságok fül alatti pipák igen, illetve új szerepkör tölthető fel, de módosítani (átnevezni), törölni nem.
        $gombok = '';

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

	// Új szerepkör felvitele
	if(isset($_GET['uj']) && _jog(JOG_SZEREPKOR_KEZEL)){

	    // FORM
		if(isset($_GET['ujkent'])){
			foreach($akt_tomb as $ertek){
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

        $js_submit_elott = 'return form_ell_admin();';

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

	// Szerepkörök módosítása
	}elseif(isset($_GET['modosit'])) {

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
        }

	    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, 'recid='.$_GET['id']);

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

	    // FORM
	    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

		// orm actionja
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
                'tartalom'  => $szamla_html,
                'link'      => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

        if($tobb_kep){
			require_once('tobb_kep.php');
		}

    // Jogosultság
    }elseif(isset($_GET['jogosultsag'])) {

        require_once('jogosultsag.php');

        $login = $adatbazis_kapcsolat->s2('flags', 'szerepkor', 'recid='.$_GET['id'], '', '1');

        if( $login ) {

            // Jogosultságok feldarabolás
            $flags = str_split($login['flags']);

            // Ellenőrzéshez
            $jogEll = array();
            $i = 0;
            foreach($jogosultsagok as $jog) {
                if( $flags[$i] ) {
                    $jogEll['jog-'.$i] = 1;
                } else {
                    $jogEll['jog-'.$i] = 0;
                }

                $i++;
            }

            $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($jogEll).'\'); </script>';

            // Hozzáadjuk az értékeket a jogosultságokhoz
            $i = 0;
            foreach($jogosultsagok as $jog) {
                $jogosultsagok[$i]['value'] = $flags[$i];
                $jogosultsagok[$i]['id'] = $i;

                $i++;
            }

            // Kigyüjtjük a kategóriákat
            $jogKatok = jogosultsagKatok( $jogosultsagok );

            // Rendezzük a kategóriákat
            function sortByOrder($a, $b) {
                return $a['sor'] - $b['sor'];
            }
            usort($jogKatok, 'sortByOrder');

            $szamla_html .= nl2('
                                <script>
                                    function form_elkuld(){
                                        param = \'\';
                                        $(\'input[type="checkbox"]\').each(function () {
                                            param += $(this).attr(\'name\')+\'=\'+$(this).is(\':checked\')+\'&amp;\';
                                        });
                                        param += \'erintettek_mod=\'+$(\'#erintettek_mod\').val()+\'&amp;\';

                                        $.ajax({
                                            url    : \''.self().'\',
                                            type   : \'POST\',
                                            data   : esamp2es(param+\'&amp;id='.$_GET['id'].'&amp;jogokhozzaad=\' + new Date().getTime()),
                                            success: function (response) {

                                                if(response.substring(0, 6)==\'SESKIL\'){ location.href = \'index.php\'; }
                                                $(\'input[type="checkbox"]\').removeClass(\'mod\');
                                                formModositva = false;
                                                $(\'.mentes_gomb\').attr(\'disabled\', true);
                                                $(\'#erintettek_mod\').val(\'\');

                                                szurkit_zar();
                                                formAdatok = JSON.parse(response);
                                            }
                                        });
                                        return false;
                                    }
                                </script>
                                <form id="szerk_form" action="" method="post" onsubmit="return form_elkuld();">
                                    <div class="dt">
                ');

            $jogdb = count($jogKatok);
            $jogdb_fel = ceil($jogdb / 2);

            // Kiíratjuk az egészet
            $i = 1;
            foreach($jogKatok as $jogKat) {
                if( $i == 1 ) {
                    $szamla_html .= '
                        <div class="dt jog_dt">
                            <div class="dtr">
                                <div class="dtc jog_dtc">
                    ';
                } else if( $i == $jogdb_fel ) {
                    $szamla_html .= '
                                </div>
                                <div class="dtc jog_dtc">
                    ';
                }

                $szamla_html .= '
                    <div class="dtr">
                        <div class="dtc form_bal jogKat"><span>'.$jogKat['nev'].':</span></div>
                    </div>
                ';

                $jogSorok = getJogSorRendez( $jogKat['id'],  $jogosultsagok);
                usort($jogSorok, 'sortByOrder');

                foreach($jogSorok as $jogSor) {

                    $checked = '';
                    if( $jogSor['value'] ) {
                        $checked = ' checked="checked"';
                    }

                    $jogSor['nev'] .= ':';
                    if($jogSor['i']){
                        $jogSor['nev'] .= '<br><i class="f12">('.$jogSor['i'].')</i><div style="margin-bottom: 0.8rem"></div>';
                    }

                    $onclick = '';
                    $kikapcs = '';
                    if($jogSor['kikapcs']!=''/*Lényeges, hogy az üres stringgel legyen hasonlítva, mert 0 lehet értéke*/){
                        foreach(explode(',', $jogSor['kikapcs']) as $pipa_ki){
                            $kikapcs .= '$(\'#jog-'.$pipa_ki.'\').prop(\'checked\', false).removeClass(\'mod\');';
                        }
                    }
                    $kikapcskor_kikapcs = '';
                    if($jogSor['kikapcskor_kikapcs']!=''/*Lényeges, hogy az üres stringgel legyen hasonlítva, mert 0 lehet értéke*/){
                        foreach(explode(',', $jogSor['kikapcskor_kikapcs']) as $pipa_ki){
                            $kikapcskor_kikapcs .= '$(\'#jog-'.$pipa_ki.'\').prop(\'checked\', false).addClass(\'mod\');';
                        }
                    }
                    $bekapcs = '';
                    if($jogSor['bekapcs']!=''){
                        foreach(explode(',', $jogSor['bekapcs']) as $pipa_be){
                            $bekapcs .= '$(\'#jog-'.$pipa_be.'\').prop(\'checked\', true).addClass(\'mod\');';
                        }
                    }

                    // Ha épp most kap bekapcsolást, csak akkor futtatjuk le
                    if($bekapcs){
                        $bekapcs = 'if( $(this).prop(\'checked\') ){'.$bekapcs.'}';
                    }
                    // Ha épp most kapcsoltuk ki
                    if($kikapcskor_kikapcs){
                        $kikapcskor_kikapcs = 'if( $(this).prop(\'checked\') == false ){'.$kikapcskor_kikapcs.'}';
                    }

                    if($kikapcs || $bekapcs || $kikapcskor_kikapcs){
                        $onclick = ' onclick="'.$kikapcs.$bekapcs.$kikapcskor_kikapcs.'"';
                    }

                    // A már törölteket rejtsük el
                    $dn = '';
                    if($jogSor['torolve']){
                        $dn = ' dn';
                    }

                    // Van ahol a pipát kell elrejteni
                    $dn_pipa  = '';
                    $dn_pipac = '';
                    if($jogSor['nincs_pipa']){
                        $dn_pipa  = ' dn';
                        $dn_pipac = ' class="dn"';
                    }

                    $szamla_html .= nl2('
                        <div class="dtr'.$dn.'">
                            <div class="dtc form_bal"><span>'.$jogSor['nev'].'</span></div>
                            <div class="dtc form_jobb">
                                <div class="cb">
                                    <input type="hidden" name="jog-'.$jogSor['id'].'" value="0">
                                    <input type="checkbox" class="check'.$dn_pipa.'" name="jog-'.$jogSor['id'].'" id="jog-'.$jogSor['id'].'" value="1"'.$checked.$onclick.'>
                                    <label'.$dn_pipac.' for="jog-'.$jogSor['id'].'"></label>
                                    '.$info.'
                                </div>
                            </div>
                        </div>
                    ');
                }

                if( $jogdb == $i ) {
                    $szamla_html .= '
                            </div>
                        </div>
                    ';
                }

                $i++;
            }

            $szamla_html .= nl2('
                        <input type="hidden" name="erintettek_mod" id="erintettek_mod">
                        ');
        }
        
        if( _jog(JOG_SZEREPKOR_KEZEL) ){
            $szamla_html .= nl2('
                    <div class="muveletek"'.$muv_style.'>
                        <div class="form_kuldes">
                            <input type="hidden" name="id" value="'.$_GET['id'].'">
                            <script> meget_gomb = \'<input type="button" class="gomb zold" value="Igen" onclick="$(\\\'#erintettek_mod\\\').val(1); $(\\\'#szerk_form\\\').submit(); $(\\\'#mehet_gomb\\\').attr(\\\'disabled\\\', true); $(\\\'#megsem_gomb\\\').attr(\\\'disabled\\\', true); alert2();"><input type="button" class="gomb piros" value="Nem" onclick="$(\\\'#szerk_form\\\').submit(); $(\\\'#mehet_gomb\\\').attr(\\\'disabled\\\', true); $(\\\'#megsem_gomb\\\').attr(\\\'disabled\\\', true); alert2();">\'; </script>
                            <input type="button" class="gomb zold" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="alert2(\'A szerepkör megváltozott.<br>Jogait beállítsuk az érintett felhasználóknál is?\', meget_gomb)">
                            <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="szurkit(\'mehet_megsem_rejt\'); formModositva=false; location.reload();">
                        </div>
                    </div>');
        }else{
            $szamla_html .= nl2('
                    <script>
                        $(document).ready(function () {
                            $(\'div.cb input\').attr(\'disabled\', true);
                        });
                    </script>
                ');
        }
        
        $szamla_html .= nl2('
                    </div>
                </form>
            ');

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html
            ));
        } else {
            echo $szamla_html;
        }

	}

    if( !$_GET['ajax'] ) {
        echo '</div>';
    }

if( !$_GET['ajax'] ) {
    require_once('footer.php');
}
