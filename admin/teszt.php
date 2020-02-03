<?php
require '../weblap.php';

// Progresshez szükséges fejlécek
header('X-Accel-Buffering: no');
header('Content-Type: text/octet-stream');
header('Cache-Control: no-cache');


//LONG RUNNING TASK
for($i = 0; $i < 10; $i++) {
    //Hard work!!
    sleep(1);
    
    //send status message
    $p = ($i+1)*10;	//Progress

    echo json_encode(array('message' => $p . '% complete. server time: ' . date('h:i:s', time()) , 'progress' => $p)) . PHP_EOL;
    
    //PUSH THE data out by all FORCE POSSIBLE
    ob_flush();
    flush();
}

sleep(1);
    
echo json_encode(array('message' => 'COMPLETE' , 'progress' => 100)) . PHP_EOL;

//PUSH THE data out by all FORCE POSSIBLE
ob_flush();
flush();

exit;