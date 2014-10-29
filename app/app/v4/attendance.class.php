<?php

class Attendance_Api extends Api
{
	public function __construct(){
		parent::_init();
		$this->refresh = Http::post('refresh', 'trim', 0);
	}
	
	// 考勤
	public function index()
	{
		$sid = Http::post('sid', 'int', 0);
		$index = Http::post('index', 'int', 0);
		if(!$sid || !$index) throw new Exception('课程不存在！');
		//
		$series = load_model('series', Null, true)->field('id,title,color')->where('id', $sid)->Row();
		if(!$series) throw new Exception('课程不存在!');
		// 此老师是否有此课程
		$date = date('Y-m-d', $index);
		$assign = load_model('assign', Null, true)
			->where('sid', $sid)
			->where('type', 1)
			->where('assigner', $this->uid)
			->where('start_date,<=', $date)
			->where('priv,&', 2)
			->or_where(array('status' => 0, 'end_date,>=' => $date))			
			->Row();
		
		if(!$assign) throw new Exception('没有此课程，或没有权限！');
		
		$_Schedule = load_model('schedule', Null, true);
		$event = $_Schedule->virtual_row($sid, $index);
		if(!$event) throw new Exception('没有此课程，或没有权限！');

		$event['title'] = $series['title'];
		$event['color'] = $series['color'];

		// 获取课程下的学生
		$data = $_Schedule->get_students($sid, $index);
		$_Record = load_model('schedule_record', Null, true);
		$records = $_Record->field('value,assigner')->where('sid', $sid, true)->where('index', $index)
				->where('type', 0)
				->where('protype', 'attend')
				->Result();		
		$attends = Array();
		foreach($records as $record)
		{
			$attends[$record['assigner']] = $record['value'];
		}		
		foreach($data as &$item)
		{				
			 $item['attend'] = !empty($attends[$item['id']]) ? $attends[$item['id']] : 0; // 默认出勤
		}
		Out(1, 'sucess', compact('event', 'data'));
	}
	
	public function submit()
	{
		$sid = Http::post('sid', 'int', 0);
		$index = Http::post('index', 'int', 0);
		$attend = Http::post('attend', 'trim', '');
		$absence = Http::post('absence', 'trim', '');
		$leave = Http::post('leave', 'trim', '');
		db()->begin();
		try
		{
			if(!$sid || !$index) throw new Exception('课程不存在！');
			//
			$date = date('Y-m-d', $index);
			$assign = load_model('assign', Null, true)
				->where('sid', $sid)
				->where('type', 1)
				->where('school', $this->school)
				->where('assigner', $this->uid)
				->where('start_date,<=', $date)
				->where('priv,&', 2)
				->or_where(array('status' => 0, 'end_date,>=' => $date))			
				->Row();					
			
			if(!$assign) throw new Exception('没有此课程，或没有权限！');
			$_Schedule = load_model('schedule', Null, true);			
			$schedule = $_Schedule->entity_row($sid, $index); //where('sid', $sid)->where('index', $index)->Row();
			if(!$schedule) throw new Exception('没有此课程，或没有权限！');
			//			
			$data =  array(
				'school' => $assign['school'],
				'sid' => $sid,
				'index' => $index,
				'type'=>0,
				'protype' => 'attend',
				'create_time' => TIMESTAMP,
				'assigner' => 0,
				'value' => 0
			);			
			if($attend)
			{
				$attend = explode(',', $attend);
				foreach($attend as $item)
				{
					if(!$item) continue;
					$res = $_Schedule->attend(array_merge($data, array('assigner' => $item, 'value' => 0)));
					if(!$res) throw new Exception('操作失败！');
				}
			}			
			if($absence)
			{
				$absence = explode(',', $absence);				
				foreach($absence as $item)
				{
					if(!$item) continue;
					$res = $_Schedule->attend(array_merge($data, array('assigner' => $item, 'value' => 1)));
					if(!$res) throw new Exception('操作失败！');
				}
			}			
			if($leave)
			{
				$leave = explode(',', $leave);
				foreach($leave as $item)
				{
					if(!$item) continue;
					$res = $_Schedule->attend(array_merge($data, array('assigner' => $item, 'value' => 2)));
					if(!$res) throw new Exception('操作失败！');
				}
			}
			
			$_Schedule->where('sid', $sid, true)->where('index', $index)->update(array('attended' => 1));

			db()->commit();
			Out(1, 'success');
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());
		}
	}

	public function history()
	{		
		$start = $this->system_start; // 系统起始时间
		$end = date('Y-m-d', time() - 86400) . " 23:59"; // 统计到前一天
		$student = Http::post('student', 'int', 0);
		$cache = $this->refresh ? false : true;
		$cache_key = "teacher_stat_" . ($student ? $student."_" : 0) . "{$this->uid}_{$end}";
		$page = Http::post('page', 'int', 1);
		$perpage = 20;
		$offset = ($page-1) * $perpage;

		$result = cache()->get($cache_key);
		if($result == false || $cache === false)
		{
			$sql = "select s.id,s.title from t_assign r left join t_series s on r.sid=s.id";
			$sql.= " Left join t_schedule i On i.sid=s.id";
			$sql.= " where r.`type`=1 And r.assigner={$this->uid} And i.attended=1";
			// $sql.= " And (r.end_date>='{$end}' or r.status=0) And s.id>0";	
			$sql.= " And r.start_date<='{$end}' And s.id>0";	
			$sql.= " Group by s.id";
			$sql.= " Order by s.`sort`";		
			$result = db()->fetchAll($sql);		
			$series = array_column($result, 'id');
			$_Record = load_model('schedule_record', Null, true)->where('type', 0, true)		
				->where('protype', 'attend')
				->where('sid,in', $series)
				->where('index,<=', strtotime($end));
			if($student)
			{
				$_Record->where('assigner', $student);
			}
			$recordSource = $_Record->Result();
			$records = Array();
			$attendence = array( 
				'attend' => 0, 
				'absence' => 0, 
				'leave' => 0
			);
			foreach($recordSource as $item)
			{				
				if(isset($records[$item['sid']]))
				{
					$_tmp = $records[$item['sid']];
				}else{
					$_tmp = $attendence;
				}
				switch($item['value'])
				{
					case 1:
						$_tmp['absence']++;
						break;
					case 2:
						$_tmp['leave']++;
						break;
					default:
						$_tmp['attend']++;
						break;
				}
				$records[$item['sid']] = $_tmp;
			}			
			foreach($result as &$sche)
			{	
				$attend=$leave=$absence=0;
				if(isset($records[$sche['id']]))
				{					
					$sche = array_merge($sche, $records[$sche['id']]);
				}else{
					$sche = array_merge($sche, $attendence);
				}	
				extract($sche);
				$sche['rate'] = 0;
				if($attend > 0)
				{
					$sche['rate'] = round($attend / ($attend+$leave+$absence) * 100, 0) . "%";
					// $sche['rate'] = sprintf("%01.2f%%", $sche['rate']);
				}				
			}
			cache()->set($cache_key, $result, 60);
		}
		$total = empty($result) ? count($result) : 0;
		$pageCount = ceil($total / $perpage);
		$page =  array('page'=>$page, 'total'=> $total, 'size'=>$perpage, 'pages'=> $pageCount);
		$data = array_slice($result, $offset, $perpage);
		Out(1, 'success', compact('page', 'data'));
	}
}
