@extends('auth.authbase' , ['title'=>'Change Password'])
@section( 'script' )
    <script src="/js/wd/pwdstr.min.js?q={!! _BUILD !!}"></script>
@endsection
@section('form')
    <div x-data="pwdStrength">
        <div class="form-panel">
            <label for="password1" id="password-label1" class="wd-label">New Password</label>
            <input type="password"
                   id="password1"
                   name="password1"
                   class="wd-input"
                   :class="warn1 ? 'wd-input-error' : '' "
                   x-model="pwd1"
                   @input.debounce.150ms="checkPassword1()"
                   placeholder="Enter Password">
            <div class="flex justify-between space-x-1 h-2 mt-1" x-show="zxcvbn">
                <template x-for="(input,index) in 4" :key="index">
                    <div class="w-full" :class="(index < (score || 0 ) ) ? colours[score] : 'bg-gray-500'"></div>
                </template>
            </div>



            <div x-show="warn1" class="mt-1">
                <span x-transition x-text="warn1"></span>
            </div>

        </div>
        <div class="form-panel">
            <label for="password2" id="password-label2" class="wd-label">Confirm Password</label>
            <input type="password"
                   id="password2"
                   name="password2"
                   class="wd-input"
                   :class="warn2 ? 'wd-input-error' : '' "
                   x-model="pwd2"
                   @input.debounce.150ms="checkPassword2()"
                   placeholder="Enter Password">
            <div x-show="warn2" class="mt-1">
                <span x-transition x-text="warn2"></span>
            </div>
        </div>
        <div class="form-panel">
            <button type="submit" :disabled="!pwdok" name="_change" id="_change" class="bar-button button-hover">Next</button>
        </div>

    </div>

@endsection
@section( 'form-submit' )

    <div class="form-panel">
        <a href="/auth/signout" class="text-sm underline">Cancel</a>
    </div>
@endsection