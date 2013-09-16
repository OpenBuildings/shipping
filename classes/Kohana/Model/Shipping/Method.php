<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    openbuildings\shipping
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Shipping_Method extends Jam_Model {

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->associations(array(
				'shipping' => Jam::association('belongsto', array('inverse_of' => 'methods')),
				'shipping_groups' => Jam::association('hasmany', array('inverse_of' => 'method')),
			))
			->fields(array(
				'id'         => Jam::field('primary'),
				'name'       => Jam::field('string'),
			))
			->validator('name', 'shipping', array('present' => TRUE));
	}
}