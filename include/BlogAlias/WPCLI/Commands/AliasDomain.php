<?php
/**
 *	@package BlogAlias\WPCLI
 *	@version 1.0.0
 *	2018-09-22
 */

namespace BlogAlias\WPCLI\Commands;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use BlogAlias\Core;
use BlogAlias\Model;

class AliasDomain extends Core\Singleton {
	private $model;
	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		$this->model = Model\ModelAliasDomains::instance();
	}

	/**
	 * List.
	 *
	 * ## OPTIONS
	 *
	 * --blog_id=<blog_id>
	 * : The Blog ID
	 * ---
     * default: 0
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp alias-domains list --blog_id=123
	 *
	 *	@alias comment-check
	 */
	public function list( $args, $kwargs ) {

		extract( $kwargs );
		if ( $blog_id ){
			$aliases = $this->model->fetch_by( 'blog_id', $blog_id );
		} else {
			$aliases = $this->model->fetch_all();
		}
		$total = count($aliases);
		if ( $total ) {
			$header = array(
				'ID',
				'created',
				'site_id',
				'blog_id',
				'domain_alias',
				'redirect',
			);
			\WP_CLI::line( implode( "\t", $header ) );
			foreach ( $aliases as $alias ) {
				$line = get_object_vars($alias);
				\WP_CLI::line( implode( "\t", $line ) );
			}
		}

		\WP_CLI::success( sprintf( __( "%d Aliases total", 'wpms-blog-alias-cli' ), $total ) );
	}

	/**
	 * Add a Domain alias.
	 * You must either specify a blog ID or a blog Domain
	 *
	 * ## OPTIONS
	 *
	 * --blog_id=<blog_id>
	 * : The Blog ID
	 * ---
     * default: 0
	 * ---
	 *
	 * --blog_domain=<blog_id>
	 * : The Blog Domain
	 * ---
     * default: ''
	 * ---
	 *
	 * --domain_alias=<domain_name>
	 * : Alias Domain to add
	 *
	 * --redirect=<int>
	 * : Whether to redirect or not. Default yes
	 * ---
     * default: 1
     * options:
     *   - 0
     *   - 1
     * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp alias-domains list --blog_id=123 --domain_alias=quux.foobar.tld
	 *
	 *	@alias comment-check
	 */
	public function add( $args, $kwargs ) {

		extract( $kwargs );

		if ( empty($blog_domain) && ! $blog_id ) {
			\WP_CLI::error( __( 'Must specify either blog_id or blog_domain', 'wpms-blog-alias-cli' ) );
		}
		// url exists as blog
		if ( ! $blog_id && ! empty( $blog_domain ) && ! ( $blog_id = get_blog_id_from_url( $blog_domain ) ) ) {
			\WP_CLI::error( sprintf(__( 'Blog domain %s does not exist', 'wpms-blog-alias-cli' ), $blog_domain ) );
		}
		if ( ! $blog_id ) {
			\WP_CLI::error( sprintf(__( 'Blog ID %d does not exist', 'wpms-blog-alias-cli' ), $blog_id ) );
		}

		// invalid hostname
		if ( false === $this->model->validate( 'domain_alias', $domain_alias ) ) {
			\WP_CLI::error( __( 'Invalid domain_alias', 'wpms-blog-alias-cli' ) );
		}

		// url exists as blog
		if ( $other_blog_id = get_blog_id_from_url( $domain_alias ) ) {
			\WP_CLI::error( sprintf(__( 'Domain %s exists for blog %d', 'wpms-blog-alias-cli' ), $domain_alias, $other_blog_id ) );
		}

		// alias exists
		if ( $record = $this->model->fetch_one_by('domain_alias', $domain_alias ) ) {
			\WP_CLI::error( sprintf(__( 'Domain Alias %s exists for blog %d', 'wpms-blog-alias-cli' ), $domain_alias, $record->blog_id ) );
		}

		$data = array(
			'site_id'		=> get_current_site()->id,
			'blog_id'		=> $blog_id,
			'domain_alias'	=> $domain_alias,
			'redirect'		=> intval( $redirect ),
		);

		$id = $this->model->insert( $data );

		if ( $id !== false ) {
			\WP_CLI::success( sprintf( __( "Alias created with ID %d", 'wpms-blog-alias-cli' ), $this->model->insert_id ) );
		} else {
			\WP_CLI::error( sprintf( __( 'Error creating Domain Alias: %s', 'wpms-blog-alias-cli' ), $this->model->last_error ) );
		}
	}


	/**
	 * Add a Domain alias.
	 * You must either specify a blog ID or a blog Domain
	 *
	 * ## OPTIONS
	 *
	 * --id=<id>
	 * : The recored ID. See command list
	 * ---
     * default: 0
	 * ---
	 *
	 * --blog_id=<blog_id>
	 * : The Blog ID
	 * ---
     * default: 0
	 * ---
	 *
	 * --blog_domain=<blog_id>
	 * : The Blog Domain
	 * ---
     * default: ''
	 * ---
	 *
	 * --domain_alias=<domain_name>
	 * : Alias Domain to remove
	 * ---
     * default: ''
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp alias-domains list --blog_id=123 --domain_alias=quux.foobar.tld
	 *
	 *	@alias comment-check
	 */
	public function remove( $args, $kwargs ) {
		extract( $kwargs );

		if ( empty( $blog_domain ) && ! $blog_id && ! $id && empty( $domain_alias ) ) {
			\WP_CLI::error( __( 'Must specify either `id` or `blog_id` or `blog_domain` or `domain_alias` to remove-cli', 'wpms-blog-alias-cli' ) );
		}
		$where = array();

		if ( ! empty( $blog_domain ) ) {
			if ( ! $blog_id = get_blog_id_from_url( $blog_domain )) {
				\WP_CLI::error( sprintf(__( 'Blog domain %s does not exist', 'wpms-blog-alias-cli' ), $blog_domain ) );
			}
			$where['blog_id'] = $blog_id;
		} else if ( ! empty( $domain_alias ) ) {
			if ( ! $this->model->fetch_one_by( 'domain_alias', $domain_alias ) ) {
				\WP_CLI::error( sprintf(__( 'Domain Alias %s does not exist', 'wpms-blog-alias-cli' ), $domain_alias ) );
			}
			$where['domain_alias'] = $domain_alias;
		} else if ( $blog_id ) {
			$where['blog_id'] = $blog_id;
		} else if ( $id ) {
			if ( ! $this->model->fetch_one_by( 'id', $id ) ) {
				\WP_CLI::error( sprintf(__( 'Domain Alias with ID %d does not exist', 'wpms-blog-alias-cli' ), $id ) );
			}
			$where['id'] = $id;
		}

		$total = $this->model->delete($where);

		if ( $total !== false ) {
			\WP_CLI::success( sprintf( __( "%d Aliases deleted", 'wpms-blog-alias-cli' ), $total ) );
		} else {
			\WP_CLI::error( sprintf( __( 'Error deleting domain aliases: %s', 'wpms-blog-alias-cli' ), $this->model->last_error ) );
		}

	}

}
