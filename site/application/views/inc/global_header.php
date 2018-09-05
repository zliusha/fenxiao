<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><header id="head" class="nav">
		<div class="nav-container clearfix">
			<a class="logo" href="javascript:;"><img src="<?=STATIC_URL?>site/img/logo-light.png" alt="云店宝"></a>
			<div class="nav-content">
				<div class="item">
					<a href="<?=SITE_URL?>" class="a <?=$this->url_class=='welcome'?'active':''?>">首页</a>
				</div>
				<div class="item dropmenu">
					<a href="javascript:;" class="a <?=$this->url_class=='product'?'active':''?>">产品中心</a>
					<div id="product" class="dropDownList" style="height: 0;">
						<div class="menuSystemList">
							<ul class="clearfix width1200 border">
								<li class="item-drop one">
									<a href="<?=SITE_URL?>product/mall" class="url"><i class="icon icon-1"></i>外卖微商城</a>
									<span class="li">
										<a href="javascript:;" target="_blank">门店管理</a>
									</span>
									<span class="li">
										<a href="javascript:;" target="_blank">微商城</a>
									</span>
									<span class="li">
										<a href="javascript:;" target="_blank">配送平台</a>
									</span>
									<span class="li">
										<a href="javascript:;" target="_blank">数据分析</a>
									</span>
								</li>
								<li class="item-drop one">
									<a href="<?=SITE_URL?>product/cashier" class="url"><i class="icon icon-2"></i>外卖收银平台</a>
									<span class="li">
										<a href="javascript:;" target="_blank">聚合收银</a>
									</span>
									<span class="li">
										<a href="javascript:;" target="_blank">后台管理</a>
									</span>
									<span class="li">
										<a href="javascript:;" target="_blank">储值卡管理</a>
									</span>
									<span class="li">
										<a href="javascript:;" target="_blank">订单处理</a>
									</span>
								</li>
								<li class="item-drop one">
									<a href="<?=SITE_URL?>product/marketing" class="url"><i class="icon icon-3"></i>互动营销</a>
									<span class="li">
										<a href="javascript:;" target="_blank">优惠券营销</a>
									</span>
									<span class="li">
										<a href="javascript:;" target="_blank">粉丝营销</a>
									</span>
									<span class="li">
										<a href="javascript:;" target="_blank">积分管理</a>
									</span>
									<span class="li">
										<a href="javascript:;" target="_blank">用户分析</a>
									</span>
								</li>
								<li class="item-drop one">
									<a href="<?=SITE_URL?>hardware" class="url"><i class="icon icon-4"></i>智能硬件</a>
									<span class="li">
										<a href="javascript:;" target="_blank">智能扫码抢</a>
									</span>
									<span class="li">
										<a href="javascript:;" target="_blank">智能收银台</a>
									</span>
									<span class="li">
										<a href="javascript:;" target="_blank">智能POS机</a>
									</span>
									<span class="li">
										<a href="javascript:;" target="_blank">钱箱和电子称</a>
									</span>
								</li>
							</ul>
						</div>
					</div>
				</div>
				<div class="item">
					<a href="<?=SITE_URL?>hardware" class="a <?=$this->url_class=='hardware'?'active':''?>">智能收银机</a>
				</div>
				<div class="item">
					<a href="<?=SITE_URL?>solution"  class="a <?=$this->url_class=='solution'?'active':''?>">解决方案</a>
				</div>
				<!-- <div class="item">
					<a href="javascript:;" class="a">用户案例</a>
				</div> -->
				<div class="item">
					<a href="<?=SITE_URL?>agency" class="a <?=$this->url_class=='agency'?'active':''?>">代理加盟</a>
				</div>
				<div class="item">
					<a href="http://ketang.waimaishop.com" class="a" target="_blank">云店宝课堂</a>
				</div>
			</div>
			<?php if(!isset($this->s_user)):?>
			<div style="float: right;margin-top: 24px;">
				<a href="<?=SITE_URL?>passport/login">登录</a><span style="margin: 0 10px">|</span><a href="<?=SITE_URL?>passport/register">注册</a>
			</div>
			<?php else:?>
			<div style="float: right;margin-top: 24px;">
				<?=$this->s_user->username?> <a href="<?=SAAS_URL?>" style="display: inline-block;padding: 2px 5px;margin-left: 10px;font-size: 14px;color: #fff;background-color: #00a0e9;border-radius: 4px;">进入后台</a>
			</div>
			<?php endif?>
		</div>
	</header>