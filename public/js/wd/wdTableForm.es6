
class wdTableForm {

    wTable = null ;
    wForm  = null ;

    constructor ( divid , json , id = null  )
    {
        this.TF = document.getElementById(divid)
        if ( !this.TF )
            return

        this.TF.cfg = json ;
        this.TF.innerHTML = '' ;
        if ( json?.table ) {
            this.T = document.createElement
            ('div');
            this.T.id = divid + '_T' ;
            this.T.style.display = 'none' ;
            this.TF.appendChild(this.T);
        }
        else
            this.T = null ;

        if ( json?.form ) {
            this.F = document.createElement('div');
            this.F.id = divid + '_F' ;
            this.F.style.display = 'none' ;
            this.TF.appendChild(this.F);
        }
        else
            this.F = null ;


        this.wTable = ( this.T && (typeof wdTable !== 'undefined' )) ? new wdTable( this.T.id , json , this ) : null ;
        this.wForm  = ( this.F && (typeof wdForm  !== 'undefined' )) ? new  wdForm( this.F.id , json , this ) : null ;

        if ( this.wForm && id !== null  ) {
            this.wForm.show( id ) ;
            this.showForm( id ) ;
        }
        else if ( this.wTable && id === null  ) {

        }
        else
            this.TF.innerHTML = '<div class="alert alert-danger">Loading</div>' ;

    }

    showForm( id ) {
        if ( this.T )
            this.T.style.display = 'none' ;
        if ( this.F )
            this.F.style.display = 'block' ;
    }

    showTable( ) {
        if ( this.F )
            this.F.style.display = 'none' ;
        if ( this.T )
            this.T.style.display = 'block' ;
    }


}
