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

use BlogAlias\Ajax as CoreAjax;
use BlogAlias\Core;
use BlogAlias\Model;


class Ajax extends Core\Singleton {

	public $ajax_handler;
	/**
	 *  @inheritdoc
	 */
	protected function __construct() {
		$this->ajax_handler = new CoreAjax\AjaxHandler( 'domain-alias-status', [
			'capability'    => 'manage_network_options',
			'callback'      => [ $this, 'ajax_status' ],
		] );
		parent::__construct();
		// vaR_dump($this);exit();
	}

	/**
	 *  ajax callbaxk
	 */
	public function ajax_status( $args ) {
		header( 'Content-Type: text/html; charset=utf8' );

		$model = Model\AliasDomains::instance();

		// occupied by another domain...
		$result = $model->check_status( $args['alias_id'] );

		if ( is_wp_error( $result ) ) {
			$code = $result->get_error_code();

			if ( $code === 'usedby-self' ) {
				?>
				<div class="notice notice-warning inline">
					<p><strong>
						<?php esc_html_e( $result->get_error_message() ); ?>
					</strong></p>
				</div>
				<?php
			} else {
				?>
				<div class="notice error inline">
					<p>
						<strong>
							<?php esc_html_e( $result->get_error_message() ); ?>
						</strong>
						<?php

							$data = $result->get_error_data( $code );

							if ( $code === 'usedby-ms_site' ) {
								echo ' ';
								printf( '<a href="%s">%s</a>',
									esc_url( network_admin_url( 'site-info.php?id=' . $data->blog_id ) ),
									esc_html__( 'Edit', 'multisite-blog-alias' )
								);
								echo ' ';
								printf( '<a href="%s">%s</a>',
									esc_url( get_site_url( $data->blog_id ) ),
									esc_html__( 'View', 'multisite-blog-alias' )
								);
							}

						?>
					</p>
				</div>
				<?php

			}
		} else {
			$errors          = '';
			$ssl_status      = true;
			$redirect_status = true;

			?><ul class="muba-result-list"><?php

			foreach ( $result->report as $item ) {

				$ssl_status      &= $item->ssl_status;
				$redirect_status &= ! $item->redirect || WPMS_BLOG_ALIAS_REDIRECT_BY === $item->redirect_by;

				printf(
					'<li class="%1$s %2$s">',
					$item->redirect
						? 'url-redirect'
						: 'url-final',
					WPMS_BLOG_ALIAS_REDIRECT_BY !== $item->redirect_by
						? 'external-redirect'
						: 'internal-redirect'
				);

				printf(
					'<span class="url source"><span class="dashicons dashicons-%1$s %2$s"></span>%3$s</span>',
					$item->is_ssl
						? 'lock'
						: 'unlock',
					$item->ssl_status
						? ($item->is_ssl ? 'success' : 'none')
						: 'error',
					esc_html( $item->url )
				);


				if ( ! $item->redirect ) {
					printf(
						'<span class="dashicons dashicons-%1$s"></span>',
						! is_wp_error( $item->error )
							? 'yes success'
							: 'no error'
					);
				}

				if ( is_wp_error( $item->error ) ) {
					$errors .= sprintf('<div class="notice error inline">%s</div>', esc_html( $item->error->get_error_message() ) );
				}

				?></li><?php
			}

			?></ul>
			<?php

			if ( $result->success ) {
				$message_class = 'notice-success';
				$messages = [
					esc_html__( 'Redirects are working.', 'multisite-blog-alias' )
				];

				if ( ! $ssl_status ) {
					$message_class = 'notice-warning';
					$messages[] = sprintf(
						/* translators: lock symbol */
						esc_html__( 'However there are problems with your SSL-Certificates indicated by a red %s in the report above.', 'multisite-blog-alias' ),
						'<span class="dashicons dashicons-lock error"></span>'
					);
				}
				if ( ! $redirect_status ) {
					$messages[] = esc_html__( 'Some of the redirects (the gray ones) are not triggered by this system.', 'multisite-blog-alias' );
				}
				printf(
					'<div class="notice %1$s inline">%2$s</div>',
					sanitize_html_class( $message_class ),
					wp_kses_post( implode( '<br />', $messages ) )
				);
			} else {
				echo wp_kses_post( $errors );
			}
		}

		// print
		exit();
	}


}
