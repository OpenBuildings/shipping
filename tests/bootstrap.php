<?php

spl_autoload_register(function($class)
{
	$file = __DIR__.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.str_replace('_', '/', $class).'.php';

	if (is_file($file))
	{
		require_once $file;
	}
});

require_once __DIR__.'/../vendor/autoload.php';

Kohana::modules(array(
	'database'         => MODPATH.'database',
	'auth'             => MODPATH.'auth',
	'jam'              => __DIR__.'/../modules/jam',
	'jam-auth'         => __DIR__.'/../modules/jam-auth',
	'jam-monetary'     => __DIR__.'/../modules/jam-monetary',
	'jam-closuretable' => __DIR__.'/../modules/jam-closuretable',
	'jam-locations'    => __DIR__.'/../modules/jam-locations',
	'purchases'        => __DIR__.'/../modules/purchases',
	'shipping'         => __DIR__.'/..',
));

Kohana::$config
	->load('database')
		->set('default', array(
			'type'       => 'MySQL',
			'connection' => array(
				'hostname'   => 'localhost',
				'database'   => 'OpenBuildings/shipping',
				'username'   => 'root',
				'password'   => '',
				'persistent' => TRUE,
			),
			'table_prefix' => '',
			'charset'      => 'utf8',
			'caching'      => FALSE,
		));

Kohana::$config
	->load('purchases')
		->set('processor', array(
			'emp' => 	array(
				'api' => array(
					'gateway_url' => 'https://my.emerchantpay.com/',
					'client_id'   => getenv('PHP_EMP_CLIENT_ID'),
					'api_key'     => getenv('PHP_EMP_API_KEY'),
				),
				'threatmatrix' => array(
					'org_id'    => getenv('PHP_THREATMATRIX_ORG_ID'),
					'client_id' => getenv('PHP_EMP_CLIENT_ID'),
				),
			),
		));

Kohana::$environment = Kohana::TESTING;
