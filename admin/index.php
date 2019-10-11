<?php
// Nyitólap
require_once('header.php');

$html = '';

// Éles és teszt-en még ne látszódjon, csak dev-en és saját környezetben
if( $_SERVER['HTTP_HOST']!='www.onteam.hu' && $_SERVER['HTTP_HOST']!='onteam.hu' && $_SERVER['HTTP_HOST']!='teszt.onteam.hu' ) {

    // Bejelentések box
    $html .= nl2('
        <div class="box">
            <h2>Bejelentések</h2>
            <div class="padd">
                <table class="csikos full">
            ');

            // Temporális tábla létrehozására minta
    //        mysqli_query($db_kapcs, 'CREATE TEMPORARY TABLE `'. get_db_tabla().'_bejelentes_tmp` ENGINE=MYISAM SELECT * FROM `'. get_db_tabla().'bejelentes` WHERE `telephely_id` IN ('.implode(',', get_akt_login_szallodai()).') ');
    //        mysqli_query($db_kapcs, 'DROP TEMPORARY TABLE `'. get_db_tabla().'_bejelentes_tmp`') or trigger_error($db_kapcs -> error);

            // Aktuális loginhoz szállódáit, részlegeit lekérjük.
            $szallodak = get_akt_login_szallodai();
            $reszlegek = get_akt_login_reszlegei();
            $felh = $adatbazis_kapcsolat->s2('reszleg_id', 'login', "`nev`='{$_SESSION[get_db_tabla().'user']}'");

            // Igazolásra váró bejelentések:
            $sql_kieg = '';
            if( _jog(JOG_BEJELENTES_SAJAT_RESZL) && !_jog(JOG_BEJELENTES_MEGTEKINT) ) {
                $sql_kieg .= "`usr_reszleg_id` IN (".implode(',', $reszlegek).") AND ";
            }
    //        // Társrészlegek bejelentéseinek igazolása joggal
    //        if( _jog(JOG_BEJELENTES_TARSRESZLEG_BEJ_IGAZOL) ) {
    //            $sql_kieg = " AND `usr_reszleg_id` IN (".implode(',', $reszlegek).")";
    //        
    //        // Saját szállodák bejelentéseinek megtekintése joggal
    //        } else if( _jog(JOG_BEJELENTES_MEGTEKINT) ) {
    //            $sql_kieg = '';
    //        
    //        // Saját részleg bejelentések megtekintése joggal
    //        } else if( _jog(JOG_BEJELENTES_SAJAT_RESZL) ) {
    //            $sql_kieg = " AND `usr_reszleg_id`='{$felh['reszleg_id']}'";
    //        }

            $statusz  = BEJELENTES_STAT_KESZ_IG;
            $db = $adatbazis_kapcsolat->s('recid', 'bejelentes', "`stat_id`='".$statusz."' AND `telephely_id` IN (".implode(',', $szallodak)."){$sql_kieg}", '', '', '', 'Num rows');
            // Ha van Igazolásra váró bejelentés
            if( $db ) {
                $link = 'bejelentes.php?mezo=stat_id&amp;op=equal&amp;felt='.$statusz.'&amp;modosit';
                $html .= nl2('
                            <tr>
                                <td>
                                    <a href="'.$link.'">Igazolásra vár:</a>
                                </td>
                                <td>
                                    <a href="'.$link.'">'.$db.' db.</a>
                                </td>
                            </tr>
                            ');
            }

            // Vizsgálat alatti bejelentések:
            // Bejelentések vizsgálat jog kell hozzá
            $db = 0;
            $statusz = BEJELENTES_STAT_VIZSG_ALATT;
            if( _jog(JOG_BEJELENTES_VIZSGAL) ) {
                $db = $adatbazis_kapcsolat->s('recid', 'bejelentes', "`stat_id`='".$statusz."' AND `telephely_id` IN (".implode(',', $szallodak)."){$sql_kieg}", '', '', '', 'Num rows');
            }
            // Ha van Vizsgálat alatti bejelentés
            if( $db ) {
                $link = 'bejelentes.php?mezo=stat_id&amp;op=equal&amp;felt='.$statusz.'&amp;modosit';
                $html .= nl2('
                            <tr>
                                <td>
                                    <a href="'.$link.'">Vizsgálat alatt:</a>
                                </td>
                                <td>
                                    <a href="'.$link.'">'.$db.' db.</a>
                                </td>
                            </tr>
                            ');
            }


    $html .= nl2('
                </table>
            </div>
        </div>
        <div class="box">
            <h2>Munkalapok</h2>
            <div class="padd">
                <table class="csikos full">
                    <tr>
                        <td>
                            <a href="#">Minta sor:</a>
                        </td>
                        <td>
                            <a href="#">123 db.</a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <a href="#">Minta sor2:</a>
                        </td>
                        <td>
                            <a href="#">123 db.</a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <a href="#">Minta sor3:</a>
                        </td>
                        <td>
                            <a href="#">123 db.</a>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        ');

    echo $html;
}

require_once('footer.php');
?>