<?php

use OpenBuildings\Monetary\Monetary;
use OpenBuildings\Monetary\Source_Static;

/**
 * @group model.shipping_item_external
 *
 * @package Functest
 * @author Danail Kyosev
 * @copyright  (c) 2011-2014 Despark Ltd.
 */
class Model_Shipping_Item_ExternalTest extends Testcase_Shipping {

	/**
	 * @covers Model_Shipping_Item_External::group_key
	 */
	public function test_group_key()
	{
		$item = Jam::build('shipping_item_external');

		$this->assertNull($item->group_key());

		$item = Jam::build('shipping_item_external', array(
			'shipping_external_data' => array(
				'key' => '019725b593b1fbb5829b50a1bc210dc4',
			)
		));

		$this->assertEquals('019725b593b1fbb5829b50a1bc210dc4', $item->group_key());
	}

	/**
	 * @covers Model_Shipping_Item_External::price
	 */
	public function test_price()
	{
		$monetary = new Monetary('GBP', new Source_Static);
		$item = $this->getMockBuilder('Model_Shipping_Item_External')
            ->setMethods(array('currency', 'monetary'))
            ->setConstructorArgs(array('shipping_item_external'))
            ->getMock();

		$item
			->expects($this->once())
				->method('monetary')
				->will($this->returnValue($monetary));

		$item
			->expects($this->once())
				->method('currency')
				->will($this->returnValue('EUR'));

		$item->set(array(
			'shipping_external_data' => array(
				'price' => new Jam_Price(10, 'USD'),
			)
		));

		$this->assertEquals(new Jam_Price(7.5091987684914, 'EUR', $monetary), $item->price());
	}

	/**
	 * @covers Model_Shipping_Item_External::additional_item_price
	 */
	public function test_additional_item_price()
	{
		$monetary = new Monetary('GBP', new Source_Static);
		$item = $this->getMockBuilder('Model_Shipping_Item_External')
            ->setMethods(array('currency', 'monetary'))
            ->setConstructorArgs(array('shipping_item_external'))
            ->getMock();

		$item
			->expects($this->any())
				->method('monetary')
				->will($this->returnValue($monetary));

		$item
			->expects($this->any())
				->method('currency')
				->will($this->returnValue('EUR'));

		$item->set(array(
			'shipping_external_data' => array(
				'price' => new Jam_Price(10, 'USD'),
			)
		));

		$this->assertEquals($item->price(), $item->additional_item_price());
	}

	/**
	 * @covers Model_Shipping_Item_External::is_discounted
	 */
	public function test_is_discounted()
	{
		$price = new Jam_Price(10, 'GBP');

		$item = Jam::build('shipping_item_external');

		$this->assertFalse($item->is_discounted($price));
	}

	/**
	 * @covers Model_Shipping_Item_External::shipping_external_data_insist
	 */
	public function test_shipping_external_data_insist()
	{
		$external_data = Jam::build('shipping_external_data');
		$item = Jam::build('shipping_item_external', array(
			'shipping_external_data' => $external_data,
		));

		$this->assertSame($external_data, $item->shipping_external_data_insist());

		$this->setExpectedException('Kohana_Exception');
		$item->shipping_external_data = NULL;

		$this->assertSame($external_data, $item->shipping_external_data_insist());
	}

	/**
	 * @covers Model_Shipping_Item_External::shipping_insist
	 */
	public function test_shipping_insist()
	{
		$shipping = Jam::build('shipping_external_dummy');
		$item = Jam::build('shipping_item_external', array(
			'purchase_item' => array(
				'reference' => Jam::build('product', array(
					'shipping' => $shipping,
				)),
			),
		));

		$this->assertEquals($shipping, $item->shipping_insist());

		$this->setExpectedException('Kohana_Exception');
		$item->purchase_item->reference = NULL;
		$item->shipping_insist();
	}

	/**
	 * @covers Model_Shipping_Item_External::delivery_time
	 */
	public function test_delivery_time()
	{
		$item = $this->getMockBuilder('Model_Shipping_Item_External')
            ->setMethods(array('shipping_external_data_insist'))
            ->setConstructorArgs(array('shipping_item_external'))
            ->getMock();
		$range = new Jam_Range(array(10, 12), 'Model_Shipping::format_shipping_time');
		$item->delivery_time = $range;
		$external_data = Jam::build('shipping_external_data', array(
			'delivery_time' => new Jam_Range(array(5, 6), 'Model_Shipping::format_shipping_time')
		));

		$item
			->expects($this->once())
			->method('shipping_external_data_insist')
			->will($this->returnValue($external_data));

		$this->assertEquals($range, $item->delivery_time());

		$item->delivery_time = NULL;
		$this->assertEquals($external_data->delivery_time, $item->delivery_time());
	}

	/**
	 * @covers Model_Shipping_Item_External::shipping_method
	 */
	public function test_shipping_method()
	{
		$method = Jam::build('shipping_method');
		$shipping = $this->getMockBuilder('Model_Shipping_External_Dummy')
            ->setMethods(array('get_external_shipping_method'))
            ->setConstructorArgs(array('shipping_external'))
            ->getMock();
		$shipping
			->expects($this->once())
			->method('get_external_shipping_method')
			->will($this->returnValue($method));

		$item = Jam::build('shipping_item_external', array(
			'purchase_item' => array(
				'reference' => Jam::build('product', array(
					'shipping' => $shipping,
				)),
			),
		));

		$this->assertEquals($method, $item->shipping_method());
	}

	/**
	 * @covers Model_Shipping_Item_External::update_address
	 */
	public function test_update_address2()
	{
		$location = Jam::find('location', 'France');
		$location = Jam::find('location', 'France');

		$brand_purchase_shipping = $this->getMockBuilder('Model_Brand_Purchase_Shipping')
            ->setMethods(array('ship_to'))
            ->setConstructorArgs(array('brand_purchase_shipping'))
            ->getMock();
		$brand_purchase_shipping
			->expects($this->once())
			->method('ship_to')
			->will($this->returnValue($location));

		$shipping = $this->getMockBuilder('Model_Shipping_External_Dummy')
            ->setMethods(array('external_data_for'))
            ->setConstructorArgs(array('shipping_external'))
            ->getMock();
		$external_data = Jam::build('shipping_external_data');

		$shipping
			->expects($this->once())
			->method('external_data_for')
			->with($this->identicalTo($location))
			->will($this->returnValue($external_data));

		$item = Jam::build('shipping_item_external', array(
			'purchase_item' => array(
				'reference' => Jam::build('product', array(
					'shipping' => $shipping,
				)),
			),
		));

		$item->update_address($brand_purchase_shipping);

		$this->assertEquals($external_data, $item->external_shipping_data);
	}
}
