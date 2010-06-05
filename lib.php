<?php 

// include spyc - yaml parsing library - http://code.google.com/p/spyc/
if ( !class_exists('Spyc') ) require_once( dirname(__FILE__).'/lib/spyc.php');

// include h2o - template parcing library
if ( !class_exists('H2o') ) require_once(dirname(__FILE__).'/lib/h2o.php');

// include SitePlugin custom h2o filters
require_once(dirname(__FILE__).'/lib/h2o_filters.php');

?>