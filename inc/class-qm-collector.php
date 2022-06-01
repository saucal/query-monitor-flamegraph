<?php

namespace QM_Flamegraph;

/*
Copyright 2009-2015 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Collector extends \QM_Collector {

	public $id         = 'flamegraph';
	protected $data    = array();
	protected $tracing = array();
	protected $traced  = array();

	public function name() {
		return __( 'Flamegraph', 'query-monitor' );
	}

	public function __construct() {
		if ( ! function_exists( 'xdebug_stop_trace' ) ) {
			return;
		}

		add_action( 'qm/flamegraph/trace/start_single', array( $this, 'start_single' ), PHP_INT_MAX );
		add_action( 'qm/flamegraph/trace/start', array( $this, 'start_trace' ), PHP_INT_MAX );
		add_action( 'qm/flamegraph/trace/stop', array( $this, 'stop' ), ~PHP_INT_MAX );
	}

	public function start_single( $label ) {
		$key = sanitize_key( $label );
		if ( isset( $this->traced[ $key ] ) ) {
			return;
		}
		$this->traced[ $key ] = true;
		$this->start( $label );
	}

	public function start_trace( $label ) {
		$this->start( $label );
	}

	protected function start( $label = '' ) {
		if ( false !== \xdebug_get_tracefile_name() ) {
			return;
		}
		$start_lvl       = count( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ) ) - 3;
		$this->tracing[] = array(
			'label'     => $label,
			'file'      => \xdebug_start_trace( null, XDEBUG_TRACE_COMPUTERIZED ),
			'start_lvl' => $start_lvl,
		);
	}

	public function stop() {
		if ( false === \xdebug_get_tracefile_name() ) {
			return;
		}
		if ( empty( $this->tracing ) ) {
			return;
		}
		\xdebug_stop_trace();
	}

	public function process() {
		if ( ! function_exists( 'xdebug_stop_trace' ) ) {
			return;
		}

		if ( false !== \xdebug_get_tracefile_name() ) {
			if ( empty( $this->tracing ) ) {
				// Tracing was started with the request
				$this->tracing[] = array(
					'label'     => '{main}',
					'file'      => \xdebug_get_tracefile_name(),
					'start_lvl' => 1,
				);
			}
			\xdebug_stop_trace();
		}

		foreach ( $this->tracing as $i => $trace ) {
			$this->tracing[ $i ] = $this->process_xdebug_trace( $trace );
		}

		$this->data = $this->tracing;
	}

	/**
	 * Adapted from https://github.com/brendangregg/FlameGraph/blob/master/stackcollapse-xdebug.php.
	 */
	protected function process_xdebug_trace( $trace ) {

		$trace = (object) $trace;

		$handle = fopen( $trace->file, 'r' );

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
		if ( $trace->start_lvl > 1 ) {
			while ( $l = fgets( $handle ) ) {
				$is_level = is_numeric( substr( $l, 0, strpos( $l, "\t" ) ) );
				if ( ! $is_level ) {
					continue;
				}

				$parts                                  = explode( "\t", $l );
				list( $level, $fn_no, $is_exit, $time ) = $parts;

				if ( (int) $level === $trace->start_lvl ) {
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

			$parts                                  = explode( "\t", $l );
			list( $real_level, $fn_no, $is_exit, $time ) = $parts;

			$level = $real_level - $trace->start_lvl + 1;

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

			if ( count( $stack ) >= 5 ) { // TODO: Make Depth Configurable
				continue;
			}

			list( $real_level, $fn_no, $is_exit, $time, $mem_usage, $func_name, $fn_type, $inc_file, $filename ) = $parts;

			if ( apply_filters( 'qm_flamegraph_append_filenames', true ) ) {
				if ( in_array( $func_name, array( 'require', 'require_once', 'include', 'include_once' ) ) ) {
					$func_name = "{$func_name} ({$inc_file})";
				}
			}

			if ( $func_name === '{main}' ) {
				$func_name = "{$filename}";
			}

			if ( $level == 1 ) {
				if ( ! isset( $root ) ) {
					$root = new Flamegraph_Leaf( '-1', $trace->label, $time );
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

		if ( $func_name === 'xdebug_stop_trace' ) {
			$root->pop();
		}

		fclose( $handle );
		// unlink( $trace->file );

		$trace->trace = $root;

		return $trace;
	}

}
