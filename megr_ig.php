<?php

require_once('header.php');

if( isset($_GET['h']) ) {
    $megr = $adatbazis_kapcsolat -> s2('recid igazolva_dt', 'eszkozigenyles_megr', "`hash`='{$_GET['h']}'");
    if( $megr['igazolva_dt'] != '0000-00-00 00:00:00' ) {
        $felirat = '<span style="color: #b91414ff">Ez a megrendelés már visszaigazolásra került!</span>';
    } else {
        $felirat = 'Megrendelés visszaigazolva!';
        $adatbazis_kapcsolat -> u('eszkozigenyles_megr', array('igazolva_dt'), array('NOW()'), "`hash`='{$_GET['h']}'");
    }
}

echo nl2('
<div class="grid">
    <div>
        <div>
            <img src="../elemek/logo.png" alt="ontEAM">
        </div>
        <div>
            <h1>'.$felirat.'</h1>
        </div>
    </div>
</div>

<style>
    .grid {
        display: grid;
        align-items: center;
        height: 100vh;
        justify-items: center;
        justify-content: center;
    }
    .grid > div {
        text-align: center;
        padding-bottom: 20vh;
    }
</style>
');

require_once('footer.php');
