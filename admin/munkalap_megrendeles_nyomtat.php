<?php

// Ha nyomtatunk, akkor pár adatot rögtön ki is számolunk
if( isset($_GET['nyomtat']) ) {

    // Végleges fájlba nyomtatáskor $_GET['id_nyomtatashoz']-t kapunk, előnézetkor $_GET['id']-t
    $ml_recid = $_GET['id_nyomtatashoz'];
    if( !$ml_recid ) {
        $ml_recid = $_GET['id'];
    }

    // Szállító kiszámítása
    $munkalap = $adatbazis_kapcsolat->s2('ikt_szam szallito_id kcs_21 telephely_id alair1 alair2 dok_db', 'munkalap', "`recid`='{$ml_recid}'");
    $ft_szerzodes = $adatbazis_kapcsolat -> s2('szszam insert_dt', 'fenntartasi_szerzodes', "`recid`='".($_SESSION['ml_megr_ft_recid'] ?? '')."'");
    $kcs_21  = $munkalap['kcs_21'];
    $penznem  = $adatbazis_kapcsolat->s2('info', 'erteklista', "`csoport`='".ERTEKLISTA_PENZNEM."' AND `id`='{$kcs_21}'");
    // $szallito = $adatbazis_kapcsolat->s2('id nev knev tel tel2 email isz varos utca', 'szallito', "`id`='{$munkalap['szallito_id']}'");
    
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

    // Számlázási cím és adószám: ha a telephelynél meg van adva eltérő számlázási cím (MEGR_SZLACIM), akkor soronként pontosan az
    // (az adószámot is tartalmazza, így külön Adószám sor nem kell), egyébként a Danubius Hotels Zrt. és a szálloda címe, alatta az adószám.
    $megr_szlacim = get_megr_szlacim_sorok($telephely['props']);
    if( $megr_szlacim ) {
        $szamlazasi_cim_html = implode('<br>', $megr_szlacim);
        $adoszam_html        = '';
    } else {
        $szamlazasi_cim_html = 'Danubius Hotels Zrt.<br> '.$szall_cim/*B verzió: $szl.' '.$cim*/;
        $adoszam_html        = '
                    <tr style="border-collapse: collapse; marign: 0;">
                        <td class="b" style="padding: 5px; padding-top: 0;padding-bottom: 0; marign: 0; vertical-align: top;">
                            Adószám:
                        </td>
                        <td style="padding: 5px; padding-top: 0; padding-bottom: 0;" colspan="2">
                            '.$adoszam.'
                        </td>
                    </tr>';
    }

    // Bevezető mondat: a telephelynél megadott eltérő szöveg (ML_MEGR_BEVEZETO), egyébként az alapértelmezett
    $bevezeto = trim((string)get_props($telephely['props'], 'ML_MEGR_BEVEZETO'));
    if( $bevezeto === '' ) {
        $bevezeto = 'A Danubius Hotels Zrt. (1051 Budapest, Szent István tér 11.) képviseletében megrendeljük Önöktől az alábbiakban részletezett munka elvégzését.';
    }

    // A megrendelő az a login (műszaki vezető) lesz, aki a ML-ot Döntésre vár státuszba tette
    $ml_statusz = $adatbazis_kapcsolat -> s2('login', 'munkalap_statusz', "`munkalap_recid`='{$ml_recid}' AND `stat_id`='".MUNKALAP_STAT_DONTESRE_VAR."'");
    $login      = $adatbazis_kapcsolat -> s2('recid', 'login', "`nev`='{$ml_statusz['login']}'");

    $jotallas_mondat = '';
    if( $_SESSION['ml_megr_jotallas'] ) {
        $jotallas_mondat = '<br>Vállalkozó a munka elvégzésére '.$_SESSION['ml_megr_jotallas'].' hónap jótállást vállal.';
    }

    // Lényegi tartalom
    $tartalom = '
        <div class="padd" style="padding-bottom: 0; margin-bottom: 0">

                '.get_nyomtatas_fejlec('', $login['recid'], $_SESSION['ml_megr_szallito_txt'], $_SESSION['ml_megr_megrendelo_txt']).'

                <div style="font-size: 12px; margin: 0 0 2px 0; padding-top: 10px">
                    Tisztelt Partnerünk!
                    <br>
                    <br>'.$bevezeto.'
                    <br>
                </div>

                <table class="border full" style="font-size: 12px ;margin: auto; margin-top: 10px; margin-bottom: 10px;border-collapse: collapse;">
                    <tr style="border-collapse: collapse;">
                        <td class="b" style="padding: 5px; padding-bottom: 0; marign: 0; vertical-align: top;">
                            Megrendelés tárgya:
                        </td>
                        <td style="padding: 5px; padding-bottom: 0; marign: 0;" colspan="2">
                            <b>'.$_SESSION['ml_megr_text'].'</b>
                        </td>
                    </tr>
                    <tr style="border-collapse: collapse;">
                        <td class="b" style="padding: 5px; padding-top: 0; padding-bottom: 0;">
                            Kapcs. ajánlat száma:
                        </td>
                        <td style="padding: 5px; padding-top: 0; padding-bottom: 0;" colspan="2">
                            '.$_SESSION['ml_kapcs_ajanlat'].'
                        </td>
                    </tr>
                    <tr style="border-collapse: collapse;">
                        <td class="b" style="padding: 5px; padding-top: 0; padding-bottom: 0;">
                            Kapcs. ajánlat kelte:
                        </td>
                        <td style="padding: 5px; padding-top: 0; padding-bottom: 0;" colspan="2">
                            '.$_SESSION['ml_megr_kelt'].'
                        </td>
                    </tr>
                    <tr style="border-collapse: collapse;">
                        <td class="b" style="padding: 5px; padding-top: 0; padding-bottom: 0;">
                            ontEAM munkalap száma:
                        </td>
                        <td style="padding: 5px; padding-top: 0; padding-bottom: 0;" colspan="2">
                            '.$_SESSION['ml_megr_ikt_szam'].'
                        </td>
                    </tr>
                    <tr style="border-collapse: collapse;">
                        <td class="b" style="padding: 5px; padding-bottom: 0; marign: 0; vertical-align: top;">
                            Munkavégzés helye:
                        </td>
                        <td style="padding: 5px; padding-bottom: 0; marign: 0;" colspan="2">
                            '.$szall_cim.'
                        </td>
                    </tr>
                    <tr style="border-collapse: collapse;">
                        <td class="b" style="padding: 5px; padding-top: 10px;">
                            Vállalási ár:
                        </td>
                        <td style="padding: 5px; padding-top: 10px; padding-bottom: 0;" colspan="2">
                            <b>'.$_SESSION['ml_megr_ar'].'</b>
                        </td>
                    </tr>
                    <tr style="border-collapse: collapse; marign: 0;">
                        <td class="b" style="padding: 5px; padding-top: 0; padding-bottom: 0; marign: 0; vertical-align: top;">
                            Kivitelezési határidő:
                        </td>
                        <td style="padding: 5px; padding-top: 0; padding-bottom: 0;" colspan="2">
                            '.$_SESSION['ml_megr_kiv_hat'].'
                        </td>
                    </tr>
                    <tr style="border-collapse: collapse;">
                        <td class="b" style="padding: 5px; padding-top: 10px; vertical-align: top;">
                            Fizetési feltételek:
                        </td>
                        <td style="padding: 5px; padding-top: 10px;" colspan="2">
                            A teljesítést követően a Vállalkozó által kiállított számla ellenében, banki átutalással a számla kézhezvételétől számított 30 naptári napos fizetési határidővel. A számla mellékletét képezi a Megrendelő által aláírt teljesítésigazolás.
                            <br>Kérjük a számlán tüntessék fel a munkavégzés helyét és a megrendelés számát.
                        </td>
                    </tr>
                    <tr style="border-collapse: collapse;">
                        <td colspan="2" style="padding-top: 10px;"></td>
                    </tr>
                    <tr style="border-collapse: collapse;">
                        <td class="b" style="padding: 5px; padding-bottom: 0; marign: 0; vertical-align: top;">
                            Számlázási cím:
                        </td>
                        <td style="padding: 5px; padding-bottom: 0; marign: 0;" colspan="2">
                            '.$szamlazasi_cim_html.'
                        </td>
                    </tr>
                    '.$adoszam_html.'
                    <tr style="border-collapse: collapse;">
                        <td class="b" style="padding: 5px; vertical-align: top; padding-top: 10px;"> 
                            Számlaküldési cím:
                        </td>
                        <td style="padding: 5px; padding-top: 10px;" colspan="2">
                            Megrendelő az ÁFA törvény 175. § (3) bekezdés b) pontjában előírtaknak megfelelően ezen beleegyezik, hogy részére elektronikus számla kerüljön kiállítása, melyet a Vállalkozó a Megrendelő alábbi e-mail címére köteles megküldeni:
                            <br><b>'.NYOMTATAS_SZLA_KULD_CIM.'</b>
                        </td>
                    </tr>
                </table>

                <div style="font-size: 12px; margin: 0 0 2px 0; padding-top: 10px">
                    <b>A megrendelés egyéb feltételei:</b>
                    <br>A jelen megrendelés tárgyát képező munkavégzés megkezdésével Felek a munkaterületet a Vállalkozó részére átadottnak tekintik, melyre tekintettel valamennyi tűz-, munka- és balesetvédelmi előírás betartása és betartatása a Vállalkozó kötelezettsége és felelőssége.
                    A Vállalkozó jelen bekezdés szerinti kötelezettsége valamennyi olyan személyre és társaságra is kiterjed, akik a Vállalkozó alvállalkozójaként a munkaterületen tartózkodnak.
                    '.$jotallas_mondat.'
                    <br>
                    <br>A jelen megrendelés a Vállalkozó fent hivatkozott ajánlatával együtt érvényes, a megrendelés a Vállalkozó ajánlatának elfogadásának minősül azzal, hogy a munka megkezdésével a Vállalkozó a jelen megrendelésben foglalt feltételeket az egymás közötti jogviszonyukban kötelezőnek fogadja el.
                </div>

                <div style="page-break-inside: avoid;">
                    <div style="font-size: 12px; margin: 30px 0 3px 0;">
                        Üdvözlettel,
                        <br>Budapest, '.date_formaz(date('Y-m-d')).'
                    </div>
                    '.get_nyomtatas_alairasok($munkalap['alair1'], $munkalap['alair2'], '-ml_megr-').'
                </div>
            </div>
        </div>
        ';

    $keret = get_nyomtatas_keret('Megrendelés száma: <br>'.$_SESSION['ml_megr_ft_szerz']);
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

    $mpdf -> AddPage('P',     '',   '', '', '', '10', '10', '20', '10', '5', '5');
    $mpdf -> nbpgPrefix = '/ ';
    $header = get_nyomtatas_fejlec_top($_SESSION['ml_megr_ft_szerz'], '-lapszámmal-');
    $_SESSION['ml_megr_ft_szerz'] = '';
    $footer = '<div class="dtc r" style="font-size: 7px">
        '.$_SESSION[get_db_tabla().'user'].'
        '.date('Y.m.d H:i:s').'
    </div>';
    $mpdf ->SetHTMLHeader( $header, '', true);
    $mpdf ->SetHTMLFooter( $footer, '', true);

    $mpdf -> WriteHTML( $html );
    
    // Adatbázis műveleteket csak akkor csinálunk, ha már nem csak simán PDF megmutatása miatt vagyunk itt
    // Így tudod egy PDF fájlba elmenteni a generált tartalmat
    // Könyvtárak kialakítása
    if( empty($_GET['PDF_elonezet']) ) {

        // Csak akkor mentünk, ha a munkalap.php ehhez a munkalaphoz hozta létre a fenntartási szerződést, és az létezik is.
        // Enélkül (pl. a ...&nyomtat URL frissítése, újbóli megnyitása) 0-s ID-jű vagy duplikált dokumentum sorok keletkeznének.
        $mentheto = !empty($_SESSION['ml_megr_ft_recid'])
                 && ($_SESSION['ml_megr_ml_recid'] ?? '') == $ml_recid
                 && !empty($ft_szerzodes['szszam']);

        if( $mentheto ) {

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

            $utvonal_db_be = $ev.'/'.$szszam_mappa.'/'.$fajlnev;

            // Dokumentum eltárolása adatbázisba
            $adatbazis_kapcsolat -> i('fenntartasi_szerzodes_dok', ['fenntartasi_szerzodes_id',    'utvonal',      'fajlnev',     'update_usr',                           'insert_dt',    'insert_usr'                            ],
                                                                   [$_SESSION['ml_megr_ft_recid'], $utvonal_db_be, $fajlnev,      $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  ]
                                                                );

            $dok_db = 1;
            // Ekkor a munkalap aktuális dokumentumait is másoljuk át a fenntartási szerződéshez (kivéve amit most vettünk fel, hisz az már benne van)
            $munkalap_dokok = $adatbazis_kapcsolat -> s('utvonal fajlnev sor loginnev update_dt update_usr insert_dt insert_usr', 'munkalap_dok', "`munkalap_id`='{$ml_recid}'");
            foreach ($munkalap_dokok as $munkalap_dok) {
                $munkalap_dok['utvonal'] = '../munkalap/'.$munkalap_dok['utvonal'];
                $adatbazis_kapcsolat -> i('fenntartasi_szerzodes_dok',  ['fenntartasi_szerzodes_id',    'utvonal',                'fajlnev',                'sor',                'loginnev',                'update_dt',                'update_usr',                'insert_dt',                'insert_usr'               ],
                                                                        [$_SESSION['ml_megr_ft_recid'], $munkalap_dok['utvonal'], $munkalap_dok['fajlnev'], $munkalap_dok['sor'], $munkalap_dok['loginnev'], $munkalap_dok['update_dt'], $munkalap_dok['update_usr'], $munkalap_dok['insert_dt'], $munkalap_dok['insert_usr']]
                                                                    );
                $dok_db++;
            }

            $adatbazis_kapcsolat -> u('fenntartasi_szerzodes', ['dok_db'], [$dok_db], "`recid`='{$_SESSION['ml_megr_ft_recid']}'");

            // Munkalapok dokumentumához is adjuk hozzá
            $ml_doc_recid = $adatbazis_kapcsolat -> i('munkalap_dok',   ['munkalap_id',    'utvonal',                                  'fajlnev',     'update_usr',                           'insert_dt',    'insert_usr'                            ],
                                                                        [$ml_recid,        '../fenntartasi_szerzodes/'.$utvonal_db_be, $fajlnev,      $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  ]
                                    );
            $adatbazis_kapcsolat -> u('munkalap', ['dok_db'], [$munkalap['dok_db']+1], "`recid`='{$ml_recid}'");

            // A mentés egyszer használatos: egy újabb ...&nyomtat kérés már ne mentsen újra
            $_SESSION['ml_megr_ft_recid'] = '';
            $_SESSION['ml_megr_ml_recid'] = '';
        }

        // Töltsük be a munkalapot
        // header('location:munkalap.php?modosit&id='.$ml_recid);
        $_GET['id'] = $ml_recid;
        header('location:'.self().get_get('nyomtat', '-&-'));

    // Egyébként a képernyőn mutatjuk meg
    } else {

        // Így tudod megnyitni a PDF-et (olyan mintha egy PDF kiterjesztésű fájlra hivatkoznál)
        if( !isset($_GET['html_mutat']) ) {
            $pdf_neve = str_replace('/', '_', $munkalap['ikt_szam']).'.pdf';
            $mpdf->Output($pdf_neve, 'I'); 

            // Ha utólag kell a PDF-et előállítani, akkor akár egy konkrét útvonalba is eltárolhatod:
            // $mpdf -> Output(iconv('UTF-8', 'Windows-1250', '../tartalom/fenntartasi_szerzodes/26/M_2026_676/Munkamegrendeles_M_2026_676.pdf'), 'F');
        }
        
        $weblap -> leallit();
        exit;
    }
}