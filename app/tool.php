<?php
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

define('SESS_UID', 'H_uid');
define('SESS_ACCOUNT', 'H_account');
define('SESS_NAME', 'H_name');
define('SESS_HULAID', 'H_hulaid');

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
//session_start();
hlp_session_start();

$uid = Http::get_session(SESS_UID);

if($_GET['m'] == 'logout')
{	
	if($uid) load_model('user')->logout($uid);
	Header('HTTP/1.0 401 Unauthorized');
	Header("Location:" . ROOT . "/tool.php");	
	exit;
}
$auth = false;
if(!$uid && !$auth)
{	
	$username = !empty($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '';
	$password = !empty($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';		
	
	$auth = User_login($username, $password);

	if(!$auth)
	{
		Header("WWW-Authenticate: Basic realm='User Login'");
		// Header('HTTP/1.0 401 Unauthorized');    
		exit;
	}else{
		Header("Location:" . ROOT . "/tool.php");
		exit;
	}
}


?>
<head><title>HULAPAI接口测试</title>
<style>
	div,table{font-size:12px;}
	ul{list-style:none; padding:0px; margin:0px;}
	li{list-style:none; padding:8px 8px; border-bottom:1px solid #CCC}
	label{font-weight:bold; padding:0px; width:60px; display:inline-block; text-align:left}
	#param li{}
	table{border:1px solid #CCC; margin:20px 0px;}
	th{background:#F1F1F1}
	dl{padding:0px; margin:0px 4px;}
	dt,dd{padding:4px 8px; margin:0px; cursor:pointer}
	dt{font-size:14px; background:#F1F1F1; border:1px solid #ccc; WIDTH:160PX; MARGIN:4PX;}
	dd{padding-left:2em;display:none}
	select{width:100px;}
	input.l{width:300px;}
	input.m{width:200px;}
	input.s{width:100px;}
	a{color:blue; cursor:pointer}
	a:hover{text-decoration: underline}
	#add{display:inline-block; margin-left:20px; border:1px solid #CCC; padding:4px;}	
	dl.on dt{background:#fdf8f2;border:1px solid #ff6600;}
	dl.on dd{display:block;}
</style>
<script src="http://code.jquery.com/jquery-1.9.1.js"></script>
</head>
<?php
$configs = include('conf/api.ini.php');
$groups = array(
	"public" => "公共",
	"apply" => "关联",
	"user"	=> "用户",
	"teacher"	=> "老师",
	"course"	=> "授课内容",
	"event"	=> "课程",
	"student"	=> "学生",
	"grade"	=> "班级",
	"school"	=> "机构",
	"comment"	=> "点评",
	"attend"	=> "考勤",
	"blog"	=> "呼啦圈",
	"feed"	=> "微博",
	"message" => "消息\通知",
	"vote" => "问卷",
	"lbs" => "搜索",
);

$agents = array(
	'web' => '',
	'android' => 'Android4.0.3,samsung GT-I9100,359778042915379',
	'ios' => '\U547c\U5566\U6d3e 1.0 (iPhone; iPhone OS 6.1.3; zh_CN),iPhone,iPhone OS 6.1.3,29E45E5C-8819-449A-AFF9-079526724CBE'
);

$group = '';
$result = array();
$response = '';

if(isset($_GET['m']) && $_GET['m']!= 'add')
{
	$api = get_api();
	extract($api);
	if(!isset($group, $app, $act)) die('参数错误！');
	if($_GET['m'] == 'delete')
	{
		if(isset($configs[$group][$app][$act]))
		{
			unset($configs[$group][$app][$act]);
			file_put_contents('conf/api.ini.php', "<?php \nreturn ". var_export($configs, true) . ";\n?>");
		}		
	}else if($_GET['m'] == 'update')
	{		
		if(isset($configs[$group][$app][$act]))
		{			
			$param = $configs[$group][$app][$act]['param'];
			$name = $configs[$group][$app][$act]['name'];
			$description = $configs[$group][$app][$act]['description'];
		}
	}else if($_GET['m'] == 'save')
	{
		empty($group) && $group = 'public';
		empty($app) && $app = 'index';
		empty($act) && $act = 'index';		
		$configs[$group][$app][$act] = array(
			'name' => $name,
			'param' => $param,
			'description' => $description
		);
		file_put_contents('conf/api.ini.php', "<?php \nreturn ". var_export($configs, true) . ";\n?>");	
	}else if($_GET['m'] == 'post')
	{			
		$post = get_post();		
		if($post['act'] == 'login' || $post['act'] == 'register')
		{
			$post['password'] = md5($post['password']);
		}
		session_write_close();
		$ch = curl_init(); //初始化curl 
		curl_setopt($ch, CURLOPT_URL, ROOT);//设置链接 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设置是否返回信息 
		// curl_setopt($ch, CURLOPT_HTTPHEADER, $header);//设置HTTP头 
		if(isset($agents[$os]) && $agent = $agents[$os])
		{
			if($os == 'ios')
			{
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('DEVICE:iPhone,iPhone OS 6.1.3,29E45E5C-8819-449A-AFF9-079526724CBE'));//设置HTTP头 				
			}
			curl_setopt($ch, CURLOPT_USERAGENT, $agent);//设置HTTP头 
		}		
		curl_setopt($ch, CURLOPT_POST, 1);//设置为POST方式 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);//POST数据 
		curl_setopt($ch, CURLOPT_COOKIE,  session_name() ."=" . session_id());
		$response = curl_exec($ch);//接收返回信息
		if(curl_errno($ch)){//出错则显示错误信息 
			print curl_error($ch); 
		} 
		curl_close($ch); //关闭curl链接		
	}
}
?>
<script>
$(function() {	
	$("#add" ).click(function(){
		var p = $("#param li:first").clone();		
		// $(p).find("input[type='text']").val('');	
		var m = $("<li>" + $(p).html() + "</li>");
		$(m).find("input[type='text']").val('');
		$("#param").append(m);
	});
	$("#list tr:even td").css("background", "#F7F7F7");

	$("#post").click(function(){
		$("#postMan").attr('action', '?m=post');
		$("#postMan").submit();
	});

	$("#list dt:first").click();

	$('dt').click(function(){
		var dl = $(this).parent()
		var on = dl.attr('class');		
		if(on == 'on')
		{
			// $(this).parent().find('dd').hide();
			// $(this).removeClass('on');
		}else{
			dl.find('dd').show();
			dl.addClass('on');
			var siblings = dl.siblings('dl');
			$(siblings).removeClass('on');
			$(siblings).find('dd').hide();
		}
	});
});
</script>

<div style="width:800px;margin:20px auto;">
<div id="list" style="position:absolute;top:0px;left:0px;padding:0px 12px;">	
	<?foreach($groups as $g=>$gL):?>
	<dl <?if($g == $group):?>class="on"<?endif;?>>
		<dt><?=$gL?></dt>
		<?if($configs[$g]):?>
			<?foreach($configs[$g] as $key=>$apps):?>
				<?foreach($apps as $k=>$action):?>
				<dd><a href="?m=update&group=<?=$g?>&app=<?=$key?>&act=<?=$k?>"><?=$action['name']?></a><a href="?m=delete&group=<?=$g?>&app=<?=$key?>&act=<?=$k?>" style="float:right">删除</a></dd>
				<?endforeach;?>
			<?endforeach;?>
		<?endif;?>		
	</dl>
	<?endforeach;?>	
</div>
<div id="form" style="position:absolute;top:0px;left:200px;padding:12px;">	
	<div style="margin-bottom:12px;background:#F9F9F9;border:1px solid #F7F7F7;line-height:24px;">
	当前用户: <?=Http::get_session(SESS_ACCOUNT)?> 用户ID：<?=Http::get_session(SESS_UID)?> hulaid:<?=Http::get_session(SESS_HULAID)?> 名：<?=Http::get_session(SESS_NAME)?>
	<a href="/tool.php" style="float:right">新建</a> <a href="?m=logout">退出</a></div>
	<form action="?m=save" method="post" name="postMan" id="postMan">
		<div style="margin-bottom:12px;background:#F9F9F9;border:1px solid #F7F7F7;line-height:24px;">
			header : <select name="os">
			<?foreach($agents as $key=>$value):?>
			<option value="<?=$key?>" <?= $key==$os ? 'selected="selected"' : ""?>><?=$key?></option>
			<?endforeach;?>
			</select>			
		</div>	
		<ul>
			<label>分组</label>
			<select name="group">
				<?foreach($groups as $key=>$val):?>
				<option value="<?=$key?>" <?= $key== $group ? 'selected="selected"' : ''?>><?=$val?></option>
				<?endforeach;?>			
			</select>
			<li><label>name</label><input type="text" name="name" class="m" value="<?=isset($name) ? $name : ''?>"/></li>
			<li><label>APP</label><input type="text" name="app" class="m" value="<?=isset($app) ? $app : ''?>"/></li>
			<li><label>ACT</label><input type="text" name="act" class="m" value="<?=isset($act) ? $act : ''?>"/></li>
			<li>
				<fieldset>
					<legend>参数 <a id="add">添加</a></legend>
					<ul id="param">
						<?if(!empty($param)):?>
							<?foreach($param as $k=>$v):?>
								<li name="params"><input type="text" name="param[key][]" value="<?=isset($v['key']) ? $v['key'] : ''?>" class="s"/>&nbsp;
									<input type="text" name="param[value][]" value="<?=isset($v['value']) ? $v['value'] : ''?>" class="m"/>&nbsp;
									<input type="text" name="param[description][]" value="<?=isset($v['description']) ? $v['description'] : ''?>" class="l"/>&nbsp;
									<select name="param[type][]">
										<option value="text" <?=$v['type'] == 'text' ? 'selected="selected"' : ''?>>text</option>
										<option value="file" <?=$v['type'] == 'file' ? 'selected="selected"' : ''?>>file</option>
									</select>
								</li>
							<?endforeach;?>
						<?else:?>
							<li name="params"><input type="text" name="param[key][]" value="" class="s"/> 
								<input type="text" name="param[value][]" value="value" class="m"/> &nbsp;
								<input type="text" name="param[description][]" value="description" class="l"/> &nbsp;
								<select name="param[type][]"><option value="text">text</option><option value="file">file</option></select>
							</li>
						<?endif;?>
					</ul>
				</fieldset>
			</li>
			<li>
				<textarea name="description" rows="8" cols="80"><?=isset($description) ? $description : '描述'?></textarea>
			</li>
			<li><input type="submit" name="submit" id="post" value="post"/>  <input type="submit" name="submit" value="save"/></li>
		<ul>
	</form>
	<?if($_GET['m'] == 'post'):?>
		<div>
			<textarea style="border:1px solid #CCC;overflow:scoll; width:780px;padding:12px;" rows="60"><?=$response?></textarea>
		</div>
	<?elseif($_GET['m'] == 'outport'):?><!-- 导入到POSTMAN -->
		<?
			foreach($configs as $key=>$item)
			{
				$md5 =  md5(crc32($group));
				$result['id'] = $collectionId = substr($md5, 0, 8) . "-" . substr($md5, 8, 4) . "-" . substr($md5, 12, 4) . "-" . substr($md5, 16, 4) . "-" . substr($md5, 20);
				$result['name'] = $groups[$group];
				$result['timestamp'] = time() * 1000;
				$result['requests'] = array();
			}
		?>
	<?endif;?>
</div>

<?php
function User_login($username, $password)
{	
	$_User = load_model('user');	
	$user = $_User->getRow(array('account' => $username));
	$user || $user = $_User->getRow(array('hulaid' => $username));
	if(!$user) return false;		
	if($user['password'] !== md5(md5($password) . $user['login_salt'])) return false;		
	$res = $_User->login($user);	
	return $res;
}

function get_api()
{
	extract($_REQUEST);
	$params = array();
	if(!isset($group, $app, $act)) return array();
	empty($os) && $os = 'web';
	empty($agent) && $agent = isset($_SERVER['USER_AGENT']) ? $_SERVER['USER_AGENT'] : '';

	foreach($param['key'] as $key => $item)
	{
		if(!$item) continue;
		$params[] = array(
			'key' => $item,
			'value' => $param['value'][$key] == 'default' ? '' : $param['value'][$key],
			'type' => $param['type'][$key],
			'description' => $param['description'][$key] == 'description' ? '' : $param['description'][$key],
		);
	}

	return array(
		'os' => $os,			
		'group' => $group,
		'app' => $app,
		'act' => $act,
		'name'=> $name,
		'param' => $params,
		'description' => isset($description) ? $description : ''
	);
}

function get_post()
{
	$api = get_api();	
	$result = array('os' => $api['os']);
	isset($api['app']) && $api['app'] != 'index' && $result['app'] =  $api['app'];
	isset($api['act']) && $api['act'] != 'index' && $result['act'] =  $api['act'];
	if(!empty($api['param']))
	{
		foreach($api['param'] as $key=>$item)
		{
			if($item['value'] !== '')
			{
				$result[$item['key']] = $item['value'];
			}
		}
	}
	return $result;
}
?>
