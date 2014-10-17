<?php defined('SYSPATH') OR die('No direct script access.');

use Clippings\Freezable\FreezableInterface;
use Clippings\Freezable\FreezableTrait;

/**
 * @package    openbuildings\shipping
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Shipping_Item extends Jam_Model implements FreezableInterface {

	use FreezableTrait;

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->associations(array(
				'store_purchase_shipping' => Jam::association('belongsto', array(
					'inverse_of' => 'items'
				)),
				'purchase_item' => Jam::association('belongsto', array(
					'inverse_of' => 'shipping_item',
					'foreign_key' => 'purchase_item_id',
					'foreign_model' => 'purchase_item_shipping'
				)),
				'shipping_group' => Jam::association('belongsto', array(
					'inverse_of' => 'shipping_items'
				)),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'model' => Jam::field('polymorphic'),
				'processing_time' => Jam::field('range', array(
					'format' => 'Model_Shipping::format_shipping_time'
				)),
				'delivery_time' => Jam::field('range', array(
					'format' => 'Model_Shipping::format_shipping_time'
				)),
				'is_frozen' => Jam::field('boolean'),
			))
			->validator('purchase_item', array(
				'present' => TRUE
			));
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
	 * Get the shipping object associated with this item
	 * @return Model_Shipping
	 * @throws Kohana_Exception If shipping_group or its shipping is NULL
	 */
	public function shipping_insist()
	{
		$self = $this;

		return Jam_Behavior_Paranoid::with_filter(Jam_Behavior_Paranoid::ALL, function() use ($self) {
			return $self->get_insist('shipping_group')->get_insist('shipping');
		});
	}

	public function purchase_item_shipping()
	{
		return $this->get_insist('purchase_item')->get_insist('reference')->shipping();
	}

	/**
	 * Return the date the purchase was paid
	 */
	public function paid_at()
	{
		return $this->get_insist('store_purchase_shipping')->paid_at();
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
		$group = $this->shipping_group;

		if ( ! $group OR ! $group->shipping)
			return NULL;

		return $group->method_id.'-'.$group->shipping->ships_from_id;
	}

	/**
	 * Get the price from shipping_group, converted into purchase's currency / monetary
	 * @return Jam_Price
	 * @throws Kohana_Exception If shipping_group is NULL
	 */
	public function price()
	{
		$price = $this->shipping_group_insist()->price ?: new Jam_Price(0, $this->currency());

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
		$group = $this->shipping_group_insist();

		$additional_price = $group->additional_item_price ?: $group->price;

		return $additional_price
			->monetary($this->monetary())
				->convert_to($this->currency());
	}

	/**
	 * Get shipping_group's is_discounted
	 * If there is no additional_item_price, return price instead
	 * @return boolean
	 * @throws Kohana_Exception If shipping_group is NULL
	 */
	public function is_discounted(Jam_Price $total)
	{
		return $this->shipping_group_insist()->is_discounted($total);
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
	 * Shipping's processing_time
	 * Freezable
	 *
	 * @return Jam_Range
	 */
	public function processing_time()
	{
		return $this->isFrozen()
			? $this->processing_time
			: $this->shipping_insist()->processing_time;
	}

	/**
	 * Shipping group's delivery_time
	 * Freezable
	 *
	 * @return Jam_Range
	 */
	public function delivery_time()
	{
		return $this->isFrozen()
			? $this->delivery_time
			: $this->shipping_group_insist()->delivery_time;
	}

	/**
	 * Return the delivery time min / max days
	 * Summed processing and delivery times.
	 *
	 * @return Jam_Range
	 */
	public function total_delivery_time()
	{
		$format = $this->meta()->field('delivery_time')->format;

		return Jam_Range::sum(array(
			$this->processing_time(),
			$this->delivery_time(),
		), $format);
	}

	/**
	 * Return the shipping date for this item
	 * @return Jam_Range
	 */
	public function shipping_date()
	{
		$paid_at = $this->paid_at();
		$days = $this->total_delivery_time();

		$from_day = strtotime("{$paid_at} + {$days->min()} weekdays");
		$to_day = strtotime("{$paid_at} + {$days->max()} weekdays");

		return new Jam_Range(array($from_day, $to_day));
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

	/**
	 * Use paranoid for shipping group
	 */
	public function shipping_group_insist()
	{
		$self = $this;

		return Jam_Behavior_Paranoid::with_filter(Jam_Behavior_Paranoid::ALL, function() use ($self) {
			return $self->get_insist('shipping_group');
		});
	}

	public function shipping_method()
	{
		if ( ! $this->shipping_group)
			return NULL;

		return $this->shipping_group->method;
	}

	public function update_address(Model_Address $address)
	{
		if ( ! $address->changed('country') OR ! $address->country)
			return;

		if (! $this->shipping_group OR ! $this->shipping_group->location OR ! $this->shipping_group->location->contains($address->country))
		{
			$this->shipping_group = $this->purchase_item_shipping()->cheapest_group_in($address->country);
		}
	}

	public function isFrozen()
	{
		return $this->is_frozen;
	}

	public function setFrozen($frozen)
	{
		$this->is_frozen = (bool) $frozen;
	}

	public function performFreeze()
	{
		$this->processing_time = $this->processing_time();
		$this->delivery_time = $this->delivery_time();
	}

	public function performUnfreeze()
	{
		$this->processing_time = NULL;
		$this->delivery_time = NULL;
	}
}
