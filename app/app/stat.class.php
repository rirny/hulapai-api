<?php

class Stat_Api extends Api
{
    
    public function __construct() {
        parent::_init();        
    }
    
    public function index()
    {
        $start = Http::post('start_date', 'string', '');
        $end = Http::post('end_date', 'string', '');
		if($start && substr_count($start, '-') != 2) throw new Exception ('时间格式错误！@[start]');
		if($end && substr_count($end, '-') != 2) throw new Exception ('时间格式错误！@[end]');

        $start || $start = date('Y-m-d', mktime(0, 0, 0, date('m'), 1, date('Y'))); // 本月第一天
        $end && $end .= " 23:59";
        $end || $end = date('Y-m-d H:i', mktime(23, 59, 0, date('m'), date('j') - 1, date('Y'))); // 当前前一天
        $character = Http::post('character', 'string', 'teacher');        
        $tm = Http::post('tm', 'string', 0);
        $student = Http::post('student', 'int', 0);
        if(!$student) throw new Exception ('未指定学生');        
        $hash = crc32("%u", md5(json_encode(Http::query()))); // 缓存        
        if($character == 'teacher')
        {
            $sql = "select t.remark,e.id,s.attend,s.absence,s.`leave`,e.start_date,e.end_date from t_course_student s left join t_event e on s.`event` = e.id left join t_course_teacher t on e.id=t.`event`";
            $sql.= " where t.`status`=0 And e.status=0 And e.rec_type!='none' And e.is_loop=0";
            $sql.= " And e.start_date>'{$start}' And e.end_date<'{$end}'";
            $sql.= " And s.student={$student} And t.teacher={$this->uid}"; // And t.priv & 2";           
            $res = db()->fetchAll($sql);
            $result = $source = $count = array();
            foreach($res as $item)
            {
                $remark = strtolower(trim($item['remark']));
                if(!isset($source[$remark]))
                {
                    $source[$remark] = array(
                        'remark' => $remark,
                        'rate' => 0,
                        'count'=> 0,
                        'attend' => 0,
                        'list' => array(
                            'attend' => array(),
                            'absence' => array(),
                            'leave' => array()
                        )
                    );
                }
                $source[$remark]['count'] ++;
                $val =  array(
                    'event' => $item['id'],
                    'remark'=> $item['remark'],
                    //'attend'=> $item['attend'],
                    //'absence'=> $item['absence'],
                    //'leave'=> $item['leave'],
                    'start_date' => $item['start_date'],
                    'end_date' => $item['end_date']
                );
                if($item['attend'])
                {                   
                   $source[$remark]['attend'] ++;
                   $source[$remark]['list']['attend'][] = $val;
                }else if($item['absence'])
                {
                   $source[$remark]['list']['absence'][] = $val;
                }else{
                   $source[$remark]['list']['leave'][] = $val;
                }
            }            
            if(isset($source))
            {
                foreach($source as $key=> $v)
                {
                    $v['rate'] = round($v['attend'] / $v['count'] * 100, 2). "%";
                    unset($v['count'], $v['attend']);
                    $result[] = $v;
                }
            }         
        }else {
            $sql = "select s.remark,e.id,s.attend,s.absence,s.`leave`,s.fee,e.teacher,e.start_date,e.end_date,e.pid from t_course_student s left join t_event e on s.`event` = e.id";
            $sql.= " where s.`status`=0 And e.status=0 And e.rec_type!='none' And e.is_loop=0 And e.attended=1";
            $sql.= " And e.start_date>'{$start}' And e.end_date<'{$end}'";
            $sql.= " And s.student={$student}";            
            $res = db()->fetchAll($sql);
            $result = $source = $count = array();
            $fee = 0.00;
            foreach($res as $item)
            {
                $teacher = load_model('user')->getRow($item['teacher'], false, 'id,firstname,lastname');
                if($item['pid'] && !$item['fee'])
                {
                    $_fee = load_model('student_course')->getRow(array('event' => $item['pid'], 'student' => $student), false, 'fee');
                    if(!empty($_fee['fee'])) $item['fee'] = $_fee['fee'];
                }
                $remark = strtolower(trim($item['remark']));                
                if(!isset($source[$remark][$teacher['id']])) 
                {
                    $val = $source[$remark][$teacher['id']] = array(
                        'remark' => $remark,
                        'teacher'=> $teacher,
                        'fee' => 0.00,
                        'rate'=> 0,
                        'list' => array(
                            'attend' => array(),                            
                            'absence' => array(),
                            'leave' => array()
                        )
                    );
                }else{
                    $val =  $source[$remark][$teacher['id']];
                }
                
                $rs = array(
                    'event' => $item['id'],
                    'remark'=> $item['remark'],
                    'fee' => $item['fee'],
                    'start_date' => $item['start_date'],
                    'end_date' => $item['end_date']
                );
                if($item['attend'])
                {                   
                   $val['fee'] += $item['fee'];
                   $val['list']['attend'][] = $rs;
                   $fee += $item['fee'];
                }else if($item['absence']) // 缺勤
                {
                   $val['fee'] += $item['fee'];
                   $val['list']['absence'][] = $rs;
                   // $fee += $item['fee'];
                }else
                {
                   $val['list']['leave'][] = $rs;
                }
                $source[$remark][$teacher['id']] = $val;
            }
            
            if($source)
            {
                foreach($source as $key=>$src)
                {
                    foreach($src as $v)
                    {
                        $fee && $v['rate'] = round($v['fee'] / $fee * 100, 2) . "%";
                        $result[] = $v;
                    }
                }
            }
        }
        out(1, 'success', $result);
    }            
}