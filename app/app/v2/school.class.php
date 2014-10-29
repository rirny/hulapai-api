<?php
set_time_limit(0);
// 课程 招生
class School_Api extends Api
{

	public function __construct(){
		$cache = Http::post('tm', 'int', 0);
		$this->cache = $cache ? false : true;
	}
	/*
	 [1] => Array
		(
			[0] => 序号
			[1] => 机构名称
			[2] => 教学内容
			[3] => 地址
			[4] => 行政区域
			[5] => 运营类型         
			[6] => 规模
			[7] => 负责人姓名
			[8] => 负责人职位
			[9] => 联系方式
			[10] => 联系方式二
			[11] => 照片
		)

	[2] => Array
		(
			[0] => 1
			[1] => 群贤文化艺术教育中心
			[2] => 艺术-乐器
			[3] => 中山北路2612号（近金沙江路）
			[4] => 普陀区
			[5] => 个体
			[6] => 50
			[7] => 章老师
			[8] => 教学主管
			[9] => 18019213615
			[10] => 
			[11] => 
		)
	*/
	public function import()
	{	
		db()->begin();
		try{		
			if(!Http::is_post()) throw new Exception('操作错误！');	
			if(empty($_FILES) || $_FILES['upfile']['error'] !=0 ) throw new Exception('文件格式错误！');				
			$file = $_FILES['upfile']['tmp_name']; // 验证			
			$source = $this->loadExcel($file, false);
			foreach($source['data'] as $key => $data)
			{				
				if($key < 2) continue;
				//if($key > 5) break;				
				$source = $data[0];
				$name = $data[1];
				if(!$name) continue; // 机构名不能为空
				$address = trim($data[3]) ? trim($data[3]) : '';
				if($address) // 相同的机构过滤
				{
					if(load_model('school')->getRow(array('name' => $this->_addslashes($name), 'address' => $this->_addslashes($address)))) continue;
				}else{
					if(load_model('school')->getRow(array('name' =>  $this->_addslashes($name)))) continue;
				}

				// 教学内容
				$_course = $description = $data[2];
				if(strpos($_course, "-"))
				{
					$_tmp = explode("-", $_course);
					$_course = $_tmp[1];					
				}
				$course = Array();
				if($_course)
				{
					$_course = trim(str_replace(array("，","、", "+"), ",", $_course));
					$_course = trim(str_replace("；", ";", $_course));
					$courseArr = explode(";", $_course);
					foreach($courseArr as $item)
					{
						$parent = $child = '';
						if(strpos($item, "/"))
						{
							list($parent, $child) = explode("/", $item);
						}else{
							$parent = $item;
						}
						if(!$parent && !$child) continue;						
						$parentObj = Null;
						if($parent)
						{
							$parentObj = load_model('course_type')->getRow(array("name" => $parent, 'pid' =>0));
						}
						if($child = trim($child))
						{
							$childs = explode(',', $child);
							foreach($childs as $_v)
							{
								$childObj = load_model('course_type')->getRow(array("name" => $_v, 'pid,>' => 0));
								if($childObj)
								{
									$course[]= array(
										'type' => $childObj['id'],
										'name' => $_v
									);
								}else if($parentObj){
									$course[]= array(
										'type' => $parentObj['id'],
										'name' => $_v
									);
								}else{
									$course[]= array(
										'type' => 10, // 其他
										'name' => $_v
									);
								}
							}
						}else if($parentObj)
						{
							$course[]= array(
								'type' => $parentObj['id'],
								'name' => $parentObj['name']
							);
						}
					}					
				}
				
				$lng = $lat = '0.00000000';
				if($address)
				{
					load('map');
					$location = Map::getCoordsFromAddress('上海市', $address);
					$location && list($lng, $lat) = $location;
				}				
				$province= 310000;
				$city = 310100; // 区县
				$area = current(load_model("area")->getColumn(array('title' => $data[4], 'id,>' => 310000, 'id,<' => 330000), 'id'));
				$_type = $data[5];
				$types = array('机构类型', '私人机构', '品牌加盟', '品牌直营');
				switch($_type)
				{
					case '个体':					
					case '独营':
					case '私教':
						$type = 1;
						break;
					case '连锁':						
					case '连锁模式':						
					case '品牌加盟':
						$type = 2;
						break;
					case '品牌直营':
						$type = 3;
						break;
				}
				$extent = trim($data[6]) ? trim($data[6]) : '';				
				$contact = trim($data[7]) ? trim($data[7]) : '';						
				$position = trim($data[8]) ? trim($data[8]) : '';
				$tel = '';
				$data[9] && $data[9] = trim(str_replace(array("，","；", "/"), array(",", ",", ","), $data[9]));
				$data[10] && $data[10] = trim(str_replace(array("，","；", "/"), array(",", ",", ","), $data[10]));
				$data[9] && $tel .= $data[9];
				$data[10] && $tel .= ($tel ? "," : '') . $data[10];
				$telArr = explode(",", $tel);
				$phone = $phone2 = '';
				$telArr = array_filter($telArr, function($v){if(is_numeric($v)) return $v;});
				if(count($telArr) > 1)
				{
					list($phone, $phone2) = $telArr;
				}else if($telArr)
				{
					$phone = current($telArr);
				}
				$creator = 2;$create_time = time();
				
				$code = "SH_". date('md') . rand(10000, 99999);
				$valid = 1;
				$insert = compact('source', 'name', 'address', 
					'lng', 'lat', 'province', 'city', 'area',
					'type', 'extent', 'contact', 'phone', 'phone2', 'position',
					'creator', 'description', 'create_time','code','valid'
				);				
				$id = load_model('school')->insert($this->_addslashes($insert));				
				if($id)
				{					
					foreach($course as $val)
					{
						$res = load_model('course')->insert(array(
							'type' => $val['type'],
							'title'=> $val['name'],
							'school' => $id,
							'operator' => 2,
							'create_time' => time()
						));
					}
				}		
			}			
			db()->commit();
			Out(1, $message, $result);			
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());			
		}
		
	}

	private function _addslashes($data)
	{
		if(is_array($data))
		{
			foreach($data as &$item)
			{
				$item = addslashes($item);
			}			
		}else if(is_string($data)){
			$data = addslashes($data);
		}
		return $data;
	}
	
	private function loadExcel($file, $multi=false)
	{
		$result = array('rows' => 0, 'cols' => 0, 'data' => array());	
		require_once LIB . '/PHPExcel.php';		
		$objReader = new PHPExcel_Reader_Excel2007();
		if(!$objReader->canRead($file)){
			$objReader = new PHPExcel_Reader_Excel5();
			if(!$objReader->canRead($file)) throw new Exception('文件格式错误！');			
		}
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


	public function lng()
	{
		$schools = load_model('school')->getAll(array('creator,>' => 2, 'lng,<' => 1));
		load('map');		
		foreach($schools as $item)
		{
			if($address = $item['address'])
			{
				$lng = $lat = 0;
				$location = Map::getCoordsFromAddress('上海市', $address);
				$location && list($lng, $lat) = $location;
				if($lng)
				{
					load_model('school')->update(array('lng' => $lng, 'lat' => $lat), $item['id']);
				}
			}
		}		
	}

	public function unlogin()
	{
		$schools = array(48,68,69,70);
		foreach($schools as $school)
		{
			$students = load_model('school_student')->getCloumn(array('school' => $school), 'student');
			$users = load_model('user_student')->getCloumn(array('student,in' => $students), 'user');
			$items = load_model('user')->getAll(array('id,in' => $users, 'login_times,<' => 1), '', '', false, false, 'mobile,concat(firstname,lastname)');
		}
	}


	public function load2()
	{
		
		$school =	array(
			'EF' => array(
				'name' => 'EF少儿英语',
				'web' => 'http://zt.114study.com/efkid/',
				'description' => '英孚——值得信赖的青少儿英语培训专家，优秀的师资是我们教学体系的核心，英孚在美、英、澳、加设有专业招聘中心，仅有6%的候选人能通过严苛的录取流程进入英孚执教。我们承诺，英孚所有外教均持有TEFL和剑桥TKT双证上岗。与全球顶尖学府展开国际学术合作，效培养英语优等生，英孚相继与剑桥、北大、哈佛、莫大等国际国内顶级学府开展教学研究，调研中国学生的英语学习习惯，探索适合中国学生的高效英语学习方法，更好帮助中国学生快速有效提高英语成绩和综合技能。 有目共睹的教学成果，89%的英孚学员在公立学校的英语考试中成绩名列前茅。95%的学员家长表示，通过在英孚的学习，孩子自信沟通和流利表达有所进步。 '
			),
			'BL' => array(
				'name' => '贝乐学科英语',
				'web' => 'http://www.beile.com/address/city_169.html',
				'description' => '贝乐学科英语成立于2008年，是一家以“浸入式学科英语”为教学法的少儿英语培训机构，其全面引进了美国最先进的教学理念、美国K12教育体系、原汁原味的"美国幼儿园"（2-6岁）、"美国小学"（7-12岁）主流核心课程体系、国际资质认证的外教老师、雄厚的研发团队以及完整的教学监督与培训系统，保证了最优秀、最专业的教学，让孩子不出国门即可"留学美国"。目前贝乐学科英语已在全国共成立了30多家分中心，服务于20,000多个家庭。 '
			),
			'WS' => array(	
				'name' => '华尔街',
				'web' => 'http://www.wsi.com.cn/cn/index.html',
				'description' => '华尔街英语由李文昊博士（外语语言学博士）在 1972 年创立于意大利，是成人英语培训的全球领先者，总部设在美国马里兰州巴尔的摩。作为国际知名品牌，华尔街英语已在全球 26 个国家和地区拥有逾 450 家中心，帮助全球 200 多万名学员成功提升英语实用能力。\n华尔街英语获得了 ISO9001:2000 认证，而剑桥大学 ESOL 考试最近的研究结果更表明，华尔街英语课程级别的等级划分方式与欧洲语言共同参照框架（CEFR）的英语水平分级方式精准对应。2010 年，华尔街英语再次获得 ISO9001:2008 认证，进一步确立了其全球公认的高品质英语培训提供者的地位。\n华尔街英语（中国）已在北京、上海、广州、深圳、天津、青岛、杭州、南京、苏州、无锡、佛山等多个顶级城市开设了逾 66 家学习中心，全部中心均为公司直营，从而确保每一家中心向学员提供同样高品质的学习体验。\n华尔街英语（国际）是华尔街英语在全球其他国家和地区运营所使用的名称，我们在亚洲、欧洲、中东、拉美等 26 个国家和地区拥有逾 450 家加盟学习中心。目前其主要业务覆盖范围有法国、意大利、土耳其、智利、委内瑞拉、哥伦比亚、韩国和中国香港。华尔街英语国际在德国等地也设有部分自营学习中心，主要以旗舰店的形式作为新产品测试及加盟学习中心最佳案例分享的基地。柯大卫先生 (Mr. David Kedwards) 是华尔街英语的全球总裁。\n华尔街英语（中国）是全球领先的教育培训巨擘英国培生集团旗下子公司。培生集团于 1724 年创立，是全球领先的英语语言教学内容出版商和供应商，发行渠道遍及全球 70 多个国家和地区，其教育产品及服务更惠及全球逾 1 亿人士。培生集团已在纽约证交所及伦敦证交所两地上市，在 70 多个国家和地区拥有 40,000 多名员工。培生集团旗下云集众多世界知名企业，在中国出版了著名的《朗文英语词典》及《新概念英语》等众多英语教学及培训书籍和字典产品，是家喻户晓的国际品牌。'
			),
			'XDF' => array(	
				'name' => '新东方',
				'web' => 'http://sh.xdf.cn/pop/',
				'description' => '新东方，全名北京新东方教育科技（集团）有限公司，总部位于北京市海淀区中关村，是目前中国大陆规模最大的综合性教育集团，同时也是全球最大的教育培训集团。公司业务包括外语培训、中小学基础教育、学前教育、在线教育、出国咨询、图书出版等各个领域。除新东方外，旗下还设有优能中学教育、泡泡少儿教育、精英英语、前途出国咨询、迅程在线教育、大愚文化出版、满天星亲子教育、同文高考复读等子品牌。公司于2006年在美国纽约证券交易所上市，是中国大陆第一家在美国上市的教育机构。'
			),

			'OY' => array(	
				'name' => '昂立',
				'web' => 'http://www.onlyedu.net/angli.asp',
				'description' => '“昂立国际教育”是上海交大昂立教育集团推出的一个教育品牌，由上海昂立教育投资咨询有限公司负责推广发展。昂立国际教育依托上海交大昂立教育集团的强大的资源优势，百年名校雄厚的师资、丰富的教育资源，积极探索、大胆创新，在少儿英语教学上独辟蹊径，通过与国际上多所著名大学的合作，建立起一套适合中国孩子学习英语的系列教材体系和科学的教学模式。以“让越来越多的孩子，享受学习英语的快乐、自信、激情”为使命，以“卓越的英语培训品牌、覆盖全国的英语培训学校”为目标，提出了“不走弯路、快乐进步”的教学口号。目前，昂立国际教育凭借强大的品牌实力、独特的教研成果、成熟的市场运营手段，截至到2012年三月，昂立教育提供咨询服务的学校遍布北京、上海、天津、重庆四大直辖市及全国1000多个城市，签约2000家，已开出1700家，年培训学生超过百万人，已成为全国外语培训行业教学质量最突出、发展速度最快、遍及地区最广、家长最满意的知名教育机构，堪称中国外语培训行业的航空母舰。'
			),
			'1S' => array(	
				'name' => '昂立',
				'web' => 'http://www.1smartchina.com/',
				'description' => '精锐教育[1]是中国领先的高端教育连锁集团，由哈佛、北大精英创立，并由全球著名投资集团贝恩资本注资，专注于培养18岁以下孩子的学习力，成就辉煌未来。自创立伊始，精锐即秉持国际化管理理念和连锁化发展模式，立志成为中国教育行业最受尊敬的品牌。旗下拥有精锐1对1、至慧学堂、精锐.佳学慧、精锐.学汇趣等子品牌。精锐结合东西方教育的优势，致力于打造快乐高效的第三课堂，创新性提出“以学为主、以教为辅、主动学习、趣味互动”的教学四项基本原则。与此同时，精锐教育与北大教育学院达成多项共识并签署相关协议。双方将结合教学研发最前沿科技成果，一起升级全新针对0-18岁孩子学习力体系和该阶段教师培训体系，双管齐下提升学生学习的自信和兴趣。精锐坚信学习力成就未来，致力于提升中国个性化教育在全球市场的竞争力！'
			),
			'LW' => array(	
				'name' => '龙文',
				'web' => 'http://www.longwenedu.com/',
				'description' => '龙文教育成立于1999年，是由海淀教委特批的个性化教育机构。自创立龙文教育  龙文教育[1]伊始，龙文教育以“良心办学、诚信办学”为发展基础，配有专业、强大的教研团队，能够准确把握当前教育政策、把握考试命题走向，为学生成功提供有力的保障。截至2012年5月，龙文教育已有1200余家分校区，遍及全国55个大中城市，业务范围涵盖了中小学专业个性化辅导、高考复读、幼教、图书出版、出国留学等诸多领域，共培养了近80万名学员。这组令其他培训机构望尘莫及的数字证实龙文社会责任感的体现：“创建分校的目的就是为了方便孩子能够就近入学。”而对于此次签约合作，韩超表示，“此次与倪萍老师签约是龙文教育集团国际化发展战略的重要举措，对加强教育服务职能、提升品牌品质、扩大个性化教育的影响力、强化企业社会责任有重要意义。'
			),
			'XES' => array(	
				'name' => '学而思',
				'web' => 'http://www.xueersi.com/',
				'description' => 'TAL Education Group 好未来 (NYSE:XRS)，英文缩写：TAL（Tomorrow Advancing Life），是一家中国领先的教育科技企业，以科技驱动、人才亲密、品质领先为发展的核心目标。自创立以来，一直致力于促进科技互联网与教育融合，为孩子创造更美好的学习体验。10余年来，好未来专注在中小学及幼儿教育领域，旗下拥有五个主品牌：学而思培优、智康1对1、摩比思维馆、学而思网校和e度教育网。其中，学而思培优作为K12高端培优教育平台，下设三个子品牌：学而思理科、乐加乐英语和东学堂语文。每年，在全国15个城市，有50余万学员走进好未来的课堂，另有30万学员通过网校获取优质的教育资源。另外，好未来旗下的E度教育网是国内覆盖面广、可信度高的教育互联网信息平台，月度活跃用户达2800万人。2010年10月20日，好未来的前身学而思在美国纽交所正式挂牌交易（NYSE:XRS），成为国内首家在美上市的中小学教育机构。'
			),

			'WEB' => array(	
				'name' => '韦博',
				'web' => 'http://shanghai.webi.cn/index.aspx',
				'description' => '韦博国际英语创立于1998年，是韦博教育旗下成人高端英语培训品牌，为成人和企业提供优质英语培训服务。时至今日，韦博国际英语已发展壮大成为了国内规模最大，首个中心数量突破100家的成人英语培训品牌。韦博国际英语课程体系以实用为导向，注重英语听、说等实用型英语技能的培养。由浅入深的8个级别课程设置，更加灵活自由的学习方式，通过外籍教师小班授课，先进的多媒体教育技术，丰富多彩的英语沙龙和课外活动......韦博国际英语创造沉浸式英语学习氛围，帮助学员实现英语水平的全新突破。韦博国际英语不仅是英语技能的进阶平台，更是开拓职场、生活新天地的精彩舞台。韦博国际英语倡导“Better English Better Life”。从流利英语到点亮人生梦想，韦博国际英语不仅帮助学员们开口说英语，更帮助学员们获得出色的职场能力、更丰富的商务社交、更宽广的国际化视野......为无数人的职场梦想插上双翼，勾勒更加精彩的未来。截至2012年，已在全国57个城市开设了140多家培训中心，拥有超过7000名雇员。'
			),

			'LG' => array(	
				'name' => '绿光',
				'web' => 'http://www.lvguang.net',
				'description' => '绿光教育(Lvguang Education)成立于2001年，是上海青少儿教育领军品牌，上海市少科站合作伙伴。绿光教育秉承“因为只做青少儿，所以我们更专业”的教育理念，针对3-17岁青少儿开设外教口语、考证类课程、幼小衔接课程及思维训练等，办学10年培训学员达到25万以上，培养了一大批德才兼备、成绩突出的优秀学子。同时拥有雄厚的师资力量，完善的教学体系、独特的教学风格，到2013年在全国拥有22家分校。'
			),

			'JBB' => array(	
				'name' => '金宝贝',
				'web' => 'http://www.gymboree.com.cn',
				'description' => '1976年成立于美国，目前在美国、英国、加拿大、瑞士、法国、亚洲等全球37个先进的国家及地区，成立有700多家早教育儿中心。Gymboree金宝贝，是0-5岁幼儿最快乐的天地，系统地开发幼儿潜能，提供寓教于乐的学习课程，我们拥有近40年育儿经验，指导父母与孩子一起学习成长。'
			),

			'GDB' => array(	
				'name' => '吉的堡',
				'web' => 'http://enschool.kidcastle.com.cn',
				'description' => '1976年成立于美国，目前在美国、英国、加拿大、瑞士、法国、亚洲等全球37个先进的国家及地区，成立有700多家早教育儿中心。Gymboree金宝贝，是0-5岁幼儿最快乐的天地，系统地开发幼儿潜能，提供寓教于乐的学习课程，我们拥有近40年育儿经验，指导父母与孩子一起学习成长。'
			),
			'DSN' => array(
				'name' => '迪斯尼',
				'web' => 'http://www.disneyenglish.com',
				'description' => '迪士尼英语致力于让孩子们能够通过流利的英语跨越国家与文化的界限交流，在迪士尼英语，我们提供高效的学习体验，结合融入式内容、创新技术和引人入胜的课堂体验，鼓励孩子们用自己的语言充满自信地与世界进行交流。'
			)
		);

		$branch =	array(			
			'JBB' => array(
				array('name'=> '金宝贝上海古北中心', 'address'=> '上海市长宁区虹桥路1665号星空广场A栋3层301-303 ', 'phone'=>'138-1802-7326，021-62784727', 'phone2' => '4007009090', 'web' => 'http://www.gymboree.com.cn/gubei/'),
				array('name'=> '金宝贝上海浦东96广场中心', 'address'=> '上海市浦东新区东方路796号陆家嘴96广场B111-B112a ', 'phone'=>'021-61001200', 'phone2' => '', 'web' => 'http://www.gymboree.com.cn/pudong/'),
				array('name'=> '金宝贝上海徐汇东中心', 'address'=> '上海市徐汇区天钥桥路333号腾飞大厦3楼301 ', 'phone'=>'021-61213618', 'phone2' => '', 'web' => 'http://www.gymboree.com.cn/xuhuidong/'),
				array('name'=> '金宝贝上海黄浦中心', 'address'=> '上海市黄浦区河南南路489号香港名都3楼 ', 'phone'=>'021-63353595', 'phone2' => '', 'web' => 'http://www.gymboree.com.cn/huangpu/'),
				array('name'=> '金宝贝上海普陀中心', 'address'=> '上海市普陀区交暨路185号6号楼1楼（兴远置业） ', 'phone'=>'021-66097500，66090101', 'phone2' => '', 'web' => 'http://www.gymboree.com.cn/putuo/'),
				array('name'=> '金宝贝上海锦江乐园中心', 'address'=> '徐汇区桂平路188号康健休闲广场2楼 ', 'phone'=>'021-54197107', 'phone2' => '', 'web' => 'http://www.gymboree.com.cn/jinjiangleyuan/'),
				array('name'=> '金宝贝上海松江中心', 'address'=> '松江新城区文诚路378弄B区4楼 ', 'phone'=>'021 - 67752002', 'phone2' => '', 'web' => 'http://www.gymboree.com.cn/songjiang/'),
				array('name'=> '金宝贝上海浦东金桥翡翠坊中心', 'address'=> '上海市台儿庄路255号金桥翡翠坊2楼 ', 'phone'=>'021-68519969', 'phone2' => '', 'web' => 'http://www.gymboree.com.cn/feicuifang/'),
				array('name'=> '金宝贝上海青浦中心', 'address'=> '上海市青浦区公园东路1590号3层 ', 'phone'=>'021-33867230', 'phone2' => '', 'web' => 'http://www.gymboree.com.cn/qingpu/'),
				array('name'=> '金宝贝上海虹口中心', 'address'=> '上海市西江湾路388号 虹口凯德龙之梦B座3楼-11 ', 'phone'=>'021-56311233', 'phone2' => '', 'web' => 'http://www.gymboree.com.cn/longzhimeng/'),
				array('name'=> '金宝贝上海五角场创智中心', 'address'=> '上海市淞沪路270号3号楼一层03-12单元 ，创智天地 ', 'phone'=>'021-65109969', 'phone2' => '', 'web' => 'http://www.gymboree.com.cn/chuangzhi/'),
				array('name'=> '金宝贝嘉定疁城中心', 'address'=> '上海市嘉定区塔城路295号4幢6号楼（近博乐路） ', 'phone'=>'021-60525232', 'phone2' => '', 'web' => 'http://www.gymboree.com.cn/jiading/'),
			),
			'GDB' => array(
				array('name'=> '吉的堡少儿英语上海虹口区海伦教学点', 'address'=> '上海市虹口区四平路95号2楼（新华书店2楼 ', 'phone'=>'021-51082275 6307-0159', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-NTK45574.html'),
				array('name'=> '吉的堡少儿英语上海虹口区凉城教学点', 'address'=> '上海市虹口区水电路592号1-2楼 ', 'phone'=>'021-31266867，33627383，33627382，33627381', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-OVAC6535.html'),
				array('name'=> '吉的堡少儿英语上海黄埔区制造局路教学点', 'address'=> '上海市黄浦区制造局路287号3楼（近斜土路） ', 'phone'=>'021-31263393', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-7IMN8450.html'),
				array('name'=> '吉的堡少儿英语上海黄埔区复兴东路教学点', 'address'=> '上海市复兴东路699号三楼（近河南南路） ', 'phone'=>'021-31263396', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-PJRM2934.html'),
				array('name'=> '吉的堡少儿英语上海闵行区莘庄教学点', 'address'=> '上海闵行区莘建东路399弄26支弄20号（地铁一号线莘庄站北广场西众众家园步行街内', 'phone'=>'021-64140217，     021-64144017', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-SPBD1102.html'),
				array('name'=> '吉的堡少儿英语上海闵行区金汇教学点', 'address'=> '上海市闵行区吴中路1366号 奥克拉大楼202室 ', 'phone'=>'021-61517971', 'phone2' => '', 'web' => 'http://shaoer.kidcastle.com.cn/prc_list.php'),
				array('name'=> '吉的堡少儿英语上海闵行区七莘教学点', 'address'=> '上海市闵行区七莘路1706号 ', 'phone'=>'021-54149796，  021-51035929', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-G6H42515.html'),
				array('name'=> '吉的堡少儿英语上海闵行区中春教学点', 'address'=> '上海市闵行区中春路3416号 ', 'phone'=>'021-52212601', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-3HBB8120.html'),
				array('name'=> '吉的堡少儿英语上海闵行区都市教学点', 'address'=> '上海市闵行区都市路3653号 ', 'phone'=>'021-51695596', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-OJBY8393.html'),
				array('name'=> '吉的堡少儿英语上海徐汇区古美路教学点', 'address'=> '上海市嘉定区金沙江西路1069号11层 ', 'phone'=>'021-67086888', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-23411625.html'),
				array('name'=> '吉的堡少儿英语上海闵行莲花南路教学点', 'address'=> '上海市闵行区莲花南路1500号 ', 'phone'=>'021-51083862', 'phone2' => '', 'web' => ''),
				array('name'=> '吉的堡少儿英语闵行江川路教学点', 'address'=> '上海市闵行区江川路344号4楼C区 ', 'phone'=>'021-51036160 ', 'phone2' => '', 'web' => ''),
				array('name'=> '吉的堡少儿英语上海浦东新区福山教学点', 'address'=> '上海市浦东新区福山路99号2-3楼 ', 'phone'=>'021-58208988', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-ZP224766.html'),
				array('name'=> '吉的堡少儿英语上海浦东新区金桥教学点', 'address'=> '上海市浦东新区张杨北路469号 ', 'phone'=>'021-31263373', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-U5SZ7276.html'),
				array('name'=> '吉的堡少儿英语上海浦东新区北洋泾教学点', 'address'=> '上海市浦东新区博山路252号2楼 ', 'phone'=>'021-51082239', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-8CM38688.html'),
				array('name'=> '吉的堡少儿英语上海浦东新区南汇教学点', 'address'=> '上海市浦東新區惠南鎮人民東路2523弄48號 ', 'phone'=>'021-68245592', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-YU986248.html'),
				array('name'=> '吉的堡少儿英语上海浦东新区上南教学点', 'address'=> '上海市浦东新区杨高南路2875号康琳创意园1号楼2楼', 'phone'=>'021-58749199 ', 'phone2' => '', 'web' =>'http://enschool.kidcastle.com.cn/index-L4QZ8322.html'),
				array('name'=> '吉的堡少儿英语上海浦东新区康桥教学点', 'address'=> '上海市浦东新区秀康路900号2楼（康桥邮电局西侧）', 'phone'=>'021-58122267，  021-20919307，  021-20919207', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-MULF5782.html'),
				array('name'=> '吉的堡少儿英语上海浦东新区涵合园教学点', 'address'=> '上海市浦东新区东绣路1249-1253号 ', 'phone'=>'021-50599145', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-1I939074.html'),
				array('name'=> '吉的堡少儿英语上海浦东新区周浦教学点', 'address'=> '上海市浦东新区川周公路4387号104号楼5楼 ', 'phone'=>'021-20948756-11   ，20948758 -11', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-5EM59850.html'),
				array('name'=> '吉的堡少儿英语上海浦东新区川沙教学点', 'address'=> '上海市浦东新区川沙镇南桥路1042-1046号（靠近妙境路南桥路） ', 'phone'=>'021-58925923', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-ZDEN8981.html'),
				array('name'=> '吉的堡少儿英语上海浦东新区金桥教学点', 'address'=> '上海市浦东新区张杨北路469号 ', 'phone'=>'021-31263373', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-U5SZ7276.html'),
				array('name'=> '吉的堡少儿英语上海浦东新区巨峰路教学点', 'address'=> '上海市浦东新区张杨北路1153号2楼 ', 'phone'=>'021-51872380, 021-50530226, 021-50530223 ', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-717K8181.html'),
				array('name'=> '吉的堡少儿英语上海浦东新区博佳花园教学点', 'address'=> '上海市浦东新区听潮路2号102、202室 ', 'phone'=>'021-51021951', 'phone2' => '', 'web' => ''),
				array('name'=> '吉的堡少儿英语上海徐汇区枫桥教学点', 'address'=> '上海市斜土路1579号1号楼(泰康大厦）7楼', 'phone'=>'021-64037712', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-PC7N4023.html'),
				array('name'=> '吉的堡少儿英语上海徐汇区斜土路教学点', 'address'=> '上海市徐汇区斜土路2669号英雄大厦6楼604-605 ', 'phone'=>'021-54257721', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-S9RF3721.html'),
				array('name'=> '吉的堡少儿英语上海市徐汇区桂林路教学点', 'address'=> '上海市徐汇区桂林路46号5楼', 'phone'=>'021-54184646', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-W7RJ3526.html'),
				array('name'=> '吉的堡少儿英语上海徐汇区桂平路教学点', 'address'=> '上海徐汇区桂平路188号2楼 ', 'phone'=>'021-54190122', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-3N689388.html'),
				array('name'=> '吉的堡少儿英语上海徐汇区华泾教学点', 'address'=> '上海市徐汇区华发路230号3楼 ', 'phone'=>'021-60793502', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-VZ3X1509.html'),
				array('name'=> '吉的堡少儿英语上海杨浦区控江教学点', 'address'=> '上海市杨浦区控江路1555号A座9楼906-909 ', 'phone'=>'021-65140278', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-KV2I7807.html'),
				array('name'=> '吉的堡少儿英语上海杨浦区翔殷教学点', 'address'=> '上海市杨浦区翔殷路588号 ', 'phone'=>'021-65332699', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-3GVI6302.html'),
				array('name'=> '吉的堡少儿英语上海杨浦区延吉教学点', 'address'=> '上海市杨浦区沧州路85号2楼（近延吉中路） ', 'phone'=>'021-51695590， 021-55136981', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-3OZ56492.html'),
				array('name'=> '吉的堡少儿英语上海杨浦区中原教学点', 'address'=> '上海市杨浦区中原路282号2楼 ', 'phone'=>'021-31001362', 'phone2' => '', 'web' => ''),
				array('name'=> '吉的堡少儿英语上海闸北区共和新路教学点', 'address'=> '上海市闸北区共和新路2305号 ', 'phone'=>'021-31263398', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-HOTU4498.html'),
				array('name'=> '吉的堡少儿英语上海闸北区原平教学点', 'address'=> '上海市闸北区原平路165号1-2楼（近晋城路口） ', 'phone'=>'021-51695393 ，021-32567236', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-3K8X3183.html'),
				array('name'=> '吉的堡少儿英语上海闸北区彭浦新村教学点', 'address'=> '上海市闸北区共和新路4432号一楼（近临汾路） ', 'phone'=>'021-31263386', 'phone2' => '', 'web' => ''),
				array('name'=> '吉的堡少儿英语上海闸北区平型关路教学点', 'address'=> '上海市闸北区平型关路385号二楼 ', 'phone'=>'021-31263391', 'phone2' => '', 'web' => ''),
				array('name'=> '吉的堡少儿英语闸北区灵石路教学点', 'address'=> '上海市闸北区灵石路658号2楼 ', 'phone'=>'021-31266036', 'phone2' => '', 'web' => ''),
				array('name'=> '吉的堡少儿英语上海长宁区天山教学点', 'address'=> '上海长宁区天山西路165号A座202室（近北渔路）', 'phone'=>'021-52179125', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-41JY7636.html'),
				array('name'=> '吉的堡少儿英语上海长宁区凯旋教学点', 'address'=> '上海长宁区武夷路699-701号2楼 ', 'phone'=>'021-62128862， 021-62128860 ', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-U7EH3627.html'),
				array('name'=> '吉的堡少儿英语上海普陀区新会教学点', 'address'=> '上海市普陀区新会路496号2楼 ', 'phone'=>'021-62995420，021-62995421', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-6B217397.html'),
				array('name'=> '吉的堡少儿英语上海普陀区曹杨教学点', 'address'=> '上海普陀区曹杨路119-121号 ', 'phone'=>'021-51082356 ', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-LKKA5381.html'),
				array('name'=> '吉的堡少儿英语上海普陀区大渡河路教学点', 'address'=> '上海市普陀区大渡河路2155号2楼 ', 'phone'=>'86-21-52660807/52660236/52660516/51036185', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-32HB6879.html'),
				array('name'=> '吉的堡少儿英语上海普陀区新村教学点', 'address'=> '上海市普陀区新村路423弄1号楼3层305,306,307室 ', 'phone'=>'021-31262202 ，021-66110630', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-H1AC7355.html'),
				array('name'=> '吉的堡少儿英语上海普陀区梅川路教学点', 'address'=> '上海市嘉定区金沙江西路1069号11层 ', 'phone'=>'021-67086888', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-DYCE9171.html'),
				array('name'=> '吉的堡少儿英语上海嘉定区城中路教学点', 'address'=> '上海嘉定区城中路76号3楼 ', 'phone'=>'021-69988839 ', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-38VH9754.html'),
				array('name'=> '吉的堡少儿英语上海嘉定区古漪园路教学点', 'address'=> '上海嘉定南翔佳通路31弄3号中冶祥腾城市广场2F', 'phone'=>'021-69122229', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-JTQZ4812.html'),
				array('name'=> '吉的堡少儿英语上海嘉定区平城路教学点', 'address'=> '上海市嘉定区平城路592号 ', 'phone'=>'021-69902129', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-UTIL5275.html'),
				array('name'=> '吉的堡少儿英语上海市嘉定区和静路教学点', 'address'=> '上海市嘉定区和静路986号世康大厦1楼 ', 'phone'=>'021-59566689', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-N72B5961.html'),
				array('name'=> '吉的堡少儿英语上海嘉定区丰庄教学点', 'address'=> '上海市嘉定区金沙江西路2876号1-3楼 ', 'phone'=>'021-31266850', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-DQTI8338.html'),
				array('name'=> '吉的堡少儿英语上海嘉定区华江路教学点', 'address'=> '上海市嘉定区华江支路336-338号（靠近曹安公路） ', 'phone'=>'021-31001306', 'phone2' => '', 'web' => ''),
				array('name'=> '吉的堡少儿英语上海嘉定区墨玉南路教学点', 'address'=> '上海市嘉定区金沙江西路1069号11层 ', 'phone'=>'021-67086888 ', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-IVIG6516.html'),
				array('name'=> '吉的堡少儿英语上海崇明八一广场教学点', 'address'=> '上海市崇明县八一广场富名街2号 ', 'phone'=>'', 'phone2' => '', 'web' => ''),
				array('name'=> '吉的堡少儿英语上海宝山区长江西路教学点', 'address'=> '上海市宝山区长江西路2337-2339号（1号地铁通河新村） ', 'phone'=>'021-36505585', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-3GYH7266.html'),
				array('name'=> '吉的堡少儿英语上海宝山区大华教学点', 'address'=> '上海市宝山区真金路1037号1楼 ', 'phone'=>'021-66406512', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-FDIV7632.html'),
				array('name'=> '吉的堡少儿英语上海宝山区顾村教学点', 'address'=> '上海市宝山区电台路 406号 ', 'phone'=>'', 'phone2' => '', 'web' => ''),
				array('name'=> '吉的堡少儿英语上海宝山区殷高西路教学点', 'address'=> '上海市宝山区殷高西路520弄1-3号1楼 ', 'phone'=>'021-51083980', 'phone2' => '', 'web' => ''),
				array('name'=> '吉的堡少儿英语上海奉贤区南桥路教学点', 'address'=> '上海奉贤区南桥镇南桥路683号双杰宾馆内(上海艺立培训学校) ', 'phone'=>'021-57418060', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-L9TC1343.html'),
				array('name'=> '吉的堡少儿英语上海松江区南青路教学点', 'address'=> '上海市松江区南青路278号301-302  ', 'phone'=>'021-37729445', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-ABD93875.html'),
				array('name'=> '吉的堡少儿英语上海松江区九亭教学点', 'address'=> '上海市松江区九亭镇沪亭北路350弄20号301室 ', 'phone'=>'021-67699924', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-3SXM9052.html'),
				array('name'=> '吉的堡少儿英语上海徐汇区枫桥教学点', 'address'=> '上海市斜土路1579号1号楼(泰康大厦）7楼 ', 'phone'=>'021-64037712', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-PC7N4023.html'),
				array('name'=> '吉的堡少儿英语上海徐汇区斜土路教学点', 'address'=> '上海市徐汇区斜土路2669号英雄大厦6楼604-605 ', 'phone'=>'021-54257721', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-S9RF3721.html'),
				array('name'=> '吉的堡少儿英语上海市徐汇区桂林路教学点', 'address'=> '上海市徐汇区桂林路46号5楼 ', 'phone'=>'021-54184646', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-W7RJ3526.html'),
				array('name'=> '吉的堡少儿英语上海徐汇区桂平路教学点', 'address'=> '上海徐汇区桂平路188号2楼 ', 'phone'=>'021-54190122', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-3N689388.html'),
				array('name'=> '吉的堡少儿英语上海徐汇区华泾教学点', 'address'=> '上海市徐汇区华发路230号3楼 ', 'phone'=>'021-60793502', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-VZ3X1509.html'),
				array('name'=> '吉的堡少儿英语上海青浦区华科路教学点', 'address'=> '上海市青浦区华科路175号 ', 'phone'=>'021-51012725', 'phone2' => '', 'web' => 'http://enschool.kidcastle.com.cn/index-BX686811.html'),
			),
			'DSN' => array(
				array('name'=> '迪斯尼英语闵行莘庄中心', 'address'=> '上海闵行区都市路5001号仲盛世界商城3楼10、12、14和18B室 ', 'phone'=>'', 'phone2' => '4008208066', 'web' => 'http://www.disneyenglish.com/centers/Shanghai/xinzhuang-center'),
				array('name'=> '迪斯尼英语漕宝中心', 'address'=> '闵行区漕宝路1574号，漕宝购物中心3楼3013室 ', 'phone'=>'', 'phone2' => '', 'web' => 'http://www.disneyenglish.com/centers/Shanghai/caobao-road-center'),
				array('name'=> '迪斯尼英语三林中心', 'address'=> '上海市上南路4467弄 20号 301室 ', 'phone'=>'', 'phone2' => '', 'web' => 'http://www.disneyenglish.com/centers/Shanghai/san-lin-center'),
				array('name'=> '迪斯尼英语徐家汇中心', 'address'=> '上海市徐汇区 辛耕路93号（近天钥桥路） ', 'phone'=>'', 'phone2' => '', 'web' => 'http://www.disneyenglish.com/centers/Shanghai/xujiahui-center'),
				array('name'=> '迪斯尼英语中山公园中心', 'address'=> '上海市长宁区愚园路1250号1楼 ', 'phone'=>'', 'phone2' => '', 'web' => 'http://www.disneyenglish.com/centers/Shanghai/zhongshan-park-center'),
				array('name'=> '迪斯尼英语天山路中心', 'address'=> '上海市长宁区天山路332号 （近威宁路） ', 'phone'=>'', 'phone2' => '', 'web' => 'http://www.disneyenglish.com/centers/Shanghai/tianshan-road-center'),
				array('name'=> '迪斯尼英语世纪公园中心', 'address'=> '上海市浦东新区锦延路320号 ', 'phone'=>'', 'phone2' => '', 'web' => 'http://www.disneyenglish.com/centers/Shanghai/century-park-center'),
				array('name'=> '迪斯尼英语大华中心', 'address'=> '上海市大华路352号(大华虎城嘉年华Room 302-303) ', 'phone'=>'', 'phone2' => '', 'web' => 'http://www.disneyenglish.com/centers/Shanghai/dahua-center'),
				array('name'=> '迪斯尼英语虹口龙之梦中心', 'address'=> '上海市虹口区西江湾路388号，凯德龙之梦虹口广场A栋3楼11C ', 'phone'=>'', 'phone2' => '', 'web' => 'http://www.disneyenglish.com/centers/Shanghai/Hongkou-center'),
				array('name'=> '迪斯尼英语金桥中心', 'address'=> '上海市张扬路3611弄金桥国际商业广场1座326-329 ', 'phone'=>'', 'phone2' => '', 'web' => 'http://www.disneyenglish.com/centers/Shanghai/jin-qiao-center'),
				array('name'=> '迪斯尼英语大宁中心', 'address'=> '上海市闸北区广中路857号1-2 层 ', 'phone'=>'', 'phone2' => '', 'web' => 'http://www.disneyenglish.com/centers/Shanghai/daning-center'),
				array('name'=> '迪斯尼英语五角场中心', 'address'=> '上海市杨浦区黄兴路1625号 2楼 ', 'phone'=>'', 'phone2' => '', 'web' => 'http://www.disneyenglish.com/centers/Shanghai/wujiaochang-center'),
			)
		);
		$province= 310000;

		db()->begin();
		try{
			foreach($branch as $key => $load)
			{
				foreach($load as $item)
				{
					array_walk($item, function(&$v){$v = trim($v);});
					$name = $web = $description = '';
					if(isset($school[$key])) extract($school[$key]);
					$_web = '';
					empty($web) || $_web = $web;
					empty($item['web']) || $_web = $item['web'];

					extract($item);
					$web = $_web;
					if($address) // 相同的机构过滤
					{
						if(load_model('school')->getRow(array('name' => $this->_addslashes($name), 'address' => $this->_addslashes($address)))) continue;
					}else{
						if(load_model('school')->getRow(array('name' =>  $this->_addslashes($name)))) continue;
					}
					$lng = $lat = '0.00000000';
					if($address = $item['address'])
					{
						load('map');
						$location = Map::getCoordsFromAddress('上海市', $address);
						$location && list($lng, $lat) = $location;
					}
					
					$phoneArr = Array();
					$phone = str_replace("，", "", $phone);
					$phone2 = str_replace("，", "", $phone2);
					if(strpos($phone, ' '))
					{
						$phoneArr = explode(" ", $phone);
					}else if($phone)
					{
						$phoneArr[] = $phone;
					}
					if(strpos($phone2, ' '))
					{
						$phone2Arr = explode(" ", $phone2);
						$phone2Arr && $phoneArr = $phoneArr + $phone2Arr;
					}else if($phone2)
					{
						$phoneArr[] = $phone2;
					}
					if(count($phoneArr) > 1)
					{
						list($phone, $phone2) = $phoneArr;
					}

					$creator = 2;$create_time = time();
					$code = $key . "_" . rand(10000, 99999);	
					$insert = compact('source', 'name', 'address', 
						'lng', 'lat', 'province', 'city', 'area','web',
						'type', 'extent', 'contact', 'phone', 'phone2', 'position',
						'creator', 'description', 'create_time','code'
					);
					$id = load_model('school')->insert($this->_addslashes($insert));				
				}
			}		
			db()->commit();
			Out(1, $message, $result);			
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());			
		}
	}

	public function logo()
	{
		$code = Http::post('code', 'trim', '');
		$schools = load_model("school")->getAll("`code` like '" .$code. "_%'");
		$logo = $_FILES['upfile']['tmp_name'];	
		import('file');
		$root = Config::get('path', 'upload');
		foreach($schools as $school)
		{
			$path = Files::get_save_path('school', $school['id']);			
			$filePath = $root. "/" . $path;	
			Files::mkdir($filePath);				
			@copy($logo, $filePath . "original_100_100.jpg");
			@copy($logo, $filePath . "original.jpg");
			@copy($logo, $filePath . "original_200_200.jpg");	
			echo $filePath . "\n";
		}
		
	}		
}