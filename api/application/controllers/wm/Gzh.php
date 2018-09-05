<?php
/**
 * @Author: binghe
 * @Date:   2018-01-18 10:57:43
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 11:20:17
 */
/**
* 公众号/小程序相关
*/
use Service\DbFrame\DataBase\WmMainDbModels\XcxAppDao;
class Gzh extends open_controller
{
    
    /**
     * 事件的通知处理
     * @return [type] [description]
     */
    public function notify()
    {
        log_message('error',__METHOD__.'-'.json_encode($this->input->post()));
        $rules = [
            ['field' => 'visit_id','label'=>'店铺所数id','rules'=>'trim|required|numeric']
            ,['field' => 'app_id','label'=>'店铺id','rules'=>'trim|required']
            ,['field' => 'type','label'=>'回调类型','rules'=>'trim|required|in_list[1,2]']
            ,['field'=> 'json','label'=>'消息','rules'=>'trim|required']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        
        $json = @json_decode($fdata['json']);
        if(empty($json))
            $this->json_do->set_error('001','json格式错误');
        // 自动将aid转换为visit_id
        $aid = $this->convert_visit_id($fdata['visit_id']);
        //$type 1小程序,2微信
        switch ($fdata['type']) {
            case 1:
                $params=['aid'=>$aid,'app_id'=>$fdata['app_id'],'visit_id'=>$fdata['visit_id']];
                $this->_process_xcx($params,$json);
                break;
            
            case 2:
                $this->_process_gzh();
                break;
        }

    }
    /**
     * 公众号消息处理,*扩展未处理
     * @return [type] [description]
     */
    private function _process_gzh()
    {
        $this->json_do->out_put();
    }
    /**
     * 小程序消息处理  
     * @param  [type] $aid    [description]
     * @param  [type] $app_id [description]
     * @param  [type] $json   [description]
     * @return [type]         [description]
     */
    private function _process_xcx($params,$json)
    {
        switch ($json->MsgType) {
            case 'event':
                    $this->_process_xcx_event($params,$json);
                break;
            
            default:
                    $this->json_do->out_put();
                break;
        }
    }
    /**
     * 小程序事件消息处理
     * @param  [type] $params [description]
     * @param  [type] $json   [description]
     * @return [type]         [description]
     */
    private function _process_xcx_event($params,$json)
    {
        switch ($json->Event) {
            case 'weapp_audit_success'://审核成功
                    
            case 'weapp_audit_fail'://审核失败
                $this->_xcx_shenhe($params,$json);
                break;
            
            default:
                $this->json_do->out_put();
                break;
        }
    }
    /**********以下是业务处理************/
    /**
     * [_xcx_shenhe description]
     * @param  [type] $params [description]
     * @param  [type] $json   [description]
     * @return [type]         [description]
     */
    private function _xcx_shenhe($params,$json)
    {
        $xcxAppDao = XcxAppDao::i();
        $m_xcx_app = $xcxAppDao->getOne(['aid'=>$params['aid'],'app_id'=>$params['app_id']]);
        if(!$m_xcx_app || $m_xcx_app->audit_status != 2)
            $this->json_do->set_error('004','待发布小程序不存在');
        $data['update_time']=time();
        if($json->Event == 'weapp_audit_success')
        {
            $data['audit_status'] = 0;
            $data['audit_reason'] = '审核成功';
            $data['last_success_version'] = $m_xcx_app->user_version;
        }
        else
        {
            $data['audit_status'] = 1;
            $data['audit_reason'] = $json->Reason;
        }

        if($xcxAppDao->update($data,['id'=>$m_xcx_app->id]))
        {

            //小程序自动发布上线
            if($json->Event == 'weapp_audit_success')
            {
                try {
                    $authInfo = $this->_getAuthInfo($params['visit_id'],$params['app_id']);
                    //授权小程序的access_token
                    $access_token = $authInfo['data']['authorizer_access_token'];
                    $xcx_sdk = new xcx_sdk;
                    $res = $xcx_sdk->release($access_token);
                    log_message('error',"发布小程序-res:".json_encode($res).",aid:{$params['aid']},app_id:{$params['app_id']}");
                } catch (Exception $e) {
                    log_message('error','发布小程序失败-'.$e->getMessage().",aid:{$params['aid']},app_id:{$params['app_id']}");
                    $this->json_do->set_error('005','发布失败-'.$e->getMessage());
                }
                
                
            }

            $this->json_do->out_put();
        }
        else
            $this->json_do->set_error('005','处理失败');
    }
    /**
     * 获取小程序授权信息
     * @return [type] [description]
     */
    private function _getAuthInfo($visit_id,$app_id)
    {
            //此处采用新的scrm
            $scrm_sdk = new scrm_sdk('scrm_new');
            $params['visit_id']=(int)$visit_id;
            $params['appid'] = $app_id;
            $res = $scrm_sdk->getXcxInfo($params);
            if(isset($res['data']['authorizer_appid']))
                return $res;
            else
                throw new Exception("小程序未授权或授权过期");
        
    }
}