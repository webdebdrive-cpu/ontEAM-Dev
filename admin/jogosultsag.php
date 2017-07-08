<?php
$jogosultsagKatok = array(
    0 => array('nev' => 'Bejelentések',     'sor' => 0),
    1 => array('nev' => 'Munkalapok',       'sor' => 1),
    2 => array('nev' => 'Rendszer',         'sor' => 2),
    3 => array('nev' => 'Anyagok',          'sor' => 3),
    4 => array('nev' => 'Személyzet',       'sor' => 4),
);

$jogosultsagok = array(
//Ez a _jog() ID_ja, tehát a => előtti rész
    0  => array('nev' => 'Bejelentések megtekintése',    'kat' => 0, 'sor' => 0, 'kikapcs' => '1,2'),
    1  => array('nev' => 'Bejelentések kezelése',        'kat' => 0, 'sor' => 1, 'bekapcs' => '0'),
    2  => array('nev' => 'Munkalapot készíthet',         'kat' => 0, 'sor' => 2, 'i' => 'diszpécserek', 'bekapcs' => '1,0'),

    3  => array('nev' => 'Munkalap megtekintése',                           'kat' => 1, 'sor' => 3, 'kikapcs' => '5,21,22,23,24'),
    4  => array('torolve' => 'TÖRÖLVE',  'nev' => 'Munkalap kezelése',      'kat' => 1, 'sor' => 4),
    5  => array('nev' => 'Csak hozzárendelt munkalapok kezelése',           'kat' => 1, 'sor' => 5, 'bekapcs' => '3', 'kikapcs' => '21,22,23,24', 'i' => 'telepített vagy mobil'),
    21 => array('nev' => 'Munkalapok kezelése mint műszaki vezető',         'kat' => 1, 'sor' => 6, 'bekapcs' => '3', 'kikapcs' => '5,22,23,24'),
    22 => array('nev' => 'Munkalapok kezelése mint területi felelős',       'kat' => 1, 'sor' => 7, 'bekapcs' => '3', 'kikapcs' => '21,5,23,24'),
    23 => array('nev' => 'Munkalapok kezelése mint Diszp.központ vezető',   'kat' => 1, 'sor' => 8, 'bekapcs' => '3', 'kikapcs' => '21,22,5,24'),
    24 => array('nev' => 'Munkalapok kezelése mint adminisztrátor',         'kat' => 1, 'sor' => 9, 'bekapcs' => '3', 'kikapcs' => '21,22,23,5'),

    6  => array('nev' => 'Felhasználók megtekintése',   'kat' => 2, 'sor' => 6, 'kikapcs' => '7'),
    7  => array('nev' => 'Felhasználók kezelése',       'kat' => 2, 'sor' => 7, 'bekapcs' => '6'),
    8  => array('nev' => 'Értéklisták megtekintése',    'kat' => 2, 'sor' => 8, 'kikapcs' => '9'),
    9  => array('nev' => 'Értéklisták kezelése',        'kat' => 2, 'sor' => 9, 'bekapcs' => '8'),
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
    
    25 => array('nev' => 'Törzsadatok olvasása',        'kat' => 4, 'sor' => 1, 'kikapcs' => '26'),
    26 => array('nev' => 'Törzsadatok kezelése',        'kat' => 4, 'sor' => 2, 'bekapcs' => '25'),
    27 => array('nev' => 'Szabadságigények olvasása',   'kat' => 4, 'sor' => 3, 'kikapcs' => '28'),
    28 => array('nev' => 'Szabadságigények kezelése',   'kat' => 4, 'sor' => 4, 'bekapcs' => '27'),
    29 => array('nev' => 'Beosztások olvasása',         'kat' => 4, 'sor' => 5, 'kikapcs' => '30'),
    30 => array('nev' => 'Beosztások kezelése',         'kat' => 4, 'sor' => 6, 'bekapcs' => '29'),

);
