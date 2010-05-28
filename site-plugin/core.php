<?php 
/*
Plugin Name: Site Plugin Core
Plugin URI: http://positivesum.org/wordpress/site-plugin-core
Description: Library that can be used to create Site Plugins. Site plugins simplify the iterative development process.
Version: 0.1
Author: Taras Mankovski
Author URI: http://taras.cc
*/

if (!class_exists("SitePlugin")) {
	
	class SitePlugin {
		
		function __construct($name) {
	
			$this->name = $name;
			$this->slug = strtolower($name);
			$this->option_name = $this->slug.'_version';
			
			// get plugin path
			$path = explode('/', dirname(__FILE__));
			
			$position = array_search('plugins', $path);
			$this->path = implode('/', array_slice($path, 0, $position+2));			
			
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
		 * @return array of all versions and their info
		 */
		function get_versions() {
			
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
					_default_wp_die_handler('Versions directory does not exist in ' . $this->name . ' plugin directory');
				}
			}
			ksort($versions);
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
			
			$version['downgrade'] = $this->get_path($id, 'downgrade');
			$version['test'] = $this->get_path($id, 'test');
			
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
		 * Return the version number of the next upgrade version
		 * @return int version of the next upgrade
		 */
		function next_version() {
			
			if ( $this->is_upgrade_available() ) {
				$versions = $this->available_upgrades();
				$ids = array_keys($versions);
				$next = array_shift($ids);
				return $next;
			} else {
				return false;
			}
			
		}
		
		/*
		 * Perform the upgrade. Return true if successful or error if failed.
		 * @param $id int id of the version to upgrade to
		 * @return mixed bool or array
		 */
		function upgrade($id) {
			
			$next = $this->next_version();
			if ( $id != $next ) {
				return "Next upgrade is " . $this->next_version() . " not $id";
			}
			
			$version = $this->get_version_info($id);	

			# run the upgrade script
			include($version['upgrade']);

			# increase next version by 1
			$this->bump_version();
			
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

		}
		
		function main_page() { 
			if ( !current_user_can('manage_options') ) {
      			wp_die( __('You do not have sufficient permissions to access this page.') );
    		}
    		?>
			<div class="wrap">
				<h2><?php echo __($this->name), __(' Upgrade Log') ?></h2>
				<p><?php echo __('Current Version: '), $this->get_current_version(); ?></p>
			</div>
		<?php } 
		
		function upgrade_page() { 
			
			if ( !current_user_can('manage_options') ) {
      			wp_die( __('You do not have sufficient permissions to access this page.') );
    		}
			
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
					
					$this->upgrade($id);
				?>
					<p><?php echo __('Execution complete!')?></p>
				<?php endif; ?>
				
				<h2><?php echo __('Available Upgrades')?></h2>
				<table class="widefat">
					<thead>
						<tr>
							<th class="version"><?php echo __('Version') ?></th>
							<th class="changelog"><?php echo __('Changelog') ?></th>
							<th class="upgrade"><?php echo __('Upgrade') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($this->available_upgrades() as $id => $info): ?>
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
								<td class="upgrade"><?php 
									if ( $id === $this->next_version() ) :
										if ( $info['upgrade' ] ) { ?>
											<a href="<?php echo esc_url($_SERVER['REQUEST_URI']."&execute=$id") ?>"><?php echo __('Execute Upgrade') ?></a>
								  <?php } else {
											echo 'Unavailable';
										}
									else : 
										echo __('Pending');
									endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>		
			</div>
		<?php }
		}
	}
?>