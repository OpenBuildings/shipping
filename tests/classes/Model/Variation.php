<?php

class Model_Variation extends Jam_Model implements Sellable, Shippable {

	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->associations(array(
				'product' => Jam::association('belongsto'),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'name' => Jam::field('string'),
				'price' => Jam::field('float'),
			))
			->validator('name', 'price', 'product', array(
				'present' => TRUE
			))
			->validator('price', array('numeric' => TRUE));
	}

	public function price_for_purchase_item(Model_Purchase_Item $item)
	{
		return $this->price;
	}

	public function currency()
	{
		return $this->get_insist('product')->currency;
	}

	public function shipping()
	{
		return  $this->get_insist('product')->shipping;
	}

	public function ships_to(Model_Location $location)
	{
		$shipping = $this->shipping();

		return $shipping ? $shipping->ships_to($location) : FALSE;
	}
}