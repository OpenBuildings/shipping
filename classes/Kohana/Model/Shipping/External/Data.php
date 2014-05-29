<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_Model_Shipping_External_Data extends Jam_Model {

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->table('shipping_external_data')
			->fields(array(
				'id'            => Jam::field('primary'),
				'key'			=> Jam::field('string'),
				'price'         => Jam::field('price'),
				'delivery_time' => Jam::field('range', array('format' => 'Model_Shipping::format_shipping_time')),
			))
			->name_key('key')
			->validator('price', 'key', 'delivery_time', array('present' => TRUE))
			->validator('key', array('unique' => TRUE))
			->validator('delivery_time', array('range' => array('consecutive' => TRUE, 'greater_than_or_equal_to' => 0)));
	}
}