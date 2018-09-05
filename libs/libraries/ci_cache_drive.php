<?php
/**
 * @Author: binghe
 * @Date:   2017-09-19 15:59:54
 * @Last Modified by:   binghe
 * @Last Modified time: 2017-10-11 14:12:55
 */
/**
* 缓存-支持redis,memecahed
*/
class ci_cache_drive 
{
    public $key_prefix='ydb_waimai:';
    public $CI;
    public $cache;
    function __construct()
    {
        $this->CI=&get_instance();
        $this->CI->load->driver('cache', ['adapter' => 'redis', 'backup' => 'memcached']);
        $this->cache=$this->CI->cache;
    }
    //生成平台区分
    public function generate_key($init_key='')
    {
        return $this->key_prefix.$init_key;
    }
}