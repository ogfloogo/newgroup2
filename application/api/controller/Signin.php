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

    public function index(){
        $user_id = 154;
        $list = (new Signconfig())->order('id asc')->field('id,day,money')->select();
        foreach($list as &$value){
            $status = db('sign_log')->where(['user_id'=>$user_id,"day"=>$value['day']])->field('id')->find();
            if(!empty($status)){
                $value['is_sign'] = 1;
            }else{
                $value['is_sign'] = 0;
            }
        }
        $return = [
            'total_money' => db('sign_log')->where(['user_id'=>$user_id])->sum('money'),
            'list' => $list
        ];
        $this->success(__("operate successfully"),$return);
    }

    /**
     * 用户签到
     * @return void
     * @throws \think\Exception
     */
    public function signin(){
        // $this->verifyUser();
        $user_id = 154;
        $signin = db('sign_log')->where(['user_id'=>$user_id])->whereTime('createtime','today')->count();
        if($signin){
            $this->error(__("Signed in"));
        }
        $is_set = db('sign_log')->where(['user_id'=>$user_id])->order('id desc')->find();
        if(!empty($is_set)){
            if($is_set['day'] >= 7){
                $this->error(__("Signed in."));
            }
            $day = $is_set['day']+1;
        }else{
            $day = 1;
        }
        $config = (new Signconfig())->where(['day'=>$day])->find();
        $create = [
            'user_id' => $user_id,
            'day' => $day,
            'money' => $config['money'],
            'createtime' => time(),
        ];
        Db::startTrans();
        $rs = db('sign_log')->insert($create);
        if(!$rs){
            Db::rollback();
            $this->error(__("operation failure"));
        }
        //第七天，领奖励
        if($day == 7){
            $rs2 = (new Usermoneylog())->moneyrecords($user_id, $config['money'], 'inc', 27, "签到奖励");
            if(!$rs2){
                Db::rollback();
                $this->error(__("operation failure"));
            }
        }
        Db::commit();
        $this->success(__("operate successfully"));
    }


    /**
     *好友邀请奖励列表
     *
     */
    public function rewardlist(){
        $this->verifyUser();
        $post = $this->request->post();
        $list = (new ModelUseraward())->rewardlist($post,$this->uid);
        $this->success(__('The request is successful'),$list);
    }

    /**
     *规则
     *
     */
    public function rule(){
        $list = (new ModelUseraward())->rule();
        $this->success(__('The request is successful'),$list);
    }


    /**
     *奖励领取
     *
     */
    public function rewardfor(){
        $this->verifyUser();
        $post = $this->request->post();
        $list = (new ModelUseraward())->rewardfor($post,$this->uid);
        $this->success(__('The request is successful'),$list);
    }
}
