<?php

/**
 * @group model.location
 *
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Model_LocationTest extends Testcase_Shipping {

	public function data_contains()
	{
		return array(
			array('Everywhere', 'Europe', TRUE),
			array('France', 'Europe', FALSE),
			array('Europe', 'France', TRUE),
			array('Europe', 'Australia', FALSE),
			array('France', 'France', TRUE),
		);
	}

	/**
	 * @dataProvider data_contains
	 */
	public function test_contains($container, $item, $expected)
	{
		$container = Jam::find('location', $container);
		$item = Jam::find('location', $item);

		$this->assertEquals($expected, $container->contains($item));
	}
}
