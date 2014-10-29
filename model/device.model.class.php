<?php
class Device_model Extends Model
{
	protected $_table = 't_device';
	protected $_key = 'id';

	private $detail = Null;
	private $simple = Null;
	
	public function __construct(){
		parent::__construct();
	}

	public function set()
	{
		$device = Http::get_device();		
		if(!$device) return ;
		$device['user'] = Http::get_session(SESS_UID);		
		if($last = parent::getRow($device, false)) // 最新的一条
		{			
			$res = db()->update($this->_table, array('last_time' => time()), "`id`='".$last['id']."'");
		}else{
			$res = db()->insert($this->_table, $device);
		}		
	}

}
