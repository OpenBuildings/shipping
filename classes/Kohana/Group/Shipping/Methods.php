<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    openbuildings\shipping
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Group_Shipping_Methods {

	public $purchase_items = array();
	public $shipping_methods = array();
	public $store_purchase_shipping;

	function __construct(Model_Store_Purchase_Shipping $store_purchase_shipping, array $shipping_methods, array $purchase_items)
	{
		Array_Util::validate_instance_of($shipping_methods, 'Model_Shipping_Method');
		Array_Util::validate_instance_of($purchase_items, 'Model_Purchase_Item');

		$this->shipping_methods = $shipping_methods;
		$this->store_purchase_shipping = $store_purchase_shipping;
		$this->purchase_items = $purchase_items;
	}

	public function group_shipping_items()
	{
		$group_shipping_items = array();

		foreach ($this->shipping_methods as $shipping_method)
		{
			$group_shipping_items []= new Group_Shipping_Items(
				$this->store_purchase_shipping,
				$this->purchase_items,
				$shipping_method
			);
		}

		return $group_shipping_items;
	}
}
