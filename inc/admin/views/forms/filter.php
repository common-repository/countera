<?php
$post_types = get_post_types(
	array(
		'public' => true,
		'_builtin' => false,
	),
	'objects'
);
?>
<form id="filter">
	<div class="form-row">
		<div class="form-group col-md-5">
			<label for="username"><?php esc_html_e( 'Username', 'countera' ); ?></label>
			<input type="text" class="form-control" id="username" name="username" placeholder="<?php esc_html_e( 'Enter Username', 'countera' ); ?>" value="">
		</div>
		<div class="form-group col-md-5">
			<label for="post_title"><?php esc_html_e( 'Post Title', 'countera' ); ?></label>
			<input type="text" class="form-control" id="post_title" name="post_title" placeholder="<?php esc_html_e( 'Enter Post Title', 'countera' ); ?>" value="">
		</div>
		<div class="form-group col-md-2">
			<label for="post_type"><?php esc_html_e( 'Post Type', 'countera' ); ?></label>
			<select id="post_type" name="post_type" class="form-control" name="post_type">
				<option value="page"><?php esc_html_e( 'Page', 'countera' ); ?></option>
				<option value="post"><?php esc_html_e( 'Post', 'countera' ); ?></option>
				<?php
				foreach ( $post_types as $post_type_obj ) {
					$labels = get_post_type_labels( $post_type_obj );
					?>
					<option value="<?php echo esc_attr( $post_type_obj->name ); ?>"><?php echo esc_html( $labels->name ); ?></option>
				<?php } ?>
			</select>
		</div>
	</div>
	<div class="form-row" id="other-filter" style="display: none">
		<div class="form-group col-md-3">
			<label for="minimum_count"><?php esc_html_e( 'Minimum Views Count', 'countera' ); ?></label>
			<input min="1" type="number" class="form-control" id="minimum_count" name="minimum_count" placeholder="Enter number">
		</div>
		<div class="form-group col-md-3">
			<label for="maximum_count"><?php esc_html_e( 'Maximum Views Count', 'countera' ); ?></label>
			<input min="1" type="number" class="form-control" id="maximum_count" name="maximum_count" placeholder="Enter number">
		</div>

		<div class="form-group col-md-3">
			<label for="from_date"><?php esc_html_e( 'From Date', 'countera' ); ?></label>
			<input min="2021-01-01" max="<?php echo esc_html( wp_date( 'Y-m-d' ) ); ?>" type="date" class="form-control" id="from_date" name="from_date" placeholder="Enter From Date">
		</div>
		<div class="form-group col-md-3">
			<label for="to_date"><?php esc_html_e( 'To Date', 'countera' ); ?></label>
			<input  min="2021-01-01" max="<?php echo esc_html( wp_date( 'Y-m-d' ) ); ?>" type="date" class="form-control" id="to_date" name="to_date" placeholder="Enter To Date">
		</div>
	</div>
	<button type="submit" class="btn btn-primary btn-sm"><?php esc_html_e( 'Apply Filter', 'countera' ); ?></button>
	<a href="<?php echo esc_url( menu_page_url( $this->get_listing_page_slug(), false ) ); ?>" class="btn btn-primary btn-sm"><?php esc_html_e( 'Reset Filter', 'countera' ); ?></a>
	<a class="btn btn-primary btn-sm alignright" id="show-hide-other-filter"><i class="fa fa-angle-double-up" aria-hidden="true"></i><i class="fa fa-angle-double-down" aria-hidden="true"></i></a>
</form>
<hr>
