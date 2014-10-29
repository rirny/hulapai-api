<?php
set_time_limit(0);
class Import_Api extends Api
{
	public $app = '';
	public $act = '';

	public $db = 'thinksns';

	
	/*
	 *---------------------------------------------------------------------
	 * 导入用户
	 *---------------------------------------------------------------------
	*/
	public function user()
	{
		db()->query('TRUNCATE huladb.t_user');
		$res = db()->fetchAll('select u.*,i.* from hulapai.t_user u left join hulapai.t_user_info i on u.uid=i.uid');
		foreach($res as $key=>$item)
		{
			if(empty($item['account'])) continue;
			$data = array(
				'id' => $item['uid'],
				'account' => $item['account'],
				'password' => $item['password'],
				'email' => $item['email'],
				'password' => $item['password'],
				'nickname' => $item['uname'],
				'create_time' => strtotime($item['reg_time']),
				'login_times' => $item['login_times'],
				'last_login_time' => $item['last_login_time'],
				'last_login_ip' => $item['last_login_ip'],
				'login_salt' => $item['login_salt'],

				'hulaid' => $item['hulaid'],
				'sign' => $item['sign'],
				'gender' => $item['sex'],
				'mobile' => $item['phone'],
				'status' => $item['state'],
				'birthday' => $item['birthday'],				

				'province' => $item['province'],
				'city' => $item['city'],
				'area' => $item['district'],

				'token' => $item['device_token'],

				'course_notice' => true,
				'setting' => json_encode(array(
					'hulaid' => $item['set_hulaid'] == 1 ? 1 :0, // 呼啦号是否已设定
					'friend_verify' => 1,
					'notice' => array(
						'method' => 0, // 震动
						'types' => '1,2,3,4,5' // 需要提醒的消息
					)
				)),
			);
			db()->insert('huladb.t_user', $data);
		}		
	}
	
	/*---------------------------------------------------------------------
	 * 导入地区
	 ---------------------------------------------------------------------*/
	public function area()
	{
		db()->query('TRUNCATE huladb.t_area');
		$res = db()->fetchAll('select * from thinksns_3_0.ts_area');
		foreach($res as $key=>$item)
		{
			$data = array(
				'id' => $item['area_id'],
				'title' => $item['title'],
				'pid' => $item['pid'],
				'sort' => $item['sort']
			);
			db()->insert('huladb.t_area', $data);
		}
	}

	/*---------------------------------------------------------------------
	 * 导入教师
	 ---------------------------------------------------------------------*/
	public function teacher()
	{
		db()->query('TRUNCATE huladb.t_teacher');
		$res = db()->fetchAll('select * from hulapai.t_profile');
		foreach($res as $key=>$item)
		{			
			$classes = db()->fetchOne('select count(*) n from hulapai.t_sche where `type`=1 And to_id=' . $item['uid']);			
			$comments = db()->fetchOne('select count(*) n from hulapai.t_sche_comment where `is_reply`=0 And comm_uid=' . $item['uid']);			
			$goods = db()->fetchOne('select sum(thumb) n from hulapai.t_sche_comment where `is_reply`=0 And thumb=1 And comm_uid=' . $item['uid']);

			if(strpos($item['target'], '儿童') !== false && strpos($item['target'], '成人') !== false)
			{
				$target = 0;
			}else if(strpos($item['target'], '儿童') !== false){
				$target = 1;
			}else if(strpos($item['target'], '成人') !== false){
				$target = 2;
			}
			
			$data = array(
				'user' => $item['uid'],
				'background' => $item['edu'],
				'mind' => $item['idea'],
				'target' => $target,
				'classes' => $classes,
				'comments' => $comments,
				'goods' => $goods ? $goods : 0,
			);
			db()->insert('huladb.t_teacher', $data);
		}
	}

	/*
	 * ---------------------------------------------------------------------
	 * 学生
	 * ---------------------------------------------------------------------
	*/
	public function student()
	{		
		db()->query('TRUNCATE huladb.t_student');
		$res = db()->fetchAll('select * from hulapai.t_stu');
		foreach($res as $key=>$item)
		{				
			$class_res = db()->fetchAll('select absence from hulapai.t_sche where `type`=2 And to_id=' . $item['stu_id']);			
			$classes = $absence = $leave = 0;
			foreach($class_res as $val)
			{
				$classes++;
				if($val['absence'] == 1) $absence++;
				if($val['absence'] == 2) $leave++;
			}
			$data = array(
				'id' => $item['stu_id'],
				'name' => $item['name'],
				'gender' => $item['sex'],
				'birthday' => $item['birthday'],
				'create_time' => $item['add_time'],				
				'classes' => $classes,
				'absence' => $absence,
				'leave' => $leave,
				'operator' => $item['uid'],
				'creator' => $item['uid']
			);
			db()->insert('huladb.t_student', $data);
		}
	}

	public function user_student()
	{		
		db()->query('TRUNCATE huladb.t_user_student');		
		$res = db()->fetchAll('select * from hulapai.t_stu_custody');
		foreach($res as $key=>$item)
		{			
			$creator = db()->fetchOne('select `uid` from hulapai.t_stu where stu_id=' . $item['stu_id']);
			$data = array(
				// 'id' => $item['stu_id'],
				'user' => $item['uid'],
				'relation' => $item['relation'],
				'student' => $item['stu_id'],
				'creator' => $creator,
				'create_time' => strtotime($item['add_time'])
			);
			db()->insert('huladb.t_user_student', $data);
		}
	}

	/*---------------------------------------------------------------------
	 * 导老师-学生
	 ---------------------------------------------------------------------*/
	public function teacher_student()
	{		
		db()->query('TRUNCATE huladb.t_teacher_student');		
		$res = db()->fetchAll('select * from hulapai.t_tch_stu');		
		foreach($res as $key=>$item)
		{	
			$sql = 'select s.absence, s.start_date from hulapai.t_sche s left join hulapai.t_sche t on s.inst_sche_id=t.inst_sche_id';
			$sql.= ' where s.`type`=2 And s.to_id=' . $item['stu_id'];
			$sql.= ' And t.`type`=1 And t.to_id=' . $item['tch_id'];
			$sql.= ' Order by s.start_date Asc';			
			$resource = db()->fetchAll($sql);
			$classes = $absence = $attend = $leave = 0;
			$study_date = '0000-00-00';
			foreach($resource as $val)
			{
				$classes++;
				if($val['absence'] == 1)
				{
					$absence++;
				}else if($val['absence'] == 2)
				{
					$leave++;
				}else{
					$attend++;
				}
				$study_date == '0000-00-00' && $study_date = date('Y-m-d', strtotime($val['start_date']));
			}			
			$data = array(				
				'teacher' => $item['tch_id'],
				'student' => $item['stu_id'],
				'create_time' => strtotime($item['verify_time']),
				// 'creator' => $item['user'],
				'classes' => $classes,
				'absence' => $absence,
				'leave' => $leave,
				'attend' => $attend,
				'study_date' => $study_date
			);
			db()->insert('huladb.t_teacher_student', $data);
		}
	}

	/*---------------------------------------------------------------------
	 * 课程分类
	 ---------------------------------------------------------------------*/
	public function course_type()
	{
		db()->query('TRUNCATE huladb.t_course_type');		
		$res = db()->fetchAll('select * from hulapai.t_course');
		foreach($res as $key=>$item)
		{
			$data = array(				
				'id' => $item['course_id'],
				'name' => $item['title'],
				'pid' => $item['p_id']
			);
			db()->insert('huladb.t_course_type', $data);
		}		
	}
	
	/*---------------------------------------------------------------------
	 * 课程
	 *---------------------------------------------------------------------
	*/
	public function event()
	{			
		db()->query('TRUNCATE huladb.t_event');
		$res = db()->fetchAll('select * from hulapai.t_inst_sche');
		foreach($res as $key=>$item)
		{
			$data = array(				
				'id' => $item['inst_sche_id'],
				'course' => $item['course_id'],
				'type' => 0,
				'text' => $item['text'],
				'pid' => $item['pid'],
				'start_date' => $item['start_date'],
				'end_date' => $item['end_date'],
				'rec_type' => $item['rec_type'],
				'length' => $item['length'],
				'teacher' => $item['create_uid'],
				'school' => $item['inst_id'],
				'color' => $item['color'],				
				'creator' => $item['create_uid'],
				'create_time' => $item['time'],
				'status' => $item['status'], // 0正常 1删除 2已上
				'commented' => $item['is_comment']
			);
			db()->insert('huladb.t_event', $data);
		}		
	}

	/*
	 *---------------------------------------------------------------------
	 * 老师课程
	 *---------------------------------------------------------------------
	*/
	public function course_teacher()
	{			
		db()->query('TRUNCATE huladb.t_course_teacher');
		$res = db()->fetchAll('select * from hulapai.t_sche where `type`=1');
		foreach($res as $key=>$item)
		{
			$data = array(				
				'event' => $item['inst_sche_id'],				
				// 'text' => $item['text'],
				'priv' => 15, // 上课1 考勤2 点评4 通知8
				'teacher' => $item['to_id'],
				'remark' => $item['remark'],
				'color' => $item['color']
			);			
			db()->insert('huladb.t_course_teacher', $data);
		}		
	}
	
	/*
	 *---------------------------------------------------------------------
	 * 学生课程
	 *---------------------------------------------------------------------
	*/
	public function course_student()
	{
		db()->query('TRUNCATE huladb.t_course_student');
		$res = db()->fetchAll('select * from hulapai.t_sche where `type`=2');
		foreach($res as $key=>$item)
		{
			$data = array(				
				'event' => $item['inst_sche_id'],			
				'student' => $item['to_id'],
				'absence' => $item['absence'],
				'remark' => $item['remark'],
				'color' => $item['color']
			);			
			db()->insert('huladb.t_course_student', $data);
		}		
	}

	/*
	 *---------------------------------------------------------------------
	 * 点评
	 *---------------------------------------------------------------------
	*/
	public function comment()
	{		
		db()->query('TRUNCATE huladb.t_comment');
		$res = db()->fetchAll('select * from hulapai.t_sche_comment');
		foreach($res as $key=>$item)
		{
			$event = db()->fetchOne('select inst_sche_id from hulapai.t_sche where `sche_id`=' . $item['sche_id'] . " limit 1");
			$data = array(		
				'id' => $item['comm_id'],
				'creator' => $item['comm_uid'],			
				'content' => $item['content'],
				'student' => $item['stu_id'],
				'event' => $event,
				'attach' => $item['attach'],
				'reply' => $item['is_reply'],
				'pid' => $item['pid'],
				'create_time' => strtotime($item['time']),				
			);
			db()->insert('huladb.t_comment', $data);
		}		
	}

	/*
	 *---------------------------------------------------------------------
	 * 申请
	 * 1家长找老师,2老师加家长,3机构加老师,4老师加机构,5好友申请,6学生加机构,7机构找学生
	 *---------------------------------------------------------------------
	*/
	public function apply()
	{		
		db()->query('TRUNCATE huladb.t_apply');
		$res = db()->fetchAll('select * from hulapai.t_apply');
		foreach($res as $key=>$item)
		{			
			$data = array(				
				'from' => $item['from'],
				'to' => $item['to'],
				'type' => $item['type'],
				'ext' => $item['extra'],				
				'create_time' => strtotime($item['time']),
				'status' => 1
			);
			db()->insert('huladb.t_apply', $data);
		}		
	}	

	/*
	 *---------------------------------------------------------------------
	 * 设备信息	 
	 *---------------------------------------------------------------------
	*/
	public function device()
	{		
		db()->query('TRUNCATE huladb.t_device');
		$res = db()->fetchAll('select * from hulapai.t_device where sn<>"" group by uid,sn');
		foreach($res as $key=>$item)
		{			
			$data = array(				
				'user' => $item['uid'],
				'brand' => $item['brand'],
				'model' => $item['model'],
				'sn' => $item['sn'],				
				'os' => $item['os'],
				'modify_time' => date('Y-m-d H:i:s', $item['time'])
			);
			db()->insert('huladb.t_device', $data);
		}		
	}

	/*
	 *---------------------------------------------------------------------
	 * feedBack	 
	 *---------------------------------------------------------------------
	*/
	public function feed_back()
	{		
		db()->query('TRUNCATE huladb.t_device');
		$res = db()->fetchAll('select * from hulapai.t_advice');		
		foreach($res as $key=>$item)
		{			
			$data = array(				
				'from' => $item['uid'],				
				'to' => 0,
				'type' => 0,
				'sorts' => 0,
				'content' => $item['content'],
				'create_time' => strtotime($item['time'])
			);
			db()->insert('huladb.t_feedback', $data);
		}

		$res = db()->fetchAll('select * from hulapai.t_complaint');
		foreach($res as $key=>$item)
		{			
			$data = array(				
				'from' => $item['uid'],				
				'to' => $item['teacher'],
				'type' => 0,
				'sorts' => 1,
				'school' => $item['inst_id'],
				'content' => $item['text'],
				'anonymous' => $item['anonymous'],
				'create_time' => strtotime($item['time'])
			);
			db()->insert('huladb.t_feedback', $data);
		}
	}

	/*
	 *---------------------------------------------------------------------
	 * 通知	
	 * 系统通知 机构通知 老师（课程）通知
	 *---------------------------------------------------------------------
	*/
	public function notify()
	{		
		db()->query('TRUNCATE huladb.t_notify');
		$res = db()->fetchAll('select * from hulapai.t_notify_0 where `type`=1 or `type`=2');
		foreach($res as $key=>$item)
		{
			$ext = $item['extra'] ? json_decode($item['extra'], true) : '';
			if(empty($ext['stu_id'])) continue;
			$student =  $ext['stu_id'];
			$data = array(
				'creator' => $item['uid'],
				'to' => $student, // 多个则分发到 message
				'event' => $item['inst_sche_id'],
				'type' => $item['event'] ? 1 : ($item['inst_sche_id'] ? 2 : 0),
				'content' => $item['content'],
				'attach' => $item['attach'],	
				'school' => $item['inst_id'],
				'create_time' => date('Y-m-d H:i:s', $item['time'])
			);
			db()->insert('huladb.t_notify', $data);
		}
	}

	/*
	 *---------------------------------------------------------------------
	 * 个人空间	
	 * space
	 *---------------------------------------------------------------------
	*/
	public function space()
	{		
		db()->query('TRUNCATE huladb.t_space');
		$res = db()->fetchAll('select * from hulapai.t_blog');
		foreach($res as $key=>$item)
		{			
			$data = array(
				'id' => $item['blog_id'],
				'creator' => $item['uid'],				
				'content' => $item['text'],
				'attach' => $item['attach'],				
				'create_time' => $item['time']
			);
			db()->insert('huladb.t_space', $data);
		}
	}
	/*
	 *---------------------------------------------------------------------
	 * 个人空间	
	 * 日志回复
	 *---------------------------------------------------------------------
	*/
	public function space_comment()
	{		
		db()->query('TRUNCATE huladb.t_space_comment');
		$res = db()->fetchAll('select * from hulapai.t_blog_comment');
		foreach($res as $key=>$item)
		{			
			$data = array(				
				'blog' => $item['blog_id'],
				'creator' => $item['comm_uid'],			
				'content' => $item['text'],
				'to' => $item['reply_uid'],				
				'create_time' => $item['time']
			);
			db()->insert('huladb.t_space_comment', $data);
		}
	}

	/*
	 *---------------------------------------------------------------------
	 * 好友
	 *---------------------------------------------------------------------
	*/
	public function friend()
	{		
		db()->query('TRUNCATE huladb.t_friend');
		$res = db()->fetchAll('select * from hulapai.t_friend');
		foreach($res as $key=>$item)
		{			
			$data = array(
				'user' => $item['uid'],			
				'friend' => $item['friend_uid'],				
				'create_time' => $item['ctime']
			);
			db()->insert('huladb.t_friend', $data);
		}
	}	
}