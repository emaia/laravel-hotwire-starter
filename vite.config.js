import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import { fileURLToPath } from 'node:url';

function devServer(mode) {
    const { APP_URL } = loadEnv(mode, process.cwd(), 'APP_URL');

    if (!APP_URL) {
        throw new Error('Set APP_URL in .env before starting the Vite development server.');
    }

    const appUrl = new URL(APP_URL);

    return {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        origin: appUrl.origin,
        allowedHosts: [appUrl.hostname],
        hmr: {
            host: appUrl.hostname,
            protocol: appUrl.protocol === 'https:' ? 'wss' : 'ws',
            clientPort: Number(appUrl.port || (appUrl.protocol === 'https:' ? 443 : 80)),
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    };
}

export default defineConfig(({ command, mode }) => ({
    resolve: {
        alias: {
            '@hotwire': fileURLToPath(
                new URL('vendor/emaia/laravel-hotwire/resources/js/controllers', import.meta.url),
            ),
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    ...(command === 'serve' ? { server: devServer(mode) } : {}),
}));
