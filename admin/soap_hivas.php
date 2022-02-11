<?php
require_once '../weblap.php';

ini_set('log_errors',       '1'             );
ini_set('display_errors' ,  '1'             );
error_reporting(E_ERROR);

$client = new SoapClient(ANDOC_POST_KULD_URL_ELES);