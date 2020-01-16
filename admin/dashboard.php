<?php
$html = '';

// Éles és teszt-en még ne látszódjon, csak dev-en és saját környezetben
if( $_SERVER['HTTP_HOST']!='www.onteam.hu' && $_SERVER['HTTP_HOST']!='onteam.hu' && $_SERVER['HTTP_HOST']!='teszt.onteam.hu' ) {
    
    // Aktuális loginhoz szállódáit, részlegeit lekérjük.
    $szallodak = get_akt_login_szallodai();
    $reszlegek = get_akt_login_reszlegei();
    $felh = $adatbazis_kapcsolat->s2('reszleg_id', 'login', "`nev`='{$_SESSION[get_db_tabla().'user']}'");


    // BEJELENTÉSEK
    if( bejelentes_lathat() ) {
        $sorok = '';    

        // Igazolásra váró bejelentések:
        $sql_kieg = '';
        if( _jog(JOG_BEJELENTES_SAJAT_RESZL) && !_jog(JOG_BEJELENTES_MEGTEKINT) ) {
            $sql_kieg .= " AND `usr_reszleg_id` IN (".implode(',', $reszlegek).")";
        }
        
        // Státuszok bejárása, sorok képzése belőle
        $statuszok = array(
                        array('nev' => 'Nyitott',                   'stat_id' => BEJELENTES_STAT_NYITOTT),
                        array('nev' => 'Folyamatban',               'stat_id' => BEJELENTES_STAT_FOLYAMATBAN),
                        array('nev' => 'Elutasítva',                'stat_id' => BEJELENTES_STAT_ELUTASITVA),
                        array('nev' => 'Várakozó',                  'stat_id' => BEJELENTES_STAT_VARAKOZO),
                        array('nev' => 'Igazolásra vár',            'stat_id' => BEJELENTES_STAT_KESZ_IG),
                        array('nev' => 'Vizsgálat alatt',           'stat_id' => BEJELENTES_STAT_VIZSG_ALATT),
                        array('nev' => 'Lezárt',                    'stat_id' => BEJELENTES_STAT_LEZART),
                    );

        $tabla = 'bejelentes';
        foreach( $statuszok as $statusz ) {
                    
            $db = $adatbazis_kapcsolat->s('recid', 'bejelentes', "`stat_id`='{$statusz['stat_id']}' AND `telephely_id` IN (".implode(',', $szallodak)."){$sql_kieg}", '', '', '', 'Num rows');
            if( $db ) {
                
                // Ha túl sok találat van, akkor rá kérdezünk, hogy biztosan szeretné-e listázni
                $js   = '';
                $link = $tabla.'.php?mezo=stat_id&amp;op=equal&amp;felt='.$statusz['stat_id'].'&amp;modosit'.$link_kieg;
                if( $db > SZURES_DB_LIMIT ) {
                    $js = '
                        <script>
                        function kerdes_'.$tabla.'_'.$statusz['stat_id'].$fgv_kieg.'() {
                            alert2(\'Ezzel a szűrési feltétellel '.number_format($db, 0, '', '.').' sor adatot listázna ki.<br>Biztosan folytatja?\', \'<input type="button" class="gomb zold" value="Igen" onclick="location.href=\\\''.$link.'\\\'"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">\', \'Kérdés\');
                        }
                        </script>
                        ';
                    $link = 'javascript:kerdes_'.$tabla.'_'.$statusz['stat_id'].$fgv_kieg.'();';
                }

                $sorok .= nl2('
                            <tr>
                                <td>
                                    '.$js.'
                                    <a href="'.$link.'">'.$statusz['nev'].':</a>
                                </td>
                                <td>
                                    <a href="'.$link.'">'.number_format($db, 0, '', '.').' db.</a>
                                </td>
                            </tr>
                            ');
            }
        }

        if( $sorok ) {
            $sorok = '<table class="csikos full">'.$sorok.'</table>';
        } else {
            $sorok = '<div class="padd">'.DASHB_NINCS_BEJEGYZES.'</div>';
        }
        $html .= nl2('
            <div class="box_grid">
                <div class="box">
                    <h2>Bejelentések</h2>
                    <div class="padd">
                        '.$sorok.'
                    </div>
                </div>
                ');
    }
    
    
    
    // MUNKALAPOK
    if( munkalap_lathat() ) {

        $sorok = '';
        
        // Alap where feltétel (ebben már jogosultság és szerep kör szerint eleve szűrve van az SQL
        $where = get_munkalap_lathatosag_feltetel('munkalap', $szallodak);
        $where = '('.csonkit($where, 5).') ';
        
        $login = $adatbazis_kapcsolat->s2('jh_ft_ig', 'login', "`recid`='{$_SESSION[get_db_tabla().'login_recid']}'");
        
        // Státuszok bejárása, sorok képzése belőle
        $statuszok = array(
                        array('nev' => 'Nyitott',                   'stat_id' => MUNKALAP_STAT_NYITOTT),
                        array('nev' => 'Hozzárendelt',              'stat_id' => MUNKALAP_STAT_HOZZARENDELT),
                        array('nev' => 'Felvéve',                   'stat_id' => MUNKALAP_STAT_FELVEVE),
                        array('nev' => 'Előkészítés alatt',         'stat_id' => MUNKALAP_STAT_ELOKESZITES_ALATT),
                        array('nev' => 'Anyagra vár',               'stat_id' => MUNKALAP_STAT_ANYAGRAVAR),
                        array('nev' => 'Várakozó',                  'stat_id' => MUNKALAP_STAT_VARAKOZO),
                        array('nev' => 'Lezárásra vár',             'stat_id' => MUNKALAP_STAT_LEZARASRA_VAR),
                        array('nev' => 'Határidős',                 'stat_id' => MUNKALAP_STAT_HATIDO_UTEMEZETT),
                        array('nev' => 'Igazolásra vár',            'stat_id' => MUNKALAP_STAT_IGAZOLASRA_VAR),
                        array('nev' => 'Döntésre vár (száll.ig)',   'stat_id' => MUNKALAP_STAT_DONTESRE_VAR),
                        array('nev' => 'Döntésre vár (MSZK vez.)',  'stat_id' => MUNKALAP_STAT_DONTESRE_VAR),
                        array('nev' => 'Ajánlat nyomtatva',         'stat_id' => MUNKALAP_STAT_AJANLAT_NYOMTATVA),
                        array('nev' => 'Megrendelésre vár',         'stat_id' => MUNKALAP_STAT_HOZREND_MEGREND_VAR),
                        array('nev' => 'Megr. Sztornóra vár',       'stat_id' => MUNKALAP_STAT_SZTORNO_VAR),
                    );
        
        $tabla = 'munkalap';
        foreach( $statuszok as $statusz ) {

            // Döntésre vár státusz kétszer szerepel benne egymás után, és ez két külön esetet jelent
            $fgv_kieg = $link_kieg = $sql_kieg = '';
            if( $statusz['stat_id'] == MUNKALAP_STAT_DONTESRE_VAR ) {
                $link_kieg  = '&amp;dv=sz_ig';
                $sql_kieg   = " AND `jh1_szerep`!='".MSZK_VEZETO."' AND `jh2_szerep`!='".MSZK_VEZETO."' AND `jh_ert`<='{$login['jh_ft_ig']}'";
            }
            if( $elozo_stat == $statusz['stat_id'] ) {
                $fgv_kieg   = '2';
                $link_kieg  = '&amp;dv=mszk_vez';
                $sql_kieg   = " AND `jh1_szerep`!='".SZALLODA_IGAZGATO."' AND `jh2_szerep`!='".SZALLODA_IGAZGATO."' AND `jh_ert`<='{$login['jh_ft_ig']}'";
            }
            $elozo_stat = $statusz['stat_id'];
                    
            $db = $adatbazis_kapcsolat->s('recid', $tabla, "`stat_id`='".$statusz['stat_id']."' AND {$where}{$sql_kieg}", '', '', '', 'Num rows');
if( isset($_GET['teszt']) ) {
    echo $adatbazis_kapcsolat->gp() . '<br>';
}
            if( $db ) {
                
                // Ha túl sok találat van, akkor rá kérdezünk, hogy biztosan szeretné-e listázni
                $js   = '';
                $link = $tabla.'.php?mezo=stat_id&amp;op=equal&amp;felt='.$statusz['stat_id'].'&amp;modosit'.$link_kieg;
                if( $db > SZURES_DB_LIMIT ) {
                    $js = '
                        <script>
                        function kerdes_'.$tabla.'_'.$statusz['stat_id'].$fgv_kieg.'() {
                            alert2(\'Ezzel a szűrési feltétellel '.number_format($db, 0, '', '.').' sor adatot listázna ki.<br>Biztosan folytatja?\', \'<input type="button" class="gomb zold" value="Igen" onclick="location.href=\\\''.$link.'\\\'"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">\', \'Kérdés\');
                        }
                        </script>
                        ';
                    $link = 'javascript:kerdes_'.$tabla.'_'.$statusz['stat_id'].$fgv_kieg.'();';
                }

                $sorok .= nl2('
                            <tr>
                                <td>
                                    '.$js.'
                                    <a href="'.$link.'">'.$statusz['nev'].':</a>
                                </td>
                                <td>
                                    <a href="'.$link.'">'.number_format($db, 0, '', '.').' db.</a>
                                </td>
                            </tr>
                            ');
            }
        }
        
        
        if( $sorok ) {
            $sorok = '<table class="csikos full">'.$sorok.'</table>';
        } else {
            $sorok = '<div class="padd">'.DASHB_NINCS_BEJEGYZES.'</div>';
        }
        $html .= nl2('
                <div class="box">
                    <h2>Munkalapok</h2>
                    <div class="padd">
                        '.$sorok.'
                    </div>
                </div>
                ');
    }
    
    
    // ANYAGIGÉNYLÉSEK
    if( anyagigenyles_lathat() ) {

        $sorok = '';
        // Státuszok bejárása, sorok képzése belőle
        // Anyagigénylés láthatóság esetén
        if( _jog(JOG_ANYAIGENLES_MEGTEKINT) ) {
            $statuszok = array(
                            array('nev' => 'Árazva (jóváhagyásra vár)', 'stat_id' => CIKKIGN_STAT_ARAZVA),
                            array('nev' => 'Jóváhagyva',                'stat_id' => CIKKIGN_STAT_JOVAHAGYVA),
                            array('nev' => 'Nyitott (árazásra vár)',    'stat_id' => CIKKIGN_STAT_NYITOTT),
                            array('nev' => 'Várakozó',                  'stat_id' => CIKKIGN_STAT_VARAKOZO),
                        );
            foreach( $statuszok as $statusz ) {

                $db = $adatbazis_kapcsolat->s('recid', 'anyagigenyles', "`stat_id`='{$statusz['stat_id']}' AND `telephely_id` IN (".implode(',', $szallodak).")", '', '', '', 'Num rows');
                if( $db ) {

                    // Ha túl sok találat van, akkor rá kérdezünk, hogy biztosan szeretné-e listázni
                    $js   = '';
                    $link = 'anyagigenyles.php?mezo=stat_id&amp;op=equal&amp;felt='.$statusz['stat_id'].'&amp;tablazat';
                    if( $statusz['anyagrend'] ) {
                        $link .= '&amp;anyagrend=1';
                    }
                    if( $db > SZURES_DB_LIMIT ) {
                        $js = '
                            <script>
                            function kerdes_anyag_'.$statusz['stat_id'].'() {
                                alert2(\'Ezzel a szűrési feltétellel '.number_format($db, 0, '', '.').' sor adatot listázna ki.<br>Biztosan folytatja?\', \'<input type="button" class="gomb zold" value="Igen" onclick="location.href=\\\''.$link.'\\\'"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">\', \'Kérdés\');
                            }
                            </script>
                            ';
                        $link = 'javascript:kerdes_anyag_'.$statusz['stat_id'].'();';
                    }

                    $sorok .= nl2('
                                <tr>
                                    <td>
                                        '.$js.'
                                        <a href="'.$link.'">'.$statusz['nev'].':</a>
                                    </td>
                                    <td>
                                        <a href="'.$link.'">'.number_format($db, 0, '', '.').' db.</a>
                                    </td>
                                </tr>
                                ');
                }
            }
        }
        
        // Anyagrendelés felületre
        if( _jog(JOG_ANYAGREND_MEGTEKINT) ) {
            $statuszok = array(
                            array('nev' => 'Megrendelve<br>(érkeztetésre vár)',         'stat_id' => CIKKIGN_STAT_MEGRENDELVE   ),
                            array('nev' => 'Részben beérkezett<br>(érkeztetésre vár)',  'stat_id' => CIKKIGN_STAT_RESZBENBEERK  ),
                            array('nev' => 'Beérkezett',                                'stat_id' => CIKKIGN_STAT_BEERKEZETT    ),
                            array('nev' => 'Törölt',                                    'stat_id' => CIKKIGN_STAT_TOROLT        ),
                            array('nev' => 'Elutasítva',                                'stat_id' => CIKKIGN_STAT_ELUTASITVA    ),
                        );
            foreach( $statuszok as $statusz ) {

                $db = $adatbazis_kapcsolat->s('recid', 'anyagigenyles', "`stat_id`='{$statusz['stat_id']}' AND `telephely_id` IN (".implode(',', $szallodak).")", '', '', '', 'Num rows');
                if( $db ) {

                    // Ha túl sok találat van, akkor rá kérdezünk, hogy biztosan szeretné-e listázni
                    $js   = '';
                    $link = 'anyagigenyles.php?mezo=stat_id&amp;op=equal&amp;felt='.$statusz['stat_id'].'&amp;tablazat&amp;anyagrend=1';
                    if( $statusz['anyagrend'] ) {
                        $link .= '&amp;anyagrend=1';
                    }
                    if( $db > SZURES_DB_LIMIT ) {
                        $js = '
                            <script>
                            function kerdes_anyag_'.$statusz['stat_id'].'() {
                                alert2(\'Ezzel a szűrési feltétellel '.number_format($db, 0, '', '.').' sor adatot listázna ki.<br>Biztosan folytatja?\', \'<input type="button" class="gomb zold" value="Igen" onclick="location.href=\\\''.$link.'\\\'"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">\', \'Kérdés\');
                            }
                            </script>
                            ';
                        $link = 'javascript:kerdes_anyag_'.$statusz['stat_id'].'();';
                    }

                    $sorok .= nl2('
                                <tr>
                                    <td>
                                        '.$js.'
                                        <a href="'.$link.'">'.$statusz['nev'].':</a>
                                    </td>
                                    <td>
                                        <a href="'.$link.'">'.number_format($db, 0, '', '.').' db.</a>
                                    </td>
                                </tr>
                                ');
                }
            }
        }

        if( $sorok ) {
            $sorok = '<table class="csikos full">'.$sorok.'</table>';
        } else {
            $sorok = '<div class="padd">'.DASHB_NINCS_BEJEGYZES.'</div>';
        }
        $html .= nl2('
                    <div class="box">
                        <h2>Anyagok</h2>
                        <div class="padd">
                            '.$sorok.'
                        </div>
                    </div>
                ');
    }



    // PROJEKTEK
    if( projektek_lathat() ) {
        $sorok = '';

        // Igénylés jóváhagyása jog
        if( _jog(JOG_PRKEZEL_KEZEL) || _jog(JOG_PRKEZEL_SAJAT_KEZEL) ) {

            // A projektkezelés láthatóság SQL központosítva van
            $where = get_projektkezeles_where_jog();
            if ( $where ) {
                $where = ' AND '.trim($where, ' AND ');
            }

            // Státuszok bejárása, sorok képzése belőle
            $statuszok = array(
                            array('nev' => 'Nyitott',           'stat_id' => PRKEZEL_STAT_NYITOTT),
                            array('nev' => 'Előkészítés alatt', 'stat_id' => PRKEZEL_STAT_ELOKESZITES_ALATT),
                            array('nev' => 'Folyamatban',       'stat_id' => PRKEZEL_STAT_FOLYAMATBAN),
                            array('nev' => 'Kész',              'stat_id' => PRKEZEL_STAT_KESZ),
                        );
            foreach( $statuszok as $statusz ) {

                $db = $adatbazis_kapcsolat->s('recid', 'projektkezeles', "`stat_id`='{$statusz['stat_id']}'{$where}", '', '', '', 'Num rows');
                if( $db ) {

                    // Ha túl sok találat van, akkor rá kérdezünk, hogy biztosan szeretné-e listázni
                    $js   = '';
                    $link = 'projektkezeles.php?mezo=stat_id&amp;op=equal&amp;felt='.$statusz['stat_id'].'&amp;modosit';
                    if( $db > SZURES_DB_LIMIT ) {
                        $js = '
                            <script>
                            function kerdes_pr_'.$statusz['stat_id'].'() {
                                alert2(\'Ezzel a szűrési feltétellel '.number_format($db, 0, '', '.').' sor adatot listázna ki.<br>Biztosan folytatja?\', \'<input type="button" class="gomb zold" value="Igen" onclick="location.href=\\\''.$link.'\\\'"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">\', \'Kérdés\');
                            }
                            </script>
                            ';
                        $link = 'javascript:kerdes_pr_'.$statusz['stat_id'].'();';
                    }
                    $sorok .= nl2('
                                <tr>
                                    <td>
                                        '.$js.'
                                        <a href="'.$link.'">'.$statusz['nev'].':</a>
                                    </td>
                                    <td>
                                        <a href="'.$link.'">'.number_format($db, 0, '', '.').' db.</a>
                                    </td>
                                </tr>
                                ');
                }
            }

        }

        if( $sorok ) {
            $sorok = '<table class="csikos full">'.$sorok.'</table>';
        } else {
            $sorok = '<div class="padd">'.DASHB_NINCS_BEJEGYZES.'</div>';
        }
        $html .= nl2('
                <div class="box">
                    <h2>Projektek</h2>
                    <div class="padd">
                        '.$sorok.'
                    </div>
                </div>
            </div>
            ');
    }
    
    $html .= nl2('
            <script>
                /* Mobil alatt a nyitólap ne tűnjön el */
                $(document).ready(function () {
                    $(\'#tartalomJobb\').show();
                });
            </script>
            ');

    echo $html;
}