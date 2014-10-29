<?php
class Contact_Api extends Api
{
	public function __construct(){
		parent::_init();
	}
	
	public function compare()
	{
		$contact = Http::post('contact', 'string', '');
		$type = Http::post('type', 'string', '');
		$student = Http::post('student', 'string', '');
		if(!$contact) throw new Exception('请选择联系人！');
		$contacts = explode(",", $contact);
		$result = array();
		switch($type)
		{
			case 'friend':
				foreach($contacts as $key=>$item)
				{					
					$user = load_model('user')->getRow(array('account' => $item), false, 'id, hualid, nickname,avatar');
					if($user)
					{
						$is_friend = load_model('friend')->is_friend($this->uid, $user['id']) ;
						$result[$item] = array(
							'_id' => $user['id'],
							'hulaid' => $user['hulaid'],
							'avatar' => $user['avatar'],
							'nickname' => $user['nickname'],
							'friend' => $is_friend ? 1 : 0
						);
					}					  
				}
				break;
			case 'teacher':
				if(!$student) throw new Exception('未指定学生！');
				foreach($contacts as $key=>$item)
				{					
					$user = load_model('user')->getRow(array('account' => $item), false, 'id');
					if($user)
					{
						$is_teacher = load_model('teacher_student')->getOne($user['id'], $student) ;
						$result[$item] = array(
							'_id' => $user['id'],
							'hulaid' => $user['hulaid'],
							'avatar' => $user['avatar'],
							'nickname' => $user['nickname'],
							'teacher' => $is_teacher ? 1 : 0
						);
					}					  
				}
				break;
			case 'student':				
				foreach($contacts as $key=>$item)
				{					
					$user = load_model('user')->getRow(array('account' => $item), false, 'id');
					if($user)
					{
						$has_student = load_model('user_student')->get_user_student($user['id']);
						$result[$item] = array(
							'_id' => $user['id'],
							'hulaid' => $user['hulaid'],
							'avatar' => $user['avatar'],
							'nickname' => $user['nickname'],
							'student' => empty($has_student) ? 0 : 1
						);
					}					  
				}
				break;
		}		
		if(empty($result)) throw new Exception('无匹配！');
		Out(1, '', $result);		
	}
}