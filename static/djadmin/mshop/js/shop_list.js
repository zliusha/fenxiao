/**
 * shop_list.js
 * by liangya
 * date: 2018-06-20
 */
$(function () {
  var $delShopModal = $('#delShopModal'),
    $delConfirm = $('#del-confirm'),
    $shopLimit = $('#shop_limit'),
    shopTpl = document.getElementById('shopTpl').innerHTML,
    mainShopTpl = document.getElementById('mainShopTpl').innerHTML;

  var cur_page = 1,
    page_size = 10;

  var shopLimit = +$shopLimit.val(),
  shopLength = 0,
  mainShopList = [];

  getShopList();
  getMainShopList();

  // 获取门店列表
  function getShopList(curr) {
    $.getJSON(__BASEURL__ + 'mshop/shop_api/list', {
      current_page: curr || 1,
      page_size: page_size
    }, function (data) {
      if (data.success) {
        var pages = Math.ceil(+data.data.total / page_size);

        shopLength = data.data.total;

        $('#shopTbody').html(template(shopTpl, data.data));

        laypage({
          cont: 'shopPage',
          pages: pages,
          curr: curr || 1,
          skin: '#5aa2e7',
          first: 1,
          last: pages,
          skip: true,
          prev: "&lt",
          next: "&gt",
          jump: function (obj, first) {
            if (!first) {
              getShopList(obj.curr);
              cur_page = obj.curr;
            }
          }
        });
      }
    });
  }

  // 获取主门店列表
  function getMainShopList() {
    $.getJSON(__BASEURL__ + 'mshop/shop_api/noref_list', {
      current_page: 1,
      page_size: 20
    }, function (data) {
      if (data.success) {
        mainShopList = data.data.list;
        $('#mainShopTbody').html(template(mainShopTpl, data.data));
      }
    });
  }

  // 添加门店
  function addShop() {
    if (shopLength < shopLimit) {
      if (mainShopList.length > 0) {
        $('#mainShopModal').modal('show');
      } else {
        window.location.href = __BASEURL__ + 'mshop/shop/add';
      }
    } else {
      $('#shopLimitModal').modal('show');
    }
  }

  // 删除门店
  function delShop(id) {
    $delConfirm.data('id', id);
    $delShopModal.modal('show');
  }

  // 确定删除门店
  $delConfirm.on('click', function () {
    var shop_id = $(this).data('id');

    $delConfirm.prop('disabled', true);

    $.post(__BASEURL__ + 'mshop/shop_api/del', autoCsrf({
      shop_id: shop_id
    }), function (data) {
      $delConfirm.prop('disabled', false);
      $delShopModal.modal('hide');

      if (data.success) {
        new Msg({
          type: 'success',
          msg: '删除成功'
        });

        getShopList(cur_page);
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
    });
  });

  window.addShop = addShop;
  window.delShop = delShop;
});
