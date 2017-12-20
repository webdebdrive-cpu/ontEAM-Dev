<?php

$nyelv = '';
if($_SESSION['nyelv']){
    $nyelv = '_'.$_SESSION['nyelv'];
}
$mit   = array('{EV_NEV}',  '{NYELV}'   );
$mivel = array($ev_nev,     $nyelv      );
echo sablon_gyar('footer.html', $mit, $mivel);

$weblap -> leallit();
?>