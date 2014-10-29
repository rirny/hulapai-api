<?php
class Push
{
	private $app = '';
	private $from = 0;	
	private $to = 0;
	private $message = '';
	private $student = 0;
	private $ext = array();
	private $act = '';
	private $character = 0;

	private $_character = '';	
	private static $instance = Null;

	private $_list = '';

	public function __construct($handle = 'redis')
	{	
		if($handle == 'db')
		{			
			$this->handle = load_model('push');
		}else{
			$this->handle = redis();
		}
	}

	public static function get_instance($handle='redis')
	{		
		if(self::$instance == NULL){  
            self::$instance = new Push($handle);  
        }         
        return self::$instance;
	}
	
	private $_characters = array(
		'user' => '用户', 
		'student' => '学生', 
		'teacher' => '老师', 
		'school' => '机构', 
		'friend' => '好友'
	);

	/* @app = event|apply|notify|feed|friend|blog|comment
	 * @act = add|remove|update
	 * @from 发送者 
	 * @to 接收者 $student = 1 to为studentID	
	 * @type 0推送但不响|震动 1,响铃\震动
	 * @character 角色
	 * @ext 来源的对象 Apply ext=扩展(用户角色,操作属性等)
	 * 
	*/
	public function add($list='H_PUSH', $data)
	{		
		$_data = array();
		$dataModel = $this->init($data);
		if(is_array($dataModel['to'])){
			$toArray = $dataModel['to'];
			unset($dataModel['to']);
			foreach($toArray as $to){
				$dataModel['to'] = $to;
				$this->handle->push($list, $dataModel);
			}
		}else{
			$this->handle->push($list, $dataModel);
		}		
		return true;
	}

	private function init($data)
	{	
		extract($data);		
		if(!isset($app) || !isset($act) || !isset($from) || !isset($to)) throw new Exception("Push Error!");
		foreach($data as $key => $item)
		{
            // $key == 'ext' && $item = json_decode($item, true);
			$this->$key = $item;
		}		
		(isset($message))  && $this->message || $this->message = $this->get_message();
		$this->_character = $this->get_character();
		return $this->to_array();
	}
	
	public function __set($key, $item)
	{
		$this->$key = $item;
	}

	public function __get($key)
	{
		return $this->$key;
	}

	// 获取信息
	private function get_message()
	{		
		if($this->act == "delete"){
			$deleteExt = '';
			switch($this->app)
			{
				case 'event':
					$deleteExt = $this->ext['event'];
				break;
				default:
				break;
			}
			if($deleteExt){
				$_Delete = load_model('delete_log');
				//记录deletelog
				$deleteLog = array(
					'app' => $this->app,
					'to' => $this->to,
					'student'=>$this->student,
					'ext'=> $deleteExt
				);
				if(!$_Delete->getRow($deleteLog)){
					$deleteLog['create_time'] = time();
					$_Delete->insert($deleteLog);
				}
				
			}
		}
		
		
		// if($this->type < 1) return ''; // 0推送 1、消息 2、提醒
		$result = '';
		$deleteExt = '';
		switch($this->app)
		{
			case 'apply':
				if($this->act == 'add')
				{
					$result = sprintf('您收到一个新的%s申请%s', $this->_character, (isset($this->ext['apply']['message']) ? $this->ext['apply']['message'] : ""));
				}else{ // deal/pass					
					// @nickname(用户)通过了您的好友申请
					// @nickname(老师)通过了您的申请
					// @nickname(学生)通过了您的申请
					// @name(机构)通过了您的申请
					$type = (isset($this->ext['apply']) && $this->ext['apply']['type'] == 5)  ? '好友' : '';
					$name = (isset($this->ext[$this->character]['name'])) ? $this->ext[$this->character]['name'] : '';
					$result = sprintf("%s(%s)通过了您的申请", $this->_character, $name, $type);
				}
			break;
			case 'notify':
				if($this->act == 'add')
				{		
					if($this->ext['type'] == 1){
						$result = '课程消息:您收到一条新通知。';
					}elseif($this->ext['type'] == 2){
						$result = '问卷消息:您收到一条新问卷。';
					}else{
						$result = '系统消息:您收到一条新消息。';
					}
				}
			break;
			case 'event':
                $result = $this->event();
			break;
			// 新的微博
			case 'feed':
				if($this->act == 'add')
				{
					//$result = '您有新的微博更新';
					$result = '您有新的意见反馈';
				}
			break;
			// 新的粉丝
			case 'feed_follow':
				if($this->act == 'add')
				{
					$result = '您有新的粉丝';
				}
			break;
			// 新的微博评论
			case 'feed_comment':
				if($this->from == $this->to) break;
				if($this->act == 'add')
				{
					if($this->ext['to_comment_id']){
						$result = '您的评论收到一个新的回复';
					}else{
						$result = '您的微博收到一个新的评论';
					}
				}
			break;
			// 新的呼啦圈动态
			case 'blog':
				if($this->act == 'add')
				{
					$result = '您有新的动态更新';
				}
			break;
			// 新的呼啦圈动态评论
			case 'blog_comment':
				if($this->from == $this->to) break;
				if($this->act == 'add')
				{
					if($this->ext['to_comment_id']){
						$result = '您的评论收到一个新的回复';
					}else{
						$result = '您的动态收到一个新的评论';
					}
				}
			break;
			case 'comment':
                $event = empty($this->ext['event']) ?  false : true;
                if($this->act == 'add')
                {
                    $result = '收到一个新点评';
                    $event && $result . "(课程) ";
                }else if ($this->act == 'reply') {
                    $result = '收到一个新的点评回复！';
                }
				// 收到一个新的家长评价
				// 收到一个新的老师评价
				// 收到一个新的课程评价
				// 收到一个点评回复
			break;
			case 'student':
				if($this->act == 'add')
				{
					$result = '有一个新学生！';
				}
			break;
			case 'teacher':
				if($this->act == 'add')
				{
					$result = '有一个新老师！';
				}
			break;
			case 'school':
				if($this->act == 'add')
				{
					$result = '有一个新机构！';
				}
			break;
			default:
				$result = '';
			break;
		}
		return $result;
	}
	
	// 课程相关
	private function event()
	{		
		if($this->character == 'student') // 发给学生
		{
			$student = load_model('student')->getRow($this->student, true, 'name,nickname');	
			$to = $student['name'];
		}else{ // 发给老师			
			$teacher = load_model('user')->getRow($this->to, true, 'firstname,lastname,nickname');
			$to = $teacher['firstname'] . $teacher['lastname'];
		}
		if($this->act == 'add')
		{			
			if(empty($this->ext['event'])) return false;
			$event = load_model('event')->getRow($this->ext['event'], false, 'id,text,start_date,end_date,rec_type,is_loop,teacher,school');			
			if($this->character == 'student')
			{
				$relation = load_model('student_course')->getRow(array('event' => $this->ext['event'], 'student' => $this->student), false, 'remark,start_date,end_date');
				if(isset($relation['start_date']) && $relation['start_date'] == '0000-00-00 00:00:00') unset($relation['start_date']);
				if(isset($relation['end_date']) && $relation['end_date'] == '0000-00-00 00:00:00') unset($relation['end_date']);
			}else{
				$relation = load_model('teacher_course')->getRow(array('event' => $this->ext['event'], 'teacher' => $this->to), false, 'remark');
			}
			$event = array_merge($event, $relation);
			$result = "{to}您好，您收到{from}{character}的新课程\n{text}\n{date}";			
		}else if($this->act == 'update')
		{
			if(empty($this->ext['event'])) return false;            
			$event = load_model('event')->getRow($this->ext['event'], false, 'id,text,start_date,end_date,rec_type,is_loop,teacher,school');			
			if($this->character == 'student')
			{
				$relation = load_model('student_course')->getRow(array('event' => $this->ext['event'], 'student' => $this->student), false, 'remark');
			}else{
				$relation = load_model('teacher_course')->getRow(array('event' => $this->ext['event'], 'teacher' => $this->to), false, 'remark');
			}
			$event = array_merge($event, $relation);			
			$result = "{to}您好，您的课程发生变更，变更后的内容如下\n{text}\t{from}{character}\n{date}";
			// $old = $this->get_event_time($this->old['start_date'], $this->old['end_date'], $this->old['rec_type']);
			// 原始时间
		}else if($this->act == 'delete')
		{
			$event = $this->ext['old'];
			$event['remark'] = $event['text'];
			$result = "{to}您好，以下课程被{from}{character}取消\n{text}\n{date}";
		}
		if(!empty($event['school']))
		{
			$school = load_model('school')->getRow($event['school'], true, 'name');
			$character = '（机构）';
			$from = $school ? $school['name'] : '';
		}else
		{
			$teacher = load_model('user')->getRow($event['teacher'], true, 'firstname,lastname,nickname,hulaid');
            if($teacher['firstname'] && $teacher['lastname'])
            {
                $from = $teacher['firstname'] . $teacher['lastname'];
            }else if($teacher['nickname']){
                $from = $teacher['nickname'];
            }
			$character = '（老师）';
		}
		$date = $this->get_event_time($event['start_date'], $event['end_date'], $event['rec_type']);
		$result = str_replace(array('{to}', '{from}', '{text}', '{character}', '{date}'),array($to, $from, $event['remark'], $character, $date), $result);
		return $result;
	}

	public function get_event_time($start_date, $end_date, $rec_type)
	{		
		$date = date("Y-m-d", strtotime($start_date));
		$time = date("H:i", strtotime($start_date)) ."-" . date("H:i", strtotime($end_date));
		$end = date("Y-m-d", strtotime($end_date));

		if($rec_type)
		{	
			list($recStr, $e) = explode("#", $rec_type);
			list($type, $step, $_t1, $_t2, $week) = explode("_", $recStr, 5);			
			switch ($type)
			{
				case 'day':                            
					$rate =  "每". ($step > 1 ? $step : '') . "天";
					break;
				case 'week':
					$resourse = array(
						0 => '日', 1 => '一', 2 => '二', 3 => '三',  4 => '四',  5 => '五',  6 => '六',
					);					
					if(!$week)
					{
						$week = date('w', strtotime($start_date));
						$weekStr = $resourse[$week];
					}else if(strpos($week, ",")){
						$weeks = explode(",", $week);
						$str = array();
						foreach($weeks as $w)
						{
							$str[] = $resourse[$w];
						}
						$weekStr = join("、周", $str);
					}else{
						$weekStr = $resourse[$week];
					}                   
					$rate .= "每" . ( $step == 2 ? '两' : ($step > 2 ? $resourse[$step] : '')) . "周 周" . $weekStr;
					break;
				case 'month':
					$rate =  "每". ($step > 1 ? $step : '') . "月 " . date('d', strtotime($start_date)) . "日";
					break;
			}
			$result = "{$date}至{$end}\n{$rate} {$time}";
		}else{
			$result = "{$date} {$time}";
		}		
		return $result;
	}

	// 获取用户角色
	private function get_character()
	{
		if(isset($this->_characters[$this->character]))
		{
			return $this->_characters[$this->character];
		}else{
			return '';
		}
	}

	private function to_array()
	{
		return array(
			'app' => $this->app,
			'act' => $this->act,
			'from' => $this->from,
			'type' => $this->type,
			'to' => $this->to,
			'student' => $this->student,
			'ext' => $this->ext,
			'message' => $this->message
		);	
	}
}