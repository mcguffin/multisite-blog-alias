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


class ModelRedirects extends Model {

	protected $fields = array(
		'ID'	=> '%d',
//		'title'	=> '%s',
//		...
	);


	/**
	 *	@inheritdoc
	 */
	protected $_table = 'redirects';

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

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$sql = "CREATE TABLE $wpdb->redirects (
			`ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			PRIMARY KEY (`id`)
		) $charset_collate;";

		// updates DB
		dbDelta( $sql );
	}
}
