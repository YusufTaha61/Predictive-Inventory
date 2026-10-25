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
                sans: ['"IBM Plex Sans"', ...defaultTheme.fontFamily.sans],
                display: ['"Bricolage Grotesque"', '"IBM Plex Sans"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Koyu orman yeşili: kenar menü, başlıklar, satış
                ink: {
                    DEFAULT: '#14352b',
                    soft: '#3d5a50',
                    muted: '#6b8279',
                },
                // Ana renk: yaprak yeşili (butonlar, linkler, seçili menü)
                leaf: {
                    50: '#eef7f1',
                    100: '#d6ecdd',
                    200: '#aed8bd',
                    300: '#7fbe97',
                    400: '#52a271',
                    500: '#2f7d4f',
                    600: '#256a42',
                    700: '#1e5636',
                    800: '#18442b',
                    900: '#123422',
                },
                // Fire / hata: domates kırmızısı
                tomato: {
                    50: '#fdf0ed',
                    100: '#fadbd3',
                    200: '#f3b1a1',
                    500: '#d9472b',
                    600: '#bf3a20',
                    700: '#9c2f1a',
                },
                // Kampanya: hardal sarısı
                mustard: {
                    50: '#fdf7e6',
                    100: '#faebc2',
                    400: '#e9b844',
                    500: '#e0a526',
                    700: '#8a6210',
                },
                // Sayfa zemini ve çizgiler
                canvas: '#f2f5f3',
                line: '#dfe6e2',
            },
            boxShadow: {
                card: '0 1px 2px rgba(20, 53, 43, 0.06)',
                lift: '0 12px 32px -12px rgba(20, 53, 43, 0.25)',
            },
        },
    },

    plugins: [forms],
};
