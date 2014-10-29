<?php
/**
 * msgtype
 * SSdesc  通讯录
 * SSend
 */
class Addressbook_Api extends Api
{

	public function __construct(){
		parent::_init();		
	}	

	/**
	 * structdef.xml
	 * 	SSaction     index
	 *  SSdesc       通讯录 
	 *  SSpargam 
	 * 		mobilePhones   varchar   手机号码,','分隔
	 * 		type  int   类型(0：所有1：老师2：学生)
	 * 		applytype  int   类型(5好友申请,8授权)
	 * 		student  int   学生id
	 *  SSreturn 
	 * 		addressbook			array   通讯录		addressbook
	 * SSreturn_array_addressbook
	 * 		user		int   	 用户
	 * 		nickname		int		昵称
	 * 		avatar	int		头像最后更新时间
	 * 		hulaid	varchar		呼啦号
	 * 		is_student		int		是否学生
	 * 		is_teacher      int   是否老师
	 * 		applyvalue		int  0无关系 1有关系 2已申请
	 * SSreturn_array_end_addressbook
	 * SSend
	 */
	public function index()
	{
		$mobilePhones = Http::post('mobilePhones', 'trim', '');
		$type = Http::post('type', 'int', 0);
		$applytype = Http::post('applytype', 'int', 0);
		$student = Http::post('student', 'int', 0);
		if(!$applytype || !in_array($applytype,array(5,8))) out(0, '参数错误 applytype');
		if($applytype == 8 && !$student) out(0, '参数错误 student');
		if(!$mobilePhones)
		{
			out(0, '参数错误 mobilePhones');
		}
		$mobilePhones = array_unique(explode('|',$mobilePhones));
		$filterFunc = create_function('$v', 'return  is_numeric($v);');
		$mobilePhones = array_filter($mobilePhones, $filterFunc);
		if(!$mobilePhones)
		{
			out(0, '参数错误 mobilePhones');
		}
		$mobilePhones = implode(',',$mobilePhones);
		
		$addressbook = load_model('user')->getBaseUsersByAccounts($mobilePhones,$type);
		if($addressbook){
			switch($applytype){
				
				case 5: //好友申请
					$_Friend = load_model('friend');
					$_Apply = load_model('apply');
					foreach($addressbook as &$_addressbook){
						$_addressbook['applytype'] = $applytype;
						$_addressbook['applyvalue'] = 0;
						if($_Friend->is_friend($this->uid, $_addressbook['user'])) $_addressbook['applyvalue'] = 1;
						//是否已申请
						if($_Apply->getRow(array('from'=>$this->uid,'to'=>$_addressbook['user'],'type'=>$applytype,'status'=>0))) $_addressbook['applyvalue'] = 2;
					}
					break;
				case 8: //授权
					$_User_student= load_model('user_student');
					$_Apply = load_model('apply');
					foreach($addressbook as &$_addressbook){
						$_addressbook['applytype'] = $applytype;
						$_addressbook['applyvalue'] = 0;
						if($_User_student->get_relation($_addressbook['user'], $student) > 0) $_addressbook['applyvalue'] = 1;
						//是否已申请
						if($_Apply->getRow(array('from'=>$this->uid,'to'=>$_addressbook['user'],'student'=>$student,'type'=>$applytype,'status'=>0))) $_addressbook['applyvalue'] = 2;
					}		
					break;
				default:
					out(0, '获取失败');
					break;
			}
			
		}
		out(1, '',array('addressbook'=>$addressbook));
	}
}