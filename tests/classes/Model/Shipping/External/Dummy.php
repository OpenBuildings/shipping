<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Shipping_External_Dummy extends Model_Shipping_External {
	const VOL_WEIGHT_DIVISOR = 5000;

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
				'price' => new Jam_Price(5.13, 'GBP'),
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

	public function get_external_shipping_method()
	{
		return Jam::build('shipping_method', array(
			'id' => 'external',
			'name' => 'External',
		));
	}
}