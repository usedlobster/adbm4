@extends('layouts.auth')
@section('content')

    @include( 'snip.logo-head', ['title'=>'Reset Password'] )
    @include( 'snip.error-block' , ['error'=> $error ?? false ])

    @include( 'snip.info-block' , [ 'info'=> 'Please enter your reset code, you recently received' ] )

    <form class="mt-8 space-y-6" action="#" method="POST" x-data="otpForm()">
        @csrf
        <div class="rounded-md shadow-sm space-y-4">
            <div>
                <div class="py-6 px-6 w-128 mx-auto text-center my-6">
                    <div>
                        <div class="flex justify-between" x-ref="otpInputContainer">
                            <label for="reset-code" class="block text-sm font-medium text-gray-700"></label>
                            <template x-for="(input, index) in 4" :key="index">
                                <input
                                        type="text"
                                        maxlength="1"
                                        class="otpInput border border-blue-500 w-9 h-9 text-center uppercase"
                                        autofocus="{index < 1}"
                                        x-on:input="handleInput($event, index)"
                                        x-on:paste="handlePaste($event)"
                                        x-on:keydown="validateKeyPress($event)"
                                        x-on:keydown.backspace="$event.target.value || handleBackspace($event, index)"
                                />
                            </template>
                            <div class="flex items-center justify-center w-9 h-9">-</div>

                            <template x-for="(input, index) in 4" :key="index">
                                <input
                                        type="text"
                                        maxlength="1"
                                        class="otpInput border border-blue-500 w-9 h-9 text-center uppercase"
                                        x-on:input="handleInput($event, index+4)"
                                        x-on:paste="handlePaste($event)"
                                        x-on:keydown="validateKeyPress($event)"
                                        x-on:keydown.backspace="$event.target.value || handleBackspace($event, index+4)"
                                />

                            </template>
                        </div>
                        <input type="hidden" id="reset-code" name="otp" x-model="value">
                    </div>
                </div>
                <script defer>
                    function otpForm() {
                        return {
                            length: 8,
                            value: "",
                            get inputs() {
                                return this.$refs.otpInputContainer.querySelectorAll('.otpInput');
                            },

                            validateKeyPress(e) {
                                // Handle Ctrl+V (or Cmd+V on Mac)
                                if ((e.ctrlKey || e.metaKey) && e.key === 'v') {
                                    return; // Let the paste event handler deal with it
                                }

                                // Allow backspace, tab, arrows
                                if (['Backspace', 'Tab', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                                    return;
                                }
                                // Only allow number
                                if (!/^[1-9A-Da-d]$/.test(e.key)) {
                                    e.preventDefault();
                                }
                            },

                            handleInput(e, index) {
                                // Validate numeric input
                                const input = e.target.value;
                                const digit = input.match(/[1-9A-Da-d]/) ? input : '';
                                e.target.value = digit;

                                const inputValues = [...this.inputs].map(input => input.value);
                                this.value = inputValues.join('');

                                if (digit) {
                                    const nextInput = this.inputs[index + 1];
                                    if (nextInput) {
                                        nextInput.focus();
                                        nextInput.select();
                                    }
                                }
                            },

                            handlePaste(e) {
                                e.preventDefault();
                                const paste = e.clipboardData.getData('text')
                                    .replace(/[^1-9A-Da-d]/g, '') // Only keep numbers
                                    .slice(0, this.length);  // Limit to 8 digits

                                // Fill in the inputs
                                [...paste].forEach((char, i) => {
                                    if (this.inputs[i]) {
                                        this.inputs[i].value = char;
                                    }
                                });

                                // Update the hidden input value
                                this.value = [...this.inputs].map(input => input.value).join('');

                                // Focus the next empty input or the last filled one
                                const nextEmptyIndex = [...this.inputs].findIndex(input => !input.value);
                                const focusIndex = nextEmptyIndex === -1 ? this.length - 1 : nextEmptyIndex;
                                this.inputs[focusIndex]?.focus();
                            },

                            handleBackspace(e, index) {
                                if (index > 0) {
                                    this.inputs[index - 1].focus();
                                    this.inputs[index - 1].select();
                                }
                            }
                        };
                    }
                </script>

            </div>

        </div>

        <div>
            @component('comp.button',['name'=>'_verify','check'=>'value.length !=8 '])
                Change Password
            @endcomponent
        </div>


        <div>
            <a href="/auth/cancel" class="text-sm text-indigo-600 hover:text-indigo-500">Cancel</a>
        </div>


    </form>

@endsection