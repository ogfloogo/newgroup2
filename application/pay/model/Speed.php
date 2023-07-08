<?php

namespace app\pay\model;

use fast\Http;
use function EasyWeChat\Kernel\Support\get_client_ip;

use app\api\model\Report;
use app\api\model\Usercash;
use app\api\model\Userrecharge;
use app\api\model\Usertotal;
use think\Cache;
use think\Model;
use think\Db;
use think\Log;
use think\Exception;


class Speed extends Model
{
    //代付提单url(提现)
    public $dai_url = 'https://api.speedlyglobal.com/api/settle/settlement';
    //代收提交url(充值)
    public $pay_url = 'https://api.speedlyglobal.com/api/pay/payment';
    //代付回调(提现)
    public $notify_dai = 'https://lequ.maygroup.shop/pay/speed/paydainotify';
    //代收回调(充值)
    public $notify_pay = 'https://lequ.maygroup.shop/pay/speed/paynotify';
    //支付成功跳转地址    
    public $callback_url = 'https://lequ.maygroup.shop/pay/speed/paysuccess';

    public $key = 'DPZIE8SBS7MUR4SKOMBK6XEPMRRECPTH';
    public $key2 = 'MFU7AKERPWGQ0KPUOKFBVQ9UWW8N2V6W';
    public $appid = 'sp1675786069406257152m';
    public function pay($order_id, $price, $userinfo, $channel_info)
    {
        $param = [
            'merchant_no' => $channel_info['merchantid'],
            'data' => [
                'country' => 'MY',
                'currency' => 'MYR',
                'payment_method_id' => 'BANK_CARD',
                'payment_method_flow' => 'REDIRECT',
                'order_id' => $order_id,
                'amount' => $price,
                'notification_url' => $this->notify_pay,
                'success_redirect_url' => $this->callback_url,
                'timestamp' => $this->getMillisecond(),
            ]
        ];
        $sign = $this->generateSign($param['data'], $this->key);
        $param['data']['signature'] = $sign;
        $param['data']['payer'] = [
            'name' => 'test',
            'document' => '1000001',
            'email' => 'testtest@gmail.com',
            'phone' => '987654321'
        ];
        Log::mylog("提交参数", $param, "speed");
        $header[0] = "Content-Type:application/json;charset=UTF-8";
        $header[1] = "ApiVersion:1.0";
        $header[2] = "AppId:".$this->appid;
        $header[3] = "Noncestr:" . mt_rand(1, 99999999);
        $header[4] = "Timestamp:" . $this->getMillisecond();
        $return_json = $this->http_post($this->pay_url,$header,json_encode($param));
        Log::mylog("返回参数", $return_json, "speed");
        $return_array = json_decode($return_json, true);
        if ($return_array['state'] == 'ok') {
            $return_array = [
                'code' => 1,
                'payurl' => !empty(($return_array['data']['redirect_url'])) ? ($return_array['data']['redirect_url']) : '',
            ];
        } else {
            $return_array = [
                'code' => 0,
                'msg' => $return_array['msg'],
            ];
        }
        return $return_array;
    }

    /**
     * 代收回调
     */
    public function paynotify($params)
    {
        if ($params['status'] == 'SUCCESS') {
            $sign = $params['signature'];
            unset($params['signature']);
            $check = $this->generateSign($params, $this->key);
            if ($sign == $check) {
                $order_id = $params['order_id']; //商户订单号
                $order_num = $params['payment_id']; //平台订单号
                $amount = $params['amount']; //支付金额
                (new Paycommon())->paynotify($order_id, $order_num, $amount, 'speedhd');
                echo 'SUCCESS';exit;
            }else{
                Log::mylog('验签失败', $sign.'---'.$check, 'speedhd');
                return false;
            }
        } else {
            //更新订单信息
            $upd = [
                'status' => 2,
                'order_id' => $params['order_id'],
                'updatetime' => time(),
            ];
            (new Userrecharge())->where('order_id', $params['order_id'])->where('status', 0)->update($upd);
            Log::mylog('支付回调失败！', $params, 'speedyhd');
        }
    }

    /**
     *提现 
     */
    public function withdraw($data, $channel)
    {
        if($data['bankname'] == 'Maybank'){
            $bank_code = 'MAYB';
        }elseif($data['bankname'] == 'AffinBank'){
            $bank_code = 'AFIN';
        }elseif($data['bankname'] == 'Alliancebank'){
            $bank_code = 'ALLI';
        }elseif($data['bankname'] == 'Bankislam'){
            $bank_code = 'MYIS';
        }elseif($data['bankname'] == 'Hongleongbank'){
            $bank_code = 'HOLB';
        }elseif($data['bankname'] == 'Cimb'){
            $bank_code = 'CIMB';
        }elseif($data['bankname'] == 'Ambank'){
            $bank_code = 'MYAB';
        }elseif($data['bankname'] == 'Publicbank'){
            $bank_code = 'PBBB';
        }elseif($data['bankname'] == 'Rhbbank'){
            $bank_code = 'RHBB';
        }else{
            return '{"state":"fail","errorMsg":"不支持的银行"}';
        }

        $params = array(
            'merchant_no' => $channel['merchantid'],
            'data' => [
                'country' => 'MY',
                'currency' => 'MYR',
                'order_id' => $data['order_id'],
                'amount' => $data['trueprice'],
                'notification_url' => $this->notify_dai,
                'timestamp' => $this->getMillisecond(),
            ],
        );
        $sign = $this->generateSign($params['data'], $this->key2);
        $params['data']['payee'] = [
            'name' => $data['username'], //收款姓名
            'account' => $data['bankcard'], //收款账号
            'account_type' => 'BANK',
            'phone' => $data['phone'],
            'email' => 'testtest@gmail.com',
            'bank_code' => $bank_code,
        ];
        $params['data']['signature'] = $sign;
        Log::mylog('提现提交参数', $params, 'speeddf');
        $header[0] = "Content-Type:application/json;charset=UTF-8";
        $header[1] = "ApiVersion:1.0";
        $header[2] = "AppId:".$this->appid;
        $header[3] = "Noncestr:" . mt_rand(1, 99999999);
        $header[4] = "Timestamp:" . $this->getMillisecond();
        $return_json = $this->http_post($this->dai_url,$header,json_encode($params));
        Log::mylog("返回参数", $return_json, "speeddf");
        return $return_json;
    }

    /**
     * 提现回调
     */
    public function paydainotify($params)
    {
        $sign = $params['signature'];
        unset($params['signature']);
        $check = $this->generateSign($params, $this->key2);
        if ($sign != $check) {
            Log::mylog('验签失败', $params, 'speeddfhd');
            return false;
        }
        $usercash = new Usercash();
        if ($params['status'] != 'SUCCESS') {
            try {
                $r = $usercash->where('order_id', $params['order_id'])->find()->toArray();;
                if ($r['status'] == 5) {
                    return false;
                }
                $upd = [
                    'status'  => 4, //新增状态 '代付失败'
                    'updatetime'  => time(),
                ];
                $res = $usercash->where('id', $r['id'])->update($upd);
                if (!$res) {
                    return false;
                }
                Log::mylog('代付失败,订单号:' . $params['order_id'], 'speeddfhd');
            } catch (Exception $e) {
                Log::mylog('代付失败,订单号:' . $params['order_id'], $e, 'speeddfhd');
            }
        } else {
            try {
                $r = $usercash->where('order_id', $params['order_id'])->find()->toArray();
                $upd = [
//                    'order_no'  => $params['tradeNo'],
                    'updatetime'  => time(),
                    'status' => 3, //新增状态 '代付成功'
                    'paytime' => time(),
                ];
                $res = $usercash->where('status','lt',3)->where('id', $r['id'])->update($upd);
                if (!$res) {
                    return false;
                }
                //统计当日提现金额
                $report = new Report();
                $report->where('date', date("Y-m-d", time()))->setInc('cash', $r['price']);
                //用户提现金额
                (new Usertotal())->where('user_id', $r['user_id'])->setInc('total_withdrawals', $r['price']);
                Log::mylog('提现成功', $params, 'speeddfhd');
                echo 'SUCCESS';exit;
            } catch (Exception $e) {
                Log::mylog('代付失败,订单号:' . $params['order_id'], $e, 'speeddfhd');
            }
        }
    }

    /**
     * 生成签名   sign = Md5(key1=vaIue1&key2=vaIue2…商户密钥);
     *  @$params 请求参数
     *  @$secretkey   密钥
     */
    public function generateSign(array $params, $key)
    {
        ksort($params);
        $params_str = '';
        foreach ($params as $k => $v) {
                $params_str = $params_str . $k . '=' . $v . '&';
        }
        $params_str = $params_str . 'key=' . $key;
        Log::mylog('验签串', $params_str, 'speed');
        $sign = strtoupper(md5($params_str));
        Log::mylog('md5', $sign, 'speed');
        return $sign;
    }

    function getMillisecond(){
        $sysTimeZone = date_default_timezone_get(); //先取出系统所在服务器的时区
        date_default_timezone_set('UTC'); //设置时区为UTC时区
        list($s1, $s2) = explode(' ', microtime());
        $timestamp = (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
        date_default_timezone_set($sysTimeZone); //设置时区为服务器时区
        return $timestamp;
    }

    function post($url, $jsonData, $header = []){
        $ch = curl_init();
        if (substr($url, 0, 5) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        $response = curl_exec($ch);
        if ($error = curl_error($ch)) {
            die($error);
        }
        curl_close($ch);
        //var_dump($response);
        return $response;
    }

    function http_post($sUrl, $aHeader, $aData)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $sUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($ch, CURLOPT_POSTFIELDS, $aData); // Post提交的数据包
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($ch, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        //curl_setopt($ch, CURLOPT_HEADER, 1); //取得返回头信息

        $sResult = curl_exec($ch);
        if ($sError = curl_error($ch)) {
            die($sError);
        }
        curl_close($ch);
        return $sResult;
    }
}
