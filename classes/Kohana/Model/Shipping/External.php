<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    openbuildings\shipping
 * @author     Danail Kyosev <ddkyosev@gmail.com>
 * @copyright  (c) 2014 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
abstract class Kohana_Model_Shipping_External extends Model_Shipping {

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		parent::initialize($meta);

		$meta
			->table('shippings')
			->fields(array(
				'width' => Jam::field('float', array('places' => 2)),
				'height' => Jam::field('float', array('places' => 2)),
				'depth' => Jam::field('float', array('places' => 2)),
				'weight' => Jam::field('float', array('places' => 2)),
			))
			->validator('width', 'height', 'depth', 'weight', array(
				'present' => TRUE,
				'numeric' => array('greater_than_or_equal_to' => 0)
			));
	}

	abstract public function external_data_for(Model_Location $location);
	abstract public function get_external_shipping_method();

	public function new_shipping_item_from(array $fields, Model_Location $location, Model_Shipping_Method $method = NULL)
	{
		$fields['shipping_external_data'] = $this->external_data_for($location);

		return Jam::build('shipping_item_external', $fields);
	}

	public function is_changed()
	{
		foreach (array('processing_time', 'ships_from_id', 'width', 'height', 'depth', 'weight') as $field)
		{
			if ($this->{$field} != $this->original($field))
				return TRUE;
		}

		return FALSE;
	}

	public function ships_to(Model_Location $location)
	{
		return $this->external_data_for($location) !== NULL;
	}

	public function delivery_time_for(Model_Location $location)
	{
		$external_data = $this->external_data_for($location);

		if ( ! $external_data)
			return NULL;

		return new Jam_Range($external_data->delivery_time, 'Model_Shipping::format_shipping_time');
	}

	public function price_for_location(Model_Location $location)
	{
		$external_data = $this->external_data_for($location);

		if ( ! $external_data)
			return NULL;

		return $external_data->price;
	}

	public function additional_price_for_location(Model_Location $location)
	{
		return NULL;
	}

	public function discount_threshold_for_location(Model_Location $location)
	{
		return NULL;
	}

	public function methods_for($location)
	{
		return array($this->get_external_shipping_method());
	}
}
