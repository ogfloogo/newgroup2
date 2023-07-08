<?php

namespace app\admin\model;

use think\cache\driver\Redis;
use think\Model;

class CacheModel extends Model
{
    public $cache_prefix = '';
    public $redisInstance = null;

    public function initialize()
    {
        $this->redisInstance = new Redis();
    }

    public function setUserLevelCache($ids, $level, $params = [], $is_del = false)
    {
        if (empty($level)) {
            return false;
        }
        if (is_array($level)) {            
            return false;
        } else {
            $key = $this->buildIdKey($level);
            if ((isset($params['status']) && !$params['status']) || $is_del) {
                return $this->redisInstance->handler()->del($key);
            }
            $params['id'] = $ids;
            $params['level'] = $level;
            return $this->redisInstance->handler()->hMSet($key, $params);
        }
    }

    public function setLevelCache($ids, $params = [], $is_del = false)
    {
        if (empty($ids)) {
            return false;
        }
        if (is_array($ids)) {
            foreach ($ids as $id) {
                $key = $this->buildIdKey($id);
                if ((isset($params['status']) && !$params['status']) || $is_del) {
                    $this->redisInstance->handler()->del($key);
                }
                $params['id'] = $id;
                $this->redisInstance->handler()->hMSet($key, $params);
            }
            return true;
        } else {
            $key = $this->buildIdKey($ids);
            if ((isset($params['status']) && !$params['status']) || $is_del) {
                return $this->redisInstance->handler()->del($key);
            }
            $params['id'] = $ids;
            return $this->redisInstance->handler()->hMSet($key, $params);
        }
    }

    public function setLevelCacheIncludeDel($ids, $params = [])
    {
        if (empty($ids)) {
            return false;
        }
        if (is_array($ids)) {
            foreach ($ids as $id) {
                $key = $this->buildIdKey($id);
                $params['id'] = $id;
                $this->redisInstance->handler()->hMSet($key, $params);
            }
            return true;
        } else {
            $key = $this->buildIdKey($ids);
            $params['id'] = $ids;
            return $this->redisInstance->handler()->hMSet($key, $params);
        }
    }

    public function setSortedSetCache($ids, $params = [], $set_id = '', $weigh = 0, $is_del = false)
    {
        $key = $this->buildSortedSetKey($set_id);
        if (is_array($ids)) {
            foreach ($ids as $id) {
                if ((isset($params['status']) && !$params['status']) || (isset($params['deletetime']) && $params['deletetime']) || $is_del) {
                    return $this->redisInstance->handler()->zRem($key, $id);
                }
                $this->redisInstance->handler()->zAdd($key, $weigh, $id);
            }
            return true;
        } else {
            if ((isset($params['status']) && !$params['status']) || (isset($params['deletetime']) && $params['deletetime']) || $is_del) {
                return $this->redisInstance->handler()->zRem($key, $ids);
            }
            $this->redisInstance->handler()->zAdd($key, $weigh, $ids);
        }
    }

    public function setRecommendSortedSetCache($ids, $params = [], $set_id = '', $weigh = 0, $is_del = false)
    {
        $key = $this->buildSortedSetKey('rec:' . $set_id);
        if (is_array($ids)) {
            foreach ($ids as $id) {
                if ((isset($params['status']) && !$params['status']) || (isset($params['is_recommend']) && !$params['is_recommend']) || (isset($params['deletetime']) && $params['deletetime']) || $is_del) {
                    return $this->redisInstance->handler()->zRem($key, $id);
                }
                $this->redisInstance->handler()->zAdd($key, $weigh, $id);
            }
            return true;
        } else {
            if ((isset($params['status']) && !$params['status']) || (isset($params['is_recommend']) && !$params['is_recommend']) || (isset($params['deletetime']) && $params['deletetime']) || $is_del) {
                return $this->redisInstance->handler()->zRem($key, $ids);
            }
            $this->redisInstance->handler()->zAdd($key, $weigh, $ids);
        }
    }

    public function setListCache()
    {
        $key = $this->buildListKey();
        $list = $this->where(['status' => 1, 'deletetime' => null])->order('weigh desc')->select();
        $this->redisInstance->handler()->set($key, json_encode($list));
    }

    public function setSetCache($ids, $is_del = false)
    {
        $key = $this->buildSetKey();
        if (is_array($ids)) {
            foreach ($ids as $id) {
                if ($is_del) {
                    return $this->redisInstance->handler()->sRem($key, $id);
                }
                $this->redisInstance->handler()->sAdd($key, $id);
            }
            return true;
        } else {
            if ($is_del) {
                return $this->redisInstance->handler()->sRem($key, $ids);
            }
            $this->redisInstance->handler()->sAdd($key, $ids);
        }
    }

    protected function buildIdKey($id)
    {
        return $this->cache_prefix . $id;
    }

    protected function buildSortedSetKey($id)
    {
        return $this->cache_prefix . 'set:' . $id;
    }

    protected function buildListKey()
    {
        return $this->cache_prefix . 'list';
    }

    protected function buildSetKey()
    {
        return $this->cache_prefix . 'set';
    }
}
