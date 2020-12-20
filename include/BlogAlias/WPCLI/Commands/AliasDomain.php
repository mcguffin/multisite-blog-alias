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

use WP_CLI\Formatter;

class AliasDomain extends Core\Singleton {


	private $model;

	private $fields = array(
		'ID',
		'created',
		'site_id',
		'blog_id',
		'domain_alias',
		'domain_alias_utf8',
		'redirect'
	);


	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		$this->model = Model\AliasDomains::instance();
	}

	/**
	 * Print a List of domain aliases
	 *
	 * ## OPTIONS
	 *
	 * --blog_id=<blog_id>
	 * : The Blog ID
	 * ---
     * default: 0
	 * ---
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * --field=<field>
	 * : Prints the value of a single field
	 * ---
     * default: 0
	 * options:
	 *	 - 0
	 *   - ID
	 *   - created
	 *   - site_id
	 *   - blog_id
	 *   - domain_alias
	 *	 - domain_alias_utf8
	 *   - redirect
	 * ---
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
     * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     // List alias domains for blog-id 123
	 *     wp alias-domains list --blog_id=123
	 *
	 *     // output all aliases as minified json
	 *     wp alias-domains list --format=json --compact=2
	 *
	 *     // output csv including the header row but omitting other messages into file
	 *     wp alias-domains list --format=csv --compact > alias-list.csv
	 *
	 */
	public function get_list( $args, $kwargs ) {

		$kwargs = wp_parse_args( $kwargs, [
			'compact' => false,
		]);

		extract( $kwargs );

		$compact = intval( $compact );

		if ( $field && ! in_array( $field, $this->fields ) ) {
			/* translators: %s invalid field name */
			\WP_CLI::error( sprintf( __( 'Field %s does not exist.', 'multisite-blog-alias-cli' ), $field ) );
		}
		if ( $blog_id ){
			$aliases = $this->model->fetch_by( 'blog_id', $blog_id );
		} else {
			$aliases = $this->model->fetch_all();
		}

		$formatter = new Formatter( $kwargs, $this->fields );
		$formatter->display_items( $aliases );

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
	 * [--compact[=<compact>]]
	 * : Just print the ID
	 * ---
	 * default: 1
	 * options:
	 *   - 0
	 *   - 1
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp alias-domains add --blog_id=123 --domain_alias=quux.foobar.tld
	 *
	 *	@alias create
	 */
	public function add( $args, $kwargs ) {

		$kwargs = wp_parse_args($kwargs,array(
			'compact' => false,
		));

		extract( $kwargs );

		if ( empty($blog_domain) && ! $blog_id ) {
			\WP_CLI::error( __( 'Must specify either blog_id or blog_domain', 'multisite-blog-alias-cli' ) );
		}
		// url exists as blog
		if ( ! $blog_id && ! empty( $blog_domain ) && ! ( $blog_id = get_blog_id_from_url( $blog_domain ) ) ) {
			/* Translators: Blog Domain */
			\WP_CLI::error( sprintf(__( 'Blog domain %s does not exist', 'multisite-blog-alias-cli' ), $blog_domain ) );
		}
		if ( ! $blog_id ) {
			/* Translators: Blog ID */
			\WP_CLI::error( sprintf(__( 'Blog ID %d does not exist', 'multisite-blog-alias-cli' ), $blog_id ) );
		}
		$blog_id = intval( $blog_id );

		$domain_alias_input = $domain_alias;

		if ( function_exists( 'idn_to_ascii' ) ) {
			$domain_alias = idn_to_ascii( $domain_alias_input, IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46 );
			$domain_alias_utf8 = sanitize_text_field( $domain_alias_input );
		} else {
			$domain_alias = $domain_alias_utf8 = sanitize_text_field( $domain_alias_input );
		}


		// invalid hostname
		if ( false === $this->model->validate( 'domain_alias', $domain_alias ) ) {
			\WP_CLI::error( __( 'Invalid domain_alias', 'multisite-blog-alias-cli' ) );
		}

		// url exists as blog
		if ( $other_blog_id = get_blog_id_from_url( $domain_alias ) ) {
			/* Translators: AliasDomain, Blog ID */
			$msg = sprintf(__( 'Domain %1$s exists for blog %2$d', 'multisite-blog-alias-cli' ), $domain_alias, $other_blog_id );
			if ( $other_blog_id !== $blog_id ) {
				\WP_CLI::error( $msg );
			} else {
				\WP_CLI::warning( $msg );
			}
		}

		// alias exists
		if ( $record = $this->model->fetch_one_by('domain_alias', $domain_alias ) ) {
			/* Translators: AliasDomain, Blog ID */
			\WP_CLI::error( sprintf(__( 'Domain Alias %1$s exists for blog %2$d', 'multisite-blog-alias-cli' ), $domain_alias, $record->blog_id ) );
		}


		$data = array(
			'created'			=> strftime('%Y-%m-%d %H:%M:%S'),
			'site_id'			=> get_current_site()->id,
			'blog_id'			=> $blog_id,
			'domain_alias'		=> $domain_alias,
			'domain_alias_utf8'	=> $domain_alias_utf8,
			'redirect'			=> intval( $redirect ),
		);

		$id = $this->model->insert( $data );

		if ( $id !== false ) {
			if ( $compact ) {
				\WP_CLI::line( $this->model->insert_id );
			} else {
				/* Translators: Alias ID */
				\WP_CLI::success( sprintf( __( "Alias created with ID %d", 'multisite-blog-alias-cli' ), $this->model->insert_id ) );
			}
		} else {
			/* Translators: Error message */
			\WP_CLI::error( sprintf( __( 'Error creating Domain Alias: %s', 'multisite-blog-alias-cli' ), $this->model->last_error ) );
		}
	}


	/**
	 * Remove a Domain alias.
	 * You must either specify an ID, a blog ID, a blog Domain or an alias domain
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
	 *     // remove all aliases for blog 123
	 *     wp alias-domains remove --domain=sub.domain.tld
	 *
	 *     // remove all aliases for blog 123
	 *     wp alias-domains remove --blog_id=123
	 *
	 *	@alias delete
	 */
	public function remove( $args, $kwargs ) {
		extract( $kwargs );

		if ( empty( $blog_domain ) && ! $blog_id && ! $id && empty( $domain_alias ) ) {
			\WP_CLI::error( __( 'Must specify either `id` or `blog_id` or `blog_domain` or `domain_alias` to remove', 'multisite-blog-alias-cli' ) );
		}
		$where = array();

		if ( ! empty( $blog_domain ) ) {
			if ( ! $blog_id = get_blog_id_from_url( $blog_domain )) {
				/* Translators: Blog Domain */
				\WP_CLI::error( sprintf(__( 'Blog domain %s does not exist', 'multisite-blog-alias-cli' ), $blog_domain ) );
			}
			$where['blog_id'] = $blog_id;
		} else if ( ! empty( $domain_alias ) ) {
			if ( ! $this->model->fetch_one_by( 'domain_alias', $domain_alias ) ) {
				/* Translators: Alias Domain */
				\WP_CLI::error( sprintf(__( 'Domain Alias %s does not exist', 'multisite-blog-alias-cli' ), $domain_alias ) );
			}
			$where['domain_alias'] = $domain_alias;
		} else if ( $blog_id ) {
			$where['blog_id'] = $blog_id;
		} else if ( $id ) {
			if ( ! $this->model->fetch_one_by( 'id', $id ) ) {
				/* Translators: Domain Alias ID */
				\WP_CLI::error( sprintf(__( 'Domain Alias with ID %d does not exist', 'multisite-blog-alias-cli' ), $id ) );
			}
			$where['id'] = $id;
		}

		$total = $this->model->delete($where);

		if ( $total !== false ) {
			/* Translators: Number of deleted items */
			\WP_CLI::success( sprintf( __( "%d Aliases deleted", 'multisite-blog-alias-cli' ), $total ) );
		} else {
			/* Translators: Error message */
			\WP_CLI::error( sprintf( __( 'Error deleting domain aliases: %s', 'multisite-blog-alias-cli' ), $this->model->last_error ) );
		}

	}


	/**
	 * Test a Domain alias.
	 * You must either specify an alias ID or an alias Domain
	 *
	 * ## OPTIONS
	 *
	 * --id=<id>
	 * : The recored ID. See command list
	 * ---
     * default: 0
	 * ---
	 *
	 * --domain_alias=<domain_name>
	 * : Alias Domain to remove
	 * ---
     * default: ''
	 * ---
	 *
	 * [--compact[=<compact>]]
	 * : Just print the ID
	 * ---
	 * default: 0
	 * options:
	 *   - 0
	 *   - 1
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp alias-domains test --domain_alias=sub.domain.tld
	 *
	 *     // test alias 123
	 *     wp alias-domains test --id=123
	 *
	 *     // test domain
	 *     wp alias-domains test --id=123
	 *
	 *	@alias comment-check
	 */
	 public function test( $args, $kwargs ) {

 		$kwargs = wp_parse_args($kwargs,array(
 			'compact' => false,
 		));
		extract( $kwargs );
		if ( ! $id && empty( $domain_alias ) ) {
			\WP_CLI::error( __( 'Must specify either `id` or `domain_alias` to test', 'multisite-blog-alias-cli' ) );
		}

		if ( ! empty( $domain_alias ) ) {
			if ( ! $record = $this->model->fetch_one_by( 'domain_alias', $domain_alias ) ) {
				/* Translators: Alias Domain */
				if ( $compact ) {
					\WP_CLI::line('not-an-alias');
					return;
				} else {
					\WP_CLI::error( sprintf(__( 'Domain Alias %s does not exist', 'multisite-blog-alias-cli' ), $domain_alias ) );
				}
			}
		} else if ( $id ) {
			if ( ! $record = $this->model->fetch_one_by( 'id', $id ) ) {
				/* Translators: Domain Alias ID */
				if ( $compact ) {
					\WP_CLI::line('not-an-alias');
					return;
				} else {
					\WP_CLI::error( sprintf(__( 'Domain Alias with ID %d does not exist', 'multisite-blog-alias-cli' ), $id ) );
				}
			}
			$where['id'] = $id;
		}

		$result = $this->model->check_status( $record );

		if ( true === $result ) {
			if ( $compact ) {
				\WP_CLI::line('ok');
			} else {
				\WP_CLI::success( __('OK', 'multisite-blog-alias-cli' ));
			}
		} else if ( is_wp_error( $result ) ) {
			if ( $compact ) {
				\WP_CLI::line($result->get_error_code());
			} else {
				\WP_CLI::error($result->get_error_message());
			}
		}
	}
}
