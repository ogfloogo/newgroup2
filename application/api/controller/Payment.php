<?php

namespace app\api\controller;

use app\admin\model\Account;
use app\admin\model\Internetbank;
use app\api\model\Goods;
use app\api\model\Order as ModelOrder;
use app\api\model\Payment as ModelPayment;
use app\api\model\Rechargechannel;
use app\api\model\User;
use app\api\model\Usercash;
use app\api\model\Userteam;
use think\cache\driver\Redis;
use think\helper\Time;
use think\Log;
use app\common\library\Sms;
use think\Config;

/**
 * 代收
 */
class Payment extends Controller
{

    /**
     *用户充值
     *
     * @ApiMethod (POST)
     * @param string $amount 充值金额
     * @param string $channel_id 渠道ID
     */
    public function topup()
    {
        $this->verifyUser();
        $post = $this->request->post();
        $price = $this->request->post('price');
        $channel_id = $this->request->post('channel_id');
        if (!$price || !$channel_id) {
            $this->error(__('parameter error'));
        }
        $channel_info = (new Rechargechannel())->where("id",$channel_id)->find();
        if(!$channel_info){
            $this->error(__('The recharge channel does not exist'));
        }
        //Minimum recharge amount
        if($price < $channel_info['minprice']){
            $this->error(__('Minimum recharge amount').$channel_info['minprice']);
        }
        //Maximum recharge amount
        if($price > $channel_info['maxprice']){
            $this->error(__('Maximum recharge amount').$channel_info['maxprice']);
        }
        $return = (new ModelPayment())->topup($post,$this->userInfo,$channel_info);
        if(!$return){
            $this->error(__('payment failure'));
        }
        $this->success(__('The request is successful'), $return);
    }

     /**
     *提现参数
     *
     * @ApiMethod (POST)
     * @param string $amount 提现金额
     */
    public function setting()
    {
        $this->verifyUser();
        //提现手续费
        $list["withdraw_fee"] = Config::get("site.withdraw_fee");
        //最低提现金额
        $list["min_withdraw"] = Config::get("site.min_withdraw");
        //每日提现次数
        $list["daily_withdraw_number"] = Config::get("site.daily_withdraw_number");
        //可提现余额
        $list["balance"] = ($this->userInfo)['money'];
        //审核中金额
        $list["audit_money"] = (new Usercash())->where('status',0)->sum('price');
        $this->success(__('The request is successful'), $list);
    }

    /**
     * 充值记录
     */
    public function paymentlog(){
        $this->verifyUser();
        $post = $this->request->post();
        $page = $this->request->post('page');
        if (!$page) {
            $this->error(__('parameter error'));
        }
        $list = (new ModelPayment())->paymentlog($post,$this->uid);
        $this->success(__('The request is successful'), $list);
    }

    public function selftopup()
    {
        $this->verifyUser();
        $userinfo = $this->userInfo;
        $post = $this->request->post();
        $goods_id = $this->request->post('goods_id');
        $post['user_id'] = $this->uid;

        if (!$goods_id) {
            $this->error(__('parameter error'));
        }
        $goods_info = (new Goods())->detail($goods_id);
        if(!$goods_info){
            $this->error(__('The goods does not exist'));
        }
        if($goods_info['category_id'] == 3){
            $is_addorder = (new ModelOrder())->where(['user_id'=>$this->uid,'order_type'=>1,'pay_status'=>1])->find();
            if(!empty($is_addorder)){
                $this->error(__('only chance'));
            }
            $price = $goods_info['price'];
        }elseif($goods_info['category_id'] == 2){
            $is_addorder = (new ModelOrder())->where(['user_id'=>$this->uid,'order_type'=>2,'pay_status'=>1])->find();
            if(!empty($is_addorder)){
                $this->error(__('only chance'));
            }
            $price = $goods_info['prepaid_amount'];
        }else{
            $price = $goods_info['price'];
        }
        $post['price'] = $price;
        $channel_info = (new Account())->where(["status"=>1])->find();
        if(!$channel_info){
            $this->error(__('The account does not exist'));
        }
        $return = (new ModelPayment())->selftopup($post,$userinfo,$channel_info,$goods_info);
        if(!$return){
            $this->error(__('payment failure'));
        }
        if($return['code'] == 0){
            $this->error($return['msg']);
        }
        $info = (new Account())->where(['id'=>$channel_info['id']])->find();
        $bank_info = (new Internetbank())->where(['id'=>$info['bank_id']])->find();
        $returninfo['order_id'] = $return['order_id'];
        $returnary['bankname'] = $bank_info['name'];
//        $returnary['bankimg'] = format_image($bank_info['image']);
        $returnary['account'] = $info['bank_card'];
        $returnary['amount'] = $price;
        $returninfo['display_key'] = $returnary;
        $this->success(__('The request is successful'), $returninfo);
    }
}
