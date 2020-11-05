<?php
// Általános konstansok
define('NYOMTATAS_ADOSZAM',         '10219522-2-44' );
define('NYOMTATAS_ADOSZAM_GUNDEL',  '10505898-2-42' );
define('NYOMTATAS_FIX_CIM',         1040            );
define('NYOMTATAS_KIVETELES_CIM',   2032            );
define('SZALLITOKOD_HOSSZ',         5               );
define('JELSZO_MIN_HOSSZ',          8               );
define('DISZPECSER_TELEFONSZAM',    '30/703 0560, 1/889 5988');
define('MSZK_MAILCIM',              'mszk@danubiushotels.com');
define('CSV_EXPORT_UTVONAL_1',      'export');
define('CSV_EXPORT_UTVONAL_2',      '/csv');
define('CSV_EXPORT_ELAVULAS',       3600); // 60 * 60
define('HUNGARIA_SZALLODA',         2120);

// Státuszok
// Bejelentés státuszok
define('BEJELENTES_STAT_NYITOTT',       '01');
define('BEJELENTES_STAT_FOLYAMATBAN',   '02');
define('BEJELENTES_STAT_ELUTASITVA',    '03');
define('BEJELENTES_STAT_VARAKOZO',      '04');
define('BEJELENTES_STAT_KESZ_IG',       '05');
define('BEJELENTES_STAT_VIZSG_ALATT',   '06');
define('BEJELENTES_STAT_LEZART',        '07');

// Anyagigénylés státuszok
define('CIKKIGN_STAT_VARAKOZO',          '01');
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
define('CIKKIGN_STAT_NEM_ERKEZETT',      '90');
define('CIKKIGN_STAT_ELUTASITVA',        '91');

// Anyagigénylés/rendelés státuszok
define('CIKKRNDFEJ_STAT_MEGRENDELVE',    '30');
define('CIKKRNDFEJ_STAT_RESZBEN_BEERK',  '35');
define('CIKKRNDFEJ_STAT_BEERKEZETT',     '40');
define('CIKKRNDFEJ_STAT_SLA_BEERK',      '45');
define('CIKKRNDFEJ_STAT_LEZART',         '50');
define('CIKKRNDFEJ_STAT_TOROLT',         '51');
define('CIKKRNDFEJ_STAT_VARAKOZO',       '52');
define('CIKKRNDFEJ_STAT_ANYAG_NEM',      '90');
define('CIKKRNDFEJ_STAT_ELUTASITVA',     '91');

// Munkalap Anyagigénylés státuszok
define('DISZPNAG_STAT_VAN_ANYAG',       '00');
define('DISZPNAG_STAT_ANYAGRA_VAR',     '01');
define('DISZPNAG_STAT_ANYAG_IGENYELVE', '02');

// Előterjesztés (FB) státuszok
define('ELOT_FB_STAT_NYITOTT',              '01');
define('ELOT_FB_STAT_VEGLEGESITVE',         '02');
define('ELOT_FB_STAT_DONT_ROG',             '03');

// Előterjesztés (BoD) státuszok
define('ELOT_BOD_STAT_NYITOTT',             '01');
define('ELOT_BOD_STAT_VEGLEGESITVE',        '02');
define('ELOT_BOD_STAT_DONT_ROG',            '03');

// Munkalap státuszok
define('MUNKALAP_STAT_NYITOTT',             '01');
define('MUNKALAP_STAT_HOZZARENDELT',        '02');
define('MUNKALAP_STAT_ELUTASITVA',          '03');
// define('MUNKALAP_STAT_HOZZARENDELT<=korábban volt egy ilyen, de nem volt használatban, és a Folyamatban státusz kapta meg ezt a nevet',        '05');
define('MUNKALAP_STAT_TOROLT',              '06');
define('MUNKALAP_STAT_HATIDO_UTEMEZETT',    '07');
define('MUNKALAP_STAT_FELVEVE',             '08');
define('MUNKALAP_STAT_ANYAGRAVAR',          '09');
define('MUNKALAP_STAT_LEZARASRA_VAR',       '10');
define('MUNKALAP_STAT_LEZART',              '11');
define('MUNKALAP_STAT_IGAZOLT',             '12');
define('MUNKALAP_STAT_ELOKESZITES_ALATT',   '13');
define('MUNKALAP_STAT_DONTESRE_VAR',        '14');
define('MUNKALAP_STAT_HOZREND_MEGREND_VAR', '15');
define('MUNKALAP_STAT_IGAZOLASRA_VAR',      '16');
define('MUNKALAP_STAT_VIZSGALAT_ALATT',     '17');
define('MUNKALAP_STAT_BEOSZTAS_NELKUL',     '18');
define('MUNKALAP_STAT_SZTORNO',             '19');
define('MUNKALAP_STAT_VARAKOZO',            '20');
define('MUNKALAP_STAT_AJANLAT_NYOMTATVA',   '21');
define('MUNKALAP_STAT_SZTORNO_VAR',         '22');

// Munkalap anyag státuszok
define('MUNKALAP_ANYAG_STAT_AI_SZUKS',      '01');

// Projektigénylés státuszok
define('PRIGENY_STAT_NYITOTT',              '01' );
define('PRIGENY_STAT_LETRE_ALATT',          '02' );
define('PRIGENY_STAT_VEL_ALATT',            '03' );
define('PRIGENY_STAT_ELUTASITVA',           '04' );
define('PRIGENY_STAT_HIANYPOTLAS',          '05' );
define('PRIGENY_STAT_HIANYPOTOLT',          '06' );
define('PRIGENY_STAT_FB_TERJESZT',          '07' );
define('PRIGENY_STAT_FB_TERJESZT_VAR',      '08' );
define('PRIGENY_STAT_BOD_TERJESZT',         '09' );
define('PRIGENY_STAT_BOD_TERJESZT_VAR',     '10' );
define('PRIGENY_STAT_VARAKOZO',             '11' );
define('PRIGENY_STAT_JOVAHAGYVA',           '12' );

// Fenntartási terv státuszok
define('ENERGIATERV_STAT_MUNKAKOZI',        '05');
define('ENERGIATERV_STAT_KESZ',             '10');
define('ENERGIATERV_STAT_ELES',             '15');

// Anyag számla státuszok
define('A_SZAMLA_STAT_FELDOLGOZASRA_VAR',   '01');
define('A_SZAMLA_STAT_IGAZOLASRA_VAR',      '02');

// Energia számla státuszok
define('E_SZAMLA_STAT_FELDOLGOZASRA_VAR',   '01');

// Fenntatási terv számla státuszok
define('F_SZAMLA_STAT_FELDOLGOZASRA_VAR',   '01');

// Projektszámla státuszok
define('P_SZAMLA_STAT_FELDOLGOZASRA_VAR',   '01');

// Menteni, csak ezekben a státuszokban lehet
function prigeny_mentheto_statuszok(){
    return array(PRIGENY_STAT_NYITOTT, PRIGENY_STAT_LETRE_ALATT, PRIGENY_STAT_VEL_ALATT, PRIGENY_STAT_HIANYPOTLAS, PRIGENY_STAT_HIANYPOTOLT, PRIGENY_STAT_FB_TERJESZT_VAR, PRIGENY_STAT_BOD_TERJESZT_VAR);
}

function prigeny_fb_bod_var(){
    return array(PRIGENY_STAT_FB_TERJESZT, PRIGENY_STAT_FB_TERJESZT_VAR, PRIGENY_STAT_BOD_TERJESZT, PRIGENY_STAT_BOD_TERJESZT_VAR);
}

// Hiánypótlásra ezekből a sátuszokból lehet léptetni
function prigeny_hianypotlasba_statuszok(){
    return array(PRIGENY_STAT_NYITOTT, PRIGENY_STAT_HIANYPOTOLT, PRIGENY_STAT_FB_TERJESZT);
}
// Ezekben a státuszokban lehet jóváhagyni egy igénylést
function prigeny_jovahagyhato_statuszok(){
    return array(PRIGENY_STAT_FB_TERJESZT, PRIGENY_STAT_NYITOTT, PRIGENY_STAT_HIANYPOTOLT, PRIGENY_STAT_VEL_ALATT, PRIGENY_STAT_FB_TERJESZT, PRIGENY_STAT_BOD_TERJESZT);
}
// FB-be ezekből a státuszokból lehet lépteni
function prigeny_fb_ele_statuszok(){
    return array(PRIGENY_STAT_NYITOTT, PRIGENY_STAT_HIANYPOTOLT, PRIGENY_STAT_VEL_ALATT, PRIGENY_STAT_BOD_TERJESZT);
}
// BOD-ba ezekből a státuszokból lehet lépteni
function prigeny_bod_ele_statuszok(){
    return array(PRIGENY_STAT_FB_TERJESZT);
}
// Véleményezőt hozzáadni ezekben a státuszokban lehet
function prigeny_velemenyezo_hozzaad_statuszok(){
    return array(PRIGENY_STAT_NYITOTT, PRIGENY_STAT_VEL_ALATT, PRIGENY_STAT_HIANYPOTOLT);
}

function specialis_karakterek_mit(){
    return array('	');
}
function specialis_karakterek_mivel(){
    return array(' ');
}

// Ha egy felhasználót létrehozunk, módosítunk, akkor az alábbi személyzetek esetén a módosítás automatikusan átkerül a törzsadatokba
function loginbol_torzsadatot_szemelyzetek(){
    return array(TELEPITETT_SZEMELYZET, MOBILCSOPORT, DISZPECSER, MUSZAKI_VEZETO, MUSZAKI_ADMINISZTRATOR, TERULETI_FELELOS, MSZK_VEZETO, MUSZAKI_IGAZGATO);
}

// Projekt státuszok
define('PRKEZEL_STAT_NYITOTT',          '01' );
define('PRKEZEL_STAT_ELOKESZITES_ALATT','02' );
define('PRKEZEL_STAT_FOLYAMATBAN',      '03' );
define('PRKEZEL_STAT_KESZ',             '04' );
define('PRKEZEL_STAT_FELFUGGESZTETT',   '05' );
define('PRKEZEL_STAT_LEZART',           '06' );

// Projektigénylés tipusok
define('PRIGENY_TIPUS_BERUH',           '1' );
define('PRIGENY_TIPUS_FENNT',           '2' );
define('PRIGENY_TIPUS_TARGY',           '3' );

// Projektigénylés besorolások
define('PRIGENY_BESOROL_FEJL',          '1' );
define('PRIGENY_BESOROL_FELU',          '2' );
define('PRIGENY_BESOROL_REKO',          '3' );

// Projektigénylés megtérülési terület
define('PRIGENY_TERULET_DOK',           '1' );

// Személyzettípusok
define('TELEPITETT_SZEMELYZET',         '1' );
define('MOBILCSOPORT',                  '2' );
define('KULSO_KIVITELEZO',              '3' );
define('MSZK_VEZETO',                   '4' );
define('MUSZAKI_VEZETO',                '5' );
define('MUSZAKI_VEZETO_HELYETTES',      '6' );
define('TERULETI_FELELOS',              '7' );
define('MUSZAKI_ADMINISZTRATOR',        '8' );
define('MUSZAKI_IGAZGATO',              '9' );
define('DISZPECSER',                    '10');
define('SZALLODA_IGAZGATO',             '11');
define('HIBABEJELENTO',                 '12');
define('FEJLESZTESI_VIG_H',             '13');
define('BERUHAZASI_IGAZGATO',           '14');
define('LETESITMENY_FELELOS',           '15');

// MozgásID-k
define('MOZGID_RAKTAR_BE',              '01' );
define('MOZGID_RAKTAR_KI',              '02' );
define('MOZGID_LELTAR',                 '03' );

// JOGOK
define('JOG_BEJELENTES_MEGTEKINT',      57);
define('JOG_BEJELENTES_SAJAT_RESZL',    0 );
define('JOG_BEJELENTES_TARSRESZLEG_BEJ_IGAZOL',    106);
define('JOG_BEJELENTES_KEZEL',          1 );
define('JOG_BEJELENTES_MAS_NEVEBEN',    56);
define('JOG_MUNKALAP_KESZITHET',        2 );
define('JOG_BEJELENTES_ELUTASIT',       41);
define('JOG_BEJELENTES_VIZSGAL',        71);
define('JOG_BEJELENTES_TORLESE',        72);
define('JOG_FELHASZNALOK_MEGTEKINT',    6 );
define('JOG_FELHASZNALOK_KEZEL',        7 );
define('JOG_FELHASZNALOK_JELSZO',       105);
define('JOG_ERTEKLISTAK_MEGTEKINT',     8 );
define('JOG_ERTEKLISTAK_KEZEL',         9 );
define('JOG_SZEREPKOR_MEGTEKINT',       10);
define('JOG_SZEREPKOR_KEZEL',           11);
define('JOG_ANYAIGENLES_MEGTEKINT',     12);
define('JOG_ANYAGIGENYLES_KEZEL',       13);
define('JOG_ANYAGIGENYLES_JOVAHAGY',    14);
define('JOG_ANYAGREND_KESZIT',          15);
define('JOG_ANYAGREND_MEGTEKINT',       16);
define('JOG_ANYAGREND_TOROL',           73);
define('JOG_RAKTAR_MEGTEKINT',          17);
define('JOG_RAKTAR_KEZEL',              18);
define('JOG_CIKKTORZS_MEGTEKINT',       19);
define('JOG_CIKKTORZS_KEZEL',           20);
define('JOG_MUNKALAP_MEGTEKINT',        3 );
define('JOG_MUNKALAP_MEGTEKINT_SZALL',  4 );
define('JOG_MUNKALAP_MEGTEKINT_FELADAT',79);
define('JOG_MUNKALAP_UJ_LETREHOZ',      5 );
define('JOG_MUNKALAP_HATIDO_UTEMEZ',    21);
define('JOG_MUNKALAP_VARAKOZTAT',       83);
define('JOG_MUNKALAP_SAJAT_KEZEL',      22);
define('JOG_MUNKALAP_SAJAT_SZALL_KEZEL',69);
define('JOG_MUNKALAP_KEZEL_TELEPITETT', 76);
define('JOG_MUNKALAP_KEZEL_MOBIL',      77);
define('JOG_MUNKALAP_KEZEL_KULSO',      78);
define('JOG_MUNKALAP_IGAZOLAS',         23);
define('JOG_MUNKALAP_MUNKAIDO_HOZZAAD_TOROLVE', 75);
define('JOG_MUNKALAP_VIZSGALAT',        24);
define('JOG_MUNKALAP_NYITOTT_KEZEL',    31);
define('JOG_MUNKALAP_SURGOS_PIPAL',     74);
define('JOG_MUNKALAP_KULSOS_DONTES',    34);
define('JOG_MUNKALAP_KULSOS_ELOKESZIT', 37);
define('JOG_MUNKALAP_KULSOS_MEGREND',   67);
define('JOG_MUNKALAP_TOROL',            68);
define('JOG_MUNKALAP_ANYAG_ERKEZTET',   70);
define('JOG_TORZSADAT_MEGTEKINT',       25);
define('JOG_TORZSADAT_KEZEL',           26);
define('JOG_TORZSADAT_SAJAT_MEGTEKINT',135);
define('JOG_SZABADSAGIG_MEGTEKINT',     27);
define('JOG_SZABADSAGIG_KEZEL',         28);
define('JOG_BEOSZTASOK_MEGTEKINT',      29);
define('JOG_BEOSZTASOK_KEZEL',          30);
define('JOG_MSZK_BEOSZTAS_MEGTEKINT',  133);
define('JOG_MSZK_BEOSZTAS_KEZEL',      134);

define('JOG_UTFEL_MEGTEKINT',           32);
define('JOG_UTFEL_KEZEL',               33);
define('JOG_UTFEL_TOROL',               80);
define('JOG_RENDSZ_KARB_MEGTEKINT',     35);
define('JOG_RENDSZ_KARB_KEZEL',         36);
define('JOG_RENDSZ_KARB_TOROL',         81);
define('JOG_ESETI_FENN_MEGTEKINT',      54);
define('JOG_ESETI_FENN_KEZEL',          55);
define('JOG_ESETI_FENN_TOROL',          82);
define('JOG_PRIGENY_LETREHOZ',          123);
define('JOG_PRIGENY_S_KEZEL',           39);
define('JOG_PRIGENY_SZ_MEGTEKINT',      38);
define('JOG_PRIGENY_SZ_KEZEL',          60);
define('JOG_PRIGENY_B_MEGTEKINT',       58);
define('JOG_PRIGENY_B_KEZEL',           59);
define('JOG_PRIGENY_F_MEGTEKINT',       61);
define('JOG_PRIGENY_F_KEZEL',           62);
define('JOG_PRIGENY_T_MEGTEKINT',       48);
define('JOG_PRIGENY_T_KEZEL',           49);
define('JOG_PRIGENY_EGY_MEGTEKINT',     46);
define('JOG_PRIGENY_EGY_KEZEL',         47);
define('JOG_PRIGENY_TOBB_MEGTEKINT',    63);
define('JOG_PRIGENY_TOBB_KEZEL',        64);
define('JOG_PRIGENY_ELUTASIT',          50);
define('JOG_PRIGENY_JOVAHAGY',          51);
define('JOG_ELOTERJ_MEGTEKINT',         65);
define('JOG_ELOTERJ_KEZEL',             66);
define('JOG_VELEMENYEZO_HOZZAAD',       40);
define('JOG_PRKEZEL_MEGTEKINT',         52);
define('JOG_PRKEZEL_KEZEL',             53);
define('JOG_PRKEZEL_SAJAT_MEGTEKINT',   118);
define('JOG_PRKEZEL_SAJAT_KEZEL',       107);
define('JOG_PRKEZEL_UJ_LETREHOZ',       108);
define('JOG_PRKEZEL_TEAM_TAG',          109);
define('JOG_PRKEZEL_SZERZODES_OLVAS',   110);
define('JOG_PRKEZEL_SZERZODES_KEZEL',   111);
define('JOG_PRKEZEL_FELFUGGESZTES',     127);
define('JOG_PRKEZEL_LEALLITAS',         128);
define('JOG_PRKEZEL_UJRANYITAS',        129);
define('JOG_PRSZAMLAK_OLVAS',           112);
define('JOG_PRSZAMLAK_KEZEL',           113);
define('JOG_PR_EGYSZALL_MEGTEKINT',     114);
define('JOG_PR_EGYSZALL_KEZEL',         115);
define('JOG_PR_TOBBSZALL_MEGTEKINT',    116);
define('JOG_PR_TOBBSZALL_KEZEL',        117);
define('JOG_PR_B_MEGTEKINT',            124);
define('JOG_PR_F_MEGTEKINT',            125);
define('JOG_PR_T_MEGTEKINT',            126);
define('JOG_PR_SAJAT_SZERZ_MEGTEKINT',  119);
define('JOG_PR_SAJAT_SZERZ_KEZEL',      120);
define('JOG_PR_SAJAT_SZAMLA_MEGTEKINT', 121);
define('JOG_PR_SAJAT_SZAMLA_KEZEL',     122);
define('JOG_ESZKOZ_MEGTEKINT',          42);
define('JOG_ESZKOZ_KEZEL',              43);
define('JOG_FT_MEGTEKINT',              44);
define('JOG_FT_KEZEL',                  45);
define('JOG_ENERGIA_EGYSEGAR_OLVAS',    103);
define('JOG_ENERGIA_EGYSEGAR_KEZEL',    104);
define('JOG_FENNTARTASI_SAJAT_SZ_OLVAS',84);
define('JOG_FENNTARTASI_SAJAT_SZ_KEZEL',85);
define('JOG_FENNTARTASI_TERV_TOROL',    101);
define('JOG_FENNTARTASI_TERV_ELESIT',   102);
define('JOG_ANYAG_SZAMLAK_OLVAS',       87);
define('JOG_ANYAG_SZAMLAK_KEZEL',       131);
define('JOG_ENERGIA_SZAMLAK_OLVAS',     88);
define('JOG_FENNTARTASI_SZAMLAK_OLVAS', 89); 
define('JOG_FENNTARTASI_SZERZ_OLVAS',   130); 
define('JOG_SZAMLAK_OLVAS',             90);
define('JOG_SZAMLAK_KEZEL',             91);
define('JOG_SZAMLAK_TOROL',             92);
define('JOG_PARTNERTORZS_OLVAS',        93);
define('JOG_PARTNERTORZS_KEZEL',        94);
define('JOG_PARTNERTORZS_NEV_SZERK',    99);
define('JOG_PARTNERTORZS_TOROL',        95);
define('JOG_DOKUMENTUMTAR_OLVAS',       96);
define('JOG_DOKUMENTUMTAR_KEZEL',       97);
define('JOG_DOKUMENTUMTAR_TOROL',       98);
define('JOG_PR_TELJES_CASH_FLOW',      132);

// ÉRTESÍTÉSEK
define('ERT_KESZRE_JELENTESKOR',         0);
define('ERT_BEJ_ELUTASITKOR',            1);
define('ERT_BEJ_REKL_ELUTASITKOR',       2);
define('ERT_BEJ_IGAZOL_ELUTASITKOR',     3);
define('ERT_BEJ_VARAKOZTATKOR',          13);
define('ERT_MUNK_KESZRE_KULSO_MOBIL',    4);
define('ERT_MUNK_VIZSGALAT_UTAN',        5);
define('ERT_MUNK_ELUTASIT_UTAN',         6);
define('ERT_MUNK_KK_ELOKESZIT_KOR',      7);
define('ERT_MUNK_KK_JT_UTAN',            8);
define('ERT_MUNK_KK_JH_UTAN',            9);

define('ERT_ANYAGIG_JOVAHAGYAS',        10);
define('ERT_ELFELEJTETT_JELSZO',        11);
define('ERT_14_NAPJA_VARO_ML',          12);
define('ERT_BEOSZTAS_ELL',              14);

define('ERT_GAR_BEJARAS',               15);

define('HIBA_ADJON_FELADAT',            ' kérem adjon hozzá egy elvégzett feladatot!');
define('DASHB_NINCS_BEJEGYZES',         'Nem található bejegyzés!');
define('NULLAS_TETEL',                  'Nullás tétel');

// Pénznem értéklistánál a HUF értéklista ID-ja
define('KCS_21_HUF',           5);

// Értéklisták
define('ERTEKLISTA_BEJ_HIBA',       4);
define('ERTEKLISTA_ELV_FELAD',      5);
define('ERTEKLISTA_MUNKALAP_TIP',   6);
define('ERTEKLISTA_ATADAS_OKA',     7);
define('ERTEKLISTA_RESZLEG',        17);
define('ERTEKLISTA_BEOSZTAS',       18);
define('ERTEKLISTA_SZAKTER',        19);
define('ERTEKLISTA_PENZNEM',        21);
define('ERTEKLISTA_AFA_TIPUS',      25);
define('ERTEKLISTA_PROJEKT_PRIOR',  40);
define('ERTEKLISTA_DOK_TIPUS',      39);
define('ERTEKLISTA_PROJEKT_SZEREP', 41);
define('ERTEKLISTA_KOLTSEGVISELO',  42);
define('ERTEKLISTA_SZERZODESTKOTO', 43);
define('ERTEKLISTA_BANK',           44);
define('ERTEKLISTA_JOVAHAGYO',      45);

// Dokumentum tipus értéklista fix, nem szerkeszthető bejegyzése
define('EL_DOK_TIPUS_ATADASI',      1);
define('EL_DOK_TIPUS_GAR_BEJAR',    2);
define('EL_DOK_TIPUS_SZERZODES',    3);
define('EL_DOK_TIPUS_MUNKATER_ATAD',4);
define('EL_DOK_TIPUS_TELJESITES',   10);

// Beosztások értéklista fix bejegyzése
define('EL_BEOSZTAS_PR_FELEOS',     189);
define('EL_BEOSZTAS_FEJL_VIG_H',    28 );
define('EL_BEOSZTAS_MUSZAKI_IG',    91 );
define('EL_BEOSZTAS_MUSZAKI_ASSZ',  131);
define('EL_BEOSZTAS_BERUH_IG',      190);
define('EL_BEOSZTAS_TER_FELELOS',   111);
define('EL_BEOSZTAS_DISZ_KP_VEZ',   16 );

// Munkalap típusok
define('MLTIPUS_HIBAELHARITAS', 1);
define('MLTIPUS_RENDSZERES_K',  2);
define('MLTIPUS_UTEMEZETT_F',   3);
define('MLTIPUS_ESETI_FENNT',   5);
define('MLTIPUS_GARANCIALIS',   6);

// Limit értékek
define('ARCHIV_NAVPANEL_LIMIT',     1000);
define('SZURES_DB_LIMIT',           1000);
define('CSV_EXPORT_LIMIT',          30000);
define('ANYAGREND_NEM_MSZK_LIMIT',  200);


// Fűtőértékkel kapcsolatos konstansok
define('FUTOERTEK_KOD',         '5114400'               );
define('FUTOERTEK_MEE',         'MJ/m3'                 );
define('FUTOERTEK_DIJELEM',     'futoertek'             );
define('FUTOERTEK_MEE2',        'Ft/MJ'                 );
define('FUTOERTEK_MEE3',        'Ft/m3'                 );
define('GAZFOGYASZTAS_DIJELEM', 'Gáz fogyasztás (m3)'   );

define('VISSZA_MOD_VISSZAADAS',  1);
define('VISSZA_MOD_VISSZAELOK',  2);

// Dashboard
define('DASHBOARD_H_A_ML_CIMKE',  'Hiányzó anyag igénylésére váró munkalapok'); // Ez az egy azért van konstansban, mert egy helyen a működést is befolyásolja.

// Cash-flow
define('CASH_FLOW_MAX_HONAPOK',  60);

// Beosztás fix műszak jelei
define('BEOSZTAS_FIX_SZABADNAP_JEL',  'SZ');
define('BEOSZTAS_FIX_PIHENONAP_JEL',  'P');
define('BEOSZTAS_FIX_BETEGSEG_JEL',   'B');
define('TELEPHELY_SZK',              1106);

// Beosztás ünnepnap típusok
define('BEOSZTAS_UNNEPNAP_MUNKASZUNET', 1);
define('BEOSZTAS_UNNEPNAP_PIHENO',      2);
define('BEOSZTAS_UNNEPNAP_HELYETTES',   3);