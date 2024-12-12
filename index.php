<?php

/*
Plugin Name: Multisite Blog Alias
Plugin URI: https://github.com/mcguffin/multisite-blog-alias
Description: Alias Domains for Blogs
Author: Jörn Lund
Version: 1.2.1
Author URI: https://github.com/mcguffin
License: GPL3
Github Repository: mcguffin/multisite-blog-alias
GitHub Plugin URI: mcguffin/multisite-blog-alias
Requires WP: 4.8
Requires PHP: 7.4
Network: true
Text Domain: multisite-blog-alias
Domain Path: /languages/
*/

/*
  Copyright 2018 Jörn Lund

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 3, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
Plugin was generated by WP Plugin Scaffold
https://github.com/mcguffin/wp-plugin-scaffold
Command line args were: ``
*/


namespace BlogAlias;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'FU!' );
}


/**
 *  Check mutisite requirement and print notice
 */
if ( ! is_multisite() ) {
	/**
	 *  @action admin_notices
	 */
	function multisite_blog_alias_multisite_required() {
		if ( current_user_can( 'activate_plugins' ) ) {

			load_plugin_textdomain( 'multisite-blog-alias', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

			deactivate_plugins( plugin_basename( __FILE__ ), true );

			?>
			<div class="notice error is-dismissible">
				<p>
					<?php esc_html_e( 'The Multisite Blog Alias plugin requires a WordPress multisite installation.', 'multisite-blog-alias' ); ?>
					<strong><?php esc_html_e( 'It has been deactivated.', 'multisite-blog-alias' ); ?></strong>
				</p>
			</div>
			<?php

		}
	}
	// print notice
	add_action( 'admin_notices', 'BlogAlias\multisite_blog_alias_multisite_required' );

	return;
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'include/autoload.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'include/api/api.php';

Core\Core::instance( __FILE__ );//->set_plugin_file( __FILE__ );

Model\AliasDomains::instance();

if ( is_network_admin() || defined( 'DOING_AJAX' ) ) {
	Admin\NetworkAdmin::instance();
}

if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	Admin\Ajax::instance();
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WPCLI\WPCLIAliasDomain::instance();
}
