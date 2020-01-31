<?php
require '../weblap.php';
// Ajax megszakítása
if( isset($_POST['csv_file_torol']) ) {
    $filename = $_POST['filename'];

    if( $filename ) {
        unlink('export/csv/'.$filename);
    }

    echo json_encode(array(
        'statusz' => 'OK'
    ));
}