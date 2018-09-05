/**
 * comment_list.js
 * by lanran
 * date: 2017-11-23
 */
$(function () {
  var $createTime = $("#create_time"),
    $editConfirm = $('#edit-confirm'),
    $editReplyModal = $('#editReplyModal'),
    dataTrendChart = echarts.init(document.getElementById('data-trend-chart')),
    commentTpl = document.getElementById("commentTpl").innerHTML;

  var start = moment().subtract(29, "days"),
    end = moment();

  var cur_page = 1,
    page_size = 10,
    shop_id = 0,
    reply_type = '',
    score = '',
    tag = '',
    is_have = '',
    create_time = start.format("YYYY-MM-DD") + " - " + end.format("YYYY-MM-DD");

  initDateRange();
  getCommentList();

  // 初始化时间范围
  function initDateRange() {
    function cb(s, e, dateText) {
      create_time = s.format("YYYY-MM-DD") + " - " + e.format("YYYY-MM-DD");
      if (dateText == '自定义') {
        dateText = create_time;
      }
      console.info(dateText)
      $('#comment-time').html(dateText);
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

  function initDataCart(one, two, three, four, five) {
    var total = Number(one) + Number(two) + Number(three) + Number(four) + Number(five);
    var _one = (Number(one) / total * 100).toFixed(2);
    var _two = (Number(two) / total * 100).toFixed(2);
    var _three = (Number(three) / total * 100).toFixed(2);
    var _four = (Number(four) / total * 100).toFixed(2);
    var _five = (Number(five) / total * 100).toFixed(2);
    console.info(_one)
    if (isNaN(_one)) {
      _one = 0;
    }
    if (isNaN(_two)) {
      _two = 0;
    }
    if (isNaN(_three)) {
      _three = 0;
    }
    if (isNaN(_four)) {
      _four = 0;
    }
    if (isNaN(_five)) {
      _five = 0;
    }
    var _data = [_one, _two, _three, _four, _five];
    var xMax = 100;
    option = {
      tooltip: {
        show: true,
        formatter: "{b} {c}"
      },
      grid: {
        left: '15%',
        top: '0',
        bottom: '0',
        right: '15%'
      },
      xAxis: [{
        max: xMax,
        type: 'value',
        axisTick: {
          show: false,
        },
        axisLine: {
          show: false,
        },
        axisLabel: {
          show: false
        },
        splitLine: {
          show: false
        }
      }],
      yAxis: [{
        type: 'category',
        data: ['一星', '二星', '三星', '四星', '五星'],
        nameTextStyle: {
          color: '#b7ce9e',
          fontSize: '18px'
        },
        axisTick: {
          show: false,
        },
        axisLine: {
          show: false,
        }
      }],
      series: [{
        name: ' ',
        type: 'bar',
        barWidth: 10,
        silent: true,
        itemStyle: {
          normal: {
            color: '#f2f2f2'
          }
        },
        label: {
          normal: {
            show: true,
            position: 'right',
            formatter: function (data) {
              return _data[data.dataIndex] + '%'
            },
            color: ['#475669']
          }
        },
        barGap: '-100%',
        barCategoryGap: '50%',
        data: _data.map(function (d) {
          return xMax
        }),
      }, {
        name: ' ',
        type: 'bar',
        barWidth: 10,
        // label: {
        //     normal: {
        //         show: true,
        //         position: 'right',
        //         formatter: '{c}%',
        //     }
        // },
        data: [{
          value: _one,
          itemStyle: {
            normal: {
              color: '#59a2e7'
            }
          }
        }, {
          value: _two,
          itemStyle: {
            normal: {
              color: '#59a2e7'
            }
          }
        }, {
          value: _three,
          itemStyle: {
            normal: {
              color: '#59a2e7'
            }
          }
        }, {
          value: _four,
          itemStyle: {
            normal: {
              color: '#59a2e7'
            }
          }
        }, {
          value: _five,
          itemStyle: {
            normal: {
              color: '#59a2e7'
            }
          }
        }],
        markLine: {
          label: {
            normal: {
              show: true,
              position: 'end',
              formatter: '{b}{c}%'
            }
          },
          lineStyle: {
            normal: {
              color: '#525d63'
            }
          }

        }
      }]
    };
    dataTrendChart.setOption(option);
  }

  // 获取评价列表
  function getCommentList(curr) {
    $.getJSON(
      __BASEURL__ + "mshop/comment_api/comment_list", {
        shop_id: shop_id,
        current_page: curr || 1,
        page_size: page_size,
        time: create_time,
        reply_type: reply_type,
        score: score,
        is_have: is_have,
        tag: tag
      },
      function (data) {
        if (data.success) {
          var pages = Math.ceil(+data.data.total / page_size);
          var comment = {
            rows: [],
            comment_tag: [],
            is_comment: true
          }
          comment.comment_tag = data.data.comment_tag;
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
          for (var j = 0; j < comment.comment_tag.length; j++) {
            comment.comment_tag[j].is_active = false;
            if (tag == comment.comment_tag[j].id) {
              comment.comment_tag[j].is_active = true;
              comment.is_comment = false;
            }
          }
          $("#commentTbody").html(
            template(commentTpl, comment)
          );
          $('#score-one').html(data.data.score.score_one_count);
          $('#score-two').html(data.data.score.score_two_count);
          $('#score-three').html(data.data.score.score_three_count);
          $('#score-four').html(data.data.score.score_four_count);
          $('#score-five').html(data.data.score.score_five_count);
          $('#reply-rate').html(data.data.reply_rate + '%');
          $('#low-reply-rate').html(data.data.low_score_reply_rate + '%');
          one = data.data.score.score_one_count;
          two = data.data.score.score_two_count;
          three = data.data.score.score_three_count;
          four = data.data.score.score_four_count;
          five = data.data.score.score_five_count;
          initDataCart(one, two, three, four, five);
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

  $('body').on('click', '.label-primary', function () {
    var id = $(this).attr('data-id');
    $(this).addClass('active');
    $(this).siblings().removeClass('active');
    tag = id;
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
});