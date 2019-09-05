<?php
/*
 * @Author: daichengxiang 
 * @Date: 2019-05-21 14:39:16 
 * @Last Modified by:   daichengxiang 
 * @Last Modified time: 2019-05-21 14:39:16 
 */
namespace phpGrace\tools;
class token{
	
	public static function getToken(){
		
		return md5(uniqid(md5(microtime(true)),true));
	}
	
	
}