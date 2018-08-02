let mix = require('laravel-mix');

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


mix.js(['resources/assets/js/app.js',
'node_modules/easy-pie-chart/dist/jquery.easypiechart.js'], 'public/js')
   .sass('resources/assets/sass/app.scss', 'public/css')
    .css('resources/assets/css/dashboard.css', 'public/css');

mix.browserSync('http://localhost:8000');