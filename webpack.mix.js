const mix = require('laravel-mix');
const path = require('path');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix
    .js('resources/js/app.js', 'public/js')
    .sass('resources/sass/app.scss', 'public/css')
    .vue({ version: 2 })
    .extract() // This now extracts all external dependencies
    .sourceMaps(false)
    .disableNotifications()
    //This is to allow Vue Components to access sass variables
    .webpackConfig({
    resolve: {
        alias: {
            '@': path.resolve('resources/sass')
        }
    }
})
;

if (mix.inProduction()) {
    mix.version(); // Version files to add cache busting in live
}
else {
    mix.browserSync({proxy:'local-homestead.com', open:false});
}
