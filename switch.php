<?php
/*                                                                                                                              *\
    switch.php: célja, hogy egy tömb alapján egy HTML űrlapot összeállítson, illetve egy $mezo_mit és $mezo_mivel tömbpárost.
    A HTML-t a $mezok nevű változóba építi fel, így használata (leegyszerűsítve) a következőképpen néz ki:
    
    $akt_tomb definiálása, amit a switch fel tud dolgozni
    require_once('switch.php');
    echo $mezok;
 
    Vagy ha nem íratod ki a $mezok-ket, akkor egy sablon fájlban le tudod cserélni az inputokat a mit-mivel tömbökkel
   (összetettebb űrlap esetére).
    
    FONTOS: ha ezt a fájlt használod léteznie kell a selectek.php-nak is, még akkor is, ha nincs benne lényegi kód.
\*                                                                                                                              */
require_once('class/html/class.Input.php');
require_once('class/html/class.Select.php');
require_once('selectek.php');

$osszevon_flag  = 0;
$mezok          = '';

// Sablon változók
$mezo_mit   = array();
$mezo_mivel = array();

foreach($akt_tomb as $ertek){

	switch($ertek['type']){
		
        case 'button':
            $input = new Input($ertek['type']);
            $input -> alert    = $ertek['alert'];
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> post     = $ertek['post'];
            $input -> pre      = $ertek['pre'];
            $input -> style    = $ertek['style'];
            $input -> value    = $ertek['value'];
            
            $html = $input -> getHtml();
            
            $mezok .= "<div class=\"{$ertek['div_class']}\" style=\"{$ertek['div_style']}\">$html</div>";
        break;
		
        case 'checkbox':
            
            $input = new Input($ertek['type']);
            
            // Checkbox estén a megnevezés az input után jön, nem elé
            $label = $ertek['megn'];
            $ertek['megn'] = '';
            
            // Pipáltságnak beállítása
            if($sor[ $ertek['name'] ]){
                $input -> checked = true;
            }elseif($ertek['checked']){
                $input -> checked  = true;
            }
            
            // Label is CSS-hető
            if( $ertek['labelClass'] ) {
                $ertek['labelClass'] = ' '.$ertek['labelClass'];
            }
            
            if(!$ertek['id']){
                $ertek['id'] = strtolower($ertek['value']);
            }
            
            $input -> alert    = $ertek['alert'];
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> post     = $ertek['post'];
            $input -> style    = $ertek['style'];
            $input -> value    = $ertek['value'];
            
            $html = $input -> getHtml();
            // Szép checkbox html része, altalanos.css-el közreműködve
            $html = nl2('
                <label class="checkbox">
                    '.$html.'
                    <span class="label_text">
                        <span class="ikon">
                            <span></span>
                        </span> '.$label.'
                    </span>
                </label>
                    ');
            
            // Sablon változók feltöltése
            $mezo_mit[]   = '{'.strtoupper(fajlnev_keszit($ertek['name'])).'}';
            $mezo_mivel[] = $html;
            
            // Összevonások lekezelése és tartalom összefűzése
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
        
		case 'file':
            $input = new Input($ertek['type']);
            $input -> id      = $ertek['name'];
            $input -> name    = $ertek['name'];
            $input -> post    = $ertek['post'];
            $input -> pre     = $ertek['pre'];
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
            
            $_SESSION['jsfajl']['tomb'] = $akt_tomb;
            
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
            
            //Több fájl feltöltésekor külső táblát használunk
            if($ertek['kulso_tabla']){
                
                $fajlok = $adatbazis_kapcsolat->s($ertek['oszlop'], $ertek['kulso_tabla'], "{$ertek['tabla']}_id={$_GET['id']}");
                $utvonal = get_utvonal().$akt_fajl[$ertek['oszlop']];
                
            }else{
                $akt_fajl       = $adatbazis_kapcsolat -> s($ertek['oszlop'], $ertek['tabla'], 'id='.$_GET['id']);
                $akt_fajl       = $akt_fajl[0];
                $utvonal        = '../'.get_utvonal().$akt_fajl[$ertek['oszlop']];
            }
            
            //Ha minden fájl feltöltése kész, meghívhatunk vlamilyen JS függvényt
            $minden_kesz = "get_file_thumb('".self()."', 'jsff_{$megn}_fajlok'); $('#jsff_{$megn} progress').fadeOut();";
            
            $mit   = array('{MEGN}', '{PHP_FAJL}',          '{CSAK_KEP}',       '{CSAK_KEP2}',      '{MERET}',  '{MINDEN_KESZ}',     '{PARAMETEREK}');
            $mivel = array($megn,    $ertek['php_fajl'],    $ertek['csakkep'],  $ertek['csakkep2'], $meret,     $minden_kesz,        $parameterek   );
            $html  = str_replace($mit, $mivel, fajlbol_olvas('sablon/jsfile.html', true));
            
            
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

		
		case 'hidden':
            $input = new Input($ertek['type']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> post     = $ertek['post'];
            $input -> pre      = $ertek['pre'];
            $input -> style    = $ertek['style'];
            $input -> value    = $ertek['value'];
            
            if(!$sor[ $ertek['name'] ]){
                $input -> value = $ertek['value'];
            }else{
                $input -> value = $sor[ $ertek['name'] ];
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
        
		case 'password':
            $input = new Input($ertek['type']);
            
            if( $ertek['labelClass'] ) {
                $ertek['labelClass'] = ' '.$ertek['labelClass'];
            }
            
            $input -> alert    = $ertek['alert'];
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> jelszometer = $ertek['jelszometer'];
            $input -> name        = $ertek['name'];
            $input -> onblur      = $ertek['onblur'];
            $input -> onclick     = $ertek['onclick'];
            $input -> onfocus     = $ertek['onfocus'];
            $input -> post        = $ertek['post'];
            $input -> pre         = $ertek['pre'];
            $input -> style       = $ertek['style'];
            
            if($ertek['i']){
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }
            
            $html = $input -> getHtml();
            
            /* Sablon változók feltöltése */
            $mezo_mit[]   = '{'.strtoupper(fajlnev_keszit($ertek['name'])).'}';
            $mezo_mivel[] = nl2('
                <label class="text'.$ertek['labelClass'].'">
                    <span class="label_text">'.$ertek['megn'].'</span>
                    '.$html.'
                </label>
            ');
            
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
                $ertek['id'] = strtolower($ertek['value']);
            }
            
            if( $ertek['labelClass'] ) {
                $ertek['labelClass'] = ' '.$ertek['labelClass'];
            }
            
            //A post-ban kellene megadni, hogy a radiobutton mellett milyen szöveg szerepeljen. Ha ez nincs megadva, akkor a value fog látszani
            if(!$ertek['post']){
                $ertek['post'] = $ertek['value'];
            }

            $ertek['post'] = ' <label class="link" for="'.$ertek['id'].'">'.$ertek['post'].'</label>';
            if($_SESSION[$ertek['name']] && $_SESSION[$ertek['name']]==$sor[$ertek['name']]){
                $ertek['checked'] = 'checked';
            }
            
            $input -> alert    = $ertek['alert'];
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
            
            if($ertek['label']){
                $html = nl2('
                    <label class="checkbox">
                        '.$html.'
                        <span class="label_text">
                            <span class="ikon">
                                <span></span>
                            </span> '.$ertek['label'].'
                        </span>
                    </label>
                        ');
            }
            
            /* Sablon változók feltöltése */
            $mezo_mit[]   = '{'.strtoupper(fajlnev_keszit($ertek['name'])).'_'.strtoupper(fajlnev_keszit($ertek['value'])).'}';
            $mezo_mivel[] = nl2('
                <label class="radio'.$ertek['labelClass'].'">
                    '.$html.'
                    <span class="label_text"><span class="ikon"><span></span></span>'.$ertek['value'].'</span>
                </label>
            ');
            
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
        
		case 'select_valami':
            $input = new Select($select_valami, $sor['valami']);
            
            if( $ertek['labelClass'] ) {
                $ertek['labelClass'] = ' '.$ertek['labelClass'];
            }
            
            $input -> alert    = $ertek['alert'];
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> naptar   = $ertek['naptar'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onchange = $ertek['onchange'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> post     = $ertek['post'];
            $input -> pre      = $ertek['pre'];
            $input -> style    = $ertek['style'];
            $input -> alert    = $ertek['alert'];
            
            if($ertek['i']){
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }
            
            //Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            //$input -> value    = $ertek['value'];

            $html = '<span class="select_style">'.$input -> getHtml().'</span>';
            
            /* Sablon változók feltöltése */
            $mezo_mit[]   = '{'.strtoupper(fajlnev_keszit($ertek['name'])).'}';
            $mezo_mivel[] = nl2('
                <label class="text'.$ertek['labelClass'].'">
                    <span class="label_text">'.$ertek['megn'].'</span>
                    '.$html.'
                </label>
            ');
            
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
		
		case 'submit':
            $input = new Input($ertek['type']);// Lehet submit meg button is
            $input -> cssClass = $ertek['class'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> post     = $ertek['post'];
            $input -> pre      = $ertek['pre'];
            $input -> style    = $ertek['style'];
            $input -> value    = $ertek['value'];
            
            $html = $input -> getHtml();
            
            $mezok .= "<div class=\"{$ertek['div_class']}\" style=\"{$ertek['div_style']}\">$html</div>";
        break;
        
        case 'text':
            $input = new Input($ertek['type']);
            
            //Ha ezresít, automatikusan meghívunk rá két JS-t
            if( $ertek['ezresit'] ){
                $ertek['onkeyup'] .= '; onlyNumbers(this); this.value=ezresek(this.value,\'.\')';
                $sor[$ertek['name']] = number_format($sor[$ertek['name']], 0, '', '.');
            }
            
            if( $ertek['labelClass'] ) {
                $ertek['labelClass'] = ' '.$ertek['labelClass'];
            }
            
            $input -> autocomplete  = $ertek['autocomplete'];
            $input -> alert         = $ertek['alert'];
            $input -> cssClass      = $ertek['class'];
            $input -> disabled      = $ertek['disabled'];
            $input -> id            = $ertek['name'];
            $input -> maxlength     = $ertek['maxlength'];
            $input -> name          = $ertek['name'];
            $input -> naptar        = $ertek['naptar'];
            $input -> onblur        = $ertek['onblur'];
            $input -> onclick       = $ertek['onclick'];
            $input -> onfocus       = $ertek['onfocus'];
            $input -> onkeyup       = $ertek['onkeyup'];
            $input -> placeholder   = $ertek['placeholder'];
            $input -> post          = $ertek['post'];
            $input -> pre           = $ertek['pre'];
            $input -> style         = $ertek['style'];
            
            if($ertek['i']){
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
                if( strpos($input -> cssClass, 'oriasi')!==false || strpos($input -> cssClass, 'nagy')!==false ){
                    $input -> style .= '; width: 95%;';
                }
            }
            
            if(!$sor[ $ertek['name'] ]){
                $input -> value = $ertek['value'];
            }else{
                if($ertek['szam']){
                    $input -> value = number_format($sor[ $ertek['name'] ], '', '', '.');
                }else{
                    $input -> value = $sor[ $ertek['name'] ];
                }
            }
            if($ertek['value_felulir']){
                $input -> value   = $ertek['value_felulir'];
            }
            
            $html = $input -> getHtml();
            
            /* Sablon változók feltöltése */
            $mezo_mit[]   = '{'.strtoupper(fajlnev_keszit($ertek['name'])).'}';
            $mezo_mivel[] = nl2('
                <label class="text'.$ertek['labelClass'].'">
                    <span class="label_text">'.$ertek['megn'].'</span>
                    '.$html.'
                </label>
            ');
            
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
            $tag -> style       = $ertek['style'];
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
                    $wysiwyg_html .= ', filebrowserUploadUrl : \'wysiwyg/ckfinder/core/connector/php/connector.php?command=QuickUpload&type='.$ertek['feltoltes'].'\'';
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