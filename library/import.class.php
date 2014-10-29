<?php
class Import_Api extends Api
{
	public $app = '';
	public $act = '';

	public function index()
	{		
		$area = db()->fetchAll('select * from thinksns_3_0.ts_area');
		foreach($area as $key => $item){
			$data = array(
				'id' => $item['area_id'],
				'title' => $item['title'],
				'pid' => $item['pid'],
				'sort' => $item['sort']
			);
			db()->insert();
		}	 
	}

}