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

abstract class Model extends Core\Singleton implements Core\ComponentInterface {

	/* @var string mysql date format */
	const MYSQL_DATE_FORMAT = 'Y-m-d H:i:s';

	/**
	 *	@var assoc column => format
	 */
	protected $columns = [];

	/**
	 *	@var array Column names
	 */
	protected $identifier_columns = [];

	/**
	 *	@var string table name for model
	 */
	protected $_table = null;

	/**
	 *	@var bool is global table
	 */
	protected $_global_table = false;

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		// setup wpdb
		global $wpdb;
		if ( $this->_global_table ) {
			$wpdb->global_tables[] = $this->table;
			$wpdb->set_prefix( $wpdb->base_prefix );
		} else {
			$wpdb->tables[] = $this->table;
			$wpdb->set_blog_id( get_current_blog_id() );
		}

		foreach ( $this->columns as $column => $format ) {
			if ( 'ID' === $column ) {

				add_filter( "sanitize_{$this->_table}/{$column}", 'absint' );
				add_filter( "validate_{$this->_table}/{$column}", 'is_int' );

			} else if ( in_array( $column, [ 'create', 'updated' ] ) ) {

				add_filter( "sanitize_{$this->_table}/{$column}", [ $this, 'sanitize_datetime' ] );

			} else if ( '%d' === $format ) {

				add_filter( "sanitize_{$this->_table}/{$column}", 'intval' );
				add_filter( "validate_{$this->_table}/{$column}", 'is_int' );

			} else if ( '%f' === $format ) {

				add_filter( "sanitize_{$this->_table}/{$column}", 'floatval' );
				add_filter( "validate_{$this->_table}/{$column}", 'is_float' );
			}
		}

		parent::__construct();
	}

	/**
	 *	magic getter
	 */
	public function __get( $what ) {
		if ( $what === 'table' ) {
			return $this->_table;
		}
		if ( $what === 'global' ) {
			return $this->_global_table;
		}
		global $wpdb;
		if ( isset( $wpdb->$what ) ) {
			return $wpdb->$what;
		}
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
		return [
			'success'	=> true,
			'message'	=> '',
		];
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
		$cls = get_called_class();
		$cls::instance()->drop_db();
	}


	/**
	 *	Whether a column exists
	 *
	 *	@param string $column
	 *	@return boolean
	 */
	public function has_column( $column ) {
		return isset( $this->columns[$column] );
	}

	/**
	 *	Get columns
	 *
	 *	@return array [
	 *		'column_name'	=> 'format',
	 *		...
	 *	]
	 */
	public function get_columns( ) {
		return $this->columns;
	}


	/**
	 *	Get identifier columns
	 *
	 *	@return array [
	 *		'column_name'	=> 'format',
	 *		...
	 *	]
	 */
	public function get_id_columns( ) {
		$ret = [];
		return array_filter( $this->columns, [ $this, 'filter_identifier_columns' ], ARRAY_FILTER_USE_KEY );
	}

	/**
	 *	array_filter callback
	 *
	 *	@param string $column
	 *	@return boolean
	 */
	private function filter_identifier_columns( $column ) {
		return in_array( $column, $this->identifier_columns );
	}

	/**
	 *	Fetch one result
	 *
	 *	@param	string 		$column
	 *	@param	string|int	$value
	 *	@return	null|object
	 */
	public function fetch_one_by( $column, $value ) {
		global $wpdb;
		$table = $wpdb->{$this->table};
		// check fields
		if ( $column == 'id' ) {
			$column = 'ID';
		}
		if ( ! $this->has_column( $column ) ) {
			return null;
		}
		$format = $this->columns[$column];

		// PHPCS: $column is checked by $this->has_column(), $format is taken directly from table definition
		foreach ( $wpdb->get_results( $wpdb->prepare("SELECT * FROM $table WHERE $column = $format LIMIT 1", $value ) ) as $result ) { // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return $result;
		};
		return null;
	}

	/**
	 *	Fetch results
	 *
	 *	@param	string 	$column
	 *	@param	mixed	$value
	 *	@return	null|array
	 */
	public function fetch_by( $column, $value ) {
		global $wpdb;
		$table = $wpdb->{$this->table};
		// check fields
		if ( $column == 'id' ) {
			$column = 'ID';
		}
		if ( ! isset( $this->columns[$column] ) ) {
			return null;
		}

		$format = $this->columns[$column];
		// PHPCS: $column is checked by $this->has_column(), $format is taken directly from table definition
		return $wpdb->get_results( $wpdb->prepare("SELECT * FROM $table WHERE $column = $format", $value ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 *	Fetch all records
	 *
	 *	@return	array
	 */
	public function fetch_all() {
		global $wpdb;
		$table = $wpdb->{$this->table};
		return $wpdb->get_results( "SELECT * FROM $table" );  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}


	/**
	 *	WPDB Wrapper
	 *
	 *	@param	array 		$data
	 *	@param	null|array	$format
	 *	@return	int|false	Last insert ID
	 */
	public function insert( $data, $format = null ) {
		global $wpdb;

		$table = $this->table;
		$data = $this->sanitize_data( $data );
		if ( is_null( $format ) ) {
			$format = $this->get_format_for_data( $data );
		}

		$wpdb->insert( $wpdb->$table, $data, $format );
		return $wpdb->insert_id;
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
	 *	sanitize values
	 *
	 *	@return assoc
	 */
	private function sanitize_data( $data ) {
		$sane = [];
		if ( ! is_array( $data ) ) {
			if ( is_object( $data ) ) {
				$data = get_object_vars( $data );
			} else {
				// throw!
			}
		}
		foreach ( $data as $key => $value ) {
			if ( $key === 'id' ) {
				$key = 'ID';
			}
			if ( false !== $this->sanitize( $key, $value ) ) {
				$sane[$key] = $value;
			}
		}
		return $sane;
	}

	/**
	 *	@return assoc
	 */
	private function get_format_for_data( $data ) {
		$format = [];
		foreach ( $data as $key => $val ) {
			if ( $key === 'id' ) {
				$key = 'ID';
			}
			$format[$key] = $this->columns[ $key ];
		}
		return $format;
	}

	/**
	 *	sanitize value
	 *	@param string|array $column Data array or column name
	 *	@param mixed $value
	 *	@return mixed
	 */
	public function sanitize( $column, $value = null ) {

		if ( is_array( $column ) ) {

			array_walk( $column, [ $this, 'sanitize_cb' ] );

			return array_filter( $column, function( $val ) {
				return $val !== false;
			} );
		}

		$this->sanitize_cb( $value, $column );

		return $value;
	}

	/**
	 *	Sanitize a single value
	 *	@param mixed &$value Value passed by reference
	 *	@param string $key
	 */
	private function sanitize_cb( &$value, $key ) {

		/**
		 *	Sanitize value before it is written to db.
		 *	The dynamic parts refer to the unprefixed table name ($table) and the columns name ($key)
		 *
		 *	@param mixed $value Value to sanitize
		 *	@return mixed sanitized $value
		 */
		$value = apply_filters( "sanitize_{$this->_table}/{$key}", $value );

	}

	/**
	 *	Validate value
	 *
	 *	@param string|array $column Data array or column name
	 *	@param mixed $value
	 *	@return mixed true if value is valid
	 */
	public function validate( $column, $value = null ) {

		if ( is_array( $column ) ) {

			array_walk( $column, [ $this, 'sanitize_cb' ] );

			return array_map( $column, function( $val ) {
				return $val !== false;
			} );
		}

		$this->validate_cb( $value, $column );

		return false !== $value;

	}

	/**
	 *	Sanitize a single value
	 *	@param mixed &$value Value passed by reference
	 *	@param string $key
	 */
	private function validate_cb( &$value, $key ) {

		/**
		 *	Validate value before it is written to db.
		 *	The dynamic parts refer to the unprefixed table name ($table) and the columns name ($key)
		 *
		 *	@param mixed $value Value to validate
		 *	@return boolean
		 */
		$value = apply_filters( "validate_{$this->_table}/{$key}", true, $value );

	}

	/**
	 *	@param \DateTime|string $value Datetime expected to be
	 *	@return string Datetime in `Y-m-d H:i:s` format
	 */
	public function sanitize_datetime( $value ) {

		// try to create DateTime
		if ( ! ( $value instanceof \DateTime ) ) {
			$value = date_create_from_format( Model::MYSQL_DATE_FORMAT, $value );
		}

		if ( false === $value ) {
			return false;
		}

		return $value->format( Model::MYSQL_DATE_FORMAT );

	}

	/**
	 *	Drop table
	 */
	public function drop_db() {
		// drop table
		global $wpdb;
		$tbl = $this->table;
		$wpdb->query("DROP TABLE {$wpdb->$tbl}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 *	Create or Update Database Table
	 */
	abstract protected function update_db();


}
