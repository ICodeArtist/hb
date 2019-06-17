<?php
/*
 * @Author: daichengxiang 
 * @Date: 2019-04-24 09:47:47 
 * @Last Modified by: daichengxiang
 * @Last Modified time: 2019-06-11 17:17:18
 */

class indexController extends grace{
	
	public function index(){
		
	}

	public function companylist(){
		$db = db('company');
		$query = '1=?';
		$queryarr[] = '1';
		if(isset($_GET['name']) && $_GET['name']){
			$query .= " and c.name like '%".$_GET['name']."%'";
		}
		if(isset($_GET['areaid']) && $_GET['areaid']>0){
			$query .= " and areaid=?";
			$queryarr[] = $_GET['areaid'];
		}
		$data = $db->where($query,$queryarr)
		->join('as c left join area as a on c.areaid=a.id')
		->limit(($_GET['pageNo']-1)*$_GET['pageSize'],$_GET['pageSize'])->fetchAll('c.*,a.name as area');
		$res['data'] = $data;
		// $res['pageSize'] = (int)$_GET['pageSize'];
		$res['pageNo'] = (int)$_GET['pageNo'];
		$res['totalCount'] = (int)count($db->where($query,$queryarr)->join('as c left join area as a on c.areaid=a.id')->fetchAll('c.*,a.name as area'));
		// $res['totalPage'] = ceil($res['totalCount']/$_GET['pageSize']);
		$this->json($res);
	}
	//地区
	public function areas(){
		$data = db('area')->fetchAll();
		$this->json($data);
	}

	//公司
	public function companys(){
		$data = db('company')->fetchAll();
		$this->json($data);
	}
	//
	public function addCompany(){
		if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){

		}else{
			$cid = $_POST['id'];
			unset($_POST['id']);
			if($cid>0){
				$companyid = db('company')->where('id=?',array($cid))->update($_POST);
			}else{
				$companyid = db('company')->add($_POST);
			}
			$companyid?$this->json(''):$this->json('','-1','失败');
		}
	}

	public function delCompany(){
		db('company')->where('id = ?', array($_GET['id']))->delete();
		$this->json('');
	}


	public function equiplist(){
		$db = db('equip');
		$query = '1=?';
		$queryarr[] = '1';
		// if(isset($_GET['name']) && $_GET['name']){
		// 	$query .= " and c.name like '%".$_GET['name']."%'";
		// }
		// if(isset($_GET['areaid']) && $_GET['areaid']>0){
		// 	$query .= " and areaid=?";
		// 	$queryarr[] = $_GET['areaid'];
		// }
		$data = $db->where($query,$queryarr)
		->join('as e left join company as c on e.companyid=c.id')
		->limit(($_GET['pageNo']-1)*$_GET['pageSize'],$_GET['pageSize'])->fetchAll('e.*,c.name as cname');
		$res['data'] = $data;
		$res['pageNo'] = (int)$_GET['pageNo'];
		$res['totalCount'] = (int)count($db->where($query,$queryarr)->join('as e left join company as c on e.companyid=c.id')->fetchAll('e.*,c.name as cname'));
		$this->json($res);
	}
	public function addEquip(){
		if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){

		}else{
			$eqid = $_POST['id'];
			unset($_POST['id']);
			if($eqid>0){
				$companyid = db('equip')->where('id=?',array($eqid))->update($_POST);
			}else{
				$companyid = db('equip')->add($_POST);
			}
			$companyid?$this->json(''):$this->json('','-1','失败');
		}
	}
	public function delEquip(){
		db('company')->where('id = ?', array($_GET['id']))->delete();
		$this->json('');
	}
	public function realtimedata(){
		$query = '1=?';
		$queryarr[] = '1';
		if(isset($_GET['cname']) && $_GET['cname']){
			$query .= " and c.name like '%".$_GET['cname']."%'";
		}
		if(isset($_GET['begintime']) && $_GET['begintime'] && isset($_GET['endtime']) && $_GET['endtime']){
			$query .=" and m.createTime>? and m.createTime<?";
			$queryarr[] = $_GET['begintime'];
			$queryarr[] = $_GET['endtime'];
		}
		$data = db('machine')->where($query,$queryarr)
		->join('as m left join equip as eq on eq.id=m.equipid left join company as c on c.id=eq.companyid')
		->limit(($_GET['pageNo']-1)*$_GET['pageSize'],$_GET['pageSize'])->fetchAll('m.*,eq.name as eqname,c.name as cname');
		$errortime = 2*86400;
		foreach ($data as $key => $value) {
			(strtotime($value['createTime'])+$errortime)<time()?$data[$key]['status'] = '0': $data[$key]['status'] = '1';
		}
		$res['data'] = $data;
		$res['pageNo'] = (int)$_GET['pageNo'];
		$res['totalCount'] = (int)count(db('machine')->where($query,$queryarr)->join('as m left join equip as eq on eq.id=m.equipid left join company as c on c.id=eq.companyid')->fetchAll('m.*,eq.name as eqname,c.name as cname'));
		$this->json($res);
	}
	public function logdata(){
		if(isset($_GET['machineid']) && $_GET['machineid']){
			$query = 'machineid=?';
			$queryarr[] = explode('_',$_GET['machineid'])[1];
			$data = db('engdata')->where($query,$queryarr)
			->join('as e left join machine as m on m.id=e.machineid left join equip as eq on eq.id=m.equipid left join company as c on c.id=eq.companyid')
			->limit(($_GET['pageNo']-1)*$_GET['pageSize'],$_GET['pageSize'])->fetchAll('e.*,m.name,eq.name as eqname,c.name as cname');
			$errortime = 2*86400;
			foreach ($data as $key => $value) {
				(strtotime($value['createTime'])+$errortime)<time()?$data[$key]['status'] = '0': $data[$key]['status'] = '1';
			}
			$totalCount = (int)count(db('engdata')->where($query,$queryarr)->join('as e left join machine as m on m.id=e.machineid left join equip as eq on eq.id=m.equipid left join company as c on c.id=eq.companyid')->fetchAll('e.*,m.name,eq.name as eqname,c.name as cname'));
		}else{
			$data = [];
			$totalCount = 0;
		}
		
		$res['data'] = $data;
		$res['pageNo'] = (int)$_GET['pageNo'];
		$res['totalCount'] = $totalCount;
		$this->json($res);
	}
	public function orgTree(){
		$companys = db('company')->fetchAll('id,name as title');
		foreach ($companys as $k1 => $v1) {
			$equips = db('equip')->where('companyid=?',array($v1['id']))->fetchAll('id,name as title');
			foreach ($equips as $k2 => $v2) {
				$machines = db('machine')->where('equipid=?',array($v2['id']))->fetchAll('id,name as title');
				foreach ($machines as $k3 => $v3) {
					$machines[$k3]['key'] = 'machine_'.$v3['id'];
					unset($machines[$k3]['id']);
				}
				$equips[$k2]['key'] = 'equip_'.$v2['id'];
				$equips[$k2]['selectable'] = false;
				unset($equips[$k2]['id']);
				$equips[$k2]['children'] = array_values($machines);
			}
			$companys[$k1]['key'] = 'company_'.$v1['id'];
			$companys[$k1]['selectable'] = false;
			unset($companys[$k1]['id']);
			$companys[$k1]['children'] = array_values($equips);
		}
		$this->json($companys);
	}
}