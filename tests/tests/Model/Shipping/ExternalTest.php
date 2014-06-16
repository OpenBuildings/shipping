<?php

/**
 * Functest_TestsTest
 *
 * @group model.shipping_external
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
	 * @dataProvider data_get_weight
	 * @covers Model_Shipping_External::get_weight
	 */
	public function test_get_weight($width, $height, $depth, $weight, $expected_weight)
	{
		$shipping = Jam::build('shipping_external', array(
			'width' => $width,
			'height' => $height,
			'depth' => $depth,
			'weight' => $weight,
		));

		$this->assertEquals($expected_weight, $shipping->get_weight());
	}

	/**
	 * @covers Model_Shipping_External::external_data_for
	 */
	public function test_external_data_for()
	{
		$external_data = Jam::find('shipping_external_data', 1);
		$france = Jam::find('location', 'France');
		$uk = Jam::find('location', 'United Kingdom');
		$shipping = $this->getMock('Model_Shipping_External', array('generate_data_key'), array('shipping_external'));

		$shipping
			->expects($this->exactly(2))
			->method('generate_data_key')
			->will($this->returnValueMap(array(
				array($france, $external_data->key),
				array($uk, 'NonexistentKey'),
			)));

		$result = $shipping->external_data_for($france);

		$this->assertInstanceOf('Model_Shipping_External_Data', $result);
		$this->assertEquals($external_data->id(), $result->id());

		$result = $shipping->external_data_for($uk);

		$this->assertInstanceOf('Model_Shipping_External_Data', $result);
		$this->assertEquals('NonexistentKey', $result->key);
		$this->assertEquals('5.13', $result->price);
	}

	public function data_generate_data_key()
	{
		return array(
			array('France', '019725b593b1fbb5829b50a1bc210dc4'),
			array('Germany', '8a2e572da3d394c6b4b903e6f8554d98'),
			array('Australia', '1ccdfb47b3c23326fed75bd6aedabf48'),
		);
	}

	/**
	 * @dataProvider data_generate_data_key
	 * @covers Model_Shipping_External::generate_data_key
	 */
	public function test_generate_data_key($location_name, $expected_key)
	{
		$uk = Jam::find('location', 'United Kingdom');
		$location = Jam::find('location', $location_name);
		$shipping = Jam::build('shipping_external', array('ships_from' => $uk));

		$this->assertEquals($expected_key, $shipping->generate_data_key($location));
	}

	/**
	 * @covers Model_Shipping_External::new_shipping_item_from
	 */
	public function test_new_shipping_item_from()
	{
		$location = Jam::find('location', 'France');
		$external_data = Jam::build('shipping_external_data');
		$shipping = $this->getMock('Model_Shipping_External', array('external_data_for'), array('shipping'));

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
		$shipping = $this->getMock('Model_Shipping_External', array('external_data_for'), array('shipping'));
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
		$shipping = $this->getMock('Model_Shipping_External', array('external_data_for'), array('shipping'));
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

	public function data_is_changed()
	{
		return array(
			array('name', 'Test', FALSE),
			array('processing_time', new Jam_Range(10, 20), TRUE),
			array('ships_from_id', 10, TRUE),
			array('width', 5, TRUE),
			array('height', 5, TRUE),
			array('depth', 5, TRUE),
			array('weight', 5, TRUE),
		);
	}

	/**
	 * @dataProvider data_is_changed
	 * @covers Model_Shipping_External::is_changed
	 */
	public function test_is_changed($field, $value, $expected)
	{
		$shipping = Jam::build('shipping_external');
		$shipping->set($field, $value);
		$this->assertEquals($expected, $shipping->is_changed());
	}

	/**
	 * @covers Model_Shipping_External::price_for_location
	 */
	public function test_price_for_location()
	{
		$shipping = $this->getMock('Model_Shipping_External', array('external_data_for'), array('shipping'));
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
		$shipping = Jam::build('shipping_external');
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
		$shipping = Jam::build('shipping_external');
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
		$shipping = $this->getMock('Model_Shipping_External', array('get_external_shipping_method'), array('shipping'));
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
		$shipping = Jam::build('shipping_external');
		$method = $shipping->get_external_shipping_method();

		$this->assertEquals('external', $method->id());
		$this->assertEquals('External', $method->name);
	}
}