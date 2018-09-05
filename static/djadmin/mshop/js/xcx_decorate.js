/**
 * xcx_decorate.js
 * by liangya
 * date: 2018-06-14
 */

$(function(){
  var $title = $("#title"),
    $phoneTitle = $('#phone-title'),
    $banner = $("#banner"),
    $xcxBanner = $('#xcx-banner'),
    $uploadBanner = $('#upload-banner'),
    $uploadBannerContainer = $('#upload-banner-container'),
    $actionBox = $(".action-box"),
    $btnCancel = $("#btn-cancel")
    $btnEdit = $("#btn-edit"),
    $btnSave = $('#btn-save');

  var id = '',
    default_banner = 'http://oydp172vs.bkt.clouddn.com/wsc_goods/1521599389689_8452.png',
    default_title = '';

  initUploadBanner();
  getXcxData();

  // 初始化上传banner
  function initUploadBanner() {
    uploadFile('main_header', {
      browse_button: 'upload-banner',
      container: 'upload-banner-container',
      drop_element: 'upload-banner-container',
      max_file_size: '1mb',
      chunk_size: '1mb',
      init: {
        'FileUploaded': function (up, file, info) {
          var res = JSON.parse(info.response);
          var banner = up.getOption('domain') + res.key;

          changeBanner(banner);
          $uploadBannerContainer.find('.upload-again').show();
        }
      }
    })
  }

  // 获取小程序装修数据
  function getXcxData(){
    $.getJSON(
      __BASEURL__ + "mshop/decorate_api/get_xcx_index_data", {
        module_id:3
      },
      function (data) {
        if (data.success) {
          var data = data.data;

          if (!data) {
            showAction();
            return;
          }

          id = data.id;

          if (data.module_data && data.module_data.img) {
            default_banner = data.module_data.img;
          }

          if (data.module_data && data.module_data.title) {
            default_title = data.module_data.title;
          }

          changeTitle(default_title);
          changeBanner(default_banner);
          $uploadBannerContainer.find('.upload-plus').hide();

          if (data.module_data.img && data.module_data.title) {
            hideAction();
          } else {
            showAction();
          }
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          })
        }
      }
    );
  }

  // 修改标题
  function changeTitle(title) {
    $title.val(default_title);
    $phoneTitle.text(default_title);
  }

  // 修改banner图
  function changeBanner(banner) {
    $banner.val(banner);
    $xcxBanner.attr('src', banner);
    $uploadBannerContainer.find('.upload-pic').attr('src', banner);
  }

  // 进入编辑状态
  function showAction() {
    $btnEdit.hide();
    $actionBox.show();
    $title.prop('disabled', false);
    $uploadBanner.show().next().show();
    $uploadBannerContainer.find('.upload-again').show();
  }

  // 进入不可编辑状态
  function hideAction() {
    $btnEdit.show();
    $actionBox.hide();
    $title.prop('disabled', true);
    $uploadBanner.hide().next().hide();
    $uploadBannerContainer.find('.upload-again').hide();
  }

  // 监听标题输入
  $title.on('input', function() {
    $phoneTitle.text($title.val());
  })

  // 撤销
  $btnCancel.on('click', function() {
    hideAction();
    changeTitle(default_title);
    changeBanner(default_banner);
  })

  // 编辑
  $btnEdit.on('click', function() {
    showAction();
  })

  // 保存
  $btnSave.on('click', function() {
    var banner = $banner.val();
    var title = $title.val();

    if (!banner) {
      new Msg({
        type: 'danger',
        msg: '请上传banner图'
      })

      return false;
    }

    if (!title) {
      new Msg({
        type: 'danger',
        msg: '标题不能为空'
      })

      return false;
    }

    if (title.length > 20) {
      new Msg({
        type: 'danger',
        msg: '标题不得超过20个字符'
      })

      return false;
    }
    
    $btnSave.prop('disabled', true).text('保存中...');

    $.post(__BASEURL__ + 'mshop/decorate_api/save_module_data', autoCsrf({
      id: id,
      module_id: 3,
      json: JSON.stringify({
        img: banner,
        title: title,
        is_on: 1
      }),
      is_save: 1,
      shop_id: 0
    }), function(data) {
      $btnSave.prop('disabled', false).text('保存');

      if (data.success) {
        default_banner = banner;
        default_title = title;
        hideAction();

        new Msg({
          type: 'success',
          msg: '保存成功'
        })
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
    })
  })
})
