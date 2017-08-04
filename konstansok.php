<?php
/*Konstansok*/

/* Státuszok */
//Bejelentés státuszok
define('BEJELENTES_STAT_NYITOTT',       '01');
define('BEJELENTES_STAT_FOLYAMATBAN',   '02');
define('BEJELENTES_STAT_ELUTASITVA',    '03');
define('BEJELENTES_STAT_VARAKOZO',      '04');
define('BEJELENTES_STAT_KESZ_IG',       '05');
define('BEJELENTES_STAT_VIZSG_ALATT',   '06');
define('BEJELENTES_STAT_LEZART',        '07');

//Munkalap státuszok
define('MUNKALAP_STAT_NYITOTT',         '01');
define('MUNKALAP_STAT_FOLYAMATBAN',     '02');
define('MUNKALAP_STAT_ELUTASITVA',      '03');
define('MUNKALAP_STAT_HOZZARENDELT',    '05');
define('MUNKALAP_STAT_TOROLT',          '06');
define('MUNKALAP_STAT_VARAKOZO',        '07');
define('MUNKALAP_STAT_FELVEVE',         '08');
define('MUNKALAP_STAT_ANYAGRAVAR',      '09');
define('MUNKALAP_STAT_KESZ_IG',         '10');
define('MUNKALAP_STAT_LEZART',          '11');
define('MUNKALAP_STAT_IGAZOLT',         '12');
define('MUNKALAP_STAT_HOZREND_ELOKESZ', '13');

//Munkalap Anyagigénylés státuszok
define('DISZPNAG_STAT_VAN_ANYAG',       '00');
define('DISZPNAG_STAT_ANYAGRA_VAR',     '01');
define('DISZPNAG_STAT_ANYAG_IGENYELVE', '02');

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

//személyzettípusok
define('TELEPITETT_SZEMELYZET',         '1' );
define('MOBILCSOPORT',                  '2' );
define('KULSO_KIVITELEZO',              '3' );
define('DISZPECSER_KOZP_VEZETO',        '4' );
define('MUSZAKI_VEZETO',                '5' );
define('MUSZAKI_VEZETO_HELYETTES',      '6' );
define('TERULETI_FELELOS',              '7' );

//MozgásID-k
define('MOZGID_RAKTAR_BE',              '01' );
define('MOZGID_RAKTAR_KI',              '02' );
define('MOZGID_LELTAR',                 '03' );

define('NYOMTATAS_ADOSZAM',  '10219522-2-44' );
define('NYOMTATAS_FIX_CIM',         1040     );
define('NYOMTATAS_KIVETELES_CIM',   2032     );

//JOGOK
define('JOG_BEJELENTES_MEGTEKINT',  0 );
define('JOG_BEJELENTES_KEZEL',      1 );
define('JOG_DISZPECSER',            2 );
define('JOG_MUNKALAP_MEGTEKINT',    3 );
define('JOG_MUNKALAP_TELEPITETT',   5 );
define('JOG_ANYAIGENLES_MEGTEKINT', 12);
define('JOG_ANYAGIGENYLES_KEZEL',   13);
define('JOG_ANYAGIGENYLES_JOVAHAGY',14);
define('JOG_ANYAGREND_KESZIT',      15);
define('JOG_ANYAGREND_MEGTEKINT',   16);
define('JOG_MUNKALAP_MUSZAKI_VEZ',  21);
define('JOG_MUNKALAP_TER_FELELOS',  22);
define('JOG_MUNKALAP_ADMIN',        24);
define('JOG_MUNKALAP_DISZP_KP_VEZ', 23);
define('JOG_MUNKALAP_MOBIL',        31);
define('JOG_MUNKALAP_KULSO',        34);
define('JOG_UTFEL_MEGTEKINT',       32);
define('JOG_UTFEL_KEZEL',           33);
define('JOG_RENDSZ_KARB_MEGTEKINT', 35);
define('JOG_RENDSZ_KARB_KEZEL',     36);
?>
