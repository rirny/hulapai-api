<?php
header("Content-type: text/html; charset=utf-8");
header("cache-control:no-cache,must-revalidate");
date_default_timezone_set('Asia/Shanghai');
define('ROOT_PATH', getcwd());
define('SYS', dirname(ROOT_PATH));
define('LIB', SYS . "/library");
define('MODEL', SYS . "/model/school");
define('ENTITY', SYS . "/entity");
define('CONF', ROOT_PATH . "/conf");
define('LOG_PATH', ROOT_PATH . "/logs");
define('APP_PATH', ROOT_PATH . "/school");

define('SESS_UID', 'H_uid');
define('SESS_ACCOUNT', 'H_account');
define('SESS_NAME', 'H_name');
define('SESS_HULAID', 'H_hulaid');


define('SCHOOL_NAME', 'H_sch_name');
define('SCHOOL_CODE', 'H_sch_code');
define('SCHOOL_ID', 'H_sch_id');
define('SCHOOL_PRIV', 'H_sch_priv');
define('SCHOOL_CREATOR', 'H_sch_creator');
define('SCHOOL_GID', 'H_sch_gid');


require(SYS.'/comm/comm.php');
define('DOMAIN', 'hulapai.cn');
define('ROOT', 'http://' . $_SERVER['SERVER_NAME']);
define('STATIC_PATH', 'http://static.' . DOMAIN);
define('UPLOAD_PATH', ROOT_PATH . "/upload");

define('SMARTY_TPL_DIR', ROOT_PATH.'/tpl/');
define('SMARTY_COMPILE_DIR', ROOT_PATH.'/data/smarty_compile/');
define('SMARTY_CACHE_DIR', ROOT_PATH.'/data/smarty_cache/');
define('SMARTY_CACHEING', false);

define('TM', time());
define('DAY', date('Y-m-d'));
define('DATETIME', date('Y-m-d H:i:s'));


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

import('http');
define('IP', Http::ip());

//session_start();
hlp_session_start();
import('HLPException');
import('router');
import('module');
require_once(APP_PATH . "/abstract.class.php");
import('hooks');

try
{
	Router::adapter();
	import('module');
	$app = ucfirst(Router::$app) . "_Module";
	$act = ucfirst(Router::$act) . "_Action";	
	if(!file_exists(Router::$appFile)) throw new HLPException("APP 不存在！", 404);
	require_once(Router::$appFile);
	$Module = new $app;
	$Module->app = Router::$app;
	$Module->act = Router::$act;
	if(!method_exists($Module, $act))
	{
		throw new HLPException("ACTION模块不存在!", 404);
	}
	$Module->$act();	
	if($Module->Error) throw new HLPException($Module->getError());
}catch(HLPException $e)
{
	die($e->getMessage());
	eval("class Error Extends Module{}");
	$Module = new Error();
	$Module->error();
}
?>
 