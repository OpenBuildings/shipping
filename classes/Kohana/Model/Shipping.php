<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Jam Model: Location
 *
 * @package applicaiton
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Kohana_Model_Shipping extends Jam_Model {

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->associations(array(
				'groups' => Jam::association('hasmany', array(
					'foreign_model' => 'shipping_group', 
					'inverse_of' => 'shipping',
				)),
				'methods' => Jam::association('manytomany', array(
					'foreign_model' => 'shipping_method',
					'association_foreign_key' => 'method_id',
					'join_table' => 'shipping_groups',
					'readonly' => TRUE,
				)),
				'locations' => Jam::association('manytomany', array(
					'join_table' => 'shipping_groups', '
					readonly' => TRUE,
				)),
				'products' => Jam::association('hasmany', array(
					'inverse_of' => 'shipping',
				)),
				'ships_from' => Jam::association('belongsto', array(
					'foreign_model' => 'location',
				))
			))

			->fields(array(
				'id' => Jam::field('primary'),
				'name' => Jam::field('string'),
				'currency' => Jam::field('string'),
				'processing_time' => Jam::field('range'),
			))

			->validator('name', 'currency', array('present' => TRUE));
	}

	/**
	 * Use this currency throughout all the shipping calculations
	 * @return string ISO currency code
	 */
	public function currency()
	{
		return $this->currency;
	}

	public function groups_in(Model_Location $location)
	{
		$location = $this->most_specific_location_containing($location);

		if ( ! $location)
			return NULL;

		return array_filter($this->groups->as_array(), function($group) use ($location) {
			return $group->location_id == $location->id();
		});
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

	public function methods_group_key()
	{
		$method_ids = $this->methods->as_array(NULL, 'id');
		sort($method_ids);
		$method_ids = array_unique($method_ids);

		return join(',', $method_ids);
	}

	public function ships_to(Model_Location $location)
	{
		return count($this->locations_containing($location)) > 0;
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
}