<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/8/10
 * Time: 19:08
 */
class Img extends xhcity_controller
{
    /**
     * 下在图片
     */
    public function down()
    {
        $rule=[
            ['field' => 'img_url', 'label' => '图片URL', 'rules' => 'trim|required']
        ];
        $this->check->check_form($rule);
        $fdata=$this->form_data($rule);

        $filename = md5($fdata['img_url']).'.png';
        
        //图片保存至服务器
        $dirpath = 'xhcity/'.date('Ymd').'/';
        $absPath= UPLOAD_PATH . $dirpath;

        if(file_exists($absPath.$filename))
        {
            $data['img_url'] = LOCAL_UPLOAD_URL.$dirpath . $filename;
        }
        else
        {
            $contents = file_get_contents($fdata['img_url']);
            if(!is_dir($absPath))
                mkdir($absPath,0700, true);
            $filepath = $absPath. $filename;
            @file_put_contents($filepath, $contents);
        }

        //保存
        $data['img_url'] = LOCAL_UPLOAD_URL.$dirpath . $filename;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}