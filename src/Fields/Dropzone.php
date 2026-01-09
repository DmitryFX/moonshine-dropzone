<?php


declare(strict_types=1);

namespace MoonShine\Dropzone\Fields;

use Closure;
use Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use MoonShine\AssetManager\Css;
use MoonShine\AssetManager\Js;
use MoonShine\Contracts\Core\DependencyInjection\FieldsContract;
use MoonShine\Dropzone\Helpers\DropzoneBag;
use MoonShine\Laravel\Traits\Request\HasPageRequest;
use MoonShine\UI\Fields\Field;
use MoonShine\Support\DTOs\AsyncCallback;
use MoonShine\Core\Traits\HasResource;
// use MoonShine\UI\Traits\Fields\WithDefaultValue;
// use MoonShine\Laravel\Http\Requests\MoonShineFormRequest;
// use MoonShine\Dropzone\Src\Helpers\DropzoneStorage;
// use Src\Helpers\DropzoneStorage;
// use MoonShine\Laravel\Events\ResourceSaved;

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

	// private mixed $resource = null;
	protected string $view = 'moonshine-dropzone::fields.dropzone';
	protected string $uid;

	private bool $disabled_until_save = false;

	private mixed $default_value;

	private int $max_files;
	private string $note;
	private bool $upload_on_drop;

	private string $disk;
	private string|Closure $upload_path;
	private string|null $temp_upload_path = null;
	protected null|Closure $upload_path_cb = null;
	
	private bool $compact_mode;
	private int $dropzone_grid_max_columns;
	private bool $reduce_empty_columns;

	private bool $poster_mode;
	private string $preview_element_style;

	private int $thumbnail_w;
	private float $thumbnail_render_w;
	private float $thumbnail_aspect;


	 public function __construct(
		Closure|string|null $label = null,
		?string $column = null,
		?Closure $formatted = null,
	) {
		
		parent::__construct( $label, $column, $formatted );
		// $this->filterable(false);

	}


	protected function booted(): void
	{
		parent::booted();
		$this->refreshAfterApply();

		$this->setLabel( $this->label );


		//? Apply defaults

		$this->max_files = 9999;
		$this->note = '';
		$this->upload_on_drop = true;
		$this->disk = 'public';
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
		 

		$this->setResource( moonshineRequest()->getResource() );

	}

	protected function assets(): array
	{
		return [
			// Css::make( asset( 'vendor/moonshine-dropzone/css/dropzone_field.css' ) ),
			// Js::make( asset( 'vendor/moonshine-dropzone/js/dropzone.min.js' ) ),
		];
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

	
	protected function resolveValue(): mixed{

		$out_val = null;

		if( empty( $this->value ) ){

			$out_val = '';

		} else{
			
			// DropzoneBag::get( "{$this->getColumn()}_path_is_temp" ) === true
			// is_null( $this->getResource()->getItemID() )
			if( !empty( request("{$this->getColumn()}_temp_path") ) ){
				
				//$out_val = $this->Convert_Temp_Paths_To_Final( $this->value );
				// $out = $new;// preg_replace( '/temp_dz_upload_.{6}/i', 'FFFFFFFFFF', $out );

			}

			$out_val = is_string( $this->value ) ? $this->value : implode( ',', $this->value );

		}
		
		// Log::debug( $this->getResource()->getItem()?->exists ? 'exists' : 'not exists' );
		// Log::debug( $this->getResource()->getItem()?->wasRecentlyCreated ? 'rec Created' : 'not rec Created' );

		//false && stristr( $out, 'temp_dz_upload_' ) && 	!is_null ( $this->getResource())->getItemID()

		return $out_val;

	}

	protected function dot_notation_hack( $dot ){

		return !stristr( $dot, '.' ) ?
			$dot :
			preg_replace_callback(
				'/(?<=\.)(\d+)(?=\.)/',
				fn($val) => max(0, intval($val[1]) - 1),
				$dot
			);

	}

   
	protected function resolveOnApply(): ?Closure
	{

		return function (mixed $item): mixed {
	
			// Log::debug( 'resolveOnApply' );
			// Log::debug( gettype($item) );
			// Log::debug( $this->isCanApply() );
			
			$value = $this->getRequestValue();
			$column = $this->getColumn();
			$result = null;

			if( !empty( $value ) ){

				if( $this->max_files === 1 ){
					
					$result = !stristr( $value, ',' ) ?
						$value :
						array_slice( explode( ',', $value ), 0, $this->max_files );


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

			data_set($item, $column, $result );

			// Log::debug( 'Res ID:__________'  );
			// Log::debug( moonshineRequest()->getResource()->getItemID()  );

			// Log::debug( $column );
			// Log::debug(  request() );
			// Log::debug( request( "{$column}_temp_path" )  );
			//? Prepare data for after-model-create actions 
			if(
				!empty( request( "{$column}_temp_path" ) ) &&
				empty( moonshineRequest()->getResource()->getItemID() )
			){


				$model_name = class_basename( moonshineRequest()->getResource()->getModel() );
				$name_dot = $this->dot_notation_hack( $this->getNameDot() );
				$temp_path = request( "{$this->getColumn()}_temp_path" );

				// Log::debug( $name_dot  );
				
				$bag_data = [
					$model_name => [
						$name_dot  => [
							'disk' => $this->disk,
							'temp_path' => $temp_path,
							'path_callback' => $this->upload_path_cb
						]
					]
				];

				DropzoneBag::set_recursive( $bag_data );

				// DropzoneBag::set_dot( "$model_name.{$name_dot}.disk", $this->disk );
				// DropzoneBag::set_dot( "$model_name.{$name_dot}.temp_path", $temp_path );
				// DropzoneBag::set_dot( "$model_name.{$name_dot}.path_callback", $this->upload_path_cb );


				
				// DropzoneBag::set_to_array( "models", $model_name, $model_name );
				// DropzoneBag::set_to_array( 'base_dirs', $name_dot, $this->disk );
				// DropzoneBag::set_to_array( 'temp_paths', $name_dot, $temp_path );
				// DropzoneBag::set_to_array( 'path_callbacks', $name_dot, $this->upload_path_cb );
			
			}
			
			// DropzoneBag::set( "$name_dot.temp_path", $temp_path );
			// DropzoneBag::set( "$name_dot.upload_path_cb", $this->upload_path_cb );

			return $item;
		};
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
	
	public function disk(

		string $disk = 'public'
		
	): Field
	{
		$this->disk = $disk;

		return $this;
	}

	/**
	 * Summary of uploadTo
	 * @param string $base_dir | Static base dir, will not be saved. Default: Storage::url("") => "/storage/"
	 * @param ?string $upload_path | Can be null for the new item, if ->getItemID() is used. Then it defaults to something like 'temp_mediapath_0e9cfd'. Dropzone will do the rename routine further. If set to '', DZ will upload to "/storage/".
	 * @return Dropzone
	 */
	public function uploadTo(

		string|Closure|null $upload_path = ''
		
	): Field
	{
		
		if( is_callable( $upload_path ) ){

			if( is_null( $this->getResource()->getItemID() ) ){

				//Log::debug( "{$this->getColumn()} uploadTo" );

				$this->uid = bin2hex( random_bytes( 3 ) );

				$this->temp_upload_path = 'dropzone_temp/' . $this->uid;
				$this->upload_path = $this->temp_upload_path;

				$this->upload_path_cb = Closure::fromCallable( $upload_path );
				

			} else {

				$this->upload_path = $upload_path( $this->getResource()->getItem() );
			}

		} else{

			$this->upload_path = 
				!empty( $upload_path ) ? $upload_path : $this->upload_path;

		}

		



		return $this;
	}

	

	protected function systemViewData(): array
	{
		// Log::debug($this->temp_upload_path);
		//$this->uid = bin2hex( random_bytes(3) );
		// $this->csrf_token = csrf_token();
		return [
			...parent::systemViewData(),

			// 'uid' => $this->uid,
			'temp_upload_path__field_name' => $this->getColumn() . '_temp_path',
			'temp_upload_path'  => $this->temp_upload_path,
			'disabled_until_save' => $this->disabled_until_save,

			'DZ_CFG' => [
				
				'csrf_token' => csrf_token(),

				'max_files'=> $this->max_files,
				'note'=>$this->note,
				'upload_on_drop'=> $this->upload_on_drop,
				'disk'=> $this->disk,
				'disk_url'=> Storage::disk( $this->disk )->url('/'),
				'upload_path'=> $this->upload_path,

			
				'dropzone_grid_max_columns'=> $this->dropzone_grid_max_columns,
				'poster_mode'=> $this->poster_mode,
				'compact_mode'=> $this->compact_mode,
				
				'preview_element_style'=> $this->preview_element_style,

				'thumbnail_w'=> $this->thumbnail_w,
				'thumbnail_render_w'=> $this->thumbnail_render_w,
				'thumbnail_aspect'=> $this->thumbnail_aspect,
			],

		];
	}
}
