<?php
class Index_Api extends Api
{

	public function __construct(){
		// parent::_init();
		$cache = Http::post('tm', 'int', 0);
		$this->cache = $cache ? false : true;
		$this->refresh = Http::post('refresh', 'trim', 0);
	}

	// 热门搜索
	public function hot()
	{
		$result = load_model('course_type')->getAll('', 8, 'hot Desc', true, true);
		Out(1, '', $result);
	}	
}