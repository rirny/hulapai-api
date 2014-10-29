<?php
// 哗啦圈赞
class Blog_digg_model Extends Model
{
	protected $_db = NULL;
	protected $_table = 'ts_blog_digg';
	protected $_key = 'id';
	protected $_table_user = 't_user';
		
	public function __construct(){
		parent::__construct();	
	}
}
