<?php
/*                                                  *\
 * FONTOS:
 * 70-es ID-nál járunk
 * 11 -es kategória ID-nál járunk
\*                                                  */

$jogosultsagKatok = array(
    0 => array('nev' => 'Bejelentések',             'sor' => 0),
    1 => array('nev' => 'Munkalapok',               'sor' => 1),
    2 => array('nev' => 'Rendszer',                 'sor' => 2),
    3 => array('nev' => 'Anyagok',                  'sor' => 3),
    5 => array('nev' => 'Ütemezett feladatok',      'sor' => 4),
    6 => array('nev' => 'Rendszeres karbantartás',  'sor' => 5),
    9 => array('nev' => 'Eseti fenntartás',         'sor' => 6),
    4 => array('nev' => 'Személyzet',               'sor' => 7),
    7 => array('nev' => 'Projekt igények',          'sor' => 8),
   10 => array('nev' => 'Projektek',                'sor' => 9),
   11 => array('nev' => 'Felmérési tervek',         'sor' => 10),
    8 => array('nev' => 'Eszközök',                 'sor' => 11),
);

$jogosultsagok = array(
//Ez a _jog() ID_ja, tehát a => előtti rész
    0  => array('nev' => 'Saját részleg bejelentések megtekintése',         'kat' => 0, 'sor' => 0,                        'kikapcs' => '1,2'  ),
    57 => array('nev' => 'Saját szállodák bejelentéseinek megtekintése',    'kat' => 0, 'sor' => 1,                        'kikapcs' => '1,2'  ),
    1  => array('nev' => 'Bejelentés saját névben',                         'kat' => 0, 'sor' => 2, 'bekapcs' => '0',      'kikapcs' => '56'   ),
    56 => array('nev' => 'Bejelentés más nevében',                          'kat' => 0, 'sor' => 3, 'bekapcs' => '57',     'kikapcs' => '1'    ),
    41 => array('nev' => 'Bejelentések elutasítása',                        'kat' => 0, 'sor' => 4, 'bekapcs' => '1'   ),

    3  => array('nev' => 'Saját megtekintése',                              'kat' => 1, 'sor' => 1  ),
    4  => array('nev' => 'Saját szállodák megtekintése',                    'kat' => 1, 'sor' => 2,  'kikapcs' => '3'   ),
    5  => array('nev' => 'Új létrehozása',                                  'kat' => 1, 'sor' => 3  ),
    68 => array('nev' => 'Munkalap törlése',                                'kat' => 1, 'sor' => 4  ),
    2  => array('nev' => 'Munkalapot készíthet',                            'kat' => 1, 'sor' => 5  ),
    31 => array('nev' => 'Nyitottak kezelése',                              'kat' => 1, 'sor' => 6  ),
    21 => array('nev' => 'Várakoztatás',                                    'kat' => 1, 'sor' => 7  ),
    22 => array('nev' => 'Saját kezelése',                                  'kat' => 1, 'sor' => 8  ),
    69 => array('nev' => 'Saját szálloda kezelése',                         'kat' => 1, 'sor' => 9  ),
    23 => array('nev' => 'Igazolás',                                        'kat' => 1, 'sor' => 10 ),
    24 => array('nev' => 'Vizsgálat',                                       'kat' => 1, 'sor' => 11 ),
    37 => array('nev' => 'Külsős: előkészítés',                             'kat' => 1, 'sor' => 12 ),
    34 => array('nev' => 'Külsős jóváhagyás',                               'kat' => 1, 'sor' => 13 ),
    67 => array('nev' => 'Külsős: megrendelés készítése',                   'kat' => 1, 'sor' => 14 ),
    70 => array('nev' => 'Anyag érkeztetése',                               'kat' => 1, 'sor' => 15 ),
    
    
    //Korábban ez volt
//    3  => array('nev' => 'Munkalap megtekintése',                           'kat' => 1, 'sor' => 1,                          'kikapcs' => '5,21,22,23,24'),
//    2  => array('nev' => 'Munkalapot készíthet',                            'kat' => 1, 'sor' => 2,  'bekapcs' => '56,57',   'kikapcs' => '1,5,31,21,22,24,34', 'i' => 'diszpécserek'),
//    4  => array('torolve' => 'TÖRÖLVE',  'nev' => 'Munkalap kezelése',      'kat' => 1, 'sor' => 4),
//    5  => array('nev' => 'Munkalapok kezelése mint telepített',             'kat' => 1, 'sor' => 5,  'bekapcs' => '3',       'kikapcs' => '2,21,22,23,24,31,34,37'),
//    31 => array('nev' => 'Munkalapok kezelése mint mobil',                  'kat' => 1, 'sor' => 6,  'bekapcs' => '3',       'kikapcs' => '2,5,21,22,23,24,34,37'),
//    21 => array('nev' => 'Munkalapok kezelése mint műszaki vezető',         'kat' => 1, 'sor' => 8,  'bekapcs' => '3',       'kikapcs' => '2,5,22,23,24,31,34,37'),
//    22 => array('nev' => 'Munkalapok kezelése mint területi felelős',       'kat' => 1, 'sor' => 9,  'bekapcs' => '3',       'kikapcs' => '2,5,21,23,24,31,34,37'),
//    23 => array('nev' => 'Munkalapok kezelése mint Diszp.központ vezető',   'kat' => 1, 'sor' => 10, 'bekapcs' => '0,1,2,3', 'kikapcs' => '5,21,22,24,31,34,37', 'kikapcskor_kikapcs' => '2'),
//    24 => array('nev' => 'Munkalapok kezelése mint adminisztrátor',         'kat' => 1, 'sor' => 11, 'bekapcs' => '3',       'kikapcs' => '2,5,21,22,23,31,34,37'),
//    37 => array('nev' => 'Munkalapok kezelése mint műszaki adminisztrátor', 'kat' => 1, 'sor' => 11, 'bekapcs' => '3',       'kikapcs' => '2,5,21,22,23,24,31,34'),
//    34 => array('nev' => 'Munkalapok kezelése mint külső kivitelező',       'kat' => 1, 'sor' => 12, 'bekapcs' => '3',       'kikapcs' => '2,5,21,24,22,23,31,37'),

    6  => array('nev' => 'Felhasználók megtekintése',   'kat' => 2, 'sor' => 6,  'kikapcs' => '7'),
    7  => array('nev' => 'Felhasználók kezelése',       'kat' => 2, 'sor' => 7,  'bekapcs' => '6'),
    8  => array('nev' => 'Értéklisták megtekintése',    'kat' => 2, 'sor' => 8,  'kikapcs' => '9'),
    9  => array('nev' => 'Értéklisták kezelése',        'kat' => 2, 'sor' => 9,  'bekapcs' => '8'),
    10 => array('nev' => 'Szerepkörök megtekintése',    'kat' => 2, 'sor' => 10, 'kikapcs' => '11'),
    11 => array('nev' => 'Szerepkörök kezelése',        'kat' => 2, 'sor' => 11, 'bekapcs' => '10'),

    12 => array('nev' => 'Anyagigénylés megtekintése',  'kat' => 3, 'sor' => 1, 'kikapcs' => '13,14,15'),
    13 => array('nev' => 'Anyagigénylés kezelése',      'kat' => 3, 'sor' => 2, 'i' => 'Vezető, új igénylés készítése. Törölhet, ha a státusza<br> nyitott, árazva, várakozó.', 'bekapcs' => '12', 'kikapcs' => '14,15'),
    14 => array('nev' => 'Igénylés jóváhagyása',        'kat' => 3, 'sor' => 3, 'i' => 'Vezető, árak és mennyiségek jóváhagyása.',  'bekapcs' => '12', 'kikapcs' => '13,15'),
    15 => array('nev' => 'Anyagrendelés készítése',     'kat' => 3, 'sor' => 4, 'i' => 'Adminisztrátor hölgyek',                    'bekapcs' => '12', 'kikapcs' => '13,14'),
    16 => array('nev' => 'Anyagrendelés megtekintése',  'kat' => 3, 'sor' => 5),
    17 => array('nev' => 'Raktár megtekintése',         'kat' => 3, 'sor' => 6, 'kikapcs' => '18'),
    18 => array('nev' => 'Raktár kezelése',             'kat' => 3, 'sor' => 7, 'bekapcs' => '17'),
    19 => array('nev' => 'Cikktörzs megtekintése',      'kat' => 3, 'sor' => 8, 'kikapcs' => '20'),
    20 => array('nev' => 'Cikktörzs kezelése',          'kat' => 3, 'sor' => 9, 'bekapcs' => '19'),
    
    32 => array('nev' => 'Feladatok megtekintése',      'kat' => 5, 'sor' => 1, 'kikapcs' => '33'),
    33 => array('nev' => 'Feladatok kezelése',          'kat' => 5, 'sor' => 2, 'bekapcs' => '32'),
    
    35 => array('nev' => 'Karbantartások megtekintése', 'kat' => 6, 'sor' => 1, 'kikapcs' => '36'),
    36 => array('nev' => 'Karbantartások kezelése',     'kat' => 6, 'sor' => 2, 'bekapcs' => '35'),
    
    54 => array('nev' => 'Eseti fenntartás megtekintése','kat' => 9, 'sor' => 1, 'kikapcs' => '55'),
    55 => array('nev' => 'Eseti fenntartás kezelése',    'kat' => 9, 'sor' => 2, 'bekapcs' => '54'),
    
    25 => array('nev' => 'Törzsadatok olvasása',        'kat' => 4, 'sor' => 1, 'kikapcs' => '26'),
    26 => array('nev' => 'Törzsadatok kezelése',        'kat' => 4, 'sor' => 2, 'bekapcs' => '25'),
    27 => array('nev' => 'Szabadságigények olvasása',   'kat' => 4, 'sor' => 3, 'kikapcs' => '28'),
    28 => array('nev' => 'Szabadságigények kezelése',   'kat' => 4, 'sor' => 4, 'bekapcs' => '27'),
    29 => array('nev' => 'Beosztások olvasása',         'kat' => 4, 'sor' => 5, 'kikapcs' => '30'),
    30 => array('nev' => 'Beosztások kezelése',         'kat' => 4, 'sor' => 6, 'bekapcs' => '29'),
    
    39 => array('nev' => 'Saját igénylések kezelése',               'kat' => 7, 'sor' => 1),
    38 => array('nev' => 'Saját szállodák igényeinek megtekintése', 'kat' => 7, 'sor' => 2, 'kikapcs' => '60'),
    60 => array('nev' => 'Saját szállodák kezelése',                'kat' => 7, 'sor' => 3, 'bekapcs' => '38'),
    58 => array('nev' => 'Beruházási igények megtekintése',         'kat' => 7, 'sor' => 4, 'kikapcs' => '59'),
    59 => array('nev' => 'Beruházási igények kezelése',             'kat' => 7, 'sor' => 5, 'bekapcs' => '58'),
    61 => array('nev' => 'Fenntartási igények megtekintése',        'kat' => 7, 'sor' => 6, 'kikapcs' => '62'),
    62 => array('nev' => 'Fenntartási igények kezelése',            'kat' => 7, 'sor' => 7, 'bekapcs' => '61'),
    48 => array('nev' => 'Tárgyi eszköz igénylések megtekintése',   'kat' => 7, 'sor' => 8, 'kikapcs' => '49'),
    49 => array('nev' => 'Tárgyi eszköz igénylések kezelése',       'kat' => 7, 'sor' => 9, 'bekapcs' => '48'),
    46 => array('nev' => 'Egy szállodás igények megtekintése',      'kat' => 7, 'sor' => 10,'kikapcs' => '47'),
    47 => array('nev' => 'Egy szállodás igények kezelése',          'kat' => 7, 'sor' => 11,'bekapcs' => '46'),
    63 => array('nev' => 'Több szállodás igények megtekintése',     'kat' => 7, 'sor' => 12,'kikapcs' => '64'),
    64 => array('nev' => 'Több szállodás igények kezelése',         'kat' => 7, 'sor' => 13,'bekapcs' => '63'),
    50 => array('nev' => 'Projekt igénylések elutasítása',          'kat' => 7, 'sor' => 14,'bekapcs' => '46,47'),
    51 => array('nev' => 'Projekt igénylések jóváhagyása',          'kat' => 7, 'sor' => 15,'bekapcs' => '46,47'),
    40 => array('nev' => 'Véleményező hozzáadása',                  'kat' => 7, 'sor' => 16),
    65 => array('nev' => 'Előterjesztések megtekintése',            'kat' => 7, 'sor' => 17, 'kikapcs' => '66'),
    66 => array('nev' => 'Előterjesztések kezelése',                'kat' => 7, 'sor' => 18, 'bekapcs' => '65'),
    
    52 => array('nev' => 'Projektek megtekintése',                  'kat' => 10, 'sor' => 9, 'kikapcs' => '53'),
    53 => array('nev' => 'Projektek kezelése',                      'kat' => 10, 'sor' => 10,'bekapcs' => '52'),

    42 => array('nev' => 'Eszközök olvasása',               'kat' => 8, 'sor' => 1, 'kikapcs' => '43'),
    43 => array('nev' => 'Eszközök kezelése',               'kat' => 8, 'sor' => 2, 'bekapcs' => '42'),
    
    
    44 => array('nev' => 'Felmérési tervek olvasása',         'kat' => 11, 'sor' => 3, 'kikapcs' => '45'),
    45 => array('nev' => 'Felmérési tervek kezelése',         'kat' => 11, 'sor' => 4, 'bekapcs' => '44'),
);
