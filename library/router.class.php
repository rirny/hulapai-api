<?php
class Router
{
	public static $app = 'index';
	public static $act = 'index';
	public static $appFile = '';
	// 分发
	public static function adapter()
	{
		$uri = $_SERVER['REQUEST_URI'];		
		$arguments = explode("?", $uri);		
		$appPath = APP_PATH;
	
		// 只支持两级
		if(!empty($arguments[0]))
		{		
			$argument = strtolower($arguments[0]);
			$dirname = dirname($argument);
			$filename = basename($argument);			
			if(is_dir(APP_PATH . $argument))
			{
				$appPath .= $argument;
			}else if(is_file(APP_PATH . $argument . ".class.php")){
				$appPath .= $dirname;
				self::$app = $filename;
			}else if(is_dir(APP_PATH . $dirname) && is_file(APP_PATH . $dirname . "/index.class.php"))
			{
				$appPath .= $dirname;
				self::$act = $filename;
			}else if(is_file(APP_PATH . $dirname . ".class.php"))
			{	
				self::$app = basename($dirname);
				self::$act = $filename;
			}
			// 指向Index.class.php
		}
		self::$appFile = str_replace("//", "/", $appPath ."/". self::$app . ".class.php");		
	}
}