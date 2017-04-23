<?php defined('SYSPATH') OR die('No direct script access.');

use Clippings\Freezable\FreezableInterface;
use Clippings\Freezable\FreezableTrait;

/**
 * @package    openbuildings\shipping
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Brand_Purchase_Shipping extends Jam_Model implements Sellable, FreezableInterface {

	use FreezableTrait;

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->associations(array(
				'brand_purchase' => Jam::association('belongsto', array('inverse_of' => 'shipping')),
				'items' => Jam::association('hasmany', array(
					'foreign_model' => 'shipping_item',
					'inverse_of' => 'brand_purchase_shipping',
					'delete_on_remove' => Jam_Association::DELETE,
					'dependent' => Jam_Association::DELETE,
				)),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'is_frozen' => Jam::field('boolean')
			))
			->validator('brand_purchase', 'items', array('present' => TRUE));
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
			return ($item->shipping_group AND $item->shipping_group->shipping AND $item->purchase_item);
		});
	}

	public function duplicate()
	{
		$duplicate = Jam::build('brand_purchase_shipping', array(
			'brand_purchase' => $this->brand_purchase
		));

		// This is needed to counteract inverse_of brand_purchase in brand_purchase_shipping
		$this->brand_purchase->shipping = $this;

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
	 * @throws Kohana_Exception If brand_purchase is NULL
	 * @return Jam_Price
	 */
	public function total_purchase_price()
	{
		return $this
			->get_insist('brand_purchase')
				->total_price(array('is_payable' => TRUE, 'not' => 'shipping'));
	}

	/**
	 * Return the paid at date
	 * @return string
	 */
	public function paid_at()
	{
		return $this->get_insist('brand_purchase')->paid_at();
	}

	/**
	 * Get the currency to be used in all the calculations
	 * @return string
	 */
	public function currency()
	{
		return $this
			->get_insist('brand_purchase')
				->currency();
	}

	/**
	 * Get the location to be used in all the calculations
	 * @return string
	 */
	public function ship_to()
	{
		return $this
			->get_insist('brand_purchase')
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
			->get_insist('brand_purchase')
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
	 * Build a single shipping_item and add it to the items of this brand_purchase_shipping.
	 * @param  Model_Purchase_Item $purchase_item
	 * @param  Model_Shipping_Method              $method
	 * @return Model_Brand_Purchase_Shipping
	 */
	public function build_item_from(Model_Purchase_Item $purchase_item, Model_Shipping_Method $method = NULL)
	{
		$this->items []= $this->new_item_from($purchase_item, $this->ship_to(), $method);

		// Mark the shipping as changed so it can be properly saved
		$this->items = $this->items;

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

	public function update_items_address(Model_Brand_Purchase_Shipping $brand_purchase_shipping)
	{
		foreach ($this->items->as_array() as $item)
		{
			$item->update_address($brand_purchase_shipping);
		}
	}

	public function new_item_from(Model_Purchase_Item $purchase_item, Model_Location $location, Model_Shipping_Method $method = NULL)
	{
		$shipping = $purchase_item->get_insist('reference')->shipping();

		$fields = array(
			'brand_purchase_shipping' => $this,
			'purchase_item' => $purchase_item,
		);

		return $shipping->new_shipping_item_from($fields, $location, $method);
	}

	public function freeze()
	{
		$this->performFreeze();
		$this->setFrozen(true);
		return $this;
	}

	public function unfreeze()
	{
		$this->performUnfreeze();
		$this->setFrozen(false);
		return $this;
	}

	public function isFrozen()
	{
		return $this->is_frozen;
	}

	protected function setFrozen($frozen)
	{
		$this->is_frozen = (bool) $frozen;

		return $this;
	}

	public function performFreeze()
	{
		$this->freezeCollection();

		return $this;
	}

	public function performUnfreeze()
	{
		$this->unfreezeCollection();
		return $this;
	}

	public function freezeCollection()
	{
		foreach ($this->items as $item)
		{
			$item->freeze();
		}
	}

	public function unfreezeCollection()
	{
		foreach ($this->items as $item)
		{
			$item->unfreeze();
		}
	}
}
