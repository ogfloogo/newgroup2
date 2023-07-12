<?php

namespace app\admin\controller\order;

use app\api\model\Goods;
use app\api\model\User;
use app\api\model\Userwarehouse;
use app\common\controller\Backend;
use Exception;
use think\Db;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;
/**
 * 订单管理
 *
 * @icon fa fa-circle-o
 */
class Order extends Backend
{

    /**
     * Order模型对象
     * @var \app\admin\model\order\Order
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\order\Order;
        $this->view->assign("typeList", $this->model->getTypeList());
        $this->view->assign("payStatusList", $this->model->getPayStatusList());
        $this->view->assign("isWinnerList", $this->model->getIsWinnerList());
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
        $user_id = $this->request->param('user_id', 0);
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
        $list = $this->model
            ->with('good,user')
            ->where($where)
            ->where(['order_type'=>['gt',0]])
            ->order($sort, $order)
            ->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }

    public function passAndPay()
    {
        $id = $this->request->param('id', 0);
        $row = $this->model->get($id);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }

        if ($row['pay_status'] != 0) {
            $this->error(__('状态不是待审核，无法操作'));
        }
        $params['pay_status'] = 1;

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
            $userinfo = (new User())->where(['id'=>$row['user_id']])->find();
            $good_info = (new Goods())->where(['id'=>$row['good_id']])->find();
            if($good_info['category_id'] == 2){
                $type = 1;
            }else{
                $type = 2;
            }
            //进仓库
            (new Userwarehouse())->drawwinnings($row, $userinfo, $row['id'], $good_info,$type);
        } catch (ValidateException | PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if (false === $result) {
            $this->error(__('No rows were updated'));
        }
        $this->success('支付成功');
    }
}
