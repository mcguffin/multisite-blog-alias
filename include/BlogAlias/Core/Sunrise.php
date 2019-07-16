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


class Sunrise extends PluginComponent {




	/**
	 *	@inheritdoc
	 */
	public function activate() {

		update_site_option( 'multisite_blog_alias_sunrise_active', true );

		if ( defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS || ! is_writable( WP_CONTENT_DIR . '/sunrise.php' )  ) {
			return $this->not_writable_error();
		}

		// update wp-config.php
		if ( ! defined( 'SUNRISE' ) ) {
			$wp_config = '';
			if ( file_exists( ABSPATH . 'wp-config.php') ) {

				/** The config file resides in ABSPATH */
				$wp_config = ABSPATH . 'wp-config.php';

			} elseif ( @file_exists( dirname( ABSPATH ) . '/wp-config.php' ) && ! @file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {

				/** The config file resides one level above ABSPATH but is not part of another installation */
				$wp_config = dirname( ABSPATH ) . '/wp-config.php';
			}
			if ( ! is_writable( $wp_config ) ) {
				return $this->not_writable_error();
			}
		}

		// write sunrise
		$sunrise_contents = self::reset_sunrise();
		$sunrise_contents .= self::generate_sunrise_php();
		self::write_sunrise( $sunrise_contents );
	}

	public function __get( $what ) {
		switch( $what ) {
			case 'location':
				return WP_CONTENT_DIR . '/sunrise.php';
			case 'code':
				return self::generate_sunrise_php();
		}
	}
	/**
	 *
	 */
	private function not_writable_error() {
		$core = Plugin::instance();
		$slug = $core->get_slug();
		if ( defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS && ! has_action( "activated_{$slug}" ) ) {
			add_action( "activated_plugin", array( $this, 'show_instructions' ), 10, 2 );
		}
	}

	/**
	 *	@action activated_{$slug}
	 */
	public function show_instructions( $plugin, $network_wide ) {
		$core = Plugin::instance();
		if ( $core->get_wp_plugin() === $plugin ) {

			$url = network_admin_url('admin.php');
			$action = 'multisite-blog-alias-instructions';
			wp_safe_redirect( add_query_arg( 'action', $action, network_admin_url('admin.php') ));
			exit();

		}
	}


	/**
	 *	@param string $wp_config Path to wp-config
	 */
	private function write_wp_config( $wp_config ) {
		$wp_config_contents = file_get_contents( $wp_config );
		$code = '/* Added by Multisite Blog Alias Plugin */' . "\n";
		$code .= 'define( \'SUNRISE\', true );'."\n\n";
		$wp_config_contents = substr( $wp_config_contents, 5);
		$wp_config_contents = '<?php' . "\n" . $code . $wp_config_contents;
		file_put_contents( $wp_config, $wp_config_contents );
	}


	/**
	 *	@inheritdoc
	 */
	public function upgrade( $new_version, $old_version ) {
		if ( version_compare( '0.3.0', $old_version, '>' ) ) {
			// code has changed in 0.3.0 (more generic)
			$sunrise_contents = self::reset_sunrise('blog_alias');
			$sunrise_contents .= self::generate_sunrise_php();
			self::write_sunrise( $sunrise_contents );
		}
	}

	/**
	 *	@inheritdoc
	 */
	public function deactivate() {
		if ( file_exists( self::instance()->$location ) ) {
			// $sunrise_contents = file_get_contents( WP_CONTENT_DIR . '/sunrise.php' );
			// $sunrise_contents = preg_replace('@//BEGIN:blog_alias(.*)END:blog_alias//@imsU', '', $sunrise_contents );
			self::write_sunrise( self::reset_sunrise() );
		}
		update_site_option( 'multisite_blog_alias_sunrise_active', false );
	}

	/**
	 *	@inheritdoc
	 */
	public static function uninstall() {
		if ( file_exists( self::instance()->$location ) ) {
			// $sunrise_contents = file_get_contents( WP_CONTENT_DIR . '/sunrise.php' );
			// $sunrise_contents = preg_replace('@//BEGIN:blog_alias(.*)END:blog_alias//@imsU', '', $sunrise_contents );
			self::write_sunrise( self::reset_sunrise() );
		}
		delete_site_option( 'multisite_blog_alias_sunrise_active' );
	}

	/**
	 *	Write sunrise contents
	 *	@param string $sunrise_contents Must be valid PHP
	 */
	private static function write_sunrise( $sunrise_contents ) {
		file_put_contents( self::instnace()->$location, $sunrise_contents );
	}

	/**
	 *	Get sunrise content to write
	 *	@param string $slug Plugin slug
	 *	@return string
	 */
	private static function generate_sunrise_php( $slug = null ) {
		$core = Plugin::instance();
		if ( is_null( $slug ) ) {
			$slug = $core->get_slug();
		}
		$php = "//BEGIN:{$slug}\n";
		$php .= '$plugin_sunrise_file = WP_CONTENT_DIR . \'/plugins/'. dirname( $core->get_wp_plugin() ).'/sunrise.php\';' . "\n";
		$php .= 'if ( file_exists( $plugin_sunrise_file ) ) {'."\n";
 		$php .= "\t".'include_once $plugin_sunrise_file;' . "\n";
		$php .= '}' . "\n";
 		$php .= "//END:{$slug}//";
		return $php;
	}

	/**
	 *	Remove plugin part from sunrise.php
	 *	@param string $slug Plugin slug
	 *	@return string
	 */
	private static function reset_sunrise( $slug = null ) {
		$core = Plugin::instance();
		if ( is_null( $slug ) ) {
			$slug = $core->get_slug();
		}
		$sunrise = WP_CONTENT_DIR . '/sunrise.php';
		if ( file_exists( $sunrise ) ) {
			$sunrise_contents = file_get_contents( $sunrise );
			$sunrise_contents = preg_replace("@//BEGIN:{$slug}(.*)END:{$slug}//@imsU", '', $sunrise_contents );
		} else {
			$sunrise_contents = '<?php' . "\n\n";
			$sunrise_contents .=  "/* sunrise.php added by Plugin {$slug} */\n";
		}
		return $sunrise_contents;
	}
}
