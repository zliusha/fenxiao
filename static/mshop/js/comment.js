$(function(){
  FastClick.attach(document.body);
  var photoTpl = document.getElementById("photoTpl").innerHTML,
      goodTpl = document.getElementById('goodTpl').innerHTML;

  var photo = {
    list:[]
  }
  var good = {
    list:[]
  }

  var pic_arr = [];

  var goods_comment_data = [];

  var order_id = $('#order-id').val(),
    shop_id = '',
    $btnConfirm = $('#btn-confirm');

  $('body').on('click','.u-label',function(){
    if($(this).hasClass('active')){
      $(this).removeClass('active');
    }else{
      $(this).addClass('active');
    }
  });

  $('body').on('click','.m-star-item',function(){
    var index = $(this).index();
    if(index==0 && !$(this).next().hasClass('active')){
      if($(this).hasClass('active')){
        $(this).siblings().removeClass('active');
        $(this).removeClass('active');
      }else{
        $(this).addClass('active');
      }
    }else{
      $(this).prevAll().addClass('active');
      $(this).addClass('active');
      $(this).nextAll().removeClass('active');
    }
  })


  function initGoodList(){
    $.getJSON(__BASEURL__ + "api/comment/order_goods_list", {
      order_id:order_id
    }, function (data) {
      if (data.success) {
        good.list = data.data.goods_list;
        for(var i=0;i<good.list.length;i++){
          var  val = '';
          if(good.list[i].sku_str!=''){
            val = good.list[i].goods_title+'-'+good.list[i].sku_str;
          }else{
            val = good.list[i].goods_title;
          }
          goods_comment_data.push({
            score:'',
            tags:'',
            content:'',
            goods_id:good.list[i].goods_id,
            ext:val
          })
        }
        $('#m-shop-title').html(data.data.shop_model.shop_name);
        $('#m-comment-logo').attr('src',data.data.shop_model.shop_logo);
        shop_id = data.data.shop_model.id;
        $("#good-list").html(template(goodTpl, good));
      }else{
        $('.g-content').hide();
        $('.g-footer').hide();
        var html = '<div class="m-empty">'+
                    '<p>亲，订单不存在！</p>'+
                    '<p><a href="'+__BASEURL__+'order/index" class="u-btn u-btn-primary">返回订单列表</a></p>'+
                  '</div>';
        $('body').append(html);
      }
    });
  }

  initGoodList();

  function handleFiles(obj){
    var file = $(obj);
    console.info(file)
    var f=file.context.files[0];
    console.log( "文件大小:" + (f.size / 1024).toFixed( 1 ) + "kB" );
    if( window.FileReader )
    {
      var reader = new FileReader();
      reader.readAsDataURL( f );
      //监听文件读取结束后事件
      reader.onloadend = function( e )
      {
        console.info(e.target.result)
        showBanner(e.target.result,f.name);
      };
    }

    function showBanner(source){
      $.getJSON(
        __BASEURL__ + "qiniu_api/get_token", {
          type:'comment_pic'
        },
        function (data) {
          if (data.success) {
             upload_url = data.data.upload_url;
             up_token = data.data.up_token;
              source = convertBase64UrlToBlob(source)
              var formData = new FormData();
              console.info(source)
              var key = 'comment_pic' + '/' + new Date().getTime() + '_' + Math.floor(1000 + Math.random() * (9999 - 1000)) + '.' + 'png';
              formData.append('file', source);
              formData.append('key',key);
              formData.append('token',up_token);
              console.info(formData)
              $.ajax({
                url: 'http://upload.qiniu.com/',
                type: 'post',
                processData: false,
                contentType: false,
                data: formData ,
                dataType: 'json',
                success: function( data )
                {
                  if( data )
                  {
                    layer.open( {
                      content: "图片上传成功",
                      skin: 'msg',
                      time: 1
                    } );
                    photo.list.push({
                      pic:__UPLOADURL__ +key
                    })
                    pic_arr.push(key)
                    $("#photo-list").html(template(photoTpl, photo));
                    if(photo.list.length>=5){
                      $('.photo-box').hide();
                    }
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

  function delImg(el,i){
    photo.list.splice(i, 1);
    pic_arr.splice(i,1);
    $("#photo-list").html(template(photoTpl, photo));
    if(photo.list.length<5){
      $('.photo-box').show();
    }
  }

  $btnConfirm.on('click',function(){
    var business_serv_score = 5,
      business_serv_tags = [],
      business_serv_content = $('#bus-comment-text').val(),
      shipping_serv_score = 5,
      shipping_serv_tags = [],
      shipping_serv_content = $('#ship-comment-text').val();
      console.info(pic_arr)
      $('.m-comment-good-item').each(function(){
        var index = $(this).index();
        var text = $(this).find('.m-comment-text').val();
        var $tags = $(this).find('.u-label.active');
        var $star = $(this).find('.m-star-item.active');
        var tags = [];
        var star = 5;
        $tags.each(function(){
          tags.push($(this).attr('data-id'));
        })
        var n = 0;
        $star.each(function(){
          n++;
        })
        if(n!=0){
          star = n;
        }else{
          star = 3;
        }
        goods_comment_data[index].content = text;
        goods_comment_data[index].tags = tags.toString();
        goods_comment_data[index].score = star;
      })

      var n = 0;
      $('.bus-star-item.active').each(function(){
        n++;
      })
      if(n!=0){
        business_serv_score = n;
      }else{
        business_serv_score = 3;
      }

      var v = 0;
      $('.ship-star-item.active').each(function(){
        v++;
      })
      if(v!=0){
        shipping_serv_score = v;
      }else{
        shipping_serv_score = 3;
      }

      $('.bus-label.active').each(function(){
        business_serv_tags.push($(this).attr('data-id'))
      })

      $('.ship-label.active').each(function(){
        shipping_serv_tags.push($(this).attr('data-id'))
      })

      console.info(goods_comment_data)

    $.post(
      __BASEURL__ + "api/comment/add",
      autoCsrf({
        shop_id:shop_id,
        order_id:order_id,
        business_serv_score:business_serv_score,
        business_serv_tags:business_serv_tags.toString(),
        business_serv_content :business_serv_content,
        shipping_serv_score:shipping_serv_score,
        shipping_serv_tags:shipping_serv_tags.toString(),
        shipping_serv_content:shipping_serv_content,
        goods_comment_data:JSON.stringify(goods_comment_data),
        picarr:pic_arr.toString()
      }),
      function (data) {
        if (data.success) {
          layer.open({
            content: data.msg,
            skin: "msg",
            time: 1
          });
          setTimeout(function(){
            window.location.href = __BASEURL__+'comment/success/'+order_id;
          },1000)
        } else {
          layer.open({
            content: data.msg,
            skin: "msg",
            time: 1
          });
        }
        $btnConfirm.prop('disabled', false);
      }
    );
  })

  window.delImg = delImg;
  window.handleFiles = handleFiles;          
})