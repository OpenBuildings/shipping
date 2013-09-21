<?php

class Model_Store_Purchase extends Kohana_Model_Store_Purchase {

	public static function initialize(Jam_Meta $meta)
	{
		parent::initialize($meta);
		$meta
			->behaviors(array(
				'shippable_store_purchase' => Jam::behavior('shippable_store_purchase'),
			));
	}
}