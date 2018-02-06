<?php
/*                                                  *\
 * FONTOS:
 * 11-es ID-nál járunk
 * 3 -es kategória ID-nál járunk
\*                                                  */

$ertesitesekKatok = array(
    0 => array('nev' => 'Bejelentések',             'sor' => 0),
    1 => array('nev' => 'Munkalapok',               'sor' => 1),
    2 => array('nev' => 'Anyagok',                  'sor' => 2),
    3 => array('nev' => 'Egyéb',                    'sor' => 3),
);

$ertesitesek = array(
//Ez a _ert() ID_ja, tehát a => előtti rész

//Törölni így tudsz:
//123  => array('torolve' => 'TÖRÖLVE - a lényeg, hogy legyen egy torolve attribútuma is.',  'nev' => 'A törölt értesítés neve',   'kat' => 123, 'sor' => 123),
    0  => array('nev' => 'Bejelentés készrejelentésekor',                                               'kat' => 0, 'sor' => 0, 'i' => 'igazolásra vár' ),
    1  => array('nev' => 'Bejelentés elutasításakor',                                                   'kat' => 0, 'sor' => 1, 'i' => 'elutasítva'     ),
    2  => array('nev' => 'Bejelentés reklamáció elutasításakor',                                        'kat' => 0, 'sor' => 2, 'i' => 'lezárt'         ),
    3  => array('nev' => 'Bejelentés igazolás elutasításakor',                                          'kat' => 0, 'sor' => 3, 'i' => 'vizsgálat alatt' ),

    4  => array('nev' => 'Munkalap készrejelentésekor, ha hozzárendelt személyzet mobil vagy külsős',   'kat' => 1, 'sor' => 1, 'i' => 'igazolásra vár'     ),
    5  => array('nev' => 'Munkalap vizsgálat utáni lezárása után',                                      'kat' => 1, 'sor' => 2, 'i' => 'lezárt'             ),
    6  => array('nev' => 'Munkalap igazolás elutasítása után',                                          'kat' => 1, 'sor' => 3, 'i' => 'vizsgálat alatt'    ),
    7  => array('nev' => 'Külső kivitelező előkészítésekor',                                            'kat' => 1, 'sor' => 4, 'i' => 'előkészítés alatt'  ),
    8  => array('nev' => 'Külső kivitelező javaslattétel után',                                         'kat' => 1, 'sor' => 5, 'i' => 'döntésre vár'       ),
    9  => array('nev' => 'Külső kivitelező jóváhagyás után',                                            'kat' => 1, 'sor' => 6, 'i' => 'megrendelésre vár'  ),

    10  => array('nev' => 'Igénylés jóváhagyása előtt',                                                 'kat' => 2, 'sor' => 1, 'i' => 'árazva'),

    11  => array('nev' => 'Értesítés, amikor egy felhasználó elfelejti a jelszavát.',                   'kat' => 3, 'sor' => 1),
);
 