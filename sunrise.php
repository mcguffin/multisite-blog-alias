<?php


//namespace BlogAlias;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


function blog_alias_site_not_found( $current_site, $domain, $path ) {

	if ( '' === get_site_option( 'multisite_blog_alias_sunrise_active' ) ) {
		return;
	}

	require_once ABSPATH . WPINC . '/kses.php'; // dep of pluggable
	require_once ABSPATH . WPINC . '/pluggable.php'; // wp_sanitize_redirect()
	require_once ABSPATH . WPINC . '/formatting.php'; // untrailingslashit()
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'include/autoload.php';

	$model = BlogAlias\Model\ModelAliasDomains::instance();

	if ( $result = $model->fetch_one_by( 'domain_alias', $domain ) ) {
		global $wpdb;

		// get site url
		switch_to_blog( $result->blog_id );
		$site_url = get_option( 'siteurl' );
		restore_current_blog();

		if ( defined('WPMU_BLOG_ALIAS_REDIRECT_WITH_PATH') && WPMU_BLOG_ALIAS_REDIRECT_WITH_PATH ) {
			$redirect = untrailingslashit( $site_url ) . $path;
		} else {
			$redirect = trailingslashit( $site_url );
		}

		http_response_code(301);
		header( "X-Redirect-By: WPMS-Blog-Alias" );
		header( 'Location: ' . wp_sanitize_redirect( $redirect ) );

		exit();
	}
}

add_action( 'ms_site_not_found', 'blog_alias_site_not_found', 10, 3 );
