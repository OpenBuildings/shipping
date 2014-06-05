<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_Model_Shipping_External extends Model_Shipping {
	const VOL_WEIGHT_DIVISOR = 5000;

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

	public function get_weight()
	{
		$vol_weight = $this->width * $this->height * $this->depth / self::VOL_WEIGHT_DIVISOR;

		return max($this->weight, $vol_weight);
	}

	public function external_data_for(Model_Location $location)
	{
		$key = $this->generate_data_key($location);

		$external_data = Jam::find('shipping_external_data', $key);

		if ( ! $external_data)
		{
			// TODO add proper external shipping API logic here
			$external_data = Jam::build('shipping_external_data', array(
				'key' => $key,
				'price' => new Jam_Price(0, 'GBP'),
				'delivery_time' => new Jam_Range(array(3, 5)),
			));

			$external_data->save();
		}

		return $external_data;
	}

	public function generate_data_key(Model_Location $location)
	{
		return md5($this->ships_from->name. $location->name. $this->get_weight());
	}

	public function new_shipping_item_from(array $fields, Model_Location $location, Model_Shipping_Method $method = NULL)
	{
		$fields['external_shipping_data'] = $this->external_data_for($location);

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
}