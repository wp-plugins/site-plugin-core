<?php 

if ( !class_exists('SiteUpgradeWidgetActions') ) {
	
	class SiteUpgradeWidgetActions extends SiteUpgradeAction {
		
		var $functions = array('widget_option_exists', 'widget_option_update');
		
		/*
		 * Return true if option exists
		 * @param str $name of option to look up
		 * @return bool
		 */
		function widget_option_exists( $name ) {
			return (boolean) get_option( $name, FALSE );
		}
		
		/*
		 * Update widget option
		 * @param mixed $value to set the the option to
		 */
		function widget_option_update( $name, $value ) {
			update_option( $name, $value );
		}

		/*
		 * Return an array of Widget options. 
		 * Widget options start with widget_
		 * @return array of widget options
		 */
		function widget_options() {
			
			global $wpdb;
			$options = $wpdb->get_results("SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'widget_%'");
			if ( $options ) return $options;
			else return array();
			
		}
		
		function admin() {
			
			$selected = array();
			
			$this->h2o->loadTemplate('widget_options.html');
			if ( array_key_exists($_POST, 'widget_options') ) {
				$selected = $_POST['widget_options'];
			}
			
			return $this->h2o->render(array('options'=>$this->widget_options(), 'selected'=>$selected));
			
		}
		
		/*
		 * Extract information from $_POST and prepare an array in the following format
		 * array('option'=>$option, 'value'=>$value)
		 * @return array
		 */
		
		function process() {
			
			$result = array();
			if ( array_key_exists($_POST, 'widget_options') && $_POST['widget_options'] ) $options = $_POST['widget_options'];
			
			foreach ( $options as $option ) { 
				$result[] = array('option'=>$option, 'value'=>Spyc::YAMLDump(get_option($option, ''));
			}
			
			return $result;
			
		}
		
		/*
		 * This method is called when upgrade script for an action is being generated.
		 * @param $args necessary for function's operation
		 * @return str of php code to add to upgrade script
		 */
		function generate( $options ) {
			$code = '';

			$this->h2o->loadTemplate('widget_options.code');
			foreach ( $options as $option ) {
				$code .= $this->h2o->render($option);
			}
			
			return $code;
		}
		
	}
	
}

?>