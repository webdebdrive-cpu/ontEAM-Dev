<?php
// Dinamikus lista tartalmának kigenerálása
/*
Használata:
<script>
    $(document).ready(function() {
        $('#ksz').autocomplete( {
            'php_fajl'      : '../autocomplete.php',
            'tabla'         : 'termek',
            'oszlop_keres'  : 'nev',
            'oszlop_listaz' : 'nev'
        });
    });
</script>
*/


//echo '1###volt|';


include('weblap.php');

if(isset($_GET['value']) && isset($_GET['tabla']) && isset($_GET['oszlop_keres'])){

    //Több oszlop is szerepelhet a $_GET['oszlop']-ban (minden oszlop értékét sortöréssel tesszük a találati listába)
    $oszlop_keres  = explode(',', str_replace(' ', '', $_GET['oszlop_keres']));
    $oszlop_listaz = explode(',', str_replace(' ', '', $_GET['oszlop_listaz']));


    //Ha legalább egy értéke van
    if( count( $oszlop_keres ) ) {

        $where_sql = '';
        $oszlopok  = '';

        //SQL előállítása
        for( $i = 0; $i < count( $oszlop_keres ); $i++ ) {

            $oszlop_keres[$i] = trim($oszlop_keres[$i]);

            $where_sql .= '`'.$oszlop_keres[$i].'` LIKE \'%'.$_GET['value'].'%\' OR ';
            $oszlopok  .= '`'.$oszlop_keres[$i].'`, ';
        }

        //Utolsó 4 karater levágása
        $where_sql =  substr($where_sql, 0 , strlen($where_sql) - 4);
        $oszlopok  =  substr($oszlopok, 0 ,  strlen($oszlopok)  - 2);

        if( $where_sql ) {
            $where_sql = ' WHERE '.$where_sql;

            if( $_GET['rkat'] ) {
                $where_sql .= " AND `rkat` = '".$_GET['rkat']."'";
            }
        }

        if( $_GET['value'] == '*' ) {
            if( $_GET['rkat'] ) {
                $where_sql = " WHERE `rkat` = '".$_GET['rkat']."'";
            } else {
                $where_sql = '';
            }
        }

        //DL lista elemeinek előállítása
        $return = '<ul>';

//echo 'SELECT `id`, `'.implode('`, `', $oszlop_listaz).'` FROM `'.get_db_tabla().$_GET['tabla'].'` WHERE '.$where_sql.' ORDER BY `'.$oszlop_keres[0].'`';
        $lekerdez = mysqli_query($db_kapcs, 'SELECT `recid`, `'.implode('`, `', $oszlop_listaz).'` FROM `'.get_db_tabla().$_GET['tabla'].'`'.$where_sql.' ORDER BY `'.$oszlop_keres[0].'`') or die(mysqli_error($db_kapcs));
        while( $sor = mysqli_fetch_array($lekerdez) ) {

            $return .= '<li>';
            //$return .= '<li data-href="'.$_GET['tabla'].'.php?id='.$sor['recid'].'&amp;modosit">';

            $size = count($oszlop_listaz) - 1;
            for( $i = 0; $i <= $size; $i++ ) {

                $return .= $sor[$oszlop_listaz[$i]];

                //Az utolsó után nem kell <br>
                if( $i != $size ) {
                    $return .= '<br>';
                }

            }

            $return .= '</li>';

    	}
        $return .= '</ul>';
        echo $return;

    }
}

$adatbazis_kapcsolat -> bezar();
?>
