
import preset from './vendor/filament/filament/tailwind.config.preset'
export default {
    content: [
        './app/Filament/**/*.php',
        './resources/views/**/*.blade.php',
        './resources/views/livewire/**/*.blade.php',
        './resources/views/components/**/*.blade.php',
        './resources/views/livewire/preview-audio.blade.php',
        './resources/js/**/*.js',
        './resources/css/**/*.css',
        './vendor/filament/**/*.blade.php',
    ],
    presets: [preset],
    plugins: [require('tailwind-scrollbar-hide')
    ],
    theme: {
        extend: {
            fontFamily: {
                'sans': ['ui-sans-serif', 'system-ui'],
                'mono': ['ui-monospace', 'SFMono-Regular', 'Monaco', 'Consolas', 'monospace'],
            },
            animation: {
                'spin': 'spin 1s linear infinite',
            },
            backdropBlur: {
                'sm': '4px',
            },
            colors: {
                'emerald': {
                    600: '#059669',
                    700: '#047857',
                },
                'sky': {
                    50: '#f0f9ff',
                    400: '#38bdf8',
                    500: '#0ea5e9',
                    600: '#0284c7',
                    700: '#0369a1',
                    900: '#0c4a6e',
                },
                'zinc': {
                    50: '#fafafa',
                    100: '#f4f4f5',
                    200: '#e4e4e7',
                    300: '#d4d4d8',
                    400: '#a1a1aa',
                    500: '#71717a',
                    600: '#52525b',
                    700: '#3f3f46',
                    800: '#27272a',
                    900: '#18181b',
                }
            }
        }
    }

}

