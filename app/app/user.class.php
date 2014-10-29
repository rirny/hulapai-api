<?php

class User_Api extends Api
{
	public function __construct(){
		parent::_init();
	}
	
	public function info()
	{
		$param = Http::query();
		$uid = Http::post('uid', 'int', 0);
		isset($param['uid']) || $uid = $this->uid;
		$_User = load_model('user');		
		$result = $_User->getRow($uid, true);
		if(!$result) Out(0, '无此用户！');
		$attachs = load_model('attach')->getAll(array('uid'=>$uid,'private'=>0),3,'',false,false,'attach_id,uid,name as attach_name,size,extension,save_path,save_name');
		if($attachs){
			$path = Config::get('path', 'upload', null, null);	
			foreach($attachs as $key=>$a)
			{
				$imagePath = $path.'/'.$a['save_path'];	
				$imageName = substr($a['save_name'],0,-4);
				$attachs[$key]['attach_id'] = $a['attach_id'];
				$attachs[$key]['attach_url'] = $a['save_path'].$imageName.'.jpg';
				$attachs[$key]['attach_small'] = $a['save_path'].$imageName.'_small.jpg';
				$attachs[$key]['attach_middle'] = $a['save_path'].$imageName.'_middle.jpg';
				$attachs[$key]['domain'] = 'HOST_IMAGE';

			}
		}
		$result['attachs'] = $attachs;
		$teacher = load_model('teacher')->getRow(array('user' => $uid));
		$result['mind'] = $teacher ? $teacher['mind'] : '';
		Out(1, '', $result);
		
	}

	// 个人信息更新
	public function update()
	{
		$param = Http::query();
		$_User = load_model('user');
		$info = $_User->getRow($this->uid, false);
		if(empty($info)) throw new Exception('用户不存在！');
		unset($info['password']);
        $setting = json_decode($info['setting'], true);
        if(isset($param['hulaid']) && $setting['hulaid']) throw new Exception('呼啦号只能修改一次！');		
		$data = array();
		foreach($param as $key => $value)
		{
			array_key_exists($key, $info) && $data[$key] = $value;
		}      
		if(isset($data['hulaid']))
		{            
            if(trim($data['hulaid']) == '') throw new Exception('呼啦号不能为空！');
            if(!preg_match('/^[a-zA-Z][a-zA-z_0-9]{5,19}$/i', $data['hulaid'])) throw new Exception('呼啦号格式不正确！');            
			$res = $_User->getRow("hulaid='" . $data['hulaid'] . "' Or account='" .$data['hulaid'] . "'");
			if($res) throw new Exception('呼啦号已存在！');
            $setting['hulaid'] = 1;            
		}
		
		// setting
		isset($param['friend_verify']) && $setting['friend_verify'] = $param['friend_verify'];		
		isset($param['notice']) && $setting['notice'] = $param['notice'];		

		$data['setting'] = json_encode($setting);

		$_User->update($data, $this->uid);
		$result = $_User->getRow($this->uid, true);
		Out(1, '成功', $result);
	}


	public function pwd()
	{
		$old = Http::post('old', 'string', '');
		if(!$old) throw new Exception('请输入旧密码!');		
		$new = Http::post('new', 'string', '');
		if(!$new) throw new Exception('请设置密码!');
		$_User = load_model('user');		
		$res = $_User->getRow($this->uid);
		if(!$res) throw new Exception('用户不存在!');
		$md5 = md5($old . $res['login_salt']);
		if($md5 != $res['password']) throw new Exception('原密码不正确!');
		$new = md5($new . $res['login_salt']);
		$result = $_User->update(array('password' => $new), $this->uid);
		Out(1, '修改成功!');
	}

	public function exists()
	{
		$key = Http::post('key', 'string');
		$value = Http::post('value', 'string');		
		if(!$key || !$value) throw new Exception('参数错误!');
		$keys = array('account', 'email', 'mobile', 'hulaid');
		$res = load_model('user')->getRow(array($key=>$value));		
		$result = empty($res) ? 0 : 1;
		Out($result);
	}
	
	public function remark()
	{
		$character = Http::post('character', 'string', '');
		$remark = Http::post('remark', 'string', '');
		// if(!$remark) throw new Exception('备注名不能为空!');
		$ext = '';
		$type = 0;
		$school = Http::post('school', 'int', 0);
		if($school)
		{
			$ext = $school;
			$type = 1;
		}
		switch($character)
		{
			case 'teacher': // 老师备注学生
				$teacher = $this->uid;//Http::post('teacher', 'int', 0);				
				$student = Http::post('student', 'int', 0);
				$res = load_model("teacher_student")->update(array('student_name' => $remark), compact('teacher', 'student', 'type', 'ext'));
			break;
			case 'student':// 学生家长备注老师
				$teacher = Http::post('teacher', 'int', 0);
				$student = Http::post('student', 'int', 0);
				$res = load_model("teacher_student")->update(array('teacher_name' => $remark), compact('teacher', 'student', 'type', 'ext'));
			break;
			case 'friend':
				// $teacher = Http::post('user', 'int', 0);
				$friend = Http::post('friend', 'int', 0);
				$user = $this->uid;
				$res = load_model("friend")->update(array('remark' => $remark), compact('user', 'friend'));
			break;
		}
		Out(1, '成功');
	}
}