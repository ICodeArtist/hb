<?php
/*
 * @Author: daichengxiang 
 * @Date: 2019-04-29 14:39:06 
 * @Last Modified by:   daichengxiang 
 * @Last Modified time: 2019-04-29 14:39:06 
 */
class adminController extends grace{
	
	public function index(){
		
	}
	public function login(){
		$username = $_POST['username'];
		$password = $_POST['password'];
		echo $username;
	}
}