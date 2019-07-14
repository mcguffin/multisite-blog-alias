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

	private $cap = 'manage_network';

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		$this->core = Core\Plugin::instance();
		$this->model = Model\ModelAliasDomains::instance();

		// render tab navigation
		add_filter( 'network_edit_site_nav_links', array( $this, 'edit_site_nav_links'));

		// editor
		add_action( 'admin_action_alias-domains', array( $this, 'admin_alias_domains' ) );

		// actions
		add_action( 'admin_action_alias-domain-add', array( $this, 'add_alias_domain' ) );
		add_action( 'admin_action_alias-domain-remove', array( $this, 'remove_alias_domain' ) );
		add_action( 'admin_action_alias-domain-remove-all', array( $this, 'remove_alias_domains' ) );

	}


	/**
	 *	@action admin_action_alias-domain-add
	 */
	public function add_alias_domain() {

		check_admin_referer( 'alias-domain-add' );

		current_user_can( $this->cap ) || wp_die(__('Insufficient permission'));

		if ( isset( $_POST['blog_id'] ) ) {
			$blog_id = absint( $_POST['blog_id'] );
		}
		if ( ! $blog_id ) {
			wp_die( __('Invalid request') );
		}

		$redirect_args = array(
			'id' => $blog_id,
			'action' => 'alias-domains',
		);
		if ( ! $domain_alias = $this->model->validate( 'domain_alias', $_POST['domain_alias'] ) ) {
			$redirect_args['error']	= 'add-invalid-domain';
		}


		// alias exists
		if ( $record = $this->model->fetch_one_by('domain_alias', $domain_alias ) ) {
			$redirect_args['error']	= 'add-alias-exists';
		}

		if ( $other_blog_id = get_blog_id_from_url( $domain_alias ) ) {
			$redirect_args['notice']		= 'add-site-exists';
			$redirect_args['other_blog']	= $other_blog_id;
		}

		if ( ! isset( $redirect_args['error'] ) ) {
			$data = array(
				'site_id'		=> get_current_site()->id,
				'blog_id'		=> $blog_id,
				'domain_alias'	=> $domain_alias,
				'redirect'		=> 1,
			);

			$id = $this->model->insert( $data );

			if ( $id === false ) {
				$redirect_args['error']	= 'unknown';
			} else {
				$redirect_args['created'] = '1';
			}
		}

		wp_safe_redirect( add_query_arg( $redirect_args, network_admin_url('admin.php?action=alias-domains')) );

		exit();
	}


	/**
	 *	@action admin_action_alias-domain-remove
	 */
	public function remove_alias_domain() {

		current_user_can( $this->cap ) || wp_die(__('Insufficient permission'));

		$id = false;

		if ( isset( $_POST['id'] ) ) {
			$id = absint( $_POST['id'] );
		}
		if ( ! $id ) {
			wp_die( __('Invalid request') );
		}

		check_admin_referer( 'alias-domain-remove-' . $id );

		if ( isset( $_POST['blog_id'] ) ) {
			$blog_id = absint( $_POST['blog_id'] );
		}
		if ( ! $blog_id ) {
			wp_die( __('Invalid request') );
		}

		$redirect_args = array(
			'id' => $blog_id,
			'action' => 'alias-domains',
		);


		if ( $total = $this->model->delete( array(
			'id'	=> $id,
		) ) ) {
			$redirect_args['deleted'] = $total;
		} else {
			$redirect_args['error'] = 'delete';
		}
		wp_safe_redirect( add_query_arg( $redirect_args, network_admin_url('admin.php')) );
		exit();
	}

	/**
	 *	@action admin_action_alias-domain-remove-all
	 */
	public function remove_alias_domains() {

		check_admin_referer( 'alias-domain-remove-all' );

		current_user_can( $this->cap ) || wp_die(__('Insufficient permission'));

		$blog_id = false;
		if ( isset( $_POST['blog_id'] ) ) {
			$blog_id = absint( $_POST['blog_id'] );
		}
		if ( ! $blog_id ) {
			wp_die( __('Invalid request') );
		}

		$redirect_args = array(
			'id' => $blog_id,
			'action' => 'alias-domains',
		);


		if ( $total = $this->model->delete( array(
			'blog_id'	=> $blog_id,
		) ) ) {
			$redirect_args['deleted'] = $total;
		} else {
			$redirect_args['error']	= 'delete';
		}

		wp_safe_redirect( add_query_arg( $redirect_args, network_admin_url('admin.php?action=alias-domains')) );
		exit();
	}


	/**
	 *	@filter network_edit_site_nav_links
	 */
	public function edit_site_nav_links( $links ) {
		$links['alias'] = array(
			 'label'	=> __( 'Alias Domains', 'wpms-blog-alias' ),
			 'url'		=> 'admin.php?action=alias-domains',
			 'cap'		=> $this->cap,
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

		add_filter( 'removable_query_args', '__return_empty_array' );

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
		wp_enqueue_script('admin-alias-domain', $this->core->get_asset_url('js/admin/network/alias.js'), ['jquery'], $this->core->get_version(), true );
		wp_enqueue_style('admin-alias-domain', $this->core->get_asset_url('css/admin/network/alias.css'), [], $this->core->get_version() );
		require( ABSPATH . 'wp-admin/admin-header.php' );

	}

	/**
	 *	Print blog admin form
	 */
	private function admin_alias_body() {

		global $title;

		$messages = array(
			'error'		=> '',
			'success'	=> '',
		);

		if ( isset( $_GET['created'] ) ) {
			$messages['updated'] = __( 'Alias created', 'wpms-blog-alias');
		} else if ( isset( $_GET['deleted'] ) ) {
			/* translators: number of deleted entries */
			$messages['notice-warning'] = sprintf( _n('%d entry deleted', '%d entries deleted', $_GET['deleted'], 'wpms-blog-alias' ), $_GET['deleted'] );
		} else if ( isset( $_GET['error'] ) ) {
			$errors = array(
				'add-alias-exists'		=> __( 'Error: The Alias already exists.', 'wpms-blog-alias' ),
				'add-invalid-domain'	=> __( 'Error: Invalid domain name', 'wpms-blog-alias' ),
				'delete'				=> __( 'Error during delete', 'wpms-blog-alias'  ),
				'default'				=> __( 'Something went wrong...', 'wpms-blog-alias' ),
			);
			$messages['error'] = isset( $errors[ $_GET['error'] ] ) ? $errors[ $_GET['error'] ] : $errors['default'];

		}
		if ( isset( $_GET['notice'] ) && $_GET['notice'] == 'add-site-exists' ) {
			$messages['notice-warning'] = __( 'Notice: A different Blog is already using this domain. The redirect will not work until this blog is deleted.', 'wpms-blog-alias' );

			if ( isset( $_GET['other_blog'] ) ) {
				$messages['notice-warning'] .= sprintf( '<a href="%s">%s</a> <a href="%s">%s</a>',
					esc_url( get_site_url( $_GET['other_blog'] ) ),
					__( 'View', 'wpms-blog-alias' ),

					esc_url( network_admin_url( 'site-info.php?id=' . $_GET['other_blog'] ) ),
					__( 'Edit', 'wpms-blog-alias' )
				);

			}

		}
		?>

		<div class="wrap admin-domain-alias">
		<h1 id="edit-site"><?php echo $title; ?></h1>

		<p class="edit-site-actions"><a href="<?php echo esc_url( get_home_url( $this->blog_details->id, '/' ) ); ?>"><?php _e( 'Visit' ); ?></a> | <a href="<?php echo esc_url( get_admin_url( $this->blog_details->id ) ); ?>"><?php _e( 'Dashboard' ); ?></a></p>
		<?php

			network_edit_site_nav( array(
				'blog_id'  => $this->blog_details->id,
				'selected' => 'alias'
			) );

			foreach ( $messages as $type => $msg ) {
				if ( ! empty( $msg ) ) {
					printf( '<div id="message-%s" class="%s notice is-dismissible"><p>%s</p></div>' , $type, $type, $msg );
				}
			}

			$aliases = $this->model->fetch_by( 'blog_id', $this->blog_details->id );

			/* actions:
				-remove-all(blog_id)
				-remove(ID)
				-add(blog_id,alias)
			*/
			// form
			?>
			<!-- add -->
			<h2><?php _e('Add Domain Alias','wpms-blog-alias'); ?></h2>
			<form method="post" action="admin.php?action=alias-domain-add">
				<?php wp_nonce_field( 'alias-domain-add' ); ?>
				<input type="hidden" name="blog_id" value="<?php echo esc_attr( $this->blog_details->id ) ?>" />
				<table class="widefat striped">
					<tbody>
						<tr>
							<td>
								<input id="add-domain-alias" placeholder="subdomain.domain.tld" type="text" name="domain_alias" class="widefat code" />
							</td>
							<td class="action-links">
								<button class="button-primary" type="submit"><?php _e('Add','wpms-blog-alias'); ?></button>
							</td>
						</tr>
					</tbody>
				</table>
			</form>
			<!-- remove -->
			<h2><?php _e('Domain Aliases','wpms-blog-alias'); ?></h2>
			<?php if ( empty( $aliases ) ) {

				?>
					<p><?php _e('– No Domain Aliases –', 'wpms-blog-alias'); ?></p>
				<?php

			} else {
				?>

				<table class="widefat striped domain-status-list-table">
					<thead>
						<th><?php _e('Alias Domain','wpms-blog-alias'); ?></th>
						<th class="status"><?php _e( 'Status', 'wpms-blog-alias' ) ?></th>
						<th class="action-links"></th>
					</thead>
					<tbody>
						<?php
						// edit aliases
						$admin = Ajax::instance();
						foreach ( $aliases as $alias ) {
							$get_status = add_query_arg(
								array(
									'action'	=> $admin->ajax_handler->action,
									'alias_id'	=> $alias->ID,
									'blog_id'	=> $this->blog_details->id,
									'nonce'		=> $admin->ajax_handler->nonce,
								),
								get_admin_url( 1, 'admin-ajax.php')
							); // add_query_arg
							?>
							<tr>
								<td>
									<code><?php echo $alias->domain_alias ?></code>
								</td>

								<td class="status" data-check-status="<?php esc_attr_e( $get_status ); ?>"></td>
								<td class="action-links">
									<form method="post" action="admin.php?action=alias-domain-remove">
										<?php wp_nonce_field( 'alias-domain-remove-' . $alias->ID ); ?>
										<input type="hidden" name="blog_id" value="<?php echo esc_attr( $this->blog_details->id ) ?>" />
										<button class="button-secondary" type="submit" name="id" value="<?php echo $alias->ID; ?>">
											<?php _e('Remove','wpms-blog-alias'); ?>
										</button>
									</form>
								</td>
							</tr>
							<?php
						}
						?>

					</tbody>
					<tfoot>
						<tr>
							<th></th>
							<th class="status"></th>
							<th class="action-links">
								<form method="post" action="admin.php?action=alias-domain-remove-all">
									<?php wp_nonce_field( 'alias-domain-remove-all' ); ?>
									<a href="#" data-action="check-alias-status" class="button"><?php _e( 'Check Status', 'wpms-blog-alias' ) ?></a>
									<button class="button-secondary" type="submit" name="blog_id" value="<?php echo $this->blog_details->id; ?>">
										<?php _e('Remove All','wpms-blog-alias'); ?>
									</button>
								</form>
							</th>
						</tr>
					</tfoot>
				</table>
				<?php
			}
			?>

		</div>
		<?php


	}



	/**
	 *	Print blog admin page footer
	 */
	private function admin_alias_footer() {
		require( ABSPATH . 'wp-admin/admin-footer.php' );
	}


}
