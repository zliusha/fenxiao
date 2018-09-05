<?php
/**
 * @Author: binghe
 * @Date:   2017-08-22 15:42:53
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 17:39:22
 */
use Service\Traits\HttpTrait;

/**
 * erp_sdk
 * doc http://rapapi.org/workspace/myWorkspace.do?projectId=29390#296252
 */
class erp_sdk
{
    public $inc;
    use HttpTrait;
    public function __construct()
    {
        $this->inc = &inc_config('erp');
    }
    

    
    /**
     * 登录
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function login($params=[])
    {
        $url = $this->inc['url'];
        $body['arguments']=$params;
        $body['class']='RpcUser';
        $body['method']='user';
        return $this->request($body,$url);
    }
    /**
     * token登录
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function tokenLogin($params=[])
    {
        $url = $this->inc['url'];
        $body['arguments']=$params;
        $body['class']='RpcLoginPermit';
        $body['method']='userAuth';
        return $this->request($body,$url);
    }
    /**
     * 注册
     */
    public function register($params=[])
    {
        $url = $this->inc['url'];
        $body['arguments']=$params;
        $body['class']='RpcUser';
        $body['method']='register';
        return $this->request($body,$url);
    }

    /**
     * 添加子账户
     * @param array $params
     * @return mixed
     */
    public function addUser($params=[])
    {
        $url = $this->inc['url'];
        $body['arguments']=$params;
        $body['class']='RpcUser';
        $body['method']='addUser';
        return $this->request($body,$url);
    }
    /**
     * 根据手机号得到用户信息
     * @return [type] [description]
     */
    public function getUserByPhone($params=[])
    {
        $url = $this->inc['url'];
        $body['arguments']=$params;
        $body['class']='RpcUser';
        $body['method']='getUser';
        return $this->request($body,$url);
    }
    /**
     * 根据id得到用户信息
     * @return [type] [description]
     */
    public function getUserById($params=[])
    {
        $url = $this->inc['url'];
        $body['arguments']=$params;
        $body['class']='RpcUser';
        $body['method']='getUserById';
        return $this->request($body,$url);
    }
    /**
     * 修改用户信息
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function updateUser($params=[])
    {
        $url = $this->inc['url'];
        $body['arguments']=$params;
        $body['class']='RpcUser';
        $body['method']='editUser';
        return $this->request($body,$url);
    }
    /**
     * 通过手机号修改密码
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function updatePwdByPhone($params=[])
    {
        $url = $this->inc['url'];
        $body['arguments']=$params;
        $body['class']='RpcSpecialUser';
        $body['method']='setPasswordByPhone';
        return $this->request($body,$url);
    }
    /**
     * 通过visit_id,user_id登录
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function loginById($params=[])
    {
        $url = $this->inc['url'];
        $body['arguments']=$params;
        $body['class']='RpcLoginPermit';
        $body['method']='loginByUserId';
        return $this->request($body,$url);
    }
    /**
     * 获取登录令牌
     * @param  [type] $params [visit_id,user_id]
     * @return [type]         [description]
     */
    public function getToken($params=[])
    {
        $url = $this->inc['url'];
        $body['arguments']=$params;
        $body['class']='RpcLoginPermit';
        $body['method']='auth';
        return $this->request($body,$url);
    }
    /**
     * 获取登录令牌
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function getUsers($params=[])
    {
        $url = $this->inc['url'];
        $body['arguments']=$params;
        $body['class']='RpcLoginPermit';
        $body['method']='permits';
        return $this->request($body,$url);
    }
    /*************以下是消息服务****************/
    /**
     * 发布主题消息
     * @return [type] [description]
     */
    public function mnsPublishTopicMsg($params=[])
    {
        $url = $this->inc['service_url'];
        $body['arguments']=$params;
        $body['class']='RpcMnsTopic';
        $body['method']='publishMessage';
        return $this->request($body,$url);
    }
    /**
     * 订阅主题
     * @return [type] [description]
     */
    public function mnsSubscribe($params=[])
    {
        $url = $this->inc['service_url'];
        $body['arguments']=$params;
        $body['class']='RpcMnsTopic';
        $body['method']='subscribe';
        return $this->request($body,$url);
    }
    /**
     * 取消订阅主题
     * @return [type] [description]
     */
    public function mnsUnSubscribe($params=[])
    {
        $url = $this->inc['service_url'];
        $body['arguments']=$params;
        $body['class']='RpcMnsTopic';
        $body['method']='unsubscribe';
        return $this->request($body,$url);
    }
    /**
     * 获取订阅列表
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function getSubscribleList($params=[])
    {
        $url = $this->inc['service_url'];
        $body['arguments']=$params;
        $body['class']='RpcMnsTopic';
        $body['method']='subscribeList';
        return $this->request($body,$url);
    }
    /***************以上是消息服务*****************/

    /**
     * 错误时有异常输出
     * @param array $params
     * @return [type] [description]
     */
    public function request($body = [] ,$url, $method='POST')
    {
        $time=time();
        $body=json_encode($body);
        $headers['Appid']=$this->inc['app_id'];
        $headers['Sign-time'] = $time;
        $headers['Content-Type']='application/json';
        $headers['Sign']=$this->_generate_sign($body,$time);
        $options=['body'=>$body,'headers'=>$headers];
        $http = $this->getHttp();
        $http->setDefaultOptions();
        $response = $http->request($url, $method,$options);
        $json_result = $http->parseJSON($response);
        $this->_valid_result($json_result);
        return $json_result['data'];

    }
    
    /**
     * 生成数据的签名
     *
     * @param $params
     * @return string
     * @author jiaozi<jiaozi@iyenei.com>
     *
     */
    private function _generate_sign($body,$time)
    {
        $sign_str = 'ecbao_rpc_secret'.$body.$time.$this->inc['app_secret'].'ecbao_rpc_secret';
        return md5($sign_str);
    }
    /**
     * 验证结果,中断
     */
    private function _valid_result($json_result)
    {

        $err_msg = 'erp接口请求异常';
        if (isset($json_result['code']) && $json_result['code'] == 0) {
            return true;
        } else {
            if (isset($json_result['code']) && isset($json_result['msg'])) {
                $err_msg = $json_result['code'] . '-' . $json_result['msg'];
            }
            log_message('error',__METHOD__.'-'.$err_msg);
            throw new Exception($err_msg);            

        }
    }

}
