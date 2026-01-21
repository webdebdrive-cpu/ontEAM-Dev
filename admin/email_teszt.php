<?php
require_once '../weblap.php';

$cimzett_neve   = 'Tibi';
$cimzett_mail   = 'bk@webdeb.hu';
$targy          = 'Email tárgya';
$uzi            = 'Email tartalma..
<br>így lehet akár több sorban.
';

echo level_kuldes_proba(get_nev(), get_mail(), $cimzett_neve, $cimzett_mail, $targy, $uzi);

function level_kuldes_proba( $felado_nev, $felado_mail, $cimzett_nev, $cimzett_mail, $targy, $uzi, $fajlok = '' ) {

    if( $cimzett_mail ) {

        // Ha a feladó neve ontEAM, akkor a levél feladója esetén ezt toldjuk még meg egy "Danubius Hotels Zrt."-vel
        if( $felado_nev == get_nev() ){
            $felado_nev .= ' Danubius Hotels Zrt.';
        }
        
        require_once('../class/class.Phpmailer.php');
        $mail = new PHPMailer;

        $cimzett_mail_cid = $cimzett_mail;
        if( is_array($cimzett_mail) ) {
            $cimzett_mail_cid = $cimzett_mail[0];
        }

        $uzi = mail_img2cid($uzi, $mail, $cimzett_mail_cid);
        $mail -> CharSet  = get_web_charset();
        $mail -> setFrom($felado_mail, $felado_nev);
        $mail -> addReplyTo($felado_mail, $felado_nev);
        $mail -> Subject  = $targy;
        $mail -> AltBody  = strip_tags($uzi);
        $mail -> MsgHTML($uzi);

        if( is_array($fajlok) ) {
            foreach ( $fajlok as $fajl ) {
                if( $fajl['nev'] && $fajl['fajl'] ) {
                    $mail -> AddAttachment($fajl['fajl'], $fajl['nev']);
                } else {
                    $mail -> AddAttachment($fajl);
                }
            }

        } elseif( $fajlok ) {
            $mail -> AddAttachment($fajlok);
        }

        if( is_array($cimzett_mail) ) {
            $i = 0;
            foreach ( $cimzett_mail as $akt_mail ) {

                // A második címzett már rejtve kapja meg
                if( $i ) {
                    $mail -> AddCc($akt_mail, $cimzett_nev[$i]);
                } else {
                    $mail -> addAddress($akt_mail, $cimzett_nev[$i]);
                }
                $i++;
            }
        } elseif( $cimzett_mail ) {
            $mail -> AddAddress($cimzett_mail, $cimzett_nev);
        }

        if( !$mail->Send() ) {
            $mail -> ClearAddresses();
            $mail -> ClearAttachments();
            return 'Hiba: ' . $mail->ErrorInfo;
        } else {
            $mail -> ClearAddresses();
            $mail -> ClearAttachments();
            return 'Sikeres kiküldés';
        }
    } else {
        return 'Hiba: Nincs címzett!';
    }
}