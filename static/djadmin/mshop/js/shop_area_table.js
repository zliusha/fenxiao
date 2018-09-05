/**
 * shop_area_table.js
 * by liangya
 * date: 2018-03-30
 */

$(function () {
  var $shop = $('#shop'),
    $shopArea = $('#shop_area'),
    $title = $('#title'),
    $btnSearch = $('#btn-search'),
    $editTableModal = $('#editTableModal'),
    $editConfirm = $('#edit-confirm'),
    $tableShopArea = $('#table_shop_area'),
    $tableName = $('#table_name'),
    $tableNumber = $('#table_number'),
    shopAreaTpl = document.getElementById("shopAreaTpl").innerHTML,
    tableTpl = document.getElementById("tableTpl").innerHTML;

  // 搜素字段
  var shop_id = $shop.val(),
    shop_area_id = $shopArea.val(),
    title = $title.val();

  getShopArea();
  getTableList();
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
          $shopArea.html(template(shopAreaTpl, shopArea));
          $tableShopArea.html(template(shopAreaTpl, shopArea));
        }
      }
    });
  }

  // 获取桌位列表
  function getTableList() {
    $.getJSON(__BASEURL__ + 'mshop/shop_area_api/area_table_list', {
      shop_id: shop_id,
      shop_area_id: shop_area_id,
      title: title
    }, function (data) {
      if (data.success) {
        var tables = {
          rows: data.data
        };

        $('#tableCon').html(template(tableTpl, tables));
      }
    });
  }

  // 查看桌位详情
  function goTableDetail(table_id) {
    window.location.href = __BASEURL__ + 'mshop/shop_area/table_detail/' + shop_id + '?table_id=' + table_id;
  }

  // 显示桌位弹窗
  function showTableModal() {
    $editTableModal.modal('show');
    $("#table-form").data('bootstrapValidator').destroy();
    $('#table-form').data('bootstrapValidator', null);
    validatorTableForm();
  }

  // 添加桌位
  function addTable() {
    $editTableModal.find('.modal-title').text('添加桌位');
    $tableShopArea.val('');
    $tableName.val('');
    $tableNumber.val('');
    $editConfirm.data('id', '');
    showTableModal();
  }

  // 下载全部二维码
  function downloadQrcodePkg() {
    window.open(__BASEURL__ + 'mshop/shop_area_api/zip_download?shop_id=' + shop_id);
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

            getTableList();
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

  // 修改区域
  $shopArea.on('change', function () {
    shop_area_id = $(this).val();

    getTableList();
  });

  // 搜索桌位
  $btnSearch.on('click', function () {
    title = $title.val();

    getTableList();
  });

  window.addTable = addTable;
  window.downloadQrcodePkg = downloadQrcodePkg;
  window.goTableDetail = goTableDetail;
});