<?php
/**
 * EverestForms Admin
 *
 * @class    EVF_Admin
 * @author   WPEverest
 * @category Admin
 * @package  EverestForms/Admin
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * EVF_Admin class.
 */
class EVF_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'includes' ) );
		add_action( 'current_screen', array( $this, 'conditional_includes' ) );
		add_action( 'admin_init', array( $this, 'addon_actions' ) );
		add_action( 'admin_init', array( $this, 'admin_redirects' ) );
		add_action( 'admin_footer', 'evf_print_js', 25 );
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
	}

	/**
	 * Include any classes we need within admin.
	 */
	public function includes() {
		include_once dirname( __FILE__ ) . '/evf-admin-functions.php';
		include_once dirname( __FILE__ ) . '/class-evf-admin-post-types.php';
		include_once dirname( __FILE__ ) . '/class-evf-admin-menus.php';
		include_once dirname( __FILE__ ) . '/class-evf-admin-notices.php';
		include_once dirname( __FILE__ ) . '/class-evf-admin-assets.php';
		include_once dirname( __FILE__ ) . '/class-evf-admin-form-builder.php';
		include_once dirname( __FILE__ ) . '/class-evf-admin-add-form.php';
		include_once dirname( __FILE__ ) . '/class-evf-admin-entries.php';
	}

	/**
	 * Include admin files conditionally.
	 */
	public function conditional_includes() {
		if ( ! $screen = get_current_screen() ) {
			return;
		}

		switch ( $screen->id ) {
			case 'dashboard' :
				//include( 'class-evf-admin-dashboard.php' );
				break;
			case 'options-permalink' :
				//include( 'class-evf-admin-permalink-settings.php' );
				break;
			case 'plugins' :
				//include( 'plugin-updates/class-evf-plugins-screen-updates.php' );
				break;
			case 'update-core' :
				//include( 'plugin-updates/class-evf-updates-screen-updates.php' );
				break;
			case 'users' :
			case 'user' :
			case 'profile' :
			case 'user-edit' :
				//include( 'class-evf-admin-profile.php' );
				break;
		}
	}

	/**
	 * Handle redirects after addon activate/deactivate.
	 */
	public function addon_actions() {
		if ( isset( $_GET['page'], $_REQUEST['action'] ) && 'evf-addons' === $_GET['page'] ) {  // WPCS: input var okay, CSRF ok.
			$action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ); // WPCS: input var okay, CSRF ok.
			$plugin = isset( $_REQUEST['plugin'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) : false; // WPCS: input var okay, CSRF ok.

			if ( 'evf-addons-refresh' === $action ) {
				if ( empty( $_GET['evf-addons-nonce'] ) || ! wp_verify_nonce( $_GET['evf-addons-nonce'], 'refresh' ) ) {
					wp_die( esc_html_e( 'Could not verify nonce', 'everest-forms' ) );
				}

				foreach ( array( 'evf_pro_license_plan', 'evf_addons_sections', 'evf_extensions_section' ) as $transient ) {
					delete_transient( $transient );
				}
			}

			if ( $plugin && in_array( $action, array( 'install', 'activate', 'deactivate' ), true ) ) {
				if ( 'install' === $action ) {
					if ( ! current_user_can( 'install_plugins' ) ) {
						wp_die( esc_html__( 'Sorry, you are not allowed to install plugins for this site.', 'everest-forms' ) );
					}

					check_admin_referer( 'install-plugin_' . $plugin );

					if ( ! empty( $_REQUEST['name'] ) ) {
						require_once ABSPATH . 'wp-admin/includes/file.php';
						require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
						require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
						require_once ABSPATH . 'wp-admin/includes/plugin.php';

						WP_Filesystem();

						$skin        = new Automatic_Upgrader_Skin();
						$upgrader    = new WP_Upgrader( $skin );
						$plugin_name = sanitize_text_field( wp_unslash( $_REQUEST['name'] ) ); // WPCS: input var okay, CSRF ok.

						// Install this thing!
						$package  = evf_get_addon_download_link( $plugin_name, $plugin, true );
						$download = $upgrader->download_package( $package );

						if ( is_wp_error( $download ) ) {
							wp_die( esc_html__( 'Sorry, you are not allowed to download plugins for this site.', 'everest-forms' ) );
						}

						$working_dir = $upgrader->unpack_package( $download, true );

						if ( is_wp_error( $working_dir ) ) {
							wp_die( esc_html__( 'Sorry, you are not allowed to download plugins for this site.', 'everest-forms' ) );
						}

						$result = $upgrader->install_package(
							array(
								'source'                      => $working_dir,
								'destination'                 => WP_PLUGIN_DIR,
								'clear_destination'           => false,
								'abort_if_destination_exists' => false,
								'clear_working'               => true,
								'hook_extra'                  => array(
									'type'   => 'plugin',
									'action' => 'install',
								),
							)
						);

						if ( is_wp_error( $result ) ) {
							wp_die( esc_html__( 'Sorry, you are not allowed to download plugins for this site.', 'everest-forms' ) );
						}
					}
				} elseif ( 'activate' === $action ) {
					if ( ! current_user_can( 'activate_plugin', $plugin ) ) {
						wp_die( esc_html__( 'Sorry, you are not allowed to activate this plugin.', 'everest-forms' ) );
					}

					check_admin_referer( 'activate-plugin_' . $plugin );

					activate_plugin( $plugin );
				} elseif ( 'deactivate' === $action ) {
					if ( ! current_user_can( 'deactivate_plugins' ) ) {
						wp_die( esc_html__( 'Sorry, you are not allowed to deactivate plugins for this site.', 'everest-forms' ) );
					}

					check_admin_referer( 'deactivate-plugin_' . $plugin );

					deactivate_plugins( $plugin );
				}
			}

			// Redirect to the add-ons page.
			wp_safe_redirect( admin_url( 'admin.php?page=evf-addons' ) );
			exit;
		}
	}

	/**
	 * Handle redirects to setup/welcome page after install and updates.
	 *
	 * For setup wizard, transient must be present, the user must have access rights, and we must ignore the network/bulk plugin updaters.
	 */
	public function admin_redirects() {
		// Nonced plugin install redirects (whitelisted)
		if ( ! empty( $_GET['evf-install-plugin-redirect'] ) ) {
			$plugin_slug = evf_clean( $_GET['evf-install-plugin-redirect'] );

			$url = admin_url( 'plugin-install.php?tab=search&type=term&s=' . $plugin_slug );
			wp_safe_redirect( $url );
			exit;
		}

		// Setup wizard redirect
		if ( get_transient( '_evf_activation_redirect' ) ) {
			delete_transient( '_evf_activation_redirect' );

			if ( ( ! empty( $_GET['page'] ) && in_array( $_GET['page'], array( 'evf-settings' ) ) ) || is_network_admin() || isset( $_GET['activate-multi'] ) || ! current_user_can( 'manage_everest_forms' ) || apply_filters( 'everest_forms_prevent_automatic_wizard_redirect', false ) ) {
				return;
			}

			// If the user needs to install, send them to the setup wizard
			if ( EVF_Admin_Notices::has_notice( 'install' ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=evf-settings' ) );
				exit;
			}
		}
	}

	/**
	 * Change the admin footer text on EverestForms admin pages.
	 *
	 * @since      1.0.0
	 *
	 * @param  string $footer_text
	 *
	 * @return string
	 */
	public function admin_footer_text( $footer_text ) {
		if ( ! current_user_can( 'manage_everest_forms' ) || ! function_exists( 'evf_get_screen_ids' ) ) {
			return $footer_text;
		}
		$current_screen = get_current_screen();
		$evf_pages      = evf_get_screen_ids();

		// Check to make sure we're on a EverestForms admin page.
		if ( isset( $current_screen->id ) && apply_filters( 'everest_forms_display_admin_footer_text', in_array( $current_screen->id, $evf_pages ) ) ) {
			// Change the footer text
			if ( ! get_option( 'everest_forms_admin_footer_text_rated' ) ) {
				$footer_text = sprintf(
				/* translators: 1: EverestForms 2:: five stars */
					__( 'If you like %1$s please leave us a %2$s rating. A huge thanks in advance!', 'everest-forms' ),
					sprintf( '<strong>%s</strong>', esc_html__( 'Everest Forms', 'everest-forms' ) ),
					'<a href="https://wordpress.org/support/plugin/everest-forms/reviews?rate=5#new-post" target="_blank" class="evf-rating-link" data-rated="' . esc_attr__( 'Thanks :)', 'everest-forms' ) . '">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
				);
				evf_enqueue_js( "
					jQuery( 'a.evf-rating-link' ).click( function() {
						jQuery.post( '" . EVF()->ajax_url() . "', { action: 'everest_forms_rated' } );
						jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
					});
				" );
			} else {
				$footer_text = __( 'Thank you for selling with EverestForms.', 'everest-forms' );
			}
		}

		return $footer_text;
	}

	/**
	 * Check on a Jetpack install queued by the Setup Wizard.
	 *
	 * See: EVF_Admin_Setup_Wizard::install_jetpack()
	 */
	public function setup_wizard_check_jetpack() {
		$jetpack_active = class_exists( 'Jetpack' );

		wp_send_json_success( array(
			'is_active' => $jetpack_active ? 'yes' : 'no',
		) );
	}

}

return new EVF_Admin();
