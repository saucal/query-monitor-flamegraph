<?php
namespace QM_Flamegraph;

class Flamegraph_Leaf {
	public $name;
	protected $fn_no;
	protected $start;
	protected $end;
	public $value;
	public $children;
	public function __construct( $fn_no, $name, $start ) {
		$this->fn_no    = $fn_no;
		$this->name     = $name;
		$this->start    = number_format( $start, 6, '.', '' );
		$this->children = array();
	}

	public function add_children( $fn_no, $name, $start ) {
		$item             = new Flamegraph_Leaf( $fn_no, $name, $start );
		$this->children[] = $item;
		return $item;
	}

	public function end( $time ) {
		$this->end   = $time;
		$this->value = (int) ( number_format( $this->end - $this->start, 6, '.', '' ) * 1000000 );
	}

	public function get_end() {
		return $this->end;
	}

	public function is( $fn_no ) {
		return $this->fn_no === $fn_no;
	}
}
