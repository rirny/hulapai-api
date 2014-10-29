<?php
class Push_model Extends Model
{
	protected $_table = 't_push';
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
			is_array($item) && $item = json_encode($item);
			$value[$k] = $item;
		}
		$value['create_time'] = time();        
		return $this->insert($value);
	}

	public function lpop($key)
	{
		
	}

	public function hset($key, $field, $value)
	{
		
	}

	public function hget($key, $field)
	{
		
	}
}
