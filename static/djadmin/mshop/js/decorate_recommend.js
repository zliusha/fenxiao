$(function(){

  var $decorateCtrl = $('#decorate-ctrl'),
    $editDecorate = $('#edit-decorate'),
    $addGoodModal = $('#addGoodModal'),
    $previewModal = $('#previewModal'),
    $btnConfirm = $('#btn-confirm'),
    $startDecorate = $('#start-decorate');

  var menuTpl = document.getElementById('menuTpl').innerHTML,
    addGoodTpl = document.getElementById('addGoodTpl').innerHTML,
    posterTpl = document.getElementById('posterTpl').innerHTML,
    selectGoodTpl = document.getElementById('selectGoodTpl').innerHTML,
    showSelectTpl = document.getElementById('showSelectTpl').innerHTML,
    sureGoodTpl = document.getElementById('sureGoodTpl').innerHTML;

  var shop_id = $('#shop_id').val();  

  var selectGoods = {
    list:[]
  };

  var sureGoods = {
    title:'店长推荐',
    list:[]
  } 

  var recommend_id = '';

  var poster = {
    list:[]
  }

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
          if(data.data.tj_goods_modules!=''){

            $editDecorate.show();
            $startDecorate.hide();
            $('#del-btn').attr('data-id',data.data.tj_goods_modules[0].id);
            sureGoods.list = data.data.tj_goods_modules[0].sys_data;
            sureGoods.title = data.data.tj_goods_modules[0].module_data.title;
            selectGoods.list =  data.data.tj_goods_modules[0].sys_data;
            recommend_id = data.data.tj_goods_modules[0].id;
            $('.recommend_title').html(data.data.tj_goods_modules[0].module_data.title);
            $('#recommend_title').val(data.data.tj_goods_modules[0].module_data.title);
          }else{
            $editDecorate.hide();
            $startDecorate.show();
          }
          $('#sureGoodTbody').html(template(sureGoodTpl, sureGoods));
          $('#sureGoodTbody2').html(template(sureGoodTpl, sureGoods));
          $('#select-good-tbody').html(template(selectGoodTpl, selectGoods));
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
        var pict_url = $(this).attr('data-img');
        if(v){
          selectGoods.list.push({
            id:id,
            title:title,
            price:price,
            pict_url:pict_url
          });
        }
      });
      $('#select-good-tbody').html(template(selectGoodTpl, selectGoods));
    }
  });  


  function startDecorate(){
    $editDecorate.hide();
    $startDecorate.hide();
    $decorateCtrl.show();
    $('.sure-add-good').show();
    $('.sureGood-box').hide();
    $('#recommend_title').val('');
    selectGoods.list = [];
    $('[name="selectGood"]').each(function () {
      $(this).prop('checked',false);
    });
  }

  function editDecorate(){
    $editDecorate.hide();
    $startDecorate.hide();
    $decorateCtrl.show();
    $('.sure-add-good').hide();
    $('.sureGood-box').show();
  }

  function changeTitle(el){
    var title = $(el).val();
    sureGoods.title = title;
    $('#show-select-good').html(template(showSelectTpl,sureGoods));
    $('#show-select-good2').html(template(showSelectTpl,sureGoods));
  }

  function release(){
    var good_ids = [];
    if(sureGoods.list.length==0){
      new Msg({
        type: 'danger',
        msg: '请选择关联商品'
      });
      return false;
    }
    for(var i=0;i<sureGoods.list.length;i++){
      good_ids.push(sureGoods.list[i].id)
    }
    var json = {
      title:sureGoods.title,
      good_ids:good_ids.toString(),
      list:sureGoods.list
    };
    $.post(__BASEURL__ + 'mshop/decorate_api/save_module_data', autoCsrf({
      shop_id:shop_id,
      id:recommend_id,
      module_id:2,
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
      $editDecorate.show();
      $decorateCtrl.hide();
      $startDecorate.hide();
      getModule();
    });
  }

  function changeGood(){
    selectGoods = {
      list:[]
    }
    for(var i=0;i<sureGoods.list.length;i++){
      selectGoods.list.push(sureGoods.list[i]);
    }
    $('[name="selectGood"]').each(function () {
      $(this).prop('checked',false);
    });
    $addGoodModal.modal('show');
    $('[name="selectGood"]').each(function () {
      for(var i=0;i<selectGoods.list.length;i++){
        if($(this).val()==selectGoods.list[i].id){
          $(this).prop('checked',true);
        }
      }
    });
    $('#select-good-tbody').html(template(selectGoodTpl, selectGoods));
  }

  function changeName(){
    $('#recommend_title').show();
    $('.recommend-title-show').hide();
  }

  function preview(){
    $previewModal.modal('show');
  }

  function sureSelect(){
    $addGoodModal.modal('hide');
    sureGoods.list = selectGoods.list;
    $('#sureGoodTbody').html(template(sureGoodTpl, sureGoods));
    $('#sureGoodTbody2').html(template(sureGoodTpl, sureGoods));
    $('#show-select-good').html(template(showSelectTpl,sureGoods));
    $('#show-select-good2').html(template(showSelectTpl,sureGoods));

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

  function deleteDecorate(id){
    var id = $('#del-btn').attr('data-id');
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

      recommend_id='';
      selectGoods = {
        list:[]
      };
      poster = {
        list:[]
      }
      sureGoods.list = [];
      sureGoods.title = '';
      getModule();
      $('#sureGoodTbody').html(template(sureGoodTpl, sureGoods));
      $('#sureGoodTbody2').html(template(sureGoodTpl, sureGoods));
      $('#show-select-good').html(template(showSelectTpl,sureGoods));
      $('#show-select-good2').html(template(showSelectTpl,sureGoods));

    });
  }

  window.deleteDecorate = deleteDecorate;
  window.delSelectGood = delSelectGood;
  window.changeTitle = changeTitle;
  window.sureSelect = sureSelect;
  window.closeSelect = closeSelect;
  window.changePhoneType = changePhoneType;
  window.changeName = changeName;
  window.changeGood = changeGood;
  window.preview = preview;
  window.release = release;
  window.editDecorate = editDecorate;
  window.startDecorate = startDecorate;
})