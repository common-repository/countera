<?php
/**
 * Vars $template
 */
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<hr class="min_hr">
	<?php
	if ( file_exists( $template ) ) {
		include $template;
	} else {
		echo esc_html( "No template found at '$template'", 'countera' );
	}
	?>
	<hr class="min_hr">
	<div>
		<a  target="_blank" href="https://wordpress.org/plugins/user-login-history/" class="btn btn-info btn-sm"><?php esc_html_e( 'RECOMMENDED PLUGIN', 'countera' ); ?> - User Login History</a>
		<a  target="_blank" href="https://www.paypal.com/paypalme/erfaiyazalam/" class="btn btn-info btn-sm"><?php esc_html_e( 'Donate and Support', 'countera' ); ?></a>
	</div>
</div>
