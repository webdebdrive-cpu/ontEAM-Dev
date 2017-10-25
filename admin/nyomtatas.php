<?php
require_once '../weblap.php';

if ($_GET['print']=='lista') {
    
    if ($_GET['mezo']) {
        $feltetel = '';
        $akt_tomb_szures = get_akt_tomb(str_replace('.php', '', basename(self())));
        foreach ($akt_tomb_szures as $ertek){
            if($ertek['name']==$_GET['mezo']){
                $feltetel .= trim($ertek['megn'], ':');
                break;
            }
        }
        switch ($_GET['op']){
            case 'equal'    : $feltetel .= ' = ';           break;
            case 'like'     : $feltetel .= ' tartalmazza '; break;
            case 'gteq'     : $feltetel .= ' >= ';          break;
            case 'lteq'     : $feltetel .= ' <= ';          break;
            case 'gt'       : $feltetel .= ' > ';           break;
            case 'lt'       : $feltetel .= ' < ';           break;
            case 'not_eq'   : $feltetel .= ' <> ';          break;
            case 'empty': 
                if($_GET['felt']==0){
                    $feltetel .= ' nem üres';
                }else{
                    $feltetel .= ' üres';
                }
                $_GET['felt'] = '';
            break;
        }
        $feltetel .= $_GET['felt'];
        
        if($_GET['ksz']){
            $feltetel .= ' Tovább szűkítve a következő keresőszóval: '.$_GET['ksz'];
        }
        $feltetel = 'Szűrő feltételek: <b>'.$feltetel.'</b>';
    }
    
    
    //FB elétrejestzett lista nyomtatásakor a lekérdezésen még állítottunk
    if(isset($_GET['fb_elo'])){
        
        $mit   = array('WHERE',   'ORDER');
        $mivel = array('WHERE (', ') AND `stat_id`=\''.PRIGENY_STAT_FB_TERJESZT.'\' ORDER');
        $_SESSION['akt_sql'] = str_replace($mit, $mivel, $_SESSION['akt_sql']);
        $query = mysqli_query($db_kapcs, $_SESSION['akt_sql']);

        if(mysqli_num_rows($query)){
            $feltetel .= '<h1>FB elé terjesztve:</h1>';
        }else{
            $feltetel .= '<h1>Nem található FB elé terjesztett projektigénylés.</h1>';
        }
    }

    $sorok = array();
    $query = mysqli_query($db_kapcs, $_SESSION['akt_sql']);
    while($sor = mysqli_fetch_assoc($query)){
        array_push($sorok, $sor);
    }
    
    //Bal oldali navpanel kiíratása
    switch(str_replace('.php', '', basename(self()))){
//        case 'anyagigenyles'        : $akt_tomb = $anyagigenyles_tomb;          break;
        case 'bejelentes'           : $sorok_html = bejelentesLista( $sorok );      break;
//        case 'beosztas'             : $akt_tomb = $beosztas_tomb;               break;
//        case 'cikktorzs'            : $akt_tomb = $cikktorzs_tomb;              break;
//        case 'eszkozkezeles'        : $akt_tomb = $eszkozkezeles_tomb;          break;
//        case 'login'                : $akt_tomb = $login_tomb;                  break;
        case 'munkalap'             : $sorok_html = munkalapLista( $sorok );        break;
//        case 'utemezett_feladatok'  : $akt_tomb = $utemezett_feladatok_tomb;    break;
//        case 'torzsadat'            : $akt_tomb = $torzsadat_tomb;              break;
        case 'projektigenyles'      : $sorok_html = projektigenylesLista( $sorok ); break;
    }
    
    echo nl2('
<link href="../css/nullaz.css" rel="stylesheet" type="text/css">
<link href="../css/admin.css" rel="stylesheet" type="text/css">
<link href="../css/nyomtatas.css" rel="stylesheet" type="text/css">
<script src="../js/jquery.js"></script>
<img src="../elemek/admin/nyomtat.gif" alt="Nyomtat" class="nyomtat_ikon nyomtat_dn link" onclick="window.print();">
<title>Listanézet '.date('Y.m.d H:i:s').'</title>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<div class="a4_allo"> 
    <div class="padd" style="padding-bottom: 5rem; min-height: 60rem">
        '.$feltetel.'
        '.$sorok_html.'
        
    </div>
    <div class="footer">
        <div class="dtc padd">
            '.$_SESSION[get_db_tabla().'teljes_nev'].'
        </div>
        <div class="dtc r padd">
            '.date('Y.m.d H:i:s').'
        </div>
    </div>
</div>
            ');
    
    
    
    $weblap -> leallit();
    exit;
}
?>
