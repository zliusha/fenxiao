/**
 * order_list.js
 * by jinmu
 * date: 2017-11-21
 */
$(function () {

  var couponForm = $(".coupon-form"),
    couponEditForm = $(".coupon-edit-form"),
    couponCard = $(".coupon-card"),
    couponLimit = $(".coupon_limit"),
    couponEnd=$(".coupon_end"),
    couponListView = $(".couponListView");

  var receive_btn = $("#receive_btn"),
    use_btn = $("#use_btn"),
    edit_btn = $("#edit_btn");


  var couponListTpl = document.getElementById("couponListTpl").innerHTML,
    myCouponTpl = document.getElementById("myCouponTpl").innerHTML;

  var phone = "",
    limit_num = 4,
    is_receive = true,
    end_tine="2017-11-24 10:10:02",
    tradeno="",
    coupons = {
      list: []
    },
    myCoupon = {
      list: [
        /* {
         full_price:"30",
          price:"8",
          overTime:"2",
           phone:phone
       }*/
      ]
    };




  function getCouponInfo() {
    phone = "13732285415";
    //phone="";

    //优惠券时间判断
    var nowDate= new Date();
    var EndTime = new Date(end_tine.replace(/-/g, '/'));
    var t = parseInt(EndTime.getTime()) - (new Date(nowDate).getTime());
    if(t<=0){
       //活动结束
      couponEnd.show();
      couponForm.hide();
      couponCard.hide();

      receive_btn.hide();
    }else{
      //活动未结束
      couponEnd.hide();

      if (phone == "") {
        couponForm.show();
        couponCard.hide();
        couponListView.hide();
        receive_btn.show();
        use_btn.hide();
      } else {
        $(".default_phone").val(phone);
        //如果手机号存在还有券也没有领取
        if (myCoupon.list.length > 0 && is_receive) {
          getMyCoupon();//我的优惠券
          couponForm.hide();
          couponCard.show();
          couponListView.show();
          receive_btn.hide();
          use_btn.show();
          couponList();
        } else {
          couponForm.show();
          couponCard.hide();
          couponListView.show();
          receive_btn.show();
          use_btn.hide();
        }

      }
    }


  }
  
  function getCouponData() {


    var url=window.location.href;	  //获取当前页面的url
    var myurl=GetQueryString("tradeno");
    var len=url.length;   //获取url的长度值
    var a = url.lastIndexOf('=');//查找最后一个a出现的位置
    //tradeno=url.substr(a+1,len);   //截取问号之后的内容
    tradeno=myurl;
    $.post(
      __BASEURL__ + "api/coupon/load", autoCsrf({tradeno: tradeno}),
      function (data) {
        if (data.success) {
          var dataInfo=data.data;
         console.log(data);
          phone=dataInfo.mobile;
          //phone=null;
          if(dataInfo.coupon!=null){
            myCoupon.list.push(dataInfo.coupon);
          }

          coupons.list=dataInfo.list;
          var code=data.code;
          //优惠券时间判断
            if(code=="004"){
              //活动结束
            couponEnd.show();
            couponForm.hide();
            couponCard.hide();

            receive_btn.hide();
          }else{
            //活动未结束
            couponEnd.hide();

            if (phone == null) {
              couponForm.show();
              couponCard.hide();
              couponListView.hide();
              receive_btn.show();
              use_btn.hide();
            } else {
              $(".default_phone").val(phone);
              //如果手机号存在还有券也没有领取
              if (myCoupon.list.length>0) {
                getMyCoupon();//我的优惠券
                couponForm.hide();
                couponCard.show();
                couponListView.show();
                receive_btn.hide();
                use_btn.show();
                couponList();
              } else {
                $.post( __BASEURL__ + "api/coupon/pickup", autoCsrf({tradeno: tradeno}), function (data) {
                  if (data.success) {
                    layer.open({
                      content: data.msg,
                      skin: "msg",
                      time: 1,
                      success: function(){
                        getCouponData();
                        //window.location.href =  document.referrer;
                      }
                    });
                   // window.location.href = document.referrer;
                  } else {
                    layer.open({
                      content: data.msg,
                      skin: "msg",
                      time: 1
                    });
                  }
                  receive_btn.prop('disabled', false);
                })
              }

            }
          }

        }else{
          phone=data.data.mobile;
          //phone=null;
          myCoupon.list.push(data.data.coupon);
          coupons.list=data.data.list;
          if(data.code=="004"){
            //couponEnd.show();
            if(data.msg=="已领取过优惠券"){
              layer.open({
                content: data.msg,
                skin: "msg",
                time: 1
              });
              couponEnd.hide();
              couponForm.hide();
              couponCard.show();
              getMyCoupon();
              couponList();
              couponListView.show();

              receive_btn.hide();
              use_btn.show();
            }else{
              layer.open({
                content: data.msg,
                skin: "msg",
                time: 1
              });
              couponEnd.show();
              couponEnd.find("p").html(data.msg);
              couponForm.hide();
              couponCard.hide();
              couponListView.show();
              //getMyCoupon();
              couponList();
              receive_btn.hide();
            }

          }
        }
      }
    );

  }

  getCouponData();

  //立即领取
  function receiveClick() {
    phone = $(".default_phone").val();
    if (!(/^(13|15|18|17)[0-9]{9}$/.test(phone))) {
      layer.open({
        content: "请输入正确的手机号码",
        skin: "msg",
        time: 1
      });
      return false;
    }

    if (limit_num >= 5) {
      console.log(limit_num);
      couponForm.hide();
      couponCard.hide();
      couponLimit.show();
      return false;
    } else {
      receive_btn.prop('disabled', true);
      $.post( __BASEURL__ + "api/coupon/pickup", autoCsrf({tradeno: tradeno,mobile:phone}), function (data) {
          if (data.success) {
            layer.open({
              content: data.msg,
              skin: "msg",
              time: 1,
              success: function(){
                getCouponData();
               // window.location.href = document.referrer;
              }
            });


        /*    getMyCoupon();
            couponList();

            //列表显示
            couponForm.hide();
            couponCard.show();
            couponListView.show();
            receive_btn.hide();
            use_btn.show();*/

          } else {
            layer.open({
              content: data.msg,
              skin: "msg",
              time: 1
            });
          }
        receive_btn.prop('disabled', false);
        }
      );
    }


  }

  //我获取的优惠券
  function getMyCoupon() {
    $(".coupon-card").html(template(myCouponTpl, myCoupon));
  }

  //优惠券列表
  function couponList() {
    $(".coupon-rank-list").html(template(couponListTpl, coupons));
  }

  //修改手机号
  function editPhone() {
    $(".now_phone").html(phone);
    $(".edit_phone").val(phone);

    couponEditForm.show();
    couponCard.hide();

    edit_btn.show();
    use_btn.hide();
  }

  //修改手机号提交表单
  function editFromClick() {
    phone = $(".edit_phone").val();
    //Tel: /^(13|15|18|17)[0-9]{9}$/, //手机
    if (!(/^(13|15|18|17)[0-9]{9}$/.test(phone))) {
      layer.open({
        content: "请输入正确的手机号码",
        skin: "msg",
        time: 1
      });
      return false;
    }

    edit_btn.prop('disabled', true);
    $.post( __BASEURL__ + "api/coupon/changeMobile", autoCsrf({mobile: phone}), function (data) {
      if (data.success) {
        layer.open({
          content: data.msg,
          skin: "msg",
          time: 1
        });
        getMyCoupon();
        couponList();
        //列表显示
        couponEditForm.hide();
        couponCard.show();
        edit_btn.hide();
        use_btn.show();

      } else {
        layer.open({
          content: data.msg,
          skin: "msg",
          time: 1
        });
      }
      edit_btn.prop('disabled', false);
    });


  }


  //getCouponInfo();

  window.receiveClick = receiveClick;
  window.editPhone = editPhone;
  window.editFromClick=editFromClick;
});