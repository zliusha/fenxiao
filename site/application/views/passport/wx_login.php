<!DOCTYPE html>
<html>
<head>
	<title>微信登录测试</title>
</head>
<body>
	<div id='login_container'></div>
	<script src="//res.wx.qq.com/connect/zh_CN/htmledition/js/wxLogin.js"></script>
	<script type="text/javascript" >
		var obj = new WxLogin({
			id:"login_container", 
			appid: "wxaf155055b303372e", 
			scope: "snsapi_login", 
			redirect_uri: encodeURI("https://www.waimaishop.com/wx/notify") ,
			state: "",
			style: "",
			href: ""
		});
	</script>
</body>
</html>