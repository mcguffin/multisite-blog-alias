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



	/** @var string plugin uninstall action name */
	private $uninstall_action = 'uninstall-multisite-blog-alias';
	private $instructions_action = 'multisite-blog-alias-instructions';


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

		add_filter( 'network_admin_plugin_action_links_'. $this->core->get_wp_plugin(), array( $this, 'add_uninstall_action' ), 10, 4 );
		add_action( 'admin_action_' . $this->uninstall_action, array( $this, 'uninstall_action' ) );

		add_action( 'admin_action_' . $this->instructions_action, array( $this, 'instructions_action' ) );

	}


	/**
	 *	@filter plugin_action_links_{$plugin_basename}
	 */
	public function add_uninstall_action( $links, $plugin_file, $plugin_data, $context ) {

		if ( current_user_can( 'manage_network_plugins' ) && current_user_can('activate_plugins') ) {
			$url = network_admin_url('admin.php');
			$url = add_query_arg( array(
				'action'	=> $this->uninstall_action,
				'nonce'		=> wp_create_nonce( $this->uninstall_action ),
			), $url );
			$links[] = sprintf('<a href="%s">%s</a>', $url, __( 'Uninstall', 'multisite-blog-alias' ) );
		}
		return $links;
	}

	/**
	 *	@action admin_action_multisite-blog-alias-instructions
	 */
	public function instructions_action() {
		// check capabilites

		if ( ! ( current_user_can( 'manage_network_plugins' ) ) ) {
			wp_die( __( 'Sorry, you are not allowed to install plugins.', 'multisite-blog-alias' ) );
		}

		// ask for confirmation
		$this->admin_header( 'plugins.php' );

		$sunrise =  Core\Sunrise::instance();
		$has_sunrise = file_exists( $sunrise->location );
		$location_of_wp_config = ABSPATH;
		if ( ! file_exists( ABSPATH . 'wp-config.php' ) && file_exists( dirname( ABSPATH ) . '/wp-config.php' ) ) {
			$location_of_wp_config = dirname( $abspath_fix );
		}
		$location_of_wp_config = trailingslashit( $location_of_wp_config );

		?>
		<div class="wrap">
			<h1><?php _e( 'Multisite Blog Alias Setup', 'multisite-blog-alias' ); ?></h1>
			<?php
				if ( $this->is_configured() ) {
					?>
					<div class="notice notice-success">
						<p><?php _e( 'The plugin is well configured. The instructions are kept for documentation purposes.', 'multisite-blog-alias' ); ?></p>
					</div>
					<?php
				}
			?>
			<p>
				<?php
					_e( 'The plugin could not write to the filesystem.','multisite-blog-alias');
					_e( 'Please change the following.','multisite-blog-alias');
				?>
			</p>
			<ol>
				<?php if ( ! defined('SUNRISE') || $this->is_configured() ) { ?>
					<li>
						<p><?php
						// stolen from WP-Core
						printf(
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
						);
						?></p>
						<textarea class="code" readonly="readonly" cols="100" rows="2">
define('SUNRISE', true);</textarea>
					</li>
				<?php } ?>

				<li>
					<p>
						<?php
						if ( $has_sunrise ) {
							/* translators: Sunrise file location */
							printf( __('Insert the following code into %s:', 'multisite-blog-alias' ), '<code>'.$sunrise->location.'</code>' );
						} else {
							/* translators: Sunrise file location */
							printf( __('Create a file %s with the following code:', 'multisite-blog-alias' ), '<code>'.$sunrise->location.'</code>' );
						}
						?>
					</p>
					<textarea class="code" readonly="readonly" cols="100" rows="9">
<?php
if ( ! $has_sunrise ) {
	echo '<?php' ."\n\n";
}
echo $sunrise->code; ?></textarea>

				</li>
			</ol>

		</div>
		<?php

		$this->admin_footer();

	}

	/**
	 *	@action admin_action_uninstall-multisite-blog-alias
	 */
	public function uninstall_action() {

		// check nonce
		check_admin_referer( $_REQUEST['action'], 'nonce' );

		// check capabilites
		if ( ! ( current_user_can( 'manage_network_plugins' ) && current_user_can('activate_plugins') ) ) {
			wp_die( __( 'Sorry, you are not allowed to run the uninstall procedere.', 'multisite-blog-alias' ) );
		}

		$model = Model\ModelAliasDomains::instance();

		//
		if ( isset( $_REQUEST['confirm'] ) ) {
			// exec
			if ( $_REQUEST['confirm'] === 'yes' ) {
				// vaR_dump('drop','deactivate');exit();
				$model->drop();
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
				<h2><?php _e( 'Uninstall Plugin?', 'multisite-blog-alias' ); ?></h2>
				<p><?php _e( 'Uninstalling the plugin will remove the Blog Alias table from the database and deactivate the plugin.','multisite-blog-alias'); ?></p>
				<p><strong><?php
					$count = $model->fetch_count();
					/* Translators: %d number of alias domains */
					printf( _n('%d Alias Domain will be deleted.', '%d Alias Domains will be deleted.', $count, 'multisite-blog-alias'), $count );
				?></strong></p>
				<form method="post">
					<a href="<?php esc_attr_e( network_admin_url( 'plugins.php' ) ); ?>" class="button"><?php _e('No, back to plugins','multisite-blog-alias'); ?></a>
					<button class="button button-primary" name="confirm" value="yes"><?php _e('Yes, Uninstall now!','multisite-blog-alias'); ?></button>
				</form>
			</div>
			<?php

			$this->admin_footer();
		}
	}

	/**
	 *	@return boolean
	 */
	private function is_configured() {
		return defined('SUNRISE') && function_exists( 'blog_alias_site_not_found' );
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
			if ( $record->blog_id != $blog_id ) {
				$redirect_args['other_blog']	= $record->blog_id;
			}
		} else if ( $other_blog_id = get_blog_id_from_url( $domain_alias ) ) {
			if ( $other_blog_id != $blog_id ) {
				$redirect_args['error']			= 'add-site-exists';
				$redirect_args['other_blog']	= $other_blog_id;
			} else {
				$redirect_args['notice']		= 'add-is-self';
			}
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
			 'label'	=> __( 'Alias Domains', 'multisite-blog-alias' ),
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

		$title = sprintf( __( 'Edit Site: %s' ), esc_html( $this->blog_details->blogname ) );

		$this->admin_header('sites.php', $title );
		$this->admin_alias_body();
		$this->admin_footer();
	}

	/**
	 *	Print blog admin page header
	 */
	private function admin_header( $parent = '', $page_title = false ) {

		// handle actions here!

		global $parent_file, $submenu_file, $title;

		$parent_file = $parent;
		$submenu_file = $parent;
		$title = $page_title;

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

		if ( ! $this->is_configured() ) {
			$messages['error'] = sprintf( '<strong>%1$s</strong> %2$s' ,
				__( 'Not Configured:', 'multisite-blog-alias' ),
				sprintf(
					/* Translators: link to setup page */
					__( 'Multisite Blog Alias is not configured. Please visit %s for instructions.', 'multisite-blog-alias' ),
					sprintf(
						'<a href="%s">%s</a>',
						add_query_arg( 'action', $this->instructions_action, network_admin_url( 'admin.php' )),
						__( 'the setup page', 'multisite-blog-alias' )
					)
				)
			);

		}

		if ( isset( $_GET['created'] ) ) {
			$messages['updated'] = __( 'Alias created', 'multisite-blog-alias');
		} else if ( isset( $_GET['deleted'] ) ) {
			/* translators: number of deleted entries */
			$messages['notice-warning'] = sprintf( _n('%d entry deleted', '%d entries deleted', $_GET['deleted'], 'multisite-blog-alias' ), $_GET['deleted'] );
		} else if ( isset( $_GET['error'] ) ) {
			$errors = array(
				'add-alias-exists'		=> __( 'The Alias already exists.', 'multisite-blog-alias' ),
				'add-invalid-domain'	=> __( 'Invalid domain name', 'multisite-blog-alias' ),
				'delete'				=> __( 'Deletion failed', 'multisite-blog-alias'  ),
				'add-site-exists'		=> __( 'A different Blog is already using this domain.', 'multisite-blog-alias' ),
				'default'				=> __( 'Something went wrong...', 'multisite-blog-alias' ),
			);
			$messages['error'] = sprintf( '<strong>%1$s</strong> %2$s' ,
				__( 'Error:', 'multisite-blog-alias' ),
				isset( $errors[ $_GET['error'] ] ) ? $errors[ $_GET['error'] ] : $errors['default']
			);

			if ( isset( $_GET['other_blog'] ) ) {
				$messages['error'] .= sprintf( ' <a href="%s">%s</a> | <a href="%s">%s</a>',
					esc_url( get_site_url( $_GET['other_blog'] ) ),
					__( 'Visit other Blog', 'multisite-blog-alias' ),

					esc_url( network_admin_url( 'site-info.php?id=' . $_GET['other_blog'] ) ),
					__( 'Edit', 'multisite-blog-alias' )
				);

			}
		}
		if ( isset( $_GET['notice'] ) && $_GET['notice'] === 'add-is-self' ) {

			$messages['notice-warning'] = sprintf( '<strong>%1$s</strong> %2$s' ,
				__('Notice:','multisite-blog-alias'),
				__('The domain matches the site URL of this blog.','multisite-blog-alias')
			);
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
			<h2><?php _e('Add Domain Alias','multisite-blog-alias'); ?></h2>
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
								<button class="button-primary" type="submit"><?php _e('Add','multisite-blog-alias'); ?></button>
							</td>
						</tr>
					</tbody>
				</table>
			</form>
			<!-- remove -->
			<h2><?php _e('Domain Aliases','multisite-blog-alias'); ?></h2>
			<?php if ( empty( $aliases ) ) {

				?>
					<p><?php _e('– No Domain Aliases –', 'multisite-blog-alias'); ?></p>
				<?php

			} else {
				?>

				<table class="widefat striped domain-status-list-table">
					<thead>
						<th><?php _e('Alias Domain','multisite-blog-alias'); ?></th>
						<th class="status"><?php _e( 'Status', 'multisite-blog-alias' ) ?></th>
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
									'nonce'		=> $admin->ajax_handler->nonce,
								),
								network_site_url( 'wp-admin/admin-ajax.php')
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
											<?php _e('Remove','multisite-blog-alias'); ?>
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
									<a href="#" data-action="check-alias-status" class="button"><?php _e( 'Check Status', 'multisite-blog-alias' ) ?></a>
									<button class="button-secondary" type="submit" name="blog_id" value="<?php echo $this->blog_details->id; ?>">
										<?php _e('Remove All','multisite-blog-alias'); ?>
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
	private function admin_footer() {
		require( ABSPATH . 'wp-admin/admin-footer.php' );
	}


}
