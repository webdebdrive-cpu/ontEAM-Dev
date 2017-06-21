<?php
require_once('../class/html/class.Input.php');
require_once('../class/html/class.Select.php');

$osszevon_flag  = 0;
$mezok          = '';
foreach($akt_tomb as $ertek){
	switch($ertek['type']){


        case 'autocomplete':
            $input = new Input('text');

            //Ha ezresít, automatikusan meghívunk rá két JS-t
            if( $ertek['ezresit'] ){
                $ertek['onkeyup'] .= '; onlyNumbers(this); this.value=ezresek(this.value,\'.\')';
                $sor[$ertek['name']] = number_format($sor[$ertek['name']], 0, '', '.');
            }

            if($ertek['alert']){
                $ertek['class'] .= ' csillag';
            }

            //Onblurra a hidden mező is nullázódjon, ha üres volna a mező
            $ertek['onblur'] .= 'if(!$(this).val()){ $(\'#'.$ertek['name'].'\').val(\'\'); }';

            $input -> cssClass      = $ertek['class'];
            $input -> disabled      = $ertek['disabled'];
            $input -> id            = $ertek['name'].'_autocomp';
            $input -> maxlength     = $ertek['maxlength'];
            $input -> name          = $ertek['name'].'_autocomp';
            $input -> naptar        = $ertek['naptar'];
            $input -> onblur        = $ertek['onblur'];
            $input -> onclick       = $ertek['onclick'];
            $input -> onfocus       = $ertek['onfocus'];
            $input -> oninput       = $ertek['oninput'];
            $input -> onkeyup       = $ertek['onkeyup'];
            $input -> onchange      = $ertek['onchange'];
            $input -> style         = $ertek['style'];
            $input -> placeholder   = $ertek['placeholder'];
            $input -> autocomplete  = 'off';
            $input -> post          = $ertek['post'];
            $input -> pre           = $ertek['pre'];
            if($ertek['i']){
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
                if( strpos($input -> cssClass, 'oriasi')!==false || strpos($input -> cssClass, 'nagy')!==false ){
                    $input -> style .= '; width: 95%;';
                }
            }

            if(!$sor[ $ertek['name'] ]) {
                $input -> value   = $ertek['value'];
            }else{
                if($ertek['szam']) {
                    $input -> value   = number_format($sor[ $ertek['name'] ], '', '', '.');
                }else{
                    if( strstr($ertek['class'], 'datepicker') ) {
                        if( $sor[ $ertek['name'] ] != '0000-00-00' ) {
                            $input -> value   = date('Y.m.d', strtotime($sor[ $ertek['name'] ]));
                        }
                    } else {
                        $input -> value   = $sor[ $ertek['name'] ];
                    }
                }
            }

            if($ertek['value_felulir']){
                $input -> value   = $ertek['value_felulir'];
            }

            $value = $input -> value;

            $kulso_val = $input -> value;
            if( !$kulso_val ) {
                $kulso_val = 0;
            }

            $csoport_where = '';
            if( $ertek['csoport'] ) {
                $csoport_where = ' and csoport='.$ertek['csoport'];
            }

            /* Lekérdezzük a külső tábla értékét */
            $kulso_ertek = $adatbazis_kapcsolat->s($ertek['listaz'], $ertek['tabla'], $ertek['oszlop_keres'].'='.$kulso_val.$csoport_where, '', '1');

            if( $kulso_ertek ) {
                $input -> value = $kulso_ertek[0][$ertek['listaz']];
            }

            $html = $input -> getHtml().'<input type="hidden" name="'.$ertek['name'].'" id="'.$ertek['name'].'" value="'.$value.'">';

            $rkat = 0;
            if( $ertek['rkat'] ) {
                $rkat = $ertek['rkat'];
            }

            $csoport = 0;
            if( $ertek['csoport'] ) {
                $csoport = $ertek['csoport'];
            }

            $id_kiir = 'recid';
            if( $ertek['id_kiir'] ) {
                $id_kiir = $ertek['id_kiir'];
            }

            $html .= nl2('
                <script>
                    $(document).ready(function() {
                        $(\'#'.$ertek['name'].'_autocomp\').autocomplete({
                            \'php_fajl\'        : \'../autocomplete.php\',
                            \'tabla\'           : \''.$ertek['tabla'].'\',
                            \'oszlop_keres\'    : \''.$ertek['oszlop_keres_autocomp'].'\',
                            \'oszlop_listaz\'   : \''.$ertek['listaz'].'\',
                            \'rkat\'            : '.$rkat.',
                            \'csoport\'         : '.$csoport.',
                            \'id_kiir\'         : \''.$id_kiir.'\'
                        });
                    });
                </script>
            ');

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if($ertek['osszevon']){
                $osszevon_flag = 1;
            }else{
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

        case 'button':
            $input = new Input($ertek['type']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> style    = $ertek['style'];
            $input -> value    = $ertek['value'];

            $html = $input -> getHtml();

            $mezok .= "<div class=\"{$ertek['div_class']}\" style=\"{$ertek['div_style']}\">$html</div>";
        break;

        case 'checkbox':
            $input = new Input($ertek['type']);
            if($sor[ $ertek['name'] ]){
                $input -> checked = true;
            }elseif($ertek['checked']){
                $input -> checked  = true;
            }

            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> style    = $ertek['style'];

            $html = '<div class="cb">'.$input -> getHtml().'<label for="'.$input -> id.'" onclick="$(\'#mehet_gomb\').addClass(\'gombb_alert\');"></label>';
            if($ertek['i']){
                $html .= ' <div class="tooltip di" style="margin-left: 2.5rem"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div>';
            }
            $html .= '</div>';

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $ertek['pre'].$html.$ertek['post'];

            if($ertek['osszevon']){
                $osszevon_flag = 1;
            }else{
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'div':
            if($ertek['value']){
                $ertek['megn'] = $ertek['value'];
            }
            $mezok .= "<div class=\"{$ertek['div_class']}\" style=\"{$ertek['div_style']}\">{$ertek['megn']}</div>";
        break;

        case 'bsz_statusz':
            if($ertek['value']){
                $ertek['megn'] = $ertek['value'];
            }

            $disabled = '';
            if( $ertek['disabled'] ) {
                $disabled = ' disabled="disabled"';
            }

            $statusz = '';
            if( $sor['statusz'] ) {
                $statusz_sor = $adatbazis_kapcsolat->s('recid nev', 'bsz_statusz', 'recid='.$sor['statusz'], '', '1');

                if( $statusz_sor ) {
                    $statusz_sor = $statusz_sor[0];

                    $statusz = $statusz_sor['nev'];
                }
            }

            $mezok .= nl2('
                <div class="dtr">
                    <div class="dtc form_bal"><span>'.$ertek['megn'].'</span></div>
                    <div class="dtc form_jobb statusz">
                        <div class="text">'.$statusz.'</div>
                        <div class="statuszGombok">
                            <input type="button" class="gombb gomb_zold" value="Igazolás"'.$disabled.'>
                            <input type="button" class="gombb_alert" value="Igazolás elutasítása"'.$disabled.'>
                        </div>
                    </div>
                </div>
            ');
        break;

        case 'statusz':
            if($ertek['value']){
                $ertek['megn'] = $ertek['value'];
            }
            if( $ertek['oldal'] == 'bejelentes' ) {
                $statusz_sor = $adatbazis_kapcsolat->s('recid stat_id text', 'bejelentes_statusz', "`id` = '".$_GET['id']."'", 'insert_dt desc', '1');
                
                $statusz = '';
                if( $statusz_sor ) {
                    $statusz_sor = $statusz_sor[0];

                    /* Státusz kiegészítés */
                    $statusz_text = '<p>'.$statusz_sor['text'].'</p>';

                    /* Státusz neve */
                    $stat_text = $adatbazis_kapcsolat->s('nev szinkod', 'bejelentes_statusz_text', 'recid='.$statusz_sor['stat_id'], '', '1');

                    if( $stat_text ) {
                        $stat_text = $stat_text[0];

                        $statusz = $stat_text['nev'];

                        $text_color = '';
                        if( $stat_text['szinkod'] && !strstr($stat_text['szinkod'], 'fff') ) {
                            $text_color = ' style="color: '.$stat_text['szinkod'].';"';
                        }
                    }
                }

                $mezok .= nl2('
                    <div class="dtr">
                        <div class="dtc form_bal"><span>'.$ertek['megn'].'</span></div>
                        <div class="dtc form_jobb statusz">
                            <div class="text"'.$text_color.'>'.$statusz.'</div>
                            '.$statusz_text.'
                        </div>
                    </div>
                ');
            }
        break;

        case 'dl':
            $html = din_list( $ertek['tabla'], $ertek['oszlop'], $ertek['name'], $ertek['php_fajl'], $sor[ $ertek['name'] ], $sor[ $ertek['dl_kellid'] ], $ertek['default'] );
            if($ertek['alert']){
                $html .= ' *';
            }

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if($ertek['osszevon']){
                $osszevon_flag = 1;
            }else{
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'file':
            $input = new Input($ertek['type']);
            $input -> id      = $ertek['name'];
            $input -> name    = $ertek['name'];
            $input -> style   = $ertek['style'];

            if($ertek['fajl_torlessel']){
                //A $sor['fajl'] érétkét be kell állítani a fájl elérési útvonalának, amely oszlopot a $fajloszlop változóban tárolunk
                $sor['fajl']   = '../'.get_utvonal().$sor[$ertek['oszlop']];
                $sor['oszlop'] = $ertek['oszlop'];
                $sor['tabla']  = $megnevezes;
                $input -> fajlTorlessel = $sor;
                $input -> fajlTorlessel['sorid'] = $sor['id'];
			}

            $html = $input -> getHtml();
            if($ertek['alert']){
                $html .= ' *';
            }

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if($ertek['osszevon']){
                $osszevon_flag = 1;
            }else{
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
		break;

		case 'hidden':
            $input = new Input($ertek['type']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> jelszometer = $ertek['jelszometer'];
            $input -> name     = $ertek['name'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> style    = $ertek['style'];
			$input -> value    = $ertek['value'];

            $html = $input -> getHtml();

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if($ertek['osszevon']){
                $osszevon_flag = 1;
            }else{
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'html':
            if($ertek['value']){
                $ertek['megn'] = $ertek['value'];
            }
            $mezok .= $ertek['megn'];
        break;

		case 'info':
            $mezok .= nl2('<div class="dtr">
                              <div class="dtc form_bal"><span>'.$ertek['megn'].'</span></div>
                              <div class="dtc form_jobb"><input type="text" name="'.$ertek['name'].'" class="oriasi" value="'.$ertek['value'].'" disabled="disabled"></div>
                          </div>');
        break;

		case 'jsfile':

            //Csak kép
            if($ertek['csakkep']){
                $ertek['csakkep']  = ' accept="image/*"';
                $ertek['csakkep2'] = ' && fajlok[i].type.match(/^image\//)';
            }

            //Fájlméret
            $meret = round($ertek['meret']/1048576);
            if(!$meret){
                $meret = 1;
            }

            //Bejegyzés szerkesztése vagy új feltöltés
            $uj_feltolt = '';
            if(isset($_GET['uj'])){
                $uj_feltolt = '1';
            }

            //Megnevezés változó/azonosító
            $megn = $ertek['tabla'].'_'.$ertek['oszlop'];

            //Fájlfeltöltéskor milyen paraméterek menjenek át
            $parameterek = nl2("
                                'sorid'         :   '{$_GET['id']}',
                                'nev'           :   '".addslashes($sor['nev'])."',
                                'megnevezes'    :   '$megn',
                                'meret'         :   '{$ertek['meret']}',
                                'algoritmus'    :   '{$ertek['algoritmus']}',
                                'tabla'         :   '{$ertek['tabla']}',
                                'oszlop'        :   '{$ertek['oszlop']}',
                                'csakkep'       :   '{$ertek['csakkep']}',
                                'reload'        :   '{$ertek['reload']}',
                                'uj_feltolt'    :   '{$uj_feltolt}'
                                 ");

            if(!$ertek['php_fajl']){
                $ertek['php_fajl'] = '../feltolt.php';
            }

            $torles_gomb    = '';
            $akt_fajl       = $adatbazis_kapcsolat -> s($ertek['oszlop'], $ertek['tabla'], 'recid='.$_GET['id']);
            $akt_fajl       = $akt_fajl[0];
            $utvonal        = '../'.get_utvonal().$akt_fajl[$ertek['oszlop']];

            //Ha minden fájl feltöltése kész, mi történjen
            $minden_kesz = '';
            if(isset($_GET['modosit']) || isset($_GET['dokumentumok'])) {
                $minden_kesz = 'get_admin_file_thumb(\''.$ertek['tabla'].'\', \''.$ertek['oszlop'].'\', \''.$_GET['id'].'\', \'#jsff_'.$megn.'_thumb\'); $(\'\');';
            }else{

            }

            $fajl_thumb  = get_admin_file_thumb($ertek['tabla'], $ertek['oszlop'], $_GET['id']);

            $mit   = array('{MEGN}', '{PHP_FAJL}',          '{CSAK_KEP}',       '{CSAK_KEP2}',      '{MERET}',  '{MINDEN_KESZ}',     '{PARAMETEREK}', '{FAJL}'  );
            $mivel = array($megn,    $ertek['php_fajl'],    $ertek['csakkep'],  $ertek['csakkep2'], $meret,     $minden_kesz,        $parameterek,    $fajl_thumb     );
            $html  = str_replace($mit, $mivel, fajlbol_olvas('../sablon/admin/jsfile.html', true));

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $ertek['pre'].$html.$ertek['post'];

            if($ertek['osszevon']){
                $osszevon_flag = 1;
            }else{
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
		break;

		case 'password':
            $input = new Input($ertek['type']);

            if($ertek['alert']){
                $ertek['class'] .= ' csillag';
            }

            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> jelszometer = $ertek['jelszometer'];
            $input -> name     = $ertek['name'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> style    = $ertek['style'];

            if($ertek['i']){
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            $html = $input -> getHtml();

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if($ertek['osszevon']){
                $osszevon_flag = 1;
            }else{
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'radio':
            $input = new Input($ertek['type']);

            if(!$ertek['id']){
                $ertek['id'] = $ertek['value'];
            }

            $ertek['post'] = ' <label class="link" for="'.$ertek['id'].'">'.$ertek['value'].'</label>';
            if($_SESSION[$ertek['name']]==$sor[$ertek['name']]){
                $ertek['checked'] = 'checked';
            }

            $input -> cssClass = $ertek['class'];
            $input -> checked  = $ertek['checked'];
            $input -> id       = $ertek['id'];
            $input -> name     = $ertek['name'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> post     = $ertek['post'];
            $input -> pre      = $ertek['pre'];
            $input -> style    = $ertek['style'];
            $input -> value    = $ertek['value'];

            $html = $input -> getHtml();

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if($ertek['osszevon']){
                $osszevon_flag = 1;
            }else{
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_VALAMI':
            $input = new Select($select_VALAMI, $sor['x']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> naptar   = $ertek['naptar'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> style    = $ertek['style'];

            if($ertek['i']){
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            //Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            //$input -> value    = $ertek['value'];

            $html = $input -> getHtml();
            if($ertek['alert']){
                //$html .= ' *';
            }

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if($ertek['osszevon']){
                $osszevon_flag = 1;
            }else{
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_erteklista':
            $input = new Select($select_erteklista, $sor['csoport']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> naptar   = $ertek['naptar'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> style    = $ertek['style'];

            if($ertek['i']){
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            //Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            //$input -> value    = $ertek['value'];

            $html = $input -> getHtml();
            if($ertek['alert']){
                //$html .= ' *';
            }

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if($ertek['osszevon']){
                $osszevon_flag = 1;
            }else{
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

        case 'select_felh_telephely':
            /* Telephelyek lekérdezése */
            $select_felh_telephely = array();
            array_push($select_felh_telephely, array('name' => '- Válasszon -', 'value' => '' ));

            /* Ha új sort szúrunk be, akkor a SESSION-ből vesszük ki a loginnevet, ha már szerkesztés alatt vagyunk, akkor pedig adatbázibsból */
            $loginNev = $sor['loginnev'];
            if( isset($_GET['uj']) ) {
                $loginNev = $_SESSION[get_db_tabla().'user'];
            }

            /* Kapcsolódó tábla adatai */
            $telephely_kapcs = $adatbazis_kapcsolat->s('telephely_id', 'telephely_kapcs', '`nev`=\''.$loginNev.'\'');
//echo $adatbazis_kapcsolat->gp() . '<br>';
            if( $telephely_kapcs ) {
                $telephely_kapcs = adatbazis2tomb($telephely_kapcs, 'telephely_id');

                $telephelyek = $adatbazis_kapcsolat -> select('id recid nev', 'telephely', "`id` IN (".implode(',', $telephely_kapcs).")", 'nev');
                foreach($telephelyek as $telephely) {
                    if( count($telephelyek) == 1 ) {
                        $sor['telephely_id'] = $telephely['id'];
                        $ertek['disabled'] = true;
                    }

                    array_push($select_felh_telephely, array('name' => $telephely['nev'], 'value' => $telephely['id'] ));
                }
            }

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

            $input = new Select($select_felh_telephely, $sor['telephely_id']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> naptar   = $ertek['naptar'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> onchange = $ertek['onchange'];
            $input -> style    = $ertek['style'];

            if($ertek['i']){
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            //Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            //$input -> value    = $ertek['value'];

            $html = $input -> getHtml();

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if($ertek['osszevon']){
                $osszevon_flag = 1;
            }else{
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

        case 'select_rkat':
            $input = new Select($select_rkat, $sor['rkat']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> naptar   = $ertek['naptar'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> style    = $ertek['style'];

            if($ertek['i']){
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            //Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            //$input -> value    = $ertek['value'];

            $html = $input -> getHtml();
            if($ertek['alert']){
                $html .= ' *';
            }

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if($ertek['osszevon']){
                $osszevon_flag = 1;
            }else{
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_fiz_mod':
            $input = new Select($select_fiz_mod, $sor['fiz_mod']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> naptar   = $ertek['naptar'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> style    = $ertek['style'];

            if($ertek['i']){
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            //Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            //$input -> value    = $ertek['value'];

            $html = $input -> getHtml();
            if($ertek['alert']){
                $html .= ' *';
            }

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if($ertek['osszevon']){
                $osszevon_flag = 1;
            }else{
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_afa_tipus':
            $input = new Select($select_afa_tipus, $sor['afa_tipus']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> naptar   = $ertek['naptar'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> style    = $ertek['style'];

            if($ertek['i']){
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            //Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            //$input -> value    = $ertek['value'];

            $html = $input -> getHtml();
            if($ertek['alert']){
                $html .= ' *';
            }

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if($ertek['osszevon']){
                $osszevon_flag = 1;
            }else{
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_eszk_csop':
            $input = new Select($select_eszk_csop, $sor['eszkoz_csop']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> naptar   = $ertek['naptar'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> style    = $ertek['style'];

            if($ertek['i']){
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            //Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            //$input -> value    = $ertek['value'];

            $html = $input -> getHtml();
            if($ertek['alert']){
                $html .= ' *';
            }

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if($ertek['osszevon']){
                $osszevon_flag = 1;
            }else{
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_cimzett':
            $input = new Select($select_cimzett, $sor['cimzett']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> naptar   = $ertek['naptar'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> style    = $ertek['style'];

            if($ertek['i']){
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            //Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            //$input -> value    = $ertek['value'];

            $html = $input -> getHtml();
            if($ertek['alert']){
                $html .= ' *';
            }

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if($ertek['osszevon']){
                $osszevon_flag = 1;
            }else{
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_ill_reszleg':
            $input = new Select($select_ill_reszleg, $sor['ill_reszleg']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> naptar   = $ertek['naptar'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> style    = $ertek['style'];

            if($ertek['i']){
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            //Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            //$input -> value    = $ertek['value'];

            $html = $input -> getHtml();
            if($ertek['alert']){
                $html .= ' *';
            }

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if($ertek['osszevon']){
                $osszevon_flag = 1;
            }else{
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_cikltip':
            $input = new Select($select_cikltip, $sor['cikltip']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> naptar   = $ertek['naptar'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> style    = $ertek['style'];

            if($ertek['i']){
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            //Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            //$input -> value    = $ertek['value'];

            $html = $input -> getHtml();
            if($ertek['alert']){
                $html .= ' *';
            }

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if($ertek['osszevon']){
                $osszevon_flag = 1;
            }else{
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_honapjan':
            $input = new Select($select_honapjan, $sor['honapjan']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> naptar   = $ertek['naptar'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> pre      = $ertek['pre'];
            $input -> post     = $ertek['post'];
            $input -> style    = $ertek['style'];

            if($ertek['i']){
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            //Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            //$input -> value    = $ertek['value'];

            $html = $input -> getHtml();
            if($ertek['alert']){
                $html .= ' *';
            }

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if($ertek['osszevon']){
                $osszevon_flag = 1;
            }else{
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_ho_ism':
            $input = new Select($select_ho_ism, $sor['ciklhet']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> naptar   = $ertek['naptar'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> pre      = $ertek['pre'];
            $input -> post     = $ertek['post'];
            $input -> style    = $ertek['style'];

            if($ertek['i']){
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            //Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            //$input -> value    = $ertek['value'];

            $html = $input -> getHtml();
            if($ertek['alert']){
                $html .= ' *';
            }

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if($ertek['osszevon']){
                $osszevon_flag = 1;
            }else{
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_ho_soran':
            $input = new Select($select_ho_soran, $sor['ho_rendsz_het']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> naptar   = $ertek['naptar'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> pre      = $ertek['pre'];
            $input -> post     = $ertek['post'];
            $input -> style    = $ertek['style'];

            if($ertek['i']){
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            //Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            //$input -> value    = $ertek['value'];

            $html = $input -> getHtml();
            if($ertek['alert']){
                $html .= ' *';
            }

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if($ertek['osszevon']){
                $osszevon_flag = 1;
            }else{
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_szemelyzet':
            $input = new Select($select_szemelyzet, $sor['szemelyzet_id']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> naptar   = $ertek['naptar'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> pre      = $ertek['pre'];
            $input -> post     = $ertek['post'];
            $input -> style    = $ertek['style'];

            if($ertek['i']){
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            //Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            //$input -> value    = $ertek['value'];

            $html = $input -> getHtml();
            if($ertek['alert']){
                $html .= ' *';
            }

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if($ertek['osszevon']){
                $osszevon_flag = 1;
            }else{
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;
        //{KOVETKEZO_SWITCH}

		case 'submit':
            $input = new Input($ertek['type']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> style    = $ertek['style'];
            $input -> value    = $ertek['value'];

            $html = $input -> getHtml();

            $mezok .= "<div class=\"form_kozep {$ertek['div_class']}\" style=\"{$ertek['div_style']}\">$html</div>";
        break;

		case 'szep':
            $tag = new Tag($ertek['type'], $ertek['megn']);
            $style = '';
            if($ertek['style']){
                $style = ' style="'.$ertek['style'].'"';
            }
            if($ertek['class']){
                $ertek['class'] .= ' szep';
            }else{
                $ertek['class'] = 'szep';
            }
            $mezok .= "<div$style class=\"{$ertek['class']}\"></div>";
        break;

        case 'text':
            $input = new Input($ertek['type']);

            //Ha ezresít, automatikusan meghívunk rá két JS-t
            if( $ertek['ezresit'] ){
                $ertek['onkeyup'] .= '; onlyNumbers(this); this.value=ezresek(this.value,\'.\')';
                $sor[$ertek['name']] = number_format($sor[$ertek['name']], 0, '', '.');
            }

            if($ertek['alert']){
                $ertek['class'] .= ' csillag';
            }

            $input -> cssClass  = $ertek['class'];
            $input -> disabled  = $ertek['disabled'];
            $input -> id        = $ertek['name'];
            $input -> maxlength = $ertek['maxlength'];
            $input -> name      = $ertek['name'];
            $input -> naptar    = $ertek['naptar'];
            $input -> onblur    = $ertek['onblur'];
            $input -> onclick   = $ertek['onclick'];
            $input -> onfocus   = $ertek['onfocus'];
            $input -> oninput   = $ertek['oninput'];
            $input -> onkeyup   = $ertek['onkeyup'];
            $input -> style     = $ertek['style'];
            $input -> placeholder = $ertek['placeholder'];
            $input -> post      = $ertek['post'];
            $input -> pre       = $ertek['pre'];
            if($ertek['i']){
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
                if( strpos($input -> cssClass, 'oriasi')!==false || strpos($input -> cssClass, 'nagy')!==false ){
                    $input -> style .= '; width: 95%;';
                }
            }

            if(!$sor[ $ertek['name'] ]) {
                $input -> value   = $ertek['value'];
            }else{
                if($ertek['szam']) {
                    $input -> value   = number_format($sor[ $ertek['name'] ], '', '', '.');
                }else{
                    if( strstr($ertek['class'], 'datepicker') ) {
                        if( $sor[ $ertek['name'] ] != '0000-00-00' ) {
                            $input -> value   = date('Y.m.d', strtotime($sor[ $ertek['name'] ]));
                        }
                    } else {
                        $input -> value   = $sor[ $ertek['name'] ];
                    }
                }
            }

            if( basename(self()) == 'szallito.php' && $ertek['name'] == 'id' ) {
                $input -> onkeyup = str_replace('{ID}', '\''.$input -> value.'\', ', $input -> onkeyup);
            }

            if($ertek['value_felulir']){
                $input -> value   = $ertek['value_felulir'];
            }

            $html = $input -> getHtml();

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if($ertek['osszevon']){
                $osszevon_flag = 1;
            }else{
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'textarea':
            if(!$sor[ $ertek['name'] ]){
                $sor[ $ertek['name'] ] = $ertek['value'];
            }

            if($ertek['alert']){
                $ertek['class'] .= ' csillag';
            }

            $tag = new Tag($ertek['type'], $sor[ $ertek['name'] ]);
            $tag -> cssClass    = $ertek['class'];
            $tag -> disabled    = $ertek['disabled'];
            $tag -> id          = $ertek['name'];
            $tag -> maxlength   = $ertek['maxlength'];
            $tag -> name        = $ertek['name'];
            $tag -> naptar      = $ertek['naptar'];
            $tag -> onblur      = $ertek['onblur'];
            $tag -> onclick     = $ertek['onclick'];
            $tag -> onfocus     = $ertek['onfocus'];
            $tag -> onkeyup     = $ertek['onkeyup'];
            $tag -> style       = $ertek['style'];
            $tag -> placeholder = $ertek['placeholder'];
            $tag -> post        = $ertek['post'];
            $tag -> pre         = $ertek['pre'];
            if($ertek['i']){
                if(!$ertek['wysiwyg']){
                    $tag -> post = ' <div class="tooltip di" style="position: absolute; margin-left: 0.2rem"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$tag -> post;
                }else{
                    $tag -> pre = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$tag -> pre;
                }
            }

            $html         = '';
            $wysiwyg_html = '';
            if($ertek['wysiwyg']){
                $wysiwyg_html .= '<script>CKEDITOR.replace( \''.$ertek['name'].'\',{toolbar : \''.$ertek['wysiwyg'].'\'';
                if($ertek['autogrow']){
                    $wysiwyg_html .= ',extraPlugins : \'autogrow\'';
                }
                if($ertek['feltoltes']){
                    $wysiwyg_html .= ', filebrowserUploadUrl : \'../'.get_utvonal().'wysiwyg/ckfinder/core/connector/php/connector.php?command=QuickUpload&type='.$ertek['feltoltes'].'\'';
                }
                if($ertek['width']){
                    $wysiwyg_html .= ', width: \''.$ertek['width'].'\'';
                }
                if($ertek['height']){
                    $wysiwyg_html .= ', height: \''.$ertek['height'].'\'';
                }
                $wysiwyg_html .= nl2('});
                    CKEDITOR.instances.'.$ertek['name'].'.on(\'change\', function() {
                       CKEDITOR.instances[\''.$ertek['name'].'\'].updateElement();
                       $(\'#mehet_gomb\').addClass(\'gombb_alert\');
                    });
                </script>');

            }
            if($ertek['bbkod']){
                $html .= str_replace('{TEXTAREA_ID}', $ertek['name'], fajlbol_olvas('../sablon/admin/bbkod.html', true));
            }


            $html .= $tag -> getHtml().$wysiwyg_html;

            //Ha limitálva van a textarea (WYSIWYG-et nem célszerű együtt használni)
            if($ertek['limit']){
                $html .= '<script>$(function(){ $(\'#'.$ertek['name'].'\').keyup(function(){ textarea_limit(\''.$ertek['name'].'\', '.$ertek['limit'].', \''.$ertek['name'].'_info\'); }) }); </script>';
                $html .= '<div id="'.$ertek['name'].'_info"></div>';
            }

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if($ertek['osszevon']){
                $osszevon_flag = 1;
            }else{
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;
	}
}
?>
