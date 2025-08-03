
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
    // safelist: [
    //     {
    //         pattern: /bg-(red|blue|green|yellow|purple|pink|indigo|gray|orange|teal|cyan|lime|emerald|violet|fuchsia|rose|sky|amber|slate|zinc|neutral|stone)-(50|100|200|300|400|500|600|700|800|900)/,
    //         variants: ['hover', 'focus', 'dark', 'dark:hover', 'disabled', 'dark:disabled'],
    //     },
    //     // Text colors with all shades
    //     {
    //         pattern: /text-(red|blue|green|yellow|purple|pink|indigo|gray|orange|teal|cyan|lime|emerald|violet|fuchsia|rose|sky|amber|slate|zinc|neutral|stone)-(50|100|200|300|400|500|600|700|800|900)/,
    //         variants: ['hover', 'focus', 'dark', 'dark:hover', 'disabled', 'dark:disabled'],
    //     },
    //     // Background colors with opacity (specifically for your use case)
    //     {
    //         pattern: /bg-(red|blue|green|yellow|purple|pink|indigo|gray|orange|teal|cyan|lime|emerald|violet|fuchsia|rose|sky|amber|slate|zinc|neutral|stone)-(50|100|200|300|400|500|600|700|800|900)\/(5|10|20|25|30|40|50|60|70|75|80|90|95)/,
    //         variants: ['hover', 'focus', 'dark', 'dark:hover', 'disabled', 'dark:disabled'],
    //     },
    //     // Text colors with opacity
    //     {
    //         pattern: /text-(red|blue|green|yellow|purple|pink|indigo|gray|orange|teal|cyan|lime|emerald|violet|fuchsia|rose|sky|amber|slate|zinc|neutral|stone)-(50|100|200|300|400|500|600|700|800|900)\/(5|10|20|25|30|40|50|60|70|75|80|90|95)/,
    //         variants: ['hover', 'focus', 'dark', 'dark:hover', 'disabled', 'dark:disabled'],
    //     },
    //     {
    //         pattern: /border-(red|blue|green|yellow|purple|pink|indigo|gray|orange|teal|cyan|lime|emerald|violet|fuchsia|rose|sky|amber|slate|zinc|neutral|stone)-(50|100|200|300|400|500|600|700|800|900)/,
    //         variants: ['hover', 'focus', 'dark', 'dark:hover', 'disabled', 'dark:disabled'],
    //     },
    //     {
    //         pattern: /border-(red|blue|green|yellow|purple|pink|indigo|gray|orange|teal|cyan|lime|emerald|violet|fuchsia|rose|sky|amber|slate|zinc|neutral|stone)-(50|100|200|300|400|500|600|700|800|900)\/(5|10|20|25|30|40|50|60|70|75|80|90|95)/,
    //         variants: ['hover', 'focus', 'dark', 'dark:hover', 'disabled', 'dark:disabled'],
    //     },
    //     // Ring colors for focus states
    //     {
    //         pattern: /ring-(red|blue|green|yellow|purple|pink|indigo|gray|orange|teal|cyan|lime|emerald|violet|fuchsia|rose|sky|amber|slate|zinc|neutral|stone)-(50|100|200|300|400|500|600|700|800|900)/,
    //         variants: ['focus', 'dark:focus'],
    //     }
    // ],
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

        }
    }

}

