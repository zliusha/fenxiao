一：api
	common->通用
	auth->外卖小程序
	main/wm -> open 用于对外,如 erp,acrm
	meal->扫码点餐h5
	xmeal->扫码点餐xcx
	sy->t1
	test 单测
	mshop->外卖h5
二:config-所有的配置
	ini文件代表的不分环境
	inc文件,区分环境,当环境文件不存在会读取默认,默认文件如:scrm.inc,环境文件scrm.develop.inc,scrm.testing.inc,scrm.production.inc
三:djadmin 云店宝后台
四:libs 老的业务层,sdk,类库,dao
五:mshop 目前就是一个前端壳
六:uploads 本地上传目录
七:workerman 消息服务 各个项目消息通知,长链接,可扩展区块
八:service 新的服务层 bll,cache,sdk,traits,support
