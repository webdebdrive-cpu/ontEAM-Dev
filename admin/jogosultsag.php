<?php
/*                                                  *\
 * FONTOS:
 * 79-es ID-nál járunk
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

//Törölni így tudsz:
//123  => array('torolve' => 'TÖRÖLVE - a lényeg, hogy legyen egy torolve attribútuma is.',  'nev' => 'A törölt jog neve',   'kat' => 123, 'sor' => 123),
    0  => array('nev' => 'Saját részleg bejelentések megtekintése',         'kat' => 0, 'sor' => 0  ),
    57 => array('nev' => 'Saját szállodák bejelentéseinek megtekintése',    'kat' => 0, 'sor' => 1  ),
    1  => array('nev' => 'Bejelentés saját névben',                         'kat' => 0, 'sor' => 2  ),
    56 => array('nev' => 'Bejelentés más nevében',                          'kat' => 0, 'sor' => 3  ),
    41 => array('nev' => 'Bejelentések elutasítása',                        'kat' => 0, 'sor' => 4  ),
    71 => array('nev' => 'Bejelentések vizsgálat',                          'kat' => 0, 'sor' => 5  ),
    72 => array('nev' => 'Bejelentések törlése',                            'kat' => 0, 'sor' => 6  ),

    75 => array('torolve' => 'TÖRÖLVE', 'nev' => 'Munkaidő hozzáadása',     'kat' => 1, 'sor' => 0  ),
    3  => array('nev' => 'Saját szerepkör megtekintése',                    'kat' => 1, 'sor' => 1, 'i' => 'telepített, mobil, külső partner'  ),
    4  => array('nev' => 'Saját szállodák megtekintése',                    'kat' => 1, 'sor' => 2, 'i' => 'diszpécser, MSZK vezető, műszaki vezető, területi felelős'  ),
    79 => array('nev' => 'Saját feladatok megtekintése',                    'kat' => 1, 'sor' => 3, 'i' => 'Szálloda igazgató, műszaki ügyintéző, területi felelős<br>kezelt jogok: igazolás, vizsgálat, külsős előkészítés,<br>jóváhagyás, megrendelés készítése'  ),
    5  => array('nev' => 'Új létrehozása',                                  'kat' => 1, 'sor' => 4  ),
    2  => array('nev' => 'Munkalapot készíthet bejelentésből',              'kat' => 1, 'sor' => 5  ),
    31 => array('nev' => 'Nyitottak kezelése',                              'kat' => 1, 'sor' => 6  ),
    74 => array('nev' => 'Sürgősség megváltoztatása',                       'kat' => 1, 'sor' => 7  ),
    21 => array('nev' => 'Várakoztatás',                                    'kat' => 1, 'sor' => 8  ),
    68 => array('nev' => 'Munkalap törlése',                                'kat' => 1, 'sor' => 9  ),
    22 => array('nev' => 'Saját szerepkör kezelése',                        'kat' => 1, 'sor' => 10 ),
    69 => array('nev' => 'Saját szállodák kezelése',                        'kat' => 1, 'sor' => 11, 'nincs_pipa' => 1, 'i' => 'Műszaki vezető, Területi felelős' ),
    76 => array('nev' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Telepített', 'kat' => 1, 'sor' => 12 ),
    77 => array('nev' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Mobil',      'kat' => 1, 'sor' => 13 ),
    78 => array('nev' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Külsős',     'kat' => 1, 'sor' => 14 ),
    23 => array('nev' => 'Igazolás',                                        'kat' => 1, 'sor' => 15 ),
    24 => array('nev' => 'Vizsgálat',                                       'kat' => 1, 'sor' => 16 ),
    37 => array('nev' => 'Külsős: előkészítés',                             'kat' => 1, 'sor' => 17 ),
    34 => array('nev' => 'Külsős jóváhagyás',                               'kat' => 1, 'sor' => 18 ),
    67 => array('nev' => 'Külsős: megrendelés készítése',                   'kat' => 1, 'sor' => 19 ),
    
    6  => array('nev' => 'Felhasználók megtekintése',   'kat' => 2, 'sor' => 6),
    7  => array('nev' => 'Felhasználók kezelése',       'kat' => 2, 'sor' => 7),
    8  => array('nev' => 'Értéklisták megtekintése',    'kat' => 2, 'sor' => 8),
    9  => array('nev' => 'Értéklisták kezelése',        'kat' => 2, 'sor' => 9),
    10 => array('nev' => 'Szerepkörök megtekintése',    'kat' => 2, 'sor' => 10),
    11 => array('nev' => 'Szerepkörök kezelése',        'kat' => 2, 'sor' => 11),

    12 => array('nev' => 'Anyagigénylés megtekintése',  'kat' => 3, 'sor' => 1),
    13 => array('nev' => 'Új igénylés létrehozása',     'kat' => 3, 'sor' => 2, 'i' => 'Műszaki vezető'),
    14 => array('nev' => 'Igénylés jóváhagyása',        'kat' => 3, 'sor' => 3, 'i' => 'Szálloda igazgató'),
    15 => array('nev' => 'Anyagrendelés készítése',     'kat' => 3, 'sor' => 4, 'i' => 'Adminisztrátor hölgyek',),
    70 => array('nev' => 'Anyag érkeztetése',           'kat' => 3, 'sor' => 5 ),
    16 => array('nev' => 'Anyagrendelés megtekintése',  'kat' => 3, 'sor' => 6),
    73 => array('nev' => 'Anyagrendelés törlése',       'kat' => 3, 'sor' => 7),
    17 => array('nev' => 'Raktár megtekintése',         'kat' => 3, 'sor' => 8),
    18 => array('nev' => 'Raktár kezelése',             'kat' => 3, 'sor' => 9),
    19 => array('nev' => 'Cikktörzs megtekintése',      'kat' => 3, 'sor' => 10),
    20 => array('nev' => 'Cikktörzs kezelése',          'kat' => 3, 'sor' => 11),
    
    32 => array('nev' => 'Feladatok megtekintése',      'kat' => 5, 'sor' => 1),
    33 => array('nev' => 'Feladatok kezelése',          'kat' => 5, 'sor' => 2),
    
    35 => array('nev' => 'Karbantartások megtekintése', 'kat' => 6, 'sor' => 1),
    36 => array('nev' => 'Karbantartások kezelése',     'kat' => 6, 'sor' => 2),
    
    54 => array('nev' => 'Eseti fenntartás megtekintése','kat' => 9, 'sor' => 1),
    55 => array('nev' => 'Eseti fenntartás kezelése',    'kat' => 9, 'sor' => 2),
    
    25 => array('nev' => 'Törzsadatok olvasása',        'kat' => 4, 'sor' => 1),
    26 => array('nev' => 'Törzsadatok kezelése',        'kat' => 4, 'sor' => 2),
    27 => array('nev' => 'Szabadságigények olvasása',   'kat' => 4, 'sor' => 3),
    28 => array('nev' => 'Szabadságigények kezelése',   'kat' => 4, 'sor' => 4),
    29 => array('nev' => 'Beosztások olvasása',         'kat' => 4, 'sor' => 5),
    30 => array('nev' => 'Beosztások kezelése',         'kat' => 4, 'sor' => 6),
    
    39 => array('nev' => 'Saját igénylések kezelése',               'kat' => 7, 'sor' => 1),
    38 => array('nev' => 'Saját szállodák igényeinek megtekintése', 'kat' => 7, 'sor' => 2),
    60 => array('nev' => 'Saját szállodák kezelése',                'kat' => 7, 'sor' => 3),
    58 => array('nev' => 'Beruházási igények megtekintése',         'kat' => 7, 'sor' => 4),
    59 => array('nev' => 'Beruházási igények kezelése',             'kat' => 7, 'sor' => 5),
    61 => array('nev' => 'Fenntartási igények megtekintése',        'kat' => 7, 'sor' => 6),
    62 => array('nev' => 'Fenntartási igények kezelése',            'kat' => 7, 'sor' => 7),
    48 => array('nev' => 'Tárgyi eszköz igénylések megtekintése',   'kat' => 7, 'sor' => 8),
    49 => array('nev' => 'Tárgyi eszköz igénylések kezelése',       'kat' => 7, 'sor' => 9),
    46 => array('nev' => 'Egy szállodás igények megtekintése',      'kat' => 7, 'sor' => 10),
    47 => array('nev' => 'Egy szállodás igények kezelése',          'kat' => 7, 'sor' => 11),
    63 => array('nev' => 'Több szállodás igények megtekintése',     'kat' => 7, 'sor' => 12),
    64 => array('nev' => 'Több szállodás igények kezelése',         'kat' => 7, 'sor' => 13),
    50 => array('nev' => 'Projekt igénylések elutasítása',          'kat' => 7, 'sor' => 14),
    51 => array('nev' => 'Projekt igénylések jóváhagyása',          'kat' => 7, 'sor' => 15),
    40 => array('nev' => 'Véleményező hozzáadása',                  'kat' => 7, 'sor' => 16),
    65 => array('nev' => 'Előterjesztések megtekintése',            'kat' => 7, 'sor' => 17),
    66 => array('nev' => 'Előterjesztések kezelése',                'kat' => 7, 'sor' => 18),
    
    52 => array('nev' => 'Projektek megtekintése',                  'kat' => 10, 'sor' => 9),
    53 => array('nev' => 'Projektek kezelése',                      'kat' => 10, 'sor' => 10),

    42 => array('nev' => 'Eszközök olvasása',               'kat' => 8, 'sor' => 1),
    43 => array('nev' => 'Eszközök kezelése',               'kat' => 8, 'sor' => 2),
    
    44 => array('nev' => 'Felmérési tervek olvasása',         'kat' => 11, 'sor' => 3),
    45 => array('nev' => 'Felmérési tervek kezelése',         'kat' => 11, 'sor' => 4),
);
