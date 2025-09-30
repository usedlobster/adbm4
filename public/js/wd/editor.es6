function _wd_postEditorsToForm(form,m) {
    const _editors = document.querySelectorAll('textarea[data-editor]');
    try {
        _editors.forEach(q => {
            const id = q.getAttribute('data-editor');
            if (id) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_editor_content' + m + '[]';
                input.value = JSON.stringify({ id , content : q.value })
                form.appendChild(input);
            }
        });
    } catch (e) {
        console.error(e);
    }

}

