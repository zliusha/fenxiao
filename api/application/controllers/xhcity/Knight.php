<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/8/27
 * Time: 9:42
 */
use Service\Support\FLock;
use Service\Bll\Hcity\UserBll;
use Service\Bll\Hcity\QsManageBll;
use Service\Bll\Hcity\ShopExtBll;
use Service\Cache\Hcity\HcityQsCache;
use Service\Cache\Hcity\HcityUserCache;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\Enum\BalanceRecordTypeEnum;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityQsDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcitySalesAccountDao;

class Knight extends xhcity_user_controller
{
    /**
     * 获取黑咖骑士信息
     */
    public function get_info()
    {
        $data['qs_info'] = null;
        try{
            $hcityQsCache = new HcityQsCache(['uid'=>$this->s_user->uid]);
            $data['qs_info'] = $hcityQsCache->getDataASNX();
        }catch(\Exception $e){}

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 黑咖骑士申请
     */
    public function apply()
    {
        if (!FLock::getInstance()->lock('Knight:apply:uid:'.$this->s_user->uid)) {
            $this->json_do->set_error('005', '操作过于频繁,请稍后再试。');
        }
        $rules = [
            ['field' => 'name', 'label' => '姓名', 'rules' => 'trim|required'],
            ['field' => 'mobile', 'label' => '手机号', 'rules' => 'trim|required|preg_key[MOBILE]'],
            ['field' => 'region', 'label' => '地址ID', 'rules' => 'trim|required'],
            ['field' => 'region_name', 'label' => '省市区名', 'rules' => 'trim|required'],
            ['field' => 'experience_type', 'label' => '骑士类型', 'rules' => 'trim|required|numeric'],
            ['field' => 'sales_id', 'label' => '销售ID', 'rules' => 'trim|numeric'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        $hcityQsDao = HcityQsDao::i();
        $hcityQs = $hcityQsDao->getOne(['uid'=>$this->s_user->uid]);
        if($hcityQs)
            $this->json_do->set_error('005', '已申请');
        $fdata['uid'] = $this->s_user->uid;
        $fdata['sales_id'] = (int)$fdata['sales_id'];
        $id = HcityQsDao::i()->create($fdata);
        if($id > 0)
        {
            $this->json_do->set_msg('申请成功');
            $this->json_do->out_put();
        }
        else
        {
            $this->json_do->set_error('005', '申请失败');
        }
    }

    /**
     * 获取销售账号id
     */
    public function get_sales_id()
    {
        $data['sales_id'] = 0;
        // 当前用户信息
        $hcityUserCache = new HcityUserCache(['uid'=>$this->s_user->uid]);
        $user = $hcityUserCache->getDataASNX();
        if($user->inviter_uid>0)
        {
            //获取邀请人信息
            $mUser = HcityUserDao::i()->getOne(['id' => $user->inviter_uid]);
            if($mUser)
            {
                //获取邀请人骑士信息
                $mQs = HcityQsDao::i()->getOne(['uid'=>$mUser->id]);;
                if($mQs)
                    $data['sales_id'] = $mQs->sales_id;
            }
        }

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * （销售员）列表
     */
    public function sales_list()
    {
        $data['sales_id'] = 0;

        $hcitySalesAccountDao = HcitySalesAccountDao::i();
        // 当前用户信息
        $hcityUserCache = new HcityUserCache(['uid'=>$this->s_user->uid]);
        $user = $hcityUserCache->getDataASNX();
        if(!empty($user->inviter_uid) && $user->inviter_uid>0)
        {
            //获取邀请人信息
            $mUser = HcityUserDao::i()->getOne(['id' => $user->inviter_uid]);
            if($mUser)
            {
                //获取邀请人骑士信息
                $mQs = HcityQsDao::i()->getOne(['uid'=>$mUser->id]);;
                if($mQs)
                    $data['sales_id'] = $mQs->sales_id;

                // 判断是否是销售账号
                $mSaleAccount = $hcitySalesAccountDao->getOne(['mobile'=>$mUser->mobile, 'status'=>1]);
                if($mSaleAccount)
                    $data['sales_id'] = $mSaleAccount->id;
            }
        }
        $data['sales_list'] = HcitySalesAccountDao::i()->getAllArray(['status'=>1], 'id,name,mobile,region_name');
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 我的收入
     */
    public function my_income()
    {
        // 校验嘿卡骑士身份
        $mQs = $this->valid_qs();
        // 更新钱相关信息
        $hcityQsDao = HcityQsDao::i();
        $mQs = $hcityQsDao->getOne(['uid' => $mQs->uid]);
        // 本周时间
        $beginWeekTime = mktime(0,0,0,date("m"),date("d")-date("w")+1,date("Y"));
        // 当月
        $thisMonthBeginTime = mktime(0,0,0,date('m'),1,date('Y'));
        // 上月
        $lastMonthBeginTime1 = mktime(0,0,0,date('m')-1,1,date('Y'));
        $lastMonthBeginTimeKey1 = date('m',$lastMonthBeginTime1);
        // 上2月
        $lastMonthBeginTime2 = mktime(0,0,0,date('m')-1,1,date('Y'));
        $lastMonthBeginTimeKey2 = date('m',$lastMonthBeginTime1);
        // 上3月
        $lastMonthBeginTime3 = mktime(0,0,0,date('m')-1,1,date('Y'));
        $lastMonthBeginTimeKey3 = date('m',$lastMonthBeginTime1);
        $time_arr = [
            'curr_week_money' => ['stime'=>$beginWeekTime,'etime'=>time(),'alias'=>'本周'],
            'curr_month_money' =>  ['stime'=>$thisMonthBeginTime,'etime'=>time(),'alias'=>'本月'],
            'last_month_money1' => ['stime'=>$lastMonthBeginTime1,'etime'=>$thisMonthBeginTime,'alias'=>$lastMonthBeginTimeKey1],
            'last_month_money2' => ['stime'=>$lastMonthBeginTime2,'etime'=>$lastMonthBeginTime1,'alias'=>$lastMonthBeginTimeKey2],
            'last_month_money3' => ['stime'=>$lastMonthBeginTime3,'etime'=>$lastMonthBeginTime2,'alias'=>$lastMonthBeginTimeKey3],
        ];
        $ydym_type = BalanceRecordTypeEnum::INVITE_OPEN_YDYM;
        $knight_type = BalanceRecordTypeEnum::INVITE_KNIGHT;
        $merchant_by_knight_type = BalanceRecordTypeEnum::INVITE_MERCHANT_BY_KNIGHT;
        $knight_newbit_type = BalanceRecordTypeEnum::KNIGHT_NEWBIE;
        $where = "uid={$mQs->uid} AND time >{$mQs->audit_time} AND type in({$ydym_type},{$knight_type},{$merchant_by_knight_type},{$knight_newbit_type})";
        $qsmanageBll = new QsManageBll();
        $return  = $qsmanageBll->incomeStatistics($where, $time_arr);
        $data = [
            'total_income' => $mQs->income,
            'curr_week_money' => ['money'=>$return[0]['curr_week_money'],'alias'=>'本周'],
            'curr_month_money' =>  ['money'=>$return[0]['curr_month_money'], 'alias'=>'本月'],
            'last_month_money1' => ['money'=>$return[0]['last_month_money1'], 'alias'=>$lastMonthBeginTimeKey1],
            'last_month_money2' => ['money'=>$return[0]['last_month_money2'], 'alias'=>$lastMonthBeginTimeKey2],
            'last_month_money3' => ['money'=>$return[0]['last_month_money3'], 'alias'=>$lastMonthBeginTimeKey3],
        ];

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 收入明细
     */
    public function income_list()
    {
        $rules = [
            ['field' => 'current_page', 'label' => '当前页数', 'rules' => 'numeric'],
            ['field' => 'page_size', 'label' => '分页大小', 'rules' => 'numeric'],
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        // 校验嘿卡骑士身份
        $mQs = $this->valid_qs();
        $qsmanageBll = new QsManageBll();
        $data = $qsmanageBll->getIncomeList($this->s_user->uid, ['stime'=>$mQs->audit_time]);

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 收入排行榜
     */
    public function income_rank()
    {
        // 校验嘿卡骑士身份
        $this->valid_qs();

        $qsManageBll = new QsManageBll();
        $data = $qsManageBll->getIncomeRank($this->s_user->uid);

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 我的骑士团
     */
    public function my_knights()
    {
        $rules = [
            ['field' => 'current_page', 'label' => '当前页数', 'rules' => 'numeric'],
            ['field' => 'page_size', 'label' => '分页大小', 'rules' => 'numeric'],
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        // 校验嘿卡骑士身份
        $mQs = $this->valid_qs();

        $input = [
            'inviter_uid'=>$mQs->uid,
            'status' => 1
        ];
        $qsmanageBll = new QsManageBll();
        $data = $qsmanageBll->qsXcxList($input);

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 我邀请的商家
     */
    public function my_shop_list()
    {
        $rules = [
            ['field' => 'current_page', 'label' => '当前页数', 'rules' => 'numeric'],
            ['field' => 'page_size', 'label' => '分页大小', 'rules' => 'numeric'],
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        // 校验嘿卡骑士身份
        $mQs = $this->valid_qs();

        $input = [
            'stime'=>$mQs->audit_time
        ];
        $shopExtBll = new ShopExtBll();
        $data = $shopExtBll->getListByInviterUid($mQs->uid, $input);

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 任务信息
     */
    public function task_info()
    {
        // 校验嘿卡骑士身份
        $mQs = $this->valid_qs();

        // 获取所有被邀请人成为骑士的数量
        $qsmanageBll = new QsManageBll();
        $user_input = [
            'is_qs'=>1
        ];
        $total_qs_count = $qsmanageBll->getQsCountByInviterUid($mQs->uid, $user_input);
        // 新手任务

        // 长期邀请骑士任务
        $valid_user_input = [
            'is_qs'=>1,
            'newbie_task_status'=>1
        ];
        $valid_qs_count = $qsmanageBll->getQsCountByInviterUid($mQs->uid, $valid_user_input);
        // 获取全部邀请店铺数量
        $input = [
            'stime'=>$mQs->audit_time
        ];
        $shopExtBll = new ShopExtBll();
        $total_shop_count = $shopExtBll->getCountByInviterUid($mQs->uid, $input);
        // 长期商家入住商圈任务
        $sq_input = [
            'shop_task_status'=>1
        ];
        $sq_shop_count = $shopExtBll->getTaskCountByInviterUid($mQs->uid, $sq_input);

        // 长期开通YDYM任务
        $ydym_input = [
            'ydym_task_status'=>1
        ];
        $ydym_shop_count = $shopExtBll->getTaskCountByInviterUid($mQs->uid, $ydym_input);


        $data = [
            'total_qs_count' => $total_qs_count, // 总邀请嘿卡骑士用户
            'valid_qs_count' => $valid_qs_count, // 完成新手任务骑士用户
            'total_shop_count' => $total_shop_count, // 送邀请店铺数量
            'sq_shop_count' => $sq_shop_count, // 有效入住商圈店铺数量
            'ydym_shop_count' => $ydym_shop_count // 开通一店一码店铺数量
        ];
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
