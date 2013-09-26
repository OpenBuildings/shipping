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

		$this->assertEquals($expected, $purchase->shipping_country());

		$purchase->shipping_same_as_billing = FALSE;

		$this->assertNull($purchase->shipping_country());

		$expected = Jam::find('location', 'Russia');

		$purchase->build('shipping_address', array('country' => $expected));

		$this->assertEquals($expected, $purchase->shipping_country());
	}

}