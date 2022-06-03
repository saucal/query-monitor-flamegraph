<?php
namespace QM_Flamegraph;

class Tracer_XHProf {
	protected static $current_trace;
	protected static $autostarted = false;
	private $data                 = array();
	public $label                 = null;
	public $trace                 = null;

	public function __construct( $label ) {
		$this->label = $label;
		\xhprof_sample_enable();
	}
	public static function is_tracing() {
		return isset( self::$current_trace );
	}
	public static function start_trace( $label ) {
		self::$current_trace = new self( $label );
		return self::$current_trace;
	}
	public static function maybe_autostart() {
		self::$autostarted = self::start_trace( '{main}' );
	}
	public static function auto_started() {
		return is_object( self::$autostarted );
	}
	public static function get_auto_started_trace() {
		return self::$autostarted;
	}
	public static function stop_trace() {
		$data = \xhprof_sample_disable();
		self::$current_trace->set_data( $data );
		self::$current_trace = null;
	}

	public function set_data( $data ) {
		$this->data = $data;
	}

	/**
	 * Accepts [ Node, Node ], [ main, wp-settings, sleep ]
	 */
	protected function add_children_to_nodes( $childs, $children, $parent = null ) {
		$node       = count( $childs ) > 0 ? end( $childs ) : null;
		$this_child = array_shift( $children );
		$time       = (int) ini_get( 'xhprof.sampling_interval' );

		if ( ! $time ) {
			$time = 100000;
		}
		if ( ! $node || ! $node->is( $this_child ) ) {
			$node = $parent->add_children( $this_child, $this_child, $parent->get_start() );
		}
		$node->add_time( $time / 1000000 );
		if ( count( $children ) >= 1 ) {
			$this->add_children_to_nodes( $node->children, $children, $node );
		}

	}

	public function process() {
		$max_depth = apply_filters( 'qm_flamegraph_max_depth', defined( 'QM_FLAMEGRAPH_MAX_DEPTH' ) ? QM_FLAMEGRAPH_MAX_DEPTH : 30 );

		$root = new Flamegraph_Leaf( 'main()', $this->label, 0 );

		foreach ( $this->data as $time => $call_stack ) {
			$call_stack = array_slice( explode( '==>', $call_stack ), 0, $max_depth + 1 );

			$this->add_children_to_nodes( array( $root ), $call_stack );
		}

		$this->trace = $root;
	}
}
