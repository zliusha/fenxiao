<?php
/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/9/3
 * Time: 下午3:45
 */

namespace Service\Support;


use Service\Exceptions\DataException;

class YdbArray
{
    private $rawData;
    private $indexKeys;
    private $indexMap;
    private $indexCallback;

    public function __construct($data = null)
    {
        if (!is_null($data)) {
            $this->rawData = $data;
        }
    }

    /**
     * 重置数组数据
     * @param $data
     * @return $this
     * @author ahe<ahe@iyenei.com>
     */
    public function reset($data)
    {
        $this->rawData = $data;
        return $this;
    }

    /**
     * 重建数组索引
     * @param string $idxName 索引名称
     * @param array $keys 索引字段
     * @param null $callback 生成索引值的回调函数
     * @param bool $redo 如果已经存在,是否重建
     * @return bool false 创建失败, true 创建成功
     * @throws DataException
     * @author ahe<ahe@iyenei.com>
     */
    public function reIndex(string $idxName, array $keys, $callback = null, $redo = false)
    {
        if (!isset($this->indexKeys[$idxName]) || $redo) {
            $this->indexKeys[$idxName] = $keys;
            $this->indexCallback[$idxName] = $callback;
        } else {
            return false;
        }

        if (empty($this->rawData) || !is_array($this->rawData)) {
            return false;
        }

        $idx = [];
        foreach ($this->rawData as $k => $i) {
            $md5Parts = [];
            foreach ($keys as $key) {
                if (is_object($i)) {
                    if (!property_exists($i, $key)) {
                        throw new DataException('属性不存在 : ' . $key);
                    }

                    $md5Parts[] = $i->{$key};
                } elseif (is_array($i)) {
                    if (!key_exists($key, $i)) {
                        throw new DataException('字段不存在 : ' . $key);
                    }

                    $md5Parts[] = $i[$key];
                } else {
                    throw new DataException('索引目标必须为对象或数组');
                }
            }

            if (is_callable($callback)) {
                $md5 = md5(call_user_func_array($callback, $md5Parts));
            } else {
                $md5 = md5(implode($md5Parts));
            }


            if (!isset($idx[$md5])) {
                $idx[$md5] = [];
            }
            $idx[$md5][] = $k;
        }

        $this->indexMap[$idxName] = $idx;
        return true;
    }

    /**
     * 获取对应索引名称下,索引值为$keyVals的元素列表
     * @param string $idxName 索引名称
     * @param array ...$keyVals 索引值
     * @return array|null
     * @author ahe<ahe@iyenei.com>
     */
    public function getList($idxName, ... $keyVals)
    {
        $realIdx = $this->_baseGet($idxName, $keyVals);
        return $this->getRawData($realIdx);
    }

    /**
     * 获取对应索引名称下,索引值为$keyVals的单个元素
     * @param string $idxName 索引名称
     * @param array ...$keyVals 索引值
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function getOne($idxName, ... $keyVals)
    {
        $realIdx = $this->_baseGet($idxName, $keyVals);
        return $this->getRawData($realIdx, true);
    }


    /**
     * 重组数组 key为indexKeys
     * @param string $idxName
     * @return array|null
     * @throws DataException
     * @author ahe<ahe@iyenei.com>
     */
    public function redoWithKey(string $idxName)
    {
        if (!isset($this->indexKeys[$idxName]) || !isset($this->indexMap[$idxName])) {
            return null;
        }
        $out = [];
        foreach ($this->indexMap[$idxName] as $key => $item) {
            foreach ($item as $val) {
                $rawData = $this->rawData[$val];
                $redoKey = '';
                array_walk($this->indexKeys[$idxName], function ($item) use ($rawData, &$redoKey) {
                    if (is_object($rawData)) {
                        $redoKey .= $rawData->$item;
                    } elseif (is_array($rawData)) {
                        $redoKey .= $rawData[$item];
                    }
                });
                if (is_object($rawData)) {
                    $out[$redoKey][] = $rawData;
                } elseif (is_array($rawData)) {
                    $out[$redoKey][] = $rawData;
                } else {
                    throw new DataException('索引目标必须为对象或数组');
                }

            }
        }
        return $out;
    }

    /**
     * 获取全部原始数据
     * @return null
     * @author ahe<ahe@iyenei.com>
     */
    public function getOriginalData()
    {
        return $this->rawData;
    }


    /**
     * 获取数组元素
     * @param $idxName
     * @param $keyVals
     * @return null
     * @throws DataException
     * @author ahe<ahe@iyenei.com>
     */
    private function _baseGet($idxName, $keyVals)
    {
        if (!isset($this->indexKeys[$idxName]) || !isset($this->indexMap[$idxName])) {
            return null;
        }

        if (count($keyVals) != count($this->indexKeys[$idxName])) {
            throw new DataException('索引不匹配');
        }

        if (is_callable($this->indexCallback[$idxName])) {
            $md5 = md5(call_user_func_array($this->indexCallback[$idxName], $keyVals));
        } else {
            $md5 = md5(implode('', $keyVals));
        }


        $idxGroup = $this->indexMap[$idxName];
        $realIdx = isset($idxGroup[$md5]) ? $idxGroup[$md5] : null;
        return $realIdx;
    }


    /**
     * 根据索引值,从原始数据数组中获取正式数据
     * @param $realIdx
     * @param $isOne
     * @return array|null
     * @author ahe<ahe@iyenei.com>
     */
    protected function getRawData($realIdx, $isOne = false)
    {
        if (is_null($realIdx)) {
            return null;
        }

        $res = [];
        foreach ($realIdx as $idx) {
            if (isset($this->rawData[$idx])) {
                if ($isOne) {
                    $res = $this->rawData[$idx];
                    break;
                } else {
                    $res[] = $this->rawData[$idx];
                }
            }
        }

        return $res;
    }

    /**
     * 判断数组中否包含某一项
     * @param $idxName
     * @param array ...$keyVals
     * @return bool
     * @author ahe<ahe@iyenei.com>
     */
    public function has($idxName, ... $keyVals)
    {
        $res = $this->getOne($idxName, ... $keyVals);
        return $res ? true : false;
    }


    /**
     * 是否存在索引
     * @param $idxName
     * @return bool
     * @author ahe<ahe@iyenei.com>
     */
    public function hasIndex($idxName)
    {
        return key_exists($idxName, $this->indexKeys) && key_exists($idxName, $this->indexMap);
    }


    /**
     * 获取数组大小
     * @param null $idxName 索引名
     * @return int
     * @author ahe<ahe@iyenei.com>
     */
    public function size($idxName = null)
    {
        if (is_null($idxName)) {
            return count($this->rawData);
        }

        if (!isset($this->indexMap[$idxName])) {
            return 0;
        }

        return count($this->indexMap[$idxName]);
    }

    /**
     * 回调遍历数据的每一项元素
     * @param \Closure $cb
     * @param null $idxName
     * @param array ...$keyVals
     * @return array
     * @author ahe<ahe@iyenei.com>
     */
    public function forEach (\Closure $cb, $idxName = null, ... $keyVals)
    {
        $res = [];
        if (!is_null($idxName)) {
            $realIdx = $this->_baseGet($idxName, $keyVals);
            if (!is_array($res) || empty($res)) {
                return $res;
            }
            foreach ($realIdx as $idx) {
                if (isset($this->rawData[$idx])) {
                    $i = $this->rawData[$idx];
                    $i = is_object($i) ? get_object_vars($i) : $i;
                    $res[] = call_user_func($cb, $i);
                }
            }
            return $res;
        } else {
            if (is_array($this->rawData) && !empty($this->rawData)) {
                foreach ($this->rawData as $i) {
                    $res[] = call_user_func($cb, $i);
                }
            }
            return $res;
        }
    }
}