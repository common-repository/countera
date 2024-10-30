<?php

namespace Countera\Inc\Frontend;

use Countera as NS;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @author    Faiyaz Alam
 */
class Frontend {

	const INTERVAL = 10;

	/**
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 *
	 * @var string
	 */
	private $version;

	/**
	 *
	 * @var string
	 */
	private $plugin_text_domain;

	/**
	 *
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The plugin name.
	 * @param string $version The plugin version.
	 * @param string $plugin_text_domain The plugin text domain.
	 */
	public function __construct( $plugin_name, $version, $plugin_text_domain ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->plugin_text_domain = $plugin_text_domain;
	}

	/**
	 * Check if the requested page should be tracked.
	 *
	 * @return boolean
	 */
	private function should_track() {
		if ( ! is_singular() || ! is_user_logged_in() ) {
			return false;
		}

		return (bool) apply_filters( "{$this->plugin_name}_should_track", true );
	}

	/**
	 * Hooked with wp_head.
	 */
	public function update_count() {
		if ( ! $this->should_track() ) {
			return false;
		}

		global $post;
		global $wpdb;
		$table = $wpdb->prefix . NS\TABLE_POST_VIEW_COUNT;
		$date = gmdate( 'Y-m-d' );
		$datetime = gmdate( 'Y-m-d H:i:s' );

		$original_data = array(
			'user_id' => get_current_user_id(),
			'post_id' => $post->ID,
			'view_count' => 1,
			'creation_date' => "'$date'",
			'created_at' => "'$datetime'",
			'modified_at' => "'$datetime'",
		);

		$data = apply_filters( "{$this->plugin_name}_post_view_count_before_save", $original_data );
		$columns = implode( ', ', array_keys( $data ) );
		$values = implode( ', ', array_values( $data ) );

		$interval = absint( apply_filters( "{$this->plugin_name}_allowed_interval", self::INTERVAL ) );
		$condition = "TIMESTAMPDIFF(SECOND, modified_at, '$datetime') > $interval";

		$sql = "INSERT INTO $table ($columns) "
				. " VALUES ($values) "
				. ' ON DUPLICATE KEY UPDATE '
				. " view_count = IF($condition, view_count+1, view_count), "
				. " modified_at = IF($condition, '$datetime', modified_at);";

		$wpdb->query( $sql );
		do_action( "{$this->plugin_name}_post_view_count_after_save", $data );
	}

}
