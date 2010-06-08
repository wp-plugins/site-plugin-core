<?php 

// include spyc - yaml parsing library - http://code.google.com/p/spyc/
if ( !class_exists('Spyc') ) require_once( dirname(__FILE__).'/lib/spyc.php');

// include h2o - template parcing library
if ( !class_exists('H2o') ) require_once(dirname(__FILE__).'/lib/h2o.php');

// include SitePlugin custom h2o filters
require_once(dirname(__FILE__).'/lib/h2o_filters.php');

// include Site Upgrade Actions
include_once(dirname(__FILE__).'/upgrade-action.php');
include_once(dirname(__FILE__).'/lib/actions/option.php');
include_once(dirname(__FILE__).'/lib/actions/category.php');
include_once(dirname(__FILE__).'/lib/actions/post.php');
include_once(dirname(__FILE__).'/lib/actions/widget.php');
?>