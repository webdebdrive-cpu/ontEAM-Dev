<html lang="hu-HU">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <style>
    * {
      box-sizing: border-box;
      padding: 0;
      margin: 0;
      font-size: 15px;
      font-family: Arial, Helvetica, sans-serif;
    }

    body {
      -webkit-display: flex;
      display: flex;
      align-self: center;
      justify-content: space-around;
      margin: 1rem;
    }

    body>div {
      height: 80vh;
      height: 80svh;
      width: 48%;
      overflow-y: auto;
      border: 2px solid brown;
    }
  </style>
</head>

<body>
  <div id="div-result"></div>
  
  <?php
  define("BR", "<br>"); //ez a böngészőben történő megjelenésnél jó, ha file-ba írunk, akkor CR vagy CRLF kell !
  set_error_handler("on_error", E_ALL);
  
  $current_time = date("Ymd_his");
  $filedir = str_replace("\\", "//", __DIR__ . "//saves//" . $current_time);   
  echo $filedir . BR;
  echo $_SERVER['SERVER_NAME']. BR;
  // if (mkdir($filedir)) {
  //   echo "mappa létrejött" . BR;
  // } else {
  //   echo "mappa NEM jött létre !";
  //   return;
  // }
  $file = $filedir . "//savetables.sql";
  // echo "<br>" . $file;
  $filejs = "/saves/savetables_" . $current_time . ".sql";
  
  ?>
  <script>
    const divresult = document.getElementById("div-result");
  </script>

  <?php    
  
  define("CR", chr(10));
  define("LF", chr(13));
  define("CRLF", CR . LF);
  
  define("RN", '\r\n');
  define("DBHOST", "localhost");
  define("DBNAME", "ppm30");
  define("DBUSERNAME", "bjhtkt");
  define("DBPASSWORD", "h3343-79T");

  // class dbh
  // {

  //   protected $dbservername;
  //   protected $dbusername;
  //   protected $dbpassword;
  //   protected $dbname;
  //   public $conn;

  //   public function __construct()
  //   {
  //     $this->dbservername = "localhost";
  //     if ($_SERVER['SERVER_NAME'] == 'localhost') {
  //       $this->dbusername = DBUSERNAME;
  //       $this->dbpassword = DBPASSWORD;
  //       $this->dbname = DBNAME;
  //     } elseif ($_SERVER['SERVER_NAME'] == 'dev.onteam.hu') {
  //       $this->dbusername = "onteam_teszt";
  //       $this->dbpassword = "iChtTeSbI4GXIlXg]67?";
  //       $this->dbname = "onteam_dev";
  //     } elseif ($_SERVER['SERVER_NAME'] == 'teszt.onteam.hu') {
  //       $this->dbusername = "onteam_teszt";
  //       $this->dbpassword = "iChtTeSbI4GXIlXg";
  //       $this->dbname = "onteam_teszt";
  //     } else {
  //       $this->dbusername = "szinvatrendhu";
  //       $this->dbpassword = "vikike";
  //       $this->dbname = "szinvatrendhu1";
  //     }
      
  //     error_reporting(0); // Turn off all error reporting

  //     // try {
  //     //   $this->conn = new mysqli($this->dbservername, $this->dbusername, $this->dbpassword, $this->dbname);
  //     // } catch (mysqli_sql_exception $e) {
  //     //   // echo $conn;
  //     //   echo $e->getCode();
  //     //   // logmsg($e->getCode() . " - Adatbázishoz való kapcsolódás nem sikerült !", true, true);
  //     //   die($e->getCode() . $e->getMessage() . " - Adatbázishoz való kapcsolódás nem sikerült ! ");
  //     // }

  //     // error_reporting(E_ALL & ~E_ERROR);  //ezt csak ide !
 
  //     // $this->conn = new mysqli($this->dbservername, $this->dbusername, $this->dbpassword, $this->dbname);
  //     // $conn = $this->conn;
  //     // if ($conn->connect_errno) {
  //     //   // echo $conn->connect_error;
  //     //   $message = 'Adatbázishoz való kapcsolódás sikertelen ! \r\n\r\n Hiba: ' . $conn->connect_errno;
  //     //   echo "
  //     //   <script>
  //     //     alert('" . $message . "');
  //     //   </script>";
  //     //   error_reporting(E_ALL); // Report all PHP errors
  //     //   // header("Location: ../index.php?error=mysqlerror");
  //     //   die();
  //     // } else {
  //     //   error_reporting(E_ALL);
  //     //   mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  //     //   $conn->set_charset("utf8mb4");
  //     // }
      
  //   } // end of __construct

  //   public static function getconn()
  //   {
  //     return self::$conn;
  //   }

  //   public function getdbname()
  //   {
  //     return $this->dbname;
  //   }
  // } // end of class dbh

  // $dbh = new dbh();
  // $conn = $dbh->getconn();
  // $dbname = $dbh->getdbname();

  $dbservername = "localhost";
  if ($_SERVER['SERVER_NAME'] == 'localhost') {
    $dbusername = DBUSERNAME;
    $dbpassword = DBPASSWORD;
    $dbname = DBNAME;
  } elseif ($_SERVER['SERVER_NAME'] == 'dev.onteam.hu') {
    $dbusername = "onteam_teszt";
    $dbpassword = "iChtTeSbI4GXIlXg";
    $dbname = "onteam_dev";
  } elseif ($_SERVER['SERVER_NAME'] == 'teszt.onteam.hu') {
    $dbusername = "onteam_teszt";
    $dbpassword = "iChtTeSbI4GXIlXg";
    $dbname = "onteam_teszt";
  }
  error_reporting(0); // Turn off all error reporting
  error_reporting(E_ALL);
  echo BR;
  try {
    $conn = new mysqli($dbservername, $dbusername, $dbpassword, $dbname);    
    // mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    // $conn->set_charset("utf8mb4");
  } catch (mysqli_sql_exception $e) {    
    die($e->getCode() . $e->getMessage() . " - Adatbázishoz való kapcsolódás nem sikerült ! ");
  }
  
  echo "kapcsolódás sikeres !". BR;
  // return;
  $sorszam = 0;
  
  ?>

  <script>
    const dbname = "<?php echo $dbname ?>";
    const file = "<?php echo htmlspecialchars($filejs) ?>";
    let html, sql;
    html = `
    <p>${dbname} adatbázis mentése: </p>
    <p>${file}</p>
    <p>--------------------------</p>
    `;
  </script>

  <?php
  if (!$conn->select_db($dbname)) {
    die("Hiba:  . {$conn->error}");
  }
  $sorszam++;
  $sql = "SHOW TABLES FROM " . $dbname . ";";
  ?>
  <script>
    sql = "<?php echo $sorszam . '.) ' . $sql; ?>";
    html += `<p>${sql}</p>`;
  </script>
  <?php

  $result_showtables = $conn->query($sql);
  if (!$result_showtables) {
    die("Hiba:  . {$conn->error}");
  }
  while ($row = $result_showtables->fetch_row()) {
    $arr_show_tables[] = $row[0];
  }

  //
  $t = $x = -1;
  foreach ($arr_show_tables as $table) {
    if (substr($table, 0, 3) === "pma" || $table === "tbllog" || $table === "tblerror" || $table === "tblukep") {
      continue;
    }
    $sqldump = "";
    $t++;
    if ($t === 2) {
      break;
    }
    $sql = "SHOW CREATE TABLE `" . $table . "`;";
    $sorszam++;
  ?>
    <script>
      sql = "<?php echo $sorszam . '.) ' . $sql . BR; ?>";
      html += `<p>${sql}</p>`;
    </script>
  <?php


    $result_showcreatetables = $conn->query($sql);
    if (!$result_showcreatetables) {
      die("Hiba:  . {$conn->error}");
    }
    $row_createtable = $result_showcreatetables->fetch_assoc();
    $sqldump .= "DROP TABLE IF EXISTS `" . $table . "`;" . CR;
    $sql_create_table = $row_createtable["Create Table"];
    $arr_create_table = explode(",", $sql_create_table);
    $cmd = "";
    $i = 0;
    $count_arr = count($arr_create_table);

    // ez jó !
    foreach ($arr_create_table as $key => $value) {
      if ($i === $count_arr - 1) { //utolsó sor
        // $lastrow = $value;
        $sqldump .= $value . ";";
      } else {
        $sqldump .= $value . ",";
      }
      $i++;
      // echo $key . ": " . $value . BR;
    }

    $sqldump .= CR . CR;
    // echo $sqldump;
    file_put_contents($file, $sqldump, FILE_APPEND);
  }
  ?>
  <script>
    const template = document.createElement('template');
    template.innerHTML = html;
    divresult.appendChild(template.content);
  </script>

  <?php
  
  $records = [];

  echo "<div>";
  
  foreach ($arr_show_tables as $table) {
    $x++;
    if ($x === 2) {
      break;
    }
    $sql = "SHOW COLUMNS FROM " . $table . ";";
    $sorszam++;
  ?>
    <script>
      sql = "<?php echo $sorszam . '.) ' . $sql . BR; ?>";
      divresult.innerHTML += `<p>${sql}</p>`;
    </script>
  <?php
    $result_showcolumns = $conn->query($sql);
    if (!$result_showcolumns) {
      die("Hiba:  . {$conn->error}");
    }
    $arr_show_columns = [];
    while ($row = $result_showcolumns->fetch_assoc()) {
      // print_r($row);
      // echo BR;
      $arr_show_columns[] = $row["Field"];
    }
    // echo BR;
    if (in_array('recid', $arr_show_columns)) {
      // echo 'van recid mező' . BR;
      $sql = "SELECT MAX(recid) AS maxrecid, Count(*) AS rekordok_szama FROM " . $table;
    } else {
      // echo 'nincs recid mező' . BR;
      $sql = "SELECT 000000000 AS maxrecid, Count(*) AS rekordok_szama FROM " . $table;
    }
    $result_reccount = $conn->query($sql);
    if (!$result_reccount) {
      die("Hiba:  . {$conn->error}");
    }
    // így kell, mert tömbbe jön a 2 érték
    $row_reccount = $result_reccount->fetch_assoc();
    // ez jó !!!
    // $fielddata = ["Field" => $table];
    // $records[$table] = array_merge($fielddata, $row_reccount);
    $records[$table] = $row_reccount;
  } // end of foreach ($arr_show_tables as $table)

  // $fieldterm = "'\\t'";
  // $lineterm = "'\\n'";
  $fieldterm = "'_@@@_'";
  $lineterm = "'\n'";
  foreach ($records as $key => $value) {
    $recid = 0;
    $limit = 50000;
    // $akt_file = __DIR__ . "\saves" . addslashes($key) . "_" . str_pad("1", 3, "0", STR_PAD_LEFT) . ".sql";
    // echo $key.BR;
    $maxrecid = $value["maxrecid"];
    // echo $maxrecid;
    $number_of_cycles = ceil($value["rekordok_szama"] / $limit);
    // echo $value["rekordok_szama"] . " " . ceil($value["rekordok_szama"] / $limit) . BR;
    for ($i = 1; $i <= $number_of_cycles; $i++) {
      // if ($i > 1) {
      //   // $sqlsorszam = ($i <= 9) ? "0" : "" . $i;
      //   $sqlsorszam = "_" . str_pad($i, 3, "0", STR_PAD_LEFT);
      //   echo 'sqlsorszam: ' . $sqlsorszam . BR;
      //   $akt_file = $key . "_" . $sqlsorszam . ".sql";
      //   echo $akt_file . BR;
      // }
      // $akt_file = $key . "_" . str_pad($i, 3, "0", STR_PAD_LEFT) . ".sql";
      $akt_file = $filedir . "//" . $key . "_" . str_pad($i, 3, "0", STR_PAD_LEFT) . ".sql";
      $limitstr = $recid . ", " . $limit;
      // echo ($maxrecid === "0") ? "nulla" : "nem nulla" . BR;
      // DISTINCT SQL_CALC_FOUND_ROWS *
      if ($maxrecid === "0") {
        $sql = "
        SELECT SQL_CALC_FOUND_ROWS * INTO OUTFILE '" . $akt_file . "' 
        FIELDS TERMINATED BY " . $fieldterm . "
				LINES TERMINATED BY " . $lineterm . "
				FROM " . $key . "
				LIMIT " . $limitstr . "
				LOCK IN SHARE MODE";
      } else {
        $sql = "
        SELECT SQL_CALC_FOUND_ROWS * INTO OUTFILE '" . $akt_file . "' 
        FIELDS TERMINATED BY " . $fieldterm . "
        LINES TERMINATED BY " . $lineterm . "
        FROM " . $key . "
        WHERE recid <= " . $maxrecid . "
        LIMIT " . $limitstr . "
        LOCK IN SHARE MODE";
      }
      echo "<p>" . $sql . "</p>";
      $result_siof = $conn->query($sql);
      if (!$result_showcreatetables) {
        die("Hiba:  . {$conn->error}" . BR . $conn->errno);
      }
      echo "<p>OK</p><br>";
      $recid = $recid + $limit;
    }
  }

  // pre($records);
  echo "</div>";


  function pre($arr)
  {
    echo "<pre>";
    print_r($arr);
    echo "</pre>";
  }

  function on_error($error_no, $error, $filename, $linenumber) {
    $currentdatetime = date('Y-m-d H:i:s');
    $str = $currentdatetime . " - " . gethostname() . BR;
    $str .= $error_no . " - " . $error . " in " . $filename . " at line " . $linenumber . BR;
    echo $str . BR;
    file_put_contents("savetables_siof.log", $str, FILE_APPEND);
  }


  ?>

</body>

</html>