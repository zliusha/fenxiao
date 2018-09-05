$(function () {
  var id = GetQueryString("id"),
    afsDetailTpl = document.getElementById("afsDetailTpl").innerHTML;

  getAfsDetail();

  // 获取售后详情
  function getAfsDetail() {
    $.getJSON(
      __BASEURL__ + "api/afs/detail", {
        id: id
      },
      function (data) {
        if (data.success) {
          $("#afs-detail").html(template(afsDetailTpl, data.data));
        } else {
          layer.open({
            content: data.msg,
            skin: "msg",
            time: 1
          });
        }
      }
    );
  }

  // 撤销退款申请
  function undoRefund() {
    layer.open({
      content: '如果主动撤销正在处理中的退款，你将无法再次发起退款申请',
      btn: ['确定', '取消'],
      yes: function (index) {
        $.post(
          __BASEURL__ + "api/afs/cancel", autoCsrf({
            afsno: id
          }),
          function (data) {
            if (data.success) {
              window.location.href = __BASEURL__ + "order";
            } else {
              layer.open({
                content: data.msg,
                skin: "msg",
                time: 1
              });
            }

            layer.closeAll();
          }
        );
      }
    });
  }

  window.undoRefund = undoRefund;
});