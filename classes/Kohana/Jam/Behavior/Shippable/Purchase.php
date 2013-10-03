<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    openbuildings\shipping
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Jam_Behavior_Shippable_Purchase extends Jam_Behavior {

	/**
	 * @codeCoverageIgnore
	 */
	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$meta
			->associations(array(
				'shipping_address' => Jam::association('belongsto', array(
					'foreign_model' => 'address',
					'dependent' => Jam_Association::DELETE,
				)),
			))
			->fields(array(
				'shipping_same_as_billing' => Jam::field('boolean', array('default' => TRUE)),
				'shipping_required' => Jam::field('boolean', array('in_db' => FALSE)),
			))
			->events()
				->bind('model.add_item', array($this, 'add_item'));
	}

	public function model_before_check(Model_Purchase $purchase, Jam_Event_Data $data)
	{
		if ($purchase->shipping_required) 
		{
			if ($purchase->shipping_same_as_billing AND ! $purchase->billing_address) 
			{
				$purchase->errors()->add('billing_address', 'present');
			}
			elseif ( ! $purchase->shipping_same_as_billing AND ! $purchase->shipping_address) 
			{
				$purchase->errors()->add('shipping_address', 'present');
			}
			else
			{
				$purchase->shipping_address()->fields_required = TRUE;	
			}
		}
	}
	
	public function add_item(Model_Purchase $purchase, Jam_Event_Data $data, Model_Purchase_Item $purchase_item)
	{
		if (($store_purchase = $purchase_item->store_purchase) AND $purchase->shipping_country())
		{
			if ( ! $store_purchase->shipping) 
			{
				$store_purchase->build('shipping');
			}

			$store_purchase->shipping->build_item_from($purchase_item);
		}
	}

	public function model_call_shipping_country(Model_Purchase $purchase, Jam_Event_Data $data, Model_Location $shipping_country = NULL)
	{
		if ($shipping_country !== NULL) 
		{
			if ($purchase->shipping_same_as_billing) 
			{
				$purchase->billing_address->country = $shipping_country;
				$purchase->billing_address = $purchase->billing_address;
			}
			else
			{
				$purchase->shipping_address->country = $shipping_country;
				$purchase->shipping_address = $purchase->shipping_address;
			}

			$data->return = $purchase;
		}

		$address = $purchase->shipping_address();

		if ($address AND $address->country)
		{
			$data->return = $address->country;
		}
	}

	public function model_call_shipping_address(Model_Purchase $purchase, Jam_Event_Data $data)
	{
		$data->return = $purchase->shipping_same_as_billing ? $purchase->billing_address : $purchase->shipping_address;
	}
}
