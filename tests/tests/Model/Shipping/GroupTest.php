<?php

use OpenBuildings\Monetary\Monetary;
use OpenBuildings\Monetary\Source_Static;

/**
 * @group model.shipping_group
 *
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Model_Shipping_GroupTest extends Testcase_Shipping {

	/**
	 * @covers Model_Shipping_Group::is_discounted
	 */
	public function test_is_discounted()
	{
		$group = Jam::build('shipping_group', array('discount_threshold' => new Jam_Price(10, 'GBP')));

		$big_price = new Jam_Price(20, 'GBP');
		$small_price = new Jam_Price(5, 'GBP');

		$this->assertTrue($group->is_discounted($big_price));
		$this->assertFalse($group->is_discounted($small_price));

		$group = Jam::build('shipping_group');

		$this->assertFalse($group->is_discounted($big_price));
		$this->assertFalse($group->is_discounted($small_price));
	}

	/**
	 * @covers Model_Shipping_Group::currency
	 */
	public function test_currency()
	{
		$shipping = $this->getMockBuilder('Model_Shipping')
            ->setMethods(array('currency'))
            ->setConstructorArgs(array('shipping_group'))
            ->getMock();

		$shipping
			->expects($this->at(0))
				->method('currency')
				->will($this->returnValue('GBP'));

		$shipping
			->expects($this->at(1))
				->method('currency')
				->will($this->returnValue('EUR'));

		$group = Jam::build('shipping_group', array(
			'shipping' => $shipping
		));

		$this->assertEquals('GBP', $group->currency());
		$this->assertEquals('EUR', $group->currency());
	}

	public function data_sort_by_price()
	{
		$monetary = new Monetary('GBP', new Source_Static());

		return array(
			array(
				array(
					array('id' => 10, 'price' => new Jam_Price(20, 'USD', $monetary)),
					array('id' => 11, 'price' => new Jam_Price(18, 'GBP', $monetary)),
					array('id' => 12, 'price' => new Jam_Price(5, 'USD', $monetary)),
				),
				array(11, 10, 12),
			),
			array(
				array(
					array('id' => 10, 'price' => new Jam_Price(20, 'USD', $monetary)),
					array('id' => 11, 'price' => new Jam_Price(20, 'GBP', $monetary)),
					array('id' => 12, 'price' => new Jam_Price(5, 'USD', $monetary)),
					array('id' => 13, 'price' => new Jam_Price(45, 'USD', $monetary)),
				),
				array(13, 11, 10, 12),
			),
		);
	}

	/**
	 * @dataProvider data_sort_by_price
 	 * @covers Model_Shipping_Group::sort_by_price
	 */
	public function test_sort_by_price($params, $expected_ids)
	{
		$shipping_groups = array();
		foreach ($params as $param)
		{
			$shipping_groups []= Jam::build('shipping_group', $param);
		}

		$sorted = Model_Shipping_Group::sort_by_price($shipping_groups);

		$this->assertEquals($expected_ids, $this->ids($sorted));
	}

	/**
 	 * @covers Model_Shipping_Group::total_delivery_time
	 */
	public function test_total_delivery_time()
	{
		$shipping_group = Jam::build('shipping_group', array(
			'delivery_time' => array(2, 3),
		));

		$expects = new Jam_Range(array(2, 3), 'Model_Shipping::format_shipping_time');

		$this->assertEquals($expects, $shipping_group->total_delivery_time());
	}
}
