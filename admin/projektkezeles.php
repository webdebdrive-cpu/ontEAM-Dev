<?php
require_once('../weblap.php');

if( !_jog(JOG_PRKEZEL_MEGTEKINT) ) {
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
$akt_tomb               = $projektkezeles_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'projekt kezelést';   // Hibaüzenetkor: Biztosan törlöd a $szoveg?

// Változók beállítása
$csak_szerkesztheto     = false;                // Ha tiltani szeretnéd, hogy újat tudjon feltölteni, vagy törölni adott bejegyzést
$elonezet               = '';                   // Ha üres nincs előnézet. Egyébként meg az előnézet URL-jét add meg ide, pl.: '../index.php?id='.$_GET['id']
$fajloszlop             = 'kep';                // Kép (vagy kivételes esetekben más fájl) oszlopneve.
$id_latszik             = false;                // A termék ID-ja látsszon-e a felsorolásnál, kibontásnál (van, hogy ügyfél is használja)
$kep_thumb_listaba      = false;                // Lista nézetben legyen-e a képoszlop alapján képthumbnail
$magas                  = get_indexkep_m();     // Kiskép magassága
$sorrendezes            = false;                // Ha sorrendet is be szeretnénk állítani. Adatbázisban sor oszlopot létre kell hozni, jquery-ui.js-t és admin_sorrendez.js-t aktiválni az adminhoz
$szel                   = get_indexkep_sz();    // Kiskép szélessége
$ujkent_szerkesztheto   = true;                 // Szerkesztésnél elérhető egy gomb, melyre kattintva a termékadatai átkerülnek az új feltöltésbe

$ckeditor_js            = '../'.get_utvonal().'wysiwyg/ckeditor.js';    // Ckeditor JS útvonala
$formazatlan_szam       = array('nev', 'csz', 'tel', 'fax', 'mobil');          // Azok az oszlopneveket sorold fel, melyek numerikus értéket is tartalmazhatnak, de nem szeretnéd, ha lista nézetben ezres csoportra bontaná

$szamla_html = '';

// Több kép menedzseléséhez
$tobb_kep = true; // Ha true, akkor js/jquery-ui.js, js/admin_sorrendez.js aktivál és minify!, admin/tobb_kep.php aktivál
if($tobb_kep){
    function get_tobbkep_darab()    { return 10;        } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '';        } // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;        } // MB-ban
    function get_tobbkep_oszlopnev(){                     // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

}

// Projekt team hozzáadása
if (isset($_POST['addProjektTeam'])) {
    
    if($_POST['prkezel_recid']){
        
        $adatbazis_kapcsolat -> d('projektkezeles_login', "`projektkezeles_recid` = '".$_POST['prkezel_recid']."'");

        // Hozzáadjuk a Loginokat
        $loginok = json_decode($_POST['loginok'], true);
        foreach($loginok as $key => $login) {
            $kapcsID = $adatbazis_kapcsolat -> i('projektkezeles_login',
                                                    array('projektkezeles_recid',  'login_recid',   'insert_dt',    'insert_usr' ,                          'update_usr'                            ),
                                                    array($_POST['prkezel_recid'], $login,          'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'], $_SESSION[get_db_tabla().'teljes_nev']  )
                                                );
        }
    }
    
    echo json_encode(array(
        'statusz' => 'siker'
    ));
    
    $weblap -> leallit();
    exit;
}



// Igénylések táblázat betöltése
if(isset($_POST['igenylesek_betolt_uj_kezeleshez'])){

    $html = '';

    $postfix         = '_fb';
    $fb_bod_statusz  = PRIGENY_STAT_FB_TERJESZT_VAR;
    $nyitott_statusz = ELOT_FB_STAT_NYITOTT;
    $nyitott_is_sql  = ', \''.PRIGENY_STAT_NYITOTT.'\''; // FB csatolásnál a nyitottakat is be lehet csatolni, de BoD-nálm már nem
    if($_POST['mod']=='bod'){
        $postfix         = '_bod';
        $fb_bod_statusz  = PRIGENY_STAT_BOD_TERJESZT_VAR;
        $nyitott_statusz = ELOT_BOD_STAT_NYITOTT;
        $nyitott_is_sql  = '';
    }

    // Rendezés beállítása
    $join  = ' JOIN `'.get_db_tabla().'telephely` ON `'.get_db_tabla().'projektigenyles`.`telephely_id`=`'.get_db_tabla().'telephely`.`id`';
    switch($_POST['order']){
        case 'bkolt': $order = '`'.get_db_tabla().'projektigenyles`.`bkolt`, `'.get_db_tabla().'telephely`.`nev`';          $bkolt_selected = ' selected';  break;
        case 'tipus': $order = '`'.get_db_tabla().'projektigenyles`.`tipus`, `'.get_db_tabla().'projektigenyles`.`bkolt`';  $tipus_selected = ' selected';  break;
        default     : $order = '`'.get_db_tabla().'telephely`.`nev`,         `'.get_db_tabla().'projektigenyles`.`bkolt`';                                  break;
    }

    // Nem szerepelnek másik nyitott vagy véglegesítve státuszú előterjesztésben
    $where_kieg = '';
    $fb_kizart_recidk = $adatbazis_kapcsolat->s('projektigenyles_recid', 'eloterj_fb_kapcs', "`eloterj_recid` IN (SELECT `recid` FROM `".get_db_tabla()."eloterj_fb` WHERE `stat_id` IN ('".ELOT_FB_STAT_NYITOTT."', '".ELOT_FB_STAT_VEGLEGESITVE."'))");
    $fb_kizart_recidk = adatbazis2tomb($fb_kizart_recidk, 'projektigenyles_recid');
    $bod_kizart_recidk = $adatbazis_kapcsolat->s('projektigenyles_recid', 'eloterj_bod_kapcs', "`eloterj_recid` IN (SELECT `recid` FROM `".get_db_tabla()."eloterj_bod` WHERE `stat_id` IN ('".ELOT_BOD_STAT_NYITOTT."', '".ELOT_BOD_STAT_VEGLEGESITVE."'))");
    $bod_kizart_recidk = adatbazis2tomb($bod_kizart_recidk, 'projektigenyles_recid');
    $kizart_recidk = array_merge($fb_kizart_recidk, $bod_kizart_recidk);
    
    // Az olyan igyénlések, amelyek recid-je már a projektkezelés relációjában szerepel, azokat szűrjük ki
    $kezelt_igenyek = $adatbazis_kapcsolat->s('projektigenyles_recid', 'projektkezeles', "`projektigenyles_recid`>0");
    foreach($kezelt_igenyek as $kezelt_igeny){
        array_push($kizart_recidk, $kezelt_igeny['projektigenyles_recid']);
    }
    
    if($kizart_recidk){
        $where_kieg .= ' AND `'.get_db_tabla().'projektigenyles`.`recid` NOT IN ('.implode(',', $kizart_recidk).')';
    }
    

    $szallodak = get_akt_login_szallodai();
    $priek = $adatbazis_kapcsolat->s('
                                        `'.get_db_tabla().'projektigenyles`.`recid`,
                                        `'.get_db_tabla().'projektigenyles`.`nev`,
                                        `'.get_db_tabla().'projektigenyles`.`tipus`,
                                        `'.get_db_tabla().'projektigenyles`.`stat_id`,
                                        `'.get_db_tabla().'projektigenyles`.`telephely_id`,
                                        `'.get_db_tabla().'projektigenyles`.`bkolt`,
                                        `'.get_db_tabla().'telephely`.`nev` as `t_nev`',
                                        'projektigenyles',
                                        "`stat_id`='".PRIGENY_STAT_JOVAHAGYVA."' AND `telephely_id` IN (".implode(',', $szallodak).")$where_kieg", $order, '', '', '', $join);
//echo $adatbazis_kapcsolat->gp() . '<br>';
    $i = 1;
    foreach($priek as $pri){

        // Szálloda
        $szalloda = $adatbazis_kapcsolat->s2('nev', 'telephely', "`id`='{$pri['telephely_id']}'");

        // Létrehozva dátum = első nyitott státusz
        $letrehozva = $adatbazis_kapcsolat->s2('insert_dt', 'projektigenyles_statusz', "`projektigenyles_recid`='{$pri['recid']}' AND `stat_id`='".PRIGENY_STAT_NYITOTT."'", 'insert_dt', '1');

        // Tipus
        require_once 'selectek.php';
        foreach($select_pri_tipus as $tip){
            if($tip['value']==$pri['tipus']){
                $tipus = $tip['name'];
            }
        }
        if(!$pri['tipus']){
            $tipus = '-';
        }

        // BoD elé terjesztettek
        $kiemelt = '';
        if($pri['stat_id']==$fb_bod_statusz){
            $kiemelt = ' kiemelt';
        }

        $html .= '
            <tr class="kijSor'.$kiemelt.'" data-i="'.$i.'" data-recid="'.$pri['recid'].'">
                <td>'.$szalloda['nev'].'</td>
                <td style="width: 30rem">'.$pri['nev'].'</td>
                <td>'.$tipus.'</td>
                <td class="r">'.number_format($pri['bkolt'], 0, '', ' ').'</td>
                <td>'.substr(str_replace('-', '.', $letrehozva['insert_dt']), 0, 10).'</td>
            </tr>
            ';
        $i++;
    }

    if($html){

        // Táblázat rendezhető: szálloda + becsült költség, becsült költség + szálloda, típus + becsült költség. Ezek legyenek egy lenyílóból kiválaszthatóak.
        $html = '
            Rendezés:
            <select onchange="igenylesek_betolt_uj_kezeleshez(this.value)">
                <option>szálloda + becsült költség</option>
                <option value="bkolt"'.$bkolt_selected.'>becsült költség + szálloda</option>
                <option value="tipus"'.$tipus_selected.'>típus + becsült költség</option>
            </select>
            <div style="max-height: 40rem; overflow-y: auto; overflow-x: none;">
                <table class="csikos v2 kijCont csak_egy_kijelolheto" id="prigenylesek" style="cursor: default;">
                <thead>
                <tr>
                    <th>
                        Szálloda
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prigenylesek tbody\'), this.value, 2)">
                    </th>
                    <th>
                        Név
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prigenylesek tbody\'), this.value, 3)">
                    </th>
                    <th>
                        Típus
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prigenylesek tbody\'), this.value, 4)">
                    </th>
                    <th>
                        Becsült költség ('.get_penznem().')
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prigenylesek tbody\'), this.value, 5)">
                    </th>
                    <th>
                        Létrehozás dátuma
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#prigenylesek tbody\'), this.value, 6)">
                    </th>
                </tr>
                </thead>
                <tbody>
                    '.$html.'
                </tbody>
                </table>
            </div>';
    }else{
        $html = '<h1>Nem található csatolható projektigénylés!</h1><script>$(\'#eloterj_igneyles_csatolForm div.kuldes\').html(\'<input type="button" class="gomb zold" value="Ok" onclick="popupZar();">\');  $(\'#eloterj_igneyles_csatolCim\').html(\'Hiba\');</script>';
    }

    echo nl2($html);

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

    // Megjegyzések darabszáma
    $megj_db = '';
    $megj_sorok = $adatbazis_kapcsolat->s('recid', 'megjegyzes', "`tabla` = '".$megnevezes."' AND `id` = '".$_GET['id']."'", '', '');
    if( $megj_sorok ) {
        $megj_db = ' ('.count($megj_sorok).')';
    }

    // Dokumentumok darabszáma
    $dok_db = '';
    $dok_sorok = $adatbazis_kapcsolat->s('recid utvonal', $megnevezes.'_dok', "`{$megnevezes}_id` = '".$_GET['id']."'", '', '');
    if( $dok_sorok ) {
        $dok_db = 0;

        foreach($dok_sorok as $dok_sor) {
            if( is_file('../'.get_utvonal().$megnevezes.'/'.$dok_sor['utvonal']) ) {
                $dok_db += 1;
            }
        }

        $dok_db = ' ('.$dok_db.')';
    }

    if( isset($_GET['modosit']) || isset($_GET['garancialis']) || isset($_GET['tortenet']) || isset($_GET['rb_anyag']) || isset($_GET['projektigeny']) || isset($_GET['cash_flow']) || isset($_GET['projekt_team']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
        
        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'tortenet'      => 'Projekt történet',
            'projekt_team'  => 'Projekt-team',
            'dokumentumok'  => 'Dokumentumok'.$dok_db,
            'cash_flow'     => 'Cash-flow',
            'projektigeny'  => 'Projektigénylések',
            'rb_anyag'      => 'RB-anyagok',
            'garancialis'   => 'Garanciális munkák',
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
    if( isset($_GET['uj']) && _jog(JOG_PRKEZEL_KEZEL) ) {

        $gombok = nl2('
            <div class="form_kuldes">
                <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="szurkit(); $(\'#szerk_form\').submit()">
                <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="formModositva=false; history.back();">
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

    // Módosítás gombok
    }else{
        
       $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");
        // Ha a státusz elutasítva vagy jóváhagyva, akkor semmilyen gomb nem érhető el

        // Ha van egyálátalán kezelésre joga
        if( _jog(JOG_PRKEZEL_KEZEL) ){

            // Csak saját bejelentéseit szerkesztheti
            $nem_szerkesztheti = false;
            if($sor['loginnev']!=$_SESSION[get_db_tabla().'user']){
                $nem_szerkesztheti = true;
            }


            if(!$nem_szerkesztheti && isset($_GET['modosit']) /*&& $sor['stat_id']==PRIGENY_STAT_LETRE_ALATT Átmenetileg végig szerkeszthető */){
                $gombok = nl2('
                    <div class="form_kuldes">
                        <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="szurkit(); $(\'#szerk_form\').submit()">
                        <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="szurkit(); formModositva=false; location.reload();">
                    </div>
                ').$gombok;
            }
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
	if(isset($_GET['uj']) && _jog(JOG_PRKEZEL_KEZEL) ){

        $navi_sablon  = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit     = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel   = array( $navi,             '',         $gyk2 );
        $szamla_html .= str_replace($navi_mit, $navi_mivel, $navi_sablon);
        
	    // HIBAüZENETEK
	    require_once('info.php');

	    // FORM
		if(isset($_GET['ujkent'])){
			foreach($akt_tomb as $ertek){
				$sor[$ertek['name']]=$_SESSION[$ertek['name']];
			}
		}
	    $js = js_kiir($akt_tomb, 'form_ell_admin()');

        // Ckeditort csak akkor húzzuk be, ha kell
        if(is_file($ckeditor_js)){
            $ckeditor = '<script src="'.$ckeditor_js.'"></script>';
        }

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
           $json_sor[$kulcs] = str_replace(array("\r\n", "\n", "'"), array("#UJSOR#", "#UJSOR#", "#APOSZTROF#"), $elem);
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

	// Termék módosítása
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
            $json_sor[$kulcs] = str_replace(array("\r\n", "\n", "'"), array("#UJSOR#", "#UJSOR#", "#APOSZTROF#"), $elem);
        }
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

        if($id_latszik){
            $navi = '<b>#'.$_GET['id'].'</b> '.$navi;
        }

        // NAVIGÁCIÓ
        $navi_sablon = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit    = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel  = array( $navi,             '',         $gyk2 );
        // echo str_replace($navi_mit, $navi_mivel, $navi_sablon);

	    // HIBAüZENETEK
        require_once('info.php');

	    // FORM
	    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

		// Form actionja
		$action = self().get_get(array('errnum','kephiba','siker'));

        // Ckeditort csak akkor húzzuk be, ha kell
        if(is_file($ckeditor_js)){
            $ckeditor = '<script src="'.$ckeditor_js.'"></script>';
        }

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

	// Cash flow
	}elseif(isset($_GET['cash_flow'])){

        $szamla_html .= 'Cash flow felülete';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Projekt team
	}elseif(isset($_GET['projekt_team'])){

        $sorok = $adatbazis_kapcsolat->s('recid projektkezeles_recid login_recid', 'projektkezeles_login', '`projektkezeles_recid`=\''.  $_GET['id'].'\'');
        $szamla_html .= '<div id="tablazatBeszur">';
        if( $sorok ) {

            // Fejlécek
            $szamla_html .= nl2('
                <div class="dt naplo szakter kijCont" data-tabla="projektkezeles_login" data-mellek="projekt_team">
                    <div class="dtr nFejlec">
                        <div class="dtc">Név</div>
                        <div class="dtc">Teljes név</div>
                    </div>
            ');

            $i = 1;
            $loginok = adatbazis2tomb($sorok, 'login_recid');
            $loginok = $adatbazis_kapcsolat->s('recid nev teljes_nev', 'login', "`recid` IN (".implode(',', $loginok).")", 'nev');

            foreach($loginok as $login) {
                
                $kapcs_id = $adatbazis_kapcsolat->s2('recid', 'projektkezeles_login', "`projektkezeles_recid`='{$_GET['id']}' AND `login_recid`='{$login['recid']}'");

                $szamla_html .= nl2('
                    <div id="naploSor-'.$kapcs_id['recid'].'" class="dtr nSor kijSor" data-id="'.$kapcs_id['recid'].'" data-i="'.$i.'">
                        <div class="dtc">'.$login['nev'].'</div>
                        <div class="dtc">'.$login['teljes_nev'].'</div>
                    </div>
                ');

                $i++;
            }

            $szamla_html .= '</div>';
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

	// Projektigény
	}elseif(isset($_GET['projektigeny'])){

        $kezeles  = $adatbazis_kapcsolat->s2('projektigenyles_recid', 'projektkezeles', "`recid`='{$_GET['id']}'");
        if($kezeles['projektigenyles_recid']){
            $igenyles = $adatbazis_kapcsolat->s2('*', 'projektigenyles', "`recid`='{$kezeles['projektigenyles_recid']}'");
            $szamla_html .= nl2('
                                <div class="dt naplo kijCont" data-tabla="munkalap_kapcs" data-mellek="munkalapok">
                                    <div class="dtr nFejlec">
                                    <div class="dtc">Azonosító</div>
                                    <div class="dtc">Szálloda</div>
                                    <div class="dtc">Név</div>
                                    <div class="dtc">Leírás</div>
                                    <div class="dtc">Létrehozva</div>
                                    <div class="dtc">Státusz</div>
                                </div>
                                <div id="naploSor-'.$igenyles['recid'].'" class="dtr nSor" data-id="'.$igenyles['recid'].'" data-i="1">
                                    <div class="dtc"><a href="projektigenyles.php?id='.$igenyles['recid'].'&amp;modosit" target="_blank">'.$igenyles['ikt_szam'].'</a></div>
                                    <div class="dtc">'.getErtek('nev', 'telephely', 'id', $igenyles['telephely_id']).'</div>
                                    <div class="dtc">'.$igenyles['nev'].'</div>
                                    <div class="dtc">'.nl2br2($igenyles['text']).'</div>
                                    <div class="dtc">'.str_replace('-', '.', $igenyles['insert_dt']).'</div>
                                    <div class="dtc">'.getStatText('projektigenyles', $igenyles['stat_id']).'</div>
                                </div>
                            ');
        }else{
            $szamla_html .= 'Ehhez a projektkezelésehez nem tartozik igénylés.';
        }
        

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

	// RB-anyagok
	}elseif(isset($_GET['rb_anyag'])){

        $szamla_html .= 'RB-anyagok felülete';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Garanciális munkák
	}elseif(isset($_GET['garancialis'])){

        $szamla_html .= 'Garanciális munkák felülete';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }


	// Dokumentumok
	}elseif(isset($_GET['dokumentumok'])){

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

        $szamla_html .= nl2('
            <div class="dt naplo kijCont" data-tabla="megjegyzes" data-mellek="megjegyzesek">
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
