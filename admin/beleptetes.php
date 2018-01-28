<?php
// Beállítások
$sha1           = true;
$jogolsultsagos = false;

require_once('../weblap.php');

// Nem láthat bárki bármilyen tartalmat, viszont az admin bármit
if( $_SESSION[get_db_tabla().'user'] && $_SESSION[get_db_tabla().'user'] != 'admin' ){

    $user_joga = $adatbazis_kapcsolat -> s2('jog', 'login', 'nev='.$_SESSION[get_db_tabla().'user']);

    $jogok = $adatbazis_kapcsolat -> s2('oldalak', 'jog', 'id='.$user_joga['jog']);

    // Nem tudjuk, hogy most  \n\r -lesz e, ezért csak \n-re vágok és trimmelek
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

// Jelszó csere
if(isset($_POST['jelszo_csere'])) {
    if( !empty($_POST['jlsz']) && !empty($_POST['jlsz2']) ) {
        if( $_POST['jlsz'] == $_POST['jlsz2'] ) {

            $felhasznalo = $adatbazis_kapcsolat->s2('recid nev teljes_nev jelszo helyesseg flags mail_flags jog van_jelszo szemelyzet_id', 'login', "`recid` = '".$_SESSION['nincs_jelsz_felhid']."'", '', '1');
            if( $felhasznalo ) {

                // Helyesség és jelszó
                $helyesseg = sha1($felhasznalo['nev'].$_POST['jlsz']);
                $jlsz = sha1($_POST['jlsz']);

                $adatbazis_kapcsolat -> u('login',  array('jelszo', 'helyesseg',    'van_jelszo'),
                                                    array($jlsz,    $helyesseg,     1           ),
                                            "`recid` = '".$felhasznalo['recid']."'");
                $_SESSION[get_db_tabla().'user']            = $felhasznalo['nev'];
                $_SESSION[get_db_tabla().'teljes_nev']      = $felhasznalo['teljes_nev'];
                $_SESSION[get_db_tabla().'login_recid']     = $felhasznalo['recid'];
                $_SESSION[get_db_tabla().'rights']          = str_split($felhasznalo['flags']);
                $_SESSION[get_db_tabla().'mail_rights']     = str_split($felhasznalo['mail_flags']);
                $_SESSION[get_db_tabla().'szemelyzet_id']   = $felhasznalo['szemelyzet_id'];

                // Nincs jelszó változók törlése
                $_SESSION['nincs_jelsz_felhid'] = false;
                $_SESSION['nincs_jelsz']        = false;

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

// Jelszó csere mégsem
if(isset($_POST['megsem'])) {
    $_SESSION['nincs_jelsz_felhid'] = false;
    $_SESSION['nincs_jelsz']        = false;

    header('Location: index.php');
    exit();
}

// Belétetés
if(isset($_POST['login'])) {

    if( $_POST['nev'] ) {
        $helyesseg = sha1($_POST['nev'].$_POST['jelszo']);

        if($sha1){
            $_POST['jelszo'] = sha1($_POST['jelszo']);
        }

        $felhasznalo = $adatbazis_kapcsolat->s2('recid nev szallito_id teljes_nev jelszo helyesseg flags mail_flags jog van_jelszo szemelyzet_id', 'login', "`nev` = '".$_POST['nev']."' AND `aktiv` = '1'", '', '1');
        
        if( $felhasznalo ) {

            if( $felhasznalo['van_jelszo'] ) {

                if( $felhasznalo['jelszo'] == $_POST['jelszo'] && $felhasznalo['helyesseg'] == $helyesseg ) {

                    $_SESSION[get_db_tabla().'user']            = $felhasznalo['nev'];
                    $_SESSION[get_db_tabla().'teljes_nev']      = $felhasznalo['teljes_nev'];
                    $_SESSION[get_db_tabla().'login_recid']     = $felhasznalo['recid'];
                    $_SESSION[get_db_tabla().'szallito_id']     = $felhasznalo['szallito_id'];
                    $_SESSION[get_db_tabla().'rights']          = str_split($felhasznalo['flags']);
                    $_SESSION[get_db_tabla().'mail_rights']     = str_split($felhasznalo['mail_flags']);
                    $_SESSION[get_db_tabla().'szemelyzet_id']   = $felhasznalo['szemelyzet_id'];

//                    if(!$_SESSION['kert_url']){
//                        $_SESSION['kert_url'] = get_domain().'/'.get_admin();
//                    }

                    $adatbazis_kapcsolat -> u('login', array('login_dt'), array(date('Y-m-d H:i:s')), '`recid` = \''.$felhasznalo['recid'].'\'');

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

// Kiléptetés
if(isset($_GET['kilep'])){
    $adatbazis_kapcsolat -> u('login', array('logout_dt'), array(date('Y-m-d H:i:s')), '`recid` = \''.$_SESSION[get_db_tabla().'login_recid'].'\'');

    unset($_SESSION[get_db_tabla().'user']);
    unset($_SESSION[get_db_tabla().'login_recid']);
    unset($_SESSION[get_db_tabla().'szallito_id']);
    unset($_SESSION['user_adatok']);
    unset($_SESSION[get_db_tabla().'rights']);

    $adatbazis_kapcsolat -> bezar();
    header('Location:index.php');
    exit();
}



if(isset($_GET['nincsjog'])){

    echo fajlbol_olvas('../sablon/admin/nincsjog.html');

    $adatbazis_kapcsolat -> bezar();
    exit();
}

// Jogosultságkezelés
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

// Új login
if (isset($_POST['uj_login'])) {

    $_POST['jelszo'] = md5($_POST['jelszo']);

    $adatbazis_kapcsolat->i('login', array('rendes_nev',        'nev',          'jelszo',          'jog'            ),
                                     array($_POST['rendes_nev'], $_POST['nev'], $_POST['jelszo'],  $_POST['jog']    ));

    hd(self().'?jogok');
}

// Login adatok mentése ajaxal
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

// Login törlése
if (isset($_POST['login_torol'])) {

    $adatbazis_kapcsolat->d('login', 'recid='.$_POST['id']);

    $weblap -> leallit;
    exit;
}




// Új jog
if (isset($_POST['uj_jog'])) {

    $adatbazis_kapcsolat->i('jog', array('nev', 'oldalak'), array($_POST['nev'], $_POST['oldalak']));

    hd(self().'?jogok');

}

// Jogosultásgok mentése ajaxal
if (isset($_POST['jog_mentes'])) {

    $adatbazis_kapcsolat->u('jog', array('nev', 'oldalak'), array($_POST['jog_nev'], $_POST['jog_oldalak']), 'id='.$_POST['id']);

    echo $_POST['jog_nev'].' adatai sikeresen mentve!';

    $weblap -> leallit;
    exit;

}

// Jogosultásgok törlése
if (isset($_POST['jog_torol'])) {

    $adatbazis_kapcsolat->d('jog', 'id='.$_POST['id']);

    $weblap -> leallit;
    exit;

}
?>
