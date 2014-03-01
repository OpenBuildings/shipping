<?php

/**
 * @group group.shipping.methods
 */
class Group_Shipping_MethodsTest extends Testcase_Shipping {

	public function test_construct()
	{
		$store_purchase_shipping = Jam::build('store_purchase_shipping');
		$methods = array(Jam::build('shipping_method'));
		$purchase_items = array(Jam::build('purchase_item'));

		$group_methods = new Group_Shipping_Methods($store_purchase_shipping, $methods, $purchase_items);

		$this->assertSame($store_purchase_shipping, $group_methods->store_purchase_shipping);
		$this->assertSame($methods, $group_methods->shipping_methods);
		$this->assertSame($purchase_items, $group_methods->purchase_items);
	}

	public function test_group_shipping_items()
	{
		$store_purchase_shipping = Jam::build('store_purchase_shipping');
		$methods = array(Jam::build('shipping_method'), Jam::build('shipping_method'));
		$purchase_items = array(Jam::build('purchase_item'));

		$group_methods = new Group_Shipping_Methods($store_purchase_shipping, $methods, $purchase_items);

		$group_items = $group_methods->group_shipping_items();

		$this->assertCount(2, $group_items);

		foreach ($group_items as $i => $item)
		{
			$this->assertInstanceOf('Group_Shipping_Items', $item);
			$this->assertSame($methods[$i], $item->shipping_method);
			$this->assertSame($store_purchase_shipping, $item->store_purchase_shipping);
			$this->assertSame($purchase_items, $item->purchase_items);
		}
	}
}
