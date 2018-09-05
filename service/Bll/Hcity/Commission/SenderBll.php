<?php
/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/27
 * Time: 下午1:52
 */

namespace Service\Bll\Hcity\Commission;

use Service\DbFrame\DataBase\HcityMainDbModels\HcityCommissionQueueDao;
use Service\Exceptions\Exception;
use Service\Traits\SingletonTrait;

class SenderBll
{
    use SingletonTrait;

    /**
     * 投递佣金任务
     * @param QueueParamsBll $params
     * @throws Exception
     * @author ahe<ahe@iyenei.com>
     */
    public function send(QueueParamsBll $params)
    {
        if (empty($params->getType()) ||
            empty($params->getFid()) ||
            empty($params->getRemark()) ||
            empty($params->getExt())
        ) {
            throw new Exception('参数错误');
        }
        if ($params->getMoney() < 0) {
            throw new Exception('分佣金额错误');
        }
        if (!$this->_validExt($params->getType(), $params->getExt())) {
            throw new Exception('扩展参数错误');
        }
        $createData = [
            'fid' => $params->getFid(),
            'money' => $params->getMoney(),
            'remark' => $params->getRemark(),
            'type' => $params->getType(),
            'time' => time(),
            'ext' => json_encode($params->getExt())
        ];
        $taskId = HcityCommissionQueueDao::i()->create($createData);
        QueueBll::getInstance()->post($taskId);
    }

    /**
     * 校验扩展参数
     * @param int $type
     * @param array $ext
     * @return bool
     * @author ahe<ahe@iyenei.com>
     */
    private function _validExt(int $type, array $ext)
    {
        if ($type == Config::TYPE_GOODS_TRADE) {
            if (!isset($ext['uid']) || !isset($ext['shop_id']) || !isset($ext['hx_code'])) {
                return false;
            }
        }
        if ($type == Config::TYPE_WELFARE_TRADE) {
            if (!isset($ext['uid']) || !isset($ext['shop_id'])) {
                return false;
            }
        }
        if ($type == Config::TYPE_OPEN_HCARD) {
            if (!isset($ext['uid'])) {
                return false;
            }
        }
        if ($type == Config::TYPE_OPEN_HCARD_BY_MERCHANT) {
            if (!isset($ext['shop_id'])) {
                return false;
            }
        }
        if ($type == Config::TYPE_OPEN_BARCODE) {
            if (!isset($ext['shop_id'])) {
                return false;
            }
        }
        if ($type == Config::TYPE_HEXIAO) {
            if (!isset($ext['hx_code'])) {
                return false;
            }
        }
        if ($type == Config::TYPE_MERCHANT_PRE_JS) {
            if (!isset($ext['shop_id']) || !isset($ext['hx_code']) || !isset($ext['source_type'])) {
                return false;
            }
        }
        if ($type == Config::TYPE_KNIGHT_INVITE_KNIGHT) {
            if (!isset($ext['uid'])) {
                return false;
            }
        }
        if ($type == Config::TYPE_KNIGHT_INVITE_SHOP) {
            if (!isset($ext['uid']) || !isset($ext['shop_id'])) {
                return false;
            }
        }
        if ($type == Config::TYPE_KNIGHT_NEWBIE) {
            if (!isset($ext['uid'])) {
                return false;
            }
        }
        return true;
    }
}