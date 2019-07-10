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
        // $this->removeCache('test', '12');
        $this->cache('test', 12, '__getData');
        p($this->test);
    }
    public function __getData(){
        echo '无缓存，进行查询...<br />';
        // return 'dd';
    }
}