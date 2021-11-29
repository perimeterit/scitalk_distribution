const gulp = require('gulp')
const postcss = require("gulp-postcss")
// Native CSS nesting using &
const postcssNesting = require("postcss-nesting")
// Native :matches() selector
const postcssMatches = require("postcss-selector-matches")
// NOT native PostCSS mixins
const postcssMixins = require("postcss-mixins")
// NOT native PostCSS @import
const postcssImport = require("postcss-import")
const sourcemaps = require('gulp-sourcemaps')
const watch = require('gulp-watch')

gulp.task("css", function() {
  const processors = [
    postcssImport,
    postcssMixins,
    postcssNesting,
    postcssMatches
  ]
  return gulp
    .src("src/**/*.css")
    .pipe( sourcemaps.init() )
    .pipe(postcss(processors))
    .pipe( sourcemaps.write('.') )
    .on('error', function(errorInfo){  // if the error event is triggered, do something
      console.log(errorInfo.toString()); // show the error information
      this.emit('end'); // tell the gulp that the task is ended gracefully and resume
    })
    .pipe(gulp.dest("css/"))
})

gulp.task("watch", function() {
  gulp.watch("src/**", gulp.series('css'));
})

 gulp.task('default', gulp.series('watch'));
