<?php

namespace app\api\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\api\model\User;
use app\api\model\Usermoneylog;
use app\api\model\Userrecharge;
use think\cache\driver\Redis;
use think\Log;

class Sendmoney extends Command
{
    protected $model = null;

    protected function configure()
    {
        // 指令配置
        $this->setName('Sendmoney')
            ->setDescription('the Sendmoney command');
    }

    protected function execute(Input $input, Output $output)
    {
        // $time = time() - 60 * 60 * 24 * 3;
        // $list = (new User())
        //     ->where('createtime', 'lt', $time)
        //     ->where('id','gt',4010)
        //     ->field('id,createtime')
        //     ->order('id asc')
        //     ->select();
        // $data = [];
        // foreach ($list as $key => $value) {
        //     (new Usermoneylog())->settables($value['id']);
        //     $moneylog = (new Usermoneylog())->where('user_id',$value['id'])->where('type',13)->find();
        //     $is_recharge = (new Userrecharge())->where('user_id',$value['id'])->where('status',1)->value('id');
        //     if(!$is_recharge && !$moneylog){
        //         $data[] = $value;
        //         Log::mylog('data2',$value,'datas');
        //         $redis = new Redis();
        //         $redis->handler()->select(6);
        //         $redis->handler()->zAdd("newgroup:sendlist", $value['createtime']+60*60*24*3, $value['id']);
        //     }
        // }
        $is_recharge = db('user_recharge')->where('status', 1)->group('user_id')->field('user_id')->select();
        dump($is_recharge);
        $redis = new Redis();
        $redis->handler()->select(6);
        foreach ($is_recharge as $key => $value) {
            (new Usermoneylog())->settables($value['user_id']);
            $moneylog = (new Usermoneylog())->where('user_id', $value['user_id'])->where('type', 13)->find();
            if (!empty($moneylog)) {
                $is_th = (new Usermoneylog())->where('user_id', $value['user_id'])->where(['type' => 10, 'money' => 99])->find();
                if (empty($is_th)) {
                    Log::mylog('data', $value['user_id'], 'sendmoneys');
                    //退还
                    $usermoneylog = (new Usermoneylog())->moneyrecords($value['user_id'], $moneylog['money'], 'inc', 10, "首充体验金退还");
                    if (!$usermoneylog) {
                        Db::rollback();
                        return false;
                    }
                }
            }
            $end_time = $redis->handler()->zScore("newgroup:sendlist", $value['user_id']); //到期时间
            if ($end_time) {
                $redis->handler()->zRem('newgroup:sendlist', $value['user_id']);
            }
        }
        // Log::mylog('data',$data,'sendmoney');
        echo "执行成功" . "\n";
    }
}
