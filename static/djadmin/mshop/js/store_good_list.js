$(function () {
  var cur_page = 1,
    page_size = 10,
    $delGoodModal = $("#delGoodModal"),
    $delConfirm = $("#del-confirm"),
    $addGoodModal = $('#addGoodModal'),
    $addshopModal = $('#addshopModal'),
    $addSyncModal = $('#addSyncModal'),
    goodTpl = document.getElementById("goodTpl").innerHTML,
    classTpl = document.getElementById("classTpl").innerHTML,
    shopTpl = document.getElementById("shopTpl").innerHTML,
    goodModalTpl = document.getElementById('goodModalTpl').innerHTML,
    shopModalTpl = document.getElementById('shopModalTpl').innerHTML,
    syncModalTpl = document.getElementById('syncModalTpl').innerHTML,
    modalTpl = document.getElementById("modalTpl").innerHTML;

  var shop_id = '';

  var v = true,
    skuData = [],
    price = [],
    shoplist = {
      list: []
    },
    catelist = {
      list: []
    };

  var is_shopAll = true;

  var goodIds = '';
  var shopIds = '';

  var select_ids = [];


  $('body').on('click', '[name="selectAll"]', function () {
    if($(this).is(':checked')){
      var $selectedItem = $('[name="selectItem"]:checked');
      $.each($selectedItem, function (i) {
        if(select_ids.indexOf($(this).val()) < 0){
          select_ids.push($(this).val())
        }
      });
    }else{
      var $selectItem = $('[name="selectItem"]');
      $.each($selectItem, function (i) {
        var index = select_ids.indexOf($(this).val()) 
        if(index > -1){
          select_ids.splice(index, 1)
        }
      });
    }
  });


  $('body').on('click', '[name="selectItem"]', function () {
    if($(this).is(':checked')){
      if(select_ids.indexOf($(this).val()) < 0){
        select_ids.push($(this).val())
      }
    }else{
      var index = select_ids.indexOf($(this).val()) 
      if(index > -1){
        select_ids.splice(index, 1)
      }
    }
  });    

  // 获取商品列表
  function getGoodList(curr, title) {
    $.getJSON(
      __BASEURL__ + "mshop/store_goods_api/goods_list", {
        current_page: curr || 1,
        page_size: page_size,
        title: title
      },
      function (data) {
        if (data.success) {
          var pages = Math.ceil(+data.data.total / page_size),
            pageData = data.data;

          skuData = data.data.rows;



          $("#goodTbody").html(template(goodTpl, pageData));
          var goodIdArr = [];
          for (var i = 0; i < data.data.rows.length; i++) {
            goodIdArr.push(data.data.rows[i].id)
          }
          goodIds = goodIdArr.toString();
          laypage({
            cont: "goodPage",
            pages: pages,
            curr: curr || 1,
            skin: "#5aa2e7",
            first: 1,
            last: pages,
            skip: true,
            prev: "&lt",
            next: "&gt",
            jump: function (obj, first) {
              if (!first) {
                getGoodList(obj.curr);
              }
            }
          });
        }
      }
    );
  }

  //获取同步记录
  function getSyncList(curr) {
    $.getJSON(
      __BASEURL__ + "mshop/store_goods_api/get_sync_record_list", {
        current_page: curr || 1,
        page_size: page_size
      },
      function (data) {
        if (data.success) {
          var pages = Math.ceil(+data.data.total / page_size),
            pageData = data.data;
          $('#syncModal').html(template(syncModalTpl, pageData));

          laypage({
            cont: "syncModalPage",
            pages: pages,
            curr: curr || 1,
            skin: "#5aa2e7",
            first: 1,
            last: pages,
            skip: true,
            prev: "&lt",
            next: "&gt",
            jump: function (obj, first) {
              if (!first) {
                getSyncList(obj.curr);
              }
            }
          });
        }
      }
    )
  }


  // 获取同步商品列表
  function getGoodModal(curr, title) {
    $.getJSON(
      __BASEURL__ + "mshop/store_goods_api/goods_list", {
        current_page: curr || 1,
        page_size: page_size,
        title: title
      },
      function (data) {
        if (data.success) {
          var pages = Math.ceil(+data.data.total / page_size),
            pageData = data.data;

          skuData = data.data.rows;
          data.data.rows.forEach(function(good,i){
            if(select_ids.indexOf(good.id)>-1){
              data.data.rows[i].is_check = true
            }
          })

          $("#goodModal").html(template(goodModalTpl, data.data));

          var l = $('[name="selectItem"]').length,
            sl = $('[name="selectItem"]:checked').length;
          if(l !== 0) {
            if (l == sl) {
              $('[name="selectAll"]').prop('checked', true);
            } else {
              $('[name="selectAll"]').prop('checked', false);
            }
          }

          laypage({
            cont: "goodModalPage",
            pages: pages,
            curr: curr || 1,
            skin: "#5aa2e7",
            first: 1,
            last: pages,
            skip: true,
            prev: "&lt",
            next: "&gt",
            jump: function (obj, first) {
              if (!first) {
                getGoodModal(obj.curr);
              }
            }
          });
        }
      }
    );
  }



  // 删除商品
  function delGood(id) {
    $delGoodModal.modal("show").on("shown.bs.modal", function () {
      $delConfirm.data("id", id);
    });
    $('[name="delType"]').each(function(){
      var val =  $(this).val();
      if(val==0){
        $(this).prop('checked',true);
      }
    })
  }

  // 确定删除商品
  $delConfirm.on("click", function () {
    var good_id = $(this).data("id"),
      type = $("select option:selected").val(),
      title = $("#searchVal").val();
      var delType = $('[name="delType"]:checked').val();
    $.post(
      __BASEURL__ + "mshop/store_goods_api/goods_del",
      autoCsrf({
        goods_id: good_id,
        type:delType
      }),
      function (data) {
        if (data.success) {
          new Msg({
            type: "success",
            msg: "删除成功"
          });
          getGoodList(cur_page, title);
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
        $delGoodModal.modal("hide");
      }
    );
  });

  //获取门店列表
  function getShopList() {
    $.getJSON(__BASEURL__ + "mshop/shop_api/all_list", {}, function (data) {
      if (data.success) {
        shoplist.list = data.data.list;
        $("#shopTbody").html(template(shopTpl, shoplist));
        if(shoplist.list.length > 0){
          var shop_id = shoplist.list[0].id;
        }
        getGoodList(cur_page);
        var shopIdArr = [];
        for (var i = 0; i < shoplist.list.length; i++) {
          shopIdArr.push(shoplist.list[i].id)
        }
        shopIds = shopIdArr.toString();
      }
    });
  }

  getShopList();

  function getShopModalList() {
    $.getJSON(__BASEURL__ + "mshop/shop_api/all_list", {}, function (data) {
      if (data.success) {
        shoplist.list = data.data.list;
        $("#shopModal").html(template(shopModalTpl, shoplist));
        var selectedItem = $('[name="selectItem"]:checked');
        $.each(selectedItem, function (i) {
          $(this).prop('checked', false);
        });
      }
    });
  }

  //获取分类列表
  function getClassList() {
    $.getJSON(__BASEURL__ + "mshop/items_api/get_all_cate", {}, function (data) {
      if (data.success) {
        catelist.list = data.data;
        $("#classTbody").html(template(classTpl, catelist));

      }
    });
  }

  getClassList();


  //搜索
  function searchVal() {
    var cate_id = $("#select-class option:selected").val(),
      shop_id = $('#select-shop option:selected').val(),
      val = $("#searchVal").val();

    getGoodList(cur_page, val);
  }

  function searchModalVal() {
    var shop_id = $('#select-shop option:selected').val(),
      val = $("#searchModalVal").val();

    getGoodModal(cur_page, val);
  }

  function batchAddGood(obj) {
    var $this = $(obj),
      cate_id = $addGoodModal.data('id'),
      shop_id = $('#select-shop option:selected').val();

    goodIds = '';
    // 判断是否已选商品
    if (select_ids.length < 1) {
      new Msg({
        type: 'danger',
        msg: '请先选择商品'
      });

      return false;
    }

    goodIds = select_ids.toString();
    $addGoodModal.modal('hide');
    $addshopModal.modal('show');
    getShopModalList();
  }


  function batchAddShop(obj) {
    var $this = $(obj),
      cate_id = $addGoodModal.data('id'),
      shop_id = $('#select-shop option:selected').val(),
      selectedItem = $('[name="shopItem"]:checked'),
      ids = [];
    shopIds = '';
    // 判断是否已选商品
    if (selectedItem.length < 1) {
      new Msg({
        type: 'danger',
        msg: '请先选择分店'
      });

      return false;
    }

    $.each(selectedItem, function (i) {
      ids.push($(this).val());
    });
    shopIds = ids.toString();
    syncGood();
    // $addGoodModal.modal('hide');
    // $addshopModal.modal('show');

  }

  function addShopItem() {
    var selectedItem = $('[name="shopItem"]:checked');
    console.info(selectedItem.length)
    $('#shop-number').html(selectedItem.length);
  }



  $('#shopModal').on('click', '[name="shopAll"]', function () {
    var is_checked = $(this).is(':checked');
    $('[name="shopItem"]').each(function (i, e) {
      var $this = $(this),
        disabled = $this.prop('disabled');
      if (disabled) {
        $this.prop('checked', false);
      } else {
        $this.prop('checked', is_checked);
      }
    });
    $('#shop-number').html($('[name="shopItem"]:checked').length);
  });

  // 打开商品弹窗
  function openGoodModal() {
    $('#goodModal').html('<tr><td class="text-center" colspan="3">加载中...</td></tr>');
    $('[name="selectAll"]').prop('checked', false);
    $addGoodModal.modal('show');
    select_ids = [];
    getGoodModal(1);
  }

  //打开同步记录弹窗
  function openSyncModal() {
    $('#goodModal').html('<tr><td class="text-center" colspan="5">加载中...</td></tr>');
    $addSyncModal.modal('show');
    getSyncList(1);
  }

  function syncGood() {
    $('#sync-btn').prop("disabled", true);
    $.post(__BASEURL__ + 'mshop/store_goods_api/sync_goods', autoCsrf({
      goods_ids: goodIds,
      shop_ids: shopIds
    }), function (data) {
      if (data.success) {
        new Msg({
          type: 'success',
          msg: '同步成功'
        });
        $addshopModal.modal('hide');
        getGoodList(cur_page);
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
      select_ids = [];
      $('#sync-btn').prop("disabled", false);
    });
  }


  window.syncGood = syncGood;
  window.delGood = delGood;
  window.searchVal = searchVal;
  window.searchModalVal = searchModalVal;
  window.openGoodModal = openGoodModal;
  window.batchAddGood = batchAddGood;
  window.batchAddShop = batchAddShop;
  window.addShopItem = addShopItem;
  window.openSyncModal = openSyncModal;
});