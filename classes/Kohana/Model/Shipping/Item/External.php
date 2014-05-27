<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_Model_Shipping_Item_External extends Model_Shipping_Item {

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->associations(array(
				'shipping_external_data' => Jam::association('belongsto', array(
					'inverse_of' => 'shipping_items'
				)),
			));
	}

	public function group_key()
	{
		$external_data = $this->shipping_external_data_insist;

		if ( ! $external_data)
			return NULL;

		return $$external_data->name;
	}

	/**
	 * Get the price from shipping_external_data, converted into purchase's currency / monetary
	 * @return Jam_Price
	 * @throws Kohana_Exception If shipping_external_data is NULL
	 */
	public function price()
	{
		$price = $this->shipping_external_data_insist()->price ?: new Jam_Price(0, $this->currency());

		return $price
			->monetary($this->monetary())
				->convert_to($this->currency());
	}

	/**
	 * For external shippings, just return the normal price
	 * @return Jam_Price
	 * @throws Kohana_Exception If shipping_external_data is NULL
	 */
	public function additional_item_price()
	{
		return $this->price()
			->monetary($this->monetary())
				->convert_to($this->currency());
	}

	/**
	 * For external shippings, there is no discount
	 * @return boolean
	 */
	public function is_discounted(Jam_Price $total)
	{
		return FALSE;
	}

	/**
	 * Use paranoid for shipping external data
	 */
	public function shipping_external_data_insist()
	{
		$self = $this;

		return Jam_Behavior_Paranoid::with_filter(Jam_Behavior_Paranoid::ALL, function() use ($self) {
			return $self->get_insist('shipping_external_data');
		});
	}
}