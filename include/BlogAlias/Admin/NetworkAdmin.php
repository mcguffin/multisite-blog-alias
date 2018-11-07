<?php
/**
 *	@package BlogAlias\Admin
 *	@version 1.0.0
 *	2018-09-22
 */

namespace BlogAlias\Admin;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use BlogAlias\Core;
use BlogAlias\Model;


class NetworkAdmin extends Core\Singleton {

	private $core;
	private $blog_details;

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		$this->core = Core\Core::instance();

		add_filter( 'network_edit_site_nav_links', array( $this, 'edit_site_nav_links'));

		add_action( 'admin_init', array( $this , 'admin_init' ) );
		add_action( 'admin_print_scripts', array( $this , 'enqueue_assets' ) );

		add_action( 'admin_action_alias-domains', array( $this, 'admin_alias_domains' ) );
	}

	/**
	 *	@filter network_edit_site_nav_links
	 */
	public function edit_site_nav_links( $links ) {
		$links['alias'] = array(
			 'label'	=> __( 'Alias Domains', 'wp-blog-alias' ),
			 'url'		=> 'admin.php?action=alias-domains',
			 'cap'		=> 'manage_network',
		);
		return $links;
	}

	/**
	 *	@action admin_action_alias-domains
	 */
	public function admin_alias_domains() {

		if ( ! current_user_can( 'manage_network' ) ) {
			wp_die( __( 'Sorry, you are not allowed to edit this site.' ) );
		}

		$id = isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : 0;

		if ( ! $id ) {
			wp_die( __('Invalid site ID.') );
		}


		$details = get_site( $id );
		if ( ! $details ) {
			wp_die( __( 'The requested site does not exist.' ) );
		}

		if ( ! can_edit_network( $details->site_id ) ) {
			wp_die( __( 'Sorry, you are not allowed to access this page.' ), 403 );
		}

		$this->blog_details = $details;

		$this->admin_alias_header();
		$this->admin_alias_body();
		$this->admin_alias_footer();
	}

	/**
	 *	Print blog admin page header
	 */
	private function admin_alias_header() {

		// handle actions here!

		global $parent_file, $submenu_file, $title;

		$parent_file = 'sites.php';
		$submenu_file = 'sites.php';
		$title = sprintf( __( 'Edit Site: %s' ), esc_html( $this->blog_details->blogname ) );

		require( ABSPATH . 'wp-admin/admin-header.php' );
	}

	/**
	 *	Print blog admin form
	 */
	private function admin_alias_body() {

		global $title;

		$model = Model\ModelAliasDomains::instance();

		if ( isset( $_GET['update'] ) ) {
			$messages = array();
			if ( 'updated' == $_GET['update'] ) {
				$messages[] = __( 'Alias Domains updated.', 'wp-blog-alias' );
			}
		}
		?>

		<div class="wrap">
		<h1 id="edit-site"><?php echo $title; ?></h1>
		<p class="edit-site-actions"><a href="<?php echo esc_url( get_home_url( $id, '/' ) ); ?>"><?php _e( 'Visit' ); ?></a> | <a href="<?php echo esc_url( get_admin_url( $id ) ); ?>"><?php _e( 'Dashboard' ); ?></a></p>
		<?php

			network_edit_site_nav( array(
				'blog_id'  => $this->blog_details->id,
				'selected' => 'alias'
			) );

			if ( ! empty( $messages ) ) {
				foreach ( $messages as $msg ) {
					echo '<div id="message" class="updated notice is-dismissible"><p>' . $msg . '</p></div>';
				}
			}

			// form
			?>
			<form method="post" action="site-info.php?action=update-site">
				<?php wp_nonce_field( 'edit-site' ); ?>
				<input type="hidden" name="id" value="<?php echo esc_attr( $this->blog_details->id ) ?>" />
				<?php
				// edit aliases
				$aliases = $model->fetch_by( 'blog_id', $this->blog_details->id );
				foreach ( $aliases as $alias ) {

				}

				?>
				<?php submit_button(); ?>
			</form>

		</div>
		<?php


	}

	/**
	 *	Print blog admin page footer
	 */
	private function admin_alias_footer() {
		require( ABSPATH . 'wp-admin/admin-footer.php' );
	}

	/**
	 *	Admin init
	 *	@action admin_init
	 */
	function admin_init() {
	}

	/**
	 *	Enqueue options Assets
	 *	@action admin_print_scripts
	 */
	function enqueue_assets() {
		wp_enqueue_style( 'blog_alias-admin' , $this->core->get_asset_url( '/css/admin.css' ) );

		wp_enqueue_script( 'blog_alias-admin' , $this->core->get_asset_url( 'js/admin.js' ) );
		wp_localize_script('blog_alias-admin' , 'blog_alias_admin' , array(
		) );
	}

}
