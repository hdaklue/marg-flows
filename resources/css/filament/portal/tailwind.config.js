import preset from '../../../../vendor/filament/filament/tailwind.config.preset'
const colors = import('tailwindcss/colors')
export default {
    presets: [preset],

    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './resources/views/tables/**/*.blade.php',
        './resources/views/vendor/**/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    safelist: [
        {
            pattern: /bg-(red|blue|green|yellow|purple|pink|indigo|gray|orange|teal|cyan|lime|emerald|violet|fuchsia|rose|sky|amber|slate|zinc|neutral|stone)-(50|100|200|300|400|500|600|700|800|900)/,
            variants: ['hover', 'focus', 'dark'],
        },
        // Text colors with all shades
        {
            pattern: /text-(red|blue|green|yellow|purple|pink|indigo|gray|orange|teal|cyan|lime|emerald|violet|fuchsia|rose|sky|amber|slate|zinc|neutral|stone)-(50|100|200|300|400|500|600|700|800|900)/,
            variants: ['hover', 'focus', 'dark'],
        },
        // Background colors with opacity (specifically for your use case)
        {
            pattern: /bg-(red|blue|green|yellow|purple|pink|indigo|gray|orange|teal|cyan|lime|emerald|violet|fuchsia|rose|sky|amber|slate|zinc|neutral|stone)-(50|100|200|300|400|500|600|700|800|900)\/(5|10|20|25|30|40|50|60|70|75|80|90|95)/,
            variants: ['hover', 'focus', 'dark'],
        },
        // Text colors with opacity
        {
            pattern: /text-(red|blue|green|yellow|purple|pink|indigo|gray|orange|teal|cyan|lime|emerald|violet|fuchsia|rose|sky|amber|slate|zinc|neutral|stone)-(50|100|200|300|400|500|600|700|800|900)\/(5|10|20|25|30|40|50|60|70|75|80|90|95)/,
            variants: ['hover', 'focus', 'dark'],
        },
        {
            pattern: /border-(red|blue|green|yellow|purple|pink|indigo|gray|orange|teal|cyan|lime|emerald|violet|fuchsia|rose|sky|amber|slate|zinc|neutral|stone)-(50|100|200|300|400|500|600|700|800|900)/,
            variants: ['hover', 'focus', 'dark'],
        },
        {
            pattern: /border-(red|blue|green|yellow|purple|pink|indigo|gray|orange|teal|cyan|lime|emerald|violet|fuchsia|rose|sky|amber|slate|zinc|neutral|stone)-(50|100|200|300|400|500|600|700|800|900)\/(5|10|20|25|30|40|50|60|70|75|80|90|95)/,
            variants: ['hover', 'focus', 'dark'],
        }
    ],

    plugins: [
        require('tailwind-scrollbar-hide')
        // ...
    ]
}
