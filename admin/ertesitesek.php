<?php
/*                                                  *\
 * FONTOS:
 * 33. ID következik
 * 10. kategória következik
\*                                                  */

$ertesitesekKatok = array(
    0 => array('nev' => 'Bejelentések',             'sor' => 0),
    1 => array('nev' => 'Munkalapok',               'sor' => 1),
    2 => array('nev' => 'Anyagok',                  'sor' => 2),
    3 => array('nev' => 'Felhasználók',             'sor' => 3),
    4 => array('nev' => 'Személyzet',               'sor' => 4),
    5 => array('nev' => 'Projektek',                'sor' => 5),
    6 => array('nev' => 'Projekt számlák',          'sor' => 6),
    7 => array('nev' => 'Megállapodás számlák',     'sor' => 7),
    8 => array('nev' => 'Fenntartás számák',        'sor' => 8),
    9 => array('nev' => 'Beszerzés',                'sor' => 9),
);

$ertesitesek = array(
// Ez a _ert() ID_ja, tehát a => előtti rész

// Törölni így tudsz:
// 123  => array('torolve' => 'TÖRÖLVE - a lényeg, hogy legyen egy torolve attribútuma is.',  'nev' => 'A törölt értesítés neve',   'kat' => 123, 'sor' => 123),
    0   => array('nev' => 'Bejelentés készrejelentésekor',                                               'kat' => 0, 'sor' => 0, 'i' => 'igazolásra vár'    ),
    1   => array('nev' => 'Bejelentés elutasításakor',                                                   'kat' => 0, 'sor' => 1, 'i' => 'elutasítva'        ),
    2   => array('nev' => 'Bejelentés reklamáció elutasításakor',                                        'kat' => 0, 'sor' => 2, 'i' => 'lezárt'            ),
    3   => array('nev' => 'Bejelentés igazolás elutasításakor',                                          'kat' => 0, 'sor' => 3, 'i' => 'vizsgálat alatt'   ),
    13  => array('nev' => 'Bejelentés várakoztatásakor',                                                 'kat' => 0, 'sor' => 4, 'i' => 'várakozó'          ),

    4   => array('nev' => 'Munkalap készrejelentésekor,<br>ha hozzárendelt személyzet mobil vagy külsős','kat' => 1, 'sor' => 1, 'i' => 'igazolásra vár'    ),
    5   => array('nev' => 'Munkalap vizsgálat utáni lezárása után',                                      'kat' => 1, 'sor' => 2, 'i' => 'lezárt'            ),
    6   => array('nev' => 'Munkalap igazolás elutasítása után',                                          'kat' => 1, 'sor' => 3, 'i' => 'vizsgálat alatt'   ),
    7   => array('nev' => 'Külső kivitelező előkészítésekor',                                            'kat' => 1, 'sor' => 4, 'i' => 'előkészítés alatt' ),
    8   => array('nev' => 'Külső kivitelező javaslattétel után',                                         'kat' => 1, 'sor' => 5, 'i' => 'döntésre vár'      ),
    9   => array('nev' => 'Külső kivitelező jóváhagyás után',                                            'kat' => 1, 'sor' => 6, 'i' => 'megrendelésre vár' ),
    12  => array('nev' => 'Több, mint 14 napja várakozó munkalapok',                                     'kat' => 1, 'sor' => 7),

    10  => array('nev' => 'Igénylés jóváhagyása előtt',                                                 'kat' => 2, 'sor' => 1, 'i' => 'árazva'),

    11  => array('nev' => 'Amikor egy felhasználó elfelejti a jelszavát',                               'kat' => 3, 'sor' => 1),
    
    14  => array('nev' => 'Munkaidőbeosztás szerinti szálloda nincs hozzárendelve felhasználóhoz',      'kat' => 4, 'sor' => 1),
    
    15  => array('nev' => 'Garanciális bejárás előtt',                                                  'kat' => 5, 'sor' => 1),
    
    16  => array('nev' => 'Igazolásra küldéskor',                                                       'kat' => 6, 'sor' => 1),
    17  => array('nev' => 'Utóigazolásra küldéskor',                                                    'kat' => 6, 'sor' => 2),
    18  => array('nev' => 'Igazolónak visszaadáskor',                                                   'kat' => 6, 'sor' => 3),
    19  => array('nev' => 'Asszisztensnek visszaadáskor',                                               'kat' => 6, 'sor' => 3),

    22  => array('nev' => 'Igazolásra küldéskor',                                                       'kat' => 7, 'sor' => 1),
    20  => array('nev' => 'Asszisztensnek visszaadáskor',                                               'kat' => 7, 'sor' => 2),

    21  => array('nev' => 'Műszaki ügyintézőnek visszaadáskor',                                         'kat' => 8, 'sor' => 1),

    23  => array('nev' => 'Igényfeladás megnyitásakor és igényfeladás zárásakor',                       'kat' => 9, 'sor' => 1),
    24  => array('nev' => 'Műszaki jóváhagyás előtt',                                                   'kat' => 9, 'sor' => 2),
    25  => array('nev' => 'Szállodai engedélyezés előtt',                                               'kat' => 9, 'sor' => 3),
    26  => array('nev' => 'BI validálás előtt',                                                         'kat' => 9, 'sor' => 4),
    32  => array('nev' => 'BI jóváhagyás előtt',                                                        'kat' => 9, 'sor' => 5),
    27  => array('nev' => 'Szakmai véleményezés előtt',                                                 'kat' => 9, 'sor' => 6),
    28  => array('nev' => 'Felsővez. jóváhagyás előtt',                                                 'kat' => 9, 'sor' => 7),
    29  => array('nev' => 'BI véglegesítés előtt',                                                      'kat' => 9, 'sor' => 8),
    30  => array('nev' => 'Megrendelő kiküldése előtt',                                                 'kat' => 9, 'sor' => 9),
    31  => array('nev' => 'Igénylés elutasításakor',                                                    'kat' => 9, 'sor' => 10),
    
);