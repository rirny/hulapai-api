<?php
class Favorite_Api extends Api
{
	public function __construct(){
		parent::_init();
	}

	public function info()
	{
		$id = Http::post('id', 'int', 0);
		if(!$id) throw new Exception('参数错误！');
		$_Favorite = load_model('favorite');
		$result = $_Favorite->getRow($id, true);
		Out(1, '', $result);
	}

	/* 列表
	 * @user
	*/
	public function getList()
	{
		$param = http::query();
		$result = load_model('space')->getAll(array('creator' => $this->uid), 'create_time Desc', '', false, true);
		Out(1, '', $result);
	}
	
	// 写日志
	public function add()
	{
		$source = Http::post('source', 'string', '');
		if(!$source) throw new Exception('收藏内容不能为空！');
		$source = Http::post('type', 'int', 0);		
		$create_time = datetime('Y-m-d H:i:s');
		$user = $this->uid;		
		$_Favorite = load_model('favorite');
		db()->begin();
		try{
			$data = compact('source', 'user', 'create_time', 'creator', 'type');
			$id = $_Favorite->insert($data);
			switch($source)
			{
				case 1:					
				case 2:
				case 3:
				default :
					$resource = load_model('space')->getRow($source);
					if(!$resource) throw new Exception('收藏内容不存在！');
					$res = load_model('space')->increment('favorites', $source, 1); // 日志增量
					if(!$res) throw new Exception('收藏失败');
					break;					
			}			
			db()->commit();
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());
		}
		Out(1, '成功！');
	}	
	
	// 删除
	public function delete()
	{
		$id = Http::post('id', 'int', 0);
		if(!$id) throw new Exception('参数错误！');
		$_Favorite = load_model('favorite');
		$resource = $_Favorite->getRow($id);
		if(!$resource) throw new Exception('内容不存在！');
		db()->begin();
		try{			
			switch($resource['type'])
			{
				case 1:					
				case 2:
				case 3:
				default :
					$res = load_model('space')->decrement('favorites', $resource['source'], 1); // 日志增量
					if(!$res) throw new Exception('失败');
					break;					
			}
			$res = $_Favorite->delete($id);
			if(!$res) throw new Exception('删除失败');
			db()->commit();
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());
		}		
		Out(1, '成功！');
	}
}