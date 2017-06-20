<?php

/**
 * @group group.shipping.items
 */
class Group_Shipping_ItemsTest extends Testcase_Shipping {

	public function data_test_parse_values()
	{
		return array(
			// Test 1
			array(
				// Value 1
				array(
					'id' => 10,
					'brand_purchases' => array(
						array(
							'id' => 20,
							'shipping' => array(
								'id' => 50,
								'items' => array(
									'0%5Bid%5D=10392&0%5Bshipping_group_id%5D=323701',
									'0%5Bid%5D=10395&0%5Bshipping_group_id%5D=350012',
								)
							)
						),
						array(
							'id' => 20,
							'shipping' => array(
								'id' => 50,
								'items' => array(
									'0%5Bid%5D=10331&0%5Bshipping_group_id%5D=323701',
									'0%5Bid%5D=10395&0%5Bshipping_group_id%5D=350012',
								)
							)
						)
					)
				),
				'brand_purchases.*.shipping.items',
				// Expected 1
				array(
					'id' => 10,
					'brand_purchases' => array(
						array(
							'id' => 20,
							'shipping' => array(
								'id' => 50,
								'items' => array(
									array(
										'id' => '10392',
										'shipping_group_id' => '323701',
									),
									array(
										'id' => '10395',
										'shipping_group_id' => '350012',
									),
								)
							)
						),
						array(
							'id' => 20,
							'shipping' => array(
								'id' => 50,
								'items' => array(
									array(
										'id' => '10331',
										'shipping_group_id' => '323701',
									),
									array(
										'id' => '10395',
										'shipping_group_id' => '350012',
									),
								)
							)
						)
					)
				),
			),
			// Test 2
			array(
				// Value 2
				array(
					'id' => 10,
					'brand_purchases' => array(
						array(
							'id' => 20,
							'shipping' => array(
								'id' => 50,
							)
						),
						array(
							'id' => 20,
							'shipping' => array(
								'id' => 50,
							)
						)
					)
				),
				'brand_purchases.*.shipping.items',
				// Expected 2
				array(
					'id' => 10,
					'brand_purchases' => array(
						array(
							'id' => 20,
							'shipping' => array(
								'id' => 50,
							)
						),
						array(
							'id' => 20,
							'shipping' => array(
								'id' => 50,
							)
						),
					),
				),
			),
			// Test 3
			array(
				// Value 3
				array(
					'id' => 10,
					'brand_purchases' => array(
						'items' => array(
							'0%5Bid%5D=10392&0%5Bshipping_group_id%5D=323701',
							'0%5Bid%5D=10395&0%5Bshipping_group_id%5D=350012',
						),
					),
				),
				'brand_purchases.items',
				// Expected 3
				array(
					'id' => 10,
					'brand_purchases' => array(
						'items' => array(
							array(
								'id' => '10392',
								'shipping_group_id' => '323701',
							),
							array(
								'id' => '10395',
								'shipping_group_id' => '350012',
							),
						),
					),
				),
			),
			// Test 4
			array(
				// Value 4
				array(
					'id' => 10,
					'brand_purchases' => array(
						array(
							'id' => 12535,
							'shipping' => array(
								'id' => 12435,
							),
						),
						array(
							'id' => 12536,
							'shipping' => array(
								'id' => 12436,
								'items' => array(
									0 => '0%5Bshipping_group_id%5D=395731&0%5Bid%5D=15237',
									1 => '0%5Bshipping_group_id%5D=395421&0%5Bid%5D=15238',
								),
							),
						),
					),
				),
				'brand_purchases.*.shipping.items',
				// Expected 4
				array(
					'id' => 10,
					'brand_purchases' => array(
						array(
							'id' => 12535,
							'shipping' => array(
								'id' => 12435,
							),
						),
						array(
							'id' => 12536,
							'shipping' => array(
								'id' => 12436,
								'items' => array(
									array(
										'id' => '15237',
										'shipping_group_id' => '395731',
									),
									array(
										'id' => '15238',
										'shipping_group_id' => '395421',
									),
								),
							),
						),
					),
				),
			),
		);
	}

	/**
	 * @dataProvider data_test_parse_values
	 * @covers Group_Shipping_Items::parse_form_values
	 * @covers Group_Shipping_Items::set_array_values
	 */
	public function test_parse_values($value, $path, $expected)
	{
		$result = Group_Shipping_Items::parse_form_values($value, $path);

		$this->assertEquals($expected, $result);
	}

	/**
	 * @covers Group_Shipping_Items::__construct
	 */
	public function test_construct()
	{
		$brand_purchase_shipping = Jam::build('brand_purchase_shipping');
		$method = Jam::build('shipping_method');
		$purchase_items = array(Jam::build('purchase_item'));

		$group_items = new Group_Shipping_Items($brand_purchase_shipping, $purchase_items, $method);

		$this->assertSame($brand_purchase_shipping, $group_items->brand_purchase_shipping);
		$this->assertSame($method, $group_items->shipping_method);
		$this->assertSame($purchase_items, $group_items->purchase_items);
	}

	/**
	 * @covers Group_Shipping_Items::total_price
	 */
	public function test_total_price()
	{
		$brand_purchase_shipping = Jam::build('brand_purchase_shipping');
		$method = Jam::build('shipping_method');
		$purchase_items = array(Jam::build('purchase_item'));

		$price = new Jam_Price(10, 'GBP');

		$shipping = $this->getMockBuilder('Model_Shipping')
            ->setMethods(array('total_price'))
            ->setConstructorArgs(array('shipping'))
            ->getMock();
		$shipping
			->expects($this->once())
			->method('total_price')
			->will($this->returnValue($price));

		$group_items = $this->getMockBuilder('Group_Shipping_Items')
            ->setMethods(array('shipping'))
            ->setConstructorArgs(array($brand_purchase_shipping, $purchase_items, $method))
            ->getMock();

		$group_items
			->expects($this->once())
			->method('shipping')
			->will($this->returnValue($shipping));

		$this->assertSame($price, $group_items->total_price());
	}


	/**
	 * @covers Group_Shipping_Items::total_delivery_time
	 */
	public function test_total_delivery_time()
	{
		$brand_purchase_shipping = Jam::build('brand_purchase_shipping');
		$method = Jam::build('shipping_method');
		$purchase_items = array(Jam::build('purchase_item'));

		$range = new Jam_Range(array(10, 20));

		$shipping = $this->getMockBuilder('Model_Shipping')
            ->setMethods(array('total_delivery_time'))
            ->setConstructorArgs(array('shipping'))
            ->getMock();
		$shipping
			->expects($this->once())
			->method('total_delivery_time')
			->will($this->returnValue($range));

		$group_items = $this->getMockBuilder('Group_Shipping_Items')
            ->setMethods(array('shipping'))
            ->setConstructorArgs(array($brand_purchase_shipping, $purchase_items, $method))
            ->getMock();

		$group_items
			->expects($this->once())
			->method('shipping')
			->will($this->returnValue($shipping));

		$this->assertSame($range, $group_items->total_delivery_time());
	}

	/**
	 * @covers Group_Shipping_Items::shipping
	 */
	public function test_shipping()
	{
		$method = Jam::build('shipping_method');
		$purchase_items = array(Jam::build('purchase_item'));

		$shipping = $this->getMockBuilder('Model_Brand_Purchase_Shipping')
            ->setMethods(array('duplicate', 'build_items_from'))
            ->setConstructorArgs(array('brand_purchase_shipping'))
            ->getMock();

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

	/**
	 * @covers Group_Shipping_Items::existing_shipping_items
	 */
	public function test_existing_shipping_items()
	{
		$method = Jam::build('shipping_method');
		$purchase_items = array(Jam::build('purchase_item'));
		$items = array(Jam::build('shipping_item'));

		$shipping = $this->getMockBuilder('Model_Brand_Purchase_Shipping')
            ->setMethods(array('items_from'))
            ->setConstructorArgs(array('brand_purchase_shipping'))
            ->getMock();

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

	/**
	 * @covers Group_Shipping_Items::is_active
	 */
	public function test_is_active()
	{
		$method = Jam::build('shipping_method', array('id' => 2));
		$purchase_items = array(Jam::build('purchase_item'));
		$shipping = Jam::build('brand_purchase_shipping');

		$active_items = array(
			Jam::build('shipping_item', array('shipping_group' => array('method_id' => 2))),
			Jam::build('shipping_item', array('shipping_group' => array('method_id' => 2))),
		);

		$inactive_items = array(
			Jam::build('shipping_item', array('shipping_group' => array('method_id' => 2))),
			Jam::build('shipping_item', array('shipping_group' => array('method_id' => 1))),
		);

		$items_without_shipping_group= array(
			Jam::build('shipping_item', array('shipping_group' => array('method_id' => 2))),
			Jam::build('shipping_item', array('shipping_group' => NULL)),
		);

		$group_items = $this->getMockBuilder('Group_Shipping_Items')
			->setMethods(array('existing_shipping_items'))
			->setConstructorArgs(array($shipping, $purchase_items, $method))
			->getMock();

		$group_items
			->expects($this->exactly(4))
			->method('existing_shipping_items')
			->will($this->onConsecutiveCalls($active_items, $inactive_items, $items_without_shipping_group, array()));

		$result = $group_items->is_active();
		$this->assertTrue($result);

		$result = $group_items->is_active();
		$this->assertFalse($result);

		$result = $group_items->is_active();
		$this->assertFalse($result);

		$result = $group_items->is_active();
		$this->assertFalse($result);
	}

	/**
	 * @covers Group_Shipping_Items::form_value
	 */
	public function test_form_value()
	{
		$method = Jam::build('shipping_method', array('id' => 2));
		$purchase_items = array(Jam::build('purchase_item'));
		$brand_purchase_shipping = Jam::build('brand_purchase_shipping');
		$shipping = Jam::build('brand_purchase_shipping', array(
			'items' => array(
				array('purchase_item_id' => 1, 'shipping_group_id' => 11),
				array('purchase_item_id' => 2, 'shipping_group_id' => 12),
				array('purchase_item_id' => 3, 'shipping_group_id' => 13),
			)
		));

		$existing_shipping_items = array(
			Jam::build('shipping_item')->load_fields(array('id' => 5, 'purchase_item_id' => 1)),
			Jam::build('shipping_item')->load_fields(array('id' => 6, 'purchase_item_id' => 2)),
			Jam::build('shipping_item', array('purchase_item_id' => 3)),
		);

		$group_items = $this->getMockBuilder('Group_Shipping_Items')
            ->setMethods(array('shipping', 'existing_shipping_items'))
            ->setConstructorArgs(array($brand_purchase_shipping, $purchase_items, $method))
            ->getMock();

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
			array('purchase_item_id' => 3, 'shipping_group_id' => 13),
		);

		$this->assertEquals($expected, $value);
	}

	/**
	 * Provides test data for test_arr_path()
	 *
	 * @return array
	 */
	public function provider_arr_path()
	{
		$array = array(
			'foobar' => array('definition' => 'lost'),
			'kohana' => 'awesome',
			'users'  => array(
				1 => array('name' => 'matt'),
				2 => array('name' => 'john', 'interests' => array('hocky' => array('length' => 2), 'football' => array())),
				3 => 'frank', // Issue #3194
			),
			'object' => new ArrayObject(array('iterator' => TRUE)), // Iterable object should work exactly the same
		);

		return array(
			// Test returns default value when not given an array
			array(5, 'abc', 'xyz', 5, NULL),
			// Tests returns normal values
			array($array['foobar'], $array, 'foobar'),
			array($array['kohana'], $array, 'kohana'),
			array($array['foobar']['definition'], $array, 'foobar.definition'),
			// Custom delimiters
			array($array['foobar']['definition'], $array, 'foobar/definition', NULL, '/'),
			// We should be able to use NULL as a default, returned if the key DNX
			array(NULL, $array, 'foobar.alternatives',  NULL),
			array(NULL, $array, 'kohana.alternatives',  NULL),
			// Try using a string as a default
			array('nothing', $array, 'kohana.alternatives',  'nothing'),
			// Make sure you can use arrays as defaults
			array(array('far', 'wide'), $array, 'cheese.origins',  array('far', 'wide')),
			// Ensures path() casts ints to actual integers for keys
			array($array['users'][1]['name'], $array, 'users.1.name'),
			// Test that a wildcard returns the entire array at that "level"
			array($array['users'], $array, 'users.*'),
			// Now we check that keys after a wilcard will be processed
			array(array(2 => array(0 => 2)), $array, 'users.*.interests.*.length'),
			// See what happens when it can't dig any deeper from a wildcard
			array(NULL, $array, 'users.*.fans'),
			// Starting wildcards, issue #3269
			array(array(1 => 'matt', 2 => 'john'), $array['users'], '*.name'),
			// Path as array, issue #3260
			array($array['users'][2]['name'], $array, array('users', 2, 'name')),
			array($array['object']['iterator'], $array, 'object.iterator'),
		);
	}

	/**
	 * Tests Arr::path()
	 *
	 * @test
	 * @dataProvider provider_arr_path
	 * @param string  $path       The path to follow
	 * @param mixed   $default    The value to return if dnx
	 * @param boolean $expected   The expected value
	 * @param string  $delimiter  The path delimiter
	 */
	public function test_arr_path($expected, $array, $path, $default = NULL, $delimiter = NULL)
	{
		$this->assertSame(
			$expected,
			Group_Shipping_Items::arr_path($array, $path, $default, $delimiter)
		);
	}
}
