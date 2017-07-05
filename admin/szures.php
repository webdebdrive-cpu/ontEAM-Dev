<?php
/* Szűrés */
if (isset($_POST['f_szuro_mezo'])) {
    
    $akt_tomb_szures = get_akt_tomb($_POST['tabla']);
    
    $selected_equal = '';
    $selected_like = '';
    $selected_empty = '';
    if($_POST['selected']=='equal'){ $selected_equal = ' selected'; }
    if($_POST['selected']=='like') { $selected_like  = ' selected'; }
    if($_POST['selected']=='empty'){ $selected_empty = ' selected'; }
    
    $operator = '';
    //Feltétel összerakása tömbből
    foreach ($akt_tomb_szures as $ertek){
        if($ertek['name']==$_POST['mezo']){
            
            //Checkbox esetén Igen/Nem
            if($ertek['type']=='checkbox'){
                
                $operator .= '<option value="equal"'.$selected_equal.'>=</option>';
                
                
            //Autocomplete esetén a switch.php alapján gyártunk egy inputot
            }elseif($ertek['type']=='autocomplete'){
                
                $operator .= '<option value="equal"'.$selected_equal.'>=</option>';
                $operator .= '<option value="like"'.$selected_like.'>Tartalmazza</option>';
                $operator .= '<option value="empty"'.$selected_empty.'>Üres</option>';
                
            //beosztása esetén
            }elseif($ertek['type']=='select_szemelyzet'){
                
                $operator .= '<option value="equal"'.$selected_equal.'>=</option>';
                $operator .= '<option value="empty"'.$selected_empty.'>Üres</option>';
                
            //Általános esetben egy szabadszavas mező
            }else{
                
                //Numerikus adatok operátora
                if( strpos($ertek['class'], 'f_num')!==false || strpos($ertek['class'], 'datepicker')!==false){
                    $operator .= '<option value="equal"'.$selected_equal.'>=</option>';
                    $operator .= '<option value="&gt;=">&gt;=</option>';
                    $operator .= '<option value="&lt;=">&lt;=</option>';
                    $operator .= '<option value="&gt;">&gt;</option>';
                    $operator .= '<option value="&lt;">&lt;</option>';
                    $operator .= '<option value="&lt;&gt;">&lt;&gt;</option>';
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
    
    //Kapcsolódó szállodák felsorolása
    if( ($_POST['tabla'] == 'login' && $_POST['mezo']=='szalloda') || ($_POST['tabla'] == 'anyagigenyles' && $_POST['mezo']=='telephely_id') ){
        
        //Ilyenkor felülírva az operátorokat, csak egyenlőségjel van
        $operator = '<option value="equal"'.$selected_equal.'>=</option>';
        
    }
    
    //Megjegyzés esetén tartalmazza operátor + szabadszavas mező
    if($_POST['mezo']=='megjegyzes'){
        $operator .= '<option value="like"'.$selected_like.'>Tartalmazza</option>';
    }
    
    //Operátor select bezárása
    $operator = '<select class="f_szuro_mezo_operator" data-tabla="'.$_POST['tabla'].'">'.$operator.'</select>';
    
    echo json_encode( array('operator' => $operator) );
    
    $weblap -> leallit();
    exit;
}

/* Operátor váltáskor generáljuk ki a feltétel mezőt */
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
    
    //Ha az éppen kiválasztott operátor az ures, akkor az alábbit adjuk vissza és kész
    if($_POST['selected']=='ures'){
        $feltetel = nl2('
            <select class="f_felt">
                <option value="1"'.$selected_egy.'>Igen</option>
                <option value="0"'.$selected_null.'>Nem</option>
            </select>
            ');
    
    //Ha nem az üres operátort választottuk ki
    }else{
    
        foreach ($akt_tomb_szures as $ertek){
            if($ertek['name']==$_POST['mezo']){

                //Checkbox esetén Igen/Nem
                if($ertek['type']=='checkbox'){

                    $feltetel = nl2('
                        <select class="f_felt">
                            <option value="1"'.$selected_egy.'>Igen</option>
                            <option value="0"'.$selected_null.'>Nem</option>
                        </select>
                        ');


                //Autocomplete esetén a switch.php alapján gyártunk egy inputot
                }elseif($ertek['type']=='autocomplete'){

                    require_once('../class/html/class.Input.php');
                    $input = new Input('text');

                    $input -> value = $_POST['felt'];
                    
                    //Onblurra a hidden mező is nullázódjon, ha üres volna a mező
                    $ertek['onblur'] .= 'if(!$(this).val()){ $(\'#'.$ertek['name'].'\').val(\'\'); }';
                    
                    if($ertek['alert']){
                        $ertek['class'] .= ' csillag';
                    }
                    
                    $input -> cssClass      = $ertek['class'];
                    $input -> id            = $ertek['name'].'_autocomp_szurobe';
                    $input -> name          = $ertek['name'].'_autocomp_szurobe';
                    $input -> placeholder   = $ertek['placeholder'];
                    $input -> autocomplete  = 'off';
                    //Ha van szélesség beállítva, akkor azt hozzuk ide is át
                    if(strpos($ertek['style'], 'width')!==false){
                        $input -> style = $ertek['style'];
                    //egyébként 30rem-es
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

                    /* Lekérdezzük a külső tábla értékét */
                    $kulso_ertek = $adatbazis_kapcsolat->s($ertek['listaz'], $ertek['tabla'], $ertek['oszlop_keres'].'='.$kulso_val.$csoport_where, '', '1');
                    if( $kulso_ertek ) {
                        $input -> value = $kulso_ertek[0][$ertek['listaz']];
                    }

                    $feltetel = $input -> getHtml().'<input type="hidden" name="'.$ertek['name'].'_szurobe" id="'.$ertek['name'].'_szurobe" value="'.$value.'">';

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
                                    \'id_kiir\'         : \''.$id_kiir.'\'
                                });
                            });
                        </script>
                    ');

                //Autocomplete esetén a switch.php alapján gyártunk egy inputot
                }elseif($ertek['type']=='select_szemelyzet'){
                    
                    require_once('../class/html/class.Select.php');
                    require_once('selectek.php');
                    
                    $input = new Select($select_szemelyzet, $_POST['felt']);
                    $feltetel = $input -> getHtml();
                    
                //Általános esetben egy szabadszavas mező
                }else{

                    //Numerikus adatok operátora
                    if( strpos($ertek['class'], 'f_num')!==false){
                        $feltetel = '<input type="text" placeholder="Érték..." class="f_num f_felt" value="'.$_POST['felt'].'">';
                    }elseif( strpos($ertek['class'], 'datepicker')!==false){
                        $feltetel = nl2('
                            <input type="text" placeholder="____.__.__" class="datepicker f_felt" value="'.$_POST['felt'].'">
                            <script>$(\'.datepicker\').datetimepicker({
                                                            timepicker      : false,
                                                            mask            : true,
                                                            format          :\'Y.m.d\',
                                                            validateOnBlur  : false
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

        //Kapcsolódó szállodák felsorolása
        if( 
                ($_POST['tabla'] == 'login'         && $_POST['mezo']=='szalloda'       ) ||
                ($_POST['tabla'] == 'anyagigenyles' && $_POST['mezo']=='telephely_id'   ) ||
                ($_POST['tabla'] == 'beosztas'      && $_POST['mezo']=='telephely_id'   ) 
            ){

            $feltetel = '<select class="f_felt">';
            $feltetel .= '<option value="">-Válasszon szállodát-</option>';
            $telephelyek = $adatbazis_kapcsolat->s('id nev', 'telephely', "", 'nev');
            foreach($telephelyek as $telephely){
                $selected = '';
                if($telephely['id']==$_POST['felt']){
                    $selected = ' selected';
                }
                $feltetel .= '<option value="'.$telephely['id'].'">'.$telephely['nev'].'</option>';
            }
            $feltetel .= '</select>';

        }
        
        //Megjegyzés esetén tartalmazza operátor + szabadszavas mező
        if($_POST['mezo']=='megjegyzes'){

            $feltetel = nl2('
                    <input type="text" placeholder="Feltétel..." class="f_felt" value="'.$_POST['felt'].'">
                ');

        }
    }
    
    echo json_encode( array('feltetel' => $feltetel) );
    
    $weblap -> leallit();
    exit;
}

//echo $megn . '<br>';
$akt_tomb_szures = get_akt_tomb($megn);
$sz_mezo = '';
foreach($akt_tomb_szures as $ertek){
    if($ertek['szuro']){
        if($ertek['megn_szurobe']){
            $ertek['megn'] = $ertek['megn_szurobe'];
        }
        $selected = '';
        if($ertek['name']==$_GET['mezo']){
            $selected = ' selected';
        }
        $sz_mezo .= '<option value="'.$ertek['name'].'"'.$selected.'>'.trim($ertek['megn'], ':').'</option>';
    }
}

//Extra szűrő mezők
if($megn == 'anyagigenyles'){
    $sz_mezo .= '<option value="megjegyzes" data-tabla="megjegyzes">Megjegyzés</option>';
}
if($megn == 'login'){
    $sz_mezo .= '<option value="jogosultsag">Jogosultság</option>';
    $sz_mezo .= '<option value="szalloda" data-tabla="telephely">Szálloda</option>';
    $sz_mezo .= '<option value="megjegyzes" data-tabla="megjegyzes">Megjegyzés</option>';
}
$szures = nl2('
        <select class="f_szuro_mezo" data-tabla="'.$megn.'">
            <option value="">- Szűrési mező -</option>
           '.$sz_mezo.'
        </select>
        <div id="operator" class="dib"></div>
        <div id="feltetel" class="dib"></div>
        <div id="szures_submit" class="dib"></div>
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
