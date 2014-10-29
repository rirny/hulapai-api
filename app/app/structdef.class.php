<?php
class Structdef_Api extends Api {
   	public $xml;
   	public $xmlEnd;
   	public $msg_types;
   	public $msg_defs;
   	public $structs;
   	public $structsEnd;
   	public $structPacket;
   	public function __construct(){
		parent::_init();
		$this->xml = '<?xml version="1.0" encoding="utf-8"?>'."\r\n";
		$this->xml .= '<structdef>'."\r\n";
		$this->xml .= "\t".'<typedefs>'."\r\n";
		$this->xml .= "\t\t".'<typedef name="int"'."\t".'desc="数字"></typedef>'."\r\n";
		$this->xml .= "\t\t".'<typedef name="varchar"'."\t".'desc="字符串"></typedef>'."\r\n";
		$this->xml .= "\t\t".'<typedef name="date"'."\t".'desc="日期"></typedef>'."\r\n";
		$this->xml .= "\t\t".'<typedef name="array"'."\t".'desc="数组"></typedef>'."\r\n";
		$this->xml .= "\t\t".'<typedef name="singleArray"'."\t".'desc="单体数组"></typedef>'."\r\n";
		$this->xml .= "\t\t".'<typedef name="file"'."\t".'desc="文件上传"></typedef>'."\r\n";
		$this->xml .= "\t".'</typedefs>'."\r\n";
		$this->xmlEnd = '</structdef>'."\r\n";
		$this->msg_types = "\t".'<msg_types>'."\r\n";
		$this->msg_defs = "\t".'<msg_defs>'."\r\n";
		$this->structs = "\t".'<structs>'."\r\n";
		$this->structsEnd = "\t".'</structs>'."\r\n";
		$this->structPacket = '';
	}
	
   	public function index() {//默认Action
   		header("content-type:text/xml;charset=utf-8");
	    $structPacket = '';
	    $struct = '';
   		$dir = APP_PATH;
   		$filename = Http::post('file', 'trim');
   		if(!$filename){
	   		if ($handle = opendir($dir)) {
			    /* 这是正确地遍历目录方法 */
			    while (false !== ($file = readdir($handle))) {
			        if($file == '.' || $file == '..' || is_dir($file)) continue;
			        if(!strpos($file,'.class.php')) continue;
			        $filename = str_replace('.class.php','',$file);
			        $this->get($filename);
			    }
			    closedir($handle);
			}
   		}else{
   			$this->get($filename);
   		}
		$this->msg_defs .= "\t".'</msg_defs>'."\r\n";
		$this->msg_types .= "\t".'</msg_types>'."\r\n";
		echo $this->xml.$this->msg_types.$this->msg_defs.$this->structs.$this->struct.$this->structsEnd.$this->xmlEnd;
   	}
   	
   	private function get($filename,$val=1){
   		import_app($filename);
		$content = ReflectionClass::export(ucfirst($filename.'_Api'),true);
		preg_match_all('/\/\*\*[\s\S]*?\*\//',$content,$out);
		if($out[0] && !empty($out[0])){
			if($filename != "Structdef"){
				$msgId = 1;
				foreach($out[0] as $_out){
					if(strpos($_out,'structdef.xml')){
						$_out = preg_filter(array('/\/\*/','/\*\//','/\t/','/\r\n/','/\r/','/\n/','/\s+/'), array('','',' ','','','',' '), $_out); 
						$actionStart = strpos($_out,'SSaction');
						$descStart = strpos($_out,'SSdesc');
						$pargamStart = strpos($_out,'SSpargam');
						$returnStart = strpos($_out,'SSreturn');
						$endStart = strpos($_out,'SSend');
						$pArr = array();
						$pArr['action'] = substr($_out,$actionStart+8,$descStart-$actionStart-8);
						$pArr['desc'] = substr($_out,$descStart+6,$pargamStart-$descStart-6);
						$pArr['pargam'] = substr($_out,$pargamStart+8,$returnStart-$pargamStart-8);
						$pArr['return'] = substr($_out,$returnStart+8,$endStart-$returnStart-8);
						$return_array = array();
						preg_match_all('/SSreturn_array_([\w]+)(.*?)SSreturn_array_end_[\w]+/',$pArr['return'],$returnout);
						if($returnout[0]){
							foreach($returnout[2] as $key=>$_returnout){
								if($returnout[1][$key]){
									$return_array[$returnout[1][$key]] = $_returnout;		
								}
							}
						}
						$return_array = $this->parr($return_array);
						$pArr['return'] = preg_replace('/SSreturn_array_([\w]+)(.*?)SSreturn_array_end_[\w]+/','',$pArr['return']);
						$pArr = $this->parr($pArr);
						$this->msg_defs .= "\t\t".'<msg'."\t".'msgid="MSG_'.strtoupper($filename).'_'.strtoupper($pArr['action']).'"'."\t".'typeid="MSG_TYPE_'.strtoupper($filename).'_'.strtoupper($pArr['action']).'"'."\t".'val="0x'.$this->msgid($msgId).'"'."\t".'desc="'.$pArr['desc'].'"></msg>'."\r\n";
						$this->struct .= "\t\t".'<struct'."\t".'app="'.$filename.'"'."\t".'act="'.$pArr['action'].'"'."\t".'msgid="MSG_'.strtoupper($filename).'_'.strtoupper($pArr['action']).'"'."\t".'flag="client_to_server"'."\t".'desc="'.$pArr['desc'].'">'."\r\n";
						if($pArr['pargam']){
							foreach($pArr['pargam'] as $pargam){
								$this->struct .= "\t\t\t".'<value'."\t".'type="'.$pargam[1].'"'."\t".'name="'.$pargam[0].'"'."\t".'desc="'.$pargam[2].'"></value>'."\r\n";
							}
						}
						$this->struct .= "\t\t".'</struct>'."\r\n";
						$this->struct .= "\t\t".'<struct'."\t".'app="'.$filename.'"'."\t".'act="'.$pArr['action'].'"'."\t".'msgid="MSG_'.strtoupper($filename).'_'.strtoupper($pArr['action']).'"'."\t".'flag="server_to_client"'."\t".'desc="'.$pArr['desc'].'">'."\r\n";
						if($pArr['return']){
							foreach($pArr['return'] as $pargam){
								$str = '';
								if($pargam[1] == 'array' || $pargam[1] == 'singleArray'){
									$str = $filename.'_'.$pArr['action'].'_'.$pargam[3];
								}
								$this->structPacket .= $this->packet($pargam[3],$filename,$pArr['action'],$return_array,$pargam[2]);
								$this->struct .= "\t\t\t".'<value'."\t".'type="'.$pargam[1].'"'."\t".'name="'.$pargam[0].'"'."\t".'desc="'.$pargam[2].'">'.$str.'</value>'."\r\n";
							}
						}
						$this->struct .= "\t\t".'</struct>'."\r\n";
						$msgId++;
					}elseif(strpos($_out,'msgtype')){
						$_out = preg_filter(array('/\/\*/','/\*\//','/\t/','/\r\n/','/\r/','/\n/','/\s+/'), array('','',' ','','','',' '), $_out); 
						$actionStart = strpos($_out,'SSaction');
						$descStart = strpos($_out,'SSdesc');
						$pargamStart = strpos($_out,'SSpargam');
						$returnStart = strpos($_out,'SSreturn');
						$endStart = strpos($_out,'SSend');
						$pArr = array();
						$pArr['desc'] = substr($_out,$descStart+6,$endStart-$descStart-6);
						$pArr = $this->parr($pArr);
						$this->msg_types .= "\t\t".'<msgtype'."\t".'typeid="MSG_TYPE_'.strtoupper($filename).'"'."\t".'name="'.strtolower($filename).'"'."\t".'val="0x'.$this->msgid($val).'"'."\t".'packet="com.hulapai.packet.'.strtolower($filename).'"></msgtype><!--'.$pArr['desc'].'-->'."\r\n";
					}
				}
				$val ++;	
			}
		}
		$this->struct .= $this->structPacket;
   	}
  
   
   	private function msgid($msgId){
   		if($msgId < 10)
   			return '0'.$msgId;
   		return $msgId;
   	}
   	
   
   	private function  packet($key,$filename,$action,$return_array,$desc){
   		$structPacket = '';
   		$structPacketExt = '';
   		if($return_array[$key]){
	   		$structPacket .= "\t\t".'<struct'."\t".'name="'.$filename.'_'.$action.'_'.$key.'"'."\t".'entity="ENTITY_PACKET"'."\t".'desc="'.$desc.'">'."\r\n";
	   		
	   		foreach($return_array[$key] as $_key=>$return){
				$str = '';
				if($return[1] == 'array' || $return[1] == 'singleArray'){
					$str = $filename.'_'.$action.'_'.$return[3];
					$structPacketExt = $this->packet($return[3],$filename,$action,$return_array,$return[2]);
					
				}
				$structPacket .= "\t\t\t".'<value'."\t".'type="'.$return[1].'"'."\t".'name="'.$return[0].'"'."\t".'desc="'.$return[2].'">'.$str.'</value>'."\r\n";
				
			}
			$structPacket .= "\t\t".'</struct>'."\r\n";
		}
		$structPacket = $structPacket.$structPacketExt;
		return $structPacket;
   	}
   	private function parr($pArr=array()){
   		if($pArr){
	   		foreach($pArr as &$_pArr){					
				$_pArr = array_values(array_filter(explode('*',trim($_pArr))));
				if(count($_pArr) <= 1){				
					$_pArr[0] = array_filter(explode(' ',trim($_pArr[0])));
					if(count($_pArr[0]) <= 1){
						$_pArr = trim($_pArr[0][0]);
					}else{
						foreach($_pArr as &$__pArr){
							$__pArr = array_map('trim',$__pArr);
						}
					}
					
				}else{
					$_pArr = array_map('trim',$_pArr);
					$_pArr = array_filter($_pArr);
					foreach($_pArr as &$__pArr){
						$__pArr = array_filter(explode(' ',trim($__pArr)));
						if(count($__pArr) <= 1){
							$__pArr = trim($__pArr[0]);
						}else{
							$__pArr = array_map('trim',$__pArr);
						
						}
					}
				}
			}
   		}
   		return $pArr;
   	}
}
?>