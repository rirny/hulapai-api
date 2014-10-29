<?php
class Upload_Api extends Api
{
	public function __construct(){
		parent::_init();

	}

	public function index()
	{
		$type = Http::post('type', 'string', '');
		$student = Http::post('student', 'int', 0);
		$school = Http::post('school', 'int', 0);
		// $attach = Http::post('attache', 'int', 0);
		$private = $type == 'feed' || $type == 'space' || $type == 'comment' ? 0 : 1; // space feed 1 else 0			
		if($type == 'avatar')
		{
			$id = $this->uid;
		}else if($type == 'student')
		{
			$res = load_model('user_student')->getRow(array('user' => $this->uid, 'student' => $student));
			if(!$res) throw new Exception('未知的学生！');
			$id = $student;
		}else if($type == 'school')
		{
			$res = load_model('school')->getRow(array('id' => $school));
			if(!$res) throw new Exception('未知的机构！');
			$id = $school;
		}else{
			$id = 0;
		}		
		if(empty($_FILES)) throw new Exception('没有文件域');
		import('file');
		$tm = time();
		$info = Files::upload($type, 'image', $id);		
		if(empty($info)) throw new Exception('上传失败');			
		if(!$id)
		{		
			$device = Http::get_device();				
			if($device['src'] == 'ios')
			{
				$from = 3;
			}else if($device['src'] == 'android')
			{
				$from = 2;
			}else{
				$from = 0;
			}			
			$data = array(
				'name' => $info['name'],
				'app_name' => 'app',
				'table' => $type,
				'uid' => $this->uid,
				'type' => $info['type'],
				'ctime' => $tm,
				'size' => $info['size'],
				'extension' => $info['extension'],
				'hash' => $info['hash'],
				'private' => $private,
				'save_path' => $info['save_path'],
				'save_name' => $info['save_name'],
				'save_domain' => 'http://static.hulapai.com',
				'from' => $from // 0：网站；1：手机网页版；2：android；3：ios',
			);
			$id = load_model('attach')->insert($data);
			$result = load_model('attach')->getRow($id, true, 'attach_id id,concat(save_path,save_name) path');
			// $result = array('id' => $row['id']);
		}else{			
			if($student){
				$res = load_model('student')->update(array('avatar' => $tm), $id);
				if(!$res) throw new Exception('上传失败');
				$result = array('avatar' => $tm);
			}elseif($school){
				$res = load_model('school')->update(array('avatar' => $tm), $id);
				if(!$res) throw new Exception('上传失败');
				$result = array('school' => $info['save_path'].$info['save_name']);
			}else{
				$res = load_model('user')->update(array('avatar' => $tm), $id);
				if(!$res) throw new Exception('上传失败');
				$result = array('avatar' => $tm);
			}
		}
		Out(1, '成功', $result);
	}
}