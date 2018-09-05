$(function () {
  var $userAvatar = $("#user-avatar"),
    $userName = $("#user-name"),
    $userPhone = $("#user-phone"),
    $userInput=$("#userInput");

  getUserInfo();

  // 初始化生日日期
  function initBirDate() {
    var year = new Date().getFullYear();

    for (var i = 1950; i < year; i++) {
      birData.push({
        name: i.toString(),
        level: []
      });
    }

    for (var i = 0; i < birData.length; i++) {
      for (var j = 1; j < 13; j++) {
        birData[i].level.push({
          name: j.toString(),
          level: []
        });
      }
    }

    for (var i = 0; i < birData.length; i++) {
      for (var j = 0; j < birData[i].level.length; j++) {
        if (j == 1) {
          if ((i % 4 == 0 && i % 100 != 0) || (i % 400 == 0 && i % 4000 != 0)) {
            for (var k = 1; k < 30; k++) {
              birData[i].level[j].level.push({
                name: k.toString()
              });
            }
          } else {
            for (var k = 1; k < 29; k++) {
              birData[i].level[j].level.push({
                name: k.toString()
              });
            }
          }
        } else {
          if (j == 3 || j == 5 || j == 8 || j == 10) {
            for (var k = 1; k < 31; k++) {
              birData[i].level[j].level.push({
                name: k.toString()
              });
            }
          } else {
            for (var k = 1; k < 32; k++) {
              birData[i].level[j].level.push({
                name: k.toString()
              });
            }
          }
        }
      }
    }
  }

  // 获取用户信息
  function getUserInfo() {
    $.getJSON(__BASEURL__ + "api/user/info", {}, function (data) {
      if (data.success) {
        data.data.img && $userAvatar.attr('src', data.data.img);
        $userName.text(data.data.username);
        $userInput.val(data.data.username);
        $userPhone.text(data.data.mobile);
        if(!data.data.mobile) {
          $userPhone.parent('a').attr('href', __BASEURL__+'user/bind_phone');
        }
      }
    });
  }

  // 修改用户名
  function editName(el) {
    $(el).hide();

    $userInput.show();
    $userInput.focus();

    $userInput.blur(function () {
      var name = $(this).val();

      if(!name){
        layer.open({
          content:  '用户名不能为空',
          skin: "msg",
          time: 1
        });
      }else{
        $(el).text(name);
        $(el).show();
        $userInput.hide();

        updateUserInfo('username', name);
      }
    });
  }

  //修改头像图片
  function handleFiles(obj){
    var file = $(obj);
    var f=file[0].files[0];

    if( window.FileReader ){
      var reader = new FileReader();
      reader.readAsDataURL( f );
      //监听文件读取结束后事件
      reader.onloadend = function( e ){
        showBanner(e.target.result,f.name);
      };
    }

    function showBanner(source){
      $.getJSON(
        __BASEURL__ + "qiniu_api/get_token", {
          type:'afs_pic'
        },
        function (data) {
          if (data.success) {
            upload_url = data.data.upload_url;
            up_token = data.data.up_token;
            source = convertBase64UrlToBlob(source);

            var formData = new FormData();
            var key = 'afs_pic' + '/' + new Date().getTime() + '_' + Math.floor(1000 + Math.random() * (9999 - 1000)) + '.' + 'png';

            formData.append('file', source);
            formData.append('key',key);
            formData.append('token',up_token);

            $.ajax({
              url: 'http://upload.qiniu.com/',
              type: 'post',
              processData: false,
              contentType: false,
              data: formData ,
              dataType: 'json',
              success: function( data )
              {
                if( data ){
                  $("#user-avatar").attr('src',__UPLOADURL__ +key);

                  updateUserInfo('img', __UPLOADURL__ +key);
                }
                else
                {
                  layer.open( {
                    content: data.msg,
                    skin: 'msg',
                    time: 1
                  } );
                }
              },
              error: function (jqXHR, textStatus, errorThrown) {
                //
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
  }

  function convertBase64UrlToBlob(urlData){
    var bytes=window.atob(urlData.split(',')[1]);        //去掉url的头，并转换为byte

    //处理异常,将ascii码小于0的转换为大于0
    var ab = new ArrayBuffer(bytes.length);
    var ia = new Uint8Array(ab);

    for (var i = 0; i < bytes.length; i++) {
      ia[i] = bytes.charCodeAt(i);
    }

    return new Blob( [ab] , {type : 'image/png'});
  }

  // 更新用户信息
  function updateUserInfo(field, value) {
    $.post(
      __BASEURL__ + "api/user/edit",
      autoCsrf({
        field: field,
        value: value
      }),
      function (data) {
        if (data.success) {
          layer.open({
            content: '保存成功',
            skin: "msg",
            time: 1
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

  window.editName = editName;
  window.handleFiles = handleFiles;
});
