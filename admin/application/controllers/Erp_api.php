<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/1/29
 * Time: 13:57
 */
use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountDao;

class Erp_api extends crud_controller
{
  /**
   * 获取erp信息
   */
    public function get_user_info()
    {
        $type = $this->input->post_get('type');
        $value = $this->input->post_get('value');

        if(empty($value))
            $this->json_do->set_error('001', '关键词不能为空');

        $erp_sdk = new erp_sdk();
        $wmMainCompanyAccountDao = MainCompanyAccountDao::i();
        $model = null;
        try
        {
            if($type == 'user_id')//user_id查询
            {
                $id = intval($value);
                $model = $wmMainCompanyAccountDao->getOne(['user_id'=>$id], 'id,aid,username,img,sex,user_id,visit_id');
                if($model)
                {
                    $params[] = (int)$model->visit_id;
                    $params[] = (int)$value;
                    $res = $erp_sdk->getUserById($params);
                }
                else
                {
                    $res = null;
                }

            }
            elseif($type == 'visit_id')//visit_id查询
            {
                $id = intval($value);
                $model = $wmMainCompanyAccountDao->getOne(['visit_id'=>$id], 'id,aid,username,img,sex,user_id,visit_id');
                if($model)
                {
                    $params[] = (int)$value;
                    $params[] = (int)$model->user_id;
                    $res = $erp_sdk->getUserById($params);
                }
                else
                {
                    $res = null;
                }

            }
            else//默认手机号码查询
            {
              $params[] = $value;
              $res = $erp_sdk->getUserByPhone($params);

              if(isset($res['user_id']) && $res['user_id'] > 0)
              {
                $model = $wmMainCompanyAccountDao->getOne(['user_id'=>$res['user_id']], 'id,aid,username,img,sex,user_id,visit_id');
              }
            }

        }catch(Exception $e)
        {
          $res = $e->getMessage();
        }

        $data['model'] = $model;
        $data['erp_model'] = $res;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}