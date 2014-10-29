<?php
/* 说明：课程
 * 作者: lyl
 * 时间：2014/7/19
*/
class Schedule
{
	public function __construct()
	{
		
	}
	/* 课程处理
	 * @rule = [
		{id : ruleid, week:0-6, start:分钟, end:分钟},
		{id : ruleid, week:0-6, start:分钟, end:分钟}
	] // 多条规则
	 * @start = yyyy-mm-dd
	 * @end	= yyyy-mm-dd	
	*/
	public static function resolve(Array $rules, $start, $end='', $times=0)
	{		
		$result = Array();
		if(empty($rules) || $start == '') goto STOP;		
		if($end == '' && $times == 0) goto STOP; // 结束时间和课次都没有不作处理
		if($end)
		{
			$end .= " 23:59:59";
			$end = strtotime($end);
			if( $end < strtotime($start))	goto STOP;		
		}
		$form = $times ? 'TIMES' : 'DATE';
		$endTime = 0;	
		$rules = self::ruleSort($rules, $start);
		LOOP:		
		for($i=0; $i< count($rules); $i++)
		{			
			$rule = $rules[$i];
			if($end && $rule['start'] > $end) goto STOP;
			$rule['index'] =  $rule['start'];
			$result[] = $rule;
			$nextWeek = $rule['start'] + (7 * 86400);
			if($form == 'DATE' && $nextWeek > $end)
			{
				unset($rules[$i]);
			}
			$rules[$i]['start'] = $nextWeek;
			$rules[$i]['date'] = date('Y-m-d', $nextWeek);
			$rules[$i]['end'] = $rule['end'] + (7 * 86400);
			$times--;
			if($form == 'TIMES' && $times == 0) goto STOP;
		}		
		reset($rules);
		if( ($form == 'TIMES' && $times > 0) || ($form == 'DATE' && !empty($rules)) ) goto LOOP;		
		STOP:
		return $result;
	}
	
	// 获取单节
	public function single(Array $rules, $index)
	{
		$date = date('Y-m-d', $index);
		$result = self::resolve($rules, $date, $date);
		return current(array_filter($result, function($v) use ($index){
			if($v['index'] == $index) return $v;
		}));
	}
	
	/* 排序值
	 * @rule = [{week:0-6, start:分钟, end:分钟},{week:0-6, start:分钟, end:分钟}]	
	*/
	public static function getSort(Array $rules)
	{
		$index = Array();
		foreach($rules as &$item)
		{		
			$index[] = $item['sort'] = $item['week'] * 24 * 60 + $item['start'];
		}
		array_multisort($index, SORT_ASC, $rules);
		return $rules;
	}
	
	/* 规则排序
	 * @start 开始时间
	 * @rule = [{week:0-6, start:分钟, end:分钟},{week:0-6, start:分钟, end:分钟}]	
	*/
	private static function ruleSort(Array $rules, $start)
	{
		if(empty($rules) || !$start) return $rules;
		$start = strtotime($start);
		$start_week_day = date('w', $start); // 开始时间这天是星期几
		$index = Array();
		foreach($rules as &$val)
		{			
			if($val['week'] >= $start_week_day) 
			{
				$diff = $val['week'] - $start_week_day;
			}else{
				$diff = $val['week'] - $start_week_day + 7;
			}
			$_start = mktime(0,0,0,date('n', $start), date('j', $start)+ $diff, date('Y', $start));
			$index[] = $val['start'] = $_start + ($val['start']*60);
			$val['date'] = date('Y-m-d', $val['start']);
			$val['end'] = $_start + ($val['end']*60);			
		}		
		array_multisort($index, SORT_ASC, $rules);
		return $rules;
	}
}

/* Example
$rules = array(	
	array('id' => 1,'week' => 0, 'start' => 540, 'end' => 600), // 每周日9:00 - 10:00
	array('id' => 2, 'week' => 1, 'start' => 600, 'end' => 660), // 每周一10:00 - 11:00
	array('id' => 3, 'week' => 0, 'start' => 480, 'end' => 600), // 每周日8:00 - 9:00
	array('id' => 4, 'week' => 4, 'start' => 720, 'end' => 780), // 每周四12:00 - 13:00
);
$start = '2014-07-06';
$end = '2014-09-30';
$times = 3;
// $schedule = Schedule::resolve($rules, $start, $end, $times);

$schedule = Schedule::single($rules, 1404604800);
print_r($schedule);

/          [start] => 1404604800
/*取规则的排序值
$rules = array(	
	array('id' => 1, 'week' => 0, 'start' => 540, 'end' => 600), // 每周日9:00 - 10:00
	array('id' => 2, 'week' => 1, 'start' => 600, 'end' => 660), // 每周一10:00 - 11:00
	array('id' => 3, 'week' => 0, 'start' => 480, 'end' => 600), // 每周日8:00 - 9:00
	array('id' => 4, 'week' => 4, 'start' => 720, 'end' => 780), // 每周四12:00 - 13:00
);
$rules = Schedule::getSort($rules);
print_r($rules);
*/