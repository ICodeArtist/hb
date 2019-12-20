<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;
require_once 'Connection.php';

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    /**
     * 新建一个类的静态成员，用来保存mysql数据库实例
     */
    public static $db = null;

    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     * 
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {
        // 向当前client_id发送数据 
        Gateway::sendToClient($client_id, "Hello $client_id\r\n");
        // 向所有人发送
        Gateway::sendToAll("$client_id login IP:".$_SERVER['REMOTE_ADDR']."\r\n");

        self::$db =new \Workerman\MySQL\Connection('localhost', '3306', 'root', 'EaHMAbLhrjkhzdJa', 'hbsql');
    }
    
   /**
    * 当客户端发来消息时触发
    * @param int $client_id 连接id
    * @param mixed $message 具体消息
    */
   public static function onMessage($client_id, $message){
        //$data1 = "
        ##0716QN=20190708113607000;ST=80;CN=2011;PW=123456;MN=0010931004HBYDTEST000002;Flag=5;CP=&&DataTime=20190708113607;ea30010101-Rtd=0.99,ea30010101-Flag=N;ea30010102-Rtd=0.00,ea30010102-Flag=N;ea30010103-Rtd=0.00,ea30010103-Flag=N;ea30010104-Rtd=0.99,ea30010104-Flag=N;ea30010105-Rtd=220,ea30010105-Flag=N;ea30010106-Rtd=0,ea30010106-Flag=N;ea30010107-Rtd=6.01,ea30010107-Flag=N;ea30010108-Rtd=3.94,ea30010108-Flag=N;ea30010109-Rtd=0.999,ea30010109-Flag=N;ea30010110-Rtd=220.0,ea30010110-Flag=N;ea30010111-Rtd=220.0,ea30010111-Flag=N;ea30010112-Rtd=219.9,ea30010112-Flag=N;ea30010113-Rtd=220.0,ea30010113-Flag=N;ea30010114-Rtd=0.0,ea30010114-Flag=N;ea30010115-Rtd=0.0,ea30010115-Flag=N;ea30010116-Rtd=0.0,ea30010116-Flag=N&&AA01
        //";
        // file_put_contents("tcpt.txt",$message,FILE_APPEND);
        $r = self::decodePushMsg($message);
        if($r != 'error'){
            Gateway::sendToClient($client_id,$r);
            $data = json_decode($r,true);
            $no = $data['val'][0]['no'];
            $updata = array(
                'val1'          =>  $data['val'][0]['Rtd'],
                'val2'          =>  $data['val'][1]['Rtd'],
                'val3'          =>  $data['val'][2]['Rtd'],
                'val4'          =>  $data['val'][3]['Rtd'],
                'val5'          =>  $data['val'][4]['Rtd'],
                'val6'          =>  $data['val'][5]['Rtd'],
                'val7'          =>  $data['val'][6]['Rtd'],
                'val8'          =>  $data['val'][7]['Rtd'],
                'val9'          =>  $data['val'][8]['Rtd'],
                // 'updateTime'    =>  $data['update']
            );
            $beltlineinfo = self::$db->select('mn,name')->from('beltline')->where("mn='".$data['mn']."' and no='".$no."'")->row();
            if(empty($beltlineinfo)){
                $facilityinfo = self::$db->select('mn,no')->from('facility')->where("mn='".$data['mn']."' and no='".$no."'")->row();
                if(empty($facilityinfo)){
                    print_r($data);
                }else{
                    self::$db->update('facility')->cols($updata)->where("mn='".$data['mn']."' and no='".$no."'")->query();
                }
            }else{
                self::$db->update('beltline')->cols($updata)->where("mn='".$data['mn']."' and no='".$no."'")->query();
            }
            return;
        }else{
            Gateway::sendToClient($client_id,"data errors");
            return;
        };
   }
   /**
    * 当用户断开连接时触发
    * @param int $client_id 连接id
    */
   public static function onClose($client_id)
   {
       // 向所有人发送 
       GateWay::sendToAll("$client_id logout\r\n");
   }
    public static function decodePushMsg($data1){
        $data = [];
        $data2 = explode(';',$data1);
        $data3 = explode('=',$data2[2]);//判断是否是2011(上传实时数据)   1013(现场机时间校准请求)无效
        $data['mn'] = explode('=',$data2[4])[1];
        if($data3[1] == 2011){
            $data4 = explode('&&',$data1)[1];
            $data5 = explode(';',$data4);
            $data6 = [];
            //时间
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
                $data6[$i-1]['no'] = substr($Rtd[0] , 6 , 2); //facility编号
                $data6[$i-1]['yz'] = substr($Rtd[0] , 8 , 2); //01-A相电流 02-B相电流 03-C相电流 04-单相电流 05-总有功功率 06-总无功功率 07-总有功电量 08-总无功电量 09-功率因数
                $data6[$i-1]['Rtd'] = $Rtd[1];                //facility val
                $data6[$i-1]['Flag'] = $Flag[1];              //N-合格 J-无效
            }
            $data['update'] = $update;
            $data['val'] = $data6;
            return json_encode($data);
        }else{
            return "error";
        }
    }
}
