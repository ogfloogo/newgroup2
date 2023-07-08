<?php

namespace app\api\controller;

use app\api\model\Userteam;

/**
 * 团队
 */
class Team extends Controller
{

    /**
     *团队统计
     *
     */
    public function myteamtotal()
    {
        $this->verifyUser();
        $list = (new Userteam())->myteamtotal($this->uid,$this->userInfo);
        $this->success(__('The request is successful'), $list);
    }

    /**
     * 团队列表
     */
    public function myteamlist()
    {
        $this->verifyUser();
        $post = $this->request->post();
        $page = $this->request->post('page'); //ID
        if (!$page) {
            $this->error(__('parameter error'));
        }
        $list = (new Userteam())->myteamlist($post,$this->uid);
        $this->success(__('The request is successful'), $list);
    }

    /**
     * 团队列表-teamsize
     */
    public function myteamsizelist()
    {
        $this->verifyUser();
        $post = $this->request->post();
        $page = $this->request->post('page'); //ID
        if (!$page) {
            $this->error(__('parameter error'));
        }
        $list = (new Userteam())->myteamsizelist($post,$this->uid);
        $this->success(__('The request is successful'), $list);
    }

    /**
     * 今日佣金，总佣金记录列表
     */
    public function commissionlist()
    {
        $this->verifyUser();
        $post = $this->request->post();
        $page = $this->request->post('page'); //ID
        if (!$page) {
            $this->error(__('parameter error'));
        }
        $list = (new Userteam())->commissionlist($post,$this->uid);
        $this->success(__('The request is successful'), $list);
    }

    /**
     * 我的下级各等级人数统计
     */
    public function childlevel(){
        $this->verifyUser();
        $level = $this->request->post('level'); //ID
        if (!$level) {
            $this->error(__('parameter error'));
        }
        $list = (new Userteam())->childlevel($level,$this->uid);
        $this->success(__('The request is successful'), $list);
    }
    
    /**
     * 我的下级各等级超过我的人数统计2
     */
    public function childlevelsurpass(){
        $this->verifyUser();
        $level = $this->request->post('level'); //ID
        if (!$level) {
            $this->error(__('parameter error'));
        }
        $list = (new Userteam())->childlevelsurpass($level,$this->userInfo);
        $this->success(__('The request is successful'), $list);
    }

    /**
     * 我的下级各等级超过我的人数统计3
     */
    public function childlevelsurpasss(){
        $this->verifyUser();
        $level = $this->request->post('level'); //ID
        if (!$level) {
            $this->error(__('parameter error'));
        }
        $list = (new Userteam())->childlevelsurpasss($level,$this->userInfo);
        $this->success(__('The request is successful'), $list);
    }

    /**
     * 我的下级各等级人数统计-总
     */
    public function childleveltotal(){
        $this->verifyUser();
        $list = (new Userteam())->childleveltotal($this->uid);
        $this->success(__('The request is successful'), $list);
    }
    
    /**
     * 我的下级各等级超过我的人数统计2-总
     */
    public function childlevelsurpasstotal(){
        $this->verifyUser();
        $list = (new Userteam())->childlevelsurpasstotal($this->userInfo);
        $this->success(__('The request is successful'), $list);
    }

    /**
     * 我的下级各等级超过我的人数统计3-总
     */
    public function childlevelsurpassstotal(){
        $this->verifyUser();
        $list = (new Userteam())->childlevelsurpassstotal($this->userInfo);
        $this->success(__('The request is successful'), $list);
    }

}
