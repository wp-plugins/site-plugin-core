<?php 

// include spyc - yaml parsing library - http://code.google.com/p/spyc/
if ( !class_exists('Spyc') ) require_once(WP_PLUGIN_DIR.'/site-plugin-core/lib/spyc.php');

// include h2o - template parcing library
if ( !class_exists('H2o') ) require_once(WP_PLUGIN_DIR.'/site-plugin-core/lib/h2o.php');

if ( !function_exists('dump_sidebars_widgets') ) {

	/*
	 * Return sidebars_widgets option serialized as YAML string
	 * @return str in yaml format
	 */
	function dump_sidebars_widgets(){
		
		return Spyc::YAMLDump(get_option('sidebars_widgets'));
		
	}
	
}

if ( !function_exists('dump_widgets') ) {
	
	/*
	 * Return widgets options serialized as YAML string
	 * @return str in yaml format
	 */
	function dump_widgets() {
		
		global $wpdb;
		
		$result = array();
		
		$widgets = $wpdb->get_results("SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'widget_%'");
		
		foreach ( $widgets as $widget ) {
			
			$result[$widget->option_name] = get_option($widget->option_name);
			
		}

		return Spyc::YAMLDump($result);
	}
	
}

if ( !function_exists('update_sidebars_widgets') ) {
	
	/*
	 * Update sidebars_widgets option to value provided by yaml string
	 * @param str in yaml format
	 */
	function update_sidebars_widgets($yaml) {
		
		update_option('sidebars_widgets', Spyc::YAMLLoad($yaml) );
		
	}
	
}

if ( !function_exists('update_widgets_options') ) {
	
	/*
	 * Update widget options from yaml string
	 * @param str yaml syntax string
	 */
	function update_widgets_options($yaml) {
		
		$widgets = Spyc::YAMLLoad($yaml);
		
		foreach ( $widgets as $widget => $data ) {
			
			update_option($widget, $data);
			
		}
		
	}
	
}

if ( !function_exists('file_from_template') ) {
	
	/*
	 * Create a file from template
	 * @param str path to the template
	 * @param str path to the output
	 * @param array with template substitutes
	 * @return bool if file was created successfully
	 */
	 function file_from_template($template, $destination, $values) {
	 	
	 	if ( !file_exists($template) || !file_exists(dirname($destination)) ) return false;
	 	
	 	$contents = new h2o($template);
	 	$output_file = fopen($destination, 'w');
		fwrite($output_file, $output = $contents->render($values));
		
		return fclose($output_file);
	 	
	 }
	 
}

?>