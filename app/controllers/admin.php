<?php
/*
 * @Author: daichengxiang 
 * @Date: 2019-04-29 14:39:06 
 * @Last Modified by: daichengxiang
 * @Last Modified time: 2019-05-31 13:56:26
 */
class adminController extends grace{
	public function index(){
		
	}
	public function login(){
		$username = $_POST['username'];
		$password = $_POST['password'];
		$admindb = db('admin');
		$data = $admindb->where('username=?',array($username))->fetch();
		$res['token'] = $data['token'];
		if($data['password'] == md5($password)){
			$this->json($res);
		}else{
			$this->json('','error');
		}
		
	}
	public function info(){
		$access_token = $_SERVER['HTTP_ACCESS_TOKEN'];
		$userInfo = db('admin')->where('token=?',array($access_token))->fetch();
		if(!empty($userInfo)){
			$roleObj = db('role')->where('roleId=?',array($userInfo['roleId']))->fetch();
			$permissions = db('role_permission')->where('roleId=?',$roleObj['roleId'])->fetchAll();
			foreach ($permissions as $key => $value) {
				$permissions[$key]['actionEntitySet'] = json_decode($value['actions']);
				$permissions[$key]['actionList'] = null;
				$permissions[$key]['dataAccess'] = null;
			}
			$roleObj['permissions'] = $permissions;
			$userInfo['role'] = $roleObj;
			$this->json($userInfo);
		}else{
			$this->json('','error');
		}
	}
}