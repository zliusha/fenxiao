<?php
/**
 * @Author: binghe
 * @Date:   2017-09-15 14:47:19
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:57:50
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmShopAccessDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopAccessRecordDao;
/**
* 访问
*/
class wm_access_bll extends base_bll
{
    /**
     * 店铺访问统计
     * @param  [type] $shop_id [description]
     * @param  [type] $uid     [description]
     * @return [type]          [description]
     */
    public static function shop($aid,$shop_id,$uid)
    {
        if(empty($aid) || empty($shop_id) || empty($uid))
            return;
        $wmShopAccessDao = WmShopAccessDao::i($aid);
        $wmShopAccessRecordDao = WmShopAccessRecordDao::i($aid);
        $ip=get_ip();

        $today_str=date('Y-m-d');
        $m_one=$wmShopAccessDao->getOne(['aid'=>$aid,'shop_id'=>$shop_id,'day'=>$today_str]);

        //主表记录
        if($m_one)  //已有记录
        {
            
            //pv+1
            $data['pv']=$m_one->pv+1;
            //ip
            $m_record_ip=$wmShopAccessRecordDao->getOne(['aid'=>$aid,'shop_id'=>$shop_id,'ip'=>$ip,'day'=>$today_str]);
            if(!$m_record_ip)
                $data['ip']=$m_one->ip+1;
            //uv
         
            //当前用户已记录
            $m_record=$wmShopAccessRecordDao->getOne(['aid'=>$aid,'shop_id'=>$shop_id,'uid'=>$uid,'day'=>$today_str]);
            if(!$m_record)
                $data['uv']=$m_one->uv+1;
                

            //更新统计
            $wmShopAccessDao->update($data,['id'=>$m_one->id]);
            //插入
        }
        else
        {
            //统计记录
            $data['aid']=$aid;
            $data['shop_id']=$shop_id;
            $data['day']=$today_str;
            $data['pv']=1;
            $data['uv']=1;
            $data['ip']=1;
            $wmShopAccessDao->create($data);
        }
        //明细表记录
        $r_data['day']=$today_str;
        $r_data['ip']=$ip;
        $r_data['uid']=$uid;
        $r_data['aid']=$aid;
        $r_data['shop_id']=$shop_id;
        $wmShopAccessRecordDao->create($r_data);

    }
    public static function goods($aid,$shop_id,$goods_id,$uid)
    {
        //统计商品的访问数据，目前无需求
        //统计店铺
        self::shop($aid,$shop_id,$uid);
    }
    /**
     * 转为报表数据单元
     * @param  object $list   对象
     * @param  [type] $s_time [description]
     * @param  [type] $e_time [description]
     * @param  int $skip    多少天为一数据记录
     * @return [type]         [description]
     */
    public static function to_items($list,$s_time,$e_time,$skip)
    {
        $items=[];
        $t_time=$s_time;

        while ($t_time<=$e_time) {
            $item=['text'=>'','uv'=>0,'pv'=>0,'ip'=>0,'range_stime'=>'','range_etime'=>''];
            $i=0;
            while ($i<$skip && $t_time<=$e_time) {
                //开始时间
               if($i==0)
               {
                $item['range_stime']=date('Y-m-d',strtotime($t_time));
               }
                if($i==($skip-1))
               {
                $item['range_etime']=date('Y-m-d',strtotime($t_time));
               }
               //首尾给文本
               if($t_time==$s_time || $t_time==$e_time || $skip==1)
               {
                $item['text']=date('Y-m-d',strtotime($t_time));
               }
               //统计
               $list_item=array_find($list,'day',$t_time);
               if($list_item)
               {
                    $item['uv']+=$list_item['uv'];
                    $item['pv']+=$list_item['pv'];
                    $item['ip']+=$list_item['ip'];
               }
               //累加
               $i++;
               $t_time=date('Y-m-d',strtotime("+1 day",strtotime($t_time)));

            }
            array_push($items,$item);
        }
        return $items;
    }
}