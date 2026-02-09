
document.addEventListener('alpine:init', () => {

    Alpine.data('pwdStrength', () => ({

        colours: [
            'bg-gray-500',
            'bg-red-500',
            'bg-orange-500',
            'bg-yellow-500',
            'bg-green-500'],

        zxcvbn : null ,
        pwdok : false ,


        init: function () {

            this.pwd1 = '' ;
            this.pwd2 = '' ;
            this.zxcvbn = null ;
            this.score = 0 ;
            this.warn1 = '' ;
            this.warn2 = '' ;
            this.pwdok = false ;
            this.touch = false ;

            import('/js/npm/zxcvbn/zxcvbn.js')
                .then(module => {
                    this.zxcvbn = module.default || module;
                }).catch(err => {
                    this.zxcvbn = null;
                    console.error('Failed to load zxcvbn:', err)
            });

        },

        checkPassword1: function() {

            if ( this.pwd1 === '' ) {
                this.score = 0;
                this.warn1 = '' ;
            }
            else if ( this.pwd1.length < 8 ) {
                this.score = 1;
                this.warn1 = 'Password must be at least 8 characters long.';
            }
            else if ( this.pwd1.length > 125 ) {
                this.score = 1;
                this.warn1 = 'Password must be less than 125 characters long.';
            }
            else if ( this.zxcvbn ) {
                let result  = this.zxcvbn.zxcvbn(this.pwd1);
                this.score = result?.score || 0 ;
                this.warn1 = result?.feedback?.warning || '' ;
            }
            else {
                this.score = 3 ;
                this.warn1 = 'unable to check password strength' ;
            }

            this.warn2 =  ( this.touch && this.pwd1 !== this.pwd2 ) ? 'Passwords do not match.' : '' ;
            this.checkOK() ;

        } ,

        checkPassword2: function() {
           this.touch = ( this.pwd2 !== '' )  ;
           this.warn2 =  ( this.touch && this.pwd1 !== this.pwd2 ) ? 'Passwords do not match.' : '' ;
           this.checkOK() ;
        } ,
        checkOK: function() {
            this.pwdok = ( this.touch && this.pwd1.length >= 8 && this.score >= 2 && this.pwd1 === this.pwd2 ) ;
        }





    }))

}) ;

