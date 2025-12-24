import Dropzone from "@deltablot/dropzone";

window.Dropzone = Dropzone;
window.Dropzone.autoDiscover = false;

window.Dropzone_Field = function( root, settings ){

	var cl = console.log;

	// cl(settings);
	// cl(settings.csrf_token);

	
	const existing_files_field = root.$refs['dropzone_existing_files_field'];
	const dropzone_el =  root.$refs['dropzone_dropzone'];

	if( !dropzone_el || !existing_files_field ) return;

	var myDropzone;

	var renderable_filetypes = [ 'jpg', 'jpeg', 'png', 'apng', 'gif', 'ico', 'avif', 'webp', 'svg'  ];
	
	// var unrenderable_filetypes = {
	// 	'document': [ 'pdf' ,
	// 	'data': [ 'csv' ,
	// 	'video': [ 'mp4' ,
	// 	'image': [ 'heic' ,
	// 	'music': [ 'mp3' ,
	// 	'archive': [ 'zip' ,
	// };

	function init(){
				
		myDropzone = new Dropzone( dropzone_el , {
							
			previewTemplate: root.$refs['dropzone_file_preview_tpl'].innerHTML,
			dictRemoveFile: 'Удалить',

			dictMaxFilesExceeded: 'max_files',
			dictResponseError: 'server_error',
			dictInvalidFileType: 'file_type',
			dictFileTooBig: 'file_size',

			createImageThumbnails: true,
			thumbnailWidth: settings.thumbnail_render_w,
			thumbnailHeight: settings.thumbnail_render_w * settings.thumbnail_aspect,
			// thumbnailWidth: 200,
			// thumbnailHeight: 200,
			thumbnailMethod: 'contain',
			resizeQuality: 1,

			// resizeThumbnail: true,

			method: 'post',
			headers: {
				'X-CSRF-Token' : settings.csrf_token
			},
			url: "/moonshine-dropzone",

			maxFilesize: 15,
			maxFiles: settings.max_files,
			uploadMultiple: true,
			addRemoveLinks: false,
			parallelUploads: 4

		});

		myDropzone.on( "sending", ( file, xhr, formData ) => {

			formData.append('action', 'upload' );
			formData.append('path', settings.upload_path );
			//cl(this);
			//cl(this.settings.upload_path);

		});
				
		// myDropzone.on( "maxfilesreached", ( ) => {

		// });
		
		myDropzone.on( "thumbnail", ( file ) => {
			
			Thumbnail_Unrenderable_Case( file );

		});
				
		myDropzone.on( "removedfile", ( ) => {

			Update_Error_Status();
			Try_Uploading_Rejected_On_Remove();

			Update_Existing_Files_Field();

			// cl(myDropzone.files)
			Shrink_Columns();
			Set_Container_Height();
		});

		myDropzone.on( "error", ( file, msg ) => {
			// cl(file)
			// cl(msg)
			// cl(type)
			file.rejection_reasons.add( msg );
		});


		myDropzone.on("addedfile", file => {

			// cl("addedfile")
			if( file.hasOwnProperty( 'upload' ) ){
				
				file.is_new = true;

				file.previewElement.classList.add('new_file_dropped');

				setTimeout( () => {
					file.previewElement.classList.remove('new_file_dropped');
				}, 50);
			}
			

			file.rejection_reasons = new Set();
			// cl('added:')
			// cl(file)
			// file.previewElement.style[ 'width' ] = settings.thumbnail_w + 'px';
			// file.previewElement.style[ 'aspect-ratio' ] = settings.thumbnail_aspect;


			file.previewElement.querySelector('.dz-remove-button').addEventListener("click", ( e ) => {
							
				Remove_File( file );
			});

			file.previewElement.querySelector( ".progressbar" ).classList.remove( '_hidden' );

			Thumbnail_Unrenderable_Case( file );
			Shrink_Columns();
			Set_Container_Height();
						

		});

				
						
		myDropzone.on( "success", file => {

			// cl("success")

			try{

				let response = JSON.parse( file.xhr.response );

				response.forEach( path => {

					path_full = `${ settings.base_dir }${ path }`;

					file.name = Get_File_Name( path_full );
					file.stored_path = path;

					file.previewElement.querySelector( ".progressbar" ).classList.add( '_hidden' );

					file.rejection_reasons = new Set();

					Update_Existing_Files_Field();

				});

			} catch(e){
				cl(e);
			}

		});

		// Called whenever the upload progress gets updated.
		// Receives `file`, `progress` (percentage 0-100) and `bytesSent`.
		// To get the total number of bytes of the file, use `file.size`
		myDropzone.on( "uploadprogress", ( file, progress, bytesSent ) => {
							
						
			if (file.previewElement ) {

				let progress_circle = file.previewElement.querySelector( "circle" );

				progress_circle.style[ 'stroke-dashoffset' ] = Math.abs( 100 - progress );
				// cl( progress );

								
															
			}
		});

		// myDropzone.on("thumbnail", function(file ) {

			// cl(file);
			// console.log( file.dataURL);

			// cl( Get_File_Blob( file.dataURL ) );

			// file.previewElement.querySelector('img').src = `data: ${file.dataURL}`;
			// cl(file.previewElement.querySelector('img'))
			// If dataUrl is empty or just "data:,", the generation failed.
		// });

				
		Show_Existing_Files();
		Set_Container_Height();
		Shrink_Columns();
	};

	init();
			
				
	function Get_File_Name( path ){
		return path.split('/').pop();
	};
	
	function Get_File_Extension( path ){
		return path.split('.').pop();
	};
				
	// function Get_File_Blob( url ){	

	// 	const blob = fetch( url )
	// 		.then((response) => {
	// 			if (!response.ok) {
	// 			throw new Error(`Response status: ${response.status}`);
	// 			}
	// 			return response.blob(); // Get the response body as a Blob
	// 		});

	// 	return blob;
	// };

	function Thumbnail_Unrenderable_Case( file ){

		// cl(file)

		var ext = Get_File_Extension( file.imageUrl || file.name );

		if( renderable_filetypes.includes( ext ) == false ){

			file.imageUrl = '';

			file.previewElement.querySelector( '.file_icon' ).textContent = '.' + ext;
			file.previewElement.classList.add( 'thumbnail_unrenderable' );

		}

	}

	// function test(){

	// 	file = {
	// 		rejection_reasons: new Set([
	// 			// 'max_files',
	// 			// 'server_error',
	// 			// 'other'
	// 		])
	// 	};

	// 	function Allowed_Retry( file ){
			
	// 		const allowed_reasons = [ 'max_files', 'server_error' ];
	// 		const keys = file.rejection_reasons.keys();

	// 		return keys.every( key => allowed_reasons.includes( key ) );

	// 	}

	// 	cl('---------------');
	// 	cl( Allowed_Retry( file ) );

	// }
				
	// test();

	function Is_Allowed_Retry( file ){
			
		const allowed_reasons = [ 'max_files', 'server_error' ];
		const keys = file.rejection_reasons.keys();

		return keys.every( key => allowed_reasons.includes( key ) );

	}

	function Try_Uploading_Rejected_On_Remove(){
			
		// cl(myDropzone.files)
		// return;
		const rejected_files = myDropzone.getRejectedFiles().filter( f => {
			return Is_Allowed_Retry( f );
		});
		
		// cl(rejected_files)

		// rejected_files.forEach( f => {
		// 	cl(Is_Allowed_Retry( f ))
		// });

		if( rejected_files.length !== 0 ){

			let file = rejected_files[ 0 ];
			
			if( !file || !file.rejection_reasons ) return;

			// const allowed_retry = Allowed_Retry( file );

			if( file.is_new === true  ){

				let copy = structuredClone( file );
				myDropzone.removeFile( file );
				myDropzone.addFile( copy );

			} else if( file.is_new === false ){

				let new_file = {
					name: file.name,
					size: 12345,
					stored_path: file.stored_path,
					imageUrl: file.imageUrl,
					accepted: idx < settings.max_files,
					is_new: false
				};

				myDropzone.removeFile( file );

				myDropzone.files.push( new_file );
					
				myDropzone.displayExistingFile(
					new_file,
					new_file.imageUrl
				);

				
			}

		}
	
	}

	function Update_Error_Status(){

		// return;

		myDropzone.files.forEach( ( file, idx ) => {

			if( file.rejection_reasons.size === 0 ){

				file.previewElement.classList['remove']( 'dz-error' );
				// file.rejection_reasons[ 'max_files' ] = false;

			} else{

				file.previewElement.classList['add']( 'dz-error' );
				// file.rejection_reasons[ 'max_files' ] = true;;

			}
					
			//if( file.accepted === false ) file.previewElement.classList.add( 'dz-error' );

		});

	}

	function Extract_Paths_From_Accepted_Files(){

		const result = [];

		myDropzone.files.forEach( file => {

			if( file.accepted ){
						
				result.push( file.stored_path );
						
			} else if( !file.is_new ){
						
				result.push( file.stored_path );

			}

		});

		return result;

	}
		
	function Read_Existing_Files_Field(){

		let value = existing_files_field.value;
	
		return value.split(',').filter( el => el.length > 0 );

	};

	function Update_Existing_Files_Field(){

		let result;
		let files = Extract_Paths_From_Accepted_Files();

		existing_files_field.value = files.join(',');

		cl( 'Field value: ' );
		cl( existing_files_field.value );

	}

	function Show_Existing_Files(){

		const existing_files = Read_Existing_Files_Field();

		existing_files.forEach( ( file_path, idx ) => {
			// file_path = `${ file_path }`;
			let  file_url = `${ settings.base_dir }${ file_path }`;
			let file_name = Get_File_Name( file_path );
					
			// cl(file_url)
					
			let file = {
				name: file_name,
				size: 12345,
				stored_path: file_path,
				imageUrl: file_url,
				accepted: idx < settings.max_files,
				is_new: false
			};


			myDropzone.files.push( file );
					
			myDropzone.displayExistingFile(
				file,
				file.imageUrl
			);

		});
				
		Update_Error_Status();

		// cl(myDropzone);
		// cl(myDropzone.files);

	};

	function Remove_File( file ){

		cl( 'Dropzone remove:' )
				
		cl(file);
	
		let remove_button = file.previewElement.querySelector( '.dz-remove-button' );
			
		let confirmation_timeout;

		// cl(remove_button)

		if( remove_button.dataset[ 'confirmation_pending' ] === 'false' ){

			remove_button.dataset[ 'confirmation_pending' ] = 'true';

			confirmation_timeout = setTimeout( () => {

				remove_button.dataset[ 'confirmation_pending' ] = 'false';
				clearTimeout( confirmation_timeout );

			}, 2000);

		} else{

			clearTimeout( confirmation_timeout );
			file.previewElement.classList.add( 'deleted_animation' );

			setTimeout( () => {
				myDropzone.removeFile( file );
			}, 200 );

		}

	};



	function Set_Container_Height(){

		  return;

		var children_count = root.$refs[ 'dropzone_dropzone' ].children.length;
		
		if( children_count === 0 /*&& root.$refs[ 'dropzone_dropzone' ].classList.contains( 'single_col' )*/ ){
			 
			root.$refs[ 'dropzone_dropzone' ].style[ 'height' ] = Math.min( settings.thumbnail_w, root.$el.offsetWidth ) / settings.thumbnail_aspect + 'px';
			 cl(root.$el.offsetWidth)

		} else {
			root.$refs[ 'dropzone_dropzone' ].style[ 'height' ] = 'auto';
		}

	};


	
	function Shrink_Columns(){

		// return;

		if( !settings.compact_mode ) return;
		// cl(settings.reduce_empty_columns)
	
		// var children_count = dropzone_el.querySelectorAll( '.dz-preview:not(.dz_sizer)' ).length;
		////? -1 for the 'fake' sizer element.
		// var children_count = dropzone_el.children.length - 1;
		var children_count = myDropzone.files.length;

		if( /*children_count > 0 &&*/ children_count <= settings.dropzone_grid_max_columns ){
			
			let columns = Math.max( 1, children_count );
		
			dropzone_el.style[ 'grid-template-columns' ] =
				`repeat( ${ columns }, minmax( 0, ${ settings.thumbnail_w }px ) )`;
				
		}
		
	};



}