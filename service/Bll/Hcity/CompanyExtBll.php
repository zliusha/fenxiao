<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/7/25
 * Time: 16:17
 */
namespace Service\Bll\Hcity;

use Service\Bll\BaseBll;

use Service\Exceptions\Exception;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopExtDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityCompanyExtDao;

class CompanyExtBll extends BaseBll
{
    /**
     * 通过邀请码设置邀请者
     * @param array input uid visit_id aid 必须
     * @param string $inviterCode
     * @return int
     * @throws Exception
     * @author liusha
     */
    public function setInviterByCode(array $input, string $inviterCode)
    {
        $data['visit_id'] = $input['visit_id'];
        $data['aid'] = $input['aid'];

        if (!empty($inviterCode)) {
            $inviter = HcityUserDao::i()->getOne(['id_code' => $inviterCode]);
            if (empty($inviter)) {
                //去查店铺码
                $shop = HcityShopExtDao::i()->getOne(['id_code' => $inviterCode]);
                if (empty($shop)) {
                    throw new Exception('邀请码错误');
                }
                //设置邀请店铺id
                $data['inviter_shop_id'] = $shop->shop_id;
            } else {
                if ($inviter->id == $input['uid']) {
                    throw new Exception('邀请码错误');
                }
                //设置邀请人id
                $data['inviter_uid'] = $inviter->id;
            }
            $data['inviter_code'] = $inviterCode;
        }

        $hcityCompanyExtDao = HcityCompanyExtDao::i();
        $hcityCompanyExt = $hcityCompanyExtDao->getOne(['aid' => $input['aid']]);
        if ($hcityCompanyExt) {
            return $hcityCompanyExtDao->update($data, ['id' => $hcityCompanyExt->id]);
        } else {
            $data['shard_node'] = '0-0-0';
            return $hcityCompanyExtDao->create($data);
        }
    }
}