<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>服务订购 - 挖到后台</title>
  <?php $this->load->view('inc/global_header'); ?>
  <style>
    .service-box {
      margin: 0 0 30px;
      padding: 20px 10px;
      background-color: #fbfdff;
      border: 1px dashed #c0ccda;
    }

    .service-price>* {
      vertical-align: middle;
    }

    .label-service {
      position: relative;
      display: inline-block;
      padding: 8px 20px;
      font-size: 16px;
      color: #475669;
      line-height: 1;
      border: 2px solid #999;
      cursor: pointer;
    }

    .label-service.active {
      border-color: #5aa2e7;
    }

    .label-service.active:after {
      position: absolute;
      right: -2px;
      bottom: -2px;
      content: "";
      width: 24px;
      height: 24px;
      background: url("<?=STATIC_URL?>djadmin/img/price_select.png") no-repeat right bottom;
    }

    .dl-horizontal {
      margin-bottom: 0;
    }

    .dl-horizontal+.dl-horizontal {
      margin-top: 10px;
    }

    .dl-horizontal>dt,
    .dl-horizontal>dd {
      line-height: 36px;
    }

    @media (min-width: 768px) {
      .dl-horizontal dt {
        width: 60px;
      }

      .dl-horizontal dd {
        margin-left: 60px;
      }
    }
    .table>tbody>tr>td {
      vertical-align: top;
    }
  </style>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <ol class="breadcrumb">
        <li class="active">服务订购</li>
      </ol>
      <div class="main-body">
        <div class="main-body-inner">
<!--          <div class="row service-box">-->
<!--            <div class="col-md-9 col-sm-9">-->
<!--              <dl class="dl-horizontal">-->
<!--                <dt>版本：</dt>-->
<!--                <dd>-->
<!--                  <span class="label-service active">付费版</span>-->
<!--                </dd>-->
<!--              </dl>-->
<!--              <dl class="dl-horizontal">-->
<!--                <dt>价格：</dt>-->
<!--                <dd class="service-price">-->
<!--                  <strong class="text-danger h3">￥6800</strong> <span>/年</span>-->
<!--                </dd>-->
<!--              </dl>-->
<!--            </div>-->
<!--            <div class="col-md-3 col-sm-3 text-right">-->
<!--              <!-- <a href="javascript:;" class="btn btn-primary mt20">立即订购</a> -->
<!--            </div>-->
<!--          </div>-->


          <div style="margin: 5px 0 30px 0;font-size: 16px;font-weight: bold">
            如需购买版本套餐、升级版本或者续费，请拨打客服电话：18667118889
          </div>
          <div class="table-responsive">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th>会员管理</th>
                  <th>互动营销</th>
                  <th>精准营销</th>
                  <th>统计分析</th>
                  <th>微信设置</th>
                  <th>微商城</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>
                    <p>客户管理</p>
                    <p>粉丝管理</p>
                    <p>标签管理(不限个数)</p>
                    <p>交易信息管理</p>
                    <p>互动信息管理</p>
                    <p>订单关怀</p>
                    <p>每日签到</p>
                    <p>会员中心</p>
                    <p>积分管理</p>
                    <p>积分商城</p>
                    <p>积分商城奖品兑换规则</p>
                  </td>
                  <td>
                    <p>红评红包</p>
                    <p>首榜/关注红包</p>
                    <p>上新提醒</p>
                    <p>活动H5页</p>
                    <p>企宣H5页</p>
                    <p>商品映射</p>
                    <p>店铺映射</p>
                    <p>粉丝福利购</p>
                    <p>购物返利</p>
                    <p>抽奖游戏</p>
                  </td>
                  <td>
                    <p>微信营销-全部</p>
                    <p>微信营销-标签精准推送</p>
                    <p>微信营销(服务号)<br>-自定义精准推送4次/月</p>
                    <p>微信营销(订阅号)<br>-自定义精准推送1次/月</p>
                    <p>短信营销-全部</p>
                    <p>短信营销-按条件筛选</p>
                    <p>短信营销-指定号码</p>
                  </td>
                  <td>
                    <p>短信营销效果</p>
                    <p>行业统计</p>
                    <p>红包数据分析</p>
                    <p>社交数据分析</p>
                    <p>销售信息分析</p>
                  </td>
                  <td>
                    <p>自定义菜单</p>
                    <p>微信素材管理</p>
                    <p>关注回复</p>
                    <p>关键词回复</p>
                    <p>微信支付</p>
                  </td>
                  <td>
                    <p>商品管理</p>
                    <p>订单交易</p>
                    <p>店铺装修</p>
                    <p>数据统计</p>
                    <p>会员管理</p>
                    <p>打折工具</p>
                    <p>促销工具</p>
                    <p>微分销</p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('djadmin/js/main.min.js');?>
</body>
</html>
