/**
 * ydb_list.js
 * by lanran
 * date: 2018-1-26
 */
$(function () {
  var cur_page = 1,
    page_size = 10,
    $delYdbModal = $('#delYdbModal'),
    $delConfirm = $('#del-confirm'),
    ydbTpl = document.getElementById('ydbTpl').innerHTML;

  getYdbList(cur_page);

  // 获取云店宝列表
  function getYdbList(curr) {
    var title = $('#searchVal').val();
    $.getJSON(__BASEURL__ + 'wm_main_version_api/grid_data', {
      title:title,
      current_page: curr || 1,
      page_size: page_size
    }, function (data) {
      if (data.success) {
        var pages = Math.ceil(+data.data.total / page_size);
        console.info(data.data)

        $('#ydbTbody').html(template(ydbTpl, data.data));

        laypage({
          cont: 'ydbPage',
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
              getYdbList(obj.curr);
            }
          }
        });
      }
    });
  }

  //搜索
  $('#btn-search').click(function(){
    getYdbList(cur_page);
  })

  // 删除公告
  function delYdb(id) {
    $delYdbModal.modal('show').on('shown.bs.modal', function () {
      $delConfirm.data('id', id);
    });
  }

  // 确定删除公告
  $delConfirm.on('click', function () {
    var id = $(this).data('id');

    $delConfirm.prop('disabled', true);

    $.post(__BASEURL__ + 'wm_main_version_api/ids_del', autoCsrf({
      ids: id
    }), function (data) {
      if (data.success) {
        new Msg({
          type: 'success',
          msg: '删除成功'
        });
        getYdbList(cur_page);
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
      $delConfirm.prop('disabled', false);
      $delYdbModal.modal('hide');
    });
  });


  // 发布公告
  function releaseNotice(id, status) {
    $.post(
      __BASEURL__ + "wm_main_version_api/status_update",
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

          getYdbList(cur_page);
        } else {
          new Msg({
            type: 'danger',
            msg: data.msg
          });
        }
      }
    );
  }

  window.delYdb = delYdb;
  window.releaseNotice = releaseNotice;
});