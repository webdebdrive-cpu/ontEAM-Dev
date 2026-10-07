<?php
if( $_GET['mezo']=='legfrissebb' && $_GET['felt']=='-' ) {
    $_GET['mezo'] = $_GET['op'] = $_GET['felt'] = '';
}

// Ez a tömb kell majd az alábbi működésekhez
$szamla_modulok = array('anyag_szamla', 'energia_szamla', 'fenntartasi_szamla', 'projektszamla');

// Operátor kiválasztása
if( isset($_POST['f_szuro_mezo']) ) {

    $akt_tomb_szures = get_akt_tomb($_POST['tabla']);

    $selected_equal = '';
    $selected_like  = '';
    $selected_empty = '';
    if($_POST['selected']=='equal'      ) { $selected_equal      = ' selected'; }
    if($_POST['selected']=='like'       ) { $selected_like       = ' selected'; }
    if($_POST['selected']=='empty'      ) { $selected_empty      = ' selected'; }
    if($_POST['selected']=='gteq'       ) { $selected_gteq       = ' selected'; }
    if($_POST['selected']=='lteq'       ) { $selected_lteq       = ' selected'; }
    if($_POST['selected']=='gt'         ) { $selected_gt         = ' selected'; }
    if($_POST['selected']=='lt'         ) { $selected_lt         = ' selected'; }
    if($_POST['selected']=='not_eq'     ) { $selected_not_eq     = ' selected'; }

    // A tömbben előforduló selectek tömbje. Innen tudjuk hogy csak két operátor tartozhat hozzá
    $selectek = array(
        'select_cikltip',
        'select_besz_tipus',
        'select_eszkoz_prior',
        'select_felh_telephely',
        'select_fiz_mod',
        'select_hat_ido',
        'select_nyelv',
        'select_ho_ism',
        'select_ho_soran',
        'select_honapjan',
        'select_szemelyzet',
        'select_szerzodes_tipus',
        'select_pri_tipus',
        'select_pri_besorol',
    );

    $operator = '';
    // Feltétel összerakása tömbből
    foreach ( $akt_tomb_szures as $ertek ) {

        if( $ertek['name'] == $_POST['mezo'] ) {
            

            // Checkbox esetén Igen/Nem
            if( $ertek['type'] == 'checkbox' ) {

                $operator .= '<option value="equal"'.$selected_equal.'>=</option>';

            // Fizetési mód a számla modulok alatt
            } elseif( $ertek['name'] == 'fizmod' && in_array($_POST['tabla'], $szamla_modulok) ) {
                
                $operator .= '<option value="equal"'.$selected_equal.'>=</option>';

            // Dátum mező esetén
            } elseif( strpos($ertek['class'], 'datepicker') !== false || strpos($ertek['class'], 'datetimepicker') !== false || strpos($ertek['class'], 'datepicker_tegnapig') !== false || strpos($ertek['class'], 'datepicker_matol') !== false || strpos($ertek['class'], 'ora_perc') !== false || strpos($ertek['class'], 'datumos_szureshez') !== false ) {

                $operator .= '<option value="equal"'.$selected_equal.'>=</option>';
                $operator .= '<option value="gteq"'.$selected_gteq.'>&gt;=</option>';
                $operator .= '<option value="gt"'.$selected_gt.'>&gt;</option>';
                $operator .= '<option value="lteq"'.$selected_lteq.'>&lt;=</option>';
                $operator .= '<option value="lt"'.$selected_lt.'>&lt;</option>';
                $operator .= '<option value="not_eq"'.$selected_not_eq.'>&lt;&gt;</option>';
                $operator .= '<option value="empty"'.$selected_empty.'>Üres</option>';

            // Autocomplete esetén a switch.php alapján gyártunk egy inputot
            } elseif( $ertek['type'] == 'autocomplete' || $ertek['type_szurohoz'] == 'autocomplete' ) {

                $operator .= '<option value="equal"'.$selected_equal.'>=</option>';
                $operator .= '<option value="not_eq"'.$selected_not_eq.'>&lt;&gt;</option>';
                $operator .= '<option value="empty"'.$selected_empty.'>Üres</option>';

            // selectek esetén csak = vagy üres operátort ajánlunk fel
            } elseif( in_array($ertek['type'], $selectek) ) {

                $operator .= '<option value="equal"'.$selected_equal.'>=</option>';
                $operator .= '<option value="not_eq"'.$selected_not_eq.'>&lt;&gt;</option>';
                $operator .= '<option value="empty"'.$selected_empty.'>Üres</option>';

            // Általános esetben egy szabadszavas mező
            } else {

                // Numerikus adatok operátora
                if( strpos($ertek['class'], 'f_num') !== false || strpos($ertek['class'], 'datepicker') !== false ) {
                    $operator .= '<option value="equal"'.$selected_equal.'>=</option>';
                    $operator .= '<option value="gteq"'.$selected_gteq.'>&gt;=</option>';
                    $operator .= '<option value="lteq"'.$selected_lteq.'>&lt;=</option>';
                    $operator .= '<option value="gt"'.$selected_gt.'>&gt;</option>';
                    $operator .= '<option value="lt"'.$selected_lt.'>&lt;</option>';
                    $operator .= '<option value="not_eq"'.$selected_not_eq.'>&lt;&gt;</option>';
                    $operator .= '<option value="empty"'.$selected_empty.'>Üres</option>';
                    
                // Ha az események tömbnél a szűrésnél szöveges operátornak jelöltünk egy mezőt, akkor csak ennek megfelelő operátorokat hozzunk fel
                } elseif( $ertek['szuro'] == 'szoveges_op' ) {
                    $operator .= '<option value="equal"'.$selected_equal.'>=</option>';
                    $operator .= '<option value="like"'.$selected_like.'>Tartalmazza</option>';
                    $operator .= '<option value="not_eq"'.$selected_not_eq.'>&lt;&gt;</option>';
                    $operator .= '<option value="empty"'.$selected_empty.'>Üres</option>';
                } else {
                    $operator .= '<option value="equal"'.$selected_equal.'>=</option>';
                    $operator .= '<option value="like"'.$selected_like.'>Tartalmazza</option>';
                    $operator .= '<option value="gteq"'.$selected_gteq.'>&gt;=</option>';
                    $operator .= '<option value="lteq"'.$selected_lteq.'>&lt;=</option>';
                    $operator .= '<option value="gt"'.$selected_gt.'>&gt;</option>';
                    $operator .= '<option value="lt"'.$selected_lt.'>&lt;</option>';
                    $operator .= '<option value="not_eq"'.$selected_not_eq.'>&lt;&gt;</option>';
                    $operator .= '<option value="empty"'.$selected_empty.'>Üres</option>';
                }

            }
            break;
        }
        
    }
    
    // Pár egyedi eset, amikor kapcsolódó tábla mezőjére keresünk
    if( $_POST['tabla'] == 'fenntartasi_szerzodes' || $_POST['tabla'] == 'megallapodas' ) {
        if( $_POST['mezo'] == 'tetel_targy' ) {
            $operator .= '<option value="equal"'.$selected_equal.'>=</option>';
            $operator .= '<option value="like"'.$selected_like.'>Tartalmazza</option>';
            $operator .= '<option value="not_eq"'.$selected_not_eq.'>&lt;&gt;</option>';
            $operator .= '<option value="empty"'.$selected_empty.'>Üres</option>';
        }
        if( $_POST['mezo'] == 'varh_erk' || $_POST['mezo'] == 'telj_dt' || $_POST['mezo'] == 'varh_ert' || $_POST['mezo'] == 'dev_varh_ert' || $_POST['mezo'] == 'netto_ert' || $_POST['mezo'] == 'dev_net' ) {
            $operator .= '<option value="equal"'.$selected_equal.'>=</option>';
            $operator .= '<option value="not_eq"'.$selected_not_eq.'>&lt;&gt;</option>';
            $operator .= '<option value="gteq"'.$selected_gteq.'>&gt;=</option>';
            $operator .= '<option value="lteq"'.$selected_lteq.'>&lt;=</option>';
            $operator .= '<option value="gt"'.$selected_gt.'>&gt;</option>';
            $operator .= '<option value="lt"'.$selected_lt.'>&lt;</option>';
        }

    }
    
    // Anyag, Energia, Fenntartási, Megállapodás vagy Projekt számláknál pár mezőnél csak egyenlőség jel van
    $equal_tablak = array('anyag_szamla', 'energia_szamla', 'fenntartasi_szamla', 'megallapodas_szamla', 'projektszamla');
    $equal_mezok  = array('feldolgozva', 'elo_igazolva', 'igazolva', 'utalvanyozva', 'sztornozva');
    if( in_array($_POST['tabla'], $equal_tablak) && in_array($_POST['mezo'], $equal_mezok) ) {
        $operator = '<option value="equal"'.$selected_equal.'>=</option>';
    }
    // Projektszámláknál a "kifizetés van-e" is csak Equal lehet
    if( $_POST['tabla'] == 'projektszamla' && $_POST['mezo'] == 'kifizetes_van_e' ) {
        $operator = '<option value="equal"'.$selected_equal.'>=</option>';
    }

    // Kapcsolódó szállodák felsorolása
    if( $_POST['mezo']=='telephely_id' || ($_POST['tabla'] == 'login' && $_POST['mezo']=='szalloda') ) {

        // Ilyenkor felülírva az operátorokat, csak egyenlőségjel van
        $operator  = '<option value="equal"'.$selected_equal.'>=</option>';
        $operator .= '<option value="not_eq"'.$selected_not_eq.'>&lt;&gt;</option>';
        if( $_POST['tabla'] != 'fenntartasi_szerzodes' && $_POST['tabla'] != 'megallapodas' ) {
            $operator .= '<option value="empty"'.$selected_empty.'>Üres</option>';
        }

    }

    // Értéklista csoport
    if( $_POST['tabla'] == 'erteklista' && ($_POST['mezo'] == 'csoport' || $_POST['mezo'] == 'fix_adat') ) {
        $operator = '<option value="equal"'.$selected_equal.'>=</option>';
    }

    // Beosztás hónap választásnál csak egyenlőség van
    if( $_POST['tabla'] == 'anyagigenyles' && $_POST['mezo'] == 'cikkmee' ) {
        $operator  = '<option value="equal"'.$selected_equal.'>=</option>';
        $operator .= '<option value="like"'.$selected_like.'>Tartalmazza</option>';
        $operator .= '<option value="not_eq"'.$selected_not_eq.'>&lt;&gt;</option>';
        $operator .= '<option value="empty"'.$selected_empty.'>Üres</option>';
    }

    // Beosztás hónap választásnál csak egyenlőség van
    if( $_POST['tabla'] == 'beosztas' && $_POST['mezo'] == 'ho' ) {
        $operator  = '<option value="equal"'.$selected_equal.'>=</option>';
        $operator .= '<option value="not_eq"'.$selected_not_eq.'>&lt;&gt;</option>';
    }

    // Projektszerződésnél a jótállás van-e szűrés
    if( $_POST['tabla'] == 'projektszerzodes' && $_POST['mezo'] == 'jotallas_van_e' ) {
        $operator = '<option value="equal"'.$selected_equal.'>=</option>';
    }

    // Létrehoztam mező esetén csak egyenlőség jel van
    if( $_POST['mezo'] == 'insert_usr' ) {
        $operator  = '<option value="equal"'.$selected_equal.'>=</option>';
        $operator .= '<option value="not_eq"'.$selected_not_eq.'>&lt;&gt;</option>';
        $operator .= '<option value="empty"'.$selected_empty.'>Üres</option>';
    }

    // Legfrissebb esetén = operátor
    if( $_POST['mezo']=='legfrissebb' || $_POST['mezo']=='dok_van_e' || $_POST['mezo']=='dok_db_min_var' || $_POST['mezo']=='kapcsolt_szerzodes_van_e' || $_POST['mezo']=='kapcsolt_szamla_van_e' ) {
        $operator = '<option value="equal"'.$selected_equal.'>=</option>';
    }

    // Megjegyzés esetén tartalmazza operátor + szabadszavas mező
    if( $_POST['mezo'] == 'megjegyzes' ) {
        $operator .= '<option value="like"'.$selected_like.'>Tartalmazza</option>';
    }

    // MSZK jóváhagyta operátora
    if( $_POST['mezo'] == 'mszk_jovahagy' ) {
        $operator .= '<option value="equal"'.$selected_equal.'>=</option>';
    }

    // Szakterület esetén = operátor + autocomplete
    if( $_POST['mezo'] == 'szakterulet' ) {
        $operator .= '<option value="equal"'.$selected_equal.'>=</option>';
    }

    // Team tag operátora
    if( $_POST['mezo'] == 'team_tag' ) {
        $operator .= '<option value="equal"'.$selected_equal.'>=</option>';
    }

    // Jogosultságnál, Státusznál és Mobilcsoportnál, Dashboradnál csak =
    if( $_POST['mezo'] == 'jogosultsag' || $_POST['mezo'] == 'stat_id' || $_POST['mezo'] == 'dashboard_feladat' || ( $_POST['mezo'] == 'mobil' && $_POST['tabla'] == 'munkalap' ) ) {
        $operator  = '<option value="equal"'.$selected_equal.'>=</option>';
        $operator .= '<option value="not_eq"'.$selected_not_eq.'>&lt;&gt;</option>';
    }

    // Operátor select bezárása
    if( $operator ) {
        $operator = '<select class="f_szuro_mezo_operator" data-tabla="'.$_POST['tabla'].'">'.$operator.'</select>';
    }

    echo json_encode( array('operator' => $operator) );

    $weblap -> leallit();
    exit;
}

// Feltétel mező kigenerálása operátor váltáskor történik
if( isset($_POST['f_szuro_mezo_operator']) ) {

    $akt_tomb_szures = get_akt_tomb($_POST['tabla']);

    $selected_null = '';
    $selected_egy  = '';
    if( $_POST['felt'] == '0' ) {
        $selected_null = ' selected';
    }
    if( $_POST['felt'] == '1' ) {
        $selected_egy = ' selected';
    }

    if( $_POST['felt'] == 'undefined' ) {
        $_POST['felt'] = '';
    }

    // Ha az éppen kiválasztott operátor az üres, akkor az alábbit adjuk vissza és kész
    $igen_nem_mezok = array('dok_van_e', 'dok_db_min_var', 'feldolgozva', 'fix_adat', 'elo_igazolva', 'igazolva', 'kapcsolt_szerzodes_van_e', 'kapcsolt_szamla_van_e', 'jotallas_van_e', 'kifizetes_van_e', 'utalvanyozva', 'sztornozva');
    if( $_POST['selected'] == 'empty' || in_array($_POST['mezo'], $igen_nem_mezok) ) {
        $feltetel = nl2('
            <select class="f_felt">
                <option value="1"'.$selected_egy.'>Igen</option>
                <option value="0"'.$selected_null.'>Nem</option>
            </select>
            ');

    // Ha nem az üres operátort választottuk ki
    } else {

        foreach ( $akt_tomb_szures as $ertek ) {
            if( $ertek['name'] == $_POST['mezo'] ) {

                // Checkbox esetén Igen/Nem
                if( $ertek['type'] == 'checkbox' ) {

                    $feltetel = nl2('
                        <select class="f_felt">
                            <option value="1"'.$selected_egy.'>Igen</option>
                            <option value="0"'.$selected_null.'>Nem</option>
                        </select>
                        ');


                // Autocomplete esetén a switch.php alapján gyártunk egy inputot
                } elseif( $ertek['type'] == 'autocomplete' || $ertek['type_szurohoz'] == 'autocomplete' ) {

                    // Összetett szűrés esetén az autocomplete mezőnek más ID-t adunk
                    $osszetett = '';
                    if( $_POST['osszetett'] ) {
                        $osszetett = '_osszetett';
                    }
                    
                    require_once('../class/html/class.Input.php');
                    $input = new Input('text');

                    $input -> value = $_POST['felt'];

                    // Onblurra a hidden mező is nullázódjon, ha üres volna a mező
                    $ertek['onblur'] .= 'if(!$(this).val()){ $(\'#'.$ertek['name'].'\').val(\'\'); }';

                    if( $ertek['alert'] ) {
                        $ertek['class'] .= ' csillag';
                    }

                    $input -> cssClass      = $ertek['class'].' f_felt';
                    $input -> data          = 'data-auto="1"';
                    $input -> id            = $ertek['name'].'_autocomp_szurobe'.$osszetett;
                    $input -> name          = $ertek['name'].'_autocomp_szurobe';
                    $input -> placeholder   = $ertek['placeholder'];
                    $input -> autocomplete  = 'off';

                    // Ha van szélesség beállítva, akkor azt hozzuk ide is át, kivéve ha szallito_id a mezőneve
                    // (Projekt számla összetett keresénél túl rövid volt a Partner mező szélessége, ezért tesszük kivételbe a szélességet itt.)
                    if( strpos($ertek['style'], 'width') !== false && $ertek['name'] != 'szallito_id' ) {
                        $input -> style = $ertek['style'];
                    // egyébként 30rem-es
                    } else {
                        $input -> style = 'width: 30rem';
                    }

                    $kulso_val = $input -> value;
                    if( !$kulso_val ) {
                        $kulso_val = 0;
                    }

                    $csoport_where = '';
                    if( $ertek['csoport'] ) {
                        $csoport_where = ' and csoport='.$ertek['csoport'];
                    }

                    // Lekérdezzük a külső tábla értékét
                    $kulso_ertek = $adatbazis_kapcsolat->s($ertek['listaz'], $ertek['tabla'], $ertek['oszlop_keres'].'='.$kulso_val.$csoport_where, '', '1');
                    if( $kulso_ertek ) {
                        $input -> value = $kulso_ertek[0][$ertek['listaz']];
                    }

                    $feltetel = $input -> getHtml().'<input type="hidden" name="'.$ertek['name'].'_szurobe" id="'.$ertek['name'].'_szurobe" value="'.$_POST['felt'].'">';

                    $rkat = 0;
                    if( $ertek['rkat'] ) {
                        $rkat = $ertek['rkat'];
                    }

                    $csoport = 0;
                    if( $ertek['csoport'] ) {
                        $csoport = $ertek['csoport'];
                    }

                    $id_kiir = 'recid';
                    if( $ertek['id_kiir'] ) {
                        $id_kiir = $ertek['id_kiir'];
                    }
                    
                    // Projektfelelős esetén az autocompletenek további paramétereket adunk át
                    $aktiv          = '';
                    $beosztas_id    = '';
                    if( $ertek['name'] == 'pfelelos' || $ertek['name'] == 'let_fel' ) {
                        $aktiv          = 1;
                        $aktiv_szinez   = 1;
                        $beosztas_id    = EL_BEOSZTAS_BERUH_IG.','.EL_BEOSZTAS_FEJL_VIG_H.','.EL_BEOSZTAS_MUSZAKI_ASSZ.','.EL_BEOSZTAS_MUSZAKI_IG.','.EL_BEOSZTAS_PR_FELEOS.','.EL_BEOSZTAS_DISZ_KP_VEZ.','.EL_BEOSZTAS_TER_FELELOS.','.EL_BEOSZTAS_KP_MUSZ_BERUH_IG;
                    }
    
                    // Partner lenyílóban legyenek színezve az aktívak, inaktívak
                    if( $ertek['name'] == 'szallito_id' ) {
                        $aktiv_szinez   = 1;
                    }

                    $feltetel .= nl2('
                        <script>
                            $(document).ready(function() {
                                $(\'#'.$ertek['name'].'_autocomp_szurobe'.$osszetett.'\').autocomplete({
                                    \'php_fajl\'        : \'../autocomplete.php\',
                                    \'tabla\'           : \''.$ertek['tabla'].'\',
                                    \'oszlop_keres\'    : \''.$ertek['oszlop_keres_autocomp'].'\',
                                    \'oszlop_listaz\'   : \''.$ertek['listaz'].'\',
                                    \'rkat\'            : '.$rkat.',
                                    \'csoport\'         : '.$csoport.',
                                    \'id_kiir\'         : \''.$id_kiir.'\',
                                    \'aktiv\'           : \''.$aktiv.'\',
                                    \'aktiv_szinez\'    : \''.$aktiv_szinez.'\',
                                    \'beosztas_id\'     : \''.$beosztas_id.'\',
                                    \'szuros\'          : \'Szűrőben hívtuk meg\'
                                });
                            });
                        </script>
                    ');
                    
                // Fizetési mód a számla modulok alatt
                } elseif( $ertek['name'] == 'fizmod' && in_array($_POST['tabla'], $szamla_modulok) ) {
                    
                    $selected_u = $selected_k = '';
                    if( $_POST['felt'] == 'U' ) {
                        $selected_u = ' selected';
                    }
                    if( $_POST['felt'] == 'k' ) {
                        $selected_k = ' selected';
                    }
                    
                    $feltetel = nl2('
                        <select class="f_felt">
                            <option value="U"'.$selected_u.'>Utalás</option>
                            <option value="k"'.$selected_k.'>Készpénz</option>
                        </select>
                        ');
                    
                // select_szemelyzet
                } elseif( $ertek['type'] == 'select_szemelyzet' ) {

                    require_once('../class/html/class.Select.php');
                    require_once('selectek_szemelyzet.php');

                    $input = new Select($select_szemelyzet, $_POST['felt']);
                    $input -> cssClass = 'f_felt';
                    $feltetel = $input -> getHtml();

                // select_cikltip
                } elseif( $ertek['type'] == 'select_cikltip' ) {

                    require_once('../class/html/class.Select.php');
                    require_once('selectek.php');

                    $input = new Select($select_cikltip, $_POST['felt']);
                    $input -> cssClass = 'f_felt';
                    $feltetel = $input -> getHtml();

                // select_besz_tipus
                } elseif( $ertek['type'] == 'select_besz_tipus' ) {

                    require_once('../class/html/class.Select.php');
                    require_once('selectek.php');

                    $input = new Select($select_besz_tipus, $_POST['felt']);
                    $input -> cssClass = 'f_felt';
                    $feltetel = $input -> getHtml();

                // select_eszkoz_prior
                } elseif( $ertek['type'] == 'select_eszkoz_prior' ) {

                    require_once('../class/html/class.Select.php');
                    require_once('selectek.php');

                    $input = new Select($select_eszkoz_prior, $_POST['felt']);
                    $input -> cssClass = 'f_felt';
                    $feltetel = $input -> getHtml();

                // select_fiz_mod
                } elseif( $ertek['type'] == 'select_fiz_mod' ) {

                    require_once('../class/html/class.Select.php');
                    require_once('selectek.php');

                    $input = new Select($select_fiz_mod, $_POST['felt']);
                    $input -> cssClass = 'f_felt';
                    $feltetel = $input -> getHtml();

                // select_hat_ido
                } elseif( $ertek['type'] == 'select_hat_ido' ) {

                    require_once('../class/html/class.Select.php');
                    require_once('selectek.php');

                    $input = new Select($select_hat_ido, $_POST['felt']);
                    $input -> cssClass = 'f_felt';
                    $feltetel = $input -> getHtml();

                // select_nyelv
                } elseif( $ertek['type'] == 'select_nyelv' ) {

                    require_once('../class/html/class.Select.php');
                    require_once('selectek.php');

                    $input = new Select($select_nyelv, $_POST['felt'] ?? '');
                    $input -> cssClass = 'f_felt';
                    $feltetel = $input -> getHtml();

                // select_mobilcsoport
                } elseif( $ertek['type'] == 'select_mobilcsoport' ) {

                    require_once('../class/html/class.Select.php');
                    require_once('selectek.php');

                    $input = new Select($select_mobilcsoport, $_POST['felt']);
                    $input -> cssClass = 'f_felt';
                    $feltetel = $input -> getHtml();

                // select_pri_tipus
                }elseif( $ertek['type'] == 'select_szerzodes_tipus' ) {

                    require_once('../class/html/class.Select.php');
                    require_once('selectek.php');

                    $input = new Select($select_szerzodes_tipus, $_POST['felt']);
                    $input -> cssClass = 'f_felt';
                    $feltetel = $input -> getHtml();

                // select_pri_tipus
                }elseif( $ertek['type'] == 'select_pri_tipus' ) {

                    require_once('../class/html/class.Select.php');
                    require_once('selectek.php');

                    $input = new Select($select_pri_tipus, $_POST['felt']);
                    $input -> cssClass = 'f_felt';
                    $feltetel = $input -> getHtml();

                // select_pri_besorol
                } elseif( $_POST['mezo'] == 'mobil' ) {

                    require_once('../class/html/class.Select.php');
                    require_once('selectek.php');

                    $input = new Select($select_mobilcsoport, $_POST['felt']);
                    $input -> cssClass = 'f_felt';
                    $feltetel = $input -> getHtml();

                // select_pri_besorol
                } elseif( $ertek['type'] == 'select_pri_besorol' ) {

                    require_once('../class/html/class.Select.php');
                    require_once('selectek.php');

                    $input = new Select($select_pri_besorol, $_POST['felt']);
                    $input -> cssClass = 'f_felt';
                    $feltetel = $input -> getHtml();

                // Értéklista csoport select-je
                } elseif( $ertek['type'] == 'select_erteklista' ) {

                    require_once('../class/html/class.Select.php');
                    require_once('selectek.php');

                    $input = new Select($select_erteklista, $_POST['felt']);
                    $input -> cssClass = 'f_felt';
                    $feltetel = $input -> getHtml();


                // select_unnepnap_tipus
                }elseif( $ertek['type'] == 'select_unnepnap_tipus' ) {

                    require_once('../class/html/class.Select.php');
                    require_once('selectek.php');

                    $input = new Select($select_unnepnap_tipus, $_POST['felt']);
                    $input -> cssClass = 'f_felt';
                    $feltetel = $input -> getHtml();

                // select_honapok
                }elseif( $ertek['type'] == 'select_honapok' ) {

                    require_once('../class/html/class.Select.php');
                    require_once('selectek.php');

                    $input = new Select($select_honapok, $_POST['ho']);
                    $input -> cssClass = 'f_felt';
                    $feltetel = $input -> getHtml();
                    
                // Általános esetben egy szabadszavas mező
                } else {

                    // Numerikus adatok operátora
                    if( strpos($ertek['class'], 'f_num') !== false ) {
                        $feltetel = nl2('
                                            <input type="text" id="szures_f_num" placeholder="Érték..." class="f_num f_felt" '.$ertek['data'].' value="'.$_POST['felt'].'" style="min-width: 10rem" onfocus="$(this).select();">
                                            <script>
                                            $(document).ready(function () {
                                                $(\'#szures_f_num\').focus().blur();
                                            });
                                            </script>
                                        ');

                    // Dátum mezők (mindig csak dátum pontos ez a mező. 
                    } elseif( strpos($ertek['class'], 'datepicker') !== false || strpos($ertek['class'], 'datetimepicker') !== false ) {
                        // Létrehozás dátum mégis dátumidős
                        if( $_POST['mezo'] == 'insert_dt' ) {
                            $feltetel = nl2('
                                <input type="text" placeholder=" . . " class="datetimepicker f_felt" value="'.$_POST['felt'].'" style="width: 14rem">
                                <script>
                                    $(\'.datetimepicker\').datetimepicker({
                                        timepicker          : true,
                                        closeOnDateSelect   : true,
                                        mask                : \'\\xa0\\xa0\\xa0\\xa0.\\xa0\\xa0.\\xa0\\xa0\\xa0\\xa0:\\xa0\\xa0\',
                                        format              :\'Y.m.d H:i\',
                                        validateOnBlur      : false
                                    });
                                </script>
                            ');
                        // Általános dátum mező
                        } else {
                            $feltetel = nl2('
                                <input type="text" placeholder=" . . " class="datepicker f_felt" value="'.$_POST['felt'].'">
                                <script>
                                    $(\'.datepicker\').datetimepicker({
                                        timepicker          : false,
                                        closeOnDateSelect   : true,
                                        mask                : \'\\xa0\\xa0\\xa0\\xa0.\\xa0\\xa0.\\xa0\\xa0\',
                                        format              :\'Y.m.d\',
                                        validateOnBlur      : false
                                    });
                                </script>
                            ');
                        }
                        
                    // Óra perc mezők
                    } elseif( strpos($ertek['class'], 'ora_perc') !== false ) {
                        $feltetel = nl2('
                            <input type="text" placeholder=" : " class="ora_perc f_felt" value="'.$_POST['felt'].'">
                            <script>
                                $(\'.ora_perc\').datetimepicker({
                                    closeOnDateSelect   : true,
                                    datepicker          : false,
                                    format              :\'H:i\',
                                    mask                : \'\xa0\xa0:\xa0\xa0\',
                                    step                : 60,
                                    validateOnBlur      : false
                                });
                            </script>
                        ');
                    } else {
                        $feltetel = '<input type="text" placeholder="Feltétel..." class="f_felt" value="'.$_POST['felt'].'" onfocus="$(this).select()">';
                    }

                }
                break;
            }
        }

        // Kapcsolódó szállodák felsorolása
        if( $_POST['mezo']=='telephely_id' || $_POST['mezo']=='telephely_unid' || ($_POST['tabla']=='login' && $_POST['mezo']=='szalloda' ) ){

            $szalloda_azon = 'id';
            if( $_POST['mezo']=='telephely_unid' ) {
                $szalloda_azon = 'unid';
            }
            $feltetel  = '<select class="f_felt">';
            $feltetel .= '<option value="">-Válasszon szállodát-</option>';
            $telephelyek = $adatbazis_kapcsolat->s($szalloda_azon.' nev', 'telephely', trim(get_akt_login_szallodai('sql', 'id'), ' AND '), 'nev');
            foreach ( $telephelyek as $telephely ) {
                $selected = '';
                if( $telephely[$szalloda_azon] == $_POST['felt'] ) {
                    $selected = ' selected';
                }
                $feltetel .= '<option value="'.$telephely[$szalloda_azon].'"'.$selected.'>'.$telephely['nev'].'</option>';
            }
            $feltetel .= '</select>';

        }

        // insert_usr esetén
        if( $_POST['mezo'] == 'insert_usr' ) {

            $feltetel = nl2('
                    <input type="text" placeholder="Feltétel..." class="f_felt" id="insert_usr_autocomp_szurobe" name="insert_usr_autocomp_szurobe" value="'.$_POST['felt'].'" onfocus="$(this).select()">
                    <input type="hidden" id="insert_usr_szurobe" name="insert_usr_szurobe" value="">
                    <script>
                        $(document).ready(function() {
                            $(\'#insert_usr_autocomp_szurobe\').autocomplete({
                                \'php_fajl\'        : \'../autocomplete.php\',
                                \'tabla\'           : \'login\',
                                \'oszlop_keres\'    : \'teljes_nev\',
                                \'oszlop_listaz\'   : \'teljes_nev\',
                                \'id_kiir\'         : \'teljes_nev\',
                                \'aktiv_szinez\'    : 1,
                                \'akt_mezo\'        : \''.$ertek['name'].'\',
                                \'szuros\'          : \'Szűrőben hívtuk meg\',
                            });
                        });
                    </script>
                ');

        }

        // Megjegyzés esetén tartalmazza operátor + szabadszavas mező
        if( $_POST['mezo'] == 'megjegyzes' ) {

            $feltetel = nl2('
                    <input type="text" placeholder="Feltétel..." class="f_felt" value="'.$_POST['felt'].'" onfocus="$(this).select()">
                ');

        }

        // Megjegyzés esetén tartalmazza operátor + szabadszavas mező
        if( $_POST['mezo'] == 'mszk_jovahagy' ) {

            $feltetel = nl2('
                <select class="f_felt">
                    <option value="1"'.$selected_egy.'>Igen</option>
                    <option value="0"'.$selected_null.'>Nem</option>
                </select>
                ');

        }

        // Szakterület esetén = operátor + autocomplete
        if( $_POST['mezo'] == 'team_tag' ) {

            $ertek['tabla']                 = 'login';
            $ertek['oszlop_keres']          = 'recid';
            $ertek['oszlop_keres_autocomp'] = 'teljes_nev';
            $ertek['listaz']                = 'teljes_nev';

            require_once('../class/html/class.Input.php');
            $input = new Input('text');

            $input -> value = $_POST['felt'];

            // Onblurra a hidden mező is nullázódjon, ha üres volna a mező
            $ertek['onblur'] .= 'if(!$(this).val()){ $(\'#'.$ertek['name'].'\').val(\'\'); }';

            if($ertek['alert']){
                $ertek['class'] .= ' csillag';
            }

            $input -> cssClass      = $ertek['class'].' f_felt';
            $input -> data          = 'data-auto="1"';
            $input -> id            = $ertek['name'].'_autocomp_szurobe';
            $input -> name          = $ertek['name'].'_autocomp_szurobe';
            $input -> placeholder   = $ertek['placeholder'];
            $input -> autocomplete  = 'off';

            $kulso_val = $input -> value;
            if( !$kulso_val ) {
                $kulso_val = 0;
            }

            $csoport_where = '';
            if( $ertek['csoport'] ) {
                $csoport_where = ' and csoport='.$ertek['csoport'];
            }

            // Lekérdezzük a külső tábla értékét
            $kulso_ertek = $adatbazis_kapcsolat->s($ertek['listaz'], $ertek['tabla'], $ertek['oszlop_keres'].'='.$kulso_val.$csoport_where, '', '1');
            if( $kulso_ertek ) {
                $input -> value = $kulso_ertek[0][$ertek['listaz']];
            }

            $feltetel = $input -> getHtml().'<input type="hidden" name="'.$ertek['name'].'_szurobe" id="'.$ertek['name'].'_szurobe" value="'.$_POST['felt'].'">';

            $rkat = 0;
            if( $ertek['rkat'] ) {
                $rkat = $ertek['rkat'];
            }

            $csoport = 0;
            if( $ertek['csoport'] ) {
                $csoport = $ertek['csoport'];
            }

            $id_kiir = 'recid';
            if( $ertek['id_kiir'] ) {
                $id_kiir = $ertek['id_kiir'];
            }

            $feltetel .= nl2('
                <script>
                    $(document).ready(function() {
                        $(\'#'.$ertek['name'].'_autocomp_szurobe\').autocomplete({
                            \'php_fajl\'        : \'../autocomplete.php\',
                            \'tabla\'           : \''.$ertek['tabla'].'\',
                            \'oszlop_keres\'    : \''.$ertek['oszlop_keres_autocomp'].'\',
                            \'oszlop_listaz\'   : \''.$ertek['listaz'].'\',
                            \'rkat\'            : '.$rkat.',
                            \'csoport\'         : '.$csoport.',
                            \'id_kiir\'         : \''.$id_kiir.'\',
                            \'szuros\'          : \'Szűrőben hívtuk meg\'
                        });
                    });
                </script>
            ');

        }

        // Szakterület esetén = operátor + autocomplete
        if( $_POST['mezo'] == 'szakterulet' ) {

            $ertek['tabla']                 = 'erteklista';
            $ertek['oszlop_keres']          = 'recid';
            $ertek['oszlop_keres_autocomp'] = 'info';
            $ertek['listaz']                = 'info';
            $ertek['csoport']               = ERTEKLISTA_SZAKTER;

            require_once('../class/html/class.Input.php');
            $input = new Input('text');

            $input -> value = $_POST['felt'];

            // Onblurra a hidden mező is nullázódjon, ha üres volna a mező
            $ertek['onblur'] .= 'if(!$(this).val()){ $(\'#'.$ertek['name'].'\').val(\'\'); }';

            if($ertek['alert']){
                $ertek['class'] .= ' csillag';
            }

            $input -> cssClass      = $ertek['class'].' f_felt';
            $input -> data          = 'data-auto="1"';
            $input -> id            = $ertek['name'].'_autocomp_szurobe';
            $input -> name          = $ertek['name'].'_autocomp_szurobe';
            $input -> placeholder   = $ertek['placeholder'];
            $input -> autocomplete  = 'off';

            $kulso_val = $input -> value;
            if( !$kulso_val ) {
                $kulso_val = 0;
            }

            $csoport_where = '';
            if( $ertek['csoport'] ) {
                $csoport_where = ' and csoport='.$ertek['csoport'];
            }

            // Lekérdezzük a külső tábla értékét
            $kulso_ertek = $adatbazis_kapcsolat->s($ertek['listaz'], $ertek['tabla'], $ertek['oszlop_keres'].'='.$kulso_val.$csoport_where, '', '1');
            if( $kulso_ertek ) {
                $input -> value = $kulso_ertek[0][$ertek['listaz']];
            }

            $feltetel = $input -> getHtml().'<input type="hidden" name="'.$ertek['name'].'_szurobe" id="'.$ertek['name'].'_szurobe" value="'.$_POST['felt'].'">';

            $rkat = 0;
            if( $ertek['rkat'] ) {
                $rkat = $ertek['rkat'];
            }

            $csoport = 0;
            if( $ertek['csoport'] ) {
                $csoport = $ertek['csoport'];
            }

            $id_kiir = 'recid';
            if( $ertek['id_kiir'] ) {
                $id_kiir = $ertek['id_kiir'];
            }

            $feltetel .= nl2('
                <script>
                    $(document).ready(function() {
                        $(\'#'.$ertek['name'].'_autocomp_szurobe\').autocomplete({
                            \'php_fajl\'        : \'../autocomplete.php\',
                            \'tabla\'           : \''.$ertek['tabla'].'\',
                            \'oszlop_keres\'    : \''.$ertek['oszlop_keres_autocomp'].'\',
                            \'oszlop_listaz\'   : \''.$ertek['listaz'].'\',
                            \'rkat\'            : '.$rkat.',
                            \'csoport\'         : '.$csoport.',
                            \'id_kiir\'         : \''.$id_kiir.'\',
                            \'szuros\'          : \'Szűrőben hívtuk meg\'
                        });
                    });
                </script>
            ');

        }

        // Jogosultság esetén
        if($_POST['mezo']=='jogosultsag'){

            require_once 'jogosultsag.php';

            $feltetel = '<select class="f_felt">';

            $jog_kat_i = 0;
            foreach($jogosultsagKatok as $jog_kat_kulcs => $jog_kat){
                $feltetel .= '<optgroup label="'.$jog_kat['nev'].'">';
                foreach($jogosultsagok as $jog_sor => $jog){
                    if($jog['kat']==$jog_kat_kulcs){

                        $selected = '';
                        if($jog_sor==$_POST['felt']){
                            $selected = ' selected';
                        }
                        $feltetel .= '<option value="'.$jog_sor.'"'.$selected.'>'.$jog['nev'].'</option>';
                    }
                }
                $feltetel .= '</optgroup>';
                $jog_kat_i++;
            }
            $feltetel .= '</select>';

        }

        // Státusz esetén
        if( $_POST['mezo'] == 'stat_id' ) {

            // Anyagigénylések/anyagrendelések alatt a státuszok listáját limitáljuk csak azokra, ami adott felületnél értelmezett
            $csak_ezeket_listazd = [];
            if( $_POST['tabla'] == 'anyagigenyles' ) {
                $csak_ezeket_listazd = [CIKKIGN_STAT_NYITOTT, CIKKIGN_STAT_ARAZVA, CIKKIGN_STAT_JOVAHAGYVA, CIKKIGN_STAT_VARAKOZO];
                if( isset($_GET['anyagrend']) ) {
                    $csak_ezeket_listazd = [CIKKIGN_STAT_BEERKEZETT, CIKKIGN_STAT_RESZBENBEERK, CIKKIGN_STAT_TOROLT, CIKKIGN_STAT_ELUTASITVA];
                
                }
            }

            $feltetel = '<select class="f_felt">';
            $feltetel .= '<option value="">-Válasszon státuszt-</option>';
            foreach ( $_stat_text[$_POST['tabla']] as $kulcs => $statusz ) {

                if( !$csak_ezeket_listazd || in_array($kulcs, $csak_ezeket_listazd) ) {
                    $selected = '';
                    if( $_POST['felt'] == $kulcs ) {
                        $selected = ' selected';
                    }
                    $feltetel .= '<option value="'.$kulcs.'"'.$selected.'>'.$statusz['nev'].'</option>';
                }
            }
            $feltetel .= '</select>';

        }

        // Legfrissebb esetén
        if( $_POST['mezo'] == 'legfrissebb' ) {
            $feltetel .= '<input type="text" placeholder="Feltétel..." class="f_felt" value="'.$_POST['felt'].'" onfocus="$(this).select()">';
        }

        // Dasboard esetén
        if( $_POST['mezo'] == 'dashboard_feladat' ) {
            $feltetel .= '<input type="text" placeholder="Feltétel..." class="f_felt" value="'.$_SESSION['dashboard_feladat'].'">';
        }

    }
    
    // Pár egyedi eset, amikor kapcsolódó tábla mezőjére keresünk
    if( ($_POST['tabla'] == 'fenntartasi_szerzodes' || $_POST['tabla'] == 'megallapodas' ) && $_POST['selected'] != 'empty' ) {
        if( $_POST['mezo'] == 'tetel_targy' ) {
            $feltetel = '<input type="text" placeholder="Feltétel..." class="f_felt" value="'.$_POST['felt'].'" onfocus="$(this).select()">';
        }
        if( $_POST['mezo'] == 'varh_erk' || $_POST['mezo'] == 'telj_dt' ) {
            $feltetel = nl2('
                <input type="text" placeholder=" . . " class="datetimepicker f_felt" value="'.$_POST['felt'].'" style="width: 14rem">
                <script>
                    $(\'.datetimepicker\').datetimepicker({
                        timepicker          : false,
                        closeOnDateSelect   : true,
                        mask                : \'\\xa0\\xa0\\xa0\\xa0.\\xa0\\xa0.\\xa0\\xa0\',
                        format              :\'Y.m.d\',
                        validateOnBlur      : false
                    });
                </script>
            ');
        }
        if( $_POST['mezo'] == 'varh_ert' || $_POST['mezo'] == 'netto_ert' ) {
            $feltetel = nl2('
                                <input type="text" id="szures_f_num" placeholder="Érték..." class="f_num f_felt" '.$ertek['data'].' value="'.$_POST['felt'].'" style="min-width: 10rem" onfocus="$(this).select();">
                                <script>
                                $(document).ready(function () {
                                    $(\'#szures_f_num\').focus().blur();
                                });
                                </script>
                            ');
        }
    }

    // Szűrés gomb rejtése, ha Dashbosard feladat szűrés van beállítva
    $link_kieg   = '';
    $szures_gomb = '<input type="button" class="gomb f_szures_submit" value="Szűrés">';
    if( $_SESSION['dashboard_feladat'] ) {
        $link_kieg   .= 'dashboard_feladat_torol';
        $szures_gomb = '';
    }
    
    // Eszkozigénylésnél a táblázatos nézet töltsön be
    if( $_POST['tabla'] == 'eszkozigenyles' ) {
        $link_kieg   .= '&amp;tablazat';
    }
    
    echo json_encode( array(
                            'feltetel'      => $feltetel,
                            'szures_gomb'   => $szures_gomb,
                            'link_kieg'     => $link_kieg
                        )
                    );

    $weblap -> leallit();
    exit;
}


// Melyik oldalon vagyunk: annak megfelelő tömbbel dolgozunk. Szűrhető mezők listája.
$tabla = str_replace('.php', '', basename(self()));
$akt_tomb_szures = get_akt_tomb($tabla);

// A szűrési mezőket egy tömbbe vesszük fel, mert a 2. felében lesz az option megnevezése, ami alapján rendezzük a listát
$sz_mezo = array();
foreach ( $akt_tomb_szures as $ertek ) {
    if( $ertek['szuro'] ) {
        if( $ertek['megn_szurobe'] ) {
            $ertek['megn'] = $ertek['megn_szurobe'];
        }
        $selected = '';
        if( $ertek['name'] == $_GET['mezo'] ) {
            $selected = ' selected';
        }
        $ertek['megn'] = strip_tags( str_replace('<br>', ' ', $ertek['megn']));
        array_push( $sz_mezo, array('eleje' => '<option value="'.$ertek['name'].'"'.$selected.'>', 'vege' => trim($ertek['megn'], ':').'</option>' ) );
    }
}

// Dokumentumos felületeken tudni kell szűrni arra is, hogy van-e dokumentuma
$dokumentumos_oldal = array(
    'anyagigenyles',
    'bejelentes',
    'bejelentes_arch',
    'besz_idoszak',
    'eloterj_bod',
    'eloterj_fb',
    'energia_terv',
    'eseti_fenntartas',
    'eszkozigenyles',
    'eszkozigenyles_megr',
    'fenntartasi_terv',
    'munkalap',
    'munkalap_arch',
    'munkalap_email',
    'projektigenyles',
    'projektkezeles',
    'projektszamla',
    'projektszerzodes',
    'szallito',
    'szallito_beszerzes',
    'rendszeres_karbantartas',
    'utemezett_feladatok',
    );
if( in_array($tabla, $dokumentumos_oldal) ) {
    $selected = '';
    if( $_GET['mezo'] == 'dok_van_e' ) {
        $selected = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="dok_van_e"'.$selected.'>', 'vege' => 'Van csatolt dokumentum</option>' ) );
}

// Kapcsolódó táblák szűrő mezői
$megjegyzesekre_szuros_mdulok = array('anyagigenyles', 'anyag_szamla', 'energia_szamla', 'fenntartasi_szamla', 'projektszamla', 'fenntartasi_szerzodes', 'projektszerzodes', 'megallapodas', 'szallito', 'szallito_beszerzes');
if( in_array($tabla, $megjegyzesekre_szuros_mdulok) ) {
    $selected_megj  = '';
    if( $_GET['mezo'] == 'megjegyzes' ) {
        $selected_megj = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="megjegyzes" data-tabla="megjegyzes"'.$selected_megj.'>', 'vege' => 'Megjegyzések</option>' ) );
}
if( $tabla == 'fenntartasi_szerzodes' ) {
    $selected = '';
    if( $_GET['mezo'] == 'telephely_id' ) {
        $selected = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="telephely_id" data-tabla="fenntartasi_szerzodes_tetel"'.$selected.'>', 'vege' => 'Tétel - Szálloda</option>' ) );
    $selected = '';
    if( $_GET['mezo'] == 'tetel_targy' ) {
        $selected = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="tetel_targy" data-tabla="fenntartasi_szerzodes_tetel"'.$selected.'>', 'vege' => 'Tétel - Tárgy</option>' ) );
    $selected = '';
    if( $_GET['mezo'] == 'varh_erk' ) {
        $selected = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="varh_erk" data-tabla="fenntartasi_szerzodes_tetel"'.$selected.'>', 'vege' => 'Tétel - Számla várh. érk.</option>' ) );
    $selected = '';
    if( $_GET['mezo'] == 'varh_ert' ) {
        $selected = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="varh_ert" data-tabla="fenntartasi_szerzodes_tetel"'.$selected.'>', 'vege' => 'Tétel - Számla várh. értéke</option>' ) );
    $selected = '';
    if( $_GET['mezo'] == 'dev_varh_ert' ) {
        $selected = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="dev_varh_ert" data-tabla="fenntartasi_szerzodes_tetel"'.$selected.'>', 'vege' => 'Tétel - Számla várh. értéke (deviza)</option>' ) );
    $selected = '';
    if( $_GET['mezo'] == 'telj_dt' ) {
        $selected = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="telj_dt" data-tabla="fenntartasi_szerzodes_tetel"'.$selected.'>', 'vege' => 'Tétel - Teljesítés</option>' ) );
    $selected = '';
    if( $_GET['mezo'] == 'netto_ert' ) {
        $selected = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="netto_ert" data-tabla="fenntartasi_szerzodes_tetel"'.$selected.'>', 'vege' => 'Tétel - Érték</option>' ) );
    $selected = '';
    if( $_GET['mezo'] == 'dev_net' ) {
        $selected = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="dev_net" data-tabla="fenntartasi_szerzodes_tetel"'.$selected.'>', 'vege' => 'Tétel - Éték (deviza)</option>' ) );
}
if( $tabla == 'megallapodas' ) {
    $selected = '';
    if( $_GET['mezo'] == 'telephely_id' ) {
        $selected = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="telephely_id" data-tabla="megallapodas_tetel"'.$selected.'>', 'vege' => 'Tétel - Szálloda</option>' ) );
    $selected = '';
    if( $_GET['mezo'] == 'tetel_targy' ) {
        $selected = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="tetel_targy" data-tabla="megallapodas_tetel"'.$selected.'>', 'vege' => 'Tétel - Tárgy</option>' ) );
    $selected = '';
    if( $_GET['mezo'] == 'varh_erk' ) {
        $selected = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="varh_erk" data-tabla="megallapodas_tetel"'.$selected.'>', 'vege' => 'Tétel - Számla várh. érk.</option>' ) );
    $selected = '';
    if( $_GET['mezo'] == 'varh_ert' ) {
        $selected = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="varh_ert" data-tabla="megallapodas_tetel"'.$selected.'>', 'vege' => 'Tétel - Számla várh. értéke</option>' ) );
    $selected = '';
    if( $_GET['mezo'] == 'dev_varh_ert' ) {
        $selected = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="dev_varh_ert" data-tabla="megallapodas_tetel"'.$selected.'>', 'vege' => 'Tétel - Számla várh. értéke (deviza)</option>' ) );
    $selected = '';
    if( $_GET['mezo'] == 'telj_dt' ) {
        $selected = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="telj_dt" data-tabla="megallapodas_tetel"'.$selected.'>', 'vege' => 'Tétel - Teljesítés</option>' ) );
    $selected = '';
    if( $_GET['mezo'] == 'netto_ert' ) {
        $selected = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="netto_ert" data-tabla="megallapodas_tetel"'.$selected.'>', 'vege' => 'Tétel - Érték</option>' ) );
    $selected = '';
    if( $_GET['mezo'] == 'dev_net' ) {
        $selected = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="dev_net" data-tabla="megallapodas_tetel"'.$selected.'>', 'vege' => 'Tétel - Éték (deviza)</option>' ) );
}

// Vannak felületek, ahol a nav panel számát limitáljuk. Ezt egy szűrővel oldjuk meg, tehát itt szükséges, hogy legyen egy Legfrisebb nevű szűrés lehetőség.
$legfrisebb_feluletek = array('anyagigenyles', 'anyag_szamla', 'bejelentes_arch', 'energia_szamla', 'eszkozigenyles_arch', 'eszkozigenyles_megr_arch', 'fenntartasi_szamla', 'fenntartasi_szerzodes', 'megallapodas', 'megallapodas_szamla', 'munkalap_arch', 'projektigenyles', 'projektkezeles', 'projektszamla', 'projektszerzodes', 'vendegejszakak');
if( in_array($tabla, $legfrisebb_feluletek) ) {
    $selected_legfrissebb  = '';
    if( $_GET['mezo'] == 'legfrissebb' && $_GET['felt']/* Tehát nem 0 sorra van szűkítve, ez egy speciális eset anyagrendeléseknél */ ) {
        $selected_legfrissebb = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="legfrissebb"'.$selected_legfrissebb.'>', 'vege' => 'Legfrissebb</option>' ) );
}
if( $tabla == 'munkalap' ) {
    $selected_mszk_jovahagy  = '';
    if($_GET['mezo']=='mszk_jovahagy'){
        $selected_mszk_jovahagy = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="mszk_jovahagy"'.$selected_mszk_jovahagy.'>', 'vege' => 'MSZK jóváhagyta</option>' ) );
}
if( $tabla == 'login' ) {
    $selected_jog   = '';
    $selected_szall = '';
    $selected_megj  = '';
    if( $_GET['mezo'] == 'jogosultsag' ) {
        $selected_jog = ' selected';
    }
    if( $_GET['mezo'] == 'szalloda' ) {
        $selected_szall = ' selected';
    }
    if( $_GET['mezo'] == 'megjegyzes' ) {
        $selected_megj = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="jogosultsag"'.$selected_jog.'>',                         'vege' => 'Jogosultság</option>'    ) );
    array_push( $sz_mezo, array('eleje' => '<option value="szalloda" data-tabla="telephely"'.$selected_szall.'>',   'vege' => 'Szálloda</option>'       ) );
    array_push( $sz_mezo, array('eleje' => '<option value="megjegyzes" data-tabla="megjegyzes"'.$selected_megj.'>', 'vege' => 'Megjegyzés</option>'     ) );
}
// Értéklisták esetén kell még egy szűrés: fix adat
if( $tabla == 'erteklista' ) {
    $selected_fix_adat  = '';
    if( $_GET['mezo'] == 'fix_adat' ) {
        $selected_fix_adat = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="fix_adat" data-tabla="fix_adat"'.$selected_fix_adat.'>', 'vege' => get_nev().' kezeli</option>' ) );
}

// Projektkezelés esetén a Team tag nevére is kell tudni szűrni
if( $tabla == 'projektkezeles' ) {
    $selected_team_tag  = '';
    if( $_GET['mezo'] == 'team_tag' ) {
        $selected_team_tag = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="team_tag"'.$selected_team_tag.'>', 'vege' => 'Team tag</option>' ) );
}
// Projektszerződésnél szűrni kell tudni, hogy van-e jótállás
if( $tabla == 'projektszerzodes' ) {
    $selected_jotallas  = '';
    if( $_GET['mezo'] == 'jotallas_van_e' ) {
        $selected_jotallas = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="jotallas_van_e"'.$selected_jotallas.'>', 'vege' => 'Van jótállás</option>' ) );
}
// Projektszámlánál szűrni kell tudni, hogy van-e kifizetés
if( $tabla == 'projektszamla' ) {
    $selected_kifizetes  = '';
    if( $_GET['mezo'] == 'kifizetes_van_e' ) {
        $selected_kifizetes = ' selected';
    }
    array_push( $sz_mezo, array('eleje' => '<option value="kifizetes_van_e"'.$selected_kifizetes.'>', 'vege' => 'Van kifizetés</option>' ) );
}
if( $tabla == 'szallito' ) {
    $selected_szakter   = '';
    // $selected_megj      = '';
    if( $_GET['mezo'] == 'szakterulet' ) {
        $selected_szakter = ' selected';
    }
    // if( $_GET['mezo'] == 'megjegyzes' ) {
    //     $selected_megj = ' selected';
    // }
    array_push( $sz_mezo, array('eleje' => '<option value="szakterulet"'.$selected_szakter.'>',                     'vege' => 'Szakterület</option>') );
    // array_push( $sz_mezo, array('eleje' => '<option value="megjegyzes" data-tabla="megjegyzes"'.$selected_megj.'>', 'vege' => 'Megjegyzés</option>' ) );
}

//echo '<pre style="font-size: 1.4rem">';
//print_r($_SESSION['o_szuro_feltetel']);
//echo '</pre>';

// HTML előlállítása
$prefix         = '';
$szurve         = '';
// Itt szándékosan figyelünk két feltételt is: ha csak az o_szuro_feltetel volna megadva,
// akkor nem jelezzük a felhasználó felé, hogy van összetett szűrés a háttérben.
// Például ha dashboard-ról jönnek át, akkor összetett szűrővel csinálunk lekérdezést, de ez nem derül ki a felhasználó felé.
if( $_SESSION['o_szuro_feltetel'] && $_SESSION['o_szuro_html'] ) {
    $szurve = ' piros';
}
$osszetett_link = '<span id="osszetett_szuro" onclick="csoportMuvelet(\'osszetett_szuro\')"><img class="link'.$szurve.' f_svg" src="../elemek/filter.svg"></span>';
if( isset($_POST['o_szures']) ) {
    $prefix         = 'o_';
    $osszetett_link = '';
}

// Ha Dashboard-ról érkeztünk, akkor nincs szűrés lehetőség
if( $_SESSION['dashboard_feladat'] ) {
    $sz_mezo = array( array('eleje' => '<option value="dashboard_feladat" selected>', 'vege' => 'Feladat</option>' ) );
}


// Tehát a 'vege' alapján rendezzük az option-okat, így lesz a szűrés lista ABC-s rendben.
$options = '';
$sz_mezo = tobbdim_tomb_kulcs_rendez($sz_mezo, 'vege', SORT_ASC);
foreach ( $sz_mezo as $mezo ) {
    $options .= $mezo['eleje'].$mezo['vege'];
}
$szures = nl2('
        '.$osszetett_link.'
        <select id="'.$prefix.'szuro_mezo" class="f_szuro_mezo" data-tabla="'.$tabla.'">
            <option value="">- Szűrési mező -</option>
           '.$options.'
        </select>
        <div id="'.$prefix.'operator" class="dib"></div>
        <div id="'.$prefix.'feltetel" class="dib"></div>
        <div id="'.$prefix.'szures_submit" class="dib"></div>
    ');

if( isset($_GET['mezo']) ) {
    $szures .= nl2('
        <script>
        $(document).ready(function () {
            operator_beallit($(\'.f_szuro_mezo\'), \''.$_GET['op'].'\', \''.addslashes($_GET['felt']).'\');
        });
        </script>
        ');
}
if( $_SESSION['dashboard_feladat'] ) {
    $szures .= nl2('
        <script>
        $(document).ready(function () {
            operator_beallit($(\'.f_szuro_mezo\'), \'equal\', \''.$_SESSION['dashboard_feladat'].'\');
        });
        </script>
        ');
}