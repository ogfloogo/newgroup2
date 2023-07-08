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
 * 理财下单
 */
class Financeorder extends Model
{
    protected $name = 'finance_order';

    public function addorder($post, $userinfo, $price, $finance_info, $issue_info)
    {
        (new Financeissue())->updatestatus($issue_info['end_time'], $issue_info['presell_end_time'], $issue_info['status'], $issue_info['id'], $issue_info['finance_id']);
        //生成唯一订单号
        $order_id = $this->createorder();
        while ($this->where(['order_id' => $order_id])->find()) {
            $order_id = $this->createorder();
        }
        //开始收益时间
        // $earning_start_time = strtotime(date("Y-m-d", $issue_info['presell_end_time'] + 60 * 60 * 24) . " 00:00:00");
        $earning_start_time = $issue_info['start_time'];
        //结束收益时间-发放时间
        // $earning_end_time = $earning_start_time + 60 * 60 * 24 * $finance_info['day'];
        $earning_end_time = $issue_info['end_time'];
        try {
            $insert = [
                'finance_id' => $finance_info['id'], //理财活动ID
                'user_id' => $userinfo['id'], //用户ID
                'issue_id' => $post['issue_id'], //期号ID
                'amount' => $price, //下单金额
                'buy_number' => $post['num'], //下单数量
                'order_id' => $order_id, //订单号
                'level' => $userinfo['level'], //用户等级
                'earning_start_time' => $earning_start_time, //开始收益时间
                'earning_end_time' => $earning_end_time, //结束收益时间
                'status' => 1,
                'state' => 0,
                'buy_time' => time(), //购买时间
                'createtime' => time(),
                'updatetime' => time(),
            ];
            //创建理财订单
            $order_id = $this->insertGetid($insert);
            //支付
            $paynow = (new Usermoneylog())->moneyrecords($userinfo['id'], $price, 'dec', 18, "理财下单");
            if (!$paynow) {
                Db::rollback();
                Log::mylog('理财支付失败', $order_id, 'financeorder');
            }
            Db::commit();
            //push 理财发放
            $redis = new Redis();
            $redis->handler()->select(6);
            $redis->handler()->zAdd("newgroup:financelist", $earning_end_time, $order_id);
            $rate = $this->getrate($finance_info['id'], $post['issue_id']);
            //更新费率
            $this->where('id', $order_id)->update(['buy_rate' => intval($rate['rate'])]);
            //统计
            $this->statistics($finance_info['id'], $post['issue_id'], $price, $userinfo['id']);
            //插入上次下单时间
            $redis = new Redis();
            $redis->handler()->select(2);
            $redis->handler()->set("newgroup:financeordertime:" . $userinfo['id'], $userinfo['id'], 5);
            //当前收益率
            $now_rate = (new Financeorder())->getrate($finance_info['id'], $post['issue_id']);
            $ratelist = (new Financerate())->detail($finance_info['id']);
            $return = [
                'presell_start_time' => $issue_info['presell_start_time'],
                'presell_end_time' => $issue_info['presell_end_time'],
                'start_time' => $issue_info['start_time'],
                'end_time' => $issue_info['end_time'],
                'end_days' => $issue_info['end_time'] + 60 * 60 * 24,
            ];
            $rate_info = (new Financeissue())->getnextrate($ratelist, $now_rate);
            if ($rate_info) {
                $return['adding'] = $rate_info['start'] - $now_rate['start'];
                $return['yield_by_rate'] = bcsub($rate_info['rate'], $now_rate['rate'], 1);
            } else {
                $return['adding'] = 0;
                $return['yield_by_rate'] = 0;
            }
            return $return;
        } catch (Exception $e) {
            Log::mylog('理财下单失败', $e, 'financeorder');
            //刷新用户信息
            (new User())->refresh($userinfo['id']);
            return false;
        }
    }

    /**
     * 获取当前收益率
     */
    public function getrate($finance_id, $issue_id)
    {
        $redis = new Redis();
        $redis->handler()->select(6);
        $order_number = $redis->handler()->zScore("newgroup:financeordernum", $issue_id);
        if (!$order_number) {
            $order_number = 0;
        }
        $redis->handler()->select(0);
        $rate = $redis->handler()->ZRANGEBYSCORE('new:finance_rate:set:' . $finance_id, $order_number, '+inf', ['limit' => [0, 1]]);
        $rate_info = $redis->handler()->Hgetall("new:finance_rate:" . $rate[0]);
        return $rate_info;
    }

    /**
     * 获取当前购买人数
     */
    public function getbuyers($issue_id)
    {
        $redis = new Redis();
        $redis->handler()->select(6);
        $buyers = $redis->handler()->zScore("newgroup:financeordernum", $issue_id);
        if (!$buyers) {
            $buyers = 0;
        }
        return $buyers;
    }

    /**
     * 统计下单人数，下单金额
     */
    public function statistics($finance_id, $issue_id, $amount, $user_id)
    {
        //当前用户是否下单过
        $order_num = $this->where('finance_id', $finance_id)->where('issue_id', $issue_id)->where('user_id', $user_id)->count();
        $redis = new Redis();
        $redis->handler()->select(6);
        //该活动未下过单
        if ($order_num == 1) {
            $is_exist = $redis->handler()->zScore("newgroup:financeordernum", $issue_id);
            if ($is_exist) {
                //更新分数
                $redis->handler()->zIncrBy("newgroup:financeordernum", 1, $issue_id);
            } else {
                $redis->handler()->zAdd("newgroup:financeordernum", 1, $issue_id);
            }
        }
        //下单金额
        $is_exist_amount = $redis->handler()->zScore("newgroup:financeordermoney", $issue_id);
        if ($is_exist_amount) {
            //更新分数
            $redis->handler()->zIncrBy("newgroup:financeordermoney", $amount, $issue_id);
        } else {
            $redis->handler()->zAdd("newgroup:financeordermoney", $amount, $issue_id);
        }
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
     * 我的理财列表
     * @ApiMethod (POST)
     * @param string $type 1=今日团购,2=历史团购
     * @param string $page 当前页
     */
    public function orderlist($post, $user_id)
    {
        $pageCount = 10;
        $startNum = ($post['page'] - 1) * $pageCount;
        $list = $this
            ->where('user_id', $user_id)
            ->where('state', $post['type'])
            ->where('deletetime', null)
            ->where('is_robot', 0)
            ->order('state desc')
            ->order('createtime desc')
            ->field('order_id,finance_id,issue_id,issue_id as id,state,amount,earning_end_time,buy_rate_end')
            // ->group('issue_id')
            ->limit($startNum, $pageCount)
            ->select();
        foreach ($list as $key => $value) {
            $issue_info = (new Financeissue())->where('id', $value['issue_id'])->find();
            $list[$key]['name'] = $issue_info['name'];
            $list[$key]['presell_start_time'] = $issue_info['presell_start_time'];
            $list[$key]['presell_end_time'] = $issue_info['presell_end_time'];
            $list[$key]['start_time'] = $issue_info['start_time'];
            $list[$key]['end_time'] = $issue_info['end_time'];
            $list[$key]['end_days'] = $issue_info['end_time'] + 60 * 60 * 24;
            $list[$key]['status'] = $issue_info['status'];
            $list[$key]['day'] = $issue_info['day'];
            $finance_info = (new Finance())->detail($value['finance_id']);
            $list[$key]['finance_name'] = $finance_info['name'];
            $list[$key]['price'] = $finance_info['price'];
            //购买人数-收益率
            $rate = (new Financerate())->detail($value['finance_id']);
            $list[$key]['ratelist'] = $rate;
            //当前收益率
            $now_rate = ($this->getrate($value['finance_id'], $value['issue_id']))['rate'];
            $list[$key]['rate'] = $now_rate;
            //预计收益
            $list[$key]['anticipated_income'] = bcmul($now_rate/100,$value['amount'],2);
            //购买人数
            $list[$key]['buyers'] = $this->getbuyers($value['issue_id']);
            if ($value['state'] == 0 && $value['presell_end_time'] < time()) {
                $this->where('order_id', $value['order_id'])->update(['state' => 1]);
            }
        }
        return $list;
    }

    /**
     * 获取当前期号最后收益率
     */
    public function getissuerate($order_info)
    {
        $redis = new Redis();
        $redis->handler()->select(6);
        $now_rate = $redis->handler()->zScore("newgroup:issuerate", $order_info['issue_id']);
        if (!$now_rate) {
            $now_rate = $this->getrate($order_info['finance_id'], $order_info['issue_id']);
            $redis->handler()->zAdd("newgroup:issuerate", $now_rate['rate'], $order_info['issue_id']);
        }
        return $now_rate;
    }

    /**
     * 理财机器人下单
     */
    public function financeRobotBuy($data)
    {
        //生成唯一订单号
        $order_id = $this->createorder();
        while ($this->where(['order_id' => $order_id])->find()) {
            $order_id = $this->createorder();
        }
        //期号
        $issue_info = (new Financeissue())->where('status', 0)->where('finance_id', $data['finance_id'])->where('presell_end_time', 'gt', time())->where('deletetime',null)->order('id desc')->find();
        if (!$issue_info) {
            return false;
        }
        $finance_info = (new Finance())->detail($data['finance_id']);
        $price = bcmul($finance_info['price'], $data['number'], 2);
        $insert = [
            'finance_id' => $data['finance_id'], //理财活动ID
            'user_id' => $data['robot_user_id'], //用户ID
            'issue_id' => $issue_info['id'], //期号ID
            'amount' => $price, //下单金额
            'buy_number' => $data['number'], //下单数量
            'order_id' => $order_id, //订单号
            'level' => 0,
            'status' => 1,
            'state' => 0,
            'is_robot' => 1,
            'buy_time' => time(), //购买时间
            'createtime' => time(),
            'updatetime' => time(),
        ];
        //创建理财订单
        $order_id = $this->insertGetid($insert);
        Db::commit();
        //更新用户最后下单时间
        db('user_robot')->where('id', $data['robot_user_id'])->update(['buytime' => time()]);
        //统计
        $this->robotstatistics($issue_info['id'], $price);
        $rand_time = time() + rand($finance_info['robot_addorder_time_start'], $finance_info['robot_addorder_time_end']);
        $rand_num = rand($finance_info['robot_addorder_num_start'], $finance_info['robot_addorder_num_end']);
        $redis = new Redis();
        $redis->handler()->select(6);
        $redis->handler()->hset("newgroup:finance:robot", $data['finance_id'], $rand_time . "-" . $rand_num);
    }

    /**
     * 开启机器人
     * finance_id 理财活动ID
     * robot_addorder_time_start 下单时间间隔1
     * robot_addorder_time_end 下单时间间隔2
     * robot_addorder_num_start 每次下单数量1
     * robot_addorder_num_end 每次下单数量2
     */
    public function openrobot($finance_id, $robot_addorder_time_start, $robot_addorder_time_end, $robot_addorder_num_start, $robot_addorder_num_end)
    {
        $rand_time = time() + rand($robot_addorder_time_start, $robot_addorder_time_end);
        $rand_num = rand($robot_addorder_num_start, $robot_addorder_num_end);
        $redis = new Redis();
        $redis->handler()->select(6);
        $open = $redis->handler()->hset("newgroup:finance:robot", $finance_id, $rand_time . "-" . $rand_num);
        if (!$open) {
            return false;
        }
        return true;
    }

    /**
     * 关闭机器人
     * finance_id 理财活动ID
     */
    public function closerobot($finance_id)
    {
        $redis = new Redis();
        $redis->handler()->select(6);
        $close = $redis->handler()->hDel("newgroup:finance:robot", $finance_id);
        if (!$close) {
            return false;
        }
        return true;
    }

    /**
     * 统计下单人数，下单金额
     */
    public function robotstatistics($issue_id, $amount)
    {
        $redis = new Redis();
        $redis->handler()->select(6);
        //该活动未下过单
        $is_exist = $redis->handler()->zScore("newgroup:financeordernum", $issue_id);
        if ($is_exist) {
            //更新分数
            $redis->handler()->zIncrBy("newgroup:financeordernum", 1, $issue_id);
        } else {
            $redis->handler()->zAdd("newgroup:financeordernum", 1, $issue_id);
        }
        //下单金额
        $is_exist_amount = $redis->handler()->zScore("newgroup:financeordermoney", $issue_id);
        if ($is_exist_amount) {
            //更新分数
            $redis->handler()->zIncrBy("newgroup:financeordermoney", $amount, $issue_id);
        } else {
            $redis->handler()->zAdd("newgroup:financeordermoney", $amount, $issue_id);
        }
    }
}
