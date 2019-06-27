<?php
/*        SZÁMLA KIFIZETÉSEK IMPORT                                                                                                                    *\

  A POST-ként kapunk egy XML-t. Ezt feldolgozva töltjük fel a számlák kifizetések fül alatti bejegyzéseket.

\*                                                                                                                                         */
//require_once '../weblap.php';
file_put_contents('postok/'.date('Y-m-d_H:i:s').'_get.txt', serialize($_GET));
file_put_contents('postok/'.date('Y-m-d_H:i:s').'_post.txt', serialize($_POST));
file_put_contents('postok/'.date('Y-m-d_H:i:s').'_request.txt', serialize($_REQUEST));

echo 'GET: '.serialize($_GET)."\n";
echo 'POST: '.serialize($_POST)."\n";
echo 'REQUEST: '.serialize($_REQUEST)."\n";
exit;

// Ha van postolt adatunk, akkor ellenőrizzük a beléptetést, és ha ez megvan, akkor tovább léptetjük az új számla feltöltéshez
if( $_POST || isset($_GET['teszt']) ) {

    echo serialize($_POST);
    file_put_contents('postok/'.date('Y-m-d_H:i:s').'.txt', serialize($_POST));
    file_put_contents('postok/'.date('Y-m-d_H:i:s').'_2.txt', serialize($_POST['xmlpszlakif']));
    file_put_contents('postok/'.date('Y-m-d_H:i:s').'_3.txt', serialize($_POST['valami']));

//    require('../class/class.XmlIterator.php');
//
//    $xml = new XmlIterator('postok/kifizetesek.xml');
//    $xml -> strObjectTagname = 'projszla';
//    
//    // Tranzakciót indítunk
//    $db_error = false;
//    mysqli_autocommit($db_kapcs, false);
//
//    // Az első ciklus csak egyetlen tag-et dolgoz fel: onteamid
//    foreach ( $xml as $projszla ) {
//        
//        // Majd váltunk a szlakif tag-re, ezt járjuk be, mert ebből már több is lehet
//        $xml -> strObjectTagname = 'szlakif';
//        foreach ( $xml as $szlakif ) {
//            
//            // XML-ben egy úgy nevezett "dhszla_id"-t kapunk. Némelyik értéklistának (így a Bankoknak) van "dhszla_id"-ja, ezzel azonosítjuk be a bankot.
//            $bank   = $adatbazis_kapcsolat->s2('id', 'erteklista', "`dhszla_id`='".htmlspecialchars($szlakif['bank'])."'");
//            $kcs_21 = $adatbazis_kapcsolat->s2('id', 'erteklista', "`dhszla_id`='".htmlspecialchars($szlakif['kcs_21'])."'");
//            
//            $sql = $adatbazis_kapcsolat->i('projektszamla_kifizetesek',     array('projektszamla_recid',    'bank',      'kifizetes',              'br_ertek',            'dev_br',              'kcs_21',  'insert_usr',              'insert_dt'                ),
//                                                                            array($projszla['onteamid'][0], $bank['id'], $szlakif['kifizetes'][0], $szlakif['brutto'][0], $szlakif['dev_br'][0], $kcs_21,   $szlakif['insert_usr'][0], $szlakif['insert_dt'][0]   )
//                                            );
//            if( $sql === false ) {
//                $db_error = true;
//            }
//// echo $adatbazis_kapcsolat->gp() . '<br>';
//        }
//
//    }
//    
//    // Tranzakció kommitolása, vagy rollback-ezése
//    if( !$db_error ) {
//        mysqli_commit($db_kapcs);
//    } else {
//        echo '02 - Adatbázisba íráskor hiba történt!';
//        mysqli_rollback($db_kapcs);
//    }

    echo 'OK';

} else {
    echo '01 - Nem érkezett postolt adat!';
}

//if( isset($_GET['teszt']) ) {
//    echo '
//        <script src="../js/jquery.js"></script>
//        <script>
//            $.ajax({
//                url: \'import_szamla_kifizetes.php\',
//                type: \'POST\',
//                data: {
//                    valami: \'valami\',
//                    fuggveny_neve: Date.now()
//                },
//                success: function (response) {
//                    console.log(response);
//                }
//            });
//        </script>';
//}
?>