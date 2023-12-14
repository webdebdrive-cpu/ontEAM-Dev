<?php
/*                                                  *\
 * FONTOS:
 * 19-as ID-nál járunk
 * 7 -es kategória ID-nál járunk
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
);

$ertesitesek = array(
// Ez a _ert() ID_ja, tehát a => előtti rész

// Törölni így tudsz:
// 123  => array('torolve' => 'TÖRÖLVE - a lényeg, hogy legyen egy torolve attribútuma is.',  'nev' => 'A törölt értesítés neve',   'kat' => 123, 'sor' => 123),
    ERT_KESZRE_JELENTESKOR      => array('nev' => 'Bejelentés készrejelentésekor',                                               'kat' => 0, 'sor' => 0, 'i' => 'igazolásra vár' ),
    ERT_BEJ_ELUTASITKOR         => array('nev' => 'Bejelentés elutasításakor',                                                   'kat' => 0, 'sor' => 1, 'i' => 'elutasítva'     ),
    ERT_BEJ_REKL_ELUTASITKOR    => array('nev' => 'Bejelentés reklamáció elutasításakor',                                        'kat' => 0, 'sor' => 2, 'i' => 'lezárt'         ),
    ERT_BEJ_IGAZOL_ELUTASITKOR  => array('nev' => 'Bejelentés igazolás elutasításakor',                                          'kat' => 0, 'sor' => 3, 'i' => 'vizsgálat alatt'),
    ERT_BEJ_VARAKOZTATKOR       => array('nev' => 'Bejelentés várakoztatásakor',                                                 'kat' => 0, 'sor' => 4, 'i' => 'várakozó'),

    ERT_MUNK_KESZRE_KULSO_MOBIL => array('nev' => 'Munkalap készrejelentésekor,<br>ha hozzárendelt személyzet mobil vagy külsős','kat' => 1, 'sor' => 1, 'i' => 'igazolásra vár'     ),
    ERT_MUNK_VIZSGALAT_UTAN     => array('nev' => 'Munkalap vizsgálat utáni lezárása után',                                      'kat' => 1, 'sor' => 2, 'i' => 'lezárt'             ),
    ERT_MUNK_ELUTASIT_UTAN      => array('nev' => 'Munkalap igazolás elutasítása után',                                          'kat' => 1, 'sor' => 3, 'i' => 'vizsgálat alatt'    ),
    ERT_MUNK_KK_ELOKESZIT_KOR   => array('nev' => 'Külső kivitelező előkészítésekor',                                            'kat' => 1, 'sor' => 4, 'i' => 'előkészítés alatt'  ),
    ERT_MUNK_KK_JT_UTAN         => array('nev' => 'Külső kivitelező javaslattétel után',                                         'kat' => 1, 'sor' => 5, 'i' => 'döntésre vár'       ),
    ERT_MUNK_KK_JH_UTAN         => array('nev' => 'Külső kivitelező jóváhagyás után',                                            'kat' => 1, 'sor' => 6, 'i' => 'megrendelésre vár'  ),
    ERT_14_NAPJA_VARO_ML        => array('nev' => 'Több, mint 14 napja várakozó munkalapok',                                     'kat' => 1, 'sor' => 7),

    ERT_ANYAGIG_JOVAHAGYAS      => array('nev' => 'Igénylés jóváhagyása előtt',                                                 'kat' => 2, 'sor' => 1, 'i' => 'árazva'),

    ERT_ELFELEJTETT_JELSZO      => array('nev' => 'Amikor egy felhasználó elfelejti a jelszavát',                               'kat' => 3, 'sor' => 1),
    
    ERT_BEOSZTAS_ELL            => array('nev' => 'Munkaidőbeosztás szerinti szálloda nincs hozzárendelve felhasználóhoz',      'kat' => 4, 'sor' => 1),
    
    ERT_GAR_BEJARAS             => array('nev' => 'Garanciális bejárás előtt',                                                  'kat' => 5, 'sor' => 1),
    
    ERT_IGAZOLASRA_KULDESKOR    => array('nev' => 'Igazolásra küldéskor',                                                       'kat' => 6, 'sor' => 1),
    ERT_UT_IGAZOLASRA_KULDESKOR => array('nev' => 'Utóigazolásra küldéskor',                                                    'kat' => 6, 'sor' => 2),
    ERT_IGAZOLONAK_VISSZAADAS   => array('nev' => 'Igazolónak visszaadáskor',                                                   'kat' => 6, 'sor' => 3),
    ERT_ASSZISZT_VISSZAADAS     => array('nev' => 'Asszisztensnek visszaadáskor',                                               'kat' => 6, 'sor' => 3),

    ERT_ASSZISZT_VISSZAADAS2    => array('nev' => 'Asszisztensnek visszaadáskor',                                               'kat' => 7, 'sor' => 1),
    
);