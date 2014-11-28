<?php

class Model_Brand extends Kohana_Model_Brand {

	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->behaviors(array(
				'shippable_brand' => Jam::behavior('shippable_brand'),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'name' => Jam::field('string'),
			));
	}
}
