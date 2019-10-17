<?php
require_once '../weblap.php';

$cli = new SoapClient(null, array('uri' => 'http://demo.webdeb.hu/danubiushotel.hu/admin/andoc.php', 'location' => 'http://demo.webdeb.hu/danubiushotel.hu/admin/andoc.php', 'trace' => true, 'encoding'=>'utf-8'));
//$h   = new SoapHeader('http://demo.webdeb.hu/danubiushotel.hu/admin/andoc.php', 'auth', '123456789', false, SOAP_ACTOR_NEXT);
//$cli ->__setSoapHeaders(array($h));

echo $cli -> osszead(25, 5);
$postdata = file_get_contents("php://input");
fajlba_mentes('!teszt.txt', $postdata);
?>