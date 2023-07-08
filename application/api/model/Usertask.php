<?php

namespace app\api\model;

use app\api\controller\controller;
use function EasyWeChat\Kernel\Support\get_client_ip;
use think\Model;
use think\cache\driver\Redis;
use think\Db;
use think\Log;

/**
 * FAQ
 */
class Usertask extends Model
{
    protected $name = 'user_task';

    /**
     * 月任务、日任务 判断邀请人数
     * @param $pid
     * @return void
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function taskRewardType($user_id)
    {
        $pid = (new User())->where(['id' => $user_id])->value('sid');
        if (!$pid) {
            return true;
        }
        $month = self::where(['user_id' => $pid, 'category' => 1, 'type' => 1])->whereTime('createtime', 'month')->find();
        if (!empty($month)) {
            self::where(['user_id' => $pid, 'category' => 1, 'type' => 1])->whereTime('createtime', 'month')->setInc('num', 1);
            $num = $month['num'] + 1;
        } else {
            $create = [
                'user_id' => $pid,
                'category' => 1,
                'type' => 1,
                'createtime' => time(),
                'num' => 1,
                'is_receive' => 1,
                'is_condition' => 1,
            ];
            self::create($create);
            $num = 1;
        }
        $month_reward = (new Monthreward())->where(['num' => $num])->find();
        if ($month_reward) {
            //发放奖励
            self::where(['user_id' => $pid, 'category' => 1, 'type' => 1])->whereTime('createtime', 'month')->update(['money' => $month_reward['reward']]);
            (new Usermoneylog())->yjmoneyrecords($pid, $month_reward['reward'], 'inc', 29, "月任务，拉取{$num}人");
        }
        return true;
    }

    /**
     * 一次性任务
     */
    public function oncetask($user_id, $type)
    {
        $my_task = self::where(['user_id' => $user_id, 'category' => 2, 'type' => $type])->find();
        if (empty($my_task)) {
            $day_reward = db('day_reward')->where(['type'=>$type])->find();
            $create = [
                'user_id' => $user_id,
                'category' => 2,
                'type' => $type,
                'createtime' => time(),
                'num' => 1,
                'is_receive' => 0,
                'is_condition' => 1,
                'money' => $day_reward['reward'],
            ];
            self::create($create);
            $this->dayRewardReceive($user_id,$type);
        }
    }

    /**
     * 领取每日奖励
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function dayRewardReceive($user_id,$type){
        $day_task = db('user_task')->where(['user_id'=>$user_id,'category'=>2,'type'=>$type,'is_condition'=>1])->find();
        //检查是否符合领取条件
        if($day_task){
            if($day_task['is_receive'] == 1){
                $this->error(__("You have already claimed it"));
            }
            Db::startTrans();
            $upd = [
                'is_receive' => 1,
            ];
            $rs = db('user_task')->where(['id'=>$day_task['id']])->update($upd);
            if(!$rs){
                Db::rollback();
            }
            $rs2 = (new Usermoneylog())->moneyrecords($user_id, $day_task['money'], 'inc', 28, "日任务，类型{$type}");
            if(!$rs2){
                Db::rollback();
            }
            Db::commit();
        }
    }
}
