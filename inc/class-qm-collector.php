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

	public $id = 'flamegraph';
	protected $data = array();

	public function name() {
		return __( 'Flamegraph', 'query-monitor' );
	}

	public function __construct() {
		if ( ! function_exists( 'xdebug_stop_trace' ) ) {
			return;
		}
	}

	public function process() {
		if ( ! function_exists( 'xdebug_stop_trace' ) ) {
			return;
		}

		$trace_file = xdebug_stop_trace();

		$this->data = $this->process_xdebug_trace( $trace_file );
	}

	/**
	 * Adapted from https://github.com/brendangregg/FlameGraph/blob/master/stackcollapse-xdebug.php.
	 */
	protected function process_xdebug_trace( $filename ) {

		$handle = fopen( $filename, 'r' );

		if ( ! $handle ) {
			return array();
		}

		// Loop till we find TRACE START.
		while ( $l = fgets( $handle ) ) {
			if ( 0 === strpos( $l, 'TRACE START' ) ) {
				break;
			}
		}

		$root      = new Flamegraph_Leaf( '-1', '{main}', 0 );
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
			list( $level, $fn_no, $is_exit, $time ) = $parts;

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

			list( $level, $fn_no, $is_exit, $time, $mem_usage, $func_name, $fn_type, $inc_file ) = $parts;

			if ( apply_filters( 'qm_flamegraph_append_filenames', true ) ) {
				if ( in_array( $func_name, array( 'require', 'require_once', 'include', 'include_once' ) ) ) {
					$func_name = "{$func_name} ({$inc_file})";
				}
			}

			if ( $func_name === '{main}' ) {
				$func_name = "{$inc_file}";
			}

			if ( $level == 1 ) {
				$item = $root->add_children( $fn_no, $func_name, $time );
			} else {
				$item = end( $stack )->add_children( $fn_no, $func_name, $time );
			}
			$stack[] = $item;
		}

		if ( ! empty( $stack ) ) {
			while ( ! empty( $stack ) ) {
				$finishing = array_pop( $stack );
				$finishing->end( $time );
			}
			$root->end( $time );
		}

		fclose( $handle );
		unlink( $filename );

		return $root;
	}

}
