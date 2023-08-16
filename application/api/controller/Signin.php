<?php

namespace app\api\controller;

use app\api\model\Banner;
use app\api\model\Order;
use app\api\model\Useraward as ModelUseraward;
use app\common\model\sys\Signconfig;
use think\cache\driver\Redis;
use think\helper\Time;
use think\Log;
use think\Db;
use app\api\model\Usermoneylog;

/**
 * 签到
 */
class Signin extends Controller
{

    public function index()
    {
        $this->verifyUser();
        $user_id = $this->uid;
        $list = (new Signconfig())->order('id asc')->field('id,day,money')->select();
        foreach ($list as &$value) {
            $status = db('sign_log')->where(['user_id' => $user_id, "day" => $value['day']])->field('id')->find();
            if (!empty($status)) {
                $value['is_sign'] = 1;
            } else {
                $value['is_sign'] = 0;
            }
        }
        $return = [
            'total_money' => db('sign_log')->where(['user_id' => $user_id])->sum('money'),
            'list' => $list
        ];
        $this->success(__("operate successfully"), $return);
    }

    /**
     * 用户签到
     * @return void
     * @throws \think\Exception
     */
    public function signin()
    {
        $this->verifyUser();
        $day = $this->request->post('day');
        $user_id = $this->uid;
        $signin = db('sign_log')->where(['user_id' => $user_id])->whereTime('createtime', 'today')->count();
        if ($signin) {
            $this->error(__("Signed in"));
        }
        $is_set = db('sign_log')->where(['user_id' => $user_id])->order('id desc')->find();
        if (!empty($is_set)) {
            $arivDate = $is_set['createtime'];
            $depDate = time();
            $datediff = abs($depDate - $arivDate);
            $day = ceil($datediff / (60 * 60 * 24));
            if ($day > 7) {
                //是否已经领取
                $is_get_sign_money = db('user')->where(['id' => $user_id])->value('is_get_sign_money');
                if ($is_get_sign_money == 1) {
                    $this->error(__("Already received the sign-in bonus"));
                }
                $money_total = db('sign_log')->where(['user_id' => $user_id])->sum('money');
                if ($money_total > 0) {
                    $rs2 = (new Usermoneylog())->moneyrecords($user_id, $money_total, 'inc', 27, "签到奖励");
                    if (!$rs2) {
                        Db::rollback();
                        $this->error(__("operation failure"));
                    }
                    //更新领取状态
                    db('user')->where(['id' => $user_id])->update(['is_get_sign_money' => 1]);
                }
                $this->success(__("Receive success"));
            }
        } else {
            $day = 1;
        }
        $config = (new Signconfig())->where(['day' => $day])->find();
        $create = [
            'user_id' => $user_id,
            'day' => $day,
            'money' => $config['money'],
            'createtime' => time(),
        ];
        Db::startTrans();
        $rs = db('sign_log')->insert($create);
        if (!$rs) {
            Db::rollback();
            $this->error(__("operation failure"));
        }
        //第七天，领奖励
        if ($day == 7) {
            $money_total = db('sign_log')->where(['user_id' => $user_id])->sum('money');
            if ($money_total > 0) {
                $rs2 = (new Usermoneylog())->moneyrecords($user_id, $money_total, 'inc', 27, "签到奖励");
                if (!$rs2) {
                    Db::rollback();
                    $this->error(__("operation failure"));
                }
                //更新领取状态
                db('user')->where(['id' => $user_id])->update(['is_get_sign_money' => 1]);
            }
        }
        Db::commit();
        $this->success(__("operate successfully"));
    }


    /**
     *好友邀请奖励列表
     *
     */
    public function rewardlist()
    {
        $this->verifyUser();
        $post = $this->request->post();
        $list = (new ModelUseraward())->rewardlist($post, $this->uid);
        $this->success(__('The request is successful'), $list);
    }

    /**
     *规则
     *
     */
    public function rule()
    {
        $list = (new ModelUseraward())->rule();
        $this->success(__('The request is successful'), $list);
    }


    /**
     *奖励领取
     *
     */
    public function rewardfor()
    {
        $this->verifyUser();
        $post = $this->request->post();
        $list = (new ModelUseraward())->rewardfor($post, $this->uid);
        $this->success(__('The request is successful'), $list);
    }
}