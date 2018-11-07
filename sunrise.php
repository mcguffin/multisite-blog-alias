<?php


//namespace BlogAlias;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


require_once __DIR__ . DIRECTORY_SEPARATOR . 'include/autoload.php';

function blog_alias_site_not_found( $current_site, $domain, $path ) {
	$model = BlogAlias\Model\ModelAliasDomains::instance();
	if ( $result = $model->fetch_one_by( 'domain_alias', $domain ) ) {
		global $wpdb;
		$scheme = is_ssl() ? 'https' : 'http';
		$blog = $wpdb->get_row( $wpdb->prepare("SELECT domain,path FROM $wpdb->blogs WHERE blog_id=%d", $result->blog_id));

		header( 'Location: ' . "{$scheme}://{$blog->domain}{$blog->path}" );
		exit();
	}
}

add_action( 'ms_site_not_found', 'blog_alias_site_not_found', 10, 3 );



//Core\Core::instance( __FILE__ );

//Model\ModelAliasDomains::instance();
