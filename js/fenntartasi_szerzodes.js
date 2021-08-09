function fenntartasi_szerzodes_feltolt() {
    csoportMuvelet('fenntartasi_szerzodes_feltolt');
}

// Adott szerződés áttevés projektszerződésnek
function projekt_szerzodes(mehet) {
    if( !mehet ) {
        alert2('Biztosan áthelyezi ezt a szerződést projekt szerződésnek?', '<input type="button" class="gomb zold enter" value="Igen" onclick="projekt_szerzodes(\'mehet\'); alert2();"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">');
        return false;
    }
    
    $.ajax({
        url : 'fenntartasi_szerzodes.php',
        type: 'POST',
        data: {
            andoc_id         : _get('andoc_id'),
            projekt_szerzodes: Date.now()
        },
        success: function (response) {
            formModositva = false;
            location.href = 'fenntartasi_szerzodes.php';
        }
    });
    
}