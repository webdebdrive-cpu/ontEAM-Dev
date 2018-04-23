<?php
echo str_replace('{CACHE}', '?cache='.time(), fajlbol_olvas('../sablon/admin/footer.html', true));
echo $_SESSION['akt_sql'];

// Legutóbbi ID megjegyeztetése
if ($_GET['id']){
    $_SESSION['legutobbi_id'] = $_GET['id'];
}

$weblap -> leallit();
?>