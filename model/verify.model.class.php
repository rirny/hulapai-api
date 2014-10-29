<?php
class Verify_model Extends Model
{
	protected $_table = 't_verify_code';
	protected $_key = 'id';	

	
	public function __construct(){
		parent::__construct();
	}
	
	
	public function send($mobile, $type=0, $message='')
	{
		if(!$mobile) return -9020;
		$res = $this->getRow(array('mobile' => $mobile, 'type' => $type));
		$tm = time();
		if(empty($res) || $res['deadline'] <= $tm)
		{			
			$this->delete(array('mobile' => $mobile, 'type' => $type), true); // 全部清除
			$deadline = $tm + 30*60;
			$create_time = $tm;
			$code = str_pad(Rand(0, 999999), 6, "0", STR_PAD_LEFT);
			$send_time = '';
			$status = 0;
			$data = compact('mobile', 'type', 'code', 'create_time', 'deadline');
			$id = $this->insert($data);
		}else{
			extract($res);
		}	
		if($send_time > $tm - 30) return -2;
		if($id)
		{	
			$rs = SMS()->sendSMS(array($mobile), str_replace('{code}', $code, $message));
			if($rs == 0)
			{
				$this->update(array('status' => 1, 'send_time'=> $tm), $id); // 已发送
				return compact('id', 'mobile', 'code', 'type');
			}
		}
		return -117;	
	}


}