<?php
if ($_GET['print']=='bej_lista') {
    echo nl2('
<link href="../css/nullaz.css" rel="stylesheet" type="text/css">
<link href="../css/admin.css" rel="stylesheet" type="text/css">
<link href="../css/nyomtatas.css" rel="stylesheet" type="text/css">
<script src="../js/jquery.js"></script>
<img src="../elemek/admin/nyomtat.gif" alt="Nyomtat" class="nyomtat_ikon nyomtat_dn link" onclick="window.print();">
<title>Listanézet '.date('Y.m.d H:i:s').'</title>

<div class="a4_allo"> 
    <div class="padd">
        Szűrő feltételek: <b>Nincs</b>
    </div>
    <div class="footer">
        <div class="dtc padd">
            '.$_SESSION[get_db_tabla().'teljes_nev'].'
        </div>
        <div class="dtc r padd">
            '.date('Y.m.d H:i:s').'
        </div>
    </div>
</div>
            ');
    
    
    
    $weblap -> leallit();
    exit;
}
?>
