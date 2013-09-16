<?php

/**
 * Functest_TestsTest 
 *
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
		$shipping = $this->getMock('Model_Shipping', array('currency'), array('shipping_group'));

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
}