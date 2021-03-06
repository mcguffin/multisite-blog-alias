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
		$this->ajax_handler = new CoreAjax\AjaxHandler( 'domain-alias-status', array(
			'capability'    => 'manage_network_options',
			'callback'      => array( $this, 'ajax_status' ),
		) );
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
		$status = $model->check_status( $args['alias_id'] );

		if ( is_wp_error( $status ) ) {
			$code = $status->get_error_code();

			if ( $code === 'usedby-self' ) {
				?>
				<div class="notice notice-warning inline">
					<p><strong>
						<?php esc_html_e( $status->get_error_message() ); ?>
					</strong></p>
				</div>
				<?php
			} else {
				?>
				<div class="notice error inline">
					<p>
						<strong>
							<?php esc_html_e( $status->get_error_message() ); ?>
						</strong>
						<?php

							$data = $status->get_error_data( $code );

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
							} elseif ( $code === 'redirect-http_error' ) {
								printf(
									'<br />%s <code>%s</code>',
									esc_html__( 'Error message:', 'multisite-blog-alias' ),
									esc_html( $data->get_error_message() )
								);

							} elseif ( $code === 'redirect-target_invalid' ) {
								//
								printf( '<br />%1$s <a href="%2$s" rel="noopener noreferrer" target="_blank">%2$s</a>',
									esc_html__( 'Last Redirect to:', 'multisite-blog-alias' ),
									esc_url( $data )
								);
							}

						?>
					</p>
				</div>
				<?php

			}
		} else {
			echo '<span class="success dashicons dashicons-yes"></span>';
		}


		// print
		exit();
	}


}
