@extends('layout.login')
@section( 'script' )
    <script defer>

        function resetFormData() {

            return {
                pwd1: '',
                pwd2: '',
                score: 0,
                warn: '-' ,
                color : 'bg-gray-500' ,
                isvalid : false ,
                init: function () {
                    import('/js/npm/zxcvbn/zxcvbn.js')
                        .then(module => {
                            this.zxcvbn = module.default || module;

                        })
                        .catch(err => {
                            this.zxcvbn = null;
                            console.error('Failed to load zxcvbn:', err)
                        });


                },
                checkStrength: function () {

                    if (this.pwd1.length < 2) {
                        this.score = 0;
                        this.warn = '-' ;
                    }
                    else {
                        let result = this.zxcvbn.zxcvbn(this.pwd1);
                        console.log( this.pwd1 , result ) ;
                        this.warn = result.feedback.warning || result.feedback.suggestions[0];
                        this.score = result.score;
                    }

                    if ( this.score >= 4 ) {
                        this.color = 'bg-green-500';
                        this.warn = '-' ;
                    }
                    else if ( this.score >= 3 )
                        this.color= 'bg-yellow-500' ;
                    else if ( this.score >= 2 )
                        this.color= 'bg-orange-500' ;
                    else if ( this.score >= 1 )
                        this.color= 'bg-red-500' ;
                    else
                        this.color = 'bg-gray-500' ;



                    this.checkConfirm() ;

                },

                checkConfirm: function ( ) {

                    this.isvalid =  (
                        this.pwd1.length >= 6 &&
                        this.score >=3 &&
                        (this.pwd1 === this.pwd2) &&
                        this.pwd1.length >= 0 ) ;

                }
            }
        }


    </script>
@endsection
@section('content')

    <div class="max-w-md w-full space-y-4">

        @include( 'component.logo-head'   , ['title'=>'Change Password'] )
        @include( 'component.error-block' , ['error'=> $error ])

        <form class="mt-8 space-y-6" action="" method="POST">
            <input hidden id="username" autocomplete="username" name="username" value="{{ $email }}" >
            @csrf
            <div class="rounded-md shadow-sm space-y-2 " x-data="resetFormData()">

                <div>
                    <label for="password1" class="block text-sm font-medium text-gray-700">New password</label>
                    <input id="password1" name="password1" type="password"
                           x-model="pwd1"
                           autocomplete="new-password"
                           maxlength="128"
                           @input.debounce.250="checkStrength($event.target.value)"
                           class="mt-1 block w-full rounded-md"
                           placeholder="Enter New Password">
                </div>
                <div class="flex justify-between space-x-2 h-2">
                    <template x-for="(input, index) in 5" :key="index">
                        <div class="w-full" :class="(index <= score ) ? color : 'bg-gray-500'"></div>
                    </template>
                </div>
                <div>
                    <div class="text-xs text-blue-500" x-text="warn"></div>
                </div>

                <div>
                    <label for="password2" class="block text-sm font-medium text-gray-700">Confirm password</label>
                    <input id="password2" name="password2" type="password"
                           x-model="pwd2"
                           autocomplete="new-password"
                           @input.debounce.200="checkConfirm()"
                           class="mt-1 block w-full rounded-md"
                           placeholder="Confirm Password">
                </div>


                <div>
                    @component('component.button' , ['name'=>'_change','check'=>'!isvalid'])
                        Change Password
                    @endcomponent
                </div>
                <div>
                    <a href="/auth/cancel" class="text-sm text-indigo-600 hover:text-indigo-500">Cancel</a>
                </div>
            </div>
        </form>

    </div>

@endsection