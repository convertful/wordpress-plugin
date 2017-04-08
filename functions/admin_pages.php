<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

add_action( 'admin_menu', 'ogr_add_admin_pages', 30 );
function ogr_add_admin_pages() {
	add_submenu_page( 'tools.php', 'Optin.Guru', 'Optin.Guru', 'manage_options', 'og-settings', 'ogr_settings_page' );
}

function ogr_handle_return_to_endpoint() {
	// Handling return to backend
	if ( isset( $_POST['owner_id'] ) AND isset( $_POST['website_id'] ) AND isset( $_POST['token'] ) AND isset( $_POST['_nonce'] ) AND wp_verify_nonce( $_POST['_nonce'], 'ogr_connect' ) ) {
		update_option( 'optinguru_owner_id', (int) $_POST['owner_id'], TRUE );
		update_option( 'optinguru_website_id', (int) $_POST['website_id'], FALSE );
		update_option( 'optinguru_token', $_POST['token'], FALSE );
	}
}

function ogr_settings_page() {
	$domain = 'https://dev.optin.guru';
	ogr_handle_return_to_endpoint();
	$website_id = get_option( 'optinguru_website_id' );
	if ( $website_id === FALSE ) {
		?>
		<div class="ogr-connect">
			<div class="ogr-connect-logo">Optin.Guru</div>
			<div class="ogr-connect-box">
				<h1 class="ogr-connect-header">Connect Website to Optin.Guru</h1>
				<form class="ogr-connect-card" method="post" action="<?php echo esc_attr( $domain . '/oauth2/connect_website' ) ?>">
					<div class="ogr-connect-card-body">
						<p>Please create an Optin.Guru Account or connect to an existing Account.<br>
							This will allow you to <strong>grow email lists easily</strong> using our top-notch builder
							with unique features and amazing pre-built form templates!</p>
					</div>
					<div class="ogr-connect-card-footer">
						<input type="hidden" name="endpoint" value="<?php echo esc_attr( admin_url( 'tools.php?page=og-settings' ) ) ?>">
						<input type="hidden" name="domain" value="<?php echo esc_attr( preg_replace( '~^https?:\/\/~', '', get_site_url() ) ) ?>">
						<input type="hidden" name="website_name" value="<?php echo esc_attr( get_bloginfo( 'name' ) ) ?>">
						<input type="hidden" name="platform" value="WordPress">
						<input type="hidden" name="_nonce" value="<?php echo esc_attr( wp_create_nonce( 'ogr_connect' ) ) ?>">
						<button class="ogr-btn action_connect">
							Connect to Optin.Guru
						</button>
					</div>
				</form>
			</div>
			<a href="https://help.optin.guru/og/connect/" class="ogr-connect-help" target="_blank">Get help connecting your site</a>
		</div>
		<?php
	} else {
		?>
		<div class="ogr-connect type_success">
			<div class="ogr-connect-logo">Optin.Guru</div>
			<div class="ogr-connect-box">
				<h1 class="ogr-connect-header">Website is Connected to Optin.Guru</h1>
				<div class="ogr-connect-card">
					<div class="ogr-connect-card-body">
						<p>Congratulations! Your website is connected to Optin.Guru.</p>
						<p>Now you can <strong>grow email lists easily</strong> using our top-notch builder
							with unique features and amazing pre-built form templates!</p>
					</div>
					<div class="ogr-connect-card-footer">
						<a class="ogr-btn action_create" href="<?php echo esc_attr( $domain . '/widgets/create?website_id=' . $website_id ) ?>">
							Create New Optin
						</a>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
