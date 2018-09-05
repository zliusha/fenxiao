<?php
/**
 * @Author: binghe
 * @Date:   2017-11-27 10:16:17
 * @Last Modified by:   binghe
 * @Last Modified time: 2017-11-27 13:29:06
 */
/**
* 公司账号
*/
class main_company_account_bll extends base_bll
{
    
    //得到s_user
    function get_s_user($m_main_company_account)
    {
        $s_main_company_account_do = new s_main_company_account_do();
        $s_main_company_account_do->id=$m_main_company_account->id;
        $s_main_company_account_do->aid=$m_main_company_account->aid;
        $s_main_company_account_do->username=$m_main_company_account->username;
        $s_main_company_account_do->visit_id = $m_main_company_account->visit_id;
        $s_main_company_account_do->user_id = $m_main_company_account->user_id;
        $s_main_company_account_do->shop_id = $m_main_company_account->shop_id;
        $s_main_company_account_do->is_admin = $m_main_company_account->is_admin == 1 ? 1 : 0;
        return $s_main_company_account_do;
    }
}