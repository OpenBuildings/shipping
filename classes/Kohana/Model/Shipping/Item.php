<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    openbuildings\shipping
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Shipping_Item extends Jam_Model {

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->behaviors(array(
				'freezable' => Jam::behavior('freezable', array('fields' => 'total_delivery_time', 'parent' => 'store_purchase_shipping')),
			))
			->associations(array(
				'store_purchase_shipping' => Jam::association('belongsto', array('inverse_of' => 'items')),
				'purchase_item' => Jam::association('belongsto'),
				'shipping_group' => Jam::association('belongsto', array('inverse_of' => 'shipping_items')),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'total_delivery_time' => Jam::field('range'),
			))
			->validator('purchase_item', 'shipping_group', array('present' => TRUE));
	}

	/**
	 * Build an array of Model_Shipping_Item objects based on the purchase_items given, location and a method
	 * uses each purchase_item's shipping to get the appropriate shipping_group for the location / method
	 * @param  array                 $purchase_items array of Shippable Model_Purchase_Item objects
	 * @param  Model_Location        $location       
	 * @param  Model_Shipping_Method $method         
	 * @return array                 an array of Model_Shipping_Item objects
	 */
	public static function build_from(array $purchase_items, $location, $method)
	{
		$items = array();

		Array_Util::validate_instance_of($purchase_items, 'Model_Purchase_Item');

		foreach ($purchase_items as $purchase_item) 
		{
			$items []= Jam::build('shipping_item', array(
				'purchase_item' => $purchase_item,
				'shipping_group' => $purchase_item->get_insist('reference')->shipping()->group_for($location, $method),
			));
		}

		return $items;
	}

	/**
	 * Filter out Model_Shipping_Item's of shipping_groups that are discounted, 
	 * based on the provided total price 
	 * @param  array     $items array of Model_Shipping_Item objects
	 * @param  Jam_Price $total 
	 * @return array     Model_Shipping_Item objects
	 */
	public static function filter_discounted_items(array $items, Jam_Price $total)
	{
		Array_Util::validate_instance_of($items, 'Model_Shipping_Item');

		return array_filter($items, function($item) use ($total) {
			return ! $item->is_discounted($total);
		});
	}

	/**
	 * Sort Model_Shipping_Item by price, biggest price first 
	 * @param  array  $items 
	 * @return array        
	 */
	public static function sort_by_price(array $items)
	{
		Array_Util::validate_instance_of($items, 'Model_Shipping_Item');

		// Suppress warnings as usort throws "Array was modified by the user comparison function"
		// When price method is being mocked.
		// Relevant php bug: https://bugs.php.net/bug.php?id=50688
		@ usort($items, function($item1, $item2){
			return $item1->price()->is(Jam_Price::GREATER_THAN, $item2->price()) ? -1 : 1;
		});

		return $items;
	}

	/**
	 * Sort and get all the realtive prices of Model_Shipping_Item object (using relative_price method)
	 * @param  array  $items 
	 * @return array  Jam_Price objects
	 */
	public static function relative_prices(array $items)
	{
		Array_Util::validate_instance_of($items, 'Model_Shipping_Item');

		$items = Model_Shipping_Item::sort_by_price($items);

		$prices = array_map(function($item, $index) {
			return $index == 0 ? $item->total_price() : $item->total_additional_item_price();
		}, $items, array_keys($items));

		return $prices;
	}

	/**
	 * Compute prices of Model_Shipping_Item filtering out discounted items,
	 * grouping by method and shipping_from, and calculating their relative prices
	 * @param  array     $items 
	 * @param  Jam_Price $total 
	 * @return Jam_Price
	 */
	public static function compute_price(array $items, Jam_Price $total)
	{
		Array_Util::validate_instance_of($items, 'Model_Shipping_Item');

		$items = Model_Shipping_Item::filter_discounted_items($items, $total);

		$groups = Array_Util::group_by($items, function($item){
			return $item->group_key();
		});

		$group_prices = array_map(function($grouped_items) use ($total) {
			$prices = Model_Shipping_Item::relative_prices($grouped_items);
			return Jam_Price::sum($prices, $total->currency(), $total->monetary());
		}, $groups);

		return Jam_Price::sum($group_prices, $total->currency(), $total->monetary());
	}

	public static function compute_delivery_time(array $items)
	{
		Array_Util::validate_instance_of($items, 'Model_Shipping_Item');

		$ranges = array_map(function($item){
			return $item->total_delivery_time();
		}, $items);

		return Jam_Range::merge($ranges);
	}

	/**
	 * Get the shipping object associated with this item
	 * @return Model_Shipping 
	 * @throws Kohana_Exception If shipping_group or its shipping is NULL
	 */
	public function shipping_insist()
	{
		return $this->get_insist('shipping_group')->get_insist('shipping');
	}

	/**
	 * Get the currency for pricing calculations
	 * @return string
	 * @throws Kohana_Exception If store_purchase_shipping is NULL
	 */
	public function currency()
	{
		return $this->get_insist('store_purchase_shipping')->currency();
	}

	/**
	 * Get the monetary object for currency calculations
	 * @return Monetary
	 * @throws Kohana_Exception If store_purchase_shipping is NULL
	 */
	public function monetary()
	{
		return $this->get_insist('store_purchase_shipping')->monetary();
	}

	/**
	 * Generate a key based on which shipping groups will be devided.
	 * The items in the same group are shipped toggether, allowing us to use 
	 * additional_item_price instead of price.
	 *
	 * Groups by method and ships_from
	 * @return string
	 * @throws Kohana_Exception If shipping_group or shipping is NULL
	 */
	public function group_key()
	{
		return $this->get_insist('shipping_group')->method_id.'-'.$this->shipping_insist()->ships_from_id;
	}

	/**
	 * Get the price from shipping_group, converted into purchase's currency / monetary
	 * @return Jam_Price
	 * @throws Kohana_Exception If shipping_group is NULL
	 */
	public function price()
	{
		$price = $this->get_insist('shipping_group')->price;

		return $price
			->monetary($this->monetary())
				->convert_to($this->currency());
	}

	/**
	 * Get the additional_item_price from shipping_group, converted into purchase's currency / monetary
	 * If there is no additional_item_price, return price instead
	 * @return Jam_Price
	 * @throws Kohana_Exception If shipping_group is NULL
	 */
	public function additional_item_price()
	{
		$group = $this->get_insist('shipping_group');

		$additional_price = $group->additional_item_price ?: $group->price;

		return $additional_price
			->monetary($this->monetary())
				->convert_to($this->currency());
	}

	/**
	 * Get shipping_group's is_discounted
	 * If there is no additional_item_price, return price instead
	 * @return Jam_Price
	 * @throws Kohana_Exception If shipping_group is NULL
	 */
	public function is_discounted(Jam_Price $total)
	{
		return $this->get_insist('shipping_group')->is_discounted($total);
	}

	/**
	 * Get purchase_item's quantity
	 * @return Jam_Price
	 * @throws Kohana_Exception If shipping_group is NULL
	 */
	public function quantity()
	{
		return $this->get_insist('purchase_item')->quantity;
	}

	/**
	 * Return price(), if quantity() > 1 the nany additional items are summed using additional_items_price()
	 * @return Jam_Price
	 * @throws Kohana_Exception If shipping_group is NULL
	 */
	public function total_price()
	{
		$additional_items_price = $this->additional_item_price()->multiply_by($this->quantity() - 1);

		return $this->price()->add($additional_items_price);
	}

	/**
	 * Shipping group's delivery_time
	 * @return Jam_Range 
	 */
	public function delivery_time()
	{
		return $this->get_insist('shipping_group')->delivery_time;
	}

	/**
	 * Shipping's processing_time
	 * @return Jam_Range 
	 */
	public function processing_time()
	{
		return $this->shipping_insist()->processing_time;
	}

	/**
	 * Return the delivary time min / max days - summed processing and delivery times
	 * Freezable
	 * @return Jam_Range
	 */
	public function total_delivery_time()
	{
		if ($this->delivery_time) 
		{
			$total_delivery_time = $this->delivery_time;
		}
		else
		{
			$total_delivery_time = Jam_Range::sum(array($this->delivery_time(), $this->processing_time()));
		}

		return $total_delivery_time;
	}

	/**
	 * Return additional_items_price() multiplied by quantity()
	 * @return Jam_Price
	 * @throws Kohana_Exception If shipping_group or purchase_item is NULL
	 */
	public function total_additional_item_price()
	{
		return $this->additional_item_price()->multiply_by($this->quantity());
	}
}