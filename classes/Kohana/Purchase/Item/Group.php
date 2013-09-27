<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    openbuildings\shipping
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Purchase_Item_Group {

	public $methods = array();
	public $purchase_items = array();
	public $store_purchase;

	function __construct(Model_Store_Purchase $store_purchase, $methods, $purchase_items) 
	{
		Array_Util::validate_instance_of($purchase_items, 'Model_Purchase_Item');
		Array_Util::validate_instance_of($methods, 'Model_Shipping_Method');

		$this->methods = $methods;
		$this->purchase_items = $purchase_items;
		$this->store_purchase = $store_purchase;
	}

	public function build_shipping_for(Model_Shipping_Method $method)
	{
		$store_purchase_shipping = Jam::build('store_purchase_shipping', array('store_purchase' => $this->store_purchase));

		$store_purchase_shipping
			->build_items_from($this->purchase_items, $method);

		return $store_purchase_shipping;
	}

	public function is_method_selected(Model_Shipping_Method $method)
	{
		$items = $this->store_purchase->get_insist('shipping')->items_from($this->purchase_items);

		foreach ($items as $item) 
		{
			if ($item->get_insist('shipping_group')->method_id !== $method->id()) 
			{
				return FALSE;
			}
		}

		return TRUE;
	}
}