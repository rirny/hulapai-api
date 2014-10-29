<?php
$config['system'] = array(		
	'debug' => true,		
);

/*
// 测试
$config['database'] = array(
	'master' => array(
		'host' => '192.168.0.222',
		'charset' => 'utf8',
		'dbname' => 'huladb',
		'username' => 'root',
		'password' => 'hulapai_2013',
	),
	'slave' => array(
		'host' => '192.168.0.222',
		'charset' => 'utf8',
		'dbname' => 'huladb',
		'username' => 'root',
		'password' => 'hulapai_2013',
	)
);
$config['sns'] = array(
	'domain' => 'http://www.hulapai.com',
	'database' => 'thinksns_3_0'
);
$config['memcache'] = array(	
	'master' => array(
		'host' => '192.168.0.222',
		'port' => '11211'		
	),
	'slave'=> array(
		'host' => '127.0.0.1',
		'port' => '11211'
	)
);

$config['redis'] = array(	
	'host' => '192.168.0.222',
	'port' => '6379'
);

*/

$config['attach'] = array(	
	'avatar'=> 'http://static.hulapai.com/',
	'image' => 'http://192.168.0.200:81/',
);

$config['upload'] = array(
	'path' => SYS . '/upload',
	// 'path' => '/home/www/server/sns/data/upload',
	'max' => array(
		'size' => '8M',
		'width' => '1024',
		'height' => '1024'
	)
);


$config['session'] = array(	
	'name' => 'HLPSESS',
	'lifetime' => 3600,
	'handle' => 'memcache',
	'domain' => '.hulapai.com',
	'path' => '/',
);
$config['thumb'] = array(
	'single' => array(
		'min_width' => 200,
		'min_height'=> 200,
		'max_width' => 320,
		'max_height'=> 400
	),
	'multi' => array(
		'width' => 100,
		'height'=> 100
	)	
);