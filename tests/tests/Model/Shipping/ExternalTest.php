<?php

/**
 * @group model.shipping_external
 *
 * @package Functest
 * @author Danail Kyosev
 * @copyright  (c) 2011-2014 Despark Ltd.
 */
class Model_Shipping_ExternalTest extends Testcase_Shipping {

	public function data_get_weight()
	{
		return array(
			array(10, 20, 30, 1, 1.2),
			array(10, 20, 30, 2, 2),
		);
	}

	/**
	 * @covers Model_Shipping_External::new_shipping_item_from
	 */
	public function test_new_shipping_item_from()
	{
		$location = Jam::find('location', 'France');
		$external_data = Jam::build('shipping_external_data');
		$shipping = $this->getMock('Model_Shipping_External_Dummy', array('external_data_for'), array('shipping'));

		$shipping
			->expects($this->once())
			->method('external_data_for')
			->with($location)
			->will($this->returnValue($external_data));

		$item = $shipping->new_shipping_item_from(array(), $location);
		$this->assertTrue($item instanceof Model_Shipping_Item_External);
		$this->assertEquals($external_data, $item->shipping_external_data);
	}

	/**
	 * @covers Model_Shipping_External::ships_to
	 */
	public function test_ships_to()
	{
		$shipping = $this->getMock('Model_Shipping_External_Dummy', array('external_data_for'), array('shipping'));
		$france = Jam::find('location', 'France');
		$uk = Jam::find('location', 'United Kingdom');
		$external_data = Jam::build('shipping_external_data');

		$shipping
			->expects($this->exactly(2))
			->method('external_data_for')
			->will($this->returnValueMap(array(
				array($france, $external_data),
				array($uk, NULL),
			)));

		$this->assertEquals(TRUE, $shipping->ships_to($france));
		$this->assertEquals(FALSE, $shipping->ships_to($uk));
	}

	/**
	 * @covers Model_Shipping_External::delivery_time_for
	 */
	public function test_delivery_time_for()
	{
		$shipping = $this->getMock('Model_Shipping_External_Dummy', array('external_data_for'), array('shipping'));
		$france = Jam::find('location', 'France');
		$uk = Jam::find('location', 'United Kingdom');
		$external_data = Jam::build('shipping_external_data', array('delivery_time' => new Jam_Range(array(13, 20))));

		$shipping
			->expects($this->exactly(2))
			->method('external_data_for')
			->will($this->returnValueMap(array(
				array($france, $external_data),
				array($uk, NULL),
			)));

		$this->assertEquals(new Jam_Range(array(13, 20), 'Model_Shipping::format_shipping_time'), $shipping->delivery_time_for($france));
		$this->assertNull($shipping->delivery_time_for($uk));
	}

	/**
	 * @covers Model_Shipping_External::price_for_location
	 */
	public function test_price_for_location()
	{
		$shipping = $this->getMock('Model_Shipping_External_Dummy', array('external_data_for'), array('shipping'));
		$france = Jam::find('location', 'France');
		$uk = Jam::find('location', 'United Kingdom');
		$external_data = Jam::build('shipping_external_data', array('price' => 13.69));

		$shipping
			->expects($this->exactly(2))
			->method('external_data_for')
			->will($this->returnValueMap(array(
				array($france, $external_data),
				array($uk, NULL),
			)));

		$this->assertEquals($external_data->price, $shipping->price_for_location($france));
		$this->assertEquals(NULL, $shipping->price_for_location($uk));
	}

	/**
	 * @covers Model_Shipping_External::additional_price_for_location
	 */
	public function test_additional_price_for_location()
	{
		$shipping = Jam::build('shipping_external_dummy');
		$france = Jam::find('location', 'France');
		$uk = Jam::find('location', 'United Kingdom');

		$this->assertEquals(NULL, $shipping->additional_price_for_location($france));
		$this->assertEquals(NULL, $shipping->additional_price_for_location($uk));
	}

	/**
	 * @covers Model_Shipping_External::discount_threshold_for_location
	 */
	public function test_discount_threshold_for_location()
	{
		$shipping = Jam::build('shipping_external_dummy');
		$france = Jam::find('location', 'France');
		$uk = Jam::find('location', 'United Kingdom');

		$this->assertEquals(NULL, $shipping->discount_threshold_for_location($france));
		$this->assertEquals(NULL, $shipping->discount_threshold_for_location($uk));
	}

	/**
	 * @covers Model_Shipping_External::methods_for
	 */
	public function test_methods_for()
	{
		$shipping = $this->getMock('Model_Shipping_External_Dummy', array('get_external_shipping_method'), array('shipping'));
		$location = Jam::find('location', 'France');
		$method = Jam::build('shipping_method');

		$shipping
			->expects($this->once())
			->method('get_external_shipping_method')
			->will($this->returnValue($method));

		$this->assertEquals(array($method), $shipping->methods_for($location));
	}

	/**
	 * @covers Model_Shipping_External::get_external_shipping_method
	 */
	public function test_get_external_data_for()
	{
		$shipping = Jam::build('shipping_external_dummy');
		$method = $shipping->get_external_shipping_method();

		$this->assertEquals('external', $method->id());
		$this->assertEquals('External', $method->name);
	}
}
