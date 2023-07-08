<?php

namespace app\pay\controller;

use app\api\controller\Controller;
use app\pay\model\Speed as ModelSpeed;
use think\Log;

/**
 * Wowpay
 */
class Speed extends Controller
{

    /**
     * 代收回调
     */
    public function paynotify()
    {
        $data = file_get_contents("php://input");
        Log::mylog('支付回调_data', $data, 'speedhd');
        (new ModelSpeed())->paynotify(json_decode($data,true));
        exit('SUCCESS');
    }

    /**
     * 代付回调
     */
    public function paydainotify()
    {
        $data = file_get_contents("php://input");
        Log::mylog('提现回调_data', $data, 'speeddfhd');
        (new ModelSpeed())->paydainotify(json_decode($data,true));
        exit('SUCCESS');
    }

    public function paysuccess(){
        echo "pay success";exit;
    }
}
