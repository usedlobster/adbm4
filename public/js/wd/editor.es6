
function _wd_saveEditorsToForm( form ) {
    const _editors = document.querySelectorAll('div[data-editor]');
    _editors.forEach( q => {

        const id = q.getAttribute('data-editor') ;
        if ( id ) {
            console.log( 'id :' , id )
        }

    }) ;

}
