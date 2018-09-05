<?php
$config = [
		//签名方式,默认为RSA2(RSA2048)
		'sign_type' => 'RSA',

		//支付宝公钥
		'alipay_public_key' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB',

		//商户私钥
		'merchant_private_key' => 'MIICXAIBAAKBgQDM5cepJKjS8hw7DdIjs/UzO4U1Zp3kacY115051i4yxyaviOcUs5budchwK8dmSoQP8JerPPWwisV3gG9fhXqfVgr6lu3veKgxjisK5V44o9H7YTsgEQ+e9mNsAoVEWl2Xw9K3YcM08tzkX+W/0Qv/MBqgtyilJo0wYfbELd68IQIDAQABAoGARXXMBsoXtVNAQMDBsTrLb+/Ii77a3dkBybTrZvT1ul8K/UzS0ZDEJNim92fP9BxkwqaUNAe5XnzczlMq7l3ooEnlc9miCyI4oGX0xx8YdbiJSl4JK6H19g6LjlNLkG2gfco3ahPSBWRtEl4HkuYe7udWRsSzHLBwsNI/pdCQRrUCQQDxDYisbEayxCI9rdmzH6Z1To4MReHTEsLqmBMVt4O8srE4vDDgf8aHGgHatbKusnC6VHiyk/RaacIJTDSwMpTrAkEA2ZpRa8ftbBjfbkvW6tTdIw9Hf8I2f1N/o7+tJW1118QYjiOfk11MH8xY7StW3xrxGlPoTyJVtDxl0fvCo0UgIwJAHYzNLmXvnMaSdAE16NF+dG721uZSMq/gGSYfYNAoZB97vjrDuyGu0q0LgSY5C1VwoEburOWaVOMWGFGxO9BXlQJBANfVROVPDKOnmBZiiu2p7R2VTinejQeF3pigyjDRfY1iJ6j0lJcqdxMjMSEtV6E7q+GdUFMNj0ySi7vXp5siOVkCQC3Zy0OnVCATf1rPCdV4tLgg0Oa7ESTrkgzHzJTjYRvA2dX72FVrUFwiZiEiidIVvpbbE3qr+4pHtMKvhLpBGzE=',

		//编码格式
		'charset' => 'UTF-8',

		//支付宝网关
		'gatewayUrl' => 'https://openapi.alipay.com/gateway.do',

		//应用ID
		'app_id' => '2016080301700437',

		//异步通知地址,只有扫码支付预下单可用
		'notify_url' => '',

		//最大查询重试次数
		'MaxQueryRetry' => '10',

		//查询间隔
		'QueryDuration' => '3'
];