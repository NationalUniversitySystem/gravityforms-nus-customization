import { dest, src, series } from 'gulp';
import babel from 'gulp-babel';
import eslint from 'gulp-eslint';
import include from 'gulp-include';
import uglify from 'gulp-uglify';
import notify from 'gulp-notify';
import del from 'del';
import plumber from 'gulp-plumber';

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

export const scripts = series( jsLinter, js );
export default scripts;
