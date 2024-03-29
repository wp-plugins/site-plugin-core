<?php 

if ( !class_exists('SiteUpgradeAction') ) {
	
	/*
	 * Site Plugin Action is an abstract class that shows what functions
	 * are necessary to write an update action.
	 */
	class SiteUpgradeAction {
		
		var $functions = array();
		
		function __construct($template_path=null) {
			
			$template_path = is_null($template_path) ? dirname(__FILE__).'/lib/actions/admin/' : $template_path;
			
			# this makes Site Upgrades class aware of functions that this action
			# exposes. Exposing functions in this way makes them available to
			# Site Upgrades class to execute during an upgrade
			add_filter('site_upgrade_actions', array($this, 'register'));

			# register admin function of current action class with code that will
			# ouput upgrade generation admin interface
			add_filter('site_plugin_admin', array($this, 'admin'));

			# register generate function that will generate code for this action
			add_filter('site_upgrade_generate', array($this, 'generate'));
			
			$loader = new H2o_File_Loader($template_path);
			$this->h2o = new H2o(NULL, array('context'=>&$this, 'loader'=>$loader));
			$this->h2o->addFilter('WordpressFilters');
			
		}
		
		/*
		 * Register an instance of this class with the upgrade
		 * @param $upgrade instance of SiteUpgrade
		 * @return $upgrade
		 */
		function register($functions) {
			
			foreach ( $this->functions as $function ) {

				// add each function as callback
				$functions[$function] = array(&$this, $function);
				
			}
			
			return $functions;
		}
		
		/*
		 * Returns array of elements to be displayed in Create Version Admin
		 * Interface. Each element is html string.
		 * @return array
		 */
		function admin($elements) {
			
			return $elements;
		
		} 
		
		/*
		 * This method is called when upgrade script for an action is being generated.
		 * @param $args necessary for function's operation
		 * @return str of php code to add to upgrade script
		 */
		function generate( $code ) {
			$code = '';
			
			return $code;
		}
		
		/*
		 * This method performs the action and returns TRUE if successful or WP_Error on failure
		 * @param $args array of arguments
		 * @param $messages instance of WP_Error
		 * @return TRUE or WP_Error on failure
		 */
		function execute($args, &$messages) {
			return TRUE;
		}
		
	}
	
}

?>