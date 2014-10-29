<?php
class Version_Api extends Api
{

	public function __construct(){
		//parent::_init();		
	}	
	
	/**
	 * version  版本号
	 * type  1登录检测   2手动检查更新
	 * 
	 */
	public function index(){
		$version = Http::post('version', 'trim', '');
		if(!$version) throw new Exception('版本号不能为空！');
		$device = Http::get_device();	
		$source = 0;			
		if($device['src'] == 'ios'){
			$source = 2;
		}else if($device['src'] == 'android'){
			$source = 1;
		}
		if(!$source) throw new Exception('非法访问！');
		//版本号是否存在
		$_Version = load_model('version'); 
		$info = $_Version->getRow(array('source'=>$source,'version'=>$version));
		if(!$info){
			$latest = $_Version->getRow(array('source'=>$source,'type'=>1),false,'*','version desc');
			if(!$latest) out(1, '安装的软件版本不存在，请卸载重新下载安装！');
			out(1, '您安装的软件版本不存在，请重新安装！',$latest);
		}else{
			//比较版本号
			$isLatest = $_Version->getRow(array('source'=>$source,'version,>'=>$info['version']));
			if(!$isLatest) out(1, '您安装的软件已是最新！');
			//检查是否有版本更新
			$packages = $_Version->getAll(array('source'=>$source,'version,>'=>$info['version'],'type'=>1),'','version desc');
			if($packages){
				$level = 0;
				foreach($packages as $package){
					if($package['level'] == 1) $level = 1;
				}
				$latest = array_shift($packages);
				$latest['level'] = $level;
				out(1, '您安装的软件版本有新的更新，请更新！',$latest);
			}
			//检查是否有补丁更新
			$packets = $_Version->getAll(array('source'=>$source,'version,>'=>$info['version'],'type'=>2),'','version asc');
			out(1, '您安装的软件有新的补丁更新！', $packets);
		}
	}
}