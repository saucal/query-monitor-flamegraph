<table>
	<tr>
		<td colspan="2">
			<strong>Query Monitor Flamegraph</strong><br />
			This Query Monitor extension will add profiling framegraphs to Query Monitor via the <a href="https://pecl.php.net/package/xdebug">xdebug</a> or <a href="https://pecl.php.net/package/xhprof">xhprof</a> PHP extensions.
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
2. Enable and configure your tracer (See below for details details)
3. Install this plugin :)
4. Trigger a trace manually via `do_action( 'qm/flamegraph/trace/start', 'Some Label' );`.
5. Stop it with `do_action( 'qm/flamegraph/trace/stop' );`
6. View the trace under query monitor flamegraphs tab.
7. PS: If the tracer you're using supports auto start (eg: xdebug), your whole request will be traced, and display. **Warning: This is a dangerous setting**

## Supported Tracers

### XDebug

[Extension Link](https://pecl.php.net/package/xdebug)

This is the most complete tracer supported by the plugin, but under heavily recursive requests it may crash your environment (the trace file will be huge).

It's recommended that you set these settings for the tracer to be the most performant:

* xdebug.start_with_request=no
* xdebug.var_display_max_children=0
* xdebug.var_display_max_data=0
* xdebug.var_display_max_depth=0
* xdebug.use_compression=0
* xdebug.trace_format=1
* xdebug.trace_output_name="trace.%r"

### XHProf

[Extension Link](https://pecl.php.net/package/xhprof)

This tracer generates a much leaner trace, but also less detailed.

It's recommended that you set these settings for the tracer to be the most useful (it's already performant):

* xhprof.sampling_interval=100

## Note on FlameGraph

This fork uses [d3-flame-graph](https://github.com/spiermar/d3-flame-graph) to generate the flame graph, but in some cases the functionality of this framework may be limiting to access certain portions of the trace.
