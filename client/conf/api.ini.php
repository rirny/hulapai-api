<?php 
return array (
  'public' => 
  array (
    'upload' => 
    array (
      'index' => 
      array (
        'name' => '上传',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'type',
            'value' => 'avatar',
            'type' => 'text',
            'description' => 'avatar、student、school、notify、feed、comment、space',
          ),
          1 => 
          array (
            'key' => 'student',
            'value' => '0',
            'type' => 'text',
            'description' => '',
          ),
          2 => 
          array (
            'key' => 'school',
            'value' => '0',
            'type' => 'text',
            'description' => '',
          ),
          3 => 
          array (
            'key' => 'upfile',
            'value' => '',
            'type' => 'file',
            'description' => '文件域',
          ),
        ),
        'description' => '所有文件上传',
      ),
    ),
    'area' => 
    array (
      'getList' => 
      array (
        'name' => '地区',
        'param' => 
        array (
        ),
        'description' => '描述',
      ),
    ),
    'apply' => 
    array (
    ),
    'index' => 
    array (
      'code' => 
      array (
        'name' => '',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'mobile',
            'value' => '18684766700',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'device' => 
      array (
        'name' => '设备device',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'type',
            'value' => '0',
            'type' => 'text',
            'description' => '0、当前设备1、最后一次的设备',
          ),
          1 => 
          array (
            'key' => 'user',
            'value' => '',
            'type' => 'text',
            'description' => '取某用户最后一次的设备信息type=1时',
          ),
        ),
        'description' => '描述',
      ),
      'search' => 
      array (
        'name' => '搜索',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'v',
            'value' => '2',
            'type' => 'text',
            'description' => '版本',
          ),
          1 => 
          array (
            'key' => 'province',
            'value' => '0',
            'type' => 'text',
            'description' => '省 默认0',
          ),
          2 => 
          array (
            'key' => 'city',
            'value' => '0',
            'type' => 'text',
            'description' => '市省 默认0',
          ),
          3 => 
          array (
            'key' => 'area',
            'value' => '0',
            'type' => 'text',
            'description' => '区 默认0',
          ),
          4 => 
          array (
            'key' => 'course',
            'value' => '0',
            'type' => 'text',
            'description' => '科目 默认0',
          ),
          5 => 
          array (
            'key' => 'distance',
            'value' => '100000',
            'type' => 'text',
            'description' => '距离 默认0米',
          ),
          6 => 
          array (
            'key' => 'keyword',
            'value' => '',
            'type' => 'text',
            'description' => '关键字',
          ),
          7 => 
          array (
            'key' => 'lat',
            'value' => '31.241039354452',
            'type' => 'text',
            'description' => '纬度 默认0',
          ),
          8 => 
          array (
            'key' => 'lng',
            'value' => '121.42187211274',
            'type' => 'text',
            'description' => '经度 默认0',
          ),
          9 => 
          array (
            'key' => 'page',
            'value' => '1',
            'type' => 'text',
            'description' => '页码 默认1',
          ),
          10 => 
          array (
            'key' => 'per',
            'value' => '20',
            'type' => 'text',
            'description' => '每页显示个数',
          ),
        ),
        'description' => '',
      ),
    ),
    'event' => 
    array (
    ),
    'feedback' => 
    array (
      'add' => 
      array (
        'name' => '意见反馈',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'teacher',
            'value' => '',
            'type' => 'text',
            'description' => '老师ID',
          ),
          1 => 
          array (
            'key' => 'school',
            'value' => '',
            'type' => 'text',
            'description' => '机构ID',
          ),
          2 => 
          array (
            'key' => 'anonymous',
            'value' => '',
            'type' => 'text',
            'description' => '匿名 1   0 实名',
          ),
          3 => 
          array (
            'key' => 'content',
            'value' => '',
            'type' => 'text',
            'description' => '内容',
          ),
          4 => 
          array (
            'key' => 'to_school',
            'value' => '',
            'type' => 'text',
            'description' => '是否发给机构 0是1否',
          ),
        ),
        'description' => '@发给系统 teacher\\school 皆为空
@机构课程发反馈shool为必须
@老师课程school和to_school默认为0',
      ),
      'getList' => 
      array (
        'name' => '意见反馈列表',
        'param' => 
        array (
        ),
        'description' => '',
      ),
    ),
    'stat' => 
    array (
    ),
    'addressbook' => 
    array (
      'index' => 
      array (
        'name' => '通讯录',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'mobilePhones',
            'value' => '',
            'type' => 'text',
            'description' => '手机号逗号分割',
          ),
          1 => 
          array (
            'key' => 'type',
            'value' => '',
            'type' => 'text',
            'description' => '0所有、1老师2学生',
          ),
          2 => 
          array (
            'key' => 'applytype',
            'value' => '',
            'type' => 'text',
            'description' => '类型(5好友申请,8授权)',
          ),
          3 => 
          array (
            'key' => 'student',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '',
      ),
    ),
    'version' => 
    array (
      'index' => 
      array (
        'name' => '软件版本',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'version',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          1 => 
          array (
            'key' => 'type',
            'value' => '0',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '',
      ),
    ),
    'cms' => 
    array (
      'help' => 
      array (
        'name' => '帮助',
        'param' => 
        array (
        ),
        'description' => '描述',
      ),
    ),
    'feed' => 
    array (
    ),
  ),
  'apply' => 
  array (
    'apply' => 
    array (
      'getList' => 
      array (
        'name' => '申请列表',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'type',
            'value' => '1',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '@type:1、学生+老师	2老师+学生,	3机构+老师,	4老师+机构,	 5好友申请,	 6学生+机构,	7机构+学生 注：type不传即收到的所有的申请',
      ),
      'add' => 
      array (
        'name' => '申请',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'type',
            'value' => '1',
            'type' => 'text',
            'description' => '申请类型',
          ),
          1 => 
          array (
            'key' => 'to',
            'value' => '0',
            'type' => 'text',
            'description' => '呼啦号、手机号(除+学生)',
          ),
          2 => 
          array (
            'key' => 'student',
            'value' => '0',
            'type' => 'text',
            'description' => '学生ID',
          ),
          3 => 
          array (
            'key' => 'code',
            'value' => '',
            'type' => 'text',
            'description' => '邀请、验证码',
          ),
          4 => 
          array (
            'key' => 'message',
            'value' => '',
            'type' => 'text',
            'description' => '消息',
          ),
          5 => 
          array (
            'key' => 'school',
            'value' => '0',
            'type' => 'text',
            'description' => '机构ID',
          ),
        ),
        'description' => '@type:1、学生+老师	2老师+学生,	3机构+老师,	4老师+机构,	 5好友申请,	 6学生+机构,	7机构+学生 注：type不传即收到的所有的申请',
      ),
      'deal' => 
      array (
        'name' => '申请处理',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'id',
            'value' => 'apply.id',
            'type' => 'text',
            'description' => '申请ID',
          ),
          1 => 
          array (
            'key' => 'status',
            'value' => '1',
            'type' => 'text',
            'description' => '1、通过 2、忽略',
          ),
        ),
        'description' => '申请处理',
      ),
      'exists' => 
      array (
        'name' => '是否有申请',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'from',
            'value' => '',
            'type' => 'text',
            'description' => '学生、老师、用户',
          ),
          1 => 
          array (
            'key' => 'to',
            'value' => '',
            'type' => 'text',
            'description' => '学生、老师、用户',
          ),
          2 => 
          array (
            'key' => 'student',
            'value' => '',
            'type' => 'text',
            'description' => '学生ID',
          ),
          3 => 
          array (
            'key' => 'school',
            'value' => '',
            'type' => 'text',
            'description' => '机构ID',
          ),
        ),
        'description' => '是否有申请',
      ),
    ),
    'vote' => 
    array (
    ),
    'notify' => 
    array (
    ),
    'message' => 
    array (
    ),
  ),
  'user' => 
  array (
    'index' => 
    array (
      'register' => 
      array (
        'name' => '注册',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'account',
            'value' => '',
            'type' => 'text',
            'description' => '手机机号',
          ),
          1 => 
          array (
            'key' => 'password',
            'value' => '',
            'type' => 'text',
            'description' => 'md5',
          ),
          2 => 
          array (
            'key' => 'verify',
            'value' => '',
            'type' => 'text',
            'description' => '验证码',
          ),
          3 => 
          array (
            'key' => 'gender',
            'value' => '1',
            'type' => 'text',
            'description' => '性别',
          ),
          4 => 
          array (
            'key' => 'nickname',
            'value' => '',
            'type' => 'text',
            'description' => '昵称',
          ),
          5 => 
          array (
            'key' => 'token',
            'value' => '',
            'type' => 'text',
            'description' => '标识码',
          ),
        ),
        'description' => '描述',
      ),
      'login' => 
      array (
        'name' => '登录',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'account',
            'value' => 'MS',
            'type' => 'text',
            'description' => '手机机号',
          ),
          1 => 
          array (
            'key' => 'password',
            'value' => '123123',
            'type' => 'text',
            'description' => 'md5',
          ),
        ),
        'description' => '描述',
      ),
      'findPwd' => 
      array (
        'name' => '找回密码',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'account',
            'value' => '13432132114',
            'type' => 'text',
            'description' => '手机机号',
          ),
          1 => 
          array (
            'key' => 'password',
            'value' => '123123',
            'type' => 'text',
            'description' => 'md5',
          ),
          2 => 
          array (
            'key' => 'verify',
            'value' => '281951',
            'type' => 'text',
            'description' => '验证码',
          ),
          3 => 
          array (
            'key' => 'code',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'logout' => 
      array (
        'name' => '退出',
        'param' => 
        array (
        ),
        'description' => '描述',
      ),
    ),
    'user' => 
    array (
      'pwd' => 
      array (
        'name' => '修改密码',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'old',
            'value' => '',
            'type' => 'text',
            'description' => '旧密码',
          ),
          1 => 
          array (
            'key' => 'new',
            'value' => '',
            'type' => 'text',
            'description' => 'md5',
          ),
        ),
        'description' => '描述',
      ),
      'exists' => 
      array (
        'name' => '用户验证',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'value',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          1 => 
          array (
            'key' => 'key',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'update' => 
      array (
        'name' => '资料修改',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'nickname',
            'value' => '231',
            'type' => 'text',
            'description' => '昵称',
          ),
          1 => 
          array (
            'key' => 'gender',
            'value' => '',
            'type' => 'text',
            'description' => '性别',
          ),
          2 => 
          array (
            'key' => 'birthday',
            'value' => '',
            'type' => 'text',
            'description' => '生日',
          ),
          3 => 
          array (
            'key' => 'province',
            'value' => '',
            'type' => 'text',
            'description' => '省份',
          ),
          4 => 
          array (
            'key' => 'city',
            'value' => '',
            'type' => 'text',
            'description' => '城市',
          ),
          5 => 
          array (
            'key' => 'area',
            'value' => '',
            'type' => 'text',
            'description' => '区域',
          ),
          6 => 
          array (
            'key' => 'address',
            'value' => '',
            'type' => 'text',
            'description' => '地址',
          ),
          7 => 
          array (
            'key' => 'sign',
            'value' => '',
            'type' => 'text',
            'description' => '签名',
          ),
          8 => 
          array (
            'key' => 'email',
            'value' => '',
            'type' => 'text',
            'description' => '邮箱',
          ),
          9 => 
          array (
            'key' => 'mobile',
            'value' => '',
            'type' => 'text',
            'description' => '手机',
          ),
          10 => 
          array (
            'key' => 'qq',
            'value' => '',
            'type' => 'text',
            'description' => 'QQ',
          ),
          11 => 
          array (
            'key' => 'course_notice',
            'value' => '',
            'type' => 'text',
            'description' => '课程通知',
          ),
          12 => 
          array (
            'key' => 'hulaid',
            'value' => '',
            'type' => 'text',
            'description' => '呼啦号',
          ),
        ),
        'description' => '',
      ),
      'remark' => 
      array (
        'name' => '备注',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'character',
            'value' => '',
            'type' => 'text',
            'description' => 'teacher、student、friend',
          ),
          1 => 
          array (
            'key' => 'remark',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          2 => 
          array (
            'key' => 'student',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          3 => 
          array (
            'key' => 'friend',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '@character
teacher:老师备注学生 （参数：@student、@remark）
student:学生备注老师（参数：@student、@teacher、@remark）
friend:备注好友（参数：@friend、@remark）',
      ),
    ),
    'friend' => 
    array (
      'getList' => 
      array (
        'name' => '好友列表',
        'param' => 
        array (
        ),
        'description' => '',
      ),
      'delete' => 
      array (
        'name' => '好友删除',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'friend',
            'value' => '',
            'type' => 'text',
            'description' => '好友ID',
          ),
        ),
        'description' => '',
      ),
    ),
  ),
  'teacher' => 
  array (
    'teacher' => 
    array (
      'add' => 
      array (
        'name' => '建档',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'province',
            'value' => '',
            'type' => 'text',
            'description' => '省份',
          ),
          1 => 
          array (
            'key' => 'city',
            'value' => '',
            'type' => 'text',
            'description' => '市',
          ),
          2 => 
          array (
            'key' => 'area',
            'value' => '',
            'type' => 'text',
            'description' => '地区',
          ),
          3 => 
          array (
            'key' => 'target',
            'value' => '',
            'type' => 'text',
            'description' => '教育对象',
          ),
          4 => 
          array (
            'key' => 'background',
            'value' => '',
            'type' => 'text',
            'description' => '教育背景',
          ),
          5 => 
          array (
            'key' => 'mind',
            'value' => '',
            'type' => 'text',
            'description' => '教育',
          ),
          6 => 
          array (
            'key' => 'firstname',
            'value' => '刘',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '',
      ),
      'info' => 
      array (
        'name' => '详细',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'character',
            'value' => '',
            'type' => 'text',
            'description' => 'parent、school、group',
          ),
          1 => 
          array (
            'key' => 'id',
            'value' => '',
            'type' => 'text',
            'description' => '老师ID',
          ),
          2 => 
          array (
            'key' => 'student',
            'value' => '',
            'type' => 'text',
            'description' => '家长以某学生档案来看',
          ),
          3 => 
          array (
            'key' => 'school',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          4 => 
          array (
            'key' => 'group',
            'value' => '',
            'type' => 'text',
            'description' => '机构不存在',
          ),
        ),
        'description' => '描述',
      ),
      'delete' => 
      array (
        'name' => '删档',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'character',
            'value' => 'parent',
            'type' => 'text',
            'description' => '身份parent|school|group',
          ),
          1 => 
          array (
            'key' => 'student',
            'value' => '173',
            'type' => 'text',
            'description' => '学生ID',
          ),
          2 => 
          array (
            'key' => 'teacher',
            'value' => '32',
            'type' => 'text',
            'description' => '老师ID',
          ),
          3 => 
          array (
            'key' => 'school',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          4 => 
          array (
            'key' => 'group',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '',
      ),
      'getList' => 
      array (
        'name' => '列表',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'character',
            'value' => 'parent',
            'type' => 'text',
            'description' => '身份parent|school|group',
          ),
          1 => 
          array (
            'key' => 'school',
            'value' => '',
            'type' => 'text',
            'description' => '机构的老师',
          ),
          2 => 
          array (
            'key' => 'group',
            'value' => '',
            'type' => 'text',
            'description' => '分组下的老师',
          ),
          3 => 
          array (
            'key' => 'student',
            'value' => '173',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '',
      ),
      'update' => 
      array (
        'name' => '修改档案',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'firstname',
            'value' => '',
            'type' => 'text',
            'description' => '姓',
          ),
          1 => 
          array (
            'key' => 'lastname',
            'value' => '',
            'type' => 'text',
            'description' => '名',
          ),
          2 => 
          array (
            'key' => 'mind',
            'value' => '',
            'type' => 'text',
            'description' => '教育理念',
          ),
          3 => 
          array (
            'key' => 'background',
            'value' => '',
            'type' => 'text',
            'description' => '教育背景',
          ),
          4 => 
          array (
            'key' => 'province',
            'value' => '',
            'type' => 'text',
            'description' => '省',
          ),
          5 => 
          array (
            'key' => 'city',
            'value' => '',
            'type' => 'text',
            'description' => '市',
          ),
          6 => 
          array (
            'key' => 'area',
            'value' => '',
            'type' => 'text',
            'description' => '地区',
          ),
          7 => 
          array (
            'key' => 'target',
            'value' => '',
            'type' => 'text',
            'description' => '教育对象',
          ),
        ),
        'description' => '描述',
      ),
      'getEventList' => 
      array (
        'name' => '获取老师课程列表',
        'param' => 
        array (
        ),
        'description' => '描述',
      ),
    ),
  ),
  'course' => 
  array (
    'course' => 
    array (
      'add' => 
      array (
        'name' => '添加',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'type',
            'value' => '',
            'type' => 'text',
            'description' => '类型',
          ),
          1 => 
          array (
            'key' => 'title',
            'value' => '',
            'type' => 'text',
            'description' => '标题',
          ),
          2 => 
          array (
            'key' => 'class_time',
            'value' => '',
            'type' => 'text',
            'description' => '课时',
          ),
          3 => 
          array (
            'key' => 'experience',
            'value' => '',
            'type' => 'text',
            'description' => '经验',
          ),
        ),
        'description' => '描述',
      ),
      'update' => 
      array (
        'name' => '修改',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'type',
            'value' => '59',
            'type' => 'text',
            'description' => '类型',
          ),
          1 => 
          array (
            'key' => 'title',
            'value' => 'title',
            'type' => 'text',
            'description' => '标题',
          ),
          2 => 
          array (
            'key' => 'class_time',
            'value' => '1',
            'type' => 'text',
            'description' => '课时',
          ),
          3 => 
          array (
            'key' => 'experience',
            'value' => '10',
            'type' => 'text',
            'description' => '经验',
          ),
          4 => 
          array (
            'key' => 'id',
            'value' => '106',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'delete' => 
      array (
        'name' => '删除',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'id',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'getList' => 
      array (
        'name' => '列表',
        'param' => 
        array (
        ),
        'description' => '描述',
      ),
      'types' => 
      array (
        'name' => '所有分类',
        'param' => 
        array (
        ),
        'description' => '所有分类',
      ),
    ),
  ),
  'student' => 
  array (
    'student' => 
    array (
      'add' => 
      array (
        'name' => '添加',
        'param' => 
        array (
        ),
        'description' => '',
      ),
      'info' => 
      array (
        'name' => '学生详情',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'character',
            'value' => '',
            'type' => 'text',
            'description' => 'parent、teacher、机构',
          ),
          1 => 
          array (
            'key' => 'student',
            'value' => '',
            'type' => 'text',
            'description' => '学生ID',
          ),
          2 => 
          array (
            'key' => 'school',
            'value' => '',
            'type' => 'text',
            'description' => '机构学生',
          ),
        ),
        'description' => '是否有申请',
      ),
      'update' => 
      array (
        'name' => '修改',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'id',
            'value' => '',
            'type' => 'text',
            'description' => '学生ID',
          ),
          1 => 
          array (
            'key' => 'name',
            'value' => '',
            'type' => 'text',
            'description' => '学生名',
          ),
          2 => 
          array (
            'key' => 'nickname',
            'value' => '',
            'type' => 'text',
            'description' => '昵称',
          ),
          3 => 
          array (
            'key' => 'birthday',
            'value' => '',
            'type' => 'text',
            'description' => '生日',
          ),
          4 => 
          array (
            'key' => 'tag',
            'value' => '',
            'type' => 'text',
            'description' => '标签',
          ),
          5 => 
          array (
            'key' => 'gender',
            'value' => '0',
            'type' => 'text',
            'description' => '',
          ),
          6 => 
          array (
            'key' => 'relation',
            'value' => '0',
            'type' => 'text',
            'description' => '1本人 2爸爸 3、妈妈4、其他',
          ),
        ),
        'description' => '是否有申请',
      ),
      'getList' => 
      array (
        'name' => '列表',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'character',
            'value' => '',
            'type' => 'text',
            'description' => 'parent、teacher、机构',
          ),
          1 => 
          array (
            'key' => 'event',
            'value' => '',
            'type' => 'text',
            'description' => '课程ID',
          ),
          2 => 
          array (
            'key' => 'grade',
            'value' => '',
            'type' => 'text',
            'description' => '班级ID',
          ),
          3 => 
          array (
            'key' => 'school',
            'value' => '',
            'type' => 'text',
            'description' => '机构ID',
          ),
        ),
        'description' => '描述',
      ),
      'relationChange' => 
      array (
        'name' => '关系修改',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'id',
            'value' => '16',
            'type' => 'text',
            'description' => '学生ID',
          ),
          1 => 
          array (
            'key' => 'relation',
            'value' => '2',
            'type' => 'text',
            'description' => '课程ID',
          ),
        ),
        'description' => '描述',
      ),
      'delete' => 
      array (
        'name' => '删除',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'character',
            'value' => '',
            'type' => 'text',
            'description' => 'parent、teacher、grade、school',
          ),
          1 => 
          array (
            'key' => 'student',
            'value' => '',
            'type' => 'text',
            'description' => '学生ID',
          ),
          2 => 
          array (
            'key' => 'user',
            'value' => '',
            'type' => 'text',
            'description' => '删除授权',
          ),
          3 => 
          array (
            'key' => 'grade',
            'value' => '',
            'type' => 'text',
            'description' => '删除班级下的学生',
          ),
          4 => 
          array (
            'key' => 'school',
            'value' => '',
            'type' => 'text',
            'description' => '删除机构下的学生',
          ),
        ),
        'description' => '@删除学生档案 character为空
@删除授权的家长 character:parent @user=要删除的家长 @student
@删除授权的学生 character:parent @student
@老师删除学生 character:teacher @sutdent @teacher
@删除班级下的学生 character:grade @sutdent @grade
@删除机构的学生 character:school @sutdent @school',
      ),
      'authList' => 
      array (
        'name' => '授权列表',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'student',
            'value' => '16',
            'type' => 'text',
            'description' => '学生ID',
          ),
        ),
        'description' => '描述',
      ),
    ),
    'stat' => 
    array (
      'index' => 
      array (
        'name' => '统计',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'character',
            'value' => 'teacher',
            'type' => 'text',
            'description' => 'parent家长 teacher老师',
          ),
          1 => 
          array (
            'key' => 'student',
            'value' => '2',
            'type' => 'text',
            'description' => '学生ID',
          ),
          2 => 
          array (
            'key' => 'start_date',
            'value' => '2013-09-01',
            'type' => 'text',
            'description' => '默认为当月1号',
          ),
          3 => 
          array (
            'key' => 'end_date',
            'value' => '2013-09-22',
            'type' => 'text',
            'description' => '默认为昨天，当时',
          ),
        ),
        'description' => '描述',
      ),
    ),
  ),
  'grade' => 
  array (
    'grade' => 
    array (
      'delete' => 
      array (
        'name' => '删除班级',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'id',
            'value' => '',
            'type' => 'text',
            'description' => '班级ID',
          ),
        ),
        'description' => '',
      ),
      'add' => 
      array (
        'name' => '创建班级',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'name',
            'value' => '小班',
            'type' => 'text',
            'description' => '班级ID',
          ),
          1 => 
          array (
            'key' => 'student',
            'value' => '1',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '添加学生',
      ),
      'add_student' => 
      array (
        'name' => '分班',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'grade',
            'value' => '',
            'type' => 'text',
            'description' => '班级ID',
          ),
          1 => 
          array (
            'key' => 'student',
            'value' => '',
            'type' => 'text',
            'description' => '学生ID',
          ),
        ),
        'description' => '添加学生',
      ),
      'getList' => 
      array (
        'name' => '班级列表',
        'param' => 
        array (
        ),
        'description' => '描述',
      ),
      'update' => 
      array (
        'name' => '班级修改',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'id',
            'value' => '21',
            'type' => 'text',
            'description' => '班级ID',
          ),
          1 => 
          array (
            'key' => 'student',
            'value' => '',
            'type' => 'text',
            'description' => '学生ID',
          ),
          2 => 
          array (
            'key' => 'name',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '添加学生、删除学生学生ID用逗号分开',
      ),
    ),
  ),
  'school' => 
  array (
    'school' => 
    array (
      'info' => 
      array (
        'name' => '机构详情',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'id',
            'value' => '',
            'type' => 'text',
            'description' => '机构id',
          ),
          1 => 
          array (
            'key' => 'name',
            'value' => '',
            'type' => 'text',
            'description' => '机构名或者机构代码',
          ),
        ),
        'description' => 'id,name  2传1',
      ),
      'getList' => 
      array (
        'name' => '机构列表',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'character',
            'value' => 'teacher',
            'type' => 'text',
            'description' => '角色:teacher  student',
          ),
          1 => 
          array (
            'key' => 'teacher',
            'value' => '',
            'type' => 'text',
            'description' => '老师id',
          ),
          2 => 
          array (
            'key' => 'student',
            'value' => '',
            'type' => 'text',
            'description' => '学生id',
          ),
        ),
        'description' => '',
      ),
      'teacher_student' => 
      array (
        'name' => '老师有关系的学生',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'id',
            'value' => '17',
            'type' => 'text',
            'description' => '机构id',
          ),
          1 => 
          array (
            'key' => 'teacher',
            'value' => '',
            'type' => 'text',
            'description' => '老师id',
          ),
        ),
        'description' => '',
      ),
      'leave' => 
      array (
        'name' => '脱离机构',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'teacher',
            'value' => '1022',
            'type' => 'text',
            'description' => '老师id',
          ),
          1 => 
          array (
            'key' => 'id',
            'value' => '8',
            'type' => 'text',
            'description' => '机构id',
          ),
        ),
        'description' => '',
      ),
    ),
  ),
  'comment' => 
  array (
    'comment' => 
    array (
      'add' => 
      array (
        'name' => '添加',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'character',
            'value' => 'teacher',
            'type' => 'text',
            'description' => 'student、teacher、school',
          ),
          1 => 
          array (
            'key' => 'teacher',
            'value' => '',
            'type' => 'text',
            'description' => '家长对某个老师的点评',
          ),
          2 => 
          array (
            'key' => 'student',
            'value' => '169',
            'type' => 'text',
            'description' => '多个用逗号',
          ),
          3 => 
          array (
            'key' => 'shool',
            'value' => '',
            'type' => 'text',
            'description' => '机构ID',
          ),
          4 => 
          array (
            'key' => 'content',
            'value' => 'wefwefwe',
            'type' => 'text',
            'description' => '',
          ),
          5 => 
          array (
            'key' => 'attach',
            'value' => '',
            'type' => 'text',
            'description' => '多个用逗号分割',
          ),
          6 => 
          array (
            'key' => 'event',
            'value' => '398',
            'type' => 'text',
            'description' => '课程ID',
          ),
        ),
        'description' => '@student 老师对某个学生、家长对老师的评价',
      ),
      'reply' => 
      array (
        'name' => '点评回复',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'character',
            'value' => '',
            'type' => 'text',
            'description' => 'student、teacher、school',
          ),
          1 => 
          array (
            'key' => 'id',
            'value' => '',
            'type' => 'text',
            'description' => 'comment id',
          ),
          2 => 
          array (
            'key' => 'content',
            'value' => '',
            'type' => 'text',
            'description' => '【0老师 -> 点评； 1、课程点评，2、学生 -> 点评 3、老师详情页(用户)；4、老师详情（点评记录）；5、学生详情（用户）；6、学生详情（点评记录）身份】',
          ),
          3 => 
          array (
            'key' => 'attach',
            'value' => '',
            'type' => 'text',
            'description' => 'type=2,5,6 必须',
          ),
          4 => 
          array (
            'key' => 'student',
            'value' => '169',
            'type' => 'text',
            'description' => '学生ID',
          ),
        ),
        'description' => '',
      ),
      'getList' => 
      array (
        'name' => '点评列表',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'character',
            'value' => '',
            'type' => 'text',
            'description' => 'student、teacher、school',
          ),
          1 => 
          array (
            'key' => 'teacher',
            'value' => '',
            'type' => 'text',
            'description' => '0',
          ),
          2 => 
          array (
            'key' => 'type',
            'value' => '1',
            'type' => 'text',
            'description' => '0-6',
          ),
          3 => 
          array (
            'key' => 'student',
            'value' => '2',
            'type' => 'text',
            'description' => 'type=2,5,6 必须',
          ),
          4 => 
          array (
            'key' => 'school',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          5 => 
          array (
            'key' => 'reply',
            'value' => '',
            'type' => 'text',
            'description' => '1是否返回回复',
          ),
          6 => 
          array (
            'key' => 'event',
            'value' => '4',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '0	首页（老师档案）　点评列表　老师　老师发出、收到（用户）
1	点评列表　　　　　课程点评　老师　某课程下所有学生的课程点评（课程) 
2	首页（学生档案）　点评　　　学生　所有（用户+ 课程） 
3	老师详情　　　　　点评　　　ALL　老师收到的所有点评（用户）
4	老师详情　　　　　点评记录　学生　学生与老师（用户+课程）
5	学生详情　　　　　点评　　　All　　学生收到的所有点评（用户）
6	学生详情　　　　　点评记录　老师　与该老师相关的（用户+课程）
2	学生详情　　　　　点评记录（家长）',
      ),
    ),
  ),
  'attend' => 
  array (
    'attendance' => 
    array (
      'update' => 
      array (
        'name' => '考勤',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'character',
            'value' => '',
            'type' => 'text',
            'description' => '角色',
          ),
          1 => 
          array (
            'key' => 'event',
            'value' => '',
            'type' => 'text',
            'description' => '课程',
          ),
          2 => 
          array (
            'key' => 'student',
            'value' => '',
            'type' => 'text',
            'description' => '学生ID',
          ),
          3 => 
          array (
            'key' => 'absence',
            'value' => '',
            'type' => 'text',
            'description' => '状态',
          ),
        ),
        'description' => '',
      ),
    ),
  ),
  'blog' => 
  array (
    'blog' => 
    array (
      'space' => 
      array (
        'name' => '我的呼啦圈',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'page',
            'value' => '',
            'type' => 'text',
            'description' => '页码',
          ),
        ),
        'description' => '描述',
      ),
      'user' => 
      array (
        'name' => '用户的呼啦圈',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'uid',
            'value' => '35',
            'type' => 'text',
            'description' => '用户ID',
          ),
          1 => 
          array (
            'key' => 'page',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'add' => 
      array (
        'name' => '发表',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'content',
            'value' => '',
            'type' => 'text',
            'description' => '内容',
          ),
          1 => 
          array (
            'key' => 'attach_ids',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'reAdd' => 
      array (
        'name' => '转发动态',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'content',
            'value' => '',
            'type' => 'text',
            'description' => '内容',
          ),
          1 => 
          array (
            'key' => 'attach_ids',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          2 => 
          array (
            'key' => 'blog_id',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          3 => 
          array (
            'key' => 'curid',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'info' => 
      array (
        'name' => '动态详情',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'blog_id',
            'value' => '26',
            'type' => 'text',
            'description' => '动态',
          ),
        ),
        'description' => '描述',
      ),
      'delete' => 
      array (
        'name' => '删除动态',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'blog_id',
            'value' => '',
            'type' => 'text',
            'description' => '动态',
          ),
        ),
        'description' => '描述',
      ),
    ),
    'blog_comment' => 
    array (
      'getList' => 
      array (
        'name' => '评论列表',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'comment_id',
            'value' => '',
            'type' => 'text',
            'description' => '评论',
          ),
        ),
        'description' => '描述',
      ),
      'add' => 
      array (
        'name' => '评论',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'content',
            'value' => '',
            'type' => 'text',
            'description' => '内容',
          ),
          1 => 
          array (
            'key' => 'to_comment_id',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          2 => 
          array (
            'key' => 'blog_id',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'delete' => 
      array (
        'name' => '删除评论',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'comment_id',
            'value' => '',
            'type' => 'text',
            'description' => '评论',
          ),
        ),
        'description' => '描述',
      ),
    ),
    'blog_digg' => 
    array (
      'add' => 
      array (
        'name' => '赞',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'blog_id',
            'value' => '',
            'type' => 'text',
            'description' => '动态',
          ),
        ),
        'description' => '描述',
      ),
      'delete' => 
      array (
        'name' => '取消赞',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'blog_id',
            'value' => '',
            'type' => 'text',
            'description' => '动态',
          ),
        ),
        'description' => '描述',
      ),
    ),
  ),
  'feed' => 
  array (
    'feed' => 
    array (
      'space' => 
      array (
        'name' => '我的微博',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'page',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'info' => 
      array (
        'name' => '微博详情',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'feed_id',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'user' => 
      array (
        'name' => '用户微博',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'page',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          1 => 
          array (
            'key' => 'uid',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'index' => 
      array (
        'name' => '广场',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'page',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'add' => 
      array (
        'name' => '发表微博',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'content',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          1 => 
          array (
            'key' => 'attach_ids',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'reAdd' => 
      array (
        'name' => '转发微博',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'content',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          1 => 
          array (
            'key' => 'feed_id',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          2 => 
          array (
            'key' => 'curid',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'delete' => 
      array (
        'name' => '删除微博',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'feed_id',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'recommend' => 
      array (
        'name' => '广场推荐',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'refresh',
            'value' => '1',
            'type' => 'text',
            'description' => '',
          ),
          1 => 
          array (
            'key' => 'page',
            'value' => '1',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
    ),
    'feed_comment' => 
    array (
      'getList' => 
      array (
        'name' => '微博评论列表',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'feed_id',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'delete' => 
      array (
        'name' => '删除微博评论',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'comment_id',
            'value' => '',
            'type' => 'text',
            'description' => '评论',
          ),
        ),
        'description' => '描述',
      ),
      'add' => 
      array (
        'name' => '评论',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'content',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          1 => 
          array (
            'key' => 'feed_id',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          2 => 
          array (
            'key' => 'to_comment_id',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
    ),
    'feed_follow' => 
    array (
      'add' => 
      array (
        'name' => '微博关注',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'fid',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'delete' => 
      array (
        'name' => '取消微博关注',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'fid',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'getList' => 
      array (
        'name' => '关注列表',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'page',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          1 => 
          array (
            'key' => 'uid',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
    ),
    'feed_collection' => 
    array (
      'getList' => 
      array (
        'name' => '微博收藏列表',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'uid',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'add' => 
      array (
        'name' => '微博收藏',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'feed_id',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'delete' => 
      array (
        'name' => '取消微博收藏',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'feed_id',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
    ),
  ),
  'friend' => 
  array (
  ),
  'event' => 
  array (
    'event' => 
    array (
      'add' => 
      array (
        'name' => '发布',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'course',
            'value' => '',
            'type' => 'text',
            'description' => '授课内容',
          ),
          1 => 
          array (
            'key' => 'start_date',
            'value' => '',
            'type' => 'text',
            'description' => '单节课开始时间',
          ),
          2 => 
          array (
            'key' => 'end_date',
            'value' => '',
            'type' => 'text',
            'description' => '单节课结束时间',
          ),
          3 => 
          array (
            'key' => 'rec_type',
            'value' => '',
            'type' => 'text',
            'description' => '重复频率 day____#',
          ),
          4 => 
          array (
            'key' => 'end',
            'value' => '',
            'type' => 'text',
            'description' => '课程结束时间',
          ),
          5 => 
          array (
            'key' => 'is_loop',
            'value' => '',
            'type' => 'text',
            'description' => '是否为重复课程',
          ),
          6 => 
          array (
            'key' => 'color',
            'value' => '',
            'type' => 'text',
            'description' => '颜色',
          ),
          7 => 
          array (
            'key' => 'class_time',
            'value' => '',
            'type' => 'text',
            'description' => '课时',
          ),
          8 => 
          array (
            'key' => 'student',
            'value' => '',
            'type' => 'text',
            'description' => '以逗号分割',
          ),
          9 => 
          array (
            'key' => 'text',
            'value' => '',
            'type' => 'text',
            'description' => '标题',
          ),
          10 => 
          array (
            'key' => 'grade',
            'value' => '',
            'type' => 'text',
            'description' => '班级ID',
          ),
        ),
        'description' => '',
      ),
      'update' => 
      array (
        'name' => '修改',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'course',
            'value' => '',
            'type' => 'text',
            'description' => '授课内容',
          ),
          1 => 
          array (
            'key' => 'start_date',
            'value' => '',
            'type' => 'text',
            'description' => '单节课开始时间 yyyy-mm-dd hh:mm',
          ),
          2 => 
          array (
            'key' => 'end_date',
            'value' => '',
            'type' => 'text',
            'description' => '单节课结束时间 yyyy-mm-dd hh:mm',
          ),
          3 => 
          array (
            'key' => 'rec_type',
            'value' => '',
            'type' => 'text',
            'description' => '重复频率 day____#',
          ),
          4 => 
          array (
            'key' => 'end',
            'value' => '',
            'type' => 'text',
            'description' => '课程结束日期 yyyy-mm-dd',
          ),
          5 => 
          array (
            'key' => 'is_loop',
            'value' => '',
            'type' => 'text',
            'description' => '是否为重复课程',
          ),
          6 => 
          array (
            'key' => 'color',
            'value' => '',
            'type' => 'text',
            'description' => '颜色',
          ),
          7 => 
          array (
            'key' => 'class_time',
            'value' => '',
            'type' => 'text',
            'description' => '课时',
          ),
          8 => 
          array (
            'key' => 'student',
            'value' => '',
            'type' => 'text',
            'description' => '以逗号分割',
          ),
          9 => 
          array (
            'key' => 'text',
            'value' => '',
            'type' => 'text',
            'description' => '标题',
          ),
          10 => 
          array (
            'key' => 'grade',
            'value' => '',
            'type' => 'text',
            'description' => '班级ID',
          ),
          11 => 
          array (
            'key' => 'whole',
            'value' => '',
            'type' => 'text',
            'description' => '循环课程是否修改整个课程',
          ),
          12 => 
          array (
            'key' => 'id',
            'value' => '',
            'type' => 'text',
            'description' => '课程ID，循环单节修改pid#length',
          ),
        ),
        'description' => '',
      ),
      'delete' => 
      array (
        'name' => '删除',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'id',
            'value' => '',
            'type' => 'text',
            'description' => '课程ID，循环课程单节pid#length',
          ),
          1 => 
          array (
            'key' => 'whole',
            'value' => '',
            'type' => 'text',
            'description' => '是否删除整个循环',
          ),
        ),
        'description' => '描述',
      ),
      'info' => 
      array (
        'name' => '详情',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'id',
            'value' => '',
            'type' => 'text',
            'description' => '课程ID，循环课程单节pid#length',
          ),
        ),
        'description' => '描述',
      ),
      'remark' => 
      array (
        'name' => '备注',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'id',
            'value' => '26',
            'type' => 'text',
            'description' => '课程ID',
          ),
          1 => 
          array (
            'key' => 'remark',
            'value' => 'test',
            'type' => 'text',
            'description' => '备注名',
          ),
          2 => 
          array (
            'key' => 'color',
            'value' => '',
            'type' => 'text',
            'description' => '颜色',
          ),
          3 => 
          array (
            'key' => 'fee',
            'value' => '',
            'type' => 'text',
            'description' => '学费',
          ),
          4 => 
          array (
            'key' => 'student',
            'value' => '5',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '',
      ),
      'getList' => 
      array (
        'name' => '列表',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'type',
            'value' => '2',
            'type' => 'text',
            'description' => '1学生2老师',
          ),
          1 => 
          array (
            'key' => 'student',
            'value' => '',
            'type' => 'text',
            'description' => '学生ID',
          ),
          2 => 
          array (
            'key' => 'start_date',
            'value' => '',
            'type' => 'text',
            'description' => '开始时间',
          ),
          3 => 
          array (
            'key' => 'end_date',
            'value' => '',
            'type' => 'text',
            'description' => '结束时间',
          ),
          4 => 
          array (
            'key' => 'pid',
            'value' => '',
            'type' => 'text',
            'description' => '循环课程ID',
          ),
          5 => 
          array (
            'key' => 'attend',
            'value' => '',
            'type' => 'text',
            'description' => '出勤 0,1',
          ),
          6 => 
          array (
            'key' => 'leave',
            'value' => '',
            'type' => 'text',
            'description' => '请假 0,1',
          ),
          7 => 
          array (
            'key' => 'absence',
            'value' => '',
            'type' => 'text',
            'description' => '缺勤',
          ),
          8 => 
          array (
            'key' => 'comment',
            'value' => '',
            'type' => 'text',
            'description' => '0未点，1已点（无关保持为空）',
          ),
          9 => 
          array (
            'key' => 'teacher',
            'value' => '',
            'type' => 'text',
            'description' => '某个老师的课程',
          ),
        ),
        'description' => '',
      ),
    ),
  ),
  'message' => 
  array (
    'vote' => 
    array (
    ),
    'notify' => 
    array (
      'add' => 
      array (
        'name' => '发送通知',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'student',
            'value' => '5,4',
            'type' => 'text',
            'description' => '',
          ),
          1 => 
          array (
            'key' => 'content',
            'value' => '123123',
            'type' => 'text',
            'description' => '',
          ),
          2 => 
          array (
            'key' => 'event',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          3 => 
          array (
            'key' => 'attach_ids',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          4 => 
          array (
            'key' => 'vote',
            'value' => '20',
            'type' => 'text',
            'description' => '',
          ),
          5 => 
          array (
            'key' => 'grade',
            'value' => '3',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'getList' => 
      array (
        'name' => '通知列表',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'page',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          1 => 
          array (
            'key' => 'event',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          2 => 
          array (
            'key' => 'student',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'info' => 
      array (
        'name' => '通知详情',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'id',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'delete' => 
      array (
        'name' => '删除通知',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'id',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
    ),
    'message' => 
    array (
      'getList' => 
      array (
        'name' => '消息列表',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'page',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          1 => 
          array (
            'key' => 'type',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'info' => 
      array (
        'name' => '消息信息',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'id',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
    ),
  ),
  'vote' => 
  array (
    'vote' => 
    array (
      'add' => 
      array (
        'name' => '增加问卷',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'title',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          1 => 
          array (
            'key' => 'multi',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          2 => 
          array (
            'key' => 'start_date',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          3 => 
          array (
            'key' => 'end_date',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          4 => 
          array (
            'key' => 'option',
            'value' => '参加#!&不参加',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'update' => 
      array (
        'name' => '修改问卷',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'title',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          1 => 
          array (
            'key' => 'multi',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          2 => 
          array (
            'key' => 'start_date',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          3 => 
          array (
            'key' => 'end_date',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          4 => 
          array (
            'key' => 'option',
            'value' => '参加#!&不参加',
            'type' => 'text',
            'description' => '',
          ),
          5 => 
          array (
            'key' => 'id',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'getList' => 
      array (
        'name' => '问卷列表',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'page',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'doVote' => 
      array (
        'name' => '做问卷',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'vote',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          1 => 
          array (
            'key' => 'character',
            'value' => '',
            'type' => 'text',
            'description' => '用户身份(user,student,teacher)',
          ),
          2 => 
          array (
            'key' => 'student',
            'value' => '',
            'type' => 'text',
            'description' => '学生id',
          ),
          3 => 
          array (
            'key' => 'option',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
      'info' => 
      array (
        'name' => '问卷详情',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'id',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
          1 => 
          array (
            'key' => 'character',
            'value' => '',
            'type' => 'text',
            'description' => '用户身份(user,student,teacher)',
          ),
          2 => 
          array (
            'key' => 'student',
            'value' => '',
            'type' => 'text',
            'description' => '学生id',
          ),
        ),
        'description' => '描述',
      ),
    ),
    'message' => 
    array (
      'delete' => 
      array (
        'name' => '删除消息',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'id',
            'value' => '',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '描述',
      ),
    ),
  ),
  'lbs' => 
  array (
    'index' => 
    array (
      'hot' => 
      array (
        'name' => '热门',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'v',
            'value' => '2',
            'type' => 'text',
            'description' => '',
          ),
        ),
        'description' => '',
      ),
      'search' => 
      array (
        'name' => '搜索',
        'param' => 
        array (
          0 => 
          array (
            'key' => 'v',
            'value' => '2',
            'type' => 'text',
            'description' => '版本',
          ),
          1 => 
          array (
            'key' => 'province',
            'value' => '0',
            'type' => 'text',
            'description' => '省 默认0',
          ),
          2 => 
          array (
            'key' => 'city',
            'value' => '0',
            'type' => 'text',
            'description' => '市省 默认0',
          ),
          3 => 
          array (
            'key' => 'area',
            'value' => '0',
            'type' => 'text',
            'description' => '区 默认0',
          ),
          4 => 
          array (
            'key' => 'course',
            'value' => '0',
            'type' => 'text',
            'description' => '科目 默认0',
          ),
          5 => 
          array (
            'key' => 'distance',
            'value' => '100000',
            'type' => 'text',
            'description' => '距离 默认0米',
          ),
          6 => 
          array (
            'key' => 'keyword',
            'value' => '',
            'type' => 'text',
            'description' => '关键字',
          ),
          7 => 
          array (
            'key' => 'lat',
            'value' => '31.241039354452',
            'type' => 'text',
            'description' => '纬度 默认0',
          ),
          8 => 
          array (
            'key' => 'lng',
            'value' => '121.42187211274',
            'type' => 'text',
            'description' => '经度 默认0',
          ),
          9 => 
          array (
            'key' => 'page',
            'value' => '1',
            'type' => 'text',
            'description' => '页码 默认1',
          ),
          10 => 
          array (
            'key' => 'per',
            'value' => '20',
            'type' => 'text',
            'description' => '每页显示个数',
          ),
        ),
        'description' => '',
      ),
    ),
  ),
);
?>