<?php

class Model_Purchase extends Kohana_Model_Purchase {

	public static function initialize(Jam_Meta $meta)
	{
		parent::initialize($meta);
		$meta
			->behaviors(array(
				'shippable_purchase' => Jam::behavior('shippable_purchase'),
			));
	}
}
