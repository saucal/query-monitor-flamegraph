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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
GNU General Public License for more details.

*/

class QM_Output_Html extends \QM_Output_Html {

	public function __construct( \QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 110 );
	}

	public function output() {
		$data = $this->collector->get_data();
		?>

		<div class="qm" id="qm-flamegraph">
			<?php
			foreach ( $data as $i => $trace ) {
				echo '<div id="qm-flamegraph-graph-' . $i . '"></div>';
			}
			?>
			<script type="text/javascript">
				var data = <?php echo wp_json_encode( $data ); ?>
			</script>
				<style>
				.d3-flame-graph-tip {
					z-index: 99999;
				}
			</style>

			<!-- D3.js -->
			<script src="https://d3js.org/d3.v7.js" charset="utf-8"></script>

			<!-- d3-flamegraph -->
			<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/d3-flame-graph@4.1.3/dist/d3-flamegraph.css">
			<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/d3-flame-graph@4.1.3/dist/d3-flamegraph.js"></script>
			<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/d3-flame-graph@4.1.3/dist/d3-flamegraph-tooltip.js"></script>

			<script type="text/javascript">
				for( var i in data ) {
					var trace = data[i];

					var chart = flamegraph()
						.width(1280)
						.cellHeight(18)
						.transitionDuration(750)
						.minFrameSize(5)
						.transitionEase(d3.easeCubic)
						.sort(true)
						//Example to sort in reverse order
						//.sort(function(a,b){ return d3.descending(a.name, b.name);})
						.title("")
						.selfValue(false)
						.setColorMapper((d, originalColor) =>
							d.highlight ? "#6aff8f" : originalColor);

					// Example on how to use custom a tooltip.
					var tip = flamegraph.tooltip.defaultFlamegraphTooltip()
					.text(d => "name: " + d.data.name + ", value: " + d.data.value);
					chart.tooltip(tip);

					// Example on how to use searchById() function in flamegraph.
					// To invoke this function after loading the graph itself, this function should be registered in d3 datum(data).call()
					// (See d3.json invocation in this file)


					// Example on how to use custom labels
					// var label = function(d) {
					//  return "name: " + d.name + ", value: " + d.value;
					// }
					// chart.label(label);

					// Example of how to set fixed chart height
					// chart.height(540);

					d3.select("#qm-flamegraph-graph-" + i)
						.datum(trace.trace)
						.call(chart)
				}

			</script>
		</div>
		<?php

	}

}
