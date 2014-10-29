<?php
Abstract class Client{
	
	public $app = '';
	public $act = '';
	
	public $error = false;
	private $error_message = '';
	
	protected static $pusher = Null;

	public function __construct(){		
		
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
}
