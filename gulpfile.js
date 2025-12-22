//"use strict";

import * as constants from "node:constants";

var __dir__ = 'D:\\dfx_programs\\moonshine_packages\\dropzone';


const cl = console.log;

import Path from "path";
import Fs from 'fs';
import { exec, spawn, execFile, execFileSync } from 'node:child_process';

//import chalk from "picocolors";
import chalk from 'chalk';

import chokidar from 'chokidar';
import { glob } from 'node:fs/promises';
import { globSync } from 'fs';
import livereload from "gulp-livereload";

import * as sass from 'sass';

import * as esbuild from 'esbuild';

//import LiveReload from 'gulp-livereload';

function Process_CSS( event, path ) {

	//cl(path);
	//return;

	//cl( chalk.black.bgGreen( `JS/CSS Processing \n ${path} \n` ) );
	//cl( chalk.gray( `Process_CSS event: ${event}` ) );
	cl( chalk.black.bgGreen( ` CSS Processing ${ path } \n` ) );

	if ( Fs.existsSync( path ) === false ) {
		cl( chalk.yellow( `Not exists: ${ path } \n` ) );
		return;
	}

	//Add_Hashes( path );

	path = Path.resolve( path );
	let scss_path = Path.dirname( path );
	let scss_name = Path.parse( path ).name;

	let out_css;
	try {
		out_css = sass.compile( path );
	} catch ( err ) {
		cl( chalk.red( `${ err } \n` ) );
		cl( chalk.red( `${ out_css } \n` ) );

		return;
	}

	out_css = sass.compile( path ).css;


	let out_css_path = scss_path.replace('scss', 'css');
	out_css_path = out_css_path.replace('src', 'public');

	if( !Fs.existsSync( out_css_path ) ) Fs.mkdirSync( out_css_path );

	Fs.writeFileSync( out_css_path + `/${ scss_name }.css`, out_css, { encoding: 'utf8' } );


	cl( chalk.black.bgGreen( '...OK \n' ) );

};

function Process_JS( event, path ) {


}


let Default = async () => {


	cl( chalk.black.bgGreen( "Default..." ) );
	cl( '\n' );


	// const liveReload = new LiveReload({
	// 	host: '127.0.0.1',
	// 	port: '35729',
	// 	enable: true,
	//
	//   });

	//livereload.start();

	livereload.listen( { host: "127.0.0.1", port: "35729", quiet: true } );
	// gulp.watch(['./**/out/*/*.html'], {delay: 800} ).on("all", function (event, path) {
	// 	Banner_Preview_Reload( event, path );
	// });

	let chokidar_settings = {
		ignoreInitial: true,
		usePolling: true,
		interval: 100,
		ignorePermissionErrors: true,
		awaitWriteFinish: {
			stabilityThreshold: 50,
			pollInterval: 50
		}
	};

	//? Process HTML, PHP

	let process_html_path = globSync( [
		__dir__ + '\\**\\*.php',
		//__dir__ + '\\resources\\**\\*.php',

	] );

	//cl(process_html_path);

	//cl( Path.resolve(__dir__ + '\\*.html') );

	let process_html_path_ignored = [];

	let chokidar_settings_Process_HTML = { ...chokidar_settings };
	chokidar_settings_Process_HTML.ignored = process_html_path_ignored;

	chokidar.watch( process_html_path, chokidar_settings_Process_HTML ).on( 'all', function ( event, path ) {

		if ( event === 'unlink' || event === 'unlinkDir' ) return;

		cl( `reload html ${ path }` );

		//livereload_server.refresh( path );
		livereload.reload();
		//BS_Server.reload( path);

	} );

	//? Process CSS

	let process_css_path = globSync( [
		//__dir__ + '\\packages\\MoonShine\\quill\\public\\js\\quill-init.js',
		__dir__ + '\\src\\scss\\dropzone_field.scss',
	] );

	let included_scss = (() => {

		let regex = new RegExp( "(?<=@use ['\"])(.*?\.scss)(?=['\";])", 'gm' );
		let included_scss_paths = {};

		process_css_path.forEach( main_scss_file => {

			let main_scss_content = Fs.readFileSync( main_scss_file, {encoding: 'utf8'} );



			main_scss_content.matchAll( regex ).forEach( ( match, idx ) => {

				included_scss_paths[ Path.resolve( `${ Path.dirname( main_scss_file ) }\\${ match[ 0 ] }` ) ] = Path.resolve( main_scss_file );

			} );

		} );

		return included_scss_paths;

	})();

	cl( chalk.black.bgBlue( 'Primary SCSS:' ) );
	cl( process_css_path );
	cl( chalk.black.bgBlue('Included SCSS:' ) );
	cl( included_scss );

	let process_css_path_ignored = [];

	let chokidar_settings_Process_CSS = { ...chokidar_settings };
	chokidar_settings_Process_CSS.ignored = process_css_path_ignored;

	//? Primary SCSS
	chokidar.watch( process_css_path, chokidar_settings_Process_CSS ).on( 'all', function ( event, path ) {

		if ( event === 'unlink' || event === 'unlinkDir' ) return;

		Process_CSS( event, path );

		path = path.replaceAll( 'scss', 'css' );

		livereload.changed( path );

	} );

	//? Included SCSS
	chokidar.watch(  Object.keys( included_scss ), chokidar_settings_Process_CSS ).on( 'all', function ( event, path ) {

		if ( event === 'unlink' || event === 'unlinkDir' ) return;


		cl( chalk.black.bgBlue(`Included SCSS:${ path } -> ` ) );

		let main_scss_file_path = included_scss[ path ];

		Process_CSS( event, main_scss_file_path );

		main_scss_file_path = main_scss_file_path.replaceAll( 'scss', 'css' );

		livereload.changed( main_scss_file_path );

	} );





	//? Process JS


	let ctx = await esbuild.context({
		entryPoints: [
				__dir__ + '\\src\\js\\dropzone.mjs'
			],
		outfile: __dir__ + '\\public\\js\\dropzone.min.js',
		bundle: true,
		treeShaking: false,
		format: 'iife',
		minify: true,
		target: 'esnext',
		// drop: [ 'cl' ],
		// pure: ['cl'],
		logLevel: "info"
	});

	await ctx.watch();

	// let lib_build = await esbuild.context({
	// 	entryPoints: [
	// 			__dir__ + '\\src\\js\\uppy.mjs',
	// 		],
	// 	// outdir: __dir__ + '\\public\\js\\',
	// 	outfile: __dir__ + '\\public\\js\\uppy.min.js',
	// 	// outfile: 'uppy.min.js',
	// 	bundle: true,
	// 	treeShaking: false,
	// 	format: 'iife',
	// 	// globalName: 'Dropzone',
	// 	target: 'esnext',
	// 	// packages: 'all',
	// 	minify: false,
	// 	logLevel: "info"
	// });

	// await lib_build.watch();


	let process_js_path = globSync( [
		 __dir__ + '\\public\\js\\*.{mjs,js}',
		//...esbuild_entry_points,
	] );

	cl( process_js_path );

	let process_js_path_ignored = [];

	let chokidar_settings_Process_JS = { ...chokidar_settings };
	chokidar_settings_Process_JS.ignored = process_js_path_ignored;

	chokidar.watch( process_js_path, chokidar_settings_Process_JS ).on( 'all', async function ( event, path ) {

		if ( event === 'unlink' || event === 'unlinkDir' ) return;

		// if( esbuild_entry_points.includes( path ) ){

		// 	let parsed_path = Path.parse( path );

		// 	await esbuild.build({
		// 	  entryPoints: [ path ],
		// 	  bundle: true,
		// 	  outfile: `./build/${ parsed_path.name }__build.${ parsed_path.ext }`,
		// 	})

		// }

		//cl( chalk.bgBlue(' \u276F Updated **/index.html  \n') );
		//Process_JS( event, path );

		//livereload_server.refresh( path );
		//cl(path)
		//path = path.replace('.scss', '.css');
		//path = path.replace('sass', 'css');
		//path = 'D:/atrium_olymp/atrium_olymp_www' + path;

		//livereload_server.refresh( path );
		//liveReload.reloadPage();
		livereload.reload();

	} );


};


Default();