<?php
namespace Service\Bll\Hcity;
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/8/22
 * Time: 16:12
 * 库存管理
 */


use Service\Exceptions\Exception;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\HcityShardDb;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityGoodsDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityGoodsSkuDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsKzDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsSkuKzDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityWelfareGoodsDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityWelfareGoodsSkuDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityGoodsJzDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityBargainDao;

class StockBll extends \Service\Bll\BaseBll
{
    // 商圈库存
    const SQ_STOCK = 1;
    // 一店一码
    const YDYM_STOCK = 2;
    // 福利池库存
    const WELFARE_STOCK = 3;
    // 助力集赞库存
    const JZ_STOCK = 4;
    // 砍价活动库存
    const BARGAIN_STOCK = 5;

    /**
     * 检测商圈库存
     * @param int $stock_type 库存类型
     * @param int $aid
     * @param int $goods_id
     * @param array $items [[goods_id sku_id num]] 字段必须
     * @param int $activity_id
     * @throws Exception
     */
    public function checkStock(int $stock_type, int $aid, int $ext_id, array $items, int $activity_id=0)
    {
        switch($stock_type)
        {
            case self::SQ_STOCK: //检测商圈库存
                $this->_checkSq($aid, $ext_id,  $items);
                break;
            case self::YDYM_STOCK://检测YDYM库存
                $this->_checkYdym($aid, $ext_id,  $items);
                break;
            case self::WELFARE_STOCK://检测福利池库存
                $this->_checkWelfare($aid, $ext_id,  $items);
                break;
            case self::JZ_STOCK://检测助力集赞库存
                $this->_checkJz($aid, $ext_id,  $items, $activity_id);
                break;
            case self::BARGAIN_STOCK://检测砍价活动库存
                $this->_checkBargain($aid, $ext_id,  $items, $activity_id);
                break;
            default:
                throw new Exception("异常库存检测");
                break;
        }
    }
    /**
     * 变更库存 $changeMethod 默认扣减 setDec 回原setInc
     * @param int $aid
     * @param int $stock_type 1扣减商圈 2扣减一店一码 3扣减福利池
     * @param array $input ['goods_id', 'sku_id, 'num] 必须
     * @param string $changeMethod 默认扣减 setDec 回原 setInc
     * @param bool $is_trans
     * @return bool
     * @liusha
     */
    public function changeStock(int $stock_type, int $aid,  array $input, string $changeMethod='setDec',bool $is_trans=false)
    {
        switch($stock_type)
        {
            case self::SQ_STOCK: //商圈库存
                return $this->_changeSq($aid, $input, $changeMethod, $is_trans);
                break;
            case self::YDYM_STOCK://YDYM库存
                return $this->_changeYdym($aid, $input, $changeMethod, $is_trans);
                break;
            case self::WELFARE_STOCK://福利池库存
                return $this->_changeWelfare($aid, $input, $changeMethod, $is_trans);
                break;
            case self::JZ_STOCK://助力集赞库存
                return $this->_changeJz($aid, $input, $changeMethod, $is_trans);
                break;
            case self::BARGAIN_STOCK://砍价活动库存
                return $this->_changeBargain($aid, $input, $changeMethod, $is_trans);
                break;
            default:
                throw new Exception("异常库存修改");
                break;
        }
    }
    /**
     * 增加商品销量
     * @param int $stock_type 库存类型
     * @param int $aid
     * @param array $input ['goods_id', 'sku_id, 'num'] 必须 activity_id 可选
     * @param bool $is_trans
     * @return bool
     * @liusha
     */
    public function addSalesNum(int $stock_type, int $aid, array $input, bool $is_trans=false)
    {
        switch($stock_type)
        {
            case self::SQ_STOCK: //商圈销量
                return $this->_addCommonSalesNum($aid, $input,  $is_trans);
                break;
            case self::YDYM_STOCK://YDYM库存
                return $this->_addCommonSalesNum($aid, $input,  $is_trans);
                break;
            case self::WELFARE_STOCK://福利池
                return $this->_addWelfareSalesNum($aid, $input,  $is_trans);
                break;
            case self::JZ_STOCK://助力集赞
                $this->_addCommonSalesNum($aid, $input,  $is_trans);// TODO 此处不严格
                return $this->_addJzSalesNum($aid, $input,  $is_trans);
                break;
            case self::BARGAIN_STOCK://活动库存
                $this->_addCommonSalesNum($aid, $input,  $is_trans);// TODO 此处不严格
                return $this->_addBargainSalesNum($aid, $input,  $is_trans);
                break;
            default:
                throw new Exception("异常销量修改");
                break;
        }
    }

    /**
     * 检测商圈库存
     * @param int $aid
     * @param int $goods_id
     * @param array $items [[goods_id sku_id num]] 字段必须
     * @throws Exception
     */
    private function _checkSq(int $aid, int $goods_id, array $items)
    {
        $goods_sku_id_arr = array_column($items, 'sku_id');
        $goods_sku_ids = empty($goods_sku_id_arr) ? '0' : implode(',', $goods_sku_id_arr);
        $shcityGoodsDao = ShcityGoodsDao::i(['aid'=>$aid]);
        $shcityGoodsSkuDao = ShcityGoodsSkuDao::i(['aid'=>$aid]);

        $goods = $shcityGoodsDao->getOne(['aid'=>$aid,'id'=>$goods_id]);
        $sku_list = $shcityGoodsSkuDao->getAllArray("aid={$aid} AND goods_id={$goods_id} AND id in ({$goods_sku_ids})");
        if(!$goods || !$sku_list)
        {
            log_message('error', __METHOD__.'--goods_id--'.$goods_id);
            throw new Exception("商品信息不存在");
        }
        // 库存是充足
        $is_enough = true;
        foreach($items as $item)
        {
            $sku = array_find($sku_list, 'id', $item['sku_id']);
            if(!$sku)
                throw new Exception("商品信息不存在");

            //1结算商圈商品 开启自定义库存并且库存不足
            if($sku['is_hcity_stock_open']==1 && ($sku['hcity_stock_num'] <= 0 || $sku['hcity_stock_num'] < $item['num']))
            {
                log_message('error', __METHOD__.json_encode($item));
                $is_enough = false;
            }

        }
        if(!$is_enough)
            throw new Exception("商品{$goods->title}库存不足");
    }

    /**
     * 变更库存 $changeMethod 默认扣减 setDec 回原setInc
     * @param int $aid
     * @param int $stock_type 1扣减商圈 2扣减一店一码 3扣减福利池
     * @param array $input ['goods_id', 'sku_id, 'num] 必须
     * @param string $changeMethod 默认扣减 setDec 回原 setInc
     * @param bool $is_trans
     * @return bool
     * @liusha
     */
    private function _changeSq(int $aid,  array $input, string $changeMethod='setDec',bool $is_trans=false)
    {
        $HcityMainDb = HcityMainDb::i();
        $hcityShardDb = HcityShardDb::i(['aid' => $aid]);
        $hcityGoodsKzDao = HcityGoodsKzDao::i();
        $hcityGoodsSkuKzDao = HcityGoodsSKuKzDao::i();
        $ShcityGoodsDao = ShcityGoodsDao::i(['aid' => $aid]);
        $shcityGoodsSkuDao = ShcityGoodsSkuDao::i(['aid' => $aid]);

        $mGoods = $ShcityGoodsDao->getOne(['aid'=>$aid, 'id'=>$input['goods_id']]);
        $mGoodsSku = $shcityGoodsSkuDao->getOne(['id'=>$input['sku_id'], 'aid'=>$aid, 'goods_id'=>$input['goods_id']]);

        //商品信息不存在
        if(!$mGoods || !$mGoodsSku)
        {
            log_message('error', __METHOD__ . "商品信息不存在：[{$changeMethod}]".json_encode($input));
            //扣减库存处理
            if($changeMethod=='setDec')
            {
                return false;
            }
            //回滚库存不做库存处理
            return true;
        }

        if($is_trans)
        {
            $HcityMainDb->trans_start();
            $hcityShardDb->trans_start();
        }

        $sku_options['where'] = [
            'id' => $input['sku_id'],
            'aid' => $aid,
            'goods_id' => $input['goods_id']
        ];
        $sku_kz_options['where'] = [
            'sku_id' => $input['sku_id'],
            'aid' => $aid,
            'goods_id' => $input['goods_id']
        ];
        $goods_kz_options['where'] = [
            'aid' => $aid,
            'goods_id' => $input['goods_id']
        ];
        if($mGoodsSku->is_hcity_stock_open==1)//扣减商圈库存
        {
            $shcityGoodsSkuDao->$changeMethod('hcity_stock_num', $input['num'], $sku_options);
            $hcityGoodsSkuKzDao->$changeMethod('hcity_stock_num', $input['num'], $sku_kz_options);
            $hcityGoodsKzDao->$changeMethod('hcity_stock_num', $input['num'], $goods_kz_options);
        }

        if($is_trans)
        {
            if ($hcityShardDb->trans_status()===false || $HcityMainDb->trans_status()===false)
            {
                $HcityMainDb->trans_rollback();
                $hcityShardDb->trans_rollback();
                return false;
            }
            else
            {
                $hcityShardDb->trans_complete();
                $HcityMainDb->trans_complete();
                return true;
            }
        }
        return true;
    }

    /**
     * 增加商品销量(通用)
     * @param int $aid
     * @param array $input ['goods_id', 'sku_id, 'num] 必须
     * @param bool $is_trans
     * @return bool
     * @liusha
     */
    private function _addCommonSalesNum(int $aid, array $input, bool $is_trans=false)
    {
        $HcityMainDb = HcityMainDb::i();
        $hcityShardDb = HcityShardDb::i(['aid' => $aid]);
        $hcityGoodsKzDao = HcityGoodsKzDao::i();
        $hcityGoodsSkuKzDao = HcityGoodsSkuKzDao::i();
        $ShcityGoodsDao = ShcityGoodsDao::i(['aid' => $aid]);
        $shcityGoodsSkuDao = ShcityGoodsSkuDao::i(['aid' => $aid]);

        $mGoods = $ShcityGoodsDao->getOne(['aid'=>$aid, 'id'=>$input['goods_id']]);
        $mGoodsSku = $shcityGoodsSkuDao->getOne(['id'=>$input['sku_id'], 'aid'=>$aid, 'goods_id'=>$input['goods_id']]);
        if(!$mGoods || !$mGoodsSku) return false;

        if($is_trans)
        {
            $HcityMainDb->trans_start();
            $hcityShardDb->trans_start();
        }

        $goods_options['where'] = [
            'id' => $input['goods_id'],
            'aid' => $aid
        ];
        $sku_options['where'] = [
            'id' => $input['sku_id'],
            'aid' => $aid,
            'goods_id' => $input['goods_id']
        ];
        $sku_kz_options['where'] = [
            'sku_id' => $input['sku_id'],
            'aid' => $aid,
            'goods_id' => $input['goods_id']
        ];
        $goods_kz_options['where'] = [
            'aid' => $aid,
            'goods_id' => $input['goods_id']
        ];
        //增加商品总销量
        $ShcityGoodsDao->setInc('sales_num', $input['num'], $goods_options);
        $shcityGoodsSkuDao->setInc('sales_num', $input['num'], $sku_options);
        $hcityGoodsKzDao->setInc('sales_num', $input['num'], $goods_kz_options);
        $hcityGoodsSkuKzDao->setInc('sales_num', $input['num'], $sku_kz_options);

        if($is_trans)
        {
            if ($hcityShardDb->trans_status()===false || $HcityMainDb->trans_status()===false)
            {
                $HcityMainDb->trans_rollback();
                $hcityShardDb->trans_rollback();
                return false;
            }
            else
            {
                $hcityShardDb->trans_complete();
                $HcityMainDb->trans_complete();
                return true;
            }
        }
        return true;
    }
    /**
     * 检测YDYM库存
     * @param int $aid
     * @param int $goods_id
     * @param array $items [[goods_id sku_id num]] 字段必须
     * @throws Exception
     */
    private function _checkYdym(int $aid, int $goods_id, array $items)
    {
        $goods_sku_id_arr = array_column($items, 'sku_id');
        $goods_sku_ids = empty($goods_sku_id_arr) ? '0' : implode(',', $goods_sku_id_arr);
        $shcityGoodsDao = ShcityGoodsDao::i(['aid'=>$aid]);
        $shcityGoodsSkuDao = ShcityGoodsSkuDao::i(['aid'=>$aid]);

        $goods = $shcityGoodsDao->getOne(['aid'=>$aid,'id'=>$goods_id]);
        $sku_list = $shcityGoodsSkuDao->getAllArray("aid={$aid} AND goods_id={$goods_id} AND id in ({$goods_sku_ids})");
        if(!$goods || !$sku_list)
        {
            log_message('error', __METHOD__.'--goods_id--'.$goods_id);
            throw new Exception("商品信息不存在");
        }
        // 库存是充足
        $is_enough = true;
        foreach($items as $item)
        {
            $sku = array_find($sku_list, 'id', $item['sku_id']);
            if(!$sku)
                throw new Exception("商品信息不存在");

            //2结算一点一码商品 开启自定义库存并且库存不足
            if($sku['is_stock_open']==1 && ($sku['stock_num'] <= 0 || $sku['stock_num'] < $item['num'] ))
            {
                log_message('error', __METHOD__.json_encode($item));
                $is_enough = false;
            }

        }
        if(!$is_enough)
            throw new Exception("商品{$goods->title}库存不足");
    }

    /**
     * 变更库存 $changeMethod 默认扣减 setDec 回原setInc
     * @param int $aid
     * @param int $stock_type 1扣减商圈 2扣减一店一码 3扣减福利池
     * @param array $input ['goods_id', 'sku_id, 'num] 必须
     * @param string $changeMethod 默认扣减 setDec 回原 setInc
     * @param bool $is_trans
     * @return bool
     * @liusha
     */
    private function _changeYdym(int $aid,  array $input, string $changeMethod='setDec',bool $is_trans=false)
    {
        $HcityMainDb = HcityMainDb::i();
        $hcityShardDb = HcityShardDb::i(['aid' => $aid]);
        $hcityGoodsSkuKzDao = HcityGoodsSKuKzDao::i();
        $ShcityGoodsDao = ShcityGoodsDao::i(['aid' => $aid]);
        $shcityGoodsSkuDao = ShcityGoodsSkuDao::i(['aid' => $aid]);

        $mGoods = $ShcityGoodsDao->getOne(['aid'=>$aid, 'id'=>$input['goods_id']]);
        $mGoodsSku = $shcityGoodsSkuDao->getOne(['id'=>$input['sku_id'], 'aid'=>$aid, 'goods_id'=>$input['goods_id']]);

        //商品信息不存在
        if(!$mGoods || !$mGoodsSku)
        {
            log_message('error', __METHOD__ . "商品信息不存在：[{$changeMethod}]".json_encode($input));
            //扣减库存处理
            if($changeMethod=='setDec')
            {
                return false;
            }
            //回滚库存不做库存处理
            return true;
        }

        if($is_trans)
        {
            $HcityMainDb->trans_start();
            $hcityShardDb->trans_start();
        }

        $sku_options['where'] = [
            'id' => $input['sku_id'],
            'aid' => $aid,
            'goods_id' => $input['goods_id']
        ];
        $sku_kz_options['where'] = [
            'sku_id' => $input['sku_id'],
            'aid' => $aid,
            'goods_id' => $input['goods_id']
        ];
        $goods_kz_options['where'] = [
            'aid' => $aid,
            'goods_id' => $input['goods_id']
        ];
        if($mGoodsSku->is_stock_open==1)//扣减一店一码库存
        {
            $shcityGoodsSkuDao->$changeMethod('stock_num', $input['num'], $sku_options);
            $hcityGoodsSkuKzDao->$changeMethod('stock_num', $input['num'], $sku_kz_options);
        }

        if($is_trans)
        {
            if ($hcityShardDb->trans_status()===false || $HcityMainDb->trans_status()===false)
            {
                $HcityMainDb->trans_rollback();
                $hcityShardDb->trans_rollback();
                return false;
            }
            else
            {
                $hcityShardDb->trans_complete();
                $HcityMainDb->trans_complete();
                return true;
            }
        }
        return true;
    }
    /**
     * 检测福利池库存
     * @param int $aid
     * @param int $goods_id
     * @param array $items [[goods_id sku_id num]] 字段必须
     * @throws Exception
     */
    private function _checkWelfare(int $aid, int $goods_id, array $items)
    {
        $goods_sku_id_arr = array_column($items, 'sku_id');
        $goods_sku_ids = empty($goods_sku_id_arr) ? '0' : implode(',', $goods_sku_id_arr);
        $welfareGoodsDao = HcityWelfareGoodsDao::i();
        $welfareGoodsSkuDao = HcityWelfareGoodsSkuDao::i();

        $goods = $welfareGoodsDao->getOne(['aid'=>$aid,'id'=>$goods_id]);
        $sku_list = $welfareGoodsSkuDao->getAllArray("aid={$aid} AND goods_id={$goods_id} AND id in ({$goods_sku_ids})");
        if(!$goods || !$sku_list)
        {
            log_message('error', __METHOD__.'--goods_id--'.$goods_id);
            throw new Exception("商品信息不存在");
        }
        // 库存是充足
        $is_enough = true;
        foreach($items as $item)
        {
            $sku = array_find($sku_list, 'id', $item['sku_id']);
            if(!$sku)
                throw new Exception("商品信息不存在");

            //福利池商品 并且库存不足
            if($sku['free_num']<= 0 || $sku['free_num'] < $item['num'])
            {
                log_message('error', __METHOD__.json_encode($item));
                $is_enough = false;
            }

        }
        if(!$is_enough)
            throw new Exception("商品{$goods->title}库存不足");
    }

    /**
     * 变更库存 $changeMethod 默认扣减 setDec 回原setInc
     * @param int $aid
     * @param int $stock_type 1扣减商圈 2扣减一店一码 3扣减福利池
     * @param array $input ['goods_id', 'sku_id, 'num] 必须
     * @param string $changeMethod 默认扣减 setDec 回原 setInc
     * @param bool $is_trans
     * @return bool
     * @liusha
     */
    private function _changeWelfare(int $aid, array $input, string $changeMethod='setDec',bool $is_trans=false)
    {
        $HcityMainDb = HcityMainDb::i();
        $welfareGoodsDao = HcityWelfareGoodsDao::i();
        $welfareGoodsSkuDao = HcityWelfareGoodsSkuDao::i();

        $mGoods = $welfareGoodsDao->getOne(['aid'=>$aid, 'id'=>$input['goods_id']]);
        $mGoodsSku = $welfareGoodsSkuDao->getOne(['id'=>$input['sku_id'], 'aid'=>$aid, 'goods_id'=>$input['goods_id']]);

        //商品信息不存在
        if(!$mGoods || !$mGoodsSku)
        {
            log_message('error', __METHOD__ . "商品信息不存在：[{$changeMethod}]".json_encode($input));
            //扣减库存处理
            if($changeMethod=='setDec')
            {
                return false;
            }
            //回滚库存不做库存处理
            return true;
        }

        if($is_trans)
        {
            $HcityMainDb->trans_start();
        }

        $sku_options['where'] = [
            'id' => $input['sku_id'],
            'aid' => $aid,
            'goods_id' => $input['goods_id']
        ];

        $goods_options['where'] = [
            'aid' => $aid,
            'id' => $input['goods_id']
        ];

        $welfareGoodsDao->$changeMethod('free_num', $input['num'], $goods_options);
        $welfareGoodsSkuDao->$changeMethod('free_num', $input['num'], $sku_options);

        if($is_trans)
        {
            if ($HcityMainDb->trans_status()===false)
            {
                $HcityMainDb->trans_rollback();
                return false;
            }
            else
            {
                $HcityMainDb->trans_complete();
                return true;
            }
        }
        return true;
    }
    /**
     * 增加商品销量
     * @param int $aid
     * @param array $input ['goods_id', 'sku_id, 'num] 必须
     * @param bool $is_trans
     * @return bool
     * @liusha
     */
    private function _addWelfareSalesNum(int $aid, array $input, bool $is_trans=false)
    {
        $HcityMainDb = HcityMainDb::i();
        $welfareGoodsDao = HcityWelfareGoodsDao::i();
        $welfareGoodsSkuDao = HcityWelfareGoodsSkuDao::i();


        $mGoods = $welfareGoodsDao->getOne(['aid'=>$aid, 'id'=>$input['goods_id']]);
        $mGoodsSku = $welfareGoodsSkuDao->getOne(['id'=>$input['sku_id'], 'aid'=>$aid, 'goods_id'=>$input['goods_id']]);
        if(!$mGoods || !$mGoodsSku) return false;

        if($is_trans)
        {
            $HcityMainDb->trans_start();
        }

        $goods_options['where'] = [
            'id' => $input['goods_id'],
            'aid' => $aid
        ];
        $sku_options['where'] = [
            'id' => $input['sku_id'],
            'aid' => $aid,
            'goods_id' => $input['goods_id']
        ];

        //增加商品总销量
        $welfareGoodsDao->setInc('sales_num', $input['num'], $goods_options);
        $welfareGoodsSkuDao->setInc('sales_num', $input['num'], $sku_options);

        if($is_trans)
        {
            if ($HcityMainDb->trans_status()===false)
            {
                $HcityMainDb->trans_rollback();
                return false;
            }
            else
            {
                $HcityMainDb->trans_complete();
                return true;
            }
        }
        return true;
    }
    /**
     * 检测福利池库存
     * @param int $aid
     * @param int $goods_id
     * @param array $items [[goods_id  num]] 字段必须
     * @throws Exception
     */
    private function _checkJz(int $aid, int $goods_id, array $items, int $activity_id)
    {
        $activityGoodsJzDao = HcityActivityGoodsJzDao::i();

        $activityGoods = $activityGoodsJzDao->getOne(['aid'=>$aid,'goods_id'=>$goods_id, 'id'=>$activity_id]);
        if(!$activityGoods)
        {
            log_message('error', __METHOD__.'--activity_id--'.$activity_id);
            throw new Exception("活动信息不存在");
        }
        if($activityGoods->stock_num <= 0)
        {
            log_message('error', __METHOD__.'--activity_id--'.$activity_id);
            throw new Exception("商品{$activityGoods->title}已售罄");
        }
        // 库存是充足
        $is_enough = true;
        foreach($items as $item)
        {
            //集赞商品 并且库存不足
            if($activityGoods->stock_num < $item['num'])
            {
                log_message('error', __METHOD__.json_encode($item));
                $is_enough = false;
            }

        }
        if(!$is_enough)
            throw new Exception("商品{$activityGoods->title}库存不足");
    }

    /**
     * 变更库存 $changeMethod 默认扣减 setDec 回原setInc
     * @param int $aid
     * @param int $stock_type 1扣减商圈 2扣减一店一码 3扣减福利池
     * @param array $input ['goods_id', 'sku_id, 'num','activity_id'] 必须
     * @param string $changeMethod 默认扣减 setDec 回原 setInc
     * @param bool $is_trans
     * @return bool
     * @liusha
     */
    private function _changeJz(int $aid, array $input, string $changeMethod='setDec',bool $is_trans=false)
    {
        $HcityMainDb = HcityMainDb::i();
        $activityGoodsJzDao = HcityActivityGoodsJzDao::i();

        $activityGoods = $activityGoodsJzDao->getOne(['id'=>$input['activity_id'],'aid'=>$aid, 'goods_id'=>$input['goods_id']]);

        //商品信息不存在
        if(!$activityGoods)
        {
            log_message('error', __METHOD__ . "活动信息不存在：[{$changeMethod}]".json_encode($input));
            //扣减库存处理
            if($changeMethod=='setDec')
            {
                return false;
            }
            //回滚库存不做库存处理
            return true;
        }

        if($is_trans)
        {
            $HcityMainDb->trans_start();
        }

        $goods_options['where'] = [
            'id'=>$input['activity_id'],
            'aid' => $aid,
            'goods_id' => $input['goods_id']
        ];

        $activityGoodsJzDao->$changeMethod('stock_num', $input['num'], $goods_options);
        if($is_trans)
        {
            if ($HcityMainDb->trans_status()===false)
            {
                $HcityMainDb->trans_rollback();
                return false;
            }
            else
            {
                $HcityMainDb->trans_complete();
                return true;
            }
        }
        return true;
    }

    /**
     * 增加商品销量
     * @param int $aid
     * @param array $input ['goods_id', 'sku_id, 'num', 'activity_id'] 必须
     * @param bool $is_trans
     * @return bool
     * @liusha
     */
    private function _addJzSalesNum(int $aid, array $input, bool $is_trans=false)
    {
        $HcityMainDb = HcityMainDb::i();
        $activityGoodsJzDao = HcityActivityGoodsJzDao::i();

        $activityGoods = $activityGoodsJzDao->getOne(['id'=>$input['activity_id'], 'aid'=>$aid, 'goods_id'=>$input['goods_id']]);
        //商品信息不存在
        if(!$activityGoods)
        {
            log_message('error', __METHOD__ . "活动信息不存在：".json_encode($input));

            return false;
        }

        if($is_trans)
        {
            $HcityMainDb->trans_start();
        }

        $goods_options['where'] = [
            'id'=>$input['activity_id'],
            'aid' => $aid,
            'goods_id' => $input['goods_id']
        ];
        $activityGoodsJzDao->setInc('sales_num', $input['num'], $goods_options);
        if($is_trans)
        {
            if ($HcityMainDb->trans_status()===false)
            {
                $HcityMainDb->trans_rollback();
                return false;
            }
            else
            {
                $HcityMainDb->trans_complete();
                return true;
            }
        }
        return true;
    }

    /**
     * 检测砍价活动库存
     * @param int $aid
     * @param int $goods_id
     * @param array $items [['num','goods_id','sku_id']]
     * @param int $activity_id
     * @throws Exception
     * @user liusha
     * @date 2018/9/4 10:23
     */
    private function _checkBargain(int $aid, int $goods_id, array $items, int $activity_id)
    {
        $hcityActivityBargainDao = HcityActivityBargainDao::i();

        $activityGoods = $hcityActivityBargainDao->getOne(['aid'=>$aid,'goods_id'=>$goods_id, 'id'=>$activity_id]);
        if(!$activityGoods)
        {
            log_message('error', __METHOD__.'--activity_id--'.$activity_id);
            throw new Exception("活动信息不存在");
        }
        if($activityGoods->stock_num <= 0)
        {
            log_message('error', __METHOD__.'--activity_id--'.$activity_id);
            throw new Exception("商品{$activityGoods->title}已售罄");
        }
        // 库存是充足
        $is_enough = true;
        foreach($items as $item)
        {
            //砍价商品 并且库存不足
            if($activityGoods->stock_num < $item['num'])
            {
                log_message('error', __METHOD__.json_encode($item));
                $is_enough = false;
            }

        }
        if(!$is_enough)
            throw new Exception("商品{$activityGoods->title}库存不足");
    }

    /**
     * 修改砍价活动库存
     * @param int $aid
     * @param array $input
     * @param string $changeMethod
     * @param bool $is_trans
     * @return bool
     * @user liusha
     * @date 2018/9/4 10:23
     */
    private function _changeBargain(int $aid, array $input, string $changeMethod='setDec',bool $is_trans=false)
    {
        $HcityMainDb = HcityMainDb::i();
        $hcityActivityBargainDao = HcityActivityBargainDao::i();

        $activityGoods = $hcityActivityBargainDao->getOne(['id'=>$input['activity_id'],'aid'=>$aid, 'goods_id'=>$input['goods_id']]);

        //商品信息不存在
        if(!$activityGoods)
        {
            log_message('error', __METHOD__ . "活动信息不存在：[{$changeMethod}]".json_encode($input));
            //扣减库存处理
            if($changeMethod=='setDec')
            {
                return false;
            }
            //回滚库存不做库存处理
            return true;
        }

        if($is_trans)
        {
            $HcityMainDb->trans_start();
        }

        $goods_options['where'] = [
            'id'=>$input['activity_id'],
            'aid' => $aid,
            'goods_id' => $input['goods_id']
        ];

        $hcityActivityBargainDao->$changeMethod('stock_num', $input['num'], $goods_options);
        if($is_trans)
        {
            if ($HcityMainDb->trans_status()===false)
            {
                $HcityMainDb->trans_rollback();
                return false;
            }
            else
            {
                $HcityMainDb->trans_complete();
                return true;
            }
        }
        return true;
    }

    /**
     * 增加砍价活动商品销量
     * @param int $aid
     * @param array $input
     * @param bool $is_trans
     * @return bool
     * @user liusha
     * @date 2018/9/4 10:23
     */
    private function _addBargainSalesNum(int $aid, array $input, bool $is_trans=false)
    {
        $HcityMainDb = HcityMainDb::i();
        $hcityActivityBargainDao = HcityActivityBargainDao::i();

        $activityGoods = $hcityActivityBargainDao->getOne(['id'=>$input['activity_id'], 'aid'=>$aid, 'goods_id'=>$input['goods_id']]);
        //商品信息不存在
        if(!$activityGoods)
        {
            log_message('error', __METHOD__ . "活动信息不存在：".json_encode($input));

            return false;
        }

        if($is_trans)
        {
            $HcityMainDb->trans_start();
        }

        $goods_options['where'] = [
            'id'=>$input['activity_id'],
            'aid' => $aid,
            'goods_id' => $input['goods_id']
        ];
        $hcityActivityBargainDao->setInc('sales_num', $input['num'], $goods_options);
        if($is_trans)
        {
            if ($HcityMainDb->trans_status()===false)
            {
                $HcityMainDb->trans_rollback();
                return false;
            }
            else
            {
                $HcityMainDb->trans_complete();
                return true;
            }
        }
        return true;
    }
}