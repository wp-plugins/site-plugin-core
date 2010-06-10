<?php 

if ( !class_exists('SiteUpgradeCategoryActions') ) {
	
	class SiteUpgradeCategoryActions extends SiteUpgradeAction {
		
		var $functions = array('category_update', 'category_exists');
	
		/*
		 * Updates category to values specified in array
		 * @param array $args
		 * @return true|WP_Error
		 */
		 function category_update($args) {
		 
		 	 if ( !array_key_exists('id', $args) ) {
		 	 	 return new WP_Error('error', __('Category id is not specified'));
		 	 }
		 	 
		 	 $term_id = $args['id'];
		 	 unset($args['id']);
		 	 
		 	 $data = array();
		 	 if ( array_key_exists('name', $args) ) $data['name'] = $args['name'];		 	 
		 	 if ( array_key_exists('description', $args) ) $data['description'] = $args['description'];
		 	 if ( array_key_exists('parent', $args) ) $data['parent'] = $args['parent'];
		 	 if ( array_key_exists('slug', $args) ) $data['slug'] = $args['slug'];
		 	 
		 	 $result = wp_update_term( $term_id, 'category', $data);
		 	 
		 	 if ( is_wp_error($result) ) return new WP_Error('error', __('Error occured while trying to update category'));
		 	 else return true;
		 
		 }
		 
		 /*
		  * Check if category exists
		  * @param array $args
		  * @return boolean
		 */
		 function category_exists( $cat_id ) {
		 	 return is_category( $cat_id );
		 }
		 
		 function admin( $elements ) {
		 	 
		 	 require_once(ABSPATH . 'wp-admin/includes/meta-boxes.php');
		 	 
		 	 $html[] = '<ul id="categorychecklist" class="list:category categorychecklist form-no-clear">';
		 	 ob_start();
		 	 $post = get_post($id=0);
		 	 $args = array( 'taxonomy' => 'category', 'popular_cats' => wp_popular_terms_checklist('category'));
			 wp_terms_checklist($post->ID, $args);
		 	 $html[] = ob_get_contents();
		 	 ob_end_clean();
		 	 $html[] = '</ul>';
		 	 $html[] = '<style type="text/css">ul.children { margin-left: 10px; }</style>';
		 	 $elements[__('Update Categories')] = implode("\n", $html);
			
		 	 return $elements;
		  
		 }
				
		/*
		 * This method is called when upgrade script for an action is being generated.
		 * @param $args necessary for function's operation
		 * @return str of php code to add to upgrade script
		 */
		function generate($code) {
			
			$result = array();
			if ( array_key_exists('post_category', $_POST) ) $categories = $_POST['post_category'];

			if ( $categories ) {
				$this->h2o->loadTemplate('category.code');
				foreach ( $categories as $cat_id ) {
					$c = get_category($cat_id, ARRAY_A);
					$data = array(
						'id'=>$c['cat_ID'],
						'name'=>$c['name'], 'description'=>$c['description'], 
						'parent'=>$c['parent'], 'slug'=>$c['slug']
						);
					$value = Spyc::YAMLDump($data);
					$code .= $this->h2o->render(array('id'=>$c['cat_ID'], 'name'=>$c['name'], 'value'=>$value));					
				}
				
				
			}
			
			return $code;
		}
		
	}

	new SiteUpgradeCategoryActions();
	
}