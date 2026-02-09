@extends('auth.authbase' , ['title'=>'Enter Reset Code'])
@section( 'script' )
    <script src="/js/wd/otp.min.js"></script>
@endsection
@section('form')
    <div x-ref="otpInputContainer" x-data="otpCode('{{$_POST['reset-code'] ?? ''}}')">
        <div class="form-panel">
            <div class="flex justify-between">
                <template x-for="(input, index) in 4" :key="index">
                    <input
                            type="text"
                            maxlength="1"
                            class="otpInput border border-blue-500 w-10 h-10 text-center uppercase"
                            autofocus="{ index < 1}"
                            x-on:input="handleInput($event, index)"
                            x-on:paste="handlePaste($event)"
                            x-on:keydown="validateKeyPress($event)"
                            x-on:keydown.backspace="$event.target.value || handleBackspace($event, index)"
                    />
                </template>

                <div class="flex items-center justify-center w-5 h-10">-</div>
                <template x-for="(input, index) in 4" :key="index">
                    <input
                            type="text"
                            maxlength="1"
                            class="otpInput border border-blue-500 w-10 h-10 text-center uppercase"
                            x-on:input="handleInput($event, index+4)"
                            x-on:paste="handlePaste($event)"
                            x-on:keydown="validateKeyPress($event)"
                            x-on:keydown.backspace="$event.target.value || handleBackspace($event, index+4)"
                    />
                </template>
                <input type="hidden" id="reset-code" name="reset-code" x-model="value">
            </div>

        </div>
        <div class="form-panel">
            <button type="submit" :disabled="value.length !==8" name="_code" id="_code" class="bar-button button-hover">Next</button>
            </div>
        </div>

@endsection
@section( 'form-submit' )
    <div class="form-panel">
        <a href="/auth/cancel-reset" class="text-sm underline">Cancel Reset</a>
    </div>
@endsection