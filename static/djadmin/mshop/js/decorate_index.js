$(function(){

  var $decorateCtrl = $('#decorate-ctrl'),
    $editDecorate = $('#edit-decorate'),
    $btnConfirm = $('#btn-confirm'),
    $previewModal = $('#previewModal'),
    $startDecorate = $('#start-decorate');

  var menuTpl = document.getElementById('menuTpl').innerHTML,
  posterTpl = document.getElementById('posterTpl').innerHTML,
  showSelectTpl = document.getElementById('showSelectTpl').innerHTML;

  var shop_id = $('#shop_id').val();

  var selectGoods = {
    list:[]
  };

  var sureGoods = {
    title:'店长推荐',
    list:[]
  } 

  var poster = {
    list:[]
  }

    // 切换子菜单
  $('.J_TOGGLE_SUBNAV').on('click', function () {
    var $this = $(this);

    $this.next().slideToggle();
    $this.parent().toggleClass('open');
  });

  function startDecorate(){
    $editDecorate.hide();
    $startDecorate.hide();
    $decorateCtrl.show();
  }

  function editDecorate(){
    $editDecorate.hide();
    $startDecorate.hide();
    $decorateCtrl.show();
  }

  function getInfo(){
    $.getJSON(
      __BASEURL__ + "mshop/decorate_api/preview_shop_info", {
        shop_id:shop_id
      },
      function (data) {
        if (data.success) {
          if(data.data.shop.bg_img){
            $editDecorate.show();
            $startDecorate.hide();
            $('#edit-banner-logo').attr('src',__UPLOADURL__+data.data.shop.bg_img)
          }else{
            $editDecorate.hide();
            $startDecorate.show();

          }
          $('#shop-name').html(data.data.shop.shop_name);
          $('#phone-title').html(data.data.shop.shop_name);
          $('#shop-notice').html(data.data.shop.notice);
          $('#arrive_time').html(data.data.shop.arrive_time);
          $('#shop-logo').attr('src',data.data.shop.shop_logo);
          if(data.data.shop.bg_img){
            var url = __UPLOADURL__+data.data.shop.bg_img;
          }else{
            var url = data.data.shop.shop_logo;
          }

           $('.store-detail-bottom-logo').attr('src',url)
           $('#shop-name2').html(data.data.shop.shop_name);
          $('#phone-title2').html(data.data.shop.shop_name);
          $('#shop-notice2').html(data.data.shop.notice);
          $('#arrive_time2').html(data.data.shop.arrive_time);
          $('#shop-logo2').attr('src',data.data.shop.shop_logo);

          if(data.data.shop.bg_img){
            $('#good_logo').val(data.data.shop.bg_img).blur();
            $('#upload-logo-container').find('.upload-again').show();
            $('#upload-logo-container').find('.upload-plus').hide();
            $('#upload-logo-container').find('#upload-pic').attr('src',__UPLOADURL__+data.data.shop.bg_img);
          }
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );    
  }

  getInfo();

  function getGoodList(){
    $.getJSON(
      __BASEURL__ + "mshop/decorate_api/preview_cate_goods_list", {
        shop_id:shop_id
      },
      function (data) {
        if (data.success) {
          var list = data.data;
          var menu = {
            list:data.data
          }
          $('#menu-list').html(template(menuTpl, menu));
          $('#menu-list2').html(template(menuTpl, menu));
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  }

  getGoodList();


  function getModule(){
    $.getJSON(
      __BASEURL__ + "mshop/decorate_api/get_modules_data", {
        type:0,
        shop_id:shop_id
      },
      function (data) {
        if (data.success) {
          if(data.data.tj_goods_modules!=''){
            sureGoods.list = data.data.tj_goods_modules[0].sys_data;
            sureGoods.title = data.data.tj_goods_modules[0].module_data.title;
            recommend_id = data.data.tj_goods_modules[0].id;
            $('.recommend_title').html(data.data.tj_goods_modules[0].module_data.title);
          }
          $('#show-select-good').html(template(showSelectTpl,sureGoods));
          $('#show-select-good2').html(template(showSelectTpl,sureGoods));
          poster.list = data.data.poster_modules;


          $('#poster-tbody').html(template(posterTpl, poster));
          $('#poster-tbody2').html(template(posterTpl, poster));
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );    
  }

  getModule();

  function delImg(){
    $('#good_logo').val('');
    $('#upload-pic').attr('src', '');
    $('#upload-plus').show();
    $('#upload-logo-container').find('.upload-again').hide();
  }

  function release(){

    var bg_img = $('#good_logo').val();
    if(!bg_img){
      new Msg({
        type: 'danger',
        msg: '请上传图片'
      });
      return false;
    }
    $.post(__BASEURL__ + 'mshop/decorate_api/store', autoCsrf({
      bg_img:bg_img,
      shop_id:shop_id
    }), function (data) {
      if (data.success) {
        new Msg({
          type: 'success',
          msg: data.msg
        });
        
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
      $btnConfirm.prop('disabled', false);
      $previewModal.modal('hide');
      $editDecorate.show();
      $decorateCtrl.hide();
      getInfo();
    });
  }

  function preview(){
    $previewModal.modal('show');
  }


  uploadFile('wsc_goods', {
    browse_button: 'upload-logo',
    container: 'upload-logo-container',
    drop_element: 'upload-logo-container',
    max_file_size: '10mb',
    chunk_size: '4mb',
    init: {
      'FileUploaded': function (up, file, info) {
        var res = JSON.parse(info.response);
        var halfpath = res.key;
        var fullpath = up.getOption('domain') + halfpath;
        $('#good_logo').val(halfpath).blur();
        var url = 'url('+fullpath+')';
        $('.store-detail-bottom').css("background-image",url);
        $('#upload-logo-container').find('.upload-again').show();
        $('#upload-logo-container').find('.upload-plus').hide();
        $('#upload-logo-container').find('#upload-pic').attr('src', fullpath);
      }
    }
  });

  function changePhoneType(el){
    $(el).addClass('active');
    $(el).siblings().removeClass('active');
    if($('.phone-preview-box').hasClass('phone-x')){
      $('.phone-preview-box').removeClass('phone-x')
    }else{
      $('.phone-preview-box').addClass('phone-x')
    }
  }

  window.changePhoneType = changePhoneType;
  window.delImg = delImg;
  window.preview = preview;
  window.release = release;
  window.editDecorate = editDecorate;
  window.startDecorate = startDecorate;
})