<?php
Abstract class Api{
	
	public $app = '';
	public $act = '';

	public $uid = '';
	public $account = '';
	public $hulaid = '';
	public $name = '';
	
	public $error = false;
	private $error_message = '';
	
	protected static $pusher = Null;

	public function __construct(){		
		
	}
	
	protected function _init()
	{		
		$this->uid = Http::get_session(SESS_UID);
		if(!$this->uid) Out(1, 'Not Login');
		$this->account = Http::get_session(SESS_ACCOUNT);
		$this->hulaid = Http::get_session(SESS_HULAID);
		$this->name = Http::get_session(SESS_NAME);
		$this->appSource = Http::post('appStore', 'string', '');
	}	

	public function __destruct(){
		if (class_exists('Db'))
		{
			db()->close();
		}
	}

	public function getError()
	{
		return $error_message;
	}
	
	// 日志
	public function logs($param, $method='db', $db='t_logs')
	{
		
	}
}
