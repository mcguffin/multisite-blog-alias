<?php

use BlogAlias\Model;

/**
 *	@param int $blog_id
 *	@param string $domain_alias_input
 *	@return int|WP_Error ID of created alias or error
 */
function alias_domain_add( $blog_id, $domain_alias_input ) {

	$model = Model\AliasDomains::instance();

	$data = $model->create_insert_data( $blog_id, $domain_alias_input );

	if ( is_wp_error( $data ) ) {
		return $data;
	}

	return $model->insert( $data );
}

/**
 *	@param string $what ID, site_id, blog_id, domain_alias
 *	@param string $value
 *	@return int|WP_Error Number of deleted aliases or error
 */
function alias_domain_remove_by( $what = 'alias_domain', $value = null ) {

	$model = Model\AliasDomains::instance();

	return $model->remove_blog_alias_by( $what, $value );
}
