<?php

use OpenBuildings\Monetary\Monetary;
use OpenBuildings\Monetary\Source_Static;

/**
 * Functest_TestsTest
 *
 * @group model.shipping_item_external
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
		$item = $this->getMock(
			'Model_Shipping_Item_External',
			array('currency', 'monetary'),
			array('shipping_item_external')
		);

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
		$item = $this->getMock(
			'Model_Shipping_Item_External',
			array('currency', 'monetary'),
			array('shipping_item_external')
		);

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
}