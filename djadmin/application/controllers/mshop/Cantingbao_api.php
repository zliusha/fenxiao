<?php
use Service\Cache\WmDianwodaShopCache;
/**
 * 点我达商户后台接口
 */
use Service\Cache\Wm\WmCantingbaoConfigCache;
use Service\DbFrame\DataBase\WmShardDbModels\WmCantingbaoConfigDao;

class Cantingbao_api extends wm_service_controller
{
    /**
     * 商户信息
     */
    public function setting()
    {
        $rules = [
            ['field' => 'app_key', 'label' => '商户Key', 'rules' => 'trim|required'],
            ['field' => 'app_secret', 'label' => '商户秘钥', 'rules' => 'trim|required']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        try{

            $wmCantingbaoConfigDao = WmCantingbaoConfigDao::i($this->s_user->aid);
            $mCantingbaoConfig = $wmCantingbaoConfigDao->getOne(['aid'=>$this->s_user->aid]);
            $data['aid'] = $this->s_user->aid;
            $data['app_key'] = $fdata['app_key'];
            $data['app_secret'] = $fdata['app_secret'];
            if($mCantingbaoConfig)
            {
                $wmCantingbaoConfigDao->update($data, ['aid'=>$this->s_user->aid]);
            }
            else
            {
                $wmCantingbaoConfigDao->create($data);
            }
            // 删除缓存
            $wmCantingbaoConfigCache = new WmCantingbaoConfigCache(['aid'=>$this->s_user->aid]);
            $wmCantingbaoConfigCache->delete();

            $wm_cantingbao_bll = new wm_cantingbao_bll();
            $wm_cantingbao_bll->updatePushUrl(['aid'=>$this->s_user->aid]);


            $this->json_do->set_msg('设置成功');
            $this->json_do->out_put();
        }catch(Exception $e){

            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 商户信息
     */
    public function info()
    {
        $wmCantingbaoConfigDao = WmCantingbaoConfigDao::i($this->s_user->aid);
        $mCantingbaoConfig = $wmCantingbaoConfigDao->getOne(['aid'=>$this->s_user->aid]);

        $data['config'] = $mCantingbaoConfig;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
