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

    // Összetett szűréskor annak a címkéjét írjuk ki: trükk: beágyazzuk rejtve az összetett szűrést, és ugyanazt a JS-el kiszámolt mondatatot írtajuk ki
    if($_SESSION['o_szuro_feltetel']){
        $feltetel  = '<script src="../js/wdb_admin_min.js"></script>';
        $feltetel .= '<script src="../js/wdb_admin_async_min.js"></script>';
        $feltetel .= '<div class="dn">'.fajlbol_olvas('../sablon/admin/popup/osszetett_szuro.html', 1).'</div>';
        $feltetel .= 'Szűrő feltételek: <b id="szuro_feltetel"></b>';
    }


    // FB elétrejestzett lista nyomtatásakor a lekérdezésen még állítottunk
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

    if( $_SESSION['nyomtatas_ids'] ) {
        if( strstr($_SESSION['akt_sql'], 'WHERE') ) {
            $_SESSION['akt_sql'] = str_replace('WHERE', "WHERE `". get_db_tabla()."$tabla`.`recid` IN (".$_SESSION['nyomtatas_ids'].") AND ", $_SESSION['akt_sql']);
        } else {
            $_SESSION['akt_sql'] = str_replace('ORDER', "WHERE `". get_db_tabla()."$tabla`.`recid` IN (".$_SESSION['nyomtatas_ids'].") ORDER", $_SESSION['akt_sql']);
        }
    }

    if( $_GET['type'] == 'egysegarak_lista' ) {
        if( strstr($_SESSION['akt_sql'], 'WHERE') ) {
            $_SESSION['akt_sql'] = str_replace('WHERE', "WHERE `recid` = '".$_GET['id']."' AND ", $_SESSION['akt_sql']);
        } else {
            $_SESSION['akt_sql'] = str_replace('ORDER', "WHERE `recid` = '".$_GET['id']."' ORDER", $_SESSION['akt_sql']);
        }
    }

    $sorok = array();
    $query = mysqli_query($db_kapcs, $_SESSION['akt_sql']);
    while($sor = mysqli_fetch_assoc($query)){
        array_push($sorok, $sor);
    }

    // Bal oldali navpanel kiíratása
    switch($tabla){
//        case 'anyagigenyles'        : $akt_tomb = $anyagigenyles_tomb;          break;
        case 'bejelentes'           : $sorok_html = bejelentesLista( $sorok, $tabla );      break;
//        case 'beosztas'             : $akt_tomb = $beosztas_tomb;               break;
//        case 'cikktorzs'            : $akt_tomb = $cikktorzs_tomb;              break;
//        case 'eszkozkezeles'        : $akt_tomb = $eszkozkezeles_tomb;          break;
        case 'login'                : $sorok_html = loginLista( $sorok, $tabla ); $akt_tomb = $login_tomb;  break;
        case 'ktghely'              : $sorok_html = ktghelyLista( $sorok, $tabla );         break;
        case 'ktgnem'               : $sorok_html = ktgnemLista( $sorok, $tabla );          break;
        case 'eloterj_fb'           : $sorok_html = eloterj_fbLista( $sorok, $tabla );      break;
        case 'eloterj_bod'          : $sorok_html = eloterj_bodLista( $sorok, $tabla );     break;
        case 'munkalap'             : $sorok_html = munkalapLista( $sorok, $tabla );        break;
        case 'energia_terv'         : $sorok_html = energia_tervLista( $sorok, $tabla );    break;
        case 'fenntartasi_terv'     : $sorok_html = fenntartasi_tervLista( $sorok, $tabla );break;
//        case 'utemezett_feladatok'  : $akt_tomb = $utemezett_feladatok_tomb;    break;
        case 'torzsadat'            : $sorok_html = torzsadatLista($sorok, $tabla);         break;
        case 'projektigenyles'      : $sorok_html = projektigenylesLista( $sorok, $tabla ); break;
        case 'szallito'             : $sorok_html = szallitoLista( $sorok, $tabla );        break;
        case 'szerepkor'            : $sorok_html = szerepkorLista( $sorok, $tabla );       break;
    }

    // Energia tervek egységár listájának nyomtatási képe
    if(basename(self())=='energia_terv.php'){
        if( $_GET['type'] == 'egysegarak_lista' ) {
            $sorok_html .= '
                <table class="szamla_cont_tabla nolink fejlec_padd szep_csikos kisbetu">
                <thead>
                <tr>
                    <th class="egys_szall"><span>Szállodák</span></th>
                    <th class="egys_szall"><span>Díjelem</span></th>
                    <th><span>Ktgnem kód</span></th>
                    <th><span>Ktgnem név</span></th>
                    <th><span>Mee</span></th>
                    <th><span>Egységár Január</span></th>
                    <th class="egys_dev"><span>Dev. Jan.</span></th>
                    <th><span>Árfolyam Január</span></th>
                    <th><span>Egységár Február</span></th>
                    <th class="egys_dev"><span>Dev. Feb</span></th>
                    <th><span>Árfolyam Február</span></th>
                    <th><span>Egységár Március</span></th>
                    <th class="egys_dev"><span>Dev. Márc.</span></th>
                    <th><span>Árfolyam Március</span></th>
                    <th><span>Egységár Április</span></th>
                    <th class="egys_dev"><span>Dev. Ápr.</span></th>
                    <th><span>Árfolyam Április</span></th>
                    <th><span>Egységár Május</span></th>
                    <th class="egys_dev"><span>Dev. Máj.</span></th>
                    <th><span>Árfolyam Május</span></th>
                    <th><span>Egységár Június</span></th>
                    <th class="egys_dev"><span>Dev. Jún.</span></th>
                    <th><span>Árfolyam Június</span></th>
                    <th><span>Egységár Július</span></th>
                    <th class="egys_dev"><span>Dev. Júl.</span></th>
                    <th><span>Árfolyam Július</span></th>
                    <th><span>Egységár Augusztus</span></th>
                    <th class="egys_dev"><span>Dev. Aug.</span></th>
                    <th><span>Árfolyam Augusztus</span></th>
                    <th><span>Egységár Szeptember</span></th>
                    <th class="egys_dev"><span>Dev. Szept.</span></th>
                    <th><span>Árfolyam Szeptember</span></th>
                    <th><span>Egységár Október</span></th>
                    <th class="egys_dev"><span>Dev. Okt.</span></th>
                    <th><span>Árfolyam Október</span></th>
                    <th><span>Egységár November</span></th>
                    <th class="egys_dev"><span>Dev. Nov.</span></th>
                    <th><span>Árfolyam November</span></th>
                    <th><span>Egységár December</span></th>
                    <th class="egys_dev"><span>Dev. Dec</span></th>
                    <th><span>Árfolyam December</span></th>
                </tr>
                </thead>
                <tbody>
                ';

            $egysegarak = $adatbazis_kapcsolat->s('*', 'energia_terv_ear', "`energia_terv_id`='{$_GET['id']}'");
            foreach($egysegarak as $egysegar){

                // Szálloda nevek
                $szalloda_html = '';
                $szallodak = explode(',', $egysegar['telephely_idk']);
                $szallodak = $adatbazis_kapcsolat->s('nev', 'telephely', "`id` IN ('".implode('\', \'', $szallodak)."')");
                $szallodak = adatbazis2tomb($szallodak, 'nev');

                // ABC sorrend
                sort($szallodak);

                // Szálloda html
                $szalloda_html = implode(', ', $szallodak);

                // Ktgnem
                $ktgnem = $adatbazis_kapcsolat->s2('id nev', 'ktgnem', "`id`='{$egysegar['ktgnem_id']}'");

                $sorok_html .= '
                    <tr id="tr_ear_'.$egysegar['recid'].'" data-recid="'.$egysegar['recid'].'">
                        <td>'.$szalloda_html.'</td>
                        <td>'.$egysegar['dijelem'].'</td>
                        <td>'.$ktgnem['id'].'</td>
                        <td>'.$ktgnem['nev'].'</td>
                        <td>'.$egysegar['mee'].'</td>
                        <td class="r">'.$egysegar['ear01'].'</td>
                        <td>'.erteklista(ERTEKLISTA_PENZNEM, $egysegar['dnem01']).'</td>
                        <td class="r">'.$egysegar['arf01'].'</td>
                        <td class="r">'.$egysegar['ear02'].'</td>
                        <td>'.erteklista(ERTEKLISTA_PENZNEM, $egysegar['dnem02']).'</td>
                        <td class="r">'.$egysegar['arf02'].'</td>
                        <td class="r">'.$egysegar['ear03'].'</td>
                        <td>'.erteklista(ERTEKLISTA_PENZNEM, $egysegar['dnem03']).'</td>
                        <td class="r">'.$egysegar['arf03'].'</td>
                        <td class="r">'.$egysegar['ear04'].'</td>
                        <td>'.erteklista(ERTEKLISTA_PENZNEM, $egysegar['dnem04']).'</td>
                        <td class="r">'.$egysegar['arf04'].'</td>
                        <td class="r">'.$egysegar['ear05'].'</td>
                        <td>'.erteklista(ERTEKLISTA_PENZNEM, $egysegar['dnem05']).'</td>
                        <td class="r">'.$egysegar['arf05'].'</td>
                        <td class="r">'.$egysegar['ear06'].'</td>
                        <td>'.erteklista(ERTEKLISTA_PENZNEM, $egysegar['dnem06']).'</td>
                        <td class="r">'.$egysegar['arf06'].'</td>
                        <td class="r">'.$egysegar['ear07'].'</td>
                        <td>'.erteklista(ERTEKLISTA_PENZNEM, $egysegar['dnem07']).'</td>
                        <td class="r">'.$egysegar['arf07'].'</td>
                        <td class="r">'.$egysegar['ear08'].'</td>
                        <td>'.erteklista(ERTEKLISTA_PENZNEM, $egysegar['dnem08']).'</td>
                        <td class="r">'.$egysegar['arf08'].'</td>
                        <td class="r">'.$egysegar['ear09'].'</td>
                        <td>'.erteklista(ERTEKLISTA_PENZNEM, $egysegar['dnem09']).'</td>
                        <td class="r">'.$egysegar['arf09'].'</td>
                        <td class="r">'.$egysegar['ear10'].'</td>
                        <td>'.erteklista(ERTEKLISTA_PENZNEM, $egysegar['dnem10']).'</td>
                        <td class="r">'.$egysegar['arf10'].'</td>
                        <td class="r">'.$egysegar['ear11'].'</td>
                        <td>'.erteklista(ERTEKLISTA_PENZNEM, $egysegar['dnem11']).'</td>
                        <td class="r">'.$egysegar['arf11'].'</td>
                        <td class="r">'.$egysegar['ear12'].'</td>
                        <td>'.erteklista(ERTEKLISTA_PENZNEM, $egysegar['dnem12']).'</td>
                        <td class="r">'.$egysegar['arf12'].'</td>
                    </tr>
                    ';
            }

            $sorok_html .= nl2('
                    </tbody>
                </table>
            ');
        }
        
    // Fenntartási tervek egységár listájának nyomtatási képe
    }elseif(basename(self())=='fenntartasi_terv.php'){
        if( $_GET['type'] == 'egysegarak_lista' ) {
            $sor = $adatbazis_kapcsolat->s2('*', 'fenntartasi_terv', "`recid`='{$_GET['id']}'");
            $ful_tomb = array(
                'energia'       => 'Energia',
                'muszaki_anyag' => 'Műszaki anyag',
                'rendsz_karban' => 'Rendszeres karbantartás',
                'eseti_karban'  => 'Eseti karbantartás',
                'hibaelharitas' => 'Hibaelhárítás',
                'miniprojekt'   => 'Miniprojekt',
                'bwh'           => 'Cukrászat BWH',
                'osszesen'      => 'Összesen',
            );
            
            $tomb = fenntartasi_terv_energia($sor);
            $sorok_html .= '<h2 class="f14">Energia</h2>'.$tomb[0];
            $sorok_html .= '<h2 class="f14" style="margin-top: 3rem">Műszaki anyag</h2>'.fenntartasi_terv_muszaki_anyag($sor);
            $sorok_html .= '<h2 class="f14" style="margin-top: 3rem">Rendszeres karbantartás</h2>'.fenntartasi_terv_rendsz_karban($sor);
            $sorok_html .= '<h2 class="f14" style="margin-top: 3rem">Eseti karbantartás</h2>'.fenntartasi_terv_eseti_karban($sor);
            $sorok_html .= '<h2 class="f14" style="margin-top: 3rem">Hibaelhárítás</h2>'.fenntartasi_terv_hibaelharitas($sor);
            $sorok_html .= '<h2 class="f14" style="margin-top: 3rem">Miniprojekt</h2>'. fenntartasi_terv_miniprojekt($sor);
            $sajat_szallodak = get_akt_login_szallodai();
            if(in_array(2120, $sajat_szallodak) && $sor['telephely_id']==2120){
                $sorok_html .= '<h2 class="f14" style="margin-top: 3rem">BWH</h2>'.fenntartasi_terv_bwh($sor);
            }
            $sorok_html .= '<h2 class="f14" style="margin-top: 3rem">Összesen</h2>'.fenntartasi_terv_osszesen($sor, $ful_tomb, $sajat_szallodak);
        }
    }

    if( !isset($_GET['pdf']) ) {
        $html = nl2('
            <link href="../css/nullaz.css" rel="stylesheet" type="text/css">
            <link href="../css/admin.css" rel="stylesheet" type="text/css">
            <link href="../css/nyomtatas.css" rel="stylesheet" type="text/css">
            <script src="../js/jquery.js"></script>
            <script src="../js/main.js"></script>
            <script src="../js/fenntartasi_terv.js"></script>
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
//var_dump($_SESSION['fajl']['konyvtar']);
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
