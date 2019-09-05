<?php
/*
 * @Author: daichengxiang 
 * @Date: 2019-04-24 09:47:47 
 * @Last Modified by: daichengxiang
 * @Last Modified time: 2019-07-09 15:47:27
 */
require_once 'Gateway.php';
use GatewayClient\Gateway;
class receiveController extends grace{
    public function testTCP(){
        $r = Gateway::sendToAll("testTCP\r\n");
        $this->json($r);
    }
    public function testRedis(){
        $this->cache('test', 12, 'dds');
        p($this->test);
    }
    public function __getData(){
        echo '无缓存，进行查询...<br />';
        return 'dds';
    }
    public function goredis(){
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $redis->auth('123456');
        $list = $redis->lpush("tutorial-list", "Redis");
        echo $list.'-';
        $hash1 = $redis->hSet('user', 'name', '2231');
        echo $hash1.'-';
    }
    public function predis(){
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $redis->auth('123456');
        echo $redis->rPop('tutorial-list');
    }
    public function decodePushMsg(){
        // $data1 = "##0087QN=20190708113403000;ST=80;CN=1013;PW=123456;MN=0010931004HBYDTEST000002;Flag=5;CP=&&&&EF41";
        $data1 = "##0716QN=20190708113607000;ST=80;CN=2011;PW=123456;MN=0010931004HBYDTEST000002;Flag=5;CP=&&DataTime=20190708113607;ea30010101-Rtd=0.99,ea30010101-Flag=N;ea30010102-Rtd=0.00,ea30010102-Flag=N;ea30010103-Rtd=0.00,ea30010103-Flag=N;ea30010104-Rtd=0.99,ea30010104-Flag=N;ea30010105-Rtd=220,ea30010105-Flag=N;ea30010106-Rtd=0,ea30010106-Flag=N;ea30010107-Rtd=6.01,ea30010107-Flag=N;ea30010108-Rtd=3.94,ea30010108-Flag=N;ea30010109-Rtd=0.999,ea30010109-Flag=N;ea30010110-Rtd=220.0,ea30010110-Flag=N;ea30010111-Rtd=220.0,ea30010111-Flag=N;ea30010112-Rtd=219.9,ea30010112-Flag=N;ea30010113-Rtd=220.0,ea30010113-Flag=N;ea30010114-Rtd=0.0,ea30010114-Flag=N;ea30010115-Rtd=0.0,ea30010115-Flag=N;ea30010116-Rtd=0.0,ea30010116-Flag=N&&AA01";
        //$data1 = "##0670QN=20190708113607000;ST=80;CN=2011;PW=123456;MN=0010931004HBYDTEST000002;Flag=5;CP=&&DataTime=20190708113607;ea30010201-Rtd=0,ea30010201-Flag=J;ea30010202-Rtd=0,ea30010202-Flag=J;ea30010203-Rtd=0,ea30010203-Flag=J;ea30010204-Rtd=0,ea30010204-Flag=J;ea30010205-Rtd=0,ea30010205-Flag=J;ea30010206-Rtd=0,ea30010206-Flag=J;ea30010207-Rtd=0,ea30010207-Flag=J;ea30010208-Rtd=0,ea30010208-Flag=J;ea30010209-Rtd=0,ea30010209-Flag=J;ea30010210-Rtd=0,ea30010210-Flag=J;ea30010211-Rtd=0,ea30010211-Flag=J;ea30010212-Rtd=0,ea30010212-Flag=J;ea30010213-Rtd=0,ea30010213-Flag=J;ea30010214-Rtd=0,ea30010214-Flag=J;ea30010215-Rtd=0,ea30010215-Flag=J;ea30010216-Rtd=0,ea30010216-Flag=J&&B981";
        $data2 = explode(';',$data1);
        $data3 = explode('=',$data2[2]);//判断是否是2011(上传实时数据)   1013(现场机时间校准请求)无效
        if($data3[1] == 2011){
            $data4 = explode('&&',$data1)[1];
            $data5 = explode(';',$data4);
            $data6 = [];
            //时间5
            $DateTime = explode('=',$data5[0])[1];
            // print_r($DateTime);echo "<br />";
            $year = substr($DateTime , 0 , 4);
            // print_r($year);echo "<br />";
            $month = substr($DateTime , 4 , 2);
            // print_r($month);echo "<br />";
            $day = substr($DateTime , 6 , 2);
            // print_r($day);echo "<br />";
            $h = substr($DateTime , 8 , 2);
            // print_r($h);echo "<br />";
            $m = substr($DateTime , 10 , 2);
            // print_r($m);echo "<br />";
            $s = substr($DateTime , 12 , 2);
            // print_r($s);echo "<br />";
            $update = $year.'-'.$month.'-'.$day.' '.$h.':'.$m.':'.$s;
            // print_r($update);
            for ($i=1; $i < count($data5); $i++) { 
                $data5_1 = explode(',', $data5[$i]);
                $Rtd = explode('=', $data5_1[0]);
                $Flag = explode('=', $data5_1[1]);
                $data6[$i-1]['no'] = substr($Rtd[0] , 6 , 2); //zlss编号
                $data6[$i-1]['yz'] = substr($Rtd[0] , 8 , 2); //01-A相电流 02-B相电流 03-C相电流 04-单相电流 05-总有功功率 06-总无功功率 07-总有功电量 08-总无功电量 09-功率因数
                $data6[$i-1]['Rtd'] = $Rtd[1];                //zlss val
                $data6[$i-1]['Flag'] = $Flag[1];              //N-合格 J-无效
            }
            print_r($data6);
        }else{
            print_r($data3[1]);
        }
        exit;
    }
}