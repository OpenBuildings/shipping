<?php

/**
 * @group jam.behavior
 * @group jam.behavior.shippable_purchase
 */
class Jam_Behavior_Shippable_PurchaseTest extends Testcase_Shipping {

	/**
	 * @covers Jam_Behavior_Shippable_Purchase::model_call_shipping_address
	 */
	public function test_shipping_address()
	{
		$purchase = Jam::find('purchase', 2);
		$expected = $purchase->billing_address;

		$this->assertSame($expected, $purchase->shipping_address());

		$expected = Jam::build('address');

		$purchase->set(array(
			'shipping_same_as_billing' => FALSE,
			'shipping_address' => $expected,
		));

		$this->assertSame($expected, $purchase->shipping_address());
	}

	/**
	 * @covers Jam_Behavior_Shippable_Purchase::model_call_shipping_country
	 */
	public function test_shipping_country()
	{
		$purchase = Jam::find('purchase', 2);
		$expected = Jam::find('location', 'United Kingdom');
		$changed = Jam::find('location', 'France');

		$this->assertEquals($expected, $purchase->shipping_country());

		$purchase->shipping_same_as_billing = FALSE;

		$this->assertNull($purchase->shipping_country(), 'Should load country from billing address');

		$expected2 = Jam::find('location', 'Russia');

		$purchase->build('shipping_address', array('country' => $expected2));

		$this->assertEquals($expected2, $purchase->shipping_country(), 'Should load country from shipping address');

		$purchase->shipping_country($changed);

		$this->assertEquals($changed, $purchase->shipping_country(), 'Should load changed country from shipping address');
		$this->assertEquals($changed, $purchase->shipping_address->country);

		$purchase->shipping_same_as_billing = TRUE;

		$this->assertEquals($expected, $purchase->shipping_country(), 'Should load from billing address, which is unchanged');

		$purchase->shipping_country($changed);

		$this->assertEquals($changed, $purchase->shipping_country(), 'Should load the changed value from billing address');
	}

	/**
	 * @covers Jam_Behavior_Shippable_Purchase::add_item
	 */
	public function test_add_item()
	{
		$purchase = Jam::find('purchase', 2);
		$france = Jam::find('location', 'France');
		$purchase->shipping_country($france);
		$product = Jam::find('product', 2);
		$perchase_item = Jam::build('purchase_item', array('reference' => $product, 'type' => 'product', 'is_payable' => TRUE));

		$purchase->add_item($product->store, $perchase_item);
		
		$item = $purchase->store_purchases[0]->shipping->items[0];

		$this->assertInstanceOf('Model_Shipping_Item', $item);
		$this->assertSame($france, $purchase->store_purchases[0]->shipping->ship_to());
		$this->assertSame($perchase_item, $item->purchase_item);
		$this->assertInstanceOf('Model_Shipping_Group', $item->shipping_group);
	}

	/**
	 * @covers Jam_Behavior_Shippable_Purchase::model_before_check
	 */
	public function test_model_before_check()
	{
		$purchase = Jam::build('purchase');

		$this->assertTrue($purchase->shipping_same_as_billing);

		$purchase->shipping_required = TRUE;

		$purchase->check();

		$this->assertEquals(array('billing_address' => array('present' => array())), $purchase->errors()->as_array());

		$purchase->billing_address = array('first_name' => 10);

		$purchase->check();

		$this->assertEquals(array('billing_address' => array('association' => array(':errors' => $purchase->billing_address->errors()))), $purchase->errors()->as_array());

		$purchase = Jam::build('purchase');
		$purchase->shipping_required = TRUE;
		$purchase->shipping_same_as_billing = FALSE;
		$purchase->check();
		
		$this->assertEquals(array('shipping_address' => array('present' => array())), $purchase->errors()->as_array());

		$purchase->shipping_address = array('first_name' => 10);

		$purchase->check();
		
		$this->assertEquals(array('shipping_address' => array('association' => array(':errors' => $purchase->shipping_address->errors()))), $purchase->errors()->as_array());
	}

}