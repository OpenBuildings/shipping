<?php

/**
 * Functest_TestsTest 
 *
 * @group model.shipping
 * 
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Purchase_Item_GroupTest extends Testcase_Shipping {

	public function test_construct()
	{
		$store_purchase = Jam::build('store_purchase');
		$methods = array(Jam::build('shipping_method'));
		$purchase_items = array(Jam::build('purchase_item'));

		$group = new Purchase_Item_Group($store_purchase, $methods, $purchase_items);

		$this->assertSame($store_purchase, $group->store_purchase);
		$this->assertSame($methods, $group->methods);
		$this->assertSame($purchase_items, $group->purchase_items);
	}

	public function test_build_shipping_for()
	{
		$store_purchase = Jam::find('store_purchase', 1);
		$france = Jam::find('location', 'France');
		$store_purchase->purchase->shipping_country($france);
		$post = Jam::find('shipping_method', 1);
		$shipping = $store_purchase->build('shipping');

		$purchase_items = array($store_purchase->items[0], $store_purchase->items[2]);
		$methods = array($post);

		$group = new Purchase_Item_Group($store_purchase, $methods, $purchase_items);

		$store_purchase_shipping = $group->build_shipping_for($post);

		$this->assertSame($store_purchase, $store_purchase_shipping->store_purchase);

		$this->assertCount(2, $store_purchase_shipping->items);

		foreach ($store_purchase_shipping->items as $item)
		{
			$this->assertINstanceOf('Model_Shipping_Item', $item);
			$this->assertEquals($france, $item->shipping_group->location);
			$this->assertEquals($post, $item->shipping_group->method);
		}

		$this->assertSame($purchase_items[0], $store_purchase_shipping->items[0]->purchase_item);
		$this->assertSame($purchase_items[1], $store_purchase_shipping->items[1]->purchase_item);
	}

}