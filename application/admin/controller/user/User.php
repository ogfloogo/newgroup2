<?php

namespace app\admin\controller\user;

use app\admin\model\user\UserTeam;
use app\api\model\User as ModelUser;
use app\common\controller\Backend;
use app\common\library\Auth;
use Exception;
use think\cache\driver\Redis;
use think\Db;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 会员管理
 *
 * @icon fa fa-user
 */
class User extends Backend
{

    protected $relationSearch = true;
    protected $searchFields = 'id,nickname,mobile';

    /**
     * @var \app\admin\model\User
     */
    protected $model = null;
    protected $where2 = [];
    protected $group = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('User');
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
     * 查看
     */
    public function index()
    {
        $this->relationSearch = true;

        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $where2 = $this->where2;
            $list = $this->model
                ->with('usertotal')
                ->where($where)
                ->where($where2)
                ->order($sort, $order)
                ->paginate($limit);
            foreach ($list as $k => $v) {
                $v->avatar = $v->avatar ? cdnurl($v->avatar, true) : letter_avatar($v->nickname);
                $v->hidden(['password', 'salt']);
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $this->token();
        }
        return parent::add();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()) {
            $this->token();
        }
        $row = $this->model->get($ids);
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if ($row['sid'] != $params['sid']) {
                $teamUserIds = (new UserTeam())->where(['user_id' => $ids, 'level' => ['gt', 0]])->column('team');
                if (count($teamUserIds)) {
                    $this->error('已有团队，不能设置');
                }
                $isbelong = (new UserTeam())->where(['team' => $ids])->count();
                if ($isbelong) {
                    $this->error('已有上级，不能设置');
                }
                if ($params['sid'] == $ids) {
                    $this->error('上级不能设置为自己');
                }
                if (in_array($params['sid'], $teamUserIds)) {
                    $this->error('上级不能设置为自己团队的用户');
                }
            }
            if (!$params['status']) {
                $token = $row['token'];
                if ($token) {
                    (new ModelUser())->logout($token);
                }
            }
            if ($params['need_sms']) {
                $token = $row['token'];
                if ($token) {
                    (new ModelUser())->logout($token);
                }
            }
            if ($params['frozentime'] && $params['frozentime']>time()) {
                $token = $row['token'];
                if ($token) {
                    (new ModelUser())->logout($token);
                }
            }
        }
        // $this->view->assign('groupList', build_select('row[group_id]', \app\admin\model\UserGroup::column('id,name'), $row['group_id'], ['class' => 'form-control selectpicker']));
        return parent::edit($ids);
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        $row = $this->model->get($ids);
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        Auth::instance()->delete($row['id']);
        $token = $row['token'];
        if ($token) {
            (new ModelUser())->logout($token);
        }
        $this->success();
    }

    /**
     * 修改余额
     */
    public function updatemoney($ids = null)
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
            return $this->view->fetch('update_money');
        }
        $amount = $this->request->post('amount');
        if (empty($amount)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $params['money'] = bcadd($row['money'], $amount, 2);
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            $result = $row->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException | PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if (false === $result) {
            $this->error(__('No rows were updated'));
        }
        $this->success();
    }

    /**
     * 修改余额
     */
    public function updatecommissionmoney($ids = null)
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
            return $this->view->fetch('update_money');
        }
        $amount = $this->request->post('amount');
        if (empty($amount)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $params['commission'] = bcadd($row['commission'], $amount, 2);
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            $result = $row->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException | PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if (false === $result) {
            $this->error(__('No rows were updated'));
        }
        $this->success();
    }

    public function verifytimes($id,$times){
        $row = $this->model->get($id);
        if(!$row){
            $this->error();
        }
        $row->times = $times;
        $rs = $row->save();
        $this->success();
    }
}
