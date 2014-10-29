<?php
set_time_limit(0);
header("Content-type: text/html; charset=utf-8");
header("cache-control:no-cache,must-revalidate");
date_default_timezone_set('Asia/Shanghai');
define('ROOT_PATH',dirname(__FILE__));
define('SYS', dirname(ROOT_PATH));
define('LIB', SYS . "/library");
define('MODEL', SYS . "/model");
define('ENTITY', SYS . "/entity");
define('CONF', SYS . "/conf");
define('LOG_PATH', SYS . "/logs");
define('APP_PATH', SYS . "/client");

require(SYS.'/comm/comm.php');
import('config');
$debug = Config::get('debug', 'system', null, false);

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

//session_start();
//hlp_session_start();
if(isset($argv[1]))
{
	$app = $argv[1];
}else{
	$app = 'index';
}
if(isset($argv[2]))
{
	$act = $argv[2];
}else{
	$act = 'index';
}
import('http');
if(count($argv) > 3)
{
	$param = array_slice($argv, 3);
}
import('client');
$api = ucfirst($app) . "_CLI";
$api_files = APP_PATH . '/app/' . $app . '.class.php';
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
?>
