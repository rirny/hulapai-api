<?php

class Event_Api extends Api
{
	public function __construct(){
		parent::_init();
		$this->refresh = Http::post('refresh', 'trim', 0);
	}
	
	// 课程下的点评
	public function comment()
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
			->where('priv,&', 1)
			->or_where(array('status' => 0, 'end_date,>=' => $date))			
			->Row();
		if(!$assign) throw new Exception('没有此课程，或没有权限！');

		$_Schedule = load_model('schedule', Null, true);
		$event = $_Schedule->virtual_row($sid, $index);
		if(!$event) throw new Exception('没有此课程，或没有权限！');
		$event['date'] = date('Y-m-d', $index);
		$event['title'] = $series['title'];
		$event['color'] = $series['color'];

		// 获取课程下的学生
		$data = $_Schedule->get_students($sid, $index);
		$_Comment = load_model('comment', Null, true);

		foreach($data as &$item)
		{
			$comment = $_Comment->field('id,teacher,student,creator,content,attach,create_time,character')
				->where('sid', $sid, true)
				->where('index', $index)
				->where('pid', 0)
				->where('student', $item['id'])
				->Row();
			$item['commented'] = 0;
			if($comment)
			{
				$comment = $_Comment->formatForApp($comment);				
				$comment['reply'] = $_Comment->clear()->field('id,teacher,student,creator,content,flower,attach,create_time,character')
						->where('pid', $comment['id'])
						->Order('create_time', 'Desc')						
						->Result();
				foreach($comment['reply'] as &$reply)
				{
					$reply = $_Comment->formatForApp($reply);
				}
				$item['commented'] = 1;
			}
			
			$item['comment'] = $comment;
		}
		Out(1, 'sucess', compact('event', 'data'));
	}	
	
	// priv 1、点评 2、考勤 4、通知
	/* 
	 * 未点评
	 * @page 当前日期
	 * @end
	*/
	private $system_start = '2013-10-10'; // 默认起始日期

	public function comment_event_list()
	{
		$start = $this->system_start; // 系统起始时间
		$end = date('Y-m-d', time() + 86400); // 当天23:59
		$page = Http::post('page', 'int', 1);
		$perpage = 20;		
		$commented = Http::post('commented', 'int', 0);
		$cache = $this->refresh ? false : true;
		$cache_key = "teacher_comment_event_list_{$this->uid}_{$start}_{$end}_{$commented}";
		$result = cache()->get($cache_key);
		if($result == false || $cache === false)
		{
			$_Schedule = load_model('schedule', Null, true);
			$param = compact('start', 'end', 'cache');
			$param['assigner'] = $this->uid;
			$events = $_Schedule->get_user_event($param, 1);			
			$result = Array();
			foreach($events as $item)
			{
				if(!($item['priv'] & 1)) continue;
				if($item['index'] > TIMESTAMP) continue;				
				$row = $_Schedule->clear()->where('sid', $item['sid'])
					->where('index', $item['index'])
					->where('commented', 1)
					->Row();				
				//$item['commented'] = $row ? 1 : 0;
				if(($commented && $row) || ($commented ==0 && !$row))
				{
					$students = $_Schedule->get_students($item['sid'], $item['index'], $cache);
					$student = $students ? join("、", array_column($students, 'name')) : "";					
					$item['student'] = $student;
					if($student)
					{
						$result[] = $item;
					}
				}	
			}
			cache()->set($cache_key, $result, 60);
		}
		$records = $result ? count($result) : 0;
		$offset = ($page-1) * $perpage;
		$pageCount = ceil($records/$perpage);
		$data = array_slice($result, $offset, $perpage);
		$data || $data = Array();

		$page =  array('page'=>$page, 'total'=> $records, 'size'=>$perpage, 'pages'=> $pageCount);
		Out(1, 'success', compact('page', 'data'));
	}
	
	
	public function attend_event_list()
	{
		$start = $this->system_start; // 系统起始时间
		$end = date('Y-m-d', time() - 86400); // 当天23:59
		$page = Http::post('page', 'int', 1);
		$sid = Http::post('sid', 'int', 0);
		$perpage = 20;

		$attended = Http::post('attended', 'int', 0);
		$cache = $this->refresh ? false : true;
		$cache_key = "teacher_attend_event_list_{$this->uid}_{$start}_{$end}_{$attended}". ($sid ? "_" . $sid : '');
		$result = cache()->get($cache_key);
		if($result == false || $cache === false)
		{
			$result = Array();
			$_Schedule = load_model('schedule', Null, true);
			$param = compact('start', 'end', 'cache', 'sid');
			$param['assigner'] = $this->uid;
			$events = $_Schedule->get_user_event($param, 1);
			foreach($events as $item)
			{
				if(!($item['priv'] & 2)) continue;				
				if($item['index'] > TIMESTAMP) continue;				
				$row = $_Schedule->clear()->where('sid', $item['sid'])
					->where('attended', 1)
					->where('index', $item['index'])
					->Row();
				if(($attended && $row) || ($attended ==0 && !$row))
				{
					$students = $_Schedule->get_students($item['sid'], $item['index'], $cache);
					$student = $students ? join("、", array_column($students, 'name')) : '';					
					$item['student'] = $student;
					if($student)
					{
						$result[] = $item;
					}
				}
			}	
			cache()->set($cache_key, $result, 60);
		}		
		$records = $result ? count($result) : 0;
		$offset = ($page-1) * $perpage;
		$pageCount = ceil($records/$perpage);			
		$data = array_slice($result, $offset, $perpage);
		$page =  array('page'=>$page, 'total'=> $records, 'size'=>$perpage, 'pages'=> $pageCount);
		$data || $data = Array();
		Out(1, 'success', compact('page', 'data'));
	}	
	
	// 详情
	public function info()
	{
		$sid = Http::post('sid', 'int', 0);
		$index = Http::post('index', 'int', 0);
		$character = Http::post('character', 'trim', 'student');
		$student = Http::post('student', 'int', 0);
		if(!$sid || !$index) throw new Exception('课程不存在!');
		$date = date('Y-m-d', $index);		
		$_Schedule = load_model('schedule', Null, true);		
		$event = $_Schedule->virtual_row($sid, $index, true);
		if(!$event) throw new Exception('课程不存在!');
		$school = load_model('school', Null, true)->field('name')
			->where('id', $event['school'])
			->limit(1)
			->Column();
		$course = load_model('course_type', Null, true)->field('name')
			->where('id', $event['course'])
			->limit(1)
			->Column();
		
		$notify = load_model('notify', Null, true)->field('id,creator,content,create_time')
			->where('sid', $sid)
			->where('index', $index)
			->Row();		
		$_Assign = load_model('assign', Null, true)
			->where('sid', $sid)
			->where('start_date,<=', $date);

		$_Comment = load_model('comment', Null, true)->field('id,content,character,creator,student,teacher,create_time')
			->where('sid', $sid)
			->where('index', $index)
			->Order('create_time', 'Desc');

		if($character == 'student')
		{
			if(!$student) throw new Exception('没有此课程');
			$assign = $_Assign->where('type', 0)
				->where('end_date,>=', $date)
				->where('assigner', $student)
				->Row();
			if(!$assign) throw new Exception('没有此课程');
			$teachers = $_Schedule->get_teachers($sid, $index);
			$_comment = $_Comment->where('student', $student)
				->where('pid', 0)
				->Row();
						
		}else{
			$assign = $_Assign->where('type', 1)
				->or_where(array('status' => 0, 'end_date,>=' => $date))
				->where('assigner', $this->uid)
				->Row();			
			if(!$assign) throw new Exception('没有此课程');
			$students = $_Schedule->get_students($sid, $index);
			
			$records = load_model('schedule_record', Null, true)->field('assigner,value')
				->where('sid', $sid, true)
				->where('index', $index)
				->where('protype', 'attend')
				->Result();
			$event['priv'] = $assign['priv'];
			$attend = $absence = $leave = 0;
			foreach($records as $record)
			{
				switch($record['value'])
				{					
					case 1:
						$absence++;
						break;
					case 2:
						$leave++;
						break;
					default:
						$attend++;
				}
			}			
			$_comment = $_Comment->Row();
			$notify || $notify = Array();			
			$attends = compact('attend', 'absence', 'leave');			
		}
		$notify || $notify = Array();
		$comment = Array();
		if($_comment)
		{
			$comment = Array(
				'id' => $_comment['id'],
				'time' => $_comment['create_time'],
				'content' => $_comment['content']
			);
			if($_comment['character'] == 'teacher')
			{
				$comment_creator = load_model('user', Null, true)->where('id', $_comment['creator'], true)->Row();
				$comment['creator'] = $_comment_creator['firstname'] . $comment_creator['lastname'];
			}else{
				$relation = load_model('user_student', Null, true)->where('user', $_comment['creator'], true)->where('student', $_comment['student'], true)->Row();
				$studentName = load_model('student', Null, true)->where('id', $_comment['student'], true)->limit(1)->Column();
				$relations = array(1 => '本人', 2 => '爸爸', 3 => '妈妈', 4 => '家长');
				$comment['creator'] = $studentName . (($relation && isset($relations[$relation])) ? $relations[$relation] : '');
			}
		}
		$result = compact('event', 'school', 'course', 'notify', 'teachers', 'students', 'attends', 'comment');
		Out(1, 'success', $result);
	}
	

	public function students()
	{
		$sid = Http::post('sid', 'int', 0);
		$index = Http::post('index', 'int', 0);
		if(!$sid || !$index) throw new Exception('没有此课程');
		$cache = $this->refresh ? false : true;
		$result = load_model('schedule', Null, true)->get_students($sid, $index, $cache);
		Out(1, 'success', $result);
	}
}