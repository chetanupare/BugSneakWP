<?php
/**
 * Culprit Detector for BugSneak.
 *
 * @package BugSneak\Culprit
 */

namespace BugSneak\Culprit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Detector
 */
class Detector {

	/**
	 * Detect the culprit (Plugin, Theme, or Core) based on file path.
	 *
	 * @param string $file Path to the file.
	 * @return string
	 */
	public static function detect( $file ) {
		$file = str_replace( '\\', '/', $file );

		if ( strpos( $file, 'wp-content/plugins/' ) !== false ) {
			preg_match( '/wp-content\/plugins\/([^\/]+)/', $file, $matches );
			return 'Plugin: ' . ( $matches[1] ?? 'Unknown' );
		}

		if ( strpos( $file, 'wp-content/themes/' ) !== false ) {
			preg_match( '/wp-content\/themes\/([^\/]+)/', $file, $matches );
			return 'Theme: ' . ( $matches[1] ?? 'Unknown' );
		}

		if ( strpos( $file, 'wp-includes/' ) !== false || strpos( $file, 'wp-admin/' ) !== false ) {
			return 'WordPress Core';
		}

		return 'Unknown';
	}
}
