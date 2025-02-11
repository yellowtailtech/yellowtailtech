<?php

namespace WPFormsConvertKit\Provider\Actions;

use WPFormsConvertKit\Api\Connection;
use WPFormsConvertKit\Provider\FieldMapper;

/**
 * Class Action.
 *
 * @since 1.0.0
 */
abstract class Action {

	/**
	 * Connection with the Kit API.
	 *
	 * @since 1.0.0
	 *
	 * @var Connection
	 */
	protected $connection;

	/**
	 * Field mapper.
	 *
	 * @since 1.0.0
	 *
	 * @var FieldMapper
	 */
	protected $field_mapper;

	/**
	 * Connection data.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $connection_data;

	/**
	 * Form data.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $form_data;

	/**
	 * Form fields.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $fields;

	/**
	 * Action constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Connection  $connection      Kit API connection.
	 * @param FieldMapper $field_mapper    Field mapper.
	 * @param array       $connection_data Connection data.
	 * @param array       $form_data       Form data.
	 * @param array       $fields          Form fields.
	 */
	public function __construct( Connection $connection, FieldMapper $field_mapper, array $connection_data, array $form_data, array $fields ) {

		$this->connection      = $connection;
		$this->field_mapper    = $field_mapper;
		$this->connection_data = $connection_data;
		$this->form_data       = $form_data;
		$this->fields          = $fields;
	}

	/**
	 * Run action.
	 *
	 * @since 1.0.0
	 */
	abstract public function run();
}
