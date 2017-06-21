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
*	Insert Text - textarea tartalmába írás (tipikusan BB kódhoz).
*
*	@param string txtId: textarea id-je
*	@param string oTag: nyitó tag
*	@param string cTag: záró tag
*	@param string defText: alapértelmezett szöveg
*/
function insert_text (txtId, oTag, cTag, defText){
	var txt = document.getElementById(txtId);
	txt.focus();

	if (txt.selectionStart != undefined){

		var sel_start = txt.selectionStart;
		var sel_end   = txt.selectionEnd;

		if (!cTag){
			txt.value = txt.value.substr(0, sel_start) + oTag + txt.value.substr(sel_end);
			txt.selectionStart = sel_start + oTag.length;
			txt.selectionEnd   = sel_start + oTag.length;
		}else{
			var selText = txt.value.substr(sel_start, sel_end - sel_start);
			if (!selText && defText) selText = defText;

			txt.value = txt.value.substr(0, sel_start) + oTag + selText + cTag + txt.value.substr(sel_end);
			txt.selectionStart = sel_start + oTag.length;
			txt.selectionEnd   = sel_start + oTag.length + selText.length;
		}
	}else if (document.selection){

		var sel = document.selection.createRange();

		if (sel) {
			if (!cTag) sel.text = oTag;
			else{
				var selText = sel.text;
				if (!selText && defText) selText = defText;
				var selLength = selText.replace(/\r\n/g, "\n").length;

				sel.text = oTag + selText + cTag;
				sel.moveStart ("character", -cTag.length - selLength);
				sel.moveEnd   ("character", -cTag.length);
			}
			sel.select();
		}
	}
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
    return '<p class="c" style="margin-top: 0.5rem"><input class="gomb piros" type="button" value="Igen" onclick="torol(\'' + self + '&amp;' + torles + '=';
}

/* Felsorolásnál törlésgomhoz HTML. Hogy valid legyen kiveszem JS-be. */
function get_torles_gomb_v(){
    return ')"> <input class="gomb zold" type="button" value="Nem" onclick="alert2()">';
}

/* Minden töröl gomb HTML-je. Hogy valid legyen kiveszem JS-be. */
function get_gomb_mindent(){
    return '<p class="c" style="margin-top: 0.5rem"><input class="gomb piros" type="button" value="Igen" onclick="document.getElementById(\'kij_torl\').submit()">  <input class="gomb zold" type="button" value="Nem" onclick="alert2()">';
}

/* Kibontásnál a töröl gomb HTML-je. Hogy valid legyen kiveszem JS-be. */
function get_kibont_torol(self, torles, get_id){
    return '<p class="c" style="margin-top: 0.5rem"><input class="gomb piros" type="button" value="Igen" onclick="location.href=\'' + self + '?lista&amp;' + torles + '=' + get_id + '\'">  <input class="gomb zold" type="button" value="Nem" onclick="alert2()">';
}

/* Kibontásnál címlap kép törlés HTML-je. Hogy valid legyen kiveszem JS-be. */
function get_fokep_torol(self, get_id, keptorol){
    return '<p class="c" style="margin-top: 0.5rem"><input class="gomb piros" type="button" value="Igen" onclick="$.ajax({ url : \'' + self + '\', type: \'GET\', data: \'id=' + get_id + '&amp;modosit&amp;keptorol=' + keptorol + '&amp;becsapas=' + new Date().getTime() + '\', success: function(html){ $(\'#fokep\').fadeOut(\'fast\'); alert2(); } });"> <input class="gomb zold" type="button" value="Nem" onclick="alert2()">';
}

/* Kibontásnál egy flashfájllal felvitt fájl törlése */
function get_fajl_torol_gomb(id, tabla, oszlop){
    return '<p class="c" style="margin-top: 0.5rem"><input class="gomb piros" type="button" value="Igen" onclick="$.ajax({type: \'POST\',url : \'esemenyek.php\',data: \'fajl_torol=1&tabla=' + tabla + '&oszlop=' + oszlop + '&id=' + id + '\', success: function(html){}}); alert2(); $(\'#fajl_' + tabla + '_' + oszlop + '_' + id + '\').fadeOut();"> <input class="gomb zold" type="button" value="Nem" onclick="alert2()">';
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


/**
* @desc Kép elforgatása megadott százalékkal
*
* @param utvonal        string A kép elérési útvonala
* @param szazalek       int    Az elforgatás mértéke %-ban
* @param jquery_String string  Jqery-s string, amivel a megfelelő képelemet elérem
*
* @return string Az elforgatott kép útvonala
*/
function kep_forgat(utvonal, szazalek, kep_object){
    alert2('Forgatás...');
    $.ajax({
        url : 'esemenyek.php',
        type: 'GET',
        data: esamp2es('utvonal='+utvonal+'&amp;szazalek='+szazalek+'&amp;kep_forgat=' + new Date().getTime()),
        success: function(html) {
            alert2();
            if($(kep_object).attr('src')){
                $(kep_object).attr('src', html);
            }else{
                $(kep_object).find('img').attr('src', html);
            }
        }
    });
}


/**
* @desc Fordítás felülethez kell, ez menti el az inputok értékeit a szotar.csv-be
*/
function szotar_csv_ment(){

    alert2('Mentés...');

    var ertekek = '';
    $('input.szotarba').each(function () {
        ertekek += $(this).attr('name') + '===' + $(this).val() + '|||';
    });

    $.ajax({
        url: 'forditas.php',
        type: 'POST',
        data: esamp2es('ertekek='+ertekek+'&amp;szotar_csv_ment=' + new Date().getTime()),
        success: function (html) {
            alert2();
        }
    });

}

function felh_valt( valt ) {
    if( valt === undefined ) {
        valt = '';
    }

    if( valt ) {
        $('div.loginAlert').removeClass('dn');
        document.getElementById('login_nev').focus();
    } else {
        $('div.loginAlert').addClass('dn');
    }
}

$(function() {
    setTimeout(function() {
        if( !$('#szamlaList li.szamla_sor').hasClass('active') && !_get('uj') ) {
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
                $('#tartalomJobb').css('width', 'calc(100% - ' + Math.abs(nbWidth) + 'px)');
                $('.muveletek').css('width', 'calc(100% - ' + (Math.abs(nbWidth) + 6) + 'px)');

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

    $(document).on('keyup', function(e) {
        if( e.keyCode == 38 || e.keyCode == 40 ) {
            var className = e.target.className;

            popup_display = $('#popup').css('display');

            if( (className !== undefined && className.indexOf('disableNav') !== -1) || popup_display == 'block' || $('#alert2').length || !$('#beleptet_alert').hasClass('dn') || $('#naptar').length ) {
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

                var active = $('#szamlaList li.szamla_sor.active').index('#szamlaList li.szamla_sor'),
                    szamlaDb = $('#szamlaList li.szamla_sor').length - 1;

                $('#szamlaList li.szamla_sor').removeClass('active');

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

                $('#szamlaList li.szamla_sor').eq(kovSzamlaSor).addClass('active');

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

                szamlaBetolt( szamlaID, tabla + '.php', 'modosit' );

                scrollLeptet();
                e.preventDefault();
            }
        }
    });

    $(document).on('click', function(e) {
        $('.kijeloltek_muv').removeClass('active');

        /*var par = $(event.target).parent();

        if( !e.ctrlKey && !e.shiftKey && e.target.className.indexOf('km_false') === -1 && !par.hasClass('km_false') ) {
            kijelolesEltavolit();
        }*/
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

    $(document).on('click', '.kijCont .kijSor', function(e) {
        var parent = $(this).parents('.kijCont'),
            tabla  = parent.attr('data-tabla'),
            mellek = parent.attr('data-mellek'),
            sorok  = parent.find('.kijSor');

        if( e.shiftKey ) {
            sorok.removeClass('active');

            if( !sorok.hasClass('kijelolve') ) {
                $(this).addClass('kijelolve');

                /* Tömeges kijelöléshez */
                sorok.removeClass('akt');
                $(this).addClass('akt');

                kijeloltSzamlal( sorok, 'mellek', tabla, mellek );
            } else {
                var akt_id = parseInt( parent.find('.kijSor.akt').attr('data-i') );

                /* Tömeges kijelöléshez */
                sorok.removeClass('akt');
                $(this).addClass('akt');

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
        } else if( e.ctrlKey ) {
            sorok.removeClass('active');

            /* Tömeges kijelöléshez */
            sorok.removeClass('akt');
            $(this).addClass('akt');

            if( $(this).hasClass('kijelolve') ) {
                $(this).removeClass('kijelolve');
            } else {
                $(this).addClass('kijelolve');
            }

            kijeloltSzamlal( sorok, 'mellek', tabla, mellek );
        }
    });

    $(document).on('click', 'ul.szamla_cont li.csoport span.csoportNev', function(e) {
        var text = $(this).find('strong').text(),
            id   = $(this).attr('data-id');

        alert2('Adja meg a csoport nevét: <br><input type=\"text\" id=\"csoport_nev_szerk\" name=\"csoport_nev_szerk\" value=\"' + text + '\" style=\"margin-top: 1rem; width: 28rem;\">', "<p class=\"c\"><input type=\"submit\" name=\"elkuld\" class=\"gomb zold\" value=\"Mentés\" onclick=\"csoportAtnevezes( " + id + " );\"><input type=\"button\" name=\"megsem\" class=\"gomb piros\" value=\"Mégsem\" onclick=\"alert2();\"></p>");

        e.preventDefault();
        e.stopPropagation();
    });

    $(document).on('click', 'ul.szamla_cont li.csoport', function(e) {
        var sorok = $('#szamlaList li.szamla_sor'),
            id = $(this).attr('data-id'),
            kijelolheto = $('#szamlaList').hasClass('kijelolheto');

        if( e.ctrlKey && kijelolheto ) {
            sorok.removeClass('active');

            if( !$('ul.szamla_cont li[data-csoportID="' + id + '"]').hasClass('kijelolve') ) {
                /*$(this).addClass('active');
                $('ul.szamla_cont li[data-csoportID="' + id + '"]').removeClass('dn');*/
                $(this).addClass('kijelolve');

                $('ul.szamla_cont li[data-csoportID="' + id + '"]').addClass('kijelolve');
            } else {
                $(this).removeClass('kijelolve');
                $('ul.szamla_cont li[data-csoportID="' + id + '"]').removeClass('kijelolve');
            }
        } else {
            $(this).toggleClass('active');
            $('ul.szamla_cont li[data-csoportID="' + id + '"]').toggleClass('dn');
        }

        kijeloltSzamlal( sorok, 'fo' );
    });

    $('#tartalomBal').on('click', '#szamlaList li.szamla_sor', function(e) {
        var sorok = $('#szamlaList li.szamla_sor'),
            kijelolheto = $('#szamlaList').hasClass('kijelolheto');

        if( !e.ctrlKey && !e.shiftKey || !kijelolheto ) {
            sorok.removeClass('active kijelolve');
            $(this).addClass('active');

            /* Navigálás */
            var recid   = $(this).find('a.szamla').attr('data-recid'),
                fajl    = $(this).find('a.szamla').attr('data-fajl'),
                muvelet = $(this).find('a.szamla').attr('data-muvelet');

            if( recid && fajl && muvelet ) {
                szamlaBetolt(recid, fajl, muvelet);
            }

            scrollLeptet();
            e.preventDefault();
            e.stopPropagation();
        } else {
            if( e.shiftKey && kijelolheto ) {
                sorok.removeClass('active');

                if( !sorok.hasClass('kijelolve') ) {
                    $(this).addClass('kijelolve');

                    /* Tömeges kijelöléshez */
                    sorok.removeClass('akt');
                    $(this).addClass('akt');

                    kijeloltSzamlal( sorok, 'fo' );
                } else {
                    var akt_id = parseInt( $('#szamlaList.kijelolheto li.szamla_sor.akt').attr('data-i') );

                    /* Tömeges kijelöléshez */
                    sorok.removeClass('akt');
                    $(this).addClass('akt');

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

                    kijeloltSzamlal( sorok, 'fo' );
                }
            } else if( e.ctrlKey && kijelolheto ) {
                sorok.removeClass('active');

                /* Tömeges kijelöléshez */
                sorok.removeClass('akt');
                $(this).addClass('akt');

                if( $(this).hasClass('kijelolve') ) {
                    $(this).removeClass('kijelolve');
                } else {
                    $(this).addClass('kijelolve');
                }

                kijeloltSzamlal( sorok, 'fo' );
            }
        }
    });

    formModositva = false;
    $('#tartalomJobb').on('keyup change paste', '#szerk_form input, #szerk_form select, #szerk_form textarea', function(e) {
        var name = $(this).attr('name'),
            type = $(this).attr('type'),
            val  = $(this).val();

        if( name != 'mehet_gomb' ) {
            var taroltVal = htmlspecialchars_decode(formAdatok[name]);

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
                    taroltVal = '____.__.__';
                }
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

    if( $('.szamla_sor').hasClass('active') ) {
        active_top = $('.szamla_sor.active').position().top;
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

    $(document).on('keydown', function(e) {
        if( e.keyCode == 13 && $('#alert2').length && !$('#alert2').hasClass('rFalse') ) {
            $('#alert2 input[value="Ok"]').click();
            /*alert2();
            e.preventDefault();*/
        }
    });

    $(document).ajaxComplete(function (event, xhr, settings) {
        $('form.popupForm input.focus').focus();
    });

    $(document).on('click', 'ul.telephelyek a.nyit', function() {
        $(this).parent('li.alkatos').find('ul.alkatok').eq(0).toggleClass('active');
        $(this).find('span.icon').toggleClass('active');

        return false;
    });

    /* Ciklus tipus válétaásakor megfelelő div megjelenítése */
    $(document).on('change', '.f_cikltip', function() {
        $('.cikltip').hide();
        if($('#tipus_'+$(this).val()).length){
            $('#tipus_'+$(this).val()).show();
        }

        /* VÁltáskor mindent nullázunk */
        $('#ciklnap').val('');
        $('#hetkoz_kent').prop('checked', false);

        $('#ciklhet').val('');
        $('#ism_het').val('');
        $('#h') .prop('checked', false);
        $('#k') .prop('checked', false);
        $('#s') .prop('checked', false);
        $('#cs').prop('checked', false);
        $('#p') .prop('checked', false);
        $('#sz').prop('checked', false);
        $('#v') .prop('checked', false);

        $('#ciklho').val('');
        $('#honapjan') .prop('checked', false);
        $('#ciklho2').val('');
        $('#ho_rendsz') .prop('checked', false);
        $('#ho_rendsz_het') .prop('checked', false);

        /*switch($(this).val()){
            case 'l':
                    datumra_utemez_mezok_nullaz('d');
                    datumra_utemez_mezok_nullaz('w');
                    datumra_utemez_mezok_nullaz('m');
            break;
            case 'd':
                    datumra_utemez_mezok_nullaz('w');
                    datumra_utemez_mezok_nullaz('m');
            break;
            case 'w':
                    datumra_utemez_mezok_nullaz('d');
                    datumra_utemez_mezok_nullaz('m');
            break;
            case 'm':
                    datumra_utemez_mezok_nullaz('d');
                    datumra_utemez_mezok_nullaz('w');
            break;
        }*/
    });

    $(document).on('keyup', function(e) {
        if( e.keyCode == 27 ) {
            alert2();
            felh_valt();
            kijelolesEltavolit();
        }
    });
});

function csoportAtnevezes( csoportID ) {
    var val = $('#csoport_nev_szerk').val();

    if( csoportID && val ) {
        $.ajax({
         	url : 'esemenyek.php',
         	type: 'GET',
         	data: esamp2es('csoportID=' + csoportID + '&amp;val=' + val + '&amp;csoportAtnevezes=' + new Date().getTime()),
         	success: function(response) {
         		if(response) {
         			response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba);
                        return false;
                    }

                    if( response.html ) {
                        $('#insertCsopHidden').html(response.html);
                    }

                    $('li.csoport[data-id="' + csoportID + '"] span.csoportNev strong').text(response.value);
         		}
         	}
        });

        alert2();
    }
}

function munkalapKeszit( recid ) {
    idk = {};

    if( recid === undefined ) {
        recid = false;
    } else {
        idk[1] = recid;
    }

    if( recid === false ) {
        szamlaSorok = $('#szamlaList li.szamla_sor.kijelolve');

        if( szamlaSorok.length ) {
            var i = 1;
            szamlaSorok.each(function() {
                var id = $(this).attr('data-recid');

                idk[i] = id;
                i++;
            });
        }
    }

    if( objLength(idk) ) {
        $.ajax({
         	url : 'esemenyek.php',
         	type: 'POST',
         	data: {
                idk: JSON.stringify(idk),
                munkalapKeszit: new Date().getTime()
            },
         	success: function(response) {
         		if(response) {
                    response = JSON.parse(response);

         			if( response.hiba ) {
                        alert2(response.hiba);
                        return false;
                    }

                    location.href = 'munkalap.php?id=' + response.recid + '&modosit';

                    kijelolesEltavolit();
                    popupZar();
         		}
         	}
        });
    }

    return false;
}

function bejegyzesElutasitas( that ) {
    var indok = $(that).find('input[name="indok"]');

    if( indok.val() ) {
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
                        indok   : indok.val(),
                        idk     : JSON.stringify(idk),
                        bejegyzesElutasitas : new Date().getTime()
                    },
                 	success: function(response) {
                 		if(response) {
                            response = JSON.parse(response);

                            if( response.hiba ) {
                                alert2(response.hiba);
                                return false;
                            }

                            if( response.kieg ) {
                                alert2(response.kieg);
                            }

                            kijelolesEltavolit();
                            bejegyzesekLista('#rendezesSelect', 'asc', '', '');
                            popupZar();
                 		}
                 	}
                });
            }
        } else {
            alert2('Nincsenek kijelölt sorok!');
        }
    } else {
        alert2('Kérem adja meg az elutasítás okát!');
    }

    return false;
}

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
                $.ajax({
                 	url : 'esemenyek.php',
                 	type: 'POST',
                 	data: {
                        oszlop  : oszlop,
                        idk     : JSON.stringify(idk),
                        csoportbolTorol : new Date().getTime()
                    },
                 	success: function(response) {
                 		if(response) {
                            response = JSON.parse(response);

                            if( response.hiba ) {
                                alert2(response.hiba);
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
            alert2('Nincsenek kijelölt sorok!');
        }
    }

}

function addCsoport() {
    var ujcsoport = $('input[name="ujcsoportnev"]');

    if( ujcsoport.val() ) {
        $('#ajaxfedo').removeClass('dn');

        var addcsoport = $.ajax({
         	url : 'esemenyek.php',
         	type: 'GET',
         	data: esamp2es('csoportnev=' + ujcsoport.val() + '&amp;addCsoport=' + new Date().getTime()),
         	success: function(response) {
         		if(response) {
         			response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba);
                        return false;
                    }

                    if( response.statusz == 'siker' ) {
                        ujcsoport.val('');
                        $('#insertCsop').html(response.html);
                        $('#insertCsopHidden').html(response.html);
                    }
         		}
         	}
        });

        $.when( addcsoport ).then(function() {
            $('#ajaxfedo').addClass('dn');
        });
    } else {
        alert2('Kérem adja meg a csoport nevét!');
    }
}

function csoportbaRendez( that ) {
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
                $.ajax({
                 	url : 'esemenyek.php',
                 	type: 'POST',
                 	data: {
                        csoportID       : csoportID.val(),
                        idk             : JSON.stringify(idk),
                        csoportbaRendez : new Date().getTime()
                    },
                 	success: function(response) {
                 		if(response) {
                            response = JSON.parse(response);

                            if( response.hiba ) {
                                alert2(response.hiba);
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
            alert2('Nincsenek kijelölt sorok!');
        }
    } else {
        alert2('Kérem válasszon egy csoport');
    }

    return false;
}

function csoportMuvelet( oldal ) {
    $('#kmCont div.kijeloltek_muv').removeClass('active');

    $.get('../sablon/admin/popup/' + oldal + '.html?cache=' + new Date().getTime(), function( data ) {
        $('#popupTart').html(data);

        switch( oldal ) {
            case 'csoporthoz_adas':
                $('#insertCsop').html($('#insertCsopHidden select').clone());
            break;
        }

        popupNyit();
    });
}

function kijeloltSzamlal( sorok, reszleg, tabla, mellek ) {
    if( tabla === undefined ) {
        tabla = '';
    }

    if( mellek === undefined ) {
        mellek = '';
    }

    if( !sorok.hasClass('kijelolve') && reszleg == 'fo' ) {
        $('#kmCont').removeClass('active');
        $('#munkalapEgyeni').removeClass('dn');
    } else if( sorok.hasClass('kijelolve') && reszleg == 'fo' ) {
        $('#kmCont').addClass('active');
        $('#munkalapEgyeni').addClass('dn');
    }

    if( !sorok.hasClass('kijelolve') && reszleg == 'mellek' ) {
        $('#navGombok a.torles').addClass('disabled').attr('onclick', '');
    } else if( sorok.hasClass('kijelolve') && reszleg == 'mellek' ) {
        gomb_uzi = 'Biztosan törli a kijelölt sorokat?';
        gomb_muv = '<p class="c"><input class="gomb piros" type="button" value="Igen" onclick="mellekTorol(\'' + tabla + '\', \'' + mellek + '\');"><input class="gomb zold" type="button" value="Nem" onclick="alert2()"></p>';

        $('#navGombok a.torles').removeClass('disabled').attr('onclick', 'alert2(\'' + gomb_uzi + '\', gomb_muv);');
    }
}

function mellekTorol( tabla, mellek ) {
    var kijelolve = $('.kijCont .kijSor.kijelolve'),
        idk = {};

    var i = 1;
    kijelolve.each(function() {
        var id = $(this).attr('data-id');

        idk[i] = id;
        i++;
    });

    if( objLength( idk ) ) {
        $.ajax({
         	url : 'esemenyek.php',
         	type: 'POST',
         	data: {
                tabla       : tabla,
                idk         : JSON.stringify(idk),
                mellekTorol : new Date().getTime()
            },
         	success: function(html) {
         		if(html) {
         			html = JSON.parse(html);

                    if( html.hiba ) {
                        alert2(html.hiba, '', 'Hiba');
                        return false;
                    }

                    if( html.statusz == 'siker' ) {
                        szamlaBetolt(_get('id'), baseName(location.href) + '.php', mellek);
                        alert2();
                    }
         		}
         	}
        });
    }

}

function kijelolesEltavolit() {
    if( $('#szamlaList li.szamla_sor').hasClass('kijelolve') ) {
        $('#szamlaList li.szamla_sor').removeClass('active kijelolve');

        $('.kijeloltek_muv').removeClass('active');
        $('#kmCont').removeClass('active');
        $('#munkalapEgyeni').removeClass('dn');

        $('ul.szamla_cont li.csoport').removeClass('kijelolve');

        if( _get('id') ) {
            $('#szamla-' + _get('id')).addClass('active');
        }
    }

    if( $('.kijCont .kijSor').hasClass('kijelolve') ) {
        $('.kijCont .kijSor').removeClass('kijelolve');
        $('#navGombok a.torles').addClass('disabled').attr('onclick', '');
    }
}

/*
function datumra_utemez_mezok_nullaz(id){
    if(id=='d'){
        $('#ciklnap').val('');
        $('#hetkoz_kent').prop('checked', false);
    }
    if(id=='w'){
        $('#ciklhet').val('');
        $('#h') .prop('checked', false);
        $('#k') .prop('checked', false);
        $('#s') .prop('checked', false);
        $('#cs').prop('checked', false);
        $('#p') .prop('checked', false);
        $('#sz').prop('checked', false);
        $('#v') .prop('checked', false);
    }
    if(id=='m'){
        $('#ciklho').val('');
        $('#honapjan') .prop('checked', false);
        $('#ciklho2').val('');
        $('#ho_rendsz') .prop('checked', false);
        $('#ho_rendsz_het') .prop('checked', false);
    }
}*/

function obj_gen() {
    $('#ajaxfedo').removeClass('dn');

    var telephely_id = $('#telephely_id').val();

    if( telephely_id ) {
        var obj = $.ajax({
         	url : 'esemenyek.php',
         	type: 'GET',
         	data: esamp2es('telephely_id=' + telephely_id + '&amp;obj_general=' + new Date().getTime()),
         	success: function(html) {
         		if(html) {
                    $('#popupTart').html(html);
                    popupNyit();
         		}
         	}
        });
    } else {
        $('#ajaxfedo').addClass('dn');

        alert2('Kérem előbb válasszon szállodát!');
        return false;
    }

    $.when( obj ).then(function() {
        $('#ajaxfedo').addClass('dn');
    });
}

function addObj( that ) {
    var text  = $(that).text(),
        recid = $(that).attr('data-recid');

    $('input[name="obj_id"]').val(recid).change();
    $('input[name="obj_id_autocomp"]').val(text).change();

    var form = $('input[name="obj_id"]').parents('#szerk_form');

    stop = false;
    form.find('input, select, textarea').each(function() {
        var name = $(this).attr('name');

        if( stop ) {
            document.getElementById(name).focus();
            return false;
        }

        if( name == 'obj_id' ) {
            stop = true;
        }
    });

    gepVane();
    popupZar();
}

function kapcsolodoSzerkeszt( oldal ) {
    $.get('../sablon/admin/popup/' + oldal + '.html?cache=' + new Date().getTime(), function( data ) {
        $('#popupTart').html(data);

        switch( oldal ) {
            case 'szakterulet':
                $('#addSzakterForm').attr('onsubmit', 'return addSzakterulet(\'' + _get('id') + '\', this);');
            break;
            case 'szallodak':
                $('#addSzallodaForm').attr('onsubmit', 'return addSzalloda(\'' + _get('id') + '\', this);');
            break;
            case 'megjegyzes':
                $('#addMegjegyzes').attr('onsubmit', 'return addMegjegyzes(\'' + _get('id') + '\', this);');
            break;
        }

        popupNyit();
    });
}

function popupZar() {
    $('#popup').css('display', 'none');
    $('#popupTart').html('');
}

function popupNyit() {
    $('#popup').css('display', 'block');
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

function scrollLeptet() {
    var active_top      = $('.szamla_sor.active').position().top,
        activeHeight    = $('.szamla_sor.active').outerHeight(),
        tartBalHeight   = $('#tartalomBal').outerHeight();

    /* Összeadjuk az active elem magasságát és a felső eltolását */
    var aTopH = active_top + activeHeight;

    if( (aTopH - activeHeight) < 0 ) {
        $('#tartalomBal').scrollTop(0);
        aTop = $('.szamla_sor.active').position().top;
        $('#tartalomBal').scrollTop(aTop - 250);
    } else if( aTopH > tartBalHeight ) {
        $('#tartalomBal').scrollTop(0);
        aTop = $('.szamla_sor.active').position().top;
        $('#tartalomBal').scrollTop(aTop - tartBalHeight + activeHeight + 250 );
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
    if( $('.szamla_sor').hasClass('active') ) {
        recid = $('.szamla_sor.active').attr('data-recid');
    }

    if( tabla ) {
        $('#ajaxfedo').removeClass('dn');

        var lista = $.ajax({
            url : 'esemenyek.php',
            type: 'GET',
            data: esamp2es('id=' + recid + '&amp;ksz=' + ksz + '&amp;rkat=' + rkat + '&amp;rend=' + rend + '&amp;irany=' + irany + '&amp;tabla=' + tabla + '&amp;szamlaLista=' + new Date().getTime()),
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
            $('#ajaxfedo').addClass('dn');

            if( irany == 'asc' ) {
                ri = 'desc';
                rt = '▲ <span>A-Z</span>';
            } else if( irany == 'desc' ) {
                ri = 'asc';
                rt = '▼ <span>Z-A</span>';
            }

            $('#rendezesIrany').attr('href', 'javascript: bejegyzesekLista( \'#rendezesSelect\', \'' + ri + '\', \'' + rkat + '\' ); void(0);').html(rt);

            if( $('.szamla_sor').hasClass('active') ) {
                active_top = $('.szamla_sor.active').position().top;
                $('#tartalomBal').scrollTop(active_top - 250);
            }
        });
    }
}

function bejegyzesekKeres() {

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

                        $('#beleptet_alert div.header').html('<h1 style="color: #FF5353">Helytelen felhasználói név vagy jelszó!</h1');
                        return false;
                    }

                    location.href = 'index.php';
         		}
         	}
        });
    }

    return false;
}

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
                        alert2(html.uzi);
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

function addMegjegyzes( pageID, that ) {
    var megj = $(that).find('textarea[name="megjegyzes"]').val();

    if( pageID && megj ) {
        var ajax = $.ajax({
         	url : 'esemenyek.php',
         	type: 'POST',
         	data: {
                pageID: pageID,
                megj: megj,
                tabla: baseName(location.href),
                addMegjegyzes: new Date().getTime()
            },
         	success: function(html) {
         		if(html) {
                    html = JSON.parse(html);

                    if( html.statusz == 'hiba' ) {
                        alert2(html.uzi);
                        return false;
                    }

         			szamlaBetolt(pageID, baseName(location.href) + '.php', 'megjegyzes').done(function() {
                        $('#naploSor-' + html.kapcsID).addClass('uj');
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
                        alert2(html.uzi);
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
        param = link[1].split('&');

        for( key in param ) {
            var p = param[key];

            if( param[key].indexOf('=') != -1 ) {
                var get = param[key].split('=');

                if( get[0] == ertek ) {
                    return get[1];
                }
            } else {
                if( param[key] == ertek ) {
                    return true;
                }
            }
        }
    } else {
        return false;
    }

    return false;
}

function szamlaBetolt( recid, page, muvelet, szerkeszt ) {
    if( szerkeszt === undefined ) {
        szerkeszt = false;
    }

    szerk = '';
    if( szerkeszt ) {
        szerk = 'szerkeszt=1&amp;';
    }

    var dfrd1 = $.Deferred();

    /* Naptár megjelenítése */
    if(page=='utemezett_feladatok.php'){
        if(muvelet.indexOf('naptar')>=0){
            $('#naptar').show();
            $('#szamlaList').hide();
            $('#utemezes').hide();
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
        $('#ajaxfedo').removeClass('dn');

        var szamla = $.ajax({
         	url : page,
         	type: 'GET',
         	data: esamp2es('id=' + recid + '&amp;ajax=1&amp;' + szerk + muvelet + '=' + new Date().getTime()),
         	success: function(html) {
         		if(html) {
                    html = JSON.parse(html);

         			$('#szamlaBetolt').html(html.tartalom).promise().done(function() {
                        dfrd1.resolve();
                    });

                    param = get_get([
                        'akt_nap',
                        'bejelentesek',
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
                        'modosit',
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
                        'szallodak',
                        'szerkeszt',
                        'szakterulet',
                        'terkep',
                        'tortenet',
                        'anyagigenyles'
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

                    window.history.pushState("", "", getHost() + param );

                    if( _get('szakterulet') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'szakterulet\'); void(0);');
                    } else if( _get('szallodak') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'szallodak\'); void(0);');
                    } else if( baseName(location.href) == 'bejelentes' && _get('megjegyzes') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');
                    } else if( baseName(location.href) == 'utemezett_feladatok' && _get('megjegyzes') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');
                    } else if( baseName(location.href) == 'munkalap' && _get('megjegyzes') ) {
                        $('#navGombok a.uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'megjegyzes\'); void(0);');
                    } else {
                        $('#navGombok a.uj').attr('href', baseName(location.href) + '.php?uj');
                    }
         		} else {
                    alert2('Nem lehet betölteni a kért oldalt!');
                    dfrd1.resolve();
                }
         	}
        });

        return $.when(szamla, dfrd1).done(function() {
            $('#ajaxfedo').addClass('dn');
            formModositva = false;
            gombokRejt();
        }).promise();
    }

}

function szamlaSorFrissit( tabla, recid ) {
    if( tabla && recid ) {
        $.ajax({
         	url : 'esemenyek.php',
         	type: 'GET',
         	data: esamp2es('tabla=' + tabla + '&amp;recid=' + recid + '&amp;szamlaSorFrissit=' + new Date().getTime()),
         	success: function(html) {
         		if(html) {
         			$('#szamla-' + recid).html(html);
         		}
         	}
        });
    }
}

function ertekFoglalt(originVal, value, tabla, mezo) {
    if( originVal != value && value && tabla && mezo ) {
        $.ajax({
         	url : 'esemenyek.php',
         	type: 'GET',
         	data: esamp2es('value=' + value + '&amp;tabla=' + tabla + '&amp;mezo=' + mezo + '&amp;ertekFoglalt=' + new Date().getTime()),
         	success: function(html) {
         		if(html) {
         			html = JSON.parse(html);

                    if( html.uzi ) {
                        alert2(html.uzi);
                    }
         		}
         	}
         });
    }
}

function htmlspecialchars_decode(string, quoteStyle) {
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

            $('#gep_id_autocomp').val('').removeClass('mod');
            $('#gep_id').val('');
        }
    }
}

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

function gepVane() {
    $('#ajaxfedo').removeClass('dn');

    var telephely_id = $('#telephely_id').val(),
        obj_id = $('#obj_id').val();

    var gepvane = $.ajax({
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

    $.when(gepvane).then(function() {
        $('#ajaxfedo').addClass('dn');
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

/* Dátumra ütemezés tesztelésére */
function datumra_utemezes(id){
    $.ajax({
        url: 'utemezett_feladatok.php',
        type: 'GET',
        data: esamp2es('id=' + id + '&amp;datumra_utemezes=' + new Date().getTime()),
        success: function (response) {
            if (response) {
                response = JSON.parse(response);
                alert2(response['uzenet']);
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
