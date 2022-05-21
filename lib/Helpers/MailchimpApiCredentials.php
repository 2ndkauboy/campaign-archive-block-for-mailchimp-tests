<?php
/**
 * Class MailchimpApiCredentials
 *
 * @package CABFM\Settings
 */

namespace CABFM\Helpers;

use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class MailchimpApiCredentials
 */
class MailchimpApiCredentials {
	/**
	 * Initialize the class
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_settings' ] );
		add_filter( 'rest_request_after_callbacks', [ $this, 'obfuscate_api_key' ], 10, 3 );
		add_filter( 'pre_update_option_cabfm_api_key', [ $this, 'validate_credentials' ], 10, 3 );
	}

	/**
	 * Register block settings.
	 */
	public function register_settings() {
		register_setting(
			'cabfm_api_key',
			'cabfm_api_key',
			[
				'type'              => 'string',
				'description'       => __( 'Mailchimp API Key for the Marketing API.', 'campaign-archive-block-for-mailchimp' ),
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => '',
			]
		);
		register_setting(
			'cabfm_api_credentials_validation_result',
			'cabfm_api_credentials_validation_result',
			[
				'type'              => 'boolean',
				'description'       => __( 'Result of the last validation request', 'campaign-archive-block-for-mailchimp' ),
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => false,
			]
		);
		register_setting(
			'cabfm_api_credentials_validation_message',
			'cabfm_api_credentials_validation_message',
			[
				'type'              => 'string',
				'description'       => __( 'Response message of the last validation request.', 'campaign-archive-block-for-mailchimp' ),
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => '',
			]
		);
		register_setting(
			'cabfm_api_credentials_account_name',
			'cabfm_api_credentials_account_name',
			[
				'type'              => 'string',
				'description'       => __( 'The account name connected to the API key.', 'campaign-archive-block-for-mailchimp' ),
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => '',
			]
		);
	}

	/**
	 * Filters the response and replaces the cabfm_api_key so it is not shown in the REST response.
	 *
	 * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $response Result to send to the client. Usually a WP_REST_Response or WP_Error.
	 * @param array                                            $handler  Route handler used for the request.
	 * @param WP_REST_Request                                  $request  Request used to generate the response.
	 *
	 * @return mixed
	 */
	public function obfuscate_api_key( $response, $handler, $request ) {
		if ( '/wp/v2/settings' !== $request->get_route() ) {
			return $response;
		}

		if ( is_array( $response ) && ! empty( $response['cabfm_api_key'] ) ) {
			// Split the API key by the token and server suffix parts.
			$api_key_parts = explode( '-', $response['cabfm_api_key'] );
			// Replace all but the first three chars of the token part.
			$api_key_parts[0] = substr_replace( $api_key_parts[0], str_pad( '', 29, '*' ), 3 );
			// Overwrite the API key in the repsonse.
			$response['cabfm_api_key'] = implode( '-', $api_key_parts );
		}

		return $response;
	}

	/**
	 * Validate if the credentials are correct, if not, return the old value so the update is skipped
	 *
	 * @param mixed  $value     The new, unserialized option value.
	 * @param mixed  $old_value The old option value.
	 * @param string $option    Option name.
	 *
	 * @return string
	 */
	public function validate_credentials( $value, $old_value, $option ) {
		// Remove options if API key is empty.
		if ( empty( $value ) ) {
			delete_option( 'cabfm_api_key' );
			delete_option( 'cabfm_api_credentials_validation_result' );
			delete_option( 'cabfm_api_credentials_validation_message' );
			delete_option( 'cabfm_api_credentials_account_name' );

			return;
		}

		// Overwrite the API key with the newly submitted value.
		MailchimpAPI::set_api_key( $value );

		// If the API key has not changed don't request it again.
		if ( $value === $old_value ) {
			return $value;
		}

		// Try to get a API response with those crendentials.
		$validation_request = MailchimpAPI::get( '/' );

		$account_name = '';

		if ( ! is_wp_error( $validation_request ) ) {
			$response_body = json_decode( wp_remote_retrieve_body( $validation_request ), true );

			if ( isset( $validation_request['response']['code'] ) && 200 !== $validation_request['response']['code'] ) {
				if ( ( isset( $response_body['status'] ) && 401 === $response_body['status'] ) || ( isset( $response_body['title'] ) && 'API Key Invalid' === $response_body['title'] ) ) {
					$validation_result  = false;
					$validation_message = __( 'The credentials you have entered are wrong!', 'campaign-archive-block-for-mailchimp' );
				} else {
					$validation_result  = false;
					$validation_message = __( 'There was an unknown error validating the credentials!', 'campaign-archive-block-for-mailchimp' );
				}
			} else {
				$validation_result  = true;
				$validation_message = __( 'The credentials you have entered have been validated and are correct!', 'campaign-archive-block-for-mailchimp' );
				$account_name       = $response_body['account_name'];
			}
		} else {
			$validation_result  = false;
			$validation_message = __( 'There was a request error trying to validating the credentials!', 'campaign-archive-block-for-mailchimp' );
		}

		update_option( 'cabfm_api_credentials_validation_result', $validation_result );
		update_option( 'cabfm_api_credentials_validation_message', $validation_message );

		if ( ! empty( $account_name ) ) {
			update_option( 'cabfm_api_credentials_account_name', $account_name );
		}

		return $value;
	}

}
