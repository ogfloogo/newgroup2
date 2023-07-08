<?php

namespace app\admin\model\groupbuy;

use app\admin\model\CacheModel;
use think\Model;
use traits\model\SoftDelete;
use app\api\model\Usercategory;

class Goods extends CacheModel
{

    use SoftDelete;

    

    // 表名
    protected $name = 'goods';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'status_text',
        'is_recommend_text',
        'now_pool_num',
        'daily_win_man',
    ];
    
    public $cache_prefix = 'newgroup:good:';

    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }

    
    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1')];
    }

    public function getIsRecommendList()
    {
        return ['0' => __('Is_recommend 0'), '1' => __('Is_recommend 1')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getIsRecommendTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_recommend']) ? $data['is_recommend'] : '');
        $list = $this->getIsRecommendList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function category()
    {
        return $this->belongsTo('\app\admin\model\groupbuy\GoodsCategory', 'category_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function goodtypes()
    {
        return $this->belongsTo('\app\admin\model\Goodtypes', 'category_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function getNowPoolNumAttr($value, $data)
    {
        $id= $data['id'];
        $pool_key = (new Usercategory())->getPoolKey($id);
        $num = $this->redisInstance->handler()->sCard($pool_key);
        return intval($num);
    }

    public function getDailyWinManAttr($value, $data)
    {
        $id= $data['id'];
        $win_pool_key = (new Usercategory())->getWinPoolKey($id);
        $num = $this->redisInstance->handler()->sCard($win_pool_key);
        return intval($num);
    }

}
