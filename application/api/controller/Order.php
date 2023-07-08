<?php

namespace app\api\controller;

use app\api\model\Goods;
use app\api\model\Goodscategory;
use app\api\model\Level;
use app\api\model\Order as ModelOrder;
use app\api\model\Orderoften;
use app\api\model\Usermoneylog;
use app\api\model\Userrecharge;
use app\common\model\User;
use think\cache\driver\Redis;
use fast\Random;
use think\Config;
use think\helper\Time;
use think\Log;

/**
 * 下单
 */
class Order extends Controller
{

    /**
     * 团购下单
     *
     * @ApiMethod (POST)
     * @param string $type 1=开团订单,2=一键成团订单
     * @param string $good_id 商品ID
     */
    public function addorder()
    {
        $this->verifyUser();
        $time = Time::today();
        $userinfo = $this->userInfo;
        //下单时间限制
        $redis = new Redis();
        //是否在体验时间内
        // $redis->handler()->select(6);
        // $end_time = $redis->handler()->zScore("newgroup:sendlist",$this->uid);//到期时间
        // if($end_time){
        //     if($end_time < time()){
        //         $this->error(__('Experience has expired'));
        //     }
        // }
        $post = $this->request->post();
        $good_id = $this->request->post("good_id"); //商品ID
        if (!$good_id) {
            $this->error(__('parameter error'));
        }
        //判断余额
        $good_info = (new Goods())->detail($good_id);
        if ($good_info['price'] > $userinfo['money']) {
            $data['code'] = 10;
            $this->success(__('Your balance is not enough'), $data);
        }
        //团购次数
        $order_num = (new ModelOrder())->where(['order_type'=>0,'user_id'=>$userinfo['id']])->where('createtime', 'between', [$time[0], $time[1]])->count();

        //团购次数
        $daily_buy_num = Config::get('site.daily_buy_num');
        if ($order_num >= $daily_buy_num) {
            $this->error(__('Reached the max group-buying times today'));
        }
        $addorder = (new ModelOrder())->addorder($post, $userinfo, $good_info);
        if (!$addorder) {
            $this->error(__('order failed'));
        }
        $this->success(__('order successfully'), $addorder);
    }

    /**
     * 新人福利，秒杀下单
     */
    public function addorders(){
        $this->verifyUser();
        $userinfo = $this->userInfo;
        $post = $this->request->post();
        $good_id = $this->request->post("good_id"); //商品ID
        if (!$good_id) {
            $this->error(__('parameter error'));
        }
        $good_info = (new Goods())->detail($good_id);
        if($good_info['category_id'] == 1){
            $this->error(__('parameter error'));
        }
        if($good_info['category_id'] == 2){
            $is_addorder = (new ModelOrder())->where(['user_id'=>$this->uid,'order_type'=>1])->find();
            if(!empty($is_addorder)){
                $this->error(__('only chance'));
            }
            $price = $good_info['prepaid_amount'];
        }else{
            $is_addorder = (new ModelOrder())->where(['user_id'=>$this->uid,'order_type'=>2])->find();
            if(!empty($is_addorder)){
                $this->error(__('only chance'));
            }
            $price = $good_info['price'];
        }
        if ($price > $userinfo['money']) {
            $data['code'] = 10;
            $this->success(__('Your balance is not enough'), $data);
        }
        $addorder = (new ModelOrder())->addorders($post, $userinfo, $good_info,$price);
        if (!$addorder) {
            $this->error(__('order failed'));
        }
        $this->success(__('order successfully'), $addorder);
    }

    /**
     * 我的团购
     *
     * @ApiMethod (POST)
     * @param string $type 1=今日团购,2=历史团购
     * @param string $page 当前页
     */
    public function orderlist()
    {
        $this->verifyUser();
        $post = $this->request->post();
        $type = $this->request->post("type");
        $page = $this->request->post("page");
        if (!$type || !$page) {
            $this->error(__('parameter error'));
        }
        $orderlist = (new ModelOrder())->orderlist($post, $this->uid);
        $this->success(__('order successfully'), $orderlist);
    }

    /**
     * 我的团购-当天次数统计
     *
     * @ApiMethod (POST)
     * @param string $type 1=今日团购,2=历史团购
     * @param string $page 当前页
     */
    public function timestotal()
    {
        $this->verifyUser();
        $configtimes = Config::get("site.daily_buy_num");
        $time = Time::today();
        $where['createtime'] = ['between', [$time[0], $time[1]]];
        $where['user_id'] = $this->uid;
        $where['order_type'] = 0;
        $count = (new ModelOrder())->where($where)->count();
        $this->success(__('order successfully'), $count . "/" . $configtimes);
    }
}
