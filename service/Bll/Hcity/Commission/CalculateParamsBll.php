<?php
/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/8/7
 * Time: 下午2:37
 */

namespace Service\Bll\Hcity\Commission;


use Service\DbFrame\DataBase\HcityMainDbModels\HcityCompanyExtDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityInviteMerchantViewDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityManageAccountDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserDao;

class CalculateParamsBll
{
    /** @var int  流水号 */
    private $fid;
    /** @var int 核销码 */
    private $hxcode;
    /** @var int  分佣类型 */
    private $type;
    /** @var float 分佣金额 */
    private $money;
    /** @var bool 是否确认佣金 */
    private $isConfirm = false;
    /** @var string 备注 */
    private $remark;

    /** @var array 数据源 */
    private $data;

    //一二级分销等级关系
    private $levelRel;
    //城市合伙人
    private $hcityPartner;
    //店铺邀请人
    private $shopInviter;

    //下级数组，用于计算c端收益贡献
    private $lowerArr = [];

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
     * 获取备注
     * @return string
     * @author ahe<ahe@iyenei.com>
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * 设置核销码
     * @param int $hxcode
     * @return $this
     * @author ahe<ahe@iyenei.com>
     */
    public function setHxcode(int $hxcode)
    {
        $this->hxcode = $hxcode;
        return $this;
    }

    /**
     * 获取核销码
     * @return int
     * @author ahe<ahe@iyenei.com>
     */
    public function getHxcode()
    {
        return $this->hxcode;
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
     * @return float
     * @author ahe<ahe@iyenei.com>
     */
    public function getMoney()
    {
        return $this->money;
    }

    /**
     * 设置分佣类型
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
     * @see \Service\Bll\Hcity\Commission\Config
     * @return int
     * @author ahe<ahe@iyenei.com>
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * 设置是否确认分佣
     * @param bool $isConfirm
     * @return $this
     * @author ahe<ahe@iyenei.com>
     */
    public function setConfirm(bool $isConfirm)
    {
        $this->isConfirm = $isConfirm;
        return $this;
    }

    /**
     * 是否确认分佣
     * @return bool
     * @author ahe<ahe@iyenei.com>
     */
    public function getConfirm()
    {
        return $this->isConfirm;
    }

    /**
     * 设置源数据
     * @param array $data
     * @return $this
     * @author ahe<ahe@iyenei.com>
     */
    public function setSourceData(array $data = [])
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 获取源数据
     * @return array
     * @author ahe<ahe@iyenei.com>
     */
    public function getSourceData()
    {
        return $this->data;
    }

    /**
     * 设置两级分销关系
     * @param int $id user_id或shop_id
     * @param bool $isShop
     * @return $this
     * @author ahe<ahe@iyenei.com>
     */
    public function setLevelRel(int $id, bool $isShop = false)
    {
        $levelData = [];
        if ($isShop) {
            $this->lowerArr['level_1'] = [
                'shop_id' => $id,
                'uid' => null
            ];
            $tmp = HcityInviteMerchantViewDao::i()->getOne(['shop_id' => $id]);
            $preUserLevel = 0;
        } else {
            $this->lowerArr['level_1'] = [
                'shop_id' => null,
                'uid' => $id
            ];
            $tmp = HcityUserDao::i()->getOne(['id' => $id]);
            $preUserLevel = $tmp->level;
        }
        if (!empty($tmp)) {
            //查找层级关系,两级分销 A->B->C
            $count = 1;
            $inviterUid = $tmp->inviter_uid;
            $inviterShopId = $tmp->inviter_shop_id;
            do {
                $this->lowerArr['level_' . ($count + 1)] = [
                    'shop_id' => $inviterShopId,
                    'uid' => $inviterUid
                ];
                if ($preUserLevel == 3) {
                    //城市合伙人不能给上线分佣，即使城市合伙人合作到期或拉黑也不给上线分佣
                    break;
                }
                if (!empty($inviterUid)) {
                    //有邀请人
                    $user = HcityUserDao::i()->getOne(['id' => $inviterUid]);
                    if (!empty($user)) {
                        $inviterUid = $user->inviter_uid;
                        $inviterShopId = $user->inviter_shop_id;
                        if ($user->level < $preUserLevel) {
                            //会员上线等级不能比下线等级低,去找下级
                            $count++;
                            continue;
                        }
                        //对会员个人分佣,需判断是否开通嘿卡,且未过期,且未被拉黑
                        if ($user->is_open_hcard == 1 && $user->hcard_expire_time > time() && $user->status == 0) {
                            $levelData[] = [
                                'fx_level' => $count,//分销层级
                                'user' => [
                                    'uid' => $user->id,
                                    'level' => $user->level
                                ]
                            ];
                        }
                        $preUserLevel = $user->level;
                    }
                } elseif (!empty($inviterShopId)) {
                    $shop = HcityInviteMerchantViewDao::i()->getOne(['shop_id' => $inviterShopId]);
                    if (!empty($shop)) {
                        $inviterUid = $shop->inviter_uid;
                        $inviterShopId = $shop->inviter_shop_id;
                        if ($shop->barcode_status == 1 && !empty($shop->barcode_expire_time) && $shop->barcode_expire_time > time()) {
                            //对门店分佣，需判断是否开通一店一码，且未过期
                            $levelData[] = [
                                'fx_level' => $count,//分销层级
                                'shop' => [
                                    'shop_id' => $shop->shop_id,
                                    'aid' => $shop->aid
                                ]
                            ];
                        }
                    }
                } else {
                    break;
                }
                $count++;
            } while ($count < 3);
        }
        $this->levelRel = $levelData;
        return $this;
    }

    /**
     * 获取两级分销关系
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function getLevelRel()
    {
        return $this->levelRel;
    }

    /**
     * 设置城市合伙人
     * @param string $region 省市区id
     * @return $this
     * @author ahe<ahe@iyenei.com>
     */
    public function setHcityPartner(string $region)
    {
        //先找区，再找市
        $where = ['expire_time >' => time(), 'type' => 0, 'status' => 0];
        $account = HcityManageAccountDao::i()->getOne(array_merge(['region' => $region], $where));
        if (empty($account)) {
            $regionArr = explode('-', $region);
            if (count($regionArr) == 3) {
                //找市级城市合伙人
                array_pop($regionArr);
                $regionNew = implode('-', $regionArr);
                $account = HcityManageAccountDao::i()->getOne(array_merge(['region' => $regionNew], $where));
            }
        }
        $this->hcityPartner = $account;
        return $this;
    }


    /**
     * 通过上下级关系设置城市合伙人
     * @param int $uid
     * @return $this
     * @author ahe<ahe@iyenei.com>
     */
    public function setHcityPartnerByRel(int $uid)
    {
        $user = HcityUserDao::i()->getOne(['id' => $uid]);
        if (empty($user)) {
            $this->hcityPartner = null;
            return $this;
        }
        $count = 1;
        //取两级上下线关系
        $inviterUid = $user->inviter_uid;
        $inviterShopId = $user->inviter_shop_id;
        $hcityPartner = null;
        do {
            if (!empty($inviterUid)) {
                $user = HcityUserDao::i()->getOne(['id' => $inviterUid]);
                if (!empty($user)) {
                    $inviterUid = $user->inviter_uid;
                    $inviterShopId = $user->inviter_shop_id;
                    $hcityPartner = HcityManageAccountDao::i()->getOne(['hcity_uid' => $user->id]);
                    if (!empty($hcityPartner) && $hcityPartner->type == 0 && $hcityPartner->status == 0 && $hcityPartner->expire_time > time()) {
                        //是城市合伙人
                        break;
                    }
                }
            } elseif (!empty($inviterShopId)) {
                $shop = HcityInviteMerchantViewDao::i()->getOne(['shop_id' => $inviterShopId]);
                if (!empty($shop)) {
                    $inviterUid = $shop->inviter_uid;
                    $inviterShopId = $shop->inviter_shop_id;
                }
            } else {
                break;
            }

            $count++;
        } while ($count < 3);

        $this->hcityPartner = $hcityPartner;
        return $this;
    }

    /**
     * 获取城市合伙人
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function getHcityPartner()
    {
        return $this->hcityPartner;
    }


    /**
     * 获取下级
     * @return array
     * @author ahe<ahe@iyenei.com>
     */
    public function getLowerArr()
    {
        return $this->lowerArr;
    }


    /**
     * 设置店铺邀请人
     * @param int $aid
     * @param int $shopId
     * @return $this
     * @author ahe<ahe@iyenei.com>
     */
    public function setShopInviter(int $aid, int $shopId)
    {
        $this->shopInviter = [
            'shop_id' => null,
            'uid' => null,
            'aid' => null,
            'lower_shop_id' => $shopId
        ];
        $company = HcityCompanyExtDao::i()->getOne(['aid' => $aid]);

        if (!empty($company) && !empty($company->inviter_uid)) {
            $this->shopInviter = [
                'shop_id' => null,
                'aid' => null,
                'uid' => $company->inviter_uid,
                'lower_shop_id' => $shopId
            ];
        }
        if (!empty($company) && !empty($company->inviter_shop_id)) {
            $this->shopInviter = [
                'aid' => $aid,
                'shop_id' => $company->inviter_shop_id,
                'uid' => null,
                'lower_shop_id' => $shopId
            ];
        }

        return $this;
    }

    /**
     * 获取店铺邀请人
     * @return array
     * @author ahe<ahe@iyenei.com>
     */
    public function getShopInviter()
    {
        return $this->shopInviter;
    }
}