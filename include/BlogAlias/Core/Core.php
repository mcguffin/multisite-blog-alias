<?php
/**
 *	@package BlogAlias\Core
 *	@version 1.0.0
 *	2018-09-22
 */

namespace BlogAlias\Core;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


class Core extends Plugin {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		add_action( 'ms_site_not_found', array($this,'site_not_found'), 10, 3 );

		$args = func_get_args();
		parent::__construct( ...$args );
	}



	/**
	 *	Load frontend styles and scripts
	 *
	 *	@action ms_site_not_found
	 */
	public function site_not_found( $current_site, $domain, $path ) {
		var_dump($current_site,$domain,$path);exit();
	}



	/**
	 *	Get asset url for this plugin
	 *
	 *	@param	string	$asset	URL part relative to plugin class
	 *	@return string URL
	 */
	public function get_asset_url( $asset ) {
		return plugins_url( $asset, $this->get_plugin_file() );
	}



}
