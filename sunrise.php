<?php


//namespace BlogAlias;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


function blog_alias_site_not_found( $current_site, $domain, $path ) {

	require_once __DIR__ . DIRECTORY_SEPARATOR . 'include/autoload.php';

	$model = BlogAlias\Model\ModelAliasDomains::instance();

	if ( $result = $model->fetch_one_by( 'domain_alias', $domain ) ) {
		global $wpdb;
		switch_to_blog( $result->blog_id );
		$site_url = get_option( 'siteurl' );
		restore_current_blog();

		http_response_code(301);
		header( 'Location: ' . $site_url );

		exit();
	}
}

add_action( 'ms_site_not_found', 'blog_alias_site_not_found', 10, 3 );
