<?php
// Ha nyomtatunk, akkor pár adatot rögtön ki is számolunk
if( isset($_GET['nyomtat']) ) {

    $ml_recid       = $_GET['nyomtat'];
    $session_user   = $_SESSION[get_db_tabla().'user'];

    // Szállító kiszámítása
    $munkalap = $adatbazis_kapcsolat->s2('szallito_id kcs_21 telephely_id dok_db', 'munkalap', "`recid`='{$ml_recid}'");
    $ft_szerzodes = $adatbazis_kapcsolat -> s2('szszam insert_dt', 'fenntartasi_szerzodes', "`recid`='{$_SESSION['ml_megr_ft_recid']}'");

    $kcs_21   = $szallito['kcs_21'];
    $penznem  = $adatbazis_kapcsolat->s2('info', 'erteklista', "`csoport`='".ERTEKLISTA_PENZNEM."' AND `id`='{$kcs_21}'");
    $szallito = $adatbazis_kapcsolat->s2('id nev knev tel tel2 email isz varos utca', 'szallito', "`id`='{$munkalap['szallito_id']}'");
    
    $telephely = $adatbazis_kapcsolat->s2('nev props', 'telephely', "`id`='{$munkalap['telephely_id']}'");
    $szn    = get_props($telephely['props'], 'SZN');
    $cim    = get_props($telephely['props'], 'cim');
    $szkcim = get_props($telephely['props'], 'SZKCIM');
    $fix_cim  = $szkcim.' '.$cim;

    // Aktuális szálloda SZN+CÍM+ha szálloda=2032,” Szondi utcai teherporta”
    $szall_cim .= $szn.' '.$cim;
    if( $munkalap['telephely_id'] == NYOMTATAS_KIVETELES_CIM ) {
        $szall_cim .= ' Szondi utcai teherporta';
    }

    $adoszam = NYOMTATAS_ADOSZAM;
    if( $munkalap['telephely_id'] == TELEPHELY_GUNDEL ) {
        $adoszam = NYOMTATAS_ADOSZAM_GUNDEL;
    }

    // A megrendelő az a login (műszaki vezető) lesz, aki a ML-ot Döntésre vár státuszba tette
    $ml_statusz = $adatbazis_kapcsolat -> s2('login', 'munkalap_statusz', "`munkalap_recid`='{$ml_recid}' AND `stat_id`='".MUNKALAP_STAT_DONTESRE_VAR."'");
    $login      = $adatbazis_kapcsolat -> s2('recid', 'login', "`nev`='{$ml_statusz['login']}'");

    // Lényegi tartalom
    $tartalom = '
        <div class="padd" style="padding-bottom: 0">

                '.get_nyomtatas_fejlec($szallito, $login['recid']).'

                <div style="font-size: 12px; margin: 0 0 2px 0; padding-top: 10px">
                    '.nl2br2($_SESSION['ml_megr_text']).'
                </div>

                <table class="border full" style="font-size: 12px ;margin: auto; margin-top: 10px; margin-bottom: 10px;border-collapse: collapse;">
                    <tr style="border-collapse: collapse;border: 1px solid #ddd; border-bottom: 0;">
                        <td class="b" style="border: 1px solid #ddd; padding: 5px; border-bottom: 0; padding-bottom: 0; marign: 0; vertical-align: top;">
                            Munkavégzés helye:
                        </td>
                        <td style="border: 1px solid #ddd; padding: 5px; border-bottom: 0; padding-bottom: 0; marign: 0;" colspan="2">
                            '.$szall_cim.'
                        </td>
                    </tr>
                    <tr style="border-collapse: collapse; border: 1px solid #ddd; border-top: 0; border-bottom: 0; marign: 0;">
                        <td class="b" style="border: 1px solid #ddd; padding: 5px; border-top: 0; border-bottom: 0; padding-top: 0;padding-bottom: 0; marign: 0; vertical-align: top;">
                            Kivitelezési határidő:
                        </td>
                        <td style="border: 1px solid #ddd; padding: 5px; border-top: 0; border-bottom: 0; padding-top: 0; padding-bottom: 0;" colspan="2">
                            '.$_SESSION['ml_megr_hatarido'].'
                        </td>
                    </tr>
                    <tr style="border-collapse: collapse;border: 1px solid #ddd; border-top: 0; border-bottom: 0;">
                        <td class="b" style="border: 1px solid #ddd; padding: 5px; border-top: 0; border-bottom: 0; padding-top: 0; padding-bottom: 0;">
                            Ajánlat kelte:
                        </td>
                        <td style="border: 1px solid #ddd; padding: 5px;  border-top: 0; border-bottom: 0; padding-top: 0; padding-bottom: 0;" colspan="2">
                            '.$_SESSION['ml_megr_kelt'].'
                        </td>
                    </tr>
                    <tr style="border-collapse: collapse; border: 1px solid #ddd; border-top: 0;  border-bottom: 0;">
                        <td class="b" style="border: 1px solid #ddd; padding: 5px; border-top: 0; border-bottom: 0; padding-top: 0;">
                            Vállalási ár:
                        </td>
                        <td style="border: 1px solid #ddd; padding: 5px; border-top: 0; border-bottom: 0; padding-top: 0;  padding-bottom: 0;" colspan="2">
                            '.$_SESSION['ml_megr_ar'].'
                        </td>
                    </tr>
                    <tr style="border-collapse: collapse; border: 1px solid #ddd; border-top: 0;">
                        <td class="b" style="border: 1px solid #ddd; padding: 5px; border-top: 0; padding-top: 0;">
                            Fizetési feltétel:
                        </td>
                        <td style="border: 1px solid #ddd; padding: 5px; border-top: 0; padding-top: 0;" colspan="2">
                            átutalás 15 naptári nap fizetési határidővel
                        </td>
                    </tr>
                    <tr style="border-collapse: collapse;">
                        <td colspan="2" style="padding-top: 10px; border-left: 0; border-right: 0"></td>
                    </tr>
                    <tr style="border-collapse: collapse;border: 1px solid #ddd; border-bottom: 0;">
                        <td class="b" style="border: 1px solid #ddd; padding: 5px; border-bottom: 0; padding-bottom: 0; marign: 0; vertical-align: top;">
                            Szállítási cím:
                            <br>
                        </td>
                        <td style="border: 1px solid #ddd; padding: 5px; border-bottom: 0; padding-bottom: 0; marign: 0;" colspan="2">
                            Danubius Hotels Zrt.
                            <br> '.$szall_cim.'
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
                    <tr style="border-collapse: collapse; border: 1px solid #ddd; border-top: 0; border-bottom: 0; marign: 0;">
                        <td class="b" style="border: 1px solid #ddd; padding: 5px; border-top: 0; border-bottom: 0; padding-top: 0;padding-bottom: 0; marign: 0; vertical-align: top;">
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
                </table>

                <div style="page-break-inside: avoid;">
                    <div style="font-size: 12px; margin: 30px 0 3px 0;">
                        Üdvözlettel,
                        <br>Budapest, '.date_formaz(date('Y-m-d')).'
                    </div>
                    '.get_nyomtatas_alairasok().'
                </div>
            </div>
        </div>
        ';

    $keret = get_nyomtatas_keret('Megrendelés sz.: '.$_SESSION['ml_megr_ft_szerz']);
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
    $header = get_nyomtatas_fejlec_top($_SESSION['ml_megr_ft_szerz'], '-lapszámmal-');
    $footer = '<div class="dtc r" style="font-size: 7px">
        '.$session_user.'
        '.date('Y.m.d H:i:s').'
    </div>';
    $mpdf ->SetHTMLHeader( $header, '', true);
    $mpdf ->SetHTMLFooter( $footer, '', true);

    $mpdf -> WriteHTML( $html );
    // Így tudod egy PDF fájlba elmenteni a generált tartalmat
    // Könyvtárak kialakítása

    $ev           = date('y', strtotime($ft_szerzodes['insert_dt']));
    $szszam_mappa = str_replace('/', '_', $ft_szerzodes['szszam']);
    $fajlnev      = 'Munkamegrendeles_'.$szszam_mappa.'.pdf';
    if( !is_dir('../'.get_utvonal().'fenntartasi_szerzodes') ) {
        mkdir('../'.get_utvonal().'fenntartasi_szerzodes');
    }
    if( !is_dir('../'.get_utvonal().'fenntartasi_szerzodes/'.$ev) ) {
        mkdir('../'.get_utvonal().'fenntartasi_szerzodes/'.$ev);
    }
    if( !is_dir('../'.get_utvonal().'fenntartasi_szerzodes/'.$ev.'/'.$szszam_mappa) ) {
        mkdir('../'.get_utvonal().'fenntartasi_szerzodes/'.$ev.'/'.$szszam_mappa);
    }

    $mpdf -> Output(iconv('UTF-8', 'Windows-1250', '../'.get_utvonal().'fenntartasi_szerzodes/'.$ev.'/'.$szszam_mappa.'/'.$fajlnev), 'F');
// if( isset($_GET['teszt']) ) {        
//     echo 'Fájl ide létrehozva: '.get_utvonal().'fenntartasi_szerzodes/'.$szszam_mappa.'/'.$fajlnev;
//     exit;
// }

    $utvonal_db_be = $ev.'/'.$szszam_mappa.'/'.$fajlnev;

    // Dokumentum eltárolása adatbázisba
    $adatbazis_kapcsolat -> i('fenntartasi_szerzodes_dok', array('fenntartasi_szerzodes_id',    'utvonal',      'fajlnev',     'update_usr',                           'insert_dt',    'insert_usr'                            ),
                                                           array($_SESSION['ml_megr_ft_recid'], $utvonal_db_be, $fajlnev,      $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  )
                                                        );

    $adatbazis_kapcsolat -> u('fenntartasi_szerzodes', array('dok_db'), array(1), "`recid`='{$_SESSION['ml_megr_ft_recid']}'");

    
    // Munkalapok dokumentumához is adjuk hozzá
    $adatbazis_kapcsolat -> i('munkalap_dok',   array('munkalap_id',    'utvonal',                                  'fajlnev',     'update_usr',                           'insert_dt',    'insert_usr'                            ),
                                                array($ml_recid,        '../fenntartasi_szerzodes/'.$utvonal_db_be, $fajlnev,      $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  )
                                                        );
    $adatbazis_kapcsolat -> u('munkalap', array('dok_db'), array($munkalap['dok_db']+1), "`recid`='{$ml_recid}'");

    // Így tudod megnyitni  a PDF-et (olyan mintha egy PDF kiterjesztésű fájlra hivatkoznál)
    if( !isset($_GET['html_mutat']) ) {
        $mpdf -> Output();
    }

    $weblap -> leallit();
    exit;
}