<?php
class Rank_Api extends Api
{
	public function __construct(){
		$cache = Http::post('tm', 'int', 0);
		$this->cache = $cache ? false : true;
	}

	private $_exprie = 1800;

	private $_order = array(
		array(
			'name' => 'qf',
			'title' => '人气教师榜',
			'description' => '勤奋榜是系统后台根据老师对学生课后点评的数量以及家长回复比统计而得的，入围老师排名不分先后。我们没有资格评价老师的专业水准，谨向老师付出的辛勤劳动致以我们的敬意！'
		),
		/*
		'xj' => '新进老师榜',
		'ww' => '默默无闻榜',
		'zm' => '名师推荐榜'
		*/
	);

	public function getList()
	{
		Out(1, 'success', $this->_order);
	}


	public function teacherList()
	{
		$type = Http::post('type', 'trim', 'sch');
		$school = Http::post('school', 'int', 0); // 机构推荐		
		if(!$type && !$school) throw new Exception('参数错误！');
		$where['type'] = $type ? $type : 'sch';
		if($school)
		{
			$where['school'] = $school;			
		}
		$limit = '';
		if($where['type'] == 'qf')
		{
			$where['term'] = 2;
			$limit = 10;
		}
		$cache_key =  'teacher_rank_list' . ($school ? "_" . $school : ''). ($type ? "_" . $type : '');
		//print_r($where);
		$result = cache()->get($cache_key);
		if($result === false || $this->cache === false)
		{			
			$result = load_model('teacher_rank')->getAll($where, $limit, '`sort` Asc', false, false, 'id rid,teacher,school,course,description');			
			array_walk($result, function(&$v, $key){
				if(!empty($v['school']))
				{
					$v['school'] = load_model('school')->getRow(array('id' => $v['school']), false, 'id, name');					
				}
				/*
				if(!empty($v['course']))
				{
					// $course = json_encode($v['course'], true);	
					// $v && $course = load_model('course')->getAll(array('id' => $v['course']), '', '', false, false, 'id,`title`,experience,`type`');
					// $v['course'] = $course;
				}
				*/
				// 取老师自己的course
				$v['course'] = load_model('course')->getAll(array('teacher' => $v['teacher']), '', '', false, false, 'id,`title`,experience,`type`');

				if(isset($v['teacher']) && ($teacher = $v['teacher']))
				{				
					$user = load_model('user')->getRow($teacher, false, 'id,concat(firstname, lastname) name,avatar');
					$teacher = load_model('teacher')->getRow(array('user' => $teacher), false, 'background,mind');					
					$v = array_merge($v, $user, $teacher);
					unset($v['teacher']);
				}
				if(!empty($v['school']))
				{					
					$v['school'] = load_model('school')->getRow($v['school'], false, 'id,name');
				}else{
					$v['school'] = array();
				}
			});
			$this->cache && cache()->set($cache_key, $result, $this->_expire);
		}
		if(!$result) throw new Exception('没有数据！');
		Out(1, 'success', $result);
	}
	
	// 老师详情
	public function teacher()
	{
		$id = Http::post('id', 'int', 0);
		$event = Http::post('event', 'int', 0);
		if(!$id) throw new Exception('参数错误！');
		$cache_key = 'rank_teacher_info_' . $id;
		$result = cache()->get($cache_key);
		if($result === false || $this->cache === false)
		{
			if($event)
			{
				$result = load_model('recruit')->getRow($event, false, 'id event, school, course, description');
				$teacher = $id;
			}else
			{
				$result = load_model('teacher_rank')->getRow($id, false, 'id rid, teacher id,school,course,description');
				$teacher = $result['id'];
			}			
			if(!$result) throw new Exception('老师不存在！');
			/*
			if(!empty($result['course']))
			{
				$course = explode(',', $result['course']);
				$course && $course = load_model('course')->getAll(array('id,in' => $course, 'school' => $result['school']), '', '', false, false, 'id,`title`,experience,`type`');
				$result['course'] = $course;
			}
			*/
			// 取老师自己的course
			$result['course'] = load_model('course')->getAll(array('teacher' => $teacher), '', '', false, false, 'id,`title`,experience,`type`');

			if($teacher)
			{	
				$user = load_model('user')->getRow($teacher, false, 'id,concat(firstname, lastname) name,avatar,hulaid');				
				$teacherObj = load_model('teacher')->getRow(array('user' => $teacher), false, 'province,city,area,background,mind,target,flower');				
				$result = array_merge($result, $user, $teacherObj);
			}
			//评价
			$comment = load_model('comment')->getRow(array('teacher' => $teacher, 'character' => 'student', 'pid' => 0, 'event' => 0, 'school' => 0), false, 'id,creator,student,school,create_time,content', 'create_time Desc');
			if($comment)
			{
				$comment['creator'] = load_model('user')->getRow($comment['creator'], false, 'id,account,firstname,lastname,avatar');
				// 课时数
				$comment['is_union']= $this->is_unoin($id, $comment['creator']['id'], 'teacher');
			}
			$comment_count = load_model('comment')->getCount(array('teacher' => $teacher, 'character' => 'student', 'pid' => 0, 'event' => 0, 'school' => 0), 'count(*)');
			
			$result['comment_count'] = $comment_count;
			if($result['school'])
			{
				$result['school'] = load_model('school')->getRow($result['school'], false, 'id,`name`,code,avatar');
			}
			$result['comment'] = $comment;
			$this->cache && cache()->set($cache_key, $result, $this->_expire);
		}
		if(!$result) throw new Exception('老师不存在！');
		Out(1, 'success', $result);
	}
	
	public function comment()
	{
		$id = Http::post('id', 'int', 0);
		if(!$id) throw new Exception('参数错误！');
		$order = Http::post('sort', 'trim', '');
		$character = Http::post('character', 'trim', 'school');
		$page = (int) Http::post('page', 'int', 0);
		$perpage = Http::post('per', 'int', 20);		
		$page = $page > 1  ?$page : 1;
		$cache_key = $character . '_comment_' . $id . ($order ? "_" . $order : "") . ($page > 1 ? '_page' . $page : '');
		$order = $order ? 'upon Desc,' : '';
		$order.= 'create_time Desc';
		$result = cache()->get($cache_key);
		if($result === false || $this->cache === false)
		{
			$limit = (($page - 1) * $perpage) . "," . $perpage;
			$where = array(
				$character => $id,
				'character' => 'student', 
				'event' => 0, 
				'pid' => 0
			);
			if($character == 'teacher')
			{
				$where['school'] = 0;
			}else{
				$where['teacher'] = 0;
			}
			$total = load_model('comment')->getCount($where, 'count(id)');
			$comments = load_model('comment')->getAll($where, $limit, $order, false, false, 'id,create_time,content,creator,student,upon');
			array_walk($comments, function(&$v, $key) use($id){
				$upon = $this->_is_upon($v['id']);				
				$v['creator'] = load_model('user')->getRow($v['creator'], false, 'id,account');	
				$v['is_upon'] = $upon ? 1 : 0;
				$v['is_union'] = $this->is_unoin($id, $v['creator']['id'], $character);	
			});
			$page =  array('page'=>$page, 'total'=> $total, 'size'=>$perpage, 'pages'=> ceil($total / $perpage));
			$result = array('comments' => $comments, 'page' => $page);
			$this->cache && cache()->set($cache_key, $result, $this->_expire);
		}
		if(!$result) throw new Exception('无数据...');
		Out(1, 'success', $result);
	}

	public function is_unoin($id, $user=0, $character='school')
	{
		$students = load_model('user_student')->getColumn(array('user' => $user), 'student');		
		if(empty($students)) return 0;
		if($character == 'teacher')
		{
			$res = load_model('teacher_student')->getRow(array('student,in' => $students, 'teacher' => $id));
		}else{
			$res = load_model('school_student')->getRow(array('student,in' => $students, 'school' => $id));
		}
		if($res) return 1;
		return 0;
	}

	private function _is_upon($comment)
	{
		$user = Http::get_session(SESS_UID);
		$result = false;
		if($user)
		{
			$result = load_model('comment_upon')->getRow(array('user' => $user, 'comment' => $comment));
		}else{
			$agent = Http::agent();
			if(isset($agent['sn']) && $sn = $agent['sn'])
			{
				$result = load_model('guest')->getRow(array('event' => 'upon', 'ext' => $comment, 'sn' => $sn));
			}
		}
		if($result) return true;
		return false;
	}

	public function upon()
	{
		$user = Http::get_session(SESS_UID);
		$user || $user = 0;
		$comment = Http::post('comment', 'int', 0);
		if(!$comment) throw new Exception('参数错误！');
		$upon = Http::post('upon', 'int', 0);
		db()->begin();
		try
		{
			$_Comment = load_model('comment');
			$commentObj = $_Comment->getRow($comment);
			if(!$commentObj) throw new Exception('数据错误，点评不存在或已被删除！');
			$userupon = $user == 0 ? 0 : load_model('comment_upon')->getRow(array('user' =>$user, 'comment' => $comment));			
			if($upon) {
				if($userupon) throw new Exception('操作失败，请不要重复操作！');
				$res = $_Comment->increment('upon', $comment);
				if(!$res) throw new Exception('操作失败！');
				if($user)
				{
					$res = load_model('comment_upon')->insert(array(
						'comment' => $comment,
						'user' => $user,
						'create_time' => time()
					));
					if(!$res) throw new Exception('操作失败！');
				}else{
					$agent = Http::agent();
					if(empty($agent['sn'])) throw new Exception('操作限制，请联系系统管理员！');
					$res = load_model('guest')->getRow(array(
						'ext' => $comment,
						'event' => 'upon',						
						'sn' => $agent['sn']						
					));
					if($res)  throw new Exception('操作失败，请不要重复操作！');
					$res = load_model('guest')->insert($m=array(
						'ext' => $comment,
						'event' => 'upon',
						'create_time' => time(),
						'sn' => $agent['sn'],
						'brand' => $agent['src']
					));
					if(!$res) throw new Exception('操作失败！');
				}
			}else{
				$res = $_Comment->decrement('upon', $comment);
				if(!$res) throw new Exception('操作失败！');
				if($user)
				{
					$res = load_model('comment_upon')->delete(array('comment' => $comment, 'user' => $user), true);
					if(!$res) throw new Exception('操作失败！');
				}else{
					$agent = Http::agent();
					if(empty($agent['sn'])) throw new Exception('操作限制，请联系系统管理员！');					
					$res = load_model('guest')->delete(array(
						'ext' => $comment,
						'event' => 'upon',					
						'sn' => $agent['sn']
					), true);
					if(!$res) throw new Exception('操作失败！');
				}
			}
			db()->commit();
			Out(1, 'success');
		}catch(Exception $e)
		{
			db()->rollback();
			out(0, $e->getMessage());
		}
	}

	

	public function appraise()
	{
		$content = Http::post('content', 'trim', '');		
		$character = Http::post('character', 'trim', 'school');
		$student = Http::post('student', 'int', 0);
		$id = Http::post('id', 'int', 0);
		db()->begin();
		try{
			$agent = Http::getSource();
			if(!$this->get_comment_priv($agent)) throw new Exception('您不能点评！');
			if(!$id || !$content) throw new Exception('操作失败');			
			$data = array(
				$character => $id,
				'content' => $content,
				'create_time' => date('Y-m-d H:i:s'),
				'character' => 'student',
				'creator' => $this->uid,
				'student' => $student,
				'agent' => $agent
			);
			$res = load_model('comment')->insert($data);
			if(!$res) throw new Exception('操作失败');
			if($character == 'school')
			{
				$res = load_model('school')->increment('comments', $id) ;
			}
			db()->commit();
			Out(1, 'success');
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());
		}
	}

	public function get_comment_priv($agent=0)
	{
		parent::_init();
		return true; // 开放 2014/6/5
		$character = Http::post('character', 'trim', 'school');
		if(isset($agent) && $agent == 2) return true;
		$id = Http::post('id', 'int', 0);	
		$students = load_model('user_student')->getColumn(array('user' => $this->uid), 'student');	
		foreach($students as &$student)
		{
			// $student = load_model('student')->getRow($student, false, 'id,name,avatar');
			if($character == 'teacher')
			{
				$relation = load_model('teacher_student')->getRow(array('teacher' => $id, 'student' => $student));				
			}else{
				$relation = load_model('school_student')->getRow(array('school' => $id, 'student' => $student));				
			}
			if($relation) return true;
		}
		return false;
	}


	// 获取学生列表
	public function get_students()
	{		
		parent::_init();
		$character = Http::post('character', 'trim', 'school');
		$id = Http::post('id', 'int', 0);		
		$students = load_model('user_student')->getColumn(array('user' => $this->uid), 'student');	
		foreach($students as &$student)
		{
			$student = load_model('student')->getRow($student, false, 'id,name,avatar,creator');
			$student['is_creator'] = $student['creator'] == $this->uid ? 1 : 0;
			unset($student['creator']);
			if($character == 'teacher')
			{
				$relation = load_model('teacher_student')->getRow(array('teacher' => $id, 'student' => $student['id']));
				$student['is_union'] = $relation ? 1 : 0;
				$apply = load_model('apply')->getRow(array('to' => $id, 'student' => $student['id'], 'type' => 1, 'status' => 0));
				$student['is_apply'] = $apply ? 1 : 0;				
			}else{
				$relation = load_model('school_student')->getRow(array('school' => $id, 'student' => $student['id']));
				$student['is_union'] = $relation ? 1 : 0;
				$apply = load_model('apply')->getRow(array('to' => $id, 'student' => $student['id'], 'type' => 6, 'status' => 0));
				$student['is_apply'] = $apply ? 1 : 0;
			}
		}
		Out(1, 'success', $students);
	}
}