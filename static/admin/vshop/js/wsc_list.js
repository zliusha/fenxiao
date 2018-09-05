/**
 * wsc_list.js
 * by lanran
 * date: 2018-1-26
 */
$(function () {
  var cur_page = 1,
    page_size = 10,
    $delWscModal = $('#delWscModal'),
    $delConfirm = $('#del-confirm'),
    wscTpl = document.getElementById('wscTpl').innerHTML;

  getWscList(cur_page);

  // 获取云店宝公告列表
  function getWscList(curr) {
    var title = $('#searchVal').val();
    var cate = $('#active_state option:selected').val();
    console.info(cate)
    $.getJSON(__BASEURL__ + 'wsc_main_article_api/grid_data', {
      cate:cate,
      title:title,
      current_page: curr || 1,
      page_size: page_size
    }, function (data) {
      if (data.success) {
        var pages = Math.ceil(+data.data.total / page_size);
        console.info(data.data)

        $('#wscTbody').html(template(wscTpl, data.data));

        laypage({
          cont: 'wscPage',
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
              getWscList(obj.curr);
            }
          }
        });
      }
    });
  }

  //选择标签
  function changeCate(){
    getWscList(cur_page);
  }

  //搜索
  $('#btn-search').click(function(){
    getWscList(cur_page);
  })

  // 删除公告
  function delWsc(id) {
    $delWscModal.modal('show').on('shown.bs.modal', function () {
      $delConfirm.data('id', id);
    });
  }

  // 确定删除公告
  $delConfirm.on('click', function () {
    var id = $(this).data('id');

    $delConfirm.prop('disabled', true);

    $.post(__BASEURL__ + 'wsc_main_article_api/ids_del', autoCsrf({
      ids: id
    }), function (data) {
      if (data.success) {
        new Msg({
          type: 'success',
          msg: '删除成功'
        });
        getWscList(cur_page);
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
      $delConfirm.prop('disabled', false);
      $delWscModal.modal('hide');
    });
  });


  // 发布公告
  function releaseNotice(id, status) {
    $.post(
      __BASEURL__ + "wsc_main_article_api/status_update",
      autoCsrf({
        id:id,
        status: status
      }),
      function (data) {
        if (data.success) {
          new Msg({
            type: 'success',
            msg: data.msg,
            delay: 1
          });

          getWscList(cur_page);
        } else {
          new Msg({
            type: 'danger',
            msg: data.msg
          });
        }
      }
    );
  }

  window.changeCate = changeCate;
  window.delWsc = delWsc;
  window.releaseNotice = releaseNotice;
});