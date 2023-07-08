<?php

namespace app\api\controller;

use think\Config;

/**
 * 首页接口
 */
class Index extends Controller
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     *
     */
    public function index()
    {
        // echo Config::get('site.daily_buy_num');
        $this->success('The request is successful');
    }
}
