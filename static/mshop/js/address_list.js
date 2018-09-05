$(function () {
  var addressTpl = document.getElementById("addressTpl").innerHTML;

  var address = {
      list: []
  };

  getAddressList();

  // 获取地址列表
  function getAddressList() {
    $.get(__BASEURL__ + "api/address", function (data) {
      if (data.success) {
        address.list = data.data;

        $("#address-list").html(template(addressTpl, address));
      }
    });
  }

  // 设置默认地址
  function setDefault(id) {
    $.post(
      __BASEURL__ + "api/address/set_default",
      autoCsrf({
        receiver_address_id: id,
        is_default: 1
      }),
      function (data) {
        if (data.success) {
          layer.open({
            content: '设置成功',
            skin: "msg",
            time: 1
          });

          getAddressList();
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

  // 添加收货地址
  function addAddress() {
    window.location.href = __BASEURL__ + "address/add";
  }

  // 删除收货地址
  function delAddress(id) {
    $.post(
      __BASEURL__ + "api/address/del",
      autoCsrf({
        receiver_address_id: id
      }),
      function (data) {
        if (data.success) {
          layer.open({
            content: '删除成功',
            skin: "msg",
            time: 1
          });

          getAddressList();
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

  // 选择收货地址
  function chooseAddress(id){
    if(!id){
      return false;
    }

    window.location.href = document.referrer + '?addr_id='+id;
  }

  window.setDefault = setDefault;
  window.addAddress = addAddress;
  window.delAddress = delAddress;
  window.chooseAddress = chooseAddress;
});