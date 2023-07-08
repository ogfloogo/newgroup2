<?php

namespace app\api\controller;

use app\admin\model\agent\AgentPopups;
use app\api\model\Agent;
use app\api\model\AgentPopups as ModelAgentPopups;
use app\api\model\Appversion;
use app\api\model\Dayreward;
use app\api\model\Popups;
use app\api\model\Recommend;
use app\api\model\Userrecharge;
use think\cache\driver\Redis;
use think\Config;
use think\Log;

/**
 * 系统配置
 */
class Setting extends Controller
{

    /**
     * 配置列表
     */
    public function list()
    {
        $list = Config::get('site');
        $fields = ['user_protocol', 'quick_guide', 'private_policy', 'level_rule', 'team_rule', 'invite_rule', 'hiearning_url', 'pingtai_picture', 'protocol', 'privilege', 'finance_rule'];
        foreach ($fields as $field) {
            $newList[$field] = $list[$field];
        }
        $newList['cash_rule'] =  Config::get("site.cash_rule");

        $this->success(__('The request is successful'), $newList);
    }

    /**
     * 用户协议
     */
    public function userprotocol()
    {
        $list = Config::get("site.user_protocol");
        $this->success(__('The request is successful'), $list);
    }

    /**
     * 充值面额
     */
    public function amountlist()
    {
        $list = explode(',', Config::get("site.pay_amount"));
        $return = [];
        foreach ($list as $key => $value) {
            $res = explode('-', $value);
            $return[] = $res[0];
        }
        $this->success(__('The request is successful'), $return);
    }

    /**
     * 充值面额
     */
    public function amountlistnew()
    {
        $list = explode(',', Config::get("site.pay_amount"));
        $return = [];
        foreach ($list as $key => $value) {
            $res = explode('-', $value);
            $return[$key]['price'] = $res[0];
            $return[$key]['rate'] = $res[1];
            $return[$key]['givemoney'] = bcmul($res[0], $res[1] / 100, 2);
        }
        $this->success(__('The request is successful'), $return);
    }

    /**
     * 统一配置-tablist
     */
    public function systemlist()
    {
        $checklogin = $this->getCacheUser();
        $language = $this->language;
        if($language == "english"){
            $list['tablist'][] = [
                "pagePath" => "/pages/home/home",
                "iconPath" => format_images("/static/image/tabbar/home-inactive.png"),
                "selectedIconPath" => format_images("/static/image/tabbar/home-active.png"),
                "text" => "Home",
            ];
            $list['tablist'][] = [
                "pagePath" => "/pages/home/benefit",
                "iconPath" => format_images("/static/image/tabbar/benefit-inactive.png"),
                "selectedIconPath" => format_images("/static/image/tabbar/benefit-active.png"),
                "text" => "Benefits",
            ];
             $list['tablist'][] = [
                 "pagePath" => "/pages/team/team",
                 "iconPath" => format_images("/static/image/tabbar/team-inactive.png"),
                 "selectedIconPath" => format_images("/static/image/tabbar/team-active.png"),
                 "text" => "Team",
             ];
            $list['tablist'][] = [
                "pagePath" => "/pages/wode/wode",
                "iconPath" => format_images("/static/image/tabbar/my-inactive.png"),
                "selectedIconPath" => format_images("/static/image/tabbar/my-active.png"),
                "text" => "Account",
            ];
        }elseif($language == "ma"){
            $list['tablist'][] = [
                "pagePath" => "/pages/home/home",
                "iconPath" => format_images("/static/image/tabbar/home-inactive.png"),
                "selectedIconPath" => format_images("/static/image/tabbar/home-active.png"),
                "text" => "Rumah",
            ];
            $list['tablist'][] = [
                "pagePath" => "/pages/home/benefit",
                "iconPath" => format_images("/static/image/tabbar/benefit-inactive.png"),
                "selectedIconPath" => format_images("/static/image/tabbar/benefit-active.png"),
                "text" => "Faedah",
            ];
             $list['tablist'][] = [
                 "pagePath" => "/pages/team/team",
                 "iconPath" => format_images("/static/image/tabbar/team-inactive.png"),
                 "selectedIconPath" => format_images("/static/image/tabbar/team-active.png"),
                 "text" => "pasukan",
             ];
            $list['tablist'][] = [
                "pagePath" => "/pages/wode/wode",
                "iconPath" => format_images("/static/image/tabbar/my-inactive.png"),
                "selectedIconPath" => format_images("/static/image/tabbar/my-active.png"),
                "text" => "akaun",
            ];
        }elseif($language == "zh"){
            $list['tablist'][] = [
                "pagePath" => "/pages/home/home",
                "iconPath" => format_images("/static/image/tabbar/home-inactive.png"),
                "selectedIconPath" => format_images("/static/image/tabbar/home-active.png"),
                "text" => "首页",
            ];
            $list['tablist'][] = [
                "pagePath" => "/pages/home/benefit",
                "iconPath" => format_images("/static/image/tabbar/benefit-inactive.png"),
                "selectedIconPath" => format_images("/static/image/tabbar/benefit-active.png"),
                "text" => "福利",
            ];
             $list['tablist'][] = [
                 "pagePath" => "/pages/team/team",
                 "iconPath" => format_images("/static/image/tabbar/team-inactive.png"),
                 "selectedIconPath" => format_images("/static/image/tabbar/team-active.png"),
                 "text" => "团队",
             ];
            $list['tablist'][] = [
                "pagePath" => "/pages/wode/wode",
                "iconPath" => format_images("/static/image/tabbar/my-inactive.png"),
                "selectedIconPath" => format_images("/static/image/tabbar/my-active.png"),
                "text" => "我的",
            ];
        }

        // if ($checklogin) {
        //     //悬浮窗
        //     $windows = json_decode(Config::get("site.windows"), true);
        //     foreach ($windows as $key => $value) {
        //         $windows[$key]['image'] = format_image($value['image']);
        //     }
        //     $list['windows'] = $windows;
        // }
        $windows = json_decode(Config::get("site.windows"), true);
        foreach ($windows as $key => $value) {
            $windows[$key]['image'] = format_image($value['image']);
        }
        $list['windows'] = $windows;
        //客服链接
        $list['service_url'] = Config::get("site.service_url");
        //邀请奖励
        $list['invite_reward'] = Config::get("site.invite_reward");
        //邀请奖励比例
        $list['invite_rate'] = Config::get("site.invite_rate");
        //提现掉级弹窗提示	
        $list['cash_remark'] = Config::get("site.cash_remark");
        //提现密码设置弹窗提示	
        $list['cash_pass'] = Config::get("site.cash_pass");
        $is_login = 0;
        if ($checklogin) {
            $list['invite_url'] = format_images('/#/pages/login/login?currentIndex=register&invite_code=' . $checklogin['invite_code']);
            $is_login = 1;
            if ($checklogin['agent_id']) {
                $agentInfo = (new Agent())->getInfoById($checklogin['agent_id']);
                if ($agentInfo) {
                    $agent_code = $agentInfo['code'];
                    if ($agent_code) {
                        $list['invite_url'] .= '&agent=' . $agent_code;
                    }
                    if ($agentInfo['windows']) {
                        $agent_windows = json_decode($agentInfo['windows'], true);
                        foreach ($agent_windows as $key => $value) {
                            $agent_windows[$key]['image'] = format_image($value['image']);
                            $list['windows'] = $agent_windows;
                        }
                        $list['windows'] = $windows;
                    }
                    if ($agentInfo['service_url']) {
                        //客服链接
                        $list['service_url'] = $agentInfo['service_url'];
                    }
                }
            }
        } else {
            $agent_code_from_header = $this->request->header('agent', '');
            if ($agent_code_from_header) {
                $agent_id = (new Agent())->getIdByCode($agent_code_from_header);
                if ($agent_id) {
                    $agentInfo = (new Agent())->getInfoById($agent_id);
                    if ($agentInfo) {
                        if ($agentInfo['windows']) {
                            $agent_windows = json_decode($agentInfo['windows'], true);
                            foreach ($agent_windows as $key => $value) {
                                $agent_windows[$key]['image'] = format_image($value['image']);
                                $list['windows'] = $agent_windows;
                            }
                            $list['windows'] = $windows;
                        }
                        if ($agentInfo['service_url']) {
                            //客服链接
                            $list['service_url'] = $agentInfo['service_url'];
                        }
                    }
                }
            } else {
                if (Config::get("host.auto_assign_agent")) {
                    // $agent_id = (new Agent())->getAssignAgentId();
                    $list['windows'] = '';
                    $list['service_url'] = '';
                }
            }
        }


        //弹窗
        $list['popup_list'] = (new Popups())->getList($is_login);
        if ($checklogin) {
            if ($checklogin['agent_id']) {
                $agentPopList = (new ModelAgentPopups())->getListFromDb($checklogin['agent_id']);
                if (count($agentPopList)) {
                    $list['popup_list'] = $agentPopList;
                }
            }
        } else {
            $agent_code_from_header = $this->request->header('agent', '');
            if ($agent_code_from_header) {
                $agent_id = (new Agent())->getIdByCode($agent_code_from_header);
                if ($agent_id) {
                    $agentPopList = (new ModelAgentPopups())->getListFromDb($agent_id);
                    if (count($agentPopList)) {
                        $list['popup_list'] = $agentPopList;
                    }
                }
            } else {
            }
        }


        //快速入门
        $list['quick_guide'] = Config::get("site.quick_guide");
        //hiearning_url
        $list['hiearning_url'] = Config::get("site.hiearning_url");
        $list['android_url'] =  Config::get("site.android_url");
        $list['new_user_pic1'] =  format_image(Config::get("site.new_user_pic1"));
        $list['new_user_pic2'] =  format_image(Config::get("site.new_user_pic2"));
        //首页弹窗
        $system_popover = json_decode(Config::get('site.system_popover'), true);
        foreach ($system_popover as $key => $value) {
            $system_popover[$key]['image'] = format_image($value['image']);
        }
        $list['system_popover'] = $system_popover;

        $list['is_audited'] = Config::get("site.is_audited");
        $list['withdraw_desc'] = Config::get("site.withdraw_desc");

        $list['cash_home_pic'] =  Config::get("site.cash_home_pic");
        $list['cash_rule'] =  Config::get("site.cash_rule");


        //APP版本
        $redis = new Redis();
        $app_version = $redis->handler()->Hgetall('newgroup:max_version_info');
        if ($app_version) {
            $app_version['update_url_and_file'] = strstr($app_version['update_url_and_file'], 'http') ? $app_version['update_url_and_file'] : format_image($app_version['update_url_and_file']);
            $app_version['update_url_wgt_file'] = strstr($app_version['update_url_wgt_file'], 'http') ? $app_version['update_url_wgt_file'] : format_image($app_version['update_url_wgt_file']);
        }

        $list['app_version_1'] = $app_version;

        $count_key = 'update_count';
        $num = $redis->handler()->get($count_key);
        if (intval($num) >= 3) {
            // $app_version = [];
        } else {
            $redis->handler()->incr($count_key);
            $redis->handler()->expire($count_key, 30);
        }

        // $app_version['update_url_wgt_file'] = "https://vgroup.oss-ap-southeast-6.aliyuncs.com/vgroup115.wgt";
        $list['app_version'] = $app_version;
        $list['experience_day'] = 3;
        $list['user_register_reward'] = Config::get('site.user_register_reward');
        //邀请奖励比例
        $list['big_money'] = bcmul(Config::get("site.invite_rate") / 100, 100000, 2);
        //about us
        $list['about_online_service'] = explode(',', Config::get('site.about_online_service'));
        $list['about_email'] = Config::get('site.about_email');
        $list['h5_url']  = Config::get('site.h5_url');

        $recommend = (new Recommend())->getRecommendList($language);
        $list['recommend'] = $recommend;
        //签到奖励金额
        $list['normal_signin'] = Config::get("site.normal_signin");
        //弹窗绑卡奖励金额
        $list['binding_card_reward'] = (new Dayreward())->where(['type'=>1])->value('reward');
        $this->success(__('The request is successful'), $list);
    }

    /**
     * 版本
     */
    public function version()
    {
        $res = (new Appversion())->where('status', 1)->find();
        $res['update_url_and_file'] = format_image($res['update_url_and_file']);
        $res['update_url_wgt_file'] = format_image($res['update_url_wgt_file']);
        $this->success(__('The request is successful'), $res);
    }

    /**
     * af
     */
    public function getaflist()
    {
        $this->verifyUser();
        $list = (new Userrecharge())
            ->where('user_id', $this->uid)
            ->where('is_af', 0)
            ->where('status', 1)
            ->field('id,order_id,order_num,price,paytime')
            ->limit(10)
            ->select();
        // if ($list) {
        //     foreach ($list as $key => $value) {
        //         $userrecharge->where('id', $value['id'])->update(['is_af' => 1]);
        //     }
        // }
        $this->success(__('The request is successful'), $list);
    }

    /**
     * af
     */
    public function setaf()
    {
        $ids = $this->request->post('ids');
        (new Userrecharge())->where('id', 'in', $ids)->update(['is_af' => 1]);
        $this->success(__('The request is successful'));
    }
}
