<?php
class Student_Api extends Api
{
	public function __construct(){
		parent::_init();
		$this->refresh = Http::post('refresh', 'trim', 0);
	}
	
	// 老师点评
	public function comments()
	{
		$student = Http::post('student', 'int', 0);		
		$_page = Http::post('page', 'int', 1);
		$perpage = 20;
		if(!$student) throw new Exception('点评失败');		
		$res = load_model('user_student', Null, true)->where('student', $student)->where('user', $this->uid)->Row();
		if(!$res) throw new Exception('权限错误！');
		$_Comment = load_model('comment', Null, true)->where('student', $student)->where('character', 'teacher')->where('pid', 0)->limit($perpage, $_page);
		$page = $_Comment->Page();
		$data = $_Comment->field('id,teacher,student,content,creator,sid,index,create_time,character')->order('create_time', 'Desc')->limit($perpage, $_page)->Result();
		foreach($data as $key=> &$item)
		{
			$item = $_Comment->formatForApp($item);			
		}		
		Out(1, 'success', compact('page', 'data'));
	}	
	
	// 课程列表
	public function event_list()
	{
		$start = Http::post('start', 'string', '');
		$end = Http::post('end', 'string', '');
		$student = Http::post('student', 'int', 0);
		if(!$student) throw new Exception('点评失败');
		if(!$start && !$end)
		{
			extract(week_day(DAY)); // 默认取本周
		}		
		$cache = $this->refresh ? false : true;
		$cache_key = "student_event_{$student}_{$start}_{$end}";
		$result = cache()->get($cache_key);
		if($result == false || $cache === false)
		{
			$_Schedule = load_model('schedule', Null, true);
			$param = compact('start', 'end', 'cache');
			$param['assigner'] = $student;
			$result = $_Schedule->get_user_event($param, 0);
			cache()->set($cache_key, $result, 60);
		}
		Out(1, 'success', $result);
	}
}
