<?php
//  if (!isset($_POST["vendejsz_adat0"])) {
//    die("Hibás kulcs !");
//  }
  define("CRLF", "<br>");
  // echo $_SERVER['SERVER_NAME'];
  // return;
  // var_dump($_POST["vendejsz_adat0"]);

  // valamelyik szájbabaszott php függvény space-t rak bele, ezért van telepid előtt space
  // if (!array_key_exists(" telepid", $_POST["vendejsz_adat0"])) {
  //   die('Hiányzó kulcs: telepid');
  // }
  // if (!array_key_exists(" v_d", $_POST["vendejsz_adat0"])) {
  //   die('Hiányzó kulcs: v_d');
  // }
  // var_dump($_POST);
  // return;
  echo "request PHP válasz: <br>";
  echo "--------------------<br>";

  $dbservername = "localhost";
  switch ($_SERVER['SERVER_NAME'] ) {
    case 'localhost':
      // code...
    break;
    case 'dev.onteam.hu':
      $dbusername = "onteam_teszt";
      $dbpassword = "iChtTeSbI4GXIlXg";
      $dbname = "onteam_dev";
    break;
    default:
      die("Hiba adatbázis megadásában !");
    break;
  }

  error_reporting(0); // Turn off all error reporting
  $conn = new mysqli($dbservername, $dbusername, $dbpassword, $dbname);
  // Check connection
  if ($conn->connect_error) {
    die("Adatbázishoz való kapcsolódás nem sikerült ! " . $conn->connect_error);
  }
  echo "Adatbázishoz való kapcsolódás sikeres !" . CRLF;
  // printf("Initial character set: %s\n", $conn->character_set_name();
  echo CRLF;
  // $conn->set_charset("latin1");
  // $conn->set_charset("utf8mb4");
  // printf("Current character set: %s\n", $conn->character_set_name());
  // $conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
  // echo CRLF;
  error_reporting(E_ALL);
  

  $keys = array_keys($_POST);

  $sql = 'INSERT INTO onteam_vendegejszakak (telepid, v_d, khsz_ert, ksz_ert, v_ert, ter_ert, rr_ert, ugyl_ert) VALUES ("1013", "20220901", "285", "198", "360", "713", "12", "419")';

echo 'd';
  if ($result = $conn->query($sql) ) {
echo 'a';
    echo "New record created successfully";
  } else {
echo 'b';
    echo "Error: " . $sql . "<br>" . $conn->error;
  }
echo 'c';

  $conn->close();
  return;
  // echo "prepare".CRLF;
  // $stmt = $conn->prepare('INSERT INTO onteam_vendegejszakak (telepid, v_d, khsz_ert, ksz_ert, v_ert, ter_ert, rr_ert, ugyl_ert) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE telepid = ?, v_d = ?, khsz_ert = ?, ksz_ert = ?, v_ert = ?, ter_ert = ?, rr_ert = ?, ugyl_ert = ?');
  // echo "bind_param".CRLF;
  // $stmt->bind_param("ssssssss", $telepid, $v_d, $khsz_ert, $ksz_ert, $v_ert, $ter_ert, $rr_ert, $ugyl_ert);
  //
  // $telepid = "1013";
  // $v_d = "20220901";
  // $khsz_ert = "285";
  // $ksz_ert = "198";
  // $v_ert = "360";
  // $ter_ert = "713";
  // $rr_ert = "12";
  // $ugyl_ert = "419";
  //
  // echo "execute".CRLF;
  // $stmt->execute();
  // $stmt->close();
  // $conn->close();
  // echo "close";
  // return;
  // echo json_encode($keys) . "<br>";
  // for ($i=0; $i < count($_POST["vendejsz_adat0"]); $i++) {
  //   // echo json_encode($_POST["vendejsz_adat" . $i]) . CRLF;
  //   echo json_encode(array_values($_POST["vendejsz_adat" . $i])) . CRLF;
  // }
  // return;

  // echo "count(keys): " . count($keys).CRLF;
  // return;

  // echo CRLF."try előtt".CRLF;
  // $conn->begin_transaction();
  // echo "begin_transaction()" . CRLF;
  // try {
  //   $values = [];
  //   for ($i = 0; $i < count($keys); $i++) {
  //     $j = 0;
  //     foreach ($_POST[$keys[$i]] as $key => $value) {
  //       $values[$j] = htmlspecialchars(stripslashes(trim($value)));
  //       $j++;
  //       // echo json_encode($values[$j]).CRLF;
  //     } // end foreach
  //     $stmt = $conn->prepare("INSERT INTO onteam_vendegejszakak (telepid, v_d, khsz_ert, ksz_ert, v_ert, ter_ert, rr_ert, ugyl_ert) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE telepid = ?, v_d = ?, khsz_ert = ?, ksz_ert = ?, v_ert = ?, ter_ert = ?, rr_ert = ?, ugyl_ert = ?");
  //     $stmt->bind_param("ssssssss", $telepid, $v_d, $khsz_ert, $ksz_ert, $v_ert, $ter_ert, $rr_ert, $ugyl_ert);
  //
  //     $telepid = $values[0];
  //     $v_d = $values[1];
  //     $khsz_ert = $values[2];
  //     $ksz_ert = $values[3];
  //     $v_ert = $values[4];
  //     $ter_ert = $values[5];
  //     $rr_ert = $values[6];
  //     $ugyl_ert = $values[7];
  //
  //     $stmt->execute();
  //
  //   } // end for
  //   $conn->commit();
  //   echo "commit()" . CRLF;
  // } catch (Exception $e) {
  //   $conn->rollback();
  //   echo "rollback()" . CRLF;
  //   echo 'Hiba : ' . $e->getCode() . " - " . $e->getMessage() . $e->getLine();
  //   // echo $e->getMessage();
  //   throw $e;
  // }
  //
  // echo CRLF."try után";
  // $conn->close();

// return;
// // ez jó saját megoldásban
  // $sql = 'INSERT INTO onteam_vendegejszakak ';
  // $sql2 = $savesql = "";
  // $lines =  [];
  // // echo "count(keys): " . count($keys).CRLF;
  // for ($i = 0; $i < count($keys); $i++) {
  //   $tablefields = $fieldvalues = $sqlduplicates = "";
  //   foreach ($_POST[$keys[$i]] as $key => $value) {
  //     $key = trim($key);
  //     // ezek nem lesznek benne, csak a saját csv-hez kellettek !
  //     // if ($key == 'recid' || $key == 'update_dt' || $key == 'insert_dt') {
  //     //   continue;
  //     // }
  //     // ez csak nekem kell egyenlőre kiiratni, hogy mi a sor értéke + mellé, hogy insert, update történt vagy nem történt művelet
  //
  //     // echo $key.": _".htmlspecialchars(stripslashes(trim($value)))."_".CRLF;
  //     $tablefields .= $key . ', ';
  //     $fieldvalues .= htmlspecialchars(stripslashes(trim($value))) . ", ";
  //     // duplikáláshoz hogy updateljen, ez a karakterlánc kell !
  //     $sqlduplicates .= $key . '=' . htmlspecialchars(stripslashes(trim($value))) . ', ';
  //   } //end of foreach
  //
  //   $tablefields = trim($tablefields, ', ');
  //   // echo "tablefields: _" . $tablefields . "_".CRLF;
  //   $fieldvalues = trim($fieldvalues, ', ');
  //   $lines[$i] = $fieldvalues;
  //   // $fieldvalues .= 'NOW()';
  //   // echo "fieldvalues: _" . $fieldvalues . "_".CRLF;
  //   $sqlduplicates = trim($sqlduplicates, ', ');
  //   // $sqlduplicates .= 'insert_dt=NOW()';
  //   // echo "sqlduplicates: _" . $sqlduplicates . "_".CRLF;
  //
  //   $sql2 = '(' . $tablefields . ') VALUES (' . $fieldvalues . ') ON DUPLICATE KEY UPDATE ' . $sqlduplicates;
  //   // echo $sql . $sql2 . "<br>";
  //   $savesql .= $sql . $sql2 . ";";
  //   // echo "--------------------------------<br>";
  // } // end of for
  // // echo $savesql . CRLF;
  // // return;
  // echo "try előtt".CRLF;
  // // return;
  // // adatmentés
  //
  // try {
  //   $conn->begin_transaction();
  //   echo "begin_transaction()" . CRLF;
  //   $i = 0;
  //   if ($conn->multi_query($savesql) === true) {
  //     do {
  //       $result = $conn->store_result();
  //       echo $i . "sor: " . ($conn->affected_rows <> 0 ? ($conn->affected_rows == 1 ? "insert" : "update") : "nincs művelet") . ". Értékek: " . $lines[$i]. CRLF;
  //       $i++;
  //       // printf("%d Row affected.\n", $conn->affected_rows);
  //     } while ($conn->more_results() && $conn->next_result());
  //   } //end if
  //   $conn->commit();
  //   echo "commit()" . CRLF;
  // } catch (Exception $e) {
  //   $conn->rollback();
  //   echo "rollback()" . CRLF;
  //   echo 'Hiba : ' . $e->getCode() . " - " . $e->getMessage() . $e->getLine();
  //   // echo $e->getMessage();
  //   throw $e;
  // }
  //
  // echo CRLF."try után";
  // $conn->close();
