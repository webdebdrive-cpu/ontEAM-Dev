<?php
/*        ADMINISZTRÁCIÓS FELÜLET                                                                                                          *\
  
  Admin mappa elérési útvonala a szerveren (jelsszóhoz): $_SERVER['DOCUMENT_ROOT']
  
  Telepítés: A teljes admin mappát át kell másolni (class, css, js mappákat is használja, de ezek már elvileg jelen vannak).
	 admin/index.php-t megnyitva telepítsd az admint. 
  
  Súgó és dokumentáció: index.php?sugo     
  
\*                                                                                                                                         */

if(isset($_GET['info'])){
    phpinfo();
    exit;
}


//Ha egy tálbának utólag adsz egy loginnev nevű oszlopnevet
if (isset($_GET['loginnev_gyart'])) {
    require_once('../weblap.php');
    
    $tabla = 'ktgnem';
    
    $sorok = $adatbazis_kapcsolat->s('recid insert_usr', $tabla);
    foreach($sorok as $sor){
        $loginnev = $adatbazis_kapcsolat->s('nev', 'login', "`teljes_nev`='{$sor['insert_usr']}'");
        $loginnev = $loginnev[0]['nev'];
        
        $adatbazis_kapcsolat->u($tabla, array('loginnev'), array($loginnev), "`recid`='{$sor['recid']}'");
echo $adatbazis_kapcsolat->gp() . '<br>';
    }
    
    exit;
}

//Anyagigénylés speciálisabb volt
if (isset($_GET['loginnev_gyart_anyagigenyles'])) {
    require_once('../weblap.php');
    
    $tabla = 'anyagigenyles';
    
    $sorok = $adatbazis_kapcsolat->s('recid loginnev', $tabla);
    foreach($sorok as $sor){
        $teljes_nev = $adatbazis_kapcsolat->s('teljes_nev', 'login', "`nev`='{$sor['loginnev']}'");
        $teljes_nev = $teljes_nev[0]['teljes_nev'];
        
        $adatbazis_kapcsolat->u($tabla, array('insert_usr'), array($teljes_nev), "`recid`='{$sor['recid']}'");
echo $adatbazis_kapcsolat->gp() . '<br>';
    }
    
    exit;
}
require_once('header.php');

require_once('footer.php');
?>