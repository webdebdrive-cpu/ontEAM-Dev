<?php
//require_once '../weblap.php';

$soap = new SoapServer(
    NULL,
    array('uri' => 'http://demo.webdeb.hu/danubiushotel.hu/admin/andoc.php')
);
$soap->setClass('MintaClass');
$soap->handle();


class MintaClass {
    
    public function auth($a) {
        if($a != '123456789'){
            Throw new SoapFault ('Server', 'Helyetelen jelszó!');
        }
    }
    
    function osszead($a, $b) {
        return $a + $b;
    }
}

?>