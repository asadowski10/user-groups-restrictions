<?php
class User_Groups_Restrictions_Admin {
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'init' ), 11 );
		add_action( 'save_post', array( __CLASS__, 'save_post' ), 10, 2 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 10, 2 );
	}
	
	/**
	 * Register taxonomy if doesn't exist
	 */
	public static function init() {
		register_taxonomy_for_object_type( 'user-group', 'page' );
	}
	
	/**
	 * Set or delete relationship on save post
	 */
	public static function save_post( $object_id = 0, $object = null ) {
		global $post;
		
		// Don't do anything when autosave 
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return false;
		
		// Save only when relation post type is called before.
		if ( !isset($_POST['bugr-metabox']) || $_POST['bugr-metabox'] != 1 )
			return false;
		
		// Get current post object
		if ( !isset($object) || is_null($object) )
			$object = get_post( $object_id );
		
		if ( !isset($_POST['groupids']) || empty($_POST['groupids']) ) {
			// Delete object term relationship
			wp_delete_object_term_relationships( $object_id, 'user-group');
		} else {
			// Set relation
			$group_ids = array_map('intval', $_POST['groupids']);
			wp_set_object_terms( $object_id, $group_ids, 'user-group', false);
		}
		
		// Test if checkbox is checked
		if ( isset( $_POST['apply_to_children'] ) && $_POST['apply_to_children'] == "1" ) {
			// Loop for set relation on child page
			if (is_post_type_hierarchical($object -> post_type) != false) {
				$child_query = new WP_Query( array('post_parent' => $object_id, 'post_type' => $object -> post_type, 'post_status' => 'publish', 'nopaging' => true));
				if ($child_query -> have_posts()) {
					while ($child_query -> have_posts()) {
						$child_query -> the_post();
						self::save_post(get_the_ID(), $post);
					}
				}
				wp_reset_postdata();
			}
		}
	}
	
	/**
	 * Add the meta box container.
	 */
	public static function add_meta_boxes( $post_type, $post ) {
		global $pagenow;
		// Hide meta box if not admin and edit page.
		if( current_user_can('manage_options') != true && $pagenow == 'post.php' ){
			return false;
		}
		
		add_meta_box(
			'usergroupsrestrictionsdiv', 
			__( 'User Groups Restriction', 'user-groups-restrictions'), 
			array( __CLASS__, 'metabox' ), 
			'page', 
			'side', 
			'low'
		);
	}
	
	/**
	 * Add content of the meta box container.
	 */
	public static function metabox( $user ) {
		global $post;
		
		//Get all the groups
		$terms = get_terms( 'user-group', array( 'hide_empty' => false ) );
		if( is_array($terms) && empty($terms) ) {
			_e('You must start by add group and after you can make the relation...', 'user-groups-restrictions' );
			return false;
		}
		
		// List all the groups for admin
		if( current_user_can('manage_options') == true ) :
			//Get the groups of post
			$post_terms = get_the_terms( $post->ID, 'user-group' );
			$terms_array = array();
			
			// Post_terms is not array ?
			if ( !is_array($post_terms) ){
				$post_terms = array();
			}
			
			// Get term_id of post_terms
			foreach ($post_terms as $post_term) {
				$terms_array[] = $post_term->term_id;
			}
			?>
			<ul>
				<?php foreach ( $terms as $term ) : ?>
					<li>
						<input id="group-id-<?php echo (int) $term->term_id ; ?>" type="checkbox" name="groupids[]" <?php checked ( in_array($term->term_id, $terms_array), true ); ?> value="<?php echo (int) $term->term_id ; ?>" />
						<label for="group-id-<?php echo (int) $term->term_id ; ?>"><?php echo esc_html($term->name); ?></label>
					</li>
				<?php endforeach; ?>
			</ul>
			
			<input type="checkbox" name="apply_to_children" value="1" id="apply_to_children"/>
			<label for="apply_to_children"><?php _e( 'Apply this group for all children', 'user-groups-restrictions' ); ?></label>
			
			<input type="hidden" name="bugr-metabox" value="1" />
			<?php
			
		else :
			// Is user ?
			$user = wp_get_current_user();
			if( empty($user) ) {
				return false;
			}
			// Get groups of user
			$user_groups = wp_get_object_terms( $user->ID, 'user-group', array('fields' => 'all') );
			
			//Show just group where user in and checked by default
			?>
			<ul>
				<?php foreach ( $user_groups as $user_group ) : ?>
					<li>
						<input id="group-id-<?php echo (int) $user_group->term_id ; ?>" type="checkbox" name="groupids[]" checked="checked" value="<?php echo (int) $user_group->term_id ; ?>" />
						<label for="group-id-<?php echo (int) $user_group->term_id ; ?>"><?php echo esc_html($user_group->name); ?></label>
					</li>
				<?php endforeach; ?>
			</ul>
			
			<input type="hidden" name="bugr-metabox" value="1" />
			<?php
			
		endif;
		
		return true;
	}
}