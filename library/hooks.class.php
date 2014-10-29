<?php
class Hooks
{
	private static $config = Array();
	private static $uid = 0;
	private static $privs = '';
	private static $school = 0;
	
	public static $error = '';

	public function __construct()
	{		
		
	}

	public static function privValid()
	{
		self::$uid = Http::get_session(SESS_UID);
		self::$school = Http::get_session(SCHOOL_ID);
		self::$privs = Http::get_session(SCHOOL_PRIV);		
		$app = Router::$app;
		$act = Router::$act;
		self::$config = Config::get(Null, 'hooks', 'school');		
		if(self::_public($app, $act)) return true;			
		if(self::_user($app, $act)) return true;		
		if(self::_school($app, $act)) return true;		
		if(self::_privs($app, $act)) return true;
		return false;
	}

	private static function _public($app, $act)
	{			
		self::$error = 404;
		$configs = self::$config['public'];		
		if(!isset($configs[$app])) return false;
		if($configs[$app] == '*') return true;		
		if(in_array($act, $configs[$app])) return true;	
		return false;
	}

	private static function _user($app, $act)
	{		
		self::$error = 'user';
		$configs = self::$config['user'];
		if(!self::$uid) return false;
		if(!isset($configs[$app])) return false;
		if($configs[$app] == '*') return true;
		if(in_array($act, $configs[$app])) return true;		
		return false;
	}

	private static function _school($app, $act)
	{
		self::$error = 'user';
		if(!self::$uid) return false;
		self::$error = 'school';
		$configs = self::$config['school'];
		if(!self::$school) return false;
		if(!isset($configs[$app])) return false;
		if($configs[$app] == '*') return true;
		if(in_array($act, $configs[$app])) return true;
		if(in_array($act, $configs['*'])) return true;		
		return false;
	}
	
	private static function _privs($app, $act)
	{
		self::$error = 'user';
		if(!self::$uid) return false;
		self::$error = 'school';
		$configs = self::$config['school'];
		if(!self::$school) return false;
		self::$error = 'priv';		
		if(self::$privs == '*') return true;		
		$privs = explode(",", self::$privs);
		if(empty($privs)) return false;

		// 重新取得权限
		$item = load_model('school_menu')->getRow(array('app' => $app, 'action' => $act), false, 'id');
		if(in_array($item['id'], $privs)) return true;		
		return false;
	}

	private static function get_priv()
	{
		if(self::uid >2)
		{
			$priv = load_model('admin_user')->getRow(array('school' => self::$school));
		}
	}

}