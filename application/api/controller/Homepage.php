<?php

namespace app\api\controller;

use app\api\model\Banner;
use app\api\model\Commission;
use app\api\model\Goods;
use app\api\model\Goodscategory;
use app\api\model\Level;
use app\api\model\Order;
use app\api\model\User;
use app\api\model\Usercash;
use app\api\model\UserLevelLog;
use app\api\model\Usermoneylog;
use app\api\model\Userteam;
use think\cache\driver\Redis;
use think\Config;
use think\helper\Time;
use think\Log;

/**
 * 首页
 */
class Homepage extends Controller
{

    /**
     * 广播类型
     */
    const TYPEENGLIST = [
        "english" => [
            1 => "充值",
            2 => "提现",
            3 => "邀请奖励",
            4 => "佣金收入",
            5 => "团购下单",
            6 => "拒绝提现",
            7 => "团购奖励",
            8 => "团长奖励",
            9 => "新用户注册奖励",
            10 => "管理员操作",
            11 => "兑换现金",
            12 => "团购未中奖返还"
        ],
    ];

    /**
     *轮播图
     *
     */
    public function getbanner()
    {
        $redis = new Redis();
        $list = $redis->handler()->get("newgroup:banner:list");
        if ($list) {
            $list = json_decode($list, true);
            $checklogin = $this->getCacheUser();
            if ($checklogin) {
                $return = [];
                foreach ($list as &$value) {
                    $value['image'] = format_image($value['image']);
                    //新人限时福利倒计时
                    $validity_config = Config::get("site.validity");
                    $validity = $checklogin['createtime'] + $validity_config * 60;
                    if ($validity < time()) {
                        if ($value['jump_url'] != "/pages/home/newcomer") {
                            $return[] = $value;
                        }
                    }else{
                        $return[] = $value;
                    }
                }
                $list = $return;
            } else {
                foreach ($list as $key => $value) {
                    $list[$key]['image'] = format_image($value['image']);
                }
            }
        } else {
            $list = [];
        }

        $this->success(__('The request is successful'), $list);
    }

    /**
     * 购买记录播报
     * 
     */
    public function broadcast()
    {
        //列表
        $data = [];
        for ($i = 0; $i < 20; $i++) {
            //随机电话号段
            $my_array = array("010","012","013","014","016","017","018");
            $length = count($my_array) - 1;
            $hd = rand(0, $length);
            $begin = $my_array[$hd];
            $a = rand(10, 99);
            $b = rand(100, 999);
            //随机提现金额
            $pay_amount = "800,1200,1800,3200,4500,6000,76000,10000,23000,28000,30000,35000,46000,50000";
            $top_up_array = explode(",", $pay_amount);
            $lengths = count($top_up_array) - 1;
            $hds = rand(0, $lengths);
            $begins = $top_up_array[$hds];

            //随机提现金额
            $pay_amounts = Config::get("site.pay_amount");
            $top_up_arrays = explode(",", $pay_amounts);
            $lengthss = count($top_up_arrays) - 1;
            $hdss = rand(0, $lengthss);
            $beginss = $top_up_arrays[$hdss];

            $nickname = $begin . '****' . $b;
            $data[] = "(" . $nickname . "）Withdrawal completed RM" . $begins;
            $data[] = "(" . $nickname . "）Recharge completed RM" . $beginss;
        }
        shuffle($data);
        $this->success(__('The request is successful'), $data);
    }

    /**
     * 弹窗
     */
    public function popupwindows()
    {
        $this->verifyUser();
        $userinfo = $this->userInfo;
        $res = [
            'member' => 0,
            'income' => 0,
            'list' => []
        ];
        $list = (new Userteam())
            ->alias('a')
            ->join('user b', 'a.team=b.id')
            ->where('a.user_id', $this->uid)
            ->where('b.level', 'gt', $userinfo['level'])
            ->field('b.nickname,b.avatar,b.level,a.level as levels')
            ->limit(9)
            ->select();
        if ($list) {
            $res['member'] = count($list);
            $res['list'] = $list;
            $redis = new Redis();
            $income = 0;
            foreach ($list as $key => $value) {
                $list[$key]['avatar'] = format_image($value['avatar']);
                $goodscategory = (new Goodscategory())->detail($value['level']);
                //团购奖励
                $commission_tg = bcmul($goodscategory['reward'], 12, 2);
                //等级佣金
                $commission_fee_level = 'commission_fee_level_' . $value['levels'];
                $levelinfo = $redis->handler()->hMget("newgroup:level:" . $value['level'], [$commission_fee_level]);
                $commission = bcmul($commission_tg, $levelinfo[$commission_fee_level] / 100, 2);
                $income += $commission;
            }
            $res['income'] = bcmul($income, 10, 2);
        }
        $this->success(__('The request is successful'), $res);
    }

    /**
     * 弹窗2
     */
    public function popuplevelwindows()
    {
        $this->verifyUser();
        $time = Time::yesterday();
        $userinfo = $this->userInfo;
        //昨日团购收入
        (new Usermoneylog())->settables($this->uid);
        $commission_tg = (new Usermoneylog())->where('user_id', $this->uid)->where('createtime', 'between', [$time[0], $time[1]])->where('type', 'in', [7, 8])->sum('money');
        //昨日佣金收入
        $commission = new Commission();
        $commission->setTableName($userinfo['id']);
        $commission_child = $commission->where('to_id', $userinfo['id'])->where('createtime', 'between', [$time[0], $time[1]])->sum('commission');
        //昨日总收入
        $commission_total = bcadd($commission_tg, $commission_child, 2);
        if ($commission_total == 0) {
            $return = [];
        } else {
            //下一个等级需要的金额
            $next_level_info = (new Level())->mylevel_commission_rates($userinfo['level'] + 1);
            if ($next_level_info) {
                //当天预计收入
                $income = (new Userteam())->Imtoget($userinfo['level']);
                $next_money = $next_level_info['become_balance'];
                $less = bcsub($next_money, $userinfo['money'], 2);
                //距离升级需要的天数
                $days = bcdiv($less, $income, 2);
                $return = [
                    'money' => $commission_total,
                    'days' => $days,
                    'must' => $less
                ];
            } else {
                $return = [];
            }
        }
        $this->success(__('The request is successful'), $return);
    }

    /**
     * 等级升级变化
     */
    public function upgrade()
    {
        $this->verifyUser();
        $level_log = (new UserLevelLog())->where('user_id', $this->uid)->where('up', 1)->where('status', 0)->order('createtime desc')->find();
        if ($level_log) {
            //旧等级
            $old_level = $level_log['old_level'];
            $old_level_info = (new Level())->mylevel_commission_rates($old_level);
            $old_goodscategory = (new Goodscategory())->where('level', $old_level)->find();
            //升级后的等级
            $new_level = $level_log['level'];
            $new_level_info = (new Level())->mylevel_commission_rates($new_level);
            $new_goodscategory = (new Goodscategory())->where('level', $new_level)->find();
            //推荐商品
            $list = (new Goods())->recommend($this->userInfo);
            $return = [
                'old_level_name' => $old_level_info['name'],
                'new_level_name' => $new_level_info['name'],
                'old_daliy_income' => bcmul($old_goodscategory['reward'], 12, 2),
                'new_daliy_income' => bcmul($new_goodscategory['reward'], 12, 2),
                'old_level_rate' => $old_level_info['commission_fee_level_1'],
                'new_level_rate' => $new_level_info['commission_fee_level_1'],
                'old_open_group_num' => $old_level_info['open_group_num'],
                'new_open_group_num' => $new_level_info['open_group_num'],
                'goodlist' => $list
            ];
            (new UserLevelLog())->where('id', $level_log['id'])->update(['status' => 1]);
        }
        $this->success(__('The request is successful'), $return ?? []);
    }

    public function deluser()
    {
        $this->verifyUser();
        (new User())->where('id', $this->uid)->update(['status' => 0]);
        (new User())->refresh($this->uid);
        $this->success(__('The request is successful'));
    }
}
