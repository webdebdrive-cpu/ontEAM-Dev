<?php
// Dinamikus lista tartalmának kigenerálása
include('weblap.php');

if(isset($_GET['value']) && isset($_GET['tabla']) && isset($_GET['oszlop_keres'])){

    // Több oszlop is szerepelhet a $_GET['oszlop']-ban (minden oszlop értékét sortöréssel tesszük a találati listába)
    $oszlop_keres  = explode(',', str_replace(' ', '', $_GET['oszlop_keres']));
    $oszlop_listaz = explode(',', str_replace(' ', '', $_GET['oszlop_listaz']));
    // Ha úgy akarnánk lekérni objektumokat vagy eszközöket, hogy nincs kiválsztva telephely, akkor nem csinálunk semmit
    if( ($_GET['tabla'] == 'obj' || $_GET['tabla'] == 'eszkozkezeles') && !$_GET['telephely_id'] ){
        $weblap -> leallit();
        exit;
    }
    // Ha legalább egy értéke van
    if( count( $oszlop_keres ) ) {

        $where_sql = '';
        $oszlopok  = '';

        // SQL előállítása
        for( $i = 0; $i < count( $oszlop_keres ); $i++ ) {

            $oszlop_keres[$i] = trim($oszlop_keres[$i]);

            $where_sql .= '`'.$oszlop_keres[$i].'` LIKE \'%'.htmlspecialchars($_GET['value']).'%\' OR ';
            $oszlopok  .= '`'.$oszlop_keres[$i].'`, ';
        }

        // Utolsó 4 karater levágása
        $where_sql =  substr($where_sql, 0, strlen($where_sql) - 4);
        $oszlopok  =  substr($oszlopok,  0, strlen($oszlopok)  - 2);

        if( $where_sql ) {
            $where_sql = ' WHERE '.$where_sql;

            if( $_GET['rkat'] ) {
                $where_sql .= " AND `rkat` = '".$_GET['rkat']."'";
            }

            if( $_GET['csoport'] ) {
                $where_sql .= " AND `csoport` = '".$_GET['csoport']."'";
            }
        }

        if( $_GET['value'] == '*' ) {
            if( $_GET['rkat'] ) {
                $where_sql = " WHERE `rkat` = '".$_GET['rkat']."'";
            } elseif( $_GET['csoport'] ) {
                $where_sql = " WHERE `csoport` = '".$_GET['csoport']."'";
            } else {
                $where_sql = '';
            }
        }

        // Telephely id
        $obj_csak_szint = false;
        if($_GET['tabla']=='obj_csakszint'){
            $_GET['tabla'] = 'obj';
            $obj_csak_szint = true;
        }
        if( is_numeric($_GET['telephely_id']) && ($_GET['tabla'] == 'obj' || $_GET['tabla'] == 'eszkozkezeles') ) {
            if( $where_sql ) {
                $where_sql .= " AND `telephely_id` = '".$_GET['telephely_id']."'";
            } else {
                $where_sql .= " WHERE `telephely_id` = '".$_GET['telephely_id']."'";
            }
        }
        
        // Ha helységet vagy objektumokat kérünk le, akkor csak a felhasználóhoz tartozó szállodákat vesszük figyelembe
        if( $_GET['tabla'] == 'obj' ) {
            $akt_user_szallodai = get_akt_login_szallodai();
            $akt_user_szallodai = implode(', ', $akt_user_szallodai);
            if( $where_sql ) {
                $where_sql .= " AND `telephely_id` IN ({$akt_user_szallodai})";
            } else {
                $where_sql .= " WHERE `telephely_id` IN ({$akt_user_szallodai})";
            }
        }

        // Energia egységár esetén ha díjelem nevét kérjük le, akkor keressük meg az éppen éles tervet, és annak egységár soraiból készítsünk listát
        if( $_GET['tabla'] == 'energia_terv_ear' ) {
            // Egy éles terv van mindig, ennek díjelemeit listázzuk ki. Az éles státuszúak közül a legnagyobb évszámút.
            $eles_terv = $adatbazis_kapcsolat->s2('recid', 'energia_terv', "`stat_id`='".ENERGIATERV_STAT_ELES."'", 'ev desc', '1');
            if( $where_sql ) {
                $where_sql .= " AND `energia_terv_id` = '".$eles_terv['recid']."'";
            } else {
                $where_sql .= " WHERE `energia_terv_id` = '".$eles_terv['recid']."'";
            }
        }

        // Objektm id
        if( $_GET['tabla'] == 'eszkozkezeles' && $_GET['obj_id'] ) {
            if( $where_sql ) {
                $where_sql .= " AND `obj_id` = '".$_GET['obj_id']."'";
            } else {
                $where_sql .= " WHERE `obj_id` = '".$_GET['obj_id']."'";
            }
        }

        // Login táblában szűrhetünk személyzetre is
        if( $_GET['tabla'] == 'login' && $_GET['szemelyzet_id'] ) {
            if( $where_sql ) {
                $where_sql .= " AND `szemelyzet_id` = '".$_GET['szemelyzet_id']."'";
            } else {
                $where_sql .= " WHERE `szemelyzet_id` = '".$_GET['szemelyzet_id']."'";
            }
        }

        // Login táblában szűrhetünk beosztásra is: ha van benne vessző, akkor egy egész felsorolás
        if( $_GET['tabla'] == 'login' && $_GET['beosztas_id'] ) {
            if( strpos($_GET['beosztas_id'], ',') !== false ) {
                if( $where_sql ) {
                    $where_sql .= " AND `beosztas_id` IN (".$_GET['beosztas_id'].")";
                } else {
                    $where_sql .= " WHERE `beosztas_id` IN (".$_GET['beosztas_id'].")";
                }
            } else {
                if( $where_sql ) {
                    $where_sql .= " AND `beosztas_id` = '".$_GET['beosztas_id']."'";
                } else {
                    $where_sql .= " WHERE `beosztas_id` = '".$_GET['beosztas_id']."'";
                }
            }
        }
        // Illetve aktívakra is (ha viszont a szűrőben jelenik meg az autocomplete, akkor az inaktivakat is látni szeretnénk)
        if( $_GET['tabla'] == 'login' && $_GET['aktiv'] && !$_GET['szuros'] ) {
            if( $where_sql ) {
                $where_sql .= " AND `aktiv` = '1'";
            } else {
                $where_sql .= " WHERE `aktiv` = '1'";
            }
        }

        // Szállítók esetén csak az aktívakat soroljuk fel
        if( $_GET['tabla'] == 'szallito' ) {
            if( $where_sql ) {
                $where_sql .= " AND `aktiv` = '1'";
            } else {
                $where_sql .= " WHERE `aktiv` = '1'";
            }
        }
        
        // Objektumonnkál van, hogy csak a szinteket szeretnénk lekérdezni
        if( $obj_csak_szint ) {
            if( $where_sql ) {
                $where_sql .= " AND `szulo`=0";
            } else {
                $where_sql .= " WHERE `szulo`=0";
            }
        }
        
        // Props oszlopban is keresünk
        if( $_GET['props'] ){
            if( $where_sql ) {
                $where_sql .= " AND `props` LIKE '%{$_GET['props']}%'";
            } else {
                $where_sql .= " WHERE `props` LIKE '%{$_GET['props']}%'";
            }
        }
        
        // Dokumentum típus esetén ha még menüpontra is szűrünk
        // FONTOS: itt van még egy extra dolog: a szerződés tipust is kiszűrjük!
        if( $_GET['menupont_szuro'] ){
            if( $where_sql ) {
                $where_sql .= " AND `{$_GET['menupont_szuro']}`='1' AND `id`!='".EL_DOK_TIPUS_SZERZODES."'";
            } else {
                $where_sql .= " WHERE `{$_GET['menupont_szuro']}`='1' AND `id`!='".EL_DOK_TIPUS_SZERZODES."'";
            }
        }

        // DL lista elemeinek előállítása
        $return = '<ul>';

        $select_kieg = '';
        if( $_GET['tabla'] == 'erteklista' || $_GET['tabla'] == 'cikktorzs' ) {
            $select_kieg = ' `id`,';
        } else if( $_GET['tabla'] == 'obj' ) {
            $select_kieg = ' `telephely_id`,';
        } else if( $_GET['tabla'] == 'szallito' ) {
            $select_kieg = ' `id`,';
        } else if( $_GET['tabla'] == 'ktgnem' ) {
            $select_kieg = ' `id`,';
        }
        
        if( $_GET['id_kiir'] == 'usermail' ) {
            $select_kieg .= ' `usermail`,';
        }

        if( $_GET['tabla'] == 'cikktorzs' ) {
            $select_kieg .= ' `cikkmee`,';
        }
        
        if( $_GET['aktiv_szinez'] ) {
            $select_kieg .= ' `aktiv`,';
        }

        // Ha törzsadatlistát kérünk le, akkor a találati listát egész bonyolultan számítjuk ki:
        if( $_GET['tabla'] == 'torzsadat' && $_GET['recid'] ) {

            // melyik szállodánál vagyunk
            $szalloda = $adatbazis_kapcsolat->s2('telephely_id', 'beosztas', "`recid`='{$_GET['recid']}'");

            // Ehhez milyen loginok tartoznak:
            $felhok = $adatbazis_kapcsolat->s('nev', 'telephely_kapcs', "`telephely_id`='{$szalloda['telephely_id']}'");
            $felhok = adatbazis2tomb($felhok, 'nev');

            $where_sql = " WHERE `felh_loginnev` IN ('".implode('\', \'', $felhok)."')";

        }
        

        // Energia terv egységárnál a nem fontos a recid, de nagyon fontos, hogy az értékek DISTINCT-elve legyenek
        if( $_GET['tabla'] == 'energia_terv_ear' ) {
            $lekerdez = mysqli_query($db_kapcs, 'SELECT DISTINCT `'.implode('`, `', $oszlop_listaz).'` FROM `'.get_db_tabla().$_GET['tabla'].'`'.$where_sql.' ORDER BY `'.$oszlop_keres[0].'`') or die(mysqli_error($db_kapcs));
// echo 'SELECT DISTINCT `'.implode('`, `', $oszlop_listaz).'` FROM `'.get_db_tabla().$_GET['tabla'].'`'.$where_sql.' ORDER BY `'.$oszlop_keres[0].'`' . '<br>';
        // Cikktörzs esetén a rendezés cikknév szerint legyen
        } elseif( $_GET['tabla'] == 'cikktorzs' ) {
            $lekerdez = mysqli_query($db_kapcs, 'SELECT DISTINCT `recid`, `'.implode('`, `', $oszlop_listaz).'`, `cikkmee` FROM `'.get_db_tabla().$_GET['tabla'].'`'.$where_sql.' ORDER BY `nev`') or die(mysqli_error($db_kapcs));
        } else {
// echo 'SELECT `recid`,'.$select_kieg.' `'.implode('`, `', $oszlop_listaz).'` FROM `'.get_db_tabla().$_GET['tabla'].'`'.$where_sql.' ORDER BY `'.$oszlop_keres[0].'`'; exit;
            $lekerdez = mysqli_query($db_kapcs, 'SELECT `recid`,'.$select_kieg.' `'.implode('`, `', $oszlop_listaz).'` FROM `'.get_db_tabla().$_GET['tabla'].'`'.$where_sql.' ORDER BY `'.$oszlop_keres[0].'`') or die(mysqli_error($db_kapcs));
        }
        while( $sor = mysqli_fetch_array($lekerdez) ) {

            $cikkmee_attr = '';
            if( $_GET['tabla'] == 'cikktorzs' ) {
                $cikkmee_attr = ' data-mee="'.$sor['cikkmee'].'" data-cikkid="'.$sor['id'].'"';
            }

            // Aktív/inatkív rekordokat színezzük
            $class = '';
            if( $_GET['aktiv_szinez'] ) {
                if( $sor['aktiv'] ) {
                    $class = ' class="zold"';
                } else {
                    $class = ' class="piros"';
                }
            }
            
            $return .= '<li data-recid="'.$sor[$_GET['id_kiir']].'"'.$cikkmee_attr.$class.'>';

            if( $_GET['tabla'] == 'obj' ) {
                $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$sor['telephely_id'], '', '1');

                if( $telephely ) {
                    $return .= $telephely[0]['nev'].' / ';
                }
            }

            $size = count($oszlop_listaz) - 1;
            for( $i = 0; $i <= $size; $i++ ) {

                $return .= $sor[$oszlop_listaz[$i]];

                // Az utolsó után nem kell <br>
                if( $i != $size ) {
                    $return .= ' - ';
                }

            }

            $return .= '</li>';

    	}
        $return .= '</ul>';
        echo $return;

    }
}

$weblap -> leallit();
?>