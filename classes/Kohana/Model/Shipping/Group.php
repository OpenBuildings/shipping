<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    openbuildings\shipping
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Shipping_Group extends Jam_Model {
	
	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->associations(array(
				'shipping' => Jam::association('belongsto', array('inverse_of' => 'locations')),
				'method'   => Jam::association('belongsto', array('foreign_model' => 'shipping_method', 'inverse_of' => 'locations')),
				'location' => Jam::association('belongsto', array('inverse_of' => 'shipping_group')),
				'shipping_items' => Jam::association('hasmany', array('inverse_of' => 'shipping_group')),

			))
			->fields(array(
				'id'            => Jam::field('primary'),
				'price'         => Jam::field('price'),
				'delivery_time' => Jam::field('range', array('format' => ':min - :max days')),
				'additional_item_price' => Jam::field('price'),
				'discount_threshold' => Jam::field('price'),
			))
			->validator('price', 'shipping', 'location', 'method', 'delivery_time', array('present' => TRUE))
			->validator('additional_item_price', 'discount_threshold', 'price', array('price' => array('greater_than_or_equal_to' => 0)))
			->validator('delivery_time', array('range' => array('consecutive' => TRUE, 'greater_than_or_equal_to' => 0)));
	}

	/**
	 * Sort Model_Shipping_Item by price, biggest price first 
	 * @param  array  $items 
	 * @return array        
	 */
	public static function sort_by_price(array $items)
	{
		Array_Util::validate_instance_of($items, 'Model_Shipping_Group');

		usort($items, function($item1, $item2){
			return $item1->price->is(Jam_Price::GREATER_THAN, $item2->price) ? -1 : 1;
		});

		return $items;
	}

	public function total_delivery_time()
	{
		$processing_time = $this->get_insist('shipping')->processing_time;
		$format = $this->meta()->field('delivery_time')->format;
		
		return Jam_Range::sum(array(
			$this->delivery_time,
			$processing_time
		), $format);
	}


	/**
	 * Get the currency for pricing calculations
	 * @return string
	 * @throws Kohana_Exception If store_purchase_shipping is NULL
	 */
	public function currency()
	{
		return $this->get_insist('shipping')->currency();
	}

	/**
	 * Return TRUE if total is bigger than discount_threshold
	 * @return boolean
	 */
	public function is_discounted(Jam_Price $total)
	{
		return ($this->discount_threshold AND $total->is(Jam_Price::GREATER_THAN, $this->discount_threshold));
	}
}