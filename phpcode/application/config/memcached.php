<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

switch(ENVIRONMENT) {
	case 'production':
		$config['memcached'] = array(
			'hostname' => '10.126.78.11',
			'port'     => 11211,
			'weight'   => 1,
		);
		break;
	case 'testing':
		$config['memcached'] = array(
			'hostname' => '172.18.194.94',
			'port'     => 11211,
			'weight'   => 1,
		);
		break;
	case 'development':
	default:
		$config['memcached_1'] = array(
			'hostname' => '172.18.194.92',
			'port'     => 11211,
			'weight'   => 50,
		);
		$config['memcached_2'] = array(
			'hostname' => '172.18.194.92',
			'port'     => 11212,
			'weight'   => 50,
		);
		break;
}