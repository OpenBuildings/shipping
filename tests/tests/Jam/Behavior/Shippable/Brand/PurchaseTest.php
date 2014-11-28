<?php

/**
 * @group jam.behavior
 * @group jam.behavior.shippable_brand_purchase
 *
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Jam_Behavior_Shippable_Brand_PurchaseTest extends Testcase_Shipping {

	/**
	 * @covers Jam_Behavior_Shippable_Brand_Purchase::update_shipping_items
	 */
	public function test_update_shipping_items()
	{
		$brand_purchase = Jam::find('brand_purchase', 2);
		$france = Jam::find('location', 'France');

		$this->assertEquals(0, $brand_purchase->items_count('shipping'));

		$brand_purchase_shipping = $this->getMock('Model_Brand_Purchase_Shipping', array('update_items_address'), array('brand_purchase_shipping'));

		$brand_purchase_shipping
			->expects($this->once())
			->method('update_items_address')
			->with($this->identicalTo($brand_purchase_shipping));

		$brand_purchase_shipping->items = array(
			array(
				'purchase_item' => $brand_purchase->items[0],
				'shipping_group' => $brand_purchase->items[0]->reference->shipping()->groups[0],
			)
		);

		$brand_purchase->items[0]->shipping_item = $brand_purchase_shipping->items[0];

		$brand_purchase->shipping = $brand_purchase_shipping;

		$brand_purchase->update_items();

		$this->assertEquals(1, $brand_purchase->items_count('shipping'));

		$this->assertEquals(new Jam_Price(10, 'GBP', $brand_purchase->monetary(), 'GBP'), $brand_purchase->total_price('shipping'));

		$shippings = $brand_purchase->items('shipping');

		$brand_purchase->shipping_address()->country = $france;

		$brand_purchase->update_items();

		$other_shippings = $brand_purchase->items('shipping');

		$this->assertSame($shippings[0], $other_shippings[0]);
	}

	/**
	 * @covers Jam_Behavior_Shippable_Brand_Purchase::add_brand_purchase_shipping
	 */
	public function test_add_brand_purchase_shipping()
	{
		$brand_purchase = Jam::find('brand_purchase', 2);

		$this->assertNull($brand_purchase->shipping);

		$brand_purchase->update_items();

		$this->assertNotNull($brand_purchase->shipping);
		$this->assertFalse($brand_purchase->shipping->loaded());
		$this->assertEquals(1, $brand_purchase->items_count('shipping'));

		$this->assertCount(1, $brand_purchase->shipping->items);
		$this->assertSame($brand_purchase->items[0], $brand_purchase->shipping->items[0]->purchase_item);
	}

	/**
	 * @covers Jam_Behavior_Shippable_Brand_Purchase::model_call_group_shipping_methods
	 */
	public function test_group_shipping_methods()
	{
		$location = Jam::build('location');
		$post = Jam::build('shipping_method')->load_fields(array('id' => 1, 'name' => 'Post'));
		$courier = Jam::build('shipping_method')->load_fields(array('id' => 1, 'name' => 'Courier'));

		$shipping = $this->getMock('Model_Shipping', array('methods_for', 'ships_to'), array('shipping'));

		$shipping
			->expects($this->exactly(3))
				->method('methods_for')
				->with($this->identicalTo($location))
				->will($this->onConsecutiveCalls(array(1 => $post), array(1 => $post), array(1 => $post, 2 => $courier)));

		$product = Jam::build('product', array('shipping' => $shipping));

		$items = Jam_Array_Model::factory()
			->model('purchase_item')
			->load_fields(array())
			->set(array(
				array(
					'id' => 10,
					'model' => 'purchase_item_product',
					'reference' => $product
				),
				array(
					'id' => 11,
					'model' => 'purchase_item_product',
					'reference' => $product
				),
				array(
					'id' => 12,
					'model' => 'purchase_item_product',
					'reference' => $product
				),
			));

		$brand_purchase = $this->getMock('Model_Brand_Purchase', array('items', 'shipping_country'), array('brand_purchase'));
		$brand_purchase_shipping = $brand_purchase->build('shipping');

		$brand_purchase
			->expects($this->any())
			->method('shipping_country')
			->will($this->returnValue($location));

		$brand_purchase
			->expects($this->once())
			->method('items')
			->with($this->equalTo(array('can_ship' => TRUE)))
			->will($this->returnValue($items->as_array()));

		$groups = $brand_purchase->group_shipping_methods();

		$this->assertCount(2, $groups);
		$this->assertInstanceOf('Group_Shipping_methods', $groups['1']);
		$this->assertInstanceOf('Group_Shipping_methods', $groups['1,2']);

		$this->assertEquals(array(10, 11), $this->ids($groups['1']->purchase_items));
		$this->assertEquals(array(12), $this->ids($groups['1,2']->purchase_items));
		$this->assertSame($brand_purchase_shipping, $groups['1']->brand_purchase_shipping);
		$this->assertSame($brand_purchase_shipping, $groups['1,2']->brand_purchase_shipping);
	}

	public function data_filter_shipping_items()
	{
		return array(
			array(array(10 => TRUE, 11 => TRUE, 12 => FALSE), 'can_ship_to', array('can_ship' => TRUE), array(10, 11)),
			array(array(22 => FALSE, 23 => TRUE, 24 => FALSE), 'can_ship_to', array('can_ship' => TRUE), array(23)),
			array(array(22 => FALSE, 23 => TRUE, 24 => FALSE), 'cannot_ship_to', array('can_ship' => FALSE), array(22, 24)),
		);
	}

	/**
	 * @dataProvider data_filter_shipping_items
	 * @covers Jam_Behavior_Shippable_Brand_Purchase::filter_shipping_items
	 */
	public function test_filter_shipping_items($item_params, $filter_name, $filters_array, $expected_ids)
	{
		$location = Jam::find('location', 'Germany');

		$shipping = $this->getMock('Model_Shipping', array('ship_to'), array('shipping'));

		$shipping
			->expects($this->exactly(6))
				->method('ship_to')
				->will($this->returnValue($location));

		$brand_purchase = Jam::build('brand_purchase', array(
			'items' => array(
				array('id' => 100, 'model' => 'purchase_item_shipping'),
				array('id' => 101, 'model' => 'purchase_item_product'),
			),
			'shipping' => $shipping
		));

		foreach ($item_params as $id => $ships_to_result)
		{
			$reference = $this->getMock('Model_Product', array(
				'ships_to'
			), array(
				'product'
			));

			$reference
				->expects($this->exactly(2))
					->method('ships_to')
					->with($this->identicalTo($location))
					->will($this->returnValue($ships_to_result));

			$brand_purchase->items->build(array(
				'id' => $id,
				'model' => 'purchase_item_product',
				'reference' => $reference
			));
		}

		$items = $brand_purchase->items(array('shippable' => TRUE));

		$this->assertEquals(array_keys($item_params), $this->ids($items));

		$items = $brand_purchase->items(array($filter_name => $location));

		$this->assertEquals($expected_ids, $this->ids($items));

		$items = $brand_purchase->items($filters_array);

		$this->assertEquals($expected_ids, $this->ids($items));
	}

	/**
	 * @covers Jam_Behavior_Shippable_Brand_Purchase::model_call_total_delivery_time
	 */
	public function test_model_call_total_delivery_time()
	{
		$range = new Jam_Range(array(10, 20));
		$shipping = $this->getMock('Model_Brand_Purchase_Shipping', array('total_delivery_time'), array('brand_purchase_shipping'));
		$shipping
			->expects($this->once())
				->method('total_delivery_time')
				->will($this->returnValue($range));

		$brand_purchase = Jam::build('brand_purchase', array('shipping' => $shipping));

		$this->assertSame($range, $brand_purchase->total_delivery_time());
	}

	/**
	 * @covers Jam_Behavior_Shippable_Brand_Purchase::model_call_shipping_country
	 */
	public function test_model_call_shipping_country()
	{
		$location = Jam::find('location', 'France');
		$purchase = $this->getMock('Model_Purchase', array('shipping_country'), array('model_purchase'));
		$purchase
			->expects($this->once())
				->method('shipping_country')
				->will($this->returnValue($location));

		$brand_purchase = Jam::build('brand_purchase', array('purchase' => $purchase));

		$this->assertSame($location, $brand_purchase->shipping_country());
	}

	/**
	 * @covers Jam_Behavior_Shippable_Brand_Purchase::model_call_shipping_address
	 */
	public function test_model_call_shipping_address()
	{
		$address = Jam::find('address', 1);
		$purchase = $this->getMock('Model_Purchase', array('shipping_address'), array('model_purchase'));
		$purchase
			->expects($this->once())
				->method('shipping_address')
				->will($this->returnValue($address));

		$brand_purchase = Jam::build('brand_purchase', array('purchase' => $purchase));

		$this->assertSame($address, $brand_purchase->shipping_address());
	}


	/**
	 * Jam_Behavior_Shippable_Brand_Purchase::model_call_delivery_time_dates
	 */
	public function test_model_call_delivery_time_dates()
	{
		$range = new Jam_Range(array(10, 20));

		$brand_purchase = $this->getMock('Model_Brand_Purchase', array('total_delivery_time', 'payed_at'), array('brand_purchase'));
		$brand_purchase
			->expects($this->once())
				->method('total_delivery_time')
				->will($this->returnValue($range));

		$brand_purchase
			->expects($this->once())
				->method('payed_at')
				->will($this->returnValue('2013-02-02 10:00:00'));

		$this->assertEquals(new Jam_Range(array(1361080800, 1362290400)), $brand_purchase->delivery_time_dates());
	}
}
