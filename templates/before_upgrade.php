<?php 
/*
 * This file is executed before performing the upgrade. This can be used to verify that
 * pre upgrade conditions match those of expected.
 * 
 * For example, if you are going to add a category as a child to an existing category then
 * you would use this file to verify that the parent category exists.
 *
 * I would recommend writing these tests before writing the upgrade code.
 * 
 */
defined('SITEPLUGIN') or wp_die('This script can only be executed from inside wp-admin');

?>