<?php
class Http{

	public function __construct(){}
	public function __destruct(){}	
	
	public static $app = null;
	public static $act = null;
		
	private static $_post = array();
	private static $_get = array();
	private static $_request = array();

	private static $_device = null;

	private static $_curl = '';

	public static function post($key=null, $filter='trim', $default=null,$param='')
	{
		if(null == $key) return $_POST;
		empty(self::$_post) && self::$_post = $_POST;
		$post = self::$_post;
		if(!isset($post[$key])) return $default;
		return self::filter($post[$key], $filter, $param);
	}

	public static function request($key=null, $filter='trim', $default=null)
	{		
		if(null == $key) return $_REQUEST;
		empty(self::$_request) && self::$_request = $_REQUEST;
		$request = self::$_request;
		if(!isset($request[$key])) return $default;
		return self::filter($request[$key], $filter);
	}

	public static function get($key=null, $filter='trim', $default=null)
	{
		if(null == $key) return $_GET;
		empty(self::$_get) && self::$_get = $_GET;
		$get = self::$_get;
		if(!isset($get[$key])) return $default;	
		return self::filter($get[$key], $filter);
	}
	
	private static function filter($value, $type='trim',$param='')
	{
		if(is_array($value))
		{
			foreach($value as &$item)
			{
				$item = self::filter($item, $type, $param);
			}
			return $value;
		}
		if (!get_magic_quotes_gpc()) { 
			$value = addslashes($value); 
		}		
		switch($type)
		{
			case 'trim':
				$value = trim($value);
				break;
			case 'int':
				$value = intval($value);
				break;
			case 'float':
				$value = floatval($value);
				//$value = round($value, 2);
				break;
			case 'date':
				$value = self::isDate($value,$param) ? $value : '';
				break;
			case 'String':
				break;
			default:
				break;	
		}
		return $value;
	}
	
	public static function query()
	{
		$param = self::request();
		$result = array();
		unset($param['app'], $param['act']);
		foreach($param as $key => $item)
		{
			$result[$key] = self::filter($item);
		}
		return $result;
	}

	public static function ip()
    {
        if (isset($_SERVER['REMOTE_ADDR']))
        {
            return $_SERVER['REMOTE_ADDR'];
        }
        else if ($_tmp = getenv('REMOTE_ADDR'))
        {
            return $_tmp;
        }
        return 'unknow';
    }

	// 是否为AJAX
	public static function is_ajax()
	{
		if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
		{
			return true;
		}
		return false;
	}
	
	// 是否为POST
	public static function is_post()
	{
		if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST')
		{
			return true;
		}
		return false;
	}

	public static function agent()
	{   
		if(!isset($_SERVER['HTTP_USER_AGENT'])) return null;
		$user_agent = $_SERVER['HTTP_USER_AGENT'];		
		$client = $model = $os = $sn = $brand = $src = '';		
		if(strpos($user_agent,"iPhone") || strpos($user_agent,"iPad") || strpos($user_agent,"iPod") || strpos($user_agent,"iOS")){
			// iPhone,iPhone OS 6.1.3,29E45E5C-8819-449A-AFF9-079526724CBE			
			$brand = 'apple';			
			if(isset($_SERVER['HTTP_DEVICE']))
			{
				list($model, $os, $sn) = explode(",", $_SERVER['HTTP_DEVICE'], 3);
			}
			$src = 'ios';
		}else if(strpos(strtolower($user_agent),"android") !== false){            
			// Android2.3.6,samsung GT-S5830i,355271050739836			
			list($os, $brand, $sn) = explode(",", $user_agent, 3);
			list($brand, $model) = explode(" ", $brand);
			$src = 'android';
		}		
		return compact('brand', 'model', 'os', 'sn', 'src');		
	}
	
	public static function curl()
	{
		$query = self::query();
		if(isset($query['page'])) unset($query['page']);
		if(!empty($query))
		{
			return $_SERVER['PHP_SELF'].'?'. http_build_query($query);
		}
		return $_SERVER['PHP_SELF'];		
	}

	public static function get_device()
	{		
		if(null === self::$_device){
			
			if(self::get_session('device'))
			{
				self::$_device = self::get_session('device');
			}else{
				self::$_device = self::agent();
				
				self::set_session('device', self::$_device);
			}			
		}
		return self::$_device;
	}
	
	// 来源
	public static function getSource()
	{
		// source 'web','webmobile','android','iphone'
		// 0：网站；1：手机网页版；2：android；3：ios'
		$source = array(
			'web', 'wap', 'android', 'ios'	
		);
		$agents = self::agent();
		$result = empty($agents['src']) ? 'web' : $agents['src'];
		$result = array_search($result, $source);		
		return $result;
	}

	public static function isMobile()
	{
		// 如果有HTTP_X_WAP_PROFILE则一定是移动设备
		//        if(isset($_SERVER['HTTP_X_WAP_PROFILE']))
		//        {
		//            return true;
		//        }
		//        
		//        //如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
		//        if(isset($_SERVER['HTTP_VIA']))
		//        {
		//            //找不到为flase,否则为true
		//            return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
		//        }
		$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
		//判断手机发送的客户端标志
		if(isset($_SERVER['HTTP_USER_AGENT']))
		{
			$clientkeywords = array('nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 
			'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 
			'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'opera mobi', 'openwave', 'nexusone', 'cldc', 'midp', 
			'wap', 'mobile');
			// 从HTTP_USER_AGENT中查找手机浏览器的关键字
			if(preg_match("/(" . implode('|', $clientkeywords) . ")/i", $userAgent) && strpos($userAgent,'ipad') == 0)
			{
				return true;
			}
		}
		return false;
	}
	
	// 是否为日期
	public static function isDate($str,$format="Y-m-d"){ 
		$strArr = explode("-",$str); 
		if(empty($strArr)){
			return false;
		}
		foreach($strArr as $val){	
			if(strlen($val) < 2){
				$val = "0".$val;
			}
			$newArr[] = $val;
		}
		$str = implode("-",$newArr);  
		$unixTime = strtotime($str);  
		$checkDate = date($format,$unixTime);  
		if($checkDate == $str)  
			return true;  
		else  
			return false;
	} 
	
	/**
     * 验证json回调函数名
     * @param $callback
     * @
     */
    public static function checkCallback($callback)
    {
        if(empty($callback))
        {
            return false;
        }
        if(preg_match("/^[a-zA-Z_][a-zA-Z0-9_\.]*$/", $callback))
        {
            return true;
        }
        return false;
    }
	
	public static function set_session($key, $value='')
	{
		if(!$key) return ;
		if(isset($_SESSION[$key])) {
			unset($_SESSION[$key]);
		}
		$_SESSION[$key] = $value;		  
	}

	public static function get_session($key=Null, $default=null)
	{
		if(null == $key) return $_SESSION;
		if(isset($_SESSION[$key])) return $_SESSION[$key];	
		return $default;
	}

	public static function delete_session()
	{
		$args = func_get_args();
		foreach($args as $item) {
			if(isset($_SESSION[$item])) unset($_SESSION[$item]);
		}
	}

	public static function is_mobile() {
		// 如果有HTTP_X_WAP_PROFILE则一定是移动设备
		if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])){
			return true;
		}
		//如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
		if (isset ($_SERVER['HTTP_VIA'])) {
		//找不到为flase,否则为true
			return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
		}
		//判断手机发送的客户端标志,兼容性有待提高
		if (isset ($_SERVER['HTTP_USER_AGENT'])) {
				$clientkeywords = array (
					'nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel',
					'lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm',
					'operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile'
				);
			// 从HTTP_USER_AGENT中查找手机浏览器的关键字
			if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
				return true;
			}
		}
		//协议法，因为有可能不准确，放到最后判断
		if (isset ($_SERVER['HTTP_ACCEPT'])) {
			// 如果只支持wml并且不支持html那一定是移动设备
			// 如果支持wml和html但是wml在html之前则是移动设备
			if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
				return true;
			}
		}
		return false;
	}
}