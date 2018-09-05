/**
 * comment_list.js
 * by lanran
 * date: 2017-11-23
 */
$(function () {
  var $createTime = $("#create_time"),
    $editConfirm = $('#edit-confirm'),
    $editReplyModal = $('#editReplyModal'),
    commentTpl = document.getElementById("commentTpl").innerHTML;

  var start = moment().subtract(29, "days"),
    end = moment();

  var cur_page = 1,
    page_size = 10,
    shop_id = 0,
    reply_type = '',
    score = '',
    is_have = '',
    create_time = start.format("YYYY-MM-DD") + " - " + end.format("YYYY-MM-DD");

  getReplyType();
  initDateRange();
  getCommentList();

  // 获取URL参数回复类型
  function getReplyType() {
    reply_type = GetQueryString('reply_type');
    $('[name="commentTpye"][value="' + reply_type + '"]').prop('checked', true);
  }

  // 初始化时间范围
  function initDateRange() {
    function cb(s, e) {
      create_time = s.format("YYYY-MM-DD") + " - " + e.format("YYYY-MM-DD");

      $createTime.val(create_time);

      getCommentList(1);
    }
    $createTime.daterangepicker({
      startDate: start,
      endDate: end,
      maxDate: end,
      applyClass: "btn-primary",
      cancelClass: "btn-default",
      locale: {
        applyLabel: "确认",
        cancelLabel: "取消",
        fromLabel: "起始时间",
        toLabel: "结束时间",
        customRangeLabel: "自定义",
        daysOfWeek: ["日", "一", "二", "三", "四", "五", "六"],
        monthNames: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
        firstDay: 1,
        format: "YYYY-MM-DD"
      },
      ranges: {
        今日: [moment(), moment()],
        昨日: [moment().subtract(1, "days"), moment().subtract(1, "days")],
        最近7日: [moment().subtract(6, "days"), moment()],
        最近30日: [moment().subtract(29, "days"), moment()]
      }
    }, cb);
  }






  // 获取评价列表
  function getCommentList(curr) {
    shop_id = $('#select-shop').val();
    if (!shop_id) {
      shop_id = 0;
    }
    $.getJSON(
      __BASEURL__ + "mshop/comment_api/comment_list", {
        shop_id: shop_id,
        current_page: curr || 1,
        page_size: page_size,
        time: create_time,
        reply_type: reply_type,
        score: score,
        is_have: is_have
      },
      function (data) {
        if (data.success) {
          var pages = Math.ceil(+data.data.total / page_size);
          var comment = {
            rows: []
          }
          comment.rows = data.data.rows;
          for (var i = 0; i < data.data.rows.length; i++) {
            if(data.data.rows[i].comments[0].tags){
              comment.rows[i].comments[0].tags = data.data.rows[i].comments[0].tags.split(",");
            } else {
              comment.rows[i].comments[0].tags = [];
            }
            if (data.data.rows[i].comments[0].picarr) {
              comment.rows[i].comments[0].picarr = data.data.rows[i].comments[0].picarr.split(",");
              for (var k = 0; k < comment.rows[i].comments[0].picarr.length; k++) {
                if (comment.rows[i].comments[0].picarr[k]) {
                  if (comment.rows[i].comments[0].picarr[k].indexOf('http') > -1) {
                    comment.rows[i].comments[0].picarr[k] = comment.rows[i].comments[0].picarr[k]
                  } else {
                    comment.rows[i].comments[0].picarr[k] = __UPLOADURL__ + comment.rows[i].comments[0].picarr[k]
                  }
                } else {
                  comment.rows[i].comments[0].picarr[k] = []
                }
              }
            } else {
              comment.rows[i].comments[0].picarr = [];
            }
          }
          console.info(comment)
          $("#commentTbody").html(
            template(commentTpl, comment)
          );
          laypage({
            cont: "commentPage",
            pages: pages,
            curr: curr || 1,
            skin: "#5aa2e7",
            first: 1,
            last: pages,
            skip: true,
            prev: "&lt",
            next: "&gt",
            jump: function (obj, first) {
              if (!first) {
                getCommentList(obj.curr);
                cur_page = obj.curr;
              }
            }
          });
        }
      }
    );
  }


  function changeShop() {
    shop_id = $('#select-shop').val();
    getCommentList(1);
  }

  $('body').on('click', '[name="commentTpye"]', function () {
    var type = $(this).val();
    $(this).prop('checked', true);
    if (type == 0) {
      reply_type = '';
    } else if (type == 1) {
      reply_type = 1;
    }
    getCommentList(1);
  });

  $('body').on('click', '[name="hasContent"]', function () {
    if ($(this).is(':checked')) {
      is_have = 1;
    } else {
      is_have = '';
    }
    getCommentList(1);
  });

  function reply(id) {
    showReplyModal();
    $('#reply_name').val('');
    $editConfirm.data('id', id);
  }

  // 显示回复弹窗
  function showReplyModal() {
    $editReplyModal.modal('show');
    $("#reply-form").data('bootstrapValidator').destroy();
    $('#reply-form').data('bootstrapValidator', null);
    validatorReplyForm();
  }

  validatorReplyForm();

  // 验证回复表单
  function validatorReplyForm() {
    $('#reply-form')
      .bootstrapValidator({
        fields: {
          reply_name: {
            validators: {
              notEmpty: {
                message: '回复内容不能为空'
              }
            }
          }
        }
      })
      .on('success.form.bv', function (e) {
        // 阻止表单默认提交
        e.preventDefault();

        var order_id = $editConfirm.data('id'),
          content = $('#reply_name').val(),
          post_data;

        // 提交数据
        post_data = {
          order_id: order_id,
          content: content
        }

        post_url = __BASEURL__ + 'mshop/comment_api/reply';

        $editConfirm.prop('disabled', true);

        $.post(post_url, autoCsrf(post_data), function (data) {
          if (data.success) {
            new Msg({
              type: 'success',
              msg: data.msg
            });
            getCommentList(cur_page);
          } else {
            new Msg({
              type: 'danger',
              msg: data.msg
            });
          }
          $editConfirm.prop('disabled', false);
          $editReplyModal.modal('hide');
        });
      });
  }

  function changeHide(id, is_hide) {
    $.post(__BASEURL__ + 'mshop/comment_api/hide', autoCsrf({
      order_id: id,
      is_hide: is_hide
    }), function (data) {
      if (data.success) {
        new Msg({
          type: 'success',
          msg: data.msg
        });
        getCommentList(1);
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
    });
  }

  function changeReply(el) {
    var value = $(el).find('input').val();
    reply_type = value;
    getCommentList(1);
  }

  function changeScore(el) {
    var value = $(el).find('input').val();
    score = value;
    getCommentList(1);
  }

  window.changeScore = changeScore;
  window.changeReply = changeReply;
  window.changeHide = changeHide;
  window.reply = reply;
  window.changeShop = changeShop;
});