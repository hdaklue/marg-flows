import tailwindcss from "@tailwindcss/vite";
import laravel, { refreshPaths } from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    build: {
        rollupOptions: {
            output: {
                manualChunks: undefined // Disable automatic chunking for small files
            }
        },
        chunkSizeWarningLimit: 1000,
        minify: 'esbuild' // Use esbuild minifier (faster, less memory)
    },
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/filament/portal/theme.css',
                // Async Alpine components
                // 'resources/js/components/async/audio-annotation.js',
                // 'resources/js/components/async/video-annotation.js',
                // 'resources/js/components/async/voice-recorder.js',
                // 'resources/js/components/async/video-recorder.js',
                // 'resources/js/components/async/audio-player.js',
                // 'resources/js/components/ChunkedFileUpload/index.js',
                // CSS files
                'resources/css/components/editorjs/index.css',
                'resources/css/components/editorjs/resizable-image.css',
                'resources/css/components/editorjs/comment-tune.css',
                'resources/css/components/document/document.css',
                'resources/css/audio-annotation.css',
                'resources/css/components/mentionable-text.css',
                'resources/css/components/voice-recorder.css',
                'resources/css/components/video-recorder.css',
            ],
            refresh: [
                ...refreshPaths,
                'app/Livewire/**',
            ],
        }),
    ],
});
