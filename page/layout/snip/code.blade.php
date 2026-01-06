<div class="relative">
    <div class="relative flex items-center space-x-2 ">
        <div class="py-2 px-6 w-128 mx-auto text-center my-2">
            <div class="">
                <div class="flex justify-between" x-ref="otpInputContainer">
                   <template x-for="(input, index) in 4" :key="index">
                        <input
                                type="text"
                                maxlength="1"
                                class="otpInput border border-blue-500 w-9 h-9 text-center uppercase m-1"
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
                                class="otpInput border border-blue-500 w-9 h-9 text-center uppercase m-1"
                                x-on:input="handleInput($event, index+4)"
                                x-on:paste="handlePaste($event)"
                                x-on:keydown="validateKeyPress($event)"
                                x-on:keydown.backspace="$event.target.value || handleBackspace($event, index+4)"
                        />
                    </template>
                </div>
                <input type="hidden" id="{{ $id }}" name="{{$id}}" x-model="value">

            </div>
        </div>
    </div>
</div>
<script defer>
    function otpInput() {
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
                // Only allow valid digits
                if (!/^[1-9A-Ha-h]$/.test(e.key)) {
                    e.preventDefault();
                }
            },

            handleInput(e, index) {
                // Validate numeric input
                const input = e.target.value;
                const digit = input.match(/[1-9A-Ha-h]/) ? input : '';
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
                    .replace(/[^1-9A-Ha-h]/g, '') // Only keep numbers
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