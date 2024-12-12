<?php
/**
 *  @package BlogAlias\Admin
 *  @version 1.0.0
 *  2018-09-22
 */

namespace BlogAlias\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'FU!' );
}

use BlogAlias\Asset;
use BlogAlias\Core;
use BlogAlias\Model;


class NetworkAdmin extends Core\Singleton {

	/** @var Model\AliasDomains */
	private $model;

	/** @var Core\Core */
	private $core;

	private $blog_details;

	/** @var string plugin uninstall action name */
	private $cap = 'manage_network';

	/** @var string current network admin menu parent file */
	private $current_menu_parent = null;

	/** @var string plugin uninstall action name */
	private $uninstall_action = 'uninstall-multisite-blog-alias';

	/** @var string plugin instructions action name */
	private $instructions_action = 'multisite-blog-alias-instructions';


	/**
	 *  @inheritdoc
	 */
	protected function __construct() {

		$this->core = Core\Core::instance();
		$this->model = Model\AliasDomains::instance();

		// render tab navigation
		add_filter( 'network_edit_site_nav_links', [ $this, 'edit_site_nav_links' ] );

		// editor
		add_action( 'admin_action_alias-domains', [ $this, 'admin_alias_domains' ] );

		// actions
		add_action( 'admin_action_alias-domain-add', [ $this, 'add_alias_domain' ] );
		add_action( 'admin_action_alias-domain-remove', [ $this, 'remove_alias_domain' ] );
		add_action( 'admin_action_alias-domain-remove-all', [ $this, 'remove_alias_domains' ] );

		add_filter( 'network_admin_plugin_action_links_' . $this->core->get_wp_plugin(), [ $this, 'add_uninstall_action' ], 10, 4 );
		add_action( 'admin_action_' . $this->uninstall_action, [ $this, 'uninstall_action' ] );

		add_action( 'admin_action_' . $this->instructions_action, [ $this, 'instructions_action' ] );

		add_action( 'wp_uninitialize_site', [ $this, 'uninitialize_site' ], 5 );

		add_action( 'update_wpmu_options', [ $this, 'update_wpmu_options' ] );

		add_action( 'wpmu_options', [ $this, 'wpmu_options' ] );

	}

	/**
	 *	@action wp_uninitialize_site
	 */
	public function uninitialize_site( $wp_site ) {

		$this->model->delete( [ 'blog_id' => $wp_site->blog_id ] );

	}

	/**
	 *  @action wpmu_options
	 */
	public function wpmu_options() {

		?>
		<h3>
			<?php esc_html_e( 'Multisite Blog Alias', 'multisite-blog-alias' ); ?>
		</h3>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<label for="blog_alias_redirect_with_path_opt">
						<?php esc_html_e( 'Redirect with path', 'multisite-blog-alias' ); ?>
					</label>
				</th>
				<td>
					<input type="checkbox" name="blog_alias_redirect_with_path" id="blog_alias_redirect_with_path_opt" value="1" <?php checked( get_site_option( 'blog_alias_redirect_with_path' ), 1, true ); ?> />
					<label for="blog_alias_redirect_with_path_opt">
						<?php esc_html_e( 'Redirect with path', 'multisite-blog-alias' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'If checked the request path will be appended to the redirect URL.', 'multisite-blog-alias' ); ?>
					</p>
					<?php

					if ( defined( 'WPMU_BLOG_ALIAS_REDIRECT_WITH_PATH' ) ) {
						?>
						<div class="notice notice-warning inline">
							<p>
								<?php
								printf(
									/* translators: 1: name of constant 2: wp-config.php filename */
									esc_html__( 'This setting is overridden by the constant %1$s in your %2$s', 'multisite-blog-alias' ),
									'<code>WPMU_BLOG_ALIAS_REDIRECT_WITH_PATH</code>',
									'<code>wp-config.php</code>'
								);
								?>
							</p>
						</div>
						<?php
					}
					?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="blog_alias_redirect_wp_admin_opt">
						<?php esc_html_e( 'Redirect wp-admin', 'multisite-blog-alias' ); ?>
					</label>
				</th>
				<td>
					<input type="checkbox" name="blog_alias_redirect_wp_admin" id="blog_alias_redirect_wp_admin_opt" value="1" <?php checked( get_site_option( 'blog_alias_redirect_wp_admin' ), 1, true ); ?> />
					<label for="blog_alias_redirect_wp_admin_opt">
						<?php esc_html_e( 'Redirect Admin Path', 'multisite-blog-alias' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'If checked wp-login.php and wp-admin/ paths request path will be appended to the redirect URL.', 'multisite-blog-alias' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 *  @action update_wpmu_options
	 */
	public function update_wpmu_options() {

		check_admin_referer( 'siteoptions' );

		$with_path       = 0;
		$with_admin_path = 0;

		if ( isset( $_POST['blog_alias_redirect_with_path'] ) ) {
			$with_path = intval( $_POST['blog_alias_redirect_with_path'] );
		}
		if ( isset( $_POST['blog_alias_redirect_wp_admin'] ) ) {
			$with_admin_path = intval( $_POST['blog_alias_redirect_wp_admin'] );
		}

		update_site_option( 'blog_alias_redirect_with_path', $with_path );
		update_site_option( 'blog_alias_redirect_wp_admin', $with_admin_path );

	}


	/**
	 *  @filter plugin_action_links_{$plugin_basename}
	 */
	public function add_uninstall_action( $links, $plugin_file, $plugin_data, $context ) {

		if ( current_user_can( 'manage_network_plugins' ) && current_user_can( 'activate_plugins' ) ) {
			$url = network_admin_url( 'admin.php' );
			$url = add_query_arg( [
				'action'    => $this->uninstall_action,
				'nonce'     => wp_create_nonce( $this->uninstall_action ),
			], $url );
			$links[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $url ),
				esc_html__( 'Uninstall', 'multisite-blog-alias' )
			);
		}
		return $links;
	}

	/**
	 *  @action admin_action_multisite-blog-alias-instructions
	 */
	public function instructions_action() {
		// check capabilites

		if ( ! ( current_user_can( 'manage_network_plugins' ) ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to install plugins.', 'multisite-blog-alias' ) );
		}

		// ask for confirmation
		$this->admin_header( 'plugins.php' );

		$sunrise = Core\Sunrise::instance();
		$has_sunrise = file_exists( $sunrise->location );
		$location_of_wp_config = ABSPATH;
		if ( ! file_exists( ABSPATH . 'wp-config.php' ) && file_exists( dirname( ABSPATH ) . '/wp-config.php' ) ) {
			$location_of_wp_config = dirname( $abspath_fix );
		}
		$location_of_wp_config = trailingslashit( $location_of_wp_config );

		?>
		<div class="wrap">
			<h1>
				<?php esc_html_e( 'Multisite Blog Alias Setup', 'multisite-blog-alias' ); ?>
			</h1>
			<?php
				if ( $this->is_configured() ) {
				?>
					<div class="notice notice-success">
						<p>
							<?php esc_html_e( 'The plugin is well configured. The instructions are kept for documentation purposes.', 'multisite-blog-alias' ); ?>
						</p>
					</div>
				<?php
				}
			?>
			<p>
				<?php
					esc_html_e( 'The plugin could not write to the filesystem.', 'multisite-blog-alias' );
					esc_html_e( 'Please change the following.', 'multisite-blog-alias' );
				?>
			</p>
			<ol>
				<?php if ( ! defined( 'SUNRISE' ) || $this->is_configured() ) { ?>
					<li>
						<p>
							<?php
							// stolen from WP-Core
							wp_kses( sprintf(
								/* translators: 1: wp-config.php, 2: location of wp-config file, 3: translated version of "That's all, stop editing! Happy publishing." */
								__( 'Add the following to your %1$s file in %2$s <strong>above</strong> the line reading %3$s:' ),
								'<code>wp-config.php</code>',
								'<code>' . $location_of_wp_config . '</code>',
								/*
								 * translators: This string should only be translated if wp-config-sample.php is localized.
								 * You can check the localized release package or
								 * https://i18n.svn.wordpress.org/<locale code>/branches/<wp version>/dist/wp-config-sample.php
								 */
								'<code>/* ' . __( 'That&#8217;s all, stop editing! Happy publishing.' ) . ' */</code>'
							), [
								'code' => [],
								'strong' => [],
							] );
							?>
						</p>
						<textarea class="code" readonly="readonly" cols="100" rows="2">
define('SUNRISE', true);</textarea>
					</li>
				<?php } ?>

				<li>
					<p>
						<?php
						if ( $has_sunrise ) {
							printf(
								/* translators: sunrise.php file location */
								esc_html__( 'Insert the following code into %s:', 'multisite-blog-alias' ),
								'<code>' . esc_html( $sunrise->location ) . '</code>'
							);
						} else {
							printf(
								/* translators: sunrise.php file location */
								esc_html__( 'Create a file %s with the following code:', 'multisite-blog-alias' ),
								'<code>' . esc_html( $sunrise->location ) . '</code>'
							);
						}
						?>
					</p>
					<textarea class="code" readonly="readonly" cols="100" rows="9">
<?php
if ( ! $has_sunrise ) {
			echo '<?php' . "\n\n";
}
echo esc_textarea( $sunrise->code );
?>
</textarea>

				</li>
			</ol>

		</div>
		<?php

		$this->admin_footer();

	}

	/**
	 *  @action admin_action_uninstall-multisite-blog-alias
	 */
	public function uninstall_action() {
		global $action;

		check_admin_referer( $action, 'nonce' );

		// check capabilites
		if ( ! ( current_user_can( 'manage_network_plugins' ) && current_user_can( 'activate_plugins' ) ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to run the uninstall procedere.', 'multisite-blog-alias' ) );
		}

		//
		if ( isset( $_REQUEST['confirm'] ) ) {
			// exec
			if ( $_REQUEST['confirm'] === 'yes' ) {
				// vaR_dump('drop','deactivate');exit();
				$this->model->drop();
				deactivate_plugins( $this->core->get_wp_plugin(), false, true );

				wp_safe_redirect( add_query_arg( 'deactivate', '1', network_admin_url( 'plugins.php' ) ) );
			} else {
				wp_safe_redirect( network_admin_url( 'plugins.php' ) );
			}
		} else {
			// ask for confirmation
			$this->admin_header( 'plugins.php' );
			?>
			<div class="card card-warning">
				<h2>
					<?php esc_html_e( 'Uninstall Plugin?', 'multisite-blog-alias' ); ?>
				</h2>
				<p>
					<?php esc_html_e( 'Uninstalling the plugin will remove the Blog Alias table from the database and deactivate the plugin.', 'multisite-blog-alias' ); ?>
				</p>
				<p><strong>
					<?php
						$count = $this->model->fetch_count();
						/* Translators: %d number of alias domains */
						esc_html_e(
							sprintf(
								/* translators: number of domains being deleted on plugin uninstall */
								_n( '%d Alias Domain will be deleted.', '%d Alias Domains will be deleted.', $count, 'multisite-blog-alias' ),
								$count
							)
						);
					?>
				</strong></p>
				<form method="post">
					<a href="<?php esc_attr_e( network_admin_url( 'plugins.php' ) ); ?>" class="button">
						<?php esc_html_e( 'No, back to plugins', 'multisite-blog-alias' ); ?>
					</a>
					<button class="button button-primary" name="confirm" value="yes">
						<?php esc_html_e( 'Yes, Uninstall now!', 'multisite-blog-alias' ); ?>
					</button>
				</form>
			</div>
			<?php

			$this->admin_footer();
		}
	}

	/**
	 *  @return boolean
	 */
	private function is_configured() {
		return defined( 'SUNRISE' ) && function_exists( 'blog_alias_site_not_found' );
	}



	/**
	 *  @action admin_action_alias-domain-add
	 */
	public function add_alias_domain() {

		check_admin_referer( 'alias-domain-add' );

		current_user_can( $this->cap ) || wp_die( esc_html__( 'Insufficient permission' ) );

		if ( isset( $_POST['blog_id'] ) ) {
			$blog_id = absint( $_POST['blog_id'] );
		}
		if ( ! $blog_id ) {
			wp_die( esc_html__( 'Invalid request' ) );
		}

		$redirect_args = [
			'id'     => $blog_id,
			'action' => 'alias-domains',
		];

		$domain_alias_input = '';

		if ( isset( $_POST['domain_alias'] ) ) {
			$domain_alias_input = sanitize_text_field( wp_unslash( $_POST['domain_alias'] ) );
		}

		$data = $this->model->create_insert_data( $blog_id, $domain_alias_input );

		if ( is_wp_error( $data ) ) {

			$redirect_args['error'] = $data->get_error_code();
			$redirect_args['error_data'] = rawurlencode( json_encode( $data->get_error_data() ) );

		} else {

			$id = $this->model->insert_blog_alias( $data );

			if ( is_wp_error( $id ) ) {
				$redirect_args['error']      = $id->get_error_code();
				$redirect_args['error_data'] = rawurlencode( json_encode( $id->get_error_data() ) );

			} else {
				$redirect_args['created'] = '1';

			}
		}

		wp_safe_redirect( add_query_arg( $redirect_args, network_admin_url( 'admin.php?action=alias-domains' ) ) );

		exit();
	}


	/**
	 *  @action admin_action_alias-domain-remove
	 */
	public function remove_alias_domain() {

		current_user_can( $this->cap ) || wp_die( esc_html__( 'Insufficient permission' ) );

		$id = $blog_id = false;

		if ( isset( $_POST['id'] ) ) {
			$id = absint( $_POST['id'] );
		}
		if ( ! $id ) {
			wp_die( esc_html__( 'Invalid request' ) );
		}

		check_admin_referer( 'alias-domain-remove-' . $id );

		if ( isset( $_POST['blog_id'] ) ) {
			$blog_id = absint( $_POST['blog_id'] );
		}

		if ( ! $blog_id ) {
			wp_die( esc_html__( 'Invalid request' ) );
		}

		$redirect_args = [
			'id'     => $blog_id,
			'action' => 'alias-domains',
		];

		$total = $this->model->remove_blog_alias_by( 'ID', $id );

		if ( ! is_wp_error( $total ) ) {
			$redirect_args['deleted'] = $total;
		} else {
			$redirect_args['error']      = $total->get_error_code();
			$redirect_args['error_data'] = rawurlencode( json_encode( $total->get_error_data() ) );
		}

		wp_safe_redirect( add_query_arg( $redirect_args, network_admin_url( 'admin.php' ) ) );
		exit();
	}

	/**
	 *  @action admin_action_alias-domain-remove-all
	 */
	public function remove_alias_domains() {

		check_admin_referer( 'alias-domain-remove-all' );

		current_user_can( $this->cap ) || wp_die( esc_html__( 'Insufficient permission' ) );

		$blog_id = false;
		if ( isset( $_POST['blog_id'] ) ) {
			$blog_id = absint( $_POST['blog_id'] );
		}
		if ( ! $blog_id ) {
			wp_die( esc_html__( 'Invalid request' ) );
		}

		$redirect_args = [
			'id'     => $blog_id,
			'action' => 'alias-domains',
		];

		$deleted = $this->model->remove_blog_alias_by( 'blog_id', $blog_id );

		if ( ! is_wp_error( $deleted ) ) {
			$redirect_args['deleted'] = $deleted;

		} else {
			$redirect_args['error']      = $deleted->get_error_code();
			$redirect_args['error_data'] = rawurlencode( json_encode( $deleted->get_error_data() ) );

		}

		wp_safe_redirect( add_query_arg( $redirect_args, network_admin_url( 'admin.php?action=alias-domains' ) ) );
		exit();
	}

	/**
	 *  @filter network_edit_site_nav_links
	 */
	public function edit_site_nav_links( $links ) {
		$links['alias'] = [
			'label'    => __( 'Alias Domains', 'multisite-blog-alias' ),
			'url'      => 'admin.php?action=alias-domains',
			'cap'      => $this->cap,
		];
		return $links;
	}

	/**
	 *	Output Admin alias site
	 *
	 *  @action admin_action_alias-domains
	 */
	public function admin_alias_domains() {

		if ( ! current_user_can( 'manage_network' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to edit this site.' ) );
		}

		$id = isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : 0;

		if ( ! $id ) {
			wp_die( esc_html__( 'Invalid site ID.' ) );
		}


		$details = get_site( $id );
		if ( ! $details ) {
			wp_die( esc_html__( 'The requested site does not exist.' ) );
		}

		if ( ! can_edit_network( $details->site_id ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.' ), 403 );
		}

		$this->blog_details = $details;

		add_filter( 'removable_query_args', '__return_empty_array' );
		/* translators: blogname */
		$title = sprintf( esc_html__( 'Edit Site: %s' ), esc_html( $this->blog_details->blogname ) );

		$this->admin_header( 'sites.php', $title );
		$this->admin_alias_body();
		$this->admin_footer();
	}

	/**
	 *  Print blog admin page header
	 */
	private function admin_header( $parent = '', $page_title = false ) {

		global $title;

		// handle actions here!
		$this->current_menu_parent = $parent;

		add_filter( 'parent_file', [ $this, 'get_current_menu_parent' ] );
		add_filter( 'submenu_file', [ $this, 'get_current_menu_parent' ] );

		Asset\Asset::get('css/admin/network/alias.css')->enqueue();

		Asset\Asset::get('js/admin/network/alias.js')
			->deps( [ 'jquery' ] )
			->enqueue();

		if ( $page_title !== false ) {
			$title = $page_title; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		require ABSPATH . 'wp-admin/admin-header.php';

	}

	/**
	 *	@filter parent_file
	 *	@filter submenu_file
	 */
	public function get_current_menu_parent( $parent ) {
		if ( ! is_null( $this->current_menu_parent ) ) {
			return $this->current_menu_parent;
		}
		return $parent;
	}

	/**
	 *  Print blog admin form
	 */
	private function admin_alias_body() {

		global $title;

		$messages = [
			'error'     => '',
			'success'   => '',
		];

		if ( ! $this->is_configured() ) {
			$messages['error'] = sprintf( '<strong>%1$s</strong> %2$s',
				esc_html__( 'Not Configured:', 'multisite-blog-alias' ),
				sprintf(
					/* Translators: link to setup page */
					esc_html__( 'Multisite Blog Alias is not configured. Please visit %s for instructions.', 'multisite-blog-alias' ),
					sprintf(
						'<a href="%s">%s</a>',
						esc_url( add_query_arg( 'action', $this->instructions_action, network_admin_url( 'admin.php' ) ) ),
						esc_html__( 'the setup page', 'multisite-blog-alias' )
					)
				)
			);

		}

		// show resopnse message
		if ( isset( $_GET['created'] ) ) {
			$messages['updated'] = esc_html__( 'Alias created', 'multisite-blog-alias' );

		} else if ( isset( $_GET['deleted'] ) ) {
			$deleted = intval( $_GET['deleted'] );
			/* translators: number of deleted entries */
			$messages['notice-warning'] = esc_html( sprintf( _n( '%d entry deleted', '%d entries deleted', $deleted, 'multisite-blog-alias' ), $deleted ) );

		} else if ( isset( $_GET['error'] ) ) {

			$model = Model\AliasDomains::instance();

			$error_code = sanitize_text_field( wp_unslash( $_GET['error'] ));
			$error_data = null;
			if ( isset( $_GET['error_data'] ) ) {
				$error_data = json_decode( wp_unslash( $_GET['error_data'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			}

			$error = $model->get_error( $error_code, $error_data );
			if ( ( $data = $error->get_error_data() ) && isset( $_GET['id'] ) && (int) $data->blog_id === (int) wp_unslash( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$messages['notice-warning'] = sprintf( '<strong>%1$s</strong> %2$s',
					__( 'Notice:', 'multisite-blog-alias' ),
					__( 'The domain matches the site URL of this blog.', 'multisite-blog-alias' )
				);
			} else {
				$messages['error'] = sprintf( '<strong>%1$s</strong> %2$s',
					__( 'Error:', 'multisite-blog-alias' ),
					$error->get_error_message()
				);
			}
		}

		?>

		<div class="wrap admin-domain-alias">
		<h1 id="edit-site">
			<?php esc_html_e( $title ); ?>
		</h1>

		<p class="edit-site-actions">
			<a href="<?php echo esc_url( get_home_url( $this->blog_details->id, '/' ) ); ?>">
				<?php esc_html_e( 'Visit' ); ?>
			</a> |
			<a href="<?php echo esc_url( get_admin_url( $this->blog_details->id ) ); ?>">
				<?php esc_html_e( 'Dashboard' ); ?>
			</a>
		</p>
		<?php

			network_edit_site_nav( [
				'blog_id'  => $this->blog_details->id,
				'selected' => 'alias',
			] );

			foreach ( $messages as $type => $msg ) {
				if ( ! empty( $msg ) ) {
					printf(
						'<div id="message-%1$s" class="%1$s notice is-dismissible"><p>%2$s</p></div>',
						sanitize_key( $type ),
						wp_kses( $msg, [
							'strong' => [],
							'code' => [],
							'a' => [ 'href' => [], 'rel' => [], 'target' => [] ],
						] )
					);
				}
			}

			$aliases = $this->model->fetch_by( 'blog_id', $this->blog_details->id );

			if ( count( $messages ) ) {
				$url = add_query_arg( ['action' => 'alias-domains', 'id' => $this->blog_details->id ], network_admin_url( 'admin.php' ) )
				?>
				<script>history.replaceState({},[],<?php echo json_encode( $url ) ?>);</script>
				<?php
			}

			// form
			?>
			<!-- add -->
			<h2>
				<?php esc_html_e( 'Add Domain Alias', 'multisite-blog-alias' ); ?>
			</h2>
			<form method="post" action="admin.php?action=alias-domain-add">
				<?php wp_nonce_field( 'alias-domain-add' ); ?>
				<input type="hidden" name="blog_id" value="<?php esc_attr_e( $this->blog_details->id ); ?>" />
				<table class="widefat striped">
					<tbody>
						<tr>
							<td>
								<input id="add-domain-alias" placeholder="subdomain.domain.tld" type="text" name="domain_alias" class="widefat code" required />
							</td>
							<td class="action-links">
								<button class="button-primary" type="submit">
									<?php esc_html_e( 'Add', 'multisite-blog-alias' ); ?>
								</button>
							</td>
						</tr>
					</tbody>
				</table>
			</form>
			<!-- remove -->
			<h2><?php esc_html_e( 'Domain Aliases', 'multisite-blog-alias' ); ?></h2>
			<?php

			if ( empty( $aliases ) ) {
				?>
					<p><?php esc_html_e( '– No Domain Aliases –', 'multisite-blog-alias' ); ?></p>
				<?php

			} else {
				?>
				<table class="widefat striped domain-status-list-table">
					<thead>
						<th><?php esc_html_e( 'Alias Domain', 'multisite-blog-alias' ); ?></th>
						<th class="status"><?php esc_html_e( 'Status', 'multisite-blog-alias' ); ?></th>
						<th class="action-links"></th>
					</thead>
					<tbody>
						<?php
						// edit aliases
						$admin = Ajax::instance();
						foreach ( $aliases as $alias ) {
							$get_status = add_query_arg(
								$admin->ajax_handler->request + [
									'alias_id'  => $alias->ID,
								],
								network_site_url( 'wp-admin/admin-ajax.php' )
							); // add_query_arg
							?>
							<tr>
								<td>
									<code>
										<?php
										if ( $alias->domain_alias_utf8 ) {
											esc_html_e( $alias->domain_alias_utf8 );
										} else {
											esc_html_e( $alias->domain_alias );
										}
										?>
									</code>
								</td>

								<td class="status" data-check-status="<?php esc_attr_e( $get_status ); ?>"></td>
								<td class="action-links">
									<form method="post" action="admin.php?action=alias-domain-remove">
										<?php wp_nonce_field( 'alias-domain-remove-' . $alias->ID ); ?>
										<input type="hidden" name="blog_id" value="<?php esc_attr_e( $this->blog_details->id ); ?>" />
										<button class="button-secondary" type="submit" name="id" value="<?php esc_attr_e( $alias->ID ); ?>">
											<?php esc_html_e( 'Remove', 'multisite-blog-alias' ); ?>
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
							<th colspan="3" class="action-links">
								<form method="post" action="admin.php?action=alias-domain-remove-all">
									<?php wp_nonce_field( 'alias-domain-remove-all' ); ?>
									<a href="#" data-action="check-alias-status" class="button">
										<?php esc_html_e( 'Check Status', 'multisite-blog-alias' ); ?>
									</a>
									<button class="button-secondary" type="submit" name="blog_id" value="<?php esc_attr_e( $this->blog_details->id ); ?>">
										<?php esc_html_e( 'Remove All', 'multisite-blog-alias' ); ?>
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
	 *  Print blog admin page footer
	 */
	private function admin_footer() {
		require ABSPATH . 'wp-admin/admin-footer.php';
	}


}
