<?php
/**
 *  @package BlogAlias
 *  @version 1.0.0
 *  2018-09-22
 */

namespace BlogAlias;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'FU!' );
}


function __autoload( $class ) {

	if ( false === ( $pos = strpos( $class, '\\' ) ) ) {
		return;
	}

	$ds = DIRECTORY_SEPARATOR;
	$top = substr( $class, 0, $pos );

	if ( false === is_dir( __DIR__ . $ds . $top ) ) {
		// not our plugin.
		return;
	}

	$file = __DIR__ . $ds . str_replace( '\\', $ds, $class ) . '.php';

	if ( file_exists( $file ) ) {
		require_once $file;
	} else {
		throw new \Exception( sprintf( 'Class `%s` could not be loaded. File `%s` not found.', esc_html( $class ), esc_html( $file ) ) );
	}
}


spl_autoload_register( 'BlogAlias\__autoload' );
