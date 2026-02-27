class wdForm {

    constructor( divid , jobj , id ) {

        if (!divid || !jobj || 'string' !== typeof divid || 'object' !== typeof jobj || id < 0 )
            throw new Error('Invalid arguments')

        this.F0 = document.getElementById(divid)
        try {
            if (this.F0 && this.F0?.tagName === 'DIV') {
                this.F0.innerHTML = '' ;
                this.cfg = jobj?.form;

                this.setupFORM() ;
                this.loadFORM( id ) ;
            } else
                throw new Error('Invalid Divid')

        } catch (e) {
            console.log(e)
            alert(e);

        }

    }

    loadFORM( id ) {
        this.id = id ;
    }

    setupFORM() {
        this.F0.innerHTML = this.cfg?.title;

    }


}