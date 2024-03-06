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

	private $fields = [
		'ID',
		'created',
		'site_id',
		'blog_id',
		'domain_alias',
		'domain_alias_utf8',
		'redirect'
	];


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
	 * [--suppress_hooks]
	 * : Suppress hooks
	 * ---
	 * default: 0
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp alias-domains add --blog_id=123 --domain_alias=quux.foobar.tld
	 *
	 *	@alias create
	 */
	public function add( $args, $kwargs ) {

		$kwargs = wp_parse_args( $kwargs, [
			'compact'        => false,
			'suppress_hooks' => false,
		]);

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

		$data = $this->model->create_insert_data( $blog_id, $domain_alias_input, $suppress_hooks );

		if ( is_wp_error( $data ) ) {
			\WP_CLI::error( $data->get_error_message() );
			return;
		}

		$id = $this->model->insert_blog_alias( $data, $suppress_hooks );

		if ( is_wp_error( $id ) ) {
			\WP_CLI::error( $id->get_error_message() );
			return;
		}

		if ( $compact ) {
			\WP_CLI::line( $id );
		} else {
			/* Translators: Alias ID */
			\WP_CLI::success( sprintf( __( "Alias created with ID %d", 'multisite-blog-alias-cli' ), $id ) );
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
	 * --site_id=<site_id>
	 * : Site ID
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
	 * [--suppress_hooks]
	 * : Suppress hooks
	 * ---
	 * default: 0
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

		$kwargs = wp_parse_args( $kwargs, [
			'suppress_hooks' => false,
		] );

		extract( $kwargs );

		if ( empty( $blog_domain ) && ! $blog_id && ! $id && empty( $domain_alias ) ) {
			\WP_CLI::error( __( 'Must specify either `id` or `blog_id` or `blog_domain` or `domain_alias` to remove', 'multisite-blog-alias-cli' ) );
		}
		$where = [];

		$by = false;

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
			$by = 'domain_alias';
			$by_value = $domain_alias;
		} else if ( $blog_id ) {
			$by = 'blog_id';
			$by_value = $blog_id;

		} else if ( $site_id ) {
			$by = 'site_id';
			$by_value = $site_id;

		} else if ( $id ) {
			if ( ! $this->model->fetch_one_by( 'id', $id ) ) {
				/* Translators: Domain Alias ID */
				\WP_CLI::error( sprintf(__( 'Domain Alias with ID %d does not exist', 'multisite-blog-alias-cli' ), $id ) );
			}
			$by = 'ID';
			$by_value = $id;
		}
		if ( ! $suppress_hooks ) {
			if ( 'blog_id' === $by ) {
				// multiple domains
				$action_arg = $this->model->fetch_by( $by, $by_value );
				/** This action is documented in include/BlogAlias/Admin/NetworkAdmin.php */
				do_action( 'blog_alias_delete_multiple', $action_arg );
			} else {
				// single
				$action_arg = $this->model->fetch_one_by( $by, $by_value );
				/** This action is documented in include/BlogAlias/Admin/NetworkAdmin.php */
				do_action( 'blog_alias_delete', $action_arg );
			}
		}
		$deleted = $this->model->remove_blog_alias_by( $by, $by_value );

		if ( ! is_wp_error( $deleted ) ) {
			/* Translators: Number of deleted items */
			\WP_CLI::success( sprintf( __( "%d Aliases deleted", 'multisite-blog-alias-cli' ), $deleted ) );
		} else {
			/* Translators: Error message */
			\WP_CLI::error( $deleted->get_error_message() );
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
	 * : Alias Domain to test
	 * ---
     * default: ''
	 * ---
	 *
	 * [--verifyssl]
	 * : Check for valid SSL certificates
	 * ---
	 * default: 0
	 * ---
	 *
	 * [--compact]
	 * : Just print basic information
	 * ---
	 * default: 0
	 * ---
	 *
	 * [--quiet]
	 * : be quiet
	 * ---
	 * default: 0
	 * ---
	 *
	 * [--report]
	 * : Print redirect report
	 * ---
	 * default: 0
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

 		$kwargs = wp_parse_args( $kwargs, [
 			'compact'   => false,
			'report'    => false,
 		] );
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
				exit(1);
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
				exit(1);
			}
			$where['id'] = $id;
		}

		$result = $this->model->check_status( $record );

		if ( $report ) {
			$report_items = array_map( function( $item ) {
					return [
						'URL'      => $item->url,
						'Redirect' => (string) $item->redirect,
						'By'       => (string) $item->redirect_by,
						'SSL'      => $item->is_ssl && $item->ssl_status
							? 'ok'
							: (
								$item->is_ssl
									? 'error'
									: '-'
							),
						'Status'   => is_wp_error($item->error)
							? $item->error->get_error_code()
							: 'ok',
					];
				},
				$result->report
			);

			$formatter = new Formatter( $assoc_args, array_values( array_filter( ['URL', 'Redirect', 'By', 'SSL', 'Status' ] ) ) );
			$formatter->display_items( $report_items );
		}

		if ( true === $result->success ) {
			if ( $compact ) {
				\WP_CLI::line('ok');
			} else {
				\WP_CLI::success( __('OK', 'multisite-blog-alias-cli' ));
			}
		} else if ( ! $report ) {
			$errors = array_filter( $result->report, function( $item ) {
				return is_wp_error($item->error);
			} );
			$error_item = $result->report[ array_key_first( $errors ) ];
			if ( $compact ) {
				\WP_CLI::line($error_item->error->get_error_code());
			} else {
				\WP_CLI::error($error_item->error->get_error_message());
			}
			exit(1);
		}
	}
}
