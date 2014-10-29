<?php
/*
 * 参数过滤
 * 
*/
class Filter
{
	public static function String($key=null, $default=null){
		if($key === null) return $defalut;
	}
	
	/**
	 * fiterHtml函数用于过滤标签，输出没有html的干净的文本
	 * @param string text 文本内容
	 * @return string 处理后内容
	 */
	public static function fiter_html($html){
	    $text = nl2br($text);
	    $text = Filter::real_strip_tags($html);
	    $text = addslashes($text);
	    $text = trim($text);
	    return $text;
	}
	
	/** 
	 * safeHtml函数用于过滤不安全的html标签，输出安全的html
	 * @param string $text 待过滤的字符串
	 * @param string $type 保留的标签格式
	 * @return string 处理后内容
	 */
	public static function safe_html($html, $type = 'html'){
	    // 无标签格式
	    $text_tags  = '';
	    //只保留链接
	    $link_tags  = '<a>';
	    //只保留图片
	    $image_tags = '<img>';
	    //只存在字体样式
	    $font_tags  = '<i><b><u><s><em><strong><font><big><small><sup><sub><bdo><h1><h2><h3><h4><h5><h6>';
	    //标题摘要基本格式
	    $base_tags  = $font_tags.'<p><br><hr><a><img><map><area><pre><code><q><blockquote><acronym><cite><ins><del><center><strike>';
	    //兼容Form格式
	    $form_tags  = $base_tags.'<form><input><textarea><button><select><optgroup><option><label><fieldset><legend>';
	    //内容等允许HTML的格式
	    $html_tags  = $base_tags.'<meta><ul><ol><li><dl><dd><dt><table><caption><td><th><tr><thead><tbody><tfoot><col><colgroup><div><span><object><embed><param>';
	    //专题等全HTML格式
	    $all_tags   = $form_tags.$html_tags.'<!DOCTYPE><html><head><title><body><base><basefont><script><noscript><applet><object><param><style><frame><frameset><noframes><iframe>';
	    //过滤标签
	    $html = Filter::real_strip_tags($html, ${$type.'_tags'});
	    // 过滤攻击代码
	    if($type != 'all') {
	        // 过滤危险的属性，如：过滤on事件lang js
	        while(preg_match('/(<[^><]+)(ondblclick|onclick|onload|onerror|unload|onmouseover|onmouseup|onmouseout|onmousedown|onkeydown|onkeypress|onkeyup|onblur|onchange|onfocus|action|background|codebase|dynsrc|lowsrc)([^><]*)/i',$text,$mat)){
	            $html = str_ireplace($mat[0], $mat[1].$mat[3], $html);
	        }
	        while(preg_match('/(<[^><]+)(window\.|javascript:|js:|about:|file:|document\.|vbs:|cookie)([^><]*)/i',$text,$mat)){
	            $html = str_ireplace($mat[0], $mat[1].$mat[3], $html);
	        }
	    }
	    return $html;
	}
	
	public static function real_strip_tags($str, $allowable_tags="") {
	    $str = stripslashes(htmlspecialchars_decode($str));
	    return strip_tags($str, $allowable_tags);
	}
	
	/**
	 * 敏感词过滤
	 */
	public static function filter_keyword($html) {
	    static $audit  =null;
	    static $auditSet = null;
	    if($audit == null){ //第一次
	        $audit = cache('apc')->getData('t_keyword');
	    }
	    // 不需要替换
	    if(empty($audit)){
	        return $html;
	    }
	    return str_replace($audit, '**', $html);
	}
	

}