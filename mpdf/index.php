<?php
/*  HTML => PDF                                                                                             *\

    1. Másold fel ennek a mappának a tartalmát a tárhelyre egy html2pdf nevű mappába.
    
    2. Ekkor egy PHP fájlban az alábbi mintakóddal tudsz PDF-et generálni.
    
    3. További infók: http://www.mpdf1.com és az examples mappában
    
    
    TIP: Ha több oldalnyi tartalmat szeretnél kinyomtatni akkor táblzatok esetén ügyelj, hogy egy oldalnyi
    taratlom után legyen megszakítás a táblázatban. Ellenkező esetben egy oldalra fogja bekicsinyíteni az
    egész táblázatot.

\*                                                                                                          */

// Így tudod kikapcsolni a warningokat és hibaüzeneteket
error_reporting(0);
include('html2pdf/mpdf.php');

$html = '<h1>Hello World</h1>';

$mpdf = new mPDF('utf-8');
$mpdf -> WriteHTML($html);

// Így tudod megnyitni  a PDF-et (olyan mintha egy PDF kiterjesztésű fájlra hivatkoznál)
$mpdf -> Output();


// Így tudod egy PDF fájlba elmenteni a generált taratlmat
$mpdf -> Output(iconv('UTF-8', 'Windows-1250', get_utvonal().'valami/valami.pdf'), 'F');

// Élőfej és élőlébra mint a Hoteles proektben található.

?>