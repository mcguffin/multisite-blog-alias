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

		add_filter("validate_{$this->_table}/domain_alias", array( $this, 'validate_domain_alias') );

	}
	/**
	 *	validate callback for domain alias
	 *	@param bool $valid Whether the vaue is valid
	 *	@param string $alias Domain alias (valid hostname)
	 *	@return bool|string false if invalid
	 */
	public function validate_domain_alias( $alias ) {

		return filter_var( strtolower( trim( $alias ) ), FILTER_VALIDATE_DOMAIN );

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
			PRIMARY KEY (`id`),
			UNIQUE KEY `domain_alias` (`domain_alias`)
		) $charset_collate;";

		// updates DB
		dbDelta( $sql );
	}
}
