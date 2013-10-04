<?php

class Model_Purchase_Item extends Kohana_Model_Purchase_Item {

	public static function initialize(Jam_Meta $meta)
	{
		parent::initialize($meta);

		$meta
			->behaviors(array(
				'shippable_purchase_item' => Jam::behavior('shippable_purchase_item'),
			));
	}
}