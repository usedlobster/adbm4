
class wdTableForm {

    constructor ( divid , json , id )
    {
        this.TF = document.getElementById(divid)
        if ( !this.TF )
            return

        this.TF.innerHTML = '' ;
        if ( json?.table ) {
            this.T = document.createElement('div');
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




        this.TABLE = ( this.T && (typeof wdTable !== 'undefined' )) ? new wdTable( this.T.id , json )   : null ;
        this.FORM  = ( this.F && (typeof wdForm !== 'undefined' )) ? new wdForm( this.F.id , json , id ) : null ;
















    }

}

