function _wd_appendEditorsToForm(form,m) {
    const _blocks = document.querySelectorAll('div[data-edit-block]');
    try {
        _blocks.forEach( b => {
            const _editors = b.querySelectorAll('textarea[data-editor]');
            _editors.forEach(q => {
                const id = q.getAttribute('data-editor');
                if (id) {
                    let change  = Alpine?.$data( b )?.change || false ;
                    if ( change ) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = '_editor_content' + m + '[]';
                        input.value = JSON.stringify({id, content: q.value})
                        form.appendChild(input);
                    }
                }
            });
        });
    } catch (e) {
        console.error(e);
    }

}

