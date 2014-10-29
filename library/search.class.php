<?php
class Search
{
	private $_keyword = '';
	private $_province = 0;
	private $_city = 0;
	private $_area = 0;
	private $_course = 0;
	private $_sub_course = 0;

	private $_distance = 0;
	private $_lng = 0;
	private $_lat = 0;
	private $_result = Null;
	private $_order = array('recommend' => 1);

	private $_page = 1;
	private $_perpage = 20;

	public $page = array();
	
	private $_exprie = 300;
	private $_query = array();
	private $_cache = false;

	public function __construct(){		
		$this->cache = cache();
	}

	public function fetch()
	{
		$this->_query = $query = $this->query();		
		if($query === false)
		{			
			$queryStr = '1=0'; //'recommend=1';
			$query = array($queryStr);
			$this->_found = false;
		}		
		$queryStr = join(" And ", $query);		
		
		if($this->_cache)
		{
			$cache_key = $this->_cache_key($query, $this->_order);
			$this->_result = $this->cache->get($cache_key);
		}

		$order_key = key($this->_order);
		$order_value = current($this->_order);	
		$filed = 'id,`name`,`avatar`,`code`,`comments` comment_count,views,`recommend`,web,description,lng,lat';
		if($this->_result === false || $this->_cache === false)
		{
			if(key($this->_order) != 'distance') // 单序
			{
				$order = $order_key . " " . ($order_value == 1 ? 'Desc' : 'Asc');
			}			
			$this->_result = load_model('school')->getAll($queryStr, '', (isset($order) ? $order : 'recommend desc'), false, false, $filed);
			if(empty($this->_result)) // 没有数据时取推荐的机构
			{				
				$this->_query = false;
				// $this->_result = load_model('school')->getAll('recommend=1', '', (isset($order) ? $order : 'recommend desc'), false, false, $filed); // 推荐机构 // 推荐方式
			}		
			if(!empty($this->_result))
			{
				$this->sorts();
				$this->_cache && $this->cache->set($cache_key, $this->_result, $this->_exprie);
			}			
		}		
		return $this;
	}

	public function result()
	{	

		$this->page = array(
			'page' => $this->_page,
			'total' => $this->count(), 
			'size' => $this->_perpage,
			'found' => 0
		);		
		$this->page['pages'] = ceil($this->page['total'] / $this->_perpage);
		$result = array();
		if($this->_page > $this->page['pages'])
		{			
			return $result;
		}
		if($this->count() > $this->_perpage)
		{			
			$result = array_slice($this->_result, ($this->_page-1) * $this->_perpage, $this->_perpage);
		}else if($this->count() > 0){
			$result =  $this->_result;
		}
		if(!empty($result) && $this->_query !== false)
		{
			 $this->page['found'] = $this->count();
		}
		return array_values($result);
	}

	public function count()
	{
		return count($this->_result);
	}

	public function keyword($keyword){
		$this->_keyword = $keyword;
		return $this;	
	}

	public function cache($enable = false){
		$this->_cache = $enable ? true : false;
		return $this;
	}

	public function order($key, $value)
	{		
		if(in_array($key, array('recommend', 'views', 'comments', 'distance')))
		{			
			$this->_order = array(
				$key => $value ? 1 : 0
			);
			// "`$key` " . ($value == 1 ? 'Desc' : 'Asc');
		}		
		return $this;		
	}
	// 参数处理
	// 区域
	public function area($province=0, $city=0, $area=0)
	{
		strLen($province) == 6 && $province > 0 && $this->_province = $province;
		strLen($city) == 6 && $city > 0 && $this->_city = $city;
		strLen($area) == 6 && $area > 0 && $this->_area = $area;		
		return $this;
	}
	// 科目
	public function course($course)
	{		
		if(strpos($course, ',') !== false)
		{
			list($this->_course, $this->_sub_course) = explode(",", $course);			
		}else if($course)
		{
			$this->_course = intVal($course);
		}
		// is_numeric($course) && $course > 0 && $this->_course = $course;
		return $this;
	}
	// 距离
	public function distance($distance, $lng, $lat)
	{	
		intVal($distance) >0 && $this->_distance = $distance;
		intVal($lng) >0 && $this->_lng = $lng;
		intVal($lat) >0 && $this->_lat = $lat;
		return $this;
	}

	public function paginator($page=1, $perpage=20)
	{
		$this->_page = is_numeric($page) && $page > 0 ? $page : 1;
		intVal($perpage)>0 && $this->_perpage = intVal($perpage);
		return $this;
	}
	

	public function location($lng, $lat)
	{
		$this->_lng = $lng;
		$this->_lat = $lat;
		return $this;
	}

	public function sorts()
	{
		if(!$this->_lng) return ;
		$result = $this->_result;		
		$point = array($this->_lng, $this->_lat);
		array_walk($result, function(&$v) use($point){		
			import('map');
			if(intval($v['lng']) > 0)
			{
				$v['distance'] = ceil(Map::getLongDistance($point, array($v['lng'], $v['lat'])));
			}else{				
				$v['distance'] = 99999999;
			}			
			//unset($v['lng'], $v['lat']);
		});
		$this->_result = array_sort($result, 'distance', SORT_ASC);

	}

	// 结果cache
	private function _cache_key(Array $query, Array $order)
	{
		if(empty($query) && empty($order)) return md5('search');
		return md5(json_encode(array(
			'query' => $query,
			'order' => $order
		)));
	}

	public function query()
	{
		$result = Array();
		$key_search_school = array();
		$key_sql = '';
		if($this->_keyword != '')
		{
			$key_search_school = load_model('course')->getColumn("`title` like '%{$this->_keyword}%' And school>0", 'school');		
			$key_sql = "`name` like '%{$this->_keyword}%'";
			$key_sql.= " or search_field like '%{$this->_keyword}%'";
			$key_sql.= " or description like '%{$this->_keyword}%'";
			// $key_sql.= " or school in(" . join(",", $course_school) . ")";			
		}
		if($this->_area)
		{
			$result[] = "`area`={$this->_area}";
		}else if($this->_city){
			if(in_array($this->_province, array('110000', '310000', '120000', '500000', '710000', '810000', '820000')))
			{
				$result[] = "`province`={$this->_province}";
			}else{
				$result[] = "`city`={$this->_city}";
			}
		}else if($this->_province){
			$result[] = "`province`={$this->_province}";
		}
		

		// 距离搜索
		if($this->_distance && $this->_lat && $this->_lng)
		{
			load('map');
			list($lat, $lng) = $a = array_values(Map::getBaiduAround(array($this->_lng, $this->_lat), $this->_distance));			
			list($minLng,$maxLng) = array_values($lng);
			$minLng && $maxLng && $result[] = "lng>{$minLng} And lng<{$maxLng}";
			list($minLat, $maxLat) = array_values($lat);
			$minLat && $maxLat && $result[] = "lat>{$minLat} And lat<{$maxLat}";
		}

		$result[] = '`status`=0'; // 正常
		$result[] = '`valid`=1';  // 审核

		// 科目
		if($this->_course || $this->_sub_course)
		{
			//所有的子ID
			$course_school_cache_key = 'school_course' . ($this->_course ? "_" . $this->_course : '') . ($this->_sub_course ? "_" . $this->_sub_course : '');
			$school = $this->cache->get($course_school_cache_key);
			if($school === false || $this->_cache === false)
			{
				if($this->_sub_course)
				{					
					$this->_course || $this->_course = load_model('course_type')->getCloumn($_sub_course, 'pid');
					if(!$this->_course) return false;
					$school = load_model('course')->getColumn("(`type`='{$this->_sub_course}') And school>0", 'school');
				}else if($this->_course){
					$subCourse = load_model('course_type')->getColumn(array('pid' => $this->_course), 'id');
					if($subCourse)
					{
						$subCourse[] = $this->_course;
						$school = load_model('course')->getColumn(array('type,in' => $subCourse, 'school,>' => 0), 'school');
					}else{
						$school = load_model('course')->getColumn(array('type' => $this->_course, 'school,>' => 0), 'school');
					}
				}
				if(!empty($school) && $this->_cache !== false)
				{
					$this->cache->set($course_school_cache_key, $school, 86400);
				}				
			}
			
			if(!empty($school))
			{
				if($key_search_school) $key_search_school = array_intersect($key_search_school, $school); // 
				$result[] = "id in(" . join("," , $school). ")";
			}else{
				// $result[] = 'recommend=1'; // 没有数据返回推荐数据
				return false; // 没有不再查询
			}
		}
		if($key_sql)
		{
			$key_search_school && $key_sql .= " or id in(" . join(",", $key_search_school) . ")";			
			$result[] = "({$key_sql})";
		}
		return $result;
	}	

	public function found()
	{
		return $this->_found;
	}
}

