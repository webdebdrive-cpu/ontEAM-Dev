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
    } else {
        $('div.loginAlert').addClass('dn');
    }
}

$(function() {
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

            if( className.indexOf('disableNav') !== -1 ) {
                return false;
            }

            if( baseName(location.href) == 'bejovo_szamlak' || baseName(location.href) == 'kimeno_szamlak' ) {
                if( baseName(location.href) == 'bejovo_szamlak' ) {
                    tabla = 'bejovo_szamlak';
                } else if( baseName(location.href) == 'kimeno_szamlak' ) {
                    tabla = 'kimeno_szamlak';
                }

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

                location.href = tabla + '.php?unset=&epo=50&oldal=1&order=nev&id=' + szamlaID + '&modosit';

                e.preventDefault();
            }
        }
    });

    $('#szamlaListaKeres').on('keyup', function() {
        var val   = $(this).val(),
            tabla = $(this).attr('data-tabla');

        if( tabla ) {
            $.ajax({
             	url : 'esemenyek.php',
             	type: 'GET',
             	data: esamp2es('val=' + val + '&amp;tabla=' + tabla + '&amp;szamlaLista=' + new Date().getTime()),
             	success: function(html) {
             		if(html) {
             			$('#tartalomBal').html(html);
             		}
             	}
            });
        }
    });

    formModositva = false;
    $('#szerk_form').find('input, select, textarea').on('keyup change', function(e) {
        var name = $(this).attr('name'),
            type = $(this).attr('type'),
            val  = $(this).val();

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
    });

    $(window).bind('beforeunload', function(){
        if( formModositva ) {
            return 'Biztos elhagyod az oldalt?';
        }
    });
});

function gombokNullaz() {
    $('#szerk_form').find('input, select, textarea').each(function() {
        $(this).removeClass('mod');
    });

    $('#visszavonas_gomb').attr('disabled', true);
    $('#kilepes_gomb').val('Kilépés');
}

function felhValt( that ) {
    var felh_nev  = $( that ).find('input[name="login_nev"]').val(),
        felh_mail = $( that ).find('input[name="login_jelszo"]').val();

    if( felh_nev && felh_mail ) {
        $.ajax({
         	url : 'beleptetes.php',
         	type: 'POST',
         	data: esamp2es('nev=' + felh_nev + '&amp;jelszo=' + felh_mail + '&amp;ajax=1&amp;login=' + new Date().getTime()),
         	success: function(html) {
         		if(html) {
         			html = JSON.parse(html);

                    if( html.statusz == 'hiba' ) {
                        $('#beleptet_alert div.header').html('<h1 style="color: #FF5353">Helytelen belépés!</h1');
                        return false;
                    }

                    location.href = 'index.php';
         		}
         	}
        });
    }

    return false;
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
