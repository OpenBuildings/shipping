<?php

/**
 * @group group.shipping.methods
 */
class Group_Shipping_MethodsTest extends Testcase_Shipping {

	public function test_construct()
	{
		$brand_purchase_shipping = Jam::build('brand_purchase_shipping');
		$methods = array(Jam::build('shipping_method'));
		$purchase_items = array(Jam::build('purchase_item'));

		$group_methods = new Group_Shipping_Methods($brand_purchase_shipping, $methods, $purchase_items);

		$this->assertSame($brand_purchase_shipping, $group_methods->brand_purchase_shipping);
		$this->assertSame($methods, $group_methods->shipping_methods);
		$this->assertSame($purchase_items, $group_methods->purchase_items);
	}

	public function test_group_shipping_items()
	{
		$brand_purchase_shipping = Jam::build('brand_purchase_shipping');
		$methods = array(Jam::build('shipping_method'), Jam::build('shipping_method'));
		$purchase_items = array(Jam::build('purchase_item'));

		$group_methods = new Group_Shipping_Methods($brand_purchase_shipping, $methods, $purchase_items);

		$group_items = $group_methods->group_shipping_items();

		$this->assertCount(2, $group_items);

		foreach ($group_items as $i => $item)
		{
			$this->assertInstanceOf('Group_Shipping_Items', $item);
			$this->assertSame($methods[$i], $item->shipping_method);
			$this->assertSame($brand_purchase_shipping, $item->brand_purchase_shipping);
			$this->assertSame($purchase_items, $item->purchase_items);
		}
	}
}
