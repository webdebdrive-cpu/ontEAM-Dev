function munkaido_torol(mehet) {
    if( !mehet ) {
        alert2($('#nev').val()+'<br>'+$('#napi_munkaido tr:last-of-type td:nth-of-type(1) input[type="hidden"]').val()+'<br><br>Biztos, hogy törli a napi munkaidő adatot?',
               '<input type="button" class="gomb zold" value="Igen" onclick="munkaido_torol(\'mehet\')"> <input type="button" class="gomb piros" value="Nem" onclick="alert2();">');
    } else {
        $.ajax({
            url : PHP_SELF,
            type: 'POST',
            data: {
                recid         : _get('id'),
                munkaido_torol: Date.now()
            },
            success: function (response) {
                if (response) {
                    response = JSON.parse(response);
                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        $('#'+response.focus).addClass('hibas_ide_focus');
                        return false;
                    }
                    
                    alert2();
                    $('#napi_munkaido tr:last-of-type').fadeOut();
                }
            }
        });
    }
}