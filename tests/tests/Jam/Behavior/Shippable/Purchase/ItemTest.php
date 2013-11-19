<?php

class Jam_Behavior_Shippable_Purchase_ItemTest extends PHPUnit_Framework_TestCase {

	public function test_initialize()
	{
		$this->assertInstanceOf(
			'Jam_Association_Hasone',
			Jam::meta('purchase_item_product')->association('shipping_item')
		);
	}
}
