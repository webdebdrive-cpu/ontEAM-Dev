/* Kép megjegyzésének módosításakor a gomb változó HTML kódja (hogy valid legyen az oldal, kivesszük egy JS fájlba) */
function get_tobbkep_megj_szerk_gomb(link, azon, id){
    return '<form method="get" action="" onsubmit="tobbkep_megj_atir(\'' + link + '\', $(\'#megj_atir' + azon + '\').val(), \'' + id + '\'); alert2(); return false;"><p class="c"><input disabled="disabled" style="width: 200px; margin: 3px" type="text" id="megj_atir' + azon + '" name="megj_atir' + azon + '" value="Töltés..."><br><input type="button" class="gomb" value="Mégsem" onclick="alert2()"> <input type="submit" class="gomb" value="Ok"></p></form>';
}

/* Aktuális megjegyzés lekérdezése */
function get_tobbkep_megj(link, id, input_azon){
    $.ajax({
		type : 'POST',
		url  : link,
		data : 'get_kepfelirat=' + id + '&tabla=' + baseName(location.href),
		success: function(html){
            if(html.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            $('#' + input_azon).removeAttr('disabled');
            $('#' + input_azon).focus();
            $('#' + input_azon).val(html);
        },
        complete: function(xhr) {
            if( xhr.status != 200 ) {
                alert2(xhr.responseText, '', 'Hiba');
            }
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
            if(html.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            $('#megj_kep' + id + '_felirat').html(html);
        },
        complete: function(xhr) {
            if( xhr.status != 200 ) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
	});

}

/* Kép törlése, ajax hívás */
function tobbkep_torol(link, id, li_id){
	$.ajax({
		type: 'POST',
		url : link,
		data: 'tobbkep_torol=' + id + '&tabla=' + baseName(location.href),
		success: function(response){
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
        },
        complete: function(xhr) {
            if( xhr.status != 200 ) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
	});
	$('#'+li_id).fadeOut();
}
