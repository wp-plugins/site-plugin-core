<?php 

if ( class_exists('h2o') ) {
	
	h2o::addFilter('WordpressFilters');
	class WordpressFilters extends FilterCollection {
		
		/*
		 * Wordpress localization filter to use inside of h2o templates
		 * @param str test to localize
		 * @return str localized string
		 */
		function l($text) {
			return __($text);
		}
		
	}
	
}

?>