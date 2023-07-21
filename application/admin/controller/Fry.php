<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 鱼苗管理
 *
 * @icon fa fa-circle-o
 */
class Fry extends Backend
{

    /**
     * Fry模型对象
     * @var \app\admin\model\Fry
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Fry;
        $this->view->assign("statusList", $this->model->getStatusList());
        $group = $this->auth->getGroupIds($this->auth->id);
        $this->group = $group[0];
        if($group[0] == 6) {
            $where2['agent_id'] = $this->auth->user_id;
        } else {
            $where2 = [];
        }
        $this->where2 = $where2;
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $where2 = $this->where2;
        $list = $this->model
            ->where($where)
            ->where($where2)
            ->order($sort, $order)
            ->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }
}
