import tailwindcss from "@tailwindcss/vite";
import laravel, { refreshPaths } from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/filament/portal/theme.css',
                // 'resources/js/components/editorjs/index.js',
                'resources/css/components/editorjs/index.css',
                'resources/css/components/editorjs/resizable-image.css',
                'resources/css/components/editorjs/comment-tune.css',
                'resources/css/components/document/document.css',
                'resources/css/audio-annotation.css',
                'resources/css/components/mentionable-text.css',
                'resources/css/components/voice-recorder.css',
                'resources/css/components/video-recorder.css',
                // 'resources/js/dist/components/alpine-sortable.js',
            ],
            refresh: [
                ...refreshPaths,
                'app/Livewire/**',
            ],
        }),
    ],
});
