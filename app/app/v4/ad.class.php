<?php
class Ad_Api extends Api
{

	public function __construct(){
		// parent::_init();
		$cache = Http::post('tm', 'int', 0);
		$this->cache = $cache ? false : true;
		$this->refresh = Http::post('refresh', 'trim', 0);
	}

	// 广告
	public function index()
	{
		$sql = "select id,title,thumb,inputtime from phpcms.v9_hulapai where catid=19 order by listorder Asc limit 4";
		$result = db()->fetchAll($sql);
		array_walk($result, function(&$v){
			$v['url'] = 'http://client.hulapai.com/?app=ad&act=info&v=4&id=' . $v['id'];
		});
		Out(1, 'success', $result);
	}
	
	public function info()
	{
		$id = Http::get('id', 'int', 0);
		$path = SYS . "/upload";		
		// $path = "E:/www/hulapai/static";
		$file = $path. "/html/ad/{$id}.html";

		if($this->refresh || !file_exists($file))
		{
			$id = Http::get('id', 'int', 0);
			if(!$id) throw new exception('错误的参数');
			$sql = "select d.id,d.title,d.thumb,d.inputtime,s.content from phpcms.v9_hulapai d left join phpcms.v9_hulapai_data s On d.id=s.id where d.id={$id}";
			$data = db()->fetchRow($sql);
			$content = file_get_contents(SYS . '/comm/ad.tpl.html');
			$article = array(
				'title' => $data['title'],
				'time' => date('Y-m-d H:i', $data['inputtime']),
				'content' => $data['content']
			);
			$content = str_replace(array('{title}', '{time}', '{content}'), $article, $content);
			file_put_contents($file, $content, true);
			echo $content;
		}else{
			header("location:http://static.hulapai.com/html/ad/{$id}.html");
		}		
	}


}