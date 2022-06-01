<?php
namespace QM_Flamegraph;

class Tracer {
	private static $tracer_class;

	protected static function maybe_define_tracer_class() {
		if ( function_exists( 'xdebug_stop_trace' ) ) {
			self::$tracer_class = Tracer_XDebug::class;
		}
	}

	public static function has_tracer() {
		self::maybe_define_tracer_class();
		return ! empty( self::$tracer_class );
	}

	public static function __callStatic( $name, $arguments ) {
		self::maybe_define_tracer_class();
		if ( ! self::has_tracer() ) {
			return;
		}
		return call_user_func_array( array( self::$tracer_class, $name ), $arguments );
	}
}
