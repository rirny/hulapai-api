<?php
class Area_model Extends Model
{
	protected $_table = 't_area';
	protected $_key = 'id';

	public $object = Null;	
	
	protected $_cache_key = 'area';
	protected $_timelife = '3600';

	public function __construct(){
		parent::__construct();
	}

	// 获取所有父级
	public function childs($pid=0, $cache=false)
	{
		$pid && $this->_cache_key .= '_' . $pid;
		$result = $cache ? cache()->get($this->_cache_key) : array();
		if($result)
		{
			$result = db()->fetchAll('select id _id, name from t_course_type where pid=0');
			cache()->set($this->_cache_key, $result, true, $this->_timelife);
		}				
		return $result;
	}

	public function catalog()
	{
		
	}
	
	// 所有课程分类
	public function all_types()
	{
		return db()->fetchAll('select id _id, name from t_course_type');
	}
}