<?php
/*
 * @Author: daichengxiang 
 * @Date: 2019-04-24 09:47:47 
 * @Last Modified by: daichengxiang
 * @Last Modified time: 2019-06-20 17:02:50
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
		if(isset($_GET['hyid']) && $_GET['hyid']>0){
			$query .= " and hyid=?";
			$queryarr[] = $_GET['hyid'];
		}
		$data = $db->where($query,$queryarr)
		->join('as c left join area as a on c.areaid=a.id left join hy as h on c.hyid=h.id')->order('id asc')
		->limit(($_GET['pageNo']-1)*$_GET['pageSize'],$_GET['pageSize'])->dcxfetchAll('c.*,a.name as area,h.name as hyname');
		$data['pageNo'] = (int)$_GET['pageNo'];
		$this->json($data);
	}
	//地区
	public function areas(){
		$data = db('area')->fetchAll();
		$this->json($data);
	}
	//行业
	public function hys(){
		$data = db('hy')->fetchAll();
		$this->json($data);
	}
	//公司
	public function companys(){
		$data = db('company')->fetchAll();
		$this->json($data);
	}
	//网关
	public function wgs(){
		$data = db('wg')->fetchAll();
		$this->json($data);
	}
	//公司网关二级联动
	public function companysandwgs(){
		$data = db('company')->fetchAll('id as value,name as label');
		foreach ($data as $key => $value) {
			$data[$key]['children'] = [];
			$children = db('wg')->where('companyid=?',array($value['value']))->fetchAll('id as value,name as label');
			if(empty($children)){
				$data[$key]['disabled'] = true;
			}else{
				$data[$key]['children'] = $children;
			}
		}
		$this->json($data);
	}

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
		db('wg')->where('companyid=?',array($_GET['id']))->delete();
		db('scss')->where('companyid=?',array($_GET['id']))->delete();
		db('zlss')->where('companyid=?',array($_GET['id']))->delete();
		$this->json('');
	}
	public function delWg(){
		db('wg')->where('id=?',array($_GET['id']))->delete();
		db('scss')->where('wgid=?',array($_GET['id']))->delete();
		db('zlss')->where('wgid=?',array($_GET['id']))->delete();
		$this->json('');
	}
	public function delScss(){
		db('scss')->where('id=?',array($_GET['id']))->delete();
		$this->json('');
	}
	public function delZlss(){
		db('zlss')->where('id=?',array($_GET['id']))->delete();
		$this->json('');
	}
	//历史数据
	public function logdata(){
		$query = '1=?';
		$queryarr[] = '1';
		if(isset($_GET['companyname']) && $_GET['companyname']){
			$query .= " and companyname like '%".$_GET['companyname']."%'";
		}
		if(isset($_GET['status']) && $_GET['status']>0){
			$query .= " and status=?";
			$queryarr[] = $_GET['status'];
		}
		$data = db('history201906')->where($query,$queryarr)->limit(($_GET['pageNo']-1)*$_GET['pageSize'],$_GET['pageSize'])
		->dcxfetchAll();
		$data['pageNo'] = (int)$_GET['pageNo'];
		$this->json($data);
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

	public function adminlist(){
		$data = db('admin')->where('deleted=?',array(0))->limit(($_GET['pageNo']-1)*$_GET['pageSize'],$_GET['pageSize'])->dcxfetchAll();
		$data['pageNo'] = (int)$_GET['pageNo'];
		$this->json($data);
	}

	public function rolelist(){
		$data = db('role')->where('deleted=?',array(0))->limit(($_GET['pageNo']-1)*$_GET['pageSize'],$_GET['pageSize'])->fetchAll();
		$res['data'] = $data;
		$res['pageNo'] = (int)$_GET['pageNo'];
		$res['totalCount'] = (int)count(db('role')->where('deleted=?',array(0))->fetchAll());
		$this->json($res);
	}

	public function ssdata(){
		$pageNo = $_GET['pageNo'];
		$pageSize = $_GET['pageSize'];
		$data = $this->getssdata($pageNo,$pageSize);
		$this->json($data);
	}
	 
	public function getssdata($pageNo,$pageSize){
		$query = '1=?';
		$queryarr[] = '1';
		if(isset($_GET['cname']) && $_GET['cname']){
			$query .= " and c.name like '%".$_GET['cname']."%'";
		}
		if(isset($_GET['areaid']) && $_GET['areaid']>0){
			$query .= " and c.areaid=?";
			$queryarr[] = $_GET['areaid'];
		}
		if(isset($_GET['hyid']) && $_GET['hyid']>0){
			$query .= " and c.hyid=?";
			$queryarr[] = $_GET['hyid'];
		}
		if(isset($_GET['cstatus']) && $_GET['cstatus']>0){
			$query .= " and c.status=?";
			$queryarr[] = $_GET['cstatus'];
		}
		if(isset($_GET['status']) && $_GET['status']>0){
			$query .= " and s.status=?";
			$queryarr[] = $_GET['status'];
		}
		$data = db('scss')->join('as s left join company as c on s.companyid=c.id 
		left join wg as w on w.id=s.wgid 
		left join area as a on a.id=c.areaid 
		left join hy as h on h.id=c.hyid')
		->where($query,$queryarr)->limit(($pageNo-1)*$pageSize,$pageSize)
		->dcxfetchAll('s.*,
		c.name as cname,c.remark,c.status as cstatus,
		h.name as hname,
		w.name as wname,w.sn as wsn,
		a.name as aname');
		$db = db('zlss');
		foreach ($data['data'] as $key => $value) {
			for ($i=1; $i <5 ; $i++) {
				$zval = $db->where('companyid=? and wgid=? and no=?',array($value['companyid'],$value['wgid'],$i))->fetch('val,updateTime');
				$data['data'][$key]['zlss'.$i] = $zval['val'];
				$data['data'][$key]['zlss'.$i.'t'] = $zval['updateTime'];
			}
		}
		$data['pageNo'] = (int)$_GET['pageNo'];
		return $data;
	}
	public function wglist(){
		$query = '1=?';
		$queryarr[] = '1';
		if(isset($_GET['cname']) && $_GET['cname']){
			$query .= " and c.name like '%".$_GET['cname']."%'";
		}
		$data = db('wg')->join('as w left join company as c on c.id=w.companyid')->where($query,$queryarr)->limit(($_GET['pageNo']-1)*$_GET['pageSize'],$_GET['pageSize'])
		->dcxfetchAll('w.*,c.name as cname');
		$data['pageNo'] = (int)$_GET['pageNo'];
		$this->json($data);
	}

	public function addWg(){
		if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){

		}else{
			$wgid = $_POST['id'];
			unset($_POST['id']);
			if($wgid>0){
				$wgida = db('wg')->where('id=?',array($wgid))->update($_POST);
			}else{
				$wgida = db('wg')->add($_POST);
			}
			$wgida?$this->json(''):$this->json('','-1','失败');
		}
	}

	public function scsslist(){
		$query = '1=?';
		$queryarr[] = '1';
		if(isset($_GET['cname']) && $_GET['cname']){
			$query .= " and c.name like '%".$_GET['cname']."%'";
		}
		$data = db('scss')->join('as s left join company as c on c.id=s.companyid 
		left join wg as w on w.id=s.wgid')
		->where($query,$queryarr)->limit(($_GET['pageNo']-1)*$_GET['pageSize'],$_GET['pageSize'])
		->dcxfetchAll('s.*,w.sn as wsn,c.name as cname');
		$data['pageNo'] = (int)$_GET['pageNo'];
		$this->json($data);
	}

	public function addScss(){
		if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){

		}else{
			$Scssid = $_POST['id'];
			unset($_POST['id']);
			$_POST['companyid'] = $_POST['cpwg'][0];
			$_POST['wgid'] = $_POST['cpwg'][1];
			unset($_POST['cpwg']);
			if($Scssid>0){
				$Scssida = db('scss')->where('id=?',array($Scssid))->update($_POST);
			}else{
				$Scssida = db('scss')->add($_POST);
			}
			$Scssida?$this->json(''):$this->json('','-1','失败');
		}
	}

	public function zlsslist(){
		$query = '1=?';
		$queryarr[] = '1';
		if(isset($_GET['cname']) && $_GET['cname']){
			$query .= " and c.name like '%".$_GET['cname']."%'";
		}
		$data = db('zlss')->join('as z left join company as c on c.id=z.companyid 
		left join wg as w on w.id=z.wgid')
		->where($query,$queryarr)->limit(($_GET['pageNo']-1)*$_GET['pageSize'],$_GET['pageSize'])
		->dcxfetchAll('z.*,w.sn as wsn,c.name as cname');
		$data['pageNo'] = (int)$_GET['pageNo'];
		$this->json($data);
	}

	public function addZlss(){
		if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){

		}else{
			$zlssid = $_POST['id'];
			unset($_POST['id']);
			$_POST['companyid'] = $_POST['cpwg'][0];
			$_POST['wgid'] = $_POST['cpwg'][1];
			unset($_POST['cpwg']);
			if($zlssid>0){
				$zlssida = db('zlss')->where('id=?',array($zlssid))->update($_POST);
			}else{
				$data = db('zlss')->where('companyid=? and wgid=? and no=?',array($_POST['companyid'],$_POST['wgid'],$_POST['no']))->fetch();
				if(!empty($data))
					$this->json('','-1','不能重复');
				$zlssida = db('zlss')->add($_POST);
			}
			$zlssida?$this->json(''):$this->json('','-1','失败');
		}
	}
	/*=================================*/
	//在中旭阿里云上做的定时任务
	/**
	 * 每个15分钟，把即时数据表中的数据，保存到当前月的数据库中
	 * 按月建表
	 */
	public function savelogdata(){
		$ssdata = $this->getssdata(1,1);
		$totalCount = $ssdata['totalCount'];
		$t = Date('Ym',time());
		$data = $this->getssdata(1,$totalCount)['data'];
		$db = db('history'.$t);
		foreach ($data as $v) {
			$addData = array(
				'companyname'=>$v['cname'],
				'scssname'=>$v['name'],
				'scsssn'=>$v['sn'],
				'status'=>$v['status'],
				'wgsn'=>$v['wsn'],
				'wgname'=>$v['wname'],
				'logtime'=>$v['updateTime'],
				'scssval'=>$v['val'],
				'zlss1'=>$v['zlss1'],
				'zlss1t'=>$v['zlss1t'],
				'zlss2'=>$v['zlss2'],
				'zlss2t'=>$v['zlss2t'],
				'zlss3'=>$v['zlss3'],
				'zlss3t'=>$v['zlss3t'],
				'zlss4'=>$v['zlss4'],
				'zlss4t'=>$v['zlss4t'],
				'zlss5'=>'',
				'zlss5t'=>'',
				'zlss6'=>'',
				'zlss6t'=>'',
				'zlss7'=>'',
				'zlss7t'=>'',
				'bj'=>0,
				'remark'=>$v['remark']
			);
			$db->add($addData);
		}
		$this->json('');
	}
	/**
	 * 时均值
	 * 每隔一小时，把当前月历史数据表中的数据，根据当前时间整点前一小时，保存到当前月的时均值表中
	 * 按月建表
	 */
	public function savehourdata(){
		$t = time();
		$ym = Date('Ym',$t);
		$h = Date('Y-m-d h',$t);//当前时间整点
		$hl = Date('Y-m-d h',$t-3600);//前一小时整点
		$data = db('history'.$ym)->where('create_time>=? and create_time<?',array($hl,$h))->fetchAll();
		$hourdb = db('everyhour'.$ym);
		foreach ($data as $key => $value) {
			unset($data[$key]['id']);
			unset($data[$key]['create_time']);
			$hourdb->add($data[$key]);
		}
		$this->json($data);
	}
	/**
	 * 日均值
	 * 每天凌晨0点15分，把当前月时均值数据表中的数据，根据当前时间前一天，保存到当前月的日均值表中
	 * 按月建表
	 */
	public function savedaydata(){
		$t = time();
		$ym = Date('Ym',$t);
		$d = Date('Y-m-d',$t)." 00:00:00";//当前时间整日
		$dl = Date('Y-m-d',$t-86400)." 00:00:00";//前一天
		$data = db('everyhour'.$ym)->where('create_time>=? and create_time<?',array($dl,$d))->fetchAll();
		$daydb = db('everyday'.$ym);
		foreach ($data as $key => $value) {
			unset($data[$key]['id']);
			unset($data[$key]['create_time']);
			$daydb->add($data[$key]);
		}
		$this->json($data);
	}
}