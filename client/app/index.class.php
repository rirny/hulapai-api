<?php
class Index_CLI extends Client
{
	public $app = '';
	public $act = '';
	
	public function __construct(){
		// parent::_init();
	}

	// 课程事务
	public function index()
	{
		die('index');
	}
	
	/* 课程生成
	 * 截止下周日 12:59
	 * resolve_time
	 * repeat = week
	*/
	public function create()
	{
		$w = date('w');
		$week = 14 - $w + 1;
		$cut = strtotime(date('Y-m-d')) + $week * 86400;
		$cutDate = date('Y-m-d', $cut);

		$seriesRes = load_model('series', Null, true)->where('status', 1)->Result();
		$series = Array();
		foreach($seriesRes as $item)
		{
			$item['rule'] = @json_decode($item['rule'], true);
			if(!$item['rule']) continue;
			$series[$item['id']] = $item;
		}
		
		$force = (empty($argv[3])) ? true : false;	

		// 排课最早的日期
		$_Assign = load_model('assign', Null, true)->clear()
			->where('start_date,<=', $cutDate)
			->where('type', 1)
			->Order('start_date', 'Desc');
		$force || $_Assign->where('update_time,<', $cut);			
		$assign = $_Assign->Result();		
		import('schedule');
		ob_start();
		db()->begin();
		try
		{
			$logs = '';
			foreach($assign as $key => $val)
			{
				$seriesItem = $series[$val['sid']];				
				if(empty($seriesItem['rule'])) continue;
				$end = (strtotime($val['end_date']) < $cut && $val['end_date'] != '0000-00-00') ? $val['end_date'] : $cutDate; // 结束时间			
				$start_date = $val['update_time'] > strtotime($val['start_date'])  ? date('Y-m-d', $val['update_time']) : $val['start_date'];			
				$sches = Schedule::resolve($seriesItem['rule'], $start_date, $end);				
				if(empty($sches)) continue;			
				echo $seriesItem['title'] . "\t" . $seriesItem['id'] . "\t" . $val['assigner'] . "\t{$val['start_date']}~{$val['end_date']}\t" . ($start_date . "~" . $end) . "\r\n";			
				foreach($sches as $sche)
				{					
					$res = load_model('schedule', Null, true)
						->where('sid', $val['sid'], true)
						->where('index', $sche['index'])
						->Row();					
					if($force)
					{
						$res && load_model('schedule', Null, true)->delete(true);
					}else
					{ 
						if($res) continue;						
					}
					$data = array(
						'sid' => $val['sid'],
						'index' => $sche['index'],
						'start' => date('H:i', $sche['start']),
						'end' => date('H:i', $sche['end']),
						'class_times' => $sche['times']
					);					
					$id = load_model('schedule', Null, true)->insert($data);
					if(!$id) throw new Exception('插入失败！');
					echo "\t" . date('Y-m-d', $data['index']) . "\r\n";
				}
				
				$update = array(
					'update_time' => $cut
				);
				if($val['type'] == 0)
				{
					$pass = load_model('schedule', Null, true)->clear()
						->where('sid', $val['sid'])
						->where('status', 0)
						->where('index,>=', strtotime($val['start_date']))
						->where('index,<=', strtotime($val['end_date']))
						->where('index,<=', strtotime(date('Y-m-d')))
						->Count();
					$remain = $val['times'] - $pass;
					$remain > 0 || $remain = 0;
					$update['remain'] = $remain;
					echo "{$val['id']}\t总课次:{$val['times']}\t已上：{$pass}\t剩余：{$val['remain']} - " . $remain . "\r\n";
				}
				$res = load_model('assign', Null, true)->clear()->where('id', $val['id'])->Update($update);
				echo "--------------------- success ---------------------------\r\n\r\n";
			}
			$path = SYS . '/logs/schedule/' . date('Y');
			_mkdir($path);
			$output = ob_get_contents();
			file_put_contents($path . "/" . date('Y-m-d') . '.txt', $output, FILE_APPEND);
			ob_end_flush();
			db()->commit();
		}catch(Exception $e)
		{
			db()->rollback();
			die($e->getMessage());
		}
	}
	
	/* 截止考勤
	 * 截止下周日 12:59
	 * resolve_time
	 * repeat = day
	*/
	public function attend()
	{		
		$cut = strtotime(date('Y-m-d')) - 7 * 86400;
		// 一周内未考勤
		$schedule = load_model('schedule', Null, true)->where('attended', 0)
			->where('status', 0)
			->where('index,>=', $cut)
			->where('attended', 0)
			->limit(5)
			->Result();
		
		$_Assign = load_model('assign', Null, true);
		$_Record = load_model('schedule_record', Null, true);
		ob_start();
		db()->begin();		
		try
		{
			foreach($schedule as $item)
			{
				$date = date('Y-m-d', $item['index']);
				$start = strtotime($date);
				$end = $start + 86400;
				$assigners = $_Assign->where('type', 0, true)
					->where('sid', $item['sid'])
					->where('start_date,<=', $date)
					->where('end_date,>=', $date)
					->Result();	
				$data = $students = $records = $change = Array();
				$school = 0;
				foreach($assigners as $assigner)
				{					
					$assigner['assigner'] . "\r\n";
					$students[] = $assigner['assigner'];
					$school = $assigner['school'];
				}
				$recordRes = $_Record->where('sid', $item['sid'], true)
					->where('index', $item['index'])
					->or_where(array('protype' => 'attend', 'protype' => 'change')) // 已考勤或已调课
					->Result();
				
				foreach($recordRes as $record)
				{							
					$change[] = $record['assigner'];
				}	
				
				$data = array_unique(array_diff($students, $change));
				$newRecord = array(
					'sid' => $item['sid'],
					'index' => $item['index'],
					'school' => $school,
					'protype' => 'attend',
					'value' => 0,
					'create_time' => time()
				);				
			
				if($data)
				{
					
					foreach($data as $val)
					{
						$newRecord['assigner'] = $val;
						$rid = $_Record->insert($newRecord);
						if(!$rid) throw new Exception('attend Fail!');
						echo "Series:{$item['sid']}\tIndex:{$item['index']}\tStudent:{$val}\r\n";
					}
					
					$res = load_model('schedule', Null, true)
						->where('sid', $item['sid'], true)
						->where('index', $item['index'])
						->update(array('attended' => 1));
					if(!$res) throw new Exception('attend Fail!');
				}
			}
			db()->commit();
			$path = SYS . '/logs/attend/' . date('Y');
			_mkdir($path);
			$output = ob_get_contents();
			file_put_contents($path . "/" . date('m-d') . '.log', $output, FILE_APPEND);
			ob_end_flush();
		}catch(Exception $e)
		{
			db()->rollback();
			echo $e->getMessage();
		}		
	}



}
