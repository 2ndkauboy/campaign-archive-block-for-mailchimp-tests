<?php
/**
 * Class MailchimpApiCredentials
 *
 * @package CABFM\Settings
 */

namespace CABFM\Settings;

use CABFM\Helpers\MailchimpAPI;

/**
 * Class MailchimpApiCredentials
 */
class MailchimpApiCredentials {
	/**
	 * Initialize the class
	 */
	public function init() {
		add_action( 'admin_menu', [ $this, 'add_options_page' ] );
		add_action( 'admin_init', [ $this, 'add_settings' ] );
	}

	/**
	 * Add an options page for the settings
	 */
	public function add_options_page() {
		add_options_page(
			_x( 'Campaign Archive For Mailchimp', 'settings-page-title', 'campaign-archive-block-for-mailchimp' ),
			_x( 'Mailchimp Archive', 'settings-page-menu-name', 'campaign-archive-block-for-mailchimp' ),
			'manage_options',
			'cabfm',
			[ $this, 'options_page' ]
		);
	}

	/**
	 * Add the settings, sections and fields
	 */
	public function add_settings() {
		register_setting(
			'cabfm',
			'cabfm_server_prefix'
		);
		register_setting(
			'cabfm',
			'cabfm_api_key'
		);

		add_settings_section(
			'cabfm_api_credentials_section',
			null,
			null,
			'cabfm'
		);

		add_settings_field(
			'cabfm_server_prefix_settings_field',
			__( 'Server Prefix', 'campaign-archive-block-for-mailchimp' ),
			[ $this, 'settings_field' ],
			'cabfm',
			'cabfm_api_credentials_section',
			[
				'label_for' => 'cabfm_server_prefix',
			]
		);
		add_settings_field(
			'cabfm_api_key_settings_field',
			__( 'API key', 'campaign-archive-block-for-mailchimp' ),
			[ $this, 'settings_field' ],
			'cabfm',
			'cabfm_api_credentials_section',
			[
				'label_for' => 'cabfm_api_key',
			]
		);
	}

	/**
	 * Render the options page
	 */
	public function options_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		settings_errors( 'cabfm_messages' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<p><?php echo esc_html__( 'To use the Campaign Archive block on you site, you have to provide credentials for the Mailchimp API in the settings below.', 'campaign-archive-block-for-mailchimp' ); ?></p>
				<p>
					<?php
					echo wp_kses_post(
						sprintf(
						// Translators: %s: link tag to the mailchimp.com page for the API credentials.
							__( 'If you have not yet created your credentials, you can do so in the %s of your Mailchimp account.', 'campaign-archive-block-for-mailchimp' ),
							sprintf(
							// Translators: %1$s: url mailchimp.com page for the API credentials, %2$s: link text for this link.
								'<a href="%1$s">%2$s</a>',
								__( 'https://us1.admin.mailchimp.com/account/api/', 'campaign-archive-block-for-mailchimp' ),
								__( 'API keys section', 'campaign-archive-block-for-mailchimp' )
							)
						)
					);
					?>
				</p>
				<p><?php echo wp_kses_post( __( 'The <strong>Server Prefix</strong> is the subdomain you see before any Mailchimp URL for your account (e.g. <code>us19</code>).', 'campaign-archive-block-for-mailchimp' ) ); ?></p>
				<?php
				settings_fields( 'cabfm' );
				do_settings_sections( 'cabfm' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the settings field
	 *
	 * @param array $args Arguments for the settings field.
	 */
	public function settings_field( $args ) {
		$setting = get_option( $args['label_for'] );
		?>
		<input type="text" id="<?php echo esc_attr( $args['label_for'] ); ?>>" name="<?php echo esc_attr( $args['label_for'] ); ?>" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>" class="regular-text">
		<?php
	}
}
