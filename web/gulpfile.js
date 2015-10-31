var gulp = require('gulp'),
    autoprefixer = require('gulp-autoprefixer'),
    minifyCss = require('gulp-minify-css'),
    stylus = require('gulp-stylus'),
    ngmin = require('gulp-ngmin'),
    gcm   = require('gulp-group-css-media-queries'),
    plumber = require('gulp-plumber');


gulp.task('default', ['watch', 'css']);

gulp.task('css', function () {
  gulp.src('./stylus/style.styl')
    .pipe(plumber())
    .pipe(stylus())
    //.pipe(gcm())
    .pipe(gulp.dest('./css'));
});




gulp.task('watch', function() {
  gulp.watch('./stylus/**/*.styl', ['css']);
});