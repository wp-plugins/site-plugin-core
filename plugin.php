<?php 
/*
Plugin Name: Site Plugin Core
Plugin URI: http://positivesum.org/wordpress/site-plugin-core
Description: Library that can be used to create Site Plugins. Site plugins simplify iterative development process.
Version: 0.2.6
Author: Taras Mankovski
Author URI: http://taras.cc
*/

require_once(dirname(__FILE__).'/lib.php');

# display Site Plugin Admin interface
if ( is_admin() ) include_once(dirname(__FILE__).'/admin.php');

if (!class_exists("SitePlugin")) {
	
	class SitePlugin {
		
		function __construct($name) {
	
			$this->name = $name;
			$this->slug = sanitize_title($name);
			$this->option_name = $this->slug.'_version';
			
			$this->path = WP_PLUGIN_DIR . '/' . $this->slug;			
			
			$this->h2o = new H2o(NULL, array('context', $this));
			$this->errors = new WP_Error;

			// contains path to versions
			$this->versions = $this->path . '/versions/';
			
			add_action('init', array(&$this, 'init'));
			
			register_activation_hook( $this->path.'/plugin.php' , array(&$this, 'create_version_option') );
			
		}
		
		/**
		 * Initialization function that setups admin
		 *
		 */
		function init() {
			
			# setup admin menu
			if ( is_admin() ) {
				
				define('SITEPLUGIN', true);
				
				add_action('admin_menu', array(&$this, 'setup_menu'));			

				require_once(WP_PLUGIN_DIR.'/site-plugin-core/helpers.php');			
				
			}

		}	
		
		/**
		 * Creates version option for current plugin
		 */
		function create_version_option() {
			if ( get_option($this->option_name, false) === false ) {
				add_option($this->option_name, 0);
			
			}
		}
		
		/*
		 * Return the current active version of the plugin
		 * @return int current version number
		 */
		function get_current_version() {
			
			return get_option($this->option_name, 0 );
			
		}
		
		/*
		 * Increase next version by 1
		 * @return int next version
		 */
		function bump_version() {
			
			$next = $this->get_current_version() + 1;
			update_option($this->option_name, $next);
			
			return $next;
			
		}
		
		/*
		 * Return array of all versions and their info
		 * @param bool applied do you want applied versionly only? 
		 * @return array of all versions and their info
		 */
		function get_versions($applied=false) {
			
			$versions = array();
			$directory = $this->versions;
			if ( is_dir($directory) ) {
				if ($dh = opendir($directory)) {
			        while (($file = readdir($dh)) !== false) {
			        	if ( filetype($directory . $file) == 'dir' && is_numeric($file) ) {
			        		$versions[(int)$file] = $this->get_version_info((int)$file);	
			        	}
			        }
        			closedir($dh);
				} else {
					wp_die('Versions directory does not exist in ' . $this->name . ' plugin directory');
				}
			}
			ksort($versions);
			if ( $applied ) { 
				$versions = array_slice($versions, 0, $this->get_current_version()); 
			}
			return $versions;
			
		}
		
		/*
		 * Return string of comments from php files
		 * @param $file str path to file
		 * @return str of comments
		 */
		function parse_comments($file) {
			$text = file_get_contents($file);
			$matches = array();
			$pattern = '/\*.*?\*/smU';
			preg_match($pattern, $text, $matches);
			$comments = array();
			// TODO: improve comment parsing
			$lines = explode("\n", $text);
			foreach ( $lines as $line ) {
				if ( preg_match('/\*.*?\*/s', $line, $matches) ) {
					$comment = str_replace('*', '', $matches[0]);
					array_push($comments, $comment);					
				}
			}
			return implode("\n", $comments);
		}
		
		/*
		 * Return array of information about a specific version
		 * @param $id int id of a version to load
		 * @return array of information about a specific version
		 */
		function get_version_info( $id ) {
			
			$version = array();
			
			if ( $version['upgrade'] = $this->get_path($id, 'upgrade') ) {
				$version['changelog'] = $this->parse_comments($version['upgrade']);
			} else {
				$version['changelog'] = false;
			}
			
			$version['before'] = $this->get_path($id, 'before_upgrade');
			$version['after'] = $this->get_path($id, 'after_upgrade');
			$version['test'] = $this->get_path($id, 'regression_tests');
			
			return $version;
		}

		function get_path($id, $name) {
			$file = $this->versions."$id/$name.php";
			if ( file_exists($file) ) {
				$value = $file;
			} else {
				$value = false;
			}
			return $value;
		}
		
		/*
		 * Return weather or not an upgrade is available
		 * @return bool of available upgrades
		 */
		function is_upgrade_available() {
			return count($this->available_upgrades()) > 0;
		}
		
		/*
		 * Return array of available upgrade versions
		 * @return array of available upgrades
		 */
		function available_upgrades() {

			$current = $this->get_current_version();
			$versions = $this->get_versions();
			if ( $current == 0 ) {
				return $versions;
			}
			
			$ids = array_keys($versions);
			if ( in_array($current, $ids) ) {
				return array_slice($versions, array_search($current, $ids)+1, sizeof($ids), TRUE);
			} else {
				wp_die("Something went wrong: $current version is not available, therefore upgrade could not be determined.");
			}
			
		}		
		
		/*
		 * Return the version number of the next upgrade
		 * @return int version of the next upgrade
		 */
		function next_upgrade() {
			return $this->get_current_version() + 1;
		}
		
		/*
		 * Return id of the last version available
		 * 
		 * @return int id of last version
		 */
		function last_version() {
			$versions = $this->get_versions();
			$ids = array_keys($versions);
			return (int)end($ids);
		}
		
		/*
		 * Return id of the next version
		 * 
		 * @return int id of the next version
		 */
		function next_version() {
			return $this->last_version() + 1;
			
		}
		
		/*
		 * Perform the upgrade. Return true if successful or error if failed.
		 * @param $id int id of the version to upgrade to
		 * @return mixed bool or array
		 */
		function execute($id) {
			
			$next = $this->next_upgrade();
			if ( $id != $next ) {
				return "Next upgrade is " . $this->next_upgrade() . " not $id";
			}
			
			$version = $this->get_version_info($id);				
			
			switch($_GET['action']):
			case 'before': include($version['before']); break;
			case 'upgrade': include($version['upgrade']); break;
			case 'after': include($version['after']); $this->bump_version(); break;
			default: wp_die($_GET['action'] . ' is not a valid action.');
			endswitch;
			
			return TRUE;
		}
		
		/**
		 * Callback to setup 
		 */
		function setup_menu(){
			
			$menu_slug =  $this->slug.'_plugin';
			add_menu_page(__($this->name), __($this->name), 'manage_options', $menu_slug, array(&$this, 'main_page'));
			if ( $this->is_upgrade_available() ) {
				add_submenu_page($menu_slug, __('Available Upgrades'), __('Upgrade'), 'manage_options', $menu_slug.'_upgrade', array(&$this, 'upgrade_page') );				
			}
			add_submenu_page($menu_slug, __('Add Version'), __('Add Version'), 'manage_options', $menu_slug.'_add_version', array(&$this, 'add_version_page') );
		}

		/*
		 * Creates next version in versions directory
		 * 
		 * @return int id of the created version
		 */
		function create_version() {
			
			$next = $this->next_version();
			$previous = $this->last_version();
			$version_dir = $this->versions . "$next/";
			
			if ( mkdir($version_dir) ) {
				
				$src = WP_PLUGIN_DIR . '/site-plugin-core/templates/';
				copy($src.'before_upgrade.php', $version_dir.'before_upgrade.php');
				copy($src.'after_upgrade.php', $version_dir.'after_upgrade.php');
				copy($src.'regression_tests.php', $version_dir.'regression_tests.php');

				// generate upgrade file from templates
				// setup default upgrade values
				$upgrade = array( 'widgets'=>false, 'sidebars'=>false, 'options'=>false );
				
				if ( array_key_exists('include_widgets', $_POST) && $_POST['include_widgets'] == 'on' ) {
					$upgrade['widgets'] = dump_widgets();
				}
				
				if ( array_key_exists('include_sidebars', $_POST) && $_POST['include_sidebars'] == 'on' ) {
					$upgrade['sidebars'] = dump_sidebars_widgets();
				}
				
				if ( array_key_exists('other_options', $_POST) && $_POST['other_options'] ) {
					$upgrade['options'] = dump_options(explode("\n", $_POST['other_options']));
				}
				
				file_from_template($src.'upgrade.php', $version_dir.'upgrade.php', $upgrade);

				// generate version file from template
				$version = array( 
					'previous_path' => '/'.$this->slug.'/versions/'.$previous.'/version.php',
					'previous' => $previous,
					'next' => $next
				);
				
				file_from_template($src.'version.php', $version_dir.'version.php', $version);
			
			} else {
				wp_die(fsprint('Could not create version %s in %s', $next, $this->versions));
			}
			
			return $next;
		}
		
		/*
		 * This page shows information about creating new version and link to do it.
		 */
		function add_version_page() { 
			$this->verify_permissions(); 

			if ( $created = array_key_exists('action', $_POST) && $_POST['action'] == 'create' ) {
				$version = $this->create_version();
			} else {
				$version = NULL;
			}
			
			$values = array(
				'created'=>$created, 
				'version'=>$version,
				'url'=>$_SERVER['REQUEST_URI']
			);
			
			$h2o = new h2o(WP_PLUGIN_DIR.'/site-plugin-core/views/add_version.html');
			echo $h2o->render($values);
		}
		
		function verify_permissions() {
			if ( !current_user_can('manage_options') ) {
      			wp_die( __('You do not have sufficient permissions to access this page.') );
    		}		
		}
		
		function main_page() {
			$this->verify_permissions(); 
			?>
			<div class="wrap">
				<h2><?php echo __($this->name), __(' Upgrade Log') ?></h2>
				<p><?php echo __('Current Version: '), $this->get_current_version(); ?></p>
				<h3><?php echo __('Changelog') ?></h3>
				<ol>
					<?php foreach ($versions = $this->get_versions(true) as $id=>$version ): ?>
						<li>
							<ul>
								<?php foreach ( explode("\n", $version['changelog']) as $item ) : ?>
									<li><?php echo $item; ?></li>
								<?php endforeach; ?>
							</ul>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
		<?php } 
		
		function upgrade_page() { 
			$this->verify_permissions();
			?>
			<div class="wrap">
			
				<?php if ( array_key_exists('execute', $_GET) ): 
					$id = $_GET['execute'];
				?>
					<h2><?php echo __('Executing Upgrade: '), $id ?></h2>
				<?php 
					$versions = $this->available_upgrades();
					$versions = array_keys($versions);
					if ( !in_array($id, $versions) ) {
      					wp_die( __("$id is not a valid version upgrade") );						
					}
					
					$this->execute($id);
				?>
					<p><?php echo __('Execution complete!')?></p>
				<?php endif; ?>
				
				<h2><?php echo __('Available Upgrades')?></h2>
				<table class="widefat">
					<thead>
						<tr>
							<th class="version"><?php echo __('Version') ?></th>
							<th class="changelog"><?php echo __('Changelog') ?></th>
							<th class="before-upgrade"><?php echo __('Verify before upgrade') ?></th>
							<th class="upgrade"><?php echo __('Upgrade') ?></th>
							<th class="after-upgrade"><?php echo __('Test after upgrade') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($this->available_upgrades() as $id => $info): 
							$next = $id === $this->next_upgrade();
						?>
							<tr>
								<td class="version"><?php echo $id ?></td>
								<td class="changelog"><?php 
									if ( $info['changelog'] ) : ?>
										<ol>
										<?php foreach ( explode("\n", $info['changelog']) as $item ) : ?>
											<li><?php echo $item; ?></li>
										<?php endforeach; ?>
										</ol>
									<?php else:
										echo __('Not specified.');
									endif; ?>
								</td>
								
								<?php if ( $next ) : ?>
								<td class="before-upgrade">
									<a href="<?php echo esc_url($_SERVER['REQUEST_URI']."&amp;execute=$id&amp;action=before") ?>"><?php echo __('Execute') ?></a>
								</td>
								<td class="upgrade">
									<a href="<?php echo esc_url($_SERVER['REQUEST_URI']."&amp;execute=$id&amp;action=upgrade") ?>"><?php echo __('Execute') ?></a>
								</td>
								<td class="after-upgrade">
									<a href="<?php echo esc_url($_SERVER['REQUEST_URI']."&amp;execute=$id&amp;action=after") ?>"><?php echo __('Execute') ?></a>
								</td>
								<?php else: ?>
									<td class="unavailable" colspan="3">
									<?php echo __('Pending'); ?>
									</td>
								<?php endif; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>		
			</div>
	<?php }
	}
}

if ( !class_exists('SiteVersion_0') ) {

	/*
	 * This is an abstract class for future site versions.
	 */
	class SiteVersion_0 {
		
		var $version = 0;
		
	}
	
}

?>
