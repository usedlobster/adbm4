@extends('layout.auth.base')

@section( 'meta-head' )
    <meta name="description" content="Request a password reset">
@endsection
@section('content')

    @include( 'layout.logo.head', ['title'=>'Change Password'] )
    @component('layout.snip.info-block' , ['type'=>'error' ])
        {!! $errormsg !!}
    @endcomponent
    <form class="wd-adbm-form" action="#" method="POST">

        @csrf
        <div style="display:none">
            <input type="text" name="_username" value="{{ $_SESSION[ '_reset_user' ] ?? '' }}" autocomplete="username" tabindex="-1" aria-hidden="true">
        </div>
        <div class=" mb-2" x-data="pwdInput()">

            <div class="relative mb-2">
                <label for="_pwd1" class="wd-label">New Password</label>
                <input class="wd-input"
                       type="password"
                       name="_pwd1"
                       id="_pwd1"
                       maxlength="128"
                       autocomplete="new-password"
                       x-model="pwd1" @input.debounce.200ms="checkStrength();"/>
            </div>

            <div class="flex justify-between space-x-2 h-2" id="_meterbar">
                <template x-for="(input, index) in 5" :key="index">
                    <div class="w-full border" :class="(index<=score ? ( score >= 3 ? 'bg-green-500' : 'bg-blue-500') : 'bg-gray-50')"></div>
                </template>
                <div class="text-xs" x-text="((score < 3 ) ? '✕' : '✓')"></div>
            </div>
            <div class="text-xs" x-show="(score<3 && pwd1 !='')" x-text="warn"></div>

            <div class="relative mb-2">
                <label for="_pwd1" class="wd-label">Confirm Password   <span x-show="pwd2 != ''" :class="pwd1 === pwd2 ? 'text-green-500' : 'text-red-500'" class="text-xs">
                            <span x-text="pwd1 === pwd2 ? 'Match ✓' : 'No match ✕'"></span>
                        </span></label>
                <input
                       class="wd-input"
                       type="password"
                       name="_pwd2"
                       autocomplete="new-password"
                       maxlength="128"
                       id="_pwd2"
                       x-model="pwd2"
                       @input.debounce.200ms="checkStrength();"/>
            </div>

            <div class="my-4">
                <button
                        type="submit"
                        name="cpw_submit"
                        id="cpw_submit"
                        :disabled="( pwd1 == '' || pwd1 !== pwd2 || score < 3 )"
                        class="bar-button transition-all duration-200"
                        :class="( pwd1 !== '' && pwd1 === pwd2 && score >= 3 ) ? 'button-hover cursor-pointer opacity-100' : 'opacity-50 cursor-not-allowed'"
                >
                    Confirm
                </button>
            </div>


            <div class="border-blue-500">
                <a href="/auth/cancel" class="wd-form-small-button">Cancel</a>
            </div>


        </div>


    </form>

    <script defer>
        function pwdInput() {
            return {
                pwd1: '',
                pwd2: '',
                score: -1,
                warn: '',

                init: function () {

                    import('/js/npm/zxcvbn/zxcvbn.js')
                        .then(module => {
                            this.zxcvbn = module.default || module;
                        })
                        .catch(err => {
                            this.zxcvbn = null;
                        });


                },

                get isValid() {
                    return this.pwd1 !== '' && this.pwd1 === this.pwd2 && this.score >= 3;
                },

                checkStrength: function () {

                    const p = this.pwd1;

                    // 1. Minimum length (Fastest)
                    if (p.length < 3) {
                        this.score = 0;
                        this.warn = 'too short';
                        return;
                    } else if ( p.length > 128 )
                    {
                        this.score = 0 ;
                        this.warn = 'too long';
                        return ;
                    }

                    // 2. Specific Security Risk (Specific feedback)
                    if (p.includes('-') && /^[A-H1-9]{4}-[A-H1-9]{4}$/i.test(p)) {
                        this.score = 0;
                        this.warn = 'cannot use reset code';
                        return;
                    }

                    // 3. Case Requirements (Ensures letters are actually present)
                    if ( !/[A-Z]/.test(p) || !/[a-z]/.test(p)) {
                        this.score = 1;
                        this.warn = 'mix upper and lower case';
                        return;
                    }

                    if ( this.zxcvbn ) {
                        let result = this.zxcvbn.zxcvbn(p);
                        this.warn = result.feedback.warning || result.feedback.suggestions[0];
                        this.score = result.score;
                    } else {
                        // Fallback: if the library isn't there, but they passed basic checks
                        this.score = 3;
                        this.warn = 'checking...';
                    }
                }
            }
        }
    </script>

@endsection