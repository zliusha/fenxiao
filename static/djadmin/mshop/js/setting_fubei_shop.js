$(function () {
  var $shopId = $('#shop_id'),
    $shopName = $('#shop_name'),
    $fubeiId = $('#fubei_id'),
    $shopForm = $('#shop-form'),
    $editShopModal = $('#editShopModal'),
    $editConfirm = $('#edit-confirm'),
    fubeiTpl = document.getElementById('fubeiTpl').innerHTML,
    shopTpl = document.getElementById('shopTpl').innerHTML;

  validatorShopForm();
  getShopList();
  getFubeiList();

  // 重置银行通道表单
  function resetShopForm() {
    $editConfirm.prop('disabled', false);
    $shopForm.data('bootstrapValidator').destroy();
    $shopForm.data('bootstrapValidator', null);
    validatorShopForm();
  }

  // 验证银行通道表单
  function validatorShopForm() {
    $shopForm
      .bootstrapValidator({
        fields: {
          shop_id: {
            validators: {
              notEmpty: {
                message: '门店不能为空'
              }
            }
          },
          fubei_id: {
            validators: {
              notEmpty: {
                message: '银行通道不能为空'
              }
            }
          }
        }
      })
      .on('success.form.bv', function (e) {
        // 阻止表单默认提交
        e.preventDefault();

        var shop_id = $shopId.val(),
          fubei_id = $fubeiId.val();

        $editConfirm.prop('disabled', true);

        $.post(__BASEURL__ + 'mshop/fubei_config_api/shop_edit', autoCsrf({
          shop_id: shop_id,
          fubei_id: fubei_id
        }), function (data) {
          $editConfirm.prop('disabled', false);
          $editShopModal.modal('hide');

          if (data.success) {
            new Msg({
              type: 'success',
              msg: data.msg
            });
            
            getShopList();
          } else {
            new Msg({
              type: 'danger',
              msg: data.msg
            });
          }
        });
      });
  }

  // 获取银行通道
  function getFubeiList() {
    $.getJSON(__BASEURL__ + 'mshop/fubei_config_api/list', {
      current_page: 1,
      page_size: 10000,
    }, function (data) {
      if (data.success) {
        var dataInfo = data.data;

        $fubeiId.html(template(fubeiTpl, dataInfo));
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        })
      }
    });
  }

  // 获取门店列表
  function getShopList() {
    $.getJSON(__BASEURL__ + 'mshop/fubei_config_api/shop_list', function (data) {
      if (data.success) {
        var dataInfo = data.data;

        $('#shopTbody').html(template(shopTpl, dataInfo));
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        })
      }
    });
  }

  // 编辑门店
  function editShop(shop_id, shop_name, fubei_id) {
    resetShopForm();
    $shopId.val(shop_id);
    $shopName.text(shop_name);
    $fubeiId.val(fubei_id);
    $editShopModal.modal('show');
  }

  window.editShop = editShop;
});