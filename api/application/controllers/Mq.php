<?php
/**
 * @Author: binghe
 * @Date:   2017-12-12 15:54:38
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-02 15:13:05
 */
require_once ROOT . 'vendor/autoload.php';
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

use Service\DbFrame\DataBase\MainDbModels\MainMqDyDao;

/**
* Mq 消息队列 - 消息分发
*/
class Mq extends base_controller
{

    //线程值，确保线程唯一，且最新
    public $process_key;
    public $ci_redis;
    public $c_key = '';
    public $list_key='';
    public $valid_second=600;
    public $client;
    public $promises=[];
    public function __construct(){
        parent::__construct();
        $this->process_key = md5(create_guid());
        $this->ci_redis = new ci_redis;
        $this->c_key = $this->ci_redis->generate_key('mq');
        $this->list_key = $this->ci_redis->generate_key('mq_list');
        $this->client= new  Client(['timeout'  => 3 ]);
    }
    /**
     * 5分钟未调用自动重启 - 定时器重启
     * @return [type] [description]
     */
    public function run($limit=2)
    {
        set_time_limit(0);//让程序一直执行下去
        ignore_user_abort(true);// 当脚本终端结束，脚本不会被立即中止
        $type = $this->input->get('type');
        $time = time();
        if($type=='close')
        {
            //开起新的线程，并立即退出
            $this->ci_redis->delete($this->c_key);
            log_message('error',__METHOD__.'--手动退出线程');
            //等待所有线程自动关闭
            sleep(3);
            exit;
        }
        elseif($type=='auto')
        {
            //最后次执行情况
            $last = @json_decode($this->ci_redis->get($this->c_key),true);
            //线程正常时，过滤线程检测
            if($last && $time - $last['process_time'] < $this->valid_second)
            {
                log_message('error',__METHOD__.'--线程正常');
                exit;
            }
            else{
                $this->_init_process($time,'自动开动线程');
            }
        }
        else
            $this->_init_process($time,'手动开起线程');

        while (true) {

            $new_last = @json_decode($this->ci_redis->get($this->c_key),true);;  
            if(!$new_last)
            {
                log_message('error',__METHOD__.'--关闭线程自动退出--'.$this->process_key);
                break;
            }

            if($new_last['process_key'] != $this->process_key)
            {
                log_message('error',__METHOD__.'--旧线程退出--'.$this->process_key);
                break;
            }
            //清空请求
            $this->promises = [];
            //消息处理
            $msg = $this->ci_redis->lPop($this->list_key);
            $msg = @unserialize($msg);
            if($msg && get_class($msg) == 'mq_msg_do' && isset($msg->type))
            {
                switch ($msg->type) {
                    //订单消息推送
                    case 'order_msg':
                            $this->_proOrderMessage($msg);
                        break;
                }
                try {
                    //同理发起异步请求
                    if(!empty($this->promises))
                    {
                        $requests = Promise\unwrap($this->promises);
                    }
                } catch (Exception $e) {
                    log_message('error',__METHOD__.'--'.$e->getMessage());
                }
                
            }

            $this->_init_process(time(),'刷新线程执行时间');
            //沉睡1秒,正式环境流量大的话可以关闭
            sleep(1);

        }
        
    }  
    private function _init_process($time,$msg='')
    {
        $new_last['process_key'] = $this->process_key;
        $new_last['process_time'] = $time;
        //更新最后次执行情况 并缓存10分钟
        $this->ci_redis->setex($this->c_key, $this->valid_second, json_encode($new_last));
        // log_message('error',__METHOD__.'--'.$msg.'--'.$this->process_key);
    }
    /**************** 订单消息 ********************/
    /**
     * 订单消息处理工厂
     * @param  [type] $msg [description]
     * @return [type]      [description]
     */
    private function _proOrderMessage($msg)
    {
        log_message('error',__METHOD__.'--msg--'.json_encode($msg));
        $this->_sendToTeServer($msg);
        $this->_sendToApp($msg);
        //$this->_sendToBrowser($msg);
    }
    /**
     * 发送消息至Te服务器
     * @return [type] [description]
     */
    private function _sendToTeServer($msg){
        //只发送待接单和已接单
        $data = $msg->data;
        if(!in_array($data['status'], ['2020','2040']))
            return;
        //查看此公司是否订阅服务
        $mainMqDyDao = MainMqDyDao::i();
        $m_main_mq_dy = $mainMqDyDao->getOne(['aid'=>$data['aid'],'type'=>'sy']);
        if($m_main_mq_dy)
        {
            
                //收银app_secret 此处硬编码,为了提高效率
                $access_key = 'yd_a8bdfd6ca4';
                $secret_key = 'c9bdfd6b43ba6cb3abf9ac67928becce';
                $auth = new auth($access_key,$secret_key);
                $params['json'] = json_encode($msg);
                $params['sign'] = $auth->sign($params);
                $options['form_params']=$params;
                $inc = &inc_config('shouyin');
                $url=$inc['notify_url'];
                $promise = $this->client->postAsync($url,$options);
                $promise->then(function (ResponseInterface $res) {
                    log_message('error','msg-te-'.$res->getBody());
                },
                function (\Exception $e) {
                    log_message('error',__METHOD__.'msg-te-'.$e->getMessage());
                });
                array_push($this->promises, $promise);
            
        }
        
    } 
    /**
     * 发送消息至app
     * @return [type] [description]
     */
    private function _sendToApp($msg){
        //只发送待接单和已接单
        $data = $msg->data;
        if(!in_array($data['status'], ['2020','2040']))
            return;
        //查看此公司是否订阅服务
        $mainMqDyDao = MainMqDyDao::i();
        $m_main_mq_dy = $mainMqDyDao->getOne(['aid'=>$data['aid'],'type'=>'sy']);
        if($m_main_mq_dy)
        {
            
                //收银app_secret 此处硬编码,为了提高效率
                $access_key = 'yd_a8bdfd6ca4';
                $secret_key = 'c9bdfd6b43ba6cb3abf9ac67928becce';
                $auth = new auth($access_key,$secret_key);
                $params['json'] = json_encode($msg);
                $params['sign'] = $auth->sign($params);
                $options['form_params']=$params;
                $url='http://fw.waimai.com/test/app';
                $promise = $this->client->postAsync($url,$options);
                $promise->then(function (ResponseInterface $res) {
                    log_message('error',__METHOD__.'msg-app-'.$res->getBody());
                },
                function (\Exception $e) {
                    log_message('error',__METHOD__.'msg-app-'.$e->getMessage());
                });
                array_push($this->promises, $promise);
            
        }
    }
    /**
     * 发送消息至浏览器
     * @return [type] [description]
     */
    private function _sendToBrowser($msg){
        log_message('error',__METHOD__.'--msg');
    }
    /**************** 订单消息 ********************/

}