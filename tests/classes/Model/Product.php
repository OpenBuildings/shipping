<?php

class Model_Product extends Jam_Model implements Sellable, Shippable {

	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->associations(array(
				'shipping' => Jam::association('belongsto', array(
					'inverse_of' => 'products'
				)),
				'store' => Jam::association('belongsto'),
				'variations' => Jam::association('hasmany'),
				'purchase_items' => Jam::association('hasmany', array(
					'as' => 'reference',
				)),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'name' => Jam::field('string'),
				'currency' => Jam::field('string'),
				'price' => Jam::field('price'),
			))
			->validator('name', 'price', 'currency', array(
				'present' => TRUE
			))
			->validator('price', array(
				'numeric' => TRUE
			));
	}

	public function price_for_purchase_item(Model_Purchase_Item $item)
	{
		return $this->price;
	}

	public function currency()
	{
		return $this->currency;
	}

	public function shipping()
	{
		return $this->shipping;
	}

	public function ships_to(Model_Location $location)
	{
		return $this->shipping ? $this->shipping->ships_to($location) : FALSE;
	}
}
