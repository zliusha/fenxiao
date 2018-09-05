/**
 * discount_list.js
 * by jimmu
 * date: 2017-11-06
 */
$(function () {
  var $delActiveModal=$("#delActiveModal"),
    $endActiveModal=$("#endActiveModal"),
    $delConfirm=$("#del-confirm"),
    $endConfirm=$("#end-confirm"),
    $btnSearch = $("#btn-search"),
    $active_state=$("#active_state"),
    $shop=$("#shop"),
    $searchVal=$("#searchVal");

  var activeTpl = document.getElementById('activeTpl').innerHTML;


  // 搜索字段
  var cur_page = 1,
    page_size = 10,
    shop_id = $shop.val(),
    status = $active_state.val(),
    title = $searchVal.val();

   var activeData={
     rows:[

     ]
   };

  getActiveList(cur_page);

  // 获取商品列表
  function getActiveList(curr) {
    $.getJSON(__BASEURL__ + 'mshop/promotion_api/index/1', {
      status:status,
      shop_id:shop_id,
      title:title,
      current_page: curr || 1,
      page_size: page_size
    }, function (data) {
      if (data.success) {
        var pages = Math.ceil(+data.data.total / page_size);
        var activeData=data.data;
        var statusName="";
        for(var i=0;i<activeData.rows.length;i++){
          if(activeData.rows[i].status==1){
            statusName="未开始";
            activeData.rows[i].statusName=statusName;
          }
          if(activeData.rows[i].status==2){
            statusName="进行中";
            activeData.rows[i].statusName=statusName;
          }
          if(activeData.rows[i].status==3){
            statusName="已结束";
            activeData.rows[i].statusName=statusName;
          }
        }

        $("#activeTbody").html(template(activeTpl, activeData));

        laypage({
          cont: 'activePage',
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
              getActiveList(obj.curr);
            }
          }
        });
      }
    });
  }


  // 修改门店
  $shop.on("change", function () {
    shop_id = $(this).val();

    getActiveList(1);
  });

  // 修改状态
  $active_state.on("change", function () {
    status = $(this).val();
    getActiveList(1);
  });


  // 搜索
  $btnSearch.on("click", function () {
    title = $searchVal.val();

    getActiveList(1);
  });
   //获取初始数据
   function getActiveInfo() {
     activeData={
       rows:[
         {
           name:"我的时间",
           active_status:"进行中",
           fromTime:"2017-10-10",
           toTime:"2017-12-10",
           disType:'打折优惠',
           rule:"不限购",
           num:"2"
         }
       ]
     };
     $("#activeTbody").html(template(activeTpl, activeData));
    /* laypage({
       cont: 'activePage',
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
           getActiveInfo(obj.curr);
         }
       }
     });*/
   }



  //结束
  function endActive(el,index,id) {
    $endActiveModal.modal("show");
    $endConfirm.attr("data_index",index);
    $endConfirm.attr("data_id",id);
  }
  $endConfirm.on("click",function () {
    var index=$(this).attr("data_index");
    var id=$(this).attr("data_id");
    $endConfirm.prop('disabled', true);
    $.post(__BASEURL__ + 'mshop/promotion_api/close', autoCsrf({id:id}), function (data) {
      if (data.success) {
        $endActiveModal.modal("hide");
       /* $(".endBtn"+index).hide();
        $(".endOfBtn"+index).show();
        $(".delBtn"+index).show();*/
        getActiveList(1);
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
      $endConfirm.prop('disabled', false);
    });
  });

  //删除
  function delActive(el,id) {
    $delActiveModal.modal("show");
    $delConfirm.attr("data_id",id);
  }
  $delConfirm.on("click",function () {
    var id=$(this).attr("data_id");
    $delConfirm.prop('disabled', true);
    $.post(__BASEURL__ + 'mshop/promotion_api/delete', autoCsrf({id:id}), function (data) {
      if (data.success) {
        $delActiveModal.modal("hide");
        getActiveList(1);
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
      $delConfirm.prop('disabled', false);
    });

  });

  window.delActive=delActive;
  window.endActive=endActive;
});