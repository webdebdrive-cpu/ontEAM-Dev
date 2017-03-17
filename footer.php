<?php
//Javasolt footer beágyazás:
$ev_nev = '';
$evtol  = 2016;
$iden   = date('Y');
if( $iden > $evtol ){
    $ev_nev .= $evtol.' - '.$iden;
}else{
    $ev_nev .= $iden;
}
$ev_nev .= ' '.get_nev();

/* Google Ecommerce-hez: A Google Analytics kód folytatásaként kell meghívni
$ga_ecom = '';
if($_SESSION['script']){
    $ga_ecom = nl2("
        ga('require', 'ecommerce');
        var transaction = {
            'id': '{$_SESSION['kosar_id']}',
            'revenue': '{$_SESSION['vegosszeg']}',
        };
        ga('ecommerce:addTransaction', transaction);
        {$_SESSION['script']}
        ga('ecommerce:send');
        ");

    $_SESSION['script']     = '';
    $_SESSION['kosar_id']   = '';
    $_SESSION['vegosszeg']  = '';
}
 */

$nyelv = '';
if($_SESSION['nyelv']){
    $nyelv = '_'.$_SESSION['nyelv'];
}
$mit   = array('{EV_NEV}',  '{NYELV}'/*, '{GA_ECOM}'*/);
$mivel = array($ev_nev,     $nyelv/*,    $ga_ecom*/   );
echo sablon_gyar('footer.html', $mit, $mivel);

//Ha responzív az oldal és be van kapcsolva a debug, akkor az aktuális képernyőszélességet kiírjuk
if( (get_responsive() && get_debug()) || isset($_GET['respo'])){

    echo nl2('
    <script>
        $(document).ready(function(){
            $(\'#ablak_szel\').html( \'Ablak szélesség: \'+$(window).width()+\'px\' );
        });
        $(window).resize(function() {
            $(\'#ablak_szel\').html( \'Ablak szélesség: \'+$(window).width()+\'px\' );
        });
    </script>
    <div id="ablak_szel" style="bottom: 0; position: fixed; text-align: center; width: 100%; background: #fff; border-top: 0.1rem solid #ccc"></div>
        ');
}

if(get_debug()){
    //echo 'Demo verzió';
}

$weblap -> leallit();
?>
