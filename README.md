# Purchases Shipping

[![Build Status](https://travis-ci.org/OpenBuildings/shipping.png?branch=master)](https://travis-ci.org/OpenBuildings/shipping)
[![Coverage Status](https://coveralls.io/repos/OpenBuildings/shipping/badge.png?branch=master)](https://coveralls.io/r/OpenBuildings/shipping?branch=master)
[![Latest Stable Version](https://poser.pugx.org/openbuildings/shipping/v/stable.png)](https://packagist.org/packages/openbuildings/shipping)

## Usage

You want to ship must implement Shippable, like this:

```php
class Model_Product extends Jam_Model implements Sellable, Shippable {

	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->associations(array(
				'shipping' => Jam::association('belongsto', array('inverse_of' => 'products')),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'name' => Jam::field('string'),
				'currency' => Jam::field('string'),
				'price' => Jam::field('price'),
			))
			->validator('type', 'price', 'quantity', array(
				'present' => TRUE
			));
	}

	// Implement Sellable
	public function price(Model_Purchase_Item $item)
	{
		return $this->price;
	}
	
	// Implement Sellable
	public function currency()
	{
		return $this->currency;
	}
	
	// Implement Shippable
	// Must return a ``Model_Shipping`` object holding all the data for the shipping
	public function shipping()
	{
		return $this->shipping;
	}

	// Implement Shippable
	// Must return a boolean whether or not the product ships can ship to that 
	public function ships_to(Model_Location $location)
	{
		return $this->shipping ? $this->shipping->ships_to($location) : FALSE;
	}
}
```

Also you need to add the shippable purchase to your store purchase model:

```php
class Model_Store_Purchase extends Kohana_Model_Store_Purchase {

	public static function initialize(Jam_Meta $meta)
	{
		parent::initialize($meta);
		$meta
			->behaviors(array(
				'shippable_purchase' => Jam::behavior('shippable_purchase'),
			));
	}
}
```

This behavior will add the 'shipping' association to the store_pruchase, also listen update_items event and add a shipping purchase_item, and listen to the filter_items event, adding some more flags to filter by.

Once you have added the shipping data to your products:

```php
$post = Jam::find('shipping_method', 'Post');
$europe = Jam::find('location', 'Europe');
$france = Jam::find('location', 'France');

$product->shipping = Jam::create('shipping', array(
	'currency' => 'GBP',
	'ships_from' => $france,
	'groups' => array(

		// Ships to all of Europe for 20 GBP
		array('method' => $post, 'location' => $europe, 'price' => 20),

		// Specifically for France - only 10 GBP
		array('method' => $post, 'location' => $france, 'price' => 10),
	)
));
```

You can start to select which shipping applies to each purchase item.

```php
$store_purchase = Jam::find('store_purchase', 1);

// If you want to set the informaction explicitly on which purchase_item what shipping_group to use
$store_purchase->build('shipping', array(
	'items' => array(
		array(
			'purchase_item' => $store_purchase->items[0],
			'shipping_group' => $store_purchase->items[0]->reference->shipping()->groups[0],
		),
	)
));

// Or if you want ones selected automatically, based on a preffered shipping method and purchaser location
$post = Jam::find('shipping_method', 'Post');
$france = Jam::find('location', 'France');

$store_purchase_shipping = $store_purchase->build('shipping', array(
	'location' => $france,
));

$store_purchase_shipping->build_items_from($store_purchase->items, $post);
```

Having configured that, you can now call ``update_items()`` method on the purchase / store_purchase, adding to the purchase_items a shipping item.

```php
$store_purchase->update_items();

echo $store_purchase->items_count('shipping'); // should return 1
```

### Shipping Groups and Price Calculations

Each shipping group has several properties that affect how much muney the shipping of this item will cost:

 - __price__ - this is the base price for shipping of 1 item.
 - __additional_item_price__ - for more than one item, the second, third, etc items require this price, instead of the base one. 
 - __discount_threshold__ - whenever the store_purchase is more than this amount - free shipping

Here are some examples:

If an item costs 10, with additional_item_price of 6, then you will pay 10+6+6 for 3 of the same item. 

Also items are grouped per shipping method, per "ships_from" location so 3 different item shipped by post will be grouped. Only the most expensive base price will be used, all others will use additional_item_price. So:

	Item 1: price 10, additional_item_price 6, quantity: 3
	Item 2: price 12, additional_item_price 8, quantity: 2
	
	Total Price will be (12 + 8) + 6 * 3

When searching for a country, the most specific one will be used for calculation, so if you are shipping for France, and you have a shipping_group for Europe, and one for France, the second one will be used.

### Advanced Item Splitting

If you want to allow people to use different methods for different products, here is how you might accomplish this:

First of all - finding all purchase_items that can / cannot ship to your country

```php
$available = $store_purchase->items(array('can_ship' => TRUE));
$not_shippable = $store_purchase->items(array('can_ship' => FALSE));
```

If you want to be more precise, you can get available items, but grouped by available shipping methods, so that if you have purchase_items that can ship with both _post_ and _courier_ and other that can ship only with _post_, they will be in different groups:

```php
$available = $store_purchase->items_by_shipping_method();
foreach ($available as $methods => $purchase_items)
{
	foreach ($purchae_items[0]->methods as $method)
	{
		// Get all the shipping items for these purchases, shippable to this location by this method.
		$shipping_items = Model_Shipping_Item::build_from($purchase_items, $store_purchase->shipping->location, $method);

		// Calculate the price of these items, provide a total price to remove ones that are discounted based on it.
		echo Model_Shipping_Item::compute_price($shipping_items, $store_purchase->total_price(array('is_payable')));
	}
}
```

### Delivery Times

This shipping module comes with extensive support for calculating delivery times.

Model_Shipping_Group has "delivery_time" - min - max workdays to deliver the item. Model_Shipping has process_time - min - max workdays to 'build' the item. Both of these are Jam_Ranges, and combined represent the total_delivery_time for a specific location.

Model_Shipping has this interface:

```php
$france = Jam::find('locaiton', 'France');

$shipping = $product->shipping();

// To get a Jam_Range object only for the delivery to that location
$shipping->delivery_time_for($france); 

// To get a Jam_Range for delivery + processing for a specific country
$shipping->total_delivery_time_for($france); 
```

The shippable purchase behavior also adds some methods to the Model_Store_Purchase for handling delivery time calculations:

```php
// Get the Jam_Range object for the delivery time for the store_purchase
$store_purchase->total_delivery_time();

// Get the Jam_Range object if the dates that the purchase will arraive
// This is calculate based on the time the payment was made. If its not yet payed purchase, the current time is used.
$store_purchase->delivery_time_dates();
```

### Shipping Address

By default as shipping address is used the billing address of the purchase. If you want to change that address, you'll have to change the ``shipping_same_as_billing`` field and set the shipping_address association, which is the same Model_Address object. After that all the calculation will take the country of shiping_address instead of billing_address:

```php
$purchase = Jam::find('purchase', 1);

$purchase->shipping_same_as_billing = FALSE;
$purchase->build('shipping_address', array(
  'country' => Jam::find('country', 'United Kingdom'),
  'line1' => 'Street 1',
  // ...
));

## License

Copyright (c) 2012-2013, OpenBuildings Ltd. Developed by Ivan Kerin as part of [clippings.com](http://clippings.com)

Under BSD-3-Clause license, read LICENSE file.

