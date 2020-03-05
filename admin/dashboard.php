<?php
$html = '';

// Éles és teszt-en még ne látszódjon, csak dev-en és saját környezetben
if( $_SERVER['HTTP_HOST']!='www.onteam.hu' && $_SERVER['HTTP_HOST']!='onteam.hu' ) {
    
    if( !isset($_GET['regi']) ) {
        
        $szallodak = get_akt_login_szallodai();
        $telephely_sql       = " AND `telephely_id` IN (".implode(',', $szallodak).")";
        $telephely_sql_mlhoz = ' AND ('.csonkit( get_munkalap_lathatosag_feltetel('munkalap', $szallodak), 5).')';
        
        $feladatok = '';
        
        
        // Bejelenetés igazolása
        if( _jog(JOG_BEJELENTES_KEZEL) ) {
            
            // Részleg ellenőrzése
            $login = $adatbazis_kapcsolat->s2('reszleg_id', 'login', "`recid`='{$_SESSION[get_db_tabla().'login_recid']}'");
            $reszlegek = array($login['reszleg_id']);
            $o_szuro_feltetel = '|!HASH!me|usr_reszleg_id!HASH!op|equal!HASH!fe|'.$login['reszleg_id'].'!HASH!va';
            // Ha van hozzá joga, megnézzük a társrészlegeket
            if( _jog(JOG_BEJELENTES_TARSRESZLEG_BEJ_IGAZOL) ) {
                $tarsreszlegek = $adatbazis_kapcsolat->s('reszleg_id', 'login_tarsreszleg', "`login_id`='{$_SESSION[get_db_tabla().'login_recid']}'");
                foreach ( $tarsreszlegek as $reszleg ) {
                    $o_szuro_feltetel .= '|!HASH!me|usr_reszleg_id!HASH!op|equal!HASH!fe|'.$reszleg['reszleg_id'].'!HASH!va';
                    array_push($reszlegek, $reszleg['reszleg_id']);
                }
            }
            // Utolsó "Vagy" (azaz "|va") törlése
            $o_szuro_feltetel = csonkit($o_szuro_feltetel, 2);
            
            // Vegyük azokat a Bejelentéseket, amik BEJELENTES_STAT_KESZ_IG státuszúak, és a részlegük benne van a $reszlegek-ben
            $db     = $adatbazis_kapcsolat->s('recid', 'bejelentes', "`stat_id`='".BEJELENTES_STAT_KESZ_IG."' AND `usr_reszleg_id` IN (".implode(', ', $reszlegek)."){$telephely_sql}", '', '', '', 'num rows');
            $link   = 'bejelentes.php?tabla=bejelentes&amp;feltetel=me|stat_id!HASH!op|equal!HASH!fe|'.BEJELENTES_STAT_KESZ_IG.'!HASH!es|!HASH!ny'.$o_szuro_feltetel.'za|!HASH!es|!HASH!&amp;f_o_szures_submit=1&amp;dashboard=Bejelentés igazolása';
            
            if( $db ) {
                $feladatok .= nl2('
                                    <tr>
                                        <td>
                                            <a href="'.$link.'">
                                                Bejelentés igazolása
                                            </a>
                                        </td>
                                        <td>
                                            <a href="'.$link.'">
                                                '.number_format($db, 0, '', '.').' db
                                            </a>
                                        </td>
                                    </tr>
                                    ');
            }
        }
        
        
        
        
        // Reklamáció kezelése
        if( _jog(JOG_BEJELENTES_VIZSGAL) ) {
            
            $db     = $adatbazis_kapcsolat->s('recid', 'bejelentes', "`stat_id`='".BEJELENTES_STAT_VIZSG_ALATT."'{$telephely_sql}", '', '', '', 'num rows');
            $link   = 'bejelentes.php?mezo=stat_id&amp;op=equal&amp;felt='.BEJELENTES_STAT_VIZSG_ALATT.'&amp;modosit&amp;dashboard=Reklamáció kezelése';
            
            if( $db ) {
                $feladatok .= nl2('
                                    <tr>
                                        <td>
                                            <a href="'.$link.'">
                                                Reklamáció kezelése
                                            </a>
                                        </td>
                                        <td>
                                            <a href="'.$link.'">
                                                '.number_format($db, 0, '', '.').' db
                                            </a>
                                        </td>
                                    </tr>
                                    ');
            }
        }
        
        
        
        
        // Munkalap készre jelentése. Ez két feltételből lett összegyúrva
        if( _jog(JOG_MUNKALAP_KEZEL_KULSO) || _jog(JOG_MUNKALAP_SAJAT_KEZEL) || _jog(JOG_MUNKALAP_KEZEL_TELEPITETT) || _jog(JOG_MUNKALAP_KEZEL_MOBIL) ) {

            // Azt, hogy milyen SQL-t kérünk le, függ a jogoktól
            // Kezdjük egy nagy nyitó zárüjellel, ezt az összetett szűrőnél is
            $sql               = '(';
            $o_szuro_feltetel  = '!HASH!ny|';
            
            // Ha van külső kezelés jogunk, akkor elég ha a ML hozzárendelt státuszú, és a hozzárendelt személy külső.
            // Ugyanez igaz akkor, ha Saját kezelés jogom van, és Külső kivitelező vagyok
            if( _jog(JOG_MUNKALAP_KEZEL_KULSO) ||
                ( _jog(JOG_MUNKALAP_SAJAT_KEZEL) && $_SESSION[get_db_tabla().'szemelyzet_id']==KULSO_KIVITELEZO ) 
            ) {
                // Ez a feltétel első fele, külön zárüjelbe szedve az SQL-ben és az összetett szűrőnél is
                $sql              .= "(`stat_id`='".MUNKALAP_STAT_HOZZARENDELT."' AND `szemelyzet_id`='".KULSO_KIVITELEZO."') OR ";
                $o_szuro_feltetel .= '!HASH!ny|!HASH!me|stat_id!HASH!op|equal!HASH!fe|'.MUNKALAP_STAT_HOZZARENDELT.'!HASH!es|!HASH!me|szemelyzet_id!HASH!op|equal!HASH!fe|'.KULSO_KIVITELEZO.'!HASH!za|!HASH!va|';
            }
            
            $o_szuro_feltetel_szemelyzet = '';
            $szemelyzet_idk = array();
            
            // Ha Telepített vagy Mobilcsoport vagyok, és van saját szerepkör kezelés jogom, akkor a saját szerepkörömnek megfelelő ML-okat tudom készre jelenteni
            $felvehetik = array(TELEPITETT_SZEMELYZET, MOBILCSOPORT);
            if( _jog(JOG_MUNKALAP_SAJAT_KEZEL) && in_array($_SESSION[get_db_tabla().'szemelyzet_id'], $felvehetik) ) {
                $o_szuro_feltetel_szemelyzet .= '!HASH!me|szemelyzet_id!HASH!op|equal!HASH!fe|'.$_SESSION[get_db_tabla().'szemelyzet_id'].'!HASH!va|';
                array_push($szemelyzet_idk, $_SESSION[get_db_tabla().'szemelyzet_id']);
            }
            // Telepített joggal azokat a ML-okat tudom készre jelenteni, ahol a hozzárendelt személy Telepített
            if( _jog(JOG_MUNKALAP_KEZEL_TELEPITETT) ) { 
                $o_szuro_feltetel_szemelyzet .= '!HASH!me|szemelyzet_id!HASH!op|equal!HASH!fe|'.TELEPITETT_SZEMELYZET.'!HASH!va|';
                array_push($szemelyzet_idk, TELEPITETT_SZEMELYZET);
            }
            // és Mobil joggal azokat a ML-okat tudom készre jelenteni, ahol a hozzárendelt személy Mobil
            if( _jog(JOG_MUNKALAP_KEZEL_MOBIL) ) {
                $o_szuro_feltetel_szemelyzet .= '!HASH!me|szemelyzet_id!HASH!op|equal!HASH!fe|'.MOBILCSOPORT.'!HASH!va|';
                array_push($szemelyzet_idk, MOBILCSOPORT);
            }
            if( $szemelyzet_idk ) {
                // Ez a másik nagy feltétel, külön zárójelbe zárva
                $o_szuro_feltetel_szemelyzet = csonkit($o_szuro_feltetel_szemelyzet, 9);
                $sql                .= "(`stat_id`='".MUNKALAP_STAT_FELVEVE."' AND `szemelyzet_id` IN (".implode(', ', $szemelyzet_idk).") ) OR ";
                $o_szuro_feltetel   .= '!HASH!ny|!HASH!me|stat_id!HASH!op|equal!HASH!fe|'.MUNKALAP_STAT_FELVEVE.'!HASH!es|!HASH!ny|'.$o_szuro_feltetel_szemelyzet.'!HASH!za|!HASH!za|!HASH!va|';
            }

            // SQL utolsó OR-ját levájuk, és a nagy bezárójelet bezárjuk, összetettnél is
            $sql               = csonkit($sql, 4).')';
            $o_szuro_feltetel  = csonkit($o_szuro_feltetel, 9).'!HASH!za|!HASH!es|!HASH!&amp;f_o_szures_submit=1';

            $db     = $adatbazis_kapcsolat->s('recid', 'munkalap', "{$sql}{$telephely_sql_mlhoz}", '', '', '', 'num rows');
            $link   = 'munkalap.php?tabla=munkalap&amp;feltetel='.$o_szuro_feltetel.'&amp;dashboard=Munkalap készre jelentése';
            
            if( $db ) {
                $feladatok .= nl2('
                                    <tr>
                                        <td>
                                            <a href="'.$link.'">
                                                Munkalap készre jelentése
                                            </a>
                                        </td>
                                        <td>
                                            <a href="'.$link.'">
                                                '.number_format($db, 0, '', '.').' db
                                            </a>
                                        </td>
                                    </tr>
                                    ');
            }
        }
        
        
        
        
        // Munkalap lezárása
        if( _jog(JOG_MUNKALAP_SAJAT_KEZEL) || _jog(JOG_MUNKALAP_KEZEL_TELEPITETT) || _jog(JOG_MUNKALAP_KEZEL_MOBIL) || _jog(JOG_MUNKALAP_KEZEL_KULSO) ) {
            
            // Azt, hogy milyen SQL-t kérünk le, függ a jogoktól
            // Kezdjük egy nagy nyitó zárüjellel, ezt az összetett szűrőnél is
            $sql               = '(';
            $o_szuro_feltetel  = '!HASH!ny|';
            
            if( _jog(JOG_MUNKALAP_SAJAT_KEZEL) ) {
                $sql                .= "(`stat_id`='".MUNKALAP_STAT_LEZARASRA_VAR."' AND `szemelyzet_id`='".$_SESSION[get_db_tabla().'szemelyzet_id']."') OR ";
                $o_szuro_feltetel   .= '!HASH!ny|!HASH!me|stat_id!HASH!op|equal!HASH!fe|'.MUNKALAP_STAT_LEZARASRA_VAR.'!HASH!es|!HASH!me|szemelyzet_id!HASH!op|equal!HASH!fe|'.$_SESSION[get_db_tabla().'szemelyzet_id'].'!HASH!za|!HASH!va|';
            }
            if( _jog(JOG_MUNKALAP_KEZEL_TELEPITETT) ) {
                $sql                .= "(`stat_id`='".MUNKALAP_STAT_LEZARASRA_VAR."' AND `szemelyzet_id`='".TELEPITETT_SZEMELYZET."') OR ";
                $o_szuro_feltetel   .= '!HASH!ny|!HASH!me|stat_id!HASH!op|equal!HASH!fe|'.MUNKALAP_STAT_LEZARASRA_VAR.'!HASH!es|!HASH!me|szemelyzet_id!HASH!op|equal!HASH!fe|'.TELEPITETT_SZEMELYZET.'!HASH!za|!HASH!va|';
            }
            if( _jog(JOG_MUNKALAP_KEZEL_MOBIL) ) {
                $sql                .= "(`stat_id`='".MUNKALAP_STAT_LEZARASRA_VAR."' AND `szemelyzet_id`='".MOBILCSOPORT."') OR ";
                $o_szuro_feltetel   .= '!HASH!ny|!HASH!me|stat_id!HASH!op|equal!HASH!fe|'.MUNKALAP_STAT_LEZARASRA_VAR.'!HASH!es|!HASH!me|szemelyzet_id!HASH!op|equal!HASH!fe|'.MOBILCSOPORT.'!HASH!za|!HASH!va|';
            }
            if( _jog(JOG_MUNKALAP_KEZEL_KULSO) ) {
                $sql                .= "(`stat_id`='".MUNKALAP_STAT_LEZARASRA_VAR."' AND `szemelyzet_id`='".KULSO_KIVITELEZO."') OR ";
                $o_szuro_feltetel   .= '!HASH!ny|!HASH!me|stat_id!HASH!op|equal!HASH!fe|'.MUNKALAP_STAT_LEZARASRA_VAR.'!HASH!es|!HASH!me|szemelyzet_id!HASH!op|equal!HASH!fe|'.KULSO_KIVITELEZO.'!HASH!za|!HASH!va|';
            }
            
            // SQL utolsó OR-ját levájuk, összetettnél is
            $sql               = csonkit($sql, 4).')';
            $o_szuro_feltetel  = csonkit($o_szuro_feltetel, 9).'!HASH!za|!HASH!es|!HASH!&amp;f_o_szures_submit=1';
            
            $db     = $adatbazis_kapcsolat->s('recid', 'munkalap', "{$sql}{$telephely_sql_mlhoz}", '', '', '', 'num rows');
            $link   = 'munkalap.php?tabla=munkalap&amp;feltetel='.$o_szuro_feltetel.'&amp;dashboard=Munkalap lezárása';
            
            if( $db ) {
                $feladatok .= nl2('
                                    <tr>
                                        <td>
                                            <a href="'.$link.'">
                                                Munkalap lezárása
                                            </a>
                                        </td>
                                        <td>
                                            <a href="'.$link.'">
                                                '.number_format($db, 0, '', '.').' db
                                            </a>
                                        </td>
                                    </tr>
                                    ');
            }
        }
        
        
        
        
        // Munkalap felvétele
        if( _jog(JOG_MUNKALAP_SAJAT_KEZEL) || _jog(JOG_MUNKALAP_KEZEL_TELEPITETT) || _jog(JOG_MUNKALAP_KEZEL_MOBIL) ) {
            
            // Azt, hogy milyen SQL-t kérünk le, függ a jogoktól
            // Kezdjük egy nagy nyitó zárüjellel, ezt az összetett szűrőnél is
            $sql               = '(';
            $o_szuro_feltetel  = '!HASH!ny|';
            
            $felvehetik = array(TELEPITETT_SZEMELYZET, MOBILCSOPORT);
            if( _jog(JOG_MUNKALAP_SAJAT_KEZEL) && in_array($_SESSION[get_db_tabla().'szemelyzet_id'], $felvehetik) ) {
                $sql                .= "(`stat_id`='".MUNKALAP_STAT_HOZZARENDELT."' AND `szemelyzet_id`='{$_SESSION[get_db_tabla().'szemelyzet_id']}') OR ";
                $o_szuro_feltetel   .= '!HASH!ny|!HASH!me|stat_id!HASH!op|equal!HASH!fe|'.MUNKALAP_STAT_HOZZARENDELT.'!HASH!es|!HASH!me|szemelyzet_id!HASH!op|equal!HASH!fe|'.$_SESSION[get_db_tabla().'szemelyzet_id'].'!HASH!za|!HASH!va|';
            }
            if( _jog(JOG_MUNKALAP_KEZEL_TELEPITETT) ) {
                $sql                .= "(`stat_id`='".MUNKALAP_STAT_HOZZARENDELT."' AND `szemelyzet_id`='".TELEPITETT_SZEMELYZET."') OR ";
                $o_szuro_feltetel   .= '!HASH!ny|!HASH!me|stat_id!HASH!op|equal!HASH!fe|'.MUNKALAP_STAT_HOZZARENDELT.'!HASH!es|!HASH!me|szemelyzet_id!HASH!op|equal!HASH!fe|'.TELEPITETT_SZEMELYZET.'!HASH!za|!HASH!va|';
            }
            if( _jog(JOG_MUNKALAP_KEZEL_MOBIL) ) {
                $sql                .= "(`stat_id`='".MUNKALAP_STAT_HOZZARENDELT."' AND `szemelyzet_id`='".MOBILCSOPORT."') OR ";
                $o_szuro_feltetel   .= '!HASH!ny|!HASH!me|stat_id!HASH!op|equal!HASH!fe|'.MUNKALAP_STAT_HOZZARENDELT.'!HASH!es|!HASH!me|szemelyzet_id!HASH!op|equal!HASH!fe|'.MOBILCSOPORT.'!HASH!za|!HASH!va|';
            }
            
            // SQL utolsó OR-ját levájuk, összetettnél is
            $sql               = csonkit($sql, 4).')';
            $o_szuro_feltetel  = csonkit($o_szuro_feltetel, 9).'!HASH!za|!HASH!es|!HASH!&amp;f_o_szures_submit=1';
            
            $db   = $adatbazis_kapcsolat->s('recid', 'munkalap', "{$sql}{$telephely_sql_mlhoz}", '', '', '', 'num rows');
            $link = 'munkalap.php?tabla=munkalap&amp;feltetel='.$o_szuro_feltetel.'&amp;dashboard=Munkalap felvétele';
            
            if( $db ) {
                $feladatok .= nl2('
                                    <tr>
                                        <td>
                                            <a href="'.$link.'">
                                                Munkalap felvétele
                                            </a>
                                        </td>
                                        <td>
                                            <a href="'.$link.'">
                                                '.number_format($db, 0, '', '.').' db
                                            </a>
                                        </td>
                                    </tr>
                                    ');
            }
        }
        
        
        
        
        // Munkalap igazolása
        if( _jog(JOG_MUNKALAP_IGAZOLAS) ) {

            $munkalapok = $adatbazis_kapcsolat->s('recid', 'munkalap', "`stat_id`='".MUNKALAP_STAT_IGAZOLASRA_VAR."'{$telephely_sql_mlhoz}");
            $link       = 'munkalap.php?mezo=stat_id&amp;op=equal&amp;felt='.MUNKALAP_STAT_IGAZOLASRA_VAR.'&amp;modosit&amp;dashboard=Munkalap igazolása';
            
            $db = count($munkalapok);
            if( $db ) {
                $feladatok .= nl2('
                                    <tr>
                                        <td>
                                            <a href="'.$link.'">
                                                Munkalap igazolása
                                            </a>
                                        </td>
                                        <td>
                                            <a href="'.$link.'">
                                                '.number_format($db, 0, '', '.').' db
                                            </a>
                                        </td>
                                    </tr>
                                    ');
            }
        }
        
        
        
        
        // Munkalap vizsgálata
        if( _jog(JOG_MUNKALAP_VIZSGALAT) ) {

            $db     = $adatbazis_kapcsolat->s('recid', 'munkalap', "`stat_id`='".MUNKALAP_STAT_VIZSGALAT_ALATT."'{$telephely_sql_mlhoz}", '', '', '', 'num rows');
            $link   = 'munkalap.php?mezo=stat_id&amp;op=equal&amp;felt='.MUNKALAP_STAT_VIZSGALAT_ALATT.'&amp;modosit&amp;dashboard=Munkalap vizsgálata';
            
            if( $db ) {
                $feladatok .= nl2('
                                    <tr>
                                        <td>
                                            <a href="'.$link.'">
                                                Munkalap vizsgálata
                                            </a>
                                        </td>
                                        <td>
                                            <a href="'.$link.'">
                                                '.number_format($db, 0, '', '.').' db
                                            </a>
                                        </td>
                                    </tr>
                                    ');
            }
        }
        
        
        
        
        // Külsős munka előkészítése
        if( _jog(JOG_MUNKALAP_KULSOS_ELOKESZIT) ) {

            $db     = $adatbazis_kapcsolat->s('recid', 'munkalap', "`stat_id`='".MUNKALAP_STAT_ELOKESZITES_ALATT."' AND `szemelyzet_id`='{$_SESSION[get_db_tabla().'szemelyzet_id']}'{$telephely_sql_mlhoz}", '', '', '', 'num rows');
            $link   = 'munkalap.php?tabla=munkalap&amp;feltetel=me|stat_id!HASH!op|equal!HASH!fe|'.MUNKALAP_STAT_ELOKESZITES_ALATT.'!HASH!es|!HASH!me|szemelyzet_id!HASH!op|equal!HASH!fe|'.$_SESSION[get_db_tabla().'szemelyzet_id'].'!HASH!es|!HASH!&amp;f_o_szures_submit=1&amp;dashboard=Külsős munka előkészítése';
            
            if( $db ) {
                $feladatok .= nl2('
                                    <tr>
                                        <td>
                                            <a href="'.$link.'">
                                                Külsős munka előkészítése
                                            </a>
                                        </td>
                                        <td>
                                            <a href="'.$link.'">
                                                '.number_format($db, 0, '', '.').' db
                                            </a>
                                        </td>
                                    </tr>
                                    ');
            }
        }
        
        
        
        
        // Külsős munka jóváhagyása
        if( _jog(JOG_MUNKALAP_KULSOS_DONTES) ) {

            $db                 = $adatbazis_kapcsolat->s('recid', 'munkalap', "`stat_id`='".MUNKALAP_STAT_DONTESRE_VAR."' AND `jh1_szerep`!='{$_SESSION[get_db_tabla().'szemelyzet_id']}' AND `jh2_szerep`!='{$_SESSION[get_db_tabla().'szemelyzet_id']}'{$telephely_sql_mlhoz}", '', '', '', 'num rows');
            $o_szuro_feltetel   = '!HASH!me|stat_id!HASH!op|equal!HASH!fe|'.MUNKALAP_STAT_DONTESRE_VAR.'!HASH!es|!HASH!me|jh1_szerep!HASH!op|not_eq!HASH!fe|'.$_SESSION[get_db_tabla().'szemelyzet_id'].'!HASH!es|!HASH!me|jh2_szerep!HASH!op|not_eq!HASH!fe|'.$_SESSION[get_db_tabla().'szemelyzet_id'].'!HASH!es|!HASH!&amp;f_o_szures_submit=1';
            $link               = 'munkalap.php?tabla=munkalap&amp;feltetel='.$o_szuro_feltetel.'&amp;dashboard=Külsős munka jóváhagyása';
            
            if( $db ) {
                $feladatok .= nl2('
                                    <tr>
                                        <td>
                                            <a href="'.$link.'">
                                                Külsős munka jóváhagyása
                                            </a>
                                        </td>
                                        <td>
                                            <a href="'.$link.'">
                                                '.number_format($db, 0, '', '.').' db
                                            </a>
                                        </td>
                                    </tr>
                                    ');
            }
        }
        
        
        
        
        // Külső munka megrendelése
        if( _jog(JOG_MUNKALAP_KULSOS_MEGREND) ) {

            $db     = $adatbazis_kapcsolat->s('recid', 'munkalap', "`stat_id`='".MUNKALAP_STAT_AJANLAT_NYOMTATVA."'{$telephely_sql_mlhoz}", '', '', '', 'num rows');
            $link   = 'munkalap.php?mezo=stat_id&amp;op=equal&amp;felt='.MUNKALAP_STAT_AJANLAT_NYOMTATVA.'&amp;modosit&amp;dashboard=Külső munka megrendelése';
            
            if( $db ) {
                $feladatok .= nl2('
                                    <tr>
                                        <td>
                                            <a href="'.$link.'">
                                                Külső munka megrendelése
                                            </a>
                                        </td>
                                        <td>
                                            <a href="'.$link.'">
                                                '.number_format($db, 0, '', '.').' db
                                            </a>
                                        </td>
                                    </tr>
                                    ');
            }
        }
        
        
        
        
        // Megr. sztornó email elküldése
        if( _jog(JOG_MUNKALAP_KULSOS_MEGREND) ) {

            $db     = $adatbazis_kapcsolat->s('recid', 'munkalap', "`stat_id`='".MUNKALAP_STAT_SZTORNO_VAR."'{$telephely_sql_mlhoz}", '', '', '', 'num rows');
            $link   = 'munkalap.php?mezo=stat_id&amp;op=equal&amp;felt='.MUNKALAP_STAT_SZTORNO_VAR.'&amp;modosit&amp;dashboard=Megr. sztornó email elküldése';
            
            if( $db ) {
                $feladatok .= nl2('
                                    <tr>
                                        <td>
                                            <a href="'.$link.'">
                                                Megr. sztornó email elküldése
                                            </a>
                                        </td>
                                        <td>
                                            <a href="'.$link.'">
                                                '.number_format($db, 0, '', '.').' db
                                            </a>
                                        </td>
                                    </tr>
                                    ');
            }
        }
        
        
        
        
        // Munkalapon hiányzó anyag igénylése
        if( _jog(JOG_ANYAGIGENYLES_KEZEL) ) {
            
            // Ami itt következik lekérdezés, azt az anyagigenyles.php?uj felületen az "Igénylésre váró anyagok a munkalapokon" címkéjű tálbázat alapján állítjuk elő
            $db = 0;
            $munkalapok = $adatbazis_kapcsolat->s('recid', 'munkalap', "`telephely_id` IN (".implode(',', $szallodak).") AND `stat_id`='".MUNKALAP_STAT_ANYAGRAVAR."'");
            $munkalapok = adatbazis2tomb($munkalapok, 'recid');
            if( $munkalapok ) {
                $anyag_sql = " AND `szuk_men`>0 AND `stat_id`='".DISZPNAG_STAT_ANYAGRA_VAR."'";
                // Szállodánként járjuk be, és azon belül az egyes anyagokat összegezzük
                $munkalapok = implode(',', $munkalapok);
                $telephelyek = $adatbazis_kapcsolat->s('distinct telephely_id', 'munkalap_anyag', "`munkalap_id` IN (".$munkalapok.") AND `stat_id`!='".DISZPNAG_STAT_VAN_ANYAG."'".$anyag_sql);
                foreach ( $telephelyek as $telephely ) {
                    // Azon belül anyagonként járjuk be
                    $anyagok = $adatbazis_kapcsolat->s('distinct cikk_id', 'munkalap_anyag', "`munkalap_id` IN (".$munkalapok.") AND `stat_id`!='".DISZPNAG_STAT_VAN_ANYAG."' AND `telephely_id`='{$telephely['telephely_id']}'".$anyag_sql);
                    foreach ( $anyagok as $anyag ) {
                        $db++;
                    }
                }
            }
            
            $link       = 'anyagigenyles.php?uj';
            if( $db ) {
                $feladatok .= nl2('
                                    <tr>
                                        <td>
                                            <a href="'.$link.'">
                                                Munkalapon hiányzó anyag igénylése
                                            </a>
                                        </td>
                                        <td>
                                            <a href="'.$link.'">
                                                '.number_format($db, 0, '', '.').' db
                                            </a>
                                        </td>
                                    </tr>
                                    ');
            }
        }
        
        
        
        
        // DASHBOARD_H_A_ML_CIMKE
        if( _jog(JOG_ANYAGIGENYLES_KEZEL) && !_jog(JOG_MUNKALAP_MEGTEKINT_FELADAT) ) {
            
            $where_kieg = '';
            if( _jog(JOG_MUNKALAP_MEGTEKINT) ) {
                // Hozzárendelt személyzet megállapítása. Ez egyenlő a Személyzet beosztásával.
                $where_kieg = "`".get_db_tabla()."munkalap`.`szemelyzet_id`='{$_SESSION[get_db_tabla().'szemelyzet_id']}' AND ";
                if( !$_SESSION[get_db_tabla() . 'szemelyzet_id'] ) {
                    $where_kieg .= ' FALSE AND ';
                }
            }
            
            $munkalapok = mysqli_query($db_kapcs, "SELECT `".get_db_tabla()."munkalap`.`recid` FROM `".get_db_tabla()."munkalap` JOIN `".get_db_tabla()."munkalap_anyag` ON `".get_db_tabla()."munkalap`.`recid`=`".get_db_tabla()."munkalap_anyag`.`munkalap_id` WHERE {$where_kieg}`".get_db_tabla()."munkalap`.`stat_id`='".MUNKALAP_STAT_ANYAGRAVAR."' AND `".get_db_tabla()."munkalap_anyag`.`stat_id`='".MUNKALAP_ANYAG_STAT_AI_SZUKS."'{$telephely_sql_mlhoz} GROUP BY `".get_db_tabla()."munkalap_anyag`.`munkalap_id`");
            $db         = mysqli_num_rows($munkalapok);
            $link       = 'munkalap.php?mezo=anyag_stat_id&amp;op=equal&amp;felt='.MUNKALAP_ANYAG_STAT_AI_SZUKS.'&amp;modosit&amp;dashboard='.DASHBOARD_H_A_ML_CIMKE;

            if( $db ) {
                $feladatok .= nl2('
                                    <tr>
                                        <td>
                                            <a href="'.$link.'">
                                                '.DASHBOARD_H_A_ML_CIMKE.'
                                            </a>
                                        </td>
                                        <td>
                                            <a href="'.$link.'">
                                                '.number_format($db, 0, '', '.').' db
                                            </a>
                                        </td>
                                    </tr>
                                    ');
            }
        }
        
        
        
        
        // Anyagigénylés jóváhagyása
        if( _jog(JOG_ANYAGIGENYLES_JOVAHAGY) ) {

            $db     = $adatbazis_kapcsolat->s('recid', 'anyagigenyles', "`stat_id`='".CIKKIGN_STAT_ARAZVA."'{$telephely_sql}", '', '', '', 'num rows');
            $link   = 'anyagigenyles.php?mezo=stat_id&amp;op=equal&amp;felt='.CIKKIGN_STAT_ARAZVA.'&amp;tablazat&amp;dashboard=Anyagigénylés jóváhagyása';
            
            if( $db ) {
                $feladatok .= nl2('
                                    <tr>
                                        <td>
                                            <a href="'.$link.'">
                                                Anyagigénylés jóváhagyása
                                            </a>
                                        </td>
                                        <td>
                                            <a href="'.$link.'">
                                                '.number_format($db, 0, '', '.').' db
                                            </a>
                                        </td>
                                    </tr>
                                    ');
            }
        }
        
        
        
        
        // Anyagigénylés árazása
        if( _jog(JOG_ANYAGREND_KESZIT) ) {

            $db     = $adatbazis_kapcsolat->s('recid', 'anyagigenyles', "`stat_id`='".CIKKIGN_STAT_NYITOTT."'{$telephely_sql}", '', '', '', 'num rows');
            $link   = 'anyagigenyles.php?mezo=stat_id&amp;op=equal&amp;felt='.CIKKIGN_STAT_NYITOTT.'&amp;tablazat&amp;dashboard=Anyagigénylés árazása';
            
            if( $db ) {
                $feladatok .= nl2('
                                    <tr>
                                        <td>
                                            <a href="'.$link.'">
                                                Anyagigénylés árazása
                                            </a>
                                        </td>
                                        <td>
                                            <a href="'.$link.'">
                                                '.number_format($db, 0, '', '.').' db
                                            </a>
                                        </td>
                                    </tr>
                                    ');
            }
        }
        
        
        
        
        // Anyagrendelés elkészítése
        if( _jog(JOG_ANYAGREND_KESZIT) ) {

            $anyagig_ek = $adatbazis_kapcsolat->s('recid', 'anyagigenyles', "`stat_id`='".CIKKIGN_STAT_JOVAHAGYVA."'{$telephely_sql}");
            $link       = 'anyagigenyles.php?mezo=stat_id&amp;op=equal&amp;felt='.CIKKIGN_STAT_JOVAHAGYVA.'&amp;tablazat&amp;dashboard=Anyagrendelés elkészítése';
            
            $db = count($anyagig_ek);
            if( $db ) {
                $feladatok .= nl2('
                                    <tr>
                                        <td>
                                            <a href="'.$link.'">
                                                Anyagrendelés elkészítése
                                            </a>
                                        </td>
                                        <td>
                                            <a href="'.$link.'">
                                                '.number_format($db, 0, '', '.').' db
                                            </a>
                                        </td>
                                    </tr>
                                    ');
            }
        }
        
        
        
        
        // Dokumentumok minősítése
        if( _jog(JOG_PRKEZEL_KEZEL) || _jog(JOG_PRKEZEL_SAJAT_KEZEL) ) {

            $projektek  = $adatbazis_kapcsolat->s('recid', 'projektkezeles', "`dok_db_min_var`>'0'{$telephely_sql}");
            $link       = 'projektkezeles.php?mezo=dok_db_min_var&amp;op=equal&amp;felt=1&amp;modosit&amp;dashboard=Dokumentumok minősítése';
            
            $db = count($projektek);
            if( $db ) {
                $feladatok .= nl2('
                                    <tr>
                                        <td>
                                            <a href="'.$link.'">
                                                Dokumentumok minősítése
                                            </a>
                                        </td>
                                        <td>
                                            <a href="'.$link.'">
                                                '.number_format($db, 0, '', '.').' db
                                            </a>
                                        </td>
                                    </tr>
                                    ');
            }
        }
        
        
        
        
        if( $feladatok ) {
            $feladatok = '<table class="csikos full">'.$feladatok.'</table>';
        }
        
        $html .= nl2('
                    <div class="dashboard">
                        <div>
                            <div class="box">
                                <h2>Feladatok</h2>
                                <div class="padd">
                                    '.$feladatok.'
                                </div>
                            </div>
                        </div>
                        <div></div>
                        <div></div>
                        <div></div>
                    </div>
                    ');
        
        
    } else {
    
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

                // Nyitott bejelentéseknél a le kell vonni a lerejtett sorokat
                // Itt azt a feltételt kell lekezelni, mint ami a bejelentesLista függvényben is van
                if( _jog(JOG_MUNKALAP_KESZITHET) && $statusz['stat_id'] == BEJELENTES_STAT_NYITOTT ) {
                    $db = 0;

                    $bejelentesek = $adatbazis_kapcsolat->s('recid telephely_id', 'bejelentes', "`stat_id`='{$statusz['stat_id']}' AND `telephely_id` IN (".implode(',', $szallodak)."){$sql_kieg}");
                    foreach( $bejelentesek as $bejelentes ) {
                        $szalloda  = $adatbazis_kapcsolat->s2('modell', 'telephely', "`id`='{$bejelentes['telephely_id']}'");

                        $rejtsuk = false;
                        if( !$szalloda['modell'] ) {
                            $van_kapcs = $adatbazis_kapcsolat->s('munkalap_id', 'munkalap_kapcs', "`bejelentes_id`='{$bejelentes['recid']}'");
                            if( count($van_kapcs) == 1 ) {
                                $letezik_munkalap = $adatbazis_kapcsolat->s2('recid', 'munkalap', "`recid`='{$van_kapcs[0]['munkalap_id']}'");
                                if( $letezik_munkalap ) {
                                    $statuszok = $adatbazis_kapcsolat->s('recid', 'munkalap_statusz', "`munkalap_recid`='{$letezik_munkalap['recid']}'");
                                    if( count($statuszok) == 2 ) {
                                        $rejtsuk = true;
                                    }
                                }
                            }
                        }
                        if( !$rejtsuk ) {
                            $db++;
                        }
                    }

                // Általános eset
                } else {
                    $db = $adatbazis_kapcsolat->s('recid', 'bejelentes', "`stat_id`='{$statusz['stat_id']}' AND `telephely_id` IN (".implode(',', $szallodak)."){$sql_kieg}", '', '', '', 'Num rows');
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
                    $sql_kieg   = " AND `jh1_szerep`!='".SZALLODA_IGAZGATO."' AND `jh2_szerep`!='".SZALLODA_IGAZGATO."' AND `jh_ert`<='{$login['jh_ft_ig']}'";
                }
                if( $elozo_stat == $statusz['stat_id'] ) {
                    $fgv_kieg   = '2';
                    $link_kieg  = '&amp;dv=mszk_vez';
                    $sql_kieg   = " AND `jh1_szerep`!='".MSZK_VEZETO."' AND `jh2_szerep`!='".MSZK_VEZETO."' AND `jh_ert`<='{$login['jh_ft_ig']}'";
                }
                $elozo_stat = $statusz['stat_id'];

                $db = $adatbazis_kapcsolat->s('recid', $tabla, "`stat_id`='".$statusz['stat_id']."' AND {$where}{$sql_kieg}", '', '', '', 'Num rows');

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