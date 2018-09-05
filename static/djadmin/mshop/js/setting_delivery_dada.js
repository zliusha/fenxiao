/**
 * setting_delivery_dada
 * by liangya
 * date: 2017-10-27
 */
$(function () {
  var $status = $("#status"),
    $sourceId = $("#source_id"),
    $shopId = $("#shop_id"),
    $shopNo = $("#shop_no"),
    $shopForm = $("#shop-form"),
    $cityCode = $("#city_code"),
    $shopModalTitle = $('#shop-modal-title'),
    $sourceFormFroup = $("#source-form-group"),
    $shopFormGroup = $("#shop-form-group"),
    $editSource = $("#edit-source"),
    $confirmSource = $("#confirm-source"),
    $cancelSource = $("#cancel-source"),
    $confirmShop = $("#confirm-shop"),
    $sourceBtnGroup = $("#source-btn-group"),
    $editShopModal = $("#editShopModal"),
    shopTpl = document.getElementById("shopTpl").innerHTML,
    cityTpl = document.getElementById("cityTpl").innerHTML,
    dadaShopTpl = document.getElementById("dadaShopTpl").innerHTML;

  var cur_page = 1,
    page_size = 10;

  var info = null;

  getDeliveryMethod();
  getDadaInfo();
  getShopList();
  getCityList();
  getDadaShopList();
  validatorShopForm();

  // 获取配送方式
  function getDeliveryMethod() {
    $.get(__BASEURL__ + 'mshop/setting_api/shipping_method', function (data) {
      if (data.success) {
        var status = +data.data.shipping;

        if (status == 2) {
          $status.prop("checked", true);
          toggleStatus(true);
        } else {
          $status.prop("checked", false);
          toggleStatus(false);
        }
      } else {
        console.log(data.msg);
      }
    });
  }

  // 切换配送开启动态
  function toggleStatus(is_use) {
    if (!is_use) {
      $sourceFormFroup.hide();
      $shopFormGroup.hide();
    } else {
      $sourceFormFroup.show();
      $shopFormGroup.show();
      $status.prop("disabled", true);
    }
  }

  // 改变配送开启动态
  $status.on("change", function () {
    var status = $(this).prop("checked");
    var shipping = !status ? 0 : 2;

    toggleStatus(status);

    $.post(
      __BASEURL__ + "mshop/setting_api/update_shipping_method",
      autoCsrf({
        shipping: shipping
      }),
      function (data) {
        if (data.success) {
          new Msg({
            type: "success",
            msg: "修改成功",
            delay: 1
          });

        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });

          $status.prop("checked", !status);
        }
      }
    );
  });

  // 获取达达配置信息
  function getDadaInfo() {
    $.get(
      __BASEURL__ + "mshop/dada_api/info",
      function (data) {
        if (data.success) {
          info = data.data.info;

          if (!info) {
            return false;
          }

          $sourceId.val(info.source_id);
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  }

  // 获取微商城门店列表
  function getShopList() {
    $.get(
      __BASEURL__ + "mshop/shop_api/all_list",
      function (data) {
        if (data.success) {
          $shopId.html(template(shopTpl, data.data));
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  }

  // 获取达达门店列表
  function getDadaShopList(curr) {
    $.getJSON(
      __BASEURL__ + "mshop/dada_api/shop_list", {
        current_page: curr || 1,
        page_size: page_size
      },
      function (data) {
        if (data.success) {
          var pages = Math.ceil(+data.data.total / page_size);

          $("#dadaShopTbody").html(template(dadaShopTpl, data.data));

          laypage({
            cont: "dadaShopPage",
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
                getDadaShopList(obj.curr);
                cur_page = obj.curr;
              }
            }
          });
        }
      }
    );
  }

  // 获取达达区域列表
  function getCityList() {
    $.get(
      __BASEURL__ + "mshop/dada_api/city_all_list",
      function (data) {
        if (data.success) {
          $cityCode.html(template(cityTpl, data.data));
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  }

  // 验证门店表单
  function validatorShopForm() {
    $shopForm
      .bootstrapValidator({
        excluded: [":disabled"],
        fields: {
          shop_id: {
            validators: {
              notEmpty: {
                message: "请选择微商城门店"
              }
            }
          },
          shop_no: {
            validators: {
              notEmpty: {
                message: "达达门店编号不能为空"
              }
            }
          },
          city_code: {
            validators: {
              notEmpty: {
                message: "请选择订单配送区域"
              }
            }
          }
        }
      })
      .on("success.form.bv", function (e) {
        // 阻止表单默认提交
        e.preventDefault();

        var shop_id = $shopId.val(),
          shop_no = $shopNo.val(),
          city_code = $cityCode.val(),
          city_name = $cityCode.find("option:selected").text();

        $confirmShop.prop("disabled", true);

        $.post(
          __BASEURL__ + "mshop/dada_api/shop_edit",
          autoCsrf({
            shop_id: shop_id,
            shop_no: shop_no,
            city_code: city_code,
            city_name: city_name
          }),
          function (data) {
            if (data.success) {
              new Msg({
                type: "success",
                msg: "保存成功",
                delay: 1
              });

              getDadaShopList(1);
            } else {
              new Msg({
                type: "danger",
                msg: data.msg
              });
            }

            $confirmShop.prop("disabled", false);
            $editShopModal.modal("hide");
          }
        );
      });
  }

  // 添加、编辑门店
  function editShop(shop_id, shop_no, city_code) {
    $shopForm
      .data("bootstrapValidator")
      .destroy();
    $shopForm.data("bootstrapValidator", null);
    validatorShopForm();
    $editShopModal.modal("show");

    if (!shop_id) {
      $shopModalTitle.text("添加门店");
      $shopId.val('').prop('disabled', false);
      $shopNo.val('');
      $cityCode.val('');
    } else {
      $shopModalTitle.text("编辑门店");
      $shopId.val(shop_id).prop('disabled', true);
      $shopNo.val(shop_no);
      $cityCode.val(city_code);
    }
  }

  // 编辑商户编号
  $editSource.on("click", function () {
    $editSource.hide();
    $sourceBtnGroup.show();
    $sourceId.prop("disabled", false);
  });

  // 隐藏商户编号操作
  function hideEditBtnGroup() {
    $editSource.show();
    $sourceBtnGroup.hide();
    $sourceId.prop("disabled", true);
  }

  // 确定修改商户编号
  $confirmSource.on("click", function () {
    var source_id = $sourceId.val();

    $.post(
      __BASEURL__ + "mshop/dada_api/edit",
      autoCsrf({
        source_id: source_id
      }),
      function (data) {
        if (data.success) {
          new Msg({
            type: "success",
            msg: "修改成功",
            delay: 1
          });
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });

          $sourceId.val(info.source_id);
        }

        hideEditBtnGroup();
      }
    );
  });

  // 取消修改商户编号
  $cancelSource.on("click", function () {
    hideEditBtnGroup();
  });

  window.editShop = editShop;
});