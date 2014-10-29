<?php

Abstract class Model{

	protected $db = Null;

	protected $uid = '';
	protected $account = '';
	protected $hulaid = '';
	protected $name = '';   
    private $_prefix = 't_';

	public function __construct($param= array()){
		$this->uid = Http::get_session(SESS_UID);		
		$this->account = Http::get_session(SESS_ACCOUNT);
		$this->hulaid = Http::get_session(SESS_HULAID);
		$this->name = Http::get_session(SESS_NAME);		
		if($param)
		{
			foreach($param as $key => $item)
			{
				$_key = '_' . $key;
				$key == 'table' && $item = $this->_prefix . $item;
				$this->$_key = $item;
			}
		}
		if(empty($this->_key)) $this->_key = 'id';
	}

	public function __destruct(){}
	
	protected $_table = '';
	protected $_pk = 'id';

	protected $_cache_key = '';	
	protected $_timelife = '3600';

	// 特殊处理
	protected $_format_column = array(		
		'id' => '_',
	);

	// 不用的字段
	protected $_unUse = array();

	public function getRow($param, $out = false, $field='*', $order='')
	{	
		$result = array();
		$where = $this->whereExp($param);
		$field || $field = '*';
		$sql = 'select ' . $field . ' from `' . $this->_table . "`";
		$where && $sql.= " Where " . $where;
		$order && $sql.= " Order by ". $order;
		$res = db()->fetchRow($sql . " limit 1");				
		if($res && $out){	
			$res = $this->Format($res);
			$this->object = (object) $res;
		}
		//echo $sql ."\n";
		$res && $result = $res;
		return $result;
	}

	public function getCount($param, $field='count(*)')
	{
		$where = $this->whereExp($param);
		$result = $this->getRow($param, false, $field . " as n");
		if(isset($result['n'])) return $result['n'];
		return 0;
	}

	public function getColumn($param, $key='', $order='')
	{	
		$key || $key = $this->_pk;
		$result = '';
		if(!$key) return $result;
		$where = $this->whereExp($param); // is_array($param) ? $this->whereExp($param) : "`" . $this->_pk . "`='" . $param . "'";
		$sql = 'select `' . $key . '` from `' . $this->_table . "`";
		$where && $sql.= " Where " . $where;
		$order && $sql.= " Order by ". $order;
		$result = db()->fetchCol($sql);		
		return $result;
	}

	public function getAll($param=array(), $limit='', $order='', $cache=false, $out=false, $field = '')
	{
		$result = array();		
		$where = $this->whereExp($param);		
		if($cache)
		{			
			$cache_key = $this->_cache_key . $this->get_cache_key($where, $limit, $order);
			$result = cache()->get($cache_key);			
			if($result) return $result;
		}		
		// if(!$where) return $result;
		$field || $field = '*';
		$sql = 'select '.$field.' from `' . $this->_table ."`". ($where ? " where " . $where : '');
		$order && $sql.= " Order by "  . $order;
		$limit && $sql.= " Limit " . $limit;		
		$res = db()->fetchAll($sql);		
		if($res && $out)
		{
			foreach($res as $key=>$item)
			{
				$res[$key] = $this->Format($item);
			}
		}
		if($cache)
		{			
			if(!$cache_key) $cache_key = $this->_cache_key . $this->get_cache_key($where, $limit, $order);			
			cache()->set($cache_key, $res, $this->_timelife);			
		}
		$res && $result = $res;
		return $result;
	}

	public function whereExp($param)
	{
		$result = '';		
		if(is_array($param))
		{
			$where = array();
			foreach($param as $key => $item)
			{
				// !,>,<,<>,IN
				$pos = '='; $alias = '';
				if(strpos($key, ','))
				{
					list($key, $pos) = explode(',', $key);
				}		
				if(strpos($key, '.'))
				{
					list($alias, $key) = explode('.', $key);	
					$key = $alias. ".`" . $key ."`" ;
				}else{
					$key = "`" . $key ."`" ;
				}
				in_array($pos, array('=', '!=', '<>', '>', '<', '<=', '>=', 'in', 'like')) || $pos = '=';				
				if(strtoupper($pos) == 'IN')
				{
					$where[] = sprintf("%s %s ('%s')", $key, $pos, join("','", $item));
				}else if(strtoupper($pos) == 'LIKE')
				{
					$where[] = sprintf("%s like '%%%s%%'", $key, $item);
				}else{
					if(is_numeric($item))
					{
						$where[] = sprintf("%s %s %s", $key, $pos, $item);
					}else{
						$where[] = sprintf("%s %s '%s'", $key, $pos, $item);
					}					
				}
			}
			$result = join(' And ', $where);
		}else if(is_numeric($param))
		{
			$result = sprintf("`%s`='%d'", $this->_pk, $param);
		}else if(strpos($param, '=') || strpos($param, '>') || strpos($param, '<') || strpos($param, 'in') || strpos($param, 'like') ){ // 包含 =、>、<、<>、in
			$result = $param;
		}
		//echo $result . "\n";
		return $result;
	}



	public function insert($data){
		return db()->insert($this->_table, $data);
	}

	public function update($data, $where){		
		if(!$data || !$where) return false;
		$where = $this->whereExp($where);	
		return db()->update($this->_table, $data, $where);
	}

	public function delete($where, $force=false){		
		if(!$where) return false;
		$where = $this->whereExp($where);        
		if($force)
		{
			return db()->delete($this->_table, $where);
		}else{
			return $this->update(array('status' => 1), $where);
		}		
	}
	
	// 自增
	public function increment($key, $where, $step=1) 
	{
		if(!$key || !$where) return false;
		$where = $this->whereExp($where);		
		return db()->increment($this->_table, $key, $where, $step);        
    }     
    // 自减        
	public function decrement($key, $where, $step=1) 
	{
		if(!$key || !$where) return false;
		$where = $this->whereExp($where);
		return db()->decrement($this->_table, $key, $where, 1);
    }  

	// 对象格式化
	public function Format($data)
	{	
		$unuse = isset($this->unUses) && is_array($this->unUses) ? array_merge($this->unUses, $this->_unUse) : $this->_unUse;
		$columns = isset($this->format_columns) && is_array($this->format_columns) ? array_merge($this->format_columns, $this->_format_column) : $this->_format_column;
		$result = array();
		foreach($data as $key => $item)
		{	
			if(in_array($key, $unuse)) continue;
			if(in_array($key, array_keys($columns)))
			{				
				$type = $columns[$key];
				switch($type)
				{
					case '_':
						$column = '_' . $key;
						break;
					case 'json':
						$column = $key;
						$item = json_decode($item, true);							
						break;
					default :
						$column = $key;
						break;
				}
			}else{
				$column = $key;
			}			
			$result[$column] = $item;			
		}		
		return $result;
	}	

	public function __invoke()
	{		
		return $this->object;
	}
	
	// 设置cache
	protected function get_cache_key()
	{
		$param = func_get_args();		
		if(empty($param)) return '';
		$str = json_encode($param, true);
		$str = sprintf("%u", crc32(md5($str)));
		$str && $str = "_" . $str;
		return $str; 
	}


	public function getOrder($order = array())
	{
		if(empty($order)) return ;
		$result = array();
		foreach($order as $key => $value)
		{
			$alias = '';				
			if(strpos($key, '.'))
			{
				list($alias, $key) = explode('.', $key);	
				$key = $alias. ".`" . $key ."`" ;		
			}else{
				$key = "`" . $key ."`" ;
			}
			$result[] = sprintf('%s %s', $key, $value == 1 ? 'desc' : 'asc');
		}
		return join(",", $result);
	}

	public function getLimit($perpage=10, $page=0)
	{
		if($page == 0) return $perpage;
		return (($page - 1) * $perpage) . "," . $perpage;
	}
}
