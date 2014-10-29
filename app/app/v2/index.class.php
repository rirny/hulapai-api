<?php
class Index_Api extends Api
{

	public function __construct(){
		// parent::_init();
		$cache = Http::post('tm', 'int', 0);
		$this->cache = $cache ? false : true;
	}
	
	private $_exprie = 1800;

	// 热门搜索
	public function hot()
	{
		$result = load_model('course_type')->getAll('', 4, 'hot Desc', true, true);
		Out(1, '', $result);
	}	
	
	public function search()
	{
		$province = Http::post('province', 'trim', 0); // 省
		$city = Http::post('city', 'trim', 0);		// 市
		$area = Http::post('area', 'trim', 0);		// 区
		
		$course = Http::post('course', 'trim', '');	// 科目
		$distance = Http::post('distance', 'trim', 0);	// 距离
		$keyword = Http::post('keyword', 'trim', '');	// 关键字
		$lat = Http::post('lat', 'trim', '');		// 纬度
		$lng = Http::post('lng', 'trim', '');		// 经度

		$page = Http::post('page', 'int', 1);		// 页
		$per = Http::post('per', 'int', 20);		// 每页
		intval($per)< 1 || $per = 20;
		$order = Http::post('order');		// 排序
		$searchEngine = load('search')
			->cache($this->cache)
			->keyword($keyword)
			->area($province, $city, $area)
			->distance($distance, $lng, $lat)
			->course($course)
			->location($lng, $lat)
			->fetch()
			->paginator($page, $per);
		$result = $searchEngine->result();		
		Out(1, '', array('schools' => $result, 'page' => $searchEngine->page));
	}
	
	// 机构详情
	public function info()
	{
		$id = Http::post('school', 'int', 0); // 机构ID
		if(!$id) throw new Exception('机构不存在！');		
		// 机构资料
		$cache_key = 'school' . $id;
		$result = cache()->get($cache_key);
		if($result === false || $this->_cache === false)
		{
			$result = load_model('school')->getRow($id, false, 'id,`code`,`name`,address,web,phone,phone2,views,lng,lat,comments comment_count,description');
			$this->_cache && cache()->set('school_' . $id);
		}

		$result['views']++;
		load_model('school')->update(array('views' => $result['views']), $id);

		if(!$result) throw new Exception('机构不存在！');
		// 资讯
		$cache_key = 'school' . $id . '_news';
		$news = cache()->get($cache_key);
		if($news === false || $this->_cache === false)
		{
			$news = load_model('news')->getRow(array('school' => $id, 'status' => 1), false, 'id,description', 'modify_time desc,`status` desc');
			$this->_cache && cache()->set($cache_key, $news, $this->_expire);
		}
		// 评论
		$cache_key = 'school' . $id . '_comment_count';
		$comment_count = cache()->get($cache_key);
		if($comment_count === false || $this->_cache === false)
		{
			$comment_count = load_model('comment')->getCount(array('school' => $id, 'pid' => 0, 'character' => 'student', 'event' => 0, 'teacher' => 0), 'count(*)');
			$this->_cache && cache()->set($cache_key, $comment_count, $this->_expire);
		}
		$cache_key = 'school' . $id . '_comment';
		$comment = cache()->get($cache_key);
		
		if($comment_count >0 && $comment === false || $this->_cache === false)
		{
			$comment = load_model('comment')->getRow(array('school' => $id, 'pid' => 0, 'character' => 'student', 'event' => 0, 'teacher' => 0), false, 'id,content,creator,student','create_time Desc');
			$comment['creator'] = load_model('user')->getRow($comment['creator'], false, 'id,concat(firstname,lastname) name,account');			
			$comment['is_union']= $this->is_unoin($id, $comment['creator']['id'], 'school');

			// 最新评论者的课时数
			$comment['class_time'] = 0;
			if($comment['student'])
			{
				$timeSql = "select sum(e.class_time) from t_course_student r left join t_event e on e.id=r.`event`";
				$timeSql.= " where e.school={$id} And r.student={$comment['student']} And r.`status`=0";
				$timeSql.= " And e.end_date<'" . date('Y-m-d H:i') . "'";
				$comment['class_time'] = db()->fetchOne($timeSql);
			}
			$this->_cache && cache()->set($cache_key, $comment, $this->_expire);
		}
		$cache_key = 'school' . $id . '_photo_cover'; // 第一张
		$photo = cache()->get($cache_key);
		if($photo === false || $this->cache === false)
		{			
			$attach = load_model('attach')->getRow(array('app_name' => 'photo', 'school' => $id));
			if($attach)
			{
				$photo = load_model('attach')->getAttachInfo($attach);
				$photo['count'] = load_model('attach')->getCount(array('app_name' => 'photo', 'school' => $id), 'count("attach_id")');
				$this->_cache && cache()->set($cache_key, $photo, 1800);
			}
		}
		$result['comment_count'] = $comment_count;
		$result['comment'] = $comment;
		$result['news'] = $news;
		$result['photo'] = $photo;	
		Out(1, '', $result);
	}
	
	// photo
	public function photo()
	{
		$id = Http::post('school', 'int', 0); // 机构ID
		if(!$id) throw new Exception('机构不存在！');
		$cache_key = 'school' . $id . '_photo';
		$result = cache()->get($cache_key);		
		if($result === false || $this->cache === false)
		{			
			$_Attach = load_model('attach');
			$result = $_Attach->getAll(array('app_name' => 'photo', 'school' => $id), 10, 'ctime desc', false, false, 'attach_id,save_path,save_name,title,ctime');
			foreach($result as $key => &$item)
			{
				$attach = $_Attach->getAttachInfo($item);
				$item = array_merge($item, $attach);
				unset($item['save_path'], $item['save_name']);
			}
			$this->_cache && cache()->set($cache_key, $result, $this->_exprie);
		}
		Out(1, 'success', $result);
	}
	
	// 电话记录
	public function phone()
	{
		$school = Http::post('school', 'int', 0);
		$user = Http::post('user', 'int', 0);
		$from = Http::post('from', 'trim', '');
		$phone = Http::post('phone', 'trim', '');
		$create_time = time();
		$id = load_model('phone_record')->insert(compact('school', 'user', 'from', 'phone', 'create_time'));
		if(!$id) throw new Exception('记录失败');
		Out(1, 'success');
	}

	public function agent()
	{
		Out(1, 'su', $_SERVER);
	}

	public function is_unoin($id, $user=0, $character='school')
	{
		$students = load_model('user_student')->getColumn(array('user' => $user), 'student');		
		if(empty($students)) return 0;
		if($character == 'teacher')
		{
			$res = load_model('teacher_student')->getRow(array('student,in' => $students, 'teacher' => $id));
		}else{
			$res = load_model('school_student')->getRow(array('student,in' => $students, 'school' => $id));
		}
		if($res) return 1;
		return 0;
	}
}