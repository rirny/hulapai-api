<?php
set_time_limit(0);
class import3_Api extends Api
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
		$type = http::post("type", "int", 0);
		$loadFun = 'loadSheet' . ($type ? $type : '');
		$startLine = http::post("start_line", "int", 1);
		$multi = http::post("multi", "int", 0);
		db()->begin();
		try{
			$source = $this->loadExcel($CSV, $multi);
			$success = array();
			$error = array();
			ob_start();
			if($multi) // 多sheet
			{
				foreach($source as $item) // 一个班级
				{
					echo "\n正在导入Sheet[{$item['name']}]";
					$this->format($this->$loadFun($startLine, $item['data']));
					flush();				
				}
			}else{ // 只取一个sheet
				echo "\n正在导入Sheet[{$source['name']}]";
				$this->format($this->$loadFun($startLine, $source['data']));
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
	
	private function format($source = Array())
	{
		$data = array(
			'no' => '', // 学号
			'name' => '', // 姓名
			'parents' => array(
				1 => '', // 本人
				2 => '', // 爸爸
				3 => '', // 妈妈
				4 => '' // 其他
			),
			'birthday' => '0000-00-00', // 生日
			'gender' => 0 // 性别
		);		
		foreach($source as $key => $item)
		{
			extract($data);
			$no = $item[0];
			$name = $item[1];		
			if(strlen($item[2]) == 11 && is_numeric($item[2])) $parents[3] = $item[2]; // 妈妈
			if(strlen($item[3]) == 11 && is_numeric($item[3])) $parents[2] = $item[3]; // 爸爸
			if(strlen($item[4]) == 11 && is_numeric($item[4])) $parents[4] = $item[4]; // 其他
			if(strlen($item[5]) == 11 && is_numeric($item[5])) $parents[1] = $item[5]; // 本人			
			$parents = array_filter($parents);		
			empty($item[6]) || $birthday = $item[6];
			$gender = str_replace(array('M', 'F', '男', '女'), array(1,2,1,2), $item[7]);
			foreach($parents as &$parent)
			{
				$parent = $this->getUser($parent);
			}			
			$student = $this->hasStudent($name, array_values($parents)); // 是否已经有此学生档案
			if(!$student)
			{
				$student = $this->createStudent(compact('name', 'birthday', 'gender')); // 没有则创建档案	
				$newParent = array_values($parents);
				load_model('student')->update(array('creator' => $newParent[0]), $student);
			}		
			if($student)
			{					
				foreach($parents as $key => $val)
				{
					if(!in_array($key, array(1,2,3,4))) $key = 4;
					$this->createRelation($val, $student, $key); // 创建家长关系
				}				
				$this->schoolRelation($student); // 创建机构学生关系
				$success[] = array($name, $item);				
			}else{
				echo "\n" . $i . "\t" . $name . "学生创建关联失败"; continue;
			}	
		}
	}
	
	// No 姓名 性别 (妈妈：1234444444,爸爸：1423444444) 联系方式一列且只有一个
	private function loadSheet($startLine = 1, Array $source)
	{
		$result = Array();
		for($i=$startLine;$i< count($source); $i++)
		{
			array_walk($source[$i], create_function('&$v,$k', '$v=trim($v);'));
			$item = Array();
			$item = array_pad($item, 8, "");
			$item[0] = $source[$i][0];
			$item[1] = $source[$i][1];			
			if(!empty($source[$i][3]))
			{
				list($rs, $phone) = explode("：", $source[$i][3]	);
				$rs = $rs ? str_replace(array('妈妈', '爸爸') , array(2, 3), $rs) : 4;
				if(!in_array($rs, array(2,3,4))) $rs = 4;
				strLen($phone) == 11 && $item[$rs] = $phone;
			}
			$item[7] = $source[$i][2];
			$result[] = $item;
			if($item[1] == '') {echo "\t学生姓名不能为空\n"; continue;}
			if($item[2] == '' && $item[3] == '' && $item[4] == '' && $item[5] == '') {echo "\t联系方式不能为空\n"; continue;}
			$result[] = $item;
		}		
		return $result;
	}	
	
	// 姓名 (F：1234444444,M：1423444444,其他:13124424444,本人：12344444) 联系方式一列且有多个
	private function loadSheet1($startLine = 1, Array $source)
	{
		$result = Array();		
		for($i=1;$i< count($source); $i++)
		{
			array_walk($source[$i], create_function('&$v,$k', '$v=trim($v);'));
			$item = array_pad(array(), 8, "");
			$item[1] = $source[$i][0];
			$contact = str_replace(array('M', 'F', '学生') , array(2, 3, 5), $source[$i][1]);			
			$contactArr = explode("/", $contact);			
			foreach($contactArr as $val)
			{
				list($rs, $phone) = explode(":", $val);
				if(!in_array($rs, array(2,3,4))) $rs = 4;
				$item[$rs] = $phone;				
			}
			if($item[1] == '') {echo "\t学生姓名不能为空\n"; continue;}
			if($item[2] == '' && $item[3] == '' && $item[4] == '') {echo "\t联系方式不能为空\n"; continue;}
			$result[] = $item;
		}		
		return $result;
	}

	// NO 姓名 妈妈 爸爸 其他 生日 性别
	private function loadSheet2($startLine = 1, Array $source)
	{
		$result = Array();		
		for($i=$startLine;$i< count($source); $i++)
		{
			array_walk($source[$i], create_function('&$v,$k', '$v=trim($v);'));
			$item = Array();
			$item = array_pad($item, 8, "");
			$item[0] = $source[$i][0];
			$item[1] = $source[$i][1];
			$item[2] = $source[$i][2];
			$item[3] = $source[$i][3];
			$item[4] = $source[$i][4];			
			$item[6] = $source[$i][5] ? date('Y-m-d', strtotime($source[$i][5])) : '';
			$item[7] = $source[$i][6];			
			if($item[1] == '') {echo "\t学生姓名不能为空\n"; continue;}
			if($item[2] == '' && $item[3] == '' && $item[4] == '') {echo "\t联系方式不能为空\n"; continue;}
			$result[] = $item;
		}		
		return $result;
	}

	// 旧版CSV
	// return array('student', array('phone1', 'phone2'))
	private function loadExcel($file, $multi=false)
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

	public function resetEvent()
	{
		$_Event = load_model('event');
		$_Student_course = load_model('course_student');
		$_Teacher_course = load_model('course_teacher');
		$events = $_Event->getAll(array('status' => 0));		
		foreach($events as $key=>$item)
		{
			$students = db()->fetchAll('select s.id,s.name,s.name_en from t_course_student r left join t_student s on r.student=s.id where r.`event`=' . $item['id']);
			$teachers = db()->fetchAll('select s.`user` id,concat(u.firstname,u.lastname) name,concat(u.firstname_en,u.lastname) name_en from t_course_teacher r left join t_teacher s on r.teacher=s.`user` Left join t_user u on u.id=r.teacher  where r.`event`=' . $item['id']);			
			$_Event->update(array('students' => json_encode($students), 'teachers' => json_encode($teachers)), $item['id']);
		}
	}

}

