<?php

namespace app\pay\controller;

use app\api\controller\Controller;
use app\pay\model\Cloudsafepay as ModelCloudsafepay;
use app\pay\model\Wowpay as ModelWowpay;
use think\Log;

/**
 * Cloudsafepay
 */
class Cloudsafepay extends Controller
{

    /**
     * 代收回调
     */
    public function paynotify()
    {
        $data = $_POST;
        Log::mylog('支付回调_data', $data, 'cloudsafepayhd');
        (new ModelCloudsafepay())->paynotify($data);
        exit('success');
    }

    /**
     * 代付回调
     */
    public function paydainotify()
    {
        $data = $_POST;
        Log::mylog('提现回调_data', $data, 'cloudsafepaydfhd');
        (new ModelCloudsafepay())->paydainotify($data);
        exit('success');
    } 
}
