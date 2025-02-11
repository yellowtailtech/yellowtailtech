<?php

namespace WPFormsFormAbandonment\Migrations;

use WPForms\Migrations\UpgradeBase;
use WPForms\Pro\Admin\DashboardWidget;

/**
 * Class Form Abandonment addon v1.10.0 upgrade.
 *
 * @since 1.10.0
 *
 * @noinspection PhpUnused
 */
class Upgrade1100 extends UpgradeBase {

	/**
	 * Run upgrade.
	 *
	 * @since 1.10.0
	 *
	 * @return bool|null Upgrade result:
	 *                   true  - the upgrade completed successfully,
	 *                   false - in the case of failure,
	 *                   null  - upgrade started but not yet finished (background task).
	 *
	 * @noinspection UnusedFunctionResultInspection
	 */
	public function run() {

		global $wpdb;

		$this->delete_survey_report_cache();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query(
			"DELETE entry_fields
			FROM {$wpdb->prefix}wpforms_entry_fields AS entry_fields
			LEFT JOIN {$wpdb->prefix}wpforms_entries AS entry ON entry_fields.entry_id = entry.entry_id
			WHERE entry.entry_id IS NULL;"
		);

		$wpdb->query(
			"DELETE entry_meta
			FROM {$wpdb->prefix}wpforms_entry_meta AS entry_meta
			LEFT JOIN {$wpdb->prefix}wpforms_entries AS entry ON entry_meta.entry_id = entry.entry_id
			WHERE entry.entry_id IS NULL;"
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

		DashboardWidget::clear_widget_cache();

		return true;
	}

	/**
	 * Get entry fields.
	 *
	 * @since 1.10.0
	 *
	 * @return array
	 */
	private function get_entry_fields(): array {

		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		return (array) $wpdb->get_results(
			"SELECT entry_fields.entry_id, entry_fields.form_id
			FROM {$wpdb->prefix}wpforms_entry_fields AS entry_fields
			LEFT JOIN {$wpdb->prefix}wpforms_entries AS entries ON entry_fields.entry_id = entries.entry_id
			WHERE entries.entry_id IS NULL;"
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Delete survey report cache.
	 *
	 * @since 1.10.0
	 */
	private function delete_survey_report_cache() {

		$entry_fields = $this->get_entry_fields();

		if ( ! $entry_fields ) {
			return;
		}

		$form  = wpforms()->get( 'form' );
		$entry = wpforms()->get( 'entry' );

		if ( $form === null || $entry === null ) {
			return;
		}

		foreach ( $entry_fields as $raw_field ) {
			$form_data = $form->get( $raw_field->form_id, [ 'content_only' => true ] );

			if ( ! $form_data ) {
				continue;
			}

			$fields      = ! empty( $form_data['fields'] ) ? $form_data['fields'] : [];
			$entry_count = $entry->get_entries( [ 'form_id' => $form_data['id'] ], true );

			$this->delete_transient( $fields, $form_data, $entry_count );
		}
	}

	/**
	 * Delete survey report transient.
	 *
	 * @since 1.10.0
	 *
	 * @param array $fields      Form fields.
	 * @param array $form_data   Form data.
	 * @param int   $entry_count Entry count.
	 */
	private function delete_transient( array $fields, array $form_data, int $entry_count ) {

		if ( ! $fields ) {
			return;
		}

		foreach ( $fields as $field ) {
			delete_transient( "wpforms_survey_report_{$form_data['id']}_{$entry_count}_{$field['id']}" );
		}

		delete_transient( "wpforms_survey_report_{$form_data['id']}_{$entry_count}" );
	}
}
