class wdModal {


    constructor ( tempID ) {
        this.tempID = tempID;
    }

    /*
    function _wd_modal_keydown ( e ) {
        if ( e.key === 'Escape' ) {
            _wd_close_modal( document.querySelector('.wd-modal-content') );
        }
    }

    function _wd_modal_click ( e ) {
        const M = document.querySelector('.wd-modal-content');
        if ( M && M.parentElement === e.target )
            _wd_close_modal( M );
    }

    function _wd_open_modal ( modal , tempID , actionFn ) {


        if ( !modal || !( modal instanceof HTMLDivElement) || 'function' !== typeof actionFn )
            return false ;

        // get id of template
        const tempnode = document.getElementById( tempID);
        if (!tempnode)
            throw new Error('template not found : ' + tempID);

        const frag = tempnode.content.cloneNode(true);
        if (!frag)
            throw new Error('failed to find clone template : ' + tempID);


        modal.replaceChildren();
        const M = document.createElement('div');
        if ( !M || !(M instanceof HTMLDivElement))
            return false ;

        M.className = 'wd-modal-content';
        M.style.display = 'block';
        actionFn( 'attach' , M , frag) ;
        modal.style.display = 'block';

        document.body.style.overflow = 'hidden';
        document.addEventListener( 'keydown', _wd_modal_keydown ) ;
        document.addEventListener( 'click' , _wd_modal_click ) ;


        modal.appendChild(M);
        return true ;

    }

    function _wd_close_modal ( M ) {

        if ( !M || !( M instanceof HTMLDivElement))
            return false ;
        modal = M.parentElement;
        if ( !modal || !( modal instanceof HTMLDivElement))
            return false ;
        if (M)
            M.remove();
        modal.replaceChildren();
        modal.style.display = 'none';
        document.removeEventListener( 'click' , _wd_modal_click);
        document.removeEventListener( 'keydown' , _wd_modal_keydown);
        document.body.style.overflow = '';
    }




    getTableDefs( col  , colorder) {

        let tbldefs = [] ;
        for ( let k = 0 ; k < colorder.length ; k++ )
        {

            let cdef = col[colorder[k]] ;

            let up = -1 ;
            let down = -1 ;
            if (( cdef?.moveable ?? false ) === true ) {
                for (let j = k - 1; j >= 0; j--)
                    if (col[colorder[j]]?.moveable ?? false) {
                        up = j ;
                        break;
                    }

                for (let j = k + 1; j < colorder.length; j++)
                    if (col[colorder[j]]?.moveable ?? false) {
                        down = j ;
                        break;
                    }
            }

            let o = {
                name       : cdef?.title || cdef?.field || '' ,
                vis        : cdef?.visible ?? true ,
                exp        : cdef?.exportable ?? true ,
                width      : cdef?.width ?? 5 ,
                moveable   : cdef?.moveable ?? false ,
                renameable : cdef?.renameable ?? false ,
                up , down
            }

            console.log( 'o' , o )
            tbldefs.push( o ) ;

        }

        return tbldefs;


    }


    openModal() {


        const refreshAlpine = ( M  , col = null , colorder = null ) => {
            let d = Alpine.$data(M);
            if ( !d )
                return ;

            d.data = {
                title: this.cfg?.title ,
                defs : this.getTableDefs( d )
            };

            console.log( 'refreshAlpine' , d.data )

        }



        _wd_open_modal( this.MODAL , 'table_modal' , (act,k,v) => {
            switch ( act ) {
                case 'attach': {
                    const xdata = "{ data: {} , init() { return this.$root.actionFn( 'init' ) } , action( t , k , v ) { this.$root.actionFn(t,k,v)} }";
                    k.actionFn = (t, k, v) => {
                        console.log(t, k, v);
                        const M = this.MODAL.firstChild;
                        if (M) switch (t) {
                            case 'init' :
                                refreshAlpine(M, this.col, this.colorder);
                                break;
                            case 'up' : {
                                // if ( v?.up >=0 ) {
                                //     // we need to move k to  v.up
                                //     if ( k !== v?.up ) {
                                //         let tmp = this.colorder[k] ;
                                //         this.colorder[k] = this.colorder[v?.up] ;
                                //         this.colorder[v?.up] = tmp ;
                                //         this.fetchPage() ;
                                //         refreshAlpine( M ) ;
                                //     }
                                // }
                            }
                                break;
                            case 'down' : {

                                // if ( v?.down >=0 ) {
                                //     if ( k !== v?.down ) {
                                //         let tmp = this.colorder[k] ;
                                //         this.colorder[k] = this.colorder[v?.down] ;
                                //         this.colorder[v?.down] = tmp ;
                                //         this.fetchPage() ;
                                //         refreshAlpine( M ) ;
                                //
                                //     }

                            }
                                break;

                            case 'close' :
                                _wd_close_modal(M);
                                break;
                        }
                    }
                    k.setAttribute('x-data', xdata);
                    k.appendChild(v);
                } break ; // end attach

            }

        } )        ;
    }


     */

}