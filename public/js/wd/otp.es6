document.addEventListener('alpine:init', () => {

  Alpine.data('otpCode', (v) => ({

    input: 0,
    otp_length: 8,
    value: v || '',

    get inputs() {
      return this.$refs.otpInputContainer.querySelectorAll('.otpInput');
    },

    init() {

      setTimeout(() => {
        this.setInput(this.value);
      }, 10);

    },

    validateKeyPress(e) {
      // Handle Ctrl+V (or Cmd+V on Mac)
      if ((e.ctrlKey || e.metaKey) && e.key === 'v') {
        return; // Let the paste event handler deal with it
      }

      // Allow bvackspace, tab, arrows
      if (['Backspace', 'Tab', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
        return;
      }
      //
      if (!/^[1-9A-Za-z]$/.test(e.key)) {
        e.preventDefault();
      }
    },
    handleInput(e, index) {
      // Validate numeric input
      const input = e.target.value;
      const digit = input.match(/[1-9A-Za-z]/) ? input : '';
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

      const paste = v.replace(/[^1-9A-Za-z]/g, '').slice(0, this.otp_length);

      // Fill in the inputs
      [...paste].forEach((char, i) => {
        if (this.inputs[i]) {
          this.inputs[i].value = char;
        }
      });

      // Update the hidden input value
      this.value = [...this.inputs].map(input => input.value).join('');

    }
    ,

    handlePaste(e) {
      e.preventDefault();
      const paste = e.clipboardData.getData('text').replace(/[^1-9A-Za-z]/g, '').slice(0, this.otp_length);
      this.setInput(paste);
      // Focus the next empty input or the last filled one
      const nextEmptyIndex = [...this.inputs].findIndex(input => !input.value);
      const focusIndex = nextEmptyIndex === -1 ? this.otp_length - 1 : nextEmptyIndex;
      this.inputs[focusIndex]?.focus();

    }
    ,

    handleBackspace(e, index) {
      if (index > 0) {
        this.inputs[index - 1].focus();
        this.inputs[index - 1].select();
      }
    },
  }));
});
