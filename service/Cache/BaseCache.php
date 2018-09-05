<?php
/**
 * @Author: binghe
 * @Date:   2018-04-03 20:18:09
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-16 14:00:01
 */
namespace Service\Cache;
use Service\Exceptions\CacheException;
/**
* base
*/
abstract class BaseCache 
{
    public $redis;
    public $input=[];
    //是否是序列化对象
    private $_isSerializ = true;
    public function __construct($input=[])
    {
        $this->redis = new \ci_redis();
        $this->input = $input;
        $this->_init();
    }
    private function _init()
    {
        //instanceof 此处可用此函数
        // $className = static::class;
        // $class = new \ReflectionClass($className);
        // $this->_isSerializ = !$class->implementsInterface(IAssign::class);
        return $this instanceof IAssign;
    }
    /**
     * 获取缓存　且不存在时设置缓存　get an save not exise
     * @return mixed 
     */
    public function getASNX($dataProviderCallback,$expiredTime = 7200)
    {
        $data = $this->get();
        if ($data === false) {
            $data = call_user_func_array($dataProviderCallback, []);
            //如不存在支持返回null值,null值不保存缓存
            if($data !== null)
                $this->save($data,$expiredTime);         
        }
        return $data;
    }
    /**
     * 设置缓存
     * @param  mixed $data [description]
     * @return bool
     */
    public function save($data,$expiredTime = 7200)
    {
        $this->_isSerializ && $data = serialize($data);
        return $this->redis->setex($this->_getLastKey(),$expiredTime,$data);
    }
    /**
     * 删除缓存
     * @return bool
     */
    public function delete()
    {
        return $this->redis->delete($this->_getLastKey());
    }
    /**
     * 获取缓存
     * @return mixed [description]
     */
    public function get()
    {
        $data = $this->redis->get($this->_getLastKey());
        if($data && $this->_isSerializ)
            return @unserialize($data);
        else return $data;
    }
    /**
     * 得到最终的key
     * @return string [description]
     */
    private function _getLastKey()
    {
        return $this->redis->generate_key($this->getKey());
    }
    /**
     * 得到未加前缀的key
     * @param  array $input 需要带入的参数
     * @return string   
     */
    public abstract function getKey();
}