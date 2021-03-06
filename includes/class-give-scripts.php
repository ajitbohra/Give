<?php

/**
 * Loads the plugin's scripts and styles.
 *
 * Registers and enqueues plugin styles and scripts. Asset versions are based
 * on the current plugin version.
 *
 * All script and style handles should be registered in this class even if they
 * are enqueued dynamically by other classes.
 *
 * @since 2.1.0
 */
class Give_Scripts {

	/**
	 * Whether RTL or not.
	 *
	 * @since  2.1.0
	 * @var    string
	 * @access private
	 */
	private $direction;

	/**
	 * Whether scripts should be loaded in the footer or not.
	 *
	 * @since  2.1.0
	 * @var    bool
	 * @access private
	 */
	private $scripts_footer;

	/**
	 * Instantiates the Assets class.
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		$this->direction      = ( is_rtl() || isset( $_GET['d'] ) && 'rtl' === $_GET['d'] ) ? '.rtl' : '';
		$this->scripts_footer = give_is_setting_enabled( give_get_option( 'scripts_footer' ) ) ? true : false;
		$this->init();
	}

	/**
	 * Fires off hooks to register assets in WordPress.
	 *
	 * @since 2.1.0
	 */
	public function init() {

		add_action( 'admin_enqueue_scripts', array( $this, 'register_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );

		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ) );
			add_action( 'enqueue_block_editor_assets', array( $this, 'gutenberg_admin_scripts' ) );
			add_action( 'admin_head', array( $this, 'global_admin_head' ) );

		} else {
			add_action( 'wp_enqueue_scripts', array( $this, 'public_enqueue_styles' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'public_enqueue_scripts' ) );
		}
	}

	/**
	 * Registers all plugin styles.
	 *
	 * @since 2.1.0
	 */
	public function register_styles() {

		// WP-admin.
		wp_register_style( 'give-admin-styles', GIVE_PLUGIN_URL . 'assets/dist/css/admin' . $this->direction . '.css', array(), GIVE_VERSION );

		// WP-admin: plugin page.
		wp_register_style(
			'plugin-deactivation-survey-css',
			GIVE_PLUGIN_URL . 'assets/dist/css/plugin-deactivation-survey.css',
			array(),
			GIVE_VERSION
		);

		// Frontend.
		if ( give_is_setting_enabled( give_get_option( 'css' ) ) ) {
			wp_register_style( 'give-styles', $this->get_frontend_stylesheet_uri(), array(), GIVE_VERSION, 'all' );
		}
	}

	/**
	 * Registers all plugin scripts.
	 *
	 * @since 2.1.0
	 */
	public function register_scripts() {

		// WP-Admin.
		wp_register_script( 'give-admin-scripts', GIVE_PLUGIN_URL . 'assets/dist/js/admin.js', array(
			'jquery',
			'jquery-ui-datepicker',
			'wp-color-picker',
			'jquery-query',
		), GIVE_VERSION );

		// WP-admin: plugin page.
		wp_register_script( 'plugin-deactivation-survey-js',
			GIVE_PLUGIN_URL . 'assets/dist/js/plugin-deactivation-survey.js',
			array( 'jquery' ),
			GIVE_VERSION,
			true
		);

		// Frontend.
		wp_register_script( 'give', GIVE_PLUGIN_URL . 'assets/dist/js/give.js', array( 'jquery' ), GIVE_VERSION, $this->scripts_footer );
	}

	/**
	 * Enqueues admin styles.
	 *
	 * @since 2.1.0
	 *
	 * @param string $hook Page hook.
	 */
	public function admin_enqueue_styles( $hook ) {
		// Give Admin Only.
		if ( ! apply_filters( 'give_load_admin_styles', give_is_admin_page(), $hook ) ) {
			return;
		}

		// Give enqueues.
		wp_enqueue_style( 'give-admin-styles' );
		wp_enqueue_style( 'give-admin-bar-notification' );

		// WP Core enqueues.
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'thickbox' ); // @TODO remove once we have modal API.

	}

	/**
	 * Enqueues admin scripts.
	 *
	 * @since 2.1.0
	 *
	 * @param string $hook Page hook.
	 */
	public function admin_enqueue_scripts( $hook ) {
		global $pagenow;

		// Plugin page script
		if ( 'plugins.php' === $pagenow ) {
			$this->plugin_equeue_scripts();
		}

		// Give Admin Only.
		if ( ! apply_filters( 'give_load_admin_scripts', give_is_admin_page(), $hook ) ) {
			return;
		}

		// WP Scripts.
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_media();

		// Give admin scripts.
		wp_enqueue_script( 'give-admin-scripts' );

		// Localize admin scripts
		$this->admin_localize_scripts();
	}

	/**
	 * Load admin plugin page related scripts, styles andd localize param
	 *
	 * @since  2.2.0
	 * @access private
	 */
	private function plugin_equeue_scripts() {
		wp_enqueue_style( 'plugin-deactivation-survey-css' );
		wp_enqueue_script( 'plugin-deactivation-survey-js' );

		$localized_data = array(
			'nonce'                           => wp_create_nonce( 'deactivation_survey_nonce' ),
			'cancel'                          => __( 'Cancel', 'give' ),
			'deactivation_no_option_selected' => __( 'Error: Please select at least one option.', 'give' ),
			'submit_and_deactivate'           => __( 'Submit and Deactivate', 'give' ),
			'skip_and_deactivate'             => __( 'Skip & Deactivate', 'give' ),
			'please_fill_field'               => __( 'Error: Please fill the field.', 'give' ),

		);

		wp_localize_script( 'plugin-deactivation-survey-js', 'give_vars', $localized_data );
	}

	/**
	 * Localize admin scripts.
	 */
	public function admin_localize_scripts() {

		global $post, $pagenow;
		$give_options = give_get_settings();

		// Price Separators.
		$thousand_separator = give_get_price_thousand_separator();
		$decimal_separator  = give_get_price_decimal_separator();
		$number_decimals    = give_get_price_decimals();

		// Localize strings & variables for JS.
		$localized_data = array(
			'post_id'                           => isset( $post->ID ) ? $post->ID : null,
			'give_version'                      => GIVE_VERSION,
			'thousands_separator'               => $thousand_separator,
			'decimal_separator'                 => $decimal_separator,
			'number_decimals'                   => $number_decimals,
			'quick_edit_warning'                => __( 'Not available for variable priced forms.', 'give' ),
			'delete_payment'                    => __( 'Are you sure you want to <strong>permanently</strong> delete this donation?', 'give' ),
			'delete_payment_note'               => __( 'Are you sure you want to delete this note?', 'give' ),
			'revoke_api_key'                    => __( 'Are you sure you want to revoke this API key?', 'give' ),
			'regenerate_api_key'                => __( 'Are you sure you want to regenerate this API key?', 'give' ),
			'resend_receipt'                    => __( 'Are you sure you want to resend the donation receipt?', 'give' ),
			'disconnect_user'                   => __( 'Are you sure you want to disconnect the user from this donor?', 'give' ),
			'one_option'                        => __( 'Choose a form', 'give' ),
			'one_or_more_option'                => __( 'Choose one or more forms', 'give' ),
			'currency_sign'                     => give_currency_filter( '' ),
			'currency_pos'                      => isset( $give_options['currency_position'] ) ? $give_options['currency_position'] : 'before',
			'currency_decimals'                 => give_get_price_decimals(),
			'ok'                                => __( 'Ok', 'give' ),
			'cancel'                            => __( 'Cancel', 'give' ),
			'success'                           => __( 'Success', 'give' ),
			'error'                             => __( 'Error', 'give' ),
			'close'                             => __( 'Close', 'give' ),
			'confirm'                           => __( 'Confirm', 'give' ),
			'copied'                            => __( 'Copied!', 'give' ),
			'shortcode_not_copy'                => __( 'Shortcode could not be copied.', 'give' ),
			'confirm_action'                    => __( 'Confirm Action', 'give' ),
			'confirm_deletion'                  => __( 'Confirm Deletion', 'give' ),
			'confirm_delete_donation'           => __( 'Confirm Delete Donation', 'give' ),
			'confirm_resend'                    => __( 'Confirm re-send', 'give' ),
			'confirm_bulk_action'               => __( 'Confirm bulk action', 'give' ),
			'restart_upgrade'                   => __( 'Do you want to restart the update process?', 'give' ),
			'restart_update'                    => __( 'It is recommended that you backup your database before proceeding. Do you want to run the update now?', 'give' ),
			'stop_upgrade'                      => __( 'Do you want to stop the update process now?', 'give' ),
			'import_failed'                     => __( 'Import failed', 'give' ),
			'flush_success'                     => __( 'Flush success', 'give' ),
			'flush_error'                       => __( 'Flush error', 'give' ),
			'no_form_selected'                  => __( 'No form selected', 'give' ),
			'batch_export_no_class'             => __( 'You must choose a method.', 'give' ),
			'batch_export_no_reqs'              => __( 'Required fields not completed.', 'give' ),
			'reset_stats_warn'                  => __( 'Are you sure you want to reset Give? This process is <strong><em>not reversible</em></strong> and will delete all data regardless of test or live mode. Please be sure you have a recent backup before proceeding.', 'give' ),
			'delete_test_donor'                 => __( 'Are you sure you want to delete all the test donors? This process will also delete test donations as well.', 'give' ),
			'delete_import_donor'               => __( 'Are you sure you want to delete all the imported donors? This process will also delete imported donations as well.', 'give' ),
			'delete_donations_only'             => __( 'Are you sure you want to delete all the donations in the specfied date range?', 'give' ),
			'price_format_guide'                => sprintf( __( 'Please enter amount in monetary decimal ( %1$s ) format without thousand separator ( %2$s ) .', 'give' ), $decimal_separator, $thousand_separator ),
			/* translators : %s: Donation form options metabox */
			'confirm_before_remove_row_text'    => __( 'Do you want to delete this item?', 'give' ),
			'matched_success_failure_page'      => __( 'You cannot set the success and failed pages to the same page', 'give' ),
			'dismiss_notice_text'               => __( 'Dismiss this notice.', 'give' ),
			'search_placeholder'                => __( 'Type to search all forms', 'give' ),
			'search_placeholder_donor'          => __( 'Type to search all donors', 'give' ),
			'search_placeholder_country'        => __( 'Type to search all countries', 'give' ),
			'search_placeholder_state'          => __( 'Type to search all states/provinces', 'give' ),
			'unlock_donor_fields_title'         => __( 'Action forbidden', 'give' ),
			'unlock_donor_fields_message'       => __( 'To edit first name and last name, please go to user profile of the donor.', 'give' ),
			'remove_from_bulk_delete'           => __( 'Remove from Bulk Delete', 'give' ),
			'donors_bulk_action'                => array(
				'no_donor_selected'  => array(
					'title' => __( 'No donors selected', 'give' ),
					'desc'  => __( 'You must choose at least one or more donors to delete.', 'give' ),
				),
				'no_action_selected' => array(
					'title' => __( 'No action selected', 'give' ),
					'desc'  => __( 'You must select a bulk action to proceed.', 'give' ),
				),
			),
			'donations_bulk_action'             => array(
				'titles'         => array(
					'zero' => __( 'No payments selected', 'give' ),
				),
				'delete'         => array(
					'zero'     => __( 'You must choose at least one or more donations to delete.', 'give' ),
					'single'   => __( 'Are you sure you want to permanently delete this donation?', 'give' ),
					'multiple' => __( 'Are you sure you want to permanently delete the selected {payment_count} donations?', 'give' ),
				),
				'resend-receipt' => array(
					'zero'     => __( 'You must choose at least one or more recipients to resend the email receipt.', 'give' ),
					'single'   => __( 'Are you sure you want to resend the email receipt to this recipient?', 'give' ),
					'multiple' => __( 'Are you sure you want to resend the emails receipt to {payment_count} recipients?', 'give' ),
				),
				'set-to-status'  => array(
					'zero'     => __( 'You must choose at least one or more donations to set status to {status}.', 'give' ),
					'single'   => __( 'Are you sure you want to set status of this donation to {status}?', 'give' ),
					'multiple' => __( 'Are you sure you want to set status of {payment_count} donations to {status}?', 'give' ),
				),
			),
			'updates'                           => array(
				'ajax_error' => __( 'Please reload this page and try again', 'give' ),
			),
			'metabox_fields'                    => array(
				'media' => array(
					'button_title' => __( 'Choose Image', 'give' ),
				),
				'file'  => array(
					'button_title' => __( 'Choose File', 'give' ),
				),
			),
			'chosen'                            => array(
				'no_results_msg'  => __( 'No results match {search_term}', 'give' ),
				'ajax_search_msg' => __( 'Searching results for match {search_term}', 'give' ),
			),
			'db_update_confirmation_msg_button' => __( 'Run Updates', 'give' ),
			'db_update_confirmation_msg'        => __( 'The following process will make updates to your site\'s database. Please create a database backup before proceeding with updates.', 'give' ),
			'error_message'                     => __( 'Something went wrong kindly try again!', 'give' ),
			'give_donation_import'              => 'give_donation_import',
			'core_settings_import'              => 'give_core_settings_import',
			'setting_not_save_message'          => __( 'Changes you made may not be saved.', 'give' ),
			'give_donation_amounts'             => array(
				'minimum' => apply_filters( 'give_donation_minimum_limit', 1 ),
				'maximum' => apply_filters( 'give_donation_maximum_limit', 999999.99 ),
			),
			'chosen_add_title_prefix'           => __( 'No result found. Press enter to add', 'give' ),
			'db_update_nonce'                   => wp_create_nonce( Give_Updates::$background_updater->get_identifier() ),
			'ajax'                              => give_test_ajax_works(),
			'date_format'                       => give_get_localized_date_format_to_js(),
			'donor_note_confirm_msg'            => __( 'You are adding a donor note , so an email notification will be send to donor. If you do not want to send email notification to donor then either create private note or disable donor note email.', 'give' ),
			'email_notification'            => array(
				'donor_note' => array(
					'status' => Give_Email_Notification_Util::is_email_notification_active( Give_Email_Notification::get_instance('donor-note' ) )
				)
			),
		);

		wp_localize_script( 'give-admin-scripts', 'give_vars', $localized_data );
	}

	/**
	 * Global admin head.
	 */
	public function global_admin_head() {
		?>
		<style type="text/css" media="screen">
			@font-face {
				font-family: 'give-icomoon';
				src: url('<?php echo GIVE_PLUGIN_URL . 'assets/dist/fonts/icomoon.eot?ngjl88'; ?>');
				src: url('<?php echo GIVE_PLUGIN_URL . 'assets/dist/fonts/icomoon.eot?#iefixngjl88'?>') format('embedded-opentype'),
				url('<?php echo GIVE_PLUGIN_URL . 'assets/dist/fonts/icomoon.woff?ngjl88'; ?>') format('woff'),
				url('<?php echo GIVE_PLUGIN_URL . 'assets/dist/fonts/icomoon.svg?ngjl88#icomoon'; ?>') format('svg');
				font-weight: normal;
				font-style: normal;
			}

			.dashicons-give:before, #adminmenu div.wp-menu-image.dashicons-give:before {
				font-family: 'give-icomoon';
				font-size: 18px;
				width: 18px;
				height: 18px;
				content: "\e800";
			}
		</style>
		<?php

	}

	/**
	 * Enqueues public styles.
	 *
	 * @since 2.1.0
	 */
	public function public_enqueue_styles() {
		wp_enqueue_style( 'give-styles' );
	}


	/**
	 * Enqueues public scripts.
	 *
	 * @since 2.1.0
	 */
	public function public_enqueue_scripts() {

		// Call Babel Polyfill with common handle so that it is compatible with plugins and themes.
		if ( ! wp_script_is( 'babel-polyfill', 'enqueued' ) ) {
			wp_enqueue_script(
				'babel-polyfill',
				GIVE_PLUGIN_URL . 'assets/dist/js/babel-polyfill.js',
				array( 'jquery' ),
				GIVE_VERSION,
				false
			);
		}

		wp_enqueue_script( 'give' );

		$this->public_localize_scripts();
	}

	/**
	 * Localize / PHP to AJAX vars.
	 */
	public function public_localize_scripts() {

		/**
		 * Filter to modify access mail send notice
		 *
		 * @since 2.1.3
		 *
		 * @param string Send notice message for email access.
		 *
		 * @return  string $message Send notice message for email access.
		 */
		$message = (string) apply_filters( 'give_email_access_mail_send_notice', __( 'Please check your email and click on the link to access your complete donation history.', 'give' ) );

		$localize_give_vars = apply_filters( 'give_global_script_vars', array(
			'ajaxurl'                     => give_get_ajax_url(),
			'checkout_nonce'              => wp_create_nonce( 'give_checkout_nonce' ),
			// Do not use this nonce. Its deprecated.
			'currency'                    => give_get_currency(),
			'currency_sign'               => give_currency_filter( '' ),
			'currency_pos'                => give_get_currency_position(),
			'thousands_separator'         => give_get_price_thousand_separator(),
			'decimal_separator'           => give_get_price_decimal_separator(),
			'no_gateway'                  => __( 'Please select a payment method.', 'give' ),
			'bad_minimum'                 => __( 'The minimum custom donation amount for this form is', 'give' ),
			'bad_maximum'                 => __( 'The maximum custom donation amount for this form is', 'give' ),
			'general_loading'             => __( 'Loading...', 'give' ),
			'purchase_loading'            => __( 'Please Wait...', 'give' ),
			'number_decimals'             => give_get_price_decimals(),
			'give_version'                => GIVE_VERSION,
			'magnific_options'            => apply_filters(
				'give_magnific_options',
				array(
					'main_class'        => 'give-modal',
					'close_on_bg_click' => false,
				)
			),
			'form_translation'            => apply_filters(
				'give_form_translation_js',
				array(
					// Field name               Validation message.
					'payment-mode'           => __( 'Please select payment mode.', 'give' ),
					'give_first'             => __( 'Please enter your first name.', 'give' ),
					'give_email'             => __( 'Please enter a valid email address.', 'give' ),
					'give_user_login'        => __( 'Invalid username. Only lowercase letters (a-z) and numbers are allowed.', 'give' ),
					'give_user_pass'         => __( 'Enter a password.', 'give' ),
					'give_user_pass_confirm' => __( 'Enter the password confirmation.', 'give' ),
					'give_agree_to_terms'    => __( 'You must agree to the terms and conditions.', 'give' ),
				)
			),
			'confirm_email_sent_message'  => $message,
			'ajax_vars'                   => apply_filters( 'give_global_ajax_vars', array(
				'ajaxurl'         => give_get_ajax_url(),
				'ajaxNonce'       => wp_create_nonce( 'give_ajax_nonce' ),
				'loading'         => __( 'Loading', 'give' ),
				// General loading message.
				'select_option'   => __( 'Please select an option', 'give' ),
				// Variable pricing error with multi-donation option enabled.
				'default_gateway' => give_get_default_gateway( null ),
				'permalinks'      => get_option( 'permalink_structure' ) ? '1' : '0',
				'number_decimals' => give_get_price_decimals(),
			) ),
			'cookie_hash'                 => COOKIEHASH,
			'delete_session_nonce_cookie' => absint( Give()->session->is_delete_nonce_cookie() )
		) );

		wp_localize_script( 'give', 'give_global_vars', $localize_give_vars );
	}

	/**
	 * Get the stylesheet URI.
	 *
	 * @since   1.6
	 * @updated 2.0.1 Moved to class and renamed as method.
	 *
	 * @return string
	 */
	public function get_frontend_stylesheet_uri() {

		$file          = 'give' . $this->direction . '.css';
		$templates_dir = give_get_theme_template_dir_name();

		// Directory paths to CSS files to support checking via file_exists().
		$child_theme_style_sheet    = trailingslashit( get_stylesheet_directory() ) . $templates_dir . $file;
		$child_theme_style_sheet_2  = trailingslashit( get_stylesheet_directory() ) . $templates_dir . 'give' . $this->direction . '.css';
		$parent_theme_style_sheet   = trailingslashit( get_template_directory() ) . $templates_dir . $file;
		$parent_theme_style_sheet_2 = trailingslashit( get_template_directory() ) . $templates_dir . 'give' . $this->direction . '.css';
		$give_plugin_style_sheet    = trailingslashit( GIVE_PLUGIN_DIR ) . 'assets/dist/css/' . $file;
		$uri                        = false;

		/**
		 * Locate the Give stylesheet:
		 *
		 * a. Look in the child theme directory first, followed by the parent theme
		 * b. followed by the Give core templates directory also look for the min version first,
		 * c. followed by non minified version, even if SCRIPT_DEBUG is not enabled. This allows users to copy just give.css to their theme.
		 * d. Finally, fallback to the standard Give version. This is the default styles included within the plugin.
		 */
		if ( file_exists( $child_theme_style_sheet ) || ( ! empty( $suffix ) && ( $nonmin = file_exists( $child_theme_style_sheet_2 ) ) ) ) {
			if ( ! empty( $nonmin ) ) {
				$uri = trailingslashit( get_stylesheet_directory_uri() ) . $templates_dir . 'give' . $this->direction . '.css';
			} else {
				$uri = trailingslashit( get_stylesheet_directory_uri() ) . $templates_dir . $file;
			}
		} elseif ( file_exists( $parent_theme_style_sheet ) || ( ! empty( $suffix ) && ( $nonmin = file_exists( $parent_theme_style_sheet_2 ) ) ) ) {
			if ( ! empty( $nonmin ) ) {
				$uri = trailingslashit( get_template_directory_uri() ) . $templates_dir . 'give' . $this->direction . '.css';
			} else {
				$uri = trailingslashit( get_template_directory_uri() ) . $templates_dir . $file;
			}
		} elseif ( file_exists( $give_plugin_style_sheet ) ) {
			$uri = trailingslashit( GIVE_PLUGIN_URL ) . 'assets/dist/css/' . $file;
		}

		return apply_filters( 'give_get_stylesheet_uri', $uri );

	}

	/**
	 * Gutenberg admin scripts.
	 */
	public function gutenberg_admin_scripts() {

		// Enqueue the bundled block JS file
		wp_enqueue_script(
			'give-blocks-js',
			GIVE_PLUGIN_URL . 'assets/dist/js/gutenberg.js',
			array( 'wp-i18n', 'wp-element', 'wp-blocks', 'wp-components', 'wp-api' ),
			GIVE_VERSION
		);

		// Enqueue public styles
		wp_enqueue_style( 'give-styles' );

		// Enqueue the bundled block css file
		wp_enqueue_style(
			'give-blocks-css',
			GIVE_PLUGIN_URL . 'assets/dist/css/gutenberg.css',
			array( ),
			GIVE_VERSION
		);

	}

}
