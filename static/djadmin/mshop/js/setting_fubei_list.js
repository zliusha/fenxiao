$(function () {
  var $name = $('#name'),
    $appId = $('#app_id'),
    $appSecret = $('#app_secret'),
    $storeId = $('#store_id'),
    $fubeiForm = $('#fubei-form'),
    $delFubeiModal = $('#delFubeiModal'),
    $editFubeiModal = $('#editFubeiModal'),
    $delConfirm = $('#del-confirm'),
    $editConfirm = $('#edit-confirm'),
    fubeiTpl = document.getElementById('fubeiTpl').innerHTML;

  var cur_page = 1,
    page_size = 10;

  validatorFubeiForm();
  getFubeiInfo();

  // 重置银行通道表单
  function resetFubeiForm() {
    $editConfirm.prop('disabled', false);
    $fubeiForm.data('bootstrapValidator').destroy();
    $fubeiForm.data('bootstrapValidator', null);
    validatorFubeiForm();
  }

  // 验证银行通道表单
  function validatorFubeiForm() {
    $fubeiForm
      .bootstrapValidator({
        fields: {
          name: {
            validators: {
              notEmpty: {
                message: '银行通道名称不能为空'
              }
            }
          },
          app_id: {
            validators: {
              notEmpty: {
                message: '商户平台ID不能为空'
              }
            }
          },
          app_secret: {
            validators: {
              notEmpty: {
                message: '商户平台secret不能为空'
              }
            }
          },
          store_id: {
            validators: {
              notEmpty: {
                message: '商户门店ID不能为空'
              }
            }
          }
        }
      })
      .on('success.form.bv', function (e) {
        // 阻止表单默认提交
        e.preventDefault();

        var id = $editConfirm.data('id'),
          name = $name.val(),
          app_id = $appId.val(),
          app_secret = $appSecret.val(),
          store_id = $storeId.val(),
          post_data = {
            name: name,
            app_id: app_id,
            app_secret: app_secret,
            store_id: store_id
          };

        // 判断是添加或修改
        if (!id) {
          post_url = __BASEURL__ + 'mshop/fubei_config_api/add';
        } else {
          post_data.id = id;
          post_url = __BASEURL__ + 'mshop/fubei_config_api/edit';
        }

        $editConfirm.prop('disabled', true);

        $.post(post_url, autoCsrf(post_data), function (data) {
          $editConfirm.prop('disabled', false);
          $editFubeiModal.modal('hide');

          if (data.success) {
            new Msg({
              type: 'success',
              msg: data.msg
            });
            
            getFubeiInfo(cur_page);
          } else {
            new Msg({
              type: 'danger',
              msg: data.msg
            });
          }
        });
      });
  }

  // 获取银行通道列表
  function getFubeiInfo(curr) {
    $.getJSON(__BASEURL__ + 'mshop/fubei_config_api/list', {
      current_page: curr || 1,
      page_size: page_size,
    }, function (data) {
      if (data.success) {
        var dataInfo = data.data;
        var pages = Math.ceil(+data.data.total / page_size);

        $("#fubeiTbody").html(template(fubeiTpl, dataInfo));

        laypage({
          cont: 'fubeiPage',
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
              getFubeiInfo(obj.curr)
              cur_page = obj.curr;
            }
          }
        })
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        })
      }
    });
  }

  // 添加银行通道
  function addFubei() {
    resetFubeiForm();
    $name.val('');
    $appId.val('');
    $appSecret.val('');
    $storeId.val('');
    $editConfirm.data('id', '');
    $editFubeiModal.find('.modal-title').text('添加银行通道');
    $editFubeiModal.modal('show');
  }

  // 修改银行通道
  function editFubei(id, name, app_id, app_secret, store_id) {
    resetFubeiForm();
    $name.val(name);
    $appId.val(app_id);
    $appSecret.val(app_secret);
    $storeId.val(store_id);
    $editConfirm.data('id', id);
    $editFubeiModal.find('.modal-title').text('修改银行通道');
    $editFubeiModal.modal('show');
  }

  // 删除银行通道
  function delFubei(id) {
    $delConfirm.data('id', id).prop('disabled', false);
    $delFubeiModal.modal('show');
  }

  // 确定删除银行通道
  $delConfirm.on('click', function () {
    var id = $(this).data('id');

    $delConfirm.prop('disabled', true);

    $.post(__BASEURL__ + 'mshop/fubei_config_api/delete', autoCsrf({
      id: id
    }), function (data) {
      if (data.success) {
        new Msg({
          type: 'success',
          msg: data.msg
        });

        getFubeiInfo(cur_page);
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }

      $delConfirm.prop('disabled', false);
      $delFubeiModal.modal('hide');
    });
  });

  window.addFubei = addFubei;
  window.editFubei = editFubei;
  window.delFubei = delFubei;
});