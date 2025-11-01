const plugin = require('tailwindcss/plugin');

module.exports = {
    content: [
        './**/*.blade.php' ,
        './**/*.php',
        'public/js/wd/**/*.min.js'
    ],
    darkMode: [ 'selector' ],
    theme: {
        extend: {
            fontFamily: {
                'roboto': ['Roboto', 'sans-serif'],
            },
            fontSize: {
                  xs   : ['0.750rem', { lineHeight: '1.50' }],
                  sm   : ['0.875rem', { lineHeight: '1.60' }],
                base : ['1,000rem', { lineHeight: '1.50', letterSpacing: '-0.01em' }],
                  lg : ['1.125rem', { lineHeight: '1.50', letterSpacing: '-0.01em' }],
                  xl : ['1.250rem', { lineHeight: '1.40', letterSpacing: '-0.01em' }],
                '2xl': ['1.500rem', { lineHeight: '1.33', letterSpacing: '-0.01em' }],
                '3xl': ['1.888rem', { lineHeight: '1.33', letterSpacing: '-0.01em' }],
                '4xl': ['2.250rem', { lineHeight: '1.25', letterSpacing: '-0.02em' }],
                '5xl': ['3.000rem', { lineHeight: '1.25', letterSpacing: '-0.02em' }],
                '6xl': ['3.750rem', { lineHeight: '1.20', letterSpacing: '-0.02em' }],
            },
            screens: {
                mobile : '480px',
            },
            minWidth : {
                15 : '3.75rem' ,
                18 : '4.5rem',
                30 : '7.5rem'
            },
            maxWidth: {
                384 : '96rem' ,
            }
        }
    },
    plugins: [
        // eslint-disable-next-line global-require
        require('@tailwindcss/forms'),

        // add custom variant for expanding sidebar
        plugin(({ addVariant, e }) => {
            addVariant('sidebar-expanded', ({ modifySelectors, separator }) => {
                modifySelectors(({ className }) => `.sidebar-expanded .${e(`sidebar-expanded${separator}${className}`)}`);
            });
        }),
    ],
};
