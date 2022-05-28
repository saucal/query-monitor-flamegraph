<?php

namespace QM_Flamegraph;

/**
 * Plugin Name: Query Monitor Flamegraph
 * Description: Add a flamegraph to Query Monitor
 * Author: Joe Hoyle | Human Made
 */

function register_qm_collector( array $collectors, \QueryMonitor $qm ) {
	include_once dirname( __FILE__ ) . '/inc/class-qm-collector.php';
	$collectors['flamegraph'] = new QM_Collector;
	return $collectors;
}

add_filter( 'qm/collectors', 'QM_Flamegraph\register_qm_collector', 20, 2 );

function register_qm_output( array $output, \QM_Collectors $collectors ) {
	if ( $collector = \QM_Collectors::get( 'flamegraph' ) ) {
		include_once dirname( __FILE__ ) . '/inc/class-qm-output-html.php';
		$output['flamegraph'] = new QM_Output_Html( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'QM_Flamegraph\register_qm_output', 120, 2 );

add_action( 'wp_enqueue_scripts', 'QM_Flamegraph\enqueue_scripts', 999 );
add_action( 'admin_enqueue_scripts', 'QM_Flamegraph\enqueue_scripts', 999 );

function enqueue_scripts() {
	wp_register_script( 'qm-flamegraph-d3', 'https://d3js.org/d3.v7.js', array(), '7', true );
	wp_register_script( 'qm-flamegraph-d3-flamegraph-tooltip', 'https://cdn.jsdelivr.net/npm/d3-flame-graph@4.1.3/dist/d3-flamegraph-tooltip.min.js', array( 'qm-flamegraph-d3' ), '4.1.3', true );
	wp_register_script( 'qm-flamegraph-d3-flamegraph', 'https://cdn.jsdelivr.net/npm/d3-flame-graph@4.1.3/dist/d3-flamegraph.min.js', array( 'qm-flamegraph-d3', 'qm-flamegraph-d3-flamegraph-tooltip' ), '4.1.3', true );

	wp_enqueue_script( 'qm-flamegraph-d3-flamegraph' );
	wp_enqueue_style( 'qm-flamegraph-d3-flamegraph', 'https://cdn.jsdelivr.net/npm/d3-flame-graph@4.1.3/dist/d3-flamegraph.css', array(), '4.1.3' );
}
