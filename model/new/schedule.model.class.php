<?php
/*
 * 课程模型
*/
class Schedule_Model_New Extends Model_New
{
	protected $_db = NULL;
	protected $_table = 'schedule';
	protected $_key = 'id';

	public function __Construct()
	{
		parent::__Construct();
	}

	/* @老师课程
	 * @start
	 * @end
	*/
	public function get_user_event(Array $param, $type=0)
	{		
		extract($param);		
		if(empty($assigner) || empty($start) || empty($end)) return Array();
		$cache_key = "user_events_{$type}_{$assigner}_{$start}_{$end}";
		$result = cache()->get($cache_key);
		(isset($cache) && $cache === false) || $cache = true;		
		if($result === false || $cache === false)
		{
			$_Assign = load_model('assign', NUll, true)->where('start_date,<=', $end, true)->where('type', $type)->where('assigner', $assigner);
			if($type)
			{
				$_Assign->or_where(array('status' => 0, 'end_date,>=' => $start));
			}else{
				$_Assign->where('end_date,>=', $start);
			}			
			empty($sid) || $_Assign->where('sid', $sid);	
			$assigns = $_Assign->Result();			
			import('schedule');
			$result = Array();			
			$_Series = load_model('series', Null, true);
			static $_series = Array();
			foreach($assigns as $item)
			{
				if(empty($_series[$item['sid']]))
				{
					$series = $_Series->where('id', $item['sid'], true)->field('title,color,course,rule,school')->Row();	
					if(empty($series['rule'])) continue;
					$rule = json_decode($series['rule'], true);					
					$start_date = (strtotime($start) < strtotime($item['start_date'])) ? $item['start_date'] : $start;
					$end_date = ($item['end_date'] == '0000-00-00' || strtotime($end) < strtotime($item['end_date'])) ? $end : $item['end_date'];
					$event = Schedule::resolve($rule, $start_date, $end_date);	
					
					$_deletes = $_changes = Array();
					// 删除
					$scheRes = load_model('schedule', Null, true)->field('sid,index,start,end,class_times,status')
						->where('sid', $item['sid'], true)
						->where('index,>=', strtotime($param['start']))
						->where('index,<=', strtotime($param['end']))
						->Result();

					foreach($scheRes as $sche)
					{
						$sche['status'] == 1 && $_deletes[] = $sche['index'];
						$sche['status'] == 2 && $_changes[] = $sche;
					}
					if($_changes)
					{						
						$event = array_merge($event, $_changes);						
					}

					foreach($event as $key => &$v)
					{
						$_key = $v['index'];
						if($_deletes && in_array($v['index'], $_deletes))
						{
							unset($event[$key]);
							continue;
						}
						$v['sid'] = $item['sid'];
						$v['title'] = $series['title'];
						$v['color'] = $series['color'];
						$v['school']= $series['school'];
						$type && $v['priv']= $item['priv'];
						if(!empty($v['status']))
						{
							$v['date'] = date('Y-m-d', $v['index']);
							$v['week'] = date('w', $v['index']);
							$v['class_time'] = $v['class_times']; 
						}else
						{
							$v['index'] = $v['start'];
							$v['start'] = date('H:i', $v['start']);
							$v['end'] = date('H:i', $v['end']);
							$v['class_time'] = $v['times'];
						}						
						unset($v['times'],$v['class_times'],$v['status']);
					}					
					$_series[$item['sid']] = $event;					
				}
				$result = array_merge($result, $_series[$item['sid']]);
			}
			cache()->set($cache_key, $result, 60); // 十分钟缓存			
		}		
		//print_r($result);
		$index = Array();
		
		foreach($result as $k => $val)
		{
			$index[] = $val['index']; // 上课排序
		}		
		array_multisort($index, SORT_DESC, $result);
		return $result;
	}
	
	private $students = Array();
	private $teachers = Array();

	// 一节课下的学生
	public function get_students($sid, $index, $cache=false)
	{		
		if(!$sid || !$index) return ;
		$date = date('Y-m-d', $index);
		$cache_key = "event_student_{$sid}_{$index}";
		$result = cache()->get($cache_key);		
		(isset($cache) && $cache === false) || $cache = true;
		if($result === false || $cache === false)
		{
			$filter = load_model('schedule_record', Null, true) // 调课
				->where('sid', $sid, true)
				->where('index', $index)
				->where('protype', 'delay')
				->where('type', 0)
				->field('assigner')
				->Column();
			
			$sql = "select s.id,s.name,s.name_en,s.avatar from t_assign r left join t_student s on r.assigner=s.id";
			$sql.= " where `type`=0 And start_date<='{$date}' And end_date>='{$date}' And r.sid={$sid}";	
			if($filter)
			{
				$sql .= " And r.assigner not in(". join($filter) .")";
			}	
			$sql .= " Group by s.id";
			$result = db()->fetchAll($sql);
			cache()->set($cache_key, $result, 60); // 十分钟缓存
		}
		return $result;
	}

	// 课 >> 老师
	public function get_teachers($sid, $index)
	{
		if(!$sid || !$index) return ;
		$date = date('Y-m-d', $index);
		$cache_key = "event_teacher_{$sid}_{$index}";
		$result = cache()->get($cache_key);
		(isset($cache) && $cache === false) || $cache = true;
		if($result === false || $cache === false)
		{
			$sql = "select s.id,s.firstname,s.lastname,s.avatar from t_assign r left join t_user s on r.assigner=s.id";
			$sql.= " where `type`=1 And start_date<='{$date}' And (r.`status`=0 Or end_date>='{$date}') And r.sid={$sid}";
			$result = db()->fetchAll($sql);
			/*
			$filter = load_model('schedule_record', Null, true) // 调课
				->where('sid', $sid, true)
				->where('index', $index)
				->where('protype', 'change')
				->where('type', 1)
				->field('assigner')
				->Column();
			*/
			cache()->set($cache_key, $result, 60); // 十分钟缓存
		}		
		return $result;
	}
	

	public function entity_row($sid, $index)
	{
		if(!$sid || !$index) return false;
		$result = $this->where('sid', $sid, true)->where('index', $index)->Row();
		if($result) return $result;
		$result = $this->virtual_row($sid, $index);
		//unset($result['title'], $result['school'], $result['course'], $result['color']);		
		$result['id'] = $this->insert($result);		
		return $result;
	}

	public function virtual_row($sid, $index, $full = false)
	{		
		$series = load_model('series', Null, true)->field('*')->where('id', $sid, true)->Row();		
		if(empty($series)) return false;		
		import('schedule');
		$date = date('Y-m-d', $index);	
		$rule = json_decode($series['rule'], true);
		$schedule = Schedule::resolve($rule, $date, $date);
		if(!$schedule) return false;
		$schedule = current($schedule);		
		$result = array(
			'sid' => $sid,
			'index' => $index,			
			'start' => date('H:i', $schedule['start']),
			'end' => date('H:i', $schedule['end']),
			'class_times' => $schedule['times']
		);
		$full && $result = array_merge($result, array(
			'title' => $series['title'],
			'date' => date('Y-m-d', $schedule['start']),
			'color' => $series['color'],
			'school' => $series['school'],
			'course' => $series['course']
		));
		return $result;
	}
	
	public function attend($data)
	{
		extract($data);
		if(!$sid || !$index || !$assigner) return false;
		$_Record = load_model('schedule_record', Null, true);
		$record = $_Record->where('sid', $sid, true)->where('index', $index)->where('type', 0)->where('assigner', $assigner)->Row();
		if(!$record)
		{
			return $_Record->insert($data); 
		}else if($record['value'] != $value){
			return $_Record->update(array('value' => $value)); 
		}		
		return true;
	}

	/* 更新统计数据
	 * 
	*/
	public function stat_refresh($sid, $school)
	{
		
	}
}