<form>
	<div class="alignright">
		<select  name="bulk_action">
			<option value=""><?php esc_html_e( 'Bulk Actions', 'countera' ); ?></option>
			<option value="delete"><?php esc_html_e( 'Delete', 'countera' ); ?></option>
		</select>
		<button class="btn btn-primary btn-sm"><?php esc_html_e( 'Apply', 'countera' ); ?></button>
		<img style="display: none" width="30px" src="<?php echo esc_url( $this->get_image_dir_url() . 'loading.gif' ); ?>">
	</div>
	<input type="hidden" name="action" value="countera_bulk_action">
	<input type="hidden" name="nonce" value="<?php echo esc_html( wp_create_nonce( "{$this->plugin_name}_bulk_action_nonce" ) ); ?>">
	<?php include $this->get_plugin_name_dir() . 'inc/admin/views/tables/listing.php'; ?>
</form>
