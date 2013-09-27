<?php

/**
 * Functest_TestsTest 
 *
 * @group jam.behavior
 * @group jam.behavior.shippable_store_purchase
 * 
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Jam_Behavior_Shippable_Store_PurchaseTest extends Testcase_Shipping {

	/**
	 * @covers Jam_Behavior_Shippable_Store_Purchase::update_shipping_items
	 */
	public function test_update_items()
	{
		$store_purchase = Jam::find('store_purchase', 2);

		$this->assertEquals(0, $store_purchase->items_count('shipping'));
		
		$store_purchase->build('shipping', array(
			'items' => array(
				array(
					'purchase_item' => $store_purchase->items[0],
					'shipping_group' => $store_purchase->items[0]->reference->shipping()->groups[0],
				)
			)
		));

		$store_purchase->update_items();

		$this->assertEquals(1, $store_purchase->items_count('shipping'));

		$this->assertEquals(new Jam_Price(10, 'GBP'), $store_purchase->total_price('shipping'));

		$shippings = $store_purchase->items('shipping');

		$store_purchase->update_items();

		$other_shippings = $store_purchase->items('shipping');

		$this->assertSame($shippings[0], $other_shippings[0]);
	}

	/**
	 * @covers Jam_Behavior_Shippable_Store_Purchase::model_call_purchase_item_groups
	 */
	public function test_purchase_item_groups()
	{
		$shipping = $this->getMock('Model_Shipping', array('methods_group_key', 'ships_to'), array('shipping'));

		$shipping
			->expects($this->exactly(3))
				->method('methods_group_key')
				->will($this->onConsecutiveCalls('group1', 'group1', 'group2'));

		$product = Jam::build('product', array('shipping' => $shipping));

		$items = Jam_Array_Model::factory()
			->model('purchase_item')
			->load_fields(array())
			->set(array(
				array('id' => 10, 'type' => 'product', 'reference' => $product),
				array('id' => 11, 'type' => 'product', 'reference' => $product),
				array('id' => 12, 'type' => 'product', 'reference' => $product),
			));

		$store_purchase = $this->getMock('Model_Store_Purchase', array('items'), array('store_purchase'));
		$store_purchase
			->expects($this->once())
			->method('items')
			->with($this->equalTo(array('can_ship' => TRUE)))
			->will($this->returnValue($items->as_array()));

		$groups = $store_purchase->purchase_item_groups();

		$this->assertCount(2, $groups);
		$this->assertInstanceOf('Purchase_Item_Group', $groups['group1']);
		$this->assertInstanceOf('Purchase_Item_Group', $groups['group2']);
		
		$this->assertEquals(array(10, 11), $this->ids($groups['group1']->purchase_items));
		$this->assertEquals(array(12), $this->ids($groups['group2']->purchase_items));
		$this->assertSame($store_purchase, $groups['group1']->store_purchase);
		$this->assertSame($store_purchase, $groups['group2']->store_purchase);
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
	 * @covers Jam_Behavior_Shippable_Store_Purchase::filter_shipping_items
	 */
	public function test_filter_shipping_items($item_params, $filter_name, $filters_array, $expected_ids)
	{
		$location = Jam::find('location', 'Germany');

		$shipping = $this->getMock('Model_Shipping', array('ship_to'), array('shipping'));

		$shipping
			->expects($this->exactly(3))
				->method('ship_to')
				->will($this->returnValue($location));

		$store_purchase = Jam::build('store_purchase', array(
			'items' => array(
				array('id' => 100, 'type' => 'shipping'),
				array('id' => 101, 'type' => 'promotion'),
			),
			'shipping' => $shipping
		));

		foreach ($item_params as $id => $ships_to_result) 
		{
			$reference = $this->getMock('Model_Product', array('ships_to'), array('product'));

			$reference
				->expects($this->exactly(2))
					->method('ships_to')
					->with($this->identicalTo($location))
					->will($this->returnValue($ships_to_result));

			$store_purchase->items->build(array('id' => $id, 'type' => 'product', 'reference' => $reference));
		}

		$items = $store_purchase->items(array('shippable' => TRUE));
		
		$this->assertEquals(array_keys($item_params), $this->ids($items));

		$items = $store_purchase->items(array($filter_name => $location));
		
		$this->assertEquals($expected_ids, $this->ids($items));

		$items = $store_purchase->items($filters_array);
		
		$this->assertEquals($expected_ids, $this->ids($items));
	}

	/**
	 * @covers Jam_Behavior_Shippable_Store_Purchase::model_call_total_delivery_time
	 */
	public function test_model_call_total_delivery_time()
	{
		$range = new Jam_Range(array(10, 20));
		$shipping = $this->getMock('Model_Store_Purchase_Shipping', array('total_delivery_time'), array('store_purchase_shipping'));
		$shipping
			->expects($this->once())
				->method('total_delivery_time')
				->will($this->returnValue($range));

		$store_purchase = Jam::build('store_purchase', array('shipping' => $shipping));

		$this->assertSame($range, $store_purchase->total_delivery_time());
	}

	/**
	 * Jam_Behavior_Shippable_Store_Purchase::model_call_delivery_time_dates
	 */
	public function test_model_call_delivery_time_dates()
	{
		$range = new Jam_Range(array(10, 20));

		$store_purchase = $this->getMock('Model_Store_Purchase', array('total_delivery_time', 'payed_at'), array('store_purchase'));
		$store_purchase
			->expects($this->once())
				->method('total_delivery_time')
				->will($this->returnValue($range));

		$store_purchase
			->expects($this->once())
				->method('payed_at')
				->will($this->returnValue('2013-02-02 10:00:00'));

		$this->assertEquals(new Jam_Range(array(1361080800, 1362290400)), $store_purchase->delivery_time_dates());
	}


}