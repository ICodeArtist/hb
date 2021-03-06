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
		->join('as c left join area as a on c.areaid=a.id left join hy as h on c.hyid=h.id')->order('c.id asc')
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
		$q = '1=?';
		$qarr[] = 1;
		if(isset($_GET['companyid']) && $_GET['companyid']>0){
			$q .= ' and companyid=?';
			$qarr[] = $_GET['companyid'];
		}
		$data = db('wg')->where($q,$qarr)->fetchAll();
		$this->json($data);
	}
	//生产线选择
	public function bels(){
		$q = '1=?';
		$qarr[] = 1;
		if(isset($_GET['companyid']) && $_GET['companyid']>0){
			$q .= ' and companyid=?';
			$qarr[] = $_GET['companyid'];
		}
		$data = db('beltline')->where($q,$qarr)->fetchAll();
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
		db('beltline')->where('companyid=?',array($_GET['id']))->delete();
		db('facility')->where('companyid=?',array($_GET['id']))->delete();
		$this->json('');
	}
	public function delWg(){
		db('wg')->where('id=?',array($_GET['id']))->delete();
		db('beltline')->where('wgid=?',array($_GET['id']))->delete();
		db('facility')->where('wgid=?',array($_GET['id']))->delete();
		$this->json('');
	}
	public function delBeltline(){
		db('beltline')->where('id=?',array($_GET['id']))->delete();
		$this->json('');
	}
	public function delFacility(){
		db('facility')->where('id=?',array($_GET['id']))->delete();
		$this->json('');
	}
	//历史数据
	public function logdata(){
		$query = '1=?';
		$queryarr[] = '1';
		if(isset($_GET['companyname']) && $_GET['companyname']){
			$query .= " and companyname like '%".$_GET['companyname']."%'";
		}
		if(isset($_GET['wstatus']) && $_GET['wstatus']>0){
			$query .= " and wstatus=?";
			$queryarr[] = $_GET['wstatus'];
		}
		$t = Date('Ym',time());
		$data = db('history'.$t)->where($query,$queryarr)->limit(($_GET['pageNo']-1)*$_GET['pageSize'],$_GET['pageSize'])
		->dcxfetchAll();
		$data['pageNo'] = (int)$_GET['pageNo'];
		$this->json($data);
	}
	//报表
	public function redata(){
		if(isset($_GET['companyname']) && $_GET['companyname']){
			$query = '1=?';
			$queryarr[] = '1';
			if(isset($_GET['sn']) && $_GET['sn']){
				$query .= ' and sn=?';
				$queryarr[] = $_GET['sn'];
			}
			$query .= " and companyname like '%".$_GET['companyname']."%'";
			if(isset($_GET['createtime']) && $_GET['createtime'][0]){
				$btstrt = strtotime(str_replace('"','',$_GET['createtime'][0]));
				$etstrt = strtotime(str_replace('"','',$_GET['createtime'][1]));
				$bt = Date('Ym',$btstrt);
				$et = Date('Ym',$etstrt);
				if($bt == $et){//选择时间为同一个月
					$t = $bt;
					$query .= " and create_time>=? and create_time<=? ";
					$queryarr[] = Date('Y-m-d H:i:s',$btstrt);
					$queryarr[] = Date('Y-m-d H:i:s',$etstrt);
					$data = db('history'.$t)->where($query,$queryarr)->limit(($_GET['pageNo']-1)*$_GET['pageSize'],$_GET['pageSize'])
					->dcxfetchAll();
				}else{//不同月
					//清空history表
					db('history')->where('1=?',[1])->delete();
					//把范围内的数据存入history中，再做分页取出
					$bt_e = date("Y-m-t", $btstrt).' 23:59:59';//开始时间的月末
					$et_b = date("Y-m-01", $etstrt).' 00:00:00';//结束时间的月初
					//取出
					$btq = $etq = $query;
					$btqarr = $etqarr = $queryarr;

					$btq .= " and create_time>=? and create_time<=? ";
					$btqarr[] = Date('Y-m-d H:i:s',$btstrt);//起始时间
					$btqarr[] = $bt_e;//月末
					
					$etq .= " and create_time>=? and create_time<=? ";
					$etqarr[] = $et_b;//月初
					$etqarr[] = Date('Y-m-d H:i:s',$etstrt);//结束时间
					
					$btdata = db('history'.$bt)->where($btq,$btqarr)->fetchAll();
					foreach ($btdata as $key => $value) {
						unset($btdata[$key]['id']);
						db('history')->add($btdata[$key]);
					}
					$etdata = db('history'.$et)->where($etq,$etqarr)->fetchAll();
					foreach ($etdata as $key => $value) {
						unset($etdata[$key]['id']);
						db('history')->add($etdata[$key]);
					}
					$data = db('history')->where($query,$queryarr)->limit(($_GET['pageNo']-1)*$_GET['pageSize'],$_GET['pageSize'])
					->dcxfetchAll();
				}
			}else{
				$t = Date('Ym',time());
				$data = db('history'.$t)->where($query,$queryarr)->limit(($_GET['pageNo']-1)*$_GET['pageSize'],$_GET['pageSize'])
				->dcxfetchAll();
			}
		}else{
			$query ='1=?';
			$queryarr[] = '0';
			$data = db('history'.$t)->where($query,$queryarr)->limit(($_GET['pageNo']-1)*$_GET['pageSize'],$_GET['pageSize'])
			->dcxfetchAll();
		}
		$data['pageNo'] = (int)$_GET['pageNo'];
		$this->json($data);
	}
	//导出
	public function exportdata(){
		$excel = tool('PHPExcel');
		//基础信息
        $excel->getProperties()->setCreator("phpGrace")
                             ->setLastModifiedBy("phpGrace")
                             ->setTitle("phpGrace demo")
                             ->setSubject("objPHPExcel");
           //设置 sheet 名称
           $excel->getActiveSheet(0)->setTitle('报表');
        //标题
        $excel->setActiveSheetIndex(0)
                    ->setCellValue('A1', '企业名称')
                    ->setCellValue('B1', '生产线名称')
                    ->setCellValue('C1', '生产线编号')
					->setCellValue('D1', '组别')
                    ->setCellValue('E1', '时间')
					->setCellValue('F1', '生产设施')
					->setCellValue('G1', '治理设施1')
					->setCellValue('H1', '治理设施2')
                    ->setCellValue('I1', '治理设施3')
                    ->setCellValue('J1', '治理设施4');
		//数据填充【此数据可以来自数据库】
		$query = '1=?';
		$queryarr[] = '1';
		if(isset($_GET['sn']) && $_GET['sn']){
			$query .= ' and sn=?';
			$queryarr[] = $_GET['sn'];
		}
		$query .= " and companyname like '%".$_GET['companyname']."%'";
		if(isset($_GET['createtime']) && $_GET['createtime'][0]){
			$btstrt = strtotime(str_replace('"','',$_GET['createtime'][0]));
			$etstrt = strtotime(str_replace('"','',$_GET['createtime'][1]));
			$bt = Date('Ym',$btstrt);
			$et = Date('Ym',$etstrt);
			if($bt == $et){//选择时间为同一个月
				$t = $bt;
				$query .= " and create_time>=? and create_time<=? ";
				$queryarr[] = Date('Y-m-d H:i:s',$btstrt);
				$queryarr[] = Date('Y-m-d H:i:s',$etstrt);
				$data = db('history'.$t)->where($query,$queryarr)->fetchAll();
			}else{//不同月
				$data = db('history')->where($query,$queryarr)->fetchAll();
			}
		}else{
			$t = Date('Ym',time());
			$data = db('history'.$t)->where($query,$queryarr)->fetchAll();
		}
        $i = 2;
        foreach($data as $rows){
            $excel->setActiveSheetIndex(0)
                    ->setCellValue('A'.$i, $rows['companyname'])
                    ->setCellValue('B'.$i, $rows['name'])
					->setCellValue('C'.$i, $rows['sn'])
					->setCellValue('D'.$i, $rows['name'])
					->setCellValue('E'.$i, $rows['create_time'])
					->setCellValue('F'.$i, $rows['val1'])
                    ->setCellValue('G'.$i, $rows['facility1val1'])
					->setCellValue('H'.$i, $rows['facility2val1'])
					->setCellValue('I'.$i, $rows['facility3val1'])
					->setCellValue('J'.$i, $rows['facility4val1']);
            $i++;
        }
		//保存为 xls
		$objWriter =PHPExcel_IOFactory:: createWriter($excel, 'Excel2007');
		$filename = 't.xls';
		$objWriter->save($filename);
		$this->json('http://125.124.87.82:8889/'.$filename);
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
		if(isset($_GET['wstatus']) && $_GET['wstatus']>0){
			$query .= " and b.wstatus=?";
			$queryarr[] = $_GET['wstatus'];
		}
		if(isset($_GET['status']) && $_GET['status']>0){
			$query .= " and b.status=?";
			$queryarr[] = $_GET['status'];
		}
		$data = db('beltline')->join('as b left join company as c on b.companyid=c.id 
		left join wg as w on w.id=b.wgid 
		left join area as a on a.id=c.areaid 
		left join hy as h on h.id=c.hyid')
		->where($query,$queryarr)->limit(($pageNo-1)*$pageSize,$pageSize)
		->dcxfetchAll('b.*,
		c.name as cname,c.remark,
		h.name as hname,
		w.name as wname,w.sn as wsn,
		a.name as aname');
		$db = db('facility');
		foreach ($data['data'] as $key => $value) {
			$f = $db->where('companyid=? and wgid=?',array($value['companyid'],$value['wgid']))->fetchAll();
			for ($i=1; $i <=count($f); $i++) {
				for ($j=1; $j <=9; $j++) {
					$data['data'][$key]['facility'.$i.'val'.$j] = $f[$i-1]['val'.$j];
				}
				$data['data'][$key]['facility'.$i.'t'] = $f[$i-1]['updateTime'];
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

	public function beltlinelist(){
		$query = '1=?';
		$queryarr[] = '1';
		if(isset($_GET['cname']) && $_GET['cname']){
			$query .= " and c.name like '%".$_GET['cname']."%'";
		}
		$data = db('beltline')->join('as s left join company as c on c.id=s.companyid 
		left join wg as w on w.id=s.wgid')
		->where($query,$queryarr)->limit(($_GET['pageNo']-1)*$_GET['pageSize'],$_GET['pageSize'])
		->dcxfetchAll('s.*,w.sn as wsn,c.name as cname');
		$data['pageNo'] = (int)$_GET['pageNo'];
		$this->json($data);
	}

	public function addBeltline(){
		$beltlineid = $_POST['id'];
		unset($_POST['id']);
		$_POST['companyid'] = $_POST['cpwg'][0];
		$_POST['wgid'] = $_POST['cpwg'][1];
		unset($_POST['cpwg']);
		if($beltlineid>0){
			$beltlineida = db('beltline')->where('id=?',array($beltlineid))->update($_POST);
		}else{
			$beltlineida = db('beltline')->add($_POST);
		}
		$beltlineida?$this->json(''):$this->json('','-1','失败');
	}

	public function facilitylist(){
		$query = '1=?';
		$queryarr[] = '1';
		if(isset($_GET['cname']) && $_GET['cname']){
			$query .= " and c.name like '%".$_GET['cname']."%'";
		}
		$data = db('facility')->join('as z left join company as c on c.id=z.companyid 
		left join wg as w on w.id=z.wgid')
		->where($query,$queryarr)->limit(($_GET['pageNo']-1)*$_GET['pageSize'],$_GET['pageSize'])
		->dcxfetchAll('z.*,w.sn as wsn,c.name as cname');
		$data['pageNo'] = (int)$_GET['pageNo'];
		$this->json($data);
	}

	public function addFacility(){
		$zlssid = $_POST['id'];
		unset($_POST['id']);
		$_POST['companyid'] = $_POST['cpwg'][0];
		$_POST['wgid'] = $_POST['cpwg'][1];
		unset($_POST['cpwg']);
		if($zlssid>0){
			$zlssida = db('facility')->where('id=?',array($zlssid))->update($_POST);
		}else{
			$data = db('facility')->where('companyid=? and wgid=? and no=?',array($_POST['companyid'],$_POST['wgid'],$_POST['no']))->fetch();
			if(!empty($data))
				$this->json('','-1','不能重复');
			$zlssida = db('facility')->add($_POST);
		}
		$zlssida?$this->json(''):$this->json('','-1','失败');
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
				'name'=>$v['name'],
				'sn'=>$v['sn'],
				'status'=>$v['status'],
				'wstatus'=>$v['wstatus'],
				'wsn'=>$v['wsn'],
				'wname'=>$v['wname'],
				'logtime'=>$v['updateTime'],
				'val1'=>$v['val1'],
				'val2'=>$v['val2'],
				'val3'=>$v['val3'],
				'val4'=>$v['val4'],
				'val5'=>$v['val5'],
				'val6'=>$v['val6'],
				'val7'=>$v['val7'],
				'val8'=>$v['val8'],
				'val9'=>$v['val9'],
				'facility1val1'=>$v['facility1val1'],
				'facility1val2'=>$v['facility1val2'],
				'facility1val3'=>$v['facility1val3'],
				'facility1val4'=>$v['facility1val4'],
				'facility1val5'=>$v['facility1val5'],
				'facility1val6'=>$v['facility1val6'],
				'facility1val7'=>$v['facility1val7'],
				'facility1val8'=>$v['facility1val8'],
				'facility1val9'=>$v['facility1val9'],
				'facility1t'=>$v['facility1t'],
				'facility2val1'=>$v['facility2val1'],
				'facility2val2'=>$v['facility2val2'],
				'facility2val3'=>$v['facility2val3'],
				'facility2val4'=>$v['facility2val4'],
				'facility2val5'=>$v['facility2val5'],
				'facility2val6'=>$v['facility2val6'],
				'facility2val7'=>$v['facility2val7'],
				'facility2val8'=>$v['facility2val8'],
				'facility2val9'=>$v['facility2val9'],
				'facility2t'=>$v['facility2t'],
				'facility3val1'=>$v['facility3val1'],
				'facility3val2'=>$v['facility3val2'],
				'facility3val3'=>$v['facility3val3'],
				'facility3val4'=>$v['facility3val4'],
				'facility3val5'=>$v['facility3val5'],
				'facility3val6'=>$v['facility3val6'],
				'facility3val7'=>$v['facility3val7'],
				'facility3val8'=>$v['facility3val8'],
				'facility3val9'=>$v['facility3val9'],
				'facility3t'=>$v['facility3t'],
				'facility4val1'=>$v['facility4val1'],
				'facility4val2'=>$v['facility4val2'],
				'facility4val3'=>$v['facility4val3'],
				'facility4val4'=>$v['facility4val4'],
				'facility4val5'=>$v['facility4val5'],
				'facility4val6'=>$v['facility4val6'],
				'facility4val7'=>$v['facility4val7'],
				'facility4val8'=>$v['facility4val8'],
				'facility4val9'=>$v['facility4val9'],
				'facility4t'=>$v['facility4t'],
				'facility5val1'=>$v['facility5val1'],
				'facility5val2'=>$v['facility5val2'],
				'facility5val3'=>$v['facility5val3'],
				'facility5val4'=>$v['facility5val4'],
				'facility5val5'=>$v['facility5val5'],
				'facility5val6'=>$v['facility5val6'],
				'facility5val7'=>$v['facility5val7'],
				'facility5val8'=>$v['facility5val8'],
				'facility5val9'=>$v['facility5val9'],
				'facility5t'=>$v['facility5t'],
				'facility6val1'=>$v['facility6val1'],
				'facility6val2'=>$v['facility6val2'],
				'facility6val3'=>$v['facility6val3'],
				'facility6val4'=>$v['facility6val4'],
				'facility6val5'=>$v['facility6val5'],
				'facility6val6'=>$v['facility6val6'],
				'facility6val7'=>$v['facility6val7'],
				'facility6val8'=>$v['facility6val8'],
				'facility6val9'=>$v['facility6val9'],
				'facility6t'=>$v['facility6t'],
				'remark'=>$v['remark']
			);
			$db->add($addData);
		}
		$this->json($t);
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
	//企业地址列表
	public function areacompany(){
		if(isset($_GET['companyname']) && $_GET['companyname']){

		}
		$data = db('area')->where('1=?',[1])->fetchAll();
		foreach ($data as $key => $value) {
			$cp = db('company')->where('areaid=?',[$value['id']])->fetchAll();
			// if(empty($cp)){
			// 	unset($data[$key]);
			// }else{
				$data[$key]['company'] = $cp;
			// }
		}
		$data = array_values($data);
		$this->json($data);
	}
	//workplace
	public function workplace(){
		$companynum = db('company')->where('1=?',[1])->count();
		$companyonlienum = db('company')->where('status=?',[1])->count();
		$beltlinenum = db('beltline')->where('1=?',[1])->count();
		$beltlineonlienum = db('beltline')->where('status=?',[1])->count();
		$data['companynum'] = $companynum;
		$data['beltlinenum'] = $beltlinenum;
		$data['companyonlienum'] = $companyonlienum;
		$data['beltlineonlienum'] = $beltlineonlienum;
		$data['sourceData'] = [];
		$area = db('area')->where('1=?',[1])->fetchAll();
		foreach ($area as $key => $value) {
			$data['sourceData'][$key]['item'] = $value['name'];
			$data['sourceData'][$key]['count'] = (int)db('company')->where('areaid=?',[$value['id']])->count();
		}
		$this->json($data);
	}
}