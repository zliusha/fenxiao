/**
 * withdraw_list.js
 * by liangya
 * date: 2017-10-27
 */
$(function () {
  var $shop = $('#shop'),
    $status = $('#status'),
    $remitModal = $('#remitModal'),
    $btnRemited = $('#btn-remited'),
    withdrawTpl = document.getElementById('withdrawTpl').innerHTML;

  var cur_page = 1,
    page_size = 10,
    shop_id = $shop.val(),
    status = $status.val();

  getStatus();
  getWithdrawList();

  function getStatus() {
    status = GetQueryString('status') || '';
    $status.val(status);
  }

  // 获取提现申请列表
  function getWithdrawList(curr) {
    $.getJSON(__BASEURL__ + 'mshop/finance_api/withdraw_list', {
      current_page: curr || 1,
      page_size: page_size,
      shop_id: shop_id,
      status: status
    }, function (data) {
      if (data.success) {
        var pages = Math.ceil(+data.data.total / page_size);

        $('#withdrawTbody').html(template(withdrawTpl, data.data));

        laypage({
          cont: 'withdrawPage',
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
              getWithdrawList(obj.curr);
              cur_page = obj.curr;
            }
          }
        });
      }else{
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
    });
  }

  // 修改门店
  $shop.on('change', function(){
    shop_id = $shop.val();

    getWithdrawList(1);
  });

   // 修改状态
   $status.on('change', function(){
    status = $status.val();

    getWithdrawList(1);
  });

  // 修改打款状态
  function remit(status){
    var id = $remitModal.data('id');

    $.post(__BASEURL__+'mshop/finance_api/update_status', autoCsrf({
      id: id,
      status: status
    }), function(data){
      if(data.success){
        new Msg({
          type: 'success',
          msg: data.msg
        })

        getWithdrawList(cur_page);
      }else{
        new Msg({
          type: 'danger',
          msg: data.msg
        })
      }

      $remitModal.modal('hide');
    });
  }

  // 打开打款弹窗
  function openRemitModal(id) {
    $remitModal.data('id', id).modal('show');
  }

  // 确定打款
  $btnRemited.on('click', function(){
    remit('1');
  });

  window.openRemitModal = openRemitModal;
});
