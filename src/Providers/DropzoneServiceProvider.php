<?php

declare(strict_types=1);

namespace MoonShine\Dropzone\Providers;

use Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Log;
use MoonShine\AssetManager\Css;
use MoonShine\AssetManager\Js;
use MoonShine\Dropzone\Helpers\DropzoneBag;
use MoonShine\Dropzone\Http\Controllers\DropzoneController;
use MoonShine\Laravel\Http\Middleware\Authenticate;
// use Src\Helpers\DropzoneStorage;

final class DropzoneServiceProvider extends ServiceProvider
{
    // public function register(): void
    // {
        
   
    // }

    public function boot(): void
    {
		moonshineAssets()->prepend([

			Css::make( asset( 'vendor/moonshine-dropzone/css/dropzone_field.css' ) ),
			Js::make( asset( 'vendor/moonshine-dropzone/js/dropzone.min.js' ) )
		]);
		
		Log::debug('DropzoneServiceProvider');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'moonshine-dropzone');


        Route::post('moonshine-dropzone', [ DropzoneController::class, 'dropzone'] )
           ->middleware(['moonshine', Authenticate::class])
           ->name('moonshine-dropzone');

        $this->publishes([
            __DIR__ . '/../../public' => public_path('vendor/moonshine-dropzone'),
        ]);
		//, ['moonshine-dropzone-assets', 'laravel-assets']
        // function 

        Event::listen(
            "eloquent.created:*",
            function( $event, $event_data ) { 

                $model = $event_data[ 0 ];
                // $model_array = Arr::dot( $model->toArray() );
                $model_basename = class_basename( $model );
                $model_array =$model->toArray();

                // Log::debug( $model_basename );
                $bag = DropzoneBag::all();
                // Log::debug( 'BAG_____________: ', DropzoneBag::all() );


                if( !key_exists( $model_basename, DropzoneBag::all() )
                ) return;

                // Log::debug( "ELOQUENT.CREATED____________" );

                // Log::debug( 'BAG_____________: ', DropzoneBag::all() );
                // die;

                // Log::debug( 'Old model_____________: ', $model_array );

           
                foreach( DropzoneBag::get( $model_basename ) as $column => $column_data ){

                    // Log::debug( 'Col_____________: '. $column );
                    
                    $model_column_value = data_get( $model_array, $column );

                    if( empty( $model_column_value ) ) continue;

                    if( is_string( $model_column_value ) ){
                        
                        $new_value = str_replace(
                            $column_data[ 'temp_path' ],
                            $column_data[ 'path_callback' ]( $model ),
                            $model_column_value
                        );

                        // data_set( $model_array, $column, $model_column_value );
                        // $model_array[ $column ] = $model_column_value;
                        data_set( $model_array, $column, $new_value );


                    } else if( is_array( $model_column_value )  ){

                        $new_array = [];

                        foreach( $model_column_value as $path ){

                            $new_array[] = str_replace(
                                $column_data['temp_path'],
                                $column_data[ 'path_callback' ]( $model ),
                                $path
                            );
                            
                        }

                        data_set( $model_array, $column, $new_array );
                        // $model_array[ $column ] = $new_array;

                    }


                    //? Temp Folder rename routine 

                    $disk = Storage::disk( $column_data['disk'] );
                    $old_path = $disk->path( $column_data['temp_path'] );

                    // Log::debug( 'Old dir_____________: ' . $old_path );

                    if( is_dir( $old_path ) ){

                        $new_path =$disk->path( $column_data[ 'path_callback' ]( $model ) );
 
                        // Log::debug( 'New dir_____________: ' . $new_path );
                        rename( $old_path, $new_path );


                    }

                }


                // Log::debug( 'New model_____________: ', $model_array );
                // die;

                $model->forceFill( $model_array );
                $model->save();
                // die;
              
            

        });
      
    }
}
?>