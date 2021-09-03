function ktgnem_beallit() {
    $.ajax({
        url : 'erteklista.php',
        type: 'POST',
        data: {
            jogcim_recid    : $('#jogcim_recid').val(),
            ktgnem_beallit  : Date.now()
        },
        success: function (response) {
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }
                
                $('#ktgnem').val(response.ktgnem);
            }
        }
    });
}