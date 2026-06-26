hugerte.init({
    relative_urls: true,
    // remove_script_host: true,
    //document_base_url: 'https://www.adbm.co/',
    // imagetools_cors_hosts: [ 'https://www.adbm.co' , 'https://:adbm.co' ],
    selector: '.syseditor',
    inline: true,
    //force_br_newlines : false,
    //force_p_newlines : false,
    //forced_root_block : '',
    // images_upload_handler: __image_upload_handler ,
    //plugins: [ 'image' , 'table' , 'lists' ,'link', 'code' , 'save'  ],
    plugins: [ 'autosave','code','save'] ,
    //autosave_ask_before_unload: true,
    //autosave_interval: '20s',
    //autosave_retention: '20m',
    //contextmenu: 'link image table',

    save_enablewhendirty: true,
    //'remove_trailing_brs': false,
    //toolbar: 'undo redo | styleselect | forecolor backcolor | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | table link image code | cancel save',

    toolbar: 'undo redo | bold italic underline| code | cancel save ' ,
    width:"auto",
});

