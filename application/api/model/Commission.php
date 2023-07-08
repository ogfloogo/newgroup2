<?php

namespace app\api\model;

use app\api\controller\controller;
use think\Model;
use think\cache\driver\Redis;
use think\Config;
use think\Db;
use think\Exception;
use think\Log;

/**
 * 团购佣金
 */
class Commission extends Model
{
    protected $name = 'commission_log';

    /**
     * 佣金发放
     * @param string $user_id  用户ID
     * @param string $amount  收益金额
     * @param string $order_id  订单ID
     * @param string $level  我的等级
     */
    public function commissionissued($user_id, $amount, $order_id, $mylevel, $agent_id = 0)
    {
        $userteam = new Userteam();
        $redis = new Redis();
        //一级
        $pushlist = [];
        $first = $userteam->where('team', $user_id)->where('level', 1)->field('user_id')->find();
        if ($first) {
            $rate = Config::get("site.first");
            $commission = bcmul($amount, $rate / 100, 2);
            $this->push($first['user_id'], $user_id, 1, $order_id, $rate, $commission, $agent_id);
            //二级
            $second = $userteam->where('team', $user_id)->where('level', 2)->field('user_id')->find();
            if ($second) {
                //我的上上级
                $rate = Config::get("site.second");
                $commission = bcmul($amount, $rate / 100, 2);
                $this->push($second['user_id'], $user_id, 2, $order_id, $rate, $commission, $agent_id);
                //三级
                $third = $userteam->where('team', $user_id)->where('level', 3)->field('user_id')->find();
                if ($third) {
                    //我的上上上级
                    $rate = Config::get("site.third");
                    $commission = bcmul($amount, $rate / 100, 2);
                    $this->push($third['user_id'], $user_id, 3, $order_id, $rate, $commission, $agent_id);
                }
            }
        }
    }

    /**
     * json入列
     */
    public function pushjson($data)
    {
        $redis = new Redis();
        $push = $redis->handler()->rpush("newgroup:yjlist", json_encode($data, JSON_UNESCAPED_SLASHES));
        // if ($push !== false) {
        //     Log::mylog('push', $data, 'push');
        // }
    }
    /**
     * 入列
     */
    public function push($to_id, $from_id, $level, $order_id, $commission_fee, $commission, $agent_id = 0)
    {
        $redis = new Redis();
        $value = $to_id . "-" . $from_id . "-" . $level . "-" . $order_id . "-" . $commission_fee . "-" . $commission . "-" . $agent_id;
        $push = $redis->handler()->rpush("newgroup:commissionlist", $value);
        // if ($push !== false) {
        //     Log::mylog('push', $value, 'push');
        // }
    }

    public function setTableName($user_id)
    {
        $mod = 1000;
        $table_number = ceil($user_id / $mod);
        if ($user_id <= 1000) {
            $tb_num = ceil($user_id / 100);
            $table_name = "fa_commission_log_1_" . $tb_num;
        } else {
            $table_name = "fa_commission_log_" . $table_number;
        }
        $this->setTable($table_name);
    }

    public function gettable($user_id)
    {
        $mod = 1000;
        $table_number = ceil($user_id / $mod);
        if ($user_id <= 1000) {
            $tb_num = ceil($user_id / 100);
            $table_name = "fa_commission_log_1_" . $tb_num;
        } else {
            $table_name = "fa_commission_log_" . $table_number;
        }
        return $table_name;
    }

    public function createtb($user_id)
    {
        $table_name = $this->gettable($user_id);
        db()->query("CREATE TABLE IF NOT EXISTS " . $table_name . ' LIKE ' . 'fa_commission_log_base');
    }
}
