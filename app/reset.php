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
$students = load_model('student')->getAll(array('status' => 0));
foreach($students as $student)
{
	$res = load_model('user_student')->insert(array( // 建立联系
		'user' => $student['creator'],
		'student' => $student['id'],
		'relation'=> rand(1,4),
		'create_time' => $student['create_time'],
		'creator' => $student['creator'],
	));
}
exit;
$relations = load_model('teacher_student')->getAll(array('status' => 0), '', '', false, false, 'teacher,student');
$_User_student = load_model('user_student');
foreach($relations as $item)
{
	$parents = $_User_student->getAll(array('id,>' => 0), '', '', false, false, '`user`');
	foreach($parents as $parent)
	{
		follow($item['teacher'], $parent['user']);
	}	
}

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