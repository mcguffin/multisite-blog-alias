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
	protected $columns = [
		'ID'                => '%d', // intval
		'created'           => '%s',
		'site_id'           => '%d', // intval
		'blog_id'           => '%d', // intval
		'domain_alias'      => '%s',
		'domain_alias_utf8' => '%s',
		'redirect'          => '%d', // intval
	];

	/**
	 *	@inheritdoc
	 */
	protected $identifier_columns = [
		'ID',
	];

	/**
	 *	@inheritdoc
	 */
	protected $_table = 'alias_domains';

	/**
	 *	@inheritdoc
	 */
	protected $_global_table = true;


	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		parent::__construct();

		add_filter( "sanitize_{$this->_table}/domain_alias", [ $this, 'sanitize_domain_alias' ] );
		add_filter( "validate_{$this->_table}/domain_alias", [ $this, 'validate_domain_alias' ], 10, 2 );

	}

	/**
	 *	@param string $error_code
	 *	@return string
	 */
	public function get_error( $error_code = 'unknown' ) {

		if ('add-blog-not-exists' === $error_code ) {
			return new \WP_Error( $error_code, __( 'Blog does not exists.', 'multisite-blog-alias' ) );

		} else if ('add-alias-exists' === $error_code ) {
			return new \WP_Error( $error_code, __( 'The Alias already exists.', 'multisite-blog-alias' ) );

		} else if ('add-empty-domain' === $error_code ) {
			return new \WP_Error( $error_code, __( 'Empty domain name', 'multisite-blog-alias' ) );

		} else if ('add-invalid-domain' === $error_code ) {
			return new \WP_Error( $error_code, __( 'Invalid domain name', 'multisite-blog-alias' ) );

		} else if ('add-site-exists' === $error_code ) {
			return new \WP_Error( $error_code, __( 'A different Blog is already using this domain.', 'multisite-blog-alias' ) );

		} else if ('delete' === $error_code ) {
			return new \WP_Error( $error_code, __( 'Deletion failed', 'multisite-blog-alias' ) );

		} else if ( 'invalid' === $error_code ) {
			return new \WP_Error( $error_code, __( 'Deletion failed', 'multisite-blog-alias' ) );//$this->get_error( 'add-empty-domain' );
		}

		return new \WP_Error( $error_code, __( 'Something went wrong...', 'multisite-blog-alias' ) );

	}

	/**
	 *	@param int $blog_id
	 *	@param string $domain_alias_input
	 *	@param boolean $suppress_hooks
	 *	@return array
	 */
	public function create_insert_data( $blog_id, $domain_alias_input, $suppress_hooks = false ) {

		if ( function_exists( 'idn_to_ascii' ) ) {
			$domain_alias = idn_to_ascii( $domain_alias_input, IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46 );
			$domain_alias_utf8 = sanitize_text_field( $domain_alias_input );
		} else {
			$domain_alias = $domain_alias_utf8 = sanitize_text_field( $domain_alias_input );
		}

		if ( empty( $domain_alias ) ) {
			return $this->get_error( 'add-empty-domain' );

		} else if ( ! get_site( $blog_id ) ) {
			return $this->get_error( 'add-blog-not-exists' );

		} else if ( false === $this->validate_domain_alias( 'domain_alias', $domain_alias ) ) {
			// check validity
			return $this->get_error( 'add-invalid-domain' );

		} else if ( $record = $this->fetch_one_by( 'domain_alias', $domain_alias ) ) {
			$error = $this->get_error( 'add-alias-exists' );
			$error->add_data( $record );
			return $error;

		} else if ( $other_blog_id = get_blog_id_from_url( $domain_alias ) && ( $other_blog_id != $blog_id ) ) {
			$error = $this->get_error( 'add-site-exists' );
			$error->add_data( (object) [ 'blog_id' => (int) $other_blog_id ] );
			return $error;
		}

		$data = [
			'created'           => date_format( date_create(), 'Y-m-d H:i:s' ),
			'site_id'           => get_current_site()->id,
			'blog_id'           => $blog_id,
			'domain_alias'      => $domain_alias,
			'domain_alias_utf8' => $domain_alias_utf8,
			'redirect'          => 1,
		];

		/**
		 *	Filter domain alias data before it is written into db
		 *
		 *	@param Array $data [
		 *		@type int    $site_id            current site id
		 *		@type int    $blog_id            current blog id
		 *		@type string $domain_alias       domain name
		 *		@type string $domain_alias_utf8  domain name UTF-8 represetation
		 *		@type bool   $redirect           NOT IN USE YET: Whether to redirect the domain
		 *	]
		 */
		 if ( ! $suppress_hooks ) {
			 $data = apply_filters( 'blog_alias_create_data', $data );
		 }
		 return $data;
	}

	/**
	 *	@param Array $data [
	 *		@type int    $site_id            current site id
	 *		@type int    $blog_id            current blog id
	 *		@type string $domain_alias       domain name
	 *		@type string $domain_alias_utf8  domain name UTF-8 represetation
	 *		@type bool   $redirect           NOT IN USE YET: Whether to redirect the domain
	 *	]
	 *	@param Boolean $suppress_hooks
	 *	@return Integer|WP_Error
	 */
	public function insert_blog_alias( $data, $suppress_hooks = false ) {

		$id = $this->insert( $data );

		if ( (int) $id <= 0 ) {

			return $this->get_error();

		}

		if ( ! $suppress_hooks ) {
			/**
			 *	Fired after a domain alias has been created
			 *	@param Integer $alias_id
			 *	@param Object $alias {
			 *		@type int    $site_id            current site id
			 *		@type int    $blog_id            current blog id
			 *		@type string $domain_alias       domain name
			 *		@type string $domain_alias_utf8  domain name UTF-8 represetation
			 *		@type bool   $redirect           NOT IN USE YET: Whether to redirect the domain
			 *	}
			 */
			do_action( 'blog_alias_created', $id, $this->fetch_one_by( 'id', $id ) );
		}
		return $id;

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

		$site_url = get_blog_option( $alias->blog_id, 'siteurl' );

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

			$response = wp_remote_head( $location, [
				'redirection'   => 0,
				'sslverify'     => false,
			] );
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

		return filter_var( strtolower( $alias ), FILTER_VALIDATE_DOMAIN, [ 'flags' => FILTER_FLAG_HOSTNAME ] );

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
			`created` datetime NOT NULL DEFAULT current_timestamp(),
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

		// Changing the default value is only working for literals
		// @see https://core.trac.wordpress.org/ticket/28591
		$created_row = $wpdb->get_row("DESCRIBE {$wpdb->alias_domains} `created`");
		if ( ! in_array( strtolower( $created_row->Default ), [ 'current_timestamp', 'current_timestamp()' ] ) ) {
			$sql = "ALTER TABLE {$wpdb->alias_domains} ALTER `created` SET DEFAULT current_timestamp()";
			$wpdb->query( $sql );
		}
	}
}
