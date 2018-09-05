$(function(){
  var gameTpl = document.getElementById("gameTpl").innerHTML;

  var $btnConfirm = $('#btn-confirm');
  var saomaType = 1;
  var shop_id = $('#shop_id').val();
  var saomalink = '';
  var selectId = '';

  window.top.$('body').addClass('has-aside');

  function changeType(el){
    var radio = $(el).find('[name="saomaType"]');
    saomaType = radio.val();
    radio.prop('checked', true);
    if(saomaType==1){
      $('.saoma-two').hide();
      $('.saoma-one').show();
      $('.saoma-logo').attr('src',$('#upload-pic').attr('src'));
      $('.saoma-bs-title').hide();
    }else if(saomaType==2){
      $('.saoma-two').show();
      $('.saoma-one').hide();
      $('.saoma-bs-title').show();
      $('.saoma-logo').attr('src',$('#select-game option:selected').attr('data-img'));
    }else{
      $('.saoma-two').hide();
      $('.saoma-one').hide();
      $('.saoma-bs-title').hide();
      $('.saoma-logo').attr('src','');
    }
  }

  window.top.$('.J_NAVBAR_NAV_ITEM').find('a').each(function(){
    var $this = $(this),
      type = $this.data('type');
    if (type == 'index') {
      $(this).parent().addClass('active')
    }
    if (type == 'game') {
      $(this).parent().removeClass('active')
    }
  })

  $('body').on('click', '#goGame', function () {
    window.top.$('body').removeClass('has-aside');
    window.top.$('.J_NAVBAR_NAV_ITEM').find('a').each(function(){
      var $this = $(this),
        type = $this.data('type');
      if (type == 'game') {
        $(this).parent().addClass('active')
      }else{
        $(this).parent().siblings().removeClass('active')
      }
    })
  });


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
        $('#upload-logo-container').find('.upload-again').show();
        $('#upload-logo-container').find('.upload-plus').hide();
        $('#upload-logo-container').find('#upload-pic').attr('src', fullpath);
        $('.saoma-logo').attr('src', fullpath);
      }
    }
  });

  getSaoma();

  function getSaoma () {
    $.post(__BASEURL__ + "mshop/decorate_api/get_select_module_data", autoCsrf({
      module_id:4,
      shop_id:shop_id,
      type:0
    }), function (data) {
      if (data.success) {
        selectId = data.data.id;
        if(data.data.module_data.type==1){
          saomaType = 1
          $('.saoma-two').hide();
          $('.saoma-one').show();
          $('#upload-logo-container').find('.upload-again').show();
          $('#upload-logo-container').find('.upload-plus').hide();
          $('#good_logo').val(data.data.module_data.src).blur();
          $('#upload-logo-container').find('#upload-pic').attr('src', data.data.module_data.src);
          $('.saoma-logo').attr('src',data.data.module_data.src);
          $('.saoma-bs-title').hide();
          $('#saomaType1').prop('checked', true);
        }else if(data.data.module_data.type==2){
          saomaType = 2
          saomalink = data.data.module_data.link
          $('.saoma-two').show();
          $('.saoma-one').hide();
          $('.saoma-bs-title').show();
          $('.saoma-logo').attr('src',data.data.module_data.src);
          $('#saomaType2').prop('checked', true);
        }else{
          saomaType = 0
          $('.saoma-two').hide();
          $('.saoma-one').hide();
          $('.saoma-logo').attr('src','');
          $('#saomaType3').prop('checked', true);
          $('.saoma-bs-title').hide();
        }
        initGame();
      }
    });
  }

  function edit() {
    $('#decorate-ctrl')
    .bootstrapValidator({
      fields: {
        good_logo: {
          validators: {
            notEmpty: {
              message: '图片不能为空'
            }
          }
        }          
      }
    }) 
    var bootstrapValidator = $('#decorate-ctrl').data('bootstrapValidator');
    bootstrapValidator.validate();
    console.info($('#select-game option:selected').val(),$('#select-game option:selected').attr('data-img'))
    if (bootstrapValidator.isValid()) {
      $btnConfirm.prop('disabled', false);
      var src,link;
      if(saomaType==1){
        src = $('.saoma-logo').attr('src');
        link = '';
      }else if(saomaType==2){
        src = $('#select-game option:selected').attr('data-img')
        link = $('#select-game option:selected').val()
      }
      var json = {
        type:saomaType,
        link:link,
        src:src
      };
      $.post(__BASEURL__ + 'mshop/decorate_api/save_module_data', autoCsrf({
        shop_id:shop_id,
        module_id:4,
        json:JSON.stringify(json),
        is_save:1,
        id:selectId
      }), function (data) {
        if (data.success) {
          new Msg({
            type: 'success',
            msg: data.msg
          });
          window.location.href = __BASEURL__ + "mshop/shop";
        } else {
          new Msg({
            type: 'danger',
            msg: data.msg
          });
        }
        $btnConfirm.prop('disabled', false);
      })
    }
  }

  function initGame() {
    $.getJSON(__BASEURL__ + "hd_api/hd_list", {
      
    }, function (data) {
      if (data.success) {
        $("#select-game").html(template(gameTpl,data.data));
        console.info(saomalink)
        $("#select-game option[value='" + saomalink + "']").attr(
          "selected",
          "selected"
        );
      }
    });
  }

  window.edit = edit;
  window.changeType = changeType;
})