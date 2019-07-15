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

		$core = Plugin::instance();
		$slug = $core->get_slug();

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
			if ( ! $wp_config ) {
				return; // don't activate!
			}
			$wp_config_contents = file_get_contents( $wp_config );
			$code = '/* Added by WP Blog Alias Plugin */' . "\n";
			$code .= 'define( \'SUNRISE\', true );'."\n\n";
			$wp_config_contents = substr( $wp_config_contents, 5);
			$wp_config_contents = '<?php' . "\n" . $code . $wp_config_contents;
			file_put_contents( $wp_config, $wp_config_contents );
		}

		// write sunrise
		$sunrise_contents = self::reset_sunrise();
		$sunrise_contents .= self::generate_sunrise_php();
		file_put_contents( WP_CONTENT_DIR . '/sunrise.php', $sunrise_contents );
	}


	/**
	 *	@inheritdoc
	 */
	public function upgrade( $new_version, $old_version ) {
		if ( version_compare( '0.3.0', $old_version, '>' ) ) {
			// code has changed in 0.3.0 (more generic)
			$sunrise_contents = self::reset_sunrise('blog_alias');
			$sunrise_contents .= self::generate_sunrise_php();
			file_put_contents( WP_CONTENT_DIR . '/sunrise.php', $sunrise_contents );
		}
	}

	/**
	 *	@inheritdoc
	 */
	public function deactivate() {
		if ( file_exists( WP_CONTENT_DIR . '/sunrise.php' ) ) {
			// $sunrise_contents = file_get_contents( WP_CONTENT_DIR . '/sunrise.php' );
			// $sunrise_contents = preg_replace('@//BEGIN:blog_alias(.*)END:blog_alias//@imsU', '', $sunrise_contents );
			file_put_contents( WP_CONTENT_DIR . '/sunrise.php', self::reset_sunrise() );
		}
	}

	/**
	 *	@inheritdoc
	 */
	public static function uninstall() {
		if ( file_exists( WP_CONTENT_DIR . '/sunrise.php' ) ) {
			// $sunrise_contents = file_get_contents( WP_CONTENT_DIR . '/sunrise.php' );
			// $sunrise_contents = preg_replace('@//BEGIN:blog_alias(.*)END:blog_alias//@imsU', '', $sunrise_contents );
			file_put_contents( WP_CONTENT_DIR . '/sunrise.php', self::reset_sunrise() );
		}
	}

	/**
	 *	Get sunrise content to write
	 */
	private static function generate_sunrise_php( $slug = null ) {
		$core = Plugin::instance();
		if ( is_null( $slug ) ) {
			$slug = $core->get_slug();
		}
		$php = "//BEGIN:{$slug}\n";
		$php .= '$plugin_sunrise_file = WP_CONTENT_DIR . \'/plugins/'. dirname( $core->get_wp_plugin() ).'/sunrise.php\';' . "\n";
		$php .= 'if ( file_exists( $plugin_sunrise_file ) ) {'."\n";
 		$php .= "\t".'require_once $plugin_sunrise_file;' . "\n";
		$php .= '}' . "\n";
 		$php .= "//END:{$slug}//";
		return $php;
	}

	/**
	 *	Remove plugin part from sunrise.php
	 */
	private static function reset_sunrise( $slug = null ) {
		if ( is_null( $slug ) ) {
			$slug = Plugin::instance()->get_slug();
		}
		if ( file_exists( WP_CONTENT_DIR . '/sunrise.php' ) ) {
			$sunrise_contents = file_get_contents( WP_CONTENT_DIR . '/sunrise.php' );
			$sunrise_contents = preg_replace("@//BEGIN:{$slug}(.*)END:{$slug}//@imsU", '', $sunrise_contents );
		} else {
			$sunrise_contents = '<?php' . "\n\n";
			$sunrise_contents .=  "/* sunrise.php added by Plugin {$slug} */\n";
		}
		return $sunrise_contents;
	}
}
