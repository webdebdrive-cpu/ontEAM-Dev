/* Kép megjegyzésének módosításakor a gomb változó HTML kódja (hogy valid legyen az oldal, kivesszük egy JS fájlba) */
function get_tobbkep_megj_szerk_gomb(link, azon, id){
    return '<form method="get" action="" onsubmit="tobbkep_megj_atir(\'' + link + '\', $(\'#megj_atir' + azon + '\').val(), \'' + id + '\'); alert2(); return false;"><p class="c"><input disabled="disabled" style="width: 200px; margin: 3px" type="text" id="megj_atir' + azon + '" name="megj_atir' + azon + '" value="Töltés..."><br><input type="button" class="gomb" value="Mégsem" onclick="alert2()"> <input type="submit" class="gomb" value="Ok"></p></form>';
}

/* Aktuális megjegyzés lekérdezése */
function get_tobbkep_megj(link, id, input_azon){
    $.ajax({
		type : 'POST',
		url  : link,
		data : 'get_kepfelirat=' + id,
		success: function(html){
            $('#' + input_azon).removeAttr('disabled');
            $('#' + input_azon).focus();
            $('#' + input_azon).val(html);
        }
	});
}

/* Megjegyzés átírása, ajax hívás */
function tobbkep_megj_atir(link, szoveg, id){

    $.ajax({
		type: 'POST',
		url : link,
		data: 'megj_atir=1&szoveg=' + szoveg + '&id=' + id + '&tabla=' + baseName(location.href),
		success: function(html){
            $('#megj_kep' + id + '_felirat').html(html);
        }
	});

}

/* Kép törlése, ajax hívás */
function tobbkep_torol(link, id, li_id){
	$.ajax({
		type: 'POST',
		url : link,
		data: 'tobbkep_torol=' + id + '&tabla=' + baseName(location.href),
		success: function(html){
        }
	});
	$('#'+li_id).fadeOut();
}

/* Tetszőleges bejegyzés sorrendezése */
$(function(){
	$('#sorrendezes_bejegyzes').sortable({
		placeholder: 'kiemel',
		update     : function(event, ui){
            var bejegyzes_sorrend;
            $('#sorrendezes_bejegyzes li').each(function(index){
                bejegyzes_sorrend += $(this).attr('value')+';';
            });

            $.ajax({
                type: 'POST',
                url : 'esemenyek.php',
                data: 'sorrendezes=' + bejegyzes_sorrend + '&tabla=' + $('#sorrendezes_bejegyzes').attr('title'),
                success: function(html){
                }
            });
        }
	});
    /*Hogy húzáskor ne jelölödjön ki a szöveg*/
	$('#sorrendezes_bejegyzes').disableSelection();
    /*Viszont inputokba lehessen írni*/
    $('#sorrendezes_tobbkep').find('input')
        .bind('mousedown.ui-disableSelection selectstart.ui-disableSelection', function(e) {
         e.stopImmediatePropagation();
    });
});

/* Több kép sorrendezése (tobb_kep.php-t kell meghívni) */
$(function(){
	$('#sorrendezes_tobbkep').sortable({
		placeholder: 'kiemel',
		update     : function(event, ui){
            var albumok_sorrend;
            $('#sorrendezes_tobbkep li').each(function(index){
            albumok_sorrend += $(this).attr('title')+';';
            });

            $.ajax({
                type: 'POST',
                url : 'tobb_kep.php',
                data: 'kepek_sorrend=' + albumok_sorrend + '&azonosit_oszlop=' + $('#sorrendezes_tobbkep').attr('title') + '&tabla=' + baseName(location.href),
                success: function(html){
                    uj_nevek = html.split(';');
                    i = 0;
                    $('#sorrendezes_tobbkep li').each(function(index){
                        $(this).attr('title', uj_nevek[i]);
                        $(this).find('a.megnyitas').attr('href', $('#self').val() + '?album='+uj_nevek[i]);
                        i++;
                    });
                }
            });
        }
	});
    /*Hogy húzáskor ne jelölödjön ki a szöveg*/
	$('#sorrendezes_tobbkep').disableSelection();
    /*Viszont inputokba lehessen írni*/
    $('#sorrendezes_tobbkep').find('input')
        .bind('mousedown.ui-disableSelection selectstart.ui-disableSelection', function(e) {
         e.stopImmediatePropagation();
    });

});
