<?php
/*        SZÁMLA IMPORT                                                                                                                    *\

  A POST-ként kapott adatokat el kell tárolni ideiglenesen, hogy ha nem lett volna belépve a felhasználó, akkor be tudjon még lépni
  majd ekkor továbbítjuk az új számla felületére, ahol a mezők már előre ki vannak töltve.

\*                                                                                                                                         */
require_once '../weblap.php';
// Ha van postolt adatunk, akkor ellenőrizzük a beléptetést, és ha ez megvan, akkor tovább léptetjük az új számla feltöltéshez
if( $_POST ) {

    $_SESSION['kert_url'] = 'projektszamla.php?uj';
    if( $_SERVER['HTTP_HOST'] == 'demo.webdeb.hu' ) {
        $_SESSION['kert_url'] = get_kerturl_elotag().$_SESSION['kert_url'];
    }
    $_SESSION['projekt_szamla_import'] = '1';
    foreach( $_POST as $kulcs => $post ) {
        $_SESSION[$kulcs] = $post;
    }

    require_once 'beleptetes.php';
    header('Location: '.$_SESSION['kert_url']);
}
?>