$(function () {
  var $quantity = $('#quantity'),
    $money = $('#money'),
    $btnConfirm = $('#btn-confirm'),
    picTpl = document.getElementById("picTpl").innerHTML;

  var tradeno = GetQueryString("tradeno"),
    ext_tid = GetQueryString("ext_tid"),
    afsno = GetQueryString("afsno");

  var number = 0,
    money = 0,
    pic = {
      list: []
    };
  
  getOrderDetail();
  updatePicList();

  // 获取订单详情
  function getOrderDetail() {
    $.get(
      __BASEURL__ + "api/order/detail", {
        tradeno: tradeno
      },
      function (data) {
        if (data.success) {
          $('#tradeno').text(tradeno);

          for (var i = 0; i < data.data.order_ext.length; i++) {
            if (ext_tid == data.data.order_ext[i].ext_tid) {
              number = Number(data.data.order_ext[i].num);
              money = Number(data.data.order_ext[i].pay_money);

              $('#good-price').text(money);
              $('#good-number').text(number);
              $('#good-title').text(data.data.order_ext[i].goods_title);
              $('#pay-time').text(data.data.pay_time.alias);
              $quantity.val(number);
              $money.val(money);

              if (data.data.status.code == '2020' || data.data.status.code == '4020') {
                $quantity.prop('disabled', true);
                $money.prop('disabled', true);
              }else{
                $quantity.prop('disabled', false);
                $money.prop('disabled', false);
              }
            }
          }

          getRefundMoney();
        } else {
          layer.open({
            content: data.msg,
            skin: "msg",
            time: 1
          });
        }
      }
    );
  }

  // 获取可退款总金额
  function getRefundMoney() {
    $.post(
      __BASEURL__ + "api/afs/availableRefundAmount", autoCsrf({
        tradeno: tradeno,
        ext_tid: ext_tid
      }),
      function (data) {
        if (data.success) {
          money = Number(data.data.available_amount.total);
          $money.val(money);
        } else {
          layer.open({
            content: data.msg,
            skin: "msg",
            time: 1
          });
        }
      }
    );
  }

  // 修改数量
  $quantity.on('keyup', function(){
    var $this = $(this),
      value = $(this).val().replace(/\D/g, '');
    
    $this.val(value);

    if (Number(value) > number) {
      $this.val(number);
    }
  });

  // 修改金额
  $money.on('keyup', function(){
    var $this = $(this),
      value = $this.val().replace(/[^\d.]/g, '').replace(/^\./g, "").replace(/\.{2,}/g, ".").replace(".", "$#$").replace(/\./g, "").replace("$#$", ".").replace(/^(\-)*(\d+)\.(\d\d).*$/, '$1$2.$3');
    
    $this.val(value);
    
    if (Number(value) > money) {
      $this.val(money);
    }
  });

  // 转化base64为blob
  function convertBase64UrlToBlob(urlData) {
    var bytes = window.atob(urlData.split(',')[1]); //去掉url的头，并转换为byte

    //处理异常,将ascii码小于0的转换为大于0
    var ab = new ArrayBuffer(bytes.length),
      ia = new Uint8Array(ab);

    for (var i = 0; i < bytes.length; i++) {
      ia[i] = bytes.charCodeAt(i);
    }

    return new Blob([ab], {
      type: 'image/png'
    });
  } 

  // 添加图片
  function addPic(obj){
    var file = obj.files[0];
    
    if (window.FileReader) {
      var reader = new FileReader();

      reader.readAsDataURL(file);
      reader.onloadend = function (e) {
        uploadPic(e.target.result, file.name);
      };
    }
  }

  // 删除图片
  function delPic(i) {
    pic.list.splice(i, 1);

    updatePicList();
  }

  // 上传图片到服务器
  function uploadPic(source) {
    var postUrl = 'http://upload.qiniu.com/';

    $.get(
      __BASEURL__ + "qiniu_api/get_token", {
        type: 'afs_pic'
      },
      function (data) {
        if (data.success) {
          var formData = new FormData(),
            key = 'afs_pic' + '/' + new Date().getTime() + '_' + Math.floor(1000 + Math.random() * (9999 - 1000)) + '.' + 'png',
            up_token = data.data.up_token;

          source = convertBase64UrlToBlob(source);

          formData.append('file', source);
          formData.append('key', key);
          formData.append('token', up_token);

          $.ajax({
            url: postUrl,
            type: 'post',
            processData: false,
            contentType: false,
            data: formData,
            dataType: 'json',
            success: function (data) {
              if (data) {
                pic.list.push({
                  pic: __UPLOADURL__ + key
                });

                updatePicList();
              } else {
                layer.open({
                  content: data.msg,
                  skin: 'msg',
                  time: 1
                });
              }
            }
          });
        } else {
          layer.open({
            content: data.msg,
            skin: "msg",
            time: 1
          });
        }
      }
    );
  } 

  // 更新图片列表
  function updatePicList(){
    $("#pic-list").html(template(picTpl, pic));
  }

  // 提交退款申请
  $btnConfirm.on('click', function(){
    var reason = $('#reason').val(),
      quantity = $('#quantity').val(),
      money = $('#money').val(),
      remark = $('#remark').val(),
      pic_arr = [],
      postData,
      postUrl;

    $.each(pic.list, function(i, e){
      pic_arr[i] = e.pic;
    });

    postData = {
      type: 1,
      tradeno: tradeno,
      ext_tid: ext_tid,
      reason: reason,
      quantity: quantity,
      money: money,
      remark: remark,
      pic_arr: JSON.stringify(pic_arr)
    };

    if(!afsno) {
      postUrl = __BASEURL__ + "api/afs/create";
    }else{
      postData.afsno = afsno;
      postUrl = __BASEURL__ + "api/afs/edit";
    }

    if (!quantity) {
      layer.open({
        content: '请输入退款数量',
        skin: "msg",
        time: 1
      });

      return false;
    }
    
    if (!money) {
      layer.open({
        content: '请输入退款金额',
        skin: "msg",
        time: 1
      });

      return false;
    }

    $btnConfirm.prop("disabled", true).text('提交中...');

    $.post(
      postUrl, 
      autoCsrf(postData),
      function (data) {
        if (data.success) {
          if(!afsno){
            window.location.href = __BASEURL__ + "afs/detail?id=" + data.data;
          }else{
            window.location.href = __BASEURL__ + "afs/detail?id=" + afsno;
          }
        } else {
          layer.open({
            content: data.msg,
            skin: "msg",
            time: 1
          });
        }
        $btnConfirm.prop("disabled", false).text('提交');
      }
    );
  });

  window.addPic = addPic;
  window.delPic = delPic;
})