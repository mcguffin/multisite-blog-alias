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
				return; // don't acitvate!
			}
			$wp_config_contents = file_get_contents( $wp_config );
			$code = '/* Added by WP Blog Alias Plugin */' . "\n";
			$code .= 'define( \'SUNRISE\', true );'."\n\n";
			$wp_config_contents = substr( $wp_config_contents, 5);
			$wp_config_contents = '<?php' . "\n" . $code . $wp_config_contents;
			file_put_contents( $wp_config, $wp_config_contents );
		}

		// write sunrise
		if ( ! file_exists( WP_CONTENT_DIR . '/sunrise.php' ) ) {
			$sunrise_contents = '<?php' . "\n\n";
			$sunrise_contents .=  '/* sunrise.php added by WP Blog Alias Plugin */' . "\n";
		} else {
			$sunrise_contents = file_get_contents( WP_CONTENT_DIR . '/sunrise.php' );
			$sunrise_contents = preg_replace('@//BEGIN:blog_alias(.*)END:blog_alias//@imsU', '', $sunrise_contents );
		}
		$sunrise_contents .= '//BEGIN:blog_alias' . "\n";
		$sunrise_contents .= 'require_once WP_CONTENT_DIR . \'/plugins/'. dirname( $core->get_wp_plugin() ).'/sunrise.php\';' . "\n";
		$sunrise_contents .= '//END:blog_alias//' . "\n";
		file_put_contents( WP_CONTENT_DIR . '/sunrise.php', $sunrise_contents );
	}


	/**
	 *	@inheritdoc
	 */
	public function upgrade( $new_version, $old_version ) {

	}

	/**
	 *	@inheritdoc
	 */
	public function deactivate() {
		if ( file_exists( WP_CONTENT_DIR . '/sunrise.php' ) ) {
			$sunrise_contents = file_get_contents( WP_CONTENT_DIR . '/sunrise.php' );
			$sunrise_contents = preg_replace('@//BEGIN:blog_alias(.*)END:blog_alias//@imsU', '', $sunrise_contents );
			file_put_contents( WP_CONTENT_DIR . '/sunrise.php', $sunrise_contents );
		}
	}

	/**
	 *	@inheritdoc
	 */
	public static function uninstall() {

	}
}
