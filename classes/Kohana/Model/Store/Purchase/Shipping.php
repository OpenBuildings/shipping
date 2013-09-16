<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    openbuildings\shipping
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Store_Purchase_Shipping extends Jam_Model implements Sellable {

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->associations(array(
				'store_purchase' => Jam::association('belongsto'),
				'location' => Jam::association('belongsto'),
				'items' => Jam::association('hasmany', array('foreign_model' => 'shipping_item', 'inverse_of' => 'store_purchase_shipping')),
			))
			->fields(array(
				'id' => Jam::field('primary'),
			))
			->validator('store_purchase', 'items', array('present' => TRUE));
	}

	/**
	 * Implement Sellable
	 * Returns the computed price of all of its items
	 * @param  Model_Purchase_Item $item
	 * @return Jam_Price
	 */
	public function price(Model_Purchase_Item $item)
	{
		$items = $this->items->as_array();
		$total_price = $this->total_purchase_price();

		return Model_Shipping_Item::compute_price($items, $total_price);
	}

	/**
	 * Total price for the purchased items
	 * @throws Kohana_Exception If store_purchase is NULL
	 * @return Jam_Price
	 */
	public function total_purchase_price()
	{
		return $this->get_insist('store_purchase')->total_price(array('is_payable' => TRUE));
	}

	/**
	 * Get the currency to be used in all the calculations
	 * @return string 
	 */
	public function currency()
	{
		return $this->get_insist('store_purchase')->currency();
	}

	/**
	 * Get the location to be used in all the calculations
	 * @return string 
	 */
	public function ship_to()
	{
		return $this->location;
	}

	/**
	 * Get the monetary object to be used in all the calculations
	 * @return Monetary 
	 */
	public function monetary()
	{
		return $this->get_insist('store_purchase')->monetary();
	}

	/**
	 * Build Shipping_Items based on purchase items and method, as well as the ship_to() method
	 * @param  array                 $purchase_items array of Model_Purchase_Item objects
	 * @param  Model_Shipping_Method $method
	 * @return $this
	 */
	public function build_items_from(array $purchase_items, Model_Shipping_Method $method)
	{
		$this->items = Model_Shipping_item::build_from($purchase_items, $this->ship_to(), $method);

		return $this;
	}
}