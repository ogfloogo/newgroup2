<?php

namespace app\api\controller;

use app\api\model\Goods as ModelGoods;
use app\api\model\Goodscategory;
use app\api\model\Level;
use app\api\model\Order as ModelOrder;
use app\api\model\Usercategory;
use app\api\model\Usermoneylog;
use app\api\model\Userrobot;
use app\common\model\User;
use think\cache\driver\Redis;
use think\Config;
use think\helper\Time;
use think\Log;

/**
 * 商品
 */
class Goods extends Controller
{
    /**
     *商品分类
     *
     * @ApiMethod (POST)
     */
    public function goodscategory()
    {
        $categoryList = (new Goodscategory())->getcategoryList();
        $this->success(__('The request is successful'), $categoryList);
    }

    /**
     * 分类-商品列表
     * 
     */
    public function goodslist()
    {
        $id = $this->request->post('id'); //ID
        $goodslist = (new ModelGoods())->getgoodslist($id);
        $this->success(__('The request is successful'), $goodslist);
    }

    /**
     * 商品详情
     * 
     */
    public function goodsdetail()
    {
        $this->verifyUser();
        $id = $this->request->post('id'); //商品ID
        $goodslist = (new ModelGoods())->goodsdetail($id);
        $this->success(__('The request is successful'), $goodslist);
    }

    /**
     * 商品详情-更多参数
     */
    public function goodsdetailparam()
    {
        $this->verifyUser();
        $id = $this->request->post('id'); //商品区ID
        $userinfo = $this->userInfo;
        (new Usercategory())->check($this->uid);
        $user_today_info = (new Usercategory())->where('user_id', $userinfo['id'])->where('date', date('Y-m-d', time()))->field('num,group_buying_commission,head_of_the_reward')->find();
        $goodslist = (new ModelGoods())->goodsdetailparam($id, $userinfo, $user_today_info);
        $time = Time::today();
        $goodslist['leader_bonus'] = bcmul($goodslist['reward'], Config::get("site.group_head_reward") / 100, 2);
        $order_num = (new ModelOrder())->where('user_id', $userinfo['id'])->where('type', 1)->where('createtime', 'between', [$time[0], $time[1]])->count();
        $goodslist['my_group_buying_num'] = $order_num;
        $this->success(__('The request is successful'), $goodslist);
    }

    /**
     * 网银管理
     */
    public function internetbank()
    {
        $type = $this->request->param('type', 1);
        $this->verifyUser();
        $redis = new Redis();
        $list = $redis->handler()->get("newgroup:internetbank:list");
        if ($list) {
            $list = json_decode($list, true);
            switch ($type) {
                case 1:
                    $is_add = db('user_info')->where(['user_id' => $this->uid, 'status' => 1])->order('id asc')->find();
                    foreach ($list as $key => $value) {
                        $list[$key]['image'] = format_image($value['image']);
                        $list[$key]['path'] = format_images($value['path']);
                        if (!empty($is_add)) {
                            if ($is_add['bank_name'] == $value['name']) {
                                $list[$key]['is_add'] = 1;
                            } else {
                                $list[$key]['is_add'] = 0;
                            }
                        }
                    }
                    break;
                case 2:
                    foreach ($list as $key => $value) {
                        $list[$key]['image'] = format_image($value['image']);
                        $list[$key]['path'] = format_images($value['path']);
                        $is_add = db('user_info')->where(['user_id' => $this->uid,'bank_name'=>$value['name'], 'status' => 1, 'type' => $type])->order('id asc')->find();
                        if (!empty($is_add)) {
                            $list[$key]['is_add'] = 1;
                        } else {
                            $list[$key]['is_add'] = 0;
                        }
                    }
                    break;
                case 3:
                    foreach ($list as $key => $value) {
                        $list[$key]['image'] = format_image($value['image']);
                        $list[$key]['path'] = format_images($value['path']);
                        $is_add = db('user_info')->where(['user_id' => $this->uid, 'status' => 1])->order('id asc')->find();
                        if (!empty($is_add)) {
                            $list[$key]['is_add'] = 1;
                        } else {
                            $list[$key]['is_add'] = 0;
                        }
                    }
                    break;
                default:
                    # code...
                    break;
            }
        } else {
            $list = [];
        }
        $this->success(__('The request is successful'), $list);
    }

    /**
     * 首页商品
     * 
     */
    public function homepagegoods()
    {
        $checklogin = $this->getCacheUser();
        if ($checklogin) {
            $homepagegoods = (new ModelGoods())->homepagegoods($checklogin);
        } else {
            $homepagegoods = (new ModelGoods())->homepagegoodsno();
        }
        $this->success(__('The request is successful'), $homepagegoods);
    }

    /**
     * 新人限时福利商品专区
     */
    public function peopleofanewtype()
    {
        $goodslist = (new ModelGoods())->goodslist(3);
        $this->success(__('The request is successful'), $goodslist);
    }

    /**
     * 秒杀商品专区
     */
    public function seckill()
    {
        $goodslist = (new ModelGoods())->goodslist(2);
        $this->success(__('The request is successful'), $goodslist);
    }

    /**
     * 首页商品区
     * 
     */
    // public function homepagegoodsold()
    // {
    //     $type = $this->request->post('type');//0=lv1-lv4，1=vip1-vip8
    //     $homepagegoods = (new ModelGoods())->homepagegoods();
    //     $return = $homepagegoods;
    //     $edit = array_column($return, 'price');
    //     array_multisort($edit, SORT_ASC, $return);
    //     $this->success(__('The request is successful'), $return);
    // }

    /**
     * 首页推荐商品区
     */
    public function recommend()
    {
        $checklogin = $this->getCacheUser();
        $model = new ModelGoods();
        $return = $model->recommends();
        if ($checklogin) {
            $return = $model->recommend($checklogin);
        }
        $this->success(__('The request is successful'), $return);
    }

    /**
     * 团购推荐
     */
    public function buyerrecommend()
    {
        //拼团人数
        $min = 300;
        $max = 350;
        $number = mt_rand($min, $max);
        //列表
        $data = [];
        for ($i = 0; $i < 2; $i++) {
            //随机头像
            $start = 3;
            $end = 222;
            $pic = mt_rand($start, $end);
            //随机电话号段
            //$my_array = array("6","7","8","9");
            $my_array = (new Userrobot())->getname();
            $length = count($my_array) - 1;
            $hd = rand(0, $length);
            $begin = $my_array[$hd];
            $a = rand(10, 99);
            $b = rand(100, 999);
            $avatar = '/client/static/img/avatar.5ff7027a.png';
            // $data[$i]['nickname'] = $begin.$a.'****'.$b;
            $data[$i]['nickname'] = $begin;
            $data[$i]['avatar'] = format_image("/uploads/robotpic/" . $pic . ".jpg");
            $start_time = time() + 60 * 60;
            $end_time = time() + 60 * 60 * 24;
            $data[$i]['end_time'] = mt_rand($start_time, $end_time);
        }
        $res['number'] = $number;
        $res['list'] = $data;
        $this->success(__('The request is successful'), $res);
    }

    /**
     * 团购列表
     */
    public function buyerlist()
    {
        //列表
        $data = [];
        for ($i = 3; $i < 23; $i++) {
            //随机电话号段
            $my_array = (new Userrobot())->getname();
            $length = count($my_array) - 1;
            $hd = rand(0, $length);
            $begin = $my_array[$hd];
            $a = rand(10, 99);
            $b = rand(100, 999);
            $avatar = '/client/static/img/avatar.5ff7027a.png';
            $data[$i]['nickname'] = $begin;
            $data[$i]['avatar'] = format_image("/uploads/robotpic/" . $i . ".jpg");
            $start_time = time() + 60 * 60;
            $end_time = time() + 60 * 60 * 24;
            $data[$i]['end_time'] = mt_rand($start_time, $end_time);
        }
        $data2 = [];
        for ($i = 3; $i < 23; $i++) {
            //随机电话号段
            $my_array = array("010", "012", "013", "014", "016", "017", "018");
            $length = count($my_array) - 1;
            $hd = rand(0, $length);
            $begin = $my_array[$hd];
            $a = rand(10, 99);
            $b = rand(100, 999);
            $avatar = '/client/static/img/avatar.5ff7027a.png';
            $data2[$i]['nickname'] = $begin . '****' . $b;
            $data2[$i]['avatar'] = format_image($avatar);
            $start_time = time() + 60 * 60;
            $end_time = time() + 60 * 60 * 24;
            $data2[$i]['end_time'] = mt_rand($start_time, $end_time);
        }
        $all = array_merge($data, $data2);
        shuffle($all);
        // $return = shuffle_assoc($all);
        $this->success(__('The request is successful'), $all);
    }
}
