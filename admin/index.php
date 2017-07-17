<?php
/*        ADMINISZTRÁCIÓS FELÜLET                                                                                                          *\
  
  Admin mappa elérési útvonala a szerveren (jelsszóhoz): $_SERVER['DOCUMENT_ROOT']
  
  Telepítés: A teljes admin mappát át kell másolni (class, css, js mappákat is használja, de ezek már elvileg jelen vannak).
	 admin/index.php-t megnyitva telepítsd az admint. 
  
  Súgó és dokumentáció: index.php?sugo     
  
\*                                                                                                                                         */

if(isset($_GET['info'])){
    phpinfo();
    exit;
}


require_once('../weblap.php');

//Ha egy tálbának utólag adsz egy loginnev nevű oszlopnevet
if (isset($_GET['loginnev_gyart'])) {
    
    $tabla = 'munkalap';
    
    $sorok = $adatbazis_kapcsolat->s('recid insert_usr', $tabla);
    foreach($sorok as $sor){
        $loginnev = $adatbazis_kapcsolat->s('nev', 'login', "`teljes_nev`='{$sor['insert_usr']}'");
        $loginnev = $loginnev[0]['nev'];
        
        $adatbazis_kapcsolat->u($tabla, array('loginnev'), array($loginnev), "`recid`='{$sor['recid']}'");
echo $adatbazis_kapcsolat->gp() . '<br>';
    }
    
    exit;
}

//Anyagigénylés speciálisabb volt
if (isset($_GET['loginnev_gyart_anyagigenyles'])) {
    
    $tabla = 'anyagigenyles';
    
    $sorok = $adatbazis_kapcsolat->s('recid loginnev', $tabla);
    foreach($sorok as $sor){
        $teljes_nev = $adatbazis_kapcsolat->s('teljes_nev', 'login', "`nev`='{$sor['loginnev']}'");
        $teljes_nev = $teljes_nev[0]['teljes_nev'];
        
        $adatbazis_kapcsolat->u($tabla, array('insert_usr'), array($teljes_nev), "`recid`='{$sor['recid']}'");
echo $adatbazis_kapcsolat->gp() . '<br>';
    }
    
    exit;
}


//Státusz feltöltés, ha csak az adott bejegyzés státusza volna kitöltve és tröténet tábla még nem
if (isset($_GET['statusz_fetlolt'])) {
    $tabla = 'anyagigenyles';
    $anyagok = $adatbazis_kapcsolat->s('recid statid', $tabla);
    foreach($anyagok as $anyag){
        $adatbazis_kapcsolat->i($tabla.'_statusz',  array('stat_id',        'id'            ),
                                                    array($anyag['statid'], $anyag['recid'] )
                                );
echo $adatbazis_kapcsolat->gp() . '<br>';
    }
    
    exit;
}

if (isset($_GET['felh2torzs'])) {
    //Felhasználóból személyzet gyártása
    $felhok = $adatbazis_kapcsolat->s('*', 'login');
echo $adatbazis_kapcsolat->gp() . '<br>';
    foreach($felhok as $felh){
        
        $beosztas = '';
        if($felh['beosztas_id']=='94'){
            $beosztas = '1';
        }
        if($felh['beosztas_id']=='93'){
            $beosztas = '2';
        }
        if($felh['beosztas_id']=='16'){
            $beosztas = '4';
        }
        if($felh['beosztas_id']=='111'){
            $beosztas = '7';
        }

        if($beosztas){
            $mezok   = array('nev',               'szemelyzet_id', 'szul_datum', 'szakma', 'munka_kezd', 'komp', 'aktiv'        );
            $ertekek = array($felh['teljes_nev'], $beosztas,       '',           '',       '',           '',     $felh['aktiv'] );
            if(!$adatbazis_kapcsolat->s('id', 'torzsadat', "`felh_loginnev`='{$felh['nev']}'")){
                array_push($mezok, 'felh_loginnev');
                array_push($ertekek, $felh['nev']);
                $adatbazis_kapcsolat->i('torzsadat', $mezok, $ertekek);
            }else{
                $adatbazis_kapcsolat->u('torzsadat', $mezok, $ertekek, "`felh_loginnev`='{$felh['nev']}'");
            }
echo $adatbazis_kapcsolat->gp() . '<br>';
        }
    }
}


require_once('header.php');
require_once('footer.php');
?>