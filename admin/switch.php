<?php
require_once('../class/html/class.Input.php');
require_once('../class/html/class.Select.php');


$osszevon_flag  = 0;
$mezok          = '';
foreach ( $akt_tomb as $ertek ) {

    // Sessionből feltöltjük a sort, ha üres. Újként szerkesztéshez kell, remélem nem kavar be máshol
    if( (isset($_GET['ujkent']) || isset($_GET['ujkent_bejelentes'])) && !$sor[$ertek['name']] && $_SESSION[$ertek['name']] ) {
        $sor[$ertek['name']] = $_SESSION[$ertek['name']];
    }

	switch ( $ertek['type'] ) {

        case 'autocomplete':
            $input = new Input('text');

            // Ha ezresít, automatikusan meghívunk rá két JS-t
            if( $ertek['ezresit'] ) {
                $ertek['onkeyup'] .= '; onlyNumbers($(this)); this.value=ezresek(this.value,\'.\')';
                $sor[$ertek['name']] = number_format($sor[$ertek['name']], 0, '', '.');
            }

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

            // Login fülön s külső kivitelezőt választva nem kell a disabled
            if( basename(self()) == 'login.php' && $sor['szemelyzet_id'] == KULSO_KIVITELEZO ) {
                $ertek['disabled'] = false;
            }

            // Onblurra a hidden mező is nullázódjon, ha üres volna a mező
            $ertek['onblur'] .= 'if(!$(this).val()){ $(\'#'.$ertek['name'].'\').val(\'\'); }';

            $input -> autocomplete  = 'off';
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
            $input -> post          = $ertek['post'];
            $input -> pre           = $ertek['pre'];
            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
                if( strpos($input -> cssClass, 'oriasi')!==false || strpos($input -> cssClass, 'nagy')!==false ){
                    $input -> style .= '; width: 95%;';
                }
            }

            if( !$sor[$ertek['name']] ) {
                $input -> value = $ertek['value'];
            } else {
                if( $ertek['szam'] ) {
                    $input -> value = number_format($sor[ $ertek['name'] ], '', '', '.');
                } else {
                    if( strstr($ertek['class'], 'datepicker') ) {
                        if( $sor[ $ertek['name'] ] != '0000-00-00' ) {
                            $input -> value = date('Y.m.d', strtotime($sor[ $ertek['name'] ]));
                        }
                    } else {
                        $input -> value = $sor[ $ertek['name'] ];
                    }
                }
            }
            if( $ertek['value_felulir'] ) {
                $input -> value   = $ertek['value_felulir'];
            }

            $value = $input -> value;

            $kulso_val = $input -> value;
            if( !$kulso_val ) {
                $kulso_val = 0;
            }

            $csoport_where = '';
            if( $ertek['csoport'] ) {
                $csoport_where = " AND `csoport`='".$ertek['csoport']."'";
            }

            // Lekérdezzük a külső tábla értékét
            $tabla = $ertek['tabla'];
            if( $ertek['tabla'] == 'obj_csakszint' ) {
                $tabla = 'obj';
            }
            $kulso_ertek = $adatbazis_kapcsolat->s($ertek['listaz'], $tabla, '`'.$ertek['oszlop_keres'].'`=\''.$kulso_val.'\''.$csoport_where, '', '1');

            if( $kulso_ertek ) {
                $input -> value = $kulso_ertek[0][$ertek['listaz']];
            }

            $html = $input -> getHtml();
            if( $ertek['id_latszik'] ) {
                $html = '<input type="text" readonly name="'.$ertek['name'].'" id="'.$ertek['name'].'" value="'.$value.'" style="width: 5rem"> '.$html;
            } else {
                $html .= '<input type="hidden" name="'.$ertek['name'].'" id="'.$ertek['name'].'" value="'.$value.'">';
            }

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

            // Van olyan eset, amikor a JS-el még nem tesszük rá az autocomplete működést, mert duplán volna rátéve 
            // (pl. helység listánál csak akkor kapja meg, ha már tudjuk a szállodát). Ha duplán kapja meg, az azért baj, mert a lefele nyíllal lépkedve duplákat ugrik a sorokon.
            if( !$ertek['js_t_meg_ne_tedd_ra'] ) {
                $html .= nl2(
                        // Lényeges, hogy ne legyen a string elején szóköz
                        // Az autocomplete tulajdonságot egy kis timeout után adjuk rá, hogy az utólagosan rátett readonly biztosan rajta legyen már
                        '<script>
                        $(document).ready(function() {
                            setTimeout(function(){
                                $(\'#'.$ertek['name'].'_autocomp\').autocomplete({
                                    \'php_fajl\'        : \'../autocomplete.php\',
                                    \'tabla\'           : \''.$ertek['tabla'].'\',
                                    \'oszlop_keres\'    : \''.$ertek['oszlop_keres_autocomp'].'\',
                                    \'oszlop_listaz\'   : \''.$ertek['listaz'].'\',
                                    \'aktiv\'           : \''.$ertek['autocomplete_aktiv'].'\',
                                    \'rkat\'            : '.$rkat.',
                                    \'csoport\'         : '.$csoport.',
                                    \'recid\'           : \''.$_GET['id'].'\',
                                    \'mezo\'            : \''.$_GET['mezo'].'\',
                                    \'op\'              : \''.$_GET['op'].'\',
                                    \'felt\'            : \''.$_GET['felt'].'\',
                                    \'beosztas_id\'     : \''.$ertek['beosztas_id'].'\',
                                    \'szemelyzet_id\'   : \''.$ertek['szemelyzet_id'].'\',
                                    \'id_kiir\'         : \''.$id_kiir.'\',
                                    \'complete\'        : function(){ '.$ertek['complete'].' }
                                });
                            }, 1000);
                        });
                    </script>'
                    // Lényeges, hogy ne legyen a string végén szóköz
                  );
            }

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
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

            $readonly = '';
            if($input -> disabled){
                $readonly = ' readonly';
            }
            $html = '<div class="cb">'.$input -> getHtml().'<label'.$readonly.' for="'.$input -> id.'" onclick="$(\'#mehet_gomb\').addClass(\'gombb_alert\');"></label>';
            if( $ertek['i'] ) {
                $html .= ' <div class="tooltip di" style="margin-left: 2.5rem"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div>';
            }
            $html .= '</div>';

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\">{$ertek['megn']}</div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText'.$ertek['felirat_class'].'">'.$ertek['megn'].'</span>';
            }

            $mezok .= '<span class="'.$ertek['felirat_class'].'">'.$ertek['pre'].$html.$ertek['post'].'</span>';

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

        // A colorpicker csak akkor fog működni, ha emellett gondoskodsz a jPicker.js és jPicker.css-ek meghívásáról is.
        case 'colorpicker':
            $input = new Input('text');

            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> style    = $ertek['style'];
            $input -> value    = $sor[$ertek['name']];

            $readonly = '';
            if( $input->disabled ) {
                $readonly = ' readonly';
            }
            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\">{$ertek['megn']}</div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText'.$ertek['felirat_class'].'">'.$ertek['megn'].'</span>';
            }

            $mezok .= '<span class="'.$ertek['felirat_class'].'">'.$ertek['pre'].$html.$ertek['post'].'</span>';

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
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
                $disabled = ' disabled';
            }

            $statusz = '';
            if( $sor['statusz'] ) {
                $statusz_sor = $adatbazis_kapcsolat->s2('recid nev', 'bsz_statusz', 'recid='.$sor['statusz']);

                if( $statusz_sor ) {
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

            // Anyagrendelések alatt a cikkrendfej-ből kell már dolgozni
            if($ertek['oldal']=='anyagigenyles' && isset($_GET['anyagrend'])){
                $anyag       = $adatbazis_kapcsolat->s2('rndid', $ertek['oldal'], "`recid` = '".$_GET['id']."'");
                $statusz_sor = $adatbazis_kapcsolat->s2('stat_id', 'cikkrendfej', "`rndid` = '".$anyag['rndid']."'");
            } else {
                $statusz_sor = $adatbazis_kapcsolat->s2('stat_id', $ertek['oldal'], "`recid` = '".$_GET['id']."'");
            }

            $statusz = '';
            if( $statusz_sor )  {

                // Státusz kiegészítés bejelentés elutasított státusz esetén
                $statusz_text = '';
                if($ertek['oldal']=='bejelentes' && $statusz_sor['stat_id']==BEJELENTES_STAT_ELUTASITVA ){
                    $statusz = $adatbazis_kapcsolat->s2('erteklista_recid text', $ertek['oldal'].'_statusz', "`{$ertek['oldal']}_recid`='{$_GET['id']}'", 'recid desc', '1');
                    if( $statusz['erteklista_recid'] ) {
                        $erteklista = $adatbazis_kapcsolat->s2('info', 'erteklista', 'recid='.$statusz['erteklista_recid']);
                        if( $ertek ) {
                            $statusz_text .= $erteklista['info'];
                        }
                    } else {
                        $statusz_text .= $statusz['text'];
                    }
                }
                // Státusz kiegészítés bejelentés lezárt státusz esetén
                // Státusz kiegészítés munkalap lezárt státusz esetén
                if(
                    ( $ertek['oldal']=='bejelentes' && $statusz_sor['stat_id']==BEJELENTES_STAT_LEZART  ) ||
                    ( $ertek['oldal']=='munkalap'   && $statusz_sor['stat_id']==MUNKALAP_STAT_LEZART    )
                    ){
                    $statusz = $adatbazis_kapcsolat->s2('text', $ertek['oldal'].'_statusz', "`{$ertek['oldal']}_recid`='{$_GET['id']}'", 'recid desc', '1');
                    $statusz_text .= $statusz['text'];
                }

                // Státusz neve
                $statusz = getStatText($ertek['oldal'], $statusz_sor['stat_id']);
                $szinkod = getStatText($ertek['oldal'], $statusz_sor['stat_id'], 'szinkod');
                $text_color = '';
                if( $szinkod && !strstr($szinkod, 'fff') ) {
                    $text_color = ' style="color: '.$szinkod.';"';
                }
            }

            $mezok .= nl2('
                <div class="dtr">
                    <div class="dtc form_bal"><span>'.$ertek['megn'].'</span></div>
                    <div class="dtc form_jobb statusz">
                        <div class="text"'.$text_color.'>'.$statusz.' <span>'.$statusz_text.'</span></div>
                    </div>
                </div>
            ');

        break;

        case 'dl':
            $html = din_list( $ertek['tabla'], $ertek['oszlop'], $ertek['name'], $ertek['php_fajl'], $sor[ $ertek['name'] ], $sor[ $ertek['dl_kellid'] ], $ertek['default'] );

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
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

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
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
			$input -> value    = $sor[ $ertek['name'] ];
            if($ertek['value']){
                $input -> value    = $sor['value'];
            }

            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
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
                              <div class="dtc form_jobb"><input type="text" name="'.$ertek['name'].'" class="kozepes" value="'.$ertek['value'].'" readonly></div>
                          </div>');
        break;

		case 'jsfile':

            // Milyen kiterjesztéseket engedünk meg
            if($ertek['eng_kit']){
                $ertek['eng_kit'] = ' accept="'.$ertek['eng_kit'].'"';
            }

            // Fájlméret
            $meret = round($ertek['meret']/1048576);
            if(!$meret){
                $meret = 1;
            }

            // Bejegyzés szerkesztése vagy új feltöltés
            $uj_feltolt = '';
            if(isset($_GET['uj'])){
                $uj_feltolt = '1';
            }

            // Megnevezés változó/azonosító
            $megn = $ertek['tabla'].'_'.$ertek['oszlop'];

            // Fájlfeltöltéskor milyen paraméterek menjenek át
            $parameterek = nl2("
                                'sorid'         :   '{$_GET['id']}',
                                'nev'           :   '".addslashes($sor['nev'])."',
                                'megnevezes'    :   '$megn',
                                'meret'         :   '{$ertek['meret']}',
                                'algoritmus'    :   '{$ertek['algoritmus']}',
                                'tabla'         :   '{$ertek['tabla']}',
                                'oszlop'        :   '{$ertek['oszlop']}',
                                'reload'        :   '{$ertek['reload']}',
                                'uj_feltolt'    :   '{$uj_feltolt}'
                                 ");

            if(!$ertek['php_fajl']){
                $ertek['php_fajl'] = '../feltolt.php';
            }

            $torles_gomb    = '';
            $akt_fajl       = $adatbazis_kapcsolat -> s2($ertek['oszlop'], $ertek['tabla'], 'recid='.$_GET['id']);
            $utvonal        = '../'.get_utvonal().$akt_fajl[$ertek['oszlop']];

            // Ha minden fájl feltöltése kész, mi történjen
            $minden_kesz = 'get_admin_file_thumb(\''.$ertek['tabla'].'\', \''.$ertek['oszlop'].'\', \''.$_GET['id'].'\', \'#jsff_'.$megn.'_thumb\');';

            $fajl_thumb  = get_admin_file_thumb($ertek['tabla'], $ertek['oszlop'], $_GET['id']);

            $mit   = array('{MEGN}', '{PHP_FAJL}',          '{ENG_KIT}',        '{MERET}',  '{MINDEN_KESZ}',     '{PARAMETEREK}', '{FAJL}',    '{MAX_MERET}'                        );
            $mivel = array($megn,    $ertek['php_fajl'],    $ertek['eng_kit'],  $meret,     $minden_kesz,        $parameterek,    $fajl_thumb, ini_get('upload_max_filesize')*1024  );


            $html  = str_replace($mit, $mivel, fajlbol_olvas('../sablon/admin/jsfile'.$ertek['sablon_post'].'.html', true));

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $ertek['pre'].$html.$ertek['post'];

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
		break;

		case 'password':
            $input = new Input($ertek['type']);

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

            $input -> autocomplete = 'off';
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> jelszometer = $ertek['jelszometer'];
            $input -> name     = $ertek['name'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> style    = $ertek['style'];

            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
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

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_jogcim':

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

            $input = new Select($select_jogcim, $sor['jogcim_recid']);
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

            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            // Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            // $input -> value    = $ertek['value'];

            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_erteklista':

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

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

            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            // Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            // $input -> value    = $ertek['value'];

            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

        case 'select_felh_telephely':
            // Telephelyek lekérdezése
            $select_felh_telephely = array();
            array_push($select_felh_telephely, array('name' => '- Válasszon -', 'value' => '' ));
            
            $loginNev = $_SESSION[get_db_tabla().'user'];

            // Kapcsolódó tábla adatai
            if( $ertek['tobb_szalloda'] ) {
                $where = '`azon` IN (\'DZ\', \'DH\')';
            } else {
                $telephely_kapcs = $adatbazis_kapcsolat->s('telephely_id', 'telephely_kapcs', '`nev`=\''.$loginNev.'\'');
                if( $telephely_kapcs ) {
                    $telephely_kapcs = adatbazis2tomb($telephely_kapcs, 'telephely_id');
                    $where = "`id` IN (".implode(',', $telephely_kapcs).")";
                }
            }
            if( !$ertek['disabled'] ) {
                $where .= " AND `inaktiv`=0";
            }
            $cegkodos_modulok = array('anyag_szamla', 'energia_szamla', 'fenntartasi_szamla', 'projektszamla');
            $self = self('-basename-', '-kit.nélkül-');
            if( 
                (isset($_GET['uj']) && isset($_GET['andoc_id']) && in_array($self, $cegkodos_modulok) )
                || $koltsegviselo_szalloda_lenyilohoz // projektszerzodes.php-ban egy ajaxos szálloda lenyíló betöltése miatt kell + projektszerződés adatlapjára is
            ) {
                
                if( $koltsegviselo_szalloda_lenyilohoz ) {
                    $erteklista = $adatbazis_kapcsolat->s2('cegkod', 'erteklista', "`csoport`='".ERTEKLISTA_KOLTSEGVISELO."' AND `id`='{$koltsegviselo_szalloda_lenyilohoz}'");
                } else {
                    $atmeneti   = $adatbazis_kapcsolat->s2('kolt_vis', $self.'_atmeneti', "`andoc_id`='{$_GET['andoc_id']}'");
                    $erteklista = $adatbazis_kapcsolat->s2('cegkod', 'erteklista', "`csoport`='".ERTEKLISTA_KOLTSEGVISELO."' AND `id`='{$atmeneti['kolt_vis']}'");
                }
                
                if( $erteklista ) {
                    $where .= " AND `cegkod`='{$erteklista['cegkod']}'";
                }
            }
            // unid esetén kell rá egy group by is
            $group_by = '';
            if( $ertek['unid'] ) {
                $group_by = 'unid';
            }
            $telephelyek = $adatbazis_kapcsolat -> select('id recid unid nev modell', 'telephely', $where, 'nev', '', $group_by);
            foreach ( $telephelyek as $telephely ) {
                // Ha csak egy szálloda van, akkor a selectből csak az az egy érték választható ki, ezt meg is tesszük, selectet így disabled-re téve.
                // Mindezet csak akkor, ha új feltöltés üzemmódban vagyunk
                if( count($telephelyek) == 1 && isset($_GET['uj']) ) {
                    $sor['telephely_id'] = $telephely['id'];
                    $ertek['disabled'] = true;
                    $ertek['post'] .= '<input type="hidden" name="telephely_id" id="telephely_id" value="'.$sor['telephely_id'].'"><input type="hidden" name="telephely_txt" id="telephely_txt" value="'.$telephely['nev'].'">';
                }
                
                // A szálloda ID-ját vagy UNID-ját szeretnénk értékként megkapni?
                if( $ertek['unid'] ) {
                    array_push($select_felh_telephely, array('name' => $telephely['nev'], 'value' => $telephely['unid'], 'data-modell' => $telephely['modell'] ));
                } else {
                    array_push($select_felh_telephely, array('name' => $telephely['nev'], 'value' => $telephely['id'], 'data-modell' => $telephely['modell'] ));
                }
            }

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

            $selected = $sor['telephely_id'];
            if( $ertek['unid'] ) {
                $selected = $sor['telephely_unid'];
            }
            if( isset($_GET['uj']) && $_GET['telephely'] ) {
                $selected = $_GET['telephely'];
            }

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

            $ertek['onchange'] = ($ertek['onchange'] ? $ertek['onchange'].' ' : '').'szalloda_modell( this );';

            $input = new Select($select_felh_telephely, $selected);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> naptar   = $ertek['naptar'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> onchange = $ertek['onchange'];
            $input -> post     = $ertek['post'];
            $input -> style    = $ertek['style'];

            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            // Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            // $input -> value    = $ertek['value'];

            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

        case 'select_rkat':

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

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

            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            // Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            // $input -> value    = $ertek['value'];

            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_fiz_mod':

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

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

            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            // Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            // $input -> value    = $ertek['value'];

            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_afa_tipus':

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

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

            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            // Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            // $input -> value    = $ertek['value'];

            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_eszk_csop':

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

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

            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            // Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            // $input -> value    = $ertek['value'];

            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_cimzett':

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

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

            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            // Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            // $input -> value    = $ertek['value'];

            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_ill_reszleg':

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

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

            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            // Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            // $input -> value    = $ertek['value'];

            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_cikltip':

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

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

            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            // Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            // $input -> value    = $ertek['value'];

            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_honapjan':

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

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

            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            // Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            // $input -> value    = $ertek['value'];

            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_ho_ism':

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

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

            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            // Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            // $input -> value    = $ertek['value'];

            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_ho_soran':

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

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

            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            // Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            // $input -> value    = $ertek['value'];

            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_honapok':

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

            $input = new Select($select_honapok, $sor['ho']);
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

            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_szemelyzet':

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

            // Új feltöltésnél biztosan ne legyen hozzárendelt személy
            if (isset($_GET['uj']) && !isset($_GET['ujkent'])) {
                $sor['szemelyzet_id'] = 0;
            }
            $input = new Select($select_szemelyzet, $sor['szemelyzet_id']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> naptar   = $ertek['naptar'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onchange = $ertek['onchange'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> pre      = $ertek['pre'];
            $input -> post     = $ertek['post'];
            $input -> style    = $ertek['style'];

            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            // Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            // $input -> value    = $ertek['value'];

            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;
        
		case 'select_szerzodes_tipus':

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

            $input = new Select($select_szerzodes_tipus, $sor['sztipus']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> naptar   = $ertek['naptar'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onchange = $ertek['onchange'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> pre      = $ertek['pre'];
            $input -> post     = $ertek['post'];
            $input -> style    = $ertek['style'];

            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            // Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            // $input -> value    = $ertek['value'];

            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;


		case 'select_beosztas':

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

            $input = new Select($select_beosztas, $sor['beosztas']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> naptar   = $ertek['naptar'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onchange = $ertek['onchange'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> pre      = $ertek['pre'];
            $input -> post     = $ertek['post'];
            $input -> style    = $ertek['style'];

            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            // Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            // $input -> value    = $ertek['value'];

            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;


		case 'select_unnepnap_tipus':

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

            $input = new Select($select_unnepnap_tipus, $sor['tipus']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> naptar   = $ertek['naptar'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onchange = $ertek['onchange'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> pre      = $ertek['pre'];
            $input -> post     = $ertek['post'];
            $input -> style    = $ertek['style'];

            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            // Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            // $input -> value    = $ertek['value'];

            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_pri_tipus':

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }
            
            $akt_value = $sor['tipus'];
            if( $ertek['value'] ) {
                $akt_value = $ertek['value'];
            }

            $input = new Select($select_pri_tipus, $akt_value);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> naptar   = $ertek['naptar'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onchange = $ertek['onchange'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> pre      = $ertek['pre'];
            $input -> post     = $ertek['post'];
            $input -> style    = $ertek['style'];

            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            // Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            // $input -> value    = $ertek['value'];

            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_pri_besorol':

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

            $input = new Select($select_pri_besorol, $sor['besorol']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> naptar   = $ertek['naptar'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onchange = $ertek['onchange'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> pre      = $ertek['pre'];
            $input -> post     = $ertek['post'];
            $input -> style    = $ertek['style'];

            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            // Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            // $input -> value    = $ertek['value'];

            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_pri_cel':

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

            $input = new Select($select_pri_cel, $sor['megter_cel']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> naptar   = $ertek['naptar'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onchange = $ertek['onchange'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> pre      = $ertek['pre'];
            $input -> post     = $ertek['post'];
            $input -> style    = $ertek['style'];

            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            // Ezt csak akkor aktiváld, ha alapértelmezett értéket szeretnél neki definiálni, de VIGYÁZZ!, a $sor['VALAMI']-t se írja felül, hisz a konstruktornak ez ad értéket! Pl. így: if(!$ertek['value']){ $ertek['value'] = $sor['VALAMI']; }
            // $input -> value    = $ertek['value'];

            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_oracikkid':

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

            $input = new Select($select_oracikkid, $sor['oracikkid']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> naptar   = $ertek['naptar'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onchange = $ertek['onchange'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> pre      = $ertek['pre'];
            $input -> post     = $ertek['post'];
            $input -> style    = $ertek['style'];

            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'select_mobilcsoport':

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

            $input = new Select($select_mobilcsoport, $sor['mobil']);
            $input -> cssClass = $ertek['class'];
            $input -> disabled = $ertek['disabled'];
            $input -> id       = $ertek['name'];
            $input -> name     = $ertek['name'];
            $input -> naptar   = $ertek['naptar'];
            $input -> onblur   = $ertek['onblur'];
            $input -> onchange = $ertek['onchange'];
            $input -> onclick  = $ertek['onclick'];
            $input -> onfocus  = $ertek['onfocus'];
            $input -> pre      = $ertek['pre'];
            $input -> post     = $ertek['post'];
            $input -> style    = $ertek['style'];

            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
            }

            $html = $input -> getHtml();

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_f= 0;
                $mezok .= "</div></div>";
            }
        break;


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
            } else {
                $ertek['class'] = 'szep';
            }
            $mezok .= "<div$style class=\"{$ertek['class']}\"></div>";
        break;

        case 'text':
            $input = new Input($ertek['type']);

            //Ha ezresít, automatikusan meghívunk rá két JS-t
            if( $ertek['ezresit'] ){
                $ertek['onkeyup'] .= '; onlyNumbers($(this)); this.value=ezresek(this.value,\'.\')';
                $sor[$ertek['name']] = number_format($sor[$ertek['name']], 0, '', '.');
            }

            if( $ertek['alert'] ) {
                $ertek['class'] .= ' csillag';
            }

            if($ertek['data']){
                $input -> data = $ertek['data'];
            }

            //Egyelőre fixen off
            $autocomplete = 'off';

            $input -> autocomplete  = $autocomplete;
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
            if( $ertek['i'] ) {
                $input -> post = ' <div class="tooltip di"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$input -> post;
                if( strpos($input -> cssClass, 'oriasi')!==false || strpos($input -> cssClass, 'nagy')!==false ){
                    $input -> style .= '; width: 95%;';
                }
            }

            if($sor[ $ertek['name'] ]=='') {
                $input -> value   = $ertek['value'];
            } else {
                if($ertek['szam']) {
                    $input -> value   = number_format($sor[ $ertek['name'] ], '', '', '.');
                } else {

                    //Date
                    if( strstr($ertek['class'], 'datepicker') ) {
                        if( $sor[ $ertek['name'] ] != '0000-00-00' ) {
                            $input -> value   = date('Y.m.d', strtotime($sor[ $ertek['name'] ]));
                        }
                    //Datetime
                    } elseif( strstr($ertek['class'], 'datetimepicker') ) {
                        if( $sor[ $ertek['name'] ] != '0000-00-00 00:00:00' ) {
                            $input -> value   = date('Y.m.d H:i', strtotime($sor[ $ertek['name'] ]));
                        }
                    //Ha numerikus adat, akkor formázzunk
                    } elseif( strpos($ertek['class'], 'f_num')!==false ) {
                        $tort = 2;
                        if( strpos($ertek['data'], 'data-tort')!==false ){
                            $tort = str_replace(array('data-tort="', '"'), array('', ''), $ertek['data']);
                        }
                        $input -> value   = number_format($sor[ $ertek['name'] ], $tort, ',', ' ');
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

            $hidden = '';
            if( $ertek['letarol'] && !isset($_GET['manualisan']) ) {
                $hidden = '<input type="hidden" name="'.$ertek['name'].'" value="'.$input -> value.'">';
            }

            $html = $input -> getHtml().$hidden;

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;

		case 'textarea':

            if( !$sor[$ertek['name']] ) {
                $sor[ $ertek['name'] ] = $ertek['value'];
            }
            if( $ertek['alert'] ) {
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
            if( $ertek['i'] ) {
                if(!$ertek['wysiwyg']){
                    $tag -> post = ' <div class="tooltip di" style="position: absolute; margin-left: 0.2rem"><img src="../elemek/admin/info.gif" alt="i" class="tooltip"><div>'.$ertek['i'].'</div></div> '.$tag -> post;
                } else {
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

            // Összevonások lekezelése és tartalom összefűzése
            if( !$osszevon_flag ) {
                $mezok .= nl2("
                                <div class=\"dtr{$ertek['dtr_class']}\">
                                    <div class=\"dtc form_bal\"><span>{$ertek['megn']}</span></div>
                                    <div class=\"dtc form_jobb\">
                            ");
            } else {
                $mezok .= '<span class="formText">'.$ertek['megn'].'</span>';
            }

            $mezok .= $html;

            if( $ertek['osszevon'] ) {
                $osszevon_flag = 1;
            } else {
                $osszevon_flag = 0;
                $mezok .= "</div></div>";
            }
        break;
	}
}
