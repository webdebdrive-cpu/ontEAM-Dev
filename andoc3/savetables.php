<?php
define("CR", chr(10));
define("LF", chr(13));
define("CRLF", CR . LF);
define("BR", "<br>"); //ez a böngészőben történő megjelenésnél jó, ha file-ba írunk, akkor CR vagy CRLF kell !
define("RN", '\r\n');
define("TAB", chr(9));
define("CHR0", chr(0));
define("FLGS_COL", 200);
define("RIGHT_ARROW", "&#8594;");
define("APP_SHORTNAME", "itthon2");
define("APP_NAME", "Itthon2");
if (isset($_SERVER['HTTPS'])) {
  define("APP_PROTOCOL", "https");
} else {
  define("APP_PROTOCOL", "http");
}
define("APP_MAIN_DIR", "/Tibor/itthon2"); // ez most csak localhoston van, ezért nem kínlódunk vele
define("APP_PATH", APP_PROTOCOL . "://" . $_SERVER['SERVER_NAME'] . APP_MAIN_DIR);
define("DBHOST", "localhost");
define("DBNAME", "itthon2");
define("DBUSERNAME", "bjhtkt");
define("DBPASSWORD", "h3343-79T");

class dbh
{

  protected $dbservername = "localhost";
  protected $dbusername;
  protected $dbpassword;
  protected $dbname;
  public static $conn;

  public function __construct()
  {
    if ($_SERVER['SERVER_NAME'] == 'localhost') {
      $this->dbusername = DBUSERNAME;
      $this->dbpassword = DBPASSWORD;
      $this->dbname = DBNAME;
    } elseif ($_SERVER['SERVER_NAME'] == 'dev.onteam.hu') {
      $this->dbusername = "onteam_teszt";
      $this->dbpassword = "iChtTeSbI4GXIlXg";
      $this->dbname = "onteam_dev";
    } elseif ($_SERVER['SERVER_NAME'] == 'teszt.onteam.hu') {
      $this->dbusername = "onteam_teszt";
      $this->dbpassword = "iChtTeSbI4GXIlXg";
      $this->dbname = "onteam_teszt";
    } else {
      $this->dbusername = "szinvatrendhu";
      $this->dbpassword = "vikike";
      $this->dbname = "szinvatrendhu1";
    }
    error_reporting(0); // Turn off all error reporting    

    self::$conn = new mysqli($this->dbservername, $this->dbusername, $this->dbpassword, $this->dbname);
    $conn = self::$conn;
    if ($conn->connect_errno) {
      $message = 'Adatbázishoz való kapcsolódás sikertelen ! \r\n\r\n Hiba: ' . $conn->connect_errno;
      echo "
        <script>
          alert('" . $message . "');
        </script>";
      error_reporting(E_ALL); // Report all PHP errors
      header("Location: ../index.php?error=mysqlerror");
      die();
    } else {
      error_reporting(E_ALL);
      mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
      $conn->set_charset("utf8mb4");
    }
  } // end of __construct

  public static function getconn()
  {
    return self::$conn;
  }

  public function getdbname()
  {
    return $this->dbname;
  }
} // end of class dbh

try {
$dbh = new dbh();
$conn = $dbh->getconn();
$dbname = $dbh->getdbname();
echo $dbname . ' adatbázis mentése: ' . BR . '--------------------------' . BR;
// 
$file = __DIR__ . "/saves/savetables_" . date("Ymd_his") . ".sql";
echo $file . BR . BR;
$sorszam = 0;

if (!$conn->select_db($dbname)) {
  die("Hiba:  . {$conn->error}");
}
$sorszam++;
$sql = "SHOW TABLES FROM " . $dbname . ";";
echo $sorszam . ".) " . $sql . BR;
$result_showtables = $conn->query($sql);
if (!$result_showtables) {
  die("Hiba:  . {$conn->error}");
}
while ($row = $result_showtables->fetch_row()) {
  $arr_show_tables[] = $row[0];
}
$t = -1;
foreach ($arr_show_tables as $table) {
  if (substr($table, 0, 3) === "pma" || $table === "tbllog" || $table === "tblerror" || $table === "tblukep") {
    continue;
  }
  $sqldump = "";
  $t++;
  if ($t === 30) {
    break;
  }
  $sql = "SHOW CREATE TABLE `" . $table . "`;";
  $sorszam++;
  echo $sorszam . ".) " . $sql . BR;
  $result_showcreatetables = $conn->query($sql);
  if (!$result_showcreatetables) {
    die("Hiba:  . {$conn->error}");
  }
  $rowvalues = $result_showcreatetables->fetch_assoc();
  $sqldump .= "DROP TABLE IF EXISTS `" . $table . "`;" . CR;
  $sql_create_table = $rowvalues["Create Table"];
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
  file_put_contents($file, $sqldump, FILE_APPEND );

  // ez arra való, ha ki kellene venni auto_increment részt, hogy nehogy az insertek végén az értéke más legyen. Php myadmin kiveszi és adatok hozzáadása után adja hozzá indexeket és utána módosítja azt a mezőt, amelyik auto_increment !

  // echo $lastrow;
  // echo BR;
  // $pos = stripos($lastrow, "auto_increment");
  // echo $pos;
  // echo BR;
  // $char1 = substr($lastrow, $pos);
  // echo $char1;
  // echo BR;
  // $pos2 = stripos($char1, " ");
  // echo $pos2;
  // echo BR;
  // echo substr_replace($lastrow, "", $pos, $pos2);
  // return;
  // idáig lett commentezve

  // mező típusok - itt nem szabad backtick-et ` használni !!!  
  $sql = "SELECT column_name, data_type, column_default FROM information_schema.columns WHERE table_schema = '" . $dbname . "' AND table_name = '" . $table . "';";
  $sorszam++;
  echo $sorszam . ".) " . $sql . BR;
  $result_columntypes = $conn->query($sql);
  if (!$result_columntypes) {
    die("Hiba:  . {$conn->error}");
  }
  $colnames = $coltypes = $coldefault = [];
  $number_of_fields = $result_columntypes->num_rows;
  $sqlinsertcmd = "INSERT INTO `" . $table . "` (";
  for ($i = 0; $i < $number_of_fields; $i++) {
    while ($row = $result_columntypes->fetch_assoc()) {
      $colnames[] = $row["column_name"];
      $sqlinsertcmd .= "`" . $row["column_name"] . "`,";
      $coltypes[] = strtolower($row["data_type"]);
      $coldefault[] = $row["column_default"];
    }
  }
  $sqlinsertcmd = rtrim($sqlinsertcmd, ", ") . ") VALUES " . CR;
  // print_r($colnames);
  // echo BR."--------------------".BR;
  // print_r($coltypes);
  // echo BR."--------------------".BR;
  // print_r($coldefault);
  // return;

  // file_put_contents($file, $sqlinsertcmd, FILE_APPEND );
  // echo "return";
  // return;
  
  // adatok lekérése insert_dt csökkenőben 
  $limit = 0;
  $limit2 = 500;
  for ($c = 0; $c < 2; $c++) {
    $sqldump = "";    
    $sql = "SELECT * FROM `" . $table . "` ORDER BY insert_dt DESC LIMIT " . $limit . ", "  . $limit2;
    $sorszam++;
    echo $sorszam . ".) " . $sql . BR;
    $result_selecttable = $conn->query($sql);
    if (!$result_selecttable) {
      die("Hiba:  . {$conn->error}");
    }

    $number_of_rows = $conn->affected_rows;
    if ($number_of_rows === 0) {
      continue;
    }

    $sqldump .= "LOCK TABLES `" . $table . "` WRITE;" . CR;

    while ($row = $result_selecttable->fetch_row()) {
      $sqlinsertvalues = "(";
      for ($k = 0; $k < $number_of_fields; $k++) {
        // echo $colnames[$k] . ": ". $row[$k] . " " . $coltypes[$k] . " " . $coldefault[$k] . BR;
        $fieldvalue = $conn->real_escape_string($row[$k]);
        switch ($coltypes[$k]) {
          case 'tinyint':
            $numeric = true;
            break;
          case 'smallint':
            $numeric = true;
            break;
          case 'mediumint':
            $numeric = true;
            break;
          case 'int':
            $numeric = true;
            break;
          case 'bigint':
            $numeric = true;
            break;
          default:
            $numeric = false;
            break;
        }
        if ($numeric) {
          $sqlinsertvalues .= " " . $fieldvalue . ",";
        } else {
          if ($coldefault[$k] === "NULL" && empty($fieldvalue)) {
            $sqlinsertvalues .= " NULL,";
          } else {
            $sqlinsertvalues .= " '" . $fieldvalue . "',";
          }
        }
      }
      $sqlinsertvalues = rtrim($sqlinsertvalues, ",") . ")," . CR;
      $sqlinsertcmd .= $sqlinsertvalues;
    } // end of while($row = $result_selecttable->fetch_row())
    $sqlinsertcmd = rtrim($sqlinsertcmd, CR . ",") . ";" . CR;
    $sqldump .= $sqlinsertcmd;
    $sqldump .= "UNLOCK TABLES;" . CR . CR;

  // echo "return";
  // echo BR."-----------------------".BR;
  // echo $sqldump;
  // echo BR."-----------------------".BR;
  // return;
    // file-ba írás  
    file_put_contents($file, $sqldump, FILE_APPEND );
    $limit += $limit2;
  } // end of for $c
} //end of foreach ($arr_show_tables as $table)
$sorszam++;
echo $sorszam . ".) " . "Kész!";
} catch (Exception $ex) {
  echo $ex->getMessage();
  die();
}