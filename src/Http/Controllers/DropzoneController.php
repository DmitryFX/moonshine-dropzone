<?php

namespace MoonShine\Dropzone\Http\Controllers;

// use Illuminate\Http\Response;

use Illuminate\Support\Str;
use MoonShine\Laravel\Http\Controllers\MoonShineController;
use Symfony\Component\HttpFoundation\Response;

class DropzoneController extends MoonShineController
{

	// public function __invoke(MoonShineRequest $request): Response
	// {
	// 	return back();
	// }

	public function dropzone(): Response{

		// $action = request( 'action' );
		$path = request( 'path' );

		$saved_file_paths = [];

		if( request()->hasFile('file') ) {


			foreach( request()->file('file') as $file ) {

				$file_info = pathinfo( $file->getClientOriginalName(), PATHINFO_ALL );

				$original_name = $file_info[ 'filename' ];
				$original_ext = $file_info[ 'extension' ];

				$hash = bin2hex( random_bytes( 3 ) );

				$new_name = Str::slug( $original_name ) . '_' . $hash;// date('d-m-Y--H-i-s');

				$resulting_basename = "{$new_name}.{$original_ext}";
				$resulting_path = "{$path}/{$resulting_basename}";

				$saved_file_paths[] = $resulting_path;

				//Log::debug( request() );
				//$del = Thumbnail::src( $resulting_path, 'public' )->delete();
				//$thumb_path = Thumbnail::src( $resulting_path, 'public' )->url();
				//Log::debug( print_r($thumb_path, true) );

				// $server = ServerFactory::create( config( 'glide' ) );
				// $server->deleteCache( $resulting_path );
				
				$file->storeAs( $path, $resulting_basename, 'public' );
			
			}

		}


		// return response( $new_name, 200 );
		return response( json_encode( $saved_file_paths, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ), 200 );

	}

	

}
