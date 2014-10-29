<?php
set_time_limit(0);
header("Content-type: text/html; charset=utf-8");
header("cache-control:no-cache,must-revalidate");
date_default_timezone_set('Asia/Shanghai');
define('ROOT_PATH', dirname(__FILE__));
define('SYS', dirname(dirname(__FILE__)));
define('LIB', SYS . "/library");
define('MODEL', SYS . "/model");
define('ENTITY', SYS . "/entity");
define('CONF', ROOT_PATH . "/conf");
define('LOG_PATH', ROOT_PATH . "/logs");
define('APP_PATH', ROOT_PATH . "/cli");

require(SYS.'/comm/comm.php');

import('config');
$debug = Config::get('debug', 'system', null, false);
define('TIMESTAMP', time());
define('DAY', date('Y-m-d'));
define('DATETIME', date('Y-m-d H:i:s'));
if ($debug)
{
	error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
	ini_set('display_errors', 1);
}
else
{
	error_reporting(0);
	ini_set('display_errors', 0);
}
import('http');

$app = isset($argv[1]) ? $argv[1] : 'index';
$act = isset($argv[2]) ? $argv[2] : 'index';

$app || $app = 'index';
$act || $act = 'index';

import('api');
$api = ucfirst($app) . "_Api";
$api_files = APP_PATH . '/' . $app . '.class.php';

try
{
	if(!file_exists($api_files)) throw new Exception("APP 不存在！");
	require_once($api_files);
	$api = new $api;
	$api->app = $app;
	$api->act = $act;
	if(!method_exists($api, $act))
	{    
		throw new Exception("ACTION模块不存在!");
	}
	$api->$act();	
	if($api->Error) throw new Exception($api->getError());
}catch(Exception $e)
{
	Out(0, $e->getMessage());
}