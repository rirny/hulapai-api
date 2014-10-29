<?php
class User_Entity extends Entity
{
	public $_id;
	public $account; 
	public $hulaid; 
	public $gender; 
	public $firstname; 
	public $lastname; 
	public $email; 
	public $weixin; 
	public $birthday; 
	public $province; 
	public $city; 
	public $area; 
	public $mobile; 
	public $status; 
	public $setting; 
	public $course_notice; 
	public $sign; 
	public $disturb; 
	public $token;
	public $login_salt;
	
	function get(){		
		var_dump(get_class_vars(__CLASS__));
	}
}