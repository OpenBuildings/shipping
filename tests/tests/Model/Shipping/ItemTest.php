<?php

use OpenBuildings\Monetary\Monetary;
use OpenBuildings\Monetary\Source_Static;

/**
 * @group model.shipping_item
 *
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Model_Shipping_ItemTest extends Testcase_Shipping {

    public function data_filter_discounted_items()
    {
        return array(
            array(array(100 => TRUE, 101 => TRUE), array()),
            array(array(100 => FALSE, 101 => TRUE), array(100)),
            array(array(100 => FALSE, 101 => FALSE), array(100, 101)),
        );
    }

    /**
     * @covers Model_Shipping_Item::filter_discounted_items
     * @dataProvider data_filter_discounted_items
     */
    public function test_filter_discounted_items($items, $expected_ids)
    {
        $total = new Jam_Price(10, 'GBP');

        $shipping_items = array();
        foreach ($items as $id => $return_is_discounted)
        {
            $shipping_group = $this->getMockBuilder('Model_Shipping_Group')
                ->setMethods(array('is_discounted'))
                ->setConstructorArgs(array('shipping_group'))
                ->getMock();

            $shipping_group
                ->expects($this->once())
                    ->method('is_discounted')
                    ->with($this->identicalTo($total))
                    ->will($this->returnValue($return_is_discounted));

            $shipping_item = Jam::build('shipping_item', array(
                'id' => $id,
                'shipping_group' => $shipping_group,
            ));

            $shipping_items []= $shipping_item;
        }

        $filtered = Model_Shipping_Item::filter_discounted_items($shipping_items, $total);

        $this->assertEquals($expected_ids, $this->ids($filtered));
    }

    public function data_sort_by_price()
    {
        $monetary = new Monetary('GBP', new Source_Static);

        return array(
            array(
                array(
                    10 => array('price' => new Jam_Price(20, 'USD', $monetary)),
                    11 => array('price' => new Jam_Price(18, 'GBP', $monetary)),
                    12 => array('price' => new Jam_Price(5, 'USD', $monetary)),
                ),
                array(11, 10, 12),
            ),
            array(
                array(
                    10 => array('price' => new Jam_Price(20, 'USD', $monetary)),
                    11 => array('price' => new Jam_Price(20, 'GBP', $monetary)),
                    12 => array('price' => new Jam_Price(5, 'USD', $monetary)),
                    13 => array('price' => new Jam_Price(45, 'USD', $monetary)),
                ),
                array(13, 11, 10, 12),
            ),
        );
    }

    /**
     * @covers Model_Shipping_Item::sort_by_price
     * @dataProvider data_sort_by_price
     */
    public function test_sort_by_price($params, $expected_ids)
    {
        $items = $this->getMockModelArray('shipping_item', $params);

        $sorted = Model_Shipping_Item::sort_by_price($items);

        $this->assertEquals($expected_ids, $this->ids($sorted));
    }

    public function data_relative_prices()
    {
        $monetary = new Monetary('GBP', new Source_Static);
        return array(
            array(
                array(
                    10 => array(
                        'price' => new Jam_Price(20, 'EUR', $monetary),
                        'total_additional_item_price' => new Jam_Price(40, 'EUR', $monetary)
                    ),
                    11 => array(
                        'price' => new Jam_Price(30, 'EUR', $monetary),
                        'total_price' => new Jam_Price(70, 'EUR', $monetary)
                    ),
                    12 => array(
                        'price' => new Jam_Price(10, 'EUR', $monetary),
                        'total_additional_item_price' => new Jam_Price(30, 'EUR', $monetary)
                    ),
                ),
                array(
                    new Jam_Price(70, 'EUR', $monetary),
                    new Jam_Price(40, 'EUR', $monetary),
                    new Jam_Price(30, 'EUR', $monetary),
                )
            ),
        );
    }

    /**
     * @dataProvider data_relative_prices
     * @covers Model_Shipping_Item::relative_prices
     */
    public function test_relative_prices($params, $expected)
    {
        $items = $this->getMockModelArray('shipping_item', $params);

        $prices = Model_Shipping_Item::relative_prices($items);

        $this->assertEquals($expected, $prices);
    }

    /**
     * @covers Model_Shipping_Item::currency
     */
    public function test_currency()
    {
        $brand_purchase_shipping = $this->getMockBuilder('Model_Brand_Purchase_Shipping')
            ->setMethods(array('currency'))
            ->setConstructorArgs(array('brand_purchase_shipping'))
            ->getMock();

        $brand_purchase_shipping
            ->expects($this->exactly(2))
                ->method('currency')
                ->will($this->onConsecutiveCalls('GBP', 'EUR'));

        $item = Jam::build('shipping_item', array('brand_purchase_shipping' => $brand_purchase_shipping));

        $this->assertEquals('GBP', $item->currency());
        $this->assertEquals('EUR', $item->currency());
    }

    /**
     * @covers Model_Shipping_Item::monetary
     */
    public function test_monetary()
    {
        $brand_purchase_shipping = $this->getMockBuilder('Model_Brand_Purchase_Shipping')
            ->setMethods(array('monetary'))
            ->setConstructorArgs(array('brand_purchase_shipping'))
            ->getMock();
        $monetary = new Monetary;

        $brand_purchase_shipping
            ->expects($this->once())
                ->method('monetary')
                ->will($this->returnValue($monetary));

        $item = Jam::build('shipping_item', array('brand_purchase_shipping' => $brand_purchase_shipping));

        $this->assertSame($monetary, $item->monetary());
    }

    /**
     * @covers Model_Shipping_Item::price
     */
    public function test_price()
    {
        $monetary = new Monetary('GBP', new Source_Static);
        $item = $this->getMockBuilder('Model_Shipping_Item')
            ->setMethods(array('currency', 'monetary'))
            ->setConstructorArgs(array('shipping_item'))
            ->getMock();

        $item
            ->expects($this->once())
                ->method('monetary')
                ->will($this->returnValue($monetary));

        $item
            ->expects($this->once())
                ->method('currency')
                ->will($this->returnValue('EUR'));

        $item->set(array(
            'shipping_group' => array(
                'price' => new Jam_Price(10, 'USD'),
            )
        ));

        $price = $item->price();

        $this->assertEquals(new Jam_Price(7.5091987684914, 'EUR', $monetary), $price);
    }

    /**
     * @covers Model_Shipping_Item::total_delivery_time
     */
    public function test_total_delivery_time()
    {
        $range = new Jam_Range(array(10, 12), 'Model_Shipping::format_shipping_time');

        $item = Jam::build('shipping_item', array(
            'shipping_group' => array(
                'delivery_time' => $range,
            ),
        ));

        $this->assertSame($range, $item->total_delivery_time());
    }

    /**
     * @covers Model_Shipping_Item::shipping_date
     */
    public function test_total_shipping_date()
    {
        $shipping_item = $this->getMockBuilder('Model_Shipping_Item')
            ->setMethods(array('total_delivery_time', 'paid_at'))
            ->setConstructorArgs(array('shipping_item'))
            ->getMock();

        $shipping_item
            ->expects($this->once())
            ->method('paid_at')
            ->will($this->returnValue('2013-01-01'));

        $shipping_item
            ->expects($this->once())
            ->method('total_delivery_time')
            ->will($this->returnValue(new Jam_Range(array(3, 10))));

        $date = $shipping_item->shipping_date();
        $expected = new Jam_Range(array(strtotime('2013-01-04'), strtotime('2013-01-15')));

        $this->assertEquals($expected, $date);
    }

    /**
     * @covers Model_Shipping_Item::paid_at
     */
    public function test_paid_at()
    {
        $brand_purchase_shipping = $this->getMockBuilder('Model_Brand_Purchase_Shipping')
            ->setMethods(array('paid_at'))
            ->setConstructorArgs(array('brand_purchase_shipping'))
            ->getMock();
        $date = '2013-01-01';

        $brand_purchase_shipping
            ->expects($this->once())
            ->method('paid_at')
            ->will($this->returnValue($date));

        $shipping_item = Jam::build('shipping_item', array('brand_purchase_shipping' => $brand_purchase_shipping));

        $this->assertEquals($date, $shipping_item->paid_at());
    }

    /**
     * @covers Model_Shipping_Item::additional_item_price
     */
    public function test_additional_item_price()
    {
        $monetary = new Monetary('GBP', new Source_Static);
        $item = $this->getMockBuilder('Model_Shipping_Item')
            ->setMethods(array('currency', 'monetary'))
            ->setConstructorArgs(array('shipping_item'))
            ->getMock();

        $item
            ->expects($this->exactly(2))
                ->method('monetary')
                ->will($this->returnValue($monetary));

        $item
            ->expects($this->exactly(2))
                ->method('currency')
                ->will($this->returnValue('EUR'));

        $item->set(array(
            'shipping_group' => array(
                'price' => new Jam_Price(10, 'USD'),
                'additional_item_price' => new Jam_Price(5, 'USD'),
            )
        ));

        $price = $item->additional_item_price();
        $this->assertEquals(new Jam_Price(3.7545993842457, 'EUR', $monetary), $price);

        $item->shipping_group->additional_item_price = NULL;

        $price = $item->additional_item_price();
        $this->assertEquals(new Jam_Price(7.5091987684914, 'EUR', $monetary), $price);
    }

    /**
     * @covers Model_Shipping_Item::quantity
     */
    public function test_quantity()
    {
        $item = Jam::build('shipping_item', array(
            'purchase_item' => array(
                'quantity' => 3
            ),
        ));

        $this->assertEquals(3, $item->quantity());
    }

    /**
     * @covers Model_Shipping_Item::shipping_insist
     */
    public function test_shipping_insist()
    {
        $shipping = Jam::build('shipping');
        $item = Jam::build('shipping_item', array(
            'shipping_group' => array(
                'shipping' => $shipping,
            ),
        ));

        $this->assertSame($shipping, $item->shipping_insist());

        $this->expectException('Kohana_Exception');
        $item->shipping_group->shipping = NULL;

        $this->assertSame($shipping, $item->shipping_insist());
    }

    /**
     * @covers Model_Shipping_Item::purchase_item_shipping
     */
    public function test_purchase_item_shipping()
    {
        $shipping = Jam::build('shipping');

        $item = Jam::build('shipping_item', array(
            'purchase_item' => array(
                'reference' => Jam::build('product', array(
                    'shipping' => $shipping,
                )),
            ),
        ));

        $this->assertSame($shipping, $item->purchase_item_shipping());

        $this->expectException('Kohana_Exception');
        $item->purchase_item->reference = NULL;

        $item->purchase_item_shipping();
    }

    /**
     * @covers Model_Shipping_Item::group_key
     */
    public function test_group_key()
    {
        $item = Jam::build('shipping_item');

        $this->assertNull($item->group_key());

        $item = Jam::build('shipping_item', array(
            'shipping_group' => array(
                'method_id' => 123,
            )
        ));

        $this->assertNull($item->group_key());

        $item = Jam::build('shipping_item', array(
            'shipping_group' => array(
                'method_id' => 123,
                'shipping' => array(
                    'ships_from_id' => 3123
                )
            )
        ));

        $this->assertEquals('123-3123', $item->group_key());
    }

    /**
     * @covers Model_Shipping_Item::is_discounted
     */
    public function test_is_discounted()
    {
        $shipping_group = $this->getMockBuilder('Model_Shipping_Group')
            ->setMethods(array('is_discounted'))
            ->setConstructorArgs(array('shipping_group'))
            ->getMock();
        $price = new Jam_Price(10, 'GBP');

        $shipping_group
            ->expects($this->exactly(3))
                ->method('is_discounted')
                ->with($this->identicalTo($price))
                ->will($this->onConsecutiveCalls(TRUE, FALSE, TRUE));

        $item = Jam::build('shipping_item', array(
            'shipping_group' => $shipping_group
        ));

        $this->assertTrue($item->is_discounted($price));
        $this->assertFalse($item->is_discounted($price));
        $this->assertTrue($item->is_discounted($price));
    }


    public function data_total_price()
    {
        $monetary = new Monetary('GBP', new Source_Static);

        return array(
            array(
                array(
                    'price' => new Jam_Price(10, 'GBP', $monetary),
                    'additional_item_price' => new Jam_Price(5, 'GBP', $monetary),
                    'quantity' => 3,
                ),
                new Jam_Price(10+5+5, 'GBP', $monetary),
            ),
            array(
                array(
                    'price' => new Jam_Price(20, 'GBP', $monetary),
                    'additional_item_price' => new Jam_Price(8, 'GBP', $monetary),
                    'quantity' => 1,
                ),
                new Jam_Price(20, 'GBP', $monetary),
            ),
        );
    }

    /**
     * @covers Model_Shipping_Item::total_price
     * @dataProvider data_total_price
     */
    public function test_total_price($params, $expected)
    {
        $item = $this->getMockFromParams('Model_Shipping_Item', $params, array('shipping_item'));

        $total_price = $item->total_price();

        $this->assertEquals($expected, $total_price);
    }

    public function data_total_additional_item_price()
    {
        $monetary = new Monetary('GBP', new Source_Static);

        return array(
            array(
                array(
                    'additional_item_price' => new Jam_Price(5, 'GBP', $monetary),
                    'quantity' => 3,
                ),
                new Jam_Price(5*3, 'GBP', $monetary),
            ),
            array(
                array(
                    'additional_item_price' => new Jam_Price(8, 'GBP', $monetary),
                    'quantity' => 1,
                ),
                new Jam_Price(8*1, 'GBP', $monetary),
            ),
        );
    }

    /**
     * @covers Model_Shipping_Item::total_additional_item_price
     * @dataProvider data_total_additional_item_price
     */
    public function test_total_additional_item_price($params, $expected)
    {
        $item = $this->getMockFromParams('Model_Shipping_Item', $params, array('shipping_item'));

        $total_additional_item_price = $item->total_additional_item_price();

        $this->assertEquals($expected, $total_additional_item_price);
    }

    /**
     * @covers Model_Shipping_Item::shipping_group_insist
     */
    public function test_shipping_group_insist()
    {
        $group = Jam::build('shipping_group');
        $item = Jam::build('shipping_item', array(
            'shipping_group' => $group,
        ));

        $this->assertSame($group, $item->shipping_group_insist());

        $this->expectException('Kohana_Exception');
        $item->shipping_group = NULL;
        $item->shipping_group_insist();
    }

    /**
     * @covers Model_Shipping_Item::shipping_method
     */
    public function test_shipping_method()
    {
        $method = Jam::build('shipping_method');
        $group = Jam::build('shipping_group', array(
            'method' => $method,
        ));
        $item = Jam::build('shipping_item', array(
            'shipping_group' => $group,
        ));

        $this->assertSame($method, $item->shipping_method());

        $item->shipping_group = NULL;
        $this->assertSame(NULL, $item->shipping_method());
    }

    public function data_update_address()
    {
        return array(
            array('France', array('France', 'France', 'United Kingdom'), 1),
            array('France', array('France', 'Greece'), 2),
            array('Australia', array('France', 'Greece'), 3),
        );
    }

    /**
     * @covers Model_Shipping_Item::update_address
     * @dataProvider data_update_address
     */
    public function test_update_address($location_name, $item_location_names, $expected_changes)
    {
        $location = Jam::find('location', $location_name);

        $brand_purchase_shipping = $this->getMockBuilder('Model_Brand_Purchase_Shipping')
            ->setMethods(array('ship_to'))
            ->setConstructorArgs(array('brand_purchase_shipping'))
            ->getMock();

        $brand_purchase_shipping
            ->expects($this->exactly(3))
            ->method('ship_to')
            ->will($this->returnValue($location));

        $shipping = $this->getMockBuilder('Model_Shipping')
            ->setMethods(array('cheapest_group_in'))
            ->setConstructorArgs(array('shipping'))
            ->getMock();

        $shipping
            ->expects($this->exactly($expected_changes))
            ->method('cheapest_group_in')
            ->with($this->identicalTo($location));

        $items = $this->getMockModelArray('shipping_item', array(
            1 => array(
                'purchase_item_shipping' => $shipping,
            ),
            2 => array(
                'purchase_item_shipping' => $shipping,
            ),
            3 => array(
                'purchase_item_shipping' => $shipping,
            ),
        ));

        foreach ($item_location_names as $i => $location_name)
        {
            if ($location_name)
            {
                $items[$i]->build('shipping_group', array('location' => Jam::find('location', $location_name)));
            }
        }

        foreach ($items as $item)
        {
            $item->update_address($brand_purchase_shipping);
        }
    }

    /**
     * @covers Model_Shipping_Item::performFreeze
     */
    public function testPerformFreeze()
    {
        $shipping_item = $this->getMockBuilder('Model_Shipping_Item')
            ->setMethods(array('total_delivery_time'))
            ->setConstructorArgs(array('shipping_item'))
            ->getMock();

        $shipping_item
            ->expects($this->once())
            ->method('total_delivery_time')
            ->will($this->returnValue('5|10'));

        $this->assertNull($shipping_item->total_delivery_time);

        $shipping_item->performFreeze();

        $this->assertEquals('5|10', $shipping_item->total_delivery_time);
    }

    /**
     * @covers Model_Shipping_Item::performUnfreeze
     */
    public function testPerformUnfreeze()
    {
        $shipping_item = Jam::build('shipping_item', array(
            'total_delivery_time' => '5|10',
        ));

        $this->assertEquals('5|10', $shipping_item->total_delivery_time);

        $shipping_item->performUnfreeze();

        $this->assertNull($shipping_item->total_delivery_time);
    }
}
