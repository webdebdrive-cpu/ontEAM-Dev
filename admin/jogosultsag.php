<?php
$jogosultsagKatok = array(
    0 => array('nev' => 'Bejelentések',     'sor' => 0),
    1 => array('nev' => 'Munkalapok',       'sor' => 1),
    2 => array('nev' => 'Rendszer',         'sor' => 2),
    3 => array('nev' => 'Anyagigénylés',    'sor' => 3),
);

$jogosultsagok = array(
//Ez a _jog() ID_ja, tehát a => előtti rész
    0 => array('nev' => 'Bejelentések megtekintése',    'kat' => 0, 'sor' => 0, 'kikapcs' => '1,2'),
    1 => array('nev' => 'Bejelentések kezelése',        'kat' => 0, 'sor' => 1, 'bekapcs' => '0'),
    2 => array('nev' => 'Munkalapot készíthet',         'kat' => 0, 'sor' => 2, 'i' => 'diszpécserek', 'bekapcs' => '1,0'),

    3 => array('nev' => 'Munkalap megtekintése',                    'kat' => 1, 'sor' => 3, 'kikapcs' => '5'),
    4 => array('torolve' => 'TÖRÖLVE',  'nev' => 'Munkalap kezelése',                        'kat' => 1, 'sor' => 4),
    5 => array('nev' => 'Csak hozzárendelt munkalapok kezelése',    'kat' => 1, 'sor' => 5, 'i' => 'nem diszpécser', 'bekapcs' => '3'),

    6  => array('nev' => 'Felhasználók megtekintése',   'kat' => 2, 'sor' => 6, 'kikapcs' => '7'),
    7  => array('nev' => 'Felhasználók kezelése',       'kat' => 2, 'sor' => 7, 'bekapcs' => '6'),
    8  => array('nev' => 'Értéklisták megtekintése',    'kat' => 2, 'sor' => 8, 'kikapcs' => '9'),
    9  => array('nev' => 'Értéklisták kezelése',        'kat' => 2, 'sor' => 9, 'bekapcs' => '8'),
    10 => array('nev' => 'Szerepkörök megtekintése',    'kat' => 2, 'sor' => 10, 'kikapcs' => '11'),
    11 => array('nev' => 'Szerepkörök kezelése',        'kat' => 2, 'sor' => 11, 'bekapcs' => '10'),

    12 => array('nev' => 'Anyagigénylés megtekintése',  'kat' => 3, 'sor' => 1, 'kikapcs' => '13,15'),
    13 => array('nev' => 'Anyagigénylés kezelése',      'kat' => 3, 'sor' => 2, 'i' => 'vezető, új igénylés készítése', 'bekapcs' => '12'),
    14 => array('nev' => 'Igénylés jóváhagyása',        'kat' => 3, 'sor' => 3, 'i' => 'vezető, árak és mennyiségek jóváhagyása'),
    15 => array('nev' => 'Anyagrendelés készítése',     'kat' => 3, 'sor' => 4, 'i' => 'adminisztrátor hölgyek', 'bekapcs' => '12'),

);
