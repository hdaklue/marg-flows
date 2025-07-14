import laravel, { refreshPaths } from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/filament/portal/theme.css',
                'resources/js/components/editorjs/index.js',
                'resources/css/components/editorjs/index.css',
                'resources/css/components/editorjs/resizable-image.css',
            ],
            refresh: [
                ...refreshPaths,
                'app/Livewire/**',
            ],
        }),
    ],
});
