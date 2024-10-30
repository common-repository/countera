<?php

namespace Countera\Inc\Core;

use Countera as NS;

/**
 * Fired during plugin activation
 *
 * @author     Faiyaz Alam
 * */
class Activator {

	/**
	 * Short Description.
	 *
	 * Long Description.
	 */
	public static function activate() {

		$min_php = '7.3.0';

		// Check PHP Version and deactivate & die if it doesn't meet minimum requirements.
		if ( version_compare( PHP_VERSION, $min_php, '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( esc_html( 'This plugin requires a minmum PHP Version of ' . $min_php ) );
		}

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table = $wpdb->prefix . NS\TABLE_POST_VIEW_COUNT;

		$sql = "CREATE TABLE $table (
id int(11) NOT NULL AUTO_INCREMENT,
user_id int(11) NOT NULL,
post_id int(11) NOT NULL,
view_count bigint(20) NOT NULL,
creation_date date NOT NULL,
created_at datetime NOT NULL,
modified_at datetime NOT NULL,
PRIMARY KEY  (id),
KEY inx_view_count (view_count),
KEY inx_created_at (created_at),
KEY inx_modified_at (modified_at),
UNIQUE KEY unq_entry (user_id, post_id, creation_date)
) $charset_collate ENGINE=InnoDB;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

}
