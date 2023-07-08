<?php

namespace app\api\model;

use function EasyWeChat\Kernel\Support\get_client_ip;

use app\api\controller\Shell;
use think\Model;
use think\cache\driver\Redis;
use think\Db;
use think\Exception;
use think\helper\Time;
use think\Log;

/**
 * 团购下单
 */
class Order extends Model
{
    protected $name = 'order';

    public function addorder($post, $userinfo, $good_info)
    {
        //生成唯一订单号
        $order_id = $this->createorder();
        while ($this->where(['order_id' => $order_id])->find()) {
            $order_id = $this->createorder();
        }
        try {
            //创建团购订单
            $insert = [
                'user_id' => $userinfo['id'], //用户ID
                'amount' => $good_info['price'], //下单金额
                'buyback' => $good_info['buyback'], //回购价
                'order_id' => $order_id, //订单号
                'level' => $userinfo['level'], //用户等级
                'good_id' => $post['good_id'], //商品ID
                // 'type' => $post['type'], //下单类型 1=开团订单,2=一键成团订单
                'createtime' => time(),
                'updatetime' => time(),
                "agent_id" => intval($userinfo['agent_id']),

            ];
            //下单，扣除余额，获得订单ID
            $id = (new Usermoneylog())->moneyrecordorders($insert, $userinfo, $good_info['price'], 'dec', 5, $order_id);
            if (!$id) { //order failed
                return false;
            }
            //统计用户下单次数
            (new Usertotal())->where('user_id', $userinfo['id'])->setInc('balance_investment', 1);
            $statistics = (new Usercategory())->statistics($post, $userinfo, $id, $good_info);
            if ($statistics && $statistics['code'] == 1) {
                $is_win = 1;
            } else {
                $is_win = 0;
            }
            //更新订单中奖状态
            $this->where('id', $id)->update(['is_winner' => $is_win]);
            //更新团购任务
            $is_addorder = $this->where(['user_id'=>$userinfo['id'],'order_type'=>0])->count();
            if($is_addorder == 1){
                (new Usertask())->oncetask($userinfo['id'],4);
            }
            //刷新用户信息
            (new User())->refresh($userinfo['id']);
            //插入上次下单时间
            $redis = new Redis();
            $redis->handler()->select(2);
            $redis->handler()->set("newgroup:addorder:" . $userinfo['id'], $userinfo['id'], 5);
            //插入最后一次下单时间
            $redis->handler()->set("newgroup:addordertime:" . $userinfo['id'], time(), 60*60*24*2);
            return [
                'order_info' => $this->where('id', $id)->field('amount,buyback,order_id,level,good_id,type,createtime,updatetime,earnings,income')->find(),
                'is_win' => $is_win
            ];
        } catch (Exception $e) {
            Log::mylog('团购order failed', $e, 'order');
            //刷新用户信息
            (new User())->refresh($userinfo['id']);
            return false;
        }
    }

    public function addorders($post, $userinfo, $good_info,$price)
    {
        //生成唯一订单号
        $order_id = $this->createorder();
        while ($this->where(['order_id' => $order_id])->find()) {
            $order_id = $this->createorder();
        }
        if($good_info['category_id'] == 2){
            $type = 1;
        }else{
            $type = 2;
        }
        try {
            //创建团购订单
            $insert = [
                'user_id' => $userinfo['id'], //用户ID
                'amount' => $price, //下单金额
                'order_id' => $order_id, //订单号
                'good_id' => $post['good_id'], //商品ID
                'order_type' => $type,
                'createtime' => time(),
                'updatetime' => time(),
                "agent_id" => intval($userinfo['agent_id']),
            ];
            if($type == 1){//25=秒杀，35=新人
                $money_type = 25;
            }else{
                $money_type = 35;
            }
            //下单，扣除余额，获得订单ID 
            $id = (new Usermoneylog())->moneyrecordorders($insert, $userinfo, $price, 'dec', $money_type, $order_id);
            if (!$id) { //order failed
                return false;
            }
            (new Userwarehouse())->drawwinnings($post, $userinfo, $id, $good_info,$type);
            //更新销量
            (new Goods())->where(['id'=>$post['good_id']])->setInc("sales",1);
            //更新redis
            (new Goods())->upd($post['good_id']);
            (new User())->refresh($userinfo['id']);
            return [
                'order_info' => $this->where('id', $id)->field('amount,buyback,order_id,level,good_id,type,createtime,updatetime,earnings,income,order_type')->find(),
            ];
        } catch (Exception $e) {
            Log::mylog('秒杀,新人order failed', $e, 'orders');
            //刷新用户信息
            (new User())->refresh($userinfo['id']);
            return false;
        }
    }

    public function getcountdown($user_id){
        //倒计时
        $redis = new Redis();
        $redis->handler()->select(2);
        //最后一次下单时间
        $countdown = $redis->handler()->get("newgroup:addordertime:" . $user_id);
        if($countdown){
            if(date("Ymd",time()) == date("Ymd",$countdown)){
                $countdown = 0;
            }else{
                $countdown = $countdown+60*60*24;
                if($countdown < time()){
                    $countdown = 0;
                }
            }
        }else{
            $countdown = 0;
        }
        return $countdown;
    }
    /**
     * 生成唯一订单号
     */
    public function createorder()
    {
        $msec = substr(microtime(), 2, 2);        //	毫秒
        $subtle = substr(uniqid('', true), -8);    //	微妙
        return date('YmdHis') . $msec . $subtle;  // 当前日期 + 当前时间 + 当前时间毫秒 + 当前时间微妙
    }

    /**
     * 订单详情
     */
    public function orderdetail($order_id)
    {
        return $this->where('id', $order_id)->find();
    }

    /**
     * 我的团购列表
     * @ApiMethod (POST)
     * @param string $type 1=今日团购,2=历史团购
     * @param string $page 当前页
     */
    public function orderlist($post, $user_id)
    {
        $time = Time::today();
        if ($post['type'] == 1) {
            $where['user_id'] = $user_id;
            $where['createtime'] = ['between', [$time[0], $time[1]]];
            $where['order_type'] = 0;
        } else {
            $where['user_id'] = $user_id;
            $where['order_type'] = 0;
        }
        $pageCount = 10;
        $startNum = ($post['page'] - 1) * $pageCount;
        $list = $this
            ->where($where)
            ->order('createtime desc')
            ->limit($startNum, $pageCount)
            ->select();
        foreach ($list as $key => $value) {
            $goods_info = (new Goods())->detail($value['good_id']);
            if ($goods_info) {
                $list[$key]['good_name'] = $goods_info['name'] ?? "";
                $list[$key]['cover_image'] = format_image($goods_info['cover_image']);
                $list[$key]['createtime'] = format_time($value['createtime']);
            }
        }
        return $list;
    }
}
