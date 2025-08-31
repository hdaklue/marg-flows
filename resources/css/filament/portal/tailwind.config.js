// import preset from '../../../../vendor/filament/filament/tailwind.config.preset'
const colors = import('tailwindcss/colors')
export default {
    theme: {
        extend: {
            fontSize: {
                '2xs': ['0.625rem', '0.75rem'],  // 10px font, 12px line-height (1.2x)
                '3xs': ['0.5rem', '0.625rem'],   // 8px font, 10px line-height (1.25x)
            }
        }
    },
    // presets: [preset],
    content: [
        './resources/views/forms/**/*.blade.php',
        './resources/views/**/*.blade.php',
        './app/Filament/**/*.php',
        './resources/views/livewire/**/*.blade.php',
        './resources/views/filament/**/*.blade.php',
        './resources/views/tables/**/*.blade.php',
        './resources/views/vendor/**/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    safelist: [
        {
            pattern: /bg-(red|blue|green|yellow|purple|pink|indigo|gray|orange|teal|cyan|lime|emerald|violet|fuchsia|rose|sky|amber|slate|zinc|neutral|stone)-(50|100|200|300|400|500|600|700|800|900)/,
            variants: ['hover', 'focus', 'dark', 'dark:hover', 'disabled', 'dark:disabled'],
        },
        // Text colors with all shades
        {
            pattern: /text-(red|blue|green|yellow|purple|pink|indigo|gray|orange|teal|cyan|lime|emerald|violet|fuchsia|rose|sky|amber|slate|zinc|neutral|stone)-(50|100|200|300|400|500|600|700|800|900)/,
            variants: ['hover', 'focus', 'dark', 'dark:hover', 'disabled', 'dark:disabled'],
        },
        // Background colors with opacity (specifically for your use case)
        {
            pattern: /bg-(red|blue|green|yellow|purple|pink|indigo|gray|orange|teal|cyan|lime|emerald|violet|fuchsia|rose|sky|amber|slate|zinc|neutral|stone)-(50|100|200|300|400|500|600|700|800|900)\/(5|10|20|25|30|40|50|60|70|75|80|90|95)/,
            variants: ['hover', 'focus', 'dark', 'dark:hover', 'disabled', 'dark:disabled'],
        },
        // Text colors with opacity
        {
            pattern: /text-(red|blue|green|yellow|purple|pink|indigo|gray|orange|teal|cyan|lime|emerald|violet|fuchsia|rose|sky|amber|slate|zinc|neutral|stone)-(50|100|200|300|400|500|600|700|800|900)\/(5|10|20|25|30|40|50|60|70|75|80|90|95)/,
            variants: ['hover', 'focus', 'dark', 'dark:hover', 'disabled', 'dark:disabled'],
        },
        {
            pattern: /border-(red|blue|green|yellow|purple|pink|indigo|gray|orange|teal|cyan|lime|emerald|violet|fuchsia|rose|sky|amber|slate|zinc|neutral|stone)-(50|100|200|300|400|500|600|700|800|900)/,
            variants: ['hover', 'focus', 'dark', 'dark:hover', 'disabled', 'dark:disabled'],
        },
        {
            pattern: /border-(red|blue|green|yellow|purple|pink|indigo|gray|orange|teal|cyan|lime|emerald|violet|fuchsia|rose|sky|amber|slate|zinc|neutral|stone)-(50|100|200|300|400|500|600|700|800|900)\/(5|10|20|25|30|40|50|60|70|75|80|90|95)/,
            variants: ['hover', 'focus', 'dark', 'dark:hover', 'disabled', 'dark:disabled'],
        }
    ],

    plugins: [
        require('tailwind-scrollbar-hide')
        // ...
    ]
}
