<?php
include('../weblap.php');
include('../html2pdf/mpdf.php');

// HTML, amiből PDF-et készítünk
$html = fajlbol_olvas('teszt.html');

// Lecserélünk belőle pár js-t css-t, ami nem kell a PDF-hez
$mit = array('<script src="../js/jquery.js"></script>', '<link href="../css/nyomtatas.css" rel="stylesheet" type="text/css">');
$html = str_replace($mit, '', $html);


$mpdf = new mPDF('utf-8');
$mpdf -> WriteHTML( 'teszt' );

// PDF fájlba mentése
$mpdf -> Output( 'teszt.pdf', 'F' );

echo '<a href="teszt.pdf" target="blank">Anti kiraly</a>';
?>