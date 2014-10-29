<?php
/**
 * msgtype
 * SSdesc  点评
 * SSend
 */
class Comment_Api extends Api
{
	public function __construct(){
		parent::_init();
	}

	// 信息
	// @id
	public function info()
	{
		$id = Http::post('id', 'int', 0);		
		if(!$id) throw new Exception('点评失败！');
		$result = load_model('comment')->getRow($id, true);		
		if(!$result) throw new Exception('点评失败！');
		empty($result['event']) || $result['event'] = load_model('event')->getRow($result['event'], true, 'id,text,color,start_date,end_date');
		empty($result['creator']) || $result['creator'] = load_model('user')->getRow($result['creator'], true, 'id,nickname,firstname,lastname,hulaid,avatar');
		$result['reply'] = load_model('comment')->getAll(array('pid' => $id), '', 'create_time Asc', false, true);		
        $result['attach'] = $this->_get_attach($result['attach']);
		
		// 是否献过花 2014/6/12
		$res = load_model('comment')->getRow(array('pid' => $id, 'creator' => $this->uid, 'character' => 'student', 'event,>' => 0, 'flower,>' => 0));
		$result['has_flower_sent'] = $res ? 1 : 0;
		
		if($result['reply'])
		{
			foreach($result['reply'] as $key => &$item)
			{
				$item['relation'] = 0;
                if($item['character'] == 'student')
                {
					$rs = load_model('user_student')->getRow(array('student' => $item['student'], 'user' => $item['creator']));					
                    $rs && $item['relation'] = $rs['relation'];
                }
				empty($item['student']) || $item['student'] = load_model('student')->getRow($item['student'], true, 'id,name,nickname,avatar');
				empty($item['teacher']) || $item['teacher'] = load_model('user')->getRow($item['teacher'], true, 'id,nickname,firstname,lastname,hulaid,avatar');
				empty($item['creator']) || $item['creator'] = load_model('user')->getRow($item['creator'], true, 'id,nickname,firstname,lastname,hulaid,avatar');                
                $item['attach'] = $this->_get_attach($item['attach']);
			}
		}
		out(1, '', $result);
	}

	/*
	 * @student
	 * @event 课程
	 * @character 老师|家长|机构
			From			页面			身份		内容说明
		0	首页（老师档案）	点评列表		老师		老师发出、收到（用户）              teacher=@uid character=teacher
		1	点评列表			课程点评		老师		某课程下所有学生的课程点评（课程)   event=@event  character=teacher
		2	首页学生档案		点评			学生		所有（用户+ 课程）                  sutdent=@student character=teacher
		3	老师详情			点评			ALL		老师收到的所有点评（用户）          teacher=@teacher character=student event=0
		4	老师详情			点评记录		学生		学生与老师（用户+课程）             teacher=@teacher student=@student
		5	学生详情			点评			All		学生收到的所有点评（用户）          student=@student character=teacher
		6	学生详情			点评记录		老师		与该老师相关的（用户+课程）	      student=@student teacher=@teacher 
		2	学生详情			点评记录 
	*/
	public function getList()
	{
		$character = Http::post('character', 'string', 'teacher');
		$event = Http::post('event', 'int', 0);
		$student = Http::post('student', 'int', 0);
		$teacher = Http::post('teacher', 'int', 0);
		$school = Http::post('school', 'int', 0);
		$type = Http::post('type', 'int', 0);
        $page = Http::post('page', 'int', 0);
        $reply = Http::post('reply', 'int', 0);
        $perpage = 10;
        $limit = $page > 1 ? (($page - 1) * $perpage) . ',' : '';
        $limit.= $perpage;

		$where['status'] = 0;		
		$where['pid'] =0;
		$school && $where['school'] = $school;
		$student && $where['student'] = $student;
		$teacher && $where['teacher'] = $teacher;
		$event && $where['event'] = $event;
		$_Comment = load_model('comment');
		switch($type)
		{
			case 0: // 老师  -> 点评 -> 我的点评
				$where['teacher'] = $this->uid;				
                $where['character'] = 'teacher';        
				$whereStr = $_Comment->whereExp($where);
				break;
			case 1: // 课程 -> 点评			
				if(!$event) throw new Exception('参数错误@Er.param[event]');		
				if($character != 'teacher') // 学生 -> 课程 -> 点评记录
				{
					if(!$student) throw new Exception('参数错误@Er.param[student]');                 
                    $relation = load_model('student_course')->getRow(array('student' => $student, 'event' => $event));
					if(!$relation) throw new Exception('没有此课程@Er.param[event]');   
					$whereStr = $_Comment->whereExp($where);
				}else{ // 老师 -> 课程 -> 点评记录
					if(!load_model('teacher_course')->getRow(array('teacher' => $this->uid, 'event' => $event))) throw new Exception('没有此课程@Er.param[event]');					
					// $where['teacher'] = $this->uid;
					$whereStr = $_Comment->whereExp($where);
					$whereStr .= " And ( `teacher`=" . $this->uid . " Or `character` = 'school')";					
				}	
				break;
			case 2: // 学生 -> 点评				
				if(!$student) throw new Exception('参数错误@Er.param[student]');
                $where['character,in'] = array('teacher', 'school');
				if(!load_model('user_student')->getRow(array('user' => $this->uid, 'student' => $student))) throw new Exception('没有学生@Er.param[student]');
				$whereStr = $_Comment->whereExp($where);
				break;
            case 3: // 评价
				if(!$teacher) throw new Exception('参数错误@Er.param[teacher]');
				$where['event'] = 0;// 评价
				$where['character,!='] = 'teacher';
				unset($where['student']);				
				$whereStr = $_Comment->whereExp($where);
				break;
            case 4: // 学生 -> 老师详情 -> 点评记录
				if(!$teacher) throw new Exception('参数错误@Er.param[teacher]');
				$where['character'] = 'teacher';
				$whereStr = $_Comment->whereExp($where);
				break;
            case 5: // 学生评价 去除 
				if(!$student) throw new Exception('参数错误@Er.param[student]');
				$where['event'] = 0;
				$where['character,!='] = 'student';
				$whereStr = $_Comment->whereExp($where);
				break;
            case 6: // 老师 ->学生详情 -> 点评记录
				if(!$student) throw new Exception('参数错误@Er.param[student]');
                $where['teacher'] = $this->uid;
				$where['character'] = 'teacher'; // 老师发出的
				$whereStr = $_Comment->whereExp($where);
				break;
			case 7: // 对机构的评价
				$whereStr = "pid=0 And `event`=0 And school='{$school}' And ((student=0 And `character`='teacher') or (teacher=0 And `character`='student'))";
				break;
		}		
		// $limit
		// $result = $_Comment->getAll($where, $limit, 'create_time Desc', false, false);
		$result = $_Comment->getAll($whereStr, '', 'create_time Desc', false, false);
		// 获取评论回复
		foreach($result as $key=> &$item)
		{
			$where['pid'] = $item['id'];
			$item['teacher'] = load_model('user')->getRow($item['teacher'], true, 'id,hulaid,nickname,firstname,lastname,avatar');
			$item['student'] = load_model('student')->getRow($item['student'], true, 'id,name,nickname,avatar');
            $item['creator'] != $item['teacher'] && $item['creator'] = load_model('user')->getRow($item['creator'], true, 'id,hulaid,account,nickname,firstname,lastname,avatar');
            $item['relation'] = 0;

			$has_flower_sent = load_model('comment')->getRow(array('pid' => $item['id'], 'creator' => $this->uid, 'character' => 'student', 'event,>' => 0, 'flower,>' => 0));
			$item['has_flower_sent'] = $has_flower_sent ? 1 : 0;
			
			if($item['character'] == 'student') 
            {
                $relation = load_model('user_student')->getRow(array('student' => $item['student'], 'user' => $item['creator']));
                $relation && $item['relation'] = $relation['relation'];
            }            
            
            $item['attach'] = $this->_get_attach($item['attach']);
            
			$reply && $item['reply'] = $_Comment->getAll(array('pid' => $item['id']), '', 'create_time Asc', false, true);
            if($item['reply'])
            {
                foreach($item['reply'] as $k => $val)
                {
                    $r = 0;
                    if($val['character'] == 'student')
                    {
                        $rs = load_model('user_student')->getRow(array('student' => $val['student'], 'user' => $val['creator']));
                        $rs && $r = $rs['relation'];
                    }
                    $val['relation'] = $r;
                    $item['reply'][$k] = $val;
                }
            } 
            $item = $_Comment->Format($item);
			// $result[$key] = $item;
		}
		Out(1, '', $result);
	}
	
    private function _get_attach($attach)
    {
        $result = array();
        if($attach) 
        {
            $attachs = load_model('attach')->getAttachs($attach);          
            foreach($attachs as $item)
            {
                $root = Config::get('path', 'upload');
                $file = substr($item['save_name'],0,-4);   
                $attach_url_size = getimagesize($root .'/' . $item['save_path'].$item['save_name']);
				$attach_small_size = getimagesize($root .'/' . $item['save_path']. $file . "_small.jpg");
				$attach_middle_size = getimagesize($root .'/' . $item['save_path']. $file . "_middle.jpg");            
                $result[] = array(
                    'attach_id' => $item['attach_id'],
                    'attach_url'=> $item['save_path'].$item['save_name'],
                    'attach_url_size'=>$attach_url_size[0].'_'.$attach_url_size[1],
                    'attach_small' => $item['save_path']. $file . "_small.jpg",
                    'attach_small_size'=>$attach_small_size[0].'_'.$attach_small_size[1],
                    'attach_middle'=> $item['save_path']. $file . "_middle.jpg",
                    'attach_middle_size'=>$attach_middle_size[0].'_'.$attach_middle_size[1],
                    'domain' => 'HOST_IMAGE'
                );
            }
        }
        return $result;
    }


    public function add()
	{
		$event = Http::post('event', 'string', 0);
		$student = Http::post('student', 'string', 0);
		$teacher = Http::post('teacher', 'int', 0);
		$content = Http::post('content', 'string', '');
		$school = Http::post('school', 'int', 0);
		$attach = Http::post('attach', 'string', '');
		$character = Http::post('character', 'string', 'teacher');
		$anonymous = Http::post('anonymous', 'int', 0);
		db()->begin();
		try
		{
			$eventResource = array();
			if($event)
			{
				if(strpos($event, '#'))
				{					
					list($pid, $length) = explode("#", $event);				
					$eventResource = load_model('event')->rec_create($pid, $length);					
					if(!$eventResource) throw new Exception('课程生成失败!');					
				}else{
					$eventResource = load_model('event')->getRow(array('id' => $event, 'status' => 0, 'is_loop' => 0));
				}
				if(!$eventResource) throw new Exception('此课程不存在或已被删除！');
				$event = $eventResource['id'];
				$school = $event['school'];

			}else{
				$event = 0;
			}			
			$creator = $this->uid;			
			$create_time = date('Y-m-d H:i:s');
			$agent = Http::getSource();
			$data = compact('creator', 'create_time', 'event', 'student', 'teacher', 'school', 'content', 'attach', 'character', 'anonymous', 'agent');
			if($character == 'teacher')
			{				
				$result = $this->_teacher_comment($data, $eventResource);
			}else if($character == 'student' || $character == 'parent')
			{
				$result = $this->_student_comment($data, $eventResource);                
			}
			if(empty($result)) throw new Exception('点评失败！');
			// $result = load_model('comment')->getRow($res, true);
			db()->commit();
			Out(1, '成功', $result);
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());
		}
	}
	
	// 老师发点评
	private function _teacher_comment($data, $event=array())
	{
        $result = array();
		if(empty($data['student']) && empty($data['school'])) throw new Exception('未指定学生！');		
		if($event)
		{
			$resource = load_model('teacher_course')->getRow(array('event' => $event['id'], 'teacher' => $this->uid, 'status' => 0), 'priv');			
			if(empty($resource) || $resource['priv'] & 4 == false) 
			{				
				throw new Exception('没有权限！@Er.event.no.promise');
			}			
			$data['school'] = isset($event['school']) ? $event['school'] : 0;		
			if(empty($event['commented']))
			{
				$res = load_model('event')->update(array('commented' => 1), $event['id']);				
				if(!$res) throw new Exception('点评失败！@Er.event.commente.fail');
			}
		}
		/* 点评数据
		$res = load_model('teacher')->increment('comments', array('user' => $this->uid));			
		if(!$res) throw new Exception('点评失败！@Er.event.teacher.comments.fail');
		*/
		$data['teacher'] = $this->uid;		
		// $id = load_model('comment')->insert($data);	
		// if(!$id) throw new Exception('点评失败！');
		if($data['student'])
		{
			$students = explode(",", $data['student']);        
			foreach($students as $item)
			{
				$data['student'] = $item;
				if($event)
				{				
					$res = load_model('student_course')->getRow(array('event' => $event['id'], 'student' => $item), false, 'id,commented');
					if(!$res)  throw new Exception("此课程下学生不存在！@Er.event.commente.fail[student:{$item}]");
					if($res['commented']) throw new Exception("此学生已点评！@Er.event.commente.fail[student:{$item}]");					
					$res = load_model('student_course')->update(array('commented' => 1), $res['id']);				
					if(!$res) throw new Exception("点评失败！@Er.event.commente.fail[student:{$item}]");
				}			
				$id = load_model('comment')->insert($data);
				if(!$id) throw new Exception("点评失败！@Er.event.commented.fail[student:{$item}]");
				$ext = $data; 
				$ext['_id'] = $id;
				$ext['student'] = load_model('student')->getRow($item, true, 'id,name,avatar');
				$ext['teacher'] = load_model('user')->getRow($this->uid, true, 'id,firstname,lastname,nickname,avatar');
				$res = load_model('student')->push($item, array(
					'app' => 'comment',	'act' => 'add', 'from' => $this->uid, 'type' => 2, 
					'character' => 'teacher', 'ext' => $ext
				));
				if(!$res) throw new Exception('点评失败！@Er.event.commente.push.fail');
				$result[] = array_merge($data, array('id' => $id));
			}
		}else{
			$id = load_model('comment')->insert($data);
			return array_merge($data, array('id' => $id));
		}
		return $result;
	}
	// 学生(家长)发点评
	private function _student_comment($data, $event=array())
	{		
        $result = array();
		if(empty($event) && empty($data['teacher']) && empty($data['school'])) throw new Exception('错误的操作！@Er.comment[event Or teacher]');
		if(empty($data['student'])) throw new Exception('未指定学生！');        
		if(!load_model('user_student')->getRow(array('user' => $this->uid, 'student' => $data['student']))) throw new Exception('学生不存在！');		
		if($event)
		{
			$resource = load_model('student_course')->getRow(array('event' => $event['id'], 'student' => $data['student'], 'status' => 0));
			if(empty($resource)) throw new Exception('没有权限！@Er.event.un_exists');
			// if($resource['commented']) throw new Exception('没有权限！@Er.comment[commented]');			
			// $res = load_model('student_course')->update(array('commented' => 1), array('event' => $event['id'], 'student' => $data['student']));
			// if(!$res) throw new Exception('点评失败！@Er.event.commented.fail');
			$data['teacher'] = $event['teacher'];
			$data['school'] = $event['school'];		
		}
		$id = load_model('comment')->insert($data);        
		if(!$id) throw new Exception("点评失败！@Er.event.commented.fail[student]");
		if(!empty($data['teacher']))
		{
			$res = push('db')->add('H_PUSH', $logs = array(
				'app' => 'comment',	'act' => 'add', 'from' => $this->uid,	'to'=> $data['teacher'], 'type' => 2, 
				'character' => 'student', 'ext' => array('comment' => $id), 'student' => $data['student']
			));        
			if(!$res) throw new Exception('点评失败！@Er.event.commente.push.fail');
		}
		return array_merge($data, array('id' => $id));
	}
	

	// 回复
	public function reply()
	{		
		$id = Http::post('id', 'int', 0);		
		$content = Http::post('content', 'string', '');		
		$attach = Http::post('attach', 'string', '');
		$student = Http::post('student', 'int', 0);
		$character = Http::post('character', 'string', 'teacher');
		$comment = load_model('comment')->getRow($id);       
		if(!$comment) throw new Exception('参数错误！@Er.comment[not exists]');
		// 花 2014/6/12
		$flower = Http::post('flower', 'int', 0);

		$data = array(
			'pid' => $id,
			'teacher' => $comment['teacher'],
			'student' => $comment['student'],
			'school' => $comment['school'],
			'attach' => $attach,
			'creator'=> $this->uid,
			'event' => $comment['event'],
			'content' => $content,            
			'create_time' => date('Y-m-d H:i:s'),
			'character' => $character,
			'agent' => Http::getSource()			
		);       
		if($character == 'student')
		{
			if(!$student) throw new Exception('未指定学生！@Er.comment[no student]');
			$data['student'] = $student;

			// 每次点评，回复只有一次送花机会 2014/6/12
			$res = load_model('comment')->getRow(array('pid' => $id, 'student' => $student, 'flower,>' => 0));
			if(!$res)
			{
				$data['flower'] = $flower;
			}

			if($student != $comment['student']) throw new Exception('错误的学生！@Er.comment[no student]');
		}
		
		db()->begin();
		try
		{
			$_id = load_model('comment')->insert($data);

			// 花计数 2014/6/12
			if(!empty($data['flower']))
			{				
				$res = load_model('comment')->increment('flower', array('id' => $id), $data['flower']); // 点评的花++				
				if(!$res) throw new Exception('提交失败！');
				$res = load_model('teacher_student')->increment('flower', array('teacher' => $comment['teacher'], 'student' => $comment['student']), $data['flower']); // 点评的花++				
				if(!$res) throw new Exception('提交失败！');
				$res = load_model('teacher')->increment('flower', array('user' => $comment['teacher']), $data['flower']); // 老师花++
				if(!$res) throw new Exception('提交失败！');
			}

			if(!$_id) throw new Exception('提交失败！@Er.comment[not exists]');
			$result = load_model('comment')->getRow($_id, true);
			if($character == 'student')
			{
				if(empty($comment['student'])) throw new Exception('操作错误！@Er.comment[student not exists]');
				$result['student'] = load_model('student')->getRow($comment['student']);	

				$res = push('db')->add('H_PUSH', array(
					'app' => 'comment',	'act' => 'reply', 'from' => $this->uid,	'to'=> $data['teacher'], 'type' => 2, 
					'character' => $character, 'ext' => $result
				));	
			}else if($character == 'teacher')
			{
				$result['student'] = load_model('student')->getRow($comment['student'], true, 'id,name,nickname,avatar');
				$result['teacher'] = load_model('user')->getRow($this->uid, true, 'id,hulaid,firstname,lastname,nickname,avatar');
				$res = load_model('student')->push($comment['student'], array(
					'app' => 'comment',	'act' => 'reply', 'from' => $this->uid, 'type' => 2, 
					'character' => $character, 'ext' => $result
				));	
			}
			db()->commit();
			Out(1, '成功', $result);
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());
		}		
	}

	// 获取回复
	public function getReply()
	{
		
	}

	// 获取回复情况
	public function getCommentStatus()
	{
		$id = Http::Post('id', 'int', 0);
		$res = load_model('comment')->getRow("pid='{$id}' And flower>0 And creator='{$this->uid}'");
		$flower = 0;
		if(!$res) $flower = 1;
		Out(1, 'sucess', compact('flower'));
	}

	
}