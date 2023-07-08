<?php

namespace app\api\model;

use app\admin\model\groupbuy\Goods as GroupbuyGoods;
use think\Model;
use think\cache\driver\Redis;
use think\Config;
use think\Db;
use think\Exception;
use think\helper\Time;
use think\Log;

/**
 * 商品
 */
class Goods extends Model
{
    protected $name = 'goods';

    /**
     * 商品列表
     */
    public function getgoodslist($id)
    {
        $category_id_key = "newgroup:good:set:" . $id;
        $redis = new Redis();
        $goodslist = $redis->handler()->ZRANGEBYSCORE($category_id_key, '-inf', '+inf', ['withscores' => true]);
        $res = [];
        foreach ($goodslist as $key => $value) {
            $res[] = $redis->handler()->hMget('newgroup:good:' . intval($key), ['id', 'name', 'category_id', 'cover_image', 'group_buy_num']);
        }
        foreach ($res as $k => $v) {
            $category_info = $redis->handler()->hMget('newgroup:category:' . $v['category_id'], ['price']);
            $res[$k]['price'] = $category_info['price'];
            $res[$k]['cover_image'] = format_image($v['cover_image']);
        }
        return $res;
    }

    /**
     * 商品详情
     */
    public function goodsdetail($id)
    {
        $detail = $this->detail($id);
        $detail['cover_image'] = format_image($detail['cover_image']);
        $banner_images = explode(',', $detail['banner_images']);
        $array = [];
        foreach ($banner_images as $key => $value) {
            $array[] = format_image($value);
        }
        $detail['banner_images'] = implode(',', $array);
        //商品价格
        $detail['buyback'] = bcsub($detail['buyback'], $detail['price'], 2);
        $daily_buy_num = Config::get("site.daily_buy_num");
        $detail['rewards'] = bcmul($daily_buy_num, $detail['reward'], 2);
        $detail['win_must_num'] = $detail['win_must_num'];
        $detail['detail_images'] = format_image($detail['detail_images']);
        return $detail;
    }

    /**
     * 商品详情
     */
    public function goodsdetails($id)
    {
        $detail = $this->details($id);
        $detail['cover_image'] = format_image($detail['cover_image']);
        return $detail;
    }

    public function goodsdetailparam($id, $userinfo, $user_today_info)
    {
        //每日开团次数
        $daily_buy_num = Config::get("site.daily_buy_num");
        //今日收入
        $today_income = bcadd($user_today_info['group_buying_commission'], $user_today_info['head_of_the_reward'], 2);
        //今日已团购次数
        $today_buy_num = $user_today_info['num'];
        //该商品区再拼几次必中商品
        $good_info = $this->detail($id);
        $user_category = (new Usermerchandise())->where('user_id', $userinfo['id'])->where('category_id', $id)->find();
        if (!$user_category) {
            $will_be_in = $good_info['win_must_num'];
        } else {
            $will_be_in = $good_info['win_must_num'] - $user_category['num'];
            if ($will_be_in < 0) {
                $will_be_in = $good_info['win_must_num'];
            }
        }
        //今日团队佣金
        $time = Time::today();
        $commission = new Commission();
        $commission->setTableName($userinfo['id']);
        $myteam_today_commission = $commission->where('to_id', $userinfo['id'])->where('level', 'in', '1,2,3')->where('createtime', 'between', [$time[0], $time[1]])->sum('commission');
        $mylevelrate = [
            [
                'name' => 'Level A',
                'rate' => Config::get("site.first"),
            ],
            [
                'name' => 'Level B',
                'rate' => Config::get("site.second"),
            ],
            [
                'name' => 'Level C',
                'rate' => Config::get("site.third"),
            ],
        ];
        return [
            'daily_buy_num' => $daily_buy_num, //每日开团次数
            'today_income' => $today_income, //今日收入
            'today_buy_num' => $today_buy_num, //今日已团购次数
            'will_be_in' => $will_be_in, //该商品区再拼几次必中商品
            'myteam_today_commission' => $myteam_today_commission, //今日团队佣金
            'reward' => $good_info['reward'],
            'mylevelrate' => $mylevelrate,
        ];
    }

    /**
     * 首页商品区
     */
    public function homepagegoodsold()
    {
        $goods_id_key =  "newgroup:good:set:rec";
        $redis = new Redis();
        $categorylist = $redis->handler()->ZRANGEBYSCORE('newgroup:category:set:0', '-inf', '+inf', ['withscores' => true]);
        $left = [];
        foreach ($categorylist as $k => $v) {
            $left[$k]['id'] = $k;
            $reward = $redis->handler()->hMget("newgroup:category:" . intval($k), ['reward', 'name', 'price', 'extra_reward_desc', 'level']);
            $left[$k]['name'] = $reward['name'];
            $left[$k]['price'] = $reward['price'];
            $left[$k]['extra_reward_desc'] = $reward['extra_reward_desc'];
            $left[$k]['level'] = $reward['level'];
            $levelinfo = (new Level())->mylevel_commission_rates($reward['level']);
            $left[$k]['level_name'] = $levelinfo['name'];
            //今日可赚金额
            $daily_buy_num = Config::get('site.daily_buy_num');
            $left[$k]['money'] = bcmul($daily_buy_num, $reward['reward'], 2);
        }
        foreach ($left as $ks => $vs) {
            $goods_id_key =  "newgroup:good:set:rec:" . $vs['id'];
            $goodids = $redis->handler()->ZRANGEBYSCORE($goods_id_key, '-inf', '+inf', ['withscores' => true]);
            $child = [];
            foreach ($goodids as $ksv => $vsv) {
                $child[] = $this->goodsdetail(intval($ksv));
            }
            $left[$ks]['list'] = $child;
        }
        return $left;
    }

    /**
     * 首页商品区 new
     */
    public function homepagegoods($userinfo)
    {
        $redis = new Redis();
        $categorylist = $redis->handler()->ZRANGEBYSCORE('newgroup:goodtypes:set:0', '-inf', '+inf', ['withscores' => true]);
        $left = [];
        foreach ($categorylist as $k => $v) {
            $left[$k]['id'] = $k;
            $reward = $redis->handler()->hMget("newgroup:goodtypes:" . intval($k), ['id', 'name', 'type']);
            $left[$k]['name'] = $reward['name'];
            $left[$k]['type'] = $reward['type'];
        }
        $return = [];
        foreach ($left as $ks => $vs) {
            $return[] = $vs;
        }
        foreach ($return as $ks => $vs) {
            if ($vs['type'] == 3) {
                //新人限时福利倒计时
                $validity_config = Config::get("site.validity");
                $validity = $userinfo['createtime'] + $validity_config * 60;
                if ($validity < time()) {
                    $return[$ks]['list'] = [];
                } else {
                    $goods_id_key =  "newgroup:good:set:rec:" . $vs['id'];
                    $goodids = $redis->handler()->ZRANGEBYSCORE($goods_id_key, '-inf', '+inf', ['withscores' => true]);
                    $child = [];
                    foreach ($goodids as $ksv => $vsv) {
                        $child[] = $this->goodsdetails(intval($ksv));
                    }
                    $return[$ks]['list'] = $child;
                }
            } else {
                $goods_id_key =  "newgroup:good:set:rec:" . $vs['id'];
                $goodids = $redis->handler()->ZRANGEBYSCORE($goods_id_key, '-inf', '+inf', ['withscores' => true]);
                $child = [];
                foreach ($goodids as $ksv => $vsv) {
                    $child[] = $this->goodsdetails(intval($ksv));
                }
                $return[$ks]['list'] = $child;
            }
        }
        return $return;
    }

    /**
     * 首页商品区 new
     */
    public function homepagegoodsno()
    {
        $redis = new Redis();
        $categorylist = $redis->handler()->ZRANGEBYSCORE('newgroup:goodtypes:set:0', '-inf', '+inf', ['withscores' => true]);
        $left = [];
        foreach ($categorylist as $k => $v) {
            $left[$k]['id'] = $k;
            $reward = $redis->handler()->hMget("newgroup:goodtypes:" . intval($k), ['id', 'name', 'type']);
            $left[$k]['name'] = $reward['name'];
            $left[$k]['type'] = $reward['type'];
        }
        $return = [];
        foreach ($left as $ks => $vs) {
            $return[] = $vs;
        }
        foreach ($return as $ks => $vs) {
            $goods_id_key =  "newgroup:good:set:rec:" . $vs['id'];
            $goodids = $redis->handler()->ZRANGEBYSCORE($goods_id_key, '-inf', '+inf', ['withscores' => true]);
            $child = [];
            foreach ($goodids as $ksv => $vsv) {
                $child[] = $this->goodsdetails(intval($ksv));
            }
            $return[$ks]['list'] = $child;
        }
        return $return;
    }

    /**
     * 商品专区
     */
    public function goodslist($id)
    {
        $redis = new Redis();
        $goods_id_key =  "newgroup:good:set:rec:" . $id;
        $goodids = $redis->handler()->ZRANGEBYSCORE($goods_id_key, '-inf', '+inf', ['withscores' => true]);
        $return = [];
        foreach ($goodids as $ksv => $vsv) {
            $return[] = $this->goodsdetails(intval($ksv));
        }
        return $return;
    }


    public function recommendlist()
    {
        $goods_id_key =  "newgroup:good:set:rec";
        $redis = new Redis();
        $categorylist = $redis->handler()->ZRANGEBYSCORE('newgroup:category:set:0', '-inf', '+inf', ['withscores' => true]);
        $left = [];
        foreach ($categorylist as $k => $v) {
            $left[$k]['id'] = $k;
            $reward = $redis->handler()->hMget("newgroup:category:" . intval($k), ['reward', 'name', 'price']);
            $left[$k]['name'] = $reward['name'];
            $left[$k]['price'] = $reward['price'];
            //今日可赚金额
            $daily_buy_num = Config::get('site.daily_buy_num');
            $left[$k]['money'] = bcmul($daily_buy_num, $reward['reward'], 2);
        }
        foreach ($left as $ks => $vs) {
            $goods_id_key =  "newgroup:good:set:rec:" . $vs['id'];
            $goodids = $redis->handler()->ZRANGEBYSCORE($goods_id_key, '-inf', '+inf', ['withscores' => true]);
            $child = [];
            foreach ($goodids as $ksv => $vsv) {
                $child[] = $this->goodsdetail(intval($ksv));
            }
            $left[$ks]['list'] = $child;
        }
        return $left;
    }
    /**
     * 首页商品推荐区
     */
    // public function recommend($userinfo)
    // {
    //     $list = $this->homepagegoods();
    //     $return = [];
    //     foreach($list as $value){
    //         $return[] = $value;
    //     }
    //     if($userinfo['level'] == 1){
    //         return $return[0];
    //     }
    //     $newlist = [];
    //     foreach ($return as $key => $value) {
    //         if ($userinfo['money'] >= $value['price']) {
    //             $newlist[] = $value;
    //         }
    //     }
    //     $edit = array_column($newlist, 'price');
    //     array_multisort($edit, SORT_DESC, $newlist);
    //     return $newlist[0];
    // }

    // public function recommends()
    // {
    //     $list = $this->homepagegoods();
    //     $return = [];
    //     foreach($list as $value){
    //         $return[] = $value;
    //     }
    //     return $return;
    // }
    public function details($good_id)
    {
        $redis = new Redis();
        $detail = $redis->handler()->hMget("newgroup:good:" . $good_id, ['id', 'name', 'category_id', 'price', 'cover_image','group_buy_num','win_people_num','cash_people_num','status','buyback','reward','original_price','prepaid_amount','limit','sales',]);
        return $detail;
    }

    public function detail($good_id)
    {
        $redis = new Redis();
        $detail = $redis->handler()->Hgetall("newgroup:good:" . $good_id);
        return $detail;
    }

    public function upd($id)
    {
        $item = db('goods')->where(['id'=>$id])->find();
        (new GroupbuyGoods())->setLevelCacheIncludeDel($item['id'], $item);
        if ($item['status']) (new GroupbuyGoods())->setSortedSetCache($item['id'], $item, $item['category_id'], $item['weigh']);
        (new GroupbuyGoods())->setRecommendSortedSetCache($item['id'], $item,  $item['category_id'], $item['weigh']);
        (new GroupbuyGoods())->setSortedSetCache($item['id'], $item, 0, $item['weigh']);
    }
}
