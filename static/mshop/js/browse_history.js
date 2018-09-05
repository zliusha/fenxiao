/**
 * browse_history.js
 * by liangya
 * date: 2017-10-24
 */
$(function () {
  var cur_page = 1,
    page_size = 20,
    is_loading = false,
    is_has_data = true,
    height = $(window).height(),
    $browseHistory = $("#browse-history"),
    $loadMore = $("#load-more"),
    $btnDelete = $('#btn-delete'),
    browseHistoryTpl = document.getElementById("browseHistoryTpl").innerHTML;

  getBrowseHistory();

  // 滚动监听
  $(window).on("scroll", function () {
    var scrolltop = $(window).scrollTop();
    var top = $loadMore.offset().top;

    if (scrolltop + height > top && is_has_data) {
      getBrowseHistory();
    }
  });

  // 获取浏览记录
  function getBrowseHistory() {
    if (is_loading) {
      return false;
    }

    is_loading = true;

    $.getJSON(
      __BASEURL__ + "api/history/", {
        current_page: cur_page,
        page_size: page_size
      },
      function (data) {
        if (data.success) {
          var l = data.data.rows.length;

          if (l < page_size) {
            $loadMore.text("没有更多了");
            is_has_data = false;
          } else {
            is_has_data = true;
          }

          if (!l && cur_page == 1) {
            $browseHistory.html('<div class="m-empty"><p>还没有浏览记录!</p><p><a class="u-btn u-btn-primary u-btn-sm" href="' + __BASEURL__ + '">去逛逛</a></p></div>');
            $loadMore.hide();
          }

          $browseHistory.append(template(browseHistoryTpl, data.data));
          cur_page++;
        } else {
          layer.open({
            content: data.msg,
            skin: "msg",
            time: 1
          });
        }
        is_loading = false;
      }
    );
  }

  // 删除浏览记录
  $btnDelete.on('click', function () {
    var is_can_delete = true;
    var ids = [];

    $('[name="select_item"]:checked').each(function (i, e) {
      ids.push($(e).val());
    });

    if (ids.length < 1 || !is_can_delete) {
      return false;
    }

    is_can_delete = false;

    $.post(__BASEURL__ + 'api/history/delete', autoCsrf({
      ids: ids.join(',')
    }), function (data) {
      if (data.success) {
        window.location.href = window.location.href;
      } else {
        layer.open({
          content: data.msg,
          skin: "msg",
          time: 1
        });
      }

      is_can_delete = true;
    })
  });
});