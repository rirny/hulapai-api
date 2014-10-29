<?php
/*
 * 排行榜
*/
class Rank_Api extends Api
{

	public function __construct(){
		$this->refresh = Http::post('refresh', 'trim', 0);
	}

	// 热门搜索
	public function index()
	{
		$_page = Http::post('page', 'int', 1);
		$_Teacher = load_model('teacher', Null, true)->where('status', 0);
		$page = $_Teacher->limit($perpage, $_page)->Page();
		$perpage = 10;
		$result = $_Teacher->field('user id,flower')->Order('flower', 'Desc')->limit($perpage, $page)->Result();

		$ids = array_column($result, 'id');
		$userRes = load_model('user', Null, true)->where('id,in', $ids)->Result();
		$schoolRes = db()->fetchAll("select s.id,s.name,r.teacher,s.avatar from t_school_teacher r left join t_school s on r.school=s.id where r.teacher in(" . join(",", $ids) . ")");
		$users = $schools = Array();
		foreach($userRes as $user)
		{
			$users[$user['id']] = array(
				'id' => $user['id'],
				'name' => $user['firstname'].$user['lastname'],
				'avatar' => $user['avatar']
			);
		}
		foreach($schoolRes as $school)
		{
			$schools[$school['teacher']] = array(
				'id' => $school['id'],
				'name' => $school['name'],
				'avatar' => $school['avatar']
			);
		}

		$position = ($_page-1) * $perpage;
		foreach($result as &$item)
		{
			$position++;
			$item = array_merge($item, $users[$item['id']]);
			$item['school'] = $schools[$item['id']];
			$item['position'] = $position;
		}
		Out(1, 'success', $result);
	}
	
	public function my()
	{
		parent::_init();
		$my = load_model('teacher', Null, true)->field('user id, flower')->where('user', $this->uid)->where('status', 0)->Row();
		$user = load_model('user', Null, true)->field('id,firstname,lastname,avatar')->where('id', $this->uid)->Row();
		$user['name'] = $user['firstname'] . $user['lastname'];
		unset($user['firstname'], $user['lastname']);
		$user = array_merge($user, $my);
		if(!$my) throw new Exception('非法访问');
		$count = load_model('teacher', Null, true)->clear()->where('status', 0)->Count();
		$order = load_model('teacher', Null, true)->clear()->where('flower,>', $my['flower'], true)->where('status', 0)->Count();
		$order++;
		$distance = 0;
		if($order > 1)
		{
			$prev = load_model('teacher', Null, true)->clear()->where('flower,>', $my['flower'], true)->where('status', 0)->Order('flower', 'Asc')->Row();
			$distance = $prev['flower'] - $my['flower'];
		}		
		$user['count'] = $count;
		$user['order'] = $order;
		$user['distance'] = $distance;
		Out(1, 'success', $user);
	}
}