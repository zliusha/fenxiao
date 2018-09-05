/**
 * good_cate.js
 * by lanran
 * date: 2017-10-25
 */
$(function () {
  var $shop = $('#shop'),
    $editCateModal = $('#editCateModal'),
    $delCateModal = $('#delCateModal'),
    $editConfirm = $('#edit-confirm'),
    $addGoodModal = $('#addGoodModal'),
    $delConfirm = $('#del-confirm'),
    $cateName = $('#cate_name'),
    $cateSort = $('#cate_sort'),
    $title = $('#title'),
    $btnSearch = $('#btn-search'),
    $selectAll = $('[name="selectAll"]'),
    $goodTbody = $('#goodTbody'),
    cateTpl = document.getElementById("cateTpl").innerHTML,
    goodTpl = document.getElementById('goodTpl').innerHTML;

  var cur_page = 1,
    good_cur_page = 1,
    page_size = 10,
    title = $title.val(),
    select_ids = [],
    shop_id = $shop.val(), // 当前店铺id
    cate_id = ''; // 当前分类id

  getCateList();
  validatorCateForm();

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

  // 获取分类列表
  function getCateList(curr) {
    $.getJSON(__BASEURL__ + 'mshop/items_api/cate_list', {
      shop_id: shop_id,
      current_page: curr || 1,
      page_size: page_size
    }, function (data) {
      if (data.success) {
        var pages = Math.ceil(+data.data.total / page_size);

        $('#cateTbody').html(template(cateTpl, data.data));

        laypage({
          cont: 'catePage',
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
              getCateList(obj.curr);
              cur_page = obj.curr;
            }
          }
        });
      }
    });
  }

  // 获取商品列表
  function getGoodList(curr) {
    var get_url,
      get_data = {
        current_page: curr || 1,
        page_size: page_size,
        title: title
      };
    
    // 判断是总账户还是子门店
    if (shop_id !== '0') {
      get_url = __BASEURL__ + 'mshop/items_api/goods_list';
      get_data.shop_id = shop_id;
    } else {
      get_url = __BASEURL__ + 'mshop/store_goods_api/goods_list';
    }

    $.getJSON(get_url, get_data, function (data) {
      if (data.success) {
        var pages = Math.ceil(+data.data.total / page_size);

        data.data.rows.forEach(function(good,i){
          if(select_ids.indexOf(good.id)>-1){
            data.data.rows[i].is_check = true
          }
        })
        $goodTbody.html(template(goodTpl, {
          rows: data.data.rows,
          cate_id: cate_id
        }));

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
          cont: 'goodPage',
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

              getGoodList(obj.curr);
              good_cur_page = obj.curr;
            }
          }
        });
      }
    });
  }

  // 打开商品弹窗
  function openGoodModal(id) {
    // 改变全局分类id
    cate_id = id;
    $goodTbody.html('<tr><td class="text-center" colspan="3">加载中...</td></tr>');
    $selectAll.prop('checked', false);
    $addGoodModal.modal('show');
    select_ids = [];
    getGoodList(1);
  }

  // 添加商品
  function addGood(obj, ids) {
    var $this = $(obj),
      post_url,
      post_data = {
        cate_id: cate_id,
        goods_ids: ids
      };

    // 判断是总账户还是子门店
    if (shop_id !== '0') {
      post_url = __BASEURL__ + 'mshop/items_api/cate_goods_add';
      post_data.shop_id = shop_id;
    } else {
      post_url = __BASEURL__ + 'mshop/store_goods_api/cate_goods_add';
    }

    $this.prop('disabled', true);

    $.post(post_url, autoCsrf(post_data), function (data) {
      if (data.success) {
        new Msg({
          type: 'success',
          msg: '添加成功'
        });

        $selectAll.prop('checked', false);
        select_ids = [];
        getGoodList(good_cur_page);
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }

      $this.prop('disabled', false);
    });
  }

  // 批量添加商品
  function batchAddGood(obj) {

    // 判断是否已选商品
    if (select_ids.length < 1) {
      new Msg({
        type: 'danger',
        msg: '请先选择商品'
      });

      return false;
    }

    addGood(obj, select_ids.join(','));
  }

  // 移除商品
  function delGood(obj, id) {
    var $this = $(obj),
      post_data = {
        cate_id: cate_id,
        goods_id: id
      },
      post_url;
    
    // 判断是总账户还是子门店
    if (shop_id !== '0') {
      post_url = __BASEURL__ + 'mshop/items_api/cate_goods_remove';
      post_data.shop_id = shop_id;
    } else {
      post_url = __BASEURL__ + 'mshop/store_goods_api/cate_goods_remove';
    }

    $this.prop('disabled', true);

    $.post(post_url, autoCsrf(post_data), function (data) {
      if (data.success) {
        new Msg({
          type: 'success',
          msg: '移除成功'
        });

        getGoodList(good_cur_page);
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });

      }
      $this.prop('disabled', false);
    });
  }

  // 显示分类弹窗
  function showCateModal() {
    $editCateModal.modal('show');
    $("#cate-form").data('bootstrapValidator').destroy();
    $('#cate-form').data('bootstrapValidator', null);
    validatorCateForm();
  }

  // 添加分类
  function addCate() {
    showCateModal();
    $editCateModal.find('.modal-title').text('添加分类');
    $cateName.val('');
    $cateSort.val('');
    $editConfirm.data('id', '');
  }

  // 编辑分类
  function editCate(id, cateName, cateSort) {
    showCateModal();
    $editCateModal.find('.modal-title').text('编辑分类');
    $cateName.val(cateName);
    $cateSort.val(cateSort);
    $editConfirm.data('id', id);
  }

  // 删除分类
  function delCate(id) {
    $delCateModal.modal('show');
    $delConfirm.data('id', id);
  }

  // 验证分类表单
  function validatorCateForm() {
    $('#cate-form')
      .bootstrapValidator({
        fields: {
          cate_name: {
            validators: {
              notEmpty: {
                message: '分类名称不能为空'
              },
              stringLength: {
                max: 8,
                message: '分类名称不得超过8个字符'
              }
            }
          },
          cate_sort: {
            validators: {
              notEmpty: {
                message: '分类排序不能为空'
              }
            }
          }
        }
      })
      .on('success.form.bv', function (e) {
        // 阻止表单默认提交
        e.preventDefault();

        var cate_name = $cateName.val(),
          cate_sort = $cateSort.val(),
          cate_id = $editConfirm.data('id'),
          post_url,
          post_data;

        // 提交数据
        post_data = {
          shop_id: shop_id,
          cate_name: cate_name,
          sort: cate_sort
        }

        // 判断是添加或编辑
        if (!cate_id) {
          post_url = __BASEURL__ + 'mshop/items_api/cate_add';
        } else {
          post_data.id = cate_id;
          post_url = __BASEURL__ + 'mshop/items_api/cate_edit';
        }

        $editConfirm.prop('disabled', true);

        $.post(post_url, autoCsrf(post_data), function (data) {
          if (data.success) {
            new Msg({
              type: 'success',
              msg: data.msg
            });

            getCateList(cur_page);
          } else {
            new Msg({
              type: 'danger',
              msg: data.msg
            });
          }

          $editConfirm.prop('disabled', false);
          $editCateModal.modal('hide');
        });
      });
  }

  // 确定删除分类
  $delConfirm.on('click', function () {
    var id = $(this).data('id');

    $delConfirm.prop('disabled', true);

    $.post(__BASEURL__ + 'mshop/items_api/cate_del', autoCsrf({
      id: id
    }), function (data) {
      if (data.success) {
        new Msg({
          type: 'success',
          msg: '删除成功'
        });

        getCateList(1);
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }

      $delConfirm.prop('disabled', false);
      $delCateModal.modal('hide');
    });
  });

  // 修改门店
  $shop.on('change', function () {
    shop_id = $(this).val();

    getCateList(1);
  });

  // 搜索商品
  $btnSearch.on('click', function () {
    title = $title.val();

    getGoodList(1);
  });

  window.addCate = addCate;
  window.editCate = editCate;
  window.delCate = delCate;
  window.openGoodModal = openGoodModal;
  window.addGood = addGood;
  window.batchAddGood = batchAddGood;
  window.delGood = delGood;
});