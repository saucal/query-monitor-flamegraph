<?php
namespace QM_Flamegraph;

class Tracer_XDebug {
	protected static $current_trace;
	private $start_lvl = 1;
	private $file      = 1;
	public $label      = null;
	public $trace      = null;

	public function __construct( $label, $trace_file = null, $start_lvl = null ) {
		$this->label = $label;
		if ( is_null( $start_lvl ) ) {
			$this->start_lvl = count( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ) ) - 7;
		} else {
			$this->start_lvl = $start_lvl;
		}

		if ( is_null( $trace_file ) ) {
			$this->file = \xdebug_start_trace( null, XDEBUG_TRACE_COMPUTERIZED );
		} else {
			$this->file = $trace_file;
		}
	}
	public static function is_tracing() {
		return false !== \xdebug_get_tracefile_name();
	}
	public static function start_trace( $label ) {
		self::$current_trace = new self( $label );
		return self::$current_trace;
	}
	public static function auto_started() {
		return ini_get( 'xdebug.start_with_request' ) === '1';
	}
	public static function get_auto_started_trace() {
		self::$current_trace = new self( '{main}', \xdebug_get_tracefile_name(), 1 );
		return self::$current_trace;
	}
	public static function stop_trace() {
		\xdebug_stop_trace();
	}

	public function process() {
		$append_filenames = apply_filters( 'qm_flamegraph_append_filenames', defined( 'QM_FLAMEGRAPH_APPEND_FILENAMES' ) ? QM_FLAMEGRAPH_APPEND_FILENAMES : true );
		$max_depth        = apply_filters( 'qm_flamegraph_max_depth', defined( 'QM_FLAMEGRAPH_MAX_DEPTH' ) ? QM_FLAMEGRAPH_MAX_DEPTH : 30 );

		$handle = fopen( $this->file, 'r' );

		if ( ! $handle ) {
			return array();
		}

		// Loop till we find TRACE START.
		while ( $l = fgets( $handle ) ) {
			if ( 0 === strpos( $l, 'TRACE START' ) ) {
				break;
			}
		}

		$time = 0;
		// Loop till we find TRACE START.
		if ( $this->start_lvl > 1 ) {
			while ( $l = fgets( $handle ) ) {
				$is_level = is_numeric( substr( $l, 0, strpos( $l, "\t" ) ) );
				if ( ! $is_level ) {
					continue;
				}

				$parts                                  = explode( "\t", $l );
				list( $level, $fn_no, $is_exit, $time ) = $parts;

				if ( (int) $level === $this->start_lvl ) {
					break;
				}
			}
		}

		$root      = null;
		$stack     = array();
		$last_time = null;
		$rows      = 0;

		while ( $l = fgets( $handle ) ) {
			// Check if we are at the beginning of a line
			$is_level = is_numeric( substr( $l, 0, strpos( $l, "\t" ) ) );
			if ( ! $is_level ) {
				continue;
			}

			$rows++;
			$is_eo_trace = false !== strpos( $l, 'TRACE END' );

			if ( $is_eo_trace ) {
				break;
			}

			$parts                                       = explode( "\t", $l );
			list( $real_level, $fn_no, $is_exit, $time ) = $parts;

			$level = $real_level - $this->start_lvl + 1;

			if ( $level < 1 ) {
				break; // We're below the starting level
			}

			$last_time = $time;

			if ( $is_exit ) {
				if ( ! end( $stack )->is( $fn_no ) ) {
					continue;
				}
				$finishing = array_pop( $stack );
				$finishing->end( $time );
				continue;
			}

			list( $real_level, $fn_no, $is_exit, $time, $mem_usage, $func_name, $fn_type, $inc_file, $filename ) = $parts;

			if ( count( $stack ) >= $max_depth ) {
				continue;
			}

			if ( $append_filenames ) {
				if ( in_array( $func_name, array( 'require', 'require_once', 'include', 'include_once' ) ) ) {
					$func_name = "{$func_name} ({$inc_file})";
				}
			}

			if ( '{main}' === $func_name ) {
				$func_name = "{$filename}";
			}

			if ( 1 === $level ) {
				if ( ! isset( $root ) ) {
					$root = new Flamegraph_Leaf( '-1', $this->label, $time );
				}
				$item = $root->add_children( $fn_no, $func_name, $time );
			} else {
				$item = end( $stack )->add_children( $fn_no, $func_name, $time );
			}
			$stack[] = $item;
		}

		if ( ! empty( $stack ) ) {
			while ( ! empty( $stack ) ) {
				$finishing = array_pop( $stack );
				$finishing->end( $last_time );
			}
		}
		$root->end( $last_time );

		if ( 'xdebug_stop_trace' === $func_name ) {
			$root->pop();
		}

		fclose( $handle );
		// unlink( $this->file );

		$this->trace = $root;

		return $root;
	}
}
