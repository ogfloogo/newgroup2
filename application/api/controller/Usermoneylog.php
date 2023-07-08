<?php

namespace app\api\controller;

use app\api\model\Faq as ModelFaq;
use app\api\model\Usermoneylog as ModelUsermoneylog;
use think\Config;

/**
 * 资金记录
 */
class Usermoneylog extends Controller
{

    /**
     * 资金记录列表
     */
    public function list(){
        $this->verifyUser();
        $page = $this->request->post('page');
        $type = $this->request->post('type');//0=余额，1=佣金记录
        $type = !empty($type) ? $type : 0;
        $list = (new ModelUsermoneylog())->list($page,$this->uid,$this->language,$type);
        $this->success(__('The request is successful'),$list);
    }

    public function moneytotal(){
        $this->verifyUser();
        $type = $this->request->post('type');//0=余额，1=佣金记录
        $type = !empty($type) ? $type : 0;
        $res = (new ModelUsermoneylog())->moneytotal($this->uid,$type);
        $this->success(__('The request is successful'),$res);
    }

}
