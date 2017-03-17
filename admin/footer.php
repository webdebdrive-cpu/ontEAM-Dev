<?php
if(isset($_GET['kepekhez'])){
    echo '<script>if(document.getElementById(\'kepek_form\')){gorgetes_start(\'kepek_form\');}</script>';
}
echo fajlbol_olvas('../sablon/admin/footer.html', true);
$weblap -> leallit();
?>