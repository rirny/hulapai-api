<?php
/*
 * 字符处理类
*/


class Ustring {

	static $_source = array();
	
	public static function substring($str, $start=0, $limit=12) {
		global $encoding;
		if(strtolower($encoding)=='gbk'){
			$strlen=strlen($str);
			if ($start>=$strlen)return $str;
			$clen=0;
			for($i=0;$i<$strlen;$i++,$clen++){
				if(ord(substr($str,$i,1))>0xa0){
					if ($clen>=$start)$tmpstr.=substr($str,$i,2);
					$i++;
				}else{
					if ($clen>=$start)$tmpstr.=substr($str,$i,1);
				}
				if ($clen>=$start+$limit)break;
			}
			$str=$tmpstr;
		}else{
			$patten = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
			preg_match_all($patten, $str, $regs);
			$v = 0; $s = '';
			for($i=0; $i<count($regs[0]); $i++){
				(ord($regs[0][$i]) > 129) ? $v += 2 : $v++;
				$s .= $regs[0][$i];
				if($v >= $limit * 2){
					break;
				}
			}
			$str=$s;
		}
		return $str;
	}
	
	public static function _iconv($str,$to='',$from='') {
		global $encoding;
		if(empty($to))$to=$encoding;
		if(empty($from)){
			if( strtolower($to)=='gbk' ){$from='utf-8';}else{$from='gbk';}
		}
		$to=strtolower($to);
		$from=strtolower($from);
		$isutf8=preg_match( '/^([\x00-\x7f]|[\xc0-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xf7][\x80-\xbf]{3})+$/', $str );
		if( $isutf8 && $to=='utf-8' ) return $str;
		if( !$isutf8 && $to=='gbk' ) return $str;

        $load = & load_class("loader");
		$ch = $load->library('chinese', array('from' => $from, 'to' => $to));
		$str= $ch->convert($str);
		return $str;
	}
    

    // UT*转GBK
    public static function u2g($str){
        return iconv("UTF-8","GBK",$str);
    }
    // GBK转UTF8
    public static function g2u($str){
        return iconv("GBK","UTF-8//ignore",$str);
    }

	public static function _strlen($str) {
		global $encoding;
		if(strtolower($encoding)=='gbk'){
	   		$length=strlen($str);
		}else {
	   		$length=floor(2/3*strlen($str));
		}
		return $length;
	}

	public static function _strtoupper($str){
		if (is_array($str)){
			foreach ($str as $key => $val){
				$str[$key] = string::_strtoupper($val);
			}
		}else{
			$i=0;
			$total = strlen($str);
			$restr = '';
			for ($i=0; $i<$total; $i++){
				$str_acsii_num = ord($str[$i]);
				if($str_acsii_num>=97 and $str_acsii_num<=122){
					$restr.=chr($str_acsii_num-32);
				}else{
					$restr.=chr($str_acsii_num);
				}
			}
		}
		return $restr;
	}
	
	public static function _strtolower($string){
		if (is_array($string)){
			foreach ($string as $key => $val){
				$string[$key] = string::_strtolower($val);
			}
		}else{
			$string = strtolower($string);
		}
		return $string;
	}

	public static function _addslashes($string, $force = 0) {
		if( ! MAGIC_QUOTES_GPC || $force) {
			if(is_array($string)) {
				foreach($string as $key => $val) {
					$string[$key] = string::_addslashes($val, $force);
				}
			}else {
				$string = addslashes($string);
			}
		}
		return $string;
	}

	public static function _stripslashes($string) {
		while(@list($key,$var) = @each($string)) {
			if ($key != 'argc' && $key != 'argv' && (strtoupper($key) != $key || ''.intval($key) == "$key")) {
				if (is_string($var)) {
					$string[$key] = stripslashes($var);
				}
				if (is_array($var))  {
					$string[$key] = string::_stripslashes($var);
				}
			}
		}
		return $string;
	}
	public static function convercharacter($str){
		$str=str_replace('\\\r',"",$str);
		$str=str_replace('\\\n',"",$str);
		$str=str_replace('\n',"",$str);
		$str=str_replace('\r',"",$str);
		return $str;
	}

	public static function getfirstletter($string) {
		$string = self::u2g($string);
		$dict=array(
			'a'=>0xB0C4,'b'=>0xB2C0,'c'=>0xB4ED,'d'=>0xB6E9,
			'e'=>0xB7A1,'f'=>0xB8C0,'g'=>0xB9FD,'h'=>0xBBF6,
			'j'=>0xBFA5,'k'=>0xC0AB,'l'=>0xC2E7,'m'=>0xC4C2,
			'n'=>0xC5B5,'o'=>0xC5BD,'p'=>0xC6D9,'q'=>0xC8BA,
			'r'=>0xC8F5,'s'=>0xCBF9,'t'=>0xCDD9,'w'=>0xCEF3,
			'x'=>0xD188,'y'=>0xD4D0,'z'=>0xD7F9,
			);
		$letter = substr($string, 0, 1);
		if ($letter >= chr(0x81) && $letter <= chr(0xfe)) {
			$num = hexdec(bin2hex(substr($string, 0, 2)));
			foreach ($dict as $k=>$v){
				if($v>=$num)
					break;
				}
				return $k;
			}
		elseif((ord($letter)>64&&ord($letter)<91) || (ord($letter)>96&&ord($letter)<123) ){
			return $letter;
		}else{
			return 0;
		}
	}
	
	public static function stripspecialcharacter($string) {
		$string=trim($string);
		$string=str_replace("&","",$string);
		$string=str_replace("\'","",$string);
		$string=str_replace("'","",$string);
		$string=str_replace("&amp;amp;","",$string);
		$string=str_replace("&amp;quot;","",$string);
		$string=str_replace("\"","",$string);
		$string=str_replace("&amp;lt;","",$string);
		$string=str_replace("<","",$string);
		$string=str_replace("&amp;gt;","",$string);
		$string=str_replace(">","",$string);
		$string=str_replace("&amp;nbsp;","",$string);
		$string=str_replace("\\\r","",$string);
		$string=str_replace("\\\n","",$string);
		$string=str_replace("\n","",$string);
		$string=str_replace("\r","",$string);
		$string=str_replace("\r","",$string);
		$string=str_replace("\n","",$string);
		$string=str_replace("'","&#39;",$string);
		$string=nl2br($string);
		return $string;
	}

	public static function convert_to_unicode($string){
		global $encoding;
		if($encoding=='GBK'){
			$string=string::_iconv($string,'utf-8','gbk');
		}
		$string=preg_replace(
				"/([\\xc0-\\xff][\\x80-\\xbf]*)/e",
				"' U8' . bin2hex( \"$1\" )",
				self::_strtolower( $string ) );
		if(strlen($string)<4){
			return $string;
		}
		return $string;
	}
    
    public static function unicode_encode($name)
    {
        $name = iconv('UTF-8', 'UCS-2', $name);
        $len = strlen($name);
        $str = '';
        for ($i = 0; $i < $len - 1; $i = $i + 2)
        {
            $c = $name[$i];
            $c2 = $name[$i + 1];
            if (ord($c) > 0)
            {    // 两个字节的文字
                $str .= '\u'.base_convert(ord($c), 10, 16).base_convert(ord($c2), 10, 16);
            }
            else
            {
                $str .= $c2;
            }
        }
        return $str;
    }

	public static function stripscript($string){
		$pregfind = array(
		"/<script.*>.*<\/script>/siU",
		'/on(mousewheel|mouseover|click|load|onload|submit|focus|blur)="[^"]*"/i',
		);
		$pregreplace = array(
			'',
			'',
		);
		$string = preg_replace($pregfind, $pregreplace, $string);
		return $string;
	}

    /**
     * (功能描述)
     *
     * @param    (类型)     (参数名)    (描述)
     */
    public static function topinyin($str,$ishead=0,$isclose=1)
    {	
        $str = strtolower(self::u2g($str));
        $pinyins = self::getSource();       
        $restr = '';
        $str = trim($str);
        $slen = strlen($str);
        if( $slen < 2){
            return $str;
        }
        for($i=0;$i<$slen;$i++){
            if(ord($str[$i])>0x80){
                $c = $str[$i].$str[$i+1];
                $i++;
                if(isset($pinyins[$c])){
                    if($ishead==0){
                        $restr .= $pinyins[$c];
                    }
                    else{
                        $restr .= $pinyins[$c][0];
                    }
                }else{
                    //$restr .= "_";
                }
            }else if(preg_match("/[a-z0-9]/",$str[$i]) ){
                $restr .= $str[$i];
            }
            else{
                //$restr .= "_";
            }
        }
        if($isclose==0){
            unset($pinyins);
        }
        return $restr;      
    }
	
	public static function getSource()
	{
		if(empty(self::$_source))
		{
			$fp = fopen(dirname(__FILE__) . "/pinyin.dat",'r');
			while(!feof($fp)){
				$line = trim(fgets($fp));
				self::$_source[$line[0].$line[1]] = substr($line,3,strlen($line)-3);
			}
			fclose($fp);
		}
		return self::$_source;
	}

    public static function getfirstchar($str)
    {
        $fchar = ord($str{0}); 
        if($fchar >= ord("A") and $fchar <= ord("z") )
            return strtoupper($str{0});        
        $str = self::u2g($str);
        $asc = ord($str{0}) * 256 + ord($str{1}) - 65536;
        if($asc >= -20319 and $asc <= -20284) return "A"; 
        if($asc >= -20283 and $asc <= -19776) return "B"; 
        if($asc >= -19775 and $asc <= -19219) return "C"; 
        if($asc >= -19218 and $asc <= -18711) return "D"; 
        if($asc >= -18710 and $asc <= -18527) return "E"; 
        if($asc >= -18526 and $asc <= -18240) return "F"; 
        if($asc >= -18239 and $asc <= -17923) return "G"; 
        if($asc >= -17922 and $asc <= -17418) return "H"; 
        if($asc >= -17417 and $asc <= -16475) return "J"; 
        if($asc >= -16474 and $asc <= -16213) return "K"; 
        if($asc >= -16212 and $asc <= -15641) return "L"; 
        if($asc >= -15640 and $asc <= -15166) return "M"; 
        if($asc >= -15165 and $asc <= -14923) return "N"; 
        if($asc >= -14922 and $asc <= -14915) return "O"; 
        if($asc >= -14914 and $asc <= -14631) return "P"; 
        if($asc >= -14630 and $asc <= -14150) return "Q"; 
        if($asc >= -14149 and $asc <= -14091) return "R"; 
        if($asc >= -14090 and $asc <= -13319) return "S"; 
        if($asc >= -13318 and $asc <= -12839) return "T"; 
        if($asc >= -12838 and $asc <= -12557) return "W"; 
        if($asc >= -12556 and $asc <= -11848) return "X"; 
        if($asc >= -11847 and $asc <= -11056) return "Y"; 
        if($asc >= -11055 and $asc <= -10247) return "Z"; 
        return null;        
    }
	
}

?>