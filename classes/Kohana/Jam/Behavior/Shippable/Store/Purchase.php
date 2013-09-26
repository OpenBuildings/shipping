<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    openbuildings\shipping
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Jam_Behavior_Shippable_Store_Purchase extends Jam_Behavior {

	/**
	 * @codeCoverageIgnore
	 */
	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$meta
			->associations(array(
				'shipping' => Jam::association('hasone', array('foreign_model' => 'store_purchase_shipping', 'inverse_of' => 'store_purchase'))
			))
			->events()
				->bind('model.update_items', array($this, 'update_shipping_items'))
				->bind('model.update_items', array($this, 'build_shipping'))
				->bind('model.filter_items', array($this, 'filter_shipping_items'));

		$behaviors = $meta->behaviors();
		$behaviors['freezable']->_associations[] = 'shipping';
	}

	public function model_call_items_by_shipping_method(Model_Store_Purchase $store_purchase, Jam_Event_Data $data)
	{
		$items = $store_purchase->items(array('can_ship' => TRUE));

		$data->return = Array_Util::group_by($items, function($item) {
			return $item->reference->shipping()->methods_group_key();
		});
	}

	public function model_call_total_delivery_time(Model_Store_Purchase $store_purchase, Jam_Event_Data $data)
	{
		$data->return = $store_purchase->get_insist('shipping')->total_delivery_time();
	}

	public function model_call_delivery_time_dates(Model_Store_Purchase $store_purchase, Jam_Event_Data $data)
	{
		$delivery_time = $store_purchase->total_delivery_time();
		$start_date = $store_purchase->payed_at() ?: time();

		$data->return = new Jam_Range(array(
			strtotime("{$start_date} + {$delivery_time->min()} weekdays"),
			strtotime("{$start_date} + {$delivery_time->max()} weekdays"),
		));
	}

	public function filter_shipping_items(Model_Store_Purchase $store_purchase, Jam_Event_Data $data, array $items, array $filter)
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
				$shipping = $item->get_insist('store_purchase')->shipping;

				if ( ! $shipping)
					continue; 

				if ($item->reference->ships_to($shipping->ship_to()) !== $filter['can_ship'])
				{
					continue;
				}
			}

			$filtered [] = $item;
		}

		$data->return = $filtered;
	}

	public function build_shipping(Model_Store_Purchase $store_purchase, Jam_Event_Data $data)
	{
		if ( ! $store_purchase->shipping AND $store_purchase->get_insist('purchase')->shipping_country())
		{
			$shippable_items = $store_purchase->items(array('shippable' => TRUE));

			if ($shippable_items)
			{
				$store_purchase
					->build('shipping')
						->build_items_from($shippable_items);
			}
		}
	}

	public function update_shipping_items(Model_Store_Purchase $store_purchase, Jam_Event_Data $data)
	{
		if ($store_purchase->shipping AND ! $store_purchase->items('shipping'))
		{
			$store_purchase->items->build(array(
				'type' => 'shipping', 
				'reference' => $store_purchase->shipping
			));
		}
	}
}
