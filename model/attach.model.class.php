<?php
class Attach_model Extends Model
{
	protected $_table = 'ts_attach';
	protected $_pk = 'attach_id';

	public $object = Null;

	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 批量获取附件信息
	 */
	public function getAttachs($attach_ids){
		return db()->fetchAll("select attach_id,uid,name as attach_name,size,extension,save_path,save_name from $this->_table where attach_id in ($attach_ids)");
	}
	
	/**
	 * 获取单个附件信息
	 */
	public function getAttach($attach_id,$uid){
		return db()->fetchRow("select attach_id,uid,name as attach_name,size,extension,save_path,save_name  from $this->_table where attach_id='$attach_id' AND uid='$uid' limit 1");
	}


	public function getAttachInfo($attach)
	{
		$root = Config::get('path', 'upload');
		$file = substr($attach['save_name'],0,-4); 		
		$attach_url_size = getimagesize($root .'/' . $attach['save_path'].$attach['save_name']);
		$attach_small_size = getimagesize($root .'/' . $attach['save_path']. $file . "_small.jpg");
		$attach_middle_size = getimagesize($root .'/' . $attach['save_path']. $file . "_middle.jpg");            
		return array(
			'attach_id' => $attach['attach_id'],
			'attach_url'=> $attach['save_path'].$attach['save_name'],
			'attach_url_size'=>$attach_url_size[0].'_'.$attach_url_size[1],
			'attach_small' => $attach['save_path']. $file . "_small.jpg",
			'attach_small_size'=>$attach_small_size[0].'_'.$attach_small_size[1],
			'attach_middle'=> $attach['save_path']. $file . "_middle.jpg",
			'attach_middle_size'=>$attach_middle_size[0].'_'.$attach_middle_size[1],
			'domain' => 'HOST_IMAGE'
		);
	}
}