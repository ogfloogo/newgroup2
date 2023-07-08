<?php

namespace app\api\command;

use app\api\model\Financeissue;
use app\api\model\Financeorder;
use think\Cache;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;


class Orderstate extends Command
{
    protected $model = null;

    protected function configure()
    {
        // 指令配置
        $this->setName('Orderstate')
            ->setDescription('更新当前期订单状态');
    }

    protected function execute(Input $input, Output $output)
    {
        $Orderstate = (new Financeissue())->where('presell_end_time', 'lt', time())->where('status', 1)->select();
        if(!empty($Orderstate)){
            foreach($Orderstate as $key=>$value){
                (new Financeorder())->where('issue_id',$value['id'])->where('state',0)->update(['state'=>1]);
            }
        }
        echo "------------";
        echo "执行成功" . "\n";
    }
}
