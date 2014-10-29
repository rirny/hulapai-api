<?php

class Sms_Api extends Api
{
    
    public function __construct() {
        parent::_init();        
    }
    
    public function index()
    {
		SMS()->login();
	}

	public function blance()
	{
		print_r(SMS()->getBlance());
	}


	public function error()
	{
		print_r(SMS()->getError());
	}
}