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


use BlogAlias\Core;

abstract class Model extends Core\PluginComponent {

	/**
	 *	@var assoc
	 */
	protected $fields = array();

	/**
	 *	@var string table name for model
	 */
	protected $_table = null;

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		// setup wpdb
		global $wpdb;
		$wpdb->tables[] = $this->table;
		$wpdb->set_blog_id( get_current_blog_id() );

		parent::__construct();
	}

	/**
	 *	magic getter
	 */
	public function __get( $what ) {
		if ( $what === 'table' ) {
			return $this->_table;
		}
		global $wpdb;
		if ( isset( $wpdb->$what ) ) {
			return $wpdb->$what;
		}
	}

	/**
	 *	@inheritdoc
	 */
	public function deactivate() {
	}

	/**
	 *	@inheritdoc
	 */
	public static function uninstall() {
		// drop table
		global $wpdb;
		$tbl = $this->table;
		$wpdb->query("DROP TABLE {$wpdb->$tbl}");

	}

	/**
	 *	Fetch one result
	 *
	 *	@param	string 		$field
	 *	@param	string|int	$value
	 *	@return	null|object
	 */
	public function fetch_one_by( $field, $value ) {
		global $wpdb;
		$table = $wpdb->{$this->table};
		// check fields
		if ( $field == 'id' ) {
			$field = 'ID';
		}
		if ( ! isset( $this->fields[$field] ) ) {
			return null;
		}
		$format = $this->fields[$field];

		foreach ( $wpdb->get_results( $wpdb->prepare("SELECT * FROM $table WHERE $field = $format LIMIT 1", $value ) ) as $result ) {
			return $result;
		};
		return null;
	}

	/**
	 *	Fetch results
	 *
	 *	@param	string 	$field
	 *	@param	mixed	$value
	 *	@return	null|array
	 */
	public function fetch_by( $field, $value ) {
		global $wpdb;
		$table = $wpdb->{$this->table};
		// check fields
		if ( $field == 'id' ) {
			$field = 'ID';
		}
		if ( ! isset( $this->fields[$field] ) ) {
			return null;
		}

		$format = $this->fields[$field];
		return $wpdb->get_results( $wpdb->prepare("SELECT * FROM $table WHERE $field = $format", $value ) );
	}

	/**
	 *	Fetch all records
	 *
	 *	@return	array
	 */
	public function fetch_all() {
		global $wpdb;
		$table = $wpdb->{$this->table};
		return $wpdb->get_results( "SELECT * FROM $table" );
	}


	/**
	 *	WPDB Wrapper
	 *
	 *	@param	array 		$data
	 *	@param	null|array	$format
	 *	@return	int|false
	 */
	public function insert( $data, $format = null ) {
		global $wpdb;

		$table = $this->table;
		$data = $this->sanitize_data( $data );
		if ( is_null( $format ) ) {
			$format = $this->get_format_for_data( $data );
		}

		return $wpdb->insert( $wpdb->$table, $data, $format );
	}

	/**
	 *	WPDB Wrapper
	 *
	 *	@param	array 		$data
	 *	@param	array 		$where
	 *	@param	null|array	$format
	 *	@param	null|array	$where_format
	 *	@return	int|false
	 */
	public function update( $data, $where, $format = null, $where_format = null ) {
		global $wpdb;
		$table = $this->table;
		$data = $this->sanitize_data( $data );
		if ( is_null( $format ) ) {
			$format = $this->get_format_for_data( $data );
		}
		if ( is_null( $where_format ) ) {
			$where_format = $this->get_format_for_data( $where );
		}
		return $wpdb->update( $wpdb->$table, $data, $where, $format, $where_format );
	}

	/**
	 *	WPDB Wrapper
	 *
	 *	@param	array 		$data
	 *	@param	null|array	$format
	 *	@return	int|false
	 */
	public function replace( $data, $format = null ) {
		global $wpdb;
		$table = $this->table;
		$data = $this->sanitize_data( $data );
		if ( is_null( $format ) ) {
			$format = $this->get_format_for_data( $data );
		}
		return $wpdb->replace( $wpdb->$table, $data, $format );
	}

	/**
	 *	WPDB Wrapper
	 *
	 *	@param	array 		$where
	 *	@param	null|array	$where_format
	 *	@return	int|false
	 */
	public function delete( $where, $where_format = null ) {
		global $wpdb;
		$table = $this->table;
		$where = $this->sanitize_data( $where );
		if ( is_null( $where_format ) ) {
			$where_format = $this->get_format_for_data( $where );
		}
		return $wpdb->delete( $wpdb->$table, $where, $where_format );
	}

	/**
	 *
	 */
	private function sanitize_data( $data ) {
		$sane = array();
		foreach ( $data as $key => $value ) {
			if ( $key === 'id' ) {
				$key = 'ID';
			}
			$sane[$key] = $value;
		}
		return $sane;
	}
	/**
	 *
	 */
	private function get_format_for_data( $data ) {
		$format = array();
		foreach ( $data as $key => $val ) {
			if ( $key === 'id' ) {
				$key = 'ID';
			}
			$format[$key] = $this->fields[ $key ];
		}
		return $format;
	}

}
