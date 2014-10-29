<?php
class Teacher_Api extends Api
{
	public function __construct(){
		parent::_init();
		$this->refresh = Http::post('refresh', 'trim', 0);
	}	

	// 
	public function event_list()
	{
		$start = Http::post('start', 'string', '');
		$end = Http::post('end', 'string', '');
		if(!$start && !$end)
		{
			extract(week_day(DAY)); // 默认取本周
		}		
		$cache = $this->refresh ? false : true;
		$cache_key = "teacher_event_{$this->uid}_{$start}_{$end}";
		$result = cache()->get($cache_key);
		if($result == false || $cache === false)
		{
			$_Schedule = load_model('schedule', Null, true);
			$param = compact('start', 'end', 'cache');
			$param['assigner'] = $this->uid;
			$result = $_Schedule->get_user_event($param, 1);			
			cache()->set($cache_key, $result, 60);
		}
		Out(1, 'success', $result);
	}
	
}