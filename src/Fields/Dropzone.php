<?php


declare(strict_types=1);

namespace MoonShine\Dropzone\Fields;

use Closure;
use Illuminate\Support\Facades\Log;
use MoonShine\AssetManager\Css;
use MoonShine\AssetManager\Js;
use MoonShine\Contracts\Core\DependencyInjection\FieldsContract;
use MoonShine\Laravel\Traits\Request\HasPageRequest;
use MoonShine\UI\Fields\Field;
use MoonShine\Core\Traits\HasResource;
// use MoonShine\UI\Traits\Fields\WithDefaultValue;
use Storage;

/**
 * Short one‑line description.
 *
 * A longer paragraph that explains what the class does,
 * its responsibilities, and any important design notes.
 *
 * @package   MoonShine\Dropzone
 * @author DmitryFX <i@dmitryfx.com>
 * @license   MIT
 * @link   https://github.com/your/repo
 * @method static static make(Closure|string|null $label = null, ?string $column = null, ?Closure $formatted = null)
 */
final class Dropzone extends Field
{	
	// use HasPageRequest;
	use HasResource;
	// use WithDefaultValue;

	protected string $view = 'moonshine-dropzone::fields.dropzone';
	protected string $uid;

	private bool $disabled_until_save = false;

	private mixed $default_value;

	private int $max_files;
	private string $note;
	private bool $upload_on_drop;

	private string $base_dir;
	private string|Closure $upload_path;
	private string|null $temp_upload_path = null;
	
	private bool $compact_mode;
	private int $dropzone_grid_max_columns;
	private bool $reduce_empty_columns;

	private bool $poster_mode;
	private string $preview_element_style;

	private int $thumbnail_w;
	private float $thumbnail_render_w;
	private float $thumbnail_aspect;



	protected Closure $upload_path_cb;
	
	 public function __construct(
		Closure|string|null $label = null,
		?string $column = null,
		?Closure $formatted = null,
	) {
		
		parent::__construct( $label, $column, $formatted );
		// $this->filterable(false);

		$this->setLabel( $label );


		//? Apply defaults
		$this->uid = bin2hex( random_bytes( 3 ) );

		$this->max_files = 9999;
		$this->note = '';
		$this->upload_on_drop = true;
		$this->base_dir = Storage::url("");
		$this->upload_path = '';

		$this->compact_mode = false;
		$this->dropzone_grid_max_columns = 3;
		$this->reduce_empty_columns = false;

		$this->poster_mode = false;
		$this->compact_mode = false;
		$this->preview_element_style = '';

		$this->thumbnail_w = 100;
		$this->thumbnail_render_w = 160;
		$this->thumbnail_aspect = 1;
		 

		$uri = request()->route('resourceUri');
		$resource = moonshine()->getResources()->findByUri($uri);
		 
		Log::debug( $resource?->getItemID() );
		// $this->removeAttribute('temp_path');
		// $this->customAttributes(['temp' => 'temp']);
		//debug($label);
		// $this->default();
		// Log::debug($this->getColumn());
		// Log::debug($this->getVirtualColumn());
		// Log::debug('----------------------');
		// Log::debug($this->getResource());
		if( empty( moonshineRequest()->getItemID() ) ){

			//$this->disabled_until_save = true;

		}

	}

	protected function assets(): array
	{
		return [
			Css::make( asset( 'vendor/moonshine-dropzone/css/dropzone_field.css' ) ),
			Js::make( asset( 'vendor/moonshine-dropzone/js/dropzone.min.js' ) ),
		];
	}

	protected function booted(): void
	{
		parent::booted();

		$this->refreshAfterApply();

		// Log::debug($this->hasResource() ? 'has' : 'not');
		// Log::debug($this->hasResource());
	}

	public function default( string|array $default_value ){

		if(
			$this->max_files === 1 && is_string( $default_value ) ||
			$this->max_files > 1 && is_array( $default_value )
		){

			$this->default_value = $default_value;

		} else{
			$this->default_value = $this->max_files > 1 ? [] : '';
			Log::debug( 'Dropzone. Incorrect default type. Single: "", Multi: []' );
		}

		return $this;
	}

	

	public function getDefault(){

		return $this->max_files > 1 ?
			$this->default_value ?? [] :
			$this->default_value ?? '' ;

	}

	// resolveOnBeforeApply()   // validation / prep
	// resolveOnApply()         // set the value on the model instance
	// Model->save()            // the new record is written to the DB
	// resolveOnAfterApply()    // side‑effects (relationships, file uploads, etc.)

	private function convertTempPath(){



	}

	protected function resolveValue(): mixed{


		// Log::debug( $this );
		// Log::debug( $this->value );
		// Log::debug( ($this->upload_path_cb)() );
		// Log::debug( get_object_vars($this) );
		
		$out = null;

		if( empty( $this->value ) ){

			$out = '';

		} else{

			$out = is_string( $this->value ) ? $this->value : implode( ',', $this->value );

		}
		
		// debug( $out );
		return $out;

	}

   
	protected function resolveOnApply(): ?Closure
	{

		return function (mixed $item): mixed {
	
			// Log::debug( 'resolveOnApply' );
			// Log::debug( gettype($item) );
			// Log::debug( $this->isCanApply() );
			
			$value = $this->getRequestValue();
			$result = null;

			if( !empty( $value ) ){

				if( $this->max_files === 1 ){
					
					$result = !stristr( $value, ',' ) ?
						$value :
						array_slice( explode( ',', $value ), 0, $this->max_files );
;

				} else {
					
					$result = array_slice( explode( ',', $value ), 0, $this->max_files );

				}

			} else{

				$result = $this->getDefault();

			}

			// Log::debug( is_null( $result ) ? 'null' : $result );
			// Log::debug( gettype( $item )  );
			// Log::debug( $item  );
			// Log::debug( $this->getColumn()  );

			data_set($item, $this->getColumn(), $result );

			return $item;
		};
	}

	// protected function resolveOnApply(): ?Closure
	// {
	// 	return function (Model $item): Model {
	// 		$item->{$this->getColumn()} = $this->getRequestValue() !== false
	// 			? $this->getRequestValue()
	// 			: $this->generateSlug($item->{$this->getFrom()});

	// 		if ($this->isUnique()) {
	// 			$item->{$this->getColumn()} = $this->makeSlugUnique($item);
	// 		}

	// 		return $item;
	// 	};
	// }
	
	protected function afterSave( mixed $item, FieldsContract $fields ): mixed{


		Log::debug("Resource ID: " . $item);
		Log::debug("Resource ID: " . $item);
		Log::debug("Resource ID: " . $fields);
		
		return $item;

	}
	
	public function afterApply( mixed $model ): mixed {

		return $model;
		// Log::debug( get_object_vars($this) );
		// Log::debug( get_class_methods( moonshineRequest() ) );


		// Log::debug( ( $this->upload_path_cb )( $model ) );
		Log::debug( '****************************************' );
		// Log::debug( moonshineRequest()->getItemID() );
		$resourceId = request()->route('resourceItem'); 

		    
		Log::debug("Resource ID: " . $resourceId);
		// Log::debug( $model);
		// Log::debug(  $this->getDotNestedToName( $this->getColumn() ) );
		// Log::debug( '----------------------' );
		// Log::debug( get_class_methods( moonshineRequest() ) );
		// Log::debug(  moonshineRequest()->getItemID()  );
		// Log::debug( moonshineRequest()->getResource()->getItem() );
		// Log::debug( $model?->getKey() );
		// Log::debug( $this->getRequestNameDot() );
		// Log::debug( ( $this->upload_path_cb )( $model ) );
		// Log::debug( $this->upload_path );
		// Log::debug( $model );
		// Log::debug( data_get( request(), $this->getRequestNameDot() ) );

		// return $model;
		die;
		// die;
		// Log::debug( $this->upload_path );
		// Log::debug( $model->getModel() );
		if( false && is_callable( $this->upload_path_cb ) ){

			// Log::debug( ( $this->upload_path_cb )( $model ) );
			$column = $this->getColumn();
			$temp_path = request( 'temp_upload_path__' . $column );
			$new_path = ( $this->upload_path_cb )( $model );
			
			// Log::debug( 'name dot: ' .  $this->getRequestNameDot() );
			// Log::debug( 'old path: ' . $temp_path );
			// Log::debug( 'new path: ' . $new_path );
			
			// Log::debug( 'old value: ' .  request( $column ) );

			// die;
			// Log::debug( $column );
			// Log::debug( '-------' );
			// Log::debug( $this->value );
			// Log::debug( '-------' );
			// Log::debug( $model );
			// Log::debug( '-------' );
			// Log::debug( $model->wasRecentlyCreated );
			// Log::debug( '-------' );
			// Log::debug( $new_path );
			// Log::debug( '--------------------------------------' );

			// $str = "temp_media_c63f87\/cover_25-12-2025.png";
			// Log::debug( preg_replace( '/temp_media.{6}/i', 'FFFFFFFFFF', $str) );

			// die;
			/*
			if( is_array( $model ) ){

				array_walk_recursive($model, function (&$value) {
					
					if( stristr( $value, 'temp_' ) ) {
						$value = str_replace('temp_', 'FFFFFFFFFF', $value);
					}
				});

				Log::debug( $model );

			} else if( get_parent_class( $model ) == 'Illuminate\Database\Eloquent\Model' ){

				// $model->set(  );
				$data = data_get( $model, $column );

				if( is_array( $data ) ){

					array_walk_recursive( $data, function( &$value ) {

						Log::debug( $value );
					
						if( stristr( $value, 'temp_media_' ) ) {
							$value = preg_replace( '/temp__media/', 'FFFFFFFFFF', $value);
						}
					});

				}

				data_set( $model, $column, $data );
				Log::debug( $model );
			}*/

			//data_set( $model, "*.$column",  );

		}

		// Log::debug( $model );
		
		// die;
		// if( $this->getModel()->wasRecentlyCreated ){

		// 	//data_set($model, $this->getColumn(), $result );

		// }


		return $model;
	}

	

	/**
	 * maxFiles = 1 saves String. Otherwise Array.
	 * @param int $max_files
	 * @return Dropzone
	 */
	public function maxFiles( int $max_files ): Field {

		$this->max_files = max( 1, min( 9999, $max_files ) );

		$this->dropzone_grid_max_columns = 
			$max_files === 1 ? 1 : $this->dropzone_grid_max_columns;

		return $this;
	}

	
	/**
	 * Dropzone area layout
	 * @param int $max_columns | max thumbnail columns
	 * @return Dropzone
	 */
	public function layout( int $max_columns = 3 ): Field {

		$this->dropzone_grid_max_columns = $max_columns;
		//$this->reduce_empty_columns = $reduce_empty_columns;
		

		return $this;
	}

	/**
	 * Maximize component and thumbnails width
	 * @return static
	 */
	public function posterMode(){

		$this->poster_mode = true;
		$this->compact_mode = false;

		return $this;
	}
	
	/**
	 * Minimize component width, reducing empty columns
	 * @return static
	 */
	public function compactMode(){

		$this->compact_mode = true;
		$this->poster_mode = false;

		return $this;
	}

	
	/**
	 * Upload immediately
	 * @param bool $upload_on_drop
	 * @return Dropzone
	 */
	public function uploadOnDrop( ?bool $upload_on_drop = true ): Field {

		$this->upload_on_drop = $upload_on_drop;

		return $this;
	}
	
	/**
	 * Size of the thumbnails.
	 * @param int $render_width | thumnbail 'intrinsic' size in pixels, affects visual quality
	 * @param int $width | thumnbail element width in pixels
	 * @param string $aspect | css aspect-ratio e.g. '16/9';
	 * @return Dropzone
	 */
	public function thumbnail( int $render_width, int $width = 100, string $aspect = '1/1' ): Field {

		$this->thumbnail_w = $width;
		$this->thumbnail_render_w = $render_width ?? $width * 1.6;

		if( !empty( $aspect ) ){

			$params = explode( '/', $aspect );
			$this->thumbnail_aspect = $params[ 0 ] / ( $params[ 1 ] !== 0 ? $params[ 1 ] : 1);

		}

		return $this;
	}
	
	// public function base_dir( bool $base_dir ): Field {

	//  $this->base_dir = $base_dir;

	//  return $this;
	// }

	/**
	 * Summary of uploadTo
	 * @param string $base_dir | Static base dir, will not be saved. Default: Storage::url("") => "/storage/"
	 * @param ?string $upload_path | Can be null for the new item, if ->getItemID() is used. Then it defaults to something like 'temp_mediapath_0e9cfd'. Dropzone will do the rename routine further. If set to '', DZ will upload to "/storage/".
	 * @return Dropzone
	 */
	public function uploadTo(

		string $base_dir = '',
		string|Closure|null $upload_path
		
	): Field
	{
		$this->base_dir = 
			!empty( $base_dir ) ? $base_dir : $this->base_dir;
		
		if( is_callable( $upload_path ) ){

				$this->temp_upload_path = 'temp_media_' . $this->uid;
				$this->upload_path = $this->temp_upload_path;
				$this->upload_path_cb = Closure::fromCallable( $upload_path );

		} else {

			$this->upload_path = 
				!empty( $upload_path ) ? $upload_path : $this->upload_path;

		}

		
		// Log::debug(  $this->getResource()->getItemID()  );
		// Log::debug(  moonshineRequest()->getResource()->getItemID()  );
		
		
		//  Log::debug(  moonshineRequest()->getResource()  );
		//  Log::debug(  moonshineRequest()->getItemID()  );
		// Log::debug( moonshineRequest()->getResource()->getItem() );

		return $this;
	}

	protected function systemViewData(): array
	{
		//$this->uid = bin2hex( random_bytes(3) );
		// $this->csrf_token = csrf_token();
		return [
			...parent::systemViewData(),

			// 'uid' => $this->uid,
			'temp_upload_path__field_name' => 'temp_upload_path__' . $this->getColumn(),
			'temp_upload_path'  => $this->temp_upload_path,
			'disabled_until_save' => $this->disabled_until_save,

			'DZ_CFG' => [
				
				'csrf_token' => csrf_token(),

				'max_files'=> $this->max_files,
				'note'=>$this->note,
				'upload_on_drop'=> $this->upload_on_drop,
				'base_dir'=> $this->base_dir,
				'upload_path'=> $this->upload_path,

			
				'dropzone_grid_max_columns'=> $this->dropzone_grid_max_columns,
				'poster_mode'=> $this->poster_mode,
				'compact_mode'=> $this->compact_mode,
				
				'preview_element_style'=> $this->preview_element_style,

				'thumbnail_w'=> $this->thumbnail_w,
				'thumbnail_render_w'=> $this->thumbnail_render_w,
				'thumbnail_aspect'=> $this->thumbnail_aspect,
			],

			//'uid' =>  bin2hex( random_bytes(3) )
		];
	}
}
