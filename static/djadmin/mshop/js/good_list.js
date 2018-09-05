$(function() {
  var $shopId = $("#shop_id"),
    $cateId = $("#cate_id"),
    $status = $("#status"),
    $title = $("#title"),
    $btnSearch = $("#btn-search"),
    $btnAddGood = $("#btn-add-good"),
    $batchShelvesUp = $("#batchShelvesUp"),
    $batchShelvesDown = $("#batchShelvesDown"),
    $delGoodModal = $("#delGoodModal"),
    $delConfirm = $("#del-confirm"),
    $editInventoryModal = $("#editInventoryModal"),
    $saveInventory = $("#save-inventory"),
    $inventoryTable = $("#inventoryTable"),
    $inventoryForm = $("#inventory-form"),
    cateTpl = document.getElementById("cateTpl").innerHTML,
    goodTpl = document.getElementById("goodTpl").innerHTML,
    inventoryTableTpl = document.getElementById("inventoryTableTpl").innerHTML;

  var cur_page = 1,
    page_size = 10,
    shop_id = $shopId.val(),
    cate_id = $cateId.val(),
    status = $status.val(),
    title = $title.val();

  getCate();
  getGoodList();
  validatorInventoryForm();

  // 获取分类
  function getCate(cb) {
    $.getJSON(
      __BASEURL__ + "mshop/items_api/get_all_cate",
      {
        shop_id: shop_id
      },
      function(data) {
        if (data.success) {
          $cateId.html(template(cateTpl, { list: data.data }));
          cb && cb();
        }
      }
    );
  }

  // 获取商品列表
  function getGoodList(curr) {
    $.getJSON(
      __BASEURL__ + "mshop/items_api/goods_list",
      {
        current_page: curr || 1,
        page_size: page_size,
        shop_id: shop_id,
        cate_id: cate_id,
        status: status,
        title: title
      },
      function(data) {
        if (data.success) {
          var pages = Math.ceil(+data.data.total / page_size);

          for (var i = 0; i < data.data.rows.length; i++) {
            data.data.rows[i].sku_list.forEach(function(item) {
              if (item.use_stock_num < 0) {
                data.data.rows[i].is_num_hide = true;
                return;
              }
            });
          }

          $('[name="selectAll"]').prop("checked", false);
          $("#goodTbody").html(template(goodTpl, data.data));

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
            jump: function(obj, first) {
              if (!first) {
                cur_page = obj.curr;
                getGoodList(obj.curr);
              }
            }
          });
        }
      }
    );
  }

  // 修改门店
  $shopId.on("change", function() {
    shop_id = $shopId.val();

    //重置分类
    $cateId.val("");
    cate_id = "";

    getCate();
    getGoodList(1);
  });

  // 修改分类
  $cateId.on("change", function() {
    cate_id = $cateId.val();

    getGoodList(1);
  });

  // 修改状态
  $status.on("change", function() {
    status = $status.val();

    getGoodList(1);
  });

  // 搜索
  $btnSearch.on("click", function() {
    title = $title.val();

    getGoodList(1);
  });

  // 搜索
  $btnAddGood.on("click", function() {
    if (!shop_id) {
      new Msg({
        type: "danger",
        msg: "请先添加门店"
      });

      return false;
    }

    window.location.href = __BASEURL__ + "mshop/items/add/" + shop_id;
  });

  // 删除商品
  function delGood(id) {
    $delConfirm.data("id", id);
    $delGoodModal.modal("show");
  }

  // 确定删除商品
  $delConfirm.on("click", function() {
    var good_id = $delConfirm.data("id");

    $.post(
      __BASEURL__ + "mshop/items_api/goods_del",
      autoCsrf({
        goods_id: good_id
      }),
      function(data) {
        $delGoodModal.modal("hide");

        if (data.success) {
          new Msg({
            type: "success",
            msg: "删除成功"
          });

          getGoodList(cur_page);
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  });

  // 上架
  function shelvesUp(ids) {
    $.post(
      __BASEURL__ + "mshop/items_api/shelves_up",
      autoCsrf({
        goods_id: ids,
        shop_id: shop_id
      }),
      function(data) {
        $batchShelvesUp.prop("disabled", false);

        if (data.success) {
          new Msg({
            type: "success",
            msg: "上架成功"
          });

          getGoodList(cur_page);
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  }

  // 批量上架
  $batchShelvesUp.on("click", function() {
    var ids = getSelectedItem();

    if (!ids) {
      return false;
    }

    $batchShelvesUp.prop("disabled", true);

    shelvesUp(ids);
  });

  // 下架
  function shelvesDown(ids) {
    $.post(
      __BASEURL__ + "mshop/items_api/shelves_down",
      autoCsrf({
        goods_id: ids,
        shop_id: shop_id
      }),
      function(data) {
        $batchShelvesDown.prop("disabled", false);

        if (data.success) {
          new Msg({
            type: "success",
            msg: "下架成功"
          });

          getGoodList(cur_page);
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  }

  // 批量下架
  $batchShelvesDown.on("click", function() {
    var ids = getSelectedItem();

    if (!ids) {
      return false;
    }

    $batchShelvesDown.prop("disabled", true);

    shelvesDown(ids);
  });

  // 获取选中的商品id（return Array:ids）
  function getSelectedItem() {
    var ids = [];

    $('[name="selectItem"]:checked').each(function() {
      ids.push($(this).val());
    });

    if (ids.length < 1) {
      new Msg({
        type: "danger",
        msg: "请先选择商品"
      });

      return false;
    }

    return ids.join(",");
  }

  // 修改库存
  function editInventory(id, type, measure_type) {
    $inventoryForm.data("bootstrapValidator").destroy();
    $inventoryForm.data("bootstrapValidator", null);
    $inventoryTable.html('<p class="text-center">加载中...</p>');

    $.getJSON(
      __BASEURL__ + "mshop/items_api/stock_sku_list",
      {
        goods_id: id
      },
      function(data) {
        data.sku_type = type;
        $inventoryTable.html(template(inventoryTableTpl, data));
        if (measure_type == 2) {
          $(".use_stock_num").each(function() {
            $(this).attr("name", "use_stock_num2");
          });
        }

        validatorInventoryForm();
      }
    );

    $editInventoryModal.modal("show");
  }

  // 验证库存表单
  function validatorInventoryForm() {
    $("#inventory-form")
      .bootstrapValidator({
        fields: {
          use_stock_num: {
            validators: {
              notEmpty: {
                message: "可用库存不能为空"
              },
              regexp: {
                regexp: /^(0|[1-9][0-9]*|-[1-9][0-9]*)$/,
                message: "请输入整数"
              }
            }
          },
          use_stock_num2: {
            validators: {
              notEmpty: {
                message: "可用库存不能为空"
              }
            }
          }
        }
      })
      .on("success.form.bv", function(e) {
        // 阻止表单默认提交
        e.preventDefault();

        var $use_stock_num = $inventoryTable.find(".use_stock_num"),
          sku = [];

        $use_stock_num.each(function(i) {
          var $this = $(this),
            sku_id = $this.data("id"),
            use_stock_num = +$this.val();

          sku.push({
            sku_id: sku_id,
            use_stock_num: use_stock_num
          });
        });

        if (sku.length < 1) {
          $editInventoryModal.modal("hide");

          return false;
        }

        $saveInventory.prop("disabled", true);

        $.post(
          __BASEURL__ + "mshop/items_api/edit_sku_stock",
          autoCsrf({
            shop_id: shop_id,
            sku: JSON.stringify(sku)
          }),
          function(data) {
            $saveInventory.prop("disabled", false);

            if (data.success) {
              new Msg({
                type: "success",
                msg: "修改成功"
              });

              $editInventoryModal.modal("hide");
              getGoodList(cur_page);
            } else {
              new Msg({
                type: "danger",
                msg: data.msg
              });
            }
          }
        );
      });
  }

  window.delGood = delGood;
  window.shelvesUp = shelvesUp;
  window.shelvesDown = shelvesDown;
  window.editInventory = editInventory;
});
