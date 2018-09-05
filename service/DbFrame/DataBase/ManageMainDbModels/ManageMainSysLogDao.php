<?php
namespace Service\DbFrame\DataBase\ManageMainDbModels;
/**
* 系统日志
*/
class ManageMainSysLogDao extends BaseDao
{
    /**
     * [slog 记录系统日志]
     * @param  varchar $log_description 描述
     * @param  $type            类型
     */
    protected function slog($log_description,$log_user,$log_ref_id=null,$type=0)
    {
        $v_data['type'] = $type;
        $v_data['log_user'] = $log_user;
        $v_data['log_ip'] = $this->input->ip_address();
        $v_data['time'] = time();
        $v_data['log_ref_id'] = $log_ref_id;
        $v_data['log_description'] = $log_description;
        $this->create($v_data);
    }
}