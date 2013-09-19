<?php

class Model_Store extends Kohana_Model_Store {

	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->behavior(array(
				'shippable_store' => Jam::association('hasmany'),
			));
	}
}