<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    openbuildings\shipping
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Shipping extends Jam_Model {

	public static function format_shipping_time($min, $max)
	{
		if ($min === NULL AND $max === NULL)
			return '-';

		if ($min == 0 AND $max == 0)
			return 'same day';

		if ($min == 1 AND $max == 1)
			return '1 day';

		return $min == $max ? "{$min} days" : "{$min} - {$max} days";
	}

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->behaviors(array(
				'paranoid' => Jam::behavior('paranoid'),
			))
			->associations(array(
				'groups' => Jam::association('hasmany', array(
					'foreign_model' => 'shipping_group',
					'inverse_of' => 'shipping',
					'delete_on_remove' => Jam_Association::DELETE,
				)),
				'methods' => Jam::association('manytomany', array(
					'foreign_model' => 'shipping_method',
					'association_foreign_key' => 'method_id',
					'join_table' => 'shipping_groups',
					'readonly' => TRUE,
				)),
				'locations' => Jam::association('manytomany', array(
					'join_table' => 'shipping_groups',
					'join_table_paranoid' => TRUE,
					'readonly' => TRUE,
				)),
				'products' => Jam::association('hasmany', array(
					'inverse_of' => 'shipping',
				)),
				'ships_from' => Jam::association('belongsto', array(
					'foreign_model' => 'location',
				)),
				'store' => Jam::association('belongsto', array(
					'inverse_of' => 'shippings'
				)),
			))

			->fields(array(
				'id' => Jam::field('primary'),
				'model' => Jam::field('polymorphic'),
				'name' => Jam::field('string'),
				'currency' => Jam::field('string'),
				'processing_time' => Jam::field('range', array('format' => 'Model_Shipping::format_shipping_time')),
			))

			->validator('name', 'currency', array('present' => TRUE))
			->validator('currency', array('currency' => TRUE))
			->validator('processing_time', array('range' => array('consecutive' => TRUE)));
	}

	/**
	 * Use this currency throughout all the shipping calculations
	 * @return string ISO currency code
	 */
	public function currency()
	{
		return $this->currency;
	}

	protected function groups_in(Model_Location $location)
	{
		$location = $this->most_specific_location_containing($location);

		if ( ! $location)
			return NULL;

		return array_filter($this->groups->as_array(), function($group) use ($location) {
			return ($group->location_id == $location->id() AND $group->price);
		});
	}

	public function cheapest_group_in(Model_Location $location)
	{
		$groups = $this->groups_in($location);

		if ( ! $groups)
			return NULL;

		$groups = Model_Shipping_Group::sort_by_price($groups);

		return end($groups);
	}

	public function group_for(Model_Location $location, Model_Shipping_Method $method)
	{
		$location = $this->most_specific_location_containing($location);

		if ( ! $location)
			return NULL;

		foreach ($this->groups->as_array() as $group)
		{
			if ($group->location_id == $location->id() AND $group->method_id == $method->id())
				return $group;
		}
	}

	public function methods_for($location)
	{
		$groups = $location ? $this->groups_in($location) : $this->groups;
		$methods = array();
		foreach ($groups as $group)
		{
			$methods[$group->method_id] = $group->method;
		}
		return $methods;
	}

	public function ships_to(Model_Location $location)
	{
		return count($this->locations_containing($location)) > 0;
	}

	public function total_delivery_time_for(Model_Location $location)
	{
		$delivery_time = $this->delivery_time_for($location);

		if ( ! $delivery_time OR ! $this->processing_time)
			return NULL;

		$format = $this->meta()->field('processing_time')->format;

		return Jam_Range::sum(array($this->processing_time, $delivery_time), $format);
	}

	public function delivery_time_for(Model_Location $location)
	{
		$groups = $this->groups_in($location);

		if ( ! $groups)
			return NULL;

		$delivery_times = array_map(function($group) {
			return $group->delivery_time;
		}, $groups);

		return Jam_Range::merge($delivery_times, 'Model_Shipping::format_shipping_time');
	}

	public function delivery_time()
	{
		$delivery_times = $this->groups->as_array(NULL, 'delivery_time');

		if ( ! $delivery_times)
			return NULL;

		return Jam_Range::merge(array_filter($delivery_times), 'Model_Shipping::format_shipping_time');
	}

	public function locations_containing(Model_Location $location)
	{
		// Get by unique id (flattening duplicates)
		$locations = $this->locations->as_array('id');

		return array_filter($locations, function($item) use ($location) {
			return $item->contains($location);
		});
	}

	public function most_specific_location_containing(Model_Location $location)
	{
		$locations = $this->locations_containing($location);

		usort($locations, function($item_a, $item_b) {
			return $item_a->depth() - $item_b->depth();
		});

		return end($locations);
	}

	public function new_shipping_item_from(array $fields, Model_Location $location, Model_Shipping_Method $method = NULL)
	{
		$fields['shipping_group'] = $method ? $this->group_for($location, $method) : $this->cheapest_group_in($location);

		return Jam::build('shipping_item', $fields);
	}

	public function is_changed()
	{
		if ($this->processing_time != $this->original('processing_time')
			OR $this->ships_from_id != $this->original('ships_from_id')
			OR array_diff($this->groups->ids(), $this->groups->original_ids())
				!== array_diff($this->groups->original_ids(), $this->groups->ids())
		)
		{
			return TRUE;
		}

		foreach ($this->groups as $group)
		{
			foreach (array('price', 'additional_item_price', 'delivery_time', 'discount_threshold') as $field)
			{
				if ($group->{$field} != $group->original($field))
					return TRUE;
			}
		}

		return FALSE;
	}

	public function price_for_location(Model_Location $location)
	{
		$group = $this->cheapest_group_in($location);

		if ( ! $group)
			return NULL;

		return $group->price;
	}

	public function additional_price_for_location(Model_Location $location)
	{
		$group = $this->cheapest_group_in($location);

		if ( ! $group)
			return NULL;

		return $group->additional_item_price;
	}

	public function discount_threshold_for_location(Model_Location $location)
	{
		$group = $this->cheapest_group_in($location);

		if ( ! $group)
			return NULL;

		return $group->discount_threshold;
	}
}
