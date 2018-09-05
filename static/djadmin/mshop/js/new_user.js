$(function () {
  var $price = $("#price"),
    $save_btn = $("#save_btn"),
    $shop = $("#shop"),
    $minus_view = $(".minus_view"),
    $manySet = $("#manySet"),
    $editInventoryModal = $('#editInventoryModal'),
    $manyShowModal = $('#manyShowModal'),
    $manyConfirm = $('#many-confirm'),
    $saveInventory = $('#save-inventory'),
    shopTpl = document.getElementById('shopTpl').innerHTML;

  var cur_page = 1,
    page_size = 10,
    shop_id = "",
    is_newbie_coupon = 0,
    newbie_coupon = "",
    is_admin=$("#is_admin").val();

    if(Number(is_admin)==1){
      getShopList(cur_page);
    }else{
      getShopInfo();
    }

  shop_id = $shop.find('option:selected').val();



  // 修改门店
  $shop.on("change", function () {
    shop_id = $(this).val();
    getShopInfo();
  });

  // 获取门店列表
  function getShopList(curr) {
    $.getJSON(__BASEURL__ + 'mshop/shop_api/list', {
      current_page: curr || 1,
      page_size: page_size
    }, function (data) {
      if (data.success) {
        var pages = Math.ceil(+data.data.total / page_size);

        $('#shopTbody').html(template(shopTpl, data.data));

        for (var i = 0; i < data.data.rows.length; i++) {
          if (data.data.rows[i].status == 0) {
            $("#shopTbody").find('[value="' + data.data.rows[i].id + '"]').attr("checked", true);
          } else {
            $("#shopTbody").find('[value="' + data.data.rows[i].id + '"]').attr("checked", false);
          }
        }

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
            }
          }
        });
      }
    });
  }

  //改变开启还是关闭
  function turnOrOff(el) {
    if ($(el).is(":checked")) {
      $minus_view.show();

    } else {
      $minus_view.hide();
    }
  }

  function turnOrOffMany(el) {
    if ($(el).is(":checked")) {
    } else {
    }
  }

  function getShopInfo() {
    shop_id = $shop.find('option:selected').val();
    $.getJSON(__BASEURL__ + 'mshop/promotion_api/loadNewbieCoupon', {
      shop_id: shop_id
    }, function (data) {
      if (data.success) {
        var activeData = data.data;

        shop_id = activeData.shop_id;

        $("#shop option[value='" + shop_id + "']").attr("selected", "selected");

        $price.val(activeData.newbie_coupon);
        is_newbie_coupon = activeData.is_newbie_coupon;

        if (is_newbie_coupon == 1) {
          $('[value="开启活动"]').prop("checked", true);
          $minus_view.show();
        } else {
          $('[value="开启活动"]').prop("checked", false);
          $minus_view.hide();
        }
      }
    });
  }

  //修改状态
  function changeStatus(id, price, is_newbie_coupon, el) {
    var is_coupon = '';

    if ($(el).is(":checked")) {
      is_coupon = 1
    } else {
      is_coupon = 0;
    }

    var post_data = {
      shop_id: id,
      newbie_coupon: Number(price),
      is_newbie_coupon: is_coupon
    };

    $.post(__BASEURL__ + 'mshop/promotion_api/setNewbieCoupon', autoCsrf(post_data), function (data) {
      if (data.success) {
        new Msg({
          type: 'success',
          msg: '修改成功'
        });
        var cur_page = $('.laypage_curr').html();
        getShopList(cur_page);
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
    });
  }

  // 修改价格
  function editInventory(id, index, price, is_newbie_coupon) {
    $('#inventory-form').find(".priceInput").val(price);
    $editInventoryModal.modal('show');
    $saveInventory.attr("is_newbie_coupon", is_newbie_coupon);
    $saveInventory.attr("shop_id", id);
  }

  // 验证价格表单
  $saveInventory.click(function () {
    var is_coupon = $saveInventory.attr('is_newbie_coupon');
    var shop_id = $saveInventory.attr('shop_id');
    var priceNum = $(".priceInput").val();
    $('#inventory-form')
      .bootstrapValidator({
        fields: {
          use_stock_num: {
            validators: {
              notEmpty: {
                message: '价格不能为空'
              },
              regexp: {
                regexp: PregRule.Money,
                message: "只能输入数字"
              }
            }
          }
        }
      })
      .on('success.form.bv', function (e) {
        // 阻止表单默认提交
        e.preventDefault();

        post_data = {
          shop_id: shop_id,
          newbie_coupon: Number(priceNum),
          is_newbie_coupon: is_coupon
        };

        $saveInventory.prop('disabled', true);

        $.post(__BASEURL__ + 'mshop/promotion_api/setNewbieCoupon', autoCsrf(post_data), function (data) {
          if (data.success) {
            new Msg({
              type: 'success',
              msg: '修改成功'
            });
            $editInventoryModal.modal('hide');
            var cur_page = $('.laypage_curr').html();
            getShopList(cur_page);
          } else {
            new Msg({
              type: 'danger',
              msg: data.msg
            });
          }
          $saveInventory.prop('disabled', false);
        });
      });
  });

  //批量修改
  function manyClick() {
    var ids = getSelectedItem();

    if (!ids) {
      return false;
    }

    $manySet.prop('disabled', true);
    $manyShowModal.modal('show');

  }

  // 获取选中的商品id（return Array:ids）
  function getSelectedItem() {
    var ids = [];

    $('[name="selectItem"]:checked').each(function () {
      ids.push($(this).attr("data-id"));
    });

    if (ids.length < 1) {
      new Msg({
        type: 'danger',
        msg: '请先选择门店'
      });
      return false;
    }

    return ids.join(',');
  }

  //批量按钮
  $manyConfirm.click(function () {
    var ids = getSelectedItem();
    newbie_coupon = $("#manyPrice").val();

    if ($('#many-form').find('[value="开启活动"]').is(':checked')) {
      is_newbie_coupon = 1;
    } else {
      is_newbie_coupon = 0;
    }
    console.log(is_newbie_coupon)
    var re = /^(0|[1-9]\d*)(\.\d{1,4})?$/;

    if(is_newbie_coupon==1){
      if (!re.test(newbie_coupon)) {
        new Msg({
          type: "danger",
          msg: "价格只能输入数字"
        });
        return false;
      }
    }else{
      newbie_coupon=0;
    }


    post_data = {
      shop_id: ids,
      newbie_coupon: newbie_coupon,
      is_newbie_coupon: is_newbie_coupon
    };

    // 判断是添加或编辑
    post_url = __BASEURL__ + 'mshop/promotion_api/setNewbieCoupon';

    $manyConfirm.prop('disabled', true);

    $.post(post_url, autoCsrf(post_data), function (data) {
      if (data.success) {
        $manyShowModal.modal('hide');
        new Msg({
          type: "success",
          msg: data.msg,
          delay: 1,
          callback: function () {
            window.location.href = __BASEURL__ + "mshop/promotion/new_user";
          }
        });

      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }

      $manyConfirm.prop('disabled', false);
    });
  });

  // 提交门店信息
    $('#user_from')
      .bootstrapValidator({
        fields: {
          shop_id: {
            validators: {
              notEmpty: {
                message: '门店不能为空'
              }
            }
          },
          price: {
            validators: {
              notEmpty: {
                message: '下单立减金额不能为空'
              }
            }
          }
        }
      })
      .on('success.form.bv', function (e) {
        // 阻止表单默认提交
        e.preventDefault();

        newbie_coupon = $price.val();

        if ($('[value="开启活动"]').is(':checked')) {
          is_newbie_coupon = 1;
        } else {
          is_newbie_coupon = 0;
        }

        if(is_newbie_coupon==1){
          //输入不能小于0
          if (newbie_coupon <= 0) {
            new Msg({
              type: 'danger',
              msg: "立减金额不能小于等于0"
            });
            return false;
          }
        }else{
          newbie_coupon=0;
        }


        shop_id = $shop.find('option:selected').val();

        post_data = {
          shop_id: shop_id,
          newbie_coupon: newbie_coupon,
          is_newbie_coupon: is_newbie_coupon
        };

        // 判断是添加或编辑
        post_url = __BASEURL__ + 'mshop/promotion_api/setNewbieCoupon';

        $save_btn.prop('disabled', true);

        $.post(post_url, autoCsrf(post_data), function (data) {
          if (data.success) {
            new Msg({
              type: "success",
              msg: data.msg,
              delay: 1,
              callback: function () {
                window.location.href = __BASEURL__ + "mshop/promotion/new_user";
              }
            });
          } else {
            new Msg({
              type: 'danger',
              msg: data.msg
            });
          }
          $save_btn.prop('disabled', false);
        });
      });



  window.turnOrOff = turnOrOff;
  window.editInventory = editInventory;
  window.changeStatus = changeStatus;
  window.manyClick = manyClick;
  window.turnOrOffMany=turnOrOffMany;
});