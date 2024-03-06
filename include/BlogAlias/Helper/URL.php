<?php
/**
 *	@package BlogAlias\Model
 *	@version 1.2.0
 *	2014-03-06
 */


namespace BlogAlias\Helper;

class URL {

	private static $curl_ssl_errors = [
		'35' => 'SSL_CONNECT_ERROR',
		'51' => 'PEER_FAILED_VERIFICATION',
		'53' => 'SSL_ENGINE_NOTFOUND',
		'54' => 'SSL_ENGINE_SETFAILED',
		'58' => 'SSL_CERTPROBLEM',
		'59' => 'SSL_CIPHER',
		'60' => 'SSL_CACERT',
		'64' => 'USE_SSL_FAILED',
		'66' => 'SSL_ENGINE_INITFAILED',
		'77' => 'SSL_CACERT_BADFILE',
		'80' => 'SSL_SHUTDOWN_FAILED',
		'82' => 'SSL_CRL_BADFILE',
		'83' => 'SSL_ISSUER_ERROR',
		'90' => 'SSL_PINNEDPUBKEYNOTMATCH',
		'91' => 'SSL_INVALIDCERTSTATUS',
	];

	/**
	 *	@param string $url
	 *	@return object
	 */
	private static function test_url( $url ) {
		$result = (object) [
			'url'                 => $url,
			'is_ssl'              => strpos( $url, 'https://' ) === 0,
			'ssl_status'          => true,
			'error'               => null,
			'redirect'            => null,
			'redirect_by'         => null,
		];

		$response = wp_remote_head( $url, [
			'redirection'   => 0,
			'sslverify'     => true,
		] );

		if ( is_wp_error( $response ) ) {
			// ssl error
			if ( preg_match('/cURL error (\d+):/', $response->get_error_message(), $matches ) ) {
				if ( count( $matches ) >= 2 && isset( self::$curl_ssl_errors[ $matches[1] ] ) ) {
					$result->ssl_status = false;
					$response = wp_remote_head( $url, [
						'redirection'   => 0,
						'sslverify'     => false,
					] );
				}
			}
		}

		if ( is_wp_error( $response ) ) {
			$result->error = $response;//new \WP_Error( 'redirect-http_error', __( 'The domain is unreachable.', 'multisite-blog-alias' ), $response );
			return $result;
		}

		// 3. Redirected?
		$redirect = $response['headers']->offsetGet( 'location' );

		// nope
		if ( is_null( $redirect ) ) {
			return $result;
		}

		// redireced to a new host?
		$parsed = parse_url( $redirect );

		if ( ! isset( $parsed['host'] ) && isset( $parsed['path'] ) ) {
			$redirect = untrailingslashit( $url ) . $parsed['path'];
		}

		$result->redirect       = $redirect;
		$result->redirect_by    = $response['headers']->offsetGet( 'x-redirect-by' );

		return $result;
	}


	/**
	 *	@param string $source_url
	 *	@param string $dest_url
	 *	@return object [ 'success' => boolean, 'report' => [ ] ]
	 */
	public static function test_redirect( $source_url, $dest_url ) {

		$max_redirect = 10;
		$count = 0;

		$result = (object) [
			'success' => false,
			'report'  => [],
		];

		$location = $source_url;

		while ( true ) {

			if ( isset( $result->report[$location] ) ) {
				$result->report[$location]->redirect = false;
				$result->report[$location]->error = new \WP_Error( 'redirect-inifinite', __( 'Circular Redirect.', 'multisite-blog-alias' ) );
				return $result;
			}

			$tested = self::test_url( $location );

			$result->report[$location] = $tested;

			if ( $count >= $max_redirect ) {
				/* translators: number of redirects */
				$result->report[$location]->error = new \WP_Error( 'redirect-max_exceeded', sprintf( __( 'Maximum number of %d Redirects reached', 'multisite-blog-alias' ), $max_redirect ) );
			}

			if ( ! $tested->redirect ) {
				$result->success = $tested->url === $dest_url;
				break;
			}
			$count++;
			$location = $tested->redirect;
		}
		if ( ! $result->success ) {
			$result->report[$location]->error = new \WP_Error( 'redirect-target_invalid', __( 'The domain or a redirect does not point to this blog.', 'multisite-blog-alias' ), $location );
		}

		return $result;
	}

}
