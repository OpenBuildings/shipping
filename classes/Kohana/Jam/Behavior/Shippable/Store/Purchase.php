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
				'shipping' => Jam::association('hasone', array(
					'foreign_model' => 'store_purchase_shipping', 
					'inverse_of' => 'store_purchase', 
					'dependent' => Jam_Association::DELETE,
				))
			))
			->events()
				->bind('model.update_items', array($this, 'update_shipping_items'))
				->bind('model.filter_items', array($this, 'filter_shipping_items'));

		$behaviors = $meta->behaviors();
		$behaviors['freezable']->_associations[] = 'shipping';
	}

	public function model_call_group_shipping_methods(Model_Store_Purchase $store_purchase, Jam_Event_Data $data)
	{
		$items = $store_purchase->items(array('can_ship' => TRUE));

		$groups = Array_Util::group_by($items, function($item) {
			return $item->get_insist('reference')->shipping()->methods_group_key();
		});

		$data->return = array_map(function($group) use ($store_purchase) {
			$methods = $group[0]->get_insist('reference')->shipping()->methods->as_array('id');

			return new Group_Shipping_Methods($store_purchase->shipping, $methods, $group);
		}, $groups);
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

	public function update_shipping_items(Model_Store_Purchase $store_purchase)
	{
		if ($store_purchase->shipping AND ! $store_purchase->items_count('shipping'))
		{
			$store_purchase->items->build(array(
				'type' => 'shipping', 
				'is_payable' => TRUE,
				'reference' => $store_purchase->shipping,
			));
		}
	}

	public function model_call_shipping_country(Model_Store_Purchase $store_purchase, Jam_Event_Data $data)
	{
		$data->return = $store_purchase->get_insist('purchase')->shipping_country();
	}

	public function model_call_shipping_address(Model_Store_Purchase $store_purchase, Jam_Event_Data $data)
	{
		$data->return = $store_purchase->get_insist('purchase')->shipping_address();
	}

}
