<?php

class Model_Purchase_Item_Product extends Kohana_Model_Purchase_Item_Product {

	public static function initialize(Jam_Meta $meta)
	{
		parent::initialize($meta);

		$meta->behaviors(array(
			'shippable_purchase_item' => Jam::behavior('shippable_purchase_item')
		));
	}
}
