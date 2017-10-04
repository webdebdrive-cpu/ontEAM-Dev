<?php
/*Konstansok*/


//Általános konstansok
define('NYOMTATAS_ADOSZAM',  '10219522-2-44' );
define('NYOMTATAS_FIX_CIM',         1040     );
define('NYOMTATAS_KIVETELES_CIM',   2032     );

/* Státuszok */
//Bejelentés státuszok
define('BEJELENTES_STAT_NYITOTT',       '01');
define('BEJELENTES_STAT_FOLYAMATBAN',   '02');
define('BEJELENTES_STAT_ELUTASITVA',    '03');
define('BEJELENTES_STAT_VARAKOZO',      '04');
define('BEJELENTES_STAT_KESZ_IG',       '05');
define('BEJELENTES_STAT_VIZSG_ALATT',   '06');
define('BEJELENTES_STAT_LEZART',        '07');

//Anyagigénylés státuszok
define('CIKKIGN_STAT_NYITOTT',           '05');
define('CIKKIGN_STAT_MEGR_OSSZEALL',     '10');
define('CIKKIGN_STAT_ARAZVA',            '20');
define('CIKKIGN_STAT_JOVAHAGYVA',        '25');
define('CIKKIGN_STAT_MEGRENDELVE',       '30');
define('CIKKIGN_STAT_RESZBENBEERK',      '35');
define('CIKKIGN_STAT_BEERKEZETT',        '40');
define('CIKKIGN_STAT_SZLA_BEERK',        '45');
define('CIKKIGN_STAT_LEZART',            '50');
define('CIKKIGN_STAT_TOROLT',            '51');
define('CIKKIGN_STAT_VARAKOZO',          '52');
define('CIKKIGN_STAT_NEM_ERKEZETT',      '90');
define('CIKKIGN_STAT_ELUTASITVA',        '91');

//Cikkrendfej státuszok
define('CIKKRNDFEJ_STAT_MEGRENDELVE',    '30');
define('CIKKRNDFEJ_STAT_RESZBEN_BEERK',  '35');
define('CIKKRNDFEJ_STAT_BEERKEZETT',     '40');
define('CIKKRNDFEJ_STAT_SLA_BEERK',      '45');
define('CIKKRNDFEJ_STAT_LEZART',         '50');
define('CIKKRNDFEJ_STAT_TOROLT',         '51');
define('CIKKRNDFEJ_STAT_VARAKOZO',       '52');
define('CIKKRNDFEJ_STAT_ANYAG_NEM',      '90');
define('CIKKRNDFEJ_STAT_ELUTASITVA',     '91');

//Munkalap Anyagigénylés státuszok
define('DISZPNAG_STAT_VAN_ANYAG',       '00');
define('DISZPNAG_STAT_ANYAGRA_VAR',     '01');
define('DISZPNAG_STAT_ANYAG_IGENYELVE', '02');

//Munkalap státuszok
define('MUNKALAP_STAT_NYITOTT',             '01');
define('MUNKALAP_STAT_FOLYAMATBAN',         '02');
define('MUNKALAP_STAT_ELUTASITVA',          '03');
define('MUNKALAP_STAT_HOZZARENDELT',        '05');
define('MUNKALAP_STAT_TOROLT',              '06');
define('MUNKALAP_STAT_VARAKOZO',            '07');
define('MUNKALAP_STAT_FELVEVE',             '08');
define('MUNKALAP_STAT_ANYAGRAVAR',          '09');
define('MUNKALAP_STAT_KESZ_IG',             '10');
define('MUNKALAP_STAT_LEZART',              '11');
define('MUNKALAP_STAT_IGAZOLT',             '12');
define('MUNKALAP_STAT_HOZREND_ELOKESZ',     '13');
define('MUNKALAP_STAT_HOZREND_JOVAHAGY',    '14');
define('MUNKALAP_STAT_HOZREND_MEGREND_VAR', '15');

//Projektigénylés státuszok
define('PRIGENY_STAT_NYITOTT',              '01' );
define('PRIGENY_STAT_LETRE_ALATT',          '02' );
define('PRIGENY_STAT_VEL_ALATT',            '03' );
define('PRIGENY_STAT_ELUTASITVA',           '04' );
define('PRIGENY_STAT_HIANYPOTLAS',          '05' );
define('PRIGENY_STAT_HIANYPOTOLT',          '06' );
define('PRIGENY_STAT_FB_TERJESZT',          '07' );
define('PRIGENY_STAT_BOD_TERJESZT',         '08' );
define('PRIGENY_STAT_VARAKOZO',             '09' );
define('PRIGENY_STAT_JOVAHAGYVA',           '10' );

//Hiánypótlásra ezekből a sátuszokból lehet léptetni
function prigeny_hianypotlasba_statuszok(){
    return array(PRIGENY_STAT_NYITOTT, PRIGENY_STAT_HIANYPOTOLT, PRIGENY_STAT_FB_TERJESZT);
}
//Ezekben a státuszokban lehet jóváhagyni egy igénylést
function prigeny_jovahagyhato_statuszok(){
    return array(PRIGENY_STAT_FB_TERJESZT, PRIGENY_STAT_NYITOTT, PRIGENY_STAT_HIANYPOTOLT, PRIGENY_STAT_VEL_ALATT, PRIGENY_STAT_FB_TERJESZT, PRIGENY_STAT_BOD_TERJESZT);
}
//FB-be ezekből a státuszokból lehet lépteni
function prigeny_fb_ele_statuszok(){
    return array(PRIGENY_STAT_NYITOTT, PRIGENY_STAT_HIANYPOTOLT, PRIGENY_STAT_VEL_ALATT, PRIGENY_STAT_BOD_TERJESZT);
}
//BOD-ba ezekből a státuszokból lehet lépteni
function prigeny_bod_ele_statuszok(){
    return array(PRIGENY_STAT_FB_TERJESZT);
}
//Véleményezőt hozzáadni ezekben a státuszokban lehet
function prigeny_velemenyezo_hozzaad_statuszok(){
    return array(PRIGENY_STAT_NYITOTT, PRIGENY_STAT_VEL_ALATT, PRIGENY_STAT_HIANYPOTOLT);
}

//Projektkezeles státuszok
define('PRKEZEL_STAT_NYITOTT',              '01' );






//Projektigénylés tipusok
define('PRIGENY_TIPUS_BERUH',           '1' );
define('PRIGENY_TIPUS_FENNT',           '2' );
define('PRIGENY_TIPUS_TARGY',           '3' );

//Projektigénylés besorolások
define('PRIGENY_BESOROL_FEJL',          '1' );
define('PRIGENY_BESOROL_FELU',          '2' );
define('PRIGENY_BESOROL_REKO',          '3' );

//Projektigénylés cél
define('PRIGENY_CEL_CSO',               '1' );
define('PRIGENY_CEL_NOV',               '2' );

//személyzettípusok
define('TELEPITETT_SZEMELYZET',         '1' );
define('MOBILCSOPORT',                  '2' );
define('KULSO_KIVITELEZO',              '3' );
define('DISZPECSER_KOZP_VEZETO',        '4' );
define('MUSZAKI_VEZETO',                '5' );
define('MUSZAKI_VEZETO_HELYETTES',      '6' );
define('TERULETI_FELELOS',              '7' );
define('MUSZAKI_ADMINISZTRATOR',        '8' );

//MozgásID-k
define('MOZGID_RAKTAR_BE',              '01' );
define('MOZGID_RAKTAR_KI',              '02' );
define('MOZGID_LELTAR',                 '03' );



//JOGOK
define('JOG_BEJELENTES_MEGTEKINT',  57);
define('JOG_BEJELENTES_SAJAT_RESZL',0 );
define('JOG_BEJELENTES_KEZEL',      1 );
define('JOG_BEJELENTES_MAS_NEVEBEN',56);
define('JOG_DISZPECSER',            2 );
define('JOG_BEJELENTES_ELUTASIT',   41);
define('JOG_MUNKALAP_MEGTEKINT',    3 );
//define('JOG_MUNKALAP_KEZEL',    4   );
define('JOG_MUNKALAP_TELEPITETT',   5 );
define('JOG_FELHASZNALOK_MEGTEKINT',6 );
define('JOG_FELHASZNALOK_KEZEL',    7 );
define('JOG_ERTEKLISTAK_MEGTEKINT', 8 );
define('JOG_ERTEKLISTAK_KEZEL',     9 );
define('JOG_SZEREPKOR_MEGTEKINT',   10);
define('JOG_SZEREPKOR_KEZEL',       11);
define('JOG_ANYAIGENLES_MEGTEKINT', 12);
define('JOG_ANYAGIGENYLES_KEZEL',   13);
define('JOG_ANYAGIGENYLES_JOVAHAGY',14);
define('JOG_ANYAGREND_KESZIT',      15);
define('JOG_ANYAGREND_MEGTEKINT',   16);
define('JOG_RAKTAR_MEGTEKINT',      17);
define('JOG_RAKTAR_KEZEL',          18);
define('JOG_CIKKTORZS_MEGTEKINT',   19);
define('JOG_CIKKTORZS_KEZEL',       20);
define('JOG_MUNKALAP_MUSZAKI_VEZ',  21);
define('JOG_MUNKALAP_TER_FELELOS',  22);
define('JOG_MUNKALAP_DISZP_KP_VEZ', 23);
define('JOG_MUNKALAP_ADMIN',        24);
define('JOG_TORZSADAT_MEGTEKINT',   25);
define('JOG_TORZSADAT_KEZEL',       26);
define('JOG_SZABADSAGIG_MEGTEKINT', 27);
define('JOG_SZABADSAGIG_KEZEL',     28);
define('JOG_BEOSZTASOK_MEGTEKINT',  29);
define('JOG_BEOSZTASOK_KEZEL',      30);
define('JOG_MUNKALAP_MOBIL',        31);
define('JOG_MUNKALAP_KULSO',        34);
define('JOG_UTFEL_MEGTEKINT',       32);
define('JOG_UTFEL_KEZEL',           33);
define('JOG_RENDSZ_KARB_MEGTEKINT', 35);
define('JOG_RENDSZ_KARB_KEZEL',     36);
define('JOG_ESETI_FENN_MEGTEKINT',  54);
define('JOG_ESETI_FENN_KEZEL',      55);
define('JOG_MUNKALAP_MUSZAK_ADMIN', 37);
define('JOG_PRIGENY_S_MEGTEKINT',   38);
define('JOG_PRIGENY_S_KEZEL',       39);
//define('JOG_PRIGENY_M_MEGTEKINT',   46);
define('JOG_PRIGENY_M_KEZEL',       47);
define('JOG_PRIGENY_T_MEGTEKINT',   48);
define('JOG_PRIGENY_T_KEZEL',       49);
define('JOG_PRIGENY_ELUTASIT',      50);
define('JOG_PRIGENY_JOVAHAGY',      51);
define('JOG_PRKEZEL_MEGTEKINT',     52);
define('JOG_PRKEZEL_KEZEL',         53);
define('JOG_VELEMENYEZO_HOZZAAD',   40);
define('JOG_ESZKOZ_MEGTEKINT',      42);
define('JOG_ESZKOZ_KEZEL',          43);
define('JOG_FT_MEGTEKINT',          44);
define('JOG_FT_KEZEL',              45);
?>
