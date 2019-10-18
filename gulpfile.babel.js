import { dest, src, series, parallel } from 'gulp';
import babel from 'gulp-babel';
import eslint from 'gulp-eslint';
import include from 'gulp-include';
import uglify from 'gulp-uglify';
import notify from 'gulp-notify';
import del from 'del';
import plumber from 'gulp-plumber';
import rename from 'gulp-rename';

// CSS related plugins.
import autoprefixer from 'gulp-autoprefixer';
import cleanCSS from 'gulp-clean-css';
import sass from 'gulp-sass';
import sassLint from 'gulp-sass-lint';

/**
 * Custom Error Handler.
 *
 * @param Mixed error
 */
 const errorHandler = error => {
	notify.onError( {
		title: 'Gulp error in ' + error.plugin,
		message: error.toString(),
		sound: false
	} )( error );
};

/**
 * Task: `jsLinter`.
 * This task does the following:
 *    1. Gets all our theme files
 *    2. Lints theme files to keep code up to standards and consistent
 */
export const jsLinter = () => {
	return src( [ './src/js/**/*.js' ] )
		.pipe( eslint() )
		.pipe( eslint.format() );
};
jsLinter.description = 'JS linter task to keep our code consistent.';

/**
 * Task: `js`.
 *
 * This task does the following:
 *     1. Gets the source folder for JS files
 *     2. Concatenates all the files and generates *.js
 *     3. Uglifes/Minifies the JS file and generates *.min.js
 */
export const js = () => {
	// Clean up old files.
	del( './js/*' );

	return src( 'src/js/*.js', {
			sourcemaps: true
		} )
		.pipe( plumber( errorHandler ) )
		.pipe( include( {
			includePaths: [
				__dirname + '/src/js',
				__dirname + '/node_modules'
			]
		} ) )
		.pipe( babel() ) // config is in .babelrc file
		.pipe( uglify() )
		.pipe( dest( './js', { sourcemaps: '.' } ) );
};
js.description = 'Run all JS compression and sourcemap work.';

/**
 * Task: `sassLinter`.
 * This task does the following:
 *    1. Gets all our scss files
 *    2. Lints theme files to keep code up to standards and consistent
 */
export const sassLinter = () => {
	return src( 'src/scss/**/*.scss' )
		.pipe( plumber( errorHandler ) )
		.pipe( sassLint() )
		.pipe( sassLint.format() )
		.pipe( sassLint.failOnError() );
};
sassLinter.description = 'Lint through all our SASS/SCSS files so our code is consistent across files.';

/**
 * Task: `css`.
 *
 * This task does the following:
 *    1. Gets the source scss file
 *    2. Compiles Sass to CSS
 *    3. Writes Sourcemaps for it
 *    4. Autoprefixes it
 *    5. Renames the CSS file with suffix .min.css
 *    6. Minifies the CSS file and generates *.min.css
 */
export const css = () => {
	// Clean up old files.
	del( './css/*' );

	return src( 'src/scss/*.scss', { sourcemaps: true } )
		.pipe( plumber( errorHandler ) )
		.pipe( sass( { outputStyle: 'expanded' } ).on( 'error', sass.logError ) )
		.pipe( dest( './css/' ) )
		.pipe( autoprefixer( {
			cascade: false
		} ) )
		.pipe( cleanCSS( {
			level: {
				2: {
					all: false,
					mergeIntoShorthands: true,
					mergeMedia: true
				}
			}
		} ) )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( dest( './css/', { sourcemaps: '.' } ) );
};
css.description = 'Compress, clean, etc our theme CSS files.';

export const scripts = series( jsLinter, js );
export const styles  = series( sassLinter, css );
export const build   = parallel( styles, scripts );

export default build;
