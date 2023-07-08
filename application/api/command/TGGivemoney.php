<?php

namespace app\api\command;

use app\api\model\Financeissue;
use app\api\model\Financeorder;
use think\cache\driver\Redis;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\api\model\User;
use app\api\model\Usermoneylog;
use think\Config;
use think\Db;
use think\Log;
use think\Exception;


class TGGivemoney extends Command
{
    protected $model = null;

    protected function configure()
    {
        // 指令配置
        $this->setName('TGGivemoney')
            ->setDescription('理财到期');
    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
        $output->writeln('TGGivemoney');
        $ws = new \Swoole\WebSocket\Server('0.0.0.0', 9612);
        //守护进程模式
        $ws->set([
            'daemonize' => true,
            'worker_num' => 1,
            // 'task_worker_num' => 4,
        ]);
        //监听WebSocket连接打开事件
        $ws->on('Open', function ($ws, $request) {
            $ws->push($request->fd, "hello, welcome\n");
        });

        //监听WebSocket消息事件
        $ws->on('Message', function ($ws, $frame) {
            echo "Message: {$frame->data}\n";
            $ws->push($frame->fd, "server: {$frame->data}");
        });
        $ws->on('WorkerStart', function ($ws, $worker_id) {
            echo "workerId:{$worker_id}\n";
            $redis = new Redis();
            $user = new User();
            $usermoneylog = new Usermoneylog();
            \Swoole\Timer::tick(1500, function () use ($redis, $user, $usermoneylog, $worker_id) {
                $redis->handler()->select(6);
                $list = $redis->handler()->ZRANGEBYSCORE('newgroup:financelist', '-inf', time(), ['withscores' => true]);
                foreach ($list as $key => $value) {
                    $lock = $redis->handler()->setnx("newgroup:financelock" . $key, $key);
                    if (!$lock) {
                        continue;
                    }
                    $redis->handler()->expireAt("newgroup:financelock" . $key, time() + 10);
                    //理财订单是否存在
                    $order_info = (new Financeorder())->where('id',$key)->find();
                    if(!$order_info){
                        Log::mylog('理财订单不存在', $list, 'Givemoney');
                        $redis->handler()->del("newgroup:financelock" . $key);
                        $redis->handler()->zRem('newgroup:financelist', $key);
                        continue;
                    }
                    $user_id = $order_info['user_id'];
                    //理财订单是否已完成
                    if($order_info['state'] == 2){
                        Log::mylog('订单已发放', $list, 'Givemoney');
                        $redis->handler()->del("newgroup:financelock" . $key);
                        $redis->handler()->zRem('newgroup:financelist', $key);
                        continue;
                    }
                    //获取收益率
                    $rate = (new Financeorder())->getissuerate($order_info);
                    //总收益金额
                    $earnings = bcmul($order_info['amount'],$rate/100,2);
                    //发放总金额
                    $return_money = bcadd($order_info['amount'],$earnings,2);
                    try {
                        //余额变动
                        Db::startTrans();
                        $isok = $usermoneylog->moneyrecords($user_id, $return_money , 'inc', 19, "理财到期");
                        if ($isok == false) {
                            Db::rollback();
                            $redis->handler()->del("newgroup:financelock" . $key);
                            continue;
                        } else {
                            Db::commit();
                            //更新订单
                            (new Financeorder())->where('id',$key)->update([
                                "buy_rate_end" => $rate, //收益率
                                "earnings" => $earnings,//收益金额
                                "issued_time" => time(),//发放时间
                                "state" => 2
                            ]);
                            Log::mylog(intval($key).'-理财发放成功', $order_info, 'Givemoney');
                            $redis->handler()->zRem('newgroup:financelist', $key);
                            $redis->handler()->del("newgroup:financelock" . $key);
                        }
                    } catch (Exception $e) {
                        Db::rollback();
                        Log::mylog('理财到期', $e, 'Givemoney');
                    }
                }
            }, $ws);
        });
        //监听WebSocket连接关闭事件
        $ws->on('Close', function ($ws, $fd) {
            echo "client-{$fd} is closed\n";
        });
        $ws->start();
    }
}
