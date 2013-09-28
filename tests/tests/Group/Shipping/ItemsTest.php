<?php

/**
 * @group group.shipping.items
 */
class Group_Shipping_ItemsTest extends Testcase_Shipping {

	public function test_construct()
	{
		$store_purchase_shipping = Jam::build('store_purchase_shipping');
		$method = Jam::build('shipping_method');
		$purchase_items = array(Jam::build('purchase_item'));

		$group_items = new Group_Shipping_Items($store_purchase_shipping, $purchase_items, $method);

		$this->assertSame($store_purchase_shipping, $group_items->store_purchase_shipping);
		$this->assertSame($method, $group_items->shipping_method);
		$this->assertSame($purchase_items, $group_items->purchase_items);
	}

	public function test_shipping()
	{
		$method = Jam::build('shipping_method');
		$purchase_items = array(Jam::build('purchase_item'));
		$items = array(Jam::build('shipping_item'));

		$shipping = $this->getMock('Model_Store_Purchase_Shipping', array('duplicate', 'build_items_from'), array('store_purchase_shipping'));

		$shipping
			->expects($this->once())
			->method('duplicate')
			->will($this->returnSelf());

		$shipping
			->expects($this->once())
			->method('build_items_from')
			->with($this->identicalTo($purchase_items), $this->identicalTo($method))
			->will($this->returnSelf());

		$group_items = new Group_Shipping_Items($shipping, $purchase_items, $method);

		$result = $group_items->shipping();
		$this->assertSame($shipping, $result);

		$result = $group_items->shipping();
		$this->assertSame($shipping, $result);
	}

	public function test_existing_shipping_items()
	{
		$method = Jam::build('shipping_method');
		$purchase_items = array(Jam::build('purchase_item'));
		$items = array(Jam::build('shipping_item'));

		$shipping = $this->getMock('Model_Store_Purchase_Shipping', array('items_from'), array('store_purchase_shipping'));

		$shipping
			->expects($this->once())
			->method('items_from')
			->with($this->identicalTo($purchase_items))
			->will($this->returnValue($items));

		$group_items = new Group_Shipping_Items($shipping, $purchase_items, $method);

		$result = $group_items->existing_shipping_items();
		$this->assertSame($items, $result);
		
		$result = $group_items->existing_shipping_items();
		$this->assertSame($items, $result);
	}

	public function test_is_active()
	{
		$method = Jam::build('shipping_method', array('id' => 2));
		$purchase_items = array(Jam::build('purchase_item'));
		$shipping = Jam::build('store_purchase_shipping');

		$inactive_items = array(
			Jam::build('shipping_item', array('shipping_group' => array('method_id' => 2))),
			Jam::build('shipping_item', array('shipping_group' => array('method_id' => 1))),
		);

		$active_items = array(
			Jam::build('shipping_item', array('shipping_group' => array('method_id' => 2))),
			Jam::build('shipping_item', array('shipping_group' => array('method_id' => 2))),
		);

		$group_items = $this->getMock('Group_Shipping_Items', array('existing_shipping_items'), array($shipping, $purchase_items, $method));

		$group_items
			->expects($this->exactly(3))
			->method('existing_shipping_items')
			->will($this->onConsecutiveCalls($inactive_items, $active_items, array()));

		$result = $group_items->is_active();
		$this->assertFalse($result);
		
		$result = $group_items->is_active();
		$this->assertTrue($result);
		
		$result = $group_items->is_active();
		$this->assertFalse($result);	
	}

	public function test_form_value()
	{
		$method = Jam::build('shipping_method', array('id' => 2));
		$purchase_items = array(Jam::build('purchase_item'));
		$store_purchase_shipping = Jam::build('store_purchase_shipping');
		$shipping = Jam::build('store_purchase_shipping', array(
			'items' => array(
				array('purchase_item_id' => 1, 'shipping_group_id' => 11),
				array('purchase_item_id' => 2, 'shipping_group_id' => 12),
			)
		));

		$existing_shipping_items = array(
			Jam::build('shipping_item', array('id' => 5, 'purchase_item_id' => 1)),
			Jam::build('shipping_item', array('id' => 6, 'purchase_item_id' => 2)),
		);

		$group_items = $this->getMock('Group_Shipping_Items', array('shipping', 'existing_shipping_items'), array($store_purchase_shipping, $purchase_items, $method));

		$group_items
			->expects($this->once())
			->method('shipping')
			->will($this->returnValue($shipping));

		$group_items
			->expects($this->once())
			->method('existing_shipping_items')
			->will($this->returnValue($existing_shipping_items));

		$value = $group_items->form_value();

		parse_str($value, $value);

		$expected = array(
    	array('id' => 5, 'shipping_group_id' => 11),
    	array('id' => 6, 'shipping_group_id' => 12),
    );

		$this->assertEquals($expected, $value);
	}
}