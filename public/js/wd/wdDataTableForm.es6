class wdDataTableForm {

    constructor(cfg, action) {

        if (!cfg || !cfg?.id )
            throw new Error('config is required');

        this.T0 = document.getElementById( cfg?.id);
        if (!this.T0)
            throw new Error('no table/ element found');

        this.T0.className = 'wd-data-table-form'; //

        this.wdForm = new wdDataForm( cfg , action , 'c' );

        this.T0.replaceChildren() ;
        if ( this.wdForm )
            this.T0.appendChild( this.wdForm.attach(true ) );



/*

        this.actionFn = ( t , act , k , v ) => {

            if ( act === 'view' )
            {
                if ( t === 'T' && this.wdTable ) {
                    //
                    this.wdTable.detach() ;
                    if ( this.wdForm )
                        this.wdForm.attach(true);
                }

            }
            else if ( !action )
                action( t , act , k , v );
        }


        this.wdForm = null ;
        if ( cfg?.form ) {
            if ( typeof wdDataForm !== 'undefined' ) {
                this.wdForm = new wdDataForm( cfg , this.actionFn , 'c' );
            }
        }


        this.T0.replaceChildren() ;

        // if ( this.wdTable )
        //     this.T0.appendChild( this.wdTable.attach(true) );

        if ( this.wdForm )
            this.T0.appendChild( this.wdForm.attach(true ) );


        //this.T0.replaceChildren() ;
        //if ( this.wdTable )
        //    this.T0.appendChild( this.wdTable.T0);


        */
    }

}
