<?php

/**
 * @Author: binghe
 * @Date:   2018-08-07 15:11:22
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-09 11:15:36
 *
 */
use Service\Bll\Hcity\Xcx\XcxBll;
use Service\Enum\XcxTemplateMessageEnum;


/**
 * test_xcx
 */
class Test_xcx extends base_controller
{

    public function get_form_id()
    {
        echo $a;
        $openId = 'ox5c65GFkb7OiR9QChuo14YfhCbc';
        $formId = (new XcxFormIdBll())->get($openId);
        var_dump($formId);
    }
    public function get_token()
    {
        $xcxBll = new XcxBll();
        echo $xcxBll->getAccessToken();
    }
    public function send_message()
    {
        $xcxBll                = new XcxBll();
        $params=[
            'openid'=>'ox5c65GFkb7OiR9QChuo14YfhCbc',
            'type'=>'冰河测试',
            'change_money'=>'3.00',
            'change_time'=>'2018年08月08日',
            'current_money'=>'100.00'
        ];
        
        $xcxBll->pushMessageFac($params,XcxTemplateMessageEnum::MONEY_CHANGE);
    }
}
