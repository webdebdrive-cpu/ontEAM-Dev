<?php

//Ha nyomtatunk, akkor pár adatot rögtön ki is számolunk
if (isset($_GET['nyomtat'])) {
    $adatok              = base64_decode($_GET['nyomtat']);
    list($sorszam, $idk) = explode('|', $adatok);
    $idk                 = json_decode($idk, true);
    $idk_impl            = implode(',', $idk);
 
    //Szállító kiszámítása
    $szallito = $adatbazis_kapcsolat->s('szallito kcs_21', 'anyagigenyles', "`recid`='{$idk[1]}'");
    $szallito = $szallito[0];
    
    $penznem = $adatbazis_kapcsolat->s('info', 'erteklista', "`csoport`=21 AND `id`='{$szallito['kcs_21']}'");
    $penznem = $penznem[0];

    $szallito = $adatbazis_kapcsolat->s('nev knev tel tel2 isz varos utca', 'szallito', "`recid`='{$szallito['szallito']}'");
    $szallito = $szallito[0];
    
}

//Szállítói nyomtatási kép
if (isset($_GET['szall'])) {
    
    if($idk_impl){
        
        //Cikkenkénti összesítés
        $tetelek_html = nl2('
                            <table class="border full" style="font-size: 1.2rem ;margin: auto; margin-top: 1rem; margin-bottom: 1rem">
                            <tr>
                                <th>Név</th>
                                <th>Mee</th>
                                <th>Rendelt mennyiség</th>
                                <th>Nettó rendelési egységár</th>
                                <th>Nettó összesen ('.$penznem['info'].')</th>
                            </tr>
                        ');
    
        //Anyagonként járjuk be az anyagigényléseket, összesítve a darabszámot
        $ossz = 0;
        $anyagok = $adatbazis_kapcsolat->s('distinct cikktorzs_id', 'anyagigenyles', "`recid` IN (".$idk_impl.")");
        foreach($anyagok as $anyag){

            $rndmen = 0;
            $anyag_lek = $adatbazis_kapcsolat->s('nev cikkmee rndmen rndear', 'anyagigenyles', "`recid` IN (".$idk_impl.") AND `cikktorzs_id`='{$anyag['cikktorzs_id']}'");
            foreach($anyag_lek as $a_lek){
                $rndmen += $a_lek['rndmen'];
            }
            $tetelek_html .= nl2('
                            <tr>
                                <td>'.$a_lek['nev'].'</td>
                                <td class="c">'.$a_lek['cikkmee'].'</td>
                                <td class="r">'.number_format($rndmen, 2, ',', ' ').'</td>
                                <td class="r">'.number_format($a_lek['rndear'], 4, ',', ' ').'</td>
                                <td class="r">'.number_format($rndmen*$a_lek['rndear'], 4, ',', ' ').'</td>
                            </tr>
                            ');
            $ossz += $rndmen*$a_lek['rndear'];
        }
        $tetelek_html .= nl2('
                        <tr>
                            <td style="border: 0"></td>
                            <td style="border: 0"></td>
                            <td style="border: 0"></td>
                            <td style="border: 0" class="r b">Mindösszesen:</td>
                            <td style="border: 0" class="r b">'.number_format($ossz, 4, ',', ' ').'</td>
                        </tr>
                        </table>
                        ');
    }
    
    
    //Lényegi tartalom
    $tartalom = nl2('        
        '.get_fejlec_top($sorszam, 'lapszámmal').'
        <div class="padd minh">
            
                '.get_fejlec($szallito).'

                <div class="f14" style="margin: 2rem 0 0.3rem 0;">
                    Tisztelt szállító!
                </div>
                <div class="f13" style="margin: 0 0 0.3rem 0;">
                    <b>A szállító levelet vagy a számla másolatát minden esetben hagyja az árú átvevőjénél és a számlán tüntesse fel megrendelőnk számát, ellenkező esetben a számlát nem áll módunkban befogadni!</b> 
                </div>
                <div class="f14" style="margin: 0 0 0.5rem 0;">
                    Megrendeljük Önöktől az alábbi anyagokat.
                </div>

                <div class="i">I. Cikkenkénti összesítés:</div>
                '.$tetelek_html.'
                <div class="i">II. Szállítási címenkénti összesítés:</div>
                '.szallitasi_cimenkenti_lista($idk_impl).'
                
                <div class="f13" style="margin: 3rem 0 0.3rem 0;">
                    Üdvözlettel,
                    <br>Budapest, '.date_formaz(date('Y-m-d')).'
                </div>
                
                '.get_alairasok().'
            </div>
        </div>
            ');
    
    $keret = get_keret();
    echo str_replace('{TARTALOM}', $tartalom, $keret);
    
    $weblap -> leallit();
    exit;
}

//Címenkénti nyomtatási kép
if (isset($_GET['cim'])) {
    
    $keret = get_keret();
    $telephelyek = $adatbazis_kapcsolat->s('distinct telephely_id', 'anyagigenyles', "`recid` IN (".$idk_impl.")");
    foreach($telephelyek as $telephely){
        
        $telephely = $adatbazis_kapcsolat->s('id nev', 'telephely', "`id`='{$telephely['telephely_id']}'");
        $telephely = $telephely[0];
        
        if($idk_impl){

            //Cikkenkénti összesítés
            $tetelek_html = nl2('
                                <table class="border full" style="font-size: 1.2rem ;margin: auto; margin-top: 1rem; margin-bottom: 1rem">
                                <tr>
                                    <th>Cikk</th>
                                    <th>Név</th>
                                    <th>Mee</th>
                                    <th>Rendelt mennyiség</th>
                                    <th>Nettó rendelési egységár</th>
                                    <th>Nettó összesen ('.$penznem['info'].')</th>
                                    <th style="width: 20rem">Megjegyzés</th>
                                </tr>
                            ');

            //Anyagonként járjuk be az anyagigényléseket, összesítve a darabszámot
            $ossz = 0;
            $anyagok = $adatbazis_kapcsolat->s('distinct cikktorzs_id', 'anyagigenyles', "`recid` IN (".$idk_impl.") AND `telephely_id`='{$telephely['id']}'");
            foreach($anyagok as $anyag){

                $rndmen = 0;
                $anyag_lek = $adatbazis_kapcsolat->s('cikktorzs_id nev cikkmee rndmen rndear', 'anyagigenyles', "`recid` IN (".$idk_impl.") AND `cikktorzs_id`='{$anyag['cikktorzs_id']}' AND `telephely_id`='{$telephely['id']}'");
                foreach($anyag_lek as $a_lek){
                    $rndmen += $a_lek['rndmen'];
                }
                $tetelek_html .= nl2('
                                <tr>
                                    <td>'.$a_lek['cikktorzs_id'].'</td>
                                    <td>'.$a_lek['nev'].'</td>
                                    <td class="c">'.$a_lek['cikkmee'].'</td>
                                    <td class="r">'.number_format($rndmen, 2, ',', ' ').'</td>
                                    <td class="r">'.number_format($a_lek['rndear'], 4, ',', ' ').'</td>
                                    <td class="r">'.number_format($rndmen*$a_lek['rndear'], 4, ',', ' ').'</td>
                                    <td></td>
                                </tr>
                                ');
                $ossz += $rndmen*$a_lek['rndear'];
            }
            $tetelek_html .= nl2('
                            <tr>
                                <td></td>
                                <td class="b">Mindösszesen:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="r b">'.number_format($ossz, 4, ',', ' ').'</td>
                                <td></td>
                            </tr>
                            </table>
                            ');
            
            
            //Lényegi tartalom
            $tartalom = nl2('        
                '.get_fejlec_top($sorszam).'
                <div class="padd minh">

                        <div class="f16 c" style="margin: 2rem 0 0.3rem 0;">
                            '.$telephely['nev'].' részére
                        </div>
                        '.get_fejlec($szallito).'

                        '.$tetelek_html.'
                            
                        <div class="lap_alja">Budapest,  '.date_formaz(date('Y-m-d')).'</div>
                    </div>
                </div>
                    ');

            echo str_replace('{TARTALOM}', $tartalom, $keret);
        }
        
    }
    
    $weblap -> leallit();
    exit;
}

//Oracle cikkszám nyomtatási kép
if (isset($_GET['oracle'])) {    
    $keret = get_keret();
    $telephelyek = $adatbazis_kapcsolat->s('distinct telephely_id', 'anyagigenyles', "`recid` IN (".$idk_impl.")");
    foreach($telephelyek as $telephely){
        
        $telephely = $adatbazis_kapcsolat->s('id nev', 'telephely', "`id`='{$telephely['telephely_id']}'");
        $telephely = $telephely[0];
        
        if($idk_impl){

            //Cikkenkénti összesítés
            $tetelek_html = nl2('
                                <table class="border full" style="font-size: 1.2rem ;margin: auto; margin-top: 1rem; margin-bottom: 1rem">
                                <tr>
                                    <th>Oracle cikkszám</th>
                                    <th>Nettó összesen ('.$penznem['info'].')</th>
                                </tr>
                            ');

            //Anyagonként járjuk be az anyagigényléseket, összesítve a darabszámot
            $ossz = 0;
            $anyagok = $adatbazis_kapcsolat->s('distinct cikktorzs_id', 'anyagigenyles', "`recid` IN (".$idk_impl.") AND `telephely_id`='{$telephely['id']}'");
            foreach($anyagok as $anyag){

                $rndmen = 0;
                $anyag_lek = $adatbazis_kapcsolat->s('cikktorzs_id nev cikkmee rndmen rndear', 'anyagigenyles', "`recid` IN (".$idk_impl.") AND `cikktorzs_id`='{$anyag['cikktorzs_id']}' AND `telephely_id`='{$telephely['id']}'");
                foreach($anyag_lek as $a_lek){
                    $rndmen += $a_lek['rndmen'];
                }
                $tetelek_html .= nl2('
                                <tr>
                                    <td>'.$a_lek['cikktorzs_id'].'</td>
                                    <td class="r">'.number_format($rndmen*$a_lek['rndear'], 4, ',', ' ').'</td>
                                </tr>
                                ');
                $ossz += $rndmen*$a_lek['rndear'];
            }
            $tetelek_html .= nl2('
                            <tr>
                                <td class="b">Mindösszesen:</td>
                                <td class="r b">'.number_format($ossz, 4, ',', ' ').'</td>
                            </tr>
                            </table>
                            ');
            
            
            //Lényegi tartalom
            $tartalom = nl2('        
                '.get_fejlec_top($sorszam).'
                <div class="padd minh">

                        <div class="f16 c" style="margin: 2rem 0 0.3rem 0;">
                            '.$telephely['nev'].' részére
                        </div>
                        '.get_fejlec($szallito).'

                        '.$tetelek_html.'
                            
                        <div class="lap_alja">Budapest,  '.date_formaz(date('Y-m-d')).'</div>
                    </div>
                </div>
                    ');

            echo str_replace('{TARTALOM}', $tartalom, $keret);
        }
        
    }
    
    $weblap -> leallit();
    exit;
}



function get_fejlec_top($sorszam, $lapszammal){
    
    if($lapszammal){
        $lapszammal = '<span class="szamlalo"></span>/';
    }
    
    return nl2('
        <div class="fejlec">
            <table class="full">
            <tr>
                <td style="width: 33%; padding-left: 1rem" class="f11">
                    Danubius Zrt.
                    <br>Műszaki szolgáltató központ
                    <br>ontEAM
                </td>
                <td class="b c" style="width: 34%">
                    Megrendelés sz.: '.$sorszam.'
                </td>
                <td class="f11 r" style="width: 33%; padding-right: 1rem">
                    '.$lapszammal.'1. lap
                </td>
            </tr>
            </table>
        </div>
        ');
}

function get_fejlec($szallito){
    return nl2('
            <div style="padding: 0 1.5rem 0 1.5rem">
                <table style="margin: auto; margin-top: 2rem; width: 95%">
                <tr>
                    <td class="f13 b" style="width: 50%; padding: 0 1rem">
                        Szállító:
                    </td>
                    <td class="f13 b" style="width: 50%; padding: 0 1rem">
                        Megrendelő:
                    </td>
                </tr>
                </table>

                <table class="border full" style="margin: auto; margin-top: 0rem;">
                <tr>
                    <td class="f12 vat" style="width: 50%; padding: 1rem">
                        Név: '.$szallito['nev'].'
                        <br>Kapcs.: '.$szallito['knev'].'
                        <br>Tel.: '.$szallito['tel'].'
                        <br>Fax.: '.$szallito['tel2'].'
                        <br>Cím.: '.$szallito['isz'].' '.$szallito['varos'].', '.$szallito['utca'].'
                    </td>
                    <td class="f12 vat" style="width: 50%; padding: 1rem">
                        Név: Műszaki szolgáltató központ
                        <br>Üi.: '.$_SESSION[get_db_tabla().'teljes_nev'].'
                    </td>
                </tr>
                </table>
        ');
}

function szallitasi_cimenkenti_lista($idk_impl, $szalloda){
    
    global $adatbazis_kapcsolat;
    
    $sql_kieg = '';
    if($szalloda){
        $sql_kieg = ' AND `telephely_id`=\''.$szalloda.'\'';
    }
    
    //Szállítási címenkénti összesítés
    $szallcim_html = nl2('
                        <table class="border full" style="font-size: 1.2rem ;margin: auto; margin-top: 1rem; margin-bottom: 1rem">
                    ');
    
    if($idk_impl){
        $anyagok = $adatbazis_kapcsolat->s('distinct telephely_id', 'anyagigenyles', "`recid` IN (".$idk_impl.")$sql_kieg");
        foreach($anyagok as $anyag){

            $telephely = $adatbazis_kapcsolat->s('nev props', 'telephely', "`id`='{$anyag['telephely_id']}'");
            $telephely = $telephely[0];
            
            $szn = get_props($telephely['props'], 'SZN');
            $szl = get_props($telephely['props'], 'SZLACIM');
            $cim = get_props($telephely['props'], 'cim');
            $szallcim_html .= nl2('
                            <tr>
                                <td class="b">
                                    Szállítási cím:
                                    <br>Számlázási cím:
                                    <br>Számlaküldési cím:
                                </td>
                                <td>
                                    '.$szn.' '.$cim.'
                                    <br>'.$szl.' '.$cim.'
                                    <br>'.$szl.' '.$cim.'
                                </td>
                            </tr>
                            ');
            
            
            //Szedjük össze az azonos cikkeket, ami adott telephelyhez tartozik, majd számoljuk ki, hány darab kell belőle
            $anyag_lek = $adatbazis_kapcsolat->s('distinct cikktorzs_id', 'anyagigenyles', "`recid` IN (".$idk_impl.") AND `telephely_id`='{$anyag['telephely_id']}'");
            foreach($anyag_lek as $a_lek){
                
                $rndmen = 0;
                $anyag_lek_db = $adatbazis_kapcsolat->s('nev cikkmee rndmen rndear', 'anyagigenyles', "`recid` IN (".$idk_impl.") AND `telephely_id`='{$anyag['telephely_id']}' AND `cikktorzs_id`='{$a_lek['cikktorzs_id']}'");
                foreach($anyag_lek_db as $a_lek_db){
                    $rndmen += $a_lek_db['rndmen'];
                }
                
                $szallcim_html .= nl2('
                                <tr>
                                    <td class="vat c">'.number_format($rndmen, 2, ',', '.').' '.$a_lek_db['cikkmee'].'</td>
                                    <td class="vat">'.$a_lek_db['nev'].'</td>
                                </tr>
                                ');
            }
            
            //Telephelyek után egy kis elválasztó
            $szallcim_html .= nl2('
                            <tr>
                                <td colspan="2" style="padding: 0.7rem; border-left: 0; border-right: 0; border-bottom: 0"></td>
                            </tr>
                            ');
        }
    }
    $szallcim_html .= nl2('
                        </table>
                    ');
    
    return $szallcim_html;
}

function get_alairasok(){
    return nl2('
                <table class="full" style="font-size: 1.2rem; margin-top: 8rem">
                <tr>
                    <td class="c">
                        .................................................
                        <br>Csanádi Péter
                        <br>MSzK vezető
                    </td>
                    <td class="c">
                        .................................................
                        <br>Kővágó Levente
                        <br>Diszpécser központ vezető
                    </td>
                </tr>
                </table>
        ');
}

function get_keret(){
    return nl2('
<head>
    <title></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <link href="../css/nullaz.css" rel="stylesheet" type="text/css">
    <link href="../css/admin.css" rel="stylesheet" type="text/css">
    <link href="../css/nyomtatas.css" rel="stylesheet" type="text/css">
    <script src="../js/jquery.js"></script>
    <img src="../elemek/admin/nyomtat.gif" alt="Nyomtat" class="nyomtat_ikon nyomtat_dn link" onclick="window.print();">
</head>
<body>
    <div class="a4_allo"> 
        {TARTALOM}
    </div>
<body>
        ');
}
?>