<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    openbuildings\shipping
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Group_Shipping_Items {

	public $shipping_method;
	public $store_purchase_shipping;
	public $purchase_items;

	protected $_shipping_for_method;
	protected $_existing_shipping_items;

	function __construct(Model_Store_Purchase_Shipping $store_purchase_shipping, $purchase_items, $shipping_method)
	{
		Array_Util::validate_instance_of($purchase_items, 'Model_Purchase_Item');

		$this->store_purchase_shipping = $store_purchase_shipping;
		$this->purchase_items = $purchase_items;
		$this->shipping_method = $shipping_method;
	}

	public function shipping()
	{
		if ( ! $this->_shipping_for_method) 
		{
			$this->_shipping_for_method = $this->store_purchase_shipping
				->duplicate()
				->build_items_from($this->purchase_items, $this->shipping_method);
		}
		return $this->_shipping_for_method;
	}

	public function existing_shipping_items()
	{
		if ($this->_existing_shipping_items === NULL)
		{
			$this->_existing_shipping_items = $this->store_purchase_shipping->items_from($this->purchase_items);
		}

		return $this->_existing_shipping_items;
	}

	public function is_active()
	{
		if ( ! ($items = $this->existing_shipping_items()))
			return FALSE;

		foreach ($items as $item) 
		{
			if ($item->get_insist('shipping_group')->method_id != $this->shipping_method->id()) 
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	public function total_price()
	{
		return $this->shipping()->total_price();
	}

	public function total_delivery_time()
	{
		return $this->shipping()->total_delivery_time();
	}

	public function form_value()
	{
		$items = $this->shipping()->items->as_array('purchase_item_id');

		$array = array();
		foreach ($this->existing_shipping_items() as $item) 
		{

			if (isset($items[$item->purchase_item_id])) 
			{
				$array []= array(
					'id' => $item->id(),
					'shipping_group_id' => $items[$item->purchase_item_id]->shipping_group_id,
				);
			}
		}
		
		return http_build_query($array);
	}

}