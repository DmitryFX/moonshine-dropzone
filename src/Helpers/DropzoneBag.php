<?php

namespace MoonShine\Dropzone\Helpers;

class DropzoneBag{

	public static array $storage = [];

	// public static function set( string $key, $value ) : void {
		
	// 	self::$storage[ $key ] = $value;

	// }

	// public static function set_dot( string $key, $value ) : void {
		
	// 	data_set( self::$storage, $key, $value );//[ $key ] = $value;

	// }
	
	public static function set_recursive( /* string $array_name, string|null $key,*/ $value ) : void {
		
		//self::$storage[ $array_name ][ $key ] = $value;
		self::$storage = array_merge_recursive( self::$storage, $value );

	}
	
	// public static function get_array( string $array_name, string $key, $value ) : array|null {
		
	// 	return self::$storage[ $array_name ] ?? null;

	// }
	
	// public static function set_immutable( string $key, $value ) : void {
		
	// 	self::$storage[ $key ] ??= $value;

	// }
	
	// public static function unset( string $key ) : void {
		
	// 	self::$storage[ $key ] = null;

	// }

	public static function get( string $key ) : mixed {
		
		return self::$storage[ $key ] ?? false;

	}

	public static function all() : array {
		
		return self::$storage;

	}


}






