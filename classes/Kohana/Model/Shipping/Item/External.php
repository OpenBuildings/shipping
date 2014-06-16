<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_Model_Shipping_Item_External extends Model_Shipping_Item {

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		parent::initialize($meta);

		$meta
			->table('shipping_items')
			->associations(array(
				'shipping_external_data' => Jam::association('belongsto', array(
					'inverse_of' => 'shipping_items'
				)),
			));
	}

	public function group_key()
	{
		$external_data = $this->shipping_external_data;

		if ( ! $external_data)
			return NULL;

		return $external_data->key;
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
		return $this->price();
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

	public function shipping_insist()
	{
		$self = $this;

		return Jam_Behavior_Paranoid::with_filter(Jam_Behavior_Paranoid::ALL, function() use ($self) {
			return $self->get_insist('purchase_item')->get_insist('reference')->shipping();
		});
	}

	public function delivery_time()
	{
		return ($this->delivery_time AND $this->delivery_time->min() !== NULL)
			? $this->delivery_time
			: $this->shipping_external_data_insist()->delivery_time;
	}

	public function shipping_method()
	{
		return $this->purchase_item_shipping()->get_external_shipping_method();
	}

	public function update_address(Model_Address $address)
	{
		$this->external_shipping_data = $this->purchase_item_shipping()->external_data_for($address->country);
	}
}