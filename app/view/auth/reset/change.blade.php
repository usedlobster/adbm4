@extends('layouts.auth')
@section('content')

    @include( 'snip.logo-head', ['title'=>'Change Password'] )
    @include( 'snip.error-block' , ['error'=> $error ?? false ])

    <form class="mt-8 space-y-6" action="" method="POST" x-data="_wd_adbm_resetFormData()">
        @csrf
        <input type="hidden" name="username" autocomplete="username" value="{{ $_SESSION['_reset_email'] ?? '' }}">
        <div class="rounded-md shadow-sm space-y-4">
            <!-- Password Input -->
            <div>
                <label for="password" id="password-label" class="block text-sm font-medium text-gray-700">
                    New Password
                </label>
                <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        x-model="pwd1"
                        @input.debounce.300ms="checkStrength();"
                        class="appearance-none relative block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        aria-labelledby="password-label"
                        aria-describedby="pwd-strength-feedback"
                        autocomplete="new-password"
                />
            </div>

            <!-- Password strength meter -->
            <div class="flex justify-between space-x-2 h-2">
                <template x-for="(input, index) in 5" :key="index">
                    <div class="w-full" :class="(index <= score ) ? color : 'bg-gray-500'"></div>
                </template>
            </div>

            <!-- Password Strength Feedback -->
            <div
                    id="pwd-strength-feedback"
                    role="status"
                    class="text-md text-black leading-relaxed"
                    x-text="warn"
                    x-show="warn.length > 1"
                    x-transition
            ></div>

            <!-- Confirm Password Input -->
            <div>
                <label for="password_confirm" id="password-confirm-label"
                       class="block text-sm font-medium text-gray-700">
                    Confirm Password
                </label>
                <input
                        id="password_confirm"
                        name="password_confirm"
                        type="password"
                        required
                        x-model="pwd2"
                        @input.debounce.300ms="checkConfirm();"
                        class="appearance-none relative block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none sm:text-sm"
                        :class="(touch && pwd1 !='' && (pwd1 !== pwd2)) ? 'border-red-500 focus:border-red-500 focus:ring-2 focus:ring-red-500' : ''"
                        aria-labelledby="password-confirm-label"
                        aria-describedby="pwd-confirm-error"
                        :aria-invalid="(touch && pwd1 !='' && (pwd1 !== pwd2)) ? 'true' : 'false'"
                        autocomplete="new-password"
                />
            </div>


            <!-- Confirm Password Error Message -->
            <div
                    id="pwd-confirm-error"
                    role="alert"
                    class="text-md text-red-600 leading-relaxed"
                    x-show="touch && pwd1 !='' && (pwd1 !== pwd2)"
                    x-text="'Passwords do not match'"
                    x-transition>
            </div>
        </div>

        <div class="mt-6">
            @component('comp.button',['name'=>'_change','check'=>'!isvalid'])
                Change Password
            @endcomponent
        </div>

        <div class="mt-6">
            <a href="/auth/cancel" class="text-sm text-indigo-600 hover:text-indigo-500">Cancel</a>
        </div>

        </form>
    </div>

@endsection
@section( 'script' )
    <script defer>

        function _wd_adbm_resetFormData() {

            return {
                pwd1: '',
                pwd2: '',
                touch: false,
                score: 0,
                warn: '-',
                color: 'bg-gray-500',
                isvalid: false,
                init: function () {
                    // TODO : use internal api - strength meter
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
                        this.warn = '-';
                    } else {
                        let result = this.zxcvbn.zxcvbn(this.pwd1);
                        console.log(this.pwd1, result);
                        this.warn = result.feedback.warning || result.feedback.suggestions[0];
                        this.score = result.score;
                    }

                    if (this.score >= 4) {
                        this.color = 'bg-green-500';
                        this.warn = '+';
                    } else if (this.score >= 3)
                        this.color = 'bg-yellow-500';
                    else if (this.score >= 2)
                        this.color = 'bg-orange-500';
                    else if (this.score >= 1)
                        this.color = 'bg-red-500';
                    else
                        this.color = 'bg-gray-500';

                    this.touch = false ;
                    this.checkConfirm();

                },

               checkConfirm: function() {

                    if ( this.pwd2 === '' )
                        this.touch = false ;
                    else
                       this.touch = true ;

                    this.isvalid = (
                        this.pwd1.length > 0 &&
                        this.score >= 2 &&
                        (this.pwd1 === this.pwd2) &&
                        this.pwd2.length > 0);

                }
            }
        }


    </script>
@endsection
