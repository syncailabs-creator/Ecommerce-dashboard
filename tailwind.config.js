import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50: '#f4f8f7',
                    100: '#e2efec',
                    200: '#c6ded9',
                    300: '#9ec5be',
                    400: '#72a59e',
                    500: '#457975', // Dr Veda Base Teal
                    600: '#36605d',
                    700: '#2d4d4b',
                    800: '#273f3d',
                    900: '#233534',
                    950: '#111f1f',
                },
                accent: {
                    DEFAULT: '#F4D860', // Dr Veda Gold
                    500: '#F4D860',
                    600: '#d9ba40',
                },
            },
        },
    },

    plugins: [forms],
};
