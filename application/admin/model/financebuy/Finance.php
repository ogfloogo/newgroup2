<?php

namespace app\admin\model\financebuy;

use app\admin\model\CacheModel;
use think\Model;
use traits\model\SoftDelete;

class Finance extends CacheModel
{

    use SoftDelete;



    // 表名
    protected $name = 'finance';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'robot_status_text',
        'auto_open_text',
        'status_text'
    ];

    public $cache_prefix = 'new:finance:';

    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }


    public function getRobotStatusList()
    {
        return ['1' => __('Robot_status 1'), '0' => __('Robot_status 0')];
    }

    public function getAutoOpenList()
    {
        return ['1' => __('Auto_open 1'), '0' => __('Auto_open 0')];
    }

    public function getStatusList()
    {
        return ['1' => __('Status 1'), '0' => __('Status 0')];
    }


    public function getRobotStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['robot_status']) ? $data['robot_status'] : '');
        $list = $this->getRobotStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getAutoOpenTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['auto_open']) ? $data['auto_open'] : '');
        $list = $this->getAutoOpenList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }
}
