<?php
echo fajlbol_olvas('../sablon/admin/footer.html', true);

// Legutóbbi ID megjegyeztetése
if ($_GET['id']){
    $_SESSION['legutobbi_id'] = $_GET['id'];
}

$weblap -> leallit();
?>