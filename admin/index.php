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
require_once('header.php');
require_once('footer.php');
?>