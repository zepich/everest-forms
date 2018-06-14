<?php
/**
 * Load assets
 *
 * @package EverestForms/Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVF_Admin_Assets', false ) ) {
	return new EVF_Admin_Assets();
}

/**
 * EVF_Admin_Assets Class.
 */
class EVF_Admin_Assets {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
	}

	/**
	 * Enqueue styles.
	 */
	public function admin_styles() {
		global $wp_scripts;

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// Register admin styles.
		wp_register_style( 'everest-forms-admin', EVF()->plugin_url() . '/assets/css/admin.css', array(), EVF_VERSION );
		wp_register_style( 'everest-forms-admin-menu', EVF()->plugin_url() . '/assets/css/menu.css', array(), EVF_VERSION );
		wp_register_style( 'jquery-ui-style', EVF()->plugin_url() . '/assets/css/jquery-ui/jquery-ui.min.css', array(), EVF_VERSION );
		wp_register_style( 'jquery-confirm', EVF()->plugin_url() . '/assets/css/jquery-confirm/jquery-confirm.min.css', array(), '3.3.0' );
		wp_register_style( 'everest-forms-admin-builder', EVF()->plugin_url() . '/assets/css/everest-builder.css', array(), EVF_VERSION );

		// Add RTL support for admin styles.
		wp_style_add_data( 'everest-forms-admin', 'rtl', 'replace' );
		wp_style_add_data( 'everest-forms-admin-menu', 'rtl', 'replace' );
		wp_style_add_data( 'everest-forms-admin-builder', 'rtl', 'replace' );

		// Sitewide menu CSS.
		wp_enqueue_style( 'everest-forms-admin-menu' );

		// Admin styles for EVF pages only.
		if ( in_array( $screen_id, evf_get_screen_ids(), true ) ) {
			wp_enqueue_style( 'everest-forms-admin' );
			wp_enqueue_style( 'jquery-confirm' );
			wp_enqueue_style( 'jquery-ui-style' );
			wp_enqueue_style( 'wp-color-picker' );
		}

		if ( in_array( $screen_id, array( 'everest-forms_page_edit-evf-add-form', 'everest-forms_page_edit-evf-form' ) ) ) {
			wp_enqueue_style( 'everest-forms-admin-builder' );
		}
	}

	/**
	 * Enqueue scripts.
	 */
	public function admin_scripts() {
		global $post;

		$screen        = get_current_screen();
		$screen_id     = $screen ? $screen->id : '';
		$evf_screen_id = sanitize_title( __( 'EverestForms', 'everest-forms' ) );
		$suffix        = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register scripts.
		wp_register_script( 'everest-forms-admin', EVF()->plugin_url() . '/assets/js/admin/everest-forms-admin' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'jquery-tiptip' ), EVF_VERSION );
		wp_register_script( 'jquery-blockui', EVF()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.70', true );
		wp_register_script( 'jquery-confirm', EVF()->plugin_url() . '/assets/js/jquery-confirm/jquery-confirm' . $suffix . '.js', array( 'jquery' ), '3.3.0', true );
		wp_register_script( 'jquery-tiptip', EVF()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), EVF_VERSION, true );
		wp_register_script( 'evf-clipboard', EVF()->plugin_url() . '/assets/js/admin/evf-clipboard' . $suffix . '.js', array( 'jquery' ), EVF_VERSION );
		wp_register_script( 'selectWoo', EVF()->plugin_url() . '/assets/js/selectWoo/selectWoo.full' . $suffix . '.js', array( 'jquery' ), '1.0.4' );

		wp_register_script( 'everest-add-form', EVF()->plugin_url() . '/assets/js/admin/evf-add-form' . $suffix . '.js', 'jquery' );
		wp_localize_script(
			'everest-add-form',
			'everest_add_form_params',
			array(
				'ajax_url'          => admin_url( 'admin-ajax.php' ),
				'create_form_nonce' => wp_create_nonce( 'everest_forms_create_form' ),
			)
		);

		wp_enqueue_script( 'evf-admin-form-modal', EVF()->plugin_url() . '/assets/js/admin/evf-form-modal.js', array( 'underscore', 'backbone', 'wp-util' ), EVF_VERSION );

		wp_register_script( 'evf-panel-builder', EVF()->plugin_url() . '/assets/js/admin/everest-panel-builder' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'jquery-tiptip', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'jquery-ui-tabs', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-confirm', 'evf-clipboard' ), EVF_VERSION );
		wp_localize_script( 'evf-panel-builder', 'evf_data', apply_filters(
			'everest_forms_builder_strings', array(
				'post_id'                      => isset( $post->ID ) ? $post->ID : '',
				'ajax_url'                     => admin_url( 'admin-ajax.php' ),
				'tab'                          => isset( $_GET['tab'] ) ? $_GET['tab'] : '',
				'evf_field_drop_nonce'         => wp_create_nonce( 'everest_forms_field_drop' ),
				'evf_save_form'                => wp_create_nonce( 'everest_forms_save_form' ),
				'evf_get_next_id'              => wp_create_nonce( 'everest_forms_get_next_id' ),
				'form_id'                      => isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0,
				'field'                        => esc_html__( 'field', 'everest-forms' ),
				'copy_of'                      => esc_html__( 'Copy of ', 'everest-forms' ),
				'i18n_ok'                      => esc_html__( 'OK', 'everest-forms' ),
				'i18n_close'                   => esc_html__( 'Close', 'everest-forms' ),
				'i18n_cancel'                  => esc_html__( 'Cancel', 'everest-forms' ),
				'i18n_row_locked'              => esc_html__( 'Row Locked', 'everest-forms' ),
				'i18n_row_locked_msg'          => esc_html__( 'Single row cannot be deleted.', 'everest-forms' ),
				'i18n_field_locked'            => esc_html__( 'Field Locked', 'everest-forms' ),
				'i18n_field_locked_msg'        => esc_html__( 'This field cannot be deleted or duplicated.', 'everest-forms' ),
				'i18n_field_error_choice'      => esc_html__( 'This item must contain at least one choice.', 'everest-forms' ),
				'i18n_delete_row_confirm'      => esc_html__( 'Are you sure you want to delete this row?', 'everest-forms' ),
				'i18n_delete_field_confirm'    => esc_html__( 'Are you sure you want to delete this field?', 'everest-forms' ),
				'i18n_duplicate_field_confirm' => esc_html__( 'Are you sure you want to duplicate this field?', 'everest-forms' ),
			)
		) );

		// Global JS.
		wp_enqueue_script( 'everest-add-form' );

		// EverestForms admin pages.
		if ( in_array( $screen_id, evf_get_screen_ids() ) ) {
			wp_enqueue_script( 'everest-forms-admin' );
		}

		// EverestForms builder pages.
		if ( in_array( $screen_id, array( 'everest-forms_page_edit-evf-add-form', 'everest-forms_page_edit-evf-form' ) ) ) {
			wp_enqueue_media();
			wp_enqueue_script( 'evf-panel-builder' );
			wp_enqueue_script( 'evf-admin-form-modal' );
		}
	}
}

return new EVF_Admin_Assets();
