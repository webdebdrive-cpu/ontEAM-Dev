// Vendégéjszakák szűrése
function vendegejszaka_szur() {
    var ev = $('#ev').val();
    var ho = $('#ho').val();
    if( ev<2000 || ev>2100 ) {
        alert2('Helytelen évszám!<br>Kérjük pontosítsa az évszámot!', '', 'Hiba');
        return false;
    }
    if( ho<1 || ho>12 ) {
        alert2('Kérjük válasszon hónapot!', '', 'Hiba');
        return false;
    }
    
    var link_kieg = '';
    if( _get('mezo') ) {
        link_kieg += '&mezo='+_get('mezo')+'&op='+_get('op')+'&felt='+_get('felt');
    }
    location.href = 'vendegejszakak.php?id=1&tablazat&ev='+ev+'&ho='+ho+link_kieg;
}