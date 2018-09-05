/* 
* @Author: binghe
* @Date:   2016-07-09 18:07:40
* @Last Modified by:   binghe
* @Last Modified time: 2016-08-02 13:43:43
*/

(function (a){
    a.Cookie = {
        GetByName: function (name) {
            var arr = document.cookie.match(new RegExp("(^| )" + name + "=([^;]*)(;|$)"));
            if (arr != null) return unescape(arr[2]); return null;
        },
        Set: function (name, value, days) {
            var Days = days; //此 cookie 将被保存 days 天
            var exp = new Date();    //new Date("December 31, 9998");
            exp.setTime(exp.getTime() + Days * 24 * 60 * 60 * 1000);
            document.cookie = name + "=" + escape(value) + ";expires=" + exp.toGMTString();
        }
    };
    a.PregRule={
        Email: /\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/,
        //Account: /\w+^$/,  //\w,匹配包括下划线的任何单词字符,等价于'[A-Za-z0-9_]'.\W,匹配任何非单词字符。等价于 '[^A-Za-z0-9_]'。
        Account: /^[a-zA-Z0-9_]{2,20}$/,
        Pwd: /^[a-zA-Z0-9_~!@#$%^&*()]{6,25}$/i,
        Tel: /^(13|14|15|16|17|18|19)[0-9]{9}$/,        //手机
        IDCard: /^\d{17}[\d|X|x]|\d{15}$/,   //身份证 
        Number: /^\d+$/,                    //数字
        Integer: /^[-\+]?\d+$/,             //正负整数
        IntegerZ: /^[1-9]\d*$/,                 //正整数
        IntegerF: /^-[1-9]\d*$/,                //负整数
        Chinese: /^[\u0391-\uFFE5]+$/,
        Zipcode: /^\d{6}$/,                     //邮编
        QQ: /^\d{4,12}$/,
        Price: /\d+(\.\d{1,2})?$/,
        Money: /\d+(\.\d{1,4})?$/,
        Letter: /^[A-Za-z]+$/,              //字母
        LetterU: /^[A-Z]+$/,                //大写字母
        LetterL: /^[a-z]+$/,                //小写字母
        Url: /^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/,
        Date: /^\d{4}(\-|\/|\.)\d{1,2}\1\d{1,2}$/,          //日期
    };
    a.Redirect=function(url){
        console.log(url);
        location.href=url;
    };

})(window);