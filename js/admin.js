var KEY_BACKSP =   8;
var KEY_TAB    =   9;
var KEY_ENTER  =  13;
var KEY_ESCAPE =  27;
var KEY_SPACE  =  32;
var KEY_UP     =  38;
var KEY_DOWN   =  40;
var KEY_PLUS   =  107;
var KEY_MINUS  =  109;
var KEY_F5     =  116;
var KEY_F12    =  123;

var EGYSEGAR_VALASZT = 'Kérjük válasszon egységár sort!';

var keys = {37: 1, 38: 1, 39: 1, 40: 1};

function preventDefault(e) {
    e = e || window.event;
    if (e.preventDefault) {
        e.preventDefault();
    }
    e.returnValue = false;
}

function preventDefaultForScrollKeys(e) {
    if (keys[e.keyCode]) {
        preventDefault(e);
        return false;
    }
}

function disableScroll() {
    if (window.addEventListener) {
        window.addEventListener('DOMMouseScroll', preventDefault, false);
    }
    window.onwheel = preventDefault;
    window.onmousewheel = document.onmousewheel = preventDefault;
    window.ontouchmove  = preventDefault;
    document.onkeydown  = preventDefaultForScrollKeys;
}

function enableScroll() {
    if (window.removeEventListener) {
        window.removeEventListener('DOMMouseScroll', preventDefault, false);
    }
    window.onmousewheel = document.onmousewheel = null;
    window.onwheel = null;
    window.ontouchmove = null;
    document.onkeydown = null;
}

/**
*	Töröl
*	Adminban a listából való törlésre
*
*	@param string fajl: a php fájl amit meghívunk
*	@param string objid: az objektum neve, amit el szeretnénk tűntetni
*/
function torol(fajl, objid){
    /* Ha a tid végződésű válétozót átnevezem tid+tobbkep_torol-re, akkor nem az esemenyek.php-ban fog lefutni a termék törlése (és ott ki is lépne az algoritmus), hanem a konkrét termek.php-ban fog lefutn a többkép törlése */
    alert2('Törlés folyamatban...');
	$.ajax({
        url : fajl.replace('tid', 'tidtobbkep_torol'),
        type: 'GET',
        data: esamp2es('&amp;becsapas=' + new Date().getTime()),
        success: function(html) {
            if(html.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            $.ajax({
                url : fajl,
                type: 'GET',
                data: esamp2es('&amp;becsapas=' + new Date().getTime()),
                success: function(html) {
                    if(html.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                    $('#bejegyzesek_szama').html(html);
                    $('#'+objid).fadeOut();
                    alert2();
                }
            });
        }
    });
}

/* A pipára kattintva változzon meg annak színe és az adatbázisban is változzon az értéke. */
function pipa_kapcsolo(id, valtozonev, lista_neve, sorid){
	$.ajax({
		type: 'POST',
		url : 'esemenyek.php',
		data: valtozonev + '=1&' + lista_neve + '=1&id=' + sorid,
		success: function(html){
            if(html.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            $('#'+id).toggleClass('aktiv');
        }
	});
}

/* Felsorolásnál törlésgomhoz HTML. Hogy valid legyen kiveszem JS-be. */
function get_torles_gomb_e(self, torles){
    return '<p class="c" style="margin-top: 0.5rem"><input class="gomb zold" type="button" value="Igen" onclick="torol(\'' + self + '&amp;' + torles + '=';
}

/* Felsorolásnál törlésgomhoz HTML. Hogy valid legyen kiveszem JS-be. */
function get_torles_gomb_v(){
    return ')"> <input class="gomb piros" type="button" value="Nem" onclick="alert2()">';
}

/* Minden töröl gomb HTML-je. Hogy valid legyen kiveszem JS-be. */
function get_gomb_mindent(){
    return '<p class="c" style="margin-top: 0.5rem"><input class="gomb zold" type="button" value="Igen" onclick="document.getElementById(\'kij_torl\').submit()">  <input class="gomb piros" type="button" value="Nem" onclick="alert2()">';
}

/* Kibontásnál a töröl gomb HTML-je. Hogy valid legyen kiveszem JS-be. */
function get_kibont_torol(self, torles, get_id){
    return '<p class="c" style="margin-top: 0.5rem"><input class="gomb zold" type="button" value="Igen" onclick="szurkit(); location.href=\'' + self + '?lista&amp;' + torles + '=' + get_id + '\'">  <input class="gomb piros" type="button" value="Nem" onclick="alert2()">';
}

/* Kibontásnál címlap kép törlés HTML-je. Hogy valid legyen kiveszem JS-be. */
function get_fokep_torol(self, get_id, keptorol){
    return '<p class="c" style="margin-top: 0.5rem"><input class="gomb zold" type="button" value="Igen" onclick="$.ajax({ url : \'' + self + '\', type: \'GET\', data: \'id=' + get_id + '&amp;modosit&amp;keptorol=' + keptorol + '&amp;becsapas=' + new Date().getTime() + '\', success: function(html){ $(\'#fokep\').fadeOut(\'fast\'); alert2(); } });"> <input class="gomb piros" type="button" value="Nem" onclick="alert2()">';
}

/* Kibontásnál egy flashfájllal felvitt fájl törlése */
function get_fajl_torol_gomb(id, tabla, oszlop){
    return '<p class="c" style="margin-top: 0.5rem"><input class="gomb zold" type="button" value="Igen" onclick="$.ajax({type: \'POST\',url : \'esemenyek.php\',data: \'fajl_torol=1&tabla=' + tabla + '&oszlop=' + oszlop + '&id=' + id + '\', success: function(html){}}); alert2(); $(\'#fajl_' + tabla + '_' + oszlop + '_' + id + '\').fadeOut(); "> <input class="gomb piros" type="button" value="Nem" onclick="alert2()">';
}
function get_fajl_torol_gomb2(id, tabla, oszlop){
    return '<p class="c" style="margin-top: 0.5rem"><input class="gomb zold" type="button" value="Igen" onclick="$.ajax({type: \'POST\',url : \'esemenyek.php\',data: \'fajl_torol=1&tabla=' + tabla + '&oszlop=' + oszlop + '&id=' + id + '\', success: function(html){}}); alert2(); $(\'#fajl_' + oszlop + '_' + id + '\').fadeOut();"> <input class="gomb piros" type="button" value="Nem" onclick="alert2()">';
}


/* Userek belépése az adminhoz */
function login_mentes(id){

    $.ajax({
        url : 'beleptetes.php',
        type: 'POST',
        data: esamp2es('id='+id+'&amp;rendes_nev='+$('#rendes_nev_'+id).val()+'&amp;nev='+$('#nev_'+id).val()+'&amp;jelszo='+$('#jelszo_'+id).val()+'&amp;jog='+$('#jog_'+id).val()+'&amp;nyelv='+$('#nyelv_'+id).val()+'&amp;login_mentes=' + new Date().getTime()),
        success: function(html){
            if(html.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            alert2(html);
            $('#jelszo_'+id).val('');
        }
    });

}

/* Előkészítés joggal aki rendelkezik, az így is létre tud hozni munkalapot */
function uj_munkalap_elokeszites_joggal(){
    alert2('<div class="c"><input type="button" class="gomb" value="Előkészítésre" onclick="location.href=\'munkalap.php?uj&amp;elokeszitesre\'" style="margin-bottom: 2rem; width: 15rem"><br><input type="button" class="gomb kek" value="Telepítettnek" onclick="location.href=\'munkalap.php?uj&amp;telepitettnek\'" style="margin-bottom: 4rem; width: 15rem"><br><input type="button" class="gomb piros" value="Mégsem" onclick="alert2()" style="margin-bottom: 1rem;"></div>', '<div class="dn">Fiktív tartalom</div>', 'Új munkalap készítése');
}

/* Userek belépése az adminhoz */
function login_torol(id){

    $.ajax({
        url : 'beleptetes.php',
        type: 'POST',
        data: esamp2es('id='+id+'&amp;login_torol=' + new Date().getTime()),
        success: function(html){
            if(html.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            $('#login_'+id).fadeOut();
            $('#login_'+id+'_szep').fadeOut();
        }
    });

}

/* Jogosultságok: mi milyen php-t lát */
function jog_mentes(id){

    $.ajax({
        url : 'beleptetes.php',
        type: 'POST',
        data: esamp2es('id='+id+'&amp;jog_nev='+$('#jog_nev_'+id).val()+'&amp;jog_oldalak='+$('#jog_oldalak_'+id).val()+'&amp;jog_mentes=' + new Date().getTime()),
        success: function(html){
            if(html.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            alert2(html);
        }
    });

}


/* Jog tölése*/
function jog_torol(id){

    $.ajax({
        url : 'beleptetes.php',
        type: 'POST',
        data: esamp2es('id='+id+'&amp;jog_torol=' + new Date().getTime()),
        success: function(html){
            if(html.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            $('#jog_'+id).fadeOut();
        }
    });

}

/**
*	A képernyő szélességét adja vissza
*/
function getKepernyoSzel(){
	return self.innerWidth || (document.documentElement.clientWidth || document.body.clientWidth);
}

/**
*	A képernyő magasságát adja vissza
*/
function getKepernyoMagas(){
	return self.innerHeight || (document.documentElement.clientHeight || document.body.clientHeight);
}

/**
*	Az oldal tényleges szélességét adja vissza pixelben (tehát nem amennyi a képernyőből látszik, hanem scrollozva)
*/
function getOldalSzel(){
	if (window.innerHeight && window.scrollMaxY) {
		xWithScroll = window.innerWidth + window.scrollMaxX;
	} else if (document.body.scrollHeight > document.body.offsetHeight){
		xWithScroll = document.body.scrollWidth;
	} else {
		xWithScroll = document.body.offsetWidth;
  	}
	return xWithScroll;
}

/**
*	Az oldal tényleges magasságát adja vissza pixelben (tehát nem amennyi a képernyőből látszik, hanem scrollozva)
*/
function getOldalMagas(){
	if (window.innerHeight && window.scrollMaxY) {
		yWithScroll = window.innerHeight + window.scrollMaxY;
	} else if (document.body.scrollHeight > document.body.offsetHeight){
		yWithScroll = document.body.scrollHeight;
	} else {
		yWithScroll = document.body.offsetHeight;
  	}
	return yWithScroll;
}


/**
* @desc Kép elforgatása megadott százalékkal
*
* @param utvonal        string A kép elérési útvonala
* @param tabla          string Az adatbázis tábla, amit épp szerkesztünk (pl. termek)
* @param oszlop         string Az adatbázis tábla melyik oszlopában van letárolva a fájl neve (pl. kep)
* @param id             string Az adatbázis tábla hanyadig soráról van szó
* @param jquery_string  string Jqery-s string, amivel a megfelelő divet elérem, ahová a tartalmat betölthetem
*
* @return string Az elforgatott kép útvonala
*/
function get_admin_file_thumb(tabla, oszlop, id, jquery_string){
    alert2('Betöltés...');
    $.ajax({
        url : 'esemenyek.php',
        type: 'GET',
        data: esamp2es('tabla='+tabla+'&amp;oszlop='+oszlop+'&amp;id='+id+'&amp;get_admin_file_thumb=' + new Date().getTime()),
        success: function(response){
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            alert2();
            $(jquery_string).html(response);
        }
    });
}


/* Felhasználó váltás */
function felh_valt( valt ) {
    if( valt === undefined ) {
        valt = '';
    }

    if( valt ) {
        $('div.loginAlert').removeClass('dn');
        document.getElementById('login_nev').select();
    } else {
        $('div.loginAlert').addClass('dn');
    }
}

$(function() {
    isMobile = false;
    if( $(window).width() < 800 ) {
        isMobile = true;
    }

    /* Csak számokat elfogadó mező */
    $(document).on('keyup blur', '.f_csakszam, .f_plus_minus', function(){
        onlyNumbers($(this));
    });

    /* Plusz/minusz billentyűvel léptető mező */
    $(document).on('keydown', '.f_plus_minus', function(event){
        if(event.keyCode == KEY_PLUS){
            $(this).val( Number($(this).val())+1 );
            return false;
        }
        if(event.keyCode == KEY_MINUS){
            $(this).val( Number($(this).val())-1 );
            return false;
        }
    });

    /* Ha nincs kjelölve a bal navpanelben egy sor sem, akkor az első lesz a kijelöltünk */
    setTimeout(function() {

        /* Anyagigénylésnél nem a bal navpanelre kattintunk, hanem a táblázat első sorára */
        if( baseName(location.href)=='anyagigenyles' ){ /* && !_get('anyagrend') */
            if( !$('table.szamla_cont_tabla tr.kijelolve').length ){
                $('table.szamla_cont_tabla tbody tr:first-of-type').addClass('kijelolve').click();
            }

        /* Kattintás a nav panel első elemére */
        } else {
            
            if( !isMobile && !$('#szamlaList li.szamla_sor').hasClass('kijelolve') && !_get('uj') ) {
                $('#szamlaList li.szamla_sor').eq(0).find('a').trigger('click');
            }
        }


    }, 1000);

    $('#ajaxfedo').addClass('dn');

    click = false;
    $(document).on('mousedown', function(event) {
        if( event.target.id == 'csuszkaallit' ) {
            click = true;

            mLeft = event.pageX;
            cbWidth = parseInt($('#tartalomBal').css('width'));

            $('body').addClass('noselect');
        }
    });

    $(document).on('mousemove', function(event) {
        if( click ) {
            var cmLeft = event.pageX - mLeft,
                nbWidth = cbWidth + cmLeft;

            if( nbWidth > 170 && nbWidth < 800 ) {
                $('#tartalomBal').css('width', nbWidth);
                $('#tartalomJobb').css('width', 'calc(100% - ' + (Math.abs(nbWidth) + 16) + 'px)');
                $('.muveletek').css('width', 'calc(100% - ' + (Math.abs(nbWidth) + 16) + 'px)');

                createCookie('csuszkaSzelesseg', Math.abs(nbWidth), 365);
                $('#stat_gyors_szuro_div').css('width', Math.abs(nbWidth-17));
                fulGombMeretez();
            }

            fixTable();
        }
    });

    $(document).on('mouseup', function(event) {
        click = false;

        $('body').removeClass('noselect');
    });

    /* F12 tiltása */
    if( window.location.hostname!='demo.webdeb.hu' ){
        $(document).keydown(function (event) {
            if (event.keyCode == 123) {
                return false;
            } else if (event.ctrlKey && event.shiftKey && (event.keyCode == 73 || event.keyCode == 67)) {
                return false;
            }
        });
    }

    /* Billentyű leütés figyelés */
    $(document).on('keyup', function(e) {

        /* F5 esetén a szürkítést felhozzuk */
        if( e.keyCode == KEY_F5 ) {
            szurkit('mentés mégsem gombot is rejtsd el');
        }
        

        /* Léptetés lefele nyíllal, lépkedve le fel */
        if( e.keyCode == KEY_UP || e.keyCode == KEY_DOWN ) {

            var className = e.target.className;
            popup_display = $('#popup').css('display');

            if( (className !== undefined && className.indexOf('disableNav') !== -1) || popup_display == 'block' || $('#alert2').length || !$('#beleptet_alert').hasClass('dn') ) {
                return false;
            }

            var ketHasabos = new Array(
                'anyagigenyles',
                'anyagrendeles',
                'bejelentes',
                'bejelentes_arch',
                'bejovo_szamlak',
                'beosztas',
                'cash_flow',
                'cikktorzs',
                'dokumentumtar',
                'eloterj_bod',
                'eloterj_fb',
                'energia',
                'energia_terv',
                'erteklista',
                'eseti_fenntartas',
                'eszkozkezeles',
                'felmeresi_terv',
                'fenntartasi_szerzodes',
                'fenntartasi_terv',
                'ktghely',
                'ktgnem',
                'login',
                'munkalap',
                'munkalap_arch',
                'projektkezeles',
                'projektigenyles',
                'projektszerzodes',
                'raktar',
                'rendszer',
                'rendszeres_karbantartas',
                'szabadsagigeny',
                'szallito',
                'szamla',
                'szerepkor',
                'szerepkor_jog',
                'tervezes',
                'torzsadat',
                'utemezett_feladatok',
                'uzemeltetesi_naplo'
            );

            /* Eltávolítjuk a bal oldali kijelöléseket */
            kijelolesEltavolit();

            if( inArray(baseName(location.href), ketHasabos) ) {
                disableScroll();

                tabla = baseName(location.href);
                elso_utolso = false;

                /* Anyagigénylésnél használt táblázat esetén valójában a táblázat következő sorára kell ugrani, ami a helyben rendezetség miatt nem ugyanaz, mint a bal oldali navpanelé */
                /*if( $('.szamla_cont_tabla').length && _get('tablazat') ){*/
                if( tabla=='anyagigenyles' || aktiv_oldal == 'szamla_cont_tabla' ) {

                    var active   = $('.szamla_cont_tabla tr.kijelolve').index('.szamla_cont_tabla tbody tr'),
                        szamlaDb = $('.szamla_cont_tabla tbody tr').length - 1;
                        $('.szamla_cont_tabla tr').removeClass('kijelolve');

                    ugras = 0;
                    if( $('.szamla_cont_tabla tbody tr.fake').length ) {
                        ugras = 1;
                    }

                    if( e.keyCode == KEY_UP ) {
                        kovSzamlaSor = active - 1;

                        if( kovSzamlaSor < ugras ) {
                            kovSzamlaSor = ugras;/* 2018.09.21 Tibi kérésére az első soron felfele nyílra lépve maradunk az első soron, nem pedig az utolsóra lépünk át */
                            /*kovSzamlaSor = szamlaDb;
                            elso_utolso  = true;

                            if( $('#tartalomJobb .fht-tbody').length ) {
                                $('#tartalomJobb .fht-tbody').scrollTop($('#tartalomJobb .fht-tbody').prop('scrollHeight'));
                            } else {
                                $('#tartalomJobb').scrollTop($('#tartalomJobb').prop('scrollHeight'));
                            }*/
                        }
                    } else if( e.keyCode == KEY_DOWN ) {
                        kovSzamlaSor = active + 1;

                        if( kovSzamlaSor > szamlaDb ) {
                            kovSzamlaSor = active; /* 2018.09.21 Tibi kérésére az u tolsó soron lefele nyílra lépve maradunk az utolsó soron, nem pedig az elsőre lépünk át */
                            /*kovSzamlaSor = ugras;
                            elso_utolso  = true;

                            if( $('#tartalomJobb .fht-tbody').length ) {
                                $('#tartalomJobb .fht-tbody').scrollTop(0);
                            } else {
                                $('#tartalomJobb').scrollTop(0);
                            }*/
                        }
                    }

                    $('.szamla_cont_tabla tbody tr:nth-of-type('+(kovSzamlaSor+1)+')').addClass('kijelolve');

                    if( aktiv_oldal != 'szamla_cont_tabla' ) {
                        $('#szamlaList li').removeClass('kijelolve');
                    }

                    $('#anyagig-sor-'+$('.szamla_cont_tabla tr.kijelolve').attr('data-recid')).click();

                    if( $('#tartalomJobb .fht-tbody').length ) {
                        scrollLeptet();
                    }

                    return false;

                /* Eszközkezelésnél egy könyvtárszerkezet van bal oldalt */
                }else if(tabla=='eszkozkezeles'){

                    /* Itt próbálkoztunk azzal, hogy a könyvtárakon végig lehessen lépkedni, de túl bonyolult volt, hagytunk végül. Tamással megbeszélve.
                     * $('.konyvtar_szerk .active').parent().next('li').first('a').first('label').click();
                     *
                     * */

                /* A listás, általános verzió */
                }else{

                    var active   = $('#szamlaList li.szamla_sor.kijelolve').index('#szamlaList li.szamla_sor:visible'),
                        szamlaDb = $('#szamlaList li.szamla_sor:visible').length - 1;

                    if( !formModositva ) {
                        $('#szamlaList li.szamla_sor').removeClass('kijelolve');
                    }

                    if( e.keyCode == KEY_UP ) {
                        kovSzamlaSor = active - 1;

                        if( kovSzamlaSor < 0 ) {
                            kovSzamlaSor = 0; /* 2018.09.21 Tibi kérésére az első soron felfele nyílra lépve maradunk az első soron, nem pedig az utolsóra lépünk át */
                            /*kovSzamlaSor = szamlaDb;
                            elso_utolso  = true;*/
                        }
                    } else if( e.keyCode == KEY_DOWN ) {
                        kovSzamlaSor = active + 1;

                        if( kovSzamlaSor > szamlaDb ) {
                            kovSzamlaSor = szamlaDb; /* 2018.09.21 Tibi kérésére az utolsó soron lefele nyílra lépve maradunk az utolsó soron, nem pedig az elsőre lépünk át */
                            /*kovSzamlaSor = 0;
                            elso_utolso  = true;*/
                        }
                    }

                    if( !formModositva ) {
                        $('#szamlaList li.szamla_sor:visible').eq(kovSzamlaSor).addClass('kijelolve');
                    }
                    var szamlaID = $('#szamlaList li.szamla_sor:visible').eq(kovSzamlaSor).attr('data-recid');

                }

                /* Link */
                param = get_get(['id'], true);

                if( !param ) {
                    param = '?id=' + szamlaID + '&modosit';
                } else {
                    if( param.indexOf('?') !== -1 ) {
                        param += '&id=' + szamlaID;
                    }
                }

                var mellek = $('#szamlaFulek a.active').attr('data-oldal');

                szamlaBetolt( szamlaID, tabla + '.php', mellek );

                if( !elso_utolso ) {
                    scrollLeptet();
                }

                e.preventDefault();
                e.stopPropagation();
            }
        }
    });

    delay = (function() {
        var timer = 0;
        return function(callback, ms) {
            clearTimeout(timer);
            timer = setTimeout(callback, ms);
        };
    })();

    /* Gyorskereső */
    /* JS-el oldottuk meg ajax helyett
    $(document).on('keyup', '#rendezesKeres', function(e) {
        var that = $(this);

        delay(function() {
            $.ajax({
                url : that.attr('data-tabla'),
                type: 'GET',
                data: esamp2es('ksz=' + that.val() + '&amp;gyorskereso_ajax=' + new Date().getTime()),
                success: function (response) {
                    if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                    $('#tartalomBal').html(response);
                }
            });
        }, 650);
    });*/

    // Általánosan kattintva a dokumentumban
    $(document).on('click', function(e) {
        $('.kijeloltek_muv').removeClass('active');
    });
    // Readonly mezők tiltása
    $('input[readonly]')   .removeAttr('onclick').removeAttr('onblur');
    $('select[readonly]')  .removeAttr('onclick').removeAttr('onblur');
    $('textarea[readonly]').removeAttr('onclick').removeAttr('onblur');
    $(document).on('mousedown', 'input[readonly], select[readonly], textarea[readonly]', function(e) {
        $(this).select();
        return false;
    });
    $(document).on('click', 'label[readonly]', function(e) {
        e.preventDefault();
        return false;
    });

    // Általánosan igaz, hogy inputokon Enter-t ütve tabozzunk. Ehhez be kell állítani a tabndexeket
    tabindex_beallit();

    // Dátum mezőket elhagyva újraformázzuk
    $(document).on('keyup', '.datepicker, .datetimepicker', function(e) {
        var cursor_poz = $(this).getCursorPosition();
        if($(this).hasClass('datetimepicker')){
            $(this).val( datum_formatumra_formaz($(this).val(), 'Perc pontos') );
        }else{
            $(this).val( datum_formatumra_formaz($(this).val()) );
        }
        $(this).cursorTo( cursor_poz );
    });

    $(document).on('blur', '.datepicker, .datetimepicker', function(e) {
        var cursor_poz = $(this).getCursorPosition();
        if($(this).hasClass('datetimepicker')){
            $(this).val( datum_formatumra_formaz($(this).val(), 'Perc pontos') );
        }else{
            $(this).val( datum_formatumra_formaz($(this).val()) );
        }
    });

    $(document).on('blur keyup', '.ora_perc', function(e) {
        var cursor_poz = $(this).getCursorPosition();
        $(this).val( datum_formatumra_formaz_ora($(this).val()) );
        /*$(this).cursorTo( cursor_poz ); ez nem tudom miért kellett, de azért vettük ki, mert Chrome alatt nem záródott be a datetime picker ütemezett feladatoknál */
    });
    $(document).on('blur', '.datepicker', function(e) {
        if(!$(this).val().trim()){
            $(this).val('\xa0\xa0\xa0\xa0.\xa0\xa0.\xa0\xa0');
        }
    });
    $(document).on('blur', '.datetimepicker', function(e) {
        if(!$(this).val().trim()){
            $(this).val('\xa0\xa0\xa0\xa0.\xa0\xa0.\xa0\xa0\xa0\xa0:\xa0\xa0');
        }
    });
    $(document).on('blur', '.ora_perc', function(e) {
        if(!$(this).val().trim()){
            $(this).val('\xa0\xa0:\xa0\xa0');
        }
    });

    // Ütemezett feladatoknál az időszakos ütemezés pipára kattintva, az alapértelmezett értékek bellítása
    $(document).on('click', '#idoszakos', function(e) {
        if( $('#idoszakos').prop('checked') ){
            if(!$('#kezdho').val()){
                $('#kezdho').val('1').addClass('mod');
            }
            if(!$('#kezdnap').val() || $('#kezdnap').val()=='0'){
                $('#kezdnap').val('1').addClass('mod');
            }
            if(!$('#vegeho').val()){
                $('#vegeho').val('12').addClass('mod');
            }
            if(!$('#vegenap').val() || $('#vegenap').val()=='0'){
                $('#vegenap').val('31').addClass('mod');
            }

            /* Kötelező mezők jelzése */
            $('#kezdho').addClass('csillag');
            $('#kezdnap').addClass('csillag');
            $('#vegeho').addClass('csillag');
            $('#vegenap').addClass('csillag');
        }else{
            /* Kötelező mezők jelzése */
            $('#kezdho').removeClass('csillag');
            $('#kezdnap').removeClass('csillag');
            $('#vegeho').removeClass('csillag');
            $('#vegenap').removeClass('csillag');

        }
    });

    /* Checkbox space-ra is kapcsolódjon ki/be */
    $(document).on('keyup', 'input[type="checkbox"]', function(e) {
        if( e.keyCode == KEY_SPACE ) {
            var val = $(this).prop('checked');

            if( val ) {
                $(this).prop('checked', false).removeClass('mod');
            } else {
                $(this).prop('checked', true).addClass('mod');
            }
        }
    });

    /* Readonly-s selectet le se lehessen nyitni */
    $(document).on('focus', 'select[readonly]', function(e) {
        $(this).blur();
    });


    $(document).on('keyup', 'input, select', function(e) {
        if (e.which == KEY_ENTER && !$('#alert2').is(':visible') ) {

            mezo_nev = $(this).attr('name');
            stop = false;
            $(this).parents('form').find('input, select, textarea').filter(':not(:disabled)').filter(":not([readonly='readonly'])").each(function() {

                var name = $(this).attr('name'),
                    type = $(this).attr('type'),
                    tag  = $(this).prop('tagName');

                /* Autocomplete hidden inputjai miatt csak arra fókuszálunk, ami látszik is */
                if( name !== undefined && (stop && type == 'checkbox') || (stop && $(this).is(':visible')) ) {

                    if( type == 'checkbox' || tag == 'SELECT' ) {
                        document.getElementById(name).focus();
                    } else {
                        if($('#' + name).length){
                            document.getElementById(name).select();
                        }
                    }

                    return false;
                }

                if( name == mezo_nev ) {
                    stop = true;
                }
            });

            e.preventDefault();
        }
    });

    /* Tallózásnál jelezzük, hogy a fájl ki lett választva */
    $(document).on('change', 'div.jsff input[type=file]', function(e) {
        if(!_get('uj')){
            $(this).parent().find('span').html('Tallózás');
        }
    });

    $(document).on('click', '.kijeloltek_muv', function(e) {
        e.stopPropagation();
    });

    $(document).on('click', '.foGomb', function() {
        if( !$(this).parent('.kijeloltek_muv').hasClass('active') ) {
            $('.kijeloltek_muv').removeClass('active');
            $(this).parent('.kijeloltek_muv').addClass('active');
        } else {
            $('.kijeloltek_muv').removeClass('active');
        }
    });

    shiftCtrl = false;
    alt_tab   = false;
    $(document).on('keydown', function(e) {
        if( e.shiftKey || e.ctrlKey ) {
            if( !shiftCtrl ) {
                $('.kijCont').addClass('link');
                shiftCtrl = true;
            }
        }

        if( e.altKey ) {
            alt_tab = true;
        }
    });

    $(document).on('keyup', function(e) {
        if( !e.shiftKey || !e.ctrlKey ) {
            $('.kijCont').removeClass('link');
            shiftCtrl = false;
        }

        if( !e.altKey ) {
            alt_tab = false;
        }
    });

    /* Aloldalon/fülek alatti felsorolás kijelölések lekezelése. Pl. felhasználók szállodái. */
    $(document).on('click', '.kijCont .kijSor', function(e) {
        var parent = $(this).parents('.kijCont'),
            tabla  = parent.attr('data-tabla'),
            mellek = parent.attr('data-mellek'),
            sorok  = parent.find('.kijSor');

        /* SHIFT gomb lekezelése */
        if( e.shiftKey && !parent.hasClass('csak_egy_kijelolheto') ) {

            if( !sorok.hasClass('kijelolve') ) {
                $(this).addClass('kijelolve');

                kijeloltSzamlal( sorok, 'mellek', tabla, mellek );
            } else {

                var akt_id = parseInt( $(this).prevAll('.kijelolve').attr('data-i') );
                if(!akt_id){
                    akt_id = parseInt( $(this).nextAll('.kijelolve').attr('data-i') );
                }

                var uj_akt_id = parseInt( $(this).attr('data-i') );

                if( uj_akt_id > akt_id ) {
                    for( var i = akt_id; i <= uj_akt_id; i++ ) {
                        if( !parent.find('.kijSor[data-i="' + i + '"]').hasClass('kijelolve') ) {
                            parent.find('.kijSor[data-i="' + i + '"]:visible').addClass('kijelolve');
                        }
                    }
                } else {
                    for( var i = akt_id; i >= uj_akt_id; i-- ) {
                        if( !parent.find('.kijSor[data-i="' + i + '"]').hasClass('kijelolve') ) {
                            parent.find('.kijSor[data-i="' + i + '"]:visible').addClass('kijelolve');
                        }
                    }
                }

                kijeloltSzamlal( sorok, 'mellek', tabla, mellek );
            }

        /* CTRL gombbal további sorok megjelölése */
        } else if( e.ctrlKey && !parent.hasClass('csak_egy_kijelolheto') ) {

            if( $(this).hasClass('kijelolve') ) {
                $(this).removeClass('kijelolve');
            } else {
                $(this).addClass('kijelolve');
            }

            kijeloltSzamlal( sorok, 'mellek', tabla, mellek );

        /* Sima kattintás */
        }else{

            sorok.removeClass('kijelolve');
            $(this).addClass('kijelolve');

            kijeloltSzamlal( sorok, 'mellek', tabla, mellek );
        }
    });


    /* Csoportra kattintás lekezelése */
    $(document).on('click', 'ul.szamla_cont li.csoport', function(e) {
        var sorok       = $('#szamlaList li.szamla_sor'),
            id          = $(this).attr('data-id'),
            kijelolheto = $('#szamlaList').hasClass('csoport_nevre_kattintas');

        if( kijelolheto ) {
            /* Ha CTRL gombot használunk, másképp kell eljárni: ha meg volt jelölve, le kell venni a megjelöléseket */
            if(e.ctrlKey){

                if($(this).hasClass('kijelolve')){
                    $(this).removeClass('kijelolve');
                    $('ul.szamla_cont li[data-csoportid="' + id + '"]').removeClass('kijelolve');
                }else{
                    $(this).addClass('kijelolve');
                    $('ul.szamla_cont li[data-csoportid="' + id + '"]').addClass('kijelolve');
                }

            /* Sima kattintás esetén pedig a nem ehhez a csoporthoz tartozó sorokról kell levenni a kijelölést */
            }else{

                /* Minden olyan sor, ami NEM a csoport része, arról levesszük a kijelölést */
                sorok.each(function () {
                    if($(this).attr('data-csoportid')!=id){
                        $(this).removeClass('kijelolve');
                    }
                });

                $('#szamlaHidden').removeClass('csoport');
                $('li.csoport').removeClass('kijelolve');

                $(this).addClass('kijelolve');
                $('ul.szamla_cont li[data-csoportid="' + id + '"]').addClass('kijelolve');

                /* Ha csoportra kattintottunk, akkor a jobb oldali tartlom is megválétozik */
                csoport_tartalom();
            }

        }

        kijeloltSzamlal( sorok, 'fo' );
    });

    /* Csoport előtti + ikonra kattintva nyitjuk le */
    $(document).on('click', 'ul.szamla_cont li.csoport span.icon', function(e) {
        var szulo_li = $(this).parent('li');
        var id       = szulo_li.attr('data-id');

        szulo_li.toggleClass('nyitva');
        $('ul.szamla_cont li[data-csoportID="' + id + '"]').toggleClass('dn');

        e.preventDefault();
        e.stopPropagation();
    });

    /* Könyvtárszerkezetnél egy sor kijelölése */
    $(document).on('click', 'ol.konyvtar_szerk li.sor', function(e) {
        $('ol.konyvtar_szerk li.sor').removeClass('aktiv');
        $(this).addClass('aktiv');
    });

    /* Bal oldali nav panelen kattintunk egy bejegyzésre. Ekkor lekezeljük mi történik a kijelöésekkel */
    $(document).on('click', '#szamlaList li.szamla_sor', function(e) {
        var sorok       = $('#szamlaList li.szamla_sor'),
            csoportok   = $('#szamlaList li.csoport'),
            kijelolheto = $('#szamlaList').hasClass('kijelolheto');


        /* Ha nem használtunk sem CTRL-t, sem SHIFT gombot, vagy ha a több sor kijelölése nem engedélyezett */
        if( (!e.ctrlKey && !e.shiftKey) || !kijelolheto ) {

            if( !formModositva && !request_in_process ) {
                sorok.removeClass('akt').removeClass('kijelolve');
                csoportok.removeClass('akt').removeClass('kijelolve');
                $(this).addClass('akt kijelolve');
            }

            /* Navigálás */
            var recid   = $(this).find('a.szamla').attr('data-recid'),
                fajl    = $(this).find('a.szamla').attr('data-fajl'),
                muvelet = $(this).find('a.szamla').attr('data-muvelet');

            var mellek = $('#szamlaFulek a.active').attr('data-oldal');

            if( mellek === undefined ) {
                mellek = 'modosit';
            }

            if( recid && fajl && mellek ) {
                if(_get('tablazat')){
                    szamlaBetolt(recid, fajl, mellek, '', 'Kérdés nélkül');
                }else{
                    szamlaBetolt(recid, fajl, mellek);
                }
            }

            /*scrollLeptet();*/
            e.preventDefault();
            e.stopPropagation();

        /* CTRL és SHIFT lekezelése */
        } else {

            /* Bal oldali navpanel SHIFT gomb használata */
            if( e.shiftKey && kijelolheto ) {

                if( !sorok.hasClass('kijelolve') ) {
                    $(this).addClass('kijelolve');

                } else {

                    /* A shifthez legközelebb eső felső kijelölt sor megkeresése, ha az nincs, akkor meg az alsó */
                    var akt_id = parseInt( $(this).prevAll('.kijelolve').attr('data-i') );
                    if(!akt_id){
                        akt_id = parseInt( $(this).nextAll('.kijelolve').attr('data-i') );
                    }

                    var uj_akt_id = parseInt( $(this).attr('data-i') );

                    if( uj_akt_id > akt_id ) {
                        for( var i = akt_id; i <= uj_akt_id; i++ ) {
                            if( !$('#szamlaList.kijelolheto li.szamla_sor[data-i="' + i + '"]').hasClass('kijelolve') ) {
                                $('#szamlaList.kijelolheto li.szamla_sor[data-i="' + i + '"]').addClass('kijelolve');
                            }
                        }
                    } else {
                        for( var i = akt_id; i >= uj_akt_id; i-- ) {
                            if( !$('#szamlaList.kijelolheto li.szamla_sor[data-i="' + i + '"]').hasClass('kijelolve') ) {
                                $('#szamlaList.kijelolheto li.szamla_sor[data-i="' + i + '"]').addClass('kijelolve');
                            }
                        }
                    }

                }
                kijeloltSzamlal( sorok, 'fo' );

            /* Bal oldali navpanel CTRL gombbal való kijelölgetése */
            } else if( e.ctrlKey && kijelolheto ) {
                /*sorok.removeClass('active');*/

                /* Tömeges kijelöléshez */
                $(this).parent().find('.kijelolve');

                if( $(this).hasClass('kijelolve') ) {
                    $(this).removeClass('kijelolve');
                } else {
                    $(this).addClass('kijelolve');
                }

                kijeloltSzamlal( sorok, 'fo' );
            }

            var csoportID = $(this).attr('data-csoportID');

            if( csoportID !== undefined ) {
                var csoport_db = $('ul.szamla_cont li.szamla_sor[data-csoportID="' + csoportID + '"]').length;
                    csoport_db_kijelolve = $('ul.szamla_cont li.szamla_sor[data-csoportID="' + csoportID + '"].kijelolve').length;

                    if( csoport_db == csoport_db_kijelolve ) {
                        if( !$('ul.szamla_cont li.csoport[data-id="' + csoportID + '"]').hasClass('kijelolve') ) {
                            $('ul.szamla_cont li.csoport[data-id="' + csoportID + '"]').addClass('kijelolve');
                        }
                    } else {
                        $('ul.szamla_cont li.csoport[data-id="' + csoportID + '"]').removeClass('kijelolve');
                    }
            }
        }

    });

    /* Oldal elhagyásakor rákérdezünk, hogy biztosan */
    $(window).bind('beforeunload', function(e) {
        if( formModositva && !_get('jogosultsag') ) {
            return 'Biztos elhagyja az oldalt mentés nélkül?';
        }
    });

    $(document).on('click', '#respo_keresGomb', function() {
        $(this).toggleClass('active');

        if( $(this).hasClass('active') ) {
            $('#rendezesKeres').fadeIn(0);
        } else {
            $('#rendezesKeres').fadeOut(0);
        }
    });

    formModositva = false;
    $('#tartalomJobb').on('keyup change blur', '#szerk_form input:not([type=\'button\']), #szerk_form select, #szerk_form textarea', function(e) {

        var name = $(this).attr('name'),
            type = $(this).attr('type'),
            val  = $(this).val();

        if( name != 'mehet_gomb'  ) {
            var taroltVal = htmlspecialchars_decode(formAdatok[name]);
            if(taroltVal===undefined){
                taroltVal = '';
            }

            if( type == 'checkbox' ) {
                val = $(this).prop('checked');
            }

            if( $(this).hasClass('ora_perc') ) {
                if( formAdatok[name] && formAdatok[name] != '00:00' ) {
                    taroltVal = formAdatok[name];
                } else {
                    taroltVal = '\xa0\xa0:\xa0\xa0';
                    /* Ha az input értéke üres, akkor az alapértelmezett érték legyen az értéke */
                    if(!val){
                        val = taroltVal;
                    }
                }
            }

            /* Dátumidő mező */
            if( $(this).hasClass('datetimepicker') ) {
                if( formAdatok[name] != '0000-00-00 00:00:00' && formAdatok[name] != '0000-00-00 00:00' ) {
                    var date = new Date(formAdatok[name]);

                    var ev    = date.getFullYear(),
                        honap = date.getMonth() + 1,
                        nap   = date.getDate(),
                        ora   = date.getHours(),
                        perc  = date.getMinutes();

                    if( honap >= 0 && honap < 10 ) {
                        honap = '0' + honap;
                    }

                    if( nap >= 0 && nap < 10 ) {
                        nap = '0' + nap;
                    }

                    if( ora >= 0 && ora < 10 ) {
                        ora = '0' + ora;
                    }

                    if( perc >= 0 && perc < 10 ) {
                        perc = '0' + perc;
                    }

                    taroltVal = ev + '.' + honap + '.' + nap + ' ' + ora + ':' + perc;

                } else {

                    taroltVal = '\xa0\xa0\xa0\xa0.\xa0\xa0.\xa0\xa0\xa0\xa0:\xa0\xa0';
                    /* Ha az input értéke üres, akkor az alapértelmezett érték legyen az értéke */
                    if(!val){
                        val = taroltVal;
                    }
                }

            /* Dátum mező */
            }else if( $(this).hasClass('datepicker') ) {
                if( formAdatok[name] && formAdatok[name] != '0000-00-00' ) {

                    var date = new Date(formAdatok[name]);

                    var ev    = date.getFullYear(),
                        honap = date.getMonth() + 1,
                        nap   = date.getDate();

                    if( honap >= 0 && honap < 10 ) {
                        honap = '0' + honap;
                    }

                    if( nap >= 0 && nap < 10 ) {
                        nap = '0' + nap;
                    }

                    taroltVal = ev + '.' + honap + '.' + nap;
                } else {
                    taroltVal = '\xa0\xa0\xa0\xa0.\xa0\xa0.\xa0\xa0';
                    /* Ha az input értéke üres, akkor az alapértelmezett érték legyen az értéke */
                    if(!val){
                        val = taroltVal;
                    }
                }

            /* Numerikus mező */
            }else if( $(this).hasClass('f_num') ) {
                val = val.replace(/ /g, '');
            }

            /* Újsort le kell csérélni. Checkboxok miatt kell ez az if elé */
            if(val && val!=true){
                val = val.replace(/(\r|\n)/g, '#UJSOR#').replace(/'/g, '#APOSZTROF#');
            }

            /* Tizedes összegeknél a pontot vesszőre kell cseérlni az ellenőrzéshez*/
            if($(this).hasClass('f_num')){
                taroltVal = taroltVal.replace('\.', ',');
            }

            /* Checkboxok egy külön problémakör, mert itt a letárolt adat lehet üres, vagy stringes false. Ezt tehát itt külön le kell kezelni. */
            if($(this).attr('type')=='checkbox'){
                if(val==false || val==''){ val = 'false'; }
                if(val==true)   {  val       = 'true';  }
                if(taroltVal==0){  taroltVal = '';      }
                if(taroltVal==1){  taroltVal = 'true';  }
            }
            if(!taroltVal && (val=='false' || val==false) ){
                val = '';
            }

/*console.log(name+' db:'+taroltVal+'|itt:'+val);*/
            if( name!='egy_tobb_egy' && name!='egy_tobb_tobb' ){
                if( taroltVal != val ) {
                    $(this).addClass('mod');
                } else {
                    $(this).removeClass('mod');
                }
            }
            var form = $(this).parents('#szerk_form');

            mod = 0;
            form.find('input, select, textarea').each(function() {
                if( $(this).hasClass('mod') ) {
                    mod++;
                }
            });

            gombok_valt(mod);

            e.preventDefault();
        }
    });

    // Az éppen aktív rekordra ugrás, miután a rendezés lefutott
    $(document).ready(function () {
        if( $('.szamla_sor.kijelolve').hasClass('kijelolve') ) {
            $('#tartalomBal').scrollTop(0);
            active_top = $('.szamla_sor.kijelolve').position().top;

            $('#tartalomBal').scrollTop(active_top - 250);
        }
        
        // Egyik sor sincs kijelölve (mert nincs találat, pl. egy szűrés miatt), akkor a gombokat mindenképp rejtsük el.
        if( !_get('uj') && !$('.szamla_sor.kijelolve').length ){
            $('.muveletek').hide();
            adatlap_minden_inputjat_disabled();
        }

    });

    /* Scroll elindítása görgetésre */
    $(document).on('mousewheel', function(event) {
        enableScroll();
    });

    $('img.f_svg').each(function(){
        var $img     = jQuery(this);
        var imgID    = $img.attr('id');
        var imgClass = $img.attr('class');
        var imgURL   = $img.attr('src');

        $.get(imgURL, function(data) {

            /* Csak az SVG tag-el foglalkozunk, a többit elhagyjuk */
            var $svg = $(data).find('svg');

            /* ID beállítása, ha volt */
            if(typeof imgID !== 'undefined') {
                $svg = $svg.attr('id', imgID);
            }

            /* CSS beállítása, ha volt */
            if(typeof imgClass !== 'undefined') {
                $svg = $svg.attr('class', imgClass+' replaced-svg');
            }

            /* Felesleges XML tag-ek eltávolítása */
            $svg = $svg.removeAttr('xmlns:a');

            /* Kép cseréje az SVG kódra */
            $img.replaceWith($svg);

        }, 'xml');
    });

    $('#popup').on('click', function(e) {
        var className = e.target.className;

        if( className == 'bezar' ) {
            popupZar();
        }
    });

    /* Alert-en az Ok gomb Enterre is működjön */
    $(document).on('keydown', function(e) {
        if( e.keyCode == KEY_ENTER && $('#alert2').length && !$('#alert2').hasClass('rFalse') ) {
            $('#alert2 input[value="Ok"]').click();
        }
    });

    /* Oldal töltésekor megjelenítjük az ajaxfedőt hogy semmi másra ne lehessen kattintani. */
    $(document).ajaxSend(function(event, xhr, options) {
        if( baseName(options.url) != 'kapcs_autocomplete' && baseName(options.url) != 'szakter_autocomplete' && baseName(options.url) != 'autocomplete' ) {
            $('#ajaxfedo').removeClass('dn');
        }
    });

    /* Ha egy ajax hívás lefutott, akkor az ajaxfedo-t elrejtjük, illetve az input.focus-t kijelöljük */
    $(document).ajaxComplete(function (event, xhr, settings) {
        /* Felhasználóknál szálloda hozzáadása popup miatt ezt mégis kivettük, mert nem működött az autocomplete popupban: $('form.popupForm input.focus').select();*/
        $('#ajaxfedo').addClass('dn');

        if( $('#szamlaList li.csoport').hasClass('kijelolve') ) {
            /* Ha épp egy csoporton állunk, akkor elrejtük a jobb oldali tartalmakat */
            $('#szamlaList li.csoport').each(function () {
                if($(this).hasClass('kijelolve')){
                    csoport_tartalom();
                }
            });
        } else {

            /* Ha lefutott az ajax, akkor gondoskodjunk róla, hogy csak egy sor legyen kijelölve a bal oldali nav panelben. */
            if( !request_in_process ) {
                /* Anyagigénylés alatt ezt mégsem tesszük meg, mert ott több sor kijelölése engedélyezett. Sőt ha engedélyezve lenne más menüpont alatt is a több kijelölés, csoportosítás, akkor azt szintén fel kellene venni itt kivételbe. */
                if( baseName(location.href) != 'anyagigenyles' ){
                    $('#szamlaList li.szamla_sor').removeClass('kijelolve akt');
                    $('#szamla-' + _get('id')).addClass('akt kijelolve');
                }
            }
        }
    });

    $(document).on('click', 'ul.telephelyek a.nyit', function() {
        $(this).parent('li.alkatos').find('ul.alkatok').eq(0).toggleClass('active');
        $(this).find('span.icon').toggleClass('active');

        return false;
    });

    /* Ciklus tipus váltásakor megfelelő div megjelenítése */
    $(document).on('change', '.f_cikltip', function() {

        /* Váltáskor a ciklnap tárolt értéket nullázzuk, mert az új felületen minden üres */
        formAdatok.ciklhet          = '';
        formAdatok.ciklho           = '';
        formAdatok.ciklho2          = '';
        formAdatok.ciklnap          = '';
        formAdatok.cikltip          = '';
        formAdatok.esnap_d          = '0000-00-00'; /* Itt azért nem \xa0\xa0\xa0\xa0.\xa0\xa0.\xa0\xa0, mert a 0000-kat ellenőrzéskor visszaalakítjuk */
        formAdatok.hetkoz_kent      = '';
        formAdatok.honapjan         = '';
        formAdatok.ho_rendsz_het    = '';
        formAdatok.ism_het          = '';
        formAdatok.h  = '';
        formAdatok.k  = '';
        formAdatok.s  = '';
        formAdatok.cs = '';
        formAdatok.p  = '';
        formAdatok.sz = '';
        formAdatok.v  = '';

        $('.cikltip').hide();
        if($('#tipus_'+$(this).val()).length){
            $('#tipus_'+$(this).val()).show();
        }

        /* Váltáskor mindent nullázunk */
        $('#ciklnap').val('');
        $('#hetkoz_kent').prop('checked', false);

        $('#esnap_d').val('\xa0\xa0\xa0\xa0.\xa0\xa0.\xa0\xa0');
        $('#ciklhet').val('');
        $('#ism_het').val('');
        $('#ho_rendsz_het').val('');
        $('#h') .prop('checked', false);
        $('#k') .prop('checked', false);
        $('#s') .prop('checked', false);
        $('#cs').prop('checked', false);
        $('#p') .prop('checked', false);
        $('#sz').prop('checked', false);
        $('#v') .prop('checked', false);

        $('#ciklho').val('');
        $('#honapjan').val('');
        $('#honapjan') .prop('checked', false);
        $('#ciklho2').val('');
        $('#ho_rendsz') .prop('checked', false);
        $('#ho_rendsz_het') .prop('checked', false);
    });

    /* Szűrő mező váltáskor */
    $(document).on('change', '.f_szuro_mezo', function(e) {
        operator_beallit($(this));
    });

    /* Operátor váltáskor is változik a feltétel mező */
    $(document).on('change', '.f_szuro_mezo_operator', function(e) {
        szuro_ertek_mezo_gen($(this));
    });

    /* Szűrés érvényesítése */
    $(document).on('click', '.f_szures_submit', function(e) {

        felt = $('.f_felt').val();
        if(!felt){
            alert2('Kérjük állítsa be a szűrési feltételt!', '', 'Hiba');
        }else{
            /* Autocompletenek az ID-ját tegyük az URL-be */
            if( $('.f_felt').attr('id') ){
                felt_id = $('.f_felt').attr('id').replace('_autocomp', '');
                if($('#'+felt_id).length){
                    felt = $('#'+felt_id).val();
                }
            }
            
            /* Numerikus adatokból vegyük ki a szóközt */
            if( $('.f_felt').hasClass('f_num') ){
                felt = $('.f_felt').val().replace(/ /g, '');
            }

            kerdo_es = '&';
            if(get_get(['mezo', 'op', 'felt']).indexOf('?')<0){
                kerdo_es = '?';
            }

            var szures_link = get_get(['mezo', 'op', 'felt'])+kerdo_es+'mezo='+$('.f_szuro_mezo').val()+'&op='+$('.f_szuro_mezo_operator').val()+'&felt='+felt;
            if(!$(this).hasClass('mehet')){

                link = location.href;
                link = link.split('?');
                link = link[0];

                $.ajax({
                    url : link,
                    type: 'GET',
                    data: get_get(['mezo', 'op', 'felt']).replace(link+'?', '')+kerdo_es+'mezo='+$('.f_szuro_mezo').val()+'&op='+$('.f_szuro_mezo_operator').val()+'&felt='+felt+'&sql_db',
                    success: function (response) {
                        if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                        if(response>SZURES_DB_LIMIT){
                            alert2('Ezzel a szűrési feltétellel több mint '+SZURES_DB_LIMIT+' sor adatot listázna ki.<br>Biztosan folytatja?', '<input type="button" class="gomb zold" value="Igen" onclick="$(\'.f_szures_submit\').addClass(\'mehet\').click();"><input type="button" class="gomb piros" value="Nem" onclick="alert2(); $(\'.f_szures_submit\').removeClass(\'mehet\');">', 'Kérdés');
                        }else{
                            szurkit();
                            location.href=szures_link;
                        }
                    }
                });

            }else{

                szurkit();
                location.href=szures_link;

            }

        }
    });

    /* Összetett szűrő feltétel összeállítása */
    $(document).on('click', '.f_o_szuro_hozzaad', function() {

        var feltetel_ertek = $('#o_feltetel select').val();
        var feltetel_nev   = $('#o_feltetel option:selected').text();
        if(!feltetel_ertek){
            feltetel_ertek = feltetel_nev = $('#o_feltetel input').val();
            /* Ha autocompletes mező, akkor a rejtett input értékétvel kell dolgozni */
            if($('#o_feltetel input').attr('data-auto')){
                id = $('#o_feltetel input').attr('id');
                id = id.replace('autocomp_', '');
                feltetel_ertek = $('#'+id).val();
            }
        }

        data_tort = $('#szures_f_num').attr('data-tort');
        if ( data_tort || data_tort=='0' ) {
            feltetel_ertek = feltetel_ertek.replace(/ /g, '');
        }

        /* Hibaellenőrzés, majd mehet */
        if(!feltetel_ertek){
            alert2('Kérjük állítsa be a szűrési feltételt!', '', 'Hiba');
        }else{

            /* Feltételek törlése gomb megjelenítése */
            $('#o_sures_ft input').show();
            if(!$('#o_sures_ft').html()){
                $('#o_sures_ft').html('<input type="button" class="gomb kis_gomb piros f_o_szures_ftorol" value="Feltételek törlése">');
            }

            var mondat = '';
            $('#o_feltetel_halmaz').append(
                                    '<div class="dtr">'+
                                        '<div class="dtc">'+
                                            '<b class="zaro nyit">(</b>'+
                                        '</div>'+
                                        '<div class="dtc">'+
                                            '<input type="hidden" class="mezo" value="'+$('#o_szuro_mezo').val()+'">'+
                                            '<input type="hidden" class="op" value="'+$('#o_operator select').val()+'">'+
                                            '<input type="hidden" class="felt" value="'+feltetel_ertek+'">' +
                                            '<span class="m_mezo">' + $('#o_szuro_mezo option:selected').text() + '</span> ' +
                                            '<span class="m_op">' + $('#o_operator option:selected').text() + '</span> ' +
                                            '<span class="m_felt">' + feltetel_nev + '</span> ' +
                                        '</div>'+
                                        '<div class="dtc">'+
                                            '<b class="zaro zar">)</b>'+
                                        '</div>'+
                                        '<div class="dtc">'+
                                            '<select style="width: 7rem" class="es_vagy" onchange="osszetett_szuro_mondat()"><option value="es|">ÉS</option><option value="va|">VAGY</option></select>'+
                                        '</div>'+
                                        '<div class="dtc">'+
                                            '<b class="torol">×</b>'+
                                        '</div>'+
                                    '</div>'
                                    );

            /* Szűrés gomb mehet a végére */
            o_szures_gomb_mutat();

            /* Szűrő inputok nullázása */
            $('#o_szuro_mezo').val('');
            $('#o_operator select').val('');
            $('#o_feltetel select').val('');
            $('#o_feltetel input').val('');

            $('#o_operator select').hide();
            $('#o_feltetel select').hide();
            $('#o_feltetel input').hide();
            $('#o_szures_submit').hide();

            osszetett_szuro_mondat();
        }
    });
    /* Zárójelek aktiválása */
    $(document).on('click', '#o_feltetel_halmaz b.zaro', function() {
        $(this).toggleClass('aktiv');
        osszetett_szuro_mondat();
    });
    /* Feltétel sor törlése */
    $(document).on('click', '#o_feltetel_halmaz b.torol', function() {
        $(this).closest('.dtr').remove();

        /* Szűrés gomb kell-e még */
        if( $('#o_feltetel_halmaz div.dtc').length>0 ){
            o_szures_gomb_mutat();
        }else{
            $('#o_sures').html('');
        }
        osszetett_szuro_mondat();
    });

    /* Összetett szűrés kiértékelése: az elő 3 karakter egy kód, hogy ne a konkrét értékeket küldjük át, így biztonságosabb lefuttatni az SQL-t is */
    $(document).on('click', '.f_o_szures_submit', function() {
        var feltetel = '';
        $('#o_feltetel_halmaz div.dtr').each(function () {

            if( $(this).find('.zaro.nyit.aktiv').length ){
                feltetel += 'ny|#';
            }
            feltetel += 'me|'+$(this).find('.mezo').val()+'#';
            feltetel += 'op|'+$(this).find('.op').val()+'#';
            feltetel += 'fe|'+$(this).find('.felt').val()+'#';
            if( $(this).find('.zaro.zar.aktiv').length ){
                feltetel += 'za|#';
            }
            feltetel += $(this).find('.es_vagy').val()+'#';

        });

        var mehet = 0;
        if( $(this).hasClass('mehet') ){
            mehet = 1;
        }

        $.ajax({
            url : 'esemenyek.php',
            type: 'POST',
            data: {
                tabla            : $('#o_szuro_mezo').attr('data-tabla'),
                feltetel         : feltetel,
                mehet            : mehet,
                html             : $('#o_feltetel_halmaz').html(),
                f_o_szures_submit: new Date().getTime()
            },
            success: function (response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                response = JSON.parse(response);

                if( response.hiba ) {
                    alert2(response.hiba, response.gomb, response.title);
                }
                if( response.mehet ) {
                    szurkit();
                    location.reload();
                }
            }
        });

    });
    
    /* Összetett szűrők törlése */
    $(document).on('click', '.f_o_szures_torol', function(e) {
        $.ajax({
            url : 'esemenyek.php',
            type: 'POST',
            data: {
                f_o_szures_torol: new Date().getTime()
            },
            success: function (response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                szurkit();
                location.reload();
            }
        });
    });
    /* Összetett szűrők felételének törlése */
    $(document).on('click', '.f_o_szures_ftorol', function(e) {
        $('#o_feltetel_halmaz').html('');
        $('#o_szures_mondat').html('');
        $('#o_sures').html('');
        $(this).hide();
    });

    /* És vagy értékének letárolása */
    $(document).on('change', '.es_vagy', function(e) {
        var uj_selected = $(this).find('option:selected');
        $(this).find('option').removeAttr('selected');
        uj_selected.attr('selected', 'selected');
        $(this).val(uj_selected.val());
    });

    /* Szám mezők formázása */
    $(document).on('keyup', '.f_num', function(e) {
        onlyNumbers($(this));
    });

    $(document).on('blur', '.f_num', function(e) {
        /* Blur esetén csinálunk egy újabb focus-blur -ozást. De hogy ez ne történjen meg végtelenszer, ezért egy class nevet ráteszünk, amit focuszkor veszünk majd le. */
        if( !$(this).hasClass('nincs_tobb_blurozas') ){
            tort = 2;
            if($(this).attr('data-tort')){
                tort = $(this).attr('data-tort');
            }
            $(this).val( ezresek($(this).val().replace(/ /g, '').replace('.', '').replace(',', '.'), tort, ',', ' ') );

            /* Ez azért kell, hogy ha 0 volt az értéke, kitörlöm üresnek, akkor az ezresek 0-ját vissza teszi bele, tehát a mezőről le kellene venni a 'mod' class-t, és ezt egy focus-blur-al oldom meg */
            $(this).addClass('nincs_tobb_blurozas');

            if( !alt_tab ) {
                $(this).focus().blur();
            }
        }

    });
    $(document).on('focus', '.f_num', function(e) {
        $(this).removeClass('nincs_tobb_blurozas');
    });
    
    // Mező fókusra kijelölődik
    $(document).on('focus', '.f_select', function(e) {
        $(this).select();
    });

    /* Anyagigénylésnél a mennyiség mező kitöltése szálloda + anyag ismerete alapján */
    $(document).on('focus', '.f_anyagig_menny_feltolt', function(e) {
        if(!$(this).val()){
            anyagig_id = '#th'+$('#telephely_id').val()+'a'+$('#nev').val();
            $('#igmen').val( $(anyagig_id).html() );
        }
    });

    $(document).on('click', '.f_anyagigenyles_mezok_kitolt', function(e) {

        recid_egyedi_azon   = $(this).attr('data-recid_egyedi_azon');
        telephely_id        = $(this).attr('data-telephely_id');
        cikk_id             = $(this).attr('data-cikk_id');
        cikk_id2            = $(this).attr('data-cikk_id2');
        nev_autocompl       = $('#cikk_nev_'+recid_egyedi_azon).html();
        igmen               = $('#th'+telephely_id+'a'+cikk_id2).html();
        mee                 = $('#th'+telephely_id+'a'+cikk_id2+'_mee').html();
        igleir              = $('#th'+telephely_id+'a'+cikk_id2+'_igleir').html().replace(/<br\s*[\/]?>/gi, "\n");

        $('#telephely_id')  .val(telephely_id).addClass('mod');
        $('#nev_autocomp')  .val(nev_autocompl);
        $('#nev_autocomp')  .attr('data-origin-val', nev_autocompl).addClass('mod');
        $('#nev')           .val(cikk_id).addClass('mod');
        $('#igmen')         .val(igmen).addClass('mod');
        $('#igleir')        .val(igleir).addClass('mod');
        $('.insertMee')     .html(mee);

        $('#mehet_gomb').removeAttr('disabled').addClass('gombb_alert');
        $('#megsem_gomb').removeAttr('disabled');

    });
    $(document).on('click', '.f_anyag_rendelkezesre_all', function(e) {

        telephely_id = $(this).attr('data-telephely_id');
        cikkid_origi = $(this).attr('data-cikkid_origi');
        cikk_id      = $(this).attr('data-cikk_id');

        $.ajax({
            url : 'anyagigenyles.php',
            type: 'POST',
            data: esamp2es('telephely_id=' + telephely_id + '&amp;cikk_id=' + cikkid_origi + '&amp;f_anyag_rendelkezesre_all=' + new Date().getTime()),
            success: function (response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                $('#sor_'+cikk_id).fadeOut();
            }
        });
    });

    /* Loginnév mező formázása */
    $(document).on('keyup', '.f_loginnev', function(e) {
        $(this).val( $(this).val().replace(/[^a-zA-Z0-9._@éáűőúöüóíÉÁŰŐÚÖÜÓÍ]+/g, '') );
    });

    /* ESCAPE gomb lekezelése */
    $(document).on('keyup', function(e) {
        if( e.keyCode == KEY_ESCAPE ) {

            alert2();
            felh_valt();
            kijelolesEltavolit();

            /* Ha popupban kaptunk volna hibaüzenetet, akkor visszatérve fókuszáljunk a hibásnak jelölt inputra. Egyúttal vegyük is le ezt a classt róla */
            $('input.hibas_ide_focus, textarea.hibas_ide_focus, select.hibas_ide_focus').select().removeClass('hibas_ide_focus');

            if($('#elv_ido_tol').length){
                $('.xdsoft_datetimepicker').hide();
            }

            /* A popup bezáródik, de csak akkor, ha az alert nincs nyitva */
            if( !$('#alert2').is(':visible') ){
                popupZar();
            }

        }
    });

    /* Autocomplete érték visszaírásához kell */
    ev = false;
    $(document).on('click', function(event) {
        if( ev ) {
            var id = event.target.id;

            if( ev !== false && id.indexOf('_autocomp') === -1 /*id != '' && id !== undefined && Ez miért kellett? Popupban levő autocomplete-nél gond volt belőle, mert ha olyan elemre kattintottunk, aminke nem volt ID-ja, akkor nem íródott vissza az origin-val */ ) {
                var elementID = ev.target.id;

                if( elementID.indexOf('_autocomp') !== -1 && !ev.target.attributes['readonly'] && $('#' + elementID).attr('data-no_original_val')!='1' ) {
                    var originVal = $('#' + elementID).attr('data-origin-val');
                    var ertekID = $('#' + elementID.replace('_autocomp', '')).val();

                    if( ertekID ) {
                        $('#' + elementID).val(originVal).change();
                    }
                }
            }
        }

        ev = event;
    });

    /* Text inputon TAB-ozva */
    $(document).on('keydown', 'input[type="text"]', function(event) {
        if( event.keyCode == KEY_TAB ) {
            var elementID = $(this).attr('id');

            if( elementID.indexOf('_autocomp') !== -1 && !$(this).attr('readonly') && !$(this).attr('disabled') && $(this).attr('data-no_original_val')!='1' ) {
                var originVal = $(this).attr('data-origin-val');
                var ertekID = $(this).val();

                if( ertekID ) {
                    $(this).val(originVal).change();
                }
            }
        }
    });

    /* Bejelentés más nevében Tovább gomb ki-be kapcsolása */
    $(document).on('click', 'table.egyErtek tr.kijSor', function() {
        $(this).parents('table.egyErtek').find('tr.kijSor').removeClass('kijelolve');
        $(this).addClass('kijelolve');

        var nev = $(this).attr('data-nev');

        if( $(this).hasClass('kijelolve') ) {
            $('#diszpFeltolt').attr('disabled', false);
            $('#masnevebenFeltolt').val(nev);
        } else {
            $('#diszpFeltolt').attr('disabled', true);
            $('#masnevebenFeltolt').val('');
        }
    });

    /* Projektgénylésnél az egy és többszállodás modell kiválasztása */
    $(document).on('click keyup', '.f_pri_egytobb', function(e) {
        
        // Ha ráklikkeltünk, vagy ha space-t nyomtunk rá
        if( e.type=='click' || (e.type=='keyup' && e.keyCode==KEY_SPACE ) ){
        
            egy_tobb = $(this).attr('data-egytobb');
            if(egy_tobb=='egy'){
                $('#egy_tobb_tobb').prop('checked', false);
                $('#egy_tobb_egy') .prop('checked', true );
                $('#egy_tobb').val(0);
                if(formAdatok['egy_tobb']=='1'){
                    $('#egy_tobb_egy') .addClass('mod');
                    $('#egy_tobb_tobb').addClass('mod');
                }else{
                    $('#egy_tobb_egy') .removeClass('mod');
                    $('#egy_tobb_tobb').removeClass('mod');
                }
            }else{
                $('#egy_tobb_egy') .prop('checked', false);
                $('#egy_tobb_tobb').prop('checked', true );
                $('#egy_tobb').val(1);
                if(formAdatok['egy_tobb']=='0'){
                    $('#egy_tobb_egy') .addClass('mod');
                    $('#egy_tobb_tobb').addClass('mod');
                }else{
                    $('#egy_tobb_egy') .removeClass('mod');
                    $('#egy_tobb_tobb').removeClass('mod');
                }
            }

            $.ajax({
                url : 'projektigenyles.php',
                type: 'POST',
                data: {
                    egy_tobb     : egy_tobb,
                    f_pri_egytobb: new Date().getTime()
                },
                success: function (response) {
                    if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                    $('#telephely_id').parent().parent().replaceWith(response);
                }
            });
        }

    });

    $('ul.alalmenu_cont').on('mouseenter', function() {
        $(this).find('ul.alalmenu').css('display', 'inline-block');
    });

    $('ul.alalmenu_cont').on('mouseleave', function() {
        $(this).find('ul.alalmenu').css('display', 'none');
    });

    /* Autocomplete inputra kattintva hozza le a listát */
    $(document).on('click', '.autocomp', function() {
        var that = $(this);

        /* Csak ha nincs nyitva */
        if( !$('#autocomplete').is(':visible') ) {
            setTimeout(function() {
                var e = jQuery.Event('keyup');
                e.which = e.shiftKey;
                that.focus().trigger(e);
            }, 1);
        }
    });

    $(document).on('blur', '.autocomp', function() {
        $('.autocomp').each(function() {
            var value = $(this).val();

            if( value == '*' ) {
                $(this).val('').change();
            }
        });
    });


    $(document).on('click', '.mobilkeret li', function() {
        $('.mobilkeret li').removeClass('kijelolt');
        $(this).addClass('kijelolt');
        $('#vissza_nyitottak_koze').removeClass('dn');
    });
    /* Az első sor legyen alaból kijelölve, már ha van */
    /*if($('.mobilkeret li').length){
        $('.mobilkeret li:first-of-type').addClass('kijelolt');
        $('#vissza_nyitottak_koze').removeClass('dn');
    }*/

    fulGombMeretez();
    $(window).resize(function() {
        fulGombMeretez();
        fixTable();
    });

    $('.menu_icon').on('click', function() {
        $('.menu_icon').toggleClass('active');
        $('.menu').toggleClass('active');
    });


    $(document).on('click', '#bejelentoText', function() {
        if( isMobile ) {
            $(this).toggleClass('active');

            if( $(this).hasClass('active') ) {
                $('.dtr.bejelento_sor').fadeIn(0);
            } else {
                $('.dtr.bejelento_sor').fadeOut(0);
            }
        }
    });

    /* Aktív terület váltása: nav panel (bal oldalt) vagy jobb oldali terület. Le-fel nyillal léptetésnél nem mindegy. */
    aktiv_oldal = 'szamla_cont';
    $(document).on('click', '.szamla_cont .szamla_sor', function() {
        aktiv_oldal = 'szamla_cont';
    });

    $(document).on('click', '.szamla_cont_tabla tr', function() {
        aktiv_oldal = 'szamla_cont_tabla';
    });

    /* Csatolmányok törlése */
    $(document).on('click', '.f_csatolmany_torol', function() {
        
        $('.f_csatolmany_torol').removeClass('ide_kattints');
        $(this).addClass('ide_kattints');
        var fajlnev = $(this).parent().find('span').html();
        if( !$(this).hasClass('mehet') ){
            alert2('Biztosan törli '+a_az(fajlnev)+' '+fajlnev+' fájl nevet?', '<input type="button" class="gomb zold" value="Igen" onclick="$(\'.ide_kattints\').addClass(\'mehet\'); $(\'.ide_kattints\').click(); alert2();"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">');
            return false;
        }
        
        var div_elem = $(this).parent();

        $.ajax({
            url : 'esemenyek.php',
            type: 'POST',
            data: {
                tabla               : baseName(location.href),
                recid               : _get('id'),
                fajlnev             : fajlnev,
                f_csatolmany_torol  : Date.now()
            },
            success: function (response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                div_elem.fadeOut();
            }
        });
    });

    setTimeout(function() {
        fixTable();
    }, 50);
});

function fixTable() {
    if( $('#fixTable').length ) {
        var tartalomHeight = $('#tartalomJobb').height(),
            tartalomScrollHeight = $('#tartalomJobb').prop('scrollHeight');

        if( tartalomScrollHeight > tartalomHeight ) {
            $('#fixTable').height($('#tartalomJobb').height() - 50);

            var fHeader = $('#fixTable .fht-thead').height();
            var fFooter = $('#fixTable .fht-tfoot').height();

            $('#fixTable .fht-tbody').height($('#tartalomJobb').height() - 50 - fHeader - fFooter);
            $('#fixTable table').fixedHeaderTable({
                footer: true,
                width: '100%',
                create: function() {
                    $('#fixTable').find('.fht-thead thead tr th').each(function() {
                        var width = $(this).outerWidth(),
                            index = $(this).index();

                        $('#fixTable .fht-tbody table tr th').eq(index).css({
                            'min-width': width,
                            'max-width': width,
                            'width': width
                        });
                    });

                    $('#fixTable').find('.fht-tbody table tr th').each(function() {
                        var width = $(this).outerWidth(),
                            index = $(this).index();

                        $('#fixTable .fht-thead thead tr th').eq(index).css({
                            'min-width': width,
                            'max-width': width,
                            'width': width
                        });

                        $('#fixTable .fht-tfoot tfoot tr td').eq(index).css({
                            'min-width': width,
                            'max-width': width,
                            'width': width
                        });
                    });
                }
            });
        } else {
            $('#fixTable table').css('width', '100%');
        }
    }
}

/* Mailto beállítása a felső ikonsor mail ikonjához */
function sendMail( that, file ) {
    szamlaSorok = $('.szamla_cont_tabla tr.kijelolve');

    idk = {};

    var i = 1;
    szamlaSorok.each(function() {
        var id = $(this).attr('data-recid');

        idk[i] = id;
        i++;
    });

    if( file ) {
        var onclick, hiba = false;

        getMailto = $.ajax({
         	url : 'esemenyek.php',
         	type: 'GET',
            async: false,
         	data: {
                idk         : JSON.stringify(idk),
                file        : file,
                recid       : _get('id'),
                sendMail    : new Date().getTime()
            },
         	success: function(response) {
         		if(response) {
         			response = JSON.parse(response);

         			if( response.hiba ) {
         				alert2(response.hiba, response.gomb, response.title);
                        hiba = true;
         				return false;
         			}

                    var mailto_link = 'mailto: ?subject=' + response.subject + '&body=' + response.html;
                    $(that).attr('href', mailto_link);

                    onclick = $(that).attr('onclick');
                    $(that).removeAttr('onclick');
         		}
         	}
        });

        $.when(getMailto).then(function() {
            if( !hiba ) {
                $(that)[0].click();
                $(that).attr('onclick', onclick).removeAttr('href');
            }
        });
    }

    return false;
}

function fulGombMeretez() {
    if( $('div.muveletek').length && !isMobile ) {
        var muvHeight   = parseInt($('div.muveletek').outerHeight());
        var fulekHeight = parseInt($('#szamlaFulek').outerHeight());

        if( muvHeight > 102 ) {
            muvHeight += 65;
        } else {
            muvHeight += 40;
        }

        if( fulekHeight > 36 ) {
            muvHeight += fulekHeight - 36;

            $('#tartalomJobb').css('margin-top', fulekHeight + 'px');
            $('#szamlaFulek').css('margin-top', -Math.abs(fulekHeight + 15) + 'px');
        }

        var szamlaKeresesHeight = parseInt($('#szamlaKereses').outerHeight());

        if( szamlaKeresesHeight > 39 ) {
            muvHeight += szamlaKeresesHeight - 39;
        }

        $('#tartalomJobb').css('height', 'calc( 100% - ' + muvHeight + 'px )');
    }
}

/* Projektigénylésnél ha a tipus nem Beruházás, akkor a Besorolás ne is jelenjen meg, értéke nullázódjon. */
function prigeny_tipus_beallit(){
    if($('.f_prigeny_tipus #tipus').val()!='1'){
        $('#besorolas_panel').hide();
        $('#besorol').val('');
    }else{
        $('#besorolas_panel').show();
    }
}

function bejBejValaszt( telephely ) {
    var sor = $('#bejbejelentok tr.kijSor.kijelolve');

    if( sor.length ) {
        var nev = sor.attr('data-nev');
        location.href = 'bejelentes.php?uj&bejelento=' + nev + '&telephely=' + telephely;
    }
}

function bejBejManualis( value ) {
    location.href = 'bejelentes.php?uj&manualisan&telephely=' + value;
}

// Bejelentés más nevében
function bejBejelentok( value ) {
    if( value ) {
        $.ajax({
            url : 'esemenyek.php',
            type: 'GET',
            async: false,
            data: esamp2es('telephely_id=' + value + '&amp;getBejBejelentok=' + new Date().getTime()),
            success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                response = JSON.parse(response);

                if( response.hiba ) {
                    alert2(response.hiba, response.gomb, response.title);
                }
            }
        });
    }
}

/* Bejelentés saját vagy más nevében? */
function bejSajatMasneven() {
    alert2('Saját nevében jelent be hibát?', '<input class="gomb" value="Bejelentés saját néven" onclick="szurkit(); location.href=\'bejelentes.php?uj\'" type="button"><input class="gomb piros" value="Bejelentés más nevében" onclick="szurkit(); bejSzallodaValaszt()" type="button">');
}

/* Új bejelentés feltöltés: első lépés, hogy telephelyet választunk */
function bejSzallodaValaszt() {

    /* Bejelentéseknél ha módosítva van egy adatlap, és újat szeretnénk feltölteni, akkor is egyből rákérdezünk, hogy biztosan elhagyja-e */
    if( formModositva ) {
        alert2('Biztosan elhagyja ezt a '+$('#rekord_neve_targy').html()+' mentés nélkül?', '<input class="gomb zold" value="Igen" onclick="szurkit(); formModositva = false; bejSzallodaValaszt();" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">');
        szurkit_zar();
        return false;
    }

    $.ajax({
     	url : 'bejelentes.php',
        type: 'POST',
     	data: esamp2es('bejSzallodaValaszt=' + new Date().getTime()),
     	success: function(response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
     		if(response) {
                response = JSON.parse(response);

                if( response.hiba ) {
                    if( response.gomb ) {
                        alert2(response.hiba, response.gomb);
                    } else {
                        alert2(response.hiba, '', 'Hiba');
                    }

                    return false;
                }

                alert2('Kérem válasszon telephelyet: <br>' + response.html, '&nbsp;');
     		}
     	}
     });
}

function munkalapMegrendelve( recid, csak_kerdezunk ) {
    if( recid ) {
        if( csak_kerdezunk === undefined ) {
            csak_kerdezunk = '';
        }

        $.ajax({
         	url : 'munkalap.php',
         	type: 'POST',
         	data: {
                recid: recid,
                csak_kerdezunk: csak_kerdezunk,
                munkalapMegrendelve: new Date().getTime()
            },
         	success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
         		if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        if( response.gomb ) {
                            alert2(response.hiba, response.gomb);
                        } else {
                            alert2(response.hiba, '', 'Hiba');
                        }

                        return false;
                    }

                    szurkit();
                    location.href = baseName(location.href) + '.php';
         		}
         	}
        });
    }
}

function kuldesJovahagyasra( recid, csak_kerdezunk ) {
    if( recid ) {
        if( csak_kerdezunk === undefined ) {
            csak_kerdezunk = '';
        }

        $.ajax({
         	url : 'munkalap.php',
         	type: 'POST',
         	data: {
                recid: recid,
                csak_kerdezunk: csak_kerdezunk,
                kuldesJovahagyasra: new Date().getTime()
            },
         	success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
         		if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        if( response.gomb ) {
                            alert2(response.hiba, response.gomb);
                        } else {
                            alert2(response.hiba, '', 'Hiba');
                        }

                        return false;
                    }

                    szurkit();
                    location.href = baseName(location.href) + '.php';
         		}
         	}
        });
    }
}

/* Átadás diszpécser központi vezetőnek */
function diszKozpVezAtadas( that ) {
    var memo = $(that).find('textarea[name="reklamacio_memo"]');

    if( memo.val() ) {
        $.ajax({
         	url : 'munkalap.php',
         	type: 'POST',
         	data: {
                recid            : _get('id'),
                validate         : 'false',
                memo             : memo.val(),
                diszKozpVezAtadas: new Date().getTime()
            },
         	success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
         		if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        if( response.gomb ) {
                            alert2(response.hiba, response.gomb);
                        } else {
                            alert2(response.hiba, '', 'Hiba');
                        }

                        return false;
                    }

                    szurkit();
                    location.href = 'munkalap.php';
         		}
         	}
        });
    } else {
        alert2('Adja meg a visszaadás okát!');
    }

    return false;
}

function diszKozpVezLezaras( that ) {
    var memo = $(that).find('textarea[name="indoklas_memo"]');

    if( memo.val() ) {
        $.ajax({
         	url : 'munkalap.php',
         	type: 'POST',
         	data: {
                recid   : _get('id'),
                validate: 'false',
                memo    : memo.val(),
                diszKozpVezLezaras: new Date().getTime()
            },
         	success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
         		if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        if( response.gomb ) {
                            alert2(response.hiba, response.gomb);
                        } else {
                            alert2(response.hiba, '', 'Hiba');
                        }

                        return false;
                    }

                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
                    popupZar();
         		}
         	}
        });
    } else {
        alert2('Adja meg a visszaadás okát!');
    }

    return false;
}

/* Bejelentés elutasítása */
function bejelentesElutasit( that ) {
    var elutasitas_id = $(that).find('input[name="elutasitas_id"]'),
                 memo = $(that).find('textarea[name="elutasitas_memo"]');

    if( elutasitas_id.val() == '' ) {
        alert2('Kérem válassza ki az elutasítás okát!', '<input type="button" class="link gomb" value="Ok" onclick="alert2(); $(\'#elutasitas_id_autocomp\').focus();">', 'Hiba');
        return false;
    }

    if( memo.val() == '' ) {
        alert2('Kérem indokolja elutasítást!', '<input type="button" class="link gomb" value="Ok" onclick="alert2(); $(\'#elutasitas_memo\').focus();">', 'Hiba');
        return false;
    }

    if( _get('id') && elutasitas_id.val() && memo.val() ) {
        $.ajax({
         	url : 'bejelentes.php',
         	type: 'POST',
         	data: {
                recid: _get('id'),
                elutasitas_id     : elutasitas_id.val(),
                memo              : memo.val(),
                validate          : 'false',
                bejelentesElutasit: new Date().getTime()
            },
         	success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
         		if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, 'Hiba');
                        return false;
                    }

                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
                    popupZar();
         		}
         	}
        });
    }

    return false;
}

function bejelentesIgazolas( recid, csak_kerdezunk ) {

    if(balnav_kijelol_ell()){

        if( csak_kerdezunk === undefined ) {
            csak_kerdezunk = false;
        }

        if( recid ) {
            $.ajax({
                url : 'bejelentes.php',
                type: 'POST',
                data: {
                    recid               : recid,
                    csak_kerdezunk      : csak_kerdezunk,
                    bejelentesIgazolas  : new Date().getTime()
                },
                success: function(response) {
                    if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                    if(response) {
                        response = JSON.parse(response);

                        if( response.hiba ) {
                            if( response.gomb ) {
                                alert2(response.hiba, response.gomb);
                            } else {
                                alert2(response.hiba, '', 'Hiba');
                            }

                            return false;
                        }

                        szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
                        alert2();
                    }
                }
            });
        }
    }
}

function tobbBejegyzesTorol() {

    if(balnav_kijelol_ell()){

        szamlaSorok = $('#szamlaList li.szamla_sor.kijelolve');

        if( szamlaSorok.length ) {
            idk = {};

            var i = 1;
            szamlaSorok.each(function() {
                var id = $(this).attr('data-recid');

                idk[i] = id;
                i++;
            });

            if( objLength(idk) ) {
                for( key in idk ) {
                    $.ajax({
                        url : 'esemenyek.php',
                        type: 'GET',
                        data: esamp2es('noload&amp;' + baseName(location.href) + 'tid=' + idk[key] + '&amp;becsapas=' + new Date().getTime()),
                        success: function(response) {
                            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                        }
                    });
                }


            } else {
                alert2('Nincsenek kijelölt sorok!', '', 'Hiba');
            }
        }
    }
}

/* Csoport átnevezés figyelmeztetések */
function csa_alert() {

    if( balnav_kijelol_ell() ){

        var csoportok = $('ul.szamla_cont li.csoport.kijelolve span.csoportNev');

        $('#kmCont div.kijeloltek_muv').removeClass('active');

        if( csoportok.length > 0 ) {
            if( csoportok.length == 1 ) {
                kijelolve_li = $('ul.szamla_cont li.kijelolve:not(.csoport)');

                var text = $(csoportok).find('.icsopnev').text(),
                    id   = $(csoportok).attr('data-id');

                /* Összes csoport elem száma */
                csoport_li = $('ul.szamla_cont li.szamla_sor[data-csoportID="' + id + '"]');

                if( kijelolve_li.length == csoport_li.length ) {
                    alert2('Adja meg a csoport nevét: <br><input type=\"text\" id=\"csoport_nev_szerk\" name=\"csoport_nev_szerk\" value=\"' + text + '\" style=\"margin-top: 1rem; width: 28rem;\">', "<p class=\"c\"><input type=\"submit\" name=\"elkuld\" class=\"gomb zold\" value=\"Mentés\" onclick=\"csoportAtnevezes( " + id + " );\"><input type=\"button\" name=\"megsem\" class=\"gomb piros\" value=\"Mégsem\" onclick=\"alert2();\"></p>");
                } else {
                    alert2('Kérem csak egy csoportot jelöljön ki az átnevezéshez!');
                }
            } else {
                alert2('Kérem egyszerre csak egy csoportot jelöljön meg az átnevezéshez!');
            }
        } else {
            alert2('Kérem jelöljön meg egy csoportot az átnevezéshez!');
        }

    }
}

/**/
function tbt_alert() {
    /*alert2('Biztos törli a kijelölt bejegyzéseket?', "<p class=\"c\"><input class=\"gomb piros\" type=\"button\" value=\"Igen\" onclick=\"tobbBejegyzesTorol();\"><input class=\"gomb zold\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>");*/
    /*Korábban rákérdezett, de több sort valójában nem is törölhető.*/
    alert2('Egyszerre csak egy rekord törölhető!', '', 'Hiba');
}

function munkHozzMegszuntet() {

    if(balnav_kijelol_ell()){

        szamlaSorok = $('#szemRendelt table tr.kijSor.kijelolve');

        if( szamlaSorok.length ) {
            idk = {};

            hiba = false;
            szamlaSorok.each(function() {
                var statid = $(this).attr('data-statid');

                if( statid == '08' ) {
                    alert2('Kérem csak folyamatban státuszú sorokat jelöljön meg!');

                    hiba = true;
                }
            });

            if( hiba ) {
                return false;
            }

            var i = 1;
            szamlaSorok.each(function() {
                var id = $(this).attr('data-recid');

                $(this).appendTo('#hozzBeosztasNelkul table');
                $(this).find('td.nev').html('<input type="hidden" name="munkHozzMegszuntet[]" value="' + id + '">');

                idk[i] = id;
                i++;
            });

            szamlaSorok.removeClass('kijelolve');

            alert2();
        } else {
            alert2('Kérem jelöljön ki sorokat!', '', 'Hiba');
        }
    }
}

function munkHozzSzemely() {

    if(balnav_kijelol_ell()){

        szamlaSorok = $('#hozzBeosztasNelkul table tr.kijSor.kijelolve');

        if( szamlaSorok.length ) {
            var torzsNev = $('#munkalapMobil input[name="torzsadat_id_autocomp"]'),
                torzsID  = $('#munkalapMobil input[name="torzsadat_id"]');

            if( torzsID.val() ) {
                idk = {};

                var i = 1;
                szamlaSorok.each(function() {
                    var id = $(this).attr('data-recid');

                    $(this).appendTo('#szemRendelt table');
                    $(this).find('td.nev').html(torzsNev.val() + '<input type="hidden" name="addTorzsID[]" value="' + torzsID.val() + '"><input type="hidden" name="addTorzsMunkID[]" value="' + id + '">');

                    idk[i] = id;
                    i++;
                });

                szamlaSorok.removeClass('kijelolve');

                torzsNev.val();
                torzsID.val();

                alert2();
            } else {
                alert2('Kérem válasszon személyzetet!');
                document.getElementById('torzsadat_id_autocomp').select();
            }
        } else {
            alert2('Kérem jelöljön ki sorokat!', '', 'Hiba');
        }
    }
}

function munkBejelKoze() {

    if(balnav_kijelol_ell()){
        szamlaSorok = $('#hozzBeosztasNelkul table tr.kijSor.kijelolve');

        if( szamlaSorok.length ) {
            idk = {};

            var i = 1;
            szamlaSorok.each(function() {
                var id = $(this).attr('data-recid');

                $(this).addClass('dn');
                $(this).find('td.nev').html('<input type="hidden" name="munkMobilKivetel[]" value="' + id + '">');

                idk[i] = id;
                i++;
            });

            szamlaSorok.removeClass('kijelolve');

            alert2();
        } else {
            alert2('Kérem jelöljön ki sorokat!', '', 'Hiba');
        }
    }
}

/* Várólistából kivétel */
function varolistabolKivetel(csak_kerdezunk) {

    if(balnav_kijelol_ell()){

        szamlaSorok = $('#szamlaList li.szamla_sor.kijelolve');

        if( szamlaSorok.length ) {
            idk = {};

            var i = 1;
            szamlaSorok.each(function() {
                var id = $(this).attr('data-recid');

                idk[i] = id;
                i++;
            });

            if( objLength(idk) ) {
                var page = $('#szamlaBetolt').attr('data-page');

                $.ajax({
                    url : 'esemenyek.php',
                    type: 'POST',
                    data: {
                        idk                 : JSON.stringify(idk),
                        page                : page,
                        csak_kerdezunk      : csak_kerdezunk,
                        varolistabolKivetel : new Date().getTime()
                    },
                    success: function(response) {
                        if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                        if(response) {
                            response = JSON.parse(response);

                            if( response.hiba ) {
                                alert2(response.hiba, response.gomb, 'Hiba');
                                return false;
                            }

                            szurkit();
                            location.reload();
                            /*kijelolesEltavolit();
                            szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');

                            szamlaSorok.each(function() {
                                szamlaSorFrissit(page, $(this).attr('data-recid'));
                            });
                            alert2();
                            */
                        }
                    }
                });
            } else {
                alert2('Nincsenek kijelölt sorok!', '', 'Hiba');
            }
        }
    }
}

/* Anyagigénylés küldés jóváhagyásra */
function anyagigenyles_kuldes_jovahagyasra(csak_kerdezunk) {
    if(balnav_kijelol_ell()){

        szamlaSorok = $('#szamlaList li.szamla_sor.kijelolve');
        if( szamlaSorok.length ) {

            idk = {};

            var i = 1;
            szamlaSorok.each(function() {
                var id = $(this).attr('data-recid');

                idk[i] = id;
                i++;
            });


            if( objLength(idk) ) {
                var page = $('#szamlaBetolt').attr('data-page');

                $.ajax({
                    url : 'anyagigenyles.php',
                    type: 'POST',
                    data: {
                        idk                 : JSON.stringify(idk),
                        page                : page,
                        csak_kerdezunk      : csak_kerdezunk,
                        kuldes_jovahagyasra : new Date().getTime()
                    },
                    success: function(response) {
                        if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                        if(response) {
                            response = JSON.parse(response);

                            if( response.hiba ) {
                                alert2(response.hiba, response.gomb, response.title);
                                return false;
                            }

                            szurkit();
                            location.href = 'anyagigenyles.php?tablazat&id='+idk[1];
                            /* navpanel frissítése (minden kijelölt sort)
                            szamlaSorok.each(function() {
                                szamlaSorFrissit(page, $(this).attr('data-recid'));
                            });*/
                            alert2();
                        }
                    }
                });

            } else {
                alert2('Nincsenek kijelölt sorok!', '', 'Hiba');
            }
        }
    }
}

/* Anyagigénylés, rendelés készítése */
function anyagigenyles_rendeles_keszit(csak_kerdezunk) {
    if(balnav_kijelol_ell()){

        szamlaSorok = $('#szamlaList li.szamla_sor.kijelolve');
        if( szamlaSorok.length ) {
            idk = {};

            mehet = '';
            if(csak_kerdezunk=='mehet'){
                csak_kerdezunk  = '';
                mehet           = 'mehet';
            }

            var i = 1;
            szamlaSorok.each(function() {
                var id = $(this).attr('data-recid');

                idk[i] = id;
                i++;
            });

            if( objLength(idk) ) {
                var page = $('#szamlaBetolt').attr('data-page');

                $.ajax({
                    url : 'anyagigenyles.php',
                    type: 'POST',
                    data: {
                        idk                 : JSON.stringify(idk),
                        page                : page,
                        csak_kerdezunk      : csak_kerdezunk,
                        mehet_db_be         : mehet,
                        rendeles_keszit     : new Date().getTime()
                    },
                    success: function(response) {
                        if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                        if(response) {
                            response = JSON.parse(response);

                            if( response.hiba ) {
                                if( response.gomb ) {
                                    alert2(response.hiba, response.gomb, response.felirat);
                                } else {
                                    alert2(response.hiba, '', 'Hiba');
                                }

                                return false;
                            }

                            /* Ha mehet, akkor anyagrendelésre ugrunk át
                             * 2018.11.22 Update: nem ugrunk át, met zavaró, maradunk az igénylésen, ezért csak a navpanelt frissítjük végig */
                            if(mehet){

                                var navpanel_recid = $('.szamla_cont_tabla tbody tr.kijelolve').attr('data-recid');
                                $('.szamla_cont_tabla tbody tr.kijelolve').remove();
                                $('#szamla-'+navpanel_recid).remove();
                                $('.szamla_cont_tabla tbody tr:nth-of-type(2)').addClass('kijelolve');
                                
                                /* Bizti kedvéért a kijelölt sorokat átmásolásoljuk az eredeti navpanelbe */
                                setTimeout(function(){
                                    $('#szamlaList li').removeClass('kijelolve');
                                    $('.szamla_cont_tabla tbody tr.kijelolve').each(function () {
                                        $('#szamla-'+$(this).attr('data-recid')).addClass('kijelolve');
                                    });

                                }, 1000);

                                /*szurkit();
                                location.href = 'anyagigenyles.php?id='+_get('id')+'&modosit&anyagrend';*/

                            }else{

                                /* Adatlap frissítése */
                                szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
                                /* navpanel frissítése (minden kijelölt sort) */
                                szamlaSorok.each(function() {
                                    szamlaSorFrissit('anyagigenyles', $(this).attr('data-recid'));
                                });

                            }

                            alert2();
                        }
                    }
                });
            } else {
                alert2('Nincsenek kijelölt sorok!', '', 'Hiba');
            }
        }
    }
}

// Árazott anyagigénylés jóváhagyása
function anyagigenyles_jovahagy(csak_kerdezunk, jhmen) {

    if(!csak_kerdezunk){
        formModositva = false;
        $('.szamla_cont_tabla input').removeClass('mod');
    }
    if(balnav_kijelol_ell()){

        szamlaSorok = $('.szamla_cont_tabla tbody tr.kijelolve');
        if( szamlaSorok.length ) {
            idk = {};

            var i = 1;
            szamlaSorok.each(function() {
                var id = $(this).attr('data-recid');

                idk[i] = id;
                i++;
            });

            if( objLength(idk) ) {
                var page = $('#szamlaBetolt').attr('data-page');

                // Anyagigénylés táblázatból meghívva ezt a függvény, minden sorra lefuttatjuk, és paraméterként adjuk át a jhmen-t, de adatlapon az input értékével számolunk
                if(!jhmen){
                    // Adatlap esetén
                    if( $('#jhmen').length ){
                        jhmen = $('#jhmen').val();
                    }
                    // Táblázat esetén a kijelölt sor
                    if( $('table.szamla_cont_tabla tr.kijelolve input').length ){
                        jhmen = $('table.szamla_cont_tabla tr.kijelolve input').val();
                    }
                }

                $.ajax({
                    url : 'anyagigenyles.php',
                    type: 'POST',
                    data: {
                        idk                     : JSON.stringify(idk),
                        page                    : page,
                        jhmen                   : jhmen,
                        csak_kerdezunk          : csak_kerdezunk,
                        anyagigenyles_jovahagy  : new Date().getTime()
                    },
                    success: function(response) {
                        if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                        if(response) {
                            response = JSON.parse(response);

                            if( response.hiba ) {
                                if( response.gomb ) {
                                    alert2(response.hiba, response.gomb);
                                } else {
                                    gomb = '';
                                    if(response.focus){
                                        gomb = '<input type="button" class="link gomb" value="Ok" onclick="alert2(); $(\'#'+response.focus+'\').select()">';
                                    }
                                    alert2(response.hiba, gomb, 'Hiba');
                                }
                                if(response.focus){
                                    $('#'+response.focus).select();
                                }

                                return false;
                            }

                            /* Anyagigénylés táblázat esetén frissítjük az oldalt, egyébként adatlapra ugrunk */
                            /*if(_get('tablazat')){
                                szurkit();
                                location.reload();
                            }else{
                                szamlaBetolt(_get('id'), baseName(location.href) + '.php');
                                // navpanel frissítése (minden kijelölt sort)
                                szamlaSorok.each(function() {
                                    szamlaSorFrissit(page, $(this).attr('data-recid'));
                                });
                            }

                            alert2();
                            */
                           szurkit();
                           location.href='anyagigenyles.php?tablazat';
                        }
                    }
                });
            } else {
                alert2('Nincsenek kijelölt sorok!', '', 'Hiba');
            }
        }
    }
}

/* Anyagigénylés elutasítás */
function anyagigenyles_elutasitas(csak_kerdezunk) {

    if(balnav_kijelol_ell()){

        szamlaSorok = $('#szamlaList li.szamla_sor.kijelolve');
        if( szamlaSorok.length ) {
            idk = {};

            var i = 1;
            szamlaSorok.each(function() {
                var id = $(this).attr('data-recid');

                idk[i] = id;
                i++;
            });

            if( objLength(idk) ) {
                var page = $('#szamlaBetolt').attr('data-page');

                var oka = '';
                if(!csak_kerdezunk){
                    oka = $('#pop_indoklas').val();
                    if(!oka){
                        $('#hibauzi').html('Indoklás nélkül nem lehet elutasítani az anyagigénylést!');
                        $('#pop_indoklas').select();
                        alert_raz();
                        return false;
                    }
                }

                $.ajax({
                    url : 'anyagigenyles.php',
                    type: 'POST',
                    data: {
                        idk                         : JSON.stringify(idk),
                        page                        : page,
                        csak_kerdezunk              : csak_kerdezunk,
                        oka                         : oka,
                        anyagigenyles_elutasitas    : new Date().getTime()
                    },
                    success: function(response) {
                        if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                        if(response) {
                            response = JSON.parse(response);

                            if( response.hiba ) {
                                if( response.gomb ) {
                                    alert2(response.hiba, response.gomb);
                                } else {
                                    gomb = '';
                                    if(response.focus){
                                        gomb = '<input type="button" class="link gomb" value="Ok" onclick="alert2(); $(\'#'+response.focus+'\').select();">';
                                    }
                                    alert2(response.hiba, gomb, 'Hiba');
                                }
                                if(response.focus){
                                    $('#'+response.focus).select();
                                }

                                return false;
                            }

                            /* Anyagigénylés táblázat esetén frissítjük az oldalt, egyébként adatlapra ugrunk */
                            szurkit();
                            location.reload();

                            alert2();
                        }
                    }
                });
            } else {
                alert2('Nincsenek kijelölt sorok!', '', 'Hiba');
            }
        }
    }
}

/* Anyagigénylés mind jóváhagyása gomb */
function anyagigenyles_mind_jovahagy(csak_kerdezunk) {

    if(balnav_kijelol_ell()){

        szamlaSorok = $('#szamlaList li.szamla_sor.arazva');
        if( szamlaSorok.length ) {
            idk     = {};
            jhmenek = {};

            var i = 1;
            szamlaSorok.each(function() {
                var id    = $(this).attr('data-recid');
                var jhmen = $('#jhmen_'+id).val();

                idk[i]      = id;
                jhmenek[id] = jhmen;
                i++;
            });

            if( objLength(idk) ) {
                var page = $('#szamlaBetolt').attr('data-page');

                $.ajax({
                    url : 'anyagigenyles.php',
                    type: 'POST',
                    data: {
                        idk                         : JSON.stringify(idk),
                        jhmenek                     : JSON.stringify(jhmenek),
                        page                        : page,
                        csak_kerdezunk              : csak_kerdezunk,
                        anyagigenyles_mind_jovahagy : new Date().getTime()
                    },
                    success: function(response) {
                        if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                        if(response) {
                            response = JSON.parse(response);

                            if( response.hiba ) {
                                if( response.gomb ) {
                                    alert2(response.hiba, response.gomb);
                                } else {
                                    gomb = '';
                                    if(response.focus){
                                        gomb = '<input type="button" class="link gomb" value="Ok" onclick="alert2(); $(\'#'+response.focus+'\').select();">';
                                    }
                                    alert2(response.hiba, gomb, 'Hiba');
                                }
                                if(response.focus){
                                    $('#'+response.focus).select();
                                }

                                return false;
                            }

                            /* Anyagigénylés táblázat esetén frissítjük az oldalt, egyébként adatlapra ugrunk */
                            szurkit();
                            location.reload();

                            /* navpanel frissítése (minden kijelölt sort) */
                            szamlaSorok.each(function() {
                                szamlaSorFrissit(page, $(this).attr('data-recid'));
                            });
                            alert2();
                        }
                    }
                });
            } else {
                alert2('Nincsenek kijelölt sorok!', '', 'Hiba');
            }
        }
    }
}

/* Anyagigénylés már igényelve gomb */
function anyagigenyles_mar_igenyelve(cikk_id, telephely_id, ig_alatt, ig_foly, csak_kerdezunk){
    $.ajax({
        url : 'anyagigenyles.php',
        type: 'POST',
        data: {
            cikk_id                     : cikk_id,
            telephely_id                : telephely_id,
            ig_alatt                    : ig_alatt,
            ig_foly                     : ig_foly,
            csak_kerdezunk              : csak_kerdezunk,
            anyagigenyles_mar_igenyelve : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if(response) {
                response = JSON.parse(response);

                if( response.hiba ) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }
            }
            szurkit();
            location.reload();
        }
    });
}

/* Csoport átnevezése */
function csoportAtnevezes( csoportID ) {
    var val = $('#csoport_nev_szerk').val(),
        page = $('#szamlaBetolt').attr('data-page');

    if( csoportID && val && page ) {
        $.ajax({
         	url : 'esemenyek.php',
         	type: 'GET',
         	data: esamp2es('page=' + page + '&amp;csoportID=' + csoportID + '&amp;val=' + val + '&amp;csoportAtnevezes=' + new Date().getTime()),
         	success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
         		if(response) {
         			response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, '', 'Hiba');
                        return false;
                    }

                    $('li.csoport[data-id="' + csoportID + '"] span.csoportNev .icsopnev').text(response.value);
         		}
         	}
        });

        alert2();
    }
}

/* Elutasítás popup mentése */
function bejegyzesElutasitas( that, csak_memo ) {
    var elutasitas_id = $(that).find('input[name="elutasitas_id"]'),
                 memo = $(that).find('textarea[name="elutasitas_memo"]');

    if(!csak_memo && !elutasitas_id.val()){
        $('#popup input, #popup textarea, #popup select').removeClass('hibas_ide_focus');
        $('#elutasitas_id_autocomp').addClass('hibas_ide_focus');
        alert2('Kérem válassza ki az elutasítás okát!', '<input type="button" class="link gomb" value="Ok" onclick="alert2();">', 'Hiba');
        return false;
    }
    if(!memo.val()){
        $('#popup input, #popup textarea, #popup select').removeClass('hibas_ide_focus');
        $('#elutasitas_memo').addClass('hibas_ide_focus');
        alert2('Kérjük indokolja az elutasítást!', '<input type="button" class="link gomb" value="Ok" onclick="alert2();">', 'Hiba');
        return false;
    }

    szamlaSorok = $('#szamlaList li.szamla_sor.kijelolve');
    if( szamlaSorok.length ) {
        idk = {};

        var i = 1;
        szamlaSorok.each(function() {
            var id = $(this).attr('data-recid');

            idk[i] = id;
            i++;
        });

        if( objLength( idk ) ) {
            $.ajax({
                url : 'esemenyek.php',
                type: 'POST',
                data: {
                    idk                 : JSON.stringify(idk),
                    memo                : memo.val(),
                    csak_memo           : csak_memo,
                    elutasitas_id       : elutasitas_id.val(),
                    tabla               : baseName(location.href),
                    bejegyzesElutasitas : new Date().getTime()
                },
                success: function(response) {
                    if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                    if(response) {
                        response = JSON.parse(response);

                        if( response.hiba ) {
                            alert2(response.hiba, response.gomb, 'Hiba');
                            return false;
                        }

                        if( response.kieg ) {
                            alert2(response.kieg, response.gomb, 'Hiba');
                        }

                        szurkit();
                        /* Projektigényléskor megszűnne bal oldali bejegyzés, ezért frissítjük az egész oldalt */
                        if(baseName(location.href)=='projektigenyles'){
                            szurkit();
                            location.reload();
                        }else{
                            kijelolesEltavolit();
                            szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
                            popupZar();
                        }
                    }
                }
            });
        }
    } else {
        alert2('Kérem jelölje ki az elutasítani kívánt '+$('#rekord_neve_targy').html()+'!', '', 'Hiba');
    }

    return false;
}

/* Törlés csoportból */
function csoportbolTorol( oszlop ) {
    if( oszlop ) {

        szamlaSorok = $('#szamlaList li.szamla_sor.kijelolve');

        if( szamlaSorok.length ) {
            idk = {};

            var i = 1;
            szamlaSorok.each(function() {
                var id = $(this).attr('data-recid');

                idk[i] = id;
                i++;
            });

            if( objLength( idk ) ) {
                var page = $('#szamlaBetolt').attr('data-page');

                $.ajax({
                    url : 'esemenyek.php',
                    type: 'POST',
                    data: {
                        oszlop  : oszlop,
                        idk     : JSON.stringify(idk),
                        page    : page,
                        csoportbolTorol : new Date().getTime()
                    },
                    success: function(response) {
                        if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                        if(response) {
                            response = JSON.parse(response);

                            if( response.hiba ) {
                                alert2(response.hiba, '', 'Hiba');
                                return false;
                            }

                            if( response.statusz == 'siker' ) {
                                $('#insertCsopHidden').html(response.html);
                            }

                            kijelolesEltavolit();
                            bejegyzesekLista('#rendezesSelect', 'asc', '', '');
                            alert2();
                        }
                    }
                });
            }
        } else {
            alert2('Nincsenek kijelölt sorok!', '', 'Hiba');
        }
    }

}

/* Csoporthoz adáskor új csoport létrehozása */
function addCsoport() {
    var ujcsoport = $('input[name="ujcsoportnev"]');

    if( ujcsoport.val() ) {
        var page = $('#szamlaBetolt').attr('data-page');
        $.ajax({
         	url : 'esemenyek.php',
         	type: 'GET',
         	data: esamp2es('page=' + page + '&amp;csoportnev=' + ujcsoport.val() + '&amp;addCsoport=' + new Date().getTime()),
         	success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
         		if(response) {
         			response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, '', 'Hiba');
                        return false;
                    }

                    if( response.statusz == 'siker' ) {
                        ujcsoport.val('');
                        $('#insertCsop').html(response.html);
                    }
         		}
         	}
        });
    } else {
        alert2('Kérem adja meg a csoport nevét!');
    }
}

// Csoportba rendezés
function csoportbaRendez( that ) {

    if(balnav_kijelol_ell()){

        var csoportID   = $(that).find('select[name="csoportID"]'),
            szamlaSorok = $('#szamlaList li.szamla_sor.kijelolve');

        if( csoportID.val() ) {
            if( szamlaSorok.length ) {
                idk = {};

                var i = 1;
                szamlaSorok.each(function() {
                    var id = $(this).attr('data-recid');

                    idk[i] = id;
                    i++;
                });

                if( objLength( idk ) ) {
                    var page = $('#szamlaBetolt').attr('data-page');

                    $.ajax({
                        url : 'esemenyek.php',
                        type: 'POST',
                        data: {
                            csoportID       : csoportID.val(),
                            idk             : JSON.stringify(idk),
                            page            : page,
                            csoportbaRendez : new Date().getTime()
                        },
                        success: function(response) {
                            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                            if(response) {
                                response = JSON.parse(response);

                                if( response.hiba ) {
                                    alert2(response.hiba, response.gomb, 'Hiba');
                                    return false;
                                }

                                kijelolesEltavolit();
                                bejegyzesekLista('#rendezesSelect', 'asc');
                                szurkit();
                                location.reload();
                                popupZar();
                            }
                        }
                    });
                }
            } else {
                alert2('Nincsenek kijelölt sorok!', '', 'Hiba');
            }
        } else {
            alert2('Kérem válasszon csoportot vagy hozzon létre újat!', '', 'Hiba');
        }
    }

    return false;
}

// Popupok megjelenítése a sablon/popup mappából
function csoportMuvelet( oldal, mobilnal_no_statusz_hiba ) {
    // Csak akkor hajtjuk végre, ha a bal oldali kijelölések is rendben vannak
    if( oldal=='anyagigenyles_erkeztet' || balnav_kijelol_ell() ){
        $('#kmCont div.kijeloltek_muv').removeClass('active');

        $.get('../sablon/admin/popup/' + oldal + '.html?cache=' + new Date().getTime(), function( data ) {

            switch( oldal ) {
                case 'csoporthoz_adas':

                    var mar_csoportosak         = '';
                    var nem_nyitott_statuszuak  = '';
                    var iktatok                 = '';
                    var elozo_telephely_id      = '';
                    var nem_azonos_szalloda     = '';
                    $('.kijelolve').each(function () {

                        // Ha már van csoportja
                        if($(this).hasClass('behuz')){
                            mar_csoportosak += $(this).find('td.ikt_szam').html()+'<br>';
                        }
                        // Ha nem nyitott a státusz
                        if(!$(this).hasClass('nyitott_statuszu')){
                            nem_nyitott_statuszuak += $(this).find('td.ikt_szam').html()+'<br>';
                        }

                        // Ha nem azonosak a szállodák
                        if(elozo_telephely_id && $(this).attr('data-telephely_id')!=elozo_telephely_id){
                            nem_azonos_szalloda = true;
                        }
                        elozo_telephely_id = $(this).attr('data-telephely_id');

                    });
                    if(mar_csoportosak){
                        alert2('Az alábbi '+$('#rekord_neve').html().toLowerCase()+' már csoport részei, így nem adhatóak újból csoporthoz:<br>'+mar_csoportosak, '', 'Hiba');
                        return false;
                    }
                    if(nem_azonos_szalloda){
                        alert2('Csak azonos szállodához tartozó '+$('#rekord_neve').html().toLowerCase()+' adhatóak csoporthoz!<br>', '', 'Hiba');
                        return false;
                    }
                    $('#popupTart').html(data);

                    var page = $('#szamlaBetolt').attr('data-page');
                    var ajax = $.ajax({
                        url : 'esemenyek.php',
                        type: 'GET',
                        data: esamp2es('page=' + page + '&amp;csoportSelect=' + new Date().getTime()),
                        success: function(response) {
                            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                            if(response) {
                                response = JSON.parse(response);

                                if( response.hiba ) {
                                    alert2(response.hiba, '', 'Hiba');
                                    return false;
                                }

                                $('#insertCsop').html(response.html);
                            }
                        }
                    });

                    $.when( ajax ).then(function() {
                        popupNyit();
                    });
                break;

                case 'anyagigenylesKpBeszerez':
                    alert2('Átmenetileg a készpénzes számlákat a PPM2 rendszerben dolgozza fel!', '', 'Tájékoztatás');
                break;

                case 'anyagigenylesLeirasHozzaadas':

                    szamlaSorok = $('#szamlaList li.szamla_sor.kijelolve');
                    if( szamlaSorok.length>1 ) {

                        oldal_nev = 'anyagigényléshez';
                        if( _get('anyagrend') ){
                            oldal_nev = 'anyagrendeléshez';
                        }
                        alert2('Leírást egyszerre csak egy '+oldal_nev+' lehet hozzáadni!');
                        return false;
                    }else{
                        $('#popupTart').html(data);
                        popupNyit();
                    }

                break;

                /* Ellenőrizzük, hogy van-e várólistás vagy sem, ha van hiba */
                case 'anyagigenylesVarolistahozAdas':


                    szamlaSorok = $('#szamlaList li.szamla_sor.kijelolve');
                    idk = {};

                    var i = 1;
                    szamlaSorok.each(function() {
                        var id = $(this).attr('data-recid');

                        idk[i] = id;
                        i++;
                    });

                    var mehet = true;
                    ellenorzes = $.ajax({
                        url : 'anyagigenyles.php',
                        type: 'POST',
                        data: {
                            idk                 : JSON.stringify(idk),
                            van_e_varoslista    : Date.now()
                        },
                        success: function(response){
                            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                            if(response){
                                response = JSON.parse(response);

                                if( response.hiba ) {
                                    alert2(response.hiba, response.gomb, 'Hiba');
                                    mehet = false;
                                    return false;
                                }


                            }
                        }
                    });

                    $.when( ellenorzes ).then(function() {
                        if(mehet){
                            $('#popupTart').html(data);
                            popupNyit();
                            return true;
                        }
                    });
                break;

                case 'munkalaphoz_adas':
                    $('#popupTart').html(data);

                    var ajax = $.ajax({
                        url : 'esemenyek.php',
                        type: 'GET',
                        data: esamp2es('munkalapSelect=' + new Date().getTime()),
                        success: function(response) {
                            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                            if(response) {
                                response = JSON.parse(response);

                                if( response.hiba ) {
                                    alert2(response.hiba, '', 'Hiba');
                                    return false;
                                }

                                $('#iMunkalapSelect').html(response.html);
                            }
                        }
                    });

                    $.when( ajax ).then(function() {
                        popupNyit();
                    });
                break;

                case 'atadasDiszpecsernek':

                    /* Ha felvéve vagy vizsgálat státuszban van, akkor azt is ellenőrizzük, hogy van-e feladat az utolsó tételéhez csatolva */
                    var mehet = true;
                    var ellenorzes = $.ajax({
                        url : 'munkalap.php',
                        type: 'POST',
                        data: {
                            recid        : _get('id'),
                            atadas_diszp : _get('id'),
                            van_e_feladat: Date.now()
                        },
                        success: function (response) {
                            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                            if(response) {
                                response = JSON.parse(response);

                                if( response.hiba ) {
                                    alert2(response.hiba, response.gomb, response.title);
                                    mehet = false;
                                    return false;
                                }

                            }
                        }
                    });
                    $.when( ellenorzes ).then(function() {

                        if(mehet){
                            $('#popupTart').html(data);
                            popupNyit();
                            return true;
                        }

                    });
                break;

                case 'munkalapAnyagigenyles':
                    $('#popupTart').html(data);

                    var mehet = true;
                    var ellenorzes = $.ajax({
                        url : 'munkalap.php',
                        type: 'POST',
                        data: {
                            recid        : _get('id'),
                            igenyles     : 1,
                            van_e_feladat: Date.now()
                        },
                        success: function (response) {
                            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                            if(response) {
                                response = JSON.parse(response);

                                if( response.hiba ) {
                                    alert2(response.hiba, response.gomb, response.title);
                                    mehet = false;
                                    return false;
                                }

                            }
                        }
                    });
                    $.when( ellenorzes ).then(function() {

                        if(mehet){

                            var ajax = $.ajax({
                                url : 'munkalap.php',
                                type: 'POST',
                                data: {
                                    recid               : _get('id'),
                                    szuk_menny          : true,
                                    getMunkalapAnyagok  : new Date().getTime()
                                },
                                success: function(response) {
                                    if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                                    if(response) {
                                        response = JSON.parse(response);

                                        if( response.hiba ) {
                                            alert2(response.hiba, response.gomb, response.title);
                                            return false;
                                        }

                                        $('#insertTable').html(response.html);
                                    }
                                }
                            });

                            $.when( ajax ).then(function() {
                                popupNyit();
                            });
                        }

                    });
                break;

                case 'munkalapAnyagvetelezes':
                    $('#popupTart').html(data);

                    var mehet = true;
                    var ellenorzes = $.ajax({
                        url : 'munkalap.php',
                        type: 'POST',
                        data: {
                            recid        : _get('id'),
                            van_e_feladat: Date.now()
                        },
                        success: function (response) {
                            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                            if(response) {
                                response = JSON.parse(response);

                                if( response.hiba ) {
                                    alert2(response.hiba, response.gomb, response.title);
                                    mehet = false;
                                    return false;
                                }

                            }
                        }
                    });
                    $.when( ellenorzes ).then(function() {

                        if(mehet){

                            var ajax = $.ajax({
                                url : 'munkalap.php',
                                type: 'POST',
                                data: {
                                    recid               : _get('id'),
                                    getMunkalapAnyagok  : new Date().getTime()
                                },
                                success: function(response) {
                                    if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                                    if(response) {
                                        response = JSON.parse(response);

                                        if( response.hiba ) {
                                            alert2(response.hiba, response.gomb, response.title);
                                            return false;
                                        }

                                        $('#insertTable').html(response.html);
                                    }
                                }
                            });

                            $.when( ajax ).then(function() {
                                popupNyit();
                            });
                        }
                    });

                break;

                case 'munkalap_mobil':
                    $('#popupTart').html(data);

                    idk = {};

                    szamlaSorok = $('#szamlaList li.szamla_sor.kijelolve');

                    if( szamlaSorok.length ) {
                        var i = 1;
                        szamlaSorok.each(function() {
                            var id = $(this).attr('data-recid');

                            idk[i] = id;
                            i++;
                        });
                    } else {
                        var recid = $('#szamlaBetolt').attr('data-recid');

                        if( recid ) {
                            idk[1] = recid;
                        }
                    }

                    if( objLength(idk) ) {
                        var ajax = $.ajax({
                            url : 'munkalap.php',
                            type: 'POST',
                            data: {
                                idk                 : JSON.stringify(idk),
                                no_statusz_hiba     : mobilnal_no_statusz_hiba,
                                hozzBeosztasNelkul  : new Date().getTime()
                            },
                            success: function(response) {
                                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                                if(response) {
                                    response = JSON.parse(response);

                                    if( response.hiba ) {
                                        alert2(response.hiba, response.gomb, response.title);
                                        popupZar();
                                        return false;
                                    }

                                    $('#hozzBeosztasNelkul').html(response.beosztasNelkul);
                                    $('#szemRendelt').html(response.szemRendelt);

                                    popupNyit();
                                }
                            }
                        });
                    } else {
                        alert2('Nincsenek kijelölt sorok!', '', 'Hiba');
                    }
                break;

                /* Munkalapoknál elvégzett feladat hozzáadása */
                case 'feladatHozzaad':

                    $('#popupTart').html(data);

                    var mehet = true;
                    var ellenorzes = $.ajax({
                        url : 'munkalap.php',
                        type: 'POST',
                        data: {
                            recid                           : _get('id'),
                            van_e_feladat_feladat_hozzaad   : Date.now()
                        },
                        success: function (response) {
                            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                            if(response) {
                                response = JSON.parse(response);

                                if( response.hiba ) {
                                    alert2(response.hiba, response.gomb, response.title);
                                    mehet = false;
                                    return false;
                                }

                            }
                        }
                    });
                    $.when( ellenorzes ).then(function() {

                        if(mehet){

                            var ajax = $.ajax({
                                url : 'munkalap.php',
                                type: 'POST',
                                data: {
                                    recid               : _get('id'),
                                    getMunkalapFeladatok: new Date().getTime()
                                },
                                success: function(response) {
                                    if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                                    if(response) {
                                        response = JSON.parse(response);

                                        if( response.hiba ) {
                                            alert2(response.hiba, response.gomb, response.title);
                                            return false;
                                        }

                                        $('#insertTable').html(response.html);
                                    }
                                }
                            });

                            $.when( ajax ).then(function() {
                                popupNyit();
                            });

                        }
                    });

                break;

                /* Munkaidő ráfordításnál táblázat kiíratása */
                case 'munkaidoHozzaad':
                    $('#popupTart').html(data);

                    var mehet = true;
                    var ellenorzes = $.ajax({
                        url : 'munkalap.php',
                        type: 'POST',
                        data: {
                            recid        : _get('id'),
                            munkaido     : 1,
                            van_e_feladat: Date.now()
                        },
                        success: function (response) {
                            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                            if(response) {
                                response = JSON.parse(response);

                                if( response.hiba ) {
                                    alert2(response.hiba, response.gomb, response.title);
                                    mehet = false;
                                    return false;
                                }

                            }
                        }
                    });

                    $.when( ellenorzes ).then(function() {

                        if(mehet){

                            var ajax = $.ajax({
                                url : 'munkalap.php',
                                type: 'POST',
                                data: {
                                    recid               : _get('id'),
                                    getMunkalapMunkaido : new Date().getTime()
                                },
                                success: function(response) {
                                    if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                                    if(response) {
                                        response = JSON.parse(response);

                                        if( response.hiba ) {
                                            alert2(response.hiba, response.gomb, response.title);
                                            return false;
                                        }

                                        $('#insertTable').html(response.html);
                                    }
                                }
                            });

                            $.when( ajax ).then(function() {
                                popupNyit();
                            });
                        }
                    });
                break;

                /* Az elutasítást csak egy soron lehet elvégezni. */
                case 'elutasitas':

                    alert2();

                    szamlaSorok = $('#szamlaList li.szamla_sor.kijelolve');
                    if( szamlaSorok.length>1 ) {

                        alert2('Egyszerre csak egy '+$('#rekord_neve_egyes').html()+' utasítható el!', '', 'Hiba');

                    /* Bejelentés, csak akkor utsasítható el, ha nyitott */
                    }else if( oldal=='bejelentes' && !$('#szamlaList li.szamla_sor.kijelolve').hasClass('nyitott_statuszu') ){

                        alert2('Csak nyitott státuszú '+$('#rekord_neve_egyes').html()+' utasítható el!', '', 'Hiba');

                    }else{

                        /* Az előző feltétel miatt itt biztosan csak egy sor van megjelölve */
                        if($('#szamlaList li.szamla_sor.kijelolve').hasClass('behuz')){
                            alert2('Csoportosított '+$('#rekord_neve_egyes').html()+' nem utasítható el.<br>Előbb vegye ki a csoportból!', '', 'Hiba');

                        /* Egyébként jöhet az elutasítás panel */
                        }else{
                            $('#popupTart').html(data);
                            popupNyit();
                        }

                    }
                break;

                /* Az elutasítást csak egy soron lehet elvégezni. */
                case 'elutasitas_csak_memo':
                    szamlaSorok = $('#szamlaList li.szamla_sor.kijelolve');
                    if( szamlaSorok.length>1 ) {
                        alert2('Egyszerre csak egy '+$('#rekord_neve_egyes').html()+' utasítható el!', '', 'Hiba');

                    /* Bejelentés, csak akkor utsasítható el, ha nyitott */
                    }else if( oldal=='bejelentes' && !$('#szamlaList li.szamla_sor.kijelolve').hasClass('nyitott_statuszu') ){

                        alert2('Csak nyitott státuszú '+$('#rekord_neve_egyes').html()+' utasítható el!', '', 'Hiba');

                    }else{

                        /* Az előző feltétel miatt itt biztosan csak egy sor van megjelölve */
                        if($('#szamlaList li.szamla_sor.kijelolve').hasClass('behuz')){
                            alert2('Csoportosított '+$('#rekord_neve_egyes').html()+' nem utasítható el.<br>Előbb vegye ki a csoportból!', '', 'Hiba');

                        /* Egybként jöhet az elutasítás panel */
                        }else{

                            $('#popupTart').html(data);
                            popupNyit();
                        }

                    }

                break;

                case 'bejelentesElutasit':
                    $('#popupTart').html(data);

                    $.ajax({
                        url : 'bejelentes.php',
                        type: 'POST',
                        data: {
                            recid             : _get('id'),
                            validate          : 'true',
                            bejelentesElutasit: new Date().getTime()
                        },
                        success: function(response) {
                            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                            if(response) {
                                response = JSON.parse(response);

                                if( response.hiba ) {
                                    alert2(response.hiba, response.gomb, response.title);
                                    return false;
                                }

                                popupNyit();
                            }
                        }
                    });
                break;

                case 'diszKozpVezAtadas':
                    $('#popupTart').html(data);

                    $.ajax({
                        url : 'munkalap.php',
                        type: 'POST',
                        data: {
                            recid            : _get('id'),
                            validate         : 'true',
                            diszKozpVezAtadas: new Date().getTime()
                        },
                        success: function(response) {
                            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                            if(response) {
                                response = JSON.parse(response);

                                if( response.hiba ) {
                                    alert2(response.hiba, response.gomb, response.title);
                                    return false;
                                }

                                $('#szoveg_kiirt').html('<p>' + response.szoveg_kiir + '</p>');
                                popupNyit();
                            }
                        }
                    });
                break;

                /* Az elutasítást csak egy soron lehet elvégezni. */
                case 'elutasitas':
                    szamlaSorok = $('#szamlaList li.szamla_sor.kijelolve');
                    if( szamlaSorok.length>1 ) {
                        alert2('Egyszerre csak egy '+$('#rekord_neve_egyes').html()+' utasítható el!', '', 'Hiba');
                    }else if( !$('#szamlaList li.szamla_sor.kijelolve').hasClass('nyitott_statuszu') ){
                        alert2('Csak nyitott státuszú '+$('#rekord_neve_egyes').html()+' utasítható el!', '', 'Hiba');
                    }else{

                        /* Az előző feltétel miatt itt biztosan csak egy sor van megjelölve */
                        if($('#szamlaList li.szamla_sor.kijelolve').hasClass('behuz')){
                            alert2('Csoportosított '+$('#rekord_neve_egyes').html()+' nem utasítható el.<br>Előbb vegye ki a csoportból!', '', 'Hiba');

                        /* Egybként jöhet az elutasítás panel */
                        }else{
                            $('#popupTart').html(data);
                            popupNyit();
                        }

                    }
                break;

                case 'bejelentesElutasit':
                    $('#popupTart').html(data);

                    $.ajax({
                        url : 'bejelentes.php',
                        type: 'POST',
                        data: {
                            recid             : _get('id'),
                            validate          : 'true',
                            bejelentesElutasit: new Date().getTime()
                        },
                        success: function(response) {
                            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                            if(response) {
                                response = JSON.parse(response);

                                if( response.hiba ) {
                                    alert2(response.hiba, response.gomb, response.title);
                                    return false;
                                }

                                popupNyit();
                            }
                        }
                    });
                break;

                case 'diszKozpVezAtadas':
                    $('#popupTart').html(data);

                    $.ajax({
                        url : 'munkalap.php',
                        type: 'POST',
                        data: {
                            recid            : _get('id'),
                            validate         : 'true',
                            diszKozpVezAtadas: new Date().getTime()
                        },
                        success: function(response) {
                            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                            if(response) {
                                response = JSON.parse(response);

                                if( response.hiba ) {
                                    alert2(response.hiba, response.gomb, response.title);
                                    return false;
                                }

                                $('#szoveg_kiirt').html('<p>' + response.szoveg_kiir + '</p>');
                                popupNyit();
                            }
                        }
                    });
                break;

                case 'diszKozpVezLezaras':
                    $('#popupTart').html(data);

                    $.ajax({
                        url : 'munkalap.php',
                        type: 'POST',
                        data: {
                            recid             : _get('id'),
                            validate          : 'true',
                            diszKozpVezLezaras: new Date().getTime()
                        },
                        success: function(response) {
                            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                            if(response) {
                                response = JSON.parse(response);

                                if( response.hiba ) {
                                    alert2(response.hiba, response.gomb, response.title);
                                    return false;
                                }

                                $('#popup_cim').html(response.popup_cim);
                                $('#szoveg_kiirt').html('<p>' + response.szoveg_kiir + '</p>');
                                popupNyit();
                            }
                        }
                    });
                break;

                case 'munkalapErkeztet':
                    $('#popupTart').html(data);

                    if(balnav_kijelol_ell()){

                        var recid = $('#szamlaList li.szamla_sor.kijelolve').attr('data-recid');
                        if( recid ) {

                            $.ajax({
                                url : 'munkalap.php',
                                type: 'POST',
                                data: {
                                    recid           : recid,
                                    munkalapErkeztet: new Date().getTime()
                                },
                                success: function(response) {
                                    if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                                    if(response) {
                                        response = JSON.parse(response);

                                        if( response.hiba ) {
                                            alert2(response.hiba, response.gomb, response.title);
                                            popupZar();
                                            return false;
                                        }

                                        $('#pop_ikt_szam').val(response.ikt_szam);
                                        $('#pop_telephely').val(response.telephely);
                                        $('#pop_tablazat').html(response.tablazat);

                                        popupNyit();
                                    }
                                }
                            });
                        } else {
                            alert2('Nincsenek kijelölt sorok!', '', 'Hiba');
                        }

                    }
                break;

                default:
                    $('#popupTart').html(data);
                    popupNyit();
                    return true;
                break;
            }

        });

    }
}

// Anyagigénylés árazása
function arazas() {

    idk = {};

    szamlaSorok = $('.szamla_cont_tabla tbody tr.kijelolve');

    if( szamlaSorok.length ) {
        var i = 1;
        szamlaSorok.each(function() {
            var id = $(this).attr('data-recid');

            idk[i] = id;
            i++;
        });
    } else {
        var recid = $('#szamlaBetolt').attr('data-recid');

        if( recid ) {
            idk[1] = recid;
        }
    }

    if( objLength(idk) ) {
        $.ajax({
            url : 'anyagigenyles.php',
            type: 'POST',
            data: {
                idk     : JSON.stringify(idk),
                arazas  : new Date().getTime()
            },
            success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, '', 'Hiba');
                        return false;
                    }
                    if( response.popup ) {
                        alert2(response.popup, '<input type="button" class="gomb zold" value="Mentés" onclick="arazas_ment()"> <input type="button" class="gomb piros" value="Mégsem" onclick="alert2()">', 'Rendelési adatok megadása');
                        return false;
                    }

                }
            }
        });
    } else {
        alert2('Nincsenek kijelölt sorok!', '', 'Hiba');
    }
    return false;
}
function arazas_ment(){

    pop_ig_menny = $('#pop_ig_menny').val().replace(',', '.').replace(/ /g,'');
    pop_rndmen   = $('#pop_rndmen').val().replace(',', '.').replace(/ /g,'');
    if(pop_ig_menny > pop_rndmen && !$('#pop_indoklas').val()){
        $('#indoklas_tr').show();
    }

    if($('#pop_dbf').prop('checked')==false && !$('#pop_erkcimid').val()){
        $('#popup_hiba').html('Kérjük válasszon szállítási címet!');
        alert_raz();
        return false;
    }

    var erkcimid = $('#pop_erkcimid').val();
    if(!erkcimid){
        erkcimid = 3;
    }

    idk = '';
    szamlaSorok = $('.szamla_cont_tabla tbody tr.kijelolve');
    if( szamlaSorok.length ) {
        var i = 1;
        szamlaSorok.each(function() {
            var id = $(this).attr('data-recid');

            idk += id+',';
            i++;
        });
    } else {
        var recid = $('#szamlaBetolt').attr('data-recid');

        if( recid ) {
            idk = recid;
        }
    }

    $.ajax({
        url : 'anyagigenyles.php',
        type: 'POST',
        data: {
            idk         : $('#idk').val(),
            szallito_id : $('#pop_szallito').val(),
            kcs_21      : $('#pop_kcs_21').val(),
            rndear      : $('#pop_rndear').val(),
            rndmen      : $('#pop_rndmen').val(),
            vrndear     : $('#pop_vrndear').val(),
            arfolyam    : $('#pop_arfolyam').val(),
            atvaltva_d  : $('#pop_atvaltva_d').val(),
            ig_menny    : $('#pop_ig_menny').val(),
            indok       : $('#pop_indoklas').val(),
            update_dt   : $('#pop_update_dt').val(),
            erkcimid    : erkcimid,
            arazas_ment : new Date().getTime()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if(response){
                response = JSON.parse(response);
                if( response.hiba ) {
                    $('#popup_hiba').html(response.hiba);
                    alert_raz();
                    if(response.focus){
                        $('#'+response.focus).select();
                    }
                    return false;
                }
                if( response.hiba_alert ) {
                    alert2(response.hiba_alert, response.gomb, response.title);
                    return false;
                }
            }

            /* Lista frissítése */
            szurkit();
            location.reload();
            /* 2do: sorokat befrissíteni
            idk = $('#idk').val().split(",");
            idk.forEach(function(id) {
                szamlaSorFrissit('anyagigenyles', id);
            });
            szamlaBetolt(idk[0], 'anyagigenyles.php', 'modosit');
            alert2();*/

        }
    });
}

/* Anyagigénylés érkezetése */
function anyagigenyles_erkeztet( ures_men_ek_pipa0_kesz, ures_men_ek_pipa1_kesz, tulrendeles_megkerdezve ) {

    /* ha van már táblázat, akkor azt dolgozzuk fel */
    if($('#rendeles_tetelek').html().trim()){

        ures_men_ek_pipa0   = '';
        ures_men_ek_pipa1   = '';
        tulrendeles_hibauzi = '';
        idk                 = '';
        hibauzi             = '';
        $('table.anyagig_erkeztet .erkmen').each(function (e) {
            id          = $(this).attr('data-id');
            szalloda    = $('#erkeztet_szall_'+id).html();
            cikkid      = $('#erkeztet_cikkid_'+id).html();
            cikknev     = $('#erkeztet_cikknev_'+id).html();
            rndmen      = $('#rndmen_'+id).html().replace(',', '.').replace(' ', '');
            marerkmen   = $('#marerkmen_'+id).html().replace(',', '.').replace(' ', '');
            erkmen      = $(this).val().replace(',', '.').replace(' ', '');
            nem_tobb    = $('#nem_tobb_'+id).prop('checked');
            mee         = $('#mee_'+id).html();
            szurkit_    = $('#erkeztet_sor_'+id).hasClass('erkeztet_szurkit');

            if(nem_tobb){
                nem_tobb = '1';
            }else{
                nem_tobb = '0';
            }

            /* Beérkezett mennyiség nem lehet negatív */
            if(erkmen<0){
                hibauzi = 'Beérkezett mennyiség nem lehet kevesebb, mint 0!';
                $(this).addClass('alert');
                return false;
            }

            /* Beérkezett mennyiség nem lehet több,<br>mint a rendelt mennyiség minusz az előzőleg beérkezett mennyiség! */
            /*if(erkmen>rndmen-marerkmen){
                hibauzi = 'Beérkezett mennyiség nem lehet több,<br>mint a rendelt mennyiség minusz az előzőleg beérkezett mennyiség!';
                $(this).addClass('alert');
                return false;
            }*/
            if(erkmen>rndmen-marerkmen && !tulrendeles_megkerdezve){
                gombok = '<input class="link gomb" value="Igen" onclick="anyagigenyles_erkeztet(\'\', \'\', \'tulrendelés már megkérdezve\')" type="button"> <input class="link gomb" value="Nem" onclick="alert2()" type="button">';
                hibauzi = 'Rendelt mennyiség: '+erkmen+' '+mee+'<br>Összes beérkezett mennyiség: '+(marerkmen+erkmen)+' '+mee+'<br>Ha folytatja, a beérkezett mennyiség több lesz, mint a rendelt mennyiség!<br>Folytatja?';
                $(this).addClass('alert');
                tulrendeles_hibauzi = true;
                return false;
            }

            /* NEM Pipált sorokra, ha üres marerkmen és erkmen is */
            if(!szurkit_ && nem_tobb==0 && (!erkmen || erkmen==0.00) && (!marerkmen || marerkmen==0.00)){
                ures_men_ek_pipa0 += szalloda+' '+cikkid+' '+cikknev+'<br>';
            }

            /* Pipált sorokra, ha üres marerkmen és erkmen is */
            if(nem_tobb==1  && (!erkmen || erkmen==0.00) && (!marerkmen || marerkmen==0.00)){
                ures_men_ek_pipa1 += szalloda+' '+cikkid+' '+cikknev+'<br>';
            }

            idk += id+'#'+erkmen+'#'+nem_tobb+'|';

        });

        /* ha túl lesz rendelve, rákérdezünk */
        if(tulrendeles_hibauzi){
            alert2(hibauzi, gombok);
            return false;
        }

        /* A hibaüzenetet itt írjuk ki, és nem az each ciklusban, mert akkor tovább futna a JS kód, ott nem űködik a return false mert az egy külön függvény */
        if(hibauzi){
            alert2(hibauzi, '<input class="link gomb" value="Ok" onclick="alert2(); $(\'#erkmen_'+id+'\').select();" type="button">');
            return false;
        }

        /* ha volt üres pipa mellett üres mennyiség sor, akkor rákrédezünk */
        if(ures_men_ek_pipa0 && !ures_men_ek_pipa0_kesz){
            gombok = '<input class="gomb zold" value="Igen" onclick="anyagigenyles_erkeztet(\'ures_men_ek_pipa0_kesz nem kérdezzük meg megint\', \'\', \'túlrendelés kérdésen már túl vagyunk\')" type="button"> <input class="gomb piros" value="Nem" onclick="alert2()" type="button">';
            alert2(ures_men_ek_pipa0+'<div class="l">Ezekhez az anyagokhoz nem adott meg beérkezett mennyiséget.<br>Ha folytatja, továbbra is megrendelve státuszban maradnak!<br>Folytatja?</div>', gombok);
            return false;
        }
        if(ures_men_ek_pipa1 && !ures_men_ek_pipa1_kesz){
            gombok = '<input class="gomb zold" value="Igen" onclick="anyagigenyles_erkeztet(\'ures_men_ek_pipa0_kesz nem kérdezzük meg megint\', \'ures_men_ek_pipa1_kesz nem kérdezzük meg megint\', \'túlrendelés kérdésen már túl vagyunk\')" type="button"> <input class="gomb piros" value="Nem" onclick="alert2()" type="button">';
            alert2(ures_men_ek_pipa1+'<div class="l">Ezekhez az anyagokhoz nem adott meg beérkezett mennyiséget.<br>Biztos, hogy már nem fognak beérkezni?<br>Folytatja?</div>', gombok);
            return false;
        }

        $.ajax({
            url : 'anyagigenyles.php',
            type: 'POST',
            data: {
                idk                     : idk,
                anyagigenyles_erkeztet  : new Date().getTime()
            },
            success: function (response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if(response){
                    response = JSON.parse(response);
                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, 'Hiba');
                        return false;
                    }
                    szurkit();
                    location.reload();
                }
                popupZar();
            }
        });

    /* Rendelés szám alapján a táblázat előállítása */
    }else{

        if(!$('#rsz').val().trim()){
            $('#rsz').addClass('hibas_ide_focus');
            alert2('Kérjük adja meg a rendelés számot!', '', 'Hiba');
            return false;
        }

        $.ajax({
            url : 'anyagigenyles.php',
            type: 'POST',
            data: {
                rsz                     : $('#rsz').val(),
                anyagigenyles_erkeztet  : new Date().getTime()
            },
            success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, '', 'Hiba');
                        return false;
                    }else{
                        $('#erkeztet_uzi').hide();
                        $('#szallito_sor').show();
                        $('#rsz').attr('readonly', true);
                        $('#pop_szallito').val(response.szallito);
                        $('#pop_szallito').attr('readonly', true);
                        $('#rendeles_tetelek').html(response.html);

                        /* Ráfókuszálás, kijelölés az első sor inputjára*/
                        $('#rendeles_tetelek input:text').first().select();
                    }

                }
            }
        });
    }

    return false;
}

/* Bal oldali navpanelben ellenőrizzük, hogy van-e olyan bejegyzés, ami ki van jelölve. */
function kijeloltSzamlal( sorok, reszleg, tabla, mellek ) {
    if( tabla === undefined ) {
        tabla = '';
    }

    if( mellek === undefined ) {
        mellek = '';
    }

    if( !sorok.hasClass('kijelolve') && reszleg == 'fo' ) {
        /*$('#kmCont').find('input[type="button"]').attr('disabled', true);*/
        $('#navGombok a.torles').attr('onclick', 'naviTorles();');

        if( _get('id') ) {
            /*$('.szamla_sor').removeClass('akt');*/
            $('#szamla-' + _get('id')).addClass('kijelolve');
        }

        jobbOldalEltuntet( sorok );
    } else if( sorok.hasClass('kijelolve') && reszleg == 'fo' ) {
        /*$('#kmCont').find('input[type="button"]').attr('disabled', false);*/
        $('#navGombok a.torles').attr('onclick', 'tbt_alert();');

        jobbOldalEltuntet( sorok );
    }

    /* Ha egy kapcsolódó táblás alodal fülön vagyunk, és nincs kijelölve egy sor sem */
    if( !sorok.hasClass('kijelolve') && reszleg == 'mellek' ) {

        $('#navGombok a.torles').addClass('disabled').attr('onclick', '');

    /* ... ha van kijelölve sor */
    } else if( sorok.hasClass('kijelolve') && reszleg == 'mellek' ) {

        /* Projektigénylések alatt, ha Cash-flow fülön állunk, akkor másképp oldjuk meg a törlést, ez a projektigenyles.js-ben található */
        if(tabla!='cash_flow'){

            szoveg = 'sorokat';
            torolhet = true;

            mellekre_valt = '';
            if(mellek=='megjegyzesek'){
                mellek          = 'megjegyzesek';
                mellekre_valt   = 'megjegyzesek';
                szoveg          = 'megjegyzéseket';
            }

            var full_szoveg = '';
            if(tabla=='munkalap' && _get('modosit')){
                full_szoveg = 'Biztosan törli a bejelentést a munkalapról?';
            }
            if(_get('engedelyek')){
                szoveg = 'engedélyeket';
            }
            if(_get('megterules')){
                szoveg = 'megtérüléseket';
            }
            if(_get('szallodak')){
                szoveg = 'szállodákat';
            }
            if(_get('velemenyezok')){
                szoveg = 'véleményezőket';
            }
            if(_get('projektigeny')){
                szoveg = 'projektigényléseket';
            }
            if(_get('projekt_team')){
                szoveg = 'felhasználókat';
            }

            if(torolhet){
                if(full_szoveg){
                    gomb_uzi = full_szoveg;
                }else{
                    gomb_uzi = 'Biztosan törli a kijelölt '+szoveg+'?';
                }
                gomb_muv   = '<p class="c"><input class="gomb zold" type="button" value="Igen" onclick="mellekTorol(\'' + tabla + '\', \'' + mellek + '\', \'' + mellekre_valt + '\');"><input class="gomb piros" type="button" value="Nem" onclick="alert2()"></p>';
                ablak_cime = '';
            }else{
                gomb_uzi   = 'Önnek nincs joga '+szoveg+' törölni!';
                gomb_muv   = '';
                ablak_cime = 'Hiba';

            }

            $('#navGombok a.torles').removeClass('disabled').attr('onclick', 'alert2(\'' + gomb_uzi + '\', gomb_muv, ablak_cime);');
        }
    }
}

function jobbOldalEltuntet( sorok ) {
    if( sorok.filter('.kijelolve').length == 1 ) {
        $('#szamlaHidden').html('').addClass('dn');
        $('#szamlaFulek, div.statusz div.text, div.naplo, div.jsff, #sorrendezes_tobbkep, #szerk_form div.dt').show();
    } else if( sorok.filter('.kijelolve').length > 1 ) {
        if( !$('#szamlaHidden').hasClass('csoport') ) {
            $('#szamlaHidden').html('Tájékoztató szöveg...').removeClass('dn');
        }
        $('#szamlaFulek, div.statusz div.text, div.naplo, div.jsff, #sorrendezes_tobbkep, #szerk_form div.dt').hide();
    }

    if( $('#szamlaList li.csoport.kijelolve').length ) {
        csoport_tartalom();
    } else {
        $('#szamlaHidden').removeClass('csoport');

        if( sorok.filter('.kijelolve').length > 1 ) {
            $('#szamlaHidden').html('Tájékoztató szöveg...');
        }
    }
}

/* Kapcsoló táblák - fülek alatti rekordok - törlése általános függvénye */
function mellekTorol( tabla, mellek, mellekre_valt, konkret_id ) {
    /* Ha van konkrét ID? akkor azt a sort töröljuk (pl. munkalapok adatlapján a bejelentések sorai) */
    if(konkret_id){
        var idk = {konkret_id};
    }else{
        var kijelolve = $('.kijCont .kijSor.kijelolve'),
            idk       = {};

        var i = 1;
        kijelolve.each(function() {
            var id = $(this).attr('data-id');

            /* Az ideiglenes soroknak nincs ID-ja, pontosabban az értéke UJ. Ezeket nem adjuk át az ajax-nak */
            if(id!='UJ'){
                idk[i] = id;
            }
            i++;
        });

    }

    /* Ha javaslattétel popuban vagyunk, akkor ott 'kep_jt_popup' a kép LI sor ID prefixe, próbáljuk meg azt is kihalványítani. */
    $('#kep_jt_popup'+konkret_id).fadeOut();
    /* Javaslattétel popupban sor eltűntetése  */
    if(mellekre_valt=='javaslattetel'){
        $('#kep'+konkret_id).fadeOut();
    }
    

    /* Ha vannak olyan ideiglenes soraink (aminek az Id-ja UJ), azokat elég csak eltűnteni
      Pl.: Projektigénylésnél a cash-flow fül */
    $('.kijCont .kijSor.kijelolve[data-id="UJ"], .kijCont .kijSor.kijelolve[data-id="UJ-FELDOLGOZVA"]').fadeOut('', function(){
        $(this).remove();
        /* Cash-flow-ban ha változás van, akkor újra kell számolni. Azért kell ide is, mert ajaxig nem biztos, hogy eljutunk. */
        if(tabla=='cash_flow'){
            becsult_szetosztott_ujraszamol();
            alert2();/* Cash flownál be kell zárni a kérdést is. */
        }
    });

    if( objLength( idk ) ) {
        $.ajax({
            url : 'esemenyek.php',
            type: 'POST',
            data: {
                recid       : _get('id'),
                tabla       : tabla,
                idk         : JSON.stringify(idk),
                mellekTorol : new Date().getTime()
            },
            success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if(response) {
                    response = JSON.parse(response);
                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    if( response.statusz == 'siker' ) {

                        if( response.alert ) {
                            alert2(response.alert, response.gomb);
                        }else{
                            alert2();
                        }

                        /* Munkalapok adatlapján a bejelentések törlésekor csak frissítünk */
                        if(tabla=='munkalap_kapcs'){
                            location.reload();
                            return false;
                        }

                        /* A számla betöltést csak akkor csináljuk meg, ha nincs ideiglenes sor hozzáadva, mert különben azok elvesznének */
                        if(!$('.kijCont .kijSor[data-id="UJ"]').length){

                            betoltott = szamlaBetolt(_get('id'), baseName(location.href) + '.php', mellek, '', 'kerdes_nelkul');
                            $.when( betoltott ).done(function() {
                                if(mellekre_valt){
                                    $('#ful_'+mellek).removeClass('active');
                                    $('#ful_'+mellekre_valt).addClass('active');
                                    $('#'+mellekre_valt+'_div').show();
                                    $('#szerk_form').hide();
                                }
                            });
                        }else{
                            $('.kijCont .kijSor.kijelolve').fadeOut('', function(){
                                $(this).remove();
                                /* Habár ez már fentebb szerepelt egyszer, azért kell ide, is, mert ha az ajax lefutott akkor tűnik el sor, akkor számoljuk újra */
                                if(tabla=='cash_flow'){
                                    becsult_szetosztott_ujraszamol();
                                }
                            });
                        }

                    }


                }
            }
        });
    }

}

function kijelolesEltavolit() {

    if( $('#szamlaList li.szamla_sor').hasClass('kijelolve') ) {
        $('#szamlaList li.szamla_sor').removeClass('kijelolve');

        $('.kijeloltek_muv').removeClass('active');

        $('#navGombok a.torles').attr('onclick', 'naviTorles();');

        $('ul.szamla_cont li.csoport').removeClass('kijelolve');

        if( _get('id') ) {
            $('#szamla-' + _get('id')).addClass('kijelolve');
        }
    }

    if( $('.kijCont .kijSor').hasClass('kijelolve') ) {
        $('.kijCont .kijSor').removeClass('kijelolve');
        $('#navGombok a.torles').addClass('disabled').attr('onclick', '');
    }

    jobbOldalEltuntet( $('#szamlaList li.szamla_sor') );
}

/* Helyiség választó popup tartalmának előállítása */
function obj_gen(szintig) {

    var telephely_id = $('#telephely_id').val();

    if(szintig){
        szintig = '1';
    }else{
        szintig = '';
    }

    if( telephely_id ) {
        $.ajax({
         	url : 'esemenyek.php',
         	type: 'GET',
         	data: esamp2es('telephely_id=' + telephely_id + '&amp;szintig=' + szintig + '&amp;obj_general=' + new Date().getTime()),
         	success: function(html) {
                if(html.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
         		if(html) {
                    $('#popupTart').html(html);
                    popupNyit();
         		}
         	}
        });
    } else {
        alert2('Kérjük előbb válasszon szállodát!');
        return false;
    }
}
/* Helyiség hozzáadása */
function addObj( that ) {
    var text  = $(that).text(),
        recid = $(that).attr('data-recid');

    $('input[name="obj_id"]').val(recid).change();
    $('input[name="obj_id_autocomp"]').val(text).change();
    /* Az autocomplete-t újra rá kell tenni, hogy az alapértelmezett értéke az üresség lehessen */
    $('#obj_id_autocomp').autocomplete({
        'php_fajl'      : '../autocomplete.php',
        'tabla'         : 'obj',
        'oszlop_keres'  : 'nev',
        'oszlop_listaz' : 'nev',
        'rkat'          : 0,
        'csoport'       : 0,
        'recid'         : '41',
        'id_kiir'       : 'recid'
    });

    var form = $('input[name="obj_id"]').parents('#szerk_form');

    stop = false;
    form.find('input, select, textarea').each(function() {
        var name = $(this).attr('name');

        if( stop ) {
            document.getElementById(name).select();
            return false;
        }

        if( name == 'obj_id' ) {
            stop = true;
        }
    });

    gepVane();
    popupZar();
}

/* Kapcsolódó táblák, azaz fülek alatti tartalom szerkesztéséhez kapcsolódó popupok, amiket sablonból olvasunk be */
function kapcsolodoSzerkeszt( oldal ) {

    $.get('../sablon/admin/popup/' + oldal + '.html?cache=' + new Date().getTime(), function( data ) {
        $('#popupTart').html(data);

        switch( oldal ) {
            case 'cash_flow':

                $.ajax({
                    url : 'projektigenyles.php',
                    type: 'POST',
                    data: {
                        recid               : _get('id'),
                        cash_flow_ellenoriz : new Date().getTime()
                    },
                    success: function (response) {
                        if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                        if (response) {
                            response = JSON.parse(response);

                            if( response.hiba ) {
                                alert2(response.hiba, response.gomb, response.title);
                                return false;
                            }else{
                                popupNyit();
                                return false;
                            }

                        }
                    }
                });
                return false;

            break;
            case 'ft_energia_mennyiseg':
                /* Fenntartási terv energia fülén ha egy sornak van futoertek class-ja, akkor arra a sorra meghívva a kitöltés gombot, biztosan hibaüzenetet kapunk (hogy nem szerkeszthető sor), így a popupot meg se jelenítsük. */
                if($('.szamla_cont_tabla tr.kijelolve').hasClass('futoertek')){
                    return false;
                }
            break;
            case 'megjegyzes':
                $('#addMegjegyzes').attr('onsubmit', 'return addMegjegyzes(\'' + _get('id') + '\', this);');
            break;
            case 'megterules':
                $('#addMegterules').attr('onsubmit', 'return addMegterules(\'' + _get('id') + '\', this);');
            break;
            case 'tarsreszlegek':
                $.ajax({
                 	url : 'login.php',
                 	type: 'POST',
                    data: {
                        recid           : _get('id'),
                        getReszlegek    : new Date().getTime()
                    },
                 	success: function(response) {
                        if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                 		if(response) {
                 			$('#addReszlegek').html(response);
                 		}
                 	}
                });
            break;
            
            case 'szakterulet':
                $('#addSzakterForm').attr('onsubmit', 'return addSzakterulet(\'' + _get('id') + '\', this);');
            break;

            case 'szallodak':
                $.ajax({
                 	url : 'login.php',
                 	type: 'POST',
                    data: {
                        recid           : _get('id'),
                        getSzallodak    : new Date().getTime()
                    },
                 	success: function(response) {
                        if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                 		if(response) {
                 			$('#addSzallodak').html(response);

                            /* Új felhasználó feltöltésekor van erre szükség: a már kiválasztott szállodák bepipálása */
                            if($('#szallodak_hidden').length && $('#szallodak_hidden').val()){
                                var szallodak = JSON.parse($('#szallodak_hidden').val());
                                for(var i in szallodak) {
                                    $('input[value='+szallodak[i]+']').prop("checked", true);
                                }
                            }
                 		}
                 	}
                });
            break;

            /* Véleménye feltötléskor pár adatra szükségünk van PHP-ból */
            case 'velemeny_feltolt':

                $.ajax({
                    url : 'projektigenyles.php',
                    type: 'POST',
                    data: {
                        get_session_id: new Date().getTime()
                    },
                    success: function (response) {
                        if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                        $('#popupTart').html(
                                            $('#popupTart').html().replace(/{GET_ID}/g, _get('id')).
                                            replace(/{SESSION_ID}/g, response)
                                        );
                    }
                });
            break;

        }

        popupNyit();
    });
}

function szallodakJelol( value ) {
    var telephelyek = $('input[name="telephely[]"]');

    $('input[name="telephely[]"]').prop('checked', false);
    if( value != 'urit' ) {
        telephelyek.each(function() {
            var pesti = parseInt( $(this).attr('data-pesti') );

            switch( value ) {
                case 'pesti':
                    if( pesti ) {
                        $(this).prop('checked', true);
                    }
                break;
                case 'videki':
                    if( !pesti ) {
                        $(this).prop('checked', true);
                    }
                break;
                case 'mind':
                    $(this).prop('checked', true);
                break;
            }
        });
    }
}

/* Popup bezárása */
function popupZar() {
    /* Anyagvételezés meg igénylésnél újratöltjük, mert összeakad a kettő */
    if($('#munkAnyagAnyagigenyles').length || $('#munkAnyagVetelezForm').length){
        $('#popupTart').html('');
        $('#popup').css('display', 'none');
    }else{
        $('#popupTart').html('');
        $('#popup').css('display', 'none');
    }
}

/* Popup megjelenítése és fókusz az első inputra */
function popupNyit() {
    $('#popup').css('display', 'block');
    $('#popup input:text, #popup textarea, #popup select').first().select();
}


/* Popup rejtése (nem töröljük a tartalmát, csak rejtük) */
function popupRejt() {
    $('#popup').hide();
}

/* Popup mutatása */
function popupMutat() {
    $('#popup').show();
    $('#popup input:text, #popup textarea, #popup select').first().select();
}

/* Popup villogtatása */
function popup_villogtat() {
    speed = 75;
    $('#popup .popup_kont, #alert2').fadeOut(speed).fadeIn(speed).fadeOut(speed).fadeIn(speed).fadeOut(speed).fadeIn(speed);
}

function inArray( keresett, tomb ) {
    if( $.isArray(tomb) && keresett !== undefined ) {
        for( key in tomb ) {
            if( keresett == tomb[key] ) {
                return true;
                break;
            }
        }
    }
    return false;
}

/* Navigáció előző */
function naviElozo() {
    $(document).trigger(jQuery.Event('keyup', {keyCode: KEY_UP}));
}

/* Navigáció következő */
function naviKov() {
    $(document).trigger(jQuery.Event('keyup', {keyCode: KEY_DOWN}));
}

function naviElso() {
    /* Anyagigénylésnél másképp járunk el */
    if( baseName(location.href)=='anyagigenyles' ){
        $('table.szamla_cont_tabla tbody tr:nth-of-type(2)').addClass('kijelolve').click();
        $('#tartalomJobb').scrollTop(0);
    }else{
        /* Bal oldali területen egy táblázat az aktív */
        if( /*$('.szamla_cont_tabla').length ||*/ aktiv_oldal == 'szamla_cont_tabla' ) {
            $('.szamla_cont_tabla tbody tr').eq(0).trigger('click');
            $('#tartalomJobb .fht-tbody').scrollTop(0);
        /* Navpanel az aktív felület */
        } else {
            $('#szamlaList li.szamla_sor').eq(0).find('a').trigger('click');
            $('#tartalomJobb').scrollTop(0);
        }
    }
}

function naviUtolso() {
    /* Anyagigénylésnél másképp járunk el */
    if( baseName(location.href)=='anyagigenyles' ){
        $('table.szamla_cont_tabla tbody tr:last-of-type').addClass('kijelolve').click();
        $('#tartalomJobb').scrollTop($('#tartalomJobb').prop('scrollHeight'));
    }else{
        /* Bal oldali területen egy táblázat az aktív */
        if( /*$('.szamla_cont_tabla').length ||*/ aktiv_oldal == 'szamla_cont_tabla' ) {
            $('.szamla_cont_tabla tbody tr:last-child').trigger('click');
            $('#tartalomJobb .fht-tbody').scrollTop($('#tartalomJobb .fht-tbody').prop('scrollHeight'));
        /* Navpanel az aktív felület */
        } else {
            $('#szamlaList li.szamla_sor:last-child a').trigger('click');
        }
    }
}

function naviSzerk() {
    if( $('#recordSzerk').length ) {
        $('#recordSzerk').click();
    }
}

function naviTorles() {
    if( $('#recordTorol').length ) {
        $('#recordTorol').click();
    }
}

/* Bal oldali navpanelben kijelölt sor magasságában legyen a scroll */
function scrollLeptet() {
    /* Húzogathatós táblázathoz */
    if( $('.sar-table').length ) {

        var active_top      = $('.szamla_cont_tabla tr.kijelolve').position().top,
            activeHeight    = $('.szamla_cont_tabla tr.kijelolve').outerHeight(),
            tartBalHeight   = $('#tartalomJobb').outerHeight();

        var theadHeight = $('.szamla_cont_tabla tr.kijelolve').parents('.sar-table').find('thead').outerHeight(),
            active_ct   = active_top - $('#tartalomJobb').scrollTop() - theadHeight;

        if( active_ct < 0 ) {
            active_ct = 0;
        }

        offset = 25;
        if( active_ct < offset ) {
            $('#tartalomJobb').scrollTop( $('#tartalomJobb').scrollTop() - activeHeight - 100 );
        } else if( (active_ct + activeHeight + theadHeight) > (tartBalHeight - offset) ) {
            $('#tartalomJobb').scrollTop( $('#tartalomJobb').scrollTop() + activeHeight + offset );
        }

    /* Jobb oldali területen található táblázat léptetése */
    } else if(aktiv_oldal == 'szamla_cont_tabla') {

        if( $('.szamla_cont_tabla tr.kijelolve').length ) {
            var active_top      = $('.szamla_cont_tabla tr.kijelolve').position().top,
                activeHeight    = $('.szamla_cont_tabla tr.kijelolve').outerHeight();

            if( $('#tartalomJobb .fht-tbody').length ) {
                var tartBalHeight = $('#tartalomJobb .fht-tbody').outerHeight();
            } else {
                var tartBalHeight = $('#szamlaBetolt').outerHeight();
            }

            /* Összeadjuk az active elem magasságát és a felső eltolását */
            var aTopH = active_top + activeHeight;

            if( (aTopH - activeHeight) < 0 ) {
                if( $('#tartalomJobb .fht-tbody').length ) {
                    $('#tartalomJobb .fht-tbody').scrollTop(0);
                } else {
                    $('#szamlaBetolt').scrollTop(0);
                }

                aTop = $('.szamla_cont_tabla tr.kijelolve').position().top;

                if( $('#tartalomJobb .fht-tbody').length ) {
                    $('#tartalomJobb .fht-tbody').scrollTop(aTop - 250);
                } else {
                    $('#szamlaBetolt').scrollTop(aTop - 250);
                }
            } else if( aTopH > tartBalHeight ) {
                if( $('#tartalomJobb .fht-tbody').length ) {
                    $('#tartalomJobb .fht-tbody').scrollTop(0);
                } else {
                    $('#szamlaBetolt').scrollTop(0);
                }

                aTop = $('.szamla_cont_tabla tr.kijelolve').position().top;

                if( $('#tartalomJobb .fht-tbody').length ) {
                    $('#tartalomJobb .fht-tbody').scrollTop(aTop - tartBalHeight + activeHeight + 250 );
                } else {
                    $('#szamlaBetolt').scrollTop(aTop - tartBalHeight + activeHeight + 250 );
                }
            }
        }

    /* Bal nav panel léptetése */
    } else {

        if( $('.szamla_sor').hasClass('kijelolve') ) {
            var active_top      = $('.szamla_sor.kijelolve').position().top,
                activeHeight    = $('.szamla_sor.kijelolve').outerHeight(),
                tartBalHeight   = $('#tartalomBal').outerHeight();

            /* Összeadjuk az active elem magasságát és a felső eltolását */
            var aTopH = active_top + activeHeight;

            if( (aTopH - activeHeight) < 0 ) {
                $('#tartalomBal').scrollTop(0);
                aTop = $('.szamla_sor.kijelolve').position().top;
                $('#tartalomBal').scrollTop(aTop - 250);
            } else if( aTopH > tartBalHeight ) {
                $('#tartalomBal').scrollTop(0);
                aTop = $('.szamla_sor.kijelolve').position().top;
                $('#tartalomBal').scrollTop(aTop - tartBalHeight + activeHeight + 250 );
            }
        }
    }
}

// Rendezés select onchange-re hívódik meg
var lista = '';
function bejegyzesekLista( that, irany, rkat, ksz ) {
    if( rkat === undefined ) {
        rkat = '';
    }

    if( ksz === undefined || ksz == '' ) {
        ksz = '';
    } else {
        ksz = $(ksz).val();
    }

    var rend  = $(that).val(),
        tabla = $(that).attr('data-tabla');

    if( irany === undefined ) {
        irany = 'asc';
    }

    var recid = '';
    if( $('.szamla_sor').hasClass('kijelolve') ) {
        recid = $('.szamla_sor.kijelolve').attr('data-recid');
    }

    if( tabla ) {
        szamlaSorok = $('#szamlaList li.szamla_sor.kijelolve');

        if( szamlaSorok.length ) {
            idk = {};

            var i = 1;
            szamlaSorok.each(function() {
                var id = $(this).attr('data-recid');

                idk[i] = id;
                i++;
            });
        }

        var param_kieg = '';
        // Anyagrendelésnél ezt a getet is tovább kell vinni
        if(_get('anyagrend')){
            param_kieg += '&amp;anyagrend=';
        }
        // Szűrés ne felejtődjön el
        if( _get('mezo') ){
            param_kieg += '&amp;mezo='+_get('mezo');
        }
        if( _get('op') ){
            param_kieg += '&amp;op='+_get('op');
        }
        if( _get('felt') ){
            param_kieg += '&amp;felt='+_get('felt');
        }
        lista = $.ajax({
            url : 'esemenyek.php',
            type: 'GET',
            data: esamp2es('id=' + recid + '&amp;kijeloltek=' + JSON.stringify(idk) + '&amp;ksz=' + ksz + '&amp;rkat=' + rkat + '&amp;rend=' + rend + '&amp;irany=' + irany + '&amp;tabla=' + tabla + param_kieg + '&amp;szamlaLista=' + new Date().getTime()),
            success: function(html) {
                if(html.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if(html) {
                    $('#tartalomBal').html(html).scrollTop(0);

                    ksz_kieg = '';
                    if( ksz ) {
                        ksz_kieg = '&ksz=' + ksz;
                    }

                    param = get_get(['rend', 'irany', 'ksz'], true);
                    if( param.indexOf('?') !== -1 ) {
                        param += '&rend=' + rend + '&irany=' + irany + ksz_kieg;
                    } else {
                        param = '?rend=' + rend + '&irany=' + irany + ksz_kieg;
                    }

                    window.history.pushState('', '', getHost() + param );
                }
            }
        });

        $.when( lista ).then(function() {
            if( irany == 'asc' ) {
                ri = 'desc';
                rt = '▲ <span>A-Z</span>';
            } else if( irany == 'desc' ) {
                ri = 'asc';
                rt = '▼ <span>Z-A</span>';
            }

            $('#rendezesIrany').attr('href', 'javascript: bejegyzesekLista( \'#rendezesSelect\', \'' + ri + '\', \'' + rkat + '\' ); void(0);').html(rt);

            if( $('.szamla_sor').hasClass('kijelolve') ) {
                active_top = $('.szamla_sor.kijelolve').position().top;
                $('#tartalomBal').scrollTop(active_top - 250);

            }

            /* Megtriggerjük a keresés mezőt, hogy leszűkítsen */
            if( $('#rendezesKeres').val() ) {
                $('#rendezesKeres').trigger('keyup');
            }
        });
    }
}

function getHost() {
    var link = location.href;

    if( link.indexOf('?') !== -1 ) {
        host = link.split('?')[0];
    } else {
        host = link;
    }

    return host;
}

function gombokNullaz() {
    $('#szerk_form').find('input, select, textarea').each(function() {
        $(this).removeClass('mod');
    });

    $('#visszavonas_gomb').attr('disabled', true);
    $('#kilepes_gomb').val('Kilépés');
}

/* Felhasználó váltás */
function felhValt( that ) {
    var felh_nev  = $( that ).find('input[name="login_nev"]').val(),
        felh_jlsz = $( that ).find('input[name="login_jelszo"]').val();

    if( felh_nev ) {
        $.ajax({
         	url : 'beleptetes.php',
         	type: 'POST',
         	data: esamp2es('nev=' + felh_nev + '&amp;jelszo=' + felh_jlsz + '&amp;ajax=1&amp;login=' + new Date().getTime()),
         	success: function(html) {
                if(html.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
         		if(html) {
         			html = JSON.parse(html);

                    if( html.statusz == 'hiba' ) {
                        if( html.link ) {
                            szurkit();
                            location.href = html.link;
                            return false;
                        }

                        $('#beleptet_alert div.header').html('<h1 style="color: #FF5353">Helytelen felhasználó név vagy jelszó!</h1');
                        return false;
                    }

                    /* Bejelentőket egyből oda is léptetjük */
                    szurkit();
                    if( html.statusz == 'bejelento' ) {
                        location.href = 'bejelentes.php';
                    }else{
                        location.href = 'index.php';
                    }

         		}
         	}
        });
    }

    return false;
}

/* Szakterület hozzáadása */
function addSzakterulet( szallitoID, that ) {
    var szakterID = $(that).find('input[name="addSzakter"]').val();

    if( szallitoID && szakterID ) {
        var ajax = $.ajax({
         	url : 'esemenyek.php',
         	type: 'GET',
         	data: esamp2es('szallitoID=' + szallitoID + '&amp;szakterID=' + szakterID + '&amp;addSzakterulet=' + new Date().getTime()),
         	success: function(html) {
                if(html.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
         		if(html) {
                    html = JSON.parse(html);

                    if( html.statusz == 'hiba' ) {
                        alert2(html.uzi, '', 'Hiba');
                        return false;
                    }

         			szamlaBetolt(szallitoID, baseName(location.href) + '.php', 'szakterulet').done(function() {
                        $('#naploSor-' + html.kapcsID).addClass('uj');
                    });

                    popupZar();
         		}
         	}
        });

        $.when( ajax ).then(function() {
            $(that).find('input[name="addSzakter"]').val('');
            $(that).find('input[name="addSzakter_autocomp"]').val('');
        });
    }

    return false;
}


/* Megjegyzés hozzáadása */
function addMegjegyzes( pageID, that ) {
    var megj = $(that).find('textarea[name="megjegyzes"]').val();

    if( pageID ) {
        var ajax = $.ajax({
         	url : 'esemenyek.php',
         	type: 'POST',
         	data: {
                pageID       : pageID,
                megj         : megj,
                tabla        : baseName(location.href),
                addMegjegyzes: new Date().getTime()
            },
         	success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
         		if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, 'Hiba');
                        return false;
                    }

         			szamlaBetolt(pageID, baseName(location.href) + '.php', 'megjegyzesek').done(function() {
                        $('#naploSor-' + response.kapcsID).addClass('uj');
                    });

                    popupZar();
         		}
         	}
        });

        $.when( ajax ).then(function() {
            $(that).find('input[name="megjegyzes"]').val('');
        });
    }

    return false;
}

/* Login hozzárendelése projektkezeléshez */
function addProjektTeam( prkezel_recid, that ) {

    var loginok = $(that).find('input[name="loginok[]"]:checked');
    var data    = {};
    loginok.each(function(i) {
        var value = $(this).val();
        data[i] = value;
    });

    if( prkezel_recid && objLength(data) ) {
        var ajax = $.ajax({
         	url : 'projektkezeles.php',
         	type: 'POST',
         	data: esamp2es('prkezel_recid=' + prkezel_recid + '&amp;loginok=' + JSON.stringify(data) + '&amp;addProjektTeam=' + new Date().getTime()),
         	success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
         		if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, '', 'Hiba');
                        return false;
                    }

         			szamlaBetolt(prkezel_recid, baseName(location.href) + '.php', 'projekt_team').done(function() {

                    });

                    popupZar();
         		}
         	}
        });
    }

    return false;
}

/* Vélemény hozzáadása */
function velemeny_hozzaad(mehet){

    /* Ha mehet, akkor nem vizsgáunk semmit, mehet a vélemény feltöltés */
    if(mehet){
        alert2();
        kapcsolodoSzerkeszt('velemeny_feltolt');
    }else{

        /* Lássuk, van-e már vélemény feltöltve, ha igen, akkor rákérdezünk, hogy biztosan? */
        $.ajax({
            url : 'projektigenyles.php',
            type: 'POST',
            data: {
                recid         : _get('id'),
                van_e_velemeny: new Date().getTime()
            },
            success: function (response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                response = JSON.parse(response);
                /* Ha még nincs véleménye, akkor nincs kérdés, mehet egyből */
                if (response.mehet) {
                    kapcsolodoSzerkeszt('velemeny_feltolt');
                }
                if (response.kerdes) {
                    alert2(response.kerdes, '<input type="button" class="gomb zold" value="Igen" onclick="velemeny_hozzaad(\'mehet\')"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">');
                }
            }
        });

    }

}

/* Vélemény törlése */
function velemeny_torol( velemeny_recid, mehet ) {
    if(!mehet){
        alert2('Biztosan törli a véleményét?', '<input type="button" class="gomb zold" value="Igen" onclick="velemeny_torol(\''+velemeny_recid+'\', \'mehet\')"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">');
    }else{
        $.ajax({
            url : 'projektigenyles.php',
            type: 'POST',
            data: {
                velemeny_recid: velemeny_recid,
                velemeny_torol: new Date().getTime()
            },
            success: function (response) {

                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                response = JSON.parse(response);

                if( response.hiba ) {
                    if( response.gomb ) {
                        alert2(response.hiba, response.gomb);
                    } else {
                        alert2(response.hiba, '', 'Hiba');
                    }
                }else{
                    szamlaBetolt(_get('id'), 'projektigenyles.php', 'velemenyezok');
                    alert2();
                }

            }
        });
    }
}

function naploTorol( tabla, sorID ) {
    if( tabla && sorID ) {
        $.ajax({
         	url : 'esemenyek.php',
         	type: 'GET',
         	data: esamp2es('tabla=' + tabla + '&amp;sorID=' + sorID + '&amp;naploTorol=' + new Date().getTime()),
         	success: function(html) {
                if(html.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
         		$('#naploSor-' + sorID).fadeOut(300, function() {
                    $(this).remove();
                });
         	}
        });
    }
}

function gombokRejt() {
    if( $('#recordSzerk').length ) {
        $('div.navGombok a.szerk').removeClass('disabled');
    } else {
        $('div.navGombok a.szerk').addClass('disabled');
    }

    if( _get('modosit') ) {
        $('div.navGombok a.torles').removeClass('disabled').attr('onclick', 'naviTorles();');
    } else {
        $('div.navGombok a.torles').addClass('disabled');
    }

    /* Új feltöltéskor az Új ikont disabled... */
    if( _get('uj') ) {
        /* Kivéve projekt szerkesztéskor */
        if( baseName(location.href)=='projektkezeles' ){
            $('div.navGombok a.uj').removeClass('disabled');
        }else{
            $('div.navGombok a.uj').addClass('disabled');
        }
    } else {
        $('div.navGombok a.uj').removeClass('disabled');
    }
}

function _get( ertek ) {
    var link  = location.href;

    if( link.indexOf('?') != -1 ) {
        /* URL szétbontása */
        link = link.split('?');

        /* Paraméterek szétbontása */
        akt_param = link[1].split('&');

        for( key in akt_param ) {
            var p = akt_param[key];

            if( akt_param[key].indexOf('=') != -1 ) {
                var get = akt_param[key].split('=');

                if( get[0] == ertek ) {
                    return get[1];
                }
            } else {
                if( akt_param[key] == ertek ) {
                    return true;
                }
            }
        }
    } else {
        return false;
    }

    return false;
}

function egysegarak_nyomtat() {
    window.open(get_get() + '&print=lista&type=egysegarak_lista', '_blank');
}

function listanezet_nyomtat( that ) {
    var ids = new Array();

    i = 0;
    $('#szamlaList li.szamla_sor').each(function() {
        var display = $(this).css('display');

        if( display != 'none' ) {
            ids[i] = $(this).attr('data-recid');

            i++;
        }
    });

    var ajax = $.ajax({
     	url : 'esemenyek.php',
     	type: 'POST',
     	data: {
            data         : ids.join(','),
            nyomtatas_ids: new Date().getTime()
        }
    });

    $.when(ajax).done(function() {
        if( ids.length ) {
            var onclick = $(that).attr('onclick');

            $(that).attr('href', get_get() + '&print=lista').removeAttr('onclick');
            $(that)[0].click();
            $(that).attr('onclick', onclick).removeAttr('href');
        } else {
            alert2('Üres találati listát nem lehet nyomtatni!', '', 'Hiba');
        }
    });
}

function csv_general( that, valasztos, link_kieg ) {

    if(!link_kieg){
        link_kieg = '';
    }

    if(valasztos){

        /* Energia tervnél lehet választani, hogy tervet, vagy Egységárakat szeretne-e nyomtatni */
        if( baseName(location.href) == 'energia_terv' ){
            alert2('Válasszon export fajtát:<br><a href="javascript:csv_general($(\'.excel\'))">» Energia tervek exportálása</a><br><a href="javascript:csv_general($(\'.excel\'), \'\', \'egysegarak&id=\'+_get(\'id\'))">» Egységárak exportálása</a>', '<input type="button" class="gomb" value="Mégsem" onclick="alert2()">', 'Válasszon exportot');
        }

        /* Projektszerződéseknél lehet választani, hogy Szerződések vagy Szerződések és tételek */
        if( baseName(location.href) == 'projektszerzodes' ){
            alert2('Válasszon export fajtát:<br><a href="javascript:csv_general($(\'.excel\'))">» Szerződések exportálása</a><br><a href="javascript:csv_general($(\'.excel\'), \'\', \'tetelek&id=\'+_get(\'id\'))">» Szerződések és tételek exportálása</a>', '<input type="button" class="gomb" value="Mégsem" onclick="alert2()">', 'Válasszon exportot');
        }

    }else{

        var ids = new Array();

        i = 0;
        $('#szamlaList li.szamla_sor').each(function() {
            var display = $(this).css('display');

            if( display != 'none' ) {
                ids[i] = $(this).attr('data-recid');

                i++;
            }
        });

        var ajax = $.ajax({
            url : 'esemenyek.php',
            type: 'POST',
            data: {
                data         : ids.join(','),
                nyomtatas_ids: new Date().getTime()
            }
        });

        $.when(ajax).done(function() {
            if( ids.length ) {
                var onclick = $(that).attr('onclick');

                $(that).attr('href', get_get(['egysegarak']) + '&csv&'+link_kieg).removeAttr('onclick');
                $(that)[0].click();
                $(that).attr('onclick', onclick).removeAttr('href');
            } else {
                alert2('Üres találati listát nem lehet exportálni!', '', 'Hiba');
            }
        });

    }

}

/* Adatlap betöltése */
var request_in_process = false;
function szamlaBetolt( recid, page, muvelet, szerkeszt, kerdes_nelkul, anyagigenylesnel_is ) {

    /* Mindenképp legyen rajta egy szurkítés */
    setTimeout(function(){ szurkit(); }, 100);

    focused = false; /* Ezt azért kell hamisra állítani, mert alert2 bezárásakor az utoljára fókuszált elemre visszatesszük a fókuszt.
                        Viszont ezt az anyagigénylések táblázatánál gondot okozott: nem lehetett kilépni az árazva sorok inputjaiból.
                        */
    alert2('');

    if( formModositva && !kerdes_nelkul ) {
        alert2('Biztosan elhagyja ezt '+a_az($('#rekord_neve_targy').html())+' '+$('#rekord_neve_targy').html()+' mentés nélkül?', '<input class="gomb zold" value="Igen" onclick="formModositva = false; szamlaBetolt( \'' + recid + '\', \'' + page + '\', \'' + muvelet + '\', \'' + szerkeszt + '\' ); $(\'#szamlaList li.szamla_sor\').removeClass(\'kijelolve\');" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">');
        szurkit_zar();
        return false;
    }

    if( szerkeszt === undefined ) {
        szerkeszt = false;
    }

    szerk = '';
    if( szerkeszt ) {
        szerk = 'szerkeszt=1&amp;';
    }

    var dfrd1 = $.Deferred();

    if( recid && page ) {

        /* Ha létezik az anyagrend GET, akkor ezt tovább kell adni */
        anyagrend = '';
        if(_get('anyagrend')){
            anyagrend = 'anyagrend=1&amp;';
        }

        /* Anyagigénylés alatt a művelet ha táblázat, nem frissítjük újra az egész oldal tartalmát (táblázat sorain lépkedve ne frissüljenek az adatok)
         * De az anyagigénylés fülre kattintva mégis be kell tölteni, erre hasznájuk a  "tablazat_" műveletet */
        muvelet_param = muvelet;
        if(muvelet=='tablazat_'){
            muvelet_param = 'tablazat&amp;teljes_tartalom=1';
        }

        /* Anyagigénylésnél a szűrés paramétereket is tovább kell még adni */
        if( page=='anyagigenyles.php' ){
            if(_get('mezo')){
                anyagrend += 'mezo='+_get('mezo')+'&amp;';
            }
            if(_get('op')){
                anyagrend += 'op='+_get('op')+'&amp;';
            }
            if(_get('felt')){
                anyagrend += 'felt='+_get('felt')+'&amp;';
            }
        }

        /* Eszközkezelésnél linkekre active class-t tenni */
        if( page=='eszkozkezeles.php' ){
            $('.konyvtar_szerk a, .konyvtar_szerk li').removeClass('active');
            if(muvelet=='obj'){
                $('#obj_'+recid).addClass('active');
            }else{
                $('#eszkoz_'+recid).addClass('active');
            }
        }

        /* Ha nem ismert a művelet, akkor találjuk ki. Ez arra az esetre jó, ha nem tudjuk, melyik fül alatt vagyunk, és maradni szeretnénk adott fül alatt.
         *  Ekkor ne paraméterezzük fel a szamlaBetolt() függvényt művelettel.
         *  */
        if(!muvelet_param){
            if(_get('modosit')      ){ muvelet_param = 'modosit';       }
            if(_get('megjegyzesek') ){ muvelet_param = 'megjegyzesek';  }
            if(_get('dokumentumok') ){ muvelet_param = 'dokumentumok';  }
            if(_get('tortenet')     ){ muvelet_param = 'tortenet';      }

            /* Ha így sincs, akkor legyen a módosítás */
            if(!muvelet_param){
                muvelet_param = 'modosit';
            }

        }

        var no_lista = '&amp;no_lista=1';
        if(anyagigenylesnel_is || page=='anyagigenyles.php'){
            no_lista = '';
        }

        request_in_process = true;
        var szamla = $.ajax({
         	url : page,
         	type: 'GET',
         	data: esamp2es('id=' + recid + no_lista+'&amp;ajax=1&amp;' + szerk + anyagrend + muvelet_param + '=' + new Date().getTime()),
         	success: function(html) {
                if(html.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
         		if(html) {
                    html = JSON.parse(html);

                    /* #szamlaBetolt tartalmának lecserélése
                     * Ha anyagigénylés táblázat alatt vagyunk akkor ne frissítsük a tartalmat, csak a gombokat.
                     */
                    if( page=='anyagigenyles.php' && muvelet=='tablazat' ){
                        $('#szamlaFulek').html(html.fulek);
                        $('div.muveletek').replaceWith(html.tartalom).promise().done(function() {
                            dfrd1.resolve();
                        });

                    }else{
                        $('#szamlaBetolt').attr('data-recid', recid).html(html.tartalom).promise().done(function() {
                            dfrd1.resolve();
                        });
                    }

                    param = get_get([
                        'akt_nap',
                        'anyagigenyles',
                        'bejelentesek',
                        'beosztasok',
                        'bwh',
                        'cash_flow',
                        'dokumentumok',
                        'egysegarak',
                        'elszamolas',
                        'energia',
                        'engedelyek',
                        'esemenyek',
                        'eseti_karban',
                        'ertesites',
                        'felmeresi',
                        'garancialis',
                        'hibaelharitas',
                        'id',
                        'jogosultsag',
                        'megjegyzes',
                        'megjegyzesek',
                        'megterules',
                        'miniprojekt',
                        'modosit',
                        'modosit_ules',
                        'munkalapok',
                        'muszaki_anyag',
                        'naplo',
                        'naptar',
                        'obj',
                        'osszesen',
                        'uj',
                        'utemezes',
                        'projekt_team',
                        'projektigeny',
                        'rb_anyag',
                        'rb_anyag',
                        'rendsz_karban',
                        'rogzit',
                        'siker',
                        'statusz',
                        'szamlak',
                        'szabadsag',
                        'szallodak',
                        'szerkeszt',
                        'szakterulet',
                        'tablazat',
                        'terkep',
                        'tortenet',
                        'tetel',
                        'ujkent',
                        'velemenyezok'
                    ], true);

                    if( muvelet != 'uj' ) {

                        if( param.indexOf('?') !== -1 ) {
                            param += '&id=' + recid + ( esamp2es(muvelet) ? '&' + esamp2es(muvelet) : '' );
                        } else {
                            param = '?id=' + recid + ( esamp2es(muvelet) ? '&' + esamp2es(muvelet) : '' );
                        }

                        if( szerkeszt ) {
                            param += '&szerkeszt';
                        }
                    } else {
                        param += '?uj';
                    }

                    /* Bal oldali navpanel sorát is frissítjük */
                    if(page!='anyagigenyles.php' && page.replace('.php', '')!='erteklista'){
                        frissit_tabla = page.replace('.php', '');

                        if( _get('obj') ) {
                            frissit_tabla = 'obj';
                        }

                        szamlaSorFrissit(frissit_tabla, recid);
                    }

                    /* URL mod */
                    window.history.pushState("", "", getHost() + param );

                    /* Táblázatnál le kel venni a paddingot */
                    if(page=='anyagigenyles.php') {
                        if( _get('tablazat') ) {
                            $('#szamlaBetolt').addClass('nopadd');
                        } else {
                            $('#szamlaBetolt').removeClass('nopadd');
                        }
                    }

                    /* Új ikonra kattintva mi történik */
                    if( _get('szakterulet') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'szakterulet\'); void(0);');

                    } else if( baseName(location.href) == 'anyagigenyles' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'anyagigenyles' && !_get('megjegyzesek') && _jogok[13]==0 ) {
                        $('#navGombok a.uj').attr('href', 'javascript: alert2(\'Önnek nincs jogosultsága új anyagigénylés feltöltéséhez!\', \'\', \'Hiba\'); void(0);');

                    } else if( baseName(location.href) == 'anyagigenyles' && _get('anyagrend') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: alert2(\'Új anyagigénylés csak az anyagigénylés menüpont alatt tölthető fel!\', \'\', \'Hiba\'); void(0);');

                    } else if( baseName(location.href) == 'beosztas' && _get('naptar') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: $(\'#szerk_form div.dt\').show(); void(0);');

                    } else if( baseName(location.href) == 'beosztas' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'bejelentes' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'bejelentes' && _jogok[56] == 1 && _jogok[1] == 1 ) {
                        $('#navGombok a.uj').attr('href', 'javascript: bejSajatMasneven(); void(0);');

                    } else if( baseName(location.href) == 'bejelentes' && _jogok[56] == 1 ) {
                        $('#navGombok a.uj').attr('href', 'javascript: bejSzallodaValaszt(); void(0);');

                    } else if( baseName(location.href) == 'cikktorzs' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'energia_terv' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'energia_terv' ) {
                        $('#navGombok a.uj').attr('href', 'javascript: csoportMuvelet(\'terv_keszites_jellemzoi\'); void(0);');

                    } else if( baseName(location.href) == 'eloterj_fb' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'eloterj_fb' && _get('projektigeny') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: csoportMuvelet(\'eloterj_igneyles_csatol\'); void(0);');

                    } else if( baseName(location.href) == 'eloterj_bod' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'eloterj_bod' && _get('projektigeny') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: csoportMuvelet(\'eloterj_igneyles_csatol\'); void(0);');

                    } else if( baseName(location.href) == 'energia' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'eseti_fenntartas' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'eszkozkezeles' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'felmeresi_terv' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'fenntartasi_szerzodes' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'fenntartasi_terv' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'fenntartasi_terv' ) {
                        $('#navGombok a.uj').attr('href', 'javascript: csoportMuvelet(\'fenntartasi_terv_feltolt\'); void(0);');

                    } else if( baseName(location.href) == 'login' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'munkalap' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    /* Munkalapon, ha nem a megjegyzés alatt vagyunk, és a usernek van előkészítés jog, akkor feljön egy popup, hogy válasszon, mit szeretne tenni */
                    } else if( baseName(location.href) == 'munkalap' && !_get('megjegyzesek') && _jogok[37] == 1 ) {
                        $('#navGombok a.uj').attr('href', 'javascript: uj_munkalap_elokeszites_joggal(); void(0);');

                    } else if( baseName(location.href) == 'munkalap' && !_get('megjegyzesek') && _jogok[5] == 0 ) {
                        $('#navGombok a.uj').attr('href', 'javascript: alert2(\'Önnek nincs joga új munkalapot létrehozni!\'); void(0);');

                    } else if( baseName(location.href) == 'rendszeres_karbantartas' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'raktar' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'projektigenyles' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'projektkezeles' && _get('projekt_team') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'projekt_team\'); void(0);');

                    } else if( baseName(location.href) == 'projektkezeles' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'projektkezeles' && (_get('modosit') || _get('tortenet') || _get('dokumentumok') || _get('cash_flow') || _get('projektigeny') || _get('rb_anyag') || _get('garancialis') ) ) {
                        $('#navGombok a.uj').attr('href', 'javascript: csoportMuvelet(\'projektkezel_uj_feltolt\'); void(0);');

                    } else if( baseName(location.href) == 'projektigenyles' && _get('megterules') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megterules\'); void(0);');

                    } else if( baseName(location.href) == 'projektszerzodes' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'szamla' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'szallito' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'tervezes' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'torzsadat' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( _get('szallodak') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'szallodak\'); void(0);');

                    } else if( baseName(location.href) == 'utemezett_feladatok' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( _get('velemenyezok') && _jogok[40] == 0 ) {
                        $('#navGombok a.uj').attr('href', 'javascript: alert2(\'Önnek nincs joga véleményezőt hozzáadni!\', \'\', \'Hiba\'); void(0);');

                    } else if( _get('velemenyezok') && _jogok[40] == 1 ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'velemenyezok\'); void(0);');

                    } else {
                        $('#navGombok a.uj').attr('href', baseName(location.href) + '.php?uj');
                    }

                    if( isMobile ) {
                        $('#tartalomBal').css('display', 'none');
                        $('#tartalomJobb').css('display', 'block');
                        $('#respo_visszalepes').css('display', 'block');
                    }

                    $('body').removeClass('ujfeltoltes');

                    /* Ha épp egy csoporton állunk, akkor elrejtük a jobb oldali tartalmakat */
                    $('#szamlaList li.csoport').each(function () {
                        if($(this).hasClass('kijelolve')){
                            csoport_tartalom();
                        }
                    });

                    $('#szamlaHidden').html('').addClass('dn').removeClass('csoport');

                    request_in_process = false;
         		} else {
                    alert2('Nem lehet betölteni a kért oldalt!', '', 'Hiba');
                    dfrd1.resolve();
                }
         	}
        });

        return $.when(szamla, dfrd1).done(function() {
            formModositva = false;
            gombokRejt();
            scrollLeptet();
            fulGombMeretez();

            fixTable();

            setTimeout(function(){ szurkit_zar(); }, 100);
        }).promise();
    }

}

function respoVisszalepes() {
    $('#tartalomBal').css('display', 'block');
    $('#tartalomJobb').css('display', 'none');
    $('#respo_visszalepes').css('display', 'none');
}

/* Bal oldali navpanel egy sorának frissítése */
function szamlaSorFrissit( tabla, recid ) {
    if( tabla && recid ) {

        var anyagrend = '';

        /* Anyagrendelés miatt figyelni kell, hogy van-e $_GET['anyagrend'] */
        param_kieg = '';
        if(_get('anyagrend')){
            param_kieg = '&amp;anyagrend';
        }

        $.ajax({
         	url : 'esemenyek.php',
         	type: 'GET',
         	data: esamp2es('tabla=' + tabla + '&amp;recid=' + recid + param_kieg + '&amp;no_lista=1&amp;szamlaSorFrissit=' + new Date().getTime()),
         	success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if(response) {
                    if( tabla == 'eszkozkezeles' || tabla == 'obj' ) {
                        response = JSON.parse(response);

                        switch(tabla) {
                            case 'eszkozkezeles':
                                $('#' + tabla + '-' + recid).html(response.nev);
                            break;
                            case 'obj':
                                $('#' + tabla + '_' + recid).html(response.nev);
                            break;
                        }
                    } else {
                        /* A frissítendő sor data-i értéke maradjon változatlan. Amit kapunk abban lesz az értéke */
                        response = response.replace('data-i="1"', 'data-i="' + $('#szamla-' + recid).attr('data-i') + '"');
             			$('#szamla-' + recid).replaceWith(response);
                    }
         		}
         	}
        });
    }
}

/* Könyvtárszerkezetek esetén a komplett bal oldalt újra kell számolni */
function bal_panel_frissit( php, recid, obj ) {
    $.ajax({
        url : php+'?id='+recid,
        type: 'POST',
        data: {
            obj: obj,
            bal_panel_frissit: new Date().getTime()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                $('#tartalomBal').html(response.html);
            }
        }
    });
}

function htmlspecialchars_decode(string, quoteStyle) {
    if(string){
        var optTemp = 0;
        var i = 0;
        var noquotes = false;
        if(typeof quoteStyle === 'undefined') {
            quoteStyle = 2;
        }
        string = string.toString().replace(/&lt;/g, '<').replace(/&gt;/g, '>');
        var OPTS = {
            'ENT_NOQUOTES': 0,
            'ENT_HTML_QUOTE_SINGLE': 1,
            'ENT_HTML_QUOTE_DOUBLE': 2,
            'ENT_COMPAT': 2,
            'ENT_QUOTES': 3,
            'ENT_IGNORE': 4
        };

        if(quoteStyle === 0) {
            noquotes = true;
        }

        if(typeof quoteStyle !== 'number') {
            quoteStyle = [].concat(quoteStyle);
            for (i = 0; i < quoteStyle.length; i++) {
                if(OPTS[quoteStyle[i]] === 0) {
                    noquotes = true;
                } else if(OPTS[quoteStyle[i]]) {
                    optTemp = optTemp | OPTS[quoteStyle[i]];
                }
            }
            quoteStyle = optTemp;
        }

        if(quoteStyle & OPTS.ENT_HTML_QUOTE_SINGLE) {
            string = string.replace(/&#0*39;/g, "'");
        }

        if(!noquotes) {
            string = string.replace(/&quot;/g, '"');
        }

        string = string.replace(/&amp;/g, '&');
    }
    return string;
}

function moveCaretToEnd(el) {
    if (typeof el.selectionStart == "number") {
        el.selectionStart = el.selectionEnd = el.value.length;
        el.focus();
    } else if (typeof el.createTextRange != "undefined") {
        el.focus();
        var range = el.createTextRange();
        range.collapse(false);
        range.select();
    }
}

/* Annak ellenőrzése, hogy a szálloda ki van-e már választva */
function szallodaVane( that ) {
    if(!$(that).attr('readonly')){
        var telephely_id = $('#telephely_id').val();

        if( !telephely_id ) {
            if( $(that).attr('name') == 'obj_id_autocomp' ) {
                $('#obj_id_autocomp').attr('readonly', true);

                alert2('Kérem előbb válasszon szállodát!');
                return false;
            }
        } else {
            if( $(that).attr('name') == 'telephely_id' ) {
                $('#obj_id_autocomp').attr('readonly', false).val('').removeClass('mod');
                $('#obj_id').val('');

                /* Az autocomplete-t újra rá kell tenni, hogy az alapértelmezett értéke az üresség lehessen */
                $('#obj_id_autocomp').autocomplete({
                    'php_fajl'      : '../autocomplete.php',
                    'tabla'         : 'obj',
                    'oszlop_keres'  : 'nev',
                    'oszlop_listaz' : 'nev',
                    'rkat'          : 0,
                    'csoport'       : 0,
                    'recid'         : '41',
                    'id_kiir'       : 'recid'
                });

                $('#gep_id_autocomp').val('').removeClass('mod');
                $('#gep_id').val('');
            }
        }
    }
}

/* Annak ellenőrzése, hogy a helyiség ki van-e már választva */
function helyisegVane( that ) {
    if(!$(that).attr('readonly')){
        var telephely_id = $('#telephely_id').val();
        var helyiseg = $('#obj_id_autocomp').val();

        if( !helyiseg || !telephely_id ) {
            if( $(that).attr('name') == 'gep_id_autocomp' ) {
                $(that).attr('readonly', true);
            }

            if( !telephely_id ) {
                alert2('Kérem előbb válasszon szállodát!');
                return false;
            }

            alert2('Kérem előbb válasszon helyiséget!');
            return false;
        } else {
            $('#gep_id_autocomp').attr('readonly', false);
        }
    }
}

/* Annak ellenőrzése, hogy a gép ki van-e már választva */
function gepVane() {

    var telephely_id    = $('#telephely_id').val(),
                obj_id  = $('#obj_id').val();

    $.ajax({
        url : 'esemenyek.php',
        type: 'GET',
        data: esamp2es('telephely_id=' + telephely_id + '&amp;obj_id=' + obj_id + '&amp;gepVane=' + new Date().getTime()),
        success: function(html) {
            if(html.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if( html ) {
                html = JSON.parse(html);

                if( html.statusz ) {
                    $('#gep_id_autocomp').attr('readonly', false).val('').removeClass('mod');
                    $('#gep_id').val('');
                } else {
                    $('#gep_id_autocomp').attr('readonly', true).val('').removeClass('mod');
                    $('#gep_id').val('');
                }
            }
        }
    });
}

/* Inline naptárhoz - már nem kell */
/*$(document).ready(function () {
    $('.naptar').datetimepicker({
        format      : 'Y-m-d',
        timepicker  : false,
        inline      : true,
        lang        : 'hu',
        onChangeDateTime : function(dp, $input) {
            var val = $input.val();
            szamlaBetolt('1', 'utemezett_feladatok.php', 'naptar=1&amp;akt_nap='+val);
        },
    });

    scrollLeptet();
});*/

/* Időszakos ütemezésnél az egyeszeri időszak kinullázása */
function egyszeri_nullaz(){
    $('#aktkezd_d').val('0000.00.00');
    $('#aktvege_d').val('0000.00.00');
}
/* Időszakos ütemezésnél a szokásos időszak kinullázása */
function szokasos_nullaz(){
    $('#kezdho') .val('');
    $('#kezdnap').val('');
    $('#vegeho') .val('');
    $('#vegenap').val('');
}

/* Foglalt ID esetén az mezőre teszünk egy foglalt_id class-t, a JS ellenézés innen fogja tudni, hogy az űrlap nem postolható el*/
function id_foglalt_e(fajl, mezo){
    $.ajax({
        url : fajl,
        type: 'GET',
        data: esamp2es('id=' + mezo.val() + '&amp;id_foglalt_e=' + new Date().getTime()),
        success: function(response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                mezo.addClass('foglalt_id');
                alert2($('#id').val()+' - '+$('#nev').val()+'<br>Ez a kód már használatban van!', '', 'Hiba');
                return false;
            }else{
                mezo.removeClass('foglalt_id');
            }
        }
    });
}

function objLength(obj) {
    var size = 0, key;
    for (key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }

    return size;
};

function operator_beallit(that, selected, felt){
    var tabla    = that.attr('data-tabla');

     $.ajax({
        url: 'header.php',
        type: 'POST',
        data: esamp2es('tabla=' + tabla + '&amp;selected=' + selected + '&amp;mezo=' + that.val() + '&amp;f_szuro_mezo=' + new Date().getTime()),
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            response = JSON.parse(response);
            if (response) {
                /* Ha létezik az összetett változat, akkor ott jelenítjük meg */
                if($('#o_operator').length){
                    $('#o_operator').html(response.operator);
                /* Egyébként a rendes szűrés */
                }else{
                    $('#operator').html(response.operator);
                }
                szuro_ertek_mezo_gen($('.f_szuro_mezo_operator'), felt);

                $('#o_szures_submit').show();
            }
        }
    });
}

/* Szűrő érték mező beállítása */
function szuro_ertek_mezo_gen(that, felt){
    var tabla    = that.attr('data-tabla');
    var selected = that.find('option:selected');

    var prefix = '';
    if($('#o_operator').length){
        prefix = 'o_';
    }

    ajax = $.ajax({
        url: 'header.php',
        type: 'POST',
        data: esamp2es('tabla=' + tabla + '&amp;mezo=' + $('#'+prefix+'szuro_mezo').val() + '&amp;selected=' + selected.val() + '&amp;felt=' + felt + '&amp;f_szuro_mezo_operator=' + new Date().getTime()),
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            response = JSON.parse(response);
            if (response) {
                $('#'+prefix+'feltetel').html(response.feltetel);
                if(prefix){
                    $('#'+prefix+'szures_submit').html( '<input type="button" class="gomb zold f_o_szuro_hozzaad" value="+" style="padding: 0.3rem 0.7rem">');
                }else{
                    var kieg = '';
                    if( baseName(location.href)=='anyagigenyles' ) {
                        kieg = '?szures_torol';
                        if(_get('anyagrend')){
                            kieg += '&anyagrend';
                        }
                    }
                    $('#'+prefix+'szures_submit').html( '<input type="button" class="gomb f_szures_submit" value="Szűrés">'+
                                                        '<input style="margin-left: 0.2rem" type="button" class="gomb piros" value="Szűrés töröl" onclick="szurkit(); location.href=\''+tabla+'.php'+kieg+'\'">'
                                                    );
                }

            }
        }
   });

   $.when(ajax).then(function() {
       fejlecPozicional();
       fulGombMeretez();
   });
}

/* Személyzet -> Beosztás -> Naptár fül alatt a felvesz gomb lekezelése */
function beosztas_naptarba_tesz(beosztas_id, felulir){
    $.ajax({
        url: 'beosztas.php',
        type: 'POST',
        data: {
            beosztas_id     : beosztas_id,
            felulir         : felulir,
            tol             : $('#tol').val(),
            ig              : $('#ig').val(),
            beosztas        : $('#beosztas').val(),
            szerepkor       : $('#szerepkor').val(),
            felh_loginnev   : $('#felh_loginnev').val(),
            beosztas_naptarba_tesz : new Date().getTime()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            response = JSON.parse(response);
            if(response.siker){

                // Naptár csere
                $('.beosztas_naptar').replaceWith(response.naptar);

                // Gombok rejtése
                $('#mehet_gomb').attr('disabled', 'disabled');
                $('#mehet_gomb_kivesz').attr('disabled', 'disabled');
                $('#megsem_gomb').attr('disabled', 'disabled');

                // Űrlap nullázása
                $('#tol').val('\xa0\xa0\xa0\xa0.\xa0\xa0.\xa0\xa0\xa0\xa0\xa0:\xa0\xa0').removeClass('mod');
                $('#ig').val('\xa0\xa0\xa0\xa0.\xa0\xa0.\xa0\xa0\xa0\xa0\xa0:\xa0\xa0').removeClass('mod');
                $('#szerk_form input, #szerk_form select').removeClass('mod');
                $('#beosztas').val('');
                $('#szerepkor').val('');
                $('#felh_loginnev').val('');
                $('#felh_loginnev_autocomp').val('');
                $('.beosztas_rejt').addClass('dn');
                $('#szerk_form div.dt').hide();
                formModositva = false;
            }
            if(response.hiba){
                alert2(response.hiba, response.gomb, 'Hiba');
            }
        }
    });


}

function naptar_torol_kerdez(){
    gomb = '<input class="gomb zold" type="button" value="Igen" onclick="naptar_torol()"> <input class="gomb piros" type="button" value="Nem" onclick="alert2()">';
    alert2('Biztosan törli ezt a beosztást?', gomb);
}

function naptar_torol(){
    $.ajax({
        url : 'beosztas.php',
        type: 'POST',
        data: {
            id          : $('#naptar_torol_id').val(),
            naptar_torol: new Date().getTime()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            response = JSON.parse(response);
            if(response.siker){
                $('.beosztas_naptar').replaceWith(response.naptar);
                $('#mehet_gomb_kivesz').attr('disabled', 'disabled');
                $('#naptar_torol_id').val('');
                alert2();
            }
            if(response.hiba){
                alert2(response.hiba, response.gomb, 'Hiba');
            }
        }
    });
}

/* Ha egy csoport ki van jelölve, akkor a jobb oldali tartalmat beállítjuk */
function csoport_tartalom() {
    $('#szerk_form input:not(\'.foGomb\'), #szerk_form select, #szerk_form textarea').val('').prop('checked', false);
    $('div.statusz div.text, div.naplo, div.jsff, #sorrendezes_tobbkep, #szerk_form div.dt').hide();
    $('#szamlaBetolt').html('');

    if( !$('#szamlaHidden').hasClass('csoport') ) {
        $.ajax({
            url: 'esemenyek.php',
            type: 'POST',
            data: {
                tabla           : baseName(location.href),
                csoport_id      : $('li.csoport.kijelolve').attr('data-id'),
                csoport_tartalom: new Date().getTime()
            },
            success: function (response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                $('#szamlaHidden').addClass('csoport').html(response);
            }
        });
    }
}

/* Duplikálás gombra kattintva feljövő kérdés */
function bejegyzesDuplikal() {
    if( _get('uj') ){
        alert2('Új feltöltés alatt nem értelmezett a duplikálás, előbb válasszon ki egy '+$('#rekord_neve_targy').html()+'!', '', 'Hiba');
    }else if( baseName(location.href)=='felmeresi_terv' ){
        alert2(duplikaKerdes, duplikalGomb);
    }else{
        szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit').done(function() {
            alert2(duplikaKerdes, duplikalGomb);
        });
    }
}

/* A bal oldali navpanel kijelölésének ellenőrzése */
function balnav_kijelol_ell(egy_csoport_reszben_szabad, kivetel_ell){
    /* Ha van csoport elem megjelölve, akkor azt vizsgáljuk meg, hogy a csoport összes eleme meg van-e jelölve */
    var van_resz_csoport  = false;
    var tobb_resz_csoport = false;
    elozo_csoportid = '';
    if($('li.szamla_sor.behuz.kijelolve').length){
        $('li.szamla_sor.behuz.kijelolve').each(function () {
            csoportid = $(this).attr('data-csoportid');
            $('li.szamla_sor.behuz[data-csoportid="'+csoportid+'"]').each(function () {
                if(!$(this).hasClass('kijelolve')){
                    van_resz_csoport = true;
                    if(elozo_csoportid && elozo_csoportid!=csoportid){
                        tobb_resz_csoport = true;
                    }
                }
            });
            elozo_csoportid = csoportid;
        });

        /* Ha van félig megjelölt csoport és szabadon álló bejegyzés, akkor hibaüzenet */
        if(van_resz_csoport && $('li.szamla_sor.kijelolve:not(\'.behuz\')').length){
            alert2('Szabad és csoportosított '+$('#rekord_neve').html().toLowerCase()+' egyszerre nem jelölhetők!', '', 'Hiba');
            return false;
        }

        if(van_resz_csoport){

            //Ha csak egy csoportból vannak megjelölve rekordok, de nem mind
            if(!tobb_resz_csoport){

                /* Ha nincs megengedve, hogy egy csoport részben legyen kijelölve, akkor szólunk */
                if(!egy_csoport_reszben_szabad){
                    alert2('Külön csoportosított '+$('#rekord_neve').html().toLowerCase()+' egyszerre nem jelölhetők!', '', 'Hiba');
                    return false;
                }

            }else{
                alert2('Külön csoportosított '+$('#rekord_neve').html().toLowerCase()+' egyszerre nem jelölhetők!', '', 'Hiba');
                return false;
            }

        }
    }

    /* Ha ki szeretnénk épp venni egy elemet a csoportból, de van olyan kijelölt, ami nincs is csoportban akkor hibaüzenet */
    if(kivetel_ell && $('li.szamla_sor.kijelolve:not(\'.behuz\')').length){
        alert2('Van olyan kijelölt '+$('#rekord_neve_egyes').html()+', mely nincs csoportosítva. A művelet így nem hajtható létre!', '', 'Hiba');
        return false;
    }

    return true;
}

/* Projekt igényléssnél ha az összes véleményező törölve van, rákérdezünk, hogy léptetjük-e a státuszt nyitottba.
 * De akkor már általánosan lett megírva a függvény. */
function pri_statusz_leptet(recid, statusz){

    if(formModositva){
        alert2('Kérjük előbb mentse el az űrlapot!', '', 'Hiba');
    }else{
        $.ajax({
            url : 'projektigenyles.php',
            type: 'POST',
            data: {
                recid             : recid,
                statusz           : statusz,
                pri_statusz_leptet: new Date().getTime()
            },
            success: function (response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                response = JSON.parse(response);
                if(response.statusz=='siker'){
                    
                    szamlaSorFrissit('projektigenyles', recid);
                    szamlaBetolt(recid, 'projektigenyles.php', 'modosit');
                    
                    alert2();
                }

                if(response.hiba){
                    alert2(response.hiba, '', 'Hiba');
                }
            }
        });
    }
}

/* Összetett szűrésnél gomb mutatása */
function o_szures_gomb_mutat(){
    $('#o_sures').html('<input type="button" class="gomb zold f_o_szures_submit" value="Szűrés">');
    osszetett_szuro_mondat();
}

/* Összetett szűrés mondat előállítása */
function osszetett_szuro_mondat(returnold){

    var mondat = '';
    $('#o_feltetel_halmaz div.dtr').each(function () {

        if($(this).find('.zaro.nyit.aktiv').length){
            mondat += '(&nbsp;';
        }
        mondat += $(this).find('.m_mezo').html()+' ';
        mondat += $(this).find('.m_op').html()+' ';
        mondat += $(this).find('.m_felt').html()+' ';

        if($(this).find('.zaro.zar.aktiv').length){
            mondat += ')&nbsp;';
        }

        mondat += $(this).find('.es_vagy option:selected').text()+' ';

    });

    /* Utolsó ÉS/VAGY levágása */
    mondat = mondat.replace(/ ÉS +$/, '').replace(/ VAGY +$/, '');

    $('#o_szures_mondat').html('<b>Megfogalmazott feltétel:</b><br>'+mondat);
    /* Ha nyomtatási nézetben lennénk, ott is jelenjen meg */
    $('#szuro_feltetel').html(mondat);

}

/* Dokumentumtár menüponthoz kell */
function getKonyvtar( link ) {
    if( link ) {
        $.ajax({
         	url : link,
         	type: 'GET',
         	data: esamp2es('ajax=' + new Date().getTime()),
         	success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
         		if(response) {
         			$('#szamlaBetolt').html(response);

                    window.history.pushState("", "", link );
         		}
         	}
        });
    }
}

/* Előterjesztés alatti döntés gomb működése */
function eloterj_dontes_feldolgoz(){
    $('.prig_statusz_txt').hide();
    $('.prig_statusz_select').show();
}
function pri_eloterj_statusz_mod_mentes(rogzit, mehet){

    /* Rögzítéskor azt is figyeljük, hogy minden státusz be van-e már állítva */
    if(rogzit){
        hianyos = 0;
        $('.prig_statusz_select select').each(function () {
            if(!$(this).val()){
                hianyos++;
            }
        });

        /* Ha van még ki nem választott select */
        if(hianyos){
            alert2('Még '+hianyos+' darab igénylés vár feldolgozásra.<br>Rögzítés előtt kérjük dolgozza fel ezeket is!', '', 'Hiba');
            return false;
        }else if(!mehet){

            if(!$('#ules_datum').val() || $('#ules_datum').val()=='0000-00-00'){
                alert2('Kérjük előbb adja meg az ülés dátumát', '<input type="button" class="gomb zold" value="Ok" onclick="szamlaBetolt( \''+_get('id')+'\', \''+baseName(location.href) + '.php\', \'modosit_ules\');">');
                return false;
            }

            kerdes_txt = ' FB';
            if(baseName(location.href) == 'eloterj_bod'){
                kerdes_txt = ' BoD';
            }
            alert2('Biztos, hogy rögzíti a'+kerdes_txt+' döntést?', '<input type="button" class="gomb zold" value="Igen" onclick="pri_eloterj_statusz_mod_mentes(\'rögzít\',\'mehet\')"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">');
            return false;
        }

    }

    pri_eloterj_recidk = {};
    stat_id_eloterjek  = {};
    i = 0;
    $('.prig_statusz_select select').each(function () {
        pri_eloterj_recidk[i] = $(this).attr('data-pri_eloterj_recid');
        stat_id_eloterjek[i]  = $(this).val();
        i++;
    });
    pri_eloterj_statusz_mod(pri_eloterj_recidk, stat_id_eloterjek, rogzit);
    $('#statusz_ment_gomb').addClass('dn');
}
function pri_eloterj_statusz_mod(pri_eloterj_recidk, stat_id_eloterjek, rogzit){
    $.ajax({
        url : 'projektigenyles.php',
        type: 'POST',
        data: {
            tabla                   : baseName(location.href),
            eloterj_recid           : _get('id'),
            rogzit                  : rogzit,
            pri_eloterj_recidk      : JSON.stringify(pri_eloterj_recidk),
            stat_id_eloterjek       : JSON.stringify(stat_id_eloterjek),
            pri_eloterj_statusz_mod : new Date().getTime()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            response = JSON.parse(response);
            if(response.hiba){
                alert2(response.hiba, response.gomb, 'Hiba');
            }else{
                alert2();
                szamlaBetolt( _get('id'), baseName(location.href) + '.php', 'projektigeny' );
            }
        }
    });
}

/* Munkalap készítése FONTOS, hogy ne a munkalap.js-ben legyen, mert a bejelentesek.php-ben látni kell! */
function munkalapKeszit(csak_kerdezunk, kerdesek_nelkul) {

    if(balnav_kijelol_ell()){

        szurkit();

        idk = {};

        szamlaSorok = $('#szamlaList li.szamla_sor.kijelolve');
        if( szamlaSorok.length ) {
            var i = 1;
            szamlaSorok.each(function() {
                var id = $(this).attr('data-recid');

                idk[i] = id;
                i++;
            });
        } else {
            var recid = $('#szamlaBetolt').attr('data-recid');

            if( recid ) {
                idk[1] = recid;
            }
        }

        if( objLength(idk) ) {
            $.ajax({
                url  : 'munkalap.php',
                type : 'POST',
                data : {
                    idk             : JSON.stringify(idk),
                    csak_kerdezunk  : csak_kerdezunk,
                    kerdesek_nelkul : kerdesek_nelkul,
                    munkalapKeszit  : new Date().getTime()
                },
                success: function(response) {
                    if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                    if(response) {
                        response = JSON.parse(response);

                        if( response.hiba ) {
                            alert2(response.hiba, response.gomb, response.title);
                            szurkit_zar();
                            return false;
                        }

                        /* 2018.02.19 Kérték, hogy ne léptessük át a munkalpara, maradunk a bejelentésnél.
                         * location.href = 'munkalap.php?id=' + response.recid + '&modosit';*/

                        alert2();
                        kijelolesEltavolit();
                        popupZar();
                        szurkit_zar();
                        szamlaBetolt( idk[1], 'bejelentes.php', 'modosit' );

                    }
                }
            });
        } else {
            alert2('Nincsenek kijelölt sorok!', '', 'Hiba');
        }
    }
    return false;
}

/* Egy átadott elem értéke valid dátumú-e? */
function valid_date(that, true_val_terj_vissza){
    /* Üres dátummezőt töltsük fel */
    if(!that.val()){
        if(that.hasClass('datepicker')){
            that.val('\xa0\xa0\xa0\xa0.\xa0\xa0.\xa0\xa0');
        }
        if(that.hasClass('datetimepicker')){
            that.val('\xa0\xa0\xa0\xa0.\xa0\xa0.\xa0\xa0\xa0\xa0\xa0:\xa0\xa0');
        }
    }
    if(
        that.val()!='\xa0\xa0\xa0\xa0.\xa0\xa0.\xa0\xa0' &&
        that.val()!='\xa0\xa0\xa0\xa0.\xa0\xa0.\xa0\xa0\xa0\xa0:\xa0\xa0' &&
        that.val()!='\xa0\xa0\xa0\xa0.\xa0\xa0.\xa0\xa0\xa0\xa0:\xa0\xa0:\xa0\xa0' &&
        that.val()!='0000.00.00' &&
        that.val()!='0000.00.00 00:00' &&
        that.val()!='0000.00.00 00:00:00' &&
        !Date.parse( that.val().replace(/\./gi, '-') )
        ){
            if(that.hasClass('datepicker')){
                alert2('Helytelen dátum: '+that.val()+'<br>Kérjük válasszon érvényes dátumot!', '', 'Hiba');
            }
            if(that.hasClass('datetimepicker')){
                alert2('Helytelen dátumidő: '+that.val()+'<br>Kérjük válasszon érvényes dátumidőt!', '', 'Hiba');
            }
            that.addClass('hibas_ide_focus');
            return false;
    }else{
        if(true_val_terj_vissza){
            return true;
        }
    }
}
/* Egy átadott elem értéke valid időpont-e? */
function valid_ido(hibas_input){
    ora_perc = hibas_input.val().split(':');
    if(ora_perc[0]>24 || ora_perc[0]<0 || ora_perc[1]>60 || ora_perc[0]<0){
        alert2('Érvénytelen időpont: '+hibas_input.val()+'<br>Kérjük válasszon érvényes időpontot!', '', 'Hiba');
        hibas_input.val('\xa0\xa0:\xa0\xa0');
        hibas_input.addClass('hibas_ide_focus');
        return false;
    }
}

/* Anyagigénylés táblázat sor hozzáadás */
function munkAnyagHozzaad( formID, igenyles ) {
    var form = $(formID);

    var cikknev     = $('#cikk_id_autocomp'),
        cikkID      = $('#cikk_id'),
        cikkid_origi= $('#cikkid_origi'),
        felh_menny  = $('#felh_menny'),
        szuk_menny  = $('#szuk_menny'),
        igleir      = $('#igleir'),
        cikkmee     = $('.insertMee').eq(0).text(),
        id_tmp      = new Date().getTime();

    var addCikkID = form.find('input[name="addCikkID[]"]').map(function(){return $(this).val();}).get();

    if( addCikkID.length ) {

        for(key in addCikkID) {

            if( addCikkID[key] == cikkid_origi.val() && cikkid_origi.val() ) {

                $('#cikk_id_autocomp').addClass('hibas_ide_focus');
                alert2('Ez az anyag már hozzá van adva a munkalap tételhez!', '', 'Hiba');

                cikkID.val('');
                cikknev.val('');
                felh_menny.val('0,00');
                szuk_menny.val('0,00');
                form.find('.insertMee').text('');

                return false;
            }
        }
    }

    if( cikknev.val() == '' || cikkID.val() == '' ) {
        $('#cikk_id_autocomp').addClass('hibas_ide_focus');
        alert2('Válassza ki a cikket!', '', 'Hiba');
        return false;
    }

    if( !igenyles && (felh_menny.val() == '' || felh_menny.val() == '0,00') ) {
        $('#felh_menny').addClass('hibas_ide_focus');
        $('#felh_menny').focus(); /* A focus() itt kivételesen mégis kell. A Felh. menny mezőre Enterezve mégse a cikknév mezőben legyen a kurzor még a hibaüzenet alatt sem. */
        alert2('Adja meg a felhasznált mennyiséget!', '', 'Hiba');
        return false;
    }

    if( igenyles ) {
        if( (felh_menny.val() == '' || felh_menny.val() == '0,00') && (szuk_menny.val() == '' || szuk_menny.val() == '0,00') ) {
            $('#felh_menny').addClass('hibas_ide_focus');
            $('#felh_menny').focus(); /* A focus() itt kivételesen mégis kell. A Felh. menny mezőre Enterezve mégse a cikknév mezőben legyen a kurzor még a hibaüzenet alatt sem. */
            alert2('Adja meg a felhasznált, vagy a szükséges mennyiséget!', '', 'Hiba');
            return false;
        }
    }

    var sz_td = '';
    if( igenyles ) {
        sz_td = '<td class="c">' + szuk_menny.val() + '<input type="hidden" name="addCikkSzukMenny[]" value="' + szuk_menny.val() + '"></td>';
    }

    /* A cikktörzs a cikkszámot is tartalmazza. Az első kötőjelig ezért eldobjuk a cikk nevét, mert az a cikkszám. */
    csak_cikknev = cikknev.val();
    csak_cikknev = csak_cikknev.substr(csak_cikknev.indexOf(' - ')+1);
    var table_sor = '<tr class="anyag_sor_'+id_tmp+'">' +
                        '<td id="anyag_cikkid_'+id_tmp+'">' + cikkid_origi.val() + '<input type="hidden" name="addCikkID[]" value="' + cikkid_origi.val() + '"><input type="hidden" name="addCikkUj[]" value="1"></td>' +
                        '<td id="anyag_nev_'+id_tmp+'">' + csak_cikknev + '</td>' +
                        '<td class="c">' + felh_menny.val() + '<input type="hidden" name="addCikkFelhMenny[]" value="' + felh_menny.val() + '"></td>' +
                        sz_td +
                        '<td>' + cikkmee + '</td>' +
                        '<td></td>' +
                        '<td>' + igleir.val().replace(/(?:\r\n|\r|\n)/g, '<br>') + '<input type="hidden" name="addCikkIgleir[]" value="' + igleir.val() + '"></td>' +
                        '<td><a id="anyag_id_'+id_tmp+'" onclick="munkAnyagTorol( \''+id_tmp+'\', \'anyag_id_'+id_tmp+'\' );"><span class="icon"><i class="material-icons">delete</i></span></a></td>' +
                    '</tr>';

    $('#insertTable table.csikos').append(table_sor);

    cikkID.val('');
    cikknev.val('');
    igleir.val('');
    felh_menny.val('0,00');
    szuk_menny.val('0,00');
    form.find('.insertMee').text('');

    $('#cikk_id_autocomp').focus();

    return false;
}

/* Felmérési tervhez van-e már feltöltve terv? */
function felmeresi_terv_csatolva(recid){
    $.ajax({
        url : 'felmeresi_terv.php',
        type: 'POST',
        data: {
            recid                   : recid,
            felmeresi_terv_csatolva : new Date().getTime()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            response = JSON.parse(response);
            if(response['tallozas_mehet']){
                $('#jsff_felmeresi_terv_felm_terv input').click();
            }
            if(response.hiba){
                alert2(response.hiba, response.gomb, response.title);
            }
        }
    });
}

/* Ütemezett feladatoknál és rendszeres karbantartásnál a külső kivitelző állapot a hozzárendelt személyzet értékén múlik */
function ut_kulso_kivitlezo_beallit(){
    if($('#szemelyzet_id').val()==3){
        $('#szallito_id_autocomp').addClass('csillag').removeAttr('readonly');
        $('#szallito_id_autocomp').autocomplete({
            'php_fajl'        : '../autocomplete.php',
            'tabla'           : 'szallito',
            'oszlop_keres'    : 'nev',
            'oszlop_listaz'   : 'nev',
            'rkat'            : '0',
            'csoport'         : '0',
            'id_kiir'         : 'id'
        });
        $('#szallito_id_autocomp').focus();
    }else{
        $('#szallito_id_autocomp').removeClass('csillag').removeClass('mod').attr('readonly', true).val('');
        $('#szallito_id').val('');
    }

    /* Ütemezett feladatoknál ha külső kivitelezőt választottunk ki, akkor szerkeszthetővé válik az eddig disabled külső kivitelező input, amit újra kell tabindxelni, hogy jó legyen a TAB-ozás */
    tabindex_beallit();
}

/* Mobilcsoport beosztás véglegesítése */
function beosztas_veglegesit(mehet){
    a_csop = '';
    $('.mobilcsop_a li').each(function () {
        a_csop += $(this).data('recid')+',';
    });
    b_csop = '';
    $('.mobilcsop_b li').each(function () {
        b_csop += $(this).data('recid')+',';
    });
    cn_csop = '';
    $('.mobilcsop_cn li').each(function () {
        cn_csop += $(this).data('recid')+',';
    });
    d_csop = '';
    $('.mobilcsop_d li').each(function () {
        d_csop += $(this).data('recid')+',';
    });

    if(!a_csop && !b_csop && !cn_csop && !d_csop){
        alert2('Kérem előbb húzza be a munkalapokat a kívánt csoportba!', '', 'Hiba');
        return false;
    }

    if(!mehet){

        veglegesit_gomb = '<input type="button" class="gomb zold" value="Igen" onclick="beosztas_veglegesit(\'mehet\');"><input type="button" class="gomb piros" value="Nem" onclick="alert2();">';

        /*
         * Vagy rákérdezünk, hogy biztosan véglegesít-e, de ha olyan csoportba húzott feladatot, amihez nincs rendelve személyzet, akkor meg arra kérdezünk rá
         * Ezt ajax-al számoljuk ki.
         * */
        $.ajax({
            url: 'mobil_hozzarendel.php',
            type: 'POST',
            data: {
                a_csop              : a_csop,
                b_csop              : b_csop,
                cn_csop             : cn_csop,
                d_csop              : d_csop,
                beosztas_kerdez     : Date.now()
            },
            success: function (response) {
                if (response) {
                    if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                    response = JSON.parse(response);
                    alert2(response.hiba, veglegesit_gomb);
                }
            }
        });
        return false;
    }

    $.ajax({
        url : 'mobil_hozzarendel.php',
        type: 'POST',
        data: {
            a_csop              : a_csop,
            b_csop              : b_csop,
            cn_csop             : cn_csop,
            d_csop              : d_csop,
            beosztas_veglegesit : new Date().getTime()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            response = JSON.parse(response);
            if(response.hiba){
                alert2(response.hiba, '', 'Hiba');
            }else{
                szurkit();
                location.reload();
            }
        }
    });

}

/* Munkalap vissza a nyitottak közé (Mobil hozzárendelés felület) */
function munkalapok_nyitottak_koze(mehet){

    if( !$('.mobilkeret li.kijelolt').length ){
        alert2('Előbb válasszon munkalapot!', '', 'Hiba');
        return false;
    }

    if(!mehet){
        nyitottak_koze_gomb = '<input type="button" class="gomb zold" value="Igen" onclick="munkalapok_nyitottak_koze(\'mehet\');"><input type="button" class="gomb piros" value="Nem" onclick="alert2();">';
        alert2($('.mobilkeret li.kijelolt .munkalapszam').html()+' munkalapszám<br>'+$('.mobilkeret li.kijelolt .szalloda').html()+' szálloda<br>'+$('.mobilkeret li.kijelolt .hiba').html()+' hiba<br><br>Biztos, <span style="display: inline-block; width: 0.4rem"></span>hogy visszahelyezi a munkalapot a nyitottak közé?', nyitottak_koze_gomb);
        return false;
    }

    $.ajax({
        url : 'mobil_hozzarendel.php',
        type: 'POST',
        data: {
            recid                    : $('.mobilkeret li.kijelolt').data('recid'),
            munkalapok_nyitottak_koze: new Date().getTime()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            szurkit();
            location.reload();
        }
    });
}

/* Az űrlap gombjait váltjuk: mentés / mégsem látsszon vagy a kék gombok. Illetve form módosított-e vagy sem.  */
function gombok_valt(mod){

    if( mod ) {
        $('#kilepes_gomb').val('Kilépés mentés nélkül');
        $('#mehet_gomb').attr('disabled', false).addClass('gombb_alert');
        $('#megsem_gomb').attr('disabled', false);
        $('#visszavonas_gomb').attr('disabled', false);
        $('.modositaskor_rejt').hide();

        formModositva = true;
    } else {
        $('#kilepes_gomb').val('Kilépés');
        /* Új anyagigénylés feltöltésénél a gombot inkább ne rejtsük el sose. Mert itt gond volt abból, hogy a Hozzáadás gomb lenyomása után nem lehetett az alap űrlapot elmenteni. */
        if(!$('#mehet_gomb').hasClass('ne_rejtsd_el')){
            $('#mehet_gomb').attr('disabled', true).removeClass('gombb_alert');
        }
        if(!$('#megsem_gomb').hasClass('ne_rejtsd_el')){
            $('#megsem_gomb').attr('disabled', true);
        }
        $('#visszavonas_gomb').attr('disabled', true);
        $('.modositaskor_rejt').show();

        formModositva = false;
    }
}

/* Betöltéskor leszürkítünk mindent, hogy ne lehessen gombokat újra megnyomni. */
function szurkit( mehet_megsem_rejt ){
    $('#ajaxfedo').removeClass('dn');
    /* Mehet/Mégsem gombot el is rejtjük, ha azokra lett kattintva */
    if(mehet_megsem_rejt){
        $('#mehet_gomb, #megsem_gomb').attr('disabled', 1);
    }
}
function szurkit_zar( mehet_megsem_mutat ){

    setTimeout(function() {

        $('#ajaxfedo').addClass('dn');
        if(mehet_megsem_mutat){
            $('#mehet_gomb, #megsem_gomb').removeAttr ('disabled');
        }


    }, 100);

}

/* Tabindex számozása */
function tabindex_beallit(){
    $(':input[type!="hidden"]:not([readonly])').each(function (i) {
        $(this).attr('tabindex', i + 1);
    });
    $(':input[type="hidden"], input[readonly]').attr('tabindex', '-1');
}

function szalloda_modell( that ) {
    var modell = parseInt($(that).find(":selected").attr('data-modell'));
    if( $('#mobilcsoport_opt').length ) {

        if( modell ) {
            $('#mobilcsoport_opt').removeClass('dn').removeAttr('disabled');
        } else {
            if( $('#szemelyzet_id').val()=='2' ){
                $('#szemelyzet_id').val(0);
            }
            $('#mobilcsoport_opt').addClass('dn').attr('disabled', true);
        }
    }
}

/* Új anygigénylésnél az anyagigénylés hozzáadás gomb működése */
var anyagigenyles_hozzaad_defer = $.Deferred();
function anyagigenylesHozzaad(mehet, mentes_gombbol, ajax_nelkul_hozzaad) {
    var form = $('#szerk_form'),
        data = {};

    var telephely_id = '';
    var telephely_nev= '';
    var cikkszam_nev = '';
    var cikkszam_id  = '';
    form.find('input, select, textarea').each(function() {
        var name  = $(this).attr('name'),
            value = $(this).val();
        if( name !== undefined ) {
            switch(name) {
                case 'telephely_id':
                    var text     = $(this).find('option:selected').text();
                    telephely_id = value;
                    telephely_nev= text;
                    /* Üres maradhat a telephely név, mert a disabled a select, mert mondjuk egy szállodás a user, akkor nem tudja kiolvasni a text-ét.
                     *  Ilyenkor van egy hidden input, benne a szálloda nevével. */
                    if(!telephely_nev){
                        telephely_nev = $('#telephely_txt').val();
                    }
                    data[name]   = ' <input type="hidden" name="' + name + '[]" value="' + value + '">';
                break;
                case 'nev':
                    var autcomplete_text = $('input[name="' + name + '_autocomp"]').val();

                    cikkszam_id             = value;
                    cikkszam_nev            = autcomplete_text;
                    data['cikkszam_recid']  = $('input[name="' + name + '"]').val();
                    data[name]              = autcomplete_text + ' <input type="hidden" name="' + name + '[]" value="' + value + '">';
                break;
                case 'igmen':
                    data[name] = value + ' <input type="hidden" name="' + name + '[]" value="' + value + '">';
                break;
                case 'kulsosnek':
                    var checked = $(this).prop('checked');

                    if( checked ) {
                        text    = '✓';
                        checked = true;
                    } else {
                        text    = '';
                        checked = false;
                    }

                    data[name] = text + ' <input type="hidden" name="' + name + '[]" value="' + checked + '">';
                break;
                case 'igleir':
                    data[name] = value.replace(/\n/g,'<br>') + ' <input type="hidden" name="' + name + '[]" value="' + value + '">';
                break;
            }
        }
    });

    /* Szálloda + cikkszám ellenőrzése */
    if( $('.szalloda_cikknev_ell[value="'+telephely_id+'_'+cikkszam_id+'"]').length ){
        alert2('Cikk: '+$('#cikkszam_'+data['cikkszam_recid']).html()+'<br>Név: '+cikkszam_nev+'<br>Szálloda: '+telephely_nev+'<br><br>A választott szálloda és anyag már szerepel a listában!', '', 'Hiba');
        return false;
    }

    /* Ajax nélkül veszük fel a sort, nem kell már ellenőrizni. Anyagigénylés gombra kattintva, a megerősítő kérdés után. */
    if(ajax_nelkul_hozzaad){
        data['cikkmee'] = form.find('.insertMee').text() + ' <input type="hidden" name="cikkmee[]" value="' + form.find('.insertMee').text() + '">';

        var mezok = '<tr class="anyag_sor">' +
                        '<td>' + $('#telephely_id').find('option:selected').text()/*Itt azért csináljuk másképpen, mert ha a szálloda readonly (egyszállodája ha van a usernek), akkor nem jelent meg a szálloda neve */
                               + data['telephely_id'] + '</td>' +
                        '<td class="l" id="cikkszam_'+data['cikkszam_recid']+'"></td>' +
                        '<td class="l">' + data['nev'] + '</td>' +
                        '<td>' + data['kulsosnek'] + '</td>' +
                        '<td>' + data['igmen'] + '</td>' +
                        '<td>' + data['cikkmee'] + '</td>' +
                        '<td>' + data['igleir'] + '</td>' +
                        '<td>' +
                            '<input type="hidden" name="szalloda_cikknev_ell" class="szalloda_cikknev_ell" value="'+telephely_id+'_'+cikkszam_id+'">' +
                            '<span class="icon link" onclick="$(this).parents(\'tr\').remove();"><i class="material-icons" style="font-size: 2rem">delete</i></span>' +
                        '</td>' +
                    '</tr>';

        $('#insertAnyagigenylesSor').append(mezok);

        anyagigenyles_hozzaad_defer.resolve();

        return true;
    }else{

        $.ajax({
            url : 'anyagigenyles.php',
            type: 'POST',
            data: {
                telephely_id                : telephely_id,
                cikk_id                     : cikkszam_id,
                mehet                       : mehet,
                mentes_gombbol              : mentes_gombbol,
                van_mar_ilyen_anyagigenyles : Date.now()
            },
            success: function (response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if(response) {
                    response = JSON.parse(response);

                    if(response.hiba){
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }else{
                        data['cikkmee'] = form.find('.insertMee').text() + ' <input type="hidden" name="cikkmee[]" value="' + form.find('.insertMee').text() + '">';

                        var mezok = '<tr class="anyag_sor">' +
                                        '<td>' + $('#telephely_id').find('option:selected').text()/*Itt azért csináljuk másképpen, mert ha a szálloda readonly (egyszállodája ha van a usernek), akkor nem jelent meg a szálloda neve */
                                               + data['telephely_id'] + '</td>' +
                                        '<td class="l" id="cikkszam_'+data['cikkszam_recid']+'"></td>' +
                                        '<td class="l">' + data['nev'] + '</td>' +
                                        '<td>' + data['kulsosnek'] + '</td>' +
                                        '<td>' + data['igmen'] + '</td>' +
                                        '<td>' + data['cikkmee'] + '</td>' +
                                        '<td>' + data['igleir'] + '</td>' +
                                        '<td>' +
                                            '<input type="hidden" name="szalloda_cikknev_ell" class="szalloda_cikknev_ell" value="'+telephely_id+'_'+cikkszam_id+'">' +
                                            '<span class="icon link" onclick="$(this).parents(\'tr\').remove();"><i class="material-icons" style="font-size: 2rem">delete</i></span>' +
                                        '</td>' +
                                    '</tr>';

                        $('#insertAnyagigenylesSor').append(mezok);
                        anyagigenyles_form_reset(telephely_id);
                        anyagigenyles_hozzaad_defer.resolve();

                        /* Cikkszám kiszámítása utólag */
                        $.ajax({
                            url : 'anyagigenyles.php',
                            type: 'POST',
                            data: {
                                recid       : cikkszam_id,
                                get_cikkszam: Date.now()
                            },
                            success: function (response) {
                                $('#cikkszam_'+cikkszam_id).html(response);

                                /* Idáig el kell jussunk ahhoz, hogy az anyagigenylesekMentes() függvényben a tálbázat feldolgozása elkezdődhessen */
                                anyagigenyles_hozzaad_defer.resolve();
                            }
                        });
                        return true;
                    }

                }
            }
        });
    }

}
function anyagigenyles_form_reset(telephely_id){
    form = $('#szerk_form');
    /* Űrlap üritése */
    document.getElementById('szerk_form').reset();
    form.find('input, select, textarea').removeClass('mod alert');

    /* Előző szálloda beállítása és Cikknév mezőbe fókuszálás */
    $('#telephely_id').val(telephely_id).addClass('mod');
    $('#nev_autocomp').focus();
}

/* Új anyagigénylésnél az oldal alján levő Anyagigénylés gomb működése (pl. táblázat feldolgozása) */
function anyagigenylesekMentes(mehet) {
    var form    = $('#anyagSorokForm'),
        inputok = form.serializeJSON();

    var darab = objLength(inputok['telephely_id']),
        dfrd1 = $.Deferred();


    if( darab ) {

        anyagigenyles_hozzaad_defer = $.Deferred();

        /* Ha az alap űrlap Cikk név mezőjében van adat, akkor az űrlap IS ki van töltve, tehát adjuk előbb hozzá a sort. De ellenőrizni kell az űrla helyességét, és ha gond van, akkor álljon meg minden!
         * Sőt ha az a kérdés jött fel, hogy adott szálloda+cikkszám már benne van az igénylések tálbába, és ezt jóváhagyták (tehát mehet paraméterrel lett meghívva eza  függvény), akkor már nem kell ellenőrizni mégegyszer a form_ell_admin_elott()-t, mehetünk tovább.
         */
        var mehet2 = true;
        if(!mehet){

            if( $('#nev_autocomp').val() ) {
                if(form_ell_admin_elott()){
                    if( anyagigenylesHozzaad('', 'Mentés gombról hívtuk')===false ){
                        mehet2 = false;
                    }
                }
            }else{
                anyagigenyles_hozzaad_defer.resolve();
            }

        /* Tehát rákérdezés után már csak annyit csinálunk, hogy fevesszük a táblázatbam, újraszmáoljuk és mehetünk tovább */
        }else{
            anyagigenylesHozzaad('', '', 'Ajax lélkül add hozzá');
        }

        $.when(anyagigenyles_hozzaad_defer).then(function() {

            inputok = form.serializeJSON();
            darab   = objLength(inputok['telephely_id']);

            if(mehet2){
                for( var i = 0; i < darab; i++ ) {
                    var data = {};

                    for( oszlop in inputok ) {
                        if( oszlop != 'cikkmee' ) {
                            for( key in inputok[oszlop] ) {
                                if( key == i ) {
                                    data[oszlop] = inputok[oszlop][key];
                                }
                            }
                        }
                    }

                    data['tobbsor'] = '1';
                    data['ujanyagigenyles'] = new Date().getTime();

                    $.ajax({
                        url : 'anyagigenyles.php?modosit',
                        type: 'POST',
                        data: data,
                        async: false,
                        success: function(response) {
                            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                            if(response) {
                                response = JSON.parse(response);

                                if( response.hiba ) {
                                    alert2(response.hiba, response.gomb, 'Hiba');
                                    return false;
                                }

                                if( darab == (i + 1) ) {
                                    dfrd1.resolve();
                                    formModositva = false;
                                    location.href = response.link;
                                }
                            }
                        }
                    });
                }

                $.when(dfrd1).then(function() {
                    console.log('Anyagigénylések beszúrva.');
                });

                alert2('');

            }
        });

    } else {
        $('#szerk_form').submit();
    }
}

// E-mail küldéskor a már feltöltött, de még el nem küldött csatolmáynok kilistázása
function get_csatolmanyok(tabla, recid) {
    $.ajax({
        url : 'esemenyek.php',
        type: 'POST',
        data: {
            recid           : recid,
            tabla           : tabla,
            get_csatolmanyok: Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            $('#pop_csatolt_fajlok').html(response);
        }
    });
}

// Adatlapon minden inputot elszürkítünk, disabled-re állítunk.
// Pl. ha nincs mentés gombunk, vagy nincs találat a bal navpanelben
function adatlap_minden_inputjat_disabled() {
    // Checbox-ok labeljeire is kell egy readonly, hogy ne lehessen rákattintani
    $('#szerk_form input[type="checkbox"]').each(function() {
        $('label[for="'+$(this).attr('id')+'"]').attr('readonly', true);
    });

    $('#szerk_form input, #szerk_form textarea, #szerk_form select').attr('readonly', true);
    $('#szerk_form input.f_nem_rejt').attr('readonly', false);
    $('#szerk_form input.foGomb').removeAttr('disabled');
}