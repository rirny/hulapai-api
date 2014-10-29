<?php
class Logs
{
	private $log = Null;
	
	private static $instance = Null;

	public function __construct($handle = 'db')
	{	
		if($handle == 'db')
		{			
			$this->handle = load_model('logs');
		}else if($handle == 'redis'){
			$this->handle = redis();
		}else{
			$this->handle = file_cache();
		}
	}

	public static function get_instance($handle = 'db')
	{		
		if(self::$instance == NULL){  
            self::$instance = new Logs($handle);  
        }         
        return self::$instance;
	}
	
	public function add($key, $field, $value)
	{
		return $this->handle->hset($key, $field, $value);
	}

	public function delete()
	{
	
	}
}