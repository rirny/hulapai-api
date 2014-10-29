<?php
class Entity
{
	public function __construct($data)
	{		
		foreach($data as $key => $value)
		{
			$this->__set($key, $value);
		}
	}

	public function __set($key, $value)
	{
		$_key = "_" . $key;
		if(isset($this->$_key)){
			$this->$_key = $value;
			return ;
		}
		if(isset($this->$key))
		{
			$this->$key = $value;
		}
	}

	public function __get($key)
	{
		$_key = "_" . $key;
		if(isset($this->$_key)){
			return $this->$_key;
		}
		if(isset($this->$key))
		{
			return $this->$key;
		}
		return ;
	}

	public function get()
	{		
		var_dump(get_class_vars(__CLASS__));
	}
}