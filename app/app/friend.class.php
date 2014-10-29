<?php

class Friend_Api extends Api
{
	public function __construct(){
		parent::_init();
	}
	
	/** 列表
	 * @user
	*/
	public function getList()
	{
		$param = http::query();
		$result = load_model('friend')->getFriends($this->uid);		
		Out(1, '', $result);
	}	
	
	/**
	 * 删除好友
	 */
	public function delete()
	{
		$friend = Http::post('friend', 'int', 0);
		if(!$friend) throw new Exception('参数错误！');
		$_Friend = load_model('friend');
		db()->begin();
		try{
			$res = $_Friend->delete(array('user' => $this->uid, 'friend' => $friend), true);
			if(!$res) throw new Exception('失败！');
			$res = $_Friend->delete(array('user' => $friend, 'friend' => $this->uid), true);
			if(!$res) throw new Exception('失败！');
			db()->commit();
		}catch(Exception $e)
		{
			Out(0, $e->getMessage());
		}
		Out(1, '成功！');
	}
}