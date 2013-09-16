<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Jam Model: Location
 *
 * @package applicaiton
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
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
				'delivery_time' => Jam::field('range'),
				'additional_item_price' => Jam::field('price'),
				'discount_threshold' => Jam::field('price'),
			))
			->validator('price', 'shipping', 'location', 'method', 'delivery_time', array('present' => TRUE))
			->validator('additional_item_price', 'discount_threshold', 'price', array('price' => TRUE));
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