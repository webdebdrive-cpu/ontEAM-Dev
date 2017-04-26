<?php
/*  Select tömbök legyártása                                         *\

    Tip: Ha a name és a value azonos, elég a value értéket megadni!

\*                                                                   */


/* Mintakód kategóriák kilistázására

//Select tömb előállítása a termékekhez (egy szint mélység esetére)
$select_kat = array();
array_push($select_kat, array('name' => '- Válassz -', 'value' => '' ));
$kat = $adatbazis_kapcsolat -> select('id nev', 'kat', '', 'nev');
foreach($kat as $kat_sor){
	array_push($select_kat, array('name' => $kat_sor['nev'], 'value' => $kat_sor['id'] ));
}
*/


/*
//Rekurzív függvény a kategóriák bejárására: tetszőleges mélység bejárásához
$select_kat = array();
array_push($select_kat, array('name' => '- Válassz -', 'value' => '' ));

$kat = $adatbazis_kapcsolat -> select('id nev', 'kat', 'szulo=\'\'', 'nev');
foreach( $kat as $kat_sor ){

    array_push($select_kat, array('name' => $kat_sor['nev'], 'value' => $kat_sor['id'], 'style' => 'font-weight: bold' ));
    get_alkat($kat_sor, 1);

}
function get_alkat($kat, $melyseg){

    global $adatbazis_kapcsolat;
    global $select_kat;


    $behuzas = '';
    for($i=0; $i<$melyseg; $i++){
        $behuzas .= '&nbsp;&nbsp;&nbsp;';
    }

    $alkat = $adatbazis_kapcsolat -> select('id nev', 'kat', 'szulo='.$kat['id'], 'nev');
    //array_push($select_kat, array('name' => $kat['nev'], 'value' => $kat['id'] ));
    foreach( $alkat as $alkat_sor){
        array_push($select_kat, array('name' => $behuzas.$kat['nev'].'&nbsp;»&nbsp;'.$alkat_sor['nev'], 'value' => $alkat_sor['id'] ));
        get_alkat($alkat_sor, $melyseg+1);
    }
}
*/


/*
//Két-három szint mélységű select esetére (a rekurzív lehet jobb!)
$select_kat = array();
array_push($select_kat, array('name' => '- Válassz -', 'value' => '' ));

$kat = $adatbazis_kapcsolat -> select('id nev', 'kat', 'szulo=\'\'', 'nev');
foreach( $kat as $kat_sor ){
	array_push($select_kat, array('label' => $kat_sor['nev']));

	$alkat = $adatbazis_kapcsolat -> select('id nev', 'kat', 'szulo='.$kat_sor['id'], 'nev');
	if($alkat){
        foreach( $alkat as $alkat_sor){
    		array_push($select_kat, array('name' => '&nbsp;'.$alkat_sor['nev'], 'value' => $alkat_sor['id'] ));

//            //3 szint esetére
//            $alalkat = $adatbazis_kapcsolat -> select('id nev', 'kat', 'szulo='.$alkat_sor['id'], 'nev');
//            foreach( $alalkat as $alalkat_sor){
//                array_push($select_kat, array('name' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$alalkat_sor['nev'], 'value' => $alalkat_sor['id'] ));
//            }

    	}
	}else{
        array_push($select_kat, array('name' => '&nbsp;'.$kat_sor['nev'], 'value' => $kat_sor['id'] ));
    }

}
*/

//Select tömb előállítása manuálisan:
$select_VALAMI = array();
array_push($select_VALAMI, array('name' => '- Válassz -', 'value' => ''));
array_push($select_VALAMI, array('label' => 'Számok'));
array_push($select_VALAMI, array('name'  => '1', 'value' => '1x'));
array_push($select_VALAMI, array('name'  => '2', 'value' => '2x'));
array_push($select_VALAMI, array('name'  => '3', 'value' => '3x'));
array_push($select_VALAMI, array('label' => 'optiongroup_bezar'));

/* Fizetési módok */
$select_fiz_mod = array();
array_push($select_fiz_mod, array('name' => '- Válassz -',    'value' => ''));
array_push($select_fiz_mod, array('name'  => 'Utalás',      'value' => 'Utalás'));
array_push($select_fiz_mod, array('name'  => 'Készpénz',    'value' => 'Készpénz'));
array_push($select_fiz_mod, array('name'  => 'Bankkártya',  'value' => 'Bankkártya'));

/* Áfa típus */
$select_afa_tipus = array();
array_push($select_afa_tipus, array('name' => '- Válassz -',    'value' => ''));
array_push($select_afa_tipus, array('name'  => 'Érték 1',    'value' => 'Érték 2'));
array_push($select_afa_tipus, array('name'  => 'Érték 1',    'value' => 'Érték 2'));

/* Eszköz csoport */
$select_eszk_csop = array();
array_push($select_eszk_csop, array('name' => '- Válassz -',    'value' => ''));
array_push($select_eszk_csop, array('name'  => 'Érték 1',    'value' => 'Érték 2'));
array_push($select_eszk_csop, array('name'  => 'Érték 1',    'value' => 'Érték 2'));

/* Címzett */
$select_cimzett = array();
array_push($select_cimzett, array('name' => '- Válassz -',    'value' => ''));
array_push($select_cimzett, array('name'  => 'Érték 1',    'value' => 'Érték 2'));
array_push($select_cimzett, array('name'  => 'Érték 1',    'value' => 'Érték 2'));

/* Illetékes részleg */
$select_ill_reszleg = array();
array_push($select_ill_reszleg, array('name' => '- Válassz -',    'value' => ''));
array_push($select_ill_reszleg, array('name'  => 'Érték 1',    'value' => 'Érték 2'));
array_push($select_ill_reszleg, array('name'  => 'Érték 1',    'value' => 'Érték 2'));

//Select tömb előállítása rendszer kategóriákhoz
$select_rkat = array();
array_push($select_rkat, array('name' => '- Válassz -', 'value' => '' ));
$rkat = $adatbazis_kapcsolat -> select('recid nev', 'rendszer_kat', '', 'nev');
foreach($rkat as $rkat_sor){
	array_push($select_rkat, array('name' => $rkat_sor['nev'], 'value' => $rkat_sor['recid'] ));
}
//{KOVETKEZO_SELECT}
