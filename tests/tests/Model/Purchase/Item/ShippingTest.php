<?php

/**
 * @group model
 * @group model.purchase_item_shipping
 */
class Model_Purchase_Item_ShippingTest extends Testcase_Shipping {

	/**
	 * @covers Model_Purchase_Item_Shipping::initialize
	 */
	public function test_initialize()
	{
		$meta = Jam::meta('purchase_item_shipping');
		$this->assertSame('purchase_items', $meta->table());

		$association = $meta->association('shipping_item');
		$this->assertInstanceOf('Jam_Association_Hasone', $association);

		$this->assertSame('purchase_item', $association->inverse_of);
		$this->assertSame(Jam_Association::DELETE, $association->dependent);

		$this->assertTrue($meta->field('is_payable')->default);
	}

	/**
	 * @covers Model_Purchase_Item_Shipping::get_price
	 */
	public function test_get_price()
	{
		$mock = $this->getMock('stdClass', array(
			'price_for_purchase_item'
		));

		$purchase_item = $this->getMock('Model_Purchase_Item_Shipping', array(
			'get_reference_paranoid'
		), array(
			'purchase_item_shipping'
		));

		$purchase_item
			->expects($this->once())
			->method('get_reference_paranoid')
			->will($this->returnValue($mock));

		$mock
			->expects($this->once())
			->method('price_for_purchase_item')
			->with($purchase_item)
			->will($this->returnValue(10.25));

		$this->assertSame(10.25, $purchase_item->get_price());
	}
}