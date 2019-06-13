<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

add_action( 'admin_menu', 'conv_add_admin_pages', 30 );
function conv_add_admin_pages() {
	global $conv_config;
	add_submenu_page( 'tools.php', $conv_config['title'], $conv_config['title'], 'manage_options', 'conv-settings', 'conv_settings_page' );
}

function conv_handle_return_to_endpoint() {
	// Handling return to backend
	if ( isset( $_GET['owner_id'] ) AND isset( $_GET['site_id'] ) AND isset( $_GET['token'] ) AND isset( $_GET['_nonce'] ) AND wp_verify_nonce( $_GET['_nonce'], 'conv_connect' ) ) {
		update_option( 'conv_owner_id', (int) $_GET['owner_id'], TRUE );
		update_option( 'conv_site_id', (int) $_GET['site_id'], FALSE );
		update_option( 'conv_token', $_GET['token'], FALSE );
		// Redirect
		echo '<script type="text/javascript">location.assign(\'' . admin_url( 'tools.php?page=conv-settings' ) . '\')</script>';
	}
}

function conv_handle_disconnect_click() {
	if ( isset( $_GET['disconnect'] ) AND wp_verify_nonce( $_GET['disconnect'], 'conv_disconnect' ) ) {
		conv_uninstall();
		// Redirect
		echo '<script type="text/javascript">location.assign(\'' . admin_url( 'tools.php?page=conv-settings' ) . '\')</script>';
	}
}

function conv_settings_page() {
	global $conv_config;
	conv_handle_return_to_endpoint();
	conv_handle_disconnect_click();
	$site_id = get_option( 'conv_site_id' );
	if ( $site_id === FALSE ) {
		$connect_url = $conv_config['host'] . '/sites/authorize/WordPressPlugin/';
		if ( $ref_username = get_option( 'conv_ref' ) ) {
			$connect_url .= '?ref=' . $ref_username;
		}
		// Generating access token to use it to authenticate requests
		$access_token = wp_generate_password( 32, false );
		update_option( 'conv_token', $access_token, FALSE );
		?>
		<div class="conv-connect">
			<div class="conv-connect-logo">
				<img class="conv-connect-logo-img" src="<?php echo esc_attr($conv_config['logo']) ?>" srcset="<?php echo esc_attr($conv_config['logo@2x']) ?>" alt="<?php echo esc_attr($conv_config['title'])?>">
			</div>
			<div class="conv-connect-box">
				<h1 class="conv-connect-header">Connect Site to <?php echo esc_attr($conv_config['title']) ?></h1>
				<form class="conv-connect-card" method="post" action="<?php echo esc_attr( $connect_url ) ?>">
					<div class="conv-connect-card-body">
						<p>Please create a <?php echo esc_attr($conv_config['title']) ?> Account or connect to an existing Account.<br>
							This will allow you to <strong>grow email lists easily</strong> using our top-notch builder
							with unique features and amazing pre-built form templates!</p>
					</div>
					<div class="conv-connect-card-footer">
						<input type="hidden" name="domain" value="<?php echo esc_attr( preg_replace( '~^https?:\/\/~', '', get_site_url() ) ) ?>">

						<input type="hidden" name="credentials[index_page_url]" value="<?php echo esc_attr( get_home_url() ) ?>">
						<input type="hidden" name="credentials[ajax_url]" value="<?php echo esc_attr( admin_url('admin-ajax.php') ) ?>">
						<input type="hidden" name="credentials[access_token]" value="<?php echo esc_attr( $access_token ) ?>">

						<button class="conv-btn action_connect">
							Connect to <?php echo esc_attr($conv_config['title']) ?>
						</button>
					</div>
				</form>
			</div>
			<a href="<?php echo esc_attr($conv_config['host']) ?>/docs/connect/wordpress/" class="conv-connect-help" target="_blank">Get help connecting your site</a>
		</div>
		<?php
	} else {
		?>
		<div class="conv-connect type_success">
			<div class="conv-connect-logo">
				<img class="conv-connect-logo-img" src="<?php echo esc_attr($conv_config['logo']) ?>" srcset="<?php echo esc_attr($conv_config['logo@2x']) ?> 2x" alt="<?php echo esc_attr($conv_config['title']) ?>">
			</div>
			<div class="conv-connect-box">
				<h1 class="conv-connect-header">Site is Connected to <?php echo esc_attr($conv_config['title']) ?></h1>
				<div class="conv-connect-card">
					<div class="conv-connect-card-body">
						<p>Congratulations! Your site is connected to <?php echo esc_attr($conv_config['title']) ?>.</p>
						<p>Now you can <strong>grow email lists easily</strong> using our top-notch builder
							with unique features and amazing pre-built form templates!</p>
					</div>
					<div class="conv-connect-card-footer">
						<a class="conv-btn action_create" href="<?php echo esc_attr( $conv_config['host'] . '/sites/' . $site_id . '/widgets/create/' ) ?>" target="_blank">
							Create New Optin
						</a>
					</div>
				</div>
			</div>
			<a href="<?php echo admin_url( 'tools.php?page=conv-settings&disconnect=' . wp_create_nonce( 'conv_disconnect' ) ) ?>" class="conv-connect-disconnect">Disconnect site</a>
		</div>
		<?php
	}
}
