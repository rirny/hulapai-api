<?php
class Guest_model Extends Model
{
	protected $_table = 't_guest';
	protected $_key = 'id';
	
	protected $_timelife = '3600';

	
	public function __construct(){
		parent::__construct();
	}
	
}