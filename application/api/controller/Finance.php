<?php

namespace app\api\controller;

use app\api\model\Finance as ModelFinance;
use app\api\model\Financeissue;
use app\api\model\Financeorder as ModelFinanceorder;
use app\api\model\Order as ModelOrder;
use app\api\model\Orderoften;
use think\cache\driver\Redis;
use think\Config;
use think\helper\Time;
use think\Log;

/**
 * 理财活动
 */
class Finance extends Controller
{
    /**
     * 理财列表-首页推荐
     *
     * @ApiMethod (POST)
     */
    public function homelist()
    {
        $addorder = (new ModelFinance())->homelist();
        $this->success(__('order successfully'), $addorder ?? []);
    }

    /**
     * 理财列表
     *
     * @ApiMethod (POST)
     */
    public function list()
    {
        $page = $this->request->post('page');
        if (!$page) {
            $this->error(__('parameter error'));
        }
        $addorder = (new ModelFinance())->getlist($page);
        $this->success(__('order successfully'), $addorder ?? []);
    }

    /**
     * 累计收益金额，累计购买人数
     */
    public function total()
    {
        $this->verifyUser();
        //累计收益
        $earnings = (new ModelFinanceorder())->where('status', 1)->where('state', 2)->where('is_robot', 0)
            ->where('user_id', $this->uid)->sum('earnings');
        //持有的理财
        $my_finance = (new ModelFinanceorder())->where('status', 1)->where('is_robot', 0)->where('state', 'lt', 2)->where('user_id', $this->uid)->sum('amount');
        $return = [
            'earnings' => $earnings,
            'my_finance' => $my_finance,
        ];
        $this->success(__('order successfully'), $return);
    }

    public function totaltest()
    {
        $redis = new Redis();
        $redis->handler()->select(6);
        $financelist = $redis->handler()->ZRANGEBYSCORE('newgroup:financeordernum', '-inf', '+inf', ['withscores' => true]);
        $buyers = 0;
        foreach ($financelist as $key => $value) {
            $buyers += $value;
        }
    }
    /**
     * 理财详情
     *
     * @ApiMethod (POST)
     */
    public function detail()
    {
        $this->verifyUser();
        $id = $this->request->post("id"); //期号ID
        if (!$id) {
            $this->error(__('parameter error'));
        }
        $detail = (new Financeissue())->detail($id, $this->uid);
        $this->success(__('order successfully'), $detail ?? []);
    }

    /**
     * 用户协议
     */
    public function protocol()
    {
        $data = Config::get("site.protocol");
        $this->success(__('order successfully'), $data ?? []);
    }

    /**
     * 用户特权
     */
    public function privilege()
    {
        $data = Config::get("site.privilege");
        $this->success(__('order successfully'), $data ?? []);
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
        $count = (new ModelOrder())->where($where)->count();
        $this->success(__('order successfully'), $count . "/" . $configtimes);
    }

    public function faq()
    {
        $list = db('finance_faq')->where('status', 1)->field('title,content')->where('deletetime', null)->select();
        $this->success(__('order successfully'), $list);
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
            $my_array = array("6", "7", "8", "9");
            $length = count($my_array) - 1;
            $hd = rand(0, $length);
            $begin = $my_array[$hd];
            $a = rand(10, 99);
            $b = rand(100, 999);
            //随机提现金额
            $pay_amount = "1000,3000,5000,7000,9000,6000,10000,13000,23000,28000,30000,35000,46000,50000";
            $top_up_array = explode(",", $pay_amount);
            $lengths = count($top_up_array) - 1;
            $hds = rand(0, $lengths);
            $begins = $top_up_array[$hds];

            //随机提现金额
            $pay_amounts = "150,260,430,460,320,450,600,760,890,1500,2800,1920,1560,1790,1780,2690";
            $top_up_arrays = explode(",", $pay_amounts);
            $lengthss = count($top_up_arrays) - 1;
            $hdss = rand(0, $lengthss);
            $beginss = $top_up_arrays[$hdss];

            $nickname = $begin . $a . '****' . $b;
            if ($this->language == "english") {
                $data[] = $nickname . " successfully purchased RM" . $begins;
                $data[] = $nickname . " successfully credited to RM" . $beginss;
            } else {
                $data[] = $nickname . " सफलतापूर्वक खरीदा गया RM" . $begins;
                $data[] = $nickname . " सफलतापूर्वक श्रेय दिया गया RM" . $beginss;
            }
        }
        shuffle($data);
        $this->success(__('The request is successful'), $data);
    }
}
