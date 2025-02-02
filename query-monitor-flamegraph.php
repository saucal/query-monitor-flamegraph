<?php

namespace QM_Flamegraph;

/**
 * Plugin Name: Query Monitor Flamegraph
 * Description: Add a flamegraph to Query Monitor
 * Author: Joe Hoyle | Human Made
 */

function register_qm_collector( array $collectors, \QueryMonitor $qm ) {
	include_once dirname( __FILE__ ) . '/inc/class-tracer.php';
	include_once dirname( __FILE__ ) . '/inc/class-tracer-xdebug.php';
	include_once dirname( __FILE__ ) . '/inc/class-tracer-xhprof.php';
	include_once dirname( __FILE__ ) . '/inc/class-flamegraph-leaf.php';
	include_once dirname( __FILE__ ) . '/inc/class-qm-collector.php';
	if ( defined( 'QM_FLAMEGRAPH_AUTOSTART' ) && QM_FLAMEGRAPH_AUTOSTART ) {
		Tracer::maybe_autostart();
	}
	$collectors['flamegraph'] = new QM_Collector();
	return $collectors;
}

add_filter( 'qm/collectors', 'QM_Flamegraph\register_qm_collector', 20, 2 );

function register_qm_output( array $output, \QM_Collectors $collectors ) {
	$collector = \QM_Collectors::get( 'flamegraph' );
	if ( ! $collector ) {
		return $output;
	}

	if ( empty( $collector->get_data() ) ) {
		return $output;
	}

	include_once dirname( __FILE__ ) . '/inc/class-qm-output-html.php';
	$output['flamegraph'] = new QM_Output_Html( $collector );
	return $output;
}

add_filter( 'qm/outputter/html', 'QM_Flamegraph\register_qm_output', 120, 2 );
