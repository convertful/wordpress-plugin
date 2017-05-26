<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

add_action( 'admin_menu', 'conv_add_admin_pages', 30 );
function conv_add_admin_pages() {
	add_submenu_page( 'tools.php', 'Convertful', 'Convertful', 'manage_options', 'og-settings', 'conv_settings_page' );
}

function conv_handle_return_to_endpoint() {
	// Handling return to backend
	if ( isset( $_GET['owner_id'] ) AND isset( $_GET['site_id'] ) AND isset( $_GET['token'] ) AND isset( $_GET['_nonce'] ) AND wp_verify_nonce( $_GET['_nonce'], 'conv_connect' ) ) {
		update_option( 'convertful_owner_id', (int) $_GET['owner_id'], TRUE );
		update_option( 'convertful_site_id', (int) $_GET['site_id'], FALSE );
		update_option( 'convertful_token', $_GET['token'], FALSE );
		// Redirect
		echo '<script type="text/javascript">location.assign(\'' . admin_url( 'tools.php?page=og-settings' ) . '\')</script>';
	}
}

function conv_handle_disconnect_click() {
	if ( isset( $_GET['disconnect'] ) AND wp_verify_nonce( $_GET['disconnect'], 'conv_disconnect' ) ) {
		conv_uninstall();
		// Redirect
		echo '<script type="text/javascript">location.assign(\'' . admin_url( 'tools.php?page=og-settings' ) . '\')</script>';
	}
}

function conv_settings_page() {
	global $conv_domain;
	conv_handle_return_to_endpoint();
	conv_handle_disconnect_click();
	$site_id = get_option( 'convertful_site_id' );
	if ( $site_id === FALSE ) {
		?>
		<div class="conv-connect">
			<div class="conv-connect-logo">
				<img class="conv-connect-logo-img" src="<?php echo $conv_domain ?>/assets/img/logo_blue.png" srcset="<?php echo $conv_domain ?>/assets/img/logo_blue@2x.png 2x" alt="Convertful">
			</div>
			<div class="conv-connect-box">
				<h1 class="conv-connect-header">Connect Site to Convertful</h1>
				<form class="conv-connect-card" method="post" action="<?php echo esc_attr( $conv_domain . '/oauth2/connect_site' ) ?>">
					<div class="conv-connect-card-body">
						<p>Please create a Convertful Account or connect to an existing Account.<br>
							This will allow you to <strong>grow email lists easily</strong> using our top-notch builder
							with unique features and amazing pre-built form templates!</p>
					</div>
					<div class="conv-connect-card-footer">
						<input type="hidden" name="endpoint" value="<?php echo esc_attr( admin_url( 'tools.php?page=og-settings' ) ) ?>">
						<input type="hidden" name="domain" value="<?php echo esc_attr( preg_replace( '~^https?:\/\/~', '', get_site_url() ) ) ?>">
						<input type="hidden" name="site_name" value="<?php echo esc_attr( get_bloginfo( 'name' ) ) ?>">
						<input type="hidden" name="platform" value="WordPress">
						<input type="hidden" name="_nonce" value="<?php echo esc_attr( wp_create_nonce( 'conv_connect' ) ) ?>">
						<button class="conv-btn action_connect">
							Connect to Convertful
						</button>
					</div>
				</form>
			</div>
			<a href="https://app.convertful.com/docs/connect/wordpress/" class="conv-connect-help" target="_blank">Get help connecting your site</a>
		</div>
		<?php
	} else {
		?>
		<div class="conv-connect type_success">
			<div class="conv-connect-logo">
				<img class="conv-connect-logo-img" src="<?php echo $conv_domain ?>/assets/img/logo_blue.png" srcset="<?php echo $conv_domain ?>/assets/img/logo_blue@2x.png 2x" alt="Convertful">
			</div>
			<div class="conv-connect-box">
				<h1 class="conv-connect-header">Site is Connected to Convertful</h1>
				<div class="conv-connect-card">
					<div class="conv-connect-card-body">
						<p>Congratulations! Your site is connected to Convertful.</p>
						<p>Now you can <strong>grow email lists easily</strong> using our top-notch builder
							with unique features and amazing pre-built form templates!</p>
					</div>
					<div class="conv-connect-card-footer">
						<a class="conv-btn action_create" href="<?php echo esc_attr( $conv_domain . '/sites/' . $site_id . '/widgets/create/' ) ?>" target="_blank">
							Create New Optin
						</a>
						<a href="https://convertful.com/premium/" class="conv-btn-premium" target="_blank">Learn about Premium features</a>
					</div>
				</div>
			</div>
			<a href="<?php echo admin_url( 'tools.php?page=og-settings&disconnect=' . wp_create_nonce( 'conv_disconnect' ) ) ?>" class="conv-connect-disconnect">Disconnect site</a>
		</div>
		<?php
	}
}
