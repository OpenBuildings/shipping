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
			->behaviors(array(
				'freezable' => Jam::behavior('freezable', array('associations' => 'items', 'parent' => 'store_purchase')),
			))
			->associations(array(
				'store_purchase' => Jam::association('belongsto', array('inverse_of' => 'shipping')),
				'items' => Jam::association('hasmany', array(
					'foreign_model' => 'shipping_item',
					'inverse_of' => 'store_purchase_shipping',
					'delete_on_remove' => Jam_Association::DELETE,
					'dependent' => Jam_Association::DELETE,
				)),
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
	public function price_for_purchase_item(Model_Purchase_Item $item)
	{
		return $this->total_price();
	}

	public function name()
	{
		return 'Shipping';
	}

	public function total_price()
	{
		$total = $this->total_purchase_price();
		$items = $this->available_items();

		$items = Model_Shipping_Item::filter_discounted_items($items, $total);

		$groups = Array_Util::group_by($items, function($item){
			return $item->group_key();
		});

		$group_prices = array_map(function($grouped_items) use ($total) {
			$prices = Model_Shipping_Item::relative_prices($grouped_items);
			return Jam_Price::sum($prices, $total->currency(), $total->monetary(), $total->display_currency());
		}, $groups);

		return Jam_Price::sum($group_prices, $total->currency(), $total->monetary(), $total->display_currency());
	}

	public function available_items()
	{
		return array_filter($this->items->as_array(), function($item){
			return (($item->shipping_group OR $item->shipping_external_data) AND $item->purchase_item);
		});
	}

	public function duplicate()
	{
		$duplicate = Jam::build('store_purchase_shipping', array(
			'store_purchase' => $this->store_purchase
		));

		// This is needed to counteract inverse_of store_purchase in store_purchase_shipping
		$this->store_purchase->shipping = $this;

		return $duplicate;
	}

	public function items_from(array $purchase_items)
	{
		Array_Util::validate_instance_of($purchase_items, 'Model_Purchase_Item');

		$purchase_item_ids = array_map(function($purchase_item){ return $purchase_item->id(); }, $purchase_items);

		$items = array();

		foreach ($this->items->as_array() as $index => $item)
		{
			if (in_array($item->purchase_item_id, $purchase_item_ids))
			{
				$items[$index] = $item;
			}
		}

		return $items;
	}

	/**
	 * Get the merge of all total_delivery_time ranges from the items
	 * By getting the maximum min and max amounts.
	 * @return Jam_Range
	 */
	public function total_delivery_time()
	{
		$times = array_map(function($item){
			return $item->total_delivery_time();
		}, $this->items->as_array());

		return Jam_Range::merge($times, 'Model_Shipping::format_shipping_time');
	}

	/**
	 * Return the day all the items should be shipped
	 * @return Jam_Range
	 */
	public function total_shipping_date()
	{
		$paid_at = $this->paid_at();
		$days = $this->total_delivery_time();

		$from_day = strtotime("{$paid_at} + {$days->min()} weekdays");
		$to_day = strtotime("{$paid_at} + {$days->max()} weekdays");

		return new Jam_Range(array($from_day, $to_day));
	}

	/**
	 * Total price for the purchased items
	 * @throws Kohana_Exception If store_purchase is NULL
	 * @return Jam_Price
	 */
	public function total_purchase_price()
	{
		return $this
			->get_insist('store_purchase')
				->total_price(array('is_payable' => TRUE, 'not' => 'shipping'));
	}

	/**
	 * Return the paid at date
	 * @return string
	 */
	public function paid_at()
	{
		return $this->get_insist('store_purchase')->paid_at();
	}

	/**
	 * Get the currency to be used in all the calculations
	 * @return string
	 */
	public function currency()
	{
		return $this
			->get_insist('store_purchase')
				->currency();
	}

	/**
	 * Get the location to be used in all the calculations
	 * @return string
	 */
	public function ship_to()
	{
		return $this
			->get_insist('store_purchase')
				->get_insist('purchase')
					->shipping_country();
	}

	/**
	 * Get the monetary object to be used in all the calculations
	 * @return Monetary
	 */
	public function monetary()
	{
		return $this
			->get_insist('store_purchase')
				->monetary();
	}

	/**
	 * Build Shipping_Items based on purchase items and method, as well as the ship_to() method
	 * @param  array                 $purchase_items array of Model_Purchase_Item objects
	 * @param  Model_Shipping_Method $method
	 * @return $this
	 */
	public function build_items_from(array $purchase_items, Model_Shipping_Method $method = NULL)
	{
		$this->items = $this->new_items_from($purchase_items, $this->ship_to(), $method);

		return $this;
	}

	/**
	 * Build a single shipping_item and add it to the items of this store_purchase_shipping.
	 * @param  Model_Purchase_Item $purchase_item
	 * @param  Model_Shipping_Method              $method
	 * @return Model_Store_Purchase_Shipping
	 */
	public function build_item_from(Model_Purchase_Item $purchase_item, Model_Shipping_Method $method = NULL)
	{
		$this->items []= $this->new_item_from($purchase_item, $this->ship_to(), $method);

		return $this;
	}

	public function new_items_from(array $purchase_items, Model_Location $location, $method = NULL)
	{
		Array_Util::validate_instance_of($purchase_items, 'Model_Purchase_Item');

		$self = $this;

		return array_map(function($purchase_item) use ($location, $method, $self) {
			return $self->new_item_from($purchase_item, $location, $method);
		}, $purchase_items);
	}

	public function update_items_location(Model_Location $location)
	{
		foreach ($this->items->as_array() as $item)
		{
			if ($item instanceof Model_Shipping_Item_External)
			{
				$item->external_shipping_data = $item->purchase_item_shipping()->external_data_for($location);
			}
			elseif ( ! $item->shipping_group OR ! $item->shipping_group->location OR ! $item->shipping_group->location->contains($location))
			{
				$item->shipping_group = $item->purchase_item_shipping()->cheapest_group_in($location);
			}
		}
	}

	public function new_item_from(Model_Purchase_Item $purchase_item, Model_Location $location, Model_Shipping_Method $method = NULL)
	{
		$shipping = $purchase_item->get_insist('reference')->shipping();

		$fields = array(
			'store_purchase_shipping' => $this,
			'purchase_item' => $purchase_item,
		);

		return $shipping->new_shipping_item_from($fields, $location, $method);
	}
}
