<?php

use OpenBuildings\Monetary\Monetary;
use OpenBuildings\Monetary\Source_Static;

/**
 * @group model.shipping
 *
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Model_ShippingTest extends Testcase_Shipping {

	public function data_format_shipping_time()
	{
		return array(
			array(3, 5, '3 - 5 working days'),
			array(5, 5, '5 working days'),
			array(NULL, NULL, '-'),
			array(0, 0, 'same day'),
		);
	}

	/**
	 * @covers Model_Shipping::format_shipping_time
	 * @dataProvider data_format_shipping_time
	 */
	public function test_format_shipping_time($min, $max, $expected)
	{
		$this->assertEquals($expected, Model_Shipping::format_shipping_time($min, $max));
	}

	public function data_currency()
	{
		return array(
			array('GBP'),
			array('EUR'),
		);
	}

	/**
	 * @dataProvider data_currency
	 * @covers Model_Shipping::currency
	 */
	public function test_currency($currency)
	{
		$shipping = Jam::build('shipping', array('currency' => $currency));

		$this->assertEquals($currency, $shipping->currency());
	}

	/**
	 * @covers Model_Shipping::methods_for
	 */
	public function test_methods_for()
	{
		$shipping = Jam::find('shipping', 1);
		$united_kingdom = Jam::find('location', 'United Kingdom');

		$this->assertEquals(array('1','2','3'), array_keys($shipping->methods_for(NULL)));

		$this->assertEquals(array('2'), array_keys($shipping->methods_for($united_kingdom)));
	}

	public function data_locations_containing()
	{
		return array(
			array('Everywhere', array(1)),
			array('Europe', array(1, 2)),
			array('France', array(1, 2, 3)),
		);
	}

	/**
	 * @dataProvider data_locations_containing
	 * @covers Model_Shipping::locations_containing
	 */
	public function test_locations_containing($location_name, $expected_location_ids)
	{
		$shipping = Jam::find('shipping', 1);

		$location = Jam::find('location', $location_name);

		$locations_containing = $shipping->locations_containing($location);

		$this->assertEquals($expected_location_ids, $this->ids($locations_containing));
	}

	public function data_ships_to()
	{
		return array(
			array('France', TRUE),
			array('Germany', TRUE),
			array('Turkey', TRUE),
			array('United Kingdom', TRUE),
			array('Russia', FALSE),
		);
	}

	/**
	 * @dataProvider data_ships_to
	 * @covers Model_Shipping::ships_to
	 */
	public function test_ships_to($location_name, $expected_ships_to)
	{
		$shipping = Jam::find('shipping', 1);

		$location = Jam::find('location', $location_name);

		$ships_to = $shipping->ships_to($location);

		$this->assertEquals($expected_ships_to, $ships_to);
	}

	public function data_most_specific_location_for()
	{
		return array(
			array('France', 'France'),
			array('Germany', 'Europe'),
			array('United Kingdom', 'Europe'),
		);
	}

	/**
	 * @dataProvider data_most_specific_location_for
	 * @covers Model_Shipping::most_specific_location_containing
	 */
	public function test_most_specific_location_for($location_name, $expected_location_name)
	{
		$shipping = Jam::find('shipping', 1);

		$location = Jam::find('location', $location_name);

		$specific_location = $shipping->most_specific_location_containing($location);

		$this->assertEquals($expected_location_name, $specific_location->name());
	}

	public function data_groups_in()
	{
		return array(
			array('France', array(4, 5)),
			array('Germany', array(2)),
			array('Australia', array(1)),
		);
	}

	/**
	 * @dataProvider data_groups_in
	 * @covers Model_Shipping::groups_in
	 */
	public function test_groups_in($location_name, $expected_group_ids)
	{
		$shipping = Jam::find('shipping', 1);
		$location = Jam::find('location', $location_name);

		$groups_in = new ReflectionMethod('Model_Shipping', 'groups_in');
		$groups_in->setAccessible(TRUE);

		$groups = $groups_in->invoke($shipping, $location);

		$this->assertEquals($expected_group_ids, $this->ids($groups));
	}

	/**
	 * @covers Model_Shipping::cheapest_group_in
	 */
	public function test_cheapest_group_in()
	{
		$monetary = new Monetary('GBP', new Source_Static());
		$france = Jam::find('location', 'France');
		$australia = Jam::find('location', 'Australia');
		$uk = Jam::find('location', 'United Kingdom');
		$shipping = $this->getMockBuilder('Model_Shipping')
            ->setMethods(array('groups_in'))
            ->setConstructorArgs(array('shipping'))
            ->getMock();

		$params = array(
			array('id' => 10, 'price' => new Jam_Price(20, 'USD', $monetary)),
			array('id' => 11, 'price' => new Jam_Price(18, 'GBP', $monetary)),
			array('id' => 12, 'price' => new Jam_Price(5, 'USD', $monetary)),
		);

		$groups = $this->buildModelArray('shipping_group', $params);

		$shipping
			->expects($this->exactly(3))
			->method('groups_in')
			->will($this->returnValueMap(array(
				array($france, $groups),
				array($australia, NULL),
				array($uk, array()),
			)));

		$group = $shipping->cheapest_group_in($france);

		$this->assertInstanceOf('Model_Shipping_Group', $group);
		$this->assertEquals(12, $group->id());

		$group = $shipping->cheapest_group_in($australia);

		$this->assertNull($group);

		$group = $shipping->cheapest_group_in($uk);

		$this->assertNull($group);
	}

	public function data_group_for()
	{
		return array(
			array('France', 1, 5),
			array('Germany', 2, 2),
			array('Australia', 1, 1),
			array('Australia', 2, NULL),
		);
	}

	/**
	 * @dataProvider data_group_for
	 * @covers Model_Shipping::group_for
	 */
	public function test_group_for($location_name, $method_id, $expected)
	{
		$shipping = Jam::find('shipping', 1);

		$location = Jam::find('location', $location_name);
		$method = Jam::find('shipping_method', $method_id);

		$group = $shipping->group_for($location, $method);

		if ($expected)
		{
			$this->assertNotNull($group);
			$this->assertEquals($expected, $group->id());
		}
		else
		{
			$this->assertNull($group);
		}
	}

	/**
	 * @covers Model_Shipping::delivery_time_for
	 */
	public function test_delivery_time_for()
	{
		$france = Jam::find('location', 'France');
		$shipping = $this->getMockBuilder('Model_Shipping')
            ->setMethods(array('groups_in'))
            ->setConstructorArgs(array('shipping'))
            ->getMock();

		$group1 = Jam::build('shipping_group', array('delivery_time' => new Jam_Range(array(10, 20))));
		$group2 = Jam::build('shipping_group', array('delivery_time' => new Jam_Range(array(13, 12))));

		$shipping
			->expects($this->at(0))
			->method('groups_in')
			->with($this->identicalTo($france))
			->will($this->returnValue(NULL));

		$shipping
			->expects($this->at(1))
			->method('groups_in')
			->with($this->identicalTo($france))
			->will($this->returnValue(array($group1, $group2)));

		$this->assertNull($shipping->delivery_time_for($france));

		$this->assertEquals(new Jam_Range(array(13, 20), 'Model_Shipping::format_shipping_time'), $shipping->delivery_time_for($france));
	}

	/**
	 * @covers Model_Shipping::total_delivery_time_for
	 */
	public function test_total_delivery_time_for()
	{
		$france = Jam::find('location', 'France');
		$shipping = $this->getMockBuilder('Model_Shipping')
            ->setMethods(array('delivery_time_for'))
            ->setConstructorArgs(array('shipping'))
            ->getMock();
		$range = new Jam_Range(array(3, 5), 'Model_Shipping::format_shipping_time');

		$shipping
			->expects($this->once())
			->method('delivery_time_for')
			->with($this->identicalTo($france))
			->will($this->returnValue($range));

		$this->assertSame($range, $shipping->total_delivery_time_for($france));
	}

	/**
	 * @covers Model_Shipping::delivery_time
	 */
	public function test_delivery_time()
	{
		$shipping = Jam::build('shipping', array(
			'groups' => array(
				array('id' => 10, 'delivery_time' => array(10, 20)),
				array('id' => 11, 'delivery_time' => array(5, 30)),
				array('id' => 12, 'delivery_time' => NULL),
			)
		));

		$this->assertEquals(new Jam_Range(array(10, 30), 'Model_Shipping::format_shipping_time'), $shipping->delivery_time());
	}

	/**
	 * @covers Model_Shipping::new_shipping_item_from
	 */
	public function test_new_shipping_item_from()
	{
		$location = Jam::find('location', 'France');
		$method = Jam::find('shipping_method', 1);
		$group = Jam::build('shipping_group');
		$shipping = $this->getMockBuilder('Model_Shipping')
            ->setMethods(array('group_for', 'cheapest_group_in'))
            ->setConstructorArgs(array('shipping'))
            ->getMock();

		$shipping
			->expects($this->once())
			->method('group_for')
			->with($location, $method)
			->will($this->returnValue($group));

		$item = $shipping->new_shipping_item_from(array(), $location, $method);
		$this->assertTrue($item instanceof Model_Shipping_Item);
		$this->assertEquals($group, $item->shipping_group);

		$shipping
			->expects($this->once())
			->method('cheapest_group_in')
			->with($location)
			->will($this->returnValue($group));

		$item = $shipping->new_shipping_item_from(array(), $location);
		$this->assertTrue($item instanceof Model_Shipping_Item);
		$this->assertEquals($group, $item->shipping_group);
	}

	/**
	 * @covers Model_Shipping::price_for_location
	 */
	public function test_price_for_location()
	{
		$shipping = $this->getMockBuilder('Model_Shipping')
            ->setMethods(array('cheapest_group_in'))
            ->setConstructorArgs(array('shipping'))
            ->getMock();
		$group = Jam::find('shipping_group', 6);
		$france = Jam::find('location', 'France');
		$uk = Jam::find('location', 'United Kingdom');

		$shipping
			->expects($this->exactly(2))
			->method('cheapest_group_in')
			->will($this->returnValueMap(array(
				array($france, $group),
				array($uk, NULL),
			)));

		$this->assertEquals($group->price, $shipping->price_for_location($france));
		$this->assertEquals(NULL, $shipping->price_for_location($uk));
	}

	/**
	 * @covers Model_Shipping::additional_price_for_location
	 */
	public function test_additional_price_for_location()
	{
		$shipping = $this->getMockBuilder('Model_Shipping')
            ->setMethods(array('cheapest_group_in'))
            ->setConstructorArgs(array('shipping'))
            ->getMock();
		$group = Jam::find('shipping_group', 6);
		$france = Jam::find('location', 'France');
		$uk = Jam::find('location', 'United Kingdom');

		$shipping
			->expects($this->exactly(2))
			->method('cheapest_group_in')
			->will($this->returnValueMap(array(
				array($france, $group),
				array($uk, NULL),
			)));

		$this->assertEquals($group->additional_item_price, $shipping->additional_price_for_location($france));
		$this->assertEquals(NULL, $shipping->additional_price_for_location($uk));
	}

	/**
	 * @covers Model_Shipping::discount_threshold_for_location
	 */
	public function test_discount_threshold_for_location()
	{
		$shipping = $this->getMockBuilder('Model_Shipping')
            ->setMethods(array('cheapest_group_in'))
            ->setConstructorArgs(array('shipping'))
            ->getMock();
		$group = Jam::find('shipping_group', 6);
		$france = Jam::find('location', 'France');
		$uk = Jam::find('location', 'United Kingdom');

		$shipping
			->expects($this->exactly(2))
			->method('cheapest_group_in')
			->will($this->returnValueMap(array(
				array($france, $group),
				array($uk, NULL),
			)));

		$this->assertEquals($group->discount_threshold, $shipping->discount_threshold_for_location($france));
		$this->assertEquals(NULL, $shipping->discount_threshold_for_location($uk));
	}
}
