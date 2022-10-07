<?php
  // var_dump($_POST);
  if (!isset($_POST["vendejsz_adat0"])) {
    die("Hibás kulcs !");
  }
  define("CRLF", "<br>");
  echo "request PHP válasz: <br>";
  echo "--------------------<br>";
  // die("PHP vége<br>");
  echo $_SERVER['SERVER_NAME'];
  // error_reporting(0); // Turn off all error reporting
  // error_reporting(E_ALL);
  $dbservername = "localhost";
  $dbusername = "onteam_teszt";
  $dbpassword = "iChtTeSbI4GXIlXg";
  $dbname = "onteam_dev";
  $conn = mysqli_connect($dbservername, $dbusername, $dbpassword, $dbname);
  echo json_encode($conn);
  // // Check connection
  // // if ($conn->connect_error) {
  // //   die("Adatbázishoz való kapcsolódás nem sikerült ! " . $conn->connect_error);
  if (!$conn) {
    die("Adatbázishoz való kapcsolódás nem sikerült ! ";
  }
  // echo "Adatbázishoz való kapcsolódás sikeres !" . CRLF;
  // // printf("Initial character set: %s\n", $conn->character_set_name();
  //
  // mysqli_close($conn);
