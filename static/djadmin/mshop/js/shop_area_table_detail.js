/**
 * shop_area_table—_detail.js
 * by liangya
 * date: 2018-03-30
 */

$(function () {
  var shop_id = $('#shop').val()
    table_id = GetQueryString('table_id'),
    $editTableModal = $('#editTableModal'),
    $delTableModal = $('#delTableModal'),
    $auditModal = $('#auditModal'),
    $editConfirm = $('#edit-confirm'),
    $delConfirm = $('#del-confirm'),
    $auditCancel = $('#audit-cancel'),
    $auditConfirm = $('#audit-confirm'),
    $tableShopArea = $('#table_shop_area'),
    $tableName = $('#table_name'),
    $tableNumber = $('#table_number'),
    $tableDetail = $('#table-detail'),
    $orderDetail = $('#order-detail'),
    shopAreaTpl = document.getElementById("shopAreaTpl").innerHTML,
    tableDetailTpl = document.getElementById("tableDetailTpl").innerHTML;

  getShopArea();
  getTableDetail();
  validatorTableForm();

  // 获取全部区域
  function getShopArea() {
    $.getJSON(__BASEURL__ + 'mshop/shop_area_api/get_all_area', {
      shop_id: shop_id
    }, function (data) {
      if (data.success) {
        var shopArea = {
          rows: data.data
        };

        if (data.data.length > 0) {
          $tableShopArea.html(template(shopAreaTpl, shopArea));
        }
      }
    });
  }

  // 获取桌位详情
  function getTableDetail() {
    $.getJSON(__BASEURL__ + 'mshop/shop_area_api/area_table_info', {
      id: table_id
    }, function (data) {
      if (data.success) {
        $tableDetail.html(template(tableDetailTpl, data.data));
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
    });
  }

  // 显示桌位弹窗
  function showTableModal() {
    $editTableModal.modal('show');
    $("#table-form").data('bootstrapValidator').destroy();
    $('#table-form').data('bootstrapValidator', null);
    validatorTableForm();
  }

  // 验证桌位表单
  function validatorTableForm() {
    $('#table-form')
      .bootstrapValidator({
        fields: {
          table_shop_area: {
            validators: {
              notEmpty: {
                message: '区域不能为空'
              }
            }
          },
          table_name: {
            validators: {
              notEmpty: {
                message: '桌位名称不能为空'
              },
              stringLength: {
                max: 30,
                message: '桌位名称不得超过30个字符'
              }
            }
          },
          table_number: {
            validators: {
              notEmpty: {
                message: '建议人数不能为空'
              }
            }
          }
        }
      })
      .on('success.form.bv', function (e) {
        // 阻止表单默认提交
        e.preventDefault();

        var table_id = $editConfirm.data('id'),
          tableShopArea = $tableShopArea.val(),
          tableName = $tableName.val(),
          tableNumber = $tableNumber.val(),
          post_url,
          post_data;

        // 提交数据
        post_data = {
          shop_area_id: tableShopArea,
          name: tableName,
          number: tableNumber
        }

        if (!table_id) {
          post_url = __BASEURL__ + 'mshop/shop_area_api/area_table_add';
        } else {
          post_data.id = table_id;
          post_url = __BASEURL__ + 'mshop/shop_area_api/area_table_edit';
        }

        $editConfirm.prop('disabled', true);

        $.post(post_url, autoCsrf(post_data), function (data) {
          if (data.success) {
            new Msg({
              type: 'success',
              msg: data.msg
            });

            getTableDetail();
          } else {
            new Msg({
              type: 'danger',
              msg: data.msg
            });
          }

          $editConfirm.prop('disabled', false);
          $editTableModal.modal('hide');
        });
      });
  }

  // 刷新二维码
  function refreshQrcode(id) {
    $.post(__BASEURL__ + 'mshop/shop_area_api/regen_qrcode', autoCsrf({
      id: id
    }), function (data) {
      if (data.success) {
        new Msg({
          type: 'success',
          msg: '刷新成功'
        });

        getTableDetail();
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
    });
  }

  // 下载二维码
  function downloadQrcode(id) {
    setTimeout(function(){
      getTableDetail();
    }, 200);

    window.open(__BASEURL__ + 'mshop/shop_area_api/download?id=' + id);
  }

  // 编辑桌位
  function editTable(id, shop_area_id, name, number) {
    $editTableModal.find('.modal-title').text('编辑桌位');
    $tableShopArea.val(shop_area_id);
    $tableName.val(name);
    $tableNumber.val(number);
    $editConfirm.data('id', id);
    showTableModal();
  }

  // 删除桌位
  function delTable(id) {
    $delConfirm.data('id', id);
    $delTableModal.modal('show');
  }

  // 确定删除桌位
  $delConfirm.on('click', function () {
    var id = $(this).data('id');

    $delConfirm.prop('disabled', true);

    $.post(__BASEURL__ + 'mshop/shop_area_api/area_table_del', autoCsrf({
      id: id
    }), function (data) {
      if (data.success) {
        new Msg({
          type: 'success',
          msg: '删除成功'
        });

        window.location.href = __BASEURL__ + 'mshop/shop_area/table/' + shop_id;
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }

      $delConfirm.prop('disabled', false);
      $delTableModal.modal('hide');
    });
  });

  // 审核桌位订单
  function auditOrder(tid, order_detail_id) {
    $auditModal.data('tid', tid).data('order_detail_id', order_detail_id).modal('show')
  }

  // 拒单
  $auditCancel.on('click', function() {
    var tid = $auditModal.data('tid'),
      order_detail_id = $auditModal.data('order_detail_id');
    
    $auditCancel.prop('disabled', true).text('提交中');

    $.post(__BASEURL__ + 'mshop/meal_order_api/refuse_order', autoCsrf({
      tid: tid,
      order_table_id: order_detail_id
    }), function(data) {
      $auditModal.modal('hide');
      $auditCancel.prop('disabled', false).text('拒单');

      if (data.success) {
        getTableDetail();

        new Msg({
          type: 'success',
          msg: data.msg
        })
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        })
      }
    })
  });

  // 拒单
  $auditConfirm.on('click', function() {
    var tid = $auditModal.data('tid'),
      order_detail_id = $auditModal.data('order_detail_id');
    
    $auditConfirm.prop('disabled', true).text('提交中');

    $.post(__BASEURL__ + 'mshop/meal_order_api/audit_order', autoCsrf({
      tid: tid,
      order_table_id: order_detail_id
    }), function(data) {
      $auditModal.modal('hide');
      $auditConfirm.prop('disabled', false).text('接单');

      if (data.success) {
        getTableDetail();
        
        new Msg({
          type: 'success',
          msg: data.msg
        })
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        })
      }
    })
  });

  window.editTable = editTable;
  window.delTable = delTable;
  window.refreshQrcode = refreshQrcode;
  window.downloadQrcode = downloadQrcode;
  window.auditOrder = auditOrder;
});
