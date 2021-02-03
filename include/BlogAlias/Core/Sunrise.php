<?php
/**
 *  @package BlogAlias\Core
 *  @version 1.0.0
 *  2018-09-22
 */

namespace BlogAlias\Core;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'FU!' );
}


class Sunrise extends PluginComponent {

	/**
	 *  @inheritdoc
	 */
	public function activate() {

		update_site_option( 'multisite_blog_alias_sunrise_active', true );

		if ( ! is_writable( WP_CONTENT_DIR . '/sunrise.php' ) ) {
			return $this->not_writable_error();
		}

		// update wp-config.php
		if ( ! defined( 'SUNRISE' ) ) {
			$wp_config = '';
			if ( file_exists( ABSPATH . 'wp-config.php' ) ) {

				/** The config file resides in ABSPATH */
				$wp_config = ABSPATH . 'wp-config.php';

			} elseif ( @file_exists( dirname( ABSPATH ) . '/wp-config.php' ) && ! @file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {

				/** The config file resides one level above ABSPATH but is not part of another installation */
				$wp_config = dirname( ABSPATH ) . '/wp-config.php';
			}
			if ( ! $this->write_wp_config( $wp_config ) ) {
				$this->not_writable_error();
			}
		}

		// write sunrise
		$sunrise_contents = self::reset_sunrise();
		$sunrise_contents .= self::generate_sunrise_php();
		if ( ! self::write_sunrise( $sunrise_contents ) ) {
			$this->not_writable_error();
		}
	}

	/**
	 * Magic getter
	 */
	public function __get( $what ) {
		switch ( $what ) {
			case 'location':
				return WP_CONTENT_DIR . '/sunrise.php';
			case 'code':
				return self::generate_sunrise_php();
		}
	}

	/**
	 *	Add redirect to instructions page
	 */
	private function not_writable_error() {
		$core = Core::instance();
		$slug = $core->get_slug();
		if ( ! defined('WP_CLI') && ! has_action( "activated_plugin", array( $this, 'show_instructions' ), 20 ) ) {
			add_action( "activated_plugin", array( $this, 'show_instructions' ), 20, 2 );
		}
	}

	/**
	 *  @action activated_{$slug}
	 */
	public function show_instructions( $plugin, $network_wide ) {
		$core = Core::instance();
		if ( $core->get_wp_plugin() === $plugin ) {

			$url = network_admin_url( 'admin.php' );
			$action = 'multisite-blog-alias-instructions';
			wp_safe_redirect( add_query_arg( 'action', $action, network_admin_url( 'admin.php' ) ) );
			exit();

		}
	}


	/**
	 *  @param string $wp_config Path to wp-config
	 */
	private function write_wp_config( $wp_config ) {
		global $wp_filesystem;

		if ( ! WP_Filesystem() ) {
			return false;
		}

		if ( $wp_filesystem->is_writable( $wp_config ) ) {
			$wp_config_contents = $wp_filesystem->get_contents( $wp_config );
			$code = '/* Added by Multisite Blog Alias Plugin */' . "\n";
			$code .= 'define( \'SUNRISE\', true );' . "\n\n";
			$wp_config_contents = substr( $wp_config_contents, 5 );
			$wp_config_contents = '<?php' . "\n" . $code . $wp_config_contents;
			return $wp_filesystem->put_contents( $wp_config, $wp_config_contents );
		}
		return false;
	}


	/**
	 *  @inheritdoc
	 */
	public function upgrade( $new_version, $old_version ) {
		$result = [
			'success'	=> true,
			'message'	=> '',
		];
		if ( version_compare( '0.3.0', $old_version, '>' ) ) {
			// code has changed in 0.3.0 (more generic)
			$sunrise_contents = self::reset_sunrise( 'blog_alias' );
			$sunrise_contents .= self::generate_sunrise_php();
			if ( ! self::write_sunrise( $sunrise_contents ) ) {
				$result = [
					'success' 	=> false,
					'message'	=> __('Error writing sunrise.php','multisite-blog-alias' ),
				];
			}
		}
		return $result;
	}

	/**
	 *  @inheritdoc
	 */
	public function deactivate() {
		if ( file_exists( self::instance()->location ) ) {
			// $sunrise_contents = file_get_contents( WP_CONTENT_DIR . '/sunrise.php' );
			// $sunrise_contents = preg_replace('@//BEGIN:blog_alias(.*)END:blog_alias//@imsU', '', $sunrise_contents );
			self::write_sunrise( self::reset_sunrise() );
		}
		update_site_option( 'multisite_blog_alias_sunrise_active', false );
	}

	/**
	 *  @inheritdoc
	 */
	public static function uninstall() {
		if ( file_exists( self::instance()->location ) ) {
			self::write_sunrise( self::reset_sunrise() );
		}
		delete_site_option( 'multisite_blog_alias_sunrise_active' );
	}

	/**
	 *  Write sunrise contents
	 *  static because called from uninstall hook
	 *
	 *  @param string $sunrise_contents Must be valid PHP
	 *	@return bool
	 */
	private static function write_sunrise( $sunrise_contents ) {
		global $wp_filesystem;

		if ( ! WP_Filesystem() ) {
			return false;
		}

		$file = self::instance()->location;

		if ( $wp_filesystem->is_writable( $file ) ) {
			return $wp_filesystem->put_contents( $file, $sunrise_contents );
		}
		return false;
	}

	/**
	 *  Get sunrise content to write
	 *  @param string $slug Plugin slug
	 *  @return string
	 */
	private static function generate_sunrise_php( $slug = null ) {
		$core = Core::instance();
		if ( is_null( $slug ) ) {
			$slug = $core->get_slug();
		}
		$php = "//BEGIN:{$slug}\n";
		$php .= '$plugin_sunrise_file = WP_CONTENT_DIR . \'/plugins/' . dirname( $core->get_wp_plugin() ) . '/sunrise.php\';' . "\n";
		$php .= 'if ( file_exists( $plugin_sunrise_file ) ) {' . "\n";
 		$php .= "\t" . 'include_once $plugin_sunrise_file;' . "\n";
		$php .= '}' . "\n";
 		$php .= "//END:{$slug}//";
		return $php;
	}

	/**
	 *  Remove plugin part from sunrise.php
	 *  @param string $slug Plugin slug
	 *  @return string
	 */
	private static function reset_sunrise( $slug = null ) {

		global $wp_filesystem;

		$core = Core::instance();

		if ( ! WP_Filesystem() ) {
			return false;
		}

		if ( is_null( $slug ) ) {
			$slug = $core->get_slug();
		}

		$sunrise = WP_CONTENT_DIR . '/sunrise.php';

		if ( $wp_filesystem->exists( $sunrise ) ) {
			$sunrise_contents = $wp_filesystem->get_contents( $sunrise );
			$sunrise_contents = preg_replace( "@//BEGIN:{$slug}(.*)END:{$slug}//@imsU", '', $sunrise_contents );
		} else {
			// return empty sunrise.php
			$sunrise_contents = '<?php' . "\n\n";
			$sunrise_contents .= "/* sunrise.php added by Plugin {$slug} */\n";
		}
		return $sunrise_contents;
	}
}
