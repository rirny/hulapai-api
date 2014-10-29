<?php
// 微博用户数据
class Feed_user_data_model Extends Model
{
	protected $_db = NULL;
	protected $_table = 'ts_feed_user_data';
	protected $_key = 'id';
	
	public function __construct(){
		parent::__construct();	
	}
}
