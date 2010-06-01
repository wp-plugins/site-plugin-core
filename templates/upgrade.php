<?php 
/*
 * This is the main file that performs all of the upgrades.
 * 
 * In this file,  you can do things like change page parents, perform large database changes
 * or anything else that would require interaction with the admin interface.
 * 
 * Please, include a comment for each action. A example of such a comment is included below. 
 * These comments are used to generate the changelog for this site.
 */
defined('SITEPLUGIN') or wp_die('This script can only be executed from inside wp-admin');

{% if widgets %}
// yaml string of widgets options
$widgets = <<<EOT
{{ widgets }}
EOT;
/* updated widgets options */
update_widgets_options($widgets);
{% endif %}

{% if sidebars %}
// yaml string of sidebars_widgets options
$sidebars = <<<EOT
{{ sidebars }}
EOT;
/* updated sidebar widgets */
update_sidebars_widgets($sidebars);
{% endif %}

/* sample comment */

?>