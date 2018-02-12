<?php
// $mpdf = new mPDF('utf-8');
// Fejléc képe
// $mpdf ->header_kep = file_get_contents('../elemek/pdf_header.png');
// $kep = '<img src="var:header_kep" />';
// $mpdf ->WriteHTML($kep);
// $mpdf ->WriteHTML($kep)
// $mpdf ->Image('var: header_kep',0,0, 33);

/*$tomb = array(
             'L' => array(
                 'content'=> '',
                 'font-size' => 12,
                 'font-family' => 'serif'
             ),
             'C' => array(
                 'content' => $rendeles_szam,
                 'font-size' => 12,
                 'font-family' => 'serif'
             ),
             'R' => array(
                 'content' => '{PAGENO} {nbpg}',
                 'font-size' => 12,
                 'font-family' => 'serif'
             ),
             'line' => 1,
            );

$mpdf ->SetHeader($tomb, 0);
*/



// $tarolo = '\'|\'.$rendeles_szam.\'|{PAGENO} {nbpg}\'';

// $mpdf ->pagenumPrefix = $rendeles_szam;
include('../weblap.php');
include('../mpdf/mpdf.php');

// HTML, amiből PDF-et készítünk
$html = fajlbol_olvas('teszt.html');

// Lecserélünk belőle pár js-t css-t, ami nem kell a PDF-hez
$mit = array('<script src="../js/jquery.js"></script>', '<link href="../css/nyomtatas.css" rel="stylesheet" type="text/css">');
$html = str_replace($mit, '', $html);

$rendeles_szam = 'Megrendelés sz.: R18/0018 ';
$footer_stamp  = 'teszt 2018.02.01 15:04:20';



$mpdf = new mPDF('utf-8','A4','','','10','10','28','25');






$header = '


            <div style="height:150px;">
                <table class="full" style="">
                    <tr>
                        <td style="width: 33%; padding-left: 10px">
                            <table class="full">
                                <tr>
                                    <td style="padding: 4px 4px 0 0"> <img src="../elemek/dh_logo.png" alt="Logo" width="50"> </td>
                                    <td style="font-size: 11px"> Danubius Zrt. <br>Műszaki Szolgáltató Központ <br>ontEAM </td>
                                </tr>
                            </table>
                        </td>
                        <td class="b c" style="width: 34%; font-size: 14px">'.$rendeles_szam.' </td>
                        <td class="r" style="font-size: 11px; width: 33%; padding-right: 10px"> <span class="szamlalo"></span>{PAGENO} {nbpg}</td>
                    </tr>
                </table>
            </div>';

$footer = '<div class="footer">
                <div class="dtc padd"> </div>
                <div class="dtc r padd" style="font-size: 7px">'.$footer_stamp.'</div>
            </div><pagebreak>';


$mpdf ->nbpgPrefix = '/ ';
$mpdf ->SetHTMLHeader( $header, '', true);
$mpdf ->SetHTMLFooter( $footer);


$mpdf ->WriteHTML($html, 0);

// PDF fájlba mentése
$mpdf -> Output( 'teszt.pdf', 'F' );

echo '<a href="teszt.pdf" target="blank">Anti kiraly</a>';
?>
