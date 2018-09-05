<?php
/**
 * @Author: binghe
 * @Date:   2016-07-27 15:40:31
 * @Last Modified by:   binghe
 * @Last Modified time: 2017-11-01 19:02:28
 */
/**
 * 上传类
 */
class Common_file extends base_controller
{
   
    public $type_array = array('tmp', 'excel','inc');
    //上传文件
    public function upload()
    {
        $type = $this->input->get('type');
        $http_url = $this->input->get('http_url'); //是否返回全路径
        if (!in_array($type, $this->type_array)) {
            $this->json_do->set_error('请选择上传文件类类型');
        }

        $ci_uploads = new ci_uploads();
        $file_data = $ci_uploads->do_upload(array('folder' => 'dj/' . $type, 'field' => 'imgFile', 'up_config' => ['allowed_types' => '*']));
        if ($file_data) {
            if ($http_url) {
                $file_path = conver_picurl($file_data['new_filepath']);
            } else {
                $file_path = $file_data['new_filepath'];
            }
            $data['url']=$file_path;
            //防止编辑器自带相对路径
            $data['new_filepath'] = $file_data['new_filepath'];
            $this->json_do->set_data($data);
            $this->json_do->out_put();

        } else {
            $errors = $ci_uploads->display_errors();
            log_message('error', __METHOD__ . $errors);
            $this->json_do->set_error('001',$errors);
        }
    }
}
