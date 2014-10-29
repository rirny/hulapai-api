<?php
/*
 * 获取配置
 * 2013/7/23
*/
class Config
{
	private static $_data =array();
	private static $_files = array();

	public static function get($key=null, $group='system', $file=null, $default=null)
	{		
		self::load($file);		
		if($key && isset(self::$_data[$group][$key]))
		{			
			return self::$_data[$group][$key];
		}else if(null === $key && isset(self::$_data[$group]))
		{
			return self::$_data[$group];
		}else{
			return $default;
		}
	}
	
	public static function all()
	{
		return self::$_data;
	}

	public static function load($file = null)
	{
		$global = SYS . "/global/conf.php";
		if(!in_array($global, self::$_files))
		{
			include_once($global);
			self::$_data = array_merge(self::$_data, $config);
			self::$_files[] = $global;
			unset($config);
		}
		
		$conf = ROOT_PATH . '/conf/config.php';
		if(!in_array($conf, self::$_files))
		{
			include_once($conf);
			self::$_data = array_merge(self::$_data, $config);
			self::$_files[] = $conf;
			unset($config);
		}
		
		if($file !== null)
		{
			$file_path = ROOT_PATH . '/conf/' . $file . ".php";
			if(!in_array($file_path, self::$_files) && file_exists($file_path))
			{
				include_once($file_path);
				self::$_data = array_merge(self::$_data, $config);
				self::$_files[] = $file_path;
				unset($config);
			}
		}
	}

	public static function set()
	{}
}