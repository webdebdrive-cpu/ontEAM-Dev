<?php
require_once '../weblap.php';

if ($_GET['print']=='lista') {
    
    if ($_GET['mezo']) {
        $feltetel = '';
        $akt_tomb_szures = get_akt_tomb(trim(basename(self()), '.php'));
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
    
    
    $sorok = array();
    $query = mysqli_query($db_kapcs, $_SESSION['akt_sql']);    
    while($sor = mysqli_fetch_assoc($query)){
        array_push($sorok, $sor);
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
    <div class="padd" style="padding-bottom: 5rem">
        '.$feltetel.'
        '.bejelentesLista( $sorok ).'
        
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
