<?php
class User_model Extends Model
{
	protected $_table = 't_user';
	protected $_key = 'id';
	
	public $object_name = 'user';
	public $object = Null;

	// 特殊处理
	protected $format_columns = array(	
		'id' => '_',		
		'setting' => 'json'		
	);
	// 不用的字段
	protected $unUses = array('weixin', 'source', 'last_login_time', 'last_login_ip', 'login_times', 'qq', 'password');
	
	public function __construct(){
		parent::__construct();
	}

	public function login($user, $token)
	{
		// 设置session	
		if(Http::get_session(SESS_UID))
		{
			$this->logout();
		}
		http::set_session(SESS_UID, $user['id']);
		http::set_session(SESS_ACCOUNT, $user['account']);
		http::set_session(SESS_NAME, $user['nickname']);
		http::set_session(SESS_HULAID, $user['hulaid']);		
		$times = $user['login_times'] + 1;
		// 信息更新
		$data = array(
			'last_login_time' => time(),
			'last_login_ip' => Http::ip(),
			'token' => $token,
			'status'=> 1,
			'agent' => Http::getSource(),
			'login_times' => $times,
		);
		if($times == 1)
		{
			$this->welcome($user['id'], $user['account']);
		}
		$this->update($data, array('id' => $user['id']));
		$token && $this->update(array('token' => ''), array('token' => $token, 'id,!=' => $user['id']));
		
		// 登录日志
		$redis = redis(3);
		$redis()->zAdd('login_' . date('Ym'), time(), json_encode(array_merge(Http::agent(), array(
			'channel' => Http::get_session('channel'),
			'ip' => Http::ip(), 
			'user' => $user['id'], 
			'type' => 'user', 
			'time' => date('Y-m-d H:i')
		)), JSON_FORCE_OBJECT));

		// db()->update($this->_table, $data, 'id=' . $user['id']);
		// $token && db()->update($this->_table, array('token' => ''), "`token`='". $token . "' And id <>'" .$user['id'] . "'"); // 登录时其他此设备上其他账号解除绑定
	}
	
	public function logout($uid)
	{
		if(!$uid) return false;
		db()->update($this->_table, array('status' => 0, 'token' => ''), 'id=' . $uid); // 清空当前账号的token
		http::delete_session(SESS_UID, SESS_ACCOUNT, SESS_NAME, SESS_HULAID, 'device', 'first');		
		hlp_session_start()->destroy();		
		session_destroy();
	}
	
	// 验证码
	public function verify($mobile, $code, $type=0)
	{		
		$res = db()->fetchRow("select * from t_verify_code where `type`='{$type}' And `code`='{$code}' order by send_time Desc");
		if(!$res) return false;		
		if(0 == $res['deadline']) return true;         
		if(time() > $res['deadline']) return false;	
		if($res['mobile'] == $mobile) 
		{
			$this->verify_delete($mobile, $type, $code);
			return true;
		}
		return false;
	}

    public function verify_delete($mobile, $type=0, $code='')
    {
		// 删除一类
		$where = "mobile ='{$mobile}' And deadline>0 And `type` ='{$type}'";
		$code && $where .= " and code='{$code}'";		
        return db()->delete('t_verify_code', $where);
    }

	// 注册
	public function register($user, $code='')
	{		
		db()->begin();
		try{
			// $id = $this->sync_sns($user);			
			$id = $this->insert($user);			
			if(!$id) throw new Exception('注册失败');
			$create_time = datetime();	
			$att = compact('id', 'create_time', $user);			
			$user = array_merge($user, $att);
			$hulaid = $this->hulaid_create($id);			
			$this->update(array('hulaid' => $hulaid), $id);
			$this->login($user, $user['token']);
			$code && $this->verify_delete($user['account'], 0); // 清除用户的注册验证码！
			// $this->welcome($id, $user['account']);
			// 登录日志
			$redis = redis(4);
			$redis()->zAdd('register_' . date('Ym'), time(), json_encode(array_merge(Http::agent(), array(
				'channel' => Http::get_session('channel'),
				'ip' => Http::ip(), 
				'user' => $id, 
				'type' => 'user', 
				'time' => date('Y-m-d H:i')
			)), JSON_FORCE_OBJECT));

			db()->commit();
			return $id;
		}catch(Exception $e)
		{
			db()->rollback();
			return false;
		}
	}

	private function welcome($id, $account)
	{
		if(!$id || !$account) return false;
		// 发送欢迎通知
		$message = Config::get('welcome', 'notice');
		$message = str_replace('{user}', substr($account, 0, 3) . "****" . substr($account, -4), $message);
		$messageId = load_model('message')->insert(array(
			// 'creator'=> 2,
			'from' => 2,
			'type'=> 0,
			'to' => $id,		
			'content' => $message,
			'create_time'=>time()
		));
		if(!$messageId) return false;
		return push('db')->add('H_PUSH', $t =array(
			'app' => 'notify',	'act' => 'add',	'from' => 2, 'to' => $id, 'type' => 2, 'ext' => array('messageId' => $messageId), 'message' => $message
		));
	}

	private function sync_sns($user)
	{
		$sns = array(
			'login' => $user['account'],
			'password' => md5($user['password'].$user['login_salt']),
			'uname' => $user['nickname'],	
			'sex' => $user['gender'],
			'login_salt' => $user['login_salt'],
			'is_audit' => 1,
			'is_active'=> 1,
			'ctime' => time(),
			'reg_ip' => Http::ip()
		);
		$db = Config::get('database', 'sns', null, null);		
		return db()->insert($db . '.ts_user', $sns);
	}

	public function hulaid_create($id)
	{
		return 'h_' . sprintf("%u", crc32($id));
	}	
	
	
	/**
	 * 获取用户基本信息
	 */
	public function getBaseUserById($id){
		$user = db()->fetchRow("SELECT id,nickname,firstname,lastname,teacher,gender,avatar,hulaid FROM $this->_table WHERE id = $id");
		return $user;
	}
	
	/**
	 * 获取用户基本信息
	 */
	public function getBaseUsers($ids){
		$users = db()->fetchAll("SELECT id as uid,nickname,avatar,hulaid,from_unixtime(last_login_time,'%Y-%m-%d %H:%i:%s') as last_login_time,gender,province,city,area,sign FROM $this->_table WHERE id IN($ids)");
		return $users;
	}
	
	/**
	 * 获取用户基本信息
	 * accounts 手机号码,','分隔
	 * type 0：所有 1：老师 2：学生
	 */
	public function getBaseUsersByAccounts($accounts,$type = 0)
	{
		/*
		$sqlTeacher = "SELECT DISTINCT(USER) FROM t_teacher";
		$sqlStudent = "SELECT DISTINCT(USER) FROM t_user_student";
		$sql = "SELECT a.id as user,a.nickname,a.avatar";
		$leftJoin = "";
		if($type == 1){
			$sql .= ",b.user as is_teacher";
			$leftJoin .= " LEFT JOIN ($sqlTeacher) AS b ON a.id=b.user";
		}elseif($type == 2){
			$sql .= ",c.user as is_student";
			$leftJoin .= " LEFT JOIN ($sqlStudent) AS c ON a.id=c.user";
		}else{
			$sql .= ",b.user as is_teacher,c.user as is_student";
			$leftJoin .= " LEFT JOIN ($sqlTeacher) AS b ON a.id=b.user LEFT JOIN ($sqlStudent) AS c ON a.id=c.user";
		}
		$sql .= " FROM t_user AS a".$leftJoin." WHERE a.account IN($accounts)";
		*/
		$users = db()->fetchAll("SELECT a.id as user,a.account,a.nickname,a.avatar,a.hulaid,b.user as is_student,c.user as is_teacher FROM t_user AS a LEFT JOIN (SELECT DISTINCT(USER) FROM t_user_student) AS b ON a.id=b.user LEFT JOIN (SELECT DISTINCT(USER) FROM t_teacher) AS c ON a.id = c.user WHERE a.account IN($accounts)");
		if($users){
			foreach($users as $key=>$user){
				$users[$key]['is_teacher'] = $user['is_teacher'] ? 1 : 0;
				$users[$key]['is_student'] = $user['is_student'] ? 1 : 0;
				if($type == 1 && !$users[$key]['is_teacher']){
					unset($users[$key]);
				}
				if($type == 2 && !$users[$key]['is_student']){
					unset($users[$key]);
				}
			}
		}
		return $users;
	}
}
