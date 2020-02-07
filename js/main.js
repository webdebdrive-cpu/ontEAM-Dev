var PHP_SELF = baseName(location.href)+'.php';

/* Jquery kiegészítés CursorTo */
(function ($) {

    $.cursorTo = function (el, index) {
        if (el.createTextRange) {
            var range = el.createTextRange();
            range.move("character", index);
            range.select();
        } else if (el.selectionStart != null) {
            el.focus();
            el.setSelectionRange(index, index);
        }
    };

    $.fn.cursorTo = function (index, offset) {
        return this.queue(function (next) {
            if (isNaN(index)) {
                var i = $(this).val().indexOf(index);

                if (offset === true) {
                    i += index.length;
                } else if (offset) {
                    i += offset;
                }

                $.cursorTo(this, i);
            } else {
                $.cursorTo(this, index);
            }

            next();
        });
    };

    $.fn.cursorToStart = function () {
        return this.cursorTo(0);
    };

    $.fn.cursorToEnd = function () {
        return this.queue(function (next) {
            $.cursorTo(this, $(this).val().length);
            next();
        });
    };
}(jQuery));
/* Curzor aktuális pozíciója */
(function ($) {
    $.fn.getCursorPosition = function () {
        var input = this.get(0);
        if (!input) return;
        if ('selectionStart' in input) {

            return input.selectionStart;
        } else if (document.selection) {

            input.focus();
            var sel = document.selection.createRange();
            var selLen = document.selection.createRange().text.length;
            sel.moveStart('character', -input.value.length);
            return sel.text.length - selLen;
        }
    }
})(jQuery);

/*
*   a_az()
*	adoszam_formaz()		Adószám megadásakor formázza a megadott számot
*	checkbox_beallit()		Összes kijelölése/törlése-hez kell adminnál. A paraméterben megdott form del[] checkboxíit a paraméterként átadott értékre állítja (továbbfejleszthető lenne, hogy a del[] is param legyen)
*	checkbox_switch()       A checkboxot ki-be pipálja
*	createCookie()          Cookieba írás - olvasás
*	esamp2es()              Az &amp;-ból csinál &-eket, hogy az ajax paraméterei HTML környezetben is használható legyen.
*	ezresek()				A számokat ezresekre osztja a megadott szeparátorral
*	fajlnev_keszit()        Egy átlagos szóból mappa/fájlnevet készít
*   fb_like()               facebookra készít egy valid like gombot XFBML-ben
*	fordit_string()			<= class.Head.php-ba kerül => A megkapott sztringet megfordítjat
*	form_ell()              Általános űrlap ellenőrző ajaxos űrlap feldolgozáshoz
*	get_get()               Get változókat adja vissza
*	get_file_thumb()        Fájlfeltöltés után ez a függvény felel a thumbnailok kiíratásáért
*	goto_url_idozitett()	Paraméterként megadott címre és idő után léteti az oldalt 404-es oldalaknál használatos főleg
*   in_array()              PHP szerű in_array
*	insert_text()			BB kódhoz első sorban. Egy textareába operál
*	insert_text_prompt()	Linkbeszúráshoz:  Olyan mint az insert_text() (sőt ezt hívja), de promtos, tehát kérdez előtte
*   is_json()               Megállapítja, hogy egy string JSON adat-e
*   is_valid_mail()         Ellenőrzi, hogy az e-mail cím megfelelő formátumú-e
*   isz2varos()             Irányítószámból várost
*	kedvencek_koze()		Kedvencek közéteszi
*	mail_rejtes()			<= class.Head.php-ba kerül => Mailcím elrejtése. Fordított e-mail címet vár, kukac és pont helyett [kukac]/[pont] szöveggel. A PHP mail_rejtes()-el működik együtt
*	mobilszam()             Mobilszám formázás pl: 304509705 => (30) 4509-705
*	onlyNumbers()			Csak számokat fogad el inputkor, Használata:  onkeyup="onlyNumbers(this)"
*   plus1()                 Google +1 kódot generál, hogy valid legyen a forrás
*	popup()					Popup ablak megnyitása
*	respo_kepek()           A class="respo"-val ellátott képeknél a data-src-desktop, data-src-tablet és data-src-mobil attribútomban megadott src-ket cserélgeti a paraméterként is megadható határoknál.
*	szokoz_szur()           A kapott stringből kiszűri a szóközöket (pl. kosár e-mail cím begépeléséhez)
*   sql_ertek()             SQL érték beszúrásához
*/

/* PHP-ban is használt a_az függvény */
function a_az( fajlnev ){
    var a_az = 'a';
    var kezdobetu = fajlnev.substr(0,1);
    if(
            kezdobetu=='a' || kezdobetu=='e' || kezdobetu=='i' || kezdobetu=='o' || kezdobetu=='u' ||
            kezdobetu=='A' || kezdobetu=='E' || kezdobetu=='I' || kezdobetu=='O' || kezdobetu=='U' ||
            kezdobetu=='á' || kezdobetu=='é' || kezdobetu=='í' || kezdobetu=='ó' || kezdobetu=='ú' || kezdobetu=='ü' || kezdobetu=='ű' || kezdobetu=='ö' || kezdobetu=='ő' ||
            kezdobetu=='Á' || kezdobetu=='É' || kezdobetu=='Í' || kezdobetu=='Ó' || kezdobetu=='Ú' || kezdobetu=='Ü' || kezdobetu=='Ű' || kezdobetu=='Ö' || kezdobetu=='Ő'
            ){
        a_az = 'az';
    }
    return a_az;
}

/**
 * @desc Adószám megadásakor formázza a megadott számot xxxxxxxx-x-xx alakúra
 *
 *	@param int szam: egy számérték
 *	@return string: a formázott adószám
 */
function adoszam_formaz(szam) {
    szam = szam.replace(/-/gi,  '');
    n = szam.length;
    uj = '';
    hanyszor = 0;
    for (i = 0; i < n; i++) {
        if(!isNaN(parseFloat(szam.substr(i, 1)))){
            switch (i) {
                case 8:
                    uj = uj + '-' + szam.substr(i, 1);
                break;

                case 9:
                    uj = uj + '-' + szam.substr(i, 1);
                break;

                default:
                    uj = uj + szam.substr(i, 1);
                break;
            }
        }
    }
    return uj.substring(0, 14);
}


/**
 * @desc Cégjegyzékszám megadásakor formázza a megadott számot xx-xx-xxxxxx alakúra
 *
 * @param int szam: egy számérték
 * @return string: a formázott cégjegyzékszám
 */
function cegjegyzekszam_formaz(szam) {
	szam = szam.replace(/-/gi, '');
	n = szam.length;

	if( n == 10 && !isNaN(szam) ) { /* 10 számjegyből kell állnia */

		uj = '';
		hanyszor = 0;
		for (i = 0; i < n; i++) {
			if(!isNaN(parseFloat(szam.substr(i, 1)))){
				switch( i ) {
					case 2:
						uj = uj + '-' + szam.substr(i, 1);
					break;

					case 4:
						uj = uj + '-' + szam.substr(i, 1);
					break;

					default:
						uj = uj + szam.substr(i, 1);
					break;
				}
			}
		}

		return uj;
	}else {
		alert2('Hibás cégjegyzékszám!');
	}

}

/**
 * @desc Összes kijelölése/törlése
 *
 * @param   string   form neve
 * @param   boolean  igaz/hamis
 *
 * @return  boolean  mindig true
 */
function checkbox_beallit(form, ertek){
    var elemek = document.forms[form].elements['del[]'];
    var elemek_db = elemek.length;

    if(elemek_db){
        for(var i=0; i<elemek_db; i++) {
            elemek[i].checked = ertek;
        }
    }else{
		elemek.checked = ertek;
	}
    return true;
}



/**
 * @desc Checkbox ki-be kapcsolása
 *
 * @param   string   checbox id-ja
 * @param   bool   Ha igaz, akkor a mehet_gomb-ot nem színezi el az admin felületen
 */
function checkbox_switch(id, mehet_gomb_nemszinez){

    if($('#'+id).attr('checked')){
        $('#'+id).attr('checked', '');
    }else{
        $('#'+id).attr('checked', 'checked');
    }
    if(!mehet_gomb_nemszinez && $('form input[type="submit"]').length()){
        $('form input[type="submit"]').addClass('gombb_alert');
    }

    return true;
}

/* Cookieba írás - olvasás */
function createCookie(name, value, days){
    if (days) {
        var date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        var expires = '; expires='+date.toGMTString();
    }
    else var expires = '';
    document.cookie = name+'='+value+expires+'; path=/';
}

function readCookie(name){
    var nameEQ = name + '=';
    var ca = document.cookie.split(';');

    for(var i=0;i < ca.length;i++){
        var c = ca[i];

        while(c.charAt(0)==' '){
            c = c.substring(1,c.length);
        }
        if (c.indexOf(nameEQ) == 0){
            return c.substring(nameEQ.length,c.length);
        }
    }
    return null;
}

function eraseCookie(name){
    createCookie(name,'',-1);
}

/**
*	@desc Nevet és e-mail címet ellenőzés
*	Hírlevélre iratkozáskor, regisztrációkor jön jól. Név nem lehet üres, az e-mail cím pedig megfelelő formátumúnak kell lennie
*
*	@param string nev: a név mező id neve
*	@param string mail: a mail mező id neve
*
*	@return boolean: (true | false)
*//*
Ez már elavult, érdemes PHP-val, Ajax-al ellenőrizni az adatok helyességét.
function email(nev,mail){
	if (nev!=null && ( document.getElementById(nev).value=='' || document.getElementById(nev).value=='Név') ){
		document.getElementById(nev).focus();
		scrollto('#'+nev, 500);
		alert2("Kérem adja meg nevét!");
		return false;
	}else if( document.getElementById(mail).value=='' || document.getElementById(mail).value=='E-mail cím'){
		document.getElementById(mail).focus();
		scrollto('#'+mail, 500);
		alert2("Kérem adja meg e-mail címét!");
		return false;
	}else{
		str=document.getElementById(mail).value.trim();
		filter=/^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i;
		if (!filter.test(str)){
			document.getElementById(mail).focus();
			scrollto('#'+mail, 500);
			alert2("Kérem egy valós e-mail címet adjon meg!");
			return false;
		}
	}
	return true;
}
*/
/* Esamp2ES függvény */
function esamp2es(string){
    if( string !== undefined ) {
        string = string.replace(/&/g, '!ES!').replace(/\+/g, '!PLUSZ!').replace(/#/g, '!SHARP!');
        return string.replace(/!ES!amp;/g, '&');
    }

    return '';
}

/**
*	@desc Ezresek
*	A számokat ezresekre osztja a megadott szeparátorral, amikor egy inputba beírjuk
*
*	@param int szam: egy számérték
*	@param string tizedes vessző: a tizedes vessző karakteer
*	@param string szep: a szeparátor, amivel az ezreseket tagoljuk
*	@param bool: a nullát is írja ki, ne üres sztingként kerüljön vissza
*
*	@return string: a formázott számérték
*/
function ezresek(number, decimals, decPoint, thousandsSep, nullat_is_irja_ki){

    number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
    var n = !isFinite(+number) ? 0 : +number;
    var prec = !isFinite(+decimals) ? 0 : Math.abs(decimals);
    var sep = (typeof thousandsSep === 'undefined') ? ',' : thousandsSep;
    var dec = (typeof decPoint === 'undefined') ? '.' : decPoint;

    var s = '';
    var toFixedFix = function (n, prec) {
        var k = Math.pow(10, prec);
        return '' + (Math.round(n * k) / k).toFixed(prec);
    };

    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    
    eredmeny = s.join(dec);
    if( eredmeny == '0' && !nullat_is_irja_ki ){
        eredmeny = '';
    }
    
    return eredmeny;
}
function number_format(number, decimals, decPoint, thousandsSep){
    ezresek(number, decimals, decPoint, thousandsSep);
}

function ezresek2(szam){
    return szam.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ').replace('.', ',');
}

/**
*	Fájlnevet készít
*	A kapott sztinet átalakítja olyanra, hogy ne tartalmazzon ékezeteket, speciális karaktereket
*
*	@param string $string: amit feldolgoz
*
*	@return array: a lecsupaszított név
*/
function fajlnev_keszit(string){
    string = string.toLowerCase();
    string = string.replace(/á/g, "a").replace(/é/g, "e").replace(/í/g, "i").replace(/ó/g, "o").replace(/ö/g, "o").replace(/ő/g, "o").replace(/ú/g, "u").replace(/ü/g, "u").replace(/ű/g, "u");
    return string.replace(/[^a-z0-9]/gi, '_');
}

/**
*	@desc Facebook Like gombot generál validan
*	Az iframe nem valid, ezért egy JS függvénnyel iratjuk ki, amit felparaméterezünk
*
*	@param string href: az URL amit linkelónk
*	@param string id: a div id-je (alap: fb-root)
*	@param string layout: elrendezés (standard | button_count | box_count)
*	@param string colorscheme: színvilág (light | dark)
*	@param string width: szélesség
*	@param string height: magasság
*	@param string show_faces: arcokat mutasson-e vagy sem (true | false)
*	@param string action: művelet (like | recommend)
*	@param string send: üzenetet lehessen-e hozzá fűzni (true | false)
*	@param string nyelv: a feliratok nyelve (alap: hu_HU)
*	@param string font: betűtipus
*
*	@return string: az XFBML kód
*/
function fb_like(href, id, layout, colorscheme, width, height, show_faces, send, action, nyelv, font){
    if(!href)       {href = document.location.href; }
    if(!id)         { id            = 'fb-root';    }
    if(!layout)     { layout        = 'standard';   } /*standard | button_count | box_count*/
    if(!colorscheme){ colorscheme   = 'light';      } /* light | dark*/
    if(!width)      { width         = '';           }
    if(!height)     { height        = '';           }
    if(!show_faces) { show_faces    = 'false';      }
    if(!send)       { send          = '';           }
    if(!action)     { action        = 'like';       } /* like | recommend */
    if(!nyelv)      { nyelv         = 'hu_HU';      }
    if(!font)       { font          = '';           }
    document.write('<div id="' + id + '"></div><script src="http://connect.facebook.net/' + nyelv + '/all.js#xfbml=1"></script><fb:like href="' + href + '" send="' + send + '" layout="' + layout + '" width="' + width + '" height="' + height + '" show_faces="' + show_faces + '" action="' + action + '" colorscheme="' + colorscheme + '" font="' + font + '"></fb:like>');
}


/**
*	@desc Fordít String
*	A kapott string-et megfordítja
*
*	@param string szoveg: a fordítandó szöveg
*
*	@return string: a megfordított szöveg
*/
function fordit_string(szoveg){
	forditott = '';
	var i = szoveg.length;

	for (i; i > 0; i-- ) {
	 forditott += szoveg.substring(i-1, i);
	}
	return forditott;
}


/**
 *  @desc Regisztrációs űrlap ellenőrzése
 *
 *  @param object: az űrlap
 *  @param bool: ha az űrlap rendben van, akkor hívjunk-e meg más függvényt (jellemzően egy Ajax-os űrlapfeldolgozásra jó). Ha ez a paraméter üres, akkor pedig az űrlap feldolgozásra kerül.
 */
function form_ell( that, uj_fuggvenyt_hivj ) {

    var animacio_sebesseg   = 500,
        hiba                = false,
        jelszo_min_hossz    = 5,
        eltolas             = -120;

    $(that).find('input, select, textarea').removeClass('alert');

    $(that).find('input, select, textarea').each(function() {

        var alert  = $(this).attr('data-alert'),
            kihagy = $(this).hasClass('kihagy');

        if( !kihagy ) {
            if( alert !== undefined ) {
                var tag   = $(this).prop('localName'),
                    value = $(this).val().trim(),
                    name  = $(this).attr('name');

                switch(tag) {

                    case 'input':
                        switch($(this).attr('type')) {

                            case 'radio':
                                value = $('input[name="' + name + '"]:checked').val();

                                if( value === undefined ) {
                                    value = '';
                                }

                                if( !value ) {

                                    alert2(alert);

                                    $(this).addClass('alert');
                                    scrollto($(this), animacio_sebesseg, eltolas);

                                    hiba = true;
                                    return false;
                                }
                            break;

                            case 'checkbox':
                                value = $(this).prop('checked');

                                if( !value && alert ) {
                                    alert2(alert);
                                    var ikon = $(this).parent('label').find('.ikon');
                                    ikon.addClass('alert');
                                    scrollto(ikon, animacio_sebesseg, eltolas);

                                    hiba = true;
                                    return false;
                                }
                            break;

                            case 'text':
                                switch(name) {
                                    case 'reg_mail':

                                        if( !value ) {

                                            alert2(alert);
                                            $(this).addClass('alert');
                                            scrollto($(this), animacio_sebesseg, eltolas);

                                            hiba = true;
                                            return false;

                                        } else if( !is_valid_mail(value) ) {

                                            alert2('Helytelen e-mail cím!');
                                            $(this).addClass('alert');
                                            scrollto($(this), animacio_sebesseg, eltolas);

                                            hiba = true;
                                            return false;
                                        }
                                    break;

                                    default:
                                        if( !value ) {

                                            alert2(alert);
                                            $(this).addClass('alert');
                                            scrollto($(this), animacio_sebesseg, eltolas);

                                            hiba = true;
                                            return false;
                                        }
                                    break;
                                }
                            break;

                            case 'password':
                                if( !value ) {

                                    alert2(alert);

                                    $(this).addClass('alert');
                                    scrollto($(this), animacio_sebesseg, eltolas);

                                    hiba = true;
                                    return false;
                                }

                                // Hány jelszó mező van?
                                var pass_ok = new Array();
                                $(that).find('input[type="password"]').each(function() {
                                    pass_ok.push($(this));
                                });

                                if(pass_ok.length>1){

                                    var egyezik = true;
                                    var jelszo = pass_ok[0].val();
                                    for(p=0; p<pass_ok.length; p++){
                                        if(pass_ok[p].val()!=jelszo){
                                            egyezik = false;
                                        }
                                    }

                                    if( pass_ok[0].val().length < jelszo_min_hossz ) {

                                        alert2('A jelszónak legalább '+jelszo_min_hossz+' karakternek kell lennie!');

                                        $(this).addClass('alert');
                                        scrollto($(this), animacio_sebesseg, eltolas);

                                        hiba = true;
                                        return false;

                                    } else if( !egyezik ) {

                                        alert2('A jelszavak nem egyeznek!');

                                        $(that).find('input[type="password"]').each(function() {
                                            $(this).addClass('alert');
                                        });
                                        scrollto($(this), animacio_sebesseg, eltolas);

                                        hiba = true;
                                        return false;
                                    }
                                }

                            break;
                        }
                    break;

                    case 'select':
                        if( !value ) {

                            alert2(alert);
                            $(this).addClass('alert');
                            scrollto($(this), animacio_sebesseg, eltolas);

                            hiba = true;
                            return false;

                        }
                    break;
                }
            }
        }
    });


    //ha van 2. paraméter, akkor a "uj_fgv_hiv" ID-jú div-re csinálunk egy klikkelést. Így tudunk új függvényt meghívni, amelyenek nem tudjuk még, hogy mi lesz a neve (eltér ugyanis regnél, kosárnál, üzenet küldésnél)
    if(!hiba && uj_fuggvenyt_hivj){
        $('#uj_fgv_hiv').click();
    }

    if(hiba){
        return false;
    }else{
        return true;
    }
}

/**
* @desc Kép elforgatása megadott százalékkal
*
* @param php            string Annak a PHP fájlnak a neve, amelyikkel a $_GET['get_file_thumb']-ot figyeljük, és a megfelelő fájlkiíratással válaszolunk rá
* @param kiir_id        string Annak a HTML elem ID-ja, amibe a kiíratást megtesszük
*
* @return               string A kiíratott kép HTML-je
*/
function get_file_thumb(php, kiir_id){
    $.ajax({
        url : php,
        type: 'POST',
        data: esamp2es('get_file_thumb=' + new Date().getTime()),
        success: function(html){
            if(html.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            alert2();
            $('#'+kiir_id).html(html);
        },
        complete: function(xhr) {
            if( xhr.status != 200 ) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}

/**
*	@desc Goto URL időzített
*	Az oldalt a megadott URL-re irányítja. Elsősorban a 404-es hiba esetén dobja a látogatóka a nyitlapra egy idő után
*
*	@param string url: a cím ahová lépni kell
*	@param int ido: ezredmésodpercek számként. Ha nincs megadva akkor 5mp
*/
function goto_url_idozitett(url,ido){
	if(!ido){ido=5000;}
    setTimeout("location.href='"+url+"'",ido);
}


/**
*	@desc in_array PHP szerű működés
*
*	@param object obj: tetszőleges objektum
*	@param array a: a tömb miben keressük
*
*	@return boolean
*/
function in_array(obj, a){
  for(var i = 0; i < a.length; i++) {
    if(a[i] === obj){
      return true;
    }
  }
  return false;
}

/**
*	@desc Insert text - egy textareába szúr be tegeket (BB kód fórumoknál)
*
*	@param string txtId: a textarea id-je
*	@param string oTag: nyitó teg
*	@param string cTag: záró teg
*	@param string defText: a nyitó és záró teg közötti alapértelmezett szöveg
*/
function insert_text(txtId, oTag, cTag, defText){
	var txt = document.getElementById(txtId);
	txt.focus();

	if (txt.selectionStart != undefined){/*FF*/
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
	}else if (document.selection){/*IE*/
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
	return true;
}

/**
*	@desc Insert text promt - olyan mint a sima insert text, de ez protos, tehát egy inputot is kérdez => URL
*
*	@param string input: a textarea id-je, ezzel hívja meg az insert_text() függvényt
*	@param string uzi: üzenet az input bekérésekor
*	@param string text: alapértelmezett érték
*/
function insert_text_prompt(input,uzi,text){
	ret=prompt(uzi,text);
	insert_text(input,"[url='"+ret+"']","[/url]",ret);
	return true;
}

/**
*	@desc JSON adat-e
*
*	@param string $str: A string, amiről megállapítjuk, hogy JSON adat-e
*	@return boolean
*/
function is_json(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}

/**
*	@desc Valid e-mail cím-e
*
*	@param string $email: E-mail, melyről meg kell állípítani, hogy megfelelő alakú-e
*	@return boolean
*/
function is_valid_mail(email) {
    var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email.trim());
}

/**
*	@desc Valid e-mail email cimek-e
*
*	@param string $email_cimek: E-mail címek vesszővel felsorolva, melyről meg kell állípítani, hogy megfelelő alakú-e
*	@return boolean
*/
/* Erre mégsem lesz szükség, PHP-val validáljunk
function is_valid_mail_tobb(email_cimek) {
    if( email_cimek.trim() ){
        email_cimek = email_cimek.split(',');
        for( i=0; i<email_cimek.length; i++ ){
            if( !is_valid_mail( email_cimek[i].trim()) ){
                return false;
            }
        }
    }
    return true;
}*/

/**
*	@desc Irányítószámból kiszámoljuk a várost. Ehhez kell egy isz input meg egy város input ID-t, illetve egy város adatbázis kell még hozzá (minta van rá a WDBHU-ban).
*
*	@param string isz_id: Az irányítószám input id-ja
*	@param string varos_id: A város input id-ja
*	@param string utvonal: Adminból így tudod használni. Add meg értékül, hogy "../"
*/
function isz2varos(isz_id, varos_id, utvonal) {
    if(!utvonal){
        utvonal = '';
    }
    if($('#'+isz_id).val().length>=4){
        $.ajax({
            url: utvonal+'weblap.php',
            type: 'GET',
            data: esamp2es('isz='+$('#'+isz_id).val()+'&amp;isz2varos=' + new Date().getTime()),
            success: function(html) {
                if(html.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if (html) {
                    $('#'+varos_id).val(html);

                    /*Kosárban, ha van olyan pipa, hogy adatok másol*/
                    if( isz_id=='szall_isz' && $('#adat_masol').is(':checked') ) {
                        $('#szaml_varos').val(html);
                    }
                }
            },
            complete: function(xhr) {
                if( xhr.status != 200 ) {
                    alert2(xhr.responseText, '', 'Hiba');
                }
            }
        });
    }
}

/**
*	@desc Kedvencek közé tesz IE alatt, más esetekben jelzi, hogy manuálisan tehető csak meg
*
*	@param string url: Az url amit hozzá akarunk adni a kedvencekhez
*	@param string cim: Az url-hez rendelt megnevezés
*/
function kedvencek_koze(url,cim){
	if(navigator.appName=='Microsoft Internet Explorer'){
		window.external.addfavorite(url,cim);
	}else{
		alert2('A hozzáadás nem támogatott. Kérem állítsd be manuálisan!');
	}
	return true;
}

/**
*	@desc E-mail cím rejtése a HTML kódban (fontos, hogy az e-mail cím link záró </a> tagja ne szerelepjen, ez itt kerül pótlásra)
*	Egy link teget vár, mely az e-mail címet fordítva tartalmazza, illetve a @ és . helyett [kukac] és [pont] szövegeket
*	Php-nak átadjuk a rendes tartalmat, abból elkészül a kívánt kód, azzal hívjuk meg ezt a js fgv-t. fordit_string() fgv-t felhasználja
*   A kiíratás végére kerül egy '</a>'. Ez azért van, hogy a HTML forrásban ez ne szrepeljen, mert a validátor nem szereti
*
*	@param string tartalom: A link tegje. Pl.: <a style="font-size: 11px" href="mailto:uh[pont]bedbew[kukac]kb">küldj üzenentet</a>
*/
function mail_rejtes(tartalom){
    tartalom      = tartalom.replace('&gt;', '>');
	talalatok     = tartalom.match(/href=['\"]([^'\"]*)['\"]/);
	eredeti_href  = talalatok[1];
    mailto_nelkul = eredeti_href.replace('mailto:', '');
	csak_cim      = mailto_nelkul.replace('!kukac!', '@');
	csak_cim      = csak_cim.replace(eval('/!pont!/g'), '.');
	csak_cim      = csak_cim.replace(/<a /g, '');
    csak_cim      = csak_cim.replace('</a>', '');
	forditott_cim = fordit_string(csak_cim);

	return '<a '+tartalom.replace(eval('/'+mailto_nelkul+'/gi'),forditott_cim)+'</a>';
}


/**
 *  @desc Mobilszám formázás pl: 304509705 => (30) 4509-705
 *	A számokat megformázza, hogy mobilszám vagy telefonszámhoz megfelelő formátumot vegyen fel
 *
 *	@param int szam: egy számérték
 *	@return string: a formázott számérték
 */
function mobilszam(szam) {
    szam = szam.replace(/\+/gi, '');
    szam = szam.replace(/-/gi,  '');
    szam = szam.replace(/ /gi,  '');
    szam = szam.replace(/\(/gi, '');
    szam = szam.replace(/\)/gi, '');

    n = szam.length;
    uj = '';
    hanyszor = 0;
    for (i = 0; i < n; i++) {
        if(!isNaN(parseFloat(szam.substr(i, 1)))){
            switch (i) {
                case 2:
                    uj = uj + ' (' + szam.substr(i, 1);
                break;

                case 4:
                    uj = uj + ') ' + szam.substr(i, 1);
                break;

                case 7:
                    uj = uj + '-' + szam.substr(i, 1);
                break;

                default:
                    if (i > 7 && (i + 1) % 4 == 0) {
                        uj = uj + '-' + szam.substr(i, 1);
                    } else {
                        uj = uj + szam.substr(i, 1);
                    }
                break;
            }
        }
    }
    return '+' + uj;
}


/**
*	@desc onlyNumbers
*	Csak számokat ended beírni az adott inputba (mibe). Használata: <input type="text" onkeyup="onlyNumbers(this)" >
*
*	@param html input elem
*/
function onlyNumbers(that){

    if(that){
        value = that.val();
        ki = '';
        for(i=0;i<value.length;i++){
            if (value.substr(i,1)>='0' && value.substr(i,1)<='9' || value.substr(i,1)=="." || value.substr(i,1)=="," || value.substr(i,1)=="-" || value.substr(i,1)==" "){
                ki+=value.substr(i,1);
             }
        }
        that.val(ki);
    }

}

/**
*	@desc onlyNumbers
*	Csak számokat ended beírni az adott inputba (mibe). Használata: <input type="text" onkeyup="onlyNumbersMobilhoz(this)" >
*
*	@param html input elem
*/
function onlyNumbersMobilhoz(value){

    ki = '';
    for(i=0;i<value.length;i++){
		if (value.substr(i,1)>='0' && value.substr(i,1)<='9'){
			ki+=value.substr(i,1);
		 }
	}

	return ki;
}

/**
*	@desc Google +1
*	Hogy a HTML valid maradjon, így egy fuggvényhívással iratjuk ki . A forrásban továbbá el kell helyetni egy ilyet (footerbe javasolt):
*   <script type="text/javascript" src="https://apis.google.com/js/plusone.js">{lang: 'hu'}</script>
*
*	@param boolean count: legyen-e ott a számláló vagy sem
*	@param string size: gomb mérete {small, medium, standard, tall}
*	@param string href: ha más oldalt szeretnénk +1-ezni
*	@param string callback: callback függvény neve (megnyomás után hívódik meg)
*/
function plus1(count, size, href){
    plusz1_html = '<g:plusone';

    if(count){
        plusz1_html += ' count="' + count + '"';
    }

    if(size){
        plusz1_html += ' size="' + size + '"';
    }

    if(href){
        plusz1_html += ' href="' + href + '"';
    }

    plusz1_html += '></g:plusone>';
    document.write(plusz1_html);
}

/**
*	@desc Popup
*	Megnyit egy új ablakot, azt középre igazítva a képernyőn. Segédfüggvénye a detect
*
*	@param string URLtoOpen: az url amit meg kell nyitni
*	@param int popwidth: ablak kívánt szélessége
*	@param int popheight: ablak kívánt magassága
*	@param boolean scroll: srollozható vagy sem az ablak
*	@param boolean resize: átméretezhető legyen-e vagy sem (csak IE_ben működik...)
*/
function detect(){
	if (navigator.appName == 'Microsoft Internet Explorer' && navigator.platform == 'MacPPC') {
		return true;
	} else {
		return false;
	}
}
function popup(URLtoOpen, popwidth, popheight, scroll, resize){
	if(scroll==undefined) scroll=0;
	if(resize==undefined) resize=0;

	var Macit = detect();
	var screenWidth = screen.availWidth; if(popwidth==-1){popwidth=screen.availWidth;}
	var screenHeight = screen.availHeight; if(popheight==-1){popwidth=screen.availHeight;}
	var x = (screenWidth/2)-(popwidth/2);
	var y = (screenHeight/2)-(popheight/2);

	if (Macit == 1) {
		popwidth2 = popwidth - 16;
		popheight2 = popheight - 16;
	} else {
		popwidth2 = popwidth;
		popheight2 = popheight;
	}
	var winParams = "height=" + popheight2 + ",width=" + popwidth2 +",resizable="+resize+",scrollbars="+scroll+",directories=0,dependent=1,toolbar=0,menubar=0,screenX=" + x + ",screenY=" + y + ",left=" + x + ",top=" + y;
	newWindow = window.open(URLtoOpen,'_blank', winParams);
}


/**
 * @desc Responsive weboldalakhoz készült függvény. A class="respo"-val ellátott képeknél a data-src-desktop, data-src-tablet és data-src-mobil
 * attribútomban megadott src-ket cserélgeti a paraméterként is megadható határoknál. Jó tanács: az alap SRC mindig a mobil legyen, hogy azt töltse be.
 *
 * @param int tablet_szel hol legyen a tablet nézet maximális szélessége
 * @param int tablet_szel hol legyen a mobil nézet maximális szélessége
 *
 */
function respo_kepek(tablet_szel, mobil_szel){

    if(!tablet_szel){ tablet_szel = '840'; }
    if(!mobil_szel) { mobil_szel  = '620'; }

    if( $(window).width() < mobil_szel ){

        $('.respo').each(function(){
            $(this).attr('src', $(this).attr('data-src-mobil'));
        });

    }else if( $(window).width() < tablet_szel ){

        $('.respo').each(function(){
            $(this).attr('src', $(this).attr('data-src-tablet'));
        });

    }else{

        $('.respo').each(function(){
            $(this).attr('src', $(this).attr('data-src-desktop'));
        });
    }
}



/**
*   @desc Szőköz szűrése a kapott sztringből
*
*   @param string az ellenőrizendő string
*/
function szokoz_szur(string){
    return string.replace(/\s/g, '');
}



/**
*   @desc SQL érték beszúrásához
*
*   @param string az ellenőrizendő string
*/
function sql_ertek(string){
    return string.replace('&', '!ES!').replace('+', '!PLUSZ!').replace('#', '!SHARP!');
}

/* Pl. egy php fál elrési útvonalakor csak az utolsó rész érdekel */
function baseName(str) {

    var base = new String(str);

    if (base.indexOf('?') != -1) {
        base = base.substring(0, base.lastIndexOf('?'));
    }
    base = base.substring(base.lastIndexOf('/') + 1);

    if (base.indexOf('.') != -1) {
        base = base.substring(0, base.indexOf('.'));
    }

    return base;
}

/* Fájl tallózásakor a fájlnak csak a neve kell. */
function baseNameFajlhoz(str){
    var base = new String(str).substring(str.lastIndexOf('\\') + 1); 
    return base;
}