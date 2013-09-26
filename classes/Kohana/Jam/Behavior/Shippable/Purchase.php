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
				'shipping_address' => Jam::association('belongsto', array('foreign_model' => 'address')),
			))
			->fields(array(
				'shipping_same_as_billing' => Jam::field('boolean', array('default' => 1)),
			));
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
