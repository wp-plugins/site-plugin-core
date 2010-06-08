<?php 

if ( !class_exists('SiteUpgradeAction') ) {
	
	/*
	 * Site Plugin Action is an abstract class that shows what functions
	 * are necessary to write an update action.
	 */
	class SiteUpgradeAction {
		
		var $functions = array();
		
		function __construct($template_path=dirname(__FILE__).'/actions/admin/') {
			
			# add filter that will register current action with the Site Upgrades
			add_filter('site_upgrade_actions', array($this, 'register'));
			
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
		 * Returns admin html
		 * @return str html
		 */
		function admin() {
			
			return $html = '';
		
		} 
		
		/*
		 * Process $_POST and return an array of arguments that's 
		 * approprate for $this->generate
		 * @return array of arguments
		 */
		function process() {
			return $args = array();
		}
		
		/*
		 * This method is called when upgrade script for an action is being generated.
		 * @param $args necessary for function's operation
		 * @return str of php code to add to upgrade script
		 */
		function generate( $args ) {
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