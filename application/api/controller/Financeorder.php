<?php

namespace app\api\controller;

use app\admin\model\financebuy\FinanceOrder as FinancebuyFinanceOrder;
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
 * 理财活动下单
 */
class Financeorder extends Controller
{

    /**
     * 理财下单
     *
     * @ApiMethod (POST)
     * @param string $issue_id 期号ID
     * @param string $num 购买数量
     */
    public function addorder()
    {
        $this->verifyUser();
        $userinfo = $this->userInfo;
        //下单时间限制
        $redis = new Redis();
        $redis->handler()->select(2);
        $last = $redis->handler()->get("newgroup:financeordertime:" . $this->uid);
        if ($last) {
            //获取头部
            $header = $this->request->header();
            (new Orderoften())->insert([
                "ip" => get_real_ip(),
                "user_id" => $this->uid,
                "content" => json_encode($header),
                "createtime" => time()
            ]);
            $this->error(__('Requests are too frequent'));
        }
        $post = $this->request->post();
        $issue_id = $this->request->post("issue_id"); //期号ID
        $num = $this->request->post("num"); //购买数量
        if (!$num || !$issue_id) {
            $this->error(__('parameter error'));
        }
        //当期期数data
        $issue_info = (new Financeissue())->where('id',$issue_id)->find();
        if(!$issue_info){
            $this->error(__("Activities that don't exist"));
        }
        //活动是否已经开始
        if($issue_info['presell_start_time'] > time()){
            $this->error(__("Activity not started"));
        }
        //活动是否已经开始
        if($issue_info['presell_end_time'] < time()){
            $this->error(__("The event has ended"));
        }
        //当期活动是否在进行中
        if($issue_info['status'] != 0){
            $this->error(__("Activity not started"));
        }
        $finance_id = $issue_info['finance_id'];
        //理财活动data
        $finance_info = (new ModelFinance())->detail($finance_id);
        if(!$finance_info){
            $this->error(__("Activities that don't exist"));
        }
        //是否上架，状态是否正常
        if($finance_info['status'] == 0){
            $this->error(__('Unlisted activity'));
        }
        //最小购买数量限制
        if($num < $finance_info['user_min_buy']){
            $this->error(__('Minimum purchase quantity ').$finance_info['user_min_buy']);
        }
        //最大购买数量限制
        $buy_number = (new ModelFinanceorder())->where('user_id',$this->uid)->where('finance_id',$finance_id)->sum('buy_number');
        if($num+$buy_number > $finance_info['user_max_buy']){
            $this->error(__('Maximum purchase quantity').$finance_info['user_max_buy']);
        }
        //购买金额
        $finance_price = bcmul($finance_info['price'], $num, 2);
        //判断余额
        if ($finance_price > $userinfo['money']) {
            // $data['code'] = 10;
            $this->error(__('Your balance is not enough'));
        }
        $addorder = (new ModelFinanceorder())->addorder($post, $userinfo, $finance_price, $finance_info, $issue_info);
        if (!$addorder) {
            $this->error(__('order failed'));
        }
        $this->success(__('order successfully'),$addorder);
    }

    /**
     * 我的理财
     *
     * @ApiMethod (POST)
     * @param string $type 订单状态:0=预售中,1=已完成,2=计息中
     * @param string $page 当前页
     */
    public function orderlist()
    {
        $this->verifyUser();
        $post = $this->request->post();
        $page = $this->request->post("page");
        if (!$page) {
            $this->error(__('parameter error'));
        }
        $orderlist = (new ModelFinanceorder())->orderlist($post, $this->uid);
        $this->success(__('order successfully'), $orderlist);
    }

    public function statistics(){
        $this->verifyUser();
        //持有理财金额
        // $res['my_finance'] = (new ModelFinanceorder())->where('user_id',$this->uid)->where('state','in',[0,2])->
        $res['my_finance'] = 0;
        $res['profit'] = 0;
        $this->success(__('order successfully'), $res);
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
}
