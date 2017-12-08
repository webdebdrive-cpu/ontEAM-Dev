<?php
require_once '../weblap.php';

if( $_GET['print']=='lista' || isset($_GET['pdf'])) {

    require 'selectek.php';
    $tabla = str_replace('.php', '', basename(self()));

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

        // Státusz szövegesen jelenjen meg
        if ($_GET['mezo']=='stat_id') {
            $feltetel .= getStatText($tabla, $_GET['felt']);
        }elseif ($tabla=='projektigenyles' && $_GET['mezo']=='tipus') {
            foreach($select_pri_tipus as $tipus){
                if($tipus['value']==$_GET['felt']){
                    $feltetel .= $tipus['name'];
                    break;
                }
            }
        }elseif ($tabla=='projektigenyles' && $_GET['mezo']=='besorol') {
            foreach($select_pri_besorol as $tipus){
                if($tipus['value']==$_GET['felt']){
                    $feltetel .= $tipus['name'];
                    break;
                }
            }
        }elseif ($_GET['mezo']=='szemelyzet_id') {
            foreach($select_szemelyzet as $tipus){
                if($tipus['value']==$_GET['felt']){
                    $feltetel .= $tipus['name'];
                    break;
                }
            }
        }else{
            $feltetel .= $_GET['felt'];
        }

        if($_GET['ksz']){
            $feltetel .= ' Tovább szűkítve a következő keresőszóval: '.$_GET['ksz'];
        }
        $feltetel = 'Szűrő feltételek: <b>'.$feltetel.'</b>';
    }

    //Összetett szűréskor annak a címkéjét írjuk ki: trükk: beágyazzuk rejtve az összetett szűrést, és ugyanazt a JS-el kiszámolt mondatatot írtajuk ki
    if($_SESSION['o_szuro_feltetel']){
        $feltetel  = '<script src="../js/wdb_admin_min.js"></script>';
        $feltetel .= '<script src="../js/wdb_admin_async_min.js"></script>';
        $feltetel .= '<div class="dn">'.fajlbol_olvas('../sablon/admin/popup/osszetett_szuro.html', 1).'</div>';
        $feltetel .= 'Szűrő feltételek: <b id="szuro_feltetel"></b>';
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
    switch($tabla){
//        case 'anyagigenyles'        : $akt_tomb = $anyagigenyles_tomb;          break;
        case 'bejelentes'           : $sorok_html = bejelentesLista( $sorok, $tabla );      break;
//        case 'beosztas'             : $akt_tomb = $beosztas_tomb;               break;
//        case 'cikktorzs'            : $akt_tomb = $cikktorzs_tomb;              break;
//        case 'eszkozkezeles'        : $akt_tomb = $eszkozkezeles_tomb;          break;
//        case 'login'                : $akt_tomb = $login_tomb;                  break;
        case 'eloterj_fb'           : $sorok_html = eloterj_fbLista( $sorok, $tabla );      break;
        case 'eloterj_bod'          : $sorok_html = eloterj_bodLista( $sorok, $tabla );     break;
        case 'munkalap'             : $sorok_html = munkalapLista( $sorok, $tabla );        break;
//        case 'utemezett_feladatok'  : $akt_tomb = $utemezett_feladatok_tomb;    break;
//        case 'torzsadat'            : $akt_tomb = $torzsadat_tomb;              break;
        case 'projektigenyles'      : $sorok_html = projektigenylesLista( $sorok, $tabla ); break;
    }

    if( !isset($_GET['pdf']) ) {
        $html = nl2('
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
        echo $html;

        $weblap -> leallit();
        exit;
    }

    if( isset($_GET['pdf']) ) {
        $sorok_html = preg_replace("/<i class=\"material-icons\">([a-z_]+)<\/i>/", "<img src=\"../elemek/ic_$1_black_24dp_2x.png\" class=\"icon\" width=\"18\">", $sorok_html);

        include('../html2pdf/mpdf.php');
        $mpdf = new mPDF('', '', 0, '', 15, 15, 15, 15, 8, 8);
        $mpdf -> WriteHTML(fajlbol_olvas('../css/nullaz.css'), 1);
        $mpdf -> WriteHTML('
            body {
                font-size: 14px;
            }

            table {
                border-bottom: 0.1rem solid #242424;
                width: 100%;
            }
        ', 1);
        $mpdf -> WriteHTML( '
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
        ', 2);

        $fajlnev = '../'.get_utvonal().$_SESSION['fajl']['konyvtar'];

        if( !is_dir($fajlnev) ) {
            mkdir($fajlnev, 0777);
        }

        $fajlnev = $fajlnev.'/'.date('ym');

        if( !is_dir($fajlnev) ) {
            mkdir($fajlnev, 0777);
        }

        $fajlnev = $fajlnev.'/'.$_SESSION[get_db_tabla().'teljes_nev'].'_'.date('Y_m_d_H_i_s').'.pdf';
        $mpdf -> Output($fajlnev);

        $disposition = 'attachment'; // 'attachment' vagy 'inline'
    	$mimeType = 'application/octet-stream';

        $fileName = $_SESSION[get_db_tabla().'teljes_nev'].'_'.date('Y_m_d_H_i_s').'.pdf';

        header('Content-Type: '.$mimeType);
    	header('Content-Disposition:'.$disposition.'; filename="'.trim(htmlspecialchars($fileName)).'"');
    	header('Content-Description: '.trim(htmlentities($fileName)));
    	header('Content-Length: '.(string)(filesize($fajlnev)));
    	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    	header('Connection: close');
    	readfile($fajlnev);

        header('Location: '.$_SESSION['fajl']['konyvtar'].'.php');
    }
}
?>
