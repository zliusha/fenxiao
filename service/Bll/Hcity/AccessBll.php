<?php

/**
 * @Author: binghe
 * @Date:   2018-08-15 11:41:14
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-16 14:03:59
 */
namespace Service\Bll\Hcity;

use Service\Bll\Hcity\Xcx\XcxBll;
use Service\Cache\Hcity\HcityAccessTmpCache;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityAccessTmpDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserWxbindDao;
use Service\Enum\XcxTemplateMessageEnum;

/**
 * 访问控制
 */
class AccessBll extends \Service\Bll\BaseBll
{
    /**
     * 门店访问
     * @param array $input 必填[open_id,aid,'shop_id','username','mobile',access_time,source]
     * @return [type] [description]
     * @author binghe 2018-08-15
     */
    public function shopAccess(array $input)
    {

        $hcityAccessTmpCache = new HcityAccessTmpCache(['open_id' => $input['open_id'], 'aid' => $input['aid'], 'shop_id' => $input['shop_id']]);
        $one                 = $hcityAccessTmpCache->getDataASNX();
        //记录不存在,代表首次访问,添加访问记录,并发送服务推送
        if ($one === null) {
            //创建访问记录
            $data['open_id'] = $input['open_id'];
            $data['aid']     = $input['aid'];
            $data['shop_id'] = $input['shop_id'];
            HcityAccessTmpDao::i()->create($data);
            //取商家openid
            $djUser = HcityUserDao::i()->getOne(['aid' => $data['aid']], 'id');
            if (!empty($djUser)) {
                $djUserWx = HcityUserWxbindDao::i()->getOne(['uid' => $djUser->id], 'open_id');
                if (!empty($djUserWx)) {
                    //发送新客访问消息
                    $xcxBll = new XcxBll();
                    $msg    = [
                        'openid'      => $djUserWx->open_id,
                        'username'    => $input['username'],
                        'mobile'      => $input['mobile'],
                        'access_time' => $input['access_time'],
                        'source'      => $input['source'],
                    ];
                    $xcxBll->pushMessageFac($msg, XcxTemplateMessageEnum::NEW_CUSTOMER_ACCESS);
                }
            }
        }

    }
}
