/**
 * shop_cate.js
 * by lanran
 * date: 2018-03-30
 */
$(function () {
  var $shop = $('#shop'),
    $name = $('#name'),
    $editAreaModal = $('#editAreaModal'),
    $delAreaModal = $('#delAreaModal'),
    $editConfirm = $('#edit-confirm'),
    $delConfirm = $('#del-confirm'),
    $areaTbody = $('#areaTbody'),
    areaTpl = document.getElementById("areaTpl").innerHTML;

  var cur_page = 1,
    page_size = 10,
    shop_id = $shop.val();

  getAreaList();
  validatorAreaForm();

  // 获取区域列表
  function getAreaList(curr) {
    $.getJSON(__BASEURL__ + 'mshop/shop_area_api/area_list', {
      shop_id: shop_id,
      current_page: curr || 1,
      page_size: page_size
    }, function (data) {
      if (data.success) {
        var pages = Math.ceil(+data.data.total / page_size);

        $('#areaTbody').html(template(areaTpl, data.data));

        laypage({
          cont: 'areaPage',
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
              getAreaList(obj.curr);
              cur_page = obj.curr;
            }
          }
        });
      }
    });
  }

  // 验证区域表单
  function validatorAreaForm() {
    $('#area-form')
      .bootstrapValidator({
        fields: {
          name: {
            validators: {
              notEmpty: {
                message: '区域名称不能为空'
              },
              stringLength: {
                max: 30,
                message: '区域名称不得超过30个字符'
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
          post_url,
          post_data;

        // 提交数据
        post_data = {
          name: name,
          shop_id: shop_id
        }

        if (!id) {
          post_url = __BASEURL__ + 'mshop/shop_area_api/area_add';
        } else {
          post_data.id = id;
          post_url = __BASEURL__ + 'mshop/shop_area_api/area_edit';
        }

        $editConfirm.prop('disabled', true);

        $.post(post_url, autoCsrf(post_data), function (data) {
          if (data.success) {
            new Msg({
              type: 'success',
              msg: data.msg
            });

            getAreaList(1);
          } else {
            new Msg({
              type: 'danger',
              msg: data.msg
            });
          }

          $editConfirm.prop('disabled', false);
          $editAreaModal.modal('hide');
        });
      });
  }

  // 显示区域弹窗
  function showAreaModal() {
    $editAreaModal.modal('show');
    $("#area-form").data('bootstrapValidator').destroy();
    $('#area-form').data('bootstrapValidator', null);
    validatorAreaForm();
  }

  // 添加区域
  function addArea() {
    showAreaModal();
    $editAreaModal.find('.modal-title').text('添加区域');
    $name.val('');
    $editConfirm.data('id', '');
  }

  // 编辑区域
  function editArea(id, name) {
    showAreaModal();
    $editAreaModal.find('.modal-title').text('编辑区域');
    $name.val(name);
    $editConfirm.data('id', id);
  }

  // 删除区域
  function delArea(id) {
    $delAreaModal.modal('show');
    $delConfirm.data('id', id);
  }

  // 确定删除区域
  $delConfirm.on('click', function () {
    var id = $(this).data('id');

    $delConfirm.prop('disabled', true);

    $.post(__BASEURL__ + 'mshop/shop_area_api/area_del', autoCsrf({
      id: id
    }), function (data) {
      if (data.success) {
        new Msg({
          type: 'success',
          msg: '删除成功'
        });

        getAreaList(1);
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }

      $delConfirm.prop('disabled', false);
      $delAreaModal.modal('hide');
    });
  });

  window.addArea = addArea;
  window.editArea = editArea;
  window.delArea = delArea;
});