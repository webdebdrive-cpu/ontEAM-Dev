<?php
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
$select_honapok = array();
array_push($select_honapok, array('name' => '- Válasszon -','value' => '' ));
array_push($select_honapok, array('name' => 'január',       'value' => '1'));
array_push($select_honapok, array('name' => 'február',      'value' => '2'));
array_push($select_honapok, array('name' => 'március',      'value' => '3'));
array_push($select_honapok, array('name' => 'április',      'value' => '4'));
array_push($select_honapok, array('name' => 'május',        'value' => '5'));
array_push($select_honapok, array('name' => 'június',       'value' => '6'));
array_push($select_honapok, array('name' => 'július',       'value' => '7'));
array_push($select_honapok, array('name' => 'augusztus',    'value' => '8'));
array_push($select_honapok, array('name' => 'szeptember',   'value' => '9'));
array_push($select_honapok, array('name' => 'okotóber',     'value' => '10'));
array_push($select_honapok, array('name' => 'november',     'value' => '11'));
array_push($select_honapok, array('name' => 'december',     'value' => '12'));

//Ütemezés típushoz
$select_szemelyzet = array();
array_push($select_szemelyzet, array('name' => '- Válasszon -',             'value' => '0' ));
array_push($select_szemelyzet, array('name' => 'Telepített személyzet',     'value' => TELEPITETT_SZEMELYZET,   'szin' => '#CBE3FF' ));
array_push($select_szemelyzet, array('name' => 'Mobilcsoport',              'value' => MOBILCSOPORT,            'szin' => '#CCFFCB' ));
array_push($select_szemelyzet, array('name' => 'Külső kivitelező',          'value' => KULSO_KIVITELEZO,        'szin' => '#FCFFCB' ));
array_push($select_szemelyzet, array('name' => 'Műszaki vezető helyettes',  'value' => MUSZAKI_VEZETO_HELYETTES,'szin' => '#FFCBF1' ));
array_push($select_szemelyzet, array('name' => 'Műszaki vezető',            'value' => MUSZAKI_VEZETO,          'szin' => '#FFCBCB' ));
array_push($select_szemelyzet, array('name' => 'Területi felelős',          'value' => TERULETI_FELELOS,        'szin' => '#E0CBFF' ));
array_push($select_szemelyzet, array('name' => 'Műszaki adminisztrátor',    'value' => MUSZAKI_ADMINISZTRATOR,  'szin' => '#E0CBFF' ));
array_push($select_szemelyzet, array('name' => 'Diszpécser közp. vezető',   'value' => DISZPECSER_KOZP_VEZETO,  'szin' => '#FFEBCB' ));
array_push($select_szemelyzet, array('name' => 'MSZK vezető',               'value' => MSZK_VEZETO,             'szin' => '#E0CBFF' ));

//Projektigénylés tipus
$select_pri_tipus = array();
array_push($select_pri_tipus, array('name' => '- Válasszon -',  'value' => '' ));
//Ha mindent vagy saját prigényt kezelhetek, akkor látszik ez a kettő. Ha csak Tárgyi bejelentést tudok kezelni, akkor csak a Tárgyi eszköz marad
if( !isset($_GET['uj']) || _jog(JOG_PRIGENY_B_MEGTEKINT)){
    array_push($select_pri_tipus, array('name' => 'Beruházás',      'value' => PRIGENY_TIPUS_BERUH  ));
}
if( !isset($_GET['uj']) || _jog(JOG_PRIGENY_F_MEGTEKINT)){
    array_push($select_pri_tipus, array('name' => 'Fenntartás',     'value' => PRIGENY_TIPUS_FENNT  ));
}
if( !isset($_GET['uj']) || _jog(JOG_PRIGENY_T_MEGTEKINT)){
    array_push($select_pri_tipus, array('name' => 'Tárgyi eszköz',  'value' => PRIGENY_TIPUS_TARGY  ));
}

//Projektigénylés besorolás
$select_pri_besorol = array();
array_push($select_pri_besorol, array('name' => '- Válasszon -',    'value' => '' ));
array_push($select_pri_besorol, array('name' => 'Fejlesztés',       'value' => PRIGENY_BESOROL_FEJL  ));
array_push($select_pri_besorol, array('name' => 'Felújítás',        'value' => PRIGENY_BESOROL_FELU  ));
array_push($select_pri_besorol, array('name' => 'Rekonstrukció ',   'value' => PRIGENY_BESOROL_REKO  ));


//Beosztás tipus
$select_beosztas = array();
array_push($select_beosztas, array('name' => '- Válasszon -', 'value' => '' ));
array_push($select_beosztas, array('name' => 'Munkavégzés',     'value' => '1' ));
array_push($select_beosztas, array('name' => 'Szabadság',       'value' => '2' ));

/* Anyag státuszok */
$munkAnyag_statusz = array(
    DISZPNAG_STAT_VAN_ANYAG         => 'Rendelkezésre áll',
    DISZPNAG_STAT_ANYAGRA_VAR       => 'Anyagigénylés szükséges',
    DISZPNAG_STAT_ANYAG_IGENYELVE   => 'Igényelve'
);

/* Értéklista csoport */
$select_erteklista = array();
array_push($select_erteklista, array('name' => '-Válassz-', 'value' => '' ));
$erteklistak = $adatbazis_kapcsolat -> select('csoport nev', 'erteklista_csoport', '', 'nev');
foreach($erteklistak as $erteklista) {
	array_push($select_erteklista, array('name' => $erteklista['nev'], 'value' => (int)$erteklista['csoport'] ));
}
//{KOVETKEZO_SELECT}
