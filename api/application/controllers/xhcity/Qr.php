<?php

/**
 * @Author: binghe
 * @Date:   2018-08-01 11:51:10
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-21 15:27:21
 */
use Service\Bll\Hcity\Xcx\XcxBll;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityXcxQrDao;
/**
 * Qr
 */
class Qr extends xhcity_controller
{
    /**
     * 获取scene信息
     * @author binghe 2018-08-21
     */
    public function scene()
    {
        $rules=[
            ['field'=>'s','label'=>'参数','rules'=>'trim|numeric|required']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);
        $mHcityXcxQr = HcityXcxQrDao::i()->getOne(['qr_id'=>$fdata['s']]);
        if($mHcityXcxQr)
        {
            $this->json_do->set_data(['scene'=>$mHcityXcxQr->scene]);
            $this->json_do->out_put();
        }
        else {
            $this->json_do->set_error('004','scene不存在');
        }
    }
    /**
     * 小程序二维码
     * @author binghe 2018-08-20
     */
    public function xcx()
    {
        $rules=[
            ['field'=>'scene','label'=>'参数','rules'=>'trim|required']
            ,['field'=>'page','label'=>'跳转路径','rules'=>'trim']
            ,['field'=>'width','label'=>'二维码的宽度','rules'=>'trim|numeric']
            ,['field'=>'line_color','label'=>'线条颜色','rules'=>'trim']
            ,['field'=>'is_hyaline','label'=>'透明底色','rules'=>'trim|numeric|in_list[0,1]']
        ];


        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);

        $params = [
        ];

        // 参数为数组
        $scene = @json_decode($fdata['scene'],true);
        if(!$scene)
            $this->json_do->set_error('001','secne参数出错');

        ksort($fdata);
        //缓存链接
        $key   = md5(create_linkstring_urlencode($fdata));
        $hcityXcxQrDao = HcityXcxQrDao::i();
        $mHcityXcxQr = $hcityXcxQrDao->getOne(['key'=>$key],'qr_id,qr_img');
        //直接输出缓存图片
        if($mHcityXcxQr)
        {
            $data['img_url'] = LOCAL_UPLOAD_URL.$mHcityXcxQr->qr_img;
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        }

        $qrId = create_order_number();
        //默认参数
        $params['scene'] = 's='.$qrId;
        

        //宽度默认430
        if($fdata['width'])
            $params['width'] = $fdata['width'];

        //默认跳转至过滤页面
        if($fdata['page'])
            $params['page'] = $fdata['page'];

        //配置线条颜色
        if($fdata['line_color'])
        {
            $rgb = @json_decode($fdata['line_color']);
            if($rgb && valid_keys_exists(['r','g','b'],$rgb))
                $params['line_color'] = json_encode($rgb);
        }
        
        //是否需要透明底色
        if($fdata['is_hyaline'] == 1)
            $params['is_hyaline'] = true;
        else
            $params['is_hyaline'] = false;

        $xcxBll = new XcxBll;
        $dirPath = 'xhcity/'.date('Ymd').'/';
        $filePath = $xcxBll->getQrcodeStreamContents($params,$dirPath);

        //保存至缓存表
        $qrData = ['qr_id'=>$qrId,'key'=>$key,'scene'=>json_encode($scene),'qr_img'=>$filePath];
        if($hcityXcxQrDao->create($qrData))
        {
            //输出
            $data['img_url'] = LOCAL_UPLOAD_URL.$filePath;
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        }
        else
            $this->json_do->set_error('005','保存二维码出错');
        
    }
    /**
     * 普通二维码
     * @return [type] [description]
     * @author binghe 2018-08-20
     */
    public function index()
    {
        $fdata = $this->input->post();
        if(empty($fdata))
            $this->json_do->set_error('004','参数不能为空');
        $url = $this->_getUrl($fdata);
        $data['url'] = $url;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 获取参数
     * @param  array  $params [description]
     * @return [type]         [description]
     */
    private function _getUrl(array $params = [])
    {
        if (empty($params)) {
            return '';
        }

        $paramsStr = '';
        foreach ($params as $k => $v) {
            if (empty($paramsStr)) {
                $paramsStr .= $k . '=' . $v;
            } else {
                $paramsStr .= '&' . $k . '=' . $v;
            }

        }
        !empty($paramsStr) && $paramsStr = '?' . $paramsStr;
        return M_HCITY_URL . 'qr/hcity' . $paramsStr;
    }

}
