/* 
    Ihleti szerzője: Sándor Ádám 2016.09.12 Köszönjük szépen!
    
    Használata:
    scrollto('#id', 1000);
    1. paraméter: egy elem ID-ja ahová gördülni szeretnénk (működik class névvel is)
    2. paraméter: a scroll sebessége 
    3. paraméter: offset. Ebben az esetben a target-hez képest adott px-el eltolva áll meg.
*/
scrollto = function( target, duration, offset ) {
    if( target !== undefined && target !== 0 ) {
        if( target === 'top' ) {
           $( 'html, body' ).animate({scrollTop: 0}, duration, 'easeInOutExpo');
        } else if( target === 'bottom' ) {
            $( 'html, body' ).animate({scrollTop: $(document).height()}, duration, 'easeInOutExpo');
        } else {
            
            var position = $( target ).position().top;
            
            if( offset === undefined ) {
                offset = 0;
            }
            
            offset = position + offset;
            
            $( 'html, body' ).animate({scrollTop: offset}, duration, 'easeInOutExpo');
        }
    }
};