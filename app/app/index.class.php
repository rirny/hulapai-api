<?php
/**
 * msgtype
 * SSdesc  系统模块
 * SSend
 */
class Index_Api extends Api
{
	public $app = '';
	public $act = '';

	public function index()
	{		
		Out(0, 'This is Hulapi!');
	}
	
	// 启动
	public function start()
	{
		$token = Http::post('token', 'string', '');
		$String = load('ustring');
		$result = Http::post('name', 'string', '');
		// exit;
		//$result =  array(Ustring::topinyin($str) => $str);
		Out(1, $result);
	}
	
	/**
	 * structdef.xml
	 * 	SSaction     code
	 *  SSdesc       短信验证码 
	 *  SSpargam 
	 * 		mobile   int   手机号码
	 *  SSreturn 
	 * 		null	 varchar  短信验证码
	 *  SSend
	 */
	public function code()
	{
		$mobile = Http::post('mobile', 'string');
        $type = Http::post('type', 'int', 0); // 0 注册 1、找回密码
		if(!$mobile) throw new Exception('请输入手机号！');	
        $message = '';
        if($type == 0)
        {
            if(load_model('user')->getRow(array('account' => $mobile))) throw new Exception ('用户已存在，您可以通过忘记密码找回或重设您的密码!');
			$message = Config::get('register', 'notice', Null, Null);
        }else if($type == 1)
		{
			if(!load_model('user')->getRow(array('account' => $mobile))) throw new Exception ('用户不存在！');
			$message = Config::get('forget', 'notice', Null, Null);
		}
		$_Verify = load_model('verify');
		db()->begin();
		try{			
			$res = $_Verify->send($mobile, $type, $message);
			if(is_array($res))
			{
				db()->commit();
				Out(1, '成功', array('code' => $res['code']));
			}else
			{
				$Errors = config::get('sms', 'error', Null, '');
				$error = isset($Errors[$res]) ? $Errors[$res] : '发送失败';				
				Out($res, $error);
			}
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());
		}
	}
	

	/**
	 * structdef.xml
	 * 	SSaction     verify_code
	 *  SSdesc       短信验证 
	 *  SSpargam 
	 * 		mobile   int   手机号码
	 * 		code	varchar  短信验证码
	 *  SSreturn 
	 *  SSend
	 */
	public function verify_code()
	{
		$mobile = Http::post('mobile', 'string');
		$code = Http::post('code', 'string');
		$_Verify = load_model('verify');
		$res = $_Verify->getRow(array('mobile' => $mobile, 'code' => $code), false, '*', 'send_time Desc');
		if($res && $res['deadline'] > time()) Out(1, '成功');
		throw new Exception('验证失败！');
	}
	
	/**
	 * structdef.xml
	 * 	SSaction     login
	 *  SSdesc       登录
	 *  SSpargam 
	 * 		account   varchar   账号
	 * 		password	varchar  密码
	 * 		token		varchar  秘钥
	 *  SSreturn 
	 * 		user   singleArray  用户信息 user
	 * 		teacher  singleArray  老师信息 teacher
	 * 		student  array  学生信息  student
	 *  SSreturn_array_user
	 * 		_id			int   id
	 * 		account		varchar  账号
	 * 		firstname	varchar		姓
	 * 		lastname	varchar		名
	 * 		email		varchar		邮箱
	 * 		nickname	varchar    昵称
	 * 		gender		int 		性别 0男，1女
	 * 		hulaid		varchar		呼啦号
	 * 		avatar		int		头像最后更新时间
	 * 		birthday	date		生日
	 * 		province	int		省
	 * 		city		int		市
	 * 		area		int		区
	 * 		address		varchar	地址
	 * 		create_time	int		注册时间
	 * 		login_salt	char		登录标
	 * 		mobile		int  手机
	 * 		status		int  0正常1冻结
	 * 		setting	    singleArray	个人设置	user_setting
	 * 		sign		varchar 	签名
	 * 		course_notice	int 	课程通知
	 * 		disturb		int		免打扰
	 * 		token		varchar		设备token
	 * SSreturn_array_end_user
	 * SSreturn_array_user_setting
	 * 		hulaid		varchar		呼啦号
	 * 		friend_verify	int 	是否允许加好友
	 * 		notice		singleArray	通知 	user_setting_notice
	 * SSreturn_array_end_user_setting
	 * SSreturn_array_user_setting_notice
	 * 		method		int
	 * 		types		varchar
	 * SSreturn_array_end_user_setting_notice
	 *  SSreturn_array_teacher
	 * 		_id			int   id 
	 * 		user		int  用户ID
	 * 		province	int  省
	 * 		city		int   市
	 * 		area		int  区
	 * 		address		varchar  地址
	 * 		target		int    对象
	 * 		background	text	教育背景
	 * 		mind		text	教学理念
	 * 		classes		int   课时数
	 * 		comments	int   点评数
	 * 		goods		int		  	
	 * 		create_time	 int  创建时间
	 * 		status		int   状态0正常 1删除
	 * 		course   array   课程   teacher_course
	 * SSreturn_array_end_teacher
	 *  SSreturn_array_teacher_course
	 * 		_id			int   id 
	 * 		title		varchar  标题
	 * 		teacher		int   老师
	 * 		school		int    机构
	 * 		experience	int    经验
	 * 		fee			float  学费
	 * SSreturn_array_end_teacher_course
	 *  SSreturn_array_student
	 * 		_id			int   id 
	 * 		name		varchar  学生名
	 * 		nickname	varchar  昵称
	 * 		avatar		int   头像最后更新时间
	 * 		gender		int  性别
	 * 		birthday		date  生日
	 * 		classes		int    创建时间
	 * 		absence	int	缺勤数
	 * 		leave		int	请假
	 * 		create_time		int   创建时间
	 * 		status	int   状态：删除、锁定...
	 * 		operator		int		操作者  	
	 * 		tag	 varchar  标签
	 * 		creator		int   创建者
	 * 		relation   int    1本人,2爸爸,3妈妈,4其他
	 * 		rid		int  t_user_student表id
	 * SSreturn_array_end_student
	 *  SSend
	 */
	public function login()
	{		
		$username = Http::post('account', 'string', '');
		$password = Http::post('password', 'string', '');
		$token = Http::post('token', 'string', '');	
		if(!$username) Out(0, '用户名不能为空！');
		if(!$password) Out(0, '密码不能为空！');		
		$_User = load_model('user');	
		$user = $_User->getRow(array('account' => $username));
		$user || $user = $_User->getRow(array('hulaid' => $username));
		if(!$user) Out(0, '用户不存在！');		
		if($user['password'] !== md5($password . $user['login_salt'])) Out(0, '密码不正确!');
		db()->begin();
		try{
			$_User->login($user, $token);			
			load_model('device')->set(); // 更新device
			$result['user'] = $_User->Format($user);
			$_Teacher = load_model('teacher');
			$teacher = $_Teacher->getRow(array('user' => $user['id']) , true);
			$teacher && $result['course'] = load_model('course')->getAll(array('teacher' => $user['id'], 'status' => 0), '', '', false, true, 'id, `title`,teacher,school,`experience`,`type`,fee');			
			$result['teacher'] = $teacher;			
			$result['student'] = load_model('user_student')->get_user_student($user['id'], true);		
			db()->commit();
			Out(1, '', $result);
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());			
		}
	}

	public function logout()
	{
		load_model('user')->logout($this->uid);		
		Out(1, '已退出！');
	}
	
	/**
	 * structdef.xml
	 * 	SSaction     register
	 *  SSdesc       注册
	 *  SSpargam 
	 * 		account   varchar   账号
	 * 		password	varchar  密码
	 * 		gender		int  性别
	 * 		nickname	varchar  昵称
	 * 		verify		varchar  验证码
	 * 		token	varchar   秘钥
	 * 		avatar		file   头像
	 *  SSreturn 
	 * 		_id			int   id
	 * 		account		varchar  账号
	 * 		firstname	varchar		姓
	 * 		lastname	varchar		名
	 * 		email		varchar		邮箱
	 * 		nickname	varchar    昵称
	 * 		gender		int 		性别 0男，1女
	 * 		hulaid		varchar		呼啦号
	 * 		avatar		int		头像最后更新时间
	 * 		birthday	date		生日
	 * 		province	int		省
	 * 		city		int		市
	 * 		area		int		区
	 * 		address		varchar	地址
	 * 		create_time	int		注册时间
	 * 		login_salt	char		登录标
	 * 		mobile		int  手机
	 * 		status		int  0正常1冻结
	 * 		setting	    singleArray	个人设置	setting
	 * 		sign		varchar 	签名
	 * 		course_notice	int 	课程通知
	 * 		disturb		int		免打扰
	 * 		token		varchar		设备token
	 * SSreturn_array_setting
	 * 		hulaid		varchar		呼啦号
	 * 		friend_verify	int 	是否允许加好友
	 * 		notice		singleArray	通知 	setting_notice
	 * SSreturn_array_end_setting
	 * SSreturn_array_setting_notice
	 * 		method		int
	 * 		types		varchar
	 * SSreturn_array_end_setting_notice
	 *  SSend
	 */
	public function register()
	{
		$account = Http::post('account', 'string', '');		
		$password = Http::post('password', 'string', '');
		$gender = Http::post('gender', 'int', 1);
		$nickname = Http::post('nickname', 'string', '');
		$verify = Http::post('verify', 'string', '');
		$token = Http::post('token', 'string', '');
		if(!$account) Out(0, '用户名不能为空！');
		if(!$password) Out(0, '密码不能为空！');
		if(!$verify) Out(0, '验证码不能为空！');
		$_User = load_model('user');		
		if($_User->getRow(array('account' => $account))) Out(0, '用户已存在，您可以通过忘记密码找回或重设您的密码!');
        if(!$_User->verify($account, $verify)) {
            Out(0, '验证码不正确！');
        }
		$login_salt = rand(10000,99999);
		$password = md5($password . $login_salt);

		$agent = Http::getSource();
		$user = compact('account', 'password', 'gender', 'nickname', 'login_salt', 'token', 'agent');		
		try
		{
			$user['setting'] = json_encode(array(
				"hulaid" => 0,
                "friend_verify" => 1,
                "notice" => array(
                    "method" => 0,
                    "types" => "1,2,3,4,5"
				)
			));
			load('ustring');
			empty($user['nickname']) || $user['nickname_en'] = Ustring::topinyin($user['nickname']);
			$id = $_User->register($user, $verify);	
			if(!$id) throw new Exception('注册失败！');
			if(!empty($_FILES))
			{	
				import('file');
				if(!($info=Files::upload('avatar', 'image', $id)))
				{
					throw new Exception('上传失败');
				}				
				if(!$_User->update(array('avatar' => time()), $id))
				{
					throw new Exception('更新失败');
				}				
			}            
			$user = $_User->getRow($id, true);
			Out(1, '注册成功！', $user);
		}catch(Exception $e)
		{
			Out(0, $e->getMessage());
		}
	}	

    public function findPwd()
    {       
        $account = Http::post('account', 'string', '');		
		if(!$account) throw new Exception ("手机错误！");
		$code = Http::post('verify', 'string', ''); 
		if(!$code) throw new Exception ("请填写验证码！");
        $password = Http::post('password', 'string', ''); 
		if(!$password) throw new Exception ("请输入新密码！");
        $_User = load_model('user');        
        if(!$_User->verify($account, $code, 1)) throw new Exception ("验证码不正确！"); 
        db()->begin();
        try{
            $user =  $_User->getRow(array('account' => $account));        
            $login_salt = $user['login_salt'];
            $res = $_User->update(array('password' => md5($password . $login_salt)), $user['id']);
            $_User->verify_delete($account, 1); // 清除用户的注册验证码！
            db()->commit();
            Out(1, '成功!');
        }  catch (Exception $e)
        {
            db()->rollback();
            Out(0, $e->getMessage());
        }
    }
	
	public function sms()
	{		
		$balance = SMS()->getBalance();
		var_dump(SMS()->getError());
		var_dump($balance);
		/*
		var_dump(SMS()->getMO());
		*/
	}

	public function help()
	{
		
	}
    
    public function redis()
	{
		$config = Config::get(null, 'redis');
		redis()->push('H_PUSH', array('app' => 'event', 'user'=> '54', 'id' => 12, '您有新的课程'));	
	}

	public function xmpp()
	{
		include(LIB . '/XMPPHP/XMPP.php');
		$option = Config::get(null, 'xmpp');
		extract($option);	
		// $host, $port, $user, $password, $resource, $server = null, $printlog = false, $loglevel = null
		$xmpp = new XMPPHP_XMPP('192.168.0.222', 5222, 1, 'admin', 'hulapai', '192.168.0.222', true, 4);
		$xmpp->connect();
		$xmpp->processUntil('session_start');
		//var_dump($xmpp->getVCard('20@192.168.0.222'));
		$xmpp->message('20@192.168.0.222', 'This is a test message!');
		//$xmpp->message($push['to'].'@'.XMPP_SERVER, json_encode(array('key'=>$push['key'], 'value'=>$push['value'])));
		
		//var_dump($xmpp);
		/*
		$xmpp->connect();
		$xmpp->processUntil('session_start');
		$xmpp->message('20@hulapai.com', 'This is a test message!');
		var_dump($xmpp);
		$xmpp->disconnect();
		//var_dump($xmpp->getVCard('20@hulapai.com'));
		// var_dump($xmpp->subscribe('20@hulapai.com'));
		//var_dump($xmpp);
		// $xmpp->message('20@'.$server, json_encode(array('key'=>1, 'value'=>2)));
		*/
						
	}

	// 同步
	public function  sync()
	{
		//print_r($this);
		//$res = db()->decrement('t_user', 'login_times', 'id=57');
		
	}

	public function test()
	{
		$result['cookie'] = $_COOKIE;
		$result['session'] = $_SESSION;
		echo json_encode($result);
	}
	
	public function device()
	{		
		$type = Http::post('type', 'int', 0);		
		if($type == 1) // 取用户最后一次的device
		{
			$user = Http::post('user', 'int', '');
			if(!$user) throw new Exception('用户不能为空！');
			$device = db()->fetchRow("select * from t_device where `user`={$user} Order by modify_time desc");
		}else if($type == 2){
			parent::_init();
			$user = $this->uid;
			if(!$user) throw new Exception('用户不能为空！');
			$device = db()->fetchRow("select * from t_device where `user`={$user} Order by modify_time desc");
		}else{ // 当前           
			$device = Http::agent();			
		}
		if(!$device) throw new Exception('没有设备信息！');
		Out(1, '成功', $device);
	}
	
    public function push()
    {
        push('db')->add('H_PUSH', array(
            'app' => 'event',
            'act' => 'delete',
            'from'=> 24,
            'to' => 1001,
			'student' => 1,
            'character' => 'student',
            'ext' => array('event' => 198, 'is_loop' => 0, 'whole' => 0, 'old' => array(
				'remark' => '11111', 'school' => 0, 'teacher' => 2,
				'pid' => 0, 'length' => 3600, 'is_loop' => 1, 'rec_type' => "week_1___1,5#",
				'start_date' => '2013-09-01 09:10:00', 'end_date' => "2013-09-03 09:50:00"
			)),
            'type' => 2
        ));    		
        
    }
	
    public function testmodel()
    {
        $res = load_model('grade_student', array('table' => 't_grade_student'))->getAll(array('grade'=>5));        
        print_r($res);
    }


	
	/*
	public function changeCode()
	{
		db()->query("use openfire");
		$res = db()->fetchAll("show tables");
		$n=0;
		$sql = '';
		foreach($res as $item)
		{
			// if($n > 2) break; 
			$cols = db()->fetchAll("SHOW FULL FIELDS FROM `{$item['Tables_in_openfire']}`;");
			foreach($cols as $col)
			{
				if(strpos($col['Collation'], "latin1") !== false)
				{					
					$sql .= "ALTER TABLE `{$item['Tables_in_openfire']}` CHANGE {$col['Field']} {$col['Field']} {$col['Type']} CHARACTER SET utf8";
					$sql .= $col['Null'] == 'NO' ? " NOT NULL" : ' DEFAULT NULL';
					$item['Comment'] && $sql .= " COMMENT '{$item['Comment']}'";
					$sql .= ";\n";
				}
			}			
			$n++;
			// echo "alter table {$item['Tables_in_openfire']} character set utf8;\n";
		}
		echo $sql;
	}
	*/	
}