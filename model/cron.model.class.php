<?php
// 计划任务
class Cron_model Extends Model
{
	protected $_db = NULL;
	protected $_table = 't_cron';
	protected $_key = 'cron_id';
		
	public function __construct(){
		parent::__construct();	
	}
}
