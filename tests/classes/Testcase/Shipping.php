<?php

use Openbuildings\EnvironmentBackup as EB;

/**
 * Testcase_Functest definition
 *
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
abstract class Testcase_Shipping extends PHPUnit_Framework_TestCase {

	public $environment;
	
	public function setUp()
	{
		parent::setUp();
		Database::instance()->begin();
		Jam_Association_Creator::current(1);

		$this->env = new EB\Environment(array(
			'static' => new EB\Environment_Group_Static(),
			'config' => new EB\Environment_Group_Config(),
		));
	}

	public function tearDown()
	{
		Database::instance()->rollback();	
		$this->env->restore();

		parent::tearDown();
	}

	public function ids(array $items)
	{
		return array_values(array_map(function($item){ return $item->id(); }, $items));
	}

	public function getMockModelArray($model_name, array $params)
	{
		$items = array();

		foreach ($params as $id => $item_params) 
		{
			$item = $this
				->getMockFromParams(Jam::class_name($model_name), $item_params, array($model_name))
				->set(array('id' => $id));
			
			$items []= $item;
		}

		return $items;
	}

	public function getMockFromParams($class, array $params, array $constructor_arguments = array())
	{
		$item = $this->getMock($class, array_keys($params), $constructor_arguments);

		foreach ($params as $name => $value) 
		{
			$item
				->expects($this->any())
					->method($name)
					->will($this->returnValue($value));
		}

		return $item;
	}

}