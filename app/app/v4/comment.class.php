<?php

class Comment_Api extends Api
{
	public function __construct(){
		parent::_init();
		$this->refresh = Http::post('refresh', 'trim', 0);
	}

	public function index()
	{
		$target = Http::post('target', 'trim', ''); // event/student
		$content = Http::post('content', 'trim', '');
		$students = Http::post('student', 'trim', '');
		$attach = Http::post('attach', 'trim', '');
		
		$sid = Http::post('sid', 'int', 0);
		$index = Http::post('index', 'int', 0);
	
		/*
		 * 点评学生 target = 'event' || target = 'student'
		 * 课堂点评
		*/
		db()->begin();
		try{
			if(!$content) throw new Exception('内容不能为空！');
			if(!$students) throw new Exception('点评失败');			
			$_Comment = load_model('comment', Null, true);
			
			$data = compact('sid', 'index', 'attach', 'content');
			$data['teacher'] = $this->uid;
			$data['creator'] = $this->uid;
			$data['create_time'] = DATETIME;
			$data['character'] = 'teacher';
			$data['target'] = $sid ? 'event' : 'student';
			$data['agent'] = Http::getSource();			
			is_array($students) || $students = explode(",", $students);

			$teacherObj = load_model('user', Null, true)->field('id,firstname,lastname,avatar')
				->where('id', $this->uid)
				->Row();
			if($sid)
			{				

				$_Schedule = load_model('schedule', Null, true);
				if(!$index) throw new Exception('点评失败');
				$schedule = $_Schedule->entity_row($sid, $index); //where('sid', $sid)->where('index', $index)->Row();				
				if(!$schedule) throw new Exception('点评失败');

				// 权限
				$date = date('Y-m-d', $index);
				$priv = load_model('assign', Null, true)
					->where('sid', $sid)
					->where('type', 1)
					->where('assigner', $this->uid)
					->where('start_date,<=', $date)
					->or_where(array('end_date,>=' => $date, 'status' => 0))
					->Row();
				
				if(!$priv || !($priv['priv'] & 1)) throw new Exception('没有权限');

				$existStudent = load_model('assign', Null, true)
					->where('sid', $sid, true)
					->where('start_date,<=', $date)
					->where('end_date,>=', $date)
					->where('type', 0)
					->where('assigner,in', $students)
					->Result();
				if(!$existStudent) throw new Exception('学生匹配错误！');
				$_Schedule->update(array('commented' => 1)); // 更新状态		
			}
			
			foreach($students as $student)
			{
				$res = load_model('teacher_student', Null, true)
					->where('teacher', $this->uid, true)
					->where('student', $student)
					->Row();
				if(!$res) throw new Exception('没有此学生！');
				$data['student'] = $student;		
				// 课程点评只能点一次
				if($sid)
				{
					$has = $_Comment->where('sid', $sid, true)->where('index', $index)->where('student', $student)->Row();
					$res = $_Comment->update($data);
					// if($res) throw new Exception('点评失败！');
				}
				//				
				$id = $_Comment->insert($data);
				if(!$id) throw new Exception('点评失败');
				$ext = $data;
				$ext['student'] = load_model('student', Null, true)->field('id,name,avatar')
					->where('id', $student)
					->Row();
				$ext['teacher'] = $teacherObj;
				$parents = load_model('user_student', Null, true)->field('user')
					->where('student', $student)
					->Column();
				foreach($parents as $parent)
				{
					// 推送
					$res = push('db')->add('H_PUSH', $logs = array(
						'app' => 'comment',	
						'act' => 'add',
						'from' => $this->uid,
						'to'=> $parent, 
						'type' => 2, 
						'character' => 'student',
						'student' => $student,
						'ext' => $ext,
					));
					if(!$res) throw new Exception('点评失败E');
				}
			}
			db()->commit();
			Out(1, 'success');
		}catch(HlpException $e){
			db()->rollback();
			Out(0, $e->getMessage());
		}		
	}

	/* 点评记录
	 * @teacher
	 * @student
	 * 详情 => 点评记录
	*/
	public function history()
	{			
		$student = Http::post('student', 'int', 0);
		$teacher = Http::post('teacher', 'int', 0);
		$_page = Http::post('page', 'int', 0);
		$perpage = 20;		
		$teacher || $teacher = $this->uid;
		if(!$student) throw new Exception('点评失败');
		$res = load_model('teacher_student', Null, true)
			->where('student', $student)
			->where('teacher', $teacher)
			->Row();
		if(!$res) throw new Exception('权限错误！');		
		$_Comment = load_model('comment', Null, true)
			->where('student', $student)
			->where('teacher', $teacher)
			->where('pid', 0);
		$page = $_Comment->limit($perpage, $_page)->Page();
		$data = $_Comment->field('id,teacher,student,creator,sid,index,content,create_time,character')->Order('create_time', 'Desc')
			->limit($perpage, $_page)
			->Result();
		foreach($data as $key=> &$item)
		{
			$item = $_Comment->formatForApp($item);
			$item['reply'] = $_Comment->clear()->where('pid', $item['id'])->Order('create_time', 'Desc')->Result();
			unset($item['sid'], $item['index']);
		}
		Out(1, 'success', compact('page', 'data'));
	}
	
	// 回复
	public function reply()
	{
		$id = Http::post('id', 'int', 0);
		$flower = Http::post('flower', 'int', 0);
		$attach = Http::post('attach', 'trim', '');
		$content = Http::post('content', 'trim', '');
		$character = Http::post('character', 'trim', 'teacher');
		db()->begin();
		try{
			if(!$id) throw new Exception('回复失败');
			if(!$content && !$flower) throw new Exception('回复失败');	
			$_Comment = load_model('comment', Null, true);
			$comment = $_Comment->where('id', $id)
				->where('pid', 0)
				->Row();
			if(!$comment) throw new Exception('点评不存在！');			
			// if($this->uid != $comment['teacher']) // 老师回复
			if($character != 'teacher') // 老师回复
			{				
				$res = load_model('user_student', Null, true)
					->where('student', $comment['student'])
					->where('user', $this->uid)
					->Row();				
				if(!$res) throw new Exception('权限错误！');

				// 是否送过花
				if($flower > 0)
				{
					$res = $_Comment->where('pid', $id, true)
						->where('character', 'student')
						// ->where('creator', $this->uid)
						->where('student', $comment['student'])
						->where('flower,>', 0)
						->Row();					
					if($res) throw new Exception('已送过花');
				}				
			}
			
			$data = compact('content', 'flower', 'character');
			$data['pid'] = $id;
			$data['creator'] = $this->uid;
			$data['create_time'] = DATETIME;
			$data = array_merge($comment, $data);

			unset($data['id']);
			$data['id'] = $_Comment->insert($data);
			if(!$data['id']) throw new Exception('回复失败！');

			if($character == 'student' && $flower>0)
			{
				$res = load_model('teacher', Null, true)->where('user', $data['teacher'])
					->increment('flower', $flower);
				if(!$res) throw new Exception('送花失败！');
				$res = $_Comment->where('id', $id, true)->increment('flower', $flower);
				if(!$res) throw new Exception('送花失败！');
				$res = load_model('teacher_student', Null, true)->where('teacher', $comment['teacher'])
					->where('student', $comment['student'])
					->increment('flower', $flower);			
				if(!$res) throw new Exception('提交失败！');
			}
			
			$data['student'] = load_model('student', Null, true)->field('id,name,avatar')
				->where('id', $comment['student'])
				->Row();
			$data['teacher'] = load_model('user', Null, true)->field('id,firstname,lastname,avatar')
				->where('id', $comment['teacher'])
				->Row();
			$push = array(
				'app' => 'comment',	
				'act' => 'reply',
				'from' => $this->uid,
				'to' => '',
				'student' => $comment['student'],
				'type' => 2,
				'character' => $character, 
				'ext' => $data
			);
			// push
			if($character == 'teacher')
			{
				$parents = load_model('user_student', Null, true)->field('user')
					->where('student', $comment['student'])
					->Column();		
				
				foreach($parents as $parent)
				{					
					$res = push('db')->add('H_PUSH', array_merge($push, array('to' => $parent)));
					if(!$res) throw new Exception('点评失败');
				}
			}else{
				$res = push('db')->add('H_PUSH', array_merge($push, array('to' => $data['teacher'])));
				if(!$res) throw new Exception('点评失败');
			}
		
			db()->commit();
			Out(1, 'success');
		}catch(HlpException $e){
			db()->rollback();
			Out(0, $e->getMessage());
		}
	}
	
	// 点评详情
	public function info()
	{
		$id = Http::post('id', 'int', 0);
		$_Comment = load_model('comment', Null, true)->field('id,pid,teacher,student,creator,character,sid,index,content,attach,flower,create_time');
		$result = $_Comment->where('id', $id)
			->where('pid', 0)
			->Row();
		if(!$result) throw new Exception('错误的请求');
		$result = $_Comment->formatForApp($result);		
		$reply = $_Comment->where('pid', $id, true)->Order('create_time', 'Desc', true)->limit()->Result();
		// 是否送过花
		$has_flower_sent = 0;		
		foreach($reply as &$item)
		{
			$item = $_Comment->formatForApp($item);
			if($has_flower_sent == 0 && $item['flower'] > 0) $has_flower_sent = 1;
		}
		Out(1, 'success', array_merge($result, compact('event', 'reply', 'has_flower_sent')));
	}
	
	// 回复记录(老师)
	public function newreply()
	{
		$_page = Http::post('page', 'int', 1);
		$_Comment = load_model('comment', Null, true)->field('id')
			->where('teacher', $this->uid)
			->where('pid,>', 0)
			->where('character', 'student');
		$perpage = 20;
		$page = $_Comment->limit($perpage, $_page)->Page();
		$data = $_Comment->field('id,pid,teacher,student,creator,character,sid,index,content,attach,flower,create_time')
			->Order('create_time', 'Desc')
			->limit($perpage, $_page)
			->Result();
		foreach($data as &$item)
		{
			$item = $_Comment->formatForApp($item);			
		}
		Out(1, 'success', compact('data', 'page'));
	}

}