<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    openbuildings\shipping
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Purchase_Item_Group {

	public static function explode_indexes($array)
	{
		if ( ! is_array($array)) 
			return $array;
		
		$new_array = array();
		
		foreach ($array as $key => & $value) 
		{
			if (strpos($key, ',') !== FALSE)
			{
				foreach (explode(',', $key) as $single_key) 
				{
					$new_array[$single_key] = Purchase_Item_Group::explode_indexes($value);
				}
			}
			else
			{
				$new_array[$key] = Purchase_Item_Group::explode_indexes($value);
			}
		}

		return $new_array;
	}
	
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
		$shipping = $this->store_purchase->shipping;

		$store_purchase_shipping = Jam::build('store_purchase_shipping', array('store_purchase' => $this->store_purchase));

		// This is needed to counteract inverse_of store_purchase in store_purchase_shipping
		$this->store_purchase->shipping = $shipping;

		$store_purchase_shipping
			->build_items_from($this->purchase_items, $method);

		return $store_purchase_shipping;
	}

	public function shipping_items_for(Model_Shipping_Method $method)
	{
		return $this->store_purchase->get_insist('shipping')->items_from($this->purchase_items);
	}

	public function is_method_selected(Model_Shipping_Method $method)
	{
		$items = $this->shipping_items_for($method);

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