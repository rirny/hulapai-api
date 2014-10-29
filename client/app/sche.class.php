<?php
class Sche_CLI extends Client
{
	public $app = '';
	public $act = '';
	
	public function __construct(){
		// parent::_init();
	}
	
	public function index()
	{
		die('index');
	}
	
	/* 旧课转换
	*/
	public function form()
	{
		// 取所有循环课程
		$series = load_model('event', Null, true)
			->where('pid', 0)
			->where('rec_type,!=', '')	
			// ->where(Array('rec_type,like' => 'week_1', 'rec_type,like' => 'day_1')) // 一次频率
			->where('school,>', 0)
			->where('series', 0)	
			//->where('school', 20)
			->Order('create_time', 'Desc')
			->limit()
			->Result();
		
		import('schedule');
		ob_start();
		$path = SYS . '/logs/old/';		
		foreach($series as $key => $sche)
		{		
			$this->_format($sche);
			$output = ob_get_contents();
			$_path = $path . "/school_" . $sche['school'];
			_mkdir($_path);	
			file_put_contents($_path . "/{$sche['id']}.log", $output, FILE_APPEND);
			ob_end_flush();
			// ob_flush();
			ob_start();
		}					
	}
	
	function _format($sche)
	{
		$db = db();
		$db->begin();
		try
		{
			$logs = "Series:{$sid} - {$sche['text']}\r\n";
			echo $sche['rec_type'];
			list($recStr, $times) = explode("#", $sche['rec_type']);			
			$times || $times = 1;				
			list($repeat, $step, $_a, $_b, $w) = explode("_", $recStr);
			if($step > 1) throw new Exception('Format Error');// 每两天，每两周不处理
			if($repeat == 'day')
			{
				$weeks = range(0, 6);
			}else if($repeat == 'week'){
				$weeks = explode(",", $w);
			}
			$start = date('G', strtotime($sche['start_date'])) * 60 + intVal(date('i', strtotime($sche['start_date'])));
			$end = date('G', strtotime($sche['end_date'])) * 60 + intVal(date('i', strtotime($sche['end_date'])));
			$times = $sche['class_time']; // 课次
			if(empty($weeks)) throw new Exception('Week Error'); 
			$rule = Array();				
			$w = Null;
			foreach($weeks as $week)
			{
				$w === Null || $w = $week;
				$rule[] = compact('week', 'start', 'end', 'times');
			}				
			$sort = ($w * 24*60) + $start;
			$course = load_model('course', Null, true)
				->where('id', $sche['course'], true)
				->field('type')
				->limit(1)
				->Column();
			$course || $course = 112;
			$data = array(
				'title' => $sche['text'],
				'color' => $sche['color'],
				'school' => $sche['school'],
				'create_time' => $sche['create_time'],
				'creator' => $sche['creator'],
				'course' => $course,
				'week' => $weeks,
				'rule' => $rule,
				'sort' => $sort,
				'status' => 2,
				//'resolve_time' => strtotime($sche['end_date'])
			);
			
			$sid = load_model('series', Null, true)->insert($data);	
			if(!$sid) throw new Exception('Series Create Error');
			$logs .= "\tSeries Create Success";

			// 更新
			$res = load_model('event', Null, true)
				->where('id', $sche['id'], true)
				->update(array(
					'series' => $sid
				));
			if(!$res) throw new Exception('Event Update Error');
			$logs .= "\tEvent Update Success\r\n";
			

			$_count = 0; // 子课程总数
			$_assign = 0; // 是否排课			
			
			// 子课
			$subItems = load_model('event', Null, true)
				->where('pid', $sche['id'], true)
				->limit()
				->Result();
			$logs .= "\tSchedule：Count(". count($subItems) . ")\r\n";
			
			foreach($subItems as $item)
			{
				$_index = strtotime($item['start_date']);
				$ex = load_model('schedule', Null, true)->where('sid', $sid, true)
					->where('index', $_index)						
					->Row();
				if($ex) continue;

				$subid = load_model('schedule', Null, true)->insert($_e = array(
					'sid' => $sid,
					'index' => $_index,
					'start' => date('H:i', $_index),
					'end' => date('H:i', strtotime($item['end_date'])),
					'attended' => $item['attended'],
					'commented' => $item['commented'],
					'class_times' => $item['class_time'],
					'status' => $item['status']
				));
				if(!$subid) throw new Exception('Schedule Create Error');					
				$logs .= "\t\tSchedule {$subid} Create Success\r\n";
				// 考勤t_
				$studentItems = load_model('course_student', Null, true)->where('event', $item['id'], true)->Result();
				foreach($studentItems as $record)
				{
					$ex = load_model('schedule_record', Null, true)->where('sid', $sid, true)
						->where('index', $_index)
						->where('assigner', $record['student'])
						->where('protype', 'attend')	
						->Row();
					if($ex) continue;
					$attend = 0;
					if($record['absence'])
					{
						$attend = 1;
					}else if($record['absence'])
					{
						$attend = 2;
					}
					$res = load_model('schedule_record', Null, true)->insert($_e = array(
						'sid' => $sid,
						'index' => $_index,
						'protype' => 'attend',
						'value' => $attend,
						'assigner' =>  $record['student'],
						'school' => $sche['school'],
						'create_time' => strtotime($item['modify_time'])
					));
					if(!$res) throw new Exception('Attend Error');
					$logs .= "\t\t\tStudent:{$record['student']}\t{$attend}\r\n";
				}
				// 点评更新
				$res = load_model('comment', Null, true)->where('event', $item['id'], true)->update(array(
					'target' => 'event',
					'sid' => $sid,
					'index' => $_index
				));
				// if(!$res) throw new Exception('Schedule Commit Update Error');
				$logs .= "\t\tSchedule Commit Success\r\n";
				$_count++;
			}

			$_count != $times &&  $times = $_count;

			// 学生排课
			$students = load_model('course_student', Null, true)
				->where('event', $sche['id'], true)
				->Result();
			foreach($students as $student)
			{
				$ex = load_model('assign', Null, true)->where('sid', $sid, true)
					->where('type', 0)
					->where('assigner', $student['student'])
					->Row();
				if($ex) continue;

				$res = load_model('assign', Null, true)->insert($_c=array(
					'sid' => $sid,
					'assigner' => $student['student'],
					'type' => 0,
					'start_date' => $student['start_date'] != '0000-00-00 00:00:00' ? date('Y-m-d', strtotime($student['start_date'])) : date('Y-m-d', strtotime($sche['start_date'])),
					'end_date' => $student['end_date'] != '0000-00-00 00:00:00' ? date('Y-m-d', strtotime($student['end_date'])) : date('Y-m-d', strtotime($sche['end_date'])),
					'times' => $times,
					'school' => $sche['school'],
					'create_time' => $sche['create_time']
				));				
				if(!$res) {
					throw new Exception('Assign Student Error');
				}
				// 成功
				$_assign = 1;
				$logs .= "\tAssign Student Success\r\n";				
			}
			// 老师排课
			$teachers = load_model('course_teacher', Null, true)
				->where('event', $sche['id'], true)
				->Result();
			foreach($teachers as $teacher)
			{
				$ex = load_model('assign', Null, true)->where('sid', $sid, true)
					->where('type', 1)
					->where('assigner', $teacher['student'])
					->Row();
				if($ex) continue;

				$res = load_model('assign', Null, true)->insert($_c=array(
					'sid' => $sid,
					'assigner' => $teacher['teacher'],
					'type' => 1,
					'start_date' => $teacher['start_date'] != '0000-00-00 00:00:00' ? date('Y-m-d', strtotime($teacher['start_date'])) : date('Y-m-d', strtotime($sche['start_date'])),
					'end_date' => $teacher['end_date'] != '0000-00-00 00:00:00' ? date('Y-m-d', strtotime($teacher['end_date'])) : date('Y-m-d', strtotime($sche['end_date'])),						
					'school' => $sche['school'],
					'create_time' => $sche['create_time'],
					'priv' => ($teacher['priv'] >> 1), // 左移一位
					'status' => 2 // 结课
				));
				if(!$res) throw new Exception('Assign Teacher Error！');
				$logs .= "\tAssign Teacher Success\r\n";		
			}
			
			load_model('series', Null, true)->where('id', $sid, true)->update(array(
				'assign' => $_assign
			));
			
			$res = load_model('event', Null, true)->update(array(
				'series' => $sid
			));
			if(!$res) throw new Exception('Event Sub Error');
			$logs .= "\tEvent Sub Success\r\n";
			$db->commit();
			echo $logs .= "------------------  success -------------------\r\n\r\n";
			// $result = true;
		}catch(Exception $e)
		{
			$db->rollback();
			echo $logs .= "\r\n ------------------  Fail:" . $e->getMessage() . " -------------------\r\n";
			$result =  false;
		}
		$db->close();
		// return $result;
	}
}