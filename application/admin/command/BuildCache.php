<?php

namespace app\admin\command;

use app\admin\model\activity\Activity;
use app\admin\model\activity\ActivityPrize;
use app\admin\model\activity\ActivityTask;
use app\admin\model\activity\CashActivity;
use app\admin\model\activity\Popups;
use app\admin\model\activity\VipVoucher;
use app\admin\model\AuthRule;
use app\admin\model\financebuy\Finance;
use app\admin\model\financebuy\FinanceRate;
use app\admin\model\Goodtypes;
use app\admin\model\groupbuy\Goods;
use app\admin\model\groupbuy\GoodsCategory;
use app\admin\model\sys\AppVersion;
use app\admin\model\sys\HighEarning;
use app\admin\model\sys\Recommend;
use app\admin\model\userlevel\UserLevel;
use app\api\model\Usertotal;
use ReflectionClass;
use ReflectionMethod;
use think\Cache;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\Db;
use think\Exception;
use think\Loader;

class BuildCache extends Command
{
    protected $model = null;

    protected function configure()
    {
        $this->setName('BuildCache')
            ->setDescription('重建缓存（等级、商品、商品区）');
        //要执行的controller必须一样，不适用模糊查询
    }

    protected function execute(Input $input, Output $output)
    {
        // (new Usertotal())->setLogin(70);
        // echo (new Usertotal())->getLoginCount();

        // return ;
        set_time_limit(0);
        // $this->buildLevelCache();
        $this->buildGoodsCategoryCache();
        $this->buildGoodsCache();
        // $this->buildPopupCache();
        // $this->buildActivityCache();
        // $this->buildVipVoucherCache();
        // $this->buildActivityPrizeCache();
        // $this->buildActivityTaskCache();
        // $this->buildCashActivityCache();
        // $this->buildFinanceCache();
        // $this->buildHighEarningCache();
        // $this->buildAppVersionCache();
        // $this->buildRecommendCache();
    }

    protected function buildLevelCache()
    {
        $list = (new UserLevel())->where(['status' => 1, 'deletetime' => null])->select();
        if (empty($list)) {
            return false;
        }
        foreach ($list as $item) {
            $item = $item->toArray();
            (new UserLevel())->setUserLevelCache(intval($item['id']), intval($item['level']), $item);
        }
    }

    protected function buildGoodsCategoryCache()
    {
        $list = Db::table('fa_goods_types')->select();
        if (empty($list)) {
            return false;
        }
        foreach ($list as $item) {
            // $item = $item->toArray();           
            if ((isset($item['deletetime']) && $item['deletetime'])) {
                (new Goodtypes())->setSortedSetCache($item['id'], [], 0, 0, true);
            } else {
                (new Goodtypes())->setLevelCache($item['id'], $item);
                (new Goodtypes())->setSortedSetCache($item['id'], $item, 0, $item['weigh']);
            }
        }
    }

    protected function buildGoodsCache()
    {
        // $list = (new Goods())->where(['id' => ['GT', 0]])->select();
        $list =  Db::table('fa_goods')->where(['id' => ['GT', 0]])->select();
        if (empty($list)) {
            return false;
        }
        foreach ($list as $item) {
            // $item = $item->toArray();
            (new Goods())->setLevelCacheIncludeDel($item['id'], $item);
            if ($item['status']) (new Goods())->setSortedSetCache($item['id'], $item, $item['category_id'], $item['weigh']);
            (new Goods())->setRecommendSortedSetCache($item['id'], $item,  $item['category_id'], $item['weigh']);
            (new Goods())->setSortedSetCache($item['id'], $item, 0, $item['weigh']);
        }
    }

    protected function buildPopupCache()
    {
        $list = (new Popups())->where(['status' => 1, 'deletetime' => null])->select();
        if (empty($list)) {
            return false;
        }
        foreach ($list as $item) {
            $item = $item->toArray();
            (new Popups())->setLevelCache($item['id'], $item);
            if (!$item['is_login']) {
                (new Popups())->setSortedSetCache($item['id'], $item, 0, $item['id']);
            }
            (new Popups())->setSortedSetCache($item['id'], $item, 1, $item['id']);
        }
    }

    protected function buildActivityCache()
    {
        $list =  Db::table('fa_activity')->where(['id' => ['GT', 0]])->select();
        if (empty($list)) {
            return false;
        }
        foreach ($list as $item) {
            // $item = $item->toArray();
            (new Activity())->setLevelCacheIncludeDel($item['id'], $item);
            (new Activity())->setSortedSetCache($item['id'], $item, 0, $item['weigh']);
        }
    }

    protected function buildVipVoucherCache()
    {
        $list =  Db::table('fa_vip_voucher')->where(['id' => ['GT', 0]])->select();
        if (empty($list)) {
            return false;
        }
        foreach ($list as $item) {
            // $item = $item->toArray();
            (new VipVoucher())->setLevelCacheIncludeDel($item['id'], $item);
            (new VipVoucher())->setSortedSetCache($item['id'], $item, 0, $item['level']);
        }
    }

    protected function buildActivityPrizeCache()
    {
        $list =  Db::table('fa_activity_prize')->where(['id' => ['GT', 0]])->select();
        if (empty($list)) {
            return false;
        }
        foreach ($list as $item) {
            // $item = $item->toArray();
            (new ActivityPrize())->setLevelCacheIncludeDel($item['id'], $item);
        }
    }

    protected function buildActivityTaskCache()
    {
        $list =  Db::table('fa_activity_task')->where(['id' => ['GT', 0]])->select();
        if (empty($list)) {
            return false;
        }
        foreach ($list as $item) {
            // $item = $item->toArray();
            (new ActivityTask())->setLevelCacheIncludeDel($item['id'], $item);
        }
    }

    protected function buildCashActivityCache()
    {
        $list = Db::table('fa_cash_activity')->where(['status' => 1])->select();
        if (empty($list)) {
            return false;
        }
        foreach ($list as $item) {
            // $item = $item->toArray();           
            if ((isset($item['status']) && !$item['status']) || (isset($item['deletetime']) && $item['deletetime'])) {
                (new CashActivity())->setSortedSetCache($item['id'], [], 0, 0, true);
            } else {
                (new CashActivity())->setLevelCacheIncludeDel($item['id'], $item);
                (new CashActivity())->setSortedSetCache($item['id'], $item, 0, $item['weigh']);
            }
        }
    }

    protected function buildFinanceCache()
    {
        $list = Db::table('fa_finance')->where(['status' => 1])->select();
        if (empty($list)) {
            return false;
        }
        foreach ($list as $item) {
            // $item = $item->toArray();           
            if ((isset($item['status']) && !$item['status']) || (isset($item['deletetime']) && $item['deletetime'])) {
                (new Finance())->setSortedSetCache($item['id'], [], 0, 0, true);
            } else {
                (new Finance())->setLevelCacheIncludeDel($item['id'], $item);
                (new Finance())->setSortedSetCache($item['id'], $item, 0, $item['weigh']);
            }
            $this->buildFinanceRateCache($item['id']);
        }
    }

    protected function buildRecommendCache()
    {
        $list = Db::table('fa_recommend')->select();
        if (empty($list)) {
            return false;
        }
        foreach ($list as $item) {
            if ((isset($item['status']) && !$item['status']) || (isset($item['deletetime']) && $item['deletetime'])) {
                (new Recommend())->setSortedSetCache($item['id'], [], 0, 0, true);
            } else {
                (new Recommend())->setLevelCacheIncludeDel($item['id'], $item);
                (new Recommend())->setSortedSetCache($item['id'], $item, 0, $item['weigh']);
            }
            $this->buildFinanceRateCache($item['id']);
        }
    }

    protected function buildFinanceRateCache($finance_id)
    {
        $list = Db::table('fa_finance_rate')->where(['finance_id' => $finance_id])->select();
        if (empty($list)) {
            return false;
        }
        foreach ($list as $item) {
            // $item = $item->toArray();           
            if ((isset($item['status']) && !$item['status']) || (isset($item['deletetime']) && $item['deletetime'])) {
                (new FinanceRate())->setSortedSetCache($item['id'], [], 0, 0, true);
            } else {
                (new FinanceRate())->setLevelCache($item['id'], $item);
                $end = intval($item['end']);
                if (!$item['end']) {
                    $end = 10000000;
                }
                (new FinanceRate())->setSortedSetCache($item['id'], $item, 0, $end);
            }
        }
    }

    protected function buildHighEarningCache()
    {
        $list = (new HighEarning())->where(['status' => 1, 'deletetime' => null])->select();
        if (empty($list)) {
            return false;
        }
        foreach ($list as $item) {
            $item = $item->toArray();
            (new HighEarning())->setUserLevelCache(intval($item['id']), intval($item['level']), $item);
            (new HighEarning())->setSortedSetCache(intval($item['level']), $item, 0, $item['level']);
        }
    }

    public function buildAppVersionCache()
    {
        (new AppVersion())->setCurrentVersion();
    }
}
