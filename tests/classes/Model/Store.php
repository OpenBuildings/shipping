<?php

class Model_Store extends Kohana_Model_Store {

	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->behaviors(array(
				'shippable_store' => Jam::behavior('shippable_store'),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'name' => Jam::field('string'),
			));
	}
}
