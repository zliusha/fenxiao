<?php
/**
 * @Author: binghe
 * @Date:   2018-03-27 16:18:19
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 11:20:09
 */
/**
* 小程序
*/
use Service\DbFrame\DataBase\WmMainDbModels\XcxAppDao;
use Service\DbFrame\DataBase\WmShardDbModels\XcxQrcodeDao;
class Xcx extends xcx_controller
{
    public function info()
    {
        //获取小程序信息
        $xcxAppDao = XcxAppDao::i();
        $m_xcx_app = $xcxAppDao->getOne(['aid'=>$this->aid,'app_id'=>$this->app_id]);
        if(!$m_xcx_app)
            $this->json_do->set_error('小程序信息不存在');
        $data['is_new'] = empty($m_xcx_app->last_success_version);
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 小程序二维码
     * @return [type] [description]
     */
    public function qrcode()
    {
        $rules=[
            ['field'=>'scene','label'=>'参数','rules'=>'trim']
            ,['field'=>'page','label'=>'跳转路径','rules'=>'trim']
            ,['field'=>'width','label'=>'二维码的宽度','rules'=>'trim|numeric']
        ];

        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);
        //默认参数
        if(empty($fdata['scene']))
            $fdata['scene'] = 't=index';
        //参数加码
        // $fdata['scene'] = urlencode($fdata['scene']);
        //宽度默认430
        if(empty($fdata['width']))
            $fdata['width'] = 430;

        //获取小程序信息
        $xcxAppDao = XcxAppDao::i();
        $m_xcx_app = $xcxAppDao->getOne(['aid'=>$this->aid,'app_id'=>$this->app_id]);
        if(!$m_xcx_app)
            $this->json_do->set_error('小程序信息不存在');
        $xcxInfo = null;
        //兼容头像,昵称,公司 如老数据都已存在可注释
        if(empty($m_xcx_app->nick_name) || empty($m_xcx_app->user_name) || empty($m_xcx_app->head_img))
        {
            $xcxInfo = $this->_getXcxInfo();
            $xcxUpdata['nick_name'] = $xcxInfo['data']['nick_name'];
            $xcxUpdata['user_name'] = $xcxInfo['data']['user_name'];
            $xcxUpdata['head_img'] = $xcxInfo['data']['head_img'];
            $xcxAppDao->update($xcxUpdata,['aid'=>$m_xcx_app->aid,'id'=>$m_xcx_app->id]);

            //减少数据请求,直接赋值
            $m_xcx_app->nick_name = $xcxUpdata['nick_name'];
            $m_xcx_app->user_name = $xcxUpdata['user_name'];
            $m_xcx_app->head_img = $xcxUpdata['head_img'];
        }

        $fdata['key'] = md5($this->aid.$this->app_id.urlencode($fdata['scene']).$fdata['page'].$fdata['width']);
        $xcxQrcodeDao = XcxQrcodeDao::i($this->aid);
        $m_xcx_qrcode = $xcxQrcodeDao->getOne(['aid'=>$this->aid,'app_id'=>$this->app_id,'key'=>$fdata['key']]);

        //二维码已存在
        if($m_xcx_qrcode)
        {
            $qrcodeUrl = LOCAL_UPLOAD_URL . $m_xcx_qrcode->local_qrcode_url;
        }
        else //不存在,则生成小程序
        {
            if(!$xcxInfo)
                $xcxInfo = $this->_getXcxInfo();
                // log_message('error',__METHOD__.json_encode($xcxInfo));
            
            //获取小程序二维码流
            try {
                $xcx_sdk = new xcx_sdk;
                $input = $fdata;
                unset($input['key']);
                $contents = $xcx_sdk->getQrcodeStreamContents($input,$xcxInfo['data']['authorizer_access_token']);
            } catch (Exception $e) {
                log_message('error',__METHOD__.'-'.$e->getMessage());
                $this->json_do->set_error('005','获取二维码失败');
            }
           
            
            //图片保存至服务器
            $dirpath = 'xcx/'.date('Ymd').'/';
            $absPath= UPLOAD_PATH . $dirpath;
            if(!is_dir($absPath))
                mkdir($absPath,0700);
            $filename = $fdata['key'] . '.png';
            $filepath = $absPath. $filename;
            file_put_contents($filepath, $contents);
            //保存
            $saveData['aid'] = $this->aid;
            $saveData['app_id'] = $this->app_id;
            $saveData['local_qrcode_url'] = $dirpath . $filename;
            $saveData['scene'] = $fdata['scene'];
            $saveData['page'] = $fdata['page'];
            $saveData['key'] = $fdata['key'];
            $xcxQrcodeDao->create($saveData);
            $qrcodeUrl = LOCAL_UPLOAD_URL.$saveData['local_qrcode_url'];

        }
        $data['nick_name'] = $m_xcx_app->nick_name;
        $data['head_img'] = $m_xcx_app->head_img;
        $data['qrcode_url'] = $qrcodeUrl;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 获取小程序信息
     * @return array [description]
     */
    private function _getXcxInfo()
    {
        $scrm_sdk = new scrm_sdk;
        try {
            $params['visit_id'] = $this->visit_id;
            $params['appid'] = $this->app_id;
            $res = $scrm_sdk->getXcxInfo($params);
            if(isset($res['data']['authorizer_appid']))
                return $res;
            else
                throw new Exception("小程序未授权或授权过期");
        } catch (Exception $e) {
            $this->json_do->set_error('005',$e->getMessage());
        }
    }

}