<?php
/*        SZÁMLA KIFIZETÉSEK IMPORT                                                                                                                    *\

  A POST-ként kapunk egy XML-t. Ezt feldolgozva töltjük fel a számlák kifizetések fül alatti bejegyzéseket.

\*                                                                                                                                         */
require_once '../weblap.php';
// file_put_contents('postok/'.date('Y-m-d_H:i:s').'_get.txt', serialize($_GET));
// file_put_contents('postok/'.date('Y-m-d_H:i:s').'_post.txt', serialize($_POST));
// Ha van postolt adatunk, akkor ellenőrizzük a beléptetést, és ha ez megvan, akkor tovább léptetjük az új számla feltöltéshez
if( $_POST /*|| isset($_GET['teszt'])*/ ) {

    // Tranzakciót indítunk
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Új kifizetések fül adat érkezett
    if( $_POST['szlakifxml'] /*|| isset($_GET['teszt'])*/ ) {
    
        file_put_contents('postok/kifizetesek.xml', utf8($_POST['szlakifxml']));

        require('../class/class.XmlIterator.php');

//        if( isset($_GET['teszt']) ) {
//            $xml = new XmlIterator('postok/kifizetesek_sajat.xml');
//        } else {
            $xml = new XmlIterator('postok/kifizetesek.xml');
//        }
        $xml -> strObjectTagname = 'projszla';

        // Az első ciklus csak egyetlen tag-et dolgoz fel: onteamid
        foreach ( $xml as $projszla ) {

            // Majd váltunk a szlakif tag-re, ezt járjuk be, mert ebből már több is lehet
            $xml -> strObjectTagname = 'szlakif';
            foreach ( $xml as $szlakif ) {

                // Ha csak egy sornak nincs ilyen adata, akkor megszakítjuk a működést
                if( !$szlakif['szlakifid'][0] ) {
                    echo '02 - Hiányzó szlakifid adat! Egyetlen most átadott kifizetés sor sem került letárolásra.';
                    mysqli_rollback($db_kapcs);
                    exit;
                }
                
                // A törlendő sorok a $projszla['onteamid'][0]-ben vannak
                $torlendok = explode(',', $projszla['szlakiftorol'][0]);
                foreach( $torlendok as $torlendo ) {
                    $torlendo = trim($torlendo);
                    if( $torlendo ) {
                        $sql = $adatbazis_kapcsolat->d('projektszamla_kifizetesek', "`szlakifid`='{$torlendo}' AND `projektszamla_recid`='{$projszla['onteamid'][0]}'");
                        if( $sql === false ) { $db_error = true; }
                    }
                }
                
                // XML-ben egy úgy nevezett "dhszla_id"-t kapunk. Némelyik értéklistának (így a Bankoknak) van "dhszla_id"-ja, ezzel azonosítjuk be a bankot.
                $bank   = $adatbazis_kapcsolat->s2('id', 'erteklista', "`dhszla_id`='".htmlspecialchars($szlakif['bank'])."'");
                $kcs_21 = $adatbazis_kapcsolat->s2('id', 'erteklista', "`dhszla_id`='".htmlspecialchars($szlakif['kcs_21'][0])."'");

                $lezeto_sor = $adatbazis_kapcsolat->s('recid', 'projektszamla_kifizetesek', "`szlakifid`='{$szlakif['szlakifid'][0]}'");
                if( $sql === false ) { $db_error = true; }
                if( $lezeto_sor ) {
                    $sql = $adatbazis_kapcsolat->u('projektszamla_kifizetesek',     array('projektszamla_recid',    'bank',      'kifizetes',              'br_ertek',            'dev_br',              'kcs_21',      'insert_usr',               'insert_dt'                ),
                                                                                    array($projszla['onteamid'][0], $bank['id'], $szlakif['kifizetes'][0], $szlakif['brutto'][0], $szlakif['dev_br'][0], $kcs_21['id'],  $szlakif['insert_usr'],    $szlakif['insert_dt'][0]   ),
                                                    "`szlakifid`='{$szlakif['szlakifid'][0]}'"
                                                    );
                } else {
                    $sql = $adatbazis_kapcsolat->i('projektszamla_kifizetesek',     array('szlakifid',              'projektszamla_recid',    'bank',      'kifizetes',              'br_ertek',            'dev_br',              'kcs_21',      'insert_usr',               'insert_dt'                ),
                                                                                    array($szlakif['szlakifid'][0], $projszla['onteamid'][0], $bank['id'], $szlakif['kifizetes'][0], $szlakif['brutto'][0], $szlakif['dev_br'][0], $kcs_21['id'],  $szlakif['insert_usr'],    $szlakif['insert_dt'][0]   )
                                                    );
                }
                if( $sql === false ) { $db_error = true; }

            }
            
            // Kifizetve pipa karbantartása
            $prszamla = $adatbazis_kapcsolat->s2('kcs_21 brutto dev_br', 'projektszamla', "`recid`='{$projszla['onteamid'][0]}'");
            if( $prszamla === false ) { $db_error = true; }

            if( $prszamla['kcs_21'] == KCS_21_HUF ) {
                $kif_mezo   = 'br_ertek';
                $br_mezo    = 'brutto';
            } else {
                $kif_mezo = 'dev_br';
                $br_mezo  = 'dev_br';
            }
            $kifizetesek = $adatbazis_kapcsolat->s2('SUM(`'.$kif_mezo.'`)', 'projektszamla_kifizetesek', "`projektszamla_recid`='{$projszla['onteamid'][0]}'");
            if( $kifizetesek === false ) { $db_error = true; }
            
            if( $kifizetesek['SUM(`'.$kif_mezo.'`)']>=$prszamla[$br_mezo] ) {
                $sql = $adatbazis_kapcsolat->u('projektszamla', array('kifizetve'), array(1), "`recid`='{$projszla['onteamid'][0]}'");
                if( $sql === false ) { $db_error = true; }
            } else {
                $sql = $adatbazis_kapcsolat->u('projektszamla', array('kifizetve'), array(0), "`recid`='{$projszla['onteamid'][0]}'");
                if( $sql === false ) { $db_error = true; }
            }

        }
        
    // Ha egyik sem érkezett    
    } else {
        echo '03 - Nem érkezett XML adat!';
    }
    
    // Tranzakció kommitolása, vagy rollback-ezése
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
    } else {
        echo '04 - Adatbázisba íráskor hiba történt!';
        mysqli_rollback($db_kapcs);
        exit;
    }

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
//                },
//                complete: function(xhr) {
//                    if( xhr.status != 200 ) {
//                        alert2(xhr.responseText, \'\', \'Hiba\');
//                    }
//                }
//            });
//        </script>';
//}
?>