import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

const port = 5173;
const origin = process.env.DDEV_PRIMARY_URL || `http://localhost:${port}`;

export default defineConfig({
    // Adjust Vites dev server for DDEV: https://vitejs.dev/config/server-options.html
    server: {
        // The following line is require until the release of https://github.com/vitejs/vite/pull/19241
        cors: { origin },
        // ----------------
        host: '0.0.0.0',
        port: port,
        origin: `${origin}:${port}`,
        strictPort: true
    },
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
