<?php
/*
 * 用户模拟
 * 生成 100 - 200 号段的用户
*/
set_time_limit(0);
header("Content-type: text/html; charset=utf-8");
header("cache-control:no-cache,must-revalidate");
date_default_timezone_set('Asia/Shanghai');
define('ROOT_PATH', getcwd());
define('SYS', dirname(ROOT_PATH));
define('LIB', SYS . "/library");
define('MODEL', SYS . "/model");
define('ENTITY', SYS . "/entity");
define('CONF', ROOT_PATH . "/conf");
define('LOG_PATH', ROOT_PATH . "/logs");
define('APP_PATH', ROOT_PATH . "/app");

require(SYS.'/comm/comm.php');
define('ROOT', 'http://' . $_SERVER['SERVER_NAME']);

import('config');
$debug = Config::get('debug', 'system', null, false);

if ($debug)
{
	error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
	ini_set('display_errors', 1);
}
else
{
	error_reporting(0);
	ini_set('display_errors', 0);
}

import('http');
$tm = time();
$date = date('Y-m-d H:i:s');


// 注册用户
// ID = 100 - 199
$userData = array(
	'id' => 0,
	'account' => '14410000', // + UID
	'firstname' => '',
	'lastname' => '',
	'email' => '',
	'nickname' => '',
	'lastname' => '',
	'password' => '',
	'gender' => 0,
	'hulaid' => 'hlp', // + UID
	'avatar' => 0,
	//'province' => '',
	//'city' => '',
	//'area' => '',
	'source' => 'test',
	'create_time' => $tm,
	'last_login_time' => $tm,
	'login_salt' => '00000',
	//'login_times' => '',
	'setting' => '',
	'teacher' => 1  
);

$users = load_model('user')->getColumn(array('status' => 0), 'id');
$teachers = load_model('teacher')->getColumn(array('status' => 0), 'user');
$students = load_model('student')->getColumn(array('status' => 0), 'id');

$relations = array(1,2,3,4);
//ob_start();
$names = range('A', 'Z');
$nums = range(0,9);
//db()->begin();
for($i=100; $i<200; $i++)
{
	$item = array(
		'id' => $i,
		'password' => md5(md5('123123') . $userData['login_salt']),
		'firstname'=> $names[array_rand($names, 1)],
		'lastname' => $nums[array_rand($nums, 1)],
		'hulaid' => $userData['hulaid'] . "_" . $i,
		'account' => $userData['account'] . str_pad($i, 3, 0, STR_PAD_LEFT)
	);
	$item['setting'] = json_encode(array(
		"hulaid" => 1,
		"friend_verify" => 1,
		"notice" => array(
			"method" => 0,
			"types" => "1,2,3,4,5"
		)
	));	
	echo $i . "\t" . $item['account'] . "\t" . $item['firstname'] . "\t" . $item['lastname'] . "\thulaid:" . $item['hulaid']; 
	// 注册用户
	load_model('user')->insert(array_merge($userData, $item));
	// 生成老师档案
	$teacher = array(
		'user' => $i,	
		'create_time' => $tm
	);
	$teaherId = load_model('teacher')->insert($teacher);

	// 生成学生档案
	$student = array(
		'name' => 'S_' . $i. "_" . $names[array_rand($names)],
		'nickname' => '', 
		'gender' => rand(1,2), 
		// 'birthday', 
		'create_time' => $tm, 
		// 'tag', 
		'operator' => $i,
		'creator' => $i
	);
	$studentId = load_model('student')->insert($student);
	echo "\tstudent:\t" . $studentId . "\t" . $student['name'];
	
	$rs = rand(0,3);
	$relation = load_model('user_student')->insert(array( // 建立联系
		'user' => $i,
		'student' => $studentId,
		'relation'=> $relations[$rs],
		'create_time' => $tm,
		'creator' => $i
	));

	// 随机一个学生授权
	$randStudent = array_rand($students, 1);
	if(!empty($randStudent[0]))
	{	
		$tmpRelation = $relations;
		unset($tmpRelation[$rs]);
		$r = array_rand($tmpRelation, 1);
		$relationRand = load_model('user_student')->insert(array( // 建立联系
			'user' => $i,
			'student' => $randStudent[0],
			'relation'=> $r,
			'create_time' => $tm,
			//'creator' => $i
		));
	}

	// 建立师生关系
	for($j=100; $j<200 && $j!= $i; $j++)
	{
		load_model('teacher_student')->add($j, $studentId);
		follow($j, $i);
		// 随机多个学生建立师生关系		
	}	

	$randStudents = array_rand($students, 2);		
	if(!empty($randStudents))
	{
		foreach($randStudents as $rStudent)
		{
			load_model('teacher_student')->add($i, $rStudent);
		}
	}	
	echo "\n";
}

//ob_flush();

// 家长关注老师
function follow($teacher, $parent)
{
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
		
		/*
		// 粉丝+
		$push = array(
			'app' => 'feed_follow',	'act' => 'add',	'from' => $parent,	'type' => 1, 'to' => $teacher,
			'character' => 'user', 'ext' => load_model('user')->getRow($parent, true, 'id,nickname,firstname,lastname,hulaid,avatar')
		);
		// $res = load_model('student')->push($student, $push);
		push('db')->add('H_PUSH', $push);
		*/
	}
	return true;
}
