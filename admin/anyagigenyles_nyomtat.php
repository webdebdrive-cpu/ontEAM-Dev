<?php

// Ha nyomtatunk, akkor pár adatot rögtön ki is számolunk
if( isset($_GET['nyomtat']) ) {
    $adatok              = base64_decode($_GET['nyomtat']);
    list($sorszam, $idk) = explode('|', $adatok);
    $idk                 = json_decode($idk, true);
    $idk_impl            = implode(',', $idk);
    $session_user        = $_SESSION[get_db_tabla().'user'];

// Ha hiányzik egy PDF, akkor kézzel így tudod létrehozni:
if( isset($_GET['teszt']) ) {
    
    $anyagrendeles_idja = 18671;    // Ide a az anyagrendelés recid-ját add meg
    $session_user       = '';       // Ide azt a User nevet add meg, amit a PDF alján a dátumnál látni szeretnél. Ha üres, akkor az anyagrendelés loginnévben írt user lesz felhasználva
    // Futtasd le ezt a PDF előállításához:
    // https://www.onteam.hu/admin/anyagigenyles.php?nyomtat=&szall&html_mutat=1&teszt
    
    $rendeles           = $adatbazis_kapcsolat->s2('rndid loginnev', 'anyagigenyles', "`recid`='{$anyagrendeles_idja}'");
    $sorszam            = str_replace('/', '_', $rendeles['rndid']);        // Ide az anyagrendelés szorszámát add meg (ebből következik a PDF neve, azért kell)
    $idk                = array($anyagrendeles_idja, $anyagrendeles_idja);  // <= Szándékosan van kétszer felvéve, csak így működik!!!
    $idk_impl           = $anyagrendeles_idja;
    if( !$session_user ) {
        $session_user = $rendeles['loginnev'];
    }
}
    // Szállító kiszámítása
    $szallito = $adatbazis_kapcsolat->s2('szallito_id kcs_21', 'anyagigenyles', "`recid`='{$idk[1]}'");

    $kcs_21   = $szallito['kcs_21'];
    $penznem  = $adatbazis_kapcsolat->s2('info', 'erteklista', "`csoport`='".ERTEKLISTA_PENZNEM."' AND `id`='{$kcs_21}'");
    $szallito = $adatbazis_kapcsolat->s2('id nev knev tel tel2 email isz varos utca', 'szallito', "`id`='{$szallito['szallito_id']}'");

}

// Szállítói nyomtatási kép
if( isset($_GET['szall']) ) {

    if( $idk_impl ) {
        // Cikkenkénti összesítés
        $tetelek_html = '
                            <table class="border full" style="font-size: 12px ;margin: auto; margin-top: 10px; margin-bottom: 10px;border-collapse: collapse;">
                            <tr style="border-collapse: collapse;border: 1px solid #ddd;">
                                <th class="l" style="border-collapse: collapse;border: 1px solid #ddd; padding: 5px;vertical-align: top;">Név</th>
                                <th style="width: 20px;border-collapse: collapse;border: 1px solid #ddd; padding: 5px;vertical-align: top;" class="r">Mee</th>
                                <th style="width: 20px;border-collapse: collapse;border: 1px solid #ddd; padding: 5px;vertical-align: top;" class="r">Rendelt mennyi- ség</th>
                                <th style="width: 20px;border-collapse: collapse;border: 1px solid #ddd; padding: 5px;vertical-align: top;" class="r">Nettó rendelési egységár</th>
                                <th style="width: 60px;border-collapse: collapse;border: 1px solid #ddd; padding: 5px;vertical-align: top;" class="r">Nettó összesen ('.$penznem['info'].')</th>
                            </tr>
                        ';

        // Anyagonként járjuk be az anyagigényléseket, összesítve a darabszámot
        $ossz     = 0;
        $anyagok = $adatbazis_kapcsolat->s('distinct cikk_id, rndear', 'anyagigenyles', "`recid` IN (".$idk_impl.")");

        $egysegar_mezo  = 'rndear';
        $egysegar_tized = 0;
        if( $kcs_21 != KCS_21_HUF ) {
            $egysegar_mezo  = 'vrndear';
            $egysegar_tized = 2;
        }
        
        foreach ( $anyagok as $anyag ) {

            $rndmen = 0;
            $anyag_lek = $adatbazis_kapcsolat->s('nev cikkmee rndmen '.$egysegar_mezo, 'anyagigenyles', "`recid` IN (".$idk_impl.") AND `cikk_id`='{$anyag['cikk_id']}' AND `rndear`='{$anyag['rndear']}'");
            foreach($anyag_lek as $a_lek){
                $rndmen += $a_lek['rndmen'];
            }

            $tetelek_html .= '
                            <tr style="border-collapse: collapse;border: 1px solid #ddd;">
                                <td class="b" style="border-collapse: collapse;border: 1px solid #ddd; padding: 5px;">'.$a_lek['nev'].'</td>
                                <td class="c" style="border-collapse: collapse;border: 1px solid #ddd; padding: 5px;">'.$a_lek['cikkmee'].'</td>
                                <td class="r" style="white-space: nowrap; border-collapse: collapse;border: 1px solid #ddd; padding: 5px;">'.number_format($rndmen, 2, ',', ' ').'</td>
                                <td class="r" style="white-space: nowrap; border-collapse: collapse;border: 1px solid #ddd; padding: 5px;">'.number_format($a_lek[$egysegar_mezo], 2, ',', ' ').'</td>
                                <td class="r" style="white-space: nowrap; border-collapse: collapse;border: 1px solid #ddd; padding: 5px;">'.number_format($rndmen*$a_lek[$egysegar_mezo], $egysegar_tized, ',', ' ').'</td>
                            </tr>
                            ';
            $ossz += $rndmen*$a_lek[$egysegar_mezo];
        }
        $tetelek_html .= '
                        <tr>
                            <td style="border: 0"></td>
                            <td style="border: 0"></td>
                            <td style="border: 0; padding: 5px;" class="r b" colspan="2">Mindösszesen:</td>
                            <td style="border: 0;padding: 5px; white-space: nowrap;" class="r b">'.number_format($ossz, $egysegar_tized, ',', ' ').'</td>
                        </tr>
                        </table>
                        ';

    }

    // Lényegi tartalom
    $tartalom = '
        <div class="padd" style="padding-bottom: 0">

                '.get_nyomtatas_fejlec($szallito).'

                <div style="font-size: 14px; margin: 20px 0 3px 0;">
                    Tisztelt szállító!
                </div>
                <div style="font-size: 12px; margin: 0 0 2px 0;">
                    <b>A szállító levelet vagy a számla másolatát minden esetben hagyja az áru átvevőjénél és a számlán tüntesse fel megrendelőnk számát, ellenkező esetben a számlát nem áll módunkban befogadni!</b>
                </div>
                <div style="font-size: 13px; margin: 0 0 8px 0;">
                    Megrendeljük Önöktől az alábbi anyagokat.
                </div>

                <div style="page-break-inside: avoid;">
                    <div class="i" style="font-size: 13px">I. Cikkenkénti összesítés:</div>
                    '.$tetelek_html.'
                </div>
                <div style="page-break-inside: avoid;">
                    <div class="i" style="font-size: 13px">II. Szállítási címenkénti összesítés:</div>
                    '.szallitasi_cimenkenti_lista($idk_impl).'
                    <div style="font-size: 12px; margin: 30px 0 3px 0;">
                        Üdvözlettel,
                        <br>Budapest, '.date_formaz(date('Y-m-d')).'
                    </div>
                    '.get_nyomtatas_alairasok().'
                </div>
            </div>
        </div>
        ';

    $keret = get_nyomtatas_keret($sorszam.' Megrendelés a szállítónak');
    $html  = str_replace('{TARTALOM}', $tartalom, $keret);

if( isset($_GET['html_mutat']) ) {
    echo $html;
}

    // Így tudod kikapcsolni a warningokat és hibaüzeneteket:
    // error_reporting(0); 
    include('../mpdf/mpdf.php');

    $mit = array('<script src="../js/jquery.js"></script>', '<link href="../css/nyomtatas.css" rel="stylesheet" type="text/css">');
    $html = str_replace($mit, '', $html);

    $mpdf = new mPDF('utf-8');

    $mpdf -> AddPage('P',     '',   '', '', '', '10', '10', '28', '20', '5', '5');
    $mpdf -> nbpgPrefix = '/ ';
    $header = get_nyomtatas_fejlec_top($sorszam, '-lapszámmal-');
    $footer = '<div class="dtc r" style="font-size: 7px">
        '.$session_user.'
        '.date('Y.m.d H:i:s').'
    </div>';
    $mpdf ->SetHTMLHeader( $header, '', true);
    $mpdf ->SetHTMLFooter( $footer, '', true);

    $mpdf -> WriteHTML( $html );
    // Így tudod egy PDF fájlba elmenteni a generált tartalmat
    // Könyvtárak kialakítása
    if( !is_dir('../'.get_utvonal().'rendeles') ) {
        mkdir('../'.get_utvonal().'rendeles');
    }
    if( !is_dir('../'.get_utvonal().'rendeles/'.date('y')) ) {
        mkdir('../'.get_utvonal().'rendeles/'.date('y'));
    }
    if( !is_dir('../'.get_utvonal().'rendeles/'.date('y').'/'.$szallito['id']) ) {
        mkdir('../'.get_utvonal().'rendeles/'.date('y').'/'.$szallito['id']);
    }

    $mpdf -> Output(iconv('UTF-8', 'Windows-1250', '../'.get_utvonal().'rendeles/'.date('y').'/'.$szallito['id'].'/'.str_replace('/', '_', $sorszam).'.pdf'), 'F');
if( isset($_GET['teszt']) ) {        
    echo 'Fájl ide létrehozva: '.get_utvonal().'rendeles/'. date('y').'/'.$szallito['id'].'/'.str_replace('/', '_', $sorszam).'.pdf';
    exit;
}
    // Így tudod megnyitni  a PDF-et (olyan mintha egy PDF kiterjesztésű fájlra hivatkoznál)
    if( !isset($_GET['html_mutat']) ) {
        $mpdf -> Output();
    }

    $weblap -> leallit();
    exit;
}

// Címenkénti nyomtatási kép
if (isset($_GET['cim'])) {

    $keret = get_nyomtatas_keret($sorszam.' Szállítási címenkénti összesítés');
    $telephelyek = $adatbazis_kapcsolat->s('distinct telephely_id', 'anyagigenyles', "`recid` IN (".$idk_impl.")");
    foreach($telephelyek as $telephely){

        $telephely = $adatbazis_kapcsolat->s2('id nev', 'telephely', "`id`='{$telephely['telephely_id']}'");

        if($idk_impl){

            // Cikkenkénti összesítés
            $tetelek_html = '   <pagebreak />
                                <table class="border full" style="font-size: 12px ;margin: auto; margin-top: 10px; margin-bottom: 10px">
                                <tr>
                                    <th>Cikk</th>
                                    <th>Név</th>
                                    <th>Mee</th>
                                    <th>Rendelt mennyiség</th>
                                    <th>Nettó rendelési egységár</th>
                                    <th>Nettó összesen ('.$penznem['info'].')</th>
                                    <th style="width: 200px">Leírás</th>
                                </tr>
                            ';

            // Anyagonként járjuk be az anyagigényléseket, összesítve a darabszámot
            $ossz = 0;
            $anyagok = $adatbazis_kapcsolat->s('distinct cikk_id', 'anyagigenyles', "`recid` IN (".$idk_impl.") AND `telephely_id`='{$telephely['id']}'");
            foreach($anyagok as $anyag){

                $rndmen = 0;
                $anyag_lek = $adatbazis_kapcsolat->s('cikk_id nev cikkmee rndmen rndear igleir', 'anyagigenyles', "`recid` IN (".$idk_impl.") AND `cikk_id`='{$anyag['cikk_id']}' AND `telephely_id`='{$telephely['id']}'");
                foreach($anyag_lek as $a_lek){
                    $rndmen += $a_lek['rndmen'];
                }
                $tetelek_html .= '
                                <tr>
                                    <td>'.$a_lek['cikk_id'].'</td>
                                    <td>'.$a_lek['nev'].'</td>
                                    <td class="c">'.$a_lek['cikkmee'].'</td>
                                    <td class="r" style="white-space: nowrap;">'.number_format($rndmen, 2, ',', ' ').'</td>
                                    <td class="r" style="white-space: nowrap;">'.number_format($a_lek['rndear'], 2, ',', ' ').'</td>
                                    <td class="r" style="white-space: nowrap;">'.number_format($rndmen*$a_lek['rndear'], 0, ',', ' ').'</td>
                                    <td>'.htmlspecialchars_decode(nl2br2($a_lek['igleir'])).'</td>
                                </tr>
                                ';
                $ossz += $rndmen*$a_lek['rndear'];
            }
            $tetelek_html .= '
                            <tr>
                                <td></td>
                                <td class="b">Mindösszesen:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="r b" style="white-space: nowrap;">'.number_format($ossz, 0, ',', ' ').'</td>
                                <td></td>
                            </tr>
                            </table>
                            ';


            // Lényegi tartalom
            $tartalom = '
                '.get_nyomtatas_fejlec_top($sorszam).'
                <div class="padd minh">

                        <div class="f16 c" style="margin: 20px 0 3px 0;">
                            '.$telephely['nev'].' részére
                        </div>
                        '.get_nyomtatas_fejlec($szallito).'

                        '.$tetelek_html.'

                        <div class="lap_alja">Budapest,  '.date_formaz(date('Y-m-d')).'</div>
                    </div>
                </div>
                    ';

            echo str_replace('{TARTALOM}', $tartalom, $keret);
        }

    }

    $weblap -> leallit();
    exit;
}

// Oracle cikkszám nyomtatási kép
if( isset($_GET['oracle']) ) {

    $keret = get_nyomtatas_keret($sorszam.' Oracle cikkszámonkénti összesítés');
    $telephelyek = $adatbazis_kapcsolat->s('distinct telephely_id', 'anyagigenyles', "`recid` IN (".$idk_impl.")");
    foreach($telephelyek as $telephely){

        $telephely = $adatbazis_kapcsolat->s2('id nev', 'telephely', "`id`='{$telephely['telephely_id']}'");

        if($idk_impl){

            // Cikkenkénti összesítés
            $tetelek_html = '
                                <table class="border full" style="font-size: 12px ;margin: auto; margin-top: 10px; margin-bottom: 10px">
                                <tr>
                                    <th>Oracle cikkszám</th>
                                    <th>Nettó összesen ('.$penznem['info'].')</th>
                                </tr>
                            ';

            // Anyagonként járjuk be az anyagigényléseket, összesítve a darabszámot
            $ossz = 0;
            $anyagok = $adatbazis_kapcsolat->s('distinct oracikkid', 'anyagigenyles', "`recid` IN (".$idk_impl.") AND `telephely_id`='{$telephely['id']}'");
            foreach ( $anyagok as $anyag ) {
                $rndmen   = 0;
                $ossz_tmp = 0;
                $anyag_lek = $adatbazis_kapcsolat->s('cikk_id nev cikkmee rndmen rndear', 'anyagigenyles', "`recid` IN (".$idk_impl.") AND `oracikkid`='{$anyag['oracikkid']}' AND `telephely_id`='{$telephely['id']}'");
                foreach ( $anyag_lek as $a_lek ) {
                    $ossz_tmp += $a_lek['rndmen']*$a_lek['rndear'];
                }
                $ossz += $ossz_tmp;

                $tetelek_html .= '
                                <tr>
                                    <td>'.$anyag['oracikkid'].'</td>
                                    <td class="r">'.number_format($ossz_tmp, 0, ',', ' ').'</td>
                                </tr>
                                ';
            }
            $tetelek_html .= '
                            <tr>
                                <td class="b">Mindösszesen:</td>
                                <td class="r b" style="white-space: nowrap;">'.number_format($ossz, 0, ',', ' ').'</td>
                            </tr>
                            </table>
                            ';


            // Lényegi tartalom
            $tartalom = '
                '.get_nyomtatas_fejlec_top($sorszam).'
                <div class="padd minh">

                        <div class="f16 c" style="margin: 20px 0 3px 0;">
                            '.$telephely['nev'].' részére
                            <br>Oracle cikkszámonként összesítve
                        </div>
                        '.get_nyomtatas_fejlec($szallito).'

                        '.$tetelek_html.'

                        <div class="lap_alja">Budapest,  '.date_formaz(date('Y-m-d')).'</div>
                    </div>
                </div>
                    ';

            echo str_replace('{TARTALOM}', $tartalom, $keret);
        }

    }

    $weblap -> leallit();
    exit;
}

function szallitasi_cimenkenti_lista($idk_impl, $szalloda = ''){

    global $adatbazis_kapcsolat;

    $sql_kieg = '';
    if( $szalloda ) {
        $sql_kieg = ' AND `telephely_id`=\''.$szalloda.'\'';
    }

    // Szállítási címenkénti összesítés
    $szallcim_html = '
                        <table class="border full" style="font-size: 12px ;margin: auto; margin-top: 10px; margin-bottom: 10px;border-collapse: collapse;">
                    ';

    // Van egy fix cím
    $telephely = $adatbazis_kapcsolat->s2('nev props', 'telephely', "`id`='".NYOMTATAS_FIX_CIM."'");

    $szn    = get_props($telephely['props'], 'SZN');
    $cim    = get_props($telephely['props'], 'cim');
    $szkcim = get_props($telephely['props'], 'SZKCIM');
    $fix_cim  = $szkcim.' '.$cim;

    if( $idk_impl ) {
        $anyagok = $adatbazis_kapcsolat->s('distinct telephely_id', 'anyagigenyles', "`recid` IN (".$idk_impl."){$sql_kieg}");
        foreach($anyagok as $anyag){

            $igenyles = $adatbazis_kapcsolat->s2('erkcimid', 'anyagigenyles', "`recid` IN (".$idk_impl."){$sql_kieg} AND `telephely_id`='{$anyag['telephely_id']}'");

            $telephely = $adatbazis_kapcsolat->s2('nev props', 'telephely', "`id`='{$anyag['telephely_id']}'");

            $szn = get_props($telephely['props'], 'SZN');
            $szl = get_props($telephely['props'], 'SZLACIM');
            $cim = get_props($telephely['props'], 'cim');

            // erkcimid mező értéke alapján változik a szállítási cím
            $szall_cim = '';
            switch ( $igenyles['erkcimid'] ) {
                case 1:
                    // Aktuális szálloda SZN+CÍM+ha szálloda=2032,” Szondi utcai teherporta”
                    $szall_cim .= $szn.' '.$cim;
                    if( $anyag['telephely_id'] == NYOMTATAS_KIVETELES_CIM ) {
                        $szall_cim .= ' Szondi utcai teherporta';
                    }
                break;
                case 2:
                    $szall_cim .= $fix_cim;
                break;
                case 3:
                    $szall_cim .= 'MSzK fuvar';
                break;
            }
            
            $adoszam = NYOMTATAS_ADOSZAM;
            if( $anyag['telephely_id'] == TELEPHELY_GUNDEL ) {
                $adoszam = NYOMTATAS_ADOSZAM_GUNDEL;
            }
            
            $szallcim_html .= '
                            <tr style="border-collapse: collapse;border: 1px solid #ddd; border-bottom: 0;">
                                <td class="b" style="border: 1px solid #ddd; padding: 5px; border-bottom: 0; padding-bottom: 0; marign: 0; vertical-align: top;">
                                    Szállítási cím:
                                </td>
                                <td style="border: 1px solid #ddd; padding: 5px; border-bottom: 0; padding-bottom: 0; marign: 0;" colspan="2">
                                    Danubius Hotels Zrt. '.$szall_cim.'
                                </td>
                            </tr>
                            <tr style="border-collapse: collapse; border: 1px solid #ddd; border-top: 0; border-bottom: 0; marign: 0;">
                                <td class="b" style="border: 1px solid #ddd; padding: 5px; border-top: 0; border-bottom: 0; padding-top: 0;padding-bottom: 0; marign: 0; vertical-align: top;">
                                    Számlázási cím:
                                </td>
                                <td style="border: 1px solid #ddd; padding: 5px; border-top: 0; border-bottom: 0; padding-top: 0; padding-bottom: 0;" colspan="2">
                                    '.$szl.' '.$cim.'
                                </td>
                            </tr>
                            <tr style="border-collapse: collapse;border: 1px solid #ddd; border-top: 0; border-bottom: 0;">
                                <td class="b" style="border: 1px solid #ddd; padding: 5px; border-top: 0; border-bottom: 0; padding-top: 0; padding-bottom: 0;">
                                    Adószám:
                                </td>
                                <td style="border: 1px solid #ddd; padding: 5px;  border-top: 0; border-bottom: 0; padding-top: 0; padding-bottom: 0;" colspan="2">
                                    '.$adoszam.'
                                </td>
                            </tr>
                            <tr style="border-collapse: collapse; border: 1px solid #ddd; border-top: 0;">
                                <td class="b" style="border: 1px solid #ddd; padding: 5px; border-top: 0; padding-top: 0;">
                                    Számlaküldési cím:
                                </td>
                                <td style="border: 1px solid #ddd; padding: 5px; border-top: 0; padding-top: 0;" colspan="2">
                                    '.NYOMTATAS_SZLA_KULD_CIM.'
                                </td>
                            </tr>
                            ';


            // Szedjük össze az azonos cikkeket, ami adott telephelyhez tartozik, majd számoljuk ki, hány darab kell belőle
            $anyag_lek = $adatbazis_kapcsolat->s('DISTINCT `cikk_id`, `igleir`', 'anyagigenyles', "`recid` IN (".$idk_impl.") AND `telephely_id`='{$anyag['telephely_id']}'");
            foreach ( $anyag_lek as $a_lek ) {

                $rndmen = 0;
                $a_lek['igleir'] = addslashes($a_lek['igleir']);
                $anyag_lek_db = $adatbazis_kapcsolat->s('nev cikkmee rndmen rndear igleir', 'anyagigenyles', "`recid` IN (".$idk_impl.") AND `telephely_id`='{$anyag['telephely_id']}' AND `cikk_id`='{$a_lek['cikk_id']}' AND `igleir`='{$a_lek['igleir']}'");
                foreach ( $anyag_lek_db as $a_lek_db ) {
                    $rndmen += $a_lek_db['rndmen'];
                }

                $szallcim_html .= '
                                <tr style="border-collapse: collapse;border: 1px solid #ddd;">
                                    <td class="vat c" style="border: 1px solid #ddd; padding: 5px;">'.number_format($rndmen, 2, ',', '.').' '.$a_lek_db['cikkmee'].'</td>
                                    <td class="vat" style="border: 1px solid #ddd; padding: 5px;">'.$a_lek_db['nev'].'</td>
                                    <td class="vat" style="border: 1px solid #ddd; padding: 5px;">'. htmlspecialchars_decode(nl2br2($a_lek_db['igleir'])) .'</td>
                                </tr>
                                ';
            }

            // Telephelyek után egy kis elválasztó
            $szallcim_html .= '
                            <tr >
                                <td colspan="3" style="border: 1px solid #ddd; padding: 7px; border-left: 0; border-right: 0; border-bottom: 0"></td>
                            </tr>
                            ';
        }
    }
    $szallcim_html .= '
                        </table>
                    ';

    return $szallcim_html;
}
