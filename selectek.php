<?php
/*  Select tömbök legyártása                                         *\
    
    Tip: Ha a name és a value azonos, elég a value értéket megadni!

\*                                                                   */


/*
//Objektumoknál előfordulhat (pl. kosárnál)
if(!is_object($adatbazis_kapcsolat) && is_object($this -> adatbazis_kapcsolat)){
    $adatbazis_kapcsolat = $this -> adatbazis_kapcsolat;
}

$select_marka = array();
array_push($select_marka, array('name' => '-Válassz-', 'value' => '' ));
$markak = $adatbazis_kapcsolat -> select('*', 'marka', '', 'nev');
foreach($markak as $sor_marka){
	array_push($select_marka, array('name' => $sor_marka['nev'], 'value' => $sor_marka['id']));
}*/



$select_valami = array();
array_push($select_valami, array('name' => 'Név',  'value' => 'ertek1'));
array_push($select_valami, array('name' => 'Név2', 'value' => 'ertek2'));
array_push($select_valami, array('name' => 'Név3', 'value' => 'ertek3'));

?>