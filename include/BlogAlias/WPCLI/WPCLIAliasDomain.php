<?php
/**
 *	@package BlogAlias\WPCLI
 *	@version 1.0.0
 *	2018-09-22
 */

namespace BlogAlias\WPCLI;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use BlogAlias\Core;

class WPCLIAliasDomain extends Core\Singleton {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		$command = Commands\AliasDomain::instance();
		\WP_CLI::add_command( 'alias-domains list', array( $command, 'get_list' ), array(
			'shortdesc'		=> 'List blog aliases',
			'is_deferred'	=> false,
		) );

		\WP_CLI::add_command( 'alias-domains add', array( $command, 'add' ), array(
			'shortdesc'		=> 'Add a blog alias',
			'is_deferred'	=> false,
		) );

		\WP_CLI::add_command( 'alias-domains remove', array( $command, 'remove' ), array(
			'shortdesc'		=> 'Remove a blog alias',
			'is_deferred'	=> false,
		) );


	}

}
