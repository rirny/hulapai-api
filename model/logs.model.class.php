<?php
class Logs_model Extends Model
{
	protected $_table = 't_logs';
	protected $_key = 'id';
	
	public $object_name = 'push';
	public $object = Null;
	
	public function __construct(){
		parent::__construct();
	}

	public function push($key, $value = array())
	{
		if(!$value) return false;
		foreach($value as $k => $item)
		{
			is_array($item) && $value[$k] = json_encode($item);
		}
		$value['create_time'] = time();
		return $this->insert($value);
	}

	public function lpop($key)
	{
		
	}

	public function hset($key, $field, $value)
	{
		if(!$value) return false;	
		foreach($value as $k => $item)
		{
			is_array($item) && $value[$k] = json_encode($item);
		}
		$value['create_time'] = time();
		return $this->insert($value);
	}

	public function hget($key, $field)
	{
		
	}
	
	public function getLogData($id){
		$result = $this->getRow($id);
		if(!$result) return false;
		$result['target'] = json_decode($result['target'],true);
		$result['ext'] = json_decode($result['ext'],true);
		$result['source'] = json_decode($result['source'],true);
		$result['data'] = json_decode($result['data'],true);
		is_array($result['target']) || $result['target'] = array($result['target']);
		is_array($result['ext']) || $result['ext'] = array($result['ext']);
		is_array($result['source']) || $result['source'] = array($result['source']);
		is_array($result['data']) || $result['data'] = array($result['data']);
		//$this->delete($id,true);
		return $result;
	}
}
