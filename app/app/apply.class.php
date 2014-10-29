<?php

class Apply_Api extends Api
{

	public function __construct(){
		parent::_init();		
	}	

	/*
	 * 1学生+老师		2老师+学生,	3机构+老师,	4老师+机构,	
	 * 5好友申请,		6学生+机构,	7机构+学生,   8学生授权
	*/
	public function add()
	{
		$type = Http::post('type', 'int', 5);		
		if(!$type) throw new Exception('参数错误！');
		$verify_code = Http::post('code', 'string', '');
		$message = Http::post('message', 'string', '');
		$from = $this->uid;
		$to = Http::post('to', 'string', '');
		$push = true;
		$result = array();
		$student = Http::post('student', 'int', 0);
		
		if($type == 4 || $type == 6) // + 机构
		{
			$school = load_model('school')->getRow(array('code' => $to), false, 'id,code,name,pid,avatar,province,city,area,address,contact,phone,phone2,description,verify_code');
			if(!$school) throw new Exception('机构不存在！');
			$to = $school['id'];
			if($verify_code){
	            if($verify_code != $school['verify_code']) throw new Exception('验证码错误！');    
	            if($type == 4)
	            {
	                if(load_model('school_teacher')->getRow(array('school' => $school['id'], 'teacher' => $this->uid))) throw new Exception('已在此机构！');                        
	                $res = load_model('school_teacher')->insert(array(
	                    'school' => $school['id'],
	                    'teacher'=> $this->uid,
	                    'create_time' => time()
	                ));
	                if(!$res) throw new Exception('添加失败！');
	            }else{
	                if(load_model('school_student')->getRow(array('school' => $school['id'], 'student' => $student))) throw new Exception('已在此机构！');                        
	                $res = load_model('school_student')->insert(array(
	                    'school' => $school['id'],
	                    'student'=> $student,
	                    'create_time' => time()
	                ));
	                if(!$res) throw new Exception('添加失败！');
	            }
	            unset($school['verify_code']);
            	Out(1, '添加成功！', load_model('school')->Format($school));
			}
		}else
		{			
			$res = load_model('user')->getRow("hulaid='". $to . "' Or `account`='" . $to . "'", false, 'id');
			if(!$res) throw new Exception('用户不存在！');
			$to = $res['id'];
		}	
		
		switch($type)
		{
			case 1: // 学生+老师
				$character = 'student';				
				$res = load_model('teacher')->getRow(array('user' => $to), false,'id');
				if(!$res) throw new Exception('教师不存在！');
				// print_r(array('id' => $student, 'creator' => $this->uid));
				$studentItem = load_model('student')->getRow(array('id' => $student, 'creator' => $this->uid), true, 'id');
				if(!$studentItem) throw new Exception('没有此学生');
				$rs = load_model('teacher_student')->getRow(array('teacher' => $to, 'student' => $student, 'type' => 0), 'id');
				if($rs) throw new Exception('已经有此老师！');				
				break;
			case 2: // 老师+学生
				$character = 'teacher';	
				$students = load_model('user_student')->getAll(array('creator' => $to));				
				foreach($students as $key => $item)
				{
					if(load_model('teacher_student')->getRow(array('teacher' => $from, 'student' => $item, 'type' => 0)))
					{
						unset($students[$key]);
					}
				}
				if(empty($students)) throw new Exception('该用户没有可添加的学生！');							
				break;
			case 3: // 机构+老师
				$character = 'school';				
				$res = load_model('teacher')->getRow(array('user' => $to), false,'id');
				if(!$res) throw new Exception('教师不存在！');				
				$rs = load_model('school_teacher')->getRow(array('teacher' => $to, 'school' => $from));
				if($rs) throw new Exception('已经有此老师！');			
				break;
			case 4:	// 老师+机构
				$character = 'teacher';
				$rs = load_model('school_teacher')->getRow(array('teacher' => $from, 'school' => $to));
				if($rs) throw new Exception('已在此机构！');
				break;
			case 5: // +好友 // 呼啦号
				$character = 'friend';
				if(load_model('user_student')->getRow(array('student' => $from, 'user' => $this->uid))) throw new Exception('没有此学生');
				$rs = load_model('friend')->getRow(array('user' => $from, 'friend' => $to));
				if($rs) throw new Exception('已有此好友！');
				break;
			case 6:	// 学生+机构 呼啦号
				$character = 'student';
				$studentItem = load_model('student')->getRow(array('id' => $student, 'creator' => $this->uid), true, 'id, name,nickname, creator, avatar');
				if(!$studentItem) throw new Exception('没有此学生');				
				$rs = load_model('school_student')->getRow(array('student' => $student, 'school' => $to));
				if($rs) throw new Exception('已经在此机构！');
				break;			
			case 7: // 机构+学生
				$character = 'school';
				$students = load_model('user_student')->getAll(array('creator' => $to));				
				foreach($students as $key => $item)
				{
					if(load_model('school_student')->getRow(array('school' => $from, 'student' => $item)))
					{
						unset($students[$key]);
					}
				}
				if(empty($students)) throw new Exception('该用户没有可添加的学生！');						
				break;
			case 8: // 学生授权
				$character = 'student';
				$studentItem = load_model('student')->getRow(array('id' => $student, 'creator' => $this->uid), true, 'id, name,nickname, creator, avatar');
				if(!$studentItem) throw new Exception('没有此学生');				
				if(load_model('user_student')->get_relation($to, $student) > 0) throw new Exception('已授权！');			
				break;
		}
		if(!$from || !$to) throw new Exception('参数错误！');
		$_Apply = load_model('apply');
		db()->begin();
		try
		{			
			$apply = $_Apply->getRow(compact('type', 'from', 'to', 'student'), true, 'id,`type`,`from`,`to`,student,create_time,message');			
			if($apply && $apply['status'] < 2) throw new Exception('已申请');			
			if($apply)
			{
				$res = $_Apply->update(array('status' => 0), $res['id']);	
				if(!$res)  throw new Exception('申请失败！');
			}else{
				$create_time = date('Y-m-d H:i:s');
				$creator = $this->uid;
				$data = compact('type', 'from', 'to', 'student', 'creator', 'create_time', 'message');				
				$id = $_Apply->insert($data);	
				if(!$id) throw new Exception('申请失败！');				
				$apply = load_model('apply')->getRow($id, true, 'id,`type`,`from`,`to`,student,create_time,message');				
			}		
			if($type != 4 && $type != 6) // 不需要推送
			{
				$push = array(
					'app' => 'apply',	'act' => 'add',	'from' => $this->uid,	'to' => $to, 'student' => $student,
					'character' => $character, 'type' => 2, 'ext' => $apply
				);
				$res = push('db')->add('H_PUSH', $push);
				if(!$res)  throw new Exception('申请失败！');
			}
			db()->commit();
			Out(1, '成功！', $apply);
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());
		}		
	}

	// user '用户', student '学生', teacher '老师', school '机构',  friend '好友'
	public function deal()
	{
		$id = Http::post('id', 'int', 0);
		$status = Http::post('status', 'int', 1); // 2忽略
		$relation = Http::post('relation', 'int', 4);
		if(!$id) throw new Exception('参数错误！@Er.apply.id');
		$apply = load_model('apply')->getRow(array('id' => $id, 'to' => $this->uid, 'status' => 0), false);
		
		if(empty($apply)) throw new Exception('无此记录！@Er.apply.data.no');
		if($apply['type'] == 8 && (!$relation || !(in_array($relation,array(1,2,3,4)))))  throw new Exception('参数错误！@Er.apply.relation');
		db()->begin();	
		$result = array();
		$tm = time();
		try{
			// 更新状态 是否保留
            /*
			$res = load_model('apply')->update(array(
				'status' => 1, 
				'verify_time' => $tm,
				'operator' => $this->uid
			), $id); // 删除             
             */
            $res = load_model('apply')->delete($id, true); // 强删除            
			if(!$res) throw new Exception('操作失败！@Er.apply[deal error]');

			if($status == 2)
			{
				switch($apply['type'])
				{
					case 1: // 学生+老师
                    case 3: // 机构+老师
						$character = 'teacher';                        
						break;
					case 2: // 老师+学生
                    case 7: // 机构+学生
						$character = 'student';
						break;					
					case 4: // 老师+机构
                    case 6: // 学生+机构 验证码
						$character = 'school';
						break;
					case 5: // 好友申请
						$character = 'friend';
						break;								
					case 8: // 学生授权
						$character = 'user';
						break;
				}
				$push = array(
					'app' => 'apply',	'act' => 'refuse',	'from' => $this->uid,	'type' => 0, 'to' => $apply['from'],
					'character' => $character, 'ext' => $id
				);
				$res = push('db')->add('H_PUSH', $push);
				if(!$res) throw new Exception('操作失败！@Er.apply[refuse push]');
			}else
			{
				switch($apply['type'])
				{
					case 1: // 学生+老师
						
						if(load_model('teacher_student')->getRow(array('teacher' => $apply['to'], 'student' => $apply['student'], 'type' => 0)))
						throw new Exception('已有此学生！');		

						$res = load_model('teacher_student')->add($apply['to'], $apply['student']);
						if(!$res) throw new Exception('添加学生失败！');
						
						// 所有家长
						$parents = load_model('user_student')->get_parents($apply['student'], false);   
						/*
						foreach($parents as $parent)
						{
							// 关注
							$res = $this->follow($apply['to'], $parent['user']);						
							if(!$res) throw new Exception('添加学生失败！@Er.follow.fail');
						}
						*/

						$push = array(
							'app' => 'teacher',	'act' => 'add',	'from' => $this->uid,	'type' => 2,	'student' => $apply['student'],
							'character' => 'teacher', 'ext' => load_model('user')->getRow($apply['to'], true, 'id,nickname,firstname,lastname,hulaid,avatar')
						);						
						$res = load_model('student')->push($apply['student'], $push);
						if(!$res) throw new Exception('操作失败！@Er.apply[push.error]');	
						break;
					case 2: // 老师+学生
						$student = Http::post('student', 'string', 0);						
						if(!$student) throw new Exception('学生不存在！@Er.param.student');						
						$students = explode(",", $student);					
						foreach($students as $key => $item)
						{
							$res = load_model('user_student')->getRow(array('creator' => $this->uid, 'student' => $item));
							if(!$res) throw new Exception("没有此学生@Er.param[student:{$item}]");
							$res = load_model('teacher_student')->getRow(array('teacher' => $apply['from'], 'student' => $item, 'type' => 0));						
							if($res) // 关系已存在
							{
								unset($students[$key]);
								continue;
							}
							// throw new Exception("已经是该老师的学生！@Er.param[teacher:{$apply['from']}]");							
							$res = load_model('teacher_student')->add($apply['from'], $item);	
							if(!$res) throw new Exception('操作失败！@Er.teacher.add');
							$push = array(
								'app' => 'student',	'act' => 'add',	'from' => $this->uid,	'to' => $apply['from'], 'type' => 1,	'student' => $item,
								'character' => 'student', 'ext' => load_model('student')->getRow($item, true, 'id,name,nickname,avatar')
							);						
							$res = push('db')->add('H_PUSH', $push);						
							if(!$res) throw new Exception('操作失败！@Er.apply[push.error]');	
							
							// 所以家长关注此老师
							/*
							$parents = load_model('user_student')->get_parents($item, false);
							foreach($parents as $parent)
							{
								// 关注
								$res = $this->follow($apply['from'], $parent['user']);
								if(!$res) throw new Exception('操作失败！@Er.student[follow fail]');
							}
							*/
						}												
						break;						
					case 3: // 机构+老师
						
						// $this->follow($apply['from'], $apply['to'],$apply['student']); // 老师关注机构

						$res = load_model('school_teacher')->add($apply['from'], $apply['to']);
						if(!$res) throw new Exception('操作失败！');
						// 机构不推送
						break;
					case 4: // 老师+机构
						// $this->follow($apply['from'], $apply['to'],$apply['student']); // 老师关注机构
						$res = load_model('school_teacher')->add($apply['to'], $apply['from']);
						if(!$res) throw new Exception('操作失败！');
						// 推送消息给老师
						$push = array(
							'app' => 'school',	'act' => 'add',	'from' => $apply['school'],	'type' => 2, 'to' => $apply['from'],
							'character' => 'school', 'ext' => load_model('school')->getRow($apply['school'], true, 'id,name,pid,`type`')
						);
						$res = push('db')->add('H_PUSH', $push);
						if(!$res) throw new Exception('操作失败！@Er.apply[push.error]');	
						break;
					case 5: // 好友申请
						$res = load_model('friend')->add($apply['to'], $apply['from']);
						if(!$res) throw new Exception('操作失败！');
						$push = array(
							'app' => 'friend',	'act' => 'add',	'from' => $this->uid,	'type' => 2, 'to' => $apply['from'],
							'character' => 'school', 'ext' => load_model('user')->getRow($apply['to'], true, 'id,nickname,firstname,lastname,hulaid,avatar')
						);
						$res = push('db')->add('H_PUSH', $push);
						if(!$res) throw new Exception('操作失败！@Er.apply[push.error]');	
						break;
					case 6: // 学生+机构 验证码						
						// $this->follow($apply['from'], $apply['to'],$apply['student']); // 学生家长关注机构
						$result = load_model('school_student')->add($apply['to'], $apply['student']);
						if(!$res) throw new Exception('操作失败！');
						$push = array(
							'app' => 'school',	'act' => 'add',	'from' => $this->uid,	'type' => 2,	'student' => $apply['student'],
							'character' => 'teacher', 'ext' => load_model('school')->getRow($apply['to'], true, 'id,name,pid,`type`')
						);
						$res = load_model('student')->push($apply['student'], $push);
						if(!$res) throw new Exception('操作失败！@Er.apply[push.error]');	
						break;
					case 7: // 机构+学生
						// $this->follow($apply['from'], $apply['to'],$apply['student']); // 学生家长关注机构
						$students = Http::post('student', 'string', '');
						$students = explode(',',$students);
						if(!$students || empty($students)) throw new Exception('学生不存在！@Er.param.student');
						foreach($students as $student){
							//是否已经是机构学生
							if(load_model('school_student')->getRow(array('school'=>$apply['from'], 'student'=>$student))) continue;
							$result = load_model('school_student')->add($apply['from'], $student);
							if(!$result) continue;
						}
						break;
					case 8: // 学生授权
						if($relation != 4 && load_model('user_student')->getRow("student = ".$apply['student']. " and relation = $relation"))  throw new Exception('关系已存在，操作失败！');
						$data = array(
							'user' => $apply['to'],
							'student' => $apply['student'],
							'relation' => $relation,
							'create_time' => $tm,
							'creator' => $apply['from']
						);
						if(!load_model('user_student')->insert($data)) throw new Exception('操作失败');

						// 推送给档案创建者
						/*
						$push = array(
							'app' => 'auth',	'act' => 'add',	'from' => $this->uid,	'type' => 0,	'student' => $apply['student'],
							'character' => 'teacher', 'ext' => array(								
								'user' => load_model('user')->getRow($apply['to'], true, 'id,nickname'), 
								'relation' => $relation
							)
						);
						$res = push('db')->add('H_PUSH', $push);
						*/
						break;
				}
			}			
			db()->commit();
			Out(1, '成功');
		}catch(Exception $e){			
			db()->rollback();
			Out(0, $e->getMessage());
		}
	}
	
	public function exists()
	{
		$type = Http::post('type', 'int', 1);
		$from = Http::post('from', 'int', 0);
		$to = Http::post('to', 'int', 0);
		$student = Http::post('student', 'int', 0);
		$school = Http::post('school', 'int', 0);
		switch($type)
		{
			case 1:				
				$from = Http::post('student', 'int', 0);			
				break;
			case 2:				
				$to = Http::post('student', 'int', 0);			
				break;			
			case 6:					
				$from = Http::post('student', 'int', 0);						
			case 7:				
				$to = Http::post('student', 'int', 0);				
				break;
			default : 
				break;
		}
		$apply = $_Apply->getRow(compact('type', 'from', 'to'));
		if($apply) Out(1, '存在');
		Out(0, '不存在');
	}
	// 获取用户的申请消息列表
	public function getList()
	{		
		$type = Http::post('type', 'int', 0);
		$where['to'] = $this->uid;
		$where['status'] = 0;
		$type && $where['type'] = $type;
		$result = load_model('apply')->getAll($where, '','',false, true, '`type`,from,student');
		Out(1, '', $result);
	}

	public function info()
	{
		$id = Http::post("id", 'int', 0);
		$apply = load_model('apply')->getRow(array(
			'id' => $id,
			'to' => $this->uid
		), true);
		if($apply) Out(1, '', $apply);
		Out(0, '无数据！');
	}
	
	// @teacher 老师
	// @parent 学生家长
	private function follow($teacher, $parent)
	{
		if(!$teacher || !$parent) return false;
		if($teacher == $parent) return true; // 自己不能加自己

		$_Feed_User_Follow = load_model('feed_user_follow');
		$_Feed_User_Data = load_model('feed_user_data');	
		$follow = $_Feed_User_Follow->getRow(array('uid'=>$parent, 'fid'=>$teacher));
		$tm = time();
		if($follow) return true;	
		$data = array(
			'uid'=>$parent,
			'fid'=>$teacher,
			'ctime'=> $tm,
		);
		if(!load_model('feed_user_follow')->insert($data)) throw new Exception('关注失败！');
		
		// 关注数+1
		if(!$_Feed_User_Data->increment('value', array('uid'=>$parent,'key'=>'feed_following_count'), 1))
		{
			$ts_user_data = array(
				'uid'=>$parent,
				'key'=>'feed_following_count',
				'value'=>1,
				'mtime'=>$tm,
			);
			if(!$_Feed_User_Data->insert($ts_user_data)) throw new Exception('关注失败！');
		}
		//对方粉丝+1
		if(!$_Feed_User_Data->increment('value', array('uid'=>$teacher,'key'=>'feed_follower_count'), 1))
		{
			$ts_user_data = array(
				'uid'=>$teacher,
				'key'=>'feed_follower_count',
				'value'=>1,
				'mtime'=>$tm,
			);
			if(!$_Feed_User_Data->insert($ts_user_data)) throw new Exception('关注失败！');

			// 粉丝+
			$push = array(
				'app' => 'feed_follow',	'act' => 'add',	'from' => $parent,	'type' => 1, 'to' => $teacher,
				'character' => 'user', 'ext' => load_model('user')->getRow($parent, true, 'id,nickname,firstname,lastname,hulaid,avatar')
			);
			// $res = load_model('student')->push($student, $push);
			push('db')->add('H_PUSH', $push);
		}
		return true;
	}

	// 同步
	public function sync()
	{
		$res = load_model('apply')->getAll(array('user' => $this->uid, 'status' => 0), true);
		Out(1, '', $res);
	}


}