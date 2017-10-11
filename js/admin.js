var naptar_latszik_nincs_leptetes = false; /* Ha a bal oldali navpanelben naptár jelenik meg, a léptetetést kikapcsoljuk */

function lap_tetejere(){
    if(getKepernyoMagas()<getOldalMagas()){
    	document.write('<div class="nyil" onclick="scrollto(\'#teto\', 1000)" title="Lap tetejére"></div>');
    }
}

function fajl_torol(dbsorid,tabla,oszlop,utvonal){
	ajaxFunction();
	req.onreadystatechange = processChoices;
	req.open("POST", "esemenyek.php", true);
	req.setRequestHeader("Content-type", "application/x-www-form-urlencoded; charset=utf-8");
	req.send("fajl_torles="+dbsorid+"&tabla="+tabla+"&oszlop="+oszlop+"&fajl=../"+utvonal);
	document.getElementById('fajl_nagyito_'+oszlop).style.display='none';
	document.getElementById('fajl_kuka_'+oszlop).style.display='none';
	return true;
}

/* Inline szerkesztés megkezdése */
var onclick_disabled = false;
function inline_szerk(oszlop, sorid){
    onclick_disabled = true;
    $('#inline_szerk_'+oszlop+'_'+sorid+'_span').slideUp(30);
    $('#inline_szerk_'+oszlop+'_'+sorid).slideDown(30);
    $('#inline_szerk_'+oszlop+'_'+sorid).focus();
}

/* Inline szerkesztés menttése */
function inline_szerk_ment(tabla, oszlop, sorid) {

    $.ajax({
        url : 'esemenyek.php',
        type: 'GET',
        data: esamp2es('tabla=' + tabla + '&amp;oszlop=' + oszlop + '&amp;ertek=' + sql_ertek( $('#inline_szerk_' + oszlop+'_'+sorid).val() ) + '&amp;sorid=' + sorid + '&amp;inline_szerk=' + new Date().getTime()),
        success: function(html){
            $('#inline_szerk_'+oszlop+'_'+sorid+'_text').html( html );
        }
    });
    $('#inline_szerk_'+oszlop+'_'+sorid+'_span').slideDown(30);
    $('#inline_szerk_'+oszlop+'_'+sorid).slideUp(30);
    onclick_disabled = false;
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
            $.ajax({
                url : fajl,
                type: 'GET',
                data: esamp2es('&amp;becsapas=' + new Date().getTime()),
                success: function(html) {
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
    return '<p class="c" style="margin-top: 0.5rem"><input class="gomb zold" type="button" value="Igen" onclick="location.href=\'' + self + '?lista&amp;' + torles + '=' + get_id + '\'">  <input class="gomb piros" type="button" value="Nem" onclick="alert2()">';
}

/* Kibontásnál címlap kép törlés HTML-je. Hogy valid legyen kiveszem JS-be. */
function get_fokep_torol(self, get_id, keptorol){
    return '<p class="c" style="margin-top: 0.5rem"><input class="gomb zold" type="button" value="Igen" onclick="$.ajax({ url : \'' + self + '\', type: \'GET\', data: \'id=' + get_id + '&amp;modosit&amp;keptorol=' + keptorol + '&amp;becsapas=' + new Date().getTime() + '\', success: function(html){ $(\'#fokep\').fadeOut(\'fast\'); alert2(); } });"> <input class="gomb piros" type="button" value="Nem" onclick="alert2()">';
}

/* Kibontásnál egy flashfájllal felvitt fájl törlése */
function get_fajl_torol_gomb(id, tabla, oszlop){
    return '<p class="c" style="margin-top: 0.5rem"><input class="gomb zold" type="button" value="Igen" onclick="$.ajax({type: \'POST\',url : \'esemenyek.php\',data: \'fajl_torol=1&tabla=' + tabla + '&oszlop=' + oszlop + '&id=' + id + '\', success: function(html){}}); alert2(); $(\'#fajl_' + tabla + '_' + oszlop + '_' + id + '\').fadeOut();"> <input class="gomb piros" type="button" value="Nem" onclick="alert2()">';
}


/* Userek belépése az adminhoz */
function login_mentes(id){

    $.ajax({
        url : 'beleptetes.php',
        type: 'POST',
        data: esamp2es('id='+id+'&amp;rendes_nev='+$('#rendes_nev_'+id).val()+'&amp;nev='+$('#nev_'+id).val()+'&amp;jelszo='+$('#jelszo_'+id).val()+'&amp;jog='+$('#jog_'+id).val()+'&amp;nyelv='+$('#nyelv_'+id).val()+'&amp;login_mentes=' + new Date().getTime()),
        success: function(html){
            alert2(html);
            $('#jelszo_'+id).val('');
        }
    });

}

/* Userek belépése az adminhoz */
function login_torol(id){

    $.ajax({
        url : 'beleptetes.php',
        type: 'POST',
        data: esamp2es('id='+id+'&amp;login_torol=' + new Date().getTime()),
        success: function(html){
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
        success: function(html){
            alert2();
            $(jquery_string).html(html);
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

    /* Ha nincs kjelölve a bal navpanelben egy sor sem, akkor az első kijelöltjük */
    setTimeout(function() {

        if( !$('#szamlaList li.szamla_sor').hasClass('kijelolve') && !_get('uj') ) {
            $('#szamlaList li.szamla_sor').eq(0).find('a').trigger('click');
        }

    }, 1);

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

            if( nbWidth > 150 && nbWidth < 800 ) {
                $('#tartalomBal').css('width', nbWidth);
                $('#tartalomJobb').css('width', 'calc(100% - ' + (Math.abs(nbWidth) + 16) + 'px)');
                $('.muveletek').css('width', 'calc(100% - ' + (Math.abs(nbWidth) + 16) + 'px)');

                createCookie('csuszkaSzelesseg', Math.abs(nbWidth), 365);
            }
        }
    });

    $(document).on('mouseup', function(event) {
        click = false;

        $('body').removeClass('noselect');
    });

    $(document).on('keydown', function(e) {
        if( baseName(location.href) == 'bejovo_szamlak' || baseName(location.href) == 'kimeno_szamlak' ) {
            if( e.keyCode == 38 || e.keyCode == 40 ) {
                e.preventDefault();
            }
        }
    });

    /* Léptetés lefele nyíllal, lépkedve le fel */
    $(document).on('keyup', function(e) {
        if( e.keyCode == 38 || e.keyCode == 40 ) {
            var className = e.target.className;
            popup_display = $('#popup').css('display');

            if( (className !== undefined && className.indexOf('disableNav') !== -1) || popup_display == 'block' || $('#alert2').length || !$('#beleptet_alert').hasClass('dn') || naptar_latszik_nincs_leptetes ) {
                return false;
            }

            var ketHasabos = new Array(
                'anyagigenyles',
                'anyagrendeles',
                'bejelentes',
                'bejovo_szamlak',
                'beosztas',
                'cash_flow',
                'cikktorzs',
                'dokumentumtar',
                'energia',
                'erteklista',
                'eseti_fenntartas',
                'eszkozkezeles',
                'felmeresi_terv',
                'fenntartasi_szerzodes',
                'ktghely',
                'ktgnem',
                'login',
                'munkalap',
                'projektkezeles',
                'projektigenyles',
                'raktar',
                'rendszer',
                'rendszeres_karbantartas',
                'szabadsagigeny',
                'szallito',
                'szamla',
                'szerepkor',
                'tervezes',
                'torzsadat',
                'utemezett_feladatok',
                'uzemeltetesi_naplo'
            );

            /* Eltávolítjuk a bal oldali kijelöléseket */
            kijelolesEltavolit();

            if( inArray(baseName(location.href), ketHasabos) ) {
                tabla = baseName(location.href);

                var active = $('#szamlaList li.szamla_sor.kijelolve').index('#szamlaList li.szamla_sor'),
                    szamlaDb = $('#szamlaList li.szamla_sor').length - 1;

                $('#szamlaList li.szamla_sor').removeClass('kijelolve');

                if( e.keyCode == 38 ) {
                    kovSzamlaSor = active - 1;

                    if( kovSzamlaSor < 0 ) {
                        kovSzamlaSor = szamlaDb;
                    }
                } else if( e.keyCode == 40 ) {
                    kovSzamlaSor = active + 1;

                    if( kovSzamlaSor > szamlaDb ) {
                        kovSzamlaSor = 0;
                    }
                }

                $('#szamlaList li.szamla_sor').eq(kovSzamlaSor).addClass('kijelolve');

                var szamlaID = $('#szamlaList li.szamla_sor').eq(kovSzamlaSor).attr('data-recid');

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

                scrollLeptet();
                e.preventDefault();
            }
        }
    });

    var delay = (function() {
        var timer = 0;
        return function(callback, ms) {
            clearTimeout(timer);
            timer = setTimeout(callback, ms);
        };
    })();

    /*Gyorskereső*/
    $(document).on('keyup', '#rendezesKeres', function(e) {
        var that = $(this);

        delay(function() {
            $.ajax({
                url : that.attr('data-tabla'),
                type: 'GET',
                data: esamp2es('ksz=' + that.val() + '&amp;gyorskereso_ajax=' + new Date().getTime()),
                success: function (response) {
                    $('#tartalomBal').html(response);
                }
            });
        }, 650);
    });

    /* Általánosan kattintva a dokumentumban */
    $(document).on('click', function(e) {
        $('.kijeloltek_muv').removeClass('active');

        /* Beosztás naptár felületéhez */
        $('.foglalt').removeClass('kijelol');
        $('#mehet_gomb_kivesz').attr('disabled', 'disabled');
    });
    /* Megnyomva az Esc-et, a popup bezáródik, de csak akkor, ha az alert nincs nyitva */
    $(document).on('keyup', function(e) {
        if (e.which == 27 && !$('#alert2').is(':visible')) {
            popupZar();
        }
    });
    /* Általánosan igaz, hogy inputokon Enter-t ütve tabozzunk */
    $(':input[type!="hidden"]').each(function (i) {
        $(this).attr('tabindex', i + 1);
    });

    $(document).on('keyup', 'input[type="checkbox"]', function(e) {
        if( e.keyCode == 32 ) {
            var val = $(this).prop('checked');

            if( val ) {
                $(this).prop('checked', false).removeClass('mod');
            } else {
                $(this).prop('checked', true).addClass('mod');
            }
        }
    });

    $(document).on('keyup', 'input', function(e) {
        if (e.which == 13 && !$('#alert2').is(':visible') ) {
            
            mezo_nev = $(this).attr('name');
            stop = false;
            $(this).parents('form').find('input, select, textarea').filter(':not(:disabled)').each(function() {
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

    /* Beosztás naptár bejegyzés törlésre kijelölés */
    $(document).on('click', '.foglalt', function(e) {
        naptar_id = $(this).attr('data-recid');
        $('#naptar_torol_id').val(naptar_id);
        $('#mehet_gomb_kivesz').removeAttr('disabled');
        $('.foglalt').removeClass('kijelol');
        $('.beosztas_'+naptar_id).addClass('kijelol');
        $.ajax({
            url: 'beosztas.php',
            type: 'POST',
            data: {
                id                  : naptar_id,
                get_beosztas_adatok : new Date().getTime()
            },
            success: function (response) {
                $('#kijelolt_erteke').html(response);
            }
        });
        e.stopPropagation();
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
    $(document).on('keydown', function(e) {
        if( e.shiftKey || e.ctrlKey ) {
            if( !shiftCtrl ) {
                $('.kijCont').addClass('link');
                shiftCtrl = true;
            }
        }
    });

    $(document).on('keyup', function(e) {
        if( !e.shiftKey || !e.ctrlKey ) {
            $('.kijCont').removeClass('link');
            shiftCtrl = false;
        }
    });

    /* Aloldalon/fülek alatti felsorolás kijelölések lekezelése. Pl. felhasználók szállodái. */
    $(document).on('click', '.kijCont .kijSor', function(e) {
        var parent = $(this).parents('.kijCont'),
            tabla  = parent.attr('data-tabla'),
            mellek = parent.attr('data-mellek'),
            sorok  = parent.find('.kijSor');

        /* SHIFT gomb lekezelése */
        if( e.shiftKey ) {

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
                            parent.find('.kijSor[data-i="' + i + '"]').addClass('kijelolve');
                        }
                    }
                } else {
                    for( var i = akt_id; i >= uj_akt_id; i-- ) {
                        if( !parent.find('.kijSor[data-i="' + i + '"]').hasClass('kijelolve') ) {
                            parent.find('.kijSor[data-i="' + i + '"]').addClass('kijelolve');
                        }
                    }
                }

                kijeloltSzamlal( sorok, 'mellek', tabla, mellek );
            }

        /* CTRL gombbal további sorok megjelölése */
        } else if( e.ctrlKey ) {

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
            kijelolheto = $('#szamlaList').hasClass('kijelolheto');

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

                /* Minden olyan sor, ami nem a csoport része, arról levesszük a kijelölést */
                sorok.each(function () {
                    if($(this).attr('data-csoportid')!=id){
                        $(this).removeClass('kijelolve');
                    }
                });

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
    $('#tartalomBal').on('click', '#szamlaList li.szamla_sor', function(e) {
        var sorok       = $('#szamlaList li.szamla_sor'),
            csoportok   = $('#szamlaList li.csoport'),
            kijelolheto = $('#szamlaList').hasClass('kijelolheto');


        /* Ha nem használtunk sem CTRL-t, sem SHIFT gombot, vagy ha a több sor kijelölése nem engedélyezett */
        if( (!e.ctrlKey && !e.shiftKey) || !kijelolheto ) {

            if( !formModositva ) {
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
                szamlaBetolt(recid, fajl, mellek);
            }

            scrollLeptet();
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

    $(window).bind('beforeunload', function(e) {
        if( formModositva && !_get('jogosultsag') ) {
            return 'Biztos elhagyja az oldalt mentés nélkül?';
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

            if( $(this).hasClass('datepicker') ) {
                if( formAdatok[name] != '0000-00-00' ) {
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
                }
            }

            if( $(this).hasClass('datetimepicker') ) {
                if( formAdatok[name] != '0000-00-00 00:00' ) {
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
                    taroltVal = '\xa0\xa0\xa0\xa0.\xa0\xa0.\xa0\xa0\xa0\xa0:\xa0\xa0';
                }
            }

            //Újsort le kell csérélni. Checkboxok miatt kell ez az if elé
            if(val && val!=true){
                val = val.replace(/(\r|\n)/g, '#UJSOR#').replace(/'/g, '#APOSZTROF#');
            }
            if( taroltVal != val ) {
                $(this).addClass('mod');
            } else {
                $(this).removeClass('mod');
            }

            var form = $(this).parents('#szerk_form');

            mod = 0;
            form.find('input, select, textarea').each(function() {
                if( $(this).hasClass('mod') ) {
                    mod++;
                }
            });
            if( mod ) {

                $('#kilepes_gomb').val('Kilépés mentés nélkül');
                $('#mehet_gomb').attr('disabled', false).addClass('gombb_alert');
                $('#visszavonas_gomb').attr('disabled', false);

                formModositva = true;
            } else {
                $('#kilepes_gomb').val('Kilépés');
                $('#mehet_gomb').attr('disabled', true).removeClass('gombb_alert');
                $('#visszavonas_gomb').attr('disabled', true);

                formModositva = false;
            }

            e.preventDefault();
        }
    });

    if( $('.szamla_sor.kijelolve').hasClass('kijelolve') ) {
        active_top = $('.szamla_sor.kijelolve').position().top;
        $('#tartalomBal').scrollTop(active_top - 250);
    }

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
        if( e.keyCode == 13 && $('#alert2').length && !$('#alert2').hasClass('rFalse') ) {
            $('#alert2 input[value="Ok"]').click();
        }
    });

    $(document).ajaxSend(function(event, xhr, options) {
        if( baseName(options.url) != 'kapcs_autocomplete' && baseName(options.url) != 'szakter_autocomplete' && baseName(options.url) != 'autocomplete' ) {
            $('#ajaxfedo').removeClass('dn');
        }
    });

    /* Ha egy ajax hívás lefutott, akkor az ajaxfedo-t elrejtjük, illetve az input.focus-t kijelöljük */
    $(document).ajaxComplete(function (event, xhr, settings) {
        /* Felhasználóknál szálloda hozzáadása popup miatt ezt mégis kivettük, mert nem működött az autocomplete popupban: $('form.popupForm input.focus').select();*/
        $('#ajaxfedo').addClass('dn');
    });

    $(document).on('click', 'ul.telephelyek a.nyit', function() {
        $(this).parent('li.alkatos').find('ul.alkatok').eq(0).toggleClass('active');
        $(this).find('span.icon').toggleClass('active');

        return false;
    });

    /* Ciklus tipus váltásakor megfelelő div megjelenítése */
    $(document).on('change', '.f_cikltip', function() {
        $('.cikltip').hide();
        if($('#tipus_'+$(this).val()).length){
            $('#tipus_'+$(this).val()).show();
        }

        /* Váltáskor mindent nullázunk */
        $('#ciklnap').val('');
        $('#hetkoz_kent').prop('checked', false);

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
            alert2('Kérjük állítsa be a szűrési feltételt!');
        }else{
            /* Autocompletenek az ID-ját tegyük az URL-be */
            if($('.f_felt').attr('id')){
                felt_id = $('.f_felt').attr('id').replace('_autocomp', '');
                if($('#'+felt_id).length){
                    felt = $('#'+felt_id).val();
                }
            }

            kerdo_es = '&';
            if(get_get(['mezo', 'op', 'felt']).indexOf('?')<0){
                kerdo_es = '?';
            }
            location.href=get_get(['mezo', 'op', 'felt'])+kerdo_es+'mezo='+$('.f_szuro_mezo').val()+'&op='+$('.f_szuro_mezo_operator').val()+'&felt='+felt;
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

        /* Hibaellenőrzés, majd mehet */
        if(!feltetel_ertek){
            alert2('Kérjük állítsa be a szűrési feltételt!');
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

            /*Szűrő inputok nullázása*/
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

        $.ajax({
            url: 'esemenyek.php',
            type: 'POST',
            data: {
                tabla            : $('#o_szuro_mezo').attr('data-tabla'),
                feltetel         : feltetel,
                html             : $('#o_feltetel_halmaz').html(),
                f_o_szures_submit: new Date().getTime()
            },
            success: function (response) {
                response = JSON.parse(response);

                if( response.hiba ) {
                    if( response.gomb ) {
                        alert2(response.hiba, response.gomb);
                    } else {
                        alert2(response.hiba, '', 'Hiba');
                    }
                }
                if( response.mehet ) {
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
                location.reload();
            }
        });
    });
    /* Összetett szűrők felételének törlése */
    $(document).on('click', '.f_o_szures_ftorol', function(e) {
        $('#o_feltetel_halmaz').html('');
        $(this).hide();
    });

    /* És vagy értékének letárolása */
    $(document).on('change', '.es_vagy', function(e) {
        $(this).find('option:selected').attr('selected', 'selected');
    });


    /* Szám mezők formázása */
    $(document).on('keyup', '.f_num', function(e) {
        onlyNumbers($(this));
    });
    $(document).on('blur', '.f_num', function(e) {
        tort = 2;
        if($(this).attr('data-tort')){
            tort = $(this).attr('data-tort');
        }
        $(this).val( ezresek($(this).val().replace(/ /g, '').replace('.', '').replace(',', '.'), tort, ',', ' ') );
        $(this).focus().blur(); /* Ez azért kell, hogy ha 0 volt az értéke, kitörlöm üresnek, akkor az ezresek 0-át vissza teszi bele, tehát a mezőről le kellene venni a 'mod' classzt, és ezt egy focus-blur-al oldom meg */
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
        nev_autocompl       = $('#cikk_nev_'+recid_egyedi_azon).html();
        igmen               = $('#th'+telephely_id+'a'+cikk_id).html();
        mee                 = $('#th'+telephely_id+'a'+cikk_id+'_mee').html();

        $('#telephely_id')  .val(telephely_id);
        $('#nev_autocomp')  .val(nev_autocompl);
        $('#nev')           .val(cikk_id);
        $('#igmen')         .val(igmen);
        $('.insertMee')     .html(mee);

        $('#mehet_gomb').removeAttr('disabled').addClass('gombb_alert');
    });
    $(document).on('click', '.f_anyag_rendelkezesre_all', function(e) {

        telephely_id = $(this).attr('data-telephely_id');
        cikkid_origi = $(this).attr('data-cikkid_origi');
        cikk_id      = $(this).attr('data-cikk_id');

        $.ajax({
            url: 'anyagigenyles.php',
            type: 'POST',
            data: esamp2es('telephely_id=' + telephely_id + '&amp;cikk_id=' + cikkid_origi + '&amp;f_anyag_rendelkezesre_all=' + new Date().getTime()),
            success: function (response) {
                $('#sor_'+cikk_id).fadeOut();
            }
        });
    });

   /* Loginnév mező formázása */
    $(document).on('keyup', '.f_loginnev', function(e) {
        $(this).val( $(this).val().replace(/\W/g, '') );
    });

    $(document).on('keyup', function(e) {
        if( e.keyCode == 27 ) {
            alert2();
            felh_valt();
            kijelolesEltavolit();
        }
    });

    /* Autocomplete érték visszaírásához kell */
    ev = false;
    $(document).on('click', function(event) {
        if( ev ) {
            var id = event.target.id;

            if( ev !== false && id !== undefined && id.indexOf('_autocomp') === -1 ) {
                var elementID = ev.target.id;

                if( elementID.indexOf('_autocomp') !== -1 ) {
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

    $(document).on('keydown', 'input[type="text"]', function(event) {
        if( event.keyCode == 9 ) {
            var elementID = $(this).attr('id');

            if( elementID.indexOf('_autocomp') !== -1 ) {
                var originVal = $(this).attr('data-origin-val');
                var ertekID = $(this).val();

                if( ertekID ) {
                    $(this).val(originVal).change();
                }
            }
        }
    });

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
    $(document).on('click', '.f_pri_egytobb', function() {
        egy_tobb = $(this).attr('data-egytobb');
        if(egy_tobb=='egy'){
            $('#egy_tobb_tobb').prop('checked', false);
            $('#egy_tobb').val(0);
        }else{
            $('#egy_tobb_egy').prop('checked', false);
            $('#egy_tobb').val(1);
        }

        $.ajax({
            url : 'projektigenyles.php',
            type: 'POST',
            data: {
                egy_tobb     : egy_tobb,
                f_pri_egytobb: new Date().getTime()
            },
            success: function (response) {
                $('#telephely_id').parent().parent().replaceWith(response);
            }
        });

    });

});

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

        location.href = 'bejelentes.php?uj&bejelento=' + nev + '&telphely=' + telephely;
    }
}

function bejBejManualis( value ) {
    location.href = 'bejelentes.php?uj&manualisan&telphely=' + value;
}

function bejBejelentok( value ) {
    if( value ) {
        $.ajax({
            url : 'esemenyek.php',
            type: 'GET',
            async: false,
            data: esamp2es('telephely_id=' + value + '&amp;getBejBejelentok=' + new Date().getTime()),
            success: function(response) {
                response = JSON.parse(response);

                if( response.hiba ) {
                    if( response.gomb ) {
                        alert2(response.hiba, response.gomb);
                    } else {
                        alert2(response.hiba, '', 'Hiba');
                    }
                }
            }
        });
    }
}

function bejSzallodaValaszt() {
    $.ajax({
     	url : 'bejelentes.php',
        type: 'POST',
     	data: esamp2es('bejSzallodaValaszt=' + new Date().getTime()),
     	success: function(response) {
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

                    location.href = baseName(location.href) + '.php';
         		}
         	}
        });
    }
}

/* Külső kivitelező hozzárendelése */
function kulsokivHozzarend( that ) {
    var szallitoid = $(that).find('input[name="szallito_id"]');

    if( szallitoid.val() ) {
        $.ajax({
         	url : 'munkalap.php',
         	type: 'POST',
         	data: {
                recid            : _get('id'),
                validate         : 'false',
                szallitoid       : szallitoid.val(),
                kulsokivHozzarend: new Date().getTime()
            },
         	success: function(response) {
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
        alert2('Kérem válasszon külső kivitelezőt!');
    }

    return false;
}

function munkFeladatTorol( recid, that ) {
    if( recid ) {
        $.ajax({
         	url : 'esemenyek.php',
         	type: 'POST',
            async: false,
         	data: {
                recid: recid,
                munkFeladatTorol: new Date().getTime()
            },
         	success: function(response) {
         		if(response) {
         			response = JSON.parse(response);

                    $(that).parents('tr').remove();
                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'tetel');
         		}
         	}
        });
    } else {
        $(that).parents('tr').remove();
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
                    recid: recid,
                    csak_kerdezunk: csak_kerdezunk,
                    bejelentesIgazolas: new Date().getTime()
                },
                success: function(response) {
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

function munkAnyagTorol( recid, that ) {
    if( recid ) {
        $.ajax({
         	url : 'esemenyek.php',
         	type: 'POST',
            async: false,
         	data: {
                recid: recid,
                munkAnyagTorol: new Date().getTime()
            },
         	success: function(response) {
         		if(response) {
         			response = JSON.parse(response);

                    $(that).parents('tr').remove();
         		}
         	}
        });
    } else {
        $(that).parents('tr').remove();
    }
}

/* Anyagigénylés táblázat sor hozzáadás */
function munkAnyagHozzaad( formID, igenyles ) {
    var form = $(formID);

    var cikknev     = form.find('input[name="cikk_id_autocomp"]'),
        cikkID      = form.find('input[name="cikk_id"]'),
        cikkid_origi= form.find('input[name="cikkid_origi"]'),
        felh_menny  = form.find('input[name="felh_menny"]'),
        szuk_menny  = form.find('input[name="szuk_menny"]'),
        cikkmee     = form.find('.insertMee').eq(0).text();

    var addCikkID = form.find('input[name="addCikkID[]"]').map(function(){return $(this).val();}).get();

    if( addCikkID.length ) {
        for(key in addCikkID) {
            if( addCikkID[key] == cikkID.val() ) {
                alert2('Ez az anyag már hozzá van adva a munkalaptételhez!', '', 'Hiba');

                cikkID.val('');
                cikknev.val('');
                felh_menny.val('');
                szuk_menny.val('');
                form.find('.insertMee').text('');

                document.getElementById('cikk_id_autocomp').select();

                return false;
            }
        }
    }

    if( cikknev.val() == '' || cikkID.val() == '' ) {
        alert2('Válassza ki a cikket!');
        document.getElementById('cikk_id_autocomp').select();
        return false;
    }

    if( !igenyles && felh_menny.val() == '' ) {
        alert2('Adja meg a felhasznált mennyiséget!');
        document.getElementById('felh_menny').select();
        return false;
    }

    if( igenyles ) {
        if( felh_menny.val() == '' && szuk_menny.val() == '' ) {
            alert2('Adja meg a felhasznát, vagy a szükséges mennyiséget!');
            document.getElementById('felh_menny').select();
            return false;
        }
    }

    var sz_td = '';
    if( igenyles ) {
        sz_td = '<td class="r">' + szuk_menny.val() + '<input type="hidden" name="addCikkSzukMenny[]" value="' + szuk_menny.val() + '"></td>';
    }

    var table_sor = '<tr>' +
                        '<td>' + cikkid_origi.val() + '<input type="hidden" name="addCikkID[]" value="' + cikkid_origi.val() + '"><input type="hidden" name="addCikkUj[]" value="1"></td>' +
                        '<td>' + cikknev.val() + '</td>' +
                        '<td class="r">' + felh_menny.val() + '<input type="hidden" name="addCikkFelhMenny[]" value="' + felh_menny.val() + '"></td>' +
                        sz_td +
                        '<td>' + cikkmee + '</td>' +
                        '<td></td>' +
                        '<td><a onclick="munkAnyagTorol( false, this );"><span class="icon"><i class="material-icons">delete</i></span></a></td>' +
                    '</tr>';

    $('#insertTable table.csikos').append(table_sor);

    cikkID.val('');
    cikknev.val('');
    felh_menny.val('');
    szuk_menny.val('');
    form.find('.insertMee').text('');

    document.getElementById('cikk_id_autocomp').select();

    return false;
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
                            if(response) {

                            }
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
            alert2('Kérem jelöljön ki sorokat!');
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
            alert2('Kérem jelöljön ki sorokat!');
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
            alert2('Kérem jelöljön ki sorokat!');
        }
    }
}

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

                            kijelolesEltavolit();
                            szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
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

function varolistahozAdas(csak_kerdezunk) {

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

                var indok = $('textarea[name="varolista_indok"]').val();
                if( indok == '' && csak_kerdezunk === undefined ) {
                    alert_raz();
                    document.getElementById('varolista_indok').focus();
                    return false;
                }

                $.ajax({
                    url : 'esemenyek.php',
                    type: 'POST',
                    data: {
                        idk             : JSON.stringify(idk),
                        page            : page,
                        indok           : indok,
                        csak_kerdezunk  : csak_kerdezunk,
                        varolistahozAdas: new Date().getTime()
                    },
                    success: function(response) {
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

                            kijelolesEltavolit();
                            szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
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

                            /* Adatlap frissítése */
                            szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
                            /* navpanel frissítése (minden kijelölt sort) */
                            szamlaSorok.each(function() {
                                szamlaSorFrissit('anyagigenyles', $(this).attr('data-recid'));
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

function anyagigenyles_jovahagy(csak_kerdezunk) {

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
                        idk                     : JSON.stringify(idk),
                        page                    : page,
                        jhmen                   : $('#jhmen').val(),
                        csak_kerdezunk          : csak_kerdezunk,
                        anyagigenyles_jovahagy  : new Date().getTime()
                    },
                    success: function(response) {
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
                            szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
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
                            szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
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

function anyagigenyles_mind_jovahagy(csak_kerdezunk) {

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
                        idk                         : JSON.stringify(idk),
                        page                        : page,
                        csak_kerdezunk              : csak_kerdezunk,
                        anyagigenyles_mind_jovahagy : new Date().getTime()
                    },
                    success: function(response) {
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
                            szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
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

/* Munkalaphoz adás - Megszűnt 2017.09.06 */
/*
function munkalaphozAdas(csak_kerdezunk) {

    if(balnav_kijelol_ell()){

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

        var munkalapID = $('#munkalapID').val();
        if(csak_kerdezunk){
            munkalapID = '-1';
        }

        if( munkalapID ) {
            if( objLength(idk) ) {
                $.ajax({
                    url : 'esemenyek.php',
                    type: 'POST',
                    data: {
                        idk             : JSON.stringify(idk),
                        munkalapID      : munkalapID,
                        csak_kerdezunk  : csak_kerdezunk,
                        munkalaphozAdas : new Date().getTime()
                    },
                    success: function(response) {
                        if(response) {
                            response = JSON.parse(response);

                            if( response.hiba ) {
                                alert2(response.hiba, '', 'Hiba');
                                return false;
                            }

                            if(csak_kerdezunk) {
                                csoportMuvelet('munkalaphoz_adas');
                            } else {
                                location.href = 'munkalap.php?id=' + response.recid + '&modosit';

                                kijelolesEltavolit();
                                popupZar();
                            }
                        }
                    }
                });
            } else {
                alert2('Nincsenek kijelölt sorok!', '', 'Hiba');
            }
        } else {
            alert2('Kérem válasszon egy munkalapot!');
        }
    }
    return false;
}
*/
function munkAnyagIgenyles( that, csak_kerdezunk ) {
    var addCikkUj = $(that).find('input[name="addCikkUj[]"]'),
        addCikkID = $(that).find('input[name="addCikkID[]"]'),
        addCikkFelhMenny = $(that).find('input[name="addCikkFelhMenny[]"]'),
        addCikkSzukMenny = $(that).find('input[name="addCikkSzukMenny[]"]');

    if( addCikkID.length && addCikkFelhMenny.length ) {
        $.ajax({
         	url : 'esemenyek.php',
         	type: 'POST',
         	data: {
                recid: _get('id'),
                addCikkUj: addCikkUj.serialize(),
                addCikkID: addCikkID.serialize(),
                addCikkFelhMenny: addCikkFelhMenny.serialize(),
                addCikkSzukMenny: addCikkSzukMenny.serialize(),
                csak_kerdezunk: csak_kerdezunk,
                munkAnyagIgenyles: new Date().getTime()
            },
         	success: function(response) {
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

                    if( response.refresh ) {
                        location.href = baseName(location.href) + '.php';
                        return false;
                    }

                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'tetel');
                    popupZar();
                    alert2();
         		}
         	}
        });
    }

    return false;
}

function munkAnyagVetelezes( that ) {
    var addCikkUj = $(that).find('input[name="addCikkUj[]"]'),
        addCikkID = $(that).find('input[name="addCikkID[]"]'),
        addCikkFelhMenny = $(that).find('input[name="addCikkFelhMenny[]"]');

    if( addCikkID.length && addCikkFelhMenny.length ) {
        $.ajax({
         	url : 'esemenyek.php',
         	type: 'POST',
         	data: {
                recid: _get('id'),
                addCikkUj: addCikkUj.serialize(),
                addCikkID: addCikkID.serialize(),
                addCikkFelhMenny: addCikkFelhMenny.serialize(),
                munkAnyagVetelezes: new Date().getTime()
            },
         	success: function(response) {
         		if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        if( response.gomb ) {
                            alert2(response.hiba, response.gomb);
                        } else {
                            alert2(response.hiba, '', 'Hiba');
                            document.getElementById('cikk_id_autocomp').focus();
                        }

                        return false;
                    }

                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'tetel');
                    popupZar();
         		}
         	}
        });
    } else {
        alert2('Kérem válasszon felhasznált mennyiséget!');
        document.getElementById('cikk_id_autocomp').focus();
    }

    return false;
}

function atadasMuszVeznek() {
    if(balnav_kijelol_ell()){
        if( _get('id') ) {
            $.ajax({
                url : 'munkalap.php',
                type: 'POST',
                data: {
                    recid: _get('id'),
                    atadasMuszVeznek: new Date().getTime()
                },
                success: function(response) {
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

                        location.href = 'munkalap.php';
                    }
                }
            });
        }
    }
}

function atadasDiszpecsernek( that ) {
    if(balnav_kijelol_ell()){
        var erteklista_id = $(that).find('input[name="erteklista_id"]'),
                     memo = $(that).find('textarea[name="atadas_memo"]');

        if( erteklista_id ) {
            $.ajax({
                url : 'esemenyek.php',
                type: 'POST',
                data: {
                    recid: _get('id'),
                    erteklista_id: erteklista_id.val(),
                    memo: memo.val(),
                    atadasDiszpecsernek: new Date().getTime()
                },
                success: function(response) {
                    if(response) {
                        response = JSON.parse(response);

                        if( response.hiba ) {
                            if( response.gomb ) {
                                alert2(response.hiba, response.gomb);
                            } else {
                                alert2(response.hiba, '', 'Hiba');
                                document.getElementById('erteklista_id_autocomp').focus();
                            }

                            return false;
                        }

                        location.href = 'munkalap.php';
                    }
                }
            });
        } else {
            alert2('Adja meg az átadás okát!');
            document.getElementById('erteklista_id_autocomp').focus();
        }
    }

    return false;
}

function feladatHozzaadas( that ) {

    if(balnav_kijelol_ell()){

        var erteklista_id = $(that).find('input[name="erteklista_id"]'),
            memo = $(that).find('textarea[name="feladat_memo"]');

        if( erteklista_id ) {
            $.ajax({
                url : 'esemenyek.php',
                type: 'POST',
                data: {
                    recid: _get('id'),
                    erteklista_id: erteklista_id.val(),
                    memo: memo.val(),
                    feladatHozzaadas: new Date().getTime()
                },
                success: function(response) {
                    if(response) {
                        response = JSON.parse(response);

                        if( response.hiba ) {
                            if( response.gomb ) {
                                alert2(response.hiba, response.gomb);
                            } else {
                                alert2(response.hiba, '', 'Hiba');
                                document.getElementById('erteklista_id_autocomp').focus();
                            }

                            return false;
                        }

                        szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'tetel');
                        popupZar();
                    }
                }
            });
        } else {
            alert2('Adja meg az elvégzett munkát!');
            document.getElementById('erteklista_id_autocomp').focus();
        }
    }
    return false;
}

function munkalapDiszpKozp() {
    if(balnav_kijelol_ell()){
        $.ajax({
            url : 'esemenyek.php',
            type: 'POST',
            data: {
                recid: _get('id'),
                munkalapDiszpKozp: new Date().getTime()
            },
            success: function(response) {
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
    }

    return false;
}

function munkalapTeruletiVezeto() {

    if(balnav_kijelol_ell()){

        $.ajax({
            url : 'esemenyek.php',
            type: 'POST',
            data: {
                recid: _get('id'),
                munkalapTeruletiVezeto: new Date().getTime()
            },
            success: function(response) {
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

    }

    return false;
}

function munkalapMuszakiVezeto() {

    if(balnav_kijelol_ell()){

        $.ajax({
            url : 'esemenyek.php',
            type: 'POST',
            data: {
                recid: _get('id'),
                munkalapMuszakiVezeto: new Date().getTime()
            },
            success: function(response) {
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

    }

    return false;
}

function munkalapAdminisztrator() {

    if(balnav_kijelol_ell()){

        $.ajax({
            url : 'esemenyek.php',
            type: 'POST',
            data: {
                recid: _get('id'),
                munkalapAdminisztrator: new Date().getTime()
            },
            success: function(response) {
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

    }

    return false;
}

function munkalapMobil( that ) {

    if(balnav_kijelol_ell()){

        /* Hozzárendelt csoport törlése */
        var munkMobilKivetel = $(that).find('input[name="munkMobilKivetel[]"]');

        /* Hozzárendelt személyzet törlése */
        var munkHozzMegszuntet = $(that).find('input[name="munkHozzMegszuntet[]"]');

        /* Törzsadat csatolása a munkalaphoz */
        var addTorzsID = $(that).find('input[name="addTorzsID[]"]'),
            addTorzsMunkID = $(that).find('input[name="addTorzsMunkID[]"]');

        var idk = {};
        var i = 1;

        var sorok = $('#hozzBeosztasNelkul table tr.kijSor');
        if( sorok.length ) {
            sorok.each(function() {
                var id = $(this).attr('data-recid');

                idk[i] = id;
                i++;
            });
        }

        var sorok = $('#szemRendelt table tr.kijSor');
        if( sorok.length ) {
            sorok.each(function() {
                var id = $(this).attr('data-recid');

                idk[i] = id;
                i++;
            });
        }

        if( objLength(idk) ) {
            $.ajax({
                url : 'munkalap.php',
                type: 'POST',
                data: {
                    idk: JSON.stringify(idk),
                    munkMobilKivetel: munkMobilKivetel.serialize(),
                    munkHozzMegszuntet: munkHozzMegszuntet.serialize(),
                    addTorzsID: addTorzsID.serialize(),
                    addTorzsMunkID: addTorzsMunkID.serialize(),
                    munkalapMobil: new Date().getTime()
                },
                success: function(response) {
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

                        location.href = get_get();
                    }
                }
            });
        }
    }

    return false;
}


/* Munkalap átadása külső csoportnak */
function munkalapKulso( that ) {

    if(balnav_kijelol_ell()){

        var szallito_id = $(that).find('input[name="szallito_id"]'),
        szallito_id_autocomp = $(that).find('input[name="szallito_id_autocomp"]'),
        szemelyzet_id = $(that).find('select[name="p_szemelyzet_id"]');

        if( szallito_id.val() && szemelyzet_id.val() ) {
            alert2('Külső kivitelezőt és hozzárendelt személyzetet egyszerre nem lehet megadni!');
            szemelyzet_id.val('');
            szallito_id_autocomp.val('');
            szallito_id.val('');
            document.getElementById('szallito_id_autocomp').focus();
            return false;
        }

        if( !szallito_id.val() && !szemelyzet_id.val() ) {
            alert2('Válasszon külső kivitelezőt!');
            document.getElementById('szallito_id_autocomp').focus();
            return false;
        }

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid        : _get('id'),
                szallito_id  : szallito_id.val(),
                szemelyzet_id: szemelyzet_id.val(),
                munkalapKulso: new Date().getTime()
            },
            success: function(response) {
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

    }

    return false;
}

/* Munkalap készre jelentés */
function munkalapKeszre(csak_kerdezunk) {

    if(balnav_kijelol_ell()){

        alert2();

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid         : _get('id'),
                csak_kerdezunk: csak_kerdezunk,
                munkalapKeszre: new Date().getTime()
            },
            success: function(response) {
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

                    location.href = baseName(location.href) + '.php';
                }
            }
        });

    }
    return false;
}

/* Munkalap lezárása */
function munkalapLezaras(csak_kerdezunk) {

    if(balnav_kijelol_ell()){

        alert2();

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid: _get('id'),
                csak_kerdezunk: csak_kerdezunk,
                munkalapLezaras: new Date().getTime()
            },
            success: function(response) {
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

                    location.href = baseName(location.href) + '.php';
                }
            }
        });

    }
    return false;
}

/* Munkalap átadása telepített csoportnak */
function munkalapTelepitett(csak_kerdezunk) {

    if(balnav_kijelol_ell()){

        alert2();

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid             : _get('id'),
                csak_kerdezunk    : csak_kerdezunk,
                munkalapTelepitett: new Date().getTime()
            },
            success: function(response) {
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

    }

    return false;
}

/* Munkalap felvétele */
function munkalapFelvesz( recid ) {

    if(balnav_kijelol_ell()){

        alert2();

        if( recid ) {
            $.ajax({
                url : 'munkalap.php',
                type: 'POST',
                data: {
                    recid           : recid,
                    munkalapFelvesz : new Date().getTime()
                },
                success: function(response) {
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

                        szamlaBetolt(recid, baseName(location.href) + '.php', 'tetel');
                    }
                }
            });
        }

        alert2();
    }
}

/* Munkalap készítése */
function munkalapKeszit(csak_kerdezunk) {

    if(balnav_kijelol_ell()){

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
                    munkalapKeszit  : new Date().getTime()
                },
                success: function(response) {
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

                        location.href = 'munkalap.php?id=' + response.recid + '&modosit';

                        kijelolesEltavolit();
                        popupZar();
                    }
                }
            });
        } else {
            alert2('Nincsenek kijelölt sorok!', '', 'Hiba');
        }
    }
    return false;
}

/* Elutasítás popup mentése */
function bejegyzesElutasitas( that, csak_memo ) {
    var elutasitas_id = $(that).find('input[name="elutasitas_id"]'),
                 memo = $(that).find('textarea[name="elutasitas_memo"]');

    if(!csak_memo && !elutasitas_id.val()){
        alert2('Kérem válassza ki az elutasítás okát!', '<input type="button" class="link gomb" value="Ok" onclick="alert2(); $(\'#elutasitas_id_autocomp\').focus();">', 'Hiba');
        return false;
    }
    if(!memo.val()){
        alert2('Kérjük indokolja az elutasítást!', '<input type="button" class="link gomb" value="Ok" onclick="alert2(); $(\'#elutasitas_memo\').focus();">', 'Hiba');
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
                    if(response) {
                        response = JSON.parse(response);

                        if( response.hiba ) {
                            alert2(response.hiba, response.gomb, 'Hiba');
                            return false;
                        }

                        if( response.kieg ) {
                            alert2(response.kieg, response.gomb, 'Hiba');
                        }

                        kijelolesEltavolit();
                        szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
                        popupZar();
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

/* Csoporthoz adás */
function addCsoport() {
    var ujcsoport = $('input[name="ujcsoportnev"]');

    if( ujcsoport.val() ) {
        var page = $('#szamlaBetolt').attr('data-page');
        $.ajax({
         	url : 'esemenyek.php',
         	type: 'GET',
         	data: esamp2es('page=' + page + '&amp;csoportnev=' + ujcsoport.val() + '&amp;addCsoport=' + new Date().getTime()),
         	success: function(response) {
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

/* Csoportba rendezés */
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
                            if(response) {
                                response = JSON.parse(response);

                                if( response.hiba ) {
                                    alert2(response.hiba, '', 'Hiba');
                                    return false;
                                }

                                kijelolesEltavolit();
                                bejegyzesekLista('#rendezesSelect', 'asc', '', '');
                                popupZar();
                            }
                        }
                    });
                }
            } else {
                alert2('Nincsenek kijelölt sorok!', '', 'Hiba');
            }
        } else {
            alert2('Kérem válasszon egy csoport', '', 'Hiba');
        }
    }

    return false;
}

/* Popupok megjelenítése a sablon/popup mappából */
function csoportMuvelet( oldal, mobilnal_no_statusz_hiba ) {

    /* Csak akkor hajtjuk végre, ha a bal oldali kijelölések is rendben vannak */
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
                        /* Ha már van csoportja */
                        if($(this).hasClass('behuz')){
                            mar_csoportosak += $(this).find('span:first-of-type').html()+'<br>';
                        }
                        /* Ha nem nyitott a státusz */
                        if(!$(this).hasClass('nyitott_statuszu')){
                            nem_nyitott_statuszuak += $(this).find('span:first-of-type').html()+'<br>';
                        }

                        /* Ha nem azonosak a szállodák */
                        if(elozo_telephely_id && $(this).attr('data-telephely_id')!=elozo_telephely_id){
                            nem_azonos_szalloda = true;
                        }
                        elozo_telephely_id = $(this).attr('data-telephely_id');

                    });
                    if(mar_csoportosak){
                        alert2('Az alábbi '+$('#rekord_neve').html().toLowerCase()+' már csoport részei, így nem adhatóak újból csoporthoz:<br>'+mar_csoportosak, '', 'Hiba');
                        return false;
                    }
                    if(nem_nyitott_statuszuak){
                        alert2('Az alábbi '+$('#rekord_neve').html().toLowerCase()+' státuszai nem nyitottak, így nem adhatóak csoporthoz:<br>'+nem_nyitott_statuszuak, '', 'Hiba');
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
                        if(nem_nyitott_statuszuak==0){
                            popupNyit();
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

                case 'munkalapAnyagigenyles':
                    $('#popupTart').html(data);

                    var ajax = $.ajax({
                        url : 'esemenyek.php',
                        type: 'POST',
                        data: {
                            recid: _get('id'),
                            szuk_menny: true,
                            getMunkalapAnyagok: new Date().getTime()
                        },
                        success: function(response) {
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

                                $('#insertTable').html(response.html);
                            }
                        }
                    });

                    $.when( ajax ).then(function() {
                        popupNyit();
                    });
                break;

                case 'munkalapAnyagvetelezes':
                    $('#popupTart').html(data);

                    var ajax = $.ajax({
                        url : 'esemenyek.php',
                        type: 'POST',
                        data: {
                            recid: _get('id'),
                            getMunkalapAnyagok: new Date().getTime()
                        },
                        success: function(response) {
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

                                $('#insertTable').html(response.html);
                            }
                        }
                    });

                    $.when( ajax ).then(function() {
                        popupNyit();
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
                                if(response) {
                                    response = JSON.parse(response);

                                    if( response.hiba ) {
                                        if( response.gomb ) {
                                            alert2(response.hiba, response.gomb);
                                        } else {
                                            alert2(response.hiba, '', 'Hiba');
                                        }

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

                case 'feladatHozzaad':
                    $('#popupTart').html(data);

                    var ajax = $.ajax({
                        url : 'munkalap.php',
                        type: 'POST',
                        data: {
                            recid: _get('id'),
                            getMunkalapFeladatok: new Date().getTime()
                        },
                        success: function(response) {
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

                                $('#insertTable').html(response.html);
                            }
                        }
                    });

                    $.when( ajax ).then(function() {
                        popupNyit();
                    });
                break;

                /* Az elutasítást csak egy soron lehet elvégezni. */
                case 'elutasitas':
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
                                    if(response) {
                                        response = JSON.parse(response);

                                        if( response.hiba ) {
                                            if( response.gomb ) {
                                                alert2(response.hiba, response.gomb);
                                            } else {
                                                alert2(response.hiba, '', 'Hiba');
                                            }
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

                case 'munkalap_kulso':
                    $('#popupTart').html(data);

                    $.ajax({
                        url : 'munkalap.php',
                        type: 'POST',
                        data: {
                            recid: _get('id'),
                            munkalap_kulsoSelect: new Date().getTime()
                        },
                        success: function(response) {
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

                                $('#hozzRendSelect').html(response.select);
                                popupNyit();
                            }
                        }
                    });
                break;

                case 'kulsokivHozzarend':
                    $('#popupTart').html(data);

                    $.ajax({
                        url : 'munkalap.php',
                        type: 'POST',
                        data: {
                            recid: _get('id'),
                            validate: 'true',
                            kulsokivHozzarend: new Date().getTime()
                        },
                        success: function(response) {
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

                                popupNyit();
                            }
                        }
                    });
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

/* Anyagigénylés árazása */
function arazas() {

    if(balnav_kijelol_ell()){

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
                url : 'anyagigenyles.php',
                type: 'POST',
                data: {
                    idk     : JSON.stringify(idk),
                    arazas  : new Date().getTime()
                },
                success: function(response) {
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

    $.ajax({
        url: 'anyagigenyles.php',
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
            erkcimid    : erkcimid,
            arazas_ment : new Date().getTime()
        },
        success: function (response) {

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
            }

            /* 2do: sorokat befrissíteni */
            idk = $('#idk').val().split(",");
            idk.forEach(function(id) {
                szamlaSorFrissit('anyagigenyles', id);
            });
            szamlaBetolt(idk[0], 'anyagigenyles.php', 'modosit');
            alert2();

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
            rndmen      = $('#rndmen_'+id).html().replace(',', '.').replace(' ', '');
            marerkmen   = $('#marerkmen_'+id).html().replace(',', '.').replace(' ', '');
            erkmen      = $(this).val().replace(',', '.').replace(' ', '');
            nem_tobb    = $('#nem_tobb_'+id).prop('checked');
            mee         = $('#mee_'+id).html();

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
            if(nem_tobb==0 && (!erkmen || erkmen==0.00) && (!marerkmen || marerkmen==0.00)){
                ures_men_ek_pipa0 += szalloda+' '+cikkid+'<br>';
            }

            /* Pipált sorokra, ha üres marerkmen és erkmen is */
            if(nem_tobb==1  && (!erkmen || erkmen==0.00) && (!marerkmen || marerkmen==0.00)){
                ures_men_ek_pipa1 += szalloda+' '+cikkid+'<br>';
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
            gombok = '<input class="link gomb" value="Igen" onclick="anyagigenyles_erkeztet(\'ures_men_ek_pipa0_kesz nem kérdezzük meg megint\', \'\', \'túlrendelés kérdésen már túl vagyunk\')" type="button"> <input class="link gomb" value="Nem" onclick="alert2()" type="button">';
            alert2(ures_men_ek_pipa0+'<div class="l">Ezekhez az anyagokhoz nem adott meg beérkezett mennyiséget.<br>Ha folytatja, továbbra is beérkezésre váró státuszban maradnak!<br>Folytatja?</div>', gombok);
            return false;
        }
        if(ures_men_ek_pipa1 && !ures_men_ek_pipa1_kesz){
            gombok = '<input class="link gomb" value="Igen" onclick="anyagigenyles_erkeztet(\'ures_men_ek_pipa0_kesz nem kérdezzük meg megint\', \'ures_men_ek_pipa1_kesz nem kérdezzük meg megint\', \'túlrendelés kérdésen már túl vagyunk\')" type="button"> <input class="link gomb" value="Nem" onclick="alert2()" type="button">';
            alert2(ures_men_ek_pipa1+'<div class="l">Ezekhez az anyagokhoz nem adott meg beérkezett mennyiséget.<br>Biztos, hogy már nem fognak beérkezni?<br>Folytatja?</div>', gombok);
            return false;
        }

        alert2();
        $.ajax({
            url: 'anyagigenyles.php',
            type: 'POST',
            data: {
                idk                     : idk,
                anyagigenyles_erkeztet  : new Date().getTime()
            },
            success: function (response) {
                popupZar();
            }
        });

    /* Rendelés szám alapján a táblázat előállítása */
    }else{

        $.ajax({
            url : 'anyagigenyles.php',
            type: 'POST',
            data: {
                rsz                     : $('#rsz').val(),
                anyagigenyles_erkeztet  : new Date().getTime()
            },
            success: function(response) {
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, '', 'Hiba');
                        return false;
                    }else{
                        $('#szallito_sor').show();
                        $('#rsz').attr('disabled', true);
                        $('#pop_szallito').val(response.szallito);
                        $('#pop_szallito').attr('disabled', true);
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

    /* Ha egy kapcsolódó tábláas alodal fülön vagyunk, és nincs kijelölve egy sor sem */
    if( !sorok.hasClass('kijelolve') && reszleg == 'mellek' ) {

        $('#navGombok a.torles').addClass('disabled').attr('onclick', '');
    /* ... ha van kijelölve sor */
    } else if( sorok.hasClass('kijelolve') && reszleg == 'mellek' ) {

        szoveg = 'sorokat';
        torolhet = true;

        mellekre_valt = '';
        if(mellek=='cash_flow'){
            mellek        = 'modosit';
            mellekre_valt = 'cash_flow';
        }

        if(_get('cash_flow') || tabla=='cash_flow'){
            szoveg = 'cash-flow-kat';
            /* Ha nincs minden kezelés joga, akkor nem törölheti */
            if( _jogok[39]=='0' && _jogok[60]=='0' && _jogok[59]=='0' && _jogok[62]=='0' && _jogok[49]=='0' && _jogok[47]=='0' && _jogok[64]=='0' ){
                torolhet = false;
            }
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

        if(torolhet){
            gomb_uzi   = 'Biztosan törli a kijelölt '+szoveg+'?';
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
function mellekTorol( tabla, mellek, mellekre_valt ) {

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

    /* Ha vannak olyan ideiglenes soraink (aminek az Id-ja UJ), azoakt elég csak eltűnteni
      Pl.: Projektigénylésnél a cash-flow */

    $('.kijCont .kijSor.kijelolve[data-id="UJ"]').fadeOut('', function(){
        $(this).remove();
        /* Cash-flow-ban ha változás van, akkor újra kell számolni. Azért kell ide is, mert ajaxig nem biztos, hogy eljutunk. */
        if(tabla=='cash_flow'){
            becsult_szetosztott_ujraszamol();
        }
    });
    alert2();

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
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, '', 'Hiba');
                        return false;
                    }

                    if( response.statusz == 'siker' ) {

                        if( response.alert ) {
                            alert2(response.alert, response.gomb);
                        }else{
                            alert2();
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
        /*Csoportos gombokat sem retjük el soha $('#kmCont').find('input[type="button"]').attr('disabled', true);*/
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
                if(_jogok[39]==1 || _jogok[60]==1 || _jogok[59]==1 || _jogok[62]==1 || _jogok[49]==1 || _jogok[47]==1 || _jogok[64]==1){
                    $('#addCashFlow').attr('onsubmit', 'return addCashFlow(\'' + _get('id') + '\', this);');
                }else{
                    alert2('Önnek nincs joga Cash-flowt feltölteni!', '', 'Hiba');
                    return false;
                }
            break;
            case 'megjegyzes':
                $('#addMegjegyzes').attr('onsubmit', 'return addMegjegyzes(\'' + _get('id') + '\', this);');
            break;
            case 'megterules':
                if(_jogok[39]==1 || _jogok[60]==1 || _jogok[59]==1 || _jogok[62]==1 || _jogok[49]==1 || _jogok[47]==1 || _jogok[64]==1){
                    $('#addMegterules').attr('onsubmit', 'return addMegterules(\'' + _get('id') + '\', this);');
                }else{
                    alert2('Önnek nincs joga Megtérülést feltölteni!', '', 'Hiba');
                }
            break;
            case 'szakterulet':
                $('#addSzakterForm').attr('onsubmit', 'return addSzakterulet(\'' + _get('id') + '\', this);');
            break;
            case 'szallodak':
                $('#addSzallodaForm').attr('onsubmit', 'return addSzalloda(\'' + _get('id') + '\', this);');
            break;
            case 'velemenyezok':
                $('#addVelemenyezokForm').attr('onsubmit', 'return addVelemenyezoFg(\'' + _get('id') + '\', this);');
                $.ajax({
                    url  : 'projektigenyles.php',
                    type : 'POST',
                    data: {
                        recid                   : _get('id'),
                        velemenyezok_status_ell : new Date().getTime()
                    },
                    success: function (response) {
                        if (response) {
                            response = JSON.parse(response);
                            if( response.hiba ) {
                                alert2(response.hiba, '', 'Hiba');
                                popupZar();
                                return false;
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

/* Popup bezárása */
function popupZar() {
    $('#popup').css('display', 'none');
    $('#popupTart').html('');
}

/* Popup megjelenítése és fókusz az első inputra */
function popupNyit() {
    $('#popup').css('display', 'block');
    $('#popup input:text, #popup textarea, #popup select').first().select();
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

function naviElozo() {
    $(document).trigger(jQuery.Event('keyup', {keyCode: 38}));
}

function naviKov() {
    $(document).trigger(jQuery.Event('keyup', {keyCode: 40}));
}

function naviElso() {
    $('#szamlaList li.szamla_sor').eq(0).find('a').trigger('click');
}

function naviUtolso() {
    $('#szamlaList li.szamla_sor:last-child a').trigger('click');
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

        var lista = $.ajax({
            url : 'esemenyek.php',
            type: 'GET',
            data: esamp2es('id=' + recid + '&amp;kijeloltek=' + JSON.stringify(idk) + '&amp;ksz=' + ksz + '&amp;rkat=' + rkat + '&amp;rend=' + rend + '&amp;irany=' + irany + '&amp;tabla=' + tabla + '&amp;szamlaLista=' + new Date().getTime()),
            success: function(html) {
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

                    window.history.pushState("", "", getHost() + param );
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
         		if(html) {
         			html = JSON.parse(html);

                    if( html.statusz == 'hiba' ) {
                        if( html.link ) {
                            location.href = html.link;
                            return false;
                        }

                        $('#beleptet_alert div.header').html('<h1 style="color: #FF5353">Helytelen felhasználó név vagy jelszó!</h1');
                        return false;
                    }

                    location.href = 'index.php';
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

/* Szálloda hozzárendelése felhasználóhoz */
function addSzalloda( loginID, that ) {
    var szallID = $(that).find('input[name="addSzall"]').val();

    if( loginID && szallID ) {
        var ajax = $.ajax({
         	url : 'esemenyek.php',
         	type: 'GET',
         	data: esamp2es('loginID=' + loginID + '&amp;szallID=' + szallID + '&amp;addSzalloda=' + new Date().getTime()),
         	success: function(html) {
         		if(html) {
                    html = JSON.parse(html);

                    if( html.statusz == 'hiba' ) {
                        alert2(html.uzi, '', 'Hiba');
                        return false;
                    }

         			szamlaBetolt(loginID, baseName(location.href) + '.php', 'szallodak').done(function() {
                        $('#naploSor-' + html.kapcsID).addClass('uj');
                    });

                    popupZar();
         		}
         	}
        });

        $.when( ajax ).then(function() {
            $(that).find('input[name="addSzalloda"]').val('');
            $(that).find('input[name="addSzalloda_autocomp"]').val('');
        });
    }

    return false;
}

/* Véleményező hozzáadása projektigényléshez */
function addVelemenyezoFg( pr_igeny_recid, that ) {

    var login_recid = $(that).find('input[name="addVelemenyezo"]').val();

    if( login_recid && pr_igeny_recid ) {
        var ajax = $.ajax({
         	url : 'projektigenyles.php',
         	type: 'GET',
         	data: esamp2es('login_recid=' + login_recid + '&amp;pr_igeny_recid=' + pr_igeny_recid + '&amp;addVelemenyezo=' + new Date().getTime()),
         	success: function(response) {
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

         			szamlaBetolt(pr_igeny_recid, baseName(location.href) + '.php', 'velemenyezok').done(function() {
                        $('#naploSor-' + response.kapcsID).addClass('uj');
                    });

                    popupZar();
         		}
         	}
        });

        $.when( ajax ).then(function() {
            $(that).find('input[name="addVelemenyezo"]').val('');
            $(that).find('input[name="addVelemenyezo_autocomp"]').val('');
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
            url: 'projektigenyles.php',
            type: 'POST',
            data: {
                recid         : _get('id'),
                van_e_velemeny: new Date().getTime()
            },
            success: function (response) {
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

    if( _get('uj') ) {
        $('div.navGombok a.uj').addClass('disabled');
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

/* Adatlap betöltése */
function szamlaBetolt( recid, page, muvelet, szerkeszt, kerdes_nelkul ) {

    alert2('');
    if( formModositva && !kerdes_nelkul ) {
        alert2('Biztosan elhagyja ezt a bejegyzést mentés nélkül?', '<input class="gomb zold" value="Igen" onclick="formModositva = false; szamlaBetolt( \'' + recid + '\', \'' + page + '\', \'' + muvelet + '\', \'' + szerkeszt + '\' ); $(\'#szamlaList li.szamla_sor\').removeClass(\'kijelolve\');" type="button"><input class="gomb piros" value="Nem" onclick="alert2()" type="button">');
        return false;
    }

    if( szerkeszt === undefined ) {
        szerkeszt = false;
    }

    szerk = '';
    if( szerkeszt ) {
        szerk = 'szerkeszt=1&amp;';
    }

    naptar_latszik_nincs_leptetes = false;
    var dfrd1 = $.Deferred();

    /* Naptár megjelenítése */
    if(page=='utemezett_feladatok.php'){
        if(muvelet.indexOf('naptar')>=0){
            $('#naptar').show();
            $('#szamlaList').hide();
            $('#utemezes').hide();
            naptar_latszik_nincs_leptetes = true;
        }else if(muvelet.indexOf('utemezes')>=0){
            $('#utemezes').show();
            $('#szamlaList').hide();
            $('#naptar').hide();
        }else{
            $('#naptar').hide();
            $('#utemezes').hide();
            $('#szamlaList').show();
        }
    }

    if( recid && page ) {

        anyagrend = '';
        if(_get('anyagrend')){
            anyagrend = 'anyagrend&amp;';
        }

        var szamla = $.ajax({
         	url : page,
         	type: 'GET',
         	data: esamp2es('id=' + recid + '&amp;ajax=1&amp;' + szerk + anyagrend + muvelet + '=' + new Date().getTime()),
         	success: function(html) {
         		if(html) {

                    html = JSON.parse(html);

         			$('#szamlaBetolt').attr('data-recid', recid).html(html.tartalom).promise().done(function() {
                        dfrd1.resolve();
                    });

                    param = get_get([
                        'akt_nap',
                        'anyagigenyles',
                        'bejelentesek',
                        'beosztasok',
                        'cash_flow',
                        'dokumentumok',
                        'elszamolas',
                        'engedelyek',
                        'esemenyek',
                        'felmeresi',
                        'garancialis',
                        'id',
                        'jogosultsag',
                        'megjegyzes',
                        'megjegyzesek',
                        'megterules',
                        'modosit',
                        'munkalapok',
                        'naplo',
                        'naptar',
                        'uj',
                        'utemezes',
                        'projekt_team',
                        'projektigeny',
                        'rb_anyag',
                        'siker',
                        'statusz',
                        'szamlak',
                        'szabadsag',
                        'szallodak',
                        'szerkeszt',
                        'szakterulet',
                        'terkep',
                        'tortenet',
                        'tetel',
                        'ujkent',
                        'velemenyezok'
                    ], true);
                    if( muvelet != 'uj' ) {

                        if( param.indexOf('?') !== -1 ) {
                            param += '&id=' + recid + '&' + esamp2es(muvelet);
                        } else {
                            param = '?id=' + recid + '&' + esamp2es(muvelet);
                        }

                        if( szerkeszt ) {
                            param += '&szerkeszt';
                        }
                    } else {
                        param += '?uj';
                    }


                    /* Bal oldali navpanel sorát is frissítjük */
                    if(page.replace('.php', '')!='erteklista'){
                        szamlaSorFrissit(page.replace('.php', ''), recid);
                    }

                    /* URL mod */
                    window.history.pushState("", "", getHost() + param );

                    /* Új ikonra kattintva mi történik */
                    if( _get('szakterulet') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'szakterulet\'); void(0);');

                    } else if( baseName(location.href) == 'anyagigenyles' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'anyagigenyles' && !_get('megjegyzesek') && _jogok[13]==0 ) {
                        $('#navGombok a.uj').attr('href', 'javascript: alert2(\'Önnen nincs jogosultsága új anyagigénylés feltöltéséhez!\', \'\', \'Hiba\'); void(0);');

                    } else if( baseName(location.href) == 'anyagigenyles' && _get('anyagrend') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: alert2(\'Új anyagigénylés csak az anyagigénylés menüpont alatt tölthető fel!\', \'\', \'Hiba\'); void(0);');

                    } else if( baseName(location.href) == 'beosztas' && _get('naptar') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: $(\'#szerk_form div.dt\').show(); void(0);');

                    } else if( baseName(location.href) == 'beosztas' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'bejelentes' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'bejelentes' && _jogok[56] == 1 ) {
                        $('#navGombok a.uj').attr('href', 'javascript: bejSzallodaValaszt(); void(0);');

                    } else if( baseName(location.href) == 'cikktorzs' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

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

                    } else if( baseName(location.href) == 'login' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'munkalap' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'rendszeres_karbantartas' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'raktar' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'projektigenyles' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'projektkezeles' && _get('megjegyzesek') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');

                    } else if( baseName(location.href) == 'projektigenyles' && _get('megterules') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megterules\'); void(0);');

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

                    if( !_get('megjegyzesek') && baseName(location.href) == 'bejelentes' && $('#diszpecser').length){
                        /*$('#navGombok a.uj').attr('href', 'javascript: alert2(\'Diszpécserként nem hozhat létre új bejelentést!\'); void(0);');*/
                    }

                    /* Ha épp egy csoporton állunk, akkor elrejtük a jobb oldali tartalmakat */
                    $('#szamlaList li.csoport').each(function () {
                        if($(this).hasClass('kijelolve')){
                            csoport_tartalom();
                        }
                    });

                    $('#szamlaHidden').html('').addClass('dn').removeClass('csoport');

         		} else {
                    alert2('Nem lehet betölteni a kért oldalt!');
                    dfrd1.resolve();
                }
         	}
        });

        return $.when(szamla, dfrd1).done(function() {
            formModositva = false;
            gombokRejt();
        }).promise();
    }

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
         	data: esamp2es('tabla=' + tabla + '&amp;recid=' + recid + param_kieg + '&amp;szamlaSorFrissit=' + new Date().getTime()),
         	success: function(response) {
         		if(response) {
                    /* A frissítendő sor data-i értéke maradjon változatlan. Amit kapunk abban lesz az értéke */
                    response = response.replace('data-i="1"', 'data-i="' + $('#szamla-' + recid).attr('data-i') + '"');
         			$('#szamla-' + recid).replaceWith(response);
         		}
         	}
        });
    }
}

/* Könyvtárszerkezetek esetén a komplett bal oldalt újra kell számolni */
function bal_panel_frissit( php, recid ) {
    $.ajax({
        url : php+'?id='+recid,
        type: 'POST',
        data: {
            bal_panel_frissit: new Date().getTime()
        },
        success: function (response) {
            if (response) {
                response = JSON.parse(response);
                $('#tartalomBal').html(response.html);
            }
        }
    });
}

function ertekFoglalt(originVal, value, tabla, mezo) {
    if( originVal != value && value && tabla && mezo ) {
        $.ajax({
         	url : 'esemenyek.php',
         	type: 'GET',
         	data: esamp2es('value=' + value + '&amp;tabla=' + tabla + '&amp;mezo=' + mezo + '&amp;ertekFoglalt=' + new Date().getTime()),
         	success: function(response) {
         		if(response) {
         			response = JSON.parse(response);

                    if( response.uzi ) {
                        alert2(response.uzi);
                    }
         		}
         	}
         });
    }
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
    var telephely_id = $('#telephely_id').val();

    if( !telephely_id ) {
        if( $(that).attr('name') == 'obj_id_autocomp' ) {
            $('#obj_id_autocomp').attr('disabled', true);

            alert2('Kérem előbb válasszon szállodát!');
            return false;
        }
    } else {
        if( $(that).attr('name') == 'telephely_id' ) {
            $('#obj_id_autocomp').attr('disabled', false).val('').removeClass('mod');
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

/* Annak ellenőrzése, hogy a helyiség ki van-e már választva */
function helyisegVane( that ) {
    var telephely_id = $('#telephely_id').val();
    var helyiseg = $('#obj_id_autocomp').val();

    if( !helyiseg || !telephely_id ) {
        if( $(that).attr('name') == 'gep_id_autocomp' ) {
            $(that).attr('disabled', true);
        }

        if( !telephely_id ) {
            alert2('Kérem előbb válasszon szállodát!');
            return false;
        }

        alert2('Kérem előbb válasszon helyiséget!');
        return false;
    } else {
        $('#gep_id_autocomp').attr('disabled', false);
    }
}

/* Annak ellenőrzése, hogy a gép ki van-e már választva */
function gepVane() {

    var telephely_id = $('#telephely_id').val(),
        obj_id = $('#obj_id').val();

    $.ajax({
        url : 'esemenyek.php',
        type: 'GET',
        data: esamp2es('telephely_id=' + telephely_id + '&amp;obj_id=' + obj_id + '&amp;gepVane=' + new Date().getTime()),
        success: function(html) {
            if( html ) {
                html = JSON.parse(html);

                if( html.statusz ) {
                    $('#gep_id_autocomp').attr('disabled', false).val('').removeClass('mod');
                    $('#gep_id').val('');
                } else {
                    $('#gep_id_autocomp').attr('disabled', true).val('').removeClass('mod');
                    $('#gep_id').val('');
                }
            }
        }
    });
}

/* Inline naptárhoz */
$(document).ready(function () {
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

    /* Bal oldali nav panelben a kijelölt sorhoz scrollozunk */
    scrollLeptet();
});

/* Időszakos ütemezésnél az egyeszeri időszak kimullázása */
function egyszeri_nullaz(){
    $('#aktkezd_d').val('0000.00.00');
    $('#aktvege_d').val('0000.00.00');
}
/* Időszakos ütemezésnél a szokásos időszak kimullázása */
function szokasos_nullaz(){
    $('#kezdho').val('');
    $('#kezdnap').val('');
    $('#vegeho').val('');
    $('#vegenap').val('');
}

/* Foglalt ID esetén az mezőre teszünk egy foglalt_id class-t, a JS ellenézés innen fogja tudni, hogy az űrlap nem postolható el*/
function id_foglalt_e(fajl, mezo){
    $.ajax({
        url : fajl,
        type: 'GET',
        data: esamp2es('id=' + mezo.val() + '&amp;id_foglalt_e=' + new Date().getTime()),
        success: function(response) {
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

    $.ajax({
        url: 'header.php',
        type: 'POST',
        data: esamp2es('tabla=' + tabla + '&amp;mezo=' + $('#'+prefix+'szuro_mezo').val() + '&amp;selected=' + selected.val() + '&amp;felt=' + felt + '&amp;f_szuro_mezo_operator=' + new Date().getTime()),
        success: function (response) {
            response = JSON.parse(response);
            if (response) {
                $('#'+prefix+'feltetel').html(response.feltetel);
                if(prefix){
                    $('#'+prefix+'szures_submit').html( '<input type="button" class="gomb zold f_o_szuro_hozzaad" value="+" style="padding: 0.3rem 0.7rem">');
                }else{
                    $('#'+prefix+'szures_submit').html( '<input type="button" class="gomb f_szures_submit" value="Szűrés">'+
                                                        '<input style="margin-left: 0.2rem" type="button" class="gomb piros" value="Szűrés töröl" onclick="location.href=\''+tabla+'.php\'">'
                                                    );
                }

            }
        }
   });
}

/* Személyzet -> Beosztás -> Naptár fül alatt a felvesz gomb lekezelése */
function beosztas_naptarba_teszt(beosztas_id, felulir){
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
            beosztas_naptarba_teszt : new Date().getTime()
        },
        success: function (response) {
            response = JSON.parse(response);
            if(response.siker){
                $('.beosztas_naptar').replaceWith(response.naptar);
                $('#mehet_gomb').removeClass('gombb_alert');
                $('#tol').val('    .  .     :  ').removeClass('mod');
                $('#ig').val('    .  .     :  ').removeClass('mod');
                $('#beosztas').val('').removeClass('mod');
                $('#szerepkor').val('').removeClass('mod');
                $('#felh_loginnev').val('').removeClass('mod');
                $('#felh_loginnev_autocomp').val('').removeClass('mod');
                $('.beosztas_rejt').addClass('dn');
            }
            if(response.hiba){
                if(response.gomb){
                    alert2(response.hiba, response.gomb);
                }else{
                    alert2(response.hiba, '', 'Hiba');
                }
            }
        }
    });


}

function naptar_torol_kerdez(){
    gomb = '<input class="gomb piros" type="button" value="Igen" onclick="naptar_torol()"> <input class="gomb zold" type="button" value="Nem" onclick="alert2()">';
    alert2('Biztosan törli ezt a beosztást?', gomb);
}

function naptar_torol(){
    $.ajax({
        url: 'beosztas.php',
        type: 'POST',
        data: {
            id: $('#naptar_torol_id').val(),
            naptar_torol: new Date().getTime()
        },
        success: function (response) {
            response = JSON.parse(response);
            if(response.siker){
                $('.beosztas_naptar').replaceWith(response.naptar);
                $('#mehet_gomb_kivesz').removeClass('gombb_alert');
                $('#naptar_torol_id').val('');
                alert2();
            }
            if(response.hiba){
                alert2(response.hiba, '', 'Hiba');
            }
        }
    });
}

/* Ha egy csoport ki van jelölve, akkor a jobb oldali tartalmat beállítjuk */
function csoport_tartalom() {
    $('#szerk_form input:not(\'.foGomb\'), #szerk_form select, #szerk_form textarea').val('').prop('checked', false);
    $('div.statusz div.text, div.naplo, div.jsff, #sorrendezes_tobbkep, #szerk_form div.dt').hide();

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
                $('#szamlaHidden').addClass('csoport').html(response);
            }
        });
    }
}

/* Duplikálás gombra kattintva feljövő kérdés */
function bejegyzesDuplikal() {
    if( _get('uj') ){
        alert2('Új feltöltés alatt nem értelmezett a duplikálás, előbb válasszon ki egy '+$('#rekord_neve_targy').html()+'!', '', 'Hiba');
    }else{
        szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit').done(function() {
            alert2(duplikaKerdes, duplikalGomb);
        });
    }
}

/* A bal oldali navpanel kijelölésének ellenőrzése */
function balnav_kijelol_ell(egy_csoport_reszben_szabad, kivetel_ell){
    console.log($('li.szamla_sor.kijelolve:not(\'.behuz\')').length);
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
        alert2('Kérjük előbb mentse el az űrlapot!');
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

                response = JSON.parse(response);
                if(response.statusz=='siker'){
                    szamlaSorFrissit('projektigenyles', recid);
                    if(_get('modosit')){
                        szamlaBetolt(recid, 'projektigenyles.php', 'modosit');
                    }
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
function osszetett_szuro_mondat(){

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
}

/* Projektigénylés alatt az adatlap és cash flow fül közötti váltás */
function prigeny_ful_valt(ful){
    
    var vannak_ideiglenes_sorok = false;
    $('#cash_flow_div div.kijSor').each(function() {
        if( $(this).attr('data-id')=='UJ' ){
            vannak_ideiglenes_sorok = true;
        }
    });

    if(ful=='cash_flow'){
        $('#cash_flow_div').show();
        $('#szerk_form').hide();
        $('#ful_cash_flow').addClass('active');
        $('#ful_modosit').removeClass('active');
        $('#navGombok .uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'cash_flow\'); void(0);');
        if(vannak_ideiglenes_sorok){
            $('#tartalomJobb').prepend('<div id="cash_flow_mentese" class="muveletek"><div class="form_kuldes"> <input class="gombb mentes_gomb gombb_alert" name="mehet_gomb" id="mehet_gomb2" value="Mentés" onclick="$(\'#szerk_form\').submit()" tabindex="23" type="button"> </div></div>');
        }
    }

    if(ful=='adatlap'){
        $('#cash_flow_div').hide();
        $('#szerk_form').show();
        $('#ful_cash_flow').removeClass('active');
        $('#ful_modosit').addClass('active');
        $('#navGombok .uj').attr('href', 'projektigenyles.php?uj');
        if(vannak_ideiglenes_sorok){
            $('#mehet_gomb').removeAttr('disabled').addClass('gombb_alert');
        }
    }

    /* Ha nem az adatlapon vagyunk akkor szamlaBetolt */
    if(!_get('modosit')){

        betoltott = szamlaBetolt(_get('id'), 'projektigenyles.php', 'modosit');

        $.when( betoltott ).done(function() {
            if(ful=='cash_flow'){
                cash_flow_fulre_lep();
            }
        });


    /* Egyébként csak JS tartalom csere */
    }else{
        if(ful=='adatlap'){
            $('#cash_flow_mentese').remove();
            $('#cash_flow_div').hide();
            $('#szerk_form').show();
            $('#szamlaFulek a').removeClass('active');
            $('#ful_modosit').addClass('active');
            $('#navGombok .uj').attr('href', 'projektigenyles.php?uj');
        }
        if(ful=='cash_flow'){
            cash_flow_fulre_lep();
        }
    }

}
function cash_flow_fulre_lep(){
    $('#cash_flow_div').show();
    $('#szerk_form').hide();
    $('#szamlaFulek a').removeClass('active');
    $('#ful_cash_flow').addClass('active');
    $('#navGombok .uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'cash_flow\'); void(0);');
}

function getKonyvtar( link ) {
    if( link ) {
        $.ajax({
         	url : link,
         	type: 'GET',
         	data: esamp2es('ajax=' + new Date().getTime()),
         	success: function(response) {
         		if(response) {
         			$('#szamlaBetolt').html(response);

                    window.history.pushState("", "", link );
         		}
         	}
        });
    }
}
