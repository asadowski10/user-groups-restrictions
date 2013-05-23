<?php
class User_Groups_Restrictions_Admin_Edit {
	public function __construct() {
		add_action( 'pre_get_posts', array( __CLASS__, 'pre_get_posts' ) );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_filter( 'page_attributes_dropdown_pages_args', array(__CLASS__, 'page_attributes_dropdown_pages_args') );
	}
	
	public static function pre_get_posts( $query ) {
		// Test admin role
		if ( current_user_can('manage_options') != false ) {
			return false;
		}
		
		// Test if current post type is a page !
		if ( $query->query_vars['post_type'] != 'page' ) {
			return false;
		}
		
		// Test main query ?
		// NOTE: admin screens also have a main query and this function can be used to detect it there.
		//if ( !$query->is_main_query() ) {
		//	return false;
		//}
		
		// Test is user have group
		$user = wp_get_current_user();
		if( empty($user) ) {
			return false;
		}
		
		// Get users groups
		$user_groups = wp_get_object_terms($user->ID, 'user-group', array('fields' => 'all'));
		if( empty($user_groups) ) {
			return false;
		}
		
		// Build slug array
		$user_groups_slugs = array();
		foreach( $user_groups as $user_group ) {
			$user_groups_slugs[] = $user_group->slug;
		}
		
		// Add tax_query to main query
		$query->query_vars['tax_query'] = array(
			'relation' => 'AND',
			array(
				'taxonomy' => 'user-group',
				'field' => 'slug',
				'terms' => $user_groups_slugs
			),
		);
	}

	/**
	 * Redirect if unauthorized user 
	 */
	public static function admin_init() {
		global $pagenow;
		
		// Is admin user ? 
		if ( current_user_can('manage_options') != false ) {
			return false;
		}
		
		// Not an edit page ? 
		if ( $pagenow != 'post.php' ) {
			return false;
		}
		
		// No action on edit page ?
		if ( !isset($_GET['post']) || $_GET['action'] != 'edit' ){
			return false;
		}
		
		// Get current page with post_id
		$current_page = get_post( $page_id = (int) $_GET['post'] );
		// Out if page empty
		if ( empty($current_page) ){
			return false;
		}
		
		// Out if is not a page
		if ( $current_page->post_type != 'page'){
			return false;
		}
		
		// Get user infos
		$user = wp_get_current_user();
		// Out if no user
		if( empty($user) ) {
			return false;
		}
		
		//Get ids of user's groups
		$user_groups = wp_get_object_terms( $user->ID, 'user-group', array('fields' => 'ids') );
		//Get ids of page's groups
		$page_groups = wp_get_object_terms( $current_page->ID, 'user-group', array('fields' => 'ids') );
		// Out if is empty
		if( empty($user_groups) || empty($page_groups) ) {
			return false;
		}
		
		// Check between user's groups and page's groups if results.
		$shared = array_intersect( $user_groups, $page_groups );
		// Redirect if results
		if ( empty($shared) ) {
			wp_redirect( admin_url('/') );
			exit();
		}
	}
	
	/**
	 * Limit to curent user attributes pages metabox 
	 */
	public static function page_attributes_dropdown_pages_args($dropdown_args){
		// Return all pages for admin
		if ( current_user_can('manage_options') != false ) {
			return $dropdown_args;
		}
		// Return all page if is not user
		$user = wp_get_current_user();
		if( empty($user) ) {
			return $dropdown_args;
		}
		
		// Get user groups
		$user_groups = wp_get_object_terms( $user->ID, 'user-group', array('fields' => 'ids') );
		// Return all if user doesn't have a group
		if( empty($user_groups) ) {
			return $dropdown_args;
		}
		
		// Get all ids of authorized pages for current user
		$query = new WP_Query( array(
			'post_type' => 'page',
			'fields' => 'ids',
			'nopaging' => true,
			'post_status' => 'publish', 
			'tax_query' => array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'user-group',
					'field' => 'id',
					'terms' => $user_groups
				)
			)
		));
		
		// Include to drowpdown_args the results of query
		if ( empty($query->posts) ) {
			$dropdown_args['include'] = array(0);
		} else {
			$dropdown_args['include'] = $query->posts;
		}
		
		return $dropdown_args;
	}
}