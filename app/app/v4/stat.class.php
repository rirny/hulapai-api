<?php

class Stat_Api extends Api
{
	public function __construct(){
		parent::_init();		
		$this->refresh = Http::post('refresh', 'trim', 0);
	}
	
	/*
	 * 课程时统计
	*/
	private $system_start = '2013-10-10'; // 默认起始日期

	public function index()
	{
		
		$student = Http::post('student', 'int', 0);
		$res = load_model('user_student', Null, true)->where('student', $student, true)
			->where('user', $this->uid)
			->Row();
		if(!$res) throw new Exception('没有此学生！');
		$start = $this->system_start; // 系统起始时间
		$end = date('Y-m-d', time() - 86400) . " 23:59"; // 统计到前一天
		$cache = $this->refresh ? false : true;
		$cache_key = "student_stat_{$student}_{$start}_{$end}";
		
		$result = cache()->get($cache_key);
		if($result == false || $cache === false)
		{
			$sql = "select s.id,s.title,s.color,r.start_date,r.end_date,s.rule,r.times,r.remain from t_assign r left join t_series s on r.sid=s.id";
			$sql.= " where r.assigner={$student}";
			$sql.= " And r.start_date<='{$end}' And s.id>0";
			$seriesSource = db()->fetchAll($sql);			
			$object = array('times' => 0, 'pass' => 0, 'attend' => 0, 'absence' => 0, 'leave' => 0, 'end_date' => '');
			$recordSource = load_model('schedule_record', Null, true)->where('type', 0, true)
				->where('assigner', $student)
				->where('protype', 'attend')
				->where('index,<=', strtotime($end))
				->Result();
			$records = $result = Array();
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
			foreach($seriesSource as $sche)
			{
				$sche['pass'] = 0;
				if($sche['rule'])
				{
					import('schedule');
					$rule = json_decode($sche['rule'], true);					
					if($rule)
					{
						$events = Schedule::resolve($rule, $sche['start_date'], $sche['end_date']);
						if($events)
						{							
							$pass = array_filter($events, function($e) use($end) {
								if($e['index'] < strtotime($end)) return $e;
							});
							$pass && $sche['pass'] = count($pass);
						}
					}
				}
				$_tmp = Array();				
				if(isset($result[$sche['id']]))
				{
					$_tmp = $result[$sche['id']];
					if(strtotime($_tmp['end_date']) < strtotime($sche['end_date']))
					{
						$_tmp['end_date'] = $sche['end_date'];
					}
					$_tmp['times'] += $sche['times'];
					$_tmp['remain'] += $sche['remain'];
				}else{
					unset($sche['rule']);
					$_tmp = $sche;
				}
				$attend = $attendence;				
				isset($records[$sche['id']]) && $attend = $records[$sche['id']];
				$result[$sche['id']] = array_merge($_tmp, $attend);				
			}
			cache()->set($cache_key, $result, 60);
		}
		Out(1, 'success', array_values($result));
	}
}