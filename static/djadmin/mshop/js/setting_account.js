$(function () {
  var $accountWork = $("#account_work"),
    $accountWorkName = $('#account_work_name'),
    $accountShop = $("#account_shop"),
    $delConfirm = $('#del-confirm'),
    $editConfirm = $('#edit-confirm'),
    $delAccountModal = $('#delAccountModal'),
    $editAccountModal = $('#editAccountModal'),
    $accountForm = $('#account-form'),
    accountTpl = document.getElementById('accountTpl').innerHTML,
    accountWorkTpl= document.getElementById('accountWorkTpl').innerHTML,
    accountShopTpl = document.getElementById('accountShopTpl').innerHTML;

  var cur_page = 1,
    page_size = 10;
  
  getWorkUrl();
  getAccountList();
  validatorAccountForm();

  // 获取员工管理链接
  function getWorkUrl() {
    $.getJSON(__BASEURL__ + "/erp_api/get_login_url", {},
      function (data) {
        if (data.success) {
          var dataInfo = data.data;

          dataInfo && $("#work_url").attr('href', dataInfo.url);
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    )
  }

  // 获取子账户列表
  function getAccountList(curr) {
    $.getJSON(__BASEURL__ + 'mshop/account_api/list', {
      current_page: curr || 1,
      page_size: page_size,
    }, function (data) {
      if (data.success) {
        var dataInfo = data.data;
        var pages = Math.ceil(+data.data.total / page_size);

        $("#accountTbody").html(template(accountTpl, dataInfo));

        laypage({
          cont: 'accountPage',
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
              getAccountList(obj.curr);
              cur_page = obj.curr;
            }
          }
        });
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
    });
  }

  //获取员工列表
  function getWorkList() {
    $accountWork.html('<option value="">请选择</option>');
    
    $.getJSON(__BASEURL__ + "erp_api/get_nobind_users", function (data) {
        if (data.success) {
          var dataInfo = data.data;

          $accountWork.html(template(accountWorkTpl, {list: dataInfo}));
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  }

  // 获取门店列表
  function getShopList() {
    $accountShop.html('<option value="">请选择</option>');

    $.getJSON(__BASEURL__ + "mshop/shop_api/all_nobind_list", function (data) {
        if (data.success) {
          var dataInfo = data.data;

          $accountShop.html(template(accountShopTpl, dataInfo));
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  }

  // 添加子账号
  function addAccount() {
    getWorkList();
    getShopList();
    resetAccountForm();
    $editAccountModal.find('.modal-title').text('添加子账号');
    $editAccountModal.modal('show');
    $editConfirm.data('id', '').prop('diabled', false);
    $accountWork.val('').show();
    $accountWorkName.text('--').hide();
    $accountShop.val('');
  }

  // 编辑子账号
  function editAccount(id, username, shop_id) {
    getShopList();
    resetAccountForm();
    $editAccountModal.find('.modal-title').text('编辑子账号');
    $editAccountModal.modal('show');
    $editConfirm.data('id', id).prop('diabled', false);
    $accountWork.hide().val('');
    $accountWorkName.show().text(username);
    $accountShop.val('');
  }

  // 删除子账户
  function delAccount(id) {
    $delAccountModal.modal('show');
    $delConfirm.data('id', id).prop('diabled', false);
  }

  // 确定删除子账号
  $delConfirm.on('click', function () {
    var id = $(this).data('id');

    $delConfirm.prop('disabled', true);

    $.post(__BASEURL__ + 'mshop/account_api/del', autoCsrf({
      account_id: id
    }), function (data) {
      $delConfirm.prop('disabled', false);
      $delAccountModal.modal('hide');

      if (data.success) {
        new Msg({
          type: 'success',
          msg: '删除成功'
        });

        getAccountList(cur_page);
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
    });
  });

  // 重置子账户表单
  function resetAccountForm() {
    $accountForm.data('bootstrapValidator').destroy();
    $accountForm.data('bootstrapValidator', null);
    validatorAccountForm();
  }

  // 验证子账户表单
  function validatorAccountForm() {
    $accountForm
      .bootstrapValidator({
        fields: {
          account_work: {
            validators: {
              notEmpty: {
                message: '请选择管理员(员工)'
              }
            }
          },
          account_shop: {
            validators: {
              notEmpty: {
                message: '请选择管理门店'
              }
            }
          }
        }
      })
      .on('success.form.bv', function (e) {
        // 阻止表单默认提交
        e.preventDefault();

        var account_id = $editConfirm.data('id'),
          user_id = $accountWork.val(),
          shop_id = $accountShop.val(),
          post_data = {
            shop_id: shop_id
          };

        // 判断是添加或编辑
        if (!account_id) {
          post_data.user_id = user_id;
          post_url = __BASEURL__ + 'mshop/account_api/add';
        } else {
          post_url = __BASEURL__ + 'mshop/account_api/edit?account_id=' + account_id;
        }

        $editConfirm.prop('disabled', true);

        $.post(post_url, autoCsrf(post_data), function (data) {
          $editConfirm.prop('disabled', false);
          $editAccountModal.modal('hide');

          if (data.success) {
            new Msg({
              type: 'success',
              msg: data.msg
            });
            
            getAccountList(cur_page);
          } else {
            new Msg({
              type: 'danger',
              msg: data.msg
            });
          }
        });
      });
  }

  window.addAccount = addAccount;
  window.editAccount = editAccount;
  window.delAccount = delAccount;
});