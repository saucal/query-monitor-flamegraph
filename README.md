<table>
	<tr>
		<td colspan="2">
			<strong>Query Monitor Flamegraph</strong><br />
			This Query Monitor extension will add profiling framegraphs to Query Monitor via the <a href="https://pecl.php.net/package/xdebug">xdebug</a> PHP extension.
		</td>
	</tr>
	<tr>
		<td colspan="2">
			Forked from https://github.com/humanmade/query-monitor-flamegraph/.
		</td>
	</tr>
</table>

## Install Instructions

1. Have the [Query Monitor](https://github.com/johnbillion/query-monitor) plugin installed and activated.
2. Have the [xdebug](https://pecl.php.net/package/xdebug) PHP extension installed.
3. Have some php settings set so that performance is not botched by the tracing functionality:
   * xdebug.start_with_request=no
   * xdebug.var_display_max_children=0
   * xdebug.var_display_max_data=0
   * xdebug.var_display_max_depth=0
   * xdebug.use_compression=0
   * xdebug.trace_format=1
   * xdebug.trace_output_name="trace.%r"
4. Install this plugin :)
5. Trigger an xdebug trace.

## Note on FlameGraph

This fork uses [d3-flame-graph](https://github.com/spiermar/d3-flame-graph) to generate the flame graph, but in some cases the functionality of this framework may be limiting to access certain portions of the trace.
