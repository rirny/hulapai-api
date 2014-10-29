<?php
if(!function_exists('Error'))
{
    function Error($msg)
    {
        die($msg);
    }
}

if(!function_exists('Out'))
{
    function Out($state=0, $message='', $data=array(),$array_id2_id = true)
    {		
		$result = array(
			'state' => $state,
			'message' => $message
		);
		if(!empty($data)){
			if($array_id2_id){
				array_id2_id($data);
			}
			$result['result'] = $data;
		}
        //die(json_encode($result, JSON_HEX_QUOT+JSON_HEX_APOS));
        // die(json_encode($result, JSON_NUMERIC_CHECK));
		die(json_encode($result));
    }
}

if(!function_exists('array_id2_id'))
{
	function array_id2_id(array &$arr)
	{
	  if($arr){
		foreach($arr as $key=>&$value){  
			if (is_array($value)) {  
				array_id2_id($value);  
			} else {  
				$value = stripslashes($value);
				if($key === "id"){
					$arr['_'.$key] = $value;
					unset($arr[$key]);
				}
			}  
		}
	  }
	}
}

if(!function_exists('hadId'))
{
	function hadId($v)
	{
	    if($v == "id"){
	    	return true;
	    }
	    return false;
	}
}

if(!function_exists('import'))
{
    function import($name)
    {        
        $class = ucfirst($name);
        if(!class_exists($class)) @require_once LIB . '/' . $name . '.class.php';
        return $class;
    }
}

if(!function_exists('load'))
{
    function load($name, $param = array())
    {
        $class = ucfirst($name);
        if(!class_exists($class)) require_once LIB . '/' . $name . '.class.php';		
        return new $class($param);
    }
}

if(!function_exists('import_app'))
{
    function import_app($name, $param = array())
    {
        $class = ucfirst($name) . "_Api";
        if(!class_exists($class)) require_once APP_PATH . '/' . $name . '.class.php';        
    }
}


if(!function_exists('load_model'))
{
    function &load_model($name, $param=Null, $version=0)
    {		
		if($version)
		{
			return load_new_model($name, $param);
		}
		static $modules = array();
		if(!isset($modules[$name]))
		{
			if(!class_exists('Model')) @require_once LIB . '/model.class.php';
			$model = ucfirst($name) . "_Model";
			$class = MODEL . '/' . $name . '.model.class.php';		
			if(!class_exists($model))
			{
				if(file_exists($class)){
					require_once $class;
				}else{
					isset($param['table']) || $param['table'] = $name;	
					eval('class ' . $model . ' extends Model { public function __construct($param=null){parent::__construct($param);}}'); 
				}
			}
			$modules[$name] = new $model($param);
		}        
        return $modules[$name];
    }
}

if(!function_exists('load_new_model'))
{
    function &load_new_model($name, $param=Null)
    {		
		static $modules = array();
		if(!isset($modules[$name]))
		{
			if(!class_exists('Model_New')) @require_once LIB . '/model.class.php';
			require_once LIB . '/model.new.class.php';
			$model = ucfirst($name) . "_Model_New";		
			$class = MODEL . '/new/' . $name . '.model.class.php';		
			if(!class_exists($model))
			{
				if(file_exists($class)){
					require_once $class;
				}else{
					isset($param['table']) || $param['table'] = $name;	
					eval('class ' . $model . ' extends Model_New { public function __construct($param=null){parent::__construct($param);}}'); 
				}
			}
			$modules[$name] = new $model($param);
		}        
        return $modules[$name];
    }
}

if(!function_exists('import_model'))
{
    function import_model($name)
    {
        if(!class_exists('Model')) @require_once LIB . '/model.class.php';
        $model = ucfirst($name) . "_Model";
        if(!class_exists($model)) @require_once MODEL . '/' . $name . '.model.class.php';
        return $model;
    }
}

if(!function_exists('load_entity'))
{
    function load_entity($name, array $data)
    {
        if(!class_exists('Entity')) require_once LIB . '/entity.class.php';
        $entity = ucfirst($name) . "_Entity";
        if(!class_exists($entity)) require_once ENTITY . '/' . $name . '.entity.class.php';
        return new $entity($data);
    }
}

if(!function_exists('logss'))
{
    function logss($subject, $action, $sql, $uid, $message=array())
    {		
        $dir = LOG_PATH . "/data/log/" . date("Ym");
		if(!is_dir($dir)) _mkdir($dir, 0777);		
		$date = date('Y-m-d H:i:s'); 
		$log = "\n==================================================\n";
		$log.= $subject."\t". $date ."\t". $action. "\t" . $uid . "\n";
		$log.= "SQL:\t" . $sql . "\n";
		$log.= "message:\t" . json_encode($message) . "\n";
		error_log($log, 3,  $dir . "/" . date('H') . ".log"); 
    }
}

if(!function_exists('_mkdir'))
{
    function _mkdir($path, $mode = 0777){
		if(!file_exists($path)){
			_mkdir(dirname($path), $mode);
			mkdir($path, $mode);
			//fclose(fopen($path . '/index.htm', 'w'));
		}
		return true;
	}
}

if(!function_exists('hlp_session_start'))
{
    function &hlp_session_start()
    {		
		static $handle = null;
		if($handle == null)
		{
			if(!class_exists('Session_handle')) require_once LIB . '/session.class.php';		
			$cache = cache('memcache');
			$handle = new Session_handle($cache);
		}
		return $handle;
    }
}

if(!function_exists('datetime'))
{
    function datetime($format='', $time)
    {
		$format || $format = 'Y-m-d H:i:s';
		$time || $time = time();
		return date($format, $time);
    }
}

if(!function_exists('cache'))
{
    function &cache($type = 'memcache')
    {
		$class = ucfirst($type) . "_handle";
		$class_file = LIB . '/' . $type . '.class.php';
        if(!class_exists($class)) @require_once LIB . '/' . $type . '.class.php';
		if(!file_exists($class_file)) Out(0, '功能文件不存在！');		
		return new $class();
    }
}

if(!function_exists('redis'))
{
    function &redis($db = 0)
    {
		static $redis = Array();	
		if(!isset($redis[$db]) && $redis[$db] == null)
		{
			$class_file = LIB . '/redis.class.php';
			if(!class_exists('Redis_handle')) @require_once $class_file;
			if(!file_exists($class_file)) Out(0, '功能文件不存在！');
			$config = Config::get(null, 'redis');
			$config['db'] = $db;
			$redis[$db] = new Redis_handle($config);		
		}
		return $redis[$db];
    }
}

if(!function_exists('push'))
{
    function &push($handle = 'redis')
    {
		static $push = array();
		if(!isset($push[$handle]))
		{
			import('push');
			$push[$handle] = Push::get_instance($handle);
		}
		return $push[$handle];
    }
}

if(!function_exists('db'))
{
    function &db($database='', $reconnect = false)
    {
		static $db = array();
		$database || $database = 'default';
		if(!isset($db[$database]) || $reconnect)
		{
			unset($db[$database]);
			$class_file = LIB . '/db.class.php';
			if(!class_exists($class)) @require_once $class_file;
			if(!file_exists($class_file)) Out(0, '功能文件不存在！');
			$config = Config::get($database, 'database');
			if(empty($config)) return false;
			$db[$database] = new Db($config);			
		}
		return $db[$database];
    }
}

if(!function_exists('logs'))
{
    function &logs($handle='')
    {
		static $logs = array();		
		$handle || $handle = 'db';		
		if(!isset($logs[$handle]))
		{
			$logs_class =  LIB . "/logs.class.php";
			if(!class_exists($logs_class)) @require_once $logs_class;
			$logs[$handle] = new Logs($handle);
		}
		return $logs[$handle];
    }
}

if(!function_exists('error'))
{
    function error($msg, $out=false)
    {		
		if($out) Out(0, $msg);
		throw new Exception($msg);
    }
}


if( !function_exists ('mime_content_type')) {
    /**
     +----------------------------------------------------------
     * 获取文件的mime_content类型
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    function mime_content_type($filename)
    {
       static $contentType = array(
			'ai'	=> 'application/postscript',
				'aif'	=> 'audio/x-aiff',
				'aifc'	=> 'audio/x-aiff',
				'aiff'	=> 'audio/x-aiff',
				'asc'	=> 'application/pgp', //changed by skwashd - was text/plain
				'asf'	=> 'video/x-ms-asf',
				'asx'	=> 'video/x-ms-asf',
				'au'	=> 'audio/basic',
				'avi'	=> 'video/x-msvideo',
				'bcpio'	=> 'application/x-bcpio',
				'bin'	=> 'application/octet-stream',
				'bmp'	=> 'image/bmp',
				'c'	=> 'text/plain', // or 'text/x-csrc', //added by skwashd
				'cc'	=> 'text/plain', // or 'text/x-c++src', //added by skwashd
				'cs'	=> 'text/plain', //added by skwashd - for C# src
				'cpp'	=> 'text/x-c++src', //added by skwashd
				'cxx'	=> 'text/x-c++src', //added by skwashd
				'cdf'	=> 'application/x-netcdf',
				'class'	=> 'application/octet-stream',//secure but application/java-class is correct
				'com'	=> 'application/octet-stream',//added by skwashd
				'cpio'	=> 'application/x-cpio',
				'cpt'	=> 'application/mac-compactpro',
				'csh'	=> 'application/x-csh',
				'css'	=> 'text/css',
				'csv'	=> 'text/comma-separated-values',//added by skwashd
				'dcr'	=> 'application/x-director',
				'diff'	=> 'text/diff',
				'dir'	=> 'application/x-director',
				'dll'	=> 'application/octet-stream',
				'dms'	=> 'application/octet-stream',
				'doc'	=> 'application/msword',
				'dot'	=> 'application/msword',//added by skwashd
				'dvi'	=> 'application/x-dvi',
				'dxr'	=> 'application/x-director',
				'eps'	=> 'application/postscript',
				'etx'	=> 'text/x-setext',
				'exe'	=> 'application/octet-stream',
				'ez'	=> 'application/andrew-inset',
				'gif'	=> 'image/gif',
				'gtar'	=> 'application/x-gtar',
				'gz'	=> 'application/x-gzip',
				'h'	=> 'text/plain', // or 'text/x-chdr',//added by skwashd
				'h++'	=> 'text/plain', // or 'text/x-c++hdr', //added by skwashd
				'hh'	=> 'text/plain', // or 'text/x-c++hdr', //added by skwashd
				'hpp'	=> 'text/plain', // or 'text/x-c++hdr', //added by skwashd
				'hxx'	=> 'text/plain', // or 'text/x-c++hdr', //added by skwashd
				'hdf'	=> 'application/x-hdf',
				'hqx'	=> 'application/mac-binhex40',
				'htm'	=> 'text/html',
				'html'	=> 'text/html',
				'ice'	=> 'x-conference/x-cooltalk',
				'ics'	=> 'text/calendar',
				'ief'	=> 'image/ief',
				'ifb'	=> 'text/calendar',
				'iges'	=> 'model/iges',
				'igs'	=> 'model/iges',
				'jar'	=> 'application/x-jar', //added by skwashd - alternative mime type
				'java'	=> 'text/x-java-source', //added by skwashd
				'jpe'	=> 'image/jpeg',
				'jpeg'	=> 'image/jpeg',
				'jpg'	=> 'image/jpeg',
				'js'	=> 'application/x-javascript',
				'kar'	=> 'audio/midi',
				'latex'	=> 'application/x-latex',
				'lha'	=> 'application/octet-stream',
				'log'	=> 'text/plain',
				'lzh'	=> 'application/octet-stream',
				'm3u'	=> 'audio/x-mpegurl',
				'man'	=> 'application/x-troff-man',
				'me'	=> 'application/x-troff-me',
				'mesh'	=> 'model/mesh',
				'mid'	=> 'audio/midi',
				'midi'	=> 'audio/midi',
				'mif'	=> 'application/vnd.mif',
				'mov'	=> 'video/quicktime',
				'movie'	=> 'video/x-sgi-movie',
				'mp2'	=> 'audio/mpeg',
				'mp3'	=> 'audio/mpeg',
				'mpe'	=> 'video/mpeg',
				'mpeg'	=> 'video/mpeg',
				'mpg'	=> 'video/mpeg',
				'mpga'	=> 'audio/mpeg',
				'ms'	=> 'application/x-troff-ms',
				'msh'	=> 'model/mesh',
				'mxu'	=> 'video/vnd.mpegurl',
				'nc'	=> 'application/x-netcdf',
				'oda'	=> 'application/oda',
				'patch'	=> 'text/diff',
				'pbm'	=> 'image/x-portable-bitmap',
				'pdb'	=> 'chemical/x-pdb',
				'pdf'	=> 'application/pdf',
				'pgm'	=> 'image/x-portable-graymap',
				'pgn'	=> 'application/x-chess-pgn',
				'pgp'	=> 'application/pgp',//added by skwashd
				'php'	=> 'application/x-httpd-php',
				'php3'	=> 'application/x-httpd-php3',
				'pl'	=> 'application/x-perl',
				'pm'	=> 'application/x-perl',
				'png'	=> 'image/png',
				'pnm'	=> 'image/x-portable-anymap',
				'po'	=> 'text/plain',
				'ppm'	=> 'image/x-portable-pixmap',
				'ppt'	=> 'application/vnd.ms-powerpoint',
				'ps'	=> 'application/postscript',
				'qt'	=> 'video/quicktime',
				'ra'	=> 'audio/x-realaudio',
				'rar'=>'application/octet-stream',
				'ram'	=> 'audio/x-pn-realaudio',
				'ras'	=> 'image/x-cmu-raster',
				'rgb'	=> 'image/x-rgb',
				'rm'	=> 'audio/x-pn-realaudio',
				'roff'	=> 'application/x-troff',
				'rpm'	=> 'audio/x-pn-realaudio-plugin',
				'rtf'	=> 'text/rtf',
				'rtx'	=> 'text/richtext',
				'sgm'	=> 'text/sgml',
				'sgml'	=> 'text/sgml',
				'sh'	=> 'application/x-sh',
				'shar'	=> 'application/x-shar',
				'shtml'	=> 'text/html',
				'silo'	=> 'model/mesh',
				'sit'	=> 'application/x-stuffit',
				'skd'	=> 'application/x-koan',
				'skm'	=> 'application/x-koan',
				'skp'	=> 'application/x-koan',
				'skt'	=> 'application/x-koan',
				'smi'	=> 'application/smil',
				'smil'	=> 'application/smil',
				'snd'	=> 'audio/basic',
				'so'	=> 'application/octet-stream',
				'spl'	=> 'application/x-futuresplash',
				'src'	=> 'application/x-wais-source',
				'stc'	=> 'application/vnd.sun.xml.calc.template',
				'std'	=> 'application/vnd.sun.xml.draw.template',
				'sti'	=> 'application/vnd.sun.xml.impress.template',
				'stw'	=> 'application/vnd.sun.xml.writer.template',
				'sv4cpio'	=> 'application/x-sv4cpio',
				'sv4crc'	=> 'application/x-sv4crc',
				'swf'	=> 'application/x-shockwave-flash',
				'sxc'	=> 'application/vnd.sun.xml.calc',
				'sxd'	=> 'application/vnd.sun.xml.draw',
				'sxg'	=> 'application/vnd.sun.xml.writer.global',
				'sxi'	=> 'application/vnd.sun.xml.impress',
				'sxm'	=> 'application/vnd.sun.xml.math',
				'sxw'	=> 'application/vnd.sun.xml.writer',
				't'	=> 'application/x-troff',
				'tar'	=> 'application/x-tar',
				'tcl'	=> 'application/x-tcl',
				'tex'	=> 'application/x-tex',
				'texi'	=> 'application/x-texinfo',
				'texinfo'	=> 'application/x-texinfo',
				'tgz'	=> 'application/x-gtar',
				'tif'	=> 'image/tiff',
				'tiff'	=> 'image/tiff',
				'tr'	=> 'application/x-troff',
				'tsv'	=> 'text/tab-separated-values',
				'txt'	=> 'text/plain',
				'ustar'	=> 'application/x-ustar',
				'vbs'	=> 'text/plain', //added by skwashd - for obvious reasons
				'vcd'	=> 'application/x-cdlink',
				'vcf'	=> 'text/x-vcard',
				'vcs'	=> 'text/calendar',
				'vfb'	=> 'text/calendar',
				'vrml'	=> 'model/vrml',
				'vsd'	=> 'application/vnd.visio',
				'wav'	=> 'audio/x-wav',
				'wax'	=> 'audio/x-ms-wax',
				'wbmp'	=> 'image/vnd.wap.wbmp',
				'wbxml'	=> 'application/vnd.wap.wbxml',
				'wm'	=> 'video/x-ms-wm',
				'wma'	=> 'audio/x-ms-wma',
				'wmd'	=> 'application/x-ms-wmd',
				'wml'	=> 'text/vnd.wap.wml',
				'wmlc'	=> 'application/vnd.wap.wmlc',
				'wmls'	=> 'text/vnd.wap.wmlscript',
				'wmlsc'	=> 'application/vnd.wap.wmlscriptc',
				'wmv'	=> 'video/x-ms-wmv',
				'wmx'	=> 'video/x-ms-wmx',
				'wmz'	=> 'application/x-ms-wmz',
				'wrl'	=> 'model/vrml',
				'wvx'	=> 'video/x-ms-wvx',
				'xbm'	=> 'image/x-xbitmap',
				'xht'	=> 'application/xhtml+xml',
				'xhtml'	=> 'application/xhtml+xml',
				'xls'	=> 'application/vnd.ms-excel',
				'xlt'	=> 'application/vnd.ms-excel',
				'xml'	=> 'application/xml',
				'xpm'	=> 'image/x-xpixmap',
				'xsl'	=> 'text/xml',
				'xwd'	=> 'image/x-xwindowdump',
				'xyz'	=> 'chemical/x-xyz',
				'z'	=> 'application/x-compress',
				'zip'	=> 'application/zip',
       );
       $type = strtolower(substr(strrchr($filename, '.'),1));
       if(isset($contentType[$type])) {
            $mime = $contentType[$type];
       }else {
       	    $mime = 'application/octet-stream';
       }
       return $mime;
    }
}

if(!function_exists('image_type_to_extension'))
{
   function image_type_to_extension($imagetype)
   {
       if(empty($imagetype)) return false;
       switch($imagetype)
       {
           case IMAGETYPE_GIF    : return '.gif';
           case IMAGETYPE_JPEG    : return '.jpg';
           case IMAGETYPE_PNG    : return '.png';
           case IMAGETYPE_SWF    : return '.swf';
           case IMAGETYPE_PSD    : return '.psd';
           case IMAGETYPE_BMP    : return '.bmp';
           case IMAGETYPE_TIFF_II : return '.tiff';
           case IMAGETYPE_TIFF_MM : return '.tiff';
           case IMAGETYPE_JPC    : return '.jpc';
           case IMAGETYPE_JP2    : return '.jp2';
           case IMAGETYPE_JPX    : return '.jpf';
           case IMAGETYPE_JB2    : return '.jb2';
           case IMAGETYPE_SWC    : return '.swc';
           case IMAGETYPE_IFF    : return '.aiff';
           case IMAGETYPE_WBMP    : return '.wbmp';
           case IMAGETYPE_XBM    : return '.xbm';
           default                : return false;
       }
   }

}

if(!function_exists('_hash'))
{
   function _hash(array $param)
   {
       if(empty($param)) return '';
		// $param = Http::post();		
		extract($param);
		$hashStr = join("", array_values($param));		
		$hash = md5($hashStr); // 标识
		if(redis()->hget('event-trad', $hash))
		{
			// throw new Exception("课程已经在处理中！");
		}else{
			redis()->hset('event-trad', $hash, $param, 0);
		}
		return $hash;
   }

}

if(!function_exists('SMS'))
{
	function SMS()
	{
		static $sms = Null;		
		if(Null === $sms)
		{
			$config = Config::get(Null, 'sms', Null, array());			
			$sms = load('sms', $config);			
			if(!$sms) return $sms = Null;
		}
		return $sms;		
	}
}

if(!function_exists('paginator'))
{
    function paginator($page, $count, $perPage = null, $pageRange = null)
    {		
        if(!class_exists('Paginator')) import(paginator);
		return new Paginator($page, $count, $perPage, $pageRange);
    }
}
if(!function_exists('excelExport'))
{
	function excelExport($fileName='',$headArr=array(),$data=array(),$ex='2007'){
		if(empty($data) || !is_array($data)){
			die("data must be a array");
		}
		if(empty($fileName)){
			exit;
		}
		$sheetName = $fileName;
		$date = date("Y_m_d",time());
		$fileName .= "_{$date}.xlsx";

		require_once LIB . '/PHPExcel.php';
		$objPHPExcel = new PHPExcel();
		$objProps = $objPHPExcel->getProperties();
		
		//设置表头
		$key = ord("A");
		foreach($headArr as $v){
			$colum = chr($key);
			$objPHPExcel->setActiveSheetIndex(0)->setCellValue($colum.'1', $v);
			$key += 1;
		}
		
		$column = 2;
		$objActSheet = $objPHPExcel->getActiveSheet();
		foreach($data as $key => $rows){ //行写入
			$span = ord("A");
			foreach($rows as $keyName=>$value){// 列写入
				$j = chr($span);
				$objActSheet->setCellValue($j.$column, $value);
				$span++;
			}
			$column++;
		}

		$fileName = iconv("utf-8", "gb2312", $fileName);
		 //设置活动单指数到第一个表,所以Excel打开这是第一个表
		$objPHPExcel->setActiveSheetIndex(0);
		//重命名表
		$objPHPExcel->getActiveSheet()->setTitle($sheetName);
		if($ex == '2007') { //导出excel2007文档
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header("Content-Disposition: attachment; filename=\"$fileName\"");
			header('Cache-Control: max-age=0');
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		} else {  //导出excel2003文档
			header('Content-Type: application/vnd.ms-excel');
			header("Content-Disposition: attachment; filename=\"$fileName\"");
			header('Cache-Control: max-age=0');
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		}
		$objWriter->save('php://output');
		exit;	
	}
}

if(!function_exists('imageUrl'))
{
	function imageUrl($id, $type=0, $size=0){
		if(!$id) return false;
		$static = array(30 => 'tiny', 50=>'small', 100=> 'middle', '200'=>'big');
		$_size = $size;
		isset($static[$size]) || $_size=200;
		$url =  Config::get('avatar', 'attach');		
		$MD = md5($id);
		$path = substr($MD,0,2) . "/" . substr($MD,2,2) . "/" .substr($MD,4,2). "/original";
		$path .= "_{$_size}_{$_size}";
		$img = '';
		if($type == 0){
			// $img =  $url ? $pathArr['image'] . $url : "";
		}elseif($type == 1){
			$img = $url . 'avatar/' . $path.'.jpg';
			@getimagesize($img) || $img = $url . "noavatar/" . $static[$_size]. ".jpg";
			$img = $img."?t=".time();
		}elseif($type == 2){
			$img = $url . 'student_avatar/'.$path.'.jpg';
			@getimagesize($img) || $img = $url . "noavatar/" . $static[$_size]. ".jpg";
			$img = $img."?t=".time();
		}elseif($type == 3){
			$img = $url . 'school/'. $path . '.jpg';
			@getimagesize($img) || $img = $url . "noavatar/" . $static[$_size]. ".jpg";
			$img = $img."?t=".time();
		}
		if($returnimg) return $img ? "<img src='$img' width='$size' height='$size'/>" : "";
		else return $img;
	}
}

if(!function_exists('loadExcel'))
{
	function loadExcel($file, $multi=false)
	{
		$result = array('rows' => 0, 'cols' => 0, 'data' => array());	
		require_once LIB . '/PHPExcel.php';
		$objReader = new PHPExcel_Reader_Excel2007(); //use excel2007
		try
		{
			$result = Array();
			$objPHPExcel = $objReader->load($file); //指定的文件
			if($multi)
			{
				$sheetCount = $objPHPExcel->getSheetCount();
				$sheetNames = $objReader->listWorksheetNames($file);				
				foreach($sheetNames as $key => $item)
				{				
					$data = $objPHPExcel->getSheet($key)->toArray();
					if(empty($data)) continue;
					$name = $item;
					foreach($data as $c => $val)
					{
						if(empty($val)) unset($data[$c]);
					}
					$result[$key] = compact('name', 'data');
				}
			}else{
				$data = $objPHPExcel->getSheet(0)->toArray();	
				$sheetNames = $objReader->listWorksheetNames($file);
				$name = $sheetNames[0];
				foreach($data as $c => $val)
				{			
					$val = array_filter($val);					
					if(empty($val)) unset($data[$c]);
				}
				$result = compact('name', 'data');
			}			
		}catch(Exception $e)
		{
			return $result;
		}		
		return $result;
	}
}

if(!function_exists('birthday_to_age'))
{
	function birthday_to_age($birth)
	{
		$birth = strtotime($birth);
		$y = date('Y', $birth);
		if (($m = (date('m') - date('m', $birth))) < 0) {
			$y++;
		}else if($m == 0 && date('d') - date('d', $birth) < 0) {
			$y++;
		}
		return date('Y') - $y;
	}
}

if(!function_exists('eventFormat'))
{
	function event_format(&$v, $k, $colors)
	{
		$v['teachers'] = json_decode($v['teachers'], true);
		array_walk($v['teachers'], create_function('&$v', '$v=$v[\'name\'];'));
		$teachers = array_slice($v['teachers'], 0, 2);
		$v['students'] = json_decode($v['students'], true);
		array_walk($v['students'], create_function('&$v', '$v=$v[\'name\'];'));
		$students = array_slice($v['students'], 0, 2);
		$v['start'] = date('H:i', strtotime($v['start_date']));
		$v['end'] = date('H:i', strtotime($v['end_date']));
		$v['commented'] || $v['commented'] = 0;
		$v['readonly'] || $v['readonly'] = 1;
		$v['color'] = $colors[$v['color']];
		$content[] = "课程名：{$v['text']}";
		$content[] = "上课时间：{$v['start']}-{$v['end']}";
		$v['teachers'] && $content[] = "老师：" . join("，", $teachers) . (count($v['teachers']) > 2 ? ".." : "");
		$v['students'] && $content[] = "学生：" . join("，", $students) . (count($v['students']) > 2 ? ".." : "");
		$v['text'] = join("<br />", $content);
		$v['title'] = join('&#13;', $content);	
		unset($v['teachers'], $v['students'], $v['start'], $v['end']); 		
	}
}

if(!function_exists('array_sort'))
{
	function array_sort($array, $on, $order=SORT_ASC)
	{
		$new_array = array();
		$sortable_array = array();
		if (count($array) > 0) {
			foreach ($array as $k => $v) {
				if (is_array($v)) {
					foreach ($v as $k2 => $v2) {
						if ($k2 == $on) {
							$sortable_array[$k] = $v2;
						}
					}
				} else {
					$sortable_array[$k] = $v;
				}
			}			
			switch ($order) {
				case SORT_ASC:
					asort($sortable_array);
				break;
				case SORT_DESC:
					arsort($sortable_array);
				break;
			}			
			foreach ($sortable_array as $k => $v) {
				$new_array[$k] = $array[$k];
			}
		}		
		return $new_array;
	}
}
/*
 * 用户日志
 * install 记录
*/
function apiLogs()
{	
	$first = Http::get_session('first', 0);
	if($first == 1) return ;	
	// Http::set_session('first', 1);
	$agent = Http::agent();	
	$time = date('Y-m-d H');
	$ip = Http::ip();
	extract($agent);
	$key =  md5($brand . $os . $model . $ip);
	$redis = redis();	
	$channelObj = $redis()->hGet('channel', $key);
	$channel = empty($channelObj['channel']) ? '' : $channelObj['channel'];
	Http::set_session('channel', $channel);
	$data = array_merge($agent, compact('channel', 'ip', 'tm'));
	if($sn && !$redis()->hExists('equipment', md5($brand.$sn))) // 此设备是否安装过应用
	{		
		$redis()->hSet('equipment', md5($brand.$sn), json_encode($data, JSON_FORCE_OBJECT)); // 设备第一次进应用的渠道
		$redis = redis(2);
		$redis()->zAdd('install_' . date('Ym'), time(), json_encode($data, JSON_FORCE_OBJECT));
		Http::set_session('first', 1);
	}
}

if(!function_exists('week_day'))
{
    function week_day($date='0000-00-00')
    {	
		$date || $date = DAY;		
		$time = strtotime($date);
		$w = date('w', $time);
		$start = $time - $w * 18600;
		$end = $start + 7 * 18600;
		$start = date('Y-m-d', $start);
		$end = date('Y-m-d', $end);		
		Return compact('start', 'end');
    }
}

if(!function_exists('month_day'))
{
    function month_day($date='0000-00-00')
    {	
		$date || $date = DAY;
		$time = strtotime($date);
		$m || $m=date('n', $time);
		$y || $y=date('Y', $time);
		$start = date("Y-m-d", mktime(0,0,0,$m, 1, $y));
		$end = date("Y-m-d", mktime(0,0,0, $m+1, 0, $y));
		Return compact('start', 'end');
    }
}

if(!function_exists('array_column'))
{
    function array_column(Array $data, $field)
    {	
		if(empty($data) || !$field) return Array();
		$result = Array();
		foreach($data as $key => $item)
		{
			if(is_array($item) && isset($item[$field]))
			{
				$result[] = $item[$field];
			}
		}
		return $result;
    }
}

