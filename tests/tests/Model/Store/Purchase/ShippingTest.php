<?php

use OpenBuildings\Monetary\Monetary;
use OpenBuildings\Monetary\Source_Static;

/**
 * @group model.purchase
 */
class Model_Store_Purchase_ShippingTest extends Testcase_Shipping {

	/**
	 * @covers Model_Store_Purchase_Shipping::price_for_purchase_item
	 */
	public function test_price_for_purchase_item()
	{
		$expected = new Jam_Price(10, 'GBP');

		$store_purchase_shipping = $this->getMock('Model_Store_Purchase_Shipping', array('total_price'), array('store_purchase_shipping'));

		$store_purchase_shipping
			->expects($this->once())
				->method('total_price')
				->will($this->returnValue($expected));

		$result = $store_purchase_shipping->price_for_purchase_item(Jam::build('purchase_item'));

		$this->assertEquals($expected, $result);
	}

	/**
	 * @covers Model_Store_Purchase_Shipping::currency
	 */
	public function test_currency()
	{
		$store_purchase = $this->getMock('Model_Store_Purchase', array('currency'), array('store_purchase'));

		$store_purchase
			->expects($this->exactly(2))
				->method('currency')
				->will($this->onConsecutiveCalls('GBP', 'EUR'));

		$item = Jam::build('store_purchase_shipping', array('store_purchase' => $store_purchase));

		$this->assertEquals('GBP', $item->currency());
		$this->assertEquals('EUR', $item->currency());
	}

	/**
	 * @covers Model_Store_Purchase_Shipping::ship_to
	 */
	public function test_ship_to()
	{
		$france = Jam::find('location', 'France');

		$purchase = $this->getMock('Model_Purchase', array('shipping_country'), array('purchase'));

		$purchase
			->expects($this->once())
				->method('shipping_country')
				->will($this->returnValue($france));
		
		$store_purchase_shipping = Jam::build('store_purchase_shipping', array(
			'store_purchase' => array(
				'purchase' => $purchase,
			),
		));

		$this->assertSame($france, $store_purchase_shipping->ship_to());
	}

	/**
	 * @covers Model_Store_Purchase_Shipping::monetary
	 */
	public function test_monetary()
	{
		$store_purchase = $this->getMock('Model_Store_Purchase', array('monetary'), array('store_purchase'));
		$monetary = new Monetary();

		$store_purchase
			->expects($this->once())
				->method('monetary')
				->will($this->returnValue($monetary));

		$item = Jam::build('store_purchase_shipping', array('store_purchase' => $store_purchase));

		$this->assertSame($monetary, $item->monetary());
	}

	/**
	 * @covers Model_Store_Purchase_Shipping::total_purchase_price
	 */
	public function test_total_purchase_price()
	{
		$store_purchase = $this->getMock('Model_Store_Purchase', array('total_price'), array('store_purchase'));
		$price = new Jam_Price(10, 'GBP');

		$store_purchase
			->expects($this->once())
				->method('total_price')
				->with($this->equalTo(array('is_payable' => TRUE, 'not' => 'shipping')))
				->will($this->returnValue($price));

		$item = Jam::build('store_purchase_shipping', array('store_purchase' => $store_purchase));

		$this->assertSame($price, $item->total_purchase_price());
	}

	public function data_total_delivery_time()
	{
		return array(
			array(
				array(
					10 => array('total_delivery_time' => new Jam_Range(array(3, 10))), 
					12 => array('total_delivery_time' => new Jam_Range(array(2, 12))), 
					14 => array('total_delivery_time' => new Jam_Range(array(5, 22))), 
				),
				new Jam_range(array(5, 22)),
			),
			array(
				array(
					20 => array('total_delivery_time' => new Jam_Range(array(5, 5))), 
					11 => array('total_delivery_time' => new Jam_Range(array(5, 6))), 
					12 => array('total_delivery_time' => new Jam_Range(array(7, 10))), 
				),
				new Jam_range(array(7, 10)),
			),
		);
	}

	/**
	 * @dataProvider data_total_delivery_time
	 * @covers Model_Store_Purchase_Shipping::total_delivery_time
	 */
	public function test_total_delivery_time($params, $expected)
	{
		$items = $this->getMockModelArray('shipping_item', $params);

		$shipping = Jam::build('store_purchase_shipping', array(
			'items' => $items,
		));
		
		$total_delivery_time = $shipping->total_delivery_time();

		$this->assertEquals($expected, $total_delivery_time);
	}

	/**
	 * @covers Model_Store_Purchase_Shipping::shipping_date
	 */
	public function test_shipping_date()
	{
		$store_purchase_shipping = $this->getMock('Model_Store_Purchase_Shipping', array('total_delivery_time', 'paid_at'), array('store_purchase_shipping'));

		$store_purchase_shipping
			->expects($this->once())
			->method('paid_at')
			->will($this->returnValue('2013-01-01'));

		$store_purchase_shipping
			->expects($this->once())
			->method('total_delivery_time')
			->will($this->returnValue(new Jam_Range(array(3, 10))));

		$date = $store_purchase_shipping->shipping_date();
		$expected = new Jam_Range(array(strtotime('2013-01-04'), strtotime('2013-01-15')));

		$this->assertEquals($expected, $date);
	}

	/**
	 * @covers Model_Store_Purchase_Shipping::paid_at
	 */
	public function test_paid_at()
	{
		$store_purchase = $this->getMock('Model_Store_Purchase', array('paid_at'), array('store_purchase'));
		$date = '2013-01-01';

		$store_purchase
			->expects($this->once())
			->method('paid_at')
			->will($this->returnValue($date));

		$store_purchase_shipping = Jam::build('store_purchase_shipping', array('store_purchase' => $store_purchase));

		$this->assertEquals($date, $store_purchase->paid_at());
	}


	/**
	 * @covers Model_Store_Purchase_Shipping::build_item_from
	 */
	public function test_build_item_from()
	{
		$purchase_item = Jam::build('purchase_item');
		$location = Jam::build('location');
		$method = Jam::build('shipping_method');
		$expected = Jam::build('shipping_item');

		$store_purchase_shipping = $this->getMock('Model_Store_Purchase_Shipping', array('ship_to', 'new_item_from'), array('store_purchase_shipping'));

		$store_purchase_shipping
			->expects($this->once())
				->method('ship_to')
				->will($this->returnValue($location));

		$store_purchase_shipping
			->expects($this->once())
				->method('new_item_from')
				->with($this->identicalTo($purchase_item), $this->identicalTo($location), $this->identicalTo($method))
				->will($this->returnValue($expected));

		$store_purchase_shipping->build_item_from($purchase_item, $method);

		$this->assertEquals($expected, $store_purchase_shipping->items[0]);
	}

	/**
	 * @covers Model_Store_Purchase_Shipping::build_items_from
	 */
	public function test_build_items_from()
	{
		$location = Jam::build('location');
		$method = Jam::build('shipping_method');

		$expected = array(
			Jam::build('shipping_item'),
		);

		$purchase_items = array(
			Jam::build('purchase_item'),
		);

		$store_purchase_shipping = $this->getMock('Model_Store_Purchase_Shipping', array('ship_to', 'new_items_from'), array('store_purchase_shipping'));

		$store_purchase_shipping
			->expects($this->once())
				->method('ship_to')
				->will($this->returnValue($location));

		$store_purchase_shipping
			->expects($this->once())
				->method('new_items_from')
				->with($this->identicalTo($purchase_items), $this->identicalTo($location), $this->identicalTo($method))
				->will($this->returnValue($expected));

		$store_purchase_shipping->build_items_from($purchase_items, $method);

		$this->assertEquals($expected, $store_purchase_shipping->items->as_array());
	}
	/**
	 * @covers Model_Store_Purchase_Shipping::new_item_from
	 */
	public function test_new_item_from()
	{
		$store_purchase = Jam::find('store_purchase', 1);
		$shipping = $store_purchase->build('shipping');

		$france = Jam::find('location', 'France');
		$post = Jam::find('shipping_method', 1);

		$purchase_item = $store_purchase->items[0];

		$shipping_item = $shipping->new_item_from($purchase_item, $france, $post);

		$this->assertINstanceOf('Model_Shipping_Item', $shipping_item);
		$this->assertEquals($france, $shipping_item->shipping_group->location);
		$this->assertSame($shipping, $shipping_item->store_purchase_shipping);
		$this->assertEquals($post, $shipping_item->shipping_group->method);

		$this->assertSame($purchase_item, $shipping_item->purchase_item);
		$this->assertEquals($post, $shipping_item->shipping_group->method);
	}
	
	/**
	 * @covers Model_Store_Purchase_Shipping::new_items_from
	 */
	public function test_new_items_from()
	{
		$store_purchase = Jam::find('store_purchase', 1);
		$france = Jam::find('location', 'France');
		$post = Jam::find('shipping_method', 1);
		$shipping = $store_purchase->build('shipping');

		$items = array($store_purchase->items[0], $store_purchase->items[2]);

		$shipping_items = $shipping->new_items_from($items, $france, $post);

		$this->assertCount(2, $shipping_items);

		foreach ($shipping_items as $item)
		{
			$this->assertINstanceOf('Model_Shipping_Item', $item);
			$this->assertEquals($france, $item->shipping_group->location);
			$this->assertEquals($post, $item->shipping_group->method);
			$this->assertSame($shipping, $item->store_purchase_shipping);
		}

		$this->assertSame($items[0], $shipping_items[0]->purchase_item);
		$this->assertSame($items[1], $shipping_items[1]->purchase_item);
	}

	public function data_total_price()
	{
		$monetary = new Monetary('GBP', new Source_Static());
		return array(
			array(
				array(
					10 => array(
						'price' => new Jam_Price(20, 'EUR', $monetary),
						'additional_item_price' => new Jam_Price(70, 'EUR', $monetary),
						'is_discounted' => TRUE,
						'quantity' => 2,
						'group_key' => 'group1',
					),
					11 => array(
						'price' => new Jam_Price(18, 'EUR', $monetary),
						'additional_item_price' => new Jam_Price(10, 'EUR', $monetary),
						'is_discounted' => FALSE,
						'quantity' => 2,
						'group_key' => 'group2',
					),
					12 => array(
						'price' => new Jam_Price(25, 'EUR', $monetary),
						'additional_item_price' => new Jam_Price(12, 'EUR', $monetary),
						'is_discounted' => FALSE,
						'quantity' => 5,
						'group_key' => 'group2',
					),
					13 => array(
						'price' => new Jam_Price(30, 'EUR', $monetary),
						'additional_item_price' => new Jam_Price(22, 'EUR', $monetary),
						'is_discounted' => FALSE,
						'quantity' => 3,
						'group_key' => 'group3',
					),
				),
				new Jam_Price(300, 'EUR', $monetary),
				new Jam_Price(
					0
					+ 10*2
					+ 25+12*4
					+ 30+22*2
					, 'EUR', $monetary
				)
			),
		);
	}

	/**
	 * @covers Model_Store_Purchase_Shipping::items_from
	 */
	public function test_items_from()
	{
		$purchase_items = array(
			Jam::build('purchase_item', array('id' => 1)),
			Jam::build('purchase_item', array('id' => 5)),
		);
	
		$items = array(
			Jam::build('shipping_item', array('purchase_item_id' => 1)),
			Jam::build('shipping_item', array('purchase_item_id' => 3)),
			Jam::build('shipping_item', array('purchase_item_id' => 5)),
		);
	
		$store_purchase_shipping = Jam::build('store_purchase_shipping', array('items' => $items));
	
		$result = $store_purchase_shipping->items_from($purchase_items);
	
		$expected = array(
			0 => $items[0],
			2 => $items[2],
		);
	
		$this->assertEquals($expected, $result);	
	}

	/**
	 * @dataProvider data_total_price
	 * @covers Model_Store_Purchase_Shipping::total_price
	 */
	public function test_total_price($params, $total, $expected)
	{
		$store_purchase_shipping = $this->getMock('Model_Store_Purchase_Shipping', array('total_purchase_price'), array('store_purchase_shipping'));

		$store_purchase_shipping->items = $this->getMockModelArray('shipping_item', $params);

		$store_purchase_shipping
			->expects($this->once())
				->method('total_purchase_price')
				->will($this->returnValue($total));

		$total_price = $store_purchase_shipping->total_price();

		$this->assertEquals($expected, $total_price);
	}
}