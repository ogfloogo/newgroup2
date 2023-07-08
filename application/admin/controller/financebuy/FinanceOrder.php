<?php

namespace app\admin\controller\financebuy;

use app\admin\model\financebuy\Finance;
use app\admin\model\financebuy\FinanceIssue;
use app\admin\model\financebuy\FinanceOrder as ModelFinanceOrder;
use app\admin\model\financebuy\FinanceRate;
use app\common\controller\Backend;

/**
 * 团购理财订单管理
 *
 * @icon fa fa-circle-o
 */
class FinanceOrder extends Backend
{

    /**
     * FinanceOrder模型对象
     * @var \app\admin\model\financebuy\FinanceOrder
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\financebuy\FinanceOrder;
        $this->view->assign("isRobotList", $this->model->getIsRobotList());
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("stateList", $this->model->getStateList());
    }


    /**
     * 查看
     *
     * @return string|Json
     * @throws \think\Exception
     * @throws DbException
     */
    public function index()
    {
        $this->relationSearch = true;
        $finance_id = $this->request->param('finance_id', 0);
        $issue_id = $this->request->param('ids', 0);

        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            $this->getFinanceInfo($finance_id, $issue_id);
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $list = $this->model
            ->with('user,issue,finance')
            ->where($where)
            ->where(['finance_order.finance_id' => $finance_id])
            ->order($sort, $order)
            ->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }

    protected function getFinanceInfo($finance_id, $issue_id)
    {
        if (!$finance_id) {
            return [];
        }
        $row = (new Finance())->where(['id' => $finance_id])->find();

        $userIds = $this->model->where(['finance_id' => $finance_id])->distinct(true)->column('user_id');
        $row['buy_user_number'] = count($userIds);
        $userIds = $this->model->where(['finance_id' => $finance_id, 'is_robot' => 0])->distinct(true)->column('user_id');
        $row['buy_real_number'] = count($userIds);
        $rate = (new FinanceRate())->where(['end' => ['GT', count($userIds)]])->order('start ASC')->value('rate');
        if (!$rate) {
            $rate = (new FinanceRate())->where(['end' => 0])->value('rate');
        }
        $row['current_ratio'] = $rate;

        // echo $this->model->getLastSql();
        $row['sell_number'] = $this->model->where(['finance_id' => $finance_id])->sum('buy_number');
        $row['sell_amount'] = $this->model->where(['finance_id' => $finance_id])->sum('amount');
        $row['sell_real_number'] = $this->model->where(['finance_id' => $finance_id, 'is_robot' => 0])->sum('buy_number');
        $row['sell_real_amount'] = $this->model->where(['finance_id' => $finance_id, 'is_robot' => 0])->sum('amount');
        $row['basic_earning_ratio'] = number_format($rate * $row['sell_amount'] / 100, 2);

        $issueInfo = (new FinanceIssue())->where(['id' => $issue_id])->find();
        $row['presell_start_time'] = date('Y-m-d H:i:s', $issueInfo['presell_start_time']);
        $row['presell_end_time'] = date('Y-m-d H:i:s', $issueInfo['presell_end_time']);
        $row['start_time'] = date('Y-m-d H:i:s', $issueInfo['start_time']);
        $row['end_time'] = date('Y-m-d H:i:s', $issueInfo['end_time']);
        // $row['status'] = $issueInfo['status_text'];
        $this->assign('row', $row);
    }
}
