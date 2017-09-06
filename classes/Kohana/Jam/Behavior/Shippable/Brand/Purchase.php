<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    openbuildings\shipping
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Jam_Behavior_Shippable_Brand_Purchase extends Jam_Behavior {

	/**
	 * @codeCoverageIgnore
	 */
	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$meta
			->association('shipping', Jam::association('hasone', array(
				'foreign_model' => 'brand_purchase_shipping',
				'inverse_of' => 'brand_purchase',
				'dependent' => Jam_Association::DELETE,
			)))
			->events()
				->bind('model.update_items', array($this, 'add_brand_purchase_shipping'))
				->bind('model.update_items', array($this, 'update_shipping_items'))
				->bind('model.filter_items', array($this, 'filter_shipping_items'));
	}

	public function model_call_group_shipping_methods(Model_Brand_Purchase $brand_purchase, Jam_Event_Data $data)
	{
		$items = $brand_purchase->items(array('can_ship' => TRUE));

		$groups = Array_Util::group_by($items, function($item) use ($brand_purchase) {
			$item->methods_for_location = $item->get_insist('reference')->shipping()->methods_for($brand_purchase->shipping_country());
			return join(',', array_keys($item->methods_for_location));
		});

		$data->return = array_map(function($group) use ($brand_purchase) {
			$methods = $group[0]->methods_for_location;

			return new Group_Shipping_Methods($brand_purchase->shipping, $methods, $group);
		}, $groups);
	}

	public function model_call_total_delivery_time(Model_Brand_Purchase $brand_purchase, Jam_Event_Data $data)
	{
		$data->return = $brand_purchase->get_insist('shipping')->total_delivery_time();
	}

	public function model_call_delivery_time_dates(Model_Brand_Purchase $brand_purchase, Jam_Event_Data $data)
	{
		$delivery_time = $brand_purchase->total_delivery_time();
		$start_date = $brand_purchase->paid_at() ?: time();

		$data->return = new Jam_Range(array(
			strtotime("{$start_date} + {$delivery_time->min()} weekdays"),
			strtotime("{$start_date} + {$delivery_time->max()} weekdays"),
		));
	}

	public function filter_shipping_items(Model_Brand_Purchase $brand_purchase, Jam_Event_Data $data, array $items, array $filter)
	{
		$items = is_array($data->return) ? $data->return : $items;
		$filtered = array();

		foreach ($items as $item)
		{
			if (array_key_exists('shippable', $filter) AND ($item->reference instanceof Shippable) !== $filter['shippable'])
			{
				continue;
			}

			if ((array_key_exists('can_ship_to', $filter) OR array_key_exists('cannot_ship_to', $filter) OR array_key_exists('can_ship', $filter))
				AND ! ($item->reference instanceof Shippable))
			{
				continue;
			}

			if (array_key_exists('can_ship_to', $filter) AND ! $item->reference->ships_to($filter['can_ship_to']))
			{
				continue;
			}

			if (array_key_exists('cannot_ship_to', $filter) AND $item->reference->ships_to($filter['cannot_ship_to']))
			{
				continue;
			}

			if (array_key_exists('can_ship', $filter))
			{
				$shipping = $item->get_insist('brand_purchase')->shipping;

				$can_ship = ($shipping AND $shipping->ship_to() AND $item->reference->ships_to($shipping->ship_to()));

				if ($can_ship !== $filter['can_ship'])
					continue;
			}

			$filtered [] = $item;
		}

		$data->return = $filtered;
	}

	public function add_brand_purchase_shipping(Model_Brand_Purchase $brand_purchase)
	{
		if ($brand_purchase->shipping_country())
		{
			if ( ! $brand_purchase->shipping)
			{
				$brand_purchase->build('shipping');
			}
			else
			{
				$brand_purchase->shipping = $brand_purchase->shipping;
			}

			$shipping_items = array();

			foreach ($brand_purchase->items(array('shippable' => TRUE)) as $purchase_item)
			{
				if ( ! $purchase_item->shipping_item)
				{
					$brand_purchase->shipping->build_item_from($purchase_item);
				} else if ( ! $purchase_item->shipping_item->shipping_group) {
					$purchase_item->shipping_item->update_address($brand_purchase->shipping);
					$purchase_item->shipping_item = $purchase_item->shipping_item;
				}

				$shipping_items [] = $purchase_item->shipping_item;
			}

			$brand_purchase->shipping->items = $shipping_items;
		}
	}

	public function update_shipping_items(Model_Brand_Purchase $brand_purchase)
	{
		if ($brand_purchase->shipping)
		{
			if (($items = $brand_purchase->items('shipping')))
			{
				$items[0]->reference = $brand_purchase->shipping;
			}
			else
			{
				$brand_purchase->items []= Jam::build('purchase_item_shipping', array(
					'is_payable' => TRUE,
					'reference' => $brand_purchase->shipping,
				));
			}
			$brand_purchase->items = $brand_purchase->items;

			if ($brand_purchase->shipping_address()->changed())
			{
				$brand_purchase->shipping->update_items_address($brand_purchase->shipping);
			}
		}
	}

	public function model_call_shipping_country(Model_Brand_Purchase $brand_purchase, Jam_Event_Data $data)
	{
		$data->return = $brand_purchase->get_insist('purchase')->shipping_country();
	}

	public function model_call_shipping_address(Model_Brand_Purchase $brand_purchase, Jam_Event_Data $data)
	{
		$data->return = $brand_purchase->get_insist('purchase')->shipping_address();
	}

}
