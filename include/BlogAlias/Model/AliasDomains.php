<?php
/**
 *	@package BlogAlias\Model
 *	@version 1.0.0
 *	2018-09-22
 */


namespace BlogAlias\Model;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


class AliasDomains extends Model {

	/**
	 *	@inheritdoc
	 */
	protected $columns = array(
		'ID'                => '%d', // intval
		'created'           => '%s',
		'site_id'           => '%d', // intval
		'blog_id'           => '%d', // intval
		'domain_alias'      => '%s',
		'domain_alias_utf8' => '%s',
		'redirect'          => '%d', // intval
	);

	/**
	 *	@inheritdoc
	 */
	protected $identifier_columns = array(
		'ID',
	);

	/**
	 *	@inheritdoc
	 */
	protected $_table = 'alias_domains';

	/**
	 *	@inheritdoc
	 */
	protected $_global_table = false;


	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		parent::__construct();

		add_filter( "sanitize_{$this->_table}/domain_alias", array( $this, 'sanitize_domain_alias' ) );
		add_filter( "validate_{$this->_table}/domain_alias", array( $this, 'validate_domain_alias' ), 10, 2 );

	}


	/**
	 *  Check alias status
	 *  Checks:
	 *  1. Is domain used by another wp-site?
	 *  2. Is domain reachable and redirects to actual blog domain?
	 *
	 *  @param int|stdClass $alias Alias domain
	 *  @param int|null $site_id Check validity for current site
	 *  @return boolean|WP_Error
	 */
	public function check_status( $alias ) {
		if ( is_numeric( $alias ) ) {
			$alias = $this->fetch_one_by( 'ID', $alias );
			if ( ! $alias ) {
				return new \WP_Error( 'not-an-alias', __( 'Domain alias not found.', 'multisite-blog-alias' ) );
			}
		}

		$site = get_site_by_path( $alias->domain_alias, '/' );

		$site_url = get_site_url( $alias->blog_id );

		if ( ! $site_url ) {
			return new \WP_Error( 'site-not_found', __( 'WP-Site for this alias could not be found.', 'multisite-blog-alias' ) );
		}
		// test if used by other sites
		if ( $site !== false ) {
			if ( intval( $site->blog_id ) !== intval( $alias->blog_id ) ) {
				return new \WP_Error( 'usedby-ms_site', __( 'The domain is already used by another site.', 'multisite-blog-alias' ), $site );
			} else {
				return new \WP_Error( 'usedby-self', __( 'The domain matches the site URL of this blog.', 'multisite-blog-alias' ) );
			}
		}

		// test redirects
		$location = trailingslashit( "http://{$alias->domain_alias}" );
		$site_url = trailingslashit( $site_url );

		while ( true ) {

			$response = wp_remote_head( $location, array(
				'redirection'   => 0,
				'sslverify'     => false,
			) );
			if ( is_wp_error( $response ) ) {

				return new \WP_Error( 'redirect-http_error', __( 'The domain is unreachable.', 'multisite-blog-alias' ), $response );

			}

			$loc = $response['headers']->offsetGet( 'location' );

			if ( ! $loc ) {
				return new \WP_Error( 'redirect-target_invalid', __( 'The domain or a redirect does not point to this blog.', 'multisite-blog-alias' ), $location );
			}
			$location = trailingslashit( $loc );
			if ( $site_url === $location ) {
				// test passed!
				break;
			}
		}

		return true;
	}

	/**
	 *  validate callback for domain alias
	 *
	 *  @param string $alias Domain alias (valid hostname)
	 *  @return bool|string false if invalid, sanitized value otherwise
	 */
	public function sanitize_domain_alias( $alias ) {

		return filter_var( strtolower( $alias ), FILTER_VALIDATE_DOMAIN, array( 'flags' => FILTER_FLAG_HOSTNAME ) );

	}

	/**
	 *  validate callback for domain alias
	 *
	 *  @param string $alias Domain alias (valid hostname)
	 *  @return bool
	 */
	public function validate_domain_alias( $valid, $alias ) {
		return false !== $this->sanitize_domain_alias( $alias );
	}


	/**
	 *	@inheritdoc
	 */
	protected function update_db(){
		global $wpdb, $charset_collate;

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$sql = "CREATE TABLE $wpdb->alias_domains (
			`ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			`created` datetime NOT NULL default '0000-00-00 00:00:00',
			`site_id` bigint(20) unsigned NOT NULL,
			`blog_id` bigint(20) unsigned NOT NULL,
			`domain_alias` varchar(255) CHARACTER SET ascii NOT NULL,
			`domain_alias_utf8` varchar(255) NOT NULL,
			`redirect` tinyint(2) NOT NULL default '1',
			PRIMARY KEY (`ID`),
			UNIQUE KEY `domain_alias` (`domain_alias`)
		) $charset_collate;";

		// updates DB
		dbDelta( $sql );
	}
}
