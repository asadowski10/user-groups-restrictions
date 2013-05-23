<?php
/*
 Plugin Name: User Groups Restrictions
 Plugin URI: http://www.beapi.fr
 Description: Extend of user-groups plugin, this plugin allows you to restrict access to users groups in back-end and front-end on page.
 Author: Amaury Balmer, Alexandre Sadowski
 Author URI: http://www.beapi.fr
 Version: 1.0
 Text Domain: user-groups-restrictions
 Domain Path: /languages/
 Network: false

 ----

 Copyright 2013 Amaury Balmer (amaury@beapi.fr), Alexandre Sadowski (asadowski@beapi.fr)

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

// Folder name
define( 'UGR_VERSION', '1.0' );
define( 'UGR_OPTION', 'user-groups-restrictions' );

define( 'UGR_URL', plugins_url( '', __FILE__ ) );
define( 'UGR_DIR', dirname( __FILE__ ) );

if( is_admin( ) ) {// Call admin class
	require (UGR_DIR.'/inc/class.admin.php');
	require (UGR_DIR.'/inc/class.admin.edit.php');
}

add_action( 'admin_notices', 'notice_plugin_user' );
function notice_plugin_user( ) {
	global $pagenow;
	if( $pagenow == 'plugins.php' ) {
		if( is_plugin_active( 'user-groups/user-groups.php' ) == false ) {
			echo '<div class="error"><p>'.__( "Be careful, you must install and activate the plugin 'user-groups' to run it. Thank you", 'user-groups-restrictions' ).'</p></div>';
		}
	}
	return false;
}

add_action( 'plugins_loaded', 'init_user_groups_restrictions', 201 );
function init_user_groups_restrictions( ) {
	global $ser_groups_restrictions;

	// Load translations
	load_plugin_textdomain( 'user-groups-restrictions', false, basename( rtrim( dirname( __FILE__ ), '/' ) ).'/languages' );

	// Admin
	if( is_admin( ) ) {
		$ser_groups_restrictions['admin-core'] = new User_Groups_Restrictions_Admin( );
		$ser_groups_restrictions['admin-edit'] = new User_Groups_Restrictions_Admin_Edit( );
	}
}