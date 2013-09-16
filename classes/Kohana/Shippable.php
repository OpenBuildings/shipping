<?php defined('SYSPATH') OR die('No direct script access.');

interface Kohana_Shippable {

	public function ships_to(Model_Location $location);

	public function shipping();
}
