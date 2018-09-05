$(function(){

  var $decorateCtrl = $('#decorate-ctrl'),
    $editDecorate = $('#edit-decorate'),
    $previewModal = $('#previewModal'),
    $addGoodModal = $('#addGoodModal'),
    $btnConfirm = $('#btn-confirm'),
    $start_time=$("#start_time"),
    $end_time=$("#end_time"),
    $startDecorate = $('#start-decorate');

  var menuTpl = document.getElementById('menuTpl').innerHTML
    addGoodTpl = document.getElementById('addGoodTpl').innerHTML,
    editPostTpl = document.getElementById('editPostTpl').innerHTML,
    posterTpl = document.getElementById('posterTpl').innerHTML,
    selectGoodTpl = document.getElementById('selectGoodTpl').innerHTML,
    showSelectTpl = document.getElementById('showSelectTpl').innerHTML,
    sureGoodTpl = document.getElementById('sureGoodTpl').innerHTML;;

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

  var initialImg = '';

  var selectPoster = '';

  var selectNumber = '';

  var selectId = '';

  function dataTimePick() {
      $('#datetimeStart').datetimepicker({
        format: 'hh:ii',
        todayBtn: true,
        language:'zh-CN',
        pickerPosition:"bottom-right",
        startView:1,
        autoclose: 1//选择后自动关闭
      });
      $('#datetimeEnd').datetimepicker({
        format: 'hh:ii',
        todayBtn: true,
        language:'zh-CN',
        pickerPosition:"bottom-right",
        startView:1,
        autoclose: 1//选择后自动关闭
      });
  }

  dataTimePick();

  function getInfo(){
    $.getJSON(
      __BASEURL__ + "mshop/decorate_api/preview_shop_info", {
        shop_id:shop_id
      },
      function (data) {
        if (data.success) {
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

  function selectTime() {
    //时间
    $(".form_datetime").datetimepicker({
      format: "yyyy-mm-dd",
      todayBtn: true,
      language:'zh-CN',
      pickerPosition:"bottom-right",
      minView: "month",//设置只显示到月份
      autoclose: 1//选择后自动关闭
    });

  }
  selectTime();

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
          $('#add-good-tbody').html(template(addGoodTpl, menu));
          // 切换子菜单
          $('.J_TOGGLE_SUBNAV').on('click', function () {
            var $this = $(this);

            $this.next().slideToggle();
            $this.parent().toggleClass('open');
          });
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
          sureGoods.list = [];
          if(data.data.tj_goods_modules!=''){
            sureGoods.list = data.data.tj_goods_modules[0].sys_data;
            sureGoods.title = data.data.tj_goods_modules[0].module_data.title;
            recommend_id = data.data.tj_goods_modules[0].id;
            $('.recommend_title').html(data.data.tj_goods_modules[0].module_data.title);
          }
          $('#show-select-good').html(template(showSelectTpl,sureGoods));
          $('#show-select-good2').html(template(showSelectTpl,sureGoods));
          poster.list = data.data.poster_modules;
          if(poster.list){
            $('#edit-decorate').show();
          }
          if(poster.list.length>=3){
            $('#start-decorate').hide();
          }else{
            $('#start-decorate').show();
          }
          $('#edit-decorate').html(template(editPostTpl,poster))

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

  $('body').on('click', '[name="selectGood"]', function () {
    var is_number = true;
    var _this = $(this);
    $('[name="selectGood"]:checked').each(function () {
      var number = $('[name="selectGood"]:checked').length;
      if(number>20){
        _this.prop('checked',false);
        new Msg({
          type: 'danger',
          msg: '最多添加20个'
        });
        is_number = false;
      }
    });
    if(is_number){
      selectGoods.list = [];
      $('[name="selectGood"]:checked').each(function () {
        var v = true;
        var id = $(this).val();
        for(var i =0;i<selectGoods.list.length;i++){
          if(id == selectGoods.list[i].id){
            v = false;
          }
        }
        var title = $(this).attr('data-title');
        var price = $(this).attr('data-price');
        var img = $(this).attr('data-img');
        if(v){
          selectGoods.list.push({
            id:id,
            title:title,
            price:price,
            img:img
          });
        }
      });
      $('#select-good-tbody').html(template(selectGoodTpl, selectGoods));
    }
  });  


  // $('body').on('click', '[name="selectMenu"]', function () {

  //   $('[name="selectMenu"]:checked').each(function () {
  //     $(this).parents('.J_TOGGLE_SUBNAV').find('[name="selectMenu"]:checked').each(function(){
  //       $(this).prop('checked',true);
  //     })
  //   });
  // }); 


  function delImg(){
    $('#good_logo').val('');
    $('#upload-pic').attr('src', '');
    $('#upload-plus').show();
    $('#upload-logo-container').find('.upload-again').hide();
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
        $('#good_logo').val(__UPLOADURL__+halfpath).blur();
        $('#upload-logo-container').find('.upload-again').show();
        $('#upload-logo-container').find('.upload-plus').hide();
        $('#upload-logo-container').find('#upload-pic').attr('src', fullpath);
        poster.list[selectNumber].module_data.img = __UPLOADURL__+halfpath;
        $('#poster-tbody').html(template(posterTpl, poster));
        $('#poster-tbody2').html(template(posterTpl, poster));
      }
    }
  });  

  function startDecorate(){
    sureGoods.list = [];
    selectId='';
    $editDecorate.hide();
    $startDecorate.hide();
    $decorateCtrl.show();
    $('#good_logo').val('')
    $('#poster-title').val('')
    $('#start_time').val('')
    $('#end_time').val('')
    $('#datetimeStart').val('')
    $('#datetimeEnd').val('')
    $('[name="date"]').each(function () {
      $(this).prop('checked',false);
    });
    $('[name="selectGood"]').each(function () {
      $(this).prop('checked',false);  
    });
    $('#upload-pic').attr('src','')
    $('#upload-plus').show();
    $('.upload-again').hide();
    selectNumber = poster.list.length;
    selectGoods.list = [];
    poster.list.push({
      module_data:{
        img:''
      }
    })
    initialImg = '';
    $('#sureGoodTbody').html(template(sureGoodTpl, selectGoods));
    $('.sure-add-good').show();
    $('.sureGood-box').hide();
  }

  function editDecorate(id,i){
    selectNumber = i;
    selectId = id;
    selectPoster = poster.list[i];
    $('#good_logo').val(selectPoster.module_data.img)
    $('#poster-title').val(selectPoster.module_data.title)
    $('#start_time').val(selectPoster.module_data.start_day)
    $('#end_time').val(selectPoster.module_data.end_day)
    $('#datetimeStart').val(selectPoster.module_data.start_time)
    $('#datetimeEnd').val(selectPoster.module_data.end_time)
    selectGoods.list = selectPoster.module_data.list;
    sureGoods.list = selectPoster.module_data.list;
    $('#sureGoodTbody').html(template(sureGoodTpl, selectGoods));
    $editDecorate.hide();
    $startDecorate.hide();
    $decorateCtrl.show();
    $('[name="date"]').each(function () {
      $(this).prop('checked',false);
    });
    $('[name="selectGood"]').each(function () {
      $(this).prop('checked',false);  
    });
    $('[name="date"]').each(function () {
      for(var i=0;i<selectPoster.module_data.week.length;i++){
        if($(this).val()==selectPoster.module_data.week[i]){
          $(this).prop('checked',true);
        }
      }
    });
    $('#upload-pic').attr('src',selectPoster.module_data.img)
    $('#upload-plus').hide();
    $('.upload-again').show();
    initialImg = poster.list[selectNumber].module_data.img;
    $('.sure-add-good').hide();
    $('.sureGood-box').show();
  }

  function Return(){
    $editDecorate.show();
    $startDecorate.hide();
    $decorateCtrl.hide();
    if(initialImg){
      poster.list[selectNumber].module_data.img = initialImg
    }else{
      poster.list.splice(selectNumber,1)
    }
    if(poster.list.length>=3){
      $('#start-decorate').hide();
    }else{
      $('#start-decorate').show();
    }
    $('#poster-tbody').html(template(posterTpl, poster));
    $('#poster-tbody2').html(template(posterTpl, poster));
  }

  function deleteDecorate(id){
    $.post(__BASEURL__ + 'mshop/decorate_api/del_module', autoCsrf({
        id:id,
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
        getModule();
      });
  }

  function deleteLogo(){

  }

  function release(){
    $('#decorate-ctrl')
    .bootstrapValidator({
      fields: {
        good_logo: {
          validators: {
            notEmpty: {
              message: '图片不能为空'
            }
          }
        },
        poster_title: {
          validators: {
            notEmpty: {
              message: '请输入名称'
            }
          }
        }           
      }
    }) 
    var bootstrapValidator = $('#decorate-ctrl').data('bootstrapValidator');
      bootstrapValidator.validate();
      if (bootstrapValidator.isValid()) {
       var img = $('#good_logo').val(),
            startDay = $('#start_time').val(),
            endDay = $('#end_time').val(),
            title = $('#poster-title').val(),
            startTime = $('#datetimeStart').val(),
            endTime = $('#datetimeEnd').val();

          if(startDay==''||endDay==''||startTime==''||endTime==''){
            new Msg({
              type: 'danger',
              msg: '请选择时间'
            });
            return false;
          }

          //时间判断
          var start_day = (startDay.replace( /-/g, '/' ));
          var end_day = new Date( endDay.replace( /-/g, '/' ) );
          var t = parseInt( end_day.getTime() ) - (new Date( start_day ).getTime());

          if(t<=0){
            new Msg({
              type: "danger",
              msg: "结束日期不能小于开始日期"
            });
            return false;
          }  


          var week = [];

          $('[name="date"]:checked').each(function () {
            var val = $(this).val();
            week.push(val);
          });
          var good_ids = [];
          if(selectGoods.list.length==0){
            new Msg({
              type: 'danger',
              msg: '请选择关联商品'
            });
            return false;
          }
          for(var i=0;i<selectGoods.list.length;i++){
            good_ids.push(selectGoods.list[i].id)
          }
          var json = {
            img:img,
            start_day:startDay,
            end_day:endDay,
            week:week.toString(),
            start_time:startTime,
            end_time:endTime,
            title:title,
            good_ids:good_ids.toString(),
            list:selectGoods.list
          };
          $.post(__BASEURL__ + 'mshop/decorate_api/save_module_data', autoCsrf({
            shop_id:shop_id,
            id:selectId,
            module_id:1,
            json:JSON.stringify(json),
            is_save:1
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
            getModule();
            $('#decorate-ctrl').hide();
            if(poster.list.length>=3){
              $('#start-decorate').hide();
            }else{
              $('#start-decorate').show();
            }
          });
      }; 
   
  }

  function preview(){
    $previewModal.modal('show');
  }

  function changeGood(){
    $addGoodModal.modal('show');
    selectGoods = {
      list:[]
    }
    for(var i=0;i<sureGoods.list.length;i++){
      selectGoods.list.push(sureGoods.list[i]);
    }
    $('[name="selectGood"]').each(function () {
      $(this).prop('checked',false);
    });
    $('[name="selectGood"]').each(function () {
      for(var i=0;i<selectGoods.list.length;i++){
        if($(this).val()==selectGoods.list[i].id){
          $(this).prop('checked',true);
        }
      }
    });
    $('#select-good-tbody').html(template(selectGoodTpl, selectGoods));
  }

  function sureSelect(){
    $addGoodModal.modal('hide');
    sureGoods.list = selectGoods.list;
    $('#sureGoodTbody').html(template(sureGoodTpl, sureGoods));
    if(sureGoods.list.length==0){
      $('.sure-add-good').show();
      $('.sureGood-box').hide();
    }else{
      $('.sure-add-good').hide();
      $('.sureGood-box').show();
    }
  }

  function closeSelect(){
    $addGoodModal.modal('hide');
    selectGoods = {
      list:[]
    }
  }

  function changePhoneType(el){
    $(el).addClass('active');
    $(el).siblings().removeClass('active');
    if($('.phone-preview-box').hasClass('phone-x')){
      $('.phone-preview-box').removeClass('phone-x')
    }else{
      $('.phone-preview-box').addClass('phone-x')
    }
  }

  function delSelectGood(i,id){
    selectGoods.list.splice(i,1);
    $('[name="selectGood"]').each(function () {
      $(this).prop('checked',false);
    });
    $('[name="selectGood"]').each(function () {
      for(var i=0;i<selectGoods.list.length;i++){
        if($(this).val()==selectGoods.list[i].id){
          $(this).prop('checked',true);
        }
      }
    });
    $('#select-good-tbody').html(template(selectGoodTpl, selectGoods));
  }

  window.delSelectGood = delSelectGood;  
  window.changePhoneType = changePhoneType;
  window.delImg = delImg;
  window.sureSelect = sureSelect;
  window.changeGood = changeGood;
  window.preview = preview;
  window.release = release;
  window.deleteLogo = deleteLogo;
  window.deleteDecorate = deleteDecorate;
  window.Return = Return;
  window.editDecorate = editDecorate;
  window.startDecorate = startDecorate;
  window.closeSelect = closeSelect;
})