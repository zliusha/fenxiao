/**
 * erp_list.js
 * by lanran
 * date: 2018-1-29
 */
$(function () {
  var erpTpl = document.getElementById('erpTpl').innerHTML;

  // 获取erp信息
  function getErpList() {
    var value = $('#searchVal').val();
    var type = $('#active_state option:selected').val();
    if(value==''){
      new Msg({
        type: "danger",
        msg: '请输入对应的值'
      });
      return false;
    }
    if(type=='mobile'){
      if(!PregRule.Tel.test(value)){
        new Msg({
          type: "danger",
          msg: '手机号格式不正确'
        });
        return false;
      }
    }
    $.getJSON(__BASEURL__ + 'erp_api/get_user_info', {
      type:type,
      value:value
    }, function (data) {
      if (data.success) {
        if(data.data){
          if(!data.data.erp_model.visit_id){
            data.data = [];
          }
        }else{
          data.data = [];
        }
        $('#erpTbody').html(template(erpTpl, data));
      }else{
        new Msg({
          type: "danger",
          msg: data.msg
        });
      }
    });
  }

  $('#btn-search').click(function(){
    getErpList();
  })


});