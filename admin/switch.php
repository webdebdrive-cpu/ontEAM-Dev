<?php
require_once('../class/html/class.Input.php');
require_once('../class/html/class.Select.php');

$osszevon_flag  = 0;
$mezok          = '';
foreach($akt_tomb as $ertek){
	switch($ertek['type']){

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
                                    <div class=\"dtc form_bal\">{$ertek['megn']}</div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= $ertek['megn'];
            }

            $mezok .= $html;

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

        case 'dl':
            $html = din_list( $ertek['tabla'], $ertek['oszlop'], $ertek['name'], $ertek['php_fajl'], $sor[ $ertek['name'] ], $sor[ $ertek['dl_kellid'] ], $ertek['default'] );
            if($ertek['alert']){
                $html .= ' *';
            }

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\">{$ertek['megn']}</div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= $ertek['megn'];
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
                                    <div class=\"dtc form_bal\">{$ertek['megn']}</div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= $ertek['megn'];
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
                                    <div class=\"dtc form_bal\">{$ertek['megn']}</div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= $ertek['megn'];
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
            $akt_fajl       = $adatbazis_kapcsolat -> s($ertek['oszlop'], $ertek['tabla'], 'id='.$_GET['id']);
            $akt_fajl       = $akt_fajl[0];
            $utvonal        = '../'.get_utvonal().$akt_fajl[$ertek['oszlop']];

            //Ha minden fájl feltöltése kész, mi történjen
            $minden_kesz = '';
            if(isset($_GET['modosit'])){
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
                                    <div class=\"dtc form_bal\">{$ertek['megn']}</div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= $ertek['megn'];
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
                                    <div class=\"dtc form_bal\">{$ertek['megn']}</div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= $ertek['megn'];
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
                                    <div class=\"dtc form_bal\">{$ertek['megn']}</div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= $ertek['megn'];
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
            $input = new Select($select_VALAMI, $sor['VALAMI']);
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
                                    <div class=\"dtc form_bal\">{$ertek['megn']}</div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= $ertek['megn'];
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
                        $input -> value   = date('Y.m.d', strtotime($sor[ $ertek['name'] ]));
                    } else {
                        $input -> value   = $sor[ $ertek['name'] ];
                    }
                }
            }

            if($ertek['value_felulir']){
                $input -> value   = $ertek['value_felulir'];
            }

            $html = $input -> getHtml();

            //Összevonások lekezelése és tartalom összefűzése
            if(!$osszevon_flag){
                $mezok .= nl2("
                                <div class=\"dtr\">
                                    <div class=\"dtc form_bal\">{$ertek['megn']}</div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= $ertek['megn'];
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
                                    <div class=\"dtc form_bal\">{$ertek['megn']}</div>
                                    <div class=\"dtc form_jobb\">
                            ");
            }else{
                $mezok .= $ertek['megn'];
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
