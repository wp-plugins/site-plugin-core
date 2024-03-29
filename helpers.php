<?php 

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

if ( !function_exists('dump_options') ) {

	/*
	 * Return options serialized as YAML string
	 * @param array of names of options to dump
	 * @return str in yaml format
	 */
	function dump_options($options) {

		$storage = array();
		foreach ( $options as $option ) {

			$value = get_option($option);
			$storage[$option] = is_serialized($value) ? unserialize($value) : $value;
			
		}
		
		return Spyc::YAMLDump($storage);
		
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
		
		if ( $widgets ) {

			foreach ( $widgets as $widget => $data ) {
			
				update_option($widget, $data);
			
			}			
			
		}
		
	}
	
}

if ( !function_exists('update_options') ) {
	
	/*
	 * Update options from yaml string
	 * @param str yaml encoded associated array
	 */
	function update_options($yaml) {
		
		$options = Spyc::YAMLLoad($yaml);
		
		if ( $options ) {
			
			foreach ( $options as $option => $data ) {
				
				update_option($option, $data);
				
			}
			
		}
		
	}
	
}

?>