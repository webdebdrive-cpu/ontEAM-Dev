<?php
require_once('header.php');

$konyvtar = '!pdf_teszt/';

// Régi PDF fájlok törlése
$fajlok = konyvtar_bejar($konyvtar);
foreach ($fajlok as $fajl) {
    unlink($fajl);
}

$limit = 150;

$igenylesek = $adatbazis_kapcsolat -> s('recid', 'eszkozigenyles', "`tipus`='".BESZ_TIP_TERVEZETT."'", '', $limit);
$igenylesek = adatbazis2tomb($igenylesek, 'recid');

megrendeles_keszit($igenylesek, '-tervezett-');

echo 'kész, sikeresen lefutott';
exit;
        
require_once('footer.php');
        
        
        
// Megrendelést készítő eljárás
function megrendeles_keszit($eszkozigenyles_id, $tevezett = 0) {
    
    global $adatbazis_kapcsolat;
    global $konyvtar;
            
    include('../mpdf/mpdf.php');
    require('selectek.php');

    $eszkozig_oszlopok = 'recid tipus stat_id rend_szam csz nev igmen jhmen arfolyam netto jhar jhossz telephely_id szallito_id szall_hat fiz_mod hat_ido ossz_netto ossz_dev kcs_21 kiszall beuzemel munkadij egyeb_koltseg eloleg megj insert_dt';

    // Group by-ozzunk szálloda és szállító alapján, mert tervezett igényléseknél ezek alapján kell létrehozni egy-egy PDF-et.
    // Soron kívüli + GM esetén sem gond a group by, egy sor marad benne
    if( $tevezett ) {
        $where = "`recid` IN (".implode(', ', $eszkozigenyles_id).")";
    } else {
        $where = "`recid`='{$eszkozigenyles_id}'";
    }
    $telephely_szallitok = $adatbazis_kapcsolat -> s('telephely_id szallito_id', 'eszkozigenyles', $where, '', '', "`telephely_id`, `szallito_id`");
    foreach ($telephely_szallitok as $telephely_szallito) {
        
        // Tervezett esetben nem csak egy igénylést, hanem egy egész tömböt kell feldolgozni
        if( $tevezett ) {
            $eszkozigenylesek = $adatbazis_kapcsolat -> s($eszkozig_oszlopok, 'eszkozigenyles', "`telephely_id`='{$telephely_szallito['telephely_id']}' AND `szallito_id`='{$telephely_szallito['szallito_id']}' AND `recid` IN (".implode(', ', $eszkozigenyles_id).")");
            if( $eszkozigenylesek === false ) { $db_error = true; }

        // Soron kívüli / GM-es eset. Itt nem is kell a $telephely_szallito feltétel bele, úgy is csak egy sor
        } else {
            $eszkozigenylesek = $adatbazis_kapcsolat -> s($eszkozig_oszlopok, 'eszkozigenyles', "`recid`='{$eszkozigenyles_id}'");
            if( $eszkozigenylesek === false ) { $db_error = true; }
        }

        $tetelek_html = '';
        $mindossz     = 0;
        $ossz_netto_megr = 0;
        $ossz_dev_megr   = 0;
        foreach ($eszkozigenylesek as $eszkozigenyles) {
            
            // A Jóváhagyott nettó összesen is garantáltan üres ekkor még. Most kell beleírni a jóváhagyott mennyiséget és egységár szorzatát.
            $jhossz = $eszkozigenyles['jhmen']*$eszkozigenyles['jhar'];
            $eszkozigenyles['jhossz'] = $jhossz;

            // HUF esetén $ossz_netto és $ossz_dev számítás
            if( $eszkozigenyles['kcs_21']==KCS_21_HUF ) {
                $ossz_netto = $eszkozigenyles['jhossz'];
                $ossz_dev   = 0;
                if( $eszkozigenyles['kiszall'] > 0 ) {
                    $ossz_netto += $eszkozigenyles['kiszall'];
                }
                if( $eszkozigenyles['beuzemel'] > 0 ) {
                    $ossz_netto += $eszkozigenyles['beuzemel'];
                }
                if( $eszkozigenyles['munkadij'] > 0 ) {
                    $ossz_netto += $eszkozigenyles['munkadij'];
                }
                if( $eszkozigenyles['egyeb_koltseg'] > 0 ) {
                    $ossz_netto += $eszkozigenyles['egyeb_koltseg'];
                }

            // Ha nem HUF $ossz_netto és $ossz_dev számítás
            } else {
                $ossz_dev = $eszkozigenyles['jhossz']/$eszkozigenyles['arfolyam'];
                if( $eszkozigenyles['kiszall'] > 0 ) {
                    $ossz_dev += $eszkozigenyles['kiszall'];
                }
                if( $eszkozigenyles['beuzemel'] > 0 ) {
                    $ossz_dev += $eszkozigenyles['beuzemel'];
                }
                if( $eszkozigenyles['munkadij'] > 0 ) {
                    $ossz_dev += $eszkozigenyles['munkadij'];
                }
                if( $eszkozigenyles['egyeb_koltseg'] > 0 ) {
                    $ossz_dev += $eszkozigenyles['egyeb_koltseg'];
                }
                $ossz_netto = $ossz_dev*$eszkozigenyles['arfolyam'];
            }
            $ossz_netto_megr += $ossz_netto;
            $ossz_dev_megr   += $ossz_dev;

            // Tételek összeszedése
            $penznem = getErtekLista(ERTEKLISTA_PENZNEM, $eszkozigenyles['kcs_21']);
            $tizedesek  = 0;
            $jhar       = $eszkozigenyles['jhar'];
            $jhossz     = $eszkozigenyles['jhossz'];
            if( $eszkozigenyles['kcs_21']!=KCS_21_HUF ) {
                $tizedesek  = 2;
                $jhar       = $eszkozigenyles['jhar'  ]/$eszkozigenyles['arfolyam'];
                $jhossz     = $eszkozigenyles['jhossz']/$eszkozigenyles['arfolyam'];
            }

            $mindossz   += $jhossz;
            $tetel_neve  = trim($eszkozigenyles['csz'].' '.$eszkozigenyles['nev']);
            $jhossz_kiir = number_format($jhossz, $tizedesek, ',', ' ');
            if( $eszkozigenyles['kiszall'] > 0 ) {
                $tetel_neve  .= '<br>Kiszállítási költség';
                $jhossz_kiir .= '<br>'.number_format($eszkozigenyles['kiszall'], $tizedesek, ',', ' ');
                $mindossz += $eszkozigenyles['kiszall'];
            }
            if( $eszkozigenyles['beuzemel'] > 0 ) {
                $tetel_neve  .= '<br>Beüzemelési költség';
                $jhossz_kiir .= '<br>'.number_format($eszkozigenyles['beuzemel'], $tizedesek, ',', ' ');
                $mindossz += $eszkozigenyles['beuzemel'];
            }
            if( $eszkozigenyles['munkadij'] > 0 ) {
                $tetel_neve  .= '<br>Munkadíj';
                $jhossz_kiir .= '<br>'.number_format($eszkozigenyles['munkadij'], $tizedesek, ',', ' ');
                $mindossz += $eszkozigenyles['munkadij'];
            }
            if( $eszkozigenyles['egyeb_koltseg'] > 0 ) {
                $tetel_neve  .= '<br>Egyéb költség';
                $jhossz_kiir .= '<br>'.number_format($eszkozigenyles['egyeb_koltseg'], $tizedesek, ',', ' ');
                $mindossz += $eszkozigenyles['egyeb_koltseg'];
            }
            if( $eszkozigenyles['megj'] ) {
                $tetel_neve .= '<br>'.nl2br2($eszkozigenyles['megj']);
            }

            $tetelek_html .= '
                            <tr style="border-top: 1px solid #ddd;">
                                <td class="c" style="border: 1px solid #ddd; padding: 2px; text-align: left; vertical-align: top;">'.$tetel_neve.'</td>
                                <td class="c" style="border: 1px solid #ddd; padding: 2px; text-align: right; vertical-align: top;">'.number_format($eszkozigenyles['jhmen' ], 0, '', ' ').'</td>
                                <td class="c" style="border: 1px solid #ddd; padding: 2px; text-align: right; vertical-align: top;">'.number_format($jhar,   $tizedesek, ',', ' ').'</td>
                                <td class="c" style="border: 1px solid #ddd; padding: 2px; text-align: right; vertical-align: top;">'.$jhossz_kiir.'</td>
                            </tr>
                        ';

        }

        // Szállító email címének lekérése (ha tervezetten egy sor volt, ha egy egész tömb, az utolsó igénylésben biztosan jó szallito_id van)
        $szall_mail = getErtek('email', 'szallito_beszerzes', 'recid', $eszkozigenyles['szallito_id']);

        // Ha létezik a rend_szam, akkor ilyen számon jöjjön létre a megrendelő (lehet egy létező megrendelést javítottak - azaz törlésre kerül - majd ugyanazon a számon kell létrehozni )
        // 2025.12.10 Ez a funkció kikapcsolásra került. Tehát megrendelő javításakor a rend_szam minden esetben törlésre kerül, így nem lesz olyan eset, hogy itt ezen a ponton ez egy ismert adat legyen.
        if( $eszkozigenyles['rend_szam'] ) {
            $rend_szam = $eszkozigenyles['rend_szam'];
        } else {
            $rend_szam = get_iktatoszam_sorszam('eszkozigenyles_megr');
            if($rend_szam===false){ $db_error = true; }
            $rend_szam = 'TE/'.date('Y').'/'.$rend_szam;
        }
        $rend_szam_fajlba = str_replace('/', '', $rend_szam);

        // Hash készítése a megrendeléshez
        $hash = jelszot_keszit();
        $foglalt = $adatbazis_kapcsolat -> s2('recid', 'eszkozigenyles_megr', "`hash`='{$hash}'");
        if( $foglalt === false ) { $db_error = true; }
        while($foglalt) {
            $hash = jelszot_keszit();
            $foglalt = $adatbazis_kapcsolat -> s2('recid', 'eszkozigenyles_megr', "`hash`='{$hash}'");
            if( $foglalt === false ) { $db_error = true; }
        }

        // Később hivatkozunk erre a mezőre, ezért kell itt megadni az értékét
        $eszkozigenyles['rend_szam'] = $rend_szam;


        // Kontakt személy lekérdezése: akinek van "Szállodai beszerző" joga és adott igénylés szállodája a látókörében van
        $kontakt = $adatbazis_kapcsolat -> s2('teljes_nev usertel usermail', 'login', "substr(`flags`, ".(JOG_BESZERZ_IGENYLESEK_BESZERZO+1).", 1)=1 AND `aktiv`=1 AND `virtual`=0 AND `nev` IN (SELECT `nev` FROM `".get_db_tabla()."telephely_kapcs` WHERE `telephely_id`='{$eszkozigenyles['telephely_id']}')");
        if( $kontakt === false ) { $db_error = true; }
        $usertel = '';
        if( $kontakt['usertel'] ) {
            $usertel = ', tel.: '.$kontakt['usertel'];
        }

        $szallito = $adatbazis_kapcsolat->s2('nev knev tel email', 'szallito_beszerzes', "`recid`='{$eszkozigenyles['szallito_id']}'");
        if( $szallito === false ) { $db_error = true; }

        // Ha nincs kapcsolattartó név, akkor "Partnerünk" a megszólítás
        if( !$szallito['knev'] ) {
            $szallito['knev'] = 'Partnerünk';
        }
        // 2025.10.04: kifejezetten fixen ezt szeretnék látni és nem a kapcsolattartó nevét
        // 2025.11.04: Tibi kérte, hogy mégis a kapcsolattartó neve kerülön oda, ha ki van töltve, sőt ott is csak az első vesszőig.
        // $szallito['knev'] = 'Partnerünk';
        $szallito['knev'] = explode(',', $szallito['knev'])[0];

        $telephely = $adatbazis_kapcsolat->s2('nev props', 'telephely', "`id`='{$eszkozigenyles['telephely_id']}'");
        if( $telephely === false ) { $db_error = true; }
        $props_szn      = get_props($telephely['props'], 'szn'      );
        $props_cim      = get_props($telephely['props'], 'cim'      );
        $props_szallcim = nl2br2(get_props($telephely['props'], 'szallcim' ));

        $fizmod_kiir = '';
        foreach ($select_fiz_mod as $fizmod) {
            if( $eszkozigenyles['fiz_mod'] && $fizmod['value'] == $eszkozigenyles['fiz_mod'] ) {
                $fizmod_kiir = $fizmod['name'];
            }
        }

        $hat_ido_kiir = '';
        foreach ($select_hat_ido as $hat_ido) {
            if( $eszkozigenyles['hat_ido'] && $hat_ido['value'] == $eszkozigenyles['hat_ido'] ) {
                $hat_ido_kiir = $hat_ido['name'];
            }
        }
    
        // A Fizetési mód és határidő sor csak akkor jelenik meg, ha van legalább egyik adat
        $fiz_mod_hatarido = '';
        $eloleg = '';
        if( $eszkozigenyles['eloleg'] ) {
            $eloleg .= '&nbsp;&nbsp;&nbsp;Előleg: '.$eszkozigenyles['eloleg'].'%';
        }
        if( $fizmod_kiir || $eszkozigenyles['hat_ido'] ) {
            $fiz_mod_hatarido .= nl2('
                <tr>
                    <td class="b" style="padding: 20px 20px 0 0">
                        Fizetési mód és határidő:
                    </td>
                    <td style="vertical-align: top; padding: 20px 0 0 0">
                        '.$fizmod_kiir.'
                        '.$hat_ido_kiir.'
                        '.$eloleg.'
                    </td>
                </tr>
            ');
        }

        // Lényegi tartalom
        $fejlec = get_nyomtatas_fejlec_megr($eszkozigenyles, $szallito, $kontakt);
        if( $fejlec === false ) {
            $db_error = true;
        }

        $tartalom = '
        <div class="padd" style="padding-bottom: 0; margin-bottom: 0">
                '.$fejlec.'
                <div style="font-size: 12px; margin: 0 0 2px 0; padding-top: 10px">
                    Tisztelt '.$szallito['knev'].'!
                    <br>
                    <br>Megrendeljük Önöktől a <b>Danubius Hotel '.getSzallodaNev($eszkozigenyles['telephely_id']).'</b> részére az alábbi tételeket:
                    <br>
                </div>

                <table class="border full" style="font-size: 12px;border-collapse: collapse; border: 1px solid #000;">
                <tr>
                    <th style="border: 1px solid #ddd; padding: 2px; text-align: left;">Megnevezés</th>
                    <th style="border: 1px solid #ddd; padding: 2px; text-align: right; width: 88px;">Mennyiség<br>(db)</th>
                    <th style="border: 1px solid #ddd; padding: 2px; text-align: right; width: 118px;">Nettó egységár<br>('.$penznem.')</th>
                    <th style="border: 1px solid #ddd; padding: 2px; text-align: right; width: 118px;">Nettó összesen<br>('.$penznem.')</th>
                </tr>
                '.$tetelek_html.'
                <tr style="border-top: 1px solid #ddd;">
                    <td class="c" style="border: 1px solid #ddd; padding: 2px; text-align: left; vertical-align: top;"><b>Mindösszesen:</b></td>
                    <td class="c" style="border: 1px solid #ddd; padding: 2px; text-align: right; vertical-align: top;"></td>
                    <td class="c" style="border: 1px solid #ddd; padding: 2px; text-align: right; vertical-align: top;"></td>
                    <td class="c" style="border: 1px solid #ddd; padding: 2px; text-align: right; vertical-align: top;"><b>'.number_format($mindossz, $tizedesek, ',', ' ').'</b></td>
                </tr>
                </table>

                <table style="font-size: 12px; border-collapse: collapse; margin-bottom: 20px">
                '.$fiz_mod_hatarido.'
                <tr>
                    <td class="b" style="padding: 10px 20px 0 0">
                        Kontaktszemély:
                    </td>
                    <td style="vertical-align: top; padding: 10px 0 0 0">
                        '.$kontakt['teljes_nev'].$usertel.'
                    </td>
                </tr>
                </table>

                <table style="font-size: 12px; border-collapse: collapse; width: 100%;">
                <tr>
                    <td style="padding: 2px; width: 50%;">
                        Számlázási cím
                    </td>
                    <td style="padding: 2px; width: 50%;">
                        Szállítási cím
                    </td>
                </tr>
                </table>
                <table style="font-size: 12px; border-collapse: collapse; margin-bottom: 20px; width: 100%; border: 1px solid #000">
                <tr>
                    <td style="padding: 2px; vertical-align: top; width: 50%; border-right: 1px solid #000">
                        <b>Danubius Hotels Zrt.</b>
                        <br><b>'.$props_szn.'
                        <br>'.$props_cim.'</b>
                        <br><b>Adószáma:</b> 10594702-2-44
                        <br><b>Cégjegyzékszám:</b> 01-10-041669
                    </td>
                    <td style="padding: 2px; width: 50%; vertical-align: top; border: 1px solid #000">
                        <b>'.$props_szallcim.'</b>
                    </td>
                </tr>
                </table>

                <div style="font-size: 12px; margin: 0 0 2px 0; padding-top: 10px">
                    Kérjük megrendelésünket a szállítási határidő feltüntetésével szíveskedjen visszaigazolni.
                    <br>A számlán a megrendelés számát legyenek kedvesek szerepeltetni!
                </div>

                <div style="page-break-inside: avoid;">
                    <div style="font-size: 12px; margin: 30px 0 3px 0;">
                        Üdvözlettel,
                        <br>Budapest, '.date_formaz(date('Y-m-d')).'
                    </div>
                    '.get_nyomtatas_alairasok(0, 0, '', $eszkozigenyles['telephely_id']).'
                </div>
            </div>
        </div>
        ';

        $keret = get_nyomtatas_keret('Megrendelés száma: '.$rend_szam);
        $html  = str_replace('{TARTALOM}', $tartalom, $keret);

        $mit = array('<script src="../js/jquery.js"></script>', '<link href="../css/nyomtatas.css" rel="stylesheet" type="text/css">');
        $html = str_replace($mit, '', $html);

        $mpdf = new mPDF('utf-8');

        $mpdf -> AddPage('P', '',   '', '', '', '10', '10', '20', '10', '5', '5');
        $mpdf -> nbpgPrefix = '/ ';
        $mpdf -> simpleTables = true;
        $mpdf -> packTableData = true;

        $header = get_nyomtatas_fejlec_top($rend_szam, '-lapszámmal-', '-Műsz.szolg. szöveg nélkül-');
        $footer = '<div class="dtc r" style="font-size: 7px">
                        '.$_SESSION[get_db_tabla().'user'].'
                        '.date('Y.m.d H:i:s').'
                    </div>';
        $mpdf ->SetHTMLHeader( $header, '', true);
        $mpdf ->SetHTMLFooter( $footer, '', true);

        $mpdf -> WriteHTML( $html );

        // Fájlba mentés
        $ev         = date('y', strtotime($eszkozigenyles['insert_dt']));
        $fajlnev    = 'Minta_pdf_'.$rend_szam_fajlba.'.pdf';
        if( !is_dir($konyvtar) ) {
            mkdir($konyvtar);
        }
        $mpdf -> Output(iconv('UTF-8', 'Windows-1250', $konyvtar.$fajlnev), 'F');
echo $konyvtar.$fajlnev .' PDF fájl létrejött<br>';

    }

}