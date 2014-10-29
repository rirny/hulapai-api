<?php
set_time_limit(0);
class import_Api extends Api
{
    
    public function __construct() {
        
    }
    
	private $userImportNum = 0;
	private $studentImportNum = 0;
	private $tm = 0;
	private $_school = 0;

    public function student()
    {
		$this->_school = http::post("school", "int", 0);
		if(!$this->_school) throw new exception("机构不能为空！");		
		$source = array();
		if(!isset($_FILES['source'])) throw new exception("请上传Excel文件");
		$CSV = $_FILES['source']['tmp_name'];
		$this->tm = time();
		db()->begin();
		try{
			$source = $this->loadExcel($CSV);
			$success = array();
			$error = array();
			ob_start();			
			foreach($source as $item) // 一个班级
			{
				echo "\n正在导入Sheet[{$item['name']}]";
				$this->loadSheet($item['name'], $item['data']);
				flush();				
			}
			echo "\n成功创建" . $this->userImportNum ."个用户，学生档案" . $this->studentImportNum . "\n";	
			ob_end_flush();
			db()->commit();			
		}catch(exception $e)
		{
			echo "导入失败！" . $e->getMessage();
			db()->rollback();
		}		
	}
	
	private function loadSheet($grade, $source)
	{
		if(empty($source)) return false;
		$title = $source[0];
		
		echo $title[0] . "\t" . $title[1]. "\t" . $title[2]. "\t" . $title[3] . "\n";
		if($title[0] == '' || $title[1] == '' || $title[2] == '' || $title[3] == '' )
		{
			echo "\t数据格式不正确\n";
			return false;
		}
			
		$grade = $this->getGrade($grade);
		$studentArr = $error = array();	
		for($i=1;$i< count($source); $i++)
		{		
			$name = "";					
			if(empty($source[$i][0])) 
			{
				echo "\n" . $i . "行\t学生姓名不能为空！\n"; 
				continue;
			}else{
				$name = $source[$i][0];
			}			
			$parents = array(); // 家长
			(isset($source[$i][1]) && strlen($source[$i][1]) == 11) && $parents[3] = $this->getUser($source[$i][1]);
			(isset($source[$i][2]) && strlen($source[$i][2]) == 11) && $parents[2] = $this->getUser($source[$i][2]);	
			(isset($source[$i][3]) && strlen($source[$i][3]) == 11) && $parents[4] = $this->getUser($source[$i][3]);

			$birthday = ''; // 生日 性别
			$gender = 0;
			
			if(empty($parents))
			{
				echo "\n" . $i . "\t" . $name . "\t联系方式不能为空"; continue;
			}

			$student = $this->hasStudent($name, array_values($parents)); // 是否已经有此学生档案
			if(!$student)
			{
				$student = $this->createStudent(compact('name', 'birthday')); // 没有则创建档案	
				$newParent = array_values($parents);
				load_model('student')->update(array('creator' => $newParent[0]), $student);
			}
			
			if($student)
			{					
				foreach($parents as $key => $val)
				{
					if(!in_array($key, array(1,2,3,4))) $key == 4;
					$this->createRelation($val, $student, $key); // 创建家长关系
				}
				// 学校relation
				$this->schoolRelation($student); // 创建机构学生关系
				$grade && $this->gradeRelation($grade, $student); // 班级关系
				$success[] = array($name, $sourceData);				
			}else{
				echo "\n" . $i . "\t" . $name . "学生创建关联失败"; continue;
			}	
		}
		echo "\n导入" . count($success) . "条记录";		
		return true;
	}

	// 旧版CSV
	// return array('student', array('phone1', 'phone2'))
	private function loadExcel($file)
	{
		$result = array('rows' => 0, 'cols' => 0, 'data' => array());	
		require_once LIB . '/PHPExcel.php';
		$objReader = new PHPExcel_Reader_Excel2007(); //use excel2007
		try
		{
			$objPHPExcel = $objReader->load($file); //指定的文件
			$sheetCount = $objPHPExcel->getSheetCount();
			$sheetNames = $objReader->listWorksheetNames($file);			
			$sheet = $objPHPExcel->getSheet(0)->toArray();			
			$result = Array();
			foreach($sheetNames as $key => $item)
			{				
				$data = $objPHPExcel->getSheet($key)->toArray();
				if(empty($data)) continue;
				$name = $item;
				foreach($data as $c => $val)
				{
					if(empty($val) || empty($val[0])) unset($data[$c]);
				}
				$result[$key] = compact('name', 'data');
			}
		}catch(Exception $e)
		{
			return $result;
		}		
		return $result;
	}

	private function getUser($account)
	{
		if($account == '' || strlen($account) != 11) return 0; // 非手机号
		$user =  load_model('user')->getRow(array('account' => $account));
		if(!$user)
		{
			$data = array(
				'account' => $account,
				'password'=> md5(md5('000000') . 12345),
				'login_salt' => 12345,
				'create_time'=> $this->tm
			);			
			$data['setting'] = json_encode(array(
				"hulaid" => 0,
                "friend_verify" => 1,
                "notice" => array(
                    "method" => 0,
                    "types" => "1,2,3,4,5"
				)
			));						
			$id = load_model('user')->insert($data);
			$this->userImportNum ++;
			load_model('user')->update(array('hulaid' => 'h_' . sprintf("%u", crc32($id))), $id);
		}else{
			$id = $user['id'];
		}
		return $id;
	}

	public function createStudent($data = array())
	{
		if(is_array($data))
		{
			extract($data);
		}else{
			$name = $data;
		}
		if($name == '') return false;

		load('ustring');
		$name_en = Ustring::topinyin($name);
		$this->studentImportNum ++;
		
		$data = array_merge($data, array(			
			'name_en' => $name_en,
			'create_time' => $this->tm
		));
		
		if(!empty($data['birthday']))
		{
			$data['birthday'] = str_replace(array('年', '月', '日'), array("-", "-", ""), $data['birthday']);
			$data['birthday'] = str_replace("/", "-", $data['birthday'] );			
		}else{
			$data['birthday'] = "0000-00-00";
		}
		return load_model('student')->insert($data);		
	}
	
	private function createRelation($user, $student, $relation=2)
	{
		$res = load_model('user_student')->getRow(array('user' => $user, 'student' => $student, 'relation' => $relation));
		if(!$res)
		{			
			load_model('user_student')->insert(array('user' => $user, 'student' => $student, 'relation' => $relation, 'create_time' => $this->tm));
		}
	}
	
	private function schoolRelation($student)
	{
		$res = load_model('school_student')->getRow(array('school' => $this->_school, 'student' => $student));
		if(!$res)
		{
			load_model('school_student')->insert(array('school' => $this->_school, 'student' => $student, 'create_time'=> $this->tm));
		}
	}

	private function gradeRelation($grade, $student)
	{
		if(!$grade || !$student) return false;
		$res = load_model('grade_student')->getRow(array('grade' => $grade, 'student' => $student, 'school' => $this->_school));
		if(!$res)
		{
			load_model('grade_student')->insert(array('grade' => $grade, 'student' => $student, 'school' => $this->_school, 'create_time'=> $this->tm));
		}
	}

	private function hasStudent($student, $users)
	{
		if(!$student) return false;
		$existStudent = load_model('student')->getRow(array('name' => $student, 'creator,in' => $users));
		if($existStudent)
		{
			return $existStudent['id'];
		}
		return false;
	}

	private function getGrade($grade)
	{
		if($grade =='') return 0;
		$_Grade = load_model('grade');
		$res = $_Grade->getColumn(array('name'=> $grade, 'school' => $this->_school), 'id');
		if(empty($res[0])) return 0;
		return $res[0];
	}
}

