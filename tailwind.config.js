const preset = require('./vendor/filament/filament/tailwind.config.preset')

module.exports = {
    presets: [preset],
    plugins: [

        require('@tailwindcss/typography'),
        require('@tailwindcss/forms'),
        require('@tailwindcss/aspect-ratio'),
        require('@tailwindcss/container-queries'),

    ],

    content: [
        './app/Filament/Admin/**/*.php',
        './resources/views/filament/admin/**/*.blade.php',
        './resources/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './src/MicroweberPackages/**/*.blade.php',
        './src/MicroweberPackages/**/resources/views/**/*.blade.php',
        './vendor/jaocero/radio-deck/resources/views/**/*.blade.php',
        './userfiles/modules/**/*.blade.php',
        './userfiles/modules/**/src/resources/views/**/*.blade.php',
    ]
}
