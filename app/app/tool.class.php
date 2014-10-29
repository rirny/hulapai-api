<?php
class Tool_Api extends Api
{
	public $app = '';
	public $act = '';
	
	public function __construct(){
		// parent::_init();
	}

	public function index()
	{
		
	}
	
	public function up_avatar()
	{
		$users = load_model('user')->getAll(array('avatar' => 0), '', '', false, false, 'id');
		import('file');
		foreach($users as $key=>$item)
		{			
			$avatar = Files::get_avatar($item['id'], 0, 0);
			if(strpos($avatar, 'original'))
			{
				$mt = filemtime(str_replace("http://192.168.0.200:81/", "/home/www/server/sns/data/upload/", $avatar));
				$res = db()->update('t_user', array('avatar' => $mt), $item['id']);
				// var_dump($res);
			}			
		}
	}

	public function up_student_avatar()
	{
		$users = load_model('student')->getAll(array('status' => 0), '', '', false, false, 'id');
		import('file');
		foreach($users as $key=>$item)
		{			
			$avatar = Files::get_avatar($item['id'], 1, 50);			
			$mt = 0;
			if(strpos($avatar, 'original'))
			{		
				$avatar = str_replace("http://192.168.0.200:81/", "/home/www/server/sns/data/upload/", $avatar);
				if(file_exists($avatar))
				{
					$mt = filemtime($avatar);					
				}
			}
			$res = db()->update('t_student', array('avatar' => $mt), $item['id']);
		}
	}
}
