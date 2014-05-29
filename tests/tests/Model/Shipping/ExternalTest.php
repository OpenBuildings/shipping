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
	 * @covers Model_Shipping::get_weight
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
	 * @covers Model_Shipping::external_data_for
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
		$this->assertEquals('0.00', $result->price);
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
	 * @covers Model_Shipping::generate_data_key
	 */
	public function test_generate_data_key($location_name, $expected_key)
	{
		$uk = Jam::find('location', 'United Kingdom');
		$location = Jam::find('location', $location_name);
		$shipping = Jam::build('shipping_external', array('ships_from' => $uk));

		$this->assertEquals($expected_key, $shipping->generate_data_key($location));
	}
}