<?php
/*                                                                                                                      *\

    0.  A Tudásbázisban ("Admin jelszavazása .htacceess-el"- címmel) van egy leírás, hogyan lehet .htaccess-el lejelszavazni egy admin mappát.
         Ettől ez a PHP-s megoldás egy szebb verzió, de tudd, hogy ott van egy másik megoldás erre.

    1.  Ha a header.php -ban rögtön a require_once('../weblap.php'); után meghívjuk ezt a fájlt require_once('beleptetes.php');
        akkor az admin jelszóval védve lesz.

    2.  Ugyanitt a header-t az alábbi kóddal kell kiegészteni:
$jogok = $adatbazis_kapcsolat -> select('oldalak', 'jog', 'id='.$_SESSION[get_db_tabla().'jog']);
$jogok = $jogok[0];

//Nem tudjuk, hogy most  \n\r -lesz e, ezért csak \n-re vágok és trimmelek
$jogok['oldalak'] = nl2br2($jogok['oldalak']);
$jogosultsagok = explode('<br>', $jogok['oldalak']);
$i = 0;
foreach($jogosultsagok as $x){
    $jogosultsagok[$i] = trim($jogosultsagok[$i]);
    $i++;
}


    3.  Majd egy-egy menüpontnál vizsgálni kell, hogy az adott menüpontot kíratjuk-e:

$menu_nev        = 'Valami';
$menu_nev_kis    = 'valami';
$menu_nev_tobbes = 'Valamik';
$megnevezes      = 'valami';
if(in_array($megn.'.php', $jogosultsagok)){
    ...
}

    4.  Ellenőrizd a sablon fájlokat is az sablon/admin mappában. Ezeknek kell léteznie: beleptetes.html, nincsjog.html

    5.  SQL adatbázis létrehozása:
CREATE TABLE `TABLA_login` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `nev` varchar(20) NOT NULL,
  `jelszo` varchar(50) NOT NULL,
  `jog` varchar(10) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `TABLA_jog` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `nev` varchar(20) NOT NULL,
  `oldalak` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

    6.  A jogosultságokat a `jog` táblával szabályozható, a felhasználóneveket jelszóval, és jogosultásggal pedig a `user` táblával.
        DE! Erre már van egy felület is, ahol ez könnyedén szerkeszthető: beleptetes.php?jogok

        Tudnivalók erről:

        USEREK LÉTREHOZÁSA
        $sha1 változóval beálítható, hogy hasznájon-e az adatbázis titkosítást vagy sem.
        Ha $sha1 igaz, akkor `lohin` táblát úgy kell feltölteni, hogy a jelszavak $sha1-el elkódolva legyenek.

        JOGOSULTSÁGOK
        Ha a $jogolsultsagos változót igazra állítod, akkor az egyes menüpontok hozzáférése korlátozható. A `jog` tábla
        `oldalak` oszlopában újsorral egymás alá sorold fel, hogy az adott jogosultság mely PHP fájlokhoz fér hozzá.
        Javasolt ezt ABC szerinti sorrendben megtenni. Pl.:
        index.php
        termerk.php

        Az adott jogosultságnak adható egy megnevezés is (pl. adminisztrátor, titkárnői jog). A jog ID-ját pedig a `login`
        táblában kell hozzárendelni az adott userhez.

\*                                                                                                                       */

//Beállítások
$sha1           = true;
$jogolsultsagos = false;

require_once('../weblap.php');

//nem láthat bárki bármilyen tartalmat, viszont az admin bármit
if( $_SESSION[get_db_tabla().'user'] && $_SESSION[get_db_tabla().'user'] != 'admin' ){

    $user_joga = $adatbazis_kapcsolat -> select('jog', 'login', 'nev='.$_SESSION[get_db_tabla().'user']);
    $user_joga = $user_joga[0];

    $jogok = $adatbazis_kapcsolat -> select('oldalak', 'jog', 'id='.$user_joga['jog']);
    $jogok = $jogok[0];

    //Nem tudjuk, hogy most  \n\r -lesz e, ezért csak \n-re vágok és trimmelek
    $jogok['oldalak'] = nl2br($jogok['oldalak']);
    $jogosultsagok = explode('<br />', $jogok['oldalak']);
    $i = 0;
    foreach($jogosultsagok as $x){
        $jogosultsagok[$i] = trim($jogosultsagok[$i]);
        $i++;
    }

    if($jogolsultsagos && !in_array( basename(self()), $jogosultsagok ) ){
        $adatbazis_kapcsolat -> bezar();
        header('Location:index.php?nincsjog');
        exit();
    }

}

/* Jelszó csere */
if(isset($_POST['jelszo_csere'])) {
    if( !empty($_POST['jlsz']) && !empty($_POST['jlsz2']) ) {
        if( $_POST['jlsz'] == $_POST['jlsz2'] ) {
            $felhasznalo = $adatbazis_kapcsolat->s('recid nev teljes_nev jelszo helyesseg flags jog van_jelszo', 'login', "`recid` = '".$_SESSION['nincs_jelsz_felhid']."'", '', '1');

            if( $felhasznalo ) {
                $felhasznalo = $felhasznalo[0];

                /* Helyesség */
                $helyesseg = sha1($felhasznalo['nev'].$_POST['jlsz']);

                /* Jelszó */
                $jlsz = sha1($_POST['jlsz']);

                $adatbazis_kapcsolat -> u('login', array('jelszo', 'helyesseg', 'van_jelszo'), array($jlsz, $helyesseg, 1), "`recid` = '".$felhasznalo['recid']."'");

                $_SESSION[get_db_tabla().'user']        = $felhasznalo['nev'];
                $_SESSION[get_db_tabla().'teljes_nev']  = $felhasznalo['teljes_nev'];
                $_SESSION[get_db_tabla().'user_id']     = $felhasznalo['recid'];
                $_SESSION[get_db_tabla().'rights']      = str_split($felhasznalo['flags']);

                /* Nincs jelszó változók törlése */
                $_SESSION['nincs_jelsz_felhid'] = false;
                $_SESSION['nincs_jelsz'] = false;

                if( !$_SESSION['kert_url'] ) {
                    $_SESSION['kert_url'] = get_domain().'/'.get_admin();
                }

                $adatbazis_kapcsolat -> bezar();
                header('Location: http://'.$_SESSION['kert_url']);
                exit();
            }
        } else {
            header('Location: index.php?jelszonemazonos');
            exit();
        }
    } else {
        header('Location: index.php?jelszoures');
        exit();
    }
}

/* Jelszó csere mégsem */
if(isset($_POST['megsem'])) {
    $_SESSION['nincs_jelsz_felhid'] = false;
    $_SESSION['nincs_jelsz'] = false;

    header('Location: index.php');
    exit();
}

/* Belétetés */
if(isset($_POST['login'])) {

    if( $_POST['nev'] ) {
        $helyesseg = sha1($_POST['nev'].$_POST['jelszo']);

        if($sha1){
            $_POST['jelszo'] = sha1($_POST['jelszo']);
        }

        $felhasznalo = $adatbazis_kapcsolat->s('recid nev teljes_nev jelszo helyesseg flags jog van_jelszo', 'login', "`nev` = '".$_POST['nev']."' AND `aktiv` = '1'", '', '1');

        if( $felhasznalo ) {
            $felhasznalo = $felhasznalo[0];

            if( $felhasznalo['van_jelszo'] ) {
                if( $felhasznalo['jelszo'] == $_POST['jelszo'] && $felhasznalo['helyesseg'] == $helyesseg ) {
                    $_SESSION[get_db_tabla().'user']        = $felhasznalo['nev'];
                    $_SESSION[get_db_tabla().'teljes_nev']  = $felhasznalo['teljes_nev'];
                    $_SESSION[get_db_tabla().'user_id']     = $felhasznalo['recid'];
                    $_SESSION[get_db_tabla().'rights']      = str_split($felhasznalo['flags']);

                    if(!$_SESSION['kert_url']){
                        $_SESSION['kert_url'] = get_domain().'/'.get_admin();
                    }

                    if( $_POST['ajax'] ) {
                        echo json_encode(array(
                            'statusz' => 'siker'
                        ));
                    } else {
                        $adatbazis_kapcsolat -> bezar();
                        header('Location: http://'.$_SESSION['kert_url']);
                        exit();
                    }
                } else {
                    if( $_POST['ajax'] ) {
                        echo json_encode(array(
                            'statusz' => 'hiba'
                        ));
                    } else {
                        $adatbazis_kapcsolat -> bezar();
                        header('Location: index.php?hiba');
                        exit();
                    }
                }
            } else {
                $_SESSION['nincs_jelsz_felhid'] = $felhasznalo['recid'];
                $_SESSION['nincs_jelsz']        = true;

                if( $_POST['ajax'] ) {
                    echo json_encode(array(
                        'statusz' => 'hiba',
                        'link'    => 'beleptetes.php?kilep'
                    ));
                } else {
                    header('Location: index.php');
                }
            }
        } else {
            $adatbazis_kapcsolat -> bezar();
            header('Location: index.php?hiba');
            exit();
        }
    } else {
        $adatbazis_kapcsolat -> bezar();
        header('Location: index.php?hiba');
        exit();
    }
}

//Kiléptetés
if(isset($_GET['kilep'])){
    unset($_SESSION[get_db_tabla().'user']);
    unset($_SESSION[get_db_tabla().'user_id']);
    unset($_SESSION['user_adatok']);
    unset($_SESSION[get_db_tabla().'rights']);

    $adatbazis_kapcsolat -> bezar();
    header('Location:index.php');
    exit();
}



if(!$_SESSION[get_db_tabla().'user']){

    /*$_SESSION['kert_url'] = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

    $felirat = '';
    if(isset($_GET['hiba'])){
        $felirat = '<h1 style="color: #fff">Helytelen felhasználó név vagy jelszó.</h1>';
    }else{
        $felirat = '<h1>Kérjük azonosítsd magad</h1>';
    }

    $mit   = array('{FELIRAT}', '{SELF}'        );
    $mivel = array( $felirat,   'beleptetes.php');
    echo str_replace($mit, $mivel, fajlbol_olvas('../sablon/admin/beleptetes.html'));

    $adatbazis_kapcsolat -> bezar();
    exit();*/

}elseif(isset($_GET['nincsjog'])){

    echo fajlbol_olvas('../sablon/admin/nincsjog.html');

    $adatbazis_kapcsolat -> bezar();
    exit();
}

//Jogosultságkezelés
if (isset($_GET['jogok'])) {
    require_once 'header.php';

    //Jogosultságok
    $jogok = $adatbazis_kapcsolat->s('*', 'jog', '', 'nev');
    array_unshift($jogok, array('id' => '', 'nev' => '-Válasszon-'));

    $html = nl2('
            <tr>
                <td class="padd">
                        <table style="margin-bottom: 2rem">
                        <tr>
                            <td class="navi_bal">Felhasználó kezelés</td>
                        </tr>
                        </table>
                        <form method="post" action="'.self().'">
                            <table>
                            <tr>
                                <th></th>
                                <th>Teljes név</th>
                                <th>Felhaználói név</th>
                                <th>Új jelszó</th>
                                <th>Jogosultsági szint</th>
                                <th>Mentés</th>
                            </tr>
                            <tr>');

                                $html .= '<td class="b">Új:</td>';
                                $html .= '<td><input type="text" name="rendes_nev" placeholder="Teljes név"></td>';
                                $html .= '<td><input type="text" name="nev" placeholder="Felhasználói név"></td>';
                                $html .= '<td><input type="text" name="jelszo" placeholder="Jelszó"></td>';
                                $html .= '<td><select name="jog">';
                                    $html .= tomb2option($jogok, 'id', 'nev');
                                $html .= '</select></td>';
                                $html .= '<td><input type="submit" name="uj_login" class="gomb" value="Hozzáadás"></td>';

                            $html .= nl2('</tr>
                            <tr>
                                <td colspan="100%" style="height: 30px;"></td>
                            </tr>
                            ');

                    $loginok = $adatbazis_kapcsolat->s('*', 'login', '', 'rendes_nev');
                    foreach($loginok as $login){

                        $html .= '<tr id="login_'.$login['recid'].'">';
                        $html .= '<td><img class="link" src="../elemek/admin/x.gif" alt="törlés" onclick="login_torol(\''.$login['recid'].'\')"></td>';
                        $html .= '<td><input type="text" placeholder="Teljes név" name="rendes_nev_'.$login['recid'].'" id="rendes_nev_'.$login['recid'].'" value="'.$login['rendes_nev'].'"></td>';
                        $html .= '<td><input type="text" placeholder="Felhasználói név" name="nev_'.$login['recid'].'" id="nev_'.$login['recid'].'" value="'.$login['nev'].'"></td>';
                        $html .= '<td><input type="text" placeholder="Új jelszó" name="jelszo_'.$login['recid'].'" id="jelszo_'.$login['recid'].'" value=""></td>';
                        $html .= '<td><select name="jog_'.$login['recid'].'" id="jog_'.$login['recid'].'">';
                            $html .= tomb2option($jogok, 'id', 'nev', $login['jog']);
                        $html .= '</select></td>';
                        $html .= '<td><input type="button" class="gomb" value="Mentés" onclick="login_mentes(\''.$login['recid'].'\')"></td>';
                        $html .= nl2('</tr>
                                  <tr id="login_'.$login['recid'].'_szep">
                                    <td colspan="100%" style="height: 5px;"></td>
                                  </tr>');

                    }

                $html .= nl2('</table>
                         </form>');


                $html .= nl2('
                        <table style="margin-bottom: 2rem">
                        <tr>
                            <td class="navi_bal">Jogosultság kezelés</td>
                        </tr>
                        </table>
                        <form method="post" action="'.self().'">
                            <table>
                            <tr>
                                <th>Megnevezés<th>
                                <th>PHP oldalak<th>
                                <th><th>
                            </tr>
                            <tr>');

                                $html .= '<td class="b">Új:</td>';
                                $html .= '<td><input type="text" name="nev" placeholder="Jogosultság neve"></td>';
                                $html .= '<td><textarea name="oldalak" rows="1" cols="1" class="oriasi" placeholder="PHP fájlok, amiket elérhet."></textarea></td>';
                                $html .= '<td><input type="submit" name="uj_jog" class="gomb" value="Hozzáadás"></td>';

                            $html .= nl2('</tr>
                            <tr>
                                <td colspan="100%" style="height: 30px;"></td>
                            </tr>
                            ');

                    $i = 0;
                    foreach($jogok as $jog){
                        if($i){
                            $html .= nl2('<tr id="jog_'.$jog['id'].'">
                                        <td><img class="link" src="../elemek/admin/x.gif" alt="törlés" onclick="jog_torol(\''.$jog['id'].'\')"></td>
                                        <td><input type="text" name="jog_nev_'.$jog['id'].'" id="jog_nev_'.$jog['id'].'" value="'.$jog['nev'].'"></td>
                                        <td><textarea rows="1" cols="1" name="jog_oldalak_'.$jog['id'].'" id="jog_oldalak_'.$jog['id'].'">')
                                        //Meg kell szakítani, hogy az nl2 ezt a részt ne egysorosítsa
                                        .$jog['oldalak'].
                                        nl2('</textarea></td>
                                        <td><input type="button" class="gomb" value="Mentés" onclick="jog_mentes(\''.$jog['id'].'\')"></td>
                                      </tr>');
                         }
                        $i++;
                    }
                    $html .= nl2('</table>
                            </form>');

                $html .= nl2('</td>
            </tr>
                ');


    echo $html;

    require_once 'footer.php';
}

//Új login
if (isset($_POST['uj_login'])) {

    $_POST['jelszo'] = md5($_POST['jelszo']);

    $adatbazis_kapcsolat->i('login', array('rendes_nev',        'nev',          'jelszo',          'jog'            ),
                                     array($_POST['rendes_nev'], $_POST['nev'], $_POST['jelszo'],  $_POST['jog']    ));

    hd(self().'?jogok');
}

//Login adatok mentése ajaxal
if (isset($_POST['login_mentes'])) {

    $adatbazis_kapcsolat->u('login', array('rendes_nev',         'nev',         'jog'),
                                     array($_POST['rendes_nev'], $_POST['nev'], $_POST['jog']),
                                    'recid='.$_POST['id']);

    //Ha jelszó is volt megadva
    if ($_POST['jelszo']) {
        $adatbazis_kapcsolat->u('login', array('jelszo'), array( sha1($_POST['jelszo']) ), 'recid='.$_POST['id']);
    }

    echo $_POST['rendes_nev'].' adatai sikeresen mentve!';

    $weblap -> leallit;
    exit;
}

//Login törlése
if (isset($_POST['login_torol'])) {

    $adatbazis_kapcsolat->d('login', 'recid='.$_POST['id']);

    $weblap -> leallit;
    exit;
}




//Új jog
if (isset($_POST['uj_jog'])) {

    $adatbazis_kapcsolat->i('jog', array('nev', 'oldalak'), array($_POST['nev'], $_POST['oldalak']));

    hd(self().'?jogok');

}

//Jogosultásgok mentése ajaxal
if (isset($_POST['jog_mentes'])) {

    $adatbazis_kapcsolat->u('jog', array('nev', 'oldalak'), array($_POST['jog_nev'], $_POST['jog_oldalak']), 'id='.$_POST['id']);

    echo $_POST['jog_nev'].' adatai sikeresen mentve!';

    $weblap -> leallit;
    exit;

}

//Jogosultásgok törlése
if (isset($_POST['jog_torol'])) {

    $adatbazis_kapcsolat->d('jog', 'id='.$_POST['id']);

    $weblap -> leallit;
    exit;

}
?>
