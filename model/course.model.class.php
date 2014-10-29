<?php
class Course_model Extends Model
{
	protected $_table = 't_course';
	protected $_key = 'id';

	public $object = Null;	
	
	protected $_cache_key = 'course_type';
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
			$result = db()->fetchAll('select id _id, name,pid from t_course_type where pid=0');
			cache()->set($this->_cache_key, $result, true, $this->_timelife);
		}				
		return $result;
	}

	public function catalog()
	{
		
	}
	
	// 所有课程分类
	public function all_types($out=false)
	{
		$cache_key = 'course';
		$result = cache()->get('course');
		if($result === false)
		{
			$result = db()->fetchAll('select id _id,pid, name,`sort` from t_course_type order by `sort`');
			if($result && $out)
			{
				foreach($result as $key=>$item)
				{
					$result[$key] = $this->Format($item);	
				}
				cache()->set('course', $result, $this->_timelife);
			}
		}
		return $result;
	}
}