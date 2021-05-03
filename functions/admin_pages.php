<?php defined( 'ABSPATH' ) or die( 'This script cannot be accessed directly.' );

add_action( 'admin_enqueue_scripts', 'conv_admin_enqueue_scripts' );
function conv_admin_enqueue_scripts( $hook ) {
	if ( $hook !== 'tools_page_conv-settings' ) {
		return;
	}

	global $conv_uri, $conv_version;
	wp_enqueue_style( 'conv-main', $conv_uri . '/css/main.css', array(), $conv_version );
	wp_enqueue_script( 'conv-main', $conv_uri . '/js/main.js', array('jquery'), $conv_version );
}

add_action( 'activated_plugin', 'conv_activated_plugin' );
function conv_activated_plugin( $plugin ) {
	global $conv_file;
	if ( $plugin !== plugin_basename( $conv_file ) ) {
		return;
	}
	// Taking into account promotional links
	$ref_data = get_transient( 'conv-ref' );
	if ( $ref_data and strpos( $ref_data, '|' ) !== FALSE ) {
		$ref_data = explode( '|', $ref_data );
		// Preventing violations with lifetime values
		if ( time() - intval( $ref_data[1] ) < DAY_IN_SECONDS ) {
			update_option( 'conv_ref', $ref_data[0], FALSE );
		}
		delete_transient( 'conv-ref' );
	}
	$owner_id = get_option( 'conv_owner_id' );
	if ( $owner_id === FALSE ) {
		$redirect_location = admin_url( 'tools.php?page=conv-settings' );
		if ( wp_doing_ajax() ) {
			wp_send_json_success( array( 'location' => $redirect_location ) );
		}
		wp_redirect( $redirect_location );
		exit;
	}
}


add_action( 'admin_menu', 'conv_add_admin_pages', 30 );
function conv_add_admin_pages() {
	global $conv_config;
	add_submenu_page( 'tools.php', $conv_config['title'], $conv_config['title'], 'manage_options', 'conv-settings', 'conv_settings_page' );
}

function conv_handle_disconnect_click() {
	if ( isset( $_GET['disconnect'] ) and wp_verify_nonce( $_GET['disconnect'], 'conv_disconnect' ) ) {
		conv_uninstall();
		// Redirect
		echo '<script type="text/javascript">location.assign(\'' . admin_url( 'tools.php?page=conv-settings' ) . '\')</script>';
	}
}

function conv_settings_page() {
	global $conv_config;
	conv_handle_disconnect_click();
	$site_id = get_option( 'conv_site_id' );
	if ( $site_id === FALSE ) {
		nocache_headers();
		$connect_url = $conv_config['host'] . '/sites/authorize/WordPressPlugin/';
		if ( $ref_username = get_option( 'conv_ref' ) ) {
			$connect_url .= '?ref=' . $ref_username;
		}
		// Generating access token to use it to authenticate requests
		$access_token = wp_generate_password( 32, FALSE );
		update_option( 'conv_token', $access_token );
		?>
		<div class="conv-connect">
			<div class="conv-connect-logo">
				<img class="conv-connect-logo-img" src="<?php echo esc_attr( $conv_config['logo'] ) ?>" srcset="<?php echo esc_attr( $conv_config['logo@2x'] ) ?>" alt="<?php echo esc_attr( $conv_config['title'] ) ?>">
			</div>
			<div class="conv-connect-box">
				<h1 class="conv-connect-header">Connect Site to <?php echo esc_attr( $conv_config['title'] ) ?></h1>
				<form class="conv-connect-card" method="post" action="<?php echo esc_attr( $connect_url ) ?>">
					<div class="conv-connect-card-body">
						<p>Please create a <?php echo esc_attr( $conv_config['title'] ) ?> Account or connect to an existing Account.<br>
							This will allow you to <strong>grow email lists easily</strong> using our top-notch builder
							with unique features and amazing pre-built form templates!</p>
					</div>
					<div class="conv-connect-card-footer">
						<input type="hidden" name="domain" value="<?php echo esc_attr( preg_replace( '~^https?://~', '', get_site_url() ) ) ?>">

						<input type="hidden" name="credentials[index_page_url]" value="<?php echo esc_attr( get_home_url() ) ?>">
						<input type="hidden" name="credentials[ajax_url]" value="<?php echo esc_attr( get_home_url() . '/index.php?rest_route=/convertful/v2/' ) ?>">
						<input type="hidden" name="credentials[access_token]" value="<?php echo esc_attr( $access_token ) ?>">

						<button class="conv-btn action_connect">
							Connect to <?php echo esc_attr( $conv_config['title'] ) ?>
						</button>
					</div>
				</form>
			</div>
			<?php if ( isset( $conv_config['connect_help_url'] ) and ! empty( $conv_config['connect_help_url'] ) ): ?>
				<a href="<?php echo esc_attr( $conv_config['connect_help_url'] ) ?>" class="conv-connect-help" target="_blank">Get help connecting your site</a>
			<?php endif; ?>
		</div>
		<?php
	} else {
		?>
		<div class="conv-connect type_success">
			<div class="conv-connect-logo">
				<img class="conv-connect-logo-img" src="<?php echo esc_attr( $conv_config['logo'] ) ?>" srcset="<?php echo esc_attr( $conv_config['logo@2x'] ) ?> 2x" alt="<?php echo esc_attr( $conv_config['title'] ) ?>">
			</div>
			<div class="conv-connect-box">
				<h1 class="conv-connect-header">Site is Connected to <?php echo esc_attr( $conv_config['title'] ) ?></h1>
				<div class="conv-connect-card">
					<div class="conv-connect-card-body">
						<p>Congratulations! Your site is connected to <?php echo esc_attr( $conv_config['title'] ) ?>.</p>
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
