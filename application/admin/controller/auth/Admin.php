<?php

namespace app\admin\controller\auth;

use app\admin\model\AuthGroup;
use app\admin\model\AuthGroupAccess;
use app\admin\model\sys\Agent;
use app\admin\model\User;
use app\common\controller\Backend;
use fast\Random;
use fast\Tree;
use think\Config;
use think\Db;
use think\Validate;

/**
 * 管理员管理
 *
 * @icon   fa fa-users
 * @remark 一个管理员可以有多个角色组,左侧的菜单根据管理员所拥有的权限进行生成
 */
class Admin extends Backend
{

    /**
     * @var \app\admin\model\Admin
     */
    protected $model = null;
    protected $selectpageFields = 'id,username,nickname,avatar';
    protected $searchFields = 'id,username,nickname';
    protected $childrenGroupIds = [];
    protected $childrenAdminIds = [];
    protected $multiFields = 'auto_assign';

    protected $agentGroupId = 6;
    protected $where2 = [];
    protected $group = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Admin');

        $this->childrenAdminIds = $this->auth->getChildrenAdminIds($this->auth->isSuperAdmin());
        $this->childrenGroupIds = $this->auth->getChildrenGroupIds($this->auth->isSuperAdmin());

        $groupList = collection(AuthGroup::where('id', 'in', $this->childrenGroupIds)->select())->toArray();

        Tree::instance()->init($groupList);
        $groupdata = [];
        if ($this->auth->isSuperAdmin()) {
            $result = Tree::instance()->getTreeList(Tree::instance()->getTreeArray(0));
            foreach ($result as $k => $v) {
                $groupdata[$v['id']] = $v['name'];
            }
        } else {
            $result = [];
            $groups = $this->auth->getGroups();
            foreach ($groups as $m => $n) {
                $childlist = Tree::instance()->getTreeList(Tree::instance()->getTreeArray($n['id']));
                $temp = [];
                foreach ($childlist as $k => $v) {
                    $temp[$v['id']] = $v['name'];
                }
                $result[__($n['name'])] = $temp;
            }
            $groupdata = $result;
        }

        $group = $this->auth->getGroupIds($this->auth->id);
        $this->group = $group[0];
        if($group[0] == 6){
            $where2['id'] = $this->auth->id;
        }else{
            $where2 = [];
        }
        $this->where2 = $where2;

        $this->view->assign('groupdata', $groupdata);
        $this->assignconfig("admin", ['id' => $this->auth->id]);
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $childrenGroupIds = $this->childrenGroupIds;
            $groupName = AuthGroup::where('id', 'in', $childrenGroupIds)
                ->column('id,name');
            $authGroupList = AuthGroupAccess::where('group_id', 'in', $childrenGroupIds)
                ->field('uid,group_id')
                ->select();

            $adminGroupName = [];
            foreach ($authGroupList as $k => $v) {
                if (isset($groupName[$v['group_id']])) {
                    $adminGroupName[$v['uid']][$v['group_id']] = $groupName[$v['group_id']];
                }
            }
            $groups = $this->auth->getGroups();
            foreach ($groups as $m => $n) {
                $adminGroupName[$this->auth->id][$n['id']] = $n['name'];
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                ->where($where)
                ->where('id', 'in', $this->childrenAdminIds)
                ->field(['password', 'salt', 'token'], true)
                ->order($sort, $order)
                ->paginate($limit);

            foreach ($list as $k => &$v) {
                $groups = isset($adminGroupName[$v['id']]) ? $adminGroupName[$v['id']] : [];
                $v['groups'] = implode(',', array_keys($groups));
                $v['groups_text'] = implode(',', array_values($groups));
            }
            unset($v);
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
            $params = $this->request->post("row/a");

            if ($params) {
                Db::startTrans();
                try {
                    if (!Validate::is($params['password'], '\S{6,30}')) {
                        exception(__("Please input correct password"));
                    }
                    $params['salt'] = Random::alnum();
                    $params['password'] = md5(md5($params['password']) . $params['salt']);
                    $params['avatar'] = '/assets/img/avatar.png'; //设置新管理员默认头像。
                    $result = $this->model->validate('Admin.add')->save($params);
                    if ($result === false) {
                        exception($this->model->getError());
                    }
                    $group = $this->request->post("group/a");

                    //过滤不允许的组别,避免越权
                    $group = array_intersect($this->childrenGroupIds, $group);
                    if (!$group) {
                        exception(__('The parent group exceeds permission limit'));
                    }

                    $dataset = [];
                    foreach ($group as $value) {
                        $dataset[] = ['uid' => $this->model->id, 'group_id' => $value];
                    }
                    model('AuthGroupAccess')->saveAll($dataset);
                    Db::commit();
                    if (in_array($this->agentGroupId, $group)) {
                        (new Agent())->checkAndAdd($this->model->id);
                    }
                } catch (\Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get(['id' => $ids]);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if (!in_array($row->id, $this->childrenAdminIds)) {
            $this->error(__('You have no permission'));
        }
        if ($this->request->isPost()) {
            $this->token();
            $params = $this->request->post("row/a");
            if ($params) {
                Db::startTrans();
                try {
                    if ($params['password']) {
                        if (!Validate::is($params['password'], '\S{6,30}')) {
                            exception(__("Please input correct password"));
                        }
                        $params['salt'] = Random::alnum();
                        $params['password'] = md5(md5($params['password']) . $params['salt']);
                    } else {
                        unset($params['password'], $params['salt']);
                    }
                    //这里需要针对username和email做唯一验证
                    $adminValidate = \think\Loader::validate('Admin');
                    $adminValidate->rule([
                        'username' => 'require|regex:\w{3,30}|unique:admin,username,' . $row->id,
                        'email'    => 'require|email|unique:admin,email,' . $row->id,
                        'mobile'    => 'regex:1[3-9]\d{9}|unique:admin,mobile,' . $row->id,
                        'password' => 'regex:\S{32}',
                    ]);
                    $result = $row->validate('Admin.edit')->save($params);
                    if ($result === false) {
                        exception($row->getError());
                    }

                    // 先移除所有权限
                    model('AuthGroupAccess')->where('uid', $row->id)->delete();

                    $group = $this->request->post("group/a");

                    // 过滤不允许的组别,避免越权
                    $group = array_intersect($this->childrenGroupIds, $group);
                    if (!$group) {
                        exception(__('The parent group exceeds permission limit'));
                    }

                    $dataset = [];
                    foreach ($group as $value) {
                        $dataset[] = ['uid' => $row->id, 'group_id' => $value];
                    }
                    model('AuthGroupAccess')->saveAll($dataset);
                    Db::commit();
                    if (in_array($this->agentGroupId, $group)) {
                        (new Agent())->checkAndAdd($row->id);
                    }
                } catch (\Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $grouplist = $this->auth->getGroups($row['id']);
        $groupids = [];
        foreach ($grouplist as $k => $v) {
            $groupids[] = $v['id'];
        }
        $this->view->assign("row", $row);
        $this->view->assign("groupids", $groupids);
        return $this->view->fetch();
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
        if ($ids) {
            $ids = array_intersect($this->childrenAdminIds, array_filter(explode(',', $ids)));
            // 避免越权删除管理员
            $childrenGroupIds = $this->childrenGroupIds;
            $adminList = $this->model->where('id', 'in', $ids)->where('id', 'in', function ($query) use ($childrenGroupIds) {
                $query->name('auth_group_access')->where('group_id', 'in', $childrenGroupIds)->field('uid');
            })->select();
            if ($adminList) {
                $deleteIds = [];
                foreach ($adminList as $k => $v) {
                    $deleteIds[] = $v->id;
                }
                $deleteIds = array_values(array_diff($deleteIds, [$this->auth->id]));
                if ($deleteIds) {
                    Db::startTrans();
                    try {
                        $this->model->destroy($deleteIds);
                        model('AuthGroupAccess')->where('uid', 'in', $deleteIds)->delete();
                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                        $this->error($e->getMessage());
                    }
                    $this->success();
                }
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('You have no permission'));
    }

    /**
     * 批量更新
     * @internal
     */
    public function multi($ids = "")
    {
        // 管理员禁止批量操作
        $this->error();
    }

    /**
     * 下拉搜索
     */
    public function selectpage()
    {
        $this->dataLimit = 'auth';
        $this->dataLimitField = 'id';
        return parent::selectpage();
    }

    public function agentindex()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
//            if ($this->request->request('keyField')) {
//                return $this->selectpage();
//            }
            $addwhere = [];
            if ($this->request->request('keyValue')) {
                $addwhere[$this->request->request('searchKey')] = ['in',$this->request->request('searchValue')];
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $where2 = $this->where2;
            $list = $this->model
                ->where($where)
                ->where($where2)
                ->where($addwhere)
                ->where(['user_id'=>['neq',0]])
                ->field(['password', 'salt', 'token'], true)
                ->order($sort, $order)
                ->paginate($limit);
            foreach ($list as &$value){
                $value['invite_code'] = (new User())->where(['is_agent'=>1,'id'=>$value['user_id']])->value('invite_code');
                $value['invite_url'] = Config::get('invite_url')."/#/pages/login/login?currentIndex=register&invite_code=".$value['invite_code'];
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    public function addagent()
    {
        if ($this->request->isPost()) {
            $this->token();
            $params = $this->request->post("row/a");

            if ($params) {
                Db::startTrans();
                try {
                    if (!Validate::is($params['password'], '\S{6,30}')) {
                        exception(__("Please input correct password"));
                    }
                    $params['email'] = $params['mobile'].'@gmail.com';
                    $params['username'] = $params['mobile'];
                    $params['salt'] = Random::alnum();
                    $params['agent_id'] = '0';
                    $password = $params['password'];
                    $params['password'] = md5(md5($params['password']) . $params['salt']);
                    $params['avatar'] = '/assets/img/avatar.png'; //设置新管理员默认头像。
//                    $country_id = $params['country_id'];
//                    unset($params['country_id']);
//                    var_dump($params);exit;
                    $result = $this->model->save($params);
                    if ($result === false) {
                        exception($this->model->getError());
                    }
                    $group = [0=>6];

                    //过滤不允许的组别,避免越权
                    $group = array_intersect($this->childrenGroupIds, $group);
                    if (!$group) {
                        exception(__('The parent group exceeds permission limit'));
                    }

                    $dataset = [];
                    foreach ($group as $value) {
                        $dataset[] = ['uid' => $this->model->id, 'group_id' => $value];
                    }
                    model('AuthGroupAccess')->saveAll($dataset);
                    $post = [
                        'mobile' => $params['mobile'],
                        'username' => $params['mobile'],
                        'invite_code' => '',
                        'password' => $password,
                        'email' => '',
//                        'country_id' => $country_id,
                        'is_agent' => 1
                    ];
                    $rs = (new \app\api\model\User())->registernew($post);
                    if($rs['code'] != 1){
                        Db::rollback();
                        $this->error($rs['msg']);
                    }
                    $this->model->where(['id'=>$this->model->id])->update(['user_id'=>$rs['data']['id']]);
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    public function editagent($ids = null)
    {
        $row = $this->model->get(['id' => $ids]);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if (!in_array($row->id, $this->childrenAdminIds)) {
            $this->error(__('You have no permission'));
        }
        if ($this->request->isPost()) {
            $this->token();
            $params = $this->request->post("row/a");
            if ($params) {
                Db::startTrans();
                try {
                    if ($params['password']) {
                        if (!Validate::is($params['password'], '\S{6,30}')) {
                            exception(__("Please input correct password"));
                        }
                        $params['salt'] = Random::alnum();
                        $params['password'] = md5(md5($params['password']) . $params['salt']);
                    } else {
                        unset($params['password'], $params['salt']);
                    }
                    $result = $row->save($params);
                    if ($result === false) {
                        exception($row->getError());
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
}
