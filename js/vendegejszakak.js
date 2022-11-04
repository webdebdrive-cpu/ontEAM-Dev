// Vendégéjszakák szűrése
function vendegejszaka_ell_link_kieg() {
    var link_kieg = '';
    var ev = $('#ev').val();
    var ho = $('#ho').val();
    if( ev ) {
        if( ev<2000 || ev>2100 ) {
            alert2('Helytelen évszám!<br>Kérjük pontosítsa az évszámot!', '', 'Hiba');
            return false;
        }
        link_kieg += '&ev='+$('#ev').val();
    }
    if( ho ) {
        if( ho<1 || ho>12 ) {
            alert2('Kérjük válasszon hónapot!', '', 'Hiba');
            return false;
        }
        link_kieg += '&ho='+$('#ho').val();
    }
    if( ev && !ho ) {
        alert2('Kérjük válasszon az évszámhoz hónapot is!', '', 'Hiba');
        return false;
    }
    if( !ev && ho ) {
        alert2('Kérjük válasszon évszámot a hónaphoz!', '', 'Hiba');
        return false;
    }
    
    return link_kieg;
}