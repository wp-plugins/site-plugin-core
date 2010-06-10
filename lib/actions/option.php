<?php 

if ( !class_exists('SiteUpgradeOptionActions') ) {
	
	class SiteUpgradeOptionActions extends SiteUpgradeAction {
		
		var $functions = array('option_update');
		
		/*
		 * Updates an option to the specified value
		 * @param $option str 
		 * @param $value mixed
		*/
		function option_update($option, $value) {	
			update_option($name, $value);
		}

		/*
		 * Return an array of available options that do not start with _
		 * @return array of widget options
		 */
		function options() {
			
			$options = array();
			global $wpdb;
			$results = $wpdb->get_results("SELECT option_name FROM $wpdb->options WHERE option_name NOT LIKE '\_%'", ARRAY_A);
			if ( $results ) {
				foreach ( $results as $option) {
					array_push($options, $option['option_name']);
				}
			}
			return $options;
		}		

		function admin( $elements ) {
			
			$selected = array();
			
			$this->h2o->loadTemplate('options.html');
			if ( array_key_exists('options', $_POST) ) {
				$selected = $_POST['options'];
			}
			
			$elements[__('Options')] = $this->h2o->render(array('options'=>$this->options(), 'selected'=>$selected));
			return $elements;
		}
		
		
		/*
		 * Extract information from $_POST and prepare an array in the following format
		 * array('option'=>$option, 'value'=>$value)
		 * @return array
		 */
		function process() {
			
			$result = array();
			if ( array_key_exists($_POST, 'options') && $_POST['options'] ) $options = $_POST['options'];
			
			foreach ( $options as $option ) { 
				$result[] = array('option'=>$option, 'value'=>Spyc::YAMLDump(get_option($option, '')));
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

			$this->h2o->loadTemplate('options.code');
			foreach ( $options as $option ) {
				$code .= $this->h2o->render($option);
			}
			
			return $code;
		}
		
	}

	new SiteUpgradeOptionActions();
	
}

?>