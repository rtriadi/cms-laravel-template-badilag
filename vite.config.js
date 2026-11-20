import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { google } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/css/public.css', 'resources/js/app.js', 'resources/js/public.js'],
            refresh: true,
            fonts: [
                google('Source Serif 4', {
                    weights: [600, 700],
                    preload: [{ weight: 700 }],
                    optimizedFallbacks: false,
                }),
                google('Plus Jakarta Sans', {
                    weights: [400, 500, 600, 700],
                    preload: [{ weight: 400 }, { weight: 600 }],
                    optimizedFallbacks: false,
                }),
                google('IBM Plex Mono', {
                    weights: [400, 500, 600],
                    preload: false,
                    optimizedFallbacks: false,
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
