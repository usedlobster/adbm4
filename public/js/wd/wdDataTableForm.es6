
class wdDataTableForm
{

    constructor( cfg   ) {

        if ( !cfg || !cfg.id )
            throw new Error('config is missing or invalid ');

        this.TFC = document.getElementById( cfg.id )
        if (!this.TFC )
            throw new Error('container not found' ) ;

        this.TFC.replaceChildren() ;
        this.TFC.className = 'wd-data-table-form' ;


        // create the table if we have config for one
        this.wdTable = (cfg?.table && (typeof wdDataTable   !== 'undefined')) ? new wdDataTable( cfg  ) : null;










    }

}
