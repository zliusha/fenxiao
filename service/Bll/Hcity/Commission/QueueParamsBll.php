<?php
/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/27
 * Time: 下午5:46
 */

namespace Service\Bll\Hcity\Commission;


use Service\Traits\SingletonTrait;

class QueueParamsBll
{
    use SingletonTrait;

    /** @var int 分佣类型 */
    private $type;

    /** @var int 流水号 */
    private $fid;

    /** @var int 分佣金额 */
    private $money;

    /** @var int 备注 */
    private $remark;

    /** @var  array 扩展数据 */
    private $ext;

    /**
     * 设置分拥类型
     * @param int $type
     * @return $this
     * @author ahe<ahe@iyenei.com>
     */
    public function setType(int $type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * 获取分佣类型
     * @return int
     * @author ahe<ahe@iyenei.com>
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * 设置流水号
     * @param int $fid
     * @return $this
     * @author ahe<ahe@iyenei.com>
     */
    public function setFid(int $fid)
    {
        $this->fid = $fid;
        return $this;
    }

    /**
     * 获取流水号
     * @return int
     * @author ahe<ahe@iyenei.com>
     */
    public function getFid()
    {
        return $this->fid;
    }

    /**
     * 设置分佣金额
     * @param float $money
     * @return $this
     * @author ahe<ahe@iyenei.com>
     */
    public function setMoney(float $money)
    {
        $this->money = $money;
        return $this;
    }

    /**
     * 获取分佣金额
     * @return int
     * @author ahe<ahe@iyenei.com>
     */
    public function getMoney()
    {
        return $this->money;
    }

    /**
     * 设置分佣备注
     * @param string $remark
     * @return $this
     * @author ahe<ahe@iyenei.com>
     */
    public function setRemark(string $remark)
    {
        $this->remark = $remark;
        return $this;
    }

    /**
     * 获取分佣备注
     * @return int
     * @author ahe<ahe@iyenei.com>
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * 设置扩展数据
     * @param array $ext
     *      Config::TYPE_GOODS_TRADE 商品分佣
     *          -- hx_code 订单核销码
     *          -- uid 当前消费会员id
     *          -- shop_id 当前购买店铺id
     *
     *      Config::TYPE_WELFARE_TRADE 福利池商品交易
     *          -- uid 当前消费会员id
     *          -- shop_id 当前购买店铺id
     *
     *      Config::TYPE_OPEN_HCARD  个人邀请码邀请开通嘿卡
     *          -- uid 当前开卡会员id
     *
     *      Config::TYPE_OPEN_HCARD_BY_MERCHANT  商户邀请码邀请开通嘿卡
     *          -- shop_id 店铺id
     *
     *      Config::TYPE_OPEN_BARCODE  邀请开通一店一码
     *          -- shop_id 当前开通店铺id
     *
     *      Config::TYPE_HEXIAO  核销订单
     *          -- hx_code 核销码
     *
     *      Config::TYPE_MERCHANT_PRE_JS  商家结算
     *          -- hx_code 核销码
     *          -- shop_id 店铺id
     *          -- source_type 来源入口 1商圈订单 2一店一码订单
     *
     *      Config::TYPE_KNIGHT_INVITE_KNIGHT  骑士拉新任务
     *          -- uid 骑士uid
     *      Config::TYPE_KNIGHT_INVITE_SHOP  商家拉新任务
     *          -- uid 骑士uid
     *          -- shop_id 店铺id
     *      Config::TYPE_KNIGHT_NEWBIE  新手任务
     *          -- uid 骑士uid
     * @return $this
     * @author ahe<ahe@iyenei.com>
     */
    public function setExt(array $ext)
    {
        $this->ext = $ext;
        return $this;
    }

    /**
     * 获取扩展数据
     * @return array
     * @author ahe<ahe@iyenei.com>
     */
    public function getExt()
    {
        return $this->ext;
    }
}