const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './**/*.blade.php',
        './**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                transparent: 'transparent',
                current: 'currentColor',
                'main': '#495057',
                'dark': '#121212',
                'light': '#dddddd',
                'primary': {
                    900: '#444809',
                    800: '#5B610C',
                    700: '#72790E',
                    600: '#899211',
                    500: '#A0AA14',
                    400: '#B7C317',
                    300: '#CEDB1A',
                    200: '#E2E976',
                    100: '#F5F8D1',
                },
            },
        },
    },

    plugins: [
        require('@tailwindcss/forms')
    ],
};
