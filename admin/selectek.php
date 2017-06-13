<?php
/*  Select tömbök legyártása                                         *\

    Tip: Ha a name és a value azonos, elég a value értéket megadni!

\*                                                                   */


/* Mintakód kategóriák kilistázására

//Select tömb előállítása a termékekhez (egy szint mélység esetére)
$select_kat = array();
array_push($select_kat, array('name' => '- Válasszon -', 'value' => '' ));
$kat = $adatbazis_kapcsolat -> select('id nev', 'kat', '', 'nev');
foreach($kat as $kat_sor){
	array_push($select_kat, array('name' => $kat_sor['nev'], 'value' => $kat_sor['id'] ));
}
*/


/*
//Rekurzív függvény a kategóriák bejárására: tetszőleges mélység bejárásához
$select_kat = array();
array_push($select_kat, array('name' => '- Válasszon -', 'value' => '' ));

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
array_push($select_kat, array('name' => '- Válasszon -', 'value' => '' ));

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
array_push($select_VALAMI, array('name' => '- Válasszon -', 'value' => ''));
array_push($select_VALAMI, array('label' => 'Számok'));
array_push($select_VALAMI, array('name'  => '1', 'value' => '1x'));
array_push($select_VALAMI, array('name'  => '2', 'value' => '2x'));
array_push($select_VALAMI, array('name'  => '3', 'value' => '3x'));
array_push($select_VALAMI, array('label' => 'optiongroup_bezar'));

/* Fizetési módok */
$select_fiz_mod = array();
array_push($select_fiz_mod, array('name' => '- Válasszon -',    'value' => ''));
array_push($select_fiz_mod, array('name'  => 'Utalás',      'value' => 'Utalás'));
array_push($select_fiz_mod, array('name'  => 'Készpénz',    'value' => 'Készpénz'));
array_push($select_fiz_mod, array('name'  => 'Bankkártya',  'value' => 'Bankkártya'));

/* Áfa típus */
$select_afa_tipus = array();
array_push($select_afa_tipus, array('name' => '- Válasszon -',    'value' => ''));
array_push($select_afa_tipus, array('name'  => 'Érték 1',    'value' => 'Érték 2'));
array_push($select_afa_tipus, array('name'  => 'Érték 1',    'value' => 'Érték 2'));

/* Eszköz csoport */
$select_eszk_csop = array();
array_push($select_eszk_csop, array('name' => '- Válasszon -',    'value' => ''));
array_push($select_eszk_csop, array('name'  => 'Érték 1',    'value' => 'Érték 2'));
array_push($select_eszk_csop, array('name'  => 'Érték 1',    'value' => 'Érték 2'));

/* Címzett */
$select_cimzett = array();
array_push($select_cimzett, array('name' => '- Válasszon -',    'value' => ''));
array_push($select_cimzett, array('name'  => 'Érték 1',    'value' => 'Érték 2'));
array_push($select_cimzett, array('name'  => 'Érték 1',    'value' => 'Érték 2'));

/* Illetékes részleg */
$select_ill_reszleg = array();
array_push($select_ill_reszleg, array('name' => '- Válasszon -',    'value' => ''));
array_push($select_ill_reszleg, array('name'  => 'Érték 1',    'value' => 'Érték 2'));
array_push($select_ill_reszleg, array('name'  => 'Érték 1',    'value' => 'Érték 2'));

//Select tömb előállítása rendszer kategóriákhoz
$select_rkat = array();
array_push($select_rkat, array('name' => '- Válasszon -', 'value' => '' ));
$rkat = $adatbazis_kapcsolat -> select('recid nev', 'rendszer_kat', '', 'nev');
foreach($rkat as $rkat_sor){
	array_push($select_rkat, array('name' => $rkat_sor['nev'], 'value' => $rkat_sor['recid'] ));
}

//Select tömb Ütemezés típushoz
$select_cikltip = array();
array_push($select_cikltip, array('name' => '- Válasszon -', 'value' => '' ));
array_push($select_cikltip, array('name'  => 'Határidős',  'value' => 'l'));
array_push($select_cikltip, array('name'  => 'Naponta',    'value' => 'd'));
array_push($select_cikltip, array('name'  => 'Hetente',    'value' => 'w'));
array_push($select_cikltip, array('name'  => 'Havonta',    'value' => 'm'));

//Select tömb Ütemezés típushoz
$select_honapjan = array();
array_push($select_honapjan, array('name' => '- Válasszon -', 'value' => '' ));
array_push($select_honapjan, array('name' => 'Első',    'value' => '1'));
array_push($select_honapjan, array('name' => '1',       'value' => '1'));
array_push($select_honapjan, array('name' => '2',       'value' => '1'));
array_push($select_honapjan, array('name' => '3',       'value' => '3'));
array_push($select_honapjan, array('name' => '4',       'value' => '4'));
array_push($select_honapjan, array('name' => '5',       'value' => '5'));
array_push($select_honapjan, array('name' => '6',       'value' => '6'));
array_push($select_honapjan, array('name' => '7',       'value' => '7'));
array_push($select_honapjan, array('name' => '8',       'value' => '8'));
array_push($select_honapjan, array('name' => '9',       'value' => '9'));
array_push($select_honapjan, array('name' => '10',      'value' => '10'));
array_push($select_honapjan, array('name' => '11',      'value' => '11'));
array_push($select_honapjan, array('name' => '12',      'value' => '12'));
array_push($select_honapjan, array('name' => '13',      'value' => '13'));
array_push($select_honapjan, array('name' => '14',      'value' => '14'));
array_push($select_honapjan, array('name' => '15',      'value' => '15'));
array_push($select_honapjan, array('name' => '16',      'value' => '16'));
array_push($select_honapjan, array('name' => '17',      'value' => '17'));
array_push($select_honapjan, array('name' => '18',      'value' => '18'));
array_push($select_honapjan, array('name' => '19',      'value' => '19'));
array_push($select_honapjan, array('name' => '20',      'value' => '20'));
array_push($select_honapjan, array('name' => '21',      'value' => '21'));
array_push($select_honapjan, array('name' => '22',      'value' => '22'));
array_push($select_honapjan, array('name' => '23',      'value' => '23'));
array_push($select_honapjan, array('name' => '24',      'value' => '24'));
array_push($select_honapjan, array('name' => '25',      'value' => '25'));
array_push($select_honapjan, array('name' => '26',      'value' => '26'));
array_push($select_honapjan, array('name' => '27',      'value' => '27'));
array_push($select_honapjan, array('name' => '28',      'value' => '28'));
array_push($select_honapjan, array('name' => '29',      'value' => '29'));
array_push($select_honapjan, array('name' => '30',      'value' => '30'));
array_push($select_honapjan, array('name' => '31',      'value' => '31'));
array_push($select_honapjan, array('name' => 'Utolsó',  'value' => '31'));

//Select tömb Ütemezés típushoz
$select_ho_ism = array();
array_push($select_ho_ism, array('name' => '- Válasszon -', 'value' => '' ));
array_push($select_ho_ism, array('name' => 'első',      'value' => '1'));
array_push($select_ho_ism, array('name' => 'második',   'value' => '2'));
array_push($select_ho_ism, array('name' => 'harmadik',  'value' => '3'));
array_push($select_ho_ism, array('name' => 'negyedik',  'value' => '4'));
array_push($select_ho_ism, array('name' => 'utolsó',    'value' => '5'));


//Select tömb Ütemezés típushoz
$select_ho_soran = array();
array_push($select_ho_soran, array('name' => '- Válasszon -', 'value' => '' ));
array_push($select_ho_soran, array('name' => 'hétfő',       'value' => '1'));
array_push($select_ho_soran, array('name' => 'kedd',        'value' => '2'));
array_push($select_ho_soran, array('name' => 'szerda',      'value' => '3'));
array_push($select_ho_soran, array('name' => 'csütörtök',   'value' => '4'));
array_push($select_ho_soran, array('name' => 'péntek',      'value' => '5'));
array_push($select_ho_soran, array('name' => 'szombat',     'value' => '6'));
array_push($select_ho_soran, array('name' => 'vasárnap',    'value' => '7'));

//Select tömb Ütemezés típushoz
$select_szemelyzet = array();
array_push($select_szemelyzet, array('name' => '- Válasszon -', 'value' => '' ));
array_push($select_szemelyzet, array('name' => 'Telepített személyzet',     'value' => '1' ));
array_push($select_szemelyzet, array('name' => 'Mobilcsoport',              'value' => '2' ));
array_push($select_szemelyzet, array('name' => 'Külső kivitelező',          'value' => '3' ));
array_push($select_szemelyzet, array('name' => 'Diszpécser közp. vezető',   'value' => '4' ));
//{KOVETKEZO_SELECT}
