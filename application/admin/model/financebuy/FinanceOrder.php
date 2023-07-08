<?php

namespace app\admin\model\financebuy;

use think\Model;
use traits\model\SoftDelete;

class FinanceOrder extends Model
{

    use SoftDelete;



    // 表名
    protected $name = 'finance_order';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'is_robot_text',
        'buy_time_text',
        'earning_start_time_text',
        'earning_end_time_text',
        'status_text',
        'state_text'
    ];



    public function getIsRobotList()
    {
        return ['1' => __('Is_robot 1'), '0' => __('Is_robot 0')];
    }

    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1'), '2' => __('Status 2')];
    }

    public function getStateList()
    {
        return ['0' => __('State 0'), '1' => __('State 1'), '2' => __('State 2')];
    }


    public function getIsRobotTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_robot']) ? $data['is_robot'] : '');
        $list = $this->getIsRobotList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getBuyTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['buy_time']) ? $data['buy_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getEarningStartTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['earning_start_time']) ? $data['earning_start_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getEarningEndTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['earning_end_time']) ? $data['earning_end_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStateTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['state']) ? $data['state'] : '');
        $list = $this->getStateList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setBuyTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setEarningStartTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setEarningEndTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    public function user()
    {
        return $this->belongsTo('\app\admin\model\User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function finance()
    {
        return $this->belongsTo('\app\admin\model\financebuy\Finance', 'finance_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function issue()
    {
        return $this->belongsTo('\app\admin\model\financebuy\FinanceIssue', 'issue_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
