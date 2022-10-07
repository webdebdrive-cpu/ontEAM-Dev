<?php
  error_reporting(E_ALL);
  define("CRLF", "<br>");
  echo "Program indul...".CRLF;
  $file = fopen('onteam_sample.csv', 'r');
  if (!$file) {
    die("Hiba csv fájl megnyitásakor !");
  }
  // ahány sor van csv-ben annyi tömböt csinálunk
  $lines = [];
// Szállodakód
// Dátum
// Kiadható szobaszám
// Kiadott szobaszám
// Vendégéjszaka
// Cover szám
// Rendezvény létszám
// Uszodai gyógy látogatók
// Kovács Laci által küldött adat: onteam_sample.csv email: 2022-09-16 15-25

  $lines[0] = '"telepid";"v_d";"khsz_ert";"ksz_ert";"v_ert";"ter_ert";"rr_ert";"ugyl_ert";';
  // echo $lines[0]."<br>";
  $i = 1;
  while(!feof($file)) {
    $lines[$i] = fgets($file);
    // echo $i." ".fgets($file)."<br>";
    // echo $i. " ".$lines[$i]."<br>";
    $lines[$i] = trim($lines[$i]);  // kell és ide és így simán para nélkül a trim !!!
    // echo "_".$lines[$i]."_"."<br>";
    $i++;
  }
  fclose($file);

  if (!strpos($lines[0], ";")) {
    echo "Hibás érték: " . CRLF;
    print_r($lines);
    die();
  }
  // üres tömb elemeket törlünk
  // echo "count(lines): " . count($lines) . CRLF;
  for ($i = count($lines) - 1; $i >= 0; $i--) {
    if ($lines[$i] == "") {
      unset($lines[$i]);
    }
  }
  // ez csak kiiratáshoz kell !
  // for ($i=0; $i < count($lines); $i++) {
  //   echo $i. " ".$lines[$i]."<br>";
  // }

  // pontosvesszőket vesszőre alakítjuk, hogy explode-dal tömb legyen belőlük, de a dupla időzőjelet is át kell alakítani, mert olyan karakternek értelmezi fogadó oldal, ami nem kezelhető
  $fieldarray = $newarr = [];
  for ($i = 0; $i < 1; $i++) {
    $newarr[$i] = str_replace(";", ", ", $lines[$i]);
    $newarr[$i] = str_replace('"', "", $newarr[$i]);
    $columns = explode(",", $newarr[$i]);
    foreach ($columns as $key => $value) {
      array_push($fieldarray, $value);
    }
  }

  // print_r($fieldarray);
  // echo "<br><br>";

  $newarr = $zz = $fieldarray2 = [];
  // itt 1-től megyünk és count($lines) -ig, nem veszünk le belőle 1-et !!!
  for ($i = 1; $i < count($lines); $i++) {

    $newarr = $lines[$i];
    // echo "i: ".$i."<br>";
    // print_r($lines[$i]);
    // echo "<br>***<br>";
    // return;
    $newarr = str_replace(";", ", ", $newarr);
    $newarr = str_replace('"', "'", $newarr);
    // $newarr[$i] = str_replace('"', "", $newarr[$i]);
    $columns = explode(",", $newarr);
    // $j = 0;
    // foreach ($columns as $value) {
    //   $zz[$j] = array($fieldarray[$j] => $value);
    //   // print_r($zz);
    //   // echo "<br>***<br>";
    //   $j++;
    // }
    $zz = [];
    // echo "oszlopok száma: ".count($columns);
    for ($j = 0; $j < count($columns) - 1; $j++) {
      // print_r($columns[$j]);
      // echo "<br>---<br>";
      $zz += [$fieldarray[$j] => $columns[$j]];
    }
    // return;
    array_push($fieldarray2, $zz);
    // echo "<br><br>";
    // foreach ($columns as $column) {
    //   // print_r($column);
    //    array_push($fieldarray, $column);
    // }
  }

  // echo "<br>-------------<br>";
  // print_r($fieldarray2);

  // return;
  // $i = 0;
  // $telephely_ids = array(
  //   $i => array(
  //       "v_ert" => "185",
  //       "v_d" => "2022-09-14",
  //       "telephely_id" => "1040",
  //   ),
  //   $i + 1 => array(
  //       "v_ert" => "220",
  //       "v_d" => "2022-09-14",
  //       "telephely_id" => "1060",
  //   ),
  //   $i + 2 => array(
  //       "v_ert" => "320",
  //       "v_d" => "2022-09-15",
  //       "telephely_id" => "2060",
  //   )
  // );
  // print_r($telephely_ids);
  // echo "<br>-------------<br>";

  // $url = "http://localhost/Tibor/szt4/tests/vejsz_request.php";

  $url = "https://dev.onteam.hu/andoc3/vejsz_request.php";
  $postdata = http_build_query($fieldarray2, 'vendejsz_adat');
  $opts = array('http' =>
      array(
          'method' => 'POST',
          'header' => 'Content-type: application/x-www-form-urlencoded',
          'content' => $postdata,
          'Content-length' => strlen($postdata)
      )
  );
  $context = stream_context_create($opts);
  $result = file_get_contents($url, false, $context);
  echo $result;
