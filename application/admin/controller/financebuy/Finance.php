<?php

namespace app\admin\controller\financebuy;

use app\admin\model\financebuy\FinanceIssue;
use app\api\model\Financeissue as ModelFinanceissue;
use app\api\model\Financeorder;
use app\common\controller\Backend;
use Exception;
use think\Db;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 基金活动管理
 *
 * @icon fa fa-circle-o
 */
class Finance extends Backend
{

    /**
     * Finance模型对象
     * @var \app\admin\model\financebuy\Finance
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\financebuy\Finance;
        $this->view->assign("robotStatusList", $this->model->getRobotStatusList());
        $this->view->assign("autoOpenList", $this->model->getAutoOpenList());
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    /**
     * 添加
     *
     * @return string
     * @throws \think\Exception
     */
    public function add()
    {
        if (false === $this->request->isPost()) {
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);

        if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
            $params[$this->dataLimitField] = $this->auth->id;
        }
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException()->validate($validate);
            }
            $result = $this->model->allowField(true)->save($params);
            Db::commit();
            $this->model->setLevelCacheIncludeDel($this->model->id, $this->model->get($this->model->id)->toArray());
            $this->model->setSortedSetCache($this->model->id, $params, 0, $params['weigh']);
            if ($params['robot_status']) {
                (new Financeorder())->openrobot($this->model->id, $params['robot_addorder_time_start'], $params['robot_addorder_time_end'], $params['robot_addorder_num_start'], $params['robot_addorder_num_end']);
            } else {
                (new Financeorder())->closerobot($this->model->id);
            }
            (new ModelFinanceissue())->addnewissue($this->model->id);
        } catch (ValidateException | PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($result === false) {
            $this->error(__('No rows were inserted'));
        }
        $this->success();
    }

    /**
     * 编辑
     *
     * @param $ids
     * @return string
     * @throws DbException
     * @throws \think\Exception
     */
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

        if (!$params['status']) {
            $count = (new FinanceIssue())->where(['finance_id' => $ids, 'presell_start_time' => ['elt', time()], 'end_time' => ['egt', time()]])->count();
            if ($count) {
                $this->error(__('期次未结束不能下架', ''));
            }
        }
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
            $this->model->setLevelCacheIncludeDel($ids, $row->toArray());
            $this->model->setSortedSetCache($ids, $params, 0, $params['weigh']);
            if ($params['robot_status']) {
                (new Financeorder())->openrobot($ids, $params['robot_addorder_time_start'], $params['robot_addorder_time_end'], $params['robot_addorder_num_start'], $params['robot_addorder_num_end']);
            } else {
                (new Financeorder())->closerobot($ids);
            }
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
     * 删除
     *
     * @param $ids
     * @return void
     * @throws DbException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function del($ids = null)
    {
        if (false === $this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ?: $this->request->post("ids");
        if (empty($ids)) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }
        $pk = $this->model->getPk();
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $this->model->where($this->dataLimitField, 'in', $adminIds);
        }
        $list = $this->model->where($pk, 'in', $ids)->select();

        $count = 0;
        Db::startTrans();
        try {
            foreach ($list as $item) {
                $count = (new FinanceIssue())->where(['finance_id' => $item->id, 'presell_start_time' => ['elt', time()], 'end_time' => ['egt', time()]])->count();
                if ($count) {
                    Db::rollback();
                    $this->error(__('期次未结束不能下架', ''));
                }
                $count += $item->delete();
                $this->model->setLevelCacheIncludeDel($item->id, $this->model::onlyTrashed()->where(['id' => $item->id])->find()->toArray());
                $this->model->setSortedSetCache($item->id, [], 0, 0, true);
                (new Financeorder())->closerobot($item->id);
            }
            Db::commit();
            // $this->model->setLevelCache($ids, [], true);
        } catch (PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $this->success();
        }
        $this->error(__('No rows were deleted'));
    }

    /**
     * 获取详情
     *
     * @param $ids
     * @return string
     * @throws DbException
     * @throws \think\Exception
     */
    public function info($ids = null)
    {

        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        $this->success('ok', null, $row);
    }
}
