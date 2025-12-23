<?php


declare(strict_types=1);

namespace MoonShine\Dropzone\Fields;

use Closure;
use Illuminate\Support\Facades\Log;
use MoonShine\AssetManager\Css;
use MoonShine\AssetManager\Js;
use MoonShine\UI\Fields\Field;
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

	// use WithDefaultValue;

	protected string $view = 'moonshine-dropzone::fields.dropzone';

	private mixed $default_value;

	private int $max_files;
	private string $note;
	private bool $upload_on_drop;
	private string $base_dir;
	private string $upload_path;

	private bool $minimize_component_width;
	private int $dropzone_grid_max_columns;
	private bool $reduce_empty_columns;

	private bool $single_preview_maximize;
	private string $preview_element_style;

	private int $thumbnail_w;
	private float $thumbnail_render_w;
	private float $thumbnail_aspect;


	
	 public function __construct(
		Closure|string|null $label = null,
		?string $column = null,
		?Closure $formatted = null,
	) {
		
		$this->setLabel( $label );


		//? Apply defaults
		$this->max_files = 9999;
		$this->note = '';
		$this->upload_on_drop = true;
		$this->base_dir = Storage::url("");
		$this->upload_path = '';

		$this->minimize_component_width = false;
		$this->dropzone_grid_max_columns = 3;
		$this->reduce_empty_columns = false;

		$this->single_preview_maximize = false;
		$this->preview_element_style = '';

		$this->thumbnail_w = 100;
		$this->thumbnail_render_w = 160;
		$this->thumbnail_aspect = 1;


		parent::__construct( $label, $column, $formatted );
		//debug($label);
		// $this->default();
		// debug($this->default_value);
	}

	protected function assets(): array
	{
		return [
			Css::make( asset( 'vendor/moonshine-dropzone/css/dropzone_field.css' ) ),
			Js::make( asset( 'vendor/moonshine-dropzone/js/dropzone.min.js' ) ),
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

			data_set($item, $this->getColumn(), $result );

			return $item;
		};
	}
	
	// public function note( string $note ): Field {

	// 	$this->note = $note;

	// 	return $this;
	// }

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
	 * @param int $max_columns | max thumbnails columns
	 * @param bool $reduce_empty_columns | allows to hide empty columns or keep e.g. 1 
	 * @param bool $minimize_component_width | component will shrink to contents
	 * @return Dropzone
	 */
	public function layout( int $max_columns = 3, bool $reduce_empty_columns = false, bool $minimize_component_width = false ): Field {

		$this->dropzone_grid_max_columns = $max_columns;
		$this->reduce_empty_columns = $reduce_empty_columns;
		$this->minimize_component_width = $minimize_component_width;

		return $this;
	}

	/**
	 * If Dropzone has only one item, make it fill the dropzone area.
	 * 
	 * @param bool $value
	 * @return Dropzone
	 */
	public function singlePreviewMaximize( bool $value = false ): Field {

		$this->single_preview_maximize = $value;

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
	 * @param mixed $base_dir
	 * @param string $upload_path
	 * @return Dropzone
	 */
	public function uploadTo(

		?string $base_dir = '',
		string $upload_path = ''
		
	): Field
	{

		$this->base_dir = empty( $base_dir ) ? $this->base_dir : $base_dir;
		$this->upload_path = $upload_path;

		return $this;
	}

	protected function systemViewData(): array
	{
		//$this->uid = bin2hex( random_bytes(3) );
		// $this->csrf_token = csrf_token();
		return [
			...parent::systemViewData(),
			'DZ_CFG' => [

				'csrf_token' => csrf_token(),

				'max_files'=> $this->max_files,
				'note'=>$this->note,
				'upload_on_drop'=> $this->upload_on_drop,
				'base_dir'=> $this->base_dir,
				'upload_path'=> $this->upload_path,

				'minimize_component_width'=> $this->minimize_component_width,
				// 'dropzone_layout'=> $this->dropzone_layout,
				'dropzone_grid_max_columns'=> $this->dropzone_grid_max_columns,
				'reduce_empty_columns'=> $this->reduce_empty_columns,

				'single_preview_maximize'=> $this->single_preview_maximize,
				'preview_element_style'=> $this->preview_element_style,

				'thumbnail_w'=> $this->thumbnail_w,
				'thumbnail_render_w'=> $this->thumbnail_render_w,
				'thumbnail_aspect'=> $this->thumbnail_aspect,
			],

			//'uid' =>  bin2hex( random_bytes(3) )
		];
	}
}
