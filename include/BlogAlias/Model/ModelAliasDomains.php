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


class ModelAliasDomains extends Model {

	protected $fields = array(
		'ID'			=> '%d',
		'created'		=> '%s',
		'site_id'		=> '%d',
		'blog_id'		=> '%d',
		'domain_alias'	=> '%s',
		'redirect'		=> '%d',
	);


	/**
	 *	@inheritdoc
	 */
	protected $_table = 'alias_domains';

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		parent::__construct();
		if ( defined('FILTER_VALIDATE_DOMAIN') )  {
			add_filter("validate_{$this->_table}/domain_alias", array( $this, 'validate_domain_alias') );
		} else {
			add_filter("validate_{$this->_table}/domain_alias", array( $this, 'legacy_validate_domain_alias') );
		}

	}

	/**
	 *	Check alias status
	 *	Checks:
	 *	1. Is domain is used by another wp-site?
	 *	2. Is domain reachable and redirects to actual domain?
	 *
	 *	@param int|stdClass $alias Alias domain
	 *	@param int|null $site_id Check validity for current site
	 *	@return boolean|WP_Error
	 */
	public function check_status( $alias, $site_id = null ) {
		if ( is_numeric( $alias ) ) {
			$alias = $this->fetch_one_by( 'ID', $alias );
		}

		$site = get_site_by_path( $alias->domain_alias, '/' );

		$site_url = get_site_url( $alias->blog_id );

		if ( ! $site_url ) {
			return new \WP_Error( 'site-not_found', __( 'WP-Site for this alias could not be found.', 'wpms-blog-alias' ) );
		}
		// test if used by other sites
		if ( $site !== false ) {
			if ( intval( $site->blog_id ) !== intval( $site_id ) ) {
				return new \WP_Error( 'usedby-ms_site', __( 'The domain is already used by another site.', 'wpms-blog-alias' ), $site );
			} else {
				return new \WP_Error( 'usedby-self', __( 'The domain matches the site URL of this blog.', 'wpms-blog-alias' ) );
			}
		}

		// test redirects
		$location = "http://{$alias->domain_alias}";
		$site_url = trailingslashit($site_url);

		while ( true ) {
			$response = wp_remote_head( $location, array(
				'redirection'	=> 0,
				'sslverify'		=> false,
			) );
			if ( is_wp_error( $response ) ) {
				return new \WP_Error( 'redirect-http_error', __( 'The domain is unreachable.', 'wpms-blog-alias' ), $response );
			}

			$loc = $response['headers']->offsetGet( 'location' );

			if ( ! $loc ) {
				return new \WP_Error( 'redirect-target_invalid', __( 'The domain or a redirect does not point to this blog.', 'wpms-blog-alias' ), $location );
			}
			$location = $loc;
			if ( $site_url === $location ) {
				// test passed!
				break;
			}
		}

		return true;
	}

	/**
	 *	validate callback for domain alias
	 *
	 *	@param string $alias Domain alias (valid hostname)
	 *	@return bool|string false if invalid, sanitized value otherwise
	 */
	public function validate_domain_alias( $alias ) {

		return filter_var( strtolower( trim( $alias ) ), FILTER_VALIDATE_DOMAIN );

	}
	/**
	 *	PHP 5.5 Legacy Domain name validation by regEx.
	 *
	 *	@param string $alias Domain alias (valid hostname)
	 *	@return bool|string false if invalid, sanitized value otherwise
	 */
	public function legacy_validate_domain_alias( $alias ) {
		$alias = strtolower( trim( $alias ) );
		if ( ! preg_match( '/^[a-z0-9][a-z0-9\-\_\.]+[a-z0-9]$/i', $alias ) ) {
			return false;
		}
		return $alias;
	}

	/**
	 *	@inheritdoc
	 */
	public function insert( $data, $format = null ) {
		$data['created'] = strftime('%Y-%m-%d %H:%M:%S');
		return parent::insert( $data, $format );
	}

	/**
	 *	@inheritdoc
	 */
	public function activate() {
		// create table
		$this->update_db();
	}

	/**
	 *	@inheritdoc
	 */
	public function upgrade( $new_version, $old_version ) {
		$this->update_db();
	}

	/**
	 *	@inheritdoc
	 */
	private function update_db(){
		global $wpdb, $charset_collate;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE $wpdb->alias_domains (
			`ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			`created` datetime NOT NULL default '0000-00-00 00:00:00',
			`site_id` bigint(20) unsigned NOT NULL,
			`blog_id` bigint(20) unsigned NOT NULL,
			`domain_alias` varchar(200) NOT NULL,
			`redirect` tinyint(2) NOT NULL default '1',
			PRIMARY KEY (`ID`),
			UNIQUE KEY `domain_alias` (`domain_alias`)
		) $charset_collate;";

		// updates DB
		dbDelta( $sql );
	}
}
