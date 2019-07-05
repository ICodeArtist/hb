<?php
/*
 * @Author: daichengxiang 
 * @Date: 2019-04-24 09:47:47 
 * @Last Modified by: daichengxiang
 * @Last Modified time: 2019-06-20 17:02:50
 */
require_once 'Gateway.php';
use GatewayClient\Gateway;
class receiveController extends grace{
    public function testTCP(){
        $r = Gateway::sendToAll("testTCP\r\n");
        $this->json($r);
    }
}