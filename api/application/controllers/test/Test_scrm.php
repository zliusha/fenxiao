<?php
/**
 * @Author: binghe
 * @Date:   2018-05-25 14:13:18
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-05-25 14:22:17
 */
/**
* test scrm
*/
class Test_scrm extends base_controller
{
    
    public function index()
    {
        $erp_sdk = new erp_sdk;
        //[visit_id,user_id]
        $params =[9169744,9095087];
        $result = $erp_sdk->getToken($params);
        var_dump($result);
        echo '<br/>---------------------<br/>';
        $input['token'] = $result['token'];
        $scrm_sdk = new scrm_sdk('scrm_new');
        $res = $scrm_sdk->getMenu($input);
        var_dump($res);
    }
}