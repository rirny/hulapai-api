<?php
class Files
{
	
	private static $allow_types = array(
		'image' => array('jpg','jpeg','png','gif'),
		'file'=> array('txt','doc','docx','xls', 'xlsx', 'ppt', 'pptx'),
	);

	public static function mkdir($path, $model = 0777)
	{		
		if(!file_exists($path)){			
			self::mkdir(dirname($path), $model);
			mkdir($path, $model);			
		}
	}

	public static function rmdir()
	{
		
	}

	public static function get_save_path($type='', $id=0)
	{		
		$result = '';
		if($type == 'student')
		{
			if(!$id) return false;
			$md5 = md5($id);			
			$result = "student_avatar/" . substr($md5, 0, 2).'/'.substr($md5, 2, 2).'/'.substr($md5, 4, 2);
		}else if($type == 'avatar')
		{
			if(!$id) return false;
			$md5 = md5($id);		
			$result = "avatar/" . substr($md5, 0, 2).'/'.substr($md5, 2, 2).'/'.substr($md5, 4, 2);
		}else if($type == 'school')
		{
			if(!$id) return false;
			$md5 = md5($id);		
			$result = "school/" . substr($md5, 0, 2).'/'.substr($md5, 2, 2).'/'.substr($md5, 4, 2);
		}else{
			$save_name = uniqid();
			$result = datetime('Y/md/H');
		}
		$result = $result . "/";
		return $result;
	}
	
	public static function get_extension($file)
	{
		return strtolower(pathinfo($file, PATHINFO_EXTENSION));
	}

	public static function extension_allow($ext, $type='image')
	{
		if(!$ext) return false;
		$exts = self::$allow_types;	
		if(!isset($exts[$type])) return false;		
		if(!in_array($ext, $exts[$type])) return false;
		return true;
	}
	
	public static function upload_allowed($inputname="upfile")
	{		
		if(!isset($_FILES[$inputname]) || !$_FILES[$inputname] || $_FILES[$inputname]["error"])
		{
			throw new Exception('没有提交文件域');
		}		
		$max = Config::get('max', 'upload');
		if($max < $_FILES[$inputname]["size"]) throw new Exception('上传图片大小不能超过2M，请重新选择');
		return true;
	}
	
	/*
	 * @type ''/avatar/student/
	 * @
	*/
	// 文件上传
	public static function upload($uploadtype='', $file_type='image', $id=0,$inputname="upfile")
	{		
		if(!self::upload_allowed($inputname)) throw new Exception('上传失败！');		
		$result = array();
		$extension = self::get_extension($_FILES[$inputname]["name"]);
		if($file_type == 'image')
		{
			$info = self::getImageInfo($_FILES[$inputname]["tmp_name"]);	
			if(!self::extension_allow($info['type'], $file_type)) throw new Exception('不允许的文件类型！');
		}else{
			if(!self::extension_allow($extension, $file_type)) throw new Exception('不允许的文件类型！');
		}
		// 
		$name = $_FILES[$inputname]['name'];
		$type = $_FILES[$name]['type'];
		$save_name = ($id ? 'original' :  uniqid()) . "." . $extension;
		$size = $_FILES[$inputname]['size'];		
		$save_path = self::get_save_path($uploadtype, $id);
		$root = Config::get('path', 'upload');
		$copy = $root .'/' . $save_path .$save_name;
		self::mkdir($root .'/' . $save_path);
		list($width, $height) = getimagesize($_FILES[$inputname]['tmp_name']);
		if(!class_exists('Image'))	require_once LIB . '/image.php';
		$config['image_library'] = 'gd2';
		$config['source_image'] = $_FILES[$inputname]['tmp_name'];
		$config['new_image'] = $copy;
		$config['maintain_ratio'] = TRUE;
		$config['thumb_marker'] = '';
		$config['create_thumb'] = TRUE;
		$config['width'] = $width;
		$config['height'] = $height;
		$image = new Image($config);
		if($image->resize())
		{
			$hash = md5_file($_FILES[$inputname]['tmp_name']);
			if($id){
				$avaterData = self::getSize(200,200,$width,$height);
				$avaterName = substr($save_name,0,-4).'_200_200.jpg';
				$config['new_image'] = $root .'/' . $save_path .$avaterName;
				$config['width'] = $avaterData['width'];
				$config['height'] = $avaterData['height'];
				$image->initialize($config);
				$image->resize();
			}else{	
				$smallImageName = substr($save_name,0,-4).'_small.jpg';
				$middleImageName = substr($save_name,0,-4).'_middle.jpg';
				$reset = Config::get('single', 'thumb');
				
		        $middleWidth = $reset['max_width'];
				$middleHeight = $reset['max_height'];
				$smallWidth = $reset['min_width'];
				$smallHeight = $reset['min_height'];  
		        
				$middleData = self::getSize($middleWidth,$middleHeight,$width,$height);
				$smallData = self::getSize($smallWidth,$smallHeight,$width,$height);
				$config['new_image'] = $root .'/' . $save_path .$middleImageName;
				$config['width'] = $middleData['width'];
				$config['height'] = $middleData['height'];
				$image->initialize($config);
				$image->resize();
				$config['new_image'] = $root .'/' . $save_path .$smallImageName;
				$config['width'] = $smallData['width'];
				$config['height'] = $smallData['height'];
				$image->initialize($config);
				$image->resize();
			}	
			return compact('extension', 'name', 'save_path', 'save_name', 'hash', 'size', 'type');	
		}			
		return false;
	}
	
	public static function getSize($width,$height,$source_width,$source_height){
		if($source_width > $width || $source_height > $height)
		{				
			$wRate = $width / $source_width;           
			$hRate = $height / $source_height;           
			$sourceRate = $wRate > $hRate ? $hRate : $wRate;
			$width = intval($source_width * $sourceRate);
			$height = intval($source_height * $sourceRate);
		}else{
			$width = $source_width;
			$height = $source_height;
		}
		return compact('width', 'height');
	}
	
	/*
	public static function upload($uploadtype='', $file_type='image', $id=0,$inputname="upfile")
	{		
		if(!self::upload_allowed($inputname)) throw new Exception('上传失败！');		
		$result = array();
		$extension = self::get_extension($_FILES[$inputname]["name"]);
		if($file_type == 'image')
		{
			$info = self::getImageInfo($_FILES[$inputname]["tmp_name"]);	
			if(!self::extension_allow($info['type'], $file_type)) throw new Exception('不允许的文件类型！');
		}else{
			if(!self::extension_allow($extension, $file_type)) throw new Exception('不允许的文件类型！');
		}
		// 
		$name = $_FILES[$inputname]['name'];
		// $type = $_FILES[$name]['type'];
		$save_name = ($id ? 'original' :  uniqid()) . "." . $extension;
		$size = $_FILES[$inputname]['size'];		
		$save_path = self::get_save_path($uploadtype, $id);
		$root = Config::get('path', 'upload');
		$copy = $root .'/' . $save_path .$save_name;
		self::mkdir($root .'/' . $save_path);			
		$type = $_FILES[$inputname]['type'];		
		if(@copy($_FILES[$inputname]['tmp_name'], $copy))
		{
			$hash = md5_file($_FILES[$inputname]['tmp_name']);
			if($id)
			{
				//self::avatar_thumb($copy, 30, 30);
				//self::avatar_thumb($copy, 50, 50);
				//self::avatar_thumb($copy, 100, 100);
				self::avatar_thumb($copy, 200, 200);			
            }else{
                $thumb = self::thumb($root . "/" . $save_path, $save_name);                  
            }		
			// @link($copy);
			return compact('extension', 'name', 'save_path', 'save_name', 'hash', 'size', 'type');	
		}		
		// throw new Exception('不允许的文件类型！');
		return false;
	}
	*/
	
	/*
	public static function avatar_thumb($file, $width, $height)
	{		
		if($width < 0 || $height < 0) return false;
		if(!class_exists('PhpThumbFactory'))	require_once LIB . '/phpthumb/ThumbLib.inc.php';
		$thumb = PhpThumbFactory::create($file);		
		$path = dirname($file);
		$new = pathinfo($file, PATHINFO_FILENAME) . "_" . $width . "_" . $height;
		$newFile = $path ."/". $new .".jpg";
		$thumb->resize($width, $height)->save($newFile, 'jpg');
	}
	*/
	
	// 其他上传
	/*
	public static function thumb($path, $filename)
	{		
		$imageFile = $path.$filename;     
		list($width, $height) = getimagesize($imageFile);	
		if(!class_exists('PhpThumbFactory'))	require_once LIB . '/phpthumb/ThumbLib.inc.php';
		$thumb = PhpThumbFactory::create($imageFile);       
		$smallImageName = substr($filename,0,-4).'_small.jpg';
		$middleImageName = substr($filename,0,-4).'_middle.jpg';
		$reset = Config::get('single', 'thumb');
		
        $middleWidth = $reset['max_width'];
		$middleHeight = $reset['max_height'];
		$smallWidth = $reset['min_width'];
		$smallHeight = $reset['min_height'];  
        
		//小图
		if($width > $smallWidth || $height > $smallHeight)
		{				
			$wRate = $smallWidth / $width;           
			$hRate = $smallHeight / $height;           
			$SmallRate = $wRate > $hRate ? $hRate : $wRate;
			$smallWidth = intval($width * $SmallRate);
			$smallHeight = intval($height * $SmallRate);
		}
		//大图
		if($width > $middleWidth || $height > $middleHeight)
		{				
			$wRate = $middleWidth / $width;           
			$hRate = $middleHeight / $height;          
			$middleRate = $wRate > $hRate ? $hRate : $wRate;           
			$middleWidth = intval($width * $middleRate);
			$middleHeight = intval($height * $middleRate);
		}		
        $resultMiddle = $thumb->resize($middleWidth, $middleHeight)->save($path.$middleImageName, 'jpg');
		$resultSmall = $thumb->cropFromCenter($smallWidth, $smallHeight)->save($path.$smallImageName , 'jpg');	
		return array(
			'small'=>$smallImageName,
			'middle'=>$middleImageName,
		);
	}
	*/
	
	public static function get_avatar($id, $student=0, $size=50)
	{		
		$static = array(30 => 'tiny', 50=>'small', 100=> 'middle', '200'=>'big');
		isset($static[$size]) || $size=100;
		$result =  Config::get('avatar', 'attach');
		$MD = md5($id);
		$student && $result .= 'student_';
		$result.= "avatar/" . substr($MD,0,2) . "/" . substr($MD,2,2) . "/" .substr($MD,4,2). "/original";
		$result.= $size >0 ? "_200_200" : '';
		//$result.= $size >0 ? "_" . $size ."_" . $size : '';
		$result.= ".jpg";		
		@getimagesize($result) || $result = $url . "/noavatar/" . $static[$size] . ".jpg";
		return $result;
	}

	static function getImageInfo($img) {
        $imageInfo = getimagesize($img);
        if( $imageInfo!== false) {
            $imageType = strtolower(substr(image_type_to_extension($imageInfo[2]),1));
            $imageSize = filesize($img);
            $info = array(
                "width"=>$imageInfo[0],
                "height"=>$imageInfo[1],
                "type"=>$imageType,
                "size"=>$imageSize,
                "mime"=>$imageInfo['mime']
            );
            return $info;
        }else {
            return false;
        }
    }
}