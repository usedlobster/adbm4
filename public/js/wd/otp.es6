document.addEventListener('alpine:init', () => {

    Alpine.data('otpCode', (v, len = 8, reg = /[1-9A-Za-z]/) => ({

        input: 0,
        otp_length: len,
        value: v || '',
        otp_regex: reg,

        get inputs() {
            return this.$refs.otpInputContainer.querySelectorAll('.otpInput');
        },

        init() {
            this.$nextTick(() => {
                this.inputs?.[0]?.focus();
                this.setInput(this.value);
            });
        } ,

        validateKeyPress(e) {
            // Handle Ctrl+V (or Cmd+V on Mac)
            if ((e.ctrlKey || e.metaKey) && e.key === 'v') {
                return;
            }

            // Allow backspace, tab, arrows
            if (['Backspace', 'Tab', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                return;
            }

            if (!this.otp_regex.test(e.key)) {
                e.preventDefault();
            }
        },

        handleInput(e, index) {
            const input = e.target.value;
            const match = input.match(this.otp_regex);
            const digit = match ? match[0] : '';
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

        setInput(v) {
            const clean = (v || '')
                .replace(new RegExp(`[^${this.otp_regex.source}]`, 'g'), '')
                .slice(0, this.otp_length);

            [...clean].forEach((char, i) => {
                if (this.inputs[i]) {
                    this.inputs[i].value = char;
                }
            });

            this.value = [...this.inputs].map(input => input.value).join('');
        },

        handlePaste(e) {
            e.preventDefault();

            const paste = (e.clipboardData.getData('text') || '')
                .split('')
                .filter(ch => {
                    this.otp_regex.lastIndex = 0;
                    return this.otp_regex.test(ch);
                })
                .join('')
                .slice(0, this.otp_length);

            this.setInput(paste);

            const nextEmptyIndex = [...this.inputs].findIndex(input => !input.value);
            const focusIndex = nextEmptyIndex === -1 ? this.otp_length - 1 : nextEmptyIndex;
            this.inputs[focusIndex]?.focus();
        },

        handleBackspace(e, index) {
            if (index > 0) {
                this.inputs[index - 1].focus();
                this.inputs[index - 1].select();
            }
        },
    }));
});;
