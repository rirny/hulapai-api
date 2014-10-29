<?php
Abstract class Model_New{

	protected $db = Null;	
	protected $_prefix = 't_';
	protected $_table = '';
	protected $_pk = 'id';

    protected $_order = '';
    protected $_limit = '';
    protected $_field = '*';
	protected $_group = '';

	// cache
	protected $_cache = Null;
	protected $_cache_key = '';
	protected $_expire = 1800;

	protected $_perpage = 10;
	protected $_page = 1;

	public $status = True;
	public $message = '';

	public function __construct($param = array()){
		$this->db = db();
		if($param)
		{
			foreach($param as $key => $item)
			{
				$_key = '_' . $key;
				// $key == 'table' && $item = $this->_prefix . $item;
				$this->$_key = $item;
			}
		}
		if(!$this->_prefix && TABLE_PREFIX != '')
		{
			$this->_prefix = TABLE_PREFIX;
		}
	}

	public function set($key, $value = Null)
	{
         $_key = '_' . $key;
		if(isset($this->$_key))
		{           
			// return $this->$_key ;
			$this->$_key = $value;
		}
		return $this;
	}
	
	public function __get($key)
	{
         $_key = '_' . $key;
		if(isset($this->$_key))
		{           
			return $this->$_key ;
		}
	}

	public function __destruct(){}
	
	public function Row($cache = false)
	{
		$result = array();		
		$sql = 'select ' . $this->_field . ' from `' . $this->_prefix.$this->_table . "`";
		$this->_limit = 1;
		$result = false;
		if($cache)
		{		
			$this->_init_cache('row');
			$result = cache()->get($this->_cahce_key);			
		}
		if($result === false || $cache === false)
		{
			$this->_where && $sql.= " Where " . $this->_where;
			$this->_group && $sql.= " Group by ". $this->_group;
			$this->_order  && $sql.= " Order by ". $this->_order;			
			$sql.= " Limit ". $this->_limit;	
			//echo $sql."\n";
			$result = $this->db->fetchRow($sql);
			if($cache) cache()->set($this->_cahce_key, $result, $this->_expire);
		}
		return $result;
	}

	private function _init_cache($method = 'row')
	{
		$param = array($this->_table, $this->_field, $this->_where, $this->_order);
		if($method != 'count') $param[] = $this->_limit;
		$this->cache_key = $method . '_' . md5(join("-", $param));
		// Return cache();
	}

	public function Count($cache = false)
	{			
		$this->_field = "count($this->_field) as n";
		$result = false;
		if($cache)
		{		
			$this->_init_cache('count');
			$result = cache()->get($this->_cahce_key);			
		}
		if($result === false || $cache === false)
		{
			$res = $this->Row();
			if(isset($res['n'])) $result = $res['n'];
			if($cache) cache()->set($this->_cahce_key, $result, $this->_expire);
		}
		if($result) Return $result;
		return 0;
	}

	public function Column($cache = false)
	{
		$result = false;
		if($cache)
		{		
			$this->_init_cache('column');
			$result = cache()->get($this->_cahce_key);			
		}		
		if($result === false || $cache === false)
		{
			$sql = 'select ' . $this->_field . ' from `' . $this->_prefix.$this->_table . "`";
			$this->_where && $sql.= " Where " . $this->_where;
			$this->_group && $sql.= " Group by ". $this->_group;
			$this->_order && $sql.= " Order by ". $this->_order;
			//echo $sql;
			$result = $this->db->fetchCol($sql);
			if($result && $this->_limit == 1) $result = current($result);
			if($cache) cache()->set($this->_cahce_key, $result, $this->_expire);
		}
		return $result;
	}

	public function Page()
	{
		$page = $this->_page;
		$perpage = $this->_perpage;
		if(!$perpage) return false;
		$records = $this->Count();
		$pageCount = ceil($records/$perpage);
		Return array('page'=>$page, 'total'=> $records, 'size'=>$perpage, 'pages'=> $pageCount);
		// Return compact('page', 'perpage', 'records', 'pageCount');
	}

	public function Result($cache = false)
	{
		$result = array();
		if($cache)
		{		
			$this->_init_cache('list');
			$result = cache()->get($this->_cahce_key);			
		}
		if(empty($result) || $cache === false)
		{
			$sql = 'select '.$this->_field.' from `' . $this->_prefix.$this->_table ."`". ($this->_where ? " where " . $this->_where : '');
			$this->_group && $sql.= " Group by ". $this->_group;			
			$this->_order && $sql.= " Order by "  . $this->order;
			$this->_limit && $sql.= " Limit " . $this->_limit;
			//echo $sql;
			$result = $this->db->fetchAll($sql);
			if($cache) cache()->set($this->_cahce_key, $result, $this->_expire);
		}
		return $result;
	}
	
	// 清除条件
	public function clear()
	{
		$this->_where = '';
		$this->_limit = '';
		$this->_order = '';
		$this->_group = '';
		$this->_field = '*';
		$this->_page = 0;
		$this->_perpage = 0;
		$this->message = '';
		$this->status = True;
		return $this;
	}

	public function where()
	{
		$args = func_num_args();
		$reset = False;
		$param = $value = Null;
		if($args == 3)
		{
			func_get_arg(2) === true && $reset = True;
			$param = func_get_arg(0);
			$value = func_get_arg(1);
		}else if($args == 2)
		{
			$param = func_get_arg(0);
			if(true === func_get_arg(1))
			{
				$reset = True;
			}else{
				$value = func_get_arg(1);
			}			
		}else if($args == 1){
			if(func_get_arg(0) === true)
			{
				$reset = True;
			}else{
				$param = func_get_arg(0);
			}			
		}else{
			$reset = True;
		}
		
		if($reset) $this->_where = '';
		if(!$param) return $this;
		
		if( Null !== $value && is_string($param))
		{
			$param = Array($param => $value);
		}
		$where =  $this->whereExp($param);
		if(!$where) return $this;
		$this->_where && $this->_where .= ' And ';
		$this->_where .= $where;				
		return $this;
	}

	public function or_where()
	{
		$args = func_num_args();
		$reset = False;
		$param = $value = Null;
		if($args == 3)
		{
			func_get_arg(2) === true && $reset = True;
			$param = func_get_arg(0);
			$value = func_get_arg(1);
		}else if($args == 2)
		{
			$param = func_get_arg(0);
			if(true === func_get_arg(1))
			{
				$reset = True;
			}else{
				$value = func_get_arg(1);
			}			
		}else if($args == 1){
			if(func_get_arg(0) === true)
			{
				$reset = True;
			}else{
				$param = func_get_arg(0);
			}			
		}else{
			$reset = True;
		}

		if($reset) $this->_where = '';
		if(!$param) return $this;
		if( Null !== $value && is_string($param))
		{
			$param = Array($param => $value);
		}

		$where =  $this->whereExp($param, true);
		if(!$where) return $this;
		$this->_where && $this->_where = "(" . $this->_where . ") And ";
		$this->_where .=  "({$where})";	
		return $this;
	}

	public function whereExp($param, $or=false)
	{
		$result = '';		
		if(is_array($param))
		{
			$where = array();
			foreach($param as $key => $item)
			{		
				$_whereStr = $this->_whereFormat($key, $item);
				$_whereStr && $where[] = $_whereStr;
			}
			$connector = $or ? ' Or ' : ' And '; 
			$result = join($connector, $where);
		}else if(is_numeric($param))
		{
			$this->_pk && $result = sprintf("`%s`='%d'", $this->_pk, $param);
		}else if(strpos($param, '=') || strpos($param, '>') || strpos($param, '<') || strpos($param, 'in') || strpos($param, 'like') || strpos($param, ' or ')){ // 包含 =、>、<、<>、in
			$result = $param;
		}
		//echo $result . "\n";
		return $result;
	}

	public function _whereFormat($key, $item)
	{	
		// !,>,<,<>,IN,not in		
		$result = '';
		$pos = '='; $alias = '';
		if(strpos($key, ',') !== false)
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
		in_array($pos, array('=', '!=', '<>', '>', '<', '<=', '>=', 'in', 'like', 'or', 'notin', '&')) || $pos = '=';				
		if(strtoupper($pos) == 'IN')
		{
			if(is_array($item) && !empty($item))
			{
				$result = sprintf("%s %s ('%s')", $key, $pos, join("','", $item));
			}
		}else if(strtoupper($pos) == 'NOTIN')
		{
			if(is_array($item) && !empty($item))
			{
				$pos = 'Not In';
				$result = sprintf("%s %s ('%s')", $key, $pos, join("','", $item));
			}
		}else if(strtoupper($pos) == 'LIKE')
		{
			$result = sprintf("%s like '%%%s%%'", $key, $item);
		}else if(strtoupper($pos) == '&') // 余运算
		{
			$result = sprintf("%s & %s", $key, $item);
		}else if(strtoupper($pos) == 'OR' && is_array($item))
		{
			$result = "(" . $this->whereExp($item, true) . ")";
		}else{
			if(is_numeric($item))
			{
				$result = sprintf("%s %s %s", $key, $pos, $item);
			}else{
				$result= sprintf("%s %s '%s'", $key, $pos, $item);
			}					
		}
		Return $result;
	}

	public function insert($data){
		array_walk($data, create_function('&$v', 'if(is_array($v)) $v= json_encode($v);'));
		return $this->db->insert($this->_prefix.$this->_table, $data);
	}

	public function getmax($key){
		if(!$key) return false;
		$sql = 'select max('.$key.')'. ' from `' . $this->_prefix.$this->_table . "`";
		$this->_where && $sql.= " Where " . $this->_where;
		return $this->db->fetchRow($sql);
	}

	public function update($data){		
		if(!$data) return false;
		array_walk($data, create_function('&$v', 'if(is_array($v)) $v= json_encode($v);'));
		return $this->db->update($this->_prefix.$this->_table, $data, $this->_where);
	}

	public function delete($force = true){		
		if(!$this->_where) return false;  
		if($force)
		{
			return $this->db->delete($this->_prefix.$this->_table, $this->_where);
		}else{
			return $this->update(array('status' => 1), $this->_where);
		}		
	}
	
	// 自增
	public function increment($key, $step=1) 
	{
		if(!$key) return false;
		return $this->db->increment($this->_prefix.$this->_table, $key, $this->_where, $step);        
    }     
    // 自减        
	public function decrement($key, $step=1) 
	{
		if(!$key) return false;		
		return $this->db->decrement($this->_prefix.$this->_table, $key, $this->_where, 1);
    }

	public function __invoke()
	{		
		return $this->object;
	}
	/*
	 * 
	*/
	public function order()
	{
		$args = func_num_args();
		$reset = False;
		$param = $value = Null;
		if($args == 3)
		{
			func_get_arg(2) === true && $reset = True;
			$param = func_get_arg(0);
			$value = func_get_arg(1);
		}else if($args == 2)
		{
			$param = func_get_arg(0);
			if(true === func_get_arg(1))
			{
				$reset = True;
			}else{
				$value = func_get_arg(1);
			}			
		}else{
			func_get_arg(0) === true && $reset = True;			
		}
		if($reset) $this->_order = '';
		if(!$param) return $this;

		if( Null !== $value && is_string($param))
		{
			$param = Array($param => $value);
		}
		if(!is_array($param)) return $this;

		foreach($param as $key => $value)
		{
			$alias = '';				
			if(strpos($key, '.'))
			{
				list($alias, $key) = explode('.', $key);	
				$key = $alias. ".`" . $key ."`" ;		
			}else{
				$key = "`" . $key ."`" ;
			}
			$result[] = sprintf('%s %s', $key, ($value != 'asc' || strtolower($value) == 'desc') ? 'desc' : 'asc');
		}
		if($result)
		{
			$this->_order && $this->_order .= ',';
			$this->_order .= join(",", $result);
		}
		return $this;
	}

	public function field($field='*')
	{		
		$this->_field = $this->_quoteField($field);
		return $this;
	}

	protected function _quoteField($field = '*')
	{
		if($field == '*' || $field =='') return '*';
		$fields = explode(",", str_replace("`", '', $field));
		foreach($fields as &$v)
		{
			$alias = $key = $tmp = $fun = '';
			$fun = false;
			// count:A sum:A
			// contact:A&B
			$v = str_replace("&", ",", $v); // concat

			if(preg_match('/[A-Za-z]+\((.*)\)+/i', $v, $match)) // 带函数 count(name) contact(A,B),仅匹配一个 // 多个函数还需要处理
			{
				$_v = $this->_quoteField($match[1]);			
				$key = str_replace($match[1], $_v, $v);
				$fun = true;
			}else if(strpos($v, '.'))
			{
				list($alias, $key) = explode('.', $v);					
			}else{
				$key = $v;
			}			
			$alias && $tmp = "`{$alias}`.";
			$key = trim($key);
			

			if(strpos($key, " ")) 
			{
				list($key, $as) = explode(" ", $key);
				$fun || $key = "`{$key}`";
				$key .= " As " . $as;
			}else{				
				$fun || $key = "`{$key}`";				
			}
			$key == '*' || $tmp .= $key;
			$v = $tmp;
		}
		return join(",", $fields);
	}

	public function limit($perpage=10, $page=0)
	{
		$args = func_num_args();
		if($args < 1) {
			$this->_limit = '';
			return $this;
		}
		$this->_page = (int)$page;
		$this->_perpage = (int)$perpage;
		if($page)
		{	
			$offset = ($this->_page  - 1) * $this->_perpage;
			$this->_limit = "{$offset},{$this->_perpage}";
		}else{
			$this->_limit = $perpage;
		}
		return $this;
	}
	
	public function group($key = '')
	{
		$key && $this->_group = $key;
		Return $this;
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

	protected function error($message, $code=0)
	{
		$this->status = $code;
		$this->message = $message;
		return false;
	}
}
