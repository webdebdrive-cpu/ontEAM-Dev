<?php
  // echo "_".$_SERVER['SERVER_NAME']."_";
  // echo $_POST["vendejsz_adat"];
  // die();
  if (!isset($_POST["vendejsz_adat"])) {
    logmsg("Hibás kulcs !", true, true);
    die("Hibás kulcs !");
  }

  set_error_handler("on_error", E_ALL);
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  // mysqli_report(MYSQLI_REPORT_ALL);
  define("CRLF", "<br>");

  $dbservername = "localhost";
  switch ($_SERVER['SERVER_NAME']) {
    case 'localhost':
      $dbusername = "bjhtkt";
      $dbpassword = "h3343-79T";
      $dbname = "ppm30";
    break;
    case 'www.onteam.hu':
      $dbusername = "onteam_live";
      $dbpassword = "HMERDQgx0JHp124U";
      $dbname = "onteam_live";
    break;
    case 'dev.onteam.hu':
      $dbusername = "onteam_teszt";
      $dbpassword = "iChtTeSbI4GXIlXg";
      $dbname = "onteam_dev";
    break;
    case 'teszt.onteam.hu':
      $dbusername = "onteam_teszt";
      $dbpassword = "iChtTeSbI4GXIlXg";
      $dbname = "onteam_teszt";
    break;
    default:
      logmsg("Hiba adatbázis megadásában !", true, true);
      die("Hiba adatbázis megadásában !");
    break;
  }

  error_reporting(0); // Turn off all error reporting

  try {
    $conn = new mysqli($dbservername, $dbusername, $dbpassword, $dbname);
  } catch (mysqli_sql_exception $e) {
    // echo $conn;
    echo $e->getCode();
    logmsg($e->getCode() . " - Adatbázishoz való kapcsolódás nem sikerült !", true, true);
    die($e->getCode() . " - Adatbázishoz való kapcsolódás nem sikerült ! ");
  }
  error_reporting(E_ALL & ~E_ERROR);  //ezt csak ide !
  logmsg("Adatbázishoz való kapcsolódás sikeres !", true, true);
  echo CRLF . "Adatbázishoz való kapcsolódás sikeres !" . CRLF;

  $lines = explode(CRLF, $_POST["vendejsz_adat"]);
  $fieldarray = $fieldarray2 = $vendejsz_adat = [];
  $str = $lines[0];
  $columns = explode(",", $str);
  foreach ($columns as $key => $value) {
    array_push($fieldarray, $value);
  }

  for ($i = 1; $i < count($lines); $i++) {
    $newarr = $lines[$i];
    // echo $newarr.CRLF;
    $columns = explode(",", $newarr);
    $zz = [];
    for ($j = 0; $j < count($columns); $j++) {
      $zz += [$fieldarray[$j] => $columns[$j]];
    }
    array_push($fieldarray2, $zz);
  }

  $keys = $fieldarray2;
  echo "count(keys): " . count($keys).CRLF;
  if ($_SERVER['SERVER_NAME'] == 'localhost') {
    $table = 'vejsz';
  } else {
    $table = 'onteam_vendegejszakak';
  }

  // SQL injection ellen: oszlopnévként csak a tábla valóban létező oszlopai fogadhatók el,
  // az értékek pedig prepared statement-tel kerülnek be (korábban nyersen, multi_query-vel futott, amivel bármilyen SQL lefuttatható volt)
  $table_columns = [];
  $result_columns = $conn->query('SHOW COLUMNS FROM `' . $table . '`');
  while ($row = $result_columns->fetch_assoc()) {
    $table_columns[] = $row['Field'];
  }

  $rows = $lines = [];
  for ($i = 0; $i < count($keys); $i++) {
    $row_fields = $row_values = [];
    foreach ($keys[$i] as $key => $value) {
      $key = trim($key);
      if ($_SERVER['SERVER_NAME'] == 'localhost' && $key == 'telephely_id') {
        $key = 'telepid';
      }
      if (!in_array($key, $table_columns, true)) {
        logmsg("Ismeretlen oszlop: " . $key, true, true);
        die("Ismeretlen oszlop: " . htmlspecialchars($key));
      }
      $row_fields[] = $key;
      $row_values[] = htmlspecialchars(stripslashes(trim($value)));
    } //end of foreach

    $rows[$i]  = ['fields' => $row_fields, 'values' => $row_values];
    $lines[$i] = implode(',', $row_values);
  } // end of for

  try {
    $conn->begin_transaction();
    logmsg("begin_transaction()", false, true);
    echo "begin_transaction()" . CRLF;
    foreach ($rows as $i => $row) {
      // Üres sort (pl. záró sortörés után) kihagyunk
      if (!$row['fields'] || implode('', $row['values']) === '') {
        continue;
      }
      $tablefields   = '`' . implode('`, `', $row['fields']) . '`';
      $placeholders  = implode(', ', array_fill(0, count($row['fields']), '?'));
      // duplikáláshoz hogy updateljen, ez a karakterlánc kell !
      $sqlduplicates = implode(', ', array_map(function ($field) { return '`' . $field . '`=VALUES(`' . $field . '`)'; }, $row['fields']));

      $stmt = $conn->prepare('INSERT INTO `' . $table . '` (' . $tablefields . ') VALUES (' . $placeholders . ') ON DUPLICATE KEY UPDATE ' . $sqlduplicates);
      $stmt->bind_param(str_repeat('s', count($row['values'])), ...$row['values']);
      $stmt->execute();

      $str = $i . " sor: " . ($stmt->affected_rows <> 0 ? ($stmt->affected_rows == 1 ? "insert" : "update") : "nincs művelet") . ". Értékek: " . $lines[$i];
      logmsg($str, false, true);
      echo $str . CRLF;
      $stmt->close();
    }
    $conn->commit();
    logmsg("commit()", false, true);
    echo "commit()" . CRLF;
  } catch (Exception $e) {
    $conn->rollback();
    logmsg("rollback()", false, true);
    echo "rollback()" . CRLF;
    echo 'Hiba : ' . $e->getCode() . " - " . $e->getMessage() . CRLF. 'Hibás sor: ' . $e->getLine();
    logmsg('Hiba : ' . $e->getCode() . " - " . $e->getMessage() . "\n" . 'Hibás sor: ' . $e->getLine(), false, true);
    // echo $e->getMessage();
    throw $e;
  }
  $conn->close();

  function logmsg($str, $isdt, $iscrlf) {
    $logstr = "";
    if ($isdt === true) {
      $logstr = "\r\n" . date('Y-m-d H:i:s') . " - " . gethostname() . "\n";
    }
    $iscrlf = ($iscrlf === true) ? "\r\n" : "\n";
    $logstr .= $str . $iscrlf;
    file_put_contents("log/vejsz_request.log", $logstr, FILE_APPEND);
  }

  function on_error($error_no, $error, $filename, $linenumber) {
    $currentdatetime = date('Y-m-d H:i:s');
    $str = $currentdatetime . " - " . gethostname() . "\n";
    $str .= $error_no . " - " . $error . " in " . $filename . " at line " . $linenumber . "\r\n\r\n";
    echo $str . CRLF;
    file_put_contents("log/vejsz_request_error.log", $str, FILE_APPEND);
  }
