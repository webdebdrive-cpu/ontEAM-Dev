<?php
// Operátor kiválasztása
if (isset($_POST['f_szuro_mezo'])) {
    
    $akt_tomb_szures = get_akt_tomb($_POST['tabla']);
    
    $selected_equal = '';
    $selected_like = '';
    $selected_empty = '';
    if($_POST['selected']=='equal'  ){ $selected_equal  = ' selected'; }
    if($_POST['selected']=='like'   ){ $selected_like   = ' selected'; }
    if($_POST['selected']=='empty'  ){ $selected_empty  = ' selected'; }
    if($_POST['selected']=='gteq'   ){ $selected_gteq   = ' selected'; }
    if($_POST['selected']=='lteq'   ){ $selected_lteq   = ' selected'; }
    if($_POST['selected']=='gt'     ){ $selected_gt     = ' selected'; }
    if($_POST['selected']=='lt'     ){ $selected_lt     = ' selected'; }
    if($_POST['selected']=='not_eq' ){ $selected_not_eq = ' selected'; }
    
    // A tömbben előforduló selectek tömbje. Innen tudjuk hogy csak két operátor tartozhat hozzá
    $selectek = array(
        'select_cikltip',
        'select_felh_telephely',
        'select_ho_ism',
        'select_ho_soran',
        'select_honapjan',
        'select_szemelyzet',
        'select_pri_tipus',
        'select_pri_besorol',
        );
    
    $operator = '';
    // Feltétel összerakása tömbből
    foreach ($akt_tomb_szures as $ertek){
        if($ertek['name']==$_POST['mezo']){
            
            // Checkbox esetén Igen/Nem
            if($ertek['type']=='checkbox'){
                
                $operator .= '<option value="equal"'.$selected_equal.'>=</option>';
                
            // Dátum mező esetén
            }elseif( strpos($ertek['class'], 'datepicker')!==false || strpos($ertek['class'], 'datetimepicker')!==false ){
                
                $operator .= '<option value="equal"'.$selected_equal.'>=</option>';
                $operator .= '<option value="gt"'.$selected_gt.'>&gt;</option>';
                $operator .= '<option value="lt"'.$selected_lt.'>&lt;</option>';
                
            // Autocomplete esetén a switch.php alapján gyártunk egy inputot
            }elseif($ertek['type']=='autocomplete'){
                
                $operator .= '<option value="equal"'.$selected_equal.'>=</option>';
                $operator .= '<option value="empty"'.$selected_empty.'>Üres</option>';
                
            // selectek esetén csak = vagy üres operátort ajánlunk fel
            }elseif( in_array($ertek['type'], $selectek) ){
                
                $operator .= '<option value="equal"'.$selected_equal.'>=</option>';
                $operator .= '<option value="empty"'.$selected_empty.'>Üres</option>';
                
            // Általános esetben egy szabadszavas mező
            }else{
                
                //Numerikus adatok operátora
                if( strpos($ertek['class'], 'f_num')!==false || strpos($ertek['class'], 'datepicker')!==false){
                    $operator .= '<option value="equal"'.$selected_equal.'>=</option>';
                    if(strpos($ertek['class'], 'datepicker')!==false){
                        $operator .= '<option value="like"'.$selected_like.'>Tartalmazza</option>';
                    }
                    $operator .= '<option value="gteq"'.$selected_gteq.'>&gt;=</option>';
                    $operator .= '<option value="lteq"'.$selected_lteq.'>&lt;=</option>';
                    $operator .= '<option value="gt"'.$selected_gt.'>&gt;</option>';
                    $operator .= '<option value="lt"'.$selected_lt.'>&lt;</option>';
                    $operator .= '<option value="not_eq"'.$selected_not_eq.'>&lt;&gt;</option>';
                    $operator .= '<option value="empty"'.$selected_empty.'>Üres</option>';
                }else{
                    $operator .= '<option value="equal"'.$selected_equal.'>=</option>';
                    $operator .= '<option value="like"'.$selected_like.'>Tartalmazza</option>';
                    $operator .= '<option value="empty"'.$selected_empty.'>Üres</option>';
                }
                
            }
            break;
        }
    }
    
    // Kapcsolódó szállodák felsorolása
    if( $_POST['mezo']=='telephely_id' || ($_POST['tabla'] == 'login' && $_POST['mezo']=='szalloda') ){
        
        // Ilyenkor felülírva az operátorokat, csak egyenlőségjel van
        $operator = '<option value="equal"'.$selected_equal.'>=</option>';
        
    }
    
    // Megjegyzés esetén tartalmazza operátor + szabadszavas mező
    if($_POST['mezo']=='megjegyzes'){
        $operator .= '<option value="like"'.$selected_like.'>Tartalmazza</option>';
    }
    
    // Szakterület esetén = operátor + autocomplete
    if($_POST['mezo']=='szakterulet'){
        $operator .= '<option value="equal"'.$selected_equal.'>=</option>';
    }
    
    // Jogosultságnál, Státusznál és Mobilcsoportnál csak =
    if($_POST['mezo']=='jogosultsag' || $_POST['mezo']=='stat_id' || ( $_POST['mezo']=='mobil' && $_POST['tabla'] == 'munkalap' ) ){
        $operator = '<option value="equal"'.$selected_equal.'>=</option>';
    }
    
    // Operátor select bezárása
    $operator = '<select class="f_szuro_mezo_operator" data-tabla="'.$_POST['tabla'].'">'.$operator.'</select>';
    
    echo json_encode( array('operator' => $operator) );
    
    $weblap -> leallit();
    exit;
}

// Feltétel mező kigenerálása operátor váltáskor történik
if (isset($_POST['f_szuro_mezo_operator'])) {

    $akt_tomb_szures = get_akt_tomb($_POST['tabla']);
    
    $selected_null = '';
    $selected_egy  = '';
    if($_POST['felt']=='0'){
        $selected_null = ' selected';
    }
    if($_POST['felt']=='1'){
        $selected_egy = ' selected';
    }
    
    if($_POST['felt']=='undefined'){
        $_POST['felt'] = '';
    }
    
    // Ha az éppen kiválasztott operátor az ures, akkor az alábbit adjuk vissza és kész
    if($_POST['selected']=='empty'){
        $feltetel = nl2('
            <select class="f_felt">
                <option value="1"'.$selected_egy.'>Igen</option>
                <option value="0"'.$selected_null.'>Nem</option>
            </select>
            ');
    
    // Ha nem az üres operátort választottuk ki
    }else{
    
        foreach ($akt_tomb_szures as $ertek){
            if($ertek['name']==$_POST['mezo']){

                // Checkbox esetén Igen/Nem
                if($ertek['type']=='checkbox'){

                    $feltetel = nl2('
                        <select class="f_felt">
                            <option value="1"'.$selected_egy.'>Igen</option>
                            <option value="0"'.$selected_null.'>Nem</option>
                        </select>
                        ');


                // Autocomplete esetén a switch.php alapján gyártunk egy inputot
                }elseif($ertek['type']=='autocomplete'){

                    require_once('../class/html/class.Input.php');
                    $input = new Input('text');

                    $input -> value = $_POST['felt'];
                    
                    // Onblurra a hidden mező is nullázódjon, ha üres volna a mező
                    $ertek['onblur'] .= 'if(!$(this).val()){ $(\'#'.$ertek['name'].'\').val(\'\'); }';
                    
                    if($ertek['alert']){
                        $ertek['class'] .= ' csillag';
                    }
                    
                    $input -> cssClass      = $ertek['class'].' f_felt';
                    $input -> data          = 'data-auto="1"';
                    $input -> id            = $ertek['name'].'_autocomp_szurobe';
                    $input -> name          = $ertek['name'].'_autocomp_szurobe';
                    $input -> placeholder   = $ertek['placeholder'];
                    $input -> autocomplete  = 'off';
                    // Ha van szélesség beállítva, akkor azt hozzuk ide is át
                    if(strpos($ertek['style'], 'width')!==false){
                        $input -> style = $ertek['style'];
                    // egyébként 30rem-es
                    }else{
                        $input -> style = 'width: 30rem';
                    }

                    $kulso_val = $input -> value;
                    if( !$kulso_val ) {
                        $kulso_val = 0;
                    }

                    $csoport_where = '';
                    if( $ertek['csoport'] ) {
                        $csoport_where = ' and csoport='.$ertek['csoport'];
                    }

                    // Lekérdezzük a külső tábla értékét
                    $kulso_ertek = $adatbazis_kapcsolat->s($ertek['listaz'], $ertek['tabla'], $ertek['oszlop_keres'].'='.$kulso_val.$csoport_where, '', '1');
                    if( $kulso_ertek ) {
                        $input -> value = $kulso_ertek[0][$ertek['listaz']];
                    }

                    $feltetel = $input -> getHtml().'<input type="hidden" name="'.$ertek['name'].'_szurobe" id="'.$ertek['name'].'_szurobe" value="'.$_POST['felt'].'">';

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

                    $feltetel .= nl2('
                        <script>
                            $(document).ready(function() {
                                $(\'#'.$ertek['name'].'_autocomp_szurobe\').autocomplete({
                                    \'php_fajl\'        : \'../autocomplete.php\',
                                    \'tabla\'           : \''.$ertek['tabla'].'\',
                                    \'oszlop_keres\'    : \''.$ertek['oszlop_keres_autocomp'].'\',
                                    \'oszlop_listaz\'   : \''.$ertek['listaz'].'\',
                                    \'rkat\'            : '.$rkat.',
                                    \'csoport\'         : '.$csoport.',
                                    \'id_kiir\'         : \''.$id_kiir.'\',
                                    \'szuros\'          : \'Szűrőben hívtuk meg\'
                                });
                            });
                        </script>
                    ');

                // select_szemelyzet
                }elseif($ertek['type']=='select_szemelyzet'){
                    
                    require_once('../class/html/class.Select.php');
                    require_once('selectek.php');
                    
                    $input = new Select($select_szemelyzet, $_POST['felt']);
                    $input -> cssClass = 'f_felt';
                    $feltetel = $input -> getHtml();

                // select_szemelyzet
                }elseif($ertek['type']=='select_cikltip'){
                    
                    require_once('../class/html/class.Select.php');
                    require_once('selectek.php');

                    $input = new Select($select_cikltip, $_POST['felt']);
                    $input -> cssClass = 'f_felt';
                    $feltetel = $input -> getHtml();

                // select_mobilcsoport
                }elseif($ertek['type']=='select_mobilcsoport'){
                    
                    require_once('../class/html/class.Select.php');
                    require_once('selectek.php');

                    $input = new Select($select_mobilcsoport, $_POST['felt']);
                    $input -> cssClass = 'f_felt';
                    $feltetel = $input -> getHtml();

                // select_pri_tipus
                }elseif($ertek['type']=='select_pri_tipus'){
                    
                    require_once('../class/html/class.Select.php');
                    require_once('selectek.php');

                    $input = new Select($select_pri_tipus, $_POST['felt']);
                    $input -> cssClass = 'f_felt';
                    $feltetel = $input -> getHtml();
                    
                // select_pri_besorol
                }elseif($_POST['mezo']=='mobil'){
                    
                    require_once('../class/html/class.Select.php');
                    require_once('selectek.php');

                    $input = new Select($select_mobilcsoport, $_POST['felt']);
                    $input -> cssClass = 'f_felt';
                    $feltetel = $input -> getHtml();
                    
                // select_pri_besorol
                }elseif($ertek['type']=='select_pri_besorol'){
                    
                    require_once('../class/html/class.Select.php');
                    require_once('selectek.php');

                    $input = new Select($select_pri_besorol, $_POST['felt']);
                    $input -> cssClass = 'f_felt';
                    $feltetel = $input -> getHtml();
                    
                // Általános esetben egy szabadszavas mező
                }else{

                    // Numerikus adatok operátora
                    if( strpos($ertek['class'], 'f_num')!==false){
                        $feltetel = '<input type="text" placeholder="Érték..." class="f_num f_felt" value="'.$_POST['felt'].'">';
                    
                    // Dátum mezők (mindig csak dátum pontos)
                    }elseif( strpos($ertek['class'], 'datepicker')!==false ||  strpos($ertek['class'], 'datetimepicker')!==false ){
                        $feltetel = nl2('
                            <input type="text" placeholder=" . . " class="datepicker f_felt" value="'.$_POST['felt'].'">
                            <script>$(\'.datepicker\').datetimepicker({
                                                            timepicker          : false,
                                                            closeOnDateSelect   : true,
                                                            mask                : \'\\xa0\\xa0\\xa0\\xa0.\\xa0\\xa0.\\xa0\\xa0\',
                                                            format              :\'Y.m.d\',
                                                            validateOnBlur      : false
                                                        });
                            </script>
                        ');
                    }else{
                        $feltetel = '<input type="text" placeholder="Feltétel..." class="f_felt" value="'.$_POST['felt'].'">';
                    }

                }
                break;
            }
        }

        // Kapcsolódó szállodák felsorolása
        if( $_POST['mezo']=='telephely_id' || ($_POST['tabla']=='login' && $_POST['mezo']=='szalloda' ) ){

            $feltetel = '<select class="f_felt">';
            $feltetel .= '<option value="">-Válasszon szállodát-</option>';
            $telephelyek = $adatbazis_kapcsolat->s('id nev', 'telephely', trim(get_akt_login_szallodai('sql', 'id'), ' AND '), 'nev');
            foreach($telephelyek as $telephely){
                $selected = '';
                if($telephely['id']==$_POST['felt']){
                    $selected = ' selected';
                }
                $feltetel .= '<option value="'.$telephely['id'].'"'.$selected.'>'.$telephely['nev'].'</option>';
            }
            $feltetel .= '</select>';

        }
        
        // Megjegyzés esetén tartalmazza operátor + szabadszavas mező
        if($_POST['mezo']=='megjegyzes'){

            $feltetel = nl2('
                    <input type="text" placeholder="Feltétel..." class="f_felt" value="'.$_POST['felt'].'">
                ');

        }
        
        // Szakterület esetén = operátor + autocomplete
        if($_POST['mezo']=='szakterulet'){

            $ertek['tabla']                 = 'erteklista';
            $ertek['oszlop_keres']          = 'recid';
            $ertek['oszlop_keres_autocomp'] = 'info';
            $ertek['listaz']                = 'info';
            $ertek['csoport']               = ERTEKLISTA_SZAKTER;
            
            require_once('../class/html/class.Input.php');
            $input = new Input('text');

            $input -> value = $_POST['felt'];

            // Onblurra a hidden mező is nullázódjon, ha üres volna a mező
            $ertek['onblur'] .= 'if(!$(this).val()){ $(\'#'.$ertek['name'].'\').val(\'\'); }';

            if($ertek['alert']){
                $ertek['class'] .= ' csillag';
            }

            $input -> cssClass      = $ertek['class'].' f_felt';
            $input -> data          = 'data-auto="1"';
            $input -> id            = $ertek['name'].'_autocomp_szurobe';
            $input -> name          = $ertek['name'].'_autocomp_szurobe';
            $input -> placeholder   = $ertek['placeholder'];
            $input -> autocomplete  = 'off';

            $kulso_val = $input -> value;
            if( !$kulso_val ) {
                $kulso_val = 0;
            }

            $csoport_where = '';
            if( $ertek['csoport'] ) {
                $csoport_where = ' and csoport='.$ertek['csoport'];
            }

            // Lekérdezzük a külső tábla értékét
            $kulso_ertek = $adatbazis_kapcsolat->s($ertek['listaz'], $ertek['tabla'], $ertek['oszlop_keres'].'='.$kulso_val.$csoport_where, '', '1');
            if( $kulso_ertek ) {
                $input -> value = $kulso_ertek[0][$ertek['listaz']];
            }

            $feltetel = $input -> getHtml().'<input type="hidden" name="'.$ertek['name'].'_szurobe" id="'.$ertek['name'].'_szurobe" value="'.$_POST['felt'].'">';

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

            $feltetel .= nl2('
                <script>
                    $(document).ready(function() {
                        $(\'#'.$ertek['name'].'_autocomp_szurobe\').autocomplete({
                            \'php_fajl\'        : \'../autocomplete.php\',
                            \'tabla\'           : \''.$ertek['tabla'].'\',
                            \'oszlop_keres\'    : \''.$ertek['oszlop_keres_autocomp'].'\',
                            \'oszlop_listaz\'   : \''.$ertek['listaz'].'\',
                            \'rkat\'            : '.$rkat.',
                            \'csoport\'         : '.$csoport.',
                            \'id_kiir\'         : \''.$id_kiir.'\',
                            \'szuros\'          : \'Szűrőben hívtuk meg\'
                        });
                    });
                </script>
            ');

        }
        
        // Jogosultság esetén
        if($_POST['mezo']=='jogosultsag'){

            require_once 'jogosultsag.php';
            
            $feltetel = '<select class="f_felt">';
            
            $jog_kat_i = 0;
            foreach($jogosultsagKatok as $jog_kat_kulcs => $jog_kat){
                $feltetel .= '<optgroup label="'.$jog_kat['nev'].'">';
                foreach($jogosultsagok as $jog_sor => $jog){
                    if($jog['kat']==$jog_kat_kulcs){
                        
                        $selected = '';
                        if($jog_sor==$_POST['felt']){
                            $selected = ' selected';
                        }
                        $feltetel .= '<option value="'.$jog_sor.'"'.$selected.'>'.$jog['nev'].'</option>';
                    }
                }
                $feltetel .= '</optgroup>';
                $jog_kat_i++;
            }
            $feltetel .= '</select>';

        }
        
        // Státusz esetén
        if($_POST['mezo']=='stat_id'){
            
            $feltetel = '<select class="f_felt">';
            $feltetel .= '<option value="">-Válasszon státuszt-</option>';
            foreach($_stat_text[$_POST['tabla']] as $kulcs => $statusz){
                $selected = '';
                if($_POST['felt']==$kulcs){
                    $selected = ' selected';
                }
                $feltetel .= '<option value="'.$kulcs.'"'.$selected.'>'.$statusz['nev'].'</option>';
            }
            $feltetel .= '</select>';

        }
        
    }
    
    echo json_encode( array('feltetel' => $feltetel) );
    
    $weblap -> leallit();
    exit;
}


// Melyik oldalon vagyunk: annak megfelelő tömbbel dolgozunk. Szűrhető mezők listája.
$tabla = str_replace('.php', '', basename(self()));
$akt_tomb_szures = get_akt_tomb($tabla);

// A szűrési mezőket egy tömbbe vesszük fel, mert a 2. felében lesz az optionmegnevezése, ami alapján rendezzük a listát
$sz_mezo = array();
foreach($akt_tomb_szures as $ertek){
    if($ertek['szuro']){
        if($ertek['megn_szurobe']){
            $ertek['megn'] = $ertek['megn_szurobe'];
        }
        $selected = '';
        if($ertek['name']==$_GET['mezo']){
            $selected = ' selected';
        }
        array_push( $sz_mezo, array('eleje' => '<option value="'.$ertek['name'].'"'.$selected.'>', 'vege' => trim($ertek['megn'], ':').'</option>' ) );
    }
}

// Kapcsolódó táblák szűrő mezői
if($tabla == 'anyagigenyles'){
    $selected_megj  = '';
    if($_GET['mezo']=='megjegyzes'){
        $selected_megj = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="megjegyzes" data-tabla="megjegyzes"'.$selected_megj.'>', 'vege' => 'Megjegyzés</option>' ) );
}
if($tabla == 'login'){
    $selected_jog   = '';
    $selected_szall = '';
    $selected_megj  = '';
    if($_GET['mezo']=='jogosultsag'){
        $selected_jog = ' selected';
    }
    if($_GET['mezo']=='szalloda'){
        $selected_szall = ' selected';
    }
    if($_GET['mezo']=='megjegyzes'){
        $selected_megj = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="jogosultsag"'.$selected_jog.'>',                         'vege' => 'Jogosultság</option>'    ) );
    array_push( $sz_mezo, array('eleje' => '<option value="szalloda" data-tabla="telephely"'.$selected_szall.'>',   'vege' => 'Szálloda</option>'       ) );
    array_push( $sz_mezo, array('eleje' => '<option value="megjegyzes" data-tabla="megjegyzes"'.$selected_megj.'>', 'vege' => 'Megjegyzés</option>'     ) );
}
if($tabla == 'szallito'){
    $selected_szakter   = '';
    $selected_megj      = '';
    if($_GET['mezo']=='szakterulet'){
        $selected_szakter = ' selected';
    }
    if($_GET['mezo']=='megjegyzes'){
        $selected_megj = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="szakterulet"'.$selected_szakter.'>',                     'vege' => 'Szakterület</option>') );
    array_push( $sz_mezo, array('eleje' => '<option value="megjegyzes" data-tabla="megjegyzes"'.$selected_megj.'>', 'vege' => 'Megjegyzés</option>' ) );
}

// HTML előlállítása
$prefix         = '';
$szurve         = '';
if($_SESSION['o_szuro_feltetel']){
    $szurve     = ' piros';
}
$osszetett_link = '<span onclick="csoportMuvelet(\'osszetett_szuro\')"><img class="link'.$szurve.' f_svg" src="../elemek/filter.svg"></span>';
if(isset($_POST['o_szures'])){
    $prefix         = 'o_';
    $osszetett_link = '';
}

// Tehát a 'vege' alapján rendezzük az option-okat, így lesz a szűrés lista ABC-s rendben.
$options = '';
$sz_mezo = tobbdim_tomb_kulcs_rendez($sz_mezo, 'vege', SORT_ASC);
foreach($sz_mezo as $mezo){
    $options .= $mezo['eleje'].$mezo['vege'];
}
$szures = nl2('
        '.$osszetett_link.'
        <select id="'.$prefix.'szuro_mezo" class="f_szuro_mezo" data-tabla="'.$tabla.'">
            <option value="">- Szűrési mező -</option>
           '.$options.'
        </select>
        <div id="'.$prefix.'operator" class="dib"></div>
        <div id="'.$prefix.'feltetel" class="dib"></div>
        <div id="'.$prefix.'szures_submit" class="dib"></div>
    ');

if (isset($_GET['mezo'])) {
    $szures .= nl2('
        <script>
        $(document).ready(function () {
            operator_beallit($(\'.f_szuro_mezo\'), \''.$_GET['op'].'\', \''.addslashes($_GET['felt']).'\');
        });
        </script>
        ');
}
?>