<?php

require_once __DIR__.'/../vendor/autoload.php';

Kohana::modules(array(
    'auth'             => MODPATH.'auth',
	'database'         => MODPATH.'database',
	'jam'              => MODPATH.'jam',
	'jam-auth'         => MODPATH.'jam-auth',
	'jam-monetary'     => MODPATH.'jam-monetary',
	'jam-closuretable' => MODPATH.'jam-closuretable',
	'jam-locations'    => MODPATH.'jam-locations',
	'purchases'        => MODPATH.'purchases',
	'shipping'         => __DIR__.'/..',
));

Kohana::$config
	->load('database')
		->set('default', array(
			'type'       => 'PDO',
			'connection' => array(
				'dsn'        => 'mysql:host=localhost;dbname=OpenBuildings/shipping',
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
