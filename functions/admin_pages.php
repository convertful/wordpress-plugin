<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

add_action( 'admin_menu', 'ogr_add_admin_pages', 30 );
function ogr_add_admin_pages() {
	add_submenu_page( 'tools.php', 'Optin.Guru', 'Optin.Guru', 'manage_options', 'og-settings', 'ogr_settings_page' );
}

function ogr_handle_return_to_endpoint() {
	// Handling return to backend
	if ( isset( $_GET['owner_id'] ) AND isset( $_GET['site_id'] ) AND isset( $_GET['token'] ) AND isset( $_GET['_nonce'] ) AND wp_verify_nonce( $_GET['_nonce'], 'ogr_connect' ) ) {
		update_option( 'optinguru_owner_id', (int) $_GET['owner_id'], TRUE );
		update_option( 'optinguru_site_id', (int) $_GET['site_id'], FALSE );
		update_option( 'optinguru_token', $_GET['token'], FALSE );
		// Redirect
		echo '<script type="text/javascript">location.assign(\'' . admin_url( 'tools.php?page=og-settings' ) . '\')</script>';
	}
}

function ogr_handle_disconnect_click() {
	if ( isset( $_GET['disconnect'] ) AND wp_verify_nonce( $_GET['disconnect'], 'ogr_disconnect' ) ) {
		ogr_uninstall();
		// Redirect
		echo '<script type="text/javascript">location.assign(\'' . admin_url( 'tools.php?page=og-settings' ) . '\')</script>';
	}
}

function ogr_settings_page() {
	global $ogr_domain;
	ogr_handle_return_to_endpoint();
	ogr_handle_disconnect_click();
	$site_id = get_option( 'optinguru_site_id', get_option( 'optinguru_website_id' ) );
	if ( $site_id === FALSE ) {
		?>
		<div class="ogr-connect">
			<div class="ogr-connect-logo">
				<div class="ogr-connect-logo-img" style="background-image: url(<?php echo $ogr_domain ?>/assets/img/logo_blue.png);"></div>
				<div class="ogr-connect-logo-text">Optin.Guru</div>
			</div>
			<div class="ogr-connect-box">
				<h1 class="ogr-connect-header">Connect Site to Optin.Guru</h1>
				<form class="ogr-connect-card" method="post" action="<?php echo esc_attr( $ogr_domain . '/oauth2/connect_site' ) ?>">
					<div class="ogr-connect-card-body">
						<p>Please create an Optin.Guru Account or connect to an existing Account.<br>
							This will allow you to <strong>grow email lists easily</strong> using our top-notch builder
							with unique features and amazing pre-built form templates!</p>
					</div>
					<div class="ogr-connect-card-footer">
						<input type="hidden" name="endpoint" value="<?php echo esc_attr( admin_url( 'tools.php?page=og-settings' ) ) ?>">
						<input type="hidden" name="domain" value="<?php echo esc_attr( preg_replace( '~^https?:\/\/~', '', get_site_url() ) ) ?>">
						<input type="hidden" name="site_name" value="<?php echo esc_attr( get_bloginfo( 'name' ) ) ?>">
						<input type="hidden" name="platform" value="WordPress">
						<input type="hidden" name="_nonce" value="<?php echo esc_attr( wp_create_nonce( 'ogr_connect' ) ) ?>">
						<button class="ogr-btn action_connect">
							Connect to Optin.Guru
						</button>
					</div>
				</form>
			</div>
			<a href="https://app.optin.guru/docs/connect/wordpress/" class="ogr-connect-help" target="_blank">Get help connecting your site</a>
		</div>
		<?php
	} else {
		?>
		<div class="ogr-connect type_success">
			<div class="ogr-connect-logo">
				<div class="ogr-connect-logo-img" style="background-image: url(<?php echo $ogr_domain ?>/assets/img/logo_blue.png);"></div>
				<div class="ogr-connect-logo-text">Optin.Guru</div>
			</div>
			<div class="ogr-connect-box">
				<h1 class="ogr-connect-header">Site is Connected to Optin.Guru</h1>
				<div class="ogr-connect-card">
					<div class="ogr-connect-card-body">
						<p>Congratulations! Your site is connected to Optin.Guru.</p>
						<p>Now you can <strong>grow email lists easily</strong> using our top-notch builder
							with unique features and amazing pre-built form templates!</p>
					</div>
					<div class="ogr-connect-card-footer">
						<a class="ogr-btn action_create" href="<?php echo esc_attr( $ogr_domain . '/sites/' . $site_id . '/widgets/create/' ) ?>" target="_blank">
							Create New Optin
						</a>
						<a href="https://optin.guru/premium/" class="ogr-btn-premium" target="_blank">Learn about Premium features</a>
					</div>
				</div>
			</div>
			<a href="<?php echo admin_url( 'tools.php?page=og-settings&disconnect=' . wp_create_nonce( 'ogr_disconnect' ) ) ?>" class="ogr-connect-disconnect">Disconnect site</a>
		</div>
		<?php
	}
}
