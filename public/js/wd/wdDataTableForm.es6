class wdDataTableForm {

    constructor(cfg, action) {

        if (!cfg )
            throw new Error('config is required');

        if ( !cfg.id )
            throw new Error('config.id is required');

        this.T0 = document.getElementById( cfg?.id);
        if (!this.T0)
            throw new Error('no table/ element found');

        this.T0.className = 'wd-data-table-form'; //


        this.wdTable = null ;
        if ( cfg?.table ) {
            if (typeof wdDataTable !== 'undefined') {
                this.wdTable = new wdDataTable('T' + cfg.id, cfg?.options, cfg.table, action );
            }
        }


        this.T0.replaceChildren() ;
        if ( this.wdTable )
            this.T0.appendChild( this.wdTable.attach() );
        // if ( this.wdForm )
        //    this.T0.appendChild( this.wdForm.render());


        /*
        this.wdForm = null ;
        if ( cfg?.form ) {
            if (typeof wdDataForm !== 'undefined') {
                this.wdForm = new wdDataForm( 'F' + cfg.id, cfg?.options, cfg.form, action);
            }
        }

         */

        //this.T0.replaceChildren() ;
        //if ( this.wdTable )
        //    this.T0.appendChild( this.wdTable.T0);



    }


}
