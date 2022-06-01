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
		if ( ! Tracer::has_tracer() ) {
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
		if ( Tracer::is_tracing() ) {
			return;
		}
		$this->tracing[] = Tracer::start_trace( $label );
	}

	public function stop() {
		if ( ! Tracer::is_tracing() ) {
			return;
		}
		if ( empty( $this->tracing ) ) {
			return;
		}
		Tracer::stop_trace();
	}

	public function process() {
		if ( Tracer::is_tracing() ) {
			if ( empty( $this->tracing ) && Tracer::auto_started() ) {
				// Tracing was started with the request
				$this->tracing[] = Tracer::get_auto_started_trace();
			}
			Tracer::stop_trace();
		}
		foreach ( $this->tracing as $i => $trace ) {
			$trace->process();
		}

		$this->data = $this->tracing;
	}

}
