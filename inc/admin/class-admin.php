<?php

namespace Countera\Inc\Admin;

use Countera as NS;

/**
 * The admin-specific functionality of the plugin.
 *
 * @author    Faiyaz Alam
 */
class Admin {

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
	 * The sql conditions.
	 *
	 * @var array
	 */
	private $conditions = array();

	/**
	 * The variables for sql conditions.
	 *
	 * @var array
	 */
	private $condition_vars = array();

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
	 *
	 * @return string
	 */
	private function get_plugin_name_dir() {
		return NS\PLUGIN_NAME_DIR;
	}

	/**
	 *
	 * @return string
	 */
	private function get_plugin_name_url() {
		return NS\PLUGIN_NAME_URL;
	}

	/**
	 *
	 * @return string
	 */
	private function get_image_dir_url() {
		return $this->get_plugin_name_url() . 'inc/admin/images/';
	}

	/**
	 * @return string
	 */
	private function get_listing_page_slug() {
		return $this->plugin_name . '-count-post-views';
	}

	/**
	 * Handle response headers for csv download.
	 *
	 * @param string $filename
	 */
	private function download_send_headers( $filename ) {
		// disable caching
		$now = gmdate( 'D, d M Y H:i:s' );
		header( 'Expires: Tue, 01 Jul 2020 01:00:00 GMT' );
		header( 'Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate' );
		header( "Last-Modified: {$now} GMT" );

		// force download
		header( 'Content-Type: application/force-download' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Type: application/download' );

		// disposition / encoding on response body
		header( "Content-Disposition: attachment;filename={$filename}" );
		header( 'Content-Transfer-Encoding: binary' );
	}

	/**
	 * Prepare the main sql query.
	 *
	 * @return string
	 */
	private function get_main_query() {
		global $wpdb;
		$table_view_counter = $wpdb->prefix . NS\TABLE_POST_VIEW_COUNT;

		$main_query = 'SELECT '
				. ' ViewCount.id, '
				. ' ViewCount.post_id, '
				. ' Post.post_title, '
				. ' Post.post_type, '
				. ' ViewCount.user_id, '
				. ' User.user_login AS username,'
				. ' ViewCount.view_count, '
				. ' ViewCount.creation_date, '
				. ' ViewCount.created_at, '
				. ' ViewCount.modified_at '
				. " FROM $table_view_counter AS ViewCount "
				. " INNER JOIN {$wpdb->posts} AS Post ON (ViewCount.post_id = Post.ID) "
				. " INNER JOIN {$wpdb->users} AS User ON (ViewCount.user_id = User.ID)";

		return $main_query;
	}

	/**
	 * Prepare the count sql query.
	 *
	 * @return string
	 */
	private function get_count_query() {
		global $wpdb;
		$table_view_counter = $wpdb->prefix . NS\TABLE_POST_VIEW_COUNT;

		$count_query = "SELECT COUNT(ViewCount.id) FROM $table_view_counter AS ViewCount "
				. " INNER JOIN {$wpdb->posts} AS Post ON (ViewCount.post_id = Post.ID) "
				. " INNER JOIN {$wpdb->users} AS User ON (ViewCount.user_id = User.ID)";
		return $count_query;
	}

	/**
	 * Gets global datetime format.
	 *
	 * @return string
	 */
	private function get_datetime_format() {
		return get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	}

	/**
	 * Prepare sql conditions.
	 *
	 * @param array $terms
	 */
	private function conditions( array $terms ) {
		$username = isset( $terms['username'] ) ? trim( $terms['username'] ) : null;
		$post_title = isset( $terms['post_title'] ) ? trim( $terms['post_title'] ) : null;
		$post_type = isset( $terms['post_type'] ) ? trim( $terms['post_type'] ) : null;
		$minimum_count = isset( $terms['minimum_count'] ) ? absint( $terms['minimum_count'] ) : null;
		$maximum_count = isset( $terms['maximum_count'] ) ? absint( $terms['maximum_count'] ) : null;
		$from_date = isset( $terms['from_date'] ) ? trim( $terms['from_date'] ) : null;
		$to_date = isset( $terms['to_date'] ) ? trim( $terms['to_date'] ) : null;

		if ( $username ) {
			$this->conditions[] = 'user_login LIKE %s';
			$this->condition_vars[] = "%{$username}%";
		}

		if ( $post_title ) {
			$this->conditions[] = 'post_title LIKE %s';
			$this->condition_vars[] = "%$post_title%";
		}

		if ( $post_type ) {
			$this->conditions[] = 'post_type = %s';
			$this->condition_vars[] = $post_type;
		}

		if ( $minimum_count ) {
			$this->conditions[] = 'view_count >= %d';
			$this->condition_vars[] = $minimum_count;
		}

		if ( $maximum_count ) {
			$this->conditions[] = 'view_count <= %d';
			$this->condition_vars[] = $maximum_count;
		}

		if ( $from_date ) {
			$this->conditions[] = 'creation_date >= %s';
			$this->condition_vars[] = $from_date;
		}

		if ( $to_date ) {
			$this->conditions[] = 'creation_date <= %s';
			$this->condition_vars[] = $to_date;
		}
	}

	/**
	 * Check if requested to export csv.
	 *
	 * @return bool
	 */
	private function is_export_request() {
		return isset( $_GET['page'] ) && $this->get_listing_page_slug() == $_GET['page'] && isset( $_GET['countera_action'] ) && 'export' == $_GET['countera_action'];
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 */
	public function enqueue_styles( $hook ) {
		if ( 'users_page_' . $this->plugin_name . '-count-post-views' == $hook ) {
			wp_enqueue_style( $this->plugin_name . '-bootstrap', plugin_dir_url( __FILE__ ).'css/bootstrap.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name . '-dataTables.bootstrap4.min', plugin_dir_url( __FILE__ ).'css/dataTables.bootstrap4.min.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name . '-buttons.bootstrap4.min', plugin_dir_url( __FILE__ ).'css/buttons.bootstrap4.min.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name . '-font-awesome.min', plugin_dir_url( __FILE__ ).'css/font-awesome.min.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name . '-toastr.min', plugin_dir_url( __FILE__ ).'css/toastr.min.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/style.css', array(), $this->version, 'all' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'users_page_' . $this->plugin_name . '-count-post-views' == $hook ) {
			$listing_url = menu_page_url( $this->get_listing_page_slug(), false );
			wp_enqueue_script( $this->plugin_name . '-jquery.dataTables.min', plugin_dir_url( __FILE__ ).'js/jquery.dataTables.min.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name . '-dataTables.bootstrap4.min', plugin_dir_url( __FILE__ ).'js/dataTables.bootstrap4.min.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name . '-dataTables.buttons.min', plugin_dir_url( __FILE__ ).'js/dataTables.buttons.min.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name . '-buttons.bootstrap4.min', plugin_dir_url( __FILE__ ).'js/buttons.bootstrap4.min.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name . '-buttons.html5.min', plugin_dir_url( __FILE__ ).'js/buttons.html5.min.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name . '-buttons.colVis.min', plugin_dir_url( __FILE__ ).'js/buttons.colVis.min.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name . '-dataTables.colReorder.min', plugin_dir_url( __FILE__ ).'js/dataTables.colReorder.min.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name . '-dataTables.select.min', plugin_dir_url( __FILE__ ).'js/dataTables.select.min.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name . '-toastr.min', plugin_dir_url( __FILE__ ).'js/toastr.min.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/scripts.js', array( 'jquery' ), $this->version, false );
			wp_localize_script(
				$this->plugin_name,
				'countera_local_script_data',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'listing_url' => $listing_url,
					'export_url' => $listing_url . "&{$this->plugin_name}_action=export&nonce=" . wp_create_nonce( "{$this->plugin_name}_export_nonce" ),
					'text' => array(
						'are_you_sure' => esc_html__( 'Are you sure?', 'countera' ),
						'yes' => esc_html__( 'Yes', 'countera' ),
						'no' => esc_html__( 'No', 'countera' ),
						'export_csv' => esc_html__( 'Export CSV', 'countera' ),
						'datatable' => array(
							'processing' => esc_html__( 'Processing', 'countera' ),
							'previous' => esc_html__( 'Previous', 'countera' ),
							'next' => esc_html__( 'Next', 'countera' ),
							'search' => esc_html__( 'Search', 'countera' ),
							/* translators: %s: item per page */
							'lengthMenu' => wp_sprintf( esc_html__( 'Show %s Items Per Page', 'countera' ), '_MENU_' ),
							'zero_records' => esc_html__( 'No records found!', 'countera' ),
							/* translators: %1$s: page, %2$s: pages, %3$s: max */
							'info' => wp_sprintf( esc_html__( 'Showing page %1$s of %2$s (%3$s total records found)', 'countera' ), '_PAGE_', '_PAGES_', '_MAX_' ),
							/* translators: %s: item per page */
							'info_filtered' => wp_sprintf( esc_html__( '(filtered from %s total records)', 'countera' ), '_MAX_' ),
						),
					),
				)
			);
		}
	}

	/**
	 * Hooked with admin_menu.
	 */
	public function admin_menu() {
		add_users_page(
			__( 'Count Post Views', 'countera' ),
			__( 'Count Post Views', 'countera' ),
			'manage_options',
			$this->get_listing_page_slug(),
			array( $this, 'render' ),
		);
	}

	/**
	 * Render the listing page.
	 */
	public function render() {
		$tab_name = ! empty( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'listing';
		$template = $this->get_plugin_name_dir() . "inc/admin/views/$tab_name.php";
		include $this->get_plugin_name_dir() . 'inc/admin/views/layouts/default.php';
	}

	/**
	 * Get user post views count. Hooked with ajax hook.
	 */
	public function get_user_post_views_count() {
		$draw = isset( $_GET['draw'] ) ? absint( $_GET['draw'] ) : 0;

		if ( ! $draw ) {
			return;
		}

		$order_column_index = sanitize_text_field( $_GET['order'][0]['column'] );
		$order_dir = sanitize_text_field( $_GET['order'][0]['dir'] );
		$order_by = sanitize_text_field( $_GET['columns'][ $order_column_index ]['data'] );

		$offset = isset( $_GET['start'] ) ? (int) $_GET['start'] : 0;
		$limit = isset( $_GET['length'] ) ? (int) $_GET['length'] : 10;
		$search = isset( $_GET['search'] ) ? wp_unslash( $_GET['search'] ) : array();
		$search_value = isset( $search['value'] ) ? sanitize_text_field( $search['value'] ) : '';
		$terms = array();
		parse_str( $search_value, $terms );

		global $wpdb;
		$records = array();
		$this->conditions( $terms );
		$count_query = $this->get_count_query();
		$main_query = $this->get_main_query();

		$records_total = $wpdb->get_var( $count_query );

		$sanitized_order_by = sanitize_sql_orderby( "$order_by $order_dir" );
		$sql_keyword = " ORDER BY $sanitized_order_by LIMIT $offset, $limit";

		if ( ! empty( $this->conditions ) ) {
			$condition = implode( ' AND ', $this->conditions );
			$records_filtered = $wpdb->get_var( $wpdb->prepare( "$count_query WHERE $condition", $this->condition_vars ) );
			$records = $wpdb->get_results( $wpdb->prepare( "$main_query WHERE $condition $sql_keyword", $this->condition_vars ) );
		} else {
			$records_filtered = $records_total;
			$records = $wpdb->get_results( "$main_query $sql_keyword" );
		}

		$datetime_format = $this->get_datetime_format();
		foreach ( $records as &$record ) {
			$record->username = esc_html( $record->username );
			$record->post_title = esc_html( $record->post_title );
			$record->user_link = get_edit_user_link( $record->user_id );
			$record->post_link = get_edit_post_link( $record->post_id );
			$record->created_at = wp_date( $datetime_format, strtotime( $record->created_at ) );
			$record->modified_at = wp_date( $datetime_format, strtotime( $record->modified_at ) );
		}

		$output = array(
			'draw' => $draw,
			'recordsTotal' => $records_total,
			'recordsFiltered' => $records_filtered,
			'data' => $records,
		);

		echo json_encode( $output );
		die();
	}

	/**
	 * Exports csv.
	 *
	 */
	public function export() {
		if ( ! $this->is_export_request() ) {
			return;
		}

		$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( $_GET['nonce'] ) : null;
		if ( ! wp_verify_nonce( $nonce, "{$this->plugin_name}_export_nonce" ) ) {
			wp_die( 'Error' );
		}

		$this->conditions( $_GET );

		global $wpdb;
		$table_view_counter = $wpdb->prefix . NS\TABLE_POST_VIEW_COUNT;

		$sql = $this->get_main_query();

		if ( $this->conditions ) {
			$condition = implode( ' AND ', $this->conditions );
			$records = $wpdb->get_results( $wpdb->prepare( "$sql WHERE $condition", $this->condition_vars ), ARRAY_A );
		} else {
			$records = $wpdb->get_results( $sql, ARRAY_A );
		}

		$this->download_send_headers( 'export_' . gmdate( 'Y-m-d' ) . '.csv' );
		echo $this->array2csv( $records );
		die();
	}

	/**
	 * Convert array to csv.
	 *
	 * @param array $array
	 * @return string
	 */
	public function array2csv( array $array ) {
		if ( count( $array ) == 0 ) {
			return null;
		}

		ob_start();
		$df = fopen( 'php://output', 'w' );
		$header = apply_filters( "{$this->plugin_name}_csv_header", array_keys( $array[0] ) );
		fputcsv( $df, $header );
		foreach ( $array as $row ) {
			fputcsv( $df, apply_filters( "{$this->plugin_name}_csv_row", $row ) );
		}
		fclose( $df );
		return ob_get_clean();
	}

	/**
	 * Do bulk action.
	 *
	 * @global type $wpdb
	 */
	public function bulk_action() {
		check_ajax_referer( "{$this->plugin_name}_bulk_action_nonce", 'nonce' );
		$action = ! empty( $_POST['bulk_action'] ) ? sanitize_text_field( $_POST['bulk_action'] ) : '';
		$ids = ! empty( $_POST['ids'] ) && is_array( $_POST['ids'] ) ? array_map( 'sanitize_text_field', $_POST['ids'] ) : array();

		if ( empty( $ids ) ) {
			wp_send_json_error( array( 'message' => 'Please select some records.' ), 400 );
		}

		$ids = array_map(
			function( $id ) {
				return absint( $id );
			},
			$ids
		);

		$id_list = implode( ', ', $ids );

		$output = array();

		global $wpdb;
		$table = $wpdb->prefix . NS\TABLE_POST_VIEW_COUNT;

		switch ( $action ) {
			case 'delete':
				$wpdb->query( "DELETE FROM $table WHERE id IN ($id_list)" );
				$output['message'] = 'The selected records have been deleted!';
				break;

			default:
				wp_send_json_error( array( 'message' => 'Invalid action.' ), 500 );
				break;
		}

		wp_send_json_success( $output );
	}

}
