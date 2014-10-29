<?php
class Session_handle extends SessionHandler
{
    private $lifetime = 1800;
    private $memcache;
    private $config;
    private $session_name = "HLPSESS";
	private $path = '/';
	private $domain = '';
	private static $_id = '';

    public function __construct(&$handle=null) {	
		
		$this->config = Config::get(null, 'session');
        isset($this->config['lifetime']) && $this->lifetime = $this->config['lifetime'];
		isset($this->config['name']) && $this->sessionname = $this->config['name'];
		isset($this->config['path']) && $this->path = $this->config['path'];
		isset($this->config['domain']) && $this->domain = $this->config['domain'];
		session_name($this->sessionname);
		if(!self::$_id)
		{			
			if(!empty($_COOKIE[$this->sessionname]))
			{
				self::$_id = $_COOKIE[$this->sessionname];
			}else{
				self::set_session_id();
				// setcookie(session_name(), self::$_id, $this->lifetime, '/');
			}
		}

		ini_set('session.cookie_path', $this->path);
		ini_set('session.cookie_domain', $this->domain);
		ini_set('session.cookie_lifetime', $this->lifetime);

		session_id(self::$_id);
		// setcookie($this->sessionname, self::$_id, 1, $this->session_cookie_path, $this->session_cookie_domain, $this->session_cookie_secure);		
		if($handle)
		{
			$this->handle = $handle;        		
			session_set_save_handler(
				array(&$this,'open'), 
				array(&$this,'close'), 
				array(&$this,'read'), 
				array(&$this,'write'), 
				array(&$this,'destroy'), 
				array(&$this,'gc')
			);
		}
		session_start();
    }

	public function set_session_id()
	{
		$ip = Http::ip();	
		$md5 = $ip . json_encode(Http::agent());		
		$uniqid = uniqid(mt_rand(), true);		
		self::$_id = strtoupper(md5($md5 . $uniqid));		
	}

	public static function get_session_id()
	{		
		return self::$_id;
	}

	/**
	 * session_set_save_handler  open方法
	 * @param $save_path
	 * @param $session_name
	 * @return true
	 */
    public function open($save_path, $session_name) {

		return true;
    }
	/**
	 * session_set_save_handler  close方法
	 * @return bool
	 */
    public function close() {
        return true;
    } 
	/**
	 * 读取session_id
	 * session_set_save_handler  read方法
	 * @return string 读取session_id
	 */
    public function read($id) {
		
		if($this->handle == null) return parent::read($id);		
		$data = $this->handle->get($id);
		if($data)
		{
			$this->handle->set($id, $data, $this->lifetime);
		}
		return $data;
    } 

	/**
	 * 写入session_id 的值
	 * 
	 * @param $id session
	 * @param $data 值
	 * @return mixed query 执行结果
	 */
    public function write($id, $data) 
	{
		if($this->handle == null) parent::write($id, $data);
		return $this->handle->set($id, $data, $this->lifetime);			
    }
	/** 
	 * 删除指定的session_id
	 * 
	 * @param $id session
	 * @return bool
	 */
    public function destroy($id) {

		 // cookie设为过期
        if (isset($_COOKIE[session_name()])) {
            $cp = session_get_cookie_params();
            // setcookie(session_name(), md5(microtime() . mt_rand(0, 999999)), 1, '/', 'api.hulapai.com', '');
			// setcookie(session_name(), self::$_id, 1, '/');
        }	

		if($this->handle)
		{
			return $this->handle->delete($id);
		}
		session_unset();
        session_destroy();       	
		return parent::delete($id, $data);
    }
	/**
	* @return bool
	*/
	public function gc($maxlifetime) {
		return true;
	}
}
?>