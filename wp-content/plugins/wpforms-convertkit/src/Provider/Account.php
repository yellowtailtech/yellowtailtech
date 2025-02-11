<?php

namespace WPFormsConvertKit\Provider;

use RuntimeException;
use WPFormsConvertKit\Plugin;
use WPFormsConvertKit\Api\Connection;

/**
 * Class Account.
 *
 * @since 1.0.0
 */
class Account {

	/**
	 * Check if account with this API key already exists.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_key    Kit API key.
	 * @param string $api_secret Kit API Secret.
	 *
	 * @return bool
	 */
	private function exists( string $api_key, string $api_secret ): bool {

		$options = wpforms_get_providers_options( Plugin::SLUG );
		$keys    = array_column( $options, 'api_key' );
		$secrets = array_column( $options, 'api_secret' );

		return in_array( $api_key, $keys, true ) || in_array( $api_secret, $secrets, true );
	}

	/**
	 * Get list of available accounts.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_all(): array {

		$accounts = wpforms_get_providers_options( Plugin::SLUG );

		if ( empty( $accounts ) ) {
			return [];
		}

		foreach ( $accounts as $account_id => $account ) {
			if ( empty( $account['api_key'] ) || empty( $account['api_secret'] ) ) {
				$this->remove( $account_id );

				continue;
			}

			$accounts[ $account_id ] = $account['label'];
		}

		return $accounts;
	}

	/**
	 * Get connection.
	 *
	 * @since 1.0.0
	 *
	 * @param string $account_id Account ID.
	 *
	 * @return Connection|null
	 */
	public function get_connection( string $account_id ) {

		$accounts = wpforms_get_providers_options( Plugin::SLUG );

		if ( empty( $accounts[ $account_id ]['api_key'] ) || empty( $accounts[ $account_id ]['api_secret'] ) ) {
			return null;
		}

		return new Connection( $accounts[ $account_id ]['api_key'], $accounts[ $account_id ]['api_secret'] );
	}

	/**
	 * Remove an account.
	 *
	 * @since 1.0.0
	 *
	 * @param string $account_id Account ID.
	 */
	private function remove( string $account_id ) {

		$providers = wpforms_get_providers_options();

		if ( empty( $providers[ Plugin::SLUG ][ $account_id ] ) ) {
			return;
		}

		unset( $providers[ Plugin::SLUG ][ $account_id ] );

		update_option( 'wpforms_providers', $providers );
	}

	/**
	 * Save a new account.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_key    API key.
	 * @param string $api_secret API secret.
	 *
	 * @return array
	 *
	 * @throws RuntimeException Invalid key or account has already exists.
	 */
	public function add( string $api_key, string $api_secret ): array {

		$connection   = new Connection( $api_key, $api_secret );
		$account_data = $connection->get_account();

		// Request error.
		if ( empty( $account_data ) || ! empty( $connection->check_valid_api_key() ) ) {
			throw new RuntimeException(
				esc_html__( 'Invalid Kit API credentials. Please check your information and try again.', 'wpforms-convertkit' ),
				400
			);
		}

		if ( $this->exists( $api_key, $api_secret ) ) {
			throw new RuntimeException(
				esc_html__( 'Account with these credentials has already been added.', 'wpforms-convertkit' ),
				400
			);
		}

		$key          = uniqid( '', true );
		$account_name = ! empty( $account_data['primary_email_address'] ) ? $account_data['primary_email_address'] : '';

		$options = [
			'api_key'    => $api_key,
			'api_secret' => $api_secret,
			'label'      => $account_name,
			'date'       => time(),
		];

		wpforms_update_providers_options( Plugin::SLUG, $options, $key );

		$options['key'] = $key;

		return $options;
	}
}
