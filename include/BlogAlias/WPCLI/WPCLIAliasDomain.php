<?php
/**
 *  @package BlogAlias\WPCLI
 *  @version 1.0.0
 *  2018-09-22
 */

namespace BlogAlias\WPCLI;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'FU!' );
}

use BlogAlias\Core;

class WPCLIAliasDomain extends Core\Singleton {

	/**
	 *  @inheritdoc
	 */
	protected function __construct() {
		$command = Commands\AliasDomain::instance();
		\WP_CLI::add_command( 'alias-domains list', [ $command, 'get_list' ], [
			'shortdesc'     => 'List blog aliases',
			'is_deferred'   => false,
		] );

		\WP_CLI::add_command( 'alias-domains add', [ $command, 'add' ], [
			'shortdesc'     => 'Add a blog alias',
			'is_deferred'   => false,
		] );

		\WP_CLI::add_command( 'alias-domains remove', [ $command, 'remove' ], [
			'shortdesc'     => 'Remove a blog alias',
			'is_deferred'   => false,
		] );

		\WP_CLI::add_command( 'alias-domains test', [ $command, 'test' ], [
			'shortdesc'     => 'Test blog alias',
			'is_deferred'   => false,
		] );

	}

}
